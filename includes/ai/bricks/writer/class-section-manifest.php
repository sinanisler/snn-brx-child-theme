<?php
/**
 * Section manifest: which top-level sections were built from HTML, with the
 * source HTML kept so a section can later be regenerated wholesale.
 *
 * Same post meta and row shape the builder agent uses (_snn_ai_section_manifest,
 * JSON), so sections built over MCP can be regenerated from the builder chat
 * and the other way round.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SNN_Bricks_Section_Manifest {

    const META      = '_snn_ai_section_manifest';
    const MAX_ROWS  = 60;
    const MAX_HTML  = 60000;

    public static function get( $post_id ) {
        $stored = get_post_meta( $post_id, self::META, true );
        $rows   = $stored ? json_decode( (string) $stored, true ) : array();
        return is_array( $rows ) ? $rows : array();
    }

    public static function sanitize_row( $row ) {
        if ( ! is_array( $row ) || empty( $row['rootId'] ) ) {
            return null;
        }
        $classes = array();
        if ( ! empty( $row['classes'] ) && is_array( $row['classes'] ) ) {
            $classes = array_slice( array_map( 'sanitize_html_class', $row['classes'] ), 0, 200 );
        }
        return array(
            'rootId'  => sanitize_key( $row['rootId'] ),
            'label'   => isset( $row['label'] ) ? sanitize_text_field( $row['label'] ) : 'Section',
            'html'    => isset( $row['html'] ) ? mb_substr( (string) $row['html'], 0, self::MAX_HTML ) : '',
            'prompt'  => isset( $row['prompt'] ) ? mb_substr( sanitize_textarea_field( (string) $row['prompt'] ), 0, 2000 ) : '',
            'theme'   => isset( $row['theme'] ) && is_array( $row['theme'] ) ? $row['theme'] : null,
            'classes' => $classes,
            'builtAt' => isset( $row['builtAt'] ) ? (int) $row['builtAt'] : 0,
            'source'  => isset( $row['source'] ) ? sanitize_key( $row['source'] ) : 'builder',
        );
    }

    /**
     * Upsert rows, drop rows whose root no longer exists, persist.
     *
     * @param int   $post_id
     * @param array $rows        new rows
     * @param array $elements    the saved element tree (for pruning)
     * @param array $replaced    root ids replaced by these rows
     */
    public static function record( $post_id, array $rows, array $elements, array $replaced = array() ) {
        $live = array();
        foreach ( $elements as $el ) {
            if ( ! empty( $el['id'] ) ) {
                $live[ $el['id'] ] = true;
            }
        }
        $current = array();
        foreach ( self::get( $post_id ) as $row ) {
            if ( ! empty( $row['rootId'] ) && isset( $live[ $row['rootId'] ] ) && ! in_array( $row['rootId'], $replaced, true ) ) {
                $current[ $row['rootId'] ] = $row;
            }
        }
        foreach ( $rows as $row ) {
            $row['builtAt'] = $row['builtAt'] ?? (int) round( microtime( true ) * 1000 );
            $clean          = self::sanitize_row( $row );
            if ( $clean ) {
                $current[ $clean['rootId'] ] = $clean;
            }
        }
        $out = array_slice( array_values( $current ), -self::MAX_ROWS );
        update_post_meta( $post_id, self::META, wp_slash( wp_json_encode( $out ) ) );
        return $out;
    }

    public static function find( $post_id, $root_id ) {
        foreach ( self::get( $post_id ) as $row ) {
            if ( ( $row['rootId'] ?? '' ) === $root_id ) {
                return $row;
            }
        }
        return null;
    }
}
