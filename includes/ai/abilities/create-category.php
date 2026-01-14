<?php 
/**
 * Create Category Ability
 * Registers the snn/create-category ability for the WordPress Abilities API
 */

// Register category
add_action( 'wp_abilities_api_categories_init', 'snn_register_taxonomy_category_create' );
function snn_register_taxonomy_category_create() {
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
add_action( 'wp_abilities_api_init', 'snn_register_create_category_ability' );
function snn_register_create_category_ability() {
    wp_register_ability(
        'snn/create-category',
        array(
            'label'       => __( 'Create Category', 'wp-abilities' ),
            'description' => __( 'Creates a new category.', 'wp-abilities' ),
            'category'    => 'taxonomy',
            'input_schema' => array(
                'type'       => 'object',
                'required'   => array( 'name' ),
                'properties' => array(
                    'name' => array(
                        'type'        => 'string',
                        'description' => 'Category name.',
                        'minLength'   => 1,
                    ),
                    'slug' => array(
                        'type'        => 'string',
                        'description' => 'Category slug (optional, will be auto-generated if not provided).',
                    ),
                    'description' => array(
                        'type'        => 'string',
                        'description' => 'Category description.',
                    ),
                    'parent' => array(
                        'type'        => 'integer',
                        'description' => 'Parent category ID.',
                        'default'     => 0,
                    ),
                ),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'id'   => array( 'type' => 'integer' ),
                    'name' => array( 'type' => 'string' ),
                    'slug' => array( 'type' => 'string' ),
                    'url'  => array( 'type' => 'string' ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $args = array(
                    'description' => isset( $input['description'] ) ? sanitize_textarea_field( $input['description'] ) : '',
                    'parent'      => isset( $input['parent'] ) ? absint( $input['parent'] ) : 0,
                );

                if ( ! empty( $input['slug'] ) ) {
                    $args['slug'] = sanitize_title( $input['slug'] );
                }

                $result = wp_insert_term(
                    sanitize_text_field( $input['name'] ),
                    'category',
                    $args
                );

                if ( is_wp_error( $result ) ) {
                    return $result;
                }

                $term = get_term( $result['term_id'], 'category' );

                return array(
                    'id'   => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                    'url'  => get_term_link( $term ),
                );
            },
            'permission_callback' => function() {
                return current_user_can( 'manage_categories' );
            },
            'meta' => array(
                'show_in_rest' => true,
                'readonly'     => false,
                'destructive'  => false,
                'idempotent'   => false,
            ),
        )
    );
}
