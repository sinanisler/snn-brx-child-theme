<?php

function snn_disable_file_edit() {
    $options = get_option('snn_security_options');
    if (isset($options['disable_file_edit'])) {
        define('DISALLOW_FILE_EDIT', true);
    }
}
add_action('init', 'snn_disable_file_edit');

function snn_disable_file_edit_setting_field() {
    add_settings_field(
        'disable_file_edit',
        __('Disable File Editing', 'snn'),
        'snn_disable_file_edit_callback',
        'snn-security',
        'snn_security_main_section'
    );
}
add_action('admin_init', 'snn_disable_file_edit_setting_field');

function snn_disable_file_edit_callback() {
    $options = get_option('snn_security_options');
    ?>
    <input type="checkbox" name="snn_security_options[disable_file_edit]" value="1" <?php checked(isset($options['disable_file_edit']), 1); ?>>
    <p><?php esc_html_e('Enabling this setting will disable file editing from the WordPress dashboard.', 'snn'); ?></p>
    <?php
}
?>
