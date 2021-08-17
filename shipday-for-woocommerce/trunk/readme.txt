=== Local Delivery App for WooCommerce by Shipday ===
Contributors: shipdayinc
Tags: delivery tracking, route-planning, delivery management, delivery dispatch, same day delivery, local pickup, local delivery, delivery tracking,  driver app
Requires at least: 3.5
Tested up to: 5.8
WC requires at least: 3.0
WC tested up to: 5.5.2
Stable tag: 0.4.4
License: GPLv2 or later

== Description ==

Use the Shipday Integration for WooCommerce plugin to create connection with [Shipday](https://www.shipday.com/).

So that, when a customer places any delivery order on your WooCommerce website, the order will be automatically imported to Shipday for dispatch and delivery tracking. 

After installation, add your Shipday API configuration in the plugin to allow your site to send order details to the Shipday dispatch dashboard. You'll find the settings under: WooCommerce > Settings > Shipday

We support both single vendor and multi-vendor marketplace setup (built with Dokan and WCFM).	

Shipday is ideal for managing fast on-demand or scheduled deliveries for your online business. 

 - Restaurant delivery
 - Prepared meal delivery 
 - Quick convenience delivery 
 - Grocery delivery
 - Pharmacy delivery 
 - Other food deliveries 
 - Flower delivery 
 - Local courier delivery
 - And many others we are learning everyday

#### Currently we are compatible with following plugins:
👉 [WooCommerce](https://wordpress.org/plugins/woocommerce/)
👉 [Dokan](https://wordpress.org/plugins/dokan-lite/)
👉 [WCFM Marketplace](https://wordpress.org/plugins/wc-multivendor-marketplace/)
👉 [Delivery & Pickup Date Time for WooCommerce](https://wordpress.org/plugins/woo-delivery/)
👉 [Order Delivery Date for WooCommerce](https://wordpress.org/plugins/order-delivery-date-for-woocommerce/)

#### What is Shipday?
[Shipday](https://www.shipday.com/) is an easy-to-use local delivery management software with driver app and live delivery tracking for customers with SMS notifications. 

It’s a cloud based local delivery dispatch and tracking software for small businesses with existing drivers. Track your delivery orders easily and get your product in the hands of customers, fast. It’s free to start and works anywhere in the world with internet connectivity.

Whether you are launching your own delivery service to fulfill orders locally, or you are a third party courier service helping other businesses deliver locally – you can use this plugin. 


 - All-in-one easy dispatch dashboard: When a customer places an order, you’ll immediately see pick-up and delivery information and can easily dispatch drivers.

 - Easy driver management with Driver App: With the Shipday Driver App, your drivers will automatically receive order notifications, the required delivery time, and any special delivery instructions. You can also track their real time location on the map during deliveries. 

 - Real-time delivery tracking for customers: Once they place an order, your customers will see real-time tracking information so they know exactly when to expect their delivery. 

Proof of Delivery with Photo and Signature: Drivers will take photo and signature proof of deliveries. No second-guessing whether an order was really delivered.

Shipday is powering local deliveries globally. Already in use in over 80+ countries. 

[youtube https://youtu.be/GCypZ45ZCQ4]

Have a look at our [terms of use](https://www.shipday.com/terms).

If you face any issue at the time of installation, send an email to : <support@shipday.com>

== Screenshots ==



== Installation ==
You can install shipday plugin directly from wordpress plugin repository or manually upload to your site. Follow one of the following process.
#### Wordpress Plugin directory
1. In your wordpress admin dashboard, go to plugins and select add new plugins.
2. Search for shipday in the search bar and install it.
3. Activate the plugin.
4. Go to Woocommerce settings tabs and select Shipday plugin tab.
5. Enter your API key which is found in your dispatch accounts setting page.
6. Confirm settings and you are done.

[youtube https://www.youtube.com/watch?v=t5MdxiBfuks]

#### Manual upload
1. Upload `woocommerce-shipday` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the options on the WooCommerce settings shipday tab.See in the screenshot https://prnt.sc/uxkksw
3. Done


== Changelog ==

= 0.4.4 (17.08.2021) =
* Fix: Delivery Date parsed from date picker
* Fix: JSON anomaly that caused order failure in dispatch
* Fix: Fixing Duplicate entry ( fisrt check if there is 'passed_top_shipday' in post_meta with value '1' if exist then it will run curl only once. )


