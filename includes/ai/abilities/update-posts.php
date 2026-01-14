<?php 
/**
 * Update Post Ability
 * Registers the core/update-post ability for the WordPress Abilities API
 */

// Register category
add_action( 'wp_abilities_api_categories_init', 'snn_register_content_category_update' );
function snn_register_content_category_update() {
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
add_action( 'wp_abilities_api_init', 'snn_register_update_post_ability' );
function snn_register_update_post_ability() {
    wp_register_ability(
        'core/update-post',
        array(
            'label'       => __( 'Update Post', 'wp-abilities' ),
            'description' => __( 'Updates an existing post with new content.', 'wp-abilities' ),
            'category'    => 'content',
            'input_schema' => array(
                'type'       => 'object',
                'required'   => array( 'post_id' ),
                'properties' => array(
                    'post_id' => array(
                        'type'        => 'integer',
                        'description' => 'ID of the post to update.',
                    ),
                    'title' => array(
                        'type'        => 'string',
                        'description' => 'New post title.',
                    ),
                    'content' => array(
                        'type'        => 'string',
                        'description' => 'New post content.',
                    ),
                    'status' => array(
                        'type'        => 'string',
                        'description' => 'New post status.',
                        'enum'        => array( 'draft', 'publish', 'pending', 'private' ),
                    ),
                    'excerpt' => array(
                        'type'        => 'string',
                        'description' => 'New post excerpt.',
                    ),
                ),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'id'       => array( 'type' => 'integer' ),
                    'updated'  => array( 'type' => 'boolean' ),
                    'url'      => array( 'type' => 'string' ),
                    'modified' => array( 'type' => 'string' ),
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

                $update_data = array( 'ID' => $post_id );

                if ( isset( $input['title'] ) ) {
                    $update_data['post_title'] = sanitize_text_field( $input['title'] );
                }

                if ( isset( $input['content'] ) ) {
                    $update_data['post_content'] = wp_kses_post( $input['content'] );
                }

                if ( isset( $input['status'] ) ) {
                    $update_data['post_status'] = sanitize_text_field( $input['status'] );
                }

                if ( isset( $input['excerpt'] ) ) {
                    $update_data['post_excerpt'] = sanitize_textarea_field( $input['excerpt'] );
                }

                $result = wp_update_post( $update_data, true );

                if ( is_wp_error( $result ) ) {
                    return $result;
                }

                $post = get_post( $post_id );

                return array(
                    'id'       => $post_id,
                    'updated'  => true,
                    'url'      => get_permalink( $post ),
                    'modified' => $post->post_modified,
                );
            },
            'permission_callback' => function() {
                return current_user_can( 'edit_posts' );
            },
            'meta' => array(
                'show_in_rest' => true,
                'readonly'     => false,
                'destructive'  => false,
                'idempotent'   => true,
            ),
        )
    );
}
