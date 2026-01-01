<?php
/**
 * ----------------------------------------
 * Total Video Duration Dynamic Tag Module
 * ----------------------------------------
 * Usage: {total_video_duration:field_name}
 *
 * Supported Properties and Outputs:
 * - field_name: The custom field name that stores the video attachment ID
 *   Returns combined duration from parent and all child posts (e.g., "3 hours 45 minutes")
 *
 * Logic:
 * - Gets video from the current post's custom field
 * - Gets videos from all child posts with the same custom field
 * - Calculates total duration and formats output
 * ----------------------------------------
 */

// Step 1: Register the dynamic tag with Bricks Builder.
add_filter('bricks/dynamic_tags_list', 'add_total_video_duration_tag_to_builder');
function add_total_video_duration_tag_to_builder($tags) {
    $tags[] = [
        'name'  => '{total_video_duration}',
        'label' => 'Total Video Duration (Parent + Children)',
        'group' => 'SNN',
    ];

    return $tags;
}

// Step 2: Get video duration from attachment ID (reuse function with unique prefix)
function snn_ddt_total_get_video_duration($attachment_id) {
    if (empty($attachment_id)) {
        return 0;
    }
    
    // Get the attachment metadata
    $metadata = wp_get_attachment_metadata($attachment_id);
    
    if (!empty($metadata['length'])) {
        return (int) $metadata['length'];
    }
    
    if (!empty($metadata['length_formatted'])) {
        // Try to parse formatted time like "1:23:45"
        $parts = explode(':', $metadata['length_formatted']);
        $seconds = 0;
        if (count($parts) == 3) {
            $seconds = ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
        } elseif (count($parts) == 2) {
            $seconds = ($parts[0] * 60) + $parts[1];
        }
        return $seconds;
    }
    
    return 0;
}

// Step 3: Format seconds to hours and minutes
function snn_ddt_total_format_duration($total_seconds) {
    $hours = floor($total_seconds / 3600);
    $minutes = floor(($total_seconds % 3600) / 60);
    
    if ($hours > 0) {
        return sprintf('%d hour%s %d minute%s', $hours, $hours > 1 ? 's' : '', $minutes, $minutes != 1 ? 's' : '');
    } else {
        return sprintf('%d minute%s', $minutes, $minutes != 1 ? 's' : '');
    }
}

// Step 4: Get the total video duration from parent and children
function get_total_video_duration($field_name = '') {
    if (empty($field_name)) {
        return '0 minutes';
    }

    // Get current post ID
    $post_id = get_the_ID();
    if (!$post_id) {
        return '0 minutes';
    }
    
    $total_duration = 0;
    $attachment_ids = array();
    
    // Get attachment ID from parent post
    $parent_attachment = get_post_meta($post_id, $field_name, true);
    if (!empty($parent_attachment)) {
        $attachment_ids[] = $parent_attachment;
    }
    
    // Get all child posts
    $child_posts = get_children(array(
        'post_parent' => $post_id,
        'post_type'   => get_post_type($post_id),
        'post_status' => 'publish',
        'numberposts' => -1
    ));
    
    // Get attachment IDs from all child posts
    foreach ($child_posts as $child) {
        $child_attachment = get_post_meta($child->ID, $field_name, true);
        if (!empty($child_attachment)) {
            $attachment_ids[] = $child_attachment;
        }
    }
    
    // Calculate total duration
    foreach ($attachment_ids as $attachment_id) {
        $duration = snn_ddt_total_get_video_duration($attachment_id);
        $total_duration += $duration;
    }
    
    if ($total_duration == 0) {
        return '0 minutes';
    }
    
    return snn_ddt_total_format_duration($total_duration);
}

// Step 5: Render the dynamic tag in Bricks Builder.
add_filter('bricks/dynamic_data/render_tag', 'render_total_video_duration_tag', 20, 3);
function render_total_video_duration_tag($tag, $post, $context = 'text') {
    // Ensure that $tag is a string before processing.
    if (is_string($tag)) {
        // Match {total_video_duration:field_name}
        if (strpos($tag, '{total_video_duration') === 0) {
            // Extract the field name from the tag
            if (preg_match('/{total_video_duration:([^}]+)}/', $tag, $matches)) {
                $field_name = trim($matches[1]);
                return get_total_video_duration($field_name);
            }
        }
    }

    // If $tag is an array, iterate through and process each element.
    if (is_array($tag)) {
        foreach ($tag as $key => $value) {
            if (is_string($value) && strpos($value, '{total_video_duration') === 0) {
                if (preg_match('/{total_video_duration:([^}]+)}/', $value, $matches)) {
                    $field_name = trim($matches[1]);
                    $tag[$key] = get_total_video_duration($field_name);
                }
            }
        }
        return $tag;
    }

    // Return the original tag if it doesn't match the expected pattern.
    return $tag;
}

// Step 6: Replace placeholders in dynamic content dynamically.
add_filter('bricks/dynamic_data/render_content', 'replace_total_video_duration_in_content', 20, 3);
add_filter('bricks/frontend/render_data', 'replace_total_video_duration_in_content', 20, 2);
function replace_total_video_duration_in_content($content, $post, $context = 'text') {
    if (!is_string($content)) {
        return $content;
    }

    // Match all {total_video_duration:field_name} tags
    preg_match_all('/{total_video_duration:([^}]+)}/', $content, $matches);

    if (!empty($matches[0])) {
        foreach ($matches[0] as $index => $full_match) {
            $field_name = isset($matches[1][$index]) ? trim($matches[1][$index]) : '';
            $value = get_total_video_duration($field_name);
            $content = str_replace($full_match, $value, $content);
        }
    }

    return $content;
}
