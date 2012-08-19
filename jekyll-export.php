<?php
/*
Plugin Name: WordPress to Jekyll Exporter
Description: Exports WordPress posts, pages, and options as YAML files parsable by Jekyll
Version: 1.0
Author: Benjamin J. Balter
Author URI: http://ben.balter.com
License: GPLv3 or Later

Copyright 2012  Benjamin J. Balter  (email : Ben@Balter.com)

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

	private $zip_folder = 'jekyll-export/'; //folder zip file extracts to
	
	public $rename_options = array( 'site', 'blog' ); //strings to strip from option keys on export
	
	public $options = array( 	//array of wp_options value to convert to _config.yml
								'name', 
								'description', 
								'url' 
							);
							
	public $posts = array( 	//array of wp_posts fields to convert to YAML front matter
							//will convert all post_meta and all taxonomies
							'author',
							'title',
							'excerpt',
					);
	
	public $extra_html_include = false; //should un-markdownify-able HTML be included or skipped?

	/**
	 * Hook into WP Core
	 */
	function __construct() {

		add_action( 'admin_menu', array( &$this, 'register_menu' ) );
		add_action( 'current_screen', array( &$this, 'callback' ) );

	}

	/**
	 * Listens for page callback, intercepts and runs export
	 */
	function callback() {

		if ( get_current_screen()->id != 'export' )
			return;
			
		if ( !isset( $_GET['type'] ) || $_GET['type'] != 'jekyll' )
			return;

		if ( !current_user_can( 'manage_options' ) )
			return;

		$this->export();
		exit();

	}


	/**
	 * Add menu option to tools list
	 */
	function register_menu() {

		add_management_page( __( 'Export to Jekyll', 'jekyll-export' ), __( 'Export to Jekyll', 'jekyll-export' ), 'manage_options', 'export.php?type=jekyll' );

	}


	/**
	 * Get an array of all post and page IDs
	 * Note: We don't use core's get_posts as it doesn't scale as well on large sites
	 */
	function get_posts() {

		global $wpdb;
		return $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_status = 'publish' AND post_type IN ('post', 'page' )" );

	}


	/**
	 * Convert a posts meta data (both post_meta and the fields in wp_posts) to key value pairs for export
	 */
	function convert_meta( $post ) {

		//convert non-content columns in wp_posts table
		foreach ( $post as $key => $value ) {

			if ( $key == 'post_content' )
				continue;
			
			//convert author from ID to display name
			if ( $key == 'post_author' )
				$value = get_userdata( $post->post_author )->display_name;
		
			//strip post_ from the key, as it will be page.foo in jekyll
			$key = str_replace( 'post_', '', $key );
			
			if ( !in_array( $key, $this->posts ) )
				continue;
				
			$output[ strtolower( $key)  ] = $value;

		}
		
		//force post_type -> layout for ease of use on the Jekyll side
		$output[ 'layout' ] = get_post_type( $post );

		//convert traditional post_meta values, hide hidden values
		foreach ( get_post_custom( $post ) as $key => $value ) {

			if ( substr( $key, 0, 1 ) == '_' )
				continue;

			$output[ $key ] = $value;

		}

		return $output;

	}


	/**
	 * Convert post taxonomies for export
	 */
	function convert_terms( $post ) {

		$output = array();
		foreach ( get_taxonomies( array( 'object_type' => array( get_post_type( $post ) ) ) ) as $tax ) {
		
			$terms = wp_get_post_terms( $post, $tax );
			
			//convert tax name for Jekyll
			if ( $tax == 'post_tag' )
				$tax = 'tags';
				
			$output[ $tax ] = wp_list_pluck( $terms, 'name' );
		
		}

		return $output;

	}


	/**
	 * Loop through and convert all posts to MD files with YAML headers
	 */
	function convert_posts() {

		foreach ( $this->get_posts() as $postID ) {
			$md = new Markdownify_Extra( null, false, $this->extra_html_include );
			$post = get_post( $postID );
			$meta = array_merge( $this->convert_meta( $post ), $this->convert_terms( $postID ) );
			$output = Spyc::YAMLDump($meta);
			$output .= "---\n";
			$body = $md->parseString( apply_filters( 'the_content', $post->post_content ) );
			$output .= $this->convert_links( $body );
			$this->write( $output, $post );
		}

	}


	/**
	 * Main function, bootstraps, converts, and cleans up
	 */
	function export() {

		if ( !class_exists( 'spyc' ) )
			require_once dirname( __FILE__ ) . '/includes/spyc.php';

		if ( !function_exists( 'Markdown' ) )
			require_once dirname( __FILE__ ) . '/includes/markdownify/markdownify_extra.php';
			
		$this->dir = sys_get_temp_dir() . '/wp-jekyll-' . md5( time() ) . '/';
		$this->zip = sys_get_temp_dir() . '/wp-jekyll.zip';
		mkdir( $this->dir );
		mkdir( $this->dir . '_posts/' );

		$this->convert_options();
		$this->convert_posts();
		$this->convert_uploads();
		$this->zip();
		$this->send();
		$this->cleanup();

	}


	/**
	 * Convert options table to _config.yml file
	 */
	function convert_options() {

		$options = wp_load_alloptions();
		foreach ( $options as $key => &$option ) {

			if ( substr( $key, 0, 1 ) == '_' )
				unset( $options[$key] );

			//strip site and blog from key names, since it will become site. when in Jekyll
			foreach ( $this->rename_options as $rename ) {

				$len = strlen( $rename );
				if ( substr( $key, 0, $len ) != $rename )
					continue;

				$this->rename_key( $options, $key, substr( $key, $len ) );

			}

			$option = maybe_unserialize( $option );

		}
		
		foreach ( $options as $key => $value ) {
			
			if ( !in_array( $key, $this->options ) )
				unset( $options[ $key ] );
				
		}

		$output = Spyc::YAMLDump( $options );

		//strip starting "---"
		$output = substr( $output, 4 );

		file_put_contents( $this->dir . '_config.yml', $output );

	}


	/**
	 * Write file to temp dir
	 */
	function write( $output, $post ) {

		if ( get_post_type( $post ) == 'page' ) {
			mkdir( $this->dir . $post->post_name );
			$filename = $post->post_name . '/index.md';
		} else {
			$filename = '_posts/' . date( 'Y-m-d', strtotime( $post->post_date ) ) . '-' . $post->post_name . '.md';
		}
				
		file_put_contents( $this->dir . $filename, $output );

	}


	/**
	 * Zip temp dir
	 */
	function zip() {

		//create zip
		$zip = new ZipArchive();
		$zip->open( $this->zip, ZIPARCHIVE::CREATE );
		$this->_zip( $this->dir, $zip );
		$zip->close();

	}


	/**
	 * Helper function to add a file to the zip
	 */
	function _zip( $dir, &$zip ) {
	
		//loop through all files in directory
		foreach ( glob( trailingslashit( $dir ) . '*' ) as $path ) {
			
			if ( is_dir( $path ) ) {
				$this->_zip( $path, $zip );
				continue;
			}
			
			//make path within zip relative to zip base, not server root
			$local_path = '/' . str_replace( $this->dir, $this->zip_folder, $path );

			//add file
			$zip->addFile( realpath( $path ), $local_path );

		}

	}


	/**
	 * Send headers and zip file to user
	 */
	function send() {

		//send headers
		header( 'Content-Type: application/zip' );
		header( "Content-Disposition: attachment; filename=jekyll-export.zip" );
		header( 'Content-Length: ' . filesize( $this->zip ) );

		//read file
		readfile( $this->zip );

	}


	/**
	 * Clear temp files
	 */
	function cleanup( ) {

		$this->rmdir_recursive( $this->dir );
		unlink( $this->zip );

	}


	/**
	 * Rename an assoc. array's key without changing the order
	 */
	function rename_key( &$array, $from, $to ) {

		$keys = array_keys( $array );
		$index = array_search( $from, $keys );

		if ( $index === false )
			return;

		$keys[ $index ] = $to;
		$array = array_combine( $keys, $array );


	}
	
	function rmdir_recursive( $dir ) {

   		foreach( glob($dir . '/*' ) as $file ) {
   		    if( is_dir( $file ) )
   		        $this->rmdir_recursive( $file );
   		    else
   		        unlink( $file );
   		}
   		
   		rmdir( $dir );
   	
   	}
   	
   	function convert_uploads() {
   	
   		$upload_dir = wp_upload_dir();
	   	$this->copy_recursive( $upload_dir['basedir'], $this->dir . str_replace( trailingslashit( get_home_url() ), '', $upload_dir['baseurl'] )  );
	   	
   	}
   	
	/**
	 * Copy a file, or recursively copy a folder and its contents
	 *
	 * @author      Aidan Lister <aidan@php.net>
	 * @version     1.0.1
	 * @link        http://aidanlister.com/2004/04/recursively-copying-directories-in-php/
	 * @param       string   $source    Source path
	 * @param       string   $dest      Destination path
	 * @return      bool     Returns TRUE on success, FALSE on failure
	 */
	function copy_recursive($source, $dest) {

	    // Check for symlinks
	    if ( is_link( $source ) ) { 
	        return symlink( readlink( $source ), $dest );
	    }
	    	     
	    // Simple copy for a file
	    if ( is_file( $source ) ) { 
	        return copy( $source, $dest );
	    }
	 
	    // Make destination directory
	    if ( !is_dir($dest) ) { 
	        mkdir($dest, null, true );
	    }

	    // Loop through the folder
	    $dir = dir($source);
	    while (false !== $entry = $dir->read()) {
	        // Skip pointers
	        if ($entry == '.' || $entry == '..') {
	            continue;
	        }
	 
	        // Deep copy directories
	        $this->copy_recursive("$source/$entry", "$dest/$entry");
	    }
	 
	    // Clean up
	    $dir->close();
	    return true;
	    
	}
	
	/**
	 * Manually convert links to markdown because markdownify does it in a really strange way
	 */
	function convert_links( $body ) {
	
		//disabeled for now
		return $body;
	
		//links = in body, footnotes = below body
		preg_match_all( '/\[([^\]]+?)\]\[([0-9]+)\]/', $body, $links, PREG_SET_ORDER );
		preg_match_all( '/\[([0-9]+)\]: (.+)/', $body, $footnotes, PREG_SET_ORDER );
		
		$find = array();
		$replace = array();
		
		//build find and replace array
		foreach ( $links as $key => $link ) {
			
			$find[] = '#' . preg_quote( $link[0], '#' ) . '#';
			$replace[] = "[{$link[1]}]({$footnotes[$key][2]})";
		}
		
		//clear out footnotes
		foreach ( $footnotes as $footnote ) {
			
			$find[] = '#' . preg_quote( $footnote[0], '#' ) . '#'; 
			$replace[] = '';
			
		}
		
		$body = trim( preg_replace( $find, $replace, $body ) );

		return $body;

	}
	
}


$je = new Jekyll_Export();