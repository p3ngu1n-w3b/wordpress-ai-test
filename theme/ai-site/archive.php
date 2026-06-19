<?php
/**
 * Archive template.
 *
 * @package AI_Site
 */

get_header();
?>
<div class="container content-area content-area--with-sidebar">
	<div class="main-column">
		<header class="page-header">
			<?php
			the_archive_title( '<h1 class="page-title">', '</h1>' );
			the_archive_description( '<div class="archive-description">', '</div>' );
			?>
		</header>

		<?php if ( have_posts() ) : ?>
			<div class="post-list">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/content', 'excerpt' );
				endwhile;
				?>
			</div>
			<?php ai_site_pagination(); ?>
		<?php else : ?>
			<p><?php esc_html_e( 'No posts found.', 'ai-site' ); ?></p>
		<?php endif; ?>
	</div>
	<?php get_sidebar(); ?>
</div>
<?php
get_footer();
