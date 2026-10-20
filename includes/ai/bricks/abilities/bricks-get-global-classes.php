<?php
/**
 * snn/bricks-get-global-classes — the site's Bricks global classes and their CSS.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_abilities_api_init', 'snn_register_bricks_get_global_classes_ability' );
function snn_register_bricks_get_global_classes_ability() {
    wp_register_ability( 'snn/bricks-get-global-classes', array(
        'label'       => __( 'Bricks: Get Global Classes', 'snn' ),
        'description' => __( 'List Bricks global classes with their CSS. Filter by name substring, or by the classes used on one page (post_id). Restyle elements by editing these classes (update_class in snn-bricks-edit-elements), not by inlining settings. Also returns colour palette swatches and global variables.', 'snn' ),
        'category'    => 'snn-bricks',
        'input_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'filter'  => array( 'type' => 'string' ),
                'post_id' => array( 'type' => 'integer', 'description' => 'Only classes used on this page.' ),
                'limit'   => array( 'type' => 'integer', 'default' => 50, 'minimum' => 1, 'maximum' => 500 ),
            ),
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'classes'   => array( 'type' => 'array' ),
                'total'     => array( 'type' => 'integer' ),
                'colors'    => array( 'type' => 'array' ),
                'variables' => array( 'type' => 'array' ),
            ),
        ),
        'execute_callback' => function ( $input ) {
            $classes = SNN_Bricks_Global_Classes_Store::read_classes();
            $used    = null;
            if ( ! empty( $input['post_id'] ) ) {
                $post_id = (int) $input['post_id'];
                if ( ! current_user_can( 'edit_post', $post_id ) ) {
                    return new WP_Error( 'snn_forbidden', 'You cannot read this post.' );
                }
                $used = array();
                foreach ( snn_bricks_page_state( $post_id )['elements'] as $el ) {
                    foreach ( (array) ( $el['settings']['_cssGlobalClasses'] ?? array() ) as $cid ) {
                        $used[ $cid ] = true;
                    }
                }
            }
            $filter = strtolower( ltrim( (string) ( $input['filter'] ?? '' ), '.' ) );
            $out    = array();
            foreach ( $classes as $gc ) {
                if ( $used !== null && ! isset( $used[ $gc['id'] ?? '' ] ) ) {
                    continue;
                }
                if ( $filter !== '' && strpos( strtolower( (string) ( $gc['name'] ?? '' ) ), $filter ) === false ) {
                    continue;
                }
                $out[] = array(
                    'id'   => $gc['id'] ?? '',
                    'name' => $gc['name'] ?? '',
                    'css'  => (string) ( $gc['settings']['_cssCustom'] ?? '' ),
                    'hasNativeSettings' => count( array_diff( array_keys( (array) ( $gc['settings'] ?? array() ) ), array( '_cssCustom' ) ) ) > 0,
                );
            }
            $limit = max( 1, min( 500, (int) ( $input['limit'] ?? 50 ) ) );

            $colors = array();
            foreach ( (array) get_option( defined( 'BRICKS_DB_COLOR_PALETTE' ) ? BRICKS_DB_COLOR_PALETTE : 'bricks_color_palette', array() ) as $palette ) {
                foreach ( (array) ( $palette['colors'] ?? array() ) as $c ) {
                    $colors[] = array( 'palette' => $palette['name'] ?? '', 'name' => $c['name'] ?? '', 'raw' => $c['raw'] ?? '', 'value' => $c['light'] ?? ( $c['hex'] ?? '' ) );
                }
            }
            $variables = array();
            foreach ( (array) get_option( defined( 'BRICKS_DB_GLOBAL_VARIABLES' ) ? BRICKS_DB_GLOBAL_VARIABLES : 'bricks_global_variables', array() ) as $v ) {
                $variables[] = array( 'name' => $v['name'] ?? '', 'value' => $v['value'] ?? '' );
            }

            return array(
                'classes'   => array_slice( $out, 0, $limit ),
                'total'     => count( $out ),
                'colors'    => array_slice( $colors, 0, 200 ),
                'variables' => array_slice( $variables, 0, 200 ),
            );
        },
        'permission_callback' => function () {
            return current_user_can( 'edit_posts' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'annotations'  => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
        ),
    ) );
}
