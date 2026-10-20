<?php
/**
 * Native Bricks layout settings — port of getBreakpointPlan(),
 * deriveNativeSettings(), computeNativeLayout(), clearNativeLayout() and
 * perBreakpointSetting() from html-to-bricks-translation.php.
 *
 * Bricks forces flex/column/wrap on its layout elements through .brxe-* rules.
 * The resolved layout is written back as element settings, which Bricks renders
 * at ID specificity, so the canvas matches the HTML preview one to one.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SNN_Bricks_Native_Layout {

    /** Bricks' default breakpoints, used when the site's own list is unavailable. */
    const DEFAULT_BREAKPOINTS = array(
        array( 'key' => 'desktop', 'width' => 1279, 'base' => true ),
        array( 'key' => 'tablet_portrait', 'width' => 991 ),
        array( 'key' => 'mobile_landscape', 'width' => 767 ),
        array( 'key' => 'mobile_portrait', 'width' => 478 ),
    );

    /** Element names Bricks treats as layout elements (_direction / _flexWrap / _columnGap). */
    const LAYOUT_ELEMENT_NAMES = array( 'section', 'container', 'block', 'div' );

    /** Every setting the cascade resolver owns, so a re-sync clears exactly these. */
    const NATIVE_LAYOUT_KEYS = array(
        '_display', '_direction', '_flexDirection', '_flexWrap', '_alignItems', '_justifyContent',
        '_rowGap', '_columnGap', '_gap', '_gridGap', '_gridTemplateColumns', '_gridTemplateRows',
        '_gridAutoColumns', '_gridAutoRows', '_gridAutoFlow', '_justifyItemsGrid', '_alignItemsGrid',
        '_justifyContentGrid', '_alignContentGrid', '_width', '_widthMax',
    );

    private static $plan = null;

    public static function is_layout( $name ) {
        return in_array( $name, self::LAYOUT_ELEMENT_NAMES, true );
    }

    /** Reset the cached plan (tests, or after breakpoints change). */
    public static function reset_plan() {
        self::$plan = null;
    }

    /**
     * The site's breakpoints arranged the way Bricks' own cascade inherits them.
     * @return array{mobileFirst: bool, base: array{key: string, width: int}, steps: array}
     */
    public static function breakpoint_plan( $override = null ) {
        if ( $override === null && self::$plan !== null ) {
            return self::$plan;
        }
        $list = self::DEFAULT_BREAKPOINTS;
        $live = $override;
        if ( $live === null && class_exists( '\Bricks\Breakpoints' ) ) {
            try {
                $live = \Bricks\Breakpoints::get_breakpoints();
            } catch ( \Throwable $e ) {
                $live = null;
            }
        }
        if ( is_array( $live ) ) {
            $parsed = array();
            foreach ( array_values( $live ) as $b ) {
                $b     = (array) $b;
                $key   = $b['key'] ?? '';
                $width = isset( $b['width'] ) ? (int) $b['width'] : 0;
                if ( $key && $width > 0 && empty( $b['paused'] ) ) {
                    $parsed[] = array( 'key' => $key, 'width' => $width, 'base' => ! empty( $b['base'] ) );
                }
            }
            if ( $parsed ) {
                $list = $parsed;
            }
        }

        $base = null;
        foreach ( $list as $b ) {
            if ( ! empty( $b['base'] ) ) {
                $base = $b;
                break;
            }
        }
        if ( ! $base ) {
            $base = $list[0];
            foreach ( $list as $b ) {
                if ( $b['width'] > $base['width'] ) {
                    $base = $b;
                }
            }
        }
        $others = array_values( array_filter( $list, function ( $b ) use ( $base ) {
            return $b['key'] !== $base['key'];
        } ) );
        $mobile_first = count( $others ) > 0;
        foreach ( $others as $b ) {
            if ( $b['width'] <= $base['width'] ) {
                $mobile_first = false;
            }
        }
        usort( $others, function ( $a, $b ) use ( $mobile_first ) {
            return $mobile_first ? $a['width'] - $b['width'] : $b['width'] - $a['width'];
        } );
        $base_width = $mobile_first ? min( $base['width'], $others[0]['width'] - 1 ) : $base['width'];

        $plan = array(
            'mobileFirst' => $mobile_first,
            'base'        => array( 'key' => $base['key'], 'width' => $base_width ),
            'steps'       => array_map( function ( $b ) {
                return array( 'key' => $b['key'], 'width' => $b['width'] );
            }, $others ),
        );
        if ( $override === null ) {
            self::$plan = $plan;
        }
        return $plan;
    }

    private static function apply_flex_gaps( array &$out, array $decls ) {
        $gap = $decls['gap'] ?? ( $decls['grid-gap'] ?? '' );
        $row = $decls['row-gap'] ?? '';
        $col = $decls['column-gap'] ?? '';
        if ( $gap !== '' ) {
            $parts = preg_split( '/\s+/', trim( $gap ) );
            if ( $row === '' ) {
                $row = $parts[0];
            }
            if ( $col === '' ) {
                $col = $parts[1] ?? $parts[0];
            }
        }
        if ( $row !== '' ) {
            $out['_rowGap'] = trim( $row );
        }
        if ( $col !== '' ) {
            $out['_columnGap'] = trim( $col );
        }
    }

    /** A declared width that may be wider than its parent, so max-width:100% would clip it. */
    public static function can_exceed_parent( $width ) {
        $w = strtolower( trim( (string) $width ) );
        if ( preg_match( '/^(auto|inherit|initial|unset)$/', $w ) ) {
            return false;
        }
        if ( preg_match( '/^(\d*\.?\d+)%$/', $w, $m ) && (float) $m[1] <= 100 ) {
            return false;
        }
        return true;
    }

    /** Translate resolved CSS into Bricks element settings (deriveNativeSettings). */
    public static function derive( array $decls, $bricks_name, $parent_decls ) {
        $out       = array();
        $is_layout = self::is_layout( $bricks_name );
        $dir_key   = $is_layout ? '_direction' : '_flexDirection';
        $display   = strtolower( trim( $decls['display'] ?? '' ) );
        $get       = function ( $k, $default = '' ) use ( $decls ) {
            $v = $decls[ $k ] ?? '';
            return $v !== '' ? trim( $v ) : $default;
        };

        if ( $is_layout ) {
            if ( $display === '' ) {
                $display = 'block';
            }
            if ( $display === 'flex' || $display === 'inline-flex' ) {
                $out['_display']        = $display;
                $out[ $dir_key ]        = $get( 'flex-direction', 'row' );
                $out['_flexWrap']       = $get( 'flex-wrap', 'nowrap' );
                $out['_alignItems']     = $get( 'align-items', 'stretch' );
                $out['_justifyContent'] = $get( 'justify-content', 'flex-start' );
                self::apply_flex_gaps( $out, $decls );
            } elseif ( $display === 'grid' || $display === 'inline-grid' ) {
                $out['_display'] = 'grid';
                $map = array(
                    'grid-template-columns' => '_gridTemplateColumns',
                    'grid-template-rows'    => '_gridTemplateRows',
                    'grid-auto-columns'     => '_gridAutoColumns',
                    'grid-auto-rows'        => '_gridAutoRows',
                    'grid-auto-flow'        => '_gridAutoFlow',
                    'justify-items'         => '_justifyItemsGrid',
                    'align-items'           => '_alignItemsGrid',
                    'justify-content'       => '_justifyContentGrid',
                    'align-content'         => '_alignContentGrid',
                );
                foreach ( $map as $prop => $key ) {
                    if ( ( $decls[ $prop ] ?? '' ) !== '' ) {
                        $out[ $key ] = trim( $decls[ $prop ] );
                    }
                }
                $g = $decls['gap'] ?? ( $decls['grid-gap'] ?? '' );
                if ( $g !== '' ) {
                    $out['_gridGap'] = trim( $g );
                }
            } else {
                $out['_display'] = $display;
            }
        } elseif ( $display !== '' ) {
            $out['_display'] = $display;
            if ( $display === 'flex' || $display === 'inline-flex' ) {
                if ( ( $decls['flex-direction'] ?? '' ) !== '' ) {
                    $out[ $dir_key ] = trim( $decls['flex-direction'] );
                }
                if ( ( $decls['align-items'] ?? '' ) !== '' ) {
                    $out['_alignItems'] = trim( $decls['align-items'] );
                }
                if ( ( $decls['justify-content'] ?? '' ) !== '' ) {
                    $out['_justifyContent'] = trim( $decls['justify-content'] );
                }
                if ( ( $decls['gap'] ?? '' ) !== '' ) {
                    $out['_gap'] = trim( $decls['gap'] );
                }
            }
        }

        // .brxe-container is hard-coded to 1100px and .brxe-block to 100%.
        if ( ( $decls['width'] ?? '' ) !== '' ) {
            $out['_width'] = trim( $decls['width'] );
        } elseif ( $bricks_name === 'container' ) {
            $p_display = strtolower( $parent_decls['display'] ?? '' );
            $p_dir     = strtolower( ( $parent_decls['flex-direction'] ?? '' ) !== '' ? $parent_decls['flex-direction'] : 'row' );
            $row_flex  = ( $p_display === 'flex' || $p_display === 'inline-flex' ) && strpos( $p_dir, 'column' ) !== 0;
            $out['_width'] = $row_flex ? 'auto' : '100%';
        } elseif ( $bricks_name === 'block' || $bricks_name === 'div' ) {
            $out['_width'] = 'auto';
        }

        // [class*=brxe-]{max-width:100%} caps every element; HTML has no such cap.
        if ( ( $decls['max-width'] ?? '' ) !== '' ) {
            $out['_widthMax'] = trim( $decls['max-width'] );
        } elseif ( ( $decls['width'] ?? '' ) !== '' && self::can_exceed_parent( $decls['width'] ) ) {
            $out['_widthMax'] = 'none';
        }

        return $out;
    }

    /**
     * Resolve an element's layout at every breakpoint and store it the way
     * Bricks does: base values unsuffixed, then only what changes, suffixed.
     */
    public static function compute( $el, $bricks_name, $ctx ) {
        $plan   = $ctx->plan;
        $p      = SNN_Bricks_Dom::parent( $el );
        $parent = $p && SNN_Bricks_Dom::tag( $p ) !== 'body' ? $p : null;
        $at = function ( $width ) use ( $el, $bricks_name, $ctx, $parent ) {
            return self::derive(
                SNN_Bricks_Cascade::resolve_decls_at( $el, $ctx->rule_set, $width ),
                $bricks_name,
                $parent ? SNN_Bricks_Cascade::resolve_decls_at( $parent, $ctx->rule_set, $width ) : null
            );
        };

        $out       = $at( $plan['base']['width'] );
        $inherited = $out;
        foreach ( $plan['steps'] as $step ) {
            $next = $at( $step['width'] );
            $keys = array_keys( $inherited );
            foreach ( array_keys( $next ) as $k ) {
                if ( ! in_array( $k, $keys, true ) ) {
                    $keys[] = $k;
                }
            }
            foreach ( $keys as $k ) {
                $value = array_key_exists( $k, $next ) ? $next[ $k ] : 'revert-layer';
                $prev  = array_key_exists( $k, $inherited ) ? $inherited[ $k ] : null;
                if ( $value === $prev ) {
                    continue;
                }
                if ( $value === 'revert-layer' && $prev === null ) {
                    continue;
                }
                $out[ $k . ':' . $step['key'] ] = $value;
                $inherited[ $k ]                = $value;
            }
        }
        return $out;
    }

    /** Remove every setting the cascade resolver owns, including breakpoint variants. */
    public static function clear( array &$settings ) {
        foreach ( array_keys( $settings ) as $k ) {
            $base = explode( ':', (string) $k )[0];
            if ( in_array( $base, self::NATIVE_LAYOUT_KEYS, true ) ) {
                unset( $settings[ $k ] );
            }
        }
    }

    /** Per-breakpoint setting: base unsuffixed, then "key:breakpoint" wherever the value changes. */
    public static function per_breakpoint( array &$settings, $key, $ctx, callable $resolve, callable $convert ) {
        $plan = $ctx->plan;
        $base = $resolve( $plan['base']['width'] );
        if ( $base ) {
            $settings[ $key ] = $convert( $base );
        }
        $prev = $base;
        foreach ( $plan['steps'] as $step ) {
            $v = $resolve( $step['width'] );
            if ( $v && $v !== $prev ) {
                $settings[ $key . ':' . $step['key'] ] = $convert( $v );
            }
            if ( $v ) {
                $prev = $v;
            }
        }
    }
}
