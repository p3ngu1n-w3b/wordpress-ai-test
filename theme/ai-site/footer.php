<?php
/**
 * Footer template.
 *
 * @package AI_Site
 */

$email   = ai_site_mod( 'contact_email' );
$phone   = ai_site_mod( 'contact_phone' );
$address = ai_site_mod( 'contact_address' );
$socials = array(
	'twitter'   => ai_site_mod( 'social_twitter' ),
	'instagram' => ai_site_mod( 'social_instagram' ),
	'facebook'  => ai_site_mod( 'social_facebook' ),
);
?>
</main><!-- #content -->

<footer class="site-footer">
	<div class="container site-footer__inner">
		<div class="site-footer__col">
			<span class="site-footer__brand"><?php bloginfo( 'name' ); ?></span>
			<?php if ( get_bloginfo( 'description' ) ) : ?>
				<p class="site-footer__tagline"><?php bloginfo( 'description' ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( $email || $phone || $address ) : ?>
		<div class="site-footer__col">
			<h4 class="widget-title"><?php esc_html_e( 'Contact', 'ai-site' ); ?></h4>
			<ul class="site-footer__contact">
				<?php if ( $email ) : ?><li><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></li><?php endif; ?>
				<?php if ( $phone ) : ?><li><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></li><?php endif; ?>
				<?php if ( $address ) : ?><li><?php echo esc_html( $address ); ?></li><?php endif; ?>
			</ul>
		</div>
		<?php endif; ?>

		<div class="site-footer__col">
			<?php if ( has_nav_menu( 'footer' ) ) : ?>
				<h4 class="widget-title"><?php esc_html_e( 'Links', 'ai-site' ); ?></h4>
				<?php
				wp_nav_menu( array(
					'theme_location' => 'footer',
					'menu_class'     => 'nav-menu nav-menu--footer',
					'container'      => false,
					'depth'          => 1,
				) );
				?>
			<?php endif; ?>

			<?php
			$has_social = array_filter( $socials );
			if ( $has_social ) :
			?>
			<ul class="social-links">
				<?php foreach ( $socials as $name => $url ) : ?>
					<?php if ( $url ) : ?>
						<li><a href="<?php echo esc_url( $url ); ?>" rel="noopener noreferrer" target="_blank"><?php echo esc_html( ucfirst( $name ) ); ?></a></li>
					<?php endif; ?>
				<?php endforeach; ?>
			</ul>
			<?php endif; ?>
		</div>
	</div>

	<div class="site-footer__bottom">
		<div class="container">
			<p>&copy; <?php echo esc_html( date_i18n( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'All rights reserved.', 'ai-site' ); ?></p>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
