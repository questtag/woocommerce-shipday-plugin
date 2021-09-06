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
			'section_title' => array(
				'name' => __('Shipday Settings', 'woocommerce-settings-tab-shipday'),
				'type' => 'title',
				'desc' => 'Login to your Shipday account to get the API key. It’s in the following, My Account > Profile > Api key',
				'id' => 'wc_settings_tab_shipday_section_title',
			),
			'shipday_key' => array(
				'name' => __('Shipday API Key', 'woocommerce-settings-tab-shipday'),
				'type' => 'text',
				'custom_attributes' => array('required' => 'required'),
				'id' => 'wc_settings_tab_shipday_api_key',
			),
			'shipday_location' => array(
				'name' => __('Pick up location settings', 'woocommerce-settings-tab-shipday'),
				'type' => 'select',
				'std' => 'Select Shipday Location',
				'options' => array(
					'single_pickup' => __('Single pick up location (single vendor)'),
					'multiple_pickup' => __('Multiple pick up location (multi-vendor)'),
				),
				'id' => 'wc_settings_tab_shipday_location',
			),
			'section_end' => array(
				'type' => 'sectionend',
				'id' => 'wc_settings_tab_shipday_section_end',
			),
		);

		return apply_filters('wc_settings_tab_shipday_settings', $settings);

	}

}
?>