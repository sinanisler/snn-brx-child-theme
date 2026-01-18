<?php


/**
 * ----------------------------------------
 * Parent Link Tag Module
 * ----------------------------------------
 * Usage: {parent_link} or {parent_link:option:property}
 *
 * Supported Options:
 * - (default): Returns the top-level parent (root ancestor) as a link
 * - first_parent: Returns the immediate parent (one level up) as a link
 *
 * Supported Properties (optional):
 * - (default): Returns the full HTML link
 * - title: Returns only the title text
 * - url: Returns only the permalink URL
 * - link: Returns only the permalink URL (alias for url)
 * - slug: Returns only the post slug
 *
 * Examples:
 * - {parent_link} → Link to top-level parent
 * - {parent_link:title} → Title of top-level parent
 * - {parent_link:url} → URL of top-level parent
 * - {parent_link:link} → URL of top-level parent (alias)
 * - {parent_link:slug} → Slug of top-level parent
 * - {parent_link:first_parent} → Link to immediate parent
 * - {parent_link:first_parent:title} → Title of immediate parent
 * - {parent_link:first_parent:url} → URL of immediate parent
 * ----------------------------------------
 */

// Step 1: Register the dynamic tags with Bricks Builder.
add_filter('bricks/dynamic_tags_list', 'register_parent_link_tag');
function register_parent_link_tag($tags) {
    $options = [
        ''             => 'Parent Link (Top Level)',
        'first_parent' => 'Parent Link (Immediate)',
    ];

    $properties = [
        ''      => '',
        'title' => ' - Title',
        'url'   => ' - URL',
        'link'  => ' - Link (URL)',
        'slug'  => ' - Slug',
    ];

    foreach ($options as $option => $option_label) {
        foreach ($properties as $property => $property_label) {
            if ($option && $property) {
                $tag_name = "{parent_link:$option:$property}";
            } elseif ($option) {
                $tag_name = "{parent_link:$option}";
            } elseif ($property) {
                // Register {parent_link:property} for top-level parent with property
                $tag_name = "{parent_link:$property}";
            } else {
                $tag_name = '{parent_link}';
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

// Step 2: Get parent post data based on option and property
function get_parent_link_data($option = '', $property = '') {
    $current_post_id = get_the_ID();

    if (!$current_post_id) {
        return '';
    }

    $current_post = get_post($current_post_id);

    if (!$current_post || !$current_post->post_parent) {
        return '';
    }

    // Determine which parent to get
    $target_parent_id = null;

    if ($option === 'first_parent') {
        // Get immediate parent (one level up)
        $target_parent_id = $current_post->post_parent;
    } else {
        // Get top-level parent (root ancestor)
        $ancestors = get_post_ancestors($current_post_id);

        if (!empty($ancestors)) {
            // The last ancestor in the array is the top-level parent
            $target_parent_id = end($ancestors);
        } else {
            // No ancestors beyond immediate parent
            $target_parent_id = $current_post->post_parent;
        }
    }

    if (!$target_parent_id) {
        return '';
    }

    $parent_post = get_post($target_parent_id);

    if (!$parent_post) {
        return '';
    }

    // Return the requested property
    switch ($property) {
        case 'title':
            return get_the_title($parent_post->ID);

        case 'url':
        case 'link':
            return get_permalink($parent_post->ID);

        case 'slug':
            return $parent_post->post_name;

        default:
            // Default: return full HTML link
            $parent_title = get_the_title($parent_post->ID);
            $parent_link  = get_permalink($parent_post->ID);
            return '<a href="' . esc_url($parent_link) . '" title="' . esc_attr($parent_title) . '">' . esc_html($parent_title) . '</a>';
    }
}

// Step 3: Render the dynamic tag in Bricks Builder.
add_filter('bricks/dynamic_data/render_tag', 'render_parent_link_tag', 20, 3);
function render_parent_link_tag($tag, $post, $context = 'text') {
    // Ensure that $tag is a string before processing.
    if (is_string($tag)) {
        // Match {parent_link}, {parent_link:option}, or {parent_link:option:property}
        if (strpos($tag, '{parent_link') === 0) {
            // Extract option and property from the tag
            if (preg_match('/{parent_link:([^:}]*):([^}]+)}/', $tag, $matches)) {
                // {parent_link:option:property}
                $option = trim($matches[1]);
                $property = trim($matches[2]);
                return get_parent_link_data($option, $property);
            } elseif (preg_match('/{parent_link:([^}]+)}/', $tag, $matches)) {
                // {parent_link:option} or {parent_link:property}
                $value = trim($matches[1]);
                // Check if it's a property (title, url, link, slug) or an option (first_parent)
                if (in_array($value, ['title', 'url', 'link', 'slug'], true)) {
                    return get_parent_link_data('', $value);
                }
                return get_parent_link_data($value);
            } elseif ($tag === '{parent_link}') {
                // {parent_link}
                return get_parent_link_data();
            }
        }
    }

    // If $tag is an array, iterate through and process each element.
    if (is_array($tag)) {
        foreach ($tag as $key => $value) {
            if (is_string($value) && strpos($value, '{parent_link') === 0) {
                if (preg_match('/{parent_link:([^:}]*):([^}]+)}/', $value, $matches)) {
                    $option = trim($matches[1]);
                    $property = trim($matches[2]);
                    $tag[$key] = get_parent_link_data($option, $property);
                } elseif (preg_match('/{parent_link:([^}]+)}/', $value, $matches)) {
                    $val = trim($matches[1]);
                    if (in_array($val, ['title', 'url', 'link', 'slug'], true)) {
                        $tag[$key] = get_parent_link_data('', $val);
                    } else {
                        $tag[$key] = get_parent_link_data($val);
                    }
                } elseif ($value === '{parent_link}') {
                    $tag[$key] = get_parent_link_data();
                }
            }
        }
        return $tag;
    }

    // Return the original tag if it doesn't match the expected pattern.
    return $tag;
}

// Step 4: Replace placeholders in dynamic content dynamically.
add_filter('bricks/dynamic_data/render_content', 'render_parent_link_tag_in_content', 20, 3);
add_filter('bricks/frontend/render_data', 'render_parent_link_tag_in_content', 20, 2);
function render_parent_link_tag_in_content($content, $post, $context = 'text') {
    if (!is_string($content)) {
        return $content;
    }

    // Match all {parent_link}, {parent_link:option}, and {parent_link:option:property} tags
    preg_match_all('/{parent_link(?::([^:}]*))?(?::([^}]+))?}/', $content, $matches);

    if (!empty($matches[0])) {
        foreach ($matches[0] as $index => $full_match) {
            $first_param = isset($matches[1][$index]) && $matches[1][$index] ? $matches[1][$index] : '';
            $second_param = isset($matches[2][$index]) && $matches[2][$index] ? $matches[2][$index] : '';

            // If only first param exists and it's a property, treat it as property not option
            if ($first_param && !$second_param && in_array($first_param, ['title', 'url', 'link', 'slug'], true)) {
                $value = get_parent_link_data('', $first_param);
            } else {
                $value = get_parent_link_data($first_param, $second_param);
            }
            $content = str_replace($full_match, $value, $content);
        }
    }

    return $content;
}
