<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Email' ) ) {
	return;
}

/**
 * Class RZ_Shipped_Email
 */

class RZ_Shipped_Email extends WC_Email {
	
	/**
	 * Set email defaults
	 */
	public function __construct() {
// 		error_log('__construct() called!');

		// Unique ID for custom email
		$this->id = 'rz_shipped_email';
		// Is a customer email
		$this->customer_email = true;
		
		// Title field in WooCommerce Email settings
		$this->title = __( 'Shipped Email', 'wc-simple-shipment-tracking' );
		// Description field in WooCommerce email settings
		$this->description = __( 'Shipped email is sent when an order processed for the customer who placed the order.', 'wc-simple-shipment-tracking' );
		// Default heading and subject lines in WooCommerce email settings
		$this->subject = apply_filters( 'rz_shipped_email_default_subject', __( 'Your order has been shipped', 'wc-simple-shipment-tracking' ) );
		$this->heading = apply_filters( 'rz_shipped_email_default_heading', __( 'Your Order Has Been Shipped', 'wc-simple-shipment-tracking' ) );
		
		// Email template file path
		$this->template_html  = 'emails/rz-customer-shipped-order.php';
		$this->template_plain = 'emails/plain/rz-customer-shipped-order.php';

		// Check if email template file exists in theme folder, if not, use plugin default template file
		$wc_template_base_in_theme = get_template_directory() . '/woocommerce/templates/';
		if( file_exists( $wc_template_base_in_theme . $this->template_html ) ) {
			$this->template_base = $wc_template_base_in_theme;

		} else {
			$this->template_base = plugin_dir_path( __FILE__ ) . 'templates/';
		}

		// Trigger email when order status changed to shipped
		// Action to which we hook onto to send the email.
		// add_action( 'woocommerce_order_status_changed', array( $this, 'myfunc_status_custom_notification' ), 10, 4 );
		
		// Call parent constructor to load any other defaults not explicity defined here
		parent::__construct();
	}

	// public function myfunc_register_email_action( $email_actions ) {
	// 	$email_actions[] = 'woocommerce_order_status_wc-shipped';
	// 	return $email_actions;
	// }

	public function myfunc_status_custom_notification( $order_id, $from_status, $to_status, $order ) {
		
		if( $order->has_status('shipped') ) {
	
			// Getting all WC_emails objects
			// $email_notifications = WC()->mailer()->get_emails();

			// Sending the customized email
			// $email_notifications['wc-shipped']->trigger( $order_id );
			$this->trigger( $order_id );
		}
	
	}




	/**
	 * Add shipped custom status to email actions list.
	*/
	// public function myfunc_register_email_action( $email_actions ) {
	// 	$email_actions[] = 'woocommerce_order_status_wc-shipped';
	// 	return $email_actions;
	// }

	/**
	 * Prepares email content and triggers the email
	 *
	 * @param int $order_id
	 */
	public function trigger( $order_id ) {

		// Bail if no order ID is present
		if ( ! $order_id )
			return;
		
		// Send welcome email only once and not on every order status change		
		// if ( ! get_post_meta( $order_id, RZ_META_KEY_EMAIL_SENT, true ) ) {
			
			// setup order object
			$this->object = new WC_Order( $order_id );
			
			// get order items as array
			$order_items = $this->object->get_items();
			//* Maybe include an additional check to make sure that the online training program account was created
			/* Uncomment and add your own conditional check
			$online_training_account_created = get_post_meta( $this->object->id, '_crwc_user_account_created', 1 );
			
			if ( ! empty( $online_training_account_created ) && false === $online_training_account_created ) {
				return;
			}
			*/
			/* Proceed with sending email */
			
			$this->recipient = $this->object->billing_email;
			// replace variables in the subject/headings
			$this->find[] = '{order_date}';
			$this->replace[] = date_i18n( woocommerce_date_format(), strtotime( $this->object->order_date ) );
			$this->find[] = '{order_number}';
			$this->replace[] = $this->object->get_order_number();
			if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
				return;
			}
			// All well, send the email
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			
			// add order note about the same
			$this->object->add_order_note( sprintf( __( '"%s" email sent to the customer.', 'wc-simple-shipment-tracking' ), $this->title ) );
			// Set order meta to indicate that the welcome email was sent
			update_post_meta( $this->object->id, RZ_META_KEY_EMAIL_SENT, 1 );
			
		// }
		
	}
	
	/**
	 * get_content_html function.
	 *
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html(
			$this->template_html,
			array(
				'order'				=> $this->object,
				'email_heading'	=> $this->get_heading(),
				'sent_to_admin'	=> false,
				'plain_text'		=> false,
				'email'				=> $this
			),
			'',
			$this->template_base
		);
	}
	/**
	 * get_content_plain function.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html(
			$this->template_plain,
			array(
				'order'				=> $this->object,
				'email_heading'	=> $this->get_heading(),
				'sent_to_admin'	=> false,
				'plain_text'		=> true,
				'email'				=> $this
			),
			'',
			$this->template_base
		);
	}
	/**
	 * Initialize settings form fields
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'    => array(
				'title'   => __( 'Enable/Disable', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => 'Enable this email notification',
				'default' => 'yes'
			),
			'subject'    => array(
				'title'       => __( 'Subject', 'woocommerce' ),
				'type'        => 'text',
				'description' => sprintf( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', $this->subject ),
				'placeholder' => '',
				'default'     => ''
			),
			'heading'    => array(
				'title'       => __( 'Email Heading', 'woocommerce' ),
				'type'        => 'text',
				'description' => sprintf( __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.' ), $this->heading ),
				'placeholder' => '',
				'default'     => ''
			),
			'email_type' => array(
				'title'       => __( 'Email type', 'woocommerce' ),
				'type'        => 'select',
				'description' => __( 'Choose which format of email to send.', 'woocommerce' ),
				'default'       => 'html',
				'class'         => 'email_type wc-enhanced-select',
				'options'     => array(
					'plain'	    => __( 'Plain text', 'woocommerce' ),
					'html' 	    => __( 'HTML', 'woocommerce' ),
					'multipart' => __( 'Multipart', 'woocommerce' ),
				)
			)
		);
	}
}
