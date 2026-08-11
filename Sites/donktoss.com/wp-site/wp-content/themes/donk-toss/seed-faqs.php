<?php
/**
 * Seed Initial Donk Toss FAQs
 * Run via WP-CLI or include once to seed database.
 */

if ( ! defined( 'ABSPATH' ) ) {
	require_once __DIR__ . '/../../../wp-load.php';
}



$faqs = array(
	// Merch & Shop
	array(
		'title'    => 'What are your shipping options and delivery timelines?',
		'category' => 'Merch & Shop',
		'answer'   => '<p>We offer standard shipping (3-5 business days) and expedited shipping options within the US. International orders typically arrive within 7-14 business days depending on destination customs. Tracking information is automatically emailed as soon as your package ships.</p>',
		'badge'    => 'Shipping',
		'order'    => 1,
	),
	array(
		'title'    => 'What is your return and exchange policy?',
		'category' => 'Merch & Shop',
		'answer'   => '<p>We accept returns and exchanges on unworn apparel and unused game gear within 30 days of purchase. Items must be in original condition. Please reach out to <a href="mailto:info@donktoss.com">info@donktoss.com</a> to initiate a return or exchange.</p>',
		'badge'    => 'Returns',
		'order'    => 2,
	),
	array(
		'title'    => 'How do Donk Toss t-shirts and hoodies fit?',
		'category' => 'Merch & Shop',
		'answer'   => '<p>Our apparel runs true to standard US sizing. Shirts are crafted from premium pre-shrunk cotton blends. If you prefer an extra relaxed fit for tournament play, we suggest sizing up one size.</p>',
		'badge'    => 'Sizing',
		'order'    => 3,
	),
	array(
		'title'    => 'Are official Donk Toss boards weather-resistant?',
		'category' => 'Merch & Shop',
		'answer'   => '<p>Yes! Official Donk Toss boards are engineered with cabinet-grade hardwood and sealed with UV-resistant weatherproof coatings to handle outdoor play, sun exposure, and damp grass.</p>',
		'badge'    => 'Products',
		'order'    => 4,
	),

	// DONK Gameplay
	array(
		'title'    => 'What are the official court dimensions and board distance for Donk Toss?',
		'category' => 'DONK Gameplay',
		'answer'   => '<p>Official tournament distance is exactly <strong>27 feet</strong> measured from the front edge of board A to the front edge of board B. For casual backyard play or younger players, a 20-24 foot distance is recommended.</p>',
		'badge'    => 'Court Setup',
		'order'    => 1,
	),
	array(
		'title'    => 'How does the scoring system work in Donk Toss?',
		'category' => 'DONK Gameplay',
		'answer'   => '<p>Games are played to <strong>21 points</strong> (win by 2). Scoring is as follows: 1 point for landing on the board, 3 points for a toss through the Donk hole. Cancellation scoring applies—only the net point difference between opposing players is awarded per frame.</p>',
		'badge'    => 'Scoring',
		'order'    => 2,
	),
	array(
		'title'    => 'Do bounces off the ground count as valid points?',
		'category' => 'DONK Gameplay',
		'answer'   => '<p>No. Any toss that contacts the ground before touching the board is a foul / dead ball and must be immediately cleared from the board prior to the next throw.</p>',
		'badge'    => 'Foul Rules',
		'order'    => 3,
	),
	array(
		'title'    => 'Can I host an official Donk Toss sanctioned tournament?',
		'category' => 'DONK Gameplay',
		'answer'   => '<p>Yes! We welcome official community organizers and tournament directors. Visit our Events section or email <a href="mailto:info@donktoss.com">info@donktoss.com</a> to receive the official Tournament Director handbook and bracket kits.</p>',
		'badge'    => 'Tournaments',
		'order'    => 4,
	),
);

$inserted_count = 0;

foreach ( $faqs as $faq_data ) {
	// Check if post already exists
	$existing = get_page_by_title( $faq_data['title'], OBJECT, 'faq' );
	if ( ! $existing ) {
		$post_id = wp_insert_post( array(
			'post_title'   => $faq_data['title'],
			'post_content' => $faq_data['answer'],
			'post_status'  => 'publish',
			'post_type'    => 'faq',
			'menu_order'   => $faq_data['order'],
		) );

		if ( $post_id && ! is_wp_error( $post_id ) ) {
			// Assign taxonomy category
			$term = get_term_by( 'name', $faq_data['category'], 'faq_category' );
			if ( $term ) {
				wp_set_post_terms( $post_id, array( $term->term_id ), 'faq_category' );
			}

			// Update ACF Meta Fields
			update_field( 'faq_answer', $faq_data['answer'], $post_id );
			if ( ! empty( $faq_data['badge'] ) ) {
				update_field( 'faq_badge', $faq_data['badge'], $post_id );
			}

			$inserted_count++;
		}
	}
}

echo "Successfully seeded {$inserted_count} FAQ items.\n";
