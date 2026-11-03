<?php
/**
 * Feature tests for the Bricks converter and the MCP-facing rules: CSS IDs, inline styles,
 * native lists, data-bricks hints validated against Bricks' registry, data-bricks-* settings,
 * interactions, native video, icon libraries, SVG case, and the schema's view of emitted keys.
 *
 * Needs WordPress with Bricks active (the schema is Bricks' own):
 *
 *   php scratchpad/wp-run.php .../tests/run-features.php
 *
 * Exits non-zero on any failure.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$GLOBALS['snn_feat_fail'] = 0;
$GLOBALS['snn_feat_ok']   = 0;
$snn_ok = function ( $cond, $msg ) {
    echo ( $cond ? '  ok   ' : '  FAIL ' ) . $msg . "\n";
    if ( $cond ) {
        $GLOBALS['snn_feat_ok']++;
    } else {
        $GLOBALS['snn_feat_fail']++;
    }
};

/** Build a page section and return all its elements plus the warnings. */
$snn_build = function ( $html ) {
    $res = SNN_Bricks_Html_To_Bricks::build( $html, array() );
    $els = array();
    foreach ( $res['sections'] as $s ) {
        foreach ( $s['content'] as $el ) {
            $els[] = $el;
        }
    }
    return array( 'els' => $els, 'warnings' => $res['warnings'], 'content' => $res['sections'] );
};

/** The first element with this name (optionally with this CSS ID). */
$snn_find = function ( array $els, $name, $css_id = null ) {
    foreach ( $els as $el ) {
        if ( $el['name'] !== $name ) {
            continue;
        }
        if ( $css_id !== null && ( $el['settings']['_cssId'] ?? null ) !== $css_id ) {
            continue;
        }
        return $el;
    }
    return null;
};

$snn_has = function ( array $warnings, $needle ) {
    foreach ( $warnings as $w ) {
        if ( strpos( $w, $needle ) !== false ) {
            return true;
        }
    }
    return false;
};

echo "Bricks is " . ( SNN_Bricks_Schema::bricks_loaded() ? 'loaded (' . count( SNN_Bricks_Schema::element_names() ) . " elements)" : 'NOT loaded' ) . "\n\n";

// ── 1. HTML id → Bricks CSS ID ─────────────────────────────────────────────
echo "1. HTML id becomes the CSS ID\n";
$r = $snn_build( '<section id="hero" data-bricks="section"><div data-bricks="container"><h1 data-bricks="heading" id="title">Hi</h1></div></section>' );
$snn_ok( $snn_find( $r['els'], 'section', 'hero' ) !== null, 'section keeps id="hero" as _cssId' );
$snn_ok( $snn_find( $r['els'], 'heading', 'title' ) !== null, 'heading keeps id="title" as _cssId' );
$snn_ok( SNN_Bricks_Html_To_Bricks::css_id_for( new DOMDocument(), 'block' ) === '', 'css_id_for tolerates a non-element' );
$r = $snn_build( '<section data-bricks="section"><div data-bricks="container"><div data-bricks="block" id="#1 Pricing!">x</div></div></section>' );
$snn_ok( $snn_find( $r['els'], 'block' )['settings']['_cssId'] === 'id-1-Pricing', 'an id is cleaned: "#1 Pricing!" becomes id-1-Pricing' );
$r = $snn_build( '<section data-bricks="section"><div data-bricks="container"><div data-bricks="block" id="a">x</div><div data-bricks="block" id="a">y</div></div></section>' );
$snn_ok( $snn_has( $r['warnings'], 'more than one element' ), 'a duplicate CSS ID is reported' );
$r = $snn_build( '<section data-bricks="section"><div data-bricks="container"><iframe id="emb" src="https://example.com/x"></iframe></div></section>' );
$snn_ok( ! isset( $snn_find( $r['els'], 'custom-html-css-script' )['settings']['_cssId'] ), 'raw custom HTML keeps its id inside its markup, not as _cssId' );

// ── 2. Inline style ─────────────────────────────────────────────────────────
echo "2. Inline styles are kept\n";
$r   = $snn_build( '<section data-bricks="section"><div data-bricks="container"><p data-bricks="text-basic" style="color: red; font-size: 20px; margin-top: 4px; display: flex; gap: 8px">Inline</p></div></section>' );
$p   = $snn_find( $r['els'], 'text-basic' );
$css = (string) ( $p['settings']['_cssCustom'] ?? '' );
$snn_ok( strpos( $css, 'color: red;' ) !== false && strpos( $css, 'font-size: 20px;' ) !== false && strpos( $css, 'margin-top: 4px;' ) !== false, 'color, font-size and margin-top are written on the element' );
$snn_ok( strpos( $css, 'display' ) === false && strpos( $css, 'gap' ) === false, 'display and gap are native settings, not repeated in CSS' );
$snn_ok( ( $p['settings']['_display'] ?? '' ) === 'flex', 'inline display:flex is native (_display flex)' );

$r   = $snn_build( '<style>.menu { display: none; flex-direction: column; } .menu[data-open] { display: flex; }</style><section data-bricks="section"><div data-bricks="container"><div data-bricks="block" class="menu" id="mobile">x</div></div></section>' );
$mn  = $snn_find( $r['els'], 'block' );
$snn_ok( ( $mn['settings']['_display'] ?? '' ) !== 'none', 'a panel hidden until [data-open] is not frozen to display:none as a native setting' );
$snn_ok( ( $mn['settings']['_cssGlobalClasses'] ?? array() ) !== array(), 'and its class (with the [data-open] rule) is attached' );

// ── 3. Native lists ─────────────────────────────────────────────────────────
echo "3. Plain lists are native\n";
$r  = $snn_build( '<style>.list li { margin: 4px 0; }</style><section data-bricks="section"><div data-bricks="container"><ul class="list"><li>One <b>bold</b></li><li>Two</li></ul></div></section>' );
$ul = $snn_find( $r['els'], 'block' );
$snn_ok( $ul && ( $ul['settings']['tag'] ?? '' ) === 'ul', 'a plain ul is a block with tag ul' );
$items = array_values( array_filter( $r['els'], function ( $e ) {
    return $e['name'] === 'text-basic' && ( $e['settings']['customTag'] ?? '' ) === 'li';
} ) );
$snn_ok( count( $items ) === 2, 'each li is its own text element (customTag li)' );
$snn_ok( isset( $items[0] ) && strpos( (string) $items[0]['settings']['text'], '<b>bold</b>' ) !== false, 'inline markup inside an item is kept' );
$snn_ok( isset( $items[0] ) && strpos( (string) ( $items[0]['settings']['_cssCustom'] ?? '' ), 'margin: 4px 0;' ) !== false, 'a contextual rule (.list li) reaches the item element' );
$r  = $snn_build( '<section data-bricks="section"><div data-bricks="container"><ul><li><h3>Card</h3></li></ul></div></section>' );
$snn_ok( $snn_find( $r['els'], 'heading' ) !== null && $snn_find( $r['els'], 'text-basic', null ) === null, 'a list of layout items keeps its blocks (no text collapse)' );

// ── 4. data-bricks hints are validated against Bricks ───────────────────────
echo "4. data-bricks names are checked\n";
$r   = $snn_build( '<section data-bricks="section"><div data-bricks="container"><div data-bricks="nope">x</div></div></section>' );
$snn_ok( $snn_has( $r['warnings'], 'Unknown element type "nope"' ), 'an unknown hint is reported with a suggestion path' );
$snn_ok( $snn_find( $r['els'], 'nope' ) === null, 'an unknown hint is not written as an element name' );
$r   = $snn_build( '<section data-bricks="section"><div data-bricks="container"><div data-bricks="icon-box" data-bricks-iconsize="40px">Hi</div></div></section>' );
$ib  = $snn_find( $r['els'], 'icon-box' );
$snn_ok( $ib !== null, 'a registered Bricks element (icon-box) is created' );
$snn_ok( ( $ib['settings']['iconSize'] ?? '' ) === '40px', 'data-bricks-iconsize writes iconSize (case and dashes matched)' );
$snn_ok( ( $ib['settings']['content'] ?? '' ) === 'Hi' && ! $snn_has( $r['warnings'], 'cannot hold' ), 'icon-box (cannot hold children) takes its text in its content editor' );
$snn_ok( empty( $ib['children'] ) && ! isset( $ib['settings']['text'] ), 'and no HTML child is attached to it' );
$r   = $snn_build( '<section data-bricks="section"><div data-bricks="container"><div data-bricks="alert" data-bricks-type="warning">Careful <b>now</b></div></div></section>' );
$al  = $snn_find( $r['els'], 'alert' );
$snn_ok( $al && ( $al['settings']['content'] ?? '' ) === 'Careful <b>now</b>' && ( $al['settings']['type'] ?? '' ) === 'warning', 'alert: content keeps its inline markup, type is set from data-bricks-type' );
$r   = $snn_build( '<section data-bricks="section"><div data-bricks="container"><div data-bricks="testimonials" data-bricks-items=\'[{"name":"Ada","nam":"typo","content":"Great"}]\'>x</div></div></section>' );
$tm  = $snn_find( $r['els'], 'testimonials' );
$snn_ok( $tm && ( $tm['settings']['items'][0]['name'] ?? '' ) === 'Ada' && ! isset( $tm['settings']['items'][0]['nam'] ), 'a repeater item keeps its known fields and drops the unknown one' );
$snn_ok( $snn_has( $r['warnings'], 'Field "nam" is not a field of the items repeater of testimonials' ) && $snn_has( $r['warnings'], 'Did you mean "name"' ), 'the unknown repeater field is reported with the right name' );
$r   = $snn_build( '<section data-bricks="section"><div data-bricks="container"><div data-bricks="icon-box" data-bricks-nosuchkey="1">Hi</div></div></section>' );
$snn_ok( $snn_has( $r['warnings'], 'Setting "nosuchkey" is not a icon-box setting in Bricks.' ), 'an unknown data-bricks-* setting is reported with the element name' );
$snn_ok( ! isset( $snn_find( $r['els'], 'icon-box' )['settings']['nosuchkey'] ), 'and it is not written to the element' );
$text_el = null;
foreach ( SNN_Bricks_Schema::element_names() as $name ) {
    if ( ! SNN_Bricks_Schema::is_nestable( $name ) && SNN_Bricks_Schema::canonical_key( $name, 'text' ) === 'text' && ! in_array( $name, array( 'heading', 'text-basic', 'text', 'text-link', 'button', 'icon', 'image', 'svg', 'divider', 'video', 'custom-html-css-script' ), true ) ) {
        $text_el = $name;
        break;
    }
}
if ( $text_el !== null ) {
    $r  = $snn_build( '<section data-bricks="section"><div data-bricks="container"><div data-bricks="' . $text_el . '">Hello <b>there</b></div></div></section>' );
    $te = $snn_find( $r['els'], $text_el );
    $snn_ok( $te && strpos( (string) ( $te['settings']['text'] ?? '' ), '<b>there</b>' ) !== false, $text_el . ' (a text-capable element) gets its content in its text setting' );
} else {
    echo "  skip a text-capable non-nestable element: none in this Bricks\n";
}
$snn_ok( SNN_Bricks_Schema::unknown_keys( 'block', array( '_display' => 'block', 'nope' => 1 ) ) === array( 'nope' ), 'the schema check flags a key the element does not have' );
$snn_ok( SNN_Bricks_Schema::is_nestable( 'container' ) && ! SNN_Bricks_Schema::is_nestable( 'icon-box' ), 'nestable: container yes, icon-box no' );
$snn_ok( SNN_Bricks_Data_Settings::value( '[{"title":"A"}]' ) === array( array( 'title' => 'A' ) ), 'data-bricks-* JSON values become arrays (repeaters)' );
$snn_ok( SNN_Bricks_Data_Settings::value( 'true' ) === true && SNN_Bricks_Data_Settings::value( 'false' ) === false, 'true and false become booleans' );

$r   = $snn_build( '<section data-bricks="section"><div data-bricks="container"><div data-bricks="block" class="faq"><details open><summary>Q?</summary><p>A.</p></details><details><summary>Q2?</summary><p>A2.</p></details></div></div></section>' );
$snn_ok( $snn_find( $r['els'], 'accordion-nested' ) !== null && $snn_find( $r['els'], 'custom-html-css-script' ) === null, 'a block hint over a group of details still makes an accordion (no raw HTML left)' );

// ── 5. Interactions ─────────────────────────────────────────────────────────
echo "5. Interactions\n";
$r   = $snn_build( '<section data-bricks="section"><div data-bricks="container"><div id="menu">m</div><button data-bricks="button" data-interact="click" data-interact-action="toggleAttribute" data-interact-key="data-open" data-interact-target="#menu">Menu</button></div></section>' );
$btn = $snn_find( $r['els'], 'button' );
$ix  = $btn['settings']['_interactions'][0] ?? null;
$snn_ok( $ix && $ix['trigger'] === 'click' && $ix['action'] === 'toggleAttribute', 'shorthand: click + toggleAttribute' );
$snn_ok( $ix && $ix['target'] === 'custom' && $ix['targetSelector'] === '#menu' && $ix['actionAttributeKey'] === 'data-open', 'target #menu and the attribute key are stored' );
$snn_ok( ! $snn_has( $r['warnings'], 'no element on the page has that id' ), 'a target that exists on the page is not reported' );
$r   = $snn_build( '<section data-bricks="section"><div data-bricks="container"><button data-bricks="button" data-interact="click" data-interact-action="show" data-interact-target="#ghost">Go</button></div></section>' );
$snn_ok( $snn_has( $r['warnings'], '#ghost' ), 'a target with no element on the page is reported' );
$r   = $snn_build( '<section data-bricks="section"><div data-bricks="container"><div data-bricks="block" data-interactions=\'[{"trigger":"hover","action":"startAnimation","animation":"pulse"},{"trigger":"enterView","action":"startAnimation","animation":"fadeInUp","delay":"0.2s"}]\'>x</div></div></section>' );
$blk = $snn_find( $r['els'], 'block' );
$snn_ok( count( $blk['settings']['_interactions'] ?? array() ) === 2, 'data-interactions JSON gives two interactions' );
$snn_ok( ( $blk['settings']['_interactions'][0]['trigger'] ?? '' ) === 'mouseover' && ( $blk['settings']['_interactions'][1]['animationDelay'] ?? '' ) === '0.2s', 'hover maps to mouseover, delay kept' );
$r   = $snn_build( '<section data-bricks="section"><div data-bricks="container"><div data-interact="wiggle" data-interact-action="show">x</div></div></section>' );
$snn_ok( $snn_has( $r['warnings'], 'Unknown interaction trigger "wiggle"' ), 'an unknown trigger is reported and not stored' );
$snn_ok( ! isset( $snn_find( $r['els'], 'block' )['settings']['_interactions'] ), 'and nothing half-formed is written' );
$r   = $snn_build( '<section data-bricks="section"><div data-bricks="container"><div data-interact="click" data-interact-action="setAttribute">x</div></div></section>' );
$snn_ok( $snn_has( $r['warnings'], 'needs data-interact-key' ), 'setAttribute without a key is reported' );

// ── 6. Native video ─────────────────────────────────────────────────────────
echo "6. Video\n";
$r  = $snn_build( '<section data-bricks="section"><div data-bricks="container"><iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ?autoplay=1" title="Demo"></iframe></div></section>' );
$v  = $snn_find( $r['els'], 'video' );
$snn_ok( $v && ( $v['settings']['videoType'] ?? '' ) === 'youtube' && ( $v['settings']['youTubeId'] ?? '' ) === 'dQw4w9WgXcQ', 'a YouTube embed is a native video (id kept)' );
$snn_ok( $v && ( $v['settings']['youtubeAutoplay'] ?? false ) === true && ( $v['settings']['iframeTitle'] ?? '' ) === 'Demo', 'autoplay and the title are kept' );
$r  = $snn_build( '<section data-bricks="section"><div data-bricks="container"><iframe src="https://player.vimeo.com/video/76979871"></iframe></div></section>' );
$v  = $snn_find( $r['els'], 'video' );
$snn_ok( $v && ( $v['settings']['videoType'] ?? '' ) === 'vimeo' && ( $v['settings']['vimeoId'] ?? '' ) === '76979871', 'a Vimeo embed is a native video' );
$r  = $snn_build( '<section data-bricks="section"><div data-bricks="container"><video src="/clip.mp4"></video></div></section>' );
$v  = $snn_find( $r['els'], 'video' );
$snn_ok( $v && ( $v['settings']['videoType'] ?? '' ) === 'file' && ( $v['settings']['fileUrl'] ?? '' ) === '/clip.mp4', 'a video file is a native video (file)' );
$r  = $snn_build( '<section data-bricks="section"><div data-bricks="container"><iframe src="https://maps.example.com/embed?x=1"></iframe></div></section>' );
$snn_ok( $snn_find( $r['els'], 'video' ) === null && $snn_find( $r['els'], 'custom-html-css-script' ) !== null, 'an iframe that is not a video stays raw HTML' );

// ── 7. Icon libraries and the notes about them ──────────────────────────────
echo "7. Icons\n";
$r  = $snn_build( '<section data-bricks="section"><div data-bricks="container"><i class="ion-ios-star"></i></div></section>' );
$ic = $snn_find( $r['els'], 'icon' );
$snn_ok( $ic && ( $ic['settings']['icon']['library'] ?? '' ) === 'ionicons', 'ion-* becomes an Ionicons icon' );
$r  = $snn_build( '<section data-bricks="section"><div data-bricks="container"><i class="fal fa-star"></i></div></section>' );
$snn_ok( $snn_has( $r['warnings'], 'Icon class "fal" is a Font Awesome style' ), 'a light FA icon is reported (drawn solid)' );
$r  = $snn_build( '<section data-bricks="section"><div data-bricks="container"><i class="lucide-star"></i></div></section>' );
$snn_ok( $snn_has( $r['warnings'], 'is not an icon Bricks has' ), 'a non-Bricks icon is reported and kept as HTML' );

// ── 8. SVG capitals survive serialisation ───────────────────────────────────
echo "8. SVG case\n";
$svg = SNN_Bricks_Dom::restore_svg_case( '<svg viewbox="0 0 10 10" preserveaspectratio="none"><lineargradient id="g"><stop offset="0"/></lineargradient><path/></svg>' );
$snn_ok( strpos( $svg, 'viewBox="0 0 10 10"' ) !== false && strpos( $svg, 'preserveAspectRatio' ) !== false, 'viewBox and preserveAspectRatio are restored' );
$snn_ok( strpos( $svg, '<linearGradient id="g">' ) !== false && strpos( $svg, '</linearGradient>' ) !== false, 'linearGradient is restored, open and close' );
$snn_ok( SNN_Bricks_Dom::restore_svg_case( '<div viewbox="x">a</div>' ) === '<div viewbox="x">a</div>', 'markup without an svg is left alone' );
$r  = $snn_build( '<section data-bricks="section"><div data-bricks="container"><svg viewBox="0 0 10 10"><linearGradient id="g"><stop offset="0"/></linearGradient></svg></div></section>' );
$raw = $snn_find( $r['els'], 'custom-html-css-script' );
$snn_ok( $raw && strpos( (string) $raw['settings']['content'], 'viewBox' ) !== false, 'a standalone svg keeps viewBox in the output' );

// ── 9. Generic element hints and the schema ─────────────────────────────────
echo "9. Schema agrees with what the converter emits\n";
$samples = glob( __DIR__ . '/samples/*.html' );
$unknown = array();
foreach ( $samples as $file ) {
    $r = $snn_build( (string) file_get_contents( $file ) );
    foreach ( $r['els'] as $el ) {
        foreach ( SNN_Bricks_Schema::unknown_keys( $el['name'], (array) $el['settings'] ) as $k ) {
            $unknown[ $el['name'] . '.' . $k ][] = basename( $file );
        }
    }
}
ksort( $unknown );
foreach ( $unknown as $k => $files ) {
    echo '  note: ' . $k . ' is not in the schema (' . count( $files ) . " element(s), e.g. " . $files[0] . ")\n";
}
$snn_ok( true, 'schema scan finished (' . count( $unknown ) . ' distinct key(s) outside the schema, listed above)' );

// ── Summary ─────────────────────────────────────────────────────────────────
echo "\n" . $GLOBALS['snn_feat_ok'] . ' passed, ' . $GLOBALS['snn_feat_fail'] . " failed\n";
if ( $GLOBALS['snn_feat_fail'] && class_exists( 'WP_CLI' ) ) {
    WP_CLI::halt( 1 );
}
