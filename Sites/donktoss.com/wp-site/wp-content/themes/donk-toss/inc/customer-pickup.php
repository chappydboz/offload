<?php
/**
 * Donk Toss Customer Pickup & ShipStation Exclusion Module
 *
 * Provides:
 * - Admin checkbox on WooCommerce coupons to flag a coupon as "Customer Pickup".
 * - Recognition of standard pickup coupons (LOCALPICKUP, PICKUP, PBHOMIEPICKUP, ONSITENOSHIPPINGDONKBUDS).
 * - Multi-coupon combinability: Allows customers to combine a pickup coupon with promotional discount codes.
 * - Dynamic shipping rate injection: Replaces flat rate shipping ($50) with "Local Pickup (Austin, TX)" ($0.00).
 * - Automatic ShipStation export exclusion: Prevents customer pickup orders from ever importing into ShipStation.
 * - Admin HPOS order list badges and order actions to inspect or toggle ShipStation exclusion.
 *
 * @package DonkToss
 * @since 4.9.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DonkToss_Customer_Pickup {

	/**
	 * Known pickup coupon codes (case-insensitive)
	 *
	 * @var array<string>
	 */
	const KNOWN_PICKUP_CODES = array(
		'pickup',
		'localpickup',
		'austinpickup',
		'pbhomiepickup',
		'onsitenoshippingdonkbuds',
		'donkactivation',
	);

	/**
	 * Initialize hooks
	 */
	public static function init() {
		// 1. Coupon Admin Settings
		add_action( 'woocommerce_coupon_options', array( __CLASS__, 'add_coupon_pickup_field' ), 10, 2 );
		add_action( 'woocommerce_coupon_options_save', array( __CLASS__, 'save_coupon_pickup_field' ), 10, 2 );

		// 2. Dynamic Shipping Rate Modification (Cart & Checkout)
		add_filter( 'woocommerce_package_rates', array( __CLASS__, 'filter_shipping_rates_for_pickup' ), 20, 2 );

		// 3. Clear shipping session cache on coupon apply/remove
		add_action( 'woocommerce_applied_coupon', array( __CLASS__, 'clear_shipping_cache_on_coupon_change' ) );
		add_action( 'woocommerce_removed_coupon', array( __CLASS__, 'clear_shipping_cache_on_coupon_change' ) );

		// 4. Order Creation Meta & Note
		add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'save_order_pickup_meta' ), 20, 2 );

		// 5. ShipStation Export Prevention Hook (Official WC ShipStation Integration Filter)
		add_filter( 'woocommerce_shipstation_export_order', array( __CLASS__, 'exclude_pickup_from_shipstation_export' ), 10, 2 );

		// 6. Cart & Checkout Coupon Pill Label
		add_filter( 'woocommerce_cart_totals_coupon_label', array( __CLASS__, 'append_pickup_coupon_label' ), 25, 2 );

		// 7. Admin HPOS & Post List Columns (Visual Indicators)
		add_filter( 'manage_woocommerce_page_wc-orders_columns', array( __CLASS__, 'add_admin_order_column' ), 20 );
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( __CLASS__, 'render_admin_order_column' ), 20, 2 );
		add_filter( 'manage_edit-shop_order_columns', array( __CLASS__, 'add_admin_order_column' ), 20 );
		add_action( 'manage_shop_order_posts_custom_column', array( __CLASS__, 'render_admin_order_column_legacy' ), 20, 2 );

		// 8. Admin Order Actions (Manual Toggle for Support/Operations)
		add_filter( 'woocommerce_order_actions', array( __CLASS__, 'add_order_actions' ) );
		add_action( 'woocommerce_order_action_donktoss_exclude_shipstation', array( __CLASS__, 'handle_order_action_exclude_shipstation' ) );
		add_action( 'woocommerce_order_action_donktoss_include_shipstation', array( __CLASS__, 'handle_order_action_include_shipstation' ) );
	}

	/**
	 * Add "Customer Pickup" checkbox to Coupon edit screen in WP Admin
	 *
	 * @param int       $coupon_id Coupon post ID.
	 * @param WC_Coupon $coupon    Coupon object.
	 */
	public static function add_coupon_pickup_field( $coupon_id, $coupon ) {
		echo '<div class="options_group donktoss_customer_pickup_options">';

		woocommerce_wp_checkbox( array(
			'id'          => '_is_customer_pickup',
			'label'       => __( 'Customer Pickup', 'donk-toss' ),
			'description' => __( 'Check this box to enable free local pickup (Austin, TX) and automatically exclude orders using this coupon from ShipStation export.', 'donk-toss' ),
			'value'       => get_post_meta( $coupon_id, '_is_customer_pickup', true ),
		) );

		echo '</div>';
	}

	/**
	 * Save the "Customer Pickup" coupon setting
	 *
	 * @param int       $post_id Coupon post ID.
	 * @param WC_Coupon $coupon  Coupon object.
	 */
	public static function save_coupon_pickup_field( $post_id, $coupon ) {
		$is_pickup = isset( $_POST['_is_customer_pickup'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_is_customer_pickup', $is_pickup );
	}

	/**
	 * Check if a coupon code or WC_Coupon object represents a customer pickup coupon
	 *
	 * @param string|WC_Coupon $coupon Coupon code or object.
	 * @return bool
	 */
	public static function is_pickup_coupon( $coupon ) {
		$code = '';
		$coupon_id = 0;

		if ( is_string( $coupon ) ) {
			$code = strtolower( trim( $coupon ) );
			$c = new WC_Coupon( $code );
			$coupon_id = $c->get_id();
		} elseif ( is_a( $coupon, 'WC_Coupon' ) ) {
			$code = strtolower( trim( $coupon->get_code() ) );
			$coupon_id = $coupon->get_id();
		}

		if ( empty( $code ) && empty( $coupon_id ) ) {
			return false;
		}

		// 1. Check known codes
		if ( in_array( $code, self::KNOWN_PICKUP_CODES, true ) ) {
			return true;
		}

		// 2. Check meta flag
		if ( $coupon_id > 0 && 'yes' === get_post_meta( $coupon_id, '_is_customer_pickup', true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if any customer pickup coupon is currently applied in the WooCommerce cart
	 *
	 * @return bool
	 */
	public static function is_pickup_coupon_applied() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return false;
		}

		$applied = WC()->cart->get_applied_coupons();
		if ( empty( $applied ) || ! is_array( $applied ) ) {
			return false;
		}

		foreach ( $applied as $code ) {
			if ( self::is_pickup_coupon( $code ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Dynamically inject or restrict shipping rates when a pickup coupon is active
	 *
	 * @param array $rates   Array of WC_Shipping_Rate objects.
	 * @param array $package Shipping package.
	 * @return array Modified rates.
	 */
	public static function filter_shipping_rates_for_pickup( $rates, $package ) {
		if ( ! self::is_pickup_coupon_applied() ) {
			return $rates;
		}

		// Define the dedicated Local Pickup rate
		$pickup_rate_id = 'local_pickup:customer_pickup';
		$pickup_rate = new WC_Shipping_Rate(
			$pickup_rate_id,
			__( 'Local Pickup (Austin, TX)', 'donk-toss' ),
			0,
			array(),
			'local_pickup'
		);

		// Return Local Pickup as the available shipping method ($0.00)
		return array( $pickup_rate_id => $pickup_rate );
	}

	/**
	 * Invalidate session shipping package cache when a coupon is applied or removed
	 *
	 * @param string|null $coupon_code Applied/removed coupon code.
	 */
	public static function clear_shipping_cache_on_coupon_change( $coupon_code = null ) {
		if ( ! function_exists( 'WC' ) || ! WC()->cart || ! WC()->session ) {
			return;
		}

		$packages = WC()->cart->get_shipping_packages();
		if ( is_array( $packages ) ) {
			foreach ( array_keys( $packages ) as $i ) {
				WC()->session->__unset( 'shipping_for_package_' . $i );
			}
		}

		WC()->cart->calculate_shipping();
	}

	/**
	 * Save pickup and ShipStation exclusion metadata during order creation
	 *
	 * @param WC_Order $order Order object being created.
	 * @param array    $data  Posted checkout data.
	 */
	public static function save_order_pickup_meta( $order, $data ) {
		$is_pickup = false;
		$pickup_reason = '';

		// 1. Check if pickup coupon is applied
		if ( self::is_pickup_coupon_applied() ) {
			$is_pickup = true;
			$pickup_reason = 'Customer Pickup Coupon Applied';
		}

		// 2. Check chosen shipping method
		$shipping_methods = WC()->session ? WC()->session->get( 'chosen_shipping_methods' ) : array();
		if ( ! empty( $shipping_methods ) && is_array( $shipping_methods ) ) {
			foreach ( $shipping_methods as $method ) {
				if ( false !== strpos( $method, 'local_pickup' ) ) {
					$is_pickup = true;
					$pickup_reason = empty( $pickup_reason ) ? 'Local Pickup Shipping Method' : $pickup_reason;
					break;
				}
			}
		}

		if ( $is_pickup ) {
			$order->update_meta_data( '_is_customer_pickup', 'yes' );
			$order->update_meta_data( '_shipstation_export_excluded', 'yes' );
			$order->update_meta_data( '_shipstation_exclusion_reason', $pickup_reason );

			// Add private internal note on the order
			$order->add_order_note(
				sprintf(
					/* translators: %s: Reason for exclusion */
					__( '[Customer Pickup] Order marked for local pickup in Austin, TX (%s). Automatically excluded from ShipStation export.', 'donk-toss' ),
					$pickup_reason
				),
				0 // 0 = internal note, strictly no customer email
			);
		}
	}

	/**
	 * Check if an order is excluded from ShipStation export
	 *
	 * Hooked to 'woocommerce_shipstation_export_order' filter from WooCommerce ShipStation Integration.
	 *
	 * @param bool $export   Default export flag (true).
	 * @param int  $order_id Order ID.
	 * @return bool False to exclude order from ShipStation export; true to allow.
	 */
	public static function exclude_pickup_from_shipstation_export( $export, $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return $export;
		}

		// 1. Check order meta flags
		if ( 'yes' === $order->get_meta( '_shipstation_export_excluded' ) || 'yes' === $order->get_meta( '_is_customer_pickup' ) ) {
			return false;
		}

		// 2. Check coupons applied to this order
		$coupon_codes = $order->get_coupon_codes();
		if ( ! empty( $coupon_codes ) && is_array( $coupon_codes ) ) {
			foreach ( $coupon_codes as $code ) {
				if ( self::is_pickup_coupon( $code ) ) {
					return false;
				}
			}
		}

		// 3. Check shipping line items on the order
		$shipping_items = $order->get_shipping_methods();
		if ( ! empty( $shipping_items ) && is_array( $shipping_items ) ) {
			foreach ( $shipping_items as $item ) {
				$method_id    = $item->get_method_id();
				$method_title = strtolower( $item->get_method_title() );

				if ( 'local_pickup' === $method_id || false !== strpos( $method_title, 'pickup' ) || false !== strpos( $method_title, 'pick up' ) ) {
					return false;
				}
			}
		}

		return $export;
	}

	/**
	 * Append "(Local Pickup)" badge to coupon label in Cart/Checkout totals
	 *
	 * @param string    $label  Coupon label markup.
	 * @param WC_Coupon $coupon Coupon object.
	 * @return string Modified label.
	 */
	public static function append_pickup_coupon_label( $label, $coupon ) {
		if ( self::is_pickup_coupon( $coupon ) ) {
			$label .= ' <span class="pickup-coupon-badge" style="color:#28a745; font-size:0.85em; font-weight:600;">(' . __( 'Local Pickup', 'donk-toss' ) . ')</span>';
		}
		return $label;
	}

	/**
	 * Add "Fulfillment" column header to WooCommerce Orders list
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public static function add_admin_order_column( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $title ) {
			$new_columns[ $key ] = $title;
			if ( 'order_status' === $key || 'status' === $key ) {
				$new_columns['donktoss_fulfillment'] = __( 'Fulfillment / ShipStation', 'donk-toss' );
			}
		}
		if ( ! isset( $new_columns['donktoss_fulfillment'] ) ) {
			$new_columns['donktoss_fulfillment'] = __( 'Fulfillment / ShipStation', 'donk-toss' );
		}
		return $new_columns;
	}

	/**
	 * Render "Fulfillment" column content for HPOS order list
	 *
	 * @param string   $column Column key.
	 * @param WC_Order $order  Order object.
	 */
	public static function render_admin_order_column( $column, $order ) {
		if ( 'donktoss_fulfillment' !== $column || ! $order instanceof WC_Order ) {
			return;
		}

		$is_excluded = 'yes' === $order->get_meta( '_shipstation_export_excluded' ) || 'yes' === $order->get_meta( '_is_customer_pickup' );

		if ( ! $is_excluded ) {
			// Check shipping items
			foreach ( $order->get_shipping_methods() as $item ) {
				if ( 'local_pickup' === $item->get_method_id() || false !== strpos( strtolower( $item->get_method_title() ), 'pickup' ) ) {
					$is_excluded = true;
					break;
				}
			}
		}

		if ( $is_excluded ) {
			echo '<span class="badge" style="display:inline-block; padding:3px 7px; background:#28a745; color:#fff; border-radius:4px; font-size:11px; font-weight:600; line-height:1.2;">' . esc_html__( 'Local Pickup', 'donk-toss' ) . '</span> ';
			echo '<span class="badge" style="display:inline-block; padding:3px 7px; background:#6c757d; color:#fff; border-radius:4px; font-size:11px; font-weight:500; line-height:1.2;">' . esc_html__( 'No ShipStation', 'donk-toss' ) . '</span>';
		} else {
			echo '<span class="badge" style="display:inline-block; padding:3px 7px; background:#0073aa; color:#fff; border-radius:4px; font-size:11px; line-height:1.2;">' . esc_html__( 'ShipStation Active', 'donk-toss' ) . '</span>';
		}
	}

	/**
	 * Render "Fulfillment" column content for legacy post-based order list
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 */
	public static function render_admin_order_column_legacy( $column, $post_id ) {
		if ( 'donktoss_fulfillment' !== $column ) {
			return;
		}
		$order = wc_get_order( $post_id );
		if ( $order ) {
			self::render_admin_order_column( $column, $order );
		}
	}

	/**
	 * Add admin order actions to manually toggle ShipStation exclusion
	 *
	 * @param array $actions Existing actions.
	 * @return array
	 */
	public static function add_order_actions( $actions ) {
		$actions['donktoss_exclude_shipstation'] = __( 'Exclude from ShipStation (Local Pickup)', 'donk-toss' );
		$actions['donktoss_include_shipstation'] = __( 'Include in ShipStation (Clear Exclusion)', 'donk-toss' );
		return $actions;
	}

	/**
	 * Handle manual order action: Exclude from ShipStation
	 *
	 * @param WC_Order $order Order object.
	 */
	public static function handle_order_action_exclude_shipstation( $order ) {
		$order->update_meta_data( '_is_customer_pickup', 'yes' );
		$order->update_meta_data( '_shipstation_export_excluded', 'yes' );
		$order->update_meta_data( '_shipstation_exclusion_reason', 'Manually excluded via Order Action' );
		$order->add_order_note( __( '[Admin Action] Order manually flagged as Local Pickup and excluded from ShipStation export.', 'donk-toss' ), 0 );
		$order->save();
	}

	/**
	 * Handle manual order action: Include in ShipStation
	 *
	 * @param WC_Order $order Order object.
	 */
	public static function handle_order_action_include_shipstation( $order ) {
		$order->delete_meta_data( '_is_customer_pickup' );
		$order->delete_meta_data( '_shipstation_export_excluded' );
		$order->delete_meta_data( '_shipstation_exclusion_reason' );
		$order->add_order_note( __( '[Admin Action] Local pickup flag cleared. Order is now eligible for ShipStation export.', 'donk-toss' ), 0 );
		$order->save();
	}
}

DonkToss_Customer_Pickup::init();
