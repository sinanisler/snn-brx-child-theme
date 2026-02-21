<?php
/**
 * ----------------------------------------
 * Parent Post Count Dynamic Tag Module
 * ----------------------------------------
 * Usage:
 *   {parent_post_count:post_type}
 *
 * Returns the count of published root-level (no parent) posts of the given post type.
 * ----------------------------------------
 */

add_filter('bricks/dynamic_tags_list', 'add_parent_post_count_tags_to_builder');
function add_parent_post_count_tags_to_builder($tags) {
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

function get_parent_post_count($post_type = 'post') {
    global $wpdb;
    return (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_parent = 0 AND post_status = 'publish'",
        $post_type
    ));
}

add_filter('bricks/dynamic_data/render_tag', 'render_parent_post_count_tag', 20, 3);
function render_parent_post_count_tag($tag, $post, $context = 'text') {
    if (!is_string($tag) || strpos($tag, '{parent_post_count:') !== 0) {
        return $tag;
    }
    if (preg_match('/{parent_post_count:([^}]+)}/', $tag, $matches)) {
        return get_parent_post_count(trim($matches[1]));
    }
    return $tag;
}

add_filter('bricks/dynamic_data/render_content', 'replace_parent_post_count_in_content', 20, 3);
add_filter('bricks/frontend/render_data', 'replace_parent_post_count_in_content', 20, 2);
function replace_parent_post_count_in_content($content, $post, $context = 'text') {
    if (!is_string($content) || strpos($content, '{parent_post_count:') === false) {
        return $content;
    }
    preg_match_all('/{parent_post_count:([^}]+)}/', $content, $matches);
    foreach ($matches[0] as $i => $full_match) {
        $content = str_replace($full_match, get_parent_post_count(trim($matches[1][$i])), $content);
    }
    return $content;
}
