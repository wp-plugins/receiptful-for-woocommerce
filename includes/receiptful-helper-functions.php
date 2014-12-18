<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Coupon by code.
 *
 * Get the coupon ID by the coupon code.
 *
 * @param 	string 		$coupon_code 	Code that is used as coupon code.
 * @return	int|bool					WP_Post ID if coupon is found, otherwise False.
 */
function wc_get_coupon_by_code( $coupon_code ) {

	global $wpdb;

	$coupon_id = $wpdb->get_var( $wpdb->prepare( apply_filters( 'woocommerce_coupon_code_query', "
		SELECT ID
		FROM $wpdb->posts
		WHERE post_title = %s
		AND post_type = 'shop_coupon'
		AND post_status = 'publish'
	" ), $coupon_code ) );

	 if ( ! $coupon_id ) {
	 	return false;
	 } else {
	 	return $coupon_id;
	 }

}


if ( ! function_exists( 'wc_get_random_products' ) ) {

	/**
	 * Random products.
	 *
	 * Get random WC product IDs.
	 *
	 * @param 	int 	$limit	Number of products to return
	 * @return 	array			List of random product IDs.
	 */
	function wc_get_random_products( $limit = 2 ) {

		$product_args = array(
			'fields'			=> 'ids',
			'post_type'			=> 'product',
			'post_status'		=> 'publish',
			'posts_per_page'	=> $limit,
			'orderby'			=> 'rand',
			'meta_query'		=> array(
				array(
					'meta_key'	=> '_thumbnail_id',
					'compare'	=> 'EXISTS',
				),
			)
		);
		$products = get_posts( $product_args );

		return $products;

	}

}


/************************************************
 * Compatibility functions.
 *
 * @author		Receiptful
 * @version		1.0.0
 * @since		1.0.1
 ***********************************************/

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
