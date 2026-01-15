<?php
/**
 * Ability Categories Registration
 *
 * Centralized registration of all ability categories for the WordPress Abilities API.
 * This file should be loaded before individual ability files to ensure categories exist.
 */

add_action( 'wp_abilities_api_categories_init', 'snn_register_all_ability_categories' );

/**
 * Register all ability categories in one place.
 */
function snn_register_all_ability_categories() {

    // Content Management - for managing posts, pages, and content
    if ( ! wp_has_ability_category( 'content' ) ) {
        wp_register_ability_category(
            'content',
            array(
                'label'       => __( 'Content Management', 'snn' ),
                'description' => __( 'Abilities for managing posts, pages, and content.', 'snn' ),
            )
        );
    }

    // Content Analysis - for analyzing and suggesting content improvements
    if ( ! wp_has_ability_category( 'content-analysis' ) ) {
        wp_register_ability_category(
            'content-analysis',
            array(
                'label'       => __( 'Content Analysis', 'snn' ),
                'description' => __( 'Abilities for analyzing and suggesting content improvements.', 'snn' ),
            )
        );
    }

    // SEO Analysis - for analyzing and improving SEO quality
    if ( ! wp_has_ability_category( 'seo-analysis' ) ) {
        wp_register_ability_category(
            'seo-analysis',
            array(
                'label'       => __( 'SEO Analysis', 'snn' ),
                'description' => __( 'Abilities for analyzing and improving SEO quality.', 'snn' ),
            )
        );
    }

    // Taxonomy Management - for managing categories, tags, and taxonomies
    if ( ! wp_has_ability_category( 'taxonomy' ) ) {
        wp_register_ability_category(
            'taxonomy',
            array(
                'label'       => __( 'Taxonomy Management', 'snn' ),
                'description' => __( 'Abilities for managing categories, tags, and taxonomies.', 'snn' ),
            )
        );
    }

    // Comments Management - for managing comments
    if ( ! wp_has_ability_category( 'comments' ) ) {
        wp_register_ability_category(
            'comments',
            array(
                'label'       => __( 'Comments Management', 'snn' ),
                'description' => __( 'Abilities for managing comments.', 'snn' ),
            )
        );
    }

    // Media Management - for managing media and attachments
    if ( ! wp_has_ability_category( 'media' ) ) {
        wp_register_ability_category(
            'media',
            array(
                'label'       => __( 'Media Management', 'snn' ),
                'description' => __( 'Abilities for managing media and attachments.', 'snn' ),
            )
        );
    }

    // User Management - for managing users and user data
    if ( ! wp_has_ability_category( 'users' ) ) {
        wp_register_ability_category(
            'users',
            array(
                'label'       => __( 'User Management', 'snn' ),
                'description' => __( 'Abilities for managing users and user data.', 'snn' ),
            )
        );
    }

    // System Information - for retrieving system and site information
    if ( ! wp_has_ability_category( 'system' ) ) {
        wp_register_ability_category(
            'system',
            array(
                'label'       => __( 'System Information', 'snn' ),
                'description' => __( 'Abilities for retrieving system and site information.', 'snn' ),
            )
        );
    }

    // System Info (legacy) - for retrieving WordPress system information
    if ( ! wp_has_ability_category( 'system-info' ) ) {
        wp_register_ability_category(
            'system-info',
            array(
                'label'       => __( 'System Information', 'snn' ),
                'description' => __( 'Abilities for retrieving WordPress system information.', 'snn' ),
            )
        );
    }
}
