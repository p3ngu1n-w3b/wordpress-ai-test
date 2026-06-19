<?php
/**
 * Post excerpt card used in archives and the blog index.
 *
 * @package AI_Site
 */
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'post-card' ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<a class="post-card__thumb" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
			<?php the_post_thumbnail( 'medium_large' ); ?>
		</a>
	<?php endif; ?>
	<div class="post-card__body">
		<h2 class="post-card__title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h2>
		<p class="post-card__meta">
			<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
		</p>
		<div class="post-card__excerpt"><?php the_excerpt(); ?></div>
		<a class="post-card__more" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Read more', 'ai-site' ); ?> &rarr;</a>
	</div>
</article>
