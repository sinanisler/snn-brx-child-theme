<?php 
/**
 * Get Posts Ability
 * Registers the snn/get-posts ability for the WordPress Abilities API
 */

// Register ability
add_action( 'wp_abilities_api_init', 'snn_register_get_posts_ability' );
function snn_register_get_posts_ability() {
    wp_register_ability(
        'snn/get-posts',
        array(
            'label'       => __( 'Get Posts', 'wp-abilities' ),
            'description' => __( 'Retrieves a list of posts with filtering and sorting options. By default includes both published and draft posts since users are in dashboard context. Returns post ID, title, permalink, excerpt (30 words), publication date, post status, and author display name. Supports filtering by post type (post/page/custom), post status, category slug, ordering (date/title/modified/random), sort direction (ASC/DESC), and limiting results (max 100 for performance). Returns summarized data - use get-post-by-id for full content. Use this to list recent posts, browse by category, get post overviews, create content listings, or analyze publication patterns.', 'wp-abilities' ),
            'category'    => 'content',
            'input_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'post_type' => array(
                        'type'        => 'string',
                        'description' => 'Post type to retrieve (post, page, or custom).',
                        'default'     => 'post',
                    ),
                    'posts_per_page' => array(
                        'type'        => 'integer',
                        'description' => 'Number of posts to retrieve (max 100 for performance). Omit parameter or use default to get first 10.',
                        'default'     => 10,
                        'minimum'     => 1,
                        'maximum'     => 100,
                    ),
                    'category' => array(
                        'type'        => 'string',
                        'description' => 'Category slug to filter by.',
                    ),
                    'post_status' => array(
                        'type'        => 'string',
                        'description' => 'Post status to filter by. Defaults to both publish and draft.',
                        'enum'        => array( 'publish', 'draft', 'private', 'pending', 'any' ),
                        'default'     => 'publish,draft',
                    ),
                    'orderby' => array(
                        'type'        => 'string',
                        'description' => 'Field to order results by (date, title, modified).',
                        'enum'        => array( 'date', 'title', 'modified', 'rand' ),
                        'default'     => 'date',
                    ),
                    'order' => array(
                        'type'        => 'string',
                        'description' => 'Sort order (ASC or DESC).',
                        'enum'        => array( 'ASC', 'DESC' ),
                        'default'     => 'DESC',
                    ),
                ),
            ),
            'output_schema' => array(
                'type'  => 'array',
                'items' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'id'      => array(
                            'type'        => 'integer',
                            'description' => 'Post ID',
                        ),
                        'title'   => array(
                            'type'        => 'string',
                            'description' => 'Post title',
                        ),
                        'url'     => array(
                            'type'        => 'string',
                            'description' => 'Post permalink',
                        ),
                        'excerpt' => array(
                            'type'        => 'string',
                            'description' => 'Post excerpt (first 30 words)',
                        ),
                        'date'    => array(
                            'type'        => 'string',
                            'description' => 'Post publication date',
                        ),
                        'status'  => array(
                            'type'        => 'string',
                            'description' => 'Post status (publish, draft, private, etc.)',
                        ),
                        'author'  => array(
                            'type'        => 'string',
                            'description' => 'Post author display name',
                        ),
                    ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $posts_per_page = isset( $input['posts_per_page'] ) ? absint( $input['posts_per_page'] ) : 10;
                
                // Handle post status - default to both publish and draft
                $post_status = $input['post_status'] ?? 'publish,draft';
                if ( strpos( $post_status, ',' ) !== false ) {
                    $post_status = array_map( 'trim', explode( ',', $post_status ) );
                }
                
                $args = array(
                    'post_type'      => $input['post_type'] ?? 'post',
                    // Cap at 100 for performance on large sites
                    'posts_per_page' => min( $posts_per_page, 100 ),
                    'post_status'    => $post_status,
                    'orderby'        => $input['orderby'] ?? 'date',
                    'order'          => $input['order'] ?? 'DESC',
                );

                if ( ! empty( $input['category'] ) ) {
                    $args['category_name'] = sanitize_text_field( $input['category'] );
                }

                $posts  = get_posts( $args );
                $result = array();

                foreach ( $posts as $post ) {
                    $author = get_userdata( $post->post_author );

                    $result[] = array(
                        'id'      => $post->ID,
                        'title'   => $post->post_title,
                        'url'     => get_permalink( $post ),
                        'excerpt' => wp_trim_words( $post->post_content, 30 ),
                        'date'    => get_the_date( 'Y-m-d H:i:s', $post ),
                        'status'  => $post->post_status,
                        'author'  => $author ? $author->display_name : '',
                    );
                }

                return $result;
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
