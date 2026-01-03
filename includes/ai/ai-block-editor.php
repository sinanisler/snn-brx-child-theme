<?php
/**
 * SNN AI Block Editor Integration
 *
 * File: ai-block-editor.php
 *
 * Purpose: This file adds AI assistant functionality to the WordPress Block Editor.
 * It allows users to generate or regenerate the complete post content using AI,
 * with access to the same action presets configured in the AI settings.
 *
 * Features:
 * - Sidebar button to launch AI overlay
 * - Generate or regenerate complete post content
 * - Uses existing AI provider configuration and action presets
 * - Simple overlay interface for content manipulation
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Enqueue block editor AI script and styles
 */
function snn_enqueue_block_editor_ai_assets() {
    // Only load in block editor
    $screen = get_current_screen();
    if ( ! $screen || ! $screen->is_block_editor() ) {
        return;
    }

    // Check if AI is enabled
    $ai_enabled = get_option('snn_ai_enabled', 'no');
    if ($ai_enabled !== 'yes') {
        return;
    }

    // Get AI configuration
    if ( ! function_exists( 'snn_get_ai_api_config' ) ) {
        return;
    }

    $ai_config = snn_get_ai_api_config();

    // Only proceed if we have valid configuration
    if ( empty( $ai_config['apiKey'] ) || empty( $ai_config['model'] ) || empty( $ai_config['apiEndpoint'] ) ) {
        return;
    }

    // Enqueue the script
    wp_enqueue_script(
        'snn-block-editor-ai',
        plugins_url('js/block-editor-ai.js', __FILE__),
        array('wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data'),
        '1.0.0',
        true
    );

    // Pass AI configuration to JavaScript
    wp_localize_script('snn-block-editor-ai', 'snnAIConfig', $ai_config);

    // Enqueue styles
    wp_enqueue_style(
        'snn-block-editor-ai-styles',
        plugins_url('css/block-editor-ai.css', __FILE__),
        array(),
        '1.0.0'
    );
}
add_action('admin_enqueue_scripts', 'snn_enqueue_block_editor_ai_assets');

/**
 * AJAX handler to get post content
 */
function snn_get_post_content_ajax() {
    check_ajax_referer('snn_ai_nonce', 'nonce');

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    if (!$post_id) {
        wp_send_json_error(array('message' => 'Invalid post ID'));
        return;
    }

    // Check permissions
    if (!current_user_can('edit_post', $post_id)) {
        wp_send_json_error(array('message' => 'Permission denied'));
        return;
    }

    $post = get_post($post_id);
    if (!$post) {
        wp_send_json_error(array('message' => 'Post not found'));
        return;
    }

    // Get raw content (blocks as HTML)
    $content = $post->post_content;

    // Strip all HTML tags to get clean text
    $clean_content = wp_strip_all_tags($content);
    
    // Also get the title
    $title = $post->post_title;

    wp_send_json_success(array(
        'content' => $clean_content,
        'title' => $title,
        'raw_content' => $content
    ));
}
add_action('wp_ajax_snn_get_post_content', 'snn_get_post_content_ajax');

/**
 * AJAX handler to update post content
 */
function snn_update_post_content_ajax() {
    check_ajax_referer('snn_ai_nonce', 'nonce');

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $new_content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';

    if (!$post_id) {
        wp_send_json_error(array('message' => 'Invalid post ID'));
        return;
    }

    // Check permissions
    if (!current_user_can('edit_post', $post_id)) {
        wp_send_json_error(array('message' => 'Permission denied'));
        return;
    }

    // Convert plain text to paragraphs
    $formatted_content = wpautop($new_content);

    // Update the post
    $result = wp_update_post(array(
        'ID' => $post_id,
        'post_content' => $formatted_content
    ), true);

    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
        return;
    }

    wp_send_json_success(array(
        'message' => 'Content updated successfully',
        'content' => $formatted_content
    ));
}
add_action('wp_ajax_snn_update_post_content', 'snn_update_post_content_ajax');

/**
 * Add inline script to create nonce
 */
function snn_add_ai_nonce() {
    $screen = get_current_screen();
    if (!$screen || !$screen->is_block_editor()) {
        return;
    }

    $ai_enabled = get_option('snn_ai_enabled', 'no');
    if ($ai_enabled !== 'yes') {
        return;
    }

    ?>
    <script type="text/javascript">
        var snnAINonce = '<?php echo wp_create_nonce('snn_ai_nonce'); ?>';
        var snnAjaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
    </script>
    <?php
}
add_action('admin_head', 'snn_add_ai_nonce');