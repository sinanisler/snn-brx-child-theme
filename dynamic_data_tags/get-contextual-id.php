<?php
/**
 * ----------------------------------------
 * Dynamic ID Tag Module
 * ----------------------------------------
 * Usage: {get_contextual_id:type}
 *
 * Supported Types and Possible Outputs:
 * - author: Displays the ID of the current post's author. (e.g., 3)
 * - post: Displays the ID of the current post. (e.g., 45)
 * - term: Displays the ID of the current term in a taxonomy archive. (e.g., 12)
 * - taxonomy: Displays the name of the current taxonomy. (e.g., 'category')
 * - user: Displays the ID of the current logged-in user. (e.g., 5)
 * - page: Displays the ID of the current page. (e.g., 67)
 * - category: Displays the ID of the current category. (e.g., 8)
 * - tag: Displays the ID of the current tag. (e.g., 14)
 * - archive: Displays the ID of the current archive term. (e.g., 9)
 * - search: Displays the search query. (e.g., 'search term')
 * - 404: Indicates a 404 error page. (Outputs '404' if on a 404 page)
 * ----------------------------------------
 */

// Step 1: Register the dynamic tags with Bricks Builder.
add_filter('bricks/dynamic_tags_list', 'add_get_contextual_id_tags_to_builder');
function add_get_contextual_id_tags_to_builder($tags) {
    $types = [
        'author'   => 'Current Author ID',
        'post'     => 'Current Post ID',
        'term'     => 'Current Term ID',
        'taxonomy' => 'Current Taxonomy Name',
        'user'     => 'Current Logged User ID',
        'page'     => 'Current Page ID',
        'category' => 'Current Category ID',
        'tag'      => 'Current Tag ID',
        'archive'  => 'Current Archive Term ID',
        'search'   => 'Search Query',
        '404'      => '404 Page Indicator',
    ];

    foreach ($types as $type => $label) {
        $tags[] = [
            'name'  => "{get_contextual_id:$type}",
            'label' => $label,
            'group' => 'SNN', // Group name in Bricks Builder.
        ];
    }

    return $tags;
}

// Step 2: Fetch the appropriate ID or information based on the type.
function get_contextual_id($type) {
    switch ($type) {
        case 'author':
            return is_singular() ? get_post_field('post_author', get_the_ID()) : '';
        case 'post':
            return is_singular() ? get_the_ID() : '';
        case 'term':
            if (is_category() || is_tag() || is_tax()) {
                $term = get_queried_object();
                return isset($term->term_id) ? $term->term_id : '';
            }
            return '';
        case 'taxonomy':
            if (is_tax()) {
                $taxonomy = get_queried_object();
                return isset($taxonomy->taxonomy) ? $taxonomy->taxonomy : '';
            }
            return '';
        case 'user':
            return is_user_logged_in() ? get_current_user_id() : '';
        case 'page':
            return is_page() ? get_the_ID() : '';
        case 'category':
            return is_category() ? get_queried_object_id() : '';
        case 'tag':
            return is_tag() ? get_queried_object_id() : '';
        case 'archive':
            return is_archive() ? get_queried_object_id() : '';
        case 'search':
            return is_search() ? get_search_query() : '';
        case '404':
            return is_404() ? '404' : '';
        default:
            return '';
    }
}

// Step 3: Render the dynamic tag in Bricks Builder.
add_filter('bricks/dynamic_data/render_tag', 'render_get_contextual_id_tag', 20, 3);
function render_get_contextual_id_tag($tag, $post, $context = 'text') {
    // Ensure that $tag is a string before processing.
    if (is_string($tag) && strpos($tag, '{get_contextual_id:') === 0) {
        // Extract the type from the tag.
        $type = trim(str_replace(['{get_contextual_id:', '}'], '', $tag));
        return get_contextual_id($type);
    }

    // If $tag is an array, iterate through and process each element.
    if (is_array($tag)) {
        foreach ($tag as $key => $value) {
            if (is_string($value) && strpos($value, '{get_contextual_id:') === 0) {
                $type = trim(str_replace(['{get_contextual_id:', '}'], '', $value));
                $tag[$key] = get_contextual_id($type);
            }
        }
        return $tag;
    }

    // Return the original tag if it doesn't match the expected pattern.
    return $tag;
}

// Step 4: Replace placeholders in dynamic content dynamically.
add_filter('bricks/dynamic_data/render_content', 'replace_get_contextual_id_in_content', 20, 3);
add_filter('bricks/frontend/render_data', 'replace_get_contextual_id_in_content', 20, 2);
function replace_get_contextual_id_in_content($content, $post, $context = 'text') {
    if (!is_string($content)) {
        return $content;
    }

    preg_match_all('/{get_contextual_id:([^}]+)}/', $content, $matches);
    if (!empty($matches[1])) {
        foreach ($matches[1] as $type) {
            $id = get_contextual_id($type);
            $content = str_replace("{get_contextual_id:$type}", $id, $content);
        }
    }
    return $content;
}
