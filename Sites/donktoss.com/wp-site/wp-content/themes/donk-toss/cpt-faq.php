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
 * Seed Initial FAQs on Admin Init if empty
 */
function donktoss_seed_initial_faqs() {
	if ( ! is_admin() || ! post_type_exists( 'faq' ) ) {
		return;
	}

	// Only seed once if no FAQ posts exist at all
	$existing_faqs = get_posts( array(
		'post_type'      => 'faq',
		'post_status'    => 'any',
		'posts_per_page' => 1,
		'fields'         => 'ids',
	) );

	if ( ! empty( $existing_faqs ) ) {
		return;
	}

	$faqs = array(
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

	foreach ( $faqs as $faq_data ) {
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
		}
	}
}
add_action( 'admin_init', 'donktoss_seed_initial_faqs' );

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
				'key' => 'field_faq_block_categories',
				'label' => __( 'Select FAQ Topics / Categories', 'donk-toss' ),
				'name' => 'selected_categories',
				'type' => 'taxonomy',
				'instructions' => __( 'Select one or more topics to display. Leave blank to display all FAQs.', 'donk-toss' ),
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
