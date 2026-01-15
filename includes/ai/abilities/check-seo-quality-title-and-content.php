<?php
/**
 * Check SEO Quality Title and Content Ability
 * Registers the snn/check-seo-quality-title-and-content ability for the WordPress Abilities API
 * Comprehensive SEO analysis including readability, keyword density, links, headings, and more
 * Supports all post types (posts, pages, custom post types) and taxonomies
 */

// Register ability
add_action( 'wp_abilities_api_init', 'snn_register_check_seo_quality_ability' );
function snn_register_check_seo_quality_ability() {
    wp_register_ability(
        'snn/check-seo-quality-title-and-content',
        array(
            'label'       => __( 'Check SEO Quality', 'snn' ),
            'description' => __( 'Performs comprehensive SEO analysis on posts (including custom post types) and taxonomy terms. Analysis includes: title optimization (length, power words, numbers), meta description quality, content analysis (word count, readability score, sentence/paragraph structure), keyword density analysis with focus keyword support, heading hierarchy (H1-H6 structure), internal/external link analysis, image alt text audit, URL/slug optimization, duplicate content detection, taxonomy term SEO analysis, and overall weighted SEO scoring. Supports any registered post type (post, page, product, portfolio, etc.) and taxonomy (category, tag, custom taxonomies). Returns detailed scores across multiple categories with actionable recommendations prioritized by impact. Use this to audit content quality, identify SEO issues, and get specific improvement suggestions.', 'snn' ),
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
                        'description' => 'Post type to analyze. Supports any registered post type: post, page, or custom post types like product, portfolio, event, etc. Use "any" to analyze all public post types. Default: post.',
                        'default'     => 'post',
                    ),
                    'taxonomy' => array(
                        'type'        => 'string',
                        'description' => 'Taxonomy to analyze terms from (e.g., category, post_tag, product_cat, or any custom taxonomy). When specified, analyzes taxonomy terms instead of posts.',
                    ),
                    'term_id' => array(
                        'type'        => 'integer',
                        'description' => 'Specific term ID to analyze. Requires taxonomy parameter.',
                    ),
                    'limit' => array(
                        'type'        => 'integer',
                        'description' => 'Number of posts or terms to analyze (default: 20).',
                        'default'     => 20,
                    ),
                    'focus_keyword' => array(
                        'type'        => 'string',
                        'description' => 'Optional focus keyword to check density and placement for.',
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
                    'description_min_length' => array(
                        'type'        => 'integer',
                        'description' => 'Minimum meta description length (default: 120).',
                        'default'     => 120,
                    ),
                    'description_max_length' => array(
                        'type'        => 'integer',
                        'description' => 'Maximum meta description length (default: 160).',
                        'default'     => 160,
                    ),
                    'include_all_posts' => array(
                        'type'        => 'boolean',
                        'description' => 'Include all posts/terms in results, not just those with issues (default: false).',
                        'default'     => false,
                    ),
                    'check_taxonomy_terms' => array(
                        'type'        => 'boolean',
                        'description' => 'When analyzing posts, also check if they have assigned taxonomy terms (categories, tags, etc.). Default: true.',
                        'default'     => true,
                    ),
                ),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'items_analyzed'       => array( 'type' => 'integer' ),
                    'analysis_type'        => array( 'type' => 'string' ),
                    'post_type'            => array( 'type' => 'string' ),
                    'taxonomy'             => array( 'type' => 'string' ),
                    'taxonomy_label'       => array( 'type' => 'string' ),
                    'issues_found'         => array( 'type' => 'integer' ),
                    'items_with_issues'    => array( 'type' => 'array' ),
                    'seo_summary'          => array( 'type' => 'object' ),
                    'category_scores'      => array( 'type' => 'object' ),
                    'available_post_types' => array( 'type' => 'array' ),
                    'available_taxonomies' => array( 'type' => 'array' ),
                ),
            ),
            'execute_callback' => 'snn_execute_seo_quality_check',
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

/**
 * Main execution callback for SEO quality check
 */
function snn_execute_seo_quality_check( $input ) {
    $post_type = isset( $input['post_type'] ) ? sanitize_text_field( $input['post_type'] ) : 'post';
    $taxonomy = isset( $input['taxonomy'] ) ? sanitize_text_field( $input['taxonomy'] ) : '';
    $term_id = isset( $input['term_id'] ) ? absint( $input['term_id'] ) : 0;
    $limit = isset( $input['limit'] ) ? absint( $input['limit'] ) : 20;
    $title_min = isset( $input['title_min_length'] ) ? absint( $input['title_min_length'] ) : 30;
    $title_max = isset( $input['title_max_length'] ) ? absint( $input['title_max_length'] ) : 60;
    $content_min = isset( $input['content_min_words'] ) ? absint( $input['content_min_words'] ) : 300;
    $desc_min = isset( $input['description_min_length'] ) ? absint( $input['description_min_length'] ) : 120;
    $desc_max = isset( $input['description_max_length'] ) ? absint( $input['description_max_length'] ) : 160;
    $focus_keyword = isset( $input['focus_keyword'] ) ? sanitize_text_field( $input['focus_keyword'] ) : '';
    $include_all = isset( $input['include_all_posts'] ) ? (bool) $input['include_all_posts'] : false;
    $check_taxonomy_terms = isset( $input['check_taxonomy_terms'] ) ? (bool) $input['check_taxonomy_terms'] : true;

    // Get available post types and taxonomies for reference
    $available_post_types = snn_get_available_post_types();
    $available_taxonomies = snn_get_available_taxonomies();

    // If taxonomy is specified, analyze taxonomy terms instead of posts
    if ( ! empty( $taxonomy ) ) {
        return snn_analyze_taxonomy_terms( $taxonomy, $term_id, $limit, $title_min, $title_max, $desc_min, $desc_max, $focus_keyword, $include_all, $available_post_types, $available_taxonomies );
    }

    // Handle "any" post type - get all public post types
    if ( $post_type === 'any' ) {
        $post_type = array_keys( $available_post_types );
    }

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
    $posts_results = array();
    $total_issues = 0;

    // Summary counters
    $summary = array(
        'titles_too_short'        => 0,
        'titles_too_long'         => 0,
        'titles_optimal'          => 0,
        'content_too_short'       => 0,
        'missing_excerpts'        => 0,
        'missing_featured_images' => 0,
        'poor_readability'        => 0,
        'missing_internal_links'  => 0,
        'missing_external_links'  => 0,
        'missing_h1'              => 0,
        'poor_heading_structure'  => 0,
        'images_missing_alt'      => 0,
        'poor_url_structure'      => 0,
        'low_keyword_density'     => 0,
        'high_keyword_density'    => 0,
    );

    // Category score aggregates
    $category_totals = array(
        'title'       => array( 'total' => 0, 'count' => 0 ),
        'content'     => array( 'total' => 0, 'count' => 0 ),
        'readability' => array( 'total' => 0, 'count' => 0 ),
        'technical'   => array( 'total' => 0, 'count' => 0 ),
        'keywords'    => array( 'total' => 0, 'count' => 0 ),
    );

    // Track all titles for duplicate detection
    $all_titles = array();
    foreach ( $posts as $post ) {
        $title_lower = strtolower( trim( $post->post_title ) );
        if ( ! isset( $all_titles[ $title_lower ] ) ) {
            $all_titles[ $title_lower ] = array();
        }
        $all_titles[ $title_lower ][] = $post->ID;
    }

    // Analyze each post
    foreach ( $posts as $post ) {
        $analysis = snn_analyze_single_post( $post, array(
            'title_min'            => $title_min,
            'title_max'            => $title_max,
            'content_min'          => $content_min,
            'desc_min'             => $desc_min,
            'desc_max'             => $desc_max,
            'focus_keyword'        => $focus_keyword,
            'all_titles'           => $all_titles,
            'check_taxonomy_terms' => $check_taxonomy_terms,
        ) );

        $total_issues += count( $analysis['issues'] );

        // Update summary counters
        foreach ( $analysis['summary_flags'] as $flag => $value ) {
            if ( $value && isset( $summary[ $flag ] ) ) {
                $summary[ $flag ]++;
            }
        }

        // Update category totals
        foreach ( $analysis['category_scores'] as $cat => $score ) {
            if ( isset( $category_totals[ $cat ] ) ) {
                $category_totals[ $cat ]['total'] += $score;
                $category_totals[ $cat ]['count']++;
            }
        }

        // Include post in results if it has issues or if include_all is true
        if ( ! empty( $analysis['issues'] ) || $include_all || ! empty( $input['post_id'] ) ) {
            $posts_results[] = $analysis['post_data'];
        }
    }

    // Sort by SEO score (lowest first - most issues)
    usort( $posts_results, function( $a, $b ) {
        return $a['seo_score'] - $b['seo_score'];
    } );

    // Calculate category averages
    $category_averages = array();
    foreach ( $category_totals as $cat => $data ) {
        $category_averages[ $cat ] = $data['count'] > 0
            ? round( $data['total'] / $data['count'] )
            : 100;
    }

    // Calculate overall average score
    $all_scores = array_column( $posts_results, 'seo_score' );
    $average_score = count( $all_scores ) > 0 ? round( array_sum( $all_scores ) / count( $all_scores ) ) : 100;

    // Determine actual post type(s) analyzed
    $analyzed_post_type = is_array( $post_type ) ? implode( ', ', $post_type ) : $post_type;

    return array(
        'items_analyzed'       => count( $posts ),
        'analysis_type'        => 'posts',
        'post_type'            => $analyzed_post_type,
        'taxonomy'             => '',
        'taxonomy_label'       => '',
        'issues_found'         => $total_issues,
        'items_with_issues'    => $posts_results,
        'seo_summary'          => array_merge( $summary, array(
            'average_score'   => $average_score,
            'thresholds_used' => array(
                'title_min_length'       => $title_min,
                'title_max_length'       => $title_max,
                'content_min_words'      => $content_min,
                'description_min_length' => $desc_min,
                'description_max_length' => $desc_max,
            ),
        ) ),
        'category_scores'      => $category_averages,
        'available_post_types' => $available_post_types,
        'available_taxonomies' => $available_taxonomies,
    );
}

/**
 * Analyze a single post for SEO quality
 */
function snn_analyze_single_post( $post, $config ) {
    $title = $post->post_title;
    $title_length = strlen( $title );
    $content_raw = $post->post_content;
    $content_text = wp_strip_all_tags( $content_raw );
    $word_count = str_word_count( $content_text );
    $has_excerpt = ! empty( $post->post_excerpt );
    $excerpt = $has_excerpt ? $post->post_excerpt : '';
    $has_featured_image = has_post_thumbnail( $post->ID );
    $permalink = get_permalink( $post->ID );
    $slug = $post->post_name;
    $post_type = $post->post_type;
    $post_type_obj = get_post_type_object( $post_type );
    $post_type_label = $post_type_obj ? $post_type_obj->labels->singular_name : $post_type;

    $issues = array();
    $recommendations = array();
    $summary_flags = array();

    // Initialize category scores (0-100 scale)
    $category_scores = array(
        'title'       => 100,
        'content'     => 100,
        'readability' => 100,
        'technical'   => 100,
        'keywords'    => 100,
    );

    // ==================== TITLE ANALYSIS ====================
    $title_analysis = snn_analyze_title( $title, $config['title_min'], $config['title_max'], $config['all_titles'], $post->ID );
    $issues = array_merge( $issues, $title_analysis['issues'] );
    $recommendations = array_merge( $recommendations, $title_analysis['recommendations'] );
    $category_scores['title'] = $title_analysis['score'];
    $summary_flags = array_merge( $summary_flags, $title_analysis['flags'] );

    // ==================== CONTENT ANALYSIS ====================
    $content_analysis = snn_analyze_content( $content_text, $word_count, $config['content_min'], $has_excerpt, $excerpt, $config['desc_min'], $config['desc_max'] );
    $issues = array_merge( $issues, $content_analysis['issues'] );
    $recommendations = array_merge( $recommendations, $content_analysis['recommendations'] );
    $category_scores['content'] = $content_analysis['score'];
    $summary_flags = array_merge( $summary_flags, $content_analysis['flags'] );

    // ==================== READABILITY ANALYSIS ====================
    $readability_analysis = snn_analyze_readability( $content_text );
    $issues = array_merge( $issues, $readability_analysis['issues'] );
    $recommendations = array_merge( $recommendations, $readability_analysis['recommendations'] );
    $category_scores['readability'] = $readability_analysis['score'];
    $summary_flags = array_merge( $summary_flags, $readability_analysis['flags'] );

    // ==================== TECHNICAL SEO ANALYSIS ====================
    $technical_analysis = snn_analyze_technical_seo( $content_raw, $slug, $has_featured_image, $post->ID );
    $issues = array_merge( $issues, $technical_analysis['issues'] );
    $recommendations = array_merge( $recommendations, $technical_analysis['recommendations'] );
    $category_scores['technical'] = $technical_analysis['score'];
    $summary_flags = array_merge( $summary_flags, $technical_analysis['flags'] );

    // ==================== TAXONOMY ASSIGNMENT CHECK ====================
    $check_taxonomy_terms = isset( $config['check_taxonomy_terms'] ) ? $config['check_taxonomy_terms'] : true;
    if ( $check_taxonomy_terms ) {
        $taxonomy_check = snn_check_post_taxonomy_assignment( $post->ID, $post_type );
        $issues = array_merge( $issues, $taxonomy_check['issues'] );
        $recommendations = array_merge( $recommendations, $taxonomy_check['recommendations'] );
        // Deduct points for missing taxonomies (minor)
        $category_scores['technical'] -= count( $taxonomy_check['issues'] ) * 3;
        $category_scores['technical'] = max( 0, $category_scores['technical'] );
    }

    // ==================== KEYWORD ANALYSIS ====================
    if ( ! empty( $config['focus_keyword'] ) ) {
        $keyword_analysis = snn_analyze_keywords( $title, $content_text, $config['focus_keyword'], $slug, $content_raw );
        $issues = array_merge( $issues, $keyword_analysis['issues'] );
        $recommendations = array_merge( $recommendations, $keyword_analysis['recommendations'] );
        $category_scores['keywords'] = $keyword_analysis['score'];
        $summary_flags = array_merge( $summary_flags, $keyword_analysis['flags'] );
    }

    // Calculate overall weighted score
    $weights = array(
        'title'       => 0.20,
        'content'     => 0.25,
        'readability' => 0.20,
        'technical'   => 0.20,
        'keywords'    => 0.15,
    );

    $overall_score = 0;
    foreach ( $category_scores as $cat => $score ) {
        $overall_score += $score * $weights[ $cat ];
    }
    $overall_score = round( max( 0, min( 100, $overall_score ) ) );

    // Sort recommendations by priority
    usort( $recommendations, function( $a, $b ) {
        $priority_order = array( 'high' => 1, 'medium' => 2, 'low' => 3 );
        $a_order = isset( $priority_order[ $a['priority'] ] ) ? $priority_order[ $a['priority'] ] : 4;
        $b_order = isset( $priority_order[ $b['priority'] ] ) ? $priority_order[ $b['priority'] ] : 4;
        return $a_order - $b_order;
    } );

    // Get assigned taxonomies for this post
    $assigned_terms = array();
    $post_taxonomies = get_object_taxonomies( $post_type, 'objects' );
    foreach ( $post_taxonomies as $tax ) {
        if ( $tax->public ) {
            $terms = get_the_terms( $post->ID, $tax->name );
            if ( $terms && ! is_wp_error( $terms ) ) {
                $assigned_terms[ $tax->name ] = array(
                    'label' => $tax->label,
                    'terms' => array_map( function( $t ) {
                        return array(
                            'id'   => $t->term_id,
                            'name' => $t->name,
                            'slug' => $t->slug,
                        );
                    }, $terms ),
                );
            }
        }
    }

    return array(
        'post_data' => array(
            'id'              => $post->ID,
            'title'           => $title,
            'url'             => $permalink,
            'slug'            => $slug,
            'post_type'       => $post_type,
            'post_type_label' => $post_type_label,
            'seo_score'       => $overall_score,
            'category_scores' => $category_scores,
            'title_analysis'  => array(
                'length'         => $title_length,
                'word_count'     => str_word_count( $title ),
                'is_optimal'     => $title_length >= $config['title_min'] && $title_length <= $config['title_max'],
                'has_numbers'    => (bool) preg_match( '/\d+/', $title ),
                'has_power_word' => snn_has_power_word( $title ),
            ),
            'content_analysis' => array(
                'word_count'         => $word_count,
                'is_sufficient'      => $word_count >= $config['content_min'],
                'has_excerpt'        => $has_excerpt,
                'has_featured_image' => $has_featured_image,
                'readability_score'  => $category_scores['readability'],
            ),
            'assigned_terms'  => $assigned_terms,
            'issues'          => $issues,
            'recommendations' => $recommendations,
        ),
        'issues'          => $issues,
        'summary_flags'   => $summary_flags,
        'category_scores' => $category_scores,
    );
}

/**
 * Analyze title for SEO
 */
function snn_analyze_title( $title, $min_length, $max_length, $all_titles, $post_id ) {
    $issues = array();
    $recommendations = array();
    $flags = array();
    $score = 100;
    $title_length = strlen( $title );
    $title_words = str_word_count( $title );

    // Title length check
    if ( $title_length < $min_length ) {
        $issues[] = array(
            'type'     => 'title_too_short',
            'message'  => sprintf( 'Title is too short (%d chars). Recommended: %d-%d characters.', $title_length, $min_length, $max_length ),
            'severity' => 'warning',
            'category' => 'title',
        );
        $recommendations[] = array(
            'action'   => 'Expand your title to include more descriptive keywords.',
            'priority' => 'high',
            'impact'   => 'Longer titles provide more context for search engines and users.',
        );
        $flags['titles_too_short'] = true;
        $score -= 20;
    } elseif ( $title_length > $max_length ) {
        $issues[] = array(
            'type'     => 'title_too_long',
            'message'  => sprintf( 'Title is too long (%d chars). May be truncated in search results. Recommended: %d-%d characters.', $title_length, $min_length, $max_length ),
            'severity' => 'warning',
            'category' => 'title',
        );
        $recommendations[] = array(
            'action'   => 'Shorten your title to prevent truncation in search results.',
            'priority' => 'medium',
            'impact'   => 'Truncated titles may lose important keywords and context.',
        );
        $flags['titles_too_long'] = true;
        $score -= 15;
    } else {
        $flags['titles_optimal'] = true;
    }

    // Title word count check
    if ( $title_words < 3 ) {
        $issues[] = array(
            'type'     => 'title_few_words',
            'message'  => sprintf( 'Title has only %d words. Consider adding more descriptive keywords.', $title_words ),
            'severity' => 'info',
            'category' => 'title',
        );
        $recommendations[] = array(
            'action'   => 'Add more descriptive words to your title for better keyword targeting.',
            'priority' => 'low',
            'impact'   => 'More words can help target multiple search queries.',
        );
        $score -= 10;
    }

    // Duplicate title check
    $title_lower = strtolower( trim( $title ) );
    if ( isset( $all_titles[ $title_lower ] ) && count( $all_titles[ $title_lower ] ) > 1 ) {
        $other_ids = array_filter( $all_titles[ $title_lower ], function( $id ) use ( $post_id ) {
            return $id !== $post_id;
        } );
        if ( ! empty( $other_ids ) ) {
            $issues[] = array(
                'type'     => 'duplicate_title',
                'message'  => sprintf( 'Duplicate title found. Other posts with same title: %s', implode( ', ', $other_ids ) ),
                'severity' => 'error',
                'category' => 'title',
            );
            $recommendations[] = array(
                'action'   => 'Create a unique title to avoid duplicate content issues.',
                'priority' => 'high',
                'impact'   => 'Duplicate titles confuse search engines and dilute ranking potential.',
            );
            $score -= 30;
        }
    }

    // Power words check
    if ( ! snn_has_power_word( $title ) ) {
        $issues[] = array(
            'type'     => 'no_power_words',
            'message'  => 'Title lacks power words that increase click-through rates.',
            'severity' => 'info',
            'category' => 'title',
        );
        $recommendations[] = array(
            'action'   => 'Consider adding power words like "Ultimate", "Complete", "Essential", "Proven", "Best".',
            'priority' => 'low',
            'impact'   => 'Power words can increase CTR by 10-20%.',
        );
        $score -= 5;
    }

    // Numbers in title check
    if ( ! preg_match( '/\d+/', $title ) ) {
        $issues[] = array(
            'type'     => 'no_numbers',
            'message'  => 'Title does not contain numbers. Titles with numbers often perform better.',
            'severity' => 'info',
            'category' => 'title',
        );
        $score -= 3;
    }

    // Question/How-to format check (often good for featured snippets)
    $question_patterns = array( 'how to', 'what is', 'why do', 'when to', 'where to', 'who is', 'which' );
    $has_question_format = false;
    foreach ( $question_patterns as $pattern ) {
        if ( stripos( $title, $pattern ) !== false ) {
            $has_question_format = true;
            break;
        }
    }

    return array(
        'issues'          => $issues,
        'recommendations' => $recommendations,
        'flags'           => $flags,
        'score'           => max( 0, $score ),
        'has_question'    => $has_question_format,
    );
}

/**
 * Analyze content quality
 */
function snn_analyze_content( $content_text, $word_count, $min_words, $has_excerpt, $excerpt, $desc_min, $desc_max ) {
    $issues = array();
    $recommendations = array();
    $flags = array();
    $score = 100;

    // Word count check
    if ( $word_count < $min_words ) {
        $severity = $word_count < 100 ? 'error' : 'warning';
        $issues[] = array(
            'type'     => 'content_too_short',
            'message'  => sprintf( 'Content is thin (%d words). Recommended minimum: %d words.', $word_count, $min_words ),
            'severity' => $severity,
            'category' => 'content',
        );
        $recommendations[] = array(
            'action'   => sprintf( 'Expand your content by %d words to improve comprehensiveness.', $min_words - $word_count ),
            'priority' => 'high',
            'impact'   => 'Longer, comprehensive content tends to rank better for more queries.',
        );
        $flags['content_too_short'] = true;
        $score -= $word_count < 100 ? 35 : 25;
    }

    // Excerpt/meta description check
    if ( ! $has_excerpt ) {
        $issues[] = array(
            'type'     => 'missing_excerpt',
            'message'  => 'No excerpt/meta description set. Search engines will auto-generate one.',
            'severity' => 'warning',
            'category' => 'content',
        );
        $recommendations[] = array(
            'action'   => sprintf( 'Add a compelling meta description between %d-%d characters.', $desc_min, $desc_max ),
            'priority' => 'medium',
            'impact'   => 'Custom meta descriptions improve CTR by providing relevant preview text.',
        );
        $flags['missing_excerpts'] = true;
        $score -= 15;
    } else {
        $excerpt_length = strlen( $excerpt );
        if ( $excerpt_length < $desc_min ) {
            $issues[] = array(
                'type'     => 'excerpt_too_short',
                'message'  => sprintf( 'Meta description is too short (%d chars). Recommended: %d-%d characters.', $excerpt_length, $desc_min, $desc_max ),
                'severity' => 'info',
                'category' => 'content',
            );
            $score -= 8;
        } elseif ( $excerpt_length > $desc_max ) {
            $issues[] = array(
                'type'     => 'excerpt_too_long',
                'message'  => sprintf( 'Meta description is too long (%d chars). May be truncated. Recommended: %d-%d characters.', $excerpt_length, $desc_min, $desc_max ),
                'severity' => 'info',
                'category' => 'content',
            );
            $score -= 5;
        }
    }

    return array(
        'issues'          => $issues,
        'recommendations' => $recommendations,
        'flags'           => $flags,
        'score'           => max( 0, $score ),
    );
}

/**
 * Analyze content readability
 */
function snn_analyze_readability( $content_text ) {
    $issues = array();
    $recommendations = array();
    $flags = array();
    $score = 100;

    if ( strlen( $content_text ) < 100 ) {
        return array(
            'issues'          => $issues,
            'recommendations' => $recommendations,
            'flags'           => array( 'poor_readability' => false ),
            'score'           => $score,
            'metrics'         => array(),
        );
    }

    // Calculate readability metrics
    $sentences = preg_split( '/[.!?]+/', $content_text, -1, PREG_SPLIT_NO_EMPTY );
    $sentence_count = count( $sentences );
    $words = str_word_count( $content_text, 1 );
    $word_count = count( $words );
    $syllable_count = snn_count_syllables( $content_text );

    // Average sentence length
    $avg_sentence_length = $sentence_count > 0 ? $word_count / $sentence_count : 0;

    // Average word length (syllables)
    $avg_syllables_per_word = $word_count > 0 ? $syllable_count / $word_count : 0;

    // Flesch Reading Ease Score
    // Formula: 206.835 - (1.015 * ASL) - (84.6 * ASW)
    // ASL = Average Sentence Length, ASW = Average Syllables per Word
    $flesch_score = 0;
    if ( $sentence_count > 0 && $word_count > 0 ) {
        $flesch_score = 206.835 - ( 1.015 * $avg_sentence_length ) - ( 84.6 * $avg_syllables_per_word );
        $flesch_score = max( 0, min( 100, $flesch_score ) );
    }

    // Flesch-Kincaid Grade Level
    $fk_grade = 0;
    if ( $sentence_count > 0 && $word_count > 0 ) {
        $fk_grade = ( 0.39 * $avg_sentence_length ) + ( 11.8 * $avg_syllables_per_word ) - 15.59;
        $fk_grade = max( 0, $fk_grade );
    }

    // Paragraph analysis
    $paragraphs = preg_split( '/\n\s*\n/', $content_text, -1, PREG_SPLIT_NO_EMPTY );
    $paragraph_count = count( $paragraphs );
    $avg_paragraph_length = $paragraph_count > 0 ? $word_count / $paragraph_count : $word_count;

    // Long sentence check (sentences over 25 words)
    $long_sentences = 0;
    foreach ( $sentences as $sentence ) {
        if ( str_word_count( $sentence ) > 25 ) {
            $long_sentences++;
        }
    }
    $long_sentence_percentage = $sentence_count > 0 ? ( $long_sentences / $sentence_count ) * 100 : 0;

    // Issues based on readability metrics
    if ( $flesch_score < 30 ) {
        $issues[] = array(
            'type'     => 'very_difficult_readability',
            'message'  => sprintf( 'Content is very difficult to read (Flesch score: %.1f). Aim for 60+ for general audience.', $flesch_score ),
            'severity' => 'warning',
            'category' => 'readability',
        );
        $recommendations[] = array(
            'action'   => 'Simplify your writing: use shorter sentences and simpler words.',
            'priority' => 'high',
            'impact'   => 'Difficult content has higher bounce rates and lower engagement.',
        );
        $flags['poor_readability'] = true;
        $score -= 30;
    } elseif ( $flesch_score < 50 ) {
        $issues[] = array(
            'type'     => 'difficult_readability',
            'message'  => sprintf( 'Content is fairly difficult to read (Flesch score: %.1f). Consider simplifying.', $flesch_score ),
            'severity' => 'info',
            'category' => 'readability',
        );
        $recommendations[] = array(
            'action'   => 'Consider breaking up complex sentences and using simpler vocabulary.',
            'priority' => 'medium',
            'impact'   => 'Easier content typically ranks better and engages more readers.',
        );
        $flags['poor_readability'] = true;
        $score -= 15;
    }

    if ( $avg_sentence_length > 25 ) {
        $issues[] = array(
            'type'     => 'long_sentences',
            'message'  => sprintf( 'Average sentence length is too high (%.1f words). Aim for 15-20 words.', $avg_sentence_length ),
            'severity' => 'info',
            'category' => 'readability',
        );
        $recommendations[] = array(
            'action'   => 'Break long sentences into shorter ones for better readability.',
            'priority' => 'medium',
            'impact'   => 'Shorter sentences are easier to understand and keep readers engaged.',
        );
        $score -= 10;
    }

    if ( $long_sentence_percentage > 30 ) {
        $issues[] = array(
            'type'     => 'too_many_long_sentences',
            'message'  => sprintf( '%.0f%% of sentences are over 25 words. Try to keep this under 25%%.', $long_sentence_percentage ),
            'severity' => 'info',
            'category' => 'readability',
        );
        $score -= 8;
    }

    if ( $avg_paragraph_length > 150 ) {
        $issues[] = array(
            'type'     => 'long_paragraphs',
            'message'  => sprintf( 'Average paragraph length is high (%.0f words). Consider breaking up large blocks of text.', $avg_paragraph_length ),
            'severity' => 'info',
            'category' => 'readability',
        );
        $recommendations[] = array(
            'action'   => 'Break up long paragraphs into smaller chunks of 3-4 sentences each.',
            'priority' => 'low',
            'impact'   => 'Shorter paragraphs improve scannability and reduce reader fatigue.',
        );
        $score -= 8;
    }

    return array(
        'issues'          => $issues,
        'recommendations' => $recommendations,
        'flags'           => $flags,
        'score'           => max( 0, $score ),
        'metrics'         => array(
            'flesch_score'            => round( $flesch_score, 1 ),
            'fk_grade_level'          => round( $fk_grade, 1 ),
            'avg_sentence_length'     => round( $avg_sentence_length, 1 ),
            'avg_paragraph_length'    => round( $avg_paragraph_length, 1 ),
            'long_sentence_percent'   => round( $long_sentence_percentage, 1 ),
            'sentence_count'          => $sentence_count,
            'paragraph_count'         => $paragraph_count,
        ),
    );
}

/**
 * Analyze technical SEO factors
 */
function snn_analyze_technical_seo( $content_raw, $slug, $has_featured_image, $post_id ) {
    $issues = array();
    $recommendations = array();
    $flags = array();
    $score = 100;

    // ==================== HEADING STRUCTURE ====================
    preg_match_all( '/<h([1-6])[^>]*>(.*?)<\/h\1>/si', $content_raw, $headings, PREG_SET_ORDER );

    $heading_counts = array( 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0 );
    $heading_hierarchy = array();

    foreach ( $headings as $heading ) {
        $level = intval( $heading[1] );
        $heading_counts[ $level ]++;
        $heading_hierarchy[] = array(
            'level' => $level,
            'text'  => wp_strip_all_tags( $heading[2] ),
        );
    }

    // Check for H1
    if ( $heading_counts[1] === 0 ) {
        $issues[] = array(
            'type'     => 'missing_h1',
            'message'  => 'No H1 heading found in content. Each page should have exactly one H1.',
            'severity' => 'warning',
            'category' => 'technical',
        );
        $recommendations[] = array(
            'action'   => 'Add a single H1 heading that describes the main topic of the content.',
            'priority' => 'high',
            'impact'   => 'H1 is a primary ranking signal for search engines.',
        );
        $flags['missing_h1'] = true;
        $score -= 20;
    } elseif ( $heading_counts[1] > 1 ) {
        $issues[] = array(
            'type'     => 'multiple_h1',
            'message'  => sprintf( 'Multiple H1 headings found (%d). Use only one H1 per page.', $heading_counts[1] ),
            'severity' => 'warning',
            'category' => 'technical',
        );
        $recommendations[] = array(
            'action'   => 'Convert extra H1 headings to H2 or other appropriate levels.',
            'priority' => 'medium',
            'impact'   => 'Multiple H1s can confuse search engines about the page topic.',
        );
        $flags['poor_heading_structure'] = true;
        $score -= 15;
    }

    // Check heading hierarchy (no skipping levels)
    $prev_level = 0;
    $hierarchy_issues = 0;
    foreach ( $heading_hierarchy as $h ) {
        if ( $prev_level > 0 && $h['level'] > $prev_level + 1 ) {
            $hierarchy_issues++;
        }
        $prev_level = $h['level'];
    }

    if ( $hierarchy_issues > 0 ) {
        $issues[] = array(
            'type'     => 'heading_hierarchy_skip',
            'message'  => sprintf( 'Heading hierarchy has gaps (skipped levels %d times). Follow H1 > H2 > H3 order.', $hierarchy_issues ),
            'severity' => 'info',
            'category' => 'technical',
        );
        $flags['poor_heading_structure'] = true;
        $score -= 8;
    }

    // Check for subheadings in long content
    $word_count = str_word_count( wp_strip_all_tags( $content_raw ) );
    $total_subheadings = $heading_counts[2] + $heading_counts[3] + $heading_counts[4];
    if ( $word_count > 300 && $total_subheadings === 0 ) {
        $issues[] = array(
            'type'     => 'no_subheadings',
            'message'  => 'No subheadings (H2-H4) found in content. Use subheadings to structure your content.',
            'severity' => 'info',
            'category' => 'technical',
        );
        $recommendations[] = array(
            'action'   => 'Add H2 and H3 subheadings to break up content and improve structure.',
            'priority' => 'medium',
            'impact'   => 'Subheadings improve readability and help search engines understand content structure.',
        );
        $score -= 10;
    }

    // ==================== LINK ANALYSIS ====================
    preg_match_all( '/<a[^>]+href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/si', $content_raw, $links, PREG_SET_ORDER );

    $internal_links = 0;
    $external_links = 0;
    $nofollow_links = 0;
    $site_url = home_url();

    foreach ( $links as $link ) {
        $href = $link[1];

        // Skip anchor links and javascript
        if ( strpos( $href, '#' ) === 0 || strpos( $href, 'javascript:' ) === 0 ) {
            continue;
        }

        if ( strpos( $href, $site_url ) === 0 || ( strpos( $href, 'http' ) !== 0 && strpos( $href, '//' ) !== 0 ) ) {
            $internal_links++;
        } else {
            $external_links++;
        }

        if ( stripos( $link[0], 'nofollow' ) !== false ) {
            $nofollow_links++;
        }
    }

    if ( $internal_links === 0 && $word_count > 200 ) {
        $issues[] = array(
            'type'     => 'no_internal_links',
            'message'  => 'No internal links found. Internal linking helps distribute page authority.',
            'severity' => 'warning',
            'category' => 'technical',
        );
        $recommendations[] = array(
            'action'   => 'Add 2-3 relevant internal links to other content on your site.',
            'priority' => 'high',
            'impact'   => 'Internal links improve site navigation and help search engines discover content.',
        );
        $flags['missing_internal_links'] = true;
        $score -= 15;
    }

    if ( $external_links === 0 && $word_count > 500 ) {
        $issues[] = array(
            'type'     => 'no_external_links',
            'message'  => 'No external links found. Linking to authoritative sources can improve credibility.',
            'severity' => 'info',
            'category' => 'technical',
        );
        $recommendations[] = array(
            'action'   => 'Consider adding 1-2 links to authoritative external sources.',
            'priority' => 'low',
            'impact'   => 'External links to quality sources can improve content credibility.',
        );
        $flags['missing_external_links'] = true;
        $score -= 5;
    }

    // ==================== IMAGE ANALYSIS ====================
    preg_match_all( '/<img[^>]+>/si', $content_raw, $images );
    $image_count = count( $images[0] );
    $images_without_alt = 0;

    foreach ( $images[0] as $img ) {
        if ( ! preg_match( '/alt=["\'][^"\']+["\']/', $img ) ) {
            $images_without_alt++;
        }
    }

    if ( $images_without_alt > 0 ) {
        $issues[] = array(
            'type'     => 'images_missing_alt',
            'message'  => sprintf( '%d image(s) missing alt text. Alt text improves accessibility and image SEO.', $images_without_alt ),
            'severity' => 'warning',
            'category' => 'technical',
        );
        $recommendations[] = array(
            'action'   => 'Add descriptive alt text to all images, including target keywords where relevant.',
            'priority' => 'medium',
            'impact'   => 'Alt text helps search engines understand images and improves accessibility.',
        );
        $flags['images_missing_alt'] = true;
        $score -= min( 15, $images_without_alt * 5 );
    }

    // Featured image check
    if ( ! $has_featured_image ) {
        $issues[] = array(
            'type'     => 'missing_featured_image',
            'message'  => 'No featured image set. Featured images improve social sharing and visual appeal.',
            'severity' => 'info',
            'category' => 'technical',
        );
        $recommendations[] = array(
            'action'   => 'Add a featured image that represents your content visually.',
            'priority' => 'medium',
            'impact'   => 'Featured images improve social media shares and visual search results.',
        );
        $flags['missing_featured_images'] = true;
        $score -= 8;
    }

    // ==================== URL/SLUG ANALYSIS ====================
    $slug_length = strlen( $slug );
    $slug_words = count( explode( '-', $slug ) );

    if ( $slug_length > 75 ) {
        $issues[] = array(
            'type'     => 'slug_too_long',
            'message'  => sprintf( 'URL slug is too long (%d chars). Keep URLs under 75 characters.', $slug_length ),
            'severity' => 'info',
            'category' => 'technical',
        );
        $recommendations[] = array(
            'action'   => 'Shorten your URL to include only essential keywords.',
            'priority' => 'low',
            'impact'   => 'Shorter URLs are easier to share and may perform better in search results.',
        );
        $flags['poor_url_structure'] = true;
        $score -= 8;
    }

    if ( preg_match( '/[0-9]{4,}/', $slug ) ) {
        $issues[] = array(
            'type'     => 'slug_has_dates',
            'message'  => 'URL contains date-like numbers which may make content appear outdated.',
            'severity' => 'info',
            'category' => 'technical',
        );
        $score -= 5;
    }

    // Check for stop words in slug
    $stop_words = array( 'a', 'an', 'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by' );
    $slug_parts = explode( '-', $slug );
    $stop_word_count = 0;
    foreach ( $slug_parts as $part ) {
        if ( in_array( strtolower( $part ), $stop_words, true ) ) {
            $stop_word_count++;
        }
    }

    if ( $stop_word_count > 2 ) {
        $issues[] = array(
            'type'     => 'slug_stop_words',
            'message'  => sprintf( 'URL contains %d stop words. Remove unnecessary words from URL.', $stop_word_count ),
            'severity' => 'info',
            'category' => 'technical',
        );
        $score -= 5;
    }

    return array(
        'issues'          => $issues,
        'recommendations' => $recommendations,
        'flags'           => $flags,
        'score'           => max( 0, $score ),
        'metrics'         => array(
            'heading_counts'      => $heading_counts,
            'internal_links'      => $internal_links,
            'external_links'      => $external_links,
            'image_count'         => $image_count,
            'images_without_alt'  => $images_without_alt,
            'slug_length'         => $slug_length,
        ),
    );
}

/**
 * Analyze keyword usage and density
 */
function snn_analyze_keywords( $title, $content_text, $focus_keyword, $slug, $content_raw ) {
    $issues = array();
    $recommendations = array();
    $flags = array();
    $score = 100;

    $keyword_lower = strtolower( $focus_keyword );
    $content_lower = strtolower( $content_text );
    $title_lower = strtolower( $title );
    $slug_lower = strtolower( $slug );

    // Count keyword occurrences
    $keyword_count = substr_count( $content_lower, $keyword_lower );
    $word_count = str_word_count( $content_text );

    // Calculate keyword density
    $keyword_word_count = str_word_count( $focus_keyword );
    $density = $word_count > 0 ? ( $keyword_count * $keyword_word_count / $word_count ) * 100 : 0;

    // Keyword in title
    $keyword_in_title = stripos( $title_lower, $keyword_lower ) !== false;
    if ( ! $keyword_in_title ) {
        $issues[] = array(
            'type'     => 'keyword_not_in_title',
            'message'  => sprintf( 'Focus keyword "%s" not found in title.', $focus_keyword ),
            'severity' => 'warning',
            'category' => 'keywords',
        );
        $recommendations[] = array(
            'action'   => sprintf( 'Include the focus keyword "%s" in your title, preferably near the beginning.', $focus_keyword ),
            'priority' => 'high',
            'impact'   => 'Title is one of the most important places for your target keyword.',
        );
        $score -= 25;
    }

    // Keyword in slug
    $keyword_in_slug = stripos( $slug_lower, str_replace( ' ', '-', $keyword_lower ) ) !== false;
    if ( ! $keyword_in_slug ) {
        $issues[] = array(
            'type'     => 'keyword_not_in_slug',
            'message'  => sprintf( 'Focus keyword "%s" not found in URL slug.', $focus_keyword ),
            'severity' => 'info',
            'category' => 'keywords',
        );
        $recommendations[] = array(
            'action'   => 'Include the focus keyword in your URL slug.',
            'priority' => 'medium',
            'impact'   => 'Keywords in URLs provide relevance signals to search engines.',
        );
        $score -= 10;
    }

    // Keyword in first paragraph (first 150 words)
    $first_paragraph = implode( ' ', array_slice( str_word_count( $content_text, 1 ), 0, 150 ) );
    $keyword_in_intro = stripos( strtolower( $first_paragraph ), $keyword_lower ) !== false;
    if ( ! $keyword_in_intro ) {
        $issues[] = array(
            'type'     => 'keyword_not_in_intro',
            'message'  => sprintf( 'Focus keyword "%s" not found in the introduction (first 150 words).', $focus_keyword ),
            'severity' => 'info',
            'category' => 'keywords',
        );
        $recommendations[] = array(
            'action'   => 'Mention your focus keyword naturally in the first paragraph.',
            'priority' => 'medium',
            'impact'   => 'Early keyword placement signals topic relevance to search engines.',
        );
        $score -= 10;
    }

    // Keyword in headings
    preg_match_all( '/<h[1-6][^>]*>(.*?)<\/h[1-6]>/si', $content_raw, $headings );
    $keyword_in_headings = false;
    if ( ! empty( $headings[1] ) ) {
        foreach ( $headings[1] as $heading_text ) {
            if ( stripos( wp_strip_all_tags( $heading_text ), $keyword_lower ) !== false ) {
                $keyword_in_headings = true;
                break;
            }
        }
    }

    if ( ! $keyword_in_headings ) {
        $issues[] = array(
            'type'     => 'keyword_not_in_headings',
            'message'  => sprintf( 'Focus keyword "%s" not found in any headings.', $focus_keyword ),
            'severity' => 'info',
            'category' => 'keywords',
        );
        $recommendations[] = array(
            'action'   => 'Include the focus keyword in at least one subheading (H2 or H3).',
            'priority' => 'low',
            'impact'   => 'Keywords in headings help structure and reinforce topic relevance.',
        );
        $score -= 8;
    }

    // Keyword density analysis
    if ( $density < 0.5 && $word_count > 300 ) {
        $issues[] = array(
            'type'     => 'low_keyword_density',
            'message'  => sprintf( 'Keyword density is low (%.1f%%). Aim for 1-2%% for optimal results.', $density ),
            'severity' => 'info',
            'category' => 'keywords',
        );
        $recommendations[] = array(
            'action'   => sprintf( 'Increase usage of "%s" naturally throughout the content.', $focus_keyword ),
            'priority' => 'medium',
            'impact'   => 'Higher (but natural) keyword density reinforces topic relevance.',
        );
        $flags['low_keyword_density'] = true;
        $score -= 10;
    } elseif ( $density > 3 ) {
        $issues[] = array(
            'type'     => 'high_keyword_density',
            'message'  => sprintf( 'Keyword density is too high (%.1f%%). This may appear as keyword stuffing.', $density ),
            'severity' => 'warning',
            'category' => 'keywords',
        );
        $recommendations[] = array(
            'action'   => 'Reduce keyword usage and use synonyms and related terms instead.',
            'priority' => 'high',
            'impact'   => 'Keyword stuffing can result in ranking penalties.',
        );
        $flags['high_keyword_density'] = true;
        $score -= 20;
    }

    return array(
        'issues'          => $issues,
        'recommendations' => $recommendations,
        'flags'           => $flags,
        'score'           => max( 0, $score ),
        'metrics'         => array(
            'keyword'            => $focus_keyword,
            'keyword_count'      => $keyword_count,
            'density'            => round( $density, 2 ),
            'in_title'           => $keyword_in_title,
            'in_slug'            => $keyword_in_slug,
            'in_intro'           => $keyword_in_intro,
            'in_headings'        => $keyword_in_headings,
        ),
    );
}

/**
 * Check if title contains power words
 */
function snn_has_power_word( $title ) {
    $power_words = array(
        'best', 'guide', 'how', 'why', 'what', 'top', 'ultimate', 'complete',
        'easy', 'quick', 'free', 'new', 'proven', 'essential', 'simple',
        'powerful', 'effective', 'amazing', 'incredible', 'secret', 'expert',
        'step-by-step', 'comprehensive', 'definitive', 'exclusive', 'insider',
        'must-have', 'game-changing', 'revolutionary', 'breakthrough', 'critical',
    );

    $title_lower = strtolower( $title );
    foreach ( $power_words as $word ) {
        if ( stripos( $title_lower, $word ) !== false ) {
            return true;
        }
    }
    return false;
}

/**
 * Count syllables in text (approximate)
 */
function snn_count_syllables( $text ) {
    $text = strtolower( $text );
    $text = preg_replace( '/[^a-z\s]/', '', $text );
    $words = preg_split( '/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY );

    $total_syllables = 0;

    foreach ( $words as $word ) {
        $syllables = snn_count_word_syllables( $word );
        $total_syllables += $syllables;
    }

    return $total_syllables;
}

/**
 * Count syllables in a single word
 */
function snn_count_word_syllables( $word ) {
    $word = strtolower( trim( $word ) );

    if ( strlen( $word ) <= 3 ) {
        return 1;
    }

    // Remove silent e at end
    $word = preg_replace( '/e$/', '', $word );

    // Count vowel groups
    preg_match_all( '/[aeiouy]+/', $word, $matches );
    $count = count( $matches[0] );

    // Handle special cases
    // Words ending in 'le' with a consonant before
    if ( preg_match( '/[^aeiouy]le$/', $word ) ) {
        $count++;
    }

    return max( 1, $count );
}

/**
 * Get available public post types
 */
function snn_get_available_post_types() {
    $post_types = get_post_types( array( 'public' => true ), 'objects' );
    $result = array();

    foreach ( $post_types as $post_type ) {
        $result[ $post_type->name ] = array(
            'label'        => $post_type->label,
            'singular'     => $post_type->labels->singular_name,
            'has_archive'  => $post_type->has_archive,
            'hierarchical' => $post_type->hierarchical,
            'taxonomies'   => get_object_taxonomies( $post_type->name ),
        );
    }

    return $result;
}

/**
 * Get available public taxonomies
 */
function snn_get_available_taxonomies() {
    $taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
    $result = array();

    foreach ( $taxonomies as $taxonomy ) {
        $result[ $taxonomy->name ] = array(
            'label'        => $taxonomy->label,
            'singular'     => $taxonomy->labels->singular_name,
            'hierarchical' => $taxonomy->hierarchical,
            'post_types'   => $taxonomy->object_type,
        );
    }

    return $result;
}

/**
 * Analyze taxonomy terms for SEO
 */
function snn_analyze_taxonomy_terms( $taxonomy, $term_id, $limit, $title_min, $title_max, $desc_min, $desc_max, $focus_keyword, $include_all, $available_post_types, $available_taxonomies ) {
    // Validate taxonomy exists
    if ( ! taxonomy_exists( $taxonomy ) ) {
        return array(
            'items_analyzed'       => 0,
            'analysis_type'        => 'taxonomy_terms',
            'post_type'            => '',
            'taxonomy'             => $taxonomy,
            'taxonomy_label'       => '',
            'issues_found'         => 0,
            'items_with_issues'    => array(),
            'seo_summary'          => array(
                'error'   => true,
                'message' => sprintf( 'Taxonomy "%s" does not exist.', $taxonomy ),
            ),
            'category_scores'      => array(),
            'available_post_types' => $available_post_types,
            'available_taxonomies' => $available_taxonomies,
        );
    }

    $term_args = array(
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
        'number'     => $limit,
    );

    if ( $term_id > 0 ) {
        $term_args['include'] = array( $term_id );
        $term_args['number'] = 1;
    }

    $terms = get_terms( $term_args );

    if ( is_wp_error( $terms ) ) {
        return array(
            'items_analyzed'       => 0,
            'analysis_type'        => 'taxonomy_terms',
            'post_type'            => '',
            'taxonomy'             => $taxonomy,
            'taxonomy_label'       => isset( $available_taxonomies[ $taxonomy ] ) ? $available_taxonomies[ $taxonomy ]['label'] : $taxonomy,
            'issues_found'         => 0,
            'items_with_issues'    => array(),
            'seo_summary'          => array(
                'error'   => true,
                'message' => $terms->get_error_message(),
            ),
            'category_scores'      => array(),
            'available_post_types' => $available_post_types,
            'available_taxonomies' => $available_taxonomies,
        );
    }

    $terms_results = array();
    $total_issues = 0;

    // Summary counters for taxonomy terms
    $summary = array(
        'names_too_short'        => 0,
        'names_too_long'         => 0,
        'names_optimal'          => 0,
        'missing_descriptions'   => 0,
        'descriptions_too_short' => 0,
        'descriptions_too_long'  => 0,
        'empty_terms'            => 0,
        'poor_slug_structure'    => 0,
    );

    // Track all names for duplicate detection
    $all_names = array();
    foreach ( $terms as $term ) {
        $name_lower = strtolower( trim( $term->name ) );
        if ( ! isset( $all_names[ $name_lower ] ) ) {
            $all_names[ $name_lower ] = array();
        }
        $all_names[ $name_lower ][] = $term->term_id;
    }

    foreach ( $terms as $term ) {
        $analysis = snn_analyze_single_term( $term, $taxonomy, array(
            'title_min'     => $title_min,
            'title_max'     => $title_max,
            'desc_min'      => $desc_min,
            'desc_max'      => $desc_max,
            'focus_keyword' => $focus_keyword,
            'all_names'     => $all_names,
        ) );

        $total_issues += count( $analysis['issues'] );

        // Update summary counters
        foreach ( $analysis['summary_flags'] as $flag => $value ) {
            if ( $value && isset( $summary[ $flag ] ) ) {
                $summary[ $flag ]++;
            }
        }

        // Include term in results if it has issues or if include_all is true
        if ( ! empty( $analysis['issues'] ) || $include_all || $term_id > 0 ) {
            $terms_results[] = $analysis['term_data'];
        }
    }

    // Sort by SEO score (lowest first - most issues)
    usort( $terms_results, function( $a, $b ) {
        return $a['seo_score'] - $b['seo_score'];
    } );

    // Calculate average score
    $all_scores = array_column( $terms_results, 'seo_score' );
    $average_score = count( $all_scores ) > 0 ? round( array_sum( $all_scores ) / count( $all_scores ) ) : 100;

    return array(
        'items_analyzed'       => count( $terms ),
        'analysis_type'        => 'taxonomy_terms',
        'post_type'            => '',
        'taxonomy'             => $taxonomy,
        'taxonomy_label'       => isset( $available_taxonomies[ $taxonomy ] ) ? $available_taxonomies[ $taxonomy ]['label'] : $taxonomy,
        'issues_found'         => $total_issues,
        'items_with_issues'    => $terms_results,
        'seo_summary'          => array_merge( $summary, array(
            'average_score'   => $average_score,
            'thresholds_used' => array(
                'title_min_length'       => $title_min,
                'title_max_length'       => $title_max,
                'description_min_length' => $desc_min,
                'description_max_length' => $desc_max,
            ),
        ) ),
        'category_scores'      => array(),
        'available_post_types' => $available_post_types,
        'available_taxonomies' => $available_taxonomies,
    );
}

/**
 * Analyze a single taxonomy term for SEO
 */
function snn_analyze_single_term( $term, $taxonomy, $config ) {
    $name = $term->name;
    $name_length = strlen( $name );
    $description = $term->description;
    $slug = $term->slug;
    $post_count = $term->count;

    $issues = array();
    $recommendations = array();
    $summary_flags = array();
    $score = 100;

    // ==================== NAME/TITLE ANALYSIS ====================
    if ( $name_length < $config['title_min'] ) {
        $issues[] = array(
            'type'     => 'name_too_short',
            'message'  => sprintf( 'Term name is too short (%d chars). Recommended: %d-%d characters.', $name_length, $config['title_min'], $config['title_max'] ),
            'severity' => 'warning',
            'category' => 'title',
        );
        $recommendations[] = array(
            'action'   => 'Consider a more descriptive term name that includes relevant keywords.',
            'priority' => 'medium',
            'impact'   => 'Descriptive term names help with search visibility and user navigation.',
        );
        $summary_flags['names_too_short'] = true;
        $score -= 15;
    } elseif ( $name_length > $config['title_max'] ) {
        $issues[] = array(
            'type'     => 'name_too_long',
            'message'  => sprintf( 'Term name is too long (%d chars). May be truncated. Recommended: %d-%d characters.', $name_length, $config['title_min'], $config['title_max'] ),
            'severity' => 'info',
            'category' => 'title',
        );
        $summary_flags['names_too_long'] = true;
        $score -= 8;
    } else {
        $summary_flags['names_optimal'] = true;
    }

    // Duplicate name check
    $name_lower = strtolower( trim( $name ) );
    if ( isset( $config['all_names'][ $name_lower ] ) && count( $config['all_names'][ $name_lower ] ) > 1 ) {
        $other_ids = array_filter( $config['all_names'][ $name_lower ], function( $id ) use ( $term ) {
            return $id !== $term->term_id;
        } );
        if ( ! empty( $other_ids ) ) {
            $issues[] = array(
                'type'     => 'duplicate_name',
                'message'  => sprintf( 'Duplicate term name found. Other terms with same name: %s', implode( ', ', $other_ids ) ),
                'severity' => 'error',
                'category' => 'title',
            );
            $recommendations[] = array(
                'action'   => 'Create unique term names to avoid confusion and duplicate content issues.',
                'priority' => 'high',
                'impact'   => 'Duplicate names confuse users and search engines.',
            );
            $score -= 25;
        }
    }

    // ==================== DESCRIPTION ANALYSIS ====================
    if ( empty( $description ) ) {
        $issues[] = array(
            'type'     => 'missing_description',
            'message'  => 'No term description set. This is used as meta description for archive pages.',
            'severity' => 'warning',
            'category' => 'content',
        );
        $recommendations[] = array(
            'action'   => sprintf( 'Add a description between %d-%d characters explaining this term.', $config['desc_min'], $config['desc_max'] ),
            'priority' => 'high',
            'impact'   => 'Descriptions improve archive page SEO and help users understand the term.',
        );
        $summary_flags['missing_descriptions'] = true;
        $score -= 25;
    } else {
        $desc_length = strlen( $description );
        if ( $desc_length < $config['desc_min'] ) {
            $issues[] = array(
                'type'     => 'description_too_short',
                'message'  => sprintf( 'Description is too short (%d chars). Recommended: %d-%d characters.', $desc_length, $config['desc_min'], $config['desc_max'] ),
                'severity' => 'info',
                'category' => 'content',
            );
            $summary_flags['descriptions_too_short'] = true;
            $score -= 10;
        } elseif ( $desc_length > $config['desc_max'] ) {
            $issues[] = array(
                'type'     => 'description_too_long',
                'message'  => sprintf( 'Description is too long (%d chars). May be truncated in search results. Recommended: %d-%d characters.', $desc_length, $config['desc_min'], $config['desc_max'] ),
                'severity' => 'info',
                'category' => 'content',
            );
            $summary_flags['descriptions_too_long'] = true;
            $score -= 5;
        }
    }

    // ==================== CONTENT CHECK (EMPTY TERMS) ====================
    if ( $post_count === 0 ) {
        $issues[] = array(
            'type'     => 'empty_term',
            'message'  => 'This term has no posts assigned. Empty terms may create poor user experience.',
            'severity' => 'info',
            'category' => 'content',
        );
        $recommendations[] = array(
            'action'   => 'Assign posts to this term or consider removing it if unused.',
            'priority' => 'low',
            'impact'   => 'Empty archive pages provide no value to users or search engines.',
        );
        $summary_flags['empty_terms'] = true;
        $score -= 10;
    }

    // ==================== SLUG ANALYSIS ====================
    $slug_length = strlen( $slug );

    if ( $slug_length > 50 ) {
        $issues[] = array(
            'type'     => 'slug_too_long',
            'message'  => sprintf( 'Term slug is too long (%d chars). Keep slugs concise.', $slug_length ),
            'severity' => 'info',
            'category' => 'technical',
        );
        $summary_flags['poor_slug_structure'] = true;
        $score -= 5;
    }

    // Check for stop words in slug
    $stop_words = array( 'a', 'an', 'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by' );
    $slug_parts = explode( '-', $slug );
    $stop_word_count = 0;
    foreach ( $slug_parts as $part ) {
        if ( in_array( strtolower( $part ), $stop_words, true ) ) {
            $stop_word_count++;
        }
    }

    if ( $stop_word_count > 1 ) {
        $issues[] = array(
            'type'     => 'slug_stop_words',
            'message'  => sprintf( 'Term slug contains %d stop words. Consider a cleaner slug.', $stop_word_count ),
            'severity' => 'info',
            'category' => 'technical',
        );
        $summary_flags['poor_slug_structure'] = true;
        $score -= 3;
    }

    // ==================== KEYWORD ANALYSIS ====================
    if ( ! empty( $config['focus_keyword'] ) ) {
        $keyword_lower = strtolower( $config['focus_keyword'] );
        $name_lower = strtolower( $name );
        $slug_lower = strtolower( $slug );
        $desc_lower = strtolower( $description );

        $keyword_in_name = stripos( $name_lower, $keyword_lower ) !== false;
        $keyword_in_slug = stripos( $slug_lower, str_replace( ' ', '-', $keyword_lower ) ) !== false;
        $keyword_in_desc = stripos( $desc_lower, $keyword_lower ) !== false;

        if ( ! $keyword_in_name ) {
            $issues[] = array(
                'type'     => 'keyword_not_in_name',
                'message'  => sprintf( 'Focus keyword "%s" not found in term name.', $config['focus_keyword'] ),
                'severity' => 'warning',
                'category' => 'keywords',
            );
            $score -= 15;
        }

        if ( ! $keyword_in_slug ) {
            $issues[] = array(
                'type'     => 'keyword_not_in_slug',
                'message'  => sprintf( 'Focus keyword "%s" not found in term slug.', $config['focus_keyword'] ),
                'severity' => 'info',
                'category' => 'keywords',
            );
            $score -= 8;
        }

        if ( ! empty( $description ) && ! $keyword_in_desc ) {
            $issues[] = array(
                'type'     => 'keyword_not_in_description',
                'message'  => sprintf( 'Focus keyword "%s" not found in term description.', $config['focus_keyword'] ),
                'severity' => 'info',
                'category' => 'keywords',
            );
            $score -= 5;
        }
    }

    // Ensure score doesn't go negative
    $score = max( 0, $score );

    // Sort recommendations by priority
    usort( $recommendations, function( $a, $b ) {
        $priority_order = array( 'high' => 1, 'medium' => 2, 'low' => 3 );
        $a_order = isset( $priority_order[ $a['priority'] ] ) ? $priority_order[ $a['priority'] ] : 4;
        $b_order = isset( $priority_order[ $b['priority'] ] ) ? $priority_order[ $b['priority'] ] : 4;
        return $a_order - $b_order;
    } );

    return array(
        'term_data' => array(
            'id'              => $term->term_id,
            'name'            => $name,
            'slug'            => $slug,
            'taxonomy'        => $taxonomy,
            'url'             => get_term_link( $term ),
            'post_count'      => $post_count,
            'seo_score'       => $score,
            'name_analysis'   => array(
                'length'     => $name_length,
                'is_optimal' => $name_length >= $config['title_min'] && $name_length <= $config['title_max'],
            ),
            'description_analysis' => array(
                'length'          => strlen( $description ),
                'has_description' => ! empty( $description ),
            ),
            'issues'          => $issues,
            'recommendations' => $recommendations,
        ),
        'issues'        => $issues,
        'summary_flags' => $summary_flags,
    );
}

/**
 * Check taxonomy assignment for a post
 */
function snn_check_post_taxonomy_assignment( $post_id, $post_type ) {
    $issues = array();
    $recommendations = array();

    // Get taxonomies associated with this post type
    $taxonomies = get_object_taxonomies( $post_type, 'objects' );

    foreach ( $taxonomies as $taxonomy ) {
        // Skip non-public taxonomies
        if ( ! $taxonomy->public ) {
            continue;
        }

        $terms = get_the_terms( $post_id, $taxonomy->name );

        // Check if post has any terms assigned for this taxonomy
        if ( empty( $terms ) || is_wp_error( $terms ) ) {
            // Only flag primary taxonomies (categories and tags equivalent)
            if ( $taxonomy->hierarchical || in_array( $taxonomy->name, array( 'category', 'post_tag' ), true ) ) {
                $issues[] = array(
                    'type'     => 'missing_taxonomy_' . $taxonomy->name,
                    'message'  => sprintf( 'No %s assigned to this post.', strtolower( $taxonomy->labels->singular_name ) ),
                    'severity' => 'info',
                    'category' => 'technical',
                );
                $recommendations[] = array(
                    'action'   => sprintf( 'Assign relevant %s to improve content organization and discoverability.', strtolower( $taxonomy->label ) ),
                    'priority' => 'low',
                    'impact'   => 'Proper categorization helps users and search engines understand content relationships.',
                );
            }
        }
    }

    return array(
        'issues'          => $issues,
        'recommendations' => $recommendations,
    );
}
