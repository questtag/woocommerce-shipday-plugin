<?php

require_once dirname( __DIR__ ) . '/functions/common.php';
require_once dirname( __FILE__ ) . '/Woocommerce_Core_Shipday.php';
require_once dirname(__FILE__). '/Woo_Order_Shipday.php';
require_once dirname(__DIR__). '/date-modifiers/order_delivery_date.php';

class WCFM_Order_Shipday extends Woocommerce_Core_Shipday {
	protected $order;
    protected $store_shipping;
	private $items_by_vendors;
	private $order_payloads;
	private $api_keys;


	function __construct($order_id) {
        shipday_logger('info', 'Constructing WCFM order from order id '.$order_id);
        try {
            $this->order            = wc_get_order($order_id);
        } catch (Exception $e) {
            shipday_logger('error', $order_id.': WCFM construct wc_get_order failed');
        }
        try {
            $this->store_shipping = (new WCFMmp_Shipping())->get_order_vendor_shipping($this->order);
        } catch (Exception $e) {
            shipday_logger('error', $order_id.': WCFM construct get_order_vendor_shipping failed');
        }
        try {
            $this->items_by_vendors = $this->split_items_by_vendors();
        } catch (Exception $e) {
            shipday_logger('error', $order_id.': WCFM construct split_items_by_vendors failed');
        }
        try {
            $this->generate_payloads_api_keys();
        } catch (Exception $e) {
            shipday_logger('error', $order_id.': WCFM construct generate_payloads_api_keys failed');
        }
	}

	public function get_payloads() {
		for ($i = 0; $i < count($this->api_keys); $i++) {
			$api_key = $this->api_keys[$i];
			$payload = $this->order_payloads[$i];
			$payloads[$api_key][] = $this->get_user_filtered_payload($payload);
		}

		return $payloads;
	}

	function split_items_by_vendors() {
		$items_by_vendors = array();
		foreach ($this->order->get_items() as $item) {
			$product_id = $item->get_product_id();
			$store_id = wcfm_get_vendor_id_by_post($product_id);
			if (!array_key_exists($store_id, $items_by_vendors))
				$items_by_vendors[$store_id] = array();
			$items_by_vendors[$store_id][] = $item;
		}
		return $items_by_vendors;
	}

	function generate_payloads_api_keys() {
		$this->order_payloads = array();
		$this->api_keys       = array();
		foreach ($this->items_by_vendors as $store_id => $items){
			if ($this->is_pickup_order() && get_shipday_pickup_enabled()) {
				// For pickup orders, use the pickup payload structure
				$payload = $this->get_wcfm_pickup_payload($store_id, $items);
			} else {
				// For delivery orders, use the existing payload structure
				$payload = array_merge(
					$this->get_ids(),
					$this->get_shipping_address(),
					$this->get_vendor_info($store_id),
					$this->get_order_items($items),
					$this->get_costing($store_id, $items),
					$this->get_payment_info(),
					$this->get_dropoff_object(),
					$this->get_message(),
					$this->get_signature_for_store($store_id),
					get_shipday_pickup_delivery_times($this->order)
				);
			}
			$this->order_payloads[] = $payload;
			$api_key = $this->get_wcfm_api_key($store_id);
			$this->api_keys[] = $api_key;
		}
	}
	function get_ids() : array {
		return array(
			'orderNumber' => $this->order->get_id(),
			'additionalId' => $this->order->get_id()
		);
	}

	function is_admin_store($store_id) {
		$store_user    = wcfmmp_get_store( $store_id );
		$store_name = $store_user->get_shop_name();
		if ($store_id == 0 && empty($store_name) ) {
			return true;
		}
		return false;
	}

	function get_wcfm_api_key($store_id) {
		if (get_shipday_order_manager() == 'admin_manage' || $this->is_admin_store($store_id)) return get_shipday_api_key();
		$vendor_data            = get_user_meta( $store_id, 'wcfmmp_profile_settings', true );
		return shipday_handle_null($vendor_data['shipday']['api_key']);
	}

	function get_vendor_info($store_id) : array {
		$store_user    = wcfmmp_get_store( $store_id );
		$store_name = $store_user->get_shop_name();

		if ($this->is_admin_store($store_id)) return Woo_Order_Shipday::get_restaurant_info();

		$address = $store_user->get_address();
		$address1 = $address['street_1'];
		$address2 = $address['street_2'];
		$city     = $address['city'];
		$post_code     = $address['zip'];
		$state_code = $address['state'];
		$country_code = $address['country'];

		$state = $this->to_state_name($state_code, $country_code);
		$country       = $this->to_country_name( $country_code );

		$full_address = $address1 . ', ' . $address2 . ', ' . $city . ', ' . $state . ', ' . $post_code . ', ' . $country;

		$phone = self::add_calling_country_code($store_user->get_phone(), $country_code);

		return array(
			"restaurantName"    => $store_name,
			"restaurantAddress" => $full_address,
			"restaurantPhoneNumber" => $phone
		);
	}


	function get_costing($store_id, $items) : array {
        $shipping_info = $this->store_shipping[$store_id];
		$tips = 0.0;
		$tax = 0;
		$discount = 0.0;
		$delivery_fee = floatval($shipping_info['shipping']);
		$total = 0;
		foreach ($items as $item) {
			$tax          += floatval( $item->get_total_tax() );
			$total        += floatval( $item->get_total() );
            $product       = wc_get_product($item->get_product_id());
            $discount     += floatval($item->get_total()) - floatval($product->get_price()) * intval($item->get_quantity());
		}

		$costing = array(
			'tips'           => $tips,
			'tax'            => $tax,
			'discountAmount' => $discount,
			'deliveryFee'    => $delivery_fee,
			'totalOrderCost' => strval($total + $delivery_fee + $tax + $tips)
		);

		return $costing;
	}
    function get_signature_for_store($store_id): array {
        $data = $this->get_signature();
        $data['signature']['vendor id'] = $store_id;
        return $data;
    }
	function get_signature(): array {
        $data = parent::get_signature();
        $data['signature']['type'] = 'multi-vendor';
        $data['signature']['Order Managed By'] = get_shipday_order_manager();
        $data['signature']['plugin'] = 'WCFM';
        $data['signature']['WCFM version'] = WCFM_VERSION;
        $data['signature']['WCFMmp version'] = WCFMmp_VERSION;
        return $data;
	}

	private function get_wcfm_pickup_payload($store_id, $items): array {
		return array_merge(
			$this->get_ids(),
			$this->get_wcfm_pickup_restaurant_info($store_id),
			$this->get_pickup_customer_info(),
			$this->get_wcfm_pickup_costing($store_id, $items),
			$this->get_order_items($items),
			$this->get_payment_info(),
			$this->get_pickup_instructions(),
			$this->get_pickup_times(),
			array('orderSource' => 'WooCommerce-WCFM')
		);
	}

	protected function get_pickup_customer_info(): array {
		$name = sanitize_user(shipday_handle_null($this->order->get_billing_first_name())) . ' ' . 
		        sanitize_user(shipday_handle_null($this->order->get_billing_last_name()));
		$phoneNumber = $this->add_calling_country_code(
			shipday_handle_null($this->order->get_billing_phone()), 
			$this->order->get_billing_country()
		);
		$emailAddress = shipday_handle_null($this->order->get_billing_email());

		return array(
			'customer' => array(
				'name' => $name,
				'phone' => $phoneNumber,
				'email' => $emailAddress
			)
		);
	}

	private function get_wcfm_pickup_costing($store_id, $items): array {
		$tax = 0;
		$discount = 0.0;
		$total = 0;
		
		foreach ($items as $item) {
			$tax += floatval($item->get_total_tax());
			$total += floatval($item->get_total());
			$product = wc_get_product($item->get_product_id());
			$discount += floatval($item->get_total()) - floatval($product->get_price()) * intval($item->get_quantity());
		}
		
		$subtotal = $total + abs($discount);
		// For pickup orders, no delivery fee
		$tips = $total - $subtotal - $tax + $discount;

		return array(
			'tips' => $tips,
			'tax' => $tax,
			'discountAmount' => abs($discount),
			'totalOrderCost' => $total + $tax
		);
	}

	protected function get_pickup_instructions(): array {
		$notes = shipday_handle_null($this->order->get_customer_note());
		$default_instruction = "Please call customer when order is ready.";
		
		return array(
			'pickupInstruction' => $notes ? $notes : $default_instruction
		);
	}

	protected function get_pickup_times(): array {
		$times = get_shipday_pickup_delivery_times($this->order);
		$result = array();
		
		if (isset($times['expectedPickupTime'])) {
			$result['expectedPickupTime'] = $times['expectedPickupTime'];
		}
		
		if (isset($times['expectedDeliveryDate'])) {
			$result['expectedPickupDate'] = $times['expectedDeliveryDate'];
		} elseif (isset($times['expectedPickupDate'])) {
			$result['expectedPickupDate'] = $times['expectedPickupDate'];
		} else {
			// Default to today if no date specified
			$result['expectedPickupDate'] = date('Y-m-d');
		}
		
		// If no time specified, set a default
		if (!isset($result['expectedPickupTime'])) {
			$result['expectedPickupTime'] = date('H:i:s', strtotime('+1 hour'));
		}
		
		return $result;
	}

	private function get_wcfm_pickup_restaurant_info($store_id): array {
		// If admin store, use default store info
		if ($this->is_admin_store($store_id)) {
			$store_name = shipday_handle_null(get_bloginfo('name'));
			$store_phone = shipday_handle_null(get_option('woocommerce_store_phone'));
			if (empty($store_phone)) {
				$store_phone = '+1-000-000-0000'; // Default placeholder
			}
			
			$address1 = shipday_handle_null(get_option('woocommerce_store_address'));
			$city = shipday_handle_null(get_option('woocommerce_store_city'));
			$post_code = shipday_handle_null(get_option('woocommerce_store_postcode'));
			$country_state = shipday_handle_null(get_option('woocommerce_default_country'));
			
			$split_country = explode(":", $country_state);
			$country_code = isset($split_country[0]) ? $split_country[0] : '';
			$state_code = isset($split_country[1]) ? $split_country[1] : '';
			$state = $this->to_state_name($state_code, $country_code);
			$country = $this->to_country_name($country_code);
			
			$full_address = $address1 . ', ' . $city . ', ' . $state . ', ' . $post_code . ', ' . $country;
		} else {
			// Use WCFM methods to get vendor store information
			global $WCFM;
			
			// Get store name using WCFM vendor support
			$store_name = '';
			if (isset($WCFM) && isset($WCFM->wcfm_vendor_support)) {
				$store_name = $WCFM->wcfm_vendor_support->wcfm_get_vendor_store_name_by_vendor($store_id);
			}
			
			// Fallback to store user method if WCFM global not available
			if (empty($store_name)) {
				$store_user = wcfmmp_get_store($store_id);
				$store_name = $store_user->get_shop_name();
			}
			
			// Get vendor address using WCFM method
			$vendor_address = '';
			if (isset($WCFM) && isset($WCFM->wcfm_vendor_support)) {
				$vendor_address = $WCFM->wcfm_vendor_support->wcfm_get_vendor_address_by_vendor($store_id);
			}
			
			// Fallback: Get address from user meta if WCFM global not available
			if (empty($vendor_address)) {
				$vendor_data = get_user_meta($store_id, 'wcfmmp_profile_settings', true);
				$address_parts = array();
				
				if (isset($vendor_data['address']['street_1']) && !empty($vendor_data['address']['street_1'])) {
					$address_parts[] = $vendor_data['address']['street_1'];
				}
				if (isset($vendor_data['address']['street_2']) && !empty($vendor_data['address']['street_2'])) {
					$address_parts[] = $vendor_data['address']['street_2'];
				}
				if (isset($vendor_data['address']['city']) && !empty($vendor_data['address']['city'])) {
					$address_parts[] = $vendor_data['address']['city'];
				}
				if (isset($vendor_data['address']['state']) && !empty($vendor_data['address']['state'])) {
					$state = $this->to_state_name($vendor_data['address']['state'], $vendor_data['address']['country']);
					$address_parts[] = $state;
				}
				if (isset($vendor_data['address']['zip']) && !empty($vendor_data['address']['zip'])) {
					$address_parts[] = $vendor_data['address']['zip'];
				}
				if (isset($vendor_data['address']['country']) && !empty($vendor_data['address']['country'])) {
					$country = $this->to_country_name($vendor_data['address']['country']);
					$address_parts[] = $country;
				}
				
				$vendor_address = implode(', ', $address_parts);
			}
			
			$full_address = $vendor_address;
			
			// Get vendor phone
			$store_phone = '';
			if (isset($vendor_data['phone']) && !empty($vendor_data['phone'])) {
				$store_phone = $vendor_data['phone'];
			} else {
				// Try to get from store user
				$store_user = wcfmmp_get_store($store_id);
				$store_phone = $store_user->get_phone();
			}
			
			if (empty($store_phone)) {
				$store_phone = '+1-000-000-0000'; // Default placeholder
			}
		}

		return array(
			'restaurant' => array(
				'name' => shipday_handle_null($store_name),
				'address' => shipday_handle_null($full_address),
				'phone' => shipday_handle_null($store_phone)
			)
		);
	}

}