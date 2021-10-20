<?php
require_once dirname( __DIR__ ) . '/functions/common.php';
require_once dirname( __DIR__ ) . '/date-modifiers/order_delivery_date.php';

class Dokan_Order_Shipday {
	private $post, $sub_orders;

	function __construct($post) {
        logger('info', 'Constructing Dokan order from order id '.$post);

        $this->post = $post;
	}

    function get_dokan_api_key($vendor_id) {
        if (get_order_manager() == 'admin_manage') return get_shipday_api_key();
        $api_key            = get_user_meta( $vendor_id, 'shipday_api_key', true );
        return handle_null($api_key);
    }

	public function get_payloads() {
		$this->create_suborders();
		if ($this->sub_orders) {
//			foreach ($this->sub_orders as $sub_order) {
//				$sub_order = wc_get_order($sub_order);
//				$vendor_id   = dokan_get_seller_id_by_order( $sub_order );
//				$wc_order = wc_get_order($this->post);
//                $api_key = $this->get_dokan_api_key($vendor_id);
//                echo $api_key;
//				$payloads[$api_key][] = array_merge(
//					(new Woo_Order_Shipday($sub_order))->get_payload_without_dependant_info(),
//					$this->get_vendor_info($vendor_id),
//					get_times($wc_order),
//					$this->get_signature($vendor_id)
//				);
//			}
            $payloads = [];
		} else {
			$order = wc_get_order($this->post);
			$vendor_id   = dokan_get_seller_id_by_order( $order );
            $api_key = $this->get_dokan_api_key($vendor_id);
            logger('INFO', 'Vendor Id:'.$vendor_id. ', API Key:'.$api_key);
			$payloads[$api_key][] = array_merge(
				(new Woo_Order_Shipday($order))->get_payload_without_dependant_info(),
				$this->get_vendor_info($vendor_id),
				get_times($order),
				$this->get_signature($vendor_id)
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
		$pickup_store = handle_null($vendor->get_shop_name());
		$address = handle_null(implode(', ', $vendor->get_address()));
		$phone = handle_null($vendor->get_phone());

		return array(
			"restaurantName"    => $pickup_store,
			"restaurantAddress" => $address,
			"restaurantPhoneNumber" => $phone
		);
	}

	function get_signature($store_id): array {
		global $shipday_plugin_version;
		return array(
			'orderSource' => 'woocommerce',
			'signature' => array(
				'version' => $shipday_plugin_version,
				'wooVersion' => WC()->version,
				'type' => 'multi-vendor',
				'vendorId' => $store_id,
				'plugin' => 'Dokan',
				'dokanVersion' => dokan()->version,
                'orderManagedBy' => get_order_manager(),
				'url' => get_site_url(),
			)
		);
	}
}