<?php
/**
 * CSS selector matching for hosts without PHP 8.4's native Element::matches().
 *
 * Covers what design CSS uses: type, *, #id, .class, attribute selectors
 * (= ~= |= ^= $= *=, with the i flag), the four combinators, structural
 * pseudo-classes (:first-child, :nth-child(An+B [of S]), :*-of-type, :only-*,
 * :empty, :root), :not() / :is() / :where() / :has() with selector lists, and
 * :checked / :disabled / :enabled / :link / :any-link from attributes.
 *
 * User-action states (:hover, :focus ...) never match, exactly like el.matches()
 * on a detached DOMParser document. Anything unparseable returns false, like the
 * try/catch around el.matches() in the JS compiler.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SNN_Bricks_Selector_Matcher {

    private static $parsed = array();

    const STATE_PSEUDOS = array(
        'hover', 'focus', 'focus-within', 'focus-visible', 'active', 'visited', 'target',
        'target-within', 'current', 'past', 'future', 'playing', 'paused', 'placeholder-shown',
        'autofill', 'user-invalid', 'user-valid', 'invalid', 'valid', 'in-range', 'out-of-range',
        'indeterminate', 'default', 'defined', 'fullscreen', 'modal', 'popover-open', 'open',
    );

    public static function matches( $el, $selector ) {
        try {
            $list = self::parse_list( (string) $selector );
        } catch ( \Throwable $e ) {
            return false;
        }
        foreach ( $list as $complex ) {
            if ( self::match_complex( $el, $complex, count( $complex ) - 1 ) ) {
                return true;
            }
        }
        return false;
    }

    // ── parsing ────────────────────────────────────────────────────────────

    /** @return array<int, array<int, array{combinator: string, compound: array}>> */
    private static function parse_list( $selector ) {
        if ( isset( self::$parsed[ $selector ] ) ) {
            return self::$parsed[ $selector ];
        }
        $out = array();
        foreach ( SNN_Bricks_Css::split_selector_list( $selector ) as $complex ) {
            $out[] = self::parse_complex( $complex );
        }
        if ( ! $out ) {
            throw new \Exception( 'empty selector' );
        }
        if ( count( self::$parsed ) > 2000 ) {
            self::$parsed = array();
        }
        self::$parsed[ $selector ] = $out;
        return $out;
    }

    /** Split a complex selector into compounds joined by combinators (right-most last). */
    private static function parse_complex( $s ) {
        $s     = trim( $s );
        $len   = strlen( $s );
        $parts = array();
        $buf   = '';
        $comb  = '';
        $depth = 0;
        $quote = null;

        $flush = function () use ( &$buf, &$parts, &$comb ) {
            if ( trim( $buf ) === '' ) {
                return;
            }
            $parts[] = array( 'combinator' => $comb, 'compound' => self::parse_compound( trim( $buf ) ) );
            $buf     = '';
            $comb    = '';
        };

        for ( $i = 0; $i < $len; $i++ ) {
            $ch = $s[ $i ];
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
            if ( $ch === '\\' && $i + 1 < $len ) {
                $buf .= $ch . $s[ ++$i ];
                continue;
            }
            if ( $ch === '(' || $ch === '[' ) {
                $depth++;
            } elseif ( $ch === ')' || $ch === ']' ) {
                $depth--;
            }
            if ( $depth === 0 && ( $ch === '>' || $ch === '+' || $ch === '~' || ctype_space( $ch ) ) ) {
                $had = trim( $buf ) !== '';
                $flush();
                if ( ctype_space( $ch ) ) {
                    if ( $had || $parts ) {
                        $comb = $comb === '' ? ' ' : $comb;
                    }
                } else {
                    $comb = $ch;
                }
                continue;
            }
            $buf .= $ch;
        }
        $flush();
        if ( ! $parts ) {
            throw new \Exception( 'empty compound' );
        }
        // A leading combinator (relative selector inside :has) stays on the first part.
        return $parts;
    }

    /** Parse "a.b#c[d]:e(f)" into simple selector tests. */
    private static function parse_compound( $s ) {
        $tests = array();
        $len   = strlen( $s );
        $i     = 0;

        if ( $i < $len && ( $s[0] === '*' || preg_match( '/^[a-zA-Z_-]/', $s ) ) ) {
            if ( $s[0] === '*' ) {
                $i = 1;
            } else {
                preg_match( '/^[a-zA-Z_-][\w-]*/', $s, $m );
                $tests[] = array( 'type', strtolower( $m[0] ) );
                $i       = strlen( $m[0] );
            }
        }

        while ( $i < $len ) {
            $ch   = $s[ $i ];
            $rest = substr( $s, $i );
            if ( $ch === '#' && preg_match( '/^#((?:[\w-]|\\\\.)+)/', $rest, $m ) ) {
                $tests[] = array( 'id', stripslashes( $m[1] ) );
                $i      += strlen( $m[0] );
            } elseif ( $ch === '.' && preg_match( '/^\.((?:[\w-]|\\\\.)+)/', $rest, $m ) ) {
                $tests[] = array( 'class', stripslashes( $m[1] ) );
                $i      += strlen( $m[0] );
            } elseif ( $ch === '[' ) {
                $end = self::matching( $s, $i, '[', ']' );
                $tests[] = self::parse_attr( substr( $s, $i + 1, $end - $i - 1 ) );
                $i       = $end + 1;
            } elseif ( $ch === ':' ) {
                if ( isset( $s[ $i + 1 ] ) && $s[ $i + 1 ] === ':' ) {
                    $tests[] = array( 'never' ); // pseudo-element
                    preg_match( '/^::[\w-]+/', $rest, $m );
                    $i += strlen( $m[0] ?? '::' );
                    if ( isset( $s[ $i ] ) && $s[ $i ] === '(' ) {
                        $i = self::matching( $s, $i, '(', ')' ) + 1;
                    }
                    continue;
                }
                if ( ! preg_match( '/^:([\w-]+)/', $rest, $m ) ) {
                    throw new \Exception( 'bad pseudo' );
                }
                $name = strtolower( $m[1] );
                $i   += strlen( $m[0] );
                $arg  = null;
                if ( isset( $s[ $i ] ) && $s[ $i ] === '(' ) {
                    $end = self::matching( $s, $i, '(', ')' );
                    $arg = substr( $s, $i + 1, $end - $i - 1 );
                    $i   = $end + 1;
                }
                $tests[] = self::parse_pseudo( $name, $arg );
            } else {
                throw new \Exception( 'unexpected ' . $ch );
            }
        }
        return $tests;
    }

    private static function matching( $s, $at, $open, $close ) {
        $depth = 0;
        $quote = null;
        $len   = strlen( $s );
        for ( $i = $at; $i < $len; $i++ ) {
            $ch = $s[ $i ];
            if ( $quote !== null ) {
                if ( $ch === $quote ) {
                    $quote = null;
                }
                continue;
            }
            if ( $ch === '"' || $ch === "'" ) {
                $quote = $ch;
            } elseif ( $ch === $open ) {
                $depth++;
            } elseif ( $ch === $close ) {
                $depth--;
                if ( $depth === 0 ) {
                    return $i;
                }
            }
        }
        throw new \Exception( 'unbalanced' );
    }

    private static function parse_attr( $inner ) {
        if ( ! preg_match( '/^\s*([\w:-]+)\s*(?:([~|^$*]?=)\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s\]]+))\s*(i|s)?)?\s*$/i', $inner, $m ) ) {
            throw new \Exception( 'bad attribute selector' );
        }
        $value = null;
        if ( isset( $m[2] ) && $m[2] !== '' ) {
            $value = $m[3] !== '' ? $m[3] : ( ( $m[4] ?? '' ) !== '' ? $m[4] : ( $m[5] ?? '' ) );
        }
        return array( 'attr', strtolower( $m[1] ), $m[2] ?? '', $value, strtolower( $m[6] ?? '' ) === 'i' );
    }

    private static function parse_nth( $arg ) {
        $arg = strtolower( trim( $arg ) );
        $of  = null;
        if ( preg_match( '/^(.*?)\s+of\s+(.+)$/', $arg, $m ) ) {
            $arg = trim( $m[1] );
            $of  = $m[2];
        }
        if ( $arg === 'odd' ) {
            return array( 2, 1, $of );
        }
        if ( $arg === 'even' ) {
            return array( 2, 0, $of );
        }
        if ( preg_match( '/^([+-]?\d*)n\s*(?:([+-])\s*(\d+))?$/', $arg, $m ) ) {
            $a = $m[1] === '' || $m[1] === '+' ? 1 : ( $m[1] === '-' ? -1 : (int) $m[1] );
            $b = isset( $m[3] ) ? (int) $m[3] * ( $m[2] === '-' ? -1 : 1 ) : 0;
            return array( $a, $b, $of );
        }
        if ( preg_match( '/^[+-]?\d+$/', $arg ) ) {
            return array( 0, (int) $arg, $of );
        }
        throw new \Exception( 'bad nth' );
    }

    private static function parse_pseudo( $name, $arg ) {
        switch ( $name ) {
            case 'not':
            case 'is':
            case 'matches':
            case 'where':
            case 'any':
                return array( $name === 'not' ? 'not' : 'is', $arg );
            case 'has':
                return array( 'has', $arg );
            case 'nth-child':
            case 'nth-last-child':
            case 'nth-of-type':
            case 'nth-last-of-type':
                return array( 'nth', $name, self::parse_nth( (string) $arg ) );
            case 'first-child':
                return array( 'nth', 'nth-child', array( 0, 1, null ) );
            case 'last-child':
                return array( 'nth', 'nth-last-child', array( 0, 1, null ) );
            case 'first-of-type':
                return array( 'nth', 'nth-of-type', array( 0, 1, null ) );
            case 'last-of-type':
                return array( 'nth', 'nth-last-of-type', array( 0, 1, null ) );
            case 'only-child':
            case 'only-of-type':
            case 'empty':
            case 'root':
            case 'checked':
            case 'disabled':
            case 'enabled':
            case 'link':
            case 'any-link':
            case 'required':
            case 'optional':
            case 'read-only':
            case 'read-write':
            case 'scope':
                return array( 'simple', $name );
            case 'before':
            case 'after':
            case 'first-line':
            case 'first-letter':
                return array( 'never' );
        }
        if ( in_array( $name, self::STATE_PSEUDOS, true ) ) {
            return array( 'never' );
        }
        throw new \Exception( 'unsupported pseudo :' . $name );
    }

    // ── matching ───────────────────────────────────────────────────────────

    private static function match_complex( $el, array $parts, $idx ) {
        if ( ! self::match_compound( $el, $parts[ $idx ]['compound'] ) ) {
            return false;
        }
        if ( $idx === 0 ) {
            return true;
        }
        $comb = $parts[ $idx ]['combinator'];
        switch ( $comb ) {
            case '>':
                $p = SNN_Bricks_Dom::parent( $el );
                return $p ? self::match_complex( $p, $parts, $idx - 1 ) : false;
            case '+':
                $prev = self::prev_element( $el );
                return $prev ? self::match_complex( $prev, $parts, $idx - 1 ) : false;
            case '~':
                for ( $prev = self::prev_element( $el ); $prev; $prev = self::prev_element( $prev ) ) {
                    if ( self::match_complex( $prev, $parts, $idx - 1 ) ) {
                        return true;
                    }
                }
                return false;
            default:
                for ( $p = SNN_Bricks_Dom::parent( $el ); $p; $p = SNN_Bricks_Dom::parent( $p ) ) {
                    if ( self::match_complex( $p, $parts, $idx - 1 ) ) {
                        return true;
                    }
                }
                return false;
        }
    }

    private static function prev_element( $el ) {
        for ( $n = $el->previousSibling; $n; $n = $n->previousSibling ) {
            if ( $n->nodeType === XML_ELEMENT_NODE ) {
                return $n;
            }
        }
        return null;
    }

    private static function next_element( $el ) {
        for ( $n = $el->nextSibling; $n; $n = $n->nextSibling ) {
            if ( $n->nodeType === XML_ELEMENT_NODE ) {
                return $n;
            }
        }
        return null;
    }

    private static function match_compound( $el, array $tests ) {
        foreach ( $tests as $t ) {
            if ( ! self::match_test( $el, $t ) ) {
                return false;
            }
        }
        return true;
    }

    private static function classes_of( $el ) {
        $c = SNN_Bricks_Dom::attr( $el, 'class' );
        return $c === null ? array() : preg_split( '/\s+/', trim( $c ), -1, PREG_SPLIT_NO_EMPTY );
    }

    private static function match_test( $el, array $t ) {
        switch ( $t[0] ) {
            case 'never':
                return false;
            case 'type':
                return SNN_Bricks_Dom::tag( $el ) === $t[1];
            case 'id':
                return SNN_Bricks_Dom::attr( $el, 'id' ) === $t[1];
            case 'class':
                return in_array( $t[1], self::classes_of( $el ), true );
            case 'attr':
                return self::match_attr( $el, $t[1], $t[2], $t[3], $t[4] );
            case 'not':
                return ! self::matches( $el, $t[1] );
            case 'is':
                return self::matches( $el, $t[1] );
            case 'has':
                return self::match_has( $el, (string) $t[1] );
            case 'nth':
                return self::match_nth( $el, $t[1], $t[2] );
            case 'simple':
                return self::match_simple( $el, $t[1] );
        }
        return false;
    }

    private static function match_attr( $el, $name, $op, $value, $ci ) {
        $actual = SNN_Bricks_Dom::attr( $el, $name );
        if ( $actual === null ) {
            return false;
        }
        if ( $op === '' ) {
            return true;
        }
        if ( $ci ) {
            $actual = strtolower( $actual );
            $value  = strtolower( (string) $value );
        }
        $value = (string) $value;
        switch ( $op ) {
            case '=':
                return $actual === $value;
            case '~=':
                return $value !== '' && in_array( $value, preg_split( '/\s+/', $actual, -1, PREG_SPLIT_NO_EMPTY ), true );
            case '|=':
                return $actual === $value || strpos( $actual, $value . '-' ) === 0;
            case '^=':
                return $value !== '' && strpos( $actual, $value ) === 0;
            case '$=':
                return $value !== '' && substr( $actual, -strlen( $value ) ) === $value;
            case '*=':
                return $value !== '' && strpos( $actual, $value ) !== false;
        }
        return false;
    }

    private static function match_nth( $el, $kind, array $nth ) {
        list( $a, $b, $of ) = $nth;
        $parent = $el->parentNode;
        if ( ! $parent ) {
            return false;
        }
        $siblings = array();
        foreach ( $parent->childNodes as $n ) {
            if ( $n->nodeType !== XML_ELEMENT_NODE ) {
                continue;
            }
            if ( ( $kind === 'nth-of-type' || $kind === 'nth-last-of-type' ) && SNN_Bricks_Dom::tag( $n ) !== SNN_Bricks_Dom::tag( $el ) ) {
                continue;
            }
            if ( $of !== null && ! self::matches( $n, $of ) ) {
                continue;
            }
            $siblings[] = $n;
        }
        if ( $kind === 'nth-last-child' || $kind === 'nth-last-of-type' ) {
            $siblings = array_reverse( $siblings );
        }
        $pos = array_search( $el, $siblings, true );
        if ( $pos === false ) {
            return false;
        }
        $pos++;
        if ( $a === 0 ) {
            return $pos === $b;
        }
        $n = ( $pos - $b ) / $a;
        return $n >= 0 && floor( $n ) == $n; // phpcs:ignore Universal.Operators.StrictComparisons
    }

    private static function match_simple( $el, $name ) {
        $tag = SNN_Bricks_Dom::tag( $el );
        switch ( $name ) {
            case 'only-child':
                return ! self::prev_element( $el ) && ! self::next_element( $el );
            case 'only-of-type':
                foreach ( SNN_Bricks_Dom::children( $el->parentNode ) as $sib ) {
                    if ( $sib !== $el && SNN_Bricks_Dom::tag( $sib ) === $tag ) {
                        return false;
                    }
                }
                return true;
            case 'empty':
                foreach ( SNN_Bricks_Dom::child_nodes( $el ) as $n ) {
                    if ( $n->nodeType === XML_ELEMENT_NODE || ( $n->nodeType === XML_TEXT_NODE && $n->textContent !== '' ) ) {
                        return false;
                    }
                }
                return true;
            case 'root':
                return $tag === 'html';
            case 'scope':
                return false;
            case 'checked':
                return $el->hasAttribute( 'checked' ) || ( $tag === 'option' && $el->hasAttribute( 'selected' ) );
            case 'disabled':
                return in_array( $tag, array( 'button', 'input', 'select', 'textarea', 'option', 'fieldset' ), true ) && $el->hasAttribute( 'disabled' );
            case 'enabled':
                return in_array( $tag, array( 'button', 'input', 'select', 'textarea', 'option', 'fieldset' ), true ) && ! $el->hasAttribute( 'disabled' );
            case 'link':
            case 'any-link':
                return in_array( $tag, array( 'a', 'area' ), true ) && $el->hasAttribute( 'href' );
            case 'required':
                return $el->hasAttribute( 'required' );
            case 'optional':
                return in_array( $tag, array( 'input', 'select', 'textarea' ), true ) && ! $el->hasAttribute( 'required' );
            case 'read-only':
                return ! in_array( $tag, array( 'input', 'textarea' ), true ) || $el->hasAttribute( 'readonly' );
            case 'read-write':
                return in_array( $tag, array( 'input', 'textarea' ), true ) && ! $el->hasAttribute( 'readonly' );
        }
        return false;
    }

    /** :has() with relative selectors: "> a", "+ b", "~ c" or a plain descendant. */
    private static function match_has( $el, $arg ) {
        foreach ( SNN_Bricks_Css::split_selector_list( $arg ) as $rel ) {
            $rel  = trim( $rel );
            $comb = ' ';
            if ( $rel !== '' && in_array( $rel[0], array( '>', '+', '~' ), true ) ) {
                $comb = $rel[0];
                $rel  = trim( substr( $rel, 1 ) );
            }
            if ( $rel === '' ) {
                continue;
            }
            $candidates = array();
            if ( $comb === '>' ) {
                $candidates = SNN_Bricks_Dom::children( $el );
            } elseif ( $comb === '+' ) {
                $n = self::next_element( $el );
                $candidates = $n ? array( $n ) : array();
            } elseif ( $comb === '~' ) {
                for ( $n = self::next_element( $el ); $n; $n = self::next_element( $n ) ) {
                    $candidates[] = $n;
                }
            } else {
                $candidates = SNN_Bricks_Dom::descendants( $el );
            }
            // The relative selector may itself continue ("> a span"): match each
            // candidate against the rest, anchored below $el.
            foreach ( $candidates as $c ) {
                if ( self::matches( $c, $rel ) ) {
                    return true;
                }
                if ( $comb !== ' ' ) {
                    foreach ( SNN_Bricks_Dom::descendants( $c ) as $d ) {
                        if ( self::matches( $d, $rel ) && strpos( $rel, ' ' ) !== false ) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }
}
