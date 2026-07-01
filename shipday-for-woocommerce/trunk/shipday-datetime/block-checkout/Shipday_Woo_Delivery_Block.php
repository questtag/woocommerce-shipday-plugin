<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema;

class Shipday_Woo_Delivery_Block {
    protected static $instance = null;
    static $IDENTIFIER = 'shipday_woo_delivery';
    static $BLOCK_NAME = 'shipday-for-woocommerce/delivery-block';
    static $EDITOR_SCRIPT_HANDLE = 'shipday-woo-delivery-block-editor';
    static $FRONTEND_SCRIPT_HANDLE = 'shipday-woo-delivery-block';
    static $EDITOR_STYLE_HANDLE = 'shipday-woo-delivery-block-editor';
    static $FRONTEND_STYLE_HANDLE = 'shipday-woo-delivery-block-style';

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
    }

    function reset_session() {
        WC()->session->set( 'on_change', false );
        WC()->session->set( 'shipday_order_type', NULL );
        WC()->session->set( 'shipday_delivery_date', NULL );
        WC()->session->set( 'shipday_delivery_time', NULL );
    }

    function register_woo_delivery_block() {
        $asset_base_url = plugin_dir_url( __FILE__ ) . 'assets/';

        wp_register_script(
            self::$EDITOR_SCRIPT_HANDLE,
            $asset_base_url . 'js/editor.js',
            array( 'wp-blocks', 'wp-element', 'wp-i18n' ),
            '2.3.1',
            true
        );

        wp_register_style(
            self::$EDITOR_STYLE_HANDLE,
            $asset_base_url . 'css/editor.css',
            array(),
            '2.3.1'
        );

        wp_register_style(
            self::$FRONTEND_STYLE_HANDLE,
            $asset_base_url . 'css/frontend.css',
            array(),
            '2.3.1'
        );

        $script_data = array(
            'blockFieldPosition' => self::get_block_field_position(),
        );

        wp_localize_script( self::$EDITOR_SCRIPT_HANDLE, 'shipdayWooDeliveryBlockData', $script_data );

        register_block_type(
            self::$BLOCK_NAME,
            array(
                'api_version'   => 3,
                'editor_script' => self::$EDITOR_SCRIPT_HANDLE,
                'editor_style'  => self::$EDITOR_STYLE_HANDLE,
                'style'         => self::$FRONTEND_STYLE_HANDLE,
                'parent'        => array( self::get_block_field_position() ),
                'attributes'    => array(
                    'lock' => array(
                        'type'    => 'object',
                        'default' => array(
                            'remove' => true,
                            'move'   => true,
                        ),
                    ),
                ),
                'render_callback' => '__return_empty_string',
            )
        );
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
                'description' => __( 'Type of order', 'shipday-for-woocommerce' ),
                'enum'        => array_merge( array_keys( $settings['delivery_options'] ), ["", null] ),
            ),
            'shipday_delivery_date' => array(
                'type'        => ['string', 'null'],
                'description' => __( 'Delivery Date', 'shipday-for-woocommerce' ),
            ),
            'shipday_delivery_time' => array(
                'type'        => ['string', 'null'],
                'description' => __( 'Delivery Time', 'shipday-for-woocommerce' ),
            ),
            'shipday_pickup_date' => array(
                'type'        => ['string', 'null'],
                'description' => __( 'Pickup Date', 'shipday-for-woocommerce' ),
            ),
            'pickup_time' => array(
                'type'        => ['string', 'null'],
                'description' => __( 'Pickup Time', 'shipday-for-woocommerce' ),
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

    private static function get_store_timezone() {
        return function_exists( 'wc_timezone' ) ? wc_timezone() : wp_timezone();
    }

    private static function normalize_list( $value ) {
        if ( ! is_array( $value ) ) {
            return [];
        }

        return array_map( 'strval', $value );
    }


    static function validate_and_set_date( $settings, $type, $selected ) {
        if ( $type === 'Delivery' ) {
            $disable_week_days = self::normalize_list( $settings['delivery_disable_week_days'] ?? [] );
            $disable_dates = array_merge(
                self::normalize_list( $settings['disable_dates'] ?? [] ),
                self::normalize_list( $settings['disable_delivery_date_passed_time'] ?? [] )
            );
            $enable_date = ! empty( $settings['enable_delivery_date'] );
            $auto_select_first_date = ! empty( $settings['auto_select_first_date'] );
            $selectable_days = isset( $settings['delivery_date_selectable_days'] ) ? absint( $settings['delivery_date_selectable_days'] ) : 30;
            $session_name = 'shipday_delivery_date';
        } else {
            $disable_week_days = self::normalize_list( $settings['pickup_disable_week_days'] ?? [] );
            $disable_dates = array_merge(
                self::normalize_list( $settings['pickup_disable_dates'] ?? [] ),
                self::normalize_list( $settings['disable_pickup_date_passed_time'] ?? [] )
            );
            $enable_date = ! empty( $settings['enable_pickup_date'] );
            $auto_select_first_date = ! empty( $settings['pickup_auto_select_first_date'] );
            $selectable_days = isset( $settings['pickup_date_selectable_days'] ) ? absint( $settings['pickup_date_selectable_days'] ) : 30;
            $session_name = 'shipday_pickup_date';
        }

        $selectable_days = max( 1, $selectable_days );
        $tz = self::get_store_timezone();
        $today = ! empty( $settings['today'] ) ? (string) $settings['today'] : wp_date( 'Y-m-d', current_time( 'timestamp', true ) );
        $current = \DateTimeImmutable::createFromFormat( 'Y-m-d', $today, $tz );

        if ( ! $current instanceof \DateTimeImmutable ) {
            $current = new \DateTimeImmutable( 'today', $tz );
        }

        $last_selectable_date = $current->modify( '+' . ( $selectable_days - 1 ) . ' day' );

        if ( !empty( $selected ) ) {
            $selected_date = \DateTimeImmutable::createFromFormat( 'Y-m-d', (string) $selected, $tz );
            if (
                $selected_date instanceof \DateTimeImmutable
                && $selected_date >= $current
                && $selected_date <= $last_selectable_date
                && ! in_array( $selected_date->format( 'w' ), $disable_week_days, true )
                && ! in_array( $selected_date->format( 'Y-m-d' ), $disable_dates, true )
            ) {
                return $selected_date->format( "Y-m-d" );
            }
        }

        $on_change = WC()->session->get( "on_change", false );

        if ( $enable_date && $auto_select_first_date && !$on_change ) {
            for ( $day_offset = 0; $day_offset < $selectable_days; $day_offset++ ) {
                $candidate = $current->modify( '+' . $day_offset . ' day' );

                if (
                    ! in_array( $candidate->format( 'w' ), $disable_week_days, true )
                    && ! in_array( $candidate->format( 'Y-m-d' ), $disable_dates, true )
                ) {
                    $formatted_date = $candidate->format( "Y-m-d" );
                    WC()->session->set( $session_name, $formatted_date );
                    return $formatted_date;
                }
            }
        }

        WC()->session->set( $session_name, NULL );
        return NULL;
    }

    static function reset_time_options( $name, $old_time_options, $selected_date = null ) {
        $time_options = [];

        // Resolve store timezone (WooCommerce) or WP as fallback
        $tz = self::get_store_timezone();
        $now = new \DateTimeImmutable('now', $tz);
        $todayYmd = $now->format('Y-m-d');

        // Normalize/resolve selected date to Y-m-d in store tz
        $is_today = false;
        if ( $selected_date ) {
            try {
                if ( $selected_date instanceof \DateTimeInterface ) {
                    $sel = new \DateTimeImmutable( $selected_date->format( 'Y-m-d' ), $tz );
                } else {
                    // allow strings like "2025-12-02" or "today" etc.
                    $sel = new \DateTimeImmutable( (string) $selected_date, $tz );
                }
                $is_today = $sel->format('Y-m-d') === $todayYmd;
            } catch ( \Exception $e ) {
                $is_today = false; // if parsing fails, treat as not today
            }
        }

        foreach ( is_array( $old_time_options ) ? $old_time_options : [] as $key => $value ) {
            $disabled = false;

            if ( $is_today ) {
                // Expect formats like "10:30 AM - 12:34 PM" (tolerate extra spaces or en-dash)
                $parts = preg_split('/\s*[-–]\s*/', (string)$key, 2); // hyphen or en dash
                if ( is_array($parts) && count($parts) === 2 ) {
                    $end_str  = trim($parts[1]); // "12:34 PM"
                    $parseFmt = 'Y-m-d h:i A';

                    $end_dt = \DateTimeImmutable::createFromFormat($parseFmt, $todayYmd . ' ' . $end_str, $tz);
                    if ( $end_dt instanceof \DateTimeInterface ) {
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
        wp_localize_script(
            self::$FRONTEND_SCRIPT_HANDLE,
            'shipdayWooDeliveryBlockData',
            array(
                'blockFieldPosition' => self::get_block_field_position(),
            )
        );
    }

    static function get_block_field_position() {
        return 'woocommerce/checkout-contact-information-block';
    }
}
