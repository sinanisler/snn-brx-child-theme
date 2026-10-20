<?php
/**
 * DOM adapter for the PHP HTML-to-Bricks compiler.
 *
 * The JS compiler runs on the browser's DOMParser. PHP 8.4+ ships the same
 * HTML5 parsing algorithm (Dom\HTMLDocument) plus a native Element::matches(),
 * so on those hosts the PHP port sees exactly the tree the builder sees. Older
 * PHP falls back to libxml's DOMDocument and the compiler's own selector
 * matcher (class-selector-matcher.php).
 *
 * Every DOM access in the compiler goes through this class, so the rest of the
 * port never has to care which of the two APIs is underneath.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SNN_Bricks_Dom {

    /** Whether the HTML5 parser (PHP 8.4+) is available. */
    public static function has_html5() {
        static $on = null;
        if ( $on === null ) {
            /** Filter: false forces the libxml fallback (used by the parity tests). */
            $on = (bool) apply_filters( 'snn_bricks_html5_parser', class_exists( '\Dom\HTMLDocument' ) );
        }
        return $on;
    }

    /**
     * Parse an HTML string like DOMParser.parseFromString(html, 'text/html').
     *
     * @return array{doc: object, body: object}
     */
    public static function parse( $html ) {
        $html = (string) $html;

        if ( self::has_html5() ) {
            $doc = \Dom\HTMLDocument::createFromString(
                '<!DOCTYPE html>' . $html,
                LIBXML_NOERROR | LIBXML_COMPACT,
                'UTF-8'
            );
            return array( 'doc' => $doc, 'body' => $doc->body );
        }

        $doc = new \DOMDocument();
        $prev = libxml_use_internal_errors( true );
        $source = preg_match( '/<body[\s>]/i', $html )
            ? $html
            : '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' . $html . '</body></html>';
        if ( ! preg_match( '/<meta[^>]+charset/i', $source ) ) {
            $source = '<?xml encoding="utf-8"?>' . $source;
        }
        $doc->loadHTML( $source, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT );
        libxml_clear_errors();
        libxml_use_internal_errors( $prev );

        $body = $doc->getElementsByTagName( 'body' )->item( 0 );
        if ( ! $body ) {
            $body = $doc->createElement( 'body' );
            $doc->appendChild( $body );
        }
        return array( 'doc' => $doc, 'body' => $body );
    }

    public static function is_element( $node ) {
        return $node && $node->nodeType === XML_ELEMENT_NODE;
    }

    /** Lowercase tag name. */
    public static function tag( $el ) {
        $name = isset( $el->localName ) && $el->localName ? $el->localName : $el->nodeName;
        return strtolower( (string) $name );
    }

    /** getAttribute() that returns null for a missing attribute, like the DOM. */
    public static function attr( $el, $name ) {
        if ( ! self::is_element( $el ) || ! $el->hasAttribute( $name ) ) {
            return null;
        }
        return (string) $el->getAttribute( $name );
    }

    /** All attributes as [name => value], in document order. */
    public static function attributes( $el ) {
        $out = array();
        if ( ! self::is_element( $el ) || ! $el->attributes ) {
            return $out;
        }
        foreach ( $el->attributes as $attr ) {
            $out[ $attr->nodeName ] = (string) $attr->nodeValue;
        }
        return $out;
    }

    /** Element children (el.children). */
    public static function children( $el ) {
        $out = array();
        if ( ! $el || ! $el->childNodes ) {
            return $out;
        }
        foreach ( $el->childNodes as $child ) {
            if ( $child->nodeType === XML_ELEMENT_NODE ) {
                $out[] = $child;
            }
        }
        return $out;
    }

    /** All child nodes (el.childNodes) as a plain array, safe against live-list mutation. */
    public static function child_nodes( $el ) {
        $out = array();
        if ( ! $el || ! $el->childNodes ) {
            return $out;
        }
        foreach ( $el->childNodes as $child ) {
            $out[] = $child;
        }
        return $out;
    }

    /** parentElement: null at the document. */
    public static function parent( $el ) {
        $p = $el ? $el->parentNode : null;
        return self::is_element( $p ) ? $p : null;
    }

    public static function text( $node ) {
        return $node ? (string) $node->textContent : '';
    }

    /** Every descendant element, document order (querySelectorAll('*')). */
    public static function descendants( $root ) {
        $out = array();
        $walk = function ( $node ) use ( &$walk, &$out ) {
            foreach ( self::children( $node ) as $child ) {
                $out[] = $child;
                $walk( $child );
            }
        };
        $walk( $root );
        return $out;
    }

    /** Descendant elements whose tag is in $tags (querySelectorAll('i, span, svg')). */
    public static function find_tags( $root, array $tags ) {
        $set = array_flip( $tags );
        return array_values( array_filter( self::descendants( $root ), function ( $el ) use ( $set ) {
            return isset( $set[ self::tag( $el ) ] );
        } ) );
    }

    /** Nearest ancestor-or-self with this tag (el.closest('svg')). */
    public static function closest( $el, $tag ) {
        for ( $n = $el; self::is_element( $n ); $n = $n->parentNode ) {
            if ( self::tag( $n ) === $tag ) {
                return $n;
            }
        }
        return null;
    }

    public static function contains( $ancestor, $node ) {
        for ( $n = $node; $n; $n = $n->parentNode ) {
            if ( $n === $ancestor ) {
                return true;
            }
        }
        return false;
    }

    public static function inner_html( $el ) {
        if ( self::has_html5() && $el instanceof \Dom\Element ) {
            return (string) $el->innerHTML;
        }
        $html = '';
        foreach ( self::child_nodes( $el ) as $child ) {
            $html .= $el->ownerDocument->saveHTML( $child );
        }
        return $html;
    }

    public static function outer_html( $el ) {
        if ( self::has_html5() && $el instanceof \Dom\Element ) {
            return (string) $el->ownerDocument->saveHtml( $el );
        }
        return (string) $el->ownerDocument->saveHTML( $el );
    }

    /** Serialize an SVG element as XML (XMLSerializer.serializeToString). */
    public static function xml( $el ) {
        if ( self::has_html5() && $el instanceof \Dom\Element ) {
            // XML serialization keeps case-sensitive names (viewBox, linearGradient).
            $xml = \Dom\XMLDocument::createEmpty();
            $copy = $xml->importNode( $el, true );
            return (string) $xml->saveXml( $copy );
        }
        return (string) $el->ownerDocument->saveXML( $el );
    }

    public static function remove( $node ) {
        if ( $node && $node->parentNode ) {
            $node->parentNode->removeChild( $node );
        }
    }

    /** el.matches(selector) — false for invalid selectors, as in the browser's try/catch callers. */
    public static function matches( $el, $selector ) {
        if ( ! self::is_element( $el ) ) {
            return false;
        }
        if ( self::has_html5() && method_exists( $el, 'matches' ) ) {
            try {
                return (bool) $el->matches( $selector );
            } catch ( \Throwable $e ) {
                return false;
            }
        }
        return SNN_Bricks_Selector_Matcher::matches( $el, $selector );
    }

    /**
     * Visible text before and after a node inside root; text inside SVGs and
     * inside the node itself is ignored (textAround in the JS compiler).
     */
    public static function text_around( $root, $node ) {
        $before = '';
        $after  = '';
        $seen   = false;
        $walk = function ( $n ) use ( &$walk, &$before, &$after, &$seen, $node ) {
            foreach ( self::child_nodes( $n ) as $child ) {
                if ( $child === $node ) {
                    $seen = true;
                    continue;
                }
                if ( $child->nodeType === XML_TEXT_NODE || $child->nodeType === XML_CDATA_SECTION_NODE ) {
                    $parent = self::parent( $child );
                    if ( $parent && self::closest( $parent, 'svg' ) ) {
                        continue;
                    }
                    if ( $seen ) {
                        $after .= $child->textContent;
                    } else {
                        $before .= $child->textContent;
                    }
                } elseif ( $child->nodeType === XML_ELEMENT_NODE ) {
                    $walk( $child );
                }
            }
        };
        $walk( $root );
        return array( 'before' => $before, 'after' => $after );
    }

    /** innerHTML of an element with one descendant removed, whitespace collapsed. */
    public static function inner_html_without( $el, $node ) {
        $clone = $el->cloneNode( true );
        if ( $node ) {
            $originals = self::descendants( $el );
            $clones    = self::descendants( $clone );
            $at        = array_search( $node, $originals, true );
            if ( $at !== false && isset( $clones[ $at ] ) ) {
                self::remove( $clones[ $at ] );
            }
        }
        return trim( preg_replace( '/\s+/', ' ', self::inner_html( $clone ) ) );
    }
}
