<?php
  
// Use custom-codes-here.php file for adding your custom JS and CSS codes. 
require_once get_stylesheet_directory() . '/custom-codes-here.php';


// Main Features and Settings
require_once get_stylesheet_directory() . '/includes/settings-page.php';
require_once get_stylesheet_directory() . '/includes/other-settings.php';
require_once get_stylesheet_directory() . '/includes/security-page.php';
require_once get_stylesheet_directory() . '/includes/post-types-settings.php';
require_once get_stylesheet_directory() . '/includes/custom-field-settings.php';
require_once get_stylesheet_directory() . '/includes/taxonomy-settings.php';
require_once get_stylesheet_directory() . '/includes/login-settings.php';
require_once get_stylesheet_directory() . '/includes/block-editor-settings.php';
require_once get_stylesheet_directory() . '/includes/remove-wp-version.php';
require_once get_stylesheet_directory() . '/includes/disable-xmlrpc.php';
require_once get_stylesheet_directory() . '/includes/disable-file-editing.php';
require_once get_stylesheet_directory() . '/includes/remove-rss.php';
require_once get_stylesheet_directory() . '/includes/disable-wp-json-if-not-logged-in.php';
require_once get_stylesheet_directory() . '/includes/login-logo-change-url-change.php';
require_once get_stylesheet_directory() . '/includes/enqueue-scripts.php';
require_once get_stylesheet_directory() . '/includes/file-size-column-media.php';
require_once get_stylesheet_directory() . '/includes/404-logging.php';
require_once get_stylesheet_directory() . '/includes/301-redirect.php';
require_once get_stylesheet_directory() . '/includes/smtp-settings.php';
require_once get_stylesheet_directory() . '/includes/mail-logging.php';
require_once get_stylesheet_directory() . '/includes/media-settings.php';
require_once get_stylesheet_directory() . '/includes/disable-emojis.php';
require_once get_stylesheet_directory() . '/includes/disable-gravatar.php';
require_once get_stylesheet_directory() . '/includes/editor-settings.php';
require_once get_stylesheet_directory() . '/includes/global-classes.php';


// Register Custom Dynamic Tags
require_once get_stylesheet_directory() . '/dynamic_data_tags/post-term-count.php';
require_once get_stylesheet_directory() . '/dynamic_data_tags/get-contextual-id.php';
require_once get_stylesheet_directory() . '/dynamic_data_tags/estimated-post-read-time.php';
require_once get_stylesheet_directory() . '/dynamic_data_tags/parent-link.php';
require_once get_stylesheet_directory() . '/dynamic_data_tags/user-author-fields.php';



// Utils
require_once get_stylesheet_directory() . '/includes/utils.php';










// Register Custom Bricks Builder Elements
add_action('init', function () {
    $custom_html_css_script_file = get_stylesheet_directory() . '/custom_elements/custom-html-css-script.php';
    if (file_exists($custom_html_css_script_file)) {
        require_once $custom_html_css_script_file;
        \Bricks\Elements::register_element($custom_html_css_script_file, 'custom-html-css-script', 'Custom_HTML_CSS_Script');
    }

    $custom_maps_file = get_stylesheet_directory() . '/custom_elements/custom-maps.php';
    if (file_exists($custom_maps_file)) {
        require_once $custom_maps_file;
        \Bricks\Elements::register_element($custom_maps_file);
    }

    $options = get_option('snn_other_settings');
    if (isset($options['enqueue_gsap']) && $options['enqueue_gsap']) {
        $lottie_animation_file = get_stylesheet_directory() . '/custom_elements/lottie-animation.php';
        if (file_exists($lottie_animation_file)) {
            require_once $lottie_animation_file;
            \Bricks\Elements::register_element($lottie_animation_file);
        }
    }

    $custom_maps_file = get_stylesheet_directory() . '/custom_elements/experimental.php';
    if (file_exists($custom_maps_file)) {
        require_once $custom_maps_file;
        \Bricks\Elements::register_element($custom_maps_file);
    }


}, 11);


