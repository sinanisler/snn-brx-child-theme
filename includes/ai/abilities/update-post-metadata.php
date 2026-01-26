<?php
/**
 * Update Post Metadata Ability
 * Registers the snn/update-post-metadata ability for the WordPress Abilities API
 */

// Register ability
add_action( 'wp_abilities_api_init', 'snn_register_update_post_metadata_ability' );
function snn_register_update_post_metadata_ability() {
    wp_register_ability(
        'snn/update-post-metadata',
        array(
            'label'       => __( 'Update Post Metadata', 'snn' ),
            'description' => __( 'Updates metadata (title, excerpt, status, categories, tags, featured image) for a WordPress post or page. Does not modify the main content. Use this to change post settings, update SEO excerpts, change publication status, assign categories/tags, or set the featured image. Supports both post and page types.', 'snn' ),
            'category'    => 'content',
            'input_schema' => array(
                'type'       => 'object',
                'required'   => array( 'post_id' ),
                'properties' => array(
                    'post_id' => array(
                        'type'        => 'integer',
                        'description' => 'Post ID to update.',
                    ),
                    'title' => array(
                        'type'        => 'string',
                        'description' => 'New post title (optional).',
                        'minLength'   => 1,
                        'maxLength'   => 200,
                    ),
                    'excerpt' => array(
                        'type'        => 'string',
                        'description' => 'New post excerpt (optional).',
                    ),
                    'status' => array(
                        'type'        => 'string',
                        'description' => 'New post status (optional).',
                        'enum'        => array( 'draft', 'publish', 'pending', 'private', 'trash' ),
                    ),
                    'categories' => array(
                        'type'        => 'array',
                        'description' => 'Array of category IDs (optional).',
                        'items'       => array( 'type' => 'integer' ),
                    ),
                    'tags' => array(
                        'type'        => 'array',
                        'description' => 'Array of tag names or IDs (optional).',
                        'items'       => array( 'type' => array( 'string', 'integer' ) ),
                    ),
                    'featured_image_id' => array(
                        'type'        => 'integer',
                        'description' => 'Featured image attachment ID (optional).',
                    ),
                ),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'success'  => array(
                        'type'        => 'boolean',
                        'description' => 'Whether update was successful',
                    ),
                    'message' => array(
                        'type'        => 'string',
                        'description' => 'Result message',
                    ),
                    'updated_fields' => array(
                        'type'        => 'array',
                        'description' => 'List of fields that were updated',
                    ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $post_id = absint( $input['post_id'] );

                // Verify post exists
                $post = get_post( $post_id );
                if ( ! $post ) {
                    return new WP_Error( 'invalid_post', __( 'Post not found.', 'snn' ) );
                }

                // Check permissions
                if ( ! current_user_can( 'edit_post', $post_id ) ) {
                    return new WP_Error( 'permission_denied', __( 'You do not have permission to edit this post.', 'snn' ) );
                }

                $updated_fields = array();

                // Build update data
                $update_data = array( 'ID' => $post_id );

                if ( isset( $input['title'] ) ) {
                    $update_data['post_title'] = sanitize_text_field( $input['title'] );
                    $updated_fields[] = 'title';
                }

                if ( isset( $input['excerpt'] ) ) {
                    $update_data['post_excerpt'] = sanitize_textarea_field( $input['excerpt'] );
                    $updated_fields[] = 'excerpt';
                }

                if ( isset( $input['status'] ) ) {
                    $update_data['post_status'] = sanitize_text_field( $input['status'] );
                    $updated_fields[] = 'status';
                }

                // Update post if there are changes
                if ( count( $update_data ) > 1 ) {
                    $result = wp_update_post( $update_data, true );
                    if ( is_wp_error( $result ) ) {
                        return $result;
                    }
                }

                // Update categories
                if ( isset( $input['categories'] ) && is_array( $input['categories'] ) ) {
                    wp_set_post_categories( $post_id, $input['categories'] );
                    $updated_fields[] = 'categories';
                }

                // Update tags
                if ( isset( $input['tags'] ) && is_array( $input['tags'] ) ) {
                    wp_set_post_tags( $post_id, $input['tags'] );
                    $updated_fields[] = 'tags';
                }

                // Update featured image
                if ( isset( $input['featured_image_id'] ) ) {
                    $image_id = absint( $input['featured_image_id'] );
                    if ( $image_id > 0 ) {
                        set_post_thumbnail( $post_id, $image_id );
                    } else {
                        delete_post_thumbnail( $post_id );
                    }
                    $updated_fields[] = 'featured_image';
                }

                return array(
                    'success'        => true,
                    'message'        => sprintf( __( 'Successfully updated %d field(s).', 'snn' ), count( $updated_fields ) ),
                    'updated_fields' => $updated_fields,
                    'edit_url'       => get_edit_post_link( $post_id, 'raw' ),
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
