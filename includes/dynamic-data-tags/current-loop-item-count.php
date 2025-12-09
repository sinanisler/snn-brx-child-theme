<?php
/**
 * Custom Dynamic Data Tag: Current Loop Item Count
 * Returns the current iteration number in a WordPress query loop
 */

// Global counter variable
$snn_loop_counter = 0;

// Step 1: Register the tag in the builder
add_filter( 'bricks/dynamic_tags_list', 'add_loop_count_tag' );
function add_loop_count_tag( $tags ) {
    $tags[] = [
        'name'  => '{snn_current_loop_count}',
        'label' => 'Current Loop Count',
        'group' => 'snn',
    ];

    return $tags;
}

// Step 2: Render the tag value
add_filter( 'bricks/dynamic_data/render_tag', 'get_loop_count_value', 20, 3 );
function get_loop_count_value( $tag, $post, $context = 'text' ) {
    if ( ! is_string( $tag ) ) {
        return $tag;
    }

    // Clean the tag
    $clean_tag = str_replace( [ '{', '}' ], '', $tag );

    // Only process our specific tag
    if ( $clean_tag !== 'snn_current_loop_count' ) {
        return $tag;
    }

    // Get the current loop count
    $value = get_current_loop_count();

    return $value;
}

// Step 3: Render in content
add_filter( 'bricks/dynamic_data/render_content', 'render_loop_count_tag', 20, 3 );
add_filter( 'bricks/frontend/render_data', 'render_loop_count_tag', 20, 2 );
function render_loop_count_tag( $content, $post, $context = 'text' ) {

    // Only process if our tag exists in content
    if ( strpos( $content, '{snn_current_loop_count}' ) === false ) {
        return $content;
    }

    // Get the current loop count
    $value = get_current_loop_count();

    // Replace the tag with the value
    $content = str_replace( '{snn_current_loop_count}', $value, $content );

    return $content;
}

// Helper function to get and increment loop count
function get_current_loop_count() {
    global $snn_loop_counter;

    // Increment counter
    $snn_loop_counter++;

    return $snn_loop_counter;
}

// Reset counter before each query loop starts
add_action( 'pre_get_posts', 'reset_loop_counter' );
function reset_loop_counter() {
    global $snn_loop_counter;
    $snn_loop_counter = 0;
}
