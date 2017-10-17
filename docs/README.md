# WordPress to Jekyll Exporter

One-click WordPress plugin that converts all posts, pages, taxonomies, metadata, and settings to Markdown and YAML which can be dropped into Jekyll.

[![Build Status](https://travis-ci.org/benbalter/wordpress-to-jekyll-exporter.svg?branch=master)](https://travis-ci.org/benbalter/wordpress-to-jekyll-exporter) [![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg?style=flat-square)](http://makeapullrequest.com)

View plugin in [the WordPress plugin directory](https://wordpress.org/plugins/jekyll-exporter/).

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

## More information

See [the full documentation](https://ben.balter.com/wordpress-to-jekyll-exporter):

* [Changelog](changelog.md)
* [Command-line-usage](command-line-usage.md)
* [Custom post types](custom-post-types.md)
* [Developing locally](developing-locally.md)
* [Minimum required PHP version](required-php-version.md)
