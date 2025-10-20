<?php

require_once dirname(__DIR__) . '/functions/common.php';
require_once dirname(__DIR__) . '/date-modifiers/order_delivery_date.php';
require_once dirname(__FILE__) . '/Core_Payload.php';


class Dokan_Payload extends _CorePayload
{
    protected $order, $vendor_id, $message, $has_suborders;

    function __construct($order_id)
    {
        shipday_logger('info', 'Constructing Dokan order from order id '.$order_id);
        $this->order = wc_get_order($order_id);
        $has_suborders = $this->has_suborders($order_id);
        if(!$has_suborders)
            $this->vendor_id = dokan_get_seller_id_by_order( $this->order );
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
        $api_key = $this->get_dokan_api_key();
        shipday_logger('INFO', 'Vendor Id:'.$this->vendor_id. ', API Key:'.$api_key);
        return [
            'api_key'       => $api_key,
            'order'         => $basePayload,
            'signature'     => $this->get_signature(),
            'has_suborders' => $this->has_suborders($this->order->get_id()),
            'restaurant'        => self::get_vendor_info(),
            'is_pickup_active'  => get_shipday_pickup_enabled(),
            'prevent_order_sync' => $this->prevent_order_sync(),
            'message'       => $this->message,
        ];
    }

    function get_dokan_api_key() {
        shipday_logger('INFO', 'manage by: '.get_shipday_order_manager().' shipday_api_key: '.get_shipday_api_key());
        if (get_shipday_order_manager() == 'admin_manage') return get_shipday_api_key();
        $api_key            = get_user_meta( $this->vendor_id, 'shipday_api_key', true );
        return shipday_handle_null($api_key);
    }

    public function has_suborders($post): bool
    {
        return (bool) get_children(
            array(
                'post_parent' => $post,
                'post_type'   => 'shop_order',
            )
        );
    }

    public function prevent_order_sync(): bool|int
    {
        return $this->has_suborders | parent::prevent_order_sync();
    }

    public function get_vendor_info() {
        try {
            $vendor = new \WeDevs\Dokan\Vendor\Vendor($this->vendor_id);
            return [
                'store_name' => $vendor->get_shop_name(),
                'phone' => $vendor->get_phone(),
                'address' => $this->get_vendor_address($vendor),
            ];
        } catch (Exception $e) {
            $this->message = $e->getMessage();
            return null ;
        }
    }

    function get_vendor_address($vendor): array
    {
        $address = $vendor->get_address();
        return [
            'street_1' => $address['street_1'],
            'street_2' => $address['street_2'],
            'city'     => $address['city'],
            'zip'      => $address['zip'],
            'state'    => $address['state'],
            'country'  => $address['country'],
        ];
    }

    function get_signature(): array
    {
        $data = parent::get_signature();
        $data['type'] = 'multi-vendor';
        $data['plugin'] = 'Dokan';
        $data['managed_by'] = get_shipday_order_manager();
        $data['dokan_version'] = dokan()->version;

        return $data;
    }

}
