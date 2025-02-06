<?php

/**
 * ----------------------------------------
 * Estimated Post Read Time Tag
 * ----------------------------------------
 * Usage: {estimated_post_read_time}
 * Description: Displays the estimated read time for a post based on word count.
 */


add_filter( 'bricks/dynamic_tags_list', 'add_estimated_post_read_time_tag_to_builder' );
function add_estimated_post_read_time_tag_to_builder( $tags ) {
    $tags[] = [
        'name'  => '{estimated_post_read_time}',
        'label' => 'Estimated Post Read Time',
        'group' => 'SNN',
    ];
    return $tags;
}

function calculate_estimated_read_time() {
    global $post;
    if ( ! $post ) {
        return 0;
    }
    $word_count = str_word_count( strip_tags( $post->post_content ) );
    $read_time  = ceil( $word_count / 200 ); // Average reading speed: 200 words per minute
    return intval( $read_time ); // Ensure it's an integer
}

add_filter( 'bricks/dynamic_data/render_tag', 'render_estimated_post_read_time_tag', 10, 3 );
function render_estimated_post_read_time_tag( $tag, $post, $context = 'text' ) {
    if ( $tag === '{estimated_post_read_time}' ) {
        return calculate_estimated_read_time();
    }
    return $tag;
}

add_filter( 'bricks/dynamic_data/render_content', 'render_estimated_post_read_time_in_content', 10, 3 );
add_filter( 'bricks/frontend/render_data', 'render_estimated_post_read_time_in_content', 10, 2 );
function render_estimated_post_read_time_in_content( $content, $post, $context = 'text' ) {
    if ( strpos( $content, '{estimated_post_read_time}' ) !== false ) {
        $read_time = calculate_estimated_read_time();
        $content   = str_replace( '{estimated_post_read_time}', esc_html( $read_time ), $content );
    }
    return $content;
}
