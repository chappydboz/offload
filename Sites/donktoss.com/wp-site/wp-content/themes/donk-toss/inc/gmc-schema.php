<?php
/**
 * Google Merchant Center Structured Data (JSON-LD) Enrichment
 *
 * Enriches WooCommerce Product JSON-LD with:
 * - Brand ("DONK")
 * - Item Condition (NewCondition)
 * - MPN (matching SKU)
 * - Merchant Return Policy (30-day window, defective/damage replacement, US coverage)
 * - Offer Shipping Details (Domestic US, 1-3 day handling, 3-5 day transit)
 *
 * @package DonkToss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DonkToss_GMC_Schema {

	public static function init() {
		// Hook into WooCommerce structured data
		add_filter( 'woocommerce_structured_data_product', array( __CLASS__, 'enrich_product_schema' ), 20, 2 );
		// Also output standalone enriched schema in head if needed
		add_action( 'wp_head', array( __CLASS__, 'render_gmc_product_schema' ), 30 );
	}

	/**
	 * Enrich WooCommerce standard structured data
	 */
	public static function enrich_product_schema( $markup, $product ) {
		if ( ! is_a( $product, 'WC_Product' ) ) {
			return $markup;
		}

		$markup['brand'] = array(
			'@type' => 'Brand',
			'name'  => 'DONK',
		);

		$sku = $product->get_sku() ? $product->get_sku() : 'DONK-' . $product->get_id();
		$markup['mpn'] = $sku;
		$markup['sku'] = $sku;

		if ( isset( $markup['offers'] ) && is_array( $markup['offers'] ) ) {
			foreach ( $markup['offers'] as &$offer ) {
				$offer['itemCondition'] = 'https://schema.org/NewCondition';
				$offer['seller']        = array(
					'@type' => 'Organization',
					'name'  => 'Donk Toss',
					'url'   => home_url( '/' ),
				);

				// Merchant Return Policy
				$offer['hasMerchantReturnPolicy'] = array(
					'@type'                => 'MerchantReturnPolicy',
					'applicableCountry'    => 'US',
					'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
					'merchantReturnDays'   => 30,
					'returnMethod'         => 'https://schema.org/ReturnByMail',
					'returnFees'           => 'https://schema.org/FreeReturn',
					'returnShippingFeesAmount' => array(
						'@type'    => 'MonetaryAmount',
						'value'    => '0.00',
						'currency' => 'USD',
					),
				);

				// Shipping Details
				$offer['shippingDetails'] = array(
					'@type'               => 'OfferShippingDetails',
					'shippingRate'        => array(
						'@type'    => 'MonetaryAmount',
						'value'    => '0.00',
						'currency' => 'USD',
					),
					'shippingDestination' => array(
						'@type'          => 'DefinedRegion',
						'addressCountry' => 'US',
					),
					'deliveryTime'        => array(
						'@type'        => 'ShippingDeliveryTime',
						'handlingTime' => array(
							'@type'    => 'QuantitativeValue',
							'minValue' => 1,
							'maxValue' => 3,
							'unitCode' => 'd',
						),
						'transitTime'  => array(
							'@type'    => 'QuantitativeValue',
							'minValue' => 3,
							'maxValue' => 5,
							'unitCode' => 'd',
						),
					),
				);
			}
		}

		return $markup;
	}

	/**
	 * Output authoritative, standalone JSON-LD on single product pages
	 */
	public static function render_gmc_product_schema() {
		if ( ! is_product() ) {
			return;
		}

		global $post;
		$product = wc_get_product( $post->ID );
		if ( ! $product ) {
			return;
		}

		$image_id  = $product->get_image_id();
		$image_url = $image_id ? wp_get_attachment_url( $image_id ) : '';
		$sku       = $product->get_sku() ? $product->get_sku() : 'DONK-' . $product->get_id();
		$price     = (float) $product->get_price();

		$schema = array(
			'@context'    => 'https://schema.org/',
			'@type'       => 'Product',
			'@id'         => get_permalink( $product->get_id() ) . '#gmc-product',
			'name'        => $product->get_name(),
			'url'         => get_permalink( $product->get_id() ),
			'description' => wp_strip_all_tags( $product->get_short_description() ? $product->get_short_description() : $product->get_description() ),
			'image'       => $image_url,
			'sku'         => $sku,
			'mpn'         => $sku,
			'brand'       => array(
				'@type' => 'Brand',
				'name'  => 'DONK',
			),
			'offers'      => array(
				'@type'                    => 'Offer',
				'url'                      => get_permalink( $product->get_id() ),
				'price'                    => number_format( $price, 2, '.', '' ),
				'priceCurrency'            => 'USD',
				'priceValidUntil'          => date( 'Y-12-31', strtotime( '+1 year' ) ),
				'itemCondition'            => 'https://schema.org/NewCondition',
				'availability'             => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
				'seller'                   => array(
					'@type' => 'Organization',
					'name'  => 'Donk Toss',
					'url'   => home_url( '/' ),
				),
				'hasMerchantReturnPolicy'  => array(
					'@type'                => 'MerchantReturnPolicy',
					'applicableCountry'    => 'US',
					'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
					'merchantReturnDays'   => 30,
					'returnMethod'         => 'https://schema.org/ReturnByMail',
					'returnFees'           => 'https://schema.org/FreeReturn',
				),
				'shippingDetails'          => array(
					'@type'               => 'OfferShippingDetails',
					'shippingRate'        => array(
						'@type'    => 'MonetaryAmount',
						'value'    => '0.00',
						'currency' => 'USD',
					),
					'shippingDestination' => array(
						'@type'          => 'DefinedRegion',
						'addressCountry' => 'US',
					),
					'deliveryTime'        => array(
						'@type'        => 'ShippingDeliveryTime',
						'handlingTime' => array(
							'@type'    => 'QuantitativeValue',
							'minValue' => 1,
							'maxValue' => 3,
							'unitCode' => 'd',
						),
						'transitTime'  => array(
							'@type'    => 'QuantitativeValue',
							'minValue' => 3,
							'maxValue' => 5,
							'unitCode' => 'd',
						),
					),
				),
			),
		);

		echo "\n<!-- Google Merchant Center Authoritative Product Schema -->\n";
		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . "</script>\n";
	}
}

DonkToss_GMC_Schema::init();
