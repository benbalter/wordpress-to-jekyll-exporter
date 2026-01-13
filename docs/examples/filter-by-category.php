<?php
/**
 * Example: Filtering WordPress Export by Category
 *
 * This example demonstrates how to use the Jekyll Export plugin
 * to export only posts from specific categories.
 *
 * @package JekyllExporter
 */

// Example 1: Export only posts from "Technology" category.
add_filter(
	'jekyll_export_taxonomy_filters',
	function() {
		return array(
			'category' => array( 'technology' ),
		);
	}
);

// Example 2: Export posts from multiple categories.
add_filter(
	'jekyll_export_taxonomy_filters',
	function() {
		return array(
			'category' => array( 'technology', 'science', 'news' ),
		);
	}
);

// Example 3: Export posts with specific tags.
add_filter(
	'jekyll_export_taxonomy_filters',
	function() {
		return array(
			'post_tag' => array( 'featured', 'popular' ),
		);
	}
);

// Example 4: Combine category and tag filters.
add_filter(
	'jekyll_export_taxonomy_filters',
	function() {
		return array(
			'category' => array( 'technology' ),
			'post_tag' => array( 'featured' ),
		);
	}
);

// Example 5: Export only posts (exclude pages).
add_filter(
	'jekyll_export_post_types',
	function() {
		return array( 'post' );
	}
);

// Example 6: Export custom post types.
add_filter(
	'jekyll_export_post_types',
	function() {
		return array( 'portfolio', 'testimonial' );
	}
);

// After adding the filters above, trigger the export:
// global $jekyll_export;
// $jekyll_export->export();
