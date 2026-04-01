<?php
require_once __DIR__ . '/wordpress-mocks.php';
require_once __DIR__ . '/../child_page_lister.php';

use PHPUnit\Framework\TestCase;

class ChildPageListerTest extends TestCase {

    protected function setUp(): void {
        cpl_reset_mocks();
    }

    protected function tearDown(): void {
        cpl_reset_mocks();
    }

    public function test_cpl_display_child_pages_shortcode_no_post() {
        global $post;
        $post = null; // Ensure no post

        $output = cpl_display_child_pages_shortcode();
        $this->assertEquals('<p>Error: Could not determine the current page.</p>', $output);
    }

    public function test_cpl_display_child_pages_shortcode_no_children() {
        global $post, $mock_wp_query_posts;
        $post = new WP_Post(1);
        $mock_wp_query_posts = []; // No children

        $output = cpl_display_child_pages_shortcode();
        $this->assertStringContainsString('<p>No child pages found for this page.</p>', $output);
    }

    public function test_cpl_display_child_pages_shortcode_with_children() {
        global $post, $mock_wp_query_posts, $mock_post_titles, $mock_post_dates, $mock_post_tags;
        $post = new WP_Post(1);

        $child_post = new WP_Post(2);
        $mock_wp_query_posts = [$child_post];

        $mock_post_titles[2] = 'Child Page 1';
        $mock_post_dates[2] = 'October 10, 2023';

        // Mock a tag object
        $tag = new stdClass();
        $tag->name = 'News';
        $mock_post_tags[2] = [$tag];

        $output = cpl_display_child_pages_shortcode();

        // Check for specific elements in the output
        $this->assertStringContainsString('Child Page 1', $output);
        $this->assertStringContainsString('October 10, 2023', $output);
        $this->assertStringContainsString('News', $output);
        $this->assertStringContainsString('cpl-page-item', $output); // Check for list item class
        $this->assertStringContainsString('cpl-sorter', $output); // Check for sorter controls

        // Default sorting checks
        $this->assertStringContainsString('sortby=title', $output);
        $this->assertStringContainsString('cpl-active', $output); // One of the buttons should be active
    }

    public function test_cpl_display_child_pages_shortcode_sorting_by_date_desc() {
        global $post, $mock_wp_query_posts, $mock_post_titles;
        $_GET['sortby'] = 'date';
        $_GET['sortorder'] = 'DESC';

        $post = new WP_Post(1);

        // We just need to check if the shortcode handles the input correctly.
        // WP_Query handles actual sorting except for tags, which we mock.
        // We just supply some dummy data to ensure it renders without error.
        $child_post_1 = new WP_Post(2);
        $child_post_2 = new WP_Post(3);
        $mock_wp_query_posts = [$child_post_1, $child_post_2];

        $mock_post_titles[2] = 'Older Post';
        $mock_post_titles[3] = 'Newer Post';

        $output = cpl_display_child_pages_shortcode();

        $this->assertStringContainsString('Older Post', $output);
        $this->assertStringContainsString('Newer Post', $output);

        // Check active class is on the date sorter
        $this->assertMatchesRegularExpression('/href="[^"]*sortby=date&(amp;)?sortorder=DESC"[^>]*class="cpl-active"/', $output);
    }

    public function test_cpl_display_child_pages_shortcode_sorting_by_tags_php_logic() {
        global $post, $mock_wp_query_posts, $mock_post_titles, $mock_post_tags;
        $_GET['sortby'] = 'tags';
        $_GET['sortorder'] = 'ASC'; // Testing default PHP tag sorting logic

        $post = new WP_Post(1);

        $child_post_1 = new WP_Post(2); // Should have Tag 'B'
        $child_post_2 = new WP_Post(3); // Should have Tag 'A'

        // Before usort, order is 2, 3
        $mock_wp_query_posts = [$child_post_1, $child_post_2];

        $mock_post_titles[2] = 'Post B';
        $mock_post_titles[3] = 'Post A';

        $tagA = new stdClass();
        $tagA->name = 'Apple';
        $tagB = new stdClass();
        $tagB->name = 'Banana';

        $mock_post_tags[2] = [$tagB]; // Post 2 has Banana
        $mock_post_tags[3] = [$tagA]; // Post 3 has Apple

        $output = cpl_display_child_pages_shortcode();

        // The shortcode should have sorted them in PHP so Apple (Post 3) comes before Banana (Post 2)
        // We verify the order in the output string
        $posA = strpos($output, 'Post A');
        $posB = strpos($output, 'Post B');

        $this->assertTrue($posA !== false && $posB !== false);
        $this->assertLessThan($posB, $posA, 'Post A should appear before Post B when sorted by tags ASC');
    }

    public function test_cpl_display_child_pages_shortcode_sorting_by_tags_desc_php_logic() {
        global $post, $mock_wp_query_posts, $mock_post_titles, $mock_post_tags;
        $_GET['sortby'] = 'tags';
        $_GET['sortorder'] = 'DESC'; // Testing DESC PHP tag sorting logic

        $post = new WP_Post(1);

        $child_post_1 = new WP_Post(2); // Should have Tag 'B'
        $child_post_2 = new WP_Post(3); // Should have Tag 'A'

        // Before usort, order is 2, 3
        $mock_wp_query_posts = [$child_post_1, $child_post_2];

        $mock_post_titles[2] = 'Post B';
        $mock_post_titles[3] = 'Post A';

        $tagA = new stdClass();
        $tagA->name = 'Apple';
        $tagB = new stdClass();
        $tagB->name = 'Banana';

        $mock_post_tags[2] = [$tagB]; // Post 2 has Banana
        $mock_post_tags[3] = [$tagA]; // Post 3 has Apple

        $output = cpl_display_child_pages_shortcode();

        // The shortcode should have sorted them in PHP so Banana (Post 2) comes before Apple (Post 3)
        $posA = strpos($output, 'Post A');
        $posB = strpos($output, 'Post B');

        $this->assertTrue($posA !== false && $posB !== false);
        $this->assertLessThan($posA, $posB, 'Post B should appear before Post A when sorted by tags DESC');
    }
}
