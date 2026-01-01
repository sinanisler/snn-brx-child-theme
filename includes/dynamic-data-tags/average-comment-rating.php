<?php
/**
 * ----------------------------------------
 * Average Comment Rating Dynamic Tag Module
 * ----------------------------------------
 * Usage: {average_comment_rating}
 *
 * Supported Properties and Outputs:
 * - (default): Returns the average rating from all comments on current post
 *   Output format: Numeric value (e.g., "4.5", "3", "2.5")
 *   Returns "0" if no ratings exist
 *
 * Logic:
 * - Gets all approved comments on the current post
 * - Reads the 'snn_rating_comment' custom field from each comment
 * - Calculates average and rounds to nearest 0.5
 * - Returns formatted number
 * ----------------------------------------
 */

// Step 1: Register the dynamic tag with Bricks Builder.
add_filter('bricks/dynamic_tags_list', 'add_average_comment_rating_tag_to_builder');
function add_average_comment_rating_tag_to_builder($tags) {
    $tags[] = [
        'name'  => '{average_comment_rating}',
        'label' => 'Average Comment Rating',
        'group' => 'SNN',
    ];

    return $tags;
}

// Step 2: Calculate average rating from comments
function get_average_comment_rating() {
    // Get current post ID
    $post_id = get_the_ID();
    if (!$post_id) {
        return '0';
    }
    
    // Get all approved comments for this post
    $comments = get_comments(array(
        'post_id' => $post_id,
        'status' => 'approve',
        'type' => 'comment'
    ));
    
    if (empty($comments)) {
        return '0';
    }
    
    $total_rating = 0;
    $rating_count = 0;
    
    // Loop through comments and collect ratings
    foreach ($comments as $comment) {
        $rating = get_comment_meta($comment->comment_ID, 'snn_rating_comment', true);
        
        // Only count if rating exists and is a valid number
        if (!empty($rating) && is_numeric($rating)) {
            $total_rating += floatval($rating);
            $rating_count++;
        }
    }
    
    // Calculate average
    if ($rating_count == 0) {
        return '0';
    }
    
    $average = $total_rating / $rating_count;
    
    // Round to nearest 0.5
    $average = round($average * 2) / 2;
    
    // Format the number (remove .0 for whole numbers)
    if (floor($average) == $average) {
        return number_format($average, 0);
    } else {
        return number_format($average, 1);
    }
}

// Step 3: Render the dynamic tag in Bricks Builder.
add_filter('bricks/dynamic_data/render_tag', 'render_average_comment_rating_tag', 20, 3);
function render_average_comment_rating_tag($tag, $post, $context = 'text') {
    // Ensure that $tag is a string before processing.
    if (is_string($tag)) {
        // Match {average_comment_rating}
        if ($tag === '{average_comment_rating}') {
            return get_average_comment_rating();
        }
    }

    // If $tag is an array, iterate through and process each element.
    if (is_array($tag)) {
        foreach ($tag as $key => $value) {
            if (is_string($value) && $value === '{average_comment_rating}') {
                $tag[$key] = get_average_comment_rating();
            }
        }
        return $tag;
    }

    // Return the original tag if it doesn't match the expected pattern.
    return $tag;
}

// Step 4: Replace placeholders in dynamic content dynamically.
add_filter('bricks/dynamic_data/render_content', 'replace_average_comment_rating_in_content', 20, 3);
add_filter('bricks/frontend/render_data', 'replace_average_comment_rating_in_content', 20, 2);
function replace_average_comment_rating_in_content($content, $post, $context = 'text') {
    if (!is_string($content)) {
        return $content;
    }

    // Match all {average_comment_rating} tags
    if (strpos($content, '{average_comment_rating}') !== false) {
        $value = get_average_comment_rating();
        $content = str_replace('{average_comment_rating}', $value, $content);
    }

    return $content;
}
