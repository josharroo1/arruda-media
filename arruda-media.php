<?php
/**
 * Plugin Name: Arruda Media
 * Description: An enhanced plugin that implements advanced configurations for WordPress sites managed by Arruda Media.
 * Version: 1.0
 * Author: Josh Arruda
 * License: GPL2
 */

defined( 'ABSPATH' ) || exit;
define( 'ARRUDA_MEDIA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
// Plugin update checker
require_once ARRUDA_MEDIA_PLUGIN_DIR . 'includes/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$updateChecker = PucFactory::buildUpdateChecker(
    'https://raw.githubusercontent.com/josharroo1/arruda-media/main/arruda-media-update.json',
    __FILE__,
    'arruda-media'
);

 // Autoload Classes
spl_autoload_register( function( $class ) {
    $prefix = 'AAC\\';
    $base_dir = __DIR__ . '/includes/';

    $len = strlen( $prefix );
    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }

    $relative_class = substr( $class, $len );
    $file = $base_dir . 'class-' . strtolower( str_replace( '_', '-', $relative_class ) ) . '.php';

    if ( file_exists( $file ) ) {
        require $file;
    }
});

// Initialize Plugin
function aac_init_plugin() {
    // Disable Comments
    $disable_comments = new AAC\Disable_Comments();
    $disable_comments->init();

    // Security Enhancements
    $security = new AAC\Security_Enhancements();
    $security->init();

    // Performance Optimizations
    $performance = new AAC\Performance_Optimizations();
    $performance->init();

    // Custom Post Types
    $custom_post_types = new AAC\Custom_Post_Types();
    $custom_post_types->init();

    // Custom Taxonomies
    $custom_taxonomies = new AAC\Custom_Taxonomies();
    $custom_taxonomies->init();

    // Google Analytics
    $google_analytics = new AAC\Google_Analytics();
    $google_analytics->init();

    // Admin Customizations
    $admin_customizations = new AAC\Admin_Customizations();
    $admin_customizations->init();

    // Database Optimization
    $database_optimization = new AAC\Database_Optimization();
    $database_optimization->init();

	// SMTP Settings
    $smtp_settings = new AAC\SMTP_Settings();
    $smtp_settings->init();

    // Custom Meta Fields
    $custom_meta_fields = new AAC\Custom_Meta_Fields();
    $custom_meta_fields->init();
}
add_action( 'plugins_loaded', 'aac_init_plugin' );

// agency-advanced-configurations.php

// Enqueue Admin Scripts and Styles
function aac_enqueue_admin_assets() {
    wp_enqueue_style( 'aac-admin-styles', plugin_dir_url( __FILE__ ) . 'assets/css/admin-styles.css', [], '1.0' );
    wp_enqueue_script( 'aac-admin-scripts', plugin_dir_url( __FILE__ ) . 'assets/js/admin-scripts.js', [ 'jquery' ], '1.0', true );
    wp_localize_script( 'aac-admin-scripts', 'aac_ajax_object', [
        'ajax_nonce' => wp_create_nonce( 'aac_nonce' ),
    ] );
}
add_action( 'admin_enqueue_scripts', 'aac_enqueue_admin_assets' );