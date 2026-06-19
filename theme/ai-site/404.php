<?php
/**
 * 404 template.
 *
 * @package AI_Site
 */

get_header();
?>
<div class="container content-area">
	<div class="main-column main-column--centered">
		<header class="page-header">
			<h1 class="page-title"><?php esc_html_e( 'Page not found', 'ai-site' ); ?></h1>
		</header>
		<p><?php esc_html_e( 'The page you were looking for could not be found. It might have been moved or deleted.', 'ai-site' ); ?></p>
		<p><a class="btn btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to home', 'ai-site' ); ?></a></p>
		<?php get_search_form(); ?>
	</div>
</div>
<?php
get_footer();
