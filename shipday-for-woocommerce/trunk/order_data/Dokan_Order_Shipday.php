<?php
require_once dirname( __DIR__ ) . '/functions/common.php';
require_once dirname( __DIR__ ) . '/date-modifiers/order_delivery_date.php';

class Dokan_Order_Shipday extends Woo_Order_Shipday {
	protected $order, $vendor_id;

    private $prevent_flag;
	function __construct($post) {
        logger('info', 'Constructing Dokan order from order id '.$post);
        if ($this->has_suborders($post)) {
            $this->prevent_flag = true;
        } else {
            $this->prevent_flag = false;
            $this->order = wc_get_order($post);
            $this->vendor_id   = dokan_get_seller_id_by_order( $this->order );
        }
    }

    function get_dokan_api_key() {
        if (get_order_manager() == 'admin_manage') return get_shipday_api_key();
        $api_key            = get_user_meta( $this->vendor_id, 'shipday_api_key', true );
        return handle_null($api_key);
    }

	public function get_payloads() {
        $api_key = $this->get_dokan_api_key();
        logger('INFO', 'Vendor Id:'.$this->vendor_id. ', API Key:'.$api_key);
        $payloads[$api_key][] = array_merge(
            $this->get_payload_without_dependant_info(),
            $this->get_vendor_info(),
            get_times($this->order),
            $this->get_signature()
        );

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
		$pickup_store = handle_null($vendor->get_shop_name());
		$address = handle_null(implode(', ', $vendor->get_address()));
		$phone = handle_null($vendor->get_phone());

		return array(
			"restaurantName"    => $pickup_store,
			"restaurantAddress" => $address,
			"restaurantPhoneNumber" => $phone
		);
	}

	function get_signature(): array {
		global $shipday_plugin_version;
		return array(
			'orderSource' => 'woocommerce',
			'signature' => array(
				'version' => $shipday_plugin_version,
				'wooVersion' => WC()->version,
				'type' => 'multi-vendor',
				'vendorId' => $this->vendor_id,
				'plugin' => 'Dokan',
				'dokanVersion' => dokan()->version,
                'orderManagedBy' => get_order_manager(),
				'url' => get_site_url(),
			)
		);
	}

    public function prevent_order_sync() {
        return $this->prevent_flag | parent::prevent_order_sync();
    }
}