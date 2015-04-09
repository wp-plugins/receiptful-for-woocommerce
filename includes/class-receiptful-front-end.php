<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Receiptful_Front_End.
 *
 * Class to manage all front-end stuff.
 *
 * @class		Receiptful_Front_End
 * @since		1.1.4
 * @version		1.1.4
 * @author		Receiptful
 */
class Receiptful_Front_End {


	/**
	 * Constructor.
	 *
	 * @since 1.1.4
	 */
	public function __construct() {

		// Track product pageviews
		add_action( 'wp_footer', array( $this, 'product_page_tracking' ) );

	}


	/**
	 * Product page tracking.
	 *
	 * Track the product pageview for better product recommendations.
	 *
	 * @since 1.1.4
	 */
	public function product_page_tracking() {

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


}
