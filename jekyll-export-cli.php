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

// Customize here the path to wordpress installation if not in the wordpress plugin dir (ex: "/home/wordpress/")
$wordpress_path = "../../../";

$user_config = array();
// You may customize user_config outside the source files with adding a jekyll-export-local-conf.php
if (file_exists('jekyll-export-local-conf.php')) { include 'jekyll-export-local-conf.php'; }

require $wordpress_path . 'wp-load.php';
require_once 'jekyll-exporter.php'; // Ensure plugin is "activated".

if ( php_sapi_name() !== 'cli' ) {
	wp_die( 'Jekyll export must be run via the command line or administrative dashboard.' );
}

$jekyll_export = new Jekyll_Export();
$jekyll_export->export($user_config);
