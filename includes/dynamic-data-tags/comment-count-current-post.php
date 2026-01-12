<?php

/**
 * ----------------------------------------
 * Comment Count Current Post Tag Module
 * ----------------------------------------
 * Usage: {comment_count_current_post}
 *
 * Returns the comment count for the current post/page
 *
 * Example:
 * - {comment_count_current_post} → Total comment count for current post
 * ----------------------------------------
 */

// Step 1: Register the dynamic tag with Bricks Builder.
add_filter('bricks/dynamic_tags_list', 'snn_register_comment_count_post_tag');
function snn_register_comment_count_post_tag($tags) {
    $tags[] = [
        'name'  => '{comment_count_current_post}',
        'label' => 'Comment Count (Current Post)',
        'group' => 'SNN',
    ];

    return $tags;
}

// Step 2: Get comment count data for current post
function snn_get_comment_count_post_data() {
    // Get current post ID
    $post_id = get_the_ID();

    if (!$post_id) {
        return 0;
    }

    // Get comment count for the post
    $comment_count = wp_count_comments($post_id);

    // Return approved comments count
    return absint($comment_count->approved);
}

// Step 3: Render the dynamic tag in Bricks Builder.
add_filter('bricks/dynamic_data/render_tag', 'snn_render_comment_count_post_tag', 20, 3);
function snn_render_comment_count_post_tag($tag, $post, $context = 'text') {
    // Ensure that $tag is a string before processing.
    if (is_string($tag)) {
        if ($tag === '{comment_count_current_post}') {
            return snn_get_comment_count_post_data();
        }
    }

    // If $tag is an array, iterate through and process each element.
    if (is_array($tag)) {
        foreach ($tag as $key => $value) {
            if (is_string($value) && $value === '{comment_count_current_post}') {
                $tag[$key] = snn_get_comment_count_post_data();
            }
        }
        return $tag;
    }

    // Return the original tag if it doesn't match the expected pattern.
    return $tag;
}

// Step 4: Replace placeholders in dynamic content dynamically.
add_filter('bricks/dynamic_data/render_content', 'snn_render_comment_count_post_in_content', 20, 3);
add_filter('bricks/frontend/render_data', 'snn_render_comment_count_post_in_content', 20, 2);
function snn_render_comment_count_post_in_content($content, $post, $context = 'text') {
    if (!is_string($content)) {
        return $content;
    }

    // Match {comment_count_current_post} tag
    if (strpos($content, '{comment_count_current_post}') !== false) {
        $value = snn_get_comment_count_post_data();
        $content = str_replace('{comment_count_current_post}', $value, $content);
    }

    return $content;
}
