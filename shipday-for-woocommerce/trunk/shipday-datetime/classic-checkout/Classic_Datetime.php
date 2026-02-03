<?php
/**
 * Plugin Name: Shipday Datetime
 * Description: Adds a Date/Time picker before the Shipping options on Classic Cehckout. Saves to order meta.
 * Version: 2.0.0
 * Author: Hadi
 */

require_once dirname(__DIR__). '../../functions/common.php';
require_once dirname(__DIR__). '../../functions/logger.php';
require_once dirname(__FILE__) . '../../block-checkout/Shipday_Woo_DateTime_Util.php';

class Classic_Datetime {

    public static $hpos;

    public static function init() {
        add_action( 'before_woocommerce_init', function () {
            if ( class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) ) {
                if ( \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
                    // HPOS usage is enabled.
                    self::$hpos = true;
                } else {
                    // Traditional CPT-based orders are in use.
                    self::$hpos = false;
                }
            }
        } );


        add_action( 'wp_enqueue_scripts',  array( __CLASS__, 'enqueue_styles' )  );
        add_action( 'wp_enqueue_scripts',  array( __CLASS__, 'enqueue_scripts' ) );


        add_action( 'woocommerce_after_order_notes', array( __CLASS__, 'render_datetime' ) );
        add_action('woocommerce_after_checkout_validation', array( __CLASS__, 'validate_before_save'));

        add_action('woocommerce_checkout_update_order_meta', array( __CLASS__, 'classic_save' ) );

        //show
        add_action( 'woocommerce_admin_order_data_after_shipping_address',  array( __CLASS__, 'admin_show'));
        add_filter( 'woocommerce_get_order_item_totals', array( __CLASS__, 'thank_you_page_delivery_information_row'), 10, 2 );


    }

    public static function render_datetime() {
        if ( ! function_exists('is_checkout') || ! is_checkout() ) return;

        $enable_datetime_plugin = get_option('shipday_enable_datetime_plugin', "no") === "yes";
        $enable_delivery_option = get_option('shipday_enable_delivery_option', "no") === "yes";
        $today = wp_date('Y-m-d', current_time('timestamp', 1));
        $week_starts_from = "0";
        $date_format = "F j, Y";

        // Delivery dates
        $enable_delivery_date = get_option('shipday_enable_delivery_date', "no") === "yes";
        $delivery_date_field_label = "Scheduled Delivery Date";
        $delivery_date_mandatory = get_option('shipday_delivery_date_mandatory', "no") === "yes";
        $delivery_date_selectable_days = get_option('shipday_selectable_delivery_days', 30);
        $delivery_disable_week_days = Shipday_Woo_DateTime_Util::get_disable_week_days(get_option('shipday_avaialble_delivery_days', Shipday_Woo_DateTime_Util::WEEK_DAYS));
        $delivery_auto_select_first_date = true;


        // Pickup dates
        $enable_pickup_date = get_option('shipday_enable_pickup_date', "no") === "yes";
        $pickup_date_field_label = "Scheduled Pickup Date";
        $pickup_date_mandatory = get_option('shipday_pickup_date_mandatory', "no") === "yes";

        $pickup_date_selectable_days = get_option('shipday_selectable_pickup_days', 30);
        $pickup_disable_week_days = Shipday_Woo_DateTime_Util::get_disable_week_days(get_option('shipday_avaialble_pickup_days', Shipday_Woo_DateTime_Util::WEEK_DAYS));
        $pickup_auto_select_first_date = true;

        echo "<div data-today_date='" . $today . "'  id='shipday_woo_delivery_setting_wrapper'>";

        // delivery options
        if ( $enable_datetime_plugin && $enable_delivery_option ) {
            echo '<div id="shipday_order_type_div" >';
            woocommerce_form_field( 'shipday_order_type_field',
                [
                    'type'        => 'select',
                    'class'       => [
                        'shipday_order_type_field form-row-wide',
                    ],
                    'label'       => "Delivery/Pickup option",
                    'placeholder' => "Choose Option",
                    'options'     => Classic_Datetime::getDeliveryoptions(),
                    'required'    => true,
                ], WC()->checkout->get_value( 'shipday_order_type_field' ) );
            echo '</div>';
        }

        //delivery date
        if ( $enable_datetime_plugin && $enable_delivery_date) {
            echo '<div id="shipday_delivery_date_div" style="display:none;">';
            woocommerce_form_field('shipday_delivery_date_field',
                [
                    'type' => 'text',
                    'class' => array(
                        'shipday_delivery_date_field form-row-wide',
                    ),
                    'id' => "shipday_delivery_date_datepicker",
                    'label' => $delivery_date_field_label,
                    'placeholder' => "Delivery Date",
                    'required' => $delivery_date_mandatory,
                    'custom_attributes' => [
                        'data-today_date' => $today,
                        'data-selectable_days' => $delivery_date_selectable_days,
                        'data-disable_week_days' => json_encode($delivery_disable_week_days),
                        'data-date_format' => $date_format,
                        'data-week_starts_from' => $week_starts_from,
                    ],
                ], WC()->checkout->get_value('shipday_delivery_date_field'));
            echo '</div>';
        }

        // Delivery Time --------------------------------------------------------------
        $enable_delivery_time = get_option('shipday_enable_delivery_time', "no") === "yes";
        $delivery_time_field_label = "Delivery Time";
        $delivery_time_mandatory = get_option('shipday_delivery_time_mandatory', "no") === "yes";
        $auto_select_first_time = false;
        $start_delivery_slot = get_option('shipday_delivery_time_slot_start', Shipday_Woo_DateTime_Util::DEFAULT_START_SLOT);
        $end_delivery_slot = get_option('shipday_delivery_time_slot_end', Shipday_Woo_DateTime_Util::DEFAULT_END_SLOT);
        $delivery_slot_duration = get_option('shipday_delivery_time_slot_duration', "60");
        $delivery_time_options = Shipday_Woo_DateTime_Util::get_time_slots_from_range($start_delivery_slot, $end_delivery_slot, $delivery_slot_duration);

        //delivery time
        if ( $enable_datetime_plugin && $enable_delivery_time) {
            echo '<div id="shipday_delivery_time_div" style="display:none;">';
            woocommerce_form_field( 'shipday_delivery_time_field',
                [
                    'type'              => 'select',
                    'class'             => [
                        'shipday_delivery_time_field form-row-wide',
                    ],
                    'label'             => __( $delivery_time_field_label, "woo-delivery" ),
                    'placeholder'       => __( $delivery_time_field_label, "woo-delivery" ),
                    'options'           =>  $delivery_time_options,
                    'required'          => $delivery_time_mandatory,
                    'custom_attributes' => [
                        'data-default_time'       => $auto_select_first_time,
                    ],
                ], WC()->checkout->get_value( 'shipday_delivery_time_field' ) );
            echo '</div>';
        }
        //pickup date
        if ($enable_datetime_plugin && $enable_pickup_date) {
            echo '<div id="shipday_pickup_date_div" style="display:none;">';
            woocommerce_form_field('shipday_pickup_date_field',
                [
                    'type' => 'text',
                    'class' => array(
                        'shipday_pickup_date_field form-row-wide',
                    ),
                    'id' => "shipday_pickup_date_datepicker",
                    'label' => $pickup_date_field_label,
                    'placeholder' => "Pickup Date",
                    'required' => $pickup_date_mandatory,
                    'custom_attributes' => [
                        'data-today_date' => $today,
                        'data-selectable_days' => $pickup_date_selectable_days,
                        'data-disable_week_days' => json_encode($pickup_disable_week_days),
                        'data-date_format' => $date_format,
                        'data-week_starts_from' => $week_starts_from,
                    ],


                ], WC()->checkout->get_value('shipday_pickup_date_field'));
            echo '</div>';
        }
        // Pickup Time --------------------------------------------------------------
        $enable_pickup_time =  get_option('shipday_enable_pickup_time', "no") === "yes";
        $pickup_time_field_label = "Pickup Time";
        $pickup_time_mandatory = get_option('shipday_pickup_time_mandatory', "no") === "yes";
        $start_pickup_slot = get_option('shipday_pickup_time_slot_start', Shipday_Woo_DateTime_Util::DEFAULT_START_SLOT);
        $end_pickup_slot = get_option('shipday_pickup_time_slot_end', Shipday_Woo_DateTime_Util::DEFAULT_END_SLOT);
        $pickup_slot_duration = get_option('shipday_pickup_time_slot_duration', "60");
        $pickup_time_options =  Shipday_Woo_DateTime_Util::get_time_slots_from_range($start_pickup_slot, $end_pickup_slot, $pickup_slot_duration);

        if ( $enable_datetime_plugin && $enable_pickup_time) {
            echo '<div id="shipday_pickup_time_div" style="display:none;">';
            woocommerce_form_field( 'shipday_pickup_time_field',
                [
                    'type'              => 'select',
                    'class'             => [
                        'shipday_pickup_time_field form-row-wide',
                    ],
                    'label'             => __( $pickup_time_field_label, "woo-delivery" ),
                    'placeholder'       => __( $pickup_time_field_label, "woo-delivery" ),
                    'options'           =>  $pickup_time_options,
                    'required'          => $pickup_time_mandatory,
                    'custom_attributes' => [
                        'data-default_time'       => $auto_select_first_time,
                    ],
                ], WC()->checkout->get_value( 'shipday_pickup_time_field' ) );
            echo '</div>';
        }



        echo '</div>';

    }

    public static function validate_before_save() {
        /*
        if ( self::is_required() && empty( $_POST['shipday_delivery_datetime'] ) ) {
            wc_add_notice( __( 'Please choose your preferred delivery date & time.', 'shipday-datetime' ), 'error' );
        }
        */

        $enable_datetime_plugin = get_option('shipday_enable_datetime_plugin', "no") === "yes";
        $enable_delivery_option = get_option('shipday_enable_delivery_option', "no") === "yes";
        $enable_delivery_date = get_option('shipday_enable_delivery_date', "no") === "yes";
        $delivery_date_mandatory = get_option('shipday_delivery_date_mandatory', "no") === "yes";
        $enable_pickup_date = get_option('shipday_enable_pickup_date', "no") === "yes";
        $pickup_date_mandatory = get_option('shipday_pickup_date_mandatory', "no") === "yes";
        $enable_delivery_time = get_option('shipday_enable_delivery_time', "no") === "yes";
        $delivery_time_mandatory = get_option('shipday_delivery_time_mandatory', "no") === "yes";
        $enable_pickup_time =  get_option('shipday_enable_pickup_time', "no") === "yes";
        $pickup_time_mandatory = get_option('shipday_pickup_time_mandatory', "no") === "yes";



        if ( $enable_datetime_plugin && $enable_delivery_option ) {
            if (!isset( $_POST['shipday_order_type_field'] ) || $_POST['shipday_order_type_field'] === "" ||  $_POST['shipday_order_type_field'] === "Choose Option" ) {
                wc_add_notice( __( "Please select order type", "shipday-delivery" ), 'error' );
            }

        }
        if ( $enable_datetime_plugin  && $enable_delivery_date && $delivery_date_mandatory &&
            (!$enable_delivery_option || $_POST['shipday_order_type_field'] === "Delivery")
        ) {

            if (!isset($_POST['shipday_delivery_date_field']) || $_POST['shipday_delivery_date_field'] === "" ) {
                wc_add_notice( __( "Please select Delivery Date", "shipday-delivery" ), 'error' );
            }

        }
        if ( $enable_datetime_plugin  && $enable_pickup_date && $pickup_date_mandatory &&
            (!$enable_delivery_option || $_POST['shipday_order_type_field'] === "Pickup")
        ) {
            if (!isset($_POST['shipday_pickup_date_field']) || $_POST['shipday_pickup_date_field'] === "" || $_POST['shipday_pickup_date_field'] ==="Pickup Date" ) {
                wc_add_notice( __( "Please select Pickup Date", "shipday-delivery" ), 'error' );
            }

        }
        if ( $enable_datetime_plugin  && $enable_pickup_time && $pickup_time_mandatory &&
            (!$enable_delivery_option || $_POST['shipday_order_type_field'] === "Pickup")
        ) {

            if (!isset($_POST['shipday_pickup_time_field']) || is_null($_POST['shipday_pickup_time_field']) || $_POST['shipday_pickup_time_field'] === "") {
                wc_add_notice( __( "Please select Pickup Time", "shipday-delivery" ), 'error' );
            }

        }
        if ( $enable_datetime_plugin  && $enable_delivery_time && $delivery_time_mandatory &&
            (!$enable_delivery_option || $_POST['shipday_order_type_field'] === "Delivery")
        ) {
            if (!isset($_POST['shipday_delivery_time_field']) || is_null($_POST['shipday_delivery_time_field']) || $_POST['shipday_delivery_time_field'] === "") {
                wc_add_notice( __( "Please select Delivery Time", "shipday-delivery" ), 'error' );
            }

        }



    }

    public static function getDeliveryoptions() {
        $delivery_option['Delivery'] = "Delivery";
        $delivery_option['Pickup'] = "Pickup";
        return $delivery_option;
    }

    public static function classic_save( $order_id) {
        if ( ! function_exists('is_checkout') || ! is_checkout() ) return;
        $order = wc_get_order( $order_id );

        $enable_datetime_plugin = get_option('shipday_enable_datetime_plugin', "no") === "yes";
        $enable_order_type = get_option('shipday_enable_delivery_option', "no") === "yes";
        $enable_delivery_date = get_option('shipday_enable_delivery_date', "no") === "yes";
        $enable_pickup_date = get_option('shipday_enable_pickup_date', "no") === "yes";
        $enable_delivery_time = get_option('shipday_enable_delivery_time', "no") === "yes";
        $enable_pickup_time =  get_option('shipday_enable_pickup_time', "no") === "yes";

        $order_type = sanitize_text_field( wp_unslash( $_POST['shipday_order_type_field'] ) );
        if ( $enable_datetime_plugin && $enable_order_type && isset( $_POST['shipday_order_type_field'] ) ) {
            if ( $order_type !== '' ) {
                if ( self::$hpos === true ) {
                    $order->update_meta_data( '_shipday_order_type', $order_type );
                } else {
                    update_post_meta( $order_id, '_shipday_order_type', $order_type );
                }
            }
        }
        if ( $enable_datetime_plugin && (!$enable_order_type || $order_type==='Delivery') && $enable_delivery_date && isset( $_POST['shipday_delivery_date_field'] ) ) {
            $val = sanitize_text_field( wp_unslash( $_POST['shipday_delivery_date_field'] ) );
            shipday_logger('error', 'datetime : '.$val);
            if ( $val !== '' ) {
                if ( self::$hpos === true ) {
                    $order->update_meta_data( '_shipday_delivery_date', $val );
                } else {
                    update_post_meta( $order_id, '_shipday_delivery_date', $val );
                }
            }
        }
        if ( $enable_datetime_plugin && (!$enable_order_type || $order_type==='Delivery') && $enable_delivery_time && isset( $_POST['shipday_delivery_time_field'] ) ) {
            $val_time = sanitize_text_field( $_POST['shipday_delivery_time_field'] );
            if ( $val_time !== '' ) {
                if ( self::$hpos === true ) {
                    $order->update_meta_data( '_shipday_delivery_time', $val_time );
                } else {
                    update_post_meta( $order_id, '_shipday_delivery_time', $val_time );
                }
            }
        }

        if ( $enable_datetime_plugin && (!$enable_order_type || $order_type==='Pickup') && $enable_pickup_date && isset( $_POST['shipday_pickup_date_field'] ) ) {
            $val = sanitize_text_field( wp_unslash( $_POST['shipday_pickup_date_field'] ) );
            shipday_logger('error', 'datetime : '.$val);
            if ( $val !== '' ) {
                if ( self::$hpos === true ) {
                    $order->update_meta_data( '_shipday_delivery_date', $val );
                } else {
                    update_post_meta( $order_id, '_shipday_delivery_date', $val );
                }
            }
        }

        if ( $enable_datetime_plugin && (!$enable_order_type || $order_type==='Pickup') &&  $enable_pickup_time && isset( $_POST['shipday_pickup_time_field'] ) ) {
            $val_time = sanitize_text_field( $_POST['shipday_pickup_time_field'] );
            if ( $val_time !== '' ) {
                if ( self::$hpos === true ) {
                    $order->update_meta_data( '_shipday_delivery_time', $val_time );
                } else {
                    update_post_meta( $order_id, '_shipday_delivery_time', $val_time );
                }
            }
        }
        $order->save();
        self::reset_session();
    }

    public static function admin_show( $order ) {

        if( version_compare( get_option( 'woocommerce_version' ), '3.0.0', ">=" ) ) {
            $order_id = $order->get_id();
        } else {
            $order_id = $order->id;
        }

        $delivery_date_field_label= "Scheduled Date";
        $delivery_time_field_label= "Time slot";
        if((metadata_exists('post', $order_id, '_shipday_delivery_date') && get_post_meta( $order_id, '_shipday_delivery_date', true ) != "") || ($order->meta_exists('_shipday_delivery_date') && $order->get_meta( '_shipday_delivery_date', true ) != "")) {

            $delivery_date_format = (isset($delivery_date_settings['date_format']) && !empty($delivery_date_settings['date_format'])) ? $delivery_date_settings['date_format'] : "F j, Y";

            if ( self::$hpos === true ) {
                $delivery_date = date($delivery_date_format, strtotime($order->get_meta( '_shipday_delivery_date', true )));
            } else {
                $delivery_date = date($delivery_date_format, strtotime(get_post_meta( $order_id, '_shipday_delivery_date', true )));
            }

            echo '<p><strong>'.__($delivery_date_field_label, "woo-delivery").':</strong> ' . $delivery_date . '</p>';

        }

        if((metadata_exists('post', $order_id, '_shipday_delivery_time') && get_post_meta($order_id,"_shipday_delivery_time",true) != "") || ($order->meta_exists('_shipday_delivery_time') && $order->get_meta( '_shipday_delivery_time', true ) != "")) {
            if ( self::$hpos === true ) {
                $time_slot = $order->get_meta( '_shipday_delivery_time', true);
            } else {
                $time_slot = get_post_meta( $order_id, '_shipday_delivery_time', true );
            }
            echo '<p><strong>'.__($delivery_time_field_label, "woo-delivery").':</strong> ' .$time_slot. '</p>';
        }
    }

    public static function thank_you_page_delivery_information_row( $total_rows, $order ) {

        if ( version_compare( get_option( 'woocommerce_version' ), '3.0.0', ">=" ) ) {
            $order_id = $order->get_id();
        } else {
            $order_id = $order->id;
        }

        if ( ( metadata_exists( 'post', $order_id, '_shipday_delivery_date' ) && get_post_meta( $order_id, '_shipday_delivery_date', true ) != "" ) || ( $order->meta_exists( '_shipday_delivery_date' ) && $order->get_meta( '_shipday_delivery_date', true ) != "" ) ) {

            if ( self::$hpos === true ){
                $delivery_date = $order->get_meta( '_shipday_delivery_date', true);
            } else {
                $delivery_date = get_post_meta( $order_id, '_shipday_delivery_date', true);
            }

            $total_rows['delivery_date'] = array(
                'label' => "Scheduled Date",
                'value' => $delivery_date,
            );
        }

        if ( ( metadata_exists( 'post', $order_id, '_shipday_delivery_time' ) && get_post_meta( $order_id, "_shipday_delivery_time", true ) != "" ) || ( $order->meta_exists( '_shipday_delivery_time' ) && $order->get_meta( '_shipday_delivery_time', true ) != "" ) ) {

            if ( self::$hpos === true ){
                $timeslot = $order->get_meta( '_shipday_delivery_time', true );
            } else {
                $timeslot = get_post_meta( $order_id, "_shipday_delivery_time", true );
            }
            $total_rows['delivery_time'] = array(
                'label' => "Time Slot",
                'value' => $timeslot,
            );
        }

        return $total_rows;
    }

    public static function enqueue_scripts() {

        if ( is_checkout() && !( is_wc_endpoint_url( 'order-pay' ) || is_wc_endpoint_url( 'order-received' ) ) ) {

            wp_enqueue_script( "flatpickr_js", plugin_dir_url( __FILE__ ) . '../public/js/flatpickr.min.js', ['jquery'], '2.0.1', true );

            $theme_name = esc_html( wp_get_theme()->get( 'Name' ) );
            $theme = wp_get_theme();

            if ( /*strpos($theme_name,"Flatsome") !== false || strpos($theme->parent_theme,"Flatsome") !== false || */strpos($theme_name,"YOOtheme") !== false || strpos($theme->parent_theme,"YOOtheme") !== false ) {
                //wp_enqueue_script( "select2_js", plugin_dir_url( __FILE__ ) . 'js/select2.coderockz.delivery.min.js', array('jquery'), $this->version, true );
                wp_enqueue_script( "shipday_script", plugin_dir_url( __FILE__ ) . '../public/js/shipday-woo-delivery-public-flatsome.js', array( 'jquery', 'select2', 'flatpickr_js' ), '2.0.0', true );
            } else {
                //wp_enqueue_script( "selectWoo_js", plugin_dir_url( __FILE__ ) . 'js/selectWoo.coderockz.delivery.min.js', array('jquery'), $this->version, true );
                wp_enqueue_script( "shipday_script", plugin_dir_url( __FILE__ ) . '../public/js/shipday-woo-delivery-public.js', array( 'jquery', 'selectWoo', 'flatpickr_js' ),'2.01.8', true );
            }
        }
    }

    public static function enqueue_styles() {
        if ( is_checkout() && !( is_wc_endpoint_url( 'order-pay' ) || is_wc_endpoint_url( 'order-received' ) ) ) {
            wp_enqueue_style( "flatpickr_css", plugin_dir_url( __FILE__ ) . '../public/css/flatpickr.min.css', array(), '2.0.0', 'all' );
            wp_enqueue_style( "shipday_styles", plugin_dir_url( __FILE__ ) . '../public/css/shipday-woo-delivery-public.css', array(), '2.0.1', 'all' );
            //wp_enqueue_style( 'select2mincss', plugin_dir_url( __FILE__ ) . '../public/css/select2.min.css', array(), '2.0.0', 'all' );
        }
    }

    static function reset_session() {
        WC()->session->__unset( 'on_change' );
        WC()->session->__unset( 'shipday_order_type_field' );
        WC()->session->__unset( 'shipday_delivery_date_field' );
        WC()->session->__unset( 'shipday_pickup_date_field' );
        WC()->session->__unset( 'shipday_delivery_time_field' );
        WC()->session->__unset( 'shipday_pickup_time_field' );
    }


}


