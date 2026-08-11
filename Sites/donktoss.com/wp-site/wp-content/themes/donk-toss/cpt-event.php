<?php
/**
 * Donk Toss Events CPT, Taxonomy, ACF Fields & Schema.org Integration
 *
 * @package Donk Toss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Include Upcoming Events Block & Shortcode logic
 */
require_once get_theme_file_path( '/block-upcoming-events.php' );

/**
 * Register ACF Pro Gutenberg Block: Upcoming Events Grid
 */
function donktoss_register_acf_blocks() {
	if ( function_exists( 'acf_register_block_type' ) ) {
		acf_register_block_type( array(
			'name'            => 'upcoming-events',
			'title'           => __( 'Upcoming Events Grid', 'donk-toss' ),
			'description'     => __( 'A clean responsive widget grid displaying upcoming Donk Toss events. Automatically hides when no upcoming events are scheduled.', 'donk-toss' ),
			'render_callback' => 'donktoss_render_upcoming_events_block',
			'category'        => 'widgets',
			'icon'            => 'calendar-alt',
			'keywords'        => array( 'events', 'upcoming', 'donk', 'schedule', 'widget' ),
			'enqueue_style'   => get_stylesheet_directory_uri() . '/style.css',
			'supports'        => array(
				'align' => array( 'full', 'wide' ),
				'mode'  => true,
				'jsx'   => true,
			),
		) );
	}
}
add_action( 'acf/init', 'donktoss_register_acf_blocks' );

/**
 * Enqueue Theme Styles in WordPress Admin Block Editor (Gutenberg Preview)
 */
function donktoss_enqueue_block_editor_assets() {
	wp_enqueue_style( 'donk-toss-editor-css', get_stylesheet_directory_uri() . '/style.css', array( 'wp-edit-blocks' ), CHILD_THEME_DONK_TOSS_VERSION );
}
add_action( 'enqueue_block_editor_assets', 'donktoss_enqueue_block_editor_assets' );

function donktoss_setup_theme_editor_styles() {
	add_theme_support( 'editor-styles' );
	add_editor_style( 'style.css' );
}
add_action( 'after_setup_theme', 'donktoss_setup_theme_editor_styles' );

/**
 * Programmatic ACF Local Field Group Registration for Upcoming Events Block
 */
function donktoss_register_acf_block_fields() {
	if ( function_exists( 'acf_add_local_field_group' ) ) {
		acf_add_local_field_group( array(
			'key' => 'group_donktoss_upcoming_events_block',
			'title' => 'Upcoming Events Block Settings',
			'fields' => array(
				array(
					'key' => 'field_donktoss_events_heading',
					'label' => 'Section Heading',
					'name' => 'donktoss_events_heading',
					'type' => 'text',
					'default_value' => 'Upcoming Events',
					'placeholder' => 'Upcoming Events',
				),
				array(
					'key' => 'field_donktoss_events_limit',
					'label' => 'Number of Events to Show',
					'name' => 'donktoss_events_limit',
					'type' => 'number',
					'default_value' => 3,
					'min' => 1,
					'max' => 10,
				),
			),
			'location' => array(
				array(
					array(
						'param' => 'block',
						'operator' => '==',
						'value' => 'acf/upcoming-events',
					),
				),
			),
			'menu_order' => 0,
			'position' => 'normal',
			'style' => 'default',
		) );
	}
}
add_action( 'acf/init', 'donktoss_register_acf_block_fields' );

/**
 * Register Custom Post Type: Event
 */
function donktoss_register_event_cpt() {
	$labels = array(
		'name'                  => _x( 'Events', 'Post Type General Name', 'donk-toss' ),
		'singular_name'         => _x( 'Event', 'Post Type Singular Name', 'donk-toss' ),
		'menu_name'             => __( 'Events', 'donk-toss' ),
		'name_admin_bar'        => __( 'Event', 'donk-toss' ),
		'archives'              => __( 'Event Archives', 'donk-toss' ),
		'attributes'            => __( 'Event Attributes', 'donk-toss' ),
		'parent_item_colon'     => __( 'Parent Event:', 'donk-toss' ),
		'all_items'             => __( 'All Events', 'donk-toss' ),
		'add_new_item'          => __( 'Add New Event', 'donk-toss' ),
		'add_new'               => __( 'Add New', 'donk-toss' ),
		'new_item'              => __( 'New Event', 'donk-toss' ),
		'edit_item'             => __( 'Edit Event', 'donk-toss' ),
		'update_item'           => __( 'Update Event', 'donk-toss' ),
		'view_item'             => __( 'View Event', 'donk-toss' ),
		'view_items'            => __( 'View Events', 'donk-toss' ),
		'search_items'          => __( 'Search Event', 'donk-toss' ),
		'not_found'             => __( 'Not found', 'donk-toss' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'donk-toss' ),
		'featured_image'        => __( 'Event Poster / Image', 'donk-toss' ),
		'set_featured_image'    => __( 'Set event poster image', 'donk-toss' ),
		'remove_featured_image' => __( 'Remove event poster image', 'donk-toss' ),
		'use_featured_image'    => __( 'Use as event poster image', 'donk-toss' ),
		'insert_into_item'      => __( 'Insert into event', 'donk-toss' ),
		'uploaded_to_this_item' => __( 'Uploaded to this event', 'donk-toss' ),
		'items_list'            => __( 'Events list', 'donk-toss' ),
		'items_list_navigation' => __( 'Events list navigation', 'donk-toss' ),
		'filter_items_list'     => __( 'Filter events list', 'donk-toss' ),
	);

	$args = array(
		'label'                 => __( 'Event', 'donk-toss' ),
		'description'           => __( 'Donk Toss events, airings, and tournaments', 'donk-toss' ),
		'labels'                => $labels,
		'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields' ),
		'taxonomies'            => array( 'event_type' ),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 5,
		'menu_icon'             => 'dashicons-calendar-alt',
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => true,
		'can_export'            => true,
		'has_archive'           => 'events',
		'exclude_from_search'   => false,
		'publicly_queryable'    => true,
		'capability_type'       => 'post',
		'show_in_rest'          => true,
		'rewrite'               => array( 'slug' => 'events', 'with_front' => false ),
	);

	register_post_type( 'event', $args );
}
add_action( 'init', 'donktoss_register_event_cpt', 0 );

/**
 * Register Custom Taxonomy: Event Type
 */
function donktoss_register_event_taxonomy() {
	$labels = array(
		'name'                       => _x( 'Event Types', 'Taxonomy General Name', 'donk-toss' ),
		'singular_name'              => _x( 'Event Type', 'Taxonomy Singular Name', 'donk-toss' ),
		'menu_name'                  => __( 'Event Types', 'donk-toss' ),
		'all_items'                  => __( 'All Event Types', 'donk-toss' ),
		'parent_item'                => __( 'Parent Event Type', 'donk-toss' ),
		'parent_item_colon'          => __( 'Parent Event Type:', 'donk-toss' ),
		'new_item_name'              => __( 'New Event Type Name', 'donk-toss' ),
		'add_new_item'               => __( 'Add New Event Type', 'donk-toss' ),
		'edit_item'                  => __( 'Edit Event Type', 'donk-toss' ),
		'update_item'                => __( 'Update Event Type', 'donk-toss' ),
		'view_item'                  => __( 'View Event Type', 'donk-toss' ),
		'separate_items_with_commas' => __( 'Separate event types with commas', 'donk-toss' ),
		'add_or_remove_items'        => __( 'Add or remove event types', 'donk-toss' ),
		'choose_from_most_used'      => __( 'Choose from the most used', 'donk-toss' ),
		'popular_items'              => __( 'Popular Event Types', 'donk-toss' ),
		'search_items'               => __( 'Search Event Types', 'donk-toss' ),
		'not_found'                  => __( 'Not Found', 'donk-toss' ),
		'no_terms'                   => __( 'No event types', 'donk-toss' ),
		'items_list'                 => __( 'Event types list', 'donk-toss' ),
		'items_list_navigation'    => __( 'Event types list navigation', 'donk-toss' ),
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
		'rewrite'                    => array( 'slug' => 'event-type' ),
	);

	register_taxonomy( 'event_type', array( 'event' ), $args );
}
add_action( 'init', 'donktoss_register_event_taxonomy', 0 );

/**
 * Seed Default Event Types
 */
function donktoss_seed_event_types() {
	if ( ! taxonomy_exists( 'event_type' ) ) {
		return;
	}
	$default_types = array(
		'TV Airing',
		'Live Activation',
		'Tournament',
		'Live Taping',
	);
	foreach ( $default_types as $type ) {
		if ( ! term_exists( $type, 'event_type' ) ) {
			wp_insert_term( $type, 'event_type' );
		}
	}
}
add_action( 'init', 'donktoss_seed_event_types', 20 );

/**
 * Programmatic ACF Local Field Group Registration
 */
function donktoss_register_acf_event_fields() {
	if ( function_exists( 'acf_add_local_field_group' ) ) {
		acf_add_local_field_group( array(
			'key' => 'group_donktoss_event_details',
			'title' => 'Event Details',
			'fields' => array(
				array(
					'key' => 'field_event_start_date',
					'label' => 'Event Start Date',
					'name' => 'event_start_date',
					'type' => 'date_picker',
					'instructions' => 'Select the date when this event takes place.',
					'required' => 1,
					'display_format' => 'F j, Y',
					'return_format' => 'Y-m-d',
					'first_day' => 1,
				),
				array(
					'key' => 'field_event_start_time',
					'label' => 'Start Time',
					'name' => 'event_start_time',
					'type' => 'text',
					'instructions' => 'e.g. 11:00 AM or 7:00 PM CT',
					'placeholder' => '11:00 AM CT',
				),
				array(
					'key' => 'field_event_end_time',
					'label' => 'End Time',
					'name' => 'event_end_time',
					'type' => 'text',
					'instructions' => 'e.g. 2:00 PM CT',
					'placeholder' => '2:00 PM CT',
				),
				array(
					'key' => 'field_event_location_name',
					'label' => 'Location / Venue Name',
					'name' => 'event_location_name',
					'type' => 'text',
					'instructions' => 'e.g. ESPN8 Ocho Main Stage, Volente Studio, etc.',
					'placeholder' => 'ESPN8 Ocho Main Stage',
				),
				array(
					'key' => 'field_event_location_address',
					'label' => 'City / Address',
					'name' => 'event_location_address',
					'type' => 'text',
					'instructions' => 'e.g. Rock Hill, SC or Volente, TX',
					'placeholder' => 'Rock Hill, SC',
				),
				array(
					'key' => 'field_event_button_text',
					'label' => 'Button Text',
					'name' => 'event_button_text',
					'type' => 'text',
					'instructions' => 'Action button text (defaults to "Watch Live" or "Get Tickets").',
					'placeholder' => 'Watch on ESPN',
				),
				array(
					'key' => 'field_event_button_link',
					'label' => 'Button Link / URL',
					'name' => 'event_button_link',
					'type' => 'url',
					'instructions' => 'Link to broadcast stream, ticket sales, or registration.',
					'placeholder' => 'https://www.espn.com/watch/',
				),
				array(
					'key' => 'field_event_thumbnail_image',
					'label' => 'Thumbnail Image (Square 1:1)',
					'name' => 'event_thumbnail_image',
					'type' => 'image',
					'instructions' => 'Upload a square thumbnail image (1:1 aspect ratio) for the event card listing.',
					'return_format' => 'url',
					'preview_size' => 'thumbnail',
					'library' => 'all',
				),
				array(
					'key' => 'field_event_hero_image',
					'label' => 'Hero Image (Widescreen 16:9)',
					'name' => 'event_hero_image',
					'type' => 'image',
					'instructions' => 'Upload a widescreen banner image (16:9 aspect ratio) for the single event detail header.',
					'return_format' => 'url',
					'preview_size' => 'medium_large',
					'library' => 'all',
				),
				array(
					'key' => 'field_event_promo_image',
					'label' => 'Promo Graphic / Flyer (Any Aspect)',
					'name' => 'event_promo_image',
					'type' => 'image',
					'instructions' => 'Upload an optional promotional graphic or flyer image (any aspect ratio) displayed inside the event body.',
					'return_format' => 'url',
					'preview_size' => 'medium_large',
					'library' => 'all',
				),
			),
			'location' => array(
				array(
					array(
						'param' => 'post_type',
						'operator' => '==',
						'value' => 'event',
					),
				),
			),
			'menu_order' => 0,
			'position' => 'normal',
			'style' => 'default',
			'label_placement' => 'top',
			'instruction_placement' => 'label',
			'active' => true,
		) );
	}
}
add_action( 'acf/init', 'donktoss_register_acf_event_fields' );

/**
 * Helper function to retrieve event field values with fallback
 */
function donktoss_get_event_field( $field_name, $post_id = null ) {
	if ( function_exists( 'get_field' ) ) {
		$val = get_field( $field_name, $post_id );
		if ( ! empty( $val ) ) {
			return $val;
		}
	}
	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}
	return get_post_meta( $post_id, $field_name, true );
}

/**
 * Helper function to retrieve image URL for ACF image fields (supports URL, Array, or ID return formats) with post thumbnail fallback
 */
function donktoss_get_event_image_url( $field_name, $post_id = null, $fallback_size = 'full' ) {
	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}
	$val = donktoss_get_event_field( $field_name, $post_id );
	if ( ! empty( $val ) ) {
		if ( is_array( $val ) && isset( $val['url'] ) ) {
			return $val['url'];
		}
		if ( is_numeric( $val ) ) {
			$img_src = wp_get_attachment_image_url( $val, $fallback_size );
			if ( $img_src ) {
				return $img_src;
			}
		}
		if ( is_string( $val ) && filter_var( $val, FILTER_VALIDATE_URL ) ) {
			return $val;
		}
	}
	if ( has_post_thumbnail( $post_id ) ) {
		return get_the_post_thumbnail_url( $post_id, $fallback_size );
	}
	return '';
}

/**
 * Schema.org Event JSON-LD Output in Head
 */
function donktoss_output_event_schema_jsonld() {
	if ( ! is_singular( 'event' ) ) {
		return;
	}

	$post_id    = get_the_ID();
	$title      = get_the_title( $post_id );
	$description = get_the_excerpt( $post_id );
	if ( empty( $description ) ) {
		$description = wp_strip_all_tags( get_the_content( null, false, $post_id ) );
		$description = wp_trim_words( $description, 30, '...' );
	}

	$start_date = donktoss_get_event_field( 'event_start_date', $post_id );
	$start_time = donktoss_get_event_field( 'event_start_time', $post_id );
	$location_name = donktoss_get_event_field( 'event_location_name', $post_id );
	$location_addr = donktoss_get_event_field( 'event_location_address', $post_id );
	$image_url  = get_the_post_thumbnail_url( $post_id, 'full' );

	$iso_start = ! empty( $start_date ) ? $start_date . 'T10:00:00-05:00' : date( 'c' );

	$schema = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'Event',
		'name'        => $title,
		'description' => $description,
		'startDate'   => $iso_start,
		'eventStatus' => 'https://schema.org/EventScheduled',
		'eventAttendanceMode' => 'https://schema.org/MixedEventAttendanceMode',
		'organizer'   => array(
			'@type' => 'Organization',
			'name'  => 'Donk Toss',
			'url'   => home_url(),
		),
	);

	if ( $image_url ) {
		$schema['image'] = array( $image_url );
	}

	if ( $location_name || $location_addr ) {
		$schema['location'] = array(
			'@type'   => 'Place',
			'name'    => $location_name ? $location_name : 'Donk Toss Arena',
			'address' => array(
				'@type'          => 'PostalAddress',
				'streetAddress'  => $location_addr ? $location_addr : 'USA',
			),
		);
	}

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}
add_action( 'wp_head', 'donktoss_output_event_schema_jsonld' );

/**
 * Auto-flush rewrite rules on initial CPT deployment
 */
function donktoss_flush_event_rewrite_rules() {
	if ( get_option( 'donktoss_event_rewrite_flushed_v5' ) !== 'yes' ) {
		global $wp_rewrite;
		donktoss_register_event_cpt();
		donktoss_register_event_taxonomy();
		if ( isset( $wp_rewrite ) ) {
			$wp_rewrite->flush_rules( true );
		} else {
			flush_rewrite_rules( true );
		}
		update_option( 'donktoss_event_rewrite_flushed_v5', 'yes' );
	}
}
add_action( 'init', 'donktoss_flush_event_rewrite_rules', 99 );

/**
 * Auto-seed sample events if none exist
 */
function donktoss_auto_seed_sample_events() {
	if ( get_option( 'donktoss_events_seeded_v1' ) === 'yes' ) {
		return;
	}

	$existing = get_posts( array(
		'post_type'      => 'event',
		'post_status'    => 'any',
		'posts_per_page' => 1,
	) );

	if ( empty( $existing ) ) {
		// Insert Upcoming Event
		$post1 = wp_insert_post( array(
			'post_title'   => 'The Ocho 2026: Donk Toss Live Activation & TV Airing',
			'post_content' => '<p>Join us live for the official Donk Toss tournament activation at ESPN8: The Ocho! Broadcast live on ESPN & Disney, featuring top teams, live commentary, and chaotic backyard entertainment.</p><p>Footprint: 20x20 ft playing surface outside third baseline fence. Live show starts at 11:00 AM CT on August 7, 2026.</p>',
			'post_excerpt' => 'Donk Toss returns to ESPN8: The Ocho live from Rock Hill, SC! Catch the live broadcast on ESPN & Disney.',
			'post_status'  => 'publish',
			'post_type'    => 'event',
			'post_name'    => 'ocho-2026-donktoss-live-activation',
		) );

		if ( $post1 && ! is_wp_error( $post1 ) ) {
			update_post_meta( $post1, 'event_start_date', '2026-08-07' );
			update_post_meta( $post1, 'event_start_time', '11:00 AM CT' );
			update_post_meta( $post1, 'event_end_time', '1:00 PM CT' );
			update_post_meta( $post1, 'event_location_name', 'ESPN8 Ocho Main Stage' );
			update_post_meta( $post1, 'event_location_address', 'Rock Hill, SC' );
			update_post_meta( $post1, 'event_button_text', 'Watch on ESPN' );
			update_post_meta( $post1, 'event_button_link', 'https://www.espn.com/watch/' );
			wp_set_object_terms( $post1, array( 'TV Airing', 'Live Activation' ), 'event_type' );
		}

		// Insert Past Event
		$post2 = wp_insert_post( array(
			'post_title'   => 'Donk Toss Summer Invitational 2025',
			'post_content' => '<p>The 2025 Donk Toss Summer Invitational brought together 16 teams for an epic showdown at Volente Studio.</p>',
			'post_excerpt' => 'Recap of the 2025 Donk Toss Summer Invitational at Volente Studio.',
			'post_status'  => 'publish',
			'post_type'    => 'event',
			'post_name'    => 'donktoss-summer-invitational-2025',
		) );

		if ( $post2 && ! is_wp_error( $post2 ) ) {
			update_post_meta( $post2, 'event_start_date', '2025-07-15' );
			update_post_meta( $post2, 'event_start_time', '2:00 PM CT' );
			update_post_meta( $post2, 'event_location_name', 'Volente Studio' );
			update_post_meta( $post2, 'event_location_address', 'Volente, TX' );
			wp_set_object_terms( $post2, array( 'Tournament' ), 'event_type' );
		}

		global $wp_rewrite;
		if ( isset( $wp_rewrite ) ) {
			$wp_rewrite->flush_rules( true );
		}
	}

	update_option( 'donktoss_events_seeded_v1', 'yes' );
}
add_action( 'init', 'donktoss_auto_seed_sample_events', 50 );

/**
 * Force Astra normal container width class on Events pages
 */
function donktoss_event_body_classes( $classes ) {
	if ( is_post_type_archive( 'event' ) || is_singular( 'event' ) ) {
		$classes = array_diff( $classes, array( 'ast-narrow-container', 'ast-container--narrow' ) );
		if ( ! in_array( 'ast-normal-container', $classes, true ) ) {
			$classes[] = 'ast-normal-container';
		}
	}
	return $classes;
}
add_filter( 'body_class', 'donktoss_event_body_classes', 99 );

/**
 * Seed actual ESPN2 events from Beehive KB
 */
function donktoss_seed_actual_espn_events() {
	// Always update watch links to ensure exact ESPN player URLs
	// 1. Event: The DONK World Championships — 2026 Premiere
	$post1 = get_posts( array(
		'post_type'   => 'event',
		'name'        => '2026-donk-world-championships',
		'post_status' => 'any',
	) );

	if ( empty( $post1 ) ) {
		$post1_id = wp_insert_post( array(
			'post_title'   => 'The DONK World Championships — 2026 Premiere',
			'post_content' => '<p>The DONK World Championships is the sport\'s biggest stage: eight elite competitors enter the Donktagon, and only one leaves with the title. Taped live at Regal Rooms in Austin, Texas, with a full broadcast crew — multiple cameras, a drone, and a sideline reporter who takes this more seriously than she needs to — the World Championships is DONK Toss at its highest level: single-elimination bracket, matches to 21, skunk rule at 13-0.</p><p>The 2026 DONK World Championships premieres on ESPN2, Friday, August 7, as part of ESPN8: The Ocho\'s 10th Anniversary Weekend.</p>',
			'post_excerpt' => 'The flagship DONK Toss competition premieres on ESPN2 — 8 elite competitors enter the Donktagon, and only one leaves with the title.',
			'post_status'  => 'publish',
			'post_type'    => 'event',
			'post_name'    => '2026-donk-world-championships',
		) );
	} else {
		$post1_id = $post1[0]->ID;
	}

	if ( $post1_id && ! is_wp_error( $post1_id ) ) {
		update_post_meta( $post1_id, 'event_start_date', '2026-08-07' );
		update_post_meta( $post1_id, 'event_start_time', '8:30 AM CT' );
		update_post_meta( $post1_id, 'event_location_name', 'ESPN2 (National Broadcast)' );
		update_post_meta( $post1_id, 'event_location_address', 'Taped at Regal Rooms, Austin, TX' );
		update_post_meta( $post1_id, 'event_button_text', 'Watch on ESPN2' );
		update_post_meta( $post1_id, 'event_button_link', 'https://www.espn.com/watch/player/_/id/5d88a16e-7eaa-443d-b4e3-5a8693d5b8f5' );
		wp_set_object_terms( $post1_id, array( 'TV Airing' ), 'event_type' );
	}

	// 2. Event: DONK Bikini Island Challenge — Live from Disney World
	$post2 = get_posts( array(
		'post_type'   => 'event',
		'name'        => 'ocho-2026-donk-bikini-island-challenge',
		'post_status' => 'any',
	) );

	if ( empty( $post2 ) ) {
		$post2_id = wp_insert_post( array(
			'post_title'   => 'DONK Bikini Island Challenge — Live from Disney World',
			'post_content' => '<p>For The Ocho\'s 10th Anniversary, Team DONK isn\'t just on TV — we\'re on-site. The DONK Bikini Island Challenge brings the cast of Bikini Island into the arena for a heated doubles matchup against the OG DONK founders, live at ESPN Wide World of Sports at Walt Disney World.</p><p>Catch the live competition as part of the official Ocho Day schedule (11:00 AM–12:00 PM ET), then find Team DONK running demos, challenges, and giveaways in the Fan Zones at The Stadium and AdventHealth Arena all day. Admission is free.</p><p>The DONK Bikini Island Challenge premieres on ESPN2, Friday, August 7, at 10:00 AM CT, as part of ESPN8: The Ocho\'s 10th Anniversary Weekend.</p>',
			'post_excerpt' => 'Team DONK goes live at ESPN Wide World of Sports for The Ocho\'s 10th Anniversary — the cast of Bikini Island takes on the OG DONK founders in a heated doubles showdown.',
			'post_status'  => 'publish',
			'post_type'    => 'event',
			'post_name'    => 'ocho-2026-donk-bikini-island-challenge',
		) );
	} else {
		$post2_id = $post2[0]->ID;
	}

	if ( $post2_id && ! is_wp_error( $post2_id ) ) {
		update_post_meta( $post2_id, 'event_start_date', '2026-08-07' );
		update_post_meta( $post2_id, 'event_start_time', '10:00 AM CT' );
		update_post_meta( $post2_id, 'event_location_name', 'ESPN Wide World of Sports at Walt Disney World' );
		update_post_meta( $post2_id, 'event_location_address', 'Lake Buena Vista, FL' );
		update_post_meta( $post2_id, 'event_button_text', 'Watch on ESPN2' );
		update_post_meta( $post2_id, 'event_button_link', 'https://www.espn.com/watch/player/_/id/9de2723a-67d0-4827-b725-518aeb9dad81' );
		wp_set_object_terms( $post2_id, array( 'Live Activation', 'TV Airing' ), 'event_type' );
	}

	global $wp_rewrite;
	if ( isset( $wp_rewrite ) ) {
		$wp_rewrite->flush_rules( true );
	}
}
add_action( 'init', 'donktoss_seed_actual_espn_events', 60 );

/**
 * Enqueue WP Media Uploader scripts in Admin for Event post type
 */
function donktoss_enqueue_event_admin_media_scripts( $hook ) {
	global $post_type;
	if ( ( 'post.php' === $hook || 'post-new.php' === $hook ) && 'event' === $post_type ) {
		wp_enqueue_media();
	}
}
add_action( 'admin_enqueue_scripts', 'donktoss_enqueue_event_admin_media_scripts' );

/**
 * Register Native WordPress Meta Box for Event Details (Guarantees fields appear on WP Admin Edit screen)
 */
function donktoss_add_event_meta_boxes() {
	add_meta_box(
		'donktoss_event_details_mb',
		'Event Details & Media Uploads',
		'donktoss_render_event_meta_box',
		'event',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'donktoss_add_event_meta_boxes' );

/**
 * Render Event Details Meta Box in WP Admin
 */
function donktoss_render_event_meta_box( $post ) {
	wp_nonce_field( 'donktoss_save_event_meta', 'donktoss_event_meta_nonce' );

	$start_date      = get_post_meta( $post->ID, 'event_start_date', true );
	$start_time      = get_post_meta( $post->ID, 'event_start_time', true );
	$end_time        = get_post_meta( $post->ID, 'event_end_time', true );
	$location_name   = get_post_meta( $post->ID, 'event_location_name', true );
	$location_addr   = get_post_meta( $post->ID, 'event_location_address', true );
	$button_text     = get_post_meta( $post->ID, 'event_button_text', true );
	$button_link     = get_post_meta( $post->ID, 'event_button_link', true );
	$thumbnail_image = get_post_meta( $post->ID, 'event_thumbnail_image', true );
	$hero_image      = get_post_meta( $post->ID, 'event_hero_image', true );
	$promo_image     = get_post_meta( $post->ID, 'event_promo_image', true );

	if ( is_array( $thumbnail_image ) && isset( $thumbnail_image['url'] ) ) { $thumbnail_image = $thumbnail_image['url']; }
	if ( is_array( $hero_image ) && isset( $hero_image['url'] ) ) { $hero_image = $hero_image['url']; }
	if ( is_array( $promo_image ) && isset( $promo_image['url'] ) ) { $promo_image = $promo_image['url']; }
	?>
	<style>
		.donktoss-admin-field-group { margin-bottom: 18px; }
		.donktoss-admin-field-group label { display: block; font-weight: 600; margin-bottom: 5px; color: #1d2327; }
		.donktoss-admin-field-group input[type="text"],
		.donktoss-admin-field-group input[type="date"],
		.donktoss-admin-field-group input[type="url"] { width: 100%; max-width: 550px; padding: 6px 10px; border-radius: 4px; border: 1px solid #8c8f94; }
		.donktoss-admin-field-group .description { font-style: italic; color: #646970; font-size: 12px; margin-top: 4px; }
		.donktoss-admin-flex-row { display: flex; gap: 30px; flex-wrap: wrap; }
		.donktoss-admin-col { flex: 1; min-width: 280px; }
		.donktoss-media-box { background: #f6f7f7; border: 1px solid #dcdcde; border-radius: 6px; padding: 12px; margin-top: 5px; max-width: 550px; }
		.donktoss-media-preview-img { max-width: 140px; max-height: 140px; border-radius: 6px; display: block; margin-bottom: 10px; border: 1px solid #c3c4c7; background: #fff; }
		.donktoss-btn-group { display: flex; gap: 8px; align-items: center; }
	</style>

	<div class="donktoss-admin-flex-row">
		<div class="donktoss-admin-col">
			<div class="donktoss-admin-field-group">
				<label for="event_start_date">Event Start Date <span style="color:red;">*</span></label>
				<input type="date" id="event_start_date" name="event_start_date" value="<?php echo esc_attr( $start_date ); ?>" required />
				<div class="description">Format: YYYY-MM-DD</div>
			</div>

			<div class="donktoss-admin-field-group">
				<label for="event_start_time">Start Time</label>
				<input type="text" id="event_start_time" name="event_start_time" value="<?php echo esc_attr( $start_time ); ?>" placeholder="e.g. 10:00 AM CT" />
			</div>

			<div class="donktoss-admin-field-group">
				<label for="event_end_time">End Time (Optional)</label>
				<input type="text" id="event_end_time" name="event_end_time" value="<?php echo esc_attr( $end_time ); ?>" placeholder="e.g. 1:00 PM CT" />
			</div>

			<div class="donktoss-admin-field-group">
				<label for="event_location_name">Location / Venue Name</label>
				<input type="text" id="event_location_name" name="event_location_name" value="<?php echo esc_attr( $location_name ); ?>" placeholder="e.g. ESPN Wide World of Sports" />
			</div>

			<div class="donktoss-admin-field-group">
				<label for="event_location_address">City / Address</label>
				<input type="text" id="event_location_address" name="event_location_address" value="<?php echo esc_attr( $location_addr ); ?>" placeholder="e.g. Lake Buena Vista, FL" />
			</div>

			<div class="donktoss-admin-field-group">
				<label for="event_button_text">CTA Button Text</label>
				<input type="text" id="event_button_text" name="event_button_text" value="<?php echo esc_attr( $button_text ); ?>" placeholder="e.g. Watch on ESPN2" />
			</div>

			<div class="donktoss-admin-field-group">
				<label for="event_button_link">CTA Button Link / URL</label>
				<input type="url" id="event_button_link" name="event_button_link" value="<?php echo esc_attr( $button_link ); ?>" placeholder="https://www.espn.com/watch/..." />
			</div>
		</div>

		<div class="donktoss-admin-col">
			<!-- Field 1: Thumbnail Image (Square 1:1) -->
			<div class="donktoss-admin-field-group">
				<label>Thumbnail Image (Square 1:1)</label>
				<div class="donktoss-media-box">
					<input type="hidden" id="event_thumbnail_image" name="event_thumbnail_image" value="<?php echo esc_attr( $thumbnail_image ); ?>" />
					<div id="event_thumbnail_image_preview">
						<?php if ( $thumbnail_image ) : ?>
							<img src="<?php echo esc_url( $thumbnail_image ); ?>" class="donktoss-media-preview-img" />
						<?php endif; ?>
					</div>
					<div class="donktoss-btn-group">
						<button type="button" class="button button-primary donktoss-upload-img-btn" data-input-id="event_thumbnail_image" data-preview-id="event_thumbnail_image_preview">
							<?php echo $thumbnail_image ? '📷 Change Thumbnail' : '📷 Upload / Select Thumbnail'; ?>
						</button>
						<button type="button" class="button donktoss-remove-img-btn" data-input-id="event_thumbnail_image" data-preview-id="event_thumbnail_image_preview" style="<?php echo $thumbnail_image ? '' : 'display:none;'; ?>">
							Remove
						</button>
					</div>
					<div class="description">Used for event listing cards. Square 1:1 aspect ratio.</div>
				</div>
			</div>

			<!-- Field 2: Hero Image (Widescreen 16:9) -->
			<div class="donktoss-admin-field-group">
				<label>Hero Image (Widescreen 16:9)</label>
				<div class="donktoss-media-box">
					<input type="hidden" id="event_hero_image" name="event_hero_image" value="<?php echo esc_attr( $hero_image ); ?>" />
					<div id="event_hero_image_preview">
						<?php if ( $hero_image ) : ?>
							<img src="<?php echo esc_url( $hero_image ); ?>" class="donktoss-media-preview-img" />
						<?php endif; ?>
					</div>
					<div class="donktoss-btn-group">
						<button type="button" class="button button-primary donktoss-upload-img-btn" data-input-id="event_hero_image" data-preview-id="event_hero_image_preview">
							<?php echo $hero_image ? '🖼️ Change Hero Image' : '🖼️ Upload / Select Hero Image'; ?>
						</button>
						<button type="button" class="button donktoss-remove-img-btn" data-input-id="event_hero_image" data-preview-id="event_hero_image_preview" style="<?php echo $hero_image ? '' : 'display:none;'; ?>">
							Remove
						</button>
					</div>
					<div class="description">Used as top banner on single event detail page. Widescreen 16:9 aspect ratio.</div>
				</div>
			</div>

			<!-- Field 3: Promo Graphic / Flyer (Any Aspect) -->
			<div class="donktoss-admin-field-group">
				<label>Promo Graphic / Flyer (Any Aspect)</label>
				<div class="donktoss-media-box">
					<input type="hidden" id="event_promo_image" name="event_promo_image" value="<?php echo esc_attr( $promo_image ); ?>" />
					<div id="event_promo_image_preview">
						<?php if ( $promo_image ) : ?>
							<img src="<?php echo esc_url( $promo_image ); ?>" class="donktoss-media-preview-img" />
						<?php endif; ?>
					</div>
					<div class="donktoss-btn-group">
						<button type="button" class="button button-primary donktoss-upload-img-btn" data-input-id="event_promo_image" data-preview-id="event_promo_image_preview">
							<?php echo $promo_image ? '🎨 Change Promo Flyer' : '🎨 Upload / Select Promo Flyer'; ?>
						</button>
						<button type="button" class="button donktoss-remove-img-btn" data-input-id="event_promo_image" data-preview-id="event_promo_image_preview" style="<?php echo $promo_image ? '' : 'display:none;'; ?>">
							Remove
						</button>
					</div>
					<div class="description">Optional promotional flyer graphic inside event description body.</div>
				</div>
			</div>
		</div>
	</div>

	<script>
	jQuery(document).ready(function($){
		$('.donktoss-upload-img-btn').on('click', function(e){
			e.preventDefault();
			var button = $(this);
			var inputId = button.data('input-id');
			var previewId = button.data('preview-id');

			var frame = wp.media({
				title: 'Select or Upload Event Image',
				button: { text: 'Use This Image' },
				multiple: false
			});

			frame.on('select', function(){
				var attachment = frame.state().get('selection').first().toJSON();
				$('#' + inputId).val(attachment.url);
				$('#' + previewId).html('<img src="' + attachment.url + '" class="donktoss-media-preview-img" />');
				button.text('Change Image');
				button.siblings('.donktoss-remove-img-btn').show();
			});

			frame.open();
		});

		$('.donktoss-remove-img-btn').on('click', function(e){
			e.preventDefault();
			var button = $(this);
			var inputId = button.data('input-id');
			var previewId = button.data('preview-id');

			$('#' + inputId).val('');
			$('#' + previewId).empty();
			button.hide();
			button.siblings('.donktoss-upload-img-btn').text('Upload / Select Image');
		});
	});
	</script>
	<?php
}

/**
 * Save Native WordPress Meta Box Fields
 */
function donktoss_save_event_meta_box( $post_id ) {
	if ( ! isset( $_POST['donktoss_event_meta_nonce'] ) || ! wp_verify_nonce( $_POST['donktoss_event_meta_nonce'], 'donktoss_save_event_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$fields = array(
		'event_start_date',
		'event_start_time',
		'event_end_time',
		'event_location_name',
		'event_location_address',
		'event_button_text',
		'event_button_link',
		'event_thumbnail_image',
		'event_hero_image',
		'event_promo_image',
	);

	foreach ( $fields as $field ) {
		if ( isset( $_POST[ $field ] ) ) {
			update_post_meta( $post_id, $field, sanitize_text_field( $_POST[ $field ] ) );
		}
	}
}
add_action( 'save_post_event', 'donktoss_save_event_meta_box' );







