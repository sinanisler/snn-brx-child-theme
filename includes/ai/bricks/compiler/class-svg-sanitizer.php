<?php
/**
 * Strip anything executable before an SVG is inlined or uploaded.
 * Port of sanitizeSvg() in ai-agent-and-chat-bricks.php. Uploads still pass
 * through Bricks' own sanitizer as well (wp_handle_upload_prefilter).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SNN_Bricks_Svg_Sanitizer {

    const REMOVE_TAGS = array( 'script', 'foreignobject', 'animate', 'set', 'handler' );

    /** @return string Clean SVG markup, or '' when it is not a readable SVG. */
    public static function sanitize( $text ) {
        $text = trim( (string) $text );
        if ( $text === '' || preg_match( '/<!ENTITY/i', $text ) ) {
            return '';
        }
        $doc  = new \DOMDocument();
        $prev = libxml_use_internal_errors( true );
        $ok   = $doc->loadXML( $text, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING );
        libxml_clear_errors();
        libxml_use_internal_errors( $prev );
        if ( ! $ok || ! $doc->documentElement || strtolower( $doc->documentElement->localName ) !== 'svg' ) {
            return '';
        }

        $doomed = array();
        foreach ( $doc->getElementsByTagName( '*' ) as $el ) {
            if ( in_array( strtolower( $el->localName ), self::REMOVE_TAGS, true ) ) {
                $doomed[] = $el;
            }
        }
        foreach ( $doomed as $el ) {
            if ( $el->parentNode ) {
                $el->parentNode->removeChild( $el );
            }
        }

        $walk = function ( $el ) use ( &$walk ) {
            $remove = array();
            foreach ( $el->attributes as $attr ) {
                $name = strtolower( $attr->nodeName );
                $val  = strtolower( trim( $attr->nodeValue ) );
                if ( strpos( $name, 'on' ) === 0 ) {
                    $remove[] = $attr->nodeName;
                    continue;
                }
                if ( $name === 'href' || $name === 'xlink:href' ) {
                    if ( strpos( $val, '#' ) !== 0 ) {
                        $remove[] = $attr->nodeName;
                    }
                    continue;
                }
                if ( $name !== 'style' && preg_match( '/^javascript:/i', $val ) ) {
                    $remove[] = $attr->nodeName;
                }
            }
            foreach ( $remove as $n ) {
                $el->removeAttribute( $n );
            }
            foreach ( $el->childNodes as $child ) {
                if ( $child->nodeType === XML_ELEMENT_NODE ) {
                    $walk( $child );
                }
            }
        };
        $walk( $doc->documentElement );

        return (string) $doc->saveXML( $doc->documentElement );
    }
}
