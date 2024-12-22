<?php

// Disable XML-RPC functionality
function snn_disable_xmlrpc($enabled) {
    $options = get_option('snn_security_options');
    if (isset($options['disable_xmlrpc'])) {
        return false;
    }
    return $enabled;
}
add_filter('xmlrpc_enabled', 'snn_disable_xmlrpc');

// Add XML-RPC settings field
function snn_disable_xmlrpc_setting_field() {
    add_settings_field(
        'disable_xmlrpc',
        __('Disable XML-RPC', 'snn'),
        'snn_disable_xmlrpc_callback',
        'snn-security',
        'snn_security_main_section'
    );
}
add_action('admin_init', 'snn_disable_xmlrpc_setting_field');

// Callback for XML-RPC settings field
function snn_disable_xmlrpc_callback() {
    $options = get_option('snn_security_options');
    ?>
    <input type="checkbox" name="snn_security_options[disable_xmlrpc]" value="1" <?php checked(isset($options['disable_xmlrpc']), 1); ?>>
    <p><?php esc_html_e('Enabling this setting will disable the XML-RPC functionality in WordPress.', 'snn'); ?></p>
    <?php
}
?>
