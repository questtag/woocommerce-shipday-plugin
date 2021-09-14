<?php

require_once dirname(__DIR__). '/functions/common.php';

class WooCommerce_REST_API {

	public static function init() {
		add_action('admin_notices', __CLASS__ . '::register_in_server');
	}

	public static function is_consumer_secret_valid($consumer_secret) {
		global $wpdb;
		$rest_api_key = $wpdb->get_row(
			$wpdb->prepare(
				"
					SELECT consumer_key, consumer_secret, permissions
					FROM {$wpdb->prefix}woocommerce_api_keys
					WHERE user_id = %d 
					  and consumer_secret = %s 
					  and permissions = 'read_write'
				",
				get_current_user_id(),
				$consumer_secret
			),
			ARRAY_A
		);
		return !is_null($rest_api_key) && $rest_api_key['consumer_secret'] == $consumer_secret ;
	}

	public static function register_in_server() {
		$key_size = 43;
		if ( get_option( 'shipday_registered_uuid' ) ) return;
		$consumer_key = trim(get_option('wc_settings_tab_shipday_rest_api_consumer_key'));
		$consumer_secret = trim(get_option('wc_settings_tab_shipday_rest_api_consumer_secret'));
		if (strlen($consumer_key) != $key_size ||
		    strlen($consumer_secret) != $key_size ||
		    !self::is_consumer_secret_valid($consumer_secret))
			return;
		$uuid = self::post_payload($consumer_key, $consumer_secret);
		if (is_null($uuid)) return;
		delete_option('wc_settings_tab_shipday_rest_api_consumer_key');
		update_option('shipday_registered_uuid', $uuid);
	}

	public static function post_payload($key, $secret) {
		$url              = get_rest_url();

		$curl             = curl_init();
		curl_setopt_array(
			$curl,
			array(
				CURLOPT_URL            => get_rest_key_install_url(),
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING       => '',
				CURLOPT_MAXREDIRS      => 10,
				CURLOPT_TIMEOUT        => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST  => 'POST',
				CURLOPT_POSTFIELDS     => '{
  						"url": "' . $url . '",
  						"consumer_key": "' . $key . '",
  						"consumer_secret": "' . $secret . '"
						}',
				CURLOPT_HTTPHEADER     => array(
					'Authorization: Basic '. get_shipday_api_key(),
					'Content-Type: application/json',
				),
			)
		);

		$response        = curl_exec($curl);
		if (is_null($response)) return null;
		$response_decoded = json_decode($response);
		if (!isset($response_decoded->success)) return null;
		$uuid            = $response_decoded->uuid;
		return $uuid;
	}
}