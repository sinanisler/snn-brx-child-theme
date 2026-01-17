<?php
/**
 * ----------------------------------------
 * Parent and Child Posts List Dynamic Tag Module
 * ----------------------------------------
 * Usage: {get_current_parent_and_child_list} or {get_current_parent_and_child_list:property}
 *
 * Supported Properties and Outputs:
 * - (default): Returns list with names and links (HTML list)
 * - name: Returns only names list
 * - id: Returns only IDs list
 *
 * Logic:
 * - Works on any post (parent or child)
 * - Always returns the top-level parent + all children
 * - Uses get_post_parent() to find the root parent
 * - Uses get_children() to get all child posts
 * ----------------------------------------
 */

// Step 1: Register the dynamic tags with Bricks Builder.
add_filter('bricks/dynamic_tags_list', 'add_parent_and_child_list_tags_to_builder');
function add_parent_and_child_list_tags_to_builder($tags) {
    $properties = [
        ''     => 'Parent & Child List (Name + Link)',
        'name' => 'Parent & Child List (Name Only)',
        'id'   => 'Parent & Child List (ID Only)',
    ];

    foreach ($properties as $property => $label) {
        $tag_name = $property ? "{get_current_parent_and_child_list:$property}" : '{get_current_parent_and_child_list}';
        $tags[] = [
            'name'  => $tag_name,
            'label' => $label,
            'group' => 'SNN',
        ];
    }

    return $tags;
}

// Step 2: Get the top-level parent post ID (root ancestor)
function get_top_level_parent_id($post_id) {
    $post = get_post($post_id);

    if (!$post) {
        return $post_id;
    }

    // If the post has no parent, it's already the top-level
    if ($post->post_parent == 0) {
        return $post_id;
    }

    // Traverse up to find the root parent
    $parent_id = $post->post_parent;
    while ($parent_id) {
        $parent_post = get_post($parent_id);
        if (!$parent_post || $parent_post->post_parent == 0) {
            break;
        }
        $parent_id = $parent_post->post_parent;
    }

    return $parent_id;
}

// Step 3: Get all descendants recursively
function get_all_descendants($parent_id, $post_type) {
    $all_children = [];

    $children = get_children([
        'post_parent' => $parent_id,
        'post_type'   => $post_type,
        'post_status' => 'publish',
        'orderby'     => 'menu_order title',
        'order'       => 'ASC',
    ]);

    foreach ($children as $child) {
        $all_children[] = $child;
        // Recursively get children of this child
        $grandchildren = get_all_descendants($child->ID, $post_type);
        $all_children = array_merge($all_children, $grandchildren);
    }

    return $all_children;
}

// Step 4: Main function to get parent and child list
function get_parent_and_child_list($property = '') {
    // Get the current post ID using get_queried_object_id for reliability
    $current_post_id = get_queried_object_id();

    // Fallback to get_the_ID if queried object is not available
    if (!$current_post_id) {
        $current_post_id = get_the_ID();
    }

    if (!$current_post_id) {
        return '';
    }

    $current_post = get_post($current_post_id);

    if (!$current_post) {
        return '';
    }

    $post_type = $current_post->post_type;

    // Get the top-level parent
    $top_parent_id = get_top_level_parent_id($current_post_id);
    $top_parent = get_post($top_parent_id);

    if (!$top_parent) {
        return '';
    }

    // Build the list: parent first, then all descendants
    $posts_list = [$top_parent];
    $descendants = get_all_descendants($top_parent_id, $post_type);
    $posts_list = array_merge($posts_list, $descendants);

    // Format output based on property
    $output = [];

    foreach ($posts_list as $post_item) {
        switch ($property) {
            case 'id':
                $output[] = $post_item->ID;
                break;
            case 'name':
                $output[] = $post_item->post_title;
                break;
            default:
                // Default: name with link
                $output[] = '<a href="' . esc_url(get_permalink($post_item->ID)) . '">' . esc_html($post_item->post_title) . '</a>';
                break;
        }
    }

    // Return as comma-separated list for id/name, or HTML list for default
    if ($property === 'id' || $property === 'name') {
        return implode(', ', $output);
    }

    // Default: return as unordered list
    return '<ul class="parent-child-list"><li>' . implode('</li><li>', $output) . '</li></ul>';
}

// Step 5: Render the dynamic tag in Bricks Builder.
add_filter('bricks/dynamic_data/render_tag', 'render_parent_and_child_list_tag', 20, 3);
function render_parent_and_child_list_tag($tag, $post, $context = 'text') {
    // Ensure that $tag is a string before processing.
    if (is_string($tag)) {
        // Match {get_current_parent_and_child_list} or {get_current_parent_and_child_list:property}
        if (strpos($tag, '{get_current_parent_and_child_list') === 0) {
            // Extract the property from the tag
            if (preg_match('/{get_current_parent_and_child_list:([^}]+)}/', $tag, $matches)) {
                $property = trim($matches[1]);
                return get_parent_and_child_list($property);
            } elseif ($tag === '{get_current_parent_and_child_list}') {
                return get_parent_and_child_list();
            }
        }
    }

    // If $tag is an array, iterate through and process each element.
    if (is_array($tag)) {
        foreach ($tag as $key => $value) {
            if (is_string($value) && strpos($value, '{get_current_parent_and_child_list') === 0) {
                if (preg_match('/{get_current_parent_and_child_list:([^}]+)}/', $value, $matches)) {
                    $property = trim($matches[1]);
                    $tag[$key] = get_parent_and_child_list($property);
                } elseif ($value === '{get_current_parent_and_child_list}') {
                    $tag[$key] = get_parent_and_child_list();
                }
            }
        }
        return $tag;
    }

    // Return the original tag if it doesn't match the expected pattern.
    return $tag;
}

// Step 6: Replace placeholders in dynamic content dynamically.
add_filter('bricks/dynamic_data/render_content', 'replace_parent_and_child_list_in_content', 20, 3);
add_filter('bricks/frontend/render_data', 'replace_parent_and_child_list_in_content', 20, 2);
function replace_parent_and_child_list_in_content($content, $post, $context = 'text') {
    if (!is_string($content)) {
        return $content;
    }

    // Match all {get_current_parent_and_child_list} and {get_current_parent_and_child_list:property} tags
    preg_match_all('/{get_current_parent_and_child_list(?::([^}]+))?}/', $content, $matches);

    if (!empty($matches[0])) {
        foreach ($matches[0] as $index => $full_match) {
            $property = isset($matches[1][$index]) && $matches[1][$index] ? $matches[1][$index] : '';
            $value = get_parent_and_child_list($property);
            $content = str_replace($full_match, $value, $content);
        }
    }

    return $content;
}
