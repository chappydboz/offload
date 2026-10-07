<?php
/**
 * Google Customer Reviews Integration
 *
 * Implements Google Customer Reviews Survey Opt-in on the WooCommerce Thank You / Order Confirmation page
 * and provides a badge shortcode [google_customer_reviews_badge].
 *
 * Merchant Center Account ID: 5847982952
 *
 * @package DonkToss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DonkToss_Google_Customer_Reviews {

	const MERCHANT_ID = 5847982952;

	public static function init() {
		// Opt-in Survey on WooCommerce Order Received page
		add_action( 'woocommerce_thankyou', array( __CLASS__, 'render_survey_optin' ), 30 );

		// Shortcode for optional site badge
		add_shortcode( 'google_customer_reviews_badge', array( __CLASS__, 'render_badge' ) );
	}

	/**
	 * Render Google Customer Reviews Survey Opt-in on Thank You Page
	 */
	public static function render_survey_optin( $order_id ) {
		if ( ! $order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$email            = $order->get_billing_email();
		$country          = $order->get_shipping_country() ? $order->get_shipping_country() : $order->get_billing_country();
		$delivery_country = $country ? $country : 'US';

		// Estimate delivery date: 5 business days from order creation
		$order_date = $order->get_date_created() ? $order->get_date_created()->getTimestamp() : time();
		$estimated_delivery_timestamp = strtotime( '+5 business days', $order_date );
		$estimated_delivery_date = date( 'Y-m-d', $estimated_delivery_timestamp );

		// Collect GTINs / SKUs
		$products = array();
		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			if ( $product ) {
				$sku = $product->get_sku();
				if ( $sku ) {
					$products[] = array( 'gtin' => $sku );
				}
			}
		}

		?>
		<!-- Google Customer Reviews Opt-in -->
		<script src="https://apis.google.com/js/platform.js?onload=renderOptIn" async defer></script>
		<script>
			window.renderOptIn = function() {
				window.gapi.load('surveyoptin', function() {
					window.gapi.surveyoptin.render(
						{
							"merchant_id": <?php echo esc_js( self::MERCHANT_ID ); ?>,
							"order_id": "<?php echo esc_js( (string) $order->get_order_number() ); ?>",
							"email": "<?php echo esc_js( $email ); ?>",
							"delivery_country": "<?php echo esc_js( $delivery_country ); ?>",
							"estimated_delivery_date": "<?php echo esc_js( $estimated_delivery_date ); ?>"
							<?php if ( ! empty( $products ) ) : ?>,
							"products": <?php echo wp_json_encode( $products ); ?>
							<?php endif; ?>
						}
					);
				});
			};
		</script>
		<!-- END Google Customer Reviews Opt-in -->
		<?php
	}

	/**
	 * Render Google Customer Reviews Badge
	 */
	public static function render_badge() {
		ob_start();
		?>
		<script src="https://apis.google.com/js/platform.js" async defer></script>
		<g:ratingbadge merchant_id="<?php echo esc_attr( self::MERCHANT_ID ); ?>"></g:ratingbadge>
		<?php
		return ob_get_clean();
	}
}

DonkToss_Google_Customer_Reviews::init();
