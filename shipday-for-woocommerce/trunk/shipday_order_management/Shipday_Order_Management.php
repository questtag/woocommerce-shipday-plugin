<?php

require_once dirname( __DIR__ ) . '/order_data/Woo_Order_Shipday.php';
require_once dirname(__DIR__). '/order_data/Dokan_Order_Shipday.php';
require_once dirname( __DIR__ ) . '/order_data/WCFM_Order_Shipday.php';
require_once dirname(__DIR__). '/order_data/FoodStore_Order_Shipday.php';

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
        $send = true;

		if ( is_plugin_active( 'dokan-lite/dokan.php' ) )
			$order_data_object = new Dokan_Order_Shipday( $order_id ) ;
		elseif ( is_plugin_active( 'wc-multivendor-marketplace/wc-multivendor-marketplace.php' ) )
			$order_data_object = new WCFM_Order_Shipday( $order_id ) ;
        elseif (is_plugin_active('food-store/food-store.php')){
            $order_data_object = new FoodStore_Order_Shipday($order_id);
            $send = $order_data_object->is_shipday_order();
        } else
			$order_data_object = ( new Woo_Order_Shipday( $order_id ) )->get_payloads();

        if ($send) {
            $success = post_orders($order_data_object->get_payloads());
            if ($success) self::register_as_posted($order_id);
        }
	}
}