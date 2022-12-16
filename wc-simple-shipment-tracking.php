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
	register_post_status( 'wc-shippeddd', array(
		'label'								=> _x('Shipped (test)', 'Custom order status for shipped!','wc-simple-shipment-tracking'),
		'public'								=> true,
		'exclude_from_search'			=> false,
		'show_in_admin_all_list'		=> true,
		'show_in_admin_status_list'	=> true,
		'label_count'						=> _n_noop( 'Shipped <span class="count">(%s)</span>', 'Shipped <span class="count">(%s)</span>', 'wc-simple-shipment-tracking' )
	));
}

/*
// Add 'Shipped' to Order Actions Metabox on Order Page
add_action( 'woocommerce_order_actions', 'rz_add_order_meta_box_actions' );
// Add Order action to Order action meta box
function rz_add_order_meta_box_actions($actions)
{
   $actions['wc-shippeddd'] = __( 'Resend shipped notification (test)', 'wc-simple-shipment-tracking');
   return $actions; 
}
*/

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
			$new_order_statuses['wc-shippeddd'] = _x('Shipped (test)', 'Custom order status for shipped!','wc-simple-shipment-tracking');    
		}
	}
	return $new_order_statuses;
}

// Adding custom status 'awaiting-delivery' to admin order list bulk dropdown
add_filter( 'bulk_actions-edit-shop_order', 'custom_dropdown_bulk_actions_shop_order', 20, 1 );
function custom_dropdown_bulk_actions_shop_order( $actions ) {
    $actions['mark_shippeddd'] = __( 'Mark Shipped', 'wc-simple-shipment-tracking' );
    return $actions;
}


// Create Callback Function if Order Status is Marked as Shipped

/*
// Add callback if Shipped action called
// The following function written on the ‘woocommerce_order_action_massimo_shipped’ hook will be called when ‘Shipped’ has been selected from the order actions drop-down list
add_action( 'woocommerce_order_action_wc-shippeddd', 'rz_order_shipped_callback');
function rz_order_shipped_callback($order)
{
	error_log(__METHOD__);
	$order->update_status('wc-shippeddd', 'Order status changed by "Order Actions".');
	
	// This function used for when we sent a shipped email before, but need to send the shipped email again.
	// So when this function selected from Order Actions dropdown we update the order status to shipped and send the email in anyway (if status updated and email sent before)

	//Here order object is sent as parameter
	//Add code for processing here
}

//Add callback if Status changed to Shipping
add_action('woocommerce_order_status_wc-shippeddd', 'rz_wwww_order_status_shipped_callback');
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
// FIXME: This is not a proper way to call the function, why should I call it directly here????
rz_add_actions_metabox(0);
function rz_add_actions_metabox ( $order ) {
	// if( !$order->has_status('processing')) return false;

	add_action( 'post.php', 'rz_order_tracking_metabox_setup' );
	add_action( 'load-post.php', 'rz_order_tracking_metabox_setup' );
	add_action( 'load-post-new.php', 'rz_order_tracking_metabox_setup' );
}
/* Meta box setup function. */
function rz_order_tracking_metabox_setup() {

	/* Add meta boxes on the 'add_meta_boxes' hook. */
	add_action( 'add_meta_boxes', 'rz_order_tracking_metabox_add' );

	/* Save post meta on the 'save_post' hook. */
	add_action( 'save_post', 'rz_order_tracking_meta_save', 10, 2 );
}
/* Create one or more meta boxes to be displayed on the post editor screen. */
function rz_order_tracking_metabox_add() {

	add_meta_box(
		'simple-shipment-tracking-class',      // Unique ID
		esc_html__( 'Shipment Tracking', 'wc-simple-shipment-tracking' ),    // Title
		'rz_order_tracking_metabox_post',   // Callback function
		'shop_order',         // Admin page (or post type)
		'side',         // Context
		'core'         // Priority
	);
}
/* Display the post meta box. */
function rz_order_tracking_metabox_post( $post ) {
	$shipment_tracking = get_post_meta( $post->ID, RZ_META_KEY_ITEM, true);
?>
	<?php wp_nonce_field( basename( __FILE__ ), 'rz_simple_shipment_sot_nonce' ); ?>
 
	<p>
		<label for="tracking_provider"><?php _e( "Provider Name:", 'wc-simple-shipment-tracking' ); ?></label>
		<br />
		<input class="widefat" type="text" name="tracking_provider" id="tracking_provider" value="<?php if(isset($shipment_tracking['tracking_provider'])) echo esc_attr( $shipment_tracking['tracking_provider'] ); ?>" size="30" />
	</p>
	<p>
		<label for="tracking_number"><?php _e( "Tracking Number:", 'wc-simple-shipment-tracking' ); ?></label>
		<br />
		<input class="widefat" type="text" name="tracking_number" id="tracking_number" value="<?php if(isset($shipment_tracking['tracking_number'])) echo esc_attr( $shipment_tracking['tracking_number'] ); ?>" size="30" />
	</p>
	<p>
		<label for="tracking_link"><?php _e( "Tracking Link:", 'wc-simple-shipment-tracking' ); ?></label>
		<br />
		<input class="widefat" type="text" name="tracking_link" id="tracking_link" placeholder="https://xyz.com/?trackingNum=%s" value="<?php if(isset($shipment_tracking['tracking_link'])) echo esc_attr( $shipment_tracking['tracking_link'] ); ?>" size="30" />
		<span class="components-form-token-field__help">Use %s for tracking number's place in link!</span>
	</p>
	<p>
		<label for="date_shipped"><?php _e( "Date Shipped:", 'wc-simple-shipment-tracking' ); ?></label>
		<br />
		<input class="widefat" type="date" name="date_shipped" id="date_shipped" value="<?php if(isset($shipment_tracking['date_shipped'])) echo esc_attr( $shipment_tracking['date_shipped'] ); ?>" size="30" />
	</p>
<?php
}
/* Save the meta box's post metadata. */
function rz_order_tracking_meta_save( $post_id, $post ) {

	/* Verify the nonce before proceeding. */
	if ( !isset( $_POST['rz_simple_shipment_sot_nonce'] ) || !wp_verify_nonce( $_POST['rz_simple_shipment_sot_nonce'], basename( __FILE__ ) ) )
		return $post_id;

	/* Get the post type object. */
	$post_type = get_post_type_object( $post->post_type );

	/* Check if the current user has permission to edit the post. */
	if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
		return $post_id;

	/* Get the posted data and sanitize it for use as an HTML class. */
	$new_meta_value = Array(
		'tracking_provider' =>	( isset( $_POST['tracking_provider'] ) ? sanitize_html_class( $_POST['tracking_provider'] ) : '' ),
		'tracking_number' =>		( isset( $_POST['tracking_number'] ) ? sanitize_html_class( $_POST['tracking_number'] ) : '' ),
		'tracking_link' =>		( isset( $_POST['tracking_link'] ) ? sanitize_url( $_POST['tracking_link'] ) : '' ),
		'date_shipped' =>			( isset( $_POST['date_shipped'] ) ? sanitize_html_class( $_POST['date_shipped'] ) : '' )
	);

	/* Get the meta value of the custom field key. */
	$meta_value = get_post_meta( $post_id, RZ_META_KEY_ITEM, true );

	/* If a new meta value was added and there was no previous value, add it. */
	if ( $new_meta_value['tracking_number'] && $meta_value == '' )
		add_post_meta( $post_id, RZ_META_KEY_ITEM, $new_meta_value, true );

	/* If the new meta value does not match the old value, update it. */
	elseif ( $new_meta_value['tracking_number'] && $new_meta_value['tracking_number'] != $meta_value['tracking_number'] )
		update_post_meta( $post_id, RZ_META_KEY_ITEM, $new_meta_value );

	/* If there is no new meta value but an old value exists, delete it. */
	elseif ( '' == $new_meta_value['tracking_number'] && $meta_value!='' )
		delete_post_meta( $post_id, RZ_META_KEY_ITEM, $meta_value );
}

// Registering a Custom WooCommerce Email
// Filtering the emails and adding our own email to WooCommerce email settings
add_filter( 'woocommerce_email_classes', 'rz_shipped_email_class', 90, 1 );
function rz_shipped_email_class( $email_classes ) {
	require_once plugin_dir_path( __FILE__ ) . '/rz_shipped_email.php';

	$email_classes['wc-shippeddd'] = new RZ_Shipped_Email(); // add to the list of email classes that WooCommerce loads

	return $email_classes;
}

add_action( 'woocommerce_order_status_changed', 'rz_status_custom_notification', 10, 4 );
function rz_status_custom_notification( $order_id, $from_status, $to_status, $order ) {
	
	if( $order->has_status( 'shippeddd' ) ) {

		// Getting all WC_emails objects
		$email_notifications = WC()->mailer()->get_emails();

		// Sending the customized email
		$email_notifications['wc-shippeddd']->trigger( $order_id );
	}

}
