<?php
/**
 * Plugin settings: Settings → Easy Folder Gallery.
 *
 * @package easy-folder-gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EFG_Settings {

	const OPTION = 'efg_settings';

	public static function register() {
		add_action( 'admin_menu', array( __CLASS__, 'add_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	public static function defaults() {
		// The efg_default_settings hook is documented in HOOKS.md.
		return apply_filters(
			'efg_default_settings',
			array(
				'dir'           => 'easy-folder-gallery',
				'title'         => __( 'Galleries', 'easy-folder-gallery' ),
				/* translators: %s: title of the overview page or name of the album */
				'back_text'     => __( 'Back to %s', 'easy-folder-gallery' ),
				'thumb_size'    => 150,
				'preview_count' => 3,
				'columns'       => 2,
				'hide_title'    => 0,
			)
		);
	}

	/**
	 * Stored settings merged with the defaults.
	 */
	public static function get() {
		return wp_parse_args( (array) get_option( self::OPTION, array() ), self::defaults() );
	}

	public static function add_page() {
		add_options_page(
			__( 'Easy Folder Gallery', 'easy-folder-gallery' ),
			__( 'Easy Folder Gallery', 'easy-folder-gallery' ),
			'manage_options',
			'easy-folder-gallery',
			array( __CLASS__, 'render_page' )
		);
	}

	public static function register_settings() {
		register_setting(
			'efg_settings',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => array(),
			)
		);

		add_settings_section( 'efg_main', '', '__return_false', 'easy-folder-gallery' );

		add_settings_field(
			'efg_dir',
			__( 'Gallery folder', 'easy-folder-gallery' ),
			array( __CLASS__, 'field_dir' ),
			'easy-folder-gallery',
			'efg_main'
		);
		add_settings_field(
			'efg_title',
			__( 'Overview title', 'easy-folder-gallery' ),
			array( __CLASS__, 'field_title' ),
			'easy-folder-gallery',
			'efg_main'
		);
		add_settings_field(
			'efg_back_text',
			__( 'Back link text', 'easy-folder-gallery' ),
			array( __CLASS__, 'field_back_text' ),
			'easy-folder-gallery',
			'efg_main'
		);
		add_settings_field(
			'efg_thumb_size',
			__( 'Thumbnail size (px)', 'easy-folder-gallery' ),
			array( __CLASS__, 'field_thumb_size' ),
			'easy-folder-gallery',
			'efg_main'
		);
		add_settings_field(
			'efg_preview_count',
			__( 'Preview thumbs per album card', 'easy-folder-gallery' ),
			array( __CLASS__, 'field_preview_count' ),
			'easy-folder-gallery',
			'efg_main'
		);
		add_settings_field(
			'efg_columns',
			__( 'Grid columns', 'easy-folder-gallery' ),
			array( __CLASS__, 'field_columns' ),
			'easy-folder-gallery',
			'efg_main'
		);
	}

	public static function sanitize( $input ) {
		$input    = (array) $input;
		$defaults = self::defaults();
		$title    = trim( sanitize_text_field( $input['title'] ?? '' ) );
		$back     = trim( sanitize_text_field( $input['back_text'] ?? '' ) );
		$clean    = array(
			'dir'           => sanitize_text_field( $input['dir'] ?? $defaults['dir'] ),
			'title'         => '' !== $title ? $title : $defaults['title'],
			'back_text'     => '' !== $back ? $back : $defaults['back_text'],
			'thumb_size'    => max( 50, min( 1000, (int) ( $input['thumb_size'] ?? $defaults['thumb_size'] ) ) ),
			'preview_count' => max( 1, min( 10, (int) ( $input['preview_count'] ?? $defaults['preview_count'] ) ) ),
			'columns'       => max( 1, min( 6, (int) ( $input['columns'] ?? $defaults['columns'] ) ) ),
			'hide_title'    => empty( $input['hide_title'] ) ? 0 : 1,
		);
		return apply_filters( 'efg_sanitized_settings', $clean, $input );
	}

	public static function field_dir() {
		$settings = self::get();
		printf(
			'<code>wp-content/uploads/</code><input type="text" name="%s[dir]" value="%s" class="regular-text">',
			esc_attr( self::OPTION ),
			esc_attr( $settings['dir'] )
		);
		echo '<p class="description">' . esc_html__( 'The folder (below the uploads directory) that holds your albums. One subfolder per album; each album holds one folder of images per gallery.', 'easy-folder-gallery' ) . '</p>';
	}

	public static function field_title() {
		$settings = self::get();
		printf(
			'<input type="text" name="%s[title]" value="%s" class="regular-text" required>',
			esc_attr( self::OPTION ),
			esc_attr( $settings['title'] )
		);
		echo '<p class="description">' . esc_html__( 'Name of the gallery overview — shown as its heading and used in the "Back to …" links, so it cannot be empty.', 'easy-folder-gallery' ) . '</p>';

		$hide_id = self::OPTION . '-hide-title';
		printf(
			'<p><input type="checkbox" id="%1$s" name="%2$s[hide_title]" value="1"%3$s> <label for="%1$s">%4$s</label></p>',
			esc_attr( $hide_id ),
			esc_attr( self::OPTION ),
			checked( $settings['hide_title'], 1, false ),
			esc_html__( 'Hide the heading on the overview page (the title is still used in the back links).', 'easy-folder-gallery' )
		);
	}

	public static function field_back_text() {
		$settings = self::get();
		printf(
			'<input type="text" name="%s[back_text]" value="%s" class="regular-text">',
			esc_attr( self::OPTION ),
			esc_attr( $settings['back_text'] )
		);
		echo '<p class="description">' . esc_html__( 'Text of the "back" links on album and gallery pages. %s is replaced by the overview title or the album name (without %s, the name is appended). Leave empty to reset to the default.', 'easy-folder-gallery' ) . '</p>';
	}

	public static function field_thumb_size() {
		$settings = self::get();
		printf(
			'<input type="number" min="50" max="1000" name="%s[thumb_size]" value="%d">',
			esc_attr( self::OPTION ),
			(int) $settings['thumb_size']
		);
		echo '<p class="description">' . esc_html__( 'Width and height of the square thumbnails in pixels. Already generated thumbnails are not resized — delete a gallery\'s thumbs folder to regenerate them.', 'easy-folder-gallery' ) . '</p>';
	}

	public static function field_preview_count() {
		$settings = self::get();
		printf(
			'<input type="number" min="1" max="10" name="%s[preview_count]" value="%d">',
			esc_attr( self::OPTION ),
			(int) $settings['preview_count']
		);
		echo '<p class="description">' . esc_html__( 'How many preview thumbnails each album card shows on the overview page.', 'easy-folder-gallery' ) . '</p>';
	}

	public static function field_columns() {
		$settings = self::get();
		printf(
			'<input type="number" min="1" max="6" name="%s[columns]" value="%d">',
			esc_attr( self::OPTION ),
			(int) $settings['columns']
		);
		echo '<p class="description">' . esc_html__( 'Number of album/gallery cards per row (on small screens the grid always collapses to one column).', 'easy-folder-gallery' ) . '</p>';
	}

	public static function render_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Easy Folder Gallery', 'easy-folder-gallery' ); ?></h1>
			<p>
				<?php
				printf(
					/* translators: %s: the shortcode */
					esc_html__( 'Put the shortcode %s on any page to show the gallery there.', 'easy-folder-gallery' ),
					'<code>[easy-folder-gallery]</code>'
				);
				?>
			</p>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'efg_settings' );
				do_settings_sections( 'easy-folder-gallery' );
				submit_button();
				?>
			</form>
			<p class="description">
				<span class="dashicons dashicons-coffee" aria-hidden="true"></span>
				<?php
				printf(
					/* translators: %s: donation link */
					esc_html__( 'This plugin runs on folders and coffee. The folders are yours — %s', 'easy-folder-gallery' ),
					'<a href="https://paypal.me/lubamclean" target="_blank" rel="noopener">' .
						esc_html__( 'wanna buy the coffee? ☕', 'easy-folder-gallery' ) . '</a>'
				);
				?>
			</p>
		</div>
		<?php
	}
}
