<?php
require_once dirname( __DIR__ ) . '/functions/common.php';
require_once dirname( __DIR__ ) . '/date-modifiers/order_delivery_date.php';

class Dokan_Order_Shipday {
	private $post, $sub_orders;

	function __construct($post) {
		$this->post = $post;
	}

	public function get_payloads() {
		$api_key = get_shipday_api_key();
		$this->create_suborders();
		if ($this->sub_orders) {
			foreach ($this->sub_orders as $sub_order) {
				$sub_order = wc_get_order($sub_order);
				$vendor_id   = dokan_get_seller_id_by_order( $sub_order );
				$wc_order = wc_get_order($this->post);
				$payloads[$api_key][] = array_merge(
					(new Woo_Order_Shipday($sub_order))->get_payload_without_dependant_info(),
					$this->get_vendor_info($vendor_id),
					get_times($wc_order),
					$this->get_signature($vendor_id)
				);
			}
		} else {
			$order = wc_get_order($this->post);
			$vendor_id   = dokan_get_seller_id_by_order( $order );
			$payloads[$api_key][] = array_merge(
				(new Woo_Order_Shipday($order))->get_payload_without_dependant_info(),
				$this->get_vendor_info($vendor_id),
				get_times($order)
			);
		}
		return $payloads;
	}

	public function create_suborders() {
		$this->sub_orders = get_children(
			array(
				'post_parent' => $this->post,
				'post_type'   => 'shop_order',
			)
		);
	}
	public function get_vendor_info($vendor_id) {
		$vendor = new \WeDevs\Dokan\Vendor\Vendor($vendor_id);
		$pickup_store = $vendor->get_shop_name();
		$address = implode(', ', $vendor->get_address());
		$phone = $vendor->get_phone();

		return array(
			"restaurantName"    => $pickup_store,
			"restaurantAddress" => $address,
			"restaurantPhoneNumber" => $phone
		);
	}

	function get_signature($vendor_id): array {
		return array(
			'source' => array(
				'name' => 'woocommerce',
				'type' => 'dokan',
				'url' => get_site_url(),
				'vendor_id' => $vendor_id
			)
		);
	}
}