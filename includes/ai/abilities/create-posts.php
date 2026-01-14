<?php 
/**
 * Create Post Ability
 * Registers the snn/create-post ability for the WordPress Abilities API
 */

// Register category
add_action( 'wp_abilities_api_categories_init', 'snn_register_content_category' );
function snn_register_content_category() {
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
add_action( 'wp_abilities_api_init', 'snn_register_create_post_ability' );
function snn_register_create_post_ability() {
    wp_register_ability(
        'snn/create-post',
        array(
            'label'       => __( 'Create Post', 'wp-abilities' ),
            'description' => __( 'Creates a new post with the provided title and content.', 'wp-abilities' ),
            'category'    => 'content',
            'input_schema' => array(
                'type'       => 'object',
                'required'   => array( 'title', 'content' ),
                'properties' => array(
                    'title' => array(
                        'type'        => 'string',
                        'description' => 'Post title.',
                        'minLength'   => 1,
                        'maxLength'   => 200,
                    ),
                    'content' => array(
                        'type'        => 'string',
                        'description' => 'Post content (HTML allowed).',
                        'minLength'   => 1,
                    ),
                    'status' => array(
                        'type'        => 'string',
                        'description' => 'Post status (draft, publish, pending).',
                        'enum'        => array( 'draft', 'publish', 'pending', 'private' ),
                        'default'     => 'draft',
                    ),
                    'post_type' => array(
                        'type'        => 'string',
                        'description' => 'Post type (post, page).',
                        'enum'        => array( 'post', 'page' ),
                        'default'     => 'post',
                    ),
                    'excerpt' => array(
                        'type'        => 'string',
                        'description' => 'Optional post excerpt.',
                    ),
                    'categories' => array(
                        'type'        => 'array',
                        'description' => 'Array of category IDs.',
                        'items'       => array( 'type' => 'integer' ),
                    ),
                    'tags' => array(
                        'type'        => 'array',
                        'description' => 'Array of tag names.',
                        'items'       => array( 'type' => 'string' ),
                    ),
                ),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'id'  => array(
                        'type'        => 'integer',
                        'description' => 'Created post ID',
                    ),
                    'url' => array(
                        'type'        => 'string',
                        'description' => 'Post permalink',
                    ),
                    'edit_url' => array(
                        'type'        => 'string',
                        'description' => 'Edit URL in admin',
                    ),
                    'status' => array(
                        'type'        => 'string',
                        'description' => 'Post status',
                    ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $post_data = array(
                    'post_title'   => sanitize_text_field( $input['title'] ),
                    'post_content' => wp_kses_post( $input['content'] ),
                    'post_status'  => $input['status'] ?? 'draft',
                    'post_type'    => $input['post_type'] ?? 'post',
                    'post_author'  => get_current_user_id(),
                );

                // Add optional excerpt
                if ( ! empty( $input['excerpt'] ) ) {
                    $post_data['post_excerpt'] = sanitize_textarea_field( $input['excerpt'] );
                }

                // Insert the post
                $post_id = wp_insert_post( $post_data, true );

                if ( is_wp_error( $post_id ) ) {
                    return $post_id;
                }

                // Set categories if provided
                if ( ! empty( $input['categories'] ) && is_array( $input['categories'] ) ) {
                    wp_set_post_categories( $post_id, $input['categories'] );
                }

                // Set tags if provided
                if ( ! empty( $input['tags'] ) && is_array( $input['tags'] ) ) {
                    wp_set_post_tags( $post_id, $input['tags'] );
                }

                return array(
                    'id'       => $post_id,
                    'url'      => get_permalink( $post_id ),
                    'edit_url' => get_edit_post_link( $post_id, 'raw' ),
                    'status'   => get_post_status( $post_id ),
                );
            },
            'permission_callback' => function() {
                return current_user_can( 'publish_posts' );
            },
            'meta' => array(
                'show_in_rest' => true,
                'readonly'     => false,
                'destructive'  => false,
                'idempotent'   => false,
            ),
        )
    );
}
