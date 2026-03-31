<?php
// tests/test-child-page-lister.php

require_once __DIR__ . '/wordpress-mocks.php';

// Define WP_Post class to avoid redefining in plugin if it isn't defined
// Mock WP_Post class is in wordpress-mocks.php

// Define constants
define('ABSPATH', true);

// Include the plugin file
require_once dirname(__DIR__) . '/child_page_lister.php';

// Helper class for assertions
class TestAssert {
    public static $fails = 0;
    public static $passes = 0;

    public static function assertEquals($expected, $actual, $message = '') {
        if ($expected === $actual) {
            self::$passes++;
            echo "PASS: $message\n";
        } else {
            self::$fails++;
            echo "FAIL: $message\n";
            echo "  Expected: " . print_r($expected, true) . "\n";
            echo "  Actual:   " . print_r($actual, true) . "\n";
        }
    }
}

// Function to run a test
function run_test($name, $callback) {
    echo "Running test: $name\n";
    cpl_reset_mocks();
    $callback();
    echo "\n";
}

// --- Setup Test Data ---
class MockTag {
    public $name;
    public function __construct($name) {
        $this->name = $name;
    }
}

// Test 1: Test sorting by tags ASC
run_test("Tag sorting ASC", function() {
    global $mock_query_posts, $mock_post_tags, $mock_titles, $post, $_GET;

    // Set current post (parent)
    $post = new WP_Post(1);

    // Set query params
    $_GET['sortby'] = 'tags';
    $_GET['sortorder'] = 'ASC';

    // Setup child posts
    $post2 = new WP_Post(2);
    $post3 = new WP_Post(3);
    $post4 = new WP_Post(4);

    $mock_query_posts = [$post2, $post3, $post4];

    // Setup tags: post2 has 'Zebra', post3 has 'Apple', post4 has 'Banana'
    $mock_post_tags[2] = [new MockTag('Zebra')];
    $mock_post_tags[3] = [new MockTag('Apple')];
    $mock_post_tags[4] = [new MockTag('Banana')];

    $mock_titles[2] = 'Zebra Post';
    $mock_titles[3] = 'Apple Post';
    $mock_titles[4] = 'Banana Post';

    $output = cpl_display_child_pages_shortcode();

    // Extract titles in order
    preg_match_all('/<h3 class="cpl-page-item-title">\s*<a href="[^"]+">\s*(.*?)\s*<\/a>/s', $output, $matches);

    TestAssert::assertEquals(['Apple Post', 'Banana Post', 'Zebra Post'], $matches[1], 'Posts should be sorted by tag alphabetically ASC');
});

// Test 2: Test sorting by tags DESC
run_test("Tag sorting DESC", function() {
    global $mock_query_posts, $mock_post_tags, $mock_titles, $post, $_GET;

    // Set current post (parent)
    $post = new WP_Post(1);

    // Set query params
    $_GET['sortby'] = 'tags';
    $_GET['sortorder'] = 'DESC';

    // Setup child posts
    $post2 = new WP_Post(2);
    $post3 = new WP_Post(3);
    $post4 = new WP_Post(4);

    $mock_query_posts = [$post2, $post3, $post4];

    // Setup tags: post2 has 'Zebra', post3 has 'Apple', post4 has 'Banana'
    $mock_post_tags[2] = [new MockTag('Zebra')];
    $mock_post_tags[3] = [new MockTag('Apple')];
    $mock_post_tags[4] = [new MockTag('Banana')];

    $mock_titles[2] = 'Zebra Post';
    $mock_titles[3] = 'Apple Post';
    $mock_titles[4] = 'Banana Post';

    $output = cpl_display_child_pages_shortcode();

    // Extract titles in order
    preg_match_all('/<h3 class="cpl-page-item-title">\s*<a href="[^"]+">\s*(.*?)\s*<\/a>/s', $output, $matches);

    TestAssert::assertEquals(['Zebra Post', 'Banana Post', 'Apple Post'], $matches[1], 'Posts should be sorted by tag alphabetically DESC');
});

// Test 3: Edge Case: Missing tags on some posts
run_test("Tag sorting with missing tags", function() {
    global $mock_query_posts, $mock_post_tags, $mock_titles, $post, $_GET;

    // Set current post (parent)
    $post = new WP_Post(1);

    // Set query params
    $_GET['sortby'] = 'tags';
    $_GET['sortorder'] = 'ASC';

    // Setup child posts
    $post2 = new WP_Post(2);
    $post3 = new WP_Post(3);
    $post4 = new WP_Post(4);
    $post5 = new WP_Post(5);

    $mock_query_posts = [$post2, $post3, $post4, $post5];

    // Setup tags:
    // post2 has 'Zebra'
    // post3 has no tags (should sort before letters in ASC order due to empty string)
    // post4 has 'Apple'
    // post5 has empty tag string
    $mock_post_tags[2] = [new MockTag('Zebra')];
    $mock_post_tags[3] = []; // Missing tags
    $mock_post_tags[4] = [new MockTag('Apple')];
    $mock_post_tags[5] = [new MockTag('')]; // Empty tag name

    $mock_titles[2] = 'Zebra Post';
    $mock_titles[3] = 'No Tag Post';
    $mock_titles[4] = 'Apple Post';
    $mock_titles[5] = 'Empty Tag Post';

    $output = cpl_display_child_pages_shortcode();

    // Extract titles in order
    preg_match_all('/<h3 class="cpl-page-item-title">\s*<a href="[^"]+">\s*(.*?)\s*<\/a>/s', $output, $matches);

    // Posts with missing/empty tags should sort to the beginning (empty string) in ASC mode,
    // Their relative order to each other depends on the usort implementation (PHP 8 uses stable sorting, PHP < 8 unstable,
    // but they will definitely both be before Apple and Zebra).

    // Get the first two elements
    $first_two = array_slice($matches[1], 0, 2);
    $last_two = array_slice($matches[1], 2, 2);

    $missing_tags_sorted_first = in_array('No Tag Post', $first_two) && in_array('Empty Tag Post', $first_two);
    TestAssert::assertEquals(true, $missing_tags_sorted_first, 'Posts with missing/empty tags should be sorted first in ASC order');
    TestAssert::assertEquals(['Apple Post', 'Zebra Post'], $last_two, 'Posts with tags should be sorted correctly after posts with no tags');
});


// Print summary
echo "Test Results: \n";
echo "Passed: " . TestAssert::$passes . "\n";
echo "Failed: " . TestAssert::$fails . "\n";

if (TestAssert::$fails > 0) {
    exit(1);
}
