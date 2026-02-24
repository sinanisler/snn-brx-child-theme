<?php

add_action('admin_menu', 'custom_editor_settings_page');
function custom_editor_settings_page() {
    add_submenu_page(
        'snn-settings',
        __('Editor Settings', 'snn'),
        __('Editor Settings', 'snn'),
        'manage_options',
        'editor-settings',
        'snn_render_editor_settings_page',
        2
    );
}

function snn_render_editor_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('Bricks Builder Editor Settings', 'snn'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('snn_editor_settings_group');
            do_settings_sections('snn-editor-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', 'snn_register_editor_settings');
function snn_register_editor_settings() {
    register_setting(
        'snn_editor_settings_group',
        'snn_editor_settings',
        'snn_sanitize_editor_settings'
    );

    add_settings_section(
        'snn_editor_settings_section',
        __('Editor Settings', 'snn'),
        'snn_editor_settings_section_callback',
        'snn-editor-settings'
    );

    add_settings_field(
        'hide_element_icons',
        __('Hide Elements Icons on Bricks Editor', 'snn'),
        'snn_hide_element_icons_callback',
        'snn-editor-settings',
        'snn_editor_settings_section'
    );

    add_settings_field(
        'make_compact_but_keep_icons',
        __('Make Elements Compact But Keep Icons on Bricks Editor', 'snn'),
        'snn_make_compact_but_keep_icons_callback',
        'snn-editor-settings',
        'snn_editor_settings_section'
    );

    add_settings_field(
        'make_elements_wide',
        __('Make Elements Wide on Bricks Editor', 'snn'),
        'snn_make_elements_wide_callback',
        'snn-editor-settings',
        'snn_editor_settings_section'
    );

    add_settings_field(
        'snn_bricks_builder_color_fix_field',
        __('Enable SNN-BRX Panel. Image Optimizer, Clamp Generator and old Global Colors', 'snn'),
        'snn_render_checkbox_field',
        'snn-editor-settings',
        'snn_editor_settings_section'
    );

    add_settings_field(
        'comingsoon_bypass',
        __('URL Coming Soon Bypass', 'snn'),
        'snn_comingsoon_bypass_callback',
        'snn-editor-settings',
        'snn_editor_settings_section'
    );
}

function snn_sanitize_editor_settings($input) {
    $sanitized = array();

    // Sanitize existing settings
    $sanitized['snn_bricks_builder_color_fix'] = isset($input['snn_bricks_builder_color_fix']) && $input['snn_bricks_builder_color_fix'] ? 1 : 0;

    // Sanitize the three other settings
    $sanitized['hide_element_icons'] = isset($input['hide_element_icons']) && $input['hide_element_icons'] ? 1 : 0;
    $sanitized['make_compact_but_keep_icons'] = isset($input['make_compact_but_keep_icons']) && $input['make_compact_but_keep_icons'] ? 1 : 0;
    $sanitized['make_elements_wide'] = isset($input['make_elements_wide']) && $input['make_elements_wide'] ? 1 : 0;

    // Sanitize coming soon bypass settings
    $sanitized['comingsoon_bypass_enabled'] = isset($input['comingsoon_bypass_enabled']) && $input['comingsoon_bypass_enabled'] ? 1 : 0;
    $raw_slug = isset($input['comingsoon_bypass_slug']) ? $input['comingsoon_bypass_slug'] : 'comingsoon_false';
    $raw_slug = preg_replace('/[^a-zA-Z0-9_-]/', '', $raw_slug);
    $sanitized['comingsoon_bypass_slug'] = $raw_slug !== '' ? $raw_slug : 'comingsoon_false';

    return $sanitized;
}

function snn_editor_settings_section_callback() {
    ?>
    <p>
        <?php
        echo sprintf(
            __('Configure Bricks Builder editor-specific settings below.<br>', 'snn')
        );
        ?>
    </p>
    <?php
}

function snn_render_checkbox_field() {
    $options = get_option('snn_editor_settings');
    $checked = isset($options['snn_bricks_builder_color_fix']) ? $options['snn_bricks_builder_color_fix'] : 0;
    ?>
    <input type="checkbox" id="snn_bricks_builder_color_fix" name="snn_editor_settings[snn_bricks_builder_color_fix]" value="1" <?php checked(1, $checked, true); ?> />
    <label for="snn_bricks_builder_color_fix">
        <?php _e('Enable SNN-BRX Editor Panel', 'snn'); ?><br>
    </label>
    <?php
}

function snn_hide_element_icons_callback() {
    $options = get_option('snn_editor_settings');
    $checked = isset($options['hide_element_icons']) ? $options['hide_element_icons'] : 0;
    ?>
    <label>
        <input type="checkbox" name="snn_editor_settings[hide_element_icons]" value="1" <?php checked(1, $checked, true); ?>>
        <?php _e('Hide Elements Icons on Bricks Editor', 'snn'); ?>
    </label>
    <?php
}

function snn_make_compact_but_keep_icons_callback() {
    $options = get_option('snn_editor_settings');
    $checked = isset($options['make_compact_but_keep_icons']) ? $options['make_compact_but_keep_icons'] : 0;
    ?>
    <label>
        <input type="checkbox" name="snn_editor_settings[make_compact_but_keep_icons]" value="1" <?php checked(1, $checked, true); ?>>
        <?php _e('Make Elements Compact But Keep Icons', 'snn'); ?>
    </label>
    <?php
}

function snn_make_elements_wide_callback() {
    $options = get_option('snn_editor_settings');
    $checked = isset($options['make_elements_wide']) ? $options['make_elements_wide'] : 0;
    ?>
    <label>
        <input type="checkbox" name="snn_editor_settings[make_elements_wide]" value="1" <?php checked(1, $checked, true); ?>>
        <?php _e('Make Elements Wide on Bricks Editor', 'snn'); ?>
    </label>
    <?php
}

function snn_add_inline_css_if_bricks_run() {
    // Ensure this runs only on the frontend
    if (is_admin()) {
        return;
    }

    $options_editor = get_option('snn_editor_settings');

    if (isset($_GET['bricks']) && $_GET['bricks'] === 'run') {
        $inline_css = '';

        if (isset($options_editor['hide_element_icons']) && $options_editor['hide_element_icons']) {
            $inline_css .= '
                .bricks-add-element .element-icon {
                    display: none;
                }
                #bricks-panel-elements .sortable-wrapper {
                    margin: 0 0 5px;
                    padding-left: 8px;
                    padding-right: 8px;
                }
                #bricks-panel-elements-categories .category-title {
                    padding-left: 8px;
                    padding-right: 8px;
                }
                #bricks-panel-elements-categories .category-title {
                    line-height: 0;
                    padding-top: 10px;
                    padding-bottom: 10px;
                }
                .bricks-add-element .element-label {
                    box-shadow: 0 0;
                    font-size: 14px;
                    padding: 0 3px;
                    line-height: 30px;
                }
            ';
        }

        if (isset($options_editor['make_compact_but_keep_icons']) && $options_editor['make_compact_but_keep_icons']) {
            $inline_css .= '
                .bricks-add-element .element-icon {
                    float: left;
                    width: 24px;
                    height: auto;
                    font-size: 14px;
                    line-height: 32px;
                }
                #bricks-panel-elements .sortable-wrapper {
                    margin: 0 0 5px;
                    padding-left: 8px;
                    padding-right: 8px;
                }
                #bricks-panel-elements-categories .category-title {
                    padding-left: 8px;
                    padding-right: 8px;
                }
                #bricks-panel-elements-categories .category-title {
                    line-height: 0;
                    padding-top: 10px;
                    padding-bottom: 10px;
                }
                .bricks-add-element .element-label {
                    box-shadow: 0 0;
                    font-size: 14px;
                    padding: 0 3px;
                    line-height: 30px;
                }
            ';
        }

        if (isset($options_editor['make_elements_wide']) && $options_editor['make_elements_wide']) {
            $inline_css .= '
                #bricks-panel-elements .sortable-wrapper {
                    grid-template-columns: 1fr;
                }
            ';
        }

        if (!empty($inline_css)) {
            echo '<style>' . $inline_css . '</style>';
        }
    }
}
add_action('wp_head', 'snn_add_inline_css_if_bricks_run');

function snn_comingsoon_bypass_callback() {
    $options = get_option('snn_editor_settings');
    $enabled = isset($options['comingsoon_bypass_enabled']) ? $options['comingsoon_bypass_enabled'] : 0;
    $slug    = isset($options['comingsoon_bypass_slug']) ? $options['comingsoon_bypass_slug'] : 'comingsoon_false';
    ?>
    <label>
        <input type="checkbox" name="snn_editor_settings[comingsoon_bypass_enabled]" value="1" <?php checked(1, $enabled, true); ?>>
        <?php _e('Enable URL Coming Soon Bypass', 'snn'); ?>
    </label>
    <br><br>
    <label for="comingsoon_bypass_slug"><?php _e('Bypass Key (URL slug)', 'snn'); ?></label><br>
    <input
        type="text"
        id="comingsoon_bypass_slug"
        name="snn_editor_settings[comingsoon_bypass_slug]"
        value="<?php echo esc_attr($slug); ?>"
        style="width:300px;"
        pattern="[a-zA-Z0-9_-]+"
        placeholder="comingsoon_false"
    >
    <p class="description">
        <?php _e('Only letters, numbers, <code>-</code> and <code>_</code> are allowed.', 'snn'); ?>
        <?php if (!empty($slug)): ?>
            <br><strong><?php _e('Preview link:', 'snn'); ?></strong> 
            <code style="background:#f0f0f1;padding:2px 6px;border-radius:3px;user-select:all;">
                <a href="<?php echo esc_url(home_url('/?key=' . $slug)); ?>" target="_blank" style="text-decoration:none;color:inherit;">
                    <?php echo esc_html(home_url('/?key=' . $slug)); ?>
                </a>
            </code>
            <span style="color:#666;font-size:12px;"><?php _e('(valid 24 h)', 'snn'); ?></span>
        <?php else: ?>
            <br><?php _e('Visit <code>/?key=YOUR_SLUG</code> to set the bypass cookie (valid 24 h).', 'snn'); ?>
        <?php endif; ?>
    </p>
    <?php
}

// ── Coming Soon Bypass Logic ──────────────────────────────────────────────────

define( 'SNN_BYPASS_COOKIE_NAME', 'bricks_bypass_maintenance' );

function snn_bypass_token( $slug ) {
    return hash_hmac( 'sha256', $slug, wp_salt( 'auth' ) );
}

add_action( 'template_redirect', 'snn_comingsoon_bypass_handle_url', 1 );
function snn_comingsoon_bypass_handle_url() {
    $options = get_option('snn_editor_settings');
    if ( empty($options['comingsoon_bypass_enabled']) ) {
        return;
    }
    $secret_key = isset($options['comingsoon_bypass_slug']) ? $options['comingsoon_bypass_slug'] : 'comingsoon_false';

    // phpcs:ignore WordPress.Security.NonceVerification
    if ( ! isset( $_GET['key'] ) ) {
        return;
    }
    $supplied_key = sanitize_text_field( wp_unslash( $_GET['key'] ) );
    if ( ! hash_equals( $secret_key, $supplied_key ) ) {
        return;
    }

    $token   = snn_bypass_token( $secret_key );
    $expires = time() + 86400; // 24 hours

    setcookie(
        SNN_BYPASS_COOKIE_NAME,
        $token,
        [
            'expires'  => $expires,
            'path'     => '/',
            'secure'   => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );

    $clean_url = remove_query_arg( 'key' );
    wp_safe_redirect( $clean_url, 302 );
    exit;
}

add_filter( 'bricks/maintenance/should_apply', 'snn_comingsoon_bypass_check_cookie', 10, 2 );
function snn_comingsoon_bypass_check_cookie( $should_apply, $mode ) {
    if ( ! $should_apply ) {
        return $should_apply;
    }
    $options = get_option('snn_editor_settings');
    if ( empty($options['comingsoon_bypass_enabled']) ) {
        return $should_apply;
    }
    $secret_key = isset($options['comingsoon_bypass_slug']) ? $options['comingsoon_bypass_slug'] : 'comingsoon_false';

    if ( ! isset( $_COOKIE[ SNN_BYPASS_COOKIE_NAME ] ) ) {
        return $should_apply;
    }
    $cookie_value   = sanitize_text_field( wp_unslash( $_COOKIE[ SNN_BYPASS_COOKIE_NAME ] ) );
    $expected_token = snn_bypass_token( $secret_key );

    if ( hash_equals( $expected_token, $cookie_value ) ) {
        return false; // Bypass – show the real site.
    }
    return $should_apply;
}
