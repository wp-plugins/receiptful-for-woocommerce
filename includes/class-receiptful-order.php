<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Receiptful_Order.
 *
 * Class to manage all order stuff.
 *
 * @class		Receiptful_Order
 * @since		1.1.6
 * @version		1.1.6
 * @author		Receiptful
 */
class Receiptful_Order {


	/**
	 * Constructor.
	 *
	 * @since 1.1.6
	 */
	public function __construct() {

		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'order_save_user_token' ), 10, 2 );

	}


	/**
	 * Save user token.
	 *
	 * Save the user token from the receiptful cookie at checkout.
	 * After save it will immediately be deleted. When deleted it will
	 * automatically re-generate a new one to track the new purchase flow.
	 *
	 * @since 1.1.6
	 *
	 * @param int 	$order_id	ID of the order that is being processed.
	 * @param array	$posted		List of $_POST values.
	 */
	public function order_save_user_token( $order_id, $posted ) {

		if ( isset( $_COOKIE['receiptful-token'] ) ) {
			update_post_meta( $order_id, '_receiptful_token', $_COOKIE['receiptful-token'] );
		}

	}

}
