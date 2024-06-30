<?php

add_action('wp_enqueue_scripts', function () {
  if (!bricks_is_builder_main()) {
    wp_enqueue_style('bricks-child', get_stylesheet_uri(), ['bricks-frontend'], filemtime(get_stylesheet_directory() . '/style.css'));
  }
});