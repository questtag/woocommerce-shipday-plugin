<?php
/**
 * Plugin Name: Shipday – Custom WC Settings Tab (with Groups)
 */
require_once dirname(__FILE__). '/Shipday_Time_Slot_Util.php';

class Shipday_WC_Settings_Tab {
    const TAB = 'shipday2';
    const SECTION_GENERAL = "general";
    const SECTION_ORDER = "order";
    const SECTION_REST_API = "rest_api";
    const SECTION_DATE_TIME = "date_time";

    const time_slot_ids = [ 'shipday_delivery_time_slot_start', 'shipday_delivery_time_slot_end', 'shipday_pickup_time_slot_start', 'shipday_pickup_time_slot_end' ];

    public static function initialize() {
        // 1) Add our tab BEFORE WooCommerce's "Advanced" tab.
        add_filter( 'woocommerce_settings_tabs_array', [ __CLASS__, 'add_tab_before_advanced' ], 10 );

        // Output our section (“group”) links
        add_action( 'woocommerce_sections_' . self::TAB, [ __CLASS__, 'render_sections_nav' ] );

        // Render settings for the current section
        add_action( 'woocommerce_settings_tabs_' . self::TAB, [ __CLASS__, 'render_settings' ] );

        // Save fields for the current section
        add_action( 'woocommerce_update_options_' . self::TAB, [ __CLASS__, 'save_settings' ] );

        add_action( 'woocommerce_admin_field_shipday_time_slot', 'Shipday_Time_Slot_Util::custom_time_slot_row');

        foreach ( self::time_slot_ids as $id ) {
            add_filter( "woocommerce_admin_settings_sanitize_option_{$id}", 'Shipday_Time_Slot_Util::sanitize_save_time_slot', 10, 3 );
        }

        add_action( 'woocommerce_admin_field_shipday_section_text',  'Shipday_Time_Slot_Util::custom_section_text');

    }
    /**
     * Insert our tab before the built-in "Advanced" tab.
     */
    public static function add_tab_before_advanced( $tabs ) {
        $tabs["shipday2"] = __('shipday2', 'woocommerce-settings-tab-shipday');
        return $tabs;
    }


    /** Current section helper: '' (Group 1) or 'group2' */
    private static function current_section() {
        return isset( $_GET['section'] ) ? wc_clean( wp_unslash( $_GET['section'] ) ) : self::SECTION_GENERAL;
    }

    /** Our two groups (sections). We print nav ourselves (no filters needed). */
    private static function sections() {
        $sections = [];
        $sections[self::SECTION_GENERAL] = __( 'General', 'shipday' );
        if ( !is_plugin_active( 'dokan-lite/dokan.php' ) && !is_plugin_active( 'wc-multivendor-marketplace/wc-multivendor-marketplace.php' ) )
            $sections[self::SECTION_REST_API] = __( 'Rest API', 'shipday' );
        $sections[self::SECTION_ORDER] = __( 'Order', 'shipday' );
        $sections[self::SECTION_DATE_TIME] = __( 'Datetime', 'shipday' );
        return $sections;
    }

    /** Print the section (group) links row */
    public static function render_sections_nav() {
        $sections = self::sections();
        if ( count( $sections ) < 2 ) return; // no nav if only one

        $current = self::current_section();
        echo '<ul class="subsubsub">';
        $i = 0; $total = count( $sections );
        foreach ( $sections as $id => $label ) {
            $url = add_query_arg(
                [ 'page' => 'wc-settings', 'tab' => self::TAB, 'section' => $id ],
                admin_url( 'admin.php' )
            );
            printf(
                '<li><a href="%s" class="%s">%s</a>%s</li>',
                esc_url( $url ),
                ( (string)$current === (string)$id ) ? 'current' : '',
                esc_html( $label ),
                ++$i < $total ? ' | ' : ''
            );
        }
        echo '</ul><br class="clear" />';
    }

    /** Render settings for the current section */
    public static function render_settings() {
        $activeSection = self::current_section();
        $fields = self::get_fields_by_section( $activeSection );
        if ( class_exists( 'WC_Admin_Settings' ) ) {
            WC_Admin_Settings::output_fields( $fields );
        }
    }

    public static function get_fields_by_section($section) {
        if($section === self::SECTION_GENERAL)
            return self::general_settings_fields();
        else if( $section === self::SECTION_ORDER )
            return self::order_settings_fields();
        else if( $section === self::SECTION_REST_API )
            return self::rest_api_fields();
        else if( $section === self::SECTION_DATE_TIME )
            return self::date_time_settings_fields();
        return [];
    }

    /** Save settings for the current section */
    public static function save_settings() {

        $current_section = self::current_section();
        $fields = self::get_fields_by_section( $current_section );
        //$fields = apply_filters( 'woocommerce_get_settings_'. self::TAB, [], $current_section );
        apply_filters('wc_settings_tab_shipday_settings', $fields);
        if ( class_exists('WC_Admin_Settings') ) {
            WC_Admin_Settings::save_fields( $fields );
        }

    }

    public static function general_settings_fields( ) {
        return  [
            array(
                'name' => __('General Settings', 'woocommerce-settings-tab-shipday'),
                'type' => 'title',
                'desc' => '',
                'id' => 'wc_settings_tab_shipday_general_section_title',
            ),
            array(
                'name' => __('Shipday API Key', 'woocommerce-settings-tab-shipday'),
                'type' => 'text',
                'desc' => 'To get API Key, Login to your Shipday account and go to My Account > Profile > Api key',
                'custom_attributes' => array('required' => 'required'),
                'id' => 'wc_settings_tab_shipday_api_key'
            ),
            array(
                'type' => 'sectionend',
                'id' => 'wc_settings_tab_shipday_general_section_end',
            )
        ];
    }

    public static function rest_api_fields( ) {
        return [
            array(
                'name' => __('REST API Settings', 'woocommerce-settings-tab-shipday'),
                'type' => 'title',
                'desc' => 'To get REST API Keys, go to WooCommerce > Settings > Advanced > API Key. Then generate a new API key with any description, '.
                    'give Read/Write permissions and copy consumer key and consumer secret and take note of the keys as you will not see it after leaving the page.',
                'id' => 'wc_settings_tab_shipday_rest_section_title',
            ),
            array(
                'name' => __('Consumer Key', 'woocommerce-settings-tab-shipday'),
                'type' => 'text',
//            'value' => "",
                'id' => 'wc_settings_tab_shipday_rest_api_consumer_key',
            ),
            array(
                'name' => __('Consumer Secret', 'woocommerce-settings-tab-shipday'),
                'type' => 'text',
//            'value' => "",
                'id' => 'wc_settings_tab_shipday_rest_api_consumer_secret',
            ),
            array(
                'type'  => 'hidden',
                'id'    => 'wc_settings_tab_shipday_registered_uuid',
            ),
            array(
                'type' => 'sectionend',
                'id' => 'wc_settings_tab_shipday_rest_section_end',
            )
        ];
    }

    public static function order_settings_fields( ) {
        return [
            array(
                'name' => __('Orders Settings', 'woocommerce-settings-tab-shipday'),
                'type' => 'title',
                'desc' => '',
                'id' => 'wc_settings_tab_shipday_general_section_title',
            ),
            array(
                'title'       => __( 'Enable pickup orders', 'woocommerce-settings-tab-shipday' ),
                'label'       => __( 'Enable pickup orders', 'woocommerce-settings-tab-shipday'),
                'type'        => 'checkbox',
                'description' => 'Allow orders with local pickup shipping method to be sent to Shipday',
                'default'     => 'no',
                'id' => 'wc_settings_tab_shipday_enable_pickup'
            ),

            array(
                'title'       => __( 'Enable new Shipday webhook', 'woocommerce-settings-tab-shipday' ),
                'label'       => __( 'Enable new Shipday webhook', 'woocommerce-settings-tab-shipday'),
                'type'        => 'checkbox',
                'description' => 'Enable this to send your orders to new Shipday Webhook',
                'default'     => 'no',
                'id' => 'wc_settings_tab_shipday_enable_webhook'
            ),
            array(
                'type' => 'sectionend',
                'id' => 'wc_settings_tab_shipday_sync_section_end',
            ),
        ];
    }

    public static function date_time_settings_fields( ) {
        return [
            array(
                'name' => __('Datetime Settings', 'woocommerce-settings-tab-shipday'),
                'type' => 'title',
                'desc' => 'Datetime picker at your checkout page',
                'id' => 'wc_settings_tab_shipday_general_section_title',
            ),

            [
                'title'       => __( 'Enable Datetime plugin', 'woocommerce-settings-tab-shipday' ),
                'type'        => 'checkbox',
                'default'     => 'no',
                'desc'        => __( 'Disabling this will hide it on the checkout page.', 'woocommerce-settings-tab-shipday' ), // tooltip text
                'desc_tip'    => true,
                'id' => 'shipday_enable_datetime_plugin'
            ],

            [
                'type' => 'shipday_section_text',
                'id'   => 'shipday_order_type_setings',
                'text' => __( 'Order Type Settings', 'woocommerce-settings-tab-shipday' ), // centered text
            ],
            array(
                'title'       => __( 'Enable order type selection (Delivery or Pickup)', 'woocommerce-settings-tab-shipday' ),
                'label'       => __( 'Enable order type selection (Delivery or Pickup)', 'woocommerce-settings-tab-shipday'),
                'type'        => 'checkbox',
                'default'     => 'no',
                'desc'     => '',
                'id' => 'shipday_enable_delivery_option'
            ),

            [
                'type' => 'shipday_section_text',
                'id'   => 'wc_settings_tab_shipday_sync_div',
                'text' => __( 'Delivery Date Settings', 'woocommerce-settings-tab-shipday' ), // centered text
            ],
            array(
                'title'       => __( 'Enable Delivery Date', 'woocommerce-settings-tab-shipday' ),
                'label'       => __( 'Enable Delivery Date', 'woocommerce-settings-tab-shipday'),
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no',
                'id' => 'shipday_enable_delivery_date'
            ),
            array(
                'title'       => __( 'Make Delivery Date Field Mandatory', 'woocommerce-settings-tab-shipday' ),
                'label'       => __( 'Make Delivery Date Field Mandatory', 'woocommerce-settings-tab-shipday'),
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no',
                'id' => 'shipday_delivery_date_mandatory'
            ),
            [
                'title'    => __( 'Delivery Days', 'woocommerce-settings-tab-shipday' ),
                'type'     => 'multiselect',
                'class'    => 'wc-enhanced-select', // select2 UI
                'options'  => [
                    '0' => __( 'Sunday', 'woocommerce-settings-tab-shipday' ),
                    '1' => __( 'Monday', 'woocommerce-settings-tab-shipday' ),
                    '2' => __( 'Tuesday', 'woocommerce-settings-tab-shipday' ),
                    '3' => __( 'Wednesday', 'woocommerce-settings-tab-shipday' ),
                    '4' => __( 'Thursday', 'woocommerce-settings-tab-shipday' ),
                    '5' => __( 'Friday', 'woocommerce-settings-tab-shipday' ),
                    '6' => __( 'Saturday', 'woocommerce-settings-tab-shipday' ),
                ],
                'default'  => [ '0','1','2','3','4' ], // optional
                'desc_tip' => __( 'Choose all days you deliver.', 'woocommerce-settings-tab-shipday' ),
                'id' => 'shipday_avaialble_delivery_days'
            ],
            array(
                'title'       => __( 'Allow Delivery in Next Available Days ', 'woocommerce-settings-tab-shipday' ),
                'label'       => __( 'Allow Delivery in Next Available Days ', 'woocommerce-settings-tab-shipday'),
                'type'        => 'text',
                'default'     => '15',
                'desc_tip' => __( 'only this number of days will be available to select as a delivery day in the calendar', 'woocommerce-settings-tab-shipday' ),
                'id' => 'shipday_selectable_delivery_days'
            ),

            [
                'type' => 'shipday_section_text',
                'id'   => 'shipday_pickup_date_settings',
                'text' => __( 'Pickup Date Settings', 'woocommerce-settings-tab-shipday' ), // centered text
            ],

            array(
                'title'       => __( 'Enable Pickup Date', 'woocommerce-settings-tab-shipday' ),
                'label'       => __( 'Enable Pickup Date', 'woocommerce-settings-tab-shipday'),
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no',
                'id' => 'shipday_enable_pickup_date'
            ),
            array(
                'title'       => __( 'Make Pickup Date Field Mandatory', 'woocommerce-settings-tab-shipday' ),
                'label'       => __( 'Make Pickup Date Field Mandatory', 'woocommerce-settings-tab-shipday'),
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no',
                'id' => 'shipday_pickup_date_mandatory'
            ),

            [
                'title'    => __( 'Pickup Days', 'woocommerce-settings-tab-shipday' ),
                'type'     => 'multiselect',
                'class'    => 'wc-enhanced-select', // select2 UI
                'options'  => [
                    '0' => __( 'Sunday', 'woocommerce-settings-tab-shipday' ),
                    '1' => __( 'Monday', 'woocommerce-settings-tab-shipday' ),
                    '2' => __( 'Tuesday', 'woocommerce-settings-tab-shipday' ),
                    '3' => __( 'Wednesday', 'woocommerce-settings-tab-shipday' ),
                    '4' => __( 'Thursday', 'woocommerce-settings-tab-shipday' ),
                    '5' => __( 'Friday', 'woocommerce-settings-tab-shipday' ),
                    '6' => __( 'Saturday', 'woocommerce-settings-tab-shipday' ),
                ],
                'default'  => [ '0','1','2','3','4' ], // optional
                'desc_tip' => __( 'Choose days your customer can pickup.', 'woocommerce-settings-tab-shipday' ),
                'id' => 'shipday_avaialble_pickup_days'
            ],

            array(
                'title'       => __( 'Allow Pickup in Next Available Days ', 'woocommerce-settings-tab-shipday' ),
                'label'       => __( 'Allow Pickup in Next Available Days ', 'woocommerce-settings-tab-shipday'),
                'type'        => 'text',
                'default'     => '15',
                'desc_tip' => __( 'Customer can select only this number of days in the calendar for pickup', 'woocommerce-settings-tab-shipday' ),
                'id' => 'shipday_selectable_pickup_days'
            ),

            [
                'type' => 'shipday_section_text',
                'id'   => 'shipday_delivery_time_settings',
                'text' => __( 'Delivery Time Settings', 'woocommerce-settings-tab-shipday' ), // centered text
            ],

            array(
                'title'       => __( 'Enable Delivery Time', 'woocommerce-settings-tab-shipday' ),
                'label'       => __( 'Enable Delivery Time', 'woocommerce-settings-tab-shipday'),
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no',
                'id' => 'shipday_enable_delivery_time'
            ),
            array(
                'title'       => __( 'Make Delivery Time Field Mandatory', 'woocommerce-settings-tab-shipday' ),
                'label'       => __( 'Make Delivery Time Field Mandatory', 'woocommerce-settings-tab-shipday'),
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no',
                'id' => 'shipday_delivery_time_mandatory'
            ),

            [
                'title' => __( 'Delivery Slot Starts From', 'woocommerce-settings-tab-shipday' ),
                'id'    => 'shipday_delivery_time_slot_start',
                'type'  => 'shipday_time_slot',
                'desc'  => '',
            ],

            [
                'title' => __( 'Delivery Slot Ends At', 'woocommerce-settings-tab-shipday' ),
                'id'    => 'shipday_delivery_time_slot_end',
                'type'  => 'shipday_time_slot',
                'desc'  => '',
            ],
            [
                'title'   => __( 'Slot Duration (Mins)', 'woocommerce-settings-tab-shipday' ),
                'id'      => 'shipday_delivery_time_slot_duration',
                'type'    => 'select',
                'options' => [
                    '10' => '10',
                    '20' => '20',
                    '30' => '30',
                    '45' => '45',
                    '60' => '60',
                    '90' => '90',
                    '120' => '120',
                ],
                'default' => '1',
                'desc'    => '',
            ],

            [
                'type' => 'shipday_section_text',
                'id'   => 'shipday_pickup_time_settings',
                'text' => __( 'Pickup Time Settings', 'woocommerce-settings-tab-shipday' ), // centered text
            ],

            array(
                'title'       => __( 'Enable Pickup Time', 'woocommerce-settings-tab-shipday' ),
                'label'       => __( 'Enable Pickup Time', 'woocommerce-settings-tab-shipday'),
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no',
                'id' => 'shipday_enable_pickup_time'
            ),
            array(
                'title'       => __( 'Make Pickup Time Field Mandatory', 'woocommerce-settings-tab-shipday' ),
                'label'       => __( 'Make Pickup Time Field Mandatory', 'woocommerce-settings-tab-shipday'),
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no',
                'id' => 'shipday_pickup_time_mandatory'
            ),
            [
                'title' => __( 'Pickup Slot Starts From', 'woocommerce-settings-tab-shipday' ),
                'id'    => 'shipday_pickup_time_slot_start',
                'type'  => 'shipday_time_slot',
                'desc'  => '',
            ],

            [
                'title' => __( 'Pickup Slot Ends At', 'woocommerce-settings-tab-shipday' ),
                'id'    => 'shipday_pickup_time_slot_end',
                'type'  => 'shipday_time_slot',
                'desc'  => '',
            ],
            [
                'title'   => __( 'Slot Duration (Mins)', 'woocommerce-settings-tab-shipday' ),
                'id'      => 'shipday_pickup_time_slot_duration',
                'type'    => 'select',
                'options' => [
                    '10' => '10',
                    '20' => '20',
                    '30' => '30',
                    '45' => '45',
                    '60' => '60',
                    '90' => '90',
                    '120' => '120',
                ],
                'default' => '1',
                'desc'    => '',
            ],

            array(
                'type' => 'sectionend',
                'id' => 'wc_settings_tab_shipday_sync_section_end',
            ),
        ];
    }



}
