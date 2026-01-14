<?php
/**
 * Create Post Summaries Ability
 * Registers the snn/create-post-summaries ability for the WordPress Abilities API
 * Auto-generates TL;DR summaries for long posts
 */

// Register category
add_action( 'wp_abilities_api_categories_init', 'snn_register_post_summaries_category' );
function snn_register_post_summaries_category() {
    if ( ! wp_has_ability_category( 'content-analysis' ) ) {
        wp_register_ability_category(
            'content-analysis',
            array(
                'label'       => __( 'Content Analysis', 'snn' ),
                'description' => __( 'Abilities for analyzing and suggesting content improvements.', 'snn' ),
            )
        );
    }
}

// Register ability
add_action( 'wp_abilities_api_init', 'snn_register_create_post_summaries_ability' );
function snn_register_create_post_summaries_ability() {
    wp_register_ability(
        'snn/create-post-summaries',
        array(
            'label'       => __( 'Create Post Summaries', 'snn' ),
            'description' => __( 'Analyzes post content and generates TL;DR summaries for long posts.', 'snn' ),
            'category'    => 'content-analysis',
            'input_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'post_id' => array(
                        'type'        => 'integer',
                        'description' => 'Specific post ID to summarize. If not provided, analyzes recent posts.',
                    ),
                    'post_type' => array(
                        'oneOf'       => array(
                            array( 'type' => 'string' ),
                            array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
                        ),
                        'description' => 'Post type(s) to analyze. Can be a single post type string (e.g., "post", "page", "product") or an array of post types (e.g., ["post", "page"]). Supports any registered post type. Default: "post".',
                        'default'     => 'post',
                    ),
                    'min_word_count' => array(
                        'type'        => 'integer',
                        'description' => 'Minimum word count to consider a post "long" (default: 500).',
                        'default'     => 500,
                    ),
                    'summary_length' => array(
                        'type'        => 'string',
                        'description' => 'Desired summary length: short (1-2 sentences), medium (3-4 sentences), long (paragraph).',
                        'enum'        => array( 'short', 'medium', 'long' ),
                        'default'     => 'medium',
                    ),
                    'limit' => array(
                        'type'        => 'integer',
                        'description' => 'Number of posts to analyze (default: 10).',
                        'default'     => 10,
                    ),
                ),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'posts_analyzed'   => array( 'type' => 'integer' ),
                    'posts_needing_summary' => array( 'type' => 'array' ),
                    'content_stats'    => array( 'type' => 'object' ),
                ),
            ),
            'execute_callback' => function( $input ) {
                // Handle post_type as string or array
                if ( isset( $input['post_type'] ) ) {
                    if ( is_array( $input['post_type'] ) ) {
                        $post_type = array_map( 'sanitize_text_field', $input['post_type'] );
                    } else {
                        $post_type = sanitize_text_field( $input['post_type'] );
                    }
                } else {
                    $post_type = 'post';
                }
                $min_words = isset( $input['min_word_count'] ) ? absint( $input['min_word_count'] ) : 500;
                $summary_length = isset( $input['summary_length'] ) ? sanitize_text_field( $input['summary_length'] ) : 'medium';
                $limit = isset( $input['limit'] ) ? absint( $input['limit'] ) : 10;

                $query_args = array(
                    'post_type'      => $post_type,
                    'post_status'    => 'publish',
                    'posts_per_page' => $limit,
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                );

                // If specific post ID provided
                if ( ! empty( $input['post_id'] ) ) {
                    $query_args['p'] = absint( $input['post_id'] );
                    $query_args['posts_per_page'] = 1;
                }

                $posts = get_posts( $query_args );
                $posts_needing_summary = array();
                $total_words = 0;
                $long_posts_count = 0;

                foreach ( $posts as $post ) {
                    $content = wp_strip_all_tags( $post->post_content );
                    $word_count = str_word_count( $content );
                    $total_words += $word_count;

                    $post_data = array(
                        'id'           => $post->ID,
                        'title'        => $post->post_title,
                        'post_type'    => $post->post_type,
                        'url'          => get_permalink( $post->ID ),
                        'word_count'   => $word_count,
                        'is_long_post' => $word_count >= $min_words,
                        'excerpt'      => $post->post_excerpt,
                        'has_excerpt'  => ! empty( $post->post_excerpt ),
                    );

                    if ( $word_count >= $min_words ) {
                        $long_posts_count++;

                        // Extract key sentences for summary generation
                        $sentences = preg_split( '/(?<=[.!?])\s+/', $content, -1, PREG_SPLIT_NO_EMPTY );
                        $sentence_count = count( $sentences );

                        // Get first paragraph as intro
                        $paragraphs = preg_split( '/\n\n+/', $content );
                        $first_paragraph = ! empty( $paragraphs[0] ) ? trim( $paragraphs[0] ) : '';

                        // Calculate target sentences based on summary length
                        switch ( $summary_length ) {
                            case 'short':
                                $target_sentences = 2;
                                break;
                            case 'long':
                                $target_sentences = 6;
                                break;
                            default: // medium
                                $target_sentences = 4;
                        }

                        // Extract key points (first sentence, middle key points, conclusion)
                        $key_points = array();
                        if ( $sentence_count > 0 ) {
                            $key_points[] = $sentences[0]; // First sentence

                            // Add middle sentences if content is long enough
                            if ( $sentence_count > 4 ) {
                                $middle = (int) ( $sentence_count / 2 );
                                $key_points[] = $sentences[ $middle ];
                            }

                            // Add last sentence if different from first
                            if ( $sentence_count > 1 ) {
                                $key_points[] = $sentences[ $sentence_count - 1 ];
                            }
                        }

                        $post_data['content_analysis'] = array(
                            'sentence_count'    => $sentence_count,
                            'paragraph_count'   => count( $paragraphs ),
                            'first_paragraph'   => substr( $first_paragraph, 0, 500 ),
                            'key_points'        => array_slice( $key_points, 0, $target_sentences ),
                            'suggested_summary' => implode( ' ', array_slice( $key_points, 0, $target_sentences ) ),
                            'reading_time_mins' => ceil( $word_count / 200 ), // Average reading speed
                        );

                        $post_data['recommendation'] = empty( $post->post_excerpt )
                            ? 'This post needs a TL;DR summary/excerpt.'
                            : 'Post has excerpt, but may benefit from a more detailed summary.';
                    }

                    $posts_needing_summary[] = $post_data;
                }

                return array(
                    'posts_analyzed'       => count( $posts ),
                    'posts_needing_summary' => $posts_needing_summary,
                    'content_stats'        => array(
                        'total_words_analyzed' => $total_words,
                        'average_word_count'   => count( $posts ) > 0 ? round( $total_words / count( $posts ) ) : 0,
                        'long_posts_count'     => $long_posts_count,
                        'min_word_threshold'   => $min_words,
                        'summary_length_used'  => $summary_length,
                    ),
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
