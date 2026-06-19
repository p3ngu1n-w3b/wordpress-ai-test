<?php
/**
 * Single page template.
 *
 * @package AI_Site
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'single-page' ); ?>>
		<header class="page-header">
			<div class="container">
				<h1 class="page-title"><?php the_title(); ?></h1>
			</div>
		</header>
		<div class="container entry-content">
			<?php
			if ( has_post_thumbnail() ) {
				the_post_thumbnail( 'large', array( 'class' => 'featured-image' ) );
			}
			the_content();
			wp_link_pages();
			?>
		</div>
	</article>
	<?php
	if ( comments_open() || get_comments_number() ) {
		echo '<div class="container">';
		comments_template();
		echo '</div>';
	}
endwhile;

get_footer();
