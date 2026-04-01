<?php
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
