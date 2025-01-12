<?php
/**
 * ----------------------------------------
 * Dynamic Post Term Count Tags
 * ----------------------------------------
 * Usage:
 *  1) {post_term_count:post_type}
 *     - e.g. {post_term_count:post}, {post_term_count:page}, {post_term_count:product}, etc.
 *
 *  2) {post_term_count:post_type:taxonomy:term_slug}
 *     - e.g. {post_term_count:product:product_cat:shoes}
 *
 * Description:
 * Displays the count of published posts for the specified post type. Optionally
 * filter the count by taxonomy and a specific term slug.
 * ----------------------------------------
 */

// Step 1: Register multiple dynamic tags for every public post type and its taxonomies.
add_filter('bricks/dynamic_tags_list', 'add_post_term_count_tags_to_builder');
function add_post_term_count_tags_to_builder($tags) {
    // Get all public post types.
    $post_types = get_post_types(['public' => true], 'objects');

    foreach ($post_types as $post_type) {
        // 1. Tag for the post type alone (no taxonomy filter).
        $tags[] = [
            'name'  => "{post_term_count:{$post_type->name}}",
            'label' => "Count of {$post_type->label}",
            'group' => 'SNN',
        ];

        // 2. Tags for each taxonomy/term combination associated with this post type.
        $taxonomies = get_object_taxonomies($post_type->name, 'objects');
        foreach ($taxonomies as $taxonomy) {
            // Get all terms for this taxonomy.
            $terms = get_terms([
                'taxonomy'   => $taxonomy->name,
                'hide_empty' => false,
            ]);

            foreach ($terms as $term) {
                // Example: {post_term_count:product:product_cat:shoes}
                $tags[] = [
                    'name'  => "{post_term_count:{$post_type->name}:{$taxonomy->name}:{$term->slug}}",
                    'label' => "Count of {$post_type->label} in {$taxonomy->label} => {$term->name}",
                    'group' => 'SNN',
                ];
            }
        }
    }

    return $tags;
}

// Step 2: Define a helper function to retrieve the count of posts.
function get_post_term_count($post_type, $taxonomy = '', $term_slug = '') {
    // If taxonomy and term_slug are provided, we get the term object.
    if (!empty($taxonomy) && !empty($term_slug)) {
        $term = get_term_by('slug', $term_slug, $taxonomy);
        if ($term && !is_wp_error($term)) {
            // Use the term's own stored count if you trust it to be up to date.
            // Otherwise, you might do a custom query for more accurate filtering.
            return intval($term->count);
        }
    }

    // Otherwise, just return the number of published posts for the post type.
    $count = wp_count_posts($post_type);
    return isset($count->publish) ? intval($count->publish) : 0;
}

// Step 3: Render each dynamic tag when Bricks encounters it in a field.
add_filter('bricks/dynamic_data/render_tag', 'render_post_term_count_tag', 20, 3);
function render_post_term_count_tag($tag, $post, $context = 'text') {
    if (strpos($tag, '{post_term_count:') === 0) {
        // Example $tag = {post_term_count:product:product_cat:shoes}
        $parts     = explode(':', trim($tag, '{}')); // ['post_term_count','product','product_cat','shoes']
        $post_type = $parts[1] ?? '';
        $taxonomy  = $parts[2] ?? '';
        $term_slug = $parts[3] ?? '';

        return get_post_term_count($post_type, $taxonomy, $term_slug);
    }
    return $tag;
}

// Step 4: Replace placeholders inside dynamic content (like in Rich Text, etc.).
add_filter('bricks/dynamic_data/render_content', 'replace_post_term_count_in_content', 20, 3);
add_filter('bricks/frontend/render_data', 'replace_post_term_count_in_content', 20, 2);
function replace_post_term_count_in_content($content, $post, $context = 'text') {
    // Pattern: {post_term_count:post_type} or {post_term_count:post_type:taxonomy:term_slug}
    if (preg_match_all('/\{post_term_count:([\w-]+)(?::([\w-]+):([\w-]+))?\}/', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            // $match[0] => The entire match, e.g. {post_term_count:product:product_cat:shoes}
            // $match[1] => post_type, e.g. 'product'
            // $match[2] => taxonomy, e.g. 'product_cat'
            // $match[3] => term_slug, e.g. 'shoes'
            $post_type = sanitize_key($match[1]);
            $taxonomy  = isset($match[2]) ? sanitize_key($match[2]) : '';
            $term_slug = isset($match[3]) ? sanitize_key($match[3]) : '';

            $post_count = get_post_term_count($post_type, $taxonomy, $term_slug);
            $content    = str_replace($match[0], $post_count, $content);
        }
    }
    return $content;
}
