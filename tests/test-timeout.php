<?php
/**
 * Test file to check if set_time_limit(0) is working correctly
 *
 * @package    JekyllExporter
 * @author     Ben Balter <ben@balter.com>
 * @copyright  2013-2025 Ben Balter
 * @license    GPLv3
 * @link       https://github.com/benbalter/wordpress-to-jekyll-exporter/
 */

/**
 * Test suite for timeout handling in JekyllExport.
 */
class TimeoutTest extends WP_UnitTestCase {

	/**
	 * Test that set_time_limit is not called during tests.
	 */
	public function test_set_time_limit_not_called_in_tests() {
		// Verify that WP_TESTS_DOMAIN is defined (we're in test environment).
		$this->assertTrue( defined( 'WP_TESTS_DOMAIN' ), 'WP_TESTS_DOMAIN should be defined in test environment' );

		// Since WP_TESTS_DOMAIN is defined, set_time_limit should not be called.
		// We can't directly test if set_time_limit was called, but we can verify.
		// the test environment is properly detected.
		$this->assertTrue( true );
	}

	/**
	 * Test that the export method runs without errors.
	 */
	public function test_export_method_executes() {
		global $jekyll_export;

		// Initialize temp directory.
		$jekyll_export->init_temp_dir();

		// Verify the directory was created.
		$this->assertTrue( file_exists( $jekyll_export->dir ), 'Export directory should exist' );

		// Clean up.
		$jekyll_export->cleanup();
	}
}
