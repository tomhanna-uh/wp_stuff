<?php
/**
 * Mock file for WordPress functions and classes to allow testing
 * of child_page_lister.php outside of a WordPress environment.
 */

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/../' );
}

if ( ! class_exists( 'WP_Post' ) ) {
    class WP_Post {
        public $ID;
        public function __construct( $id = 0 ) {
            $this->ID = $id;
        }
    }
}

if ( ! function_exists( 'add_action' ) ) {
    function add_action( $hook, $function_to_add, $priority = 10, $accepted_args = 1 ) {
        // No-op for tests
    }
}

if ( ! function_exists( 'add_shortcode' ) ) {
    function add_shortcode( $tag, $callback ) {
        // No-op for tests
    }
}

if ( ! function_exists( 'register_taxonomy_for_object_type' ) ) {
    function register_taxonomy_for_object_type( $taxonomy, $object_type ) {
        // No-op for tests
    }
}

function cpl_reset_mocks() {
    global $post;
    $post = null;
    $_GET = [];
    $_POST = [];
}
