<?php
/**
 * Render Callback for ACF Gutenberg Block: FAQ Accordion
 *
 * @package Donk Toss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function donktoss_render_faq_accordion_block( $block, $content = '', $is_preview = false, $post_id = 0 ) {
	// Block unique ID
	$block_id = 'donktoss-faq-' . $block['id'];
	if ( ! empty( $block['anchor'] ) ) {
		$block_id = $block['anchor'];
	}

	// Block Alignment & Classes
	$class_name = 'donktoss-faq-block-container';
	if ( ! empty( $block['className'] ) ) {
		$class_name .= ' ' . $block['className'];
	}
	if ( ! empty( $block['align'] ) ) {
		$class_name .= ' align' . $block['align'];
	}

	// Fetch ACF Settings
	$selected_cat_ids   = get_field( 'selected_categories' );
	$group_by_category  = get_field( 'group_by_category' );
	$heading_tag        = get_field( 'heading_tag' ) ?: 'h2';
	$accordion_mode     = get_field( 'accordion_mode' ) ?: 'multi';
	$show_search        = get_field( 'show_search' );
	$show_footer_cta    = get_field( 'show_footer_cta' );
	$footer_cta_text    = get_field( 'footer_cta_text' );

	if ( false === $group_by_category ) {
		$group_by_category = false;
	} else {
		$group_by_category = true;
	}

	if ( false === $show_footer_cta ) {
		$show_footer_cta = false;
	} else {
		$show_footer_cta = true;
	}

	if ( empty( $footer_cta_text ) ) {
		$footer_cta_text = '<p>View all FAQs <a href="/faqs/">here</a>. Have a question not answered here? Email us at <a href="mailto:info@donktoss.com">info@donktoss.com</a>.</p>';
	}

	// Ensure $selected_cat_ids is array if single ID passed
	if ( ! empty( $selected_cat_ids ) && ! is_array( $selected_cat_ids ) ) {
		$selected_cat_ids = array( $selected_cat_ids );
	}

	// Prepare Schema.org JSON-LD array
	$schema_questions = array();

	// Fetch terms or posts
	$categorized_faqs = array();

	if ( $group_by_category ) {
		$term_args = array(
			'taxonomy'   => 'faq_category',
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		);
		if ( ! empty( $selected_cat_ids ) ) {
			$term_args['include'] = $selected_cat_ids;
		}

		$terms = get_terms( $term_args );

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$faq_query = new WP_Query( array(
					'post_type'      => 'faq',
					'posts_per_page' => -1,
					'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
					'tax_query'      => array(
						array(
							'taxonomy' => 'faq_category',
							'field'    => 'term_id',
							'terms'    => $term->term_id,
						),
					),
				) );

				if ( $faq_query->have_posts() ) {
					$categorized_faqs[ $term->name ] = $faq_query->posts;
				}
				wp_reset_postdata();
			}
		}
	} else {
		$query_args = array(
			'post_type'      => 'faq',
			'posts_per_page' => -1,
			'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		);

		if ( ! empty( $selected_cat_ids ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'faq_category',
					'field'    => 'term_id',
					'terms'    => $selected_cat_ids,
				),
			);
		}

		$faq_query = new WP_Query( $query_args );
		if ( $faq_query->have_posts() ) {
			$categorized_faqs['All FAQs'] = $faq_query->posts;
		}
		wp_reset_postdata();
	}

	// Render Output
	?>
	<div id="<?php echo esc_attr( $block_id ); ?>" class="<?php echo esc_attr( $class_name ); ?>" data-accordion-mode="<?php echo esc_attr( $accordion_mode ); ?>" itemscope itemtype="https://schema.org/FAQPage">
		
		<?php if ( $is_preview ) : ?>
			<div class="donktoss-faq-admin-notice">
				<span class="dashicons dashicons-editor-help"></span> <strong>Donk Toss FAQ Accordion Block</strong> (Gutenberg Preview)
			</div>
		<?php endif; ?>

		<?php if ( $show_search ) : ?>
			<div class="donktoss-faq-search-wrapper">
				<input type="text" class="donktoss-faq-search-input" placeholder="Search FAQs..." aria-label="Search FAQs" />
				<span class="donktoss-faq-search-icon" aria-hidden="true">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
				</span>
			</div>
		<?php endif; ?>

		<?php if ( empty( $categorized_faqs ) ) : ?>
			<div class="donktoss-faq-empty">
				<p><?php esc_html_e( 'No FAQs found matching the selected topics.', 'donk-toss' ); ?></p>
			</div>
		<?php else : ?>
			<div class="donktoss-faq-list">
				<?php foreach ( $categorized_faqs as $cat_title => $posts ) : ?>
					<div class="donktoss-faq-category-group">
						<?php if ( $group_by_category && count( $categorized_faqs ) > 0 ) : ?>
							<<?php echo esc_attr( $heading_tag ); ?> class="donktoss-faq-category-heading">
								<?php echo esc_html( $cat_title ); ?>
							</<?php echo esc_attr( $heading_tag ); ?>>
						<?php endif; ?>

						<div class="donktoss-faq-items">
							<?php foreach ( $posts as $faq_post ) : 
								$question = get_the_title( $faq_post->ID );
								$answer   = get_field( 'faq_answer', $faq_post->ID );
								if ( empty( $answer ) ) {
									$answer = apply_filters( 'the_content', $faq_post->post_content );
								} else {
									$answer = wpautop( $answer );
								}
								$badge = get_field( 'faq_badge', $faq_post->ID );

								// Add to Schema.org JSON-LD array
								$schema_questions[] = array(
									'@type'          => 'Question',
									'name'           => wp_strip_all_tags( $question ),
									'acceptedAnswer' => array(
										'@type' => 'Answer',
										'text'  => wp_strip_all_tags( $answer ),
									),
								);
							?>
								<details class="donktoss-faq-item" itemprop="mainEntity" itemscope itemtype="https://schema.org/Question" data-faq-id="<?php echo esc_attr( $faq_post->ID ); ?>">
									<summary class="donktoss-faq-question" itemprop="name">
										<span class="donktoss-faq-question-text"><?php echo esc_html( $question ); ?></span>
										<?php if ( ! empty( $badge ) ) : ?>
											<span class="donktoss-faq-badge"><?php echo esc_html( $badge ); ?></span>
										<?php endif; ?>
										<span class="donktoss-faq-icon" aria-hidden="true">
											<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
												<polyline points="6 9 12 15 18 9"></polyline>
											</svg>
										</span>
									</summary>
									<div class="donktoss-faq-answer" itemprop="acceptedAnswer" itemscope itemtype="https://schema.org/Answer" data-faq-answer>
										<div class="donktoss-faq-answer-inner" itemprop="text">
											<?php echo wp_kses_post( $answer ); ?>
										</div>
									</div>
								</details>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( $show_footer_cta && ! empty( $footer_cta_text ) ) : ?>
			<div class="donktoss-faq-footer-cta">
				<?php echo wp_kses_post( $footer_cta_text ); ?>
			</div>
		<?php endif; ?>

		<?php
		// Render Schema.org FAQPage JSON-LD for Search Engines & AI Crawlers
		if ( ! empty( $schema_questions ) && ! $is_preview ) :
			$schema_json = array(
				'@context'   => 'https://schema.org',
				'@type'      => 'FAQPage',
				'mainEntity' => $schema_questions,
			);
			?>
			<script type="application/ld+json">
				<?php echo wp_json_encode( $schema_json, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ); ?>
			</script>
		<?php endif; ?>
	</div>
	<?php
}
