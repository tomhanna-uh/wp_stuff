<?php

// Require mocks first to mock WP_Post and others before plugin definitions
require_once __DIR__ . '/wordpress-mocks.php';

// Mock add_shortcode and add_action which are called when we include the plugin file
if (!function_exists('add_shortcode')) {
    function add_shortcode($tag, $callback) {}
}
if (!function_exists('add_action')) {
    function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {}
}
if (!function_exists('register_taxonomy_for_object_type')) {
    function register_taxonomy_for_object_type($taxonomy, $object_type) {}
}

// Ensure ABSPATH is defined as plugin requires it
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

// Require the plugin file
require_once __DIR__ . '/../child_page_lister.php';

$tests_passed = 0;
$tests_failed = 0;

function assert_contains($haystack, $needle, $message) {
    global $tests_passed, $tests_failed;
    if (strpos($haystack, $needle) !== false) {
        echo "PASS: $message\n";
        $tests_passed++;
    } else {
        echo "FAIL: $message\n";
        echo "Expected to contain: '$needle'\n";
        $tests_failed++;
    }
}

function run_test($name, $callback) {
    echo "Running test: $name\n";
    cpl_reset_mocks();

    // Clear superglobals between tests
    $_GET = [];

    $callback();
}

// -------------------------------------------------------------------------
// TESTS
// -------------------------------------------------------------------------

run_test('Error handling when not on a page', function() {
    global $post, $mock_data;
    $post = null; // Ensure $post is null

    $output = cpl_display_child_pages_shortcode();
    assert_contains($output, '<p>Error: Could not determine the current page.</p>', 'Returns error when $post is null');
});

run_test('Empty state when no child pages found', function() {
    global $post, $mock_data;
    $post = new WP_Post(1);

    // Mock WP_Query to return no posts
    $mock_data['wp_query_posts'] = [];

    $output = cpl_display_child_pages_shortcode();
    assert_contains($output, '<p>No child pages found for this page.</p>', 'Returns empty message when no child pages found');
});

run_test('Populated state with child pages', function() {
    global $post, $mock_data;
    $post = new WP_Post(1);

    // Create a mock child post
    $child_post = new WP_Post(2);
    $mock_data['wp_query_posts'] = [$child_post];

    // Mock outputs for the specific post
    $mock_data['get_the_title_callable'] = function($id) {
        if ($id === 2) return 'Test Child Page';
        return 'Mock Title';
    };
    $mock_data['get_permalink_callable'] = function($id) {
        if ($id === 2) return 'http://example.com/child-page/';
        return 'http://example.com/page/';
    };
    $mock_data['get_the_date_callable'] = function($format, $id) {
        if ($id === 2) return 'October 31, 2024';
        return 'January 1, 2024';
    };

    $output = cpl_display_child_pages_shortcode();

    assert_contains($output, '<ul class="cpl-page-list">', 'Contains list container');
    assert_contains($output, '<li class="cpl-page-item">', 'Contains list item container');
    assert_contains($output, '<h3 class="cpl-page-item-title">', 'Contains item title container');
    assert_contains($output, 'Test Child Page', 'Contains the mock title');
    assert_contains($output, 'http://example.com/child-page/', 'Contains the mock permalink');
    assert_contains($output, '<span class="cpl-meta-date">Published: October 31, 2024</span>', 'Contains the mock published date');
});

run_test('Tag sorting state in ascending order', function() {
    global $post, $mock_data;
    $post = new WP_Post(1);

    // Set query params to sort by tags ascending
    $_GET['sortby'] = 'tags';
    $_GET['sortorder'] = 'asc';

    // Create two child posts
    $child_post_1 = new WP_Post(2); // Should be sorted second (Z)
    $child_post_2 = new WP_Post(3); // Should be sorted first (A)

    // Note: The plugin does usort, so it will sort the elements in place
    $mock_data['wp_query_posts'] = [$child_post_1, $child_post_2];

    // Mock wp_get_post_tags output
    $tag_z = new WP_Term(); $tag_z->name = 'Zebra';
    $tag_a = new WP_Term(); $tag_a->name = 'Apple';

    $mock_data['wp_get_post_tags'] = [
        2 => [$tag_z],
        3 => [$tag_a]
    ];

    // Set titles so we can verify output order
    $mock_data['get_the_title_callable'] = function($id) {
        if ($id === 2) return 'Post Z';
        if ($id === 3) return 'Post A';
        return 'Mock Title';
    };

    $output = cpl_display_child_pages_shortcode();

    // Find the positions of the output titles to verify order
    $pos_a = strpos($output, 'Post A');
    $pos_z = strpos($output, 'Post Z');

    if ($pos_a !== false && $pos_z !== false && $pos_a < $pos_z) {
        echo "PASS: Sorted posts by tags successfully\n";
        global $tests_passed; $tests_passed++;
    } else {
        echo "FAIL: Sorted posts by tags successfully\n";
        echo "Expected 'Post A' to be before 'Post Z'.\n";
        global $tests_failed; $tests_failed++;
    }
});

echo "\nTests Summary: $tests_passed Passed, $tests_failed Failed\n";
if ($tests_failed > 0) {
    exit(1);
}
exit(0);
