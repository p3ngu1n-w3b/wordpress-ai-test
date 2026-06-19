<?php
/**
 * Features section.
 *
 * @package AI_Site
 */

$args    = wp_parse_args( $args ?? array(), array( 'section' => array() ) );
$section = $args['section'];
$heading = isset( $section['heading'] ) ? $section['heading'] : '';
$items   = isset( $section['items'] ) && is_array( $section['items'] ) ? $section['items'] : array();

if ( empty( $items ) ) {
	return;
}
?>
<section class="section section--features">
	<div class="container">
		<?php if ( $heading ) : ?>
			<h2 class="section__heading"><?php echo esc_html( $heading ); ?></h2>
		<?php endif; ?>
		<div class="features-grid">
			<?php foreach ( $items as $item ) : ?>
				<div class="feature-card">
					<?php if ( ! empty( $item['icon'] ) ) : ?>
						<span class="feature-card__icon" data-icon="<?php echo esc_attr( $item['icon'] ); ?>" aria-hidden="true"></span>
					<?php endif; ?>
					<?php if ( ! empty( $item['title'] ) ) : ?>
						<h3 class="feature-card__title"><?php echo esc_html( $item['title'] ); ?></h3>
					<?php endif; ?>
					<?php if ( ! empty( $item['text'] ) ) : ?>
						<p class="feature-card__text"><?php echo esc_html( $item['text'] ); ?></p>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
