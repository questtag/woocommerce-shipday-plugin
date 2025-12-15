<?php

/*
Plugin Name: Shipday Integration for Wordpress (WooCommerce)
Plugin URI: https://www.shipday.com/woocommerce
Version: 2.0.0
Description: Enable fast local deliveries for your online store or marketplace with Shipday. Easy driver and dispatch app with live delivery tracking. Built-in connection with on-demand delivery services like DoorDash and Uber in the US.
Author URI: https://www.shipday.com/
Text Domain: woocommerce-shipday
*/

/** Prevent direct access  */


defined('ABSPATH') || exit;



/** Functions end */
global $shipday_plugin_version;
$shipday_plugin_version = '2.0.0';

require_once ABSPATH.'wp-admin/includes/plugin.php';
require_once dirname( __FILE__ ) . '/views/WC_Settings_Tab_Shipday.php';
require_once dirname( __FILE__ ) . '/views/WCFM_vendor_settings_shipday.php';
require_once dirname(__FILE__) . '/views/Dokan_vendor_settings_shipday.php';

require_once dirname(__FILE__). '/dispatch_post/post_fun.php';
require_once dirname(__FILE__). '/functions/common.php';
require_once dirname(__FILE__). '/functions/logger.php';

require_once dirname(__FILE__). '/rest_api/WooCommerce_REST_API.php';

require_once dirname( __FILE__ ) . '/shipday_order_management/Shipday_Order_Management.php';
require_once dirname(__FILE__) . '/shipday_order_management/Woo_Sync_Order.php';

require_once dirname(__FILE__). '/views/Notices.php';

require_once dirname(__FILE__) . '/shipday-datetime/classic-checkout/Classic_Datetime.php';
//require_once dirname(__FILE__) . '/shipday-datetime/block-checkout/Shipday_Delivery_Block.php';
require_once dirname(__FILE__) . '/shipday-datetime/block-checkout/Shipday_Woo_Delivery_Block.php';
require_once dirname(__FILE__) . '/shipday-datetime/block-checkout/Shipday_Woo_Delivery_Block_Storage.php';
require_once dirname(__FILE__) . '/shipday-datetime/block-checkout/Shipday_Woo_DateTime_Util.php';

require_once dirname(__FILE__). '/admin/Shipday_WC_Settings_Tab.php';
require_once dirname(__FILE__). '/admin/Shipday_Menu_Settings.php';

define( 'WC_SHIPDAY_FILE', __FILE__ );
if ( !defined( "SHIPDAY_PLUGIN_DIR" ) ) {
	define( "SHIPDAY_PLUGIN_DIR", plugin_dir_path( __FILE__ ) );
}

if ( !defined( "SHIPDAY_PLUGIN_URL" ) ) {
	define( "SHIPDAY_PLUGIN_URL", plugin_dir_url( __FILE__ ) );
}

function main() {
	if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {

		//WC_Settings_Tab_Shipday::init();
		WCFM_vendor_settings_shipday::init();
        Dokan_vendor_settings_shipday::init();
		//WooCommerce_REST_API::init();
		Shipday_Order_Management::init();
        Woo_Sync_Order::init();
		Notices::init();

        Classic_Datetime::init();
		Shipday_Woo_Delivery_Block::get_instance();
		Shipday_Woo_Delivery_Block_Storage::get_instance();
		Shipday_Woo_DateTime_Util::get_instance();
		Shipday_Menu_Settings::initialize();

	}
}

main();

?>
