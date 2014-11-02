<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Receiptful_Email_Customer_New_Order' ) ) :

	/**
	 * Customer New Order Email
	 *
	 * Emails are sent to the customer when the order is placed and usual indicates that the order has been shipped.
	 *
	 * @class 		Receiptful_Email_Customer_New_Order
	 * @version		1.0.0
	 * @package		Receiptful/Classes/Emails
	 * @author 		Receiptful
	 * @extends 	WC_Email
	 */
	class Receiptful_Email_Customer_New_Order extends WC_Email {

		/**
		 * Constructor
		 */
		function __construct() {

			$this->id 				= 'customer_new_order';
			$this->title 			= __( 'Receiptful New Order', 'woocommerce' );
			$this->description		= __( 'Receiptful will send a new order receipt when the order is placed.', 'woocommerce' );

			// Triggers for this email
			add_action( 'receiptful_order_status_processing_notification', array( $this, 'trigger' ) );

			// Call parent constuctor
			parent::__construct();
		}

		/**
		 * trigger function.
		 *
		 * @access public
		 * @param int $order_id
		 * @return void
		 */
		function trigger( $order_id ) {

			if ( $order_id ) {

				$order = wc_get_order( $order_id );

				$receiptful_id = get_post_meta( $order_id, '_receiptful_receipt_id', true );

				if ( ! $receiptful_id == '' ) {

					// receipt exits so resend
					$order_args = array();
					$response = Receiptful()->api->resend_receipt( $receiptful_id, $order_args );

					if ( $response['response']['code'] == '201' ) {

						$order->add_order_note( 'Customer receipt resent via Receiptful.' );

						$body = json_decode( $response ['body'], true );
						//TODO: add better logging?

					} else {
						$order->add_order_note( 'Error resending customer receipt sent via Receiptful.'
												. "\n" . "Error Code: " . $response['response']['code']
												. "\n" . "Error Message: " . $response['response']['message'] );
					}

					return;
				}

				// Sending new receipt
				$items = array();

				foreach ( $order->get_items() as $item ) {

					$product_amount = $item['line_subtotal'] / $item['qty'];

					$items[] = array(
						'reference'     => $item['product_id'],
						'description'   => $item['name'],
						'quantity'      => $item['qty'],
						'amount'        => $product_amount
					);

				}

				// get all the subtotals that can include
				// shipping, tax, discount
				$subtotals = array();
				$tax_display = $order->tax_display_cart;

				if ( $order->get_cart_discount() > 0 ) {
					$subtotals[] = array( 'description' => __( 'Cart Discount', 'woocommerce') , 'amount' => number_format( (float)  $order->get_cart_discount(), 2, '.', '')  );
				}

				if ( $order->order_shipping > 0 ) {
					$subtotals[] = array( 'description' => __( 'Shipping', 'woocommerce') , 'amount' => number_format( (float) $order->order_shipping, 2, '.', '')  );
				}

				if ( $order->order_shipping_tax > 0 ){
					$subtotals[] = array( 'description' => __( 'Shipping Tax', 'woocommerce') , 'amount' => number_format( (float) $order->order_shipping_tax, 2, '.', '')  );
				}
				if ( $fees = $order->get_fees() )

					foreach( $fees as $id => $fee ) {

						if ( apply_filters( 'woocommerce_get_order_item_totals_excl_free_fees', $fee['line_total'] + $fee['line_tax'] == 0, $id ) ) {
							continue;
						}

						if ( 'excl' == $tax_display ) {
							$subtotals[] = array( 'description' => $fee['name'] , 'amount' => number_format((float) $fee['line_total'], 2, '.', '') );

						} else {
							$subtotals[] = array( 'description' => $fee['name'] ,  'amount' => number_format( (float) $fee['line_total'] + $fee['line_tax'], 2, '.', '') );

						}
					}

				// Tax for tax exclusive prices
				if ( 'excl' == $tax_display ) {

					if ( get_option( 'woocommerce_tax_total_display' ) == 'itemized' ) {

						foreach ( $order->get_tax_totals() as $code => $tax ) {
							$subtotals[] = array( 'description' => $tax->label, 'amount' => number_format((float)$tax->amount, 2, '.', '') );
						}

					} else {

						$subtotals[] = array( 'description' => WC()->countries->tax_or_vat() , 'amount' => number_format((float)$order->get_total_tax(), 2, '.', '') );

					}
				}

				if ( $order->get_order_discount() > 0 ) {

					$subtotals[] = array( 'description' => __( 'Order Discount:', 'woocommerce' ) , 'amount' => '-' . number_format((float)$order->get_order_discount(), 2, '.', '') );

				}


				// These values are added to the order at checkout if available.
				// If not recorded then emtpy string will be sent.
				$card_type      = isset( $order->receiptful_card_type ) ? $order->receiptful_card_type : '' ;
				$last4          = isset( $order->receiptful_last4 ) ? $order->receiptful_last4 : '';
				$customer_ip    = isset( $order->receitpful_customer_ip ) ? $order->receitpful_customer_ip : '';

				$order_args = array(

					'reference'  => ltrim( $order->get_order_number(), _x( '#', 'hash before order number', 'woocommerce' ) ),
					'currency'   => get_woocommerce_currency(),
					'amount'     => number_format((float) $order->get_total(), 2, '.', '') ,
					'to'         => $order->billing_email,
					'from'       => $this->get_from_address(),
					'card'       => array(
						'type'  => $card_type,
						'last4' => $last4
					),
					'items'      => $items,
					'subtotals'  => $subtotals,
					'customerIp' => $customer_ip,
					'billing'    => array(
						'address' => array(
							'firstName'    => $order->billing_first_name,
							'lastName'     => $order->billing_last_name,
							'company'      => $order->billing_company,
							'addressLine1' => $order->billing_address_1,
							'addressLine2' => $order->billing_address_2,
							'city'         => $order->billing_city,
							'state'        => $order->billing_state,
							'postcode'     => $order->billing_postcode,
							'country'      => $order->billing_country,
						),
						'phone'   => $order->billing_phone,
						'email'   => $order->billing_email
					),
					'shipping'   => array(
						'firstName'    => $order->shipping_first_name,
						'lastName'     => $order->shipping_last_name,
						'company'      => $order->shipping_company,
						'addressLine1' => $order->shipping_address_1,
						'addressLine2' => $order->shipping_address_2,
						'city'         => $order->shipping_city,
						'state'        => $order->shipping_state,
						'postcode'     => $order->shipping_postcode,
						'country'      => $order->shipping_country,
					),
				);

				$response = Receiptful()->api->receipt( $order_args );

				if ( is_wp_error($response)) {
					// queue the message for sending via cron
					$resend_queue = get_option( '_receiptful_resend_queue' );
					$resend_queue[] = $order->id;
					update_option( '_receiptful_resend_queue', $resend_queue);

				} elseif ( $response['response']['code'] == '201' ) {

					$order->add_order_note('Customer receipt sent via Receiptful.');
					$body = json_decode( $response ['body'], true);

					add_post_meta( $order_id, '_receiptful_web_link', $body['_meta']['links']);
					add_post_meta( $order_id, '_receiptful_receipt_id', $body['_id'] );

					$upsell = $body['upsell'];
					if ( isset( $upsell['couponCode']) ) {
						do_action( 'receiptful_add_upsell', $upsell );
					}

				} else {
					$order->add_order_note('Error sending customer receipt sent via Receiptful.'
											. "\n" . "Error Code: " . $response['response']['code']
											. "\n" . "Error Message: " . $response['response']['message']);

					// queue the message for sending via cron
					$resend_queue = get_option( '_receiptful_resend_queue' );
					$resend_queue[] = $order_id;
					update_option( '_receiptful_resend_queue', $resend_queue);
				}


			}

		}

		/**
		 * Initialise Settings Form Fields
		 *
		 * @access public
		 * @return void
		 */
		function init_form_fields() {
			$this->form_fields = array(
				'enabled' => array(
					'title' 		=> __( 'Enable/Disable', 'woocommerce' ),
					'type' 			=> 'checkbox',
					'label' 		=> __( 'Enable this email notification', 'woocommerce' ),
					'default' 		=> 'yes'
				),
			);
		}
	}

endif;

return new Receiptful_Email_Customer_New_Order();
