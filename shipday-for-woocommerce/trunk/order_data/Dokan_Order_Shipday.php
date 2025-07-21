<?php
require_once dirname( __DIR__ ) . '/functions/common.php';
require_once dirname( __DIR__ ) . '/date-modifiers/order_delivery_date.php';

class Dokan_Order_Shipday extends Woo_Order_Shipday {
	protected $order, $vendor_id;

    private $prevent_flag;
	function __construct($post) {
        shipday_logger('info', 'Constructing Dokan order from order id '.$post);
        if ($this->has_suborders($post)) {
            $this->prevent_flag = true;
        } else {
            $this->prevent_flag = false;
            $this->order = wc_get_order($post);
            $this->vendor_id   = dokan_get_seller_id_by_order( $this->order );
        }
    }

    function get_dokan_api_key() {
        if (get_shipday_order_manager() == 'admin_manage') return get_shipday_api_key();
        $api_key            = get_user_meta( $this->vendor_id, 'shipday_api_key', true );
        return shipday_handle_null($api_key);
    }

	public function get_payloads() {
        $api_key = $this->get_dokan_api_key();
        shipday_logger('INFO', 'Vendor Id:'.$this->vendor_id. ', API Key:'.$api_key);
        
        if ($this->is_pickup_order() && get_shipday_pickup_enabled()) {
            // For pickup orders, use the pickup payload structure
            $payload = $this->get_dokan_pickup_payload();
        } else {
            // For delivery orders, use the existing payload structure
            $payload = array_merge(
                $this->get_payload_without_dependant_info(),
                $this->get_vendor_info(),
                get_shipday_pickup_delivery_times($this->order),
                $this->get_signature()
            );
        }
        
        $payloads[$api_key][] = $this->get_user_filtered_payload($payload);

		return $payloads;
	}

	public function has_suborders($post) {
		return (bool) get_children(
			array(
				'post_parent' => $post,
				'post_type'   => 'shop_order',
			)
		);
	}
	public function get_vendor_info() {
		$vendor = new \WeDevs\Dokan\Vendor\Vendor($this->vendor_id);
		$pickup_store = shipday_handle_null($vendor->get_shop_name());
		$address = shipday_handle_null(implode(', ', $vendor->get_address()));
		$phone = shipday_handle_null($vendor->get_phone());

		return array(
			"restaurantName"    => $pickup_store,
			"restaurantAddress" => $address,
			"restaurantPhoneNumber" => $phone
		);
	}

	function get_signature(): array {
        $data = parent::get_signature();
        $data['signature']['type'] = 'multi-vendor';
        $data['signature']['vendor id'] = $this->vendor_id;
        $data['signature']['Order Managed By'] = get_shipday_order_manager();
        $data['signature']['plugin'] = 'Dokan';
        $data['signature']['Dokan version'] = dokan()->version;
        return $data;
	}

    public function prevent_order_sync() {
        return $this->prevent_flag | parent::prevent_order_sync();
    }
    
    private function get_dokan_pickup_payload(): array {
        return array_merge(
            $this->get_ids(),
            $this->get_dokan_pickup_restaurant_info(),
            $this->get_pickup_customer_info(),
            $this->get_pickup_costing(),
            $this->get_order_items(),
            $this->get_payment_info(),
            $this->get_pickup_instructions(),
            $this->get_pickup_times(),
            array('orderSource' => 'WooCommerce-Dokan')
        );
    }
    
    private function get_dokan_pickup_restaurant_info(): array {
        $store_info = dokan_get_store_info($this->vendor_id);

        $vendor_name = isset($store_info['store_name']) ? shipday_handle_null($store_info['store_name']) : '';
        $vendor_phone = isset($store_info['phone']) ? shipday_handle_null($store_info['phone']) : '';

        $address_parts = array();
        if (isset($store_info['address']['street_1']) && !empty($store_info['address']['street_1'])) {
            $address_parts[] = $store_info['address']['street_1'];
        }
        if (isset($store_info['address']['street_2']) && !empty($store_info['address']['street_2'])) {
            $address_parts[] = $store_info['address']['street_2'];
        }
        if (isset($store_info['address']['city']) && !empty($store_info['address']['city'])) {
            $address_parts[] = $store_info['address']['city'];
        }
        if (isset($store_info['address']['state']) && !empty($store_info['address']['state'])) {
            $address_parts[] = $store_info['address']['state'];
        }
        if (isset($store_info['address']['zip']) && !empty($store_info['address']['zip'])) {
            $address_parts[] = $store_info['address']['zip'];
        }
        if (isset($store_info['address']['country']) && !empty($store_info['address']['country'])) {
            $address_parts[] = $store_info['address']['country'];
        }
        
        $vendor_address = implode(', ', $address_parts);
        
        // If vendor phone is empty, try to get store phone as fallback
        if (empty($vendor_phone)) {
            $vendor_phone = shipday_handle_null(get_option('woocommerce_store_phone'));
            if (empty($vendor_phone)) {
                $vendor_phone = '+1-000-000-0000'; // Default placeholder
            }
        }
        
        return array(
            'restaurant' => array(
                'name'    => $vendor_name,
                'address' => $vendor_address,
                'phone'   => $vendor_phone
            )
        );
    }
}