/**
 * Donk Toss — Client-Side Checkout Duplicate Lock & bfcache Protection
 *
 * 1. Detects back-forward cache (bfcache) restoration on mobile/desktop browsers after checkout.
 * 2. Debounces rapid multi-tap / double-click submissions on "Place Order".
 * 3. Re-enables submit buttons if WooCommerce returns validation errors.
 *
 * @package Donk Toss
 * @since 4.9.3
 */

(function($) {
	'use strict';

	var ORDER_EXPIRY_MS = 15 * 60 * 1000; // 15 minutes lookback in session storage

	/**
	 * Check if the current page is the order received / thank-you confirmation page
	 */
	function isOrderReceivedPage() {
		return document.body.classList.contains('woocommerce-order-received') ||
			window.location.pathname.indexOf('/order-received/') !== -1 ||
			window.location.search.indexOf('order-received') !== -1;
	}

	/**
	 * Check if current page is the active checkout page (not the thank you page)
	 */
	function isCheckoutFormPage() {
		return document.body.classList.contains('woocommerce-checkout') && !isOrderReceivedPage();
	}

	/**
	 * On Order Received page: Record completion timestamp in sessionStorage
	 */
	if (isOrderReceivedPage()) {
		try {
			sessionStorage.setItem('donktoss_last_order_placed_at', Date.now().toString());
		} catch (e) {
			// Storage quota / private browsing fallback
		}
	}

	/**
	 * Handle browser Back/Forward Cache (bfcache) restoration
	 */
	window.addEventListener('pageshow', function(event) {
		var isPersisted = event.persisted;
		
		// Fallback check for performance navigation back_forward type
		if (!isPersisted && window.performance && window.performance.getEntriesByType) {
			var navEntries = window.performance.getEntriesByType('navigation');
			if (navEntries && navEntries.length > 0 && navEntries[0].type === 'back_forward') {
				isPersisted = true;
			}
		}

		if (isPersisted && isCheckoutFormPage()) {
			var lastOrderTime = 0;
			try {
				lastOrderTime = parseInt(sessionStorage.getItem('donktoss_last_order_placed_at') || '0', 10);
			} catch (e) {
				lastOrderTime = 0;
			}

			// If an order was placed within the last 15 minutes, force reload to flush cached cart DOM
			if (lastOrderTime > 0 && (Date.now() - lastOrderTime) < ORDER_EXPIRY_MS) {
				window.location.reload();
			}
		}
	});

	/**
	 * Debounce and lock checkout form upon submission
	 */
	$(document).ready(function() {
		var $checkoutForm = $('form.checkout');
		if (!$checkoutForm.length) {
			return;
		}

		var isSubmitting = false;

		$checkoutForm.on('checkout_place_order', function() {
			if (isSubmitting) {
				return false;
			}
			isSubmitting = true;

			var $submitBtn = $('#place_order');
			$submitBtn.addClass('donktoss-submitting-locked').prop('disabled', true);

			// Safety timeout: Release lock after 20 seconds if no network response
			setTimeout(function() {
				isSubmitting = false;
				$submitBtn.removeClass('donktoss-submitting-locked').prop('disabled', false);
			}, 20000);

			return true;
		});

		// If WooCommerce encounters errors and halts checkout, release button lock
		$(document.body).on('checkout_error', function() {
			isSubmitting = false;
			var $submitBtn = $('#place_order');
			$submitBtn.removeClass('donktoss-submitting-locked').prop('disabled', false);
		});

		// Also handle AJAX complete to restore button state if needed
		$(document).ajaxComplete(function(event, xhr, settings) {
			if (settings && settings.url && settings.url.indexOf('wc-ajax=checkout') !== -1) {
				try {
					var res = JSON.parse(xhr.responseText);
					if (res && res.result === 'failure') {
						isSubmitting = false;
						$('#place_order').removeClass('donktoss-submitting-locked').prop('disabled', false);
					}
				} catch (e) {
					isSubmitting = false;
					$('#place_order').removeClass('donktoss-submitting-locked').prop('disabled', false);
				}
			}
		});
	});

})(jQuery);
