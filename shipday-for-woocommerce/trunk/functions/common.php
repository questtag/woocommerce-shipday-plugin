<?php

/** Global Variables */
$api_url = 'https://api.shipday.com/orders';
$debug_url = '';
$rest_key_install_url = 'https://api.shipday.com/woocommerce/install';
$shipday_debug_flag = false;

/** Functions */
function get_shipday_api_url(): string {
	global $api_url;
	return $api_url;
}

function get_shipday_debug_api_url(): string {
	global $debug_url;
	return $debug_url;
}

function get_shipday_rest_key_install_url() {
	global $rest_key_install_url;
	return $rest_key_install_url;
}

function shipday_handle_null($text) {
	return !isset($text) ? "" : $text;
}

function get_shipday_api_key() {
	$key = get_option('wc_settings_tab_shipday_api_key');
	return shipday_handle_null($key);
}

function get_shipday_sync_status() {
	$key = get_option('wc_settings_tab_shipday_sync');
	return shipday_handle_null($key) == 'yes';
}

function reset_shipday_sync_status() {
	update_option('wc_settings_tab_shipday_sync', 'no');
}

function get_shipday_order_manager() {
	$key = get_option('wc_settings_tab_shipday_order_manage');
	return shipday_handle_null($key);
}

?>
