<?php
/**
 * Call-to-action section.
 *
 * @package AI_Site
 */

$args    = wp_parse_args( $args ?? array(), array( 'section' => array() ) );
$section = $args['section'];
$heading = isset( $section['heading'] ) ? $section['heading'] : '';
$text    = isset( $section['text'] ) ? $section['text'] : '';
$label   = isset( $section['cta']['label'] ) ? $section['cta']['label'] : '';
$url     = isset( $section['cta']['url'] ) ? $section['cta']['url'] : '';

if ( ! $heading && ! $text ) {
	return;
}
?>
<section class="section section--cta">
	<div class="container section--cta__inner">
		<?php if ( $heading ) : ?>
			<h2 class="section__heading"><?php echo esc_html( $heading ); ?></h2>
		<?php endif; ?>
		<?php if ( $text ) : ?>
			<p><?php echo esc_html( $text ); ?></p>
		<?php endif; ?>
		<?php if ( $label && $url ) : ?>
			<a class="btn btn--primary" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a>
		<?php endif; ?>
	</div>
</section>
