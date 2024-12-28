<?php

function snn_add_gallery_submenu() {
    add_submenu_page(
        'snn-settings',
        'Media Settings',
        'Media Settings',
        'manage_options',
        'snn-gallery-settings',
        'snn_render_gallery_settings'
    );
}
add_action('admin_menu', 'snn_add_gallery_submenu');

function snn_render_gallery_settings() {
    ?>
    <div class="wrap">
        <h1>Gallery Settings</h1>
        <form method="post" action="options.php">
            <?php
                settings_fields('snn_gallery_settings_group');
                do_settings_sections('snn-gallery-settings');
                submit_button();
            ?>
        </form>
    </div>
    <?php
}

function snn_register_gallery_settings() {
    register_setting(
        'snn_gallery_settings_group',
        'snn_gallery_settings',
        'snn_sanitize_gallery_settings'
    );

    add_settings_section(
        'snn_gallery_settings_section',
        'Gallery Settings',
        'snn_gallery_settings_section_callback',
        'snn-gallery-settings'
    );

    add_settings_field(
        'redirect_media_library',
        'Redirect Media Library Grid View to List View',
        'snn_redirect_media_library_callback',
        'snn-gallery-settings',
        'snn_gallery_settings_section'
    );
}
add_action('admin_init', 'snn_register_gallery_settings');

function snn_sanitize_gallery_settings($input) {
    $sanitized = array();
    $sanitized['redirect_media_library'] = isset($input['redirect_media_library']) && $input['redirect_media_library'] ? 1 : 0;
    return $sanitized;
}

function snn_gallery_settings_section_callback() {
    echo '<p>Configure gallery-related settings below.</p>';
}

function snn_redirect_media_library_callback() {
    $options = get_option('snn_gallery_settings');
    ?>
    <input type="checkbox" name="snn_gallery_settings[redirect_media_library]" value="1" <?php checked(1, isset($options['redirect_media_library']) ? $options['redirect_media_library'] : 0); ?>>
    <p>Enabling this setting will redirect the Media Library grid view to list view by default.</p>
    <?php
}

function snn_redirect_media_library_grid_to_list() {
    $options = get_option('snn_gallery_settings');
    if (
        isset($options['redirect_media_library']) &&
        $options['redirect_media_library'] &&
        is_admin() &&
        strpos($_SERVER['REQUEST_URI'], 'upload.php') !== false
    ) {
        $current_mode = isset($_GET['mode']) ? $_GET['mode'] : '';
        if ($current_mode !== 'list') {
            $list_mode_url = remove_query_arg('mode');
            $list_mode_url = add_query_arg('mode', 'list', $list_mode_url);
            wp_redirect($list_mode_url);
            exit;
        }
    }
}
add_action('admin_init', 'snn_redirect_media_library_grid_to_list');

?>
