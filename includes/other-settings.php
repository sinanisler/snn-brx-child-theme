<?php

function snn_add_other_settings_submenu() {
    add_submenu_page(
        'snn-settings',
        'Other Settings',
        'Other Settings',
        'manage_options',
        'snn-other-settings',
        'snn_render_other_settings'
    );
}
add_action('admin_menu', 'snn_add_other_settings_submenu');

function snn_render_other_settings() {
    ?>
    <div class="wrap">
        <h1>Other Settings</h1>
        <form method="post" action="options.php">
            <?php
                settings_fields('snn_other_settings_group');
                do_settings_sections('snn-other-settings');
                submit_button();
            ?>
        </form>
    </div>
    <?php
}

function snn_register_other_settings() {
    register_setting(
        'snn_other_settings_group',
        'snn_other_settings',
        'snn_sanitize_other_settings'
    );

    add_settings_section(
        'snn_other_settings_section',
        'Other Settings',
        'snn_other_settings_section_callback',
        'snn-other-settings'
    );

    add_settings_field(
        'enqueue_gsap',
        'Enable GSAP',
        'snn_enqueue_gsap_callback',
        'snn-other-settings',
        'snn_other_settings_section'
    );

    add_settings_field(
        'revisions_limit',
        'Limit Post Revisions',
        'snn_revisions_limit_callback',
        'snn-other-settings',
        'snn_other_settings_section'
    );

    add_settings_field(
        'auto_update_bricks',
        'Auto Update Bricks Theme (Main Theme Only)',
        'snn_auto_update_bricks_callback',
        'snn-other-settings',
        'snn_other_settings_section'
    );

    add_settings_field(
        'move_bricks_menu',
        'Move Bricks Menu to End',
        'snn_move_bricks_menu_callback',
        'snn-other-settings',
        'snn_other_settings_section'
    );

    add_settings_field(
        'disable_comments',
        'Disable Comments',
        'snn_disable_comments_callback',
        'snn-other-settings',
        'snn_other_settings_section'
    );
}
add_action('admin_init', 'snn_register_other_settings');

function snn_sanitize_other_settings($input) {
    $sanitized = array();

    $sanitized['enqueue_gsap'] = isset($input['enqueue_gsap']) && $input['enqueue_gsap'] ? 1 : 0;

    if (isset($input['revisions_limit'])) {
        $sanitized['revisions_limit'] = intval($input['revisions_limit']);
    }

    $sanitized['auto_update_bricks'] = isset($input['auto_update_bricks']) && $input['auto_update_bricks'] ? 1 : 0;

    $sanitized['move_bricks_menu'] = isset($input['move_bricks_menu']) && $input['move_bricks_menu'] ? 1 : 0;

    $sanitized['disable_comments'] = isset($input['disable_comments']) && $input['disable_comments'] ? 1 : 0;

    return $sanitized;
}

function snn_other_settings_section_callback() {
    echo '<p>Configure additional settings for your site below.</p>';
}

function snn_enqueue_gsap_callback() {
    $options = get_option('snn_other_settings');
    ?>
    <input type="checkbox" name="snn_other_settings[enqueue_gsap]" value="1" <?php checked(1, isset($options['enqueue_gsap']) ? $options['enqueue_gsap'] : 0); ?>>
    <?php
}

function snn_revisions_limit_callback() {
    $options = get_option('snn_other_settings');
    $value = isset($options['revisions_limit']) ? intval($options['revisions_limit']) : '';
    ?>
    <input type="number" name="snn_other_settings[revisions_limit]" value="<?php echo esc_attr($value); ?>" placeholder="500">
    <?php
}

function snn_auto_update_bricks_callback() {
    $options = get_option('snn_other_settings');
    ?>
    <input type="checkbox" name="snn_other_settings[auto_update_bricks]" value="1" <?php checked(1, isset($options['auto_update_bricks']) ? $options['auto_update_bricks'] : 0); ?>>
    <?php
}

function snn_move_bricks_menu_callback() {
    $options = get_option('snn_other_settings');
    ?>
    <input type="checkbox" name="snn_other_settings[move_bricks_menu]" value="1" <?php checked(1, isset($options['move_bricks_menu']) ? $options['move_bricks_menu'] : 0); ?>>
    <?php
}

function snn_disable_comments_callback() {
    $options = get_option('snn_other_settings');
    ?>
    <label>
    <input type="checkbox" name="snn_other_settings[disable_comments]" value="1" <?php checked(1, isset($options['disable_comments']) ? $options['disable_comments'] : 0); ?> >
    </label>
    <?php
}

function snn_hide_comments_section() {
    $options = get_option('snn_other_settings');
    if (isset($options['disable_comments']) && $options['disable_comments']) {
        echo '<style>#menu-comments { display: none !important; }</style>';
        update_option('comment_registration', 1); 
    } else {
        update_option('comment_registration', 0); 
    }
}
add_action('admin_head', 'snn_hide_comments_section');

?>
