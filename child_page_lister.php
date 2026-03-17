<?php
/**
 * Plugin Name:       Child Page Lister and Sorter
 * Description:       Lists child pages with sorting options. Use the shortcode [child_page_lister] on any parent page.
 * Version:           1.0
 * Author:            Gemini
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       child-page-lister
 */

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Adds tag support to pages.
 * This is necessary for the 'sort by tags' feature to work.
 */
function cpl_add_tags_to_pages() {
    register_taxonomy_for_object_type( 'post_tag', 'page' );
}
add_action( 'init', 'cpl_add_tags_to_pages' );

/**
 * The main function to display the list of child pages via shortcode.
 *
 * @return string The HTML output for the child page list and sorter.
 */
function cpl_display_child_pages_shortcode() {
    global $post;

    // Ensure we are on a page.
    if ( ! is_a( $post, 'WP_Post' ) ) {
        return '<p>Error: Could not determine the current page.</p>';
    }
    $parent_id = $post->ID;

    // --- Get sorting parameters from URL ---
    // Sanitize and set default values for sorting.
    $valid_sort_by = ['date', 'title', 'tags'];
    $sort_by = isset($_GET['sortby']) && is_string($_GET['sortby']) && in_array($_GET['sortby'], $valid_sort_by) ? sanitize_key($_GET['sortby']) : 'title';
    $sort_order = isset($_GET['sortorder']) && is_string($_GET['sortorder']) && in_array(strtoupper($_GET['sortorder']), ['ASC', 'DESC']) ? strtoupper(sanitize_key($_GET['sortorder'])) : 'ASC';

    // Start output buffering to capture all HTML.
    ob_start();
    ?>
    <style>
        /* Scoped styles for the child page lister */
        .cpl-container {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            margin: 2em 0;
        }
        .cpl-sorter {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem;
            background-color: #f7f7f7;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        .cpl-sort-group, .cpl-order-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .cpl-sorter span {
            font-weight: 600;
            color: #333;
        }
        .cpl-sorter a {
            text-decoration: none;
            padding: 0.5rem 1rem;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #fff;
            color: #0073aa;
            transition: all 0.2s ease-in-out;
        }
        .cpl-sorter a:hover {
            background-color: #f0f0f0;
            border-color: #999;
        }
        .cpl-sorter a.cpl-active {
            background-color: #0073aa;
            color: #fff;
            border-color: #0073aa;
            font-weight: bold;
        }
        .cpl-page-list {
            list-style: none;
            padding: 0;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        .cpl-page-item {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            overflow: hidden;
        }
        .cpl-page-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .cpl-page-item-content {
            padding: 1.25rem;
        }
        .cpl-page-item-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0 0 0.5rem 0;
        }
        .cpl-page-item-title a {
            text-decoration: none;
            color: #1d2327;
        }
        .cpl-page-item-title a:hover {
            color: #0073aa;
        }
        .cpl-meta {
            font-size: 0.875rem;
            color: #555;
            padding: 0.75rem 1.25rem;
            background-color: #f9f9f9;
            border-top: 1px solid #e0e0e0;
        }
        .cpl-meta-date {
            display: block;
            margin-bottom: 0.5rem;
        }
        .cpl-meta-tags .cpl-tag {
            display: inline-block;
            background-color: #eef6fc;
            color: #005a87;
            padding: 0.25rem 0.6rem;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-right: 0.3rem;
            margin-bottom: 0.3rem;
        }
    </style>
    <div class="cpl-container">
        <!-- Sorting Controls -->
        <div class="cpl-sorter">
            <?php
            $base_url = get_permalink($parent_id);
            $next_order = ($sort_order === 'ASC') ? 'desc' : 'asc';
            ?>
            <div class="cpl-sort-group">
                <span>Sort by:</span>
                <a href="<?php echo esc_url(add_query_arg(['sortby' => 'title', 'sortorder' => $sort_order], $base_url)); ?>" class="<?php echo $sort_by === 'title' ? 'cpl-active' : ''; ?>">Title</a>
                <a href="<?php echo esc_url(add_query_arg(['sortby' => 'date', 'sortorder' => $sort_order], $base_url)); ?>" class="<?php echo $sort_by === 'date' ? 'cpl-active' : ''; ?>">Date</a>
                <a href="<?php echo esc_url(add_query_arg(['sortby' => 'tags', 'sortorder' => $sort_order], $base_url)); ?>" class="<?php echo $sort_by === 'tags' ? 'cpl-active' : ''; ?>">Tags</a>
            </div>
            <div class="cpl-order-group">
                <span>Order:</span>
                <a href="<?php echo esc_url(add_query_arg(['sortby' => $sort_by, 'sortorder' => $next_order], $base_url)); ?>"><?php echo $sort_order === 'ASC' ? 'Ascending &darr;' : 'Descending &uarr;'; ?></a>
            </div>
        </div>

        <?php
        // --- WP_Query to get child pages ---
        $args = [
            'post_parent'    => $parent_id,
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => ($sort_by === 'tags') ? 'none' : $sort_by, // Disable DB order for tags
            'order'          => $sort_order,
        ];

        $child_pages_query = new WP_Query($args);
        $child_pages = $child_pages_query->get_posts();
        
        // --- Custom sorting for tags in PHP ---
        if ($sort_by === 'tags' && !empty($child_pages)) {
            usort($child_pages, function($a, $b) use ($sort_order) {
                $tags_a = wp_get_post_tags($a->ID);
                $tags_b = wp_get_post_tags($b->ID);
                // Use the name of the first tag for comparison
                $first_tag_a = !empty($tags_a) ? strtolower($tags_a[0]->name) : '';
                $first_tag_b = !empty($tags_b) ? strtolower($tags_b[0]->name) : '';
                
                $comparison = strcmp($first_tag_a, $first_tag_b);
                return ($sort_order === 'ASC') ? $comparison : -$comparison;
            });
        }

        // --- Display the list of child pages ---
        if (!empty($child_pages)) {
            echo '<ul class="cpl-page-list">';
            foreach ($child_pages as $child) {
                setup_postdata($child);
                $tags = get_the_tags($child->ID);
                ?>
                <li class="cpl-page-item">
                    <div class="cpl-page-item-content">
                        <h3 class="cpl-page-item-title">
                            <a href="<?php echo esc_url(get_permalink($child->ID)); ?>">
                                <?php echo esc_html(get_the_title($child->ID)); ?>
                            </a>
                        </h3>
                    </div>
                    <div class="cpl-meta">
                        <span class="cpl-meta-date">Published: <?php echo get_the_date('F j, Y', $child->ID); ?></span>
                        <div class="cpl-meta-tags">
                            <?php if ($tags) : ?>
                                <?php foreach ($tags as $tag) : ?>
                                    <span class="cpl-tag"><?php echo esc_html($tag->name); ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="cpl-tag">No Tags</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </li>
                <?php
            }
            echo '</ul>';
            wp_reset_postdata();
        } else {
            echo '<p>No child pages found for this page.</p>';
        }
        ?>
    </div>
    <?php

    // Return the buffered content.
    return ob_get_clean();
}
add_shortcode('child_page_lister', 'cpl_display_child_pages_shortcode');
