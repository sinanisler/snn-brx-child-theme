<?php
/**
 * snn/bricks-find-elements — locate elements by text, class or type.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_abilities_api_init', 'snn_register_bricks_find_elements_ability' );
function snn_register_bricks_find_elements_ability() {
    wp_register_ability( 'snn/bricks-find-elements', array(
        'label'       => __( 'Bricks: Find Elements', 'snn' ),
        'description' => __( 'Find elements on a Bricks page by visible text (partial, case-insensitive), global class name and/or element type (heading, text-basic, button, image, section, block ...). Returns up to 30 matches with id, type, classes and text, to use as targets in snn-bricks-edit-elements.', 'snn' ),
        'category'    => 'snn-bricks',
        'input_schema' => array(
            'type'       => 'object',
            'required'   => array( 'post_id' ),
            'properties' => array(
                'post_id' => snn_bricks_post_id_schema(),
                'text'    => array( 'type' => 'string' ),
                'class'   => array( 'type' => 'string' ),
                'type'    => array( 'type' => 'string' ),
                'within'  => array( 'type' => 'string', 'description' => 'Only descendants of this element id.' ),
            ),
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'matches'        => array( 'type' => 'array' ),
                'total'          => array( 'type' => 'integer' ),
                'documentDigest' => array( 'type' => 'string' ),
            ),
        ),
        'execute_callback' => function ( $input ) {
            $post_id = (int) $input['post_id'];
            $state   = snn_bricks_page_state( $post_id );
            $ops     = new SNN_Bricks_Edit_Ops( $post_id, $state['elements'], SNN_Bricks_Global_Classes_Store::read_classes() );

            $allowed = null;
            if ( ! empty( $input['within'] ) ) {
                $allowed = array_flip( $ops->descendants_of( (string) $input['within'] ) );
            }
            $text  = isset( $input['text'] ) ? mb_strtolower( (string) $input['text'] ) : '';
            $class = isset( $input['class'] ) ? ltrim( (string) $input['class'], '.' ) : '';
            $type  = isset( $input['type'] ) ? (string) $input['type'] : '';

            $matches = array();
            foreach ( $state['elements'] as $el ) {
                if ( $allowed !== null && ! isset( $allowed[ $el['id'] ] ) ) {
                    continue;
                }
                if ( $text !== '' && mb_strpos( mb_strtolower( SNN_Bricks_Edit_Ops::text_of( $el, 400 ) ), $text ) === false ) {
                    continue;
                }
                $names = $ops->class_names_of( $el );
                if ( $class !== '' && ! in_array( $class, $names, true ) ) {
                    continue;
                }
                if ( $type !== '' && ( $el['name'] ?? '' ) !== $type ) {
                    continue;
                }
                $matches[] = array(
                    'id'      => $el['id'],
                    'name'    => $el['name'],
                    'parent'  => $el['parent'] ?? 0,
                    'classes' => $names,
                    'text'    => SNN_Bricks_Edit_Ops::text_of( $el, 80 ),
                );
            }
            return array(
                'matches'        => array_slice( $matches, 0, 30 ),
                'total'          => count( $matches ),
                'documentDigest' => $state['documentDigest'],
            );
        },
        'permission_callback' => 'snn_bricks_read_permission',
        'meta' => array(
            'show_in_rest' => true,
            'annotations'  => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
        ),
    ) );
}
