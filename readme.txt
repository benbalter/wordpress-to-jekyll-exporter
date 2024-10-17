=== Jekyll Exporter ===
Contributors: benbalter
Tags: jekyll, github, github pages, yaml, export, markdown
Requires at least: 4.4
Tested up to: 6.6
Requires PHP: 7.2
Stable tag: 2.4.1
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

== Features ==

* Converts all posts, pages, and settings from WordPress to Markdown and YAML for use in Jekyll (or Hugo or any other Markdown and YAML based site engine)
* Export what your users see, not what the database stores (runs post content through `the_content` filter prior to export, allowing third-party plugins to modify the output)
* Converts all `post_content` to Markdown
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

* [Changelog](https://ben.balter.com/wordpress-to-jekyll-exporter/./docs/changelog/)
* [Command-line-usage](https://ben.balter.com/wordpress-to-jekyll-exporter/./docs/command-line-usage/)
* [Custom post types](https://ben.balter.com/wordpress-to-jekyll-exporter/./docs/custom-post-types/)
* [Custom fields](https://ben.balter.com/wordpress-to-jekyll-exporter/./docs/custom-fields/)
* [Developing locally](https://ben.balter.com/wordpress-to-jekyll-exporter/./docs/developing-locally/)
* [Minimum required PHP version](https://ben.balter.com/wordpress-to-jekyll-exporter/./docs/required-php-version/)


=== Security Policy ===

To report a security vulnerability, please email [ben@balter.com](mailto:ben@balter.com).


== Where to get help or report an issue ==

* For getting started and general documentation, please browse, and feel free to contribute to [the project documentation](http://ben.balter.com/wordpress-to-jekyll-exporter/).
* For support questions ("How do I", "I can't seem to", etc.) please search and if not already answered, open a thread in the [Support Forums](http://wordpress.org/support/plugin/jekyll-exporter).
* For technical issues (e.g., to submit a bug or feature request) please search and if not already filed, [open an issue on GitHub](https://github.com/benbalter//wordpress-to-jekyll-exporter/issues).

== Things to check before reporting an issue ==

* Are you using the latest version of WordPress?
* Are you using the latest version of the plugin?
* Does the problem occur even when you deactivate all plugins and use the default theme?
* Have you tried deactivating and reactivating the plugin?
* Has your issue [already been reported](https://github.com/benbalter/wordpress-to-jekyll-exporter/issues)?

== What to include in an issue ==

* What steps can another user take to recreate the issue?
* What is the expected outcome of that action?
* What is the actual outcome of that action?
* Are there any screenshots or screencasts that may be helpful to include?
* Only include one bug per issue. If you have discovered two bugs, please file two issues.


== Changelog ==

[View Past Releases](https://github.com/benbalter/wordpress-to-jekyll-exporter/releases)


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


== Custom fields ==

When using custom fields (e.g. with the Advanced Custom fields plugin) you might have to register a filter to convert array style configs to plain values.

By default, the plugin saves custom fields in an array structure that is exported as: 

```php
["my-bool"]=>
    array(1) {
        [0] => string(1) "1"
    }
["location"]=>
    array(1) {
        [0] => string(88) "My address"
    }
```

And this leads to a YAML structure like:

```yaml
my-bool:
- "1"
location:
- 'My address'
```

This is likely not the structure you expect or want to work with. You can convert it using a filter:

```php
add_filter( 'jekyll_export_meta', function($meta) {
    foreach ($meta as $key => $value) {
        if (is_array($value) && count($value) === 1 && array_key_exists(0, $value)) {
            $meta[$key] = $value[0];
        }
    }

    return $meta;
});
```

A more complete solution could look like that:

```php
add_filter( 'jekyll_export_meta', function($meta) {
    foreach ($meta as $key => $value) {
        // Advanced Custom Fields
        if (is_array($value) && count($value) === 1 && array_key_exists(0, $value)) {
            $value = maybe_unserialize($value[0]);
            // Advanced Custom Fields: NextGEN Gallery Field add-on
            if (is_array($value) && count($value) === 1 && array_key_exists(0, $value)) {
                $value = $value[0];
            }
        }
        // convert types
        $value = match ($key) {
            // Advanced Custom Fields: "true_false" type
            'my-bool' => (bool) $value,
            default => $value
        };
        $meta[$key] = $value;
    }

    return $meta;
});
```



== Custom post types ==

To export custom post types, you'll need to add a filter (w.g. to your themes config file) to do the following:

```php
add_filter( 'jekyll_export_post_types', function() {
	return array('post', 'page', 'you-custom-post-type');
});
```

The custom post type will be exported as a Jekyll collection. You'll need to initialize it in the resulting Jekyll site's `_config.yml`.


== Developing locally ==

= Prerequisites =

1. `sudo apt-get update`
1. `sudo apt-get install composer`
1. `sudo apt-get install php7.3-xml`
1. `sudo apt-get install php7.3-mysql`
1. `sudo apt-get install php7.3-zip`
1. `sudo apt-get install php-mbstring`
1. `sudo apt-get install subversion`
1. `sudo apt-get install mysql-server`
1. `sudo apt-get install php-pear`
1. `sudo pear install PHP_CodeSniffer`

= Bootstrap & Setup =

1. `git clone https://github.com/benbalter/wordpress-to-jekyll-exporter`
2. `cd wordpress-to-jekyll-exporter`
3. `script/bootstrap`
4. `script/setup`

= Running tests =

`script/cibuild`

== Testing locally via Docker ==

1. `git clone https://github.com/benbalter/wordpress-to-jekyll-exporter`
2. `docker-compose up`
3. `open localhost:8088`

== Minimum required PHP version ==

Many shared hosts may use an outdated version of PHP by default. **WordPress to Jekyll Export requires PHP 5.6 or greater.**

If you get an error message that looks like `unexpected T_STRING`, `unexpected '['` or `expecting T_CONSTANT_ENCAPSED_STRING`, you need to update your PHP version. In a shared hosting environment, you should be able to change the version of PHP used by simply toggling the setting in the host's control panel.

PHP 5.4 lost support from the PHP project itself in 2015. You'll need to be running at least PHP 5.5 which adds namespace support (the reason it's breaking), but I'd recommend at least 7.3 (or the latest your host supports) as it's the [oldest supported version](https://www.php.net/supported-versions.php).

= How to determine which version of PHP you're running =

* Try [this plugin](https://wordpress.org/plugins/display-php-version/)
* Follow [WordPress's tutorial](https://codex.wordpress.org/Finding_Server_Info) or [this wikihow](https://www.wikihow.com/Check-PHP-Version)

= How to upgrade your version of PHP =

If you are using a shared hosting environment, upgrading to a newer version of PHP should be a matter of changing a setting in your host's control panel. You'll have to follow your host specific documentation to determine how to access it or where the setting lives. Check out [this list of common hosts](https://kb.yoast.com/kb/how-to-update-your-php-version/) for more details.
