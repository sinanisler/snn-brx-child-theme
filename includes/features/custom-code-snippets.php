<?php
// Prevent direct access 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

//  define( 'SNN_CODE_DISABLE', true );


define('SNN_CUSTOM_CODES_LOG_OPTION', 'snn_custom_codes_error_log');
define('SNN_CUSTOM_CODES_MAX_LOG_ENTRIES', 150);
define('SNN_FATAL_ERROR_NOTICE_TRANSIENT', 'snn_fatal_error_admin_notice');

// Runtime safety state: which snippets are verified-good, which are blocked by an
// error, and which one (if any) was mid-execution when a request died.
define('SNN_SNIPPET_STATE_OPTION', 'snn_snippet_state');
// Per-snippet on/off switches. For the legacy tabs an absent key means enabled
// (back-compat); modern snippets are off unless switched on.
define('SNN_SNIPPET_ENABLED_OPTION', 'snn_snippet_enabled_map');
// How long an "in flight" marker may sit there before we treat it as a crash
// rather than as a request that is still running. Measured from when the marker
// was first armed, NOT from the last request that touched it - otherwise a site
// with traffic resets its own timer on every crash and never recovers.
// Must exceed max_execution_time.
define('SNN_SNIPPET_CRASH_GRACE', 60);
// How many times a snippet may be started without a single request ever reaching
// the end before we call it a crash loop, regardless of elapsed time. Set above
// the number of requests that plausibly overlap the very first run of freshly
// saved code (page + admin-ajax + heartbeat), so concurrency alone cannot trip it.
define('SNN_SNIPPET_CRASH_MAX_STARTS', 3);

// Consecutive fatal requests that could NOT be pinned on a specific snippet but
// happened while snippets were running. Fatals inside a hook a snippet
// registered, inside a file it included, memory exhaustion and execution
// timeouts all report a file that is not the eval'd code, so they are
// unattributable - but they are still usually our fault. Counting them lets the
// global switch trip as a last resort instead of leaving a permanent white screen.
define('SNN_UNATTRIBUTED_FATAL_OPTION', 'snn_unattributed_fatal_streak');
define('SNN_UNATTRIBUTED_FATAL_LIMIT', 3);

// Save -> test -> publish. An edit to a snippet that runs is stored as a draft
// in this post meta key and only replaces the live code (post_content) after it
// has survived real page loads of the site. The live version keeps running in
// the meantime, so a broken edit never reaches visitors.
define('SNN_SNIPPET_DRAFT_META', '_snn_snippet_draft');
// Transient prefix for one test run: the code under test, the pages to load,
// and the result each of those page loads records for itself.
define('SNN_SNIPPET_TEST_TRANSIENT', 'snn_snippet_test_');
// How long a test run stays valid. The browser drives the page loads, and some
// environments (WordPress Studio, for one) serve a single request at a time.
define('SNN_SNIPPET_TEST_TTL', 15 * MINUTE_IN_SECONDS);

// Modern snippets: unlimited private snn_code_snippet posts, settings in post
// meta. Their key in the enable map, the safety state and the logs is
// "snn-code-{ID}"; the four fixed tabs above are the "legacy" snippets.
define('SNN_SNIPPET_META_TYPE', '_snn_code_type');
define('SNN_SNIPPET_META_LOCATION', '_snn_location');
define('SNN_SNIPPET_META_PRIORITY', '_snn_priority');
define('SNN_SNIPPET_META_CONDITIONS', '_snn_conditions');
define('SNN_SNIPPET_META_TEST_URL', '_snn_test_url');
// Every switched-on modern snippet, code included, in one autoloaded option.
// Rebuilt on every save, switch and delete, so running snippets costs no
// queries - and works at after_setup_theme, before post types exist.
define('SNN_SNIPPET_CACHE_OPTION', 'snn_snippets_cache');
// Keeps ?snn_safe_mode=1 on for an administrator's browser until they exit it.
define('SNN_SNIPPET_SAFE_MODE_COOKIE', 'snn_snippets_safe_mode');

/**
 * Canonical snippet slug list, keyed by admin tab key.
 * Single source of truth for the slug <-> tab mapping.
 */
function snn_snippet_slugs() {
    return array(
        'frontend'     => 'snn-snippet-frontend-head',
        'footer'       => 'snn-snippet-footer',
        'admin'        => 'snn-snippet-admin-head',
        'functions'    => 'snn-snippet-functions-php',
    );
}

/**
 * Human readable snippet titles, keyed by slug.
 */
function snn_snippet_titles() {
    return array(
        'snn-snippet-frontend-head' => __( 'Legacy: Frontend Head PHP/HTML', 'snn' ),
        'snn-snippet-footer'        => __( 'Legacy: Frontend Footer PHP/HTML', 'snn' ),
        'snn-snippet-admin-head'    => __( 'Legacy: Admin Head PHP/HTML', 'snn' ),
        'snn-snippet-functions-php' => __( 'Legacy: PHP (functions.php)', 'snn' ),
    );
}

/**
 * Title for a snippet key (legacy slug or modern "snn-code-{ID}"), falling
 * back to the key itself.
 */
function snn_snippet_title( $slug ) {
    $titles = snn_snippet_titles();
    if ( isset( $titles[ $slug ] ) ) {
        return $titles[ $slug ];
    }
    $id = snn_snippet_modern_id( $slug );
    if ( $id ) {
        $cache = snn_snippets_cache();
        if ( isset( $cache['titles'][ $id ] ) && '' !== $cache['titles'][ $id ] ) {
            return $cache['titles'][ $id ];
        }
        /* translators: %d: snippet ID */
        return sprintf( __( 'Snippet #%d', 'snn' ), $id );
    }
    return $slug;
}

/** Key of a modern snippet: "snn-code-{ID}". */
function snn_snippet_modern_key( $id ) {
    return 'snn-code-' . absint( $id );
}

/** Post ID in a modern snippet key, or 0 for anything else. */
function snn_snippet_modern_id( $key ) {
    return ( is_string( $key ) && preg_match( '/^snn-code-(\d+)$/', $key, $m ) ) ? (int) $m[1] : 0;
}

/** Whether a key is one of the four fixed legacy snippets. */
function snn_snippet_is_legacy( $key ) {
    return in_array( $key, snn_snippet_slugs(), true );
}

/**
 * Whether a key names a snippet that exists. The crash guard uses this to tell
 * a real crash from a stale marker left by a deleted snippet.
 */
function snn_snippet_key_exists( $key ) {
    if ( snn_snippet_is_legacy( $key ) ) {
        return true;
    }
    $id    = snn_snippet_modern_id( $key );
    $cache = snn_snippets_cache();
    return $id && isset( $cache['titles'][ $id ] );
}

/** Admin URL of the screen that edits a snippet. */
function snn_snippet_edit_url( $key ) {
    $tab = array_search( $key, snn_snippet_slugs(), true );
    if ( false !== $tab ) {
        return admin_url( 'admin.php?page=snn-custom-codes-snippets&tab=' . $tab );
    }
    $id = snn_snippet_modern_id( $key );
    if ( $id ) {
        return admin_url( 'admin.php?page=snn-custom-codes-snippets&view=edit&snippet=' . $id );
    }
    return admin_url( 'admin.php?page=snn-custom-codes-snippets' );
}

/**
 * Code types. Only the two PHP types are executed; the rest are printed as-is.
 * 'mode' is the CodeMirror mode the editor switches to.
 */
function snn_snippet_code_types() {
    return array(
        'php'      => array( 'label' => __( 'PHP', 'snn' ), 'mode' => 'text/x-php' ),
        'html_php' => array( 'label' => __( 'HTML + PHP', 'snn' ), 'mode' => 'application/x-httpd-php' ),
        'html'     => array( 'label' => __( 'HTML', 'snn' ), 'mode' => 'text/html' ),
        'css'      => array( 'label' => __( 'CSS', 'snn' ), 'mode' => 'text/css' ),
        'js'       => array( 'label' => __( 'JavaScript', 'snn' ), 'mode' => 'text/javascript' ),
    );
}

/** Whether a code type is executed as PHP (and therefore tested before going live). */
function snn_snippet_type_runs_php( $type ) {
    return 'php' === $type || 'html_php' === $type;
}

/**
 * Where modern snippets run. No translations here: this is read on every
 * request, before the text domain loads.
 *
 *  - area:    'any', 'admin' or 'front'.
 *  - stage:   'early'  runs before the page query; PHP only, output discarded.
 *             'query'  runs once the page query is known; PHP only, output discarded.
 *             'output' prints into the page; every code type.
 *  - targets: the test pages that must load cleanly before a draft goes live.
 */
function snn_snippet_location_map() {
    return array(
        'everywhere'    => array( 'hook' => 'after_setup_theme', 'area' => 'any',   'stage' => 'early',  'targets' => array( 'admin', 'home_in', 'home_out' ) ),
        'admin_only'    => array( 'hook' => 'after_setup_theme', 'area' => 'admin', 'stage' => 'early',  'targets' => array( 'admin' ) ),
        'frontend_only' => array( 'hook' => 'after_setup_theme', 'area' => 'front', 'stage' => 'early',  'targets' => array( 'home_in', 'home_out' ) ),
        'frontend_wp'   => array( 'hook' => 'wp',                'area' => 'front', 'stage' => 'query',  'targets' => array( 'home_in', 'home_out' ) ),
        'site_head'     => array( 'hook' => 'wp_head',           'area' => 'front', 'stage' => 'output', 'targets' => array( 'home_in', 'home_out' ) ),
        'body_open'     => array( 'hook' => 'wp_body_open',      'area' => 'front', 'stage' => 'output', 'targets' => array( 'home_in', 'home_out' ) ),
        'site_footer'   => array( 'hook' => 'wp_footer',         'area' => 'front', 'stage' => 'output', 'targets' => array( 'home_in', 'home_out' ) ),
        'admin_head'    => array( 'hook' => 'admin_head',        'area' => 'admin', 'stage' => 'output', 'targets' => array( 'admin' ) ),
        'admin_footer'  => array( 'hook' => 'admin_footer',      'area' => 'admin', 'stage' => 'output', 'targets' => array( 'admin' ) ),
    );
}

/** Location labels for the admin screens. */
function snn_snippet_location_labels() {
    return array(
        'everywhere'    => __( 'Run everywhere', 'snn' ),
        'admin_only'    => __( 'Admin only', 'snn' ),
        'frontend_only' => __( 'Front end only', 'snn' ),
        'frontend_wp'   => __( 'Front end, after page query', 'snn' ),
        'site_head'     => __( 'Site head', 'snn' ),
        'body_open'     => __( 'After opening <body>', 'snn' ),
        'site_footer'   => __( 'Site footer', 'snn' ),
        'admin_head'    => __( 'Admin head', 'snn' ),
        'admin_footer'  => __( 'Admin footer', 'snn' ),
    );
}

/** HTML, CSS and JavaScript can only go where output is printed. */
function snn_snippet_location_allows_type( $location, $type ) {
    $map = snn_snippet_location_map();
    return isset( $map[ $location ] ) && ( 'output' === $map[ $location ]['stage'] || snn_snippet_type_runs_php( $type ) );
}

/** Whether WordPress knows which page it is showing when this location runs. */
function snn_snippet_location_knows_page( $location ) {
    $map = snn_snippet_location_map();
    return isset( $map[ $location ] ) && 'front' === $map[ $location ]['area'] && 'early' !== $map[ $location ]['stage'];
}

/**
 * Conditional logic rules. 'page' rules need the page query, so they are only
 * offered for locations that run once it is known.
 */
function snn_snippet_rule_defs() {
    return array(
        'logged_in' => array( 'ops' => array( 'is' ), 'page' => false ),
        'user_role' => array( 'ops' => array( 'is', 'is_not' ), 'page' => false ),
        'area'      => array( 'ops' => array( 'is' ), 'page' => false ),
        'url_path'  => array( 'ops' => array( 'equals', 'contains', 'starts_with' ), 'page' => false ),
        'device'    => array( 'ops' => array( 'is' ), 'page' => false ),
        'page_type' => array( 'ops' => array( 'is', 'is_not' ), 'page' => true ),
        'post_type' => array( 'ops' => array( 'is', 'is_not' ), 'page' => true ),
        'post_id'   => array( 'ops' => array( 'is', 'is_not' ), 'page' => true ),
    );
}

/** Page types the page_type rule understands. */
function snn_snippet_page_types() {
    return array( 'front_page', 'blog', 'singular', 'archive', 'search', '404' );
}

/** Conditional logic that is switched off. */
function snn_snippet_empty_conditions() {
    return array( 'enabled' => false, 'action' => 'show', 'groups' => array() );
}

/**
 * Clean up conditional logic from the editor or from storage: known rules
 * and operators only, one sanitized value each, empty groups removed. Rules
 * the location cannot evaluate are dropped and counted in $dropped.
 */
function snn_snippet_normalize_conditions( $raw, $location, &$dropped = 0 ) {
    $dropped = 0;
    if ( ! is_array( $raw ) ) {
        return snn_snippet_empty_conditions();
    }
    $defs       = snn_snippet_rule_defs();
    $knows_page = snn_snippet_location_knows_page( $location );
    $groups     = array();

    foreach ( ( isset( $raw['groups'] ) && is_array( $raw['groups'] ) ) ? $raw['groups'] : array() as $group ) {
        $rules = array();
        foreach ( is_array( $group ) ? $group : array() as $rule ) {
            if ( ! is_array( $rule ) || ! isset( $rule['rule'], $defs[ $rule['rule'] ] ) ) {
                continue;
            }
            $name = $rule['rule'];
            if ( $defs[ $name ]['page'] && ! $knows_page ) {
                $dropped++;
                continue;
            }
            $op    = ( isset( $rule['op'] ) && in_array( $rule['op'], $defs[ $name ]['ops'], true ) ) ? $rule['op'] : $defs[ $name ]['ops'][0];
            $value = isset( $rule['value'] ) && is_scalar( $rule['value'] ) ? (string) $rule['value'] : '';
            switch ( $name ) {
                case 'logged_in':
                    $value = 'false' === $value ? 'false' : 'true';
                    break;
                case 'area':
                    $value = 'admin' === $value ? 'admin' : 'front';
                    break;
                case 'device':
                    $value = 'mobile' === $value ? 'mobile' : 'desktop';
                    break;
                case 'page_type':
                    $value = in_array( $value, snn_snippet_page_types(), true ) ? $value : 'front_page';
                    break;
                case 'user_role':
                case 'post_type':
                    $value = sanitize_key( $value );
                    break;
                case 'post_id':
                    $value = (string) absint( $value );
                    break;
                case 'url_path':
                    $value = substr( sanitize_text_field( $value ), 0, 500 );
                    break;
            }
            if ( '' === $value || '0' === $value && 'post_id' === $name ) {
                continue;
            }
            $rules[] = array( 'rule' => $name, 'op' => $op, 'value' => $value );
        }
        if ( $rules ) {
            $groups[] = $rules;
        }
    }

    return array(
        'enabled' => ! empty( $raw['enabled'] ) && (bool) $groups,
        'action'  => ( isset( $raw['action'] ) && 'hide' === $raw['action'] ) ? 'hide' : 'show',
        'groups'  => $groups,
    );
}

/** Settings of a new modern snippet. */
function snn_snippet_default_settings() {
    return array(
        'type'       => 'php',
        'location'   => 'everywhere',
        'priority'   => 10,
        'conditions' => snn_snippet_empty_conditions(),
    );
}

/**
 * Clean up a modern snippet's settings. A type that cannot go where it was
 * placed (CSS in "Run everywhere") moves to the site head.
 *
 * @param array $settings type, location, priority, conditions.
 * @param int   $dropped  Receives the number of conditions the location cannot evaluate.
 */
function snn_snippet_normalize_settings( $settings, &$dropped = 0 ) {
    $defaults = snn_snippet_default_settings();
    $settings = is_array( $settings ) ? array_merge( $defaults, $settings ) : $defaults;
    $map      = snn_snippet_location_map();

    $type     = array_key_exists( (string) $settings['type'], array( 'php' => 1, 'html_php' => 1, 'html' => 1, 'css' => 1, 'js' => 1 ) ) ? (string) $settings['type'] : 'php';
    $location = isset( $map[ (string) $settings['location'] ] ) ? (string) $settings['location'] : 'everywhere';
    if ( ! snn_snippet_location_allows_type( $location, $type ) ) {
        $location = 'site_head';
    }

    return array(
        'type'       => $type,
        'location'   => $location,
        'priority'   => max( 0, min( 9999, (int) $settings['priority'] ) ),
        'conditions' => snn_snippet_normalize_conditions( $settings['conditions'], $location, $dropped ),
    );
}

/** A modern snippet's stored settings. */
function snn_snippet_get_settings( $post_id ) {
    $priority = get_post_meta( $post_id, SNN_SNIPPET_META_PRIORITY, true );
    return snn_snippet_normalize_settings( array(
        'type'       => get_post_meta( $post_id, SNN_SNIPPET_META_TYPE, true ),
        'location'   => get_post_meta( $post_id, SNN_SNIPPET_META_LOCATION, true ),
        'priority'   => '' === $priority ? 10 : $priority,
        'conditions' => get_post_meta( $post_id, SNN_SNIPPET_META_CONDITIONS, true ),
    ) );
}

/**
 * Store a modern snippet's settings. update_post_meta() unslashes its input,
 * so the conditions (which may hold backslashes) are slashed first.
 */
function snn_snippet_save_settings( $post_id, $settings ) {
    update_post_meta( $post_id, SNN_SNIPPET_META_TYPE, $settings['type'] );
    update_post_meta( $post_id, SNN_SNIPPET_META_LOCATION, $settings['location'] );
    update_post_meta( $post_id, SNN_SNIPPET_META_PRIORITY, (int) $settings['priority'] );
    update_post_meta( $post_id, SNN_SNIPPET_META_CONDITIONS, wp_slash( $settings['conditions'] ) );
}

/**
 * The code as the executor evaluates it. The executor starts in HTML mode
 * (legacy semantics), so pure PHP gets an open tag in front - on the same line,
 * so line numbers still match the editor.
 */
function snn_snippet_exec_code( $type, $code ) {
    return 'php' === $type ? '<' . '?php ' . $code : $code;
}

/**
 * Pure PHP is stored without its open tag; a leading "<?php" is optional in
 * the editor and stripped on save.
 */
function snn_snippet_strip_open_tag( $code ) {
    return preg_replace( '/^\s*<\?php\b\s*/i', '', (string) $code, 1 );
}

/**
 * The modern snippets cache: 'titles' (every modern snippet, id => title) and
 * 'active' (switched-on, non-empty snippets with code and settings, in run
 * order). Blocked snippets stay in 'active'; the safety state skips them.
 */
function snn_snippets_cache( $fresh = false ) {
    static $cache = null;
    if ( $fresh || null === $cache ) {
        $stored = get_option( SNN_SNIPPET_CACHE_OPTION, null );
        $cache  = ( is_array( $stored ) && isset( $stored['titles'], $stored['active'] ) && is_array( $stored['titles'] ) && is_array( $stored['active'] ) )
            ? $stored
            : array( 'titles' => array(), 'active' => array() );
    }
    return $cache;
}

/** Run order: priority, then ID. */
function snn_snippet_compare_run_order( $a, $b ) {
    if ( (int) $a['priority'] === (int) $b['priority'] ) {
        return (int) $a['id'] - (int) $b['id'];
    }
    return (int) $a['priority'] - (int) $b['priority'];
}

/** Every modern snippet post, oldest first. */
function snn_snippets_get_modern_posts() {
    $posts = get_posts( array(
        'post_type'        => 'snn_code_snippet',
        'post_status'      => 'private',
        'posts_per_page'   => -1,
        'orderby'          => 'ID',
        'order'            => 'ASC',
        'meta_key'         => SNN_SNIPPET_META_TYPE,
        'no_found_rows'    => true,
        'suppress_filters' => true,
    ) );
    $legacy = snn_snippet_slugs();
    $modern = array();
    foreach ( $posts as $post ) {
        if ( ! in_array( $post->post_name, $legacy, true ) ) {
            $modern[] = $post;
        }
    }
    return $modern;
}

/** Rebuild the modern snippets cache from the database. */
function snn_snippets_rebuild_cache() {
    $map    = snn_get_snippet_enabled_map();
    $titles = array();
    $active = array();

    foreach ( snn_snippets_get_modern_posts() as $post ) {
        $id            = (int) $post->ID;
        $titles[ $id ] = (string) $post->post_title;
        if ( empty( $map[ snn_snippet_modern_key( $id ) ] ) || '' === trim( $post->post_content ) ) {
            continue;
        }
        $active[] = array_merge( snn_snippet_get_settings( $id ), array(
            'id'   => $id,
            'code' => (string) $post->post_content,
        ) );
    }
    usort( $active, 'snn_snippet_compare_run_order' );

    update_option( SNN_SNIPPET_CACHE_OPTION, array( 'titles' => $titles, 'active' => $active ), true );
    return snn_snippets_cache( true );
}

/**
 * Line ranges handed out to eval'd snippets in this request, as a list of
 * array( slug, start, end, offset ).
 *
 * PHP reports every eval'd line against the same file name, so on its own a
 * fatal inside a hook a snippet registered - one that fires long after the
 * snippet ran - cannot be told apart from any other snippet's. Each snippet
 * is therefore padded so its code occupies its own range of line numbers, and
 * a line number alone then names exactly one snippet.
 */
function snn_snippet_line_ranges( $add = null ) {
    static $ranges = array();
    if ( null !== $add ) {
        $ranges[] = $add;
    }
    return $ranges;
}

/**
 * Reserve a line range for one evaluation of a snippet and return the number
 * of lines to pad it with.
 */
function snn_snippet_line_offset( $slug, $code ) {
    static $next = 0;
    $offset = $next;
    $lines  = substr_count( (string) $code, "\n" ) + 1;
    $next  += $lines + 1;
    snn_snippet_line_ranges( array(
        'slug'   => $slug,
        'start'  => $offset + 1,
        'end'    => $offset + $lines,
        'offset' => $offset,
    ) );
    return $offset;
}

/**
 * Which snippet a reported file and line belong to.
 *
 * @return array|null array( slug, line ) with the line inside that snippet, or
 *                    null when the location is not eval'd snippet code.
 */
function snn_snippet_locate_line( $file, $line ) {
    $file = wp_normalize_path( (string) $file );
    if ( 0 !== strpos( $file, wp_normalize_path( __FILE__ ) ) || false === strpos( $file, "eval()'d code" ) ) {
        return null;
    }
    $line = (int) $line;
    foreach ( snn_snippet_line_ranges() as $range ) {
        if ( $line >= $range['start'] && $line <= $range['end'] ) {
            return array( 'slug' => $range['slug'], 'line' => $line - $range['offset'] );
        }
    }
    return null;
}

/**
 * Which snippet line an exception or error came from: where it was thrown, or
 * failing that the innermost snippet line on its stack - the line that called
 * into WordPress when the error was raised inside WordPress itself.
 *
 * @return array|null array( slug, line ), or null.
 */
function snn_snippet_locate_throwable( $e ) {
    $located = snn_snippet_locate_line( $e->getFile(), $e->getLine() );
    if ( $located ) {
        return $located;
    }
    foreach ( $e->getTrace() as $frame ) {
        if ( isset( $frame['file'], $frame['line'] ) ) {
            $located = snn_snippet_locate_line( $frame['file'], $frame['line'] );
            if ( $located ) {
                return $located;
            }
        }
    }
    return null;
}

/**
 * The request path conditions compare against: lower case, relative to the
 * site's home path, always starting with "/".
 */
function snn_snippet_request_path() {
    $uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
    $path = (string) parse_url( $uri, PHP_URL_PATH );
    $home = rtrim( (string) parse_url( home_url(), PHP_URL_PATH ), '/' );
    if ( '' !== $home && 0 === strpos( $path, $home ) ) {
        $path = substr( $path, strlen( $home ) );
    }
    return strtolower( '/' . ltrim( rawurldecode( $path ), '/' ) );
}

/** Post types the current page is about: the single post's, or the archive's. */
function snn_snippet_queried_post_types() {
    if ( is_singular() ) {
        return array( (string) get_post_type( get_queried_object_id() ) );
    }
    if ( is_post_type_archive() ) {
        return array_map( 'strval', (array) get_query_var( 'post_type' ) );
    }
    return array();
}

/** Does one condition rule match this request? */
function snn_snippet_rule_matches( $rule ) {
    $value = (string) $rule['value'];
    $not   = ( 'is_not' === $rule['op'] );

    switch ( $rule['rule'] ) {
        case 'logged_in':
            return is_user_logged_in() === ( 'true' === $value );

        case 'user_role':
            $user = wp_get_current_user();
            $has  = $user && $user->exists() && in_array( $value, (array) $user->roles, true );
            return $not ? ! $has : $has;

        case 'area':
            return ( 'admin' === $value ) === is_admin();

        case 'device':
            return ( 'mobile' === $value ) === wp_is_mobile();

        case 'url_path':
            $path   = snn_snippet_request_path();
            $needle = strtolower( trim( $value ) );
            if ( 'contains' === $rule['op'] ) {
                return false !== strpos( $path, $needle );
            }
            $needle = '/' . ltrim( $needle, '/' );
            if ( 'starts_with' === $rule['op'] ) {
                return 0 === strpos( $path, $needle );
            }
            return untrailingslashit( $path ) === untrailingslashit( $needle );
    }

    // Page rules: only meaningful once the front-end page query has run.
    if ( is_admin() || ! did_action( 'wp' ) ) {
        return false;
    }

    switch ( $rule['rule'] ) {
        case 'page_type':
            $checks = array(
                'front_page' => is_front_page(),
                'blog'       => is_home(),
                'singular'   => is_singular(),
                'archive'    => is_archive(),
                'search'     => is_search(),
                '404'        => is_404(),
            );
            $match = ! empty( $checks[ $value ] );
            break;
        case 'post_type':
            $match = in_array( $value, snn_snippet_queried_post_types(), true );
            break;
        case 'post_id':
            $match = is_singular() && (int) get_queried_object_id() === (int) $value;
            break;
        default:
            return false;
    }
    return $not ? ! $match : $match;
}

/**
 * Do a snippet's conditions let it run on this request? Rows in a group must
 * all match; any one matching group is enough.
 */
function snn_snippet_conditions_pass( $conditions ) {
    if ( empty( $conditions['enabled'] ) || empty( $conditions['groups'] ) ) {
        return true;
    }
    $matched = false;
    foreach ( $conditions['groups'] as $group ) {
        $all = (bool) $group;
        foreach ( $group as $rule ) {
            if ( ! snn_snippet_rule_matches( $rule ) ) {
                $all = false;
                break;
            }
        }
        if ( $all ) {
            $matched = true;
            break;
        }
    }
    return ( 'hide' === $conditions['action'] ) ? ! $matched : $matched;
}

/**
 * Read the runtime safety state.
 *
 * Shape:
 *   'verified'  => array( slug => md5(code) )  snippets that survived a full request
 *   'errors'    => array( slug => array( type, message, line, time ) ) blocked snippets
 *   'in_flight' => array( slug, hash, time )   set while unverified code is running
 */
function snn_get_snippet_state( $fresh = false ) {
    static $cache = null;
    if ( $fresh || null === $cache ) {
        $state = get_option( SNN_SNIPPET_STATE_OPTION, array() );
        if ( ! is_array( $state ) ) {
            $state = array();
        }
        $state['verified'] = isset( $state['verified'] ) && is_array( $state['verified'] ) ? $state['verified'] : array();
        $state['errors']   = isset( $state['errors'] )   && is_array( $state['errors'] )   ? $state['errors']   : array();
        $cache = $state;
    }
    return $cache;
}

/**
 * Persist the runtime safety state and refresh the in-process cache.
 */
function snn_update_snippet_state( $state ) {
    update_option( SNN_SNIPPET_STATE_OPTION, $state, true );
    snn_get_snippet_state( true );
}

/**
 * Block a snippet from executing and record why.
 * Also drops its "verified" marker so the next save re-arms the crash guard.
 */
function snn_snippet_record_error( $slug, $type, $message, $line = 0 ) {
    // Test page loads are a sandbox: they report to the test run, never block.
    if ( snn_snippet_test_context() ) {
        return;
    }

    $state = snn_get_snippet_state( true );

    $state['errors'][ $slug ] = array(
        'type'    => (string) $type,
        'message' => (string) $message,
        'line'    => absint( $line ),
        'time'    => time(),
    );
    unset( $state['verified'][ $slug ] );

    if ( isset( $state['in_flight']['slug'] ) && $state['in_flight']['slug'] === $slug ) {
        unset( $state['in_flight'] );
    }

    snn_update_snippet_state( $state );
}

/**
 * Unblock a snippet (called when valid code is saved, or manually from the UI).
 *
 * Also drops any in-flight marker for this slug. That matters in two cases the
 * error list alone does not cover:
 *
 *  - A crash was detected but not yet acted on (the marker is flagged 'crashed'
 *    and no request has run recovery yet) and the user saves a fix. Without this
 *    the stale marker would block the brand new, working code on the next request.
 *  - The user clicks "Re-enable this snippet" while such a marker stands. Without
 *    this, recovery would immediately re-block it and the button would appear to
 *    do nothing.
 *
 * @return bool Whether a recorded error was cleared (drives the admin notice).
 */
function snn_snippet_clear_error( $slug ) {
    $state   = snn_get_snippet_state( true );
    $changed = false;

    if ( isset( $state['in_flight']['slug'] ) && $state['in_flight']['slug'] === $slug ) {
        unset( $state['in_flight'] );
        $changed = true;
    }

    $had_error = isset( $state['errors'][ $slug ] );
    if ( $had_error ) {
        unset( $state['errors'][ $slug ], $state['verified'][ $slug ] );
        $changed = true;
    }

    if ( $changed ) {
        snn_update_snippet_state( $state );
    }

    return $had_error;
}

/**
 * Recorded error for a snippet, or false.
 */
function snn_snippet_get_error( $slug ) {
    $state = snn_get_snippet_state();
    return isset( $state['errors'][ $slug ] ) ? $state['errors'][ $slug ] : false;
}

/**
 * Per-snippet enable map. A missing key counts as enabled so that sites
 * upgrading from an earlier version keep running exactly what they ran before.
 */
function snn_get_snippet_enabled_map() {
    $map = get_option( SNN_SNIPPET_ENABLED_OPTION, array() );
    return is_array( $map ) ? $map : array();
}

/**
 * Whether a snippet is switched on by the user (ignores error state). Modern
 * snippets are off until switched on; legacy ones count as on when unset.
 */
function snn_snippet_is_enabled( $slug ) {
    $map = snn_get_snippet_enabled_map();
    if ( snn_snippet_modern_id( $slug ) ) {
        return ! empty( $map[ $slug ] );
    }
    return ! isset( $map[ $slug ] ) || (bool) $map[ $slug ];
}

/**
 * Tracks which snippet is being eval'd right now, so the shutdown handler can
 * attribute a fatal error to it with certainty. Static only, no DB writes.
 * Pass a string to set (use '' to clear); returns the current value.
 */
function snn_snippet_active_slug( $slug = null ) {
    static $active = '';
    if ( null !== $slug ) {
        $active = (string) $slug;
    }
    return $active;
}

/**
 * Every slug eval'd during this request. Used as a fallback attribution when a
 * fatal happens after eval() returned (e.g. inside a hook the snippet added).
 */
function snn_snippet_executed_slugs( $add = null ) {
    static $slugs = array();
    if ( null !== $add ) {
        $slugs[ $add ] = true;
    }
    return array_keys( $slugs );
}

/**
 * Set by the fatal shutdown handler once it has pinned a fatal on a specific
 * snippet and blocked it. Lets the verification pass that runs afterwards know
 * the crash is already accounted for, so it does not additionally blame whatever
 * unrelated snippet happened to hold the in-flight marker.
 */
function snn_snippet_fatal_attributed( $set = false ) {
    static $attributed = false;
    if ( $set ) {
        $attributed = true;
    }
    return $attributed;
}

/**
 * Snippets awaiting end-of-request verification, as slug => hash.
 */
function snn_snippet_pending_verification( $slug = null, $hash = '' ) {
    static $pending = array();
    if ( null !== $slug ) {
        $pending[ $slug ] = $hash;
    }
    return $pending;
}

/**
 * Is this a request for the login/registration screen?
 *
 * wp-login.php is NOT is_admin(), so without this check user code executes on the
 * login screen - and a fatal there locks the administrator out of the entire
 * admin area, including the snippets page that exists to repair it. Worse,
 * ?snn_safe_mode=1 cannot rescue that situation because it requires being logged
 * in. Logging in must never depend on user code.
 */
function snn_is_login_request() {
    // Set by wp-includes/vars.php during wp-settings.php, so it is reliable by init.
    if ( ! empty( $GLOBALS['pagenow'] ) && in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ), true ) ) {
        return true;
    }

    // Belt and braces for setups where $pagenow is not what we expect.
    $script = '';
    if ( isset( $_SERVER['SCRIPT_NAME'] ) ) {
        $script = (string) $_SERVER['SCRIPT_NAME'];
    } elseif ( isset( $_SERVER['PHP_SELF'] ) ) {
        $script = (string) $_SERVER['PHP_SELF'];
    }
    if ( '' !== $script ) {
        $path = parse_url( $script, PHP_URL_PATH );
        if ( is_string( $path ) && in_array( basename( $path ), array( 'wp-login.php', 'wp-register.php' ), true ) ) {
            return true;
        }
    }

    return false;
}

/**
 * Should snippet execution be skipped entirely for this request?
 *
 * Safe mode exists so a snippet that kills the site can always be fixed:
 * the login screen and the snippets admin page never run snippets, and an admin
 * can add ?snn_safe_mode=1 to any URL.
 */
function snn_snippets_in_safe_mode() {
    if ( defined( 'SNN_CODE_SAFE_MODE' ) && SNN_CODE_SAFE_MODE ) {
        return true;
    }

    // Getting logged in must always work, whatever the user's code does.
    if ( snn_is_login_request() ) {
        return true;
    }

    // Never execute snippets on the page used to edit them, so a broken snippet
    // can always be repaired from the UI that it broke.
    if ( is_admin() && isset( $_GET['page'] ) && 'snn-custom-codes-snippets' === $_GET['page'] ) {
        return true;
    }

    // Same for the page's own admin-ajax calls: finishing a test run, previewing
    // a revision, switching a snippet and dismissing the notice must work while
    // a snippet is broken.
    if ( wp_doing_ajax() && isset( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], array( 'snn_snippet_test_finish', 'snn_get_revision_content', 'snn_dismiss_fatal_error_notice', 'snn_snippet_toggle', 'snn_snippet_search_posts' ), true ) ) {
        return true;
    }

    return snn_snippets_sticky_safe_mode();
}

/**
 * Safe mode an administrator switched on with ?snn_safe_mode=1. It stays on
 * for their browser (a cookie) until they exit it with ?snn_safe_mode=0, so
 * it is not lost on the next click. The cookie means nothing on its own:
 * only an administrator's requests honour it.
 */
function snn_snippets_sticky_safe_mode() {
    static $on = null;
    if ( null !== $on ) {
        return $on;
    }

    $param = isset( $_GET['snn_safe_mode'] ) && is_string( $_GET['snn_safe_mode'] ) ? $_GET['snn_safe_mode'] : null;
    $asked = ( '1' === $param ) || ( null === $param && ! empty( $_COOKIE[ SNN_SNIPPET_SAFE_MODE_COOKIE ] ) );
    $on    = $asked && current_user_can( 'manage_options' );

    if ( null !== $param && ! headers_sent() ) {
        $path = defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/';
        if ( $on ) {
            setcookie( SNN_SNIPPET_SAFE_MODE_COOKIE, '1', 0, $path, defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '', is_ssl(), true );
        } elseif ( '0' === $param ) {
            setcookie( SNN_SNIPPET_SAFE_MODE_COOKIE, '', time() - YEAR_IN_SECONDS, $path, defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '', is_ssl(), true );
        }
    }
    return $on;
}

/**
 * Validate PHP syntax using PHP's own parser.
 *
 * TOKEN_PARSE makes token_get_all() run the real Zend parser and throw a genuine
 * ParseError. It parses only - it never compiles, autoloads, includes or executes
 * anything - so it is safe to run inside the save request. Intended to be called
 * once at save time, never on the execution path.
 *
 * @return true|array True when the code parses, otherwise array( message, line ).
 */
function snn_check_php_syntax( $code ) {
    if ( '' === trim( (string) $code ) ) {
        return true;
    }

    // Older/limited PHP builds without the tokenizer: skip rather than guess.
    if ( ! function_exists( 'token_get_all' ) || ! defined( 'TOKEN_PARSE' ) ) {
        return true;
    }

    // Pathological input is a memory risk for any parser. Above this size we
    // decline to check rather than risk taking down the save request.
    if ( strlen( $code ) > 512000 ) {
        return true;
    }

    /*
     * The executor evals a close-tag followed by the snippet, so the code starts
     * in PHP mode and immediately drops to HTML. The open-tag/close-tag pair
     * prefixed below reproduces that for a file-context parse and adds no
     * newline, so reported line numbers map 1:1 onto the user's code.
     *
     * Note: this must be a block comment. A line comment containing a PHP close
     * tag would end PHP mode right there - which is exactly the trap the
     * "? >" spelling elsewhere in this file exists to avoid.
     */
    $prefix = '<' . '?php ?' . '>';

    try {
        token_get_all( $prefix . $code, TOKEN_PARSE );
    } catch ( ParseError $e ) {
        return array(
            'message' => $e->getMessage(),
            'line'    => $e->getLine(),
        );
    } catch ( Throwable $e ) {
        // CompileError and friends also surface here on newer PHP versions.
        return array(
            'message' => $e->getMessage(),
            'line'    => method_exists( $e, 'getLine' ) ? $e->getLine() : 0,
        );
    }

    return true;
}

/**
 * Next token index after $index that is not whitespace or a comment, or null.
 */
function snn_php_next_significant( $tokens, $index ) {
    $count = count( $tokens );
    for ( $i = $index + 1; $i < $count; $i++ ) {
        if ( is_array( $tokens[ $i ] ) && in_array( $tokens[ $i ][0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
            continue;
        }
        return $i;
    }
    return null;
}

/**
 * Record one declared name, or a duplicate of one already seen.
 */
function snn_php_add_declared_name( &$names, $kind, $namespace, $name, $line ) {
    $label = ( '' !== $namespace ? $namespace . '\\' : '' ) . $name;
    $key   = strtolower( $label );

    if ( isset( $names[ $kind ][ $key ] ) ) {
        $names['duplicates'][] = array(
            'kind'       => $kind,
            'key'        => $key,
            'label'      => $label,
            'line'       => (int) $line,
            'first_line' => $names[ $kind ][ $key ]['line'],
        );
        return;
    }

    $names[ $kind ][ $key ] = array( 'label' => $label, 'line' => (int) $line );
}

/**
 * Names a piece of snippet code declares when it runs: functions and
 * class-like structures (classes, interfaces, traits, enums), namespaced and
 * lower-cased the way PHP compares them.
 *
 * "Cannot redeclare" is a compile-time fatal that no try/catch can stop, so it
 * has to be caught before the code ever runs. Names passed anywhere in the code
 * to function_exists()/class_exists() and friends are collected as "guarded",
 * so the conventional `if ( ! function_exists( 'x' ) )` pattern is not flagged.
 *
 * Tokenizes only; nothing is compiled or executed.
 *
 * @return array{function: array, class: array, guarded: array, duplicates: array}
 */
function snn_php_declared_names( $code ) {
    $names = array(
        'function'   => array(),
        'class'      => array(),
        'guarded'    => array(),
        'duplicates' => array(),
    );

    if ( '' === trim( (string) $code ) || ! function_exists( 'token_get_all' ) ) {
        return $names;
    }

    // Same prefix as the syntax check and the executor, so lines map 1:1.
    $tokens = token_get_all( '<' . '?php ?' . '>' . $code );

    $skip       = array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT );
    $name_types = array( T_STRING, T_NS_SEPARATOR );
    if ( defined( 'T_NAME_QUALIFIED' ) ) {
        $name_types[] = T_NAME_QUALIFIED;
        $name_types[] = T_NAME_FULLY_QUALIFIED;
    }
    $class_like = array( T_CLASS, T_INTERFACE, T_TRAIT );
    if ( defined( 'T_ENUM' ) ) {
        $class_like[] = T_ENUM;
    }
    $by_ref = array();
    if ( defined( 'T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG' ) ) {
        $by_ref = array( T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG, T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG );
    }
    $guards = array( 'function_exists', 'class_exists', 'interface_exists', 'trait_exists', 'enum_exists' );

    $namespace   = '';
    $depth       = 0;
    $class_depth = array(); // Brace depths at which class-like bodies opened.
    $open_body   = false;   // The next "{" opens a class-like body.
    $previous    = null;    // Previous significant token.
    $count       = count( $tokens );

    for ( $i = 0; $i < $count; $i++ ) {
        $token = $tokens[ $i ];

        if ( is_string( $token ) ) {
            if ( '{' === $token ) {
                $depth++;
                if ( $open_body ) {
                    $class_depth[] = $depth;
                    $open_body     = false;
                }
            } elseif ( '}' === $token ) {
                if ( $class_depth && end( $class_depth ) === $depth ) {
                    array_pop( $class_depth );
                }
                $depth--;
            }
            $previous = $token;
            continue;
        }

        $id = $token[0];
        if ( in_array( $id, $skip, true ) ) {
            continue;
        }

        // "{$" and "${" inside strings open a brace that a plain "}" closes.
        if ( T_CURLY_OPEN === $id || T_DOLLAR_OPEN_CURLY_BRACES === $id ) {
            $depth++;
        } elseif ( T_NAMESPACE === $id ) {
            $j = snn_php_next_significant( $tokens, $i );
            // namespace\foo() on PHP 7 is the namespace operator, not a declaration.
            if ( null === $j || ! is_array( $tokens[ $j ] ) || T_NS_SEPARATOR !== $tokens[ $j ][0] ) {
                $declared = '';
                while ( null !== $j && is_array( $tokens[ $j ] ) && in_array( $tokens[ $j ][0], $name_types, true ) ) {
                    $declared .= $tokens[ $j ][1];
                    $i         = $j;
                    $j         = snn_php_next_significant( $tokens, $j );
                }
                $namespace = trim( $declared, '\\' );
            }
        } elseif ( T_STRING === $id && in_array( strtolower( $token[1] ), $guards, true ) ) {
            $j = snn_php_next_significant( $tokens, $i );
            $k = ( null !== $j && '(' === $tokens[ $j ] ) ? snn_php_next_significant( $tokens, $j ) : null;
            if ( null !== $k && is_array( $tokens[ $k ] ) && T_CONSTANT_ENCAPSED_STRING === $tokens[ $k ][0] ) {
                $guarded = str_replace( '\\\\', '\\', substr( $tokens[ $k ][1], 1, -1 ) );
                $names['guarded'][ strtolower( ltrim( $guarded, '\\' ) ) ] = true;
            }
        } elseif ( T_FUNCTION === $id ) {
            // "use function foo;" imports a function, it does not declare one.
            $is_import = is_array( $previous ) && T_USE === $previous[0];
            $j         = snn_php_next_significant( $tokens, $i );
            if ( null !== $j && ( '&' === $tokens[ $j ] || ( is_array( $tokens[ $j ] ) && in_array( $tokens[ $j ][0], $by_ref, true ) ) ) ) {
                $j = snn_php_next_significant( $tokens, $j );
            }
            // Methods live inside class bodies; closures have no name.
            if ( ! $is_import && ! $class_depth && null !== $j && is_array( $tokens[ $j ] ) && T_STRING === $tokens[ $j ][0] ) {
                snn_php_add_declared_name( $names, 'function', $namespace, $tokens[ $j ][1], $tokens[ $j ][2] );
            }
        } elseif ( in_array( $id, $class_like, true ) ) {
            if ( is_array( $previous ) && T_DOUBLE_COLON === $previous[0] ) {
                // Foo::class is a constant, not a declaration.
            } elseif ( is_array( $previous ) && T_NEW === $previous[0] ) {
                $open_body = true; // Anonymous class: skip its methods, declare nothing.
            } else {
                $j = snn_php_next_significant( $tokens, $i );
                if ( null !== $j && is_array( $tokens[ $j ] ) && T_STRING === $tokens[ $j ][0] ) {
                    snn_php_add_declared_name( $names, 'class', $namespace, $tokens[ $j ][1], $tokens[ $j ][2] );
                    $open_body = true;
                }
            }
        }

        $previous = $token;
    }

    return $names;
}

/**
 * Would running this code redeclare a function or class that already exists?
 *
 * Checked against three sources:
 *  - everything WordPress, plugins and the theme have declared in this request.
 *    The snippets page never runs snippets, so this never includes the
 *    snippet's own previous version;
 *  - the other snippets that run, scanned from their code, because they are not
 *    loaded on the snippets page;
 *  - the snippet itself, which may declare the same name twice.
 *
 * @return true|array True when clean, otherwise array( message, line ).
 */
function snn_check_php_redeclarations( $code, $slug ) {
    $mine     = snn_php_declared_names( $code );
    $problems = array();
    $kinds    = array(
        'function' => __( 'function', 'snn' ),
        'class'    => __( 'class', 'snn' ),
    );

    foreach ( $mine['duplicates'] as $duplicate ) {
        if ( isset( $mine['guarded'][ $duplicate['key'] ] ) ) {
            continue;
        }
        $problems[] = array(
            /* translators: 1: "function" or "class", 2: name, 3: first line, 4: second line */
            'message' => sprintf( __( 'Cannot redeclare %1$s %2$s: it is declared twice in this snippet (lines %3$d and %4$d).', 'snn' ), $kinds[ $duplicate['kind'] ], $duplicate['label'], $duplicate['first_line'], $duplicate['line'] ),
            'line'    => $duplicate['line'],
        );
    }

    foreach ( $kinds as $kind => $kind_label ) {
        foreach ( $mine[ $kind ] as $key => $info ) {
            if ( isset( $mine['guarded'][ $key ] ) ) {
                continue;
            }
            if ( 'function' === $kind ) {
                $exists = function_exists( $key );
            } else {
                $exists = class_exists( $key, false ) || interface_exists( $key, false ) || trait_exists( $key, false )
                    || ( function_exists( 'enum_exists' ) && enum_exists( $key, false ) );
            }
            if ( $exists ) {
                $problems[] = array(
                    /* translators: 1: "function" or "class", 2: name */
                    'message' => sprintf( __( 'Cannot redeclare %1$s %2$s: it already exists (declared by WordPress, a plugin or the theme). Rename it, or wrap it in a function_exists()/class_exists() check.', 'snn' ), $kind_label, $info['label'] ),
                    'line'    => $info['line'],
                );
            }
        }
    }

    foreach ( snn_snippet_other_running_php( $slug ) as $other => $other_code ) {
        $theirs = snn_php_declared_names( $other_code );
        foreach ( $kinds as $kind => $kind_label ) {
            foreach ( $mine[ $kind ] as $key => $info ) {
                if ( isset( $theirs[ $kind ][ $key ] ) && ! isset( $mine['guarded'][ $key ] ) && ! isset( $theirs['guarded'][ $key ] ) ) {
                    $problems[] = array(
                        /* translators: 1: "function" or "class", 2: name, 3: other snippet title */
                        'message' => sprintf( __( 'Cannot redeclare %1$s %2$s: the "%3$s" snippet already declares it.', 'snn' ), $kind_label, $info['label'], snn_snippet_title( $other ) ),
                        'line'    => $info['line'],
                    );
                }
            }
        }
    }

    if ( ! $problems ) {
        return true;
    }

    $first = $problems[0];
    if ( count( $problems ) > 1 ) {
        /* translators: %d: number of further problems */
        $first['message'] .= ' ' . sprintf( _n( '(%d more name conflict.)', '(%d more name conflicts.)', count( $problems ) - 1, 'snn' ), count( $problems ) - 1 );
    }
    return $first;
}

/**
 * The PHP of every other snippet that runs (switched on, not blocked), legacy
 * and modern, keyed by snippet key and ready to scan for declared names.
 */
function snn_snippet_other_running_php( $exclude ) {
    $codes = array();
    foreach ( snn_snippet_slugs() as $other ) {
        if ( $other !== $exclude && snn_snippet_is_enabled( $other ) && ! snn_snippet_get_error( $other ) ) {
            $codes[ $other ] = snn_get_code_snippet_content( $other );
        }
    }
    $cache = snn_snippets_cache();
    foreach ( $cache['active'] as $snippet ) {
        $key = snn_snippet_modern_key( $snippet['id'] );
        if ( $key !== $exclude && snn_snippet_type_runs_php( $snippet['type'] ) && ! snn_snippet_get_error( $key ) ) {
            $codes[ $key ] = snn_snippet_exec_code( $snippet['type'], $snippet['code'] );
        }
    }
    return $codes;
}

/**
 * Everything that can be checked without running the code: syntax, then
 * function/class redeclarations.
 *
 * @return true|array True when clean, otherwise array( type, message, line, target ).
 */
function snn_snippet_static_check( $code, $slug ) {
    $syntax = snn_check_php_syntax( $code );
    if ( is_array( $syntax ) ) {
        return array(
            'type'    => __( 'Parse error', 'snn' ),
            'message' => $syntax['message'],
            'line'    => (int) $syntax['line'],
            'target'  => '',
        );
    }

    $redeclared = snn_check_php_redeclarations( $code, $slug );
    if ( is_array( $redeclared ) ) {
        return array(
            'type'    => __( 'Name conflict', 'snn' ),
            'message' => $redeclared['message'],
            'line'    => (int) $redeclared['line'],
            'target'  => '',
        );
    }

    return true;
}

/**
 * The pages a test run can load, keyed by target.
 */
function snn_snippet_test_target_defs() {
    return array(
        'admin'    => array(
            'label'   => __( 'Admin area (logged in)', 'snn' ),
            'url'     => admin_url( 'profile.php' ),
            'cookies' => true,
        ),
        'home_in'  => array(
            'label'   => __( 'Front end (logged in)', 'snn' ),
            'url'     => home_url( '/' ),
            'cookies' => true,
        ),
        'home_out' => array(
            'label'   => __( 'Front end (logged out)', 'snn' ),
            'url'     => home_url( '/' ),
            'cookies' => false,
        ),
    );
}

/**
 * Pages that must load cleanly before a snippet's draft may go live: the places
 * that snippet actually executes.
 */
function snn_snippet_test_targets_for( $slug, $settings = null ) {
    if ( is_array( $settings ) ) {
        // Modern snippet: HTML, CSS and JavaScript cannot crash anything.
        if ( ! snn_snippet_type_runs_php( $settings['type'] ) ) {
            return array();
        }
        $map = snn_snippet_location_map();
        return isset( $map[ $settings['location'] ] ) ? $map[ $settings['location'] ]['targets'] : array( 'admin', 'home_in', 'home_out' );
    }
    switch ( $slug ) {
        case 'snn-snippet-functions-php':
            return array( 'admin', 'home_in', 'home_out' );
        case 'snn-snippet-admin-head':
            return array( 'admin' );
        default: // Frontend head and footer.
            return array( 'home_in', 'home_out' );
    }
}

/** Whether a URL points at this site, so a test may load it. */
function snn_snippet_is_own_url( $url ) {
    $scheme = strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) );
    return in_array( $scheme, array( 'http', 'https' ), true )
        && strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) ) === strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
}

/**
 * A front-end page that satisfies one group of "show" conditions, or ''.
 * Only the rules that point at a page are used; the rest (logged-in, device)
 * do not change which page to load.
 */
function snn_snippet_test_url_for_group( $group ) {
    $url = '';
    foreach ( $group as $rule ) {
        if ( 'is_not' === $rule['op'] ) {
            continue;
        }
        switch ( $rule['rule'] ) {
            case 'post_id':
                $link = get_permalink( (int) $rule['value'] );
                if ( $link ) {
                    return $link;
                }
                break;
            case 'post_type':
                $newest = get_posts( array( 'post_type' => $rule['value'], 'post_status' => 'publish', 'posts_per_page' => 1, 'fields' => 'ids', 'suppress_filters' => true ) );
                if ( $newest ) {
                    $url = get_permalink( $newest[0] );
                }
                break;
            case 'page_type':
                if ( 'blog' === $rule['value'] && get_option( 'page_for_posts' ) ) {
                    $url = get_permalink( (int) get_option( 'page_for_posts' ) );
                } elseif ( 'singular' === $rule['value'] ) {
                    $newest = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 1, 'fields' => 'ids', 'suppress_filters' => true ) );
                    $url    = $newest ? get_permalink( $newest[0] ) : '';
                } elseif ( 'archive' === $rule['value'] ) {
                    $url = get_post_type_archive_link( 'post' );
                } elseif ( 'search' === $rule['value'] ) {
                    $url = add_query_arg( 's', 'snn-snippet-test', home_url( '/' ) );
                } elseif ( '404' === $rule['value'] ) {
                    $url = home_url( '/snn-snippet-test-page-not-found/' );
                }
                break;
            case 'url_path':
                if ( 'contains' !== $rule['op'] ) {
                    $url = home_url( '/' . ltrim( $rule['value'], '/' ) );
                }
                break;
        }
    }
    return $url ? $url : '';
}

/**
 * The page each test target loads. Admin targets load the profile screen.
 * Front-end targets load the URL the editor names, else a page picked to
 * match the snippet's conditions (the newest product for "Post type is
 * Product"), else the home page. During the test the draft runs whatever its
 * conditions say, so the page only decides which code paths get exercised.
 */
function snn_snippet_test_urls( $targets, $settings = null, $test_url = '' ) {
    $defs  = snn_snippet_test_target_defs();
    $front = home_url( '/' );
    if ( '' !== $test_url && snn_snippet_is_own_url( $test_url ) ) {
        $front = $test_url;
    } elseif ( is_array( $settings ) && ! empty( $settings['conditions']['enabled'] ) && 'show' === $settings['conditions']['action'] ) {
        foreach ( $settings['conditions']['groups'] as $group ) {
            $picked = snn_snippet_test_url_for_group( $group );
            if ( '' !== $picked ) {
                $front = $picked;
                break;
            }
        }
    }

    $urls = array();
    foreach ( $targets as $target ) {
        $urls[ $target ] = 'admin' === $target ? $defs['admin']['url'] : $front;
    }
    return $urls;
}

/**
 * The test run this request belongs to, or false.
 *
 * A test page load is an ordinary request to the site carrying a single-use
 * token. Every live snippet runs as usual, except that the snippet under test
 * runs its draft instead of its live code. Resolved from the query string and a
 * transient only - no user or post lookups - because it is needed at load time,
 * before any snippet executes.
 *
 * @return array|false array( token, target, slug, code ), or false.
 */
function snn_snippet_test_context() {
    static $context = null;
    if ( null !== $context ) {
        return $context;
    }
    $context = false;

    if ( ! isset( $_GET['snn_snippet_test'], $_GET['snn_snippet_target'] )
        || ! is_string( $_GET['snn_snippet_test'] ) || ! is_string( $_GET['snn_snippet_target'] ) ) {
        return $context;
    }

    $token  = preg_replace( '/[^A-Za-z0-9]/', '', wp_unslash( $_GET['snn_snippet_test'] ) );
    $target = preg_replace( '/[^a-z_]/', '', wp_unslash( $_GET['snn_snippet_target'] ) );
    if ( 32 !== strlen( $token ) ) {
        return $context;
    }

    $test = get_transient( SNN_SNIPPET_TEST_TRANSIENT . $token );
    // Each target reports once; a result already on record means this token is spent.
    if ( ! is_array( $test ) || empty( $test['targets'] ) || ! in_array( $target, $test['targets'], true ) || isset( $test['results'][ $target ] ) ) {
        return $context;
    }

    $context = array(
        'token'    => $token,
        'target'   => $target,
        'slug'     => (string) $test['slug'],
        'code'     => (string) $test['code'],
        // Modern snippets: the draft's settings, which may differ from the live ones.
        'settings' => ( isset( $test['settings'] ) && is_array( $test['settings'] ) ) ? $test['settings'] : null,
    );
    return $context;
}

/**
 * Everything that went wrong during a test page load. Test loads never write to
 * the error log or the safety state; problems are collected here and stored
 * with the test run's result.
 */
function snn_snippet_test_log( $entry = null ) {
    static $entries = array();
    if ( null !== $entry && count( $entries ) < 50 ) {
        $entries[] = $entry;
    }
    return $entries;
}

/**
 * Turn this request into a test page load when it carries a valid token.
 * Called at load time, before any snippet can run.
 */
function snn_snippet_test_boot() {
    if ( ! snn_snippet_test_context() ) {
        return;
    }

    // WordPress' own sandbox flag: a fatal here must not show the "critical
    // error" screen, send the recovery-mode email or pause the theme.
    if ( ! defined( 'WP_SANDBOX_SCRAPING' ) ) {
        define( 'WP_SANDBOX_SCRAPING', true );
    }
    // Never let a page cache keep a page rendered with unpublished code.
    if ( ! defined( 'DONOTCACHEPAGE' ) ) {
        define( 'DONOTCACHEPAGE', true );
    }
    add_action( 'send_headers', 'snn_snippet_test_headers' );
    register_shutdown_function( 'snn_snippet_test_record_result' );
}

/** No caching, no indexing for test page loads. */
function snn_snippet_test_headers() {
    nocache_headers();
    header( 'X-Robots-Tag: noindex, nofollow' );
}

/**
 * Shutdown: store how this test page load went with its test run. Runs after a
 * fatal error and after exit(), so a page that dies still reports.
 */
function snn_snippet_test_record_result() {
    static $recorded = false;
    $context = snn_snippet_test_context();
    if ( ! $context || $recorded ) {
        return;
    }
    $recorded = true;

    $fatal = null;
    $error = error_get_last();
    if ( $error && in_array( $error['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR ), true ) ) {
        $error_file   = wp_normalize_path( (string) $error['file'] );
        $from_snippet = ( 0 === strpos( $error_file, wp_normalize_path( __FILE__ ) ) ) && ( false !== strpos( $error_file, "eval()'d code" ) );
        $located      = snn_snippet_locate_line( $error_file, $error['line'] );
        $slug         = $located ? $located['slug'] : snn_snippet_active_slug();
        $executed     = snn_snippet_executed_slugs();
        if ( $from_snippet && '' === $slug && 1 === count( $executed ) ) {
            $slug = $executed[0];
        }
        if ( $located ) {
            $error['line'] = $located['line'];
        }
        $root    = array( ABSPATH, wp_normalize_path( ABSPATH ) );
        $message = str_replace( $root, '', (string) $error['message'] );
        // An uncaught exception's message carries its file, line and a stack
        // trace. The line is reported on its own, so keep just the error.
        $message = preg_replace( '/\s*Stack trace:.*$/s', '', $message );
        $message = preg_replace( '/ in (?!.* in ).*:\d+$/s', '', $message );
        $fatal   = array(
            'type'         => snn_get_php_error_type_string( $error['type'] ),
            'message'      => $message,
            'line'         => $from_snippet ? (int) $error['line'] : 0,
            'file'         => $from_snippet ? '' : str_replace( $root, '', $error_file ) . ':' . (int) $error['line'],
            'from_snippet' => $from_snippet,
            'slug'         => $from_snippet ? $slug : '',
        );
    }

    $key  = SNN_SNIPPET_TEST_TRANSIENT . $context['token'];
    $test = get_transient( $key );
    if ( ! is_array( $test ) ) {
        return;
    }

    $test['results'][ $context['target'] ] = array(
        'user'  => function_exists( 'get_current_user_id' ) ? get_current_user_id() : 0,
        'ran'   => in_array( $context['slug'], snn_snippet_executed_slugs(), true ),
        'fatal' => $fatal,
        'log'   => snn_snippet_test_log(),
    );
    set_transient( $key, $test, max( 60, (int) $test['expires'] - time() ) );
}

/**
 * Register the Custom Post Type for Code Snippets.
 */
function snn_custom_codes_snippets_register_cpt() {
    $labels = array(
        'name'               => _x( 'Code Snippets', 'post type general name', 'snn' ),
        'singular_name'      => _x( 'Code Snippet', 'post type singular name', 'snn' ),
        'all_items'          => __( 'All Code Snippets', 'snn' ),
        'edit_item'          => __( 'Edit Code Snippet', 'snn' ),
        'new_item'           => __( 'New Code Snippet', 'snn' ),
        'view_item'          => __( 'View Code Snippet', 'snn' ),
        'search_items'       => __( 'Search Code Snippets', 'snn' ),
        'not_found'          => __( 'No code snippets found', 'snn' ),
        'not_found_in_trash' => __( 'No code snippets found in Trash', 'snn' ),
        'revisions'          => __( 'Revisions', 'snn' ),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'publicly_queryable' => false,
        'show_ui'            => false,
        'show_in_menu'       => false,
        'query_var'          => false,
        'rewrite'            => false,
        'capability_type'    => 'post',
        'map_meta_cap'       => true,
        'hierarchical'       => false,
        'supports'           => array( 'title', 'editor', 'revisions' ),
        'has_archive'        => false,
        'show_in_rest'       => false,
    );
    register_post_type( 'snn_code_snippet', $args );
}
add_action( 'init', 'snn_custom_codes_snippets_register_cpt' );

/**
 * Add the submenu page for managing snippets.
 */
function snn_custom_codes_snippets_add_submenu() {
    $hook = add_submenu_page(
        'snn-settings', // Parent slug
        __( 'Code Snippets', 'snn' ), // Page title
        __( 'Code Snippets', 'snn' ), // Menu title
        'manage_options', // Capability
        'snn-custom-codes-snippets', // Menu slug
        'snn_custom_codes_snippets_page' // Function to display the page
    );
    // Modern snippet actions are handled before any output, so they can redirect.
    if ( $hook ) {
        add_action( 'load-' . $hook, 'snn_snippets_load_page' );
    }
}
add_action( 'admin_menu', 'snn_custom_codes_snippets_add_submenu', 10 );

/**
 * Scripts and the code editor for the snippets page.
 *
 * The page's own script is always loaded. It used to ride on the code
 * editor's script, so switching the code editor off in the user profile also
 * dropped the confirmation prompts, the test runner and every other control.
 */
function snn_custom_codes_snippets_enqueue_assets( $hook ) {
    if ( ! isset( $_GET['page'] ) || 'snn-custom-codes-snippets' !== $_GET['page'] || ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $view   = snn_snippets_current_view();
    $types  = snn_snippet_code_types();
    $editor = false;
    if ( in_array( $view, array( 'legacy', 'edit', 'new' ), true ) ) {
        $mode = $types['html_php']['mode'];
        if ( 'edit' === $view || 'new' === $view ) {
            $state = snn_snippets_editor_state( snn_snippets_requested_id() );
            $mode  = $types[ $state['settings']['type'] ]['mode'];
        }
        $editor = wp_enqueue_code_editor( array( 'type' => $mode ) );
    }

    $modes = array();
    foreach ( $types as $type => $info ) {
        $modes[ $type ] = $info['mode'];
    }

    wp_register_script( 'snn-code-snippets', false, $editor ? array( 'jquery', 'code-editor' ) : array( 'jquery' ), '2', true );
    wp_enqueue_script( 'snn-code-snippets' );
    wp_enqueue_style( 'dashicons' );

    $config = array(
        'editor'  => $editor,
        'modes'   => $modes,
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonces'  => array(
            'revision' => wp_create_nonce( 'snn_preview_revision_nonce' ),
            'dismiss'  => wp_create_nonce( 'snn_dismiss_fatal_notice_nonce' ),
            'toggle'   => wp_create_nonce( 'snn_snippet_toggle' ),
            'search'   => wp_create_nonce( 'snn_snippet_search' ),
        ),
        'i18n'    => array(
            'loading'          => __( 'Loading...', 'snn' ),
            'error'            => __( 'Error', 'snn' ),
            'ajaxError'        => __( 'Could not reach the site. Please try again.', 'snn' ),
            'confirmRestore'   => __( 'Load this revision as a draft and test it? It only goes live if it passes the test; until then the current version keeps running.', 'snn' ),
            'confirmClearRevs' => __( 'Are you absolutely sure you want to delete all revisions for this snippet? This action cannot be undone.', 'snn' ),
            'confirmClearLogs' => __( 'Are you absolutely sure you want to delete all error logs? This action cannot be undone.', 'snn' ),
            'confirmDelete'    => __( 'Delete this snippet permanently? Its revisions are deleted too.', 'snn' ),
            'confirmBulkDel'   => __( 'Delete the selected snippets permanently?', 'snn' ),
            'hasDraft'         => __( 'This snippet has an unpublished draft. Open it to test and publish the draft (or discard it) first?', 'snn' ),
            'enableLogic'      => __( 'Enable logic', 'snn' ),
            'show'             => __( 'Show', 'snn' ),
            'hide'             => __( 'Hide', 'snn' ),
            'snippetIf'        => __( 'this code snippet if', 'snn' ),
            'or'               => __( 'or', 'snn' ),
            'and'              => __( 'AND', 'snn' ),
            'addGroup'         => __( '+ Add new group', 'snn' ),
            'remove'           => __( 'Remove rule', 'snn' ),
            'needsPage'        => __( '(needs a location after the page query)', 'snn' ),
            'searchPosts'      => __( 'Type to search, or enter an ID', 'snn' ),
            'cacheWarning'     => __( 'Logged-in, user role and device rules are decided on the server. A page cache serves the same copy to everyone, so on cached pages these rules may not apply as expected.', 'snn' ),
            'unavailable'      => __( 'Greyed-out rules need to know which page is showing; this location runs before that. They are removed when you save.', 'snn' ),
        ),
    );
    wp_add_inline_script( 'snn-code-snippets', 'window.snnSnippets = ' . wp_json_encode( $config ) . ";\n" . snn_snippets_admin_js() );
}
add_action( 'admin_enqueue_scripts', 'snn_custom_codes_snippets_enqueue_assets' );

/**
 * The snippets page script: code editors, revisions, confirmations, the list
 * switches, the conditional logic builder and the browser test runner.
 */
function snn_snippets_admin_js() {
    return <<<'JS'
jQuery( function ( $ ) {
    var cfg  = window.snnSnippets || {};
    var i18n = cfg.i18n || {};

    // ----- Code editors ---------------------------------------------------
    var editors = {};
    $( '#snn_frontend_code, #snn_footer_code, #snn_admin_code, #snn_functions_code, #snn_modern_code' ).each( function () {
        if ( cfg.editor && window.wp && wp.codeEditor ) {
            editors[ this.id ] = wp.codeEditor.initialize( this, cfg.editor ).codemirror;
        } else {
            // The code editor is off in this user's profile: a plain monospace box.
            $( this ).css( { 'font-family': 'monospace', 'font-size': '13px', width: '100%' } );
        }
    } );

    // ----- Conditional logic builder ---------------------------------------
    var condBox = document.getElementById( 'snn-conditions' );
    if ( condBox ) {
        initConditions( condBox );
    }

    function initConditions( box ) {
        var rules  = JSON.parse( box.getAttribute( 'data-rules' ) || '{}' );
        var titles = JSON.parse( box.getAttribute( 'data-post-titles' ) || '{}' ) || {};
        var input  = document.getElementById( 'snn_conditions_input' );
        var state;
        try { state = JSON.parse( input.value || '{}' ); } catch ( e ) { state = {}; }
        if ( ! state || typeof state !== 'object' ) { state = {}; }
        state.enabled = !! state.enabled;
        state.action  = state.action === 'hide' ? 'hide' : 'show';
        state.groups  = Array.isArray( state.groups ) ? state.groups : [];
        var knowsPage = true;

        function el( tag, attrs, text ) {
            var node = document.createElement( tag );
            Object.keys( attrs || {} ).forEach( function ( name ) {
                if ( name === 'className' ) { node.className = attrs[ name ]; } else { node.setAttribute( name, attrs[ name ] ); }
            } );
            if ( text !== undefined ) { node.textContent = text; }
            return node;
        }
        function select( pairs, value, onChange ) {
            var node = el( 'select' );
            pairs.forEach( function ( pair ) {
                var option = el( 'option', { value: pair[0] }, pair[1] );
                if ( pair[0] === value ) { option.selected = true; }
                node.appendChild( option );
            } );
            node.addEventListener( 'change', function () { onChange( node.value ); } );
            return node;
        }
        function available( name ) { return ! rules[ name ].page || knowsPage; }
        function firstRule() {
            var names = Object.keys( rules );
            for ( var i = 0; i < names.length; i++ ) { if ( available( names[ i ] ) ) { return names[ i ]; } }
            return names[0];
        }
        function defaultValue( name ) { return rules[ name ].values.length ? rules[ name ].values[0][0] : ''; }
        function newRule() { var name = firstRule(); return { rule: name, op: rules[ name ].ops[0][0], value: defaultValue( name ) }; }
        function save() { input.value = JSON.stringify( state ); }

        function postPicker( rule ) {
            var wrap   = el( 'span', { className: 'snn-post-picker' } );
            var listId = 'snn-post-list-' + Math.random().toString( 36 ).slice( 2 );
            var field  = el( 'input', { type: 'text', className: 'regular-text', list: listId, placeholder: i18n.searchPosts } );
            var list   = el( 'datalist', { id: listId } );
            var timer  = null;
            field.value = rule.value ? rule.value + ( titles[ rule.value ] ? ' — ' + titles[ rule.value ] : '' ) : '';
            field.addEventListener( 'input', function () {
                var match = /^\s*(\d+)/.exec( field.value );
                rule.value = match ? match[1] : '';
                save();
                clearTimeout( timer );
                var term = field.value.trim();
                if ( term.length < 2 || /^\d+ — /.test( term ) ) { return; }
                timer = setTimeout( function () {
                    $.get( cfg.ajaxUrl, { action: 'snn_snippet_search_posts', nonce: cfg.nonces.search, term: term } ).done( function ( response ) {
                        list.innerHTML = '';
                        ( response && response.success ? response.data : [] ).forEach( function ( post ) {
                            titles[ post.id ] = post.title;
                            list.appendChild( el( 'option', { value: post.id + ' — ' + post.title }, post.type ) );
                        } );
                    } );
                }, 250 );
            } );
            wrap.appendChild( field );
            wrap.appendChild( list );
            return wrap;
        }

        function ruleRow( group, rule, gi, ri ) {
            if ( ! rules[ rule.rule ] ) { rule.rule = firstRule(); rule.op = rules[ rule.rule ].ops[0][0]; rule.value = defaultValue( rule.rule ); }
            var def = rules[ rule.rule ];
            var row = el( 'div', { className: 'snn-cond-rule' + ( available( rule.rule ) ? '' : ' is-unavailable' ) } );

            var names = Object.keys( rules ).map( function ( name ) {
                return [ name, rules[ name ].label + ( available( name ) ? '' : ' ' + i18n.needsPage ) ];
            } );
            var ruleSelect = select( names, rule.rule, function ( name ) {
                rule.rule = name; rule.op = rules[ name ].ops[0][0]; rule.value = defaultValue( name );
                save(); render();
            } );
            Array.prototype.forEach.call( ruleSelect.options, function ( option ) {
                if ( ! available( option.value ) && option.value !== rule.rule ) { option.disabled = true; }
            } );
            row.appendChild( ruleSelect );
            row.appendChild( select( def.ops, rule.op, function ( op ) { rule.op = op; save(); } ) );

            if ( def.input === 'text' ) {
                var text = el( 'input', { type: 'text', className: 'regular-text', placeholder: '/shop/' } );
                text.value = rule.value;
                text.addEventListener( 'input', function () { rule.value = text.value; save(); } );
                row.appendChild( text );
            } else if ( def.input === 'post' ) {
                row.appendChild( postPicker( rule ) );
            } else {
                row.appendChild( select( def.values, rule.value, function ( value ) { rule.value = value; save(); } ) );
            }

            var remove = el( 'button', { type: 'button', className: 'button-link snn-cond-remove', 'aria-label': i18n.remove }, '×' );
            remove.addEventListener( 'click', function () {
                group.splice( ri, 1 );
                if ( ! group.length ) { state.groups.splice( gi, 1 ); }
                if ( ! state.groups.length ) { state.enabled = false; }
                save(); render();
            } );
            row.appendChild( remove );
            return row;
        }

        function render() {
            box.innerHTML = '';
            var enable = el( 'label', { className: 'snn-cond-enable' } );
            var check  = el( 'input', { type: 'checkbox' } );
            check.checked = state.enabled;
            check.addEventListener( 'change', function () {
                state.enabled = check.checked;
                if ( state.enabled && ! state.groups.length ) { state.groups.push( [ newRule() ] ); }
                save(); render();
            } );
            enable.appendChild( check );
            enable.appendChild( document.createTextNode( ' ' + i18n.enableLogic ) );
            box.appendChild( enable );

            if ( ! state.enabled ) { save(); return; }

            var head = el( 'div', { className: 'snn-cond-head' } );
            head.appendChild( select( [ [ 'show', i18n.show ], [ 'hide', i18n.hide ] ], state.action, function ( action ) { state.action = action; save(); } ) );
            head.appendChild( el( 'span', {}, ' ' + i18n.snippetIf ) );
            box.appendChild( head );

            var usesCache = false, usesUnavailable = false;
            state.groups.forEach( function ( group, gi ) {
                if ( gi > 0 ) { box.appendChild( el( 'div', { className: 'snn-cond-or' }, i18n.or ) ); }
                var node = el( 'div', { className: 'snn-cond-group' } );
                group.forEach( function ( rule, ri ) {
                    node.appendChild( ruleRow( group, rule, gi, ri ) );
                    if ( rules[ rule.rule ] && rules[ rule.rule ].cache ) { usesCache = true; }
                    if ( rules[ rule.rule ] && ! available( rule.rule ) ) { usesUnavailable = true; }
                } );
                var and = el( 'button', { type: 'button', className: 'button button-primary snn-cond-and' }, i18n.and );
                and.addEventListener( 'click', function () { group.push( newRule() ); save(); render(); } );
                node.appendChild( and );
                box.appendChild( node );
            } );

            var add = el( 'button', { type: 'button', className: 'button button-primary snn-cond-add-group' }, i18n.addGroup );
            add.addEventListener( 'click', function () { state.groups.push( [ newRule() ] ); save(); render(); } );
            box.appendChild( add );

            if ( usesUnavailable ) { box.appendChild( el( 'p', { className: 'snn-cond-note is-warning' }, i18n.unavailable ) ); }
            if ( usesCache ) { box.appendChild( el( 'p', { className: 'snn-cond-note' }, i18n.cacheWarning ) ); }
            save();
        }

        window.snnConditionsKnowsPage = function ( value ) { knowsPage = value; render(); };
        render();
    }

    // ----- Code type and location ------------------------------------------
    var typeSelect  = $( '#snn_code_type' );
    var placeSelect = $( '#snn_location' );
    var initialType = typeSelect.val();

    function applyLocation() {
        var option = placeSelect.find( 'option:selected' );
        $( '.snn-location-help' ).text( option.data( 'help' ) || '' );
        if ( window.snnConditionsKnowsPage ) { window.snnConditionsKnowsPage( String( option.data( 'knows-page' ) ) === '1' ); }
    }
    function applyType() {
        var type = typeSelect.val();
        var php  = type === 'php' || type === 'html_php';
        var cm   = editors.snn_modern_code;
        if ( cm ) {
            cm.setOption( 'mode', cfg.modes[ type ] );
            // Lint rules only exist for the type the editor was loaded with.
            if ( type !== initialType ) { cm.setOption( 'lint', false ); }
        }
        $( '.snn-type-help' ).prop( 'hidden', true ).filter( '[data-type="' + type + '"]' ).prop( 'hidden', false );
        placeSelect.find( 'option' ).each( function () {
            this.disabled = ! php && $( this ).data( 'stage' ) !== 'output';
        } );
        if ( placeSelect.find( 'option:selected' ).prop( 'disabled' ) ) { placeSelect.val( 'site_head' ); }
        applyLocation();
    }
    if ( typeSelect.length ) {
        typeSelect.on( 'change', applyType );
        placeSelect.on( 'change', applyLocation );
        applyType();
    }

    // ----- Revisions --------------------------------------------------------
    $( 'body' ).on( 'click', '.snn-preview-revision', function ( e ) {
        e.preventDefault();
        var button = $( this );
        var label  = button.text();
        var target = $( '.snn-revisions-panel' ).data( 'active-editor-id' );
        button.prop( 'disabled', true ).text( i18n.loading );
        $.post( cfg.ajaxUrl, { action: 'snn_get_revision_content', revision_id: button.data( 'revision-id' ), nonce: cfg.nonces.revision } )
            .done( function ( response ) {
                if ( response && response.success ) {
                    if ( editors[ target ] ) {
                        editors[ target ].setValue( response.data.content );
                        editors[ target ].refresh();
                    } else {
                        $( '#' + target ).val( response.data.content );
                    }
                    $( '.snn-restore-revision-button' ).hide();
                    button.closest( 'li' ).find( '.snn-restore-revision-button' ).show();
                } else {
                    alert( i18n.error + ': ' + ( response && response.data && response.data.message ? response.data.message : i18n.ajaxError ) );
                }
            } )
            .fail( function () { alert( i18n.ajaxError ); } )
            .always( function () { button.prop( 'disabled', false ).text( label ); } );
    } );

    // ----- Confirmations ----------------------------------------------------
    function confirmOn( selector, message ) {
        $( 'body' ).on( 'click', selector, function ( e ) {
            if ( ! confirm( message ) ) { e.preventDefault(); }
        } );
    }
    confirmOn( '.snn-restore-revision-button', i18n.confirmRestore );
    confirmOn( '.snn-clear-revisions-button', i18n.confirmClearRevs );
    confirmOn( '.snn-clear-error-logs-button', i18n.confirmClearLogs );
    confirmOn( '.snn-delete-snippet', i18n.confirmDelete );
    $( '#snn-bulk-form' ).on( 'submit', function ( e ) {
        if ( $( '#snn-bulk-action' ).val() === 'delete' && ! confirm( i18n.confirmBulkDel ) ) { e.preventDefault(); }
    } );

    // ----- Fatal error notice -----------------------------------------------
    $( 'body' ).on( 'click', '.snn-dismiss-fatal-notice', function ( e ) {
        e.preventDefault();
        var button = $( this );
        $.post( cfg.ajaxUrl, { action: 'snn_dismiss_fatal_error_notice', nonce: cfg.nonces.dismiss } )
            .done( function ( response ) {
                if ( response && response.success ) { button.closest( '.snn-fatal-error-notice' ).fadeOut(); }
            } );
    } );

    // ----- List switches ----------------------------------------------------
    // Off is instant. On is tested first when the snippet runs PHP: the page
    // reloads and drives the test from the list.
    $( '.snn-snippet-toggle' ).on( 'change', function () {
        var box = this;
        var on  = box.checked;
        box.disabled = true;
        $.post( cfg.ajaxUrl, { action: 'snn_snippet_toggle', nonce: cfg.nonces.toggle, key: $( box ).data( 'key' ), on: on ? 1 : 0 } )
            .done( function ( response ) {
                if ( response && response.success ) {
                    if ( response.data.state === 'testing' ) { window.location.reload(); return; }
                    if ( response.data.state === 'draft' ) {
                        box.checked = false;
                        if ( confirm( i18n.hasDraft ) ) { window.location.href = response.data.url; }
                    }
                } else {
                    alert( response && response.data && response.data.message ? response.data.message : i18n.ajaxError );
                    box.checked = ! on;
                }
            } )
            .fail( function () { alert( i18n.ajaxError ); box.checked = ! on; } )
            .always( function () { box.disabled = false; } );
    } );

    // ----- Test runner ------------------------------------------------------
    // Loads each run's test pages from the browser, one page at a time (some
    // servers handle a single request at once), then asks the server to judge
    // the run. Only what each page load records on the server counts, so the
    // responses are ignored and failures simply move on.
    $( '[data-snn-test-runner]' ).each( function () {
        if ( ! window.fetch ) { return; }
        var box    = this;
        var conf   = JSON.parse( box.getAttribute( 'data-snn-test-runner' ) );
        var status = box.querySelector( '.snn-test-status' );
        var r = 0, t = 0;

        function say( text ) { if ( status ) { status.textContent = ' ' + text; } }
        function next() {
            var run = conf.runs[ r ];
            if ( ! run ) { window.location.href = conf.returnUrl; return; }
            if ( t >= run.targets.length ) { finish( run ); return; }
            var target     = run.targets[ t++ ];
            var controller = window.AbortController ? new AbortController() : null;
            var timer      = controller ? setTimeout( function () { controller.abort(); }, conf.timeout ) : null;
            say( conf.i18n.testing.replace( '%1$s', run.title ).replace( '%2$s', target.label ) );
            fetch( target.url, {
                credentials: target.cookies ? 'include' : 'omit',
                cache: 'no-store',
                redirect: 'manual',
                signal: controller ? controller.signal : undefined
            } ).catch( function () {} ).then( function () {
                if ( timer ) { clearTimeout( timer ); }
                next();
            } );
        }
        function finish( run ) {
            say( conf.i18n.checking.replace( '%s', run.title ) );
            var body = new FormData();
            body.append( 'action', 'snn_snippet_test_finish' );
            body.append( 'nonce', conf.nonce );
            body.append( 'token', run.token );
            body.append( 'slug', run.slug );
            fetch( conf.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } )
                .then( function ( response ) { return response.json(); } )
                .then( function () { r++; t = 0; next(); } )
                .catch( function () { say( conf.i18n.failed ); } );
        }
        next();
    } );
} );
JS;
}

/**
 * Styles for the snippets page, scoped to it.
 */
function snn_custom_codes_snippets_admin_styles() {
    if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'snn-custom-codes-snippets' || ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <style>
        .snn-snippets-wrap h3 { margin-top: 10px; }
        .snn-snippets-wrap .CodeMirror { min-height: 600px !important; border: 1px solid #dcdcde; }
        .snn-snippet-nav-tab-wrapper { margin-bottom: 15px; }
        .snn-snippet-description { margin-bottom: 10px; font-style: italic; color: #555; }
        .snn-breadcrumb { margin: 4px 0 8px; }
        .snn-legacy-copy { margin: 4px 0 14px; }
        .snn-spacer { flex: 1; }
        .snn-dim { color: #8c8f94; }

        /* Editor and revisions */
        .snn-editor-revision-wrapper { display: flex; flex-wrap: wrap; gap: 20px; margin-top: 5px; }
        .snn-editor-area { flex: 3; min-width: 0; flex-basis: 380px; position: relative; }
        .snn-revisions-panel { flex: 1; flex-basis: 280px; max-width: 360px; border-left: 1px solid #ccd0d4; padding-left: 20px; }
        .snn-revisions-panel-inner { max-height: 680px; overflow-y: auto; padding-right: 10px; }
        .snn-revisions-list { list-style: none; margin: 0; padding: 0; }
        .snn-revisions-list li { margin-bottom: 0; padding-bottom: 5px; border-bottom: 1px solid #eee; }
        .snn-revisions-list li:last-child { border-bottom: none; }
        .snn-revisions-list .revision-info { display: block; font-size: 0.9em; color: #555; margin-bottom: 8px; }
        .snn-revisions-list .revision-actions button,
        .snn-revisions-list .revision-actions .snn-view-comparison-link { margin-right: 5px; margin-top: 5px; vertical-align: middle; }
        .snn-revisions-list .revision-actions .snn-view-comparison-link .dashicons { font-size: 14px; text-decoration: none; vertical-align: text-bottom; position: relative; top: 5px; }
        .snn-revisions-panel h4 { margin-top: 0; font-size: 1.1em; }
        .snn-php-execution-warning { border-left-width: 4px; margin-top: 15px; margin-bottom: 15px; }
        .snn-manage-revisions-section { margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px; }
        @media (max-width: 782px) { .snn-revisions-panel { border-left: 0; padding-left: 0; max-width: none; } }

        .snn-editor-top { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin: 4px 0 12px; }
        .snn-editor-top h2 { margin: 0; font-size: 1.3em; }
        .snn-title-input { width: 100%; font-size: 1.4em; padding: 6px 10px; margin-bottom: 12px; }
        .snn-code-head { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
        .snn-type-help { margin: 0 0 8px; }
        .snn-panel { margin-top: 20px; }
        .snn-panel > h2 { font-size: 14px; padding: 10px 14px; margin: 0; border-bottom: 1px solid #c3c4c7; }
        .snn-panel .inside { padding: 4px 14px 14px; margin: 0; }
        .snn-field { display: grid; grid-template-columns: 170px minmax(0, 1fr); gap: 12px; align-items: start; margin: 14px 0; }
        .snn-field > label { padding-top: 5px; }
        @media (max-width: 782px) { .snn-field { grid-template-columns: minmax(0, 1fr); gap: 4px; } }
        .submit .snn-delete-snippet { margin-left: 16px; color: #b32d2e; }

        /* Switches */
        .snn-switch { position: relative; display: inline-block; width: 36px; height: 20px; flex: none; vertical-align: middle; }
        .snn-switch input { position: absolute; inset: 0; width: 100%; height: 100%; margin: 0; opacity: 0; cursor: pointer; z-index: 1; }
        .snn-switch-slider { position: absolute; inset: 0; background: #c3c4c7; border-radius: 20px; transition: background .15s; }
        .snn-switch-slider::after { content: ""; position: absolute; top: 3px; left: 3px; width: 14px; height: 14px; border-radius: 50%; background: #fff; transition: transform .15s; }
        .snn-switch input:checked + .snn-switch-slider { background: #2271b1; }
        .snn-switch input:checked + .snn-switch-slider::after { transform: translateX(16px); }
        .snn-switch input:focus-visible + .snn-switch-slider { box-shadow: 0 0 0 2px #fff, 0 0 0 4px #2271b1; }
        .snn-switch input:disabled + .snn-switch-slider { opacity: .5; }
        @media (prefers-reduced-motion: reduce) { .snn-switch-slider, .snn-switch-slider::after { transition: none; } }

        /* List */
        .snn-global-switch { display: flex; gap: 12px; align-items: flex-start; background: #fff; border: 1px solid #c3c4c7; border-left: 4px solid #00a32a; padding: 10px 14px; margin: 14px 0; }
        .snn-global-switch.is-off { border-left-color: #d63638; }
        .snn-global-switch p { margin: 6px 0 0; }
        .snn-tablenav { display: flex; flex-wrap: wrap; gap: 6px 8px; align-items: center; margin: 8px 0; clear: both; }
        .snn-filters { margin-bottom: 0; }
        .snn-snippets-table .snn-col-status { width: 70px; }
        .snn-snippets-table td, .snn-snippets-table th { vertical-align: middle; }
        .snn-snippets-table tr.snn-legacy-group td { background: #fcf3e3; color: #8a5a00; font-weight: 600; font-size: 12px; letter-spacing: .06em; text-transform: uppercase; }
        .snn-tag { display: inline-block; font-size: 11px; font-weight: 600; line-height: 1.7; padding: 0 7px; border-radius: 3px; margin-left: 6px; vertical-align: 1px; background: #f0f0f1; color: #50575e; }
        .snn-tag-type { margin-left: 0; font-family: Consolas, Monaco, monospace; font-weight: 500; }
        .snn-tag-draft { background: #e5f0fa; color: #2271b1; }
        .snn-tag-blocked { background: #fcf0f1; color: #b32d2e; }
        .snn-tag-legacy { background: #fcf3e3; color: #8a5a00; }
        .snn-row-error { display: block; color: #b32d2e; font-size: 12px; margin-top: 2px; }
        .snn-row-sub { display: block; color: #8c8f94; font-size: 12px; margin-top: 2px; }

        /* Conditional logic */
        .snn-cond-enable { display: inline-block; margin: 6px 0; font-weight: 600; }
        .snn-cond-head { margin: 10px 0; }
        .snn-cond-group { background: #f6f7f7; border: 1px solid #dcdcde; border-radius: 4px; padding: 12px; display: grid; gap: 8px; max-width: 920px; justify-items: start; }
        .snn-cond-rule { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .snn-cond-rule.is-unavailable select, .snn-cond-rule.is-unavailable input { opacity: .55; }
        .snn-cond-remove { font-size: 20px; line-height: 1; text-decoration: none; color: #787c82; padding: 0 4px; }
        .snn-cond-remove:hover { color: #b32d2e; }
        .snn-cond-or { font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: #787c82; margin: 8px 0; }
        .snn-cond-add-group { margin-top: 12px !important; }
        .snn-cond-note { color: #50575e; max-width: 920px; }
        .snn-cond-note.is-warning { color: #8a5a00; }

        /* Error Logs Table Styling */
        .snn-error-logs-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 13px; }
        .snn-error-logs-table th, .snn-error-logs-table td { border: 1px solid #ddd; padding: 10px !important; text-align: left; vertical-align: top; }
        .snn-error-logs-table th { background-color: #f0f0f1; font-weight: 600; position: sticky; top: 0; }
        .snn-error-logs-table td pre { white-space: pre-wrap; word-wrap: break-word; margin: 0; font-size: 12px; font-family: "Courier New", Courier, monospace; }
        .snn-error-logs-table .snn-log-message { max-width: 400px; overflow-wrap: break-word; }
        .snn-error-logs-table tr:hover { background-color: #f9f9f9; }
        .snn-error-logs-table code { background: #fff3cd; padding: 2px 6px; border-radius: 3px; font-size: 12px; }
        .snn-error-logs-table details { cursor: pointer; }
        .snn-error-logs-table details summary { color: #2271b1; font-weight: 500; }
        .snn-error-logs-table details summary:hover { color: #135e96; text-decoration: underline; }

        /* Fatal Error Notice Styling (admin notice) */
        .snn-fatal-error-notice strong { color: #dc3232; }
        .snn-fatal-error-notice code { background: #f9f9f9; border: 1px solid #ddd; padding: 2px 4px; font-size: 0.9em; display: block; white-space: pre-wrap; word-break: break-all; }

        /* Draft / test-before-publish notices */
        .snn-draft-notice pre { max-height: 300px; overflow: auto; background: #f6f7f7; border: 1px solid #dcdcde; padding: 10px; font-size: 12px; white-space: pre-wrap; }
        .snn-draft-notice details { margin: 8px 0; }
        .snn-draft-notice .spinner { float: none; margin: 0 6px; }
    </style>
    <?php
}
add_action( 'admin_head', 'snn_custom_codes_snippets_admin_styles' );

/**
 * Back-compat wrapper around snn_check_php_syntax().
 *
 * The previous implementation called token_get_all() without TOKEN_PARSE, which
 * only tokenizes and never reports syntax errors, then inspected
 * error_get_last() - which returns the last error from anywhere earlier in the
 * request, not from the call above it. It therefore reported phantom errors and
 * missed real ones. Kept only so external callers do not fatal.
 *
 * @deprecated Use snn_check_php_syntax().
 * @return true|array True when valid, otherwise array( message, line, code_context ).
 */
function snn_validate_php_syntax( $code ) {
    $result = snn_check_php_syntax( $code );
    if ( is_array( $result ) ) {
        $result['code_context'] = snn_get_code_context( $code, $result['line'] );
    }
    return $result;
}

/**
 * Helper function to extract code context around a specific line.
 */
function snn_get_code_context( $code, $error_line, $context_lines = 3 ) {
    $lines = explode( "\n", $code );
    $start = max( 0, $error_line - $context_lines - 1 );
    $end = min( count( $lines ), $error_line + $context_lines );
    
    $context = array();
    for ( $i = $start; $i < $end; $i++ ) {
        $marker = ( $i === $error_line - 1 ) ? ' >>> ' : '     ';
        $context[] = sprintf( '%s%4d: %s', $marker, $i + 1, $lines[$i] );
    }
    
    return implode( "\n", $context );
}

/**
 * Helper function to extract function names from code context.
 */
function snn_get_function_context( $code, $error_line ) {
    $lines = explode( "\n", $code );
    $function_name = '';
    
    // Search backwards from error line to find the function declaration
    for ( $i = min( $error_line - 1, count( $lines ) - 1 ); $i >= 0; $i-- ) {
        if ( preg_match( '/function\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*\(/', $lines[$i], $matches ) ) {
            $function_name = $matches[1];
            break;
        }
        // Also check for class methods
        if ( preg_match( '/(?:public|private|protected|static)?\s*function\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*\(/', $lines[$i], $matches ) ) {
            $function_name = $matches[1];
            break;
        }
        // Check for hook callbacks
        if ( preg_match( '/add_(?:action|filter)\s*\(\s*[\'"]([^\'\"]+)[\'"]/', $lines[$i], $matches ) ) {
            return 'Hook: ' . $matches[1];
        }
    }
    
    return $function_name ? 'Function: ' . $function_name : '';
}

/**
 * Helper function to log an error event.
 */
function snn_log_error_event( $type, $message, $snippet_slug, $file = '', $line = 0, $code_context = '', $function_context = '' ) {
    // Test page loads report to their test run, not to the error log.
    if ( snn_snippet_test_context() ) {
        return;
    }

    $logs = get_option( SNN_CUSTOM_CODES_LOG_OPTION, array() );
    if ( ! is_array( $logs ) ) { // Ensure logs is an array
        $logs = array();
    }

    $snippet_title = snn_snippet_title( $snippet_slug );

    $log_entry = array(
        'timestamp'        => current_time( 'mysql' ), // WordPress current time in MySQL format
        'type'             => sanitize_text_field( $type ),
        'message'          => wp_strip_all_tags( $message ), // Basic sanitization for display
        'snippet_slug'     => sanitize_text_field( $snippet_slug ),
        'snippet_title'    => sanitize_text_field( $snippet_title ),
        'file'             => sanitize_text_field( $file ),
        'line'             => absint( $line ),
        'code_context'     => $code_context ? substr( $code_context, 0, 2000 ) : '', // Increased to 2000 chars for better context
        'function_context' => sanitize_text_field( $function_context ),
    );

    // Add new log entry to the beginning of the array
    array_unshift( $logs, $log_entry );

    // Keep only the most recent N entries (defined by SNN_CUSTOM_CODES_MAX_LOG_ENTRIES)
    if ( count( $logs ) > SNN_CUSTOM_CODES_MAX_LOG_ENTRIES ) {
        $logs = array_slice( $logs, 0, SNN_CUSTOM_CODES_MAX_LOG_ENTRIES );
    }

    update_option( SNN_CUSTOM_CODES_LOG_OPTION, $logs );
}

/**
 * Helper function to get a specific code snippet's content from its CPT.
 */
function snn_get_code_snippet_content( $slug ) {
    $id = snn_snippet_modern_id( $slug );
    if ( $id ) {
        $post = get_post( $id );
        return ( $post && 'snn_code_snippet' === $post->post_type ) ? (string) $post->post_content : '';
    }
    $args = array(
        'post_type'        => 'snn_code_snippet',
        'name'             => $slug, // Post slug
        'posts_per_page'   => 1,
        'post_status'      => 'private', // Snippets are stored as private posts
        'suppress_filters' => true, // For consistency, bypass filters
    );
    $snippet_posts = get_posts( $args );
    if ( ! empty( $snippet_posts ) && isset( $snippet_posts[0]->post_content ) ) {
        return $snippet_posts[0]->post_content;
    }
    return ''; // Return empty string if not found
}

/**
 * Helper function to get a specific code snippet's CPT ID by its slug.
 */
function snn_get_code_snippet_id( $slug ) {
    $id = snn_snippet_modern_id( $slug );
    if ( $id ) {
        $post = get_post( $id );
        return ( $post && 'snn_code_snippet' === $post->post_type ) ? $id : 0;
    }
    $args = array(
        'post_type'        => 'snn_code_snippet',
        'name'             => $slug,
        'posts_per_page'   => 1,
        'post_status'      => 'private',
        'fields'           => 'ids', // Only retrieve post IDs
        'suppress_filters' => true,
    );
    $snippet_ids = get_posts( $args );
    return ! empty( $snippet_ids ) ? $snippet_ids[0] : 0; // Return ID or 0 if not found
}

/**
 * Executes a PHP code snippet with output buffering and error handling.
 *
 * Notices, warnings and deprecations are logged but do NOT discard the snippet's
 * output - only a ParseError or Error does, because at that point the output is
 * genuinely incomplete. Syntax is validated once at save time
 * (snn_check_php_syntax), never here.
 */
function snn_execute_php_snippet( $code_to_execute, $snippet_location_slug ) {
    if ( empty( trim( $code_to_execute ) ) ) {
        return ''; // Do nothing if code is empty
    }

    $previous_slug = snn_snippet_active_slug();
    snn_snippet_active_slug( $snippet_location_slug );
    snn_snippet_executed_slugs( $snippet_location_slug );

    // This snippet's own range of line numbers (see snn_snippet_line_ranges()).
    $offset = snn_snippet_line_offset( $snippet_location_slug, $code_to_execute );

    // Custom error handler for non-fatal errors (Warnings, Notices, etc.)
    set_error_handler(function($errno, $errstr, $errfile, $errline) use ($snippet_location_slug, $code_to_execute) {
        // Honour the @ operator and the active error_reporting level, so a
        // snippet's own suppressed calls are not reported as its errors.
        if ( 0 === ( error_reporting() & $errno ) ) {
            return true;
        }

        $error_type_str = 'PHP Error'; // Default type
        switch ($errno) { // Determine error type string
            case E_WARNING: case E_USER_WARNING: $error_type_str = 'PHP Warning'; break;
            case E_NOTICE: case E_USER_NOTICE: $error_type_str = 'PHP Notice'; break;
            case E_DEPRECATED: case E_USER_DEPRECATED: $error_type_str = 'PHP Deprecated'; break;
        }

        // Raised in snippet code: whichever snippet owns that line (a closure
        // another snippet hooked can fire while this one runs). Raised in a
        // WordPress or plugin file: this snippet called it, but there is no
        // line of its own to point at.
        $located = snn_snippet_locate_line( $errfile, $errline );
        $slug    = $located ? $located['slug'] : $snippet_location_slug;
        $line    = $located ? $located['line'] : 0;
        if ( ! $located ) {
            $errstr .= ' (in ' . str_replace( wp_normalize_path( ABSPATH ), '', wp_normalize_path( (string) $errfile ) ) . ':' . (int) $errline . ')';
        }
        $code = ( $slug === $snippet_location_slug ) ? $code_to_execute : snn_get_code_snippet_content( $slug );

        if ( snn_snippet_test_context() ) {
            snn_snippet_test_log( array(
                'kind'    => 'warning',
                'type'    => $error_type_str,
                'message' => $errstr,
                'line'    => $line,
                'slug'    => $slug,
            ) );
            return true;
        }

        $code_context     = $line ? snn_get_code_context( $code, $line ) : '';
        $function_context = $line ? snn_get_function_context( $code, $line ) : '';

        snn_log_error_event( $error_type_str, $errstr, $slug, 'eval()\'d code (runtime)', $line, $code_context, $function_context );
        return true; // Prevent default PHP error handler from running
    });

    $fatal_thrown = false;
    $ob_level     = ob_get_level();
    ob_start(); // Start output buffering

    try {
        // The padding comes first, while the eval is still in PHP mode, so it is
        // whitespace rather than output. The "? >" after it switches to HTML mode,
        // which is why code that doesn't start with <?php is treated as HTML.
        // No @ here: it would neuter error_reporting() inside the handler above and
        // silence every warning the snippet raises.
        eval( str_repeat( "\n", $offset ) . "?>" . $code_to_execute );
    } catch (Throwable $e) { // ParseError, Error, Exception
        // Blame the snippet whose line threw - usually this one, but it can be
        // a closure another snippet hooked. An error thrown inside WordPress
        // is traced back to the snippet line that called it.
        $located          = snn_snippet_locate_throwable( $e );
        $blame            = $located ? $located['slug'] : $snippet_location_slug;
        $error_line       = $located ? $located['line'] : 0;
        $blame_code       = ( $blame === $snippet_location_slug ) ? $code_to_execute : snn_get_code_snippet_content( $blame );
        $code_context     = $error_line ? snn_get_code_context( $blame_code, $error_line ) : '';
        $function_context = $error_line ? snn_get_function_context( $blame_code, $error_line ) : '';
        $type             = ( $e instanceof ParseError ) ? 'PHP Parse Error' : get_class( $e );

        if ( snn_snippet_test_context() ) {
            // Test page load: report to the test run instead of logging and blocking.
            snn_snippet_test_log( array(
                'kind'    => ( $e instanceof Error ) ? 'error' : 'exception',
                'type'    => $type,
                'message' => $e->getMessage(),
                'line'    => (int) $error_line,
                'slug'    => $blame,
            ) );
            $fatal_thrown = ( $e instanceof Error );
        } else {
            snn_log_error_event(
                $type,
                $e->getMessage(),
                $blame,
                'eval()\'d code',
                $error_line,
                $code_context,
                $function_context
            );

            // An Error (which includes ParseError) means this snippet cannot run.
            // Block just this snippet - the others are unaffected. A plain Exception
            // is the snippet's own business and is only logged.
            if ( $e instanceof Error ) {
                $fatal_thrown = true;

                snn_snippet_record_error(
                    $blame,
                    $type,
                    $e->getMessage() . ( $function_context ? ' [' . $function_context . ']' : '' ),
                    $error_line
                );

                set_transient( SNN_FATAL_ERROR_NOTICE_TRANSIENT, array(
                    'message' => $e->getMessage() . ( $function_context ? ' [' . $function_context . ']' : '' ),
                    'file'    => 'Snippet: ' . snn_snippet_title( $blame ),
                    'line'    => $error_line,
                    'type'    => $type,
                    'slug'    => $blame,
                ), DAY_IN_SECONDS );
            }
        }
    } finally {
        // Unwind any buffers the snippet opened and forgot to close, then take
        // ours. If the snippet closed ours too, take nothing.
        while ( ob_get_level() > $ob_level + 1 ) {
            ob_end_clean();
        }
        $output_from_snippet = ( ob_get_level() > $ob_level ) ? ob_get_clean() : '';

        restore_error_handler(); // Restore previous error handler
        snn_snippet_active_slug( $previous_slug );
    }

    // Partial output from a snippet that died mid-render is worse than none.
    if ( $fatal_thrown ) {
        return '';
    }

    return $output_from_snippet; // Return the output from the snippet
}

/**
 * Runs a snippet behind the crash guard.
 *
 * Code that has already survived a complete request is "verified" and runs with
 * zero extra database writes. Code that has not (i.e. was just saved) gets an
 * in-flight marker written before it runs; if the request dies before the marker
 * is cleared, the next request knows exactly which snippet killed it. That covers
 * the fatals no parser can predict - undefined functions, redeclarations, memory
 * exhaustion, execution timeouts - and costs one pair of writes, once.
 */
function snn_snippet_run( $code, $slug ) {
    // Test page loads never touch the crash guard's state: they are a sandbox
    // whose outcome is reported to the test run.
    if ( snn_snippet_test_context() ) {
        return snn_execute_php_snippet( $code, $slug );
    }

    // Deliberately fresh: another request may have recorded an error for this
    // snippet since our in-process cache was filled, and writing a stale copy of
    // the whole state array back would silently un-block a snippet that is
    // actively killing the site.
    $state = snn_get_snippet_state( true );
    $hash  = md5( $code );

    $verified = isset( $state['verified'][ $slug ] ) && $state['verified'][ $slug ] === $hash;

    if ( ! $verified ) {
        $existing = ( isset( $state['in_flight'] ) && is_array( $state['in_flight'] ) ) ? $state['in_flight'] : array();
        $is_same  = isset( $existing['slug'], $existing['hash'] )
            && $existing['slug'] === $slug
            && $existing['hash'] === $hash;

        $state['in_flight'] = array(
            'slug' => $slug,
            'hash' => $hash,
            // Armed-at time. Carried over when a marker for this exact code is
            // already standing, so the grace period counts from the FIRST run.
            // Rewriting it here was the bug that made the crash guard unable to
            // ever fire on a site receiving requests more often than the grace
            // period: every crashing request pushed its own deadline forward.
            'time'    => ( $is_same && ! empty( $existing['time'] ) ) ? (int) $existing['time'] : time(),
            'touched' => time(),
            // Starts without a single request ever reaching the end.
            'starts'  => $is_same ? ( (int) ( isset( $existing['starts'] ) ? $existing['starts'] : 0 ) + 1 ) : 1,
            // Preserve a crash flag another request already proved.
            'crashed' => ( $is_same && ! empty( $existing['crashed'] ) ) ? 1 : 0,
        );
        snn_update_snippet_state( $state );

        if ( ! snn_snippet_pending_verification() ) {
            // Registered after the fatal handler (init:1), so it sees any error
            // that handler recorded and declines to mark the snippet good.
            register_shutdown_function( 'snn_snippet_finalize_pending' );
        }
        snn_snippet_pending_verification( $slug, $hash );
    }

    return snn_execute_php_snippet( $code, $slug );
}

/**
 * End of request: clear the in-flight marker and promote snippets that made it
 * all the way through without a fatal. Runs after snn_fatal_error_shutdown_handler().
 */
function snn_snippet_finalize_pending() {
    $pending = snn_snippet_pending_verification();
    if ( empty( $pending ) ) {
        return;
    }
    // Once: it also runs early, from WordPress' error page.
    static $ran = false;
    if ( $ran ) {
        return;
    }
    $ran = true;

    $error = error_get_last();
    $fatal = $error && in_array( $error['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR ), true );

    $state   = snn_get_snippet_state( true );
    $changed = false;

    if ( $fatal ) {
        /*
         * The request died. Shutdown functions still run after a fatal, so the
         * old code cleared the in-flight marker here - destroying the only
         * evidence the next request had. Whenever the fatal could not be pinned
         * on a snippet by file name (a hook the snippet registered, a file it
         * included, memory exhaustion, a timeout), that left nothing at all
         * behind and the site stayed down forever.
         *
         * Keep the marker and flag it instead: the next request blocks the
         * snippet immediately rather than waiting out the grace period.
         *
         * Skipped when the fatal handler already pinned and blocked a snippet -
         * that crash is accounted for, and the marker may belong to a different,
         * innocent snippet.
         */
        if ( ! snn_snippet_fatal_attributed()
            && isset( $state['in_flight'] ) && is_array( $state['in_flight'] )
            && empty( $state['in_flight']['crashed'] ) ) {
            $state['in_flight']['crashed'] = 1;
            $changed = true;
        }
    } else {
        if ( isset( $state['in_flight'] ) ) {
            unset( $state['in_flight'] );
            $changed = true;
        }

        foreach ( $pending as $slug => $hash ) {
            if ( isset( $state['errors'][ $slug ] ) ) {
                continue; // Something went wrong for this one; do not vouch for it.
            }
            if ( ! isset( $state['verified'][ $slug ] ) || $state['verified'][ $slug ] !== $hash ) {
                $state['verified'][ $slug ] = $hash;
                $changed = true;
            }
        }
    }

    if ( $changed ) {
        snn_update_snippet_state( $state );
    }
}

/**
 * Start of request: if an in-flight marker survived from a previous request, that
 * request died inside the named snippet. Block it and let the site come back up.
 */
function snn_snippet_recover_from_crash() {
    // Once per request: modern snippets call it at after_setup_theme, the
    // legacy ones again at init.
    static $done = false;
    if ( $done ) {
        return;
    }
    $done = true;

    // Recovery writes state; test page loads leave the safety state alone.
    if ( snn_snippet_test_context() ) {
        return;
    }

    // Fresh read: this is the path that brings a dead site back up, so it must
    // never act on a state snapshot taken earlier in the request.
    $state = snn_get_snippet_state( true );

    if ( empty( $state['in_flight']['slug'] ) ) {
        return;
    }

    $in_flight = $state['in_flight'];
    $slug      = $in_flight['slug'];

    /*
     * A marker naming a snippet that no longer exists is stale bookkeeping, not
     * a crash to report. This happens when a snippet type is removed from the
     * theme (the "Advanced Raw Code" feature, for one) while one of its markers
     * was still standing: without this the code below would "recover" a snippet
     * that cannot be edited, logging a fatal and raising an admin notice that
     * points at a tab which is no longer rendered - and which therefore offers
     * no button to clear it. Drop the marker quietly and carry on.
     */
    if ( ! snn_snippet_key_exists( $slug ) ) {
        unset( $state['in_flight'] );
        snn_update_snippet_state( $state );
        return;
    }

    /*
     * Three independent triggers, so recovery does not depend on any single one
     * of them working:
     *
     * 1. 'crashed' - the previous request ended in a fatal and the verification
     *    pass flagged the marker on its way out. This is the common case and it
     *    fires on the very next request, no waiting.
     * 2. 'starts'  - the snippet has been started this many times and not one of
     *    those requests ever reached the end. Catches hard kills (php-fpm
     *    request_terminate_timeout, SIGKILL, OOM killer, segfault) where no
     *    shutdown function ever ran, on a site with traffic.
     * 3. age       - the marker has simply been standing too long. Catches the
     *    same hard kills on a site with no traffic. Measured from when the
     *    marker was armed, never from the last request that touched it.
     */
    $crashed = ! empty( $in_flight['crashed'] );
    $starts  = isset( $in_flight['starts'] ) ? (int) $in_flight['starts'] : 1;
    $armed   = isset( $in_flight['time'] ) ? (int) $in_flight['time'] : 0;
    $age     = $armed > 0 ? ( time() - $armed ) : PHP_INT_MAX;

    if ( ! $crashed && $starts < SNN_SNIPPET_CRASH_MAX_STARTS && $age < SNN_SNIPPET_CRASH_GRACE ) {
        // A concurrent request may legitimately still be inside this snippet.
        return;
    }

    if ( $crashed ) {
        $reason = __( 'A previous request ended in a fatal PHP error while this snippet was executing. The error could not be pinned to an exact line, which usually means it happened inside a hook this snippet registered, inside a file it included, or that the request ran out of memory or time. Fix the snippet and save to re-enable it.', 'snn' );
        $short  = __( 'A previous request ended in a fatal error while this snippet was executing.', 'snn' );
    } else {
        $reason = __( 'A previous request stopped while this snippet was executing and never completed - the process was killed outright (execution timeout, memory limit or a crash). Fix the snippet and save to re-enable it.', 'snn' );
        $short  = __( 'A previous request stopped while this snippet was executing and never finished.', 'snn' );
    }

    snn_log_error_event(
        'PHP Fatal Error (crash guard)',
        $reason . ' ' . sprintf(
            /* translators: 1: number of starts, 2: seconds since the marker was armed */
            __( '(started %1$d time(s) without completing, %2$d second(s) since first run)', 'snn' ),
            $starts,
            PHP_INT_MAX === $age ? 0 : $age
        ),
        $slug,
        'eval()\'d code',
        0,
        '',
        ''
    );

    snn_snippet_record_error( $slug, 'Fatal Error (crash guard)', $reason, 0 );

    set_transient( SNN_FATAL_ERROR_NOTICE_TRANSIENT, array(
        'message' => $short,
        'file'    => 'Snippet: ' . snn_snippet_title( $slug ),
        'line'    => 0,
        'type'    => 'Fatal Error (crash guard)',
        'slug'    => $slug,
    ), DAY_IN_SECONDS );
}

/**
 * The "Run this snippet" checkbox as submitted for one tab: true/false, or null
 * when the submission did not include that tab's checkbox. Only the tab on
 * screen renders it, so a hidden marker says which tab the submission owns -
 * otherwise the absent checkboxes would read as "switch everything off".
 */
function snn_snippet_posted_toggle( $key ) {
    if ( ! isset( $_POST['snn_snippet_toggle_present'] ) || sanitize_key( wp_unslash( $_POST['snn_snippet_toggle_present'] ) ) !== $key ) {
        return null;
    }
    return isset( $_POST['snn_snippet_enabled'] );
}

/**
 * Switch one snippet on or off.
 */
function snn_snippet_set_enabled( $slug, $on ) {
    $map          = snn_get_snippet_enabled_map();
    $map[ $slug ] = $on ? 1 : 0;
    update_option( SNN_SNIPPET_ENABLED_OPTION, $map, true );
    if ( snn_snippet_modern_id( $slug ) ) {
        snn_snippets_rebuild_cache();
    }
}

/**
 * Can snippets execute at all right now: global switch on, no kill constant?
 */
function snn_snippets_execution_possible() {
    if ( ( defined( 'SNN_CODE_DISABLE' ) && SNN_CODE_DISABLE ) || ( defined( 'SNN_CODE_SAFE_MODE' ) && SNN_CODE_SAFE_MODE ) ) {
        return false;
    }
    return (bool) get_option( 'snn_codes_snippets_enabled', 0 );
}

/**
 * The unpublished draft stored for a snippet post, or false.
 *
 * Shape: code, hash, status ('pending' | 'failed'), token, switch_on, time,
 * user, error (array( type, message, line, target ) once failed).
 */
function snn_snippet_get_draft( $post_id ) {
    if ( ! $post_id ) {
        return false;
    }
    $draft = get_post_meta( $post_id, SNN_SNIPPET_DRAFT_META, true );
    return ( is_array( $draft ) && isset( $draft['code'], $draft['status'] ) ) ? $draft : false;
}

/**
 * Store a draft. update_post_meta() unslashes its input, so the draft is
 * slashed first - the same trap that used to eat backslashes in saved code.
 */
function snn_snippet_save_draft( $post_id, $draft ) {
    update_post_meta( $post_id, SNN_SNIPPET_DRAFT_META, wp_slash( $draft ) );
}

/**
 * Forget a snippet's draft, and void its test run if one is in progress.
 */
function snn_snippet_delete_draft( $post_id ) {
    $draft = snn_snippet_get_draft( $post_id );
    if ( ! $draft ) {
        return;
    }
    if ( ! empty( $draft['token'] ) ) {
        delete_transient( SNN_SNIPPET_TEST_TRANSIENT . $draft['token'] );
    }
    delete_post_meta( $post_id, SNN_SNIPPET_DRAFT_META );
}

/**
 * Post ID for a snippet, creating an empty private post on first use so a
 * draft has somewhere to live.
 *
 * @return int|WP_Error
 */
function snn_snippet_ensure_post( $def ) {
    $post_id = snn_get_code_snippet_id( $def['slug'] );
    if ( $post_id ) {
        return (int) $post_id;
    }
    return wp_insert_post( array(
        'post_title'   => $def['title'],
        'post_content' => '',
        'post_status'  => 'private',
        'post_type'    => 'snn_code_snippet',
        'post_name'    => $def['slug'],
    ), true );
}

/**
 * Make code live: it becomes the post content (creating a revision), any draft
 * is dropped, any block is lifted, and the snippet is switched on if asked.
 * The runtime crash guard re-arms itself against the new code.
 *
 * Modern snippets pass their settings, which go live together with the code.
 *
 * @return true|WP_Error
 */
function snn_snippet_publish( $def, $post_id, $code, $switch_on, $settings = null ) {
    $result = wp_update_post( array(
        'ID'           => $post_id,
        'post_title'   => wp_slash( $def['title'] ),
        // wp_update_post() expects slashed input and unslashes internally.
        // Passing unslashed code ate one level of backslashes on every save,
        // silently turning \WP_Query into WP_Query and '/\d+/' into '/d+/'.
        'post_content' => wp_slash( $code ),
    ), true );
    if ( is_wp_error( $result ) ) {
        return $result;
    }

    if ( is_array( $settings ) ) {
        snn_snippet_save_settings( $post_id, $settings );
    }
    snn_snippet_delete_draft( $post_id );
    snn_snippet_clear_error( $def['slug'] );
    if ( $switch_on ) {
        snn_snippet_set_enabled( $def['slug'], true ); // Rebuilds the cache for modern snippets.
    } elseif ( snn_snippet_modern_id( $def['slug'] ) ) {
        snn_snippets_rebuild_cache();
    }
    return true;
}

/**
 * Keep code as a draft and open a test run for it. The admin page then loads
 * the test pages from the browser - after this request has finished, so it
 * works on servers that handle one request at a time - and asks the server
 * to publish once every page has reported back.
 */
function snn_snippet_start_test( $slug, $post_id, $code, $switch_on, $settings = null, $test_url = '' ) {
    snn_snippet_delete_draft( $post_id ); // Voids an earlier run's token.

    $targets = snn_snippet_test_targets_for( $slug, $settings );
    $token   = wp_generate_password( 32, false, false );
    set_transient( SNN_SNIPPET_TEST_TRANSIENT . $token, array(
        'slug'     => $slug,
        'code'     => $code,
        'settings' => $settings,
        'hash'     => md5( $code ),
        'user'     => get_current_user_id(),
        'targets'  => $targets,
        'urls'     => snn_snippet_test_urls( $targets, $settings, $test_url ),
        'results'  => array(),
        'expires'  => time() + SNN_SNIPPET_TEST_TTL,
    ), SNN_SNIPPET_TEST_TTL );

    snn_snippet_save_draft( $post_id, array(
        'code'      => $code,
        'settings'  => $settings,
        'hash'      => md5( $code ),
        'status'    => 'pending',
        'token'     => $token,
        'switch_on' => (bool) $switch_on,
        'time'      => time(),
        'user'      => get_current_user_id(),
        'error'     => null,
    ) );
}

/**
 * Keep code as a draft that failed its checks, and log why. Nothing about the
 * live snippet changes.
 *
 * @param array $error array( type, message, line, target ).
 */
function snn_snippet_fail_draft( $slug, $post_id, $code, $switch_on, $error, $settings = null ) {
    snn_snippet_delete_draft( $post_id );
    snn_snippet_save_draft( $post_id, array(
        'code'      => $code,
        'settings'  => $settings,
        'hash'      => md5( $code ),
        'status'    => 'failed',
        'token'     => '',
        'switch_on' => (bool) $switch_on,
        'time'      => time(),
        'user'      => get_current_user_id(),
        'error'     => $error,
    ) );

    $line = (int) $error['line'];
    snn_log_error_event(
        'Not published: ' . $error['type'],
        $error['message'] . ( ! empty( $error['target'] ) ? ' [' . $error['target'] . ']' : '' ),
        $slug,
        'Check before publishing',
        $line,
        $line ? snn_get_code_context( $code, $line ) : '',
        $line ? snn_get_function_context( $code, $line ) : ''
    );
}

/**
 * Save one snippet from the admin page.
 *
 * Code that cannot execute - empty code, a snippet that is switched off, or
 * snippet execution being off entirely - is published as soon as it passes the
 * static checks. Code that would execute is kept as a draft and tested on real
 * page loads first; the live version keeps running until the test passes.
 * Switching a snippet on counts as a change to test; switching one off is
 * always safe and happens immediately.
 *
 * Modern snippets also pass their settings: a change to type, location,
 * priority or conditions is a change to test, exactly like a code change.
 * HTML, CSS and JavaScript cannot crash anything and go live straight away.
 *
 * @param array      $def        Snippet definition (slug, title).
 * @param string     $code       Submitted code, unslashed.
 * @param bool|null  $desired_on The "Run this snippet" checkbox, or null if not submitted.
 * @param array|null $settings   Modern snippets: normalized settings. Null for legacy.
 * @param string     $test_url   Modern snippets: the page to test on, or ''.
 */
function snn_snippet_process_save( $def, $code, $desired_on, $settings = null, $test_url = '' ) {
    $slug  = $def['slug'];
    $title = $def['title'];

    $post_id = snn_snippet_ensure_post( $def );
    if ( is_wp_error( $post_id ) ) {
        add_settings_error( 'snn-custom-codes', 'save_failed_' . $slug, sprintf(
            /* translators: 1: snippet title, 2: error message */
            __( 'Could not save "%1$s": %2$s', 'snn' ),
            esc_html( $title ),
            esc_html( $post_id->get_error_message() )
        ), 'error' );
        return;
    }

    $is_on     = snn_snippet_is_enabled( $slug );
    $switch_on = false;
    if ( false === $desired_on && $is_on ) {
        snn_snippet_set_enabled( $slug, false );
        $is_on = false;
    } elseif ( true === $desired_on && ! $is_on ) {
        $switch_on = true;
    }

    $is_modern = is_array( $settings );
    $runs_php  = ! $is_modern || snn_snippet_type_runs_php( $settings['type'] );
    $live      = snn_get_code_snippet_content( $slug );
    $changed   = ( $code !== $live ) || ( $is_modern && $settings != snn_snippet_get_settings( $post_id ) );
    $runs      = ( $is_on || $switch_on ) && snn_snippets_execution_possible() && $runs_php;
    $blocked   = (bool) snn_snippet_get_error( $slug );

    // Nothing new to publish, nothing to switch on, no block to retry: drop any
    // stale draft (the editor was reverted to the live code) and stop here.
    if ( ! $changed && ! $switch_on && ! ( $runs && $blocked ) ) {
        snn_snippet_delete_draft( $post_id );
        return;
    }

    $empty = ( '' === trim( $code ) );
    $check = ( $empty || ! $runs_php ) ? true : snn_snippet_static_check( $is_modern ? snn_snippet_exec_code( $settings['type'], $code ) : $code, $slug );
    if ( true !== $check ) {
        snn_snippet_fail_draft( $slug, $post_id, $code, $switch_on, $check, $settings );
        add_settings_error( 'snn-custom-codes', 'not_published_' . $slug, sprintf(
            /* translators: 1: snippet title, 2: error message, 3: line number */
            __( '"%1$s" was NOT published: %2$s (line %3$d). Your edit is kept as a draft and the live version keeps running. Fix the code and save again.', 'snn' ),
            esc_html( $title ),
            esc_html( $check['message'] ),
            absint( $check['line'] )
        ), 'error' );
        return;
    }

    if ( $empty || ! $runs ) {
        $published = snn_snippet_publish( $def, $post_id, $code, $switch_on, $settings );
        if ( is_wp_error( $published ) ) {
            add_settings_error( 'snn-custom-codes', 'save_failed_' . $slug, sprintf(
                /* translators: 1: snippet title, 2: error message */
                __( 'Could not save "%1$s": %2$s', 'snn' ),
                esc_html( $title ),
                esc_html( $published->get_error_message() )
            ), 'error' );
        } elseif ( $empty ) {
            /* translators: %s: snippet title */
            add_settings_error( 'snn-custom-codes', 'saved_' . $slug, sprintf( __( '"%s" saved.', 'snn' ), esc_html( $title ) ), 'updated' );
        } elseif ( ! $runs_php && ( $is_on || $switch_on ) ) {
            /* translators: %s: snippet title */
            add_settings_error( 'snn-custom-codes', 'saved_' . $slug, sprintf( __( '"%s" saved and live. HTML, CSS and JavaScript are printed as-is and cannot crash the site, so they are not test-loaded.', 'snn' ), esc_html( $title ) ), 'updated' );
        } elseif ( ! $is_on && ! $switch_on ) {
            /* translators: %s: snippet title */
            add_settings_error( 'snn-custom-codes', 'saved_' . $slug, sprintf( __( '"%s" saved. It is switched off, so it was not test-loaded; switching it on will test it first.', 'snn' ), esc_html( $title ) ), 'updated' );
        } else {
            /* translators: %s: snippet title */
            add_settings_error( 'snn-custom-codes', 'saved_' . $slug, sprintf( __( '"%s" saved. Snippet execution is off (globally or by a constant), so it was not test-loaded.', 'snn' ), esc_html( $title ) ), 'updated' );
        }
        return;
    }

    snn_snippet_start_test( $slug, $post_id, $code, $switch_on, $settings, $test_url );
    add_settings_error( 'snn-custom-codes', 'testing_' . $slug, sprintf(
        /* translators: %s: snippet title */
        __( '"%s" was saved as a draft and is being tested on your site. It goes live only if the test passes; until then the current version keeps running.', 'snn' ),
        esc_html( $title )
    ), 'info' );
}

/**
 * Judge a test run from the results its page loads recorded.
 *
 * Anything missing counts against the draft: a page that never reported back
 * may have been killed outright, and that is exactly what must not go live.
 *
 * @return array{problems: array, warnings: array}
 */
function snn_snippet_evaluate_test( $test ) {
    $defs     = snn_snippet_test_target_defs();
    $slug     = $test['slug'];
    $problems = array();
    $warnings = array();
    $ran      = false;

    foreach ( $test['targets'] as $target ) {
        $label = isset( $defs[ $target ] ) ? $defs[ $target ]['label'] : $target;

        if ( empty( $test['results'][ $target ] ) ) {
            $problems[] = array(
                'type'    => __( 'No result', 'snn' ),
                'message' => __( 'This test page never reported back. It may have crashed hard, timed out, redirected before WordPress loaded, or been served from a page cache.', 'snn' ),
                'line'    => 0,
                'target'  => $label,
            );
            continue;
        }

        $result = $test['results'][ $target ];
        if ( ! empty( $result['ran'] ) ) {
            $ran = true;
        }

        // Logged-in targets must really have loaded as the person who saved.
        if ( 'home_out' !== $target && (int) $result['user'] !== (int) $test['user'] ) {
            $problems[] = array(
                'type'    => __( 'Not logged in', 'snn' ),
                'message' => __( 'This test page did not load as your logged-in account, so its result cannot be trusted.', 'snn' ),
                'line'    => 0,
                'target'  => $label,
            );
            continue;
        }

        if ( ! empty( $result['fatal'] ) ) {
            $fatal = $result['fatal'];
            $line  = 0;
            if ( $fatal['from_snippet'] && '' !== $fatal['slug'] && $fatal['slug'] !== $slug ) {
                /* translators: 1: other snippet title, 2: error message */
                $message = sprintf( __( 'Another snippet ("%1$s") crashed during the test, so this one could not be verified: %2$s', 'snn' ), snn_snippet_title( $fatal['slug'] ), $fatal['message'] );
            } elseif ( $fatal['from_snippet'] ) {
                $message = $fatal['message'];
                $line    = (int) $fatal['line'];
            } else {
                /* translators: 1: error message, 2: file and line */
                $message = sprintf( __( '%1$s (in %2$s)', 'snn' ), $fatal['message'], $fatal['file'] );
            }
            $problems[] = array(
                'type'    => $fatal['type'],
                'message' => $message,
                'line'    => $line,
                'target'  => $label,
            );
        }

        foreach ( (array) $result['log'] as $entry ) {
            if ( ! isset( $entry['slug'] ) || $entry['slug'] !== $slug ) {
                continue; // Other snippets' problems are not this draft's fault.
            }
            if ( 'error' === $entry['kind'] ) {
                $problems[] = array(
                    'type'    => $entry['type'],
                    'message' => $entry['message'],
                    'line'    => (int) $entry['line'],
                    'target'  => $label,
                );
            } else {
                $warnings[ $entry['type'] . '|' . $entry['message'] . '|' . $entry['line'] ] = sprintf(
                    /* translators: 1: warning type, 2: message, 3: line, 4: test page */
                    __( '%1$s: %2$s (line %3$d, %4$s)', 'snn' ),
                    $entry['type'],
                    $entry['message'],
                    (int) $entry['line'],
                    $label
                );
            }
        }
    }

    if ( ! $problems && ! $ran ) {
        $problems[] = array(
            'type'    => __( 'Did not run', 'snn' ),
            'message' => __( 'The snippet never ran on any test page, so it could not be verified. Check that snippet execution is on and that the test pages are not redirecting.', 'snn' ),
            'line'    => 0,
            'target'  => '',
        );
    }

    return array(
        'problems' => $problems,
        'warnings' => array_values( $warnings ),
    );
}

/**
 * Queue a notice for the current user's next view of the snippets page. Test
 * runs finish over AJAX, then the page reloads to show the outcome.
 *
 * @param string $type    settings_errors() type.
 * @param string $message Already-escaped HTML.
 */
function snn_snippet_flash( $type, $message ) {
    $key   = 'snn_snippet_flash_' . get_current_user_id();
    $queue = get_transient( $key );
    $queue = is_array( $queue ) ? $queue : array();
    $queue[] = array( $type, $message );
    set_transient( $key, $queue, 5 * MINUTE_IN_SECONDS );
}

/**
 * Move queued notices into this page view's settings errors.
 */
function snn_snippet_consume_flash() {
    $key   = 'snn_snippet_flash_' . get_current_user_id();
    $queue = get_transient( $key );
    if ( ! is_array( $queue ) ) {
        return;
    }
    delete_transient( $key );
    foreach ( $queue as $i => $item ) {
        add_settings_error( 'snn-custom-codes', 'snn_flash_' . $i, $item[1], $item[0] );
    }
}

/**
 * Live code and the discard button, shown under every draft notice.
 */
function snn_snippet_render_draft_footer( $key, $live ) {
    ?>
    <details>
        <summary><?php esc_html_e( 'Show the live version', 'snn' ); ?></summary>
        <pre><?php echo '' === trim( $live ) ? esc_html__( '(empty)', 'snn' ) : esc_html( $live ); ?></pre>
    </details>
    <p>
        <button type="submit" name="snn_discard_draft_button" value="<?php echo esc_attr( $key ); ?>" class="button"
                onclick="return confirm('<?php echo esc_js( __( 'Discard this draft? The editor goes back to the live version.', 'snn' ) ); ?>');">
            <?php esc_html_e( 'Discard draft', 'snn' ); ?>
        </button>
    </p>
    <?php
}

/**
 * The notice above the editor while a snippet has an unpublished draft: the
 * test in progress (with the script that drives it), a failed check, or a test
 * that never finished.
 */
/**
 * The pages one pending test run still has to load, for the browser runner,
 * or false when the draft has no live test run.
 */
function snn_snippet_runner_config( $slug, $draft ) {
    if ( ! $draft || 'pending' !== $draft['status'] || empty( $draft['token'] ) ) {
        return false;
    }
    $test = get_transient( SNN_SNIPPET_TEST_TRANSIENT . $draft['token'] );
    if ( ! is_array( $test ) ) {
        return false;
    }
    $defs    = snn_snippet_test_target_defs();
    $targets = array();
    foreach ( $test['targets'] as $target ) {
        $url       = isset( $test['urls'][ $target ] ) ? $test['urls'][ $target ] : $defs[ $target ]['url'];
        $targets[] = array(
            'label'   => $defs[ $target ]['label'],
            'url'     => add_query_arg( array(
                'snn_snippet_test'   => $draft['token'],
                'snn_snippet_target' => $target,
            ), $url ),
            'cookies' => $defs[ $target ]['cookies'],
        );
    }
    return array(
        'token'   => $draft['token'],
        'slug'    => $slug,
        'title'   => snn_snippet_title( $slug ),
        'targets' => $targets,
    );
}

/**
 * Attributes that make an element the browser test runner: the admin script
 * loads each run's pages one at a time, asks the server to judge the run, and
 * after the last run goes to $return_url, where the outcome is shown.
 *
 * @param array  $runs       Results of snn_snippet_runner_config().
 * @param string $return_url Where to go when every run is finished.
 */
function snn_snippet_runner_attrs( $runs, $return_url ) {
    $config = array(
        'runs'      => array_values( $runs ),
        'nonce'     => wp_create_nonce( 'snn_snippet_test_finish' ),
        'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
        'returnUrl' => $return_url,
        'timeout'   => 60000,
        'i18n'      => array(
            /* translators: 1: snippet title, 2: test page, e.g. "Front end (logged out)" */
            'testing'  => __( '"%1$s": loading %2$s', 'snn' ),
            /* translators: %s: snippet title */
            'checking' => __( '"%s": checking the results…', 'snn' ),
            'failed'   => __( 'Could not reach the site to finish the test. Reload this page to try again.', 'snn' ),
        ),
    );
    return 'data-snn-test-runner="' . esc_attr( wp_json_encode( $config ) ) . '"';
}

function snn_snippet_render_draft_notice( $key, $def, $draft, $return_url = '' ) {
    $live   = snn_get_code_snippet_content( $def['slug'] );
    $runner = snn_snippet_runner_config( $def['slug'], $draft );

    if ( $runner ) {
        $return_url = $return_url ? $return_url : snn_snippet_edit_url( $def['slug'] );
        ?>
        <div class="notice notice-info inline snn-draft-notice" <?php echo snn_snippet_runner_attrs( array( $runner ), $return_url ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the helper. ?>>
            <p><strong><?php esc_html_e( 'Testing your changes before they go live…', 'snn' ); ?></strong><span class="spinner is-active"></span><span class="snn-test-status"></span></p>
            <p><?php esc_html_e( 'Your site is being loaded in the background with this draft. The current version keeps running until the test passes. Keep this page open; it reloads with the result.', 'snn' ); ?></p>
            <noscript><p><?php esc_html_e( 'Testing needs JavaScript. Until it runs, these changes stay unpublished.', 'snn' ); ?></p></noscript>
            <?php snn_snippet_render_draft_footer( $key, $live ); ?>
        </div>
        <?php
        return;
    }

    $error = ( 'failed' === $draft['status'] && ! empty( $draft['error'] ) && is_array( $draft['error'] ) ) ? $draft['error'] : null;
    ?>
    <div class="notice <?php echo $error ? 'notice-error' : 'notice-warning'; ?> inline snn-draft-notice">
        <?php if ( $error ) : ?>
            <p><strong><?php esc_html_e( 'These changes are NOT live: they failed the check before publishing.', 'snn' ); ?></strong> <?php esc_html_e( 'The live version is still running unchanged.', 'snn' ); ?></p>
            <p>
                <code><?php echo esc_html( $error['type'] ); ?></code>
                <?php echo esc_html( $error['message'] ); ?>
                <?php if ( ! empty( $error['line'] ) ) : ?>
                    <?php printf( esc_html__( '(line %d)', 'snn' ), absint( $error['line'] ) ); ?>
                <?php endif; ?>
                <?php if ( ! empty( $error['target'] ) ) : ?>
                    <em>&mdash; <?php echo esc_html( $error['target'] ); ?></em>
                <?php endif; ?>
            </p>
            <?php if ( ! empty( $error['line'] ) ) : ?>
                <pre><?php echo esc_html( snn_get_code_context( $draft['code'], (int) $error['line'] ) ); ?></pre>
            <?php endif; ?>
        <?php else : ?>
            <p><strong><?php esc_html_e( 'These changes are NOT live: their test did not finish.', 'snn' ); ?></strong> <?php esc_html_e( 'The page was closed or the test expired. Save again to test them; the live version keeps running until then.', 'snn' ); ?></p>
        <?php endif; ?>
        <p class="description">
            <?php
            /* translators: %s: human readable time difference */
            printf( esc_html__( 'Draft saved %s ago.', 'snn' ), esc_html( human_time_diff( (int) $draft['time'], time() ) ) );
            ?>
        </p>
        <?php snn_snippet_render_draft_footer( $key, $live ); ?>
    </div>
    <?php
}

/**
 * Display the admin page for managing custom code snippets.
 */
function snn_custom_codes_snippets_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'snn' ) );
    }

    switch ( snn_snippets_current_view() ) {
        case 'legacy':
        case 'logs':
            snn_snippets_legacy_page();
            break;
        case 'edit':
        case 'new':
            snn_snippets_render_editor();
            break;
        default:
            snn_snippets_render_list();
    }
}

/**
 * Which screen of the snippets page is requested: 'list' (default), 'new',
 * 'edit' (a modern snippet), 'legacy' (one of the four fixed tabs, reached
 * through the old &tab= links) or 'logs'.
 */
function snn_snippets_current_view() {
    if ( isset( $_GET['tab'] ) ) {
        $tab = sanitize_key( wp_unslash( $_GET['tab'] ) );
        if ( 'error_logs' === $tab ) {
            return 'logs';
        }
        $slugs = snn_snippet_slugs();
        if ( isset( $slugs[ $tab ] ) ) {
            return 'legacy';
        }
    }
    $view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : '';
    if ( 'new' === $view ) {
        return 'new';
    }
    if ( 'edit' === $view && snn_snippets_requested_id() ) {
        return 'edit';
    }
    return 'list';
}

/** The modern snippet ID in the request (&snippet=), if it exists. */
function snn_snippets_requested_id() {
    $id = isset( $_GET['snippet'] ) ? absint( $_GET['snippet'] ) : 0;
    return ( $id && snn_snippet_key_exists( snn_snippet_modern_key( $id ) ) ) ? $id : 0;
}

/**
 * The legacy editor (one of the four fixed tabs) and the Error Logs tab.
 * Kept as it was, minus the global switch (now on the snippet list).
 */
function snn_snippets_legacy_page() {
    // Check for the emergency disable constant
    $is_disabled_by_constant = defined( 'SNN_CODE_DISABLE' ) && SNN_CODE_DISABLE;

    // Definitions for each snippet location
    $snippet_defs = array(
        'frontend' => array(
            'title'       => snn_snippet_title( 'snn-snippet-frontend-head' ),
            'slug'        => 'snn-snippet-frontend-head', // Used as post_name and for retrieval
            'field_id'    => 'snn_frontend_code', // HTML ID for textarea
            'description' => __( 'PHP code or HTML executed within the <code>&lt;head&gt;</code> tags on the frontend. Use for dynamic meta tags, conditional CSS/JS links, etc. You can use <code>&lt;?php ?&gt;</code> tags for PHP code.', 'snn' ),
        ),
        'footer'   => array(
            'title'       => snn_snippet_title( 'snn-snippet-footer' ),
            'slug'        => 'snn-snippet-footer',
            'field_id'    => 'snn_footer_code',
            'description' => __( 'PHP code or HTML executed before the <code>&lt;/body&gt;</code> tag on the frontend. Use for late-loading dynamic content, analytics, etc. You can use <code>&lt;?php ?&gt;</code> tags for PHP code.', 'snn' ),
        ),
        'admin'    => array(
            'title'       => snn_snippet_title( 'snn-snippet-admin-head' ),
            'slug'        => 'snn-snippet-admin-head',
            'field_id'    => 'snn_admin_code',
            'description' => __( 'PHP code or HTML executed within the <code>&lt;head&gt;</code> of WordPress admin pages. Use for conditional admin CSS/JS, admin modifications, etc. You can use <code>&lt;?php ?&gt;</code> tags for PHP code.', 'snn' ),
        ),
        'functions' => array(
            'title'       => snn_snippet_title( 'snn-snippet-functions-php' ),
            'slug'        => 'snn-snippet-functions-php',
            'field_id'    => 'snn_functions_code',
            'description' => __( 'PHP executed immediately when this code feature loads (no hook) – similar to putting code in <code>functions.php</code>. Use for hooks, filters, and functions. Avoid direct output here unless intended. Errors can break your site.', 'snn' ),
        ),
    );

    $settings_saved_message_type = 'updated'; // Default message type for settings errors

    // Handle form submissions
    if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['snn_codes_snippets_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['snn_codes_snippets_nonce'] ) ), 'snn_save_codes_snippets' ) ) {

        // Handle Clear Error Logs Action
        if ( isset( $_POST['snn_clear_error_logs_button'] ) ) {
            check_admin_referer( 'snn_clear_error_logs_action', 'snn_clear_error_logs_nonce' );
            update_option( SNN_CUSTOM_CODES_LOG_OPTION, array() ); // Clear logs
            add_settings_error('snn-custom-codes', 'logs_cleared', __('All error logs have been cleared.', 'snn'), 'updated');
            $_GET['tab'] = 'error_logs'; // Stay on the logs tab
        }
        // Handle "re-enable this snippet" after a parse error or a crash block
        elseif ( isset( $_POST['snn_clear_snippet_error_button'] ) ) {
            $unblock_key = sanitize_key( wp_unslash( $_POST['snn_clear_snippet_error_button'] ) );
            $all_slugs   = snn_snippet_slugs();
            if ( isset( $all_slugs[ $unblock_key ] ) ) {
                snn_snippet_clear_error( $all_slugs[ $unblock_key ] );
                delete_transient( SNN_FATAL_ERROR_NOTICE_TRANSIENT );
                add_settings_error(
                    'snn-custom-codes',
                    'snippet_unblocked',
                    sprintf( __( '"%s" has been re-enabled. If it is still broken it will be blocked again automatically.', 'snn' ), esc_html( snn_snippet_title( $all_slugs[ $unblock_key ] ) ) ),
                    'updated'
                );
                $_GET['tab'] = $unblock_key;
            }
        }
        // Handle "Discard draft": forget an unpublished edit, keep the live code
        elseif ( isset( $_POST['snn_discard_draft_button'] ) ) {
            $discard_key = sanitize_key( wp_unslash( $_POST['snn_discard_draft_button'] ) );
            if ( isset( $snippet_defs[ $discard_key ] ) ) {
                snn_snippet_delete_draft( snn_get_code_snippet_id( $snippet_defs[ $discard_key ]['slug'] ) );
                add_settings_error(
                    'snn-custom-codes',
                    'draft_discarded',
                    /* translators: %s: snippet title */
                    sprintf( __( '"%s": draft discarded. The editor shows the live version again.', 'snn' ), esc_html( $snippet_defs[ $discard_key ]['title'] ) ),
                    'updated'
                );
                $_GET['tab'] = $discard_key;
            }
        }
        // Handle Clear Revisions Action for a specific snippet
        elseif ( isset( $_POST['snn_clear_revisions_button'] ) && ! empty( $_POST['snn_clear_revisions_button'] ) ) {
            $snippet_key_to_clear = isset( $_POST['snn_snippet_key_to_clear'] ) ? sanitize_key( $_POST['snn_snippet_key_to_clear'] ) : '';
            if ( $snippet_key_to_clear && isset( $snippet_defs[ $snippet_key_to_clear ] ) ) {
                check_admin_referer( 'snn_clear_revisions_' . $snippet_key_to_clear, 'snn_clear_revisions_nonce_' . $snippet_key_to_clear );
                $target_snippet_def = $snippet_defs[ $snippet_key_to_clear ];
                $target_post_id = snn_get_code_snippet_id( $target_snippet_def['slug'] );
                if ( $target_post_id && current_user_can( 'delete_post', $target_post_id ) ) {
                    $revisions_to_delete = wp_get_post_revisions( $target_post_id, array( 'fields' => 'ids', 'posts_per_page' => -1 ) );
                    if ( !empty($revisions_to_delete) ) {
                        $deleted_count = 0;
                        foreach ( $revisions_to_delete as $revision_id_to_delete ) {
                            if ( wp_delete_post_revision( $revision_id_to_delete ) ) $deleted_count++;
                        }
                        if ($deleted_count > 0) add_settings_error('snn-custom-codes', 'revisions_cleared', sprintf(__( '%d revision(s) for "%s" cleared successfully.', 'snn' ), $deleted_count, esc_html($target_snippet_def['title'])), 'updated');
                        else add_settings_error('snn-custom-codes', 'revisions_clear_failed_none_deleted', sprintf(__( 'No revisions were deleted for "%s".', 'snn' ), esc_html($target_snippet_def['title'])), 'warning');
                    } else add_settings_error('snn-custom-codes', 'no_revisions_to_clear', sprintf(__( 'No revisions found to clear for "%s".', 'snn' ), esc_html($target_snippet_def['title'])), 'info');
                } else {
                    add_settings_error('snn-custom-codes', 'clear_revisions_failed_permissions', __('Failed to clear revisions. Invalid snippet or insufficient permissions.', 'snn'), 'error');
                    $settings_saved_message_type = 'error';
                }
                $_GET['tab'] = $snippet_key_to_clear; // Stay on the current snippet tab
            }
        }
        // Handle Restore Revision Action for a specific snippet
        elseif ( isset( $_POST['snn_restore_submit_button'] ) && ! empty( $_POST['snn_restore_submit_button'] ) ) {
            $restore_action = sanitize_text_field( wp_unslash( $_POST['snn_restore_submit_button'] ) );
            $parts = explode( '_', $restore_action );
            if ( count($parts) === 3 && 'restore' === $parts[0] ) {
                $revision_id = absint( $parts[1] );
                $snippet_key_for_restore = sanitize_key( $parts[2] );

                if ( $revision_id && isset( $snippet_defs[ $snippet_key_for_restore ] ) ) {
                    $target_snippet_def = $snippet_defs[ $snippet_key_for_restore ];
                    $target_post_id = snn_get_code_snippet_id( $target_snippet_def['slug'] );
                    $revision = wp_get_post_revision( $revision_id );

                    if ( $target_post_id && $revision && $revision->post_parent == $target_post_id && current_user_can( 'edit_post', $target_post_id ) ) {
                        // Restoring is saving old code: it takes the same
                        // draft -> test -> publish path as any other edit.
                        $_GET['tab'] = $snippet_key_for_restore;
                        snn_snippet_process_save( $target_snippet_def, $revision->post_content, snn_snippet_posted_toggle( $snippet_key_for_restore ) );
                    } else {
                        add_settings_error('snn-custom-codes', 'restore_failed', __('Failed to restore revision. Invalid ID or permissions.', 'snn'), 'error');
                        $settings_saved_message_type = 'error';
                         $_GET['tab'] = $snippet_key_for_restore;
                    }
                }
            }
        }

        // Save the snippet on screen. The global switch lives on the snippet list.
        if ( isset($_POST['snn_save_all_settings_button']) ) {
            // Save the snippet on screen (only the current tab renders its
            // textarea). Syntax and name conflicts are checked here, once; an
            // edit to a snippet that runs is then kept as a draft and tested on
            // real page loads before it replaces the live code.
            foreach ( $snippet_defs as $key => $def ) {
                if ( isset( $_POST[ $def['field_id'] ] ) ) {
                    snn_snippet_process_save( $def, wp_unslash( $_POST[ $def['field_id'] ] ), snn_snippet_posted_toggle( $key ) );
                }
            }

            if ( ! get_settings_errors( 'snn-custom-codes' ) ) {
                add_settings_error( 'snn-custom-codes', 'settings_saved', __( 'Settings saved.', 'snn' ), 'updated' );
            }
        }
    } // End of POST handling

    // Get current state for display
    $enabled_globally = get_option( 'snn_codes_snippets_enabled', 0 );
    $default_tab = 'frontend';
    $current_tab_key = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : $default_tab;
    
    // Validate tab
    $valid_tabs = array_keys($snippet_defs);
    $valid_tabs[] = 'error_logs';
    if ( ! in_array( $current_tab_key, $valid_tabs ) ) {
        $current_tab_key = $default_tab;
    }


    // What each editor shows: the unpublished draft when there is one (the
    // latest work), otherwise the live code.
    $codes_for_display = array();
    $drafts            = array();
    foreach ( $snippet_defs as $key => $def ) {
        $drafts[ $key ]            = snn_snippet_get_draft( snn_get_code_snippet_id( $def['slug'] ) );
        $codes_for_display[ $key ] = $drafts[ $key ] ? $drafts[ $key ]['code'] : snn_get_code_snippet_content( $def['slug'] );
    }

    snn_snippet_consume_flash(); // Outcome of a test run that finished over AJAX.
    settings_errors('snn-custom-codes'); // Display any admin notices queued
    ?>
    <div class="wrap snn-snippets-wrap">
        <?php snn_snippets_render_header( 'error_logs' === $current_tab_key ? 'logs' : 'list' ); ?>

        <?php if ( $is_disabled_by_constant ) : ?>
            <div class="notice notice-error">
                <p>
                    <strong><?php esc_html_e( 'Execution Disabled by Constant:', 'snn' ); ?></strong>
                    <?php
                    printf(
                        // translators: %s: The name of the constant, e.g., SNN_CODE_DISABLE
                        esc_html__( 'All snippet execution is currently disabled by the %s constant. To re-enable execution, you must remove or set this constant to false.', 'snn' ),
                        '<code>SNN_CODE_DISABLE</code>'
                    );
                    ?>
                </p>
            </div>
        <?php endif; ?>

        <div class="notice notice-warning inline snn-php-execution-warning">
            <p><strong>Warning:</strong> <?php esc_html_e( 'ATTENTION PLEASE! These settings are not for normal users! If you don’t have at least some basic knowledge of HTML, CSS, and FTP login, DO NOT USE IT!', 'snn' ); ?></p>
            <p><strong>INFO:</strong> <?php esc_html_e( 'If needed use define( ‘SNN_CODE_DISABLE’, true ); in functions.php file to disable the code snippets feature temporarly. ', 'snn' ); ?></p>
        </div>

        <form method="post" action="admin.php?page=snn-custom-codes-snippets&tab=<?php echo esc_attr($current_tab_key); ?>">
            <?php wp_nonce_field( 'snn_save_codes_snippets', 'snn_codes_snippets_nonce' ); ?>

            <?php if ( ! $enabled_globally ) : ?>
                <div class="notice notice-warning inline"><p>
                    <?php
                    printf(
                        /* translators: %s: link to the snippet list */
                        wp_kses_post( __( 'Snippet execution is switched off, so no snippet runs. Switch it on from the <a href="%s">snippet list</a>.', 'snn' ) ),
                        esc_url( admin_url( 'admin.php?page=snn-custom-codes-snippets' ) )
                    );
                    ?>
                </p></div>
            <?php endif; ?>

            <?php if ( $current_tab_key === 'error_logs' ) : // Display Error Logs Tab Content ?>
            <div id="snn-tab-content-error-logs" class="snn-tab-content">
                <h3><?php esc_html_e( 'Snippet Execution Error Logs', 'snn' ); ?></h3>
                <p><?php printf( esc_html__( 'This log shows the last %d errors recorded from snippet executions. A fatal error blocks only the snippet that caused it; execution is switched off for every snippet only when a crash cannot be traced to one snippet.', 'snn' ), SNN_CUSTOM_CODES_MAX_LOG_ENTRIES ); ?></p>
                <?php
                $error_logs = get_option( SNN_CUSTOM_CODES_LOG_OPTION, array() );
                if ( ! is_array( $error_logs ) ) $error_logs = array();

                if ( ! empty( $error_logs ) ) : ?>
                    <table class="snn-error-logs-table widefat striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Timestamp', 'snn' ); ?></th>
                                <th><?php esc_html_e( 'Type', 'snn' ); ?></th>
                                <th><?php esc_html_e( 'Tab/Snippet', 'snn' ); ?></th>
                                <th><?php esc_html_e( 'Line', 'snn' ); ?></th>
                                <th><?php esc_html_e( 'Function/Context', 'snn' ); ?></th>
                                <th class="snn-log-message"><?php esc_html_e( 'Error Message', 'snn' ); ?></th>
                                <th><?php esc_html_e( 'Code Context', 'snn' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $error_logs as $log_entry ) : 
                                $snippet_title = isset( $log_entry['snippet_title'] ) ? $log_entry['snippet_title'] : ( isset( $log_entry['snippet_slug'] ) ? $log_entry['snippet_slug'] : 'Unknown' );
                                $function_context = isset( $log_entry['function_context'] ) ? $log_entry['function_context'] : '';
                                $line_number = isset( $log_entry['line'] ) ? absint( $log_entry['line'] ) : 0;
                            ?>
                            <tr>
                                <td style="white-space: nowrap;"><?php echo esc_html( date_i18n( get_option('date_format') . ' ' . get_option('time_format'), strtotime( $log_entry['timestamp'] ) ) ); ?></td>
                                <td><strong><?php echo esc_html( $log_entry['type'] ); ?></strong></td>
                                <td><strong><?php echo esc_html( $snippet_title ); ?></strong></td>
                                <td style="text-align: center;"><?php echo $line_number > 0 ? '<strong>' . esc_html( $line_number ) . '</strong>' : '<em>N/A</em>'; ?></td>
                                <td><?php echo $function_context ? '<code>' . esc_html( $function_context ) . '</code>' : '<em>' . esc_html__('Top-level', 'snn') . '</em>'; ?></td>
                                <td class="snn-log-message"><pre style="max-width: 400px; overflow-x: auto;"><?php echo esc_html( $log_entry['message'] ); ?></pre></td>
                                <td class="snn-log-message"><?php 
                                    if ( ! empty( $log_entry['code_context'] ) ) {
                                        echo '<details><summary>' . esc_html__('View Code', 'snn') . '</summary><pre style="background: #f9f9f9; padding: 10px; border: 1px solid #ddd; font-size: 11px; line-height: 1.4; overflow-x: auto;">' . esc_html( $log_entry['code_context'] ) . '</pre></details>';
                                    } else {
                                        echo '<em>' . esc_html__('N/A', 'snn') . '</em>';
                                    }
                                ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p>
                        <?php wp_nonce_field( 'snn_clear_error_logs_action', 'snn_clear_error_logs_nonce' ); ?>
                        <button type="submit" name="snn_clear_error_logs_button" class="button button-danger snn-clear-error-logs-button">
                            <?php esc_html_e( 'Clear All Error Logs', 'snn' ); ?>
                        </button>
                    </p>
                <?php else : ?>
                    <p><?php esc_html_e( 'No errors logged yet.', 'snn' ); ?></p>
                <?php endif; ?>
            </div>

            <?php elseif ( isset( $snippet_defs[ $current_tab_key ] ) ) : // Display Snippet Editor Tab Content
                $active_snippet_def = $snippet_defs[ $current_tab_key ];
                $current_code_value = isset($codes_for_display[ $current_tab_key ]) ? $codes_for_display[ $current_tab_key ] : '';

                // Clearing revisions submits the form without saving: keep what was typed.
                if ( isset( $_POST['snn_clear_revisions_button'], $_POST[ $active_snippet_def['field_id'] ] ) ) {
                    $current_code_value = wp_unslash( $_POST[ $active_snippet_def['field_id'] ] );
                }

                $active_snippet_post_id = snn_get_code_snippet_id( $active_snippet_def['slug'] );
                $revisions = array();
                if ( $active_snippet_post_id && wp_revisions_enabled( get_post( $active_snippet_post_id ) ) ) {
                    $revisions = wp_get_post_revisions( $active_snippet_post_id, array( 'posts_per_page' => 20, 'orderby' => 'post_date', 'order' => 'DESC' ) );
                }
                ?>
                <div class="snn-editor-revision-wrapper">
                    <div class="snn-editor-area">
                        <div id="snn-tab-content-<?php echo esc_attr( $current_tab_key ); ?>" class="snn-tab-content">
                            <p class="snn-breadcrumb"><a href="<?php echo esc_url( admin_url( 'admin.php?page=snn-custom-codes-snippets' ) ); ?>">&larr; <?php esc_html_e( 'All snippets', 'snn' ); ?></a></p>
                            <h3><?php echo esc_html( $active_snippet_def['title'] ); ?> <span class="snn-tag snn-tag-legacy"><?php esc_html_e( 'Legacy', 'snn' ); ?></span></h3>
                            <p class="snn-snippet-description"><?php echo wp_kses_post( $active_snippet_def['description'] ); ?></p>
                             <?php if ( $active_snippet_def['slug'] === 'snn-snippet-functions-php' ): ?>
                                <div class="notice notice-warning inline snn-php-execution-warning">
                                    <p><strong><?php esc_html_e('Warning:', 'snn'); ?></strong> <?php esc_html_e('Code in this section runs like functions.php. Errors here can easily break your site. Test thoroughly!', 'snn'); ?></p>
                                    <p><?php echo wp_kses_post( __( '<strong>Timing:</strong> this legacy snippet runs inside <code>init</code> at priority 10. An <code>add_action( \'init\', ... )</code> here at priority 10 or lower never fires, because that moment has already passed. Use priority 11 or higher, or copy it to a modern snippet, which runs earlier.', 'snn' ) ); ?></p>
                                </div>
                            <?php endif; ?>
                            <p class="snn-legacy-copy">
                                <button type="submit" name="snn_copy_legacy_button" value="<?php echo esc_attr( $current_tab_key ); ?>" class="button"
                                        onclick="return confirm('<?php echo esc_js( __( 'Create a modern snippet with this code? It starts switched off; switch it on and this legacy snippet off when you are ready.', 'snn' ) ); ?>');">
                                    <?php esc_html_e( 'Copy to modern snippet', 'snn' ); ?>
                                </button>
                                <span class="description"><?php esc_html_e( 'Copies the live code (not an unsaved edit) into a new modern snippet.', 'snn' ); ?></span>
                            </p>

                            <?php $snippet_error = snn_snippet_get_error( $active_snippet_def['slug'] ); ?>
                            <?php if ( $snippet_error ) : ?>
                                <div class="notice notice-error inline snn-php-execution-warning">
                                    <p>
                                        <strong><?php esc_html_e( 'This snippet is blocked and is not running.', 'snn' ); ?></strong><br>
                                        <code><?php echo esc_html( $snippet_error['type'] ); ?></code>
                                        <?php echo esc_html( $snippet_error['message'] ); ?>
                                        <?php if ( ! empty( $snippet_error['line'] ) ) : ?>
                                            <?php printf( esc_html__( '(line %d)', 'snn' ), absint( $snippet_error['line'] ) ); ?>
                                        <?php endif; ?>
                                    </p>
                                    <p><?php esc_html_e( 'Fix the code and save to re-enable it automatically, or force it back on now:', 'snn' ); ?></p>
                                    <p>
                                        <button type="submit" name="snn_clear_snippet_error_button" value="<?php echo esc_attr( $current_tab_key ); ?>" class="button">
                                            <?php esc_html_e( 'Re-enable this snippet', 'snn' ); ?>
                                        </button>
                                    </p>
                                </div>
                            <?php endif; ?>

                            <?php
                            $active_draft     = $drafts[ $current_tab_key ];
                            $pending_switch_on = $active_draft && ! empty( $active_draft['switch_on'] );
                            if ( $active_draft ) {
                                snn_snippet_render_draft_notice( $current_tab_key, $active_snippet_def, $active_draft );
                            }
                            ?>

                            <p>
                                <input type="hidden" name="snn_snippet_toggle_present" value="<?php echo esc_attr( $current_tab_key ); ?>">
                                <label for="snn_snippet_enabled">
                                    <input type="checkbox" id="snn_snippet_enabled" name="snn_snippet_enabled" value="1" <?php checked( true, snn_snippet_is_enabled( $active_snippet_def['slug'] ) || $pending_switch_on ); ?>>
                                    <?php esc_html_e( 'Run this snippet', 'snn' ); ?>
                                </label>
                                <?php if ( $pending_switch_on ) : ?>
                                    <span class="description"><?php esc_html_e( '(switches on once the draft passes its test)', 'snn' ); ?></span>
                                <?php endif; ?>
                            </p>
                            <textarea id="<?php echo esc_attr( $active_snippet_def['field_id'] ); ?>"
                                      name="<?php echo esc_attr( $active_snippet_def['field_id'] ); ?>"
                                      class="large-text code"
                                      rows="25"
                                      placeholder="<?php esc_attr_e( 'Enter your PHP code or HTML here...', 'snn' ); ?>"
                            ><?php echo esc_textarea( $current_code_value ); ?></textarea>
                        </div>
                    </div>

                    <div class="snn-revisions-panel" data-active-editor-id="<?php echo esc_attr( $active_snippet_def['field_id'] ); ?>">
                        <h4><?php printf( esc_html__( 'Revisions for %s', 'snn' ), esc_html( $active_snippet_def['title'] ) ); ?></h4>
                        <div class="snn-revisions-panel-inner">
                            <?php if ( ! empty( $revisions ) ) : ?>
                                <ul class="snn-revisions-list">
                                    <?php foreach ( $revisions as $revision ) :
                                        $revision_author_id   = $revision->post_author;
                                        $revision_author_info = get_userdata( $revision_author_id );
                                        $revision_author_name = $revision_author_info ? esc_html($revision_author_info->display_name) : __( 'Unknown Author', 'snn' );
                                        $comparison_link_nonce = wp_create_nonce( 'view-revision_' . $revision->ID );
                                        $comparison_link       = admin_url( 'revision.php?revision=' . $revision->ID . '&nonce=' . $comparison_link_nonce );
                                        $time_diff             = human_time_diff( strtotime( $revision->post_date_gmt ), current_time( 'timestamp', true ) );
                                        $revision_date_title   = date_i18n( get_option('date_format') . ' ' . get_option('time_format'), strtotime( $revision->post_date ) );
                                        $revision_info         = sprintf( '%s by %s (%s %s)', $revision_date_title, $revision_author_name, $time_diff, __('ago', 'snn') );
                                    ?>
                                    <li>
                                        <span class="revision-info"><?php echo esc_html( $revision_info ); ?></span>
                                        <div class="revision-actions">
                                            <button type="button" class="button button-secondary button-small snn-preview-revision"
                                                    data-revision-id="<?php echo esc_attr( $revision->ID ); ?>">
                                                <?php esc_html_e( 'Preview in Editor', 'snn' ); ?>
                                            </button>
                                            <a href="<?php echo esc_url( $comparison_link ); ?>" target="_blank"
                                               class="button button-outlined button-small snn-view-comparison-link"
                                               title="<?php esc_attr_e( 'View full comparison in new tab', 'snn' ); ?>">
                                                <span class="dashicons dashicons-search"></span> <?php esc_html_e('Compare', 'snn'); ?>
                                            </a>
                                            <button type="submit"
                                                    name="snn_restore_submit_button"
                                                    value="restore_<?php echo esc_attr( $revision->ID ) . '_' . esc_attr( $current_tab_key ); ?>"
                                                    class="button button-primary button-small snn-restore-revision-button"
                                                    style="display:none;"> <?php esc_html_e( 'Load Revision & Test', 'snn' ); ?>
                                            </button>
                                        </div>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                                <div class="snn-manage-revisions-section">
                                    <?php wp_nonce_field( 'snn_clear_revisions_' . $current_tab_key, 'snn_clear_revisions_nonce_' . $current_tab_key ); ?>
                                    <input type="hidden" name="snn_snippet_key_to_clear" value="<?php echo esc_attr($current_tab_key); ?>">
                                    <button type="submit" name="snn_clear_revisions_button" value="clear_<?php echo esc_attr($current_tab_key); ?>" class="button button-danger snn-clear-revisions-button">
                                        <?php esc_html_e( 'Clear All Revisions for this Snippet', 'snn' ); ?>
                                    </button>
                                    <p class="description"><?php esc_html_e( 'This will permanently delete all revisions for this snippet. Cannot be undone.', 'snn' ); ?></p>
                                </div>
                            <?php elseif ( $active_snippet_post_id ) : ?>
                                <p><?php esc_html_e( 'No past revisions found. Save changes to create revisions.', 'snn' ); ?></p>
                            <?php else : ?>
                                <p><?php esc_html_e( 'Save this snippet to start tracking revisions.', 'snn' ); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($current_tab_key !== 'error_logs'): ?>
                <p class="description"><?php esc_html_e( 'Changes to a snippet that runs are kept as a draft and tested on your site (admin area and front end) before they go live. The live version keeps running until the test passes.', 'snn' ); ?></p>
                <?php submit_button( __( 'Save Snippet', 'snn' ), 'primary large', 'snn_save_all_settings_button' ); ?>
            <?php endif; ?>

        </form>
    </div>
    <?php
}

/**
 * Page title, "Add New" and the Snippets / Error Logs tabs, shared by every screen.
 */
function snn_snippets_render_header( $active ) {
    ?>
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Code Snippets', 'snn' ); ?></h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=snn-custom-codes-snippets&view=new' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'snn' ); ?></a>
    <hr class="wp-header-end">
    <nav class="nav-tab-wrapper snn-snippet-nav-tab-wrapper" aria-label="<?php esc_attr_e( 'Code snippets', 'snn' ); ?>">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=snn-custom-codes-snippets' ) ); ?>" class="nav-tab <?php echo 'logs' !== $active ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Snippets', 'snn' ); ?></a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=snn-custom-codes-snippets&tab=error_logs' ) ); ?>" class="nav-tab <?php echo 'logs' === $active ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Error Logs', 'snn' ); ?></a>
    </nav>
    <?php
}

/** Notices for the kill constants, shown on the list and the editor. */
function snn_snippets_render_status_notices() {
    if ( defined( 'SNN_CODE_DISABLE' ) && SNN_CODE_DISABLE ) {
        echo '<div class="notice notice-error inline"><p><strong>' . esc_html__( 'Execution disabled by constant:', 'snn' ) . '</strong> '
            . wp_kses_post( __( 'All snippet execution is disabled by the <code>SNN_CODE_DISABLE</code> constant. Remove it, or set it to false, to run snippets again.', 'snn' ) ) . '</p></div>';
    } elseif ( defined( 'SNN_CODE_SAFE_MODE' ) && SNN_CODE_SAFE_MODE ) {
        echo '<div class="notice notice-warning inline"><p>' . wp_kses_post( __( 'Safe mode is on for everyone: the <code>SNN_CODE_SAFE_MODE</code> constant stops every snippet from running.', 'snn' ) ) . '</p></div>';
    }
}

/** Labels for conditional logic, used by the editor and the list's Conditions column. */
function snn_snippet_rule_labels() {
    return array(
        'rules'  => array(
            'logged_in' => __( 'Logged-in', 'snn' ),
            'user_role' => __( 'User role', 'snn' ),
            'area'      => __( 'Area', 'snn' ),
            'url_path'  => __( 'URL path', 'snn' ),
            'device'    => __( 'Device', 'snn' ),
            'page_type' => __( 'Page type', 'snn' ),
            'post_type' => __( 'Post type', 'snn' ),
            'post_id'   => __( 'Page or post', 'snn' ),
        ),
        'ops'    => array(
            'is'          => __( 'is', 'snn' ),
            'is_not'      => __( 'is not', 'snn' ),
            'equals'      => __( 'equals', 'snn' ),
            'contains'    => __( 'contains', 'snn' ),
            'starts_with' => __( 'starts with', 'snn' ),
        ),
        'values' => array(
            'logged_in' => array( 'true' => __( 'true', 'snn' ), 'false' => __( 'false', 'snn' ) ),
            'area'      => array( 'admin' => __( 'admin area', 'snn' ), 'front' => __( 'front end', 'snn' ) ),
            'device'    => array( 'mobile' => __( 'mobile', 'snn' ), 'desktop' => __( 'desktop', 'snn' ) ),
            'page_type' => array(
                'front_page' => __( 'Front page', 'snn' ),
                'blog'       => __( 'Blog page', 'snn' ),
                'singular'   => __( 'Single post or page', 'snn' ),
                'archive'    => __( 'Archive', 'snn' ),
                'search'     => __( 'Search results', 'snn' ),
                '404'        => __( '404 page', 'snn' ),
            ),
        ),
    );
}

/** A rule's value in words: "Product" for a post type, a title for a post. */
function snn_snippet_rule_value_label( $rule, $labels ) {
    $value = (string) $rule['value'];
    if ( isset( $labels['values'][ $rule['rule'] ][ $value ] ) ) {
        return $labels['values'][ $rule['rule'] ][ $value ];
    }
    switch ( $rule['rule'] ) {
        case 'user_role':
            $names = wp_roles()->get_names();
            return isset( $names[ $value ] ) ? translate_user_role( $names[ $value ] ) : $value;
        case 'post_type':
            $object = get_post_type_object( $value );
            return $object ? $object->labels->singular_name : $value;
        case 'post_id':
            $title = get_the_title( (int) $value );
            return '' !== $title ? $title : '#' . $value;
    }
    return $value;
}

/** Conditions in plain words for the list: "Logged-in is false and Post type is Product". */
function snn_snippet_conditions_summary( $conditions ) {
    if ( empty( $conditions['enabled'] ) || empty( $conditions['groups'] ) ) {
        return '';
    }
    $labels = snn_snippet_rule_labels();
    $groups = array();
    foreach ( $conditions['groups'] as $group ) {
        $parts = array();
        foreach ( $group as $rule ) {
            $parts[] = $labels['rules'][ $rule['rule'] ] . ' ' . $labels['ops'][ $rule['op'] ] . ' ' . snn_snippet_rule_value_label( $rule, $labels );
        }
        $groups[] = implode( ' ' . __( 'and', 'snn' ) . ' ', $parts );
    }
    $text = implode( ' — ' . __( 'or', 'snn' ) . ' — ', $groups );
    /* translators: %s: conditions in words */
    return 'hide' === $conditions['action'] ? sprintf( __( 'Hidden when: %s', 'snn' ), $text ) : $text;
}

/**
 * Switched-on PHP snippets whose current code has never run successfully
 * through a complete request. Listed before execution is switched back on.
 */
function snn_snippets_untested_active() {
    $state = snn_get_snippet_state();
    $keys  = array();
    foreach ( snn_snippet_slugs() as $slug ) {
        $code = snn_get_code_snippet_content( $slug );
        if ( snn_snippet_is_enabled( $slug ) && '' !== trim( $code ) && ( ! isset( $state['verified'][ $slug ] ) || md5( $code ) !== $state['verified'][ $slug ] ) ) {
            $keys[] = $slug;
        }
    }
    $cache = snn_snippets_cache();
    foreach ( $cache['active'] as $snippet ) {
        if ( ! snn_snippet_type_runs_php( $snippet['type'] ) ) {
            continue;
        }
        $key  = snn_snippet_modern_key( $snippet['id'] );
        $code = snn_snippet_exec_code( $snippet['type'], $snippet['code'] );
        if ( ! isset( $state['verified'][ $key ] ) || md5( $code ) !== $state['verified'][ $key ] ) {
            $keys[] = $key;
        }
    }
    return $keys;
}

/** The global switch banner at the top of the list. */
function snn_snippets_render_global_switch() {
    $on       = (bool) get_option( 'snn_codes_snippets_enabled', 0 );
    $constant = defined( 'SNN_CODE_DISABLE' ) && SNN_CODE_DISABLE;
    $crashed  = ! $on && get_transient( SNN_FATAL_ERROR_NOTICE_TRANSIENT );
    $untested = $on ? array() : snn_snippets_untested_active();
    ?>
    <form method="post" class="snn-global-switch <?php echo $on ? 'is-on' : 'is-off'; ?>">
        <?php wp_nonce_field( 'snn_modern_action', 'snn_modern_nonce' ); ?>
        <input type="hidden" name="snn_modern_action" value="global">
        <label class="snn-switch">
            <input type="checkbox" name="snn_global_on" value="1" <?php checked( $on ); ?> <?php disabled( $constant ); ?> onchange="this.form.submit()">
            <span class="snn-switch-slider" aria-hidden="true"></span>
            <span class="screen-reader-text"><?php esc_html_e( 'Snippet execution', 'snn' ); ?></span>
        </label>
        <div>
            <?php if ( $on ) : ?>
                <strong><?php esc_html_e( 'Snippet execution is on.', 'snn' ); ?></strong>
                <span class="description"><?php esc_html_e( 'Turn off to stop every snippet at once.', 'snn' ); ?></span>
            <?php else : ?>
                <strong><?php esc_html_e( 'Snippet execution is off.', 'snn' ); ?></strong>
                <span class="description"><?php esc_html_e( 'No snippet runs until you switch it on.', 'snn' ); ?></span>
                <?php if ( $crashed ) : ?>
                    <p><?php esc_html_e( 'It was switched off automatically after a crash that could not be traced to one snippet. Check the Error Logs tab before switching it back on.', 'snn' ); ?></p>
                <?php endif; ?>
                <?php if ( $untested ) : ?>
                    <p>
                        <?php esc_html_e( 'These switched-on snippets have not yet run successfully with their current code, so they start running untested when you switch execution on:', 'snn' ); ?>
                        <?php
                        $links = array();
                        foreach ( $untested as $key ) {
                            $links[] = '<a href="' . esc_url( snn_snippet_edit_url( $key ) ) . '">' . esc_html( snn_snippet_title( $key ) ) . '</a>';
                        }
                        echo implode( ', ', $links ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above.
                        ?>.
                        <?php esc_html_e( 'To be safe, switch them off first, switch execution on, then switch them back on one at a time: each is tested before it runs.', 'snn' ); ?>
                    </p>
                <?php endif; ?>
            <?php endif; ?>
            <noscript><button type="submit" class="button"><?php esc_html_e( 'Save', 'snn' ); ?></button></noscript>
        </div>
    </form>
    <?php
}

/** An on/off switch for the list. */
function snn_snippets_switch_html( $key, $on, $title ) {
    return '<label class="snn-switch"><input type="checkbox" class="snn-snippet-toggle" data-key="' . esc_attr( $key ) . '"' . checked( $on, true, false ) . '>'
        . '<span class="snn-switch-slider" aria-hidden="true"></span>'
        /* translators: %s: snippet title */
        . '<span class="screen-reader-text">' . esc_html( sprintf( __( 'Run "%s"', 'snn' ), $title ) ) . '</span></label>';
}

/** Draft and Blocked badges for a list row, with the blocking error underneath. */
function snn_snippets_row_badges( $draft, $testing, $error ) {
    $out = '';
    if ( $testing ) {
        $out .= '<span class="snn-tag snn-tag-draft">' . esc_html__( 'Draft testing', 'snn' ) . '</span>';
    } elseif ( $draft ) {
        $out .= '<span class="snn-tag snn-tag-draft">' . ( 'failed' === $draft['status'] ? esc_html__( 'Draft failed', 'snn' ) : esc_html__( 'Draft', 'snn' ) ) . '</span>';
    }
    if ( $error ) {
        $detail = wp_html_excerpt( (string) $error['message'], 90, '…' );
        if ( ! empty( $error['line'] ) ) {
            /* translators: %d: line number */
            $detail .= ' · ' . sprintf( __( 'line %d', 'snn' ), (int) $error['line'] );
        }
        $out .= '<span class="snn-tag snn-tag-blocked">' . esc_html__( 'Blocked', 'snn' ) . '</span><span class="snn-row-error">' . esc_html( $detail ) . '</span>';
    }
    return $out;
}

/**
 * Screen 1: the snippet list. Modern snippets with filters, bulk actions and
 * instant switches, then the four legacy snippets in a fixed section at the
 * bottom. Any test runs still pending (a switch-on, a bulk activate, an
 * editor save whose page was left) are driven from here too.
 */
function snn_snippets_render_list() {
    $types    = snn_snippet_code_types();
    $places   = snn_snippet_location_labels();
    $status   = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : 'all';
    $status   = in_array( $status, array( 'all', 'active', 'inactive' ), true ) ? $status : 'all';
    $f_type   = isset( $_GET['code_type'] ) ? sanitize_key( wp_unslash( $_GET['code_type'] ) ) : '';
    $f_place  = isset( $_GET['location'] ) ? sanitize_key( wp_unslash( $_GET['location'] ) ) : '';
    $base_url = admin_url( 'admin.php?page=snn-custom-codes-snippets' );
    $date_fmt = get_option( 'date_format' );

    $counts = array( 'all' => 0, 'active' => 0, 'inactive' => 0 );
    $rows   = array();
    $runs   = array();
    foreach ( array_reverse( snn_snippets_get_modern_posts() ) as $post ) {
        $key    = snn_snippet_modern_key( $post->ID );
        $on     = snn_snippet_is_enabled( $key );
        $draft  = snn_snippet_get_draft( $post->ID );
        $runner = snn_snippet_runner_config( $key, $draft );
        if ( $runner ) {
            $runs[] = $runner;
        }
        $counts['all']++;
        $counts[ $on ? 'active' : 'inactive' ]++;

        $settings = snn_snippet_get_settings( $post->ID );
        if ( ( 'active' === $status && ! $on ) || ( 'inactive' === $status && $on )
            || ( '' !== $f_type && $settings['type'] !== $f_type ) || ( '' !== $f_place && $settings['location'] !== $f_place ) ) {
            continue;
        }
        $rows[] = array(
            'post'     => $post,
            'key'      => $key,
            'on'       => $on,
            'settings' => $settings,
            'draft'    => $draft,
            'testing'  => (bool) $runner,
            'error'    => snn_snippet_get_error( $key ),
        );
    }
    foreach ( snn_snippet_slugs() as $slug ) {
        $runner = snn_snippet_runner_config( $slug, snn_snippet_get_draft( snn_get_code_snippet_id( $slug ) ) );
        if ( $runner ) {
            $runs[] = $runner;
        }
    }

    snn_snippet_consume_flash();
    $status_links = array(
        'all'      => __( 'All', 'snn' ),
        'active'   => __( 'Active', 'snn' ),
        'inactive' => __( 'Inactive', 'snn' ),
    );
    ?>
    <div class="wrap snn-snippets-wrap">
        <?php snn_snippets_render_header( 'list' ); ?>
        <?php settings_errors( 'snn-custom-codes' ); ?>
        <?php snn_snippets_render_status_notices(); ?>

        <?php if ( $runs ) : ?>
            <div class="notice notice-info inline snn-draft-notice" <?php echo snn_snippet_runner_attrs( $runs, $base_url ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the helper. ?>>
                <p>
                    <strong><?php echo esc_html( sprintf( _n( 'Testing %d snippet before it goes live…', 'Testing %d snippets before they go live…', count( $runs ), 'snn' ), count( $runs ) ) ); ?></strong>
                    <span class="spinner is-active"></span><span class="snn-test-status"></span>
                </p>
                <p><?php esc_html_e( 'Your site is being loaded in the background. Nothing changes until a test passes. Keep this page open; it reloads with the results.', 'snn' ); ?></p>
                <noscript><p><?php esc_html_e( 'Testing needs JavaScript.', 'snn' ); ?></p></noscript>
            </div>
        <?php endif; ?>

        <?php snn_snippets_render_global_switch(); ?>

        <ul class="subsubsub">
            <?php
            $links = array();
            foreach ( $status_links as $value => $label ) {
                $url     = 'all' === $value ? $base_url : add_query_arg( 'status', $value, $base_url );
                $current = $status === $value ? ' class="current" aria-current="page"' : '';
                $links[] = '<li><a href="' . esc_url( $url ) . '"' . $current . '>' . esc_html( $label ) . ' <span class="count">(' . (int) $counts[ $value ] . ')</span></a>';
            }
            echo implode( ' |</li>', $links ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above.
            ?>
        </ul>

        <form method="get" class="snn-tablenav snn-filters">
            <input type="hidden" name="page" value="snn-custom-codes-snippets">
            <?php if ( 'all' !== $status ) : ?><input type="hidden" name="status" value="<?php echo esc_attr( $status ); ?>"><?php endif; ?>
            <label class="screen-reader-text" for="snn-filter-type"><?php esc_html_e( 'Filter by type', 'snn' ); ?></label>
            <select name="code_type" id="snn-filter-type">
                <option value=""><?php esc_html_e( 'All types', 'snn' ); ?></option>
                <?php foreach ( $types as $value => $type ) : ?>
                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $f_type, $value ); ?>><?php echo esc_html( $type['label'] ); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="screen-reader-text" for="snn-filter-location"><?php esc_html_e( 'Filter by location', 'snn' ); ?></label>
            <select name="location" id="snn-filter-location">
                <option value=""><?php esc_html_e( 'All locations', 'snn' ); ?></option>
                <?php foreach ( $places as $value => $label ) : ?>
                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $f_place, $value ); ?>><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="button"><?php esc_html_e( 'Filter', 'snn' ); ?></button>
        </form>

        <form method="post" id="snn-bulk-form">
            <?php wp_nonce_field( 'snn_modern_action', 'snn_modern_nonce' ); ?>
            <input type="hidden" name="snn_modern_action" value="bulk">
            <div class="snn-tablenav">
                <label class="screen-reader-text" for="snn-bulk-action"><?php esc_html_e( 'Bulk actions', 'snn' ); ?></label>
                <select name="snn_bulk_action" id="snn-bulk-action">
                    <option value=""><?php esc_html_e( 'Bulk actions', 'snn' ); ?></option>
                    <option value="activate"><?php esc_html_e( 'Activate', 'snn' ); ?></option>
                    <option value="deactivate"><?php esc_html_e( 'Deactivate', 'snn' ); ?></option>
                    <option value="delete"><?php esc_html_e( 'Delete', 'snn' ); ?></option>
                </select>
                <button type="submit" class="button"><?php esc_html_e( 'Apply', 'snn' ); ?></button>
                <span class="snn-spacer"></span>
                <span class="displaying-num"><?php echo esc_html( sprintf( _n( '%d item', '%d items', count( $rows ), 'snn' ), count( $rows ) ) ); ?></span>
            </div>

            <table class="wp-list-table widefat striped snn-snippets-table">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column"><label class="screen-reader-text" for="snn-select-all"><?php esc_html_e( 'Select all', 'snn' ); ?></label><input type="checkbox" id="snn-select-all"></td>
                        <th scope="col" class="column-primary"><?php esc_html_e( 'Name', 'snn' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Type', 'snn' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Location', 'snn' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Conditions', 'snn' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Modified', 'snn' ); ?></th>
                        <th scope="col" class="snn-col-status"><?php esc_html_e( 'Status', 'snn' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! $rows ) : ?>
                        <tr class="no-items"><td colspan="7">
                            <?php if ( $counts['all'] ) : ?>
                                <?php esc_html_e( 'No snippets match these filters.', 'snn' ); ?>
                            <?php else : ?>
                                <?php esc_html_e( 'No snippets yet.', 'snn' ); ?>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=snn-custom-codes-snippets&view=new' ) ); ?>"><?php esc_html_e( 'Add your first snippet', 'snn' ); ?></a>
                            <?php endif; ?>
                        </td></tr>
                    <?php endif; ?>

                    <?php foreach ( $rows as $row ) :
                        $post       = $row['post'];
                        $settings   = $row['settings'];
                        $edit_url   = snn_snippet_edit_url( $row['key'] );
                        $delete_url = wp_nonce_url( add_query_arg( array( 'snn_action' => 'delete', 'snippet' => $post->ID ), $base_url ), 'snn_delete_snippet_' . $post->ID );
                        $title      = '' !== $post->post_title ? $post->post_title : __( 'Untitled snippet', 'snn' );
                        $summary    = snn_snippet_conditions_summary( $settings['conditions'] );
                        $place      = $places[ $settings['location'] ];
                        if ( 10 !== (int) $settings['priority'] ) {
                            /* translators: %d: priority */
                            $place .= ' · ' . sprintf( __( 'priority %d', 'snn' ), (int) $settings['priority'] );
                        }
                        ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <label class="screen-reader-text" for="snn-cb-<?php echo (int) $post->ID; ?>"><?php echo esc_html( sprintf( __( 'Select %s', 'snn' ), $title ) ); ?></label>
                                <input type="checkbox" name="snippet_ids[]" id="snn-cb-<?php echo (int) $post->ID; ?>" value="<?php echo (int) $post->ID; ?>">
                            </th>
                            <td class="column-primary">
                                <strong><a class="row-title" href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $title ); ?></a></strong>
                                <?php echo snn_snippets_row_badges( $row['draft'], $row['testing'], $row['error'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the helper. ?>
                                <div class="row-actions">
                                    <span class="edit"><a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'snn' ); ?></a> | </span>
                                    <span class="trash"><a class="submitdelete snn-delete-snippet" href="<?php echo esc_url( $delete_url ); ?>"><?php esc_html_e( 'Delete', 'snn' ); ?></a></span>
                                </div>
                            </td>
                            <td><span class="snn-tag snn-tag-type"><?php echo esc_html( $types[ $settings['type'] ]['label'] ); ?></span></td>
                            <td><?php echo esc_html( $place ); ?></td>
                            <td><?php echo '' !== $summary ? esc_html( $summary ) : '<span class="snn-dim">&mdash;</span>'; ?></td>
                            <td><?php echo esc_html( date_i18n( $date_fmt, strtotime( $post->post_modified ) ) ); ?></td>
                            <td><?php echo snn_snippets_switch_html( $row['key'], $row['on'], $title ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the helper. ?></td>
                        </tr>
                    <?php endforeach; ?>

                    <tr class="snn-legacy-group"><td colspan="7"><?php esc_html_e( 'Legacy snippets', 'snn' ); ?></td></tr>
                    <?php
                    $legacy_places = array(
                        'frontend'  => $places['site_head'],
                        'footer'    => $places['site_footer'],
                        'admin'     => $places['admin_head'],
                        'functions' => __( 'Every request (init, priority 10)', 'snn' ),
                    );
                    foreach ( snn_snippet_slugs() as $tab => $slug ) :
                        $post_id = snn_get_code_snippet_id( $slug );
                        $code    = snn_get_code_snippet_content( $slug );
                        $draft   = snn_snippet_get_draft( $post_id );
                        $title   = snn_snippet_title( $slug );
                        ?>
                        <tr class="snn-legacy-row">
                            <th scope="row" class="check-column"></th>
                            <td class="column-primary">
                                <strong><a class="row-title" href="<?php echo esc_url( snn_snippet_edit_url( $slug ) ); ?>"><?php echo esc_html( $title ); ?></a></strong>
                                <span class="snn-tag snn-tag-legacy"><?php esc_html_e( 'Legacy', 'snn' ); ?></span>
                                <?php echo snn_snippets_row_badges( $draft, (bool) snn_snippet_runner_config( $slug, $draft ), snn_snippet_get_error( $slug ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the helper. ?>
                                <?php if ( '' === trim( $code ) ) : ?><span class="snn-row-sub"><?php esc_html_e( '(empty)', 'snn' ); ?></span><?php endif; ?>
                            </td>
                            <td><span class="snn-tag snn-tag-type"><?php echo esc_html( $types['html_php']['label'] ); ?></span></td>
                            <td><?php echo esc_html( $legacy_places[ $tab ] ); ?></td>
                            <td><span class="snn-dim">&mdash;</span></td>
                            <td><?php echo $post_id ? esc_html( date_i18n( $date_fmt, strtotime( get_post_field( 'post_modified', $post_id ) ) ) ) : '<span class="snn-dim">&mdash;</span>'; ?></td>
                            <td><?php echo snn_snippets_switch_html( $slug, snn_snippet_is_enabled( $slug ), $title ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the helper. ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
    </div>
    <?php
}

/**
 * Initialize snippet execution hooks based on saved content and global setting.
 */
function snn_custom_codes_snippets_init_execution() {
    // Emergency override: if the constant is defined and true in wp-config.php, do nothing.
    if ( defined( 'SNN_CODE_DISABLE' ) && SNN_CODE_DISABLE ) {
        return;
    }

    // Only proceed if snippets are globally enabled
    if ( ! get_option( 'snn_codes_snippets_enabled', 0 ) ) {
        return;
    }

    // Before anything runs: did the previous request die inside a snippet?
    //
    // This is bookkeeping only - it executes no user code - so it deliberately
    // runs BEFORE the safe-mode check. The snippets admin page is permanently in
    // safe mode, and skipping recovery there made it the one place that never
    // told you which snippet had just killed your site.
    snn_snippet_recover_from_crash();

    // Safe mode: the login screen, the snippets admin page, ?snn_safe_mode=1,
    // or SNN_CODE_SAFE_MODE.
    if ( snn_snippets_in_safe_mode() ) {
        return;
    }

    // Execute "Direct PHP (functions.php style)" snippet
    $direct_code = snn_snippet_code_for_request( 'snn-snippet-functions-php' );
    if ( snn_snippet_should_run( 'snn-snippet-functions-php', $direct_code ) ) {
        echo snn_snippet_run( $direct_code, 'snn-snippet-functions-php' );
    }

    // Add hooks for other snippets only if they have content
    if ( snn_snippet_should_run( 'snn-snippet-frontend-head', snn_snippet_code_for_request( 'snn-snippet-frontend-head' ) ) ) {
        add_action( 'wp_head', 'snn_custom_codes_snippets_frontend_output', 1 );
    }
    if ( snn_snippet_should_run( 'snn-snippet-footer', snn_snippet_code_for_request( 'snn-snippet-footer' ) ) ) {
        add_action( 'wp_footer', 'snn_custom_codes_snippets_footer_output', 9999 );
    }
    if ( is_admin() && snn_snippet_should_run( 'snn-snippet-admin-head', snn_snippet_code_for_request( 'snn-snippet-admin-head' ) ) ) {
        add_action( 'admin_head', 'snn_custom_codes_snippets_admin_output', 1 );
    }
}
add_action( 'init', 'snn_custom_codes_snippets_init_execution', 10 );

/**
 * The modern snippets that run in this request, in run order: the cached
 * switched-on snippets and, on a test page load for a modern snippet, its
 * draft (code and settings) in place of the live version.
 */
function snn_snippets_for_request() {
    $cache   = snn_snippets_cache();
    $list    = $cache['active'];
    $context = snn_snippet_test_context();
    $test_id = $context ? snn_snippet_modern_id( $context['slug'] ) : 0;

    if ( $test_id && is_array( $context['settings'] ) ) {
        $kept = array();
        foreach ( $list as $snippet ) {
            if ( (int) $snippet['id'] !== $test_id ) {
                $kept[] = $snippet;
            }
        }
        $kept[] = array_merge( $context['settings'], array( 'id' => $test_id, 'code' => $context['code'] ) );
        usort( $kept, 'snn_snippet_compare_run_order' );
        $list = $kept;
    }
    return $list;
}

/**
 * Modern snippets: hook every snippet that may run in this request onto its
 * location. Runs first thing in after_setup_theme - a theme loads after
 * plugins, so this is the earliest point it has. "Run everywhere" snippets
 * therefore run before init, which also avoids the legacy functions tab's trap
 * where add_action( 'init', ... ) at priority 10 never fires.
 */
function snn_snippets_boot_modern() {
    if ( ! snn_snippets_execution_possible() ) {
        return;
    }

    // Bookkeeping only, so before the safe-mode check (see the legacy init).
    snn_snippet_recover_from_crash();

    if ( snn_snippets_in_safe_mode() ) {
        return;
    }

    $map   = snn_snippet_location_map();
    $admin = is_admin();
    foreach ( snn_snippets_for_request() as $snippet ) {
        $location = isset( $map[ $snippet['location'] ] ) ? $map[ $snippet['location'] ] : null;
        if ( ! $location || ( 'admin' === $location['area'] && ! $admin ) || ( 'front' === $location['area'] && $admin ) ) {
            continue;
        }
        if ( ! snn_snippet_should_run( snn_snippet_modern_key( $snippet['id'] ), $snippet['code'] ) ) {
            continue;
        }
        add_action( $location['hook'], snn_snippet_modern_callback( $snippet ), $snippet['priority'] );
    }
}
add_action( 'after_setup_theme', 'snn_snippets_boot_modern', -10000 );

/** Hook callback that runs one modern snippet. */
function snn_snippet_modern_callback( $snippet ) {
    return function () use ( $snippet ) {
        snn_snippet_run_modern( $snippet );
    };
}

/**
 * Run one modern snippet at its location: check its conditions, then execute
 * PHP behind the crash guard or print HTML, CSS or JavaScript.
 *
 * Output from PHP snippets at locations before the page starts is discarded:
 * printed there it would land before the doctype and break the page.
 */
function snn_snippet_run_modern( $snippet ) {
    $key        = snn_snippet_modern_key( $snippet['id'] );
    $context    = snn_snippet_test_context();
    $under_test = $context && $context['slug'] === $key;

    // The draft under test runs whatever its conditions say: running it is the
    // point of the test page load.
    if ( ! $under_test && ! snn_snippet_conditions_pass( $snippet['conditions'] ) ) {
        return;
    }

    $map   = snn_snippet_location_map();
    $stage = $map[ $snippet['location'] ]['stage'];

    if ( snn_snippet_type_runs_php( $snippet['type'] ) ) {
        $code   = snn_snippet_exec_code( $snippet['type'], $snippet['code'] );
        $state  = snn_get_snippet_state();
        $first  = ! isset( $state['verified'][ $key ] ) || md5( $code ) !== $state['verified'][ $key ];
        $output = snn_snippet_run( $code, $key );

        if ( 'output' === $stage ) {
            echo $output; // phpcs:ignore WordPress.Security.EscapeOutput -- the snippet's own output.
            return;
        }
        if ( '' !== trim( $output ) ) {
            $note = __( 'This snippet printed output at a location that runs before the page starts, so the output was discarded. Use Site head, After opening <body> or Site footer to print output.', 'snn' );
            if ( $under_test ) {
                snn_snippet_test_log( array( 'kind' => 'warning', 'type' => __( 'Output discarded', 'snn' ), 'message' => $note, 'line' => 0, 'slug' => $key ) );
            } elseif ( $first ) {
                // Noted until the code has completed a request, not on every page view.
                snn_log_error_event( __( 'Output discarded', 'snn' ), $note, $key );
            }
        }
        return;
    }

    $id = (int) $snippet['id'];
    if ( 'css' === $snippet['type'] ) {
        echo '<style id="snn-snippet-' . $id . "\">\n" . $snippet['code'] . "\n</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput
    } elseif ( 'js' === $snippet['type'] ) {
        echo '<script id="snn-snippet-' . $id . "\">\n" . $snippet['code'] . "\n</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput
    } else {
        echo $snippet['code'] . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
    }
}

/**
 * The code a snippet runs in this request: its live code or, on a test page
 * load for this snippet, the draft under test.
 */
function snn_snippet_code_for_request( $slug ) {
    $context = snn_snippet_test_context();
    if ( $context && $context['slug'] === $slug ) {
        return $context['code'];
    }
    return snn_get_code_snippet_content( $slug );
}

/**
 * Whether a snippet has content, is switched on, and is not blocked by a
 * recorded parse or fatal error.
 */
function snn_snippet_should_run( $slug, $code ) {
    if ( '' === trim( (string) $code ) ) {
        return false;
    }
    // The draft under test runs whatever its switch or block says: running it
    // is the whole point of the test page load.
    $context = snn_snippet_test_context();
    if ( $context && $context['slug'] === $slug ) {
        return true;
    }
    if ( ! snn_snippet_is_enabled( $slug ) ) {
        return false;
    }
    if ( snn_snippet_get_error( $slug ) ) {
        return false;
    }
    return true;
}

/** Output callback for frontend head snippet */
function snn_custom_codes_snippets_frontend_output() {
    $code = snn_snippet_code_for_request( 'snn-snippet-frontend-head' );
    echo snn_snippet_run( $code, 'snn-snippet-frontend-head' );
}
/** Output callback for frontend footer snippet */
function snn_custom_codes_snippets_footer_output()    {
    $code = snn_snippet_code_for_request( 'snn-snippet-footer' );
    echo snn_snippet_run( $code, 'snn-snippet-footer' );
}
/** Output callback for admin head snippet */
function snn_custom_codes_snippets_admin_output()     {
    $code = snn_snippet_code_for_request( 'snn-snippet-admin-head' );
    echo snn_snippet_run( $code, 'snn-snippet-admin-head' );
}

/**
 * Queue this request's notices for the next page view and redirect there.
 */
function snn_snippets_redirect( $url ) {
    foreach ( get_settings_errors( 'snn-custom-codes' ) as $notice ) {
        snn_snippet_flash( $notice['type'], $notice['message'] );
    }
    wp_safe_redirect( $url );
    exit;
}

/**
 * Switch a snippet (legacy or modern) on. PHP that would run is tested first,
 * exactly like a save; everything else switches on at once.
 *
 * @return string 'on', 'testing', 'draft' (it has an unpublished edit to deal
 *                with first), or an error message.
 */
function snn_snippets_switch_on( $key ) {
    $post_id = snn_get_code_snippet_id( $key );
    if ( snn_snippet_is_enabled( $key ) && ! snn_snippet_get_error( $key ) ) {
        return 'on';
    }
    if ( $post_id && snn_snippet_get_draft( $post_id ) ) {
        return 'draft';
    }

    $is_modern = (bool) snn_snippet_modern_id( $key );
    $settings  = ( $is_modern && $post_id ) ? snn_snippet_get_settings( $post_id ) : null;
    $code      = snn_get_code_snippet_content( $key );
    $runs_php  = ! $is_modern || snn_snippet_type_runs_php( $settings['type'] );

    if ( ! $post_id || '' === trim( $code ) || ! $runs_php || ! snn_snippets_execution_possible() ) {
        snn_snippet_set_enabled( $key, true );
        return 'on';
    }

    $check = snn_snippet_static_check( $is_modern ? snn_snippet_exec_code( $settings['type'], $code ) : $code, $key );
    if ( true !== $check ) {
        /* translators: 1: snippet title, 2: error message, 3: line number */
        return sprintf( __( '"%1$s" was not switched on: %2$s (line %3$d).', 'snn' ), snn_snippet_title( $key ), $check['message'], (int) $check['line'] );
    }

    $test_url = $is_modern ? (string) get_post_meta( $post_id, SNN_SNIPPET_META_TEST_URL, true ) : '';
    snn_snippet_start_test( $key, $post_id, $code, true, $settings, $test_url );
    return 'testing';
}

/**
 * Switch a snippet off. Immediate: switching off is always safe. A draft
 * still being tested no longer switches the snippet on when it passes.
 */
function snn_snippets_switch_off( $key ) {
    snn_snippet_set_enabled( $key, false );
    $post_id = snn_get_code_snippet_id( $key );
    $draft   = snn_snippet_get_draft( $post_id );
    if ( $draft && ! empty( $draft['switch_on'] ) ) {
        $draft['switch_on'] = false;
        snn_snippet_save_draft( $post_id, $draft );
    }
}

/**
 * Delete a modern snippet and everything recorded about it.
 */
function snn_snippets_delete( $id ) {
    $key = snn_snippet_modern_key( $id );
    snn_snippet_delete_draft( $id );
    wp_delete_post( $id, true );

    $map = snn_get_snippet_enabled_map();
    unset( $map[ $key ] );
    update_option( SNN_SNIPPET_ENABLED_OPTION, $map, true );

    $state = snn_get_snippet_state( true );
    unset( $state['errors'][ $key ], $state['verified'][ $key ] );
    if ( isset( $state['in_flight']['slug'] ) && $state['in_flight']['slug'] === $key ) {
        unset( $state['in_flight'] );
    }
    snn_update_snippet_state( $state );

    $notice = get_transient( SNN_FATAL_ERROR_NOTICE_TRANSIENT );
    if ( is_array( $notice ) && isset( $notice['slug'] ) && $notice['slug'] === $key ) {
        delete_transient( SNN_FATAL_ERROR_NOTICE_TRANSIENT );
    }

    snn_snippets_rebuild_cache();
}

/**
 * Create a switched-off modern snippet holding a legacy tab's live code, at
 * the location and priority it has today. Pure PHP from the functions tab
 * becomes a PHP snippet; everything else keeps HTML + PHP, which runs exactly
 * the way the legacy tab does.
 *
 * @return int|WP_Error New post ID.
 */
function snn_snippets_copy_legacy( $tab ) {
    $slugs  = snn_snippet_slugs();
    $places = array(
        'frontend'  => array( 'site_head', 1 ),
        'footer'    => array( 'site_footer', 9999 ),
        'admin'     => array( 'admin_head', 1 ),
        'functions' => array( 'everywhere', 10 ),
    );
    if ( ! isset( $slugs[ $tab ], $places[ $tab ] ) ) {
        return new WP_Error( 'snn_bad_tab', __( 'Unknown legacy snippet.', 'snn' ) );
    }

    $code = snn_get_code_snippet_content( $slugs[ $tab ] );
    $type = 'html_php';
    if ( 'functions' === $tab && preg_match( '/^\s*<\?php\b/i', $code ) && false === strpos( $code, '?' . '>' ) ) {
        $type = 'php';
        $code = snn_snippet_strip_open_tag( $code );
    }

    $id = wp_insert_post( array(
        'post_type'    => 'snn_code_snippet',
        'post_status'  => 'private',
        /* translators: %s: legacy snippet title */
        'post_title'   => wp_slash( sprintf( __( 'Copy of %s', 'snn' ), snn_snippet_title( $slugs[ $tab ] ) ) ),
        'post_content' => wp_slash( $code ),
    ), true );
    if ( is_wp_error( $id ) ) {
        return $id;
    }

    snn_snippet_save_settings( $id, snn_snippet_normalize_settings( array(
        'type'     => $type,
        'location' => $places[ $tab ][0],
        'priority' => $places[ $tab ][1],
    ) ) );
    snn_snippet_set_enabled( snn_snippet_modern_key( $id ), false ); // Explicitly off; rebuilds the cache.
    return (int) $id;
}

/**
 * Before the snippets page prints anything: handle the modern screens' forms
 * and links, then redirect (post/redirect/get), carrying notices along. The
 * legacy tabs keep handling their own form inside the page.
 */
function snn_snippets_load_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Also self-healing: a lost or stale cache never outlives a visit here.
    snn_snippets_rebuild_cache();

    $list_url = admin_url( 'admin.php?page=snn-custom-codes-snippets' );

    if ( isset( $_GET['snn_action'] ) && 'delete' === $_GET['snn_action'] ) {
        $id = snn_snippets_requested_id();
        check_admin_referer( 'snn_delete_snippet_' . $id );
        if ( $id ) {
            $title = snn_snippet_title( snn_snippet_modern_key( $id ) );
            snn_snippets_delete( $id );
            /* translators: %s: snippet title */
            add_settings_error( 'snn-custom-codes', 'snippet_deleted', sprintf( __( '"%s" deleted.', 'snn' ), esc_html( $title ) ), 'updated' );
        }
        snn_snippets_redirect( $list_url );
    }

    if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
        return;
    }

    // "Copy to modern snippet" lives in a legacy tab's form.
    if ( isset( $_POST['snn_copy_legacy_button'] ) ) {
        check_admin_referer( 'snn_save_codes_snippets', 'snn_codes_snippets_nonce' );
        $tab = sanitize_key( wp_unslash( $_POST['snn_copy_legacy_button'] ) );
        $id  = snn_snippets_copy_legacy( $tab );
        if ( is_wp_error( $id ) ) {
            add_settings_error( 'snn-custom-codes', 'copy_failed', esc_html( $id->get_error_message() ), 'error' );
            snn_snippets_redirect( $list_url );
        }
        add_settings_error( 'snn-custom-codes', 'copied', esc_html__( 'Copied to a new modern snippet. It is switched off: check its location, switch it on, then switch the legacy snippet off.', 'snn' ), 'updated' );
        snn_snippets_redirect( snn_snippet_edit_url( snn_snippet_modern_key( $id ) ) );
    }

    if ( ! isset( $_POST['snn_modern_nonce'] ) ) {
        return;
    }
    check_admin_referer( 'snn_modern_action', 'snn_modern_nonce' );
    $action = isset( $_POST['snn_modern_action'] ) ? sanitize_key( wp_unslash( $_POST['snn_modern_action'] ) ) : '';

    if ( 'global' === $action ) {
        $on = empty( $_POST['snn_global_on'] ) ? 0 : 1;
        update_option( 'snn_codes_snippets_enabled', $on );
        if ( $on ) {
            delete_transient( SNN_FATAL_ERROR_NOTICE_TRANSIENT );
            // The admin has deliberately switched execution back on. Start the
            // unattributed-fatal streak from zero so a count left over from the
            // previous breakage does not trip the kill switch prematurely.
            update_option( SNN_UNATTRIBUTED_FATAL_OPTION, 0, true );
        }
        add_settings_error( 'snn-custom-codes', 'global', $on ? esc_html__( 'Snippet execution is on.', 'snn' ) : esc_html__( 'Snippet execution is off. No snippet runs until you switch it back on.', 'snn' ), 'updated' );
        snn_snippets_redirect( $list_url );
    }

    if ( 'bulk' === $action ) {
        snn_snippets_handle_bulk();
        snn_snippets_redirect( $list_url );
    }

    if ( 'save' === $action ) {
        snn_snippets_handle_editor_post();
    }
}

/**
 * Bulk actions from the snippet list: activate (each one tested like a
 * save), deactivate, delete.
 */
function snn_snippets_handle_bulk() {
    $bulk = isset( $_POST['snn_bulk_action'] ) ? sanitize_key( wp_unslash( $_POST['snn_bulk_action'] ) ) : '';
    $ids  = isset( $_POST['snippet_ids'] ) ? array_filter( array_map( 'absint', (array) $_POST['snippet_ids'] ) ) : array();
    if ( ! $ids || ! in_array( $bulk, array( 'activate', 'deactivate', 'delete' ), true ) ) {
        add_settings_error( 'snn-custom-codes', 'bulk_none', esc_html__( 'Select snippets and an action.', 'snn' ), 'warning' );
        return;
    }

    $done    = 0;
    $testing = 0;
    foreach ( $ids as $id ) {
        $key = snn_snippet_modern_key( $id );
        if ( ! snn_snippet_key_exists( $key ) ) {
            continue;
        }
        if ( 'delete' === $bulk ) {
            snn_snippets_delete( $id );
            $done++;
        } elseif ( 'deactivate' === $bulk ) {
            snn_snippets_switch_off( $key );
            $done++;
        } else {
            $result = snn_snippets_switch_on( $key );
            if ( 'on' === $result ) {
                $done++;
            } elseif ( 'testing' === $result ) {
                $testing++;
            } elseif ( 'draft' === $result ) {
                /* translators: %s: snippet title */
                add_settings_error( 'snn-custom-codes', 'bulk_draft_' . $id, sprintf( esc_html__( '"%s" has an unpublished draft. Open it to test and publish the draft, or discard it, then switch it on.', 'snn' ), esc_html( snn_snippet_title( $key ) ) ), 'warning' );
            } else {
                add_settings_error( 'snn-custom-codes', 'bulk_error_' . $id, esc_html( $result ), 'error' );
            }
        }
    }

    $labels = array(
        /* translators: %d: number of snippets */
        'activate'   => _n( '%d snippet switched on.', '%d snippets switched on.', $done, 'snn' ),
        /* translators: %d: number of snippets */
        'deactivate' => _n( '%d snippet switched off.', '%d snippets switched off.', $done, 'snn' ),
        /* translators: %d: number of snippets */
        'delete'     => _n( '%d snippet deleted.', '%d snippets deleted.', $done, 'snn' ),
    );
    if ( $done ) {
        add_settings_error( 'snn-custom-codes', 'bulk_done', esc_html( sprintf( $labels[ $bulk ], $done ) ), 'updated' );
    }
    if ( $testing ) {
        /* translators: %d: number of snippets */
        add_settings_error( 'snn-custom-codes', 'bulk_testing', esc_html( sprintf( _n( '%d snippet is being tested and switches on if it passes.', '%d snippets are being tested and switch on if they pass.', $testing, 'snn' ), $testing ) ), 'info' );
    }
}

/**
 * The modern snippet editor's form: save (creating the snippet the first
 * time), or one of its buttons - discard draft, re-enable, load a revision,
 * clear revisions.
 */
function snn_snippets_handle_editor_post() {
    $id  = isset( $_POST['snn_snippet_id'] ) ? absint( $_POST['snn_snippet_id'] ) : 0;
    $key = $id ? snn_snippet_modern_key( $id ) : '';
    if ( $id && ! snn_snippet_key_exists( $key ) ) {
        snn_snippets_redirect( admin_url( 'admin.php?page=snn-custom-codes-snippets' ) );
    }

    if ( $id && isset( $_POST['snn_discard_draft_button'] ) ) {
        snn_snippet_delete_draft( $id );
        add_settings_error( 'snn-custom-codes', 'draft_discarded', esc_html__( 'Draft discarded. The editor shows the live version again.', 'snn' ), 'updated' );
        snn_snippets_redirect( snn_snippet_edit_url( $key ) );
    }

    if ( $id && isset( $_POST['snn_modern_unblock'] ) ) {
        snn_snippet_clear_error( $key );
        delete_transient( SNN_FATAL_ERROR_NOTICE_TRANSIENT );
        add_settings_error( 'snn-custom-codes', 'snippet_unblocked', esc_html__( 'The snippet has been re-enabled. If it is still broken it will be blocked again automatically.', 'snn' ), 'updated' );
        snn_snippets_redirect( snn_snippet_edit_url( $key ) );
    }

    if ( $id && isset( $_POST['snn_clear_revisions_button'] ) ) {
        $deleted = 0;
        foreach ( wp_get_post_revisions( $id, array( 'fields' => 'ids', 'posts_per_page' => -1 ) ) as $revision_id ) {
            if ( wp_delete_post_revision( $revision_id ) ) {
                $deleted++;
            }
        }
        /* translators: %d: number of revisions */
        add_settings_error( 'snn-custom-codes', 'revisions_cleared', esc_html( sprintf( _n( '%d revision cleared.', '%d revisions cleared.', $deleted, 'snn' ), $deleted ) ), 'updated' );
        snn_snippets_redirect( snn_snippet_edit_url( $key ) );
    }

    if ( $id && isset( $_POST['snn_restore_submit_button'] ) ) {
        $parts    = explode( '_', sanitize_text_field( wp_unslash( $_POST['snn_restore_submit_button'] ) ) );
        $revision = ( 3 === count( $parts ) && 'restore' === $parts[0] ) ? wp_get_post_revision( absint( $parts[1] ) ) : null;
        if ( $revision && (int) $revision->post_parent === $id ) {
            // Restoring is saving old code: the same draft -> test -> publish path.
            snn_snippet_process_save( array( 'slug' => $key, 'title' => snn_snippet_title( $key ) ), $revision->post_content, null, snn_snippet_get_settings( $id ), (string) get_post_meta( $id, SNN_SNIPPET_META_TEST_URL, true ) );
        } else {
            add_settings_error( 'snn-custom-codes', 'restore_failed', esc_html__( 'Failed to restore revision. Invalid ID or permissions.', 'snn' ), 'error' );
        }
        snn_snippets_redirect( snn_snippet_edit_url( $key ) );
    }

    // Save.
    $title = isset( $_POST['snn_title'] ) ? sanitize_text_field( wp_unslash( $_POST['snn_title'] ) ) : '';
    $title = '' !== $title ? $title : __( 'Untitled snippet', 'snn' );
    $code  = isset( $_POST['snn_code'] ) ? (string) wp_unslash( $_POST['snn_code'] ) : '';

    $requested = array(
        'type'       => isset( $_POST['snn_code_type'] ) ? sanitize_key( wp_unslash( $_POST['snn_code_type'] ) ) : 'php',
        'location'   => isset( $_POST['snn_location'] ) ? sanitize_key( wp_unslash( $_POST['snn_location'] ) ) : 'everywhere',
        'priority'   => isset( $_POST['snn_priority'] ) ? (int) $_POST['snn_priority'] : 10,
        'conditions' => isset( $_POST['snn_conditions'] ) ? json_decode( (string) wp_unslash( $_POST['snn_conditions'] ), true ) : null,
    );
    $dropped  = 0;
    $settings = snn_snippet_normalize_settings( $requested, $dropped );
    if ( $settings['location'] !== $requested['location'] ) {
        add_settings_error( 'snn-custom-codes', 'location_moved', esc_html__( 'HTML, CSS and JavaScript can only go where output is printed, so the location was changed to Site head.', 'snn' ), 'warning' );
    }
    if ( $dropped ) {
        add_settings_error( 'snn-custom-codes', 'conditions_dropped', esc_html__( 'Some conditions were removed: they need to know which page is showing, and this location runs before that is known.', 'snn' ), 'warning' );
    }
    if ( 'php' === $settings['type'] ) {
        $code = snn_snippet_strip_open_tag( $code );
    }

    $test_url = isset( $_POST['snn_test_url'] ) ? esc_url_raw( trim( (string) wp_unslash( $_POST['snn_test_url'] ) ) ) : '';
    if ( '' !== $test_url && ! snn_snippet_is_own_url( $test_url ) ) {
        add_settings_error( 'snn-custom-codes', 'test_url', esc_html__( 'The test URL must be a page of this site, so it was cleared.', 'snn' ), 'warning' );
        $test_url = '';
    }

    if ( ! $id ) {
        $id = wp_insert_post( array(
            'post_type'    => 'snn_code_snippet',
            'post_status'  => 'private',
            'post_title'   => wp_slash( $title ),
            'post_content' => '',
        ), true );
        if ( is_wp_error( $id ) ) {
            add_settings_error( 'snn-custom-codes', 'create_failed', esc_html( $id->get_error_message() ), 'error' );
            snn_snippets_redirect( admin_url( 'admin.php?page=snn-custom-codes-snippets&view=new' ) );
        }
        $key = snn_snippet_modern_key( $id );
        // The live version starts empty with default settings; what was
        // submitted goes through the same path as any later save.
        snn_snippet_save_settings( $id, snn_snippet_default_settings() );
        snn_snippet_set_enabled( $key, false ); // New snippets start switched off.
    } elseif ( get_post_field( 'post_title', $id ) !== $title ) {
        wp_update_post( array( 'ID' => $id, 'post_title' => wp_slash( $title ) ) );
        snn_snippets_rebuild_cache();
    }

    update_post_meta( $id, SNN_SNIPPET_META_TEST_URL, $test_url );
    snn_snippet_process_save( array( 'slug' => $key, 'title' => $title ), $code, isset( $_POST['snn_active'] ), $settings, $test_url );

    if ( ! get_settings_errors( 'snn-custom-codes' ) ) {
        add_settings_error( 'snn-custom-codes', 'saved', esc_html__( 'Snippet saved.', 'snn' ), 'updated' );
    }
    snn_snippets_redirect( snn_snippet_edit_url( $key ) );
}

/**
 * AJAX: the on/off switch in the snippet list.
 */
add_action( 'wp_ajax_snn_snippet_toggle', 'snn_ajax_snippet_toggle' );
function snn_ajax_snippet_toggle() {
    check_ajax_referer( 'snn_snippet_toggle', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Permission denied.', 'snn' ) ), 403 );
    }
    $key = isset( $_POST['key'] ) ? sanitize_key( wp_unslash( $_POST['key'] ) ) : '';
    if ( ! snn_snippet_key_exists( $key ) ) {
        wp_send_json_error( array( 'message' => __( 'Snippet not found.', 'snn' ) ), 404 );
    }

    if ( empty( $_POST['on'] ) ) {
        snn_snippets_switch_off( $key );
        wp_send_json_success( array( 'state' => 'off' ) );
    }

    $result = snn_snippets_switch_on( $key );
    if ( in_array( $result, array( 'on', 'testing', 'draft' ), true ) ) {
        wp_send_json_success( array( 'state' => $result, 'url' => snn_snippet_edit_url( $key ) ) );
    }
    wp_send_json_error( array( 'message' => $result ) );
}

/**
 * AJAX: posts and pages for the "Page or post" condition's search box.
 */
add_action( 'wp_ajax_snn_snippet_search_posts', 'snn_ajax_snippet_search_posts' );
function snn_ajax_snippet_search_posts() {
    check_ajax_referer( 'snn_snippet_search', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array(), 403 );
    }
    $term  = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';
    $types = array_values( get_post_types( array( 'public' => true ) ) );
    $args  = array(
        'post_type'        => $types,
        'post_status'      => 'publish',
        'posts_per_page'   => 20,
        'no_found_rows'    => true,
        'suppress_filters' => true,
    );
    if ( ctype_digit( $term ) ) {
        $args['post__in'] = array( (int) $term );
    } else {
        $args['s'] = $term;
    }

    $found = array();
    foreach ( get_posts( $args ) as $post ) {
        $type    = get_post_type_object( $post->post_type );
        $found[] = array(
            'id'    => (int) $post->ID,
            'title' => '' !== $post->post_title ? $post->post_title : '#' . $post->ID,
            'type'  => $type ? $type->labels->singular_name : $post->post_type,
        );
    }
    wp_send_json_success( $found );
}

/** Where "Exit safe mode" leads: the current page with safe mode switched off. */
function snn_snippets_safe_mode_exit_url() {
    return add_query_arg( 'snn_safe_mode', '0' );
}

/**
 * While sticky safe mode is on: a notice in the admin, and a reminder with an
 * exit link in the admin bar on both the admin and the site.
 */
function snn_snippets_safe_mode_notice() {
    if ( ! snn_snippets_sticky_safe_mode() ) {
        return;
    }
    echo '<div class="notice notice-warning"><p><strong>' . esc_html__( 'Snippets safe mode is on.', 'snn' ) . '</strong> '
        . esc_html__( 'No code snippet runs while you browse the site and the admin in this browser. Visitors are not affected.', 'snn' )
        . ' <a href="' . esc_url( snn_snippets_safe_mode_exit_url() ) . '">' . esc_html__( 'Exit safe mode', 'snn' ) . '</a></p></div>';
}
add_action( 'admin_notices', 'snn_snippets_safe_mode_notice' );

function snn_snippets_safe_mode_admin_bar( $bar ) {
    if ( ! snn_snippets_sticky_safe_mode() ) {
        return;
    }
    $bar->add_node( array(
        'id'    => 'snn-snippets-safe-mode',
        'title' => esc_html__( 'Snippets safe mode: exit', 'snn' ),
        'href'  => esc_url( snn_snippets_safe_mode_exit_url() ),
        'meta'  => array( 'title' => esc_attr__( 'No code snippet runs for you while safe mode is on. Click to exit.', 'snn' ) ),
    ) );
}
add_action( 'admin_bar_menu', 'snn_snippets_safe_mode_admin_bar', 100 );

// Read ?snn_safe_mode= (and set or clear the cookie) on every request, before
// any snippet runs and even while snippet execution is off.
add_action( 'after_setup_theme', 'snn_snippets_sticky_safe_mode', -10001 );

/**
 * AJAX handler for fetching revision content to preview in editor.
 */
add_action( 'wp_ajax_snn_get_revision_content', 'snn_ajax_get_revision_content_callback' );
function snn_ajax_get_revision_content_callback() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'snn_preview_revision_nonce' ) ) {
        wp_send_json_error( array( 'message' => __( 'Nonce verification failed.', 'snn' ) ), 403 );
        return;
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Permission denied to manage options.', 'snn' ) ), 403 );
        return;
    }
    $revision_id = isset( $_POST['revision_id'] ) ? absint( $_POST['revision_id'] ) : 0;
    if ( ! $revision_id ) {
        wp_send_json_error( array( 'message' => __( 'Missing revision ID.', 'snn' ) ) );
        return;
    }
    $revision = wp_get_post_revision( $revision_id );
    if ( ! $revision ) {
        wp_send_json_error( array( 'message' => __( 'Revision not found.', 'snn' ) ) );
        return;
    }
    if ( ! current_user_can( 'edit_post', $revision->post_parent ) ) {
        wp_send_json_error( array( 'message' => __( 'Permission denied for accessing this revision content.', 'snn' ) ), 403 );
        return;
    }
    wp_send_json_success( array( 'content' => $revision->post_content, 'title'   => wp_post_revision_title_expanded( $revision ) ) );
}

/**
 * AJAX handler for dismissing the fatal error admin notice.
 */
add_action( 'wp_ajax_snn_dismiss_fatal_error_notice', 'snn_ajax_dismiss_fatal_error_notice_callback' );
function snn_ajax_dismiss_fatal_error_notice_callback() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'snn_dismiss_fatal_notice_nonce' ) ) {
        wp_send_json_error( array( 'message' => __( 'Nonce verification failed.', 'snn' ) ), 403 );
        return;
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Permission denied.', 'snn' ) ), 403 );
        return;
    }
    delete_transient( SNN_FATAL_ERROR_NOTICE_TRANSIENT );
    wp_send_json_success();
}

/**
 * AJAX: the browser has loaded every test page for a draft. Judge what those
 * page loads recorded and publish the draft only if all of them came through
 * clean. The outcome is queued as a notice and the page reloads to show it.
 */
add_action( 'wp_ajax_snn_snippet_test_finish', 'snn_ajax_snippet_test_finish' );
function snn_ajax_snippet_test_finish() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'snn_snippet_test_finish' ) ) {
        wp_send_json_error( array( 'message' => __( 'Nonce verification failed.', 'snn' ) ), 403 );
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Permission denied.', 'snn' ) ), 403 );
    }

    $token = isset( $_POST['token'] ) ? preg_replace( '/[^A-Za-z0-9]/', '', (string) wp_unslash( $_POST['token'] ) ) : '';
    $slug  = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
    if ( 32 !== strlen( $token ) || ! snn_snippet_key_exists( $slug ) ) {
        wp_send_json_error( array( 'message' => __( 'Invalid test run.', 'snn' ) ), 400 );
    }

    $def     = array( 'slug' => $slug, 'title' => snn_snippet_title( $slug ) );
    $post_id = snn_get_code_snippet_id( $slug );
    $draft   = snn_snippet_get_draft( $post_id );
    $test    = get_transient( SNN_SNIPPET_TEST_TRANSIENT . $token );

    // The run must belong to the draft as it is now: a newer save or a discard
    // replaces the token, and the code tested must be the code to publish.
    if ( ! is_array( $test ) || ! $draft || 'pending' !== $draft['status'] || $draft['token'] !== $token
        || $test['slug'] !== $slug || $test['hash'] !== $draft['hash'] ) {
        snn_snippet_flash( 'error', sprintf(
            /* translators: %s: snippet title */
            __( '"%s": this test run is no longer valid (it expired, or the draft changed). Save again to test the current draft.', 'snn' ),
            esc_html( $def['title'] )
        ) );
        wp_send_json_error( array( 'message' => 'stale' ) );
    }

    delete_transient( SNN_SNIPPET_TEST_TRANSIENT . $token ); // Single use.
    $verdict   = snn_snippet_evaluate_test( $test );
    $switch_on = ! empty( $draft['switch_on'] );
    $settings  = ( isset( $draft['settings'] ) && is_array( $draft['settings'] ) ) ? $draft['settings'] : null;

    if ( ! $verdict['problems'] ) {
        $published = snn_snippet_publish( $def, $post_id, $draft['code'], $switch_on, $settings );
        if ( is_wp_error( $published ) ) {
            snn_snippet_flash( 'error', sprintf(
                /* translators: 1: snippet title, 2: error message */
                __( '"%1$s" passed the test but could not be saved: %2$s', 'snn' ),
                esc_html( $def['title'] ),
                esc_html( $published->get_error_message() )
            ) );
            wp_send_json_error( array( 'message' => 'save_failed' ) );
        }

        snn_snippet_flash( 'success', sprintf(
            /* translators: %s: snippet title */
            __( '"%s" passed the test on every page and is now live.', 'snn' ),
            esc_html( $def['title'] )
        ) . ( $switch_on ? ' ' . esc_html__( 'It has been switched on.', 'snn' ) : '' ) );

        if ( $verdict['warnings'] ) {
            snn_snippet_flash( 'warning', sprintf(
                /* translators: %s: snippet title */
                __( 'Warnings while testing "%s" (they did not stop it from going live):', 'snn' ),
                esc_html( $def['title'] )
            ) . '<br>' . implode( '<br>', array_map( 'esc_html', array_slice( $verdict['warnings'], 0, 10 ) ) ) );
        }
        wp_send_json_success( array( 'published' => true ) );
    }

    $problem = $verdict['problems'][0];
    snn_snippet_fail_draft( $slug, $post_id, $draft['code'], $switch_on, $problem, $settings );
    snn_snippet_flash( 'error', sprintf(
        /* translators: 1: snippet title, 2: test page, 3: error type, 4: error message */
        __( '"%1$s" was NOT published: it failed the test (%2$s). %3$s: %4$s', 'snn' ),
        esc_html( $def['title'] ),
        esc_html( '' !== $problem['target'] ? $problem['target'] : __( 'all pages', 'snn' ) ),
        esc_html( $problem['type'] ),
        esc_html( $problem['message'] )
    ) . ( $problem['line'] ? ' ' . sprintf( esc_html__( '(line %d)', 'snn' ), absint( $problem['line'] ) ) : '' )
      . ' ' . esc_html__( 'The live version keeps running unchanged.', 'snn' ) );
    wp_send_json_success( array( 'published' => false ) );
}


/**
 * What the modern editor shows: the unpublished draft when there is one (the
 * latest work), otherwise the live code and settings.
 *
 * @return array{code: string, settings: array, draft: array|false}
 */
function snn_snippets_editor_state( $id ) {
    if ( ! $id ) {
        return array( 'code' => '', 'settings' => snn_snippet_default_settings(), 'draft' => false );
    }
    $draft = snn_snippet_get_draft( $id );
    return array(
        'code'     => $draft ? (string) $draft['code'] : snn_get_code_snippet_content( snn_snippet_modern_key( $id ) ),
        'settings' => ( $draft && isset( $draft['settings'] ) && is_array( $draft['settings'] ) ) ? $draft['settings'] : snn_snippet_get_settings( $id ),
        'draft'    => $draft,
    );
}

/**
 * The conditional logic builder's rules, for the editor script: labels,
 * operators and values as ordered [value, label] pairs.
 */
function snn_snippet_rules_ui_data() {
    $labels = snn_snippet_rule_labels();
    $pairs  = function ( $assoc ) {
        $out = array();
        foreach ( $assoc as $value => $label ) {
            $out[] = array( (string) $value, $label );
        }
        return $out;
    };

    $roles = array();
    foreach ( wp_roles()->get_names() as $role => $name ) {
        $roles[ $role ] = translate_user_role( $name );
    }
    $post_types = array();
    foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $type ) {
        $post_types[ $type->name ] = $type->labels->singular_name;
    }
    $values = $labels['values'] + array( 'user_role' => $roles, 'post_type' => $post_types );

    $rules = array();
    foreach ( snn_snippet_rule_defs() as $name => $def ) {
        $ops = array();
        foreach ( $def['ops'] as $op ) {
            $ops[] = array( $op, $labels['ops'][ $op ] );
        }
        $rules[ $name ] = array(
            'label'  => $labels['rules'][ $name ],
            'ops'    => $ops,
            'input'  => 'url_path' === $name ? 'text' : ( 'post_id' === $name ? 'post' : 'select' ),
            'values' => isset( $values[ $name ] ) ? $pairs( $values[ $name ] ) : array(),
            'page'   => $def['page'],
            // Decided per visitor on the server, so a shared page cache defeats them.
            'cache'  => in_array( $name, array( 'logged_in', 'user_role', 'device' ), true ),
        );
    }
    return $rules;
}

/**
 * Screen 2 and 3: the modern snippet editor - title, code with a Code Type
 * picker, the Insertion panel and the Conditional Logic panel, plus the
 * draft/test notice and revisions.
 */
function snn_snippets_render_editor() {
    $id         = snn_snippets_requested_id();
    $key        = $id ? snn_snippet_modern_key( $id ) : '';
    $state      = snn_snippets_editor_state( $id );
    $draft      = $state['draft'];
    $settings   = $state['settings'];
    $title      = $id ? (string) get_post_field( 'post_title', $id ) : '';
    $on         = $id && snn_snippet_is_enabled( $key );
    $pending_on = $draft && ! empty( $draft['switch_on'] );
    $test_url   = $id ? (string) get_post_meta( $id, SNN_SNIPPET_META_TEST_URL, true ) : '';
    $error      = $id ? snn_snippet_get_error( $key ) : false;
    $types      = snn_snippet_code_types();
    $map        = snn_snippet_location_map();
    $places     = snn_snippet_location_labels();
    $list_url   = admin_url( 'admin.php?page=snn-custom-codes-snippets' );
    $action_url = $id ? snn_snippet_edit_url( $key ) : admin_url( 'admin.php?page=snn-custom-codes-snippets&view=new' );
    $revisions  = ( $id && wp_revisions_enabled( get_post( $id ) ) ) ? wp_get_post_revisions( $id, array( 'posts_per_page' => 20, 'orderby' => 'post_date', 'order' => 'DESC' ) ) : array();

    $post_titles = array();
    foreach ( $settings['conditions']['groups'] as $group ) {
        foreach ( $group as $rule ) {
            if ( 'post_id' === $rule['rule'] ) {
                $post_titles[ $rule['value'] ] = get_the_title( (int) $rule['value'] );
            }
        }
    }

    $type_help = array(
        'php'      => __( 'Pure PHP. A leading <?php is optional and removed on save. Tested on your site before it goes live.', 'snn' ),
        'html_php' => __( 'HTML with <?php ?> blocks, the way legacy snippets run. Tested on your site before it goes live.', 'snn' ),
        'html'     => __( 'Printed as-is. Cannot crash the site, so it goes live without a test.', 'snn' ),
        'css'      => __( 'Printed inside a <style> tag - do not add the tag yourself. Goes live without a test.', 'snn' ),
        'js'       => __( 'Printed inside a <script> tag - do not add the tag yourself. Goes live without a test.', 'snn' ),
    );
    $stage_help = array(
        'early'  => __( 'Runs as WordPress starts, before it knows which page is showing. PHP only; anything printed is discarded.', 'snn' ),
        'query'  => __( 'Runs on the front end once WordPress knows which page is showing, before the page is printed. PHP only; anything printed is discarded.', 'snn' ),
        'output' => __( 'Prints into the page at this spot. Any code type.', 'snn' ),
    );

    snn_snippet_consume_flash();
    ?>
    <div class="wrap snn-snippets-wrap">
        <?php snn_snippets_render_header( 'list' ); ?>
        <?php settings_errors( 'snn-custom-codes' ); ?>
        <?php snn_snippets_render_status_notices(); ?>

        <form method="post" action="<?php echo esc_url( $action_url ); ?>" id="snn-snippet-editor-form">
            <?php wp_nonce_field( 'snn_modern_action', 'snn_modern_nonce' ); ?>
            <input type="hidden" name="snn_modern_action" value="save">
            <input type="hidden" name="snn_snippet_id" value="<?php echo (int) $id; ?>">

            <p class="snn-breadcrumb"><a href="<?php echo esc_url( $list_url ); ?>">&larr; <?php esc_html_e( 'All snippets', 'snn' ); ?></a></p>

            <div class="snn-editor-top">
                <h2><?php echo $id ? esc_html__( 'Edit Snippet', 'snn' ) : esc_html__( 'Add New Snippet', 'snn' ); ?></h2>
                <span class="snn-spacer"></span>
                <?php if ( $pending_on ) : ?>
                    <span class="description"><?php esc_html_e( '(switches on once the draft passes its test)', 'snn' ); ?></span>
                <?php endif; ?>
                <span id="snn-active-label"><?php esc_html_e( 'Active', 'snn' ); ?></span>
                <label class="snn-switch">
                    <input type="checkbox" name="snn_active" value="1" aria-labelledby="snn-active-label" <?php checked( $on || $pending_on ); ?>>
                    <span class="snn-switch-slider" aria-hidden="true"></span>
                </label>
                <button type="submit" name="snn_modern_save" class="button button-primary"><?php esc_html_e( 'Save Snippet', 'snn' ); ?></button>
            </div>

            <label class="screen-reader-text" for="snn_title"><?php esc_html_e( 'Snippet title', 'snn' ); ?></label>
            <input type="text" id="snn_title" name="snn_title" class="snn-title-input" value="<?php echo esc_attr( $title ); ?>" placeholder="<?php esc_attr_e( 'Snippet title', 'snn' ); ?>">

            <?php if ( $error ) : ?>
                <div class="notice notice-error inline snn-php-execution-warning">
                    <p>
                        <strong><?php esc_html_e( 'This snippet is blocked and is not running.', 'snn' ); ?></strong><br>
                        <code><?php echo esc_html( $error['type'] ); ?></code>
                        <?php echo esc_html( $error['message'] ); ?>
                        <?php if ( ! empty( $error['line'] ) ) : ?>
                            <?php printf( esc_html__( '(line %d)', 'snn' ), absint( $error['line'] ) ); ?>
                        <?php endif; ?>
                    </p>
                    <p><?php esc_html_e( 'Fix the code and save to re-enable it automatically, or force it back on now:', 'snn' ); ?></p>
                    <p><button type="submit" name="snn_modern_unblock" value="1" class="button"><?php esc_html_e( 'Re-enable this snippet', 'snn' ); ?></button></p>
                </div>
            <?php endif; ?>

            <?php
            if ( $draft ) {
                snn_snippet_render_draft_notice( 'modern', array( 'slug' => $key, 'title' => $title ), $draft, snn_snippet_edit_url( $key ) );
            }
            ?>

            <div class="snn-editor-revision-wrapper">
                <div class="snn-editor-area">
                    <div class="snn-code-head">
                        <strong><?php esc_html_e( 'Code', 'snn' ); ?></strong>
                        <span class="snn-spacer"></span>
                        <label for="snn_code_type"><?php esc_html_e( 'Code type', 'snn' ); ?></label>
                        <select id="snn_code_type" name="snn_code_type">
                            <?php foreach ( $types as $value => $type ) : ?>
                                <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['type'], $value ); ?>><?php echo esc_html( $type['label'] ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php foreach ( $type_help as $value => $help ) : ?>
                        <p class="description snn-type-help" data-type="<?php echo esc_attr( $value ); ?>" <?php echo $value === $settings['type'] ? '' : 'hidden'; ?>><?php echo esc_html( $help ); ?></p>
                    <?php endforeach; ?>
                    <label class="screen-reader-text" for="snn_modern_code"><?php esc_html_e( 'Code', 'snn' ); ?></label>
                    <textarea id="snn_modern_code" name="snn_code" class="large-text code" rows="25"><?php echo esc_textarea( $state['code'] ); ?></textarea>
                </div>

                <?php if ( $id ) : ?>
                    <div class="snn-revisions-panel" data-active-editor-id="snn_modern_code">
                        <h4><?php esc_html_e( 'Revisions', 'snn' ); ?></h4>
                        <div class="snn-revisions-panel-inner">
                            <?php if ( $revisions ) : ?>
                                <ul class="snn-revisions-list">
                                    <?php foreach ( $revisions as $revision ) :
                                        $author = get_userdata( $revision->post_author );
                                        $when   = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $revision->post_date ) );
                                        $ago    = human_time_diff( strtotime( $revision->post_date_gmt ), time() );
                                        ?>
                                        <li>
                                            <span class="revision-info"><?php echo esc_html( sprintf( '%s by %s (%s %s)', $when, $author ? $author->display_name : __( 'Unknown Author', 'snn' ), $ago, __( 'ago', 'snn' ) ) ); ?></span>
                                            <div class="revision-actions">
                                                <button type="button" class="button button-secondary button-small snn-preview-revision" data-revision-id="<?php echo esc_attr( $revision->ID ); ?>"><?php esc_html_e( 'Preview in Editor', 'snn' ); ?></button>
                                                <a href="<?php echo esc_url( admin_url( 'revision.php?revision=' . $revision->ID . '&nonce=' . wp_create_nonce( 'view-revision_' . $revision->ID ) ) ); ?>" target="_blank" class="button button-small snn-view-comparison-link" title="<?php esc_attr_e( 'View full comparison in new tab', 'snn' ); ?>">
                                                    <span class="dashicons dashicons-search"></span> <?php esc_html_e( 'Compare', 'snn' ); ?>
                                                </a>
                                                <button type="submit" name="snn_restore_submit_button" value="<?php echo esc_attr( 'restore_' . $revision->ID . '_modern' ); ?>" class="button button-primary button-small snn-restore-revision-button" style="display:none;"><?php esc_html_e( 'Load Revision & Test', 'snn' ); ?></button>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <div class="snn-manage-revisions-section">
                                    <button type="submit" name="snn_clear_revisions_button" value="modern" class="button snn-clear-revisions-button"><?php esc_html_e( 'Clear All Revisions for this Snippet', 'snn' ); ?></button>
                                    <p class="description"><?php esc_html_e( 'This will permanently delete all revisions for this snippet. Cannot be undone.', 'snn' ); ?></p>
                                </div>
                            <?php else : ?>
                                <p><?php esc_html_e( 'No past revisions yet. Each version that goes live is kept here.', 'snn' ); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="postbox snn-panel">
                <h2><?php esc_html_e( 'Insertion', 'snn' ); ?></h2>
                <div class="inside">
                    <p class="description"><?php esc_html_e( 'Choose where the snippet runs. Page-specific rules live in Conditional Logic below.', 'snn' ); ?></p>
                    <div class="snn-field">
                        <label for="snn_location"><strong><?php esc_html_e( 'Location', 'snn' ); ?></strong></label>
                        <div>
                            <select id="snn_location" name="snn_location">
                                <?php foreach ( $map as $value => $location ) : ?>
                                    <option value="<?php echo esc_attr( $value ); ?>"
                                            data-stage="<?php echo esc_attr( $location['stage'] ); ?>"
                                            data-knows-page="<?php echo snn_snippet_location_knows_page( $value ) ? '1' : '0'; ?>"
                                            data-help="<?php echo esc_attr( $stage_help[ $location['stage'] ] ); ?>"
                                            <?php selected( $settings['location'], $value ); ?>
                                            <?php disabled( ! snn_snippet_location_allows_type( $value, $settings['type'] ) ); ?>><?php echo esc_html( $places[ $value ] ); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description snn-location-help"><?php echo esc_html( $stage_help[ $map[ $settings['location'] ]['stage'] ] ); ?></p>
                        </div>
                    </div>
                    <div class="snn-field">
                        <label for="snn_priority"><strong><?php esc_html_e( 'Priority', 'snn' ); ?></strong></label>
                        <div>
                            <input type="number" id="snn_priority" name="snn_priority" class="small-text" min="0" max="9999" step="1" value="<?php echo (int) $settings['priority']; ?>">
                            <span class="description"><?php esc_html_e( 'Lower runs first. Snippets with the same priority run oldest first.', 'snn' ); ?></span>
                        </div>
                    </div>
                    <div class="snn-field">
                        <label for="snn_test_url"><strong><?php esc_html_e( 'Test on this URL', 'snn' ); ?></strong></label>
                        <div>
                            <input type="url" id="snn_test_url" name="snn_test_url" class="regular-text" value="<?php echo esc_attr( $test_url ); ?>" placeholder="<?php echo esc_attr( home_url( '/' ) ); ?>">
                            <p class="description"><?php esc_html_e( 'Optional. Front-end test pages load this URL. Leave empty to use a page that matches the conditions (for example the newest product for "Post type is Product"), or else the home page.', 'snn' ); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="postbox snn-panel">
                <h2><?php esc_html_e( 'Conditional Logic', 'snn' ); ?></h2>
                <div class="inside">
                    <p class="description"><?php esc_html_e( 'Limit the pages where this snippet runs. Rows in a group must all match; any one group matching is enough.', 'snn' ); ?></p>
                    <div id="snn-conditions"
                         data-rules="<?php echo esc_attr( wp_json_encode( snn_snippet_rules_ui_data() ) ); ?>"
                         data-post-titles="<?php echo esc_attr( wp_json_encode( (object) $post_titles ) ); ?>"></div>
                    <input type="hidden" id="snn_conditions_input" name="snn_conditions" value="<?php echo esc_attr( wp_json_encode( $settings['conditions'] ) ); ?>">
                    <noscript><p><?php esc_html_e( 'Conditional logic needs JavaScript. Saving without it keeps the current conditions.', 'snn' ); ?></p></noscript>
                </div>
            </div>

            <p class="description"><?php esc_html_e( 'Changes to a PHP snippet that runs are kept as a draft and tested on your site before they go live; the live version keeps running until the test passes. Changes to type, location, priority and conditions are tested the same way.', 'snn' ); ?></p>
            <p class="submit">
                <button type="submit" name="snn_modern_save" class="button button-primary button-large"><?php esc_html_e( 'Save Snippet', 'snn' ); ?></button>
                <?php if ( $id ) : ?>
                    <a class="submitdelete snn-delete-snippet" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'snn_action' => 'delete', 'snippet' => $id ), $list_url ), 'snn_delete_snippet_' . $id ) ); ?>"><?php esc_html_e( 'Delete snippet', 'snn' ); ?></a>
                <?php endif; ?>
            </p>
        </form>
    </div>
    <?php
}

/**
 * Register fatal error shutdown handler.
 */
function snn_register_fatal_error_handler() {
    register_shutdown_function( 'snn_fatal_error_shutdown_handler' );
}
// Registered at load time (bottom of this file), before any snippet can run:
// modern snippets start at after_setup_theme, long before init.

/**
 * The snippet the fatal handler blocked in this request, or ''. Pass a key to set.
 */
function snn_snippet_blamed_slug( $slug = null ) {
    static $blamed = '';
    if ( null !== $slug ) {
        $blamed = (string) $slug;
    }
    return $blamed;
}

/**
 * WordPress' own fatal error handler is registered before ours and, when it
 * shows the "critical error" page, ends the request with wp_die() - so the
 * shutdown functions registered after it (ours) never run, and a crash was
 * only caught a request or two later by the crash guard. Its message filter
 * fires before that wp_die(): do our bookkeeping there, then tell an
 * administrator on the error page which snippet did it.
 */
function snn_snippets_php_error_message( $message, $error ) {
    snn_snippet_test_record_result();
    snn_fatal_error_shutdown_handler();
    snn_snippet_finalize_pending();

    $slug = snn_snippet_blamed_slug();
    if ( '' !== $slug && function_exists( 'current_user_can' ) && current_user_can( 'manage_options' ) ) {
        $message .= '<p>' . sprintf(
            /* translators: %s: snippet title */
            esc_html__( 'The code snippet "%s" caused this error. It has been blocked, so the next page load works again.', 'snn' ),
            esc_html( snn_snippet_title( $slug ) )
        ) . ' <a href="' . esc_url( snn_snippet_edit_url( $slug ) ) . '">' . esc_html__( 'Open the snippet', 'snn' ) . '</a></p>';
    }
    return $message;
}
add_filter( 'wp_php_error_message', 'snn_snippets_php_error_message', 10, 2 );

/**
 * A request that ran snippets finished without a fatal: the site is healthy, so
 * drop any streak. Without this, unrelated fatals spread across weeks would
 * eventually accumulate into a global shutdown for no reason.
 *
 * Costs nothing on requests where no snippet ran, and no write when already zero.
 */
function snn_reset_unattributed_fatal_streak() {
    if ( ! snn_snippet_executed_slugs() ) {
        return;
    }
    if ( 0 !== (int) get_option( SNN_UNATTRIBUTED_FATAL_OPTION, 0 ) ) {
        update_option( SNN_UNATTRIBUTED_FATAL_OPTION, 0, true );
    }
}

/**
 * A request that ran snippets ended in a fatal we could NOT pin on any snippet.
 *
 * We refuse to block a specific snippet on this evidence - we would be guessing,
 * and guessing wrong disables working code while the real culprit keeps running.
 * So we count instead. Once requests keep dying, the global switch goes off:
 * a site with its snippets disabled is recoverable, a site that white-screens
 * every request is not.
 */
function snn_record_unattributed_fatal( $error ) {
    // No snippet executed this request, so this fatal is somebody else's problem.
    if ( ! snn_snippet_executed_slugs() ) {
        return;
    }

    $streak = (int) get_option( SNN_UNATTRIBUTED_FATAL_OPTION, 0 ) + 1;

    if ( $streak < SNN_UNATTRIBUTED_FATAL_LIMIT ) {
        update_option( SNN_UNATTRIBUTED_FATAL_OPTION, $streak, true );
        return;
    }

    update_option( SNN_UNATTRIBUTED_FATAL_OPTION, 0, true );
    update_option( 'snn_codes_snippets_enabled', 0 );

    snn_log_error_event(
        'PHP Fatal Error (global kill switch)',
        sprintf(
            /* translators: 1: number of consecutive fatal requests, 2: the PHP error message */
            __( '%1$d consecutive requests ended in a fatal error while snippets were running, and none could be attributed to a specific snippet. Global snippet execution has been switched off so the site can load. Last error: %2$s', 'snn' ),
            SNN_UNATTRIBUTED_FATAL_LIMIT,
            isset( $error['message'] ) ? $error['message'] : ''
        ),
        'unknown_or_direct_fatal',
        isset( $error['file'] ) ? $error['file'] : '',
        isset( $error['line'] ) ? $error['line'] : 0,
        '',
        ''
    );

    set_transient( SNN_FATAL_ERROR_NOTICE_TRANSIENT, array(
        'message' => sprintf(
            /* translators: %d: number of consecutive fatal requests */
            __( '%d consecutive requests ended in a fatal error while snippets were running. The error could not be traced to one snippet, so global snippet execution was switched off.', 'snn' ),
            SNN_UNATTRIBUTED_FATAL_LIMIT
        ) . ' ' . ( isset( $error['message'] ) ? $error['message'] : '' ),
        'file'    => isset( $error['file'] ) ? $error['file'] : '',
        'line'    => isset( $error['line'] ) ? $error['line'] : 0,
        'type'    => 'Fatal Error (global kill switch)',
        'slug'    => '',
    ), DAY_IN_SECONDS );
}

/**
 * Fatal error shutdown handler.
 */
function snn_fatal_error_shutdown_handler() {
    // Once: it also runs early, from WordPress' error page (see snn_snippets_php_error_message()).
    static $ran = false;
    if ( $ran ) {
        return;
    }
    $ran = true;

    // A test page load reports its own fatal to the test run and blocks nothing.
    if ( snn_snippet_test_context() ) {
        return;
    }

    $error = error_get_last();

    if ( ! $error || ! in_array( $error['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR ), true ) ) {
        // Request completed cleanly.
        snn_reset_unattributed_fatal_streak();
        return;
    }

    if ( ! get_option( 'snn_codes_snippets_enabled', 0 ) ) {
        return;
    }

    // Proof, not guesswork. For code running inside eval(), PHP reports the file
    // as "/path/to/custom-code-snippets.php(657) : eval()'d code" - so the test is
    // a prefix match plus the eval marker, never an equality check against
    // __FILE__ (which can never match) and never a scan of the error message
    // (which matches half the plugins on the internet).
    $self       = wp_normalize_path( __FILE__ );
    $error_file = isset( $error['file'] ) ? wp_normalize_path( (string) $error['file'] ) : '';

    $from_snippet = ( '' !== $error_file )
        && ( 0 === strpos( $error_file, $self ) )
        && ( false !== strpos( $error_file, "eval()'d code" ) );

    if ( ! $from_snippet ) {
        /*
         * Not provably ours - but very possibly still our fault. A fatal inside a
         * hook the snippet registered, inside a file it included, memory
         * exhaustion or "Maximum execution time exceeded" all report a file that
         * is not the eval'd code. Blaming a snippet on that would be a guess, so
         * count it instead and let the streak trip the global switch. If the
         * fatal really was another plugin's, the count resets as soon as one
         * request completes.
         */
        snn_record_unattributed_fatal( $error );
        return;
    }

    // Provably ours, and about to be blocked precisely. Not an unattributed fatal.
    snn_reset_unattributed_fatal_streak();

    // Attribution, best evidence first: the snippet whose line range holds the
    // failing line (exact, even inside a hook that fired later), then the
    // snippet mid-eval, then the only snippet that ran.
    $located  = snn_snippet_locate_line( $error_file, $error['line'] );
    $slug     = $located ? $located['slug'] : snn_snippet_active_slug();
    $executed = snn_snippet_executed_slugs();
    if ( $located ) {
        $error['line'] = $located['line'];
    }

    if ( '' === $slug && 1 === count( $executed ) ) {
        // The fatal happened after eval() returned - typically inside a hook the
        // snippet registered. Only one snippet ran, so it is still unambiguous.
        $slug = $executed[0];
    }

    snn_log_error_event(
        'PHP Fatal Error (Shutdown Handler)',
        $error['message'],
        '' !== $slug ? $slug : 'unknown_or_direct_fatal',
        $error['file'],
        $error['line'],
        '' // Code context not available in shutdown handler
    );

    if ( '' !== $slug ) {
        // Block the guilty snippet only. Everything else keeps working.
        snn_snippet_record_error(
            $slug,
            snn_get_php_error_type_string( $error['type'] ),
            $error['message'],
            $error['line']
        );
        // Tell the verification pass (which runs right after us) that this crash
        // is handled, so it does not also flag whatever snippet happens to be
        // holding the in-flight marker.
        snn_snippet_fatal_attributed( true );
        snn_snippet_blamed_slug( $slug );
    } else {
        // Certainly a snippet, but more than one ran and none was mid-eval.
        // Fall back to the global switch rather than block the wrong snippet.
        update_option( 'snn_codes_snippets_enabled', 0 );
        snn_snippet_fatal_attributed( true );
    }

    set_transient( SNN_FATAL_ERROR_NOTICE_TRANSIENT, array(
        'message' => $error['message'],
        'file'    => '' !== $slug ? 'Snippet: ' . snn_snippet_title( $slug ) : $error['file'],
        'line'    => $error['line'],
        'type'    => snn_get_php_error_type_string( $error['type'] ),
        'slug'    => $slug,
    ), DAY_IN_SECONDS );
}

/**
 * Helper function to convert PHP error constant to a user-friendly string.
 */
function snn_get_php_error_type_string($type) {
    switch($type) {
        case E_ERROR: return 'E_ERROR (Fatal run-time error)';
        case E_WARNING: return 'E_WARNING (Run-time warning)';
        case E_PARSE: return 'E_PARSE (Compile-time parse error)';
        case E_NOTICE: return 'E_NOTICE (Run-time notice)';
        case E_CORE_ERROR: return 'E_CORE_ERROR (Fatal error during PHP startup)';
        case E_CORE_WARNING: return 'E_CORE_WARNING (Warning during PHP startup)';
        case E_COMPILE_ERROR: return 'E_COMPILE_ERROR (Fatal compile-time error)';
        case E_COMPILE_WARNING: return 'E_COMPILE_WARNING (Compile-time warning)';
        case E_USER_ERROR: return 'E_USER_ERROR (User-generated error message)';
        case E_USER_WARNING: return 'E_USER_WARNING (User-generated warning message)';
        case E_USER_NOTICE: return 'E_USER_NOTICE (User-generated notice message)';
        case E_STRICT: return 'E_STRICT (Run-time notice for deprecated code or bad practices)';
        case E_RECOVERABLE_ERROR: return 'E_RECOVERABLE_ERROR (Catchable fatal error)';
        case E_DEPRECATED: return 'E_DEPRECATED (Run-time notice for code that will not work in future PHP versions)';
        case E_USER_DEPRECATED: return 'E_USER_DEPRECATED (User-generated warning for deprecated code)';
        default: return "Unknown error type ($type)";
    }
}


/**
 * Display an admin notice if a fatal error occurred and snippets were disabled.
 */
function snn_display_fatal_error_admin_notice() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $fatal_error_details = get_transient( SNN_FATAL_ERROR_NOTICE_TRANSIENT );

    if ( $fatal_error_details && is_array($fatal_error_details) ) {
        ?>
        <div class="notice notice-error is-dismissible snn-fatal-error-notice">
            <p><strong><?php esc_html_e( 'CRITICAL: A code snippet was blocked after a fatal error.', 'snn' ); ?></strong></p>
            <p>
                <?php
                if ( ! empty( $fatal_error_details['slug'] ) ) {
                    printf(
                        /* translators: %s: snippet title */
                        esc_html__( 'The snippet "%s" caused a fatal PHP error and has been blocked so your site keeps loading. Other snippets are unaffected.', 'snn' ),
                        esc_html( snn_snippet_title( $fatal_error_details['slug'] ) )
                    );
                } else {
                    esc_html_e( 'A custom code snippet caused a fatal PHP error, so snippet execution was disabled. This is a safety measure to prevent your site from breaking further.', 'snn' );
                }
                ?>
            </p>
            <p><strong><?php esc_html_e( 'Error Details:', 'snn' ); ?></strong></p>
            <p>
                <code>
                    <?php
                    $type = isset($fatal_error_details['type']) ? $fatal_error_details['type'] : 'Unknown Type';
                    $message = isset($fatal_error_details['message']) ? $fatal_error_details['message'] : 'No message provided.';
                    $file = isset($fatal_error_details['file']) ? $fatal_error_details['file'] : 'Unknown file.';
                    $line = isset($fatal_error_details['line']) ? $fatal_error_details['line'] : 'Unknown line.';
                    echo esc_html( sprintf( "Type: %s\nMessage: %s\nFile: %s\nLine: %d", $type, $message, $file, $line ) );
                    ?>
                </code>
            </p>
            <p>
                <?php
                $logs_url = admin_url( 'admin.php?page=snn-custom-codes-snippets&tab=error_logs' );

                if ( ! empty( $fatal_error_details['slug'] ) ) {
                    // Only this snippet was blocked, so point at its own tab and
                    // say what actually re-enables it: saving a working version.
                    $tab_url = snn_snippet_key_exists( $fatal_error_details['slug'] )
                        ? snn_snippet_edit_url( $fatal_error_details['slug'] )
                        : $logs_url;

                    printf(
                        /* translators: 1: link to the affected snippet tab, 2: link to the error logs tab */
                        wp_kses_post( __( 'Open <a href="%1$s">that snippet</a> and fix the code - saving a working version re-enables it automatically. Global snippet execution was NOT switched off, so your other snippets are still running. Full details are in the <a href="%2$s">Error Logs tab</a>.', 'snn' ) ),
                        esc_url( $tab_url ),
                        esc_url( $logs_url )
                    );
                } else {
                    printf(
                        /* translators: %s: link to the error logs tab */
                        wp_kses_post( __( 'Please review the <a href="%s">Error Logs tab</a> for more details, identify and fix the problematic snippet. Once fixed, you can re-enable "Global Snippet Execution" on the custom code settings page and save.', 'snn' ) ),
                        esc_url( $logs_url )
                    );
                }
                ?>
            </p>
            <p>
                <?php
                printf(
                    /* translators: %s: safe mode URL */
                    wp_kses_post( __( 'If the front end is still broken, add <code>?snn_safe_mode=1</code> to any URL to load the site with every snippet switched off, or set <code>define( \'SNN_CODE_SAFE_MODE\', true );</code> in <code>wp-config.php</code>. The snippets settings page never executes snippets, so it always loads: <a href="%s">open it now</a>.', 'snn' ) ),
                    esc_url( admin_url( 'admin.php?page=snn-custom-codes-snippets' ) )
                );
                ?>
            </p>
             <p><button type="button" class="button snn-dismiss-fatal-notice"><?php esc_html_e('Dismiss This Notice', 'snn'); ?></button></p>
        </div>
        <?php
    }
}
add_action( 'admin_notices', 'snn_display_fatal_error_admin_notice' );


/** * Activation hook: Register CPT, flush rewrite rules, set default options.
 */
function snn_custom_codes_feature_activate() {
    snn_custom_codes_snippets_register_cpt(); // Ensure CPT is registered
    flush_rewrite_rules(); // Important after CPT registration

    if ( false === get_option( 'snn_codes_snippets_enabled', false ) ) {
        update_option( 'snn_codes_snippets_enabled', 0 );
    }
    if ( false === get_option( SNN_CUSTOM_CODES_LOG_OPTION, false ) ) {
        update_option( SNN_CUSTOM_CODES_LOG_OPTION, array() );
    }
}

function snn_custom_codes_feature_deactivate() {
    flush_rewrite_rules();
}

// Recognise a test page load now, at load time, before any snippet can run.
snn_snippet_test_boot();
// Then the fatal handler, so it sees every snippet, the earliest included.
snn_register_fatal_error_handler();

?>
