<?php
namespace AAC;

defined( 'ABSPATH' ) || exit;

class Disable_Comments {

    public function init() {
        add_action( 'init', [ $this, 'disable_comments_post_types_support' ] );
        add_filter( 'comments_open', '__return_false', 20, 2 );
        add_filter( 'pings_open', '__return_false', 20, 2 );
        add_filter( 'comments_array', '__return_empty_array', 10, 2 );
        add_action( 'admin_menu', [ $this, 'remove_comments_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'redirect_comments_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'remove_comments_dashboard' ] );
        add_action( 'wp_before_admin_bar_render', [ $this, 'remove_comments_admin_bar' ] );
    }

    public function disable_comments_post_types_support() {
        $post_types = get_post_types();
        foreach ( $post_types as $post_type ) {
            if ( post_type_supports( $post_type, 'comments' ) ) {
                remove_post_type_support( $post_type, 'comments' );
                remove_post_type_support( $post_type, 'trackbacks' );
            }
        }
    }

    public function remove_comments_admin_menu() {
        remove_menu_page( 'edit-comments.php' );
    }

    public function redirect_comments_admin_menu() {
        global $pagenow;
        if ( $pagenow === 'edit-comments.php' ) {
            wp_redirect( admin_url() ); exit;
        }
    }

    public function remove_comments_dashboard() {
        remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
    }

    public function remove_comments_admin_bar() {
        global $wp_admin_bar;
        $wp_admin_bar->remove_menu( 'comments' );
    }
}