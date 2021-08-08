<?php
/*
Plugin Name: Shipday Integration for WooCommerce
Plugin URI: https://www.shipday.com/woocommerce
Version: 0.4.1
Description:Allows you to add shipday API configuration and create connection with shipday. Then anyone places any order to the WooCommerce site it should also appear on your Shipday dispatch dashboard. Local Delivery App for WooCommerce by Shipday is compatible with Dokan Multivendor plugin, WCFM Market Place,Order Delivery Date For Woocommerce and Woo Delivery.
Author URI: https://www.shipday.com/
Text Domain: woocommerce-shipday
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**************************************************
	To check if WooCommerce plugin is actived
*/
require_once ABSPATH . 'wp-admin/includes/plugin.php';
if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
	class WC_Settings_Tab_Shipday {

		/*
		init function
		*/
		public static function init() {

			add_filter( 'woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50 );
			add_action( 'woocommerce_settings_tabs_settings_tab_shipday', __CLASS__ . '::settings_tab' );
			add_action( 'woocommerce_update_options_settings_tab_shipday', __CLASS__ . '::update_settings' );
			add_action( 'admin_enqueue_scripts', __CLASS__ . '::register_wcsscript' ); // admin enqueue for registered js file.

		}

		/*
		Function for Register custom js file.
		*/
		public static function register_wcsscript() {
			wp_enqueue_script( 'custom-wcsscript', plugin_dir_url( __FILE__ ) . 'js/wc-shipday-script.js', array(), '1.0' );
			wp_enqueue_script( 'custom-wcsscript' );
		}

		/*
		Add a new shipday tab
		*/
		public static function add_settings_tab( $settings_tabs ) {

			$settings_tabs['settings_tab_shipday'] = __( 'Shipday', 'woocommerce-settings-tab-shipday' );
			return $settings_tabs;

		}

		/*
		Uses the woocommerce admin fields API
		*/
		public static function settings_tab() {

			woocommerce_admin_fields( self::get_settings() );
		}

		/*
		Uses the woocommerce options api to save settings.
		*/
		public static function update_settings() {

			woocommerce_update_options( self::get_settings() );

			// self::sd_create_webhook( $_POST );
		}

		/**********************************************
		Function to create & update woocommerce webhook
		 **********************************************/


		/*
		Get all the settings for this shipday tab
		*/
		public static function get_settings() {

			$settings = array(
				'section_title'       => array(
					'name' => __( 'Shipday Settings', 'woocommerce-settings-tab-shipday' ),
					'type' => 'title',
					'desc' => 'Login to your Shipday account to get the API key. It’s in the following, My Account > Profile > Api key',
					'id'   => 'wc_settings_tab_shipday_section_title',
				),
				'shipday_key'         => array(
					'name'              => __( 'Shipday API Key', 'woocommerce-settings-tab-shipday' ),
					'type'              => 'text',
					'custom_attributes' => array( 'required' => 'required' ),
					'id'                => 'wc_settings_tab_shipday_shipday_key',
				),
				'shipday_location'    => array(
					'name'    => __( 'Pick up location settings', 'woocommerce-settings-tab-shipday' ),
					'type'    => 'select',
					'std'     => 'Select Shipday Location',
					'options' => array(
						'single_pickup'   => __( 'Single pick up location (single vendor)' ),
						'multiple_pickup' => __( 'Multiple pick up location (multi-vendor)' ),
					),
					'id'      => 'wc_settings_tab_shipday_location',
				),
			/* 	'shipday_vendor_type' => array(
					'name'    => __( 'Select Vendor Type', 'woocommerce-settings-tab-shipday' ),
					'type'    => 'select',
					'std'     => 'Select Vendor Type',
					'options' => array(
						'dokan' => __( 'Dokan' ),
						'wcfm'  => __( 'WCFM' ),
					),
					'id'      => 'wc_settings_tab_shipday_vendor_type',
					'css'     => 'display:none;',
				), */
				/*
				'business_name' => array(
					'name' => __( 'Business Name', 'woocommerce-settings-tab-shipday' ),
					'type' => 'text',
					'id'   => 'wc_settings_tab_shipday_business_name',
					'class' => 'wcs_single_business_data'
				),
				'pickup_address' => array(
					'name' => __( 'Pickup Address', 'woocommerce-settings-tab-shipday' ),
					'type' => 'text',
					'id'   => 'wc_settings_tab_shipday_pickup_address',
					'class' => 'wcs_single_business_pickdata'
				),
				'pickup_phone' => array(
					'name' => __( 'Pickup Phone Number', 'woocommerce-settings-tab-shipday' ),
					'type' => 'tel',
					'css'  => 'width:400px;',
					'id'   => 'wc_settings_tab_shipday_pickup_phone',
					'class' => 'wcs_single_business_data'
				),*/
				'section_end'         => array(
					'type' => 'sectionend',
					'id'   => 'wc_settings_tab_shipday_section_end',
				),
			);
			return apply_filters( 'wc_settings_tab_shipday_settings', $settings );

		}

	}
	WC_Settings_Tab_Shipday::init();

	/*
	woocommerce delete webhook callback
	*/

	function action_woocommerce_delete_webhook( $id, $webhook ) {

		if ( current_user_can( 'manage_woocommerce' ) ) {

			if ( get_option( 'webhook_id' ) == $id ) {

				update_option( 'webhook_id', '' );

			}
		}
	}

	add_action( 'woocommerce_webhook_deleted', 'action_woocommerce_delete_webhook', 10, 2 );

	/*
	Function for get Dokan vendor data.
	*/
	if ( is_plugin_active( 'dokan-lite/dokan.php' ) ) {
		function get_dokanvendor_data( $get_items, $item_meta_data ) {
			foreach ( $get_items as $_get_items ) {
				$itemid = $_get_items->get_id();
				foreach ( $item_meta_data as $meta_data_item ) {
					$vendor_key = $meta_data_item->get_data();
					if ( $vendor_key['key'] && $vendor_key['key'] == '_dokan_vendor_id' ) {
						$vendorid     = $meta_data_item->value;
						$usermetadata = get_user_meta( $vendorid, 'dokan_profile_settings', true );
						$userdata     = get_userdata( $vendorid );
						/* update dokan vendor information in order item meta */
						wc_update_order_item_meta( $itemid, '_vendor_id', $vendorid );
					}
				}
			}
		}
	}



	/*
	Woocommerce function for remove extra vendor data on order received page.
	*/
	add_filter( 'woocommerce_order_item_get_formatted_meta_data', 'unset_specific_order_item_meta_data', 10, 2 );
	function unset_specific_order_item_meta_data( $formatted_meta, $item ) {
		foreach ( $formatted_meta as $key => $meta ) {
			if ( in_array( $meta->key, array( 'vendor_phone', 'vendor_email', 'vendor_street', 'vendor_city', 'vendor_zip', 'vendor_country' ) ) ) {
				unset( $formatted_meta[ $key ] );
			}
		}
		return $formatted_meta;
	}




	/*
	Check if order-delivery-date-for-woocommerce is activated.
	*/
	if ( is_plugin_active( 'order-delivery-date-for-woocommerce/order_delivery_date.php' ) ) {


		function odd_order_delivery_date( $response, $object, $request ) {

			if ( empty( $response->data ) ) {
				return $response;
			}

			$images                                 = array();
			$delivery_date                          = date_create( get_post_meta( $response->data['id'], 'Delivery Date', true ) );
			$final_date                             = date_format( $delivery_date, 'Y-m-d' );
			$response->data['delivery_date']        = $final_date;
			$response->data['expectedDeliveryDate'] = $final_date;

			return $response;
		}

		add_filter( 'woocommerce_rest_prepare_shop_order_object', 'odd_order_delivery_date', 10, 3 );

	}


	/*
	Check if woo-delivery is activated.
	*/
	if ( is_plugin_active( 'woo-delivery/coderockz-woo-delivery.php' ) ) {

		function wd_woo_delivery( $response, $object, $request ) {

			if ( empty( $response->data ) ) {
				return $response;
			}

			$images = array();
			if ( $response->data['delivery_type'] == 'pickup' ) {
				$get_wd_date                     = date_create( get_post_meta( $response->data['id'], 'pickup_date', true ) );
				$wd_date                         = date_format( $get_wd_date, 'Y-m-d' );
				$response->data['delivery_date'] = $wd_date;
			} else {
				$get_wd_date                     = date_create( get_post_meta( $response->data['id'], 'delivery_date', true ) );
				$wd_date                         = date_format( $get_wd_date, 'Y-m-d' );
				$response->data['delivery_date'] = $wd_date;

			}

			$response->data['expectedDeliveryDate'] = $wd_date;

			return $response;
		}

		add_filter( 'woocommerce_rest_prepare_shop_order_object', 'wd_woo_delivery', 10, 3 );

	}
	$get_api = ( ! empty( get_option( 'wc_settings_tab_shipday_shipday_key' ) ) ? 'Authorization: Basic ' . get_option( 'wc_settings_tab_shipday_shipday_key' ) : '' );
	if( empty( $get_api ) ) {
		function general_admin_notice(){
		
			/* if ( $pagenow == 'options-general.php' ) { */
				 echo "<div class='notice notice-warning is-dismissible'>
					 <p>It seems REST API isn't enabled on your website. Shipday integration requires it to operate properly.</p>
				 </div>";
			/* } */
		}
		add_action('admin_notices', 'general_admin_notice');
	}
	add_action( 'woocommerce_thankyou', 'custom_content_thankyou', 10, 1 );
	function custom_content_thankyou( $order_id ) {
		$order   = new WC_Order( $order_id );
		$get_api = ( ! empty( get_option( 'wc_settings_tab_shipday_shipday_key' ) ) ? 'Authorization: Basic ' . get_option( 'wc_settings_tab_shipday_shipday_key' ) : '' );
		$get_site_url = get_site_url();
		$customer_billing_address_1 = ( ! empty( $order->billing_address_1 ) ? $order->billing_address_1 . ',' : '' );
		$customer_billing_address_2 = ( ! empty( $order->billing_address_2 ) ? $order->billing_address_2 . ',' : '' );
		$customer_billing_city      = ( ! empty( $order->billing_city ) ? $order->billing_city . ',' : '' );
		$customer_billing_state     = ( ! empty( $order->billing_state ) ? $order->billing_state . ',' : '' );
		$customer_billing_country   = ( ! empty( $order->billing_country ) ? $order->billing_country : '' );
		$customer_complete_address  = $customer_billing_address_1 . $customer_billing_address_2 . $customer_billing_city . $customer_billing_state . $customer_billing_country;
		$customer_billing_phone     = ( ! empty( $order->billing_phone ) ? $order->billing_phone : '' );

		$woocommerce_store_address   = ( ! empty( get_option( 'woocommerce_store_address' ) ) ? get_option( 'woocommerce_store_address' ) : '' );
		$woocommerce_store_address_2 = ( ! empty( get_option( 'woocommerce_store_address_2' ) ) ? get_option( 'woocommerce_store_address_2' ) : '' );
		$woocommerce_default_country = ( ! empty( get_option( 'woocommerce_default_country' ) ) ? get_option( 'woocommerce_default_country' ) : '' );
		$woocommerce_store_city      = ( ! empty( get_option( 'woocommerce_store_city' ) ) ? get_option( 'woocommerce_store_city' ) : '' );
		$default_store_address       = $woocommerce_store_address . $woocommerce_store_address_2 . $woocommerce_default_country . $woocommerce_store_city;

		$customer_fullname = $order->billing_first_name . ' ' . $order->billing_last_name;
		$order_num         = $order->get_order_number();

		$customer_order       = wc_get_order( $order_id );
		$customer_order_items = $customer_order->get_items();
		foreach ( $customer_order_items as $item ) {
			$product_name         = $item['name'];
			$product_id           = $item['product_id'];
			$product_variation_id = $item['variation_id'];
			$quantity             = $item['quantity'];
			$product_price        = $item['total'] / $quantity;

			$customer_order_name[]    = $product_name;
			$customer_product_price[] = $product_price;
			$customer_quantity[]      = $quantity;

			$customer_items[] = "{'name':'" . $product_name . "','unitPrice':'" . $product_price . "','quantity':'" . $quantity . "'}";

		}
		 $order_item_encode     = json_encode( $customer_items );
		 $orderItem             = str_replace( '"', '', $order_item_encode );
		$total_amt              = $order->total;
		$customer_billing_email = ( ! empty( $order->billing_email ) ? $order->billing_email : '' );
		$order_shipping_tax     = $order->order_shipping_tax;

		if ( ! empty( get_post_meta( $order_id, 'Delivery Date', true ) ) ) {
			$del_date = get_post_meta( $order_id, 'Delivery Date' );
		}
		if ( ! empty( get_post_meta( $order_id, 'delivery_date', true ) ) ) {
			$del_date = get_post_meta( $order_id, 'delivery_date' );
		}
		if ( ! empty( get_post_meta( $order_id, 'pickup_date', true ) ) ) {
			$del_date = get_post_meta( $order_id, 'pickup_date' );
		}
		$del_date = ( ! empty( $del_date[0] ) ? $del_date[0] : '' );

							/* WCFM Start */
		if ( is_plugin_active( 'wc-multivendor-marketplace/wc-multivendor-marketplace.php' ) ) {
			// Get Product ID
			$order = wc_get_order( $order_id );
			$items = $order->get_items();

			/*
			  echo ' PID ';
			echo "<pre>";
			print_r($items);
			echo "</pre>"; */
			$curl                 = curl_init();
			$customer_order_items = $order->get_items();
			$hold_store_id        = array();
			foreach ( $customer_order_items as $item ) {
				$product_id       = $item->get_product_id();
				$store_id         = wcfm_get_vendor_id_by_post( $product_id );
				$list_store_id[]  = wcfm_get_vendor_id_by_post( $product_id );
				$list_of_stores[] = get_user_meta( $store_id, 'store_name', true );
				array_push( $hold_store_id, $store_id );
			}
			$customer_items = array();
			/* print_r($hold_store_id); */
			$total_same_id = array_count_values( $hold_store_id );

			$count_num                   = 0;
			$product_total_prices        = array();
			$single_product_total_prices = array();
			$SingleOrderItemDetails      = array();
			$orderItems                  = array();
			foreach ( $items as $item ) {

				$product              = $item->get_product();
				$product_id           = $item->get_product_id();
				$store_id             = wcfm_get_vendor_id_by_post( $product_id );
				$product_name         = $item['name'];
				$product_variation_id = $item['variation_id'];
				$quantity             = $item['quantity'];
				$product_price        = $item['total'] / $quantity;
				if ( in_array( $store_id, $hold_store_id ) && $total_same_id[ $store_id ] > 1 && ( count( array_unique( $hold_store_id ) ) != 1 ) ) {
					$store_data = "{'name':'" . $product_name . "','unitPrice':'" . $product_price . "','quantity':'" . $quantity . "'}";
					array_push( $customer_items, $store_data );
					$order_item_encode = json_encode( $customer_items );
					$orderItem         = str_replace( '"', '', $order_item_encode );

					array_push( $product_total_prices, $item['total'] );
				} else {
					$SingleOrderItem             = "[{'name':'" . $product_name . "','unitPrice':'" . $product_price . "','quantity':'" . $quantity . "'}]";
					$single_product_total_prices = array( $item['total'] );
					array_push( $SingleOrderItemDetails, $SingleOrderItem );

				}

				$pickupstore = get_user_meta( $store_id, 'store_name', true );
				$name = get_user_meta( $store_id, 'nickname', true );

				$street1              = ( ! empty( get_user_meta( $store_id, '_wcfm_street_1', true ) ) ? get_user_meta( $store_id, '_wcfm_street_1', true ) . ',' : $woocommerce_store_address );
				$street2              = ( ! empty( get_user_meta( $store_id, '_wcfm_street_2', true ) ) ? get_user_meta( $store_id, '_wcfm_street_2', true ) . ',' : $woocommerce_store_address_2 );
				$city                 = ( ! empty( get_user_meta( $store_id, '_wcfm_city', true ) ) ? get_user_meta( $store_id, '_wcfm_city', true ) . ',' : $woocommerce_store_city );
				$country              = ( ! empty( get_user_meta( $store_id, '_wcfm_country', true ) ) ? get_user_meta( $store_id, '_wcfm_country', true ) . ',' : $woocommerce_default_country );
				$state                = ( ! empty( get_user_meta( $store_id, '_wcfm_state', true ) ) ? get_user_meta( $store_id, '_wcfm_state', true ) . ',' : '' );
				$vendor_address       = $street1 . $street2 . $city . $country;
				$vendor_billing_phone = get_user_meta( $store_id, 'billing_phone', true );

			}
			$unique_store_ids = array_unique( $hold_store_id );
			$order            = wc_get_order( $order_id );
			$runner           = 0;
			if ( ! empty( $product_total_prices ) && ! empty( $single_product_total_prices ) ) {
				$get_subtotal_each = array_sum( $product_total_prices ) . ',' . array_sum( $single_product_total_prices );
				$get_subtotal      = explode( ',', $get_subtotal_each );
			} elseif ( empty( $product_total_prices ) && ! empty( $single_product_total_prices ) ) {
				$get_subtotal = $single_product_total_prices;
			} elseif ( ! empty( $product_total_prices ) && empty( $single_product_total_prices ) ) {
				$get_subtotal = array_sum( $product_total_prices );
			} else {
				$get_subtotal = '0';
			}
			echo $single_product_total_prices;
			print_r( $orderItem );
			$orderItem = array( $orderItem );
			echo '<br>singleOrder:';
			// print_r($SingleOrderItem);
			print_r( $SingleOrderItemDetails );

			/*
			 $SingleOrderItem = array($SingleOrderItem);
			$orderItems = array_merge($orderItem,$SingleOrderItem); */

			$SingleOrderItem = array( $SingleOrderItemDetails );
			$orderItems      = array_merge( $orderItem, $SingleOrderItemDetails );
			echo '<br>OrderItems: ';
			print_r( $orderItems );
			foreach ( $unique_store_ids as $store_id ) {
				if ( ! empty( $get_api ) ) {
					$product_id = $item->get_product_id();

					$pickupstore = get_user_meta( $store_id, 'store_name', true );

					$store_profile          = get_user_meta( $store_id, 'wcfmmp_profile_settings', true );
					$vendor_billing_phone   = $store_profile['phone'];
					$customer_billing_email = $store_profile['store_email'];
					/*  $orderItem1 = ( !empty( $SingleOrderItem ) ? $SingleOrderItem : $orderItem ); */
					$_wcfm_find_address = get_user_meta( $store_id, '_wcfm_find_address', true );
					if ( ! empty( $_wcfm_find_address ) ) {
						$vendor_address = $_wcfm_find_address;
					} else {
						$street1        = ( ! empty( get_user_meta( $store_id, '_wcfm_street_1', true ) ) ? get_user_meta( $store_id, '_wcfm_street_1', true ) . ',' : $woocommerce_store_address );
						$street2        = ( ! empty( get_user_meta( $store_id, '_wcfm_street_2', true ) ) ? get_user_meta( $store_id, '_wcfm_street_2', true ) . ',' : $woocommerce_store_address_2 );
						$city           = ( ! empty( get_user_meta( $store_id, '_wcfm_city', true ) ) ? get_user_meta( $store_id, '_wcfm_city', true ) . ',' : $woocommerce_store_city );
						$country        = ( ! empty( get_user_meta( $store_id, '_wcfm_country', true ) ) ? get_user_meta( $store_id, '_wcfm_country', true ) . ',' : $woocommerce_default_country );
						$state          = ( ! empty( get_user_meta( $store_id, '_wcfm_state', true ) ) ? get_user_meta( $store_id, '_wcfm_state', true ) . ',' : '' );
						$vendor_address = $street1 . $street2 . $city . $country;
					}
					$vendor_billing_phone = get_user_meta( $store_id, 'billing_phone', true );
					$total_amt            = $get_subtotal[ $runner ];

					curl_setopt_array(
						$curl,
						array(
							CURLOPT_URL            => 'https://api.shipday.com/orders',
							CURLOPT_RETURNTRANSFER => true,
							CURLOPT_ENCODING       => '',
							CURLOPT_MAXREDIRS      => 10,
							CURLOPT_TIMEOUT        => 0,
							CURLOPT_FOLLOWLOCATION => true,
							CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
							CURLOPT_CUSTOMREQUEST  => 'POST',
							CURLOPT_POSTFIELDS     => '{
						"orderNumber" : "' . $order_num . '",
						"customerName" : "' . $customer_fullname . '",
						"customerAddress" : "' . $customer_complete_address . '",
						"customerEmail" : "' . $customer_billing_email . '",
						"customerPhoneNumber" : "' . $customer_billing_phone . '",
						"restaurantName" : "' . $pickupstore . '",
						"restaurantAddress" : "' . $vendor_address . '",
						"restaurantPhoneNumber" : "' . $vendor_billing_phone . '",
						"expectedDeliveryDate": "' . $del_date . '",
						"totalOrderCost":"' . $total_amt . '",
						"orderItem": ' . $orderItems[ $runner ] . ',
						"tax":"' . $order_shipping_tax . '",
						"additionalId":"xxxx",
						"orderSource" :"woocommerce",
						"url" : "'.$get_site_url.'"
					}',
							CURLOPT_HTTPHEADER     => array(
								$get_api,
								'Content-Type: application/json',
							),
						)
					);

					$response = curl_exec( $curl );
					// echo $response;
				}
				$runner++;
			}

			 curl_close( $curl );
		}
		/*  ========= WCFM End ===========  */

		/*  ====== Dokan ======  */

		if ( is_plugin_active( 'dokan-lite/dokan.php' ) ) {

			require_once dirname( __FILE__ ) . '/dokan-custom-field.php';

			include_once ABSPATH . 'wp-admin/includes/plugin.php';
			$sub_orders = get_children(
				array(
					'post_parent' => $order_id,
					'post_type'   => 'shop_order',
				)
			);
			if ( $sub_orders && $sub_orders != '' ) {
				foreach ( $sub_orders as $_sub_orders ) {
					$order     = wc_get_order( $_sub_orders->ID );
					$get_items = $order->get_items();

					$item_meta_data = $order->get_meta_data();
					// echo "<pre>";
					// print_r($get_items);
					// echo "</pre>";
					foreach ( $get_items as $get_item ) {

						$product_name         = $get_item['name'];
						$product_id           = $get_item['product_id'];
						$product_variation_id = $get_item['variation_id'];
						$quantity             = $get_item['quantity'];
						$product_price        = $get_item['total'] / $quantity;

						$get_order_id = $get_item['order_id'];

						$total_amt   = $get_item['total'];
						$vendor_id   = dokan_get_seller_id_by_order( $get_order_id );
						$vendor_data = get_user_meta( $vendor_id );
						$pickupstore = ( ! empty( $vendor_data['dokan_store_name'][0] ) ? $vendor_data['dokan_store_name'][0] : '' );

						$SingleOrderItem = "[{'name':'" . $product_name . "','unitPrice':'" . $product_price . "','quantity':'" . $quantity . "'}]";
					

						// code...
					}
				

					$curl = curl_init();

					curl_setopt_array(
						$curl,
						array(
							CURLOPT_URL            => 'https://api.shipday.com/orders',
							CURLOPT_RETURNTRANSFER => true,
							CURLOPT_ENCODING       => '',
							CURLOPT_MAXREDIRS      => 10,
							CURLOPT_TIMEOUT        => 0,
							CURLOPT_FOLLOWLOCATION => true,
							CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
							CURLOPT_CUSTOMREQUEST  => 'POST',
							CURLOPT_POSTFIELDS     => '{
							"orderNumber" : ' . $get_order_id . ',
							"customerName" : "' . $customer_fullname . '",
							"customerAddress" : "' . $customer_complete_address . '",
							"customerEmail" : "' . $customer_billing_email . '",
							"customerPhoneNumber" : "' . $customer_billing_phone . '",
							"restaurantName" : "' . $pickupstore . '",
							"restaurantAddress" : "' . $vendor_address . '",
							"restaurantPhoneNumber" : 2512121,
							"expectedDeliveryDate": "' . $del_date . '",
							"orderItem":' . $SingleOrderItem . ',
							"totalOrderCost":"' . $total_amt . '",
							"tax":"' . $order_shipping_tax . '",
							"additionalId":"xxxx",
							"orderSource" :"woocommerce",
							"url" : "'.$get_site_url.'"
						}',
							CURLOPT_HTTPHEADER     => array(
								$get_api,
								'Content-Type: application/json',
							),
						)
					);

					$response = curl_exec( $curl );

					curl_close( $curl );
					echo $response;

				}
			} else {

						$vendor_id   = dokan_get_seller_id_by_order( $order_id );
						$vendor_data = get_user_meta( $vendor_id );
						$pickupstore = ( ! empty( $vendor_data['dokan_store_name'][0] ) ? $vendor_data['dokan_store_name'][0] : '' );

						$SingleOrderItem = "[{'name':'" . $product_name . "','unitPrice':'" . $product_price . "','quantity':'" . $quantity . "'}]";

					$curl = curl_init();

					curl_setopt_array(
						$curl,
						array(
							CURLOPT_URL            => 'https://api.shipday.com/orders',
							CURLOPT_RETURNTRANSFER => true,
							CURLOPT_ENCODING       => '',
							CURLOPT_MAXREDIRS      => 10,
							CURLOPT_TIMEOUT        => 0,
							CURLOPT_FOLLOWLOCATION => true,
							CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
							CURLOPT_CUSTOMREQUEST  => 'POST',
							CURLOPT_POSTFIELDS     => '{
							"orderNumber" : "' . $order_id . '",
							"customerName" : "' . $customer_fullname . '",
							"customerAddress" : "' . $customer_complete_address . '",
							"customerEmail" : "' . $customer_billing_email . '",
							"customerPhoneNumber" : "' . $customer_billing_phone . '",
							"restaurantName" : "' . $pickupstore . '",
							"restaurantAddress" : "' . $vendor_address . '",
							"restaurantPhoneNumber" : 124854524 ,
							"expectedDeliveryDate": "' . $del_date . '",
							"orderItem":' . $SingleOrderItem . ',
							"totalOrderCost":"' . $total_amt . '",
							"tax":"' . $order_shipping_tax . '",
							"additionalId":"xxxx",
							"orderSource" :"woocommerce",
							"url" : "'.$get_site_url.'"
						}',
							CURLOPT_HTTPHEADER     => array(
								$get_api,
								'Content-Type: application/json',
							),
						)
					);

					$response = curl_exec( $curl );

					curl_close( $curl );
					echo $response;

			}
		}

		/*  ====== End Dokan ======  */

		/* if both dokan and wcfm deactive */

		if ( ! is_plugin_active( 'dokan-lite/dokan.php' ) && ! is_plugin_active( 'wc-multivendor-marketplace/wc-multivendor-marketplace.php' ) ) {

			if ( ! is_plugin_active( 'woo-delivery/coderockz-woo-delivery.php' ) && ! is_plugin_active( 'order-delivery-date-for-woocommerce/order_delivery_date.php' ) ) {
				$del_date = date( 'Y-m-d' );
			}

			$get_total_orders = $order->get_items();
			$pickupstore      = get_bloginfo( 'name' );
			$curl             = curl_init();
			curl_setopt_array(
				$curl,
				array(
					CURLOPT_URL            => 'https://api.shipday.com/orders',
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_ENCODING       => '',
					CURLOPT_MAXREDIRS      => 10,
					CURLOPT_TIMEOUT        => 0,
					CURLOPT_FOLLOWLOCATION => true,
					CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST  => 'POST',
					CURLOPT_POSTFIELDS     => '{
					"orderNumber" : "' . $order_id . '",
					"customerName" : "' . $customer_fullname . '",
					"customerAddress" : "' . $customer_complete_address . '",
					"customerEmail" : "' . $customer_billing_email . '",
					"customerPhoneNumber" : "' . $customer_billing_phone . '",
					"restaurantName" : "' . $pickupstore . '",
					"restaurantAddress" : "' . $default_store_address . '",
					"restaurantPhoneNumber" : 124854524 ,
					"expectedDeliveryDate": "' . $del_date . '",
					"orderItem":' . $orderItem . ',
					"totalOrderCost":"' . $total_amt . '",
					"tax":"' . $order_shipping_tax . '",
					"additionalId":"xxxx",
					"orderSource" :"woocommerce",
					"url" : "'.$get_site_url.'"
				}',
					CURLOPT_HTTPHEADER     => array(
						$get_api,
						'Content-Type: application/json',
					),
				)
			);

			$response = curl_exec( $curl );

			curl_close( $curl );

		}

		
	}
}



