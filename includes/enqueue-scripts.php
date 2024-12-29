<?php

add_action('wp_enqueue_scripts', function () {
  if (!bricks_is_builder_main()) {
    wp_enqueue_style('bricks-child', get_stylesheet_uri(), ['bricks-frontend'], filemtime(get_stylesheet_directory() . '/style.css'));
  }
});



// Check if the user is logged in and the URL '?bricks=run'
function add_footer_inline_js_for_logged_users() {
  if (is_user_logged_in() && isset($_GET['bricks']) && $_GET['bricks'] === 'run') {
      add_action('wp_footer', function() {
?>
<script>





</script>
<?php
      });
  }
}
add_action('wp', 'add_footer_inline_js_for_logged_users');
