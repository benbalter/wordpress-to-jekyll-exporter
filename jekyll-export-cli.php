<?php
/**
 * Run the exporter from the command line and spit the zipfile to STDOUT.
 *
 * @package    JekyllExporter
 * @author     Ben Balter <ben.balter@github.com>
 * @copyright  2013-2016 Ben Balter
 * @license    GPLv3
 * @link       https://github.com/benbalter/wordpress-to-jekyll-exporter/
 */

include '../../../wp-load.php';
include '../../../wp-admin/includes/file.php';
require_once 'jekyll-exporter.php'; // Ensure plugin is "activated".

if ( php_sapi_name() != 'cli' ) {
	wp_die( 'Jekyll export must be run via the command line or administrative dashboard.' );
}

/**
* Usage:
*
*     $ php jekyll-export-cli.php > my-jekyll-files.zip
*
* Must be run in the wordpress-to-jekyll-exporter/ directory.
*/
$jekyll_export = new Jekyll_Export();
$jekyll_export->export();
