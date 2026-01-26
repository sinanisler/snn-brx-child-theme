<?php
/**
 * Append Content to Post Ability
 * Registers the snn/append-content-to-post ability for the WordPress Abilities API
 */

// Register ability
add_action( 'wp_abilities_api_init', 'snn_register_append_content_to_post_ability' );
function snn_register_append_content_to_post_ability() {
    wp_register_ability(
        'snn/append-content-to-post',
        array(
            'label'       => __( 'Append Content to Post', 'snn' ),
            'description' => __( 'Appends new content to the end of an existing WordPress post or page. Supports HTML and automatically creates appropriate block markup for the block editor. Use this to add additional paragraphs, sections, or any content to the bottom of a post without replacing existing content. Perfect for adding conclusions, updates, or follow-up information.', 'snn' ),
            'category'    => 'content',
            'input_schema' => array(
                'type'       => 'object',
                'required'   => array( 'post_id', 'content' ),
                'properties' => array(
                    'post_id' => array(
                        'type'        => 'integer',
                        'description' => 'Post ID to append content to.',
                    ),
                    'content' => array(
                        'type'        => 'string',
                        'description' => 'Content to append (HTML supported).',
                        'minLength'   => 1,
                    ),
                    'separator' => array(
                        'type'        => 'string',
                        'description' => 'Optional separator between existing content and new content.',
                        'default'     => "\n\n",
                    ),
                ),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'success'  => array(
                        'type'        => 'boolean',
                        'description' => 'Whether append was successful',
                    ),
                    'message' => array(
                        'type'        => 'string',
                        'description' => 'Result message',
                    ),
                    'new_word_count' => array(
                        'type'        => 'integer',
                        'description' => 'Updated word count',
                    ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $post_id = absint( $input['post_id'] );
                $content = $input['content'];
                $separator = $input['separator'] ?? "\n\n";

                // Get post
                $post = get_post( $post_id );
                if ( ! $post ) {
                    return new WP_Error( 'invalid_post', __( 'Post not found.', 'snn' ) );
                }

                // Check permissions
                if ( ! current_user_can( 'edit_post', $post_id ) ) {
                    return new WP_Error( 'permission_denied', __( 'You do not have permission to edit this post.', 'snn' ) );
                }

                // Append content
                $new_content = $post->post_content . $separator . wp_kses_post( $content );

                // Update post
                $result = wp_update_post( array(
                    'ID'           => $post_id,
                    'post_content' => $new_content,
                ), true );

                if ( is_wp_error( $result ) ) {
                    return $result;
                }

                // Calculate new word count
                $word_count = str_word_count( wp_strip_all_tags( $new_content ) );

                return array(
                    'success'        => true,
                    'message'        => __( 'Content successfully appended to post.', 'snn' ),
                    'new_word_count' => $word_count,
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
                'idempotent'   => false,
            ),
        )
    );
}
