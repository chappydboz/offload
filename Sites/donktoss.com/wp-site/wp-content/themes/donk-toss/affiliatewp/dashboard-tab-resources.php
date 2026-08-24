<?php
/**
 * Affiliate Dashboard Tab: Help & Resources (Guidelines & FAQs)
 *
 * @package Donk Toss
 */

$affiliate_id   = affwp_get_affiliate_id();
$affiliate_user = get_userdata( affwp_get_affiliate_user_id( $affiliate_id ) );
$coupon_code    = '';

if ( function_exists( 'affwp_get_affiliate_coupons' ) ) {
	$coupons = affwp_get_affiliate_coupons( $affiliate_id );
	if ( ! empty( $coupons ) && is_array( $coupons ) ) {
		if ( ! empty( $coupons['dynamic'] ) && is_array( $coupons['dynamic'] ) ) {
			$dyn = reset( $coupons['dynamic'] );
			if ( is_array( $dyn ) && ! empty( $dyn['code'] ) ) {
				$coupon_code = $dyn['code'];
			}
		} elseif ( ! empty( $coupons['standard'] ) && is_array( $coupons['standard'] ) ) {
			$std = reset( $coupons['standard'] );
			if ( is_array( $std ) && ! empty( $std['code'] ) ) {
				$coupon_code = $std['code'];
			}
		}
	}
}

if ( empty( $coupon_code ) && $affiliate_user ) {
	$coupon_code = 'AFFILIATE-' . strtoupper( $affiliate_user->user_login );
}
?>
<div id="affwp-affiliate-dashboard-resources" class="affwp-tab-content donk-resources-tab-content">

	<!-- Header Intro Card -->
	<div class="affwp-card donk-resources-intro-card">
		<div class="donk-resources-hero">
			<h3 class="donk-resources-title">Affiliate Help, Guidelines & FAQs</h3>
			<p class="donk-resources-desc">Everything you need to successfully promote Donk Toss, maximize your referral commissions, troubleshoot tracking, and understand program guidelines.</p>
		</div>

		<?php if ( ! empty( $coupon_code ) ) : ?>
		<div class="donk-affiliate-coupon-callout">
			<div class="donk-coupon-callout-icon">💡</div>
			<div class="donk-coupon-callout-body">
				<div class="donk-coupon-callout-title">
					Your Dedicated Coupon Code: <span class="donk-coupon-pill"><?php echo esc_html( $coupon_code ); ?></span>
				</div>
				<p>Sharing your coupon code gives your audience a discount and <strong>guarantees 100% commission attribution</strong> &mdash; even on Apple Pay, ad blockers, Instagram/TikTok in-app browsers, and cross-device purchases!</p>
			</div>
		</div>
		<?php endif; ?>
	</div>

	<!-- Guidelines & How It Works Grid -->
	<div class="donk-resources-grid">
		<div class="affwp-card donk-resource-col">
			<h4 class="donk-card-heading">🏆 How Earning Works</h4>
			<ul class="donk-guidelines-list">
				<li><strong>10% Commission on Every Sale:</strong> Earn 10% on every referred order (excluding shipping & taxes).</li>
				<li><strong>Lifetime Customer Tagging:</strong> When a customer purchases through your link or code, they are tagged to you forever. You earn commission on all their future orders too!</li>
				<li><strong>Monthly Payouts:</strong> Earnings are paid out monthly via PayPal (or store credit) after standard 30-day order return clearance.</li>
			</ul>
		</div>

		<div class="affwp-card donk-resource-col">
			<h4 class="donk-card-heading">📣 Promotion Guidelines</h4>
			<ul class="donk-guidelines-list">
				<li><strong>Where to Share:</strong> Social media posts, link-in-bio, YouTube/TikTok video descriptions, text messages, podcasts, tailgates, and tournament nights.</li>
				<li><strong>FTC Disclosure:</strong> Always let your audience know you earn a commission (e.g. <em>#ad</em>, <em>#affiliate</em>, or &ldquo;Use my code to support me!&rdquo;).</li>
				<li><strong>Creatives & Assets:</strong> Check the <a href="<?php echo esc_url( affwp_get_affiliate_area_page_url( 'creatives' ) ); ?>">Creatives tab</a> for official logos and banners.</li>
			</ul>
		</div>
	</div>

	<!-- Program Rules & Prohibited Practices -->
	<div class="affwp-card donk-rules-card">
		<h4 class="donk-card-heading">⚠️ Program Rules & Prohibitions</h4>
		<div class="donk-rules-grid">
			<div class="donk-rule-item">
				<span class="donk-rule-icon">🚫</span>
				<div class="donk-rule-text">
					<strong>No Self-Referrals</strong>
					<p>You cannot use your own referral link or coupon code to purchase products for yourself.</p>
				</div>
			</div>
			<div class="donk-rule-item">
				<span class="donk-rule-icon">🚫</span>
				<div class="donk-rule-text">
					<strong>No PPC Bidding on Branded Terms</strong>
					<p>Bidding on &ldquo;Donk Toss&rdquo;, &ldquo;Donk Toss Coupons&rdquo;, or trademarked phrases on Google/Bing Ads is strictly prohibited.</p>
				</div>
			</div>
			<div class="donk-rule-item">
				<span class="donk-rule-icon">🚫</span>
				<div class="donk-rule-text">
					<strong>No Coupon Scraper Sites</strong>
					<p>Submitting affiliate codes or links to coupon aggregator or scraper sites is not permitted.</p>
				</div>
			</div>
			<div class="donk-rule-item">
				<span class="donk-rule-icon">🚫</span>
				<div class="donk-rule-text">
					<strong>No Spam</strong>
					<p>Mass unsolicited emails, SMS spam, or automated bot commenting are zero-tolerance violations.</p>
				</div>
			</div>
		</div>
	</div>

	<!-- Affiliate FAQs Accordion Section -->
	<div class="affwp-card donk-faqs-card">
		<h4 class="donk-card-heading">❓ Frequently Asked Questions</h4>
		<div class="donk-affiliate-faqs-wrapper">
			<?php echo do_shortcode( '[donktoss_faqs category="affiliates" heading_tag="h4" show_search="0" show_footer_cta="0"]' ); ?>
		</div>
	</div>

	<!-- Support & Manual Attribution Card -->
	<div class="affwp-card donk-support-card">
		<div class="donk-support-content">
			<h4>Need Help or Missing an Attribution?</h4>
			<p>If a customer purchased through your referral but didn't use your code or cookie, don't worry! Contact our affiliate support team with the buyer's name or order number and we will review and manually credit the sale.</p>
			<a href="mailto:affiliates@donktoss.com?subject=Affiliate%20Attribution%20Inquiry%20-%20<?php echo esc_attr( $affiliate_user ? $affiliate_user->user_login : '' ); ?>" class="button donk-support-btn">Contact Affiliate Support &rarr;</a>
		</div>
	</div>

</div>
