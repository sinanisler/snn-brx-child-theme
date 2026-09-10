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
// Per-snippet on/off switches. Absent key means enabled (back-compat).
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
        'snn-snippet-frontend-head' => __( 'Frontend Head PHP/HTML', 'snn' ),
        'snn-snippet-footer'        => __( 'Frontend Footer PHP/HTML', 'snn' ),
        'snn-snippet-admin-head'    => __( 'Admin Head PHP/HTML', 'snn' ),
        'snn-snippet-functions-php' => __( 'PHP (functions.php)', 'snn' ),
    );
}

/**
 * Title for a single snippet slug, falling back to the slug itself.
 */
function snn_snippet_title( $slug ) {
    $titles = snn_snippet_titles();
    return isset( $titles[ $slug ] ) ? $titles[ $slug ] : $slug;
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
 * Whether a snippet is switched on by the user (ignores error state).
 */
function snn_snippet_is_enabled( $slug ) {
    $map = snn_get_snippet_enabled_map();
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
    // a revision and dismissing the notice must work while a snippet is broken.
    if ( wp_doing_ajax() && isset( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], array( 'snn_snippet_test_finish', 'snn_get_revision_content', 'snn_dismiss_fatal_error_notice' ), true ) ) {
        return true;
    }

    if ( isset( $_GET['snn_safe_mode'] ) && '1' === $_GET['snn_safe_mode'] && current_user_can( 'manage_options' ) ) {
        return true;
    }

    return false;
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

    foreach ( snn_snippet_slugs() as $other ) {
        if ( $other === $slug || ! snn_snippet_is_enabled( $other ) || snn_snippet_get_error( $other ) ) {
            continue;
        }
        $theirs = snn_php_declared_names( snn_get_code_snippet_content( $other ) );
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
function snn_snippet_test_targets_for( $slug ) {
    switch ( $slug ) {
        case 'snn-snippet-functions-php':
            return array( 'admin', 'home_in', 'home_out' );
        case 'snn-snippet-admin-head':
            return array( 'admin' );
        default: // Frontend head and footer.
            return array( 'home_in', 'home_out' );
    }
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
        'token'  => $token,
        'target' => $target,
        'slug'   => (string) $test['slug'],
        'code'   => (string) $test['code'],
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
    $context = snn_snippet_test_context();
    if ( ! $context ) {
        return;
    }

    $fatal = null;
    $error = error_get_last();
    if ( $error && in_array( $error['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR ), true ) ) {
        $error_file   = wp_normalize_path( (string) $error['file'] );
        $from_snippet = ( 0 === strpos( $error_file, wp_normalize_path( __FILE__ ) ) ) && ( false !== strpos( $error_file, "eval()'d code" ) );
        $slug         = snn_snippet_active_slug();
        $executed     = snn_snippet_executed_slugs();
        if ( $from_snippet && '' === $slug && 1 === count( $executed ) ) {
            $slug = $executed[0];
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
    add_submenu_page(
        'snn-settings', // Parent slug
        __( 'Code Snippets', 'snn' ), // Page title
        __( 'Code Snippets', 'snn' ), // Menu title
        'manage_options', // Capability
        'snn-custom-codes-snippets', // Menu slug
        'snn_custom_codes_snippets_page' // Function to display the page
    );
}
add_action( 'admin_menu', 'snn_custom_codes_snippets_add_submenu', 10 );

/**
 * Enqueue CodeMirror assets and add inline JavaScript.
 */
function snn_custom_codes_snippets_enqueue_assets( $hook ) {
    // Determine the correct hook for the snippets page.
    $current_screen = get_current_screen();
    $is_correct_page = false;
    if ($current_screen) {
        $valid_ids = [
            'snn-settings_page_snn-custom-codes-snippets', // Submenu of 'snn-settings'
            'toplevel_page_snn-settings_page_snn-custom-codes-snippets', // If 'snn-settings' is top-level
            'admin_page_snn-custom-codes-snippets' // If added under a generic admin page (less common for add_submenu_page)
        ];
         // Check current screen ID against known valid IDs or the base hook
         if (in_array($current_screen->id, $valid_ids) || $current_screen->base === 'snn-settings_page_snn-custom-codes-snippets') {
             $is_correct_page = true;
         }
    }

    // Fallback check using $hook if $current_screen is not definitive or available early enough
    // This checks if the hook suffix contains our page slug.
    // Also, a more direct check for the page query arg.
    if (!$is_correct_page &&
        (strpos($hook, 'snn-custom-codes-snippets') === false && (!isset($_GET['page']) || $_GET['page'] !== 'snn-custom-codes-snippets'))) {
        return;
    }


    // Enqueue CodeMirror
    $cm_settings = wp_enqueue_code_editor( array( 'type' => 'application/x-httpd-php' ) ); // For PHP
    if ( false === $cm_settings ) {
        // Fallback if CodeMirror can't be initialized (e.g., user preference disabled it)
        wp_enqueue_script('jquery'); // Ensure jQuery is loaded for basic fallback
        return;
    }

    // Enqueue WordPress scripts and styles for the editor
    wp_enqueue_script( 'wp-theme-plugin-editor' );
    wp_enqueue_style( 'wp-codemirror' );
    wp_enqueue_style( 'dashicons' ); // For icons like compare revisions

    // Inline script to initialize CodeMirror on textareas
    wp_add_inline_script(
        'wp-theme-plugin-editor',
        sprintf(
            'jQuery( function( $ ) {
                var editorSettings = %s;
                $( "#snn_frontend_code, #snn_footer_code, #snn_admin_code, #snn_functions_code" ).each( function() {
                    if (wp && wp.codeEditor) { // Check if CodeMirror API is available
                        wp.codeEditor.initialize( this, editorSettings );
                    } else {
                        // Basic styling if CodeMirror fails (e.g. user disabled it in profile)
                        $(this).css({"font-family": "monospace", "font-size": "13px", "border": "1px solid #ddd", "width": "100%%", "padding": "10px"});
                    }
                });
            } );',
            wp_json_encode( $cm_settings )
        )
    );

    // JavaScript for AJAX handling of revisions and notices
    $ajax_nonce = wp_create_nonce( 'snn_preview_revision_nonce' );
    $js_for_revisions = "
jQuery(document).ready(function($) {
    var snn_revisions_vars = {
        ajax_url: '" . esc_url( admin_url( 'admin-ajax.php' ) ) . "',
        nonce: '" . esc_js( $ajax_nonce ) . "',
        loading_text: '" . esc_js(__( 'Loading...', 'snn' )) . "',
        preview_text: '" . esc_js(__( 'Preview in Editor', 'snn' )) . "',
        error_text: '" . esc_js(__( 'Error', 'snn' )) . "',
        ajax_error_text: '" . esc_js(__( 'AJAX error fetching revision.', 'snn' )) . "',
        confirm_restore_text: '" . esc_js(__('Load this revision as a draft and test it? It only goes live if it passes the test; until then the current version keeps running.', 'snn')) . "',
        confirm_clear_revisions_text: '" . esc_js(__('Are you absolutely sure you want to delete all revisions for this snippet? This action cannot be undone.', 'snn')) . "',
        confirm_clear_logs_text: '" . esc_js(__('Are you absolutely sure you want to delete all error logs? This action cannot be undone.', 'snn')) . "'
    };

    // Handle 'Preview in Editor' button click for revisions
    $('body').on('click', '.snn-preview-revision', function(e) {
        e.preventDefault();
        var revisionId = $(this).data('revision-id');
        var button = $(this);
        var originalButtonText = button.text();
        // Get the ID of the currently active editor's textarea from the panel's data attribute
        var activeEditorTextareaId = $('.snn-revisions-panel').data('active-editor-id');

        if (!activeEditorTextareaId) {
            alert('Could not determine active editor. Ensure data-active-editor-id is set on .snn-revisions-panel.');
            return;
        }

        var editorTextarea = $('#' + activeEditorTextareaId);
        var cmInstance = null;

        // Try to get the CodeMirror instance associated with the textarea
        if (editorTextarea.length) {
            if (editorTextarea.get(0).CodeMirror) { // Instance directly on textarea
                cmInstance = editorTextarea.get(0).CodeMirror;
            } else if (editorTextarea.next('.CodeMirror').get(0) && editorTextarea.next('.CodeMirror').get(0).CodeMirror) {
                // Instance on the .CodeMirror wrapper div next to the textarea
                cmInstance = editorTextarea.next('.CodeMirror').get(0).CodeMirror;
            }
        }

        if (!cmInstance) {
            // Fallback if CodeMirror instance isn't found (e.g., editor disabled by user)
            // Update textarea value directly
            button.prop('disabled', true).text(snn_revisions_vars.loading_text);
            $.ajax({
                url: snn_revisions_vars.ajax_url, type: 'POST',
                data: { action: 'snn_get_revision_content', revision_id: revisionId, nonce: snn_revisions_vars.nonce },
                success: function(response) {
                    if (response.success) { editorTextarea.val(response.data.content); }
                    else { alert(snn_revisions_vars.error_text + ': ' + (response.data.message || snn_revisions_vars.ajax_error_text)); }
                },
                error: function() { alert(snn_revisions_vars.ajax_error_text); },
                complete: function() { button.prop('disabled', false).text(originalButtonText); }
            });
            return;
        }

        // If CodeMirror instance is found, use its API
        button.prop('disabled', true).text(snn_revisions_vars.loading_text);

        $.ajax({
            url: snn_revisions_vars.ajax_url, type: 'POST',
            data: { action: 'snn_get_revision_content', revision_id: revisionId, nonce: snn_revisions_vars.nonce },
            success: function(response) {
                if (response.success) {
                    cmInstance.setValue(response.data.content);
                    cmInstance.refresh(); // Refresh CM to show new content
                } else {
                    alert(snn_revisions_vars.error_text + ': ' + (response.data.message || snn_revisions_vars.ajax_error_text));
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                alert(snn_revisions_vars.ajax_error_text + '\\n' + textStatus + ': ' + errorThrown);
            },
            complete: function() { button.prop('disabled', false).text(originalButtonText); }
        });
    });

    // Confirmation for 'Restore & Save' button
    $('body').on('click', '.snn-restore-revision-button', function(e) {
        if (!confirm(snn_revisions_vars.confirm_restore_text)) {
            e.preventDefault(); // Prevent form submission if user cancels
        }
    });

    // Show 'Restore & Save' button when 'Preview in Editor' is clicked
    $('body').on('click', '.snn-preview-revision', function() {
        // Hide all other restore buttons first to prevent multiple showing
        $('.snn-restore-revision-button').hide();
        // Show the restore button specific to this revision item
        $(this).closest('li').find('.snn-restore-revision-button').show();
    });

    // Confirmation for 'Clear All Revisions' button
    $('body').on('click', '.snn-clear-revisions-button', function(e) {
        if (!confirm(snn_revisions_vars.confirm_clear_revisions_text)) {
            e.preventDefault();
        }
    });

    // Confirmation for 'Clear All Error Logs' button
    $('body').on('click', '.snn-clear-error-logs-button', function(e) {
        if (!confirm(snn_revisions_vars.confirm_clear_logs_text)) {
            e.preventDefault();
        }
    });

    // AJAX for dismissing the fatal error admin notice
    $('body').on('click', '.snn-dismiss-fatal-notice', function(e) {
        e.preventDefault();
        var \$button = \$(this);
        $.ajax({
            url: snn_revisions_vars.ajax_url, // Use the global ajax_url
            type: 'POST',
            data: {
                action: 'snn_dismiss_fatal_error_notice',
                nonce: '" . esc_js(wp_create_nonce('snn_dismiss_fatal_notice_nonce')) . "' // Specific nonce for this action
            },
            success: function(response) {
                if (response.success) {
                    \$button.closest('.notice-error.snn-fatal-error-notice').fadeOut(); // Fade out the specific notice
                } else {
                    alert('Could not dismiss notice: ' + (response.data && response.data.message ? response.data.message : 'Unknown error'));
                }
            },
            error: function() {
                alert('AJAX error dismissing notice.');
            }
        });
    });
});
";
    wp_add_inline_script( 'wp-theme-plugin-editor', $js_for_revisions );
}
add_action( 'admin_enqueue_scripts', 'snn_custom_codes_snippets_enqueue_assets' );

/**
 * Add custom CSS to admin head for the snippets page.
 */
function snn_custom_codes_snippets_admin_styles() {
    // Simplified check: If the 'page' GET parameter is 'snn-custom-codes-snippets'
    // and the current user can manage options (basic security check for admin pages).
    if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'snn-custom-codes-snippets' || ! current_user_can('manage_options') ) {
        return; // Exit if not the correct page or insufficient permissions
    }

    // Output the styles
    echo '<style>
        /* General styling for the settings page */
        h3{margin-top:10px} /* Reset margin for h3 if needed */
        th,td{padding:0 !important} /* Reset padding for th,td if needed by theme */
        .CodeMirror { min-height: 600px !important; border: 1px solid #ddd; }
        .snn-snippet-nav-tab-wrapper { margin-bottom: 15px; }
        .snn-snippet-description { margin-bottom: 10px; font-style: italic; color: #555; }
        .form-table th { width: 200px; } /* Consistent width for settings labels */

        /* Flex layout for editor and revisions panel */
        .snn-editor-revision-wrapper { display: flex; flex-wrap: wrap; gap: 20px; margin-top: 5px; }
        .snn-editor-area { flex: 3; min-width: 380px; position: relative; } /* Editor takes more space */
        .snn-revisions-panel {
            flex: 1; /* Revisions panel takes less space */
            min-width: 300px; /* Minimum width before wrapping */
            max-width: 360px; /* Maximum width */
            border-left: 1px solid #ccd0d4; /* Separator line */
            padding-left: 20px;
        }
        .snn-revisions-panel-inner {
            max-height: 680px; /* Max height for scrollbar */
            overflow-y: auto;  /* Enable vertical scrollbar if content exceeds max-height */
            padding-right: 10px; /* Space for scrollbar */
        }
        .snn-revisions-list { list-style: none; margin: 0; padding: 0; }
        .snn-revisions-list li {
            margin-bottom: 0px; /* Reduced from 10px */
            padding-bottom: 5px; /* Reduced from 10px */
            border-bottom: 1px solid #eee;
        }
        .snn-revisions-list li:last-child { border-bottom: none; }
        .snn-revisions-list .revision-info { display: block; font-size: 0.9em; color: #555; margin-bottom: 8px; }
        .snn-revisions-list .revision-actions button,
        .snn-revisions-list .revision-actions .snn-view-comparison-link {
            margin-right: 5px;
            margin-top: 5px; /* Added for spacing */
            vertical-align: middle;
        }
        .snn-revisions-list .revision-actions .snn-view-comparison-link .dashicons {
            font-size: 14px; /* Dashicon size */
            text-decoration: none;
            vertical-align: text-bottom; /* Align with text */
            position: relative; /* For fine-tuning alignment */
            top: 5px; /* Adjusted for better alignment with buttons */
        }
        .snn-revisions-panel h4 { margin-top: 0; font-size: 1.1em; }
        .snn-php-execution-warning { border-left-width: 4px; margin-top: 15px; margin-bottom: 15px; }
        .snn-clear-revisions-button { margin-top: 10px; }
        .snn-manage-revisions-section { margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px; }

        /* Error Logs Table Styling */
        .snn-error-logs-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 13px; }
        .snn-error-logs-table th, .snn-error-logs-table td { border: 1px solid #ddd; padding: 10px !important; text-align: left; vertical-align: top; }
        .snn-error-logs-table th { background-color: #f0f0f1; font-weight: 600; position: sticky; top: 0; }
        .snn-error-logs-table td pre { white-space: pre-wrap; word-wrap: break-word; margin: 0; font-size: 12px; font-family: "Courier New", Courier, monospace; }
        .snn-error-logs-table .snn-log-message { max-width: 400px; overflow-wrap: break-word; }
        .snn-error-logs-table .snn-log-actions { width: 100px; }
        .snn-error-logs-table tr:hover { background-color: #f9f9f9; }
        .snn-error-logs-table code { background: #fff3cd; padding: 2px 6px; border-radius: 3px; font-size: 12px; }
        .snn-error-logs-table details { cursor: pointer; }
        .snn-error-logs-table details summary { color: #2271b1; font-weight: 500; }
        .snn-error-logs-table details summary:hover { color: #135e96; text-decoration: underline; }

        /* Fatal Error Notice Styling (admin notice) */
        .snn-fatal-error-notice strong { color: #dc3232; }
        .snn-fatal-error-notice code { background: #f9f9f9; border: 1px sofully read the code lid #ddd; padding: 2px 4px; font-size: 0.9em; display: block; white-space: pre-wrap; word-break: break-all;}

        /* Styles for fatal error indication on the settings row itself */
        .snn-setting-row-error {
            background-color: #fbeaea !important; /* Light red background */
            border-left: 4px solid #dc3232 !important; /* Red left border */
        }
        .snn-setting-row-error th,
        .snn-setting-row-error td {
            padding-top: 12px !important;
            padding-bottom: 12px !important;
        }
        .snn-setting-row-error td .description { /* Style for the error message text below checkbox */
            color: #c00 !important;
            font-weight: bold !important;
            margin-top: 5px !important;
        }
        .snn-setting-row-error label { /* Ensure label text is clearly visible */
             color: #333;
        }

        /* Draft / test-before-publish notices */
        .snn-draft-notice pre { max-height: 300px; overflow: auto; background: #f6f7f7; border: 1px solid #dcdcde; padding: 10px; font-size: 12px; white-space: pre-wrap; }
        .snn-draft-notice details { margin: 8px 0; }
        .snn-draft-notice .spinner { float: none; margin: 0 6px; }
    </style>';
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

    // Get snippet title for better readability
    $snippet_titles = array(
        'snn-snippet-frontend-head' => 'Frontend Head PHP/HTML',
        'snn-snippet-footer' => 'Frontend Footer PHP/HTML',
        'snn-snippet-admin-head' => 'Admin Head PHP/HTML',
        'snn-snippet-functions-php' => 'PHP (functions.php)',
    );
    $snippet_title = isset( $snippet_titles[ $snippet_slug ] ) ? $snippet_titles[ $snippet_slug ] : $snippet_slug;

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

        if ( snn_snippet_test_context() ) {
            snn_snippet_test_log( array(
                'kind'    => 'warning',
                'type'    => $error_type_str,
                'message' => $errstr,
                'line'    => (int) $errline,
                'slug'    => $snippet_location_slug,
            ) );
            return true;
        }

        $code_context = snn_get_code_context( $code_to_execute, $errline );
        $function_context = snn_get_function_context( $code_to_execute, $errline );

        snn_log_error_event($error_type_str, $errstr, $snippet_location_slug, 'eval()\'d code (runtime)', $errline, $code_context, $function_context);
        return true; // Prevent default PHP error handler from running
    });

    $fatal_thrown = false;
    $ob_level     = ob_get_level();
    ob_start(); // Start output buffering

    try {
        // The "? >" before $code_to_execute ensures that if the code doesn't start with <?php, it's treated as HTML.
        // No @ here: it would neuter error_reporting() inside the handler above and
        // silence every warning the snippet raises.
        eval( "?>" . $code_to_execute );
    } catch (Throwable $e) { // ParseError, Error, Exception
        $error_line       = $e->getLine();
        $code_context     = snn_get_code_context( $code_to_execute, $error_line );
        $function_context = snn_get_function_context( $code_to_execute, $error_line );
        $type             = ( $e instanceof ParseError ) ? 'PHP Parse Error' : get_class( $e );

        if ( snn_snippet_test_context() ) {
            // Test page load: report to the test run instead of logging and blocking.
            snn_snippet_test_log( array(
                'kind'    => ( $e instanceof Error ) ? 'error' : 'exception',
                'type'    => $type,
                'message' => $e->getMessage(),
                'line'    => (int) $error_line,
                'slug'    => $snippet_location_slug,
            ) );
            $fatal_thrown = ( $e instanceof Error );
        } else {
            snn_log_error_event(
                $type,
                $e->getMessage(),
                $snippet_location_slug,
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
                    $snippet_location_slug,
                    $type,
                    $e->getMessage() . ( $function_context ? ' [' . $function_context . ']' : '' ),
                    $error_line
                );

                set_transient( SNN_FATAL_ERROR_NOTICE_TRANSIENT, array(
                    'message' => $e->getMessage() . ( $function_context ? ' [' . $function_context . ']' : '' ),
                    'file'    => 'Tab: ' . snn_snippet_title( $snippet_location_slug ),
                    'line'    => $error_line,
                    'type'    => $type,
                    'slug'    => $snippet_location_slug,
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
    if ( ! in_array( $slug, snn_snippet_slugs(), true ) ) {
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
        'file'    => 'Tab: ' . snn_snippet_title( $slug ),
        'line'    => 0,
        'type'    => 'Fatal Error (crash guard)',
        'slug'    => $slug,
    ), DAY_IN_SECONDS );
}

/**
 * Applies a save-time syntax check result: block the snippet and explain why, or
 * clear a previous block now that valid code has been saved.
 *
 * The code is already stored either way - a failed check never costs the user
 * their work, it only stops the broken version from running.
 *
 * @param string     $slug   Snippet slug.
 * @param string     $title  Human readable snippet title.
 * @param true|array $syntax Result of snn_check_php_syntax().
 * @param string     $code   The code that was checked, for error context.
 */
function snn_apply_syntax_check_result( $slug, $title, $syntax, $code ) {
    if ( ! is_array( $syntax ) ) {
        // Parses cleanly. Lift any previous block (parse error or past crash) and
        // let the runtime crash guard re-arm itself against the new content.
        if ( snn_snippet_clear_error( $slug ) ) {
            add_settings_error(
                'snn-custom-codes',
                'snippet_unblocked_' . $slug,
                sprintf( __( '"%s" parses cleanly and has been re-enabled.', 'snn' ), esc_html( $title ) ),
                'updated'
            );
        }
        return;
    }

    snn_log_error_event(
        'PHP Parse Error (save-time check)',
        $syntax['message'],
        $slug,
        'Save-time syntax check',
        $syntax['line'],
        snn_get_code_context( $code, $syntax['line'] ),
        snn_get_function_context( $code, $syntax['line'] )
    );

    snn_snippet_record_error( $slug, 'Parse Error', $syntax['message'], $syntax['line'] );

    add_settings_error(
        'snn-custom-codes',
        'syntax_error_' . $slug,
        sprintf(
            /* translators: 1: snippet title, 2: PHP error message, 3: line number */
            __( '"%1$s" was saved but will NOT run: %2$s (line %3$d). Fix the error and save again to re-enable it.', 'snn' ),
            esc_html( $title ),
            esc_html( $syntax['message'] ),
            absint( $syntax['line'] )
        ),
        'error'
    );
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
 * @return true|WP_Error
 */
function snn_snippet_publish( $def, $post_id, $code, $switch_on ) {
    $result = wp_update_post( array(
        'ID'           => $post_id,
        'post_title'   => $def['title'],
        // wp_update_post() expects slashed input and unslashes internally.
        // Passing unslashed code ate one level of backslashes on every save,
        // silently turning \WP_Query into WP_Query and '/\d+/' into '/d+/'.
        'post_content' => wp_slash( $code ),
    ), true );
    if ( is_wp_error( $result ) ) {
        return $result;
    }

    snn_snippet_delete_draft( $post_id );
    snn_snippet_clear_error( $def['slug'] );
    if ( $switch_on ) {
        snn_snippet_set_enabled( $def['slug'], true );
    }
    return true;
}

/**
 * Keep code as a draft and open a test run for it. The admin page then loads
 * the test pages from the browser - after this request has finished, so it
 * works on servers that handle one request at a time - and asks the server
 * to publish once every page has reported back.
 */
function snn_snippet_start_test( $slug, $post_id, $code, $switch_on ) {
    snn_snippet_delete_draft( $post_id ); // Voids an earlier run's token.

    $token = wp_generate_password( 32, false, false );
    set_transient( SNN_SNIPPET_TEST_TRANSIENT . $token, array(
        'slug'    => $slug,
        'code'    => $code,
        'hash'    => md5( $code ),
        'user'    => get_current_user_id(),
        'targets' => snn_snippet_test_targets_for( $slug ),
        'results' => array(),
        'expires' => time() + SNN_SNIPPET_TEST_TTL,
    ), SNN_SNIPPET_TEST_TTL );

    snn_snippet_save_draft( $post_id, array(
        'code'      => $code,
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
function snn_snippet_fail_draft( $slug, $post_id, $code, $switch_on, $error ) {
    snn_snippet_delete_draft( $post_id );
    snn_snippet_save_draft( $post_id, array(
        'code'      => $code,
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
 * @param array     $def        Snippet definition (slug, title).
 * @param string    $code       Submitted code, unslashed.
 * @param bool|null $desired_on The "Run this snippet" checkbox, or null if not submitted.
 */
function snn_snippet_process_save( $def, $code, $desired_on ) {
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

    $live    = snn_get_code_snippet_content( $slug );
    $changed = ( $code !== $live );
    $runs    = ( $is_on || $switch_on ) && snn_snippets_execution_possible();
    $blocked = (bool) snn_snippet_get_error( $slug );

    // Nothing new to publish, nothing to switch on, no block to retry: drop any
    // stale draft (the editor was reverted to the live code) and stop here.
    if ( ! $changed && ! $switch_on && ! ( $runs && $blocked ) ) {
        snn_snippet_delete_draft( $post_id );
        return;
    }

    $empty = ( '' === trim( $code ) );
    $check = $empty ? true : snn_snippet_static_check( $code, $slug );
    if ( true !== $check ) {
        snn_snippet_fail_draft( $slug, $post_id, $code, $switch_on, $check );
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
        $published = snn_snippet_publish( $def, $post_id, $code, $switch_on );
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
        } elseif ( ! $is_on && ! $switch_on ) {
            /* translators: %s: snippet title */
            add_settings_error( 'snn-custom-codes', 'saved_' . $slug, sprintf( __( '"%s" saved. It is switched off, so it was not test-loaded; switching it on will test it first.', 'snn' ), esc_html( $title ) ), 'updated' );
        } else {
            /* translators: %s: snippet title */
            add_settings_error( 'snn-custom-codes', 'saved_' . $slug, sprintf( __( '"%s" saved. Snippet execution is off (globally or by a constant), so it was not test-loaded.', 'snn' ), esc_html( $title ) ), 'updated' );
        }
        return;
    }

    snn_snippet_start_test( $slug, $post_id, $code, $switch_on );
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
function snn_snippet_render_draft_notice( $key, $def, $draft ) {
    $live = snn_get_code_snippet_content( $def['slug'] );
    $test = ( 'pending' === $draft['status'] && ! empty( $draft['token'] ) ) ? get_transient( SNN_SNIPPET_TEST_TRANSIENT . $draft['token'] ) : false;

    if ( is_array( $test ) ) {
        $defs    = snn_snippet_test_target_defs();
        $targets = array();
        foreach ( $test['targets'] as $target ) {
            $targets[] = array(
                'label'   => $defs[ $target ]['label'],
                'url'     => add_query_arg( array(
                    'snn_snippet_test'   => $draft['token'],
                    'snn_snippet_target' => $target,
                ), $defs[ $target ]['url'] ),
                'cookies' => $defs[ $target ]['cookies'],
            );
        }
        $config = array(
            'targets'   => $targets,
            'token'     => $draft['token'],
            'slug'      => $def['slug'],
            'nonce'     => wp_create_nonce( 'snn_snippet_test_finish' ),
            'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
            'returnUrl' => admin_url( 'admin.php?page=snn-custom-codes-snippets&tab=' . $key ),
            'timeout'   => 60000,
            'i18n'      => array(
                /* translators: %s: test page, e.g. "Front end (logged out)" */
                'testing'  => __( 'Loading: %s', 'snn' ),
                'checking' => __( 'Checking the results…', 'snn' ),
                'failed'   => __( 'Could not reach the site to finish the test. Reload this page, or save again to test again.', 'snn' ),
            ),
        );
        ?>
        <div class="notice notice-info inline snn-draft-notice" id="snn-snippet-test-runner" data-config="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">
            <p><strong><?php esc_html_e( 'Testing your changes before they go live…', 'snn' ); ?></strong><span class="spinner is-active"></span><span class="snn-test-status"></span></p>
            <p><?php esc_html_e( 'Your site is being loaded in the background with this draft. The current version keeps running until the test passes. Keep this page open; it reloads with the result.', 'snn' ); ?></p>
            <noscript><p><?php esc_html_e( 'Testing needs JavaScript. Until it runs, these changes stay unpublished.', 'snn' ); ?></p></noscript>
            <?php snn_snippet_render_draft_footer( $key, $live ); ?>
        </div>
        <script>
        ( function () {
            var box = document.getElementById( 'snn-snippet-test-runner' );
            if ( ! box || ! window.fetch ) {
                return;
            }
            var cfg    = JSON.parse( box.getAttribute( 'data-config' ) );
            var status = box.querySelector( '.snn-test-status' );
            var index  = 0;

            // One page at a time: some servers handle a single request at once.
            // Only what each page load records on the server counts, so the
            // responses are ignored and failures simply move on.
            function next() {
                if ( index >= cfg.targets.length ) {
                    finish();
                    return;
                }
                var target     = cfg.targets[ index++ ];
                var controller = window.AbortController ? new AbortController() : null;
                var timer      = controller ? setTimeout( function () { controller.abort(); }, cfg.timeout ) : null;
                status.textContent = cfg.i18n.testing.replace( '%s', target.label );
                fetch( target.url, {
                    credentials: target.cookies ? 'include' : 'omit',
                    cache: 'no-store',
                    redirect: 'manual',
                    signal: controller ? controller.signal : undefined
                } ).catch( function () {} ).then( function () {
                    if ( timer ) {
                        clearTimeout( timer );
                    }
                    next();
                } );
            }

            function finish() {
                status.textContent = cfg.i18n.checking;
                var body = new FormData();
                body.append( 'action', 'snn_snippet_test_finish' );
                body.append( 'nonce', cfg.nonce );
                body.append( 'token', cfg.token );
                body.append( 'slug', cfg.slug );
                fetch( cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } )
                    .then( function ( response ) { return response.json(); } )
                    .then( function () { window.location.href = cfg.returnUrl; } )
                    .catch( function () { status.textContent = cfg.i18n.failed; } );
            }

            next();
        } )();
        </script>
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

    // Check for the emergency disable constant
    $is_disabled_by_constant = defined( 'SNN_CODE_DISABLE' ) && SNN_CODE_DISABLE;

    // Definitions for each snippet location
    $snippet_defs = array(
        'frontend' => array(
            'title'       => __( 'Frontend Head PHP/HTML', 'snn' ),
            'slug'        => 'snn-snippet-frontend-head', // Used as post_name and for retrieval
            'field_id'    => 'snn_frontend_code', // HTML ID for textarea
            'description' => __( 'PHP code or HTML executed within the <code>&lt;head&gt;</code> tags on the frontend. Use for dynamic meta tags, conditional CSS/JS links, etc. You can use <code>&lt;?php ?&gt;</code> tags for PHP code.', 'snn' ),
        ),
        'footer'   => array(
            'title'       => __( 'Frontend Footer PHP/HTML', 'snn' ),
            'slug'        => 'snn-snippet-footer',
            'field_id'    => 'snn_footer_code',
            'description' => __( 'PHP code or HTML executed before the <code>&lt;/body&gt;</code> tag on the frontend. Use for late-loading dynamic content, analytics, etc. You can use <code>&lt;?php ?&gt;</code> tags for PHP code.', 'snn' ),
        ),
        'admin'    => array(
            'title'       => __( 'Admin Head PHP/HTML', 'snn' ),
            'slug'        => 'snn-snippet-admin-head',
            'field_id'    => 'snn_admin_code',
            'description' => __( 'PHP code or HTML executed within the <code>&lt;head&gt;</code> of WordPress admin pages. Use for conditional admin CSS/JS, admin modifications, etc. You can use <code>&lt;?php ?&gt;</code> tags for PHP code.', 'snn' ),
        ),
        'functions' => array(
            'title'       => __( 'PHP (functions.php)', 'snn' ),
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

        // Save global enable/disable setting and all snippet contents
        if ( isset($_POST['snn_save_all_settings_button']) ) {
            // Main snippet execution setting
            $is_enabled = isset( $_POST['snn_codes_snippets_enabled'] ) ? 1 : 0;
            update_option( 'snn_codes_snippets_enabled', $is_enabled );

            if ($is_enabled) {
                delete_transient(SNN_FATAL_ERROR_NOTICE_TRANSIENT);
                // The admin has deliberately switched execution back on. Start the
                // unattributed-fatal streak from zero so a count left over from the
                // previous breakage does not trip the kill switch prematurely.
                update_option( SNN_UNATTRIBUTED_FATAL_OPTION, 0, true );
            }

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
    <div class="wrap">
        <h1> <?php esc_html_e( 'Manage Code Snippets', 'snn' ); ?> </h1>

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

            <table class="form-table" role="presentation">
                <tbody>
                    <?php
                    $fatal_error_occurred = (bool) get_transient(SNN_FATAL_ERROR_NOTICE_TRANSIENT);
                    $row_class = ( ! $enabled_globally && $fatal_error_occurred ) ? 'snn-setting-row-error' : '';
                    ?>
                    <tr class="<?php echo esc_attr( $row_class ); ?>">
                        <th scope="row"><?php esc_html_e( 'Global Snippet Execution', 'snn' ); ?></th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text"><span><?php esc_html_e( 'Global Snippet Execution', 'snn' ); ?></span></legend>
                                <label for="snn_codes_snippets_enabled">
                                    <input type="checkbox" id="snn_codes_snippets_enabled" name="snn_codes_snippets_enabled" value="1"
                                        <?php checked( 1, $enabled_globally ); ?>
                                    >
                                    <?php esc_html_e( 'Enable execution of all custom PHP snippets', 'snn' ); ?>
                                </label>
                                <?php if ( ! $enabled_globally && $fatal_error_occurred ) : ?>
                                    <p class="description">
                                        <?php esc_html_e( 'Execution was automatically disabled due to a fatal error. Please check the Error Logs tab, resolve the issue, then re-check this box and save settings to re-enable.', 'snn' ); ?>
                                    </p>
                                <?php elseif ( ! $enabled_globally ) : ?>
                                     <p class="description">
                                        <?php esc_html_e( 'Snippet execution is currently disabled. Check this box and save settings to enable.', 'snn' ); ?>
                                    </p>
                                <?php endif; ?>
                            </fieldset>
                        </td>
                    </tr>
                </tbody>
            </table>

            <h2 class="nav-tab-wrapper snn-snippet-nav-tab-wrapper">
                <?php
                foreach ( $snippet_defs as $key => $def ) {
                    $active_class = ( $current_tab_key === $key ) ? 'nav-tab-active' : '';
                    $tab_url = admin_url( 'admin.php?page=snn-custom-codes-snippets&tab=' . $key );
                    $tab_label = $def['title'] . ( ! empty( $drafts[ $key ] ) ? ' ' . __( '(draft)', 'snn' ) : '' );
                    echo '<a href="' . esc_url( $tab_url ) . '" class="nav-tab ' . esc_attr( $active_class ) . '">' . esc_html( $tab_label ) . '</a>';
                }
                // Add Error Logs tab link
                $logs_tab_active_class = ( $current_tab_key === 'error_logs' ) ? 'nav-tab-active' : '';
                $logs_tab_url = admin_url( 'admin.php?page=snn-custom-codes-snippets&tab=error_logs' );
                echo '<a href="' . esc_url( $logs_tab_url ) . '" class="nav-tab ' . esc_attr( $logs_tab_active_class ) . '">' . esc_html__( 'Error Logs', 'snn' ) . '</a>';
                ?>
            </h2>

            <?php if ( $current_tab_key === 'error_logs' ) : // Display Error Logs Tab Content ?>
            <div id="snn-tab-content-error-logs" class="snn-tab-content">
                <h3><?php esc_html_e( 'Snippet Execution Error Logs', 'snn' ); ?></h3>
                <p><?php printf(esc_html__( 'This log shows the last %d errors recorded from snippet executions. If a fatal error occurs, snippet execution will be globally disabled.', 'snn' ), SNN_CUSTOM_CODES_MAX_LOG_ENTRIES); ?></p>
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
                            <h3><?php echo esc_html( $active_snippet_def['title'] ); ?></h3>
                            <p class="snn-snippet-description"><?php echo wp_kses_post( $active_snippet_def['description'] ); ?></p>
                             <?php if ( $active_snippet_def['slug'] === 'snn-snippet-functions-php' ): ?>
                                <div class="notice notice-warning inline snn-php-execution-warning">
                                    <p><strong><?php esc_html_e('Warning:', 'snn'); ?></strong> <?php esc_html_e('Code in this section runs like functions.php. Errors here can easily break your site. Test thoroughly!', 'snn'); ?></p>
                                </div>
                            <?php endif; ?>

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
                <?php submit_button( __( 'Save All Snippets & Settings', 'snn' ), 'primary large', 'snn_save_all_settings_button' ); ?>
            <?php endif; ?>

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
    if ( 32 !== strlen( $token ) || ! in_array( $slug, snn_snippet_slugs(), true ) ) {
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

    if ( ! $verdict['problems'] ) {
        $published = snn_snippet_publish( $def, $post_id, $draft['code'], $switch_on );
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
    snn_snippet_fail_draft( $slug, $post_id, $draft['code'], $switch_on, $problem );
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
 * Register fatal error shutdown handler.
 */
function snn_register_fatal_error_handler() {
    register_shutdown_function( 'snn_fatal_error_shutdown_handler' );
}
add_action( 'init', 'snn_register_fatal_error_handler', 1 );

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

    // Attribution, best evidence first.
    $slug     = snn_snippet_active_slug();
    $executed = snn_snippet_executed_slugs();

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
    } else {
        // Certainly a snippet, but more than one ran and none was mid-eval.
        // Fall back to the global switch rather than block the wrong snippet.
        update_option( 'snn_codes_snippets_enabled', 0 );
        snn_snippet_fatal_attributed( true );
    }

    set_transient( SNN_FATAL_ERROR_NOTICE_TRANSIENT, array(
        'message' => $error['message'],
        'file'    => '' !== $slug ? 'Tab: ' . snn_snippet_title( $slug ) : $error['file'],
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
                    $tab_key = array_search( $fatal_error_details['slug'], snn_snippet_slugs(), true );
                    $tab_url = $tab_key
                        ? admin_url( 'admin.php?page=snn-custom-codes-snippets&tab=' . $tab_key )
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

?>
