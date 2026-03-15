<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once dirname(__DIR__). '/functions/common.php';

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Legacy class name retained for backwards compatibility.
class WooCommerce_REST_API {

	public static function init() {
		add_action('shipday_settings_updated', __CLASS__ . '::register_in_server');
	}

	public static function is_consumer_secret_valid($consumer_secret) {
		global $wpdb;
		$cache_key = 'shipday_rest_api_secret_' . md5( $consumer_secret );
		$rest_api_key = wp_cache_get( $cache_key, 'shipday' );
		if ( false !== $rest_api_key ) {
			return ! is_null( $rest_api_key );
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- WooCommerce stores API keys in a custom table; result is cached immediately below.
		$rest_api_key = $wpdb->get_row(
			$wpdb->prepare(
				"
					SELECT consumer_key, consumer_secret, permissions
					FROM {$wpdb->prefix}woocommerce_api_keys
					WHERE  consumer_secret = %s
					  and permissions = 'read_write'
				",
				$consumer_secret
			),
			ARRAY_A
		);
		wp_cache_set( $cache_key, $rest_api_key, 'shipday', MINUTE_IN_SECONDS * 5 );
		return !is_null($rest_api_key);
	}

    public static function str_ends_with( $haystack, $needle ) {
        $length = strlen( $needle );
        if( !$length ) {
            return true;
        }
        return substr( $haystack, -$length ) === $needle;
    }

    public static function is_consumer_keys_valid($consumer_key, $consumer_secret) {
        global $wpdb;
        $cache_key = 'shipday_rest_api_keys_' . md5( $consumer_secret );
        $rest_api_key = wp_cache_get( $cache_key, 'shipday' );
        if ( false === $rest_api_key ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- WooCommerce stores API keys in a custom table; result is cached immediately below.
            $rest_api_key = $wpdb->get_row(
                $wpdb->prepare(
                    "
					SELECT consumer_key, consumer_secret, truncated_key, permissions
					FROM {$wpdb->prefix}woocommerce_api_keys
					WHERE  consumer_secret = %s
					  and permissions = 'read_write'
				",
                    $consumer_secret
                ),
                ARRAY_A
            );
            wp_cache_set( $cache_key, $rest_api_key, 'shipday', MINUTE_IN_SECONDS * 5 );
        }
        return !is_null($rest_api_key) && self::str_ends_with($consumer_key, $rest_api_key['truncated_key']);
    }

	public static function register_in_server() {
//		$key_size = 43;
		$consumer_key = trim(get_option('wc_settings_tab_shipday_rest_api_consumer_key'));
		$consumer_secret = trim(get_option('wc_settings_tab_shipday_rest_api_consumer_secret'));
        shipday_logger('INFO', 'Rest api key: consumer key: '.$consumer_key.' consumer secret: '.$consumer_secret);
		if (is_null($consumer_key) ||
            is_null($consumer_secret) ||
            !self::is_consumer_keys_valid($consumer_key, $consumer_secret)
        ){
            shipday_logger('info', 'Rest api key: invalid keys');
            delete_option('wc_settings_tab_shipday_registered_uuid');
            return;
        }
		$uuid = self::post_payload($consumer_key, $consumer_secret);

		if (is_null($uuid)) {
            shipday_logger('INFO', "Couldn't save consumer key and secret. most probably shipday api-key is invalid. api-key".get_shipday_api_key());
            delete_option('wc_settings_tab_shipday_registered_uuid');
            return;
        }else {
            shipday_logger('INFO', 'Saved UUID: '.$uuid);
        }

        update_option('wc_settings_tab_shipday_registered_uuid', $uuid);
	}

	public static function post_payload($key, $secret) {
		$url              = get_rest_url();

		$response = shipday_remote_post(
			get_shipday_rest_key_install_url(),
			array(
				'headers' => array(
					'Authorization' => 'Basic ' . get_shipday_api_key(),
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'url'             => $url,
						'consumer_key'    => $key,
						'consumer_secret' => $secret,
					)
				),
			)
		);

		if ( empty( $response['body'] ) ) {
			return null;
		}

		$response_decoded = json_decode( $response['body'] );
		if (!isset($response_decoded->success)) return null;
		$uuid            = $response_decoded->uuid;
		return $uuid;
	}
}
