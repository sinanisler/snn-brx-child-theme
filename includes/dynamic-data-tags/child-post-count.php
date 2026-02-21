<?php
/**
 * ----------------------------------------
 * Child Post Count Dynamic Tag Module
 * ----------------------------------------
 * Usage:
 *   {get_childs_post_count}
 *   {get_childs_post_count:level_n}
 *   {get_childs_post_count:post_type}
 *   {get_childs_post_count:post_type:level_n}
 *
 * Supported Properties and Outputs:
 * - (default)            : Total count of all child posts, any level (same post type as parent)
 * - level_n              : Count of children at exactly level n (same post type as parent)
 * - post_type            : Total count of all child posts of specified post type (all levels)
 * - post_type:level_n    : Count of children of specified post type at exactly level n
 *
 * Works inside and outside Bricks query loops (falls back to queried object on single views).
 * ----------------------------------------
 */

// Step 1: Register the dynamic tags with Bricks Builder.
add_filter('bricks/dynamic_tags_list', 'add_child_post_count_tags_to_builder');
function add_child_post_count_tags_to_builder($tags) {
    $levels = [
        ''        => 'All Levels',
        'level_1' => 'Level 1',
        'level_2' => 'Level 2',
        'level_3' => 'Level 3',
        'level_4' => 'Level 4',
        'level_5' => 'Level 5',
    ];

    // Generic tags (uses current post's post type)
    foreach ($levels as $level => $level_label) {
        $tag_name = $level ? "{get_childs_post_count:$level}" : '{get_childs_post_count}';
        $tags[] = [
            'name'  => $tag_name,
            'label' => "Child Post Count ($level_label)",
            'group' => 'SNN',
        ];
    }

    // Post-type-specific tags (works outside loops too)
    $post_types = get_post_types(['public' => true], 'objects');
    foreach ($post_types as $pt) {
        foreach ($levels as $level => $level_label) {
            $tag_name = $level
                ? "{get_childs_post_count:{$pt->name}:$level}"
                : "{get_childs_post_count:{$pt->name}}";
            $tags[] = [
                'name'  => $tag_name,
                'label' => "Child Post Count: {$pt->label} ($level_label)",
                'group' => 'SNN',
            ];
        }
    }

    return $tags;
}

// Step 2: Get child post count based on the specified depth level and/or post type.
function get_child_post_count($property = '') {
    // Get current post ID — works inside loops
    $current_post_id = get_the_ID();

    // Fallback for use outside of loops on single post/page views
    if (!$current_post_id) {
        $queried = get_queried_object();
        if ($queried instanceof WP_Post) {
            $current_post_id = $queried->ID;
        }
    }

    if (!$current_post_id) {
        return 0;
    }

    $current_post = get_post($current_post_id);

    if (!$current_post) {
        return 0;
    }

    $post_type_override = null;
    $max_depth          = null;

    if (!empty($property)) {
        if (strpos($property, ':') !== false) {
            // Format: "post_type:level_n"
            $parts              = explode(':', $property, 2);
            $post_type_override = trim($parts[0]);
            $level_part         = trim($parts[1]);
            if (strpos($level_part, 'level_') === 0) {
                $level_number = substr($level_part, 6); // strip "level_"
                if (is_numeric($level_number)) {
                    $max_depth = (int) $level_number;
                }
            }
        } elseif (strpos($property, 'level_') === 0) {
            // Format: "level_n" — same post type, specific depth
            $level_number = substr($property, 6);
            if (is_numeric($level_number)) {
                $max_depth = (int) $level_number;
            }
        } else {
            // Format: "post_type" — specified type, all levels
            $post_type_override = $property;
        }
    }

    $query_post_type = $post_type_override !== null ? $post_type_override : $current_post->post_type;

    return count_children_recursive($current_post_id, $query_post_type, $max_depth);
}

// Helper function to count children recursively.
function count_children_recursive($parent_id, $post_type, $max_depth = null, $current_depth = 1) {
    $args = [
        'post_type'      => $post_type,
        'post_parent'    => $parent_id,
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'fields'         => 'ids',
    ];

    $children = get_posts($args);
    $count    = 0;

    // No depth limit — count ALL descendants
    if ($max_depth === null) {
        $count = count($children);
        foreach ($children as $child_id) {
            $count += count_children_recursive($child_id, $post_type, null, $current_depth + 1);
        }
        return $count;
    }

    // Depth limit — count only children AT exactly that depth
    if ($current_depth === $max_depth) {
        return count($children);
    }

    if ($current_depth < $max_depth) {
        foreach ($children as $child_id) {
            $count += count_children_recursive($child_id, $post_type, $max_depth, $current_depth + 1);
        }
    }

    return $count;
}

// Step 3: Render the dynamic tag in Bricks Builder.
add_filter('bricks/dynamic_data/render_tag', 'render_child_post_count_tag', 20, 3);
function render_child_post_count_tag($tag, $post, $context = 'text') {
    if (is_string($tag)) {
        if (strpos($tag, '{get_childs_post_count') === 0) {
            if (preg_match('/{get_childs_post_count:([^}]+)}/', $tag, $matches)) {
                return get_child_post_count(trim($matches[1]));
            } elseif ($tag === '{get_childs_post_count}') {
                return get_child_post_count();
            }
        }
    }

    if (is_array($tag)) {
        foreach ($tag as $key => $value) {
            if (is_string($value) && strpos($value, '{get_childs_post_count') === 0) {
                if (preg_match('/{get_childs_post_count:([^}]+)}/', $value, $matches)) {
                    $tag[$key] = get_child_post_count(trim($matches[1]));
                } elseif ($value === '{get_childs_post_count}') {
                    $tag[$key] = get_child_post_count();
                }
            }
        }
        return $tag;
    }

    return $tag;
}

// Step 4: Replace placeholders in dynamic content.
add_filter('bricks/dynamic_data/render_content', 'replace_child_post_count_in_content', 20, 3);
add_filter('bricks/frontend/render_data', 'replace_child_post_count_in_content', 20, 2);
function replace_child_post_count_in_content($content, $post, $context = 'text') {
    if (!is_string($content)) {
        return $content;
    }

    preg_match_all('/{get_childs_post_count(?::([^}]+))?}/', $content, $matches);

    if (!empty($matches[0])) {
        foreach ($matches[0] as $index => $full_match) {
            $property = isset($matches[1][$index]) && $matches[1][$index] ? $matches[1][$index] : '';
            $content  = str_replace($full_match, get_child_post_count($property), $content);
        }
    }

    return $content;
}
