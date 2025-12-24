<?php
/**
 * AI Ability Categories
 *
 * Registers ability categories for WordPress Abilities API
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_abilities_api_categories_init', 'snn_register_ability_categories');

function snn_register_ability_categories() {
    // Content Management Category
    wp_register_ability_category(
        'content-management',
        [
            'label' => __('Content Management', 'snn'),
            'description' => __('Abilities for managing and organizing content.', 'snn'),
        ]
    );

    // User Management Category
    wp_register_ability_category(
        'user-management',
        [
            'label' => __('User Management', 'snn'),
            'description' => __('Abilities for managing users.', 'snn'),
        ]
    );

    // SEO Category
    wp_register_ability_category(
        'seo',
        [
            'label' => __('SEO', 'snn'),
            'description' => __('Abilities for SEO analysis and optimization.', 'snn'),
        ]
    );
}
