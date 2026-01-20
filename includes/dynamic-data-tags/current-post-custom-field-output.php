<?php
/**
 * ----------------------------------------
 * Dynamic Current Post Custom Field Output Module
 * ----------------------------------------
 * Usage:
 *  1) {snn_custom_field_current_post_output:field_name}
 *     - e.g. {snn_custom_field_current_post_output:product_price}
 *     - e.g. {snn_custom_field_current_post_output:custom_description}
 *
 *  2) {snn_custom_field_current_post_output:any_custom_field_name}
 *     - Example for fetching any custom post meta fields.
 *
 * Description:
 * Dynamically generates and renders post custom field tags for the current post.
 * ----------------------------------------
 */

// Step 1: Register the dynamic tags with Bricks Builder.
add_filter('bricks/dynamic_tags_list', 'add_dynamic_post_custom_field_tags_to_builder');
function add_dynamic_post_custom_field_tags_to_builder($tags) {
    // Define popular/example custom fields.
    $popular_fields = [
        'product_price'       => 'Product Price',
        'custom_description'  => 'Custom Description',
        'featured_text'       => 'Featured Text',
        'additional_info'     => 'Additional Info',
        'custom'              => 'Custom Field Example', // Generic custom field
    ];

    // Add each field as a tag.
    foreach ($popular_fields as $field => $label) {
        $tags[] = [
            'name'  => "{snn_custom_field_current_post_output:$field}",
            'label' => "Current Post Custom Field - $label",
            'group' => 'SNN', // Group name in Bricks Builder.
        ];
    }

    return $tags;
}

// Step 2: Define a helper function to retrieve post custom fields dynamically.
function get_dynamic_post_custom_field($field, $post_id = null) {
    // Get the current post ID if not provided.
    if (!$post_id) {
        // Try multiple methods to get the post ID for maximum compatibility
        // 1. Try get_the_ID() - works in the loop
        $post_id = get_the_ID();

        // 2. If still no ID, try get_queried_object_id() - works on singular pages, archives, etc.
        if (!$post_id) {
            $post_id = get_queried_object_id();
        }

        // 3. If still no ID, try global $post
        if (!$post_id) {
            global $post;
            $post_id = isset($post->ID) ? $post->ID : 0;
        }
    }

    // Return empty if no valid post ID.
    if (!$post_id) {
        return '';
    }

    // Sanitize the field name to prevent potential security issues.
    $sanitized_field = sanitize_key($field);

    // Get the custom field value.
    $field_value = get_post_meta($post_id, $sanitized_field, true);

    // Handle different data types
    if (is_array($field_value)) {
        // For arrays, you might want to serialize or join them
        $field_value = implode(', ', array_map('esc_html', $field_value));
    } else {
        $field_value = esc_html($field_value);
    }

    return $field_value ?: '';
}

// Step 3: Render each dynamic tag when Bricks encounters it in a field.
add_filter('bricks/dynamic_data/render_tag', 'render_dynamic_post_custom_field_tag', 20, 3);
function render_dynamic_post_custom_field_tag($tag, $post, $context = 'text') {
    // Check if $tag is an array and has the 'name' key.
    if (is_array($tag) && isset($tag['name'])) {
        $tag_name = $tag['name'];

        // Ensure $tag_name is a string before using strpos().
        if (is_string($tag_name) && strpos($tag_name, '{snn_custom_field_current_post_output:') === 0 && substr($tag_name, -1) === '}') {
            // Extract the field name from the tag.
            $field = str_replace(['{snn_custom_field_current_post_output:', '}'], '', $tag_name);

            // Get post ID from context if available, otherwise let the helper function determine it
            $post_id = is_object($post) && isset($post->ID) ? $post->ID : null;

            return get_dynamic_post_custom_field($field, $post_id);
        }
    }
    // If $tag is not an array or doesn't match the pattern, return it unchanged.
    return $tag;
}

// Step 4: Replace placeholders inside dynamic content (like in Rich Text, etc.).
add_filter('bricks/dynamic_data/render_content', 'replace_dynamic_post_custom_field_in_content', 20, 3);
add_filter('bricks/frontend/render_data', 'replace_dynamic_post_custom_field_in_content', 20, 2);
function replace_dynamic_post_custom_field_in_content($content, $post, $context = 'text') {
    // Pattern: {snn_custom_field_current_post_output:field_name}
    if (preg_match_all('/\{snn_custom_field_current_post_output:([\w-]+)\}/', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            // $match[0] => The entire match, e.g., {snn_custom_field_current_post_output:product_price}
            // $match[1] => Field name, e.g., 'product_price'
            $field = sanitize_key($match[1]);

            // Get post ID from context if available, otherwise let the helper function determine it
            $post_id = is_object($post) && isset($post->ID) ? $post->ID : null;

            $field_value = get_dynamic_post_custom_field($field, $post_id);
            $content = str_replace($match[0], $field_value, $content);
        }
    }
    return $content;
}
