<?php
/**
 * MCP debug log.
 *
 * Off by default (SNN Settings > MCP > Debug). When on, every request to an
 * MCP server route writes JSON lines to a private log file:
 *
 *  - request   JSON-RPC method, tool name, body size, client, PHP limits
 *  - ability.* start / end / denied / invalid input, with timings, memory and
 *              a summary of input and result (full WP_Error code, message, data)
 *  - stage     steps inside a tool (zip unpack, asset upload, compile, save …)
 *              logged by snn_mcp_debug() calls in the Bricks code
 *  - response  HTTP status, duration, peak memory, JSON-RPC error
 *  - php.fatal fatal errors (memory exhausted, max execution time) caught at shutdown
 *
 * Secrets are never written: keys that look like credentials or base64 payloads
 * are replaced by their length and a short hash. Long strings are cut unless
 * "Log full payloads" is on, which keeps HTML/CSS up to 200 KB per value.
 *
 * A request that never shows up here was refused before WordPress ran (web
 * server, CDN or firewall body-size limits): check the host's access log.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const SNN_MCP_DEBUG_MAX_FILE = 5242880; // rotate at 5 MB, one backup kept

function snn_mcp_debug_enabled() {
    return ! empty( snn_mcp_get_settings()['debug'] );
}

/** Correlates every line written during one PHP request. */
function snn_mcp_debug_request_id() {
    static $id = null;
    if ( $id === null ) {
        $id = substr( md5( uniqid( '', true ) . wp_rand() ), 0, 10 );
    }
    return $id;
}

function snn_mcp_debug_dir() {
    $secret = get_option( 'snn_mcp_debug_secret' );
    if ( ! $secret ) {
        $secret = wp_generate_password( 16, false );
        update_option( 'snn_mcp_debug_secret', $secret, false );
    }
    $dir = trailingslashit( wp_upload_dir( null, false )['basedir'] ) . 'snn-mcp-debug-' . $secret;
    if ( ! is_dir( $dir ) && wp_mkdir_p( $dir ) ) {
        // Random folder name for nginx; deny rules for Apache / LiteSpeed.
        file_put_contents( $dir . '/.htaccess', "Require all denied\nDeny from all\n" );
        file_put_contents( $dir . '/index.php', "<?php\n// Silence is golden.\n" );
    }
    return $dir;
}

function snn_mcp_debug_file() {
    return snn_mcp_debug_dir() . '/mcp-debug.log';
}

/**
 * Write one log line. Safe to call anywhere: it does nothing unless debug is
 * on and the current request is an MCP request.
 */
function snn_mcp_debug( $event, array $data = array(), $level = 'info' ) {
    if ( ! function_exists( 'snn_mcp_get_settings' ) || ! snn_mcp_debug_enabled() || ! snn_mcp_is_mcp_request() ) {
        return;
    }
    $line = array_merge( array(
        't'     => gmdate( 'Y-m-d\TH:i:s' ) . sprintf( '.%03dZ', (int) ( fmod( microtime( true ), 1 ) * 1000 ) ),
        'req'   => snn_mcp_debug_request_id(),
        'user'  => get_current_user_id(),
        'level' => $level,
        'event' => (string) $event,
    ), snn_mcp_debug_summarize( $data ) );

    $file = snn_mcp_debug_file();
    if ( file_exists( $file ) && filesize( $file ) > SNN_MCP_DEBUG_MAX_FILE ) {
        @rename( $file, preg_replace( '/\.log$/', '.1.log', $file ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
    }
    @file_put_contents( $file, wp_json_encode( $line, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR ) . "\n", FILE_APPEND | LOCK_EX ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
}

/** Redact secrets and shorten big values so a log line stays readable. */
function snn_mcp_debug_summarize( $value, $key = '', $depth = 0 ) {
    $full = ! empty( snn_mcp_get_settings()['debug_payloads'] );

    if ( is_wp_error( $value ) ) {
        return array(
            'error'   => $value->get_error_code(),
            'message' => $value->get_error_message(),
            'data'    => snn_mcp_debug_summarize( $value->get_error_data(), 'data', $depth + 1 ),
            'more'    => count( $value->get_error_codes() ) > 1 ? $value->get_error_codes() : null,
        );
    }
    if ( is_object( $value ) ) {
        $value = json_decode( (string) wp_json_encode( $value ), true );
    }
    if ( is_string( $value ) ) {
        if ( $key !== '' && preg_match( '/base64|password|passwd|secret|token|authorization|cookie|nonce|api[_-]?key/i', $key ) ) {
            return '[redacted ' . strlen( $value ) . ' chars sha256:' . substr( hash( 'sha256', $value ), 0, 12 ) . ']';
        }
        $limit = $full ? 204800 : 300;
        return strlen( $value ) > $limit ? substr( $value, 0, $limit ) . ' … [' . strlen( $value ) . ' chars]' : $value;
    }
    if ( ! is_array( $value ) ) {
        return $value;
    }
    if ( $depth > 6 ) {
        return '[' . count( $value ) . ' items]';
    }
    $out = array();
    $n   = 0;
    foreach ( $value as $k => $v ) {
        if ( ++$n > 60 ) {
            $out['…'] = ( count( $value ) - 60 ) . ' more';
            break;
        }
        $out[ $k ] = snn_mcp_debug_summarize( $v, is_string( $k ) ? $k : $key, $depth + 1 );
    }
    return $out;
}

/** PHP / host limits that decide whether a big tool call can succeed. */
function snn_mcp_debug_environment() {
    return array(
        'php'                 => PHP_VERSION,
        'wp'                  => get_bloginfo( 'version' ),
        'bricks'              => defined( 'BRICKS_VERSION' ) ? BRICKS_VERSION : null,
        'post_max_size'       => ini_get( 'post_max_size' ),
        'upload_max_filesize' => ini_get( 'upload_max_filesize' ),
        'memory_limit'        => ini_get( 'memory_limit' ),
        'max_execution_time'  => ini_get( 'max_execution_time' ),
        'zip'                 => class_exists( 'ZipArchive' ),
        'html5Parser'         => class_exists( '\Dom\HTMLDocument' ),
        'server'              => isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '',
    );
}

// ── hooks ────────────────────────────────────────────────────────────

add_filter( 'rest_pre_dispatch', 'snn_mcp_debug_on_request', 1, 3 );
function snn_mcp_debug_on_request( $result, $server, $request ) {
    if ( ! snn_mcp_debug_enabled() || ! snn_mcp_is_mcp_request() ) {
        return $result;
    }
    $GLOBALS['snn_mcp_debug_started'] = microtime( true );
    $body = $request instanceof WP_REST_Request ? (string) $request->get_body() : '';
    $rpc  = json_decode( $body, true );
    $rpc  = is_array( $rpc ) ? $rpc : array();
    snn_mcp_debug( 'request', array(
        'route'     => $request instanceof WP_REST_Request ? $request->get_route() : '',
        'http'      => $request instanceof WP_REST_Request ? $request->get_method() : '',
        'rpc'       => $rpc['method'] ?? null,
        'rpcId'     => $rpc['id'] ?? null,
        'tool'      => $rpc['params']['name'] ?? null,
        'argKeys'   => isset( $rpc['params']['arguments'] ) && is_array( $rpc['params']['arguments'] ) ? array_keys( $rpc['params']['arguments'] ) : null,
        'bodyBytes' => strlen( $body ),
        'client'    => $request instanceof WP_REST_Request ? (string) $request->get_header( 'user_agent' ) : '',
        'session'   => $request instanceof WP_REST_Request ? (string) $request->get_header( 'mcp_session_id' ) : '',
        'env'       => snn_mcp_debug_environment(),
    ) );
    register_shutdown_function( 'snn_mcp_debug_on_shutdown' );
    return $result;
}

// After the route callback, on every dispatch path (rest_post_dispatch only runs for served HTTP requests).
add_filter( 'rest_request_after_callbacks', 'snn_mcp_debug_on_response', 999, 3 );
function snn_mcp_debug_on_response( $response, $handler, $request ) {
    if ( ! snn_mcp_debug_enabled() || ! snn_mcp_is_mcp_request() ) {
        return $response;
    }
    if ( is_wp_error( $response ) ) {
        snn_mcp_debug( 'response', array( 'error' => $response ), 'error' );
        return $response;
    }
    $data  = $response instanceof WP_REST_Response ? json_decode( (string) wp_json_encode( $response->get_data() ), true ) : null;
    $error = null;
    if ( is_array( $data ) ) {
        if ( isset( $data['error'] ) ) {
            $error = $data['error'];
        } elseif ( ! empty( $data['result']['isError'] ) ) {
            $error = $data['result']['content'] ?? true;
        } elseif ( isset( $data['code'], $data['message'] ) ) {
            $error = array( 'code' => $data['code'], 'message' => $data['message'] );
        }
    }
    $started = $GLOBALS['snn_mcp_debug_started'] ?? microtime( true );
    snn_mcp_debug( 'response', array(
        'status'    => $response instanceof WP_HTTP_Response ? $response->get_status() : null,
        'ms'        => (int) round( ( microtime( true ) - $started ) * 1000 ),
        'peakMb'    => round( memory_get_peak_usage( true ) / 1048576, 1 ),
        'bodyBytes' => strlen( (string) wp_json_encode( $data ) ),
        'error'     => $error,
    ), $error ? 'error' : 'info' );
    return $response;
}

add_action( 'wp_before_execute_ability', 'snn_mcp_debug_before_ability', 1, 2 );
function snn_mcp_debug_before_ability( $name, $input ) {
    if ( ! snn_mcp_debug_enabled() ) {
        return;
    }
    $GLOBALS['snn_mcp_debug_ability_started'][ $name ] = microtime( true );
    snn_mcp_debug( 'ability.start', array( 'ability' => $name, 'input' => $input ) );
}

add_filter( 'wp_ability_execute_result', 'snn_mcp_debug_after_ability', 5, 3 );
function snn_mcp_debug_after_ability( $result, $name, $input ) {
    if ( ! snn_mcp_debug_enabled() ) {
        return $result;
    }
    $started = $GLOBALS['snn_mcp_debug_ability_started'][ $name ] ?? microtime( true );
    snn_mcp_debug( 'ability.end', array(
        'ability' => $name,
        'ok'      => ! is_wp_error( $result ),
        'ms'      => (int) round( ( microtime( true ) - $started ) * 1000 ),
        'peakMb'  => round( memory_get_peak_usage( true ) / 1048576, 1 ),
        'result'  => $result,
    ), is_wp_error( $result ) ? 'error' : 'info' );
    return $result;
}

add_filter( 'wp_ability_permission_result', 'snn_mcp_debug_permission', 999, 3 );
function snn_mcp_debug_permission( $permission, $name, $input ) {
    if ( $permission !== true && snn_mcp_debug_enabled() ) {
        snn_mcp_debug( 'ability.denied', array( 'ability' => $name, 'reason' => is_wp_error( $permission ) ? $permission : 'permission_callback returned false' ), 'warn' );
    }
    return $permission;
}

add_filter( 'wp_ability_validate_input', 'snn_mcp_debug_invalid_input', 999, 3 );
function snn_mcp_debug_invalid_input( $validity, $input, $name ) {
    if ( is_wp_error( $validity ) && snn_mcp_debug_enabled() ) {
        snn_mcp_debug( 'ability.invalid_input', array( 'ability' => $name, 'reason' => $validity, 'input' => $input ), 'warn' );
    }
    return $validity;
}

function snn_mcp_debug_on_shutdown() {
    $err = error_get_last();
    if ( $err && in_array( $err['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR ), true ) ) {
        snn_mcp_debug( 'php.fatal', array(
            'message' => $err['message'],
            'file'    => str_replace( ABSPATH, '', (string) $err['file'] ),
            'line'    => $err['line'],
            'peakMb'  => round( memory_get_peak_usage( true ) / 1048576, 1 ),
        ), 'error' );
    }
}

// ── admin: read, download, clear ─────────────────────────────────────

/** Newest-first log entries, optionally only one request. */
function snn_mcp_debug_read( $limit = 200, $req = '' ) {
    $file = snn_mcp_debug_file();
    if ( ! file_exists( $file ) ) {
        return array();
    }
    $lines = file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
    $out   = array();
    for ( $i = count( $lines ) - 1; $i >= 0 && count( $out ) < $limit; $i-- ) {
        $row = json_decode( $lines[ $i ], true );
        if ( is_array( $row ) && ( $req === '' || ( $row['req'] ?? '' ) === $req ) ) {
            $out[] = $row;
        }
    }
    return $out;
}

add_action( 'admin_post_snn_mcp_debug_download', 'snn_mcp_debug_download' );
function snn_mcp_debug_download() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Not allowed.', 'snn' ) );
    }
    check_admin_referer( 'snn_mcp_debug' );
    $file = snn_mcp_debug_file();
    nocache_headers();
    header( 'Content-Type: application/x-ndjson; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="mcp-debug-' . gmdate( 'Ymd-His' ) . '.jsonl"' );
    $backup = preg_replace( '/\.log$/', '.1.log', $file );
    foreach ( array( $backup, $file ) as $f ) {
        if ( file_exists( $f ) ) {
            readfile( $f ); // phpcs:ignore WordPress.WP.AlternativeFunctions
        }
    }
    exit;
}

add_action( 'admin_post_snn_mcp_debug_clear', 'snn_mcp_debug_clear' );
function snn_mcp_debug_clear() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Not allowed.', 'snn' ) );
    }
    check_admin_referer( 'snn_mcp_debug' );
    $file = snn_mcp_debug_file();
    foreach ( array( $file, preg_replace( '/\.log$/', '.1.log', $file ) ) as $f ) {
        if ( file_exists( $f ) ) {
            @unlink( $f ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
        }
    }
    wp_safe_redirect( admin_url( 'admin.php?page=snn-mcp-settings&debug_cleared=1#snn-mcp-debug' ) );
    exit;
}

/** The Debug log panel on the MCP settings screen. */
function snn_mcp_debug_render_panel() {
    $file  = snn_mcp_debug_file();
    $size  = file_exists( $file ) ? filesize( $file ) : 0;
    $req   = isset( $_GET['req'] ) ? sanitize_key( wp_unslash( $_GET['req'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
    $rows  = snn_mcp_debug_read( $req ? 500 : 150, $req );
    $nonce = wp_create_nonce( 'snn_mcp_debug' );
    $base  = admin_url( 'admin.php?page=snn-mcp-settings' );
    ?>
    <h2 id="snn-mcp-debug"><?php esc_html_e( 'Debug log', 'snn' ); ?></h2>
    <p>
        <?php echo esc_html( sprintf( __( 'Log size: %s.', 'snn' ), size_format( $size ) ) ); ?>
        <a class="button" href="<?php echo esc_url( admin_url( 'admin-post.php?action=snn_mcp_debug_download&_wpnonce=' . $nonce ) ); ?>"><?php esc_html_e( 'Download', 'snn' ); ?></a>
        <a class="button" href="<?php echo esc_url( admin_url( 'admin-post.php?action=snn_mcp_debug_clear&_wpnonce=' . $nonce ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete the debug log?', 'snn' ) ); ?>');"><?php esc_html_e( 'Clear', 'snn' ); ?></a>
        <?php if ( $req ) : ?>
            <a class="button" href="<?php echo esc_url( $base . '#snn-mcp-debug' ); ?>"><?php esc_html_e( 'Show all requests', 'snn' ); ?></a>
        <?php endif; ?>
    </p>
    <?php if ( ! $rows ) : ?>
        <p class="description"><?php esc_html_e( 'No entries yet. If a tool call fails and nothing appears here, the request was refused before WordPress ran (web server, CDN or firewall body-size limit).', 'snn' ); ?></p>
        <?php return; ?>
    <?php endif; ?>
    <table class="widefat striped" style="max-width:1200px;table-layout:fixed">
        <thead><tr>
            <th style="width:170px"><?php esc_html_e( 'Time (UTC)', 'snn' ); ?></th>
            <th style="width:90px"><?php esc_html_e( 'Request', 'snn' ); ?></th>
            <th style="width:150px"><?php esc_html_e( 'Event', 'snn' ); ?></th>
            <th><?php esc_html_e( 'Details', 'snn' ); ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ( $rows as $row ) :
            $details = array_diff_key( $row, array( 't' => 1, 'req' => 1, 'level' => 1, 'event' => 1, 'user' => 1 ) );
            $color   = $row['level'] === 'error' ? '#b32d2e' : ( $row['level'] === 'warn' ? '#996800' : 'inherit' );
            $head    = $row['ability'] ?? ( $row['tool'] ?? ( $row['stage'] ?? '' ) );
            ?>
            <tr>
                <td><code><?php echo esc_html( str_replace( 'T', ' ', (string) $row['t'] ) ); ?></code></td>
                <td><a href="<?php echo esc_url( add_query_arg( 'req', $row['req'], $base ) . '#snn-mcp-debug' ); ?>"><code><?php echo esc_html( $row['req'] ); ?></code></a></td>
                <td style="color:<?php echo esc_attr( $color ); ?>"><strong><?php echo esc_html( $row['event'] ); ?></strong><?php if ( $head ) : ?><br><small><?php echo esc_html( $head ); ?></small><?php endif; ?></td>
                <td><details><summary style="cursor:pointer;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?php echo esc_html( substr( wp_json_encode( $details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ), 0, 220 ) ); ?></summary>
                    <pre style="white-space:pre-wrap;word-break:break-all;max-height:420px;overflow:auto"><?php echo esc_html( wp_json_encode( $details, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ); ?></pre>
                </details></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}
