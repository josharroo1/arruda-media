<?php
namespace AAC;

defined( 'ABSPATH' ) || exit;

class Performance_Optimizations {

    public function init() {
        // Enable Gzip Compression
        add_action( 'init', [ $this, 'enable_gzip_compression' ] );

        // Defer Parsing of JavaScript
        add_filter( 'script_loader_tag', [ $this, 'defer_parsing_of_js' ], 10, 3 );

        // Remove Query Strings from Static Resources
        add_filter( 'script_loader_src', [ $this, 'remove_query_strings' ], 15, 1 );
        add_filter( 'style_loader_src', [ $this, 'remove_query_strings' ], 15, 1 );

        // Disable Emojis
        add_action( 'init', [ $this, 'disable_emojis' ] );

        // Enable Lazy Loading for Images
        add_filter( 'the_content', [ $this, 'add_lazy_loading_to_images' ] );

        // Disable Heartbeat API
        add_action( 'init', [ $this, 'disable_heartbeat' ], 1 );
    }

    public function enable_gzip_compression() {
        if ( ! is_admin() && ! ob_get_level() ) {
            ob_start( 'ob_gzhandler' );
        }
    }

    public function defer_parsing_of_js( $tag, $handle, $src ) {
        if ( is_admin() ) return $tag;
        if ( strpos( $tag, 'jquery.js' ) ) return $tag;
        return str_replace( ' src', ' defer src', $tag );
    }

    public function remove_query_strings( $src ) {
        if ( strpos( $src, '?ver=' ) )
            $src = remove_query_arg( 'ver', $src );
        return $src;
    }

    public function disable_emojis() {
        remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
        remove_action( 'wp_print_styles', 'print_emoji_styles' );
        remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
        remove_action( 'admin_print_styles', 'print_emoji_styles' );
        remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
        remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
        remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
        add_filter( 'emoji_svg_url', '__return_false' );
    }

    public function add_lazy_loading_to_images( $content ) {
        if ( ! is_feed() || ! is_preview() ) {
            $content = preg_replace( '/<img(.*?)src=/', '<img$1loading="lazy" src=', $content );
        }
        return $content;
    }

    public function disable_heartbeat() {
        wp_deregister_script( 'heartbeat' );
    }
}
