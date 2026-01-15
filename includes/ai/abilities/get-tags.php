<?php
/**
 * Get Tags Ability
 * Registers the snn/get-tags ability for the WordPress Abilities API
 */

// Register ability
add_action( 'wp_abilities_api_init', 'snn_register_get_tags_ability' );
function snn_register_get_tags_ability() {
    wp_register_ability(
        'snn/get-tags',
        array(
            'label'       => __( 'Get Tags', 'wp-abilities' ),
            'description' => __( 'Retrieves post tags (post_tag taxonomy) with ID, name, slug, post count, tag archive URL, and description. Can show/hide empty tags and limit result count. Returns flat list of all tags. This is a convenience wrapper for get-terms with taxonomy="post_tag". Use this for quick tag listings, tag clouds, or when you specifically need post tags. For more advanced filtering, custom taxonomies, or hierarchical terms, use get-terms or list-taxonomies instead.', 'wp-abilities' ),
            'category'    => 'taxonomy',
            'input_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'hide_empty' => array(
                        'type'        => 'boolean',
                        'description' => 'Whether to hide empty tags.',
                        'default'     => false,
                    ),
                    'number' => array(
                        'type'        => 'integer',
                        'description' => 'Maximum number of tags to return.',
                        'default'     => 0,
                    ),
                ),
            ),
            'output_schema' => array(
                'type'  => 'array',
                'items' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'id'    => array( 'type' => 'integer' ),
                        'name'  => array( 'type' => 'string' ),
                        'slug'  => array( 'type' => 'string' ),
                        'count' => array( 'type' => 'integer' ),
                        'url'   => array( 'type' => 'string' ),
                        'description' => array( 'type' => 'string' ),
                    ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $args = array(
                    'taxonomy'   => 'post_tag',
                    'hide_empty' => isset( $input['hide_empty'] ) ? (bool) $input['hide_empty'] : false,
                    'number'     => isset( $input['number'] ) ? absint( $input['number'] ) : 0,
                );

                $tags = get_terms( $args );

                if ( is_wp_error( $tags ) ) {
                    return $tags;
                }

                $result = array();
                foreach ( $tags as $tag ) {
                    $result[] = array(
                        'id'    => $tag->term_id,
                        'name'  => $tag->name,
                        'slug'  => $tag->slug,
                        'count' => $tag->count,
                        'url'   => get_term_link( $tag ),
                        'description' => $tag->description,
                    );
                }

                return $result;
            },
            'permission_callback' => '__return_true',
            'meta' => array(
                'show_in_rest' => true,
                'readonly'     => true,
                'destructive'  => false,
                'idempotent'   => true,
            ),
        )
    );
}
