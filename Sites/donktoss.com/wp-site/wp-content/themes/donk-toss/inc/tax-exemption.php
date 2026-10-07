<?php
/**
 * Donk Toss Sales Tax Exemption & Coupon Waiver Module
 *
 * Provides:
 * - Admin checkbox on WooCommerce coupons to flag a coupon as "Waive Sales Tax / Tax Exempt".
 * - Dynamic cart & checkout tax waiving (via WC()->customer->set_is_vat_exempt) when an exempt coupon is applied.
 * - Support for combining tax exemption with any discount (e.g. 10% off + 0% tax, or 0% discount + 0% tax).
 * - Order meta recording and cart totals notification.
 *
 * @package DonkToss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DonkToss_Tax_Exemption {

	public static function init() {
		// Coupon Admin Fields
		add_action( 'woocommerce_coupon_options', array( __CLASS__, 'add_coupon_tax_exempt_field' ), 10, 2 );
		add_action( 'woocommerce_coupon_options_save', array( __CLASS__, 'save_coupon_tax_exempt_field' ), 10, 2 );

		// Cart & Checkout Tax Exemption Evaluation
		add_action( 'woocommerce_before_calculate_totals', array( __CLASS__, 'apply_tax_exemption_to_cart' ), 20, 1 );
		add_action( 'woocommerce_calculate_totals', array( __CLASS__, 'apply_tax_exemption_to_cart' ), 20, 1 );
		add_action( 'woocommerce_checkout_update_order_review', array( __CLASS__, 'apply_tax_exemption_on_checkout_ajax' ) );

		// Order Creation Meta
		add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'save_tax_exempt_order_meta' ), 20, 2 );

		// Display Indicator in Cart/Checkout Totals
		add_filter( 'woocommerce_cart_totals_coupon_label', array( __CLASS__, 'append_tax_exempt_coupon_label' ), 10, 2 );
	}

	/**
	 * Add "Waive Sales Tax" checkbox to Coupon edit screen in WP Admin
	 */
	public static function add_coupon_tax_exempt_field( $coupon_id, $coupon ) {
		echo '<div class="options_group donktoss_tax_exempt_options">';

		woocommerce_wp_checkbox( array(
			'id'          => '_waive_sales_tax',
			'label'       => __( 'Waive Sales Tax', 'donk-toss' ),
			'description' => __( 'Check this box to waive/exempt all sales tax for orders using this coupon code.', 'donk-toss' ),
			'value'       => get_post_meta( $coupon_id, '_waive_sales_tax', true ),
		) );

		echo '</div>';
	}

	/**
	 * Save the "Waive Sales Tax" coupon setting
	 */
	public static function save_coupon_tax_exempt_field( $post_id, $coupon ) {
		$waive_tax = isset( $_POST['_waive_sales_tax'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_waive_sales_tax', $waive_tax );
	}

	/**
	 * Check if any applied coupon in the cart waives sales tax
	 */
	public static function is_tax_exempt_coupon_applied() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return false;
		}

		$applied_coupons = WC()->cart->get_applied_coupons();
		if ( empty( $applied_coupons ) ) {
			return false;
		}

		foreach ( $applied_coupons as $code ) {
			$coupon = new WC_Coupon( $code );
			if ( $coupon->get_id() ) {
				$waive = get_post_meta( $coupon->get_id(), '_waive_sales_tax', true );
				if ( 'yes' === $waive ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Apply or remove tax exemption on the customer session during calculation
	 */
	public static function apply_tax_exemption_to_cart( $cart = null ) {
		if ( ! function_exists( 'WC' ) || ! WC()->customer ) {
			return;
		}

		$should_exempt = self::is_tax_exempt_coupon_applied();

		// Check if logged-in user has permanent tax-exempt status
		if ( ! $should_exempt && is_user_logged_in() ) {
			$user_id = get_current_user_id();
			if ( 'yes' === get_user_meta( $user_id, 'is_tax_exempt', true ) || current_user_can( 'tax_exempt' ) ) {
				$should_exempt = true;
			}
		}

		WC()->customer->set_is_vat_exempt( $should_exempt );
	}

	/**
	 * Re-verify on AJAX checkout update
	 */
	public static function apply_tax_exemption_on_checkout_ajax() {
		self::apply_tax_exemption_to_cart();
	}

	/**
	 * Record tax exemption status on the final order
	 */
	public static function save_tax_exempt_order_meta( $order, $data ) {
		if ( self::is_tax_exempt_coupon_applied() ) {
			$order->update_meta_data( '_is_tax_exempt_applied', 'yes' );
			$order->update_meta_data( '_tax_exempt_reason', 'Tax-Exempt Coupon Applied' );
		}
	}

	/**
	 * Append "(Tax-Exempt)" notation to coupon label in order summary if applicable
	 */
	public static function append_tax_exempt_coupon_label( $label, $coupon ) {
		if ( is_a( $coupon, 'WC_Coupon' ) && 'yes' === get_post_meta( $coupon->get_id(), '_waive_sales_tax', true ) ) {
			$label .= ' <span class="tax-exempt-badge" style="color:#fd6d25; font-size:0.85em; font-weight:600;">(' . __( 'Tax Exempt', 'donk-toss' ) . ')</span>';
		}
		return $label;
	}
}

DonkToss_Tax_Exemption::init();
