<?php 
/**
 * Search Content Ability
 * Registers the snn/search-content ability for the WordPress Abilities API
 */

// Register ability
add_action( 'wp_abilities_api_init', 'snn_register_search_content_ability' );
function snn_register_search_content_ability() {
    wp_register_ability(
        'snn/search-content',
        array(
            'label'       => __( 'Search Content', 'snn' ),
            'description' => __( 'Performs full-text search across WordPress content (posts, pages, custom post types) matching query against titles and content. Returns post ID, title, post type, permalink, excerpt (20 words), publication date, plus total found count and returned count. Can limit to specific post type or search all ("any"), supports pagination with limit/offset (max 100 per request), searches any post status. Use this to find posts by keyword, locate specific content, search across all content types, or build search functionality. Returns relevance-ordered results matching WordPress default search behavior.', 'snn' ),
            'category'    => 'content',
            'input_schema' => array(
                'type'       => 'object',
                'required'   => array( 'query' ),
                'properties' => array(
                    'query' => array(
                        'type'        => 'string',
                        'description' => 'Search query string.',
                        'minLength'   => 1,
                    ),
                    'post_type' => array(
                        'type'        => 'string',
                        'description' => 'Limit search to specific post type.',
                        'default'     => 'any',
                    ),
                    'limit' => array(
                        'type'        => 'integer',
                        'description' => 'Maximum results to return.',
                        'default'     => 10,
                        'minimum'     => 1,
                        'maximum'     => 100,
                    ),
                    'offset' => array(
                        'type'        => 'integer',
                        'description' => 'Number of results to skip (for pagination).',
                        'default'     => 0,
                        'minimum'     => 0,
                    ),
                ),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'total' => array(
                        'type'        => 'integer',
                        'description' => 'Total number of results found',
                    ),
                    'returned' => array(
                        'type'        => 'integer',
                        'description' => 'Number of results returned',
                    ),
                    'results' => array(
                        'type'  => 'array',
                        'items' => array(
                            'type'       => 'object',
                            'properties' => array(
                                'id'      => array( 'type' => 'integer' ),
                                'title'   => array( 'type' => 'string' ),
                                'type'    => array( 'type' => 'string' ),
                                'url'     => array( 'type' => 'string' ),
                                'excerpt' => array( 'type' => 'string' ),
                                'date'    => array( 'type' => 'string' ),
                            ),
                        ),
                    ),
                ),
            ),
            'execute_callback' => function( $input ) {
                // Never query 'any': that would include internal types such as agent history.
                // Only post types with an admin UI that the user can edit are searched.
                $post_types = array();
                foreach ( get_post_types( array( 'show_ui' => true ), 'objects' ) as $type_obj ) {
                    if ( current_user_can( $type_obj->cap->edit_posts ) ) {
                        $post_types[] = $type_obj->name;
                    }
                }

                $requested_type = sanitize_key( $input['post_type'] ?? 'any' );
                if ( '' !== $requested_type && 'any' !== $requested_type ) {
                    if ( ! in_array( $requested_type, $post_types, true ) ) {
                        return new WP_Error(
                            'invalid_post_type',
                            sprintf( 'Post type "%s" does not exist or you are not allowed to read it.', $requested_type ),
                            array( 'status' => 403 )
                        );
                    }
                    $post_types = array( $requested_type );
                }

                if ( empty( $post_types ) ) {
                    return array( 'total' => 0, 'returned' => 0, 'results' => array() );
                }

                $args = array(
                    's'              => sanitize_text_field( $input['query'] ),
                    'post_type'      => $post_types,
                    'posts_per_page' => max( 1, min( absint( $input['limit'] ?? 10 ), 100 ) ),
                    'offset'         => absint( $input['offset'] ?? 0 ),
                    // Explicit statuses instead of 'any'; unreadable posts are filtered below
                    'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
                );

                $query   = new WP_Query( $args );
                $results = array();
                $hidden  = 0;

                foreach ( $query->posts as $post ) {
                    if ( ! current_user_can( 'read_post', $post->ID ) ) {
                        $hidden++;
                        continue;
                    }

                    $results[] = array(
                        'id'      => $post->ID,
                        'title'   => $post->post_title,
                        'type'    => $post->post_type,
                        'url'     => (string) get_permalink( $post ),
                        'excerpt' => wp_trim_words( $post->post_content, 20 ),
                        'date'    => get_the_date( 'Y-m-d H:i:s', $post ),
                    );
                }

                return array(
                    'total'    => max( 0, (int) $query->found_posts - $hidden ),
                    'returned' => count( $results ),
                    'results'  => $results,
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
