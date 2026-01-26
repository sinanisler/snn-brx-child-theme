<?php
/** 
 * Active Theme Info Ability
 * Registers the snn/active-theme-info ability for the WordPress Abilities API
 * Returns detailed information about the active theme
 */

// Register ability
add_action( 'wp_abilities_api_init', 'snn_register_active_theme_info_ability' );
function snn_register_active_theme_info_ability() {
    wp_register_ability(
        'snn/active-theme-info',
        array(
            'label'       => __( 'Active Theme Info', 'snn' ),
            'description' => __( 'Retrieves comprehensive information about the active WordPress theme including version, author, requirements, template files, theme modifications, and supported features. Returns child theme and parent theme data if applicable. Can optionally include all installed themes, template file list, and theme customizer settings. Use this when you need to understand the current theme setup, check compatibility, analyze theme structure, or investigate theme-related issues.', 'snn' ),
            'category'    => 'system-info',
            'input_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'include_all_themes' => array(
                        'type'        => 'boolean',
                        'description' => 'Include list of all installed themes (default: false).',
                        'default'     => false,
                    ),
                    'include_template_files' => array(
                        'type'        => 'boolean',
                        'description' => 'Include list of theme template files (default: false).',
                        'default'     => false,
                    ),
                    'include_theme_mods' => array(
                        'type'        => 'boolean',
                        'description' => 'Include theme modifications/customizer settings (default: false).',
                        'default'     => false,
                    ),
                ),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'active_theme'   => array( 'type' => 'object' ),
                    'parent_theme'   => array( 'type' => 'object' ),
                    'is_child_theme' => array( 'type' => 'boolean' ),
                    'all_themes'     => array( 'type' => 'array' ),
                    'template_files' => array( 'type' => 'array' ),
                    'theme_mods'     => array( 'type' => 'object' ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $include_all_themes = isset( $input['include_all_themes'] ) ? (bool) $input['include_all_themes'] : false;
                $include_template_files = isset( $input['include_template_files'] ) ? (bool) $input['include_template_files'] : false;
                $include_theme_mods = isset( $input['include_theme_mods'] ) ? (bool) $input['include_theme_mods'] : false;

                // Get active theme
                $theme = wp_get_theme();
                $is_child_theme = is_child_theme();

                // Build active theme info
                $active_theme = array(
                    'name'           => $theme->get( 'Name' ),
                    'version'        => $theme->get( 'Version' ),
                    'description'    => $theme->get( 'Description' ),
                    'author'         => $theme->get( 'Author' ),
                    'author_uri'     => $theme->get( 'AuthorURI' ),
                    'theme_uri'      => $theme->get( 'ThemeURI' ),
                    'template'       => $theme->get_template(),
                    'stylesheet'     => $theme->get_stylesheet(),
                    'screenshot'     => $theme->get_screenshot(),
                    'text_domain'    => $theme->get( 'TextDomain' ),
                    'requires_wp'    => $theme->get( 'RequiresWP' ),
                    'requires_php'   => $theme->get( 'RequiresPHP' ),
                    'tags'           => $theme->get( 'Tags' ),
                    'theme_root'     => $theme->get_theme_root(),
                    'theme_root_uri' => $theme->get_theme_root_uri(),
                    'stylesheet_directory'     => get_stylesheet_directory(),
                    'stylesheet_directory_uri' => get_stylesheet_directory_uri(),
                    'template_directory'       => get_template_directory(),
                    'template_directory_uri'   => get_template_directory_uri(),
                );

                // Check for theme updates
                $update_themes = get_site_transient( 'update_themes' );
                $theme_slug = $theme->get_stylesheet();
                if ( isset( $update_themes->response[ $theme_slug ] ) ) {
                    $active_theme['has_update'] = true;
                    $active_theme['update_info'] = array(
                        'new_version' => $update_themes->response[ $theme_slug ]['new_version'],
                        'package'     => isset( $update_themes->response[ $theme_slug ]['package'] )
                            ? $update_themes->response[ $theme_slug ]['package'] : '',
                    );
                } else {
                    $active_theme['has_update'] = false;
                }

                $result = array(
                    'active_theme'   => $active_theme,
                    'is_child_theme' => $is_child_theme,
                );

                // Get parent theme info if child theme
                if ( $is_child_theme ) {
                    $parent_theme = wp_get_theme( $theme->get_template() );
                    $result['parent_theme'] = array(
                        'name'         => $parent_theme->get( 'Name' ),
                        'version'      => $parent_theme->get( 'Version' ),
                        'description'  => $parent_theme->get( 'Description' ),
                        'author'       => $parent_theme->get( 'Author' ),
                        'author_uri'   => $parent_theme->get( 'AuthorURI' ),
                        'theme_uri'    => $parent_theme->get( 'ThemeURI' ),
                        'template'     => $parent_theme->get_template(),
                        'stylesheet'   => $parent_theme->get_stylesheet(),
                        'requires_wp'  => $parent_theme->get( 'RequiresWP' ),
                        'requires_php' => $parent_theme->get( 'RequiresPHP' ),
                    );

                    // Check for parent theme updates
                    $parent_slug = $parent_theme->get_stylesheet();
                    if ( isset( $update_themes->response[ $parent_slug ] ) ) {
                        $result['parent_theme']['has_update'] = true;
                        $result['parent_theme']['update_info'] = array(
                            'new_version' => $update_themes->response[ $parent_slug ]['new_version'],
                        );
                    } else {
                        $result['parent_theme']['has_update'] = false;
                    }
                }

                // Include all installed themes
                if ( $include_all_themes ) {
                    $all_themes = wp_get_themes();
                    $themes_list = array();

                    foreach ( $all_themes as $theme_slug => $theme_obj ) {
                        $themes_list[] = array(
                            'slug'        => $theme_slug,
                            'name'        => $theme_obj->get( 'Name' ),
                            'version'     => $theme_obj->get( 'Version' ),
                            'author'      => $theme_obj->get( 'Author' ),
                            'is_active'   => $theme_slug === $theme->get_stylesheet(),
                            'is_parent'   => $is_child_theme && $theme_slug === $theme->get_template(),
                            'screenshot'  => $theme_obj->get_screenshot(),
                            'has_update'  => isset( $update_themes->response[ $theme_slug ] ),
                        );
                    }

                    $result['all_themes'] = $themes_list;
                    $result['total_themes'] = count( $themes_list );
                }

                // Include template files
                if ( $include_template_files ) {
                    $template_files = array();

                    // Get page templates
                    $page_templates = wp_get_theme()->get_page_templates();
                    foreach ( $page_templates as $file => $name ) {
                        $template_files[] = array(
                            'type' => 'page_template',
                            'file' => $file,
                            'name' => $name,
                        );
                    }

                    // Get common template files
                    $common_templates = array(
                        'index.php',
                        'header.php',
                        'footer.php',
                        'sidebar.php',
                        'single.php',
                        'page.php',
                        'archive.php',
                        'search.php',
                        '404.php',
                        'comments.php',
                        'front-page.php',
                        'home.php',
                        'category.php',
                        'tag.php',
                        'author.php',
                        'date.php',
                        'attachment.php',
                        'image.php',
                    );

                    $theme_dir = get_stylesheet_directory();
                    $parent_dir = get_template_directory();

                    foreach ( $common_templates as $template ) {
                        $in_child = file_exists( $theme_dir . '/' . $template );
                        $in_parent = $is_child_theme && file_exists( $parent_dir . '/' . $template );

                        if ( $in_child || $in_parent ) {
                            $template_files[] = array(
                                'type'      => 'template',
                                'file'      => $template,
                                'in_child'  => $in_child,
                                'in_parent' => $in_parent,
                            );
                        }
                    }

                    $result['template_files'] = $template_files;
                }

                // Include theme mods
                if ( $include_theme_mods ) {
                    $mods = get_theme_mods();

                    // Filter out potentially sensitive or very large data
                    $safe_mods = array();
                    if ( is_array( $mods ) ) {
                        foreach ( $mods as $key => $value ) {
                            // Skip nav_menu_locations as it can be large
                            if ( $key === 'nav_menu_locations' ) {
                                $safe_mods[ $key ] = '(menu locations data)';
                                continue;
                            }
                            // Skip sidebars_widgets
                            if ( $key === 'sidebars_widgets' ) {
                                $safe_mods[ $key ] = '(sidebar widgets data)';
                                continue;
                            }
                            // Limit string length
                            if ( is_string( $value ) && strlen( $value ) > 500 ) {
                                $safe_mods[ $key ] = substr( $value, 0, 500 ) . '...';
                            } else {
                                $safe_mods[ $key ] = $value;
                            }
                        }
                    }

                    $result['theme_mods'] = $safe_mods;
                }

                // Add theme support features
                $theme_supports = array(
                    'post-thumbnails'       => current_theme_supports( 'post-thumbnails' ),
                    'custom-header'         => current_theme_supports( 'custom-header' ),
                    'custom-background'     => current_theme_supports( 'custom-background' ),
                    'custom-logo'           => current_theme_supports( 'custom-logo' ),
                    'menus'                 => current_theme_supports( 'menus' ),
                    'automatic-feed-links'  => current_theme_supports( 'automatic-feed-links' ),
                    'html5'                 => current_theme_supports( 'html5' ),
                    'title-tag'             => current_theme_supports( 'title-tag' ),
                    'post-formats'          => current_theme_supports( 'post-formats' ),
                    'widgets'               => current_theme_supports( 'widgets' ),
                    'editor-styles'         => current_theme_supports( 'editor-styles' ),
                    'wp-block-styles'       => current_theme_supports( 'wp-block-styles' ),
                    'responsive-embeds'     => current_theme_supports( 'responsive-embeds' ),
                    'align-wide'            => current_theme_supports( 'align-wide' ),
                    'woocommerce'           => current_theme_supports( 'woocommerce' ),
                );

                $result['theme_supports'] = $theme_supports;

                return $result;
            },
            'permission_callback' => function() {
                return current_user_can( 'switch_themes' );
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
