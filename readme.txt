=== Jekyll Exporter ===
Contributors: benbalter
Donate link: http://ben.balter.com/donate
Tags: jekyll, github, github pages, yaml, export
Requires at least: 3.0
Tested up to: 4.0.1
Stable tag: 2.0.1
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

One-click WordPress plugin that converts all posts, pages, taxonomies, metadata, and settings to Markdown and YAML which can be dropped into Jekyll.

== Description ==

* Converts all posts, pages, and settings from WordPress for use in Jekyll
* Export what your users see, not what the database stores (runs post content through `the_content` filter prior to export, allowing third-party plugins to modify the output)
* Converts all `post_content` to Markdown Extra (using Markdownify)
* Converts all `post_meta` and fields within the `wp_posts` table to YAML front matter for parsing by Jekyll
* Generates a `_config.yml` with all settings in the `wp_options` table
* Outputs a single zip file with `_config.yml`, pages, and `_posts` folder containing `.md` files for each post in the proper Jekyll naming convention
* No settings. Just a single click.

Usage
-----

1. Place plugin in `/wp-content/plugins/` folder
2. Activate plugin in WordPress dashboard
3. Select `Export to Jekyll` from the `Tools` menu

See https://github.com/benbalter/wordpress-to-jekyll-exporter for more information.
