<?php
/**
 * ----------------------------------------
 * Dynamic Current User Fields Module
 * ----------------------------------------
 * Usage:
 *  1) {current_user_field:field_name}
 *     - e.g. {current_user_field:name}, {current_user_field:firstname}, {current_user_field:lastname}, {current_user_field:email}
 * 
 *  2) {current_user_field:custom_field_name}
 *     - Example for fetching custom user meta fields.
 *
 * Description:
 * Dynamically generates and renders user field tags, including common fields and custom fields.
 * ----------------------------------------
 */

// Step 1: Register the dynamic tags with Bricks Builder.
add_filter('bricks/dynamic_tags_list', 'add_dynamic_user_field_tags_to_builder');
function add_dynamic_user_field_tags_to_builder($tags) {
    // Define popular user fields.
    $popular_fields = [
        'name'      => 'Display Name',
        'firstname' => 'First Name',
        'lastname'  => 'Last Name',
        'email'     => 'Email',
        'custom'    => 'Custom Field Example', // Example custom field
    ];

    // Add each field as a tag.
    foreach ($popular_fields as $field => $label) {
        $tags[] = [
            'name'  => "{current_user_field:$field}",
            'label' => "Current User - $label",
            'group' => 'SNN', // Group name in Bricks Builder.
        ];
    }

    return $tags;
}

// Step 2: Define a helper function to retrieve user fields dynamically.
function get_dynamic_user_field($field) {
    $current_user = wp_get_current_user();

    if ($current_user->ID !== 0) { // Check if the user is logged in.
        switch (strtolower($field)) {
            case 'name':
                return esc_html($current_user->display_name);
            case 'firstname':
                return esc_html(get_user_meta($current_user->ID, 'first_name', true));
            case 'lastname':
                return esc_html(get_user_meta($current_user->ID, 'last_name', true));
            case 'email':
                return esc_html($current_user->user_email);
            case 'custom': // Example for a custom user field.
                return esc_html(get_user_meta($current_user->ID, 'custom_field_example', true)) ?: 'No Value';
            default:
                // Sanitize the field name to prevent potential security issues.
                $sanitized_field = sanitize_key($field);
                return esc_html(get_user_meta($current_user->ID, $sanitized_field, true)) ?: '';
        }
    }

    return '';
}

// Step 3: Render each dynamic tag when Bricks encounters it in a field.
add_filter('bricks/dynamic_data/render_tag', 'render_dynamic_user_field_tag', 20, 3);
function render_dynamic_user_field_tag($tag, $post, $context = 'text') {
    // Check if $tag is an array and has the 'name' key.
    if (is_array($tag) && isset($tag['name'])) {
        $tag_name = $tag['name'];

        // Ensure $tag_name is a string before using strpos().
        if (is_string($tag_name) && strpos($tag_name, '{current_user_field:') === 0 && substr($tag_name, -1) === '}') {
            // Extract the field name from the tag.
            $field = str_replace(['{current_user_field:', '}'], '', $tag_name);
            return get_dynamic_user_field($field);
        }
    }
    // If $tag is not an array or doesn't match the pattern, return it unchanged.
    return $tag;
}

// Step 4: Replace placeholders inside dynamic content (like in Rich Text, etc.).
add_filter('bricks/dynamic_data/render_content', 'replace_dynamic_user_field_in_content', 20, 3);
add_filter('bricks/frontend/render_data', 'replace_dynamic_user_field_in_content', 20, 2);
function replace_dynamic_user_field_in_content($content, $post, $context = 'text') {
    // Pattern: {current_user_field:field_name}
    if (preg_match_all('/\{current_user_field:([\w-]+)\}/', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            // $match[0] => The entire match, e.g., {current_user_field:name}
            // $match[1] => Field name, e.g., 'name'
            $field = sanitize_key($match[1]);
            $field_value = get_dynamic_user_field($field);
            $content = str_replace($match[0], $field_value, $content);
        }
    }
    return $content;
}
