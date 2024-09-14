<?php
namespace AAC;

defined( 'ABSPATH' ) || exit;

class Google_Analytics {

    public function init() {
        add_action( 'wp_head', [ $this, 'add_google_analytics' ] );
    }

    public function add_google_analytics() {
        if ( ! is_admin() ) {
            ?>
            <!-- Global site tag (gtag.js) - Google Analytics -->
            <script async src="https://www.googletagmanager.com/gtag/js?id=YOUR_TRACKING_ID"></script>
            <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', 'YOUR_TRACKING_ID');
            </script>
            <?php
        }
    }
}
