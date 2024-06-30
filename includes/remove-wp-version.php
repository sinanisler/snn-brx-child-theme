<?php

function snn_remove_wp_version() {
  $options = get_option('snn_settings');
  if (isset($options['remove_wp_version'])) {
    return '';
  }
}
add_filter('the_generator', 'snn_remove_wp_version');

function snn_remove_wp_version_setting_field() {
  add_settings_field(
    'snn_remove_wp_version',
    'Remove WP Version',
    'snn_remove_wp_version_callback',
    'snn-settings',
    'snn_general_section'
  );
}
add_action('admin_init', 'snn_remove_wp_version_setting_field');

function snn_remove_wp_version_callback() {
  $options = get_option('snn_settings');
  ?>
  <input type="checkbox" name="snn_settings[remove_wp_version]" value="1" <?php checked(isset($options['remove_wp_version']), 1); ?>>
  <p>Enabling this setting will remove the WordPress version number from your website's HTML source code.</p>
  <?php
}