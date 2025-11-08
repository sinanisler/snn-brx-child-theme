<?php

/**
 * SNN Bricks Double Text Repeaters & Dynamic Data Integration
 * ----------------------------------------------------
 *
 * This file extends Bricks Builder with custom query types and dynamic data tags for DOUBLE TEXT custom field repeaters.
 *
 * Features implemented:
 *
 * 1. **Custom Query Types for Double Text Repeaters**
 *    - Registers new query types for each double_text custom field marked as a repeater.
 *    - Allows Bricks Query Loop to fetch double text repeater data from post meta.
 *
 * 2. **Custom Dynamic Data Tags for Double Text Repeater Items**
 *    - Registers two dynamic data tags per field: `{snn_cf_fieldname_1}` and `{snn_cf_fieldname_2}`.
 *    - Enables output of the first or second text value from the double text field.
 *    - Works inside query loop context for repeaters.
 *
 * 3. **Context-Aware Dynamic Data Rendering**
 *    - When inside a double text repeater query loop, tags output the current item's text 1 or text 2.
 *    - Outside the loop, tags fallback to first item values.
 *    - Automatically parses JSON array format ["text1", "text2"].
 *
 * 4. **Global Variables for Template Usage**
 *    - Sets global variables for current double text repeater item and field name.
 *    - Cleans up globals after loop ends for safety.
 *
 * 5. **Bricks Hooks Used**
 *    - `bricks/setup/control_options`: Register custom query types.
 *    - `bricks/query/run`: Provide double text repeater data for custom query types.
 *    - `bricks/query/loop_object`: Set global context for current double text repeater item.
 *    - `bricks/dynamic_tags_list`: Register custom dynamic data tags (_1 and _2 variants).
 *    - `bricks/dynamic_data/render_tag`: Render individual dynamic data tags.
 *    - `bricks/dynamic_data/render_content`: Render content with multiple dynamic tags.
 *    - `bricks/frontend/render_data`: Ensure frontend rendering of dynamic tags.
 *    - `bricks/query/after_loop`: Clean up global variables after loop.
 *
 * 6. **Usage**
 *    - In Bricks Builder, enable Query Loop on a container and select the double text repeater query type.
 *    - Use `{snn_cf_fieldname_1}` to output the first text value.
 *    - Use `{snn_cf_fieldname_2}` to output the second text value.
 *    - Works in repeater loops and as fallback to first item outside loops.
 *
 * Author: SNN Team
 * Date: 2025-11-08
 */


// ================================================
// REGISTER DOUBLE TEXT REPEATER QUERY TYPES
// ================================================

add_filter('bricks/setup/control_options', function($control_options) {
    $custom_fields = get_option('snn_custom_fields', []);
    foreach ($custom_fields as $field) {
        // Only register double_text fields that are repeaters
        if (!empty($field['repeater']) && isset($field['type']) && $field['type'] === 'double_text') {
            $type_key = 'snn_repeater_' . $field['name'];
            $label = 'SNN Double Text Repeater ' . $field['label'];
            $control_options['queryTypes'][$type_key] = esc_html($label);
        }
    }
    return $control_options;
});


// ================================================
// PROVIDE DOUBLE TEXT REPEATER DATA FOR QUERY LOOP
// ================================================

add_filter('bricks/query/run', function($results, $query_obj) {
    $custom_fields = get_option('snn_custom_fields', []);
    foreach ($custom_fields as $field) {
        // Only handle double_text repeater fields
        if (!empty($field['repeater']) && isset($field['type']) && $field['type'] === 'double_text') {
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


// ================================================
// SET GLOBAL CONTEXT FOR CURRENT REPEATER ITEM
// ================================================

add_filter('bricks/query/loop_object', function($loop_object, $loop_key, $query_obj) {
    // Setup global variables for template usage
    if (strpos($query_obj->object_type, 'snn_repeater_') === 0) {
        // Check if this is a double_text repeater
        $field_name = str_replace('snn_repeater_', '', $query_obj->object_type);
        $custom_fields = get_option('snn_custom_fields', []);
        
        foreach ($custom_fields as $field) {
            if ($field['name'] === $field_name && isset($field['type']) && $field['type'] === 'double_text') {
                // Store both the current item and the field name for later use
                global $snn_current_double_text_item, $snn_current_double_text_field;
                $snn_current_double_text_item = $loop_object;
                $snn_current_double_text_field = $field_name;
                break;
            }
        }
    }
    return $loop_object;
}, 10, 3);


// ================================================
// REGISTER CUSTOM DYNAMIC DATA TAGS FOR DOUBLE TEXT
// ================================================

/**
 * Step 1: Register custom SNN double text repeater dynamic data tags
 * Creates two tags per field: fieldname_1 and fieldname_2
 */
add_filter('bricks/dynamic_tags_list', function($tags) {
    $custom_fields = get_option('snn_custom_fields', []);
    
    foreach ($custom_fields as $field) {
        // Only register double_text repeater fields
        if (!empty($field['repeater']) && isset($field['type']) && $field['type'] === 'double_text') {
            $field_name = $field['name'];
            $field_label = !empty($field['label']) ? $field['label'] : ucwords(str_replace('_', ' ', $field_name));
            
            // Register tag for first text value
            $tags[] = [
                'name'  => '{snn_cf_' . $field_name . '_1}',
                'label' => $field_label . ' - Text 1',
                'group' => 'SNN Double Text Repeater Fields',
            ];
            
            // Register tag for second text value
            $tags[] = [
                'name'  => '{snn_cf_' . $field_name . '_2}',
                'label' => $field_label . ' - Text 2',
                'group' => 'SNN Double Text Repeater Fields',
            ];
        }
    }
    
    return $tags;
});


// ================================================
// RENDER INDIVIDUAL DOUBLE TEXT TAGS
// ================================================

/**
 * Step 2: Process individual tags with bricks/dynamic_data/render_tag
 */
add_filter('bricks/dynamic_data/render_tag', function($tag, $post, $context = 'text') {
    if (!is_string($tag)) {
        return $tag;
    }
    
    // Remove curly braces to get clean tag name
    $clean_tag = str_replace(['{', '}'], '', $tag);
    
    // Check if this is one of our SNN double text tags (ends with _1 or _2)
    if (strpos($clean_tag, 'snn_cf_') !== 0 || !preg_match('/_(1|2)$/', $clean_tag)) {
        return $tag; // Not our tag, return unchanged
    }
    
    // Extract field name and index (1 or 2)
    preg_match('/^snn_cf_(.+)_(1|2)$/', $clean_tag, $matches);
    if (!$matches) {
        return $tag;
    }
    
    $field_name = $matches[1];
    $index = (int)$matches[2]; // 1 or 2
    
    // Check if this is a registered double_text field
    if (!snn_is_double_text_repeater_field($field_name)) {
        return $tag;
    }
    
    // Get the value for this double text field
    $value = snn_get_double_text_field_value($field_name, $index, $post);
    
    return $value;
}, 20, 3);


// ================================================
// RENDER CONTENT WITH MULTIPLE TAGS
// ================================================

/**
 * Step 3: Process content that may contain multiple tags
 */
add_filter('bricks/dynamic_data/render_content', function($content, $post, $context = 'text') {
    // Only process if content contains our tags
    if (strpos($content, '{snn_cf_') === false) {
        return $content;
    }
    
    // Use regex to find all our double text tags (ending with _1 or _2)
    preg_match_all('/{(snn_cf_[^}]+_(1|2))}/', $content, $matches);
    
    if (empty($matches[0])) {
        return $content;
    }
    
    foreach ($matches[0] as $key => $full_tag) {
        $clean_tag = $matches[1][$key]; // snn_cf_fieldname_1 or snn_cf_fieldname_2
        
        // Extract field name and index
        preg_match('/^snn_cf_(.+)_(1|2)$/', $clean_tag, $field_matches);
        if (!$field_matches) {
            continue;
        }
        
        $field_name = $field_matches[1];
        $index = (int)$field_matches[2];
        
        // Check if this is a registered double_text field
        if (!snn_is_double_text_repeater_field($field_name)) {
            continue;
        }
        
        // Get the value
        $value = snn_get_double_text_field_value($field_name, $index, $post);
        
        // Replace in content
        $content = str_replace($full_tag, $value, $content);
    }
    
    return $content;
}, 20, 3);


// ================================================
// FRONTEND RENDER FILTER
// ================================================

/**
 * Also hook into frontend render for safety
 */
add_filter('bricks/frontend/render_data', function($content, $post) {
    return apply_filters('bricks/dynamic_data/render_content', $content, $post, 'text');
}, 20, 2);


// ================================================
// HELPER FUNCTIONS
// ================================================

/**
 * Check if a field is a double_text repeater field
 */
function snn_is_double_text_repeater_field($field_name) {
    $custom_fields = get_option('snn_custom_fields', []);
    foreach ($custom_fields as $field) {
        if ($field['name'] === $field_name && 
            !empty($field['repeater']) && 
            isset($field['type']) && 
            $field['type'] === 'double_text') {
            return true;
        }
    }
    return false;
}

/**
 * Helper function to get double text field value
 * 
 * @param string $field_name The field name
 * @param int $index 1 or 2 (which text value to return)
 * @param object $post Post object
 * @return string The text value
 */
function snn_get_double_text_field_value($field_name, $index, $post = null) {
    global $snn_current_double_text_item, $snn_current_double_text_field;
    
    // Array index (0 or 1)
    $array_index = $index - 1;
    
    // Check if we're currently in a double text repeater loop context
    if (!empty($snn_current_double_text_item) && !empty($snn_current_double_text_field)) {
        // We're in a double text repeater context
        if ($field_name === $snn_current_double_text_field) {
            // Parse the current item as JSON
            $parsed = snn_parse_double_text_value($snn_current_double_text_item);
            if ($parsed && isset($parsed[$array_index])) {
                return $parsed[$array_index];
            }
        }
    }
    
    // Not in repeater context - fallback to post meta (first item)
    if ($post && isset($post->ID)) {
        $post_id = $post->ID;
    } else {
        $post_id = get_the_ID();
    }
    
    if ($post_id) {
        $repeater_values = get_post_meta($post_id, $field_name, true);
        if (is_array($repeater_values) && !empty($repeater_values)) {
            // Get first item
            $first_item = $repeater_values[0];
            $parsed = snn_parse_double_text_value($first_item);
            if ($parsed && isset($parsed[$array_index])) {
                return $parsed[$array_index];
            }
        }
    }
    
    return '';
}

/**
 * Parse a double text value (JSON string or array) and return array
 * 
 * @param mixed $value JSON string like '["text1","text2"]' or already decoded array
 * @return array|false Array with two elements or false if invalid
 */
function snn_parse_double_text_value($value) {
    if (empty($value)) {
        return false;
    }
    
    // If already an array, return it
    if (is_array($value)) {
        return (count($value) >= 2) ? $value : false;
    }
    
    // If string, try to decode JSON
    if (is_string($value)) {
        $decoded = json_decode($value, true);
        if (is_array($decoded) && count($decoded) >= 2) {
            return $decoded;
        }
    }
    
    return false;
}


// ================================================
// CLEANUP AFTER LOOP
// ================================================

/**
 * Clean up global variables when query loop ends
 */
add_action('bricks/query/after_loop', function($query_obj) {
    if (strpos($query_obj->object_type, 'snn_repeater_') === 0) {
        // Check if this was a double text repeater
        $field_name = str_replace('snn_repeater_', '', $query_obj->object_type);
        if (snn_is_double_text_repeater_field($field_name)) {
            global $snn_current_double_text_item, $snn_current_double_text_field;
            $snn_current_double_text_item = null;
            $snn_current_double_text_field = null;
        }
    }
});
