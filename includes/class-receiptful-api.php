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
	 * Send receipt.
	 *
	 * Send the Receiptful receipt based on $args.
	 *
	 * @since 1.0.0
	 *
	 * @param 	array	$args 	API call arguments.
	 * @return	array			API response.
	 */
	public function receipt( $args = array() ) {

		$response = $this->api_call( '/receipts', $args );

		return $response;

	}


	/**
	 * Resend receipt.
	 *
	 * Resend the previously send Receiptful receipt.
	 *
	 * @since 1.0.0
	 *
	 * @param int 	$receipt_id	Receiptful receipt ID, as retreived from original API call.
	 * @param array $args		API call arguments.
	 */
	public function resend_receipt( $receipt_id, $args = array() ) {

		$this->api_call( '/receipts/' . $receipt_id . '/send', $args );

	}


	/**
	 * Coupon used.
	 *
	 * API call to register the usage of a (Receiptful created) coupon).
	 *
	 * @since 1.0.1
	 *
	 * @param 	string 	$coupon_id 	ID of the coupon used.
	 * @param 	array	$args		API arguments.
	 * @return	array				API response.
	 */
	public function coupon_used( $coupon_id, $args = array() ) {

		$response = $this->api_put( '/coupons/' . $coupon_id . '/use', $args );

		return $response;

	}


	/**
	 * API Call
	 *
	 * Send a Receiptful API call based on method and arguments.
	 *
	 * @since 1.0.0
	 *
	 * @param 	string 	$method				API method to call.
	 * @param 	array 	$args				Arguments to pass in the API call.
	 * @return 	array 	$response|WP_Error 	API response.
	 */
	protected function api_call( $method, $args = array() ){

		$url 		= $this->url;
		$headers 	= array( 'X-ApiKey' => $this->api_key );

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
	 * API PUT call. Used to track coupon usage.
	 *
	 * @since 1.0.1
	 *
	 * @param 	string 	$method				API method to call.
	 * @param 	array 	$args				Arguments to pass in the API call.
	 * @return 	array 	$response|WP_Error 	API response.
	 */
	protected function api_put( $method, $args = array() ){

		$url 		= $this->url;
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
