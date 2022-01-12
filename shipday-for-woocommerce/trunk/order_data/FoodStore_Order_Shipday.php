<?php
require_once dirname( __DIR__ ) . '/functions/common.php';
require_once dirname(__DIR__). '/date-modifiers/order_delivery_date.php';
require_once dirname( __FILE__ ) . '/Woo_Order_Shipday.php';

class FoodStore_Order_Shipday extends Woo_Order_Shipday
{
    function __construct($order_id) {
        logger('info', 'Constructing FoodStore order from order id '.$order_id);
        parent::__construct($order_id);
    }

    public function prevent_order_sync() {
        $service_type = get_post_meta($this->order->get_id(), '_wfs_service_type', true);
        return $service_type == 'delivery' ? true : parent::prevent_order_sync();
    }

    function get_signature(): array {
        global $shipday_plugin_version;
        return array(
            'orderSource' => 'woocommerce',
            'signature' => array(
                'version' => $shipday_plugin_version,
                'wooVersion' => WC()->version,
                'type' => 'single-vendor',
                'plugin' => 'food-store',
                'url' => get_site_url()
            )
        );
    }

}