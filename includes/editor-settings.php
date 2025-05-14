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
        __('SNN Settings Panel and Bricks Builder Global Colors Sync with Color Palette', 'snn'),
        'snn_render_checkbox_field',
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
        <?php _e('Enable Bricks Builder Editor Color Fix', 'snn'); ?><br>
        <?php _e('This setting will show the primary global color variables inside all color palettes.<br>
        It will load those color palettes as :root frontend colors as well.<br>
        Only create one Theme Style.<br>', 'snn'); ?>
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
