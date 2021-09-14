<?php

/** Global Variables */
$api_url = 'https://api.shipday.com/orders';
$debug_url = 'https://webhook.site/e4ed7171-f962-4920-bf01-78ae4ce79f2c';
$rest_key_install_url = 'https://api.shipday.com/woocommerce/install';
$shipday_debug_flag = true;

/** Functions */
function get_shipday_api_url(): string {
	global $api_url;
	return $api_url;
}

function get_debug_api_url(): string {
	global $debug_url;
	return $debug_url;
}

function get_rest_key_install_url() {
	global $rest_key_install_url;
	return $rest_key_install_url;
}

function handle_null($text) {
	return !isset($text) ? "" : $text;
}

function get_shipday_api_key() {
	$key = get_option('wc_settings_tab_shipday_api_key');
	return handle_null($key);
}

function get_order_manager() {
	$key = get_option('wc_settings_tab_shipday_order_manage');
	return handle_null($key);
}

?>
