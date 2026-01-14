<?php 
/**
 * Get Users Ability
 * Registers the snn/get-users ability for the WordPress Abilities API
 */

// Register category
add_action( 'wp_abilities_api_categories_init', 'snn_register_users_category' );
function snn_register_users_category() {
    // Only register if not already registered
    if ( ! wp_has_ability_category( 'users' ) ) {
        wp_register_ability_category(
            'users',
            array(
                'label'       => __( 'User Management', 'snn' ),
                'description' => __( 'Abilities for managing users and user data.', 'snn' ),
            )
        );
    }
}

// Register ability
add_action( 'wp_abilities_api_init', 'snn_register_get_users_ability' );
function snn_register_get_users_ability() {
    wp_register_ability(
        'snn/get-users',
        array(
            'label'       => __( 'Get Users', 'wp-abilities' ),
            'description' => __( 'Retrieves a list of users.', 'wp-abilities' ),
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
                'type'  => 'array',
                'items' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'id'           => array( 'type' => 'integer' ),
                        'username'     => array( 'type' => 'string' ),
                        'display_name' => array( 'type' => 'string' ),
                        'email'        => array( 'type' => 'string' ),
                        'roles'        => array( 'type' => 'array' ),
                        'registered'   => array( 'type' => 'string' ),
                        'post_count'   => array( 'type' => 'integer' ),
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
