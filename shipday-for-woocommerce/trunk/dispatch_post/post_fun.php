<?php
require_once dirname(__DIR__). '/functions/logger.php';

function post_orders(array $payloads) {
	foreach ($payloads as $api_key => $payload_array) {
		foreach ($payload_array as $payload){
			$response = post_order($payload, $api_key);
		}
	}
}

function post_order(array $payload, string $api_key) {
	if (strlen($api_key) < 3) return false;
	$response = curl_post_order($payload, $api_key);
	return $response;
}

function streams_post_order(array $payload, string $api_key) {
	$opts = array(
		'http' => array(
			'method' => 'POST',
			'header' => array(
				'Content-Type: application/json',
				'Authorization: Basic '.$api_key,
			),
			'content' => json_encode($payload)
		)
	);
	$context = stream_context_create($opts);
	file_get_contents(get_shipday_api_url(), false, $context);
	return $http_response_header;
}

function curl_post_order(array $payload, string $api_key) {
	$curl = curl_init();
	curl_setopt_array(
		$curl,
		array(
			CURLOPT_URL            => get_shipday_api_url(),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING       => '',
			CURLOPT_MAXREDIRS      => 10,
			CURLOPT_TIMEOUT        => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST  => 'POST',
			CURLOPT_POSTFIELDS     => json_encode($payload),
			CURLOPT_HTTPHEADER     => array(
				'Authorization: Basic '.$api_key,
				'Content-Type: application/json'
			)
		)
	);
	$response = curl_exec($curl);
	return curl_getinfo($curl);
}
