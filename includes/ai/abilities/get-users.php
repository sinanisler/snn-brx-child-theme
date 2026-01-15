<?php
/**
 * Get Users Ability
 * Registers the snn/get-users ability for the WordPress Abilities API
 */

// Register ability
add_action( 'wp_abilities_api_init', 'snn_register_get_users_ability' );
function snn_register_get_users_ability() {
    wp_register_ability(
        'snn/get-users',
        array(
            'label'       => __( 'Get Users', 'wp-abilities' ),
            'description' => __( 'Retrieves WordPress users with complete details including user ID, username (login), display name, email address, assigned roles (administrator/editor/author/contributor/subscriber), registration date/time, and total authored post count. Can filter by specific role and limit results (max 100 for performance). Use this when you need user lists, want to find users by role, need email addresses for notifications, analyze author activity, check user registrations, or export user data. Returns all public user information.', 'wp-abilities' ),
            'category'    => 'users',
            'input_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'number' => array(
                        'type'        => 'integer',
                        'description' => 'Number of users to retrieve (max 100 for performance). Omit parameter or use default to get first 10.',
                        'default'     => 10,
                        'minimum'     => 1,
                        'maximum'     => 100,
                    ),
                    'role' => array(
                        'type'        => 'string',
                        'description' => 'Filter by user role (e.g., administrator, editor, author).',
                    ),
                ),
            ),
            'output_schema' => array(
                'type'        => 'array',
                'description' => 'Array of user objects with complete user information.',
                'items'       => array(
                    'type'       => 'object',
                    'properties' => array(
                        'id' => array(
                            'type'        => 'integer',
                            'description' => 'The user ID.',
                        ),
                        'username' => array(
                            'type'        => 'string',
                            'description' => 'The user login/username.',
                        ),
                        'display_name' => array(
                            'type'        => 'string',
                            'description' => 'The user display name.',
                        ),
                        'email' => array(
                            'type'        => 'string',
                            'description' => 'The user email address.',
                        ),
                        'roles' => array(
                            'type'        => 'array',
                            'description' => 'Array of user roles (e.g., administrator, editor, author, subscriber).',
                        ),
                        'registered' => array(
                            'type'        => 'string',
                            'description' => 'The date and time the user registered.',
                        ),
                        'post_count' => array(
                            'type'        => 'integer',
                            'description' => 'Total number of posts authored by the user.',
                        ),
                    ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $number = isset( $input['number'] ) ? absint( $input['number'] ) : 10;
                // Cap at 100 for performance on large sites
                $args = array(
                    'number' => min( $number, 100 ),
                );

                if ( ! empty( $input['role'] ) ) {
                    $args['role'] = sanitize_text_field( $input['role'] );
                }

                $users = get_users( $args );
                $result = array();

                foreach ( $users as $user ) {
                    $result[] = array(
                        'id'           => $user->ID,
                        'username'     => $user->user_login,
                        'display_name' => $user->display_name,
                        'email'        => $user->user_email,
                        'roles'        => $user->roles,
                        'registered'   => $user->user_registered,
                        'post_count'   => count_user_posts( $user->ID ),
                    );
                }

                return $result;
            },
            'permission_callback' => function() {
                return current_user_can( 'list_users' );
            },
            'meta' => array(
                'show_in_rest' => true,
                'readonly'     => true,
                'destructive'  => false,
                'idempotent'   => true,
            ),
        )
    );
}
