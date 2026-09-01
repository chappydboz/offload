<?php
/**
 * PostHog WooCommerce Ecommerce & Conversion Tracking Integration
 *
 * Emits standard PostHog e-commerce events:
 * - view_item (Single Product View)
 * - add_to_cart (Direct & AJAX Add to Cart)
 * - view_cart (Cart Page View)
 * - begin_checkout (Checkout Page View)
 * - purchase (Order Received / Thank You Page with Customer Identification)
 *
 * @package DonkToss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DonkToss_PostHog_WooCommerce {

	public static function init() {
		add_action( 'wp_footer', array( __CLASS__, 'render_event_telemetry' ), 99 );
	}

	public static function render_event_telemetry() {
		if ( is_admin() ) {
			return;
		}
		?>
		<script type="text/javascript">
		(function() {
			if (!window.posthog) return;

			<?php
			// 1. Single Product View (view_item)
			if ( function_exists( 'is_product' ) && is_product() ) {
				global $post;
				$product = wc_get_product( $post->ID );
				if ( $product ) {
					$data = array(
						'product_id' => $product->get_id(),
						'name'       => $product->get_name(),
						'price'      => (float) $product->get_price(),
						'sku'        => $product->get_sku(),
						'currency'   => get_woocommerce_currency(),
						'category'   => wp_strip_all_tags( wc_get_product_category_list( $product->get_id() ) ),
					);
					echo 'posthog.capture("view_item", ' . wp_json_encode( $data ) . ');' . "\n";
				}
			}

			// 2. Cart Page (view_cart)
			if ( function_exists( 'is_cart' ) && is_cart() && function_exists( 'WC' ) && WC()->cart && ! WC()->cart->is_empty() ) {
				$cart_items = array();
				foreach ( WC()->cart->get_cart() as $cart_item ) {
					$p = $cart_item['data'];
					$cart_items[] = array(
						'product_id' => $p->get_id(),
						'name'       => $p->get_name(),
						'price'      => (float) $p->get_price(),
						'quantity'   => (int) $cart_item['quantity'],
					);
				}
				$cart_data = array(
					'value'       => (float) WC()->cart->get_total( 'edit' ),
					'currency'    => get_woocommerce_currency(),
					'items_count' => (int) WC()->cart->get_cart_contents_count(),
					'items'       => $cart_items,
				);
				echo 'posthog.capture("view_cart", ' . wp_json_encode( $cart_data ) . ');' . "\n";
			}

			// 3. Checkout Page (begin_checkout)
			if ( function_exists( 'is_checkout' ) && is_checkout() && ! is_order_received_page() && function_exists( 'WC' ) && WC()->cart && ! WC()->cart->is_empty() ) {
				$checkout_items = array();
				foreach ( WC()->cart->get_cart() as $cart_item ) {
					$p = $cart_item['data'];
					$checkout_items[] = array(
						'product_id' => $p->get_id(),
						'name'       => $p->get_name(),
						'price'      => (float) $p->get_price(),
						'quantity'   => (int) $cart_item['quantity'],
					);
				}
				$checkout_data = array(
					'value'       => (float) WC()->cart->get_total( 'edit' ),
					'currency'    => get_woocommerce_currency(),
					'items_count' => (int) WC()->cart->get_cart_contents_count(),
					'items'       => $checkout_items,
				);
				echo 'posthog.capture("begin_checkout", ' . wp_json_encode( $checkout_data ) . ');' . "\n";
			}

			// 4. Order Confirmation / Thank You Page (purchase)
			if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
				global $wp;
				$order_id = isset( $wp->query_vars['order-received'] ) ? absint( $wp->query_vars['order-received'] ) : 0;
				if ( ! $order_id && isset( $_GET['order-received'] ) ) {
					$order_id = absint( $_GET['order-received'] );
				}
				if ( $order_id ) {
					$order = wc_get_order( $order_id );
					if ( $order ) {
						$order_items = array();
						foreach ( $order->get_items() as $item ) {
							$p = $item->get_product();
							$order_items[] = array(
								'product_id' => $item->get_product_id(),
								'name'       => $item->get_name(),
								'quantity'   => (int) $item->get_quantity(),
								'total'      => (float) $item->get_total(),
								'sku'        => $p ? $p->get_sku() : '',
							);
						}

						$billing_email = $order->get_billing_email();
						$billing_name  = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );

						if ( $billing_email ) {
							$person_props = array(
								'email'      => $billing_email,
								'name'       => $billing_name,
								'phone'      => $order->get_billing_phone(),
								'city'       => $order->get_billing_city(),
								'state'      => $order->get_billing_state(),
								'country'    => $order->get_billing_country(),
							);
							echo 'posthog.identify(' . wp_json_encode( $billing_email ) . ', ' . wp_json_encode( $person_props ) . ');' . "\n";
						}

						$purchase_data = array(
							'order_id'       => $order_id,
							'order_number'   => $order->get_order_number(),
							'revenue'        => (float) $order->get_total(),
							'value'          => (float) $order->get_total(),
							'subtotal'       => (float) $order->get_subtotal(),
							'tax'            => (float) $order->get_total_tax(),
							'shipping'       => (float) $order->get_shipping_total(),
							'currency'       => $order->get_currency(),
							'payment_method' => $order->get_payment_method_title(),
							'coupon_codes'   => $order->get_coupon_codes(),
							'items_count'    => (int) $order->get_item_count(),
							'items'          => $order_items,
						);
						echo 'posthog.capture("purchase", ' . wp_json_encode( $purchase_data ) . ');' . "\n";
					}
				}
			}
			?>

			// 5. Client-Side AJAX / Direct Add-to-Cart Listeners
			if (typeof jQuery !== 'undefined') {
				jQuery(document).ready(function($) {
					// AJAX add to cart on archive/shop loops
					$(document.body).on('added_to_cart', function(event, fragments, cart_hash, $button) {
						var product_id = $button ? $button.data('product_id') : null;
						var quantity = $button ? ($button.data('quantity') || 1) : 1;
						var product_name = $button ? ($button.data('product_name') || $button.attr('aria-label') || 'Product') : 'Product';
						posthog.capture('add_to_cart', {
							product_id: product_id,
							name: product_name,
							quantity: quantity
						});
					});

					// Single product page add to cart button click
					$('form.cart').on('submit', function() {
						var $form = $(this);
						var qty = $form.find('input[name="quantity"]').val() || 1;
						var prodId = $form.find('[name="add-to-cart"]').val() || $('button[name="add-to-cart"]').val();
						var prodTitle = $('h1.product_title').text().trim() || 'Product';
						var prodPrice = $('.price .woocommerce-Price-amount').first().text().replace(/[^0-9.]/g, '');
						posthog.capture('add_to_cart', {
							product_id: prodId,
							name: prodTitle,
							price: parseFloat(prodPrice) || 0,
							quantity: parseInt(qty, 10)
						});
					});
				});
			}
		})();
		</script>
		<?php
	}
}

DonkToss_PostHog_WooCommerce::init();
