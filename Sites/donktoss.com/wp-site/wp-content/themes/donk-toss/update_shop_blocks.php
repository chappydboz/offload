<?php
/**
 * Reset and Update Shop Content Blocks to Merch & Shop Category (Term ID 50) and clean footer CTA
 */

if ( ! defined( 'ABSPATH' ) ) {
	require_once __DIR__ . '/../../../wp-load.php';
}

global $wpdb;

$merch_term = get_term_by( 'slug', 'merch-shop', 'faq_category' );
$term_id = $merch_term ? (int) $merch_term->term_id : 50;

$blocks = array(
	'shop-homepage-faq' => array(
		'title'   => 'Shop Archive / Homepage FAQ Block',
		'heading' => 'Frequently Asked Questions',
	),
	'cart-faq' => array(
		'title'   => 'Cart Page FAQ Block',
		'heading' => 'Frequently Asked Questions & Shipping Info',
	),
	'checkout-faq' => array(
		'title'   => 'Checkout Page FAQ Block',
		'heading' => 'Frequently Asked Questions & Store Policies',
	),
	'single-product-faq' => array(
		'title'   => 'Single Product Pages FAQ Block',
		'heading' => 'Product FAQs & Support',
	),
);

$footer_html = '<p>Have a question not answered here? Visit our <a href="/faq/">Official FAQs</a> or email us at <a href="mailto:shop@donktoss.com">shop@donktoss.com</a>.</p>';

foreach ( $blocks as $slug => $info ) {
	$post = get_page_by_path( $slug, OBJECT, 'donk_shop_block' );

	$acf_data = array(
		'name' => 'acf/faq-accordion',
		'data' => array(
			'ordering_mode'        => 'auto',
			'_ordering_mode'       => 'field_faq_block_ordering_mode',
			'selected_categories'  => array( $term_id ),
			'_selected_categories' => 'field_faq_block_categories',
			'group_by_category'    => '0',
			'_group_by_category'  => 'field_faq_block_group_by_category',
			'heading_tag'          => 'h3',
			'_heading_tag'         => 'field_faq_block_heading_tag',
			'accordion_mode'       => 'multi',
			'_accordion_mode'      => 'field_faq_block_accordion_mode',
			'show_search'          => '0',
			'_show_search'         => 'field_faq_block_show_search',
			'show_footer_cta'      => '1',
			'_show_footer_cta'     => 'field_faq_block_show_footer_cta',
			'footer_cta_text'      => $footer_html,
			'_footer_cta_text'     => 'field_faq_block_footer_cta_text',
		),
		'align' => '',
		'mode' => 'preview',
	);

	$block_json = json_encode( $acf_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	$content = '<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">' . esc_html( $info['heading'] ) . '</h2><!-- /wp:heading -->' . "\n" . '<!-- wp:acf/faq-accordion ' . $block_json . ' /-->';

	if ( $post ) {
		$wpdb->update(
			$wpdb->posts,
			array(
				'post_title'   => $info['title'],
				'post_content' => $content,
			),
			array( 'ID' => $post->ID )
		);
		clean_post_cache( $post->ID );
		echo "Updated block {$slug} (ID {$post->ID})\n";
	} else {
		$new_id = wp_insert_post( array(
			'post_name'    => $slug,
			'post_title'   => $info['title'],
			'post_content' => $content,
			'post_status'  => 'publish',
			'post_type'    => 'donk_shop_block',
		) );
		echo "Created block {$slug} (ID {$new_id})\n";
	}
}

echo "Successfully updated all Shop Content Blocks.\n";
