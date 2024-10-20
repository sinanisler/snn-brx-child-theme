<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Include Settings Pages
require_once get_stylesheet_directory() . '/includes/settings-page.php';
require_once get_stylesheet_directory() . '/includes/documentation-settings-page.php';

// Include Block Editor Settings
include_once get_stylesheet_directory() . '/includes/block-editor-settings.php';


// Include Feature Files
$includes = [
    'remove-wp-version.php',
    'disable-xmlrpc.php',
    'disable-file-editing.php',
    'remove-rss.php',
    'disable-wp-json-if-not-logged-in.php',
    'move-bricks-menu.php',
    'auto-update-bricks.php',
    'login-math-captcha.php',
    'login-error-message.php',
    'login-logo-change-url-change.php',
    'wp-revision-limit.php',
    'enqueue-gsap.php',
    'enqueue-scripts.php',
];

foreach ( $includes as $file ) {
    $filepath = get_stylesheet_directory() . '/includes/' . $file;
    if ( file_exists( $filepath ) ) {
        require_once $filepath;
    }
}

// Include Custom Dynamic Data Tags
require_once get_stylesheet_directory() . '/dynamic_data_tags/custom_dynamic_data_tags.php';

// Register Custom Elements
add_action( 'init', function() {
    $element_files = [
        get_stylesheet_directory() . '/custom_elements/custom-html-css-script.php',
    ];

    foreach ( $element_files as $file ) {
        if ( file_exists( $file ) ) {
            require_once $file;
            $element_class = 'Custom_HTML_CSS_Script';
            \Bricks\Elements::register_element( $file, 'custom-html-css-script', $element_class );
        }
    }
}, 11 );
