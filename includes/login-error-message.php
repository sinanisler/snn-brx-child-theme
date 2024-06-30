<?php

function snn_failed_login_message() {
  $options = get_option('snn_settings');
  $message = $options['login_error_message'] ?? 'Incorrect, please try again';
  return $message;
}
add_filter('login_errors', 'snn_failed_login_message');

function snn_login_error_message_setting_field() {
  add_settings_field(
    'snn_login_error_message',
    'Login Error Message',
    'snn_login_error_message_callback',
    'snn-settings',
    'snn_general_section'
  );
}
add_action('admin_init', 'snn_login_error_message_setting_field');

function snn_login_error_message_callback() {
  $options = get_option('snn_settings');
  ?>
  <input type="text" name="snn_settings[login_error_message]" value="<?php echo esc_attr($options['login_error_message'] ?? ''); ?>">
  <p>This setting allows you to customize the error message displayed when a user enters incorrect login credentials.</p>
  <?php
}