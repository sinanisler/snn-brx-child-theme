<?php
/**
 * @media evaluated against a concrete viewport width — the width of the Bricks
 * breakpoint being resolved — instead of being snapped to the nearest key.
 * Port of the "MEDIA QUERIES" part of html-to-bricks-translation.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SNN_Bricks_Media {

    private static $cache = array();

    private static function length_px( $num, $unit ) {
        $n = (float) $num;
        return ( $unit === 'em' || $unit === 'rem' ) ? $n * 16 : $n;
    }

    private static function compare( $width, $op, $px ) {
        switch ( $op ) {
            case '<':
                return $width < $px;
            case '<=':
                return $width <= $px;
            case '>':
                return $width > $px;
            case '>=':
                return $width >= $px;
            default:
                return $width == $px; // phpcs:ignore Universal.Operators.StrictComparisons
        }
    }

    private static function flip( $op ) {
        $map = array( '<' => '>', '<=' => '>=', '>' => '<', '>=' => '<=', '=' => '=' );
        return $map[ $op ] ?? $op;
    }

    /** Split on a keyword (and / or) outside parentheses. */
    private static function split_top_level_word( $str, $word ) {
        $parts = array();
        $depth = 0;
        $start = 0;
        $len   = strlen( $str );
        for ( $i = 0; $i < $len; $i++ ) {
            $ch = $str[ $i ];
            if ( $ch === '(' ) {
                $depth++;
            } elseif ( $ch === ')' ) {
                $depth--;
            } elseif ( $depth === 0 && ctype_space( $ch ) ) {
                if ( preg_match( '/^\s+' . $word . '\s+/', substr( $str, $i ), $m ) ) {
                    $parts[] = substr( $str, $start, $i - $start );
                    $i      += strlen( $m[0] ) - 1;
                    $start   = $i + 1;
                }
            }
        }
        $parts[] = substr( $str, $start );
        return array_values( array_filter( array_map( 'trim', $parts ), 'strlen' ) );
    }

    private static function wrapped_in_parens( $str ) {
        $len = strlen( $str );
        if ( $len < 2 || $str[0] !== '(' || $str[ $len - 1 ] !== ')' ) {
            return false;
        }
        $depth = 0;
        for ( $i = 0; $i < $len; $i++ ) {
            if ( $str[ $i ] === '(' ) {
                $depth++;
            } elseif ( $str[ $i ] === ')' ) {
                $depth--;
                if ( $depth === 0 && $i < $len - 1 ) {
                    return false;
                }
            }
        }
        return true;
    }

    private static function feature_matches( $feature, $width ) {
        $f = trim( preg_replace( '/\s+/', ' ', $feature ) );
        if ( preg_match( '/^(min|max)-width\s*:\s*(-?\d*\.?\d+)(px|em|rem)?$/', $f, $m ) ) {
            $px = self::length_px( $m[2], $m[3] ?? '' );
            return $m[1] === 'min' ? $width >= $px : $width <= $px;
        }
        if ( preg_match( '/^(-?\d*\.?\d+)(px|em|rem)?\s*(<=|<)\s*width\s*(<=|<)\s*(-?\d*\.?\d+)(px|em|rem)?$/', $f, $m ) ) {
            return self::compare( $width, self::flip( $m[3] ), self::length_px( $m[1], $m[2] ) )
                && self::compare( $width, $m[4], self::length_px( $m[5], $m[6] ?? '' ) );
        }
        if ( preg_match( '/^width\s*(<=|>=|<|>|=)\s*(-?\d*\.?\d+)(px|em|rem)?$/', $f, $m ) ) {
            return self::compare( $width, $m[1], self::length_px( $m[2], $m[3] ?? '' ) );
        }
        if ( preg_match( '/^(-?\d*\.?\d+)(px|em|rem)?\s*(<=|>=|<|>|=)\s*width$/', $f, $m ) ) {
            return self::compare( $width, self::flip( $m[3] ), self::length_px( $m[1], $m[2] ) );
        }
        // orientation, hover, prefers-*, height ... cannot be decided from a width.
        return false;
    }

    private static function condition_matches( $cond, $width ) {
        $c = trim( $cond );
        if ( $c === 'all' || $c === 'screen' ) {
            return true;
        }
        if ( preg_match( '/^(print|speech|tty|tv|projection|handheld|braille|embossed|aural)$/', $c ) ) {
            return false;
        }
        while ( self::wrapped_in_parens( $c ) ) {
            $c = trim( substr( $c, 1, -1 ) );
        }
        if ( preg_match( '/^not\s+/', $c ) ) {
            return ! self::condition_matches( preg_replace( '/^not\s+/', '', $c ), $width );
        }
        $ands = self::split_top_level_word( $c, 'and' );
        if ( count( $ands ) > 1 ) {
            foreach ( $ands as $x ) {
                if ( ! self::condition_matches( $x, $width ) ) {
                    return false;
                }
            }
            return true;
        }
        $ors = self::split_top_level_word( $c, 'or' );
        if ( count( $ors ) > 1 ) {
            foreach ( $ors as $x ) {
                if ( self::condition_matches( $x, $width ) ) {
                    return true;
                }
            }
            return false;
        }
        return self::feature_matches( $c, $width );
    }

    /** Does "@media <query>" apply at this viewport width? */
    public static function matches_width( $query, $width ) {
        $key = $query . '|' . $width;
        if ( isset( self::$cache[ $key ] ) ) {
            return self::$cache[ $key ];
        }
        $q = strtolower( trim( preg_replace( '/^@media\s*/i', '', (string) $query ) ) );
        $result = false;
        if ( $q === '' ) {
            $result = true;
        } else {
            foreach ( SNN_Bricks_Css::split_selector_list( $q ) as $part ) {
                $p      = trim( $part );
                $negate = false;
                if ( preg_match( '/^not\s+/', $p ) ) {
                    $negate = true;
                    $p      = preg_replace( '/^not\s+/', '', $p );
                }
                $p  = preg_replace( '/^only\s+/', '', $p );
                $ok = self::condition_matches( $p, $width );
                if ( $negate ? ! $ok : $ok ) {
                    $result = true;
                    break;
                }
            }
        }
        self::$cache[ $key ] = $result;
        return $result;
    }
}
