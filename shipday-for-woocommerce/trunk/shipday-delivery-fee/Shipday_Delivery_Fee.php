<?php

defined('ABSPATH') || exit;

class Shipday_Delivery_Fee {

	const META_KEY         = '_shipday_delivery_fee';
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
		add_action( 'woocommerce_after_checkout_shipping_form',                 [ __CLASS__, 'render_checkout_map_container' ], 20 );
		add_action( 'wp_ajax_shipday_get_delivery_fee',                         [ __CLASS__, 'ajax_get_fee' ] );
		add_action( 'wp_ajax_nopriv_shipday_get_delivery_fee',                  [ __CLASS__, 'ajax_get_fee' ] );
		add_action( 'wp_ajax_shipday_log_checkout_map',                         [ __CLASS__, 'ajax_log_checkout_map' ] );
		add_action( 'wp_ajax_nopriv_shipday_log_checkout_map',                  [ __CLASS__, 'ajax_log_checkout_map' ] );
		add_action( 'woocommerce_cart_calculate_fees',                          [ __CLASS__, 'add_checkout_fee' ],  10 );
		add_action( 'woocommerce_checkout_update_order_meta',                   [ __CLASS__, 'save_fee_classic' ],  10 );
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', [ __CLASS__, 'save_fee_block' ],    20, 2 );
		add_action( 'woocommerce_admin_order_data_after_shipping_address',      [ __CLASS__, 'admin_display' ],     20 );
	}

	private static function is_enabled(): bool {
		return get_option( 'shipday_enable_delivery_fee', 'no' ) === 'yes';
	}

	private static function is_maps_enabled(): bool {
		$maps_api_key = get_option( 'shipday_google_maps_api_key', '' );
		return ! empty( trim( $maps_api_key ) );
	}

	private static function get_fee_label(): string {
		return __( 'Delivery Fee', 'shipday-for-woocommerce' );
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
	// Returns fee 0 on any error so the fee line is hidden rather than showing a hardcoded fallback.
	private static function fetch_from_api( string $pickup, string $delivery, array $extra = [] ): array {
		$cache_key = 'shipday_fee_' . md5( $pickup . '|' . $delivery . '|' . wp_json_encode( $extra ) );
		$cached    = get_transient( $cache_key );
		if ( $cached !== false ) {
			return [ 'fee' => (float) $cached, 'raw' => null ];
		}

		$api_key = get_shipday_api_key();
		if ( empty( $api_key ) ) {
			shipday_logger( 'error', '[DeliveryFee] API key missing — hiding delivery fee.' );
			return [ 'fee' => 0, 'raw' => null ];
		}

		$body = array_merge( [ 'pickupAddress' => $pickup, 'deliveryAddress' => $delivery ], $extra );

		$response = wp_safe_remote_post( self::AVAILABILITY_URL, [
			'headers' => [
				'Authorization' => 'Basic ' . $api_key,
				'Content-Type'  => 'application/json',
			],
			'body'    => wp_json_encode( $body ),
			'timeout' => 10,
		] );

		if ( is_wp_error( $response ) ) {
			shipday_logger( 'error', '[DeliveryFee] API request error: ' . $response->get_error_message() . ' — hiding delivery fee.' );
			return [ 'fee' => 0, 'raw' => null ];
		}

		$http_code = wp_remote_retrieve_response_code( $response );
		$raw_body  = wp_remote_retrieve_body( $response );

		if ( $http_code !== 200 ) {
			shipday_logger( 'error', '[DeliveryFee] API returned HTTP ' . $http_code . ': ' . $raw_body . ' — hiding delivery fee.' );
			return [ 'fee' => 0, 'raw' => null ];
		}

		$parsed = json_decode( $raw_body, true );

		if ( ! is_array( $parsed ) ) {
			shipday_logger( 'info', '[DeliveryFee] Delivery address: ' . $delivery . ' | No delivery fee' );
			return [ 'fee' => 0, 'raw' => $parsed ];
		}

		$valid_rows = array_values(
			array_filter(
				$parsed,
				static function ( $item ) {
					return is_array( $item ) && array_key_exists( 'error', $item ) && false === $item['error'];
				}
			)
		);

		if ( empty( $valid_rows ) || ! isset( $valid_rows[0]['fee'] ) || ! is_numeric( $valid_rows[0]['fee'] ) ) {
			shipday_logger( 'info', '[DeliveryFee] Delivery address: ' . $delivery . ' | No delivery fee' );
			return [ 'fee' => 0, 'raw' => $parsed ];
		}

		$fee = (float) $valid_rows[0]['fee'];
		set_transient( $cache_key, $fee, self::CACHE_TTL );
		shipday_logger( 'info', '[DeliveryFee] Delivery address: ' . $delivery . ' | Resolved delivery fee: ' . $fee );
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
			return 0;
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

			$maps_api_key = get_option( 'shipday_google_maps_api_key', '' );
			$maps_enabled = self::is_maps_enabled();

			$checkout_script_dependencies = [ 'jquery' ];
		if ( wp_script_is( 'wp-data', 'registered' ) ) {
			$checkout_script_dependencies[] = 'wp-data';
		}
		if ( wp_script_is( 'wc-blocks-data-store', 'registered' ) ) {
			$checkout_script_dependencies[] = 'wc-blocks-data-store';
		}

		wp_enqueue_script(
			'shipday-delivery-fee',
			plugin_dir_url( WC_SHIPDAY_FILE ) . 'shipday-delivery-fee/checkout-fee.js',
			$checkout_script_dependencies,
			'1.0.5',
			true
		);
		wp_localize_script( 'shipday-delivery-fee', 'shipdayFeeData', [
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce( 'shipday_delivery_fee_nonce' ),
			'pickupAddress' => self::get_pickup_address(),
			'mapsEnabled'   => $maps_enabled,
		] );

			if ( $maps_enabled ) {
				wp_enqueue_script(
					'shipday-google-maps',
					'https://maps.googleapis.com/maps/api/js?key=' . rawurlencode( trim( $maps_api_key ) ) . '&callback=shipdayInitMap&loading=async',
					[ 'shipday-delivery-fee' ],
					'1.0.0',
					true
				);
			}

		// Inline CSS — fee row loading animation + responsive map container.
		wp_register_style( 'shipday-delivery-fee-css', false ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_enqueue_style( 'shipday-delivery-fee-css' );
		wp_add_inline_style(
			'shipday-delivery-fee-css',
			'.woocommerce-checkout-review-order-table tr.fee.shipday-calculating td,
			 .woocommerce-checkout-review-order-table tr.fee.shipday-calculating th { opacity: 0.3; transition: opacity 200ms ease; }
			 .woocommerce-checkout-review-order-table tr.shipping,
			 .woocommerce-checkout-review-order-table tr.woocommerce-shipping-totals,
			 .woocommerce-shipping-methods,
			 #shipping_method,
			 fieldset#wc-block-checkout__shipping-option,
			 fieldset#shipping-option,
			 .wc-block-checkout__shipping-option,
			 .wp-block-woocommerce-checkout-shipping-methods-block,
			 .wp-block-woocommerce-checkout-order-summary-shipping-block,
			 .wc-block-components-totals-shipping,
			 .wc-block-components-shipping-rates-control,
			 .wc-block-components-radio-control[aria-label=\"Shipping options\"] {
			     display: none !important;
			 }
			 #shipday-delivery-map-wrapper {
			     display: none;
			     margin-top: 16px;
			 }
			 #shipday-delivery-map-wrapper .shipday-delivery-map__title {
			     margin-bottom: 10px;
			     font-weight: 600;
			 }
			 #shipday-delivery-map {
			     width: 100%;
			     height: 320px;
			     border-radius: 6px;
			     overflow: hidden;
			     box-sizing: border-box;
			     background: #f6f7f7;
			     border: 1px solid #e3e4eb;
			 }
			 @media (max-width: 768px) {
			     #shipday-delivery-map { height: 220px; }
			 }'
		);
		}

		public static function render_checkout_map_container( $checkout = null ) {
			if ( ! is_checkout() || is_wc_endpoint_url( 'order-pay' ) || is_wc_endpoint_url( 'order-received' ) ) {
				return;
			}

			if ( ! self::is_enabled() || ! self::is_maps_enabled() ) {
				return;
			}

			?>
			<div id="shipday-delivery-map-wrapper" aria-live="polite">
			<div class="shipday-delivery-map__title"><?php esc_html_e( 'Delivery Route', 'shipday-for-woocommerce' ); ?></div>
			<div id="shipday-delivery-map"></div>
		</div>
		<?php
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

		$fee           = 0;
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

	public static function ajax_log_checkout_map() {
		check_ajax_referer( 'shipday_delivery_fee_nonce', 'nonce' );

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		$level   = isset( $_POST['level'] ) ? strtolower( sanitize_text_field( wp_unslash( $_POST['level'] ) ) ) : 'info';
		$message = isset( $_POST['message'] ) ? sanitize_text_field( wp_unslash( $_POST['message'] ) ) : '';
		$context = isset( $_POST['context'] ) ? sanitize_textarea_field( wp_unslash( $_POST['context'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( ! in_array( $level, [ 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency' ], true ) ) {
			$level = 'info';
		}

		if ( $message === '' ) {
			wp_send_json_success();
		}

		$payload = '[DeliveryFee][Map][Frontend] ' . $message;
		if ( $context !== '' ) {
			$payload .= ' | ' . $context;
		}

		shipday_logger( $level, $payload );
		wp_send_json_success();
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
				self::get_fee_label(),
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
		$label = self::get_fee_label();
		foreach ( $order->get_fees() as $fee_item ) {
			if ( $fee_item->get_name() === $label ) {
				return (float) $fee_item->get_total();
			}
		}
		return 0;
	}

	private static function persist_meta( $order, $order_id ) {
		$amount = self::resolve_saved_fee( $order );
		if ( $amount <= 0 ) {
			return;
		}
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
