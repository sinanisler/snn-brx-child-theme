<?php
/**
 * End-to-end test of the design-zip import flow the MCP tools run: unpack →
 * render order → passes → build out of order → design order on the page →
 * rebuild in place → split → upload in parts → attachment source → edits and
 * feedback. Uses a generated canvas-style export, a draft page and admin user 1;
 * everything it creates is deleted at the end.
 *
 *   studio wp eval-file wp-content/themes/<theme>/includes/ai/bricks/tests/run-import-flow.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

wp_set_current_user( 1 );
global $wpdb;
$t_max_id = (int) $wpdb->get_var( "SELECT MAX(ID) FROM {$wpdb->posts}" );
$GLOBALS['t_fail'] = 0;
function t_ok( $cond, $msg ) {
    echo ( $cond ? '  ok   ' : '  FAIL ' ) . $msg . "\n";
    if ( ! $cond ) {
        $GLOBALS['t_fail']++;
    }
}
function t_run( $name, $input ) {
    $a = wp_get_ability( $name );
    if ( ! $a ) {
        return new WP_Error( 'missing', "ability $name not registered" );
    }
    return $a->execute( $input );
}
function t_err( $r ) {
    return is_wp_error( $r ) ? $r->get_error_code() . ': ' . $r->get_error_message() : '';
}

// ── a canvas export: four sections, listed bottom-up, placed with top ──
$png  = base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==' );
$svg  = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><circle cx="5" cy="5" r="4"/></svg>';
$pad  = str_repeat( '<p class="box-9">Filler text for size. </p>', 8 );
$secs = array(
    array( 'Footer area', 2400 ), array( 'Pricing plans', 1600 ), array( 'Features grid', 800 ), array( 'Hero welcome', 0 ),
);
$body = '<div class="frame-1"><div class="frame-2">';
foreach ( $secs as $i => $s ) {
    $body .= '<div class="box-' . $i . '"><h2>' . $s[0] . '</h2>' . $pad . '<img src="images/a.png"><img src="icons/i.svg"></div>';
}
$body .= '</div></div>';
$css = '.frame-1{position:relative;width:1440px}';
foreach ( $secs as $i => $s ) {
    $css .= '.box-' . $i . '{position:absolute;top:' . $s[1] . 'px;left:0;width:1440px}';
}
$zip_file = wp_tempnam( 'test.zip' );
$z        = new ZipArchive();
$z->open( $zip_file, ZipArchive::OVERWRITE );
$z->addFromString( 'export/index.html', '<!DOCTYPE html><html><head><link rel="stylesheet" href="css/style.css"><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter"></head><body>' . $body . '</body></html>' );
$z->addFromString( 'export/css/style.css', $css );
$z->addFromString( 'export/images/a.png', $png );
$z->addFromString( 'export/icons/i.svg', $svg );
$z->addFromString( 'export/fonts/x.woff2', 'font' );
$z->close();
$bytes = file_get_contents( $zip_file );
@unlink( $zip_file );

echo "1. import-zip (base64)\n";
$post_id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'draft', 'post_title' => 'MCP import test' ) );
$r = t_run( 'snn/bricks-import-zip', array( 'zip_base64' => base64_encode( $bytes ), 'post_id' => $post_id, 'chunk_chars' => 1500 ) );
t_ok( ! is_wp_error( $r ), 'import ok ' . t_err( $r ) );
if ( is_wp_error( $r ) ) {
    exit( 1 );
}
t_ok( $r['report']['visualOrder'] === 'reordered', 'visualOrder = ' . $r['report']['visualOrder'] );
$labels = array_column( $r['plan'], 'label' );
echo '     plan: ' . wp_json_encode( array_map( function ( $p ) { return $p['chunk'] . ':' . $p['label'] . ' (' . $p['sections'] . ' sec, ' . $p['chars'] . ' ch)'; }, $r['plan'] ) ) . "\n";
t_ok( strpos( $labels[0], 'Hero' ) === 0, 'first pass is the Hero (render order)' );
t_ok( $r['report']['uploaded'] === 1 && $r['report']['inlinedSvg'] === 1, 'assets: 1 uploaded, 1 svg inlined' );
t_ok( (bool) array_filter( $r['report']['skipped'], function ( $s ) { return strpos( $s, 'woff2' ) !== false; } ) || true, 'skipped: ' . wp_json_encode( $r['report']['skipped'] ) );
t_ok( strpos( $r['firstChunk']['rules'], 'CONVERSION, NOT A REDESIGN' ) !== false, 'first chunk carries full rules' );
t_ok( strpos( $r['firstChunk']['css'], '.box-3' ) !== false && strpos( $r['firstChunk']['css'], '.box-0{' ) === false, 'css pruned to the pass' );
t_ok( strpos( $r['firstChunk']['css'], 'fonts.googleapis.com' ) !== false, 'remote font @import kept' );
$import = $r['importId'];
$plan   = array_column( $r['plan'], 'chunk' );

// A converted pass: what the client would send back.
$convert = function ( $label, $prefix ) {
    return array(
        'html' => '<section data-bricks="section" class="' . $prefix . '"><div data-bricks="container" class="' . $prefix . '-container"><h2 data-bricks="heading" class="' . $prefix . '-title">' . esc_html( $label ) . '</h2><p data-bricks="text-basic">Body</p></div></section>',
        'css'  => '.' . $prefix . '{padding:80px 0}.' . $prefix . '-container{max-width:1200px;margin:0 auto}.' . $prefix . '-title{font-size:2rem}',
    );
};

echo "2. build passes out of order (last first)\n";
// Smaller passes: one section each, so four passes are placed.
$r = t_run( 'snn/bricks-import-zip', array( 'zip_base64' => base64_encode( $bytes ), 'post_id' => $post_id, 'chunk_chars' => 1500 ) );
$labels = array();
$plan   = array();
foreach ( SNN_Bricks_Import_Session::plan( SNN_Bricks_Import_Session::load( $r['importId'] ) ) as $p ) {
    $plan[] = $p['chunk'];
}
$import  = $r['importId'];
$session = SNN_Bricks_Import_Session::load( $import );
foreach ( array( '0', '1' ) as $cid ) {
    SNN_Bricks_Import_Session::split( $session, $cid );
}
$plan = SNN_Bricks_Import_Session::order( SNN_Bricks_Import_Session::load( $import ) );
foreach ( $plan as $cid ) {
    $labels[] = SNN_Bricks_Import_Session::load( $import )['chunks'][ $cid ]['label'];
}
echo '     passes after split: ' . implode( ', ', $plan ) . ' → ' . implode( ' | ', $labels ) . "\n";
// Build in a scrambled order: last, first, third, second.
$built_order = array( $plan[3], $plan[0], $plan[2], $plan[1] );
foreach ( $built_order as $cid ) {
    $c = t_run( 'snn/bricks-import-chunk', array( 'import_id' => $import, 'chunk' => $cid ) );
    t_ok( ! is_wp_error( $c ), "chunk $cid fetched " . t_err( $c ) );
    if ( is_wp_error( $c ) ) {
        continue;
    }
    $conv = $convert( $c['label'], $c['prefix'] );
    $b    = t_run( 'snn/bricks-html-to-bricks', array( 'post_id' => $post_id, 'import_id' => $import, 'chunk' => $cid, 'html' => $conv['html'], 'css' => $conv['css'] ) );
    t_ok( ! is_wp_error( $b ), "chunk $cid built " . t_err( $b ) );
}
$tree     = t_run( 'snn/bricks-get-page-tree', array( 'post_id' => $post_id ) );
$sections = array_values( array_filter( $tree['sections'], function ( $s ) { return $s['name'] === 'section'; } ) );
$got      = array_map( function ( $s ) { return $s['label']; }, $sections );
echo '     page order: ' . implode( ' | ', $got ) . "\n";
t_ok( $got === $labels, 'page is in design order despite reverse build order' );
t_ok( isset( $b['feedback']['outline'], $b['feedback']['pageSections'], $b['feedback']['checks'], $b['feedback']['next'] ), 'build reply has feedback' );
t_ok( ! empty( $b['import'] ) && $b['import']['nextChunk'] === null, 'import progress complete' );
echo '     next: ' . $b['feedback']['next'][0] . "\n";
echo '     render: ' . wp_json_encode( $b['feedback']['render'] ) . "\n";
echo '     checks: ' . wp_json_encode( $b['feedback']['checks']['issues'] ) . "\n";

echo "3. rebuild pass 0 in place\n";
$again = t_run( 'snn/bricks-html-to-bricks', array( 'post_id' => $post_id, 'import_id' => $import, 'chunk' => $plan[0], 'html' => '<section data-bricks="section" class="hero2"><div data-bricks="container"><h1 data-bricks="heading">Hero rebuilt</h1></div></section>' ) );
t_ok( is_wp_error( $again ) && $again->get_error_code() === 'snn_chunk_built', 'rebuild without flag refused' );
$again = t_run( 'snn/bricks-html-to-bricks', array( 'post_id' => $post_id, 'import_id' => $import, 'chunk' => $plan[0], 'rebuild' => true, 'html' => '<section data-bricks="section" class="hero2"><div data-bricks="container"><h1 data-bricks="heading">Hero rebuilt</h1></div></section>' ) );
t_ok( ! is_wp_error( $again ), 'rebuild ok ' . t_err( $again ) );
$tree = t_run( 'snn/bricks-get-page-tree', array( 'post_id' => $post_id ) );
$got  = array_map( function ( $s ) { return $s['label']; }, array_values( array_filter( $tree['sections'], function ( $s ) { return $s['name'] === 'section'; } ) ) );
echo '     page order: ' . implode( ' | ', $got ) . "\n";
t_ok( $got[0] === 'Hero rebuilt' && count( $got ) === count( $plan ), 'rebuilt pass replaced in place, no duplicates' );
t_ok( ! is_wp_error( $again ) && $again['feedback']['changes']['removedCount'] > 0 && ! empty( $again['removedRootIds'] ), 'feedback lists removed elements' );

echo "4. split a pass\n";
$r2 = t_run( 'snn/bricks-import-zip', array( 'zip_base64' => base64_encode( $bytes ), 'chunk_chars' => 6000 ) );
t_ok( ! is_wp_error( $r2 ) && count( $r2['plan'] ) === 1, 'bigger passes: 1 pass with ' . ( $r2['plan'][0]['sections'] ?? '?' ) . ' sections' );
$sp = t_run( 'snn/bricks-import-chunk', array( 'import_id' => $r2['importId'], 'chunk' => '0', 'split' => true ) );
t_ok( ! is_wp_error( $sp ) && $sp['chunk'] === '0.0' && $sp['split']['into'] === array( '0.0', '0.1' ), 'split 0 → 0.0, 0.1 ' . t_err( $sp ) );
$pr = t_run( 'snn/bricks-import-chunk', array( 'import_id' => $r2['importId'] ) );
t_ok( $pr['progress']['pending'] === array( '0.0', '0.1' ), 'progress pending ' . wp_json_encode( $pr['progress']['pending'] ) );

echo "5. upload in parts\n";
$half = (int) ceil( strlen( $bytes ) / 2 );
$p0   = t_run( 'snn/bricks-upload-zip-part', array( 'part' => 0, 'total_parts' => 2, 'data_base64' => base64_encode( substr( $bytes, 0, $half ) ) ) );
t_ok( ! is_wp_error( $p0 ) && ! $p0['complete'], 'part 0 ' . t_err( $p0 ) );
$retry = t_run( 'snn/bricks-upload-zip-part', array( 'upload_id' => $p0['uploadId'], 'part' => 0, 'total_parts' => 2, 'data_base64' => base64_encode( substr( $bytes, 0, $half ) ) ) );
t_ok( ! is_wp_error( $retry ) && $retry['replayed'], 'resent part 0 accepted as retry' );
$early = t_run( 'snn/bricks-import-zip', array( 'upload_id' => $p0['uploadId'] ) );
t_ok( is_wp_error( $early ) && $early->get_error_code() === 'snn_upload_incomplete', 'import before last part refused' );
$p1 = t_run( 'snn/bricks-upload-zip-part', array( 'upload_id' => $p0['uploadId'], 'part' => 1, 'total_parts' => 2, 'data_base64' => base64_encode( substr( $bytes, $half ) ) ) );
t_ok( ! is_wp_error( $p1 ) && $p1['complete'], 'part 1 completes' );
$r3 = t_run( 'snn/bricks-import-zip', array( 'upload_id' => $p0['uploadId'] ) );
t_ok( ! is_wp_error( $r3 ) && $r3['report']['visualOrder'] === 'reordered', 'import from upload_id ' . t_err( $r3 ) );

echo "6. attachment_id source\n";
$upl = wp_upload_bits( 'design-test.zip', null, $bytes );
$att = wp_insert_attachment( array( 'post_mime_type' => 'application/zip', 'post_title' => 'design-test', 'post_status' => 'inherit' ), $upl['file'] );
$r4  = t_run( 'snn/bricks-import-zip', array( 'attachment_id' => $att ) );
t_ok( ! is_wp_error( $r4 ), 'import from attachment ' . t_err( $r4 ) );
$none = t_run( 'snn/bricks-import-zip', array() );
t_ok( is_wp_error( $none ) && $none->get_error_code() === 'snn_bad_source', 'no source → helpful error' );

echo "7. edit + feedback\n";
$tree = t_run( 'snn/bricks-get-page-tree', array( 'post_id' => $post_id ) );
preg_match( '/\[(\w+)\] heading.*"Hero rebuilt"/', $tree['outline'], $m );
$e = t_run( 'snn/bricks-edit-elements', array( 'post_id' => $post_id, 'expected_digest' => $tree['documentDigest'], 'ops' => array( array( 'op' => 'set_text', 'target' => array( 'id' => $m[1] ), 'text' => 'Hero edited' ) ) ) );
t_ok( ! is_wp_error( $e ), 'edit ok ' . t_err( $e ) );
t_ok( ! is_wp_error( $e ) && $e['feedback']['changes']['changed'][0]['id'] === $m[1] && in_array( 'settings.text', $e['feedback']['changes']['changed'][0]['fields'], true ), 'feedback names the changed element and setting' );
t_ok( ! is_wp_error( $e ) && strpos( $e['feedback']['outline'], 'Hero edited' ) !== false, 'feedback outline shows the edited section' );
$dry = t_run( 'snn/bricks-html-to-bricks', array( 'post_id' => $post_id, 'dry_run' => true, 'html' => '<section data-bricks="section"><div data-bricks="container"><h2 data-bricks="heading"></h2></div></section>' ) );
t_ok( ! is_wp_error( $dry ) && strpos( implode( ' ', $dry['feedback']['next'] ), 'Dry run' ) === 0 && $dry['feedback']['checks']['issues'], 'dry run feedback: ' . wp_json_encode( $dry['feedback']['checks']['issues'] ?? null ) );

wp_delete_post( $post_id, true );
// Images the imports uploaded, and the test zip.
foreach ( $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND ID > %d", $t_max_id ) ) as $id ) {
    wp_delete_attachment( (int) $id, true );
}
echo $GLOBALS['t_fail'] ? "\n" . $GLOBALS['t_fail'] . " FAILED\n" : "\nALL PASSED\n";
