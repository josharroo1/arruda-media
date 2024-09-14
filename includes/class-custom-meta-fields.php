<?php
namespace AAC;

defined( 'ABSPATH' ) || exit;

class Custom_Meta_Fields {

    private $option_name = 'aac_custom_meta_fields';
    private $meta_fields;

    public function init() {
        // Settings page
        add_action( 'admin_menu', [ $this, 'add_meta_fields_page' ] );
        add_action( 'admin_init', [ $this, 'register_meta_fields_settings' ] );
        // Meta boxes
        add_action( 'add_meta_boxes', [ $this, 'add_custom_meta_boxes' ] );
        add_action( 'save_post', [ $this, 'save_meta_fields' ] );
        // Shortcode
        add_shortcode( 'aac_meta_field', [ $this, 'display_meta_field_shortcode' ] );
        // AJAX
        add_action( 'wp_ajax_aac_get_meta_field_row', [ $this, 'ajax_get_meta_field_row' ] );
    }

    /**
     * Add settings page under 'Settings' menu
     */
    public function add_meta_fields_page() {
        add_options_page(
            'Custom Meta Fields',
            'Custom Meta Fields',
            'manage_options',
            'aac-custom-meta-fields',
            [ $this, 'render_meta_fields_page' ]
        );
    }

    /**
     * Register settings
     */
    public function register_meta_fields_settings() {
        register_setting( 'aac_custom_meta_fields_group', $this->option_name, [ $this, 'sanitize_settings' ] );
    }

    /**
     * Sanitize settings input
     */
    public function sanitize_settings( $input ) {
        $sanitized = [];
        if ( ! empty( $input ) && is_array( $input ) ) {
            foreach ( $input as $field ) {
                $mf = [];
                $mf['label'] = sanitize_text_field( $field['label'] );
                $mf['key'] = sanitize_key( $field['key'] );
                $mf['type'] = sanitize_text_field( $field['type'] );
                $mf['placeholder'] = sanitize_text_field( $field['placeholder'] ?? '' );
                $mf['options'] = sanitize_text_field( $field['options'] ?? '' );
                $mf['post_types'] = array_map( 'sanitize_text_field', $field['post_types'] ?? [] );
                $sanitized[] = $mf;
            }
        }
        return $sanitized;
    }

    /**
     * Render settings page HTML
     */
    public function render_meta_fields_page() {
        ?>
        <div class="wrap aac-meta-fields-page">
            <h1>Custom Meta Fields</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'aac_custom_meta_fields_group' );
                $meta_fields = get_option( $this->option_name, [] );
                ?>
                <div id="aac-meta-fields-container">
                    <?php if ( ! empty( $meta_fields ) ) : ?>
                        <?php foreach ( $meta_fields as $index => $field ) : ?>
                            <?php $this->render_meta_field_row( $field, $index ); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <p>
                    <button type="button" class="button button-primary" id="aac-add-meta-field">Add New Meta Field</button>
                </p>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render a meta field row
     */
    private function render_meta_field_row( $field = [], $index = 0 ) {
        $field = wp_parse_args( $field, [
            'label'       => '',
            'key'         => '',
            'type'        => 'text',
            'placeholder' => '',
            'options'     => '',
            'post_types'  => [],
        ] );

        $field_types = [
            'text'     => 'Text',
            'textarea' => 'Textarea',
            'number'   => 'Number',
            'email'    => 'Email',
            'url'      => 'URL',
            'checkbox' => 'Checkbox',
            'radio'    => 'Radio',
            'select'   => 'Select',
            'date'     => 'Date',
            'color'    => 'Color',
        ];

        $post_types = get_post_types( [ 'public' => true ], 'objects' );
        ?>
        <div class="aac-meta-field-row">
            <h2>Meta Field</h2>
            <button type="button" class="button aac-remove-meta-field">&times;</button>
            <div class="field-group">
                <label>Field Label <span class="required">*</span></label>
                <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo $index; ?>][label]" value="<?php echo esc_attr( $field['label'] ); ?>" required>
            </div>
            <div class="field-group">
                <label>Field Key <span class="required">*</span></label>
                <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo $index; ?>][key]" value="<?php echo esc_attr( $field['key'] ); ?>" required>
                <p class="description">Shortcode: [aac_meta_field key="<?php echo esc_attr( $field['key'] ); ?>"]</p>
            </div>
            <div class="field-group">
                <label>Field Type</label>
                <select name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo $index; ?>][type]">
                    <?php foreach ( $field_types as $key => $label ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $field['type'], $key ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field-group options-field" style="display: <?php echo ( $field['type'] == 'select' || $field['type'] == 'radio' ) ? 'block' : 'none'; ?>;">
                <label>Options (comma-separated)</label>
                <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo $index; ?>][options]" value="<?php echo esc_attr( $field['options'] ); ?>">
            </div>
            <div class="field-group">
                <label>Placeholder</label>
                <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo $index; ?>][placeholder]" value="<?php echo esc_attr( $field['placeholder'] ); ?>">
            </div>
            <div class="field-group">
                <label>Post Types</label>
                <div class="checkbox-group">
                    <?php foreach ( $post_types as $pt ) : ?>
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo $index; ?>][post_types][]" value="<?php echo esc_attr( $pt->name ); ?>" <?php checked( in_array( $pt->name, $field['post_types'] ), true ); ?>>
                            <?php echo esc_html( $pt->labels->name ); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <hr>
        </div>
        <?php
    }

    /**
     * Handle AJAX request to get a new meta field row
     */
    public function ajax_get_meta_field_row() {
        check_ajax_referer( 'aac_nonce', 'security' );

        $index = intval( $_POST['index'] );
        ob_start();
        $this->render_meta_field_row( [], $index );
        $row = ob_get_clean();
        echo $row;
        wp_die();
    }

    /**
     * Add custom meta boxes to selected post types
     */
    public function add_custom_meta_boxes() {
        $options = get_option( $this->option_name );
        $this->meta_fields = $options ?? [];

        if ( ! empty( $this->meta_fields ) ) {
            foreach ( $this->meta_fields as $field ) {
                if ( ! empty( $field['post_types'] ) ) {
                    foreach ( $field['post_types'] as $post_type ) {
                        add_meta_box(
                            'aac_meta_box_' . sanitize_key( $field['key'] ),
                            esc_html( $field['label'] ),
                            [ $this, 'render_meta_box' ],
                            $post_type,
                            'normal',
                            'default',
                            $field
                        );
                    }
                }
            }
        }
    }

    /**
     * Render the custom meta box fields
     */
    public function render_meta_box( $post, $metabox ) {
        $field = $metabox['args'];
        $value = get_post_meta( $post->ID, $field['key'], true );
        wp_nonce_field( 'aac_save_meta_fields', 'aac_meta_fields_nonce' );

        $type = $field['type'];
        $placeholder = $field['placeholder'];

        switch ( $type ) {
            case 'text':
            case 'email':
            case 'url':
            case 'number':
            case 'date':
            case 'color':
                echo '<input type="' . esc_attr( $type ) . '" name="' . esc_attr( $field['key'] ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $placeholder ) . '" style="width:100%;">';
                break;

            case 'textarea':
                echo '<textarea name="' . esc_attr( $field['key'] ) . '" placeholder="' . esc_attr( $placeholder ) . '" style="width:100%;">' . esc_textarea( $value ) . '</textarea>';
                break;

            case 'checkbox':
                echo '<label><input type="checkbox" name="' . esc_attr( $field['key'] ) . '" value="1" ' . checked( $value, '1', false ) . '> ' . esc_html( $field['label'] ) . '</label>';
                break;

            case 'radio':
            case 'select':
                if ( ! empty( $field['options'] ) ) {
                    $options = explode( ',', $field['options'] );
                    if ( $type == 'select' ) {
                        echo '<select name="' . esc_attr( $field['key'] ) . '" style="width:100%;">';
                        foreach ( $options as $option ) {
                            $option = trim( $option );
                            echo '<option value="' . esc_attr( $option ) . '" ' . selected( $value, $option, false ) . '>' . esc_html( $option ) . '</option>';
                        }
                        echo '</select>';
                    } elseif ( $type == 'radio' ) {
                        foreach ( $options as $option ) {
                            $option = trim( $option );
                            echo '<label><input type="radio" name="' . esc_attr( $field['key'] ) . '" value="' . esc_attr( $option ) . '" ' . checked( $value, $option, false ) . '> ' . esc_html( $option ) . '</label><br>';
                        }
                    }
                }
                break;

            default:
                // Default to text input
                echo '<input type="text" name="' . esc_attr( $field['key'] ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $placeholder ) . '" style="width:100%;">';
                break;
        }
    }

    /**
     * Save custom meta field values when the post is saved
     */
    public function save_meta_fields( $post_id ) {
        if ( ! isset( $_POST['aac_meta_fields_nonce'] ) || ! wp_verify_nonce( $_POST['aac_meta_fields_nonce'], 'aac_save_meta_fields' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        foreach ( $this->meta_fields as $field ) {
            $key = $field['key'];
            if ( isset( $_POST[ $key ] ) ) {
                $value = $_POST[ $key ];
                switch ( $field['type'] ) {
                    case 'email':
                        $value = sanitize_email( $value );
                        break;
                    case 'url':
                        $value = esc_url_raw( $value );
                        break;
                    case 'number':
                        $value = floatval( $value );
                        break;
                    case 'date':
                        $value = sanitize_text_field( $value );
                        break;
                    case 'color':
                        $value = sanitize_hex_color( $value );
                        break;
                    case 'checkbox':
                        $value = '1';
                        break;
                    default:
                        $value = sanitize_text_field( $value );
                        break;
                }
                update_post_meta( $post_id, $key, $value );
            } else {
                if ( $field['type'] == 'checkbox' ) {
                    update_post_meta( $post_id, $key, '0' );
                } else {
                    delete_post_meta( $post_id, $key );
                }
            }
        }
    }

    /**
     * Shortcode to display custom meta field values
     */
    public function display_meta_field_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'key' => '',
            'post_id' => get_the_ID(),
        ], $atts, 'aac_meta_field' );

        if ( empty( $atts['key'] ) ) {
            return '';
        }

        $value = get_post_meta( $atts['post_id'], $atts['key'], true );

        return esc_html( $value );
    }
}
