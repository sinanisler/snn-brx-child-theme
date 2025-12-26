<?php
/**
 * ----------------------------------------
 * Dynamic Slug Tag Module
 * ----------------------------------------
 * Usage: {get_contextual_slug:type}
 * 
 * Supported Types and Possible Outputs:
 * - post: Displays the slug of the current post. (e.g., 'my-post-title')
 * - author: Displays the slug (nicename) of the current post's author. (e.g., 'john-doe')
 * - term: Displays the slug of the current term in a taxonomy archive. (e.g., 'uncategorized')
 * - taxonomy: Displays the slug of the current taxonomy. (e.g., 'category')
 * - category: Displays the slug of the current category. (e.g., 'news')
 * - tag: Displays the slug of the current tag. (e.g., 'wordpress')
 * - post_type: Displays the slug of the current post type. (e.g., 'post', 'page', 'product')
 * - post_type_archive: Displays the archive slug of the current post type. (e.g., 'products')
 * - page: Displays the slug of the current page. (e.g., 'about-us')
 * - archive: Displays the slug of the current archive term. (e.g., 'technology')
 * - user: Displays the slug (nicename) of the current logged-in user. (e.g., 'jane-smith')
 * - parent: Displays the slug of the parent post/page. (e.g., 'parent-page')
 * - site: Displays the site slug (for multisite). (e.g., 'myblog')
 * - search: Displays the sanitized search query slug. (e.g., 'search-term')
 * ----------------------------------------
 */

// Step 1: Register the dynamic tags with Bricks Builder.
add_filter('bricks/dynamic_tags_list', 'add_get_contextual_slug_tags_to_builder');
function add_get_contextual_slug_tags_to_builder($tags) {
    $types = [
        'post'              => 'Current Post Slug',
        'author'            => 'Current Author Slug',
        'term'              => 'Current Term Slug',
        'taxonomy'          => 'Current Taxonomy Slug',
        'category'          => 'Current Category Slug',
        'tag'               => 'Current Tag Slug',
        'post_type'         => 'Current Post Type Slug',
        'post_type_archive' => 'Post Type Archive Slug',
        'page'              => 'Current Page Slug',
        'archive'           => 'Current Archive Term Slug',
        'user'              => 'Current Logged User Slug',
        'parent'            => 'Parent Post/Page Slug',
        'site'              => 'Site Slug (Multisite)',
        'search'            => 'Search Query Slug',
    ];

    foreach ($types as $type => $label) {
        $tags[] = [
            'name'  => "{get_contextual_slug:$type}",
            'label' => $label,
            'group' => 'SNN', // Group name in Bricks Builder.
        ];
    }

    return $tags;
}

// Step 2: Fetch the appropriate slug based on the type.
function get_contextual_slug($type) {
    switch ($type) {
        case 'author':
            // On author archive pages, return the queried author slug
            if (is_author()) {
                $author = get_queried_object();
                return isset($author->user_nicename) ? $author->user_nicename : '';
            }
            // On singular posts/pages, return the post author slug
            if (is_singular()) {
                $author_id = get_post_field('post_author', get_the_ID());
                if ($author_id) {
                    $author = get_userdata($author_id);
                    return $author ? $author->user_nicename : '';
                }
            }
            // In the loop, return current post's author slug
            if (in_the_loop() && get_the_ID()) {
                $author_id = get_post_field('post_author', get_the_ID());
                if ($author_id) {
                    $author = get_userdata($author_id);
                    return $author ? $author->user_nicename : '';
                }
            }
            return '';
            
        case 'post':
            // Return current post slug if available
            $post = get_post();
            if ($post) {
                return $post->post_name;
            }
            // On front page or home, try to get page for posts slug
            if (is_home() && !is_front_page()) {
                $page_id = get_option('page_for_posts');
                if ($page_id) {
                    $page = get_post($page_id);
                    return $page ? $page->post_name : '';
                }
            }
            // On front page
            if (is_front_page() && get_option('show_on_front') === 'page') {
                $page_id = get_option('page_on_front');
                if ($page_id) {
                    $page = get_post($page_id);
                    return $page ? $page->post_name : '';
                }
            }
            return '';
            
        case 'term':
        case 'category':
        case 'tag':
            // Universal term slug getter with multiple fallbacks
            
            // 1. Check specific archive types first
            if ($type === 'category' && is_category()) {
                $term = get_queried_object();
                return isset($term->slug) ? $term->slug : '';
            }
            if ($type === 'tag' && is_tag()) {
                $term = get_queried_object();
                return isset($term->slug) ? $term->slug : '';
            }
            
            // 2. Any taxonomy archive
            if (is_category() || is_tag() || is_tax()) {
                $term = get_queried_object();
                if (isset($term->slug)) {
                    // For 'category' or 'tag' type, verify it's the right taxonomy
                    if ($type === 'category' && isset($term->taxonomy) && $term->taxonomy === 'category') {
                        return $term->slug;
                    }
                    if ($type === 'tag' && isset($term->taxonomy) && $term->taxonomy === 'post_tag') {
                        return $term->slug;
                    }
                    if ($type === 'term') {
                        return $term->slug;
                    }
                }
            }
            
            // 3. Try queried object
            $queried = get_queried_object();
            if (isset($queried->slug)) {
                return $queried->slug;
            }
            
            return '';
            
        case 'taxonomy':
            // On any taxonomy archive
            if (is_category() || is_tag() || is_tax()) {
                $queried = get_queried_object();
                if (isset($queried->taxonomy)) {
                    $taxonomy = get_taxonomy($queried->taxonomy);
                    // Return rewrite slug if available, otherwise taxonomy name
                    if ($taxonomy && isset($taxonomy->rewrite['slug'])) {
                        return $taxonomy->rewrite['slug'];
                    }
                    return $queried->taxonomy;
                }
            }
            return '';
            
        case 'user':
            if (is_user_logged_in()) {
                $user = wp_get_current_user();
                return $user ? $user->user_nicename : '';
            }
            return '';
            
        case 'page':
            // On page, return page slug
            if (is_page()) {
                $page = get_post();
                return $page ? $page->post_name : '';
            }
            // On front page
            if (is_front_page() && get_option('show_on_front') === 'page') {
                $page_id = get_option('page_on_front');
                if ($page_id) {
                    $page = get_post($page_id);
                    return $page ? $page->post_name : '';
                }
            }
            // In loop on page
            if (in_the_loop() && is_page()) {
                $page = get_post();
                return $page ? $page->post_name : '';
            }
            return '';
            
        case 'archive':
            // On any archive (including author, category, tag, date, custom post type, custom taxonomy)
            if (is_archive()) {
                $queried = get_queried_object();
                // Check if it's a term
                if (isset($queried->slug)) {
                    return $queried->slug;
                }
                // Check if it's an author
                if (isset($queried->user_nicename)) {
                    return $queried->user_nicename;
                }
                // Check if it's a post type archive
                if (is_post_type_archive()) {
                    $post_type = get_query_var('post_type');
                    $post_type = is_array($post_type) ? reset($post_type) : $post_type;
                    $post_type_obj = get_post_type_object($post_type);
                    if ($post_type_obj && isset($post_type_obj->rewrite['slug'])) {
                        return $post_type_obj->rewrite['slug'];
                    }
                    return $post_type;
                }
            }
            return '';
            
        case 'search':
            if (is_search()) {
                $query = get_search_query();
                return sanitize_title($query);
            }
            return '';
            
        case 'post_type':
            // Get current post type slug
            if (is_singular()) {
                $post_type = get_post_type();
                $post_type_obj = get_post_type_object($post_type);
                if ($post_type_obj && isset($post_type_obj->rewrite['slug'])) {
                    return $post_type_obj->rewrite['slug'];
                }
                return $post_type;
            }
            if (is_post_type_archive()) {
                $pt = get_query_var('post_type');
                $pt = is_array($pt) ? reset($pt) : $pt;
                $post_type_obj = get_post_type_object($pt);
                if ($post_type_obj && isset($post_type_obj->rewrite['slug'])) {
                    return $post_type_obj->rewrite['slug'];
                }
                return $pt;
            }
            if (in_the_loop()) {
                $post_type = get_post_type();
                $post_type_obj = get_post_type_object($post_type);
                if ($post_type_obj && isset($post_type_obj->rewrite['slug'])) {
                    return $post_type_obj->rewrite['slug'];
                }
                return $post_type;
            }
            return '';
            
        case 'post_type_archive':
            // Get post type archive slug
            if (is_post_type_archive()) {
                $post_type = get_query_var('post_type');
                $post_type = is_array($post_type) ? reset($post_type) : $post_type;
                $post_type_obj = get_post_type_object($post_type);
                if ($post_type_obj && isset($post_type_obj->rewrite['slug'])) {
                    return $post_type_obj->rewrite['slug'];
                }
                return $post_type;
            }
            return '';
            
        case 'parent':
            // Get parent post/page slug
            if (is_singular()) {
                $post = get_post();
                if ($post && $post->post_parent) {
                    $parent = get_post($post->post_parent);
                    return $parent ? $parent->post_name : '';
                }
            }
            return '';
            
        case 'site':
            // Multisite support - get blog slug
            if (is_multisite()) {
                $blog_details = get_blog_details();
                return $blog_details ? trim($blog_details->path, '/') : '';
            }
            // For single site, return site name as slug
            return sanitize_title(get_bloginfo('name'));
            
        default:
            return '';
    }
}

// Step 3: Render the dynamic tag in Bricks Builder.
add_filter('bricks/dynamic_data/render_tag', 'render_get_contextual_slug_tag', 20, 3);
function render_get_contextual_slug_tag($tag, $post, $context = 'text') {
    // Ensure that $tag is a string before processing.
    if (is_string($tag) && strpos($tag, '{get_contextual_slug:') === 0) {
        // Extract the type from the tag.
        $type = trim(str_replace(['{get_contextual_slug:', '}'], '', $tag));
        return get_contextual_slug($type);
    }

    // If $tag is an array, iterate through and process each element.
    if (is_array($tag)) {
        foreach ($tag as $key => $value) {
            if (is_string($value) && strpos($value, '{get_contextual_slug:') === 0) {
                $type = trim(str_replace(['{get_contextual_slug:', '}'], '', $value));
                $tag[$key] = get_contextual_slug($type);
            }
        }
        return $tag;
    }

    // Return the original tag if it doesn't match the expected pattern.
    return $tag;
}

// Step 4: Replace placeholders in dynamic content dynamically.
add_filter('bricks/dynamic_data/render_content', 'replace_get_contextual_slug_in_content', 20, 3);
add_filter('bricks/frontend/render_data', 'replace_get_contextual_slug_in_content', 20, 2);
function replace_get_contextual_slug_in_content($content, $post, $context = 'text') {
    if (!is_string($content)) {
        return $content;
    }

    preg_match_all('/{get_contextual_slug:([^}]+)}/', $content, $matches);
    if (!empty($matches[1])) {
        foreach ($matches[1] as $type) {
            $slug = get_contextual_slug($type);
            $content = str_replace("{get_contextual_slug:$type}", $slug, $content);
        }
    }
    return $content;
}
