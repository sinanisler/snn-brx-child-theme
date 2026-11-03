<?php
/**
 * Cascade resolver — port of the "CASCADE RESOLVER" part of
 * html-to-bricks-translation.php.
 *
 * Every style rule is flattened into a list once; the selector engine (native
 * Element::matches() on PHP 8.4+, SNN_Bricks_Selector_Matcher elsewhere)
 * decides which rules reach each element, and the declarations are resolved
 * per viewport width so @media rules land on the breakpoints they cover.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SNN_Bricks_Rule_Set {
    /** @var array Structural rules that drive native settings. */
    public $rules = array();
    /** @var array :hover/:focus/... rules, kept for icon state styling. */
    public $states = array();
    /** @var array Every rule, pseudo-elements included, for selector rewriting. */
    public $all = array();
    /** @var \SplObjectStorage element → sorted matched rules */
    public $match_cache;
    /** @var array "objectId|width" → resolved declarations */
    public $decl_cache = array();
    /** @var \SplObjectStorage element → bool, a :hover/:focus rule sets its layout */
    public $state_layout;

    public function __construct() {
        $this->match_cache = new \SplObjectStorage();
        $this->state_layout = new \SplObjectStorage();
    }
}

class SNN_Bricks_Cascade {

    /** Selectors carrying interaction/state pseudos must not drive base layout settings. */
    const STATE_SELECTOR = '/::|:(hover|focus|focus-within|focus-visible|active|visited|target|checked|disabled|placeholder)\b/i';

    /**
     * Attribute selectors that a script or Bricks' interaction switches at runtime: data-*, aria-*,
     * and the state attributes (hidden, open, checked, disabled). A rule on them (`.menu[data-open]`)
     * is a state rule, so its layout stays in CSS, where it can win over the base value.
     */
    const STATE_ATTRIBUTE = '/\[(?:data-[\w-]+|aria-[\w-]+|hidden|open|checked|disabled)(?=[\]=~|^$*\s])/i';

    /** Longer names first, so ":focus" never half-matches ":focus-within". */
    const STATE_PSEUDO = '/:(hover|focus-within|focus-visible|focus|active|visited|target|checked|disabled)\b/i';

    const PSEUDO_ELEMENT = '/::?(before|after|first-letter|first-line|placeholder|marker|selection)\b/i';

    /** Layout property → the Bricks setting keys it is written to. A state rule owns these keys for its element. */
    const STATE_LAYOUT_KEYS = array(
        'display'               => array( '_display' ),
        'flex-direction'        => array( '_direction', '_flexDirection' ),
        'flex-wrap'             => array( '_flexWrap' ),
        'align-items'           => array( '_alignItems', '_alignItemsGrid' ),
        'justify-content'       => array( '_justifyContent', '_justifyContentGrid' ),
        'gap'                   => array( '_gap', '_gridGap', '_rowGap', '_columnGap' ),
        'row-gap'               => array( '_rowGap' ),
        'column-gap'            => array( '_columnGap' ),
        'grid-template-columns' => array( '_gridTemplateColumns' ),
        'grid-template-rows'    => array( '_gridTemplateRows' ),
        'width'                 => array( '_width' ),
        'max-width'             => array( '_widthMax' ),
    );

    /** Approximate CSS specificity as a single sortable integer (same formula as the JS). */
    public static function specificity( $sel ) {
        $s       = preg_replace( '/\[[^\]]*\]/', "\x01A\x01", (string) $sel );
        $ids     = preg_match_all( '/#[\w-]+/', $s );
        $classes = preg_match_all( '/\.[\w-]+/', $s )
                 + substr_count( $s, "\x01A\x01" )
                 + preg_match_all( '/:(?!:)[\w-]+/', $s );
        $stripped = str_replace( "\x01A\x01", '', preg_replace( '/[.#][\w-]+/', '', $s ) );
        $types   = preg_match_all( '/(^|[\s>+~(,])\s*[a-zA-Z][\w-]*/', $stripped );
        return $ids * 10000 + $classes * 100 + $types;
    }

    /** Flatten a stylesheet into a rule set. */
    public static function build_rule_set( $css ) {
        $set   = new SNN_Bricks_Rule_Set();
        $order = 0;
        $walk = function ( $text, $media ) use ( &$walk, $set, &$order ) {
            foreach ( SNN_Bricks_Css::top_level_blocks( $text ) as $block ) {
                if ( $block['body'] === null ) {
                    continue;
                }
                $prelude = $block['prelude'];
                if ( preg_match( '/^@media/i', $prelude ) ) {
                    $walk( $block['body'], array_merge( $media, array( trim( preg_replace( '/^@media\s*/i', '', $prelude ) ) ) ) );
                    continue;
                }
                if ( strpos( $prelude, '@' ) === 0 ) {
                    continue;
                }
                $decls = SNN_Bricks_Css::parse_declarations_with_priority( $block['body'] );
                if ( ! $decls['count'] ) {
                    continue;
                }
                foreach ( SNN_Bricks_Css::split_selector_list( $prelude ) as $sel ) {
                    $rule = array(
                        'selector'  => $sel,
                        'decls'     => $decls['normal'],
                        'important' => $decls['important'],
                        'raw'       => $block['body'],
                        'spec'      => self::specificity( $sel ),
                        'order'     => $order++,
                        'media'     => $media ? $media : null,
                    );
                    $set->all[] = $rule;
                    if ( preg_match( self::STATE_SELECTOR, $sel ) ) {
                        if ( strpos( $sel, '::' ) === false ) {
                            $set->states[] = $rule;
                        }
                        continue;
                    }
                    if ( preg_match( self::STATE_ATTRIBUTE, $sel ) ) {
                        // Applies as a base rule where the attribute is in the markup ([data-kind="x"]), and owns its
                        // layout where a script sets it later (.menu[data-open]): both lists, so each case works.
                        $set->states[] = $rule;
                        $set->rules[]  = $rule;
                        continue;
                    }
                    $set->rules[] = $rule;
                }
            }
        };
        $walk( SNN_Bricks_Css::strip_comments( $css ), array() );
        return $set;
    }

    public static function rule_applies_at( $rule, $width ) {
        if ( empty( $rule['media'] ) ) {
            return true;
        }
        foreach ( $rule['media'] as $q ) {
            if ( ! SNN_Bricks_Media::matches_width( $q, $width ) ) {
                return false;
            }
        }
        return true;
    }

    /** Structural rules matching an element, sorted by cascade order. Width-independent, cached per element. */
    public static function matched_rules_for( $el, $set ) {
        if ( ! $set || ! SNN_Bricks_Dom::is_element( $el ) ) {
            return array();
        }
        if ( $set->match_cache->contains( $el ) ) {
            return $set->match_cache[ $el ];
        }
        $hit = array();
        foreach ( $set->rules as $r ) {
            if ( SNN_Bricks_Dom::matches( $el, $r['selector'] ) ) {
                $hit[] = $r;
            }
        }
        usort( $hit, function ( $a, $b ) {
            return ( $a['spec'] - $b['spec'] ) ?: ( $a['order'] - $b['order'] );
        } );
        $set->match_cache[ $el ] = $hit;
        return $hit;
    }

    /**
     * The element a state selector styles: `.nav:hover .mega` styles `.mega`, `.btn:focus` styles `.btn`.
     * The state pseudo-classes are dropped, leaving a plain selector for the element itself.
     */
    public static function state_subject( $selector ) {
        $parts = preg_split( '/\s*[>+~]\s*|\s+/', trim( (string) $selector ) );
        $last  = (string) end( $parts );
        // The attribute a script sets is not on the element at compile time, so it is dropped with the pseudo-classes.
        $last  = preg_replace( '/\[[^\]]*\]/', '', $last );
        return trim( preg_replace( self::STATE_PSEUDO, '', $last ) );
    }

    /**
     * Bricks setting keys whose layout a state rule (:hover, :focus-within, ...) changes on this element.
     *
     * Native layout is written as #brxe-{id} CSS, which outranks every class rule. If the base value
     * (`display:none` on an overlay) were written there, it would beat the state rule (`:hover` shows
     * the overlay) and the state would never apply. These keys stay out of the element settings, so
     * the class CSS carries them and the state rule can win. Other layout (width, grid columns) is
     * still native.
     */
    public static function state_owned_keys( $el, $set ) {
        if ( ! $set || ! SNN_Bricks_Dom::is_element( $el ) ) {
            return array();
        }
        if ( $set->state_layout->contains( $el ) ) {
            return $set->state_layout[ $el ];
        }
        $keys = array();
        foreach ( $set->states as $r ) {
            $subject = self::state_subject( $r['selector'] );
            if ( $subject === '' || ! SNN_Bricks_Dom::matches( $el, $subject ) ) {
                continue;
            }
            foreach ( array_keys( $r['decls'] ) as $prop ) {
                $owned = self::STATE_LAYOUT_KEYS[ strtolower( (string) $prop ) ] ?? array();
                foreach ( $owned as $key ) {
                    $keys[ $key ] = true;
                }
            }
        }
        $keys = array_keys( $keys );
        $set->state_layout[ $el ] = $keys;
        return $keys;
    }

    /** The declarations that apply to one element at one viewport width. */
    public static function resolve_decls_at( $el, $set, $width ) {
        if ( ! $el ) {
            return array();
        }
        $key = spl_object_id( $el ) . '|' . $width;
        if ( $set && isset( $set->decl_cache[ $key ] ) ) {
            return $set->decl_cache[ $key ];
        }
        $out       = array();
        $important = array();
        foreach ( self::matched_rules_for( $el, $set ) as $r ) {
            if ( ! self::rule_applies_at( $r, $width ) ) {
                continue;
            }
            foreach ( $r['decls'] as $k => $v ) {
                $out[ $k ] = $v;
            }
            foreach ( $r['important'] as $k => $v ) {
                $important[ $k ] = $v;
            }
        }
        // A stray inline style="" wins over any stylesheet rule.
        $inline = SNN_Bricks_Dom::attr( $el, 'style' );
        if ( $inline ) {
            foreach ( SNN_Bricks_Css::parse_declarations( $inline ) as $k => $v ) {
                $out[ $k ] = $v;
            }
        }
        foreach ( $important as $k => $v ) {
            $out[ $k ] = $v;
        }
        if ( $set ) {
            $set->decl_cache[ $key ] = $out;
        }
        return $out;
    }

    /**
     * Declarations from contextual rules (`.card p`, `.faq-item summary`) that apply to one
     * element at one width. Bricks does not keep the design's DOM path, so these move onto the
     * element itself. Layout is left out: the native layout pass already sets it.
     */
    public static function contextual_decls_at( $el, $set, $width ) {
        $out = array();
        foreach ( self::matched_rules_for( $el, $set ) as $r ) {
            if ( ! self::rule_applies_at( $r, $width ) || ! preg_match( '/[\s>+~]/', trim( $r['selector'] ) ) ) {
                continue;
            }
            foreach ( $r['decls'] as $k => $v ) {
                $out[ $k ] = $v;
            }
            foreach ( $r['important'] as $k => $v ) {
                $out[ $k ] = $v;
            }
        }
        return $out;
    }

    /** Declarations of the plain `body` rule: the values text inherits in the browser. */
    public static function body_decls( $set ) {
        $out = array();
        if ( ! $set ) {
            return $out;
        }
        foreach ( $set->rules as $r ) {
            if ( strtolower( trim( $r['selector'] ) ) === 'body' && empty( $r['media'] ) ) {
                foreach ( $r['decls'] as $k => $v ) {
                    $out[ $k ] = $v;
                }
            }
        }
        return $out;
    }
}
