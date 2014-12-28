<?php

if ( defined('WP_CLI') && WP_CLI ) {

  class Jekyll_Export_Command extends WP_CLI_Command {

    function __invoke() {
      global $jekyll_export;
      $jekyll_export->export();
    }
  }

  WP_CLI::add_command( 'jekyll-export', 'Jekyll_Export_Command' );

}
