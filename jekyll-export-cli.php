<?php
/**
 * Exports WordPress posts, pages, and options as YAML files parsable by Jekyll
 *
 * @package    JekyllExporter
 * @author     Ben Balter <ben@balter.com>
 * @copyright  2013-2021 Ben Balter
 * @license    GPLv3
 * @link       https://github.com/benbalter/wordpress-to-jekyll-exporter/
 */

/**
 * Usage:
 *
 *     $ php jekyll-export-cli.php > my-jekyll-files.zip
 *
 * Must be run in the wordpress-to-jekyll-exporter/ directory.
 */

// Uncomment for extra replace options

$wordpress_path = "../../../";
//$wordpress_path = "/home/wordpress/";

require $wordpress_path . 'wp-load.php';
require_once 'jekyll-exporter.php'; // Ensure plugin is "activated".

$config["site_url"] = "https://www.mywebsite.fr/";

$config["extra_remove_site_url"] = $config["site_url"];  // Will remove site url to get relative URLs
$config['extra_code'] = true; // Will retrieve lang and protect pre/code
$config['extra_img_style'] = true; // Will retrieve style from image
$config['extra_tag_spoiler'] = true; // Will convert spoiler tags

//$config['force_dest_dir'] = "/dest-path/"; // "_export/"; // Force destination directory to relative one (without temp folder, will deactivate zip)


if ( php_sapi_name() !== 'cli' ) {
	wp_die( 'Jekyll export must be run via the command line or administrative dashboard.' );
}

add_filter( 'the_content', function($contents) {
	
	if ($config["extra_remove_site_url"]) $contents = preg_replace("!${$config['extra_remove_site_url']}!",'', $contents);

	if ($config['extra_code']) {
		$contents = preg_replace('!<span[^>]*crayon-inline[^>]*>([^<]*)</span>!','<code>\1</code>', $contents);
		$contents = preg_replace('!class="lang:default!','class="', $contents);
		$contents = preg_replace('!class="lang:!','class="language-', $contents);
		$contents = preg_replace('!<pre([^>]*)><code>!','<pre\1>', $contents);
		$contents = preg_replace('!</code></pre>!','</pre>', $contents);
		$contents = preg_replace('!<pre([^>]*)>!','<pre><code \1>', $contents);
		$contents = preg_replace('!</pre[^>]*>!','</code></pre>', $contents);
	}
	if ($config['extra_img_style']) {
		$contents = preg_replace('!(<img[^>]*alignright[^>]*/>)!','\1{: .img-right}', $contents);
		$contents = preg_replace('!(<img[^>]*aligncenter[^>]*/>)!','\1{: .img-center}', $contents);
		$contents = preg_replace('!(<img[^>]*aligncenter[^>]*/>)!','\1{: .img-center}', $contents);
	}
	if ($config['extra_tag_spoiler']) {
		$contents = preg_replace('!\[su_spoiler title="([^"]*)" [^\]]*\]!',"<details markdown=\"1\"><summary>\\1</summary>", $contents);
		$contents = preg_replace('!\[/su_spoiler\]!',"</details>", $contents);
	}
								
	return $contents;
});

$jekyll_export = new Jekyll_Export();
$jekyll_export->export();
