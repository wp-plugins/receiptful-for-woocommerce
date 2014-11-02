<?php
/**
 * CRON events.
 *
 * @author		Receiptful
 * @version		1.0.0
 * @since		1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Schedule events.
 *
 * Schedule the resend of receipts to fire hourly
 * Scheduled outside class because working with objects isn't
 * perfect while doing events.
 *
 * @since 1.0.0
 */
// Schedule resend receipts event
if ( ! wp_next_scheduled( 'receiptful_check_resend' ) ) :
	wp_schedule_event( 1407110400, 'hourly', 'receiptful_check_resend' ); // 1407110400 is 08 / 4 / 2014 @ 0:0:0 UTC
endif;

/**
 *
 * Resend any receipts in queue
 *
 * @since 1.0.0
 */
add_action( 'receiptful_check_resend', 'receiptful_check_resend' );
function receiptful_check_resend() {

	Receiptful()->email->resend_queue();

}
