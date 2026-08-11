<?php
/**
 * Donk Toss FAQ CPT, Taxonomy, ACF Fields & Gutenberg Block Registration
 *
 * @package Donk Toss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Include FAQ Accordion Block rendering logic
 */
require_once get_theme_file_path( '/block-faq-accordion.php' );

/**
 * Register Custom Post Type: FAQ
 */
function donktoss_register_faq_cpt() {
	$labels = array(
		'name'                  => _x( 'FAQs', 'Post Type General Name', 'donk-toss' ),
		'singular_name'         => _x( 'FAQ', 'Post Type Singular Name', 'donk-toss' ),
		'menu_name'             => __( 'FAQs', 'donk-toss' ),
		'name_admin_bar'        => __( 'FAQ', 'donk-toss' ),
		'archives'              => __( 'FAQ Archives', 'donk-toss' ),
		'attributes'            => __( 'FAQ Attributes', 'donk-toss' ),
		'parent_item_colon'     => __( 'Parent FAQ:', 'donk-toss' ),
		'all_items'             => __( 'All FAQs', 'donk-toss' ),
		'add_new_item'          => __( 'Add New FAQ', 'donk-toss' ),
		'add_new'               => __( 'Add New', 'donk-toss' ),
		'new_item'              => __( 'New FAQ', 'donk-toss' ),
		'edit_item'             => __( 'Edit FAQ', 'donk-toss' ),
		'update_item'           => __( 'Update FAQ', 'donk-toss' ),
		'view_item'             => __( 'View FAQ', 'donk-toss' ),
		'view_items'            => __( 'View FAQs', 'donk-toss' ),
		'search_items'          => __( 'Search FAQs', 'donk-toss' ),
		'not_found'             => __( 'No FAQs found', 'donk-toss' ),
		'not_found_in_trash'    => __( 'No FAQs found in Trash', 'donk-toss' ),
		'items_list'            => __( 'FAQs list', 'donk-toss' ),
		'items_list_navigation' => __( 'FAQs list navigation', 'donk-toss' ),
		'filter_items_list'     => __( 'Filter FAQs list', 'donk-toss' ),
	);

	$args = array(
		'label'                 => __( 'FAQ', 'donk-toss' ),
		'description'           => __( 'Donk Toss Frequently Asked Questions', 'donk-toss' ),
		'labels'                => $labels,
		'supports'              => array( 'title', 'editor', 'revisions', 'page-attributes', 'custom-fields' ),
		'taxonomies'            => array( 'faq_category' ),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 6,
		'menu_icon'             => 'dashicons-editor-help',
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => true,
		'can_export'            => true,
		'has_archive'           => 'faqs',
		'exclude_from_search'   => false,
		'publicly_queryable'    => true,
		'capability_type'       => 'post',
		'show_in_rest'          => true,
		'rewrite'               => array( 'slug' => 'faq', 'with_front' => false ),
	);

	register_post_type( 'faq', $args );
}
add_action( 'init', 'donktoss_register_faq_cpt', 0 );

/**
 * Register Custom Post Type: Shop Content Blocks (Gutenberg Blocks for WooCommerce Pages)
 */
function donktoss_register_shop_block_cpt() {
	$labels = array(
		'name'               => _x( 'Shop Content Blocks', 'Post Type General Name', 'donk-toss' ),
		'singular_name'      => _x( 'Shop Content Block', 'Post Type Singular Name', 'donk-toss' ),
		'menu_name'          => __( 'Shop Content Blocks', 'donk-toss' ),
		'all_items'          => __( 'Shop Content Blocks', 'donk-toss' ),
		'add_new_item'       => __( 'Add New Shop Content Block', 'donk-toss' ),
		'add_new'            => __( 'Add New', 'donk-toss' ),
		'new_item'           => __( 'New Block', 'donk-toss' ),
		'edit_item'          => __( 'Edit Gutenberg Block', 'donk-toss' ),
		'update_item'        => __( 'Update Block', 'donk-toss' ),
		'view_item'          => __( 'View Block', 'donk-toss' ),
		'search_items'       => __( 'Search Blocks', 'donk-toss' ),
		'not_found'          => __( 'No Blocks found', 'donk-toss' ),
	);

	$args = array(
		'label'               => __( 'Shop Content Block', 'donk-toss' ),
		'description'         => __( 'Gutenberg Blocks inserted programmatically into WooCommerce Shop locations', 'donk-toss' ),
		'labels'              => $labels,
		'supports'            => array( 'title', 'editor', 'revisions', 'custom-fields' ),
		'hierarchical'        => false,
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => 'edit.php?post_type=faq',
		'show_in_admin_bar'   => true,
		'can_export'          => true,
		'exclude_from_search' => true,
		'publicly_queryable'  => false,
		'capability_type'     => 'post',
		'show_in_rest'        => true,
	);

	register_post_type( 'donk_shop_block', $args );
}
add_action( 'init', 'donktoss_register_shop_block_cpt', 0 );

/**
 * Auto-seed Default Gutenberg Shop FAQ Content Blocks
 */
function donktoss_seed_shop_content_blocks() {
	if ( ! post_type_exists( 'donk_shop_block' ) ) {
		return;
	}

	$default_blocks = array(
		'shop-homepage-faq' => array(
			'title'   => 'Shop Archive / Homepage FAQ Block',
			'content' => '<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Frequently Asked Questions</h2><!-- /wp:heading --><!-- wp:acf/faq-accordion {"name":"acf/faq-accordion","data":{"ordering_mode":"custom","group_by_category":"1","heading_tag":"h3","accordion_mode":"multi","show_search":"0","show_footer_cta":"1","footer_cta_text":"\u003cp\u003eView all FAQs \u003ca href=\u0022/faq/\u0022\u003ehere\u003c/a\u003e. Have a question not answered here? Email us at \u003ca href=\u0022mailto:info@donktoss.com\u0022\u003einfo@donktoss.com\u003c/a\u003e.\u003c/p\u003e"},"align":"","mode":"preview"} /-->',
		),
		'cart-faq' => array(
			'title'   => 'Cart Page FAQ Block',
			'content' => '<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Frequently Asked Questions &amp; Shipping Info</h2><!-- /wp:heading --><!-- wp:acf/faq-accordion {"name":"acf/faq-accordion","data":{"ordering_mode":"custom","group_by_category":"0","heading_tag":"h3","accordion_mode":"multi","show_search":"0","show_footer_cta":"1","footer_cta_text":"\u003cp\u003eQuestions about your cart or order? Visit our \u003ca href=\u0022/faq/\u0022\u003eOfficial FAQs\u003c/a\u003e or email \u003ca href=\u0022mailto:shop@donktoss.com\u0022\u003eshop@donktoss.com\u003c/a\u003e.\u003c/p\u003e"},"align":"","mode":"preview"} /-->',
		),
		'checkout-faq' => array(
			'title'   => 'Checkout Page FAQ Block',
			'content' => '<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Frequently Asked Questions &amp; Store Policies</h2><!-- /wp:heading --><!-- wp:acf/faq-accordion {"name":"acf/faq-accordion","data":{"ordering_mode":"custom","group_by_category":"0","heading_tag":"h3","accordion_mode":"multi","show_search":"0","show_footer_cta":"1","footer_cta_text":"\u003cp\u003eQuestions about your order? Visit our \u003ca href=\u0022/faq/\u0022\u003eOfficial FAQs\u003c/a\u003e or email \u003ca href=\u0022mailto:shop@donktoss.com\u0022\u003eshop@donktoss.com\u003c/a\u003e.\u003c/p\u003e"},"align":"","mode":"preview"} /-->',
		),
		'single-product-faq' => array(
			'title'   => 'Single Product Pages FAQ Block',
			'content' => '<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Product FAQs &amp; Support</h2><!-- /wp:heading --><!-- wp:acf/faq-accordion {"name":"acf/faq-accordion","data":{"ordering_mode":"custom","group_by_category":"1","heading_tag":"h3","accordion_mode":"multi","show_search":"0","show_footer_cta":"1","footer_cta_text":"\u003cp\u003eView all FAQs \u003ca href=\u0022/faq/\u0022\u003ehere\u003c/a\u003e. Need support? Email \u003ca href=\u0022mailto:shop@donktoss.com\u0022\u003eshop@donktoss.com\u003c/a\u003e.\u003c/p\u003e"},"align":"","mode":"preview"} /-->',
		),
	);

	foreach ( $default_blocks as $slug => $data ) {
		$existing = get_page_by_path( $slug, OBJECT, 'donk_shop_block' );
		if ( ! $existing ) {
			wp_insert_post( array(
				'post_name'    => $slug,
				'post_title'   => $data['title'],
				'post_content' => $data['content'],
				'post_status'  => 'publish',
				'post_type'    => 'donk_shop_block',
			) );
		}
	}
}
add_action( 'init', 'donktoss_seed_shop_content_blocks', 20 );

/**
 * Parse and render Gutenberg block content reliably
 */
function donktoss_parse_and_render_gutenberg( $content ) {
	if ( empty( $content ) ) {
		return '';
	}

	// Decode HTML entities if block comment comments contain &quot;
	$clean_content = html_entity_decode( $content, ENT_QUOTES, 'UTF-8' );

	$blocks = parse_blocks( $clean_content );
	$output = '';
	foreach ( $blocks as $block ) {
		if ( ! empty( $block['blockName'] ) ) {
			if ( 'acf/faq-accordion' === $block['blockName'] && function_exists( 'donktoss_render_faq_accordion_block' ) ) {
				ob_start();
				$acf_block_data = isset( $block['attrs'] ) ? $block['attrs'] : array();
				if ( ! isset( $acf_block_data['id'] ) ) {
					$acf_block_data['id'] = 'shop-faq-' . md5( wp_json_encode( $block ) );
				}
				donktoss_render_faq_accordion_block( $acf_block_data );
				$output .= ob_get_clean();
			} else {
				$output .= render_block( $block );
			}
		} elseif ( ! empty( $block['innerHTML'] ) && '' !== trim( $block['innerHTML'] ) ) {
			$output .= $block['innerHTML'];
		}
	}
	return $output;
}



/**
 * Render Gutenberg Shop FAQ Blocks Programmatically into WooCommerce Locations
 */
function donktoss_render_woocommerce_shop_faq_blocks() {
	static $rendered_locations = array();

	if ( is_admin() ) {
		return;
	}

	$block_slug = '';

	if ( is_shop() || is_post_type_archive( 'product' ) || is_page( 'shop' ) || is_page( 28 ) ) {
		$block_slug = 'shop-homepage-faq';
	} elseif ( is_cart() || is_page( 'cart' ) || is_page( 29 ) ) {
		$block_slug = 'cart-faq';
	} elseif ( is_checkout() && ! is_order_received_page() ) {
		$block_slug = 'checkout-faq';
	} elseif ( is_product() ) {
		$block_slug = 'single-product-faq';
	}

	if ( empty( $block_slug ) || isset( $rendered_locations[ $block_slug ] ) ) {
		return;
	}

	// Mark location as rendered
	$rendered_locations[ $block_slug ] = true;

	// Check for per-product override if on single product page
	if ( is_product() ) {
		global $post;
		if ( $post && function_exists( 'get_field' ) ) {
			$custom_product_block = get_field( 'product_custom_faq_block', $post->ID );
			if ( $custom_product_block && is_object( $custom_product_block ) && ! empty( $custom_product_block->post_content ) ) {
				echo '<div class="donktoss-woocommerce-faq-wrap donktoss-faq-location-custom">';
				echo donktoss_parse_and_render_gutenberg( $custom_product_block->post_content );
				echo '</div>';
				return;
			}
		}
	}

	$shop_block_post = get_page_by_path( $block_slug, OBJECT, 'donk_shop_block' );

	echo '<div class="donktoss-woocommerce-faq-wrap donktoss-faq-location-' . esc_attr( $block_slug ) . '">';

	if ( $shop_block_post && ! empty( $shop_block_post->post_content ) ) {
		echo donktoss_parse_and_render_gutenberg( $shop_block_post->post_content );
	} else {
		// Fallback rendering
		if ( function_exists( 'donktoss_render_faq_accordion_block' ) ) {
			$fallback_block = array(
				'id' => 'auto-' . $block_slug,
				'anchor' => '',
				'className' => '',
				'align' => '',
			);
			donktoss_render_faq_accordion_block( $fallback_block );
		}
	}

	echo '</div>';
}

/**
 * Render FAQ block on Cart page via hooks or content filter
 */
function donktoss_render_cart_faq_block() {
	static $cart_rendered = false;

	if ( is_admin() || $cart_rendered ) {
		return;
	}

	if ( is_cart() || is_page( 'cart' ) || is_page( 29 ) ) {
		$cart_rendered = true;
		$shop_block_post = get_page_by_path( 'cart-faq', OBJECT, 'donk_shop_block' );
		echo '<div class="donktoss-woocommerce-faq-wrap donktoss-faq-location-cart-faq">';
		if ( $shop_block_post && ! empty( $shop_block_post->post_content ) ) {
			echo donktoss_parse_and_render_gutenberg( $shop_block_post->post_content );
		} else {
			if ( function_exists( 'donktoss_render_faq_accordion_block' ) ) {
				donktoss_render_faq_accordion_block( array( 'id' => 'auto-cart-faq' ) );
			}
		}
		echo '</div>';
	}
}

// Attach to Cart page hooks
add_action( 'woocommerce_after_cart', 'donktoss_render_cart_faq_block', 30 );
add_action( 'woocommerce_after_cart_table', 'donktoss_render_cart_faq_block', 30 );
add_action( 'woocommerce_cart_collaterals', 'donktoss_render_cart_faq_block', 30 );

/**
 * Filter the_content to guarantee bottom in-line placement under main page content for Shop & Cart pages
 */
function donktoss_append_faq_block_to_shop_pages( $content ) {
	if ( is_admin() || ! is_main_query() || ! in_the_loop() ) {
		return $content;
	}

	if ( is_shop() || is_post_type_archive( 'product' ) || is_page( 'shop' ) || is_page( 28 ) ) {
		static $shop_content_rendered = false;
		if ( ! $shop_content_rendered ) {
			$shop_content_rendered = true;
			ob_start();
			donktoss_render_woocommerce_shop_faq_blocks();
			$faq_html = ob_get_clean();
			return $content . $faq_html;
		}
	} elseif ( is_cart() || is_page( 'cart' ) || is_page( 29 ) ) {
		ob_start();
		donktoss_render_cart_faq_block();
		$faq_html = ob_get_clean();
		return $content . $faq_html;
	}

	return $content;
}
add_filter( 'the_content', 'donktoss_append_faq_block_to_shop_pages', 30 );

// Hook into WooCommerce locations for Checkout & Products
add_action( 'woocommerce_after_checkout_form', 'donktoss_render_woocommerce_shop_faq_blocks', 30 );
add_action( 'woocommerce_after_single_product_summary', 'donktoss_render_woocommerce_shop_faq_blocks', 25 );
add_action( 'astra_content_bottom', 'donktoss_render_woocommerce_shop_faq_blocks', 30 );







/**
 * Register Taxonomy: FAQ Category (FAQ Topics)
 */
function donktoss_register_faq_taxonomy() {
	$labels = array(
		'name'                       => _x( 'FAQ Topics', 'Taxonomy General Name', 'donk-toss' ),
		'singular_name'              => _x( 'FAQ Topic', 'Taxonomy Singular Name', 'donk-toss' ),
		'menu_name'                  => __( 'FAQ Topics', 'donk-toss' ),
		'all_items'                  => __( 'All Topics', 'donk-toss' ),
		'parent_item'                => __( 'Parent Topic', 'donk-toss' ),
		'parent_item_colon'          => __( 'Parent Topic:', 'donk-toss' ),
		'new_item_name'              => __( 'New Topic Name', 'donk-toss' ),
		'add_new_item'               => __( 'Add New Topic', 'donk-toss' ),
		'edit_item'                  => __( 'Edit Topic', 'donk-toss' ),
		'update_item'                => __( 'Update Topic', 'donk-toss' ),
		'view_item'                  => __( 'View Topic', 'donk-toss' ),
		'separate_items_with_commas' => __( 'Separate topics with commas', 'donk-toss' ),
		'add_or_remove_items'        => __( 'Add or remove topics', 'donk-toss' ),
		'choose_from_most_used'      => __( 'Choose from the most used', 'donk-toss' ),
		'popular_items'              => __( 'Popular Topics', 'donk-toss' ),
		'search_items'               => __( 'Search Topics', 'donk-toss' ),
		'not_found'                  => __( 'Not Found', 'donk-toss' ),
		'no_terms'                   => __( 'No topics', 'donk-toss' ),
		'items_list'                 => __( 'Topics list', 'donk-toss' ),
		'items_list_navigation'     => __( 'Topics list navigation', 'donk-toss' ),
	);

	$args = array(
		'labels'                     => $labels,
		'hierarchical'               => true,
		'public'                     => true,
		'show_ui'                    => true,
		'show_admin_column'          => true,
		'show_in_nav_menus'          => true,
		'show_tagcloud'              => false,
		'show_in_rest'               => true,
		'rewrite'                    => array( 'slug' => 'faq-topic', 'with_front' => false ),
	);

	register_taxonomy( 'faq_category', array( 'faq' ), $args );
}
add_action( 'init', 'donktoss_register_faq_taxonomy', 0 );

/**
 * Register Default FAQ Categories on Initialization if missing
 */
function donktoss_seed_faq_categories() {
	if ( ! taxonomy_exists( 'faq_category' ) ) {
		return;
	}

	$default_categories = array(
		'Merch & Shop'         => 'Questions about Donk Toss merchandise, board orders, shipping, and returns.',
		'DONK Gameplay'        => 'Rules of play, court distances, scoring system, and tournament regulations.',
		'Events & Tournaments' => 'Information on attending, participating in, or hosting Donk Toss events.',
		'General & Support'    => 'General inquiries and customer support questions.',
	);

	foreach ( $default_categories as $term_name => $term_desc ) {
		if ( ! term_exists( $term_name, 'faq_category' ) ) {
			wp_insert_term(
				$term_name,
				'faq_category',
				array(
					'description' => $term_desc,
					'slug'        => sanitize_title( $term_name ),
				)
			);
		}
	}
}
add_action( 'init', 'donktoss_seed_faq_categories', 10 );

/**
 * Register ACF Fields for FAQ CPT & FAQ Accordion Block
 */


function donktoss_register_faq_acf_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	// 1. FAQ Post Meta Fields (Answer & Optional Badge)
	acf_add_local_field_group( array(
		'key' => 'group_donktoss_faq_details',
		'title' => __( 'FAQ Details', 'donk-toss' ),
		'fields' => array(
			array(
				'key' => 'field_faq_answer',
				'label' => __( 'Answer', 'donk-toss' ),
				'name' => 'faq_answer',
				'type' => 'wysiwyg',
				'instructions' => __( 'Provide the comprehensive answer for this FAQ item. Supports formatted text, lists, and links.', 'donk-toss' ),
				'required' => 0,
				'tabs' => 'all',
				'toolbar' => 'full',
				'media_upload' => 1,
				'delay' => 0,
			),
			array(
				'key' => 'field_faq_badge',
				'label' => __( 'Badge / Label (Optional)', 'donk-toss' ),
				'name' => 'faq_badge',
				'type' => 'text',
				'instructions' => __( 'Optional small pill badge displayed next to question (e.g. "Popular", "Rules", "Shipping").', 'donk-toss' ),
				'placeholder' => __( 'e.g. Popular', 'donk-toss' ),
			),
		),
		'location' => array(
			array(
				array(
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'faq',
				),
			),
		),
		'menu_order' => 0,
		'position' => 'normal',
		'style' => 'default',
	) );

	// 2. FAQ Accordion Gutenberg Block Settings
	acf_add_local_field_group( array(
		'key' => 'group_donktoss_faq_accordion_block',
		'title' => __( 'FAQ Accordion Block Settings', 'donk-toss' ),
		'fields' => array(
			array(
				'key' => 'field_faq_block_ordering_mode',
				'label' => __( 'Ordering Mode', 'donk-toss' ),
				'name' => 'ordering_mode',
				'type' => 'select',
				'instructions' => __( 'Choose how topics and FAQs are ordered in this block instance.', 'donk-toss' ),
				'choices' => array(
					'custom' => 'Custom Drag & Drop Order (Reorder Topics & FAQs below)',
					'auto'   => 'Automatic (Default by Menu Order & Category Name)',
				),
				'default_value' => 'custom',
				'ui' => 1,
			),

			array(
				'key' => 'field_faq_block_custom_topics',
				'label' => __( 'Drag & Drop Topics & FAQ Order', 'donk-toss' ),
				'name' => 'custom_topic_order',
				'type' => 'repeater',
				'instructions' => __( 'Drag & drop topic rows up or down using the left handle to change topic order. Pick FAQs for each topic and drag & drop them to set question order.', 'donk-toss' ),
				'collapsed' => 'field_faq_topic_term',
				'min' => 0,
				'max' => 0,
				'layout' => 'block',
				'button_label' => __( 'Add Topic Group', 'donk-toss' ),
				'sub_fields' => array(
					array(
						'key' => 'field_faq_topic_term',
						'label' => __( 'Select Topic / Category', 'donk-toss' ),
						'name' => 'topic_term',
						'type' => 'taxonomy',
						'taxonomy' => 'faq_category',
						'field_type' => 'select',
						'allow_null' => 0,
						'add_term' => 0,
						'save_terms' => 0,
						'load_terms' => 0,
						'return_format' => 'id',
						'multiple' => 0,
					),
					array(
						'key' => 'field_faq_topic_faqs',
						'label' => __( 'Select & Drag/Drop FAQs for this Topic', 'donk-toss' ),
						'name' => 'topic_faqs',
						'type' => 'relationship',
						'instructions' => __( 'Select FAQs for this topic. Drag & drop items in the selection box to set their exact display order. Leave empty to display all FAQs for this topic.', 'donk-toss' ),
						'post_type' => array( 'faq' ),
						'filters' => array( 'search' ),
						'elements' => array( 'title' ),
						'min' => 0,
						'max' => 0,
						'return_format' => 'object',
					),
				),
				'conditional_logic' => array(
					array(
						array(
							'field' => 'field_faq_block_ordering_mode',
							'operator' => '==',
							'value' => 'custom',
						),
						array(
							'field' => 'field_faq_block_group_by_category',
							'operator' => '==',
							'value' => '1',
						),
					),
				),
			),
			array(
				'key' => 'field_faq_block_custom_flat_faqs',
				'label' => __( 'Drag & Drop FAQ Order (Flat List)', 'donk-toss' ),
				'name' => 'custom_flat_faq_order',
				'type' => 'relationship',
				'instructions' => __( 'Select FAQs and drag & drop them in the right box to set their exact display order.', 'donk-toss' ),
				'post_type' => array( 'faq' ),
				'filters' => array( 'search' ),
				'elements' => array( 'title' ),
				'min' => 0,
				'max' => 0,
				'return_format' => 'object',
				'conditional_logic' => array(
					array(
						array(
							'field' => 'field_faq_block_ordering_mode',
							'operator' => '==',
							'value' => 'custom',
						),
						array(
							'field' => 'field_faq_block_group_by_category',
							'operator' => '==',
							'value' => '0',
						),
					),
				),
			),
			array(
				'key' => 'field_faq_block_categories',
				'label' => __( 'Select FAQ Topics / Categories', 'donk-toss' ),
				'name' => 'selected_categories',
				'type' => 'taxonomy',
				'instructions' => __( 'Select one or more topics to display (e.g. Merch & Shop). Leave blank to display all FAQs.', 'donk-toss' ),
				'taxonomy' => 'faq_category',
				'field_type' => 'checkbox',
				'allow_null' => 1,
				'add_term' => 0,
				'save_terms' => 0,
				'load_terms' => 0,
				'return_format' => 'id',
				'multiple' => 1,
			),


			array(
				'key' => 'field_faq_block_group_by_category',
				'label' => __( 'Display Category Headings', 'donk-toss' ),
				'name' => 'group_by_category',
				'type' => 'true_false',
				'instructions' => __( 'Display category headings above FAQ topic groups. Turn off to display a flat list of FAQs without topic headings.', 'donk-toss' ),
				'default_value' => 1,
				'ui' => 1,
			),
			array(
				'key' => 'field_faq_block_heading_tag',
				'label' => __( 'Category Heading Level', 'donk-toss' ),
				'name' => 'heading_tag',
				'type' => 'select',
				'instructions' => __( 'HTML heading level for category titles (maintains SEO heading hierarchy).', 'donk-toss' ),
				'choices' => array(
					'h2' => 'H2 - Section Heading',
					'h3' => 'H3 - Sub Heading',
					'h4' => 'H4 - Minor Heading',
				),
				'default_value' => 'h2',
				'conditional_logic' => array(
					array(
						array(
							'field' => 'field_faq_block_group_by_category',
							'operator' => '==',
							'value' => '1',
						),
					),
				),
			),

			array(
				'key' => 'field_faq_block_accordion_mode',
				'label' => __( 'Accordion Behavior', 'donk-toss' ),
				'name' => 'accordion_mode',
				'type' => 'select',
				'choices' => array(
					'multi'  => 'Allow Multiple Open',
					'single' => 'Single Open Only (Auto-close others)',
				),
				'default_value' => 'multi',
			),
			array(
				'key' => 'field_faq_block_show_search',
				'label' => __( 'Enable Quick Filter / Search Bar', 'donk-toss' ),
				'name' => 'show_search',
				'type' => 'true_false',
				'instructions' => __( 'Display an instant search field above the FAQ list.', 'donk-toss' ),
				'default_value' => 0,
				'ui' => 1,
			),
			array(
				'key' => 'field_faq_block_show_footer_cta',
				'label' => __( 'Display Block Footer Call-to-Action', 'donk-toss' ),
				'name' => 'show_footer_cta',
				'type' => 'true_false',
				'instructions' => __( 'Display CTA footer at the bottom of the FAQ block.', 'donk-toss' ),
				'default_value' => 1,
				'ui' => 1,
			),
			array(
				'key' => 'field_faq_block_footer_cta_text',
				'label' => __( 'Footer CTA Content', 'donk-toss' ),
				'name' => 'footer_cta_text',
				'type' => 'wysiwyg',
				'instructions' => __( 'Customize the call to action message displayed at the bottom of this FAQ block instance.', 'donk-toss' ),
				'default_value' => '<p>View all FAQs <a href="/faqs/">here</a>. Have a question not answered here? Email us at <a href="mailto:info@donktoss.com">info@donktoss.com</a>.</p>',
				'tabs' => 'all',
				'toolbar' => 'basic',
				'media_upload' => 0,
				'conditional_logic' => array(
					array(
						array(
							'field' => 'field_faq_block_show_footer_cta',
							'operator' => '==',
							'value' => '1',
						),
					),
				),
			),
		),
		'location' => array(
			array(
				array(
					'param' => 'block',
					'operator' => '==',
					'value' => 'acf/faq-accordion',
				),
			),
		),
		'menu_order' => 0,
		'position' => 'normal',
		'style' => 'default',
	) );
}
add_action( 'acf/init', 'donktoss_register_faq_acf_fields' );

/**
 * Register ACF Gutenberg Block: FAQ Accordion
 */
function donktoss_register_faq_acf_blocks() {
	if ( function_exists( 'acf_register_block_type' ) ) {
		acf_register_block_type( array(
			'name'            => 'faq-accordion',
			'title'           => __( 'FAQ Accordion', 'donk-toss' ),
			'description'     => __( 'An accessible, SEO-optimized, and AI agent readable FAQ accordion widget supporting multi-category filtering.', 'donk-toss' ),
			'render_callback' => 'donktoss_render_faq_accordion_block',
			'category'        => 'widgets',
			'icon'            => 'editor-help',
			'keywords'        => array( 'faq', 'faqs', 'accordion', 'questions', 'answers', 'help', 'donk' ),
			'enqueue_style'   => get_stylesheet_directory_uri() . '/assets/css/faq-accordion.css',
			'enqueue_script'  => get_stylesheet_directory_uri() . '/assets/js/faq-accordion.js',
			'supports'        => array(
				'align' => array( 'full', 'wide' ),
				'mode'  => true,
				'jsx'   => true,
			),
		) );
	}
}
add_action( 'acf/init', 'donktoss_register_faq_acf_blocks' );

/**
 * Custom Admin List Table Columns for FAQ CPT
 */
function donktoss_faq_admin_columns( $columns ) {
	$new_columns = array(
		'cb'           => $columns['cb'],
		'title'        => __( 'Question', 'donk-toss' ),
		'faq_category' => __( 'FAQ Topic', 'donk-toss' ),
		'faq_answer'   => __( 'Answer Preview', 'donk-toss' ),
		'menu_order'   => __( 'Order', 'donk-toss' ),
		'date'         => $columns['date'],
	);
	return $new_columns;
}
add_filter( 'manage_faq_posts_columns', 'donktoss_faq_admin_columns' );

function donktoss_faq_admin_column_content( $column, $post_id ) {
	switch ( $column ) {
		case 'faq_category':
			$terms = get_the_term_list( $post_id, 'faq_category', '', ', ', '' );
			echo $terms ? $terms : '<span style="color:#999;">—</span>';
			break;
		case 'faq_answer':
			$answer = get_field( 'faq_answer', $post_id );
			if ( empty( $answer ) ) {
				$answer = get_post_field( 'post_content', $post_id );
			}
			$clean = wp_strip_all_tags( $answer );
			echo esc_html( wp_trim_words( $clean, 15, '...' ) );
			break;
		case 'menu_order':
			$post = get_post( $post_id );
			echo esc_html( $post->menu_order );
			break;
	}
}
add_action( 'manage_faq_posts_custom_column', 'donktoss_faq_admin_column_content', 10, 2 );

function donktoss_faq_sortable_columns( $columns ) {
	$columns['menu_order'] = 'menu_order';
	return $columns;
}
add_filter( 'manage_edit-faq_sortable_columns', 'donktoss_faq_sortable_columns' );
