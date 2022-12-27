<?php
/**
 * Admin cancelled order email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/admin-cancelled-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woothemes.com/document/template-structure/
 * @author  	WooThemes
 * @package 	WooCommerce/Templates/Emails/Plain
 * @version 	2.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// get shipment tracking data from order metadata and format it for email template
$shipment_data = get_post_meta( $order->id, '_wc_simple_shipment_tracking_items', false);

if( count($shipment_data) > 0 ) {

	foreach( $shipment_data as $i => $v ) {
		if( $v['tracking_link'] != '' ) {
			// Replace %s in tracking_link with tracking number
			$v['tracking_link'] = sprintf( $v['tracking_link'], $v['tracking_number'] );
		}
		
		// Format shipment date like "Monday, 12th February" If available
		if( $v['date_shipped'] != '' ) {
			$v['date_shipped'] = date_format( date_create( $v['date_shipped'] ),"l, jS F" );
		} else {
			$v['date_shipped'] = 'None';
		}

		$shipment_data[ $i ] = $v;
	}
}


echo "= " . $email_heading . " =\n\n";

echo sprintf( "Good news! Your order number %s has been shipped. Your order will be delivered between 7-12 business days.\n\n", $order->get_order_number() );

if( count($shipment_data) > 0 ) {
	echo "Here are the tracking numbers that you can use to check the location of your packages. Please note that tracking may take up to one business day to activate.\n\n";

	foreach( $shipment_data as $i => $v ) {
		echo "Package Number: " . ( $i + 1 ) . "\n";
		echo "Tracking Provider: " . $v['tracking_provider'] . "\n";
		echo "Tracking Number: " . $v['tracking_number'] . "\n";
		echo "Date Shipped: " . $v['date_shipped'] . "\n";
		if( $v['tracking_link'] != '')
			echo "Tracking Link: " . $v['tracking_link'] . "\n";
		echo "\n";
	}
}

echo "Please by replying to this email let us know if you have any questions.\n\n";
echo "Thank you for placing your order with us!\n\n";

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/**
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Emails::order_schema_markup() Adds Schema.org markup.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/**
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/**
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );