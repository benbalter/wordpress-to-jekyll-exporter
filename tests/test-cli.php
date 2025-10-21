<?php
/**
 * Tests for CLI functionality
 *
 * @package    JekyllExporter
 * @author     Ben Balter <ben@balter.com>
 * @copyright  2013-2021 Ben Balter
 * @license    GPLv3
 * @link       https://github.com/benbalter/wordpress-to-jekyll-exporter/
 */

/**
 * Test suite for CLI functionality
 */
class CLITest extends WP_UnitTestCase {

	/**
	 * Test that Jekyll_Export_Command class exists when WP_CLI is defined
	 */
	function test_cli_command_class_exists() {
		if ( ! defined( 'WP_CLI' ) ) {
			define( 'WP_CLI', true );
		}

		// Reload the CLI file.
		require_once __DIR__ . '/../lib/cli.php';

		$this->assertTrue( class_exists( 'Jekyll_Export_Command' ), 'Jekyll_Export_Command class should exist when WP_CLI is defined' );
	}

	/**
	 * Test that Jekyll_Export_Command has an invoke method
	 */
	function test_cli_command_has_invoke() {
		if ( ! class_exists( 'Jekyll_Export_Command' ) ) {
			if ( ! defined( 'WP_CLI' ) ) {
				define( 'WP_CLI', true );
			}
			require_once __DIR__ . '/../lib/cli.php';
		}

		$this->assertTrue( method_exists( 'Jekyll_Export_Command', '__invoke' ), 'Jekyll_Export_Command should have __invoke method' );
	}

	/**
	 * Test that the CLI command can be instantiated
	 */
	function test_cli_command_instantiation() {
		if ( ! class_exists( 'Jekyll_Export_Command' ) ) {
			if ( ! defined( 'WP_CLI' ) ) {
				define( 'WP_CLI', true );
			}
			require_once __DIR__ . '/../lib/cli.php';
		}

		$command = new Jekyll_Export_Command();
		$this->assertInstanceOf( 'Jekyll_Export_Command', $command );
	}
}
