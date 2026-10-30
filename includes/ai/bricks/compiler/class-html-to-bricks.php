<?php
/**
 * HTML → Bricks compiler, server side.
 *
 * PHP port of the Bricks AI agent's class-based compiler
 * (html-to-bricks-translation.php + the build orchestration in
 * ai-agent-and-chat-bricks.php: compileSectionBySection, planClassNames,
 * compileRegeneratedSection, parseHTMLIntoSections, validateAndFixBricksJSON).
 *
 * Architecture, unchanged from the JS:
 *  - native CSS classes become Bricks global classes, their CSS kept verbatim
 *    in settings._cssCustom;
 *  - the layout-critical part of the cascade is resolved per breakpoint and
 *    written as native element settings (ID specificity beats .brxe-* defaults);
 *  - icons, dividers, SVGs, buttons and links get their native settings.
 *
 * The compiler never writes anything. SNN_Bricks_Writer persists its output.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** 6-lowercase-letter Bricks ids, unique against everything the build touches. */
class SNN_Bricks_Id_Pool {
    const LETTERS = 'abcdefghijklmnopqrstuvwxyz';
    private $used = array();

    public function __construct( array $taken = array() ) {
        foreach ( $taken as $id ) {
            $this->used[ (string) $id ] = true;
        }
    }

    public function reserve( $id ) {
        $this->used[ (string) $id ] = true;
    }

    public function has( $id ) {
        return isset( $this->used[ (string) $id ] );
    }

    public function next() {
        do {
            $id = '';
            for ( $i = 0; $i < 6; $i++ ) {
                $id .= self::LETTERS[ random_int( 0, 25 ) ];
            }
        } while ( isset( $this->used[ $id ] ) );
        $this->used[ $id ] = true;
        return $id;
    }
}

class SNN_Bricks_Html_To_Bricks {

    /** Bricks' text-basic tag control only offers these; anything else needs customTag. */
    const TEXT_TAG_OPTIONS = array( 'div', 'p', 'span', 'figcaption', 'address', 'figure' );

    // Scripts are not skipped: each one is kept as embedded code (see keep_script()).
    const SKIP_TAGS = array( 'style', 'meta', 'link', 'title', 'br', 'wbr' );

    // Settings that carry the design's typography and paint, never layout (native layout owns that).
    const PAINT_OR_TYPE_PATTERN = '/^(line-height|letter-spacing|color|font-|text-|text-transform|text-decoration|background|border|box-shadow|opacity|fill|stroke|filter|cursor|transition)/';

    // Text-bearing Bricks elements that receive inherited typography and contextual rules.
    const TEXT_EXTRA_NAMES = array( 'heading', 'text-basic', 'text', 'text-link', 'button' );

    const IGNORED_ATTRS = array(
        'id', 'class', 'style', 'data-bricks',
        'data-loop', 'data-loop-posts-per-page', 'data-loop-orderby', 'data-loop-order',
        'data-icon', 'data-icon-position', 'data-icon-gap', 'data-icon-size',
        'data-animate', 'data-animate-trigger', 'data-animate-duration', 'data-animate-delay', 'data-animate-offset',
        'data-href', 'href', 'src', 'alt', 'target', 'rel',
    );

    // ── STEP 2: one fragment → Bricks JSON ─────────────────────────────────

    /**
     * compileHtmlToBricksJson().
     *
     * @param string                     $html
     * @param array|null                 $class_name_to_id className → global class id (pre-computed from the full page)
     * @param SNN_Bricks_Rule_Set|null   $rule_set         cascade of the full design CSS
     * @param array                      $options          svg_assets, ids (SNN_Bricks_Id_Pool), plan, palette
     * @return array{content: array, globalClasses: array, classNameToId: array}
     */
    public static function compile( $html, $class_name_to_id = null, $rule_set = null, array $options = array() ) {
        $parsed  = SNN_Bricks_Dom::parse( $html );
        $body    = $parsed['body'];
        $ids     = $options['ids'] ?? new SNN_Bricks_Id_Pool();
        $content = array();

        $precomputed  = $class_name_to_id !== null;
        $class_to_id  = $precomputed ? $class_name_to_id : array();
        $class_map    = array();

        if ( ! $precomputed ) {
            $local_css = '';
            // doc.querySelectorAll('style'): a leading <style> is parsed into <head>.
            foreach ( SNN_Bricks_Dom::find_tags( $parsed['doc']->documentElement, array( 'style' ) ) as $style_el ) {
                $css        = SNN_Bricks_Dom::text( $style_el );
                $local_css .= $css . "\n";
                foreach ( SNN_Bricks_Css::parse_css_rules( $css ) as $class_name => $css_body ) {
                    if ( ! isset( $class_map[ $class_name ] ) ) {
                        $gid                        = $ids->next();
                        $class_map[ $class_name ]   = array( 'id' => $gid, 'css' => '' );
                        $class_to_id[ $class_name ] = $gid;
                    }
                    $class_map[ $class_name ]['css'] .= $css_body . ' ';
                }
            }
            self::register_bare_classes( $body, $class_to_id, $ids, $class_map );
            if ( ! $rule_set ) {
                $rule_set = SNN_Bricks_Cascade::build_rule_set( $local_css );
            }
        }
        if ( ! $rule_set ) {
            $rule_set = new SNN_Bricks_Rule_Set();
        }

        $plan = $options['plan'] ?? SNN_Bricks_Native_Layout::breakpoint_plan();
        $ctx  = new SNN_Bricks_Compile_Context( $rule_set, $plan, $options['svg_assets'] ?? array(), $options['palette'] ?? null );

        if ( ! $precomputed ) {
            $line_only = SNN_Bricks_Features::find_line_only_classes( $body, $rule_set, $plan );
            foreach ( $line_only as $cn => $direction ) {
                if ( isset( $class_map[ $cn ] ) ) {
                    $class_map[ $cn ]['css'] = SNN_Bricks_Features::strip_line_paint_from_css( $class_map[ $cn ]['css'], $cn, $direction );
                }
            }
        }

        foreach ( SNN_Bricks_Dom::children( $body ) as $el ) {
            self::walk( $el, 0, $ctx, $class_to_id, $ids, $content );
        }

        $global_classes = array();
        foreach ( $class_map as $class_name => $gc ) {
            $global_classes[] = array(
                'id'       => $gc['id'],
                'name'     => $class_name,
                'settings' => array( '_cssCustom' => $gc['css'] ),
            );
        }

        return array(
            'content'       => $content,
            'globalClasses' => $global_classes,
            'classNameToId' => $class_to_id,
            'scripts'       => $ctx->scripts,
        );
    }

    /** Register class names worn in markup with no CSS rule of their own. */
    public static function register_bare_classes( $root, array &$class_to_id, $ids, &$class_map = null ) {
        $added = 0;
        foreach ( SNN_Bricks_Dom::descendants( $root ) as $el ) {
            $attr = SNN_Bricks_Dom::attr( $el, 'class' );
            if ( $attr === null ) {
                continue;
            }
            foreach ( preg_split( '/\s+/', $attr ) as $cn ) {
                if ( $cn === '' || SNN_Bricks_Features::is_fa_token( $cn ) || isset( $class_to_id[ $cn ] ) ) {
                    continue;
                }
                $gid               = $ids->next();
                $class_to_id[ $cn ] = $gid;
                if ( is_array( $class_map ) ) {
                    $class_map[ $cn ] = array( 'id' => $gid, 'css' => '' );
                }
                $added++;
            }
        }
        return $added;
    }

    /**
     * A <script> becomes a custom HTML element holding the tag as written, so it runs on the
     * page. Bricks cannot edit it as an element, so the build reports each one.
     */
    private static function keep_script( $el, $parent_id, $ctx, $ids, array &$content ) {
        $html = SNN_Bricks_Dom::outer_html( $el );
        $src  = (string) SNN_Bricks_Dom::attr( $el, 'src' );
        $id   = $ids->next();

        $content[] = array(
            'id'          => $id,
            'name'        => 'custom-html-css-script',
            'parent'      => $parent_id,
            'children'    => array(),
            'settings'    => array( 'content' => $html, '_display' => 'contents' ),
            'themeStyles' => array(),
        );
        $ctx->scripts[] = array( 'src' => $src !== '' ? $src : 'inline', 'bytes' => strlen( $html ) );

        return $id;
    }

    /**
     * Custom CSS for one text-bearing element, as Bricks writes it on the element itself.
     * Covers three things a browser gives the design for free: contextual rules that no
     * longer match the converted DOM, body line-height / letter-spacing / colour that text
     * inherits, and the inherit values that stop Bricks' own button defaults overriding it.
     */
    public static function element_css_extras( $el, $bricks_name, $ctx, array $own_decls ) {
        if ( $ctx === null || ! in_array( $bricks_name, self::TEXT_EXTRA_NAMES, true ) ) {
            return '';
        }
        $base  = $ctx->plan['base']['width'];
        $decls = array();

        foreach ( SNN_Bricks_Cascade::contextual_decls_at( $el, $ctx->rule_set, $base ) as $k => $v ) {
            if ( preg_match( self::PAINT_OR_TYPE_PATTERN, $k ) ) {
                $decls[ $k ] = $v;
            }
        }

        foreach ( array( 'line-height', 'letter-spacing', 'color' ) as $k ) {
            if ( isset( $own_decls[ $k ] ) ) {
                continue;
            }
            $value = self::inherited_value( $el, $k, $ctx );
            if ( $value !== null ) {
                $decls[ $k ] = $value;
            }
        }

        if ( $bricks_name === 'button' ) {
            foreach ( array( 'letter-spacing', 'text-align' ) as $k ) {
                if ( ! isset( $own_decls[ $k ] ) && ! isset( $decls[ $k ] ) ) {
                    $decls[ $k ] = 'inherit';
                }
            }
        }

        if ( ! $decls ) {
            return '';
        }
        $lines = array();
        foreach ( $decls as $k => $v ) {
            $lines[] = $k . ': ' . $v . ';';
        }
        return '%root% { ' . implode( ' ', $lines ) . ' }';
    }

    /**
     * The value a browser gives an inherited property: the nearest ancestor that sets it, or
     * the body's value when no ancestor does. Null when nothing sets it.
     */
    private static function inherited_value( $el, $prop, $ctx ) {
        $base   = $ctx->plan['base']['width'];
        $parent = SNN_Bricks_Dom::parent( $el );
        while ( $parent && SNN_Bricks_Dom::is_element( $parent ) && ! in_array( SNN_Bricks_Dom::tag( $parent ), array( 'body', 'html' ), true ) ) {
            $decls = SNN_Bricks_Cascade::resolve_decls_at( $parent, $ctx->rule_set, $base );
            if ( isset( $decls[ $prop ] ) ) {
                return $decls[ $prop ];
            }
            $parent = SNN_Bricks_Dom::parent( $parent );
        }
        $body = SNN_Bricks_Cascade::body_decls( $ctx->rule_set );
        return $body[ $prop ] ?? null;
    }

    /** STEP 2c: walk the DOM and emit minimal Bricks elements. */
    private static function walk( $el, $parent_id, $ctx, array $class_to_id, $ids, array &$content ) {
        if ( ! SNN_Bricks_Dom::is_element( $el ) ) {
            return null;
        }
        $tag = SNN_Bricks_Dom::tag( $el );
        if ( $tag === 'script' ) {
            return self::keep_script( $el, $parent_id, $ctx, $ids, $content );
        }
        if ( in_array( $tag, self::SKIP_TAGS, true ) ) {
            return null;
        }

        $explicit_name = SNN_Bricks_Dom::attr( $el, 'data-bricks' );

        // Accordion, tabs and slider become Bricks' own nested elements, with their items as children.
        $nested = SNN_Bricks_Nested::name_for( $el, $explicit_name );
        if ( $nested ) {
            $walk_child = function ( $node, $parent ) use ( &$content, $ctx, $class_to_id, $ids ) {
                return self::walk( $node, $parent, $ctx, $class_to_id, $ids, $content );
            };
            return SNN_Bricks_Nested::build( $el, $nested, $parent_id, $ids, $content, $walk_child, $class_to_id, $ctx );
        }

        $bricks_name   = SNN_Bricks_Features::bricks_name_for( $el );
        $base_width    = $ctx->plan['base']['width'];
        $own_decls     = SNN_Bricks_Cascade::resolve_decls_at( $el, $ctx->rule_set, $base_width );

        // Decorative lines → Bricks divider, judged by what the CSS draws.
        $line_info = null;
        if ( SNN_Bricks_Features::is_line_candidate( $el ) ) {
            $line_info = SNN_Bricks_Features::analyze_line( $own_decls );
            if ( $line_info ) {
                $bricks_name = 'divider';
            }
        }
        if ( $bricks_name === 'divider' && ! $line_info ) {
            $paint = SNN_Bricks_Features::resolve_background_paint( $own_decls );
            if ( $paint && ! empty( $paint['gradient'] ) ) {
                $bricks_name = 'block';
            }
        }

        // A bare <span class="fas fa-..."> is an icon, not empty text.
        if ( $bricks_name === 'text-basic' && ( ! $explicit_name || $explicit_name === 'text-basic' )
            && ! SNN_Bricks_Dom::children( $el ) && trim( SNN_Bricks_Dom::text( $el ) ) === ''
            && SNN_Bricks_Features::parse_fa_icon( (string) SNN_Bricks_Dom::attr( $el, 'class' ) ) ) {
            $bricks_name = 'icon';
        }

        // Inline SVG → Bricks' native SVG element (from its uploaded file).
        $svg_node = null;
        if ( $bricks_name === 'custom-html-css-script' || $bricks_name === 'svg' ) {
            $candidate = $tag === 'svg' ? $el : SNN_Bricks_Features::sole_svg_child( $el );
            if ( $candidate && SNN_Bricks_Features::svg_asset_for( $candidate, $ctx ) ) {
                $bricks_name = 'svg';
                $svg_node    = $candidate;
            } elseif ( $bricks_name === 'svg' ) {
                $bricks_name = 'custom-html-css-script';
            }
        }

        $id      = $ids->next();
        $element = array(
            'id'          => $id,
            'name'        => $bricks_name,
            'parent'      => $parent_id,
            'children'    => array(),
            'settings'    => array(),
            'themeStyles' => array(),
        );

        // Styling: _cssGlobalClasses. Custom HTML keeps its classes on its own markup.
        $html_class = SNN_Bricks_Dom::attr( $el, 'class' );
        if ( $html_class && $bricks_name !== 'custom-html-css-script' ) {
            $gids = array();
            foreach ( preg_split( '/\s+/', $html_class ) as $cn ) {
                if ( $cn !== '' && ! SNN_Bricks_Features::is_fa_token( $cn ) && ! empty( $class_to_id[ $cn ] ) ) {
                    $gids[] = $class_to_id[ $cn ];
                }
            }
            if ( $gids ) {
                $element['settings']['_cssGlobalClasses'] = $gids;
            }
        }

        if ( $bricks_name === 'custom-html-css-script' ) {
            $element['settings']['_display'] = 'contents';
        } else {
            foreach ( SNN_Bricks_Native_Layout::compute( $el, $bricks_name, $ctx ) as $k => $v ) {
                $element['settings'][ $k ] = $v;
            }
        }

        if ( SNN_Bricks_Native_Layout::is_layout( $bricks_name ) ) {
            SNN_Bricks_Features::apply_layout_tag( $element, $el, $bricks_name, $tag );
        }

        // data-animate → Bricks' own startAnimation interaction (scripts never convert).
        $interactions = SNN_Bricks_Animate::interactions_for( $el, $id );
        if ( $interactions ) {
            $element['settings']['_interactions'] = $interactions;
        }

        // Inherited typography and contextual rules, as the element's own custom CSS.
        $extra_css = self::element_css_extras( $el, $bricks_name, $ctx, $own_decls );
        if ( $extra_css !== '' ) {
            $element['settings']['_cssCustom'] = $extra_css;
        }

        $is_leaf = false;
        switch ( $bricks_name ) {
            case 'heading':
                $element['settings']['text'] = trim( SNN_Bricks_Dom::inner_html( $el ) );
                $element['settings']['tag']  = in_array( $tag, array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ), true ) ? $tag : 'h2';
                $is_leaf = true;
                break;

            case 'text-basic':
                $element['settings']['text'] = trim( SNN_Bricks_Dom::inner_html( $el ) );
                if ( in_array( $tag, self::TEXT_TAG_OPTIONS, true ) ) {
                    $element['settings']['tag'] = $tag;
                } else {
                    $element['settings']['tag']       = 'custom';
                    $element['settings']['customTag'] = $tag;
                }
                $is_leaf = true;
                break;

            case 'text':
                $element['settings']['text'] = trim( SNN_Bricks_Dom::inner_html( $el ) );
                $is_leaf = true;
                break;

            case 'icon':
                $icon = SNN_Bricks_Features::parse_fa_icon( (string) SNN_Bricks_Dom::attr( $el, 'class' ) );
                if ( $icon ) {
                    $element['settings']['icon'] = $icon;
                } else {
                    $element['name']     = 'custom-html-css-script';
                    $element['settings'] = array( 'content' => SNN_Bricks_Dom::outer_html( $el ), '_display' => 'contents' );
                }
                if ( $element['name'] === 'icon' ) {
                    SNN_Bricks_Features::pin_icon_appearance( $el, $element['settings'], $ctx );
                }
                $is_leaf = true;
                break;

            case 'text-link':
            case 'button':
                $found = SNN_Bricks_Features::find_control_icon( $el, $ctx );
                $element['settings']['text'] = SNN_Bricks_Dom::inner_html_without( $el, $found ? $found['removal'] : null );
                $href = SNN_Bricks_Dom::attr( $el, 'href' );
                if ( $href === null || $href === '' ) {
                    $href = SNN_Bricks_Dom::attr( $el, 'data-href' );
                }
                if ( $bricks_name === 'text-link' || $href ) {
                    $element['settings']['link'] = SNN_Bricks_Features::parse_link( $el );
                } elseif ( $tag === 'button' ) {
                    $element['settings']['tag'] = 'button';
                }
                if ( $found ) {
                    SNN_Bricks_Features::apply_control_icon( $element, $el, $found, $own_decls, $ctx );
                } elseif ( SNN_Bricks_Dom::attr( $el, 'data-icon-gap' ) ) {
                    $element['settings'][ $bricks_name === 'button' ? 'iconGap' : 'gap' ] = SNN_Bricks_Dom::attr( $el, 'data-icon-gap' );
                }
                $is_leaf = true;
                break;

            case 'image':
                $src = SNN_Bricks_Dom::attr( $el, 'src' );
                if ( $src === null || $src === '' ) {
                    $src = SNN_Bricks_Dom::attr( $el, 'data-src' );
                }
                if ( $src ) {
                    $element['settings']['image'] = self::image_setting( $src );
                }
                $alt = SNN_Bricks_Dom::attr( $el, 'alt' );
                if ( $alt ) {
                    $element['settings']['altText'] = $alt;
                }
                $is_leaf = true;
                break;

            case 'svg':
                $node  = $svg_node ? $svg_node : $el;
                $asset = SNN_Bricks_Features::svg_asset_for( $node, $ctx );
                $element['settings']['file'] = array( 'id' => $asset['id'], 'filename' => $asset['filename'], 'url' => $asset['url'] );
                $d = $node === $el ? $own_decls : SNN_Bricks_Cascade::resolve_decls_at( $node, $ctx->rule_set, $base_width );
                $w = SNN_Bricks_Features::explicit_length( $d['width'] ?? '' ) ?: SNN_Bricks_Features::explicit_length( SNN_Bricks_Dom::attr( $node, 'width' ) );
                $h = SNN_Bricks_Features::explicit_length( $d['height'] ?? '' ) ?: SNN_Bricks_Features::explicit_length( SNN_Bricks_Dom::attr( $node, 'height' ) );
                foreach ( array_keys( $element['settings'] ) as $k ) {
                    if ( explode( ':', $k )[0] === '_width' ) {
                        unset( $element['settings'][ $k ] );
                    }
                }
                if ( $w ) {
                    $element['settings']['width'] = $w;
                }
                if ( $h ) {
                    $element['settings']['height'] = $h;
                }
                $fill   = SNN_Bricks_Features::explicit_paint( $d['fill'] ?? '' );
                $stroke = SNN_Bricks_Features::explicit_paint( $d['stroke'] ?? '' );
                if ( $fill ) {
                    $element['settings']['fill'] = SNN_Bricks_Features::to_bricks_color( $fill, $ctx );
                }
                if ( $stroke ) {
                    $element['settings']['stroke'] = SNN_Bricks_Features::to_bricks_color( $stroke, $ctx );
                }
                $is_leaf = true;
                break;

            case 'divider':
                foreach ( array_keys( $element['settings'] ) as $k ) {
                    if ( $k === '_display' || strpos( $k, '_display:' ) === 0 ) {
                        unset( $element['settings'][ $k ] );
                    }
                }
                $line     = $line_info ? $line_info : SNN_Bricks_Features::analyze_line( $own_decls );
                $vertical = $line && $line['direction'] === 'vertical';
                if ( $line ) {
                    if ( $vertical ) {
                        $element['settings']['direction'] = 'vertical';
                    }
                    $element['settings']['height'] = $vertical ? $line['length'] : $line['thickness'];
                    $element['settings']['width']  = $vertical ? $line['thickness'] : $line['length'];
                    $element['settings']['style']  = $line['style'];
                    if ( $line['color'] ) {
                        $element['settings']['color'] = SNN_Bricks_Features::to_bricks_color( $line['color'], $ctx );
                    }
                } else {
                    $element['settings']['style'] = 'solid';
                }
                if ( empty( $element['settings']['_width'] ) && ! $vertical ) {
                    $element['settings']['_width'] = '100%';
                }
                $is_leaf = true;
                break;

            case 'custom-html-css-script':
                $element['settings']['content'] = SNN_Bricks_Dom::outer_html( $el );
                $is_leaf = true;
                break;
        }

        // Query loop support.
        $loop_type = SNN_Bricks_Dom::attr( $el, 'data-loop' );
        if ( $loop_type ) {
            $element['settings']['hasLoop'] = true;
            $element['settings']['query']   = array(
                'post_type'      => array( $loop_type ),
                'posts_per_page' => SNN_Bricks_Dom::attr( $el, 'data-loop-posts-per-page' ) ?: '6',
                'orderby'        => array( SNN_Bricks_Dom::attr( $el, 'data-loop-orderby' ) ?: 'date' ),
                'order'          => SNN_Bricks_Dom::attr( $el, 'data-loop-order' ) ?: 'DESC',
            );
        }

        // HTML attributes → Bricks custom attributes.
        if ( $element['name'] !== 'custom-html-css-script' && $element['name'] !== 'svg' ) {
            $custom = array();
            foreach ( SNN_Bricks_Dom::attributes( $el ) as $name => $value ) {
                if ( ! in_array( $name, self::IGNORED_ATTRS, true ) ) {
                    $custom[] = array( '_id' => $ids->next(), 'name' => $name, 'value' => $value );
                }
            }
            if ( $custom ) {
                $element['settings']['_attributes'] = $custom;
            }
        }

        $content[] = $element;
        $index     = count( $content ) - 1;

        if ( ! $is_leaf ) {
            foreach ( SNN_Bricks_Dom::child_nodes( $el ) as $child ) {
                if ( $child->nodeType === XML_TEXT_NODE ) {
                    $text = trim( $child->textContent );
                    if ( $text !== '' ) {
                        $text_id   = $ids->next();
                        $content[] = array(
                            'id' => $text_id, 'name' => 'text-basic', 'parent' => $id,
                            'children' => array(), 'settings' => array( 'text' => $text, 'tag' => 'span' ), 'themeStyles' => array(),
                        );
                        $content[ $index ]['children'][] = $text_id;
                    }
                } else {
                    $child_id = self::walk( $child, $id, $ctx, $class_to_id, $ids, $content );
                    if ( $child_id ) {
                        $content[ $index ]['children'][] = $child_id;
                    }
                }
            }
        }

        return $id;
    }

    /**
     * Image setting. A URL already in the media library is stored with its
     * attachment id, so Bricks serves responsive sizes; anything else stays
     * external, exactly as the builder compiler stores it.
     */
    public static function image_setting( $src ) {
        $setting = array( 'url' => $src, 'size' => 'full', 'external' => true );
        if ( function_exists( 'attachment_url_to_postid' ) ) {
            $attachment_id = attachment_url_to_postid( $src );
            if ( $attachment_id ) {
                return array(
                    'id'       => (int) $attachment_id,
                    'filename' => basename( (string) get_attached_file( $attachment_id ) ),
                    'size'     => 'full',
                    'full'     => $src,
                    'url'      => $src,
                );
            }
        }
        return $setting;
    }

    // ── sections and JSON validation ───────────────────────────────────────

    /** parseHTMLIntoSections(): top-level semantic blocks → [{label, html}]. */
    public static function parse_into_sections( $html ) {
        $parsed   = SNN_Bricks_Dom::parse( $html );
        $body     = $parsed['body'];
        $sem_tags = array( 'section', 'header', 'footer', 'nav', 'article' );
        $results  = array();

        $label = function ( $el ) {
            $h = SNN_Bricks_Dom::find_tags( $el, array( 'h1', 'h2', 'h3', 'h4' ) );
            $aria = SNN_Bricks_Dom::attr( $el, 'aria-label' );
            if ( $aria ) {
                return $aria;
            }
            if ( $h ) {
                $t = mb_substr( trim( SNN_Bricks_Dom::text( $h[0] ) ), 0, 50 );
                if ( $t !== '' ) {
                    return $t;
                }
            }
            return ucfirst( SNN_Bricks_Dom::tag( $el ) );
        };
        $preceding_styles = function ( $el ) {
            $styles = '';
            for ( $prev = $el->previousSibling; $prev; $prev = $prev->previousSibling ) {
                if ( $prev->nodeType !== XML_ELEMENT_NODE ) {
                    continue;
                }
                if ( SNN_Bricks_Dom::tag( $prev ) === 'style' && $prev->hasAttribute( 'data-style-id' ) ) {
                    $styles = SNN_Bricks_Dom::outer_html( $prev ) . $styles;
                    continue;
                }
                break;
            }
            return $styles;
        };

        foreach ( SNN_Bricks_Dom::children( $body ) as $child ) {
            $tag = SNN_Bricks_Dom::tag( $child );
            if ( $tag === 'main' ) {
                $inner = array_values( array_filter( SNN_Bricks_Dom::children( $child ), function ( $el ) use ( $sem_tags ) {
                    $t = SNN_Bricks_Dom::tag( $el );
                    return in_array( $t, $sem_tags, true ) || ( $t === 'div' && SNN_Bricks_Dom::children( $el ) );
                } ) );
                if ( count( $inner ) >= 2 ) {
                    foreach ( $inner as $el ) {
                        $results[] = array( 'label' => $label( $el ), 'html' => SNN_Bricks_Dom::outer_html( $el ) );
                    }
                } else {
                    $results[] = array( 'label' => $label( $child ), 'html' => SNN_Bricks_Dom::outer_html( $child ) );
                }
            } elseif ( in_array( $tag, $sem_tags, true ) || ( $tag === 'div' && SNN_Bricks_Dom::children( $child ) ) ) {
                $results[] = array( 'label' => $label( $child ), 'html' => $preceding_styles( $child ) . SNN_Bricks_Dom::outer_html( $child ) );
            }
        }
        if ( ! $results ) {
            $results[] = array( 'label' => 'Page Content', 'html' => $html );
        }
        return $results;
    }

    /**
     * validateAndFixBricksJSON(): rewrite every id to a fresh Bricks id, remap
     * parents, rescue orphans, demote nested containers, rebuild children.
     */
    public static function validate_and_fix( array $content, $ids ) {
        $errors = array();
        $remap  = array();
        if ( ! $content ) {
            return array( 'content' => $content, 'errors' => array( 'Empty content array' ) );
        }

        foreach ( $content as &$el ) {
            $old            = (string) ( $el['id'] ?? '' );
            $new            = $ids->next();
            $remap[ $old ]  = $new;
            $el['id']       = $new;
            if ( empty( $el['name'] ) ) {
                $el['name'] = 'block';
            }
            if ( ! isset( $el['settings'] ) || ! is_array( $el['settings'] ) ) {
                $el['settings'] = array();
            }
            if ( ! isset( $el['themeStyles'] ) ) {
                $el['themeStyles'] = array();
            }
            $el['children'] = array();
        }
        unset( $el );

        foreach ( $content as &$el ) {
            $raw = $el['parent'] ?? 0;
            if ( $raw === 0 || $raw === '0' || $raw === null || $raw === '' ) {
                $el['parent'] = 0;
            } elseif ( isset( $remap[ (string) $raw ] ) ) {
                $el['parent'] = $remap[ (string) $raw ];
            } else {
                $errors[]     = 'Orphan rescue: ' . $el['id'] . ' → root';
                $el['parent'] = 0;
            }
        }
        unset( $el );

        $names = array();
        foreach ( $content as $el ) {
            $names[ $el['id'] ] = $el['name'];
        }
        foreach ( $content as &$el ) {
            if ( $el['name'] === 'container' && $el['parent'] !== 0 ) {
                $pn = $names[ $el['parent'] ] ?? '';
                if ( $pn === 'container' || $pn === 'block' ) {
                    $errors[]            = 'Nesting fix: container ' . $el['id'] . ' inside ' . $pn . ' → block';
                    $el['name']          = 'block';
                    $names[ $el['id'] ]  = 'block';
                }
            }
            if ( isset( $el['settings']['_cssGlobalClasses'] ) && ! is_array( $el['settings']['_cssGlobalClasses'] ) ) {
                $el['settings']['_cssGlobalClasses'] = array( $el['settings']['_cssGlobalClasses'] );
            }
        }
        unset( $el );

        $index = array();
        foreach ( $content as $i => $el ) {
            $index[ $el['id'] ] = $i;
        }
        foreach ( $content as $i => $el ) {
            if ( $el['parent'] !== 0 ) {
                if ( isset( $index[ $el['parent'] ] ) ) {
                    $content[ $index[ $el['parent'] ] ]['children'][] = $el['id'];
                } else {
                    $content[ $i ]['parent'] = 0;
                }
            }
        }

        return array( 'content' => $content, 'errors' => $errors );
    }

    // ── class naming against the site ──────────────────────────────────────

    private static function norm_css( $css ) {
        return trim( preg_replace( '/\s+/', ' ', (string) $css ) );
    }

    /**
     * planClassNames(): same name + same CSS is reused; same name + different
     * CSS gets a free "-N" name, cascading to reused classes that mention it.
     */
    public static function plan_class_names( array $class_map, array $existing ) {
        $by_name = array();
        $taken   = array();
        foreach ( $existing as $gc ) {
            if ( isset( $gc['name'] ) ) {
                $by_name[ $gc['name'] ] = $gc;
                $taken[ $gc['name'] ]   = true;
            }
        }
        foreach ( array_keys( $class_map ) as $n ) {
            $taken[ $n ] = true;
        }
        $renames = array();
        $reuse   = array();
        $rename = function ( $name ) use ( &$renames, &$reuse, &$taken ) {
            $n = 1;
            while ( isset( $taken[ $name . '-' . $n ] ) ) {
                $n++;
            }
            $renames[ $name ] = $name . '-' . $n;
            $taken[ $renames[ $name ] ] = true;
            unset( $reuse[ $name ] );
        };

        foreach ( $class_map as $name => $gc ) {
            if ( ! isset( $by_name[ $name ] ) ) {
                continue;
            }
            $current = $by_name[ $name ];
            if ( self::norm_css( $current['settings']['_cssCustom'] ?? '' ) === self::norm_css( $gc['css'] ) ) {
                $reuse[ $name ] = $current['id'];
            } else {
                $rename( $name );
            }
        }

        $changed = true;
        while ( $changed ) {
            $changed = false;
            foreach ( array_keys( $reuse ) as $name ) {
                $mentioned = SNN_Bricks_Css::class_names_in_css( $class_map[ $name ]['css'] );
                foreach ( array_keys( $renames ) as $r ) {
                    if ( isset( $mentioned[ $r ] ) ) {
                        $rename( $name );
                        $changed = true;
                        break;
                    }
                }
            }
        }
        return array( 'renames' => $renames, 'reuse' => $reuse );
    }

    /**
     * @import lines for remote stylesheets linked with <link rel="stylesheet">
     * (Google Fonts ...). Such links usually sit in <head>, which the body
     * markup never carries, so they are carried over as @import, the same way
     * the zip importer does it.
     */
    public static function remote_sheet_imports( $html ) {
        $out = array();
        if ( preg_match_all( '#<link\b[^>]*>#i', (string) $html, $links ) ) {
            foreach ( $links[0] as $tag ) {
                if ( preg_match( '/\brel\s*=\s*["\']?[^"\'>]*stylesheet/i', $tag ) && preg_match( '/\bhref\s*=\s*["\'](https?:\/\/[^"\']+|\/\/[^"\']+)["\']/i', $tag, $h ) ) {
                    $out[] = '@import url("' . html_entity_decode( $h[1], ENT_QUOTES ) . '");';
                }
            }
        }
        return array_values( array_unique( $out ) );
    }

    // ── full builds ────────────────────────────────────────────────────────

    /**
     * compileSectionBySection(): compile a full HTML page (with <style>) into
     * Bricks sections plus the global classes and page CSS it needs.
     *
     * @param string $full_html
     * @param array  $args {
     *   existing_classes: array  site global classes (bricks_global_classes)
     *   taken_ids:        array  ids already used on the page and by classes
     *   svg_assets:       array  svg key → asset (from SNN_Bricks_Assets)
     *   section_filter:   int[]  only these section indexes
     * }
     * @return array {
     *   sections: [{label, html, content, rootId}], classesToCreate: [], classesReused: [],
     *   pageCssElement: array|null, colorVars: [], sizeVars: [], renames: [], warnings: []
     * }
     */
    public static function build( $full_html, array $args = array() ) {
        $existing = (array) ( $args['existing_classes'] ?? array() );
        $taken    = (array) ( $args['taken_ids'] ?? array() );
        foreach ( $existing as $gc ) {
            if ( ! empty( $gc['id'] ) ) {
                $taken[] = $gc['id'];
            }
        }
        $ids      = new SNN_Bricks_Id_Pool( $taken );
        $plan     = SNN_Bricks_Native_Layout::breakpoint_plan();
        $warnings = array();

        $all_sections = self::parse_into_sections( $full_html );
        $sections     = $all_sections;
        if ( ! empty( $args['section_filter'] ) && is_array( $args['section_filter'] ) ) {
            $keep     = array_map( 'intval', $args['section_filter'] );
            $sections = array_values( array_filter( $all_sections, function ( $s, $i ) use ( $keep ) {
                return in_array( $i, $keep, true );
            }, ARRAY_FILTER_USE_BOTH ) );
        }

        // PHASE 0b: classes, fonts and variables from the FULL html <style> blocks.
        $full_parsed   = SNN_Bricks_Dom::parse( $full_html );
        $full_body     = $full_parsed['body'];
        $class_map     = array();
        $class_to_id   = array();
        $all_style_css = '';
        foreach ( SNN_Bricks_Dom::find_tags( $full_parsed['doc']->documentElement, array( 'style' ) ) as $style_el ) {
            $css            = SNN_Bricks_Dom::text( $style_el );
            $all_style_css .= $css . "\n";
            foreach ( SNN_Bricks_Css::parse_css_rules( $css ) as $class_name => $css_block ) {
                if ( ! isset( $class_map[ $class_name ] ) ) {
                    $gid                        = $ids->next();
                    $class_map[ $class_name ]   = array( 'id' => $gid, 'css' => '' );
                    $class_to_id[ $class_name ] = $gid;
                }
                $class_map[ $class_name ]['css'] .= $css_block;
            }
        }
        self::register_bare_classes( $full_body, $class_to_id, $ids, $class_map );

        $rule_set = SNN_Bricks_Cascade::build_rule_set( $all_style_css );

        $line_only = SNN_Bricks_Features::find_line_only_classes( $full_body, $rule_set, $plan );
        foreach ( $line_only as $cn => $direction ) {
            if ( isset( $class_map[ $cn ] ) ) {
                $class_map[ $cn ]['css'] = SNN_Bricks_Features::strip_line_paint_from_css( $class_map[ $cn ]['css'], $cn, $direction );
            }
        }

        // PHASE 0c: class names vs the site's existing global classes.
        $naming  = self::plan_class_names( $class_map, $existing );
        $renames = $naming['renames'];
        if ( $renames ) {
            foreach ( $class_map as &$gc ) {
                $gc['css'] = SNN_Bricks_Css::rename_classes_in_css( $gc['css'], $renames );
            }
            unset( $gc );
        }
        foreach ( $naming['reuse'] as $name => $id ) {
            $class_map[ $name ]['id']     = $id;
            $class_map[ $name ]['reused'] = true;
            $class_to_id[ $name ]         = $id;
        }

        // PHASE 0d: non-class CSS (@import, :root, body, *, @font-face ...).
        $global_css = SNN_Bricks_Css::rename_classes_in_css( SNN_Bricks_Css::extract_global_css( $all_style_css ), $renames );

        // Remote stylesheets from <head> (Google Fonts) must lead the page CSS: @import has to come first.
        $imports = array_filter( self::remote_sheet_imports( $full_html ), function ( $import ) use ( $global_css ) {
            return strpos( $global_css, $import ) === false;
        } );
        if ( $imports ) {
            $global_css = implode( "\n", $imports ) . "\n" . $global_css;
        }

        // Resolvable :root variables → Bricks palette colours / global variables.
        $color_vars = array();
        $size_vars  = array();
        foreach ( SNN_Bricks_Css::extract_root_variables( $all_style_css )['variables'] as $v ) {
            $val = $v['value'];
            if ( preg_match( '/^var\(--/', $val ) ) {
                continue;
            }
            if ( preg_match( '/^#[0-9a-fA-F]{3,8}$/', $val ) || preg_match( '/^rgb(a?)\(/', $val ) || preg_match( '/^hsl(a?)\(/', $val )
                || preg_match( '/^(transparent|currentColor|inherit|initial|unset)$/i', $val ) ) {
                $color_vars[] = $v;
            } elseif ( preg_match( '/^-?\d/', $val ) || ( preg_match( '/[a-z]+$/i', $val ) && preg_match( '/px|em|rem|%|vw|vh|vmin|vmax|ch|ex|cm|mm|in|pt|pc/', $val ) ) ) {
                $size_vars[] = $v;
            }
        }

        // PHASE 2: compile each section with the shared class map and cascade.
        $compiled     = array();
        $kept_scripts = array();
        foreach ( $sections as $i => $section ) {
            $data = self::compile( $section['html'], $class_to_id, $rule_set, array(
                'svg_assets' => $args['svg_assets'] ?? array(),
                'ids'        => $ids,
                'plan'       => $plan,
            ) );
            if ( empty( $data['content'] ) ) {
                $warnings[] = 'Section "' . $section['label'] . '" produced no elements and was skipped.';
                continue;
            }
            $kept_scripts = array_merge( $kept_scripts, $data['scripts'] ?? array() );
            $fixed = self::validate_and_fix( $data['content'], $ids );
            foreach ( $fixed['errors'] as $e ) {
                $warnings[] = $section['label'] . ': ' . $e;
            }
            $compiled[] = array( 'label' => $section['label'], 'html' => $section['html'], 'content' => $fixed['content'] );
        }

        // Scripts are kept as embedded code. Report each one so the reviewer sees it ran.
        if ( $kept_scripts ) {
            $list = array();
            foreach ( $kept_scripts as $s ) {
                $list[] = $s['src'] . ' (' . $s['bytes'] . ' bytes)';
            }
            $warnings[] = count( $kept_scripts ) . ' <script> tag(s) kept as embedded code, not converted to Bricks elements: ' . implode( '; ', $list ) . '. Check them in the page source before publishing.';
        }

        // PHASE 2.5: classes attached to elements become global classes; markup-only
        // classes go into the page CSS element (Bricks prints class CSS only for attached classes).
        $attached = array();
        foreach ( $compiled as &$c ) {
            foreach ( $c['content'] as $el ) {
                foreach ( (array) ( $el['settings']['_cssGlobalClasses'] ?? array() ) as $gid ) {
                    $attached[ $gid ] = true;
                }
            }
            SNN_Bricks_Css::apply_class_renames_to_elements( $c['content'], $renames );
        }
        unset( $c );

        $to_create = array();
        $reused    = array();
        $loose     = array();
        foreach ( $class_map as $class_name => $gc ) {
            $css = trim( $gc['css'] );
            if ( isset( $attached[ $gc['id'] ] ) ) {
                if ( empty( $gc['reused'] ) ) {
                    $to_create[] = array( 'id' => $gc['id'], 'name' => $renames[ $class_name ] ?? $class_name, 'settings' => array( '_cssCustom' => $css ) );
                } else {
                    $reused[] = $class_name;
                }
            } elseif ( $css !== '' ) {
                $loose[] = $css;
            }
        }

        $page_css = implode( "\n", array_filter( array_merge( array( $global_css ), $loose ), 'strlen' ) );
        $page_css_element = null;
        if ( $page_css !== '' ) {
            $page_css_element = array(
                'id' => $ids->next(), 'name' => 'custom-html-css-script', 'parent' => 0, 'children' => array(),
                'settings' => array( 'content' => '<style>' . $page_css . '</style>', '_display' => 'contents' ),
                'themeStyles' => array(),
            );
        }

        $out_sections = array();
        foreach ( $compiled as $c ) {
            $root = null;
            foreach ( $c['content'] as $el ) {
                if ( $el['parent'] === 0 ) {
                    $root = $el['id'];
                    break;
                }
            }
            $out_sections[] = array(
                'label'   => $c['label'],
                'html'    => $c['html'],
                'content' => $c['content'],
                'rootId'  => $root,
                'classes' => array_map( function ( $n ) use ( $renames ) {
                    return $renames[ $n ] ?? $n;
                }, array_keys( $class_to_id ) ),
            );
        }

        return array(
            'sections'        => $out_sections,
            'classesToCreate' => $to_create,
            'classesReused'   => $reused,
            'pageCssElement'  => $page_css_element,
            'colorVars'       => $color_vars,
            'sizeVars'        => $size_vars,
            'renames'         => $renames,
            'warnings'        => $warnings,
            'ids'             => $ids,
        );
    }

    /** CSS of a global class as plain selectors (%root% stands for the class itself). */
    public static function global_class_css( array $gc ) {
        return str_replace( '%root%', '.' . ( $gc['name'] ?? '' ), (string) ( $gc['settings']['_cssCustom'] ?? '' ) );
    }

    /** <style> blocks the page carries in custom HTML elements, optionally skipping ids. */
    public static function page_style_css( array $content, array $skip = array() ) {
        $parts = array();
        foreach ( $content as $el ) {
            if ( ( $el['name'] ?? '' ) !== 'custom-html-css-script' || isset( $skip[ $el['id'] ?? '' ] ) ) {
                continue;
            }
            if ( preg_match_all( '/<style[^>]*>([\s\S]*?)<\/style>/i', (string) ( $el['settings']['content'] ?? '' ), $m ) ) {
                foreach ( $m[1] as $css ) {
                    $parts[] = $css;
                }
            }
        }
        return implode( "\n", $parts );
    }

    /**
     * compileRegeneratedSection(): compile a replacement for one section
     * against the page it lands in.
     *
     * @param string $html           new section HTML (with its <style>)
     * @param array  $page_content   current page elements
     * @param array  $doomed         [id => true] of the section being replaced
     * @param array  $existing       site global classes
     * @param array  $svg_assets
     * @return array|null { content, classesToCreate, classesToUpdate: [{id, css}], classNames }
     */
    public static function compile_section_replacement( $html, array $page_content, array $doomed, array $existing, array $svg_assets = array() ) {
        $by_name = array();
        $taken   = array();
        foreach ( $existing as $gc ) {
            if ( isset( $gc['name'] ) ) {
                $by_name[ $gc['name'] ] = $gc;
            }
            if ( ! empty( $gc['id'] ) ) {
                $taken[] = $gc['id'];
            }
        }
        foreach ( $page_content as $el ) {
            if ( ! empty( $el['id'] ) ) {
                $taken[] = $el['id'];
            }
        }
        $ids    = new SNN_Bricks_Id_Pool( $taken );
        $parsed = SNN_Bricks_Dom::parse( $html );
        $body   = $parsed['body'];

        $used_outside = array();
        foreach ( $page_content as $el ) {
            if ( isset( $doomed[ $el['id'] ?? '' ] ) ) {
                continue;
            }
            foreach ( (array) ( $el['settings']['_cssGlobalClasses'] ?? array() ) as $gid ) {
                $used_outside[ $gid ] = true;
            }
        }

        $new_css = '';
        foreach ( SNN_Bricks_Dom::find_tags( $parsed['doc']->documentElement, array( 'style' ) ) as $st ) {
            $new_css .= SNN_Bricks_Dom::text( $st ) . "\n";
        }

        $class_map   = array();
        $class_to_id = array();
        foreach ( SNN_Bricks_Css::parse_css_rules( $new_css ) as $name => $css ) {
            if ( isset( $by_name[ $name ] ) ) {
                $current = $by_name[ $name ];
                $shared  = isset( $used_outside[ $current['id'] ] );
                $class_map[ $name ] = array( 'id' => $current['id'], 'css' => $shared ? self::global_class_css( $current ) : $css, 'existing' => $current, 'update' => ! $shared );
            } else {
                $class_map[ $name ] = array( 'id' => $ids->next(), 'css' => $css, 'existing' => null, 'update' => false );
            }
            $class_to_id[ $name ] = $class_map[ $name ]['id'];
        }
        foreach ( SNN_Bricks_Dom::descendants( $body ) as $node ) {
            foreach ( preg_split( '/\s+/', (string) SNN_Bricks_Dom::attr( $node, 'class' ) ) as $cn ) {
                if ( $cn === '' || SNN_Bricks_Features::is_fa_token( $cn ) || isset( $class_map[ $cn ] ) ) {
                    continue;
                }
                $current = $by_name[ $cn ] ?? null;
                $class_map[ $cn ] = $current
                    ? array( 'id' => $current['id'], 'css' => self::global_class_css( $current ), 'existing' => $current, 'update' => false )
                    : array( 'id' => $ids->next(), 'css' => '', 'existing' => null, 'update' => false );
                $class_to_id[ $cn ] = $class_map[ $cn ]['id'];
            }
        }

        $rule_css = array( self::page_style_css( $page_content, $doomed ), SNN_Bricks_Css::extract_global_css( $new_css ) );
        foreach ( $class_map as $c ) {
            $rule_css[] = $c['css'];
        }
        $rule_set = SNN_Bricks_Cascade::build_rule_set( implode( "\n", $rule_css ) );
        $plan     = SNN_Bricks_Native_Layout::breakpoint_plan();

        foreach ( SNN_Bricks_Features::find_line_only_classes( $body, $rule_set, $plan ) as $cn => $direction ) {
            if ( isset( $class_map[ $cn ] ) && ( ! $class_map[ $cn ]['existing'] || $class_map[ $cn ]['update'] ) ) {
                $class_map[ $cn ]['css'] = SNN_Bricks_Features::strip_line_paint_from_css( $class_map[ $cn ]['css'], $cn, $direction );
            }
        }

        $compiled = self::compile( $html, $class_to_id, $rule_set, array( 'svg_assets' => $svg_assets, 'ids' => $ids, 'plan' => $plan ) );
        if ( empty( $compiled['content'] ) ) {
            return null;
        }
        $fixed   = self::validate_and_fix( $compiled['content'], $ids );
        $content = $fixed['content'];

        $attached = array();
        foreach ( $content as $el ) {
            foreach ( (array) ( $el['settings']['_cssGlobalClasses'] ?? array() ) as $gid ) {
                $attached[ $gid ] = true;
            }
        }

        $to_create = array();
        $to_update = array();
        $loose     = array();
        foreach ( $class_map as $name => $c ) {
            $css = trim( $c['css'] );
            if ( isset( $attached[ $c['id'] ] ) ) {
                if ( ! $c['existing'] ) {
                    $to_create[] = array( 'id' => $c['id'], 'name' => $name, 'settings' => array( '_cssCustom' => $css ) );
                } elseif ( $c['update'] && self::norm_css( $c['existing']['settings']['_cssCustom'] ?? '' ) !== self::norm_css( $css ) ) {
                    $to_update[] = array( 'id' => $c['id'], 'css' => $css );
                }
            } elseif ( $css !== '' && ! ( $c['existing'] && isset( $used_outside[ $c['id'] ] ) ) ) {
                $loose[] = $css;
            }
        }

        $root_index = null;
        foreach ( $content as $i => $el ) {
            if ( $el['parent'] === 0 ) {
                $root_index = $i;
                break;
            }
        }
        if ( $loose && $root_index !== null ) {
            $style_el = array(
                'id' => $ids->next(), 'name' => 'custom-html-css-script', 'parent' => $content[ $root_index ]['id'], 'children' => array(),
                'settings' => array( 'content' => '<style>' . implode( "\n", $loose ) . '</style>', '_display' => 'contents' ),
                'themeStyles' => array(),
            );
            array_unshift( $content[ $root_index ]['children'], $style_el['id'] );
            array_splice( $content, $root_index + 1, 0, array( $style_el ) );
        }

        return array(
            'content'         => $content,
            'rootId'          => $root_index !== null ? $content[ $root_index ]['id'] : null,
            'classesToCreate' => $to_create,
            'classesToUpdate' => $to_update,
            'classNames'      => array_keys( $class_map ),
            'warnings'        => $fixed['errors'],
        );
    }
}
