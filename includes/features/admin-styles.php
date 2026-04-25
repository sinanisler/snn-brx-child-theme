<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * All SNN admin page slugs.
 * Used to scope the modern stylesheet and body class to SNN pages only.
 */
function snn_get_admin_page_slugs() {
    return array(
        'snn-settings',
        'snn-other-settings',
        'editor-settings',
        'snn-security',
        'snn-custom-post-types',
        'snn-custom-fields',
        'snn-taxonomies',
        'snn-seo-settings',
        'snn-login-settings',
        'snn-404-logs',
        'snn-301-redirects',
        'snn-smtp-settings',
        'snn-mail-logs',
        'snn-mail-customizer',
        'snn-role-management',
        'snn-cookie-settings',
        'snn-accessibility-settings',
        'snn-ai-settings',
        'snn-ai-agent-settings',
        'snn-interactions',
        'snn-search-logs',
        'snn-media-settings',
        'snn-activity-log',
        'snn-custom-codes-snippets',
        'snn-block-editor-settings',
        'snn-image-optimization',
    );
}

/**
 * Return true when the current admin request is for an SNN page.
 *
 * @return bool
 */
function snn_is_snn_admin_page() {
    $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
    return in_array( $page, snn_get_admin_page_slugs(), true );
}

/**
 * Enqueue the shared modern admin stylesheet on all SNN pages.
 */
function snn_enqueue_admin_styles() {
    if ( snn_is_snn_admin_page() ) {
        wp_enqueue_style(
            'snn-admin-styles',
            SNN_URL_ASSETS . 'css/snn-admin.css',
            array(),
            filemtime( SNN_PATH_ASSETS . 'css/snn-admin.css' )
        );
    }
}
add_action( 'admin_enqueue_scripts', 'snn_enqueue_admin_styles' );

/**
 * Add 'snn-admin-page' class to the admin <body> on all SNN pages.
 * This scopes every CSS rule in snn-admin.css to SNN pages only.
 *
 * @param string $classes Space-separated list of body classes.
 * @return string
 */
function snn_admin_body_class( $classes ) {
    if ( snn_is_snn_admin_page() ) {
        $classes .= ' snn-admin-page';
    }

    return $classes;
}
add_filter( 'admin_body_class', 'snn_admin_body_class' );
