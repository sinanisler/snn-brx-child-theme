<?php 
/**
 * Get Categories Ability
 * Registers the snn/get-categories ability for the WordPress Abilities API
 */

// Register category
add_action( 'wp_abilities_api_categories_init', 'snn_register_taxonomy_category' );
function snn_register_taxonomy_category() {
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
add_action( 'wp_abilities_api_init', 'snn_register_get_categories_ability' );
function snn_register_get_categories_ability() {
    wp_register_ability(
        'snn/get-categories',
        array(
            'label'       => __( 'Get Categories', 'wp-abilities' ),
            'description' => __( 'Retrieves a list of categories.', 'wp-abilities' ),
            'category'    => 'taxonomy',
            'input_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'hide_empty' => array(
                        'type'        => 'boolean',
                        'description' => 'Whether to hide empty categories.',
                        'default'     => false,
                    ),
                    'number' => array(
                        'type'        => 'integer',
                        'description' => 'Maximum number of categories to return.',
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
                        'parent' => array( 'type' => 'integer' ),
                        'description' => array( 'type' => 'string' ),
                    ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $args = array(
                    'taxonomy'   => 'category',
                    'hide_empty' => isset( $input['hide_empty'] ) ? (bool) $input['hide_empty'] : false,
                    'number'     => isset( $input['number'] ) ? absint( $input['number'] ) : 0,
                );

                $categories = get_terms( $args );

                if ( is_wp_error( $categories ) ) {
                    return $categories;
                }

                $result = array();
                foreach ( $categories as $category ) {
                    $result[] = array(
                        'id'    => $category->term_id,
                        'name'  => $category->name,
                        'slug'  => $category->slug,
                        'count' => $category->count,
                        'url'   => get_term_link( $category ),
                        'parent' => $category->parent,
                        'description' => $category->description,
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
