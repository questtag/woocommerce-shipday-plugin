<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy settings/filter API is retained for backwards compatibility.

function get_woocommerce_settings()
{
    $settings = array(
        array(
            'name' => __('General Settings', 'shipday-for-woocommerce'),
            'type' => 'title',
            'desc' => '',
            'id' => 'wc_settings_tab_shipday_general_section_title',
        ),
        array(
            'name' => __('Shipday API Key', 'shipday-for-woocommerce'),
            'type' => 'text',
            'desc' => 'To get API Key, Login to your Shipday account and go to My Account > Profile > Api key',
            'custom_attributes' => array('required' => 'required'),
            'id' => 'wc_settings_tab_shipday_api_key',
        ),
        array(
            'type' => 'sectionend',
            'id' => 'wc_settings_tab_shipday_general_section_end',
        ),
        array(
            'name' => __('REST API Settings', 'shipday-for-woocommerce'),
            'type' => 'title',
            'desc' => 'To get REST API Keys, go to WooCommerce > Settings > Advanced > API Key. Then generate a new API key with any description, '.
                'give Read/Write permissions and copy consumer key and consumer secret and take note of the keys as you will not see it after leaving the page.',
            'id' => 'wc_settings_tab_shipday_rest_section_title',
        ),
        array(
            'name' => __('Consumer Key', 'shipday-for-woocommerce'),
            'type' => 'text',
//            'value' => "",
            'id' => 'wc_settings_tab_shipday_rest_api_consumer_key',
        ),
        array(
            'name' => __('Consumer Secret', 'shipday-for-woocommerce'),
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
        ),
        array(
            'name' => __('Orders Settings', 'shipday-for-woocommerce'),
            'type' => 'title',
            'desc' => '',
            'id' => 'wc_settings_tab_shipday_general_section_title',
        ),
        array(
            'title'       => __( 'Sync previous orders', 'shipday-for-woocommerce' ),
            'label'       => __( 'Sync previous orders', 'shipday-for-woocommerce'),
            'type'        => 'checkbox',
            'description' => '',
            'default'     => 'no',
            'id' => 'wc_settings_tab_shipday_sync'
        ),
        array(
            'title'       => __( 'Enable pickup orders', 'shipday-for-woocommerce' ),
            'label'       => __( 'Enable pickup orders', 'shipday-for-woocommerce'),
            'type'        => 'checkbox',
            'description' => 'Allow orders with local pickup shipping method to be sent to Shipday',
            'default'     => 'no',
            'id' => 'wc_settings_tab_shipday_enable_pickup'
        ),

        array(
            'title'       => __( 'Enable new Shipday webhook', 'shipday-for-woocommerce' ),
            'label'       => __( 'Enable new Shipday webhook', 'shipday-for-woocommerce'),
            'type'        => 'checkbox',
            'description' => 'Enable this to send your orders to new Shipday Webhook',
            'default'     => 'no',
            'id' => 'wc_settings_tab_shipday_enable_webhook'
        ),

        array(
            'type' => 'sectionend',
            'id' => 'wc_settings_tab_shipday_sync_section_end',
        ),
    );

    return apply_filters('wc_settings_tab_shipday_settings_single_vendor', $settings);

}

function get_dokan_settings()
{
    $settings = array(
        array(
            'name' => __('General Settings', 'shipday-for-woocommerce'),
            'type' => 'title',
            'desc' => '',
            'id' => 'wc_settings_tab_shipday_general_section_title',
        ),
        array(
            'name' => __('Order Management Settings for Dokan Multi-vendor', 'shipday-for-woocommerce'),
            'type' => 'radio',
            'std' => 'admin_manage',
            'default' => 'admin_manage',
            'options' => array(
                'admin_manage' => __('Dokan Admin account manages deliveries for all vendors', 'shipday-for-woocommerce'),
                'vendor_manage' => __('Vendors manage their orders in Shipday', 'shipday-for-woocommerce'),
            ),
            'id' => 'wc_settings_tab_shipday_order_manage',
        ),
        array(
            'name' => __('Shipday API Key of Admin\'s Account', 'shipday-for-woocommerce'),
            'type' => 'text',
            'desc' => 'To get API Key, Login to your Shipday account and go to My Account > Profile > Api key',
            'id' => 'wc_settings_tab_shipday_api_key',
        ),
        array(
            'title'       => __( 'Enable pickup orders', 'shipday-for-woocommerce' ),
            'label'       => __( 'Enable pickup orders', 'shipday-for-woocommerce'),
            'type'        => 'checkbox',
            'description' => 'Allow orders with local pickup shipping method to be sent to Shipday',
            'default'     => 'no',
            'id' => 'wc_settings_tab_shipday_enable_pickup'
        ),
        array(
            'title'       => __( 'Enable new Shipday webhook', 'shipday-for-woocommerce' ),
            'label'       => __( 'Enable new Shipday webhook', 'shipday-for-woocommerce'),
            'type'        => 'checkbox',
            'description' => 'Enable this to send your orders to new Shipday Webhook',
            'default'     => 'no',
            'id' => 'wc_settings_tab_shipday_enable_webhook'
        ),
        array(
            'type' => 'sectionend',
            'id' => 'wc_settings_tab_shipday_general_section_end',
        )
    );

    return apply_filters('wc_settings_tab_shipday_settings_dokan', $settings);

}

function get_wcfm_settings()
{
    $settings = array(
        array(
            'name' => __('General Settings', 'shipday-for-woocommerce'),
            'type' => 'title',
            'desc' => '',
            'id' => 'wc_settings_tab_shipday_general_section_title',
        ),
        array(
            'name' => __('Order Management Settings for WCFM Multi-vendor', 'shipday-for-woocommerce'),
            'type' => 'radio',
            'std' => 'admin_manage',
            'default' => 'admin_manage',
            'options' => array(
                'admin_manage' => __('WCFM Admin account manages deliveries for all vendors', 'shipday-for-woocommerce'),
                'vendor_manage' => __('Vendors manage their orders in Shipday', 'shipday-for-woocommerce'),
            ),
            'id' => 'wc_settings_tab_shipday_order_manage',
        ),
        array(
            'name' => __('Shipday API Key of Admin\'s Account', 'shipday-for-woocommerce'),
            'type' => 'text',
            'desc' => 'To get API Key, Login to your Shipday account and go to My Account > Profile > Api key',
            'id' => 'wc_settings_tab_shipday_api_key',
        ),
        array(
            'title'       => __( 'Enable pickup orders', 'shipday-for-woocommerce' ),
            'label'       => __( 'Enable pickup orders', 'shipday-for-woocommerce'),
            'type'        => 'checkbox',
            'description' => 'Allow orders with local pickup shipping method to be sent to Shipday',
            'default'     => 'no',
            'id' => 'wc_settings_tab_shipday_enable_pickup'
        ),
        array(
            'title'       => __( 'Enable new Shipday webhook', 'shipday-for-woocommerce' ),
            'label'       => __( 'Enable new Shipday webhook', 'shipday-for-woocommerce'),
            'type'        => 'checkbox',
            'description' => 'Enable this to send your orders to new Shipday Webhook',
            'default'     => 'no',
            'id' => 'wc_settings_tab_shipday_enable_webhook'
        ),
        array(
            'type' => 'sectionend',
            'id' => 'wc_settings_tab_shipday_general_section_end',
        )
    );

    return apply_filters('wc_settings_tab_shipday_settings_wcfm', $settings);
}
