<?php

require_once dirname(__DIR__) . '/functions/common.php';
require_once dirname(__DIR__) . '/date-modifiers/order_delivery_date.php';
require_once dirname(__FILE__) . '/Core_Payload.php';


class FoodStore_Payload extends _CorePayload
{
    protected $order, $message;

    function __construct($order_id)
    {
        shipday_logger('info', 'Constructing FoodStore order from order id '.$order_id);
        $this->order = wc_get_order($order_id);
    }

    public function getPayload(): array
    {
        $basePayload = null;
        try {
            $basePayload = parent::getBasicPayload();
        } catch (Exception $e) {
            $this->message = $e->getMessage();
            shipday_logger('error', ': Woo construct order failed. '.$e->getMessage());
        }
        return [
            'api_key'           => get_shipday_api_key(),
            'order'             => $basePayload,
            'restaurant'        => self::get_restaurant_info(),
            'signature'         => $this->get_signature(),
            'is_pickup_active'  => get_shipday_pickup_enabled(),
            'prevent_order_sync' => parent::prevent_order_sync(),
            'wfs_service_type' => $this->get_wfs_service_type(),
            'message'           => $this->message,
        ];
    }

    public function get_wfs_service_type() {
        return get_post_meta($this->order->get_id(), 'wfs_service_type', true);
    }

    public function prevent_order_sync(): bool
    {
        $service_type = $this->get_wfs_service_type();
        return $service_type != 'delivery' || parent::prevent_order_sync();
    }

    public static function get_restaurant_info(): array
    {
        return [
            'store_name' => get_bloginfo('name'),
            'phone'     => get_option('woocommerce_store_phone', '+10000000000'),
            'address'   => self::get_restaurant_address(),
        ];
    }
    public static function get_restaurant_address() {
        $country_state = shipday_handle_null(get_option('woocommerce_default_country'));
        $split_country = explode(":", $country_state);
        $country_code = $split_country[0] ?? '';
        $state_code = $split_country[1] ?? '';
        $state = self::to_state_name($state_code, $country_code);
        $country = self::to_country_name($country_code);

        return [
            'street_1'  => get_option('woocommerce_store_address'),
            'city'      => get_option('woocommerce_store_city'),
            'zip' => get_option('woocommerce_store_postcode'),
            'state'     => $state,
            'country'   => $country,
        ];
    }

    function get_signature(): array {
        $data = parent::get_signature();
        $data['type'] = 'single-vendor';
        $data['plugin'] = 'food-store';
        return $data;
    }

}
