<?php
/**
 * Built-in MCP server: the Streamable HTTP endpoint and JSON-RPC dispatch.
 *
 * Endpoints:
 *  - /wp-json/snn-mcp/v1/mcp                  canonical
 *  - /wp-json/mcp/mcp-adapter-default-server  legacy URL, kept so connectors that
 *                                             already point at it keep working. It is
 *                                             registered only when no other plugin has
 *                                             taken that route.
 *
 * The server is stateless: it never issues an Mcp-Session-Id, so every POST
 * stands alone and carries MCP-Protocol-Version. Each request authenticates as a
 * WordPress user (Application Password, or a logged-in session with a REST nonce),
 * and every tool still runs the ability's own permission check.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SNN_MCP_Server {

    const NAMESPACE_CANONICAL = 'snn-mcp/v1';
    const ROUTE_CANONICAL     = '/mcp';

    const NAMESPACE_LEGACY = 'mcp';
    const ROUTE_LEGACY     = '/mcp-adapter-default-server';

    const INSTRUCTIONS = 'WordPress abilities of this site. Discover them with mcp-adapter-discover-abilities, or call a named tool directly. For Bricks pages, start with snn-bricks-start-here.';

    public static function register_routes() {
        if ( ! snn_mcp_is_enabled() ) {
            return;
        }

        $args = array(
            'methods'             => array( 'POST', 'GET', 'DELETE' ),
            'callback'            => array( __CLASS__, 'handle' ),
            'permission_callback' => array( __CLASS__, 'check_permission' ),
        );

        register_rest_route( self::NAMESPACE_CANONICAL, self::ROUTE_CANONICAL, $args );

        if ( ! self::legacy_route_taken() ) {
            register_rest_route( self::NAMESPACE_LEGACY, self::ROUTE_LEGACY, $args );
        }
    }

    /** True when something else already serves the legacy endpoint path. */
    private static function legacy_route_taken() {
        $routes = rest_get_server()->get_routes();
        return isset( $routes[ '/' . self::NAMESPACE_LEGACY . self::ROUTE_LEGACY ] );
    }

    public static function check_permission() {
        if ( ! is_user_logged_in() ) {
            return false;
        }
        return current_user_can( apply_filters( 'snn_mcp_transport_capability', 'read' ) );
    }

    public static function handle( WP_REST_Request $request ) {
        // Nothing is streamed and no sessions exist, so GET and DELETE have nothing to act on.
        if ( 'POST' !== $request->get_method() ) {
            $response = new WP_REST_Response( null, 405 );
            $response->header( 'Allow', 'POST' );
            return $response;
        }

        if ( ! self::origin_allowed( $request ) ) {
            return new WP_REST_Response( SNN_MCP_Protocol::error( null, SNN_MCP_Protocol::INVALID_REQUEST, 'Origin not allowed for this MCP endpoint.' ), 403 );
        }

        $version = $request->get_header( 'mcp-protocol-version' );
        if ( null !== $version && '' !== $version && ! SNN_MCP_Protocol::is_supported( $version ) ) {
            return new WP_REST_Response( SNN_MCP_Protocol::error( null, SNN_MCP_Protocol::INVALID_REQUEST, 'Unsupported MCP-Protocol-Version: ' . $version ), 400 );
        }

        $message = json_decode( (string) $request->get_body(), true, 512 );
        if ( JSON_ERROR_NONE !== json_last_error() ) {
            return new WP_REST_Response( SNN_MCP_Protocol::error( null, SNN_MCP_Protocol::PARSE_ERROR, 'Parse error: ' . json_last_error_msg() ), 400 );
        }

        if ( ! is_array( $message ) || array_values( $message ) === $message || ( $message['jsonrpc'] ?? null ) !== '2.0' ) {
            return new WP_REST_Response( SNN_MCP_Protocol::error( null, SNN_MCP_Protocol::INVALID_REQUEST, 'The payload must be one JSON-RPC 2.0 object; batches are not supported.' ), 400 );
        }

        // A reply to a request the server never sent: nothing to answer.
        if ( ! isset( $message['method'] ) || ! is_string( $message['method'] ) ) {
            return new WP_REST_Response( null, array_key_exists( 'result', $message ) || array_key_exists( 'error', $message ) ? 202 : 400 );
        }

        // Notifications (initialized, cancelled, ...) carry no id and get no body.
        if ( ! array_key_exists( 'id', $message ) ) {
            return new WP_REST_Response( null, 202 );
        }

        $id = $message['id'];
        if ( ! is_string( $id ) && ! is_int( $id ) ) {
            return new WP_REST_Response( SNN_MCP_Protocol::error( null, SNN_MCP_Protocol::INVALID_REQUEST, 'The id must be a string or an integer.' ), 400 );
        }

        $params = isset( $message['params'] ) && is_array( $message['params'] ) ? $message['params'] : array();

        try {
            $envelope = SNN_MCP_Protocol::result( $id, self::dispatch( $message['method'], $params ) );
        } catch ( SNN_MCP_Rpc_Exception $exception ) {
            $envelope = SNN_MCP_Protocol::error( $id, $exception->getCode(), $exception->getMessage() );
        } catch ( Throwable $exception ) {
            snn_mcp_debug( 'rpc.error', array( 'method' => $message['method'], 'message' => $exception->getMessage() ), 'error' );
            $envelope = SNN_MCP_Protocol::error( $id, SNN_MCP_Protocol::INTERNAL_ERROR, 'Failed to execute the request.' );
        }

        return new WP_REST_Response( $envelope, self::http_status( $envelope ) );
    }

    private static function dispatch( $method, array $params ) {
        switch ( $method ) {
            case 'initialize':
                return self::initialize( $params );
            case 'ping':
                return new stdClass();
            case 'tools/list':
                return array( 'tools' => SNN_MCP_Tools::list_tools() );
            case 'tools/call':
                return SNN_MCP_Tools::call_tool( $params );
        }

        throw new SNN_MCP_Rpc_Exception( 'Method not found: ' . $method, SNN_MCP_Protocol::METHOD_NOT_FOUND );
    }

    private static function initialize( array $params ) {
        $requested = is_string( $params['protocolVersion'] ?? null ) ? $params['protocolVersion'] : '';

        return array(
            'protocolVersion' => SNN_MCP_Protocol::negotiate( $requested ),
            'capabilities'    => array(
                'tools' => array( 'listChanged' => false ),
            ),
            'serverInfo'      => array(
                'name'    => 'snn-mcp',
                'version' => (string) wp_get_theme( get_stylesheet() )->get( 'Version' ),
            ),
            'instructions'    => self::INSTRUCTIONS,
        );
    }

    /**
     * Browsers send Origin; server-side clients usually do not. Only the site's own
     * origin is accepted by default, which blocks DNS-rebinding style pages. Other
     * origins can be allowed with the snn_mcp_allowed_origins filter.
     */
    private static function origin_allowed( WP_REST_Request $request ) {
        $origin = $request->get_header( 'origin' );
        if ( null === $origin || '' === $origin ) {
            return true;
        }

        $allowed = apply_filters( 'snn_mcp_allowed_origins', array( self::site_origin() ) );
        return is_array( $allowed ) && in_array( rtrim( $origin, '/' ), $allowed, true );
    }

    private static function site_origin() {
        $parts  = wp_parse_url( home_url() );
        $origin = ( $parts['scheme'] ?? 'https' ) . '://' . ( $parts['host'] ?? '' );
        if ( ! empty( $parts['port'] ) ) {
            $origin .= ':' . $parts['port'];
        }
        return $origin;
    }

    /** JSON-RPC errors travel in a 200 response, except malformed requests and server faults. */
    private static function http_status( array $envelope ) {
        $code = $envelope['error']['code'] ?? null;
        if ( SNN_MCP_Protocol::PARSE_ERROR === $code || SNN_MCP_Protocol::INVALID_REQUEST === $code ) {
            return 400;
        }
        return SNN_MCP_Protocol::INTERNAL_ERROR === $code ? 500 : 200;
    }
}
