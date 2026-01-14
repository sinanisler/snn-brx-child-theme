<?php
/**
 * Suggest Content Ideas Ability
 * Registers the snn/suggest-content-ideas ability for the WordPress Abilities API
 * Analyzes existing categories/tags and suggests content gaps
 */

// Register category
add_action( 'wp_abilities_api_categories_init', 'snn_register_content_ideas_category' );
function snn_register_content_ideas_category() {
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
add_action( 'wp_abilities_api_init', 'snn_register_suggest_content_ideas_ability' );
function snn_register_suggest_content_ideas_ability() {
    wp_register_ability(
        'snn/suggest-content-ideas',
        array(
            'label'       => __( 'Suggest Content Ideas', 'snn' ),
            'description' => __( 'Analyzes existing categories and tags to identify content gaps and suggest new content ideas.', 'snn' ),
            'category'    => 'content-analysis',
            'input_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'post_type' => array(
                        'type'        => 'string',
                        'description' => 'Post type to analyze (default: post).',
                        'default'     => 'post',
                    ),
                    'min_posts_threshold' => array(
                        'type'        => 'integer',
                        'description' => 'Categories/tags with fewer posts than this are considered gaps (default: 3).',
                        'default'     => 3,
                    ),
                    'include_empty' => array(
                        'type'        => 'boolean',
                        'description' => 'Include categories/tags with zero posts (default: true).',
                        'default'     => true,
                    ),
                ),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'analysis_summary' => array( 'type' => 'object' ),
                    'category_gaps'    => array( 'type' => 'array' ),
                    'tag_gaps'         => array( 'type' => 'array' ),
                    'suggestions'      => array( 'type' => 'array' ),
                    'popular_topics'   => array( 'type' => 'array' ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $post_type = isset( $input['post_type'] ) ? sanitize_text_field( $input['post_type'] ) : 'post';
                $min_posts = isset( $input['min_posts_threshold'] ) ? absint( $input['min_posts_threshold'] ) : 3;
                $include_empty = isset( $input['include_empty'] ) ? (bool) $input['include_empty'] : true;

                // Get all categories
                $categories = get_terms( array(
                    'taxonomy'   => 'category',
                    'hide_empty' => ! $include_empty,
                ) );

                // Get all tags
                $tags = get_terms( array(
                    'taxonomy'   => 'post_tag',
                    'hide_empty' => ! $include_empty,
                ) );

                $category_gaps = array();
                $popular_categories = array();
                $tag_gaps = array();
                $popular_tags = array();

                // Analyze categories
                if ( ! is_wp_error( $categories ) ) {
                    foreach ( $categories as $category ) {
                        $cat_data = array(
                            'id'         => $category->term_id,
                            'name'       => $category->name,
                            'slug'       => $category->slug,
                            'post_count' => $category->count,
                            'url'        => get_term_link( $category ),
                        );

                        if ( $category->count < $min_posts ) {
                            $cat_data['gap_type'] = $category->count === 0 ? 'empty' : 'underutilized';
                            $category_gaps[] = $cat_data;
                        } else {
                            $popular_categories[] = $cat_data;
                        }
                    }
                }

                // Analyze tags
                if ( ! is_wp_error( $tags ) ) {
                    foreach ( $tags as $tag ) {
                        $tag_data = array(
                            'id'         => $tag->term_id,
                            'name'       => $tag->name,
                            'slug'       => $tag->slug,
                            'post_count' => $tag->count,
                            'url'        => get_term_link( $tag ),
                        );

                        if ( $tag->count < $min_posts ) {
                            $tag_data['gap_type'] = $tag->count === 0 ? 'empty' : 'underutilized';
                            $tag_gaps[] = $tag_data;
                        } else {
                            $popular_tags[] = $tag_data;
                        }
                    }
                }

                // Sort by post count
                usort( $popular_categories, function( $a, $b ) {
                    return $b['post_count'] - $a['post_count'];
                });
                usort( $popular_tags, function( $a, $b ) {
                    return $b['post_count'] - $a['post_count'];
                });

                // Generate suggestions based on gaps
                $suggestions = array();

                foreach ( array_slice( $category_gaps, 0, 5 ) as $gap ) {
                    $suggestions[] = array(
                        'type'        => 'category_gap',
                        'priority'    => $gap['post_count'] === 0 ? 'high' : 'medium',
                        'suggestion'  => sprintf(
                            'Create content for the "%s" category which has only %d posts.',
                            $gap['name'],
                            $gap['post_count']
                        ),
                        'category_id' => $gap['id'],
                    );
                }

                foreach ( array_slice( $tag_gaps, 0, 5 ) as $gap ) {
                    $suggestions[] = array(
                        'type'       => 'tag_gap',
                        'priority'   => $gap['post_count'] === 0 ? 'high' : 'medium',
                        'suggestion' => sprintf(
                            'Write posts about "%s" - this tag has only %d posts.',
                            $gap['name'],
                            $gap['post_count']
                        ),
                        'tag_id'     => $gap['id'],
                    );
                }

                // Cross-reference suggestions
                if ( ! empty( $popular_categories ) && ! empty( $tag_gaps ) ) {
                    $top_category = $popular_categories[0];
                    foreach ( array_slice( $tag_gaps, 0, 3 ) as $tag_gap ) {
                        $suggestions[] = array(
                            'type'       => 'cross_reference',
                            'priority'   => 'medium',
                            'suggestion' => sprintf(
                                'Combine your popular category "%s" with the underused tag "%s" for new content.',
                                $top_category['name'],
                                $tag_gap['name']
                            ),
                        );
                    }
                }

                return array(
                    'analysis_summary' => array(
                        'total_categories'     => count( $categories ),
                        'total_tags'           => count( $tags ),
                        'category_gaps_count'  => count( $category_gaps ),
                        'tag_gaps_count'       => count( $tag_gaps ),
                        'threshold_used'       => $min_posts,
                    ),
                    'category_gaps'    => $category_gaps,
                    'tag_gaps'         => $tag_gaps,
                    'suggestions'      => $suggestions,
                    'popular_topics'   => array(
                        'categories' => array_slice( $popular_categories, 0, 10 ),
                        'tags'       => array_slice( $popular_tags, 0, 10 ),
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
