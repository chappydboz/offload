<?php
/**
 * Donk Toss Single Event Detail Page Template (/event/{slug}/)
 *
 * @package Donk Toss
 */

get_header();

while ( have_posts() ) :
	the_post();
	$post_id        = get_the_ID();
	$start_date     = donktoss_get_event_field( 'event_start_date', $post_id );
	$start_time     = donktoss_get_event_field( 'event_start_time', $post_id );
	$end_time       = donktoss_get_event_field( 'event_end_time', $post_id );
	$location_name  = donktoss_get_event_field( 'event_location_name', $post_id );
	$location_addr  = donktoss_get_event_field( 'event_location_address', $post_id );
	$btn_text       = donktoss_get_event_field( 'event_button_text', $post_id );
	$btn_link       = donktoss_get_event_field( 'event_button_link', $post_id );
	$types          = get_the_terms( $post_id, 'event_type' );
	$formatted_date = $start_date ? date( 'l, F j, Y', strtotime( $start_date ) ) : '';
	$permalink      = get_permalink();
	$share_title    = rawurlencode( get_the_title() );
	$share_url      = rawurlencode( $permalink );

	// Image fields (Hero 16:9/natural, Promo graphic) with featured image fallback
	$hero_url  = donktoss_get_event_image_url( 'event_hero_image', $post_id, 'full' );
	$promo_url = donktoss_get_event_image_url( 'event_promo_image', $post_id, 'full' );

	// Resolve Location & Address cleanly
	$location_raw = $location_name ? $location_name : $location_addr;
	$loc_venue    = '';
	$loc_address  = '';

	if ( $location_name && $location_addr && $location_name !== $location_addr ) {
		$loc_venue   = $location_name;
		$loc_address = $location_addr;
	} elseif ( $location_raw ) {
		if ( strpos( $location_raw, ',' ) !== false ) {
			$parts       = explode( ',', $location_raw, 2 );
			$loc_venue   = trim( $parts[0] );
			$loc_address = trim( $parts[1] );
		} else {
			$loc_venue = $location_raw;
		}
	}

	// Smart button text default
	if ( empty( $btn_text ) && ! empty( $btn_link ) ) {
		if ( preg_match( '/(ticket|eventbrite|dice\.fm|ticketmaster|axs|stubhub)/i', $btn_link ) ) {
			$btn_text = __( 'Get Tickets', 'donk-toss' );
		} elseif ( preg_match( '/(espn|youtube|twitch|stream|live)/i', $btn_link ) ) {
			$btn_text = __( 'Watch Live', 'donk-toss' );
		} elseif ( preg_match( '/(celis|brewery|bar|venue)/i', $btn_link ) ) {
			$btn_text = __( 'Visit Venue Website', 'donk-toss' );
		} else {
			$btn_text = __( 'Event Website / Info', 'donk-toss' );
		}
	}

	// Google Maps query URL
	$maps_query = rawurlencode( trim( ( $loc_venue ? $loc_venue . ' ' : '' ) . $loc_address ) );
	$maps_url   = $maps_query ? 'https://www.google.com/maps/search/?api=1&query=' . $maps_query : '';
	?>

	<article id="post-<?php the_ID(); ?>" <?php post_class( 'donktoss-single-event' ); ?>>
		
		<!-- Theme Native Page Header -->
		<div class="ast-container">
			<div id="primary" class="content-area primary">
				<main id="main" class="site-main">

					<header class="donktoss-events-page-header">
						<?php if ( ! empty( $types ) && ! is_wp_error( $types ) ) : ?>
							<div class="donktoss-single-event-types">
								<?php foreach ( $types as $type_term ) : ?>
									<span class="donktoss-type-pill"><?php echo esc_html( $type_term->name ); ?></span>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<h1 class="entry-title donktoss-page-title" itemprop="headline">
							<?php the_title(); ?>
						</h1>
					</header>

					<!-- Natural Height Hero Image Banner (No Empty Space) -->
					<?php if ( $hero_url ) : ?>
						<div class="donktoss-single-event-featured-media">
							<img src="<?php echo esc_url( $hero_url ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" class="donktoss-single-hero-img" />
						</div>
					<?php endif; ?>

					<!-- Quick Event Info Card Bar -->
					<div class="donktoss-single-event-meta-card">
						<div class="donktoss-meta-card-col">
							<div class="donktoss-meta-card-icon">📅</div>
							<div class="donktoss-meta-card-text">
								<span class="donktoss-meta-card-label"><?php esc_html_e( 'Date & Time', 'donk-toss' ); ?></span>
								<strong><?php echo esc_html( $formatted_date ? $formatted_date : __( 'TBD', 'donk-toss' ) ); ?></strong>
								<?php if ( $start_time ) : ?>
									<span><?php echo esc_html( $start_time . ( $end_time ? ' - ' . $end_time : '' ) ); ?></span>
								<?php endif; ?>
							</div>
						</div>

						<?php if ( $loc_venue || $loc_address ) : ?>
							<div class="donktoss-meta-card-col donktoss-meta-card-location">
								<div class="donktoss-meta-card-icon">📍</div>
								<div class="donktoss-meta-card-text">
									<span class="donktoss-meta-card-label"><?php esc_html_e( 'Location / Venue', 'donk-toss' ); ?></span>
									<?php if ( $loc_venue ) : ?>
										<strong><?php echo esc_html( $loc_venue ); ?></strong>
									<?php endif; ?>
									<?php if ( $loc_address ) : ?>
										<span><?php echo esc_html( $loc_address ); ?></span>
									<?php endif; ?>
									<?php if ( $maps_url ) : ?>
										<a href="<?php echo esc_url( $maps_url ); ?>" target="_blank" rel="noopener noreferrer" class="donktoss-meta-card-directions">
											<?php esc_html_e( 'Get Directions', 'donk-toss' ); ?> ↗
										</a>
									<?php endif; ?>
								</div>
							</div>
						<?php endif; ?>

						<?php if ( $btn_link ) : ?>
							<div class="donktoss-meta-card-col donktoss-meta-card-cta">
								<a href="<?php echo esc_url( $btn_link ); ?>" target="_blank" rel="noopener noreferrer" class="ast-button donktoss-btn-primary">
									<?php echo esc_html( $btn_text ); ?> ↗
								</a>
							</div>
						<?php endif; ?>
					</div>

					<!-- Main Event Content Body -->
					<div class="donktoss-single-event-body-wrap">
						<div class="entry-content donktoss-single-event-content">
							<!-- Description Text First -->
							<?php the_content(); ?>

							<!-- Promo Image Graphic After Description Text -->
							<?php if ( $promo_url ) : ?>
								<div class="donktoss-single-promo-wrap">
									<img src="<?php echo esc_url( $promo_url ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?> Promo Graphic" class="donktoss-single-promo-img" />
								</div>
							<?php endif; ?>
						</div>

						<!-- Social Share Bar -->
						<footer class="donktoss-single-event-footer">
							<div class="donktoss-social-share-bar">
								<span class="donktoss-share-label"><?php esc_html_e( 'Share This Event:', 'donk-toss' ); ?></span>
								<a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $share_url; ?>" target="_blank" rel="noopener noreferrer" class="donktoss-share-btn donktoss-share-fb" title="Share on Facebook">
									Facebook
								</a>
								<a href="https://twitter.com/intent/tweet?text=<?php echo $share_title; ?>&url=<?php echo $share_url; ?>" target="_blank" rel="noopener noreferrer" class="donktoss-share-btn donktoss-share-x" title="Share on X">
									X / Twitter
								</a>
								<a href="mailto:?subject=<?php echo $share_title; ?>&body=Check%20out%20this%20event:%20<?php echo $share_url; ?>" class="donktoss-share-btn donktoss-share-email" title="Share via Email">
									Email
								</a>
								<button onclick="navigator.clipboard.writeText('<?php echo esc_js( $permalink ); ?>'); alert('Event link copied to clipboard!');" class="donktoss-share-btn donktoss-share-copy">
									Copy Link
								</button>
							</div>
						</footer>
					</div>

				</main>
			</div>
		</div>

	</article>

	<?php
endwhile;

get_footer();
