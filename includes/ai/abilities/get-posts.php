<?php 
/**
 * Get Posts Ability
 * Registers the snn/get-posts ability for the WordPress Abilities API
 */

// Register category
add_action( 'wp_abilities_api_categories_init', 'snn_register_content_category_get_posts' );
function snn_register_content_category_get_posts() {
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
add_action( 'wp_abilities_api_init', 'snn_register_get_posts_ability' );
function snn_register_get_posts_ability() {
    wp_register_ability(
        'snn/get-posts',
        array(
            'label'       => __( 'Get Posts', 'wp-abilities' ),
            'description' => __( 'Retrieves a list of posts with optional filtering.', 'wp-abilities' ),
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
                        'author'  => array(
                            'type'        => 'string',
                            'description' => 'Post author display name',
                        ),
                    ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $posts_per_page = isset( $input['posts_per_page'] ) ? absint( $input['posts_per_page'] ) : 10;
                $args = array(
                    'post_type'      => $input['post_type'] ?? 'post',
                    // Cap at 100 for performance on large sites
                    'posts_per_page' => min( $posts_per_page, 100 ),
                    'post_status'    => 'publish',
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
