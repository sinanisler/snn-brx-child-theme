<?php
/**
 * Concurrency guards for MCP / ability writes to a Bricks page.
 *
 * Same building blocks as Bricks' own MCP (includes/abilities): a SHA-256
 * document digest compatible with Save_Pipeline::document_digest(), the
 * WordPress post edit lock the builder holds through heartbeat, and
 * idempotency keys so a retried call never writes twice.
 *
 * One deliberate difference: Bricks only refuses a lock held by ANOTHER user.
 * An MCP client normally authenticates as the same person who has the builder
 * open, so here a fresh lock from any user — the caller included — blocks the
 * write unless the call passes force=true. Saving the open builder later would
 * otherwise silently overwrite what MCP wrote.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SNN_Bricks_Write_Guard {

    const IDEMPOTENCY_TTL = DAY_IN_SECONDS;

    /** Bricks-compatible digest of an element tree. */
    public static function digest( array $elements ) {
        if ( class_exists( '\Bricks\Abilities\Save_Pipeline' ) ) {
            return \Bricks\Abilities\Save_Pipeline::document_digest( $elements );
        }
        return hash( 'sha256', (string) wp_json_encode( array_values( $elements ) ) );
    }

    /** Content area a post saves into: content, or header/footer for those templates. */
    public static function area( $post_id ) {
        if ( class_exists( '\Bricks\Abilities\Elements' ) && method_exists( '\Bricks\Abilities\Elements', 'get_save_area_for_post' ) ) {
            return \Bricks\Abilities\Elements::get_save_area_for_post( $post_id );
        }
        if ( get_post_type( $post_id ) === 'bricks_template' ) {
            $type = get_post_meta( $post_id, '_bricks_template_type', true );
            if ( $type === 'header' || $type === 'footer' ) {
                return $type;
            }
        }
        return 'content';
    }

    public static function meta_key( $area ) {
        if ( class_exists( '\Bricks\Database' ) ) {
            return \Bricks\Database::get_bricks_data_key( $area );
        }
        return $area === 'header' ? '_bricks_page_header_2' : ( $area === 'footer' ? '_bricks_page_footer_2' : '_bricks_page_content_2' );
    }

    /** Current saved elements, bypassing the request-local meta cache. */
    public static function read_elements( $post_id, $area ) {
        wp_cache_delete( $post_id, 'post_meta' );
        $elements = get_post_meta( $post_id, self::meta_key( $area ), true );
        return is_array( $elements ) ? $elements : array();
    }

    /**
     * Who holds a fresh edit lock on the post (any user), or 0.
     * Reads _edit_lock directly because wp_check_post_lock() ignores the caller's own lock.
     */
    public static function lock_holder( $post_id ) {
        $lock = get_post_meta( $post_id, '_edit_lock', true );
        if ( ! $lock ) {
            return 0;
        }
        $parts = explode( ':', (string) $lock );
        $time  = (int) ( $parts[0] ?? 0 );
        $user  = (int) ( $parts[1] ?? get_post_meta( $post_id, '_edit_last', true ) );
        $window = (int) apply_filters( 'wp_check_post_lock_window', 150 );
        return ( $time && $time > time() - $window ) ? $user : 0;
    }

    /**
     * Run every pre-write check. Returns true or a WP_Error the ability returns as is.
     *
     * @param int    $post_id
     * @param string $area
     * @param string $expected_digest Digest the caller read (empty = not supplied).
     * @param bool   $force           Write even though a builder tab holds the lock.
     */
    public static function check( $post_id, $area, $expected_digest = '', $force = false ) {
        $holder = self::lock_holder( $post_id );
        if ( $holder && ! $force ) {
            $user = get_userdata( $holder );
            return new WP_Error( 'snn_post_locked', sprintf(
                'The page is open in the Bricks builder (locked by %s). Saving there would overwrite this write. Close the builder tab, or pass force=true to write anyway.',
                $user ? $user->display_name : 'user ' . $holder
            ), array( 'status' => 409, 'lockedBy' => $holder, 'self' => $holder === get_current_user_id() ) );
        }
        if ( $holder && $holder !== get_current_user_id() && ! current_user_can( 'edit_others_posts' ) ) {
            return new WP_Error( 'snn_post_locked', 'Another user is editing this page.', array( 'status' => 409 ) );
        }
        if ( $expected_digest !== '' && $expected_digest !== null ) {
            $actual = self::digest( self::read_elements( $post_id, $area ) );
            if ( ! hash_equals( (string) $expected_digest, $actual ) ) {
                return new WP_Error( 'snn_document_digest_mismatch', 'The page changed since you read it. Read it again with snn-bricks-get-page-tree and retry.', array(
                    'status'                 => 409,
                    'expectedDocumentDigest' => (string) $expected_digest,
                    'actualDocumentDigest'   => $actual,
                ) );
            }
        }
        return true;
    }

    private static function idem_key( $key ) {
        return 'snn_bricks_idem_' . get_current_user_id() . '_' . md5( (string) $key );
    }

    /**
     * Replay a finished call with the same key, or refuse when the key was used
     * for a different request. Returns null when the call should run.
     */
    public static function idempotent_replay( $key, $request_digest ) {
        if ( $key === '' || $key === null ) {
            return null;
        }
        $stored = get_transient( self::idem_key( $key ) );
        if ( ! is_array( $stored ) ) {
            return null;
        }
        if ( ! hash_equals( (string) $stored['request'], (string) $request_digest ) ) {
            return new WP_Error( 'snn_idempotency_key_reused', 'This idempotencyKey was already used for a different request. Use a new key.', array( 'status' => 409 ) );
        }
        $result             = $stored['result'];
        $result['replayed'] = true;
        return $result;
    }

    public static function remember( $key, $request_digest, $result ) {
        if ( $key === '' || $key === null || is_wp_error( $result ) ) {
            return;
        }
        set_transient( self::idem_key( $key ), array( 'request' => $request_digest, 'result' => $result ), self::IDEMPOTENCY_TTL );
    }

    public static function request_digest( array $input ) {
        unset( $input['idempotencyKey'] );
        ksort( $input );
        return hash( 'sha256', (string) wp_json_encode( $input ) );
    }
}
