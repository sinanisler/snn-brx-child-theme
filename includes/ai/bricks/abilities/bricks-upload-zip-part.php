<?php
/**
 * snn/bricks-upload-zip-part — send a design .zip in parts, for clients that
 * can read the file but would hit a request-size limit sending it whole (a
 * host's post_max_size, a CDN or firewall body limit).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_abilities_api_init', 'snn_register_bricks_upload_zip_part_ability' );
function snn_register_bricks_upload_zip_part_ability() {
    wp_register_ability( 'snn/bricks-upload-zip-part', array(
        'label'       => __( 'Bricks: Upload Zip Part', 'snn' ),
        'description' => __( 'Upload a design .zip in parts (base64, up to 8 MB decoded each; 2 MB parts are safest on shared hosting). Part 0 without upload_id starts an upload and returns upload_id; send the remaining parts in order with that id; resending the last part after a timeout is safe. When complete, call snn-bricks-import-zip with upload_id. Only for clients that read the file with a tool: never type file bytes out yourself. Hosted chats should ask the user to upload the zip under SNN Settings > MCP and use attachment_id instead.', 'snn' ),
        'category'    => 'snn-bricks',
        'input_schema' => array(
            'type'       => 'object',
            'required'   => array( 'part', 'total_parts', 'data_base64' ),
            'properties' => array(
                'upload_id'   => array( 'type' => 'string', 'description' => 'From the reply to part 0.' ),
                'part'        => array( 'type' => 'integer', 'minimum' => 0 ),
                'total_parts' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 200 ),
                'data_base64' => array( 'type' => 'string' ),
            ),
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'uploadId' => array( 'type' => 'string' ),
                'received' => array( 'type' => 'integer' ),
                'complete' => array( 'type' => 'boolean' ),
                'next'     => array( 'type' => 'string' ),
            ),
        ),
        'execute_callback' => function ( $input ) {
            $bytes = base64_decode( preg_replace( '/\s+/', '', (string) $input['data_base64'] ), true );
            if ( $bytes === false || $bytes === '' ) {
                return new WP_Error( 'snn_bad_base64', 'data_base64 could not be decoded.', array( 'status' => 400 ) );
            }
            return SNN_Bricks_Import_Session::append_part( (string) ( $input['upload_id'] ?? '' ), (int) $input['part'], (int) $input['total_parts'], $bytes );
        },
        'permission_callback' => function () {
            return current_user_can( 'upload_files' ) && current_user_can( 'edit_posts' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'annotations'  => array( 'readonly' => false, 'destructive' => false, 'idempotent' => true ),
        ),
    ) );
}
