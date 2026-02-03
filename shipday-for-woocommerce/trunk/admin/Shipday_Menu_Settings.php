<?php
require_once dirname(__FILE__). '/../rest_api/WooCommerce_REST_API.php';

class Shipday_Menu_Settings {
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
            __('Shipday', 'shipday-delivery'),
            __('Shipday', 'shipday-delivery'),
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


    public static function save_connect_settings() {
        check_ajax_referer('shipday_nonce');

        parse_str( $_POST[ 'formData' ], $form_data );
        $api_key = sanitize_text_field($form_data['shipday_api_key']);
        $enable_pickup = !isset($form_data['wc_settings_tab_shipday_enable_pickup']) ? "no" : "yes";
        $enable_prev_order_sync = isset($form_data['wc_settings_tab_shipday_sync']) ? "yes" : "no";

        $order_manage = !isset($form_data['wc_settings_tab_shipday_order_manage']) ? "admin_manage" : $form_data['wc_settings_tab_shipday_order_manage'];

        update_option('wc_settings_tab_shipday_enable_pickup', $enable_pickup);
        update_option('wc_settings_tab_shipday_sync', $enable_prev_order_sync);
        update_option('wc_settings_tab_shipday_order_manage', $order_manage);
        update_option('wc_settings_tab_shipday_api_key', $api_key);

        wp_send_json_success();

    }

    public static function save_rest_api_settings() {
        check_ajax_referer('shipday_nonce');

        parse_str( $_POST[ 'formData' ], $form_data );
        $consumer_key = sanitize_text_field($form_data['shipday_consumer_key']);
        $consumer_secret = sanitize_text_field($form_data['shipday_consumer_secret']);

        update_option('wc_settings_tab_shipday_rest_api_consumer_key', $consumer_key);
        update_option('wc_settings_tab_shipday_rest_api_consumer_secret', $consumer_secret);

        WooCommerce_REST_API::register_in_server();

        wp_send_json_success();

    }


    public static function save_general_settings() {
        check_ajax_referer('shipday_nonce');

        parse_str( $_POST[ 'formData' ], $form_data );
        $enable_datetime =  !isset($form_data['shipday_enable_datetime_plugin']) ? "no" : "yes";
        $enable_order_type = !isset($form_data['shipday_enable_delivery_option']) ? "no" : "yes";

        update_option('shipday_enable_datetime_plugin', $enable_datetime);
        update_option('shipday_enable_delivery_option', $enable_order_type);
        if(isset($form_data['shipday_delivery_pickup_label']))
            update_option('shipday_delivery_pickup_label', $form_data['shipday_delivery_pickup_label']);

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

        parse_str( $_POST[ 'formData' ], $form_data );

        $enable_delivery_date =  !isset($form_data['shipday_enable_delivery_date']) ? "no" : "yes";
        $delivery_date_mandatory = !isset($form_data['shipday_delivery_date_mandatory']) ? "no" : "yes";
        $available_days_ = !isset($form_data['shipday_avaialble_delivery_days'])? [] : array_map('strval', $form_data['shipday_avaialble_delivery_days']);

        $start_delivery_slot = [];
        $start_delivery_slot['hh'] = $form_data['shipday_delivery_time_slot_start_hh'];
        $start_delivery_slot['mm'] = $form_data['shipday_delivery_time_slot_start_mm'];
        $start_delivery_slot['ampm'] = $form_data['shipday_delivery_time_slot_start_ampm'];

        $enable_delivery_time =  !isset($form_data['shipday_enable_delivery_time']) ? "no" : "yes";
        $delivery_time_mandatory = !isset($form_data['shipday_delivery_time_mandatory']) ? "no" : "yes";

        $end_delivery_slot = [];
        $end_delivery_slot['hh'] = $form_data['shipday_delivery_time_slot_end_hh'];
        $end_delivery_slot['mm'] = $form_data['shipday_delivery_time_slot_end_mm'];
        $end_delivery_slot['ampm'] = $form_data['shipday_delivery_time_slot_end_ampm'];

        update_option('shipday_enable_delivery_date', $enable_delivery_date);
        update_option('shipday_delivery_date_mandatory', $delivery_date_mandatory);
        update_option('shipday_avaialble_delivery_days',  $available_days_);
        update_option('shipday_selectable_delivery_days', $form_data['shipday_selectable_delivery_days']);

        update_option('shipday_enable_delivery_time', $enable_delivery_time);
        update_option('shipday_delivery_time_mandatory', $delivery_time_mandatory);


        update_option('shipday_delivery_time_slot_start',  $start_delivery_slot);
        update_option('shipday_delivery_time_slot_end',  $end_delivery_slot);
        update_option('shipday_delivery_time_slot_duration',  $form_data['shipday_delivery_time_slot_duration']);

        wp_send_json_success();

    }

    public static function save_pickup_settings() {
        check_ajax_referer('shipday_nonce');

        parse_str( $_POST[ 'formData' ], $form_data );

        $enable_pickup_date =  !isset($form_data['shipday_enable_pickup_date']) ? "no" : "yes";
        $pickup_date_mandatory = !isset($form_data['shipday_pickup_date_mandatory']) ? "no" : "yes";
        $available_days_ = !isset($form_data['shipday_avaialble_pickup_days'])? [] : array_map('strval', $form_data['shipday_avaialble_pickup_days']);

        $start_pickup_slot = [];
        $start_pickup_slot['hh'] = $form_data['shipday_pickup_time_slot_start_hh'];
        $start_pickup_slot['mm'] = $form_data['shipday_pickup_time_slot_start_mm'];
        $start_pickup_slot['ampm'] = $form_data['shipday_pickup_time_slot_start_ampm'];

        $enable_pickup_time =  !isset($form_data['shipday_enable_pickup_time']) ? "no" : "yes";
        $pickup_time_mandatory = !isset($form_data['shipday_pickup_time_mandatory']) ? "no" : "yes";

        $end_pickup_slot = [];
        $end_pickup_slot['hh'] = $form_data['shipday_pickup_time_slot_end_hh'];
        $end_pickup_slot['mm'] = $form_data['shipday_pickup_time_slot_end_mm'];
        $end_pickup_slot['ampm'] = $form_data['shipday_pickup_time_slot_end_ampm'];

        update_option('shipday_enable_pickup_date', $enable_pickup_date);
        update_option('shipday_pickup_date_mandatory', $pickup_date_mandatory);
        update_option('shipday_avaialble_pickup_days',  $available_days_);
        update_option('shipday_selectable_pickup_days', $form_data['shipday_selectable_pickup_days']);

        update_option('shipday_enable_pickup_time', $enable_pickup_time);
        update_option('shipday_pickup_time_mandatory', $pickup_time_mandatory);
        update_option('shipday_pickup_time_slot_start',  $start_pickup_slot);
        update_option('shipday_pickup_time_slot_end',  $end_pickup_slot);
        update_option('shipday_pickup_time_slot_duration',  $form_data['shipday_pickup_time_slot_duration']);

        wp_send_json_success();

    }
}
