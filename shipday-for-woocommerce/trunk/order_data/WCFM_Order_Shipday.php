<?php

require_once dirname( __DIR__ ) . '/functions/common.php';
require_once dirname( __FILE__ ) . '/Woocommerce_Core_Shipday.php';
require_once dirname(__DIR__). '/date-modifiers/order_delivery_date.php';

class WCFM_Order_Shipday extends Woocommerce_Core_Shipday {
	protected $order;
	private $items_by_vendors;
	private $order_payloads;
	private $api_keys;
	function __construct($order_id) {
		$this->order            = wc_get_order($order_id);
		$this->items_by_vendors = $this->split_items_by_vendors();
		$this->generate_payloads_api_keys();
	}

	public function get_payloads() {
		for ($i = 0; $i < count($this->api_keys); $i++) {
			$api_key = $this->api_keys[$i];
			$payload = $this->order_payloads[$i];
			$payloads[$api_key][] = $payload;
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
			$payload                = array_merge(
				$this->get_ids(),
				$this->get_shipping_address(),
				$this->get_vendor_info($store_id),
				$this->get_order_items($items),
				$this->get_costing($items),
				$this->get_message(),
				$this->get_signature($store_id),
				get_times($this->order)
			);
			$this->order_payloads[] = $payload;
			$vendor_data            = get_user_meta( $store_id, 'wcfmmp_profile_settings', true );
			$api_key = (!is_null($vendor_data['shipday']['api_key']) &&
			            strlen($vendor_data['shipday']['api_key'] > 0) ) ?
				$vendor_data['shipday']['api_key'] : get_shipday_api_key();
			$this->api_keys[] = $api_key;
		}
	}
	function get_ids() : array {
		return array(
			'orderNumber' => $this->order->get_id(),
			'additionalId' => $this->order->get_id()
		);
	}

	function get_vendor_info($store_id) : array {
		$store_user    = wcfmmp_get_store( $store_id );
		$store_name = $store_user->get_shop_name();

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


	function get_costing($items) : array {
		$tips = 0.0;
		$tax = 0;
		$discount = 0.0;
		$delivery_fee = 0.0;
		$total = 0;
		foreach ($items as $item) {
			$tax          += floatval( $item->get_total_tax() );
			//$discount     += floatval( $item->get_total_discount() );         function does not exist
			//$delivery_fee += floatval( $item->get_shipping_total() );         function does not exist
			$total        += floatval( $item->get_total() );
		}
		$costing = array(
			'tips'           => $tips,
			'tax'            => $tax,
			'discountAmount' => $discount,
			'deliveryFee'    => $delivery_fee,
			'totalOrderCost' => strval($total)
		);

		return $costing;
	}
	function get_signature($store_id): array {
		return array(
			'source' => array(
				'name' => 'woocommerce',
				'type' => 'wcfm',
				'url' => get_site_url(),
				'vendor_id' => $store_id
			)
		);
	}

}