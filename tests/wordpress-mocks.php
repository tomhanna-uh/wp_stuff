<?php
// Mocking WP functions

if (!class_exists('WP_Post')) {
    class WP_Post {
        public $ID;
        public function __construct($id) {
            $this->ID = $id;
        }
    }
}

if (!class_exists('WP_Query')) {
    class WP_Query {
        public $args;
        public function __construct($args) {
            $this->args = $args;
        }
        public function get_posts() {
            global $mock_wp_query_posts;
            if (isset($mock_wp_query_posts)) {
                return $mock_wp_query_posts;
            }
            return [];
        }
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink($id) {
        return 'http://example.com/?p=' . $id;
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url) {
        return $url;
    }
}

if (!function_exists('add_query_arg')) {
    function add_query_arg($args, $url) {
        $query = http_build_query($args);
        return $url . '&' . $query;
    }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key($key) {
        return strtolower(preg_replace('/[^a-z0-9_\-]/i', '', $key));
    }
}

if (!function_exists('wp_get_post_tags')) {
    function wp_get_post_tags($id) {
        return [];
    }
}

if (!function_exists('setup_postdata')) {
    function setup_postdata($post) {
        global $post;
        $post = $post;
    }
}

if (!function_exists('get_the_tags')) {
    function get_the_tags($id) {
        return false;
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('get_the_title')) {
    function get_the_title($id) {
        return 'Post ' . $id;
    }
}

if (!function_exists('get_the_date')) {
    function get_the_date($format, $id) {
        return 'January 1, 2023';
    }
}

if (!function_exists('wp_reset_postdata')) {
    function wp_reset_postdata() {
        global $post;
        $post = null;
    }
}

if (!function_exists('cpl_reset_mocks')) {
    function cpl_reset_mocks() {
        global $mock_wp_query_posts, $post, $_GET;
        $mock_wp_query_posts = null;
        $post = null;
        $_GET = [];
    }
}

if (!function_exists('register_taxonomy_for_object_type')) {
    function register_taxonomy_for_object_type($taxonomy, $object_type) {
        return true;
    }
}

if (!function_exists('add_action')) {
    function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
        return true;
    }
}

if (!function_exists('add_shortcode')) {
    function add_shortcode($tag, $callback) {
        return true;
    }
}
