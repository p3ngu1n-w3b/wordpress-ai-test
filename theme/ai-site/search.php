<?php
/**
 * Search results template.
 *
 * @package AI_Site
 */

get_header();
?>
<div class="container content-area content-area--with-sidebar">
	<div class="main-column">
		<header class="page-header">
			<h1 class="page-title">
				<?php
				/* translators: %s: search query. */
				printf( esc_html__( 'Search results for: %s', 'ai-site' ), '<span>' . esc_html( get_search_query() ) . '</span>' );
				?>
			</h1>
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
			<p><?php esc_html_e( 'No results found. Try another search.', 'ai-site' ); ?></p>
			<?php get_search_form(); ?>
		<?php endif; ?>
	</div>
	<?php get_sidebar(); ?>
</div>
<?php
get_footer();
