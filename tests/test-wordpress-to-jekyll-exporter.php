<?php
/**
 * Exports WordPress posts, pages, and options as YAML files parsable by Jekyll
 *
 * @package    JekyllExporter
 * @author     Ben Balter <ben.balter@github.com>
 * @copyright  2013-2016 Ben Balter
 * @license    GPLv3
 * @link       https://github.com/benbalter/wordpress-to-jekyll-exporter/
 */

use Alchemy\Zippy\Zippy;

/**
 * Test suite for JekyllExport
 */
class WordPressToJekyllExporterTest extends WP_UnitTestCase {

	/**
	 * Setup the test suite
	 */
	function setUp() {
		parent::setUp();
		$author = wp_insert_user(
			array(
				'user_login'   => rand_str(),
				'user_pass'    => rand_str(),
				'display_name' => 'Tester',
			)
		);

		$category_id = wp_insert_category(
			array(
				'cat_name' => 'Testing',
			)
		);

		wp_insert_post(
			array(
				'post_name'     => 'test-post',
				'post_title'    => 'Test Post',
				'post_content'  => 'This is a test <strong>post</strong>.',
				'post_status'   => 'publish',
				'post_author'   => $author,
				'post_category' => array( $category_id ),
				'tags_input'    => array( 'tag1', 'tag2' ),
				'post_date'     => '2014-01-01',
			)
		);

		$page_id = wp_insert_post(
			array(
				'post_name'    => 'test-page',
				'post_title'   => 'Test Page',
				'post_content' => 'This is a test <strong>page</strong>.',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_author'  => $author,
			)
		);

		wp_insert_post(
			array(
				'post_name'    => 'sub-page',
				'post_title'   => 'Sub Page',
				'post_content' => 'This is a test <strong>sub</strong> page.',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_parent'  => $page_id,
				'post_author'  => $author,
			)
		);

		global $jekyll_export;
		$jekyll_export->init_temp_dir();

	}

	/**
	 * Tear down the test suite
	 */
	function tearDown() {
		global $jekyll_export;
		$jekyll_export->cleanup();
		$upload_dir = wp_upload_dir();
		@array_map( 'unlink', glob( $upload_dir['basedir'] . '/*' ) );
	}

	/**
	 * Test that the plugin is activated
	 */
	function test_activated() {
		global $jekyll_export;
		$this->assertTrue( class_exists( 'Jekyll_Export' ), 'Jekyll_Export class not defined' );
		$this->assertTrue( isset( $jekyll_export ) );
	}

	/**
	 * Test that the plugin loads dependencies
	 */
	function test_loads_dependencies() {
		$this->assertTrue( class_exists( 'Spyc' ), 'Spyc class not defined' );
		$this->assertTrue( class_exists( 'Markdownify\Parser' ), 'Markdownify class not defined' );
	}

	/**
	 * Test that it returns post IDs
	 */
	function test_gets_post_ids() {
		global $jekyll_export;
		$this->assertEquals( 9, count( $jekyll_export->get_posts() ) );
	}

	/**
	 * Test that it converts meta
	 */
	function test_convert_meta() {
		global $jekyll_export;
		$posts = $jekyll_export->get_posts();
		$post = get_post( $posts[0] );
		$meta = $jekyll_export->convert_meta( $post );
		$expected = array(
			'id'        => $post->ID,
			'title'     => 'Test Post',
			'date'      => '2014-01-01T00:00:00+00:00',
			'author'    => 'Tester',
			'excerpt'   => '',
			'layout'    => 'post',
			'permalink' => '/?p=3',
			'guid'      => $post->guid,
		);
		$this->assertEquals( $expected, $meta );
	}

	/**
	 * Test that it converts terms
	 */
	function test_convert_terms() {
		global $jekyll_export;
		$posts = $jekyll_export->get_posts();
		$post = get_post( $posts[0] );
		$terms = $jekyll_export->convert_terms( $post->ID );
		$this->assertEquals(
			array(
				0 => 'Testing',
			), $terms['categories']
		);
		$this->assertEquals(
			array(
				0 => 'tag1',
				1 => 'tag2',
			), $terms['tags']
		);
	}

	/**
	 * Test that it converts content
	 */
	function test_convert_content() {
		global $jekyll_export;
		$posts = $jekyll_export->get_posts();
		$post = get_post( $posts[0] );
		$content = $jekyll_export->convert_content( $post );
		$this->assertEquals( 'This is a test **post**.', $content );
	}

	/**
	 * Test that it init's the temporary work directory
	 */
	function test_init_temp_dir() {
		global $jekyll_export;
		$this->assertTrue( file_exists( $jekyll_export->dir ) );
		$this->assertTrue( file_exists( $jekyll_export->dir . '/_posts' ) );
	}

	/**
	 * Test that it converts posts
	 */
	function test_convert_posts() {
		global $jekyll_export;
		$posts = $jekyll_export->convert_posts();
		$post = $jekyll_export->dir . '/_posts/2014-01-01-test-post.md';

		// write the file to the temp dir.
		$this->assertTrue( file_exists( $post ) );

		// Handles pages.
		$this->assertTrue( file_exists( $jekyll_export->dir . 'test-page.md' ) );
		$this->assertTrue( file_exists( $jekyll_export->dir . 'test-page/sub-page.md' ) );

		// writes the file contents.
		$contents = file_get_contents( $post );
		$this->assertContains( 'title: Test Post', $contents );

		// writes valid YAML.
		$parts = explode( '---', $contents );
		$this->assertEquals( 3,count( $parts ) );
		$yaml = spyc_load( $parts[1] );
		$this->assertNotEmpty( $yaml );

		// writes the front matter.
		$this->assertEquals( 'Test Post', $yaml['title'] );
		$this->assertEquals( 'Tester', $yaml['author'] );
		$this->assertEquals( 'post', $yaml['layout'] );
		$this->assertEquals( '/?p=3', $yaml['permalink'] );
		$this->assertEquals(
			array(
				0 => 'Testing',
			), $yaml['categories']
		);
		$this->assertEquals(
			array(
				0 => 'tag1',
				1 => 'tag2',
			), $yaml['tags']
		);

		// writes the post body.
		$this->assertEquals( "\nThis is a test **post**.", $parts[2] );
	}

	/**
	 * Test that it exports site options to the site config
	 */
	function test_export_options() {
		global $jekyll_export;
		$jekyll_export->convert_options();
		$config = $jekyll_export->dir . '/_config.yml';

		// write the file to the temp dir.
		$this->assertTrue( file_exists( $config ) );

		// writes the file content.
		$contents = file_get_contents( $config );
		$this->assertContains( 'description: Just another WordPress site', $contents );

		// writes valid YAML.
		$yaml = spyc_load( $contents );
		$this->assertEquals( 'Just another WordPress site', $yaml['description'] );
		$this->assertEquals( 'http://example.org', $yaml['url'] );
		$this->assertEquals( 'Test Blog', $yaml['name'] );
	}

	/**
	 * Test that it writes files to the temporary directory
	 */
	function test_write() {
		global $jekyll_export;
		$posts = $jekyll_export->get_posts();
		$post = get_post( $posts[0] );
		$jekyll_export->write( 'Foo', $post );
		$post = $jekyll_export->dir . '/_posts/2014-01-01-test-post.md';
		$this->assertTrue( file_exists( $post ) );
		$this->assertEquals( 'Foo',file_get_contents( $post ) );
	}

	/**
	 * Test that it creates the zip
	 */
	function test_zip() {

		global $jekyll_export;

		file_put_contents( $jekyll_export->dir . '/foo.txt', 'bar' );
		$jekyll_export->zip();
		$this->assertTrue( file_exists( $jekyll_export->zip ) );

		$temp_dir = get_temp_dir() . 'jekyll-export-extract';
		array_map( 'unlink', glob( "$temp_dir/*.*" ) );
		if ( file_exists( $temp_dir ) ) {
			delete_dir( $temp_dir );
		}

		$zip = new ZipArchive();
		$zip->open( $jekyll_export->zip );
		$zip->extractTo( $temp_dir );
		$zip->close();

		$this->assertTrue( file_exists( $temp_dir . '/foo.txt' ) );
		$this->assertEquals( 'bar', file_get_contents( $temp_dir . '/foo.txt' ) );
	}

	/**
	 * Test that it cleans up after itself
	 */
	function test_cleanup() {
		global $jekyll_export;
		$this->assertTrue( file_exists( $jekyll_export->dir ) );
		$jekyll_export->cleanup();
		$this->assertFalse( file_exists( $jekyll_export->dir ) );
	}

	/**
	 * Test that it renames meta keys
	 */
	function test_rename_key() {
		global $jekyll_export;
		$array = array(
			'foo' => 'bar',
			'foo2' => 'bar2',
		);
		$jekyll_export->rename_key( $array, 'foo', 'baz' );
		$expected = array(
			'baz' => 'bar',
			'foo2' => 'bar2',
		);
		$this->assertEquals( $expected, $array );
	}

	/**
	 * Test that it converts uploads
	 */
	function test_convert_uploads() {
		global $jekyll_export;
		$upload_dir = wp_upload_dir();
		file_put_contents( $upload_dir['basedir'] . '/foo.txt', 'bar' );
		$jekyll_export->convert_uploads();
		$this->assertTrue( file_exists( $jekyll_export->dir . '/wp-content/uploads/foo.txt' ) );
	}

	/**
	 * Test that it recursively coppies static files
	 */
	function test_copy_recursive() {
		global $jekyll_export;
		$upload_dir = wp_upload_dir();

		if ( ! file_exists( $upload_dir['basedir'] . '/folder' ) ) {
			mkdir( $upload_dir['basedir'] . '/folder' );
		}

		file_put_contents( $upload_dir['basedir'] . '/foo.txt', 'bar' );
		file_put_contents( $upload_dir['basedir'] . '/folder/foo.txt', 'bar' );
		$jekyll_export->copy_recursive( $upload_dir['basedir'], $jekyll_export->dir );

		$this->assertTrue( file_exists( $jekyll_export->dir . '/foo.txt' ) );
		$this->assertTrue( file_exists( $jekyll_export->dir . '/folder/foo.txt' ) );
	}

}
