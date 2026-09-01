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
define( 'CHILD_THEME_DONK_TOSS_VERSION', '4.9.2' );

/**
 * Include Custom Post Type & ACF Events definitions
 */
require_once get_theme_file_path( '/cpt-event.php' );

/**
 * Include FAQ CPT, Taxonomy & ACF Accordion Block definitions
 */
require_once get_theme_file_path( '/cpt-faq.php' );

/**
 * Include Outgoing Email Rate Limiter & Background Throttling (SendLayer 50/min protection)
 */
require_once get_theme_file_path( '/inc/rate-limiter.php' );

/**
 * Include PostHog WooCommerce Ecommerce & Conversion Tracking
 */
require_once get_theme_file_path( '/inc/posthog-woocommerce.php' );

/**
 * Include Google Merchant Center Schema & Product Feed
 */
require_once get_theme_file_path( '/inc/gmc-schema.php' );
require_once get_theme_file_path( '/inc/gmc-feed.php' );


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
 * Set dedicated icon for Affiliate Area WooCommerce account menu item
 */
add_filter( 'astra_addon_woo_account_menu_icon', function( $icon, $endpoint ) {
	if ( 'affiliate-area' === $endpoint && class_exists( 'Astra_Builder_UI_Controller' ) ) {
		return Astra_Builder_UI_Controller::fetch_svg_icon( 'tag', false );
	}
	return $icon;
}, 20, 2 );

/**
 * Hide star reviews and rating templates on single product pages
 */
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );

/**
 * Render custom product label badge inside Gutenberg WooCommerce Product Collection / Image blocks
 */
add_filter( 'render_block_woocommerce/product-image', function( $block_content, $block ) {
	$product_id = 0;
	if ( isset( $block['context']['productId'] ) ) {
		$product_id = absint( $block['context']['productId'] );
	} elseif ( isset( $block['attrs']['productId'] ) ) {
		$product_id = absint( $block['attrs']['productId'] );
	} else {
		global $post;
		if ( $post && 'product' === $post->post_type ) {
			$product_id = $post->ID;
		}
	}

	if ( ! $product_id ) {
		return $block_content;
	}

	$custom_label_text = get_post_meta( $product_id, '_custom_product_label_text', true );
	if ( empty( $custom_label_text ) ) {
		$product = wc_get_product( $product_id );
		if ( $product && ! $product->is_in_stock() ) {
			$custom_label_text = get_post_meta( $product_id, '_custom_out_of_stock_text', true );
			if ( empty( $custom_label_text ) ) {
				$custom_label_text = __( 'Out of stock', 'woocommerce' );
			}
		}
	}

	if ( ! empty( $custom_label_text ) ) {
		$badge_html = '<span class="ast-shop-product-out-of-stock custom-product-label-badge">' . esc_html( $custom_label_text ) . '</span>';
		if ( strpos( $block_content, '</a>' ) !== false ) {
			$block_content = str_replace( '</a>', $badge_html . '</a>', $block_content );
		} else {
			$block_content .= $badge_html;
		}
	}

	return $block_content;
}, 10, 2 );

/**
 * Ensure entire product card in WooCommerce grids routes to the product page on click
 */
add_action( 'wp_footer', function() {
	?>
	<script id="donktoss-product-card-click-handler">
	(function() {
		document.addEventListener('click', function(e) {
			var productCard = e.target.closest('ul.products li.product, .wc-block-grid__product, .wc-block-product');
			if (!productCard) return;

			// Do not intercept if clicking interactive elements (Add to cart, options, buttons, inputs)
			if (e.target.closest('.add_to_cart_button, .ast-on-card-button, .button, a.button, input, select, textarea, button, .ast-card-action-tooltip')) {
				return;
			}

			// Route cleanly to the product page on title, image, badge, or card click
			var productLink = productCard.querySelector('a.woocommerce-LoopProduct-link, a.ast-loop-product__link, .wc-block-grid__product-link, a.wc-block-grid__product-link, a[data-wp-on--click*="viewProduct"], a[href*="/product/"]');
			if (productLink && productLink.href) {
				e.preventDefault();
				e.stopPropagation();
				window.location.assign(productLink.href);
			}
		}, true);
	})();
	</script>
	<?php
}, 100 );

/**
 * Format coupon label as pill-style badge with green text over white background (Cart & Checkout)
 */
add_filter( 'woocommerce_cart_totals_coupon_label', function( $label, $coupon ) {
	if ( is_string( $coupon ) ) {
		$code = $coupon;
	} elseif ( is_object( $coupon ) && method_exists( $coupon, 'get_code' ) ) {
		$code = $coupon->get_code();
	} else {
		return $label;
	}

	return sprintf(
		'%s <span class="donk-coupon-pill">%s</span>',
		esc_html__( 'Coupon:', 'woocommerce' ),
		esc_html( $code )
	);
}, 20, 2 );

/**
 * Register "Help & Resources" tab on AffiliateWP Affiliate Area Dashboard
 */
add_filter( 'affwp_affiliate_area_tabs', function( $tabs ) {
	$new_tabs = array();
	foreach ( $tabs as $key => $title ) {
		$new_tabs[ $key ] = $title;
		// Place Help & FAQs right after Creatives or URLs
		if ( 'creatives' === $key ) {
			$new_tabs['resources'] = __( 'Help & FAQs', 'donk-toss' );
		}
	}
	if ( ! isset( $new_tabs['resources'] ) ) {
		$new_tabs['resources'] = __( 'Help & FAQs', 'donk-toss' );
	}
	return $new_tabs;
}, 20 );

add_filter( 'affwp_render_affiliate_dashboard_tab_resources', function( $content, $tab ) {
	$template_file = get_stylesheet_directory() . '/affiliatewp/dashboard-tab-resources.php';
	if ( file_exists( $template_file ) ) {
		ob_start();
		include $template_file;
		return ob_get_clean();
	}
	return $content;
}, 10, 2 );

/**
 * Customize page title on Order Confirmation endpoint
 */
function donktoss_customize_order_received_title( $title, $id = null ) {
	if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
		if ( ! is_admin() && ( null === $id || $id === wc_get_page_id( 'checkout' ) ) ) {
			return __( 'Order Confirmation', 'donk-toss' );
		}
	}
	return $title;
}
add_filter( 'astra_the_title', 'donktoss_customize_order_received_title', 20, 2 );
add_filter( 'the_title', 'donktoss_customize_order_received_title', 20, 2 );