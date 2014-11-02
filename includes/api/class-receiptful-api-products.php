<?php
/**
 * Receiptful API Product class
 *
 * @author      Receiptful
 * @category    API
 * @package     Receiptful/API
 * @since       1.0.0
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Receiptful_API_Products extends WC_API_Resource {

	/** @var string $base the route base */
	protected $base = '/receiptful-products';

	/**
	 * Register the routes for this class
	 *
	 * GET /receiptful-products/<id>
	 *
	 * @since 1.0.0
	 * @param array $routes
	 * @return array
	 */
	public function register_routes( $routes ) {

		# GET /receiptful-products/<id>
		$routes[ $this->base . '/(?P<id>\d+)' ] = array(
			array( array( $this, 'get_products' ) ),
		);

		return $routes;
	}

	/**
	 * Return Suggested Products based on supplied product ID
	 *
	 * @since 1.0.0
	 * @param string $id
	 * @return array
	 */
	public function get_products( $id ) {

		$id = $this->validate_request( $id, 'product', 'read' );

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		$product = wc_get_product( $id );

		// get two related products using WooCommerce method
		$related_ids = $product->get_related( 2 );

		$related_products = array();
		foreach ( $related_ids as $related_id ){

			$related_product = wc_get_product( $related_id );

			$new_related = array(
				'title' 	=> $related_product->get_title(),					// Product Name
				'url' => get_permalink( $related_product->id ),				// Product URL
				'image' => $this->get_image_url( $related_product->id ),	// Image URL
				'description' => $related_product->post->post_content,		// Description
				'price' => $related_product->get_price(),					// Price
			);

			$related_products[] = $new_related;

		}

		return array( 'products' => $related_products );


	}


	/**
	 * Validate the request by checking:
	 *
	 * 1) the ID is a valid integer
	 * 2) the ID returns a valid post object and matches the provided post type
	 *
	 * @since 1.0.0
	 * @param string|int $id the post ID
	 * @return int|WP_Error valid post ID or WP_Error if any of the checks fails
	 */
	protected function validate_request( $id ) {

		$resource_name = 'product';

		$id = absint( $id );

		// validate ID
		if ( empty( $id ) )
			return new WP_Error( "woocommerce_api_invalid_{$resource_name}_id", sprintf( __( 'Invalid %s ID', 'woocommerce' ), 'product' ), array( 'status' => 404 ) );

		// only custom post types have per-post type/permission checks
		$post = get_post( $id );

		// for checking permissions, product variations are the same as the product post type
		$post_type = ( 'product_variation' === $post->post_type ) ? 'product' : $post->post_type;

		// validate post type
		if ( 'product' !== $post_type )
			return new WP_Error( "woocommerce_api_invalid_{$resource_name}", sprintf( __( 'Invalid %s', 'woocommerce' ), $resource_name ), array( 'status' => 404 ) );


		return $id;
	}


	/**
	 * Get URL for related product's Featured Image (thumbnail)
	 *
	 * Default size will be 'shop_thumbnail'
	 *
	 * @param $id
	 * @return string URL of the image
	 */
	protected function get_image_url( $id ) {

		$image	= '';

		if ( has_post_thumbnail( $id ) ) {

			$image_id = get_post_thumbnail_id( $id );
			$image = wp_get_attachment_image_src( $image_id );
			return $image[0];

		} elseif ( ( $parent_id = wp_get_post_parent_id( $id ) ) && has_post_thumbnail( $parent_id ) ) {

			$image_id = get_post_thumbnail_id( $parent_id );
			$image = wp_get_attachment_image_src( $image_id );
			return $image[0];

		}

		return $image;

	}


}