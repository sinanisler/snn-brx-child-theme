<?php
/**
 * Ability: Get User Count
 *
 * Retrieves total user count using WordPress Abilities API
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_abilities_api_init', 'snn_register_user_count_ability');

function snn_register_user_count_ability() {
    wp_register_ability(
        'snn/get-user-count',
        [
            'label' => __('Get User Count', 'snn'),
            'description' => __('Retrieves the total number of users on the site.', 'snn'),
            'category' => 'user-management',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'role' => [
                        'type' => 'string',
                        'description' => __('Optional: Filter by user role (e.g., administrator, editor, author).', 'snn'),
                    ],
                ],
            ],
            'output_schema' => [
                'type' => 'object',
                'properties' => [
                    'total_users' => [
                        'type' => 'integer',
                        'description' => __('The total number of users.', 'snn'),
                    ],
                    'role' => [
                        'type' => 'string',
                        'description' => __('The role that was filtered (if any).', 'snn'),
                    ],
                ],
            ],
            'execute_callback' => 'snn_execute_get_user_count',
            'permission_callback' => function() {
                return current_user_can('list_users');
            },
            'meta' => [
                'show_in_rest' => true,
            ],
        ]
    );
}

function snn_execute_get_user_count($input) {
    $role = $input['role'] ?? null;

    $args = [];
    if ($role) {
        $args['role'] = $role;
    }

    $user_query = new WP_User_Query($args);
    $total = $user_query->get_total();

    return [
        'total_users' => (int) $total,
        'role' => $role,
    ];
}
