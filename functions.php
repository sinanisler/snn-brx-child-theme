<?php

// Settings Page
require_once get_stylesheet_directory() . '/includes/settings-page.php';

// Features & Settings (each with its own settings)
require_once get_stylesheet_directory() . '/includes/remove-wp-version.php';
require_once get_stylesheet_directory() . '/includes/disable-xmlrpc.php';
require_once get_stylesheet_directory() . '/includes/remove-rss.php';
require_once get_stylesheet_directory() . '/includes/login-error-message.php';
require_once get_stylesheet_directory() . '/includes/disable-wp-json-if-not-logged-in.php';
require_once get_stylesheet_directory() . '/includes/auto-update-bricks.php';
require_once get_stylesheet_directory() . '/includes/enqueue-gsap.php';
require_once get_stylesheet_directory() . '/includes/move-bricks-menu.php';
require_once get_stylesheet_directory() . '/includes/snn_custom_css_setting.php';

// Include script enqueuing
require_once get_stylesheet_directory() . '/includes/enqueue-scripts.php';

// Custom Dynamic Data Tags
// https://academy.bricksbuilder.io/article/dynamic-data/
require_once get_stylesheet_directory() . '/dynamic_data_tags/custom_dynamic_data_tags.php';



// Custom Elementors
// https://academy.bricksbuilder.io/article/dynamic-data/

require_once get_stylesheet_directory() . '/custom_elements/title.php';
