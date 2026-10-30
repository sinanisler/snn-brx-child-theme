<?php
/**
 * Element-level features of the compiler: FontAwesome icons, links, decorative
 * lines → dividers, colours, icon size/colour/state pinning, SVG assets and the
 * native icons of buttons and text links. Port of the matching sections of
 * html-to-bricks-translation.php, same names in snake_case.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Everything one compile shares: rule set, breakpoint plan, SVG assets, palette. */
class SNN_Bricks_Compile_Context {
    /** @var SNN_Bricks_Rule_Set */
    public $rule_set;
    /** @var array */
    public $plan;
    /** @var array<string, array{id: int, url: string, filename: string}> svg key → asset */
    public $svg_assets = array();
    /** @var array|null Bricks colour palettes */
    public $palette = null;
    /** @var array<int, array{src: string, bytes: int}> <script> tags kept as embedded code */
    public $scripts = array();

    public function __construct( $rule_set, $plan, $svg_assets = array(), $palette = null ) {
        $this->rule_set   = $rule_set ? $rule_set : new SNN_Bricks_Rule_Set();
        $this->plan       = $plan;
        $this->svg_assets = is_array( $svg_assets ) ? $svg_assets : array();
        $this->palette    = $palette;
    }
}

class SNN_Bricks_Features {

    /** FontAwesome utility tokens that are NOT the glyph name. */
    const FA_MODIFIER = '/^fa-(fw|border|inverse|li|ul|pull-left|pull-right|spin|pulse|beat|fade|bounce|shake|flip|flip-horizontal|flip-vertical|flip-both|rotate-(90|180|270|by)|stack|stack-1x|stack-2x|xs|sm|lg|xl|2xl|[0-9]+x|sharp|duotone|solid|regular|brands|light|thin)$/';

    const MAX_LINE_THICKNESS_PX = 8;

    const BORDER_STYLES = array( 'solid', 'dashed', 'dotted', 'double', 'groove', 'ridge', 'inset', 'outset', 'none', 'hidden' );

    const SECTION_TAG_OPTIONS = array( 'section', 'header', 'footer', 'article', 'aside', 'div' );
    const BLOCK_TAG_OPTIONS   = array( 'div', 'section', 'a', 'article', 'nav', 'ol', 'ul', 'li', 'aside', 'address', 'figure' );

    const ICON_WRAPPER_TAGS = array( 'span', 'i', 'div', 'em', 'b', 'strong', 'small' );

    const INLINE_LIST_TAGS = array( 'a', 'abbr', 'b', 'br', 'code', 'em', 'i', 'kbd', 'mark', 's', 'small', 'span', 'strong', 'sub', 'sup', 'svg', 'time', 'u', 'wbr' );

    /** Tag → Bricks element type fallback. */
    const TAG_TO_ELEMENT = array(
        'section' => 'section', 'header' => 'section', 'footer' => 'section',
        'nav' => 'block', 'article' => 'block', 'aside' => 'block', 'main' => 'block',
        'div' => 'block', 'figure' => 'block', 'li' => 'block',
        'h1' => 'heading', 'h2' => 'heading', 'h3' => 'heading',
        'h4' => 'heading', 'h5' => 'heading', 'h6' => 'heading',
        'p' => 'text-basic', 'span' => 'text-basic', 'strong' => 'text-basic',
        'em' => 'text-basic', 'small' => 'text-basic', 'blockquote' => 'text-basic',
        'figcaption' => 'text-basic', 'address' => 'text-basic', 'time' => 'text-basic',
        'label' => 'text-basic', 'ul' => 'text-basic', 'ol' => 'text-basic',
        'dl' => 'text-basic', 'pre' => 'text-basic',
        'a' => 'text-link', 'button' => 'button', 'img' => 'image',
        'i' => 'icon',
        'hr' => 'divider',
        'svg' => 'custom-html-css-script', 'canvas' => 'custom-html-css-script',
        'iframe' => 'custom-html-css-script', 'video' => 'custom-html-css-script',
        'audio' => 'custom-html-css-script', 'picture' => 'custom-html-css-script',
        'table' => 'custom-html-css-script', 'form' => 'custom-html-css-script',
        'details' => 'custom-html-css-script', 'input' => 'custom-html-css-script',
        'textarea' => 'custom-html-css-script', 'select' => 'custom-html-css-script',
        'object' => 'custom-html-css-script', 'embed' => 'custom-html-css-script',
    );

    // ── icons and links ────────────────────────────────────────────────────

    /** FontAwesome class string → Bricks icon object, or null. */
    public static function parse_fa_icon( $class_string ) {
        $class_string = trim( (string) $class_string );
        if ( $class_string === '' ) {
            return null;
        }
        $style = array(
            'fas' => 'fontawesomeSolid', 'fa-solid' => 'fontawesomeSolid',
            'far' => 'fontawesomeRegular', 'fa-regular' => 'fontawesomeRegular',
            'fab' => 'fontawesomeBrands', 'fa-brands' => 'fontawesomeBrands',
            'fa'  => 'fontawesomeSolid',
        );
        $library = null;
        $glyph   = null;
        foreach ( preg_split( '/\s+/', $class_string ) as $token ) {
            if ( isset( $style[ $token ] ) ) {
                if ( ! $library || $library === 'fontawesomeSolid' ) {
                    $library = $style[ $token ];
                }
            } elseif ( ! $glyph && strpos( $token, 'fa-' ) === 0 && ! preg_match( self::FA_MODIFIER, $token ) ) {
                $glyph = $token;
            }
        }
        if ( ! $glyph ) {
            return null;
        }
        if ( ! $library ) {
            $library = 'fontawesomeSolid';
        }
        $prefix = $library === 'fontawesomeBrands' ? 'fab' : ( $library === 'fontawesomeRegular' ? 'far' : 'fas' );
        return array( 'library' => $library, 'icon' => $prefix . ' ' . $glyph );
    }

    public static function is_fa_token( $c ) {
        return in_array( $c, array( 'fa', 'fas', 'far', 'fab', 'fal', 'fad', 'fat' ), true ) || strpos( $c, 'fa-' ) === 0;
    }

    /** href → Bricks link object (always external, like Bricks' own converter). */
    public static function parse_link( $el ) {
        $href = SNN_Bricks_Dom::attr( $el, 'href' );
        if ( $href === null || $href === '' ) {
            $href = SNN_Bricks_Dom::attr( $el, 'data-href' );
        }
        $href = trim( ( $href === null || $href === '' ) ? '#' : $href );
        $link = array( 'type' => 'external', 'url' => $href );
        if ( SNN_Bricks_Dom::attr( $el, 'target' ) === '_blank' ) {
            $link['newTab'] = true;
        }
        $rel = SNN_Bricks_Dom::attr( $el, 'rel' );
        if ( $rel ) {
            $link['rel'] = $rel;
        }
        return $link;
    }

    // ── element type ───────────────────────────────────────────────────────

    /** A list whose items hold layout (cards, nested blocks) rather than inline text. */
    public static function is_structural_list( $el ) {
        foreach ( SNN_Bricks_Dom::descendants( $el ) as $d ) {
            if ( $d->hasAttribute( 'data-bricks' ) ) {
                return true;
            }
        }
        foreach ( SNN_Bricks_Dom::children( $el ) as $li ) {
            foreach ( SNN_Bricks_Dom::children( $li ) as $c ) {
                if ( ! in_array( SNN_Bricks_Dom::tag( $c ), self::INLINE_LIST_TAGS, true ) ) {
                    return true;
                }
            }
        }
        return false;
    }

    /** The Bricks element an HTML node compiles to. */
    public static function bricks_name_for( $el ) {
        $explicit = SNN_Bricks_Dom::attr( $el, 'data-bricks' );
        if ( $explicit ) {
            return $explicit;
        }
        $tag = SNN_Bricks_Dom::tag( $el );
        if ( ( $tag === 'ul' || $tag === 'ol' ) && self::is_structural_list( $el ) ) {
            return 'block';
        }
        return self::TAG_TO_ELEMENT[ $tag ] ?? 'block';
    }

    /** Keep a layout element's real HTML tag so tag selectors and semantics survive. */
    public static function apply_layout_tag( array &$element, $el, $bricks_name, $tag ) {
        $is_section = $bricks_name === 'section';
        $fallback   = $is_section ? 'section' : 'div';
        if ( $tag === $fallback ) {
            return;
        }
        if ( in_array( $tag, $is_section ? self::SECTION_TAG_OPTIONS : self::BLOCK_TAG_OPTIONS, true ) ) {
            $element['settings']['tag'] = $tag;
        } else {
            $element['settings']['tag']       = 'custom';
            $element['settings']['customTag'] = $tag;
        }
        $href = SNN_Bricks_Dom::attr( $el, 'href' );
        $data = SNN_Bricks_Dom::attr( $el, 'data-href' );
        if ( $tag === 'a' && ( $href || $data ) ) {
            $element['settings']['link'] = self::parse_link( $el );
        }
    }

    // ── lengths, colours, borders ──────────────────────────────────────────

    /** "1px" → 1, "0.125rem" → 2, "0" → 0; relative or computed values → null. */
    public static function css_length_to_px( $v ) {
        if ( ! preg_match( '/^(-?\d*\.?\d+)(px|rem)?$/i', trim( (string) $v ), $m ) ) {
            return null;
        }
        $n = (float) $m[1];
        if ( empty( $m[2] ) ) {
            return $n == 0 ? 0 : null; // phpcs:ignore Universal.Operators.StrictComparisons
        }
        return strtolower( $m[2] ) === 'rem' ? $n * 16 : $n;
    }

    public static function is_color_token( $t ) {
        return (bool) preg_match( '/^(#[0-9a-f]{3,8}|(rgb|hsl|hwb|lab|lch|oklab|oklch|color)a?\(.*\)|var\(.*\)|[a-z]+)$/i', $t )
            && ! preg_match( '/^(none|transparent|inherit|initial|unset|auto)$/i', $t );
    }

    public static function border_width_px( $w ) {
        if ( $w === null || $w === '' ) {
            return 3;
        }
        $k = array( 'thin' => 1, 'medium' => 3, 'thick' => 5 );
        $l = strtolower( (string) $w );
        return isset( $k[ $l ] ) ? $k[ $l ] : self::css_length_to_px( $w );
    }

    /** One side's border as { width, style, color }; the most specific declaration wins. */
    public static function resolve_border_side( array $decls, $side ) {
        $out = array( 'width' => null, 'style' => null, 'color' => null );
        $idx = array( 'top' => 0, 'right' => 1, 'bottom' => 2, 'left' => 3 )[ $side ];
        $box = function ( $v ) use ( $idx ) {
            $p = SNN_Bricks_Css::split_tokens( $v );
            if ( count( $p ) === 1 ) {
                return $p[0];
            }
            if ( count( $p ) === 2 ) {
                return $p[ $idx % 2 ];
            }
            if ( count( $p ) === 3 ) {
                return $idx === 3 ? $p[1] : $p[ $idx ];
            }
            return $p[ $idx ] ?? null;
        };
        $take = function ( $shorthand ) use ( &$out ) {
            if ( ! $shorthand ) {
                return;
            }
            $out = array( 'width' => null, 'style' => null, 'color' => null );
            foreach ( SNN_Bricks_Css::split_tokens( $shorthand ) as $t ) {
                $lower = strtolower( $t );
                if ( in_array( $lower, self::BORDER_STYLES, true ) ) {
                    $out['style'] = $lower;
                } elseif ( preg_match( '/^(thin|medium|thick)$/i', $t ) || preg_match( '/^-?\d*\.?\d+(px|rem|em)?$/i', $t ) ) {
                    $out['width'] = $t;
                } elseif ( self::is_color_token( $t ) ) {
                    $out['color'] = $t;
                }
            }
        };
        $take( $decls['border'] ?? '' );
        if ( ! empty( $decls['border-width'] ) ) {
            $out['width'] = $box( $decls['border-width'] );
        }
        if ( ! empty( $decls['border-style'] ) ) {
            $out['style'] = strtolower( (string) $box( $decls['border-style'] ) );
        }
        if ( ! empty( $decls['border-color'] ) ) {
            $out['color'] = $box( $decls['border-color'] );
        }
        $take( $decls[ 'border-' . $side ] ?? '' );
        if ( ! empty( $decls[ 'border-' . $side . '-width' ] ) ) {
            $out['width'] = trim( $decls[ 'border-' . $side . '-width' ] );
        }
        if ( ! empty( $decls[ 'border-' . $side . '-style' ] ) ) {
            $out['style'] = strtolower( trim( $decls[ 'border-' . $side . '-style' ] ) );
        }
        if ( ! empty( $decls[ 'border-' . $side . '-color' ] ) ) {
            $out['color'] = trim( $decls[ 'border-' . $side . '-color' ] );
        }
        return $out;
    }

    public static function is_visible_border( array $b ) {
        if ( ! $b['style'] || $b['style'] === 'none' || $b['style'] === 'hidden' ) {
            return false;
        }
        $px = self::border_width_px( $b['width'] );
        return $px === null || $px > 0;
    }

    /** Solid background colour: { color }, { gradient: true } for images/gradients, or null. */
    public static function resolve_background_paint( array $decls ) {
        $image = $decls['background-image'] ?? '';
        if ( $image !== '' && ! preg_match( '/^none$/i', trim( $image ) ) ) {
            return array( 'gradient' => true );
        }
        $shorthand = $decls['background'] ?? '';
        if ( $shorthand !== '' && preg_match( '/(url|gradient)\s*\(/i', $shorthand ) ) {
            return array( 'gradient' => true );
        }
        $value = ( $decls['background-color'] ?? '' ) !== '' ? $decls['background-color'] : $shorthand;
        if ( $value === '' ) {
            return null;
        }
        $tokens = SNN_Bricks_Css::split_tokens( $value );
        if ( count( $tokens ) !== 1 || ! self::is_color_token( $tokens[0] ) ) {
            return null;
        }
        return array( 'color' => $tokens[0] );
    }

    /** Does this element's resolved CSS draw a straight line? Describe it in divider terms. */
    public static function analyze_line( array $decls ) {
        $w   = trim( $decls['width'] ?? '' );
        $h   = trim( $decls['height'] ?? '' );
        $w_px = self::css_length_to_px( $w );
        $h_px = self::css_length_to_px( $h );
        $has_length = function ( $v ) {
            return $v !== '' && ! preg_match( '/^(auto|0|0px)$/i', $v );
        };

        $paint = self::resolve_background_paint( $decls );
        if ( $paint && ! empty( $paint['gradient'] ) ) {
            return null;
        }

        if ( $paint && ! empty( $paint['color'] ) ) {
            if ( $h_px !== null && $h_px > 0 && $h_px <= self::MAX_LINE_THICKNESS_PX && ! ( $w_px !== null && $w_px <= $h_px ) ) {
                return array( 'direction' => 'horizontal', 'thickness' => self::num( $h_px ) . 'px', 'length' => $has_length( $w ) ? $w : '100%', 'color' => $paint['color'], 'style' => 'solid' );
            }
            if ( $w_px !== null && $w_px > 0 && $w_px <= self::MAX_LINE_THICKNESS_PX && $has_length( $h ) && ! ( $h_px !== null && $h_px <= $w_px ) ) {
                return array( 'direction' => 'vertical', 'thickness' => self::num( $w_px ) . 'px', 'length' => $h, 'color' => $paint['color'], 'style' => 'solid' );
            }
            return null;
        }

        $sides = array();
        foreach ( array( 'top', 'right', 'bottom', 'left' ) as $s ) {
            $b = self::resolve_border_side( $decls, $s );
            if ( self::is_visible_border( $b ) ) {
                $sides[] = array( 'side' => $s, 'b' => $b );
            }
        }
        if ( count( $sides ) !== 1 ) {
            return null;
        }
        $side      = $sides[0]['side'];
        $b         = $sides[0]['b'];
        $thickness = self::border_width_px( $b['width'] );
        if ( $thickness === null || $thickness > self::MAX_LINE_THICKNESS_PX ) {
            return null;
        }
        $style = in_array( $b['style'], self::BORDER_STYLES, true ) ? $b['style'] : 'solid';
        $color = $b['color'] && ! preg_match( '/^currentcolor$/i', $b['color'] ) ? $b['color'] : null;
        if ( $side === 'top' || $side === 'bottom' ) {
            if ( $h_px !== null && $h_px > 0 ) {
                return null;
            }
            return array( 'direction' => 'horizontal', 'thickness' => self::num( $thickness ) . 'px', 'length' => $has_length( $w ) ? $w : '100%', 'color' => $color, 'style' => $style );
        }
        if ( ! $has_length( $h ) || ( $w_px !== null && $w_px > 0 ) ) {
            return null;
        }
        return array( 'direction' => 'vertical', 'thickness' => self::num( $thickness ) . 'px', 'length' => $h, 'color' => $color, 'style' => $style );
    }

    /** JS number → string: 2 not 2.0, 0.5 stays 0.5. */
    public static function num( $n ) {
        if ( is_float( $n ) && floor( $n ) == $n && abs( $n ) < 1e15 ) { // phpcs:ignore Universal.Operators.StrictComparisons
            return (string) (int) $n;
        }
        $s = rtrim( rtrim( sprintf( '%.10F', $n ), '0' ), '.' );
        return $s === '-0' ? '0' : $s;
    }

    /** An empty div/span/hr the markup did not pin to some unrelated element type. */
    public static function is_line_candidate( $el ) {
        $tag = SNN_Bricks_Dom::tag( $el );
        if ( ! in_array( $tag, array( 'div', 'span', 'hr' ), true ) ) {
            return false;
        }
        $explicit = SNN_Bricks_Dom::attr( $el, 'data-bricks' );
        if ( $explicit && ! in_array( $explicit, array( 'block', 'div', 'text-basic', 'divider' ), true ) ) {
            return false;
        }
        if ( SNN_Bricks_Dom::children( $el ) || trim( SNN_Bricks_Dom::text( $el ) ) !== '' ) {
            return false;
        }
        if ( self::parse_fa_icon( (string) SNN_Bricks_Dom::attr( $el, 'class' ) ) ) {
            return false;
        }
        return true;
    }

    /** Class names worn ONLY by elements that compile to dividers → line direction. */
    public static function find_line_only_classes( $body, $rule_set, $plan ) {
        $line_classes  = array();
        $other_classes = array();
        $base_width    = $plan['base']['width'];
        foreach ( SNN_Bricks_Dom::descendants( $body ) as $el ) {
            $classes = array_values( array_filter( preg_split( '/\s+/', (string) SNN_Bricks_Dom::attr( $el, 'class' ) ), function ( $c ) {
                return $c !== '' && ! self::is_fa_token( $c );
            } ) );
            if ( ! $classes ) {
                continue;
            }
            $line = self::is_line_candidate( $el ) ? self::analyze_line( SNN_Bricks_Cascade::resolve_decls_at( $el, $rule_set, $base_width ) ) : null;
            foreach ( $classes as $c ) {
                if ( ! $line ) {
                    $other_classes[ $c ] = true;
                    continue;
                }
                if ( ! isset( $line_classes[ $c ] ) ) {
                    $line_classes[ $c ] = $line['direction'];
                }
            }
        }
        foreach ( array_keys( $other_classes ) as $c ) {
            unset( $line_classes[ $c ] );
        }
        return $line_classes;
    }

    private static function is_line_paint_prop( $prop, $direction ) {
        if ( preg_match( '/^background(-color|-image)?$/', $prop ) ) {
            return true;
        }
        if ( preg_match( '/^border(-(top|right|bottom|left))?(-(width|style|color))?$/', $prop ) ) {
            return true;
        }
        return $direction === 'vertical' ? $prop === 'width' : $prop === 'height';
    }

    /** Remove line-drawing declarations from a class's base ".name {}" rule. */
    public static function strip_line_paint_from_css( $css, $class_name, $direction ) {
        $target = '.' . $class_name;
        $out    = '';
        $i      = 0;
        $len    = strlen( $css );
        while ( $i < $len ) {
            $brace = strpos( $css, '{', $i );
            if ( $brace === false ) {
                $out .= substr( $css, $i );
                break;
            }
            $selector = substr( $css, $i, $brace - $i );
            $depth    = 1;
            $end      = $brace + 1;
            while ( $depth > 0 && $end < $len ) {
                if ( $css[ $end ] === '{' ) {
                    $depth++;
                } elseif ( $css[ $end ] === '}' ) {
                    $depth--;
                }
                $end++;
            }
            if ( trim( $selector ) === $target ) {
                $kept = array_values( array_filter( SNN_Bricks_Css::split_declarations( substr( $css, $brace + 1, $end - 1 - $brace - 1 ) ), function ( $d ) use ( $direction ) {
                    $at = strpos( $d, ':' );
                    return $at === false || ! self::is_line_paint_prop( strtolower( trim( substr( $d, 0, $at ) ) ), $direction );
                } ) );
                $out .= $selector . "{\n" . implode( "\n", array_map( function ( $d ) {
                    return '  ' . $d . ';';
                }, $kept ) ) . ( $kept ? "\n" : '' ) . '}';
            } else {
                $out .= substr( $css, $i, $end - $i );
            }
            $i = $end;
        }
        return $out;
    }

    /** CSS colour → Bricks colour object; a var() naming a palette colour links to that swatch. */
    public static function to_bricks_color( $value, $ctx = null ) {
        $v = trim( (string) $value );
        if ( preg_match( '/^var\(\s*(--[\w-]+)\s*(?:,[^)]*)?\)$/', $v, $m ) ) {
            $swatch = self::find_palette_color( $m[1], $ctx );
            return $swatch ? array( 'id' => $swatch['id'], 'raw' => $swatch['raw'], 'light' => $swatch['light'] ?? null ) : array( 'raw' => $v );
        }
        if ( preg_match( '/^#[0-9a-f]{3,8}$/i', $v ) ) {
            return array( 'hex' => $v );
        }
        if ( preg_match( '/^rgba?\(/i', $v ) ) {
            return array( 'rgb' => $v );
        }
        return array( 'raw' => $v );
    }

    public static function palette( $ctx = null ) {
        if ( $ctx && $ctx->palette !== null ) {
            return $ctx->palette;
        }
        $palette = get_option( defined( 'BRICKS_DB_COLOR_PALETTE' ) ? BRICKS_DB_COLOR_PALETTE : 'bricks_color_palette', array() );
        $palette = is_array( $palette ) ? $palette : array();
        if ( $ctx ) {
            $ctx->palette = $palette;
        }
        return $palette;
    }

    private static function find_palette_color( $css_var, $ctx ) {
        $raw = 'var(' . $css_var . ')';
        foreach ( self::palette( $ctx ) as $palette ) {
            foreach ( (array) ( $palette['colors'] ?? array() ) as $c ) {
                if ( ( $c['raw'] ?? null ) === $raw ) {
                    return $c;
                }
            }
        }
        return null;
    }

    /** A declared colour that really paints — not inherit/currentColor/none. */
    public static function explicit_paint( $value ) {
        $v = trim( (string) $value );
        if ( $v === '' || preg_match( '/^(inherit|initial|unset|revert|currentcolor|none|transparent)$/i', $v ) ) {
            return null;
        }
        return $v;
    }

    /** A declared length worth pinning — not inherit/auto. */
    public static function explicit_length( $value ) {
        $v = trim( (string) $value );
        if ( $v === '' || preg_match( '/^(inherit|initial|unset|revert|auto)$/i', $v ) ) {
            return null;
        }
        return $v;
    }

    // ── icon size and colour ───────────────────────────────────────────────

    private static function round_css( $n ) {
        return self::num( round( $n * 1000 ) / 1000 );
    }

    private static function scale_font_size( $value, $factor ) {
        if ( abs( $factor - 1 ) < 1e-9 ) {
            return $value;
        }
        if ( preg_match( '/^(-?\d*\.?\d+)(px|rem)$/i', $value, $m ) ) {
            return self::round_css( (float) $m[1] * $factor ) . $m[2];
        }
        if ( preg_match( '/^[a-z-]+$/i', $value ) ) {
            return $value;
        }
        return 'calc(' . $value . ' * ' . self::round_css( $factor ) . ')';
    }

    /** The font-size an element actually renders at, compounding em/% steps on the way up. */
    public static function resolve_effective_font_size( $el, $rule_set, $width ) {
        $factor = 1.0;
        for ( $node = $el; SNN_Bricks_Dom::is_element( $node ); $node = $node->parentNode ) {
            $fs    = trim( SNN_Bricks_Cascade::resolve_decls_at( $node, $rule_set, $width )['font-size'] ?? '' );
            $lower = strtolower( $fs );
            if ( $fs === '' || $lower === 'inherit' || $lower === 'unset' ) {
                continue;
            }
            if ( preg_match( '/^(-?\d*\.?\d+)(em|%)$/', $lower, $m ) ) {
                $factor *= (float) $m[1] / ( $m[2] === '%' ? 100 : 1 );
                continue;
            }
            return self::scale_font_size( $fs, $factor );
        }
        return self::scale_font_size( '16px', $factor );
    }

    /** The colour an element really renders in: its own, or the nearest ancestor's. */
    public static function resolve_effective_color( $el, $rule_set, $width ) {
        for ( $node = $el; SNN_Bricks_Dom::is_element( $node ); $node = $node->parentNode ) {
            $c = trim( SNN_Bricks_Cascade::resolve_decls_at( $node, $rule_set, $width )['color'] ?? '' );
            if ( $c === '' || preg_match( '/^(inherit|unset|currentcolor)$/i', $c ) ) {
                continue;
            }
            if ( preg_match( '/^initial$/i', $c ) ) {
                return null;
            }
            return $c;
        }
        return null;
    }

    /**
     * Every state rule that reaches an icon's colour (or its own font-size),
     * rewritten against %root% so it outranks the pinned base value.
     */
    public static function icon_state_css( $node, $ctx, $holder = null, $node_target = '', $color_target = '' ) {
        $states = $ctx->rule_set->states;
        if ( ! $states ) {
            return '';
        }
        $base_width = $ctx->plan['base']['width'];
        $chain      = array();
        for ( $n = $node; SNN_Bricks_Dom::is_element( $n ); $n = $n->parentNode ) {
            $chain[] = $n;
        }
        $holder_depth = $holder ? array_search( $holder, $chain, true ) : 0;
        if ( $holder_depth === false ) {
            $holder_depth = -1;
        }
        $base_decls = array_map( function ( $n ) use ( $ctx, $base_width ) {
            return SNN_Bricks_Cascade::resolve_decls_at( $n, $ctx->rule_set, $base_width );
        }, $chain );
        $rules      = array();
        $transition = null;

        $applicable = array_values( array_filter( $states, function ( $r ) use ( $base_width ) {
            return SNN_Bricks_Cascade::rule_applies_at( $r, $base_width );
        } ) );
        usort( $applicable, function ( $a, $b ) {
            return ( $a['spec'] - $b['spec'] ) ?: ( $a['order'] - $b['order'] );
        } );

        foreach ( $applicable as $r ) {
            $structural = trim( preg_replace( SNN_Bricks_Cascade::STATE_PSEUDO, '', $r['selector'] ) );
            if ( $structural === '' ) {
                $structural = '*';
            }
            $depth = -1;
            foreach ( $chain as $i => $c ) {
                if ( SNN_Bricks_Dom::matches( $c, $structural ) ) {
                    $depth = $i;
                    break;
                }
            }
            if ( $depth === -1 ) {
                continue;
            }
            if ( $holder && $depth > 0 && $depth < $holder_depth ) {
                continue;
            }

            $rd    = array_merge( $r['decls'], $r['important'] );
            $decls = array();
            if ( ! empty( $rd['color'] ) ) {
                $closer_sets_color = false;
                for ( $k = 0; $k < $depth; $k++ ) {
                    if ( ! empty( $base_decls[ $k ]['color'] ) ) {
                        $closer_sets_color = true;
                        break;
                    }
                }
                if ( ! $closer_sets_color ) {
                    $decls[] = 'color: ' . $rd['color'];
                    $decls[] = 'fill: ' . $rd['color'];
                }
            }
            if ( ! empty( $rd['font-size'] ) && $depth === 0 ) {
                $decls[] = 'font-size: ' . $rd['font-size'];
            }
            if ( ! $decls ) {
                continue;
            }

            if ( ! $holder ) {
                $sel = $depth === 0 ? '%root%:is(' . $r['selector'] . ')' : ':is(' . $r['selector'] . ') %root%';
            } elseif ( $depth === 0 ) {
                $sel = '%root% ' . $node_target . ':is(' . $r['selector'] . ')';
            } elseif ( $depth === $holder_depth ) {
                $sel = '%root%:is(' . $r['selector'] . ') ' . $color_target;
            } else {
                $sel = ':is(' . $r['selector'] . ') %root% ' . $color_target;
            }
            $rules[] = $sel . " {\n  " . implode( ";\n  ", $decls ) . ";\n}";
            $t = $base_decls[ $depth ]['transition'] ?? '';
            if ( ! $transition && $t !== '' && preg_match( '/color|all/i', $t ) ) {
                $transition = trim( $t );
            }
        }
        if ( ! $rules ) {
            return '';
        }
        $self = $holder ? '%root% ' . $color_target : '%root%';
        return ( $transition ? $self . " {\n  transition: " . $transition . ";\n}\n" : '' ) . implode( "\n", $rules );
    }

    /** Pin a font icon's size, colour and state styling as native settings. */
    public static function pin_icon_appearance( $el, array &$settings, $ctx ) {
        $size_attr = SNN_Bricks_Dom::attr( $el, 'data-icon-size' );
        if ( $size_attr ) {
            $settings['iconSize'] = $size_attr;
        } else {
            SNN_Bricks_Native_Layout::per_breakpoint( $settings, 'iconSize', $ctx, function ( $w ) use ( $el, $ctx ) {
                return self::resolve_effective_font_size( $el, $ctx->rule_set, $w );
            }, function ( $v ) {
                return $v;
            } );
        }
        SNN_Bricks_Native_Layout::per_breakpoint( $settings, 'iconColor', $ctx, function ( $w ) use ( $el, $ctx ) {
            return self::resolve_effective_color( $el, $ctx->rule_set, $w );
        }, function ( $v ) use ( $ctx ) {
            return self::to_bricks_color( $v, $ctx );
        } );
        $state_css = self::icon_state_css( $el, $ctx );
        if ( $state_css !== '' ) {
            $settings['_cssCustom'] = $state_css;
        }
    }

    // ── SVG assets ─────────────────────────────────────────────────────────

    /** Stable identity of an inline SVG: its XML serialization, minus the builder hint. */
    public static function svg_key( $svg ) {
        $clone = $svg->cloneNode( true );
        if ( $clone->hasAttribute( 'data-bricks' ) ) {
            $clone->removeAttribute( 'data-bricks' );
        }
        return SNN_Bricks_Dom::xml( $clone );
    }

    public static function svg_asset_for( $svg, $ctx ) {
        if ( ! $svg || ! $ctx || ! $ctx->svg_assets ) {
            return null;
        }
        return $ctx->svg_assets[ self::svg_key( $svg ) ] ?? null;
    }

    /** <use href="#x"> pointing outside the SVG breaks once the SVG is a file. */
    public static function svg_is_self_contained( $svg ) {
        foreach ( SNN_Bricks_Dom::find_tags( $svg, array( 'use' ) ) as $use ) {
            $ref = SNN_Bricks_Dom::attr( $use, 'href' );
            if ( $ref === null || $ref === '' ) {
                $ref = (string) SNN_Bricks_Dom::attr( $use, 'xlink:href' );
            }
            if ( strpos( $ref, '#' ) !== 0 ) {
                return false;
            }
            $id    = substr( $ref, 1 );
            $found = false;
            foreach ( SNN_Bricks_Dom::descendants( $svg ) as $d ) {
                if ( SNN_Bricks_Dom::attr( $d, 'id' ) === $id ) {
                    $found = true;
                    break;
                }
            }
            if ( ! $found ) {
                return false;
            }
        }
        return true;
    }

    /** An element whose only content is one <svg>. */
    public static function sole_svg_child( $el ) {
        $kids = SNN_Bricks_Dom::children( $el );
        if ( ! $el || count( $kids ) !== 1 ) {
            return null;
        }
        $child = $kids[0];
        if ( SNN_Bricks_Dom::tag( $child ) !== 'svg' ) {
            return null;
        }
        foreach ( SNN_Bricks_Dom::child_nodes( $el ) as $n ) {
            if ( $n === $child || $n->nodeType === XML_COMMENT_NODE || ( $n->nodeType === XML_TEXT_NODE && trim( $n->textContent ) === '' ) ) {
                continue;
            }
            return null;
        }
        return $child;
    }

    /** The inline SVGs a build can turn into Bricks elements or icon settings. */
    public static function svg_upload_candidates( $root ) {
        $out = array();
        foreach ( SNN_Bricks_Dom::find_tags( $root, array( 'svg' ) ) as $svg ) {
            $p = SNN_Bricks_Dom::parent( $svg );
            if ( $p && SNN_Bricks_Dom::closest( $p, 'svg' ) ) {
                continue;
            }
            if ( ! self::svg_is_self_contained( $svg ) ) {
                continue;
            }
            $child  = $svg;
            $node   = SNN_Bricks_Dom::parent( $svg );
            $holder = null;
            $ok     = true;
            while ( $node && SNN_Bricks_Dom::tag( $node ) !== 'body' ) {
                $name        = self::bricks_name_for( $node );
                $transparent = $child === $svg && self::sole_svg_child( $node ) === $svg
                    && in_array( $name, array( 'custom-html-css-script', 'text-basic', 'icon' ), true );
                if ( SNN_Bricks_Native_Layout::is_layout( $name ) || $transparent ) {
                    $child = $node;
                    $node  = SNN_Bricks_Dom::parent( $node );
                    continue;
                }
                if ( ( $name === 'button' || $name === 'text-link' ) && ! $holder ) {
                    $holder = $node;
                    $child  = $node;
                    $node   = SNN_Bricks_Dom::parent( $node );
                    continue;
                }
                if ( $holder && ( $name === 'text-basic' || $name === 'icon' ) ) {
                    $child = $node;
                    $node  = SNN_Bricks_Dom::parent( $node );
                    continue;
                }
                $ok = false;
                break;
            }
            if ( $ok ) {
                $out[] = $svg;
            }
        }
        return $out;
    }

    // ── icons in buttons and text links ────────────────────────────────────

    /** Climb single-child wrappers so they leave with the icon. */
    public static function icon_removal_node( $node, $root ) {
        $cur = $node;
        while ( $cur->parentNode && $cur->parentNode !== $root && SNN_Bricks_Dom::is_element( $cur->parentNode ) ) {
            $p    = $cur->parentNode;
            $hint = SNN_Bricks_Dom::attr( $p, 'data-bricks' );
            if ( count( SNN_Bricks_Dom::children( $p ) ) !== 1 ) {
                break;
            }
            if ( trim( SNN_Bricks_Dom::text( $p ) ) !== trim( SNN_Bricks_Dom::text( $cur ) ) ) {
                break;
            }
            if ( ! in_array( SNN_Bricks_Dom::tag( $p ), self::ICON_WRAPPER_TAGS, true ) && $hint !== 'custom-html-css-script' && $hint !== 'icon' ) {
                break;
            }
            $cur = $p;
        }
        return $cur;
    }

    /** The icon a button or text link should carry natively. */
    public static function find_control_icon( $el, $ctx ) {
        $data_icon = SNN_Bricks_Dom::attr( $el, 'data-icon' );
        if ( $data_icon ) {
            $obj = self::parse_fa_icon( $data_icon );
            if ( $obj ) {
                return array(
                    'kind' => 'font', 'icon' => $obj, 'node' => null, 'removal' => null,
                    'before' => '', 'after' => SNN_Bricks_Dom::text( $el ),
                    'position' => SNN_Bricks_Dom::attr( $el, 'data-icon-position' ) ?: 'left',
                );
            }
        }
        $candidates = array();
        foreach ( SNN_Bricks_Dom::find_tags( $el, array( 'i', 'span', 'svg' ) ) as $node ) {
            $p = SNN_Bricks_Dom::parent( $node );
            if ( $p && SNN_Bricks_Dom::closest( $p, 'svg' ) ) {
                continue;
            }
            if ( SNN_Bricks_Dom::tag( $node ) === 'svg' ) {
                $asset = self::svg_asset_for( $node, $ctx );
                if ( $asset ) {
                    $candidates[] = array( 'kind' => 'svg', 'node' => $node, 'asset' => $asset );
                }
                continue;
            }
            $obj = self::parse_fa_icon( (string) SNN_Bricks_Dom::attr( $node, 'class' ) );
            if ( $obj && trim( SNN_Bricks_Dom::text( $node ) ) === '' && ! SNN_Bricks_Dom::children( $node ) ) {
                $candidates[] = array( 'kind' => 'font', 'node' => $node, 'icon' => $obj );
            }
        }
        foreach ( $candidates as &$c ) {
            $c['removal'] = self::icon_removal_node( $c['node'], $el );
            $around       = SNN_Bricks_Dom::text_around( $el, $c['removal'] );
            $c['before']  = $around['before'];
            $c['after']   = $around['after'];
        }
        unset( $c );
        $pick = null;
        foreach ( $candidates as $c ) {
            if ( trim( $c['before'] ) === '' || trim( $c['after'] ) === '' ) {
                $pick = $c;
                break;
            }
        }
        if ( ! $pick && $candidates ) {
            $pick = $candidates[0];
        }
        if ( ! $pick ) {
            return null;
        }
        $pick['position'] = SNN_Bricks_Dom::attr( $el, 'data-icon-position' ) ?: ( trim( $pick['before'] ) !== '' ? 'right' : 'left' );
        return $pick;
    }

    /** Space between label and icon, as the HTML laid it out. */
    public static function icon_gap_from_css( array $decls, array $found ) {
        $display = strtolower( $decls['display'] ?? '' );
        if ( preg_match( '/flex|grid/', $display ) ) {
            $col = $decls['column-gap'] ?? '';
            if ( $col === '' && ( $decls['gap'] ?? '' ) !== '' ) {
                $parts = SNN_Bricks_Css::split_tokens( $decls['gap'] );
                $col   = $parts[1] ?? $parts[0];
            }
            return $col !== '' ? trim( $col ) : '0';
        }
        $spaced = $found['position'] === 'right' ? preg_match( '/\s$/', $found['before'] ) : preg_match( '/^\s/', $found['after'] );
        return $spaced ? '0.25em' : '0';
    }

    /** ".card:hover > .btn-arrow" → { prefix: ".card:hover", last: ".btn-arrow" }. */
    public static function split_last_compound( $selector ) {
        $sel   = trim( (string) $selector );
        $len   = strlen( $sel );
        $depth = 0;
        $quote = null;
        $cut   = -1;
        for ( $i = 0; $i < $len; $i++ ) {
            $ch = $sel[ $i ];
            if ( $quote !== null ) {
                if ( $ch === $quote ) {
                    $quote = null;
                }
                continue;
            }
            if ( $ch === '"' || $ch === "'" ) {
                $quote = $ch;
                continue;
            }
            if ( $ch === '(' || $ch === '[' ) {
                $depth++;
            } elseif ( $ch === ')' || $ch === ']' ) {
                $depth--;
            } elseif ( $depth === 0 && ( $ch === ' ' || $ch === '>' || $ch === '+' || $ch === '~' ) ) {
                $cut = $i;
            }
        }
        if ( $cut === -1 ) {
            return array( 'prefix' => '', 'last' => $sel );
        }
        return array(
            'prefix' => trim( preg_replace( '/[\s>+~]+$/', '', substr( $sel, 0, $cut ) ) ),
            'last'   => trim( substr( $sel, $cut + 1 ) ),
        );
    }

    /** Class rules that styled the icon node or its removed wrapper, re-anchored on %root%. */
    public static function rewrite_icon_class_rules( $holder, array $found, $ctx, $icon_selector ) {
        if ( empty( $found['node'] ) ) {
            return '';
        }
        $tokens  = array();
        $chain   = array();
        $collect = function ( $node ) use ( &$tokens ) {
            foreach ( preg_split( '/\s+/', (string) SNN_Bricks_Dom::attr( $node, 'class' ) ) as $c ) {
                if ( $c !== '' && ! self::is_fa_token( $c ) ) {
                    $tokens[ $c ] = true;
                }
            }
        };
        if ( $found['kind'] === 'font' ) {
            $collect( $found['node'] );
            $chain[] = $found['node'];
        }
        for ( $n = SNN_Bricks_Dom::parent( $found['node'] ); $n && $n !== $holder && ! empty( $found['removal'] ) && SNN_Bricks_Dom::contains( $found['removal'], $n ); $n = SNN_Bricks_Dom::parent( $n ) ) {
            $collect( $n );
            $chain[] = $n;
        }
        if ( ! $tokens ) {
            return '';
        }

        $all = $ctx->rule_set->all;
        usort( $all, function ( $a, $b ) {
            return $a['order'] - $b['order'];
        } );
        $out = array();
        foreach ( $all as $r ) {
            $parts    = self::split_last_compound( $r['selector'] );
            $mentions = false;
            SNN_Bricks_Css::map_selector_classes( $parts['last'], function ( $n ) use ( $tokens, &$mentions ) {
                if ( isset( $tokens[ $n ] ) ) {
                    $mentions = true;
                }
                return null;
            } );
            if ( ! $mentions ) {
                continue;
            }
            $structural = trim( preg_replace( SNN_Bricks_Cascade::PSEUDO_ELEMENT, '', preg_replace( SNN_Bricks_Cascade::STATE_PSEUDO, '', $r['selector'] ) ) );
            if ( $structural === '' ) {
                $structural = '*';
            }
            $reaches = false;
            foreach ( $chain as $n ) {
                if ( SNN_Bricks_Dom::matches( $n, $structural ) ) {
                    $reaches = true;
                    break;
                }
            }
            if ( ! $reaches ) {
                continue;
            }

            preg_match_all( '/::?[\w-]+(\([^)]*\))?/', $parts['last'], $pm );
            $pseudo = implode( '', $pm[0] );
            if ( $parts['prefix'] === '' ) {
                $selector = '%root% ' . $icon_selector . $pseudo;
            } else {
                $prefix_structural = trim( preg_replace( SNN_Bricks_Cascade::STATE_PSEUDO, '', $parts['prefix'] ) );
                if ( $prefix_structural === '' ) {
                    $prefix_structural = '*';
                }
                $on_root  = SNN_Bricks_Dom::matches( $holder, $prefix_structural );
                $selector = $on_root
                    ? '%root%:is(' . $parts['prefix'] . ') ' . $icon_selector . $pseudo
                    : ':is(' . $parts['prefix'] . ') %root% ' . $icon_selector . $pseudo;
            }
            $rule = $selector . " {\n" . SNN_Bricks_Css::format_css_body( $r['raw'] ) . "\n}";
            foreach ( array_reverse( (array) ( $r['media'] ?? array() ) ) as $q ) {
                $rule = '@media ' . $q . " {\n" . $rule . "\n}";
            }
            $out[] = $rule;
        }
        return implode( "\n", $out );
    }

    /** A button's iconTypography per breakpoint: font-size and colour the icon itself declares. */
    public static function icon_typography_per_breakpoint( array &$settings, $ctx, callable $size, callable $color ) {
        $plan       = $ctx->plan;
        $base_size  = $size( $plan['base']['width'] );
        $base_color = $color( $plan['base']['width'] );
        $base       = array();
        if ( $base_size ) {
            $base['font-size'] = $base_size;
        }
        if ( $base_color ) {
            $base['color'] = self::to_bricks_color( $base_color, $ctx );
        }
        if ( $base ) {
            $settings['iconTypography'] = $base;
        }
        $prev_size  = $base_size;
        $prev_color = $base_color;
        foreach ( $plan['steps'] as $step ) {
            $fs = $size( $step['width'] );
            $c  = $color( $step['width'] );
            $t  = array();
            if ( $fs && $fs !== $prev_size ) {
                $t['font-size'] = $fs;
            }
            if ( $c && $c !== $prev_color ) {
                $t['color'] = self::to_bricks_color( $c, $ctx );
            }
            if ( $t ) {
                $settings[ 'iconTypography:' . $step['key'] ] = $t;
            }
            if ( $fs ) {
                $prev_size = $fs;
            }
            if ( $c ) {
                $prev_color = $c;
            }
        }
    }

    /** Fill a button / text link's native icon settings from the lifted icon. */
    public static function apply_control_icon( array &$element, $el, array $found, array $own_decls, $ctx ) {
        $s         = &$element['settings'];
        $is_button = $element['name'] === 'button';
        $tag_sel   = $found['kind'] === 'svg' ? 'svg' : 'i';
        $node_sel  = $is_button ? $tag_sel : '.icon > ' . $tag_sel;

        if ( $found['kind'] === 'svg' ) {
            $s['icon'] = array(
                'library' => 'svg',
                'svg'     => array( 'id' => $found['asset']['id'], 'filename' => $found['asset']['filename'], 'url' => $found['asset']['url'] ),
            );
            $d = SNN_Bricks_Cascade::resolve_decls_at( $found['node'], $ctx->rule_set, $ctx->plan['base']['width'] );
            $w = self::explicit_length( $d['width'] ?? '' ) ?: self::explicit_length( SNN_Bricks_Dom::attr( $found['node'], 'width' ) );
            $h = self::explicit_length( $d['height'] ?? '' ) ?: self::explicit_length( SNN_Bricks_Dom::attr( $found['node'], 'height' ) );
            if ( $w ) {
                $s['icon']['width'] = $w;
            }
            if ( $h ) {
                $s['icon']['height'] = $h;
            }
            $fill   = self::explicit_paint( $d['fill'] ?? '' );
            $stroke = self::explicit_paint( $d['stroke'] ?? '' );
            if ( $fill ) {
                $s['icon']['fill'] = self::to_bricks_color( $fill, $ctx );
            }
            if ( $stroke ) {
                $s['icon']['stroke'] = self::to_bricks_color( $stroke, $ctx );
            }
        } else {
            $s['icon'] = $found['icon'];
            if ( ! empty( $found['node'] ) ) {
                $node  = $found['node'];
                $own   = function ( $w ) use ( $node, $ctx ) {
                    return SNN_Bricks_Cascade::resolve_decls_at( $node, $ctx->rule_set, $w );
                };
                $size  = function ( $w ) use ( $own ) {
                    return self::explicit_length( $own( $w )['font-size'] ?? '' );
                };
                $color = function ( $w ) use ( $own ) {
                    return self::explicit_paint( $own( $w )['color'] ?? '' );
                };
                if ( $is_button ) {
                    self::icon_typography_per_breakpoint( $s, $ctx, $size, $color );
                } else {
                    SNN_Bricks_Native_Layout::per_breakpoint( $s, 'iconSize', $ctx, $size, function ( $v ) {
                        return $v;
                    } );
                    SNN_Bricks_Native_Layout::per_breakpoint( $s, 'iconColor', $ctx, $color, function ( $v ) use ( $ctx ) {
                        return self::to_bricks_color( $v, $ctx );
                    } );
                }
                if ( $color( $ctx->plan['base']['width'] ) ) {
                    $state_css = self::icon_state_css( $node, $ctx, $el, $node_sel, $is_button ? 'i' : '.icon' );
                    if ( $state_css !== '' ) {
                        $s['_cssCustom'] = ( ! empty( $s['_cssCustom'] ) ? $s['_cssCustom'] . "\n" : '' ) . $state_css;
                    }
                }
            }
        }
        $s['iconPosition'] = $found['position'];

        $gap_key = $is_button ? 'iconGap' : 'gap';
        $s[ $gap_key ] = SNN_Bricks_Dom::attr( $el, 'data-icon-gap' ) ?: self::icon_gap_from_css( $own_decls, $found );

        $class_css = self::rewrite_icon_class_rules( $el, $found, $ctx, $node_sel );
        if ( $class_css !== '' ) {
            $s['_cssCustom'] = ( ! empty( $s['_cssCustom'] ) ? $s['_cssCustom'] . "\n" : '' ) . $class_css;
        }
        unset( $s );
    }
}
