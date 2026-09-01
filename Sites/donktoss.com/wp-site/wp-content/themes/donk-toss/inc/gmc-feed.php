<?php
/**
 * Google Merchant Center Dynamic XML Product Feed Generator
 *
 * Exposes a live, self-updating Google Shopping XML feed endpoint at:
 * https://donktoss.com/?feed=google-merchant-center
 * or https://donktoss.com/gmc-feed.xml
 *
 * @package DonkToss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DonkToss_GMC_Feed {

	public static function init() {
		// Custom query var & template redirect
		add_action( 'init', array( __CLASS__, 'add_feed_endpoint' ) );
		add_action( 'template_redirect', array( __CLASS__, 'render_feed' ) );
	}

	public static function add_feed_endpoint() {
		add_feed( 'google-merchant-center', array( __CLASS__, 'render_xml' ) );
		add_feed( 'gmc', array( __CLASS__, 'render_xml' ) );
	}

	public static function render_feed() {
		if ( isset( $_GET['feed'] ) && in_array( sanitize_text_field( $_GET['feed'] ), array( 'google-merchant-center', 'gmc', 'google-shopping' ), true ) ) {
			self::render_xml();
			exit;
		}
	}

	public static function render_xml() {
		header( 'Content-Type: application/xml; charset=UTF-8' );
		header( 'X-Robots-Tag: noindex, follow', true );

		$products = wc_get_products( array(
			'status' => 'publish',
			'limit'  => -1,
		) );

		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		?>
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
  <channel>
    <title><?php echo esc_html( get_bloginfo( 'name' ) ); ?> — Official Google Merchant Feed</title>
    <link><?php echo esc_url( home_url( '/' ) ); ?></link>
    <description>Official Google Shopping product catalog for Donk Toss tournament kits and accessories.</description>
    <lastBuildDate><?php echo esc_html( date( 'r' ) ); ?></lastBuildDate>
    <?php
    foreach ( $products as $product ) {
    	if ( ! $product || ! $product->is_visible() ) {
    		continue;
    	}

    	$prod_id     = $product->get_id();
    	$sku         = $product->get_sku() ? $product->get_sku() : 'DONK-' . $prod_id;
    	$title       = $product->get_name();
    	$desc        = wp_strip_all_tags( $product->get_description() ? $product->get_description() : $product->get_short_description() );
    	$link        = get_permalink( $prod_id );
    	$image_id    = $product->get_image_id();
    	$image_url   = $image_id ? wp_get_attachment_url( $image_id ) : '';
    	$price       = number_format( (float) $product->get_price(), 2, '.', '' ) . ' ' . get_woocommerce_currency();
    	$avail       = $product->is_in_stock() ? 'in_stock' : 'out_of_stock';
    	$category    = wp_strip_all_tags( wc_get_product_category_list( $prod_id ) );

    	// Additional gallery images
    	$gallery_ids = $product->get_gallery_image_ids();
    	?>
    <item>
      <g:id><?php echo esc_xml( $sku ); ?></g:id>
      <g:title><?php echo esc_xml( $title ); ?></g:title>
      <g:description><?php echo esc_xml( $desc ); ?></g:description>
      <g:link><?php echo esc_url( add_query_arg( array( 'utm_source' => 'google', 'utm_medium' => 'cpc', 'utm_campaign' => 'shopping' ), $link ) ); ?></g:link>
      <?php if ( $image_url ) : ?>
      <g:image_link><?php echo esc_url( $image_url ); ?></g:image_link>
      <?php endif; ?>
      <?php if ( ! empty( $gallery_ids ) ) :
      	foreach ( array_slice( $gallery_ids, 0, 3 ) as $gid ) :
      		$gurl = wp_get_attachment_url( $gid );
      		if ( $gurl ) : ?>
      <g:additional_image_link><?php echo esc_url( $gurl ); ?></g:additional_image_link>
      <?php endif; endforeach; endif; ?>
      <g:availability><?php echo esc_xml( $avail ); ?></g:availability>
      <g:price><?php echo esc_xml( $price ); ?></g:price>
      <g:brand>DONK</g:brand>
      <g:condition>new</g:condition>
      <g:google_product_category>Toys &amp; Games &gt; Games &gt; Outdoor Games</g:google_product_category>
      <g:product_type><?php echo esc_xml( $category ? 'Backyard Games &gt; ' . $category : 'Backyard Games' ); ?></g:product_type>
      <g:mpn><?php echo esc_xml( $sku ); ?></g:mpn>
      <g:identifier_exists>no</g:identifier_exists>
      <g:shipping>
        <g:country>US</g:country>
        <g:service>Standard Ground</g:service>
        <g:price>0.00 USD</g:price>
      </g:shipping>
    </item>
    <?php } ?>
  </channel>
</rss>
<?php
		exit;
	}
}

DonkToss_GMC_Feed::init();
