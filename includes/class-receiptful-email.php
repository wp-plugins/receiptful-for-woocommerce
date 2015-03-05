<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Receiptful_Email.
 *
 * Admin class.
 *
 * @class		Receiptful_Email
 * @version		1.0.0
 * @author		Receiptful
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

		// Add hook to send new email
		//add_action( 'woocommerce_order_status_completed', array( $this, 'send_transactional_email' ) );
		add_action( 'woocommerce_order_status_pending_to_processing', array( $this, 'send_transactional_email' ) );
		add_action( 'woocommerce_order_status_on-hold_to_processing', array( $this, 'send_transactional_email' ) );
		add_action( 'woocommerce_order_status_pending_to_completed', array( $this, 'send_transactional_email' ) );

		// Save card last 4, card type and customer IP for sending with receipt
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'save_card_data' ), 90, 2 );

		// Add coupon if the Receiptful API returns an upsell
		add_action( 'receiptful_add_upsell', array( $this, 'create_coupon' ), 10, 2 );

		// Add 'View Receipt' button to the My Account page
		add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'view_receipt_button' ), 9, 2 );

		// Add option to Order Actions meta box on the Edit Order admin page
		add_action( 'woocommerce_order_actions', array( $this, 'receiptful_order_actions' ) );

		// Order Action callback
		add_action( 'woocommerce_order_action_receiptful_send_receipt', array( $this, 'send_transactional_email' ), 60);

	}


	/**
	 * WC Emails.
	 *
	 * Remove the WooCommerce Completed Order and New Order emails and add
	 * Receiptful email in their place.
	 *
	 * @since 1.0.0
	 *
	 * @param	array $emails	List of existing/registered WC emails.
	 * @return	array			List of modified WC emails.
	 */
	public function update_woocommerce_email( $emails ) {

		// Remove triggers
		remove_action( 'woocommerce_order_status_completed_notification', array( $emails['WC_Email_Customer_Completed_Order'], 'trigger' ) );
		remove_action( 'woocommerce_order_status_pending_to_processing_notification', array( $emails['WC_Email_Customer_Processing_Order'], 'trigger' ) );
		remove_action( 'woocommerce_order_status_pending_to_on-hold_notification', array( $emails['WC_Email_Customer_Processing_Order'], 'trigger' ) );

		// Remove WC_Email_Customer_Processing_Order
		unset( $emails['WC_Email_Customer_Processing_Order'] );

		// Remove WC_Email_Customer_Completed_Order
		unset( $emails['WC_Email_Customer_Completed_Order'] );

		// Add the Receiptful Completed Order email
		$emails['WC_Email_Customer_Completed_Order'] = include plugin_dir_path( __FILE__ ) . 'emails/class-receiptful-email-customer-new-order.php';

		return $emails;

	}


	/**
	 * Send mail.
	 *
	 * Init the mailer and call our notification for completed order.
	 *
	 * @since 1.0.0
	 */
	public function send_transactional_email() {

		WC()->mailer();
		$args = func_get_args();
		do_action_ref_array( 'receiptful_order_status_processing_notification', $args );

	}


	/**
	 * Save CC.
	 *
	 * Save the last 4 digits of the used credit card.
	 *
	 * @since 1.0.0
	 *
	 * @param int	$order_id	Order ID to save the card data to.
	 * @param array	$posted		List of posted data.
	 */
	public function save_card_data( $order_id, $posted ) {

		// TODO: try a different route
		// most gateways don't collect card type, but the number
		// save IP address

		return;

	}


	/**
	 * Create coupon.
	 *
	 * Create a coupon when upsell data returned from Receiptful API.
	 *
	 * @since 1.0.0
	 *
	 * @param	array	$data		List of data returned by the Receiptful API.
	 * @param	int		$order_id	ID of the order being processed.
	 * @return  int     $id         ID of new coupon
	 */
	public function create_coupon( $data, $order_id ) {

		$order				= wc_get_order( $order_id );
		$coupon_code		= apply_filters( 'woocommerce_coupon_code', wc_clean( $data['couponCode'] ) );
		$shipping_coupon	= 'no';
		$discount_type		= 'fixed_cart';

		// Check for duplicate coupon codes
		$coupon_found = wc_get_coupon_by_code( $coupon_code );

		// Return the coupon ID when it already exists
		if ( $coupon_found ) {
			return $coupon_found;
		}

		$expiry_date = date_i18n( 'Y-m-d', strtotime( '+' . wc_clean( $data['expiryPeriod'] ) . ' day' ) );

		if ( 'discountcoupon' == $data['upsellType'] ) {

			switch ( wc_clean( $data['couponType'] ) ) {
				case 1:
					$discount_type = 'fixed_cart';
				break;
				case 2:
					$discount_type = 'percent';
				break;
				default:
					$discount_type = 'fixed_cart';
				break;
			}

		} elseif ( 'shippingcoupon' == $data['upsellType'] ) {

			$shipping_coupon = 'yes';

		}

		$coupon_data = array(
			'type'							=> $discount_type,
			'amount'						=> isset( $data['amount'] ) ? wc_clean( $data['amount'] ) : '',
			'individual_use'				=> 'yes',
			'product_ids'					=> $data['products'],
			'exclude_product_ids'			=> array(),
			'usage_limit'					=> '1',
			'usage_limit_per_user'			=> '1',
			'limit_usage_to_x_items'		=> '',
			'usage_count'					=> '',
			'expiry_date'					=> $expiry_date,
			'apply_before_tax'				=> 'yes',
			'free_shipping'					=> $shipping_coupon,
			'product_categories'			=> array(),
			'exclude_product_categories'	=> array(),
			'exclude_sale_items'			=> 'no',
			'minimum_amount'				=> '',
			'maximum_amount'				=> '',
			'customer_email'				=> ! empty( $data['emailLimit'] ) ? array( $order->billing_email ) : array(),
		);

		$new_coupon = array(
			'post_title'	=> $coupon_code,
			'post_content'	=> '',
			'post_status'	=> 'publish',
			'post_author'	=> get_current_user_id(),
			'post_type'		=> 'shop_coupon',
			'post_excerpt'	=> isset( $data['title'] ) ? wc_clean( $data['title'] ) : '',
		);
		$id = wp_insert_post( $new_coupon );

		// set coupon meta
		update_post_meta( $id, 'discount_type', $coupon_data['type'] );
		update_post_meta( $id, 'coupon_amount', wc_format_decimal( $coupon_data['amount'] ) );
		update_post_meta( $id, 'individual_use', $coupon_data['individual_use'] );
		update_post_meta( $id, 'product_ids', '' );
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
		update_post_meta( $id, 'receiptful_coupon', 'yes' );
		update_post_meta( $id, 'receiptful_coupon_order', $order_id );

		return $id;

	}


	/**
	 * Online receipt.
	 *
	 * Prints the View Receipt button to view the receipt online.
	 *
	 * @since 1.0.0
	 *
	 * @param	array		$actions	List of existing actions (buttons).
	 * @param	WC_Order	$order		Order object of the current order line.
	 * @return	array					List of modified actions (buttons).
	 */
	public function view_receipt_button( $actions, $order ) {

		$receipt_id				= get_post_meta( $order->id, '_receiptful_receipt_id', true );
		$receiptful_web_link	= get_post_meta( $order->id, '_receiptful_web_link', true);

		if ( $receipt_id && $receiptful_web_link ){
			// Id exists so remove old View button and add Receiptful button
			unset( $actions['view'] );

			$actions['receipt'] = array(
				'url'	=> $receiptful_web_link['webview'],
				'name'	=> __( 'View Receipt', 'receiptful' )
			);
		}

		return $actions;

	}


	/**
	 * Resend queue.
	 *
	 * Resend receipts in queue. Called from Cron.
	 *
	 * @since 1.0.0
	 */
	public static function resend_queue(){

		// Check queue
		$resend_queue = get_option( '_receiptful_resend_queue' );

		if ( is_array( $resend_queue ) && ( count( $resend_queue ) > 0 ) ) {

			foreach ( $resend_queue as $key => $order_id ) {
				WC()->mailer();
				do_action_ref_array( 'receiptful_order_status_processing_notification', array( 0 => $order_id ) );
				$receiptful_email = new Receiptful_Email_Customer_New_Order();
				$response = $receiptful_email->trigger( $order_id );
				unset( $resend_queue[ $key ] );
			}

			update_option( '_receiptful_resend_queue', $resend_queue );

		}

	}


	/**
	 * Order actions.
	 *
	 * Display the Receiptful action in the Order Actions meta box drop down.
	 *
	 * @since 1.0.0
	 *
	 * @param	array $actions	List of existing order actions.
	 * @return	array			List of modified order actions.
	 */
	public function receiptful_order_actions( $actions ) {

		if ( is_array( $actions ) ) {
			$actions['receiptful_send_receipt'] = __( 'Send receipt', 'receiptful' );
		}

		return $actions;

	}


}
