<?php
/**
 * Check SEO Quality Title and Content Ability
 * Registers the snn/check-seo-quality-title-and-content ability for the WordPress Abilities API
 * Analyzes titles and content for SEO quality issues
 */

// Register category
add_action( 'wp_abilities_api_categories_init', 'snn_register_seo_quality_category' );
function snn_register_seo_quality_category() {
    if ( ! wp_has_ability_category( 'seo-analysis' ) ) {
        wp_register_ability_category(
            'seo-analysis',
            array(
                'label'       => __( 'SEO Analysis', 'snn' ),
                'description' => __( 'Abilities for analyzing and improving SEO quality.', 'snn' ),
            )
        );
    }
}

// Register ability
add_action( 'wp_abilities_api_init', 'snn_register_check_seo_quality_ability' );
function snn_register_check_seo_quality_ability() {
    wp_register_ability(
        'snn/check-seo-quality-title-and-content',
        array(
            'label'       => __( 'Check SEO Quality', 'snn' ),
            'description' => __( 'Performs comprehensive SEO analysis on posts including title length validation (optimal 30-60 chars), content word count assessment, duplicate title detection, missing meta descriptions/excerpts, featured image presence, and overall SEO scoring. Identifies titles that are too long/short for search results, thin content below recommended word count, and provides actionable recommendations. Returns detailed SEO scores and prioritized list of posts needing optimization. Use this to audit content quality, find SEO issues, and improve search engine visibility.', 'snn' ),
            'category'    => 'seo-analysis',
            'input_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'post_id' => array(
                        'type'        => 'integer',
                        'description' => 'Specific post ID to analyze. If not provided, analyzes recent posts.',
                    ),
                    'post_type' => array(
                        'type'        => 'string',
                        'description' => 'Post type to analyze (default: post).',
                        'default'     => 'post',
                    ),
                    'limit' => array(
                        'type'        => 'integer',
                        'description' => 'Number of posts to analyze (default: 20).',
                        'default'     => 20,
                    ),
                    'title_min_length' => array(
                        'type'        => 'integer',
                        'description' => 'Minimum recommended title length in characters (default: 30).',
                        'default'     => 30,
                    ),
                    'title_max_length' => array(
                        'type'        => 'integer',
                        'description' => 'Maximum recommended title length in characters (default: 60).',
                        'default'     => 60,
                    ),
                    'content_min_words' => array(
                        'type'        => 'integer',
                        'description' => 'Minimum recommended content word count (default: 300).',
                        'default'     => 300,
                    ),
                ),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'posts_analyzed' => array( 'type' => 'integer' ),
                    'issues_found'   => array( 'type' => 'integer' ),
                    'posts_with_issues' => array( 'type' => 'array' ),
                    'seo_summary'    => array( 'type' => 'object' ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $post_type = isset( $input['post_type'] ) ? sanitize_text_field( $input['post_type'] ) : 'post';
                $limit = isset( $input['limit'] ) ? absint( $input['limit'] ) : 20;
                $title_min = isset( $input['title_min_length'] ) ? absint( $input['title_min_length'] ) : 30;
                $title_max = isset( $input['title_max_length'] ) ? absint( $input['title_max_length'] ) : 60;
                $content_min = isset( $input['content_min_words'] ) ? absint( $input['content_min_words'] ) : 300;

                $query_args = array(
                    'post_type'      => $post_type,
                    'post_status'    => 'publish',
                    'posts_per_page' => $limit,
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                );

                if ( ! empty( $input['post_id'] ) ) {
                    $query_args['p'] = absint( $input['post_id'] );
                    $query_args['posts_per_page'] = 1;
                }

                $posts = get_posts( $query_args );
                $posts_with_issues = array();
                $total_issues = 0;

                // Counters for summary
                $titles_too_short = 0;
                $titles_too_long = 0;
                $titles_optimal = 0;
                $content_too_short = 0;
                $missing_excerpts = 0;
                $missing_featured_images = 0;
                $duplicate_titles = array();

                // Track all titles for duplicate detection
                $all_titles = array();
                foreach ( $posts as $post ) {
                    $title_lower = strtolower( trim( $post->post_title ) );
                    if ( ! isset( $all_titles[ $title_lower ] ) ) {
                        $all_titles[ $title_lower ] = array();
                    }
                    $all_titles[ $title_lower ][] = $post->ID;
                }

                foreach ( $posts as $post ) {
                    $title = $post->post_title;
                    $title_length = strlen( $title );
                    $content = wp_strip_all_tags( $post->post_content );
                    $word_count = str_word_count( $content );
                    $has_excerpt = ! empty( $post->post_excerpt );
                    $has_featured_image = has_post_thumbnail( $post->ID );

                    $issues = array();
                    $warnings = array();
                    $score = 100; // Start with perfect score

                    // Title length checks
                    if ( $title_length < $title_min ) {
                        $issues[] = array(
                            'type'    => 'title_too_short',
                            'message' => sprintf(
                                'Title is too short (%d chars). Recommended: %d-%d characters.',
                                $title_length,
                                $title_min,
                                $title_max
                            ),
                            'severity' => 'warning',
                        );
                        $titles_too_short++;
                        $score -= 15;
                    } elseif ( $title_length > $title_max ) {
                        $issues[] = array(
                            'type'    => 'title_too_long',
                            'message' => sprintf(
                                'Title is too long (%d chars). Recommended: %d-%d characters. May be truncated in search results.',
                                $title_length,
                                $title_min,
                                $title_max
                            ),
                            'severity' => 'warning',
                        );
                        $titles_too_long++;
                        $score -= 10;
                    } else {
                        $titles_optimal++;
                    }

                    // Content length check
                    if ( $word_count < $content_min ) {
                        $issues[] = array(
                            'type'    => 'content_too_short',
                            'message' => sprintf(
                                'Content is thin (%d words). Recommended minimum: %d words for better SEO.',
                                $word_count,
                                $content_min
                            ),
                            'severity' => 'warning',
                        );
                        $content_too_short++;
                        $score -= 20;
                    }

                    // Meta description / excerpt check
                    if ( ! $has_excerpt ) {
                        $issues[] = array(
                            'type'    => 'missing_excerpt',
                            'message' => 'No excerpt/meta description set. Search engines may auto-generate one.',
                            'severity' => 'info',
                        );
                        $missing_excerpts++;
                        $score -= 10;
                    }

                    // Featured image check
                    if ( ! $has_featured_image ) {
                        $issues[] = array(
                            'type'    => 'missing_featured_image',
                            'message' => 'No featured image. Posts with images tend to perform better in search and social.',
                            'severity' => 'info',
                        );
                        $missing_featured_images++;
                        $score -= 5;
                    }

                    // Duplicate title check
                    $title_lower = strtolower( trim( $title ) );
                    if ( count( $all_titles[ $title_lower ] ) > 1 ) {
                        $other_ids = array_filter( $all_titles[ $title_lower ], function( $id ) use ( $post ) {
                            return $id !== $post->ID;
                        });
                        $issues[] = array(
                            'type'    => 'duplicate_title',
                            'message' => sprintf(
                                'Duplicate title found. Other posts with same title: %s',
                                implode( ', ', $other_ids )
                            ),
                            'severity' => 'error',
                        );
                        $score -= 25;
                    }

                    // Title keyword analysis
                    $title_words = str_word_count( $title );
                    if ( $title_words < 3 ) {
                        $warnings[] = 'Title has very few words. Consider adding more descriptive keywords.';
                        $score -= 5;
                    }

                    // Check for numbers in title (often good for CTR)
                    $has_numbers = preg_match( '/\d+/', $title );

                    // Check for power words
                    $power_words = array( 'best', 'guide', 'how', 'why', 'what', 'top', 'ultimate', 'complete', 'easy', 'quick', 'free', 'new', 'proven' );
                    $has_power_word = false;
                    foreach ( $power_words as $word ) {
                        if ( stripos( $title, $word ) !== false ) {
                            $has_power_word = true;
                            break;
                        }
                    }

                    // Ensure score doesn't go negative
                    $score = max( 0, $score );

                    $total_issues += count( $issues );

                    $post_data = array(
                        'id'          => $post->ID,
                        'title'       => $title,
                        'url'         => get_permalink( $post->ID ),
                        'seo_score'   => $score,
                        'title_analysis' => array(
                            'length'         => $title_length,
                            'word_count'     => $title_words,
                            'is_optimal'     => $title_length >= $title_min && $title_length <= $title_max,
                            'has_numbers'    => (bool) $has_numbers,
                            'has_power_word' => $has_power_word,
                        ),
                        'content_analysis' => array(
                            'word_count'          => $word_count,
                            'is_sufficient'       => $word_count >= $content_min,
                            'has_excerpt'         => $has_excerpt,
                            'has_featured_image'  => $has_featured_image,
                        ),
                        'issues'      => $issues,
                        'warnings'    => $warnings,
                    );

                    // Only include posts with issues or if analyzing a specific post
                    if ( ! empty( $issues ) || ! empty( $input['post_id'] ) ) {
                        $posts_with_issues[] = $post_data;
                    }
                }

                // Sort by SEO score (lowest first - most issues)
                usort( $posts_with_issues, function( $a, $b ) {
                    return $a['seo_score'] - $b['seo_score'];
                });

                return array(
                    'posts_analyzed'    => count( $posts ),
                    'issues_found'      => $total_issues,
                    'posts_with_issues' => $posts_with_issues,
                    'seo_summary'       => array(
                        'titles_too_short'       => $titles_too_short,
                        'titles_too_long'        => $titles_too_long,
                        'titles_optimal'         => $titles_optimal,
                        'content_too_short'      => $content_too_short,
                        'missing_excerpts'       => $missing_excerpts,
                        'missing_featured_images' => $missing_featured_images,
                        'average_score'          => count( $posts_with_issues ) > 0
                            ? round( array_sum( array_column( $posts_with_issues, 'seo_score' ) ) / count( $posts_with_issues ) )
                            : 100,
                        'thresholds_used'        => array(
                            'title_min_length'  => $title_min,
                            'title_max_length'  => $title_max,
                            'content_min_words' => $content_min,
                        ),
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
