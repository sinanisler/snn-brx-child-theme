<?php     

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
require_once SNN_PATH . 'includes/editor-settings-panel.php';
require_once SNN_PATH . 'includes/cookie-banner.php';


/**
  * Register Custom Dynamic Data Tags
  */
require_once SNN_PATH . 'includes/dynamic-data-tags/estimated-post-read-time.php';
require_once SNN_PATH . 'includes/dynamic-data-tags/get-contextual-id.php';
require_once SNN_PATH . 'includes/dynamic-data-tags/parent-link.php';
require_once SNN_PATH . 'includes/dynamic-data-tags/post-term-count.php';
// require_once SNN_PATH . 'includes/dynamic-data-tags/taxonomy-term-slug.php';
require_once SNN_PATH . 'includes/dynamic-data-tags/user-author-fields.php';

// Utils
require_once SNN_PATH . 'includes/utils.php';


// Register Custom Bricks Builder Elements
add_action('init', function () {
    \Bricks\Elements::register_element(SNN_PATH . 'includes/elements/custom-html-css-script.php', 'custom-html-css-script', 'Custom_HTML_CSS_Script');

    \Bricks\Elements::register_element(SNN_PATH . 'includes/elements/custom-maps.php');

    \Bricks\Elements::register_element(SNN_PATH . 'includes/elements/custom-maps.php');

    \Bricks\Elements::register_element(SNN_PATH . 'includes/elements/advanced-image.php');


    

    // if GSAP setting is enabled Register Elements
    $options = get_option('snn_other_settings');
    if (!empty($options['enqueue_gsap'])) {
        \Bricks\Elements::register_element(SNN_PATH . 'includes/elements/lottie-animation.php');

        \Bricks\Elements::register_element(SNN_PATH . 'includes/elements/gsap-animations.php');

        \Bricks\Elements::register_element(SNN_PATH . 'includes/elements/gsap-text-animations.php');
    }


}, 11);
