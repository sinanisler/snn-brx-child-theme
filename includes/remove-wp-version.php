<?php

// Remove/Hide WordPress version
function snn_remove_wp_version() {
    $options = get_option('snn_security_options');
    if (isset($options['remove_wp_version'])) {
        return '';
    }
    return;
}
add_filter('the_generator', 'snn_remove_wp_version');

/**
 * Add Remove WP Version settings field
 */
function snn_remove_wp_version_setting_field() {
    add_settings_field(
        'remove_wp_version',
        __('Remove/Hide WP Version', 'snn'),
        'snn_remove_wp_version_callback',
        'snn-security',
        'snn_security_main_section'
    );
}
add_action('admin_init', 'snn_remove_wp_version_setting_field');

/**
 * Callback for Remove WP Version settings field
 */
function snn_remove_wp_version_callback() {
    $options = get_option('snn_security_options');
    ?>
    <input type="checkbox" name="snn_security_options[remove_wp_version]" value="1" <?php checked(isset($options['remove_wp_version']), 1); ?>>
    <p><?php esc_html_e('Enabling this setting will remove the WordPress version number from your website\'s HTML source code.', 'snn'); ?></p>
    <?php
}
?>
