<?php

/**
 * ----------------------------------------
 * Comment Count Current User Tag Module
 * ----------------------------------------
 * Usage: {comment_count_current_user} or {comment_count_current_user:month}
 *
 * Supported Options:
 * - (default): Returns total comment count for current user
 * - month: Returns comment count for current month only
 *
 * Examples:
 * - {comment_count_current_user} → Total comment count for current user
 * - {comment_count_current_user:month} → Comment count for current month only
 * ----------------------------------------
 */

// Step 1: Register the dynamic tags with Bricks Builder.
add_filter('bricks/dynamic_tags_list', 'register_comment_count_current_user_tag');
function register_comment_count_current_user_tag($tags) {
    $tags[] = [
        'name'  => '{comment_count_current_user}',
        'label' => 'Comment Count (Current User)',
        'group' => 'SNN',
    ];

    $tags[] = [
        'name'  => '{comment_count_current_user:month}',
        'label' => 'Comment Count (Current User) - Current Month',
        'group' => 'SNN',
    ];

    return $tags;
}

// Step 2: Get comment count data based on option
function get_comment_count_current_user_data($option = '') {
    // Get current user ID
    $current_user_id = get_current_user_id();

    if (!$current_user_id) {
        return 0;
    }

    // Base query arguments
    $args = [
        'user_id' => $current_user_id,
        'count'   => true,
        'status'  => 'approve', // Only approved comments
    ];

    // Add date query for current month if option is 'month'
    if ($option === 'month') {
        $args['date_query'] = [
            [
                'year'  => date('Y'),
                'month' => date('n'),
            ],
        ];
    }

    // Get comment count
    $comment_count = get_comments($args);

    return absint($comment_count);
}

// Step 3: Render the dynamic tag in Bricks Builder.
add_filter('bricks/dynamic_data/render_tag', 'render_comment_count_current_user_tag', 20, 3);
function render_comment_count_current_user_tag($tag, $post, $context = 'text') {
    // Ensure that $tag is a string before processing.
    if (is_string($tag)) {
        // Match {comment_count_current_user:month}
        if (preg_match('/{comment_count_current_user:([^}]+)}/', $tag, $matches)) {
            $option = trim($matches[1]);
            return get_comment_count_current_user_data($option);
        } elseif ($tag === '{comment_count_current_user}') {
            // {comment_count_current_user}
            return get_comment_count_current_user_data();
        }
    }

    // If $tag is an array, iterate through and process each element.
    if (is_array($tag)) {
        foreach ($tag as $key => $value) {
            if (is_string($value)) {
                if (preg_match('/{comment_count_current_user:([^}]+)}/', $value, $matches)) {
                    $option = trim($matches[1]);
                    $tag[$key] = get_comment_count_current_user_data($option);
                } elseif ($value === '{comment_count_current_user}') {
                    $tag[$key] = get_comment_count_current_user_data();
                }
            }
        }
        return $tag;
    }

    // Return the original tag if it doesn't match the expected pattern.
    return $tag;
}

// Step 4: Replace placeholders in dynamic content dynamically.
add_filter('bricks/dynamic_data/render_content', 'render_comment_count_current_user_tag_in_content', 20, 3);
add_filter('bricks/frontend/render_data', 'render_comment_count_current_user_tag_in_content', 20, 2);
function render_comment_count_current_user_tag_in_content($content, $post, $context = 'text') {
    if (!is_string($content)) {
        return $content;
    }

    // Match all {comment_count_current_user} and {comment_count_current_user:month} tags
    preg_match_all('/{comment_count_current_user(?::([^}]+))?}/', $content, $matches);

    if (!empty($matches[0])) {
        foreach ($matches[0] as $index => $full_match) {
            $option = isset($matches[1][$index]) && $matches[1][$index] ? $matches[1][$index] : '';
            $value = get_comment_count_current_user_data($option);
            $content = str_replace($full_match, $value, $content);
        }
    }

    return $content;
}
