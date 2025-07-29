<?php
require_once dirname( __DIR__ ) . '/functions/common.php';
require_once dirname(__DIR__). '/date-modifiers/order_delivery_date.php';
require_once dirname( __FILE__ ) . '/Woocommerce_Core_Shipday.php';


class Woo_Order_Shipday extends Woocommerce_Core_Shipday {
	protected $order;

	function __construct($order_id) {
		$this->order = wc_get_order($order_id);
	}
	public function get_payloads() {
		if ($this->is_pickup_order() && get_shipday_pickup_enabled()) {
			return array(
				get_shipday_api_key() => [$this->get_user_filtered_payload($this->get_pickup_payload())]
			);
		}
		return array(
			get_shipday_api_key() => [$this->get_user_filtered_payload($this->get_payload())]
		);
	}

	public function get_payload() {
		return array_merge(
			$this->get_payload_without_dependant_info(),
			$this->get_restaurant_info(),
			get_shipday_pickup_delivery_times($this->order),
			$this->get_signature(),
			$this->get_uuid()
		);
	}

	public function get_payload_without_dependant_info() {
		return array_merge(
			$this->get_ids(),
			$this->get_shipping_address(),
			$this->get_costing(),
			$this->get_dropoff_object(),
			$this->get_order_items(),
			$this->get_payment_info(),
			$this->get_message()
		);
	}

	function get_ids() : array {
		return array(
			'orderNumber' => $this->order->get_id(),
			'additionalId' => $this->order->get_id()
		);
	}

	/** Needs more info */
	public static function get_restaurant_info( ): array {
		$store_name = shipday_handle_null( get_bloginfo( 'name' ) );

		$address1      = shipday_handle_null( get_option( 'woocommerce_store_address' ) );
		$city          = shipday_handle_null( get_option( 'woocommerce_store_city' ) );
		$post_code     = shipday_handle_null( get_option( 'woocommerce_store_postcode' ) );
		$country_state = shipday_handle_null( get_option( 'woocommerce_default_country' ) );

		$split_country = explode( ":", $country_state );
		$country_code  = $split_country[0];
		$state_code    = $split_country[1];
		$state         = self::to_state_name( $state_code, $country_code );
		$country       = self::to_country_name( $country_code );

		$full_address = $address1 . ', ' . $city . ', ' . $state . ', ' . $post_code . ', ' . $country;

		return array(
			"restaurantName"    => $store_name,
			"restaurantAddress" => $full_address
		);
	}

	function get_costing(): array {
		$tax          = $this->order->get_total_tax();
		$discount     = $this->order->get_total_discount();
		$delivery_fee = $this->order->get_shipping_total();
		$total        = $this->order->get_total();
        $subtotal     = $this->order->get_subtotal();

        $tips = $total - $subtotal - $tax + $discount - $delivery_fee;

		return array(
			'tips'           => $tips,
			'tax'            => $tax,
			'discountAmount' => $discount,
			'deliveryFee'    => $delivery_fee,
			'totalOrderCost' => strval($total)
		);
	}

	public function get_uuid(): array {
		return array(
			'uuid' => get_option('wc_settings_tab_shipday_registered_uuid')
		);
	}

	function get_signature(): array {
        $data = parent::get_signature();
        $data['signature']['type'] = 'single-vendor';
        $data['signature']['plugin'] = 'vanilla';

        return $data;
	}

	public function get_pickup_payload(): array {
		return array_merge(
			$this->get_ids(),
			$this->get_pickup_restaurant_info(),
			$this->get_pickup_customer_info(),
			$this->get_pickup_costing(),
			$this->get_order_items(),
			$this->get_payment_info(),
			$this->get_pickup_instructions(),
			$this->get_pickup_times(),
			array('orderSource' => 'WooCommerce')
		);
	}

	private function get_pickup_restaurant_info(): array {
		$store_name = shipday_handle_null( get_bloginfo( 'name' ) );
		$store_phone = shipday_handle_null( get_option( 'woocommerce_store_phone' ) );
		
		$address1      = shipday_handle_null( get_option( 'woocommerce_store_address' ) );
		$city          = shipday_handle_null( get_option( 'woocommerce_store_city' ) );
		$post_code     = shipday_handle_null( get_option( 'woocommerce_store_postcode' ) );
		$country_state = shipday_handle_null( get_option( 'woocommerce_default_country' ) );

		$split_country = explode( ":", $country_state );
		$country_code  = isset($split_country[0]) ? $split_country[0] : '';
		$state_code    = isset($split_country[1]) ? $split_country[1] : '';
		$state         = self::to_state_name( $state_code, $country_code );
		$country       = self::to_country_name( $country_code );

		$full_address = $address1 . ', ' . $city . ', ' . $state . ', ' . $post_code . ', ' . $country;

		return array(
			'restaurant' => array(
				'name'    => $store_name,
				'address' => $full_address,
				'phone'   => $store_phone
			)
		);
	}

	protected function get_pickup_customer_info(): array {
		$name = sanitize_user( shipday_handle_null( $this->order->get_billing_first_name() ) ) . ' ' . 
		        sanitize_user( shipday_handle_null( $this->order->get_billing_last_name() ) );
		$phoneNumber  = $this->add_calling_country_code(
			shipday_handle_null( $this->order->get_billing_phone() ), 
			$this->order->get_billing_country()
		);
		$emailAddress = shipday_handle_null( $this->order->get_billing_email() );

		return array(
			'customer' => array(
				'name'  => $name,
				'phone' => $phoneNumber,
				'email' => $emailAddress
			)
		);
	}

	protected function get_pickup_costing(): array {
		$tax      = $this->order->get_total_tax();
		$discount = $this->order->get_total_discount();
		$total    = $this->order->get_total();
		$subtotal = $this->order->get_subtotal();
		
		// For pickup orders, no delivery fee
        $tips = $this->get_tip_amount();

		return array(
			'tips'           => $tips,
			'tax'            => $tax,
			'discountAmount' => $discount,
			'totalOrderCost' => $total
		);
	}

    private function get_tip_amount(): float
    {
        $tip_amount = 0;

        $meta_keys = ['_tip_amount', '_customer_tip', '_gratuity_amount', '_delivery_tip'];
        foreach ($meta_keys as $meta_key) {
            $meta_tip = $this->order->get_meta($meta_key);
            if (!empty($meta_tip) && is_numeric($meta_tip)) {
                return floatval($meta_tip);
            }
        }
        return $tip_amount;
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

}