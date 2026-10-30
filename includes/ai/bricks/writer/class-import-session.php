<?php
/**
 * Design-zip import sessions: the builder agent's conversion loop, server side.
 *
 * In the builder the browser holds the unpacked export, cuts it into small
 * passes, has the model rewrite each pass, retries a failing pass in halves,
 * and builds the results in design order. An MCP client has no such state, so
 * the server keeps it:
 *
 *  1. snn-bricks-import-zip unpacks the zip once and stores a session: the body
 *     markup cut into passes (SNN_Bricks_Zip_Import::split_chunks), the CSS,
 *     and the asset report.
 *  2. snn-bricks-import-chunk hands out one pass at a time with only the CSS it
 *     uses and the conversion rules. A pass that is too big for the model is
 *     split in two (ids "3" → "3.0", "3.1"), like the builder's bisect().
 *  3. snn-bricks-html-to-bricks with import_id + chunk builds a converted pass.
 *     The session places it in design order whatever order passes arrive in,
 *     and remembers its root ids so the pass can be rebuilt in place.
 *
 * Sessions and partial uploads are JSON / zip files in a private uploads
 * folder, owned by the user who created them, and expire after two days.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SNN_Bricks_Import_Session {

    const TTL          = 172800; // 2 days
    const PART_MAX     = 8388608; // 8 MB decoded per upload part
    const MAX_CHUNK    = 12000;

    // ── storage ──────────────────────────────────────────────────────

    private static function dir( $sub = '' ) {
        $secret = get_option( 'snn_bricks_import_secret' );
        if ( ! $secret ) {
            $secret = wp_generate_password( 16, false );
            update_option( 'snn_bricks_import_secret', $secret, false );
        }
        $dir = trailingslashit( wp_upload_dir( null, false )['basedir'] ) . 'snn-bricks-imports-' . $secret;
        if ( ! is_dir( $dir ) && wp_mkdir_p( $dir ) ) {
            file_put_contents( $dir . '/.htaccess', "Require all denied\nDeny from all\n" );
            file_put_contents( $dir . '/index.php', "<?php\n// Silence is golden.\n" );
        }
        if ( $sub !== '' ) {
            $dir .= '/' . $sub;
            if ( ! is_dir( $dir ) ) {
                wp_mkdir_p( $dir );
            }
        }
        return $dir;
    }

    private static function valid_id( $id ) {
        return is_string( $id ) && preg_match( '/^[a-z0-9]{8,40}$/', $id );
    }

    private static function path( $id ) {
        return self::dir( 'sessions' ) . '/' . $id . '.json';
    }

    /** Delete expired sessions and uploads (runs on every new import). */
    public static function cleanup() {
        foreach ( array( 'sessions', 'uploads' ) as $sub ) {
            foreach ( (array) glob( self::dir( $sub ) . '/*' ) as $f ) {
                if ( is_file( $f ) && filemtime( $f ) < time() - self::TTL ) {
                    @unlink( $f ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
                }
            }
        }
    }

    public static function save( array $s ) {
        $s['updated'] = time();
        file_put_contents( self::path( $s['id'] ), wp_json_encode( $s, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ), LOCK_EX );
    }

    /** @return array|WP_Error */
    public static function load( $id ) {
        $id = strtolower( trim( (string) $id ) );
        if ( ! self::valid_id( $id ) || ! file_exists( self::path( $id ) ) ) {
            return new WP_Error( 'snn_import_not_found', 'No import session "' . $id . '". Sessions expire after two days: run snn-bricks-import-zip again.', array( 'status' => 404 ) );
        }
        $s = json_decode( (string) file_get_contents( self::path( $id ) ), true );
        if ( ! is_array( $s ) ) {
            return new WP_Error( 'snn_import_corrupt', 'The import session could not be read.', array( 'status' => 500 ) );
        }
        if ( (int) $s['user'] !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'snn_import_forbidden', 'This import session belongs to another user.', array( 'status' => 403 ) );
        }
        return $s;
    }

    /**
     * Start a session from SNN_Bricks_Zip_Import::extract() output.
     * @param array $source  type (base64|upload|attachment|url), name, plus a reference
     */
    public static function create( array $extract, array $source, $post_id = 0, $chunk_chars = 0 ) {
        self::cleanup();
        $max    = $chunk_chars ? max( 1500, min( self::MAX_CHUNK, (int) $chunk_chars ) ) : SNN_Bricks_Zip_Import::CHUNK_CHARS;
        $chunks = array();
        foreach ( SNN_Bricks_Zip_Import::split_chunks( $extract['html'], $max ) as $i => $c ) {
            $chunks[ (string) $i ] = array( 'label' => $c['label'], 'parts' => $c['parts'], 'status' => 'pending' );
        }
        $s = array(
            'id'         => strtolower( wp_generate_password( 20, false ) ),
            'user'       => get_current_user_id(),
            'created'    => time(),
            'source'     => $source,
            'postId'     => (int) $post_id,
            'htmlPath'   => $extract['htmlPath'],
            'candidates' => $extract['candidates'],
            'css'        => $extract['css'],
            'report'     => $extract['report'],
            'chunkChars' => $max,
            'chunks'     => $chunks,
            'prefixes'   => array(),
        );
        self::save( $s );
        return $s;
    }

    // ── chunk order ──────────────────────────────────────────────────

    /** "3.1" sorts after "3.0" and before "4" (the builder's cmpOrder). */
    public static function cmp( $a, $b ) {
        $x = array_map( 'intval', explode( '.', (string) $a ) );
        $y = array_map( 'intval', explode( '.', (string) $b ) );
        for ( $i = 0; $i < max( count( $x ), count( $y ) ); $i++ ) {
            $d = ( $x[ $i ] ?? -1 ) - ( $y[ $i ] ?? -1 );
            if ( $d ) {
                return $d;
            }
        }
        return 0;
    }

    /** Leaf passes (not split) in design order. */
    public static function order( array $s ) {
        $ids = array();
        foreach ( $s['chunks'] as $id => $c ) {
            if ( $c['status'] !== 'split' ) {
                $ids[] = (string) $id;
            }
        }
        usort( $ids, array( __CLASS__, 'cmp' ) );
        return $ids;
    }

    public static function progress( array $s ) {
        $order   = self::order( $s );
        $pending = array();
        $built   = 0;
        foreach ( $order as $id ) {
            if ( $s['chunks'][ $id ]['status'] === 'built' ) {
                $built++;
            } else {
                $pending[] = $id;
            }
        }
        return array(
            'importId'  => $s['id'],
            'total'     => count( $order ),
            'built'     => $built,
            'pending'   => $pending,
            'nextChunk' => $pending ? $pending[0] : null,
        );
    }

    /** One line per pass, for the client's plan. */
    public static function plan( array $s ) {
        $out = array();
        foreach ( self::order( $s ) as $i => $id ) {
            $c     = $s['chunks'][ $id ];
            $out[] = array(
                'chunk'    => $id,
                'position' => $i + 1,
                'label'    => $c['label'],
                'sections' => count( $c['parts'] ),
                'chars'    => array_sum( array_map( 'mb_strlen', $c['parts'] ) ),
                'status'   => $c['status'],
            );
        }
        return $out;
    }

    /** Halve a pending pass. @return array|WP_Error the two new chunk ids */
    public static function split( array &$s, $chunk_id ) {
        $chunk_id = (string) $chunk_id;
        $c        = $s['chunks'][ $chunk_id ] ?? null;
        if ( ! $c || $c['status'] === 'split' ) {
            return new WP_Error( 'snn_chunk_not_found', 'No pass "' . $chunk_id . '" to split. Pending passes: ' . implode( ', ', self::progress( $s )['pending'] ) . '.', array( 'status' => 404 ) );
        }
        if ( $c['status'] === 'built' ) {
            return new WP_Error( 'snn_chunk_built', 'Pass "' . $chunk_id . '" is already built. Rebuild it with rebuild=true instead of splitting it.', array( 'status' => 409 ) );
        }
        $halves = SNN_Bricks_Zip_Import::bisect( $c );
        if ( ! $halves ) {
            return new WP_Error( 'snn_chunk_single', 'Pass "' . $chunk_id . '" is a single section and cannot be split. Convert it in parts yourself: build the first half with mode "append" and the rest with snn-bricks-edit-elements, or simplify the markup.', array( 'status' => 422 ) );
        }
        $s['chunks'][ $chunk_id ]['status'] = 'split';
        $ids = array();
        foreach ( $halves as $i => $h ) {
            $id                  = $chunk_id . '.' . $i;
            $s['chunks'][ $id ]  = array( 'label' => $h['label'], 'parts' => $h['parts'], 'status' => 'pending' );
            $ids[]               = $id;
        }
        self::save( $s );
        return $ids;
    }

    /** Unique class prefix per pass, so passes cannot collide (the builder's slug()). */
    private static function prefix( array &$s, $chunk_id ) {
        if ( isset( $s['prefixes'][ $chunk_id ] ) ) {
            return $s['prefixes'][ $chunk_id ];
        }
        $label = strtolower( (string) $s['chunks'][ $chunk_id ]['label'] );
        $base  = substr( trim( preg_replace( '/[^a-z0-9]+/', '-', remove_accents( $label ) ), '-' ), 0, 24 );
        $base  = $base !== '' && ! ctype_digit( $base[0] ) ? $base : 'sec' . str_replace( '.', '-', $chunk_id );
        $taken = array_flip( array_values( $s['prefixes'] ) );
        $p     = $base;
        for ( $n = 2; isset( $taken[ $p ] ); $n++ ) {
            $p = $base . '-' . $n;
        }
        $s['prefixes'][ $chunk_id ] = $p;
        return $p;
    }

    /** What the client converts for one pass. */
    public static function chunk_payload( array &$s, $chunk_id, $include_rules ) {
        $chunk_id = (string) $chunk_id;
        $c        = $s['chunks'][ $chunk_id ] ?? null;
        if ( ! $c || $c['status'] === 'split' ) {
            $p = self::progress( $s );
            return new WP_Error( 'snn_chunk_not_found', 'No pass "' . $chunk_id . '". Pending passes: ' . ( $p['pending'] ? implode( ', ', $p['pending'] ) : 'none' ) . '.', array( 'status' => 404 ) );
        }
        $order  = self::order( $s );
        $only   = count( $order ) === 1;
        $markup = implode( "\n", $c['parts'] );
        $prefix = self::prefix( $s, $chunk_id );
        self::save( $s );

        $out = array(
            'importId' => $s['id'],
            'chunk'    => $chunk_id,
            'position' => ( array_search( $chunk_id, $order, true ) + 1 ) . ' of ' . count( $order ),
            'label'    => $c['label'],
            'status'   => $c['status'],
            'prefix'   => $prefix,
            'markup'   => $markup,
            // A single pass gets the whole stylesheet; others only the rules they use.
            'css'      => $only ? $s['css'] : SNN_Bricks_Zip_Import::prune_css( $s['css'], $markup ),
        );
        $out['sourceChars'] = mb_strlen( $out['markup'] ) + mb_strlen( $out['css'] );
        $out['rules']       = $include_rules ? self::conversion_rules( $prefix ) : self::rules_reminder( $prefix );
        $out['send']        = 'snn-bricks-html-to-bricks with post_id, import_id "' . $s['id'] . '", chunk "' . $chunk_id . '", html (your converted markup) and css (your converted stylesheet). If your reply would be too long, call snn-bricks-import-chunk with split=true for this chunk first.';
        if ( $c['status'] === 'built' ) {
            $out['send'] .= ' This pass is already built: add rebuild=true to replace it in place.';
        }
        return $out;
    }

    /** The builder's conversionPrompt(), for a client that converts the pass itself. */
    public static function conversion_rules( $prefix ) {
        return implode( "\n", array(
            'You are converting an EXISTING design export (Figma, Penpot, Sketch or similar "export to HTML/CSS") into clean, Bricks-ready HTML.',
            '',
            'THIS IS A CONVERSION, NOT A REDESIGN.',
            '- Reproduce the given design faithfully: same text, same colors, same fonts, same order, same imagery.',
            '- Never invent content, never substitute Lorem Ipsum, never drop a section.',
            '- Keep every image URL EXACTLY as given — they are already WordPress media library URLs.',
            '- Inline <svg> stays a plain <svg> with no data-bricks wrapper. An icon inside a button or link stays DIRECTLY inside it, one icon per button, at the start or end of the label.',
            '- Links keep their real href; target="_blank" opens a new tab.',
            '',
            'WHAT YOU MUST FIX (design tools export unusable markup):',
            '- Absolute positioning and fixed pixel canvas widths (width:1440px, position:absolute, left/top offsets) MUST become normal flow: flex/grid, max-width containers, percentage or auto widths.',
            '- Drop the exporter\'s meaningless wrapper divs and generated class names (box-1, frame-1206). Rename every class semantically.',
            '- Fixed heights that only fitted the canvas must go; let content size itself.',
            '- Turn the generated class soup into a small, readable class system.',
            '- Add responsive @media queries — the export has none. Use this site\'s breakpoints (snn-bricks-start-here lists them).',
            '- Remove empty spacer divs that exist only for canvas layout.',
            '',
            'CLASS NAMING: prefix every class with "' . $prefix . '-" so passes cannot collide: .' . $prefix . ', .' . $prefix . '-title, .' . $prefix . '-grid.',
            '',
            'REQUIRED data-bricks ATTRIBUTES on every structural element: section (top-level section/header/footer), container (one per section, its direct child), block (inner layout divs), heading (h1-h6), text-basic (p, span, li), text (rich inline formatting), button (CTAs, with href), text-link (inline <a>), image (<img>, src verbatim), icon (FontAwesome <i>), divider (<hr>), custom-html-css-script (iframes, forms, tables).',
            'Structure: section > container > content. Never a container inside a block.',
            'class="..." only — NO inline style="" attributes.',
            'CSS: one property per line; :root custom properties with concrete hex/px values, never var() as a value.',
        ) );
    }

    private static function rules_reminder( $prefix ) {
        return 'Same conversion rules as the first pass (call with include_rules=true to see them again): faithful conversion, normal flow instead of absolute/canvas sizes, semantic classes prefixed "' . $prefix . '-", data-bricks hints, section > container > content, no inline styles, responsive @media, image URLs verbatim.';
    }

    // ── building in design order ─────────────────────────────────────

    /**
     * Where a pass goes on the page: after the nearest earlier built pass,
     * else before the nearest later one, else at the end.
     * @return array mode, anchor_id, remove (root ids of a previous build of this pass)
     */
    public static function placement( array $s, $chunk_id, array $elements, $rebuild ) {
        $top = array();
        foreach ( $elements as $el ) {
            $p = $el['parent'] ?? 0;
            if ( $p === 0 || $p === '0' ) {
                $top[ (string) $el['id'] ] = true;
            }
        }
        $live = function ( $id ) use ( $s, $top ) {
            $roots = (array) ( $s['chunks'][ $id ]['built']['rootIds'] ?? array() );
            return array_values( array_filter( $roots, function ( $r ) use ( $top ) {
                return isset( $top[ (string) $r ] );
            } ) );
        };

        $c      = $s['chunks'][ (string) $chunk_id ];
        $remove = array();
        if ( $c['status'] === 'built' ) {
            if ( ! $rebuild ) {
                return new WP_Error( 'snn_chunk_built', 'Pass "' . $chunk_id . '" is already built (root ids: ' . implode( ', ', (array) $c['built']['rootIds'] ) . '). Send rebuild=true to replace it in place.', array( 'status' => 409 ) );
            }
            $remove = $live( (string) $chunk_id );
        }

        $before = null;
        $after  = null;
        foreach ( self::order( $s ) as $id ) {
            if ( $id === (string) $chunk_id || $s['chunks'][ $id ]['status'] !== 'built' || ! $live( $id ) ) {
                continue;
            }
            if ( self::cmp( $id, $chunk_id ) < 0 ) {
                $before = $id;
            } elseif ( $after === null ) {
                $after = $id;
            }
        }
        if ( $remove ) {
            // In place: the old pass's position is the anchor.
            return array( 'mode' => 'insert-before', 'anchor_id' => $remove[0], 'remove' => $remove );
        }
        if ( $before !== null ) {
            $roots = $live( $before );
            return array( 'mode' => 'insert-after', 'anchor_id' => end( $roots ), 'remove' => array() );
        }
        if ( $after !== null ) {
            return array( 'mode' => 'insert-before', 'anchor_id' => $live( $after )[0], 'remove' => array() );
        }
        return array( 'mode' => 'append', 'anchor_id' => '', 'remove' => array() );
    }

    public static function mark_built( array &$s, $chunk_id, $post_id, array $root_ids ) {
        // Re-read first: another pass may have been built since this one started.
        $fresh = self::load( $s['id'] );
        if ( ! is_wp_error( $fresh ) ) {
            $s = $fresh;
        }
        $s['chunks'][ (string) $chunk_id ]['status'] = 'built';
        $s['chunks'][ (string) $chunk_id ]['built']  = array( 'postId' => (int) $post_id, 'rootIds' => array_values( $root_ids ), 'at' => time() );
        if ( ! $s['postId'] ) {
            $s['postId'] = (int) $post_id;
        }
        self::save( $s );
    }

    // ── zip upload in parts ──────────────────────────────────────────

    /**
     * Append one part of a zip sent in pieces. Parts must arrive in order;
     * resending the last part is accepted (a retry after a timeout).
     * @return array|WP_Error
     */
    public static function append_part( $upload_id, $index, $total, $bytes ) {
        $index = (int) $index;
        $total = (int) $total;
        if ( $upload_id === '' ) {
            if ( $index !== 0 ) {
                return new WP_Error( 'snn_upload_missing', 'Start with part 0 and no upload_id; reuse the upload_id it returns for the next parts.', array( 'status' => 400 ) );
            }
            $upload_id = strtolower( wp_generate_password( 20, false ) );
        }
        if ( ! self::valid_id( $upload_id ) ) {
            return new WP_Error( 'snn_upload_bad_id', 'Invalid upload_id.', array( 'status' => 400 ) );
        }
        if ( strlen( $bytes ) > self::PART_MAX ) {
            return new WP_Error( 'snn_part_too_large', 'A part may be at most 8 MB decoded.', array( 'status' => 413 ) );
        }
        $dir  = self::dir( 'uploads' );
        $file = $dir . '/' . $upload_id . '.zip';
        $meta_file = $dir . '/' . $upload_id . '.json';
        $meta = file_exists( $meta_file ) ? json_decode( (string) file_get_contents( $meta_file ), true ) : null;
        if ( $index === 0 && ! $meta ) {
            $meta = array( 'user' => get_current_user_id(), 'received' => 0, 'bytes' => 0, 'total' => $total, 'lastHash' => '', 'lastSize' => 0 );
            file_put_contents( $file, '' );
        }
        if ( ! is_array( $meta ) ) {
            return new WP_Error( 'snn_upload_missing', 'Unknown or expired upload_id. Start again with part 0.', array( 'status' => 404 ) );
        }
        if ( (int) $meta['user'] !== get_current_user_id() ) {
            return new WP_Error( 'snn_upload_forbidden', 'This upload belongs to another user.', array( 'status' => 403 ) );
        }
        $hash = hash( 'sha256', $bytes );
        if ( $index === $meta['received'] - 1 && hash_equals( $meta['lastHash'], $hash ) ) {
            return self::part_status( $upload_id, $meta, true ); // retry of the last part
        }
        if ( $index !== $meta['received'] ) {
            return new WP_Error( 'snn_part_out_of_order', 'Expected part ' . $meta['received'] . ', got ' . $index . '.', array( 'status' => 409, 'expected' => $meta['received'] ) );
        }
        if ( $meta['bytes'] + strlen( $bytes ) > SNN_Bricks_Zip_Import::LIMITS['zipBytes'] ) {
            return new WP_Error( 'snn_zip_too_large', 'The .zip is larger than 100 MB.', array( 'status' => 413 ) );
        }
        file_put_contents( $file, $bytes, FILE_APPEND | LOCK_EX );
        $meta['received']++;
        $meta['bytes']   += strlen( $bytes );
        $meta['total']    = $total ? $total : $meta['total'];
        $meta['lastHash'] = $hash;
        file_put_contents( $meta_file, wp_json_encode( $meta ), LOCK_EX );
        return self::part_status( $upload_id, $meta, false );
    }

    private static function part_status( $upload_id, array $meta, $replayed ) {
        $complete = $meta['total'] && $meta['received'] >= $meta['total'];
        return array(
            'uploadId' => $upload_id,
            'received' => $meta['received'],
            'total'    => $meta['total'],
            'bytes'    => $meta['bytes'],
            'complete' => $complete,
            'replayed' => $replayed,
            'next'     => $complete
                ? 'Upload complete: call snn-bricks-import-zip with upload_id "' . $upload_id . '".'
                : 'Send part ' . $meta['received'] . ' with upload_id "' . $upload_id . '".',
        );
    }

    /** Path of a finished upload. @return string|WP_Error */
    public static function upload_file( $upload_id ) {
        $upload_id = strtolower( (string) $upload_id );
        if ( ! self::valid_id( $upload_id ) ) {
            return new WP_Error( 'snn_upload_bad_id', 'Invalid upload_id.', array( 'status' => 400 ) );
        }
        $dir  = self::dir( 'uploads' );
        $meta = file_exists( $dir . '/' . $upload_id . '.json' ) ? json_decode( (string) file_get_contents( $dir . '/' . $upload_id . '.json' ), true ) : null;
        if ( ! is_array( $meta ) || ! file_exists( $dir . '/' . $upload_id . '.zip' ) ) {
            return new WP_Error( 'snn_upload_missing', 'Unknown or expired upload_id.', array( 'status' => 404 ) );
        }
        if ( (int) $meta['user'] !== get_current_user_id() ) {
            return new WP_Error( 'snn_upload_forbidden', 'This upload belongs to another user.', array( 'status' => 403 ) );
        }
        if ( $meta['total'] && $meta['received'] < $meta['total'] ) {
            return new WP_Error( 'snn_upload_incomplete', 'Only ' . $meta['received'] . ' of ' . $meta['total'] . ' parts arrived.', array( 'status' => 409 ) );
        }
        return $dir . '/' . $upload_id . '.zip';
    }
}
