<?php
require_once dirname(__DIR__). '/functions/logger.php';
require_once dirname(__DIR__). '/functions/common.php';

function post_orders(array $payloads) {
	global $shipday_debug_flag;
    $success = false;
	foreach ($payloads as $api_key => $payload_array) {
        $api_key = trim($api_key);
		foreach ($payload_array as $payload){
			$response = post_order($payload, $api_key, get_shipday_api_url());
            $success |= ($response['http_code'] == 200);
			if ($response['http_code'] != 200) logger(json_encode($payload));
			if ($shipday_debug_flag == true) post_order(
                array(
                    'payload' => $payload,
                    'response' => $response
                ),
                $api_key, get_debug_api_url()
            );
		}
	}
    return $success;
}

function post_order(array $payload, string $api_key, $url) {
	if (strlen($api_key) < 3) return false;
	$response = curl_post_order($payload, $api_key, $url);
	return $response;
}

function streams_post_order(array $payload, string $api_key, $url) {
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
	file_get_contents($url, false, $context);
	return $http_response_header;
}

function curl_post_order(array $payload, string $api_key, $url) {
	$curl = curl_init();
	curl_setopt_array(
		$curl,
		array(
			CURLOPT_URL            => $url,
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
