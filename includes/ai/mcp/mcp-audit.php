<?php
/**
 * Audit trail for abilities executed through MCP.
 *
 * Abilities run the same way for the theme's chat agents (REST) and for MCP
 * clients, so the source is told apart by the request route: the MCP Adapter
 * serves its servers under /wp-json/mcp/. Each MCP execution writes one
 * Activity Log entry with the user, ability, post ID and outcome.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** True while handling a request to an MCP Adapter server route. */
function snn_mcp_is_mcp_request() {
    if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
        return false;
    }
    $route = '';
    if ( isset( $GLOBALS['wp'] ) && isset( $GLOBALS['wp']->query_vars['rest_route'] ) ) {
        $route = (string) $GLOBALS['wp']->query_vars['rest_route'];
    }
    if ( $route === '' && isset( $_SERVER['REQUEST_URI'] ) ) {
        $route = (string) wp_unslash( $_SERVER['REQUEST_URI'] );
    }
    return (bool) preg_match( '#(^|/)(wp-json/)?mcp/#', $route );
}

add_filter( 'wp_ability_execute_result', 'snn_mcp_audit_execution', 10, 4 );
function snn_mcp_audit_execution( $result, $name, $input, $ability ) {
    if ( strpos( (string) $name, 'mcp-adapter/' ) === 0 || ! snn_mcp_is_mcp_request() ) {
        return $result;
    }
    if ( ! function_exists( 'snn_log_user_activity' ) ) {
        return $result;
    }

    $post_id = 0;
    if ( is_array( $input ) ) {
        foreach ( array( 'post_id', 'postId', 'id' ) as $key ) {
            if ( isset( $input[ $key ] ) && is_numeric( $input[ $key ] ) ) {
                $post_id = (int) $input[ $key ];
                break;
            }
        }
    }

    $outcome = is_wp_error( $result ) ? 'error: ' . $result->get_error_code() : 'ok';
    snn_log_user_activity(
        sprintf( 'MCP ability %s (%s)', $name, $outcome ),
        'mcp',
        $post_id,
        'mcp_ability'
    );

    return $result;
}
