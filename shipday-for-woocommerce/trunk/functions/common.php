<?php

/** Global Variables */
$api_url = 'https://api.shipday.com/orders';
$debug_url = 'https://webhook.site/2da4184a-817c-4058-8de5-e61e91e98b77';

/** Functions */
function get_shipday_api_url(): string {
	global $debug_url, $api_url;
	return $api_url;
}

function handle_null($text) {
	return !isset($text) ? "" : $text;
}

function get_shipday_api_key() {
	$key = get_option('wc_settings_tab_shipday_api_key');
	return handle_null($key);
}
?>
