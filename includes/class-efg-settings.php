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
		return array(
			'dir'           => 'easy-folder-gallery',
			'title'         => __( 'Galleries', 'easy-folder-gallery' ),
			'thumb_size'    => 150,
			'preview_count' => 3,
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
	}

	public static function sanitize( $input ) {
		$input    = (array) $input;
		$defaults = self::defaults();
		return array(
			'dir'           => sanitize_text_field( $input['dir'] ?? $defaults['dir'] ),
			'title'         => sanitize_text_field( $input['title'] ?? $defaults['title'] ),
			'thumb_size'    => max( 50, min( 1000, (int) ( $input['thumb_size'] ?? $defaults['thumb_size'] ) ) ),
			'preview_count' => max( 1, min( 10, (int) ( $input['preview_count'] ?? $defaults['preview_count'] ) ) ),
		);
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
			'<input type="text" name="%s[title]" value="%s" class="regular-text">',
			esc_attr( self::OPTION ),
			esc_attr( $settings['title'] )
		);
		echo '<p class="description">' . esc_html__( 'Heading shown on the gallery overview page.', 'easy-folder-gallery' ) . '</p>';
	}

	public static function field_thumb_size() {
		$settings = self::get();
		printf(
			'<input type="number" min="50" max="1000" name="%s[thumb_size]" value="%d">',
			esc_attr( self::OPTION ),
			(int) $settings['thumb_size']
		);
	}

	public static function field_preview_count() {
		$settings = self::get();
		printf(
			'<input type="number" min="1" max="10" name="%s[preview_count]" value="%d">',
			esc_attr( self::OPTION ),
			(int) $settings['preview_count']
		);
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
		</div>
		<?php
	}
}
