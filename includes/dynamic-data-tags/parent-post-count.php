<?php
/**
 * ----------------------------------------
 * Parent Post Count Dynamic Tag Module
 * ----------------------------------------
 * Usage:
 *   {parent_post_count}
 *   {parent_post_count:post_type}
 *
 * Supported Properties and Outputs:
 * - (default)   : Count of ancestor posts (same post type as current post)
 * - post_type   : Count of ancestor posts of the specified post type
 *
 * Works inside and outside Bricks query loops (falls back to queried object on single views).
 * ----------------------------------------
 */

// Step 1: Register the dynamic tags with Bricks Builder.
add_filter('bricks/dynamic_tags_list', 'add_parent_post_count_tags_to_builder');
function add_parent_post_count_tags_to_builder($tags) {
    $tags[] = [
        'name'  => '{parent_post_count}',
        'label' => 'Parent Post Count',
        'group' => 'SNN',
    ];

    $post_types = get_post_types(['public' => true], 'objects');
    foreach ($post_types as $pt) {
        $tags[] = [
            'name'  => "{parent_post_count:{$pt->name}}",
            'label' => "Parent Post Count: {$pt->label}",
            'group' => 'SNN',
        ];
    }

    return $tags;
}

// Step 2: Resolve the post ID from multiple sources.
function snn_ppc_resolve_post_id($post = null) {
    // From Bricks $post param (object or int)
    if ($post instanceof WP_Post) {
        return $post->ID;
    }
    if (is_int($post) && $post > 0) {
        return $post;
    }

    // From the loop
    $id = get_the_ID();
    if ($id) {
        return $id;
    }

    // Fallback: queried object on single views
    $queried = get_queried_object();
    if ($queried instanceof WP_Post) {
        return $queried->ID;
    }

    return 0;
}

// Step 3: Count ancestor posts by walking up the post_parent chain.
function get_parent_post_count($post_type_override = '', $post = null) {
    $post_id = snn_ppc_resolve_post_id($post);

    if (!$post_id) {
        return 0;
    }

    $current_post = get_post($post_id);
    if (!$current_post) {
        return 0;
    }

    $query_post_type = !empty($post_type_override) ? $post_type_override : $current_post->post_type;
    $count           = 0;
    $parent_id       = $current_post->post_parent;

    while ($parent_id) {
        $parent = get_post($parent_id);
        if (!$parent) {
            break;
        }
        if ($parent->post_type === $query_post_type) {
            $count++;
        }
        $parent_id = $parent->post_parent;
    }

    return $count;
}

// Step 4: Render the dynamic tag in Bricks Builder.
add_filter('bricks/dynamic_data/render_tag', 'render_parent_post_count_tag', 20, 3);
function render_parent_post_count_tag($tag, $post, $context = 'text') {
    if (is_string($tag)) {
        if (strpos($tag, '{parent_post_count') === 0) {
            if (preg_match('/{parent_post_count:([^}]+)}/', $tag, $matches)) {
                return get_parent_post_count(trim($matches[1]), $post);
            } elseif ($tag === '{parent_post_count}') {
                return get_parent_post_count('', $post);
            }
        }
    }

    if (is_array($tag)) {
        foreach ($tag as $key => $value) {
            if (is_string($value) && strpos($value, '{parent_post_count') === 0) {
                if (preg_match('/{parent_post_count:([^}]+)}/', $value, $matches)) {
                    $tag[$key] = get_parent_post_count(trim($matches[1]), $post);
                } elseif ($value === '{parent_post_count}') {
                    $tag[$key] = get_parent_post_count('', $post);
                }
            }
        }
        return $tag;
    }

    return $tag;
}

// Step 5: Replace placeholders in dynamic content.
add_filter('bricks/dynamic_data/render_content', 'replace_parent_post_count_in_content', 20, 3);
add_filter('bricks/frontend/render_data', 'replace_parent_post_count_in_content', 20, 2);
function replace_parent_post_count_in_content($content, $post, $context = 'text') {
    if (!is_string($content)) {
        return $content;
    }

    preg_match_all('/{parent_post_count(?::([^}]+))?}/', $content, $matches);

    if (!empty($matches[0])) {
        foreach ($matches[0] as $index => $full_match) {
            $property = isset($matches[1][$index]) && $matches[1][$index] ? $matches[1][$index] : '';
            $content  = str_replace($full_match, get_parent_post_count($property, $post), $content);
        }
    }

    return $content;
}
