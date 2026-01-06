<?php
/**
 * ----------------------------------------
 * First Child Post Dynamic Tag Module
 * ----------------------------------------
 * Usage: {first_child_post} or {first_child_post:property}
 *
 * Supported Properties and Outputs:
 * - (default): Returns the URL/permalink of the first child post (e.g., https://example.com/parent/first-child/)
 * - title: Returns the title of the first child post (e.g., "First Child Post")
 * - slug: Returns the slug/post_name of the first child post (e.g., "first-child")
 * - first_grand_child: Returns the URL/permalink of the first grandchild (first child's first child)
 * - first_grand_child:title: Returns the title of the first grandchild
 * - first_grand_child:slug: Returns the slug of the first grandchild
 *
 * Logic:
 * - Gets the current post's grandparent (parent of parent)
 * - Finds the first child of that grandparent based on menu_order (ASC)
 * - For first_grand_child properties, gets the first child of the first child
 * - Returns the requested property
 * ----------------------------------------
 */

// Step 1: Register the dynamic tags with Bricks Builder.
add_filter('bricks/dynamic_tags_list', 'add_first_child_post_tags_to_builder');
function add_first_child_post_tags_to_builder($tags) {
    $properties = [
        ''                       => 'First Child Post URL',
        'title'                  => 'First Child Post Title',
        'slug'                   => 'First Child Post Slug',
        'first_grand_child'      => 'First Grand Child URL',
        'first_grand_child:title' => 'First Grand Child Title',
        'first_grand_child:slug'  => 'First Grand Child Slug',
    ];

    foreach ($properties as $property => $label) {
        $tag_name = $property ? "{first_child_post:$property}" : '{first_child_post}';
        $tags[] = [
            'name'  => $tag_name,
            'label' => $label,
            'group' => 'SNN',
        ];
    }

    return $tags;
}

// Step 2: Get the first child post based on the current context.
function get_first_child_post($property = '') {
    // Get the current post ID
    $current_post_id = get_the_ID();

    if (!$current_post_id) {
        return '';
    }

    // Get the current post object
    $current_post = get_post($current_post_id);

    if (!$current_post) {
        return '';
    }

    // Get all ancestors (parent, grandparent, great-grandparent, etc.)
    $ancestors = get_post_ancestors($current_post_id);

    // Determine which page to get children from
    $target_parent_id = null;

    if (empty($ancestors)) {
        // No ancestors - we're on a top-level page
        // Get the first child of the current page itself
        $target_parent_id = $current_post_id;
    } elseif (isset($ancestors[1])) {
        // Has grandparent - get grandparent's first child
        $target_parent_id = $ancestors[1];
    } else {
        // Only has parent - get parent's first child
        $target_parent_id = $ancestors[0];
    }

    // Query for the first child based on menu_order
    $args = [
        'post_type'      => $current_post->post_type,
        'post_parent'    => $target_parent_id,
        'posts_per_page' => 1,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
        'post_status'    => 'publish',
    ];

    $children = get_posts($args);

    if (empty($children)) {
        return '';
    }

    $first_child = $children[0];

    // Handle first_grand_child properties
    if (strpos($property, 'first_grand_child') === 0) {
        // Get the first child of the first child (grandchild)
        $grandchild_args = [
            'post_type'      => $current_post->post_type,
            'post_parent'    => $first_child->ID,
            'posts_per_page' => 1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
            'post_status'    => 'publish',
        ];

        $grandchildren = get_posts($grandchild_args);

        if (empty($grandchildren)) {
            return '';
        }

        $first_grandchild = $grandchildren[0];

        // Check for sub-property after first_grand_child
        if ($property === 'first_grand_child:title') {
            return get_the_title($first_grandchild->ID);
        } elseif ($property === 'first_grand_child:slug') {
            return $first_grandchild->post_name;
        } else {
            // Default: return URL/permalink of grandchild
            return get_permalink($first_grandchild->ID);
        }
    }

    // Return the requested property for first child
    switch ($property) {
        case 'title':
            return get_the_title($first_child->ID);

        case 'slug':
            return $first_child->post_name;

        default:
            // Default: return URL/permalink
            return get_permalink($first_child->ID);
    }
}

// Step 3: Render the dynamic tag in Bricks Builder.
add_filter('bricks/dynamic_data/render_tag', 'render_first_child_post_tag', 20, 3);
function render_first_child_post_tag($tag, $post, $context = 'text') {
    // Ensure that $tag is a string before processing.
    if (is_string($tag)) {
        // Match {first_child_post} or {first_child_post:property}
        if (strpos($tag, '{first_child_post') === 0) {
            // Extract the property from the tag
            if (preg_match('/{first_child_post:([^}]+)}/', $tag, $matches)) {
                $property = trim($matches[1]);
                return get_first_child_post($property);
            } elseif ($tag === '{first_child_post}') {
                return get_first_child_post();
            }
        }
    }

    // If $tag is an array, iterate through and process each element.
    if (is_array($tag)) {
        foreach ($tag as $key => $value) {
            if (is_string($value) && strpos($value, '{first_child_post') === 0) {
                if (preg_match('/{first_child_post:([^}]+)}/', $value, $matches)) {
                    $property = trim($matches[1]);
                    $tag[$key] = get_first_child_post($property);
                } elseif ($value === '{first_child_post}') {
                    $tag[$key] = get_first_child_post();
                }
            }
        }
        return $tag;
    }

    // Return the original tag if it doesn't match the expected pattern.
    return $tag;
}

// Step 4: Replace placeholders in dynamic content dynamically.
add_filter('bricks/dynamic_data/render_content', 'replace_first_child_post_in_content', 20, 3);
add_filter('bricks/frontend/render_data', 'replace_first_child_post_in_content', 20, 2);
function replace_first_child_post_in_content($content, $post, $context = 'text') {
    if (!is_string($content)) {
        return $content;
    }

    // Match all {first_child_post} and {first_child_post:property} tags
    preg_match_all('/{first_child_post(?::([^}]+))?}/', $content, $matches);

    if (!empty($matches[0])) {
        foreach ($matches[0] as $index => $full_match) {
            $property = isset($matches[1][$index]) && $matches[1][$index] ? $matches[1][$index] : '';
            $value = get_first_child_post($property);
            $content = str_replace($full_match, $value, $content);
        }
    }

    return $content;
}
