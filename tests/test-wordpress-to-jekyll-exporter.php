<?php

class WordPressToJekyllExporterTest extends WP_UnitTestCase {

  function setUp() {
    parent::setUp();
  }

  function test_activated() {
    $this->assertTrue( class_exists( 'Jekyll_Export' ), 'Jekyll_Export class not defined' );
  }

  function test_loads_dependencies() {
    $this->assertTrue( class_exists( 'Spyc' ), 'Spyc class not defined' );
    $this->assertTrue( class_exists( 'Markdownify\Parser' ), 'Markdownify class not defined' );
  }

}
