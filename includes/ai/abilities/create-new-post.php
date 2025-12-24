<?php
/**
 * Ability: Create New Post
 *
 * Creates a new WordPress post using WordPress Abilities API
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_abilities_api_init', 'snn_register_create_post_ability');

function snn_register_create_post_ability() {
    wp_register_ability(
        'snn/create-post',
        [
            'label' => __('Create Post', 'snn'),
            'description' => __('Creates a new WordPress post with the given title and content.', 'snn'),
            'category' => 'content-management',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'title' => [
                        'type' => 'string',
                        'description' => __('The title of the post.', 'snn'),
                        'minLength' => 1,
                    ],
                    'content' => [
                        'type' => 'string',
                        'description' => __('The content of the post (can include HTML).', 'snn'),
                    ],
                    'status' => [
                        'type' => 'string',
                        'enum' => ['draft', 'publish', 'pending'],
                        'description' => __('The post status. Default is draft.', 'snn'),
                        'default' => 'draft',
                    ],
                    'post_type' => [
                        'type' => 'string',
                        'description' => __('The post type. Default is post.', 'snn'),
                        'default' => 'post',
                    ],
                ],
                'required' => ['title', 'content'],
            ],
            'output_schema' => [
                'type' => 'object',
                'properties' => [
                    'success' => [
                        'type' => 'boolean',
                        'description' => __('Whether the post was created successfully.', 'snn'),
                    ],
                    'post_id' => [
                        'type' => 'integer',
                        'description' => __('The ID of the created post.', 'snn'),
                    ],
                    'edit_url' => [
                        'type' => 'string',
                        'description' => __('The URL to edit the post.', 'snn'),
                    ],
                    'view_url' => [
                        'type' => 'string',
                        'description' => __('The URL to view the post.', 'snn'),
                    ],
                ],
            ],
            'execute_callback' => 'snn_execute_create_post',
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            },
            'meta' => [
                'show_in_rest' => true,
            ],
        ]
    );
}

function snn_execute_create_post($input) {
    $post_data = [
        'post_title' => sanitize_text_field($input['title']),
        'post_content' => wp_kses_post($input['content']),
        'post_status' => $input['status'] ?? 'draft',
        'post_type' => $input['post_type'] ?? 'post',
    ];

    $post_id = wp_insert_post($post_data);

    if (is_wp_error($post_id)) {
        return $post_id;
    }

    return [
        'success' => true,
        'post_id' => $post_id,
        'edit_url' => get_edit_post_link($post_id, 'raw'),
        'view_url' => get_permalink($post_id),
    ];
}
