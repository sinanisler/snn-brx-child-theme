<?php
/**
 * ----------------------------------------
 * Dynamic Post Term Count Module
 * ----------------------------------------
 * Usage:
 *  1) {post_term_count:post_type}
 *     - e.g. {post_term_count:post}, {post_term_count:page}, {post_term_count:product}, etc.
 *
 *  2) {post_term_count:taxonomy}
 *     - e.g. {post_term_count:category}, {post_term_count:post_tag}, {post_term_count:product_cat}, etc.
 *
 * Description:
 * Displays the count of published posts for the specified post type or the count of terms in the specified taxonomy.
 * ----------------------------------------
 */

// Step 1: Register the dynamic tags with Bricks Builder.
add_filter('bricks/dynamic_tags_list', 'add_post_term_count_tags_to_builder');
function add_post_term_count_tags_to_builder($tags) {
    // Register tags for all public post types.
    $post_types = get_post_types(['public' => true], 'objects');
    foreach ($post_types as $post_type) {
        $tags[] = [
            'name'  => "{post_term_count:{$post_type->name}}",
            'label' => "Count of {$post_type->label}",
            'group' => 'SNN', // Group name in Bricks Builder.
        ];
    }

    // Register tags for all public taxonomies.
    $taxonomies = get_taxonomies(['public' => true], 'objects');
    foreach ($taxonomies as $taxonomy) {
        $tags[] = [
            'name'  => "{post_term_count:{$taxonomy->name}}",
            'label' => "Count of Terms in {$taxonomy->label}",
            'group' => 'SNN', // Group name in Bricks Builder.
        ];
    }

    return $tags;
}

// Step 2: Define a helper function to retrieve the count based on type.
function get_post_term_count($type) {
    // Check if the type is a post type.
    if (post_type_exists($type)) {
        $count = wp_count_posts($type);
        return isset($count->publish) ? intval($count->publish) : 0;
    }

    // Check if the type is a taxonomy.
    if (taxonomy_exists($type)) {
        $terms = get_terms([
            'taxonomy'   => $type,
            'hide_empty' => false,
            'fields'     => 'ids', // Only retrieve term IDs for performance.
        ]);

        if (!is_wp_error($terms) && is_array($terms)) {
            return count($terms);
        }
    }

    return 0;
}

// Step 3: Render each dynamic tag when Bricks encounters it in a field.
add_filter('bricks/dynamic_data/render_tag', 'render_post_term_count_tag', 20, 3);
function render_post_term_count_tag($tag, $post, $context = 'text') {
    // Ensure that $tag is a string before proceeding.
    if (!is_string($tag)) {
        return $tag;
    }

    // Check if the tag matches the {post_term_count:type} pattern.
    if (strpos($tag, '{post_term_count:') === 0 && substr($tag, -1) === '}') {
        // Extract the type from the tag.
        $type = str_replace(['{post_term_count:', '}'], '', $tag);
        return get_post_term_count($type);
    }

    return $tag;
}

// Step 4: Replace placeholders inside dynamic content (like in Rich Text, etc.).
add_filter('bricks/dynamic_data/render_content', 'replace_post_term_count_in_content', 20, 3);
add_filter('bricks/frontend/render_data', 'replace_post_term_count_in_content', 20, 2);
function replace_post_term_count_in_content($content, $post, $context = 'text') {
    // Pattern: {post_term_count:post_type} or {post_term_count:taxonomy}
    if (preg_match_all('/\{post_term_count:([\w-]+)\}/', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            // $match[0] => The entire match, e.g. {post_term_count:post}
            // $match[1] => type, e.g. 'post'
            $type = sanitize_key($match[1]);
            $count = get_post_term_count($type);
            $content = str_replace($match[0], intval($count), $content);
        }
    }
    return $content;
}
?>
