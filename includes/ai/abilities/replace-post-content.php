<?php
/**
 * Replace Post Content Ability
 * Registers the snn/replace-post-content ability for the WordPress Abilities API
 */

// Register ability
add_action( 'wp_abilities_api_init', 'snn_register_replace_post_content_ability' );
function snn_register_replace_post_content_ability() {
    wp_register_ability(
        'snn/replace-post-content',
        array(
            'label'       => __( 'Replace Post Content', 'snn' ),
            'description' => __( 'Replaces the entire content of a WordPress post or page with new content. Supports HTML and block editor markup. Use this to completely rewrite a post, update outdated content, or fix major issues. This is a destructive operation - the old content will be replaced. Consider using append-content-to-post for adding content instead.', 'snn' ),
            'category'    => 'content',
            'input_schema' => array(
                'type'       => 'object',
                'required'   => array( 'post_id', 'new_content' ),
                'properties' => array(
                    'post_id' => array(
                        'type'        => 'integer',
                        'description' => 'Post ID to update.',
                    ),
                    'new_content' => array(
                        'type'        => 'string',
                        'description' => 'New content to replace existing content (HTML supported).',
                        'minLength'   => 1,
                    ),
                    'update_modified_date' => array(
                        'type'        => 'boolean',
                        'description' => 'Whether to update the post modified date.',
                        'default'     => true,
                    ),
                ),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'success'  => array(
                        'type'        => 'boolean',
                        'description' => 'Whether replacement was successful',
                    ),
                    'message' => array(
                        'type'        => 'string',
                        'description' => 'Result message',
                    ),
                    'old_word_count' => array(
                        'type'        => 'integer',
                        'description' => 'Previous word count',
                    ),
                    'new_word_count' => array(
                        'type'        => 'integer',
                        'description' => 'New word count',
                    ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $post_id = absint( $input['post_id'] );
                $new_content = $input['new_content'];
                $update_modified = $input['update_modified_date'] ?? true;

                // Get post
                $post = get_post( $post_id );
                if ( ! $post ) {
                    return new WP_Error( 'invalid_post', __( 'Post not found.', 'snn' ) );
                }

                // Check permissions
                if ( ! current_user_can( 'edit_post', $post_id ) ) {
                    return new WP_Error( 'permission_denied', __( 'You do not have permission to edit this post.', 'snn' ) );
                }

                // Calculate old word count
                $old_word_count = str_word_count( wp_strip_all_tags( $post->post_content ) );

                // Update post content
                $update_data = array(
                    'ID'           => $post_id,
                    'post_content' => wp_kses_post( $new_content ),
                );

                // Preserve modified date if requested
                if ( ! $update_modified ) {
                    $update_data['post_modified']     = $post->post_modified;
                    $update_data['post_modified_gmt'] = $post->post_modified_gmt;
                }

                $result = wp_update_post( $update_data, true );

                if ( is_wp_error( $result ) ) {
                    return $result;
                }

                // Calculate new word count
                $new_word_count = str_word_count( wp_strip_all_tags( $new_content ) );

                return array(
                    'success'        => true,
                    'message'        => __( 'Post content successfully replaced.', 'snn' ),
                    'old_word_count' => $old_word_count,
                    'new_word_count' => $new_word_count,
                    'word_count_change' => $new_word_count - $old_word_count,
                    'edit_url'       => get_edit_post_link( $post_id, 'raw' ),
                );
            },
            'permission_callback' => function() {
                return current_user_can( 'edit_posts' );
            },
            'meta' => array(
                'show_in_rest' => true,
                'readonly'     => false,
                'destructive'  => true,
                'idempotent'   => true,
            ),
        )
    );
}
