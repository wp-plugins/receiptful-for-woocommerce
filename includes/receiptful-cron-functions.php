<?php
/**
 * CRON events.
 *
 * @author		Receiptful
 * @version		1.0.1
 * @since		1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * 15 minute interval.
 *
 * Add a 15 minute interval to the cron schedules.
 *
 * @since 1.0.0
 *
 * @param 	array $schedules	List of current CRON schedules.
 * @return 	array				List of modified CRON schedules.
 */
add_filter( 'cron_schedules', 'receiptful_add_quarter_schedule' );
function receiptful_add_quarter_schedule( $schedules ) {

	$schedules['quarter_hour'] = array(
		'interval' 	=> 60 * 15, // 60 seconds * 15 minutes
		'display' 	=> __( 'Every quarter', 'receiptful' )
	);

	return $schedules;

}


/**
 * Schedule events.
 *
 * Schedule the resend of receipts to fire every 15 minutes
 * Scheduled outside class because working with objects isn't
 * perfect while doing events.
 *
 * @since 1.0.0
 */
// Schedule resend receipts event
if ( ! wp_next_scheduled( 'receiptful_check_resend' ) ) {
	wp_schedule_event( 1407110400, 'quarter_hour', 'receiptful_check_resend' ); // 1407110400 is 08 / 4 / 2014 @ 0:0:0 UTC
}


/**
 * Cron function.
 *
 * Function being fired by CRON. Resend any receipts in queue.
 *
 * @since 1.0.0
 */
add_action( 'receiptful_check_resend', 'receiptful_check_resend' );
function receiptful_check_resend() {

	Receiptful()->email->resend_queue();

}

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
function receiptful_do_not_copy_meta_data( $order_meta_query, $original_order_id, $renewal_order_id, $new_order_role ) {

	$order_meta_query .= " AND `meta_key` NOT IN ('_receiptful_receipt_id', '_receiptful_web_link')";

	return $order_meta_query;
}
add_filter( 'woocommerce_subscriptions_renewal_order_meta_query', 'receiptful_do_not_copy_meta_data', 10, 4 );
