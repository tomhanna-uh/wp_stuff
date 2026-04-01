<?php

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
