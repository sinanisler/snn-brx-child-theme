<?php
/**
 * Structure test for the nested Bricks elements: accordion, tabs and slider.
 *
 *   studio wp eval-file wp-content/themes/<theme>/includes/ai/bricks/tests/run-nested.php
 *
 * Checks that each HTML pattern becomes Bricks' own element with the children
 * Bricks expects (titles, panes, slides) and the hidden classes its scripts use.
 * Exits non-zero on any failure.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$snn_nested_fail = 0;
$snn_nested_ok   = function ( $cond, $msg ) use ( &$snn_nested_fail ) {
    echo ( $cond ? '  ok   ' : '  FAIL ' ) . $msg . "\n";
    if ( ! $cond ) {
        $snn_nested_fail++;
    }
};

/** Element by id from a compiled list. */
$snn_by_id = function ( array $content, $id ) {
    foreach ( $content as $el ) {
        if ( $el['id'] === $id ) {
            return $el;
        }
    }
    return null;
};

$snn_html = <<<'HTML'
<section data-bricks="section"><div data-bricks="container">
  <div class="faq">
    <details open><summary class="faq-q">First?</summary><p>Answer one.</p></details>
    <details><summary>Second?</summary><p>Answer two.</p></details>
  </div>
  <div data-bricks="tabs" class="tabs">
    <div data-bricks="tab" data-title="Residential"><p>Homes text.</p></div>
    <div data-bricks="tab" data-title="Commercial"><p>Shops text.</p></div>
  </div>
  <div data-bricks="slider" data-per-page="2" data-arrows="false" data-gap="20">
    <div class="slide"><h3>Slide one</h3></div>
    <div class="slide"><h3>Slide two</h3></div>
    <div class="slide"><h3>Slide three</h3></div>
  </div>
</div></section>
HTML;

echo "nested elements\n";
$snn_result = SNN_Bricks_Html_To_Bricks::compile( $snn_html, null, null, array(
    'plan'    => SNN_Bricks_Native_Layout::breakpoint_plan( SNN_Bricks_Native_Layout::DEFAULT_BREAKPOINTS ),
    'palette' => array(),
) );
$snn_content = $snn_result['content'];
$snn_names   = array_column( $snn_content, 'name' );

// Accordion: details group without a hint becomes accordion-nested.
$snn_acc = null;
foreach ( $snn_content as $el ) {
    if ( $el['name'] === 'accordion-nested' ) {
        $snn_acc = $el;
    }
}
$snn_ok = $snn_acc !== null;
$snn_nested_ok( $snn_ok, 'details group becomes accordion-nested' );
if ( $snn_acc ) {
    $snn_items = array_map( function ( $id ) use ( $snn_content, $snn_by_id ) {
        return $snn_by_id( $snn_content, $id );
    }, $snn_acc['children'] );
    $snn_nested_ok( count( $snn_items ) === 2, 'accordion has one item per details' );
    $snn_nested_ok( ( $snn_acc['settings']['independentToggle'] ?? false ) === true, 'accordion items toggle independently' );
    $snn_nested_ok( ( $snn_acc['settings']['expandFirstItem'] ?? false ) === true, 'open first details expands the first item' );

    $snn_title = $snn_by_id( $snn_content, $snn_items[0]['children'][0] );
    $snn_nested_ok( ( $snn_title['settings']['_hidden']['_cssClasses'] ?? '' ) === 'accordion-title-wrapper', 'title row carries accordion-title-wrapper' );
    $snn_nested_ok( ( $snn_title['settings']['_cssGlobalClasses'] ?? array() ) !== array(), 'summary class moves onto the title row' );
    $snn_heading = $snn_by_id( $snn_content, $snn_title['children'][0] );
    $snn_nested_ok( $snn_heading['name'] === 'heading' && $snn_heading['settings']['text'] === 'First?', 'summary text becomes the item heading' );
    $snn_nested_ok( $snn_by_id( $snn_content, $snn_title['children'][1] )['name'] === 'icon', 'title row has the accordion icon' );

    $snn_body = $snn_by_id( $snn_content, $snn_items[0]['children'][1] );
    $snn_nested_ok( ( $snn_body['settings']['_hidden']['_cssClasses'] ?? '' ) === 'accordion-content-wrapper', 'content block carries accordion-content-wrapper' );
    $snn_nested_ok( count( $snn_body['children'] ) === 1 && $snn_by_id( $snn_content, $snn_body['children'][0] )['name'] === 'text-basic', 'answer paragraph is a native text element inside the item' );
}

// Tabs: menu and content, one title and one pane per tab, matched by order.
$snn_tabs = null;
foreach ( $snn_content as $el ) {
    if ( $el['name'] === 'tabs-nested' ) {
        $snn_tabs = $el;
    }
}
$snn_nested_ok( $snn_tabs !== null, 'data-bricks="tabs" becomes tabs-nested' );
if ( $snn_tabs ) {
    $snn_menu = $snn_by_id( $snn_content, $snn_tabs['children'][0] );
    $snn_panes = $snn_by_id( $snn_content, $snn_tabs['children'][1] );
    $snn_nested_ok( ( $snn_menu['settings']['_hidden']['_cssClasses'] ?? '' ) === 'tab-menu', 'first child is the tab menu' );
    $snn_nested_ok( ( $snn_panes['settings']['_hidden']['_cssClasses'] ?? '' ) === 'tab-content', 'second child is the tab content' );
    $snn_nested_ok( count( $snn_menu['children'] ) === 2 && count( $snn_panes['children'] ) === 2, 'two titles and two panes' );
    $snn_title_text = $snn_by_id( $snn_content, $snn_by_id( $snn_content, $snn_menu['children'][1] )['children'][0] );
    $snn_nested_ok( $snn_title_text['settings']['text'] === 'Commercial', 'titles come from data-title, in order' );
    $snn_pane = $snn_by_id( $snn_content, $snn_panes['children'][0] );
    $snn_nested_ok( ( $snn_pane['settings']['_hidden']['_cssClasses'] ?? '' ) === 'tab-pane', 'panes carry tab-pane' );
}

// Slider: slides as children, settings from the data attributes.
$snn_slider = null;
foreach ( $snn_content as $el ) {
    if ( $el['name'] === 'slider-nested' ) {
        $snn_slider = $el;
    }
}
$snn_nested_ok( $snn_slider !== null, 'data-bricks="slider" becomes slider-nested' );
if ( $snn_slider ) {
    $snn_nested_ok( count( $snn_slider['children'] ) === 3, 'one child per slide' );
    $snn_nested_ok( ( $snn_slider['settings']['perPage'] ?? null ) === 2, 'data-per-page sets perPage' );
    // Bricks shows arrows whenever the key is set, so a hidden one must be absent.
    $snn_nested_ok( ! array_key_exists( 'arrows', $snn_slider['settings'] ), 'data-arrows="false" leaves the arrows key out' );
    $snn_nested_ok( ( $snn_slider['settings']['pagination'] ?? null ) === true, 'pagination on by default' );
    $snn_nested_ok( ( $snn_slider['settings']['gap'] ?? null ) === 20, 'data-gap sets the gap' );
    $snn_nested_ok( ( $snn_slider['settings']['type'] ?? '' ) === 'slide', 'type is slide' );
}

// Every child points back at its parent, and no element is orphaned.
$snn_parents_ok = true;
foreach ( $snn_content as $el ) {
    foreach ( $el['children'] as $cid ) {
        $child = $snn_by_id( $snn_content, $cid );
        if ( ! $child || $child['parent'] !== $el['id'] ) {
            $snn_parents_ok = false;
        }
    }
}
$snn_nested_ok( $snn_parents_ok, 'every child lists its parent correctly' );

echo "\n" . ( $snn_nested_fail ? "FAILED: $snn_nested_fail" : 'all nested checks passed' ) . "\n";
if ( $snn_nested_fail && class_exists( 'WP_CLI' ) ) {
    WP_CLI::halt( 1 );
}
