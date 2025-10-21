<?php
/**
 * Example configuration for optimizing WordPress to Jekyll exports
 * 
 * Add this code to your theme's functions.php file or create a custom plugin.
 * These examples show how to use the new performance filters.
 */

// Example 1: Skip uploads entirely for sites using CDN
// =====================================================
// If all your images and media are served from a CDN like Cloudinary,
// AWS S3, or Cloudflare, you don't need to export them.

add_filter( 'jekyll_export_skip_uploads', '__return_true' );


// Example 2: Exclude specific directories from uploads
// ====================================================
// Keep the uploads but skip cache directories and temporary files

add_filter( 'jekyll_export_excluded_upload_dirs', function( $excluded ) {
    return array_merge( $excluded, array(
        '/cache/',           // Generic cache directory
        '/tmp/',             // Temporary files
        '/temp/',            // Alternative temp directory
        '/backup/',          // Backup files
        '/wc-logs/',         // WooCommerce logs
        '/edd-logs/',        // Easy Digital Downloads logs
        '/wpml/',            // WPML cache
        '/et-cache/',        // Elegant Themes cache
        '/elementor/css/',   // Elementor CSS cache
        '/oxygen/css/',      // Oxygen CSS cache
    ) );
} );


// Example 3: Export only specific post types
// ==========================================
// If you only want to export blog posts (not pages, revisions, etc.)

add_filter( 'jekyll_export_post_types', function() {
    return array( 'post' ); // Only export posts
} );

// Or export posts and custom post types:
add_filter( 'jekyll_export_post_types', function() {
    return array( 'post', 'page', 'portfolio', 'product' );
} );


// Example 4: Custom converter options
// ===================================
// The HtmlConverter is now reused, but you can still customize its options

add_filter( 'jekyll_export_markdown_converter_options', function( $options ) {
    return array_merge( $options, array(
        'strip_tags'        => false,
        'remove_nodes'      => 'script style',
        'hard_break'        => true,
        'header_style'      => 'atx', // # Heading style
    ) );
} );


// Example 5: Modify exported metadata
// ===================================
// Add custom fields or modify the YAML front matter

add_filter( 'jekyll_export_meta', function( $meta ) {
    // Add reading time estimate
    if ( isset( $meta['id'] ) ) {
        $post = get_post( $meta['id'] );
        if ( $post ) {
            $word_count = str_word_count( strip_tags( $post->post_content ) );
            $meta['reading_time'] = ceil( $word_count / 200 ); // Assume 200 WPM
        }
    }
    
    // Remove fields you don't need
    unset( $meta['guid'] );
    
    // Ensure consistent date format
    if ( isset( $meta['date'] ) ) {
        $meta['date'] = gmdate( 'Y-m-d H:i:s O', strtotime( $meta['date'] ) );
    }
    
    return $meta;
} );


// Example 6: Performance monitoring
// =================================
// Track export performance to identify bottlenecks

add_action( 'jekyll_export', function() {
    // Store start time
    update_option( 'jekyll_export_start_time', microtime( true ) );
} );

add_action( 'jekyll_export_complete', function() {
    // Calculate and log duration
    $start = get_option( 'jekyll_export_start_time' );
    if ( $start ) {
        $duration = microtime( true ) - $start;
        error_log( sprintf( 
            'Jekyll export completed in %.2f seconds (%s)',
            $duration,
            human_time_diff( $start, microtime( true ) )
        ) );
        delete_option( 'jekyll_export_start_time' );
    }
} );


// Example 7: Large site optimization (all optimizations combined)
// ===============================================================
// Recommended configuration for sites with 10,000+ posts

function my_jekyll_export_optimizations() {
    // Skip uploads if using CDN
    add_filter( 'jekyll_export_skip_uploads', '__return_true' );
    
    // Export only published content
    add_filter( 'jekyll_export_post_types', function() {
        return array( 'post', 'page' ); // Skip revisions
    } );
    
    // Increase memory limit
    if ( ! defined( 'WP_MEMORY_LIMIT' ) ) {
        define( 'WP_MEMORY_LIMIT', '512M' );
    }
    
    // Disable other plugins during export
    add_filter( 'option_active_plugins', function( $plugins ) {
        if ( ! isset( $_GET['type'] ) || 'jekyll' !== $_GET['type'] ) {
            return $plugins;
        }
        
        // Only keep essential plugins active during export
        return array_filter( $plugins, function( $plugin ) {
            return strpos( $plugin, 'wordpress-to-jekyll-exporter' ) !== false;
        } );
    } );
}
add_action( 'init', 'my_jekyll_export_optimizations' );


// Example 8: Testing configuration
// ================================
// Test the export configuration without actually exporting

function test_jekyll_export_config() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    
    echo '<pre>';
    echo "WordPress to Jekyll Exporter Configuration Test\n";
    echo "==============================================\n\n";
    
    // Check post types
    $post_types = apply_filters( 'jekyll_export_post_types', array( 'post', 'page', 'revision' ) );
    echo "Post types to export: " . implode( ', ', $post_types ) . "\n";
    
    // Count posts
    global $wpdb;
    $placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
    $query = "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ($placeholders)";
    $count = $wpdb->get_var( $wpdb->prepare( $query, $post_types ) );
    echo "Total posts to export: $count\n\n";
    
    // Check upload settings
    $skip_uploads = apply_filters( 'jekyll_export_skip_uploads', false );
    echo "Skip uploads: " . ( $skip_uploads ? 'Yes' : 'No' ) . "\n";
    
    if ( ! $skip_uploads ) {
        $upload_dir = wp_upload_dir();
        $size = 0;
        if ( is_dir( $upload_dir['basedir'] ) ) {
            $iterator = new RecursiveIteratorIterator( 
                new RecursiveDirectoryIterator( $upload_dir['basedir'] )
            );
            foreach ( $iterator as $file ) {
                if ( $file->isFile() ) {
                    $size += $file->getSize();
                }
            }
        }
        echo "Uploads directory size: " . size_format( $size ) . "\n";
    }
    
    // Memory limit
    echo "\nPHP Configuration:\n";
    echo "Memory limit: " . ini_get( 'memory_limit' ) . "\n";
    echo "Max execution time: " . ini_get( 'max_execution_time' ) . "s\n";
    
    echo '</pre>';
}
// Uncomment to test:
// add_action( 'admin_notices', 'test_jekyll_export_config' );
