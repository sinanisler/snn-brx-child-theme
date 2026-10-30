<?php
/**
 * Access tokens for the built-in MCP server: one per AI assistant.
 *
 * The token travels in the connection link itself (?key=...), or as an
 * Authorization: Bearer header for clients that take headers, so the assistant
 * needs nothing else. A token acts as the WordPress user who created it, with
 * one of two access levels:
 *  - read:  the assistant only gets abilities that are read-only.
 *  - write: read and write abilities, still limited by the group toggles.
 *
 * Only a keyed hash of each token is stored. The token itself is shown once,
 * right after it is created. Revoking a token cuts that assistant off at once.
 *
 * Stored as one option, snn_mcp_tokens, keyed by token id:
 *   id => [ 'name', 'hash', 'access' => read|write, 'user', 'created', 'last_used' ]
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const SNN_MCP_TOKEN_OPTION = 'snn_mcp_tokens';

/** How often last_used is written back, so a busy assistant does not write on every call. */
const SNN_MCP_TOKEN_TOUCH_EVERY = 300;

/** All stored tokens, keyed by id. */
function snn_mcp_tokens_get() {
    $tokens = get_option( SNN_MCP_TOKEN_OPTION, array() );
    return is_array( $tokens ) ? $tokens : array();
}

function snn_mcp_token_hash( $token ) {
    return hash_hmac( 'sha256', $token, wp_salt( 'auth' ) );
}

/**
 * Create a token for the current user.
 *
 * @return string The plain token. It is not stored, so keep it for the caller to show once.
 */
function snn_mcp_token_create( $name, $access ) {
    $token  = 'snn_' . bin2hex( random_bytes( 24 ) );
    $tokens = snn_mcp_tokens_get();

    $tokens[ bin2hex( random_bytes( 4 ) ) ] = array(
        'name'      => $name,
        'hash'      => snn_mcp_token_hash( $token ),
        'access'    => 'write' === $access ? 'write' : 'read',
        'user'      => get_current_user_id(),
        'created'   => time(),
        'last_used' => 0,
    );
    update_option( SNN_MCP_TOKEN_OPTION, $tokens, false );

    return $token;
}

/** Remove a token. Returns false when no token has that id. */
function snn_mcp_token_revoke( $id ) {
    $tokens = snn_mcp_tokens_get();
    if ( ! isset( $tokens[ $id ] ) ) {
        return false;
    }
    unset( $tokens[ $id ] );
    update_option( SNN_MCP_TOKEN_OPTION, $tokens, false );
    return true;
}

/** Id of the stored token a plain token belongs to, or '' when none does. */
function snn_mcp_token_find_id( $token ) {
    $hash = snn_mcp_token_hash( $token );
    foreach ( snn_mcp_tokens_get() as $id => $record ) {
        if ( hash_equals( (string) $record['hash'], $hash ) ) {
            return (string) $id;
        }
    }
    return '';
}

/** The token a request presents: a Bearer header first, then the ?key= link parameter. */
function snn_mcp_token_from_request( WP_REST_Request $request ) {
    $header = (string) $request->get_header( 'authorization' );
    if ( '' === $header ) {
        // Some hosts pass the header only under these names; WordPress core reads them the same way.
        $header = isset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ? (string) wp_unslash( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
    }
    if ( preg_match( '/^Bearer\s+(\S+)$/i', $header, $match ) ) {
        return $match[1];
    }

    $key = $request->get_param( 'key' );
    return is_string( $key ) ? $key : '';
}

/**
 * Called by the MCP route's permission check. A request without a token is left
 * alone, so Application Passwords and logged-in sessions keep working. A request
 * with a token must present a valid one, and then it runs as the token's user.
 *
 * @return true|WP_Error
 */
function snn_mcp_token_authenticate( WP_REST_Request $request ) {
    unset( $GLOBALS['snn_mcp_token'] );

    $token = snn_mcp_token_from_request( $request );
    if ( '' === $token ) {
        return true;
    }

    $id     = snn_mcp_token_find_id( $token );
    $tokens = snn_mcp_tokens_get();
    if ( '' === $id || ! get_user_by( 'id', (int) $tokens[ $id ]['user'] ) ) {
        return new WP_Error( 'snn_mcp_bad_token', __( 'The MCP token is unknown or was revoked.', 'snn' ), array( 'status' => 401 ) );
    }

    $record = $tokens[ $id ];
    wp_set_current_user( (int) $record['user'] );
    $GLOBALS['snn_mcp_token'] = array(
        'id'     => $id,
        'name'   => (string) $record['name'],
        'access' => (string) $record['access'],
    );

    if ( time() - (int) $record['last_used'] >= SNN_MCP_TOKEN_TOUCH_EVERY ) {
        // Re-read before writing, so a token revoked a moment ago is not written back.
        $fresh = snn_mcp_tokens_get();
        if ( isset( $fresh[ $id ] ) ) {
            $fresh[ $id ]['last_used'] = time();
            update_option( SNN_MCP_TOKEN_OPTION, $fresh, false );
        }
    }

    return true;
}

/** Access of the current request's token: 'read', 'write', or '' when the request used no token. */
function snn_mcp_token_access() {
    return $GLOBALS['snn_mcp_token']['access'] ?? '';
}

/** Name of the current request's token, or '' when it used none. */
function snn_mcp_token_name() {
    return $GLOBALS['snn_mcp_token']['name'] ?? '';
}

/** Whether the current request may use an ability. A read-only token gets read-only abilities only. */
function snn_mcp_token_allows_ability( $ability ) {
    if ( 'read' !== snn_mcp_token_access() ) {
        return true;
    }
    return snn_mcp_args_are_readonly( array( 'meta' => $ability->get_meta() ) );
}

/** Address an assistant connects to with a token: the endpoint plus the key. */
function snn_mcp_token_url( $token ) {
    return add_query_arg( 'key', $token, snn_mcp_endpoint_url() );
}

/** Transient that holds a token just created, so the settings page can show it once. */
function snn_mcp_token_notice_key() {
    return 'snn_mcp_new_token_' . get_current_user_id();
}

add_action( 'admin_post_snn_mcp_create_token', 'snn_mcp_handle_create_token' );
function snn_mcp_handle_create_token() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Not allowed.', 'snn' ) );
    }
    check_admin_referer( 'snn_mcp_create_token' );

    $name   = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
    $name   = '' === $name ? __( 'AI assistant', 'snn' ) : mb_substr( $name, 0, 60 );
    $access = sanitize_key( wp_unslash( $_POST['access'] ?? 'read' ) ); // phpcs:ignore WordPress.Security.NonceVerification

    $token = snn_mcp_token_create( $name, $access );
    set_transient( snn_mcp_token_notice_key(), array( 'name' => $name, 'access' => $access, 'token' => $token ), 10 * MINUTE_IN_SECONDS );

    wp_safe_redirect( admin_url( 'admin.php?page=snn-mcp-settings' ) );
    exit;
}

add_action( 'admin_post_snn_mcp_revoke_token', 'snn_mcp_handle_revoke_token' );
function snn_mcp_handle_revoke_token() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Not allowed.', 'snn' ) );
    }
    $id = sanitize_key( wp_unslash( $_GET['id'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
    check_admin_referer( 'snn_mcp_revoke_token_' . $id );

    snn_mcp_token_revoke( $id );

    wp_safe_redirect( admin_url( 'admin.php?page=snn-mcp-settings&snn_mcp_revoked=1' ) );
    exit;
}
