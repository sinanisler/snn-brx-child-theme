<?php
/**
 * ----------------------------------------
 * SNN Options and Fields Dynamic Tag Module
 * ----------------------------------------
 * Usage:
 *   {get_options_fields:field_name}
 *
 * Retrieves values from SNN Custom Fields settings or globally saved options.
 * ----------------------------------------
 */

// Step 1: Register the dynamic tags with Bricks Builder.
add_filter('bricks/dynamic_tags_list', 'snn_add_options_and_fields_tags_to_builder');
function snn_add_options_and_fields_tags_to_builder($tags) {
    $custom_fields = get_option('snn_custom_fields', []);

    if (is_array($custom_fields)) {
        foreach ($custom_fields as $field) {
            if (!empty($field['name'])) {
                $field_name = $field['name'];
                $field_label = !empty($field['label']) ? $field['label'] : $field_name;
                
                $tags[] = [
                    'name'  => '{get_options_fields:' . $field_name . '}',
                    'label' => 'SNN Field: ' . $field_label,
                    'group' => 'SNN Options & Fields',
                ];
            }
        }
    }

    return $tags;
}

// Step 2: Get the value of the custom field or option.
function snn_get_options_fields_value($property = '') {
    if (empty($property)) {
        return '';
    }

    // Attempt 1: Check if it's an SNN options page value
    // In custom-field-settings.php, options are saved as 'snn_opt_' . $field_name
    $option_value = get_option('snn_opt_' . $property);
    if ($option_value !== false) {
        return is_array($option_value) ? implode(', ', $option_value) : $option_value;
    }

    // Attempt 2: Check current context (Post, Term, User)
    $current_id = get_the_ID();
    if (!$current_id) {
        $queried = get_queried_object();
        if ($queried instanceof WP_Post) {
            $current_id = $queried->ID;
        } elseif ($queried instanceof WP_Term) {
            // Check term meta
            $term_value = get_term_meta($queried->term_id, $property, true);
            if ($term_value !== '') {
                return is_array($term_value) ? implode(', ', $term_value) : $term_value;
            }
        } elseif ($queried instanceof WP_User) {
            // Check user meta
            $user_value = get_user_meta($queried->ID, $property, true);
            if ($user_value !== '') {
                return is_array($user_value) ? implode(', ', $user_value) : $user_value;
            }
        }
    }

    // If we have a post ID, check post meta
    if ($current_id) {
        $post_value = get_post_meta($current_id, $property, true);
        if ($post_value !== '') {
            return is_array($post_value) ? implode(', ', $post_value) : $post_value;
        }
    }

    // Attempt 3: Standard WordPress Option fallback (if user wants to fetch a native WP option directly)
    $pure_option = get_option($property);
    if ($pure_option !== false && !is_object($pure_option)) {
        return is_array($pure_option) ? implode(', ', $pure_option) : $pure_option;
    }

    return '';
}

// Step 3: Render the dynamic tag in Bricks Builder.
add_filter('bricks/dynamic_data/render_tag', 'snn_render_options_and_fields_tag', 20, 3);
function snn_render_options_and_fields_tag($tag, $post, $context = 'text') {
    if (is_string($tag)) {
        if (strpos($tag, '{get_options_fields:') === 0) {
            if (preg_match('/{get_options_fields:([^}]+)}/', $tag, $matches)) {
                return snn_get_options_fields_value(trim($matches[1]));
            }
        }
    }

    if (is_array($tag)) {
        foreach ($tag as $key => $value) {
            if (is_string($value) && strpos($value, '{get_options_fields:') === 0) {
                if (preg_match('/{get_options_fields:([^}]+)}/', $value, $matches)) {
                    $tag[$key] = snn_get_options_fields_value(trim($matches[1]));
                }
            }
        }
        return $tag;
    }

    return $tag;
}

// Step 4: Replace placeholders in dynamic content.
add_filter('bricks/dynamic_data/render_content', 'snn_replace_options_and_fields_in_content', 20, 3);
add_filter('bricks/frontend/render_data', 'snn_replace_options_and_fields_in_content', 20, 2);
function snn_replace_options_and_fields_in_content($content, $post = null, $context = 'text') {
    if (!is_string($content)) {
        return $content;
    }

    preg_match_all('/{get_options_fields:([^}]+)}/', $content, $matches);

    if (!empty($matches[0])) {
        foreach ($matches[0] as $index => $full_match) {
            $property = isset($matches[1][$index]) ? trim($matches[1][$index]) : '';
            if ($property) {
                $content  = str_replace($full_match, snn_get_options_fields_value($property), $content);
            }
        }
    }

    return $content;
}
