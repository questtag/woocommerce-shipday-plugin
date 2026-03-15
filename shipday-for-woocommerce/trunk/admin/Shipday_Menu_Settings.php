<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
require_once dirname(__FILE__). '/../rest_api/WooCommerce_REST_API.php';

class Shipday_Menu_Settings {
    private static $allowed_order_managers = array( 'admin_manage', 'vendor_manage' );
    private static $allowed_week_days = array( '0', '1', '2', '3', '4', '5', '6' );
    private static $allowed_slot_durations = array( '10', '15', '30', '45', '60', '90', '120', '150', '180', '240', '300', '360' );

    public static function initialize() {
        add_action( 'admin_enqueue_scripts',[ __CLASS__, 'enqueue_styles' ] );
        add_action( 'admin_enqueue_scripts',[ __CLASS__, 'enqueue_scripts' ], 9999999 );

        //add_action( 'admin_init',[ __CLASS__, 'add_woo_delivery_type' ] );


        add_action( 'admin_menu', [ __CLASS__, 'add_menu_section' ] );
        add_filter( 'plugin_action_links_' . plugin_basename( WC_SHIPDAY_FILE ) , [ __CLASS__, 'add_settings_link_in_plugin_list' ] );

        add_action( 'wp_ajax_shipday_general_settings_save', [ __CLASS__, 'save_general_settings' ] );
        add_action( 'wp_ajax_shipday_connect_settings_save', [ __CLASS__, 'save_connect_settings' ] );
        add_action( 'wp_ajax_shipday_rest_api_settings_save', [ __CLASS__, 'save_rest_api_settings' ] );
        add_action( 'wp_ajax_shipday_delivery_settings_save', [ __CLASS__, 'save_delivery_settings' ] );
        add_action( 'wp_ajax_shipday_pickup_settings_save', [ __CLASS__, 'save_pickup_settings' ] );

    }

    public static function enqueue_styles() {

        wp_enqueue_style( 'select2mincss', plugin_dir_url( __FILE__ ) . 'css/select2.min.css', array(), "2.0.0", 'all' );
        wp_enqueue_style( "flatpickr_css",  plugin_dir_url( __FILE__ ) . '../shipday-datetime/public/css/flatpickr.min.css', array(), "2.0.0", 'all' );
        wp_enqueue_style( "shipday_admin_menu_css", plugin_dir_url( __FILE__ ) . 'css/shipday_admin_menu.css', array(), "2.5.66", 'all' );

    }

    public static function enqueue_scripts() {

        wp_enqueue_script( 'jquery-effects-slide' );
        wp_enqueue_code_editor( array( 'type' => 'text/css' ) );
        wp_enqueue_script( "flatpickr_js",  plugin_dir_url( __FILE__ ) . 'public/js/flatpickr.min.js', [], "2.0.0", true );
        wp_enqueue_script( "shipday_admin_menu_js", plugin_dir_url( __FILE__ ) . 'js/shipday_admin_menu.js', array( 'jquery', 'selectWoo', 'flatpickr_js' ), "2.0.51", 'all' );
        $shipday_nonce = wp_create_nonce('shipday_nonce');
        wp_localize_script("shipday_admin_menu_js", 'shipday_ajax_obj', array(
            'shipday_ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => $shipday_nonce,
        ));

    }

    public static function add_menu_section() {

        add_menu_page(
            __('Shipday', 'shipday-for-woocommerce'),
            __('Shipday', 'shipday-for-woocommerce'),
            'manage_options',
            'shipday-delivery-settings',
            [ __CLASS__, 'shipday_admin_main_layout' ],
            "dashicons-cart",
            null
        );

    }

    public static function add_settings_link_in_plugin_list( $links ) {
        $links[] = '<a href="admin.php?page=shipday-delivery-settings">Settings</a>';
        return $links;

        //return array_merge( $plugin_links, $links );
    }

    public static function shipday_admin_main_layout() {
        include_once SHIPDAY_PLUGIN_DIR . '/admin/partials/shipday-admin-new.php';
    }

    private static function ensure_settings_access() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }
    }

    private static function get_form_data_from_request() {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified in each AJAX handler before this helper runs.
        if ( ! isset( $_POST['formData'] ) || ! is_string( $_POST['formData'] ) ) {
            wp_send_json_error( array( 'message' => 'Invalid form data.' ), 400 );
        }

        $form_data = array();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Serialized form payload is unslashed here and each parsed field is sanitized individually before use.
        parse_str( wp_unslash( $_POST['formData'] ), $form_data );

        return is_array( $form_data ) ? $form_data : array();
    }

    private static function sanitize_yes_no_flag( $is_enabled ) {
        return $is_enabled ? 'yes' : 'no';
    }

    private static function sanitize_allowed_value( $value, $allowed_values, $default ) {
        $value = sanitize_text_field( (string) $value );

        return in_array( $value, $allowed_values, true ) ? $value : $default;
    }

    private static function sanitize_day_list( $days ) {
        if ( ! is_array( $days ) ) {
            return array();
        }

        $sanitized_days = array();
        foreach ( $days as $day ) {
            $day = sanitize_text_field( (string) $day );
            if ( in_array( $day, self::$allowed_week_days, true ) ) {
                $sanitized_days[] = $day;
            }
        }

        return array_values( array_unique( $sanitized_days ) );
    }

    private static function sanitize_positive_int( $value, $default ) {
        $value = absint( $value );

        return $value > 0 ? $value : $default;
    }

    private static function sanitize_time_slot( $hour, $minute, $ampm ) {
        $hour = max( 1, min( 12, absint( $hour ) ) );
        $minute = max( 0, min( 59, absint( $minute ) ) );
        $ampm = self::sanitize_allowed_value( $ampm, array( 'AM', 'PM' ), 'AM' );

        return array(
            'hh'   => str_pad( (string) $hour, 2, '0', STR_PAD_LEFT ),
            'mm'   => str_pad( (string) $minute, 2, '0', STR_PAD_LEFT ),
            'ampm' => $ampm,
        );
    }

    public static function save_connect_settings() {
        check_ajax_referer('shipday_nonce');
        self::ensure_settings_access();

        $form_data = self::get_form_data_from_request();
        $api_key = isset( $form_data['shipday_api_key'] ) ? sanitize_text_field( $form_data['shipday_api_key'] ) : '';
        $enable_pickup = self::sanitize_yes_no_flag( isset( $form_data['wc_settings_tab_shipday_enable_pickup'] ) );
        $enable_prev_order_sync = self::sanitize_yes_no_flag( isset( $form_data['wc_settings_tab_shipday_sync'] ) );
        $order_manage = isset( $form_data['wc_settings_tab_shipday_order_manage'] )
            ? self::sanitize_allowed_value( $form_data['wc_settings_tab_shipday_order_manage'], self::$allowed_order_managers, 'admin_manage' )
            : 'admin_manage';

        update_option('wc_settings_tab_shipday_enable_pickup', $enable_pickup);
        update_option('wc_settings_tab_shipday_sync', $enable_prev_order_sync);
        update_option('wc_settings_tab_shipday_order_manage', $order_manage);
        update_option('wc_settings_tab_shipday_api_key', $api_key);

        wp_send_json_success();

    }

    public static function save_rest_api_settings() {
        check_ajax_referer('shipday_nonce');
        self::ensure_settings_access();

        $form_data = self::get_form_data_from_request();
        $consumer_key = isset( $form_data['shipday_consumer_key'] ) ? sanitize_text_field( $form_data['shipday_consumer_key'] ) : '';
        $consumer_secret = isset( $form_data['shipday_consumer_secret'] ) ? sanitize_text_field( $form_data['shipday_consumer_secret'] ) : '';

        update_option('wc_settings_tab_shipday_rest_api_consumer_key', $consumer_key);
        update_option('wc_settings_tab_shipday_rest_api_consumer_secret', $consumer_secret);

        WooCommerce_REST_API::register_in_server();

        wp_send_json_success();

    }


    public static function save_general_settings() {
        check_ajax_referer('shipday_nonce');
        self::ensure_settings_access();

        $form_data = self::get_form_data_from_request();
        $enable_datetime = self::sanitize_yes_no_flag( isset( $form_data['shipday_enable_datetime_plugin'] ) );
        $enable_order_type = self::sanitize_yes_no_flag( isset( $form_data['shipday_enable_delivery_option'] ) );

        update_option('shipday_enable_datetime_plugin', $enable_datetime);
        update_option('shipday_enable_delivery_option', $enable_order_type);
        if ( isset( $form_data['shipday_delivery_pickup_label'] ) ) {
            update_option( 'shipday_delivery_pickup_label', sanitize_text_field( $form_data['shipday_delivery_pickup_label'] ) );
        }

        if($enable_datetime === "no"){
            update_option('shipday_enable_delivery_date', "no");
            update_option('shipday_delivery_date_mandatory', "no");
            update_option('shipday_avaialble_delivery_days',  []);
            update_option('shipday_enable_delivery_time', "no");
            update_option('shipday_delivery_time_mandatory', "no");

            update_option('shipday_enable_pickup_date', "no");
            update_option('shipday_pickup_date_mandatory', "no");
            update_option('shipday_avaialble_pickup_days',  []);
            update_option('shipday_enable_pickup_time', "no");
            update_option('shipday_pickup_time_mandatory', "no");
            update_option('shipday_enable_delivery_option', "no");
        }

        wp_send_json_success();

    }

    public static function save_delivery_settings() {
        check_ajax_referer('shipday_nonce');
        self::ensure_settings_access();

        $form_data = self::get_form_data_from_request();

        $enable_delivery_date = self::sanitize_yes_no_flag( isset( $form_data['shipday_enable_delivery_date'] ) );
        $delivery_date_mandatory = self::sanitize_yes_no_flag( isset( $form_data['shipday_delivery_date_mandatory'] ) );
        $available_days_ = isset( $form_data['shipday_avaialble_delivery_days'] )
            ? self::sanitize_day_list( $form_data['shipday_avaialble_delivery_days'] )
            : array();
        $start_delivery_slot = self::sanitize_time_slot(
            $form_data['shipday_delivery_time_slot_start_hh'] ?? 9,
            $form_data['shipday_delivery_time_slot_start_mm'] ?? 0,
            $form_data['shipday_delivery_time_slot_start_ampm'] ?? 'AM'
        );
        $enable_delivery_time = self::sanitize_yes_no_flag( isset( $form_data['shipday_enable_delivery_time'] ) );
        $delivery_time_mandatory = self::sanitize_yes_no_flag( isset( $form_data['shipday_delivery_time_mandatory'] ) );
        $end_delivery_slot = self::sanitize_time_slot(
            $form_data['shipday_delivery_time_slot_end_hh'] ?? 9,
            $form_data['shipday_delivery_time_slot_end_mm'] ?? 0,
            $form_data['shipday_delivery_time_slot_end_ampm'] ?? 'AM'
        );
        $selectable_delivery_days = self::sanitize_positive_int( $form_data['shipday_selectable_delivery_days'] ?? 30, 30 );
        $delivery_slot_duration = self::sanitize_allowed_value(
            $form_data['shipday_delivery_time_slot_duration'] ?? '60',
            self::$allowed_slot_durations,
            '60'
        );

        update_option('shipday_enable_delivery_date', $enable_delivery_date);
        update_option('shipday_delivery_date_mandatory', $delivery_date_mandatory);
        update_option('shipday_avaialble_delivery_days',  $available_days_);
        update_option('shipday_selectable_delivery_days', $selectable_delivery_days);

        update_option('shipday_enable_delivery_time', $enable_delivery_time);
        update_option('shipday_delivery_time_mandatory', $delivery_time_mandatory);


        update_option('shipday_delivery_time_slot_start',  $start_delivery_slot);
        update_option('shipday_delivery_time_slot_end',  $end_delivery_slot);
        update_option('shipday_delivery_time_slot_duration',  $delivery_slot_duration);

        wp_send_json_success();

    }

    public static function save_pickup_settings() {
        check_ajax_referer('shipday_nonce');
        self::ensure_settings_access();

        $form_data = self::get_form_data_from_request();

        $enable_pickup_date = self::sanitize_yes_no_flag( isset( $form_data['shipday_enable_pickup_date'] ) );
        $pickup_date_mandatory = self::sanitize_yes_no_flag( isset( $form_data['shipday_pickup_date_mandatory'] ) );
        $available_days_ = isset( $form_data['shipday_avaialble_pickup_days'] )
            ? self::sanitize_day_list( $form_data['shipday_avaialble_pickup_days'] )
            : array();
        $start_pickup_slot = self::sanitize_time_slot(
            $form_data['shipday_pickup_time_slot_start_hh'] ?? 9,
            $form_data['shipday_pickup_time_slot_start_mm'] ?? 0,
            $form_data['shipday_pickup_time_slot_start_ampm'] ?? 'AM'
        );
        $enable_pickup_time = self::sanitize_yes_no_flag( isset( $form_data['shipday_enable_pickup_time'] ) );
        $pickup_time_mandatory = self::sanitize_yes_no_flag( isset( $form_data['shipday_pickup_time_mandatory'] ) );
        $end_pickup_slot = self::sanitize_time_slot(
            $form_data['shipday_pickup_time_slot_end_hh'] ?? 9,
            $form_data['shipday_pickup_time_slot_end_mm'] ?? 0,
            $form_data['shipday_pickup_time_slot_end_ampm'] ?? 'AM'
        );
        $selectable_pickup_days = self::sanitize_positive_int( $form_data['shipday_selectable_pickup_days'] ?? 30, 30 );
        $pickup_slot_duration = self::sanitize_allowed_value(
            $form_data['shipday_pickup_time_slot_duration'] ?? '60',
            self::$allowed_slot_durations,
            '60'
        );

        update_option('shipday_enable_pickup_date', $enable_pickup_date);
        update_option('shipday_pickup_date_mandatory', $pickup_date_mandatory);
        update_option('shipday_avaialble_pickup_days',  $available_days_);
        update_option('shipday_selectable_pickup_days', $selectable_pickup_days);

        update_option('shipday_enable_pickup_time', $enable_pickup_time);
        update_option('shipday_pickup_time_mandatory', $pickup_time_mandatory);
        update_option('shipday_pickup_time_slot_start',  $start_pickup_slot);
        update_option('shipday_pickup_time_slot_end',  $end_pickup_slot);
        update_option('shipday_pickup_time_slot_duration',  $pickup_slot_duration);

        wp_send_json_success();

    }
}
