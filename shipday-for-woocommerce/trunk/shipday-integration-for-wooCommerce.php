<?php

/*
Plugin Name: Shipday Integration for WooCommerce
Plugin URI: https://www.shipday.com/woocommerce
Version: 1.0.0
Description:Allows you to add shipday API configuration and create connection with shipday. Then anyone places any order to the WooCommerce site it should also appear on your Shipday dispatch dashboard. Local Delivery App for WooCommerce by Shipday is compatible with Dokan Multivendor plugin, WCFM Market Place,Order Delivery Date For Woocommerce and Woo Delivery.
Author URI: https://www.shipday.com/
Text Domain: woocommerce-shipday
*/

/** Prevent direct access  */
defined('ABSPATH') || exit;



/** Functions end */
global $shipday_plugin_version;
$shipday_plugin_version = '1.0.0';

require_once ABSPATH.'wp-admin/includes/plugin.php';
require_once dirname( __FILE__ ) . '/views/WC_Settings_Tab_Shipday.php';
require_once dirname( __FILE__ ) . '/views/WCFM_vendor_settings_shipday.php';

require_once dirname(__FILE__). '/dispatch_post/post_fun.php';
require_once dirname(__FILE__). '/functions/common.php';
require_once dirname(__FILE__). '/functions/logger.php';

require_once dirname(__FILE__). '/rest_api/WooCommerce_REST_API.php';

require_once dirname( __FILE__ ) . '/shipday_order_management/Shipday_Order_Management.php';

require_once dirname(__FILE__). '/views/Notices.php';

function main() {
	if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		WC_Settings_Tab_Shipday::init();
		WCFM_vendor_settings_shipday::init();
		WooCommerce_REST_API::init();
		Shipday_Order_Management::init();
		Notices::init();
	}
}

main();

?>