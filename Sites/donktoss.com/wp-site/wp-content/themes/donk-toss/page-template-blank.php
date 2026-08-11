<?php /* Template Name: Blank Page (no header/footer) */ ?>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<meta name="viewport" content="width=device-width" />
<title><?php wp_title( '|', true, 'right' ); ?></title>
<link rel="profile" href="https://gmpg.org/xfn/11" />
<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

	<div id="primary" <?php astra_primary_class(); ?>>

		<?php //astra_primary_content_top(); ?>

		<?php astra_content_page_loop(); ?>

		<?php //astra_primary_content_bottom(); ?>

	</div><!-- #primary -->
	

<?php wp_footer(); ?>
