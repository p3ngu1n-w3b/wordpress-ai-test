<?php
/**
 * About section.
 *
 * @package AI_Site
 */

$args    = wp_parse_args( $args ?? array(), array( 'section' => array() ) );
$section = $args['section'];
$heading = isset( $section['heading'] ) ? $section['heading'] : '';
$text    = isset( $section['text'] ) ? $section['text'] : '';
$image   = isset( $section['image'] ) ? $section['image'] : '';

if ( ! $heading && ! $text && ! $image ) {
	return;
}
?>
<section class="section section--about">
	<div class="container section--about__inner">
		<?php if ( $image ) : ?>
			<div class="section--about__media">
				<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $heading ); ?>" loading="lazy">
			</div>
		<?php endif; ?>
		<div class="section--about__content">
			<?php if ( $heading ) : ?>
				<h2 class="section__heading"><?php echo esc_html( $heading ); ?></h2>
			<?php endif; ?>
			<?php if ( $text ) : ?>
				<p><?php echo esc_html( $text ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</section>
