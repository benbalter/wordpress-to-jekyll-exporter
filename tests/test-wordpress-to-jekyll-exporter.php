<?php

class WordPressToJekyllExporterTest extends WP_UnitTestCase {

  function setUp() {
    parent::setUp();
    $author = wp_insert_user(array(
      "user_login"   => "testuser",
      "user_pass"    => "testing",
      "display_name" => "Tester",
    ));

    $category_id = wp_insert_category(array('cat_name' => 'Testing'));

    wp_insert_post(array(
      "post_name"     => "test-post",
      "post_title"    => "Test Post",
      "post_content"  => "This is a test <strong>post</strong>.",
      "post_status"   => "publish",
      "post_author"   => $author,
      "post_category" => array($category_id),
      "tags_input"    => array("tag1", "tag2"),
    ));

    wp_insert_post(array(
      "post_name"    => "test-page",
      "post_title"   => "Test Page",
      "post_content" => "This is a test <strong>page</strong>.",
      "post_status"  => "publish",
      "post_type"    => "page",
      "post_author"  => $author,
    ));
  }

  function test_activated() {
    global $jekyll_export;
    $this->assertTrue( class_exists( 'Jekyll_Export' ), 'Jekyll_Export class not defined' );
    $this->assertTrue( isset($jekyll_export) );
  }

  function test_loads_dependencies() {
    $this->assertTrue( class_exists( 'Spyc' ), 'Spyc class not defined' );
    $this->assertTrue( class_exists( 'Markdownify\Parser' ), 'Markdownify class not defined' );
  }

  function test_gets_post_ids() {
    global $jekyll_export;
    $this->assertEquals(2, count($jekyll_export->get_posts()));
  }

  function test_convert_meta() {
    global $jekyll_export;
    $posts = $jekyll_export->get_posts();
    $post = get_post($posts[1]);
    $meta = $jekyll_export->convert_meta($post);
    $expected = Array (
      'title'     => 'Test Post',
      'author'    => 'Tester',
      'excerpt'   => '',
      'layout'    => 'post',
      'permalink' => '/?p=9',
    );
    $this->assertEquals($expected, $meta);
  }

  function test_convert_terms() {
    global $jekyll_export;
    $posts = $jekyll_export->get_posts();
    $post = get_post($posts[1]);
    $terms = $jekyll_export->convert_terms($post->ID);
    $this->assertEquals(array(0 => "Testing"), $terms["categories"]);
    $this->assertEquals(array(0 => "tag1", 1 => "tag2"), $terms["tags"]);
  }

  function test_convert_content() {
    global $jekyll_export;
    $posts = $jekyll_export->get_posts();
    $post = get_post($posts[1]);
    $content = $jekyll_export->convert_content($post);
    $this->assertEquals("This is a test **post**.", $content);
  }

  

}
