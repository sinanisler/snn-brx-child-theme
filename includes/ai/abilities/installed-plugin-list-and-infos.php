<?php
/**
 * Installed Plugin List and Infos Ability
 * Registers the snn/installed-plugin-list-and-infos ability for the WordPress Abilities API
 * Returns information about all installed plugins
 */

// Register category
add_action( 'wp_abilities_api_categories_init', 'snn_register_system_info_category' );
function snn_register_system_info_category() {
    if ( ! wp_has_ability_category( 'system-info' ) ) {
        wp_register_ability_category(
            'system-info',
            array(
                'label'       => __( 'System Information', 'snn' ),
                'description' => __( 'Abilities for retrieving WordPress system information.', 'snn' ),
            )
        );
    }
}

// Register ability
add_action( 'wp_abilities_api_init', 'snn_register_installed_plugin_list_ability' );
function snn_register_installed_plugin_list_ability() {
    wp_register_ability(
        'snn/installed-plugin-list-and-infos',
        array(
            'label'       => __( 'Installed Plugin List', 'snn' ),
            'description' => __( 'Returns a list of all installed plugins with their information and status.', 'snn' ),
            'category'    => 'system-info',
            'input_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'status' => array(
                        'type'        => 'string',
                        'description' => 'Filter by plugin status: all, active, inactive, update_available (default: all).',
                        'enum'        => array( 'all', 'active', 'inactive', 'update_available' ),
                        'default'     => 'all',
                    ),
                    'search' => array(
                        'type'        => 'string',
                        'description' => 'Search plugins by name or description.',
                    ),
                    'include_must_use' => array(
                        'type'        => 'boolean',
                        'description' => 'Include must-use plugins (default: true).',
                        'default'     => true,
                    ),
                    'include_dropins' => array(
                        'type'        => 'boolean',
                        'description' => 'Include drop-in plugins (default: false).',
                        'default'     => false,
                    ),
                ),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'total_plugins'    => array( 'type' => 'integer' ),
                    'active_count'     => array( 'type' => 'integer' ),
                    'inactive_count'   => array( 'type' => 'integer' ),
                    'plugins'          => array( 'type' => 'array' ),
                    'must_use_plugins' => array( 'type' => 'array' ),
                    'dropins'          => array( 'type' => 'array' ),
                ),
            ),
            'execute_callback' => function( $input ) {
                if ( ! function_exists( 'get_plugins' ) ) {
                    require_once ABSPATH . 'wp-admin/includes/plugin.php';
                }

                $status_filter = isset( $input['status'] ) ? sanitize_text_field( $input['status'] ) : 'all';
                $search = isset( $input['search'] ) ? sanitize_text_field( $input['search'] ) : '';
                $include_must_use = isset( $input['include_must_use'] ) ? (bool) $input['include_must_use'] : true;
                $include_dropins = isset( $input['include_dropins'] ) ? (bool) $input['include_dropins'] : false;

                // Get all plugins
                $all_plugins = get_plugins();
                $active_plugins = get_option( 'active_plugins', array() );
                $update_plugins = get_site_transient( 'update_plugins' );

                $plugins = array();
                $active_count = 0;
                $inactive_count = 0;

                foreach ( $all_plugins as $plugin_file => $plugin_data ) {
                    $is_active = in_array( $plugin_file, $active_plugins, true );
                    $has_update = isset( $update_plugins->response[ $plugin_file ] );

                    // Apply status filter
                    if ( $status_filter === 'active' && ! $is_active ) {
                        continue;
                    }
                    if ( $status_filter === 'inactive' && $is_active ) {
                        continue;
                    }
                    if ( $status_filter === 'update_available' && ! $has_update ) {
                        continue;
                    }

                    // Apply search filter
                    if ( ! empty( $search ) ) {
                        $search_lower = strtolower( $search );
                        $name_match = stripos( $plugin_data['Name'], $search ) !== false;
                        $desc_match = stripos( $plugin_data['Description'], $search ) !== false;
                        if ( ! $name_match && ! $desc_match ) {
                            continue;
                        }
                    }

                    // Count active/inactive
                    if ( $is_active ) {
                        $active_count++;
                    } else {
                        $inactive_count++;
                    }

                    $plugin_info = array(
                        'file'           => $plugin_file,
                        'name'           => $plugin_data['Name'],
                        'version'        => $plugin_data['Version'],
                        'description'    => wp_strip_all_tags( $plugin_data['Description'] ),
                        'author'         => wp_strip_all_tags( $plugin_data['Author'] ),
                        'author_uri'     => $plugin_data['AuthorURI'],
                        'plugin_uri'     => $plugin_data['PluginURI'],
                        'text_domain'    => $plugin_data['TextDomain'],
                        'requires_wp'    => isset( $plugin_data['RequiresWP'] ) ? $plugin_data['RequiresWP'] : '',
                        'requires_php'   => isset( $plugin_data['RequiresPHP'] ) ? $plugin_data['RequiresPHP'] : '',
                        'is_active'      => $is_active,
                        'has_update'     => $has_update,
                    );

                    // Add update information if available
                    if ( $has_update ) {
                        $update_info = $update_plugins->response[ $plugin_file ];
                        $plugin_info['update_info'] = array(
                            'new_version' => $update_info->new_version,
                            'package'     => isset( $update_info->package ) ? $update_info->package : '',
                            'url'         => isset( $update_info->url ) ? $update_info->url : '',
                        );
                    }

                    $plugins[] = $plugin_info;
                }

                // Sort plugins: active first, then by name
                usort( $plugins, function( $a, $b ) {
                    if ( $a['is_active'] !== $b['is_active'] ) {
                        return $b['is_active'] - $a['is_active'];
                    }
                    return strcasecmp( $a['name'], $b['name'] );
                });

                $result = array(
                    'total_plugins'  => count( $plugins ),
                    'active_count'   => $active_count,
                    'inactive_count' => $inactive_count,
                    'plugins'        => $plugins,
                );

                // Include must-use plugins
                if ( $include_must_use ) {
                    $mu_plugins = get_mu_plugins();
                    $must_use_list = array();

                    foreach ( $mu_plugins as $plugin_file => $plugin_data ) {
                        $must_use_list[] = array(
                            'file'        => $plugin_file,
                            'name'        => $plugin_data['Name'],
                            'version'     => $plugin_data['Version'],
                            'description' => wp_strip_all_tags( $plugin_data['Description'] ),
                            'author'      => wp_strip_all_tags( $plugin_data['Author'] ),
                        );
                    }

                    $result['must_use_plugins'] = $must_use_list;
                    $result['must_use_count'] = count( $must_use_list );
                }

                // Include drop-ins
                if ( $include_dropins ) {
                    $dropins = get_dropins();
                    $dropin_list = array();

                    foreach ( $dropins as $plugin_file => $plugin_data ) {
                        $dropin_list[] = array(
                            'file'        => $plugin_file,
                            'name'        => $plugin_data['Name'],
                            'description' => wp_strip_all_tags( $plugin_data['Description'] ),
                        );
                    }

                    $result['dropins'] = $dropin_list;
                    $result['dropins_count'] = count( $dropin_list );
                }

                return $result;
            },
            'permission_callback' => function() {
                return current_user_can( 'activate_plugins' );
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
