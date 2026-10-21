<?php
/**
 * snn/bricks-get-page-tree — the page as an indented outline, plus everything a
 * client needs before writing: documentDigest, section order, which sections
 * were built from HTML (and can be regenerated), and the builder lock state.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_abilities_api_init', 'snn_register_bricks_get_page_tree_ability' );
function snn_register_bricks_get_page_tree_ability() {
    wp_register_ability( 'snn/bricks-get-page-tree', array(
        'label'       => __( 'Bricks: Get Page Tree', 'snn' ),
        'description' => __( 'Read a Bricks page: an indented outline of elements ([id] type .classes "text"), the top-level section order, sections built from HTML (with source retained, so they can be regenerated), the documentDigest to pass to write tools as expected_digest, and whether the page is open in the builder. Pass element_id for one element\'s full settings.', 'snn' ),
        'category'    => 'snn-bricks',
        'input_schema' => array(
            'type'       => 'object',
            'required'   => array( 'post_id' ),
            'properties' => array(
                'post_id'      => snn_bricks_post_id_schema(),
                'element_id'   => array( 'type' => 'string', 'description' => 'Return this element in full (settings, classes, children) instead of the outline.' ),
                'root_id'      => array( 'type' => 'string', 'description' => 'Only the subtree under this element.' ),
                'max_elements' => array( 'type' => 'integer', 'default' => 300, 'minimum' => 10, 'maximum' => 2000 ),
                'include_raw'  => array( 'type' => 'boolean', 'default' => false, 'description' => 'Also return the raw Bricks element array.' ),
            ),
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'postId'         => array( 'type' => 'integer' ),
                'title'          => array( 'type' => 'string' ),
                'area'           => array( 'type' => 'string' ),
                'documentDigest' => array( 'type' => 'string' ),
                'elementCount'   => array( 'type' => 'integer' ),
                'outline'        => array( 'type' => 'string' ),
                'sections'       => array( 'type' => 'array' ),
                'lockedBy'       => array( 'type' => array( 'integer', 'null' ) ),
            ),
        ),
        'execute_callback'    => 'snn_bricks_get_page_tree',
        'permission_callback' => 'snn_bricks_read_permission',
        'meta' => array(
            'show_in_rest' => true,
            'annotations'  => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
        ),
    ) );
}

function snn_bricks_get_page_tree( $input ) {
    $post_id = (int) $input['post_id'];
    $post    = get_post( $post_id );
    if ( ! $post ) {
        return new WP_Error( 'snn_invalid_post', 'Post not found.' );
    }
    $state   = snn_bricks_page_state( $post_id );
    $classes = SNN_Bricks_Global_Classes_Store::read_classes();
    $ops     = new SNN_Bricks_Edit_Ops( $post_id, $state['elements'], $classes );
    $by_id   = array();
    foreach ( $state['elements'] as $el ) {
        $by_id[ $el['id'] ] = $el;
    }

    if ( ! empty( $input['element_id'] ) ) {
        $id = (string) $input['element_id'];
        if ( ! isset( $by_id[ $id ] ) ) {
            return new WP_Error( 'snn_element_not_found', 'No element [' . $id . '] on this page.' );
        }
        $el = $by_id[ $id ];
        return array(
            'postId'         => $post_id,
            'documentDigest' => $state['documentDigest'],
            'element'        => array(
                'id'       => $el['id'],
                'name'     => $el['name'],
                'parent'   => $el['parent'] ?? 0,
                'children' => $ops->children_of( $id ),
                'classes'  => $ops->class_names_of( $el ),
                'settings' => $el['settings'] ?? (object) array(),
            ),
        );
    }

    $manifest = array();
    foreach ( SNN_Bricks_Section_Manifest::get( $post_id ) as $row ) {
        $manifest[ $row['rootId'] ?? '' ] = $row;
    }

    $max   = max( 10, min( 2000, (int) ( $input['max_elements'] ?? 300 ) ) );
    $roots = array();
    if ( ! empty( $input['root_id'] ) ) {
        if ( ! isset( $by_id[ $input['root_id'] ] ) ) {
            return new WP_Error( 'snn_element_not_found', 'No element [' . $input['root_id'] . '] on this page.' );
        }
        $roots = array( (string) $input['root_id'] );
    } else {
        foreach ( $state['elements'] as $el ) {
            $p = $el['parent'] ?? 0;
            if ( $p === 0 || $p === '0' || ! isset( $by_id[ $p ] ) ) {
                $roots[] = $el['id'];
            }
        }
    }
    list( $lines, $truncated ) = SNN_Bricks_Feedback::outline( $state['elements'], $ops, $roots, $max );
    if ( $truncated ) {
        $lines[] = '(outline truncated at ' . $max . ' elements — use root_id or element_id for detail)';
    }

    $sections = array();
    foreach ( $roots as $i => $r ) {
        $el    = $by_id[ $r ];
        $m     = $manifest[ $r ] ?? null;
        $label = $m ? $m['label'] : ( SNN_Bricks_Edit_Ops::text_of( $el, 40 ) ?: $el['name'] );
        if ( ! $m && $el['name'] === 'custom-html-css-script' && preg_match( '/^\s*<style\b/i', (string) ( $el['settings']['content'] ?? '' ) ) ) {
            $label = 'Page CSS (fonts, :root variables, tag rules)';
        }
        $sections[] = array(
            'position'      => $i,
            'id'            => $r,
            'name'          => $el['name'],
            'label'         => $label,
            'regenerable'   => $m && ! empty( $m['html'] ),
            'sourceHtmlKb'  => $m ? round( strlen( (string) $m['html'] ) / 1024, 1 ) : 0,
        );
    }

    $locked = SNN_Bricks_Write_Guard::lock_holder( $post_id );
    $out    = array(
        'postId'         => $post_id,
        'title'          => get_the_title( $post_id ),
        'postType'       => $post->post_type,
        'status'         => $post->post_status,
        'area'           => $state['area'],
        'documentDigest' => $state['documentDigest'],
        'elementCount'   => count( $state['elements'] ),
        'outline'        => $lines ? implode( "\n", $lines ) : 'Page is empty.',
        'sections'       => $sections,
        'lockedBy'       => $locked ? $locked : null,
        'lockedBySelf'   => $locked && $locked === get_current_user_id(),
        'permalink'      => get_permalink( $post_id ),
    );
    if ( ! empty( $input['include_raw'] ) ) {
        $out['elements'] = $state['elements'];
    }
    return $out;
}
