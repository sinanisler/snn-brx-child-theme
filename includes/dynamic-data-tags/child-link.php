<?php


/**
 * ----------------------------------------
 * Child Link Tag Module
 * ----------------------------------------
 * Usage: {child_link} or {child_link:option:property}
 *
 * Supported Options:
 * - (default): Returns the first child (based on menu_order) as a link
 * - first_child: Returns the first child (same as default, explicit)
 * - last_child: Returns the last child (based on menu_order DESC)
 *
 * Supported Properties (optional):
 * - (default): Returns the full HTML link
 * - title: Returns only the title text
 * - url: Returns only the permalink URL
 * - slug: Returns only the post slug
 *
 * Examples:
 * - {child_link} → Link to first child
 * - {child_link:first_child} → Link to first child (explicit)
 * - {child_link:last_child} → Link to last child
 * - {child_link:first_child:title} → Title of first child
 * - {child_link:last_child:url} → URL of last child
 * - {child_link::title} → Title of first child (note double colon)
 * ----------------------------------------
 */

// Step 1: Register the dynamic tags with Bricks Builder.
add_filter('bricks/dynamic_tags_list', 'register_child_link_tag');
function register_child_link_tag($tags) {
    $options = [
        ''            => 'Child Link (First)',
        'first_child' => 'Child Link (First)',
        'last_child'  => 'Child Link (Last)',
    ];

    $properties = [
        ''      => '',
        'title' => ' - Title',
        'url'   => ' - URL',
        'slug'  => ' - Slug',
    ];

    foreach ($options as $option => $option_label) {
        foreach ($properties as $property => $property_label) {
            if ($option && $property) {
                $tag_name = "{child_link:$option:$property}";
            } elseif ($option) {
                $tag_name = "{child_link:$option}";
            } elseif ($property) {
                $tag_name = "{child_link::$property}";
            } else {
                $tag_name = '{child_link}';
            }

            // Skip duplicate registration of {child_link}
            if ($option === 'first_child' && $property === '') {
                continue;
            }

            $tags[] = [
                'name'  => $tag_name,
                'label' => $option_label . $property_label,
                'group' => 'SNN',
            ];
        }
    }

    return $tags;
}

// Step 2: Get child post data based on option and property
function get_child_link_data($option = '', $property = '') {
    $current_post_id = get_the_ID();

    if (!$current_post_id) {
        return '';
    }

    $current_post = get_post($current_post_id);

    if (!$current_post) {
        return '';
    }

    // Determine which child to get
    $order = 'ASC'; // Default to first child

    if ($option === 'last_child') {
        $order = 'DESC';
    }

    // Query for children based on menu_order
    $args = [
        'post_type'      => $current_post->post_type,
        'post_parent'    => $current_post_id,
        'posts_per_page' => 1,
        'orderby'        => 'menu_order',
        'order'          => $order,
        'post_status'    => 'publish',
    ];

    $children = get_posts($args);

    if (empty($children)) {
        return '';
    }

    $child_post = $children[0];

    // Return the requested property
    switch ($property) {
        case 'title':
            return get_the_title($child_post->ID);

        case 'url':
            return get_permalink($child_post->ID);

        case 'slug':
            return $child_post->post_name;

        default:
            // Default: return full HTML link
            $child_title = get_the_title($child_post->ID);
            $child_link  = get_permalink($child_post->ID);
            return '<a href="' . esc_url($child_link) . '" title="' . esc_attr($child_title) . '">' . esc_html($child_title) . '</a>';
    }
}

// Step 3: Render the dynamic tag in Bricks Builder.
add_filter('bricks/dynamic_data/render_tag', 'render_child_link_tag', 20, 3);
function render_child_link_tag($tag, $post, $context = 'text') {
    // Ensure that $tag is a string before processing.
    if (is_string($tag)) {
        // Match {child_link}, {child_link:option}, or {child_link:option:property}
        if (strpos($tag, '{child_link') === 0) {
            // Extract option and property from the tag
            if (preg_match('/{child_link:([^:}]*):([^}]+)}/', $tag, $matches)) {
                // {child_link:option:property}
                $option = trim($matches[1]);
                $property = trim($matches[2]);
                return get_child_link_data($option, $property);
            } elseif (preg_match('/{child_link:([^}]+)}/', $tag, $matches)) {
                // {child_link:option}
                $option = trim($matches[1]);
                return get_child_link_data($option);
            } elseif ($tag === '{child_link}') {
                // {child_link}
                return get_child_link_data();
            }
        }
    }

    // If $tag is an array, iterate through and process each element.
    if (is_array($tag)) {
        foreach ($tag as $key => $value) {
            if (is_string($value) && strpos($value, '{child_link') === 0) {
                if (preg_match('/{child_link:([^:}]*):([^}]+)}/', $value, $matches)) {
                    $option = trim($matches[1]);
                    $property = trim($matches[2]);
                    $tag[$key] = get_child_link_data($option, $property);
                } elseif (preg_match('/{child_link:([^}]+)}/', $value, $matches)) {
                    $option = trim($matches[1]);
                    $tag[$key] = get_child_link_data($option);
                } elseif ($value === '{child_link}') {
                    $tag[$key] = get_child_link_data();
                }
            }
        }
        return $tag;
    }

    // Return the original tag if it doesn't match the expected pattern.
    return $tag;
}

// Step 4: Replace placeholders in dynamic content dynamically.
add_filter('bricks/dynamic_data/render_content', 'render_child_link_tag_in_content', 20, 3);
add_filter('bricks/frontend/render_data', 'render_child_link_tag_in_content', 20, 2);
function render_child_link_tag_in_content($content, $post, $context = 'text') {
    if (!is_string($content)) {
        return $content;
    }

    // Match all {child_link}, {child_link:option}, and {child_link:option:property} tags
    preg_match_all('/{child_link(?::([^:}]*))?(?::([^}]+))?}/', $content, $matches);

    if (!empty($matches[0])) {
        foreach ($matches[0] as $index => $full_match) {
            $option = isset($matches[1][$index]) && $matches[1][$index] ? $matches[1][$index] : '';
            $property = isset($matches[2][$index]) && $matches[2][$index] ? $matches[2][$index] : '';
            $value = get_child_link_data($option, $property);
            $content = str_replace($full_match, $value, $content);
        }
    }

    return $content;
}
