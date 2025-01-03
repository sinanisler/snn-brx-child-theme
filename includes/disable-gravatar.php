<?php
// Register the setting and add the settings field
function snn_register_gravatar_setting() {
    // Register the setting
    register_setting(
        'snn_security_options_group', // Option group
        'snn_security_options'        // Option name
    );

    // Add the settings field
    add_settings_field(
        'disable_gravatar',           // ID
        __('Disable Gravatar Support', 'snn'), // Title
        'snn_disable_gravatar_callback', // Callback
        'snn-security',               // Page
        'snn_security_main_section'   // Section
    );
}
add_action('admin_init', 'snn_register_gravatar_setting');

// Callback function to render the checkbox
function snn_disable_gravatar_callback() {
    $options = get_option('snn_security_options');
    ?>
    <input type="checkbox" name="snn_security_options[disable_gravatar]" value="1" <?php checked(isset($options['disable_gravatar']), 1); ?>>
    <p><?php esc_html_e('Check this box to disable Gravatar support throughout the site.', 'snn'); ?></p>
    <?php
}

// Function to disable Gravatar support if the setting is enabled
function snn_maybe_disable_gravatar() {
    $options = get_option('snn_security_options');
    if (isset($options['disable_gravatar']) && $options['disable_gravatar'] == 1) {
        // Disable Gravatar throughout the site
        add_filter('get_avatar', 'snn_disable_gravatar', 10, 2);
    }
}
add_action('init', 'snn_maybe_disable_gravatar');

// Function to return an empty string, effectively disabling Gravatar
function snn_disable_gravatar($avatar, $id_or_email) {
    return '';
}
?>
