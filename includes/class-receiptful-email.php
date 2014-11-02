<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Receiptful_Email.
 *
 * Admin class.
 *
 * @class       Receiptful_Email
 * @version     1.0.0
 * @author      Grow Development
 */
class Receiptful_Email {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->hooks();

	}


	/**
	 * Class hooks.
	 *
	 * @since 1.0.0
	 */
	public function hooks() {

		// Remove standard emails
		add_filter( 'woocommerce_email_classes', array( $this, 'update_woocommerce_email' ), 90 );
		add_action( 'init', array( $this, 'remove_wc_completed_email') );

		// Add hook to send new email
		//add_action( 'woocommerce_order_status_completed', array( $this, 'send_transactional_email' ) );
		add_action( 'woocommerce_order_status_pending_to_processing', array( $this, 'send_transactional_email' ) );
		add_action( 'woocommerce_order_status_on-hold_to_processing', array( $this, 'send_transactional_email' ) );

		// Save card last 4, card type and customer IP for sending with receipt
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'save_card_data' ), 90, 2 );

		// Add API endpoints
		add_action( 'woocommerce_api_loaded', array( $this, 'load_api_endpoints' ) );
		add_action( 'woocommerce_api_classes', array( $this, 'add_api_classes' ) );

		// Bypass API Authentication for custom endpoints
		add_action( 'woocommerce_api_check_authentication', array( $this, 'authenticate' ), 90 );

		// Add coupon if the Receiptful API returns an upsell
		add_action( 'receiptful_add_upsell', array($this, 'create_coupon') );

		// Add 'View Receipt' button to the My Account page
		add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'view_receipt_button' ), 9, 2 );

		// Add option to Order Actions meta box on the Edit Order admin page
		add_action('woocommerce_order_actions', array( $this, 'receiptful_order_actions' ));

		// Order Actions callbacks
		add_action('woocommerce_order_action_receiptful_send_receipt', array( $this, 'send_transactional_email' ), 60);

	}


	/**
	 * Remove the WooCommerce Completed Order and New Order emails and add
	 * Receiptful email in their place.
	 *
	 * @param array $emails
	 * @return array
	 */
	public function update_woocommerce_email( $emails ) {

		// Remove WC_Email_Customer_Processing_Order
		unset( $emails['WC_Email_Customer_Processing_Order'] );

		// Remove WC_Email_Customer_Completed_Order
		unset( $emails['WC_Email_Customer_Completed_Order'] );

		// Add the Receiptful Completed Order email
		$emails['WC_Email_Customer_Completed_Order']  = include( 'emails/class-receiptful-email-customer-new-order.php' );

		return $emails;
	}


	/**
	 * Remove the email being sent when order status is set to 'completed'
	 *
	 */
	public function remove_wc_completed_email() {

		$wc = WC();
		remove_action( 'woocommerce_order_status_completed' , array( $wc, 'send_transactional_email' ), 10, 10 );

	}


	/**
	 * Init the mailer and call our notification for completed order
	 *
	 */
	public function send_transactional_email() {

		WC()->mailer();
		$args = func_get_args();
		do_action_ref_array( 'receiptful_order_status_processing_notification', $args );
	}


	/**
	 * Save Card Data
	 *
	 */
	public function save_card_data( $order_id, $posted ){

		// TODO: try a different route
		// most gateways don't collect card type, but the number
		// save IP address

		return;
	}


	/**
	 * Authenticate
	 *
	 * This function bypasses the authentication for receiptful endpoints
	 *
	 * @since 1.0.0
	 * @param WP_User $user
	 * @return null|WP_Error|WP_User
	 */
	public function authenticate( $user ) {

		if ( '/receiptful-products' === substr( WC()->api->server->path, 0, 20) )
			return new WP_User( 0 );

		return $user;
	}


	/**
	 * Load API Endpoints
	 *
	 *
	 *
	 */
	public function load_api_endpoints(){

		include_once( 'api/class-receiptful-api-products.php' );

	}


	/**
	 * Add API Classes
	 *
	 * @param array $classes
	 * @return array
	 */
	public function add_api_classes( $classes ){

		array_push($classes, 'Receiptful_API_Products');

		return $classes;

	}


	/**
	 * Create a coupon when upsell data returned from Receiptful API
	 *
	 * @since 1.0.0
	 * @param array $data
	 * @return void
	 */
	public function create_coupon( $data ) {
		global $wpdb;

		$coupon_code = apply_filters( 'woocommerce_coupon_code', wc_clean( $data['couponCode'] ) );

		// Check for duplicate coupon codes
		$coupon_found = $wpdb->get_var( $wpdb->prepare( "
			SELECT $wpdb->posts.ID
			FROM $wpdb->posts
			WHERE $wpdb->posts.post_type = 'shop_coupon'
			AND $wpdb->posts.post_status = 'publish'
			AND $wpdb->posts.post_title = '%s'
		 ", $coupon_code ) );

		if ( $coupon_found ) {
			// duplicate
			//return new WP_Error( 'woocommerce_api_coupon_code_already_exists', __( 'The coupon code already exists', 'woocommerce' ), array( 'status' => 400 ) );
			return;
		}

		$expiry_date = date_i18n('c', strtotime( '+' . wc_clean( $data['expiryPeriod'] ) . ' day' ) );

		switch ( wc_clean( $data['couponType'] ) ) {
			case 1:
				$discount_type = 'fixed_cart';
				break;
			case 2:
				$discount_type = 'percent';
				break;
			default:
				$discount_type = 'fixed_cart';
		}

		$coupon_data = array(
			'type'                       => $discount_type,
			'amount'                     => wc_clean ( $data['amount'] ),
			'individual_use'             => 'yes',
			'product_ids'                => $data['products'],
			'exclude_product_ids'        => array(),
			'usage_limit'                => '1',
			'usage_limit_per_user'       => '1',
			'limit_usage_to_x_items'     => '',
			'usage_count'                => '',
			'expiry_date'                => $expiry_date,
			'apply_before_tax'           => 'yes',
			'free_shipping'              => 'no',
			'product_categories'         => array(),
			'exclude_product_categories' => array(),
			'exclude_sale_items'         => 'no',
			'minimum_amount'             => '',
			'maximum_amount'             => '',
			'customer_email'             => array(),
			''
		);

		$new_coupon = array(
			'post_title'   => $coupon_code,
			'post_content' => '',
			'post_status'  => 'publish',
			'post_author'  => get_current_user_id(),
			'post_type'    => 'shop_coupon',
			'post_excerpt' => isset( $data['title'] ) ? wc_clean( $data['title'] ) : '',
		);

		$id = wp_insert_post( $new_coupon, $wp_error = false );

		if ( is_wp_error( $id ) ) {
			return;
			//return new WP_Error( 'woocommerce_api_cannot_create_coupon', $id->get_error_message(), array( 'status' => 400 ) );
		}

		// set coupon meta
		update_post_meta( $id, 'discount_type', $coupon_data['type'] );
		update_post_meta( $id, 'coupon_amount', wc_format_decimal( $coupon_data['amount'] ) );
		update_post_meta( $id, 'individual_use', $coupon_data['individual_use'] );
		update_post_meta( $id, 'product_ids', implode( ',', array_filter( array_map( 'intval', $coupon_data['product_ids'] ) ) ) );
		update_post_meta( $id, 'exclude_product_ids', implode( ',', array_filter( array_map( 'intval', $coupon_data['exclude_product_ids'] ) ) ) );
		update_post_meta( $id, 'usage_limit', absint( $coupon_data['usage_limit'] ) );
		update_post_meta( $id, 'usage_limit_per_user', absint( $coupon_data['usage_limit_per_user'] ) );
		update_post_meta( $id, 'limit_usage_to_x_items', absint( $coupon_data['limit_usage_to_x_items'] ) );
		update_post_meta( $id, 'usage_count', absint( $coupon_data['usage_count'] ) );
		update_post_meta( $id, 'expiry_date', wc_clean( $coupon_data['expiry_date'] ) );
		update_post_meta( $id, 'apply_before_tax', wc_clean( $coupon_data['apply_before_tax'] ) );
		update_post_meta( $id, 'free_shipping', wc_clean( $coupon_data['free_shipping'] ) );
		update_post_meta( $id, 'product_categories', array_filter( array_map( 'intval', $coupon_data['product_categories'] ) ) );
		update_post_meta( $id, 'exclude_product_categories', array_filter( array_map( 'intval', $coupon_data['exclude_product_categories'] ) ) );
		update_post_meta( $id, 'exclude_sale_items', wc_clean( $coupon_data['exclude_sale_items'] ) );
		update_post_meta( $id, 'minimum_amount', wc_format_decimal( $coupon_data['minimum_amount'] ) );
		update_post_meta( $id, 'maximum_amount', wc_format_decimal( $coupon_data['maximum_amount'] ) );
		update_post_meta( $id, 'customer_email', array_filter( array_map( 'sanitize_email', $coupon_data['customer_email'] ) ) );

		return;

	}


	/**
	 * Prints the View Receipt button
	 *
	 * @since 1.0.0
	 * @param array $actions
	 * @param Object $order
	 * @return array $actions
	 */
	public function view_receipt_button( $actions, $order ) {

		$receipt_id = get_post_meta( $order->id, '_receiptful_receipt_id', true );
		$receiptful_web_link = get_post_meta( $order->id, '_receiptful_web_link', true);
		if ( $receipt_id && $receiptful_web_link ){
			// Id exists so remove old View button and add Receiptful button
			unset( $actions['view'] );

			$actions['receipt'] = array(
				'url'  => $receiptful_web_link['webview'],
				'name' => __( 'View Receipt', 'woocommerce' )
			);
		}

		return $actions;

	}


	/**
	 * Resend receipts in queue. Called from Cron.
	 *
	 */
	public static function resend_queue(){

		// Check queue
		$resend_queue = get_option( '_receiptful_resend_queue' );

		if ( is_array($resend_queue) && ( count( $resend_queue ) > 0 ) ) {

			foreach ( $resend_queue as $key=>$val ) {
				WC()->mailer();
				// the $val will be an Order (post) ID.
				$args = array( 0 => $val );
				do_action_ref_array( 'receiptful_order_status_processing_notification', $args );
				unset($resend_queue[$key]);
			}

			update_option( '_receiptful_resend_queue', $resend_queue );

		}

	}

	/**
	 * Display the Receiptful action in the Order Actions meta box drop down.
	 *
	 * @access public
	 * @param array $actions
	 * @return array $actions
	 */
	function receiptful_order_actions( $actions ) {

		if ( is_array( $actions ) ) {
			$actions['receiptful_send_receipt'] = __('Send receipt', 'receiptful');
		}

		return $actions;
	}

}
