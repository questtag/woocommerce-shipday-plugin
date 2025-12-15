<?php

class Shipday_Woo_Delivery_Block_Storage {
    protected static $instance = null;

    private function __clone() {}
    public function __wakeup() {
        throw new \Exception( "Cannot unserialize." );
    }

    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'woocommerce_store_api_checkout_update_order_from_request', [$this, 'update_block_order_meta'], 10, 2 );
    }

    function update_block_order_meta( $order, $request ) {
        $settings = Shipday_Woo_DateTime_Util::get_default_settings();
        $extensions = $request->get_param( 'extensions' );
        $data = $extensions['shipday-woo-delivery'] ?? [];
        $order_id = $order->get_id();
        $hpos = false;
        if ( class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) ) {
            $hpos = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
        }

        $errors = new \WP_Error();

        self::validation( $data, $settings, $errors );

        if ( $errors->has_errors() ) {
            $error_messages = $errors->get_error_messages();
            $combined_error_message = implode( "<br>", $error_messages );
            throw new \WC_Data_Exception( 'SHIPDAY_WOO_ERROR', $combined_error_message );
        }

        if ( !empty( $data['shipday_order_type'] ) ) {
            if ( $hpos ) {
                $order->update_meta_data( 'delivery_type', sanitize_text_field( $data['shipday_order_type'] ) );
            } else {
                update_post_meta( $order_id, 'delivery_type', sanitize_text_field( $data['shipday_order_type'] ) );
            }
        }

        if ( $settings['enable_delivery_date'] && ( !$settings['enable_delivery_option'] || $data['shipday_order_type'] === 'Delivery' ) && !empty( $data['shipday_delivery_date'] ) ) {
            if ( $hpos ) {
                $order->update_meta_data( '_shipday_delivery_date', sanitize_text_field( $data['shipday_delivery_date'] ) );
            } else {
                update_post_meta( $order_id, '_shipday_delivery_date', sanitize_text_field( $data['shipday_delivery_date'] ) );
            }
        }

        if ( $settings['enable_delivery_time'] && ( !$settings['enable_delivery_option'] || $data['shipday_order_type'] === 'Delivery' ) && !empty( $data['shipday_delivery_time'] ) ) {
            if ( $hpos ) {
                $order->update_meta_data( '_shipday_delivery_time', sanitize_text_field( $data['shipday_delivery_time'] ) );
            } else {
                update_post_meta( $order_id, '_shipday_delivery_time', sanitize_text_field( $data['shipday_delivery_time'] ) );
            }
        }

        if ( $settings['enable_pickup_date'] &&  $data['shipday_order_type'] === 'Pickup' && !empty( $data['shipday_pickup_date'] ) ) {
            if ( $hpos ) {
                $order->update_meta_data( '_shipday_delivery_date', sanitize_text_field( $data['shipday_pickup_date'] ) );
            } else {
                update_post_meta( $order_id, '_shipday_delivery_date', sanitize_text_field( $data['shipday_pickup_date'] ) );
            }
        }

        if ( $settings['enable_pickup_time'] && $data['shipday_order_type'] === 'Pickup' && !empty( $data['pickup_time'] ) ) {
            if ( $hpos ) {
                $order->update_meta_data( '_shipday_delivery_time', sanitize_text_field( $data['pickup_time'] ) );
            } else {
                update_post_meta( $order_id, '_shipday_delivery_time', sanitize_text_field( $data['pickup_time'] ) );
            }
        }

        $order->save();

        self::reset_session();
    }

    static function validation( $data, $settings, $errors ) {

        if ( $settings['enable_datetime_plugin'] && $settings['enable_delivery_option'] && empty( $data['shipday_order_type'] ) ) {
            $errors->add( 'error', $settings['checkout_delivery_option_notice'] );
        }

        if ( $settings['enable_datetime_plugin'] && $settings['enable_delivery_date'] && $settings['delivery_date_mandatory'] && ( !$settings['enable_delivery_option'] || $data['shipday_order_type'] === 'Delivery' ) && empty( $data['shipday_delivery_date'] ) ) {
            $errors->add( 'error', $settings['checkout_date_notice'] );
        }

        if ( $settings['enable_datetime_plugin'] && $settings['enable_delivery_time'] && $settings['delivery_time_mandatory'] && ( !$settings['enable_delivery_option'] || $data['shipday_order_type'] === 'Delivery' ) && empty( $data['shipday_delivery_time'] ) ) {
            $errors->add( 'error', $settings['checkout_time_notice'] );
        }

        if ( $settings['enable_datetime_plugin'] && $settings['enable_pickup_date'] && $settings['pickup_date_mandatory'] &&  $data['shipday_order_type'] === 'Pickup' && empty( $data['shipday_pickup_date'] ) ) {
            $errors->add( 'error', $settings['checkout_date_notice'] );
        }

    }

    static function reset_session() {
        WC()->session->__unset( 'on_change' );
        WC()->session->__unset( 'shipday_order_type' );
        WC()->session->__unset( 'shipday_delivery_date' );
        WC()->session->__unset( 'shipday_delivery_time' );
        WC()->session->__unset( 'shipday_pickup_date' );
        WC()->session->__unset( 'pickup_time' );
    }

}
