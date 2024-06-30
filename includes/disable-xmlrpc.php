<?php

function snn_disable_xmlrpc($enabled) {
  $options = get_option('snn_settings');
  if (isset($options['disable_xmlrpc'])) {
    return false;
  }
  return $enabled;
}
add_filter('xmlrpc_enabled', 'snn_disable_xmlrpc');

function snn_disable_xmlrpc_setting_field() {
  add_settings_field(
    'snn_disable_xmlrpc',
    'Disable XML-RPC',
    'snn_disable_xmlrpc_callback',
    'snn-settings',
    'snn_general_section'
  );
}
add_action('admin_init', 'snn_disable_xmlrpc_setting_field');

function snn_disable_xmlrpc_callback() {
  $options = get_option('snn_settings');
  ?>
  <input type="checkbox" name="snn_settings[disable_xmlrpc]" value="1" <?php checked(isset($options['disable_xmlrpc']), 1); ?>>
  <p>Enabling this setting will disable the XML-RPC functionality in WordPress.</p>
  <?php
}