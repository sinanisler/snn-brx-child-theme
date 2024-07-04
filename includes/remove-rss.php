<?php

function snn_remove_rss() {
  $options = get_option('snn_settings');
  if (isset($options['remove_rss'])) {
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'feed_links', 2);
    remove_action('wp_head', 'feed_links_extra', 3);
    remove_action('wp_head', 'wlwmanifest_link');
  }
}
add_action('init', 'snn_remove_rss');

function snn_remove_rss_setting_field() {
  add_settings_field(
    'snn_remove_rss',
    'Disable Remove RSS',
    'snn_remove_rss_callback',
    'snn-settings',
    'snn_general_section'
  );
}
add_action('admin_init', 'snn_remove_rss_setting_field');

function snn_remove_rss_callback() {
  $options = get_option('snn_settings');
  ?>
  <input type="checkbox" name="snn_settings[remove_rss]" value="1" <?php checked(isset($options['remove_rss']), 1); ?>>
  <p>Enabling this setting will remove the RSS feed links from your website's HTML source code.</p>
  <?php
}