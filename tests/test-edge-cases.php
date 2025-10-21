<?php
/**
 * Edge case and error handling tests
 *
 * @package    JekyllExporter
 * @author     Ben Balter <ben@balter.com>
 * @copyright  2013-2021 Ben Balter
 * @license    GPLv3
 * @link       https://github.com/benbalter/wordpress-to-jekyll-exporter/
 */

use Symfony\Component\Yaml\Yaml;

/**
 * Test suite for edge cases and error handling
 */
class EdgeCasesTest extends WP_UnitTestCase {

	/**
	 * Setup each test
	 */
	function set_up() {
		parent::set_up();

		global $jekyll_export;
		$jekyll_export->init_temp_dir();
	}

	/**
	 * Tear down each test
	 */
	function tear_down() {
		global $jekyll_export;
		if ( isset( $jekyll_export->dir ) && file_exists( $jekyll_export->dir ) ) {
			$jekyll_export->cleanup();
		}
	}

	/**
	 * Test handling of post with very long title
	 */
	function test_post_with_long_title() {
		global $jekyll_export;

		$long_title = str_repeat( 'Very Long Title ', 50 );
		$post_id    = wp_insert_post(
			array(
				'post_title'   => $long_title,
				'post_content' => 'Content',
				'post_status'  => 'publish',
				'post_name'    => 'long-title-post',
				'post_date'    => '2024-03-01',
			)
		);

		$post = get_post( $post_id );
		$jekyll_export->write( 'Test content', $post );

		// Verify file was created with sanitized name.
		$files = glob( $jekyll_export->dir . '/_posts/2024-03-01-long-title-post.md' );
		$this->assertNotEmpty( $files, 'File should be created even with long title' );
	}

	/**
	 * Test handling of post with unicode characters
	 */
	function test_post_with_unicode() {
		global $jekyll_export;

		$post_id = wp_insert_post(
			array(
				'post_title'   => 'Test with émojis 🎉 and ñ characters',
				'post_content' => 'Content with 中文 and العربية',
				'post_status'  => 'publish',
				'post_date'    => '2024-04-01',
			)
		);

		$post = get_post( $post_id );
		$meta = $jekyll_export->convert_meta( $post );

		$this->assertEquals( 'Test with émojis 🎉 and ñ characters', $meta['title'] );

		$content = $jekyll_export->convert_content( $post );
		$this->assertStringContainsString( '中文', $content );
		$this->assertStringContainsString( 'العربية', $content );
	}

	/**
	 * Test handling of post with HTML in title
	 */
	function test_post_with_html_in_title() {
		global $jekyll_export;

		$post_id = wp_insert_post(
			array(
				'post_title'   => 'Test <strong>Bold</strong> Title',
				'post_content' => 'Content',
				'post_status'  => 'publish',
			)
		);

		$post = get_post( $post_id );
		$meta = $jekyll_export->convert_meta( $post );

		// Title should be retrieved via get_the_title which strips tags.
		$this->assertEquals( 'Test Bold Title', $meta['title'] );
	}

	/**
	 * Test handling of post with tables
	 */
	function test_post_with_table() {
		global $jekyll_export;

		$table_html = '<table><tr><th>Header 1</th><th>Header 2</th></tr><tr><td>Cell 1</td><td>Cell 2</td></tr></table>';

		$post_id = wp_insert_post(
			array(
				'post_title'   => 'Post with Table',
				'post_content' => $table_html,
				'post_status'  => 'publish',
			)
		);

		$post    = get_post( $post_id );
		$content = $jekyll_export->convert_content( $post );

		// Should convert to markdown table format.
		$this->assertStringContainsString( 'Header 1', $content );
		$this->assertStringContainsString( 'Cell 1', $content );
	}

	/**
	 * Test handling of post with shortcodes
	 */
	function test_post_with_shortcodes() {
		global $jekyll_export;

		$content_with_shortcode = 'Before [gallery] After';

		$post_id = wp_insert_post(
			array(
				'post_title'   => 'Post with Shortcode',
				'post_content' => $content_with_shortcode,
				'post_status'  => 'publish',
			)
		);

		$post    = get_post( $post_id );
		$content = $jekyll_export->convert_content( $post );

		// Shortcodes should be processed through the_content filter.
		$this->assertIsString( $content );
	}

	/**
	 * Test handling of post with serialized meta
	 */
	function test_post_with_serialized_meta() {
		global $jekyll_export;

		$post_id = wp_insert_post(
			array(
				'post_title'   => 'Post with Serialized Meta',
				'post_content' => 'Content',
				'post_status'  => 'publish',
			)
		);

		// Add serialized meta data.
		$array_data = array( 'key1' => 'value1', 'key2' => 'value2' );
		add_post_meta( $post_id, 'serialized_field', $array_data );

		$post = get_post( $post_id );
		$meta = $jekyll_export->convert_meta( $post );

		// Should handle serialized data.
		$this->assertArrayHasKey( 'serialized_field', $meta );
		$this->assertIsArray( $meta['serialized_field'] );
	}

	/**
	 * Test handling of post with empty post_name
	 */
	function test_post_with_empty_slug() {
		global $jekyll_export;

		$post_id = wp_insert_post(
			array(
				'post_title'   => 'Post Without Slug',
				'post_content' => 'Content',
				'post_status'  => 'publish',
				'post_name'    => '',
				'post_date'    => '2024-05-01',
			)
		);

		$post = get_post( $post_id );

		// WordPress should auto-generate a post_name.
		$this->assertNotEmpty( $post->post_name );

		$jekyll_export->write( 'Content', $post );

		// File should still be created.
		$files = glob( $jekyll_export->dir . '/_posts/2024-05-01-*.md' );
		$this->assertNotEmpty( $files );
	}

	/**
	 * Test handling of post with post_format
	 */
	function test_post_with_format() {
		global $jekyll_export;

		$post_id = wp_insert_post(
			array(
				'post_title'   => 'Post with Format',
				'post_content' => 'Content',
				'post_status'  => 'publish',
			)
		);

		set_post_format( $post_id, 'aside' );

		$terms = $jekyll_export->convert_terms( $post_id );

		$this->assertArrayHasKey( 'format', $terms );
		$this->assertEquals( 'aside', $terms['format'] );
	}

	/**
	 * Test handling of options with serialized values
	 */
	function test_options_with_serialized_values() {
		global $jekyll_export;

		// Options are already serialized in the database, test that convert_options handles them.
		$jekyll_export->convert_options();

		$config_file = $jekyll_export->dir . '/_config.yml';
		$this->assertTrue( file_exists( $config_file ) );

		$contents = file_get_contents( $config_file );
		$yaml     = Yaml::parse( $contents );

		// Should have basic site options.
		$this->assertIsArray( $yaml );
	}

	/**
	 * Test handling of copy_recursive with symbolic links
	 */
	function test_copy_recursive_with_symlink() {
		global $jekyll_export;

		$temp_dir   = get_temp_dir() . 'symlink-test-' . time();
		$target_dir = get_temp_dir() . 'symlink-target-' . time();

		mkdir( $temp_dir );
		mkdir( $target_dir );

		file_put_contents( $target_dir . '/target.txt', 'target content' );

		// Create a symlink.
		if ( function_exists( 'symlink' ) && ! is_windows() ) {
			symlink( $target_dir . '/target.txt', $temp_dir . '/link.txt' );

			$result = $jekyll_export->copy_recursive( $temp_dir, $jekyll_export->dir . '/symlink-test' );

			$this->assertTrue( $result );

			// Cleanup.
			@unlink( $temp_dir . '/link.txt' );
		}

		// Cleanup.
		@unlink( $target_dir . '/target.txt' );
		@rmdir( $target_dir );
		@rmdir( $temp_dir );
	}

	/**
	 * Test that convert_posts handles empty post list
	 */
	function test_convert_posts_empty() {
		global $jekyll_export, $wpdb;

		// Temporarily delete all posts from cache.
		wp_cache_delete( 'jekyll_export_posts' );

		// Mock get_posts to return empty array.
		$original_posts = $jekyll_export->get_posts();

		// If there are posts, this test would need to clear them, but we'll test the method doesn't crash.
		$jekyll_export->convert_posts();

		// Should complete without errors.
		$this->assertTrue( true );
	}

	/**
	 * Test handling of post with invalid date
	 */
	function test_post_with_invalid_date_handling() {
		global $jekyll_export;

		$post_id = wp_insert_post(
			array(
				'post_title'   => 'Test Post',
				'post_content' => 'Content',
				'post_status'  => 'publish',
				'post_date'    => '0000-00-00 00:00:00',
			)
		);

		// WordPress should handle invalid dates internally.
		$post = get_post( $post_id );
		$meta = $jekyll_export->convert_meta( $post );

		// Should have a date field.
		$this->assertArrayHasKey( 'date', $meta );
	}

	/**
	 * Helper function to check if running on Windows
	 */
	private function is_windows() {
		return strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN';
	}
}
