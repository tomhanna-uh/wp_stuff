<?php
define('ABSPATH', true);
require_once __DIR__ . '/wordpress-mocks.php';
require_once __DIR__ . '/../child_page_lister.php';

function test_no_child_pages() {
    cpl_reset_mocks();

    global $post, $mock_wp_query_posts;

    // Set up a mock parent post
    $post = new WP_Post(1);

    // Mock WP_Query to return empty array
    $mock_wp_query_posts = [];

    // Call the shortcode
    $output = cpl_display_child_pages_shortcode();

    // Check if the output contains the 'No child pages found' message
    if (strpos($output, '<p>No child pages found for this page.</p>') !== false) {
        echo "PASS: test_no_child_pages\n";
    } else {
        echo "FAIL: test_no_child_pages\n";
        echo "Expected output to contain '<p>No child pages found for this page.</p>'\n";
        echo "Actual output:\n" . $output . "\n";
        exit(1);
    }
}

test_no_child_pages();
echo "Done.\n";
