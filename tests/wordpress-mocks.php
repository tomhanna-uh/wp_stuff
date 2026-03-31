<?php

// Mocking WP_Post class
class WP_Post {
    public $ID;
    public function __construct($id = null) {
        if ($id !== null) {
            $this->ID = $id;
        }
    }
}

// Global state for mocks
global $mock_data;
function cpl_reset_mocks() {
    global $mock_data;
    $mock_data = [
        'is_a' => true,
        'get_permalink' => 'http://example.com/page/',
        'wp_query_posts' => [],
        'wp_get_post_tags' => [],
        'get_the_tags' => false,
        'get_the_title' => 'Mock Title',
        'get_the_date' => 'January 1, 2024',
    ];
}
cpl_reset_mocks(); // Initialize

// Mock functions
if (!function_exists('is_a')) {
    function is_a($object, $class_name, $allow_string = false) {
        global $mock_data;
        if (isset($mock_data['is_a_callable']) && is_callable($mock_data['is_a_callable'])) {
            return $mock_data['is_a_callable']($object, $class_name);
        }
        return is_object($object) && get_class($object) === $class_name;
    }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key($key) {
        return strtolower(preg_replace('/[^a-zA-Z0-9_\-]/', '', $key));
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink($id = 0, $leavename = false) {
        global $mock_data;
        if (isset($mock_data['get_permalink_callable']) && is_callable($mock_data['get_permalink_callable'])) {
            return $mock_data['get_permalink_callable']($id);
        }
        return $mock_data['get_permalink'];
    }
}

if (!function_exists('add_query_arg')) {
    function add_query_arg() {
        $args = func_get_args();
        $url = isset($args[1]) ? $args[1] : '';
        if (is_array($args[0])) {
            $qs = http_build_query($args[0]);
            $url .= (strpos($url, '?') === false ? '?' : '&') . $qs;
        }
        return $url;
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url, $protocols = null, $_context = 'display') {
        return $url;
    }
}

class WP_Query {
    public function __construct($args = []) {
        // Do nothing
    }
    public function get_posts() {
        global $mock_data;
        return $mock_data['wp_query_posts'];
    }
}

if (!function_exists('wp_get_post_tags')) {
    function wp_get_post_tags($post_id = 0, $args = []) {
        global $mock_data;
        if (isset($mock_data['wp_get_post_tags'][$post_id])) {
            return $mock_data['wp_get_post_tags'][$post_id];
        }
        return [];
    }
}

if (!function_exists('setup_postdata')) {
    function setup_postdata($post) {
        // Mock
    }
}

if (!function_exists('get_the_tags')) {
    function get_the_tags($id = 0) {
        global $mock_data;
        if (isset($mock_data['get_the_tags_callable']) && is_callable($mock_data['get_the_tags_callable'])) {
            return $mock_data['get_the_tags_callable']($id);
        }
        return $mock_data['get_the_tags'];
    }
}

if (!function_exists('get_the_title')) {
    function get_the_title($post = 0) {
        global $mock_data;
        if (isset($mock_data['get_the_title_callable']) && is_callable($mock_data['get_the_title_callable'])) {
            return $mock_data['get_the_title_callable']($post);
        }
        return $mock_data['get_the_title'];
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('get_the_date')) {
    function get_the_date($format = '', $post = null) {
        global $mock_data;
        if (isset($mock_data['get_the_date_callable']) && is_callable($mock_data['get_the_date_callable'])) {
            return $mock_data['get_the_date_callable']($format, $post);
        }
        return $mock_data['get_the_date'];
    }
}

if (!function_exists('wp_reset_postdata')) {
    function wp_reset_postdata() {
        // Mock
    }
}

// Add a dummy WP_Term class to mock tags
class WP_Term {
    public $term_id;
    public $name;
    public $slug;
    public $term_group;
    public $term_taxonomy_id;
    public $taxonomy;
    public $description;
    public $parent;
    public $count;
    public $filter;
}
