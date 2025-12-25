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
        'author'            => 'Current Author ID',
        'post'              => 'Current Post ID',
        'term'              => 'Current Term ID',
        'taxonomy'          => 'Current Taxonomy Name',
        'user'              => 'Current Logged User ID',
        'page'              => 'Current Page ID',
        'category'          => 'Current Category ID',
        'tag'               => 'Current Tag ID',
        'archive'           => 'Current Archive Term ID',
        'search'            => 'Search Query',
        '404'               => '404 Page Indicator',
        'post_type'         => 'Current Post Type',
        'post_type_archive' => 'Post Type Archive Name',
        'queried_object_id' => 'Queried Object ID',
        'site'              => 'Site/Blog ID',
        'parent'            => 'Parent Post/Page ID',
        'template'          => 'Current Template Name',
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
            // On author archive pages, return the queried author ID
            if (is_author()) {
                return get_queried_object_id();
            }
            // On singular posts/pages, return the post author
            if (is_singular()) {
                return get_post_field('post_author', get_the_ID());
            }
            // In the loop, return current post's author
            if (in_the_loop() && get_the_ID()) {
                return get_post_field('post_author', get_the_ID());
            }
            return '';
            
        case 'post':
            // Return current post ID if available
            $post_id = get_the_ID();
            if ($post_id) {
                return $post_id;
            }
            // On front page or home, try to get page for posts ID
            if (is_home() && !is_front_page()) {
                return get_option('page_for_posts');
            }
            // On front page
            if (is_front_page() && get_option('show_on_front') === 'page') {
                return get_option('page_on_front');
            }
            return '';
            
        case 'term':
        case 'category':
        case 'tag':
            // Universal term ID getter with multiple fallbacks
            
            // 1. Check specific archive types first
            if ($type === 'category' && is_category()) {
                return get_queried_object_id();
            }
            if ($type === 'tag' && is_tag()) {
                return get_queried_object_id();
            }
            
            // 2. Any taxonomy archive
            if (is_category() || is_tag() || is_tax()) {
                $term_id = get_queried_object_id();
                if ($term_id) {
                    // For 'category' or 'tag' type, verify it's the right taxonomy
                    if ($type === 'category') {
                        $term = get_term($term_id);
                        return ($term && !is_wp_error($term) && $term->taxonomy === 'category') ? $term_id : '';
                    }
                    if ($type === 'tag') {
                        $term = get_term($term_id);
                        return ($term && !is_wp_error($term) && $term->taxonomy === 'post_tag') ? $term_id : '';
                    }
                    return $term_id;
                }
            }
            
            // 3. Try queried object
            $queried = get_queried_object();
            if (isset($queried->term_id)) {
                return $queried->term_id;
            }
            
            return '';
            
        case 'taxonomy':
            // On any taxonomy archive
            if (is_category() || is_tag() || is_tax()) {
                $queried = get_queried_object();
                return isset($queried->taxonomy) ? $queried->taxonomy : '';
            }
            return '';
            
        case 'user':
            return is_user_logged_in() ? get_current_user_id() : '';
            
        case 'page':
            // On page, return page ID
            if (is_page()) {
                return get_the_ID();
            }
            // On front page
            if (is_front_page() && get_option('show_on_front') === 'page') {
                return get_option('page_on_front');
            }
            // In loop on page
            if (in_the_loop() && is_page()) {
                return get_the_ID();
            }
            return '';
            
        case 'archive':
            // On any archive (including author, category, tag, date, custom post type, custom taxonomy)
            if (is_archive()) {
                return get_queried_object_id();
            }
            return '';
            
        case 'search':
            return is_search() ? get_search_query() : '';
            
        case '404':
            return is_404() ? '404' : '';
            
        case 'post_type':
            // Get current post type
            if (is_singular()) {
                return get_post_type();
            }
            if (is_post_type_archive()) {
                $pt = get_query_var('post_type');
                return is_array($pt) ? reset($pt) : $pt;
            }
            if (in_the_loop()) {
                return get_post_type();
            }
            return '';
            
        case 'post_type_archive':
            // Get post type archive slug
            if (is_post_type_archive()) {
                $post_type = get_query_var('post_type');
                return is_array($post_type) ? reset($post_type) : $post_type;
            }
            return '';
            
        case 'queried_object_id':
            // Universal fallback - gets ID of whatever is being queried
            $id = get_queried_object_id();
            return $id ? $id : '';
            
        case 'site':
            // Multisite support
            return get_current_blog_id();
            
        case 'parent':
            // Get parent post/page ID
            if (is_singular()) {
                $post = get_post();
                return $post && $post->post_parent ? $post->post_parent : '';
            }
            return '';
            
        case 'template':
            // Get current template file name
            if (is_singular()) {
                $template = get_page_template_slug();
                return $template ? basename($template, '.php') : 'default';
            }
            return '';
            
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
