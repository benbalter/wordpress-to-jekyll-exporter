WordPress to Jekyll Exporter
============================

One-click WordPress plugin that converts all posts, pages, taxonomies, metadata, and settings to Markdown and YAML which can be dropped into Jekyll.

Features
--------

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
2. Make sure `extension=zip.so` line is uncommented in your `php.ini`
3. Activate plugin in WordPress dashboard
4. Select `Export to Jekyll` from the `Tools` menu

Command-line Usage
------------------

If you're having trouble with your web server timing out before the export is complete, you may enjoy the command-line tool.
It works just like the plugin, but produces the zipfile on STDOUT:

    php jekyll-export-cli.php > jekyll-export.zip


Changelog
---------

### 1.3

* Use [fork of Markdownify](https://github.com/Pixel418/Markdownify) rather than external API to convert content from HTML to markdown

### 1.1

* Use WP_Filesystem for better compatability

### 1.0 

* Initial Release
