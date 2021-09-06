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
		return array(
			get_shipday_api_key() => [$this->get_payload()]
		);
	}

	public function get_payload() {
		return array_merge(
			$this->get_payload_without_dependant_info(),
			$this->get_restaurant_info(),
			get_times($this->order),
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
	function get_restaurant_info( ): array {
		$store_name = handle_null( get_bloginfo( 'name' ) );

		$address1      = handle_null( get_option( 'woocommerce_store_address' ) );
		$address2      = handle_null( get_option( 'woocommerce_store_address_2' ) );
		$city          = handle_null( get_option( 'woocommerce_store_city' ) );
		$post_code     = handle_null( get_option( 'woocommerce_store_postcode' ) );
		$country_state = handle_null( get_option( 'woocommerce_default_country' ) );

		$split_country = explode( ":", $country_state );
		$country_code  = $split_country[0];
		$state_code    = $split_country[1];
		$state         = $this->to_state_name( $state_code, $country_code );
		$country       = $this->to_country_name( $country_code );

		$full_address = $address2 . ', ' . $address1 . ', ' . $city . ', ' . $state . ', ' . $post_code . ', ' . $country;

		return array(
			"restaurantName"    => $store_name,
			"restaurantAddress" => $full_address
		);
	}

	function get_costing(): array {
		$tips         = 0.0;
		$tax          = floatval( $this->order->get_total_tax() );
		$discount     = floatval( $this->order->get_total_discount() );
		$delivery_fee = floatval( $this->order->get_shipping_total() );
		$total        = strval( $this->order->get_total() );

		return array(
			'tips'           => $tips,
			'tax'            => $tax,
			'discountAmount' => $discount,
			'deliveryFee'    => $delivery_fee,
			'totalOrderCost' => $total
		);
	}

	function get_dropoff_object(): array {
		$address = $this->order->get_address( 'shipping' );

		$name         = handle_null( $address['first_name'] ) . ' ' . handle_null( $address['last_name'] );
		$company      = handle_null( $address['company'] );
		$address1     = handle_null( $address['address_1'] );
		$address2     = handle_null( $address['address_2'] );
		$city         = handle_null( $address['city'] );
		$state_code   = handle_null( $address['state'] );
		$post_code    = handle_null( $address['postcode'] );
		$country_code = handle_null( $address['country'] );

		$state   = ! empty( $state_code ) ? WC()->countries->get_states( $country_code )[ $state_code ] : '';
		$country = ! empty( $country_code ) ? ( new WC_Countries() )->get_countries()[ $country_code ] : '';

		return array(
			'dropoff' => array(
				'address' => array(
					'unit'    => $address2,
					'street'  => $address1,
					'city'    => $city,
					'state'   => $state,
					'zip'     => $post_code,
					'country' => $country
				)
			)
		);
	}

	function get_uuid(): array {
		return array(
			'uuid' => get_option('shipday_registered_uuid')
		);
	}

	function get_signature(): array {
		global $shipday_plugin_version;
		return array(
			'orderSource' => 'woocommerce',
			'signature' => array(
				'version' => $shipday_plugin_version,
				'wooVersion' => WC()->version,
				'type' => 'single-vendor',

				'url' => get_site_url()
			)
		);
	}

}