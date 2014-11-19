<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Receiptful_Api.
 *
 *
 * @class       Receiptful_Api
 * @version     1.0.0
 * @author      Receiptful
 */
class Receiptful_Api {

	/**
	 * Receiptful API key
	 *
	 * @since 1.0.0
	 * @var $api_key
	 */
	public $api_key;

	/**
	 * URL for Receiptful
	 *
	 * @since 1.0.0
	 * @var $url
	 */
	protected $url = 'https://app.receiptful.com/api/v1';


	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->api_key = get_option( 'receiptful_api_key' );
	}


	/**
	 * Send new receipt
	 *
	 * @since 1.0.0
	 * @param array $args
	 * @return array $response
	 */
	public function receipt( $args = array() ) {

		$response = $this->api_call( '/receipts', $args );

		return $response;
	}


	/**
	 * Resend receipt
	 *
	 * @param int $receipt_id
	 * @param array $args
	 */
	public function resend_receipt( $receipt_id, $args = array() ) {

		$this->api_call( '/receipts/' . $receipt_id . '/send' , $args, $receipt_id );

	}


	/**
	 * Coupon used.
	 *
	 * API call to register the usage of a (Receiptful created) coupon).
	 *
	 * @since 1.0.0
	 *
	 * @param string 	$coupon_id ID of the coupon used.
	 * @param array		$args
	 */
	public function coupon_used( $coupon_id, $args = array() ) {

		$response = $this->api_put( '/coupons/' . $coupon_id . '/use', $args );

		return $response;

	}


	/**
	 * API Call
	 *
	 * @since 1.0.0
	 * @access protected
	 * 
	 * @param string $method
	 * @param array $args
	 * @return array $response|WP_Error $response
	 */
	protected function api_call( $method, $args = array() ){

		$url = $this->url;

		$headers	= array( 'X-ApiKey' => $this->api_key );

		$api_response = wp_remote_post( $url . $method , array(
				'method' 		=> 'POST',
				'timeout' 		=> 45,
				'redirection' 	=> 5,
				'httpversion' 	=> '1.0',
				'blocking' 		=> true,
				'headers' 		=> $headers,
				'body' 			=> $args,
				'cookies' 		=> array()
			)
		);

		if ( is_wp_error( $api_response ) ) {
			return $api_response;
		} else {
			$response['response']   = $api_response['response'];
			$response['body']       = $api_response['body'];
			return $response;
		}

	}


	/**
	 * API PUT
	 *
	 * @since 1.0.0
	 * @param string $method
	 * @param array $args
	 * @return array $response|WP_Error $response
	 */
	private function api_put( $method, $args = array() ){

		$url = $this->url;

		$headers	= array( 'X-ApiKey' => $this->api_key );

		$api_response = wp_remote_request( $url . $method , array(
				'method' 		=> 'PUT',
				'timeout' 		=> 45,
				'redirection' 	=> 5,
				'httpversion' 	=> '1.0',
				'blocking' 		=> true,
				'headers' 		=> $headers,
				'body' 			=> $args,
				'cookies' 		=> array()
			)
		);

		if ( is_wp_error( $api_response ) ) {
			return $api_response;
		} else {
			$response['response']   = $api_response['response'];
			$response['body']       = $api_response['body'];
			return $response;
		}

	}


}
