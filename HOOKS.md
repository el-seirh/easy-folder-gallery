# Extension hooks

Easy Folder Gallery exposes a small, stable extension API for add-on plugins.
Everything documented here is a compatibility promise; anything not listed is
internal and may change without notice.

## Stable identifiers

| Identifier | Value |
|---|---|
| Shortcode | `easy-folder-gallery` |
| Option name | `efg_settings` |
| Settings page slug | `easy-folder-gallery` (section `efg_main`) — add fields via `add_settings_field()` |
| Query vars | `efg_album`, `efg_gallery` |
| Text domain | `easy-folder-gallery` |

## The `$ctx` array

Most hooks receive the render context: `root` (absolute path of the gallery
root), `root_url`, `base_url` (permalink of the page), `title`, `back_text`,
`thumb_size`, `preview_count`, `columns`, `hide_title`, `album` and `gallery`
(the validated current folder names, `''` on higher-level views).

## Folder model filters

All folder/file names returned from these filters are re-validated by the
plugin (defense in depth): anything that could escape the gallery root is
silently dropped, and album/gallery names must be existing subfolders. So you
can safely reorder and remove; added folder entries must exist on disk.

```php
apply_filters( 'efg_albums', string[] $names, array $ctx )
```
Album folder names shown on the overview, alphabetically sorted.

```php
apply_filters( 'efg_galleries', string[] $names, string $album, array $ctx )
```
Gallery folder names of one album. Also applied to the per-album gallery
count on the overview, so hiding a gallery keeps the counts consistent —
which means it runs once per album there; keep listeners free of per-call
I/O (no queries or HTTP per invocation).

```php
apply_filters( 'efg_images', string[] $images, string $dir )
```
Image filenames of one gallery folder, alphabetically sorted. Applied
everywhere images are listed (photo grid, previews, thumbnail spread).
Only plain, non-hidden filenames survive (no path segments).

```php
apply_filters( 'efg_info_text', string $html, string $path )
```
The sanitized HTML of an `info.txt` / `info-long.txt`. Also fired when the
file does not exist (`$html` is `''` then), so descriptions can come from
other sources. Returned markup is printed as-is — sanitize what you add.

## Rendering filters

```php
apply_filters( 'efg_pre_render', null|string $html, string $view, array $ctx )
```
Short-circuit: return a non-null string to replace the whole view (core
`pre_*` pattern). `$view` is `'overview'`, `'album'` or `'gallery'`. Use for
access control — e.g. return a password form instead of a protected album.

```php
apply_filters( 'efg_card_html', string $html, string $type, string $name, array $ctx )
```
One album/gallery card (`$type` is `'album'` or `'gallery'`, `$name` the
folder name). Wrap or extend it — e.g. inject an edit button for admins.

```php
apply_filters( 'efg_view_html', string $html, string $view, array $ctx )
```
The final output of a view, before it is returned to WordPress.

```php
apply_filters( 'efg_context', array $ctx, array $atts )
```
The render context after shortcode attributes and navigation are resolved.
`album`/`gallery` are re-validated against the filesystem *after* this filter
runs — invalid replacements are cleared (falling back to the parent view),
and the rendered view is derived from the validated result. So you can
legitimately redirect navigation by setting them to existing folders, but
you cannot escape the gallery root.

WordPress core additionally provides `shortcode_atts_easy-folder-gallery`
(from `shortcode_atts()`) to filter the raw shortcode attributes.

## Thumbnail action

```php
do_action( 'efg_thumb_created', string $thumb_path, string $source_path, int $size )
```
Fires once after a thumbnail file was generated — post-process it in place
(watermark, rotation fix). Not fired for hand-picked `thumbs/preview.jpg`
or when generation fails.

## Settings filters

```php
apply_filters( 'efg_default_settings', array $defaults )
apply_filters( 'efg_sanitized_settings', array $clean, array $raw_input )
```
Register your own settings keys with defaults, and sanitize them on save.
Keys you add are stored in the same `efg_settings` option and come back from
`EFG_Settings::get()`. Input fields for them are your job — use
`add_settings_field()` with the page/section slugs listed above.

## Public helpers

```php
EFG_Renderer::gallery_root( ?string $dir = null ): string
```
Absolute path of the gallery root (`$dir` defaults to the configured
setting), sanitized exactly like the shortcode does it.

```php
EFG_Renderer::sanitize_folder( string $value ): string
EFG_Renderer::is_child_dir( string $parent, string $child ): bool
```
The renderer's own validation rules — use these for any folder name or path
you accept from users, so extension and core can never drift apart. Returns
`''` / `false` on anything unsafe. Note `is_child_dir()` is for
**directories only** — it returns `false` for file paths by design; validate
a filename separately (plain `basename()`-stable name, then check the file
inside a validated directory).

## Example: hide albums starting with an underscore

```php
add_filter( 'efg_albums', function ( $albums ) {
	return array_values( array_filter( $albums, function ( $name ) {
		return '_' !== $name[0];
	} ) );
} );
```
