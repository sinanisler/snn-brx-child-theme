<?php
/**
 * snn/bricks-import-zip — unpack a design-export .zip on the server.
 *
 * Raster assets go to the media library, SVG assets are inlined, local
 * stylesheets are inlined, every reference is rewritten. Returns the cleaned
 * HTML body and CSS. Design-tool exports are canvas markup (absolute
 * positioning, generated class names), so the client should rewrite them per
 * the snn-bricks-start-here rules before calling snn-bricks-html-to-bricks —
 * or pass compile=true when the export is already clean semantic HTML.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_abilities_api_init', 'snn_register_bricks_import_zip_ability' );
function snn_register_bricks_import_zip_ability() {
    $props = array_merge(
        array(
            'zip_base64' => array( 'type' => 'string', 'description' => 'The .zip file, base64 (max 25 MB decoded).' ),
            'html_path'  => array( 'type' => 'string', 'description' => 'Which HTML file inside the zip to use (default: index.html, else the largest page). candidates lists them.' ),
            'compile'    => array( 'type' => 'boolean', 'default' => false, 'description' => 'Also build the result straight into post_id (only for exports that are already clean semantic HTML).' ),
            'post_id'    => array( 'type' => 'integer', 'description' => 'Target page when compile=true.' ),
            'mode'       => array( 'type' => 'string', 'enum' => SNN_Bricks_Writer::MODES, 'default' => 'append' ),
        ),
        snn_bricks_write_input_properties()
    );

    wp_register_ability( 'snn/bricks-import-zip', array(
        'label'       => __( 'Bricks: Import Design Zip', 'snn' ),
        'description' => __( 'Unpack a design-export .zip (Figma/Penpot/Webflow "export to HTML"): images are uploaded to the media library, SVGs inlined, stylesheets inlined, references rewritten. Returns { html, css, candidates, report }. Then rewrite that markup into clean sections following snn-bricks-start-here and send it to snn-bricks-html-to-bricks. Sections are NOT reordered by visual position — check the order yourself.', 'snn' ),
        'category'    => 'snn-bricks',
        'input_schema' => array(
            'type'       => 'object',
            'required'   => array( 'zip_base64' ),
            'properties' => $props,
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'htmlPath'   => array( 'type' => 'string' ),
                'candidates' => array( 'type' => 'array' ),
                'html'       => array( 'type' => 'string' ),
                'css'        => array( 'type' => 'string' ),
                'report'     => array( 'type' => 'object' ),
                'build'      => array( 'type' => 'object' ),
            ),
        ),
        'execute_callback' => function ( $input ) {
            return snn_bricks_with_idempotency( $input, function () use ( $input ) {
                $b64   = preg_replace( '/^data:[^;,]+;base64,/i', '', (string) $input['zip_base64'] );
                $bytes = base64_decode( $b64, true );
                if ( $bytes === false || $bytes === '' ) {
                    return new WP_Error( 'snn_bad_base64', 'zip_base64 could not be decoded.' );
                }
                $result = SNN_Bricks_Zip_Import::extract( $bytes, array( 'html_path' => $input['html_path'] ?? '' ) );
                if ( is_wp_error( $result ) ) {
                    return $result;
                }
                if ( ! empty( $input['compile'] ) ) {
                    $post_id = (int) ( $input['post_id'] ?? 0 );
                    $perm    = snn_bricks_write_permission( array( 'post_id' => $post_id ) );
                    if ( ! $post_id || $perm !== true ) {
                        return is_wp_error( $perm ) ? $perm : new WP_Error( 'snn_forbidden', 'compile=true needs a post_id you can edit with Bricks.' );
                    }
                    $result['build'] = SNN_Bricks_Writer::build_and_write( $post_id, array(
                        'html'            => $result['html'],
                        'css'             => $result['css'],
                        'mode'            => $input['mode'] ?? 'append',
                        'expected_digest' => $input['expected_digest'] ?? '',
                        'force'           => ! empty( $input['force'] ),
                        'source'          => 'mcp-zip',
                    ) );
                    if ( is_wp_error( $result['build'] ) ) {
                        return $result['build'];
                    }
                }
                return $result;
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
