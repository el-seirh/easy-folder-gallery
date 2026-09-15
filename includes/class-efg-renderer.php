<?php
/**
 * Renders the three gallery views: overview (albums), album (galleries) and gallery (photos).
 *
 * @package easy-folder-gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EFG_Renderer {

	const THUMB_DIR      = 'thumbs';
	const INFO_FILE      = 'info.txt';
	const INFO_LONG_FILE = 'info-long.txt';
	const PREVIEW_FILE   = 'preview.jpg';
	const QUERY_ALBUM    = 'efg_album';
	const QUERY_GALLERY  = 'efg_gallery';

	public static function register() {
		add_shortcode( 'easy-folder-gallery', array( __CLASS__, 'shortcode' ) );
		add_action( 'init', array( __CLASS__, 'register_assets' ) );
	}

	public static function register_assets() {
		wp_register_style( 'easy-folder-gallery', EFG_PLUGIN_URL . 'assets/css/easy-folder-gallery.css', array(), EFG_VERSION );
		wp_register_script( 'easy-folder-gallery', EFG_PLUGIN_URL . 'assets/js/easy-folder-gallery.js', array(), EFG_VERSION, true );
	}

	/**
	 * [easy-folder-gallery] — all attributes are optional and default to the plugin settings:
	 * dir (folder below wp-content/uploads), title, thumb_size, preview_count, columns.
	 */
	public static function shortcode( $atts ) {
		$settings = EFG_Settings::get();
		$atts     = shortcode_atts(
			array(
				'dir'           => $settings['dir'],
				'title'         => $settings['title'],
				'thumb_size'    => $settings['thumb_size'],
				'preview_count' => $settings['preview_count'],
				'columns'       => $settings['columns'],
				'hide_title'    => $settings['hide_title'],
				'back_text'     => $settings['back_text'],
			),
			$atts,
			'easy-folder-gallery'
		);

		$uploads = wp_upload_dir();
		$dir     = self::sanitize_relative_dir( $atts['dir'] );

		// The title is mandatory — it names the overview in the "Back to …" links.
		$defaults = EFG_Settings::defaults();
		$title    = trim( (string) $atts['title'] );
		if ( '' === $title ) {
			$title = $defaults['title'];
		}
		$back_text = trim( (string) $atts['back_text'] );
		if ( '' === $back_text ) {
			$back_text = $defaults['back_text'];
		}

		$ctx = array(
			'root'          => $uploads['basedir'] . '/' . $dir,
			'root_url'      => $uploads['baseurl'] . '/' . $dir,
			'base_url'      => get_permalink(),
			'title'         => $title,
			'back_text'     => $back_text,
			'thumb_size'    => max( 50, (int) $atts['thumb_size'] ),
			'preview_count' => max( 1, (int) $atts['preview_count'] ),
			'columns'       => max( 1, min( 6, (int) $atts['columns'] ) ),
			'hide_title'    => filter_var( $atts['hide_title'], FILTER_VALIDATE_BOOLEAN ),
		);

		if ( ! is_dir( $ctx['root'] ) ) {
			if ( current_user_can( 'manage_options' ) ) {
				return '<p>' . sprintf(
					/* translators: %s: folder path inside wp-content/uploads */
					esc_html__( 'Easy Folder Gallery: the folder %s does not exist yet. Create it and add one folder per album, each containing one folder of images per gallery.', 'easy-folder-gallery' ),
					'<code>' . esc_html( 'wp-content/uploads/' . $dir ) . '</code>'
				) . '</p>';
			}
			return '';
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only navigation parameters.
		$album   = isset( $_GET[ self::QUERY_ALBUM ] ) ? self::sanitize_folder( wp_unslash( $_GET[ self::QUERY_ALBUM ] ) ) : '';
		$gallery = isset( $_GET[ self::QUERY_GALLERY ] ) ? self::sanitize_folder( wp_unslash( $_GET[ self::QUERY_GALLERY ] ) ) : '';
		// phpcs:enable

		// Only accept folders that really exist below the gallery root.
		if ( '' !== $album && ! self::is_child_dir( $ctx['root'], $ctx['root'] . '/' . $album ) ) {
			$album = '';
		}
		if ( '' === $album || ( '' !== $gallery && ! self::is_child_dir( $ctx['root'] . '/' . $album, $ctx['root'] . '/' . $album . '/' . $gallery ) ) ) {
			$gallery = '';
		}
		$ctx['album']   = $album;
		$ctx['gallery'] = $gallery;

		wp_enqueue_style( 'easy-folder-gallery' );

		if ( '' !== $gallery ) {
			wp_enqueue_script( 'easy-folder-gallery' );
			return self::render_gallery( $ctx );
		}
		if ( '' !== $album ) {
			return self::render_album( $ctx );
		}
		return self::render_overview( $ctx );
	}

	/**
	 * Top level: one card per album with gallery count, preview thumbs and optional info text.
	 */
	private static function render_overview( $ctx ) {
		$out = self::open_wrapper( $ctx );
		if ( ! $ctx['hide_title'] ) {
			$out .= '<h1 class="efg-title">' . esc_html( $ctx['title'] ) . '</h1>';
		}
		$out .= '<div class="efg-grid">';

		foreach ( self::subdirs( $ctx['root'] ) as $album ) {
			$album_path = $ctx['root'] . '/' . $album;
			$galleries  = self::subdirs( $album_path );
			$url        = add_query_arg( self::QUERY_ALBUM, rawurlencode( $album ), $ctx['base_url'] );

			$out .= '<a class="efg-card" href="' . esc_url( $url ) . '">';
			$out .= '<h2>' . esc_html( $album ) . ' <span class="efg-count">(' . count( $galleries ) . ')</span></h2>';

			$previews = self::preview_thumbs( $album_path, $galleries, $ctx['preview_count'], $ctx['thumb_size'] );
			if ( $previews ) {
				$out .= '<div class="efg-previews">';
				foreach ( $previews as $relative_path ) {
					$out .= sprintf(
						'<img src="%s" width="%d" height="%d" alt="" loading="lazy">',
						esc_url( $ctx['root_url'] . '/' . $album . '/' . $relative_path ),
						$ctx['thumb_size'],
						$ctx['thumb_size']
					);
				}
				$out .= '</div>';
			}

			$info = self::info_text( $album_path . '/' . self::INFO_FILE );
			if ( '' !== $info ) {
				$out .= '<div class="efg-info">' . $info . '</div>';
			}
			$out .= '</a>';
		}

		$out .= '</div></div>';
		return $out;
	}

	/**
	 * Album level: one card per gallery with its preview thumb and optional teaser text.
	 */
	private static function render_album( $ctx ) {
		$album_path = $ctx['root'] . '/' . $ctx['album'];

		$out  = self::open_wrapper( $ctx );
		$out .= '<p class="efg-backlink"><a href="' . esc_url( $ctx['base_url'] ) . '">&larr; ' .
			esc_html( self::back_label( $ctx, $ctx['title'] ) ) . '</a></p>';
		$out .= '<h1 class="efg-title">' . esc_html( $ctx['album'] ) . '</h1>';

		$info = self::info_text( $album_path . '/' . self::INFO_FILE );
		if ( '' !== $info ) {
			$out .= '<div class="efg-info-long">' . $info . '</div>';
		}

		$out .= '<div class="efg-grid">';
		foreach ( self::subdirs( $album_path ) as $gallery ) {
			$gallery_path = $album_path . '/' . $gallery;
			$url          = add_query_arg(
				array(
					self::QUERY_ALBUM   => rawurlencode( $ctx['album'] ),
					self::QUERY_GALLERY => rawurlencode( $gallery ),
				),
				$ctx['base_url']
			);

			$out .= '<a class="efg-card" href="' . esc_url( $url ) . '">';
			$out .= '<h2>' . esc_html( $gallery ) . '</h2>';

			$preview = self::gallery_preview_image( $gallery_path );
			if ( '' !== $preview ) {
				$relative_path = self::thumb_relative_path( $gallery_path, $preview, $ctx['thumb_size'] );
				$out          .= '<div class="efg-preview">' . sprintf(
					'<img src="%s" width="%d" height="%d" alt="" loading="lazy">',
					esc_url( $ctx['root_url'] . '/' . $ctx['album'] . '/' . $gallery . '/' . $relative_path ),
					$ctx['thumb_size'],
					$ctx['thumb_size']
				) . '</div>';
			}

			$info = self::info_text( $gallery_path . '/' . self::INFO_FILE );
			if ( '' !== $info ) {
				$out .= '<div class="efg-info">' . $info . '</div>';
			}
			$out .= '</a>';
		}
		$out .= '</div></div>';
		return $out;
	}

	/**
	 * Gallery level: optional intro text and all photos as lightbox-enabled thumbnails.
	 */
	private static function render_gallery( $ctx ) {
		$gallery_path = $ctx['root'] . '/' . $ctx['album'] . '/' . $ctx['gallery'];
		$gallery_url  = $ctx['root_url'] . '/' . $ctx['album'] . '/' . $ctx['gallery'];
		$album_url    = add_query_arg( self::QUERY_ALBUM, rawurlencode( $ctx['album'] ), $ctx['base_url'] );

		$backlink = '<p class="efg-backlink"><a href="' . esc_url( $album_url ) . '">&larr; ' .
			esc_html( self::back_label( $ctx, $ctx['album'] ) ) . '</a></p>';

		$out  = '<div class="efg">' . $backlink;
		$out .= '<h1 class="efg-title">' . esc_html( $ctx['gallery'] ) . '</h1>';

		$info_long = self::info_text( $gallery_path . '/' . self::INFO_LONG_FILE );
		if ( '' !== $info_long ) {
			$out .= '<div class="efg-info-long">' . $info_long . '</div>';
		}

		$out .= '<div class="efg-photos">';
		foreach ( self::image_list( $gallery_path ) as $image ) {
			$relative_path = self::thumb_relative_path( $gallery_path, $image, $ctx['thumb_size'] );
			$out          .= '<a class="efg-photo" href="' . esc_url( $gallery_url . '/' . $image ) . '">';
			$out          .= sprintf(
				'<img src="%s" width="%d" height="%d" alt="%s" loading="lazy">',
				esc_url( $gallery_url . '/' . $relative_path ),
				$ctx['thumb_size'],
				$ctx['thumb_size'],
				esc_attr( $image )
			);
			$out .= '</a>';
		}
		$out .= '</div>' . $backlink . '</div>';
		return $out;
	}

	/**
	 * The back-link label: the configured text with %s replaced by the target
	 * name; without a %s the name is simply appended.
	 */
	private static function back_label( $ctx, $target ) {
		if ( false !== strpos( $ctx['back_text'], '%s' ) ) {
			return str_replace( '%s', $target, $ctx['back_text'] );
		}
		return $ctx['back_text'] . ' ' . $target;
	}

	/**
	 * The common wrapper element; the column count feeds the grid via a CSS custom property.
	 */
	private static function open_wrapper( $ctx ) {
		return '<div class="efg" style="--efg-columns:' . (int) $ctx['columns'] . '">';
	}

	/**
	 * Pick up to $count preview thumbs for an album card, deterministically:
	 * - enough galleries: one preview per gallery (the last ones, alphabetically),
	 *   gaps are filled from the last gallery.
	 * - fewer galleries: hand-picked previews keep their slot, the rest is
	 *   spread evenly over all images (first, middle, last).
	 *
	 * @return string[] Paths relative to the album folder.
	 */
	private static function preview_thumbs( $album_path, $galleries, $count, $size ) {
		if ( ! $galleries ) {
			return array();
		}

		$chosen         = array(); // "gallery/image" keys avoid duplicates.
		$last_galleries = array_slice( $galleries, -$count );

		if ( count( $galleries ) >= $count ) {
			foreach ( $last_galleries as $gallery ) {
				$image = self::gallery_preview_image( $album_path . '/' . $gallery );
				if ( '' !== $image ) {
					$chosen[ $gallery . '/' . $image ] = array( $gallery, $image );
				}
			}
			$fill_galleries = array( end( $last_galleries ) );
		} else {
			foreach ( $last_galleries as $gallery ) {
				if ( is_file( $album_path . '/' . $gallery . '/' . self::THUMB_DIR . '/' . self::PREVIEW_FILE ) ) {
					$chosen[ $gallery . '/' . self::PREVIEW_FILE ] = array( $gallery, self::PREVIEW_FILE );
				}
			}
			$fill_galleries = $last_galleries;
		}

		// Fill the remaining slots with images spread evenly over the pool.
		$pool = array();
		foreach ( $fill_galleries as $gallery ) {
			foreach ( self::image_list( $album_path . '/' . $gallery ) as $image ) {
				if ( ! isset( $chosen[ $gallery . '/' . $image ] ) ) {
					$pool[] = array( $gallery, $image );
				}
			}
		}
		$missing = $count - count( $chosen );
		if ( $missing > 0 && $pool ) {
			$last_index = count( $pool ) - 1;
			for ( $i = 0; $i < $missing; $i++ ) {
				$pick                    = ( $missing > 1 ) ? (int) round( $i * $last_index / ( $missing - 1 ) ) : (int) round( $last_index / 2 );
				list( $gallery, $image ) = $pool[ $pick ];

				$chosen[ $gallery . '/' . $image ] = array( $gallery, $image );
			}
		}

		$relative_paths = array();
		foreach ( array_slice( $chosen, 0, $count ) as $item ) {
			list( $gallery, $image ) = $item;

			$relative_paths[] = $gallery . '/' . self::thumb_relative_path( $album_path . '/' . $gallery, $image, $size );
		}
		return $relative_paths;
	}

	/**
	 * A gallery's preview image: the hand-picked thumbs/preview.jpg if present,
	 * otherwise the first image.
	 */
	private static function gallery_preview_image( $gallery_path ) {
		if ( is_file( $gallery_path . '/' . self::THUMB_DIR . '/' . self::PREVIEW_FILE ) ) {
			return self::PREVIEW_FILE;
		}
		$images = self::image_list( $gallery_path );
		return $images ? $images[0] : '';
	}

	/**
	 * Make sure a thumbnail exists and return its path relative to the gallery
	 * folder. Falls back to the full-size image if the thumbnail can't be made.
	 */
	private static function thumb_relative_path( $gallery_path, $image, $size ) {
		if ( self::PREVIEW_FILE === $image && is_file( $gallery_path . '/' . self::THUMB_DIR . '/' . self::PREVIEW_FILE ) ) {
			return self::THUMB_DIR . '/' . self::PREVIEW_FILE;
		}
		if ( self::ensure_thumb( $gallery_path, $image, $size ) ) {
			return self::THUMB_DIR . '/' . $image;
		}
		return $image;
	}

	/**
	 * Lazily create a square thumbnail via the WordPress image editor (GD or Imagick).
	 */
	private static function ensure_thumb( $gallery_path, $image, $size ) {
		$thumb_dir = $gallery_path . '/' . self::THUMB_DIR;
		$thumb     = $thumb_dir . '/' . $image;
		if ( is_file( $thumb ) ) {
			return true;
		}
		if ( ! is_dir( $thumb_dir ) && ! wp_mkdir_p( $thumb_dir ) ) {
			return false;
		}
		$editor = wp_get_image_editor( $gallery_path . '/' . $image );
		if ( is_wp_error( $editor ) ) {
			return false;
		}
		$editor->resize( $size, $size, true );
		return ! is_wp_error( $editor->save( $thumb ) );
	}

	/**
	 * Visible subfolders of a folder, sorted alphabetically.
	 */
	private static function subdirs( $dir ) {
		$names = array();
		$paths = glob( $dir . '/*', GLOB_ONLYDIR );
		foreach ( $paths ? $paths : array() as $path ) {
			$name = basename( $path );
			if ( '.' !== $name[0] && self::THUMB_DIR !== $name ) {
				$names[] = $name;
			}
		}
		sort( $names, SORT_STRING );
		return $names;
	}

	/**
	 * Image filenames in a folder, sorted alphabetically.
	 */
	private static function image_list( $dir ) {
		$images = array();
		$paths  = glob( $dir . '/*.{jpg,JPG,jpeg,JPEG,png,PNG,gif,GIF,webp,WEBP}', GLOB_BRACE );
		foreach ( $paths ? $paths : array() as $file ) {
			$images[] = basename( $file );
		}
		sort( $images, SORT_STRING );
		return $images;
	}

	/**
	 * Read an info file; simple HTML (as in post content) is allowed.
	 */
	private static function info_text( $path ) {
		if ( ! is_file( $path ) ) {
			return '';
		}
		return wp_kses_post( trim( (string) file_get_contents( $path ) ) );
	}

	/**
	 * A folder name from user input: no paths, no hidden folders.
	 */
	private static function sanitize_folder( $value ) {
		$value = basename( trim( sanitize_text_field( (string) $value ) ) );
		if ( '' === $value || '.' === $value[0] ) {
			return '';
		}
		return $value;
	}

	/**
	 * A relative folder path from the settings: no empty, hidden or dot-dot segments.
	 */
	private static function sanitize_relative_dir( $dir ) {
		$parts = array();
		foreach ( explode( '/', (string) $dir ) as $part ) {
			$part = trim( $part );
			if ( '' !== $part && '.' !== $part[0] ) {
				$parts[] = $part;
			}
		}
		return $parts ? implode( '/', $parts ) : 'easy-folder-gallery';
	}

	/**
	 * True if $child resolves to a real directory below $parent (blocks path traversal).
	 */
	private static function is_child_dir( $parent, $child ) {
		$parent_real = realpath( $parent );
		$child_real  = realpath( $child );
		return false !== $parent_real && false !== $child_real && is_dir( $child_real )
			&& 0 === strpos( $child_real, $parent_real . DIRECTORY_SEPARATOR );
	}
}
