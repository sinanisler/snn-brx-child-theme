<?php
/**
 * Create Terms Ability
 * Registers the snn/create-terms ability for the WordPress Abilities API
 */

// Register ability
add_action( 'wp_abilities_api_init', 'snn_register_create_terms_ability' );
function snn_register_create_terms_ability() {
    wp_register_ability(
        'snn/create-terms',
        array(
            'label'       => __( 'Create Terms', 'wp-abilities' ),
            'description' => __( 'Creates a new term (category, tag, or custom taxonomy term) with name, slug, description, and optional parent (for hierarchical taxonomies). CRITICAL: You MUST provide a valid taxonomy slug. Call list-taxonomies FIRST if you don\'t know the exact taxonomy slugs available on this site. Common built-in taxonomies: "category" (hierarchical post categories), "post_tag" (flat post tags). Custom taxonomies vary by site (e.g., "product_cat", "course_category"). Returns the new term ID, slug, and URL. Validates taxonomy exists before creation and provides helpful error messages with available taxonomies if invalid slug provided.', 'wp-abilities' ),
            'category'    => 'taxonomy',
            'input_schema' => array(
                'type'       => 'object',
                'required'   => array( 'name', 'taxonomy' ),
                'properties' => array(
                    'name' => array(
                        'type'        => 'string',
                        'description' => 'Term name.',
                        'minLength'   => 1,
                    ),
                    'taxonomy' => array(
                        'type'        => 'string',
                        'description' => 'The taxonomy slug to create the term in. REQUIRED. Use "category" for post categories, "post_tag" for tags. For custom taxonomies (e.g., courses, products), call list-taxonomies first to get the exact slug. Do NOT guess taxonomy slugs.',
                        'minLength'   => 1,
                    ),
                    'slug' => array(
                        'type'        => 'string',
                        'description' => 'Term slug (optional, will be auto-generated if not provided).',
                    ),
                    'description' => array(
                        'type'        => 'string',
                        'description' => 'Term description.',
                    ),
                    'parent' => array(
                        'type'        => 'integer',
                        'description' => 'Parent term ID (only for hierarchical taxonomies like categories).',
                        'default'     => 0,
                    ),
                ),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'id'       => array( 'type' => 'integer' ),
                    'name'     => array( 'type' => 'string' ),
                    'slug'     => array( 'type' => 'string' ),
                    'taxonomy' => array( 'type' => 'string' ),
                    'url'      => array( 'type' => 'string' ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $taxonomy = sanitize_key( $input['taxonomy'] );

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

                $args = array(
                    'description' => isset( $input['description'] ) ? sanitize_textarea_field( $input['description'] ) : '',
                );

                // Only set parent for hierarchical taxonomies
                if ( is_taxonomy_hierarchical( $taxonomy ) && isset( $input['parent'] ) ) {
                    $args['parent'] = absint( $input['parent'] );
                }

                if ( ! empty( $input['slug'] ) ) {
                    $args['slug'] = sanitize_title( $input['slug'] );
                }

                $result = wp_insert_term(
                    sanitize_text_field( $input['name'] ),
                    $taxonomy,
                    $args
                );

                if ( is_wp_error( $result ) ) {
                    return $result;
                }

                $term = get_term( $result['term_id'], $taxonomy );

                return array(
                    'id'       => $term->term_id,
                    'name'     => $term->name,
                    'slug'     => $term->slug,
                    'taxonomy' => $term->taxonomy,
                    'url'      => get_term_link( $term ),
                );
            },
            'permission_callback' => function( $input ) {
                $taxonomy = isset( $input['taxonomy'] ) ? sanitize_key( $input['taxonomy'] ) : 'category';
                $tax_obj = get_taxonomy( $taxonomy );

                if ( ! $tax_obj ) {
                    return false;
                }

                return current_user_can( $tax_obj->cap->manage_terms );
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
