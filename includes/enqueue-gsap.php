<?php

function snn_enqueue_gsap_scripts() {
  $options = get_option('snn_settings');
  if (isset($options['enqueue_gsap'])) {
    wp_enqueue_script('gsap-js', get_stylesheet_directory_uri() . '/js/gsap.min.js', array(), false, true);
    wp_enqueue_script('gsap-st-js', get_stylesheet_directory_uri() . '/js/ScrollTrigger.min.js', array('gsap-js'), false, true);
    wp_enqueue_script('gsap-data-js', get_stylesheet_directory_uri() . '/js/gsap-data-animate.js?v0.01', array(), false, true);
  }
}
add_action('wp_enqueue_scripts', 'snn_enqueue_gsap_scripts');

function snn_enqueue_gsap_setting_field() {
  add_settings_field(
    'snn_enqueue_gsap',
    'Enable GSAP',
    'snn_enqueue_gsap_callback',
    'snn-settings',
    'snn_general_section'
  );
}
add_action('admin_init', 'snn_enqueue_gsap_setting_field');

function snn_enqueue_gsap_callback() {
  $options = get_option('snn_settings');
  ?>
  <input type="checkbox" name="snn_settings[enqueue_gsap]" value="1" <?php checked(isset($options['enqueue_gsap']), 1); ?>>
  <p>Enabling this setting will enqueue the GSAP library and its associated scripts on your website. </p>

  <p>Enabling this setting will enqueue the GSAP library and its associated scripts on your website. <br>
    GSAP is a powerful JavaScript animation library that allows you to create complex and interactive animations.</p>
  <ul>
    <li><code>gsap.min.js</code>: The core GSAP library.</li>
    <li><code>ScrollTrigger.min.js</code>: A GSAP plugin that enables scroll-based animations.</li>
    <li><code>gsap-data-animate.js</code>: A custom script that utilizes GSAP and ScrollTrigger for animating elements based on data attributes.</li>
    <li>Read <a href="/wp-admin/admin.php?page=snn-documentation">Documentation for more details</a></li>
  </ul>





  <?php
}