<?php
/**
 * snn/bricks-start-here — the rules and workflow for building Bricks pages.
 * Read-only; MCP clients call it first.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_abilities_api_init', 'snn_register_bricks_start_here_ability' );
function snn_register_bricks_start_here_ability() {
    wp_register_ability( 'snn/bricks-start-here', array(
        'label'       => __( 'Bricks: Start Here', 'snn' ),
        'description' => __( 'Call this FIRST before building or editing Bricks pages. Returns the HTML authoring rules (data-bricks hints, section > container > content structure, class-based CSS, responsive breakpoints of this site, how to clean design exports, how to build from a zip you cannot send) and the recommended order of tool calls. Always build the page: never stop at an empty draft waiting for the user.', 'snn' ),
        'category'    => 'snn-bricks',
        'input_schema' => array(
            'type'       => 'object',
            'properties' => (object) array(),
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'rules'       => array( 'type' => 'string' ),
                'breakpoints' => array( 'type' => 'array' ),
                'bricks'      => array( 'type' => 'object' ),
            ),
        ),
        'execute_callback' => function () {
            $rules = (string) require dirname( __DIR__ ) . '/rules/html-rules.php';
            $plan  = SNN_Bricks_Native_Layout::breakpoint_plan();

            $breakpoints = array( array( 'key' => $plan['base']['key'], 'width' => $plan['base']['width'], 'base' => true ) );
            foreach ( $plan['steps'] as $s ) {
                $breakpoints[] = array( 'key' => $s['key'], 'width' => $s['width'] );
            }
            $media = array();
            foreach ( $plan['steps'] as $s ) {
                $media[] = $plan['mobileFirst'] ? '@media (min-width: ' . $s['width'] . 'px)' : '@media (max-width: ' . $s['width'] . 'px)';
            }
            $rules .= "\n\n## This site's breakpoints\n\n" . ( $plan['mobileFirst'] ? 'Mobile-first. ' : 'Desktop-first. ' )
                . 'Write responsive CSS with exactly these queries so they map onto Bricks breakpoints: ' . implode( ', ', $media ) . '.';

            return array(
                'rules'       => $rules,
                'breakpoints' => $breakpoints,
                'bricks'      => array(
                    'active'          => class_exists( '\Bricks\Database' ),
                    'version'         => defined( 'BRICKS_VERSION' ) ? BRICKS_VERSION : null,
                    'savePipeline'    => class_exists( '\Bricks\Abilities\Save_Pipeline' ),
                    'canUploadSvg'    => SNN_Bricks_Assets::can_upload_svg(),
                    'html5Parser'     => SNN_Bricks_Dom::has_html5(),
                ),
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
