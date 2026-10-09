<?php
/**
 * Donk Toss Pre-Order Management Module
 *
 * Provides:
 * - Admin "Pre-Order" Product Data Tab in WooCommerce Product Editor.
 * - Per-product Pre-Order toggle (_donktoss_preorder_enabled).
 * - Custom copy fields with intelligent defaults:
 *     1. Badge / Grid Listing Label (_donktoss_preorder_badge_label)
 *     2. Single Product Callout Blurb (_donktoss_preorder_single_blurb)
 *     3. Single Product Page Disclaimer (_donktoss_preorder_disclaimer_blurb)
 *     4. Cart & Checkout Line-Item Notice (_donktoss_preorder_cart_blurb)
 *     5. Customer Order Email Blurb (_donktoss_preorder_email_blurb)
 * - Automatic zero-stock purchasability & dynamic button copy ("Pre-Order Now").
 * - Mixed-cart awareness & separate shipping expectations notice.
 * - Order line-item & order-level metadata (_is_preorder, _donktoss_has_preorder).
 * - Warehouse internal order note & ShipStation CustomField export integration.
 * - Admin HPOS order list visual indicator badge.
 * - AutomateWoo integration: Custom rule "Order - Has Active Pre-Order Product" to halt drip emails once stock arrives.
 *
 * Strict Compliance: Zero !important CSS rules; Astra child-theme modular architecture.
 *
 * @package DonkToss
 * @since 4.9.8
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DonkToss_Preorder {

	/**
	 * Default copy constants
	 */
	const DEFAULT_BADGE_LABEL      = 'PRE-ORDER';
	const DEFAULT_SINGLE_BLURB     = 'This item is currently available for Pre-Order. Reserve yours now — orders will ship as soon as our incoming batch arrives at the warehouse.';
	const DEFAULT_DISCLAIMER_BLURB = 'Pre-Order Notice: Payment is processed at the time of order to reserve your unit from our upcoming production batch. In-stock accessories in your order ship on our standard schedule. Estimated delivery will be communicated via email updates.';
	const DEFAULT_CART_BLURB       = 'Pre-Order Item — Reserved batch unit. Will ship once incoming inventory arrives.';
	const DEFAULT_EMAIL_BLURB      = 'PRE-ORDER RESERVATION: You have secured a unit from our upcoming batch. We will send you periodic updates on our production and shipping schedule as your gear is prepped.';

	/**
	 * Initialize all hooks
	 */
	public static function init() {
		// 1. Admin Product Data Tab & Settings
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'add_product_data_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'render_product_data_panel' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_product_data_panel' ) );

		// 2. Stock, Purchasability & Button Text
		add_filter( 'woocommerce_product_is_in_stock', array( __CLASS__, 'filter_product_is_in_stock' ), 20, 2 );
		add_filter( 'woocommerce_product_backorders_allowed', array( __CLASS__, 'filter_backorders_allowed' ), 20, 2 );
		add_filter( 'woocommerce_get_availability', array( __CLASS__, 'filter_stock_availability' ), 20, 2 );
		add_filter( 'woocommerce_product_single_add_to_cart_text', array( __CLASS__, 'filter_add_to_cart_button_text' ), 20, 2 );
		add_filter( 'woocommerce_product_add_to_cart_text', array( __CLASS__, 'filter_add_to_cart_button_text' ), 20, 2 );

		// 3. Front-End Product Badges & Single Product Callouts
		add_filter( 'get_post_metadata', array( __CLASS__, 'filter_custom_label_post_meta' ), 20, 4 );
		add_filter( 'donktoss_custom_product_label_text', array( __CLASS__, 'override_product_badge_label' ), 20, 2 );
		add_filter( 'astra_woo_shop_out_of_stock_string', array( __CLASS__, 'filter_astra_out_of_stock_string' ), 20 );
		add_action( 'woocommerce_shop_loop_item_title', array( __CLASS__, 'suppress_astra_out_of_stock_badge_start' ), 7 );
		add_action( 'woocommerce_shop_loop_item_title', array( __CLASS__, 'restore_astra_out_of_stock_badge_end' ), 9 );
		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'render_single_product_callout' ), 25 );
		add_action( 'woocommerce_after_single_product_summary', array( __CLASS__, 'render_single_product_disclaimer' ), 22 );

		// 4. Cart & Checkout Displays
		add_filter( 'woocommerce_cart_item_name', array( __CLASS__, 'append_cart_item_preorder_notice' ), 20, 3 );
		add_action( 'woocommerce_before_cart', array( __CLASS__, 'render_mixed_cart_notice' ) );
		add_action( 'woocommerce_before_checkout_form', array( __CLASS__, 'render_mixed_cart_notice' ) );

		// 5. Order Creation & Line-Item Metadata
		add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'save_order_line_item_preorder_meta' ), 20, 4 );
		add_action( 'woocommerce_checkout_order_created', array( __CLASS__, 'handle_order_created' ), 20 );

		// 6. Order Confirmation Details & Customer Emails
		add_filter( 'woocommerce_order_item_name', array( __CLASS__, 'append_email_order_item_notice' ), 20, 3 );

		// 7. ShipStation Integration Filter
		add_filter( 'woocommerce_shipstation_export_custom_field_2', array( __CLASS__, 'map_shipstation_custom_field_2' ) );
		add_filter( 'woocommerce_shipstation_export_custom_field_2_value', array( __CLASS__, 'filter_shipstation_custom_field_2_value' ), 20, 2 );

		// 8. Admin HPOS & Post List Columns (Visual Indicators)
		add_filter( 'manage_woocommerce_page_wc-orders_columns', array( __CLASS__, 'add_admin_order_column' ), 25 );
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( __CLASS__, 'render_admin_order_column' ), 25, 2 );
		add_filter( 'manage_edit-shop_order_columns', array( __CLASS__, 'add_admin_order_column' ), 25 );
		add_action( 'manage_shop_order_posts_custom_column', array( __CLASS__, 'render_admin_order_column_legacy' ), 25, 2 );

		// 9. AutomateWoo Custom Rule & Variable Registration
		add_filter( 'automatewoo/rules/includes', array( __CLASS__, 'register_automatewoo_rules' ) );
		add_filter( 'automatewoo/variables', array( __CLASS__, 'register_automatewoo_variables' ) );
		add_shortcode( 'donktoss_preorder_update', array( __CLASS__, 'shortcode_preorder_update' ) );

		// 9b. Recurring 2-Week Drip Cycle Hooks (Action Scheduler)
		add_action( 'automatewoo_after_workflow_run', array( __CLASS__, 'handle_recurring_preorder_workflow' ) );
		add_action( 'donktoss_run_recurring_preorder_workflow', array( __CLASS__, 'execute_recurring_preorder_workflow' ), 10, 2 );
		add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'cleanup_recurring_on_order_status_change' ), 20, 4 );

		// 10. Front-End Styles (Zero !important)
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_preorder_styles' ), 110 );

		// 11. Admin Stock & Backorder Notification Suppression for Pre-Orders
		add_filter( 'woocommerce_should_send_no_stock_notification', array( __CLASS__, 'suppress_no_stock_notification' ), 20, 2 );
		add_filter( 'woocommerce_should_send_backorder_notification', array( __CLASS__, 'suppress_backorder_notification' ), 20, 2 );
		add_filter( 'woocommerce_should_send_low_stock_notification', array( __CLASS__, 'suppress_low_stock_notification' ), 20, 2 );
		add_filter( 'woocommerce_email_recipient_no_stock', array( __CLASS__, 'suppress_stock_email_recipient' ), 20, 2 );
		add_filter( 'woocommerce_email_recipient_backorder', array( __CLASS__, 'suppress_backorder_email_recipient' ), 20, 2 );
		add_filter( 'woocommerce_email_recipient_low_stock', array( __CLASS__, 'suppress_stock_email_recipient' ), 20, 2 );
	}

	/**
	 * Helper: Check if a product currently has Pre-Order mode enabled
	 *
	 * @param WC_Product|int $product Product object or ID.
	 * @return bool
	 */
	public static function is_product_preorder_enabled( $product ) {
		if ( is_numeric( $product ) ) {
			$product = wc_get_product( $product );
		}
		if ( ! is_a( $product, 'WC_Product' ) ) {
			return false;
		}

		// 1. Direct manual activation
		$enabled = $product->get_meta( '_donktoss_preorder_enabled', true );
		if ( 'yes' === $enabled ) {
			return true;
		}

		// 2. Automatic cutover when inventory reaches zero
		$auto_zero = $product->get_meta( '_donktoss_preorder_auto_zero_stock', true );
		if ( 'yes' === $auto_zero ) {
			if ( $product->managing_stock() ) {
				$qty = $product->get_stock_quantity();
				if ( null !== $qty && $qty <= 0 ) {
					return true;
				}
			} else {
				if ( 'outofstock' === $product->get_stock_status() ) {
					return true;
				}
			}
		}

		// 3. Variation fallback to parent product
		if ( $product->is_type( 'variation' ) && $product->get_parent_id() ) {
			$parent = wc_get_product( $product->get_parent_id() );
			if ( $parent && self::is_product_preorder_enabled( $parent ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Helper: Check if a given order contains any pre-ordered line items
	 *
	 * @param WC_Order|int $order Order object or ID.
	 * @return bool
	 */
	public static function order_has_preorder( $order ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}
		if ( ! is_a( $order, 'WC_Order' ) ) {
			return false;
		}

		if ( 'yes' === $order->get_meta( '_donktoss_has_preorder', true ) ) {
			return true;
		}

		foreach ( $order->get_items() as $item ) {
			if ( 'yes' === $item->get_meta( '_is_preorder', true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Helper: Check if an order still contains any items whose products are ACTIVELY in pre-order mode right now
	 *
	 * Used to halt automated drip workflows dynamically once incoming stock arrives.
	 *
	 * @param WC_Order|int $order Order object or ID.
	 * @return bool
	 */
	public static function order_has_active_preorder_product( $order ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}
		if ( ! is_a( $order, 'WC_Order' ) ) {
			return false;
		}

		$status = $order->get_status();
		if ( in_array( $status, array( 'completed', 'cancelled', 'refunded', 'failed' ), true ) ) {
			return false;
		}

		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			if ( $product && self::is_product_preorder_enabled( $product ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * 1. Add "Pre-Order" Tab in WooCommerce Product Data meta box
	 */
	public static function add_product_data_tab( $tabs ) {
		$tabs['donktoss_preorder'] = array(
			'label'    => __( 'Pre-Order', 'donk-toss' ),
			'target'   => 'donktoss_preorder_product_data',
			'class'    => array( 'show_if_simple', 'show_if_variable' ),
			'priority' => 35, // Positioned right after Shipping / Inventory
		);
		return $tabs;
	}

	/**
	 * 1b. Render "Pre-Order" Tab Panel content in WP Admin
	 */
	public static function render_product_data_panel() {
		global $post;
		$product = wc_get_product( $post->ID );
		if ( ! $product ) {
			return;
		}

		$is_enabled       = $product->get_meta( '_donktoss_preorder_enabled', true );
		$is_auto_zero     = $product->get_meta( '_donktoss_preorder_auto_zero_stock', true );
		$badge_label      = $product->get_meta( '_donktoss_preorder_badge_label', true );
		$single_blurb     = $product->get_meta( '_donktoss_preorder_single_blurb', true );
		$disclaimer_blurb = $product->get_meta( '_donktoss_preorder_disclaimer_blurb', true );
		$cart_blurb       = $product->get_meta( '_donktoss_preorder_cart_blurb', true );
		$email_blurb      = $product->get_meta( '_donktoss_preorder_email_blurb', true );
		$latest_update    = $product->get_meta( '_donktoss_preorder_latest_update', true );
		?>
		<div id="donktoss_preorder_product_data" class="panel woocommerce_options_panel hidden">
			<div class="options_group" style="border-bottom: 1px solid #eee; padding-bottom: 12px; margin-bottom: 12px;">
				<p style="padding: 10px 15px; background: #fff8e5; border-left: 4px solid #ff6b00; margin: 10px 15px; color: #664d03; font-weight: 500;">
					<?php esc_html_e( 'Pre-Order Mode keeps this product purchasable when stock runs out, adjusts button copy to "Pre-Order Now", updates grid badges, and injects clear wait-time expectations across the single product page, cart, checkout, confirmation emails, and ShipStation.', 'donk-toss' ); ?>
				</p>
				<?php
				woocommerce_wp_checkbox( array(
					'id'            => '_donktoss_preorder_enabled',
					'value'         => $is_enabled ? $is_enabled : 'no',
					'label'         => __( 'Manual Pre-Order', 'donk-toss' ),
					'description'   => __( 'Force Pre-Order mode active immediately (regardless of inventory level).', 'donk-toss' ),
					'desc_tip'      => false,
				) );

				woocommerce_wp_checkbox( array(
					'id'            => '_donktoss_preorder_auto_zero_stock',
					'value'         => $is_auto_zero ? $is_auto_zero : 'no',
					'label'         => __( 'Auto-Activate on Zero Stock', 'donk-toss' ),
					'description'   => __( 'Automatically switch into Pre-Order mode whenever stock reaches 0 (or marked out-of-stock). Sells as regular in-stock while units remain.', 'donk-toss' ),
					'desc_tip'      => false,
				) );
				?>
			</div>

			<div class="options_group">
				<h4 style="margin: 15px 15px 5px; color: #1d2327; font-size: 14px;">
					<?php esc_html_e( 'Front-End Messaging & Overrides', 'donk-toss' ); ?>
				</h4>
				<p class="description" style="margin: 0 15px 15px;">
					<?php esc_html_e( 'Leave fields empty to fall back to intelligent default copy.', 'donk-toss' ); ?>
				</p>

				<?php
				woocommerce_wp_text_input( array(
					'id'          => '_donktoss_preorder_badge_label',
					'value'       => $badge_label,
					'placeholder' => self::DEFAULT_BADGE_LABEL,
					'label'       => __( 'Grid Badge Label', 'donk-toss' ),
					'desc_tip'    => true,
					'description' => __( 'Overrides the standard custom label on product grid/archive listings when pre-order is active.', 'donk-toss' ),
				) );

				woocommerce_wp_textarea_input( array(
					'id'          => '_donktoss_preorder_single_blurb',
					'value'       => $single_blurb,
					'placeholder' => self::DEFAULT_SINGLE_BLURB,
					'label'       => __( 'Product Callout Blurb', 'donk-toss' ),
					'desc_tip'    => true,
					'description' => __( 'Prominently displayed on the single product page directly above the Add to Cart button.', 'donk-toss' ),
					'rows'        => 3,
				) );

				woocommerce_wp_textarea_input( array(
					'id'          => '_donktoss_preorder_disclaimer_blurb',
					'value'       => $disclaimer_blurb,
					'placeholder' => self::DEFAULT_DISCLAIMER_BLURB,
					'label'       => __( 'Page Disclaimer Blurb', 'donk-toss' ),
					'desc_tip'    => true,
					'description' => __( 'Detailed policy notice displayed at the bottom of the single product page.', 'donk-toss' ),
					'rows'        => 3,
				) );

				woocommerce_wp_text_input( array(
					'id'          => '_donktoss_preorder_cart_blurb',
					'value'       => $cart_blurb,
					'placeholder' => self::DEFAULT_CART_BLURB,
					'label'       => __( 'Cart/Checkout Line Blurb', 'donk-toss' ),
					'desc_tip'    => true,
					'description' => __( 'Rendered directly beneath the item name in Cart, Mini-Cart, and Checkout review tables.', 'donk-toss' ),
				) );

				woocommerce_wp_textarea_input( array(
					'id'          => '_donktoss_preorder_email_blurb',
					'value'       => $email_blurb,
					'placeholder' => self::DEFAULT_EMAIL_BLURB,
					'label'       => __( 'Order Email Blurb', 'donk-toss' ),
					'desc_tip'    => true,
					'description' => __( 'Appears in customer order confirmation receipts to reinforce pre-order status.', 'donk-toss' ),
					'rows'        => 3,
				) );
				?>
			</div>

			<div class="options_group">
				<h4 style="margin: 15px 15px 5px; color: #1d2327; font-size: 14px;">
					<?php esc_html_e( 'AutomateWoo Drip Status Override (Latest News)', 'donk-toss' ); ?>
				</h4>
				<p class="description" style="margin: 0 15px 15px;">
					<?php esc_html_e( 'Enter real-time shipping or production news to dynamically inject into all outgoing customer pre-order drip emails (via {{ order.preorder_status_update }}). If left blank, emails use the standard reassurance message.', 'donk-toss' ); ?>
				</p>

				<?php
				woocommerce_wp_textarea_input( array(
					'id'          => '_donktoss_preorder_latest_update',
					'value'       => $latest_update,
					'placeholder' => __( 'e.g. Our container cleared port customs and freight transit to our warehouse is underway. Orders begin shipping immediately upon intake.', 'donk-toss' ),
					'label'       => __( 'Latest Status Update', 'donk-toss' ),
					'desc_tip'    => true,
					'description' => __( 'Appears as an orange callout box in scheduled customer drip emails. Editing this field immediately updates all future automated drip emails across all customers waiting for this product.', 'donk-toss' ),
					'rows'        => 3,
				) );
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * 1c. Save Pre-Order product settings
	 */
	public static function save_product_data_panel( $post_id ) {
		$product = wc_get_product( $post_id );
		if ( ! $product ) {
			return;
		}

		$is_enabled   = isset( $_POST['_donktoss_preorder_enabled'] ) ? 'yes' : 'no';
		$is_auto_zero = isset( $_POST['_donktoss_preorder_auto_zero_stock'] ) ? 'yes' : 'no';
		$product->update_meta_data( '_donktoss_preorder_enabled', $is_enabled );
		$product->update_meta_data( '_donktoss_preorder_auto_zero_stock', $is_auto_zero );

		$fields = array(
			'_donktoss_preorder_badge_label'      => 'sanitize_text_field',
			'_donktoss_preorder_single_blurb'     => 'sanitize_textarea_field',
			'_donktoss_preorder_disclaimer_blurb' => 'sanitize_textarea_field',
			'_donktoss_preorder_cart_blurb'       => 'sanitize_text_field',
			'_donktoss_preorder_email_blurb'      => 'sanitize_textarea_field',
			'_donktoss_preorder_latest_update'    => 'wp_kses_post',
		);

		foreach ( $fields as $field_key => $sanitizer ) {
			if ( isset( $_POST[ $field_key ] ) ) {
				$val = call_user_func( $sanitizer, wp_unslash( $_POST[ $field_key ] ) );
				$product->update_meta_data( $field_key, $val );
			}
		}

		$product->save();
	}

	/**
	 * 2. Ensure product remains purchasable and backorders allowed when Pre-Order is active
	 */
	public static function filter_product_is_in_stock( $is_in_stock, $product ) {
		if ( self::is_product_preorder_enabled( $product ) ) {
			return true;
		}
		return $is_in_stock;
	}

	public static function filter_backorders_allowed( $allowed, $product ) {
		if ( self::is_product_preorder_enabled( $product ) ) {
			return true;
		}
		return $allowed;
	}

	public static function filter_stock_availability( $availability, $product ) {
		if ( self::is_product_preorder_enabled( $product ) ) {
			$availability['class']        = 'in-stock donktoss-preorder-availability';
			$availability['availability'] = __( 'Available for Pre-Order', 'donk-toss' );
		}
		return $availability;
	}

	/**
	 * 2b. Dynamic button copy: Change "Add to Cart" to "Pre-Order Now"
	 */
	public static function filter_add_to_cart_button_text( $text, $product ) {
		if ( self::is_product_preorder_enabled( $product ) ) {
			return __( 'Pre-Order Now', 'donk-toss' );
		}
		return $text;
	}

	/**
	 * Internal recursion guard for meta filtering
	 *
	 * @var bool
	 */
	private static $filtering_meta = false;

	/**
	 * 3a. Intercept _custom_product_label_text and _custom_out_of_stock_text metadata
	 *
	 * Ensures WPCode snippets, Astra templates, and Gutenberg block renderers
	 * display the pre-order badge label seamlessly across all loops and cards.
	 */
	public static function filter_custom_label_post_meta( $value, $object_id, $meta_key, $single ) {
		if ( self::$filtering_meta || ! $object_id ) {
			return $value;
		}

		if ( '_custom_product_label_text' === $meta_key ) {
			self::$filtering_meta = true;
			if ( self::is_product_preorder_enabled( $object_id ) ) {
				$label = self::override_product_badge_label( '', $object_id );
				self::$filtering_meta = false;
				return $single ? $label : array( $label );
			}
			self::$filtering_meta = false;
		} elseif ( '_custom_out_of_stock_text' === $meta_key ) {
			self::$filtering_meta = true;
			if ( self::is_product_preorder_enabled( $object_id ) ) {
				self::$filtering_meta = false;
				return $single ? '' : array( '' );
			}
			self::$filtering_meta = false;
		}

		return $value;
	}

	/**
	 * 3b. Suppress Astra's redundant "Out of stock" badge when pre-order is active
	 */
	public static function suppress_astra_out_of_stock_badge_start() {
		global $product;
		if ( $product && self::is_product_preorder_enabled( $product ) ) {
			remove_action( 'woocommerce_shop_loop_item_title', 'astra_woo_shop_out_of_stock', 8 );
		}
	}

	/**
	 * 3c. Restore Astra's "Out of stock" badge hook for subsequent loop items
	 */
	public static function restore_astra_out_of_stock_badge_end() {
		if ( ! has_action( 'woocommerce_shop_loop_item_title', 'astra_woo_shop_out_of_stock' ) && function_exists( 'astra_woo_shop_out_of_stock' ) ) {
			add_action( 'woocommerce_shop_loop_item_title', 'astra_woo_shop_out_of_stock', 8 );
		}
	}

	/**
	 * 3d. Filter Astra out of stock string fallback
	 */
	public static function filter_astra_out_of_stock_string( $string ) {
		global $product;
		if ( $product && self::is_product_preorder_enabled( $product ) ) {
			return '';
		}
		return $string;
	}

	/**
	 * 3e. Front-End Grid Badge Override
	 */
	public static function override_product_badge_label( $label, $product_id ) {
		$product = wc_get_product( $product_id );
		if ( $product && self::is_product_preorder_enabled( $product ) ) {
			$custom = $product->get_meta( '_donktoss_preorder_badge_label', true );
			return ! empty( $custom ) ? $custom : self::DEFAULT_BADGE_LABEL;
		}
		return $label;
	}

	/**
	 * 3b. Render Single Product Callout Blurb (Above Add to Cart)
	 */
	public static function render_single_product_callout() {
		global $product;
		if ( ! is_a( $product, 'WC_Product' ) || ! self::is_product_preorder_enabled( $product ) ) {
			return;
		}

		$blurb = $product->get_meta( '_donktoss_preorder_single_blurb', true );
		if ( empty( $blurb ) ) {
			$blurb = self::DEFAULT_SINGLE_BLURB;
		}

		echo '<div class="donktoss-preorder-single-callout">';
		echo '<div class="donktoss-preorder-callout-header">';
		echo '<span class="donktoss-preorder-badge-pill">' . esc_html( self::override_product_badge_label( '', $product->get_id() ) ) . '</span>';
		echo '<span class="donktoss-preorder-callout-title">' . esc_html__( 'Batch Reservation Active', 'donk-toss' ) . '</span>';
		echo '</div>';
		echo '<p class="donktoss-preorder-callout-body">' . esc_html( $blurb ) . '</p>';
		echo '</div>';
	}

	/**
	 * 3c. Render Single Product Page Disclaimer (Bottom of page)
	 */
	public static function render_single_product_disclaimer() {
		global $product;
		if ( ! is_a( $product, 'WC_Product' ) || ! self::is_product_preorder_enabled( $product ) ) {
			return;
		}

		$disclaimer = $product->get_meta( '_donktoss_preorder_disclaimer_blurb', true );
		if ( empty( $disclaimer ) ) {
			$disclaimer = self::DEFAULT_DISCLAIMER_BLURB;
		}

		echo '<div class="donktoss-preorder-page-disclaimer" id="donktoss-preorder-disclaimer">';
		echo '<div class="donktoss-preorder-disclaimer-inner">';
		echo '<h4 class="donktoss-preorder-disclaimer-title">';
		echo '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather-info"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg> ';
		echo esc_html__( 'Pre-Order & Delivery Information', 'donk-toss' );
		echo '</h4>';
		echo '<p class="donktoss-preorder-disclaimer-text">' . esc_html( $disclaimer ) . '</p>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * 4. Cart & Checkout Line-Item Notice
	 */
	public static function append_cart_item_preorder_notice( $name, $cart_item, $cart_item_key ) {
		$product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
		if ( ! $product || ! self::is_product_preorder_enabled( $product ) ) {
			return $name;
		}

		$cart_blurb = $product->get_meta( '_donktoss_preorder_cart_blurb', true );
		if ( empty( $cart_blurb ) ) {
			$cart_blurb = self::DEFAULT_CART_BLURB;
		}

		$badge_text = self::override_product_badge_label( '', $product->get_id() );

		$notice_html  = '<div class="donktoss-preorder-cart-blurb">';
		$notice_html .= '<span class="donktoss-preorder-inline-pill">' . esc_html( $badge_text ) . '</span> ';
		$notice_html .= '<span class="donktoss-preorder-inline-text">' . esc_html( $cart_blurb ) . '</span>';
		$notice_html .= '</div>';

		return $name . $notice_html;
	}

	/**
	 * 4b. Mixed Cart Informational Notice (In-Stock + Pre-Order items)
	 */
	public static function render_mixed_cart_notice() {
		if ( ! WC()->cart || WC()->cart->is_empty() ) {
			return;
		}

		$has_preorder = false;
		$has_instock  = false;

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
			if ( $product ) {
				if ( self::is_product_preorder_enabled( $product ) ) {
					$has_preorder = true;
				} else {
					$has_instock = true;
				}
			}
		}

		if ( $has_preorder && $has_instock ) {
			echo '<div class="woocommerce-message donktoss-mixed-cart-notice" role="alert">';
			echo '<strong>' . esc_html__( 'Note on Multiple Items:', 'donk-toss' ) . '</strong> ';
			echo esc_html__( 'Your order contains both pre-order gear and in-stock items. In-stock products ship on our standard schedule; pre-ordered kits will ship once incoming warehouse stock lands.', 'donk-toss' );
			echo '</div>';
		} elseif ( $has_preorder ) {
			echo '<div class="woocommerce-info donktoss-preorder-cart-banner" role="alert">';
			echo '<strong>' . esc_html__( 'Pre-Order Reservation:', 'donk-toss' ) . '</strong> ';
			echo esc_html__( 'Your cart includes a reserved pre-order kit. You will receive email tracking as soon as production inventory arrives at our warehouse.', 'donk-toss' );
			echo '</div>';
		}
	}

	/**
	 * 5. Save order line-item Pre-Order metadata
	 */
	public static function save_order_line_item_preorder_meta( $item, $cart_item_key, $values, $order ) {
		$product = isset( $values['data'] ) ? $values['data'] : null;
		if ( $product && self::is_product_preorder_enabled( $product ) ) {
			$item->add_meta_data( '_is_preorder', 'yes', true );
			$item->add_meta_data( __( 'Fulfillment Type', 'donk-toss' ), __( 'Pre-Order Reservation', 'donk-toss' ), true );

			$email_blurb = $product->get_meta( '_donktoss_preorder_email_blurb', true );
			if ( empty( $email_blurb ) ) {
				$email_blurb = self::DEFAULT_EMAIL_BLURB;
			}
			$item->add_meta_data( '_preorder_email_note', $email_blurb, true );
		}
	}

	/**
	 * 5b. Order Creation: Add Order-level Pre-Order Meta & Warehouse Note
	 */
	public static function handle_order_created( $order ) {
		if ( ! is_a( $order, 'WC_Order' ) ) {
			return;
		}

		$preorder_item_names = array();

		foreach ( $order->get_items() as $item ) {
			if ( 'yes' === $item->get_meta( '_is_preorder', true ) ) {
				$preorder_item_names[] = $item->get_name();
			}
		}

		if ( ! empty( $preorder_item_names ) ) {
			$order->update_meta_data( '_donktoss_has_preorder', 'yes' );
			$order->update_meta_data( '_donktoss_preorder_items', $preorder_item_names );
			$order->update_meta_data( '_donktoss_preorder_placed_at', current_time( 'mysql' ) );

			// Add private internal note for warehouse and ShipStation
			$order->add_order_note(
				sprintf(
					/* translators: %s: Comma-separated product names */
					__( '[PRE-ORDER] Order contains pre-ordered item(s): %s. Hold fulfillment until stock arrives at warehouse.', 'donk-toss' ),
					implode( ', ', $preorder_item_names )
				),
				0 // 0 = strictly internal private note
			);

			$order->save();
		}
	}

	/**
	 * 6. Order Confirmation Details & Customer Emails
	 */
	public static function append_email_order_item_notice( $item_name, $item, $is_visible ) {
		if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) {
			return $item_name;
		}

		$is_preorder = ( 'yes' === $item->get_meta( '_is_preorder', true ) );
		$product     = $item->get_product();

		if ( ! $is_preorder && $product && self::is_product_preorder_enabled( $product ) ) {
			$is_preorder = true;
		}

		if ( $is_preorder ) {
			$email_note = $item->get_meta( '_preorder_email_note', true );
			if ( empty( $email_note ) && $product ) {
				$email_note = $product->get_meta( '_donktoss_preorder_email_blurb', true );
			}
			if ( empty( $email_note ) ) {
				$email_note = self::DEFAULT_EMAIL_BLURB;
			}

			$html  = '<div style="margin-top:6px; padding:6px 10px; background-color:#21262d; border-left:3px solid #ff6b00; border-radius:3px; font-size:12px; line-height:1.4; color:#e6edf3;">';
			$html .= '<strong style="color:#ffa726; font-size:11px; text-transform:uppercase; letter-spacing:0.5px;">' . esc_html__( 'Pre-Order', 'donk-toss' ) . ':</strong> ';
			$html .= esc_html( $email_note );
			$html .= '</div>';

			return $item_name . $html;
		}

		return $item_name;
	}

	/**
	 * 7. ShipStation Custom Field Export Integration
	 */
	public static function map_shipstation_custom_field_2( $meta_key ) {
		// Maps ShipStation CustomField2 to our preorder flag if not already set
		return ! empty( $meta_key ) ? $meta_key : '_donktoss_has_preorder';
	}

	public static function filter_shipstation_custom_field_2_value( $val, $order_id ) {
		$order = wc_get_order( $order_id );
		if ( $order && self::order_has_preorder( $order ) ) {
			return 'PRE-ORDER';
		}
		return $val;
	}

	/**
	 * 8. Admin HPOS & Post List Columns (Visual Indicators)
	 */
	public static function add_admin_order_column( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $title ) {
			$new_columns[ $key ] = $title;
			if ( 'order_status' === $key || 'status' === $key ) {
				$new_columns['donktoss_preorder'] = __( 'Pre-Order', 'donk-toss' );
			}
		}
		if ( ! isset( $new_columns['donktoss_preorder'] ) ) {
			$new_columns['donktoss_preorder'] = __( 'Pre-Order', 'donk-toss' );
		}
		return $new_columns;
	}

	public static function render_admin_order_column( $column, $order ) {
		if ( 'donktoss_preorder' !== $column || ! $order instanceof WC_Order ) {
			return;
		}

		if ( self::order_has_preorder( $order ) ) {
			echo '<span class="badge" style="display:inline-flex; align-items:center; gap:4px; padding:3px 8px; background:#ff6b00; color:#fff; border-radius:4px; font-size:11px; font-weight:700; line-height:1.2; letter-spacing:0.3px;">';
			echo '<span class="dashicons dashicons-clock" style="font-size:13px; width:13px; height:13px; line-height:13px;"></span> ';
			echo esc_html__( 'PRE-ORDER', 'donk-toss' );
			echo '</span>';
		} else {
			echo '<span style="color:#8c8f94; font-size:12px;">—</span>';
		}
	}

	public static function render_admin_order_column_legacy( $column, $post_id ) {
		if ( 'donktoss_preorder' !== $column ) {
			return;
		}
		$order = wc_get_order( $post_id );
		if ( $order ) {
			self::render_admin_order_column( $column, $order );
		}
	}

	/**
	 * 9. AutomateWoo Custom Rule & Variable Registration
	 */
	public static function register_automatewoo_rules( $includes ) {
		$includes['donktoss_order_has_active_preorder'] = 'DonkToss_Rule_Order_Has_Active_Preorder';
		return $includes;
	}

	public static function register_automatewoo_variables( $variables ) {
		if ( ! is_array( $variables ) ) {
			$variables = array();
		}
		if ( ! isset( $variables['order'] ) || ! is_array( $variables['order'] ) ) {
			$variables['order'] = array();
		}
		$variables['order']['preorder_status_update'] = 'DonkToss_Variable_Preorder_Status_Update';
		return $variables;
	}

	/**
	 * 9b. Render or retrieve latest pre-order status update for an order
	 *
	 * Used by AutomateWoo variable {{ order.preorder_status_update }} and
	 * shortcode [donktoss_preorder_update].
	 * Gracefully handles single or multiple pre-ordered items in the same order.
	 */
	public static function get_order_preorder_status_update_html( $order, $parameters = array() ) {
		if ( ! is_a( $order, 'WC_Order' ) ) {
			return '';
		}

		$product_updates = array();
		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			if ( $product && self::is_product_preorder_enabled( $product ) ) {
				$text = $product->get_meta( '_donktoss_preorder_latest_update', true );
				if ( ! empty( $text ) ) {
					$product_updates[ $product->get_name() ] = $text;
				}
			}
		}

		if ( empty( $product_updates ) ) {
			return '';
		}

		$format = isset( $parameters['format'] ) ? $parameters['format'] : 'box';

		if ( 1 === count( $product_updates ) ) {
			$content = wpautop( wp_kses_post( reset( $product_updates ) ) );
			if ( 'raw' === $format ) {
				return esc_html( reset( $product_updates ) );
			}
		} else {
			$content = '';
			foreach ( $product_updates as $prod_name => $prod_update ) {
				$content .= '<div style="margin-bottom:12px;">';
				$content .= '<strong style="color:#ffffff; font-size:13px; display:block; margin-bottom:2px;">' . esc_html( $prod_name ) . ':</strong>';
				$content .= '<div style="font-size:13px; line-height:1.5; color:#d0d7de;">' . wpautop( wp_kses_post( $prod_update ) ) . '</div>';
				$content .= '</div>';
			}
			if ( 'raw' === $format ) {
				return wp_strip_all_tags( $content );
			}
		}

		$html  = '<div class="donktoss-preorder-email-update-box" style="margin: 22px 0; padding: 16px 20px; background-color: #21262d; border-left: 4px solid #ff6b00; border-radius: 6px; color: #f0f6fc; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">';
		$html .= '<p style="margin: 0 0 8px 0; font-size: 11px; font-weight: 700; color: #ffa726; text-transform: uppercase; letter-spacing: 0.5px;">' . esc_html__( 'Latest Production &amp; Shipping Status', 'donk-toss' ) . ':</p>';
		$html .= '<div style="font-size: 14px; line-height: 1.55; color: #e6edf3;">' . $content . '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Shortcode fallback: [donktoss_preorder_update]
	 */
	public static function shortcode_preorder_update( $atts ) {
		global $order;
		$order_obj = null;
		if ( ! empty( $atts['order_id'] ) ) {
			$order_obj = wc_get_order( absint( $atts['order_id'] ) );
		} elseif ( is_a( $order, 'WC_Order' ) ) {
			$order_obj = $order;
		}
		return self::get_order_preorder_status_update_html( $order_obj, (array) $atts );
	}

	/**
	 * 9c. Handle Recurring 2-Week Drip Cycle Re-Queue
	 *
	 * When a recurring pre-order workflow finishes running, automatically schedules
	 * the next 2-week cycle as long as the order has active pre-order items.
	 */
	public static function handle_recurring_preorder_workflow( $workflow ) {
		if ( ! is_a( $workflow, '\AutomateWoo\Workflow' ) ) {
			return;
		}

		if ( 'yes' !== get_post_meta( $workflow->get_id(), '_donktoss_is_recurring_preorder', true ) ) {
			return;
		}

		$order = $workflow->data_layer()->get_order();
		if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
			return;
		}

		// Halt immediately if order is completed, cancelled, or all pre-orders arrived
		if ( 'processing' !== $order->get_status() || ! self::order_has_active_preorder_product( $order ) ) {
			return;
		}

		$next_id = get_post_meta( $workflow->get_id(), '_donktoss_recurring_next_workflow_id', true );
		if ( empty( $next_id ) ) {
			$next_id = $workflow->get_id();
		}

		$delay_days = (int) get_post_meta( $workflow->get_id(), '_donktoss_recurring_delay_days', true );
		if ( ! $delay_days ) {
			$delay_days = 14;
		}

		$next_run_timestamp = time() + ( $delay_days * DAY_IN_SECONDS );

		if ( function_exists( 'as_schedule_single_action' ) ) {
			// Prevent double queuing for the same order
			if ( function_exists( 'as_has_scheduled_action' ) && as_has_scheduled_action( 'donktoss_run_recurring_preorder_workflow', array( 'workflow_id' => (int) $next_id, 'order_id' => (int) $order->get_id() ), 'donktoss_preorder' ) ) {
				return;
			}

			as_schedule_single_action(
				$next_run_timestamp,
				'donktoss_run_recurring_preorder_workflow',
				array(
					'workflow_id' => (int) $next_id,
					'order_id'    => (int) $order->get_id(),
				),
				'donktoss_preorder'
			);
		}
	}

	/**
	 * 9d. Execute Recurring Workflow via Action Scheduler
	 */
	public static function execute_recurring_preorder_workflow( $workflow_id, $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order || 'processing' !== $order->get_status() || ! self::order_has_active_preorder_product( $order ) ) {
			return;
		}

		$workflow = null;
		if ( class_exists( '\AutomateWoo\Workflows\Factory' ) ) {
			$workflow = \AutomateWoo\Workflows\Factory::get( $workflow_id );
		} elseif ( class_exists( '\AutomateWoo\Workflow_Factory' ) ) {
			$workflow = \AutomateWoo\Workflow_Factory::get( $workflow_id );
		}

		if ( ! $workflow && class_exists( '\AutomateWoo\Workflow' ) ) {
			$post = get_post( $workflow_id );
			if ( $post ) {
				$workflow = new \AutomateWoo\Workflow( $post );
			}
		}

		if ( ! $workflow || ! $workflow->is_active() ) {
			return;
		}

		$workflow->setup( $workflow->post );
		$workflow->data_layer()->set_order( $order );

		$customer_id = $order->get_customer_id();
		if ( $customer_id && class_exists( '\AutomateWoo\Customer' ) ) {
			$workflow->data_layer()->set_customer( new \AutomateWoo\Customer( $customer_id ) );
		} elseif ( class_exists( '\AutomateWoo\Guest' ) ) {
			$workflow->data_layer()->set_guest( new \AutomateWoo\Guest( $order->get_billing_email() ) );
		}

		if ( $workflow->validate_rules() ) {
			$workflow->run();
		}
	}

	/**
	 * 9e. Cleanup Pending Recurring Actions when Order Status Changes (Completed, Cancelled, Refunded)
	 */
	public static function cleanup_recurring_on_order_status_change( $order_id, $status_from, $status_to, $order ) {
		if ( in_array( $status_to, array( 'completed', 'cancelled', 'refunded', 'failed' ), true ) ) {
			if ( function_exists( 'as_get_scheduled_actions' ) && class_exists( '\ActionScheduler_Store' ) && class_exists( '\ActionScheduler' ) ) {
				$actions = as_get_scheduled_actions(
					array(
						'hook'   => 'donktoss_run_recurring_preorder_workflow',
						'status' => \ActionScheduler_Store::STATUS_PENDING,
						'group'  => 'donktoss_preorder',
					)
				);
				foreach ( $actions as $action_id => $action ) {
					$args = $action->get_args();
					if ( isset( $args['order_id'] ) && (int) $args['order_id'] === (int) $order_id ) {
						\ActionScheduler::store()->cancel_action( $action_id );
					}
				}
			} elseif ( function_exists( 'as_unschedule_action' ) ) {
				as_unschedule_action( 'donktoss_run_recurring_preorder_workflow', array( 'order_id' => (int) $order_id ), 'donktoss_preorder' );
			}
		}
	}

	/**
	 * 10. Front-End Styles (Zero !important, high selector specificity)
	 */
	public static function enqueue_preorder_styles() {
		$custom_css = '
			/* Single Product Pre-Order Callout */
			body.single-product div.product .donktoss-preorder-single-callout {
				margin: 16px 0 20px;
				padding: 14px 18px;
				background: rgba(255, 107, 0, 0.08);
				border: 1px solid rgba(255, 107, 0, 0.35);
				border-left: 4px solid #ff6b00;
				border-radius: 6px;
				color: #e6edf3;
			}
			body.single-product div.product .donktoss-preorder-single-callout .donktoss-preorder-callout-header {
				display: flex;
				align-items: center;
				gap: 10px;
				margin-bottom: 8px;
			}
			body.single-product div.product .donktoss-preorder-single-callout .donktoss-preorder-badge-pill {
				display: inline-block;
				background: #ff6b00;
				color: #ffffff;
				font-size: 11px;
				font-weight: 700;
				letter-spacing: 0.6px;
				text-transform: uppercase;
				padding: 2px 8px;
				border-radius: 4px;
			}
			body.single-product div.product .donktoss-preorder-single-callout .donktoss-preorder-callout-title {
				font-size: 13px;
				font-weight: 600;
				color: #ffa726;
				text-transform: uppercase;
				letter-spacing: 0.5px;
			}
			body.single-product div.product .donktoss-preorder-single-callout .donktoss-preorder-callout-body {
				margin: 0;
				font-size: 14px;
				line-height: 1.5;
				color: #d0d7de;
			}

			/* Single Product Disclaimer Section */
			body.single-product .donktoss-preorder-page-disclaimer {
				margin: 35px 0 35px;
				padding: 18px 22px;
				background: #161b22;
				border: 1px solid #30363d;
				border-radius: 8px;
			}
			body.single-product .donktoss-preorder-page-disclaimer .donktoss-preorder-disclaimer-title {
				display: flex;
				align-items: center;
				gap: 8px;
				margin: 0 0 8px;
				font-size: 15px;
				font-weight: 600;
				color: #f0f6fc;
			}
			body.single-product .donktoss-preorder-page-disclaimer .donktoss-preorder-disclaimer-title svg {
				color: #ff6b00;
			}
			body.single-product .donktoss-preorder-page-disclaimer .donktoss-preorder-disclaimer-text {
				margin: 0;
				font-size: 13px;
				line-height: 1.55;
				color: #8b949e;
			}

			/* Cart & Checkout Line-Item Notice */
			.woocommerce-cart table.cart .donktoss-preorder-cart-blurb,
			.woocommerce-checkout #order_review .donktoss-preorder-cart-blurb {
				margin-top: 6px;
				font-size: 12px;
				line-height: 1.4;
				color: #8b949e;
			}
			.woocommerce-cart table.cart .donktoss-preorder-inline-pill,
			.woocommerce-checkout #order_review .donktoss-preorder-inline-pill {
				display: inline-block;
				background: #ff6b00;
				color: #ffffff;
				font-size: 10px;
				font-weight: 700;
				padding: 1px 6px;
				border-radius: 3px;
				letter-spacing: 0.4px;
				text-transform: uppercase;
				vertical-align: middle;
			}
			.woocommerce-cart table.cart .donktoss-preorder-inline-text,
			.woocommerce-checkout #order_review .donktoss-preorder-inline-text {
				color: #d0d7de;
			}

			/* Mixed Cart Notice Banner */
			body.woocommerce-page .donktoss-mixed-cart-notice {
				background-color: rgba(255, 107, 0, 0.12);
				border-left-color: #ff6b00;
				color: #f0f6fc;
			}
			body.woocommerce-page .donktoss-preorder-cart-banner {
				background-color: rgba(56, 139, 253, 0.12);
				border-left-color: #58a6ff;
				color: #f0f6fc;
			}
		';

		wp_add_inline_style( 'donk-toss-theme-css', $custom_css );
	}

	/**
	 * 11a. Suppress core out-of-stock admin email notification for pre-order products
	 *
	 * @param bool $should_send Whether to send notification.
	 * @param int  $product_id Out of stock product ID.
	 * @return bool
	 */
	public static function suppress_no_stock_notification( $should_send, $product_id ) {
		if ( self::is_product_preorder_enabled( $product_id ) ) {
			return false;
		}
		return $should_send;
	}

	/**
	 * 11b. Suppress core backorder admin email notification for pre-order products
	 *
	 * @param bool $should_send Whether to send notification.
	 * @param int  $product_id Backordered product ID.
	 * @return bool
	 */
	public static function suppress_backorder_notification( $should_send, $product_id ) {
		if ( self::is_product_preorder_enabled( $product_id ) ) {
			return false;
		}
		return $should_send;
	}

	/**
	 * 11c. Suppress core low-stock admin email notification for pre-order products
	 *
	 * @param bool $should_send Whether to send notification.
	 * @param int  $product_id Low stock product ID.
	 * @return bool
	 */
	public static function suppress_low_stock_notification( $should_send, $product_id ) {
		if ( self::is_product_preorder_enabled( $product_id ) ) {
			return false;
		}
		return $should_send;
	}

	/**
	 * 11d. Recipient filter fallback: Empty out recipient for no_stock & low_stock if pre-order active
	 *
	 * @param string          $recipient Recipient email address(es).
	 * @param WC_Product|null $product   Product object.
	 * @return string
	 */
	public static function suppress_stock_email_recipient( $recipient, $product ) {
		if ( $product && self::is_product_preorder_enabled( $product ) ) {
			return '';
		}
		return $recipient;
	}

	/**
	 * 11e. Recipient filter fallback: Empty out recipient for backorder if pre-order active
	 *
	 * @param string $recipient Recipient email address(es).
	 * @param array  $args      Backorder email arguments containing 'product'.
	 * @return string
	 */
	public static function suppress_backorder_email_recipient( $recipient, $args ) {
		if ( ! empty( $args['product'] ) && self::is_product_preorder_enabled( $args['product'] ) ) {
			return '';
		}
		return $recipient;
	}
}

/**
 * Define AutomateWoo Custom Rule Class if AutomateWoo is loaded
 */
function donktoss_define_automatewoo_preorder_rule() {
	if ( class_exists( '\AutomateWoo\Rules\Abstract_Bool' ) && ! class_exists( 'DonkToss_Rule_Order_Has_Active_Preorder' ) ) {
		class DonkToss_Rule_Order_Has_Active_Preorder extends \AutomateWoo\Rules\Abstract_Bool {

			/** @var string */
			public $data_item = 'order';

			/**
			 * Init the rule
			 */
			public function init() {
				$this->title = __( 'Order - Product Still in Pre-Order Mode', 'donk-toss' );
				$this->group = __( 'Order', 'automatewoo' );
			}

			/**
			 * Validate the rule against an order
			 *
			 * @param \WC_Order $order
			 * @param string    $compare
			 * @param string    $value
			 * @return bool
			 */
			public function validate( $order, $compare, $value ) {
				if ( ! is_a( $order, 'WC_Order' ) ) {
					return false;
				}

				// If order is completed or cancelled, it should no longer be treated as waiting
				$status = $order->get_status();
				if ( in_array( $status, array( 'completed', 'cancelled', 'refunded', 'failed' ), true ) ) {
					return ( 'no' === $value );
				}

				$has_active_preorder_product = false;

				foreach ( $order->get_items() as $item ) {
					$product = $item->get_product();
					if ( $product && DonkToss_Preorder::is_product_preorder_enabled( $product ) ) {
						$has_active_preorder_product = true;
						break;
					}
				}

				return ( $value === 'yes' ) ? $has_active_preorder_product : ! $has_active_preorder_product;
			}
		}
	}
}

add_action( 'automatewoo_init', 'donktoss_define_automatewoo_preorder_rule' );
add_action( 'init', 'donktoss_define_automatewoo_preorder_rule', 20 );
donktoss_define_automatewoo_preorder_rule();

/**
 * Define AutomateWoo Custom Variable Class if AutomateWoo is loaded
 */
function donktoss_define_automatewoo_preorder_variable() {
	if ( class_exists( '\AutomateWoo\Variable' ) && ! class_exists( 'DonkToss_Variable_Preorder_Status_Update' ) ) {
		class DonkToss_Variable_Preorder_Status_Update extends \AutomateWoo\Variable {

			/**
			 * Load admin details.
			 */
			public function load_admin_details() {
				$this->description = __( 'Displays the latest production or shipping status update entered in the Pre-Order tab of the pre-ordered product. Outputs a formatted status box if text is present, or blank if none.', 'donk-toss' );
			}

			/**
			 * Get variable value for an order.
			 *
			 * @param \WC_Order $order
			 * @param array     $parameters
			 * @return string
			 */
			public function get_value( $order, $parameters = array() ) {
				return DonkToss_Preorder::get_order_preorder_status_update_html( $order, (array) $parameters );
			}
		}
	}
}

add_action( 'automatewoo_init', 'donktoss_define_automatewoo_preorder_variable' );
add_action( 'init', 'donktoss_define_automatewoo_preorder_variable', 20 );
donktoss_define_automatewoo_preorder_variable();

DonkToss_Preorder::init();
