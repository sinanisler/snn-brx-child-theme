<?php 
/**
 * Get Tags Ability
 * Registers the snn/get-tags ability for the WordPress Abilities API
 */

// Register category
add_action( 'wp_abilities_api_categories_init', 'snn_register_taxonomy_category_tags' );
function snn_register_taxonomy_category_tags() {
    // Only register if not already registered
    if ( ! wp_has_ability_category( 'taxonomy' ) ) {
        wp_register_ability_category(
            'taxonomy',
            array(
                'label'       => __( 'Taxonomy Management', 'snn' ),
                'description' => __( 'Abilities for managing categories, tags, and taxonomies.', 'snn' ),
            )
        );
    }
}

// Register ability
add_action( 'wp_abilities_api_init', 'snn_register_get_tags_ability' );
function snn_register_get_tags_ability() {
    wp_register_ability(
        'snn/get-tags',
        array(
            'label'       => __( 'Get Tags', 'wp-abilities' ),
            'description' => __( 'Retrieves a list of tags.', 'wp-abilities' ),
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
