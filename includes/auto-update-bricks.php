<?php

function snn_auto_update_bricks_theme($update, $item) {
    $options = get_option('snn_settings');
    
    // Check if the auto-update option is enabled and that the theme slug is exactly 'bricks'.
    // Note: Ensure that the property you're checking matches your theme's update object. 
    // Often the theme directory name is stored in $item->theme.
    if ( isset($options['auto_update_bricks']) && $options['auto_update_bricks'] == 1 && isset($item->theme) && 'bricks' === $item->theme ) {
        return true;
    }
    
    return $update;
}
add_filter('auto_update_theme', 'snn_auto_update_bricks_theme', 10, 2);

function snn_auto_update_bricks_setting_field() {
    add_settings_field(
        'snn_auto_update_bricks',
        __('Auto Update Bricks Theme (Bricks Builder Only)', 'snn'),
        'snn_auto_update_bricks_callback',
        'snn-settings',
        'snn_general_section'
    );
}
add_action('admin_init', 'snn_auto_update_bricks_setting_field');

function snn_auto_update_bricks_callback() {
    $options = get_option('snn_settings');
    ?>
    <input type="checkbox" name="snn_settings[auto_update_bricks]" value="1" <?php checked(isset($options['auto_update_bricks']), 1); ?>>
    <p><?php _e('Enabling this setting will automatically update the Bricks theme whenever a new version is available.', 'snn'); ?></p>
    <?php
}