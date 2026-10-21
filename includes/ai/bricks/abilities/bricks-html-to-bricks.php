<?php
/**
 * snn/bricks-html-to-bricks — compile HTML/CSS into native Bricks elements and
 * save them to a page. The server-side twin of the Bricks AI agent's build.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_abilities_api_init', 'snn_register_bricks_html_to_bricks_ability' );
function snn_register_bricks_html_to_bricks_ability() {
    $props = array_merge(
        array(
            'post_id'    => snn_bricks_post_id_schema(),
            'html'       => array( 'type' => 'string', 'description' => 'Page body HTML following snn-bricks-start-here: sibling <section> elements with data-bricks hints and class attributes. May contain <style> blocks.' ),
            'css'        => array( 'type' => 'string', 'description' => 'Stylesheet for the HTML (class rules, :root variables, @media). Optional when the HTML has <style>.' ),
            'mode'       => array(
                'type'        => 'string',
                'enum'        => SNN_Bricks_Writer::MODES,
                'default'     => 'append',
                'description' => 'append / prepend: add sections to the page. replace: replace the whole page. insert-after: after the top-level element anchor_id. replace-section: rebuild the section section_id in place (keeps shared classes intact).',
            ),
            'anchor_id'  => array( 'type' => 'string', 'description' => 'Top-level element id for mode=insert-after.' ),
            'section_id' => array( 'type' => 'string', 'description' => 'Section root id for mode=replace-section.' ),
            'dry_run'    => array( 'type' => 'boolean', 'default' => false, 'description' => 'Compile and return the Bricks elements and classes without saving anything.' ),
            'prompt'     => array( 'type' => 'string', 'description' => 'What the user asked for; stored with the section so it can be regenerated later.' ),
            'import_id'  => array( 'type' => 'string', 'description' => 'Design-zip import session (snn-bricks-import-zip). With chunk, the pass is placed in design order automatically and mode/anchor_id are ignored.' ),
            'chunk'      => array( 'type' => 'string', 'description' => 'The import pass this HTML converts, e.g. "0" or "3.1".' ),
            'rebuild'    => array( 'type' => 'boolean', 'default' => false, 'description' => 'With import_id + chunk: replace an already built pass in place.' ),
            'verify'     => array( 'type' => 'boolean', 'default' => true, 'description' => 'Run Bricks\' server render on the result and include it in feedback.render.' ),
        ),
        snn_bricks_write_input_properties()
    );

    wp_register_ability( 'snn/bricks-html-to-bricks', array(
        'label'       => __( 'Bricks: HTML to Bricks', 'snn' ),
        'description' => __( 'Build Bricks content from HTML/CSS. CSS classes become Bricks global classes (CSS kept verbatim; same name + same CSS reused, conflicting names renamed), layout (flex/grid/gap/width per breakpoint) becomes native element settings, icons/buttons/dividers/SVGs/images become native elements, inline SVGs are uploaded. Saves through Bricks\' own save pipeline with a revision. Read snn-bricks-start-here first. Pass expected_digest from snn-bricks-get-page-tree. For a design-zip import pass, add import_id + chunk. Returns section root ids, revisionId, the new documentDigest and feedback: what changed, the outline of the new sections (ids for later edits), the page\'s section order, checks, a server render check and the next step. dry_run returns the same feedback without saving.', 'snn' ),
        'category'    => 'snn-bricks',
        'input_schema' => array(
            'type'       => 'object',
            'required'   => array( 'post_id', 'html' ),
            'properties' => $props,
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'postId'         => array( 'type' => 'integer' ),
                'mode'           => array( 'type' => 'string' ),
                'sections'       => array( 'type' => 'array' ),
                'classesCreated' => array( 'type' => 'array' ),
                'revisionId'     => array( 'type' => array( 'integer', 'null' ) ),
                'documentDigest' => array( 'type' => 'string' ),
                'warnings'       => array( 'type' => 'array' ),
            ),
        ),
        'execute_callback' => function ( $input ) {
            $html = (string) ( $input['html'] ?? '' );
            $css  = (string) ( $input['css'] ?? '' );
            if ( trim( $html ) === '' ) {
                return new WP_Error( 'snn_missing_html', 'html is required.' );
            }
            $max = (int) apply_filters( 'snn_bricks_max_html_bytes', 2097152 );
            if ( strlen( $html ) + strlen( $css ) > $max ) {
                return new WP_Error( 'snn_html_too_large', 'html + css is larger than ' . round( $max / 1048576, 1 ) . ' MB. Send the page in several calls (mode=append).' );
            }
            if ( ! empty( $input['import_id'] ) ) {
                if ( ! isset( $input['chunk'] ) || $input['chunk'] === '' ) {
                    return new WP_Error( 'snn_missing_chunk', 'import_id needs chunk (the pass id from snn-bricks-import-chunk).' );
                }
                if ( ! empty( $input['dry_run'] ) ) {
                    return snn_bricks_build_import_chunk( $input );
                }
                return snn_bricks_with_idempotency( $input, function () use ( $input ) {
                    return snn_bricks_build_import_chunk( $input );
                } );
            }
            if ( ( $input['mode'] ?? '' ) === 'replace-section' && empty( $input['section_id'] ) ) {
                return new WP_Error( 'snn_missing_section', 'mode=replace-section needs section_id.' );
            }
            if ( in_array( $input['mode'] ?? '', array( 'insert-after', 'insert-before' ), true ) && empty( $input['anchor_id'] ) ) {
                return new WP_Error( 'snn_missing_anchor', 'mode=' . $input['mode'] . ' needs anchor_id.' );
            }
            if ( ! empty( $input['dry_run'] ) ) {
                return SNN_Bricks_Writer::build_and_write( (int) $input['post_id'], snn_bricks_html_args( $input ) );
            }
            return snn_bricks_with_idempotency( $input, function () use ( $input ) {
                return SNN_Bricks_Writer::build_and_write( (int) $input['post_id'], snn_bricks_html_args( $input ) );
            } );
        },
        'permission_callback' => 'snn_bricks_write_permission',
        'meta' => array(
            'show_in_rest' => true,
            'annotations'  => array( 'readonly' => false, 'destructive' => true, 'idempotent' => false ),
        ),
    ) );
}

function snn_bricks_html_args( array $input ) {
    return array(
        'html'            => (string) $input['html'],
        'css'             => (string) ( $input['css'] ?? '' ),
        'mode'            => $input['mode'] ?? 'append',
        'anchor_id'       => $input['anchor_id'] ?? '',
        'section_id'      => $input['section_id'] ?? '',
        'expected_digest' => $input['expected_digest'] ?? '',
        'force'           => ! empty( $input['force'] ),
        'dry_run'         => ! empty( $input['dry_run'] ),
        'prompt'          => $input['prompt'] ?? '',
        'source'          => snn_mcp_is_mcp_request() ? 'mcp' : 'ability',
        'verify'          => ! array_key_exists( 'verify', $input ) || ! empty( $input['verify'] ),
    );
}

/** Build one design-zip import pass in design order and record it in the session. */
function snn_bricks_build_import_chunk( array $input ) {
    $post_id = (int) $input['post_id'];
    $s       = SNN_Bricks_Import_Session::load( $input['import_id'] );
    if ( is_wp_error( $s ) ) {
        return $s;
    }
    $chunk = (string) $input['chunk'];
    if ( ! isset( $s['chunks'][ $chunk ] ) || $s['chunks'][ $chunk ]['status'] === 'split' ) {
        $p = SNN_Bricks_Import_Session::progress( $s );
        return new WP_Error( 'snn_chunk_not_found', 'No pass "' . $chunk . '" in this import. Pending passes: ' . ( $p['pending'] ? implode( ', ', $p['pending'] ) : 'none' ) . '.', array( 'status' => 404 ) );
    }
    if ( $s['postId'] && $s['postId'] !== $post_id ) {
        return new WP_Error( 'snn_import_other_post', 'This import is being built into post ' . $s['postId'] . '. Start a new import for another page.', array( 'status' => 409 ) );
    }

    $state = snn_bricks_page_state( $post_id );
    $place = SNN_Bricks_Import_Session::placement( $s, $chunk, $state['elements'], ! empty( $input['rebuild'] ) );
    if ( is_wp_error( $place ) ) {
        return $place;
    }
    $args = array_merge( snn_bricks_html_args( $input ), array(
        'mode'            => $place['mode'],
        'anchor_id'       => $place['anchor_id'],
        'remove_root_ids' => $place['remove'],
        'source'          => 'mcp-zip',
        'prompt'          => $input['prompt'] ?? ( 'Design zip ' . ( $s['source']['name'] ?? '' ) . ' · pass ' . $chunk . ' (' . $s['chunks'][ $chunk ]['label'] . ')' ),
    ) );
    if ( function_exists( 'snn_mcp_debug' ) ) {
        snn_mcp_debug( 'stage', array( 'stage' => 'import.place', 'importId' => $s['id'], 'chunk' => $chunk, 'placement' => $place ) );
    }

    $result = SNN_Bricks_Writer::build_and_write( $post_id, $args );
    if ( is_wp_error( $result ) || ! empty( $result['dryRun'] ) ) {
        return $result;
    }
    $roots = array_column( $result['sections'], 'rootId' );
    if ( ! empty( $result['pageCssId'] ) ) {
        array_unshift( $roots, $result['pageCssId'] );
    }
    SNN_Bricks_Import_Session::mark_built( $s, $chunk, $post_id, array_values( array_filter( $roots ) ) );
    $result['chunk'] = $chunk;
    return SNN_Bricks_Writer::attach_import_progress( $result, SNN_Bricks_Import_Session::progress( $s ) );
}
