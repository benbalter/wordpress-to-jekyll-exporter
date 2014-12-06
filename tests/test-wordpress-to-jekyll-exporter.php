<?php

class WordPressToJekyllExporterTest extends WP_UnitTestCase {

  function setUp() {
    parent::setUp();
  }

  function test_activated() {
    $this->assertTrue( class_exists( 'Jekyll_Export' ), 'Jekyll_Export class not defined' );
  }

}
