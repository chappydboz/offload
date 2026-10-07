<?php
/**
 * Donk Toss - Automated Coupon Pruner & Garbage Collector
 *
 * Automatically purges expired, unredeemed shop_coupon posts and associated postmeta
 * to prevent database bloat from dynamic marketing coupons (Klaviyo).
 *
 * @package DonkToss
 */

if ( ! defined( "ABSPATH" ) ) {
	exit;
}

/**
 * Perform safe, batched pruning of expired, unredeemed coupons.
 *
 * @param int $max_to_prune Maximum number of coupons to delete in this run (default 5000).
 * @param int $batch_size Batch size per database transaction (default 1000).
 * @return int Total number of pruned coupons.
 */
function donktoss_prune_expired_unredeemed_coupons( $max_to_prune = 5000, $batch_size = 1000 ) {
	global $wpdb;

	$protected_codes = array(
		"wel_9d0w0c7g",
		"cart_8s0f5e6d",
		"WELCOME10",
		"ABANDONED10",
		"pbhomie",
		"pbhomiepickup",
		"donkthanks10",
		"onsitenoshippingdonkbuds",
		"donkdickinson10",
	);

	$placeholders = implode( ",", array_fill( 0, count( $protected_codes ), "%s" ) );
	$total_deleted = 0;

	while ( $total_deleted < $max_to_prune ) {
		$limit = min( $batch_size, $max_to_prune - $total_deleted );

		$query = $wpdb->prepare(
			"SELECT p.ID 
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} exp ON p.ID = exp.post_id 
				AND exp.meta_key = 'date_expires' 
				AND exp.meta_value IS NOT NULL 
				AND exp.meta_value != '' 
				AND CAST(exp.meta_value AS UNSIGNED) < UNIX_TIMESTAMP()
			LEFT JOIN {$wpdb->postmeta} uc ON p.ID = uc.post_id 
				AND uc.meta_key = 'usage_count'
			WHERE p.post_type = 'shop_coupon'
			  AND (uc.meta_value = '0' OR uc.meta_value IS NULL)
			  AND p.ID NOT IN (SELECT coupon_id FROM {$wpdb->prefix}wc_order_coupon_lookup)
			  AND p.post_title NOT IN ($placeholders)
			LIMIT %d",
			array_merge( $protected_codes, array( $limit ) )
		);

		$candidate_ids = $wpdb->get_col( $query );

		if ( empty( $candidate_ids ) ) {
			break;
		}

		$id_list = implode( ",", array_map( "intval", $candidate_ids ) );
		$batch_count = count( $candidate_ids );

		$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE post_id IN ({$id_list})" );
		$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE ID IN ({$id_list})" );

		$total_deleted += $batch_count;

		usleep( 100000 ); // 0.1s
	}

	if ( $total_deleted > 0 ) {
		wp_cache_flush();
		if ( function_exists( "wc_get_logger" ) ) {
			wc_get_logger()->info(
				sprintf( "Automated Coupon Pruner safely deleted %d expired unredeemed coupons.", $total_deleted ),
				array( "source" => "donktoss-coupon-pruner" )
			);
		}
	}

	return $total_deleted;
}

/**
 * Schedule weekly WP-Cron task if not already scheduled.
 */
add_action( "init", function() {
	if ( ! wp_next_scheduled( "donktoss_weekly_coupon_prune" ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, "weekly", "donktoss_weekly_coupon_prune" );
	}
} );

/**
 * Hook the cron event to the pruning function.
 */
add_action( "donktoss_weekly_coupon_prune", function() {
	donktoss_prune_expired_unredeemed_coupons( 10000, 1000 );
} );

/**
 * Register WP-CLI command: wp donktoss prune-coupons [--max=<count>] [--batch=<size>]
 */
if ( defined( "WP_CLI" ) && WP_CLI ) {
	WP_CLI::add_command( "donktoss prune-coupons", function( $args, $assoc_args ) {
		$max   = isset( $assoc_args["max"] ) ? intval( $assoc_args["max"] ) : 10000;
		$batch = isset( $assoc_args["batch"] ) ? intval( $assoc_args["batch"] ) : 1000;

		WP_CLI::log( sprintf( "Starting coupon pruning (max: %d, batch: %d)...", $max, $batch ) );
		$deleted = donktoss_prune_expired_unredeemed_coupons( $max, $batch );
		WP_CLI::success( sprintf( "Pruning complete. Deleted %d expired unredeemed coupons.", $deleted ) );
	} );
}
