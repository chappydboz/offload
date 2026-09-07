<?php
/**
 * Donk Toss — Duplicate Order Prevention & Checkout Idempotency Guard
 *
 * Prevents accidental double orders caused by Stripe Link redirects,
 * mobile browser back-button navigation (bfcache), and rapid double-clicks.
 *
 * @package Donk Toss
 * @since 4.9.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generate a normalized cart items signature (e.g., "123:1,456:2")
 *
 * @param WC_Cart|null $cart
 * @return string
 */
function donktoss_get_cart_signature( $cart = null ) {
	if ( ! $cart ) {
		$cart = function_exists( 'WC' ) && WC()->cart ? WC()->cart : null;
	}

	if ( ! $cart || $cart->is_empty() ) {
		return '';
	}

	$items = array();
	foreach ( $cart->get_cart() as $cart_item ) {
		$item_id = ! empty( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : $cart_item['product_id'];
		$qty     = ! empty( $cart_item['quantity'] ) ? (int) $cart_item['quantity'] : 1;
		$items[] = $item_id . ':' . $qty;
	}

	sort( $items );
	return implode( ',', $items );
}

/**
 * Find an existing identical order placed within the specified time window
 *
 * @param string $billing_email Customer billing email
 * @param string $cart_signature Normalized items signature
 * @param string $cart_hash WooCommerce cart hash
 * @param float  $total Total order dollar amount
 * @param int    $window_seconds Lookback window in seconds (default: 600 = 10 mins)
 * @return WC_Order|null
 */
function donktoss_find_recent_duplicate_order( $billing_email, $cart_signature, $cart_hash, $total, $window_seconds = 600 ) {
	$clean_email = sanitize_email( strtolower( trim( $billing_email ) ) );
	if ( empty( $clean_email ) ) {
		return null;
	}

	$cutoff_timestamp = time() - $window_seconds;

	$query_args = array(
		'type'          => 'shop_order',
		'billing_email' => $clean_email,
		'date_created'  => '>=' . $cutoff_timestamp,
		'status'        => array( 'processing', 'completed', 'on-hold', 'pending' ),
		'limit'         => 10,
		'orderby'       => 'date',
		'order'         => 'DESC',
		'return'        => 'objects',
	);

	$recent_orders = function_exists( 'wc_get_orders' ) ? wc_get_orders( $query_args ) : array();

	if ( empty( $recent_orders ) ) {
		return null;
	}

	foreach ( $recent_orders as $order ) {
		if ( ! is_a( $order, 'WC_Order' ) || is_a( $order, 'WC_Order_Refund' ) || ( class_exists( 'Automattic\WooCommerce\Admin\Overrides\OrderRefund' ) && is_a( $order, 'Automattic\WooCommerce\Admin\Overrides\OrderRefund' ) ) ) {
			continue;
		}

		// If status is 'pending', only treat as duplicate if created in the last 120 seconds (active gateway processing)
		$order_timestamp = $order->get_date_created() ? $order->get_date_created()->getTimestamp() : 0;
		if ( 'pending' === $order->get_status() && ( time() - $order_timestamp ) > 120 ) {
			continue;
		}

		// Verify dollar total match (within 1 cent float tolerance)
		$order_total = (float) $order->get_total();
		if ( abs( $order_total - (float) $total ) > 0.01 ) {
			continue;
		}

		// Check 1: Match by WooCommerce cart hash
		$order_cart_hash = $order->get_cart_hash();
		if ( ! empty( $cart_hash ) && ! empty( $order_cart_hash ) && $order_cart_hash === $cart_hash ) {
			return $order;
		}

		// Check 2: Match by exact product/variation IDs and quantities
		$order_items = array();
		foreach ( $order->get_items() as $item ) {
			$item_id       = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
			$qty           = (int) $item->get_quantity();
			$order_items[] = $item_id . ':' . $qty;
		}
		sort( $order_items );
		$order_signature = implode( ',', $order_items );

		if ( ! empty( $cart_signature ) && ! empty( $order_signature ) && $order_signature === $cart_signature ) {
			return $order;
		}
	}

	return null;
}

/**
 * Concurrency Mutex Lock: Prevent sub-second double clicks / multi-tap race conditions
 */
function donktoss_checkout_concurrency_guard() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
		return;
	}

	$email = ! empty( $_POST['billing_email'] ) ? sanitize_email( $_POST['billing_email'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( empty( $email ) && is_user_logged_in() ) {
		$current_user = wp_get_current_user();
		$email        = $current_user->user_email;
	}

	if ( empty( $email ) ) {
		return;
	}

	$total          = (float) WC()->cart->get_total( 'edit' );
	$cart_hash      = WC()->cart->get_cart_hash();
	$cart_signature = donktoss_get_cart_signature( WC()->cart );
	$lock_key       = 'donk_chk_lock_' . md5( strtolower( trim( $email ) ) . '_' . $cart_hash );

	// If an identical order was already placed, let woocommerce_after_checkout_validation display the rich receipt link
	$existing_duplicate = donktoss_find_recent_duplicate_order( $email, $cart_signature, $cart_hash, $total, 600 );
	if ( $existing_duplicate ) {
		delete_transient( $lock_key );
		return;
	}

	if ( get_transient( $lock_key ) ) {
		wc_add_notice(
			__( 'Your order is currently being processed. Please wait a few seconds for confirmation.', 'donk-toss' ),
			'error'
		);
		return;
	}

	// Set a 30-second transient lock for in-flight request
	set_transient( $lock_key, time(), 30 );
}
add_action( 'woocommerce_checkout_process', 'donktoss_checkout_concurrency_guard', 5 );

/**
 * Server-Side Validation: Intercept accidental duplicate checkouts before order creation
 *
 * @param array    $data Posted checkout form data
 * @param WP_Error $errors Validation error collector
 */
function donktoss_prevent_duplicate_checkout( $data, $errors ) {
	if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
		return;
	}

	$billing_email = ! empty( $data['billing_email'] ) ? sanitize_email( $data['billing_email'] ) : '';
	if ( empty( $billing_email ) && is_user_logged_in() ) {
		$current_user  = wp_get_current_user();
		$billing_email = $current_user->user_email;
	}

	if ( empty( $billing_email ) ) {
		return;
	}

	$total          = (float) WC()->cart->get_total( 'edit' );
	$cart_hash      = WC()->cart->get_cart_hash();
	$cart_signature = donktoss_get_cart_signature( WC()->cart );

	// Search for matching duplicate order placed in last 10 minutes (600s)
	$duplicate_order = donktoss_find_recent_duplicate_order( $billing_email, $cart_signature, $cart_hash, $total, 600 );

	if ( $duplicate_order ) {
		$order_id    = $duplicate_order->get_id();
		$order_num   = $duplicate_order->get_order_number();
		$receipt_url = $duplicate_order->get_checkout_order_received_url();
		$date_obj    = $duplicate_order->get_date_created();
		$time_diff   = $date_obj ? human_time_diff( $date_obj->getTimestamp() ) : 'a few moments';

		// Log intercepted duplicate for telemetry and audit trail
		if ( function_exists( 'wc_get_logger' ) ) {
			$logger = wc_get_logger();
			$logger->warning(
				sprintf(
					'[DONK-DUPLICATE-PREVENTED] Intercepted duplicate order attempt. Customer: %s, Previous Order: #%s (placed %s ago), Total: $%s, Cart Hash: %s, Signature: %s',
					$billing_email,
					$order_num,
					$time_diff,
					number_format( $total, 2 ),
					$cart_hash,
					$cart_signature
				),
				array( 'source' => 'donktoss-duplicate-prevention' )
			);
		}

		// Clear the transient lock so user can interact if needed
		$lock_key = 'donk_chk_lock_' . md5( strtolower( trim( $billing_email ) ) . '_' . $cart_hash );
		delete_transient( $lock_key );

		$notice_html = sprintf(
			/* translators: 1: Order number, 2: Elapsed time diff, 3: Order receipt URL */
			__( '<strong>Order Already Placed:</strong> It looks like you already placed this identical order (<strong>#%1$s</strong>) %2$s ago.<br><br>To protect you from accidental double charges, this duplicate order was stopped.<br><br><a href="%3$s" class="button donktoss-view-receipt-btn" style="display:inline-block; margin-top:6px;">View Your Order Receipt &rarr;</a>', 'donk-toss' ),
			esc_html( $order_num ),
			esc_html( $time_diff ),
			esc_url( $receipt_url )
		);

		$errors->add( 'donktoss_duplicate_order_prevented', $notice_html );
	}
}
add_action( 'woocommerce_after_checkout_validation', 'donktoss_prevent_duplicate_checkout', 10, 2 );

/**
 * Release mutex lock upon successful order completion
 *
 * @param int $order_id
 */
function donktoss_clear_checkout_lock( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}

	$email     = $order->get_billing_email();
	$cart_hash = $order->get_cart_hash();

	if ( $email && $cart_hash ) {
		$lock_key = 'donk_chk_lock_' . md5( strtolower( trim( $email ) ) . '_' . $cart_hash );
		delete_transient( $lock_key );
	}
}
add_action( 'woocommerce_checkout_order_processed', 'donktoss_clear_checkout_lock', 10, 1 );
