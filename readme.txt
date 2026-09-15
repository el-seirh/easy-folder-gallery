=== Easy Folder Gallery ===
Contributors: lubamclean
Tags: gallery, albums, folder, photos, lightbox
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A file-based photo gallery: your folders are your albums and galleries. No database entries, no upload UI — just folders of images.

== Description ==

Easy Folder Gallery turns a simple folder structure into a browsable photo gallery with albums, galleries and a lightbox. You manage everything by uploading folders of images (via FTP, SSH, your hoster's file manager, …) — the plugin stores nothing in the database.

* **Albums and galleries from folders** — one folder per album, one subfolder per gallery.
* **Automatic square thumbnails**, generated lazily on first view and cached on disk.
* **Optional descriptions** via plain text files (`info.txt`, `info-long.txt`), simple HTML allowed.
* **Smart album previews** — up to 3 thumbnails per album card, picked deterministically; hand-pick one by adding a `thumbs/preview.jpg`.
* **Built-in lightbox** — dependency-free, with keyboard navigation.
* **Theme-neutral styling** you can override with CSS custom properties.

= Folder structure =

    wp-content/uploads/easy-folder-gallery/
    ├── Holidays/                ← album
    │   ├── info.txt             ← optional album description
    │   ├── Italy 2024/          ← gallery
    │   │   ├── info.txt         ← optional teaser (album page)
    │   │   ├── info-long.txt    ← optional intro (gallery page)
    │   │   ├── IMG_001.jpg
    │   │   └── thumbs/          ← auto-generated; add preview.jpg to hand-pick a preview
    │   └── Norway 2025/
    └── Family/

== Installation ==

1. Install the plugin via *Plugins → Add New* (search for "Easy Folder Gallery"), or upload the ZIP under *Plugins → Add New → Upload Plugin*, then click *Activate*.
2. Create the folder `wp-content/uploads/easy-folder-gallery/` and fill it with album folders containing gallery folders of images.
3. Put the shortcode `[easy-folder-gallery]` on a page.
4. Optionally adjust folder, title, thumbnail size and preview count under Settings → Easy Folder Gallery, or per shortcode: `[easy-folder-gallery dir="my-photos" title="Photos" thumb_size="200" preview_count="3"]`.

== Frequently Asked Questions ==

= Where do the images live? =

In a folder below `wp-content/uploads/` (default: `easy-folder-gallery`). The plugin never touches the WordPress media library or the database.

= How do I add descriptions? =

Put a UTF-8 `info.txt` into an album or gallery folder (shown on its card) and/or an `info-long.txt` into a gallery folder (shown above the photos). Simple HTML is allowed.

= How do I choose an album/gallery preview image? =

Put a square `preview.jpg` into the gallery's `thumbs/` folder. Without one, the first image is used; album cards fill remaining slots deterministically (first, middle, last image).

= A thumbnail is wrong or outdated =

Delete it from the gallery's `thumbs/` folder and reload the page — it is regenerated.

== Changelog ==

= 0.1.0 =
* Initial release: folder-based albums/galleries, lazy thumbnails, info texts, album preview thumbs, built-in lightbox, settings page.
