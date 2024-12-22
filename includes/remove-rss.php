<?php

// Remove RSS feed links from the head
function snn_remove_rss() {
    $options = get_option('snn_security_options');
    if (isset($options['remove_rss'])) {
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'feed_links', 2);
        remove_action('wp_head', 'feed_links_extra', 3);
        remove_action('wp_head', 'wlwmanifest_link');
    }
}
add_action('init', 'snn_remove_rss');

/**
 * Add Remove RSS settings field
 */
function snn_remove_rss_setting_field() {
    add_settings_field(
        'remove_rss',
        __('Disable Remove RSS', 'snn'),
        'snn_remove_rss_callback',
        'snn-security',
        'snn_security_main_section'
    );
}
add_action('admin_init', 'snn_remove_rss_setting_field');

/**
 * Callback for Remove RSS settings field
 */
function snn_remove_rss_callback() {
    $options = get_option('snn_security_options');
    ?>
    <input type="checkbox" name="snn_security_options[remove_rss]" value="1" <?php checked(isset($options['remove_rss']), 1); ?>>
    <p><?php esc_html_e('Enabling this setting will remove the RSS feed links from your website\'s HTML source code.', 'snn'); ?></p>
    <?php
}
?>
