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

		// Save Receiptful user token on checkout
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'order_save_user_token' ), 10, 2 );

		// Check product stock, if empty update product
		add_action( 'woocommerce_reduce_order_stock', array( $this, 'maybe_update_products' ), 10, 1 );

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


	/**
	 * Update products.
	 *
	 * Maybe send a update to Receiptful. Check if the product is out-of-stock,
	 * when it is, a update will be send to Receiptful to make sure the product
	 * is set to 'hidden'.
	 *
	 * @since 1.1.9
	 *
	 * @param	WC_Order $order Order object.
	 */
	public function maybe_update_products( $order ) {

		foreach ( $order->get_items() as $item ) {

			if ( $item['product_id'] > 0 ) {
				$_product = $order->get_product_from_item( $item );

				if ( $_product && $_product->exists() && $_product->managing_stock() ) {
					if ( ! $_product->is_in_stock() ) {
						Receiptful()->products->update_product( $_product->id );
					}
				}

			}

		}

	}


}
