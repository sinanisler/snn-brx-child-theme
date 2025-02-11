<?php

/**
  * Define Constants
  *
  * These mimic Bricks Builder original constants for ease of use.
  *
  * Please mind the trailing '/' or there be dragons.
  */
define( 'SNN_PATH', trailingslashit( get_stylesheet_directory() ) );    // SNN dir for require_once files
define( 'SNN_PATH_ASSETS', trailingslashit( SNN_PATH . 'assets' ) );    // SNN assets dir
define( 'SNN_URL', trailingslashit( get_stylesheet_directory_uri() ) ); // SNN URL for enqueue files
define( 'SNN_URL_ASSETS', trailingslashit( SNN_URL . 'assets' ) );      // SNN assets URL

// Use custom-codes-here.php file for adding your custom JS and CSS codes.
require_once SNN_PATH . 'custom-codes-here.php';

// Main Features and Settings
require_once SNN_PATH . 'includes/settings-page.php';
require_once SNN_PATH . 'includes/other-settings.php';
require_once SNN_PATH . 'includes/security-page.php';
require_once SNN_PATH . 'includes/post-types-settings.php';
require_once SNN_PATH . 'includes/custom-field-settings.php';
require_once SNN_PATH . 'includes/taxonomy-settings.php';
require_once SNN_PATH . 'includes/login-settings.php';
require_once SNN_PATH . 'includes/block-editor-settings.php';
require_once SNN_PATH . 'includes/remove-wp-version.php';
require_once SNN_PATH . 'includes/disable-xmlrpc.php';
require_once SNN_PATH . 'includes/disable-file-editing.php';
require_once SNN_PATH . 'includes/remove-rss.php';
require_once SNN_PATH . 'includes/disable-wp-json-if-not-logged-in.php';
require_once SNN_PATH . 'includes/login-logo-change-url-change.php';
require_once SNN_PATH . 'includes/enqueue-scripts.php';
require_once SNN_PATH . 'includes/file-size-column-media.php';
require_once SNN_PATH . 'includes/404-logging.php';
require_once SNN_PATH . 'includes/search-loggins.php';
require_once SNN_PATH . 'includes/301-redirect.php';
require_once SNN_PATH . 'includes/smtp-settings.php';
require_once SNN_PATH . 'includes/mail-logging.php';
require_once SNN_PATH . 'includes/media-settings.php';
require_once SNN_PATH . 'includes/disable-emojis.php';
require_once SNN_PATH . 'includes/disable-gravatar.php';
require_once SNN_PATH . 'includes/editor-settings.php'; 
require_once SNN_PATH . 'includes/editor-color-global-sync.php'; 
require_once SNN_PATH . 'includes/global-classes.php';

// Register Custom Dynamic Tags
require_once SNN_PATH . 'dynamic_data_tags/post-term-count.php';
require_once SNN_PATH . 'dynamic_data_tags/get-contextual-id.php';
require_once SNN_PATH . 'dynamic_data_tags/estimated-post-read-time.php';
require_once SNN_PATH . 'dynamic_data_tags/parent-link.php';
require_once SNN_PATH . 'dynamic_data_tags/user-author-fields.php';

// Utils
require_once SNN_PATH . 'includes/utils.php';

// Register Custom Bricks Builder Elements
add_action('init', function () {
    $custom_html_css_script_file = SNN_PATH . 'includes/elements/custom-html-css-script.php';
    if (file_exists($custom_html_css_script_file)) {
        require_once $custom_html_css_script_file;
        \Bricks\Elements::register_element($custom_html_css_script_file, 'custom-html-css-script', 'Custom_HTML_CSS_Script');
    }

    $custom_maps_file = SNN_PATH . 'includes/elements/custom-maps.php';
    if (file_exists($custom_maps_file)) {
        require_once $custom_maps_file;
        \Bricks\Elements::register_element($custom_maps_file);
    }

    $options = get_option('snn_other_settings');
    if (isset($options['enqueue_gsap']) && $options['enqueue_gsap']) {
        $lottie_animation_file = SNN_PATH . 'includes/elements/lottie-animation.php';
        if (file_exists($lottie_animation_file)) {
            require_once $lottie_animation_file;
            \Bricks\Elements::register_element($lottie_animation_file);
        }
    }

    $gsap_animation_element = SNN_PATH . 'includes/elements/gsap-animations.php';
    if (file_exists($gsap_animation_element)) {
        require_once $gsap_animation_element;
        \Bricks\Elements::register_element($gsap_animation_element);
    }

    $gsap_animation_element = SNN_PATH . 'includes/elements/gsap-text-animations.php';
    if (file_exists($gsap_animation_element)) {
        require_once $gsap_animation_element;
        \Bricks\Elements::register_element($gsap_animation_element);
    }
}, 11);
