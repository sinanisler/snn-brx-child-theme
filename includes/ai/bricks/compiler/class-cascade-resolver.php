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

    public function __construct() {
        $this->match_cache = new \SplObjectStorage();
    }
}

class SNN_Bricks_Cascade {

    /** Selectors carrying interaction/state pseudos must not drive base layout settings. */
    const STATE_SELECTOR = '/::|:(hover|focus|focus-within|focus-visible|active|visited|target|checked|disabled|placeholder)\b/i';

    /** Longer names first, so ":focus" never half-matches ":focus-within". */
    const STATE_PSEUDO = '/:(hover|focus-within|focus-visible|focus|active|visited|target|checked|disabled)\b/i';

    const PSEUDO_ELEMENT = '/::?(before|after|first-letter|first-line|placeholder|marker|selection)\b/i';

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
}
