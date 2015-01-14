<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Receiptful_Email_Customer_New_Order' ) ) {

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
		 *
		 * @since 1.0.0
		 */
		function __construct() {

			$this->id 				= 'customer_new_order';
			$this->title 			= __( 'Receiptful New Order', 'receiptful' );
			$this->description		= __( 'Receiptful will send a new order receipt when the order is placed.', 'receiptful' );

			// Triggers for this email
			add_action( 'receiptful_order_status_processing_notification', array( $this, 'trigger' ) );

			// Call parent constructor
			parent::__construct();
		}


		/**
		 * trigger function.
		 *
		 * This is the big function of Receiptful. When this email is triggered
		 * it will send data (API call) to Receiptful to send the actual receipt.
		 *
		 * @since 1.0.0
		 *
		 * @param int $order_id ID of the order being processed.
		 */
		function trigger( $order_id ) {

			global $wpdb;

			if ( $order_id ) {

				$order 			= wc_get_order( $order_id );
				$receiptful_id 	= get_post_meta( $order_id, '_receiptful_receipt_id', true );

				if ( ! $receiptful_id == '' ) {

					// receipt exits so resend
					$order_args = array();
					$response 	= Receiptful()->api->resend_receipt( $receiptful_id, $order_args );

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

				// Setting order item meta
				foreach ( $order->get_items() as $key => $item ) {

					// Get Item Meta Data
					$meta_data 	= array();
					$item_meta 	= $order->get_item_meta( $key );
					$metadata 	= $order->has_meta( $key );

					foreach ( $metadata as $meta ) {
						// Skip hidden core fields
						// Skip meta for WC Subscriptions
						if ( in_array( $meta['meta_key'], apply_filters( 'woocommerce_hidden_order_itemmeta', array(
							'_qty',
							'_tax_class',
							'_product_id',
							'_variation_id',
							'_line_subtotal',
							'_line_subtotal_tax',
							'_line_total',
							'_line_tax',
							'_subscription_period',
							'_subscription_interval',
							'_subscription_length',
							'_subscription_trial_length',
							'_subscription_trial_period',
							'_subscription_recurring_amount',
							'_subscription_sign_up_fee',
							'_recurring_line_total',
							'_recurring_line_tax',
							'_recurring_line_subtotal',
							'_recurring_line_subtotal_tax'
						) ) ) ) {
							continue;
						}

						// Skip serialised meta
						if ( is_serialized( $meta['meta_value'] ) ) {
							continue;
						}

						// Get attribute data
						if ( taxonomy_exists( $meta['meta_key'] ) ) {
							$term           = get_term_by( 'slug', $meta['meta_value'], $meta['meta_key'] );
							$attribute_name = str_replace( 'pa_', '', wc_clean( $meta['meta_key'] ) );
							$attribute      = $wpdb->get_var(
								$wpdb->prepare( "
										SELECT attribute_label
										FROM {$wpdb->prefix}woocommerce_attribute_taxonomies
										WHERE attribute_name = %s;
									",
									$attribute_name
								)
							);

							$meta['meta_key']   = ( ! is_wp_error( $attribute ) && $attribute ) ? $attribute : $attribute_name;
							$meta['meta_value'] = ( isset( $term->name ) ) ? $term->name : $meta['meta_value'];
						}

						$meta_data[] = array(
							'key'	=> wp_kses_post( urldecode( $meta['meta_key'] ) ),
							'value'	=> wp_kses_post( urldecode( $meta['meta_value'] ) ),
						);

					}

					$product_amount = $item['line_subtotal'] / $item['qty'];

					$items[] = array(
						'reference'		=> $item['product_id'],
						'description'	=> $item['name'],
						'quantity'		=> $item['qty'],
						'amount'		=> $product_amount,
						'url'			=> $this->maybe_get_download_url( $item, $order_id ),
						'metas'			=> $meta_data,
					);


				}


				// get all the subtotals that can include
				// shipping, tax, discount
				$subtotals = array();
				$tax_display = $order->tax_display_cart;

				if ( $order->get_cart_discount() > 0 ) {
					$subtotals[] = array(
						'description' 	=> __( 'Cart Discount', 'receiptful'),
						'amount' 		=> '-' . number_format( (float)  $order->get_cart_discount(), 2, '.', '' )
					);
				}

				if ( $order->order_shipping > 0 ) {
					$subtotals[] = array( 'description' => __( 'Shipping', 'receiptful'), 'amount' => number_format( (float) $order->order_shipping, 2, '.', '') );
				}

				if ( $order->order_shipping_tax > 0 ) {
					$subtotals[] = array( 'description' => __( 'Shipping Tax', 'receiptful'), 'amount' => number_format( (float) $order->order_shipping_tax, 2, '.', '') );
				}

				if ( $fees = $order->get_fees() ) {

					foreach( $fees as $id => $fee ) {

						if ( apply_filters( 'woocommerce_get_order_item_totals_excl_free_fees', $fee['line_total'] + $fee['line_tax'] == 0, $id ) ) {
							continue;
						}

						if ( 'excl' == $tax_display ) {
							$subtotals[] = array( 'description' => $fee['name'], 'amount' => number_format( (float) $fee['line_total'], 2, '.', '' ) );
						} else {
							$subtotals[] = array( 'description' => $fee['name'], 'amount' => number_format( (float) $fee['line_total'] + $fee['line_tax'], 2, '.', '' ) );
						}
					}

				}

				// Tax for tax exclusive prices
				if ( 'excl' == $tax_display ) {

					if ( get_option( 'woocommerce_tax_total_display' ) == 'itemized' ) {

						foreach ( $order->get_tax_totals() as $code => $tax ) {
							$subtotals[] = array( 'description' => $tax->label, 'amount' => number_format( (float) $tax->amount, 2, '.', '' ) );
						}

					} else {

						$subtotals[] = array( 'description' => WC()->countries->tax_or_vat(), 'amount' => number_format( (float)$order->get_total_tax(), 2, '.', '' ) );

					}
				}

				if ( $order->get_order_discount() > 0 ) {
					$subtotals[] = array(
						'description' 	=> __( 'Order Discount:', 'receiptful' ),
						'amount' 		=> '-' . number_format( (float) $order->get_order_discount(), 2, '.', '' )
					);
				}


				// Related products
				$order_item				= reset( $items );
				$first_item_id			= $order_item['reference'];
				$product 				= wc_get_product( $first_item_id );
				$related_products 		= array();
				$related_product_ids 	= $product->get_related( 2 );

				// Fallback to random products when no related were found.
				if ( empty ( $related_product_ids ) ) :
					$related_product_ids = wc_get_random_products( 2 );
				endif;

				if ( ! empty( $related_product_ids ) ) {
					foreach ( $related_product_ids as $related_id ) {

						$product 		= wc_get_product( $related_id );
						$product_image  = wp_get_attachment_image_src( $product->get_image_id(), array( 450, 450 ) );
						$post_content	= strip_tags( $product->post->post_content );
						$description 	= strlen( $post_content ) <= 100 ? $post_content : substr( $post_content, 0, strrpos( $post_content, ' ', -( strlen( $post_content ) - 100 ) ) );

						$related_products[] = array(
							'title'			=> $product->get_title(),
							'actionUrl'		=> get_permalink( $product->id ),
							'image'			=> $product_image[0],
							'description'	=> $description,
						);

					}
				}


				// These values are added to the order at checkout if available.
				// If not recorded then empty string will be sent.
				$card_type		= isset( $order->receiptful_card_type ) 	? $order->receiptful_card_type 		: '' ;
				$last4			= isset( $order->receiptful_last4 ) 		? $order->receiptful_last4 			: '';
				$customer_ip	= isset( $order->receitpful_customer_ip ) 	? $order->receitpful_customer_ip 	: '';

				$order_args = array(
					'reference'		=> ltrim( $order->get_order_number(), _x( '#', 'hash before order number', 'receiptful' ) ),
					'currency'		=> get_woocommerce_currency(),
					'amount'		=> number_format( (float) $order->get_total(), 2, '.', '' ),
					'to'			=> $order->billing_email,
					'from'			=> $this->get_from_address(),
					'card'			=> array(
						'type'	=> $card_type,
						'last4'	=> $last4
					),
					'items'			=> $items,
					'subtotals'		=> $subtotals,
					'upsell'		=> array( 'products' => $related_products ),
					'customerIp'	=> $customer_ip,
					'billing'		=> array(
						'address'	=> array(
							'firstName'		=> $order->billing_first_name,
							'lastName'		=> $order->billing_last_name,
							'company'		=> $order->billing_company,
							'addressLine1'	=> $order->billing_address_1,
							'addressLine2'	=> $order->billing_address_2,
							'city'			=> $order->billing_city,
							'state'			=> $order->billing_state,
							'postcode'		=> $order->billing_postcode,
							'country'		=> $order->billing_country,
						),
						'phone'		=> $order->billing_phone,
						'email'		=> $order->billing_email
					),
					'shipping'		=> array(
						'firstName'		=> $order->shipping_first_name,
						'lastName'		=> $order->shipping_last_name,
						'company'		=> $order->shipping_company,
						'addressLine1'	=> $order->shipping_address_1,
						'addressLine2'	=> $order->shipping_address_2,
						'city'			=> $order->shipping_city,
						'state'			=> $order->shipping_state,
						'postcode'		=> $order->shipping_postcode,
						'country'		=> $order->shipping_country,
					),
				);

				$response = Receiptful()->api->receipt( $order_args );

				if ( is_wp_error( $response ) ) {
					// queue the message for sending via cron
					$resend_queue 	= get_option( '_receiptful_resend_queue' );
					$resend_queue[] = $order->id;
					update_option( '_receiptful_resend_queue', $resend_queue );

				} elseif ( $response['response']['code'] == '201' ) {

					$order->add_order_note( 'Customer receipt sent via Receiptful.' );
					$body = json_decode( $response ['body'], true);

					add_post_meta( $order_id, '_receiptful_web_link', $body['_meta']['links'] );
					add_post_meta( $order_id, '_receiptful_receipt_id', $body['_id'] );

					$upsell = $body['upsell'];
					if ( isset( $upsell['couponCode'] ) ) {
						do_action( 'receiptful_add_upsell', $upsell, $order->id );
					}

				} else {
					$order->add_order_note( 'Error sending customer receipt sent via Receiptful.'
											. "\n" . "Error Code: " . $response['response']['code']
											. "\n" . "Error Message: " . $response['response']['message'] );

					// queue the message for sending via cron
					$resend_queue 	= get_option( '_receiptful_resend_queue' );
					$resend_queue[] = $order_id;
					update_option( '_receiptful_resend_queue', $resend_queue );
				}

			}

		}


		/**
		 * Download url.
		 *
		 * Get the download url(s) for the products that are downloadable.
		 *
		 * @param	array	$item		Item list param as gotton from $order->get_items().
		 * @param   int     $order_id	Order ID to get the download url for.
		 * @return	string				Download URL.
		 */
		public function maybe_get_download_url( $item, $order_id ) {

			$urls = null;

			if ( ! is_array( $item ) ) {
				return null;
			}

			$order			= wc_get_order( $order_id );
			$download_ids	= $order->get_item_downloads( $item );
			$product_id		= $item['variation_id'] > 0 ? $item['variation_id'] : $item['product_id'];

			foreach( $download_ids as $download_id ) {
				$urls[] = $download_id['download_url'];
			}

			return is_array( $urls ) ? reset( $urls ) : null; // Return the first result - no multiple downloads supported atm.

		}


		/**
		 * Email settings.
		 *
		 * Initialize email settings.
		 *
		 * @since 1.0.0
		 */
		function init_form_fields() {

			$this->form_fields = array(
				'enabled' => array(
					'title'			=> __( 'Enable/Disable', 'receiptful' ),
					'type'			=> 'checkbox',
					'label'			=> __( 'Enable this email notification', 'receiptful' ),
					'default'		=> 'yes'
				),
			);

		}


	}


}

return new Receiptful_Email_Customer_New_Order();
