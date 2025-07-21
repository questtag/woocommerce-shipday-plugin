# Pickup Orders Feature Documentation

## Overview

This document describes the implementation of the pickup orders feature for the WooCommerce Shipday plugin. The feature allows stores to send pickup orders to Shipday's dedicated pickup API endpoint when enabled.

## Implementation Details

### 1. Settings Configuration

**File**: `views/WC_Settings_Tab_Shipday_menus.php`

Added a new checkbox setting in the Orders Settings section:

```php
array(
    'title'       => __( 'Enable pickup orders', 'woocommerce-settings-tab-shipday' ),
    'label'       => __( 'Enable pickup orders', 'woocommerce-settings-tab-shipday'),
    'type'        => 'checkbox',
    'description' => 'Allow orders with local pickup shipping method to be sent to Shipday',
    'default'     => 'no',
    'id' => 'wc_settings_tab_shipday_enable_pickup'
),
```

**Location in UI**: WooCommerce → Settings → Shipday → Orders Settings

### 2. Helper Functions

**File**: `functions/common.php`

#### Added Functions:

1. **`get_shipday_pickup_enabled()`**
   - Retrieves the pickup orders setting from WordPress options
   - Returns `true` if enabled, `false` otherwise

```php
function get_shipday_pickup_enabled() {
    $key = get_option('wc_settings_tab_shipday_enable_pickup');
    return shipday_handle_null($key) == 'yes';
}
```

2. **`get_shipday_pickup_api_url()`**
   - Returns the pickup orders API endpoint
   - URL: `https://api.shipday.com/pickup-orders`

```php
function get_shipday_pickup_api_url(): string {
    return 'https://api.shipday.com/pickup-orders';
}
```

### 3. API Communication

**File**: `dispatch_post/post_fun.php`

#### Added Function:

**`shipday_post_pickup_orders(array $payloads)`**
- Dedicated function for posting pickup orders
- Uses the same authentication and payload structure as delivery orders
- Sends to the pickup-specific API endpoint
- Includes specific logging for pickup orders

```php
function shipday_post_pickup_orders(array $payloads) {
    global $shipday_debug_flag;
    $success = false;
    shipday_logger('INFO', 'Pickup order payload: ' . json_encode($payloads));

    foreach ($payloads as $api_key => $payload_array) {
        $api_key = trim($api_key);
        foreach ($payload_array as $payload){
            $response = shipday_post_order($payload, $api_key, get_shipday_pickup_api_url());
            $success |= ($response['http_code'] == 200);
            if ($response['http_code'] != 200) {
                shipday_logger('error', 'Pickup order post failed for API key: '.$api_key);
            }
            // Debug mode handling...
        }
    }
    return $success;
}
```

### 4. Order Processing Logic

**File**: `shipday_order_management/Shipday_Order_Management.php`

#### Modified `process_and_send()` method:

The order processing flow now:

1. **Detects pickup orders** using `is_pickup_order()` method
2. **Checks settings** to determine if pickup orders should be processed
3. **Routes to appropriate API**:
   - Pickup orders → `shipday_post_pickup_orders()` → Pickup API
   - Delivery orders → `shipday_post_orders()` → Delivery API
4. **Logs appropriately** with order type context

```php
$is_pickup = $order_data_object->is_pickup_order();

if ($is_pickup && !get_shipday_pickup_enabled()) {
    shipday_logger('info', $order_id . ': Order filtered out as pickup order (pickup orders disabled)');
    return;
}

// Route to appropriate API based on order type
if ($is_pickup && get_shipday_pickup_enabled()) {
    shipday_logger('info', $order_id.': Sending pickup order to Shipday pickup API');
    $success = shipday_post_pickup_orders($payloads);
} else {
    shipday_logger('info', $order_id.': Sending delivery order to Shipday delivery API');
    $success = shipday_post_orders($payloads);
}
```

## Order Flow Diagram

```mermaid
graph TD
    A[Order Status: Processing] --> B{Is Pickup Order?}
    B -->|No| C[Send to Delivery API]
    B -->|Yes| D{Pickup Enabled?}
    D -->|No| E[Filter Out<br/>Log: Pickup Disabled]
    D -->|Yes| F[Send to Pickup API]
    
    C --> G[/orders endpoint]
    F --> H[/pickup-orders endpoint]
    
    G --> I[Success/Failure Logging]
    H --> I
```

## Logging

The implementation includes comprehensive logging at each step:

1. **Order type detection**: "Shipday Order Management Process post sending starts for pickup/delivery order"
2. **Routing decision**: "Sending pickup order to Shipday pickup API" or "Sending delivery order to Shipday delivery API"
3. **Success/Failure**: "Shipday Order Management Process post successfully sent for pickup/delivery order"
4. **Filtering**: "Order filtered out as pickup order (pickup orders disabled)"

## Testing Instructions

1. **Enable the feature**:
   - Navigate to WooCommerce → Settings → Shipday
   - Check "Enable pickup orders" under Orders Settings
   - Save changes

2. **Create a test order**:
   - Add products to cart
   - Choose "Local Pickup" as shipping method
   - Complete checkout

3. **Verify behavior**:
   - Check logs for pickup order processing
   - Confirm order is sent to `/pickup-orders` endpoint
   - Verify payload structure matches delivery orders

## Security Considerations

- Uses the same API key authentication as delivery orders
- No additional credentials required
- Maintains existing security patterns

## Backward Compatibility

- Feature is disabled by default
- Existing behavior unchanged when disabled
- No impact on delivery order processing
