<?php

require_once dirname( __DIR__ ) . '/functions/common.php';

function get_times(WC_Order $order) {

	if(is_plugin_active('woo-delivery/coderockz-woo-delivery.php')) {
		require_once dirname( __FILE__ ) . '/Coderocks_Woo_Delivery.php';
		$date_picker_object = new Coderocks_Woo_Delivery($order->get_id());
	} else if(is_plugin_active('order-delivery-date-for-woocommerce/order_delivery_date.php') ||
	          is_plugin_active('order-delivery-date/order_delivery_date.php')){
		require_once dirname( __FILE__ ) . '/Order_Delivery_Date_Shipday.php';
		$date_picker_object = new Order_Delivery_Date_Shipday($order->get_id());
	}

	if (!isset($date_picker_object)) return array();

	$times = array();
	if ($date_picker_object->has_delivery_date()) $times["expectedDeliveryDate"] = $date_picker_object->get_delivery_date();
	if ($date_picker_object->has_delivery_time()) $times["expectedDeliveryTime"] = $date_picker_object->get_delivery_time();
	if ($date_picker_object->has_pickup_time())  $times["expectedPickupTime"] = $date_picker_object->get_pickup_time();
	return $times;
}

?>