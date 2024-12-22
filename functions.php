<?php


// Frontend Head ınline JS and CSS
function snn_custom_inline_frontend_head_code() {
    ?>
<style type="text/css">


</style>
<script>


</script>
<?php
}
add_action( 'wp_head', 'snn_custom_inline_frontend_head_code', 1 );




// Frontend Footer Inline JS and CSS
function snn_custom_footer_inline() { ?>
<style>


</style>
<script>


</script>
<?php
}
add_action('wp_footer', 'snn_custom_footer_inline');











// Include Settings Pages
require_once get_stylesheet_directory() . '/includes/settings-page.php';

require_once get_stylesheet_directory() . '/includes/security-page.php';
require_once get_stylesheet_directory() . '/includes/post-types-settings.php';
require_once get_stylesheet_directory() . '/includes/custom-field-settings.php';
require_once get_stylesheet_directory() . '/includes/taxonomy-settings.php';

require_once get_stylesheet_directory() . '/includes/documentation-settings-page.php';
require_once get_stylesheet_directory() . '/includes/block-editor-settings.php';
require_once get_stylesheet_directory() . '/includes/remove-wp-version.php';
require_once get_stylesheet_directory() . '/includes/disable-xmlrpc.php';
require_once get_stylesheet_directory() . '/includes/disable-file-editing.php';
require_once get_stylesheet_directory() . '/includes/remove-rss.php';
require_once get_stylesheet_directory() . '/includes/disable-wp-json-if-not-logged-in.php';
require_once get_stylesheet_directory() . '/includes/move-bricks-menu.php';
require_once get_stylesheet_directory() . '/includes/auto-update-bricks.php';

require_once get_stylesheet_directory() . '/includes/login-error-message.php';
require_once get_stylesheet_directory() . '/includes/login-logo-change-url-change.php';
require_once get_stylesheet_directory() . '/includes/wp-revision-limit.php';
require_once get_stylesheet_directory() . '/includes/enqueue-gsap.php';
require_once get_stylesheet_directory() . '/includes/enqueue-scripts.php';
require_once get_stylesheet_directory() . '/includes/color-sync-bricks-and-block-editor.php';
require_once get_stylesheet_directory() . '/includes/theme-json-styles.php';



// Feature 
require_once get_stylesheet_directory() . '/includes/file-size-column-media.php';
require_once get_stylesheet_directory() . '/includes/wp-admin-css-style-footer-inline.php';



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
