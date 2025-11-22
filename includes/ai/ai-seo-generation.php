<?php
/**
 * SNN AI SEO Generation
 *
 * File: ai-seo-generation.php
 *
 * Purpose: This file handles AI-powered SEO title and description generation for posts, pages,
 * custom post types, and taxonomies. It integrates with the existing AI infrastructure
 * (ai-api.php) and provides both individual and bulk generation capabilities.
 *
 * Features:
 * - AI generation buttons in SEO meta box (post edit screens)
 * - Bulk AI generation for post list screens
 * - Taxonomy term AI generation
 * - Uses existing AI overlay infrastructure with action prompts
 * - Supports all configured post types and taxonomies
 */

if (!defined('ABSPATH')) exit;

/**
 * Check if SEO AI features are enabled
 */
function snn_seo_ai_is_enabled() {
    $seo_enabled = get_option('snn_seo_enabled', false);
    $seo_ai_enabled = get_option('snn_seo_ai_enabled', false);
    $ai_enabled = get_option('snn_ai_enabled', 'no');
    
    return $seo_enabled && $seo_ai_enabled && $ai_enabled === 'yes';
}

/**
 * Enqueue AI SEO scripts and styles for admin
 */
function snn_seo_ai_enqueue_admin_scripts($hook) {
    if (!snn_seo_ai_is_enabled()) {
        return;
    }

    // Only load on post edit screens and post list screens
    $allowed_hooks = ['post.php', 'post-new.php', 'edit.php', 'term.php', 'edit-tags.php'];
    if (!in_array($hook, $allowed_hooks)) {
        return;
    }

    // Get AI config
    if (!function_exists('snn_get_ai_api_config')) {
        return;
    }
    
    $config = snn_get_ai_api_config();
    
    if (empty($config['apiKey']) || empty($config['apiEndpoint'])) {
        return;
    }

    // Pass config to JavaScript
    wp_localize_script('jquery', 'snnSeoAiConfig', array(
        'apiKey' => $config['apiKey'],
        'model' => $config['model'],
        'apiEndpoint' => $config['apiEndpoint'],
        'systemPrompt' => $config['systemPrompt'],
        'actionPresets' => $config['actionPresets'],
        'responseFormat' => $config['responseFormat'],
        'nonce' => wp_create_nonce('snn_seo_ai_nonce'),
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'strings' => array(
            'generating' => __('Generating...', 'snn'),
            'error' => __('Error generating content', 'snn'),
            'success' => __('Generated successfully', 'snn'),
            'confirmBulk' => __('This will generate SEO data for selected items using AI. Continue?', 'snn'),
        )
    ));
}
add_action('admin_enqueue_scripts', 'snn_seo_ai_enqueue_admin_scripts');

/**
 * Add AI generation buttons to SEO meta box
 */
function snn_seo_ai_meta_box_buttons($post) {
    if (!snn_seo_ai_is_enabled()) {
        return;
    }

    $post_type = get_post_type($post);
    $enabled_post_types = get_option('snn_seo_post_types_enabled', []);
    
    if (!isset($enabled_post_types[$post_type]) || !$enabled_post_types[$post_type]) {
        return;
    }

    // Get post content for context
    $post_content = $post->post_content;
    $post_title = $post->post_title;
    $post_excerpt = $post->post_excerpt;
    
    // Extract Bricks content if available
    if (function_exists('snn_seo_extract_bricks_content')) {
        $bricks_content = snn_seo_extract_bricks_content($post->ID);
        if (!empty($bricks_content)) {
            $post_content = $bricks_content;
        }
    }
    
    ?>
    <style>
        .snn-seo-ai-button {
            display: inline-block;
            margin-left: 10px;
            padding: 4px 12px;
            background: #2271b1;
            color: #fff;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            transition: background 0.2s;
        }
        .snn-seo-ai-button:hover {
            background: #135e96;
            color: #fff;
        }
        .snn-seo-ai-button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .snn-seo-ai-loading {
            opacity: 0.6;
        }
        .snn-seo-ai-row {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .snn-seo-ai-row label {
            flex: 0 0 auto;
            margin-right: 10px;
        }
        .snn-seo-ai-row input,
        .snn-seo-ai-row textarea {
            flex: 1;
        }
    </style>

    <script>
    jQuery(document).ready(function($) {
        if (typeof snnSeoAiConfig === 'undefined') {
            return;
        }

        const config = snnSeoAiConfig;
        const postContent = <?php echo json_encode(wp_strip_all_tags(substr($post_content, 0, 3000))); ?>;
        const postTitle = <?php echo json_encode($post_title); ?>;
        const postExcerpt = <?php echo json_encode($post_excerpt); ?>;

        // Generate SEO title
        $('#snn-seo-ai-generate-title').on('click', function(e) {
            e.preventDefault();
            const $btn = $(this);
            const $input = $('input[name="snn_seo_meta_title"]');
            
            if ($btn.prop('disabled')) return;
            
            $btn.prop('disabled', true).text(config.strings.generating);
            
            generateSeoContent('title', postContent, postTitle, postExcerpt)
                .then(result => {
                    $input.val(result);
                    $btn.text('✓ ' + config.strings.success);
                    setTimeout(() => {
                        $btn.prop('disabled', false).text('<?php _e('Generate with AI', 'snn'); ?>');
                    }, 2000);
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert(config.strings.error + ': ' + error.message);
                    $btn.prop('disabled', false).text('<?php _e('Generate with AI', 'snn'); ?>');
                });
        });

        // Generate SEO description
        $('#snn-seo-ai-generate-description').on('click', function(e) {
            e.preventDefault();
            const $btn = $(this);
            const $textarea = $('textarea[name="snn_seo_meta_description"]');
            
            if ($btn.prop('disabled')) return;
            
            $btn.prop('disabled', true).text(config.strings.generating);
            
            generateSeoContent('description', postContent, postTitle, postExcerpt)
                .then(result => {
                    $textarea.val(result);
                    $btn.text('✓ ' + config.strings.success);
                    setTimeout(() => {
                        $btn.prop('disabled', false).text('<?php _e('Generate with AI', 'snn'); ?>');
                    }, 2000);
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert(config.strings.error + ': ' + error.message);
                    $btn.prop('disabled', false).text('<?php _e('Generate with AI', 'snn'); ?>');
                });
        });

        // AI Generation function
        async function generateSeoContent(type, content, title, excerpt) {
            const systemPrompt = config.systemPrompt || 'You are an SEO expert helping to generate optimized meta tags.';
            
            let userPrompt = '';
            if (type === 'title') {
                userPrompt = `Based on the following content, generate an SEO-optimized meta title (max 60 characters). Only return the title text, nothing else.

Post Title: ${title}
Content: ${content.substring(0, 1000)}

Generate a compelling, keyword-rich meta title:`;
            } else if (type === 'description') {
                userPrompt = `Based on the following content, generate an SEO-optimized meta description (max 160 characters). Only return the description text, nothing else.

Post Title: ${title}
Excerpt: ${excerpt}
Content: ${content.substring(0, 1500)}

Generate a compelling meta description:`;
            }

            const requestBody = {
                model: config.model,
                messages: [
                    { role: 'system', content: systemPrompt },
                    { role: 'user', content: userPrompt }
                ],
                temperature: 0.7,
                max_tokens: 150
            };

            if (config.responseFormat && config.responseFormat.type) {
                requestBody.response_format = config.responseFormat;
            }

            const response = await fetch(config.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${config.apiKey}`
                },
                body: JSON.stringify(requestBody)
            });

            if (!response.ok) {
                throw new Error(`API request failed: ${response.status}`);
            }

            const data = await response.json();
            
            if (data.choices && data.choices[0] && data.choices[0].message) {
                return data.choices[0].message.content.trim();
            }
            
            throw new Error('Invalid API response');
        }
    });
    </script>
    <?php
}

/**
 * Add custom bulk actions for AI generation
 */
function snn_seo_ai_bulk_actions($bulk_actions) {
    if (!snn_seo_ai_is_enabled()) {
        return $bulk_actions;
    }
    
    $bulk_actions['snn_generate_seo_ai'] = __('Generate SEO with AI', 'snn');
    return $bulk_actions;
}

/**
 * Register bulk actions for enabled post types
 */
function snn_seo_ai_register_bulk_actions() {
    if (!snn_seo_ai_is_enabled()) {
        return;
    }

    $enabled_post_types = get_option('snn_seo_post_types_enabled', []);
    
    foreach ($enabled_post_types as $post_type => $enabled) {
        if ($enabled) {
            add_filter("bulk_actions-edit-{$post_type}", 'snn_seo_ai_bulk_actions');
            add_filter("handle_bulk_actions-edit-{$post_type}", 'snn_seo_ai_handle_bulk_action', 10, 3);
        }
    }
}
add_action('admin_init', 'snn_seo_ai_register_bulk_actions');

/**
 * Handle bulk action for AI SEO generation
 */
function snn_seo_ai_handle_bulk_action($redirect_to, $action, $post_ids) {
    if ($action !== 'snn_generate_seo_ai') {
        return $redirect_to;
    }

    if (!snn_seo_ai_is_enabled()) {
        return $redirect_to;
    }

    // Store post IDs in transient for AJAX processing
    $transient_key = 'snn_seo_bulk_' . wp_generate_password(12, false);
    set_transient($transient_key, $post_ids, 300); // 5 minutes

    // Redirect to custom page with processing indicator
    return add_query_arg(array(
        'snn_seo_bulk_process' => $transient_key,
        'post_count' => count($post_ids)
    ), $redirect_to);
}

/**
 * Display bulk processing notice and interface
 */
function snn_seo_ai_bulk_processing_notice() {
    if (!isset($_GET['snn_seo_bulk_process'])) {
        return;
    }

    $transient_key = sanitize_text_field($_GET['snn_seo_bulk_process']);
    $post_count = isset($_GET['post_count']) ? intval($_GET['post_count']) : 0;
    
    $post_ids = get_transient($transient_key);
    
    if (!$post_ids || !is_array($post_ids)) {
        echo '<div class="notice notice-error"><p>' . __('Bulk processing data expired or invalid.', 'snn') . '</p></div>';
        return;
    }

    ?>
    <div class="notice notice-info" id="snn-seo-bulk-notice">
        <p>
            <strong><?php _e('AI SEO Generation in Progress...', 'snn'); ?></strong><br>
            <span id="snn-seo-bulk-progress">0</span> / <?php echo $post_count; ?> <?php _e('items processed', 'snn'); ?>
        </p>
        <div style="background: #f0f0f0; height: 20px; border-radius: 10px; overflow: hidden;">
            <div id="snn-seo-bulk-progress-bar" style="background: #2271b1; height: 100%; width: 0%; transition: width 0.3s;"></div>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        const postIds = <?php echo json_encode(array_values($post_ids)); ?>;
        const config = window.snnSeoAiConfig || {};
        let processed = 0;

        async function processPost(postId) {
            try {
                const response = await $.ajax({
                    url: config.ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'snn_seo_ai_generate_bulk',
                        post_id: postId,
                        nonce: config.nonce
                    }
                });

                return response.success;
            } catch (error) {
                console.error('Error processing post ' + postId, error);
                return false;
            }
        }

        async function processAll() {
            for (const postId of postIds) {
                await processPost(postId);
                processed++;
                
                $('#snn-seo-bulk-progress').text(processed);
                $('#snn-seo-bulk-progress-bar').css('width', (processed / postIds.length * 100) + '%');
            }

            $('#snn-seo-bulk-notice').removeClass('notice-info').addClass('notice-success');
            $('#snn-seo-bulk-notice p').html('<strong><?php _e('AI SEO Generation Complete!', 'snn'); ?></strong><br>' + processed + ' <?php _e('items processed successfully', 'snn'); ?>');
            
            // Clean up transient
            $.post(config.ajaxUrl, {
                action: 'snn_seo_ai_cleanup_transient',
                transient_key: '<?php echo esc_js($transient_key); ?>',
                nonce: config.nonce
            });

            setTimeout(() => {
                window.location.href = window.location.href.split('?')[0] + '?post_type=' + new URLSearchParams(window.location.search).get('post_type');
            }, 2000);
        }

        processAll();
    });
    </script>
    <?php
}
add_action('admin_notices', 'snn_seo_ai_bulk_processing_notice');

/**
 * AJAX handler for bulk AI generation
 */
function snn_seo_ai_generate_bulk_handler() {
    check_ajax_referer('snn_seo_ai_nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Insufficient permissions');
    }

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    
    if (!$post_id) {
        wp_send_json_error('Invalid post ID');
    }

    // Generate and save SEO data
    $result = snn_seo_ai_generate_for_post($post_id);
    
    if ($result) {
        wp_send_json_success();
    } else {
        wp_send_json_error('Generation failed');
    }
}
add_action('wp_ajax_snn_seo_ai_generate_bulk', 'snn_seo_ai_generate_bulk_handler');

/**
 * AJAX handler for transient cleanup
 */
function snn_seo_ai_cleanup_transient_handler() {
    check_ajax_referer('snn_seo_ai_nonce', 'nonce');
    
    $transient_key = isset($_POST['transient_key']) ? sanitize_text_field($_POST['transient_key']) : '';
    
    if ($transient_key) {
        delete_transient($transient_key);
    }
    
    wp_send_json_success();
}
add_action('wp_ajax_snn_seo_ai_cleanup_transient', 'snn_seo_ai_cleanup_transient_handler');

/**
 * Generate SEO data for a single post using AI
 */
function snn_seo_ai_generate_for_post($post_id) {
    if (!function_exists('snn_get_ai_api_config')) {
        return false;
    }

    $post = get_post($post_id);
    if (!$post) {
        return false;
    }

    // Get post content
    $content = $post->post_content;
    
    // Extract Bricks content if available
    if (function_exists('snn_seo_extract_bricks_content')) {
        $bricks_content = snn_seo_extract_bricks_content($post_id);
        if (!empty($bricks_content)) {
            $content = $bricks_content;
        }
    }

    $content = wp_strip_all_tags(substr($content, 0, 2000));
    
    $config = snn_get_ai_api_config();
    
    if (empty($config['apiKey']) || empty($config['apiEndpoint'])) {
        return false;
    }

    // Generate title
    $title = snn_seo_ai_call_api(
        $config,
        "Generate an SEO-optimized meta title (max 60 characters) for this content. Only return the title text:\n\nTitle: {$post->post_title}\nContent: {$content}",
        100
    );

    // Generate description
    $description = snn_seo_ai_call_api(
        $config,
        "Generate an SEO-optimized meta description (max 160 characters) for this content. Only return the description text:\n\nTitle: {$post->post_title}\nContent: {$content}",
        150
    );

    if ($title) {
        update_post_meta($post_id, '_snn_seo_title', sanitize_text_field($title));
    }

    if ($description) {
        update_post_meta($post_id, '_snn_seo_description', sanitize_textarea_field($description));
    }

    return true;
}

/**
 * Make AI API call
 */
function snn_seo_ai_call_api($config, $prompt, $max_tokens = 150) {
    $request_body = array(
        'model' => $config['model'],
        'messages' => array(
            array('role' => 'system', 'content' => $config['systemPrompt']),
            array('role' => 'user', 'content' => $prompt)
        ),
        'temperature' => 0.7,
        'max_tokens' => $max_tokens
    );

    if (!empty($config['responseFormat']['type'])) {
        $request_body['response_format'] = $config['responseFormat'];
    }

    $response = wp_remote_post($config['apiEndpoint'], array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $config['apiKey']
        ),
        'body' => json_encode($request_body),
        'timeout' => 30
    ));

    if (is_wp_error($response)) {
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    if (isset($body['choices'][0]['message']['content'])) {
        return trim($body['choices'][0]['message']['content']);
    }

    return false;
}

/**
 * Add AI buttons to taxonomy term edit screen
 */
function snn_seo_ai_taxonomy_fields($term) {
    if (!snn_seo_ai_is_enabled()) {
        return;
    }

    $taxonomy = $term->taxonomy;
    $enabled_taxonomies = get_option('snn_seo_taxonomies_enabled', []);
    
    if (!isset($enabled_taxonomies[$taxonomy]) || !$enabled_taxonomies[$taxonomy]) {
        return;
    }

    $term_title = get_term_meta($term->term_id, '_snn_seo_title', true);
    $term_description = get_term_meta($term->term_id, '_snn_seo_description', true);

    ?>
    <tr class="form-field">
        <th scope="row">
            <label><?php _e('SEO Title', 'snn'); ?></label>
        </th>
        <td>
            <input type="text" name="snn_seo_term_title" value="<?php echo esc_attr($term_title); ?>" class="regular-text" />
            <button type="button" class="button snn-seo-ai-button" id="snn-seo-ai-generate-term-title">
                <?php _e('Generate with AI', 'snn'); ?>
            </button>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row">
            <label><?php _e('SEO Description', 'snn'); ?></label>
        </th>
        <td>
            <textarea name="snn_seo_term_description" rows="3" class="large-text"><?php echo esc_textarea($term_description); ?></textarea>
            <button type="button" class="button snn-seo-ai-button" id="snn-seo-ai-generate-term-description">
                <?php _e('Generate with AI', 'snn'); ?>
            </button>
        </td>
    </tr>

    <script>
    jQuery(document).ready(function($) {
        if (typeof snnSeoAiConfig === 'undefined') return;

        const config = snnSeoAiConfig;
        const termName = <?php echo json_encode($term->name); ?>;
        const termDescription = <?php echo json_encode($term->description); ?>;

        $('#snn-seo-ai-generate-term-title').on('click', function(e) {
            e.preventDefault();
            const $btn = $(this);
            const $input = $('input[name="snn_seo_term_title"]');
            
            $btn.prop('disabled', true).text(config.strings.generating);
            
            generateTermSeo('title', termName, termDescription)
                .then(result => {
                    $input.val(result);
                    $btn.text('✓').prop('disabled', false);
                    setTimeout(() => $btn.text('<?php _e('Generate with AI', 'snn'); ?>'), 2000);
                })
                .catch(error => {
                    alert(config.strings.error);
                    $btn.text('<?php _e('Generate with AI', 'snn'); ?>').prop('disabled', false);
                });
        });

        $('#snn-seo-ai-generate-term-description').on('click', function(e) {
            e.preventDefault();
            const $btn = $(this);
            const $textarea = $('textarea[name="snn_seo_term_description"]');
            
            $btn.prop('disabled', true).text(config.strings.generating);
            
            generateTermSeo('description', termName, termDescription)
                .then(result => {
                    $textarea.val(result);
                    $btn.text('✓').prop('disabled', false);
                    setTimeout(() => $btn.text('<?php _e('Generate with AI', 'snn'); ?>'), 2000);
                })
                .catch(error => {
                    alert(config.strings.error);
                    $btn.text('<?php _e('Generate with AI', 'snn'); ?>').prop('disabled', false);
                });
        });

        async function generateTermSeo(type, name, description) {
            const prompt = type === 'title' 
                ? `Generate an SEO-optimized meta title (max 60 characters) for this taxonomy term:\n\nTerm: ${name}\nDescription: ${description}`
                : `Generate an SEO-optimized meta description (max 160 characters) for this taxonomy term:\n\nTerm: ${name}\nDescription: ${description}`;

            const response = await fetch(config.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${config.apiKey}`
                },
                body: JSON.stringify({
                    model: config.model,
                    messages: [
                        { role: 'system', content: config.systemPrompt },
                        { role: 'user', content: prompt }
                    ],
                    temperature: 0.7,
                    max_tokens: type === 'title' ? 100 : 150
                })
            });

            if (!response.ok) throw new Error('API failed');
            
            const data = await response.json();
            return data.choices[0].message.content.trim();
        }
    });
    </script>
    <?php
}

/**
 * Save taxonomy term SEO fields
 */
function snn_seo_ai_save_taxonomy_fields($term_id) {
    if (isset($_POST['snn_seo_term_title'])) {
        update_term_meta($term_id, '_snn_seo_title', sanitize_text_field($_POST['snn_seo_term_title']));
    }
    
    if (isset($_POST['snn_seo_term_description'])) {
        update_term_meta($term_id, '_snn_seo_description', sanitize_textarea_field($_POST['snn_seo_term_description']));
    }
}

/**
 * Register taxonomy field hooks for enabled taxonomies
 */
function snn_seo_ai_register_taxonomy_hooks() {
    if (!snn_seo_ai_is_enabled()) {
        return;
    }

    $enabled_taxonomies = get_option('snn_seo_taxonomies_enabled', []);
    
    foreach ($enabled_taxonomies as $taxonomy => $enabled) {
        if ($enabled) {
            add_action("{$taxonomy}_edit_form_fields", 'snn_seo_ai_taxonomy_fields', 10, 1);
            add_action("edited_{$taxonomy}", 'snn_seo_ai_save_taxonomy_fields', 10, 1);
        }
    }
}
add_action('admin_init', 'snn_seo_ai_register_taxonomy_hooks');

/**
 * Hook AI buttons into SEO meta box
 */
function snn_seo_ai_init() {
    if (!snn_seo_ai_is_enabled()) {
        return;
    }
    
    // Add buttons after meta box is rendered
    add_action('snn_seo_meta_box_after_fields', 'snn_seo_ai_meta_box_buttons');
}
add_action('init', 'snn_seo_ai_init');
