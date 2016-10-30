# WordPress to Jekyll Exporter

One-click WordPress plugin that converts all posts, pages, taxonomies, metadata, and settings to Markdown and YAML which can be dropped into Jekyll.

[![Build Status](https://travis-ci.org/benbalter/wordpress-to-jekyll-exporter.svg?branch=master)](https://travis-ci.org/benbalter/wordpress-to-jekyll-exporter)

## A Note

Many shared hosts may use an outdated version of PHP by default. **WordPress to Jekyll Export requires PHP 5.5 or greater.**

If you get an error message that looks like `unexpected T_STRING`, `unexpected '['` or `expecting T_CONSTANT_ENCAPSED_STRING`, you need to update your PHP version. In a shared hosting environment, you should be able to change the version of PHP used by simply toggling the setting in the host's control panel.

PHP 5.4 lost support from the PHP project itself in 2015. You'll need to be running at least PHP 5.5 which adds namespace support (the reason it's breaking), but I'd recommend at least 5.6 (or the latest your host supports) as it's the oldest supported version: <https://en.wikipedia.org/wiki/PHP#Release_history>

## Features

* Converts all posts, pages, and settings from WordPress for use in Jekyll
* Export what your users see, not what the database stores (runs post content through `the_content` filter prior to export, allowing third-party plugins to modify the output)
* Converts all `post_content` to Markdown Extra (using Markdownify)
* Converts all `post_meta` and fields within the `wp_posts` table to YAML front matter for parsing by Jekyll
* Generates a `_config.yml` with all settings in the `wp_options` table
* Outputs a single zip file with `_config.yml`, pages, and `_posts` folder containing `.md` files for each post in the proper Jekyll naming convention
* No settings. Just a single click.

## Usage

1. Place plugin in `/wp-content/plugins/` folder
2. Activate plugin in WordPress dashboard
3. Select `Export to Jekyll` from the `Tools` menu

## Command-line Usage

If you're having trouble with your web server timing out before the export is complete, or if you just like terminal better, you may enjoy the command-line tool.

It works just like the plugin, but produces the zipfile on STDOUT:

```
php jekyll-export-cli.php > jekyll-export.zip
```

If using this method, you must run first `cd` into the wordpress-to-jekyll-exporter directory.

Alternatively, if you have [WP-CLI](http://wp-cli.org) installed, you can run:

```
wp jekyll-export > export.zip
```

The WP-CLI version will provide greater compatibility for alternate WordPress environments, such as when `wp-content` isn't in the usual location.

## Custom post types

To export custom post types, you'll need to add a filter to do the following:

```php
add_filter( 'jekyll_export_post_types', array('posts', 'pages', 'you-custom-post-type') );
```

The custom post type will be exported as a Jekyll collection. You'll need to initialize it in the resulting Jekyll site's `_config.yml`.

## Changelog

[View Past Releases](https://github.com/benbalter/wordpress-to-jekyll-exporter/releases)

## Developing locally

1. `git clone https://github.com/benbalter/wordpress-to-jekyll-exporter`
2. `cd wordpress-to-jekyll-exporter`
3. `script/bootstrap`
4. `script/setup`

To run tests

`script/cibuild`

## License

The project is licensed under the GPLv3 or later
