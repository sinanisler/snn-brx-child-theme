<?php

// Settings Page
require_once get_stylesheet_directory() . '/includes/settings-page.php';

// Features & Settings (each with its own settings)
require_once get_stylesheet_directory() . '/includes/remove-wp-version.php';
require_once get_stylesheet_directory() . '/includes/disable-xmlrpc.php';
require_once get_stylesheet_directory() . '/includes/disable-file-editing.php';
require_once get_stylesheet_directory() . '/includes/remove-rss.php';
require_once get_stylesheet_directory() . '/includes/login-error-message.php';
require_once get_stylesheet_directory() . '/includes/disable-wp-json-if-not-logged-in.php';
require_once get_stylesheet_directory() . '/includes/login-math-captcha.php';
require_once get_stylesheet_directory() . '/includes/login-logo-change-url-change.php';
require_once get_stylesheet_directory() . '/includes/auto-update-bricks.php';
require_once get_stylesheet_directory() . '/includes/enqueue-gsap.php';
require_once get_stylesheet_directory() . '/includes/move-bricks-menu.php';
require_once get_stylesheet_directory() . '/includes/snn-custom-css-setting.php';
require_once get_stylesheet_directory() . '/includes/snn-custom-css-js-cdn.php';
require_once get_stylesheet_directory() . '/includes/wp-head-css-frontend.php';
require_once get_stylesheet_directory() . '/includes/wp-footer-html-frontend.php';



// Include script enqueuing
require_once get_stylesheet_directory() . '/includes/enqueue-scripts.php';



// Custom Dynamic Data Tags
// https://academy.bricksbuilder.io/article/dynamic-data/
require_once get_stylesheet_directory() . '/dynamic_data_tags/custom_dynamic_data_tags.php';



// Register Custom Elements
// https://academy.bricksbuilder.io/article/create-your-own-elements/

add_action( 'init', function() {
    $element_files = [
      __DIR__ . '/custom_elements/custom-html-css-script.php', 
    ];
  
    foreach ( $element_files as $file ) {
      if (file_exists($file)) {
        require_once $file;
        $element_class = 'Custom_HTML_CSS_Script'; 
        \Bricks\Elements::register_element($file, 'custom-html-css-script', $element_class); 
      }
    }
  }, 11 );
  





// number of post revisions to keep
function custom_revisions_limit($num, $post) {
  return 100;
}
add_filter('wp_revisions_to_keep', 'custom_revisions_limit', 10, 2);

