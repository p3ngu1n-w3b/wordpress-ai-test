<?php
/**
 * Single post template.
 *
 * @package AI_Site
 */

get_header();
?>
<div class="container content-area content-area--with-sidebar">
	<div class="main-column">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'single-post' ); ?>>
				<header class="entry-header">
					<h1 class="entry-title"><?php the_title(); ?></h1>
					<p class="entry-meta">
						<?php
						printf(
							/* translators: 1: date, 2: author. */
							esc_html__( 'Published on %1$s by %2$s', 'ai-site' ),
							'<time datetime="' . esc_attr( get_the_date( 'c' ) ) . '">' . esc_html( get_the_date() ) . '</time>',
							esc_html( get_the_author() )
						);
						?>
					</p>
				</header>
				<?php
				if ( has_post_thumbnail() ) {
					the_post_thumbnail( 'large', array( 'class' => 'featured-image' ) );
				}
				?>
				<div class="entry-content">
					<?php
					the_content();
					wp_link_pages();
					?>
				</div>
				<footer class="entry-footer">
					<?php the_tags( '<span class="tag-links">', ', ', '</span>' ); ?>
				</footer>
			</article>
			<?php
			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
		endwhile;
		?>
	</div>
	<?php get_sidebar(); ?>
</div>
<?php
get_footer();
