<?php
 
// Use custom-codes-here.php file for adding your custom JS and CSS codes. 
require_once get_stylesheet_directory() . '/custom-codes-here.php';




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
// require_once get_stylesheet_directory() . '/includes/theme-json-styles.php';
require_once get_stylesheet_directory() . '/includes/enqueue-scripts.php';
require_once get_stylesheet_directory() . '/includes/file-size-column-media.php';
require_once get_stylesheet_directory() . '/includes/404-logging.php';
require_once get_stylesheet_directory() . '/includes/301-redirect.php';
require_once get_stylesheet_directory() . '/includes/smtp-settings.php';
require_once get_stylesheet_directory() . '/includes/mail-logging.php';
require_once get_stylesheet_directory() . '/includes/media-settings.php';
require_once get_stylesheet_directory() . '/includes/disable-emojis.php';
require_once get_stylesheet_directory() . '/includes/disable-gravatar.php';




require_once get_stylesheet_directory() . '/dynamic_data_tags/custom_dynamic_data_tags.php';





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



}, 11);






// Enable JSON file uploads in the Media Library
function allow_json_upload($mimes) {
    // Add .json to the list of allowed mime types
    $mimes['json'] = 'application/json';
    return $mimes;
}
add_filter('upload_mimes', 'allow_json_upload');

// Fix MIME type check for JSON uploads (for security and compatibility)
function check_json_filetype($data, $file, $filename, $mimes) {
    // Get the file extension
    $filetype = wp_check_filetype($filename, $mimes);
    
    // If the extension is JSON, update the type and ext
    if ($filetype['ext'] === 'json') {
        $data['ext'] = 'json';
        $data['type'] = 'application/json';
    }

    return $data;
}
add_filter('wp_check_filetype_and_ext', 'check_json_filetype', 10, 4);

// Allow JSON files to bypass the upload restriction in WordPress
function allow_unfiltered_json_upload($file) {
    // Check for JSON file type
    if ($file['type'] === 'application/json') {
        // No error for JSON files
        $file['error'] = false;
    }
    return $file;
}
add_filter('wp_handle_upload_prefilter', 'allow_unfiltered_json_upload');

// Display JSON files properly in the Media Library (optional)
function enable_json_preview_in_media_library($response, $attachment, $meta) {
    // Ensure the file is a JSON file
    if ($response['mime'] === 'application/json') {
        // Provide a basic preview message for JSON files
        $response['uploaded_filename'] = basename(get_attached_file($attachment->ID));
        $response['url'] = wp_get_attachment_url($attachment->ID);
    }
    return $response;
}
add_filter('wp_prepare_attachment_for_js', 'enable_json_preview_in_media_library', 10, 3);
