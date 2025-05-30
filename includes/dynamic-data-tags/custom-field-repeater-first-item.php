<?php
/**
 * -----------------------------------------------------------------------
 * Dynamic Tag: Custom Field Repeater First Item
 * -----------------------------------------------------------------------
 * Group: SNN
 * 
 * Usage:
 *   1) Use the tag format in Bricks Builder:
 *      {custom_field_repeater_first_item:your_field_name}
 *      - Replace 'your_field_name' with your custom field/meta key (e.g. ACF repeater, Meta Box group, etc).
 *      - This will output the **first item** of any repeater-type custom/meta field (array-based).
 * 
 * Features:
 *   - Works with any field that stores an array of values (such as ACF Repeater, Meta Box Group, etc).
 *   - Auto-detects if the first item is a string or array, and outputs the first value or encodes the array.
 *   - No hardcoding: The field name is passed dynamically by the tag.
 *   - Enables dynamic tag use in all Bricks dynamic content areas.
 * 
 * Alternatives/Options:
 *   - For more advanced subfields or custom structures, adapt the helper to extract the needed value.
 *   - Can be expanded for nested or flexible content fields (see notes in the helper function).
 * 
 * -----------------------------------------------------------------------
 */

// 1. Register the dynamic tag in Bricks Builder.
add_filter( 'bricks/dynamic_tags_list', function( $tags ) {
    $tags[] = [
        'name'  => 'custom_field_repeater_first_item',
        'label' => 'Custom Field Repeater First Item',
        'group' => 'SNN', // Use "SNN" group as per your example.
    ];
    return $tags;
});

// 2. Helper function: Get the first item from a repeater/meta field.
function get_custom_field_repeater_first_item( $post_id, $field_name ) {
    $repeater = get_post_meta( $post_id, $field_name, true );
    if ( !empty( $repeater ) && is_array( $repeater ) ) {
        $first = $repeater[0];
        // For array with one key (common in Meta Box, ACF), return its value.
        if ( is_array( $first ) && count( $first ) === 1 ) {
            return esc_html( reset($first) );
        }
        // If using ACF-style ['sub_field' => 'value']
        elseif ( is_array( $first ) && isset( $first['text'] ) ) {
            return esc_html( $first['text'] );
        }
        // For other arrays (return as JSON string) or simple strings
        else {
            return esc_html( is_array( $first ) ? json_encode($first) : $first );
        }
    }
    return '';
}

// 3. Render the dynamic tag {custom_field_repeater_first_item:your_field_name}
add_filter( 'bricks/dynamic_data/render_tag', function( $tag_value, $post, $context ) {
    if ( preg_match( '/^custom_field_repeater_first_item:([a-zA-Z0-9_\-]+)/', $tag_value, $matches ) ) {
        $field_name = $matches[1];
        $post_id = isset( $post->ID ) ? $post->ID : $post;
        return get_custom_field_repeater_first_item( $post_id, $field_name );
    }
    return $tag_value;
}, 10, 3 );

// 4. Replace placeholders like {custom_field_repeater_first_item:your_field_name} in content.
add_filter( 'bricks/dynamic_data/render_content', function( $content, $post, $context ) {
    if ( preg_match_all( '/\{custom_field_repeater_first_item:([a-zA-Z0-9_\-]+)\}/', $content, $matches ) ) {
        foreach ( $matches[1] as $i => $field_name ) {
            $post_id = isset( $post->ID ) ? $post->ID : $post;
            $first_item = get_custom_field_repeater_first_item( $post_id, $field_name );
            $content = str_replace( $matches[0][$i], $first_item, $content );
        }
    }
    return $content;
}, 10, 3 );
