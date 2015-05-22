<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Receiptful_Front_End.
 *
 * Class to manage all front-end stuff.
 *
 * @class		Receiptful_Front_End
 * @since		1.1.4
 * @version		1.1.6
 * @author		Receiptful
 */
class Receiptful_Front_End {


	/**
	 * Constructor.
	 *
	 * @since 1.1.4
	 */
	public function __construct() {

		// Track pageviews
		add_action( 'wp_footer', array( $this, 'page_tracking' ) );

		// Delete user token
		add_action( 'woocommerce_thankyou', array( $this, 'reset_user_token_cookie' ) );

	}


	/**
	 * Product page tracking.
	 *
	 * Track the product pageview for better product recommendations.
	 *
	 * @since 1.1.4
	 */
	public function product_page_tracking() {

		return _deprecated_function( __METHOD__, '1.1.6', 'page_tracking' );

		if ( ! is_singular( 'product' ) ) {
			return;
		}

		$public_user_key 	= Receiptful()->api->get_public_user_key();
		$product_id 		= get_the_ID();
		$customer 			= is_user_logged_in() ? get_current_user_id() : '';

		// Bail if public user key is empty/invalid
		if ( ! $public_user_key ) {
			return false;
		}

		?><script type='text/javascript'>
			document.addEventListener('DOMContentLoaded', function(event) {
				if ( typeof Receiptful !== 'undefined' ) {
					Receiptful.init({
						user: '<?php echo esc_js( $public_user_key ); ?>',
						product: '<?php echo esc_js( $product_id ); ?>',
						customer: '<?php echo esc_js( $customer ); ?>'
					});
				}
			});
		</script><?php

	}


	/**
	 * Page tracking.
	 *
	 * Track the pageviews for better product recommendations.
	 *
	 * @since 1.1.6
	 */
	public function page_tracking() {

		$public_user_key 	= Receiptful()->api->get_public_user_key();
		$product_id 		= 'product' == get_post_type( get_the_ID() ) ? get_the_ID() : null;
		$customer 			= is_user_logged_in() ? get_current_user_id() : '';
		$cart				= WC()->cart->get_cart();
		$product_ids		= array_values( wp_list_pluck( $cart, 'product_id' ) );

		// Bail if public user key is empty/invalid
		if ( ! $public_user_key ) {
			return false;
		}

		?><script type='text/javascript'>
			document.addEventListener('DOMContentLoaded', function(event) {
				if ( typeof Receiptful !== 'undefined' ) {
					Receiptful.init({
						user: '<?php echo esc_js( $public_user_key ); ?>',
						product: '<?php echo esc_js( $product_id ); ?>',
						cart: '<?php echo esc_js( implode( ',', $product_ids ) ); ?>',
						customer: '<?php echo esc_js( $customer ); ?>',
						recommend: <?php echo 'yes' == get_option( 'receiptful_enable_recommendations', false ) ? true : false; ?>
					});
				}
			});
		</script><?php

	}


	/**
	 * Delete user token.
	 *
	 * Delete the receiptful user token cookie after checkout. When deleted
	 * it will automatically re-generate a new one to track the new purchase flow.
	 *
	 * @since 1.1.6
	 */
	public function reset_user_token_cookie( $order_id ) {

		?><script type='text/javascript'>
			document.addEventListener('DOMContentLoaded', function(event) {
				if ( typeof Receiptful !== 'undefined' ) {
					Receiptful.docCookies.removeItem('receiptful-token', '/');
				}
			});
		</script><?php

	}


}
