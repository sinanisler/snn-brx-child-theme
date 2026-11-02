<?php
/**
 * Pixabay Image Ability
 * Registers the snn/pixabay-image ability: search Pixabay, or import one result
 * into the media library. Helper: includes/ai/pixabay/class-pixabay.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_abilities_api_init', 'snn_register_pixabay_image_ability' );
function snn_register_pixabay_image_ability() {
    wp_register_ability(
        'snn/pixabay-image',
        array(
            'label'       => __( 'Pixabay Image', 'snn' ),
            'description' => __( 'Search Pixabay photos or import one into the media library. SEARCH: pass query, and optionally orientation, min_width, order and per_page (3 to 20, default 10). It returns candidates with tags, size, popularity and photographer, and writes nothing. Choose by tags and size, then call again with the chosen pixabay_id to IMPORT: pass pixabay_id and alt (a short description of the image). The import returns the media library id and url. Use that url in the page. Never use preview_url in page content: it is for judging the image only, and Pixabay does not allow serving its images from the site. Importing an ID that is already in the library returns the existing attachment. Keep photographer and page_url for attribution.', 'snn' ),
            'category'    => 'media',
            'input_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'query'       => array(
                        'type'        => 'string',
                        'maxLength'   => 100,
                        'description' => 'Search words, e.g. "mountain lake at dawn". Required to search.',
                    ),
                    'pixabay_id'  => array(
                        'type'        => 'integer',
                        'minimum'     => 1,
                        'description' => 'Import this image. Use a pixabay_id from an earlier search result.',
                    ),
                    'alt'         => array(
                        'type'        => 'string',
                        'description' => 'Alt text for the imported image. Only used when importing.',
                    ),
                    'orientation' => array(
                        'type'        => 'string',
                        'enum'        => array( 'all', 'horizontal', 'vertical' ),
                        'default'     => 'all',
                        'description' => 'Only used when searching.',
                    ),
                    'min_width'   => array(
                        'type'        => 'integer',
                        'minimum'     => 1,
                        'description' => 'Only used when searching: minimum width in pixels (e.g. 1200 for a hero image).',
                    ),
                    'order'       => array(
                        'type'        => 'string',
                        'enum'        => array( 'popular', 'latest' ),
                        'default'     => 'popular',
                        'description' => 'Only used when searching.',
                    ),
                    'per_page'    => array(
                        'type'        => 'integer',
                        'minimum'     => 3,
                        'maximum'     => 20,
                        'default'     => 10,
                        'description' => 'Only used when searching: how many candidates to return.',
                    ),
                ),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'results'      => array(
                        'type'  => 'array',
                        'items' => array(
                            'type'       => 'object',
                            'properties' => array(
                                'pixabay_id'   => array( 'type' => 'integer' ),
                                'preview_url'  => array( 'type' => 'string' ),
                                'width'        => array( 'type' => 'integer' ),
                                'height'       => array( 'type' => 'integer' ),
                                'tags'         => array( 'type' => 'string' ),
                                'views'        => array( 'type' => 'integer' ),
                                'downloads'    => array( 'type' => 'integer' ),
                                'photographer' => array( 'type' => 'string' ),
                                'page_url'     => array( 'type' => 'string' ),
                            ),
                        ),
                    ),
                    'id'           => array( 'type' => 'integer' ),
                    'url'          => array( 'type' => 'string' ),
                    'filename'     => array( 'type' => 'string' ),
                    'pixabay_id'   => array( 'type' => 'integer' ),
                    'photographer' => array( 'type' => 'string' ),
                    'page_url'     => array( 'type' => 'string' ),
                ),
            ),
            'execute_callback' => function ( $input ) {
                $pixabay_id = (int) ( $input['pixabay_id'] ?? 0 );
                if ( $pixabay_id > 0 ) {
                    return SNN_Pixabay::import( $pixabay_id, sanitize_text_field( (string) ( $input['alt'] ?? '' ) ) );
                }

                $query = sanitize_text_field( (string) ( $input['query'] ?? '' ) );
                if ( '' === $query ) {
                    return new WP_Error( 'snn_pixabay_missing_query', 'Give a query to search, or a pixabay_id to import.' );
                }

                $hits = SNN_Pixabay::search( array(
                    'query'       => $query,
                    'per_page'    => (int) ( $input['per_page'] ?? 10 ),
                    'orientation' => (string) ( $input['orientation'] ?? 'all' ),
                    'order'       => (string) ( $input['order'] ?? 'popular' ),
                    'min_width'   => (int) ( $input['min_width'] ?? 0 ),
                ) );
                if ( is_wp_error( $hits ) ) {
                    return $hits;
                }

                return array( 'results' => array_map( array( 'SNN_Pixabay', 'public_view' ), $hits ) );
            },
            'permission_callback' => function () {
                return current_user_can( 'upload_files' );
            },
            'meta' => array(
                'show_in_rest' => true,
                'annotations'  => array( 'readonly' => false, 'destructive' => false, 'idempotent' => true ),
            ),
        )
    );
}
