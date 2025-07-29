<?php
require_once dirname(__DIR__). '/functions/logger.php';
require_once dirname(__DIR__). '/functions/common.php';
require_once dirname(__DIR__). '/dispatch_post/post_fun.php';

require_once dirname(__DIR__). '/order_data/Woo_Order_Shipday.php';
require_once dirname(__DIR__). '/order_data/Dokan_Order_Shipday.php';
require_once dirname(__DIR__). '/order_data/WCFM_Order_Shipday.php';
require_once dirname(__DIR__). '/order_data/FoodStore_Order_Shipday.php';

require_once dirname(__DIR__). '/date-modifiers/order_delivery_date.php';

class Shipday_Order_Management {
    private static $persistance_time = 60*60*24*30;

    public static function init() {
		add_action('woocommerce_order_status_processing', __CLASS__.'::process_and_send');
	}
    public static function map_to_transient($order_id) {
        return 'shipday_order_posted'.$order_id;
    }

    public static function is_duplicate($order_id) {
        return get_transient(self::map_to_transient($order_id));
    }

    public static function register_as_posted($order_id) {
        set_transient(self::map_to_transient($order_id), true, self::$persistance_time);
    }

    public static function unregister_as_posted($order_id) {
        set_transient(self::map_to_transient($order_id), false, self::$persistance_time);
    }

	public static function process_and_send($order_id) {

        if (self::is_duplicate($order_id)) return ;
        self::register_as_posted($order_id);
        shipday_logger('info', $order_id.': Shipday Order Management Process started');
		if ( is_plugin_active( 'dokan-lite/dokan.php' ) )
            $order_data_object = new Dokan_Order_Shipday( $order_id ) ;
		elseif ( is_plugin_active( 'wc-multivendor-marketplace/wc-multivendor-marketplace.php' ) )
			$order_data_object = new WCFM_Order_Shipday( $order_id ) ;
        elseif (is_plugin_active('food-store/food-store.php')){
            $order_data_object = new FoodStore_Order_Shipday($order_id);
        } else
			$order_data_object = new Woo_Order_Shipday( $order_id );

        if ($order_data_object->prevent_order_sync()) {
            shipday_logger('info', $order_id.': Not a shipday order');
            return;
        }

        $is_pickup = $order_data_object-> is_pickup_order();
        
        if ($is_pickup && !get_shipday_pickup_enabled()) {
            shipday_logger('info', $order_id . ': Order filtered out as pickup order (pickup orders disabled)');
            return;
        }

        $order_type = $is_pickup ? 'pickup' : 'delivery';
        shipday_logger('info', $order_id.': Shipday Order Management Process post sending starts for ' . $order_type . ' order' );
        
        $payloads = array();
        $success = false;
        
        try {
            $payloads = $order_data_object->get_payloads();
        } catch (Exception $exception) {
            shipday_logger('error', $order_id.': Shipday Order Management Process get_payloads failed');
        }
        
        try {
            if ($is_pickup && get_shipday_pickup_enabled()) {
                shipday_logger('info', $order_id.': Sending pickup order to Shipday pickup API');
                $success = shipday_post_pickup_orders($payloads);
            } else {
                shipday_logger('info', $order_id.': Sending delivery order to Shipday delivery API');
                $success = shipday_post_orders($payloads);
            }
        } catch (Exception $exception) {
            shipday_logger('info', $order_id.': Shipday Order Management Process post sending failed for ' . $order_type . ' order');
        }
        
        if ($success) {
            shipday_logger('info', $order_id.': Shipday Order Management Process post successfully sent for ' . $order_type . ' order');
        } else {
            shipday_logger('info', $order_id.': Shipday Order Management Process post sending failed for ' . $order_type . ' order');
            self::unregister_as_posted($order_id);
        }

	}
}