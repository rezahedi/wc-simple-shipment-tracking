<?php
/**
 * Cancelled Order sent to Customer.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php /* translators: %s: Customer first name */ ?>
<p><?php printf( esc_html__( 'Hey %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) ); ?></p>
<?php /* translators: %s: Order number */ ?>

<p><?php
// get shipment tracking data from order metadata
$shipment_tracking = $order->get_meta('_wc_simple_shipment_tracking_items');

// If tracking information provided!
if( $shipment_tracking['tracking_number'] != '' ) {

	if( $shipment_tracking['tracking_link'] != '' ) {
		// Replace %s in tracking_link with tracking number
		$shipment_tracking['tracking_link'] = sprintf( $shipment_tracking['tracking_link'], $shipment_tracking['tracking_number'] );
	
		// Add tracking link html tag over tracking number!
		$shipment_tracking['tracking_number_linked'] = sprintf( '<a href="%s">%s</a>', $shipment_tracking['tracking_link'], $shipment_tracking['tracking_number'] );
	}
	
	// Format shipment date like "Monday, 12th February" If available
	if( $shipment_tracking['date_shipped'] != '' ) {
		$shipment_tracking['date_shipped'] = ' on ' . date_format( date_create( $shipment_tracking['date_shipped'] ),"l, jS F" );
	}
	
	printf( esc_html__( 'Good news! Your order number %s has been shipped to %s%s, for tracking your order\'s package please use %s tracking code.', 'woocommerce' ),
		esc_html( $order->get_order_number() ),
		$shipment_tracking['tracking_provider'],
		$shipment_tracking['date_shipped'],
		( $shipment_tracking['tracking_link'] !='' ? $shipment_tracking['tracking_number_linked'] : $shipment_tracking['tracking_number'] )
	);

} else {
	// If tracking information not provided.
	printf( esc_html__( 'Good news! Your order number %s has been shipped.', 'woocommerce' ), esc_html( $order->get_order_number() ) );
}

?></p>

<?php

/**
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Emails::order_schema_markup() Adds Schema.org markup.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/**
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );