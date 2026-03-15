<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
require_once dirname(__DIR__). '/functions/logger.php';
require_once dirname(__DIR__). '/functions/common.php';

function shipday_post_orders(array $payloads) {
	global $shipday_debug_flag;
    $success = false;
    shipday_logger('INFO', json_encode($payloads));

    foreach ($payloads as $api_key => $payload_array) {
        $api_key = trim($api_key);
		foreach ($payload_array as $payload){
			$response = shipday_post_order($payload, $api_key, get_shipday_api_url());
            $success |= ($response['http_code'] == 200);
			if ($response['http_code'] != 200) {
                shipday_logger('error', 'Post failed for API key: '.$api_key);
            }
			if ($shipday_debug_flag == true) shipday_post_order(
                array(
                    'payload' => $payload,
                    'response' => $response
                ),
                $api_key, get_shipday_debug_api_url()
            );
		}
	}
    return $success;
}

function shipday_post_pickup_orders(array $payloads) {
    global $shipday_debug_flag;
    $success = false;
    shipday_logger('INFO', 'Pickup order payload: ' . json_encode($payloads));

    foreach ($payloads as $api_key => $payload_array) {
        $api_key = trim($api_key);
        foreach ($payload_array as $payload){
            $response = shipday_post_order($payload, $api_key, get_shipday_pickup_api_url());
            $success |= ($response['http_code'] == 200);
            if ($response['http_code'] != 200) {
                shipday_logger('error', 'Pickup order post failed for API key: '.$api_key);
            }
            if ($shipday_debug_flag == true) shipday_post_order(
                array(
                    'payload' => $payload,
                    'response' => $response
                ),
                $api_key, get_shipday_debug_api_url()
            );
        }
    }
    return $success;
}

function shipday_post_order(array $payload, string $api_key, $url) {
	if (strlen($api_key) < 3) return false;
	$response = shipday_http_post_order($payload, $api_key, $url);
    if ($response['http_code'] != 200) {
        shipday_logger('error', 'HTTP API request failed with code: '.$response['http_code'].' Response: '.json_encode($response));
    }
	return $response;
}

function shipday_http_post_order(array $payload, string $api_key, $url) {
	return shipday_remote_post(
		$url,
		array(
			'headers' => array(
				'Authorization' => 'Basic ' . $api_key,
				'Content-Type'  => 'application/json',
			),
			'body'    => remove_emoji( wp_json_encode( $payload ) ),
		)
	);
}
