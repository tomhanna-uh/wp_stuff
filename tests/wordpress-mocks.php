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
define( "ABSPATH", __DIR__ );

// Mocking WP_Post
class WP_Post {
    public $ID;
    public function __construct($id) {
        $this->ID = $id;
    }
}

global $mock_query_posts;
$mock_query_posts = [];

// Mocking WP_Query
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
$GLOBALS['cpl_mock_state'] = [
    'actions' => [],
    'shortcodes' => [],
    'taxonomies' => [],
    'posts' => [],
    'post_tags' => [],
];

function cpl_reset_mocks() {
    $GLOBALS['cpl_mock_state'] = [
        'actions' => [],
        'shortcodes' => [],
        'taxonomies' => [],
        'posts' => [],
        'post_tags' => [],
    ];
    $GLOBALS['post'] = null;
    $_GET = [];
}

function add_action($tag, $function_to_add) {
    $GLOBALS['cpl_mock_state']['actions'][$tag][] = $function_to_add;
}

function add_shortcode($tag, $callback) {
    $GLOBALS['cpl_mock_state']['shortcodes'][$tag] = $callback;
}

function register_taxonomy_for_object_type($taxonomy, $object_type) {
    $GLOBALS['cpl_mock_state']['taxonomies'][] = [$taxonomy, $object_type];
}

function sanitize_key($key) {
    return strtolower(preg_replace('/[^a-z0-9_\-]/i', '', $key));
}

function esc_url($url) {
    return $url;
}

function esc_html($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function add_query_arg($args, $uri = '') {
    $query = http_build_query($args);
    $separator = strpos($uri, '?') !== false ? '&' : '?';
    return $uri . $separator . $query;
}

function get_permalink($id = 0) {
    return 'http://example.com/page/' . $id;
}

function get_the_title($id = 0) {
    foreach ($GLOBALS['cpl_mock_state']['posts'] as $post) {
        if ($post->ID == $id) return $post->post_title;
    }
    return '';
}

function get_the_date($format, $id = 0) {
    foreach ($GLOBALS['cpl_mock_state']['posts'] as $post) {
        if ($post->ID == $id) return date($format, strtotime($post->post_date));
    }
    return '';
}

function get_the_tags($id = 0) {
    return isset($GLOBALS['cpl_mock_state']['post_tags'][$id]) && !empty($GLOBALS['cpl_mock_state']['post_tags'][$id])
        ? $GLOBALS['cpl_mock_state']['post_tags'][$id]
        : false;
}

function wp_get_post_tags($id = 0) {
    return $GLOBALS['cpl_mock_state']['post_tags'][$id] ?? [];
}

function setup_postdata($post) {
    $GLOBALS['post'] = $post;
}

function wp_reset_postdata() {
    // Mock
}

class WP_Post {
    public $ID;
    public $post_title;
    public $post_date;
    public $post_parent;

    public function __construct($args = []) {
        foreach ($args as $k => $v) {
            $this->$k = $v;
        }
    }
}

class WP_Query {
    public $args;
    public function __construct($args) {
        $this->args = $args;
    }
    public function get_posts() {
        $posts = [];
        $parent = $this->args['post_parent'] ?? 0;
        foreach ($GLOBALS['cpl_mock_state']['posts'] as $post) {
            if ($post->post_parent == $parent) {
                $posts[] = $post;
            }
        }

        $orderby = $this->args['orderby'] ?? 'title';
        $order = $this->args['order'] ?? 'ASC';

        if ($orderby !== 'none') {
            usort($posts, function($a, $b) use ($orderby, $order) {
                if ($orderby === 'title') {
                    $cmp = strcmp($a->post_title, $b->post_title);
                } elseif ($orderby === 'date') {
                    $cmp = strtotime($a->post_date) - strtotime($b->post_date);
                } else {
                    $cmp = 0;
                }
                return $order === 'ASC' ? $cmp : -$cmp;
            });
        }

        return $posts;
    }
}
