<?php
/**
 * MCP exposure for snn/* abilities
 *
 * The theme does not ship its own MCP server. The official MCP Adapter plugin
 * (WordPress/mcp-adapter) turns registered abilities into MCP tools on
 * /wp-json/mcp/mcp-adapter-default-server, the same endpoint Bricks' own
 * bricks/* abilities use. This file only decides which snn/* abilities that
 * server may see:
 *
 *  - meta.mcp.public comes from the per-group Read / Write toggles in
 *    mcp-settings.php (writes are off by default).
 *  - The legacy top-level readonly / destructive / idempotent flags the theme's
 *    abilities carry are copied into meta.annotations, which is where core and
 *    MCP clients read them (clients use them to decide when to ask for approval).
 *
 * Exposure through REST (show_in_rest) is untouched, so the theme's own chat
 * agents keep calling every ability the way they do today.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/mcp-settings.php';
require_once __DIR__ . '/mcp-audit.php';

/**
 * Ability groups shown in the settings screen. A group lists ability names;
 * whether a given ability is a read or a write comes from its own annotations.
 */
function snn_mcp_ability_groups() {
    return array(
        'content'  => array(
            'label'     => __( 'Content (posts, pages, terms, media, comments)', 'snn' ),
            'abilities' => array(
                'snn/get-posts', 'snn/get-post-by-id', 'snn/get-post-content', 'snn/get-post-meta',
                'snn/search-content', 'snn/get-terms', 'snn/get-tags', 'snn/list-taxonomies',
                'snn/get-media', 'snn/get-comments', 'snn/create-post', 'snn/update-post',
                'snn/update-post-metadata', 'snn/create-terms', 'snn/edit-block-content',
                'snn/generate-block-pattern',
            ),
        ),
        'analysis' => array(
            'label'     => __( 'SEO and content analysis', 'snn' ),
            'abilities' => array(
                'snn/analyze-post-seo', 'snn/check-seo-quality-title-and-content',
                'snn/create-post-summaries', 'snn/suggest-content-ideas',
            ),
        ),
        'site'     => array(
            'label'     => __( 'Site information (site, theme, plugins, users)', 'snn' ),
            'abilities' => array(
                'snn/get-site-info', 'snn/active-theme-info', 'snn/installed-plugin-list-and-infos', 'snn/get-users',
            ),
        ),
        'bricks'   => array(
            'label'     => __( 'Bricks (page tree, HTML to Bricks, element edits, assets)', 'snn' ),
            // Every snn/bricks-* ability belongs here, see snn_mcp_group_for_ability().
            'abilities' => array(),
        ),
    );
}

/** Group key an ability belongs to, or '' when it is not a theme ability. */
function snn_mcp_group_for_ability( $name ) {
    if ( strpos( $name, 'snn/bricks-' ) === 0 ) {
        return 'bricks';
    }
    foreach ( snn_mcp_ability_groups() as $key => $group ) {
        if ( in_array( $name, $group['abilities'], true ) ) {
            return $key;
        }
    }
    return strpos( $name, 'snn/' ) === 0 ? 'content' : '';
}

/**
 * Whether ability args describe a read-only ability. Abilities that do not say
 * are treated as writes, so an unannotated ability never slips out as "safe".
 */
function snn_mcp_args_are_readonly( $args ) {
    $meta = isset( $args['meta'] ) && is_array( $args['meta'] ) ? $args['meta'] : array();
    if ( isset( $meta['annotations']['readonly'] ) ) {
        return (bool) $meta['annotations']['readonly'];
    }
    return ! empty( $meta['readonly'] );
}

add_filter( 'wp_register_ability_args', 'snn_mcp_filter_ability_args', 10, 2 );
function snn_mcp_filter_ability_args( $args, $name ) {
    $group = snn_mcp_group_for_ability( (string) $name );
    if ( $group === '' || ! is_array( $args ) ) {
        return $args;
    }

    $meta = isset( $args['meta'] ) && is_array( $args['meta'] ) ? $args['meta'] : array();

    // Legacy flags → core annotations (explicit annotations win).
    $annotations = isset( $meta['annotations'] ) && is_array( $meta['annotations'] ) ? $meta['annotations'] : array();
    foreach ( array( 'readonly', 'destructive', 'idempotent' ) as $flag ) {
        if ( ! array_key_exists( $flag, $annotations ) && array_key_exists( $flag, $meta ) ) {
            $annotations[ $flag ] = (bool) $meta[ $flag ];
        }
    }
    $meta['annotations'] = $annotations;

    $readonly = snn_mcp_args_are_readonly( array( 'meta' => $meta ) );
    $mcp      = isset( $meta['mcp'] ) && is_array( $meta['mcp'] ) ? $meta['mcp'] : array();

    $mcp['public'] = snn_mcp_is_enabled() && snn_mcp_group_allows( $group, $readonly ? 'read' : 'write' );
    if ( empty( $mcp['type'] ) ) {
        $mcp['type'] = 'tool';
    }
    $meta['mcp'] = $mcp;

    $args['meta'] = $meta;
    return $args;
}

/**
 * List the exposed Bricks abilities as named tools on the default server
 * (snn/bricks-html-to-bricks → tool "snn-bricks-html-to-bricks"), the same way
 * Bricks lists its high-frequency abilities. Every other enabled ability stays
 * callable through the adapter's mcp-adapter-execute-ability dispatcher.
 */
add_filter( 'mcp_adapter_default_server_config', 'snn_mcp_append_named_tools', 20 );
function snn_mcp_append_named_tools( $config ) {
    if ( ! is_array( $config ) || ! snn_mcp_is_enabled() || ! function_exists( 'wp_get_abilities' ) ) {
        return $config;
    }
    $tools = isset( $config['tools'] ) && is_array( $config['tools'] ) ? $config['tools'] : array();
    foreach ( wp_get_abilities() as $ability ) {
        $name = $ability->get_name();
        if ( strpos( $name, 'snn/bricks-' ) !== 0 ) {
            continue;
        }
        $meta = $ability->get_meta();
        if ( ! empty( $meta['mcp']['public'] ) && ! in_array( $name, $tools, true ) ) {
            $tools[] = $name;
        }
    }
    $config['tools'] = $tools;
    return $config;
}

/** Whether the MCP Adapter plugin is active, so the settings screen can say so. */
function snn_mcp_adapter_active() {
    return class_exists( '\WP\MCP\Core\McpAdapter' ) || defined( 'WP_MCP_DIR' ) || function_exists( 'mcp_adapter' );
}

/** URL of the default MCP Adapter server endpoint. */
function snn_mcp_endpoint_url() {
    return rest_url( 'mcp/mcp-adapter-default-server' );
}
