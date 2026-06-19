<?php
/**
 * Gallery section.
 *
 * @package AI_Site
 */

$args    = wp_parse_args( $args ?? array(), array( 'section' => array() ) );
$section = $args['section'];
$heading = isset( $section['heading'] ) ? $section['heading'] : '';
$images  = isset( $section['images'] ) && is_array( $section['images'] ) ? $section['images'] : array();

if ( empty( $images ) ) {
	return;
}
?>
<section class="section section--gallery">
	<div class="container">
		<?php if ( $heading ) : ?>
			<h2 class="section__heading"><?php echo esc_html( $heading ); ?></h2>
		<?php endif; ?>
		<div class="gallery-grid">
			<?php foreach ( $images as $image ) : ?>
				<figure class="gallery-grid__item">
					<img src="<?php echo esc_url( $image ); ?>" alt="" loading="lazy">
				</figure>
			<?php endforeach; ?>
		</div>
	</div>
</section>
