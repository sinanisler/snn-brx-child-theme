<?php
/**
 * Get Site Info Ability
 * Registers the snn/get-site-info ability for the WordPress Abilities API
 * Provides comprehensive WordPress, PHP, and server health information
 */

// Register category
add_action( 'wp_abilities_api_categories_init', 'snn_register_system_category' );
function snn_register_system_category() {
    // Only register if not already registered
    if ( ! wp_has_ability_category( 'system' ) ) {
        wp_register_ability_category(
            'system',
            array(
                'label'       => __( 'System Information', 'snn' ),
                'description' => __( 'Abilities for retrieving system and site information.', 'snn' ),
            )
        );
    }
}

// Register ability
add_action( 'wp_abilities_api_init', 'snn_register_get_site_info_ability' );
function snn_register_get_site_info_ability() {
    wp_register_ability(
        'snn/get-site-info',
        array(
            'label'       => __( 'Get Site Info', 'wp-abilities' ),
            'description' => __( 'Retrieves comprehensive information about the WordPress site, server, and PHP environment.', 'wp-abilities' ),
            'category'    => 'system',
            'input_schema' => array(
                'type'       => 'object',
                'properties' => array(),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'wordpress'   => array( 'type' => 'object' ),
                    'server'      => array( 'type' => 'object' ),
                    'php'         => array( 'type' => 'object' ),
                    'database'    => array( 'type' => 'object' ),
                    'content'     => array( 'type' => 'object' ),
                    'security'    => array( 'type' => 'object' ),
                    'performance' => array( 'type' => 'object' ),
                ),
            ),
            'execute_callback' => 'snn_execute_get_site_info',
            'permission_callback' => function() {
                return current_user_can( 'manage_options' );
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

/**
 * Execute callback for get-site-info ability
 */
function snn_execute_get_site_info( $input ) {
    global $wp_version, $wpdb;

    // WordPress Information
    $theme = wp_get_theme();
    $parent_theme = $theme->parent() ? $theme->parent()->get( 'Name' ) : null;

    $wordpress_info = array(
        'version'           => $wp_version,
        'site_name'         => get_bloginfo( 'name' ),
        'site_description'  => get_bloginfo( 'description' ),
        'site_url'          => get_bloginfo( 'url' ),
        'home_url'          => home_url(),
        'admin_url'         => admin_url(),
        'admin_email'       => get_bloginfo( 'admin_email' ),
        'language'          => get_bloginfo( 'language' ),
        'locale'            => get_locale(),
        'timezone'          => get_option( 'timezone_string' ) ?: 'UTC' . get_option( 'gmt_offset' ),
        'date_format'       => get_option( 'date_format' ),
        'time_format'       => get_option( 'time_format' ),
        'multisite'         => is_multisite(),
        'permalink_structure' => get_option( 'permalink_structure' ) ?: 'Plain',
        'active_theme'      => array(
            'name'          => $theme->get( 'Name' ),
            'version'       => $theme->get( 'Version' ),
            'author'        => $theme->get( 'Author' ),
            'parent_theme'  => $parent_theme,
            'theme_uri'     => $theme->get( 'ThemeURI' ),
        ),
        'is_debug_mode'     => defined( 'WP_DEBUG' ) && WP_DEBUG,
        'is_debug_log'      => defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG,
        'is_debug_display'  => defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY,
        'memory_limit'      => defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : 'Not set',
        'max_memory_limit'  => defined( 'WP_MAX_MEMORY_LIMIT' ) ? WP_MAX_MEMORY_LIMIT : 'Not set',
    );

    // Server Information
    $server_info = array(
        'software'          => isset( $_SERVER['SERVER_SOFTWARE'] ) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown',
        'server_name'       => isset( $_SERVER['SERVER_NAME'] ) ? $_SERVER['SERVER_NAME'] : 'Unknown',
        'server_ip'         => isset( $_SERVER['SERVER_ADDR'] ) ? $_SERVER['SERVER_ADDR'] : 'Unknown',
        'server_protocol'   => isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'Unknown',
        'document_root'     => isset( $_SERVER['DOCUMENT_ROOT'] ) ? $_SERVER['DOCUMENT_ROOT'] : 'Unknown',
        'https'             => is_ssl(),
        'operating_system'  => PHP_OS,
        'os_family'         => PHP_OS_FAMILY,
        'hostname'          => function_exists( 'gethostname' ) ? gethostname() : 'Unknown',
        'server_time'       => current_time( 'mysql' ),
        'server_timezone'   => date_default_timezone_get(),
    );

    // PHP Information
    $php_info = array(
        'version'           => PHP_VERSION,
        'version_id'        => PHP_VERSION_ID,
        'major_version'     => PHP_MAJOR_VERSION,
        'minor_version'     => PHP_MINOR_VERSION,
        'release_version'   => PHP_RELEASE_VERSION,
        'sapi'              => PHP_SAPI,
        'memory_limit'      => ini_get( 'memory_limit' ),
        'max_execution_time' => ini_get( 'max_execution_time' ),
        'max_input_time'    => ini_get( 'max_input_time' ),
        'max_input_vars'    => ini_get( 'max_input_vars' ),
        'post_max_size'     => ini_get( 'post_max_size' ),
        'upload_max_filesize' => ini_get( 'upload_max_filesize' ),
        'max_file_uploads'  => ini_get( 'max_file_uploads' ),
        'display_errors'    => ini_get( 'display_errors' ),
        'error_reporting'   => error_reporting(),
        'default_charset'   => ini_get( 'default_charset' ),
        'allow_url_fopen'   => ini_get( 'allow_url_fopen' ) ? true : false,
        'allow_url_include' => ini_get( 'allow_url_include' ) ? true : false,
        'disabled_functions' => ini_get( 'disable_functions' ) ? explode( ',', ini_get( 'disable_functions' ) ) : array(),
        'loaded_extensions' => get_loaded_extensions(),
        'zend_version'      => zend_version(),
        'opcache_enabled'   => function_exists( 'opcache_get_status' ) && @opcache_get_status() ? true : false,
    );

    // Database Information
    $db_info = array(
        'server_version'    => $wpdb->db_version(),
        'client_version'    => function_exists( 'mysqli_get_client_info' ) ? mysqli_get_client_info() : 'Unknown',
        'database_name'     => $wpdb->dbname,
        'table_prefix'      => $wpdb->prefix,
        'charset'           => $wpdb->charset,
        'collation'         => $wpdb->collate,
        'total_tables'      => count( $wpdb->get_results( "SHOW TABLES" ) ),
    );

    // Content Statistics
    $post_count = wp_count_posts( 'post' );
    $page_count = wp_count_posts( 'page' );
    $attachment_count = wp_count_posts( 'attachment' );
    $comment_count = wp_count_comments();
    $users = count_users();

    // Get all post types counts
    $post_types = get_post_types( array( 'public' => true ), 'objects' );
    $post_type_counts = array();
    foreach ( $post_types as $post_type ) {
        $counts = wp_count_posts( $post_type->name );
        $post_type_counts[ $post_type->name ] = array(
            'label'     => $post_type->label,
            'published' => isset( $counts->publish ) ? (int) $counts->publish : 0,
            'draft'     => isset( $counts->draft ) ? (int) $counts->draft : 0,
            'pending'   => isset( $counts->pending ) ? (int) $counts->pending : 0,
            'private'   => isset( $counts->private ) ? (int) $counts->private : 0,
            'trash'     => isset( $counts->trash ) ? (int) $counts->trash : 0,
        );
    }

    $content_info = array(
        'posts'             => array(
            'published'     => (int) $post_count->publish,
            'draft'         => (int) $post_count->draft,
            'pending'       => (int) $post_count->pending,
            'private'       => (int) $post_count->private,
            'trash'         => (int) $post_count->trash,
        ),
        'pages'             => array(
            'published'     => (int) $page_count->publish,
            'draft'         => (int) $page_count->draft,
            'pending'       => (int) $page_count->pending,
            'private'       => (int) $page_count->private,
            'trash'         => (int) $page_count->trash,
        ),
        'media'             => array(
            'total'         => (int) $attachment_count->inherit,
            'trash'         => (int) $attachment_count->trash,
        ),
        'comments'          => array(
            'total'         => (int) $comment_count->total_comments,
            'approved'      => (int) $comment_count->approved,
            'pending'       => (int) $comment_count->moderated,
            'spam'          => (int) $comment_count->spam,
            'trash'         => (int) $comment_count->trash,
        ),
        'users'             => array(
            'total'         => $users['total_users'],
            'by_role'       => $users['avail_roles'],
        ),
        'categories'        => (int) wp_count_terms( 'category' ),
        'tags'              => (int) wp_count_terms( 'post_tag' ),
        'post_types'        => $post_type_counts,
    );

    // Active Plugins
    $active_plugins = get_option( 'active_plugins', array() );
    $plugins_info = array();
    foreach ( $active_plugins as $plugin_path ) {
        $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_path, false, false );
        $plugins_info[] = array(
            'name'      => $plugin_data['Name'],
            'version'   => $plugin_data['Version'],
            'author'    => $plugin_data['AuthorName'],
            'plugin_uri' => $plugin_data['PluginURI'],
        );
    }

    // Security Information
    $security_info = array(
        'ssl_enabled'       => is_ssl(),
        'file_editing_disabled' => defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT,
        'file_mods_disabled' => defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS,
        'auto_updates'      => array(
            'core'          => ( defined( 'WP_AUTO_UPDATE_CORE' ) ? WP_AUTO_UPDATE_CORE : 'minor' ),
            'plugins'       => (bool) get_option( 'auto_update_plugins', false ),
            'themes'        => (bool) get_option( 'auto_update_themes', false ),
        ),
        'users_can_register' => get_option( 'users_can_register' ) ? true : false,
        'default_role'      => get_option( 'default_role' ),
        'admin_user_exists' => username_exists( 'admin' ) ? true : false,
    );

    // Performance Information
    $uploads_dir = wp_upload_dir();
    $performance_info = array(
        'object_cache'      => array(
            'enabled'       => wp_using_ext_object_cache(),
            'type'          => wp_using_ext_object_cache() ? ( defined( 'WP_REDIS_DISABLED' ) ? 'Unknown' : ( class_exists( 'Redis' ) ? 'Redis' : ( class_exists( 'Memcached' ) ? 'Memcached' : 'Unknown' ) ) ) : 'None',
        ),
        'cron'              => array(
            'enabled'       => ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ),
            'alternate_cron' => defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON,
        ),
        'uploads_dir'       => array(
            'path'          => $uploads_dir['basedir'],
            'url'           => $uploads_dir['baseurl'],
            'writable'      => wp_is_writable( $uploads_dir['basedir'] ),
        ),
        'wp_content_writable' => wp_is_writable( WP_CONTENT_DIR ),
        'wp_debug_log_writable' => defined( 'WP_DEBUG_LOG' ) && is_string( WP_DEBUG_LOG ) ? wp_is_writable( dirname( WP_DEBUG_LOG ) ) : wp_is_writable( WP_CONTENT_DIR ),
    );

    // Disk Space (if available)
    if ( function_exists( 'disk_free_space' ) && function_exists( 'disk_total_space' ) ) {
        $disk_path = ABSPATH;
        $free_space = @disk_free_space( $disk_path );
        $total_space = @disk_total_space( $disk_path );
        if ( $free_space !== false && $total_space !== false ) {
            $performance_info['disk_space'] = array(
                'free'      => size_format( $free_space ),
                'total'     => size_format( $total_space ),
                'used'      => size_format( $total_space - $free_space ),
                'percent_used' => round( ( ( $total_space - $free_space ) / $total_space ) * 100, 2 ) . '%',
            );
        }
    }

    // Constants
    $important_constants = array(
        'ABSPATH'           => ABSPATH,
        'WP_CONTENT_DIR'    => WP_CONTENT_DIR,
        'WP_PLUGIN_DIR'     => WP_PLUGIN_DIR,
        'WPINC'             => WPINC,
        'WP_DEBUG'          => defined( 'WP_DEBUG' ) ? WP_DEBUG : false,
        'WP_DEBUG_LOG'      => defined( 'WP_DEBUG_LOG' ) ? WP_DEBUG_LOG : false,
        'WP_DEBUG_DISPLAY'  => defined( 'WP_DEBUG_DISPLAY' ) ? WP_DEBUG_DISPLAY : true,
        'SCRIPT_DEBUG'      => defined( 'SCRIPT_DEBUG' ) ? SCRIPT_DEBUG : false,
        'WP_CACHE'          => defined( 'WP_CACHE' ) ? WP_CACHE : false,
        'COMPRESS_CSS'      => defined( 'COMPRESS_CSS' ) ? COMPRESS_CSS : false,
        'COMPRESS_SCRIPTS'  => defined( 'COMPRESS_SCRIPTS' ) ? COMPRESS_SCRIPTS : false,
        'CONCATENATE_SCRIPTS' => defined( 'CONCATENATE_SCRIPTS' ) ? CONCATENATE_SCRIPTS : true,
        'WP_LOCAL_DEV'      => defined( 'WP_LOCAL_DEV' ) ? WP_LOCAL_DEV : false,
    );

    return array(
        'wordpress'     => $wordpress_info,
        'server'        => $server_info,
        'php'           => $php_info,
        'database'      => $db_info,
        'content'       => $content_info,
        'active_plugins' => $plugins_info,
        'security'      => $security_info,
        'performance'   => $performance_info,
        'constants'     => $important_constants,
    );
}
