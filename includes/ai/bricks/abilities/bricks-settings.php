<?php
/**
 * snn/bricks-settings-get and snn/bricks-settings-set: read and change Bricks settings that
 * Bricks' own abilities do not expose on this site.
 *
 * Global settings go through Bricks' settings ability (same registry, same validation, API
 * keys excluded). The SVG upload permission is Bricks' own per-user capability
 * (bricks_upload_svg), set the way Bricks' profile screen sets it. Admins only.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_abilities_api_init', 'snn_register_bricks_settings_abilities' );
function snn_register_bricks_settings_abilities() {
    wp_register_ability( 'snn/bricks-settings-get', array(
        'label'       => __( 'Bricks: Get settings', 'snn' ),
        'description' => __( 'Read Bricks global settings and the SVG upload permission of a user. Pass keys to read specific settings (omit for all readable ones). svgUpload reports whether that user may upload SVG. Requires manage_options.', 'snn' ),
        'category'    => 'snn-bricks',
        'input_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'keys'    => array(
                    'type'        => 'array',
                    'items'       => array( 'type' => 'string' ),
                    'description' => 'Bricks setting keys, e.g. ["postTypes"]. Omit for every readable setting.',
                ),
                'user_id' => array(
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'description' => 'User whose SVG upload permission to read. Defaults to the user making the call.',
                ),
            ),
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'settings'  => array( 'type' => 'object' ),
                'missing'   => array( 'type' => 'array' ),
                'svgUpload' => array( 'type' => 'object' ),
            ),
        ),
        'execute_callback'    => 'snn_bricks_settings_get',
        'permission_callback' => 'snn_bricks_settings_permission',
        'meta'                => array(
            'show_in_rest' => true,
            'annotations'  => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
        ),
    ) );

    wp_register_ability( 'snn/bricks-settings-set', array(
        'label'       => __( 'Bricks: Change settings', 'snn' ),
        'description' => __( 'Change Bricks global settings (settings: key → value, validated by Bricks) and/or a user\'s SVG upload permission (svg_upload: { enabled, user_id }). Returns what changed. Requires manage_options.', 'snn' ),
        'category'    => 'snn-bricks',
        'input_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'settings'   => array(
                    'type'        => 'object',
                    'description' => 'Bricks global settings to change, e.g. {"postTypes": ["page"]}. Keys and values are validated by Bricks.',
                ),
                'svg_upload' => array(
                    'type'        => 'object',
                    'description' => 'SVG upload permission for one user. enabled: true or false. user_id: defaults to the user making the call.',
                    'properties'  => array(
                        'enabled' => array( 'type' => 'boolean' ),
                        'user_id' => array( 'type' => 'integer', 'minimum' => 1 ),
                    ),
                    'required'    => array( 'enabled' ),
                ),
            ),
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'settings'  => array( 'type' => 'object' ),
                'svgUpload' => array( 'type' => 'object' ),
            ),
        ),
        'execute_callback'    => 'snn_bricks_settings_set',
        'permission_callback' => 'snn_bricks_settings_permission',
        'meta'                => array(
            'show_in_rest' => true,
            'annotations'  => array( 'readonly' => false, 'destructive' => true, 'idempotent' => true ),
        ),
    ) );
}

/** Both abilities: an admin, with Bricks active. */
function snn_bricks_settings_permission() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return new WP_Error( 'snn_forbidden', 'Bricks settings need manage_options.' );
    }
    if ( ! class_exists( '\Bricks\Abilities\Settings' ) || ! class_exists( '\Bricks\Capabilities' ) ) {
        return new WP_Error( 'snn_bricks_missing', 'Bricks is not active.' );
    }
    return true;
}

/** The user a call is about: the given id, or the caller. Null when the id does not exist. */
function snn_bricks_settings_user( $user_id ) {
    $user_id = (int) $user_id;
    $user    = $user_id ? get_user_by( 'id', $user_id ) : wp_get_current_user();
    return $user && $user->exists() ? $user : null;
}

/** Bricks' SVG upload permission for one user, as the upload check sees it. */
function snn_bricks_svg_status( $user ) {
    return array(
        'userId'    => (int) $user->ID,
        'login'     => $user->user_login,
        'enabled'   => $user->has_cap( \Bricks\Capabilities::UPLOAD_SVG ),
        'canUpload' => $user->has_cap( 'upload_files' ),
    );
}

function snn_bricks_settings_get( $input ) {
    $input = (array) $input;
    $user  = snn_bricks_settings_user( $input['user_id'] ?? 0 );
    if ( ! $user ) {
        return new WP_Error( 'snn_user_not_found', 'No user with that id.' );
    }

    $read = \Bricks\Abilities\Settings::get_global_settings( array(
        'keys' => isset( $input['keys'] ) && is_array( $input['keys'] ) ? $input['keys'] : array(),
    ) );

    return array(
        'settings'  => $read['settings'],
        'missing'   => $read['missing'],
        'svgUpload' => snn_bricks_svg_status( $user ),
    );
}

function snn_bricks_settings_set( $input ) {
    $input  = (array) $input;
    $result = array();

    if ( ! empty( $input['settings'] ) && is_array( $input['settings'] ) ) {
        $written = \Bricks\Abilities\Settings::set_global_settings( array( 'settings' => $input['settings'] ) );
        if ( is_wp_error( $written ) ) {
            return $written;
        }
        $result['settings'] = $written;
    }

    if ( isset( $input['svg_upload'] ) && is_array( $input['svg_upload'] ) ) {
        $user = snn_bricks_settings_user( $input['svg_upload']['user_id'] ?? 0 );
        if ( ! $user ) {
            return new WP_Error( 'snn_user_not_found', 'No user with that id.' );
        }
        if ( ! empty( $input['svg_upload']['enabled'] ) ) {
            $user->add_cap( \Bricks\Capabilities::UPLOAD_SVG );
        } else {
            $user->remove_cap( \Bricks\Capabilities::UPLOAD_SVG );
        }
        $result['svgUpload'] = snn_bricks_svg_status( $user );
    }

    if ( ! $result ) {
        return new WP_Error( 'snn_nothing_to_change', 'Pass settings and/or svg_upload.' );
    }
    return $result;
}
