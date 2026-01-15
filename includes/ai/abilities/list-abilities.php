<?php 
/**
 * List Abilities Ability
 * Registers the snn/list-abilities ability for the WordPress Abilities API
 */

// Register ability (category 'system' is already registered in get-site-info.php)
add_action( 'wp_abilities_api_init', 'snn_register_list_abilities_ability' );
function snn_register_list_abilities_ability() {
    wp_register_ability(
        'snn/list-abilities',
        array(
            'label'       => __( 'List Abilities', 'snn' ),
            'description' => __( 'Retrieves all registered WordPress abilities with their names, descriptions, categories, and parameter schemas. Use this to discover what actions the AI agent can perform, understand available functionality, check which abilities are enabled, or help users learn what tasks can be automated. Returns comprehensive information about each ability including input/output requirements.', 'snn' ),
            'category'    => 'system',
            'input_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'category' => array(
                        'type'        => 'string',
                        'description' => 'Optional: Filter abilities by category (e.g., posts, users, system).',
                    ),
                ),
            ),
            'output_schema' => array(
                'type'        => 'array',
                'description' => 'Array of ability objects with complete information.',
                'items'       => array(
                    'type'       => 'object',
                    'properties' => array(
                        'name' => array(
                            'type'        => 'string',
                            'description' => 'The ability name/identifier.',
                        ),
                        'label' => array(
                            'type'        => 'string',
                            'description' => 'Human-readable label.',
                        ),
                        'description' => array(
                            'type'        => 'string',
                            'description' => 'Detailed description of what the ability does.',
                        ),
                        'category' => array(
                            'type'        => 'string',
                            'description' => 'The category this ability belongs to.',
                        ),
                        'input_schema' => array(
                            'type'        => 'object',
                            'description' => 'Schema defining required and optional input parameters.',
                        ),
                        'output_schema' => array(
                            'type'        => 'object',
                            'description' => 'Schema defining the expected output format.',
                        ),
                    ),
                ),
            ),
            'execute_callback' => function( $input ) {
                // Get all registered abilities using WordPress Core function
                $all_abilities = wp_get_abilities();
                
                if ( empty( $all_abilities ) ) {
                    return new \WP_Error(
                        'no_abilities_registered',
                        __( 'No abilities are currently registered.', 'snn' )
                    );
                }
                
                $result = array();
                
                foreach ( $all_abilities as $ability ) {
                    // Filter by category if specified
                    $ability_category = $ability->get_category();
                    if ( ! empty( $input['category'] ) && $ability_category !== $input['category'] ) {
                        continue;
                    }
                    
                    $result[] = array(
                        'name'          => $ability->get_name(),
                        'label'         => $ability->get_label(),
                        'description'   => $ability->get_description(),
                        'category'      => $ability_category ?? 'uncategorized',
                        'input_schema'  => $ability->get_input_schema(),
                        'output_schema' => $ability->get_output_schema(),
                        'meta'          => $ability->get_meta(),
                    );
                }
                
                // Sort by category, then by name
                usort( $result, function( $a, $b ) {
                    $cat_cmp = strcmp( $a['category'], $b['category'] );
                    if ( $cat_cmp !== 0 ) {
                        return $cat_cmp;
                    }
                    return strcmp( $a['name'], $b['name'] );
                } );
                
                return array(
                    'abilities' => $result,
                    'total'     => count( $result ),
                );
            },
            'permission_callback' => '__return_true',
        )
    );
}
