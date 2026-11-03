<?php
/**
 * snn/bricks-element-schema: what this Bricks site can place and what each element accepts.
 *
 * Read-only. With no names it lists element types (filter by search or category) and the
 * conventions the HTML converter understands. With names it returns those elements' settings,
 * read from Bricks' own schema: key, type, label and, for selects, the allowed values.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_abilities_api_init', 'snn_register_bricks_element_schema_ability' );
function snn_register_bricks_element_schema_ability() {
    wp_register_ability( 'snn/bricks-element-schema', array(
        'label'       => __( 'Bricks: Element Schema', 'snn' ),
        'description' => __( 'What this Bricks site can place and what each element accepts. Without names: the element types (filter with search or category) and the HTML conventions the converter understands. With names (for example ["icon-box","video"]): each element\'s settings as Bricks defines them (key, type, label, allowed values). Call it before writing an element whose settings you are not sure of.', 'snn' ),
        'category'    => 'snn-bricks',
        'input_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'names'         => array(
                    'type'        => 'array',
                    'items'       => array( 'type' => 'string' ),
                    'maxItems'    => 20,
                    'description' => 'Element type names to describe, for example ["icon-box", "counter"].',
                ),
                'search'        => array( 'type' => 'string', 'description' => 'Without names: element types whose name or label contains this text.' ),
                'category'      => array( 'type' => 'string', 'description' => 'Without names: element types in this Bricks category (for example "general", "wordpress").' ),
                'max_settings'  => array( 'type' => 'integer', 'minimum' => 10, 'maximum' => 400, 'default' => 200, 'description' => 'With names: most settings returned per element.' ),
            ),
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'bricksLoaded' => array( 'type' => 'boolean' ),
                'elements'     => array( 'type' => 'array' ),
                'unknown'      => array( 'type' => 'array' ),
                'conventions'  => array( 'type' => 'object' ),
            ),
        ),
        'execute_callback'    => 'snn_bricks_element_schema',
        'permission_callback' => function () {
            return current_user_can( 'edit_posts' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'annotations'  => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
        ),
    ) );
}

function snn_bricks_element_schema( $input ) {
    $input  = (array) $input;
    $names  = array_values( array_filter( array_map( 'strval', (array) ( $input['names'] ?? array() ) ), 'strlen' ) );
    $loaded = SNN_Bricks_Schema::bricks_loaded();

    if ( ! $names ) {
        $search   = mb_strtolower( trim( (string) ( $input['search'] ?? '' ) ) );
        $category = mb_strtolower( trim( (string) ( $input['category'] ?? '' ) ) );
        $rows     = array();
        foreach ( SNN_Bricks_Schema::element_names() as $name ) {
            $info = SNN_Bricks_Schema::element_info( $name );
            if ( $search !== '' && strpos( mb_strtolower( $name . ' ' . $info['label'] ), $search ) === false ) {
                continue;
            }
            if ( $category !== '' && mb_strtolower( $info['category'] ) !== $category ) {
                continue;
            }
            $rows[] = array(
                'name'     => $name,
                'label'    => $info['label'],
                'category' => $info['category'],
                'nestable' => SNN_Bricks_Schema::is_nestable( $name ),
            );
        }
        return array(
            'bricksLoaded' => $loaded,
            'elements'     => array_slice( $rows, 0, 300 ),
            'total'        => count( $rows ),
            'unknown'      => array(),
            'conventions'  => snn_bricks_element_schema_conventions(),
        );
    }

    $max      = max( 10, min( 400, (int) ( $input['max_settings'] ?? 200 ) ) );
    $elements = array();
    $unknown  = array();
    foreach ( array_slice( $names, 0, 20 ) as $name ) {
        if ( ! SNN_Bricks_Schema::has_element( $name ) ) {
            $unknown[] = array(
                'name'        => $name,
                'message'     => SNN_Bricks_Schema::unknown_element_message( $name ),
                'suggestions' => SNN_Bricks_Schema::suggest( $name, SNN_Bricks_Schema::element_names() ),
            );
            continue;
        }
        $info     = SNN_Bricks_Schema::element_info( $name );
        $schema   = SNN_Bricks_Schema::settings( $name );
        $settings = array();
        foreach ( (array) $schema as $key => $def ) {
            if ( ! is_array( $def ) ) {
                continue;
            }
            $row = array(
                'key'   => (string) $key,
                'type'  => (string) ( $def['type'] ?? '' ),
                'label' => (string) ( $def['label'] ?? $key ),
            );
            if ( ! empty( $def['options'] ) && is_array( $def['options'] ) ) {
                $row['options'] = array_slice( array_map( 'strval', array_keys( $def['options'] ) ), 0, 40 );
            }
            if ( ( $def['type'] ?? '' ) === 'repeater' && ! empty( $def['fields'] ) && is_array( $def['fields'] ) ) {
                // Repeater items (JSON in data-bricks-<key>) take these fields only.
                $row['fields'] = array();
                foreach ( array_slice( $def['fields'], 0, 40, true ) as $fk => $fd ) {
                    if ( is_array( $fd ) ) {
                        $row['fields'][] = array( 'key' => (string) $fk, 'type' => (string) ( $fd['type'] ?? '' ), 'label' => (string) ( $fd['label'] ?? $fk ) );
                    }
                }
            }
            if ( isset( $def['default'] ) && is_scalar( $def['default'] ) ) {
                $row['default'] = $def['default'];
            }
            $settings[] = $row;
            if ( count( $settings ) >= $max ) {
                break;
            }
        }
        $elements[] = array(
            'name'        => $name,
            'label'       => $info['label'],
            'category'    => $info['category'],
            'nestable'    => SNN_Bricks_Schema::is_nestable( $name ),
            'settingCount' => count( (array) $schema ),
            'settings'    => $settings,
        );
    }
    return array(
        'bricksLoaded' => $loaded,
        'elements'     => $elements,
        'unknown'      => $unknown,
        'conventions'  => snn_bricks_element_schema_conventions(),
    );
}

/** The HTML conventions the converter reads, so an agent can write them without a second lookup. */
function snn_bricks_element_schema_conventions() {
    return array(
        'hint'         => 'data-bricks="<element name>" picks any registered element type (names from this ability). Unknown names are reported and the HTML tag is used instead.',
        'id'           => 'id="x" becomes the Bricks CSS ID; href="#x" and interaction selectors point at it.',
        'settings'     => 'data-bricks-<setting>="value" writes any setting, matched to the schema key regardless of case or dashes. JSON values ([ or {) become repeaters and objects; true/false become booleans.',
        'interactions' => array(
            'shorthand'  => 'data-interact="<trigger>" data-interact-action="<action>" data-interact-target="#id" data-interact-key="data-attr" data-interact-value="v" data-interact-animation="pulse" data-interact-duration="0.6s" data-interact-delay="0.2s" data-interact-offset="120px"',
            'json'       => 'data-interactions=\'[{"trigger":"click","action":"show","target":"#menu"}]\' (any number per element)',
            'triggers'   => SNN_Bricks_Interactions::TRIGGERS,
            'actions'    => SNN_Bricks_Interactions::ACTIONS,
            'targets'    => 'self (default), popup, or a CSS selector such as #menu',
        ),
        'animation'    => 'data-animate="fadeInUp" with data-animate-trigger (enterView default, contentLoaded, scroll with data-animate-offset, hover), data-animate-delay, data-animate-duration.',
        'video'        => 'YouTube and Vimeo iframes and <video src> become Bricks video elements.',
        'lists'        => 'ul/ol with plain text items become a Bricks block whose items are text elements; lists that hold layout keep their blocks.',
        'icons'        => 'Font Awesome (fas, fab, far, fa-*), Ionicons (ion-*) and Themify (ti-*). Other icon classes stay as HTML.',
        'loop'         => 'data-loop="post_type" with data-loop-posts-per-page, data-loop-orderby, data-loop-order.',
        'inline_style' => 'Inline style= declarations become CSS on the element; layout (display, flex, grid, gap, width) becomes native settings.',
        'classes'      => 'Classes with CSS become global classes. snn/bricks-edit-elements can rename_class and delete_class them.',
    );
}
