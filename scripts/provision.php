<?php
/**
 * Content provisioner — executed via `wp eval-file` so the full WordPress API
 * is available. Reads /site-config/site.json and:
 *   - imports images into the media library (idempotent)
 *   - sets brand colors, fonts, logo and hero/contact theme mods
 *   - creates/updates pages and sets the static front page + blog page
 *   - builds menus and assigns them to theme locations
 *   - creates categories and posts
 *
 * Re-running is safe: existing objects are looked up by slug/title and updated.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Must be run via wp eval-file.\n" );
	exit( 1 );
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$config_path = '/site-config/site.json';
$images_dir  = '/site-config/images';

$cli = function ( $msg ) {
	if ( class_exists( 'WP_CLI' ) ) {
		WP_CLI::log( '  ' . $msg );
	} else {
		echo $msg . "\n";
	}
};

$raw = file_get_contents( $config_path );
if ( false === $raw ) {
	$cli( "Could not read $config_path" );
	return;
}
$config = json_decode( $raw, true );
if ( ! is_array( $config ) ) {
	$cli( 'Invalid JSON in site config.' );
	return;
}

/* -------------------------------------------------------------------------
 * 1. Media import (filename => ['id' => .., 'url' => ..])
 * ---------------------------------------------------------------------- */
$media_map = array();

/**
 * Import a single image file by name, reusing an existing attachment if one
 * with the same source filename already exists.
 */
$import_image = function ( $filename ) use ( &$media_map, $images_dir, $cli ) {
	$filename = trim( (string) $filename );
	if ( '' === $filename ) {
		return null;
	}
	if ( isset( $media_map[ $filename ] ) ) {
		return $media_map[ $filename ];
	}

	$source = $images_dir . '/' . $filename;
	if ( ! file_exists( $source ) ) {
		$cli( "Image not found, skipping: $filename" );
		return null;
	}

	// Reuse by stored source filename to stay idempotent.
	$existing = get_posts( array(
		'post_type'      => 'attachment',
		'posts_per_page' => 1,
		'meta_key'       => '_ai_site_source',
		'meta_value'     => $filename,
		'fields'         => 'ids',
	) );
	if ( ! empty( $existing ) ) {
		$id  = (int) $existing[0];
		$ret = array( 'id' => $id, 'url' => wp_get_attachment_url( $id ) );
		$media_map[ $filename ] = $ret;
		return $ret;
	}

	$upload = wp_upload_dir();
	$dest   = trailingslashit( $upload['path'] ) . wp_unique_filename( $upload['path'], $filename );
	if ( ! @copy( $source, $dest ) ) {
		$cli( "Failed to copy image: $filename" );
		return null;
	}

	$filetype   = wp_check_filetype( basename( $dest ), null );
	$attachment = array(
		'post_mime_type' => $filetype['type'] ? $filetype['type'] : 'image/png',
		'post_title'     => sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) ),
		'post_content'   => '',
		'post_status'    => 'inherit',
	);
	$attach_id = wp_insert_attachment( $attachment, $dest );
	if ( is_wp_error( $attach_id ) || ! $attach_id ) {
		$cli( "Failed to create attachment: $filename" );
		return null;
	}

	// SVGs have no intermediate sizes; guard generate metadata.
	if ( 'image/svg+xml' !== $attachment['post_mime_type'] ) {
		$meta = wp_generate_attachment_metadata( $attach_id, $dest );
		wp_update_attachment_metadata( $attach_id, $meta );
	}
	update_post_meta( $attach_id, '_ai_site_source', $filename );

	$cli( "Imported image: $filename (#$attach_id)" );
	$ret = array( 'id' => $attach_id, 'url' => wp_get_attachment_url( $attach_id ) );
	$media_map[ $filename ] = $ret;
	return $ret;
};

// Pre-import every file in the images dir so galleries/sections resolve.
if ( is_dir( $images_dir ) ) {
	foreach ( (array) scandir( $images_dir ) as $file ) {
		if ( '.' === $file || '..' === $file ) {
			continue;
		}
		if ( is_file( $images_dir . '/' . $file ) ) {
			$import_image( $file );
		}
	}
}

/* -------------------------------------------------------------------------
 * 2. Site identity
 * ---------------------------------------------------------------------- */
$site = isset( $config['site'] ) ? $config['site'] : array();
if ( ! empty( $site['title'] ) ) {
	update_option( 'blogname', $site['title'] );
}
if ( isset( $site['tagline'] ) ) {
	update_option( 'blogdescription', $site['tagline'] );
}

/* -------------------------------------------------------------------------
 * 3. Branding -> theme mods + custom logo
 * ---------------------------------------------------------------------- */
$branding = isset( $config['branding'] ) ? $config['branding'] : array();
if ( ! empty( $branding['colors'] ) ) {
	$colors = $branding['colors'];
	foreach ( array( 'primary', 'accent', 'text', 'background' ) as $c ) {
		if ( ! empty( $colors[ $c ] ) ) {
			$key = 'background' === $c ? 'color_bg' : 'color_' . $c;
			set_theme_mod( 'ai_site_' . $key, sanitize_hex_color( $colors[ $c ] ) );
		}
	}
}
if ( ! empty( $branding['fonts']['heading'] ) ) {
	set_theme_mod( 'ai_site_font_heading', $branding['fonts']['heading'] );
}
if ( ! empty( $branding['fonts']['body'] ) ) {
	set_theme_mod( 'ai_site_font_body', $branding['fonts']['body'] );
}
if ( ! empty( $branding['logo'] ) ) {
	$logo = $import_image( $branding['logo'] );
	if ( $logo ) {
		set_theme_mod( 'custom_logo', $logo['id'] );
	}
}
if ( ! empty( $branding['favicon'] ) ) {
	$fav = $import_image( $branding['favicon'] );
	if ( $fav ) {
		update_option( 'site_icon', $fav['id'] );
	}
}

/* -------------------------------------------------------------------------
 * 4. Hero + contact -> theme mods
 * ---------------------------------------------------------------------- */
$hero = isset( $config['hero'] ) ? $config['hero'] : array();
if ( $hero ) {
	set_theme_mod( 'ai_site_hero_heading', isset( $hero['heading'] ) ? $hero['heading'] : '' );
	set_theme_mod( 'ai_site_hero_subheading', isset( $hero['subheading'] ) ? $hero['subheading'] : '' );
	if ( ! empty( $hero['image'] ) ) {
		$hi = $import_image( $hero['image'] );
		if ( $hi ) {
			set_theme_mod( 'ai_site_hero_image', $hi['url'] );
		}
	}
	set_theme_mod( 'ai_site_hero_cta_label', isset( $hero['cta']['label'] ) ? $hero['cta']['label'] : '' );
	set_theme_mod( 'ai_site_hero_cta_url', isset( $hero['cta']['url'] ) ? $hero['cta']['url'] : '' );
	set_theme_mod( 'ai_site_hero_cta2_label', isset( $hero['secondary_cta']['label'] ) ? $hero['secondary_cta']['label'] : '' );
	set_theme_mod( 'ai_site_hero_cta2_url', isset( $hero['secondary_cta']['url'] ) ? $hero['secondary_cta']['url'] : '' );
}

$contact = isset( $config['contact'] ) ? $config['contact'] : array();
if ( $contact ) {
	set_theme_mod( 'ai_site_contact_email', isset( $contact['email'] ) ? $contact['email'] : '' );
	set_theme_mod( 'ai_site_contact_phone', isset( $contact['phone'] ) ? $contact['phone'] : '' );
	set_theme_mod( 'ai_site_contact_address', isset( $contact['address'] ) ? $contact['address'] : '' );
	$social = isset( $contact['social'] ) ? $contact['social'] : array();
	foreach ( array( 'twitter', 'instagram', 'facebook' ) as $net ) {
		if ( ! empty( $social[ $net ] ) ) {
			set_theme_mod( 'ai_site_social_' . $net, esc_url_raw( $social[ $net ] ) );
		}
	}
}

/* -------------------------------------------------------------------------
 * 5. Sections -> resolve image filenames to URLs, store as JSON theme mod
 * ---------------------------------------------------------------------- */
$sections = isset( $config['sections'] ) && is_array( $config['sections'] ) ? $config['sections'] : array();
foreach ( $sections as &$section ) {
	if ( ! empty( $section['image'] ) ) {
		$im = $import_image( $section['image'] );
		$section['image'] = $im ? $im['url'] : '';
	}
	if ( ! empty( $section['images'] ) && is_array( $section['images'] ) ) {
		$resolved = array();
		foreach ( $section['images'] as $img ) {
			$im = $import_image( $img );
			if ( $im ) {
				$resolved[] = $im['url'];
			}
		}
		$section['images'] = $resolved;
	}
}
unset( $section );
set_theme_mod( 'ai_site_sections_json', wp_json_encode( $sections ) );

/* -------------------------------------------------------------------------
 * 6. Pages (idempotent by slug)
 * ---------------------------------------------------------------------- */
$slug_to_id = array();
$front_page_id = 0;
$pages = isset( $config['pages'] ) && is_array( $config['pages'] ) ? $config['pages'] : array();

$upsert_page = function ( $slug, $title, $content ) use ( $cli ) {
	$existing = get_page_by_path( $slug, OBJECT, 'page' );
	$postarr  = array(
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_content' => $content,
		'post_status'  => 'publish',
		'post_type'    => 'page',
	);
	if ( $existing ) {
		$postarr['ID'] = $existing->ID;
		$id = wp_update_post( $postarr, true );
		$cli( "Updated page: $title" );
	} else {
		$id = wp_insert_post( $postarr, true );
		$cli( "Created page: $title" );
	}
	return is_wp_error( $id ) ? 0 : (int) $id;
};

foreach ( $pages as $page ) {
	if ( empty( $page['slug'] ) || empty( $page['title'] ) ) {
		continue;
	}
	$content = isset( $page['content'] ) ? $page['content'] : '';
	$id      = $upsert_page( $page['slug'], $page['title'], $content );
	if ( $id ) {
		$slug_to_id[ $page['slug'] ] = $id;
		if ( ! empty( $page['front_page'] ) ) {
			$front_page_id = $id;
		}
	}
}

// Ensure a blog/posts page so the post archive stays reachable.
$blog_id = $upsert_page( 'blog', __( 'Blog', 'ai-site' ), '' );

/* -------------------------------------------------------------------------
 * 7. Static front page configuration
 * ---------------------------------------------------------------------- */
if ( $front_page_id ) {
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $front_page_id );
	if ( $blog_id && $blog_id !== $front_page_id ) {
		update_option( 'page_for_posts', $blog_id );
	}
	$cli( 'Configured static front page.' );
}

/* -------------------------------------------------------------------------
 * 8. Categories + posts (idempotent by title)
 * ---------------------------------------------------------------------- */
$posts = isset( $config['posts'] ) && is_array( $config['posts'] ) ? $config['posts'] : array();
foreach ( $posts as $post ) {
	if ( empty( $post['title'] ) ) {
		continue;
	}
	$existing = get_page_by_title( $post['title'], OBJECT, 'post' );
	$cat_ids  = array();
	if ( ! empty( $post['category'] ) ) {
		$term = term_exists( $post['category'], 'category' );
		if ( ! $term ) {
			$term = wp_insert_term( $post['category'], 'category' );
		}
		if ( ! is_wp_error( $term ) ) {
			$cat_ids[] = (int) ( is_array( $term ) ? $term['term_id'] : $term );
		}
	}
	$postarr = array(
		'post_title'    => $post['title'],
		'post_content'  => isset( $post['content'] ) ? $post['content'] : '',
		'post_status'   => 'publish',
		'post_type'     => 'post',
		'post_category' => $cat_ids,
	);
	if ( $existing ) {
		$postarr['ID'] = $existing->ID;
		wp_update_post( $postarr );
		$cli( 'Updated post: ' . $post['title'] );
	} else {
		wp_insert_post( $postarr );
		$cli( 'Created post: ' . $post['title'] );
	}
}

/* -------------------------------------------------------------------------
 * 9. Menus -> theme locations
 * ---------------------------------------------------------------------- */
$menus    = isset( $config['menus'] ) && is_array( $config['menus'] ) ? $config['menus'] : array();
$location_map = array(
	'primary' => 'primary',
	'footer'  => 'footer',
);
$nav_locations = get_theme_mod( 'nav_menu_locations', array() );

foreach ( $menus as $menu_key => $items ) {
	if ( ! is_array( $items ) ) {
		continue;
	}
	$menu_name = ucfirst( $menu_key ) . ' Menu';
	$menu      = wp_get_nav_menu_object( $menu_name );
	if ( ! $menu ) {
		$menu_id = wp_create_nav_menu( $menu_name );
	} else {
		$menu_id = $menu->term_id;
		// Clear existing items to avoid duplicates on re-run.
		foreach ( wp_get_nav_menu_items( $menu_id ) as $old_item ) {
			wp_delete_post( $old_item->ID, true );
		}
	}
	if ( is_wp_error( $menu_id ) ) {
		continue;
	}

	foreach ( $items as $item ) {
		$title = isset( $item['title'] ) ? $item['title'] : '';
		if ( ! empty( $item['page'] ) && isset( $slug_to_id[ $item['page'] ] ) ) {
			wp_update_nav_menu_item( $menu_id, 0, array(
				'menu-item-title'     => $title,
				'menu-item-object'    => 'page',
				'menu-item-object-id' => $slug_to_id[ $item['page'] ],
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
			) );
		} elseif ( ! empty( $item['url'] ) ) {
			wp_update_nav_menu_item( $menu_id, 0, array(
				'menu-item-title'  => $title,
				'menu-item-url'    => $item['url'],
				'menu-item-type'   => 'custom',
				'menu-item-status' => 'publish',
			) );
		}
	}

	if ( isset( $location_map[ $menu_key ] ) ) {
		$nav_locations[ $location_map[ $menu_key ] ] = $menu_id;
	}
	$cli( "Built menu: $menu_name" );
}
set_theme_mod( 'nav_menu_locations', $nav_locations );

$cli( 'Content provisioning complete.' );
