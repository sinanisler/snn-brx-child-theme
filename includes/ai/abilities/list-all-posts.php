<?php
/**
 * Ability: List All Posts
 *
 * Lists all posts with their details using WordPress Abilities API
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_abilities_api_init', 'snn_register_list_posts_ability');

function snn_register_list_posts_ability() {
    wp_register_ability(
        'snn/list-posts',
        [
            'label' => __('List Posts', 'snn'),
            'description' => __('Retrieves a list of all posts with their details including ID, title, status, author, and date.', 'snn'),
            'category' => 'content-management',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'post_type' => [
                        'type' => 'string',
                        'description' => __('The post type to list. Defaults to "post".', 'snn'),
                        'default' => 'post',
                    ],
                    'post_status' => [
                        'type' => 'string',
                        'description' => __('The post status to filter by. Defaults to "any".', 'snn'),
                        'default' => 'any',
                    ],
                    'posts_per_page' => [
                        'type' => 'integer',
                        'description' => __('Number of posts to retrieve. Use -1 for all posts. Defaults to -1.', 'snn'),
                        'default' => -1,
                    ],
                ],
                'additionalProperties' => false,
            ],
            'output_schema' => [
                'type' => 'object',
                'properties' => [
                    'total' => [
                        'type' => 'integer',
                        'description' => __('Total number of posts found.', 'snn'),
                    ],
                    'posts' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => [
                                    'type' => 'integer',
                                    'description' => __('Post ID.', 'snn'),
                                ],
                                'title' => [
                                    'type' => 'string',
                                    'description' => __('Post title.', 'snn'),
                                ],
                                'status' => [
                                    'type' => 'string',
                                    'description' => __('Post status.', 'snn'),
                                ],
                                'author' => [
                                    'type' => 'string',
                                    'description' => __('Post author display name.', 'snn'),
                                ],
                                'date' => [
                                    'type' => 'string',
                                    'description' => __('Post publication date.', 'snn'),
                                ],
                            ],
                        ],
                        'description' => __('Array of post objects.', 'snn'),
                    ],
                ],
            ],
            'execute_callback' => 'snn_execute_list_posts',
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

function snn_execute_list_posts($input) {
    $post_type = $input['post_type'] ?? 'post';
    $post_status = $input['post_status'] ?? 'any';
    $posts_per_page = $input['posts_per_page'] ?? -1;

    if (!post_type_exists($post_type)) {
        return new WP_Error(
            'invalid_post_type',
            sprintf(__('Post type "%s" does not exist.', 'snn'), $post_type)
        );
    }

    $args = [
        'post_type' => $post_type,
        'post_status' => $post_status,
        'posts_per_page' => $posts_per_page,
        'orderby' => 'date',
        'order' => 'DESC',
    ];

    $query = new WP_Query($args);
    $posts = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $author_id = get_the_author_meta('ID');
            $author_name = get_the_author_meta('display_name');

            $posts[] = [
                'id' => $post_id,
                'title' => get_the_title(),
                'status' => get_post_status(),
                'author' => $author_name,
                'date' => get_the_date('Y-m-d H:i:s'),
            ];
        }
        wp_reset_postdata();
    }

    return [
        'total' => $query->found_posts,
        'posts' => $posts,
    ];
}
