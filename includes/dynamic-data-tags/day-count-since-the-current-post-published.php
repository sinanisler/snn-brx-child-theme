<?php
/**
 * Custom Dynamic Data Tag: Days Since Published
 * Returns the number of days that have passed since the post was published
 */

// Step 1: Register the tag in the builder
add_filter( 'bricks/dynamic_tags_list', 'add_days_since_published_tag' );
function add_days_since_published_tag( $tags ) {
    $tags[] = [
        'name'  => '{snn_publish_day_count_since_publish}',
        'label' => 'Days Since Published',
        'group' => 'SNN',
    ];
    
    return $tags;
}

// Step 2: Render the tag value
add_filter( 'bricks/dynamic_data/render_tag', 'get_days_since_published_value', 20, 3 );
function get_days_since_published_value( $tag, $post, $context = 'text' ) {
    if ( ! is_string( $tag ) ) {
        return $tag;
    }
    
    // Clean the tag
    $clean_tag = str_replace( [ '{', '}' ], '', $tag );
    
    // Only process our specific tag
    if ( $clean_tag !== 'snn_publish_day_count_since_publish' ) {
        return $tag;
    }
    
    // Calculate days since published
    $value = calculate_days_since_published( $post );
    
    return $value;
}

// Step 3: Render in content
add_filter( 'bricks/dynamic_data/render_content', 'render_days_since_published_tag', 20, 3 );
add_filter( 'bricks/frontend/render_data', 'render_days_since_published_tag', 20, 2 );
function render_days_since_published_tag( $content, $post, $context = 'text' ) {
    
    // Only process if our tag exists in content
    if ( strpos( $content, '{snn_publish_day_count_since_publish}' ) === false ) {
        return $content;
    }
    
    // Calculate days since published
    $value = calculate_days_since_published( $post );
    
    // Replace the tag with the value
    $content = str_replace( '{snn_publish_day_count_since_publish}', $value, $content );
    
    return $content;
}

// Helper function to calculate days
function calculate_days_since_published( $post ) {
    // Get post ID
    $post_id = is_object( $post ) ? $post->ID : $post;
    
    if ( empty( $post_id ) ) {
        $post_id = get_the_ID();
    }
    
    // Get the publish date
    $publish_date = get_the_date( 'U', $post_id ); // Unix timestamp
    
    if ( empty( $publish_date ) ) {
        return 0;
    }
    
    // Get current date
    $current_date = current_time( 'U' );
    
    // Calculate difference in days
    $difference = $current_date - $publish_date;
    $days = floor( $difference / DAY_IN_SECONDS );
    
    return $days;
}