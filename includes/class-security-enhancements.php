<?php
namespace AAC;

defined( 'ABSPATH' ) || exit;

class Security_Enhancements {

    public function init() {
        // Enforce Strong Passwords
        add_action( 'user_profile_update_errors', [ $this, 'enforce_strong_passwords' ], 10, 3 );

        // Disable File Editing
        if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
            define( 'DISALLOW_FILE_EDIT', true );
        }

        // Hide WordPress Version
        remove_action( 'wp_head', 'wp_generator' );

        // Disable XML-RPC
        add_filter( 'xmlrpc_enabled', '__return_false' );

        // Limit Login Attempts
        add_action( 'login_init', [ $this, 'limit_login_attempts' ] );

        // Add Security Headers
        add_action( 'send_headers', [ $this, 'add_security_headers' ] );

        // // Change Login URL
        // add_action( 'init', [ $this, 'change_login_url' ] );
        // add_action( 'template_redirect', [ $this, 'login_template_redirect' ] );

        // Implement Content Security Policy
        add_action( 'send_headers', [ $this, 'add_csp_header' ] );
    }

    public function enforce_strong_passwords( $errors, $update, $user ) {
        $password = $_POST['pass1'];
        if ( $update && ! empty( $password ) ) {
            $strength = $this->check_password_strength( $password );
            if ( $strength < 3 ) {
                $errors->add( 'pass', 'Please choose a stronger password.' );
            }
        }
    }

    private function check_password_strength( $password ) {
        $score = 0;
        if ( strlen( $password ) < 8 ) return $score;
        if ( preg_match( '/[0-9]/', $password ) ) $score++;
        if ( preg_match( '/[a-z]/', $password ) && preg_match( '/[A-Z]/', $password ) ) $score++;
        if ( preg_match( '/[^a-zA-Z0-9]/', $password ) ) $score++;
        if ( strlen( $password ) >= 12 ) $score++;
        return $score;
    }

    public function limit_login_attempts() {
        if ( ! session_id() ) {
            session_start();
        }

        $max_login_attempts = 5;
        $lockout_time = 900; // 15 minutes

        if ( isset( $_POST['wp-submit'] ) && isset( $_POST['log'] ) ) {
            if ( isset( $_SESSION['login_attempts'] ) ) {
                $_SESSION['login_attempts']++;
            } else {
                $_SESSION['login_attempts'] = 1;
            }

            if ( $_SESSION['login_attempts'] > $max_login_attempts ) {
                $_SESSION['last_login_time'] = time();
                wp_die( 'Too many failed login attempts. Please try again in 15 minutes.' );
            }
        } elseif ( isset( $_SESSION['last_login_time'] ) && ( time() - $_SESSION['last_login_time'] ) < $lockout_time ) {
            wp_die( 'You are temporarily locked out due to too many failed login attempts. Please try again later.' );
        } elseif ( isset( $_SESSION['last_login_time'] ) && ( time() - $_SESSION['last_login_time'] ) > $lockout_time ) {
            unset( $_SESSION['login_attempts'] );
            unset( $_SESSION['last_login_time'] );
        }
    }

    public function add_security_headers() {
        header( 'X-Content-Type-Options: nosniff' );
        header( 'X-Frame-Options: SAMEORIGIN' );
        header( 'X-XSS-Protection: 1; mode=block' );
        header( 'Referrer-Policy: no-referrer-when-downgrade' );
        header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' );
    }

    public function change_login_url() {
        $login_slug = 'login';
        global $pagenow;

        if ( 'wp-login.php' == $pagenow ) {
            if ( isset( $_GET['action'] ) && $_GET['action'] === 'logout' ) {
                // Allow logout action
            } else {
                wp_redirect( site_url( '/' . $login_slug . '/' ) );
                exit;
            }
        }
    }

    public function login_template_redirect() {
        $login_slug = 'login';
        if ( strtolower( $_SERVER['REQUEST_URI'] ) == '/' . $login_slug . '/' ) {
            require_once( ABSPATH . 'wp-login.php' );
            exit;
        }
    }

    public function add_csp_header() {
        header( "Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://www.googletagmanager.com; img-src 'self' data:; style-src 'self' 'unsafe-inline'; font-src 'self';" );
    }
}
