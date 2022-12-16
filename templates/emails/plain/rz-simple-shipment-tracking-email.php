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

echo "= " . $email_heading . " =\n\n";

// get shipment tracking data from order metadata
$shipment_tracking = $order->get_meta('_wc_simple_shipment_tracking_items');

// If tracking information provided!
if( $shipment_tracking['tracking_number'] != '' ) {

	if( $shipment_tracking['tracking_link'] != '' ) {
		// Replace %s in tracking_link with tracking number
		$shipment_tracking['tracking_link'] = sprintf( $shipment_tracking['tracking_link'], $shipment_tracking['tracking_number'] );
	}

	// Format shipment date like "Monday, 12th February" If available
	if( $shipment_tracking['date_shipped'] != '' ) {
		$shipment_tracking['date_shipped'] = ' on ' . date_format( date_create( $shipment_tracking['date_shipped'] ),"l, jS F" );
	}

	echo sprintf( __( 'Good news! Your order number %s has been shipped to %s%s, for tracking your order\'s package please use %s tracking code.', 'woocommerce' ),
		$order->get_order_number(),
		$shipment_tracking['tracking_provider'],
		$shipment_tracking['date_shipped'],
		$shipment_tracking['tracking_number']
	) . "\n\n";

	// Print tracking_link if available
	if( $shipment_tracking['tracking_link'] != '' ) {
		echo sprintf( __("Or just copy the following link to your browser: \n\n%s\n\n"), $shipment_tracking['tracking_link'] );
	}

} else {
	// If tracking information not provided.
	printf( esc_html__( 'Good news! Your order number %s has been shipped.', 'woocommerce' ), esc_html( $order->get_order_number() ) );
}

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