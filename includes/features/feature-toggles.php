<?php
/**
 * SNN Feature Toggles
 *
 * Central registry of the major SNN features (the ones listed as buttons on the
 * SNN Settings dashboard). Every feature is enabled by default; disabling one
 * from the "Customizations" section on admin.php?page=snn-settings simply makes
 * snn_feature_enabled() return false so the matching require_once lines in
 * functions.php are skipped.
 *
 * Storage note: we store the DISABLED slugs (option snn_disabled_features) so
 * that any newly added feature is enabled out of the box without a migration.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Slugs that can never be switched off.
 *
 * @return array
 */
function snn_get_always_on_features() {
    return array( 'snn-settings' );
}

/**
 * The full feature registry, with labels and icons for the admin UI.
 *
 * Only call this from the admin screens: the labels are translated, and
 * snn_feature_enabled() must stay usable from functions.php long before the
 * text domain is loaded.
 *
 * @return array
 */
function snn_get_features() {
    static $features = null;

    if ( null !== $features ) {
        return $features;
    }

    $dynamic_title = get_option( 'snn_menu_title', __( 'SNN Settings', 'snn' ) );

    $features = array(
        'snn-settings' => array(
            'label'    => $dynamic_title . ' ' . __( 'Settings', 'snn' ),
            'dashicon' => 'dashicons-admin-home',
            'always'   => true,
        ),
        'snn-other-settings' => array(
            'label'    => __( 'Dashboard Settings', 'snn' ),
            'dashicon' => 'dashicons-dashboard',
        ),
        'editor-settings' => array(
            'label'    => __( 'Editor Settings', 'snn' ),
            'dashicon' => 'dashicons-edit',
        ),
        'snn-security' => array(
            'label'    => __( 'Security Settings', 'snn' ),
            'dashicon' => 'dashicons-shield',
        ),
        'snn-custom-post-types' => array(
            'label'    => __( 'Post Types', 'snn' ),
            'dashicon' => 'dashicons-admin-post',
        ),
        'snn-custom-fields' => array(
            'label'    => __( 'Custom Fields', 'snn' ),
            'dashicon' => 'dashicons-admin-page',
        ),
        'snn-taxonomies' => array(
            'label'    => __( 'Taxonomies', 'snn' ),
            'dashicon' => 'dashicons-category',
        ),
        'snn-seo-settings' => array(
            'label'    => __( 'SEO Settings', 'snn' ),
            'dashicon' => 'dashicons-editor-textcolor',
        ),
        'snn-login-settings' => array(
            'label'    => __( 'Login Settings', 'snn' ),
            'dashicon' => 'dashicons-admin-users',
        ),
        'snn-404-logs' => array(
            'label'    => __( '404 Logs', 'snn' ),
            'dashicon' => 'dashicons-warning',
        ),
        'snn-301-redirects' => array(
            'label'    => __( '301 Redirects', 'snn' ),
            'dashicon' => 'dashicons-share',
        ),
        'snn-smtp-settings' => array(
            'label'    => __( 'Mail SMTP Settings', 'snn' ),
            'dashicon' => 'dashicons-email',
        ),
        'snn-mail-logs' => array(
            'label'    => __( 'Mail Logs', 'snn' ),
            'dashicon' => 'dashicons-email-alt',
        ),
        'snn-role-management' => array(
            'label'    => __( 'Role Manager', 'snn' ),
            'dashicon' => 'dashicons-admin-users',
        ),
        'snn-cookie-settings' => array(
            'label'    => __( 'Cookie Settings', 'snn' ),
            'dashicon' => 'dashicons-admin-site',
        ),
        'snn-accessibility-settings' => array(
            'label'    => __( 'Accessibility Settings', 'snn' ),
            'dashicon' => 'dashicons-universal-access',
        ),
        'snn-ai-settings' => array(
            'label'    => __( 'AI Settings', 'snn' ),
            'dashicon' => 'dashicons-nametag',
        ),
        'snn-ai-agent-settings' => array(
            'label'    => __( 'AI Agent and Chat', 'snn' ),
            'dashicon' => 'dashicons-nametag',
        ),
        'snn-interactions' => array(
            'label'    => __( 'Interactions', 'snn' ),
            'dashicon' => 'dashicons-table-col-after',
        ),
        'snn-search-logs' => array(
            'label'    => __( 'Search Logs', 'snn' ),
            'dashicon' => 'dashicons-search',
        ),
        'snn-media-settings' => array(
            'label'    => __( 'Media Settings', 'snn' ),
            'dashicon' => 'dashicons-format-image',
        ),
        'snn-activity-log' => array(
            'label'    => __( 'Activity Logs', 'snn' ),
            'dashicon' => 'dashicons-text',
        ),
        'snn-custom-codes-snippets' => array(
            'label'    => __( 'Code Snippets', 'snn' ),
            'dashicon' => 'dashicons-editor-code',
        ),
        'snn-block-editor-settings' => array(
            'label'    => __( 'Block Editor Settings', 'snn' ),
            'dashicon' => 'dashicons-admin-customizer',
        ),
    );

    return $features;
}

/**
 * Slugs that are currently switched off.
 *
 * @return array
 */
function snn_get_disabled_features() {
    static $disabled = null;

    if ( null !== $disabled ) {
        return $disabled;
    }

    $stored = get_option( 'snn_disabled_features', array() );

    if ( ! is_array( $stored ) ) {
        $stored = array();
    }

    $disabled = array_values( array_filter( array_map( 'strval', $stored ) ) );

    return $disabled;
}

/**
 * Is a feature active? Unknown slugs and "always" features are always active.
 *
 * @param string $slug Feature slug, e.g. 'snn-security'.
 * @return bool
 */
function snn_feature_enabled( $slug ) {
    if ( in_array( $slug, snn_get_always_on_features(), true ) ) {
        return true;
    }

    return ! in_array( $slug, snn_get_disabled_features(), true );
}

/**
 * Keep only the disabled slugs we actually know about, and never let the main
 * settings page disable itself.
 *
 * @param mixed $value Raw posted value.
 * @return array
 */
function snn_sanitize_disabled_features( $value ) {
    if ( ! is_array( $value ) ) {
        return array();
    }

    $features = snn_get_features();
    $clean    = array();

    foreach ( $value as $slug ) {
        $slug = sanitize_key( $slug );

        if ( '' === $slug || ! isset( $features[ $slug ] ) ) {
            continue;
        }

        if ( in_array( $slug, snn_get_always_on_features(), true ) ) {
            continue;
        }

        $clean[ $slug ] = $slug;
    }

    return array_values( $clean );
}
