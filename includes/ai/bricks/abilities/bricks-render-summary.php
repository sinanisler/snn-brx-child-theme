<?php
/**
 * snn/bricks-render-summary — server-side render check for clients without a
 * browser: landmarks, heading outline, responsive CSS, CSS balance errors.
 * Uses Bricks' own renderer (Conversion::render_elements, Bricks 2.4+).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_abilities_api_init', 'snn_register_bricks_render_summary_ability' );
function snn_register_bricks_render_summary_ability() {
    wp_register_ability( 'snn/bricks-render-summary', array(
        'label'       => __( 'Bricks: Render Summary', 'snn' ),
        'description' => __( 'Render the saved Bricks page on the server and return a compact check: element count, header/nav/main/footer landmarks, heading outline, whether responsive CSS exists, CSS syntax errors and specificity risks. Use after a build when you cannot open a browser. It cannot prove visual layout; with a browser, check the permalink at desktop and 390px width.', 'snn' ),
        'category'    => 'snn-bricks',
        'input_schema' => array(
            'type'       => 'object',
            'required'   => array( 'post_id' ),
            'properties' => array(
                'post_id' => snn_bricks_post_id_schema(),
                'format'  => array( 'type' => 'string', 'enum' => array( 'summary', 'detailed' ), 'default' => 'summary', 'description' => 'detailed also returns the rendered HTML and CSS.' ),
            ),
        ),
        'output_schema' => array( 'type' => 'object' ),
        'execute_callback' => function ( $input ) {
            if ( ! class_exists( '\Bricks\Abilities\Conversion' ) ) {
                return new WP_Error( 'snn_render_unavailable', 'Server rendering needs Bricks 2.4 or newer.' );
            }
            $result = \Bricks\Abilities\Conversion::render_elements( array(
                'postId'         => (int) $input['post_id'],
                'responseFormat' => ( $input['format'] ?? 'summary' ) === 'detailed' ? 'detailed' : 'summary',
            ) );
            if ( is_wp_error( $result ) ) {
                return $result;
            }
            if ( isset( $result['html'] ) ) {
                $result['summary'] = \Bricks\Abilities\Conversion::summarize_render_output( $result['html'], $result['css'] ?? '', count( snn_bricks_page_state( (int) $input['post_id'] )['elements'] ) );
            }
            $result['permalink'] = get_permalink( (int) $input['post_id'] );
            return $result;
        },
        'permission_callback' => 'snn_bricks_read_permission',
        'meta' => array(
            'show_in_rest' => true,
            'annotations'  => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
        ),
    ) );
}
