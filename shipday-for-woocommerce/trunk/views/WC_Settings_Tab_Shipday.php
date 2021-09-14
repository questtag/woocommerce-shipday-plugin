<?php

class WC_Settings_Tab_Shipday
{
	public static function init()
	{

		add_filter('woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50);
		add_action('woocommerce_settings_tabs_settings_tab_shipday', __CLASS__ . '::settings_tab');
		add_action('woocommerce_update_options_settings_tab_shipday', __CLASS__ . '::update_settings');
		add_action('admin_enqueue_scripts', __CLASS__ . '::register_wcsscript'); // admin enqueue for registered js file.

	}

	public static function register_wcsscript()
	{
		wp_enqueue_script('custom-wcsscript', plugin_dir_url(__FILE__) . 'js/wc-shipday-script.js', array(), '1.0');
		wp_enqueue_script('custom-wcsscript');
	}

	public static function add_settings_tab($settings_tabs)
	{
		$settings_tabs['settings_tab_shipday'] = __('Shipday', 'woocommerce-settings-tab-shipday');
		return $settings_tabs;

	}

	public static function settings_tab()
	{
		woocommerce_admin_fields(self::get_settings());
	}

	public static function update_settings()
	{
		woocommerce_update_options(self::get_settings());
	}

	public static function get_settings()
	{
		$settings = array(
			array(
				'name' => __('General Settings', 'woocommerce-settings-tab-shipday'),
				'type' => 'title',
				'desc' => '',
				'id' => 'wc_settings_tab_shipday_general_section_title',
			),
			array(
				'name' => __('Pick up location settings', 'woocommerce-settings-tab-shipday'),
				'type' => 'select',
				'std' => 'Select Shipday Location',
				'options' => array(
					'single_pickup' => __('Single pick up location (single vendor)'),
					'multiple_pickup' => __('Multiple pick up location (multi-vendor)'),
				),
				'id' => 'wc_settings_tab_shipday_vendor_type',
			),
			array(
				'name' => __('Order Management Settings for WCFM', 'woocommerce-settings-tab-shipday'),
				'type' => 'radio',
				'std' => 'admin_manage',
				'default' => 'admin_manage',
				'options' => array(
					'admin_manage' => __('I am going to manage all the orders in Shipday'),
					'vendor_manage' => __('Vendors manages their orders in Shipday'),
				),
				'id' => 'wc_settings_tab_shipday_order_manage',
			),
			array(
				'name' => __('Shipday API Key', 'woocommerce-settings-tab-shipday'),
				'type' => 'text',
				'desc' => 'To get API Key, Login to your Shipday account and go to My Account > Profile > Api key',
//				'custom_attributes' => array('required' => 'required'),
				'id' => 'wc_settings_tab_shipday_api_key',
			),
			array(
				'type' => 'sectionend',
				'id' => 'wc_settings_tab_shipday_general_section_end',
			),
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
				'value' => "",
				'id' => 'wc_settings_tab_shipday_rest_api_consumer_key',
			),
			array(
				'name' => __('Consumer Secret', 'woocommerce-settings-tab-shipday'),
				'type' => 'text',
				'value' => "",
				'id' => 'wc_settings_tab_shipday_rest_api_consumer_secret',
			),
			array(
				'type' => 'sectionend',
				'id' => 'wc_settings_tab_shipday_rest_section_end',
			),
		);

		return apply_filters('wc_settings_tab_shipday_settings', $settings);

	}

}
?>