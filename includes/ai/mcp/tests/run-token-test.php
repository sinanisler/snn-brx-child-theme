<?php
/**
 * Token test for the built-in MCP server (mcp-tokens.php).
 *
 * Creates two throwaway tokens (read only and read and write), sends requests
 * with each one by Bearer header and by ?key= link, and checks: a valid token
 * runs as its owner, a read-only token only sees read-only tools, a bad or
 * revoked token is 401, and the plain token is never stored. The stored tokens
 * are restored when the test ends.
 *
 *   studio wp eval-file wp-content/themes/<theme>/includes/ai/mcp/tests/run-token-test.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$GLOBALS['t_fail'] = 0;
$saved_tokens      = get_option( SNN_MCP_TOKEN_OPTION, null );

function t_ok( $cond, $msg ) {
    echo ( $cond ? '  ok   ' : '  FAIL ' ) . $msg . "\n";
    if ( ! $cond ) {
        $GLOBALS['t_fail']++;
    }
}

/** Send one JSON-RPC request with the given auth; returns the HTTP status and decoded body. */
function tk_send( $message, $headers = array(), $query = array() ) {
    wp_set_current_user( 0 );
    $request = new WP_REST_Request( 'POST', '/snn-mcp/v1/mcp' );
    foreach ( $headers as $name => $value ) {
        $request->set_header( $name, $value );
    }
    $request->set_query_params( $query );
    $request->set_body( wp_json_encode( $message ) );
    $response = rest_do_request( $request );
    return array(
        'status' => $response->get_status(),
        'body'   => json_decode( wp_json_encode( $response->get_data() ), true ),
    );
}

function tk_list( $headers = array(), $query = array() ) {
    return tk_send( array( 'jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list' ), $headers, $query );
}

function tk_bearer( $token ) {
    return array( 'Authorization' => 'Bearer ' . $token );
}

wp_set_current_user( 1 );

echo "creation\n";
$read_token  = snn_mcp_token_create( 'test read', 'read' );
$write_token = snn_mcp_token_create( 'test write', 'write' );
$read_id     = snn_mcp_token_find_id( $read_token );
$write_id    = snn_mcp_token_find_id( $write_token );
t_ok( '' !== $read_id && '' !== $write_id, 'both tokens are stored' );
t_ok( false === strpos( wp_json_encode( snn_mcp_tokens_get() ), $read_token ), 'the plain token is not stored' );
t_ok( 'read' === snn_mcp_tokens_get()[ $read_id ]['access'], 'a read token records read access' );
t_ok( 'write' === snn_mcp_tokens_get()[ $write_id ]['access'], 'a write token records write access' );

echo "authentication\n";
$r = tk_list( tk_bearer( $read_token ) );
t_ok( 200 === $r['status'], 'a valid Bearer token is accepted' );
t_ok( 'test read' === ( $GLOBALS['snn_mcp_token']['name'] ?? null ), 'the request is tagged with the token name' );
t_ok( 1 === get_current_user_id(), 'the request runs as the token owner' );

$r = tk_list( array(), array( 'key' => $read_token ) );
t_ok( 200 === $r['status'], 'a valid ?key= link is accepted' );

$r = tk_list( tk_bearer( 'snn_not_a_real_token' ) );
t_ok( 401 === $r['status'], 'an unknown token is 401' );
t_ok( ! isset( $r['body']['result'] ), 'an unknown token gets no tool result' );

echo "access levels\n";
$read_tools = tk_list( tk_bearer( $read_token ) )['body']['result']['tools'] ?? array();
$all_read   = true;
foreach ( $read_tools as $tool ) {
    if ( empty( $tool['annotations']['readOnlyHint'] ) && 'mcp-adapter-discover-abilities' !== $tool['name'] && 'mcp-adapter-get-ability-info' !== $tool['name'] && 'mcp-adapter-execute-ability' !== $tool['name'] ) {
        $all_read = false;
    }
}
t_ok( count( $read_tools ) > 0 && $all_read, 'a read-only token is offered only read-only tools' );

$write_call = tk_send(
    array( 'jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/call', 'params' => array( 'name' => 'mcp-adapter-execute-ability', 'arguments' => array( 'ability_name' => 'snn/create-post', 'input' => array( 'title' => 'x' ) ) ) ),
    tk_bearer( $read_token )
);
t_ok( true === ( $write_call['body']['result']['isError'] ?? null ), 'a read-only token cannot run a write ability' );

$write_list = tk_list( tk_bearer( $write_token ) );
t_ok( 200 === $write_list['status'] && count( $write_list['body']['result']['tools'] ?? array() ) >= count( $read_tools ), 'a read and write token sees at least the read tools' );

echo "revocation\n";
snn_mcp_token_revoke( $read_id );
$r = tk_list( tk_bearer( $read_token ) );
t_ok( 401 === $r['status'], 'a revoked token is 401 at once' );
t_ok( 200 === tk_list( tk_bearer( $write_token ) )['status'], 'other tokens keep working after a revoke' );

echo "cleanup\n";
if ( null === $saved_tokens ) {
    delete_option( SNN_MCP_TOKEN_OPTION );
} else {
    update_option( SNN_MCP_TOKEN_OPTION, $saved_tokens, false );
}
unset( $GLOBALS['snn_mcp_token'] );
wp_set_current_user( 1 );
t_ok( get_option( SNN_MCP_TOKEN_OPTION, null ) === $saved_tokens, 'stored tokens are restored to what they were' );

echo $GLOBALS['t_fail'] ? "\n{$GLOBALS['t_fail']} check(s) failed\n" : "\nall checks passed\n";
