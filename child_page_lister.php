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
 * Enqueue frontend stylesheets for the plugin.
 */
function cpl_enqueue_styles() {
    wp_enqueue_style(
        'child-page-lister',
        plugins_url( 'assets/css/child-page-lister.css', __FILE__ ),
        array(),
        '1.0'
    );
}
add_action( 'wp_enqueue_scripts', 'cpl_enqueue_styles' );

/**
 * Fetches and sorts the child pages.
 *
 * @param int    $parent_id  The parent post ID.
 * @param string $sort_by    The sorting field (date, title, tags).
 * @param string $sort_order The sorting order (ASC, DESC).
 * @return array Array of WP_Post objects.
 */
function cpl_get_child_pages( $parent_id, $sort_by, $sort_order ) {
    $args = [
        'post_parent'    => $parent_id,
        'post_type'      => 'page',
        'post_status'    => 'publish',
        'posts_per_page' => 500, // Bound the query
        'orderby'        => ( $sort_by === 'tags' ) ? 'none' : $sort_by,
        'order'          => $sort_order,
    ];

    $child_pages_query = new WP_Query( $args );
    $child_pages       = $child_pages_query->get_posts();

    if ( $sort_by === 'tags' && ! empty( $child_pages ) ) {
        // Prime the term cache for efficiency
        $post_ids = wp_list_pluck( $child_pages, 'ID' );
        update_object_term_cache( $post_ids, 'page' );

        // Pre-fetch tags into a lookup array
        $tag_lookup = [];
        foreach ( $child_pages as $child ) {
            $tags = get_the_tags( $child->ID );
            $tag_lookup[ $child->ID ] = ( ! empty( $tags ) ) ? strtolower( $tags[0]->name ) : '';
        }

        usort( $child_pages, function ( $a, $b ) use ( $sort_order, $tag_lookup ) {
            // Use ?? to handle potential missing keys safely
            $first_tag_a = $tag_lookup[ $a->ID ] ?? '';
            $first_tag_b = $tag_lookup[ $b->ID ] ?? '';

            $comparison = strcmp( $first_tag_a, $first_tag_b );
            return ( $sort_order === 'ASC' ) ? $comparison : -$comparison;
        } );
    }

    return $child_pages;
}

/**
 * Renders the sorting controls HTML.
 *
 * @param int    $parent_id  The parent post ID.
 * @param string $sort_by    The current sorting field.
 * @param string $sort_order The current sorting order.
 */
function cpl_render_sorting_controls( $parent_id, $sort_by, $sort_order ) {
    $base_url   = get_permalink( $parent_id );
    $next_order = ( $sort_order === 'ASC' ) ? 'desc' : 'asc';
    ?>
    <div class="cpl-sorter">
        <div class="cpl-sort-group">
            <span><?php esc_html_e( 'Sort by:', 'child-page-lister' ); ?></span>
            <a href="<?php echo esc_url( add_query_arg( [ 'sortby' => 'title', 'sortorder' => $sort_order ], $base_url ) ); ?>" class="<?php echo $sort_by === 'title' ? 'cpl-active' : ''; ?>"><?php esc_html_e( 'Title', 'child-page-lister' ); ?></a>
            <a href="<?php echo esc_url( add_query_arg( [ 'sortby' => 'date', 'sortorder' => $sort_order ], $base_url ) ); ?>" class="<?php echo $sort_by === 'date' ? 'cpl-active' : ''; ?>"><?php esc_html_e( 'Date', 'child-page-lister' ); ?></a>
            <a href="<?php echo esc_url( add_query_arg( [ 'sortby' => 'tags', 'sortorder' => $sort_order ], $base_url ) ); ?>" class="<?php echo $sort_by === 'tags' ? 'cpl-active' : ''; ?>"><?php esc_html_e( 'Tags', 'child-page-lister' ); ?></a>
        </div>
        <div class="cpl-order-group">
            <span><?php esc_html_e( 'Order:', 'child-page-lister' ); ?></span>
            <a href="<?php echo esc_url( add_query_arg( [ 'sortby' => $sort_by, 'sortorder' => $next_order ], $base_url ) ); ?>">
                <?php echo $sort_order === 'ASC' ? esc_html__( 'Ascending', 'child-page-lister' ) . ' &darr;' : esc_html__( 'Descending', 'child-page-lister' ) . ' &uarr;'; ?>
            </a>
        </div>
    </div>
    <?php
}

/**
 * Renders the list of child pages HTML.
 *
 * @param array $child_pages Array of WP_Post objects.
 */
function cpl_render_child_pages_list( $child_pages ) {
    if ( empty( $child_pages ) ) {
        echo '<p>' . esc_html__( 'No child pages found for this page.', 'child-page-lister' ) . '</p>';
        return;
    }

    echo '<ul class="cpl-page-list">';
    foreach ( $child_pages as $child ) {
        $tags = get_the_tags( $child->ID );
        ?>
        <li class="cpl-page-item">
            <div class="cpl-page-item-content">
                <h3 class="cpl-page-item-title">
                    <a href="<?php echo esc_url( get_permalink( $child->ID ) ); ?>">
                        <?php echo esc_html( get_the_title( $child->ID ) ); ?>
                    </a>
                </h3>
            </div>
            <div class="cpl-meta">
                <span class="cpl-meta-date">
                    <?php
                    /* translators: %s: Published date */
                    printf( esc_html__( 'Published: %s', 'child-page-lister' ), get_the_date( 'F j, Y', $child->ID ) );
                    ?>
                </span>
                <div class="cpl-meta-tags">
                    <?php if ( $tags ) : ?>
                        <?php foreach ( $tags as $tag ) : ?>
                            <span class="cpl-tag"><?php echo esc_html( $tag->name ); ?></span>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <span class="cpl-tag"><?php esc_html_e( 'No Tags', 'child-page-lister' ); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </li>
        <?php
    }
    echo '</ul>';
}

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

    $child_pages = cpl_get_child_pages( $parent_id, $sort_by, $sort_order );

    // Start output buffering to capture all HTML.
    ob_start();
    ?>
    <div class="cpl-container">
        <?php
        cpl_render_sorting_controls( $parent_id, $sort_by, $sort_order );
        cpl_render_child_pages_list( $child_pages );
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
        
        // Pre-fetch term cache to avoid N+1 queries in loop and sorting
        if (!empty($child_pages)) {
            update_object_term_cache(wp_list_pluck($child_pages, 'ID'), 'page');
        }

        // --- Custom sorting for tags in PHP ---
        if ($sort_by === 'tags' && !empty($child_pages)) {
            // Prime term cache to avoid N+1 queries
            update_object_term_cache( wp_list_pluck( $child_pages, 'ID' ), 'page' );

            // Pre-fetch tags to avoid O(N log N) lookups in usort
            $tag_lookup = [];
            foreach ($child_pages as $child) {
                $tags = wp_get_post_tags($child->ID);
            $tag_lookup = [];
            foreach ($child_pages as $child) {
                $tags = get_the_tags($child->ID);
                $tag_lookup[$child->ID] = !empty($tags) ? strtolower($tags[0]->name) : '';
            }

            usort($child_pages, function($a, $b) use ($sort_order, $tag_lookup) {
                // Use the pre-fetched name of the first tag for comparison
                $first_tag_a = $tag_lookup[$a->ID] ?? '';
                $first_tag_b = $tag_lookup[$b->ID] ?? '';
                
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
