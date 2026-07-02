<?php
/**
 * SNN MCP Connections
 *
 * File: ai-mcp.php
 *
 * Purpose: Manages MCP (Model Context Protocol) server connections.
 * Provides admin UI for adding/editing/testing connections, and
 * injects MCP tools as dynamic abilities into AI chat overlays.
 *
 * Architecture:
 * - SNN_MCP_Crypto    — encrypt/decrypt auth values at rest
 * - SNN_MCP_Client    — zero-dependency JSON-RPC 2.0 MCP client
 * - SNN_MCP_Manager   — connection CRUD + tool aggregation
 * - Admin tab         — repeater UI with test/debug logs
 * - AJAX handlers     — test, call, refresh tools
 * - Context helpers   — inject tools as abilities into chats
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ================================================================
// 1. SNN_MCP_Crypto — Encrypt/Decrypt auth values at rest
// ================================================================

class SNN_MCP_Crypto {

    /**
     * Derive a 256-bit key from WordPress salts.
     */
    private static function get_key() {
        $salt = defined( 'LOGGED_IN_SALT' ) ? LOGGED_IN_SALT : wp_salt( 'logged_in' );
        $key  = hash( 'sha256', $salt . 'snn_mcp_encryption', true );
        return $key;
    }

    /**
     * Encrypt a plaintext value.
     * Returns 'encrypted:' prefix + base64( iv + hmac + ciphertext ).
     */
    public static function encrypt( $plaintext ) {
        if ( empty( $plaintext ) ) {
            return '';
        }
        $key       = self::get_key();
        $iv        = random_bytes( 16 ); // 128-bit IV for AES-256-CBC
        $cipher    = openssl_encrypt( $plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );
        $hmac      = hash_hmac( 'sha256', $iv . $cipher, $key, true );
        $encoded   = base64_encode( $iv . $hmac . $cipher );
        return 'encrypted:' . $encoded;
    }

    /**
     * Decrypt an 'encrypted:' prefixed value.
     * Returns the plaintext, or empty string on failure.
     */
    public static function decrypt( $encrypted ) {
        if ( empty( $encrypted ) || ! is_string( $encrypted ) ) {
            return '';
        }
        // Not encrypted — return as-is (backwards compat)
        if ( strpos( $encrypted, 'encrypted:' ) !== 0 ) {
            return $encrypted;
        }
        $encoded = substr( $encrypted, 10 );
        $data    = base64_decode( $encoded );
        if ( strlen( $data ) < 48 ) { // 16 iv + 32 hmac minimum
            return '';
        }
        $key      = self::get_key();
        $iv       = substr( $data, 0, 16 );
        $hmac     = substr( $data, 16, 32 );
        $cipher   = substr( $data, 48 );
        $calc_hmac = hash_hmac( 'sha256', $iv . $cipher, $key, true );
        if ( ! hash_equals( $hmac, $calc_hmac ) ) {
            return ''; // Tampered data
        }
        $plaintext = openssl_decrypt( $cipher, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );
        return ( false === $plaintext ) ? '' : $plaintext;
    }

    /**
     * Mask a token for display (show first 4 + last 4 chars).
     */
    public static function mask( $value ) {
        if ( empty( $value ) ) {
            return '';
        }
        $len = strlen( $value );
        if ( $len <= 8 ) {
            return str_repeat( '•', $len );
        }
        return substr( $value, 0, 4 ) . str_repeat( '•', $len - 8 ) . substr( $value, -4 );
    }
}

// ================================================================
// 2. SNN_MCP_Exception — Typed exceptions with OAuth metadata
// ================================================================

class SNN_MCP_Exception extends \Exception {

    private $oauth_url = '';
    private $response_headers = '';
    private $response_body = '';

    public function set_oauth_url( $url ) {
        $this->oauth_url = $url;
    }

    public function get_oauth_url() {
        return $this->oauth_url;
    }

    public function set_response_headers( $headers ) {
        $this->response_headers = $headers;
    }

    public function get_response_headers() {
        return $this->response_headers;
    }

    public function set_response_body( $body ) {
        $this->response_body = $body;
    }

    public function get_response_body() {
        return $this->response_body;
    }

    /**
     * Try to detect if this is an OAuth server based on the response.
     */
    public function is_oauth_server() {
        // If we have an explicit OAuth URL from the error data
        if ( ! empty( $this->oauth_url ) ) {
            return true;
        }

        // Check WWW-Authenticate header for Bearer (indicates OAuth)
        if ( stripos( $this->response_headers, 'WWW-Authenticate: Bearer' ) !== false ) {
            return true;
        }

        // Known OAuth MCP servers by URL patterns
        $body_lower = strtolower( $this->response_body );
        if (
            strpos( $body_lower, 'oauth' ) !== false ||
            strpos( $body_lower, 'authorize' ) !== false ||
            strpos( $body_lower, 'login' ) !== false ||
            strpos( $body_lower, 'sign in' ) !== false
        ) {
            return true;
        }

        return false;
    }
}

// ================================================================
// 2b. SNN_MCP_Client — Zero-dependency JSON-RPC 2.0 MCP client
// ================================================================

class SNN_MCP_Client {

    private $url;
    private $headers  = [];
    private $session_id = null;
    private $request_id = 0;
    private $timeout    = 30;
    private $connect_timeout = 10;
    private $server_info    = null;
    private $capabilities   = null;

    /**
     * @param string $url          MCP server endpoint URL
     * @param string $auth_value   Decrypted auth token/key (empty for no auth)
     * @param string $auth_type    'bearer' | 'api_key' | 'none'
     * @param string $header_name  Custom header name for api_key auth type
     */
    public function __construct( $url, $auth_value = '', $auth_type = 'bearer', $header_name = 'X-API-Key' ) {
        $this->url     = trailingslashit( $url ) . ( str_contains( $url, '?' ) ? '' : '' );
        $this->url     = rtrim( $this->url, '/' ); // MCP endpoints typically don't need trailing slash
        $this->headers = [ 'Content-Type' => 'application/json', 'Accept' => 'application/json' ];

        if ( ! empty( $auth_value ) && $auth_type !== 'none' ) {
            if ( $auth_type === 'api_key' ) {
                $this->headers[ $header_name ] = $auth_value;
            } else {
                // Default: Bearer token
                $this->headers['Authorization'] = 'Bearer ' . $auth_value;
            }
        }
    }

    public function set_timeout( $seconds ) {
        $this->timeout = max( 5, (int) $seconds );
        return $this;
    }

    public function set_connect_timeout( $seconds ) {
        $this->connect_timeout = max( 2, (int) $seconds );
        return $this;
    }

    // ── Core JSON-RPC transport ─────────────────────────────

    /**
     * Send a JSON-RPC request and return the parsed result.
     * Throws SNN_MCP_Exception on transport or protocol errors.
     */
    private function send( $method, $params = [] ) {
        $body = [
            'jsonrpc' => '2.0',
            'id'      => 'snn_' . ( ++$this->request_id ),
            'method'  => $method,
            'params'  => ! empty( $params ) ? (object) $params : new \stdClass(),
        ];

        $headers = $this->headers;
        if ( $this->session_id ) {
            $headers['Mcp-Session-Id'] = $this->session_id;
        }

        // Build cURL header array
        $curl_headers = [];
        foreach ( $headers as $k => $v ) {
            $curl_headers[] = $k . ': ' . $v;
        }

        $ch = curl_init();
        curl_setopt_array( $ch, [
            CURLOPT_URL            => $this->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => wp_json_encode( $body ),
            CURLOPT_HTTPHEADER     => $curl_headers,
            CURLOPT_CONNECTTIMEOUT => $this->connect_timeout,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HEADER         => true,
        ] );

        $response      = curl_exec( $ch );
        $status_code   = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        $header_size   = (int) curl_getinfo( $ch, CURLINFO_HEADER_SIZE );
        $total_time_ms = round( curl_getinfo( $ch, CURLINFO_TOTAL_TIME ) * 1000 );
        $curl_errno    = curl_errno( $ch );
        $curl_error    = curl_error( $ch );
        curl_close( $ch );

        if ( false === $response ) {
            throw new SNN_MCP_Exception( 'cURL error ' . $curl_errno . ': ' . $curl_error, $status_code );
        }

        // Split headers from body
        $response_headers = substr( $response, 0, $header_size );
        $response_body    = substr( $response, $header_size );

        // Capture Mcp-Session-Id from response headers
        if ( preg_match( '/^Mcp-Session-Id:\s*(.+)$/im', $response_headers, $m ) ) {
            $this->session_id = trim( $m[1] );
        }

        $data = json_decode( $response_body, true );

        // ── Handle non-JSON responses (common with OAuth servers) ──────
        if ( ! $data ) {
            $ex = new SNN_MCP_Exception(
                'Invalid JSON response (HTTP ' . $status_code . '). Raw: ' . substr( $response_body, 0, 300 ),
                $status_code
            );
            $ex->set_response_headers( $response_headers );
            $ex->set_response_body( $response_body );
            throw $ex;
        }

        // Check JSON-RPC error
        if ( isset( $data['error'] ) ) {
            $err = $data['error'];
            $msg = isset( $err['message'] ) ? $err['message'] : json_encode( $err );
            $code = isset( $err['code'] ) ? $err['code'] : 0;
            $ex  = new SNN_MCP_Exception( 'JSON-RPC error [' . $code . ']: ' . $msg, $status_code, $code );
            // Pass through OAuth URL if present in error data
            if ( isset( $err['data']['authUrl'] ) ) {
                $ex->set_oauth_url( $err['data']['authUrl'] );
            }
            throw $ex;
        }

        return [
            'result'      => isset( $data['result'] ) ? $data['result'] : null,
            'status_code' => $status_code,
            'time_ms'     => $total_time_ms,
        ];
    }
            'status_code' => $status_code,
            'time_ms'     => $total_time_ms,
        ];
    }

    // ── MCP Lifecycle ───────────────────────────────────────

    /**
     * Initialize the MCP connection (handshake).
     * Returns server info array.
     */
    public function initialize() {
        $response = $this->send( 'initialize', [
            'protocolVersion' => '2025-06-18',
            'capabilities'    => [ 'tools' => [ 'listChanged' => true ] ],
            'clientInfo'      => [ 'name' => 'SNN_AI', 'version' => '1.0' ],
        ] );

        $this->server_info  = isset( $response['result']['serverInfo'] ) ? $response['result']['serverInfo'] : null;
        $this->capabilities = isset( $response['result']['capabilities'] ) ? $response['result']['capabilities'] : null;

        // Check for OAuth URL in the initialize result
        $oauth_url = isset( $response['result']['auth']['authUrl'] ) ? $response['result']['auth']['authUrl'] : '';

        // Send initialized notification
        $this->send( 'notifications/initialized', [] );

        return [
            'server_info'  => $this->server_info,
            'capabilities' => $this->capabilities,
            'protocol'     => isset( $response['result']['protocolVersion'] ) ? $response['result']['protocolVersion'] : 'unknown',
            'session_id'   => $this->session_id,
            'oauth_url'    => $oauth_url,
        ];
    }

    /**
     * Probe the server to determine authentication requirements.
     * Sends a minimal initialize WITHOUT any auth headers.
     * Returns [ 'auth_type' => 'none'|'oauth'|'static_token', 'oauth_url' => '', 'message' => '' ]
     */
    public static function probe_auth( $url, $timeout = 10 ) {
        $probe = new self( $url, '', 'none' );
        $probe->set_timeout( $timeout );
        $probe->set_connect_timeout( 5 );

        try {
            $result = $probe->initialize();
            $oauth_url = isset( $result['oauth_url'] ) ? $result['oauth_url'] : '';

            if ( ! empty( $oauth_url ) ) {
                return [
                    'auth_type' => 'oauth',
                    'oauth_url' => $oauth_url,
                    'message'   => 'OAuth authentication required. Server provided an authorization URL.',
                ];
            }

            return [
                'auth_type' => 'none',
                'oauth_url' => '',
                'message'   => 'Server is publicly accessible — no authentication required.',
            ];
        } catch ( SNN_MCP_Exception $e ) {
            $status = $e->getCode();

            // Check if server explicitly provides an OAuth URL
            $oauth_url = $e->get_oauth_url();

            if ( $e->is_oauth_server() || $status === 401 ) {
                // Try to extract OAuth URL from WWW-Authenticate header
                if ( empty( $oauth_url ) ) {
                    $headers = $e->get_response_headers();
                    if ( preg_match( '/Bearer\s+realm="([^"]+)"/i', $headers, $m ) ) {
                        $oauth_url = $m[1];
                    }
                }

                // Known OAuth MCP servers
                $known_oauth = [
                    'mcp.figma.com' => [
                        'auth_url' => 'https://www.figma.com/oauth?client_id=mcp&redirect_uri=urn:ietf:wg:oauth:2.0:oob&scope=files:read',
                        'message'  => 'Figma MCP requires OAuth. Click "Start OAuth Flow" to authenticate with your Figma account. A browser window will open for you to authorize access.',
                    ],
                ];

                foreach ( $known_oauth as $host => $info ) {
                    if ( strpos( $url, $host ) !== false ) {
                        return [
                            'auth_type' => 'oauth',
                            'oauth_url' => $info['auth_url'],
                            'message'   => $info['message'],
                        ];
                    }
                }

                return [
                    'auth_type' => 'oauth',
                    'oauth_url' => $oauth_url,
                    'message'   => empty( $oauth_url )
                        ? 'This server requires OAuth authentication (HTTP 401). You need to authenticate through the provider\'s website first. Check the MCP server documentation for OAuth setup instructions.'
                        : 'OAuth authentication required. Authorization URL: ' . $oauth_url,
                ];
            }

            if ( $status === 403 ) {
                return [
                    'auth_type' => 'static_token',
                    'oauth_url' => '',
                    'message'   => 'Server requires a static API key or token (HTTP 403). Paste your token in the field above.',
                ];
            }

            return [
                'auth_type' => 'static_token',
                'oauth_url' => '',
                'message'   => 'Server requires authentication (HTTP ' . $status . '). Provide a token or API key.',
            ];
        }
    }

    /**
     * Ping the server.
     */
    public function ping() {
        $response = $this->send( 'ping', [] );
        return $response['time_ms'];
    }

    // ── Tool Discovery ──────────────────────────────────────

    /**
     * List all tools from the MCP server.
     */
    public function list_tools() {
        $response = $this->send( 'tools/list', [] );
        return isset( $response['result']['tools'] ) ? $response['result']['tools'] : [];
    }

    /**
     * List resources from the MCP server.
     */
    public function list_resources() {
        $response = $this->send( 'resources/list', [] );
        return isset( $response['result']['resources'] ) ? $response['result']['resources'] : [];
    }

    // ── Tool Execution ──────────────────────────────────────

    /**
     * Call a specific tool on the MCP server.
     */
    public function call_tool( $name, $arguments = [] ) {
        $response = $this->send( 'tools/call', [
            'name'      => $name,
            'arguments' => $arguments,
        ] );
        return $response['result'];
    }

    // ── Helpers ─────────────────────────────────────────────

    public function get_server_info() {
        return $this->server_info;
    }

    public function get_capabilities() {
        return $this->capabilities;
    }

    public function get_session_id() {
        return $this->session_id;
    }

    /**
     * Extract text content from a tool call result.
     * MCP tools return { content: [{ type: "text", text: "..." }] }
     */
    public static function extract_text( $result ) {
        if ( ! isset( $result['content'] ) || ! is_array( $result['content'] ) ) {
            return json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
        }
        $texts = [];
        foreach ( $result['content'] as $item ) {
            if ( isset( $item['text'] ) ) {
                $texts[] = $item['text'];
            } elseif ( isset( $item['data'] ) ) {
                $texts[] = '[Binary data: ' . ( isset( $item['mimeType'] ) ? $item['mimeType'] : 'unknown' ) . ']';
            }
        }
        return implode( "\n", $texts );
    }
}

// ================================================================
// 3. SNN_MCP_Manager — Connection CRUD + Tool Aggregation
// ================================================================

class SNN_MCP_Manager {

    /**
     * Get all connections from the database.
     */
    public static function get_all() {
        $connections = get_option( 'snn_mcp_connections', [] );
        if ( ! is_array( $connections ) ) {
            $connections = [];
        }
        return array_values( $connections );
    }

    /**
     * Get a single connection by its slug (semantic name).
     */
    public static function get_by_slug( $slug ) {
        $connections = self::get_all();
        foreach ( $connections as $conn ) {
            if ( self::slugify( $conn['name'] ) === $slug ) {
                return $conn;
            }
        }
        return null;
    }

    /**
     * Get only enabled connections.
     */
    public static function get_enabled() {
        return array_filter( self::get_all(), function( $conn ) {
            return ! empty( $conn['enabled'] );
        } );
    }

    /**
     * Get decrypted auth value for a connection.
     */
    public static function get_auth( $connection ) {
        $auth_value = isset( $connection['auth_value'] ) ? $connection['auth_value'] : '';
        if ( empty( $auth_value ) ) {
            return '';
        }
        return SNN_MCP_Crypto::decrypt( $auth_value );
    }

    /**
     * Build an initialized MCP client for a connection.
     */
    public static function get_client( $connection ) {
        $auth_value = self::get_auth( $connection );
        $auth_type  = isset( $connection['auth_type'] ) ? $connection['auth_type'] : 'bearer';
        $header_name = isset( $connection['header_name'] ) ? $connection['header_name'] : 'X-API-Key';
        $timeout     = isset( $connection['timeout'] ) ? (int) $connection['timeout'] : 30;

        $client = new SNN_MCP_Client(
            $connection['url'],
            $auth_value,
            $auth_type,
            $header_name
        );
        $client->set_timeout( $timeout );
        return $client;
    }

    /**
     * Discover tools for a connection and cache them.
     * Returns [ 'success' => bool, 'tools' => [], 'logs' => [], 'error' => '' ]
     */
    public static function test_and_discover( $connection, $save_cache = true ) {
        $logs  = [];
        $tools = [];

        $logs[] = [ 'type' => 'info', 'message' => 'Connecting to ' . $connection['name'] . ' at ' . $connection['url'] ];

        $auth_type  = isset( $connection['auth_type'] ) ? $connection['auth_type'] : 'bearer';
        $auth_val   = self::get_auth( $connection );
        $has_creds  = ! empty( $auth_val );

        if ( $auth_type === 'oauth' ) {
            $logs[] = [ 'type' => 'info', 'message' => 'Auth type: OAuth 2.0 (token present: ' . ( $has_creds ? 'yes' : 'no — needs authorization' ) . ')' ];
        } elseif ( $has_creds ) {
            $masked = SNN_MCP_Crypto::mask( $auth_val );
            $logs[] = [ 'type' => 'info', 'message' => 'Auth type: ' . strtoupper( $auth_type ) . ' (' . $masked . ')' ];
        } else {
            $logs[] = [ 'type' => 'info', 'message' => 'Auth type: None (public server)' ];
        }

        $logs[] = [ 'type' => 'info', 'message' => str_repeat( '─', 50 ) ];

        // ── Auth Probe (if no credentials provided) ──────────────────
        if ( ! $has_creds && $auth_type !== 'oauth' ) {
            $logs[] = [ 'type' => 'info', 'message' => 'PHASE 0: Auth Detection — probing server without credentials' ];
            try {
                $probe = SNN_MCP_Client::probe_auth( $connection['url'], isset( $connection['timeout'] ) ? (int) $connection['timeout'] : 10 );
                $logs[] = [ 'type' => 'info', 'message' => 'Probe result: ' . $probe['message'] ];

                if ( $probe['auth_type'] === 'oauth' ) {
                    $logs[] = [ 'type' => 'warning', 'message' => '⚠️ This server requires OAuth authentication.' ];
                    $logs[] = [ 'type' => 'info', 'message' => 'Switch auth type to "OAuth 2.0" and click "Start OAuth Flow" to authenticate.' ];
                    if ( ! empty( $probe['oauth_url'] ) ) {
                        $logs[] = [ 'type' => 'info', 'message' => 'Authorization URL: ' . $probe['oauth_url'] ];
                    }
                    return [
                        'success'      => false,
                        'tools'        => [],
                        'logs'         => $logs,
                        'error'        => 'OAuth authentication required.',
                        'oauth_needed' => true,
                        'oauth_url'    => $probe['oauth_url'],
                    ];
                }

                if ( $probe['auth_type'] === 'static_token' ) {
                    $logs[] = [ 'type' => 'warning', 'message' => '⚠️ This server requires a static token/API key.' ];
                    $logs[] = [ 'type' => 'info', 'message' => 'Obtain a token from the service provider and paste it in the Token field above, then test again.' ];
                    return [
                        'success'      => false,
                        'tools'        => [],
                        'logs'         => $logs,
                        'error'        => 'Static token required.',
                        'oauth_needed' => false,
                    ];
                }
            } catch ( \Exception $e ) {
                $logs[] = [ 'type' => 'warning', 'message' => 'Auth probe failed: ' . $e->getMessage() ];
                // Continue with normal test — maybe the probe was wrong
            }
            $logs[] = [ 'type' => 'info', 'message' => str_repeat( '─', 50 ) ];
        }

        // ── OAuth but no token yet ──────────────────────────────────
        if ( $auth_type === 'oauth' && ! $has_creds ) {
            $logs[] = [ 'type' => 'warning', 'message' => '⚠️ OAuth is configured but no access token is present.' ];
            $logs[] = [ 'type' => 'info', 'message' => 'Click "Start OAuth Flow" to authenticate with the provider.' ];
            return [
                'success'      => false,
                'tools'        => [],
                'logs'         => $logs,
                'error'        => 'OAuth authorization needed.',
                'oauth_needed' => true,
                'oauth_url'    => '',
            ];
        }

        try {
            // PHASE 1: HTTP Connectivity
            $logs[]    = [ 'type' => 'info', 'message' => 'PHASE 1: HTTP Connectivity — Sending POST to endpoint' ];
            $client    = self::get_client( $connection );

            // PHASE 2: MCP Protocol Handshake
            $logs[]    = [ 'type' => 'info', 'message' => 'PHASE 2: MCP Protocol Handshake (initialize)' ];
            $init      = $client->initialize();
            $server    = $init['server_info'];
            $server_name = isset( $server['name'] ) ? $server['name'] : 'Unknown';
            $server_ver  = isset( $server['version'] ) ? $server['version'] : '?';
            $logs[] = [ 'type' => 'success', 'message' => 'Protocol version negotiated: ' . $init['protocol'] ];
            $logs[] = [ 'type' => 'success', 'message' => 'Mcp-Session-Id: ' . substr( $init['session_id'], 0, 12 ) . '... (captured)' ];
            $logs[] = [ 'type' => 'info', 'message' => 'Server: "' . $server_name . '" v' . $server_ver ];

            // Capabilities
            $caps = $init['capabilities'];
            $cap_list = [];
            if ( $caps ) {
                foreach ( $caps as $k => $v ) {
                    if ( $v ) $cap_list[] = $k;
                }
            }
            $logs[] = [ 'type' => 'success', 'message' => 'Server capabilities: ' . ( $cap_list ? implode( ' ✓  ', $cap_list ) . ' ✓' : 'none' ) ];
            $logs[] = [ 'type' => 'success', 'message' => 'Initialized notification sent — handshake complete' ];

            $logs[] = [ 'type' => 'info', 'message' => str_repeat( '─', 50 ) ];

            // PHASE 3: Tool Discovery
            $logs[] = [ 'type' => 'info', 'message' => 'PHASE 3: Tool Discovery (tools/list)' ];
            $tools  = $client->list_tools();
            if ( empty( $tools ) ) {
                $logs[] = [ 'type' => 'warning', 'message' => 'No tools discovered. The server may not expose any tools.' ];
            } else {
                $logs[] = [ 'type' => 'success', 'message' => count( $tools ) . ' tools discovered:' ];
                foreach ( $tools as $tool ) {
                    $name = isset( $tool['name'] ) ? $tool['name'] : '?';
                    $desc = isset( $tool['description'] ) ? $tool['description'] : 'No description';
                    $logs[] = [ 'type' => 'success', 'message' => '  • ' . $name . ' — ' . $desc ];
                }
            }

            $logs[] = [ 'type' => 'info', 'message' => str_repeat( '─', 50 ) ];

            // PHASE 4: Resource Discovery (optional)
            $logs[] = [ 'type' => 'info', 'message' => 'PHASE 4: Resource Discovery (resources/list)' ];
            try {
                $resources = $client->list_resources();
                if ( empty( $resources ) ) {
                    $logs[] = [ 'type' => 'info', 'message' => 'No resources exposed.' ];
                } else {
                    $logs[] = [ 'type' => 'success', 'message' => count( $resources ) . ' resources discovered' ];
                }
            } catch ( \Exception $e ) {
                $logs[] = [ 'type' => 'info', 'message' => 'Resources not supported by this server (skipped).' ];
            }

            $logs[] = [ 'type' => 'info', 'message' => str_repeat( '─', 50 ) ];

            // PHASE 5: Health Check
            $logs[] = [ 'type' => 'info', 'message' => 'PHASE 5: Health Check (ping)' ];
            try {
                $ping_ms = $client->ping();
                $logs[]  = [ 'type' => 'success', 'message' => 'Server responded to ping in ' . $ping_ms . 'ms' ];
            } catch ( \Exception $e ) {
                $logs[] = [ 'type' => 'warning', 'message' => 'Ping not supported by this server (skipped).' ];
            }

            $logs[] = [ 'type' => 'info', 'message' => str_repeat( '─', 50 ) ];
            $logs[] = [ 'type' => 'success', 'message' => '✅ Connection test PASSED! ' . $connection['name'] . ' is ready with ' . count( $tools ) . ' tools.' ];

            // Save tools cache
            if ( $save_cache ) {
                self::update_tools_cache( $connection, $tools );
            }

            return [
                'success' => true,
                'tools'   => $tools,
                'logs'    => $logs,
                'error'   => '',
            ];

        } catch ( \Exception $e ) {
            $code = $e->getCode();
            $msg  = $e->getMessage();

            $logs[] = [ 'type' => 'error', 'message' => $msg ];

            // Check for OAuth requirement
            $oauth_url = '';
            if ( $e instanceof SNN_MCP_Exception ) {
                $oauth_url = $e->get_oauth_url();
                if ( $e->is_oauth_server() || ( $code === 401 && empty( $auth_val ) ) ) {
                    $logs[] = [ 'type' => 'warning', 'message' => '⚠️ This server requires OAuth 2.0 authentication (not a static API key).' ];
                    $logs[] = [ 'type' => 'info', 'message' => 'Switch the auth type dropdown to "OAuth 2.0" and use the "Start OAuth Flow" button.' ];
                    if ( $oauth_url ) {
                        $logs[] = [ 'type' => 'info', 'message' => 'Authorization URL: ' . $oauth_url ];
                    }
                    return [
                        'success'      => false,
                        'tools'        => [],
                        'logs'         => $logs,
                        'error'        => 'OAuth authentication required — not a static token server.',
                        'oauth_needed' => true,
                        'oauth_url'    => $oauth_url,
                    ];
                }
            }

            // Provide helpful tips based on error
            if ( strpos( $msg, 'cURL error' ) !== false && strpos( $msg, 'Could not resolve' ) !== false ) {
                $logs[] = [ 'type' => 'error', 'message' => 'Tip: Could not resolve hostname. Check the URL is correct and the server is reachable.' ];
            } elseif ( strpos( $msg, 'cURL error' ) !== false && strpos( $msg, 'timed out' ) !== false ) {
                $logs[] = [ 'type' => 'error', 'message' => 'Tip: Connection timed out. Verify the server is running and the firewall allows the connection.' ];
            } elseif ( strpos( $msg, 'cURL error' ) !== false && strpos( $msg, 'SSL' ) !== false ) {
                $logs[] = [ 'type' => 'error', 'message' => 'Tip: SSL certificate error. The server may be using a self-signed certificate or HTTPS is not properly configured.' ];
            } elseif ( $code === -32000 || $code === -32001 ) {
                $logs[] = [ 'type' => 'error', 'message' => 'Tip: The server rejected the protocol version. It may require a different MCP protocol version.' ];
            } elseif ( strpos( $msg, '401' ) !== false || strpos( $msg, '403' ) !== false ) {
                $logs[] = [ 'type' => 'error', 'message' => 'Tip: Authentication failed. Check your API key or token. The server requires valid credentials.' ];
            } elseif ( strpos( $msg, '404' ) !== false ) {
                $logs[] = [ 'type' => 'error', 'message' => 'Tip: Endpoint not found. Verify the URL points to a valid MCP server endpoint (usually ends with /mcp).' ];
            } elseif ( strpos( $msg, 'Invalid JSON' ) !== false && strpos( $msg, '401' ) !== false ) {
                $logs[] = [ 'type' => 'error', 'message' => 'Tip: This may be an OAuth-secured server. Try switching auth type to "OAuth 2.0".' ];
            } elseif ( strpos( $msg, 'Invalid JSON' ) !== false ) {
                $logs[] = [ 'type' => 'error', 'message' => 'Tip: The server returned non-JSON content. It may not be an MCP-compatible endpoint.' ];
            }

            return [
                'success' => false,
                'tools'   => [],
                'logs'    => $logs,
                'error'   => $msg,
            ];
        }
    }

    /**
     * Update the tools cache for a specific connection.
     */
    public static function update_tools_cache( $connection, $tools ) {
        $connections = self::get_all();
        $slug        = self::slugify( $connection['name'] );

        foreach ( $connections as &$conn ) {
            if ( self::slugify( $conn['name'] ) === $slug ) {
                $conn['tools_cache'] = $tools;
                $conn['last_sync']   = current_time( 'mysql' );
                break;
            }
        }
        unset( $conn );

        update_option( 'snn_mcp_connections', $connections );
    }

    /**
     * Get all MCP tools formatted as abilities for chat context.
     * Returns an array of ability-like objects that can be merged into ChatState.abilities.
     */
    public static function get_tools_as_abilities() {
        $connections = self::get_enabled();
        $abilities   = [];

        foreach ( $connections as $conn ) {
            $slug  = self::slugify( $conn['name'] );
            $tools = isset( $conn['tools_cache'] ) ? $conn['tools_cache'] : [];

            if ( empty( $tools ) ) {
                // Try to discover tools now if cache is empty
                $result = self::test_and_discover( $conn, true );
                if ( $result['success'] ) {
                    $tools = $result['tools'];
                }
            }

            foreach ( $tools as $tool ) {
                $tool_name = isset( $tool['name'] ) ? $tool['name'] : '';
                if ( empty( $tool_name ) ) {
                    continue;
                }

                $ability_name = 'mcp/' . $slug . '/' . $tool_name;
                $abilities[]  = [
                    'name'         => $ability_name,
                    'label'        => $conn['name'] . ': ' . $tool_name,
                    'description'  => isset( $tool['description'] ) ? $tool['description'] : 'MCP tool from ' . $conn['name'],
                    'category'     => 'mcp/' . $slug,
                    'input_schema' => isset( $tool['inputSchema'] ) ? $tool['inputSchema'] : [ 'type' => 'object', 'properties' => [] ],
                    'output_schema'=> isset( $tool['outputSchema'] ) ? $tool['outputSchema'] : null,
                    'meta'         => [
                        'show_in_rest' => false,  // Not a real REST ability
                        'readonly'     => true,
                        'destructive'  => false,
                        'idempotent'   => true,
                        'is_mcp'       => true,
                        'mcp_connection' => $slug,
                        'mcp_tool'     => $tool_name,
                    ],
                ];
            }
        }

        return $abilities;
    }

    /**
     * Execute an MCP tool by ability name: mcp/{connection_slug}/{tool_name}
     */
    public static function execute_tool( $ability_name, $arguments = [] ) {
        // Parse: mcp/connection-slug/tool-name
        $parts = explode( '/', $ability_name, 3 );
        if ( count( $parts ) < 3 || $parts[0] !== 'mcp' ) {
            return [ 'success' => false, 'error' => 'Invalid MCP ability name: ' . $ability_name ];
        }

        $connection_slug = $parts[1];
        $tool_name       = $parts[2];

        $connection = self::get_by_slug( $connection_slug );
        if ( ! $connection ) {
            return [ 'success' => false, 'error' => 'MCP connection not found: ' . $connection_slug ];
        }

        if ( empty( $connection['enabled'] ) ) {
            return [ 'success' => false, 'error' => 'MCP connection is disabled: ' . $connection_slug ];
        }

        try {
            $client = self::get_client( $connection );
            $client->initialize(); // Establish session
            $result = $client->call_tool( $tool_name, $arguments );
            $text   = SNN_MCP_Client::extract_text( $result );

            return [
                'success'  => true,
                'data'     => $text,
                'raw'      => $result,
                'tool'     => $tool_name,
                'connection' => $connection['name'],
            ];
        } catch ( \Exception $e ) {
            return [ 'success' => false, 'error' => $e->getMessage() ];
        }
    }

    /**
     * Slugify a connection name for use in ability names.
     * "Figma Design" → "figma-design"
     */
    public static function slugify( $name ) {
        $slug = sanitize_title( $name );
        // Ensure unique slugs by appending a suffix if needed
        return $slug ?: 'connection-' . substr( md5( $name ), 0, 6 );
    }
}

// ================================================================
// 4. Context Helpers — for chat overlay integration
// ================================================================

/**
 * Get MCP tools as abilities array — injected into chat JS config.
 * Called from both chat overlays' enqueue_assets().
 */
function snn_mcp_get_tools_for_chat() {
    return SNN_MCP_Manager::get_tools_as_abilities();
}

/**
 * Get MCP connections context (just metadata, no auth values).
 * Safe to pass to the browser.
 */
function snn_mcp_get_connections_context() {
    $connections = SNN_MCP_Manager::get_enabled();
    $context     = [];

    foreach ( $connections as $conn ) {
        $context[] = [
            'slug'        => SNN_MCP_Manager::slugify( $conn['name'] ),
            'name'        => $conn['name'],
            'url'         => $conn['url'],
            'description' => isset( $conn['description'] ) ? $conn['description'] : '',
            'tool_count'  => isset( $conn['tools_cache'] ) ? count( $conn['tools_cache'] ) : 0,
            'last_sync'   => isset( $conn['last_sync'] ) ? $conn['last_sync'] : '',
        ];
    }

    return $context;
}

// ================================================================
// 5. AJAX Handlers
// ================================================================

/**
 * AJAX: Test a single MCP connection.
 * Accepts connection data in POST, returns structured logs + tools.
 */
add_action( 'wp_ajax_snn_mcp_test_connection', 'snn_mcp_test_connection_handler' );
function snn_mcp_test_connection_handler() {
    check_ajax_referer( 'snn_mcp_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => 'Unauthorized.' ] );
    }

    $connection = [
        'name'        => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
        'url'         => isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '',
        'auth_type'   => isset( $_POST['auth_type'] ) ? sanitize_text_field( wp_unslash( $_POST['auth_type'] ) ) : 'bearer',
        'auth_value'  => isset( $_POST['auth_value'] ) ? wp_unslash( $_POST['auth_value'] ) : '', // Will be encrypted later
        'header_name' => isset( $_POST['header_name'] ) ? sanitize_text_field( wp_unslash( $_POST['header_name'] ) ) : 'X-API-Key',
        'timeout'     => isset( $_POST['timeout'] ) ? (int) $_POST['timeout'] : 30,
    ];

    if ( empty( $connection['name'] ) || empty( $connection['url'] ) ) {
        wp_send_json_error( [ 'message' => 'Name and URL are required.' ] );
    }

    // If auth_value looks like a masked placeholder, try to find existing encrypted value
    if ( ! empty( $_POST['existing_slug'] ) ) {
        $existing_slug = sanitize_text_field( wp_unslash( $_POST['existing_slug'] ) );
        $existing = SNN_MCP_Manager::get_by_slug( $existing_slug );
        if ( $existing && isset( $existing['auth_value'] ) ) {
            $connection['auth_value'] = $existing['auth_value']; // Use stored encrypted value
        }
    } elseif ( ! empty( $connection['auth_value'] ) && strpos( $connection['auth_value'], 'encrypted:' ) !== 0 ) {
        // New plaintext auth value — encrypt it for the test
        $connection['auth_value'] = SNN_MCP_Crypto::encrypt( $connection['auth_value'] );
    }

    $result = SNN_MCP_Manager::test_and_discover( $connection, false ); // Don't save cache on test

    if ( $result['success'] ) {
        wp_send_json_success( [
            'logs'  => $result['logs'],
            'tools' => $result['tools'],
        ] );
    } else {
        wp_send_json_error( [
            'logs'  => $result['logs'],
            'error' => $result['error'],
            'tools' => [],
        ] );
    }
}

/**
 * AJAX: Call an MCP tool (used by AI agents at runtime).
 */
add_action( 'wp_ajax_snn_mcp_call_tool', 'snn_mcp_call_tool_handler' );
function snn_mcp_call_tool_handler() {
    check_ajax_referer( 'snn_mcp_nonce', 'nonce' );

    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( [ 'message' => 'Unauthorized.' ] );
    }

    $ability_name = isset( $_POST['ability'] ) ? sanitize_text_field( wp_unslash( $_POST['ability'] ) ) : '';
    $arguments_raw = isset( $_POST['arguments'] ) ? wp_unslash( $_POST['arguments'] ) : '{}';
    $arguments     = json_decode( $arguments_raw, true );

    if ( ! is_array( $arguments ) ) {
        $arguments = [];
    }

    if ( empty( $ability_name ) ) {
        wp_send_json_error( [ 'message' => 'Ability name is required.' ] );
    }

    $result = SNN_MCP_Manager::execute_tool( $ability_name, $arguments );

    if ( $result['success'] ) {
        wp_send_json_success( $result );
    } else {
        wp_send_json_error( $result );
    }
}

/**
 * AJAX: Refresh/discover tools for a connection.
 */
add_action( 'wp_ajax_snn_mcp_refresh_tools', 'snn_mcp_refresh_tools_handler' );
function snn_mcp_refresh_tools_handler() {
    check_ajax_referer( 'snn_mcp_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => 'Unauthorized.' ] );
    }

    $slug = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';
    $connection = SNN_MCP_Manager::get_by_slug( $slug );

    if ( ! $connection ) {
        wp_send_json_error( [ 'message' => 'Connection not found.' ] );
    }

    $result = SNN_MCP_Manager::test_and_discover( $connection, true );

    if ( $result['success'] ) {
        wp_send_json_success( [
            'logs'  => $result['logs'],
            'tools' => $result['tools'],
        ] );
    } else {
        wp_send_json_error( [
            'logs'  => $result['logs'],
            'error' => $result['error'],
        ] );
    }
}

/**
 * AJAX: Start OAuth flow for an MCP connection.
 * Probes the server and returns the OAuth authorization URL.
 */
add_action( 'wp_ajax_snn_mcp_oauth_start', 'snn_mcp_oauth_start_handler' );
function snn_mcp_oauth_start_handler() {
    check_ajax_referer( 'snn_mcp_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => 'Unauthorized.' ] );
    }

    $url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';

    if ( empty( $url ) ) {
        wp_send_json_error( [ 'message' => 'Server URL is required.' ] );
    }

    try {
        $probe = SNN_MCP_Client::probe_auth( $url, 15 );

        if ( $probe['auth_type'] === 'oauth' && ! empty( $probe['oauth_url'] ) ) {
            wp_send_json_success( [
                'auth_url' => $probe['oauth_url'],
                'message'  => $probe['message'],
            ] );
        } elseif ( $probe['auth_type'] === 'oauth' ) {
            wp_send_json_error( [
                'message' => 'OAuth server detected but no authorization URL was found. You may need to obtain a token manually from the provider.',
            ] );
        } else {
            wp_send_json_error( [
                'message' => $probe['message'],
            ] );
        }
    } catch ( \Exception $e ) {
        wp_send_json_error( [ 'message' => 'Auth probe failed: ' . $e->getMessage() ] );
    }
}

// ================================================================
// 6. Admin Settings Tab — MCP Connections Repeater UI
// ================================================================

/**
 * Register MCP settings.
 */
add_action( 'admin_init', 'snn_mcp_register_settings' );
function snn_mcp_register_settings() {
    register_setting( 'snn_ai_settings_group', 'snn_mcp_connections', [
        'type'              => 'array',
        'default'           => [],
        'sanitize_callback' => 'snn_mcp_sanitize_connections',
    ] );
}

/**
 * Sanitize MCP connections on save.
 * Encrypts auth values, preserves existing encrypted values.
 */
function snn_mcp_sanitize_connections( $input ) {
    if ( ! is_array( $input ) ) {
        return [];
    }

    $existing = get_option( 'snn_mcp_connections', [] );
    if ( ! is_array( $existing ) ) {
        $existing = [];
    }

    $sanitized = [];

    foreach ( $input as $index => $conn ) {
        if ( empty( $conn['name'] ) || empty( $conn['url'] ) ) {
            continue; // Skip incomplete rows
        }

        $name = sanitize_text_field( $conn['name'] );
        $url  = esc_url_raw( $conn['url'] );
        $slug = SNN_MCP_Manager::slugify( $name );

        // Find matching existing connection to preserve data
        $existing_conn = null;
        foreach ( $existing as $ec ) {
            if ( SNN_MCP_Manager::slugify( $ec['name'] ) === $slug ) {
                $existing_conn = $ec;
                break;
            }
        }

        $sanitized[] = [
            'id'           => isset( $existing_conn['id'] ) ? $existing_conn['id'] : 'conn_' . substr( md5( uniqid( $slug, true ) ), 0, 8 ),
            'name'         => $name,
            'enabled'      => isset( $conn['enabled'] ) ? (bool) $conn['enabled'] : true,
            'url'          => $url,
            'auth_type'    => isset( $conn['auth_type'] ) ? sanitize_text_field( $conn['auth_type'] ) : 'bearer',
            'auth_value'   => snn_mcp_preserve_or_encrypt_auth( $conn, $existing_conn ),
            'header_name'  => isset( $conn['header_name'] ) ? sanitize_text_field( $conn['header_name'] ) : 'X-API-Key',
            'timeout'      => isset( $conn['timeout'] ) ? max( 5, (int) $conn['timeout'] ) : 30,
            'description'  => isset( $conn['description'] ) ? sanitize_textarea_field( $conn['description'] ) : '',
            'tools_cache'  => isset( $existing_conn['tools_cache'] ) ? $existing_conn['tools_cache'] : [],
            'last_sync'    => isset( $existing_conn['last_sync'] ) ? $existing_conn['last_sync'] : '',
        ];
    }

    return $sanitized;
}

/**
 * Preserve existing encrypted auth value if user didn't change it,
 * or encrypt a new plaintext value.
 */
function snn_mcp_preserve_or_encrypt_auth( $new_conn, $existing_conn ) {
    $new_auth = isset( $new_conn['auth_value'] ) ? trim( $new_conn['auth_value'] ) : '';

    // Empty — no auth
    if ( empty( $new_auth ) ) {
        return '';
    }

    // Already encrypted — pass through
    if ( strpos( $new_auth, 'encrypted:' ) === 0 ) {
        return $new_auth;
    }

    // Masked placeholder (••••) — preserve existing encrypted value
    if ( preg_match( '/^[•]{3,}$/', str_replace( [ '•', '●', '○', '*' ], '', $new_auth ) ) === 0
         && preg_match( '/[•●○*]/', $new_auth ) ) {
        return isset( $existing_conn['auth_value'] ) ? $existing_conn['auth_value'] : '';
    }

    // New plaintext value — encrypt it
    return SNN_MCP_Crypto::encrypt( $new_auth );
}

/**
 * Render the MCP Connections tab content.
 * Called from ai-settings.php tab content area.
 */
function snn_mcp_render_tab() {
    $connections = SNN_MCP_Manager::get_all();
    ?>
    <div class="snn-tab-content" id="snn-tab-mcp">

        <h2><?php esc_html_e( 'MCP Connections', 'snn' ); ?></h2>
        <p>
            <?php esc_html_e( 'Connect any MCP (Model Context Protocol) server by pasting its URL and credentials. Your AI agents will auto-discover and use the tools exposed by each server.', 'snn' ); ?>
        </p>
        <p class="description">
            <?php esc_html_e( 'MCP is an open standard by Anthropic — one universal connector for any AI tool. Supported servers include Figma, GitHub, Slack, Outlook, browser automation, and any custom MCP server.', 'snn' ); ?>
        </p>

        <div id="snn-mcp-connections-container">

            <?php if ( ! empty( $connections ) ) : ?>
                <?php foreach ( $connections as $index => $conn ) : ?>
                    <?php snn_mcp_render_connection_row( $index, $conn ); ?>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="snn-mcp-empty-state" id="snn-mcp-empty-state">
                    <p style="color: #666; font-style: italic;">
                        <?php esc_html_e( 'No MCP connections yet. Click "Add Connection" to get started.', 'snn' ); ?>
                    </p>
                </div>
            <?php endif; ?>

        </div>

        <p>
            <button type="button" class="button" id="snn-mcp-add-connection">
                <?php esc_html_e( '+ Add Connection', 'snn' ); ?>
            </button>
        </p>

        <?php snn_mcp_render_scripts(); ?>
    </div>
    <?php
}

/**
 * Render a single MCP connection row (repeater item).
 */
function snn_mcp_render_connection_row( $index, $conn ) {
    $name        = isset( $conn['name'] ) ? $conn['name'] : '';
    $url         = isset( $conn['url'] ) ? $conn['url'] : '';
    $auth_type   = isset( $conn['auth_type'] ) ? $conn['auth_type'] : 'bearer';
    $auth_value  = isset( $conn['auth_value'] ) ? $conn['auth_value'] : '';
    $header_name = isset( $conn['header_name'] ) ? $conn['header_name'] : 'X-API-Key';
    $timeout     = isset( $conn['timeout'] ) ? (int) $conn['timeout'] : 30;
    $description = isset( $conn['description'] ) ? $conn['description'] : '';
    $enabled     = isset( $conn['enabled'] ) ? (bool) $conn['enabled'] : true;
    $tools_cache = isset( $conn['tools_cache'] ) ? $conn['tools_cache'] : [];
    $last_sync   = isset( $conn['last_sync'] ) ? $conn['last_sync'] : '';
    $tool_count  = count( $tools_cache );
    $status_text = $tool_count > 0
        ? sprintf( __( '%d tools · Last synced: %s', 'snn' ), $tool_count, $last_sync ?: 'N/A' )
        : __( 'Not tested yet', 'snn' );

    // Mask auth value for display
    $display_auth = '';
    if ( ! empty( $auth_value ) && strpos( $auth_value, 'encrypted:' ) === 0 ) {
        // Try to decrypt for masking
        $decrypted = SNN_MCP_Crypto::decrypt( $auth_value );
        $display_auth = $decrypted ? SNN_MCP_Crypto::mask( $decrypted ) : '••••••••';
    } elseif ( ! empty( $auth_value ) ) {
        $display_auth = SNN_MCP_Crypto::mask( $auth_value );
    }
    ?>
    <div class="snn-mcp-connection-row" data-index="<?php echo (int) $index; ?>">
        <div class="snn-mcp-row-header">
            <span class="snn-mcp-row-title">
                <?php echo esc_html( $name ?: __( 'New Connection', 'snn' ) ); ?>
            </span>
            <span class="snn-mcp-row-status <?php echo $tool_count > 0 ? 'snn-mcp-status-ok' : 'snn-mcp-status-pending'; ?>">
                <?php echo esc_html( $status_text ); ?>
            </span>
            <button type="button" class="button snn-mcp-toggle-row" title="<?php esc_attr_e( 'Expand/Collapse', 'snn' ); ?>">
                <span class="dashicons dashicons-arrow-down-alt2"></span>
            </button>
            <button type="button" class="button snn-mcp-remove-row" title="<?php esc_attr_e( 'Remove Connection', 'snn' ); ?>">
                <span class="dashicons dashicons-trash"></span>
            </button>
        </div>

        <div class="snn-mcp-row-body">
            <table class="form-table" style="margin-top: 0;">
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e( 'Connection Name', 'snn' ); ?></label>
                    </th>
                    <td>
                        <input
                            type="text"
                            name="snn_mcp_connections[<?php echo (int) $index; ?>][name]"
                            value="<?php echo esc_attr( $name ); ?>"
                            class="regular-text snn-mcp-name-input"
                            placeholder="<?php esc_attr_e( 'e.g., Figma Design, GitHub, Slack', 'snn' ); ?>"
                        />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e( 'MCP Server URL', 'snn' ); ?></label>
                    </th>
                    <td>
                        <input
                            type="url"
                            name="snn_mcp_connections[<?php echo (int) $index; ?>][url]"
                            value="<?php echo esc_attr( $url ); ?>"
                            class="regular-text snn-mcp-url-input"
                            placeholder="<?php esc_attr_e( 'https://your-mcp-server.example.com/mcp', 'snn' ); ?>"
                        />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e( 'Enabled', 'snn' ); ?></label>
                    </th>
                    <td>
                        <input
                            type="hidden"
                            name="snn_mcp_connections[<?php echo (int) $index; ?>][enabled]"
                            value="0"
                        />
                        <input
                            type="checkbox"
                            name="snn_mcp_connections[<?php echo (int) $index; ?>][enabled]"
                            value="1"
                            <?php checked( $enabled ); ?>
                        />
                        <span class="description"><?php esc_html_e( 'Make tools available to AI agents', 'snn' ); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e( 'Authentication', 'snn' ); ?></label>
                    </th>
                    <td>
                        <select
                            name="snn_mcp_connections[<?php echo (int) $index; ?>][auth_type]"
                            class="snn-mcp-auth-type"
                        >
                            <option value="none"    <?php selected( $auth_type, 'none' ); ?>><?php esc_html_e( 'No Auth (public server)', 'snn' ); ?></option>
                            <option value="bearer"  <?php selected( $auth_type, 'bearer' ); ?>><?php esc_html_e( 'Bearer Token', 'snn' ); ?></option>
                            <option value="api_key" <?php selected( $auth_type, 'api_key' ); ?>><?php esc_html_e( 'API Key (custom header)', 'snn' ); ?></option>
                            <option value="oauth"   <?php selected( $auth_type, 'oauth' ); ?>><?php esc_html_e( 'OAuth 2.0', 'snn' ); ?></option>
                        </select>
                        <div class="snn-mcp-auth-fields" style="margin-top: 8px; display: <?php echo $auth_type === 'none' ? 'none' : 'block'; ?>;">
                            <div class="snn-mcp-api-key-header-field" style="display: <?php echo $auth_type === 'api_key' ? 'block' : 'none'; ?>; margin-bottom: 6px;">
                                <label style="display: block; margin-bottom: 2px; font-weight: 600;"><?php esc_html_e( 'Header Name', 'snn' ); ?></label>
                                <input
                                    type="text"
                                    name="snn_mcp_connections[<?php echo (int) $index; ?>][header_name]"
                                    value="<?php echo esc_attr( $header_name ); ?>"
                                    class="regular-text"
                                    placeholder="X-API-Key"
                                    style="max-width: 200px;"
                                />
                            </div>
                            <div class="snn-mcp-oauth-ui" style="display: <?php echo $auth_type === 'oauth' ? 'block' : 'none'; ?>;">
                                <p class="description" style="margin: 0 0 8px 0;">
                                    <?php esc_html_e( 'OAuth 2.0 authentication. Click below to start the authorization flow, or paste an access token manually.', 'snn' ); ?>
                                </p>
                                <button type="button" class="button snn-mcp-oauth-start-btn" data-index="<?php echo (int) $index; ?>">
                                    <?php esc_html_e( '🔑 Start OAuth Flow', 'snn' ); ?>
                                </button>
                                <span class="spinner" style="float: none; margin: 0 6px; display: none;"></span>
                                <span class="snn-mcp-oauth-status" style="font-size: 12px; margin-left: 6px;"></span>
                                <p style="margin: 8px 0 4px 0;">
                                    <label style="font-weight: 600;"><?php esc_html_e( 'Or paste access token manually:', 'snn' ); ?></label>
                                </p>
                            </div>
                            <label class="snn-mcp-token-label" style="display: <?php echo $auth_type === 'oauth' ? 'none' : 'block'; ?>; margin-bottom: 2px; font-weight: 600;">
                                <?php echo $auth_type === 'api_key' ? esc_html__( 'API Key', 'snn' ) : esc_html__( 'Token', 'snn' ); ?>
                            </label>
                            <input
                                type="password"
                                name="snn_mcp_connections[<?php echo (int) $index; ?>][auth_value]"
                                value="<?php echo esc_attr( $display_auth ); ?>"
                                class="regular-text snn-mcp-auth-input"
                                placeholder="<?php esc_attr_e( 'Paste your token or API key', 'snn' ); ?>"
                                autocomplete="off"
                                style="max-width: 400px;"
                            />
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e( 'Timeout', 'snn' ); ?></label>
                    </th>
                    <td>
                        <input
                            type="number"
                            name="snn_mcp_connections[<?php echo (int) $index; ?>][timeout]"
                            value="<?php echo (int) $timeout; ?>"
                            class="small-text"
                            min="5"
                            max="120"
                        />
                        <span class="description"><?php esc_html_e( 'seconds', 'snn' ); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e( 'Description', 'snn' ); ?></label>
                    </th>
                    <td>
                        <textarea
                            name="snn_mcp_connections[<?php echo (int) $index; ?>][description]"
                            rows="2"
                            class="regular-text"
                            placeholder="<?php esc_attr_e( 'Optional notes about this connection...', 'snn' ); ?>"
                            style="max-width: 400px;"
                        ><?php echo esc_textarea( $description ); ?></textarea>
                    </td>
                </tr>
            </table>

            <!-- Test & Tools Area -->
            <div class="snn-mcp-test-area">
                <button type="button" class="button button-primary snn-mcp-test-btn" data-index="<?php echo (int) $index; ?>">
                    <?php esc_html_e( '🔍 Test Connection', 'snn' ); ?>
                </button>
                <span class="spinner" style="float: none; margin: 0 10px; display: none;"></span>

                <div class="snn-mcp-log-wrap" style="display: none; margin-top: 15px;">
                    <div class="snn-mcp-log-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <strong><?php esc_html_e( 'Connection Test Log', 'snn' ); ?></strong>
                        <button type="button" class="button snn-mcp-copy-log" style="font-size: 11px;"><?php esc_html_e( 'Copy Log', 'snn' ); ?></button>
                    </div>
                    <div class="snn-mcp-log-box" style="background: #1e1e1e; color: #d4d4d4; font-family: 'Courier New', monospace; font-size: 12px; padding: 14px; border-radius: 6px; max-height: 400px; overflow-y: auto; line-height: 1.6; white-space: pre-wrap; word-break: break-word;">
                        <?php esc_html_e( 'Click "Test Connection" to see results here...', 'snn' ); ?>
                    </div>
                </div>

                <?php if ( ! empty( $tools_cache ) ) : ?>
                <div class="snn-mcp-tools-cache" style="margin-top: 12px;">
                    <strong><?php echo esc_html( count( $tools_cache ) ); ?> <?php esc_html_e( 'cached tools:', 'snn' ); ?></strong>
                    <ul style="margin: 6px 0 0 16px; color: #555; font-size: 13px;">
                        <?php foreach ( $tools_cache as $tool ) : ?>
                            <li>
                                <code><?php echo esc_html( isset( $tool['name'] ) ? $tool['name'] : '?' ); ?></code>
                                — <?php echo esc_html( isset( $tool['description'] ) ? $tool['description'] : __( 'No description', 'snn' ) ); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Render the JavaScript for the MCP tab.
 */
function snn_mcp_render_scripts() {
    ?>
    <style>
    /* MCP Connection Row Styles */
    .snn-mcp-connection-row {
        background: #fff;
        border: 1px solid #dcdcde;
        border-radius: 6px;
        margin-bottom: 12px;
        overflow: hidden;
    }
    .snn-mcp-row-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        background: #f6f7f7;
        border-bottom: 1px solid #dcdcde;
        cursor: pointer;
        user-select: none;
    }
    .snn-mcp-row-header:hover {
        background: #f0f0f1;
    }
    .snn-mcp-row-title {
        font-weight: 600;
        font-size: 14px;
        flex: 1;
    }
    .snn-mcp-row-status {
        font-size: 12px;
        padding: 2px 10px;
        border-radius: 10px;
        font-weight: 500;
    }
    .snn-mcp-status-ok {
        background: #e8f5e9;
        color: #2e7d32;
    }
    .snn-mcp-status-pending {
        background: #fff3e0;
        color: #e65100;
    }
    .snn-mcp-row-body {
        padding: 14px;
        display: none;
    }
    .snn-mcp-row-body.open {
        display: block;
    }
    .snn-mcp-connection-row .form-table {
        margin-top: 0;
    }
    .snn-mcp-connection-row .form-table th {
        width: 140px;
        padding: 8px 10px 8px 0;
    }
    .snn-mcp-connection-row .form-table td {
        padding: 8px 10px;
    }
    .snn-mcp-test-area {
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #eee;
    }
    .snn-mcp-log-box .log-success { color: #4caf50; }
    .snn-mcp-log-box .log-error   { color: #f44336; }
    .snn-mcp-log-box .log-warning { color: #ff9800; }
    .snn-mcp-log-box .log-info    { color: #64b5f6; }
    .snn-mcp-empty-state {
        padding: 30px;
        text-align: center;
        background: #f9f9f9;
        border: 2px dashed #dcdcde;
        border-radius: 6px;
    }
    /* Remove button */
    .snn-mcp-remove-row {
        color: #b32d2e !important;
        border-color: #b32d2e !important;
    }
    .snn-mcp-remove-row:hover {
        background: #b32d2e !important;
        color: #fff !important;
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('snn-mcp-connections-container');
        const addBtn    = document.getElementById('snn-mcp-add-connection');

        // ── Add Connection ──────────────────────────────────────
        if (addBtn) {
            addBtn.addEventListener('click', function() {
                const index = container.querySelectorAll('.snn-mcp-connection-row').length;
                const emptyState = document.getElementById('snn-mcp-empty-state');
                if (emptyState) emptyState.remove();

                const template = getNewRowTemplate(index);
                const div = document.createElement('div');
                div.innerHTML = template;
                container.appendChild(div.firstElementChild);
                bindRowEvents(container.lastElementChild);
            });
        }

        // ── Bind events to existing rows ────────────────────────
        document.querySelectorAll('.snn-mcp-connection-row').forEach(bindRowEvents);

        function bindRowEvents(row) {
            // Toggle expand/collapse
            const header = row.querySelector('.snn-mcp-row-header');
            const body   = row.querySelector('.snn-mcp-row-body');
            if (header && body) {
                header.addEventListener('click', function(e) {
                    if (e.target.closest('button')) return; // Don't toggle on button clicks
                    body.classList.toggle('open');
                    const icon = header.querySelector('.dashicons');
                    if (icon) {
                        icon.classList.toggle('dashicons-arrow-down-alt2');
                        icon.classList.toggle('dashicons-arrow-up-alt2');
                    }
                });
            }

            // Auth type change — show/hide fields
            const authType = row.querySelector('.snn-mcp-auth-type');
            if (authType) {
                authType.addEventListener('change', function() {
                    const fields = row.querySelector('.snn-mcp-auth-fields');
                    const headerField = row.querySelector('.snn-mcp-api-key-header-field');
                    const oauthUI = row.querySelector('.snn-mcp-oauth-ui');
                    const tokenLabel = row.querySelector('.snn-mcp-token-label');
                    if (fields) {
                        fields.style.display = this.value === 'none' ? 'none' : 'block';
                    }
                    if (headerField) {
                        headerField.style.display = this.value === 'api_key' ? 'block' : 'none';
                    }
                    if (oauthUI) {
                        oauthUI.style.display = this.value === 'oauth' ? 'block' : 'none';
                    }
                    if (tokenLabel) {
                        tokenLabel.style.display = this.value === 'oauth' ? 'none' : 'block';
                    }
                });
            }

            // OAuth Start Flow button
            const oauthBtn = row.querySelector('.snn-mcp-oauth-start-btn');
            if (oauthBtn) {
                oauthBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    startOAuthFlow(row);
                });
            }

            // Remove button
            const removeBtn = row.querySelector('.snn-mcp-remove-row');
            if (removeBtn) {
                removeBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (confirm('<?php echo esc_js( __( 'Remove this MCP connection? This cannot be undone.', 'snn' ) ); ?>')) {
                        row.style.transition = 'opacity 0.2s';
                        row.style.opacity = '0';
                        setTimeout(() => {
                            row.remove();
                            if (container.querySelectorAll('.snn-mcp-connection-row').length === 0) {
                                container.innerHTML = '<div class="snn-mcp-empty-state" id="snn-mcp-empty-state"><p style="color:#666;font-style:italic;"><?php echo esc_js( __( 'No MCP connections yet. Click "Add Connection" to get started.', 'snn' ) ); ?></p></div>';
                            }
                            reindexRows();
                        }, 200);
                    }
                });
            }

            // Test button
            const testBtn = row.querySelector('.snn-mcp-test-btn');
            if (testBtn) {
                testBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    testConnection(row);
                });
            }

            // Copy log button
            const copyBtn = row.querySelector('.snn-mcp-copy-log');
            if (copyBtn) {
                copyBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const logBox = row.querySelector('.snn-mcp-log-box');
                    if (logBox) {
                        const text = logBox.innerText;
                        navigator.clipboard.writeText(text).then(() => {
                            copyBtn.textContent = '✓ Copied!';
                            setTimeout(() => { copyBtn.textContent = 'Copy Log'; }, 2000);
                        });
                    }
                });
            }
        }

        // ── Test Connection ─────────────────────────────────────
        async function testConnection(row) {
            const index      = row.getAttribute('data-index');
            const testBtn    = row.querySelector('.snn-mcp-test-btn');
            const spinner    = row.querySelector('.spinner');
            const logWrap    = row.querySelector('.snn-mcp-log-wrap');
            const logBox     = row.querySelector('.snn-mcp-log-box');
            const statusEl   = row.querySelector('.snn-mcp-row-status');
            const toolsCache = row.querySelector('.snn-mcp-tools-cache');

            const nameInput  = row.querySelector('.snn-mcp-name-input');
            const urlInput   = row.querySelector('.snn-mcp-url-input');
            const authType   = row.querySelector('.snn-mcp-auth-type');
            const authInput  = row.querySelector('.snn-mcp-auth-input');
            const headerInput = row.querySelector('.snn-mcp-api-key-header-field input');

            const name = nameInput ? nameInput.value.trim() : '';
            const url  = urlInput  ? urlInput.value.trim()  : '';

            if (!name || !url) {
                alert('<?php echo esc_js( __( 'Please enter both a connection name and URL before testing.', 'snn' ) ); ?>');
                return;
            }

            // Show log area
            testBtn.disabled = true;
            spinner.style.display = 'inline-block';
            logWrap.style.display = 'block';
            logBox.innerHTML = '<span class="log-info">[INFO]</span> Starting connection test...\n';
            logBox.scrollTop = logBox.scrollHeight;

            function appendLog(type, message) {
                const cls = 'log-' + type;
                const prefix = type === 'success' ? '[OK]' : type === 'error' ? '[ERROR]' : type === 'warning' ? '[WARN]' : '[INFO]';
                logBox.innerHTML += '<span class="' + cls + '">' + prefix + '</span> ' + escHtml(message) + '\n';
                logBox.scrollTop = logBox.scrollHeight;
            }

            const formData = new FormData();
            formData.append('action', 'snn_mcp_test_connection');
            formData.append('nonce', '<?php echo esc_js( wp_create_nonce( 'snn_mcp_nonce' ) ); ?>');
            formData.append('name', name);
            formData.append('url', url);
            formData.append('auth_type', authType ? authType.value : 'bearer');
            formData.append('auth_value', authInput ? authInput.value : '');
            formData.append('header_name', headerInput ? headerInput.value : 'X-API-Key');
            formData.append('timeout', row.querySelector('[name*="[timeout]"]')?.value || '30');

            // Pass existing slug for preserving auth
            const existingSlug = slugify(name);
            formData.append('existing_slug', existingSlug);

            try {
                const resp = await fetch(ajaxurl, { method: 'POST', body: formData });
                const data = await resp.json();

                if (data.success && data.data && data.data.logs) {
                    data.data.logs.forEach(l => appendLog(l.type, l.message));

                    // Update status
                    if (statusEl) {
                        const toolCount = data.data.tools ? data.data.tools.length : 0;
                        statusEl.textContent = toolCount + ' tools discovered';
                        statusEl.className = 'snn-mcp-row-status snn-mcp-status-ok';
                    }

                    // Update tools cache display
                    if (toolsCache && data.data.tools && data.data.tools.length > 0) {
                        let toolsHtml = '<strong>' + data.data.tools.length + ' discovered tools:</strong><ul style="margin:6px 0 0 16px;color:#555;font-size:13px;">';
                        data.data.tools.forEach(t => {
                            toolsHtml += '<li><code>' + escHtml(t.name || '?') + '</code> — ' + escHtml(t.description || 'No description') + '</li>';
                        });
                        toolsHtml += '</ul>';
                        toolsCache.innerHTML = toolsHtml;
                        toolsCache.style.display = 'block';
                    }
                } else {
                    const logs = (data.data && data.data.logs) ? data.data.logs : [];
                    logs.forEach(l => appendLog(l.type, l.message));
                    appendLog('error', data.data?.error || data.data?.message || 'Test failed');

                    if (statusEl) {
                        statusEl.textContent = 'Connection failed';
                        statusEl.className = 'snn-mcp-row-status snn-mcp-status-pending';
                    }
                }
            } catch (err) {
                appendLog('error', 'Network error: ' + err.message);
            } finally {
                testBtn.disabled = false;
                spinner.style.display = 'none';
            }
        }

        // ── Helper: Generate new row template ──────────────────
        function getNewRowTemplate(index) {
            // Build a minimal new row (PHP renders the full markup for existing rows)
            return `
            <div class="snn-mcp-connection-row" data-index="${index}">
                <div class="snn-mcp-row-header">
                    <span class="snn-mcp-row-title">New Connection</span>
                    <span class="snn-mcp-row-status snn-mcp-status-pending">Not tested yet</span>
                    <button type="button" class="button snn-mcp-toggle-row" title="Expand/Collapse">
                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                    </button>
                    <button type="button" class="button snn-mcp-remove-row" title="Remove Connection">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
                <div class="snn-mcp-row-body">
                    <table class="form-table" style="margin-top:0;">
                        <tr>
                            <th scope="row"><label>Connection Name</label></th>
                            <td><input type="text" name="snn_mcp_connections[${index}][name]" value="" class="regular-text snn-mcp-name-input" placeholder="e.g., Figma Design, GitHub, Slack" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label>MCP Server URL</label></th>
                            <td><input type="url" name="snn_mcp_connections[${index}][url]" value="" class="regular-text snn-mcp-url-input" placeholder="https://your-mcp-server.example.com/mcp" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label>Enabled</label></th>
                            <td>
                                <input type="hidden" name="snn_mcp_connections[${index}][enabled]" value="0" />
                                <input type="checkbox" name="snn_mcp_connections[${index}][enabled]" value="1" checked />
                                <span class="description">Make tools available to AI agents</span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label>Authentication</label></th>
                            <td>
                                <select name="snn_mcp_connections[${index}][auth_type]" class="snn-mcp-auth-type">
                                    <option value="none">No Auth (public server)</option>
                                    <option value="bearer" selected>Bearer Token</option>
                                    <option value="api_key">API Key (custom header)</option>
                                    <option value="oauth">OAuth 2.0</option>
                                </select>
                                <div class="snn-mcp-auth-fields" style="margin-top:8px;">
                                    <div class="snn-mcp-api-key-header-field" style="display:none;margin-bottom:6px;">
                                        <label style="display:block;margin-bottom:2px;font-weight:600;">Header Name</label>
                                        <input type="text" name="snn_mcp_connections[${index}][header_name]" value="X-API-Key" class="regular-text" placeholder="X-API-Key" style="max-width:200px;" />
                                    </div>
                                    <div class="snn-mcp-oauth-ui" style="display:none;">
                                        <p class="description" style="margin:0 0 8px 0;">OAuth 2.0 authentication. Click below to start the authorization flow, or paste an access token manually.</p>
                                        <button type="button" class="button snn-mcp-oauth-start-btn" data-index="${index}">🔑 Start OAuth Flow</button>
                                        <span class="spinner" style="float:none;margin:0 6px;display:none;"></span>
                                        <span class="snn-mcp-oauth-status" style="font-size:12px;margin-left:6px;"></span>
                                        <p style="margin:8px 0 4px 0;"><label style="font-weight:600;">Or paste access token manually:</label></p>
                                    </div>
                                    <label class="snn-mcp-token-label" style="display:block;margin-bottom:2px;font-weight:600;">Token</label>
                                    <input type="password" name="snn_mcp_connections[${index}][auth_value]" value="" class="regular-text snn-mcp-auth-input" placeholder="Paste your token or API key" autocomplete="off" style="max-width:400px;" />
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label>Timeout</label></th>
                            <td>
                                <input type="number" name="snn_mcp_connections[${index}][timeout]" value="30" class="small-text" min="5" max="120" />
                                <span class="description">seconds</span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label>Description</label></th>
                            <td>
                                <textarea name="snn_mcp_connections[${index}][description]" rows="2" class="regular-text" placeholder="Optional notes about this connection..." style="max-width:400px;"></textarea>
                            </td>
                        </tr>
                    </table>
                    <div class="snn-mcp-test-area">
                        <button type="button" class="button button-primary snn-mcp-test-btn" data-index="${index}">🔍 Test Connection</button>
                        <span class="spinner" style="float:none;margin:0 10px;display:none;"></span>
                        <div class="snn-mcp-log-wrap" style="display:none;margin-top:15px;">
                            <div class="snn-mcp-log-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                                <strong>Connection Test Log</strong>
                                <button type="button" class="button snn-mcp-copy-log" style="font-size:11px;">Copy Log</button>
                            </div>
                            <div class="snn-mcp-log-box" style="background:#1e1e1e;color:#d4d4d4;font-family:'Courier New',monospace;font-size:12px;padding:14px;border-radius:6px;max-height:400px;overflow-y:auto;line-height:1.6;white-space:pre-wrap;word-break:break-word;">
                                Click "Test Connection" to see results here...
                            </div>
                        </div>
                        <div class="snn-mcp-tools-cache" style="margin-top:12px;display:none;"></div>
                    </div>
                </div>
            </div>`;
        }

        // ── Helper: Reindex rows after removal ─────────────────
        function reindexRows() {
            container.querySelectorAll('.snn-mcp-connection-row').forEach((row, i) => {
                row.setAttribute('data-index', i);
                row.querySelectorAll('[name]').forEach(el => {
                    el.name = el.name.replace(/snn_mcp_connections\[\d+\]/, 'snn_mcp_connections[' + i + ']');
                });
                const testBtn = row.querySelector('.snn-mcp-test-btn');
                if (testBtn) testBtn.setAttribute('data-index', i);
            });
        }

        // ── Helpers ────────────────────────────────────────────
        function escHtml(str) {
            const div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        }

        function slugify(text) {
            return text.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'connection';
        }

        // ── OAuth Flow ──────────────────────────────────────────
        async function startOAuthFlow(row) {
            const urlInput  = row.querySelector('.snn-mcp-url-input');
            const statusEl  = row.querySelector('.snn-mcp-oauth-status');
            const oauthBtn  = row.querySelector('.snn-mcp-oauth-start-btn');
            const spinner   = row.querySelector('.snn-mcp-oauth-ui .spinner');
            const logWrap   = row.querySelector('.snn-mcp-log-wrap');
            const logBox    = row.querySelector('.snn-mcp-log-box');

            const url = urlInput ? urlInput.value.trim() : '';
            if (!url) {
                alert('Please enter the MCP Server URL first.');
                return;
            }

            oauthBtn.disabled = true;
            spinner.style.display = 'inline-block';
            if (statusEl) statusEl.textContent = 'Probing server...';

            // Show log area
            if (logWrap) logWrap.style.display = 'block';
            if (logBox) {
                logBox.innerHTML = '<span class="log-info">[INFO]</span> Probing server for OAuth configuration...\n';
            }

            try {
                const formData = new FormData();
                formData.append('action', 'snn_mcp_oauth_start');
                formData.append('nonce', '<?php echo esc_js( wp_create_nonce( 'snn_mcp_nonce' ) ); ?>');
                formData.append('url', url);

                const resp = await fetch(ajaxurl, { method: 'POST', body: formData });
                const data = await resp.json();

                if (data.success && data.data && data.data.auth_url) {
                    if (logBox) {
                        logBox.innerHTML += '<span class="log-success">[OK]</span> OAuth authorization URL obtained.\n';
                        logBox.innerHTML += '<span class="log-info">[INFO]</span> Opening authorization page in a new tab...\n';
                        logBox.innerHTML += '<span class="log-info">[INFO]</span> URL: ' + escHtml(data.data.auth_url) + '\n';
                        logBox.innerHTML += '<span class="log-info">[INFO]</span> After authorizing, paste the access token you receive into the Token field above.\n';
                    }
                    if (statusEl) statusEl.textContent = 'Opening authorization page...';
                    // Open the auth URL in a new tab
                    window.open(data.data.auth_url, '_blank', 'width=800,height=700');
                    if (statusEl) {
                        setTimeout(() => {
                            statusEl.textContent = '✓ Authorization page opened. Paste the token above when ready.';
                        }, 1500);
                    }
                } else {
                    const msg = data.data?.message || 'Could not determine OAuth URL.';
                    if (logBox) {
                        logBox.innerHTML += '<span class="log-error">[ERROR]</span> ' + escHtml(msg) + '\n';
                        logBox.innerHTML += '<span class="log-info">[INFO]</span> You may need to manually obtain an OAuth token from the provider and paste it in the Token field.\n';
                    }
                    if (statusEl) statusEl.textContent = '⚠ ' + msg;
                }
            } catch (err) {
                if (logBox) {
                    logBox.innerHTML += '<span class="log-error">[ERROR]</span> ' + escHtml(err.message) + '\n';
                }
                if (statusEl) statusEl.textContent = 'Error: ' + err.message;
            } finally {
                oauthBtn.disabled = false;
                spinner.style.display = 'none';
            }
        }
    });
    </script>
    <?php
}
