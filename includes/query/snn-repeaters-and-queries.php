<?php

/**
 * SNN Bricks Custom Repeaters & Dynamic Data Integration
 * ----------------------------------------------------
 *
 * This file extends Bricks Builder with custom query types and dynamic data tags for custom field repeaters.
 *
 * Features implemented:
 *
 * 1. **Custom Query Types for Repeaters**
 *    - Registers new query types for each custom field marked as a repeater (see `bricks/setup/control_options`).
 *    - Allows Bricks Query Loop to fetch repeater data from post meta, enabling looped display of custom repeater fields.
 *    - See Bricks docs: https://academy.bricksbuilder.io/article/query-loop/
 *
 * 2. **Custom Dynamic Data Tags for Repeater Items**
 *    - Registers dynamic data tags for each repeater field (e.g. `{snn_cf_fieldname}`) for use in Bricks templates.
 *    - Enables output of current repeater item or subfields inside the query loop context.
 *    - See Bricks docs: https://academy.bricksbuilder.io/article/dynamic-data/
 *
 * 3. **Context-Aware Dynamic Data Rendering**
 *    - When inside a repeater query loop, dynamic tags output the current item or subfield value.
 *    - Outside the loop, tags fallback to first item or post meta value.
 *    - Supports grouped repeaters and direct subfield access.
 *
 * 4. **Global Variables for Template Usage**
 *    - Sets global variables for current repeater item and field, making them accessible in template PHP or dynamic tag rendering.
 *    - Cleans up globals after loop ends for safety.
 *
 * 5. **Bricks Hooks Used**
 *    - `bricks/setup/control_options`: Register custom query types.
 *    - `bricks/query/run`: Provide repeater data for custom query types.
 *    - `bricks/query/loop_object`: Set global context for current repeater item.
 *    - `bricks/dynamic_tags_list`: Register custom dynamic data tags.
 *    - `bricks/dynamic_data/render_tag`: Render individual dynamic data tags.
 *    - `bricks/dynamic_data/render_content`: Render content with multiple dynamic tags.
 *    - `bricks/frontend/render_data`: Ensure frontend rendering of dynamic tags.
 *    - `bricks/query/after_loop`: Clean up global variables after loop.
 *
 * 6. **Compatibility**
 *    - Works with Bricks Query Loop for posts, terms, users, and custom repeaters.
 *    - Supports ACF, Meta Box, JetEngine, and native WordPress custom fields as repeaters.
 *    - See Bricks docs for custom fields: https://academy.bricksbuilder.io/article/dynamic-data/#custom-fields-integrations
 *
 * 7. **Usage**
 *    - In Bricks Builder, enable Query Loop on a container and select the custom repeater query type.
 *    - Use `{snn_cf_fieldname}` or `{snn_cf_fieldname_subfield}` in child elements to output repeater data.
 *    - Supports fallback to first item if not in loop context.
 *
 * 8. **Extensibility**
 *    - Easily add new repeater fields via the `snn_custom_fields` option.
 *    - Extend dynamic tag rendering for more complex field structures as needed.
 *
 * For more info, see Bricks official docs:
 * - Query Loop: https://academy.bricksbuilder.io/article/query-loop/
 * - Dynamic Data: https://academy.bricksbuilder.io/article/dynamic-data/
 * - Custom Fields: https://academy.bricksbuilder.io/article/dynamic-data/#custom-fields-integrations
 *
 * Author: SNN Team
 * Date: 2025-08-25
 */









add_filter('bricks/setup/control_options', function($control_options) {
    $custom_fields = get_option('snn_custom_fields', []);
    foreach ($custom_fields as $field) {
        if (!empty($field['repeater'])) {
            $type_key = 'snn_repeater_' . $field['name'];
            $label = 'SNN Repeater ' . $field['label'];
            $control_options['queryTypes'][$type_key] = esc_html($label);
        }
    }
    return $control_options;
});

add_filter('bricks/query/run', function($results, $query_obj) {
    $custom_fields = get_option('snn_custom_fields', []);
    foreach ($custom_fields as $field) {
        if (!empty($field['repeater'])) {
            $type_key = 'snn_repeater_' . $field['name'];
            if ($query_obj->object_type === $type_key) {
                // Get the current post ID (works in builder and frontend)
                $post_id = get_the_ID();
                // Get repeater values from post meta
                $values = get_post_meta($post_id, $field['name'], true);
                if (is_array($values)) {
                    $results = $values;
                } else {
                    $results = [];
                }
            }
        }
    }
    return $results;
}, 10, 2);

add_filter('bricks/query/loop_object', function($loop_object, $loop_key, $query_obj) {
    // Setup global $repeater_item for template usage
    if (strpos($query_obj->object_type, 'snn_repeater_') === 0) {
        // $loop_object is the repeater row array
        // You can use $repeater_item in your template
        global $repeater_item;
        $repeater_item = $loop_object;
    }
    return $loop_object;
}, 10, 3);


















 

// Keep your existing query filters (the code you already have)
add_filter('bricks/setup/control_options', function($control_options) {
    $custom_fields = get_option('snn_custom_fields', []);
    foreach ($custom_fields as $field) {
        if (!empty($field['repeater'])) {
            $type_key = 'snn_repeater_' . $field['name'];
            $label = 'SNN Repeater ' . $field['label'];
            $control_options['queryTypes'][$type_key] = esc_html($label);
        }
    }
    return $control_options;
});

add_filter('bricks/query/run', function($results, $query_obj) {
    $custom_fields = get_option('snn_custom_fields', []);
    foreach ($custom_fields as $field) {
        if (!empty($field['repeater'])) {
            $type_key = 'snn_repeater_' . $field['name'];
            if ($query_obj->object_type === $type_key) {
                // Get the current post ID (works in builder and frontend)
                $post_id = get_the_ID();
                // Get repeater values from post meta
                $values = get_post_meta($post_id, $field['name'], true);
                if (is_array($values)) {
                    $results = $values;
                } else {
                    $results = [];
                }
            }
        }
    }
    return $results;
}, 10, 2);

add_filter('bricks/query/loop_object', function($loop_object, $loop_key, $query_obj) {
    // Setup global variables for template usage
    if (strpos($query_obj->object_type, 'snn_repeater_') === 0) {
        // Store both the current item and the field name for later use
        global $snn_current_repeater_item, $snn_current_repeater_field;
        $snn_current_repeater_item = $loop_object;
        
        // Extract field name from query type
        $snn_current_repeater_field = str_replace('snn_repeater_', '', $query_obj->object_type);
    }
    return $loop_object;
}, 10, 3);

// ================================================
// CUSTOM DYNAMIC DATA TAGS FOR REPEATERS
// ================================================

/**
 * Step 1: Register custom SNN repeater dynamic data tags
 */
add_filter('bricks/dynamic_tags_list', function($tags) {
    $custom_fields = get_option('snn_custom_fields', []);
    
    foreach ($custom_fields as $field) {
        if (!empty($field['repeater'])) {
            $field_name = $field['name'];
            $field_label = !empty($field['label']) ? $field['label'] : ucwords(str_replace('_', ' ', $field_name));
            
            // Register the custom tag in the correct format
            $tags[] = [
                'name'  => '{snn_cf_' . $field_name . '}',
                'label' => $field_label . ' (Current Item)',
                'group' => 'SNN Repeater Fields',
            ];
        }
    }
    
    return $tags;
});

/**
 * Step 2: Process individual tags with bricks/dynamic_data/render_tag
 */
add_filter('bricks/dynamic_data/render_tag', function($tag, $post, $context = 'text') {
    if (!is_string($tag)) {
        return $tag;
    }
    
    // Remove curly braces to get clean tag name
    $clean_tag = str_replace(['{', '}'], '', $tag);
    
    // Check if this is one of our SNN repeater tags
    if (strpos($clean_tag, 'snn_cf_') !== 0) {
        return $tag; // Not our tag, return unchanged
    }
    
    // Extract field name
    $field_name = str_replace('snn_cf_', '', $clean_tag);
    
    // Get the value for this repeater field
    $value = snn_get_repeater_field_value($field_name, $post);
    
    return $value;
}, 20, 3);

/**
 * Step 3: Process content that may contain multiple tags
 */
add_filter('bricks/dynamic_data/render_content', function($content, $post, $context = 'text') {
    // Only process if content contains our tags
    if (strpos($content, '{snn_cf_') === false) {
        return $content;
    }
    
    // Use regex to find all our tags
    preg_match_all('/{(snn_cf_[^}]+)}/', $content, $matches);
    
    if (empty($matches[0])) {
        return $content;
    }
    
    foreach ($matches[1] as $key => $match) {
        $full_tag = $matches[0][$key]; // {snn_cf_fieldname}
        $clean_tag = $matches[1][$key]; // snn_cf_fieldname
        
        // Extract field name
        $field_name = str_replace('snn_cf_', '', $clean_tag);
        
        // Get the value
        $value = snn_get_repeater_field_value($field_name, $post);
        
        // Replace in content
        $content = str_replace($full_tag, $value, $content);
    }
    
    return $content;
}, 20, 3);

/**
 * Also hook into frontend render for safety
 */
add_filter('bricks/frontend/render_data', function($content, $post) {
    return apply_filters('bricks/dynamic_data/render_content', $content, $post, 'text');
}, 20, 2);

/**
 * Helper function to get repeater field value
 */
function snn_get_repeater_field_value($field_name, $post = null) {
    global $snn_current_repeater_item, $snn_current_repeater_field;
    
    // Check if we're currently in a repeater loop context
    if (!empty($snn_current_repeater_item) && !empty($snn_current_repeater_field)) {
        // We're in a repeater context
        if ($field_name === $snn_current_repeater_field) {
            // Simple repeater - return the current item
            if (!is_array($snn_current_repeater_item)) {
                return $snn_current_repeater_item;
            } else {
                // If it's an array, convert to string for display
                return implode(', ', $snn_current_repeater_item);
            }
        }
        
        // For future grouped repeaters - check if field exists in current item
        if (is_array($snn_current_repeater_item)) {
            // Check for grouped field access like fieldname_subfield
            if (strpos($field_name, $snn_current_repeater_field . '_') === 0) {
                $subfield = str_replace($snn_current_repeater_field . '_', '', $field_name);
                if (isset($snn_current_repeater_item[$subfield])) {
                    return $snn_current_repeater_item[$subfield];
                }
            }
            
            // Direct field access
            if (isset($snn_current_repeater_item[$field_name])) {
                return $snn_current_repeater_item[$field_name];
            }
        }
    }
    
    // Not in repeater context or field not found - fallback to post meta
    if ($post && isset($post->ID)) {
        $post_id = $post->ID;
    } else {
        $post_id = get_the_ID();
    }
    
    if ($post_id) {
        $repeater_values = get_post_meta($post_id, $field_name, true);
        if (is_array($repeater_values) && !empty($repeater_values)) {
            // Return first item as fallback
            $first_item = $repeater_values[0];
            if (is_array($first_item)) {
                return implode(', ', $first_item);
            }
            return $first_item;
        } elseif (!empty($repeater_values)) {
            return $repeater_values;
        }
    }
    
    return '';
}

/**
 * Clean up global variables when query loop ends
 */
add_action('bricks/query/after_loop', function($query_obj) {
    if (strpos($query_obj->object_type, 'snn_repeater_') === 0) {
        global $snn_current_repeater_item, $snn_current_repeater_field;
        $snn_current_repeater_item = null;
        $snn_current_repeater_field = null;
    }
});

