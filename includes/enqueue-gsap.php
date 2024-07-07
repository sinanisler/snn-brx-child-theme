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
  <p>Enabling this setting will enqueue the GSAP library and its associated scripts on your website.</p>

  <p>Enabling this setting will enqueue the GSAP library and its associated scripts on your website. GSAP is a powerful JavaScript animation library that allows you to create complex and interactive animations.</p>
  <ul>
    <li><code>gsap.min.js</code>: The core GSAP library.</li>
    <li><code>ScrollTrigger.min.js</code>: A GSAP plugin that enables scroll-based animations.</li>
    <li><code>gsap-data-animate.js</code>: A custom script that utilizes GSAP and ScrollTrigger for animating elements based on data attributes.</li>
  </ul>

<pre>
  Supported Features;
  x: Horizontal position (e.g., x: 100).
  y: Vertical position (e.g., y: -50).
  o: Opacity (e.g., o: 0.5).
  r: Rotation angle (e.g., r: 45).
  s: Scale (e.g., s: 0.8).
  start: Scroll trigger start position (e.g., start: top 20%).
  end: Scroll trigger end position (e.g., end: bottom 80%).
  scrub: Scrubbing behavior (e.g., scrub: true).
  pin: Pin element during scroll (e.g., pin: true).
  markers: Display scroll trigger markers (e.g., markers: true).
  toggleClass: Toggle CSS class (e.g., toggleClass: active).
  pinSpacing: Spacing behavior when pinning (e.g., pinSpacing: margin).
  splittext: Split text into characters (e.g., splittext: true).
  stagger: Stagger delay between characters (e.g., stagger: 0.05).
</pre>


  <br><br>
  <p>This example will animate the element by fading it in from the left. The element will start with an x-offset of -50 pixels and an opacity of 0.</p>
  <textarea class="tt1"><h1 data-animate="x:-50, o:0, start:top 80%, end:bottom 20%">Welcome to my website!</h1></textarea>


  <br><br>
  <p>In this example, the div element will scale up from 0.5 to 1 and rotate by 180 degrees. The animation will start when the element is 60% from the top of the viewport and end when it reaches 40% from the bottom.</p>
  <textarea class="tt1"><div data-animate="s:0.5, r:180, start:top 60%, end:bottom 40%, scrub:true">Lorem ipsum dolor sit amet.</div></textarea>





  <?php
}