<?php
/**
 * List Taxonomies Ability
 * Registers the snn/list-taxonomies ability for the WordPress Abilities API
 */

// Register category
add_action( 'wp_abilities_api_categories_init', 'snn_register_taxonomy_category_list_taxonomies' );
function snn_register_taxonomy_category_list_taxonomies() {
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
add_action( 'wp_abilities_api_init', 'snn_register_list_taxonomies_ability' );
function snn_register_list_taxonomies_ability() {
    wp_register_ability(
        'snn/list-taxonomies',
        array(
            'label'       => __( 'List Taxonomies', 'wp-abilities' ),
            'description' => __( 'CRITICAL DISCOVERY TOOL: Lists all registered taxonomies on the site with slug (required for get-terms/create-terms), singular/plural names, hierarchical status (true for category-like, false for tag-like), public visibility, associated post types, and term count. Can filter by public taxonomies only or by post type association. ALWAYS call this FIRST when working with taxonomies if you don\'t know the exact taxonomy slugs - never guess taxonomy names. Built-in taxonomies are "category" and "post_tag", but sites often have custom ones like "product_cat", "course_category", "portfolio_tag". Returns sorted list by label.', 'wp-abilities' ),
            'category'    => 'taxonomy',
            'input_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'public_only' => array(
                        'type'        => 'boolean',
                        'description' => 'If true, only return public taxonomies. If false, return all taxonomies including internal ones. Default is true.',
                        'default'     => true,
                    ),
                    'object_type' => array(
                        'type'        => 'string',
                        'description' => 'Filter taxonomies by the post type they are associated with (e.g., "post", "page", "product"). Leave empty to get all taxonomies.',
                    ),
                ),
            ),
            'output_schema' => array(
                'type'  => 'array',
                'items' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'slug'         => array(
                            'type'        => 'string',
                            'description' => 'The taxonomy slug to use with get-terms and create-terms abilities.',
                        ),
                        'name'         => array(
                            'type'        => 'string',
                            'description' => 'Human-readable singular name of the taxonomy.',
                        ),
                        'label'        => array(
                            'type'        => 'string',
                            'description' => 'Human-readable plural label of the taxonomy.',
                        ),
                        'hierarchical' => array(
                            'type'        => 'boolean',
                            'description' => 'Whether the taxonomy is hierarchical (like categories) or flat (like tags).',
                        ),
                        'public'       => array(
                            'type'        => 'boolean',
                            'description' => 'Whether the taxonomy is publicly visible.',
                        ),
                        'object_types' => array(
                            'type'        => 'array',
                            'description' => 'Post types this taxonomy is associated with.',
                            'items'       => array( 'type' => 'string' ),
                        ),
                        'term_count'   => array(
                            'type'        => 'integer',
                            'description' => 'Number of terms in this taxonomy.',
                        ),
                    ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $public_only = isset( $input['public_only'] ) ? (bool) $input['public_only'] : true;
                $object_type = isset( $input['object_type'] ) && ! empty( $input['object_type'] )
                    ? sanitize_key( $input['object_type'] )
                    : null;

                $args = array();
                if ( $public_only ) {
                    $args['public'] = true;
                }
                if ( $object_type ) {
                    $args['object_type'] = array( $object_type );
                }

                $taxonomies = get_taxonomies( $args, 'objects' );
                $result = array();

                foreach ( $taxonomies as $taxonomy ) {
                    // Get term count for this taxonomy
                    $term_count = wp_count_terms( array(
                        'taxonomy'   => $taxonomy->name,
                        'hide_empty' => false,
                    ) );

                    if ( is_wp_error( $term_count ) ) {
                        $term_count = 0;
                    }

                    $result[] = array(
                        'slug'         => $taxonomy->name,
                        'name'         => $taxonomy->labels->singular_name,
                        'label'        => $taxonomy->label,
                        'hierarchical' => $taxonomy->hierarchical,
                        'public'       => $taxonomy->public,
                        'object_types' => $taxonomy->object_type,
                        'term_count'   => (int) $term_count,
                    );
                }

                // Sort by label for easier reading
                usort( $result, function( $a, $b ) {
                    return strcasecmp( $a['label'], $b['label'] );
                } );

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
