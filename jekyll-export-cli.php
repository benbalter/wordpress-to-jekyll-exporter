<?php
/*
 * Run the exporter from the command line and spit the zipfile to STDOUT.
 *
 * Usage:
 *
 *     $ php jekyll-export-cli.php > my-jekyll-files.zip
 *
 * Must be run in the wordpress-to-jekyll-exporter/ directory.
 *
 */
 
include("jekyll-export.php");
include("../../../wp-config.php");

$je = new Jekyll_Export();
$je->export();

/*
 * Optional WP CLI Support
 */
if ( class_exists( 'WP_CLI_Command' ) ):

  class Jekyll_Export_Command extends WP_CLI_Command {

  	function __invoke() {
  		global $je;

  		$je->export();
  	}
  }

  WP_CLI::add_command( 'jekyll-export', 'Jekyll_Export_Command' );

endif;