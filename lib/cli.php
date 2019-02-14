<?php
/**
 * Run the exporter from the command line and spit the zipfile to STDOUT.
 *
 * @package    JekyllExporter
 * @author     Ben Balter <ben.balter@github.com>
 * @copyright  2013-2019 Ben Balter
 * @license    GPLv3
 * @link       https://github.com/benbalter/wordpress-to-jekyll-exporter/
 */

if ( defined( 'WP_CLI' ) && WP_CLI ) {

	/**
	 * Extends WP CLI to add our export command
	 */
	class Jekyll_Export_Command extends WP_CLI_Command {

		/**
		 * Trigger an export
		 */
		function __invoke() {
			global $jekyll_export;
			$jekyll_export->export();
		}
	}

	WP_CLI::add_command( 'jekyll-export', 'Jekyll_Export_Command' );

}
