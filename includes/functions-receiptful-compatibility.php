<?php
/**
 * Compatibility functions.
 *
 * @author		Receiptful
 * @version		1.0.0
 * @since		1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 *  Define function to make plugin compatible with WooCommerce 2.1.x
 */
if ( ! function_exists( 'wc_get_product' ) ) {

	function wc_get_product( $product_id ) {

		return get_product( $product_id );
	}

}

/**
 *  Define function to make plugin compatible with WooCommerce 2.1.x
 */
if ( ! function_exists( 'wc_get_order' ) ) {

	function wc_get_order( $order ) {

		$order_id = 0;

		if ( $order instanceof WP_Post ) {
			$order_id = $order->ID;
		} elseif ( $order instanceof WC_Order ) {
			$order_id = $order->id;
		} elseif ( is_numeric( $order ) ) {
			$order_id = $order;
		}

		return new WC_Order( $order_id );
	}
}
