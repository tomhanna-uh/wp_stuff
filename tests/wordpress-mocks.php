<?php

define( "ABSPATH", __DIR__ );

// Mocking WP_Post
class WP_Post {
    public $ID;
    public function __construct($id) {
        $this->ID = $id;
    }
}

// Mocking WP_Query
class WP_Query {
    public $args;
    public function __construct($args) {
        $this->args = $args;
    }
    public function get_posts() {
        global $mock_wp_query_posts;
        return $mock_wp_query_posts ?? [];
    }
}

// Mocking WordPress functions
function sanitize_key($key) {
    return strtolower(preg_replace('/[^a-z0-9_\-]/i', '', $key));
}

function get_permalink($id) {
    global $mock_permalinks;
    return $mock_permalinks[$id] ?? "http://example.com/page/" . $id;
}

function esc_url($url) {
    return $url; // Basic mock
}

function add_query_arg($args, $url) {
    $query = http_build_query($args);
    return $url . '?' . $query;
}

function wp_get_post_tags($id) {
    global $mock_post_tags;
    return $mock_post_tags[$id] ?? [];
}

function setup_postdata($post) {
    // No-op for testing
}

function get_the_tags($id) {
    global $mock_post_tags;
    return $mock_post_tags[$id] ?? false;
}

function get_the_title($id) {
    global $mock_post_titles;
    return $mock_post_titles[$id] ?? "Post Title $id";
}

function get_the_date($format, $id) {
    global $mock_post_dates;
    return $mock_post_dates[$id] ?? "January 1, 2024";
}

function esc_html($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function wp_reset_postdata() {
    // No-op for testing
}

function add_shortcode($tag, $callback) {
    global $mock_shortcodes;
    $mock_shortcodes[$tag] = $callback;
}

function register_taxonomy_for_object_type($taxonomy, $object_type) {
    global $mock_taxonomies;
    $mock_taxonomies[] = [$taxonomy, $object_type];
}

function add_action($hook, $callback) {
    global $mock_actions;
    $mock_actions[$hook][] = $callback;
}

function cpl_reset_mocks() {
    global $post, $original_post, $mock_wp_query_posts, $mock_post_tags, $mock_post_titles, $mock_post_dates, $mock_permalinks, $mock_shortcodes, $mock_taxonomies, $mock_actions;
    $post = null;
    $original_post = null;
    $mock_wp_query_posts = [];
    $mock_post_tags = [];
    $mock_post_titles = [];
    $mock_post_dates = [];
    $mock_permalinks = [];
    $_GET = [];
    $mock_shortcodes = [];
    $mock_taxonomies = [];
    $mock_actions = [];
}
