<?php
/**
 * @wordpress-plugin
 * Plugin Name: Simple Shipment Tracking for WooCommerce
 * Plugin URI: https://rezahedi.com/projects/wp-woocommerce-simple-shipment-tracking
 * Description: Add shipment tracking information to your WooCommerce orders and provide customers with an easy way to track their orders. Shipment tracking Info will appear in customers accounts (in the order panel) and in WooCommerce order shipped email. 
 * Version: 1.0.0
 * Author: Reza Zahedi
 * Author URI: https://rezahedi.dev
 * Text Domain: wc-simple-shipment-tracking 
 *
 * WC requires at least: 6.0
 * WC tested up to: 7.2.0
 *
 * License: GNU General Public License v3.0
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * 
*/


// Plugin meta keys
define( 'RZ_META_KEY_ITEM', '_wc_simple_shipment_tracking_items' );
define( 'RZ_META_KEY_EMAIL_SENT', '_wc_simple_shipment_tracking_email_sent' );


// Register Shipped Order Status in WooCommerce
add_action( 'init', 'rz_register_shipped_order_status' );
function rz_register_shipped_order_status()
{
	register_post_status( 'wc-shipped', array(
		'label'								=> _x('Shipped', 'Custom order status for shipped!','wc-simple-shipment-tracking'),
		'public'								=> true,
		'exclude_from_search'			=> false,
		'show_in_admin_all_list'		=> true,
		'show_in_admin_status_list'	=> true,
		'label_count'						=> _n_noop( 'Shipped <span class="count">(%s)</span>', 'Shipped <span class="count">(%s)</span>', 'wc-simple-shipment-tracking' )
	));
}


// Add 'Resend Email' action to Order Actions Metabox
add_action( 'woocommerce_order_actions', 'rz_add_resend_action' );

function rz_add_resend_action( $actions )
{
   $actions['rz-resend-email-action'] = __( 'Resend shipped email notification', 'wc-simple-shipment-tracking');
   return $actions; 
}

// Link callback to our custom action 'rz-resend-email-action'
add_action( 'woocommerce_order_action_rz-resend-email-action', 'rz_resend_email_action');
function rz_resend_email_action( $order ) {
	$wc_emails = WC()->mailer()->get_emails();
	$wc_emails['wc-shipped']->trigger( $order->get_id(), true );
}


// Add 'Shipped' to Order Status list on Single Order Page
add_filter( 'wc_order_statuses', 'rz_add_shipped_to_order_statuses' );
/* Adds new Order status - Shipped in Order statuses*/
function rz_add_shipped_to_order_statuses($order_statuses)
{
   $new_order_statuses = array();
   // add new order status after "On hold"
	foreach ( $order_statuses as $key => $status ) 
	{
		$new_order_statuses[ $key ] = $status;
		if ( 'wc-on-hold' === $key ) 
		{
			$new_order_statuses['wc-shipped'] = _x('Shipped', 'Custom order status for shipped!','wc-simple-shipment-tracking');    
		}
	}
	return $new_order_statuses;
}

// Add style for Shipped label in Orders list page in admin panel
add_action('admin_head', 'rz_shipped_label_style');
function rz_shipped_label_style() {
	global $current_screen;
	if ( $current_screen->id != 'edit-shop_order' ) return;
	
	echo '<style>.order-status.status-shipped{background:#769349;color:#fff}</style>';
}


// Adding custom status 'awaiting-delivery' to admin order list bulk dropdown
add_filter( 'bulk_actions-edit-shop_order', 'custom_dropdown_bulk_actions_shop_order', 20, 1 );
function custom_dropdown_bulk_actions_shop_order( $actions ) {
    $actions['mark_shipped'] = __( 'Change status to Shipped', 'wc-simple-shipment-tracking' );
    return $actions;
}


// Add metabox setup action to init
add_action('init', 'rz_metabox_setup');

/* Meta box setup function. */
function rz_metabox_setup() {

	add_action( 'wp_ajax_rz_simple_shipment_tracking_delete', 'rz_meta_delete' );
	add_action( 'wp_ajax_rz_simple_shipment_tracking_add', 'rz_meta_add' );	

	/* Add meta boxes on the 'add_meta_boxes' hook. */
	add_action( 'add_meta_boxes', 'rz_metabox_add',10, 2 );
}
/* Create one or more meta boxes to be displayed on the post editor screen. */
function rz_metabox_add($post_type, $post) {
	if( $post_type != 'shop_order' ) return;

	// Get order object
	$order = new WC_Order($post->ID);

	// Get order status
	$order_status = $order->get_status();

	add_meta_box(
		'rz-sst-id',      // Unique ID
		esc_html__( 'Shipment Tracking', 'wc-simple-shipment-tracking' ),    // Title
		'rz_order_tracking_metabox_post',   // Callback function
		'shop_order',         // Admin page (or post type)
		'side',         // Context
		'high'         // Priority
	);
}
/* Display the post meta box. */
function rz_order_tracking_metabox_post( $post ) {
	
	// Get order object
	$order = new WC_Order($post->ID);

	// Get order metadata
	$shipment_data = rz_get_post_metashipments_formatted($post->ID, '%s', 'F j, Y');

	echo "<div class='rz-sst-content'>";

	rz_print_shipment_list( $shipment_data, $order );

	// Show metabox inputs if order status was in processing, on-hold or shipped
	if( $order->has_status( array('processing', 'shipped') ) ) {
		$shipment_email_sent = get_post_meta( $post->ID, RZ_META_KEY_EMAIL_SENT, true);
		rz_print_shipment_metabox($shipment_email_sent, $post->ID);
	}

	echo '</div>';
}
/* Save the meta box's post metadata. */
function rz_meta_add() {

	check_ajax_referer( 'rz_nonce_add', 'nonce' );

	// check posted data is not empty
	if( empty($_POST['tracking_provider']) && empty($_POST['tracking_number']) )
		return;

	$order_id    = isset( $_POST['order_id'] ) ? wc_clean( $_POST['order_id'] ) : '';

	if( !$order_id ) return;

	$order = new WC_Order( $order_id );

	if( !$order ) return;

	/* Check if the current user has permission to edit the post. */
	$post = get_post( $order_id );
	$post_type = get_post_type_object( $post->post_type );
	if ( !current_user_can( $post_type->cap->edit_post, $order_id ) )
		return;

	/* Get the posted data and sanitize it for use as an HTML class. */
	$new_meta_value = Array(
		'id' => 						$order_id . uniqid(),
		'tracking_provider' =>	( isset( $_POST['tracking_provider'] ) ? sanitize_text_field( $_POST['tracking_provider'] ) : '' ),
		'tracking_number' =>		( isset( $_POST['tracking_number'] ) ? sanitize_text_field( $_POST['tracking_number'] ) : '' ),
		'tracking_link' =>		( isset( $_POST['tracking_link'] ) ? sanitize_url( $_POST['tracking_link'] ) : '' ),
		'date_shipped' =>			( isset( $_POST['date_shipped'] ) ? sanitize_text_field( $_POST['date_shipped'] ) : '' )
	);

	$data = get_post_meta( $order_id, RZ_META_KEY_ITEM, true);
	if( $data ) {
		$data[] = $new_meta_value;
		$shipment_data = $data;
	} else {
		$shipment_data[] = $new_meta_value;
	}

	// Update the meta field.
	update_post_meta( $order_id, RZ_META_KEY_ITEM, $shipment_data );

			
	/* translators: %s: Reaplce with tracking provider, %s: Reaplce with tracking number */
	$note = sprintf(
		__( 'Tracking info added for tracking provider %s with tracking number %s', 'wc-simple-shipment-tracking' ),
		$new_meta_value['tracking_provider'], $new_meta_value['tracking_number']
	);
	
	// Add the note
	$order->add_order_note( $note );

	$formatted_data = rz_format_data($new_meta_value, '%s', 'F j, Y');
	echo json_encode($formatted_data);
	die();
}
/* Delete the metadata ajax */
function rz_meta_delete() {
		
	check_ajax_referer( 'rz_nonce_delete', 'nonce' );
	
	$order_id    = isset( $_POST['order_id'] ) ? wc_clean( $_POST['order_id'] ) : '';
	$tracking_id = isset( $_POST['tracking_id'] ) ? wc_clean( $_POST['tracking_id'] ) : '';

	if( !$order_id || !$tracking_id ) return;

	$shipment_data = get_post_meta( $order_id, RZ_META_KEY_ITEM, true);

	if( !$shipment_data || count($shipment_data) == 0 ) return;

	// $order = wc_get_order(  $order_id );
	$order = new WC_Order( $order_id );

	$changed = false;
	foreach ( $shipment_data as $i => $v ) {
		if ( $v['id'] == $tracking_id ) {
			
			/* translators: %s: Reaplce with tracking provider, %s: Reaplce with tracking number */
			$note = sprintf(
				__( 'Tracking info was deleted for tracking provider %s with tracking number %s', 'wc-simple-shipment-tracking' ),
				$v['tracking_provider'], $v['tracking_number']
			);
			
			// Add the note
			$order->add_order_note( $note );

			unset( $shipment_data[$i] );

			$changed = true;
			
			break;
		}
	}

	if( $changed ) {
		if( count($shipment_data) == 0 ) {
			delete_post_meta( $order_id, RZ_META_KEY_ITEM );
		} else {
			update_post_meta( $order_id, RZ_META_KEY_ITEM, $shipment_data );
		}
	}

	echo '1';
	die();
}


// Registering a Custom WooCommerce Email
// Filtering the emails and adding our own email to WooCommerce email settings
add_filter( 'woocommerce_email_classes', 'rz_shipped_email_class', 90, 1 );
function rz_shipped_email_class( $email_classes ) {
	require_once plugin_dir_path( __FILE__ ) . '/rz_shipped_email.php';

	$email_classes['wc-shipped'] = new RZ_Shipped_Email(); // add to the list of email classes that WooCommerce loads

	return $email_classes;
}

add_action( 'woocommerce_order_status_changed', 'rz_status_custom_notification', 10, 4 );
function rz_status_custom_notification( $order_id, $old_status, $new_status, $order ) {
	
	if( $order->has_status( 'shipped' ) ) {

		// Getting all WC_emails objects
		$email_notifications = WC()->mailer()->get_emails();

		// Sending the customized email
		$email_notifications['wc-shipped']->trigger( $order_id );
	}

}


/**
 * ORDER LIST COLUMN in >> Admin Panel <<
 * Add 'Shipment Tracking' column to order list
 */
// Column header (after Total column)
add_filter( 'manage_edit-shop_order_columns', 'rz_shipment_tracking_column_header' );
function rz_shipment_tracking_column_header( $columns ) {
	$new_columns = array();
	foreach ($columns as $column_name => $column_info) {
		 $new_columns[$column_name] = $column_info;
		 if ('order_status' === $column_name) {
			  $new_columns['shipment_tracking'] = __('Shipment Tracking', 'wc-simple-shipment-tracking');
		 }
	}
	return $new_columns;
}
// Column content
add_action( 'manage_shop_order_posts_custom_column', 'rz_shipment_tracking_column_content' );
function rz_shipment_tracking_column_content( $column ) {
	global $post;

	if ( 'shipment_tracking' === $column ) {
		
		$shipment_data = rz_get_post_metashipments_formatted($post->ID);
		if ( !$shipment_data ) return;

		foreach( $shipment_data as $v ) {
			printf( '<b>%s</b> : %s<br>', $v['tracking_provider'], $v['tracking_number_linked'] );
		}
	}
}



// Tracking info in >> Frontend <<
// Add column title to "My Account" > "Orders" table after "Order Status" column

add_filter( 'woocommerce_my_account_my_orders_columns', 'rz_add_tracking_to_my_account_orders' );
function rz_add_tracking_to_my_account_orders( $columns ) {
	$new_columns = array();
	foreach ($columns as $column_name => $column_info) {
		 $new_columns[$column_name] = $column_info;
		 if ('order-status' === $column_name) {
			  $new_columns['shipment_tracking'] = __('Shipment Tracking', 'wc-simple-shipment-tracking');
		 }
	}
	return $new_columns;
}

// Add column data to "My Account" > "Orders" table
add_action( 'woocommerce_my_account_my_orders_column_shipment_tracking', 'rz_add_tracking_to_my_account_orders_table' );

function rz_add_tracking_to_my_account_orders_table( $order ) {

	$shipment_data = rz_get_post_metashipments_formatted($order->get_id());
	if ( !$shipment_data ) return;

	foreach( $shipment_data as $v ) {
		printf( '<b>%s</b> : %s<br>', $v['tracking_provider'], $v['tracking_number_linked'] );
	}
}


// Show tracking data in "My Account" > "View Order" detail page
add_action('woocommerce_view_order', 'rz_add_tracking_to_my_account_order_view', 1);
function rz_add_tracking_to_my_account_order_view( $order_id ) {

	$shipment_data = rz_get_post_metashipments_formatted($order_id, '%s');
	if ( !$shipment_data ) return;

	// Check if template file exists in theme folder, if not, use plugin default template file
	$template_file = "myaccount/rz-tracking-info.php";
	$wc_template_base_in_theme = get_template_directory() . '/woocommerce/';

	if( file_exists( $wc_template_base_in_theme . $template_file ) ) {
		require_once( $wc_template_base_in_theme . $template_file );

	} else {
		require_once( plugin_dir_path( __FILE__ ) . 'templates/' . $template_file );
	}
}










/**
 * MY FUNCTIONS
 */
function rz_get_post_metashipments_formatted($order_id, $date_tpl = 'on %s', $date_format = 'M j, Y') {
	
	$shipment_data = get_post_meta($order_id, RZ_META_KEY_ITEM, true);
	
	// If meta data was empty return empty array
	if ( empty($shipment_data) ) return false;

	foreach( $shipment_data as $k=>$v ) {
		$shipment_data[$k] = rz_format_data($v, $date_tpl, $date_format);
	}

	return $shipment_data;
}

function rz_format_data($data, $date_tpl = 'on %s', $date_format = 'M j, Y') {

	// Check array format if not main return empty array
	if( !isset($data['tracking_provider']) || !isset($data['tracking_number']) || !isset($data['tracking_link']) || !isset($data['date_shipped']) )
		return array();

	if( $data['tracking_link'] != '' ) {
		// Put tracking number in link
		$data['tracking_link'] = sprintf( $data['tracking_link'], $data['tracking_number'] );
		$data['tracking_number_linked'] = sprintf( '<a target="_blank" href="%s">%s</a>', $data['tracking_link'], $data['tracking_number'] );
	} else {
		$data['tracking_number_linked'] = $data['tracking_number'];
	}

	if( $data['date_shipped'] != '' ) {
		$data['date_shipped_formatted'] = sprintf( $date_tpl, date_format( date_create($data['date_shipped']), $date_format) );
	} else {
		$data['date_shipped_formatted'] = $data['date_shipped'];
	}

	return $data;
}

// Print shipment items
function rz_print_shipment_list($shipment_data, $order) {
	$nonce = wp_create_nonce( 'rz_nonce_delete' );

	// if order status was not shipped or processing show note message
	$message = '';
	if( !$order->has_status(array('shipped', 'processing')) )
		$message = "<i>To update shipment tracking, order status should be 'Processing' or 'Shipped'.</i>";

	if( !$message && empty($shipment_data) )
		$message = "<b>No shipment tracking data found.</b>";

?>
<ul
	data-nonce="<?php echo $nonce; ?>"
	data-order_id="<?php echo $order->ID; ?>"
	data-admin-ajax="<?php echo admin_url('admin-ajax.php'); ?>"
	class="order_notes">

	<?php echo $message; ?>

	<?php if($message == '') foreach( $shipment_data as &$sh ): ?>
	<li class="note">
		<div class="note_content">
			<p><b><?php echo $sh['tracking_provider']; ?></b> <?php echo $sh['tracking_number_linked']; ?></p>
		</div>
		<p class="meta">
			Shipped on <time class="exact-date" datetime="<?php echo $sh['date_shipped']; ?>"><?php echo ( $sh['date_shipped_formatted'] ? $sh['date_shipped_formatted'] : 'no date' ) ?></time>
			<a href="#"
				data-tracking_id="<?php echo $sh['id']; ?>"
				class="rz_delete_meta" role="button">Delete</a>
		</p>
	</li>
	<?php endforeach; ?>

</ul>
<?php

}

// Print metabox form
function rz_print_shipment_metabox ($shipment_email_sent, $order_id) {
	$nonce = wp_create_nonce( 'rz_nonce_add' );

	// Get providers list from json file
	if( file_exists( plugin_dir_path( __FILE__ ) . '/courier_list.json' ) )
		$providers_list = json_decode( file_get_contents( plugin_dir_path( __FILE__ ) . '/courier_list.json' ), true );

	?>
	<?php if($providers_list): ?>
	<p><select name="providers_list" class="widefat">
		<option value="none">Select the Provider</option>
		<?php foreach( $providers_list as $i => $v ): ?>
			<option value="<?php echo $v['link']; ?>"><?php echo $v['name']; ?></option>
		<?php endforeach; ?>
		<option value="custom">Custom Provider</option>
	</select></p>
	<?php endif; ?>
	<p class="customShow" <?php if($providers_list): ?>style="visibility:hidden;height:0;margin:0;float:left"<?php endif; ?>>
		<label for="tracking_provider"><?php _e( "Provider Name <sup>*</sup>:", 'wc-simple-shipment-tracking' ); ?></label>
		<br />
		<input class="widefat" type="text" name="tracking_provider" id="tracking_provider" size="30" />
	</p>
	<p>
		<label for="tracking_number"><?php _e( "Tracking Number <sup>*</sup>:", 'wc-simple-shipment-tracking' ); ?></label>
		<br />
		<input class="widefat" type="text" name="tracking_number" id="tracking_number" size="30" />
	</p>
	<p class="customShow" <?php if($providers_list): ?>style="visibility:hidden;height:0;margin:0;float:left"<?php endif; ?>>
		<label for="tracking_link"><?php _e( "Tracking Link:", 'wc-simple-shipment-tracking' ); ?></label>
		<br />
		<input class="widefat" type="text" name="tracking_link" id="tracking_link" placeholder="https://xyz.com/?trackingNum=%s" size="30" />
		<span class="components-form-token-field__help">Use %s for tracking number's place in link!</span>
	</p>
	<p>
		<label for="date_shipped"><?php _e( "Date Shipped:", 'wc-simple-shipment-tracking' ); ?></label>
		<br />
		<input class="widefat" type="date" name="date_shipped" id="date_shipped" size="30" />
	</p>
	<button type="submit" class="button-primary rz_add_meta" name="add" value="Add"
		data-href="<?php echo admin_url('admin-ajax.php'); ?>"
		data-nonce="<?php echo $nonce; ?>"
		data-order_id="<?php echo $order_id; ?>"
	><?php _e('Add New Shipment Tracking')?></button>
<script>
jQuery(document).ready( function() {

// Providers list change event
jQuery('.rz-sst-content select[name="providers_list"]').change(function(){
	if( jQuery(this).val() == 'custom' ) {
		jQuery('.rz-sst-content input[name="tracking_provider"]').val('');
		jQuery('.rz-sst-content input[name="tracking_link"]').val('');

		jQuery('.customShow').css({'visibility': 'visible', 'height': 'auto', 'margin': '1rem 0', 'float': 'none'})

	} else {
		jQuery('.customShow').css({'visibility': 'hidden', 'height': 0, 'margin': 0, 'float': 'left'})

		jQuery('.rz-sst-content input[name="tracking_provider"]').val( jQuery(this).find(':selected').text() );
		jQuery('.rz-sst-content input[name="tracking_link"]').val( jQuery(this).val() );
	}
});

// Delete shipment tracking event
jQuery(".rz-sst-content").on('click', '.rz_delete_meta', function(e) {
	e.preventDefault();

	let nonce = jQuery('.rz-sst-content .order_notes').attr("data-nonce");
	let order_id = jQuery('.rz-sst-content .order_notes').attr("data-order_id");
	let url = jQuery('.rz-sst-content .order_notes').attr("data-admin-ajax");

	let metaElement = jQuery(this).parent().parent();
	let tracking_id = jQuery(this).attr("data-tracking_id")

	metaElement.css('position', 'relative').append('<div class="blockUI blockOverlay" style="z-index:1000; border:none; margin:0px; padding:0px; width:100%; height:100%; top:0px; left:0px; background:#fff; opacity:0.6; cursor:wait; position:absolute"></div>');
	jQuery.ajax({
		type : "post",
		dataType : "json",
		url : url,
		data : {action: "rz_simple_shipment_tracking_delete", nonce: nonce, order_id : order_id, tracking_id: tracking_id},
		success: function(response) {
			if(response == "1")
				metaElement.hide('fast', function(){ this.remove() });
		},
		complete: function() {
			metaElement.find('.blockUI').remove();
		}
	})
})

// Add shipment tracking event
jQuery(".rz_add_meta").click( function(e) {
	e.preventDefault();

	let url = jQuery(this).attr("data-href");
	let metaElement = jQuery(this).parent().parent();
	let nonce = jQuery(this).attr("data-nonce")
	let order_id = jQuery(this).attr("data-order_id")

	// Loading overlay element for ajax request
	metaElement.css('position', 'relative').append('<div class="blockUI blockOverlay" style="z-index:1000; border:none; margin:0px; padding:0px; width:100%; height:100%; top:0px; left:0px; background:#fff; opacity:0.6; cursor:wait; position:absolute"></div>');

	jQuery.ajax({
		type : "post",
		dataType : "json",
		url : url,
		data : {
			action: "rz_simple_shipment_tracking_add",
			nonce: nonce,
			order_id : order_id,
			tracking_provider : jQuery('.rz-sst-content input[name="tracking_provider"]').val(),
			tracking_number : jQuery('.rz-sst-content input[name="tracking_number"]').val(),
			tracking_link : jQuery('.rz-sst-content input[name="tracking_link"]').val(),
			date_shipped : jQuery('.rz-sst-content input[name="date_shipped"]').val()
		},
		success: function(response) {
			if( response && response.id != '' ){
				// Remove message if exists
				if( jQuery('.rz-sst-content .order_notes li').length == 0 )
					jQuery('.rz-sst-content .order_notes *').remove();
				
				// Add new shipment tracking
				jQuery('.rz-sst-content .order_notes').append('<li class="note"><div class="note_content">'
					+'<p><b>'+response.tracking_provider+'</b> '+response.tracking_number_linked+'</p></div>'
					+'<p class="meta">Shipped on <time class="exact-date">'+response.date_shipped_formatted+'</time>'
					+' <a href="#" data-tracking_id="'+response.id+'" class="rz_delete_meta" role="button">Delete</a></p></li>');

				// Clear inputs
				jQuery('.rz-sst-content input[name="tracking_provider"]').val('');
				jQuery('.rz-sst-content input[name="tracking_number"]').val('');
				jQuery('.rz-sst-content input[name="tracking_link"]').val('');
				jQuery('.rz-sst-content input[name="date_shipped"]').val('');
			}
		},
		complete: function() {
			metaElement.find('.blockUI').remove();
		}
	})
})

})
</script>
	<?php
}