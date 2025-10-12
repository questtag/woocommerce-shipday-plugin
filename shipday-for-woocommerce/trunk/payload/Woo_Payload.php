<?php

require_once dirname(__DIR__) . '/functions/common.php';
require_once dirname(__DIR__) . '/date-modifiers/order_delivery_date.php';
require_once dirname(__FILE__) . '/Core_Payload.php';


class Woo_Payload extends _CorePayload
{
    protected $order, $message;

    function __construct($order_id)
    {
        shipday_logger('info', 'Constructing Woo order from order id '.$order_id);
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
            'api_key'       => get_shipday_api_key(),
            'order'         => $basePayload,
            'restaurant'    => parent::get_restaurant_info(),
            'signature'     => $this->get_signature(),
            'is_pickup_active'  => get_shipday_pickup_enabled(),
            'prevent_order_sync' => parent::prevent_order_sync(),
            'message'       => $this->message,
        ];
    }

    function get_signature(): array
    {
        $data = parent::get_signature();
        $data['type'] = 'single-vendor';
        $data['plugin'] = 'vanilla';
        $data['url'] = get_site_url();

        return $data;
    }



}
