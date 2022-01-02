<?php
require_once dirname(__FILE__).'/WC_Settings_Tab_Shipday_menus.php';

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
        if ( is_plugin_active( 'dokan-lite/dokan.php' ) )
            $settings = get_dokan_settings() ;
        elseif ( is_plugin_active( 'wc-multivendor-marketplace/wc-multivendor-marketplace.php' ) )
            $settings = get_wcfm_settings();
        else
            $settings = get_woocommerce_settings();

		return apply_filters('wc_settings_tab_shipday_settings', $settings);

	}

}
?>