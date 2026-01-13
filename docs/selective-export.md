# Selective Export by Category or Tag

This feature allows you to export only a specific subset of your WordPress content, filtered by category, tag, or post type. This is particularly useful when:

- You have a large WordPress site but only need to convert specific sections
- You want to migrate content by topic or category
- You need to export content incrementally

## Using WP-CLI

The easiest way to perform selective exports is via WP-CLI commands.

### Export by Category

To export posts from a single category, use the category slug:

```bash
wp jekyll-export --category=technology > technology-export.zip
```

To export from multiple categories (OR logic - posts in any of these categories):

```bash
wp jekyll-export --category=tech,news,updates > export.zip
```

### Export by Tag

To export posts with a specific tag:

```bash
wp jekyll-export --tag=featured > featured-export.zip
```

To export posts with multiple tags (OR logic):

```bash
wp jekyll-export --tag=featured,popular > export.zip
```

### Export Specific Post Types

To export only pages:

```bash
wp jekyll-export --post_type=page > pages-export.zip
```

To export only posts:

```bash
wp jekyll-export --post_type=post > posts-export.zip
```

To export custom post types:

```bash
wp jekyll-export --post_type=portfolio,testimonial > custom-export.zip
```

### Combining Filters

You can combine multiple filters. Posts must match ALL specified filters (AND logic):

```bash
# Export posts that are in "technology" category AND have "featured" tag
wp jekyll-export --category=technology --tag=featured --post_type=post > export.zip
```

## Using PHP Filters

For more programmatic control, you can use WordPress filters directly in your theme's `functions.php` or a custom plugin.

### Filter by Category

```php
add_filter( 'jekyll_export_taxonomy_filters', function() {
    return array(
        'category' => array( 'technology', 'science' ),
    );
} );
```

### Filter by Tag

```php
add_filter( 'jekyll_export_taxonomy_filters', function() {
    return array(
        'post_tag' => array( 'featured', 'popular' ),
    );
} );
```

### Filter by Custom Taxonomy

```php
add_filter( 'jekyll_export_taxonomy_filters', function() {
    return array(
        'my_custom_taxonomy' => array( 'term-slug-1', 'term-slug-2' ),
    );
} );
```

### Combine Multiple Taxonomies

```php
add_filter( 'jekyll_export_taxonomy_filters', function() {
    return array(
        'category' => array( 'technology' ),
        'post_tag' => array( 'featured' ),
        'custom_tax' => array( 'term-1' ),
    );
} );
```

### Filter Post Types

```php
add_filter( 'jekyll_export_post_types', function() {
    return array( 'post', 'page' ); // Only export posts and pages
} );
```

## Finding Category and Tag Slugs

If you're not sure what slug to use:

### Via WordPress Admin

1. Go to **Posts > Categories** or **Posts > Tags**
2. Hover over the category/tag name
3. Look at the browser's status bar or the URL - you'll see something like `tag_ID=123&taxonomy=post_tag&term_slug=featured`
4. The slug is the part after `term_slug=`

### Via WP-CLI

List all categories with their slugs:

```bash
wp term list category --fields=name,slug
```

List all tags with their slugs:

```bash
wp term list post_tag --fields=name,slug
```

## Use Cases

### Scenario 1: Export a Single Blog Section

You have a WordPress site with multiple sections (Tech, Lifestyle, Travel) and want to move just the Tech section to a static site:

```bash
wp jekyll-export --category=tech > tech-blog-export.zip
```

### Scenario 2: Export Featured Content

You want to export only posts marked as "featured" for a special showcase site:

```bash
wp jekyll-export --tag=featured > featured-content.zip
```

### Scenario 3: Export by Year (using custom taxonomy)

If you've tagged posts by year, you can export by year:

```bash
wp jekyll-export --tag=2024 > 2024-posts.zip
```

### Scenario 4: Migrate Content Incrementally

Export different categories separately for incremental migration:

```bash
wp jekyll-export --category=tech > tech.zip
wp jekyll-export --category=news > news.zip
wp jekyll-export --category=reviews > reviews.zip
```

## Technical Details

- **Taxonomy Filtering**: Uses WordPress term slugs (not names or IDs)
- **Query Performance**: Filtering is done at the database level for efficiency
- **OR Logic Within Taxonomy**: Multiple terms in the same taxonomy use OR logic (e.g., posts in category A OR B)
- **AND Logic Across Taxonomies**: Multiple taxonomies use AND logic (e.g., posts in category A AND having tag B)
- **Post Type Filtering**: Works independently of taxonomy filtering

## Limitations

- Revisions are excluded when using taxonomy filters (as they don't have taxonomy terms)
- Taxonomy filtering uses term slugs, not term IDs or names
- Empty taxonomy filters are ignored (no filtering applied)

## Troubleshooting

### No Posts Exported

If your export is empty:

1. **Check the slug**: Make sure you're using the term slug, not the name
   - Use `wp term list category` to verify the exact slug
2. **Check post status**: Only published, future, and draft posts are exported
3. **Verify taxonomy**: Make sure you're using the correct taxonomy name (`category`, `post_tag`, etc.)

### Wrong Posts Exported

If you're getting unexpected posts:

1. **Check term associations**: Verify which posts have the category/tag assigned
2. **Review filter logic**: Remember that multiple categories use OR logic
3. **Clear cache**: If testing, use `wp cache flush` between exports
