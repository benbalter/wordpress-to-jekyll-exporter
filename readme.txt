=== Jekyll Exporter ===
Contributors: benbalter
Tags: jekyll, github, github pages, yaml, export
Requires at least: 4.4
Tested up to: 5.0.3
Stable tag: 2.3.0
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

== Features ==

* Converts all posts, pages, and settings from WordPress for use in Jekyll
* Export what your users see, not what the database stores (runs post content through `the_content` filter prior to export, allowing third-party plugins to modify the output)
* Converts all `post_content` to Markdown Extra (using Markdownify)
* Converts all `post_meta` and fields within the `wp_posts` table to YAML front matter for parsing by Jekyll
* Generates a `_config.yml` with all settings in the `wp_options` table
* Outputs a single zip file with `_config.yml`, pages, and `_posts` folder containing `.md` files for each post in the proper Jekyll naming convention
* No settings. Just a single click.

== Usage ==

1. Place plugin in `/wp-content/plugins/` folder
2. Activate plugin in WordPress dashboard
3. Select `Export to Jekyll` from the `Tools` menu

== More information ==

See [the full documentation](https://ben.balter.com/wordpress-to-jekyll-exporter):

* [Changelog](http://ben.balter.com/wordpress-to-jekyll-exporter/changelog/)
* [Command-line-usage](http://ben.balter.com/wordpress-to-jekyll-exporter/command-line-usage/)
* [Custom post types](http://ben.balter.com/wordpress-to-jekyll-exporter/custom-post-types/)
* [Developing locally](http://ben.balter.com/wordpress-to-jekyll-exporter/developing-locally/)
* [Minimum required PHP version](http://ben.balter.com/wordpress-to-jekyll-exporter/required-php-version/)


== Custom post types ==

To export custom post types, you'll need to add a filter to do the following:

```php
add_filter( 'jekyll_export_post_types', array('posts', 'pages', 'you-custom-post-type') );
```

The custom post type will be exported as a Jekyll collection. You'll need to initialize it in the resulting Jekyll site's `_config.yml`.


== Changelog ==

[View Past Releases](https://github.com/benbalter/wordpress-to-jekyll-exporter/releases)


== Developing locally ==

= Prerequisites =
1. `sudo apt-get update`
1. `sudo apt install composer`
1. `sudo apt install php7.0-xml`
1. `sudo apt install php7.0-mysql`
1. `sudo apt install php7.0-zip`
1. `sudo apt install php-mbstring`
1. `sudo apt install subversion`
1. `sudo apt install mysql-server`
1. `sudo apt install php-pear`
1. `sudo pear install PHP_CodeSniffer`

= Bootstrap & Setup =
1. `git clone https://github.com/benbalter/wordpress-to-jekyll-exporter`
2. `cd wordpress-to-jekyll-exporter`
3. `script/bootstrap`
4. `script/setup`

= Running tests =
`script/cibuild`


== Command-line Usage ==

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


== Minimum required PHP version ==

Many shared hosts may use an outdated version of PHP by default. **WordPress to Jekyll Export requires PHP 5.6 or greater.**

If you get an error message that looks like `unexpected T_STRING`, `unexpected '['` or `expecting T_CONSTANT_ENCAPSED_STRING`, you need to update your PHP version. In a shared hosting environment, you should be able to change the version of PHP used by simply toggling the setting in the host's control panel.

PHP 5.4 lost support from the PHP project itself in 2015. You'll need to be running at least PHP 5.5 which adds namespace support (the reason it's breaking), but I'd recommend at least 5.6 (or the latest your host supports) as it's the oldest supported version: <https://en.wikipedia.org/wiki/PHP#Release_history>

= How to determine which version of PHP you're running =

* Try [this plugin](https://wordpress.org/plugins/display-php-version/)
* Follow [WordPress's tutorial](https://codex.wordpress.org/Finding_Server_Info) or [this wikihow](https://www.wikihow.com/Check-PHP-Version)

= How to upgrade your version of PHP =

If you are using a shared hosting environment, upgrading to a newer version of PHP should be a matter of changing a setting in your host's control panel. You'll have to follow your host specific documentation to determine how to access it or where the setting lives. Check out [this list of common hosts](https://kb.yoast.com/kb/how-to-update-your-php-version/) for more details.
