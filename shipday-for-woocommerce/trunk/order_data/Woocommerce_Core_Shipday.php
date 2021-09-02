<?php
require_once dirname( __DIR__ ) . '/functions/common.php';

class Woocommerce_Core_Shipday {
	protected $order;
	public static function to_state_name( $state_code, $country_code ) {
		return ! empty( $state_code ) ? WC()->countries->get_states( $country_code )[ $state_code ] : $state_code;
	}

	public static function to_country_name( $country_code ) {
		return ! empty( $country_code ) ? ( new WC_Countries() )->get_countries()[ $country_code ] : '';
	}

	public static function add_calling_country_code($phone_number, $country_code) {
		if ($phone_number[0] == '+') return $phone_number;
		if (substr($phone_number, 0, 2) == '00') return '+'.substr($phone_number, 2);
		$calling_code = ( new WC_Countries() )->get_country_calling_code( $country_code );
		if ($phone_number[0] == '0') $phone_number = substr($phone_number, 1);
		return $calling_code.$phone_number;
	}

	function get_customer_info(): array {
		$name = sanitize_user( handle_null( $this->order->get_billing_first_name() ) ) . ' ' . sanitize_user( handle_null( $this->order->get_billing_last_name() ) );

		$address1     = handle_null( $this->order->get_billing_address_1() );
		$address2     = handle_null( $this->order->get_billing_address_2() );
		$city         = handle_null( $this->order->get_billing_city() );
		$state_code   = handle_null( $this->order->get_billing_state() );
		$post_code    = handle_null( $this->order->get_billing_postcode() );
		$country_code = handle_null( $this->order->get_billing_country() );

		$state        = $this->to_state_name( $state_code, $country_code );
		$country      = $this->to_country_name( $country_code );
		$full_address = $address2 . ', ' . $address1 . ', ' . $city . ', ' . $state . ', ' . $post_code . ', ' . $country;

		$phoneNumber  = $this->add_calling_country_code(handle_null( $this->order->get_billing_phone() ), $country_code);

		$emailAddress = handle_null( $this->order->get_billing_email() );

		$customer_info = array(
			"customerName"        => $name,
			"customerAddress"     => $full_address,
			"customerPhoneNumber" => $phoneNumber,
			"customerEmail"       => $emailAddress
		);

		return $customer_info;
	}

	function get_shipping_address() : array {
		if ( ! $this->order->has_shipping_address() ) {
			return $this->get_customer_info();
		}
		$shipping_info = array(
			"customerName"        => sanitize_user( handle_null( $this->order->get_shipping_first_name() ) ) . ' ' . sanitize_user( handle_null( $this->order->get_shipping_last_name() ) ),
			"customerAddress"     => handle_null( $this->order->get_shipping_address_2() ) . ', ' .
			                         handle_null( $this->order->get_shipping_address_1() ) . ', ' .
			                         handle_null( $this->order->get_shipping_city() ) . ', ' .
			                         $this->to_state_name( handle_null( $this->order->get_shipping_state() ), handle_null( $this->order->get_shipping_country() ) ) . ', ' .
			                         handle_null( $this->order->get_shipping_postcode() ) . ', ' .
			                         $this->to_country_name( handle_null( $this->order->get_shipping_country() ) ),
			"customerPhoneNumber" => ! empty( $this->order->shipping_phone ) ?
				self::add_calling_country_code($this->order->shipping_phone, $this->order->get_shipping_country()) :
				$this->add_calling_country_code(handle_null( $this->order->get_billing_phone() ), $this->order->get_billing_country()),
			"customerEmail"       => ! empty( $this->order->shipping_email ) ? $this->order->shipping_email : handle_null( $this->order->get_billing_email() )
		);

		return $shipping_info;
	}

	function get_order_items($items = null) : array {
		if ($items === null) $items = $this->order->get_items();
		foreach ( $items as $item_id => $item ) {
			$orderItem[] = array(
				'name'      => $item->get_name(),
				'quantity'  => $item->get_quantity(),
				'unitPrice' => $item->get_total() / $item->get_quantity(),
			);
		}
		return array(
			'orderItem' => $orderItem
		);
	}


	function get_message() : array {
		return array(
			'deliveryInstruction' => handle_null($this->order->get_customer_note())
		);
	}

}