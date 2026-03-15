<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once dirname(__DIR__). '/functions/common.php';
require_once dirname(__FILE__). '/Shipday_Order_Management.php';

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Legacy class name retained for backwards compatibility.
class Woo_Sync_Order
{
    public static function init(){
        if (!get_shipday_sync_status()) return;
        reset_shipday_sync_status();
        add_action('woocommerce_after_register_post_type', __CLASS__.'::sync');
    }
    public static function get_processing_orders() {
        $query = new WC_Order_Query( array(
            'status' => array('wc-processing'),
            'orderby' => 'date',
            'return' => 'ids',
        ) );
        return $query->get_orders();
    }

    public static function sync(){
        $orders = self::get_processing_orders();
        foreach ( $orders as $order_id) {
            Shipday_Order_Management::process_and_send($order_id);
        }
    }

}
