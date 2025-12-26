<?php
/**
 * ----------------------------------------
 * Dynamic Content Tag Module
 * ----------------------------------------
 * Usage: {get_contextual_content:type} or {get_contextual_content:taxonomy_slug:field}
 * 
 * Supported Types and Outputs:
 * 
 * POST/PAGE CONTENT:
 * - title: Current post title
 * - content: Current post content
 * - excerpt: Current post excerpt
 * - author: Current post author display name
 * - author_email: Current post author email
 * - author_url: Current post author website URL
 * - date: Current post publish date
 * - modified: Current post last modified date
 * - featured_image: Current post featured image URL
 * - permalink: Current post permalink
 * - slug: Current post slug
 * - type: Current post type label
 * - status: Current post status
 * - [custom_field_name]: Any custom field value
 * 
 * TAXONOMY CONTENT (when on single post/page):
 * - {taxonomy_slug}:name - Get taxonomy terms names (comma separated)
 * - {taxonomy_slug}:desc - Get taxonomy terms descriptions (comma separated)
 * - {taxonomy_slug}:slug - Get taxonomy terms slugs (comma separated)
 * - {taxonomy_slug}:ids - Get taxonomy terms IDs (comma separated)
 * - {taxonomy_slug}:count - Get number of terms assigned
 * - {taxonomy_slug}:{custom_field} - Get custom field from first assigned term
 * 
 * TAXONOMY ARCHIVE CONTENT (when on taxonomy archive):
 * - archive_term:name - Current term name
 * - archive_term:desc - Current term description
 * - archive_term:slug - Current term slug
 * - archive_term:count - Current term post count
 * - archive_term:{custom_field} - Current term custom field value
 * ----------------------------------------
 */

// Step 1: Register the dynamic tags with Bricks Builder.
add_filter('bricks/dynamic_tags_list', 'add_get_contextual_content_tags_to_builder');
function add_get_contextual_content_tags_to_builder($tags) {
    // Post/Page content tags
    $content_types = [
        'title'           => 'Current Post Title',
        'content'         => 'Current Post Content',
        'excerpt'         => 'Current Post Excerpt',
        'author'          => 'Current Post Author Name',
        'author_email'    => 'Current Post Author Email',
        'author_url'      => 'Current Post Author URL',
        'date'            => 'Current Post Date',
        'modified'        => 'Current Post Modified Date',
        'featured_image'  => 'Current Post Featured Image URL',
        'permalink'       => 'Current Post Permalink',
        'slug'            => 'Current Post Slug',
        'type'            => 'Current Post Type',
        'status'          => 'Current Post Status',
    ];

    foreach ($content_types as $type => $label) {
        $tags[] = [
            'name'  => "{get_contextual_content:$type}",
            'label' => $label,
            'group' => 'SNN',
        ];
    }

    // Archive term tags
    $archive_types = [
        'archive_term:name'   => 'Current Term Name',
        'archive_term:desc'   => 'Current Term Description',
        'archive_term:slug'   => 'Current Term Slug',
        'archive_term:count'  => 'Current Term Count',
    ];

    foreach ($archive_types as $type => $label) {
        $tags[] = [
            'name'  => "{get_contextual_content:$type}",
            'label' => $label,
            'group' => 'SNN',
        ];
    }

    return $tags;
}

// Step 2: Get the current post ID with various fallbacks
function get_contextual_content_post_id() {
    // Try to get current post ID
    $post_id = get_the_ID();
    
    if (!$post_id && in_the_loop()) {
        global $post;
        $post_id = isset($post->ID) ? $post->ID : 0;
    }
    
    // Try queried object if singular
    if (!$post_id && is_singular()) {
        $post_id = get_queried_object_id();
    }
    
    return $post_id;
}

// Step 3: Fetch the appropriate content based on the type.
function get_contextual_content($params) {
    // Split parameters by colon
    $parts = explode(':', $params);
    $type = isset($parts[0]) ? trim($parts[0]) : '';
    $subtype = isset($parts[1]) ? trim($parts[1]) : '';
    
    // Handle taxonomy archive content
    if ($type === 'archive_term') {
        return get_contextual_archive_term_content($subtype);
    }
    
    // Check if this is a taxonomy request (has two parts)
    if ($subtype && $type !== 'archive_term') {
        return get_contextual_taxonomy_content($type, $subtype);
    }
    
    // Get current post ID
    $post_id = get_contextual_content_post_id();
    
    if (!$post_id) {
        return '';
    }
    
    // Handle post content requests
    switch ($type) {
        case 'title':
            return get_the_title($post_id);
            
        case 'content':
            $post = get_post($post_id);
            return $post ? apply_filters('the_content', $post->post_content) : '';
            
        case 'excerpt':
            return get_the_excerpt($post_id);
            
        case 'author':
            $author_id = get_post_field('post_author', $post_id);
            return get_the_author_meta('display_name', $author_id);
            
        case 'author_email':
            $author_id = get_post_field('post_author', $post_id);
            return get_the_author_meta('user_email', $author_id);
            
        case 'author_url':
            $author_id = get_post_field('post_author', $post_id);
            return get_the_author_meta('user_url', $author_id);
            
        case 'date':
            return get_the_date('', $post_id);
            
        case 'modified':
            return get_the_modified_date('', $post_id);
            
        case 'featured_image':
            return get_the_post_thumbnail_url($post_id, 'full');
            
        case 'permalink':
            return get_permalink($post_id);
            
        case 'slug':
            $post = get_post($post_id);
            return $post ? $post->post_name : '';
            
        case 'type':
            $post_type = get_post_type($post_id);
            $post_type_obj = get_post_type_object($post_type);
            return $post_type_obj ? $post_type_obj->labels->singular_name : $post_type;
            
        case 'status':
            return get_post_status($post_id);
            
        default:
            // Try to get as custom field
            $value = get_post_meta($post_id, $type, true);
            return $value !== '' ? $value : '';
    }
}

// Get taxonomy content from current post
function get_contextual_taxonomy_content($taxonomy_slug, $field) {
    $post_id = get_contextual_content_post_id();
    
    if (!$post_id) {
        return '';
    }
    
    // Get terms assigned to this post
    $terms = get_the_terms($post_id, $taxonomy_slug);
    
    if (!$terms || is_wp_error($terms)) {
        return '';
    }
    
    // Handle different field types
    switch ($field) {
        case 'name':
            $names = array_map(function($term) { return $term->name; }, $terms);
            return implode(', ', $names);
            
        case 'desc':
        case 'description':
            $descriptions = array_filter(array_map(function($term) { 
                return $term->description; 
            }, $terms));
            return implode(', ', $descriptions);
            
        case 'slug':
            $slugs = array_map(function($term) { return $term->slug; }, $terms);
            return implode(', ', $slugs);
            
        case 'ids':
            $ids = array_map(function($term) { return $term->term_id; }, $terms);
            return implode(', ', $ids);
            
        case 'count':
            return count($terms);
            
        default:
            // Try to get term meta from first term
            $first_term = reset($terms);
            if ($first_term) {
                $value = get_term_meta($first_term->term_id, $field, true);
                return $value !== '' ? $value : '';
            }
            return '';
    }
}

// Get content from current taxonomy archive term
function get_contextual_archive_term_content($field) {
    // Check if we're on a taxonomy archive
    if (!is_category() && !is_tag() && !is_tax()) {
        return '';
    }
    
    $term = get_queried_object();
    
    if (!$term || !isset($term->term_id)) {
        return '';
    }
    
    switch ($field) {
        case 'name':
            return $term->name;
            
        case 'desc':
        case 'description':
            return $term->description;
            
        case 'slug':
            return $term->slug;
            
        case 'count':
            return isset($term->count) ? $term->count : 0;
            
        case 'id':
            return $term->term_id;
            
        default:
            // Try to get term meta
            $value = get_term_meta($term->term_id, $field, true);
            return $value !== '' ? $value : '';
    }
}

// Step 4: Render the dynamic tag in Bricks Builder.
add_filter('bricks/dynamic_data/render_tag', 'render_get_contextual_content_tag', 20, 3);
function render_get_contextual_content_tag($tag, $post, $context = 'text') {
    // Ensure that $tag is a string before processing.
    if (is_string($tag) && strpos($tag, '{get_contextual_content:') === 0) {
        // Extract the parameters from the tag.
        $params = trim(str_replace(['{get_contextual_content:', '}'], '', $tag));
        return get_contextual_content($params);
    }

    // If $tag is an array, iterate through and process each element.
    if (is_array($tag)) {
        foreach ($tag as $key => $value) {
            if (is_string($value) && strpos($value, '{get_contextual_content:') === 0) {
                $params = trim(str_replace(['{get_contextual_content:', '}'], '', $value));
                $tag[$key] = get_contextual_content($params);
            }
        }
        return $tag;
    }

    // Return the original tag if it doesn't match the expected pattern.
    return $tag;
}

// Step 5: Replace placeholders in dynamic content dynamically.
add_filter('bricks/dynamic_data/render_content', 'replace_get_contextual_content_in_content', 20, 3);
add_filter('bricks/frontend/render_data', 'replace_get_contextual_content_in_content', 20, 2);
function replace_get_contextual_content_in_content($content, $post, $context = 'text') {
    if (!is_string($content)) {
        return $content;
    }

    preg_match_all('/{get_contextual_content:([^}]+)}/', $content, $matches);
    if (!empty($matches[1])) {
        foreach ($matches[1] as $params) {
            $value = get_contextual_content($params);
            $content = str_replace("{get_contextual_content:$params}", $value, $content);
        }
    }
    return $content;
}
