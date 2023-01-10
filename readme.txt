=== Simple Shipment Tracking for WooCommerce ===

Contributors: rezawm
Tags: WooCommerce, delivery, shipping, shipment tracking, tracking
Requires at least: 5.3
Tested up to: 6.0.1
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A WordPress plugin to add shipment tracking information to your orders in WooCommerce and provide customers with an easy way to track their orders by sending email notifications about order processing and shipment status.

== Description ==

This plugin adds new order status "Shipped", add new metaboxs to the order edit page where you can add multi tracking information, and add a new email template for the "Shipped" order status under WooCommerce > Settings > Emails.

With this plugin, you can add shipment tracking information to your orders in WooCommerce and provide customers with an easy way to track their orders by sending email notifications about order processing and shipment status. Shipment tracking Info will appear in customers' accounts (in the frontend orders panel) and in the WooCommerce order shipped email.

== Key Features ==

1. Add new order status "Shipped"
2. Add new metaboxs to the backend order page where you can add multi tracking information
3. Add a new email template for the "Shipped" order status under WooCommerce > Settings > Emails
4. Trigger email notification to customers when order status changed to "Shipped"
5. Add shipment tracking info to the shipped order email notification
6. Add shipment tracking info to the customer account (in the frontend orders panel)
7. Bulk update order status to "Shipped" from the backend orders page
8. Add the "Resend shipment email notification" action to the backend order page > actions box

== Installation ==

1. Upload the plugin folder to your /wp-content/plugins/ folder.
1. Go to the **Plugins** page and activate the plugin.

== Frequently Asked Questions ==

= Does this plugin include/import any js/css file in frontend website? =

No, this plugin do not add any bloat files to your frontend website, you need to add your own styles for this plugin.

= How to uninstall the plugin? =

First if you have orders with 'Shipped' status, bulk change status of orders from 'Shipped' to 'Completed' then simply deactivate and delete the plugin.

== Changelog ==
= 1.0.0 =
* Plugin released.