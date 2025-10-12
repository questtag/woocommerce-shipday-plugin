<?php
require_once dirname(__DIR__). '/functions/logger.php';
require_once dirname(__DIR__). '/functions/common.php';

$shipday_integration_url = 'https://integration.shipday.com';
$single_vendor_webhook_url = $shipday_integration_url.'/woocommerce/plugin/single-vendor/order';
$multi_vendor_webhook_url = $shipday_integration_url.'/woocommerce/plugin/multi-vendor/order';

function send_single_vendor_payload(array $payload)
{
    global $single_vendor_webhook_url;
    $response = shipday_curl_post_payload($payload, $single_vendor_webhook_url);
    if ($response['http_code'] != 200) {
        shipday_logger('error', 'Curl(single-vendor) failed with code: '.$response['http_code'].' Response: '.json_encode($response));
        $response = streams_post_payload($payload, $single_vendor_webhook_url);
    }
    if ($response['http_code'] != 200) {
        shipday_logger('error', 'Stream(single-vendor) failed with code: '.$response['http_code'].' Response: '.json_encode($response));
    }
    return $response;
}

function send_multi_vendor_payload(array $payload)
{
    global $multi_vendor_webhook_url;
    $response = shipday_curl_post_payload($payload, $multi_vendor_webhook_url);
    if ($response['http_code'] != 200) {
        shipday_logger('error', 'Curl(multi-vendor) failed with code: '.$response['http_code'].' Response: '.json_encode($response));
        $response = streams_post_payload($payload, $multi_vendor_webhook_url);
    }
    if ($response['http_code'] != 200) {
        shipday_logger('error', 'Stream(multi-vendor) failed with code: '.$response['http_code'].' Response: '.json_encode($response));
    }
    return $response;
}

function streams_post_payload(array $payload, $url) {
	$opts = array(
		'http' => array(
			'method' => 'POST',
			'header' => array(
				'Content-Type: application/json'
			),
			'content' => json_encode($payload)
		)
	);
	$context = stream_context_create($opts);
	file_get_contents($url, false, $context);
	return $http_response_header;
}

function shipday_curl_post_payload(array $payload, $url) {
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
			CURLOPT_POSTFIELDS     => remove_emoji(json_encode($payload)),
			CURLOPT_HTTPHEADER     => array(
				'Content-Type: application/json'
			)
		)
	);
	$response = curl_exec($curl);
	return curl_getinfo($curl);
}
