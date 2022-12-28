# WordPress plugin: Simple Shipment Tracking for WooCommerce

This plugin adds new order status "Shipped", add new metaboxs to the order edit page where you can add multi tracking information, and add a new email template for the "Shipped" order status under WooCommerce > Settings > Emails.

With this plugin, you can add shipment tracking information to your orders in WooCommerce and provide customers with an easy way to track their orders by sending email notifications about order processing and shipment status. Shipment tracking Info will appear in customers' accounts (in the frontend orders panel) and in the WooCommerce order shipped email.


## Features

1. Add new order status "Shipped"
2. Add new metaboxs to the backend order page where you can add multi tracking information
3. Add a new email template for the "Shipped" order status under WooCommerce > Settings > Emails
4. Trigger email notification to customers when order status changed to "Shipped"
5. Add shipment tracking info to the shipped order email notification
6. Add shipment tracking info to the customer account (in the frontend orders panel)
7. Bulk update order status to "Shipped" from the backend orders page
8. Add the "Resend shipment email notification" action to the backend order page > actions box


## Template Customization

All frontend-related changes can be done by copying the template files to your theme folder and editing the template files in your theme folder.

To customize the email template or myaccount > order > tracking info template, copy the files in `plugins/PLUGIN-FOLDER/templates/` to your theme folder `YOUR-THEME/woocommerce/templates/` and edit it as you need.

__Note: There are NOT any bloat assets like CSS or JS files imported in the frontend by this plugin.__