<?php
namespace AAC;

defined( 'ABSPATH' ) || exit;

class SMTP_Settings {

    public function init() {
        add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'phpmailer_init', [ $this, 'configure_phpmailer' ] );
    }

    public function add_settings_page() {
        add_options_page(
            'SMTP Settings',
            'SMTP Settings',
            'manage_options',
            'aac-smtp-settings',
            [ $this, 'render_settings_page' ]
        );
    }

    public function register_settings() {
        register_setting( 'aac_smtp_settings_group', 'aac_smtp_settings' );

        add_settings_section(
            'aac_smtp_settings_section',
            'SMTP Configuration',
            null,
            'aac-smtp-settings'
        );

        add_settings_field(
            'postmark_api_key',
            'Postmark API Key',
            [ $this, 'render_api_key_field' ],
            'aac-smtp-settings',
            'aac_smtp_settings_section'
        );

        add_settings_field(
            'sender_email',
            'Sender Email',
            [ $this, 'render_sender_email_field' ],
            'aac-smtp-settings',
            'aac_smtp_settings_section'
        );

        add_settings_field(
            'sender_name',
            'Sender Name',
            [ $this, 'render_sender_name_field' ],
            'aac-smtp-settings',
            'aac_smtp_settings_section'
        );
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>SMTP Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'aac_smtp_settings_group' );
                do_settings_sections( 'aac-smtp-settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function render_api_key_field() {
        $options = get_option( 'aac_smtp_settings' );
        ?>
        <input type="text" name="aac_smtp_settings[postmark_api_key]" value="<?php echo esc_attr( $options['postmark_api_key'] ?? '' ); ?>" size="50">
        <?php
    }

    public function render_sender_email_field() {
        $options = get_option( 'aac_smtp_settings' );
        ?>
        <input type="email" name="aac_smtp_settings[sender_email]" value="<?php echo esc_attr( $options['sender_email'] ?? '' ); ?>" size="50">
        <?php
    }

    public function render_sender_name_field() {
        $options = get_option( 'aac_smtp_settings' );
        ?>
        <input type="text" name="aac_smtp_settings[sender_name]" value="<?php echo esc_attr( $options['sender_name'] ?? '' ); ?>" size="50">
        <?php
    }

    public function configure_phpmailer( $phpmailer ) {
        $options = get_option( 'aac_smtp_settings' );
        if ( empty( $options['postmark_api_key'] ) || empty( $options['sender_email'] ) ) {
            return;
        }

        $phpmailer->isSMTP();
        $phpmailer->Host       = 'smtp.postmarkapp.com';
        $phpmailer->SMTPAuth   = true;
        $phpmailer->Port       = 587;
        $phpmailer->Username   = $options['postmark_api_key'];
        $phpmailer->Password   = $options['postmark_api_key'];
        $phpmailer->SMTPSecure = 'tls';
        $phpmailer->From       = $options['sender_email'];
        $phpmailer->FromName   = $options['sender_name'] ?? get_bloginfo( 'name' );
    }
}