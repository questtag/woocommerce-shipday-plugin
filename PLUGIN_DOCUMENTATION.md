# WooCommerce Shipday Plugin Documentation

## Table of Contents
1. [Overview](#overview)
2. [Setup & Connectivity](#setup--connectivity)
3. [High-Level Architecture](#high-level-architecture)
4. [Order Lifecycle Guide](#order-lifecycle-guide)
5. [Multi-Vendor Support](#multi-vendor-support)
6. [Date & Time Integration](#date--time-integration)
7. [API Reference](#api-reference)

## Overview

The WooCommerce Shipday Plugin enables seamless integration between WooCommerce stores and Shipday's delivery management platform. It automatically sends order data to Shipday when orders reach processing status, supporting both single-vendor and multi-vendor setups.

**Key Features:**
- Automatic order dispatch to Shipday
- Multi-vendor marketplace support (Dokan, WCFM)
- Delivery date/time scheduling integration
- Previous order synchronization
- REST API webhook support

## Setup & Connectivity

### Prerequisites
- WordPress with WooCommerce installed
- Active Shipday account with API key
- WooCommerce REST API credentials

### Installation Steps

1. **Install Plugin**
   - Upload plugin to `/wp-content/plugins/`
   - Activate through WordPress admin

2. **Configure Shipday Settings**
   Navigate to: `WooCommerce → Settings → Shipday`

   **Required Settings:**
   - **Shipday API Key**: Get from Shipday Dashboard → My Account → Profile → API Key
   - **Consumer Key & Secret**: Generate in WooCommerce → Settings → Advanced → REST API
     - Set permissions to `Read/Write`
     - Copy credentials immediately (shown only once)

3. **Optional Settings**
   - **Sync Previous Orders**: Enable to send existing processing orders to Shipday

### Configuration Verification

```mermaid
graph LR
    A[Plugin Activation] --> B[Enter API Keys]
    B --> C[REST API Registration]
    C --> D[UUID Generated]
    D --> E[Ready for Orders]
```

## High-Level Architecture

```mermaid
graph TD
    subgraph "WordPress/WooCommerce"
        A[Order Created] --> B[Status: Processing]
        B --> C[Shipday_Order_Management]
        
        subgraph "Order Data Processing"
            C --> D{Vendor Type?}
            D -->|Single| E[Woo_Order_Shipday]
            D -->|Dokan| F[Dokan_Order_Shipday]
            D -->|WCFM| G[WCFM_Order_Shipday]
            D -->|FoodStore| H[FoodStore_Order_Shipday]
        end
        
        subgraph "Data Preparation"
            E --> I[Order Payload]
            F --> I
            G --> I
            H --> I
            I --> J[Date/Time Modifiers]
            J --> K[Complete Payload]
        end
    end
    
    subgraph "External Communication"
        K --> L[shipday_post_order]
        L --> M{Transport}
        M -->|Primary| N[cURL POST]
        M -->|Fallback| O[Stream Context]
        N --> P[Shipday API]
        O --> P
    end
    
    P --> Q[Order in Shipday]
```

### Core Components

1. **Entry Point** (`shipday-integration-for-wooCommerce.php`)
   - Initializes all components
   - Checks WooCommerce dependency

2. **Settings Management** (`WC_Settings_Tab_Shipday.php`)
   - Handles plugin configuration
   - Manages vendor-specific settings

3. **Order Processing** (`Shipday_Order_Management.php`)
   - Hooks into order status changes
   - Prevents duplicate submissions
   - Manages order dispatch

4. **Data Models** (`order_data/`)
   - Transforms WooCommerce orders to Shipday format
   - Handles vendor-specific data

5. **API Communication** (`dispatch_post/post_fun.php`)
   - Sends orders to Shipday API
   - Implements retry mechanism

## Order Lifecycle Guide

```mermaid
sequenceDiagram
    participant Customer
    participant WooCommerce
    participant Plugin
    participant Shipday API
    
    Customer->>WooCommerce: Place Order
    WooCommerce->>WooCommerce: Order Status: Pending
    
    alt Payment Completed
        WooCommerce->>Plugin: Status → Processing
        Plugin->>Plugin: Check Duplicate
        Plugin->>Plugin: Build Payload
        
        Note over Plugin: Extract order data<br/>Add delivery times<br/>Include vendor info
        
        Plugin->>Shipday API: POST /orders
        Shipday API-->>Plugin: 200 OK
        Plugin->>Plugin: Mark as Posted
        
        Note over Shipday API: Order ready for<br/>dispatch management
    else Payment Failed
        WooCommerce->>Customer: Order Cancelled
    end
```

### Detailed Order Flow

1. **Order Trigger**
   - Hook: `woocommerce_order_status_processing`
   - File: `Shipday_Order_Management.php:15`

2. **Duplicate Prevention**
   - Uses WordPress transients (30-day persistence)
   - Key: `shipday_order_posted{order_id}`

3. **Order Filtering**
   - Skips pickup orders (`local_pickup` shipping method)
   - Checks `_shipday_order_sync_prevent` meta

4. **Payload Construction**
   ```
   Order Data Structure:
   ├── Order IDs (orderNumber, additionalId)
   ├── Customer Info
   │   ├── Name, Email, Phone
   │   └── Shipping/Billing Address
   ├── Restaurant Info
   │   ├── Store Name
   │   └── Store Address
   ├── Order Items
   │   └── Name, Quantity, Unit Price
   ├── Costing
   │   ├── Subtotal, Tax, Tips
   │   ├── Discount, Delivery Fee
   │   └── Total
   ├── Delivery Instructions
   ├── Payment Method
   └── Metadata
       ├── Plugin Version
       ├── WooCommerce Version
       └── Timezone Info
   ```

5. **API Communication**
   - Endpoint: `https://api.shipday.com/orders`
   - Auth: Basic Auth with API Key
   - Retry: Falls back to stream context on cURL failure

## Multi-Vendor Support

### Supported Platforms
- **Dokan** (Lite/Pro)
- **WCFM** (WC Marketplace)
- **FoodStore**

### Vendor Management Options

```mermaid
graph TD
    A[Multi-Vendor Order] --> B{Management Mode}
    B -->|Admin Manages| C[Single Shipday Account]
    B -->|Vendor Manages| D[Multiple Shipday Accounts]
    
    C --> E[Admin API Key Used]
    D --> F[Vendor API Keys Required]
    
    F --> G[Each Vendor Dashboard]
    G --> H[Individual Shipday Settings]
```

### Configuration by Platform

**Dokan Settings:**
- Location: `WooCommerce → Settings → Shipday`
- Options:
  - Admin manages all deliveries
  - Vendors manage own deliveries

**WCFM Settings:**
- Similar structure to Dokan
- Vendor settings in WCFM dashboard

## Date & Time Integration

### Supported Plugins
1. CodeRockz Woo Delivery (Free/Pro)
2. Tyche Order Delivery Date (Free/Pro)
3. WooCommerce Delivery Area Pro

### Data Flow
```
Order → Date Plugin → Date Modifier → Shipday Payload
                         ↓
                   Extract Times:
                   - expectedDeliveryDate
                   - expectedDeliveryTime
                   - expectedPickupTime
```

## API Reference

### Endpoints

**Shipday Order API**
- URL: `https://api.shipday.com/orders`
- Method: POST
- Auth: Basic Auth (API Key)

**REST API Registration**
- URL: `https://api.shipday.com/woocommerce/install`
- Purpose: Register WooCommerce REST API for webhooks

### Hooks & Filters

**Actions:**
- `woocommerce_order_status_processing` - Triggers order dispatch
- `shipday_settings_updated` - Triggers REST API registration

**Filters:**
- `shipday_order_data_filter` - Modify order payload before sending
- `wc_settings_tab_shipday_settings` - Customize settings fields

### Error Handling

1. **API Failures**
   - Logged to `functions/logger.php`
   - Transient cleared for retry

2. **Validation**
   - REST API credentials verified
   - Order data sanitized
   - Emoji removal for compatibility

### Security Considerations

1. **API Key Storage**
   - Stored in WordPress options table
   - Never exposed in frontend

2. **REST API Security**
   - Requires Read/Write permissions
   - Consumer secret validation

3. **Data Sanitization**
   - User inputs sanitized
   - SQL prepared statements used
