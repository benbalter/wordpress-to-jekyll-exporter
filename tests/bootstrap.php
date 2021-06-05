<?php
/**
 * Bootstrap the local test environment
 *
 * @package Jekyll_Exporter
 */

// Save error reporting level (for reversion after file delete).
// phpcs:ignore
$err_level = error_reporting();

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

/**
 * Output message to log.
 *
 * @param string $text text to output.
 */
function console_log( $text ) {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
	fwrite( STDERR, "\n" . $text . ' : ' );
}

/**
 * Require the Jekyll Export Plugin on load
 */
function _manually_load_plugin() {
	require __DIR__ . '/../jekyll-exporter.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

/**
 * Several tests will try to serve a file twice, this would fail, so suppress headers from being written.
 *
 * Tests also require buffers opened to be closed (and so send headers).
 *
 * @param array  $headers any headers for the file being served.
 * @param string $file    file name of file being served.
 */
function _remove_headers( $headers, $file ) {
	return array();
}

/**
 * Extends the test framework's native wp_die_handler filter to filter
 * wp_die() calls for XML requests in addition to HTML requests
 *
 * @param string $handler the default callback.
 * @return string the filtered callback.
 */
function _wpdr_die_handler_filter( $handler ) {
	return apply_filters( 'wp_die_handler', $handler );
}

tests_add_filter( 'wp_die_xml_handler', '_wpdr_die_handler_filter' );

require $_tests_dir . '/includes/bootstrap.php';
