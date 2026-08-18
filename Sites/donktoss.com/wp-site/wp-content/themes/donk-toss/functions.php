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
define( 'CHILD_THEME_DONK_TOSS_VERSION', '4.0.2' );

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

/**
 * Live Broadcast High-Traffic Performance Optimizations
 */
function donktoss_optimize_frontend_performance() {
	if ( ! is_admin() && ! is_user_logged_in() ) {
		// Disable Heartbeat on frontend to save PHP workers
		wp_deregister_script( 'heartbeat' );

		// Disable WooCommerce cart fragments on non-cart/checkout pages
		if ( function_exists( 'is_cart' ) && function_exists( 'is_checkout' ) ) {
			if ( ! is_cart() && ! is_checkout() ) {
				wp_dequeue_script( 'wc-cart-fragments' );
			}
		}
	}
}
add_action( 'wp_enqueue_scripts', 'donktoss_optimize_frontend_performance', 99 );

/**
 * Fix WooCommerce 100% Coupon / $0.00 Free Order Checkout "No Payment Method Provided" Error
 */
function donktoss_enable_free_checkout_gateway( $available_gateways ) {
	if ( is_admin() ) {
		return $available_gateways;
	}
	if ( function_exists( 'WC' ) && WC()->cart && (float) WC()->cart->get_total( 'edit' ) === 0.00 ) {
		if ( class_exists( 'WC_Gateway_COD' ) ) {
			$free_gateway = new WC_Gateway_COD();
			$free_gateway->id           = 'cod';
			$free_gateway->title        = __( 'Free Order', 'donk-toss' );
			$free_gateway->description  = __( 'No payment required for this 100% discounted order.', 'donk-toss' );
			$free_gateway->instructions = ''; // Remove "Pay with cash upon delivery"
			$available_gateways['cod']  = $free_gateway;
		}
	}
	return $available_gateways;
}
add_filter( 'woocommerce_available_payment_gateways', 'donktoss_enable_free_checkout_gateway', 99, 1 );

function donktoss_handle_free_order_checkout( $data ) {
	if ( function_exists( 'WC' ) && WC()->cart && (float) WC()->cart->get_total( 'edit' ) === 0.00 ) {
		$data['payment_method'] = 'cod';
	}
	return $data;
}
add_filter( 'woocommerce_checkout_posted_data', 'donktoss_handle_free_order_checkout', 99, 1 );

/**
 * Clean up Order Received / Thank You page text for Free Orders
 */
function donktoss_custom_thankyou_text( $text, $order ) {
	if ( $order && (float) $order->get_total() === 0.00 ) {
		return __( 'Thank you! Your order has been received and is being processed.', 'donk-toss' );
	}
	return $text;
}
add_filter( 'woocommerce_thankyou_order_received_text', 'donktoss_custom_thankyou_text', 10, 2 );