<?php 
/**
 * Test Ability - Simple ability to test the system
 * 
 * This file provides a simple test ability to verify the Abilities API is working.
 * You can call this ability from the chat overlay to test the connection.
 */

// Hook into the WordPress Abilities API initialization
add_action( 'wp_abilities_api_categories_init', 'snn_register_test_ability_category' );
add_action( 'wp_abilities_api_init', 'snn_register_test_ability' );

/**
 * Register the test ability category
 */
function snn_register_test_ability_category() {
    wp_register_ability_category(
        'testing',
        array(
            'label'       => __( 'Testing', 'snn' ),
            'description' => __( 'Abilities for testing the system.', 'snn' ),
        )
    );
}

/**
 * Register the test ability
 */
function snn_register_test_ability() {
    wp_register_ability(
        'snn/test-connection',
        array(
            'label'       => __( 'Test Connection', 'snn' ),
            'description' => __( 'A simple test to verify the Abilities API is working correctly.', 'snn' ),
            'category'    => 'testing',
            'input_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'message' => array(
                        'type'        => 'string',
                        'description' => 'An optional test message.',
                        'default'     => 'Hello from the test ability!',
                    ),
                ),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'success'  => array(
                        'type'        => 'boolean',
                        'description' => 'Whether the test was successful',
                    ),
                    'message'  => array(
                        'type'        => 'string',
                        'description' => 'Test response message',
                    ),
                    'timestamp' => array(
                        'type'        => 'string',
                        'description' => 'Current timestamp',
                    ),
                    'user_info' => array(
                        'type'        => 'object',
                        'description' => 'Information about the current user',
                    ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $message = isset( $input['message'] ) ? sanitize_text_field( $input['message'] ) : 'Hello from the test ability!';
                
                $current_user = wp_get_current_user();
                
                return array(
                    'success'   => true,
                    'message'   => $message,
                    'timestamp' => current_time( 'mysql' ),
                    'user_info' => array(
                        'id'    => $current_user->ID,
                        'name'  => $current_user->display_name,
                        'email' => $current_user->user_email,
                    ),
                );
            },
            'permission_callback' => function() {
                return current_user_can( 'edit_posts' );
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
