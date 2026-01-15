<?php 
/**
 * Get Comments Ability
 * Registers the snn/get-comments ability for the WordPress Abilities API
 */

// Register category
add_action( 'wp_abilities_api_categories_init', 'snn_register_comments_category' );
function snn_register_comments_category() {
    // Only register if not already registered
    if ( ! wp_has_ability_category( 'comments' ) ) {
        wp_register_ability_category(
            'comments',
            array(
                'label'       => __( 'Comments Management', 'snn' ),
                'description' => __( 'Abilities for managing comments.', 'snn' ),
            )
        );
    }
}

// Register ability
add_action( 'wp_abilities_api_init', 'snn_register_get_comments_ability' );
function snn_register_get_comments_ability() {
    wp_register_ability(
        'snn/get-comments',
        array(
            'label'       => __( 'Get Comments', 'wp-abilities' ),
            'description' => __( 'Retrieves comments with full details including comment ID, post ID, author name, email, content, date, and approval status. Can filter by specific post ID to get post-specific comments or retrieve site-wide comments. Supports filtering by status (approved, pending, spam) and limiting results (max 100 for performance). Returns array of comment objects. Use this to moderate comments, analyze engagement, find spam comments, check specific post discussions, or export comment data.', 'wp-abilities' ),
            'category'    => 'comments',
            'input_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'post_id' => array(
                        'type'        => 'integer',
                        'description' => 'Post ID to get comments for (optional).',
                    ),
                    'number' => array(
                        'type'        => 'integer',
                        'description' => 'Number of comments to retrieve (max 100 for performance). Omit parameter or use default to get first 10.',
                        'default'     => 10,
                        'minimum'     => 1,
                        'maximum'     => 100,
                    ),
                    'status' => array(
                        'type'        => 'string',
                        'description' => 'Comment status (approve, hold, spam).',
                        'default'     => 'approve',
                    ),
                ),
            ),
            'output_schema' => array(
                'type'  => 'array',
                'items' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'id'      => array( 'type' => 'integer' ),
                        'post_id' => array( 'type' => 'integer' ),
                        'author'  => array( 'type' => 'string' ),
                        'email'   => array( 'type' => 'string' ),
                        'content' => array( 'type' => 'string' ),
                        'date'    => array( 'type' => 'string' ),
                        'status'  => array( 'type' => 'string' ),
                    ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $number = isset( $input['number'] ) ? absint( $input['number'] ) : 10;
                $args = array(
                    // Cap at 100 for performance on large sites
                    'number' => min( $number, 100 ),
                    'status' => isset( $input['status'] ) ? sanitize_text_field( $input['status'] ) : 'approve',
                );

                if ( ! empty( $input['post_id'] ) ) {
                    $args['post_id'] = absint( $input['post_id'] );
                }

                $comments = get_comments( $args );
                $result = array();

                foreach ( $comments as $comment ) {
                    $result[] = array(
                        'id'      => $comment->comment_ID,
                        'post_id' => $comment->comment_post_ID,
                        'author'  => $comment->comment_author,
                        'email'   => $comment->comment_author_email,
                        'content' => $comment->comment_content,
                        'date'    => $comment->comment_date,
                        'status'  => wp_get_comment_status( $comment ),
                    );
                }

                return $result;
            },
            'permission_callback' => function() {
                return current_user_can( 'moderate_comments' );
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
