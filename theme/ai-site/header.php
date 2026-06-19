<?php
/**
 * Header template.
 *
 * @package AI_Site
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Skip to content', 'ai-site' ); ?></a>

<header class="site-header">
	<div class="container site-header__inner">
		<div class="site-branding">
			<?php
			if ( has_custom_logo() ) {
				the_custom_logo();
			} else {
				?>
				<a class="site-title" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
				<?php
			}
			$description = get_bloginfo( 'description', 'display' );
			if ( $description ) {
				echo '<p class="site-description">' . esc_html( $description ) . '</p>';
			}
			?>
		</div>

		<button class="nav-toggle" aria-controls="primary-navigation" aria-expanded="false">
			<span class="nav-toggle__bar"></span>
			<span class="screen-reader-text"><?php esc_html_e( 'Menu', 'ai-site' ); ?></span>
		</button>

		<nav id="primary-navigation" class="main-navigation" aria-label="<?php esc_attr_e( 'Primary', 'ai-site' ); ?>">
			<?php
			wp_nav_menu( array(
				'theme_location' => 'primary',
				'menu_class'     => 'nav-menu',
				'container'      => false,
				'fallback_cb'    => 'ai_site_default_menu',
			) );
			?>
		</nav>
	</div>
</header>

<main id="content" class="site-content">
