<?php
/**
 * ----------------------------------------
 * Child Post Count Dynamic Tag Module
 * ----------------------------------------
 * Usage: {get_childs_post_count} or {get_childs_post_count:level_n}
 *
 * Supported Properties and Outputs:
 * - (default): Returns the total count of all child posts (all levels)
 * - level_1: Returns the count of direct children (1st level only)
 * - level_2: Returns the count of children up to 2 levels deep
 * - level_n: Returns the count of children up to n levels deep (e.g., level_3, level_4, etc.)
 *
 * Logic:
 * - Gets the current post's ID
 * - Counts child posts based on the specified depth level
 * - Returns the count as a number
 * ----------------------------------------
 */

// Step 1: Register the dynamic tags with Bricks Builder.
add_filter('bricks/dynamic_tags_list', 'add_child_post_count_tags_to_builder');
function add_child_post_count_tags_to_builder($tags) {
    $properties = [
        ''        => 'Child Post Count (All Levels)',
        'level_1' => 'Child Post Count (Level 1)',
        'level_2' => 'Child Post Count (Level 2)',
        'level_3' => 'Child Post Count (Level 3)',
        'level_4' => 'Child Post Count (Level 4)',
        'level_5' => 'Child Post Count (Level 5)',
    ];

    foreach ($properties as $property => $label) {
        $tag_name = $property ? "{get_childs_post_count:$property}" : '{get_childs_post_count}';
        $tags[] = [
            'name'  => $tag_name,
            'label' => $label,
            'group' => 'SNN',
        ];
    }

    return $tags;
}

// Step 2: Get child post count based on the specified depth level.
function get_child_post_count($property = '') {
    // Get the current post ID
    $current_post_id = get_the_ID();

    if (!$current_post_id) {
        return 0;
    }

    // Get the current post object
    $current_post = get_post($current_post_id);

    if (!$current_post) {
        return 0;
    }

    // Determine the max depth level
    $max_depth = null;

    if (!empty($property) && strpos($property, 'level_') === 0) {
        // Extract the level number (e.g., "level_2" -> 2)
        $level_number = str_replace('level_', '', $property);
        if (is_numeric($level_number)) {
            $max_depth = (int)$level_number;
        }
    }

    // Count children recursively
    $count = count_children_recursive($current_post_id, $current_post->post_type, $max_depth);

    return $count;
}

// Helper function to count children recursively
function count_children_recursive($parent_id, $post_type, $max_depth = null, $current_depth = 1) {
    // Query for direct children
    $args = [
        'post_type'      => $post_type,
        'post_parent'    => $parent_id,
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'fields'         => 'ids', // Only get IDs for better performance
    ];

    $children = get_posts($args);
    $count = count($children);

    // If max_depth is set and we've reached it, don't go deeper
    if ($max_depth !== null && $current_depth >= $max_depth) {
        return $count;
    }

    // Count grandchildren recursively
    foreach ($children as $child_id) {
        $count += count_children_recursive($child_id, $post_type, $max_depth, $current_depth + 1);
    }

    return $count;
}

// Step 3: Render the dynamic tag in Bricks Builder.
add_filter('bricks/dynamic_data/render_tag', 'render_child_post_count_tag', 20, 3);
function render_child_post_count_tag($tag, $post, $context = 'text') {
    // Ensure that $tag is a string before processing.
    if (is_string($tag)) {
        // Match {get_childs_post_count} or {get_childs_post_count:property}
        if (strpos($tag, '{get_childs_post_count') === 0) {
            // Extract the property from the tag
            if (preg_match('/{get_childs_post_count:([^}]+)}/', $tag, $matches)) {
                $property = trim($matches[1]);
                return get_child_post_count($property);
            } elseif ($tag === '{get_childs_post_count}') {
                return get_child_post_count();
            }
        }
    }

    // If $tag is an array, iterate through and process each element.
    if (is_array($tag)) {
        foreach ($tag as $key => $value) {
            if (is_string($value) && strpos($value, '{get_childs_post_count') === 0) {
                if (preg_match('/{get_childs_post_count:([^}]+)}/', $value, $matches)) {
                    $property = trim($matches[1]);
                    $tag[$key] = get_child_post_count($property);
                } elseif ($value === '{get_childs_post_count}') {
                    $tag[$key] = get_child_post_count();
                }
            }
        }
        return $tag;
    }

    // Return the original tag if it doesn't match the expected pattern.
    return $tag;
}

// Step 4: Replace placeholders in dynamic content dynamically.
add_filter('bricks/dynamic_data/render_content', 'replace_child_post_count_in_content', 20, 3);
add_filter('bricks/frontend/render_data', 'replace_child_post_count_in_content', 20, 2);
function replace_child_post_count_in_content($content, $post, $context = 'text') {
    if (!is_string($content)) {
        return $content;
    }

    // Match all {get_childs_post_count} and {get_childs_post_count:property} tags
    preg_match_all('/{get_childs_post_count(?::([^}]+))?}/', $content, $matches);

    if (!empty($matches[0])) {
        foreach ($matches[0] as $index => $full_match) {
            $property = isset($matches[1][$index]) && $matches[1][$index] ? $matches[1][$index] : '';
            $value = get_child_post_count($property);
            $content = str_replace($full_match, $value, $content);
        }
    }

    return $content;
}
