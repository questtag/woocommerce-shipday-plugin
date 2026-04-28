<?php

defined('ABSPATH') || exit;

class Shipday_Delivery_Fee {

	const META_KEY         = '_shipday_delivery_fee';
	const FEE_LABEL        = 'Delivery Fee';
	const FALLBACK_FEE     = 11.5;
	const AVAILABILITY_URL = 'https://api.shipday.com/on-demand/availability';
	const SESSION_KEY      = 'shipday_delivery_fee';
	const CACHE_TTL        = 300; // 5 minutes

	private static $hpos = false;

	public static function init() {
		add_action( 'before_woocommerce_init', function () {
			if ( class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) ) {
				self::$hpos = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
			}
		} );

		add_action( 'wp_enqueue_scripts',                                       [ __CLASS__, 'enqueue_checkout_scripts' ] );
		add_action( 'wp_ajax_shipday_get_delivery_fee',                         [ __CLASS__, 'ajax_get_fee' ] );
		add_action( 'wp_ajax_nopriv_shipday_get_delivery_fee',                  [ __CLASS__, 'ajax_get_fee' ] );
		add_action( 'woocommerce_cart_calculate_fees',                          [ __CLASS__, 'add_checkout_fee' ],  10 );
		add_action( 'woocommerce_checkout_update_order_meta',                   [ __CLASS__, 'save_fee_classic' ],  10 );
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', [ __CLASS__, 'save_fee_block' ],    20, 2 );
		add_action( 'woocommerce_admin_order_data_after_shipping_address',      [ __CLASS__, 'admin_display' ],     20 );
	}

	private static function is_enabled(): bool {
		return get_option( 'shipday_enable_delivery_fee', 'no' ) === 'yes';
	}

	// Returns the configured pickup address, falling back to the WooCommerce store address.
	private static function get_pickup_address(): string {
		$saved = get_option( 'shipday_pickup_address', '' );
		if ( ! empty( trim( $saved ) ) ) {
			return trim( $saved );
		}
		$parts = array_filter( [
			get_option( 'woocommerce_store_address' ),
			get_option( 'woocommerce_store_address_2' ),
			get_option( 'woocommerce_store_city' ),
			get_option( 'woocommerce_store_postcode' ),
			WC()->countries->get_base_country(),
		] );
		return implode( ', ', $parts );
	}

	// Reads the current customer's delivery address from WC()->customer.
	// WC populates this from the posted form data before woocommerce_cart_calculate_fees fires.
	private static function get_customer_address(): string {
		$customer = WC()->customer;
		if ( ! $customer ) {
			return '';
		}
		$parts = array_filter( [
			$customer->get_shipping_address_1(),
			$customer->get_shipping_address_2(),
			$customer->get_shipping_city(),
			$customer->get_shipping_state(),
			$customer->get_shipping_postcode(),
			$customer->get_shipping_country(),
		] );
		if ( empty( $parts ) ) {
			$parts = array_filter( [
				$customer->get_billing_address_1(),
				$customer->get_billing_address_2(),
				$customer->get_billing_city(),
				$customer->get_billing_state(),
				$customer->get_billing_postcode(),
				$customer->get_billing_country(),
			] );
		}
		return implode( ', ', $parts );
	}

	// Calls the Shipday availability API and returns ['fee' => float, 'raw' => array|null].
	// Results are cached in a transient for CACHE_TTL seconds.
	private static function fetch_from_api( string $pickup, string $delivery, array $extra = [] ): array {
		$cache_key = 'shipday_fee_' . md5( $pickup . '|' . $delivery . '|' . wp_json_encode( $extra ) );
		$cached    = get_transient( $cache_key );
		if ( $cached !== false ) {
			return [ 'fee' => (float) $cached, 'raw' => null ];
		}

		$api_key = get_shipday_api_key();
		if ( empty( $api_key ) ) {
			shipday_logger( 'error', '[DeliveryFee] API key missing — using fallback fee.' );
			return [ 'fee' => self::FALLBACK_FEE, 'raw' => null ];
		}

		$body = array_merge( [ 'pickupAddress' => $pickup, 'deliveryAddress' => $delivery ], $extra );
		shipday_logger( 'info', '[DeliveryFee] Availability API request: ' . wp_json_encode( $body ) );

		$response = wp_safe_remote_post( self::AVAILABILITY_URL, [
			'headers' => [
				'Authorization' => 'Basic ' . $api_key,
				'Content-Type'  => 'application/json',
			],
			'body'    => wp_json_encode( $body ),
			'timeout' => 10,
		] );

		if ( is_wp_error( $response ) ) {
			shipday_logger( 'error', '[DeliveryFee] API request error: ' . $response->get_error_message() . ' — using fallback fee.' );
			return [ 'fee' => self::FALLBACK_FEE, 'raw' => null ];
		}

		$http_code = wp_remote_retrieve_response_code( $response );
		$raw_body  = wp_remote_retrieve_body( $response );

		if ( $http_code !== 200 ) {
			shipday_logger( 'error', '[DeliveryFee] API returned HTTP ' . $http_code . ': ' . $raw_body . ' — using fallback fee.' );
			return [ 'fee' => self::FALLBACK_FEE, 'raw' => null ];
		}

		$parsed = json_decode( $raw_body, true );
		shipday_logger( 'info', '[DeliveryFee] Availability API response: ' . $raw_body );

		if ( ! is_array( $parsed ) || empty( $parsed[0]['fee'] ) ) {
			shipday_logger( 'info', '[DeliveryFee] No valid fee in response — using fallback fee.' );
			return [ 'fee' => self::FALLBACK_FEE, 'raw' => $parsed ];
		}

		$fee = (float) $parsed[0]['fee'];
		set_transient( $cache_key, $fee, self::CACHE_TTL );
		shipday_logger( 'info', '[DeliveryFee] Fee resolved: ' . $fee );
		return [ 'fee' => $fee, 'raw' => $parsed ];
	}

	// Determines the fee to charge.
	// Priority: WC session (set by JS AJAX call) → server-side API call via WC()->customer.
	// Returns 0 when no address has been entered yet (fee should not show).
	private static function resolve_fee(): float {
		// 1. Session value set by the JS AJAX call (includes date/time if provided).
		if ( WC()->session ) {
			$session_fee = WC()->session->get( self::SESSION_KEY );
			if ( $session_fee !== null ) {
				return (float) $session_fee;
			}
		}

		// 2. Server-side fallback: compute from WC()->customer address directly.
		//    WC updates WC()->customer from the posted form data before this hook fires,
		//    so this always reflects the address the customer just entered.
		$delivery = self::get_customer_address();
		if ( empty( $delivery ) ) {
			return 0; // No address yet — do not show the fee.
		}

		$pickup = self::get_pickup_address();
		if ( empty( $pickup ) ) {
			return self::FALLBACK_FEE;
		}

		$result = self::fetch_from_api( $pickup, $delivery );
		return $result['fee'];
	}

	public static function enqueue_checkout_scripts() {
		if ( ! is_checkout() || is_wc_endpoint_url( 'order-pay' ) || is_wc_endpoint_url( 'order-received' ) ) {
			return;
		}
		if ( ! self::is_enabled() ) {
			return;
		}
		wp_enqueue_script(
			'shipday-delivery-fee',
			plugin_dir_url( WC_SHIPDAY_FILE ) . 'shipday-delivery-fee/checkout-fee.js',
			[ 'jquery' ],
			'1.0.0',
			true
		);
		wp_localize_script( 'shipday-delivery-fee', 'shipdayFeeData', [
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce( 'shipday_delivery_fee_nonce' ),
			'pickupAddress' => self::get_pickup_address(),
		] );

		// Inline CSS — mirrors WooCommerce's own loading opacity on totals rows.
		wp_register_style( 'shipday-delivery-fee-css', false ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_enqueue_style( 'shipday-delivery-fee-css' );
		wp_add_inline_style(
			'shipday-delivery-fee-css',
			'.woocommerce-checkout-review-order-table tr.fee.shipday-calculating td,
			 .woocommerce-checkout-review-order-table tr.fee.shipday-calculating th { opacity: 0.3; transition: opacity 200ms ease; }'
		);
	}

	// WP AJAX handler: called by JS when the customer changes their address or date/time.
	// Calls the API, sets the WC session for the next cart calculation, and returns
	// the full request + response for browser console logging.
	public static function ajax_get_fee() {
		check_ajax_referer( 'shipday_delivery_fee_nonce', 'nonce' );

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		$delivery_address = isset( $_POST['deliveryAddress'] ) ? sanitize_text_field( wp_unslash( $_POST['deliveryAddress'] ) ) : '';
		$delivery_date    = isset( $_POST['deliveryDate'] )    ? sanitize_text_field( wp_unslash( $_POST['deliveryDate'] ) )    : '';
		$delivery_time    = isset( $_POST['deliveryTime'] )    ? sanitize_text_field( wp_unslash( $_POST['deliveryTime'] ) )    : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$pickup_address = self::get_pickup_address();
		$api_key        = get_shipday_api_key();

		// Build optional extra fields for the API body.
		$extra = [];
		if ( ! empty( $delivery_date ) && ! empty( $delivery_time ) ) {
			// Time slot format: "09:00 AM - 10:00 AM" — use the start of the slot.
			$time_parts           = explode( ' - ', $delivery_time );
			$extra['deliveryTime'] = $delivery_date . ' ' . trim( $time_parts[0] );
		}

		$request_body = array_merge(
			[ 'pickupAddress' => $pickup_address, 'deliveryAddress' => $delivery_address ],
			$extra
		);

		$request_log = [
			'url'     => self::AVAILABILITY_URL,
			'headers' => [
				'Authorization' => 'Basic ' . ( ! empty( $api_key ) ? '***' : '(missing)' ),
				'Content-Type'  => 'application/json',
			],
			'body'    => $request_body,
		];

		$fee           = self::FALLBACK_FEE;
		$response_body = null;

		if ( ! empty( $delivery_address ) ) {
			$result        = self::fetch_from_api( $pickup_address, $delivery_address, $extra );
			$fee           = $result['fee'];
			$response_body = $result['raw'];
		}

		// Persist in WC session so the next woocommerce_cart_calculate_fees call picks it up.
		if ( WC()->session ) {
			WC()->session->set( self::SESSION_KEY, $fee );
		}

		wp_send_json_success( [
			'request'  => $request_log,
			'response' => $response_body,
			'fee'      => $fee,
		] );
	}

	public static function add_checkout_fee( $cart ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}
		if ( ! self::is_enabled() ) {
			return;
		}
		if ( ! is_checkout() && ! self::is_store_api_request() ) {
			return;
		}

		$fee = self::resolve_fee();
		if ( $fee <= 0 ) {
			return;
		}

		$cart->add_fee(
			__( self::FEE_LABEL, 'shipday-for-woocommerce' ),
			$fee,
			false // not taxable
		);
	}

	private static function is_store_api_request(): bool {
		if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
			return false;
		}
		$route = isset( $GLOBALS['wp']->query_vars['rest_route'] )
			? (string) $GLOBALS['wp']->query_vars['rest_route']
			: '';
		return strpos( $route, '/wc/store' ) !== false;
	}

	public static function save_fee_classic( $order_id ) {
		if ( ! self::is_enabled() ) {
			return;
		}
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		self::persist_meta( $order, $order_id );
	}

	public static function save_fee_block( $order, $request ) {
		if ( ! self::is_enabled() ) {
			return;
		}
		self::persist_meta( $order, $order->get_id() );
	}

	// Reads the fee from the order's own fee line items — avoids re-calling the API at save time.
	private static function resolve_saved_fee( $order ): float {
		$label = __( self::FEE_LABEL, 'shipday-for-woocommerce' );
		foreach ( $order->get_fees() as $fee_item ) {
			if ( $fee_item->get_name() === $label ) {
				return (float) $fee_item->get_total();
			}
		}
		return self::FALLBACK_FEE;
	}

	private static function persist_meta( $order, $order_id ) {
		$amount = self::resolve_saved_fee( $order );
		if ( self::$hpos ) {
			$order->update_meta_data( self::META_KEY, $amount );
			$order->save();
		} else {
			update_post_meta( $order_id, self::META_KEY, $amount );
		}
	}

	public static function admin_display( $order ) {
		$fee = self::$hpos
			? $order->get_meta( self::META_KEY, true )
			: get_post_meta( $order->get_id(), self::META_KEY, true );

		if ( $fee !== null && $fee !== '' ) {
			echo '<p><strong>' . esc_html__( 'Delivery Fee', 'shipday-for-woocommerce' ) . ':</strong> '
				. wp_kses_post( wc_price( (float) $fee ) ) . '</p>';
		}
	}
}
