<?php 
/**
 * Get Site Info Ability
 * Registers the core/get-site-info ability for the WordPress Abilities API
 */

// Register category
add_action( 'wp_abilities_api_categories_init', 'snn_register_system_category' );
function snn_register_system_category() {
    // Only register if not already registered
    if ( ! wp_has_ability_category( 'system' ) ) {
        wp_register_ability_category(
            'system',
            array(
                'label'       => __( 'System Information', 'snn' ),
                'description' => __( 'Abilities for retrieving system and site information.', 'snn' ),
            )
        );
    }
}

// Register ability
add_action( 'wp_abilities_api_init', 'snn_register_get_site_info_ability' );
function snn_register_get_site_info_ability() {
    wp_register_ability(
        'core/get-site-info',
        array(
            'label'       => __( 'Get Site Info', 'wp-abilities' ),
            'description' => __( 'Retrieves basic information about the WordPress site.', 'wp-abilities' ),
            'category'    => 'system',
            'input_schema' => array(
                'type'       => 'object',
                'properties' => array(),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'site_name'    => array( 'type' => 'string' ),
                    'site_url'     => array( 'type' => 'string' ),
                    'admin_email'  => array( 'type' => 'string' ),
                    'wp_version'   => array( 'type' => 'string' ),
                    'language'     => array( 'type' => 'string' ),
                    'timezone'     => array( 'type' => 'string' ),
                    'post_count'   => array( 'type' => 'integer' ),
                    'page_count'   => array( 'type' => 'integer' ),
                    'user_count'   => array( 'type' => 'integer' ),
                    'active_theme' => array( 'type' => 'string' ),
                ),
            ),
            'execute_callback' => function( $input ) {
                global $wp_version;

                $theme = wp_get_theme();
                $post_count = wp_count_posts( 'post' );
                $page_count = wp_count_posts( 'page' );

                return array(
                    'site_name'    => get_bloginfo( 'name' ),
                    'site_url'     => get_bloginfo( 'url' ),
                    'admin_email'  => get_bloginfo( 'admin_email' ),
                    'wp_version'   => $wp_version,
                    'language'     => get_bloginfo( 'language' ),
                    'timezone'     => get_option( 'timezone_string' ),
                    'post_count'   => (int) $post_count->publish,
                    'page_count'   => (int) $page_count->publish,
                    'user_count'   => count_users()['total_users'],
                    'active_theme' => $theme->get( 'Name' ),
                );
            },
            'permission_callback' => function() {
                return current_user_can( 'manage_options' );
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
