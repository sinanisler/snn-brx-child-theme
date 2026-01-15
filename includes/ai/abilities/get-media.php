<?php 
/**
 * Get Media Ability
 * Registers the snn/get-media ability for the WordPress Abilities API
 */

// Register category
add_action( 'wp_abilities_api_categories_init', 'snn_register_media_category' );
function snn_register_media_category() {
    // Only register if not already registered
    if ( ! wp_has_ability_category( 'media' ) ) {
        wp_register_ability_category(
            'media',
            array(
                'label'       => __( 'Media Management', 'snn' ),
                'description' => __( 'Abilities for managing media and attachments.', 'snn' ),
            )
        );
    }
}

// Register ability
add_action( 'wp_abilities_api_init', 'snn_register_get_media_ability' );
function snn_register_get_media_ability() {
    wp_register_ability(
        'snn/get-media',
        array(
            'label'       => __( 'Get Media', 'wp-abilities' ),
            'description' => __( 'Retrieves media library items (images, videos, documents, audio) with complete details including attachment ID, title, file URL, MIME type, file size in bytes, upload date, and alt text. Can filter by MIME type (e.g., "image/jpeg", "image/png", "application/pdf", "video/mp4") to get specific file types. Limited to 100 items per request for performance. Use this to audit media library, find unused images, check file sizes, locate specific media types, export media data, or analyze media usage patterns.', 'wp-abilities' ),
            'category'    => 'media',
            'input_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'posts_per_page' => array(
                        'type'        => 'integer',
                        'description' => 'Number of media items to retrieve (max 100 for performance). Omit parameter or use default to get first 10.',
                        'default'     => 10,
                        'minimum'     => 1,
                        'maximum'     => 100,
                    ),
                    'mime_type' => array(
                        'type'        => 'string',
                        'description' => 'Filter by MIME type (e.g., image/jpeg, image/png).',
                    ),
                ),
            ),
            'output_schema' => array(
                'type'  => 'array',
                'items' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'id'        => array( 'type' => 'integer' ),
                        'title'     => array( 'type' => 'string' ),
                        'url'       => array( 'type' => 'string' ),
                        'mime_type' => array( 'type' => 'string' ),
                        'file_size' => array( 'type' => 'integer' ),
                        'date'      => array( 'type' => 'string' ),
                        'alt_text'  => array( 'type' => 'string' ),
                    ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $posts_per_page = isset( $input['posts_per_page'] ) ? absint( $input['posts_per_page'] ) : 10;
                $args = array(
                    'post_type'      => 'attachment',
                    'post_status'    => 'inherit',
                    // Cap at 100 for performance on large sites
                    'posts_per_page' => min( $posts_per_page, 100 ),
                );

                if ( ! empty( $input['mime_type'] ) ) {
                    $args['post_mime_type'] = sanitize_text_field( $input['mime_type'] );
                }

                $attachments = get_posts( $args );
                $result = array();

                foreach ( $attachments as $attachment ) {
                    $file_path = get_attached_file( $attachment->ID );
                    $file_size = file_exists( $file_path ) ? filesize( $file_path ) : 0;

                    $result[] = array(
                        'id'        => $attachment->ID,
                        'title'     => $attachment->post_title,
                        'url'       => wp_get_attachment_url( $attachment->ID ),
                        'mime_type' => $attachment->post_mime_type,
                        'file_size' => $file_size,
                        'date'      => $attachment->post_date,
                        'alt_text'  => get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ),
                    );
                }

                return $result;
            },
            'permission_callback' => function() {
                return current_user_can( 'upload_files' );
            },
            'meta' => array(
                'show_in_rest' => true,
                'readonly'     => true,
                'destructive'  => false,
                'idempotent'   => true,
            ),
        )
    );
}
