<?php
/**
 * Compiler parity test: the PHP port must produce exactly what the builder's
 * JS compiler produced for every samples/*.html (expected/*.json, written by
 * export-expected.js).
 *
 *   studio wp eval-file wp-content/themes/<theme>/includes/ai/bricks/tests/run-parity.php [sample-name] [diff]
 *   (or: wp eval-file ...)
 *
 * Exits non-zero when any sample differs, so it can run in CI.
 * Both sides use Bricks' default breakpoints and no colour palette, so the
 * result does not depend on the site's settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$snn_parity_dir  = __DIR__;
$snn_parity_only = '';
$snn_parity_diff = false;
foreach ( (array) ( $args ?? array() ) as $snn_arg ) {
    if ( $snn_arg === '--diff' || $snn_arg === 'diff' ) {
        $snn_parity_diff = true;
    } else {
        $snn_parity_only = preg_replace( '/\.html$/', '', $snn_arg );
    }
}

/** Same rules as normalize() in export-expected.js. */
function snn_parity_normalize( array $result ) {
    $map = array();
    $e   = 0;
    $c   = 0;
    $a   = 0;
    foreach ( $result['content'] as $el ) {
        if ( ! isset( $map[ $el['id'] ] ) ) {
            $map[ $el['id'] ] = 'E' . ( ++$e );
        }
    }
    foreach ( $result['globalClasses'] as $gc ) {
        if ( ! isset( $map[ $gc['id'] ] ) ) {
            $map[ $gc['id'] ] = 'C' . ( ++$c );
        }
    }
    foreach ( $result['content'] as $el ) {
        foreach ( (array) ( $el['settings']['_attributes'] ?? array() ) as $at ) {
            if ( ! empty( $at['_id'] ) && ! isset( $map[ $at['_id'] ] ) ) {
                $map[ $at['_id'] ] = 'A' . ( ++$a );
            }
        }
    }
    $walk = function ( $v ) use ( &$walk, $map ) {
        if ( is_array( $v ) ) {
            $out = array();
            foreach ( $v as $k => $x ) {
                $out[ $k ] = $walk( $x );
            }
            return $out;
        }
        if ( is_string( $v ) && isset( $map[ $v ] ) ) {
            return $map[ $v ];
        }
        return $v;
    };
    return array( 'content' => $walk( $result['content'] ), 'globalClasses' => $walk( $result['globalClasses'] ) );
}

/** JSON the way JSON.stringify(x, null, 2) prints it. */
function snn_parity_json( $value ) {
    $json = wp_json_encode( $value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
    // PHP indents with 4 spaces, JS with 2; empty objects/arrays print as {} / [].
    $json = preg_replace_callback( '/^( +)/m', function ( $m ) {
        return str_repeat( ' ', (int) ( strlen( $m[1] ) / 2 ) );
    }, $json );
    return $json;
}

$snn_plan = SNN_Bricks_Native_Layout::breakpoint_plan( SNN_Bricks_Native_Layout::DEFAULT_BREAKPOINTS );
$snn_pass = 0;
$snn_fail = array();

foreach ( glob( $snn_parity_dir . '/samples/*.html' ) as $snn_file ) {
    $snn_name = basename( $snn_file, '.html' );
    if ( $snn_parity_only !== '' && $snn_name !== $snn_parity_only ) {
        continue;
    }
    $snn_expected_file = $snn_parity_dir . '/expected/' . $snn_name . '.json';
    if ( ! file_exists( $snn_expected_file ) ) {
        echo "SKIP  {$snn_name} (no expected/{$snn_name}.json — run npm run expected)\n";
        continue;
    }
    $snn_result = SNN_Bricks_Html_To_Bricks::compile( file_get_contents( $snn_file ), null, null, array(
        'plan'    => $snn_plan,
        'palette' => array(),
    ) );
    $snn_actual   = snn_parity_normalize( $snn_result );
    $snn_expected = json_decode( file_get_contents( $snn_expected_file ), true );

    // Compare decoded structures: key order, values and types all count.
    $snn_a = snn_parity_json( $snn_actual );
    $snn_b = snn_parity_json( $snn_expected );
    if ( $snn_a === $snn_b ) {
        $snn_pass++;
        echo "PASS  {$snn_name}\n";
        continue;
    }
    $snn_fail[] = $snn_name;
    echo "FAIL  {$snn_name}\n";
    if ( $snn_parity_diff ) {
        $snn_al = explode( "\n", $snn_a );
        $snn_bl = explode( "\n", $snn_b );
        $snn_shown = 0;
        for ( $i = 0, $n = max( count( $snn_al ), count( $snn_bl ) ); $i < $n && $snn_shown < 12; $i++ ) {
            if ( ( $snn_al[ $i ] ?? '' ) !== ( $snn_bl[ $i ] ?? '' ) ) {
                echo '   line ' . ( $i + 1 ) . "\n     js : " . trim( $snn_bl[ $i ] ?? '' ) . "\n     php: " . trim( $snn_al[ $i ] ?? '' ) . "\n";
                $snn_shown++;
            }
        }
    }
}

echo "\n{$snn_pass} passed, " . count( $snn_fail ) . " failed" . ( SNN_Bricks_Dom::has_html5() ? ' (HTML5 parser)' : ' (libxml fallback parser)' ) . "\n";
if ( $snn_fail && class_exists( 'WP_CLI' ) ) {
    WP_CLI::halt( 1 );
}
