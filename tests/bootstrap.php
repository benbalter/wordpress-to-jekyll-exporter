<?php
/**
 * PHPUnit bootstrap file
 *
 * @package jekyll-exporter
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/jekyll-exporter.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

/**
 * Recursively remove a directory
 *
 * See https://stackoverflow.com/a/3349792.
 *
 * @param String $dir_path the path to the directory.
 * @throws InvalidArgumentException Thrown if not a directory.
 */
function delete_dir( $dir_path ) {
	if ( ! is_dir( $dir_path ) ) {
		throw new InvalidArgumentException( "$dir_path must be a directory" );
	}
	if ( substr( $dir_path, strlen( $dir_path ) - 1, 1 ) !== '/' ) {
		$dir_path .= '/';
	}
	$files = glob( $dir_path . '*', GLOB_MARK );
	foreach ( $files as $file ) {
		if ( is_dir( $file ) ) {
			delete_dir( $file );
		} else {
			unlink( $file );
		}
	}
	rmdir( $dir_path );
}
