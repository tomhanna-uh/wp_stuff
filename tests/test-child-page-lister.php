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
