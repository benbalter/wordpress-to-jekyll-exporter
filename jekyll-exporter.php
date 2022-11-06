<?php
/**
 * Exports WordPress posts, pages, and options as YAML files parsable by Jekyll
 *
 * @package    JekyllExporter
 * @author     Ben Balter <ben@balter.com>
 * @copyright  2013-2022 Ben Balter
 * @license    GPLv3
 * @link       https://github.com/benbalter/wordpress-to-jekyll-exporter/
 *
 * @wordpress-plugin
 * Plugin Name: WordPress to Jekyll Exporter
 * Plugin URI:  https://github.com/benbalter/wordpress-to-jekyll-exporter/
 * Description: Exports WordPress posts, pages, and options as YAML files parsable by Jekyll
 * Version:     2.3.6
 * Author:      Ben Balter
 * Author URI:  http://ben.balter.com
 * Text Domain: jekyll-export
 * License:     GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * Copyright 2012-2022 Ben Balter  (email : Ben@Balter.com)
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

// Extra options

$config = array();

// $config["site_url"] = "https://mywebsite.fr/"; // Destination site URL
// $config['force_dest_dir'] = "_export/"; // Force destination directory to relative one (without temp folder, will deactivate zip)

$config['forceHtml'] = false; // true;	// Force export to HTML

// Skip some parts of the script that you do not need
// $config['skip_options'] = true; 
// $config['skip_posts'] = true; 
// $config['skip_uploads'] = true; 
// $config['skip_comments'] = true; 

$config['comment_target_data'] = true;		// Export comments as data files
// $config['comment_target_pages'] = true;	// Export comments as jekyll pages
// $config['comment_target_github'] = true; // Export comments to GitHub discussions (for use with Giscus)

// To export comments to GitHub for Giscus (https://giscus.app)
// $config['gh_token'] = "gho_xxxx";					 // Get token from command line: gh auth token 
// $config['gh_repo'] = "R_kgDOIVZ3BQ";				 // Get values from giscus config page
// $config['category_id'] = "DIC_kwDOIVZ3Bc4CSRMx"; // Get values from giscus config page



if ( version_compare( PHP_VERSION, '5.3.0', '<' ) ) {
	wp_die( 'Jekyll Export requires PHP 5.3 or later' );
}

require_once dirname( __FILE__ ) . '/lib/cli.php';
require_once dirname( __FILE__ ) . '/vendor/autoload.php';

use League\HTMLToMarkdown\HtmlConverter;
use League\HTMLToMarkdown\Converter\TableConverter;
use Symfony\Component\Yaml\Yaml;


// https://gist.github.com/dunglas/05d901cb7560d2667d999875322e690a
function graphql_query(string $endpoint, string $query, array $variables = [], ?string $token = null): array
{
    $headers = ['Content-Type: application/json', 'User-Agent: Dunglas\'s minimal GraphQL client'];
    if (null !== $token) {
        $headers[] = "Authorization: bearer $token";
    }

    if (false === $data = @file_get_contents($endpoint, false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => $headers,
            'content' => json_encode(['query' => $query, 'variables' => $variables]),
        ]
    ]))) {
        $error = error_get_last();
        throw new \ErrorException($error['message'], $error['type']);
    }

    return json_decode($data, true);
}


function github_query(string $query, array $variables = []) {
	global $config;
	$ret = null;
	if ($config['gh_token']) { 
		$ret = graphql_query('https://api.github.com/graphql', $query, $variables, $config['gh_token']);
		sleep(5);	// Delay because Github does not like mutations too quick
	}
	return $ret;
}


function gh_create_discussion($title, $content) {
	global $config;

	$query = <<<'GRAPHQL'
		mutation MyMutation($repositoryId: ID!, $title: String!, $body: String!, $categoryId: ID!) { 
			createDiscussion(input: {
				repositoryId: $repositoryId, 
				title: $title, 
				body: $body, 
				categoryId: $categoryId
			}) 
		{ discussion {  id  } } }
		GRAPHQL;

	$vars = [
		'repositoryId' => $config['gh_repo'], 
		'title' => $title, 
		'body' => $content, 
		'categoryId' => $config['category_id']
	];

	$r = github_query($query, $vars);

	$discussion_id = $r['data']['createDiscussion']['discussion']['id'] ?? null;
	
	if ($discussion_id == null) { var_dump(">>> Discussion", $query, $vars, $r, $discussion_id); }

	return $discussion_id;
}


function gh_create_discussion_comment($discussionId, $content, $parent_comment = null) {
	global $config;

	$query = <<<'GRAPHQL'
				mutation MyMutation($discussionId: ID!, $body: String!, $replyToId: ID ) {
					addDiscussionComment(
					input: {
						discussionId: $discussionId, 
						body: $body, 
						replyToId: $replyToId
					}) 
					{ comment { id }}
				}
		GRAPHQL;

	$vars = [
		'discussionId' => $discussionId, 
		'body' => $content, 
		'replyToId' => $parent_comment
	];
	
	$r = github_query($query, $vars);

	$comment_id = $r['data']['addDiscussionComment']['comment']['id'] ?? null;

	//if (!$comment_id) { var_dump(">> Comment", $query, $vars, $r); }

	return $comment_id;
}

/**
 * Class Jekyll_Export
 *
 * @package    JekyllExporter
 * @author     Ben Balter <ben.balter@github.com>
 * @copyright  2012-2022 Ben Balter
 * @license    GPLv3
 * @link       https://github.com/benbalter/wordpress-to-jekyll-exporter/
 */
class Jekyll_Export {

	public $gh_discussions = array(); // wordpress id => github id
	public $gh_comments = array(); // wordpress id => github id

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
		$post_types = apply_filters( 'jekyll_export_post_types', array( 'post', 'page' /*, 'revision'*/ ) );

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
			'post_id'      => $post->ID,
			'title'   => get_the_title( $post ),
			'date'    => get_the_date( 'c', $post ),
			'last_modified_at'    => get_the_modified_date( 'c', $post ),
			'author'  => get_userdata( $post->post_author )->display_name,
			'excerpt' => $post->post_excerpt,
			'layout'  => get_post_type( $post ),
			'guid'    => $post->guid,
			'slug'   => $post->post_name,
		);

		// Preserve exact permalink, since Jekyll doesn't support redirection.
		$ignore_permalink = array(); // array('page')
		if ( !in_array($post->post_type, $ignore_permalink) ) {
			$output['permalink'] = str_replace( home_url(), '', get_permalink( $post ) );
		}

		// Convert traditional post_meta values, hide hidden values.
		$ignore_list = array(
			'_*',
			'ocean_*',
			'classic-editor-remember',
			'osh_disable_topbar_sticky',
			'osh_disable_header_sticky',
			'osh_sticky_header_style',
			'ampforwp-amp-on-off',
		);

		foreach ( get_post_custom( $post->ID ) as $key => $value ) {

			$ignore = false;
			foreach ($ignore_list as $ignore_item) {
				if (fnmatch($ignore_item, $key)) { $ignore = true; }
			}

			if (is_array($value) && (count($value) == 1)) { $value = $value[0]; }
			if (!$ignore) $output[ $key ] = $value;
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
			/*array(
				'object_type' => array( get_post_type( $post ) ),
			)*/
		) as $tax ) {

			$terms = get_the_terms( $post, $tax );

			if ($terms != false) {
				// Convert tax name for Jekyll.
				switch ( $tax ) {
					case 'post_tag':
						$tax = 'tags';
						break;
					case 'category':
						$tax = 'categories';
						foreach ($terms as $key => $value) {
							$tr_tax = wp_get_object_terms($value->term_id, 'term_translations');
							$translations = unserialize($tr_tax[0]->description);
							$cat_fr_id = $translations['fr'];
							if ($cat_fr_id > 0) { 
								$cat_fr = get_term($cat_fr_id + 0);
								$terms[$key] = $cat_fr;
							}
						}
						break;
					
					// Extract Polylang
					case 'language':
						$tax = 'lang';
						if (count($terms) == 1) {
							$terms = str_replace('pll_','', $terms[0]->slug);
						}
						break;
					case 'post_language':
						$tax = 'lang';
						if (count($terms) == 1) {
							$terms = str_replace('pll_','', $terms[0]->slug);
						}
						break;
					case 'post_translations':
						$tax = 'lang-translations';
						if (count($terms) == 1) {
							$output['lang-ref'] = $terms[0]->name;
							$translations = unserialize($terms[0]->description);
							foreach ($translations as $key => $value) {
								$tr_post = get_post($value + 0);
								$translations[$key] = $tr_post->post_name;
							}
							$terms = $translations;
						}
						break;
				}

				if ( 'post_format' === $tax ) {
					$output['format'] = get_post_format( $post );
				} elseif ( is_array( $terms ) && ($tax != 'lang-translations')) {
					$output[ $tax ] = wp_list_pluck( $terms, 'name' );
				} else {
					$output[ $tax ] = $terms;
				}
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
	function convert_content( $post_content ) {
		/*
		// check if jetpack markdown is available.
		if ( class_exists( 'WPCom_Markdown' ) ) {
			$wpcom_markdown_instance = WPCom_Markdown::get_instance();

			if ( $wpcom_markdown_instance && $wpcom_markdown_instance->is_posting_enabled() ) {
				// jetpack markdown is available so just return it.
				$content = apply_filters( 'edit_post_content', $post->post_content, $post->ID );

				return $content;
			}
		}
		*/

		global $config;

		$content           = apply_filters( 'the_content', $post_content );
		$converter_options = apply_filters( 'jekyll_export_markdown_converter_options', array( 'header_style' => 'atx' ) );
		$converter         = new HtmlConverter( $converter_options );
		$converter->getEnvironment()->addConverter( new TableConverter() );

		$markdown = $converter->convert( $content );

		if ( ( strpos( $markdown, '[]: ' ) !== false) || ($config['forceHtml']) ) {
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
		//global $post;  // was causing the issue, php was mixing posts
		
		foreach (  $this->get_posts() as $post_id ) {
			$post = get_post( $post_id );

			setup_postdata( $post );

			//if ($post->ID != $post_id)  var_dump($post);

			$meta = array_merge( $this->convert_meta( $post ), $this->convert_terms( $post_id ) );

			// remove falsy values, which just add clutter.
			foreach ( $meta as $key => $value ) {
				if ( ! is_numeric( $value ) && ! $value ) {
					unset( $meta[ $key ] );
				}
			}

			$content = $post->post_content;

			$output  = "---\n";
			$output .= Yaml::dump( $meta );
			$output .= "---\n\n";
			$output .= $this->convert_content( $content );
			$this->write( $output, $post );
		}
	}

	function convert_comment_github($header, $content, $post, $comment) {
		global $config;

		$post_id = $post->ID;

		// Need to get meta of post
		$meta = array_merge( $this->convert_meta( $post ), $this->convert_terms( $post_id ) );

		// Create discussion if not already created (TODO: search github instead of cache)
		if (!array_key_exists($post_id, $this->gh_discussions)) {
			$discussion_id = gh_create_discussion($meta['permalink'],
				get_the_excerpt($post) . "\n\n" .
				$config["site_url"] . $meta['permalink']);
			$this->gh_discussions[$post_id] = $discussion_id;
		}

		// Get parent comment id (TODO: search github instead of cache)
		$comment_parent_id = null;
		if ($comment->comment_parent != '0') {
			if (array_key_exists($comment->comment_parent, $this->gh_comments)) {
				$comment_parent_id = $this->gh_comments[$comment->comment_parent];
			} else {
				// parent comment should have been created before, not normal
				printf("Parent comment not found:" . $comment->comment_parent);
			}
		}


		$migrated_text = "_From **{$comment->comment_author}** on **{$comment->comment_date}** (Migrated from Wordpress):_\n\n";
		// Basic multilang comment support (fr only)
		if ($meta['lang'] == 'fr') $migrated_text = "_De **{$comment->comment_author}** le **{$comment->comment_date}** (Migré depuis Wordpress):_\n\n";
		
		$comment_id = gh_create_discussion_comment(
			$this->gh_discussions[$post_id],
			"<!-- WordPress ID: \n$header -->\n" . $migrated_text . 	$content, 
			$comment_parent_id
		);
		$this->gh_comments[$comment->comment_ID] = $comment_id;
	}

	/**
	 * Loop through and convert all comments to MD files with YAML headers
	 */

	function convert_comment($post, $comment) {
		global $config;

		$comment_meta =  array(
			'comment_ID'      => $comment->comment_ID,
			'comment_author'      => $comment->comment_author,
			'comment_author_url'      => $comment->comment_author_url,
			'comment_date'      => $comment->comment_date, // = '0000-00-00 00:00:00';
			'comment_date_gmt'      => $comment->comment_date_gmt, // = '0000-00-00 00:00:00';
			'comment_type'      => $comment->comment_type,
			'comment_parent'      => $comment->comment_parent,
			'post_id'      => $post->ID,
			// Other comments metadata available (warning you may expose personal data)
			// 'comment_author_email'      => $comment->comment_author_email,
			// 'comment_author_IP'      => $comment->comment_author_IP,
			// 'comment_karma'      => $comment->comment_karma,
			// 'comment_approved'      => $comment->comment_approved,
			// 'comment_agent'      => $comment->comment_agent,
			// 'user_id'      => $user_id,
		);
		
		$header   = "---\n";
		$header .= Yaml::dump( $comment_meta );
		$header .= "---\n\n";

		$content = $this->convert_content( $comment->comment_content );

		if ($config['comment_target_github']) {
			$this->convert_comment_github($header, $content, $post, $comment);
		}

		if ($config['comment_target_pages']) {
			$this->write($header . $content, $post, $comment);
		}

		$this->convert_comment_children($post, $comment->comment_ID);
	}

	function convert_comment_children($post, $comment_parent) {

		$comments = get_comments(array(
			'post_id' =>  $post->ID, 
			'type' => 'comment', 
			'parent' => $comment_parent,
			'orderby'=>'comment_date',
            'order'=>'ASC' 
		));

		foreach( $comments as $comment ) {
			$this->convert_comment($post, $comment);
		}

	}

	function convert_comment_data($post) {

		global $wp_filesystem;

		$comments = get_comments(array(
			'post_id' =>  $post->ID, 
			'type' => 'comment', 
			'orderby'=>'comment_date',
            'order'=>'ASC' 
		));

		$comments_list = [];

		foreach( $comments as $comment ) {
			$comment_data = [
				'ID'      => $comment->comment_ID,
				'post_id'      => $post->ID,
				'author'      => $comment->comment_author,
				'date'      => $comment->comment_date, // = '0000-00-00 00:00:00';
			];
			$comment_data['comment'] =  $comment->comment_content;
			if ($comment->comment_parent) $comment_data['parent'] = $comment->comment_parent;
			if ($comment->comment_author_url) $comment_data['author_url'] = $comment->comment_author_url;

			$comments_list[] = $comment_data;
		}

		if (count($comments_list) > 0) {

			$filename = '_data/comments/' . $post->post_name . '.yml';

			$output = Yaml::dump( $comments_list );

			$wp_filesystem->put_contents( $this->dir . $filename,  $output);		
		}

	}


	function convert_comments() {
		global $wp_filesystem;
		global $config;

		//global $post;  // was causing the issue, php was mixing posts

		$ext = ($config['forceHtml']) ? '.html' : '.md';

		$gql = '';
		
		foreach (  $this->get_posts() as $post_id ) {
			$post = get_post( $post_id );
			setup_postdata( $post );

			// Convert to pages & github
			if ($config['comment_target_pages'] || $config['comment_target_github']) {
				$this->convert_comment_children($post, '0');
			}

			// Convert to data
			if ($config['comment_target_data']) {
				$this->convert_comment_data($post);
			}
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
		global $config;

		add_filter( 'filesystem_method', array( &$this, 'filesystem_method_filter' ) );

		WP_Filesystem();

		// When on Azure Web App use %HOME%\temp\ to avoid weird default temp folder behavior.
		// For more information see https://github.com/projectkudu/kudu/wiki/Understanding-the-Azure-App-Service-file-system.
		
		$temp_dir = ( getenv( 'WEBSITE_SITE_NAME' ) !== false ) ? ( getenv( 'HOME' ) . DIRECTORY_SEPARATOR . 'temp' ) : get_temp_dir();
		$wp_filesystem->mkdir( $temp_dir );
		$temp_dir = realpath( $temp_dir ) . DIRECTORY_SEPARATOR;

		$this->dir = $temp_dir . 'wp-jekyll-' . md5( time() ) . DIRECTORY_SEPARATOR;
		if ($config['force_dest_dir']) $this->dir = $config['force_dest_dir']; 
		$this->zip = $temp_dir . 'wp-jekyll.zip';

		$wp_filesystem->mkdir( $this->dir );
		$wp_filesystem->mkdir( $this->dir . '_posts/' );
		$wp_filesystem->mkdir( $this->dir . '_pages/' );
		$wp_filesystem->mkdir( $this->dir . '_drafts/' );
		//$wp_filesystem->mkdir( $this->dir . 'wp-content/' );
		if ($config['comment_target_pages']) {
			$wp_filesystem->mkdir( $this->dir . '_comments/' );
			$wp_filesystem->mkdir( $this->dir . '_comments/_posts/' );
			$wp_filesystem->mkdir( $this->dir . '_comments/_pages/' );
		}
		if ($config['comment_target_data']) {
			$wp_filesystem->mkdir( $this->dir . '_data/' );
			$wp_filesystem->mkdir( $this->dir . '_data/comments/' );
		}
	}

	/**
	 * Main function, bootstraps, converts, and cleans up
	 */
	function export() {
		global $config;

		do_action( 'jekyll_export' );
		ob_start();
		$this->init_temp_dir();
		if (!$config['skip_options']) $this->convert_options();
		if (!$config['skip_posts']) $this->convert_posts();
		if (!$config['skip_uploads']) $this->convert_uploads();
		if (!$config['skip_comments']) $this->convert_comments();
		ob_end_clean();
		if (!$config['force_dest_dir']) {
			$this->zip();
			$this->send();
			$this->cleanup();
		}
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

		$output = Yaml::dump( $options );

		$wp_filesystem->put_contents( $this->dir . '_config.yml', $output );
	}


	/**
	 * Write file to temp dir
	 *
	 * @param String $output the post content.
	 * @param Post   $post the Post object.
	 */
	function write( $output, $post, $comment = null ) {

		global $wp_filesystem;
		global $config;

		$ext = ($config['forceHtml']) ? '.html' : '.md';

		if ( get_post_type( $post ) === 'revision' ) {
			$filename = '_revisions/' . sanitize_file_name( get_page_uri( $post ) . '-' . ( get_the_title( $post ) ) );
		} elseif ( ! in_array( get_post_status( $post ), array( 'publish', 'future' ), true ) ) {
			$filename = '_drafts/' . sanitize_file_name( get_page_uri( $post ) . '-' . ( get_the_title( $post ) ) );
		} elseif ( get_post_type( $post ) === 'page' ) {
			$filename = '_pages/' . get_page_uri( $post );
		} else {
			$filename = '_' . get_post_type( $post ) . 's/' . gmdate( 'Y-m-d', strtotime( $post->post_date ) ) . '-' . sanitize_file_name( $post->post_name );
		}

		$wp_filesystem->mkdir( $this->dir . dirname( $filename ) );

		if ($comment) {
			$filename = "_comments/" . $filename;
			$wp_filesystem->mkdir( $this->dir . dirname( $filename ) );
			$filename = $filename . '/' . gmdate( 'Y-m-d', strtotime( $comment->comment_date ) ) . '-' . sanitize_file_name( $comment->comment_type ) . '-' . $comment->comment_ID  . '-' . sanitize_file_name( $comment->comment_author );
			$wp_filesystem->mkdir( $this->dir . dirname( $filename ) );
		}

		$wp_filesystem->put_contents( $this->dir . $filename . $ext, $output );
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

		// Avoid copying the output of this plugin and causing infinite recursion.
		if ( strpos( $source, '/wp-jekyll-' ) !== false ) {
			return true;
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
