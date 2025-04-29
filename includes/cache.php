<?php


// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// --- Constants ---
define( 'SNN_CACHE_VERSION', '0.2.1' ); // Updated version
define( 'SNN_CACHE_DIR_NAME', 'snn-cache-enhanced' );
define( 'SNN_CACHE_BASE_DIR', WP_CONTENT_DIR . '/cache' );
define( 'SNN_CACHE_DIR', SNN_CACHE_BASE_DIR . '/' . SNN_CACHE_DIR_NAME );
define( 'SNN_CACHE_SETTINGS_SLUG', 'snn-cache-settings' );
define( 'SNN_CACHE_OPTION_GROUP', 'snn_cache_options' );
define( 'SNN_CACHE_OPTIONS_KEY', 'snn_cache_settings' );

// --- Activation / Deactivation Hooks ---

/**
 * Plugin activation hook. Creates cache directories and sets default options.
 */
function snn_cache_activate() {
    if ( ! file_exists( SNN_CACHE_BASE_DIR ) ) {
        @mkdir( SNN_CACHE_BASE_DIR, 0755 );
        @file_put_contents( SNN_CACHE_BASE_DIR . '/index.php', '<?php // Silence is golden.' );
    }

    if ( ! file_exists( SNN_CACHE_DIR ) ) {
        if ( @mkdir( SNN_CACHE_DIR, 0755, true ) ) {
            @file_put_contents( SNN_CACHE_DIR . '/index.php', '<?php // Silence is golden.' );
            @file_put_contents( SNN_CACHE_DIR . '/.htaccess', 'Deny from all' );
        } else {
            error_log( "SNN Cache Error: Could not create cache directory: " . SNN_CACHE_DIR );
            // Consider adding an admin notice here
        }
    }

    // Set default options if they don't exist
    if ( false === get_option( SNN_CACHE_OPTIONS_KEY ) ) {
        $default_options = array(
            'enabled'           => 0, // Disabled by default
            'cache_ttl'         => 3600, // Default 1 hour
            'enable_gzip'       => 1,
            'exclude_urls'      => "/cart/\n/checkout/\n/my-account/", // Common e-commerce exclusions
            'exclude_cookies'   => "wordpress_logged_in_\nwp-postpass_", // Standard WP cookies
            'exclude_agents'    => "",
            'clear_on_update'   => 1,
            'clear_on_comment'  => 1,
        );
        update_option( SNN_CACHE_OPTIONS_KEY, $default_options );
    }
}
register_activation_hook( __FILE__, 'snn_cache_activate' );

/**
 * Plugin deactivation hook. Clears the cache.
 */
function snn_cache_deactivate() {
    snn_cache_clear_all();
}
register_deactivation_hook( __FILE__, 'snn_cache_deactivate' );

// --- Core Caching Logic ---

/**
 * Retrieves plugin options with defaults.
 *
 * @return array The plugin options.
 */
function snn_cache_get_options() {
    $defaults = array(
        'enabled'           => 0,
        'cache_ttl'         => 3600,
        'enable_gzip'       => 1,
        'exclude_urls'      => '',
        'exclude_cookies'   => '',
        'exclude_agents'    => '',
        'clear_on_update'   => 1,
        'clear_on_comment'  => 1,
    );
    return wp_parse_args( get_option( SNN_CACHE_OPTIONS_KEY, $defaults ), $defaults );
}

/**
 * Checks if the current request is eligible for caching based on settings.
 *
 * @return bool True if cachable, false otherwise.
 */
function snn_cache_is_cachable() {
    $options = snn_cache_get_options();

    if ( empty( $options['enabled'] ) ) return false;
    if ( is_user_logged_in() ) return false;
    if ( is_admin() ) return false;
    if ( is_feed() || is_trackback() ) return false;
    if ( $_SERVER['REQUEST_METHOD'] !== 'GET' ) return false;
    if ( is_preview() ) return false;
    if ( is_search() ) return false; // Typically dynamic
    if ( defined('DONOTCACHEPAGE') && DONOTCACHEPAGE ) return false;

    // Check for query strings (allow exceptions later if needed)
    if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
        // Simple approach: disallow all query strings for now
        return false;
    }

    // Check excluded URLs
    if ( ! empty( $options['exclude_urls'] ) ) {
        $excluded_urls = explode( "\n", trim( $options['exclude_urls'] ) );
        $current_uri = $_SERVER['REQUEST_URI'];
        foreach ( $excluded_urls as $pattern ) {
            $pattern = trim( $pattern );
            if ( empty( $pattern ) ) continue;
            // Use # as delimiter for preg_match
            if ( preg_match( '#' . str_replace( '#', '\#', $pattern ) . '#i', $current_uri ) ) {
                return false;
            }
        }
    }

    // Check excluded cookies
    if ( ! empty( $options['exclude_cookies'] ) ) {
        $excluded_cookies = explode( "\n", trim( $options['exclude_cookies'] ) );
        foreach ( $excluded_cookies as $cookie_name ) {
            $cookie_name = trim( $cookie_name );
            if ( empty( $cookie_name ) ) continue;
            // Check if any cookie starts with the excluded name (handles WP auth cookies)
            foreach ( $_COOKIE as $key => $value ) {
                 if ( strpos( $key, $cookie_name ) === 0 ) {
                    return false; // Found an excluded cookie pattern match
                 }
            }
        }
    }

    // Check excluded user agents
    if ( ! empty( $options['exclude_agents'] ) && isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
        $excluded_agents = explode( "\n", trim( $options['exclude_agents'] ) );
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        foreach ( $excluded_agents as $agent_pattern ) {
            $agent_pattern = trim( $agent_pattern );
            if ( empty( $agent_pattern ) ) continue;
            if ( stripos( $user_agent, $agent_pattern ) !== false ) {
                return false;
            }
        }
    }

    // WooCommerce specific checks (basic)
    if ( function_exists('is_woocommerce') ) {
        if ( is_cart() || is_checkout() || is_account_page() ) {
            return false;
        }
    }

    return true;
}

/**
 * Generates a unique hash key for the current request.
 *
 * @return string Cache key hash.
 */
function snn_cache_get_request_hash() {
    $scheme = isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    // Consider adding Vary headers like Accept-Encoding later if needed
    $key_string = $scheme . '://' . $host . $uri;
    return md5( $key_string ); // MD5 is fast and usually sufficient for uniqueness here
}

/**
 * Generates the full path for the cache file.
 *
 * @param bool $gzip Whether to get the path for the gzipped version.
 * @return string|false The full path to the cache file or false on failure.
 */
function snn_cache_get_cache_filepath( $gzip = false ) {
    if ( ! defined('SNN_CACHE_DIR') || ! SNN_CACHE_DIR ) {
        return false;
    }

    $hash = snn_cache_get_request_hash();
    // Use first 2 chars of hash for subdirectory to avoid too many files in one dir
    $subdir = substr( $hash, 0, 2 );
    $filename = $hash . '.html';
    if ( $gzip ) {
        $filename .= '.gz';
    }

    $filepath = SNN_CACHE_DIR . '/' . $subdir . '/' . $filename;

    // Basic security check (realpath requires dir existence, check dirname)
    $dir_path = dirname( $filepath );
    if ( strpos( $dir_path, SNN_CACHE_DIR ) !== 0 ) {
         error_log("SNN Cache Security Alert: Potential cache path issue: " . $filepath);
         return false;
    }

    return $filepath;
}


/**
 * Tries to serve a cached file if it exists and is valid (not expired).
 */
function snn_cache_serve_cached_file() {
    if ( ! snn_cache_is_cachable() ) {
        header( 'X-SNN-Cache: Disabled' ); // Indicate why no cache served
        return;
    }

    $options = snn_cache_get_options();
    $filepath_gz = false;
    $filepath_plain = false;
    $serve_gzip = false;
    $filepath_to_serve = null; // Initialize

    // Check for Gzip support and if enabled
    if ( $options['enable_gzip'] && isset( $_SERVER['HTTP_ACCEPT_ENCODING'] ) && strpos( $_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip' ) !== false ) {
        $filepath_gz = snn_cache_get_cache_filepath( true );
        if ( $filepath_gz && file_exists( $filepath_gz ) ) {
             $serve_gzip = true;
             $filepath_to_serve = $filepath_gz;
        }
    }

    // If Gzip not suitable or not found, check for plain file
    if ( ! $serve_gzip ) {
        $filepath_plain = snn_cache_get_cache_filepath( false );
        if ( $filepath_plain && file_exists( $filepath_plain ) ) {
            $filepath_to_serve = $filepath_plain;
        }
    }

    if ( isset( $filepath_to_serve ) && file_exists( $filepath_to_serve ) ) {
        $file_mtime = @filemtime( $filepath_to_serve );
        $ttl = absint( $options['cache_ttl'] );

        // Check expiration
        if ( $file_mtime && ( time() - $file_mtime ) < $ttl ) {
            // Serve the file
            header( 'X-SNN-Cache: Hit' );
            header( 'Content-Type: text/html; charset=' . get_option('blog_charset') );
            header( 'Vary: Accept-Encoding' ); // Important if serving gzip/plain conditionally
            header( 'Cache-Control: public, max-age=' . $ttl );
            header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + $ttl ) . ' GMT' );
            header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $file_mtime ) . ' GMT');
            // Add ETag maybe? ETag: "hash-of-content"

            if ( $serve_gzip ) {
                header( 'Content-Encoding: gzip' );
                header( 'Content-Length: ' . filesize( $filepath_to_serve ) );
            }

            // Read the file content
            $content = @file_get_contents( $filepath_to_serve );

            // If gzipped, decompress temporarily to add comment (if needed, usually comment is already there)
            // The comment is added during *saving*, so it should already be in the cached file.
            // We just need to serve the file as is.

            // Output the content
            echo $content;
            exit;
        } else {
            // Cache file expired, delete it (both versions)
            if ( $filepath_gz && file_exists( $filepath_gz ) ) @unlink( $filepath_gz );
            if ( $filepath_plain && file_exists( $filepath_plain ) ) @unlink( $filepath_plain );
            header( 'X-SNN-Cache: Expired' );
        }
    } else {
         header( 'X-SNN-Cache: Miss' );
    }
}

/**
 * Output buffer callback. Saves the generated HTML to cache files (plain and gzipped).
 * Adds a timestamp comment before </body>.
 *
 * @param string $buffer The captured HTML output.
 * @return string The original buffer.
 */
function snn_cache_save_page( $buffer ) {
    // Re-check conditions and buffer validity
    if ( ! snn_cache_is_cachable() || strlen( $buffer ) < 256 || strpos( $buffer, '<html' ) === false || http_response_code() !== 200 ) {
        return $buffer;
    }

    $options = snn_cache_get_options();
    $filepath_plain = snn_cache_get_cache_filepath( false );
    $filepath_gz = snn_cache_get_cache_filepath( true );

    if ( ! $filepath_plain || ! $filepath_gz ) {
        return $buffer;
    }

    // Ensure the directory exists (for both plain and gz, path is same)
    $directory = dirname( $filepath_plain );
    if ( ! is_dir( $directory ) ) {
        if ( ! @mkdir( $directory, 0755, true ) ) {
            error_log( "SNN Cache Error: Could not create cache sub-directory: " . $directory );
            return $buffer;
        }
        @file_put_contents( $directory . '/index.php', '<?php // Silence is golden.' );
    }

    // --- Add the timestamp comment ---
    // *** FIXED the sprintf format string ***
    $timestamp_comment = sprintf(
        "\n\n",
        SNN_CACHE_VERSION,
        gmdate('Y-m-d H:i:s', time()) // Current time in GMT
    );

    // Try to insert before </body>, case-insensitive
    $body_end_pos = stripos( $buffer, '</body>' );
    if ( false !== $body_end_pos ) {
        $buffer_with_stamp = substr_replace( $buffer, $timestamp_comment, $body_end_pos, 0 );
    } else {
        // Fallback: Append to the end if </body> not found
        $buffer_with_stamp = $buffer . $timestamp_comment;
    }
    // --- End timestamp comment addition ---


    // Save plain HTML file
    $result_plain = @file_put_contents( $filepath_plain, $buffer_with_stamp );
    if ( false === $result_plain ) {
        error_log( "SNN Cache Error: Could not write to plain cache file: " . $filepath_plain );
    }

    // Save gzipped file if enabled
    if ( $options['enable_gzip'] ) {
        $gzipped_buffer = @gzencode( $buffer_with_stamp, 6 ); // Compression level 6
        if ( $gzipped_buffer !== false ) {
             $result_gz = @file_put_contents( $filepath_gz, $gzipped_buffer );
             if ( false === $result_gz ) {
                 error_log( "SNN Cache Error: Could not write to gzipped cache file: " . $filepath_gz );
             }
        } else {
             error_log( "SNN Cache Error: Failed to gzip buffer for: " . $filepath_gz );
        }
    }

    return $buffer; // Return original buffer to browser
}

/**
 * Starts the caching process: checks for cache or starts output buffering.
 */
function snn_cache_start() {
    // Try serving cache first
    snn_cache_serve_cached_file();

    // If not served and cachable, start output buffering
    if ( snn_cache_is_cachable() ) {
        ob_start( 'snn_cache_save_page' );
    }
}
// Hook early, but after conditional tags are available.
add_action( 'template_redirect', 'snn_cache_start', 0 );


// --- Cache Clearing ---

/**
 * Deletes a directory and its contents recursively.
 *
 * @param string $dir Path to the directory.
 * @return bool True on success, false on failure.
 */
function snn_cache_delete_directory( $dir ) {
    if ( ! file_exists( $dir ) ) return true;
    if ( ! is_dir( $dir ) ) return @unlink( $dir );

    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ( $iterator as $file ) {
            if ( $file->isDir() ) {
                @rmdir( $file->getRealPath() );
            } else {
                @unlink( $file->getRealPath() );
            }
        }
        return @rmdir( $dir );
    } catch ( Exception $e ) {
        error_log("SNN Cache Error deleting directory {$dir}: " . $e->getMessage());
        return false;
    }
}

/**
 * Clears the entire SNN Cache directory, preserving essential files.
 *
 * @return bool True if successful (or directory doesn't exist), false on partial/full failure.
 */
function snn_cache_clear_all() {
    if ( ! defined('SNN_CACHE_DIR') || ! is_dir(SNN_CACHE_DIR) ) {
        return true; // Nothing to clear
    }

    $success = true;
    try {
        $iterator = new DirectoryIterator( SNN_CACHE_DIR );
        foreach ( $iterator as $fileinfo ) {
            if ( $fileinfo->isDot() ) continue;

            $filename = $fileinfo->getFilename();
            if ( $filename === 'index.php' || $filename === '.htaccess' ) {
                continue; // Skip protection files
            }

            $pathname = $fileinfo->getPathname();
            if ( $fileinfo->isDir() ) {
                if ( ! snn_cache_delete_directory( $pathname ) ) {
                    $success = false;
                    error_log("SNN Cache Error: Failed to delete sub-directory: " . $pathname);
                }
            } else {
                if ( ! @unlink( $pathname ) ) {
                    $success = false;
                    error_log("SNN Cache Error: Failed to delete file: " . $pathname);
                }
            }
        }
    } catch ( Exception $e ) {
         error_log("SNN Cache Error clearing cache: " . $e->getMessage());
         $success = false;
    }
    return $success;
}

/**
 * Clears the cache for a specific URL.
 *
 * @param string $url The URL to clear.
 */
function snn_cache_clear_url( $url ) {
     if ( empty($url) ) return;

     // Store original server vars if they exist
     $original_https = $_SERVER['HTTPS'] ?? null;
     $original_host = $_SERVER['HTTP_HOST'] ?? null;
     $original_uri = $_SERVER['REQUEST_URI'] ?? null;

     $url_parts = parse_url( $url );
     if ( ! $url_parts ) {
         // Restore original server vars before returning
         if ($original_https !== null) $_SERVER['HTTPS'] = $original_https; else unset($_SERVER['HTTPS']);
         if ($original_host !== null) $_SERVER['HTTP_HOST'] = $original_host; else unset($_SERVER['HTTP_HOST']);
         if ($original_uri !== null) $_SERVER['REQUEST_URI'] = $original_uri; else unset($_SERVER['REQUEST_URI']);
         return;
     }

     // Simulate server variables needed for hash generation
     $_SERVER['HTTPS'] = isset($url_parts['scheme']) && $url_parts['scheme'] === 'https' ? 'on' : 'off';
     $_SERVER['HTTP_HOST'] = $url_parts['host'] ?? $original_host ?? 'localhost'; // Use original host as fallback
     $_SERVER['REQUEST_URI'] = $url_parts['path'] ?? '/';
     if (isset($url_parts['query'])) {
         $_SERVER['REQUEST_URI'] .= '?' . $url_parts['query'];
     }

     $filepath_plain = snn_cache_get_cache_filepath( false );
     $filepath_gz = snn_cache_get_cache_filepath( true );

     // Clean up simulation by restoring original server vars
     if ($original_https !== null) $_SERVER['HTTPS'] = $original_https; else unset($_SERVER['HTTPS']);
     if ($original_host !== null) $_SERVER['HTTP_HOST'] = $original_host; else unset($_SERVER['HTTP_HOST']);
     if ($original_uri !== null) $_SERVER['REQUEST_URI'] = $original_uri; else unset($_SERVER['REQUEST_URI']);

     if ( $filepath_plain && file_exists( $filepath_plain ) ) {
         @unlink( $filepath_plain );
     }
     if ( $filepath_gz && file_exists( $filepath_gz ) ) {
         @unlink( $filepath_gz );
     }
}

/**
 * Clears cache for a specific post ID and potentially related archives.
 *
 * @param int $post_id Post ID.
 */
function snn_cache_clear_post_related( $post_id ) {
    // Ensure post ID is valid
    if ( ! $post_id || ! is_numeric($post_id) ) return;
    $post_id = absint($post_id);
    if ( ! $post_id ) return;

    $post_url = get_permalink( $post_id );
    if ( $post_url && ! is_wp_error($post_url) ) {
        snn_cache_clear_url( $post_url );
    }

    // Clear homepage cache
    $home_url = home_url('/');
    snn_cache_clear_url( $home_url );

    // Clear archives (categories, tags) associated with the post
    $post_type = get_post_type( $post_id );
    if ( $post_type ) { // Check if post type exists
        $taxonomies = get_object_taxonomies( $post_type );
        if ( ! empty($taxonomies) ) {
            foreach ( $taxonomies as $taxonomy ) {
                $terms = wp_get_post_terms( $post_id, $taxonomy );
                if ( ! is_wp_error( $terms ) && ! empty($terms) ) {
                    foreach ( $terms as $term ) {
                        $term_link = get_term_link( $term, $taxonomy );
                        if ( ! is_wp_error( $term_link ) ) {
                            snn_cache_clear_url( $term_link );
                        }
                    }
                }
            }
        }

        // Clear post type archive if it exists
        if ( $post_type_archive_link = get_post_type_archive_link( $post_type ) ) {
            snn_cache_clear_url( $post_type_archive_link );
        }
    }

    // Clear author archive
    $post = get_post($post_id);
    if ($post && $post->post_author && $author_link = get_author_posts_url($post->post_author)) {
        snn_cache_clear_url($author_link);
    }

    // Consider clearing date archives if relevant
}

/**
 * Clears cache when a term (category, tag) is updated.
 *
 * @param int    $term_id Term ID.
 * @param int    $tt_id   Term taxonomy ID.
 * @param string $taxonomy Taxonomy slug.
 */
function snn_cache_clear_term_related( $term_id, $tt_id, $taxonomy ) {
    $term_link = get_term_link( (int) $term_id, $taxonomy );
    if ( ! is_wp_error( $term_link ) ) {
        snn_cache_clear_url( $term_link );
    }
    // Also clear homepage as it might list posts from this term
    snn_cache_clear_url( home_url('/') );
}


// --- Automatic Cache Clearing Hooks ---
// Get options *after* they are potentially defined/updated
function snn_cache_register_clear_hooks() {
    $options = snn_cache_get_options();

    if ( ! empty( $options['clear_on_update'] ) ) {
        add_action( 'save_post', 'snn_cache_clear_post_related', 99 );
        add_action( 'delete_post', 'snn_cache_clear_post_related', 99 );
        add_action( 'wp_trash_post', 'snn_cache_clear_post_related', 99 );
        add_action( 'untrash_post', 'snn_cache_clear_post_related', 99 );

        add_action( 'edit_term', 'snn_cache_clear_term_related', 99, 3 );
        add_action( 'delete_term', 'snn_cache_clear_term_related', 99, 3 ); // Note: delete_term passes tt_id as second param before WP 4.2

        add_action( 'switch_theme', 'snn_cache_clear_all', 99 );
        // Clear cache when plugin settings are saved
        add_action( 'update_option_' . SNN_CACHE_OPTIONS_KEY, 'snn_cache_clear_all_on_option_update', 10, 0 ); // Use specific wrapper
    }

    if ( ! empty( $options['clear_on_comment'] ) ) {
        add_action( 'comment_post', 'snn_cache_clear_on_comment_post', 99, 2 );
        add_action( 'edit_comment', 'snn_cache_clear_on_comment_change', 99 );
        add_action( 'delete_comment', 'snn_cache_clear_on_comment_change', 99 );
        add_action( 'trash_comment', 'snn_cache_clear_on_comment_change', 99 );
        add_action( 'untrash_comment', 'snn_cache_clear_on_comment_change', 99 );
        add_action( 'spam_comment', 'snn_cache_clear_on_comment_change', 99 );
        add_action( 'unspam_comment', 'snn_cache_clear_on_comment_change', 99 );
        add_action( 'transition_comment_status', 'snn_cache_clear_on_comment_transition', 99, 3 );
    }
}
add_action( 'plugins_loaded', 'snn_cache_register_clear_hooks' ); // Register hooks after options are loaded

/**
 * Wrapper function to clear cache on option update.
 * Ensures snn_cache_clear_all is available.
 */
function snn_cache_clear_all_on_option_update() {
    snn_cache_clear_all();
}


/**
 * Clear cache for a post when a new comment is posted (if approved).
 */
function snn_cache_clear_on_comment_post( $comment_id, $comment_approved ) {
    // Check for integer 1 or string 'approve' for compatibility
    if ( $comment_approved === 1 || $comment_approved === 'approve' || $comment_approved === true ) {
        $comment = get_comment( $comment_id );
        if ( $comment && ! empty( $comment->comment_post_ID ) ) {
            snn_cache_clear_post_related( $comment->comment_post_ID );
        }
    }
}

/**
 * Clear cache for a post when a comment is changed (edited, deleted, etc.).
 */
function snn_cache_clear_on_comment_change( $comment_id ) {
    $comment = get_comment( $comment_id );
    // Check if comment exists, as it might be called after deletion
    if ( $comment && ! empty( $comment->comment_post_ID ) ) {
        snn_cache_clear_post_related( $comment->comment_post_ID );
    } else {
        // If comment doesn't exist (e.g., after delete_comment), we can't easily get the post ID.
        // A full cache clear might be too aggressive.
        // For now, we only clear if we can reliably get the comment object and post ID.
    }
}

/**
 * Clear cache for a post when a comment's status transitions.
 */
function snn_cache_clear_on_comment_transition( $new_status, $old_status, $comment ) {
    if ( $comment && ! empty( $comment->comment_post_ID ) ) {
        // Clear if the comment becomes approved, or goes from approved to something else
        if ( $new_status === 'approved' || $old_status === 'approved' ) {
             snn_cache_clear_post_related( $comment->comment_post_ID );
        }
    }
}


// --- Admin Settings Page ---

/**
 * Adds the SNN Cache menu item under Settings.
 */
function snn_cache_add_admin_menu() {
    add_options_page(
        __( 'SNN Cache Settings', 'snn-cache' ),
        __( 'SNN Cache', 'snn-cache' ),
        'manage_options',
        SNN_CACHE_SETTINGS_SLUG,
        'snn_cache_settings_page_html'
    );
}
add_action( 'admin_menu', 'snn_cache_add_admin_menu' );

/**
 * Registers plugin settings using the Settings API.
 */
function snn_cache_register_settings() {
    register_setting(
        SNN_CACHE_OPTION_GROUP,
        SNN_CACHE_OPTIONS_KEY,
        'snn_cache_sanitize_options'
    );

    // --- General Section ---
    add_settings_section(
        'snn_cache_general_section',
        __( 'General Settings', 'snn-cache' ),
        null,
        SNN_CACHE_SETTINGS_SLUG
    );

    add_settings_field( 'snn_cache_field_enabled', __( 'Enable Cache', 'snn-cache' ), 'snn_cache_field_checkbox_cb', SNN_CACHE_SETTINGS_SLUG, 'snn_cache_general_section', ['id' => 'enabled', 'desc' => __( 'Enable static page caching.', 'snn-cache' )] );
    add_settings_field( 'snn_cache_field_ttl', __( 'Cache TTL (Seconds)', 'snn-cache' ), 'snn_cache_field_number_cb', SNN_CACHE_SETTINGS_SLUG, 'snn_cache_general_section', ['id' => 'cache_ttl', 'desc' => __( 'Time in seconds cached files remain valid. Default: 3600 (1 hour). Minimum: 60.', 'snn-cache' )] );
    add_settings_field( 'snn_cache_field_gzip', __( 'Enable Gzip', 'snn-cache' ), 'snn_cache_field_checkbox_cb', SNN_CACHE_SETTINGS_SLUG, 'snn_cache_general_section', ['id' => 'enable_gzip', 'desc' => __( 'Store and serve gzipped cache files if browser supports it.', 'snn-cache' )] );

    // --- Exclusion Section ---
    add_settings_section(
        'snn_cache_exclusion_section',
        __( 'Exclusion Rules', 'snn-cache' ),
        'snn_cache_exclusion_section_cb', // Callback for section description
        SNN_CACHE_SETTINGS_SLUG
    );

    add_settings_field( 'snn_cache_field_exclude_urls', __( 'Exclude URLs Containing', 'snn-cache' ), 'snn_cache_field_textarea_cb', SNN_CACHE_SETTINGS_SLUG, 'snn_cache_exclusion_section', ['id' => 'exclude_urls', 'desc' => __( 'Enter partial URLs or patterns (one per line) to exclude from caching. Uses regex matching. Example: `/cart/` or `product-category/sale`', 'snn-cache' )] );
    add_settings_field( 'snn_cache_field_exclude_cookies', __( 'Exclude Based on Cookies', 'snn-cache' ), 'snn_cache_field_textarea_cb', SNN_CACHE_SETTINGS_SLUG, 'snn_cache_exclusion_section', ['id' => 'exclude_cookies', 'desc' => __( 'Enter cookie name prefixes (one per line). If a request contains a cookie starting with this name, the page will not be cached. Example: `my_custom_cookie` or `wordpress_logged_in_`', 'snn-cache' )] );
    add_settings_field( 'snn_cache_field_exclude_agents', __( 'Exclude User Agents Containing', 'snn-cache' ), 'snn_cache_field_textarea_cb', SNN_CACHE_SETTINGS_SLUG, 'snn_cache_exclusion_section', ['id' => 'exclude_agents', 'desc' => __( 'Enter strings (one per line) found in user agent headers to exclude (case-insensitive). Example: `Googlebot` or `MobileBrowser`', 'snn-cache' )] );

    // --- Cache Clearing Section ---
     add_settings_section(
        'snn_cache_clearing_section',
        __( 'Automatic Clearing', 'snn-cache' ),
        null,
        SNN_CACHE_SETTINGS_SLUG
    );
    add_settings_field( 'snn_cache_field_clear_update', __( 'Clear on Content Update', 'snn-cache' ), 'snn_cache_field_checkbox_cb', SNN_CACHE_SETTINGS_SLUG, 'snn_cache_clearing_section', ['id' => 'clear_on_update', 'desc' => __( 'Automatically clear relevant cache when posts, pages, or terms are updated/deleted.', 'snn-cache' )] );
    add_settings_field( 'snn_cache_field_clear_comment', __( 'Clear on Comment Change', 'snn-cache' ), 'snn_cache_field_checkbox_cb', SNN_CACHE_SETTINGS_SLUG, 'snn_cache_clearing_section', ['id' => 'clear_on_comment', 'desc' => __( 'Automatically clear post cache when comments are added, approved, or changed.', 'snn-cache' )] );

}
add_action( 'admin_init', 'snn_cache_register_settings' );

/**
 * Sanitizes the options array before saving.
 *
 * @param array $input Raw input data from the form.
 * @return array Sanitized options array.
 */
function snn_cache_sanitize_options( $input ) {
    $options = snn_cache_get_options(); // Get existing options to preserve unset checkboxes
    $sanitized_options = array();

    $sanitized_options['enabled'] = isset( $input['enabled'] ) ? 1 : 0;
    $sanitized_options['cache_ttl'] = isset( $input['cache_ttl'] ) ? absint( $input['cache_ttl'] ) : 3600;
    $sanitized_options['enable_gzip'] = isset( $input['enable_gzip'] ) ? 1 : 0;
    $sanitized_options['clear_on_update'] = isset( $input['clear_on_update'] ) ? 1 : 0;
    $sanitized_options['clear_on_comment'] = isset( $input['clear_on_comment'] ) ? 1 : 0;

    // Sanitize textareas (allow basic patterns, slashes, etc.)
    $sanitized_options['exclude_urls'] = isset( $input['exclude_urls'] ) ? sanitize_textarea_field( $input['exclude_urls'] ) : '';
    $sanitized_options['exclude_cookies'] = isset( $input['exclude_cookies'] ) ? sanitize_textarea_field( $input['exclude_cookies'] ) : '';
    $sanitized_options['exclude_agents'] = isset( $input['exclude_agents'] ) ? sanitize_textarea_field( $input['exclude_agents'] ) : '';

    // Prevent extremely low TTL
    if ( $sanitized_options['cache_ttl'] < 60 ) {
         $sanitized_options['cache_ttl'] = 60; // Minimum 1 minute
         add_settings_error('snn-cache-notices', 'ttl-too-low', __('Cache TTL was set too low and has been adjusted to 60 seconds.', 'snn-cache'), 'warning');
    }

    return $sanitized_options;
}

/**
 * Callback for Exclusion Rules section description.
 */
function snn_cache_exclusion_section_cb() {
    echo '<p>' . esc_html__( 'Define rules to prevent specific pages or visitors from being served cached content.', 'snn-cache' ) . '</p>';
    echo '<p>' . esc_html__( 'Note: Logged-in users, admin pages, feeds, trackbacks, previews, search results, and requests with query strings are always excluded by default.', 'snn-cache' ) . '</p>';
}


/**
 * Generic callback for rendering checkbox fields.
 * Args requires 'id' and 'desc'.
 */
function snn_cache_field_checkbox_cb( $args ) {
    $options = snn_cache_get_options();
    $id = $args['id'];
    $desc = $args['desc'];
    $checked = isset( $options[$id] ) && $options[$id] == 1 ? 'checked' : '';
    ?>
    <label for="snn_cache_<?php echo esc_attr($id); ?>">
        <input type="checkbox" id="snn_cache_<?php echo esc_attr($id); ?>" name="<?php echo SNN_CACHE_OPTIONS_KEY; ?>[<?php echo esc_attr($id); ?>]" value="1" <?php echo $checked; ?>>
        <?php echo esc_html( $desc ); ?>
    </label>
    <?php
}

/**
 * Generic callback for rendering number input fields.
 * Args requires 'id' and 'desc'.
 */
function snn_cache_field_number_cb( $args ) {
    $options = snn_cache_get_options();
    $id = $args['id'];
    $desc = $args['desc'];
    $value = isset( $options[$id] ) ? intval( $options[$id] ) : 0;
    // Ensure default value meets minimum if not set
    if ($id === 'cache_ttl' && $value < 60) {
        $value = max(60, $value); // Use max to handle potential 0 default correctly
    }
    ?>
    <input type="number" id="snn_cache_<?php echo esc_attr($id); ?>" name="<?php echo SNN_CACHE_OPTIONS_KEY; ?>[<?php echo esc_attr($id); ?>]" value="<?php echo esc_attr( $value ); ?>" min="60" step="1" class="small-text">
    <p class="description"><?php echo esc_html( $desc ); ?></p>
    <?php
}

/**
 * Generic callback for rendering textarea fields.
 * Args requires 'id' and 'desc'.
 */
function snn_cache_field_textarea_cb( $args ) {
    $options = snn_cache_get_options();
    $id = $args['id'];
    $desc = $args['desc'];
    $value = isset( $options[$id] ) ? $options[$id] : '';
    ?>
    <textarea id="snn_cache_<?php echo esc_attr($id); ?>" name="<?php echo SNN_CACHE_OPTIONS_KEY; ?>[<?php echo esc_attr($id); ?>]" rows="5" cols="50" class="large-text code"><?php echo esc_textarea( $value ); ?></textarea>
    <p class="description"><?php echo wp_kses_post( $desc ); // Allow basic HTML in description for clarity ?></p>
    <?php
}


/**
 * Handles the 'Clear Cache' button action via admin_init.
 */
function snn_cache_handle_clear_cache_action() {
    // Check if our specific action is set (from the button press)
    if ( isset( $_POST['snn_cache_action'] ) && $_POST['snn_cache_action'] === 'clear_cache' ) {

        // Verify nonce
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'snn_cache_clear_cache_nonce' ) ) {
            wp_die( __( 'Security check failed!', 'snn-cache' ) );
        }

        // Check user capability
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have permission to clear the cache.', 'snn-cache' ) );
        }

        // Attempt to clear the cache
        $cleared = snn_cache_clear_all();

        // Add admin notice based on result
        if ( $cleared ) {
            add_settings_error( 'snn-cache-notices', 'cache-cleared', __( 'SNN Cache cleared successfully.', 'snn-cache' ), 'success' );
        } else {
             add_settings_error( 'snn-cache-notices', 'cache-clear-failed', __( 'SNN Cache failed to clear completely. Check file permissions or error logs.', 'snn-cache' ), 'error' );
        }

        // Store notices for display after redirect
        set_transient( 'settings_errors', get_settings_errors(), 30 );

        // Redirect back to settings page to prevent form resubmission
        wp_safe_redirect( admin_url( 'options-general.php?page=' . SNN_CACHE_SETTINGS_SLUG . '&settings-updated=cache_cleared' ) ); // Using a unique param
        exit;
    }
}
add_action( 'admin_init', 'snn_cache_handle_clear_cache_action' );


/**
 * Displays the HTML for the settings page.
 */
function snn_cache_settings_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    // Display notices stored in transient and regular settings errors
    settings_errors( 'snn-cache-notices' );

    $options = snn_cache_get_options();
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

        <form action="options.php" method="post">
            <?php
            settings_fields( SNN_CACHE_OPTION_GROUP );
            do_settings_sections( SNN_CACHE_SETTINGS_SLUG );
            submit_button( __( 'Save Settings', 'snn-cache' ) );
            ?>
        </form>

        <hr>

        <h2><?php esc_html_e( 'Cache Management', 'snn-cache' ); ?></h2>
        <p><?php esc_html_e( 'Manually clear all cached files.', 'snn-cache' ); ?></p>
        <form method="post" action="<?php echo esc_url( admin_url( 'options-general.php?page=' . SNN_CACHE_SETTINGS_SLUG ) ); // Post back to the same page ?>">
            <input type="hidden" name="snn_cache_action" value="clear_cache">
            <?php wp_nonce_field( 'snn_cache_clear_cache_nonce' ); ?>
            <?php submit_button( __( 'Clear Entire Cache', 'snn-cache' ), 'delete', 'snn_clear_cache_submit', false, ['id' => 'snn-clear-cache-button'] ); // Use 'delete' class for red styling ?>
        </form>

        <hr>
        <h2><?php esc_html_e( 'Cache Information', 'snn-cache' ); ?></h2>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Cache Status', 'snn-cache' ); ?></th>
                    <td>
                        <?php
                         if ( ! empty( $options['enabled'] ) ) {
                              echo '<span style="color: green; font-weight: bold;">' . esc_html__( 'Enabled', 'snn-cache' ) . '</span>';
                         } else {
                              echo '<span style="color: red; font-weight: bold;">' . esc_html__( 'Disabled', 'snn-cache' ) . '</span>';
                         }
                        ?>
                    </td>
                </tr>
                 <tr>
                    <th scope="row"><?php esc_html_e( 'Cache Directory', 'snn-cache' ); ?></th>
                    <td><code><?php echo esc_html( SNN_CACHE_DIR ); ?></code></td>
                </tr>
                 <tr>
                    <th scope="row"><?php esc_html_e( 'Directory Status', 'snn-cache' ); ?></th>
                    <td>
                        <?php
                        $cache_dir_path = SNN_CACHE_DIR; // Use variable for clarity
                        $base_dir_path = SNN_CACHE_BASE_DIR;

                        if ( ! file_exists( $cache_dir_path ) ) {
                            echo '<span style="color: orange; font-weight: bold;">' . esc_html__( 'Directory Missing', 'snn-cache' ) . '</span><br>';
                            if ( ! is_writable( $base_dir_path ) ) {
                                echo '<span style="color: red;">' . sprintf( esc_html__( 'Base directory (%s) is not writable.', 'snn-cache' ), '<code>' . esc_html( $base_dir_path ) . '</code>' ) . '</span>';
                            } else {
                                echo esc_html__( 'Try saving settings or activating the plugin again to recreate it.', 'snn-cache' );
                            }
                        } elseif ( ! is_dir( $cache_dir_path ) ) {
                             echo '<span style="color: red; font-weight: bold;">' . esc_html__( 'Path exists but is not a directory.', 'snn-cache' ) . '</span>';
                        } elseif ( ! is_writable( $cache_dir_path ) ) {
                            echo '<span style="color: red; font-weight: bold;">' . esc_html__( 'Not Writable', 'snn-cache' ) . '</span><br>';
                            echo esc_html__( 'Please check file permissions.', 'snn-cache' );
                        } else {
                            echo '<span style="color: green;">' . esc_html__( 'Exists and is Writable', 'snn-cache' ) . '</span>';
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Cache Size / Files', 'snn-cache' ); ?></th>
                    <td><?php echo esc_html( snn_cache_get_stats() ); ?></td>
                </tr>
            </tbody>
        </table>

    </div>
    <?php
}

/**
 * Calculates cache directory size and file count.
 * Excludes index.php and .htaccess files.
 *
 * @return string Formatted string with cache stats or error message.
 */
function snn_cache_get_stats() {
    $cache_dir = SNN_CACHE_DIR; // Use variable
    if ( ! is_dir( $cache_dir ) || ! is_readable( $cache_dir ) ) {
        return __( 'Cache directory not accessible.', 'snn-cache' );
    }

    $size = 0;
    $count = 0;
    try {
        // Use FilesystemIterator::SKIP_DOTS to automatically skip '.' and '..'
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( $cache_dir, FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS ),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ( $iterator as $file ) {
            // Check if it's a file and not one of the protected files
            if ( $file->isFile() ) {
                $filename = $file->getFilename();
                if ( $filename !== 'index.php' && $filename !== '.htaccess' ) {
                    // Check if file is readable before getting size
                    if ( $file->isReadable() ) {
                         $size += $file->getSize();
                    } else {
                        // Log error or indicate issue if file isn't readable
                        error_log("SNN Cache Stats: Could not read file size for " . $file->getPathname());
                    }
                    $count++;
                }
            }
        }
        // Use size_format for user-friendly size display
        return sprintf( '%s / %d files', size_format( $size, 2 ), $count ); // Show 2 decimal places for size
    } catch ( UnexpectedValueException $e ) {
        // Catch potential errors if directory becomes unreadable during iteration
         error_log("SNN Cache Error getting stats (UnexpectedValueException): " . $e->getMessage());
         return __( 'Error calculating stats (directory issue).', 'snn-cache' );
    } catch ( Exception $e ) {
        error_log("SNN Cache Error getting stats: " . $e->getMessage());
        return __( 'Error calculating stats.', 'snn-cache' );
    }
}

?>
