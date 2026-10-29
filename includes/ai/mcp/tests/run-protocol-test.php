<?php
/**
 * Protocol test for the built-in MCP server (includes/ai/mcp/server/).
 *
 * Sends JSON-RPC requests to the endpoint in-process as admin user 1 and checks
 * what MCP clients rely on: version negotiation, notifications, the tool list,
 * tool calls and their errors, HTTP status codes, Origin and version checks, and
 * the legacy URL. Read-only: nothing is created or changed. When the MCP Adapter
 * is loaded, the named tool definitions are compared with the adapter's own.
 *
 *   studio wp eval-file wp-content/themes/<theme>/includes/ai/mcp/tests/run-protocol-test.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

wp_set_current_user( 1 );
$GLOBALS['t_fail'] = 0;

function t_ok( $cond, $msg ) {
    echo ( $cond ? '  ok   ' : '  FAIL ' ) . $msg . "\n";
    if ( ! $cond ) {
        $GLOBALS['t_fail']++;
    }
}

/** Send one request; returns the HTTP status and the decoded JSON body. */
function t_send( $body, $headers = array(), $route = '/snn-mcp/v1/mcp', $method = 'POST' ) {
    $request = new WP_REST_Request( $method, $route );
    foreach ( $headers as $name => $value ) {
        $request->set_header( $name, $value );
    }
    if ( null !== $body ) {
        $request->set_body( is_string( $body ) ? $body : wp_json_encode( $body ) );
    }
    $response = rest_do_request( $request );
    return array(
        'status' => $response->get_status(),
        'body'   => json_decode( wp_json_encode( $response->get_data() ), true ),
    );
}

/** Deep ksort so two definitions compare regardless of key order. */
function t_normalize( $value ) {
    if ( is_object( $value ) ) {
        $value = get_object_vars( $value );
    }
    if ( is_array( $value ) ) {
        foreach ( $value as $key => $item ) {
            $value[ $key ] = t_normalize( $item );
        }
        if ( array_keys( $value ) !== range( 0, count( $value ) - 1 ) ) {
            ksort( $value );
        }
    }
    return $value;
}

function t_call( $name, $arguments ) {
    return t_send( array( 'jsonrpc' => '2.0', 'id' => 100, 'method' => 'tools/call', 'params' => array( 'name' => $name, 'arguments' => $arguments ) ) );
}

echo "initialize\n";
$r = t_send( array( 'jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => array( 'protocolVersion' => '2025-06-18' ) ) );
t_ok( 200 === $r['status'], 'initialize answers 200' );
t_ok( '2025-06-18' === ( $r['body']['result']['protocolVersion'] ?? null ), 'a supported version is echoed' );
t_ok( isset( $r['body']['result']['capabilities']['tools'] ), 'tools capability is declared' );
t_ok( 'snn-mcp' === ( $r['body']['result']['serverInfo']['name'] ?? null ), 'serverInfo name is snn-mcp' );

$r = t_send( array( 'jsonrpc' => '2.0', 'id' => 2, 'method' => 'initialize', 'params' => array( 'protocolVersion' => '2099-01-01' ) ) );
t_ok( '2025-11-25' === ( $r['body']['result']['protocolVersion'] ?? null ), 'an unknown version is answered with the latest one' );

echo "notifications and ping\n";
$r = t_send( array( 'jsonrpc' => '2.0', 'method' => 'notifications/initialized' ) );
t_ok( 202 === $r['status'] && null === $r['body'], 'a notification gets 202 and no body' );

$r = t_send( array( 'jsonrpc' => '2.0', 'id' => 3, 'method' => 'ping' ) );
t_ok( 200 === $r['status'] && array() === ( $r['body']['result'] ?? null ), 'ping returns an empty result' );

echo "tools/list\n";
$r     = t_send( array( 'jsonrpc' => '2.0', 'id' => 4, 'method' => 'tools/list' ) );
$tools = $r['body']['result']['tools'] ?? array();
$names = array_column( $tools, 'name' );
t_ok( in_array( 'mcp-adapter-discover-abilities', $names, true ), 'discover meta tool is listed' );
t_ok( in_array( 'mcp-adapter-get-ability-info', $names, true ), 'get-ability-info meta tool is listed' );
t_ok( in_array( 'mcp-adapter-execute-ability', $names, true ), 'execute-ability meta tool is listed' );
t_ok( in_array( 'snn-bricks-start-here', $names, true ), 'named Bricks tool snn-bricks-start-here is listed' );
if ( wp_has_ability( 'bricks/get-design-context' ) ) {
    t_ok( in_array( 'bricks-get-design-context', $names, true ), 'a named tool added by Bricks through mcp_adapter_default_server_config is listed' );
} else {
    echo "  skipped: Bricks abilities are not registered\n";
}
// Same mechanism Bricks uses, with an ability that is always present.
$add_named = static function ( $config ) {
    $config['tools'][] = 'snn/get-site-info';
    return $config;
};
add_filter( 'mcp_adapter_default_server_config', $add_named );
$extra_names = array_column( t_send( array( 'jsonrpc' => '2.0', 'id' => 4, 'method' => 'tools/list' ) )['body']['result']['tools'] ?? array(), 'name' );
remove_filter( 'mcp_adapter_default_server_config', $add_named );
t_ok( in_array( 'snn-get-site-info', $extra_names, true ), 'an ability added through mcp_adapter_default_server_config becomes a named tool' );
t_ok( ! in_array( 'snn-get-site-info', array_column( $tools, 'name' ), true ), 'and is not listed once the filter is removed' );
$all_have_schema = true;
$all_have_hints  = true;
foreach ( $tools as $tool ) {
    $all_have_schema = $all_have_schema && 'object' === ( $tool['inputSchema']['type'] ?? null );
    $all_have_hints  = $all_have_hints && isset( $tool['annotations']['readOnlyHint'] );
}
t_ok( $all_have_schema, 'every tool has an object input schema' );
t_ok( $all_have_hints, 'every tool carries readOnlyHint' );

echo "tools/call\n";
$r = t_call( 'mcp-adapter-execute-ability', array( 'ability_name' => 'snn/get-site-info', 'parameters' => array() ) );
t_ok( false === ( $r['body']['result']['isError'] ?? true ), 'execute-ability runs a public read ability' );
t_ok( true === ( $r['body']['result']['structuredContent']['success'] ?? null ), 'execute-ability reports success' );

$r = t_call( 'mcp-adapter-discover-abilities', array() );
$listed = array_column( $r['body']['result']['structuredContent']['abilities'] ?? array(), 'name' );
t_ok( in_array( 'snn/get-site-info', $listed, true ), 'discover lists snn/get-site-info' );

$r = t_call( 'mcp-adapter-get-ability-info', array( 'ability_name' => 'snn/get-site-info' ) );
t_ok( 'snn/get-site-info' === ( $r['body']['result']['structuredContent']['name'] ?? null ), 'get-ability-info returns the ability' );

$r = t_call( 'mcp-adapter-get-ability-info', array( 'ability_name' => 'snn/no-such-ability' ) );
t_ok( true === ( $r['body']['result']['isError'] ?? null ), 'an unknown ability is an isError result' );
t_ok( false !== strpos( $r['body']['result']['content'][0]['text'] ?? '', 'not found' ), 'the error explains the ability was not found' );

$r = t_call( 'mcp-adapter-execute-ability', array() );
t_ok( true === ( $r['body']['result']['isError'] ?? null ), 'execute-ability without ability_name is an isError result' );

$r = t_call( 'snn-bricks-start-here', array() );
t_ok( false === ( $r['body']['result']['isError'] ?? true ) && isset( $r['body']['result']['structuredContent'] ), 'named tool returns structuredContent' );
t_ok( is_string( $r['body']['result']['content'][0]['text'] ?? null ), 'named tool returns a text block too' );

$r = t_call( 'snn-bricks-get-page-tree', array() );
t_ok( true === ( $r['body']['result']['isError'] ?? null ), 'missing required input is an isError result' );

$r = t_call( 'snn-no-such-tool', array() );
t_ok( -32602 === ( $r['body']['error']['code'] ?? null ) && 200 === $r['status'], 'an unknown tool is a JSON-RPC invalid-params error with 200' );

echo "errors and status codes\n";
$r = t_send( array( 'jsonrpc' => '2.0', 'id' => 5, 'method' => 'resources/list' ) );
t_ok( -32601 === ( $r['body']['error']['code'] ?? null ) && 200 === $r['status'], 'unknown method is -32601 with 200' );

$r = t_send( '{not json' );
t_ok( 400 === $r['status'] && -32700 === ( $r['body']['error']['code'] ?? null ), 'invalid JSON is a parse error with 400' );

$r = t_send( array( array( 'jsonrpc' => '2.0', 'id' => 6, 'method' => 'ping' ) ) );
t_ok( 400 === $r['status'] && -32600 === ( $r['body']['error']['code'] ?? null ), 'a batch is refused with 400' );

$r = t_send( null, array(), '/snn-mcp/v1/mcp', 'GET' );
t_ok( 405 === $r['status'], 'GET is 405 (no SSE stream)' );

$r = t_send( null, array(), '/snn-mcp/v1/mcp', 'DELETE' );
t_ok( 405 === $r['status'], 'DELETE is 405 (no sessions)' );

$r = t_send( array( 'jsonrpc' => '2.0', 'id' => 7, 'method' => 'ping' ), array( 'MCP-Protocol-Version' => '1999-01-01' ) );
t_ok( 400 === $r['status'], 'an unsupported MCP-Protocol-Version header is 400' );

$r = t_send( array( 'jsonrpc' => '2.0', 'id' => 8, 'method' => 'ping' ), array( 'MCP-Protocol-Version' => '2025-11-25' ) );
t_ok( 200 === $r['status'], 'a supported MCP-Protocol-Version header is accepted' );

$r = t_send( array( 'jsonrpc' => '2.0', 'id' => 9, 'method' => 'ping' ), array( 'Origin' => 'https://evil.example' ) );
t_ok( 403 === $r['status'], 'a foreign Origin is refused with 403' );

$r = t_send( array( 'jsonrpc' => '2.0', 'id' => 10, 'method' => 'ping' ), array( 'Origin' => home_url() ) );
t_ok( 200 === $r['status'], 'the site Origin is accepted' );

$r = t_send( array( 'jsonrpc' => '2.0', 'id' => 11, 'method' => 'ping' ), array(), '/mcp/mcp-adapter-default-server' );
t_ok( 200 === $r['status'] && array() === ( $r['body']['result'] ?? null ), 'the legacy URL answers the same way' );

wp_set_current_user( 0 );
$r = t_send( array( 'jsonrpc' => '2.0', 'id' => 12, 'method' => 'ping' ) );
t_ok( in_array( $r['status'], array( 401, 403 ), true ), 'an anonymous request is refused' );
wp_set_current_user( 1 );

echo "adapter parity\n";
if ( class_exists( '\WP\MCP\Domain\Tools\RegisterAbilityAsMcpTool' ) ) {
    $ability_by_tool = array();
    foreach ( wp_get_abilities() as $ability ) {
        $ability_by_tool[ str_replace( '/', '-', $ability->get_name() ) ] = $ability;
    }
    foreach ( $tools as $tool ) {
        $ability = $ability_by_tool[ $tool['name'] ] ?? null;
        if ( ! $ability ) {
            continue;
        }
        $built = \WP\MCP\Domain\Tools\RegisterAbilityAsMcpTool::build( $ability );
        if ( is_wp_error( $built ) ) {
            t_ok( false, $tool['name'] . ' builds in the adapter' );
            continue;
        }
        $adapter = t_normalize( $built['tool_data'] );
        $ours    = t_normalize( $tool );
        t_ok( wp_json_encode( $adapter ) === wp_json_encode( $ours ), $tool['name'] . ' has the same definition as the adapter' );
        if ( wp_json_encode( $adapter ) !== wp_json_encode( $ours ) ) {
            echo '    adapter: ' . wp_json_encode( $adapter ) . "\n";
            echo '    ours:    ' . wp_json_encode( $ours ) . "\n";
        }
    }

    $adapter_run = \WP\MCP\Abilities\ExecuteAbilityAbility::execute( array( 'ability_name' => 'snn/get-site-info', 'parameters' => array() ) );
    $ours_run    = t_call( 'mcp-adapter-execute-ability', array( 'ability_name' => 'snn/get-site-info', 'parameters' => array() ) );
    t_ok( wp_json_encode( t_normalize( $adapter_run['data'] ?? null ) ) === wp_json_encode( t_normalize( $ours_run['body']['result']['structuredContent']['data'] ?? null ) ), 'execute-ability returns the same data as the adapter' );
} else {
    echo "  skipped: MCP Adapter not loaded\n";
}

echo "\n" . ( $GLOBALS['t_fail'] ? $GLOBALS['t_fail'] . " check(s) FAILED\n" : "all checks passed\n" );
