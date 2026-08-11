<?php
/**
 * Donk Toss Events Archive Listing Template (/events/)
 *
 * @package Donk Toss
 */

get_header();

$today = date( 'Y-m-d' );

// Query all published events
$all_events_args = array(
	'post_type'      => 'event',
	'posts_per_page' => -1,
	'post_status'    => 'publish',
);
$all_events_query = new WP_Query( $all_events_args );

$upcoming_events = array();
$past_events     = array();

if ( $all_events_query->have_posts() ) {
	while ( $all_events_query->have_posts() ) {
		$all_events_query->the_post();
		$event_id   = get_the_ID();
		$start_date = donktoss_get_event_field( 'event_start_date', $event_id );

		$event_obj = array(
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

		if ( empty( $start_date ) || $start_date >= $today ) {
			$upcoming_events[] = $event_obj;
		} else {
			$past_events[] = $event_obj;
		}
	}
	wp_reset_postdata();
}

// Sort upcoming events ASC (earliest first)
usort( $upcoming_events, function( $a, $b ) {
	return strcmp( $a['start_date'], $b['start_date'] );
});

// Sort past events DESC (most recent past event first)
usort( $past_events, function( $a, $b ) {
	return strcmp( $b['start_date'], $a['start_date'] );
});
?>

<div class="ast-container">
	<div id="primary" class="content-area primary">
		<main id="main" class="site-main">

			<!-- Page Header styled after About & Home page headers -->
			<header class="donktoss-events-page-header">
				<h1 class="entry-title donktoss-page-title" itemprop="headline">
					DONK TOSS <span class="donktoss-title-orange">EVENTS</span>
				</h1>
				<p class="donktoss-page-subtitle">
					<?php esc_html_e( 'Donk Toss is a glorious backyard sport that blends skill, chaos, and comedy. Catch our upcoming TV airings, live activations, tournaments, and live tapings.', 'donk-toss' ); ?>
				</p>
			</header>

			<!-- Section: Upcoming Events -->
			<section class="donktoss-events-section donktoss-upcoming-events">
				<h2 class="donktoss-section-heading">
					<span class="dashicons dashicons-calendar-alt"></span> <?php esc_html_e( 'Upcoming Events', 'donk-toss' ); ?>
				</h2>

				<?php if ( ! empty( $upcoming_events ) ) : ?>
					<div class="donktoss-events-list">
						<?php foreach ( $upcoming_events as $event ) :
							$month_abbr     = $event['start_date'] ? date( 'M', strtotime( $event['start_date'] ) ) : 'TBD';
							$day_num        = $event['start_date'] ? date( 'd', strtotime( $event['start_date'] ) ) : '--';
							$formatted_date = $event['start_date'] ? date( 'F j, Y', strtotime( $event['start_date'] ) ) : 'Date TBD';
							?>
							<article class="donktoss-event-horizontal-card">
								
								<!-- Left Media / Date Badge Column -->
								<div class="donktoss-event-card-media">
									<a href="<?php echo esc_url( $event['permalink'] ); ?>" class="donktoss-event-image-link">
										<?php if ( ! empty( $event['thumbnail_url'] ) ) : ?>
											<img src="<?php echo esc_url( $event['thumbnail_url'] ); ?>" alt="<?php echo esc_attr( $event['title'] ); ?>" class="donktoss-event-img donktoss-img-square" />
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

								<!-- Right Content Details Column -->
								<div class="donktoss-event-card-body">
									<div class="donktoss-event-card-header">
										<?php if ( ! empty( $event['types'] ) && ! is_wp_error( $event['types'] ) ) : ?>
											<div class="donktoss-event-types">
												<?php foreach ( $event['types'] as $type_term ) : ?>
													<span class="donktoss-type-pill"><?php echo esc_html( $type_term->name ); ?></span>
												<?php endforeach; ?>
											</div>
										<?php endif; ?>

										<h3 class="donktoss-event-card-title">
											<a href="<?php echo esc_url( $event['permalink'] ); ?>"><?php echo esc_html( $event['title'] ); ?></a>
										</h3>
									</div>

									<div class="donktoss-event-meta-line">
										<span class="donktoss-meta-item">
											<strong>📅 Date:</strong> <?php echo esc_html( $formatted_date ); ?>
											<?php if ( $event['start_time'] ) : ?>
												• <?php echo esc_html( $event['start_time'] ); ?>
											<?php endif; ?>
										</span>
										<?php if ( $event['loc_name'] || $event['loc_addr'] ) : ?>
											<span class="donktoss-meta-item">
												<strong>📍 Location:</strong> 
												<?php echo esc_html( trim( $event['loc_name'] . ( $event['loc_name'] && $event['loc_addr'] ? ' — ' : '' ) . $event['loc_addr'] ) ); ?>
											</span>
										<?php endif; ?>
									</div>

									<div class="donktoss-event-excerpt">
										<?php echo wp_kses_post( $event['excerpt'] ); ?>
									</div>

									<div class="donktoss-event-actions">
										<a href="<?php echo esc_url( $event['permalink'] ); ?>" class="ast-button donktoss-btn-secondary">
											<?php esc_html_e( 'Event Details', 'donk-toss' ); ?>
										</a>
										<?php if ( $event['btn_link'] ) : ?>
											<a href="<?php echo esc_url( $event['btn_link'] ); ?>" target="_blank" rel="noopener noreferrer" class="ast-button donktoss-btn-primary">
												<?php echo esc_html( $event['btn_text'] ? $event['btn_text'] : __( 'Get Tickets / Watch', 'donk-toss' ) ); ?>
											</a>
										<?php endif; ?>
									</div>
								</div>

							</article>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<div class="donktoss-no-events-notice">
						<p><?php esc_html_e( 'No upcoming events scheduled right now. Check back soon or view our past events below!', 'donk-toss' ); ?></p>
					</div>
				<?php endif; ?>
			</section>

			<!-- Section: Past Events -->
			<section class="donktoss-events-section donktoss-past-events">
				<h2 class="donktoss-section-heading">
					<span class="dashicons dashicons-backup"></span> <?php esc_html_e( 'Past Events', 'donk-toss' ); ?>
				</h2>

				<?php if ( ! empty( $past_events ) ) : ?>
					<div class="donktoss-past-events-list">
						<?php foreach ( $past_events as $event ) :
							$formatted_date = $event['start_date'] ? date( 'M j, Y', strtotime( $event['start_date'] ) ) : '';
							?>
							<div class="donktoss-past-event-row">
								<div class="donktoss-past-date">
									<?php echo esc_html( $formatted_date ); ?>
								</div>
								<div class="donktoss-past-info">
									<h4 class="donktoss-past-title">
										<a href="<?php echo esc_url( $event['permalink'] ); ?>"><?php echo esc_html( $event['title'] ); ?></a>
									</h4>
									<?php if ( ! empty( $event['types'] ) && ! is_wp_error( $event['types'] ) ) : ?>
										<span class="donktoss-type-pill donktoss-type-pill-sm"><?php echo esc_html( $event['types'][0]->name ); ?></span>
									<?php endif; ?>
									<?php if ( $event['loc_name'] || $event['loc_addr'] ) : ?>
										<span class="donktoss-past-location">📍 <?php echo esc_html( $event['loc_name'] ? $event['loc_name'] : $event['loc_addr'] ); ?></span>
									<?php endif; ?>
								</div>
								<div class="donktoss-past-action">
									<a href="<?php echo esc_url( $event['permalink'] ); ?>" class="donktoss-link-arrow"><?php esc_html_e( 'Recap →', 'donk-toss' ); ?></a>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<div class="donktoss-no-events-notice">
						<p><?php esc_html_e( 'No past events logged yet.', 'donk-toss' ); ?></p>
					</div>
				<?php endif; ?>
			</section>

		</main>
	</div>
</div>

<?php
get_footer();
