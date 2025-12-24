<?php
/**
 * Ability: Get Post Type and Post Count
 *
 * Retrieves post types and their post counts using WordPress Abilities API
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_abilities_api_init', 'snn_register_post_count_ability');

function snn_register_post_count_ability() {
    wp_register_ability(
        'snn/get-post-type-count',
        [
            'label' => __('Get Post Type Count', 'snn'),
            'description' => __('Retrieves the count of published posts for a specified post type.', 'snn'),
            'category' => 'content-management',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'post_type' => [
                        'type' => 'string',
                        'description' => __('The post type to count. Defaults to "post".', 'snn'),
                        'default' => 'post',
                    ],
                ],
            ],
            'output_schema' => [
                'type' => 'object',
                'properties' => [
                    'post_type' => [
                        'type' => 'string',
                        'description' => __('The post type that was counted.', 'snn'),
                    ],
                    'count' => [
                        'type' => 'integer',
                        'description' => __('The number of published posts.', 'snn'),
                    ],
                ],
            ],
            'execute_callback' => 'snn_execute_get_post_type_count',
            'permission_callback' => function() {
                return current_user_can('read');
            },
            'meta' => [
                'show_in_rest' => true,
            ],
        ]
    );
}

function snn_execute_get_post_type_count($input) {
    $post_type = $input['post_type'] ?? 'post';

    if (!post_type_exists($post_type)) {
        return new WP_Error(
            'invalid_post_type',
            sprintf(__('Post type "%s" does not exist.', 'snn'), $post_type)
        );
    }

    $count = wp_count_posts($post_type);

    return [
        'post_type' => $post_type,
        'count' => (int) $count->publish,
    ];
}
