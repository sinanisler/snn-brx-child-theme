<?php
/**
 * MCP protocol constants and JSON-RPC envelopes for the built-in server.
 *
 * The server speaks the MCP Streamable HTTP transport (spec 2025-11-25). A client
 * may still send an older identifier in `initialize`; those revisions only add
 * optional fields on top of the shapes used here, so the same results are valid
 * for them. JSON-RPC batches (a top-level array) are refused, as 2025-06-18 requires.
 *
 * @see https://modelcontextprotocol.io/specification
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SNN_MCP_Protocol {

    const LATEST_VERSION = '2025-11-25';

    /** Versions a client may request, newest first. */
    const SUPPORTED_VERSIONS = array( '2025-11-25', '2025-06-18', '2024-11-05' );

    const PARSE_ERROR      = -32700;
    const INVALID_REQUEST  = -32600;
    const METHOD_NOT_FOUND = -32601;
    const INVALID_PARAMS   = -32602;
    const INTERNAL_ERROR   = -32603;

    /** Echo a version the server supports, otherwise counter-propose the latest one. */
    public static function negotiate( $requested ) {
        return in_array( $requested, self::SUPPORTED_VERSIONS, true ) ? $requested : self::LATEST_VERSION;
    }

    public static function is_supported( $version ) {
        return in_array( $version, self::SUPPORTED_VERSIONS, true );
    }

    public static function result( $id, $result ) {
        return array(
            'jsonrpc' => '2.0',
            'id'      => $id,
            'result'  => $result,
        );
    }

    public static function error( $id, $code, $message ) {
        return array(
            'jsonrpc' => '2.0',
            'id'      => $id,
            'error'   => array(
                'code'    => $code,
                'message' => $message,
            ),
        );
    }
}

/**
 * Thrown inside a handler to answer the request with a JSON-RPC error.
 * The exception code is the JSON-RPC error code.
 */
class SNN_MCP_Rpc_Exception extends RuntimeException {
}
