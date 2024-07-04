<?php

function snn_disable_file_edit($enabled) {
  $options = get_option('snn_settings');
  if (isset($options['disable_file_edit'])) {
    define('DISALLOW_FILE_EDIT', true);
    return false;
  }
  return $enabled;
}
add_filter('admin_init', 'snn_disable_file_edit');

function snn_disable_file_edit_setting_field() {
  add_settings_field(
    'snn_disable_file_edit',
    'Disable File Editing',
    'snn_disable_file_edit_callback',
    'snn-settings',
    'snn_general_section'
  );
}
add_action('admin_init', 'snn_disable_file_edit_setting_field');

function snn_disable_file_edit_callback() {
  $options = get_option('snn_settings');
  ?>
  <input type="checkbox" name="snn_settings[disable_file_edit]" value="1" <?php checked(isset($options['disable_file_edit']), 1); ?>>
  <p>Enabling this setting will disable file editing from the WordPress dashboard.</p>
  <?php
}
