<?php
namespace AAC;

defined( 'ABSPATH' ) || exit;

class Admin_Customizations {

    public function init() {
        // Remove Dashboard Widgets
        add_action( 'wp_dashboard_setup', [ $this, 'remove_dashboard_widgets' ] );

        // Customize Admin Footer
        add_filter( 'admin_footer_text', [ $this, 'custom_admin_footer' ] );

        // Add Support for Additional MIME Types
        add_filter( 'upload_mimes', [ $this, 'custom_mime_types' ] );

        // Set Default Permalink Structure
        add_action( 'init', [ $this, 'set_default_permalink' ] );

        // Limit Post Revisions
        if ( ! defined( 'WP_POST_REVISIONS' ) ) {
            define( 'WP_POST_REVISIONS', 5 );
        }
    }

    public function remove_dashboard_widgets() {
        remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );
        remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
        remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
    }

    public function custom_admin_footer() {
        echo 'Powered by Arruda Media.';
    }

    public function custom_mime_types( $mimes ) {
        $mimes['svg']  = 'image/svg+xml';
        $mimes['json'] = 'application/json';
        return $mimes;
    }

    public function set_default_permalink() {
        global $wp_rewrite;
        if ( ! get_option( 'permalink_structure' ) ) {
            $wp_rewrite->set_permalink_structure( '/%postname%/' );
            $wp_rewrite->flush_rules();
        }
    }
}