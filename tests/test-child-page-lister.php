<?php

// Require mock environment
require_once __DIR__ . '/wordpress-mocks.php';

// Require the actual plugin code
require_once __DIR__ . '/../child_page_lister.php';

class ChildPageListerTests {

    public function run() {
        echo "Running tests...\n";
        $passed = 0;
        $failed = 0;

        $methods = get_class_methods( $this );
        foreach ( $methods as $method ) {
            if ( strpos( $method, 'test_' ) === 0 ) {
                cpl_reset_mocks(); // Reset state before each test
                try {
                    $this->$method();
                    echo "✅ {$method} passed.\n";
                    $passed++;
                } catch ( Exception $e ) {
                    echo "❌ {$method} failed: " . $e->getMessage() . "\n";
                    $failed++;
                }
            }
        }

        echo "\nTests completed. Passed: {$passed}, Failed: {$failed}\n";
        if ( $failed > 0 ) {
            exit( 1 );
        }
    }

    private function assert_equals( $expected, $actual, $message = '' ) {
        if ( $expected !== $actual ) {
            throw new Exception( $message . " Expected: '{$expected}', Actual: '{$actual}'" );
        }
    }

    public function test_error_path_invalid_post_type() {
        global $post;

        // Ensure $post is NOT a WP_Post object
        $post = null;

        $result = cpl_display_child_pages_shortcode();

        $expected_error = '<p>Error: Could not determine the current page.</p>';
        $this->assert_equals( $expected_error, $result, 'Shortcode should return error string when $post is not a WP_Post.' );
    }
}

// Run the tests
$tester = new ChildPageListerTests();
$tester->run();
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
/**
 * Test script for Child Page Lister and Sorter plugin
 *
 * Run via: php tests/test-child-page-lister.php
 */

require_once __DIR__ . '/wordpress-mocks.php';

// Define ABSPATH if not defined
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
require_once __DIR__ . '/../child_page_lister.php';

class ChildPageListerTest {

    private $tests_passed = 0;
    private $tests_failed = 0;

    public function setUp() {
        cpl_reset_mocks();
        $_GET = []; // Reset GET parameters explicitly
    }

    private function assertStringContains($needle, $haystack, $message = '') {
        if (strpos($haystack, $needle) === false) {
            echo "❌ Failed: " . ($message ? $message : "Expected '$needle' not found in haystack") . "\n";
            $this->tests_failed++;
            return false;
        }
        return true;
    }

    private function assertStringNotContains($needle, $haystack, $message = '') {
        if (strpos($haystack, $needle) !== false) {
            echo "❌ Failed: " . ($message ? $message : "Expected '$needle' to NOT be found in haystack") . "\n";
            $this->tests_failed++;
            return false;
        }
        return true;
    }

    private function assertEqual($expected, $actual, $message = '') {
        if ($expected !== $actual) {
            echo "❌ Failed: " . ($message ? $message : "Expected '$expected', got '$actual'") . "\n";
            $this->tests_failed++;
            return false;
        }
        return true;
    }

    private function pass($message) {
        echo "✅ Passed: $message\n";
        $this->tests_passed++;
    }

    public function run() {
        echo "Running Child Page Lister Tests...\n\n";

        $methods = get_class_methods($this);
        foreach ($methods as $method) {
            if (strpos($method, 'test_') === 0) {
                $this->$method();
            }
        }

        echo "\nTest Summary:\n";
        echo "Passed: {$this->tests_passed}\n";
        echo "Failed: {$this->tests_failed}\n";

        if ($this->tests_failed > 0) {
            exit(1);
        } else {
            exit(0);
        }
    }

    // --- The Tests ---

    public function test_plugin_initialization() {
        $this->setUp();

        // Check if tags are added to pages
        cpl_add_tags_to_pages();

        $found = false;
        if (!empty($GLOBALS['cpl_mock_state']['taxonomies'])) {
            foreach ($GLOBALS['cpl_mock_state']['taxonomies'] as $tax) {
                if ($tax[0] === 'post_tag' && $tax[1] === 'page') {
                    $found = true;
                }
            }
        }

        if ($found) {
            $this->pass(__FUNCTION__);
        } else {
            echo "❌ Failed: " . __FUNCTION__ . " - Tags not registered for pages\n";
            $this->tests_failed++;
        }
    }

    public function test_shortcode_no_post() {
        $this->setUp();
        $GLOBALS['post'] = null;
        $output = cpl_display_child_pages_shortcode();

        if ($this->assertStringContains('Error: Could not determine the current page.', $output)) {
            $this->pass(__FUNCTION__);
        }
    }

    public function test_shortcode_no_child_pages() {
        $this->setUp();
        $GLOBALS['post'] = new WP_Post(['ID' => 1]);
        $output = cpl_display_child_pages_shortcode();

        if ($this->assertStringContains('No child pages found for this page.', $output)) {
            $this->pass(__FUNCTION__);
        }
    }

    public function test_shortcode_lists_children() {
        $this->setUp();
        $GLOBALS['post'] = new WP_Post(['ID' => 1]);
        $GLOBALS['cpl_mock_state']['posts'] = [
            new WP_Post(['ID' => 2, 'post_title' => 'First Child', 'post_parent' => 1, 'post_date' => '2023-01-01']),
            new WP_Post(['ID' => 3, 'post_title' => 'Second Child', 'post_parent' => 1, 'post_date' => '2023-01-02']),
        ];

        $output = cpl_display_child_pages_shortcode();

        $passed1 = $this->assertStringContains('First Child', $output);
        $passed2 = $this->assertStringContains('Second Child', $output);

        if ($passed1 && $passed2) {
            $this->pass(__FUNCTION__);
        }
    }

    public function test_shortcode_sorting_by_title_desc() {
        $this->setUp();
        $GLOBALS['post'] = new WP_Post(['ID' => 1]);
        $_GET['sortby'] = 'title';
        $_GET['sortorder'] = 'desc';

        $GLOBALS['cpl_mock_state']['posts'] = [
            new WP_Post(['ID' => 2, 'post_title' => 'Alpha', 'post_parent' => 1, 'post_date' => '2023-01-01']),
            new WP_Post(['ID' => 3, 'post_title' => 'Bravo', 'post_parent' => 1, 'post_date' => '2023-01-02']),
        ];

        $output = cpl_display_child_pages_shortcode();

        $posAlpha = strpos($output, 'Alpha');
        $posBravo = strpos($output, 'Bravo');

        if ($posBravo !== false && $posAlpha !== false && $posBravo < $posAlpha) {
            $this->pass(__FUNCTION__);
        } else {
            echo "❌ Failed: " . __FUNCTION__ . " - Bravo should appear before Alpha\n";
            $this->tests_failed++;
        }
    }

    public function test_shortcode_sorting_by_date_asc() {
        $this->setUp();
        $GLOBALS['post'] = new WP_Post(['ID' => 1]);
        $_GET['sortby'] = 'date';
        $_GET['sortorder'] = 'asc';

        $GLOBALS['cpl_mock_state']['posts'] = [
            new WP_Post(['ID' => 2, 'post_title' => 'Late Post', 'post_parent' => 1, 'post_date' => '2023-12-01']),
            new WP_Post(['ID' => 3, 'post_title' => 'Early Post', 'post_parent' => 1, 'post_date' => '2023-01-01']),
        ];

        $output = cpl_display_child_pages_shortcode();

        $posLate = strpos($output, 'Late Post');
        $posEarly = strpos($output, 'Early Post');

        if ($posEarly !== false && $posLate !== false && $posEarly < $posLate) {
            $this->pass(__FUNCTION__);
        } else {
            echo "❌ Failed: " . __FUNCTION__ . " - Early Post should appear before Late Post\n";
            $this->tests_failed++;
        }
    }

    public function test_shortcode_sorting_by_tags_asc() {
        $this->setUp();
        $GLOBALS['post'] = new WP_Post(['ID' => 1]);
        $_GET['sortby'] = 'tags';
        $_GET['sortorder'] = 'asc';

        $GLOBALS['cpl_mock_state']['posts'] = [
            new WP_Post(['ID' => 2, 'post_title' => 'Zebra Post', 'post_parent' => 1, 'post_date' => '2023-01-01']),
            new WP_Post(['ID' => 3, 'post_title' => 'Apple Post', 'post_parent' => 1, 'post_date' => '2023-01-02']),
        ];

        $tag_zebra = new stdClass(); $tag_zebra->name = 'Zebra Tag';
        $tag_apple = new stdClass(); $tag_apple->name = 'Apple Tag';

        $GLOBALS['cpl_mock_state']['post_tags'][2] = [$tag_zebra];
        $GLOBALS['cpl_mock_state']['post_tags'][3] = [$tag_apple];

        $output = cpl_display_child_pages_shortcode();

        $posZebra = strpos($output, 'Zebra Post');
        $posApple = strpos($output, 'Apple Post');

        if ($posApple !== false && $posZebra !== false && $posApple < $posZebra) {
            $this->pass(__FUNCTION__);
        } else {
            echo "❌ Failed: " . __FUNCTION__ . " - Apple Post should appear before Zebra Post\n";
            $this->tests_failed++;
        }
    }

    public function test_shortcode_sorting_by_tags_desc() {
        $this->setUp();
        $GLOBALS['post'] = new WP_Post(['ID' => 1]);
        $_GET['sortby'] = 'tags';
        $_GET['sortorder'] = 'desc';

        $GLOBALS['cpl_mock_state']['posts'] = [
            new WP_Post(['ID' => 2, 'post_title' => 'Apple Post', 'post_parent' => 1, 'post_date' => '2023-01-02']),
            new WP_Post(['ID' => 3, 'post_title' => 'Zebra Post', 'post_parent' => 1, 'post_date' => '2023-01-01']),
        ];

        $tag_zebra = new stdClass(); $tag_zebra->name = 'Zebra Tag';
        $tag_apple = new stdClass(); $tag_apple->name = 'Apple Tag';

        $GLOBALS['cpl_mock_state']['post_tags'][2] = [$tag_apple];
        $GLOBALS['cpl_mock_state']['post_tags'][3] = [$tag_zebra];

        $output = cpl_display_child_pages_shortcode();

        $posApple = strpos($output, 'Apple Post');
        $posZebra = strpos($output, 'Zebra Post');

        if ($posZebra !== false && $posApple !== false && $posZebra < $posApple) {
            $this->pass(__FUNCTION__);
        } else {
            echo "❌ Failed: " . __FUNCTION__ . " - Zebra Post should appear before Apple Post\n";
            $this->tests_failed++;
        }
    }

    public function test_shortcode_sorting_by_tags_missing_tags() {
        $this->setUp();
        $GLOBALS['post'] = new WP_Post(['ID' => 1]);
        $_GET['sortby'] = 'tags';
        $_GET['sortorder'] = 'asc';

        $GLOBALS['cpl_mock_state']['posts'] = [
            new WP_Post(['ID' => 2, 'post_title' => 'Post with Tags', 'post_parent' => 1, 'post_date' => '2023-01-02']),
            new WP_Post(['ID' => 3, 'post_title' => 'Post without Tags', 'post_parent' => 1, 'post_date' => '2023-01-01']),
        ];

        $tag_apple = new stdClass(); $tag_apple->name = 'Apple Tag';

        $GLOBALS['cpl_mock_state']['post_tags'][2] = [$tag_apple];
        // Post 3 has no tags

        $output = cpl_display_child_pages_shortcode();

        // Empty strings (no tag) sort before 'apple'
        $posWithTags = strpos($output, 'Post with Tags');
        $posWithoutTags = strpos($output, 'Post without Tags');

        if ($posWithoutTags !== false && $posWithTags !== false && $posWithoutTags < $posWithTags) {
            $this->pass(__FUNCTION__);
        } else {
            echo "❌ Failed: " . __FUNCTION__ . " - Post without Tags should appear before Post with Tags\n";
            $this->tests_failed++;
        }
    }

    public function test_shortcode_sanitization_invalid_sortby() {
        $this->setUp();
        $GLOBALS['post'] = new WP_Post(['ID' => 1]);
        $_GET['sortby'] = 'invalid_column_name';
        $_GET['sortorder'] = 'asc';

        $GLOBALS['cpl_mock_state']['posts'] = [
            new WP_Post(['ID' => 2, 'post_title' => 'Alpha', 'post_parent' => 1, 'post_date' => '2023-01-01']),
            new WP_Post(['ID' => 3, 'post_title' => 'Bravo', 'post_parent' => 1, 'post_date' => '2023-01-02']),
        ];

        $output = cpl_display_child_pages_shortcode();

        // Default is title asc
        $posAlpha = strpos($output, 'Alpha');
        $posBravo = strpos($output, 'Bravo');

        if ($posAlpha !== false && $posBravo !== false && $posAlpha < $posBravo) {
            $this->pass(__FUNCTION__);
        } else {
            echo "❌ Failed: " . __FUNCTION__ . " - Should fallback to title ASC sorting\n";
            $this->tests_failed++;
        }
    }
}

$test = new ChildPageListerTest();
$test->run();
