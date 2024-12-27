<?php
 

// Frontend Head Inline JS and CSS, This will load in the <head>
// ASCII ART https://patorjk.com/software/taag/#p=display&f=ANSI%20Regular&t=sinanisler.com
function snn_custom_inline_frontend_head_code() {    ?>
<style type="text/css">

</style>
<script>

</script>
<?php }
add_action( 'wp_head', 'snn_custom_inline_frontend_head_code', 1 );



// Frontend Footer Inline JS and CSS,  This will load just before the </body>
function snn_custom_footer_inline() { ?>
<style>

</style>
<script>

</script>
<?php }
add_action('wp_footer', 'snn_custom_footer_inline', 9999);






require_once get_stylesheet_directory() . '/includes/settings-page.php';
require_once get_stylesheet_directory() . '/includes/other-settings.php';
require_once get_stylesheet_directory() . '/includes/security-page.php';
require_once get_stylesheet_directory() . '/includes/post-types-settings.php';
require_once get_stylesheet_directory() . '/includes/custom-field-settings.php';
require_once get_stylesheet_directory() . '/includes/taxonomy-settings.php';
require_once get_stylesheet_directory() . '/includes/smtp-settings.php';
require_once get_stylesheet_directory() . '/includes/login-settings.php';
require_once get_stylesheet_directory() . '/includes/block-editor-settings.php';
require_once get_stylesheet_directory() . '/includes/remove-wp-version.php';
require_once get_stylesheet_directory() . '/includes/disable-xmlrpc.php';
require_once get_stylesheet_directory() . '/includes/disable-file-editing.php';
require_once get_stylesheet_directory() . '/includes/remove-rss.php';
require_once get_stylesheet_directory() . '/includes/disable-wp-json-if-not-logged-in.php';
require_once get_stylesheet_directory() . '/includes/login-logo-change-url-change.php';
require_once get_stylesheet_directory() . '/includes/theme-json-styles.php';
require_once get_stylesheet_directory() . '/includes/enqueue-scripts.php';
require_once get_stylesheet_directory() . '/includes/file-size-column-media.php';
require_once get_stylesheet_directory() . '/includes/404-logging.php';
require_once get_stylesheet_directory() . '/includes/301-redirect.php';




require_once get_stylesheet_directory() . '/dynamic_data_tags/custom_dynamic_data_tags.php';



add_action( 'init', function() {
    $elements = [
        [
            'file' => get_stylesheet_directory() . '/custom_elements/custom-html-css-script.php',
            'slug' => 'custom-html-css-script',
            'class' => 'Custom_HTML_CSS_Script',
        ],


        [
            'file' => get_stylesheet_directory() . '/custom_elements/custom-maps.php',
            'slug' => null,
            'class' => null,
        ],

        /*
        [
            'file' => get_stylesheet_directory() . '/custom_elements/custom-button.php',
            'slug' => null,
            'class' => null,
        ],
        */


    ];

    foreach ( $elements as $element ) {
        if ( file_exists( $element['file'] ) ) {
            require_once $element['file'];
            if ( $element['slug'] && $element['class'] ) {
                \Bricks\Elements::register_element( $element['file'], $element['slug'], $element['class'] );
            } else {
                \Bricks\Elements::register_element( $element['file'] );
            }
        }
    }
}, 11 );
