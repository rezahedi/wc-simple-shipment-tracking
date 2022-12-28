<?php
/**
 * Shipped order email sent to customer (html version)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/rz-simple-shipment-tracking-email.php.
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
		
			// Add tracking link html tag over tracking number!
			$v['tracking_number'] = sprintf( '<a href="%s">%s</a>', $v['tracking_link'], $v['tracking_number'] );
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


/**
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php /* translators: %s: Customer first name */ ?>
<p><?php printf( esc_html__( 'Hey %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) ); ?></p>
<?php /* translators: %s: Order number */ ?>

<p>Good news! Your order number <?php echo $order->get_order_number(); ?> has been shipped. Your order will be delivered between 7-12 business days.</p>

<?php if( count($shipment_data) > 0 ): ?>
	<p>Here are the tracking numbers that you can use to check the location of your packages. Please note that tracking may take up to one business day to activate.</p>
	<table cellspacing="0" cellpadding="6" border="1" width="100%" style="color:#636363; border:1px solid #e5e5e5">
		<thead>
			<tr>
				<th>Tracking Provider</th>
				<th>Tracking Number</th>
				<th>Date Shipped</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach($shipment_data as $shipment): ?>
			<tr>
				<td><?php echo $shipment['tracking_provider']; ?></td>
				<td><?php echo $shipment['tracking_number']; ?></td>
				<td><?php echo $shipment['date_shipped']; ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>

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