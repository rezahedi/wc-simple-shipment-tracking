<?php
/**
 * myaccount > orders > tracking info template in order view page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/rz-tracking-info.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


?>
<section class="rz-shipment-tracking">
	<h2>Track Your Order</h2>
	<table cellpadding="6" cellspacing="0" border="1" width="100%" style="color:#636363; border:1px solid #e5e5e5">
		<tr>
			<th>Provider</th>
			<th>Tracking Number</th>
			<th>Date Shipped</th>
		</tr>
		<?php foreach( $shipment_data as $shipment ) : ?>
			<tr>
				<td><?php echo $shipment['tracking_provider']; ?></td>
				<td><?php echo $shipment['tracking_number_linked']; ?></td>
				<td><?php echo $shipment['date_shipped_formatted']; ?></td>
			</tr>
		<?php endforeach; ?>
	</table>
</section>
