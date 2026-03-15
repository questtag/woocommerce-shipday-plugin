<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Legacy payload helpers are used across the plugin codebase.
require_once dirname(__DIR__). '/functions/logger.php';
require_once dirname(__DIR__). '/functions/common.php';

$shipday_integration_url = 'https://integration.shipday.com';
$single_vendor_webhook_url = $shipday_integration_url.'/woocommerce/plugin/single-vendor/order';
$multi_vendor_webhook_url = $shipday_integration_url.'/woocommerce/plugin/multi-vendor/order';
$cancel_webhook_url = $shipday_integration_url.'/woocommerce/plugin/cancel/order';

function send_single_vendor_payload(array $payload)
{
    global $single_vendor_webhook_url;
    $response = shipday_http_post_payload($payload, $single_vendor_webhook_url);
    if ($response['http_code'] != 200) {
        shipday_logger('error', 'HTTP API(single-vendor) failed with code: '.$response['http_code'].' Response: '.json_encode($response));
    }
    return $response;
}

function send_multi_vendor_payload(array $payload)
{
    global $multi_vendor_webhook_url;
    $response = shipday_http_post_payload($payload, $multi_vendor_webhook_url);
    if ($response['http_code'] != 200) {
        shipday_logger('error', 'HTTP API(multi-vendor) failed with code: '.$response['http_code'].' Response: '.json_encode($response));
    }
    return $response;
}

function send_cancel_payload(array $payload)
{
    global $cancel_webhook_url;
    $response = shipday_http_post_payload($payload, $cancel_webhook_url);
    if ($response['http_code'] != 200) {
        shipday_logger('error', 'HTTP API(cancel-order) failed with code: '.$response['http_code'].' Response: '.json_encode($response));
    }
    return $response;
}

function shipday_http_post_payload(array $payload, $url) {
	return shipday_remote_post(
		$url,
		array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body'    => remove_emoji( wp_json_encode( $payload ) ),
		)
	);
}
