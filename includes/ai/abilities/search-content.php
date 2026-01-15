<?php 
/**
 * Search Content Ability
 * Registers the snn/search-content ability for the WordPress Abilities API
 */

// Register category
add_action( 'wp_abilities_api_categories_init', 'snn_register_content_category_search' );
function snn_register_content_category_search() {
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
add_action( 'wp_abilities_api_init', 'snn_register_search_content_ability' );
function snn_register_search_content_ability() {
    wp_register_ability(
        'snn/search-content',
        array(
            'label'       => __( 'Search Content', 'wp-abilities' ),
            'description' => __( 'Performs full-text search across WordPress content (posts, pages, custom post types) matching query against titles and content. Returns post ID, title, post type, permalink, excerpt (20 words), publication date, plus total found count and returned count. Can limit to specific post type or search all ("any"), supports pagination with limit/offset (max 100 per request), searches any post status. Use this to find posts by keyword, locate specific content, search across all content types, or build search functionality. Returns relevance-ordered results matching WordPress default search behavior.', 'wp-abilities' ),
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
                $args = array(
                    's'              => sanitize_text_field( $input['query'] ),
                    'post_type'      => $input['post_type'] ?? 'any',
                    'posts_per_page' => $input['limit'] ?? 10,
                    'offset'         => $input['offset'] ?? 0,
                    'post_status'    => 'any',
                );

                $query   = new WP_Query( $args );
                $results = array();

                foreach ( $query->posts as $post ) {
                    $results[] = array(
                        'id'      => $post->ID,
                        'title'   => $post->post_title,
                        'type'    => $post->post_type,
                        'url'     => get_permalink( $post ),
                        'excerpt' => wp_trim_words( $post->post_content, 20 ),
                        'date'    => get_the_date( 'Y-m-d H:i:s', $post ),
                    );
                }

                return array(
                    'total'    => $query->found_posts,
                    'returned' => count( $results ),
                    'results'  => $results,
                );
            },
            'permission_callback' => '__return_true',
            'meta' => array(
                'show_in_rest' => true,
                'readonly'     => true,
                'destructive'  => false,
                'idempotent'   => true,
            ),
        )
    );
}
