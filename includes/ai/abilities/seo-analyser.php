<?php
/**
 * Ability: SEO Analyzer
 *
 * Analyzes post content for SEO using WordPress Abilities API
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_abilities_api_init', 'snn_register_seo_analyzer_ability');

function snn_register_seo_analyzer_ability() {
    wp_register_ability(
        'snn/analyze-seo',
        [
            'label' => __('Analyze Content SEO', 'snn'),
            'description' => __('Analyzes post content for basic SEO metrics.', 'snn'),
            'category' => 'seo',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'post_id' => [
                        'type' => 'integer',
                        'description' => __('The ID of the post to analyze.', 'snn'),
                    ],
                ],
                'required' => ['post_id'],
            ],
            'output_schema' => [
                'type' => 'object',
                'properties' => [
                    'post_id' => [
                        'type' => 'integer',
                        'description' => __('The ID of the analyzed post.', 'snn'),
                    ],
                    'title_length' => [
                        'type' => 'integer',
                        'description' => __('The length of the title.', 'snn'),
                    ],
                    'content_word_count' => [
                        'type' => 'integer',
                        'description' => __('The word count of the content.', 'snn'),
                    ],
                    'has_excerpt' => [
                        'type' => 'boolean',
                        'description' => __('Whether the post has an excerpt.', 'snn'),
                    ],
                    'has_featured_image' => [
                        'type' => 'boolean',
                        'description' => __('Whether the post has a featured image.', 'snn'),
                    ],
                    'score' => [
                        'type' => 'number',
                        'description' => __('SEO score out of 100.', 'snn'),
                    ],
                    'recommendations' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'string',
                        ],
                        'description' => __('SEO improvement recommendations.', 'snn'),
                    ],
                ],
            ],
            'execute_callback' => 'snn_execute_seo_analyzer',
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            },
            'meta' => [
                'show_in_rest' => true,
            ],
        ]
    );
}

function snn_execute_seo_analyzer($input) {
    $post_id = $input['post_id'];
    $post = get_post($post_id);

    if (!$post) {
        return new WP_Error(
            'post_not_found',
            __('The specified post does not exist.', 'snn')
        );
    }

    $title = $post->post_title;
    $content = $post->post_content;
    $excerpt = $post->post_excerpt;

    $title_length = mb_strlen($title);
    $word_count = str_word_count(strip_tags($content));
    $has_excerpt = !empty($excerpt);
    $has_featured_image = has_post_thumbnail($post_id);

    // Simple scoring
    $score = 0;
    $recommendations = [];

    // Title length check (ideal: 50-60 characters)
    if ($title_length >= 50 && $title_length <= 60) {
        $score += 20;
    } else {
        $recommendations[] = __('Title should be between 50-60 characters for optimal SEO.', 'snn');
    }

    // Content length check (ideal: 300+ words)
    if ($word_count >= 300) {
        $score += 30;
    } else {
        $recommendations[] = __('Content should have at least 300 words for better SEO.', 'snn');
    }

    // Excerpt check
    if ($has_excerpt) {
        $score += 25;
    } else {
        $recommendations[] = __('Add a meta description (excerpt) for better search engine visibility.', 'snn');
    }

    // Featured image check
    if ($has_featured_image) {
        $score += 25;
    } else {
        $recommendations[] = __('Add a featured image to improve social sharing and SEO.', 'snn');
    }

    return [
        'post_id' => $post_id,
        'title_length' => $title_length,
        'content_word_count' => $word_count,
        'has_excerpt' => $has_excerpt,
        'has_featured_image' => $has_featured_image,
        'score' => $score,
        'recommendations' => $recommendations,
    ];
}
