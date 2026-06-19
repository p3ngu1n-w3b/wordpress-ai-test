<?php
/**
 * Main template / blog index.
 *
 * @package AI_Site
 */

get_header();
?>
<div class="container content-area content-area--with-sidebar">
	<div class="main-column">
		<?php if ( is_home() && ! is_front_page() ) : ?>
			<header class="page-header">
				<h1 class="page-title"><?php single_post_title(); ?></h1>
			</header>
		<?php endif; ?>

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
			<p><?php esc_html_e( 'Nothing has been published yet.', 'ai-site' ); ?></p>
		<?php endif; ?>
	</div>
	<?php get_sidebar(); ?>
</div>
<?php
get_footer();
