<?php
/**
 * Front page template.
 *
 * Renders the hero and the config-driven sections. If the front page is set to
 * a static page that has its own content, that content is rendered below the
 * sections so editors can still add block content in wp-admin.
 *
 * @package AI_Site
 */

get_header();

$hero_heading    = ai_site_mod( 'hero_heading' );
$hero_subheading = ai_site_mod( 'hero_subheading' );
$hero_image      = ai_site_mod( 'hero_image' );
$cta_label       = ai_site_mod( 'hero_cta_label' );
$cta_url         = ai_site_mod( 'hero_cta_url' );
$cta2_label      = ai_site_mod( 'hero_cta2_label' );
$cta2_url        = ai_site_mod( 'hero_cta2_url' );
?>

<?php if ( $hero_heading || $hero_subheading || $hero_image ) : ?>
<section class="hero">
	<div class="container hero__inner">
		<div class="hero__content">
			<?php if ( $hero_heading ) : ?>
				<h1 class="hero__heading"><?php echo esc_html( $hero_heading ); ?></h1>
			<?php endif; ?>
			<?php if ( $hero_subheading ) : ?>
				<p class="hero__subheading"><?php echo esc_html( $hero_subheading ); ?></p>
			<?php endif; ?>
			<?php if ( ( $cta_label && $cta_url ) || ( $cta2_label && $cta2_url ) ) : ?>
				<div class="hero__actions">
					<?php if ( $cta_label && $cta_url ) : ?>
						<a class="btn btn--primary" href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( $cta_label ); ?></a>
					<?php endif; ?>
					<?php if ( $cta2_label && $cta2_url ) : ?>
						<a class="btn btn--ghost" href="<?php echo esc_url( $cta2_url ); ?>"><?php echo esc_html( $cta2_label ); ?></a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php if ( $hero_image ) : ?>
			<div class="hero__media">
				<img src="<?php echo esc_url( $hero_image ); ?>" alt="<?php echo esc_attr( $hero_heading ); ?>" loading="eager">
			</div>
		<?php endif; ?>
	</div>
</section>
<?php endif; ?>

<?php
$sections = ai_site_sections();
foreach ( $sections as $section ) {
	$type = isset( $section['type'] ) ? $section['type'] : '';
	switch ( $type ) {
		case 'features':
			get_template_part( 'template-parts/section', 'features', array( 'section' => $section ) );
			break;
		case 'about':
			get_template_part( 'template-parts/section', 'about', array( 'section' => $section ) );
			break;
		case 'gallery':
			get_template_part( 'template-parts/section', 'gallery', array( 'section' => $section ) );
			break;
		case 'cta':
			get_template_part( 'template-parts/section', 'cta', array( 'section' => $section ) );
			break;
	}
}
?>

<?php
// Render the static front page's own block content, if any.
if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		$content = get_the_content();
		if ( trim( wp_strip_all_tags( $content ) ) !== '' ) :
			?>
			<section class="page-content">
				<div class="container">
					<?php the_content(); ?>
				</div>
			</section>
			<?php
		endif;
	endwhile;
endif;
?>

<?php get_footer(); ?>
