<?php
/**
 * CSS tokenizing helpers — PHP port of the "CSS TOKENIZING" and "STEP 1" parts
 * of html-to-bricks-translation.php. Function-for-function, same behaviour:
 * the parity tests compare the two compilers' output byte for byte.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SNN_Bricks_Css {

    /**
     * Split CSS into its top-level parts: { prelude, body } for blocks and
     * { prelude, body: null } for statements such as @import. Quotes are
     * respected, so content:"{" does not unbalance the braces.
     */
    public static function top_level_blocks( $css ) {
        $out      = array();
        $text     = (string) $css;
        $len      = strlen( $text );
        $depth    = 0;
        $start    = 0;
        $brace_at = -1;
        $quote    = null;

        for ( $i = 0; $i < $len; $i++ ) {
            $ch = $text[ $i ];
            if ( $quote !== null ) {
                if ( $ch === $quote && ( $i === 0 || $text[ $i - 1 ] !== '\\' ) ) {
                    $quote = null;
                }
                continue;
            }
            if ( $ch === '"' || $ch === "'" ) {
                $quote = $ch;
                continue;
            }
            if ( $ch === '{' ) {
                if ( $depth === 0 ) {
                    $brace_at = $i;
                }
                $depth++;
                continue;
            }
            if ( $ch === '}' ) {
                $depth--;
                if ( $depth === 0 && $brace_at > -1 ) {
                    $out[] = array(
                        'prelude' => trim( substr( $text, $start, $brace_at - $start ) ),
                        'body'    => substr( $text, $brace_at + 1, $i - $brace_at - 1 ),
                    );
                    $start    = $i + 1;
                    $brace_at = -1;
                }
                if ( $depth < 0 ) {
                    $depth = 0;
                }
                continue;
            }
            if ( $ch === ';' && $depth === 0 ) {
                $stmt = trim( substr( $text, $start, $i + 1 - $start ) );
                if ( $stmt !== '' ) {
                    $out[] = array( 'prelude' => $stmt, 'body' => null );
                }
                $start = $i + 1;
            }
        }
        return $out;
    }

    /** "a, :is(b, c)" → ["a", ":is(b, c)"] — commas inside parentheses or brackets stay put. */
    public static function split_selector_list( $text ) {
        $out   = array();
        $depth = 0;
        $buf   = '';
        $quote = null;
        $text  = (string) $text;
        $len   = strlen( $text );
        for ( $i = 0; $i < $len; $i++ ) {
            $ch = $text[ $i ];
            if ( $quote !== null ) {
                $buf .= $ch;
                if ( $ch === $quote ) {
                    $quote = null;
                }
                continue;
            }
            if ( $ch === '"' || $ch === "'" ) {
                $quote = $ch;
                $buf  .= $ch;
                continue;
            }
            if ( $ch === '(' || $ch === '[' ) {
                $depth++;
            }
            if ( $ch === ')' || $ch === ']' ) {
                $depth--;
            }
            if ( $ch === ',' && $depth === 0 ) {
                if ( trim( $buf ) !== '' ) {
                    $out[] = trim( $buf );
                }
                $buf = '';
                continue;
            }
            $buf .= $ch;
        }
        if ( trim( $buf ) !== '' ) {
            $out[] = trim( $buf );
        }
        return $out;
    }

    /**
     * Visit every class token in a selector, skipping attribute selectors and
     * quoted strings. The callback may return a replacement name (string) or null.
     */
    public static function map_selector_classes( $selector, callable $fn ) {
        $s       = (string) $selector;
        $len     = strlen( $s );
        $out     = '';
        $i       = 0;
        $bracket = 0;
        $quote   = null;
        while ( $i < $len ) {
            $ch = $s[ $i ];
            if ( $quote !== null ) {
                $out .= $ch;
                if ( $ch === $quote ) {
                    $quote = null;
                }
                $i++;
                continue;
            }
            if ( $ch === '"' || $ch === "'" ) {
                $quote = $ch;
                $out  .= $ch;
                $i++;
                continue;
            }
            if ( $ch === '[' ) {
                $bracket++;
            }
            if ( $ch === ']' ) {
                $bracket--;
            }
            if ( $ch === '.' && $bracket === 0 ) {
                if ( preg_match( '/^-?[_a-zA-Z][\w-]*/', substr( $s, $i + 1 ), $m ) ) {
                    $next = $fn( $m[0] );
                    $out .= '.' . ( is_string( $next ) ? $next : $m[0] );
                    $i   += 1 + strlen( $m[0] );
                    continue;
                }
            }
            $out .= $ch;
            $i++;
        }
        return $out;
    }

    /** Rename class selectors everywhere in a stylesheet: nested @media/@supports included, declarations untouched. */
    public static function rename_classes_in_css( $css, $renames ) {
        if ( ! $css || empty( $renames ) ) {
            return $css;
        }
        $parts = array();
        foreach ( self::top_level_blocks( $css ) as $block ) {
            if ( $block['body'] === null ) {
                $parts[] = $block['prelude'];
                continue;
            }
            $prelude = $block['prelude'];
            if ( preg_match( '/^@(media|supports|container|layer|document)\b/i', $prelude ) ) {
                $parts[] = $prelude . " {\n" . self::rename_classes_in_css( $block['body'], $renames ) . "\n}";
                continue;
            }
            if ( strpos( $prelude, '@' ) === 0 ) {
                $parts[] = $prelude . ' {' . $block['body'] . '}';
                continue;
            }
            $parts[] = self::map_selector_classes( $prelude, function ( $n ) use ( $renames ) {
                return array_key_exists( $n, $renames ) ? $renames[ $n ] : null;
            } ) . ' {' . $block['body'] . '}';
        }
        return implode( "\n", $parts );
    }

    /** Every class name a stylesheet's selectors mention, as a set [name => true]. */
    public static function class_names_in_css( $css ) {
        $names = array();
        $walk = function ( $text ) use ( &$walk, &$names ) {
            foreach ( self::top_level_blocks( $text ) as $block ) {
                if ( $block['body'] === null ) {
                    continue;
                }
                if ( preg_match( '/^@(media|supports|container|layer|document)\b/i', $block['prelude'] ) ) {
                    $walk( $block['body'] );
                    continue;
                }
                if ( strpos( $block['prelude'], '@' ) === 0 ) {
                    continue;
                }
                self::map_selector_classes( $block['prelude'], function ( $n ) use ( &$names ) {
                    $names[ $n ] = true;
                    return null;
                } );
            }
        };
        $walk( self::strip_comments( $css ) );
        return $names;
    }

    /** Rename class="" tokens (and <style> selectors) inside raw HTML a setting carries. */
    public static function rename_class_attrs_in_html( $html, $renames ) {
        if ( ! $html || empty( $renames ) ) {
            return $html;
        }
        $html = preg_replace_callback( '/(<style[^>]*>)([\s\S]*?)(<\/style>)/i', function ( $m ) use ( $renames ) {
            return $m[1] . self::rename_classes_in_css( $m[2], $renames ) . $m[3];
        }, (string) $html );
        return preg_replace_callback( '/(\sclass\s*=\s*)(["\'])([^"\']*)\2/i', function ( $m ) use ( $renames ) {
            $tokens = preg_split( '/(\s+)/', $m[3], -1, PREG_SPLIT_DELIM_CAPTURE );
            foreach ( $tokens as &$tok ) {
                if ( array_key_exists( $tok, $renames ) ) {
                    $tok = $renames[ $tok ];
                }
            }
            unset( $tok );
            return $m[1] . $m[2] . implode( '', $tokens ) . $m[2];
        }, $html );
    }

    /** Apply class renames to raw HTML and element custom CSS carried by compiled elements. */
    public static function apply_class_renames_to_elements( array &$content, $renames ) {
        if ( empty( $renames ) ) {
            return;
        }
        foreach ( $content as &$el ) {
            if ( empty( $el['settings'] ) || ! is_array( $el['settings'] ) ) {
                continue;
            }
            foreach ( array( 'text', 'content' ) as $k ) {
                if ( isset( $el['settings'][ $k ] ) && is_string( $el['settings'][ $k ] ) ) {
                    $el['settings'][ $k ] = self::rename_class_attrs_in_html( $el['settings'][ $k ], $renames );
                }
            }
            foreach ( array_keys( $el['settings'] ) as $k ) {
                if ( ( $k === '_cssCustom' || strpos( $k, '_cssCustom:' ) === 0 ) && is_string( $el['settings'][ $k ] ) ) {
                    $el['settings'][ $k ] = self::rename_classes_in_css( $el['settings'][ $k ], $renames );
                }
            }
        }
        unset( $el );
    }

    public static function strip_comments( $css ) {
        return preg_replace( '/\/\*[\s\S]*?\*\//', '', (string) $css );
    }

    /**
     * Split a CSS rule body into "prop: value" declarations without breaking on
     * semicolons inside url(), quotes or parentheses.
     */
    public static function split_declarations( $body ) {
        $out   = array();
        $buf   = '';
        $depth = 0;
        $quote = null;
        $body  = (string) $body;
        $len   = strlen( $body );
        for ( $i = 0; $i < $len; $i++ ) {
            $ch = $body[ $i ];
            if ( $quote !== null ) {
                $buf .= $ch;
                if ( $ch === $quote && ( $i === 0 || $body[ $i - 1 ] !== '\\' ) ) {
                    $quote = null;
                }
                continue;
            }
            if ( $ch === '"' || $ch === "'" ) {
                $quote = $ch;
                $buf  .= $ch;
                continue;
            }
            if ( $ch === '(' ) {
                $depth++;
            }
            if ( $ch === ')' ) {
                $depth--;
            }
            if ( $ch === ';' && $depth === 0 ) {
                if ( trim( $buf ) !== '' ) {
                    $out[] = trim( $buf );
                }
                $buf = '';
                continue;
            }
            $buf .= $ch;
        }
        if ( trim( $buf ) !== '' ) {
            $out[] = trim( $buf );
        }
        return $out;
    }

    /** "font-size: 60px; color: #fff;" → "  font-size: 60px;\n  color: #fff;" */
    public static function format_css_body( $body ) {
        if ( ! $body ) {
            return '';
        }
        return implode( "\n", array_map( function ( $p ) {
            return '  ' . $p . ';';
        }, self::split_declarations( $body ) ) );
    }

    /** "color:red;gap:2px" → [ 'color' => 'red', 'gap' => '2px' ] */
    public static function parse_declarations( $body ) {
        $out = array();
        foreach ( self::split_declarations( (string) $body ) as $decl ) {
            $at = strpos( $decl, ':' );
            if ( $at === false ) {
                continue;
            }
            $prop = strtolower( trim( substr( $decl, 0, $at ) ) );
            $val  = trim( substr( $decl, $at + 1 ) );
            if ( $prop !== '' ) {
                $out[ $prop ] = $val;
            }
        }
        return $out;
    }

    /** Like parse_declarations, but keeps !important values apart so they can win the cascade. */
    public static function parse_declarations_with_priority( $body ) {
        $normal    = array();
        $important = array();
        $count     = 0;
        foreach ( self::split_declarations( (string) $body ) as $decl ) {
            $at = strpos( $decl, ':' );
            if ( $at === false ) {
                continue;
            }
            $prop = strtolower( trim( substr( $decl, 0, $at ) ) );
            $val  = trim( substr( $decl, $at + 1 ) );
            if ( $prop === '' ) {
                continue;
            }
            $count++;
            if ( preg_match( '/!\s*important\s*$/i', $val ) ) {
                $important[ $prop ] = trim( preg_replace( '/!\s*important\s*$/i', '', $val ) );
            } else {
                $normal[ $prop ] = $val;
            }
        }
        return array( 'normal' => $normal, 'important' => $important, 'count' => $count );
    }

    /** The class a rule is filed under: the first class in the selector (attribute selectors ignored). */
    public static function first_class_in_selector( $sel ) {
        $first = null;
        self::map_selector_classes( $sel, function ( $n ) use ( &$first ) {
            if ( $first === null ) {
                $first = $n;
            }
            return null;
        } );
        return $first;
    }

    /**
     * STEP 1: { className: fullCssBlock } — each class's CSS is self-contained:
     * base rules + @media blocks + the @keyframes its animations name.
     */
    public static function parse_css_rules( $css ) {
        $classes   = array();
        $keyframes = array();
        $css       = self::strip_comments( $css );

        $walk = function ( $text, $media_stack ) use ( &$walk, &$classes, &$keyframes ) {
            foreach ( self::top_level_blocks( $text ) as $block ) {
                if ( $block['body'] === null ) {
                    continue;
                }
                $prelude = $block['prelude'];
                if ( preg_match( '/^@media/i', $prelude ) ) {
                    $walk( $block['body'], array_merge( $media_stack, array( $prelude ) ) );
                    continue;
                }
                if ( preg_match( '/^@(-webkit-)?keyframes/i', $prelude ) ) {
                    if ( ! $media_stack ) {
                        $keyframes[] = $block;
                    }
                    continue;
                }
                if ( strpos( $prelude, '@' ) === 0 ) {
                    continue;
                }
                foreach ( self::split_selector_list( $prelude ) as $sel ) {
                    $class_name = self::first_class_in_selector( $sel );
                    if ( $class_name === null ) {
                        continue;
                    }
                    $rule = $sel . " {\n" . self::format_css_body( $block['body'] ) . "\n}";
                    for ( $i = count( $media_stack ) - 1; $i >= 0; $i-- ) {
                        $rule = $media_stack[ $i ] . " {\n" . $rule . "\n}";
                    }
                    $classes[ $class_name ] = ( $classes[ $class_name ] ?? '' ) . $rule . "\n\n";
                }
            }
        };
        $walk( $css, array() );

        foreach ( $keyframes as $block ) {
            if ( ! preg_match( '/keyframes\s+([\w-]+)/i', $block['prelude'], $nm ) ) {
                continue;
            }
            $uses = '/animation(?:-name)?\s*:[^;{}]*?(?:^|[\s,:])' . preg_quote( $nm[1], '/' ) . '(?![\w-])/i';
            $text = $block['prelude'] . ' {' . $block['body'] . "}\n\n";
            foreach ( array_keys( $classes ) as $name ) {
                if ( preg_match( $uses, $classes[ $name ] ) ) {
                    $classes[ $name ] .= $text;
                }
            }
        }

        foreach ( array_keys( $classes ) as $name ) {
            $classes[ $name ] = trim( preg_replace( '/\n{3,}/', "\n\n", $classes[ $name ] ) );
        }
        return $classes;
    }

    /** Split a CSS value on whitespace, keeping var()/rgb() groups intact. */
    public static function split_tokens( $v ) {
        $out   = array();
        $buf   = '';
        $depth = 0;
        $v     = trim( (string) $v );
        $len   = strlen( $v );
        for ( $i = 0; $i < $len; $i++ ) {
            $ch = $v[ $i ];
            if ( $ch === '(' ) {
                $depth++;
            }
            if ( $ch === ')' ) {
                $depth--;
            }
            if ( ctype_space( $ch ) && $depth === 0 ) {
                if ( $buf !== '' ) {
                    $out[] = $buf;
                }
                $buf = '';
                continue;
            }
            $buf .= $ch;
        }
        if ( $buf !== '' ) {
            $out[] = $buf;
        }
        return $out;
    }

    /**
     * Extract CSS custom properties from the first :root { ... } block.
     * @return array{variables: array<int, array{name: string, value: string}>, raw: string}
     */
    public static function extract_root_variables( $css ) {
        $css = (string) $css;
        if ( ! preg_match( '/:root\s*\{/', $css, $m, PREG_OFFSET_CAPTURE ) ) {
            return array( 'variables' => array(), 'raw' => '' );
        }
        $root_start = $m[0][1];
        $brace_idx  = strpos( $css, '{', $root_start );
        $depth      = 1;
        $end        = $brace_idx + 1;
        $len        = strlen( $css );
        while ( $depth > 0 && $end < $len ) {
            if ( $css[ $end ] === '{' ) {
                $depth++;
            } elseif ( $css[ $end ] === '}' ) {
                $depth--;
            }
            $end++;
        }
        $full = substr( $css, $root_start, $end - $root_start );
        $body = substr( $css, $brace_idx + 1, $end - 1 - $brace_idx - 1 );

        $vars = array();
        if ( preg_match_all( '/--([a-zA-Z0-9_-]+)\s*:\s*([^;]+);/', $body, $all, PREG_SET_ORDER ) ) {
            foreach ( $all as $row ) {
                $vars[] = array( 'name' => $row[1], 'value' => trim( $row[2] ) );
            }
        }
        return array( 'variables' => $vars, 'raw' => $full );
    }

    /**
     * Non-class CSS blocks (body, html, *, :root, @font-face, @import ...) that
     * parse_css_rules() does not capture. Port of extractGlobalCSS().
     */
    public static function extract_global_css( $css ) {
        $css    = self::strip_comments( $css );
        $result = '';
        $i      = 0;
        $len    = strlen( $css );

        while ( $i < $len ) {
            $brace_idx = strpos( $css, '{', $i );
            if ( $brace_idx === false ) {
                $tail = trim( substr( $css, $i ) );
                if ( $tail !== '' ) {
                    $result .= $tail . "\n";
                }
                break;
            }

            $sel_start = $brace_idx - 1;
            while ( $sel_start >= 0 && $css[ $sel_start ] !== '}' ) {
                $sel_start--;
            }
            $sel_start++;

            $selector_text = trim( substr( $css, $sel_start, $brace_idx - $sel_start ) );

            $depth = 1;
            $end   = $brace_idx + 1;
            while ( $depth > 0 && $end < $len ) {
                if ( $css[ $end ] === '{' ) {
                    $depth++;
                } elseif ( $css[ $end ] === '}' ) {
                    $depth--;
                }
                $end++;
            }

            $full_block = substr( $css, $sel_start, $end - $sel_start );
            if ( $selector_text !== '' && strpos( $selector_text, '.' ) === false && strpos( $selector_text, '@media' ) !== 0 ) {
                $result .= $full_block . "\n";
            }
            $i = $end;
        }

        $first_brace = strpos( $css, '{' );
        if ( $first_brace !== false ) {
            $preamble = trim( substr( $css, 0, $first_brace ) );
            // A quoted URL may contain ';' (Google Fonts: wght@400;700), so skip quoted strings.
            if ( $preamble !== '' && preg_match_all( '/@(import|charset|namespace)(?:"[^"]*"|\'[^\']*\'|[^;"\'])+;/i', $preamble, $at ) ) {
                foreach ( $at[0] as $r ) {
                    if ( strpos( $result, $r ) === false ) {
                        $result = $r . "\n" . $result;
                    }
                }
            }
        }

        return trim( $result );
    }

    /** Google Fonts / any @import url(...) targets. */
    public static function extract_imports( $css ) {
        preg_match_all( '/@import\s+url\([\'"]?([^\'")\s]+)[\'"]?\)/i', (string) $css, $m );
        return $m[1];
    }
}
