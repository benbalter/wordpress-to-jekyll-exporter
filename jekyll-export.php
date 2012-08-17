<?php
/*
Plugin Name: Name Of The Plugin
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: A brief description of the Plugin.
Version: The Plugin's Version Number, e.g.: 1.0
Author: Name Of The Plugin Author
Author URI: http://URI_Of_The_Plugin_Author
License: A "Slug" license name e.g. GPL2

Copyright YEAR  PLUGIN_AUTHOR_NAME  (email : PLUGIN AUTHOR EMAIL)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class Jekyll_Export {

	private $zip_folder = 'jekyll-export/';
	
	function __construct() {
		
		if ( !class_exists( 'spyc' ) )
			require_once( dirname( __FILE__ ) . '/spyc.php' );
			
		if ( !class_exists( 'Markdownify' ) )
			require_once( dirname( __FILE__ ) . '/markdownify/markdownify.php' );
		
		$this->dir = sys_get_temp_dir() . '/wp-jekyll-' . md5( time() ) . '/';
		$this->zip = sys_get_temp_dir() . '/wp-jekyll.zip';
		mkdir( $this->dir );
		mkdir( $this->dir . '_posts/' );
		
	}
	
	function get_posts() {
		
		global $wpdb;
		return $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_status IN ( 'publish', 'draft' ) AND post_type IN ('post', 'page' )" );
		
	}
	
	function convert_meta( $post ) {
		
		foreach( $post as $key => $value ) {
		
			if ( $key == 'post_content' )
				continue;
							
			$key = str_replace( 'post_', '', $key );
			$output[ strtolower( $key)  ] = $value;
			
		}
		
		foreach ( get_post_custom( $post ) as $key => $value ) {
			
			if ( substr( $key, 0, 1 ) == '_' )
				continue;
		
			$output[ $key ] = $value;
			
		}
		
		return $output;
		
	}
	
	function convert_terms( $post ) {

		$output = array();
		foreach( get_taxonomies( array( 'object_type' => array( get_post_type( $post ) ) ) ) as $tax ) { 
			$terms = wp_get_post_terms( $post, $tax );
			$output[ $tax ] = wp_list_pluck( $terms, 'name' );
		}
		
		return $output;
		
	}
	
	function convert() {

		$this->convert_options();
		
		foreach ( $this->get_posts() as $postID ) {
			$md = new Markdownify;
			$post = get_post( $postID );
			$meta = $this->convert_meta( $post );
			$meta = array_merge( $meta, $this->convert_terms( $postID ) );
			$output = Spyc::YAMLDump($meta);
			$output .= "\n---\n";
			$output .= $md->parseString( apply_filters( 'the_content', $post->post_content ) );
			$this->write( $output, $post );
		}
		
		$this->zip();
		$this->send();
		
	}
	
	function convert_options() {
		
		$options = wp_load_alloptions();
		foreach ( $options as $key => &$option ) {

			if ( substr( $key, 0, 1 ) == '_' )
				unset( $options[$key] );
		
			$option = maybe_unserialize( $option );

		}
		
		$output = Spyc::YAMLDump( $options );
		$output .= '---';
		
		file_put_contents( $this->dir . '_config.yml', $output );


	}
	
	function write( $output, $post ) {
		
		$filename = ( get_post_type( $post ) == 'post' ) ? date( 'Y-m-d' ) . '-' . $post->post_name . '.md': $post->post_name . '.md';
		$prefix = ( get_post_type( $post ) == 'post' ) ? '_posts/' : '';
		file_put_contents( $this->dir . $prefix . $filename, $output );
		
	}
	
	function zip() {
		
		//create zip
		$zip = new ZipArchive();
		$zip->open( $this->zip, ZIPARCHIVE::CREATE );
		
		$this->_zip( $this->dir . '_config.yml', $zip );
		$this->_zip( $this->dir . '*.md', $zip );
		$this->_zip( $this->dir . '_posts/*.md', $zip );			
		$zip->close();
		
	}
	
	function _zip( $q, &$zip ) {
		
		//loop through all files in directory
		foreach ( glob( $q ) as $path ) {

			//make path within zip relative to zip base, not server root
			$local_path = str_replace( $this->dir, $this->zip_folder, $path );

			//add file
			$zip->addFile( realpath( $path ), $local_path );
		
		}
		
	}
	
	function send() {
		
		//send headers
		header( 'Content-Type: application/zip' );
		header( "Content-Disposition: attachment; filename=jekyll-export.zip" );
		header( 'Content-Length: ' . filesize( $this->zip ) );
		
		//read file
		readfile( $this->zip );
		    	
	}
	
	function __destruct( ) {

		foreach ( glob( $this->dir . '*' ) as $file )
			unlink( $file );
		
		rmdir( $this->dir );
		unlink( $this->zip );

	}

	
}

$je = new Jekyll_Export();
$je->convert();