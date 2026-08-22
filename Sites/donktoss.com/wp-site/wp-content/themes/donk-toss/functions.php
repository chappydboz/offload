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
define( 'CHILD_THEME_DONK_TOSS_VERSION', '4.6.0' );

/**
 * Include Custom Post Type & ACF Events definitions
 */
require_once get_theme_file_path( '/cpt-event.php' );

/**
 * Include FAQ CPT, Taxonomy & ACF Accordion Block definitions
 */
require_once get_theme_file_path( '/cpt-faq.php' );


/**
 * Enqueue styles (priority 100 ensures child styles load after all plugin stylesheets)
 */
function child_enqueue_styles() {

	wp_enqueue_style( 'donk-toss-theme-css', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), CHILD_THEME_DONK_TOSS_VERSION, 'all' );

}

add_action( 'wp_enqueue_scripts', 'child_enqueue_styles', 100 );

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

/**
 * Ensure Checkout Field Labels Always Display Above Fields (Remove screen-reader-text)
 */
function donktoss_show_all_checkout_field_labels( $args, $key, $value ) {
	if ( isset( $args['label_class'] ) && is_array( $args['label_class'] ) ) {
		$args['label_class'] = array_diff( $args['label_class'], array( 'screen-reader-text' ) );
	}
	return $args;
}
add_filter( 'woocommerce_form_field_args', 'donktoss_show_all_checkout_field_labels', 9999, 3 );
add_filter( 'woocommerce_checkout_fields', function( $fields ) {
	foreach ( array( 'billing', 'shipping', 'account', 'order' ) as $section ) {
		if ( isset( $fields[ $section ] ) && is_array( $fields[ $section ] ) ) {
			foreach ( $fields[ $section ] as $key => &$field ) {
				if ( isset( $field['label_class'] ) && is_array( $field['label_class'] ) ) {
					$field['label_class'] = array_diff( $field['label_class'], array( 'screen-reader-text' ) );
				}
			}
		}
	}
	return $fields;
}, 9999, 1 );

/**
 * Klaviyo SMS Consent Disclosure - Fine Print & Toggle on Checkbox Click
 */
add_action( 'init', function() {
	if ( function_exists( 'kl_mobile_compliance_text' ) ) {
		remove_action( 'woocommerce_after_checkout_billing_form', 'kl_mobile_compliance_text' );
	}
} );

add_action( 'woocommerce_after_checkout_billing_form', function() {
	$klaviyo_settings = get_option( 'klaviyo_settings' );
	if ( ! empty( $klaviyo_settings['klaviyo_sms_consent_disclosure_text'] ) && ! empty( $klaviyo_settings['klaviyo_sms_subscribe_checkbox'] ) ) {
		echo '<div id="klaviyo_sms_disclosure_box" class="kl_sms_consent_disclosure_text" style="display:none; font-size:11px; line-height:1.45; color:rgba(255,255,255,0.65); margin-top:8px; margin-bottom:14px; padding:8px 12px; background:rgba(0,0,0,0.2); border-left:2px solid #fd6d25; border-radius:4px;">' . esc_html( $klaviyo_settings['klaviyo_sms_consent_disclosure_text'] ) . '</div>';
	}
}, 99 );

add_action( 'wp_footer', function() {
	if ( function_exists( 'is_checkout' ) && is_checkout() ) {
		?>
		<script>
		jQuery(function($) {
			function toggleKlaviyoSms() {
				var $cb = $('#kl_sms_consent_checkbox');
				var $box = $('#klaviyo_sms_disclosure_box');
				if ($cb.length && $box.length) {
					if ($cb.is(':checked')) {
						$box.slideDown(150);
					} else {
						$box.slideUp(150);
					}
				}
			}
			$(document).on('change', '#kl_sms_consent_checkbox', toggleKlaviyoSms);
			toggleKlaviyoSms();
		});
		</script>
		<?php
	}
}, 99 );

/**
 * Custom Breadcrumbs Hierarchy:
 * - Checkout: HOME > SHOP > CART > CHECKOUT
 * - Cart: HOME > SHOP > CART
 */
function donktoss_checkout_shop_breadcrumb( $items, $args ) {
	if ( ( function_exists( 'is_checkout' ) && is_checkout() ) || is_page( 'checkout' ) ) {
		$shop_page_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'shop' ) : 0;
		$shop_url     = $shop_page_id > 0 ? get_permalink( $shop_page_id ) : home_url( '/shop/' );
		$shop_link    = sprintf( '<a href="%s"><span>%s</span></a>', esc_url( $shop_url ), __( 'Shop', 'donk-toss' ) );

		$cart_page_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'cart' ) : 0;
		$cart_url     = $cart_page_id > 0 ? get_permalink( $cart_page_id ) : home_url( '/cart/' );
		$cart_link    = sprintf( '<a href="%s"><span>%s</span></a>', esc_url( $cart_url ), __( 'Cart', 'donk-toss' ) );

		if ( ! empty( $items ) && is_array( $items ) ) {
			array_splice( $items, 1, 0, array( $shop_link, $cart_link ) );
		} else {
			$items[] = $shop_link;
			$items[] = $cart_link;
		}
	} elseif ( ( function_exists( 'is_cart' ) && is_cart() ) || is_page( 'cart' ) ) {
		$shop_page_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'shop' ) : 0;
		$shop_url     = $shop_page_id > 0 ? get_permalink( $shop_page_id ) : home_url( '/shop/' );
		$shop_link    = sprintf( '<a href="%s"><span>%s</span></a>', esc_url( $shop_url ), __( 'Shop', 'donk-toss' ) );

		if ( ! empty( $items ) && is_array( $items ) ) {
			array_splice( $items, 1, 0, array( $shop_link ) );
		} else {
			$items[] = $shop_link;
		}
	} elseif ( is_page( 'affiliate-area' ) || ( function_exists( 'affwp_is_affiliate_area' ) && affwp_is_affiliate_area() ) ) {
		$myaccount_page_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'myaccount' ) : 0;
		$myaccount_url     = $myaccount_page_id > 0 ? get_permalink( $myaccount_page_id ) : home_url( '/my-account/' );
		$myaccount_link    = sprintf( '<a href="%s"><span>%s</span></a>', esc_url( $myaccount_url ), __( 'My account', 'donk-toss' ) );

		if ( ! empty( $items ) && is_array( $items ) ) {
			array_splice( $items, 1, 0, array( $myaccount_link ) );
		} else {
			$items[] = $myaccount_link;
		}
	}
	return $items;
}
add_filter( 'astra_breadcrumb_trail_items', 'donktoss_checkout_shop_breadcrumb', 20, 2 );

/**
 * Add page-slug class to <body> for precise scoped styling
 */
add_filter( 'body_class', function( $classes ) {
	if ( is_page() || is_singular() ) {
		$post_obj = get_queried_object();
		if ( $post_obj && isset( $post_obj->post_name ) ) {
			$classes[] = 'page-slug-' . sanitize_html_class( $post_obj->post_name );
		} elseif ( isset( $GLOBALS['post']->post_name ) ) {
			$classes[] = 'page-slug-' . sanitize_html_class( $GLOBALS['post']->post_name );
		}
	}
	if ( function_exists( 'is_account_page' ) && ( is_account_page() || is_wc_endpoint_url() ) ) {
		$classes[] = 'woocommerce-account';
		$classes[] = 'page-slug-my-account';
	}
	return $classes;
} );

/**
 * Customize Affiliate QR Code link to open a high-res 800x800 image
 */
add_action( 'init', function() {
	if ( function_exists( 'affiliatewp_affiliate_qr_codes' ) && isset( affiliatewp_affiliate_qr_codes()->affiliate_area ) ) {
		remove_action( 'affwp_affiliate_dashboard_urls_before_generator', array( affiliatewp_affiliate_qr_codes()->affiliate_area, 'render_qr_code' ) );
		add_action( 'affwp_affiliate_dashboard_urls_before_generator', 'donktoss_render_affiliate_qr_code', 10 );
	}
}, 20 );

function donktoss_render_affiliate_qr_code( $affiliate_id ) {
	if ( ! function_exists( 'affiliatewp_affiliate_qr_codes' ) ) {
		return;
	}

	$generator = affiliatewp_affiliate_qr_codes()->generator;
	$image_url = $generator->get_code_for_affiliate( $affiliate_id );

	// Set margin=1 and size=800 for high-res QR code link
	$image_url_href = add_query_arg(
		array(
			'margin' => 1,
			'size'   => 800,
		),
		$image_url
	);

	// Set margin=0 for display
	$image_url_src = add_query_arg( array( 'margin' => 0 ), $image_url );

	$image   = $generator->build_image_html( $image_url_src );
	$qr_code = sprintf( '<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>', esc_url( $image_url_href ), $image );
	?>
	<div class="affwp-card affwp-affiliate-link affwp-qr-code-card">
		<div class="affwp-card__header affwp-affiliate-link__header">
			<h3><?php esc_html_e( 'Your QR Code', 'affiliatewp-affiliate-qr-codes' ); ?></h3>
		</div>
		<div class="affwp-card__content">
			<p class="description"><?php echo esc_html_x( 'Click the image below to view or download a high-resolution QR code (800x800) for print, packaging, and sharing.', 'affiliate area', 'affiliatewp-affiliate-qr-codes' ); ?></p>
			<p class="affwp-qr-code-wrapper"><?php echo $qr_code; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
		</div>
	</div>
	<?php
}

/**
 * Remove duplicate Astra Addon hamburger icon on Affiliate Area WooCommerce account menu item
 */
add_filter( 'astra_addon_woo_account_menu_icon', function( $icon, $endpoint ) {
	if ( 'affiliate-area' === $endpoint ) {
		return '';
	}
	return $icon;
}, 20, 2 );