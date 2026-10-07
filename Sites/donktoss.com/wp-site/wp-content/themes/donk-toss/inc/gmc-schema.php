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
	 * Dynamically determine standard US shipping cost for a given product
	 * based on its WooCommerce shipping class and US flat rate configuration.
	 */
	public static function get_product_shipping_cost( $product ) {
		if ( ! is_a( $product, 'WC_Product' ) ) {
			return 0.00;
		}

		$shipping_class_id = $product->get_shipping_class_id();

		// Check WooCommerce US shipping zones
		if ( class_exists( 'WC_Shipping_Zones' ) ) {
			$zones = WC_Shipping_Zones::get_zones();
			foreach ( $zones as $zone ) {
				if ( isset( $zone['zone_name'] ) && ( stripos( $zone['zone_name'], 'United States' ) !== false || stripos( $zone['zone_name'], 'US' ) !== false ) ) {
					if ( ! empty( $zone['shipping_methods'] ) ) {
						foreach ( $zone['shipping_methods'] as $method ) {
							if ( $method->id === 'flat_rate' && $method->enabled === 'yes' ) {
								$settings = isset( $method->instance_settings ) ? $method->instance_settings : array();
								if ( $shipping_class_id && ! empty( $settings[ "class_cost_{$shipping_class_id}" ] ) ) {
									$raw_cost = preg_replace( '/[^0-9\.]/', '', $settings[ "class_cost_{$shipping_class_id}" ] );
									if ( is_numeric( $raw_cost ) ) {
										return (float) $raw_cost;
									}
								}
								if ( ! empty( $settings['no_class_cost'] ) ) {
									$raw_cost = preg_replace( '/[^0-9\.]/', '', $settings['no_class_cost'] );
									if ( is_numeric( $raw_cost ) ) {
										return (float) $raw_cost;
									}
								}
								if ( isset( $settings['cost'] ) && is_numeric( $settings['cost'] ) && (float) $settings['cost'] > 0 ) {
									return (float) $settings['cost'];
								}
							}
						}
					}
				}
			}
		}

		// Fallback mapping based on configured Donk Toss shipping classes
		$class_cost_map = array(
			25 => 50.00, // Donk Pro Kit
			38 => 15.00, // Donk TableTop Kit
			37 => 10.00, // Donk Dice Kit
			60 => 20.00, // Donk Pro Single
		);

		if ( $shipping_class_id && isset( $class_cost_map[ $shipping_class_id ] ) ) {
			return (float) $class_cost_map[ $shipping_class_id ];
		}

		return 0.00;
	}

	/**
	 * Enrich WooCommerce standard structured data
	 */
	public static function enrich_product_schema( $markup, $product ) {
		if ( ! is_a( $product, 'WC_Product' ) ) {
			return $markup;
		}

		$shipping_cost = self::get_product_shipping_cost( $product );

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

				// Merchant Return Policy (All standard sales final; replacements for defective/damaged items)
				$offer['hasMerchantReturnPolicy'] = array(
					'@type'                => 'MerchantReturnPolicy',
					'applicableCountry'    => 'US',
					'returnPolicyCategory' => 'https://schema.org/MerchantReturnNotPermitted',
					'merchantReturnLink'   => home_url( '/refund_returns/' ),
				);

				// Shipping Details
				$offer['shippingDetails'] = array(
					'@type'               => 'OfferShippingDetails',
					'shippingRate'        => array(
						'@type'    => 'MonetaryAmount',
						'value'    => number_format( $shipping_cost, 2, '.', '' ),
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

		$shipping_cost = self::get_product_shipping_cost( $product );
		$image_id      = $product->get_image_id();
		$image_url     = $image_id ? wp_get_attachment_url( $image_id ) : '';
		$sku           = $product->get_sku() ? $product->get_sku() : 'DONK-' . $product->get_id();
		$price         = (float) $product->get_price();

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
					'returnPolicyCategory' => 'https://schema.org/MerchantReturnNotPermitted',
					'merchantReturnLink'   => home_url( '/refund_returns/' ),
				),
				'shippingDetails'          => array(
					'@type'               => 'OfferShippingDetails',
					'shippingRate'        => array(
						'@type'    => 'MonetaryAmount',
						'value'    => number_format( $shipping_cost, 2, '.', '' ),
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
