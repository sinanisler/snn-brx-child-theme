<?php 


    // =========================================================================
    // ABILITY 1: Get Posts
    // =========================================================================
    wp_register_ability(
        'core/get-posts',
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
                        'description' => 'Number of posts to retrieve. Use -1 for all.',
                        'default'     => 10,
                        'minimum'     => -1,
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
                $args = array(
                    'post_type'      => $input['post_type'] ?? 'post',
                    'posts_per_page' => $input['posts_per_page'] ?? 10,
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
