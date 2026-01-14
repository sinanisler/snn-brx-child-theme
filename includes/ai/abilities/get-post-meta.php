<?php 
/**
 * Get Post Meta Ability
 * Registers the snn/get-post-meta ability for the WordPress Abilities API
 */

// Register category
add_action( 'wp_abilities_api_categories_init', 'snn_register_content_category_meta' );
function snn_register_content_category_meta() {
    // Only register if not already registered
    if ( ! wp_has_ability_category( 'content' ) ) {
        wp_register_ability_category(
            'content',
            array(
                'label'       => __( 'Content Management', 'snn' ),
                'description' => __( 'Abilities for managing posts, pages, and content.', 'snn' ),
            )
        );
    }
}

// Register ability
add_action( 'wp_abilities_api_init', 'snn_register_get_post_meta_ability' );
function snn_register_get_post_meta_ability() {
    wp_register_ability(
        'snn/get-post-meta',
        array(
            'label'       => __( 'Get Post Meta', 'wp-abilities' ),
            'description' => __( 'Retrieves custom fields (meta data) for a specific post.', 'wp-abilities' ),
            'category'    => 'content',
            'input_schema' => array(
                'type'       => 'object',
                'required'   => array( 'post_id' ),
                'properties' => array(
                    'post_id' => array(
                        'type'        => 'integer',
                        'description' => 'Post ID to get meta data for.',
                    ),
                    'meta_key' => array(
                        'type'        => 'string',
                        'description' => 'Specific meta key to retrieve (optional).',
                    ),
                ),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'post_id' => array( 'type' => 'integer' ),
                    'meta'    => array( 'type' => 'object' ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $post_id = absint( $input['post_id'] );

                // Check if post exists
                if ( ! get_post( $post_id ) ) {
                    return new WP_Error(
                        'post_not_found',
                        sprintf( 'Post with ID %d not found.', $post_id ),
                        array( 'status' => 404 )
                    );
                }

                if ( ! empty( $input['meta_key'] ) ) {
                    // Get specific meta key
                    $meta_key = sanitize_text_field( $input['meta_key'] );
                    $meta_value = get_post_meta( $post_id, $meta_key, true );

                    return array(
                        'post_id' => $post_id,
                        'meta'    => array( $meta_key => $meta_value ),
                    );
                } else {
                    // Get all meta data
                    $meta_data = get_post_meta( $post_id );
                    
                    // Clean up meta data (remove protected fields and arrays)
                    $clean_meta = array();
                    foreach ( $meta_data as $key => $value ) {
                        // Skip WordPress internal meta fields
                        if ( substr( $key, 0, 1 ) !== '_' ) {
                            $clean_meta[ $key ] = is_array( $value ) && count( $value ) === 1 ? $value[0] : $value;
                        }
                    }

                    return array(
                        'post_id' => $post_id,
                        'meta'    => $clean_meta,
                    );
                }
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
