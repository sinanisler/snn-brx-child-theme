<?php
/**
 * MCP for snn/* abilities: which abilities are exposed, and the built-in server.
 *
 * The theme runs its own MCP server (server/): it needs no plugin, because the
 * Abilities API is part of WordPress core. This file decides which abilities
 * that server may see:
 *
 *  - meta.mcp.public comes from the per-group Read / Write toggles in
 *    mcp-settings.php (writes are off by default).
 *  - The legacy top-level readonly / destructive / idempotent flags the theme's
 *    abilities carry are copied into meta.annotations, which is where MCP
 *    clients read them (clients use them to decide when to ask for approval).
 *
 * Exposure through REST (show_in_rest) is untouched, so the theme's own chat
 * agents keep calling every ability the way they do today.
 *
 * Isolation: every name here is prefixed SNN_MCP_ or snn_mcp_, the server has
 * its own REST namespace (snn-mcp/v1), and the whole thing can be switched off
 * in SNN Settings > MCP. While it is on, the MCP Adapter's default server is
 * switched off, so the two never serve the same endpoint.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/mcp-settings.php';
require_once __DIR__ . '/mcp-audit.php';
require_once __DIR__ . '/mcp-debug.php';
require_once __DIR__ . '/server/class-mcp-protocol.php';
require_once __DIR__ . '/server/class-mcp-tools.php';
require_once __DIR__ . '/server/class-mcp-server.php';

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
 * The MCP Adapter registers its own default server (and three abilities) on
 * rest_api_init. While the built-in server is on, that default server stays off,
 * so the two never serve the same endpoint. Other servers a plugin creates with
 * the adapter are not affected.
 */
add_filter( 'mcp_adapter_create_default_server', 'snn_mcp_disable_adapter_default_server' );
function snn_mcp_disable_adapter_default_server( $create ) {
    return snn_mcp_is_enabled() ? false : $create;
}

add_action( 'rest_api_init', array( 'SNN_MCP_Server', 'register_routes' ), 20 );

/** Whether the MCP Adapter plugin is active, so the settings screen can say so. */
function snn_mcp_adapter_active() {
    return class_exists( '\WP\MCP\Core\McpAdapter' ) || defined( 'WP_MCP_DIR' ) || function_exists( 'mcp_adapter' );
}

/** URL of the built-in MCP server. */
function snn_mcp_endpoint_url() {
    return rest_url( SNN_MCP_Server::NAMESPACE_CANONICAL . SNN_MCP_Server::ROUTE_CANONICAL );
}

/** URL that connectors set up with the MCP Adapter still use. */
function snn_mcp_legacy_endpoint_url() {
    return rest_url( SNN_MCP_Server::NAMESPACE_LEGACY . SNN_MCP_Server::ROUTE_LEGACY );
}
