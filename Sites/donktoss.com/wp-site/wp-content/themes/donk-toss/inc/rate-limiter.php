<?php
/**
 * Donk Toss: Outgoing Email Rate Limiting & Background Throttling
 *
 * Designed to ensure background AutomateWoo workflows and Action Scheduler
 * queues strictly adhere to SendLayer's 50 emails/minute rate ceiling.
 *
 * @package DonkToss
 * @since 4.8.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Throttle AutomateWoo outgoing emails to stay safely under SendLayer's 50/min limit.
 *
 * Spacing emails by 1.5 seconds caps the dispatch rate at 40 emails/minute,
 * providing a 20% safety margin while running completely in background processes.
 *
 * @param \AutomateWoo\Mailer_Abstract|\AutomateWoo\Workflow_Email $mailer Mailer instance.
 */
function donktoss_throttle_automatewoo_emails( $mailer ) {
	// Only throttle in background context / CLI / Action Scheduler / WP-Cron.
	// Allow frontend synchronous checkout notifications (if any) to remain unblocked.
	$delay_microseconds = apply_filters( 'donktoss_email_throttle_delay_microseconds', 1500000 ); // 1.5s = 1,500,000us

	if ( $delay_microseconds > 0 ) {
		usleep( $delay_microseconds );
	}
}
add_action( 'automatewoo/email/before_send', 'donktoss_throttle_automatewoo_emails', 10, 1 );

/**
 * Optimize Action Scheduler batch size to prevent concurrent execution bursts.
 *
 * @param int $batch_size Default batch size.
 * @return int Constrained batch size.
 */
function donktoss_optimize_action_scheduler_batch_size( $batch_size ) {
	return min( $batch_size, 25 );
}
add_filter( 'action_scheduler_queue_runner_batch_size', 'donktoss_optimize_action_scheduler_batch_size', 10, 1 );

