<?php
/**
 * Sidebar template.
 *
 * @package AI_Site
 */

if ( ! is_active_sidebar( 'sidebar-1' ) ) {
	return;
}
?>
<aside class="sidebar widget-area" aria-label="<?php esc_attr_e( 'Sidebar', 'ai-site' ); ?>">
	<?php dynamic_sidebar( 'sidebar-1' ); ?>
</aside>
