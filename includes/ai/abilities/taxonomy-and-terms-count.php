<?php
/**
 * Ability: Get Taxonomy and Terms Count
 *
 * Retrieves taxonomies and their term counts using WordPress Abilities API
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_abilities_api_init', 'snn_register_taxonomy_count_ability');

function snn_register_taxonomy_count_ability() {
    wp_register_ability(
        'snn/get-taxonomy-terms-count',
        [
            'label' => __('Get Taxonomy Terms Count', 'snn'),
            'description' => __('Retrieves the count of terms in a specified taxonomy.', 'snn'),
            'category' => 'content-management',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'taxonomy' => [
                        'type' => 'string',
                        'description' => __('The taxonomy to count terms from. Defaults to "category".', 'snn'),
                        'default' => 'category',
                    ],
                ],
            ],
            'output_schema' => [
                'type' => 'object',
                'properties' => [
                    'taxonomy' => [
                        'type' => 'string',
                        'description' => __('The taxonomy that was counted.', 'snn'),
                    ],
                    'count' => [
                        'type' => 'integer',
                        'description' => __('The number of terms in the taxonomy.', 'snn'),
                    ],
                ],
            ],
            'execute_callback' => 'snn_execute_get_taxonomy_terms_count',
            'permission_callback' => function() {
                return current_user_can('read');
            },
            'meta' => [
                'show_in_rest' => true,
            ],
        ]
    );
}

function snn_execute_get_taxonomy_terms_count($input) {
    $taxonomy = $input['taxonomy'] ?? 'category';

    if (!taxonomy_exists($taxonomy)) {
        return new WP_Error(
            'invalid_taxonomy',
            sprintf(__('Taxonomy "%s" does not exist.', 'snn'), $taxonomy)
        );
    }

    $terms = get_terms([
        'taxonomy' => $taxonomy,
        'hide_empty' => false,
    ]);

    if (is_wp_error($terms)) {
        return $terms;
    }

    return [
        'taxonomy' => $taxonomy,
        'count' => count($terms),
    ];
}
