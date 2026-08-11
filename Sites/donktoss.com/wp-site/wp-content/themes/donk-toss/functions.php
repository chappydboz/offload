<?php
/**
 * Donk Toss Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Donk Toss
 * @since 1.0.0
 */

/**
 * Define Constants
 */
define( 'CHILD_THEME_DONK_TOSS_VERSION', '3.1.0' );

/**
 * Include Custom Post Type & ACF Events definitions
 */
require_once get_theme_file_path( '/cpt-event.php' );

/**
 * Include FAQ CPT, Taxonomy & ACF Accordion Block definitions
 */
require_once get_theme_file_path( '/cpt-faq.php' );


/**
 * Enqueue styles
 */
function child_enqueue_styles() {

	wp_enqueue_style( 'donk-toss-theme-css', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), CHILD_THEME_DONK_TOSS_VERSION, 'all' );

}

add_action( 'wp_enqueue_scripts', 'child_enqueue_styles', 15 );