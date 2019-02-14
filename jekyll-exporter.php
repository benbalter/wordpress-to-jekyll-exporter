<?php
/**
 * Exports WordPress posts, pages, and options as YAML files parsable by Jekyll
 *
 * @package    JekyllExporter
 * @author     Ben Balter <ben.balter@github.com>
 * @copyright  2012-2019 Ben Balter
 * @license    GPLv3
 * @link       https://github.com/benbalter/wordpress-to-jekyll-exporter/
 *
 * @wordpress-plugin
 * Plugin Name: WordPress to Jekyll Exporter
 * Plugin URI:  https://github.com/benbalter/wordpress-to-jekyll-exporter/
 * Description: Exports WordPress posts, pages, and options as YAML files parsable by Jekyll
 * Version:     2.3.0
 * Author:      Ben Balter
 * Author URI:  http://ben.balter.com
 * Text Domain: jekyll-export
 * License:     GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * Copyright 2012-2019 Ben Balter  (email : Ben@Balter.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

if ( version_compare( PHP_VERSION, '5.3.0', '<' ) ) {
	wp_die( 'Jekyll Export requires PHP 5.3 or later' );
}

require_once dirname( __FILE__ ) . '/lib/cli.php';
require_once dirname( __FILE__ ) . '/vendor/autoload.php';

/**
 * Class Jekyll_Export
 *
 * @package    JekyllExporter
 * @author     Ben Balter <ben.balter@github.com>
 * @copyright  2012-2019 Ben Balter
 * @license    GPLv3
 * @link       https://github.com/benbalter/wordpress-to-jekyll-exporter/
 */
class Jekyll_Export {

	/**
	 * Strings to strip from option keys on export
	 *
	 * @var $rename_options
	 */
	public $rename_options = array( 'site', 'blog' );

	/**
	 * Array of wp_options value to convert to _config.yml
	 *
	 * @var $options
	 */
	public $options = array(
		'name',
		'description',
		'url',
	);

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

		if ( get_current_screen()->id !== 'export' ) {
			return;
		}

		if ( ! isset( $_GET['type'] ) || 'jekyll' !== $_GET['type'] ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

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

		$posts = wp_cache_get( 'jekyll_export_posts' );
		if ( $posts ) {
			return $posts;
		}

		$posts      = array();
		$post_types = apply_filters( 'jekyll_export_post_types', array( 'post', 'page', 'revision' ) );

		/**
		 * WordPress style rules don't let us interpolate a string before passing it to
		 * $wpdb->prepare, but I can't find any other way to do an "IN" query
		 * So query each post_type individually and merge the IDs
		 */
		foreach ( $post_types as $post_type ) {
			$ids   = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s", $post_type ) );
			$posts = array_merge( $posts, $ids );
		}

		wp_cache_set( 'jekyll_export_posts', $posts );
		return $posts;

	}

	/**
	 * Convert a posts meta data (both post_meta and the fields in wp_posts) to key value pairs for export
	 *
	 * @param Post $post the post.
	 */
	function convert_meta( $post ) {

		$output = array(
			'id'      => $post->ID,
			'title'   => get_the_title( $post ),
			'date'    => get_the_date( 'c', $post ),
			'author'  => get_userdata( $post->post_author )->display_name,
			'excerpt' => $post->post_excerpt,
			'layout'  => get_post_type( $post ),
			'guid'    => $post->guid,
		);

		// Preserve exact permalink, since Jekyll doesn't support redirection.
		if ( 'page' !== $post->post_type ) {
			$output['permalink'] = str_replace( home_url(), '', get_permalink( $post ) );
		}

		// Convert traditional post_meta values, hide hidden values.
		foreach ( get_post_custom( $post->ID ) as $key => $value ) {

			if ( substr( $key, 0, 1 ) === '_' ) {
				continue;
			}

			$output[ $key ] = $value;

		}

		$post_thumbnail_id = get_post_thumbnail_id( $post );

		if ( $post_thumbnail_id ) {
			$post_thumbnail_src = wp_get_attachment_image_src( $post_thumbnail_id, 'post-thumbnail' );

			if ( $post_thumbnail_src ) {
				$output['image'] = str_replace( home_url(), '', $post_thumbnail_src[0] );
			}
		}

		$output = apply_filters( 'jekyll_export_meta', $output );
		return $output;
	}


	/**
	 * Convert post taxonomies for export
	 *
	 * @param Post $post the Post object.
	 * @return Array an array of converted terms
	 */
	function convert_terms( $post ) {

		$output = array();
		foreach ( get_taxonomies(
			array(
				'object_type' => array( get_post_type( $post ) ),
			)
		) as $tax ) {

			$terms = get_the_terms( $post, $tax );

			// Convert tax name for Jekyll.
			switch ( $tax ) {
				case 'post_tag':
					$tax = 'tags';
					break;
				case 'category':
					$tax = 'categories';
					break;
			}

			if ( 'post_format' === $tax ) {
				$output['format'] = get_post_format( $post );
			} elseif ( is_array( $terms ) ) {
				$output[ $tax ] = wp_list_pluck( $terms, 'name' );
			}
		}

		$output = apply_filters( 'jekyll_export_terms', $output );
		return $output;
	}

	/**
	 * Convert the main post content to Markdown.
	 *
	 * @param Post $post the post to Convert.
	 * @return String the converted post content
	 */
	function convert_content( $post ) {

		// check if jetpack markdown is available.
		if ( class_exists( 'WPCom_Markdown' ) ) {
			$wpcom_markdown_instance = WPCom_Markdown::get_instance();

			if ( $wpcom_markdown_instance && $wpcom_markdown_instance->is_posting_enabled() ) {
				// jetpack markdown is available so just return it.
				$content = apply_filters( 'edit_post_content', $post->post_content, $post->ID );

				return $content;
			}
		}

		$content   = apply_filters( 'the_content', $post->post_content );
		$converter = new Markdownify\ConverterExtra( Markdownify\Converter::LINK_IN_PARAGRAPH );
		$markdown  = $converter->parseString( $content );

		if ( strpos( $markdown, '[]: ' ) !== false ) {
			// faulty links; return plain HTML.
			$content = apply_filters( 'jekyll_export_html', $content );
			$content = apply_filters( 'jekyll_export_content', $content );
			return $content;
		}

		$markdown = apply_filters( 'jekyll_export_markdown', $markdown );
		$markdown = apply_filters( 'jekyll_export_content', $markdown );
		return $markdown;
	}

	/**
	 * Loop through and convert all posts to MD files with YAML headers
	 */
	function convert_posts() {
		global $post;

		foreach ( $this->get_posts() as $post_id ) {
			$post = get_post( $post_id );
			setup_postdata( $post );

			$meta = array_merge( $this->convert_meta( $post ), $this->convert_terms( $post_id ) );

			// remove falsy values, which just add clutter.
			foreach ( $meta as $key => $value ) {
				if ( ! is_numeric( $value ) && ! $value ) {
					unset( $meta[ $key ] );
				}
			}

			// Jekyll doesn't like word-wrapped permalinks.
			$output = Spyc::YAMLDump( $meta, false, 0 );

			$output .= "---\n";
			$output .= $this->convert_content( $post );
			$this->write( $output, $post );
		}

	}

	/**
	 * Callback to modify the filesystem filter
	 */
	function filesystem_method_filter() {
		return 'direct';
	}

	/**
	 * Initialize the temporary directory
	 */
	function init_temp_dir() {
		global $wp_filesystem;

		add_filter( 'filesystem_method', array( &$this, 'filesystem_method_filter' ) );

		WP_Filesystem();

		// When on Azure Web App use %HOME%\temp\ to avoid weird default temp folder behavior.
		// For more information see https://github.com/projectkudu/kudu/wiki/Understanding-the-Azure-App-Service-file-system.
		$temp_dir = ( getenv( 'WEBSITE_SITE_NAME' ) !== false ) ? ( getenv( 'HOME' ) . DIRECTORY_SEPARATOR . 'temp' ) : get_temp_dir();
		$wp_filesystem->mkdir( $temp_dir );
		$temp_dir = realpath( $temp_dir ) . DIRECTORY_SEPARATOR;

		$this->dir = $temp_dir . 'wp-jekyll-' . md5( time() ) . DIRECTORY_SEPARATOR;
		$this->zip = $temp_dir . 'wp-jekyll.zip';

		$wp_filesystem->mkdir( $this->dir );
		$wp_filesystem->mkdir( $this->dir . '_posts/' );
		$wp_filesystem->mkdir( $this->dir . '_drafts/' );
		$wp_filesystem->mkdir( $this->dir . 'wp-content/' );
	}

	/**
	 * Main function, bootstraps, converts, and cleans up
	 */
	function export() {
		do_action( 'jekyll_export' );
		ob_start();
		$this->init_temp_dir();
		$this->convert_options();
		$this->convert_posts();
		$this->convert_uploads();
		$this->zip();
		ob_end_clean();
		$this->send();
		$this->cleanup();
	}


	/**
	 * Convert options table to _config.yml file
	 */
	function convert_options() {

		global $wp_filesystem;

		$options = wp_load_alloptions();
		foreach ( $options as $key => &$option ) {

			if ( substr( $key, 0, 1 ) === '_' ) {
				unset( $options[ $key ] );
			}

			// Strip site and blog from key names, since it will become site when in Jekyll.
			foreach ( $this->rename_options as $rename ) {

				$len = strlen( $rename );
				if ( substr( $key, 0, $len ) !== $rename ) {
					continue;
				}

				$this->rename_key( $options, $key, substr( $key, $len ) );

			}

			$option = maybe_unserialize( $option );

		}

		foreach ( $options as $key => $value ) {

			if ( ! in_array( $key, $this->options, true ) ) {
				unset( $options[ $key ] );
			}
		}

		$output = Spyc::YAMLDump( $options );

		// strip starting "---".
		$output = substr( $output, 4 );

		$wp_filesystem->put_contents( $this->dir . '_config.yml', $output );

	}


	/**
	 * Write file to temp dir
	 *
	 * @param String $output the post content.
	 * @param Post   $post the Post object.
	 */
	function write( $output, $post ) {

		global $wp_filesystem;

		if ( get_post_status( $post ) !== 'publish' ) {
			$filename = '_drafts/' . sanitize_file_name( get_page_uri( $post->id ) . '-' . ( get_the_title( $post->id ) ) . '.md' );
		} elseif ( get_post_type( $post ) === 'page' ) {
			$filename = get_page_uri( $post->id ) . '.md';
		} else {
			$filename = '_' . get_post_type( $post ) . 's/' . date( 'Y-m-d', strtotime( $post->post_date ) ) . '-' . sanitize_file_name( $post->post_name ) . '.md';
		}

		$wp_filesystem->mkdir( $this->dir . dirname( $filename ) );
		$wp_filesystem->put_contents( $this->dir . $filename, $output );
	}

	/**
	 * Creates a zip archive of the given folder
	 *
	 * @param String $source the source directory to zip.
	 * @param String $destination the path to output the zip.
	 */
	function zip_folder( $source, $destination ) {

		if ( ! file_exists( $source ) ) {
			die( 'file does not exist: ' . esc_html( $source ) );
		}

		$source = realpath( $source );

		$zip = new ZipArchive();
		if ( ! $zip->open( $destination, ZipArchive::CREATE | ZIPARCHIVE::OVERWRITE ) ) {
			die( 'Cannot open zip archive: ' . esc_html( $destination ) );
		}

		$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $source ), RecursiveIteratorIterator::SELF_FIRST );

		foreach ( $files as $file ) {
			// Ignore "." and ".." folders.
			if ( in_array( substr( $file, strrpos( $file, DIRECTORY_SEPARATOR ) + 1 ), array( '.', '..' ), true ) ) {
				continue;
			}

			if ( is_dir( $file ) === true ) {
				$zip->addEmptyDir( substr( realpath( $file ), strlen( $source ) + 1 ) );
			} elseif ( is_file( $file ) === true ) {
				$zip->addFile( $file, substr( realpath( $file ), strlen( $source ) + 1 ) );
			}
		}

		return $zip->close();
	}

	/**
	 * Zip temp dir
	 */
	function zip() {
		$this->zip_folder( $this->dir, $this->zip );
	}

	/**
	 * Send headers and zip file to user
	 */
	function send() {

		// Send headers.
		@header( 'Content-Type: application/zip' );
		@header( 'Content-Disposition: attachment; filename=jekyll-export.zip' );
		@header( 'Content-Length: ' . filesize( $this->zip ) );

		// Read file.
		flush();
		readfile( $this->zip );

	}


	/**
	 * Clear temp files
	 */
	function cleanup() {

		global $wp_filesystem;

		$wp_filesystem->delete( $this->dir, true );
		$wp_filesystem->delete( $this->zip );

	}


	/**
	 * Rename an assoc. array's key without changing the order
	 *
	 * @param Array  $array the Array.
	 * @param String $from the original key.
	 * @param String $to the resulting key.
	 * @return The New Array
	 */
	function rename_key( &$array, $from, $to ) {

		$keys  = array_keys( $array );
		$index = array_search( $from, $keys, true );

		if ( false === $index ) {
			return;
		}

		$keys[ $index ] = $to;
		$array          = array_combine( $keys, $array );

	}

	/**
	 * Convert uploads to static files in the resulting site
	 */
	function convert_uploads() {
		$upload_dir = wp_upload_dir();
		$source     = $upload_dir['basedir'];
		$site_url   = trailingslashit( set_url_scheme( get_site_url(), 'http' ) );
		$base_url   = set_url_scheme( $upload_dir['baseurl'], 'http' );
		$dest       = $this->dir . str_replace( $site_url, '', $base_url );
		$this->copy_recursive( $source, $dest );
	}

	/**
	 * Copy a file, or recursively copy a folder and its contents
	 *
	 * @author      Aidan Lister <aidan@php.net>
	 * @version     1.0.1
	 * @link        http://aidanlister.com/2004/04/recursively-copying-directories-in-php/
	 * @param       string $source    Source path.
	 * @param       string $dest      Destination path.
	 * @return      bool     Returns TRUE on success, false on failure
	 */
	function copy_recursive( $source, $dest ) {

		global $wp_filesystem;

		// Check for symlinks.
		if ( is_link( $source ) ) {
			return symlink( readlink( $source ), $dest );
		}

		// Simple copy for a file.
		if ( is_file( $source ) ) {
			return $wp_filesystem->copy( $source, $dest );
		}

		// Make destination directory.
		if ( ! is_dir( $dest ) ) {
			$wp_filesystem->mkdir( $dest );
		}

		// Loop through the folder.
		$dir = dir( $source );
		while ( $entry = $dir->read() ) {
			// Skip pointers.
			if ( '.' === $entry || '..' === $entry ) {
				continue;
			}

			// Deep copy directories.
			$this->copy_recursive( "$source/$entry", "$dest/$entry" );
		}

		// Clean up.
		$dir->close();
		return true;

	}

}

global $jekyll_export;
$jekyll_export = new Jekyll_Export();
