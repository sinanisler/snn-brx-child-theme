<?php
/**
 * snn/bricks-upload-asset — put an image or SVG in the media library and get
 * its URL back, for use in HTML sent to snn-bricks-html-to-bricks.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_abilities_api_init', 'snn_register_bricks_upload_asset_ability' );
function snn_register_bricks_upload_asset_ability() {
    wp_register_ability( 'snn/bricks-upload-asset', array(
        'label'       => __( 'Bricks: Upload Asset', 'snn' ),
        'description' => __( 'Upload one image to the WordPress media library and return its URL. Give exactly one source: url (public http/https, sideloaded; private and loopback hosts are refused), base64 + filename (png, jpg, gif, webp, avif, svg; max 10 MB, SVG max 64 KB), or svg (inline SVG markup). SVGs are sanitized and need Bricks SVG upload permission. Use the returned url in <img src> or CSS url() — Bricks then links the image to the library.', 'snn' ),
        'category'    => 'snn-bricks',
        'input_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'url'      => array( 'type' => 'string' ),
                'base64'   => array( 'type' => 'string', 'description' => 'File bytes, base64 (a data: URI prefix is accepted).' ),
                'filename' => array( 'type' => 'string', 'description' => 'Required with base64, e.g. "hero.jpg".' ),
                'svg'      => array( 'type' => 'string', 'description' => 'Inline SVG markup.' ),
                'alt'      => array( 'type' => 'string' ),
            ),
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'id'       => array( 'type' => 'integer' ),
                'url'      => array( 'type' => 'string' ),
                'filename' => array( 'type' => 'string' ),
            ),
        ),
        'execute_callback' => function ( $input ) {
            $sources = array_filter( array( ! empty( $input['url'] ), ! empty( $input['base64'] ), ! empty( $input['svg'] ) ) );
            if ( count( $sources ) !== 1 ) {
                return new WP_Error( 'snn_bad_source', 'Give exactly one of url, base64 (+ filename) or svg.' );
            }
            $alt = (string) ( $input['alt'] ?? '' );
            if ( ! empty( $input['url'] ) ) {
                return SNN_Bricks_Assets::store_from_url( $input['url'], $alt );
            }
            if ( ! empty( $input['svg'] ) ) {
                return SNN_Bricks_Assets::store_svg( $input['svg'], $input['filename'] ?? 'icon.svg' );
            }
            if ( empty( $input['filename'] ) ) {
                return new WP_Error( 'snn_missing_filename', 'filename is required with base64.' );
            }
            $b64   = preg_replace( '/^data:[^;,]+;base64,/i', '', (string) $input['base64'] );
            $bytes = base64_decode( $b64, true );
            if ( $bytes === false ) {
                return new WP_Error( 'snn_bad_base64', 'base64 could not be decoded.' );
            }
            if ( preg_match( '/\.svg$/i', $input['filename'] ) ) {
                return SNN_Bricks_Assets::store_svg( $bytes, $input['filename'] );
            }
            return SNN_Bricks_Assets::store_raster( $bytes, $input['filename'], $alt );
        },
        'permission_callback' => function () {
            return current_user_can( 'upload_files' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'annotations'  => array( 'readonly' => false, 'destructive' => false, 'idempotent' => false ),
        ),
    ) );
}
