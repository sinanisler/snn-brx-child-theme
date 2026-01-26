<?php
/**
 * Analyze Post SEO Ability
 * Registers the snn/analyze-post-seo ability for the WordPress Abilities API
 */

// Register ability
add_action( 'wp_abilities_api_init', 'snn_register_analyze_post_seo_ability' );
function snn_register_analyze_post_seo_ability() {
    wp_register_ability(
        'snn/analyze-post-seo',
        array(
            'label'       => __( 'Comprehensive Analyze Post SEO', 'snn' ),
            'description' => __( 'Performs comprehensive SEO analysis on a WordPress post or page. Checks title length, meta description, keyword usage, content length, heading structure, image alt text, internal/external links, readability score, and provides actionable recommendations. Use this to optimize content for search engines and improve rankings.', 'snn' ),
            'category'    => 'seo',
            'input_schema' => array(
                'type'       => 'object',
                'required'   => array( 'post_id' ),
                'properties' => array(
                    'post_id' => array(
                        'type'        => 'integer',
                        'description' => 'Post ID to analyze.',
                    ),
                    'focus_keyword' => array(
                        'type'        => 'string',
                        'description' => 'Focus keyword for SEO analysis (optional).',
                    ),
                ),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'post_id'  => array(
                        'type'        => 'integer',
                        'description' => 'Analyzed post ID',
                    ),
                    'seo_score' => array(
                        'type'        => 'integer',
                        'description' => 'Overall SEO score (0-100)',
                    ),
                    'analysis' => array(
                        'type'        => 'object',
                        'description' => 'Detailed SEO analysis',
                    ),
                    'recommendations' => array(
                        'type'        => 'array',
                        'description' => 'List of SEO improvement recommendations',
                    ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $post_id = absint( $input['post_id'] );
                $focus_keyword = isset( $input['focus_keyword'] ) ? sanitize_text_field( $input['focus_keyword'] ) : '';

                // Get post
                $post = get_post( $post_id );
                if ( ! $post ) {
                    return new WP_Error( 'invalid_post', __( 'Post not found.', 'snn' ) );
                }

                // Check permissions
                if ( ! current_user_can( 'read_post', $post_id ) ) {
                    return new WP_Error( 'permission_denied', __( 'You do not have permission to read this post.', 'snn' ) );
                }

                $analysis = array();
                $recommendations = array();
                $score = 100;

                // Title analysis
                $title_length = strlen( $post->post_title );
                $analysis['title'] = array(
                    'text'   => $post->post_title,
                    'length' => $title_length,
                    'status' => $title_length >= 30 && $title_length <= 60 ? 'good' : 'warning',
                );
                if ( $title_length < 30 ) {
                    $recommendations[] = 'Title is too short (< 30 characters). Consider expanding it for better SEO.';
                    $score -= 10;
                } elseif ( $title_length > 60 ) {
                    $recommendations[] = 'Title is too long (> 60 characters). It may be truncated in search results.';
                    $score -= 5;
                }

                // Content analysis
                $content = wp_strip_all_tags( $post->post_content );
                $word_count = str_word_count( $content );
                $analysis['content'] = array(
                    'word_count'  => $word_count,
                    'char_count'  => strlen( $content ),
                    'status'      => $word_count >= 300 ? 'good' : 'warning',
                );
                if ( $word_count < 300 ) {
                    $recommendations[] = sprintf( 'Content is too short (%d words). Aim for at least 300 words for better SEO.', $word_count );
                    $score -= 15;
                }

                // Excerpt / Meta Description
                $excerpt_length = strlen( $post->post_excerpt );
                $analysis['excerpt'] = array(
                    'text'   => $post->post_excerpt,
                    'length' => $excerpt_length,
                    'status' => $excerpt_length >= 120 && $excerpt_length <= 160 ? 'good' : ( $excerpt_length > 0 ? 'warning' : 'critical' ),
                );
                if ( empty( $post->post_excerpt ) ) {
                    $recommendations[] = 'No excerpt/meta description set. Add one for better search result snippets.';
                    $score -= 15;
                } elseif ( $excerpt_length < 120 || $excerpt_length > 160 ) {
                    $recommendations[] = 'Excerpt length should be between 120-160 characters for optimal display in search results.';
                    $score -= 5;
                }

                // Heading structure
                $heading_matches = array();
                preg_match_all( '/<h([1-6])[^>]*>(.*?)<\/h[1-6]>/i', $post->post_content, $heading_matches );
                $headings = array();
                if ( ! empty( $heading_matches[1] ) ) {
                    foreach ( $heading_matches[1] as $index => $level ) {
                        $headings[] = array(
                            'level' => 'H' . $level,
                            'text'  => wp_strip_all_tags( $heading_matches[2][ $index ] ),
                        );
                    }
                }
                $analysis['headings'] = array(
                    'count'  => count( $headings ),
                    'list'   => $headings,
                    'status' => count( $headings ) > 0 ? 'good' : 'warning',
                );
                if ( count( $headings ) === 0 ) {
                    $recommendations[] = 'No headings found. Add H2/H3 headings to structure your content.';
                    $score -= 10;
                }

                // Image analysis
                $image_matches = array();
                preg_match_all( '/<img[^>]+>/i', $post->post_content, $image_matches );
                $images = array();
                $images_without_alt = 0;
                if ( ! empty( $image_matches[0] ) ) {
                    foreach ( $image_matches[0] as $img_tag ) {
                        preg_match( '/alt=(["\'])(.*?)\1/i', $img_tag, $alt_match );
                        $alt_text = isset( $alt_match[2] ) ? $alt_match[2] : '';
                        if ( empty( $alt_text ) ) {
                            $images_without_alt++;
                        }
                        $images[] = array(
                            'has_alt' => ! empty( $alt_text ),
                            'alt_text' => $alt_text,
                        );
                    }
                }
                $analysis['images'] = array(
                    'total'           => count( $images ),
                    'without_alt'     => $images_without_alt,
                    'status'          => $images_without_alt === 0 ? 'good' : 'warning',
                );
                if ( $images_without_alt > 0 ) {
                    $recommendations[] = sprintf( '%d image(s) missing alt text. Add descriptive alt text for better accessibility and SEO.', $images_without_alt );
                    $score -= 5;
                }

                // Link analysis
                $link_matches = array();
                preg_match_all( '/<a[^>]+href=(["\'])(.*?)\1[^>]*>/i', $post->post_content, $link_matches );
                $internal_links = 0;
                $external_links = 0;
                $site_url = get_site_url();
                if ( ! empty( $link_matches[2] ) ) {
                    foreach ( $link_matches[2] as $url ) {
                        if ( strpos( $url, $site_url ) !== false || strpos( $url, '/' ) === 0 ) {
                            $internal_links++;
                        } else {
                            $external_links++;
                        }
                    }
                }
                $analysis['links'] = array(
                    'total'    => count( $link_matches[2] ),
                    'internal' => $internal_links,
                    'external' => $external_links,
                    'status'   => $internal_links > 0 ? 'good' : 'warning',
                );
                if ( $internal_links === 0 ) {
                    $recommendations[] = 'No internal links found. Add links to related content on your site.';
                    $score -= 10;
                }

                // Focus keyword analysis (if provided)
                if ( ! empty( $focus_keyword ) ) {
                    $keyword_lower = strtolower( $focus_keyword );
                    $title_lower = strtolower( $post->post_title );
                    $content_lower = strtolower( $content );

                    $keyword_in_title = strpos( $title_lower, $keyword_lower ) !== false;
                    $keyword_in_content = substr_count( $content_lower, $keyword_lower );

                    $analysis['focus_keyword'] = array(
                        'keyword'        => $focus_keyword,
                        'in_title'       => $keyword_in_title,
                        'in_content'     => $keyword_in_content,
                        'density'        => $word_count > 0 ? round( ( $keyword_in_content / $word_count ) * 100, 2 ) : 0,
                        'status'         => ( $keyword_in_title && $keyword_in_content > 0 ) ? 'good' : 'critical',
                    );

                    if ( ! $keyword_in_title ) {
                        $recommendations[] = sprintf( 'Focus keyword "%s" not found in title. Include it for better SEO.', $focus_keyword );
                        $score -= 15;
                    }
                    if ( $keyword_in_content === 0 ) {
                        $recommendations[] = sprintf( 'Focus keyword "%s" not found in content. Use it naturally throughout the article.', $focus_keyword );
                        $score -= 15;
                    } elseif ( $keyword_in_content < 3 ) {
                        $recommendations[] = sprintf( 'Focus keyword "%s" appears only %d time(s). Use it more frequently (but naturally).', $focus_keyword, $keyword_in_content );
                        $score -= 10;
                    }
                }

                // Readability estimate (Flesch Reading Ease approximation)
                $sentences = preg_split( '/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY );
                $sentence_count = count( $sentences );
                $syllables = strlen( preg_replace( '/[^aeiouAEIOU]/', '', $content ) ); // Rough estimate

                if ( $word_count > 0 && $sentence_count > 0 ) {
                    $avg_words_per_sentence = $word_count / $sentence_count;
                    $analysis['readability'] = array(
                        'avg_words_per_sentence' => round( $avg_words_per_sentence, 1 ),
                        'sentence_count'         => $sentence_count,
                        'status'                 => $avg_words_per_sentence <= 20 ? 'good' : 'warning',
                    );

                    if ( $avg_words_per_sentence > 20 ) {
                        $recommendations[] = sprintf( 'Average sentence length is %.1f words. Consider shorter sentences for better readability.', $avg_words_per_sentence );
                        $score -= 5;
                    }
                }

                // Ensure score is within bounds
                $score = max( 0, min( 100, $score ) );

                return array(
                    'post_id'         => $post_id,
                    'seo_score'       => $score,
                    'analysis'        => $analysis,
                    'recommendations' => $recommendations,
                    'summary'         => sprintf(
                        'SEO Score: %d/100. %d recommendations for improvement.',
                        $score,
                        count( $recommendations )
                    ),
                );
            },
            'permission_callback' => function() {
                return current_user_can( 'read' );
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
