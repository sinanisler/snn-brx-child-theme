<?php
/**
 * Ability: List All Post Types
 *
 * Lists all registered post types using WordPress Abilities API
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_abilities_api_init', 'snn_register_list_post_types_ability');

function snn_register_list_post_types_ability() {
    wp_register_ability(
        'snn/list-post-types',
        [
            'label' => __('List Post Types', 'snn'),
            'description' => __('Retrieves a list of all registered post types on the site.', 'snn'),
            'category' => 'content-management',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'public_only' => [
                        'type' => 'boolean',
                        'description' => __('Whether to list only public post types. Defaults to false.', 'snn'),
                        'default' => false,
                    ],
                ],
                'additionalProperties' => false,
            ],
            'output_schema' => [
                'type' => 'object',
                'properties' => [
                    'post_types' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'name' => [
                                    'type' => 'string',
                                    'description' => __('Post type name (slug).', 'snn'),
                                ],
                                'label' => [
                                    'type' => 'string',
                                    'description' => __('Post type label.', 'snn'),
                                ],
                                'public' => [
                                    'type' => 'boolean',
                                    'description' => __('Whether the post type is public.', 'snn'),
                                ],
                                'count' => [
                                    'type' => 'integer',
                                    'description' => __('Number of published posts of this type.', 'snn'),
                                ],
                            ],
                        ],
                        'description' => __('Array of post type objects.', 'snn'),
                    ],
                ],
            ],
            'execute_callback' => 'snn_execute_list_post_types',
            'permission_callback' => function() {
                return current_user_can('read');
            },
            'meta' => [
                'show_in_rest' => true,
                'annotations' => [
                    'readonly' => true,
                    'destructive' => false,
                ],
            ],
        ]
    );
}

function snn_execute_list_post_types($input) {
    $public_only = $input['public_only'] ?? false;

    $args = $public_only ? ['public' => true] : [];
    $post_types = get_post_types($args, 'objects');

    $result = [];

    foreach ($post_types as $post_type) {
        $count = wp_count_posts($post_type->name);
        $published_count = isset($count->publish) ? (int) $count->publish : 0;

        $result[] = [
            'name' => $post_type->name,
            'label' => $post_type->label,
            'public' => (bool) $post_type->public,
            'count' => $published_count,
        ];
    }

    return [
        'post_types' => $result,
    ];
}
