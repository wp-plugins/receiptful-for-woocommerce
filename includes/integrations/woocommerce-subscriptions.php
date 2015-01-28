<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WooCommerce Subscriptions compatibility.
 *
 * @author		Receiptful
 * @version		1.0.0
 * @since		1.1.0
 */


add_filter( 'woocommerce_email_classes', 'receiptful_wcs_remove_email', 90 );
/**
 * Remove subscription email.
 *
 * Remove the subscription email from the settings.
 *
 * @since 1.0.0
 */
function receiptful_wcs_remove_email( $emails ) {

	unset( $emails['WCS_Email_New_Renewal_Order'] );

	return $emails;

}


add_action( 'init', 'remove_wcs_completed_email' );
/**
 * Remove completed.
 *
 * Remove the email being sent when order status is set to 'completed'.
 *
 * @since 1.1.0
 */
function remove_wcs_completed_email() {

	// Remove WooCommerce Subscriptions emails
	remove_action( 'woocommerce_order_status_pending_to_processing', 'WC_Subscriptions_Email::send_renewal_order_email', 10 );
	remove_action( 'woocommerce_order_status_pending_to_completed', 'WC_Subscriptions_Email::send_renewal_order_email', 10 );
	remove_action( 'woocommerce_order_status_pending_to_on-hold', 'WC_Subscriptions_Email::send_renewal_order_email', 10 );
	remove_action( 'woocommerce_order_status_failed_to_processing_notification', 'WC_Subscriptions_Email::send_renewal_order_email', 10 );
	remove_action( 'woocommerce_order_status_failed_to_completed_notification', 'WC_Subscriptions_Email::send_renewal_order_email', 10 );
	remove_action( 'woocommerce_order_status_failed_to_on-hold_notification', 'WC_Subscriptions_Email::send_renewal_order_email', 10 );
	remove_action( 'woocommerce_order_status_completed', 'WC_Subscriptions_Email::send_renewal_order_email', 10 );
	remove_action( 'woocommerce_generated_manual_renewal_order', 'WC_Subscriptions_Email::send_renewal_order_email', 10 );
	remove_action( 'woocommerce_order_status_failed', 'WC_Subscriptions_Email::send_renewal_order_email', 10 );

}


add_filter( 'woocommerce_subscriptions_renewal_order_meta_query', 'receiptful_wcs_do_not_copy_meta_data', 10, 4 );
/**
 * Do not copy receiptful meta data for WC Subscription renewals
 *
 * @param $order_meta_query
 * @param $original_order_id
 * @param $renewal_order_id
 * @param $new_order_role
 *
 * @return string
 */
function receiptful_wcs_do_not_copy_meta_data( $order_meta_query, $original_order_id, $renewal_order_id, $new_order_role ) {

	$order_meta_query .= " AND `meta_key` NOT IN ('_receiptful_receipt_id', '_receiptful_web_link')";

	return $order_meta_query;

}


add_filter( 'receiptful_hidden_order_itemmeta', 'receiptful_wcs_hide_subscription_meta_from_mail' );
/**
 * Remove subscription meta.
 *
 * Remove the subscription meta from the mail to prevent
 * strange and a long list.
 *
 * @since 1.1.0
 *
 * @param	array $existing_meta	List of existing meta being excluded.
 * @return	array					List of modified meta being excluded, this includes subscription meta.
 */
function receiptful_wcs_hide_subscription_meta_from_mail( $existing_meta ) {

	$meta = array(
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
		'_recurring_line_subtotal_tax',
	);

	return array_merge( $existing_meta, $meta );

}
