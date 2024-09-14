<?php
namespace AAC;

defined( 'ABSPATH' ) || exit;

class Custom_Post_Types {

    private $option_name = 'aac_custom_post_types';

    public function init() {
        add_action( 'init', [ $this, 'register_custom_post_types' ] );
        add_action( 'init', [ $this, 'register_custom_taxonomies' ] );
        add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'wp_ajax_aac_get_post_type_row', [ $this, 'ajax_get_post_type_row' ] );
        add_action( 'admin_notices', [ $this, 'admin_notices' ] );
    }

    private $notices = [];

    /**
     * Register custom post types from settings
     */
    public function register_custom_post_types() {
        $post_types = get_option( $this->option_name, [] );

        if ( ! empty( $post_types ) ) {
            foreach ( $post_types as $post_type ) {
                $labels = $post_type['labels'];

                $args = array(
                    'labels'             => $labels,
                    'public'             => $post_type['public'] ?? true,
                    'has_archive'        => $post_type['has_archive'] ?? true,
                    'rewrite'            => array( 'slug' => $post_type['slug'] ),
                    'supports'           => $post_type['supports'] ?? array( 'title', 'editor', 'thumbnail' ),
                    'menu_icon'          => $post_type['menu_icon'] ?? 'dashicons-admin-post',
                    'exclude_from_search'=> $post_type['exclude_from_search'] ?? false,
                    'capability_type'    => $post_type['capability_type'] ?? 'post',
                    'hierarchical'       => $post_type['hierarchical'] ?? false,
                );

                register_post_type( $post_type['post_type'], $args );
            }
        }
    }

    /**
     * Register custom taxonomies from settings
     */
    public function register_custom_taxonomies() {
        $post_types = get_option( $this->option_name, [] );

        if ( ! empty( $post_types ) ) {
            foreach ( $post_types as $post_type ) {
                if ( ! empty( $post_type['taxonomies'] ) ) {
                    foreach ( $post_type['taxonomies'] as $taxonomy ) {
                        $labels = $taxonomy['labels'];

                        $args = array(
                            'labels'            => $labels,
                            'public'            => $taxonomy['public'] ?? true,
                            'hierarchical'      => $taxonomy['hierarchical'] ?? false,
                            'rewrite'           => array( 'slug' => $taxonomy['slug'] ),
                        );

                        register_taxonomy( $taxonomy['taxonomy'], $post_type['post_type'], $args );
                    }
                }
            }
        }
    }

    /**
     * Add settings page under 'Settings' menu
     */
    public function add_settings_page() {
        add_options_page(
            __( 'Custom Post Types', 'aac' ),
            __( 'Custom Post Types', 'aac' ),
            'manage_options',
            'aac-custom-post-types',
            [ $this, 'render_settings_page' ]
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting( 'aac_custom_post_types_group', $this->option_name, [ $this, 'sanitize_settings' ] );
    }

    /**
     * Sanitize settings input
     */
    public function sanitize_settings( $input ) {
        $sanitized = [];

        if ( ! empty( $input ) && is_array( $input ) ) {
            foreach ( $input as $post_type ) {
                $pt = [];
                $pt['post_type'] = sanitize_key( $post_type['post_type'] );
                $pt['slug'] = sanitize_title( $post_type['slug'] );

                // Check for duplicate post_type
                if ( $this->is_duplicate_post_type( $pt['post_type'], $sanitized ) ) {
                    $this->notices[] = sprintf( __( 'Duplicate post type: %s', 'aac' ), $pt['post_type'] );
                    continue;
                }

                // Sanitize labels
                foreach ( $post_type['labels'] as $key => $label ) {
                    $pt['labels'][ $key ] = sanitize_text_field( $label );
                }

                // Sanitize other fields
                $pt['public'] = isset( $post_type['public'] ) ? (bool) $post_type['public'] : true;
                $pt['has_archive'] = isset( $post_type['has_archive'] ) ? (bool) $post_type['has_archive'] : true;
                $pt['menu_icon'] = sanitize_text_field( $post_type['menu_icon'] );
                $pt['supports'] = array_map( 'sanitize_text_field', $post_type['supports'] ?? [] );
                $pt['exclude_from_search'] = isset( $post_type['exclude_from_search'] ) ? (bool) $post_type['exclude_from_search'] : false;
                $pt['capability_type'] = sanitize_text_field( $post_type['capability_type'] );
                $pt['hierarchical'] = isset( $post_type['hierarchical'] ) ? (bool) $post_type['hierarchical'] : false;

                // Sanitize taxonomies
                if ( ! empty( $post_type['taxonomies'] ) && is_array( $post_type['taxonomies'] ) ) {
                    foreach ( $post_type['taxonomies'] as $taxonomy ) {
                        $tax = [];
                        $tax['taxonomy'] = sanitize_key( $taxonomy['taxonomy'] );
                        $tax['slug'] = sanitize_title( $taxonomy['slug'] );

                        // Check for duplicate taxonomy
                        if ( $this->is_duplicate_taxonomy( $tax['taxonomy'], $pt['taxonomies'] ?? [] ) ) {
                            $this->notices[] = sprintf( __( 'Duplicate taxonomy: %s', 'aac' ), $tax['taxonomy'] );
                            continue;
                        }

                        // Sanitize labels
                        foreach ( $taxonomy['labels'] as $key => $label ) {
                            $tax['labels'][ $key ] = sanitize_text_field( $label );
                        }

                        // Sanitize other fields
                        $tax['public'] = isset( $taxonomy['public'] ) ? (bool) $taxonomy['public'] : true;
                        $tax['hierarchical'] = isset( $taxonomy['hierarchical'] ) ? (bool) $taxonomy['hierarchical'] : false;

                        $pt['taxonomies'][] = $tax;
                    }
                }

                $sanitized[] = $pt;
            }
        }

        return $sanitized;
    }

    private function is_duplicate_post_type( $post_type, $post_types ) {
        foreach ( $post_types as $pt ) {
            if ( $pt['post_type'] === $post_type ) {
                return true;
            }
        }
        return false;
    }

    private function is_duplicate_taxonomy( $taxonomy, $taxonomies ) {
        foreach ( $taxonomies as $tax ) {
            if ( $tax['taxonomy'] === $taxonomy ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Render settings page HTML
     */
    public function render_settings_page() {
        ?>
        <div class="wrap aac-settings-page">
            <h1><?php esc_html_e( 'Custom Post Types', 'aac' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'aac_custom_post_types_group' );
                $post_types = get_option( $this->option_name, [] );
                ?>
                <div id="aac-cpt-container">
                    <?php if ( ! empty( $post_types ) ) : ?>
                        <?php foreach ( $post_types as $index => $post_type ) : ?>
                            <?php $this->render_post_type_section( $post_type, $index ); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <p>
                    <button type="button" class="button button-primary" id="aac-add-cpt"><?php esc_html_e( 'Add New Post Type', 'aac' ); ?></button>
                </p>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render admin notices
     */
    public function admin_notices() {
        foreach ( $this->notices as $notice ) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $notice ) . '</p></div>';
        }
    }

    /**
     * Render a post type section
     */
    private function render_post_type_section( $post_type = [], $index = 0 ) {
        $post_type = wp_parse_args( $post_type, [
            'post_type'    => '',
            'slug'         => '',
            'labels'       => [],
            'public'       => true,
            'has_archive'  => true,
            'menu_icon'    => 'dashicons-admin-post',
            'supports'     => [ 'title', 'editor', 'thumbnail' ],
            'exclude_from_search' => false,
            'capability_type' => 'post',
            'hierarchical' => false,
            'taxonomies'   => [],
        ]);

        $supports = [
            'title'           => __( 'Title', 'aac' ),
            'editor'          => __( 'Editor', 'aac' ),
            'thumbnail'       => __( 'Featured Image', 'aac' ),
            'excerpt'         => __( 'Excerpt', 'aac' ),
            'comments'        => __( 'Comments', 'aac' ),
            'revisions'       => __( 'Revisions', 'aac' ),
            'custom-fields'   => __( 'Custom Fields', 'aac' ),
            'page-attributes' => __( 'Page Attributes', 'aac' ),
        ];

        $capability_types = [
            'post' => __( 'Post', 'aac' ),
            'page' => __( 'Page', 'aac' ),
        ];
        ?>
        <div class="aac-cpt-section">
            <h2><?php esc_html_e( 'Custom Post Type', 'aac' ); ?></h2>
            <button type="button" class="button aac-remove-cpt">&times;</button>
            <div class="aac-cpt-fields">
                <div class="field-group">
                    <label><?php esc_html_e( 'Post Type Slug (internal)', 'aac' ); ?> <span class="required">*</span></label>
                    <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo esc_attr( $index ); ?>][post_type]" value="<?php echo esc_attr( $post_type['post_type'] ); ?>" required>
                </div>
                <div class="field-group">
                    <label><?php esc_html_e( 'Slug (URL)', 'aac' ); ?> <span class="required">*</span></label>
                    <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo esc_attr( $index ); ?>][slug]" value="<?php echo esc_attr( $post_type['slug'] ); ?>" required>
                </div>
                <div class="field-group">
                    <label><?php esc_html_e( 'Labels', 'aac' ); ?></label>
                    <?php
                    $default_labels = [
                        'name'               => '',
                        'singular_name'      => '',
                        'menu_name'          => '',
                        'name_admin_bar'     => '',
                        'add_new'            => '',
                        'add_new_item'       => '',
                        'new_item'           => '',
                        'edit_item'          => '',
                        'view_item'          => '',
                        'all_items'          => '',
                        'search_items'       => '',
                        'parent_item_colon'  => '',
                        'not_found'          => '',
                        'not_found_in_trash' => '',
                    ];
                    $labels = wp_parse_args( $post_type['labels'], $default_labels );

                    foreach ( $labels as $key => $label ) : ?>
                        <div class="label-field">
                            <label><?php echo esc_html( ucfirst( str_replace( '_', ' ', $key ) ) ); ?></label>
                            <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo esc_attr( $index ); ?>][labels][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $label ); ?>" required>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="field-group">
                    <label><?php esc_html_e( 'Settings', 'aac' ); ?></label>
                    <div class="checkbox-group">
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo esc_attr( $index ); ?>][public]" <?php checked( $post_type['public'], true ); ?>> <?php esc_html_e( 'Public', 'aac' ); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo esc_attr( $index ); ?>][has_archive]" <?php checked( $post_type['has_archive'], true ); ?>> <?php esc_html_e( 'Has Archive', 'aac' ); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo esc_attr( $index ); ?>][exclude_from_search]" <?php checked( $post_type['exclude_from_search'], true ); ?>> <?php esc_html_e( 'Exclude from Search', 'aac' ); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo esc_attr( $index ); ?>][hierarchical]" <?php checked( $post_type['hierarchical'], true ); ?>> <?php esc_html_e( 'Hierarchical', 'aac' ); ?>
                        </label>
                    </div>
                    <label><?php esc_html_e( 'Capability Type', 'aac' ); ?></label>
                    <select name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo esc_attr( $index ); ?>][capability_type]">
                        <?php foreach ( $capability_types as $key => $label ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $post_type['capability_type'], $key ); ?>><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label><?php esc_html_e( 'Menu Icon (Dashicon class)', 'aac' ); ?></label>
                    <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo esc_attr( $index ); ?>][menu_icon]" value="<?php echo esc_attr( $post_type['menu_icon'] ); ?>">
                </div>
                <div class="field-group">
                    <label><?php esc_html_e( 'Supports', 'aac' ); ?></label>
                    <div class="checkbox-group">
                        <?php foreach ( $supports as $key => $label ) : ?>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo esc_attr( $index ); ?>][supports][]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $post_type['supports'] ), true ); ?>> <?php echo esc_html( $label ); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Custom Taxonomies -->
                <div class="field-group">
                    <label><?php esc_html_e( 'Custom Taxonomies', 'aac' ); ?></label>
                    <div class="aac-taxonomies-container">
                        <?php
                        if ( ! empty( $post_type['taxonomies'] ) ) :
                            foreach ( $post_type['taxonomies'] as $tax_index => $taxonomy ) :
                                $this->render_taxonomy_section( $taxonomy, $index, $tax_index );
                            endforeach;
                        endif;
                        ?>
                    </div>
                    <button type="button" class="button aac-add-taxonomy"><?php esc_html_e( 'Add Taxonomy', 'aac' ); ?></button>
                </div>
            </div>
            <hr>
        </div>
        <?php
    }

    /**
     * Render a taxonomy section
     */
    private function render_taxonomy_section( $taxonomy = [], $post_type_index = 0, $tax_index = 0 ) {
        $taxonomy = wp_parse_args( $taxonomy, [
            'taxonomy'     => '',
            'slug'         => '',
            'labels'       => [],
            'public'       => true,
            'hierarchical' => false,
        ]);

        ?>
        <div class="aac-taxonomy-section">
            <h3><?php esc_html_e( 'Custom Taxonomy', 'aac' ); ?></h3>
            <button type="button" class="button aac-remove-taxonomy">&times;</button>
            <div class="taxonomy-fields">
                <div class="field-group">
                    <label><?php esc_html_e( 'Taxonomy Slug (internal)', 'aac' ); ?> <span class="required">*</span></label>
                    <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo esc_attr( $post_type_index ); ?>][taxonomies][<?php echo esc_attr( $tax_index ); ?>][taxonomy]" value="<?php echo esc_attr( $taxonomy['taxonomy'] ); ?>" required>
                </div>
                <div class="field-group">
                    <label><?php esc_html_e( 'Slug (URL)', 'aac' ); ?> <span class="required">*</span></label>
                    <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo esc_attr( $post_type_index ); ?>][taxonomies][<?php echo esc_attr( $tax_index ); ?>][slug]" value="<?php echo esc_attr( $taxonomy['slug'] ); ?>" required>
                </div>
                <div class="field-group">
                    <label><?php esc_html_e( 'Labels', 'aac' ); ?></label>
                    <?php
                    $default_labels = [
                        'name'              => '',
                        'singular_name'     => '',
                        'search_items'      => '',
                        'all_items'         => '',
                        'parent_item'       => '',
                        'parent_item_colon' => '',
                        'edit_item'         => '',
                        'update_item'       => '',
                        'add_new_item'      => '',
                        'new_item_name'     => '',
                        'menu_name'         => '',
                    ];
                    $labels = wp_parse_args( $taxonomy['labels'], $default_labels );

                    foreach ( $labels as $key => $label ) : ?>
                        <div class="label-field">
                            <label><?php echo esc_html( ucfirst( str_replace( '_', ' ', $key ) ) ); ?></label>
                            <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo esc_attr( $post_type_index ); ?>][taxonomies][<?php echo esc_attr( $tax_index ); ?>][labels][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $label ); ?>" required>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="field-group">
                    <label><?php esc_html_e( 'Settings', 'aac' ); ?></label>
                    <div class="checkbox-group">
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo esc_attr( $post_type_index ); ?>][taxonomies][<?php echo esc_attr( $tax_index ); ?>][public]" <?php checked( $taxonomy['public'], true ); ?>> <?php esc_html_e( 'Public', 'aac' ); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo esc_attr( $post_type_index ); ?>][taxonomies][<?php echo esc_attr( $tax_index ); ?>][hierarchical]" <?php checked( $taxonomy['hierarchical'], true ); ?>> <?php esc_html_e( 'Hierarchical', 'aac' ); ?>
                        </label>
                    </div>
                </div>
            </div>
            <hr>
        </div>
        <?php
    }

    /**
     * Handle AJAX request to get a new post type or taxonomy section
     */
    public function ajax_get_post_type_row() {
        check_ajax_referer( 'aac_nonce', 'security' );

        $type = sanitize_text_field( $_POST['type'] );
        $index = intval( $_POST['index'] );

        if ( $type === 'post_type' ) {
            ob_start();
            $this->render_post_type_section( [], $index );
            $row = ob_get_clean();
            echo $row;
        } elseif ( $type === 'taxonomy' ) {
            $post_type_index = intval( $_POST['post_type_index'] );
            ob_start();
            $this->render_taxonomy_section( [], $post_type_index, $index );
            $row = ob_get_clean();
            echo $row;
        }

        wp_die();
    }
}