<?php

require_once dirname( __DIR__ ) . '/order_data/Woo_Order_Shipday.php';
require_once dirname(__DIR__). '/order_data/Dokan_Order_Shipday.php';
require_once dirname( __DIR__ ) . '/order_data/WCFM_Order_Shipday.php';

require_once dirname(__DIR__). '/date-modifiers/order_delivery_date.php';

class Shipday_Order_Management {
	public static function init() {
		add_action('woocommerce_thankyou', __CLASS__.'::checkout_process');
	}
    public static function map_to_transient($order_id) {
        return 'shipday_order_posted'.$order_id;
    }

    public static function is_duplicate($order_id) {
        return get_transient(self::map_to_transient($order_id));
    }

    public static function register_as_posted($order_id) {
        $persistance_time = 60*60*24*30;
        set_transient(self::map_to_transient($order_id), true, $persistance_time);
    }

	public static function checkout_process($order_id) {

        if (self::is_duplicate($order_id)) return ;

		if ( is_plugin_active( 'dokan-lite/dokan.php' ) )
			$payloads = ( new Dokan_Order_Shipday( $order_id ) )->get_payloads();
		elseif ( is_plugin_active( 'wc-multivendor-marketplace/wc-multivendor-marketplace.php' ) )
			$payloads = ( new WCFM_Order_Shipday( $order_id ) )-> get_payloads();
		else
			$payloads = ( new Woo_Order_Shipday( $order_id ) )->get_payloads();

		$success = post_orders($payloads);
        if ($success) self::register_as_posted($order_id);
	}
}