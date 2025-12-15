<?php

use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema;

class Shipday_Woo_Delivery_Block {
    protected static $instance = null;
    static $IDENTIFIER = 'shipday_woo_delivery';

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
        add_action( 'init', [$this, 'register_woo_delivery_block'] );
        add_action( 'woocommerce_blocks_enqueue_checkout_block_scripts_before', [$this, 'reset_session'] );
        add_action( 'woocommerce_blocks_loaded', [$this, 'register_block'] );
        add_action( 'woocommerce_blocks_loaded', [$this, 'add_data'] );
        add_action( 'woocommerce_blocks_loaded', [$this, 'extension_data_declaration'] );
        add_action( 'woocommerce_blocks_loaded', [$this, 'order_type_change_callback'] );
        add_action( 'wp_footer', [$this, 'localize_settings'] );
    }

    function reset_session() {
        WC()->session->set( 'on_change', false );
        WC()->session->set( 'shipday_order_type', NULL );
        WC()->session->set( 'shipday_delivery_date', NULL );
        WC()->session->set( 'shipday_delivery_time', NULL );
    }

    function register_woo_delivery_block() {
        register_block_type( 'shipday-woo-delivery/delivery-block' );
    }


    function register_block() {
        require_once 'Shipday_Woo_Delivery_Block_Integration.php';
        add_action(
            'woocommerce_blocks_checkout_block_registration',
            function ( $integration_registry ) {
                $integration_registry->register( new Shipday_Woo_Delivery_Block_Integration() );
            }
        );
    }


    function add_data() {
        woocommerce_store_api_register_endpoint_data(
            array(
                'endpoint'      => CartSchema::IDENTIFIER,
                'namespace'     => self::$IDENTIFIER,
                'data_callback' => [__CLASS__, 'data'],
                'schema_type'   => 'ARRAY_A',
            )
        );
    }


    static function data() {
        $data = Shipday_Woo_DateTime_Util::get_default_settings();

        $data['shipday_order_type'] = WC()->session->get( 'shipday_order_type' );

        $data['shipday_delivery_date'] = self::validate_and_set_date( $data, 'Delivery', WC()->session->get( 'shipday_delivery_date' ) );
        $data['shipday_delivery_time'] = self::validate_and_set_time( $data, 'Delivery', WC()->session->get( 'shipday_delivery_time' ) );

        $data['shipday_pickup_date'] = self::validate_and_set_date( $data, 'Pickup', WC()->session->get( 'shipday_pickup_date' ) );
        $data['pickup_time'] = self::validate_and_set_time( $data, 'Pickup', WC()->session->get( 'pickup_time' ) );

        $data['delivery_time_options'] = self::reset_time_options('Delivery', $data['delivery_time_options'] ,  $data['shipday_delivery_date']);
        $data['pickup_time_options'] = self::reset_time_options('Pickup',  $data['pickup_time_options'] ,  $data['shipday_pickup_date']);

        return $data;
    }

    function extension_data_declaration() {
        woocommerce_store_api_register_endpoint_data(
            array(
                'endpoint'        => CheckoutSchema::IDENTIFIER,
                'namespace'       => 'shipday-woo-delivery',
                'schema_type'     => 'ARRAY_A',
                'schema_callback' => [__CLASS__, 'data_structure'],
            )
        );
    }

    static function data_structure() {
        $settings = Shipday_Woo_DateTime_Util::get_default_settings();
        return array(
            'shipday_order_type'    => array(
                'type'        => ['string', 'null'],
                'description' => __( 'Type of order', 'shipday-delivery' ),
                'enum'        => array_merge( array_keys( $settings['delivery_options'] ), ["", null] ),
            ),
            'shipday_delivery_date' => array(
                'type'        => ['string', 'null'],
                'description' => __( 'Delivery Date', 'shipday-delivery' ),
            ),
            'shipday_delivery_time' => array(
                'type'        => ['string', 'null'],
                'description' => __( 'Delivery Time', 'shipday-delivery' ),
            ),
            'shipday_pickup_date' => array(
                'type'        => ['string', 'null'],
                'description' => __( 'Pickup Date', 'shipday-delivery' ),
            ),
            'pickup_time' => array(
                'type'        => ['string', 'null'],
                'description' => __( 'Pickup Time', 'shipday-delivery' ),
            ),
        );
    }

    function order_type_change_callback() {
        woocommerce_store_api_register_update_callback( [
            'namespace' => self::$IDENTIFIER . '_order_type_change',
            'callback'  => [$this, 'order_type_change'],
        ] );
        woocommerce_store_api_register_update_callback( [
            'namespace' => self::$IDENTIFIER . '_delivery_date_change',
            'callback'  => [$this, 'delivery_date_change'],
        ] );
        woocommerce_store_api_register_update_callback( [
            'namespace' => self::$IDENTIFIER . '_delivery_time_change',
            'callback'  => [$this, 'delivery_time_change'],
        ] );

        woocommerce_store_api_register_update_callback( [
            'namespace' => self::$IDENTIFIER . '_pickup_date_change',
            'callback'  => [$this, 'pickup_date_change'],
        ] );

        woocommerce_store_api_register_update_callback( [
            'namespace' => self::$IDENTIFIER . '_pickup_time_change',
            'callback'  => [$this, 'pickup_time_change'],
        ] );
    }

    function order_type_change( $data ) {
        $order_type = sanitize_text_field( $data['shipday_order_type'] );
        WC()->session->set( 'on_change', false );
        WC()->session->set( "shipday_order_type", $order_type );
    }

    function delivery_date_change( $data ) {
        $delivery_date = sanitize_text_field( $data['shipday_delivery_date'] );
        WC()->session->set( 'on_change', true );
        WC()->session->set( "shipday_delivery_date", $delivery_date );
    }

    function pickup_date_change( $data ) {
        $pickup_date = sanitize_text_field( $data['shipday_pickup_date'] );
        WC()->session->set( 'on_change', true );
        WC()->session->set( "shipday_pickup_date", $pickup_date );
    }

    function delivery_time_change( $data ) {
        $delivery_time = sanitize_text_field( $data['shipday_delivery_time'] );
        WC()->session->set( 'on_change', true );
        WC()->session->set( "shipday_delivery_time", $delivery_time );
    }

    function pickup_time_change( $data ) {
        $delivery_time = sanitize_text_field( $data['pickup_time'] );
        WC()->session->set( 'on_change', true );
        WC()->session->set( "pickup_time", $delivery_time );
    }


    static function validate_and_set_date( $settings, $type, $selected ) {
        if ( $type === 'Delivery' ) {
            $disable_week_days = $settings['delivery_disable_week_days'];
            $disable_dates = array_merge( $settings['disable_dates'], $settings['disable_delivery_date_passed_time'] );
            $enable_date = $settings['enable_delivery_date'];
            $auto_select_first_date = $settings['auto_select_first_date'];
            $session_name = 'shipday_delivery_date';
        } else {
            $disable_week_days = $settings['pickup_disable_week_days'];
            $disable_dates = array_merge( $settings['pickup_disable_dates'], $settings['disable_pickup_date_passed_time'] );
            $enable_date = $settings['enable_pickup_date'];
            $auto_select_first_date = $settings['pickup_auto_select_first_date'];
            $session_name = 'shipday_pickup_date';
        }
        $current = \DateTime::createFromFormat( 'Y-m-d', $settings['today'] );

        if ( !empty( $selected ) ) {
            $selected_date = \DateTime::createFromFormat( 'Y-m-d', $selected );
            if ( $selected_date >= $current && !in_array( $selected_date->format( 'w' ), $disable_week_days )
                && !in_array( $selected_date->format( 'Y-m-d' ), $disable_dates ) ) {
                return $selected_date->format( "Y-m-d" );
            }
        }

        $on_change = WC()->session->get( "on_change", false );

        if ( $enable_date && $auto_select_first_date && !$on_change ) {
            while (
                in_array( $current->format( 'w' ), $disable_week_days )
                || in_array( $current->format( 'Y-m-d' ), $disable_dates )
            ) {
                $current->modify( "+1 day" );
            }
            $formatted_date = $current->format( "Y-m-d" );
            WC()->session->set( $session_name, $formatted_date );
            return $formatted_date;
        }

        WC()->session->set( $session_name, NULL );
        return NULL;
    }

    static function reset_time_options( $name, $old_time_options, $selected_date = null ) {
        $time_options = [];

        // Resolve store timezone (WooCommerce) or WP as fallback
        $tz = function_exists('wc_timezone') ? wc_timezone() : wp_timezone();
        $now = new DateTimeImmutable('now', $tz);
        $todayYmd = $now->format('Y-m-d');

        // Normalize/resolve selected date to Y-m-d in store tz
        $is_today = false;
        if ( $selected_date ) {
            try {
                if ($selected_date instanceof DateTimeInterface) {
                    $sel = (new DateTimeImmutable($selected_date->format('Y-m-d'), $tz));
                } else {
                    // allow strings like "2025-12-02" or "today" etc.
                    $sel = new DateTimeImmutable((string)$selected_date, $tz);
                }
                $is_today = $sel->format('Y-m-d') === $todayYmd;
            } catch (Exception $e) {
                $is_today = false; // if parsing fails, treat as not today
            }
        }

        foreach ( $old_time_options as $key => $value ) {
            $disabled = false;

            if ( $is_today ) {
                // Expect formats like "10:30 AM - 12:34 PM" (tolerate extra spaces or en-dash)
                $parts = preg_split('/\s*[-–]\s*/', (string)$key, 2); // hyphen or en dash
                if ( is_array($parts) && count($parts) === 2 ) {
                    $end_str  = trim($parts[1]); // "12:34 PM"
                    $parseFmt = 'Y-m-d h:i A';

                    $end_dt = DateTimeImmutable::createFromFormat($parseFmt, $todayYmd . ' ' . $end_str, $tz);
                    if ( $end_dt instanceof DateTimeInterface ) {
                        // Disable if end time is strictly earlier than now
                        if ( $end_dt < $now ) {
                            $disabled = true;
                        }
                    }
                }
            }

            $time_options[$key] = [
                'title'    => is_array($value) ? $value['title'] : $value,
                'disabled' => $disabled,
            ];
        }

        return $time_options;
    }



    static function validate_and_set_time( $settings, $type, $selected ) {

        $time_options = $type === 'Delivery' ? $settings["delivery_time_options"] : $settings["pickup_time_options"];
        $enable_time = $type === 'Delivery' ? $settings['enable_delivery_time'] : $settings['enable_pickup_time'];
        $auto_select_first_time = $type === 'Delivery' ? $settings['auto_select_first_time'] : $settings['pickup_auto_select_first_time'];
        $session_name = $type === 'Delivery' ? 'shipday_delivery_time' : 'pickup_time';

        if ( !empty( $selected ) ) {
            if ( isset( $time_options[$selected]['disabled'] ) && !$time_options[$selected]['disabled'] ) {
                return $selected;
            }
        }

        $on_change = WC()->session->get( "on_change", false );

        if ( $enable_time && $auto_select_first_time && !$on_change ) {
            foreach ( $time_options as $key => $data ) {
                if ( !$data['disabled'] ) {
                    WC()->session->set( $session_name, $key );
                    return $key;
                }
            }
        }

        WC()->session->set( $session_name, NULL );
        return NULL;
    }

    function localize_settings() {

        $shipday_block_field_position = "contact-information";
        if ( $shipday_block_field_position === 'contact-information' ) {
            $block_field_position = "woocommerce/checkout-contact-information-block";
        }
        wp_localize_script( 'shipday-woo-delivery-block', 'shipday_woo_delivery_localize_settings',
            array(
                'block_field_position' => "woocommerce/checkout-contact-information-block",
            )
        );
    }
}
