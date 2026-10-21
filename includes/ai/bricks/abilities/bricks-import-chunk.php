<?php
/**
 * snn/bricks-import-chunk — one conversion pass of a design-zip import.
 *
 * Returns the pass's markup, only the CSS it uses, the class prefix and the
 * conversion rules, like the builder agent's convertChunk(). split=true halves
 * a pass that is too big to convert in one reply (the builder's bisect()).
 * Without chunk it returns the import's progress.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_abilities_api_init', 'snn_register_bricks_import_chunk_ability' );
function snn_register_bricks_import_chunk_ability() {
    wp_register_ability( 'snn/bricks-import-chunk', array(
        'label'       => __( 'Bricks: Import Chunk', 'snn' ),
        'description' => __( 'Get one conversion pass of a design-zip import started with snn-bricks-import-zip: its markup, the CSS it uses, the class prefix and the conversion rules. Convert it and send it to snn-bricks-html-to-bricks with import_id and chunk. If the converted pass would be too long for one reply, call again with split=true: the pass is halved into "<chunk>.0" and "<chunk>.1". Without chunk, returns the progress (built / pending passes).', 'snn' ),
        'category'    => 'snn-bricks',
        'input_schema' => array(
            'type'       => 'object',
            'required'   => array( 'import_id' ),
            'properties' => array(
                'import_id'     => array( 'type' => 'string' ),
                'chunk'         => array( 'type' => 'string', 'description' => 'Pass id from the plan, e.g. "0", "3", "3.1". Omit for progress only.' ),
                'split'         => array( 'type' => 'boolean', 'default' => false, 'description' => 'Halve this pass and return the first half.' ),
                'include_rules' => array( 'type' => 'boolean', 'default' => false, 'description' => 'Repeat the full conversion rules (they come with the first pass).' ),
            ),
        ),
        'output_schema' => array( 'type' => 'object' ),
        'execute_callback' => function ( $input ) {
            $s = SNN_Bricks_Import_Session::load( $input['import_id'] );
            if ( is_wp_error( $s ) ) {
                return $s;
            }
            $progress = SNN_Bricks_Import_Session::progress( $s );
            if ( ! isset( $input['chunk'] ) || $input['chunk'] === '' ) {
                return array(
                    'progress' => $progress,
                    'plan'     => SNN_Bricks_Import_Session::plan( $s ),
                    'postId'   => $s['postId'],
                    'next'     => $progress['nextChunk'] !== null
                        ? 'Fetch chunk "' . $progress['nextChunk'] . '".'
                        : 'Every pass is built. Verify with snn-bricks-render-summary or open the page.',
                );
            }
            $chunk = (string) $input['chunk'];
            if ( ! empty( $input['split'] ) ) {
                $ids = SNN_Bricks_Import_Session::split( $s, $chunk );
                if ( is_wp_error( $ids ) ) {
                    return $ids;
                }
                $out          = SNN_Bricks_Import_Session::chunk_payload( $s, $ids[0], ! empty( $input['include_rules'] ) );
                $out['split'] = array( 'from' => $chunk, 'into' => $ids, 'note' => 'Build "' . $ids[0] . '" now; fetch "' . $ids[1] . '" after it.' );
            } else {
                $out = SNN_Bricks_Import_Session::chunk_payload( $s, $chunk, ! empty( $input['include_rules'] ) );
            }
            if ( is_wp_error( $out ) ) {
                return $out;
            }
            $out['progress'] = SNN_Bricks_Import_Session::progress( $s );
            return $out;
        },
        'permission_callback' => function () {
            return current_user_can( 'edit_posts' );
        },
        'meta' => array(
            'show_in_rest' => true,
            // Only the server-side session changes (split), never the site.
            'annotations'  => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
        ),
    ) );
}
