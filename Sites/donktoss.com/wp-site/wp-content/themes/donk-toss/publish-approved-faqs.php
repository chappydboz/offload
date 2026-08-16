<?php
/**
 * Publish Approved Donk Toss FAQs
 */

if ( ! defined( 'ABSPATH' ) ) {
	require_once __DIR__ . '/../../../wp-load.php';
}

// Delete any existing FAQ posts to ensure clean state
$existing_faqs = get_posts( array(
	'post_type'      => 'faq',
	'post_status'    => 'any',
	'posts_per_page' => -1,
	'fields'         => 'ids',
) );

foreach ( $existing_faqs as $id ) {
	wp_delete_post( $id, true );
}

$approved_faqs = array(
	// DONK Gameplay
	array(
		'title'    => 'What is the proper inflation level for a Donk Pro?',
		'category' => 'DONK Gameplay',
		'answer'   => '<p>Donks should be inflated to a PSI of approximately <strong>0.5 PSI</strong>. Make sure the snout is fairly firm. Do not overinflate—if your DONK’s waist starts to swell, it is receiving too much air. For fair competition, always ensure both DONKs on the court are inflated to the exact same level.</p>',
		'badge'    => 'Inflation',
		'order'    => 1,
	),
	array(
		'title'    => 'Where can I find the official rules and downloadable guides for Donk Toss?',
		'category' => 'DONK Gameplay',
		'answer'   => '<p>You can view our complete official rules on our <a href="https://donktoss.com/about/rules/">Rules &amp; Resources page</a>. You can also download the official <a href="https://donktoss.com/wp-content/uploads/DONKPro-Official-Guide-web.pdf" target="_blank" rel="noopener">DONK Pro Getting Started Guide &amp; Tournament Rules PDF</a> directly to print or take with you to your next game.</p>',
		'badge'    => 'Rules & PDF',
		'order'    => 2,
	),
	array(
		'title'    => 'Can I bring Donk Toss to my sports event, halftime show, or media broadcast?',
		'category' => 'DONK Gameplay',
		'answer'   => '<p>Yes! We partner with sports teams, event organizers, and media outlets for live tournament features, halftime entertainment, and fan activations. Contact us at <a href="mailto:info@donktoss.com">info@donktoss.com</a> with your event details.</p>',
		'badge'    => 'Events',
		'order'    => 3,
	),

	// Merch & Shop
	array(
		'title'    => 'Do you ship internationally or to Canada / Australia?',
		'category' => 'Merch & Shop',
		'answer'   => '<p>Currently, we only ship within the United States and do not offer international shipping or international distributors at this time. If you have a shipping address or contact within the US, we can deliver your order there.</p>',
		'badge'    => 'Shipping',
		'order'    => 1,
	),
	array(
		'title'    => 'How long does shipping take and when will my order arrive?',
		'category' => 'Merch & Shop',
		'answer'   => '<p>Orders typically process and ship within 3 to 5 business days. Once dispatched, standard UPS transit time takes an additional 3 to 5 business days for delivery within the continental US.</p>',
		'badge'    => 'Delivery',
		'order'    => 2,
	),
	array(
		'title'    => 'Are the checkered Donk Toss shirts or competitor jerseys available for purchase?',
		'category' => 'Merch & Shop',
		'answer'   => '<p>We do not currently sell apparel or competitor jerseys, but we plan to offer official merchandise and apparel in the near future!</p>',
		'badge'    => 'Merch',
		'order'    => 3,
	),
	array(
		'title'    => 'What is your return policy if an item arrives damaged?',
		'category' => 'Merch & Shop',
		'answer'   => '<p>All sales are final. We only offer returns, exchanges, or replacements for products that arrive damaged or defective upon delivery. If your order arrives damaged, please email us at <a href="mailto:shop@donktoss.com">shop@donktoss.com</a> with your order number and photos of the damage so we can promptly issue a replacement.</p>',
		'badge'    => 'Returns',
		'order'    => 4,
	),

	array(
		'title'    => 'How do I track my order once it\'s placed?',
		'category' => 'Merch & Shop',
		'answer'   => '<p>As soon as your package is processed and picked up by UPS, an automated confirmation email containing your tracking link will be sent to the email address used at checkout.</p>',
		'badge'    => 'Tracking',
		'order'    => 5,
	),
);

$published_count = 0;

foreach ( $approved_faqs as $faq_data ) {
	$post_id = wp_insert_post( array(
		'post_title'   => $faq_data['title'],
		'post_content' => $faq_data['answer'],
		'post_status'  => 'publish',
		'post_type'    => 'faq',
		'menu_order'   => $faq_data['order'],
	) );

	if ( $post_id && ! is_wp_error( $post_id ) ) {
		$term = get_term_by( 'name', $faq_data['category'], 'faq_category' );
		if ( $term ) {
			wp_set_post_terms( $post_id, array( $term->term_id ), 'faq_category' );
		}
		if ( function_exists( 'update_field' ) ) {
			update_field( 'faq_answer', $faq_data['answer'], $post_id );
			if ( ! empty( $faq_data['badge'] ) ) {
				update_field( 'faq_badge', $faq_data['badge'], $post_id );
			}
		}
		$published_count++;
	}
}

echo "Successfully published {$published_count} approved FAQ items to WordPress.\n";
