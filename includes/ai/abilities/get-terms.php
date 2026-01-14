<?php
/**
 * Get Terms Ability
 * Registers the snn/get-terms ability for the WordPress Abilities API
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
add_action( 'wp_abilities_api_init', 'snn_register_get_terms_ability' );
function snn_register_get_terms_ability() {
    wp_register_ability(
        'snn/get-terms',
        array(
            'label'       => __( 'Get Terms', 'wp-abilities' ),
            'description' => __( 'Retrieves a list of terms from a specific taxonomy. IMPORTANT: You must provide a valid taxonomy slug (e.g., "category", "post_tag"). If you do not know the available taxonomies, call list-taxonomies first to discover them. Common taxonomies: "category" for post categories, "post_tag" for post tags. Custom taxonomies vary by site.', 'wp-abilities' ),
            'category'    => 'taxonomy',
            'input_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'taxonomy' => array(
                        'type'        => 'string',
                        'description' => 'The taxonomy slug to retrieve terms from. REQUIRED. Use "category" for post categories, "post_tag" for tags. For custom taxonomies (e.g., courses, products), call list-taxonomies first to get the exact slug. Do NOT guess taxonomy slugs.',
                        'default'     => 'category',
                    ),
                    'hide_empty' => array(
                        'type'        => 'boolean',
                        'description' => 'Whether to hide empty terms.',
                        'default'     => false,
                    ),
                    'number' => array(
                        'type'        => 'integer',
                        'description' => 'Maximum number of terms to return (max 100 for performance). Use 0 for default limit.',
                        'default'     => 100,
                        'minimum'     => 0,
                        'maximum'     => 100,
                    ),
                    'orderby' => array(
                        'type'        => 'string',
                        'description' => 'Field to order terms by (e.g., "name", "slug", "term_id", "count").',
                        'default'     => 'name',
                    ),
                    'order' => array(
                        'type'        => 'string',
                        'description' => 'Sort order ("ASC" or "DESC").',
                        'default'     => 'ASC',
                    ),
                    'parent' => array(
                        'type'        => 'integer',
                        'description' => 'Parent term ID to retrieve direct children of. Use 0 for top-level terms only.',
                    ),
                    'search' => array(
                        'type'        => 'string',
                        'description' => 'Search term name or slug.',
                    ),
                ),
            ),
            'output_schema' => array(
                'type'  => 'array',
                'items' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'id'          => array( 'type' => 'integer' ),
                        'name'        => array( 'type' => 'string' ),
                        'slug'        => array( 'type' => 'string' ),
                        'taxonomy'    => array( 'type' => 'string' ),
                        'count'       => array( 'type' => 'integer' ),
                        'url'         => array( 'type' => 'string' ),
                        'parent'      => array( 'type' => 'integer' ),
                        'description' => array( 'type' => 'string' ),
                    ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $taxonomy = isset( $input['taxonomy'] ) ? sanitize_key( $input['taxonomy'] ) : 'category';

                // Validate taxonomy exists
                if ( ! taxonomy_exists( $taxonomy ) ) {
                    // Get list of available public taxonomies to help the agent
                    $available_taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
                    $taxonomy_list = array();
                    foreach ( $available_taxonomies as $tax ) {
                        $taxonomy_list[] = sprintf( '"%s" (%s)', $tax->name, $tax->label );
                    }
                    return new WP_Error(
                        'invalid_taxonomy',
                        sprintf(
                            'The taxonomy "%s" does not exist. Available taxonomies: %s. Use list-taxonomies ability to get full details.',
                            $taxonomy,
                            implode( ', ', $taxonomy_list )
                        )
                    );
                }

                $number = isset( $input['number'] ) ? absint( $input['number'] ) : 100;
                // Cap at 100 for performance
                $number = min( $number, 100 );
                if ( $number === 0 ) {
                    $number = 100;
                }

                $args = array(
                    'taxonomy'   => $taxonomy,
                    'hide_empty' => isset( $input['hide_empty'] ) ? (bool) $input['hide_empty'] : false,
                    'number'     => $number,
                    'orderby'    => isset( $input['orderby'] ) ? sanitize_key( $input['orderby'] ) : 'name',
                    'order'      => isset( $input['order'] ) ? strtoupper( sanitize_key( $input['order'] ) ) : 'ASC',
                );

                if ( isset( $input['parent'] ) ) {
                    $args['parent'] = absint( $input['parent'] );
                }

                if ( isset( $input['search'] ) && ! empty( $input['search'] ) ) {
                    $args['search'] = sanitize_text_field( $input['search'] );
                }

                $terms = get_terms( $args );

                if ( is_wp_error( $terms ) ) {
                    return $terms;
                }

                $result = array();
                foreach ( $terms as $term ) {
                    $result[] = array(
                        'id'          => $term->term_id,
                        'name'        => $term->name,
                        'slug'        => $term->slug,
                        'taxonomy'    => $term->taxonomy,
                        'count'       => $term->count,
                        'url'         => get_term_link( $term ),
                        'parent'      => $term->parent,
                        'description' => $term->description,
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
