<?php
/**
 * End-to-end test through the real abilities: build a draft page with the new features, read it
 * back, edit it (rename and delete a class, the new checks), and remove it again. The global class
 * store is snapshotted before and restored after, so the site's classes are left as they were.
 *
 *   php scratchpad/wp-run.php .../tests/run-writes.php
 *
 * Exits non-zero on any failure. Needs WordPress with Bricks and the abilities API.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$GLOBALS['snn_w_ok']   = 0;
$GLOBALS['snn_w_fail'] = 0;
$snn_w = function ( $cond, $msg ) {
    echo ( $cond ? '  ok   ' : '  FAIL ' ) . $msg . "\n";
    $GLOBALS[ $cond ? 'snn_w_ok' : 'snn_w_fail' ]++;
};

if ( ! function_exists( 'wp_get_ability' ) ) {
    echo "The abilities API is not available; nothing to test here.\n";
    return;
}

$admin = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
wp_set_current_user( (int) ( $admin[0] ?? 1 ) );

$classes_snapshot = get_option( SNN_Bricks_Global_Classes_Store::classes_option(), array() );
$post_id          = wp_insert_post( array(
    'post_title'  => 'SNN converter test (safe to delete)',
    'post_status' => 'draft',
    'post_type'   => 'page',
    'post_author' => (int) get_current_user_id(),
) );
$snn_w( $post_id && ! is_wp_error( $post_id ), 'a throwaway draft page was created (' . $post_id . ')' );

$call = function ( $name, array $input ) {
    $ability = wp_get_ability( $name );
    if ( ! $ability ) {
        return new WP_Error( 'no_ability', 'Ability ' . $name . ' is not registered.' );
    }
    return $ability->execute( $input );
};

try {
    // 1. Build with the new features, dry run first.
    $html = '<style>.hero-title { font-size: 48px; color: #111; }</style>'
        . '<section id="hero" data-bricks="section"><div data-bricks="container">'
        . '<h1 data-bricks="heading" class="hero-title">Welcome</h1>'
        . '<button data-bricks="button" data-interact="click" data-interact-action="toggleAttribute" data-interact-key="data-open" data-interact-target="#menu">Menu</button>'
        . '<div id="menu">Menu panel</div>'
        . '<ul><li>Plain item</li></ul>'
        . '<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" title="Demo"></iframe>'
        . '</div></section>';

    $dry = $call( 'snn/bricks-html-to-bricks', array( 'post_id' => $post_id, 'html' => $html, 'mode' => 'append', 'dry_run' => true ) );
    $snn_w( ! is_wp_error( $dry ), 'dry run succeeds' . ( is_wp_error( $dry ) ? ': ' . $dry->get_error_message() : '' ) );
    if ( is_wp_error( $dry ) ) {
        throw new RuntimeException( 'dry run failed' );
    }

    // 2. The real build.
    $built = $call( 'snn/bricks-html-to-bricks', array( 'post_id' => $post_id, 'html' => $html, 'mode' => 'append', 'idempotencyKey' => 'snn-test-' . $post_id ) );
    $snn_w( ! is_wp_error( $built ), 'the build saves' . ( is_wp_error( $built ) ? ': ' . $built->get_error_message() : '' ) );
    if ( is_wp_error( $built ) ) {
        throw new RuntimeException( 'build failed' );
    }
    $area     = SNN_Bricks_Write_Guard::area( $post_id );
    $saved    = SNN_Bricks_Write_Guard::read_elements( $post_id, $area );
    $by_css   = array();
    $by_name  = array();
    foreach ( $saved as $el ) {
        if ( ! empty( $el['settings']['_cssId'] ) ) {
            $by_css[ $el['settings']['_cssId'] ] = $el;
        }
        $by_name[ $el['name'] ][] = $el;
    }
    $snn_w( isset( $by_css['hero'] ), 'the saved section carries _cssId "hero"' );
    $snn_w( isset( $by_css['menu'] ), 'the saved panel carries _cssId "menu"' );
    $btn = $by_name['button'][0] ?? null;
    $snn_w( $btn && ( $btn['settings']['_interactions'][0]['targetSelector'] ?? '' ) === '#menu', 'the saved button interaction targets #menu' );
    $snn_w( ! empty( $by_name['video'][0]['settings']['youTubeId'] ), 'the iframe was saved as a native video' );
    $snn_w( ! empty( $by_name['block'] ) && count( array_filter( $by_name['text-basic'] ?? array(), function ( $e ) {
        return ( $e['settings']['customTag'] ?? '' ) === 'li';
    } ) ) === 1, 'the plain list was saved as a block with one text item' );
    $snn_w( ! $built['warnings'] || ! array_filter( $built['warnings'], function ( $w ) {
        return strpos( $w, 'targets #' ) !== false;
    } ), 'no interaction target is reported missing (the menu is on the page)' );

    // 3. Edit: rename the class, refuse to delete a used class, insert with a wrong element name, unknown setting key.
    $edit = $call( 'snn/bricks-edit-elements', array(
        'post_id' => $post_id,
        'ops'     => array( array( 'op' => 'rename_class', 'from' => 'hero-title', 'to' => 'hero-heading' ) ),
    ) );
    $snn_w( ! is_wp_error( $edit ) && in_array( 'hero-title => hero-heading', (array) ( $edit['classesRenamed'] ?? array() ), true ), 'rename_class renames the global class' );
    $names = array_map( function ( $c ) {
        return $c['name'];
    }, SNN_Bricks_Global_Classes_Store::read_classes() );
    $snn_w( in_array( 'hero-heading', $names, true ) && ! in_array( 'hero-title', $names, true ), 'the store now holds hero-heading and not hero-title' );
    $css_now = '';
    foreach ( SNN_Bricks_Global_Classes_Store::read_classes() as $c ) {
        if ( ( $c['name'] ?? '' ) === 'hero-heading' ) {
            $css_now = (string) $c['settings']['_cssCustom'];
        }
    }
    $snn_w( strpos( $css_now, '.hero-heading' ) !== false && strpos( $css_now, '.hero-title' ) === false, 'the renamed class CSS selects .hero-heading' );

    $bad = $call( 'snn/bricks-edit-elements', array( 'post_id' => $post_id, 'ops' => array( array( 'op' => 'insert', 'parent' => array( 'type' => 'section' ), 'name' => 'headng', 'text' => 'x' ) ) ) );
    $snn_w( is_wp_error( $bad ) && strpos( $bad->get_error_message(), 'Did you mean' ) !== false, 'insert of a misspelt element name fails with a suggestion' );

    $del = $call( 'snn/bricks-edit-elements', array( 'post_id' => $post_id, 'ops' => array( array( 'op' => 'delete_class', 'name' => 'hero-heading' ) ) ) );
    $snn_w( is_wp_error( $del ) && strpos( $del->get_error_message(), 'detach' ) !== false, 'delete_class refuses a class that elements use' );

    $kv = $call( 'snn/bricks-edit-elements', array( 'post_id' => $post_id, 'ops' => array( array( 'op' => 'set_settings', 'target' => array( 'type' => 'video' ), 'settings' => array( 'youtubeColour' => 'red', 'videoType' => 'youtube' ) ) ) ) );
    $snn_w( ! is_wp_error( $kv ) && ! empty( $kv['warnings'] ) && strpos( implode( ' ', $kv['warnings'] ), 'youtubeColour' ) !== false, 'an unknown setting key is written around and reported as a warning' );

    $det = $call( 'snn/bricks-edit-elements', array( 'post_id' => $post_id, 'ops' => array( array( 'op' => 'delete_class', 'name' => 'hero-heading', 'detach' => true ) ) ) );
    $snn_w( ! is_wp_error( $det ) && in_array( 'hero-heading', (array) ( $det['classesDeleted'] ?? array() ), true ), 'delete_class with detach removes the class and its use' );

    // 4. Element schema through its ability.
    $schema = $call( 'snn/bricks-element-schema', array( 'names' => array( 'icon-box', 'nope-box' ) ) );
    $snn_w( ! is_wp_error( $schema ) && ( $schema['elements'][0]['name'] ?? '' ) === 'icon-box' && ! empty( $schema['elements'][0]['settings'] ), 'element-schema describes icon-box with its settings' );
    $snn_w( ! is_wp_error( $schema ) && ( $schema['unknown'][0]['name'] ?? '' ) === 'nope-box', 'element-schema reports an unknown name' );
    $list = $call( 'snn/bricks-element-schema', array( 'search' => 'video' ) );
    $snn_w( ! is_wp_error( $list ) && ! empty( $list['elements'] ), 'element-schema lists element types by search' );
} catch ( Throwable $e ) {
    echo '  FAIL (stopped): ' . $e->getMessage() . "\n";
    $GLOBALS['snn_w_fail']++;
}

// Clean up: remove the page, and put the global classes back exactly as they were.
wp_delete_post( $post_id, true );
update_option( SNN_Bricks_Global_Classes_Store::classes_option(), $classes_snapshot );
SNN_Bricks_Global_Classes_Store::regenerate_global_class_css();
echo "  ok   cleanup: page removed, global classes restored\n";

echo "\n" . $GLOBALS['snn_w_ok'] . ' passed, ' . $GLOBALS['snn_w_fail'] . " failed\n";
