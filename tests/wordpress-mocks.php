<?php
// tests/wordpress-mocks.php

function register_taxonomy_for_object_type( $taxonomy, $object_type ) {}
function add_action( $hook, $function ) {}
function add_shortcode( $tag, $callback ) {}

function sanitize_key($key) {
    return strtolower(preg_replace('/[^a-z0-9_\-]/i', '', $key));
}

function get_permalink($id = 0) {
    return "http://example.com/?page_id=$id";
}

function add_query_arg($args, $url) {
    $query = http_build_query($args);
    return strpos($url, '?') !== false ? "$url&$query" : "$url?$query";
}

function esc_url($url) { return $url; }
function esc_html($text) { return htmlspecialchars($text ?? ''); }

class WP_Post {
    public $ID;
    public function __construct($id) {
        $this->ID = $id;
    }
}

global $mock_query_posts;
$mock_query_posts = [];

class WP_Query {
    public $args;
    public function __construct($args) {
        $this->args = $args;
    }
    public function get_posts() {
        global $mock_query_posts;
        return $mock_query_posts;
    }
}

global $mock_post_tags;
$mock_post_tags = [];

function wp_get_post_tags($id) {
    global $mock_post_tags;
    return isset($mock_post_tags[$id]) ? $mock_post_tags[$id] : [];
}

function get_the_tags($id) {
    $tags = wp_get_post_tags($id);
    return empty($tags) ? false : $tags;
}

function setup_postdata($post) {
    global $post_data_mock;
    $post_data_mock = $post;
}

function wp_reset_postdata() {
    global $post_data_mock;
    $post_data_mock = null;
}

global $mock_titles;
$mock_titles = [];

function get_the_title($id = 0) {
    global $mock_titles;
    return isset($mock_titles[$id]) ? $mock_titles[$id] : "Post $id";
}

function get_the_date($format, $id = 0) {
    return "January 1, 2023";
}

function cpl_reset_mocks() {
    global $mock_query_posts, $mock_post_tags, $mock_titles, $post, $_GET;
    $mock_query_posts = [];
    $mock_post_tags = [];
    $mock_titles = [];
    $post = null;
    $_GET = [];
}
