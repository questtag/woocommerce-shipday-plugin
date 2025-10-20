<?php

require_once dirname(__DIR__) . '/functions/common.php';
require_once dirname(__DIR__) . '/date-modifiers/order_delivery_date.php';
require_once dirname(__FILE__) . '/Core_Payload.php';


class WCFM_Payload extends _CorePayload
{
    protected $order, $store_shipping, $message;

    function __construct($order_id)
    {
        shipday_logger('INFO', 'Constructing WCFM payload from order id '.$order_id);
        try {
            $this->order = wc_get_order($order_id);
        } catch (Exception $e) {
            $this->message = $e->getMessage();
            shipday_logger('error', ': WCFM construct order failed. '.$e->getMessage());
        }
        try {
            $this->store_shipping = (new WCFMmp_Shipping())->get_order_vendor_shipping($this->order);
        } catch (Exception $e) {
            $this->message = $this->message.' WCFMmp_Shipping: '. $e->getMessage();
            shipday_logger('error', ': WCFM construct get_order_vendor_shipping failed. '.$e->getMessage());
        }
    }

    public function getPayload(): array
    {
        $basicPayload = parent::getBasicPayload();
        shipday_logger('INFO', 'WCFM construct basic payload success:'.json_encode($basicPayload) );
        $vendors = $this->get_vendor_list($basicPayload['line_items']);
        shipday_logger('INFO', 'vendors:'.json_encode($vendors) );
        return [

            'order'             => $basicPayload,
            'signature'         => $this->get_signature(),
            'vendors'           => $vendors,
            'store_shipping'    => $this->store_shipping,
            'is_pickup_active'  => get_shipday_pickup_enabled(),
            'prevent_order_sync' => $this->prevent_order_sync(),
            'message'           => $this->message,
        ];
    }
    private function get_shipping_cost($vendor_id)
    {
        try {
            return $this->store_shipping[$vendor_id]['shipping'];
        } catch (Exception $e) {}
        return "0";
    }

    private function get_vendor_list(&$items): array {
        $stores = array();
        foreach ($items as &$item){
            $product_id = $item['product_id'];
            $store_id = wcfm_get_vendor_id_by_post($product_id);
            $item['vendor_id'] = $store_id;
            if (!array_key_exists($store_id, $stores)) {
                $store = $this->get_vendor_info($store_id);
                $store['vendor_id'] = $store_id;
                $store['shipping_cost'] = $this->get_shipping_cost($store_id);
                $store['api_key'] = $this->get_wcfm_api_key($store_id);
                $stores[$store_id] = $store;
            }
        }
        unset($item); // Unset the reference to avoid accidental modifications
        return array_values($stores);
    }

    function get_vendor_info($store_id) : array {
        $store_user    = wcfmmp_get_store( $store_id );

        if ($this->is_admin_store($store_user)) {
            shipday_logger('INFO', 'is admin store: '.$store_id);
            return _CorePayload::get_restaurant_info();
        }
        shipday_logger('INFO', 'vendor store address : '.json_encode($store_user->get_address()));
        return [
            'store_name' => $store_user->get_shop_name(),
            'phone' => $store_user->get_phone(),
            'address' => $this->get_vendor_address($store_user),
        ];

    }

    function  get_vendor_phone($store_user)
    {
        $store_phone = '';
        try {

            if (isset($vendor_data['phone']) && !empty($vendor_data['phone'])) {
                $store_phone = $vendor_data['phone'];
            } else {
                // Try to get from store user
                $store_user = wcfmmp_get_store($store_id);
                $store_phone = $store_user->get_phone();
            }

            if (empty($store_phone)) {
                $store_phone = '+1-000-000-0000'; // Default placeholder
            }
        } catch (Exception $e) {}
        return $store_phone;

    }

    function get_vendor_address($store_user): array
    {
        $address = $store_user->get_address();
        return [
            'street_1' => $address['street_1'],
            'street_2' => $address['street_2'],
            'city'     => $address['city'],
            'zip'      => $address['zip'],
            'state'    => $address['state'],
            'country'  => $address['country'],
        ];
    }

    function is_admin_store($vendor_shop) {
        $store_name = $vendor_shop->get_shop_name();
        if ($vendor_shop->get_id() == 0 && empty($store_name) ) {
            return true;
        }
        return false;
    }

    function get_wcfm_api_key($store_id) {
        if (get_shipday_order_manager() == 'admin_manage' || $this->is_admin_store($store_id)) return get_shipday_api_key();
        $vendor_data = get_user_meta( $store_id, 'wcfmmp_profile_settings', true );
        return shipday_handle_null($vendor_data['shipday']['api_key']);
    }


    function get_signature(): array
    {
        $data = parent::get_signature();
        $data['type'] = 'multi-vendor';
        $data['plugin'] = 'WCFM';
        $data['managed_by'] = get_shipday_order_manager();
        if (defined('WCFM_VERSION')) {
            $data['wcfm_version'] = WCFM_VERSION;
        }
        if (defined('WCFMmp_VERSION')) {
            $data['wcfmmp_version'] = WCFMmp_VERSION;
        }

        return $data;
    }

}
