<?php
/**
 * Donk Toss Upcoming Events Gutenberg / ACF Pro Block & Shortcode Renderer
 *
 * @package Donk Toss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Filter an array of event objects/arrays to keep only upcoming events
 */
function donktoss_filter_upcoming_events( $events_list, $today_date = null ) {
	if ( null === $today_date ) {
		$today_date = date( 'Y-m-d' );
	}

	$filtered = array();
	foreach ( $events_list as $event ) {
		$date = is_array( $event ) ? ( isset( $event['start_date'] ) ? $event['start_date'] : '' ) : ( isset( $event->start_date ) ? $event->start_date : '' );
		if ( empty( $date ) || $date >= $today_date ) {
			$filtered[] = $event;
		}
	}
	return $filtered;
}

/**
 * Fetch Upcoming Events from Database
 */
function donktoss_get_upcoming_events_data( $limit = 3 ) {
	$today = date( 'Y-m-d' );

	$query_args = array(
		'post_type'      => 'event',
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		'meta_key'       => 'event_start_date',
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
		'meta_query'     => array(
			'relation' => 'OR',
			array(
				'key'     => 'event_start_date',
				'value'   => $today,
				'compare' => '>=',
				'type'    => 'DATE',
			),
			array(
				'key'     => 'event_start_date',
				'compare' => 'NOT EXISTS',
			),
		),
	);

	$query  = new WP_Query( $query_args );
	$events = array();

	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			$event_id   = get_the_ID();
			$start_date = donktoss_get_event_field( 'event_start_date', $event_id );

			$events[] = array(
				'id'            => $event_id,
				'title'         => get_the_title(),
				'permalink'     => get_permalink(),
				'excerpt'       => get_the_excerpt(),
				'start_date'    => $start_date,
				'start_time'    => donktoss_get_event_field( 'event_start_time', $event_id ),
				'end_time'      => donktoss_get_event_field( 'event_end_time', $event_id ),
				'loc_name'      => donktoss_get_event_field( 'event_location_name', $event_id ),
				'loc_addr'      => donktoss_get_event_field( 'event_location_address', $event_id ),
				'btn_text'      => donktoss_get_event_field( 'event_button_text', $event_id ),
				'btn_link'      => donktoss_get_event_field( 'event_button_link', $event_id ),
				'types'         => get_the_terms( $event_id, 'event_type' ),
				'thumbnail_url' => donktoss_get_event_image_url( 'event_thumbnail_image', $event_id, 'medium_large' ),
			);
		}
		wp_reset_postdata();
	}

	return donktoss_filter_upcoming_events( $events, $today );
}

/**
 * Pure HTML Renderer for Upcoming Events Grid
 */
function donktoss_render_upcoming_events_html( $attributes = array(), $events = null ) {
	$heading    = ! empty( $attributes['heading'] ) ? $attributes['heading'] : __( 'Upcoming Events', 'donk-toss' );
	$limit      = ! empty( $attributes['limit'] ) ? intval( $attributes['limit'] ) : 3;

	if ( null === $events ) {
		$events = donktoss_get_upcoming_events_data( $limit );
	} else {
		// Slice mock/passed array
		$events = array_slice( $events, 0, $limit );
	}

	// 0-EVENT GUARD: Return exact empty string if no upcoming events exist!
	if ( empty( $events ) ) {
		return '';
	}

	ob_start();
	?>
	<section class="donktoss-homepage-events-section donktoss-events-widget-wrap">
		<div class="donktoss-homepage-events-container">
			
			<div class="donktoss-homepage-events-header">
				<h2 class="donktoss-section-heading">
					<span class="dashicons dashicons-calendar-alt"></span>
					<?php echo esc_html( $heading ); ?>
				</h2>
				<a href="<?php echo esc_url( home_url( '/events/' ) ); ?>" class="donktoss-view-all-link">
					<?php esc_html_e( 'View All Events →', 'donk-toss' ); ?>
				</a>
			</div>

			<div class="donktoss-homepage-events-grid">
				<?php foreach ( $events as $event ) : ?>
					<?php
					$event_id    = ! empty( $event['id'] ) ? $event['id'] : 0;
					$title       = ! empty( $event['title'] ) ? $event['title'] : '';
					$permalink   = ! empty( $event['permalink'] ) ? $event['permalink'] : '#';
					$start_date  = ! empty( $event['start_date'] ) ? $event['start_date'] : '';
					$start_time  = ! empty( $event['start_time'] ) ? $event['start_time'] : '';
					$loc_name    = ! empty( $event['loc_name'] ) ? $event['loc_name'] : '';
					$btn_text    = ! empty( $event['btn_text'] ) ? $event['btn_text'] : __( 'Event Details', 'donk-toss' );
					$btn_link    = ! empty( $event['btn_link'] ) ? $event['btn_link'] : $permalink;
					$types       = ! empty( $event['types'] ) ? $event['types'] : array();
					$thumb_url   = ! empty( $event['thumbnail_url'] ) ? $event['thumbnail_url'] : '';

					$month_abbr  = $start_date ? date( 'M', strtotime( $start_date ) ) : 'UP';
					$day_num     = $start_date ? date( 'd', strtotime( $start_date ) ) : 'COMING';
					$date_format = $start_date ? date( 'F j, Y', strtotime( $start_date ) ) : '';
					$is_external = ! empty( $event['btn_link'] ) && ( strpos( $event['btn_link'], 'http' ) === 0 );
					?>

					<div class="donktoss-event-grid-card">
						<div class="donktoss-grid-card-media">
							<a href="<?php echo esc_url( $permalink ); ?>" class="donktoss-grid-image-link">
								<?php if ( $thumb_url ) : ?>
									<img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" class="donktoss-grid-img" />
								<?php else : ?>
									<div class="donktoss-event-img-placeholder">
										<span class="dashicons dashicons-tickets-alt"></span>
									</div>
								<?php endif; ?>
							</a>
							<div class="donktoss-event-date-badge">
								<span class="donktoss-badge-month"><?php echo esc_html( strtoupper( $month_abbr ) ); ?></span>
								<span class="donktoss-badge-day"><?php echo esc_html( $day_num ); ?></span>
							</div>
						</div>

						<div class="donktoss-grid-card-body">
							<?php if ( ! empty( $types ) && ! is_wp_error( $types ) ) : ?>
								<div class="donktoss-event-types">
									<?php foreach ( $types as $type_term ) : ?>
										<span class="donktoss-type-pill donktoss-type-pill-sm"><?php echo esc_html( is_object( $type_term ) ? $type_term->name : $type_term ); ?></span>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>

							<h3 class="donktoss-grid-card-title">
								<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
							</h3>

							<div class="donktoss-grid-meta-line">
								<?php if ( $date_format ) : ?>
									<span class="donktoss-meta-item">📅 <?php echo esc_html( $date_format . ( $start_time ? ' • ' . $start_time : '' ) ); ?></span>
								<?php endif; ?>
								<?php if ( $loc_name ) : ?>
									<span class="donktoss-meta-item">📍 <?php echo esc_html( $loc_name ); ?></span>
								<?php endif; ?>
							</div>

							<div class="donktoss-grid-card-footer">
								<a href="<?php echo esc_url( $btn_link ); ?>" <?php echo $is_external ? 'target="_blank" rel="noopener noreferrer"' : ''; ?> class="ast-button donktoss-btn-primary donktoss-grid-btn">
									<?php echo esc_html( $btn_text ); ?> <?php echo $is_external ? '↗' : '→'; ?>
								</a>
							</div>
						</div>
					</div>

				<?php endforeach; ?>
			</div>

		</div>
	</section>
	<?php
	return ob_get_clean();
}

/**
 * Gutenberg / ACF Block Render Callback
 */
function donktoss_render_upcoming_events_block( $block, $content = '', $is_preview = false, $post_id = 0 ) {
	$heading = function_exists( 'get_field' ) ? get_field( 'donktoss_events_heading' ) : '';
	$limit   = function_exists( 'get_field' ) ? get_field( 'donktoss_events_limit' ) : 3;

	if ( empty( $heading ) ) {
		$heading = isset( $block['data']['heading'] ) ? $block['data']['heading'] : __( 'Upcoming Events', 'donk-toss' );
	}

	$attributes = array(
		'heading' => $heading,
		'limit'   => $limit ? intval( $limit ) : 3,
	);

	$html = donktoss_render_upcoming_events_html( $attributes );

	if ( empty( $html ) && $is_preview ) {
		echo '<div class="donktoss-block-preview-notice" style="padding: 15px; background: #fff7ed; border: 1px solid #ffedd5; border-radius: 8px; color: #c2410c; text-align: center;"><strong>Upcoming Events Block:</strong> Currently no upcoming events scheduled. (This block will automatically hide on the live front-end).</div>';
		return;
	}

	echo $html;
}

/**
 * Shortcode Handler: [donktoss_upcoming_events limit="3" heading="Upcoming Events"]
 */
function donktoss_upcoming_events_shortcode( $atts ) {
	$attributes = shortcode_atts( array(
		'heading' => __( 'Upcoming Events', 'donk-toss' ),
		'limit'   => 3,
	), $atts, 'donktoss_upcoming_events' );

	return donktoss_render_upcoming_events_html( $attributes );
}
if ( function_exists( 'add_shortcode' ) ) {
	add_shortcode( 'donktoss_upcoming_events', 'donktoss_upcoming_events_shortcode' );
}
