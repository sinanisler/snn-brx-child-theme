<?php
/**
 * snn/bricks-edit-elements — the Bricks AI agent's edit vocabulary, server side.
 * All ops apply in memory; the page is saved once (one revision), or not at all.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_abilities_api_init', 'snn_register_bricks_edit_elements_ability' );
function snn_register_bricks_edit_elements_ability() {
    $spec = implode( "\n", array(
        '{"op":"set_text","target":{"id":"abcdef"},"text":"New heading"}',
        '{"op":"set_image","target":{"id":"abcdef"},"url":"https://...","background":false}',
        '{"op":"set_icon","target":{"id":"abcdef"},"icon":"fas fa-arrow-right","position":"right","size":"18px","color":"#ffffff"}',
        '{"op":"set_settings","target":{"id":"abcdef"},"settings":{"_padding":{"top":"40"}}}  (null or "" deletes a key)',
        '{"op":"update_class","class":"hero-title","declarations":{"font-size":"3.5rem","color":"#111"}}  (or "css" to replace the whole class CSS)',
        '{"op":"delete","target":{"id":"abcdef"}}',
        '{"op":"move","target":{"id":"abcdef"},"before":{"id":"ghijkl"}}  (or "after", or "parent" + "index")',
        '{"op":"duplicate","target":{"id":"abcdef"}}',
        '{"op":"insert","parent":{"id":"abcdef"},"index":1,"name":"heading","text":"Hello","classes":["hero-title"]}',
        '{"op":"wrap","targets":[{"id":"a"},{"id":"b"}],"name":"block","classes":["row"]}',
        '{"op":"unwrap","target":{"id":"abcdef"}}',
    ) );

    $props = array_merge(
        array(
            'post_id' => snn_bricks_post_id_schema(),
            'ops'     => array(
                'type'        => 'array',
                'minItems'    => 1,
                'maxItems'    => 100,
                'items'       => array( 'type' => 'object' ),
                'description' => "Operations, applied in order. Targets: {\"id\":\"abcdef\"} (preferred), {\"text\":\"partial\"}, {\"class\":\"x\"}, {\"type\":\"heading\",\"index\":0}, {\"within\":{...}}. An ambiguous target fails with candidates.\n" . $spec,
            ),
            'dry_run' => array( 'type' => 'boolean', 'default' => false, 'description' => 'Validate and report what would change without saving.' ),
            'verify'  => array( 'type' => 'boolean', 'default' => false, 'description' => 'Also run Bricks\' server render on the result (feedback.render).' ),
        ),
        snn_bricks_write_input_properties()
    );

    wp_register_ability( 'snn/bricks-edit-elements', array(
        'label'       => __( 'Bricks: Edit Elements', 'snn' ),
        'description' => __( 'Edit a Bricks page with operations: set_text, set_image, set_icon, set_settings, update_class, delete, move, duplicate, insert, wrap, unwrap. All-or-nothing: one failing op leaves the page untouched. Every reply (dry_run too) has feedback: which elements changed and which settings, the outline of the touched sections, checks and the next step. Get ids from snn-bricks-get-page-tree or snn-bricks-find-elements and pass its documentDigest as expected_digest. Style changes belong in update_class (classes are shared), content changes in element ops. update_class on a layout property re-derives native layout on sections built from HTML.', 'snn' ),
        'category'    => 'snn-bricks',
        'input_schema' => array(
            'type'       => 'object',
            'required'   => array( 'post_id', 'ops' ),
            'properties' => $props,
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'applied'        => array( 'type' => 'array' ),
                'newIds'         => array( 'type' => 'array' ),
                'revisionId'     => array( 'type' => array( 'integer', 'null' ) ),
                'documentDigest' => array( 'type' => 'string' ),
            ),
        ),
        'execute_callback' => function ( $input ) {
            $run = function () use ( $input ) {
                $post_id = (int) $input['post_id'];
                $target  = SNN_Bricks_Writer::check_target( $post_id );
                if ( is_wp_error( $target ) ) {
                    return $target;
                }
                $state   = snn_bricks_page_state( $post_id );
                $dry_run = ! empty( $input['dry_run'] );
                if ( ! $dry_run ) {
                    $guard = SNN_Bricks_Write_Guard::check( $post_id, $state['area'], (string) ( $input['expected_digest'] ?? '' ), ! empty( $input['force'] ) );
                    if ( is_wp_error( $guard ) ) {
                        return $guard;
                    }
                }

                $ops    = new SNN_Bricks_Edit_Ops( $post_id, $state['elements'], SNN_Bricks_Global_Classes_Store::read_classes() );
                $result = $ops->apply( (array) $input['ops'] );
                if ( empty( $result['success'] ) ) {
                    return new WP_Error( 'snn_edit_failed', $result['error'], array(
                        'status'     => 422,
                        'failedOp'   => $result['failedOp'] ?? null,
                        'candidates' => $result['candidates'] ?? null,
                    ) );
                }

                $out = array(
                    'postId'               => $post_id,
                    'dryRun'               => $dry_run,
                    'applied'              => $result['applied'],
                    'newIds'               => $result['newIds'],
                    'documentDigestBefore' => $state['documentDigest'],
                );
                $out['classChanges'] = array_keys( $ops->class_writes() );
                if ( $dry_run ) {
                    $out['feedback'] = snn_bricks_edit_feedback( $post_id, $state['elements'], $ops, false, false );
                    return $out;
                }

                $classes_before = SNN_Bricks_Global_Classes_Store::read_classes();
                $new_classes    = $ops->new_classes();
                if ( $new_classes ) {
                    $created = SNN_Bricks_Global_Classes_Store::apply( $new_classes );
                    if ( is_wp_error( $created ) ) {
                        return $created;
                    }
                }
                foreach ( $ops->class_writes() as $name => $css ) {
                    $w = SNN_Bricks_Global_Classes_Store::set_class_css( $name, $css );
                    if ( is_wp_error( $w ) ) {
                        update_option( SNN_Bricks_Global_Classes_Store::classes_option(), $classes_before );
                        return $w;
                    }
                }

                $out['revisionId']     = null;
                $out['documentDigest'] = $state['documentDigest'];
                if ( $result['contentChanged'] ) {
                    $saved = SNN_Bricks_Writer::save( $post_id, $state['area'], $ops->content(), $state['documentDigest'] );
                    if ( is_wp_error( $saved ) ) {
                        update_option( SNN_Bricks_Global_Classes_Store::classes_option(), $classes_before );
                        return $saved;
                    }
                    $out['revisionId']     = $saved['revisionId'];
                    $out['documentDigest'] = $saved['documentDigest'];
                    $out['changed']        = $saved['changed'];
                } else {
                    // A class-only change still needs the post CSS rebuilt.
                    wp_update_post( array( 'ID' => $post_id, 'post_modified' => current_time( 'mysql' ) ) );
                    $out['changed'] = true;
                }
                $out['feedback'] = snn_bricks_edit_feedback( $post_id, $state['elements'], $ops, true, ! empty( $input['verify'] ) );
                return $out;
            };
            return ! empty( $input['dry_run'] ) ? $run() : snn_bricks_with_idempotency( $input, $run );
        },
        'permission_callback' => 'snn_bricks_write_permission',
        'meta' => array(
            'show_in_rest' => true,
            'annotations'  => array( 'readonly' => false, 'destructive' => true, 'idempotent' => false ),
        ),
    ) );
}

/**
 * Feedback for an edit. A class edit changes no element, so the sections that
 * use the edited classes are outlined instead.
 */
function snn_bricks_edit_feedback( $post_id, array $before, SNN_Bricks_Edit_Ops $ops, $saved, $verify ) {
    $after = $ops->content();
    $focus = array();
    $names = array_keys( $ops->class_writes() );
    if ( $names ) {
        foreach ( $after as $el ) {
            if ( array_intersect( $names, $ops->class_names_of( $el ) ) ) {
                $root = SNN_Bricks_Feedback::section_of( $after, $el['id'] );
                if ( $root !== null && ! in_array( $root, $focus, true ) ) {
                    $focus[] = $root;
                }
            }
        }
    }
    if ( $focus ) {
        foreach ( SNN_Bricks_Feedback::diff( $before, $after )['changed'] as $row ) {
            $root = SNN_Bricks_Feedback::section_of( $after, $row['id'] );
            if ( $root !== null && ! in_array( $root, $focus, true ) ) {
                $focus[] = $root;
            }
        }
    }
    $report = SNN_Bricks_Feedback::report( $post_id, $before, $after, array( 'focus' => array_slice( $focus, 0, 6 ), 'verify' => $verify, 'saved' => $saved ) );
    if ( $names ) {
        $report['classesChanged'] = $names;
    }
    return $report;
}
