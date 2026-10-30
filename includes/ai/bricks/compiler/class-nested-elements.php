<?php
/**
 * Nested Bricks elements: accordion, tabs and slider.
 *
 * The HTML says what it is, and the compiler writes Bricks' own element with
 * its own item structure, so the result stays editable in the builder:
 *
 *   <div data-bricks="accordion">               accordion-nested
 *     <details><summary>Title</summary>…</details>
 *   </div>
 *
 *   <div data-bricks="tabs">                    tabs-nested
 *     <div data-bricks="tab" data-title="One">…</div>
 *   </div>
 *
 *   <div data-bricks="slider" data-per-page="1"> slider-nested
 *     <div>…slide…</div>
 *   </div>
 *
 * A container whose children are all <details> is an accordion without any hint.
 *
 * Item structures follow the children Bricks itself offers for each element
 * (get_nestable_item / get_nestable_children in the Bricks theme), including the
 * hidden classes its scripts look for (accordion-title-wrapper, tab-menu, tab-pane …).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SNN_Bricks_Nested {

    /** The compile context while a nested element is being built: its breakpoint plan and cascade. */
    private static $ctx = null;

    /**
     * Which nested element an HTML element becomes, or null.
     *
     * @param object      $el      DOM element
     * @param string|null $explicit value of its data-bricks attribute
     */
    public static function name_for( $el, $explicit ) {
        if ( $explicit !== null && $explicit !== '' ) {
            $map = array( 'accordion' => 'accordion-nested', 'tabs' => 'tabs-nested', 'slider' => 'slider-nested' );
            return $map[ $explicit ] ?? null;
        }
        // No hint: a container made only of <details> is an accordion.
        $kids = SNN_Bricks_Dom::children( $el );
        if ( ! $kids ) {
            return null;
        }
        foreach ( $kids as $kid ) {
            if ( SNN_Bricks_Dom::tag( $kid ) !== 'details' ) {
                return null;
            }
        }
        return 'accordion-nested';
    }

    /**
     * Build the nested element and its children into $content.
     *
     * @param object   $el          the container
     * @param string   $name        accordion-nested | tabs-nested | slider-nested
     * @param string   $parent_id   Bricks id of the container's parent
     * @param object   $ids         SNN_Bricks_Id_Pool
     * @param array    $content     flat element list (by reference)
     * @param callable $walk        walks one child node under a parent id, returns the child's id
     * @param array    $class_to_id className → global class id
     * @return string  Bricks id of the nested element
     */
    public static function build( $el, $name, $parent_id, $ids, array &$content, $walk, array $class_to_id, $ctx = null ) {
        self::$ctx = $ctx;
        $index     = self::node( $content, $name, $parent_id, $ids, array(), self::global_ids( $el, $class_to_id ) );
        self::style( $content, $index, $el );
        self::hand_over_inner_layout( $content[ $index ]['settings'] );
        switch ( $name ) {
            case 'accordion-nested':
                self::accordion( $el, $index, $ids, $content, $walk, $class_to_id );
                break;
            case 'tabs-nested':
                self::tabs( $el, $index, $ids, $content, $walk, $class_to_id );
                break;
            case 'slider-nested':
                self::slider( $el, $index, $ids, $content, $walk, $class_to_id );
                break;
        }
        return $content[ $index ]['id'];
    }

    // ── accordion ──────────────────────────────────────────────────────────

    private static function accordion( $el, $self, $ids, array &$content, $walk, array $class_to_id ) {
        $items = array_values( array_filter( SNN_Bricks_Dom::children( $el ), function ( $kid ) {
            return SNN_Bricks_Dom::tag( $kid ) === 'details' || SNN_Bricks_Dom::attr( $kid, 'data-bricks' ) === 'accordion-item';
        } ) );

        $content[ $self ]['settings']['independentToggle'] = true; // <details> items open independently
        if ( $items && SNN_Bricks_Dom::attr( $items[0], 'open' ) !== null ) {
            $content[ $self ]['settings']['expandFirstItem'] = true;
        }

        foreach ( $items as $details ) {
            $item = self::node( $content, 'block', $content[ $self ]['id'], $ids, array(), self::global_ids( $details, $class_to_id ) );
            self::link( $content, $self, $item );
            self::style( $content, $item, $details );

            $summary = null;
            foreach ( SNN_Bricks_Dom::children( $details ) as $kid ) {
                if ( SNN_Bricks_Dom::tag( $kid ) === 'summary' ) {
                    $summary = $kid;
                    break;
                }
            }

            // Title row: heading and the chevron icon, as Bricks' accordion template builds it.
            $title = self::node( $content, 'block', $content[ $item ]['id'], $ids, array(
                '_display'        => 'flex', // the row must stay a flex row, whatever the summary's own display says
                '_direction'      => 'row',
                '_alignItems'     => 'center',
                '_justifyContent' => 'space-between',
                '_hidden'         => array( '_cssClasses' => 'accordion-title-wrapper' ),
            ), $summary ? self::global_ids( $summary, $class_to_id ) : array() );
            self::link( $content, $item, $title );
            self::style( $content, $title, $summary );

            // The summary's type (size, weight, colour) belongs to the heading that shows its text.
            $heading = self::node( $content, 'heading', $content[ $title ]['id'], $ids, array(
                'text' => $summary ? trim( SNN_Bricks_Dom::inner_html( $summary ) ) : '',
                'tag'  => 'h3',
            ), $summary ? self::global_ids( $summary, $class_to_id ) : array() );
            self::link( $content, $title, $heading );

            // Contextual rules on the summary (`.faq-item summary`) and inherited typography reach the heading.
            if ( $summary && self::$ctx !== null ) {
                $own   = SNN_Bricks_Cascade::resolve_decls_at( $summary, self::$ctx->rule_set, self::$ctx->plan['base']['width'] );
                $extra = SNN_Bricks_Html_To_Bricks::element_css_extras( $summary, 'heading', self::$ctx, $own );
                if ( $extra !== '' ) {
                    $content[ $heading ]['settings']['_cssCustom'] = $extra;
                }
            }

            $icon = self::node( $content, 'icon', $content[ $title ]['id'], $ids, array(
                'icon'            => array( 'icon' => 'ion-ios-arrow-forward', 'library' => 'ionicons' ),
                'iconSize'        => '1em',
                'isAccordionIcon' => true,
            ) );
            self::link( $content, $title, $icon );

            $body = self::node( $content, 'block', $content[ $item ]['id'], $ids, array(
                '_hidden' => array( '_cssClasses' => 'accordion-content-wrapper' ),
            ) );
            self::link( $content, $item, $body );

            foreach ( SNN_Bricks_Dom::children( $details ) as $kid ) {
                if ( $kid === $summary ) {
                    continue;
                }
                $child = $walk( $kid, $content[ $body ]['id'] );
                if ( $child ) {
                    $content[ $body ]['children'][] = $child;
                }
            }
        }
    }

    // ── tabs ───────────────────────────────────────────────────────────────

    private static function tabs( $el, $self, $ids, array &$content, $walk, array $class_to_id ) {
        $panes = array_values( array_filter( SNN_Bricks_Dom::children( $el ), function ( $kid ) {
            return SNN_Bricks_Dom::attr( $kid, 'data-bricks' ) === 'tab';
        } ) );

        $menu = self::node( $content, 'block', $content[ $self ]['id'], $ids, array(
            '_direction' => 'row',
            '_hidden'    => array( '_cssClasses' => 'tab-menu' ),
        ) );
        self::link( $content, $self, $menu );
        $body = self::node( $content, 'block', $content[ $self ]['id'], $ids, array(
            '_hidden' => array( '_cssClasses' => 'tab-content' ),
        ) );
        self::link( $content, $self, $body );

        foreach ( $panes as $pane ) {
            $title = (string) SNN_Bricks_Dom::attr( $pane, 'data-title' );

            // Titles are styled from the tabs container (.tabs .tab-title, .tab-title.brx-open),
            // so the pane's own classes stay on the pane.
            $tab = self::node( $content, 'div', $content[ $menu ]['id'], $ids, array(
                '_hidden' => array( '_cssClasses' => 'tab-title' ),
            ) );
            self::link( $content, $menu, $tab );
            $label = self::node( $content, 'text-basic', $content[ $tab ]['id'], $ids, array(
                'text' => htmlspecialchars( $title, ENT_QUOTES, 'UTF-8' ),
            ) );
            self::link( $content, $tab, $label );

            $panel = self::node( $content, 'block', $content[ $body ]['id'], $ids, array(
                '_hidden' => array( '_cssClasses' => 'tab-pane' ),
            ) );
            self::link( $content, $body, $panel );

            // Bricks forces the open pane to display:block, so the design's own layout (grid, gap)
            // lives on a block inside the pane, which keeps the pane's classes and styles.
            $inner = self::node( $content, 'block', $content[ $panel ]['id'], $ids, array(), self::global_ids( $pane, $class_to_id ) );
            self::link( $content, $panel, $inner );
            self::style( $content, $inner, $pane );
            foreach ( SNN_Bricks_Dom::children( $pane ) as $kid ) {
                $child = $walk( $kid, $content[ $inner ]['id'] );
                if ( $child ) {
                    $content[ $inner ]['children'][] = $child;
                }
            }
        }
    }

    // ── slider ─────────────────────────────────────────────────────────────

    private static function slider( $el, $self, $ids, array &$content, $walk, array $class_to_id ) {
        $settings = array(
            'type'       => 'slide',
            // Bricks shows arrows and dots whenever the key is present, so a hidden one is left out.
            // Slides size to their content; Bricks' default is a fixed 50vh, which clips cards.
            'autoHeight' => true,
        ) + self::per_page_settings( SNN_Bricks_Dom::attr( $el, 'data-per-page' ) );
        if ( SNN_Bricks_Dom::attr( $el, 'data-arrows' ) !== 'false' ) {
            $settings['arrows'] = true;
        }
        if ( SNN_Bricks_Dom::attr( $el, 'data-pagination' ) !== 'false' ) {
            $settings['pagination'] = true;
        }
        $gap = SNN_Bricks_Dom::attr( $el, 'data-gap' );
        if ( $gap !== null && $gap !== '' ) {
            $settings['gap'] = (int) $gap;
        }
        $content[ $self ]['settings'] = array_merge( $content[ $self ]['settings'], $settings );

        foreach ( SNN_Bricks_Dom::children( $el ) as $slide ) {
            $item = self::node( $content, 'block', $content[ $self ]['id'], $ids, array(), self::global_ids( $slide, $class_to_id ) );
            self::link( $content, $self, $item );
            self::style( $content, $item, $slide );
            foreach ( SNN_Bricks_Dom::children( $slide ) as $kid ) {
                $child = $walk( $kid, $content[ $item ]['id'] );
                if ( $child ) {
                    $content[ $item ]['children'][] = $child;
                }
            }
        }
    }

    // ── helpers ────────────────────────────────────────────────────────────

    /** Append one element and return its index in $content. */
    private static function node( array &$content, $name, $parent_id, $ids, array $settings = array(), array $classes = array() ) {
        if ( $classes ) {
            $settings['_cssGlobalClasses'] = $classes;
        }
        $content[] = array(
            'id'          => $ids->next(),
            'name'        => $name,
            'parent'      => $parent_id,
            'children'    => array(),
            'settings'    => $settings,
            'themeStyles' => array(),
        );
        return count( $content ) - 1;
    }

    /**
     * Native layout (display, flex, grid, gap, width) of the design element a wrapper stands for,
     * merged under the settings the wrapper already has. Same rules as every other element.
     */
    private static function style( array &$content, $index, $el ) {
        if ( self::$ctx === null || $el === null ) {
            return;
        }
        $layout = SNN_Bricks_Native_Layout::compute( $el, 'block', self::$ctx );
        $content[ $index ]['settings'] = array_merge( $layout, $content[ $index ]['settings'] );
    }

    /**
     * Slides per view from data-per-page. "3" sets the desktop value; "3 2 1" also sets the
     * tablet portrait (991px) and mobile landscape (767px) values, as Bricks stores them.
     */
    private static function per_page_settings( $raw ) {
        $keys   = array( 'perPage', 'perPage:tablet_portrait', 'perPage:mobile_landscape' );
        $values = preg_split( '/\s+/', trim( (string) $raw ), -1, PREG_SPLIT_NO_EMPTY );
        $out    = array( 'perPage' => 1 );
        foreach ( $values as $i => $value ) {
            if ( ! isset( $keys[ $i ] ) ) {
                break;
            }
            $out[ $keys[ $i ] ] = max( 1, (int) $value );
        }
        return $out;
    }

    /**
     * Bricks lays out the inside of a nested element itself (the slider's track, the tab menu,
     * the accordion items). A design grid or flex on the wrapper would fight that layout, so the
     * wrapper keeps only its width and its box settings.
     */
    private static function hand_over_inner_layout( array &$settings ) {
        foreach ( array_keys( $settings ) as $key ) {
            if ( preg_match( '/^_(display|direction|flex|grid|gap|column|row|align|justify)/', (string) $key ) ) {
                unset( $settings[ $key ] );
            }
        }
    }

    /** Record that the element at $child is a child of the element at $parent. */
    private static function link( array &$content, $parent, $child ) {
        $content[ $parent ]['children'][] = $content[ $child ]['id'];
    }

    /** Global class ids for the classes an element wears. */
    private static function global_ids( $el, array $class_to_id ) {
        $gids = array();
        foreach ( preg_split( '/\s+/', (string) SNN_Bricks_Dom::attr( $el, 'class' ) ) as $cn ) {
            if ( $cn !== '' && ! SNN_Bricks_Features::is_fa_token( $cn ) && ! empty( $class_to_id[ $cn ] ) ) {
                $gids[] = $class_to_id[ $cn ];
            }
        }
        return $gids;
    }
}
