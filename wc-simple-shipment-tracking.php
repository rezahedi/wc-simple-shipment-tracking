<?php
/**
 * @wordpress-plugin
 * Plugin Name: Simple Shipment Tracking for WooCommerce
 * Plugin URI: https://rezahedi.com/projects/wp-woocommerce-simple-shipment-tracking
 * Description: Add shipment tracking information to your WooCommerce orders and provide customers with an easy way to track their orders. Shipment tracking Info will appear in customers accounts (in the order panel) and in WooCommerce order shipped email. 
 * Version: 0.1
 * Author: Reza Zahedi
 * Author URI: https://rezahedi.dev
 * License: GPL-2.0+
 * License URI: 
 * Text Domain: wc-simple-shipment-tracking 
 * WC tested up to: 6.8.0
*/


// TODO: Review source codes: https://wordpress.org/plugins/woo-advanced-shipment-tracking/

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
	echo '<style>.order-status.status-shipped{background:#769349;color:#fff}</style>';
}


// Adding custom status 'awaiting-delivery' to admin order list bulk dropdown
add_filter( 'bulk_actions-edit-shop_order', 'custom_dropdown_bulk_actions_shop_order', 20, 1 );
function custom_dropdown_bulk_actions_shop_order( $actions ) {
    $actions['mark_shipped'] = __( 'Change status to Shipped', 'wc-simple-shipment-tracking' );
    return $actions;
}


// Create Callback Function if Order Status is Marked as Shipped

/*
// Add callback if Shipped action called
// The following function written on the ‘woocommerce_order_action_massimo_shipped’ hook will be called when ‘Shipped’ has been selected from the order actions drop-down list
add_action( 'woocommerce_order_action_wc-shipped', 'rz_order_shipped_callback');
function rz_order_shipped_callback($order)
{
	error_log(__METHOD__);
	$order->update_status('wc-shipped', 'Order status changed by "Order Actions".');
	
	// This function used for when we sent a shipped email before, but need to send the shipped email again.
	// So when this function selected from Order Actions dropdown we update the order status to shipped and send the email in anyway (if status updated and email sent before)

	//Here order object is sent as parameter
	//Add code for processing here
}

//Add callback if Status changed to Shipping
add_action('woocommerce_order_status_wc-shipped', 'rz_wwww_order_status_shipped_callback');
function rz_wwww_order_status_shipped_callback($order_id)
{
	error_log(__METHOD__);

	//Here order id is sent as parameter
	//Add code for processing here
}
*/


// Shipment Tracking Metabox
/* Fire our meta box setup function on the post editor screen. */
// TODO: IF order status is processing then show tracking metabox, wrap the three following lines in a if statement.
// add_action('woocommerce_order_status_on-hold', 'rz_add_actions_metabox', 10, 1);

// Add metabox setup action to init
add_action('init', 'rz_metabox_setup');

/* Meta box setup function. */
function rz_metabox_setup() {

	/* Add meta boxes on the 'add_meta_boxes' hook. */
	add_action( 'add_meta_boxes', 'rz_metabox_add',10, 2 );

	/* Save post meta on the 'save_post' hook. */
	add_action( 'save_post', 'rz_meta_save', 10, 2 );
}
/* Create one or more meta boxes to be displayed on the post editor screen. */
function rz_metabox_add($post_type, $post) {
	if( $post_type != 'shop_order' ) return;

	// Get order object
	$order = new WC_Order($post->ID);

	// Get order status
	$order_status = $order->get_status();

	add_meta_box(
		'simple-shipment-tracking-class',      // Unique ID
		esc_html__( 'Shipment Tracking', 'wc-simple-shipment-tracking' ),    // Title
		'rz_order_tracking_metabox_post',   // Callback function
		'shop_order',         // Admin page (or post type)
		'side',         // Context
		'high'         // Priority
	);
}
/* Display the post meta box. */
function rz_order_tracking_metabox_post( $post ) {
	$shipment_tracking = get_post_meta( $post->ID, RZ_META_KEY_ITEM, true);

	echo "<div id='woocommerce-shipment-tracking'>";
	
	// Get order object
	$order = new WC_Order($post->ID);

	// Get order metadata
	$shipment_tracking_items = rz_get_post_metashipments_formatted($post->ID, '<a target="_blank" href="%s">%s</a>', '%s', 'F j, Y');

	// Shipment metadata is editable
	$editable = false;

	if( $order->has_status(array('processing', 'shipped') ) )
		$editable = true;

	rz_print_shipment_list( $shipment_tracking_items, $editable, $order );

	// Show metabox inputs if order status was in processing, on-hold or shipped
	if( $order->has_status( array('processing', 'shipped') ) ) {
		$shipment_email_sent = get_post_meta( $post->ID, RZ_META_KEY_EMAIL_SENT, true);
		rz_print_shipment_metabox($shipment_email_sent);
	}

	echo '</div>';
}
/* Save the meta box's post metadata. */
function rz_meta_save( $post_id, $post ) {

	/* Verify the nonce before proceeding. */
	if ( !isset( $_POST['rz_simple_shipment_sot_nonce'] ) || !wp_verify_nonce( $_POST['rz_simple_shipment_sot_nonce'], basename( __FILE__ ) ) )
		return $post_id;

	/* Get the post type object. */
	$post_type = get_post_type_object( $post->post_type );
	/* Check if the current user has permission to edit the post. */
	if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
		return $post_id;

	// TODO: metadata box should work by ajax when submitted
	// check posted data is not empty
	if( empty($_POST['tracking_provider']) && empty($_POST['tracking_number']) )
		return $post_id;
	
	/* Get the posted data and sanitize it for use as an HTML class. */
	$new_meta_value = Array(
		'tracking_provider' =>	( isset( $_POST['tracking_provider'] ) ? sanitize_html_class( $_POST['tracking_provider'] ) : '' ),
		'tracking_number' =>		( isset( $_POST['tracking_number'] ) ? sanitize_html_class( $_POST['tracking_number'] ) : '' ),
		'tracking_link' =>		( isset( $_POST['tracking_link'] ) ? sanitize_url( $_POST['tracking_link'] ) : '' ),
		'date_shipped' =>			( isset( $_POST['date_shipped'] ) ? sanitize_html_class( $_POST['date_shipped'] ) : '' )
	);

	add_post_meta( $post_id, RZ_META_KEY_ITEM, $new_meta_value, false );
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
		
		$shipment_data = rz_get_post_metashipments_formatted($post->ID, '<a target="_blank" href="%s">%s</a>');
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

	$shipment_data = rz_get_post_metashipments_formatted($order->get_id(), '<a target="_blank" href="%s">%s</a>');
	if ( !$shipment_data ) return;

	foreach( $shipment_data as $v ) {
		printf( '<b>%s</b> : %s<br>', $v['tracking_provider'], $v['tracking_number_linked'] );
	}
}


// Show tracking data in "My Account" > "View Order" detail page
add_action('woocommerce_view_order', 'rz_add_tracking_to_my_account_order_view');
function rz_add_tracking_to_my_account_order_view( $order_id ) {

	$shipment_data = rz_get_post_metashipments_formatted($order_id, '<a target="_blank" href="%s">%s</a>', '%s');
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
function rz_get_post_metashipments_formatted($order_id, $link_tpl = '<a href="%s">%s</a>', $date_tpl = 'on %s', $date_format = 'M j, Y') {
	
	$array = get_post_meta($order_id, RZ_META_KEY_ITEM, false);
	
	// If meta data was empty return empty array
	if ( empty($array) ) return false;

	foreach( $array as $k=>$v ) {

		// Check array format if not main return empty array
		if( !isset($v['tracking_provider']) || !isset($v['tracking_number']) || !isset($v['tracking_link']) || !isset($v['date_shipped']) ) return array();

		if( $v['tracking_link'] != '' ) {
			// Put tracking number in link
			$v['tracking_link'] = sprintf( $v['tracking_link'], $v['tracking_number'] );
			$v['tracking_number_linked'] = sprintf( $link_tpl, $v['tracking_link'], $v['tracking_number'] );
		} else {
			$v['tracking_number_linked'] = $v['tracking_number'];
		}

		if( $v['date_shipped'] != '' ) {
			$v['date_shipped_formatted'] = sprintf( $date_tpl, date_format( date_create($v['date_shipped']), $date_format) );
		} else {
			$v['date_shipped_formatted'] = $v['date_shipped'];
		}

		$array[$k] = $v;
	}

	return $array;
}

// Print shipment items
function rz_print_shipment_list($shipment_tracking_items, $editable, $order) {
	
	// if order status was not shipped or processing show note message
	if( !$order->has_status(array('shipped', 'processing')) )
		echo "<p><i>To update shipment tracking, order status should be 'Processing' or 'Shipped'.</i></p>";

	if( empty($shipment_tracking_items) ){
		echo "<p><b>No shipment tracking data found.</b></p>";

	} else {
?><ul class="order_notes">
	<?php foreach( $shipment_tracking_items as &$sh ): ?>
	<li class="note">
		<div class="note_content">
			<p><b><?php echo $sh['tracking_provider']; ?></b> <?php echo $sh['tracking_number_linked']; ?></p>
		</div>
		<p class="meta">
			Shipped on <time class="exact-date" datetime="<?php echo $sh['date_shipped']; ?>"><?php echo ( $sh['date_shipped_formatted'] ? $sh['date_shipped_formatted'] : 'no date' ) ?></time>
			<?php if($editable):?><a href="#" class="delete_note" role="button">Delete</a><?php endif; ?>
		</p>
	</li>
	<?php endforeach; ?>
</ul><?php
	}
}

// Print metabox form
function rz_print_shipment_metabox ($shipment_email_sent) {

?>
	<?php wp_nonce_field( basename( __FILE__ ), 'rz_simple_shipment_sot_nonce' ); ?>
 
	<p>
		<label for="tracking_provider"><?php _e( "Provider Name <sup>*</sup>:", 'wc-simple-shipment-tracking' ); ?></label>
		<br />
		<input class="widefat" type="text" name="tracking_provider" id="tracking_provider" size="30" />
	</p>
	<p>
		<label for="tracking_number"><?php _e( "Tracking Number <sup>*</sup>:", 'wc-simple-shipment-tracking' ); ?></label>
		<br />
		<input class="widefat" type="text" name="tracking_number" id="tracking_number" size="30" />
	</p>
	<p>
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
	<button type="submit" class="button save_order button-primary" name="add" value="Add"><?php _e('Add New Shipment Tracking')?></button>
<?php

}