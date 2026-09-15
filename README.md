# Easy Folder Gallery

A file-based photo gallery plugin for WordPress: **your folders are your albums and galleries.**
No database entries, no upload UI — you manage your photos by uploading folders of images
(via FTP, SSH, or your hoster's file manager), and the plugin renders them as a browsable
gallery with albums, thumbnails and a lightbox.

## Why folder-based?

- Your photo collection stays portable: it's just files, organized the way you already organize photos.
- Nothing to migrate, nothing to break: deactivate the plugin and your images are still there.
- Bulk-friendly: drop 500 photos into a folder via FTP instead of clicking through an upload UI.

## Features

- **Albums → galleries → photos** from a plain folder structure
- **Automatic square thumbnails**, generated lazily on first view (GD or Imagick via the WordPress image editor) and cached on disk
- **Optional descriptions** from plain text files — `info.txt` (card teaser) and `info-long.txt` (gallery intro), simple HTML allowed
- **Smart album previews**: up to 3 deterministic preview thumbs per album card; hand-pick one with `thumbs/preview.jpg`
- **Built-in dependency-free lightbox** with keyboard navigation
- **Theme-neutral styling**, overridable via CSS custom properties (`--efg-accent`, `--efg-thumb-size`, …)
- Hardened against path traversal; all output escaped

## Usage

1. Install and activate the plugin.
2. Create the gallery root and fill it:

   ```
   wp-content/uploads/easy-folder-gallery/
   ├── Holidays/                ← album
   │   ├── info.txt             ← optional album description
   │   ├── Italy 2024/          ← gallery
   │   │   ├── info.txt         ← optional teaser (shown on the album page)
   │   │   ├── info-long.txt    ← optional intro (shown above the photos)
   │   │   ├── IMG_001.jpg
   │   │   └── thumbs/          ← auto-generated; add preview.jpg to hand-pick a preview
   │   └── Norway 2025/
   └── Family/
   ```

3. Put the shortcode on any page:

   ```
   [easy-folder-gallery]
   ```

   All attributes are optional and default to the plugin settings
   (Settings → Easy Folder Gallery):

   ```
   [easy-folder-gallery dir="my-photos" title="Photos" thumb_size="200" preview_count="3"]
   ```

## Requirements

- WordPress 6.0+, PHP 8.0+
- GD or Imagick PHP extension (for thumbnail generation)
- The web server needs write access to the gallery folders (to create `thumbs/`)

## Development

The plugin is plain PHP, CSS and vanilla JS — no build step. Clone into
`wp-content/plugins/easy-folder-gallery` and activate.

Run the smoke test (no WordPress needed): `php tests/smoke-test.php`

Releases are built by the GitHub Actions workflow on version tags (`v*`), producing an
installable ZIP.

## License

GPL v2 or later — see [LICENSE](LICENSE).
