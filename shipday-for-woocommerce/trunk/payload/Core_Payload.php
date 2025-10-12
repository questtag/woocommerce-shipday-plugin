<?php

require_once dirname(__DIR__) . '/functions/common.php';

class _CorePayload
{
    protected $order;
    private $message;

    public static function to_state_name($state_code, $country_code)
    {
        return !empty($state_code) ? WC()->countries->get_states($country_code)[$state_code] : $state_code;
    }

    public static function to_country_name($country_code)
    {
        return !empty($country_code) ? (new WC_Countries())->get_countries()[$country_code] : $country_code;
    }

    function get_signature(): array
    {
        global $shipday_plugin_version;
        return [
            'shipday_version' => $shipday_plugin_version,
            'woo_version' => WC()->version,
            'timezone' => $this->get_woo_timezone(),
        ];
    }

    function getBasicPayload(): ?array {
        try {
            return [
                'id' => $this->order->get_id(),
                'parent_id' => $this->order->get_parent_id(),
                'number' => $this->order->get_order_number(),
                'order_key' => $this->order->get_order_key(),
                'version' => $this->order->get_version(),
                'status' => $this->order->get_status(),
                'currency' => $this->order->get_currency(),
                'date_created' => $this->order->get_date_created(),
                'date_modified' => $this->order->get_date_modified(),
                'discount_total' => $this->order->get_discount_total(),
                'discount_tax' => $this->order->get_discount_tax(),
                'shipping_total' => $this->order->get_shipping_total(),
                'shipping_tax' => $this->order->get_shipping_tax(),
                'shipping_methods' => $this->get_shipping_methods(),
                'total' => $this->order->get_total(),
                'total_tax' => $this->order->get_total_tax(),
                'customer_id' => $this->order->get_customer_id(),
                'customer_ip_address' => $this->order->get_customer_ip_address(),
                'customer_user_agent' => $this->order->get_customer_user_agent(),
                'customer_note' => $this->order->get_customer_note(),
                'billing' => $this->order->get_address('billing'),
                'shipping' => $this->order->get_address('shipping'),
                'payment_method' => $this->order->get_payment_method(),
                'payment_method_title' => $this->order->get_payment_method_title(),
                'transaction_id' => $this->order->get_transaction_id(),
                'date_paid' => $this->order->get_date_paid(),
                'date_completed' => $this->order->get_date_completed(),
                'meta_data' => $this->order->get_meta_data(),
                'line_items' => $this->get_order_items(),
                'time_zone' => $this->get_timezone(),
                'uuid'      => $this->get_uuid()
            ];
        } catch (Exception $e){
            $this->message = $e->getMessage();
            shipday_logger('INFO', ': Base order payload failed. '.$e->getMessage());
        }
        return null;
    }

    function get_order_items() : array
    {
        $items = $this->order->get_items();
        $result = [];
        foreach ($items as $item_id => $item) {
            $result[] = $item->get_data();
        }
        return $result;
    }

    function get_shipping_methods() : array
    {
        $shippingItems = $this->order->get_items('shipping');
        $result = [];
        foreach ( $shippingItems as $item ) {
            $result[] = $item->get_data();
        }
        return $result;
    }

    public static function get_restaurant_info(): array
    {
        return [
            'store_name' => get_bloginfo('name'),
            'phone'     => get_option('woocommerce_store_phone','+10000000000'),
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
            'zip'       => get_option('woocommerce_store_postcode'),
            'state'     => $state,
            'country'   => $country,
        ];
    }
    private static function get_timezone() {
        try {
            return get_shipday_datetime_timezone();
        } catch (Exception $e) {
            shipday_logger('error', ': get timezone failed. '.$e->getMessage());
        }
        return 'UTC';
    }

    private static function get_woo_timezone() {
        try {
            return wp_timezone_string();
        } catch (Exception $e) {
            shipday_logger('error', ': get woo timezone failed. '.$e->getMessage());
        }
        return '+00:10';
    }

    public function get_uuid() {
        try {
            return get_option('wc_settings_tab_shipday_registered_uuid');
        } catch (Exception $e) {}
        return null;
    }

    function prevent_order_sync() {
        if ($this->order == null) return true;
        $flag = get_post_meta($this->order->get_id(), '_shipday_order_sync_prevent', true);
        return $flag == "yes";
    }

}
