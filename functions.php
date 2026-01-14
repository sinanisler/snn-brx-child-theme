<?php                                                                   
// DO NOT TOUCH THIS FILE 


define( 'SNN_PATH', trailingslashit( get_stylesheet_directory() ) );    
define( 'SNN_PATH_ASSETS', trailingslashit( SNN_PATH . 'assets' ) );    
define( 'SNN_URL', trailingslashit( get_stylesheet_directory_uri() ) ); 
define( 'SNN_URL_ASSETS', trailingslashit( SNN_URL . 'assets' ) );  


// Main Features and Settings
require_once SNN_PATH . 'includes/settings-page.php';

require_once SNN_PATH . 'includes/other-settings.php';
require_once SNN_PATH . 'includes/security-page.php';
require_once SNN_PATH . 'includes/post-types-settings.php';
require_once SNN_PATH . 'includes/custom-field-settings.php';
require_once SNN_PATH . 'includes/taxonomy-settings.php';
require_once SNN_PATH . 'includes/login-settings.php';
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
require_once SNN_PATH . 'includes/editor-settings-bricks.php'; 
require_once SNN_PATH . 'includes/editor-settings-panel-bricks.php';
require_once SNN_PATH . 'includes/editor-class-generator.php';
require_once SNN_PATH . 'includes/role-manager.php';
require_once SNN_PATH . 'includes/custom-code-snippets.php';
require_once SNN_PATH . 'includes/cookie-banner.php';
require_once SNN_PATH . 'includes/accessibility-settings.php';
require_once SNN_PATH . 'includes/activity-logs.php';
require_once SNN_PATH . 'includes/seo.php';
require_once SNN_PATH . 'includes/seo-bricks-setting.php';


require_once SNN_PATH . 'includes/ai/ai-settings.php';
require_once SNN_PATH . 'includes/ai/ai-api.php';
require_once SNN_PATH . 'includes/ai/ai-overlay.php';
require_once SNN_PATH . 'includes/ai/ai-seo-generation.php';
require_once SNN_PATH . 'includes/ai/ai-block-editor.php';

// AI Agent and Chat and AI Abilities (WordPress Abilities API)
require_once SNN_PATH . 'includes/ai/ai-agent-and-chat.php';
require_once SNN_PATH . 'includes/ai/abilities/create-posts.php';
require_once SNN_PATH . 'includes/ai/abilities/update-posts.php';
require_once SNN_PATH . 'includes/ai/abilities/get-posts.php';
require_once SNN_PATH . 'includes/ai/abilities/get-post-by-id.php';
require_once SNN_PATH . 'includes/ai/abilities/get-post-meta.php';
require_once SNN_PATH . 'includes/ai/abilities/search-content.php';
require_once SNN_PATH . 'includes/ai/abilities/get-terms.php';
require_once SNN_PATH . 'includes/ai/abilities/get-tags.php';
require_once SNN_PATH . 'includes/ai/abilities/create-terms.php';
require_once SNN_PATH . 'includes/ai/abilities/get-media.php';
require_once SNN_PATH . 'includes/ai/abilities/get-users.php';
require_once SNN_PATH . 'includes/ai/abilities/get-comments.php';
require_once SNN_PATH . 'includes/ai/abilities/get-site-info.php';
require_once SNN_PATH . 'includes/ai/abilities/suggest-content-ideas.php';
require_once SNN_PATH . 'includes/ai/abilities/create-post-summaries.php';
require_once SNN_PATH . 'includes/ai/abilities/check-seo-quality-title-and-content.php';
require_once SNN_PATH . 'includes/ai/abilities/installed-plugin-list-and-infos.php';
require_once SNN_PATH . 'includes/ai/abilities/active-theme-info.php';


require_once SNN_PATH . 'includes/block-editor-settings.php';
require_once SNN_PATH . 'includes/wp-admin-image-opt.php';
require_once SNN_PATH . 'includes/wp-admin-dashboard-widgets.php';

// Register Custom Dynamic Data Tags
require_once SNN_PATH . 'includes/dynamic-data-tags/estimated-post-read-time.php';
require_once SNN_PATH . 'includes/dynamic-data-tags/get-contextual-id.php';
require_once SNN_PATH . 'includes/dynamic-data-tags/contextual-slug.php';
require_once SNN_PATH . 'includes/dynamic-data-tags/get-contextual-content.php';
require_once SNN_PATH . 'includes/dynamic-data-tags/parent-link.php';
require_once SNN_PATH . 'includes/dynamic-data-tags/parent-id.php';
require_once SNN_PATH . 'includes/dynamic-data-tags/parent-detection.php';
require_once SNN_PATH . 'includes/dynamic-data-tags/child-link.php';
require_once SNN_PATH . 'includes/dynamic-data-tags/first-child-post.php';
require_once SNN_PATH . 'includes/dynamic-data-tags/post-term-count.php';
require_once SNN_PATH . 'includes/dynamic-data-tags/user-author-fields.php';
require_once SNN_PATH . 'includes/dynamic-data-tags/custom-field-repeater-first-item.php';
require_once SNN_PATH . 'includes/dynamic-data-tags/attachment-metadata.php';
require_once SNN_PATH . 'includes/dynamic-data-tags/raw-all-custom-fields.php';
require_once SNN_PATH . 'includes/dynamic-data-tags/day-count-since-the-current-post-published.php';
require_once SNN_PATH . 'includes/dynamic-data-tags/current-loop-item-count.php';
require_once SNN_PATH . 'includes/dynamic-data-tags/total-video-duration.php';
require_once SNN_PATH . 'includes/dynamic-data-tags/single-video-duration.php';
require_once SNN_PATH . 'includes/dynamic-data-tags/average-comment-rating.php';
require_once SNN_PATH . 'includes/dynamic-data-tags/comment-count-current-user.php';
require_once SNN_PATH . 'includes/dynamic-data-tags/child-post-count.php';
require_once SNN_PATH . 'includes/dynamic-data-tags/comment-count-current-post.php';

// Utils
require_once SNN_PATH . 'includes/utils.php';
require_once SNN_PATH . 'includes/auto-update-snn-brx-github.php';
require_once SNN_PATH . 'includes/query/snn-repeaters-and-queries.php';
require_once SNN_PATH . 'includes/query/snn-double-repeaters-and-queries.php';

// Register Custom Bricks Builder Elements
add_action('init', function () {
\Bricks\Elements::register_element(SNN_PATH . 'includes/elements/custom-html-css-script.php');
\Bricks\Elements::register_element(SNN_PATH . 'includes/elements/custom-maps.php');
\Bricks\Elements::register_element(SNN_PATH . 'includes/elements/advanced-image.php');
\Bricks\Elements::register_element(SNN_PATH . 'includes/elements/smoke-text.php');
\Bricks\Elements::register_element(SNN_PATH . 'includes/elements/read-more-toggle-text.php');
\Bricks\Elements::register_element(SNN_PATH . 'includes/elements/animated-vfx-text.php');
\Bricks\Elements::register_element(SNN_PATH . 'includes/elements/polkadot-effect.php');
\Bricks\Elements::register_element(SNN_PATH . 'includes/elements/animated-heading.php');
\Bricks\Elements::register_element(SNN_PATH . 'includes/elements/svg-text-path.php');
\Bricks\Elements::register_element(SNN_PATH . 'includes/elements/timeline.php');
\Bricks\Elements::register_element(SNN_PATH . 'includes/elements/like-button.php');
\Bricks\Elements::register_element(SNN_PATH . 'includes/elements/flip-box.php');
\Bricks\Elements::register_element(SNN_PATH . 'includes/elements/compare-image.php');
\Bricks\Elements::register_element(SNN_PATH . 'includes/elements/conditions.php');
\Bricks\Elements::register_element(SNN_PATH . 'includes/elements/comment-form.php');
\Bricks\Elements::register_element(SNN_PATH . 'includes/elements/comment-list.php');
\Bricks\Elements::register_element(SNN_PATH . 'includes/elements/frontend-post-form.php');
\Bricks\Elements::register_element(SNN_PATH . 'includes/elements/text-action-social-share.php');
\Bricks\Elements::register_element(SNN_PATH . 'includes/elements/scroll-line-vertical-indicator.php');
\Bricks\Elements::register_element(SNN_PATH . 'includes/elements/element-event-action-selector.php');
\Bricks\Elements::register_element(SNN_PATH . 'includes/elements/matrix.php');
\Bricks\Elements::register_element(SNN_PATH . 'includes/elements/multi-step-form.php');
\Bricks\Elements::register_element(SNN_PATH . 'includes/elements/query.php');
\Bricks\Elements::register_element(SNN_PATH . 'includes/elements/print.php');
\Bricks\Elements::register_element(SNN_PATH . 'includes/elements/image-hotspot.php');
\Bricks\Elements::register_element(SNN_PATH . 'includes/elements/video-player.php');
\Bricks\Elements::register_element(SNN_PATH . 'includes/elements/audio-player.php');
\Bricks\Elements::register_element(SNN_PATH . 'includes/elements/marquee-slider-carousel.php');
\Bricks\Elements::register_element(SNN_PATH . 'includes/elements/dynamic-pdf.php');
\Bricks\Elements::register_element(SNN_PATH . 'includes/elements/gallery-and-thumbnails.php');
\Bricks\Elements::register_element(SNN_PATH . 'includes/elements/cursor.php');
\Bricks\Elements::register_element(SNN_PATH . 'includes/elements/breadcrumbs.php');

\Bricks\Elements::register_element(SNN_PATH . 'includes/elements/lottie-animation.php');


// if GSAP setting is enabled Register Elements
$options = get_option('snn_other_settings');
if (!empty($options['enqueue_gsap'])) {
    \Bricks\Elements::register_element(SNN_PATH . 'includes/elements/gsap-animations.php');
    \Bricks\Elements::register_element(SNN_PATH . 'includes/elements/gsap-animations-code.php');
    \Bricks\Elements::register_element(SNN_PATH . 'includes/elements/gsap-text-animations.php');
    \Bricks\Elements::register_element(SNN_PATH . 'includes/elements/svg-animation.php');
    
}



}, 11);


$options = get_option('snn_other_settings');
if (!empty($options['enqueue_gsap'])) {

    require_once SNN_PATH . 'includes/elements/gsap-multi-element-register.php';

}



// Load Translations
load_theme_textdomain('snn', SNN_PATH . '/languages');
