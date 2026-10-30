<?php
/**
 * snn/bricks-import-zip — unpack a design-export .zip on the server and start
 * an import session (SNN_Bricks_Import_Session).
 *
 * Raster assets go to the media library, SVG assets are inlined, local
 * stylesheets are inlined, every reference is rewritten, sections are put in
 * render order, and the body is cut into the same small conversion passes the
 * builder agent uses. The client then works pass by pass:
 * snn-bricks-import-chunk → convert → snn-bricks-html-to-bricks (import_id + chunk).
 *
 * Where the zip comes from (exactly one):
 *  - attachment_id  a zip in the media library (SNN Settings > MCP has an upload box)
 *  - zip_url        a public http(s) URL
 *  - upload_id      a zip sent in parts with snn-bricks-upload-zip-part
 *  - zip_base64     the whole zip inside this call (25 MB; only practical for
 *                   local clients that can read the file themselves)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_abilities_api_init', 'snn_register_bricks_import_zip_ability' );
function snn_register_bricks_import_zip_ability() {
    $props = array_merge(
        array(
            'attachment_id' => array( 'type' => 'integer', 'description' => 'A .zip in the media library. Hosted chats (claude.ai, ChatGPT) should use this: the user uploads the zip under SNN Settings > MCP and gives you the ID.' ),
            'zip_url'       => array( 'type' => 'string', 'description' => 'Public http(s) URL of the .zip (max 100 MB).' ),
            'upload_id'     => array( 'type' => 'string', 'description' => 'A zip sent in parts with snn-bricks-upload-zip-part.' ),
            'zip_base64'    => array( 'type' => 'string', 'description' => 'The whole .zip as base64 (max 25 MB decoded). Only for clients that can read the file with a tool; never type file bytes out yourself.' ),
            'post_id'       => array( 'type' => 'integer', 'description' => 'Target page. Optional here; needed for compile=true and when building passes.' ),
            'html_path'     => array( 'type' => 'string', 'description' => 'Which HTML file inside the zip to use (default: index.html, else the largest page). candidates lists them.' ),
            'chunk_chars'   => array( 'type' => 'integer', 'minimum' => 1500, 'maximum' => 12000, 'description' => 'Markup per conversion pass (default 4000, as in the builder). Smaller passes need fewer output tokens per reply.' ),
            'return'        => array( 'type' => 'string', 'enum' => array( 'session', 'full' ), 'default' => 'session', 'description' => 'session: a pass plan plus the first pass (recommended). full: also the whole cleaned html and css.' ),
            'compile'       => array( 'type' => 'boolean', 'default' => false, 'description' => 'Build the whole export into post_id at once without converting it first. Only for exports that are already clean semantic HTML.' ),
            'mode'          => array( 'type' => 'string', 'enum' => SNN_Bricks_Writer::MODES, 'default' => 'append' ),
        ),
        snn_bricks_write_input_properties()
    );

    wp_register_ability( 'snn/bricks-import-zip', array(
        'label'       => __( 'Bricks: Import Design Zip', 'snn' ),
        'description' => __( 'Start a design-export .zip import (Figma/Penpot/Webflow "export to HTML"), the same pipeline as the Bricks AI agent in the builder: images are uploaded to the media library, SVGs and stylesheets inlined, sections put in render order, and the page cut into small conversion passes. Give the zip as attachment_id, zip_url, upload_id (snn-bricks-upload-zip-part) or zip_base64. If you can read the zip but cannot send it (hosted chats), do NOT call this and do not wait: convert the HTML/CSS yourself, build it with snn-bricks-html-to-bricks keeping the export\'s image paths, and give the user feedback.assets.uploadUrl to drop the zip for the images (see snn-bricks-start-here). Returns importId, the pass plan and the first pass with the conversion rules. Then, for each pass: convert it, send it to snn-bricks-html-to-bricks with import_id and chunk, and fetch the next pass with snn-bricks-import-chunk. Each build answers with feedback and the next step.', 'snn' ),
        'category'    => 'snn-bricks',
        'input_schema' => array(
            'type'       => 'object',
            'properties' => $props,
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'importId'   => array( 'type' => 'string' ),
                'htmlPath'   => array( 'type' => 'string' ),
                'candidates' => array( 'type' => 'array' ),
                'report'     => array( 'type' => 'object' ),
                'plan'       => array( 'type' => 'array' ),
                'firstChunk' => array( 'type' => 'object' ),
                'next'       => array( 'type' => 'string' ),
                'html'       => array( 'type' => 'string' ),
                'css'        => array( 'type' => 'string' ),
                'build'      => array( 'type' => 'object' ),
            ),
        ),
        'execute_callback' => function ( $input ) {
            return snn_bricks_with_idempotency( $input, function () use ( $input ) {
                return snn_bricks_import_zip( $input );
            } );
        },
        'permission_callback' => function () {
            return current_user_can( 'upload_files' ) && current_user_can( 'edit_posts' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'annotations'  => array( 'readonly' => false, 'destructive' => false, 'idempotent' => false ),
        ),
    ) );
}

function snn_bricks_import_zip( array $input ) {
    $given = array_keys( array_filter( array(
        'attachment_id' => ! empty( $input['attachment_id'] ),
        'zip_url'       => ! empty( $input['zip_url'] ),
        'upload_id'     => ! empty( $input['upload_id'] ),
        'zip_base64'    => ! empty( $input['zip_base64'] ),
    ) ) );
    if ( count( $given ) !== 1 ) {
        return new WP_Error( 'snn_bad_source', 'Give exactly one of attachment_id, zip_url, upload_id or zip_base64. If you cannot send the file, build the page from its HTML/CSS with snn-bricks-html-to-bricks instead (keep image paths); the reply gives the user an upload link for the images.', array( 'status' => 400 ) );
    }
    $options = array( 'html_path' => $input['html_path'] ?? '' );
    $debug   = function ( $data, $level = 'info' ) {
        if ( function_exists( 'snn_mcp_debug' ) ) {
            snn_mcp_debug( 'stage', array_merge( array( 'stage' => 'zip.source' ), $data ), $level );
        }
    };
    $t0 = microtime( true );

    switch ( $given[0] ) {
        case 'attachment_id':
            $id   = (int) $input['attachment_id'];
            $file = get_attached_file( $id );
            if ( get_post_type( $id ) !== 'attachment' || ! $file ) {
                return new WP_Error( 'snn_attachment_not_found', 'No media library file with ID ' . $id . '.', array( 'status' => 404 ) );
            }
            if ( ! current_user_can( 'edit_post', $id ) ) {
                return new WP_Error( 'snn_forbidden', 'You cannot use media item ' . $id . '.', array( 'status' => 403 ) );
            }
            if ( ! preg_match( '/\.zip$/i', $file ) ) {
                return new WP_Error( 'snn_not_zip', 'Media item ' . $id . ' is not a .zip file.', array( 'status' => 400 ) );
            }
            $source = array( 'type' => 'attachment', 'attachmentId' => $id, 'name' => basename( $file ) );
            $debug( $source + array( 'bytes' => filesize( $file ) ) );
            $result = SNN_Bricks_Zip_Import::extract_file( $file, $options );
            break;

        case 'zip_url':
            $result = snn_bricks_fetch_zip( (string) $input['zip_url'], $options );
            $source = array( 'type' => 'url', 'url' => esc_url_raw( (string) $input['zip_url'] ), 'name' => basename( (string) wp_parse_url( (string) $input['zip_url'], PHP_URL_PATH ) ) );
            $debug( $source + array( 'ok' => ! is_wp_error( $result ) ) );
            break;

        case 'upload_id':
            $file = SNN_Bricks_Import_Session::upload_file( $input['upload_id'] );
            if ( is_wp_error( $file ) ) {
                return $file;
            }
            $source = array( 'type' => 'upload', 'uploadId' => (string) $input['upload_id'], 'name' => 'upload.zip' );
            $debug( $source + array( 'bytes' => filesize( $file ) ) );
            $result = SNN_Bricks_Zip_Import::extract_file( $file, $options );
            break;

        default:
            $b64   = preg_replace( '/^data:[^;,]+;base64,/i', '', (string) $input['zip_base64'] );
            $bytes = base64_decode( $b64, true );
            if ( $bytes === false || $bytes === '' ) {
                return new WP_Error( 'snn_bad_base64', 'zip_base64 could not be decoded. It must be the exact base64 of the file — use attachment_id or upload parts instead of typing it out.', array( 'status' => 400 ) );
            }
            if ( strlen( $bytes ) > SNN_Bricks_Zip_Import::LIMITS['base64Bytes'] ) {
                return new WP_Error( 'snn_zip_too_large', 'zip_base64 is limited to 25 MB. Send the zip with snn-bricks-upload-zip-part, or upload it to the media library and pass attachment_id.', array( 'status' => 413 ) );
            }
            $source = array( 'type' => 'base64', 'name' => 'inline.zip' );
            $debug( $source + array( 'bytes' => strlen( $bytes ), 'base64Chars' => strlen( $b64 ) ) );
            $result = SNN_Bricks_Zip_Import::extract( $bytes, $options );
    }

    if ( is_wp_error( $result ) ) {
        $debug( array( 'error' => $result ), 'error' );
        return $result;
    }
    if ( function_exists( 'snn_mcp_debug' ) ) {
        snn_mcp_debug( 'stage', array(
            'stage'      => 'zip.extracted',
            'ms'         => (int) round( ( microtime( true ) - $t0 ) * 1000 ),
            'htmlPath'   => $result['htmlPath'],
            'candidates' => $result['candidates'],
            'htmlChars'  => strlen( $result['html'] ),
            'cssChars'   => strlen( $result['css'] ),
            'report'     => $result['report'],
        ) );
    }

    $post_id = (int) ( $input['post_id'] ?? 0 );
    if ( $post_id ) {
        $perm = snn_bricks_write_permission( array( 'post_id' => $post_id ) );
        if ( $perm !== true ) {
            return is_wp_error( $perm ) ? $perm : new WP_Error( 'snn_forbidden', 'You cannot edit post ' . $post_id . ' with Bricks.', array( 'status' => 403 ) );
        }
    }

    if ( ! empty( $input['compile'] ) ) {
        if ( ! $post_id ) {
            return new WP_Error( 'snn_forbidden', 'compile=true needs a post_id you can edit with Bricks.', array( 'status' => 400 ) );
        }
        $result['build'] = SNN_Bricks_Writer::build_and_write( $post_id, array(
            'html'            => $result['html'],
            'css'             => $result['css'],
            'mode'            => $input['mode'] ?? 'append',
            'expected_digest' => $input['expected_digest'] ?? '',
            'force'           => ! empty( $input['force'] ),
            'source'          => 'mcp-zip',
        ) );
        return is_wp_error( $result['build'] ) ? $result['build'] : $result;
    }

    $session = SNN_Bricks_Import_Session::create( $result, $source, $post_id, (int) ( $input['chunk_chars'] ?? 0 ) );
    $plan    = SNN_Bricks_Import_Session::plan( $session );
    $first   = SNN_Bricks_Import_Session::chunk_payload( $session, SNN_Bricks_Import_Session::order( $session )[0], true );

    $order_note = array(
        'reordered' => 'Sections were absolutely placed and have been put in render order (by their CSS top).',
        'checked'   => 'Sections are absolutely placed and already in render order.',
        'flow'      => 'Sections are in normal flow, so source order is render order.',
        'single'    => 'The page has a single top-level block.',
        'uncertain' => 'Section order could not be confirmed (some sections are absolutely placed, some are not). Check the plan labels against the design and tell the user if the order looks wrong.',
    );
    $out = array(
        'importId'   => $session['id'],
        'htmlPath'   => $result['htmlPath'],
        'candidates' => $result['candidates'],
        'report'     => $result['report'],
        'orderNote'  => $order_note[ $result['report']['visualOrder'] ] ?? '',
        'plan'       => $plan,
        'cssChars'   => strlen( $result['css'] ),
        'firstChunk' => $first,
        'next'       => 'Convert firstChunk.markup + firstChunk.css with firstChunk.rules, then call snn-bricks-html-to-bricks with post_id, import_id "' . $session['id'] . '", chunk "' . $first['chunk'] . '", html and css. ' . ( count( $plan ) > 1 ? 'Then fetch each next pass with snn-bricks-import-chunk; every build reply names the next one.' : 'This export is a single pass.' ),
    );
    if ( $result['report']['skipped'] ) {
        $out['next'] .= ' Some assets were skipped (report.skipped): tell the user which, e.g. fonts must be added in Bricks > Custom fonts.';
    }
    if ( ( $input['return'] ?? 'session' ) === 'full' ) {
        $out['html'] = $result['html'];
        $out['css']  = $result['css'];
    }
    return $out;
}

/** Download a zip from a public URL into a temp file and unpack it. */
function snn_bricks_fetch_zip( $url, array $options ) {
    $url    = esc_url_raw( trim( $url ) );
    $scheme = wp_parse_url( $url, PHP_URL_SCHEME );
    if ( ! $url || ! in_array( $scheme, array( 'http', 'https' ), true ) || ! wp_http_validate_url( $url ) ) {
        return new WP_Error( 'snn_bad_url', 'Only public http(s) URLs are allowed.', array( 'status' => 400 ) );
    }
    require_once ABSPATH . 'wp-admin/includes/file.php';
    $tmp      = wp_tempnam( 'snn-design.zip' );
    $response = wp_safe_remote_get( $url, array(
        'timeout'             => 90,
        'redirection'         => 3,
        'stream'              => true,
        'filename'            => $tmp,
        'limit_response_size' => SNN_Bricks_Zip_Import::LIMITS['zipBytes'] + 1,
    ) );
    try {
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        if ( (int) wp_remote_retrieve_response_code( $response ) !== 200 ) {
            return new WP_Error( 'snn_fetch_failed', 'The URL returned HTTP ' . (int) wp_remote_retrieve_response_code( $response ) . '.', array( 'status' => 502 ) );
        }
        return SNN_Bricks_Zip_Import::extract_file( $tmp, $options );
    } finally {
        @unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
    }
}
