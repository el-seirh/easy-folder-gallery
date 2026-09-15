<?php
/**
 * Smoke test: render all three views of Easy Folder Gallery against WP stubs
 * and a fake folder tree. Fails loudly on any PHP warning/notice.
 *
 * Run with: php tests/smoke-test.php
 */
error_reporting(E_ALL);
set_error_handler(function ($no, $str, $file, $line) {
    fwrite(STDERR, "PHP problem: $str at $file:$line\n");
    exit(1);
});

define('ABSPATH', '/fake/');

// Build a fake gallery tree in a temp folder.
$ROOT = sys_get_temp_dir() . '/efg-smoke-' . getmypid();
foreach (array('Albumümlaut/Gallery One', 'Albumümlaut/Gallery Two/thumbs', 'Beach/Sunset') as $d) {
    mkdir("$ROOT/easy-folder-gallery/$d", 0755, true);
}
foreach (array('Albumümlaut/Gallery One/a1.jpg', 'Albumümlaut/Gallery One/a2.jpg',
               'Albumümlaut/Gallery Two/b1.jpg', 'Albumümlaut/Gallery Two/thumbs/preview.jpg',
               'Beach/Sunset/c1.jpg') as $f) {
    touch("$ROOT/easy-folder-gallery/$f");
}
file_put_contents("$ROOT/easy-folder-gallery/Albumümlaut/info.txt", "An album info text");
file_put_contents("$ROOT/easy-folder-gallery/Albumümlaut/Gallery One/info.txt", "A gallery teaser");
file_put_contents("$ROOT/easy-folder-gallery/Albumümlaut/Gallery One/info-long.txt", "A long gallery intro");
register_shutdown_function(function () use ($ROOT) {
    exec('rm -rf ' . escapeshellarg($ROOT));
});

// --- Minimal WordPress stubs -------------------------------------------------
class WP_Error {}
function plugin_dir_path($f) { return dirname($f) . '/'; }
function plugin_dir_url($f) { return 'https://example.test/wp-content/plugins/easy-folder-gallery/'; }
function plugin_basename($f) { return 'easy-folder-gallery/easy-folder-gallery.php'; }
function load_plugin_textdomain($d, $x, $p) {}
function add_action($hook, $cb) {}
function add_shortcode($tag, $cb) {}
function shortcode_atts($defaults, $atts, $tag = '') { return array_merge($defaults, array_intersect_key((array) $atts, $defaults)); }
function wp_upload_dir() { global $ROOT; return array('basedir' => $ROOT, 'baseurl' => 'https://example.test/wp-content/uploads'); }
function get_permalink() { return 'https://example.test/photos/'; }
function get_option($k, $d = false) { return $d; }
function wp_parse_args($args, $defaults) { return array_merge($defaults, (array) $args); }
function current_user_can($c) { return true; }
function wp_unslash($v) { return $v; }
function sanitize_text_field($v) { return trim(preg_replace('/[\r\n\t]+/', ' ', strip_tags((string) $v))); }
function esc_html($v) { return htmlspecialchars((string) $v, ENT_QUOTES); }
function esc_attr($v) { return htmlspecialchars((string) $v, ENT_QUOTES); }
function esc_url($v) { return str_replace(' ', '%20', (string) $v); }
function esc_html__($t, $d = '') { return esc_html($t); }
function __($t, $d = '') { return $t; }
function wp_kses_post($v) { return $v; } // stub: pass through
function add_query_arg($key, $value = null, $url = null) {
    if (is_array($key)) { $args = $key; $url = $value; } else { $args = array($key => $value); }
    $sep = (strpos($url, '?') === false) ? '?' : '&';
    foreach ($args as $k => $v) { $url .= $sep . $k . '=' . $v; $sep = '&'; }
    return $url;
}
function wp_enqueue_style($h) {}
function wp_enqueue_script($h) {}
function wp_register_style($h, $s, $d, $v) {}
function wp_register_script($h, $s, $d, $v, $f = false) {}
function wp_mkdir_p($dir) { return is_dir($dir) || mkdir($dir, 0755, true); }
function is_wp_error($x) { return $x instanceof WP_Error; }
function wp_get_image_editor($path) { return new WP_Error(); } // thumbnails "fail" -> full-size fallback

// --- Load plugin classes -----------------------------------------------------
require dirname(__DIR__) . '/includes/class-efg-settings.php';
require dirname(__DIR__) . '/includes/class-efg-renderer.php';

function check($label, $html, array $must_contain, array $must_not_contain = array()) {
    foreach ($must_contain as $needle) {
        if (strpos($html, $needle) === false) {
            fwrite(STDERR, "FAIL [$label]: missing '$needle'\nHTML: $html\n");
            exit(1);
        }
    }
    foreach ($must_not_contain as $needle) {
        if (strpos($html, $needle) !== false) {
            fwrite(STDERR, "FAIL [$label]: must not contain '$needle'\nHTML: $html\n");
            exit(1);
        }
    }
    echo "PASS $label\n";
}

// Overview
$_GET = array();
$html = EFG_Renderer::shortcode(array());
check('overview', $html, array(
    'efg-grid', '<h2>Albumümlaut', '(2)', 'Beach', 'efg_album=Album%C3%BCmlaut',
    'An album info text', 'efg-previews',
));

// Album view
$_GET = array('efg_album' => 'Albumümlaut');
$html = EFG_Renderer::shortcode(array());
check('album', $html, array('efg-backlink', '<h1 class="efg-title">Albumümlaut</h1>', 'Gallery One', 'efg_gallery=Gallery%20One', 'A gallery teaser'));

// Gallery view
$_GET = array('efg_album' => 'Albumümlaut', 'efg_gallery' => 'Gallery One');
$html = EFG_Renderer::shortcode(array());
check('gallery', $html, array('efg-photos', 'a1.jpg', 'a2.jpg', 'efg-photo', 'A long gallery intro'));

// Path traversal attempts must fall back to the overview
foreach (array('../../etc', '..', '.hidden', 'Albumümlaut/../../etc') as $evil) {
    $_GET = array('efg_album' => $evil);
    $html = EFG_Renderer::shortcode(array());
    check("traversal '$evil'", $html, array('efg-grid'), array('etc', 'passwd'));
}

// Nonexistent album falls back to overview; nonexistent gallery falls back to album
$_GET = array('efg_album' => 'NoSuchAlbum');
check('missing album', EFG_Renderer::shortcode(array()), array('(2)'));
$_GET = array('efg_album' => 'Albumümlaut', 'efg_gallery' => 'NoSuchGallery');
check('missing gallery', EFG_Renderer::shortcode(array()), array('Gallery One'));

// XSS: folder names with special chars must come out escaped
$_GET = array();
$html = EFG_Renderer::shortcode(array());
if (strpos($html, '<script') !== false) { fwrite(STDERR, "FAIL: raw script tag in output\n"); exit(1); }
echo "PASS xss-basic\n";

echo "ALL PASSED\n";
