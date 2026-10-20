<?php
/**
 * Site-wide Bricks design resources: global classes, colour palette, variables.
 *
 * Writes are compare-and-swap: the option is re-read right before writing and
 * the write is refused if someone else changed it in between, so a concurrent
 * builder save of global classes is never clobbered. Existing entries are never
 * deleted or overwritten, except a class update the compiler planned explicitly
 * (a regenerated section that is the only user of that class).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SNN_Bricks_Global_Classes_Store {

    private static function opt( $const, $fallback ) {
        return defined( $const ) ? constant( $const ) : $fallback;
    }

    public static function classes_option() {
        return self::opt( 'BRICKS_DB_GLOBAL_CLASSES', 'bricks_global_classes' );
    }

    public static function read_classes() {
        wp_cache_delete( self::classes_option(), 'options' );
        wp_cache_delete( 'alloptions', 'options' );
        $classes = get_option( self::classes_option(), array() );
        return is_array( $classes ) ? array_values( $classes ) : array();
    }

    private static function fingerprint( $value ) {
        return md5( (string) maybe_serialize( $value ) );
    }

    /** update_option only when the stored value still equals $before. */
    private static function cas_write( $option, $before, $after ) {
        wp_cache_delete( $option, 'options' );
        wp_cache_delete( 'alloptions', 'options' );
        $current = get_option( $option, array() );
        if ( self::fingerprint( is_array( $current ) ? $current : array() ) !== self::fingerprint( $before ) ) {
            return new WP_Error( 'snn_design_conflict', 'Bricks ' . $option . ' changed during this write. Retry the call.', array( 'status' => 409 ) );
        }
        update_option( $option, $after );
        return true;
    }

    /**
     * Add new classes and apply planned CSS updates.
     *
     * @param array $create [{id, name, settings: {_cssCustom}}]
     * @param array $update [{id, css}]
     * @return array|WP_Error { created: string[], updated: string[] }
     */
    public static function apply( array $create, array $update = array() ) {
        if ( ! $create && ! $update ) {
            return array( 'created' => array(), 'updated' => array() );
        }
        $before   = self::read_classes();
        $after    = $before;
        $ids      = array();
        $names    = array();
        foreach ( $after as $gc ) {
            $ids[ $gc['id'] ?? '' ]     = true;
            $names[ $gc['name'] ?? '' ] = true;
        }
        $now     = time();
        $user    = (string) get_current_user_id();
        $created = array();
        $updated = array();

        foreach ( $create as $gc ) {
            if ( empty( $gc['id'] ) || empty( $gc['name'] ) || isset( $ids[ $gc['id'] ] ) ) {
                continue;
            }
            $name = $gc['name'];
            $css  = (string) ( $gc['settings']['_cssCustom'] ?? '' );
            if ( isset( $names[ $name ] ) ) {
                // The compiler plans unique names up front; this is only a last-resort guard.
                $suffix = 1;
                while ( isset( $names[ $name . '-' . $suffix ] ) ) {
                    $suffix++;
                }
                $css  = SNN_Bricks_Css::rename_classes_in_css( $css, array( $name => $name . '-' . $suffix ) );
                $name = $name . '-' . $suffix;
            }
            $after[] = array(
                'id'       => $gc['id'],
                'name'     => $name,
                'user_id'  => $user,
                'modified' => $now,
                'settings' => array( '_cssCustom' => $css ),
            );
            $ids[ $gc['id'] ] = true;
            $names[ $name ]   = true;
            $created[]        = $name;
        }

        foreach ( $update as $u ) {
            foreach ( $after as &$gc ) {
                if ( ( $gc['id'] ?? '' ) === $u['id'] ) {
                    if ( ! isset( $gc['settings'] ) || ! is_array( $gc['settings'] ) ) {
                        $gc['settings'] = array();
                    }
                    $gc['settings']['_cssCustom'] = (string) $u['css'];
                    $gc['modified']               = $now;
                    $updated[]                    = $gc['name'];
                }
            }
            unset( $gc );
        }

        if ( ! $created && ! $updated ) {
            return array( 'created' => array(), 'updated' => array() );
        }
        $write = self::cas_write( self::classes_option(), $before, $after );
        if ( is_wp_error( $write ) ) {
            return $write;
        }
        update_option( self::opt( 'BRICKS_DB_GLOBAL_CLASSES_TIMESTAMP', 'bricks_global_classes_timestamp' ), $now );
        update_option( self::opt( 'BRICKS_DB_GLOBAL_CLASSES_USER', 'bricks_global_classes_user' ), get_current_user_id() );
        self::regenerate_global_class_css();

        return array( 'created' => $created, 'updated' => $updated );
    }

    /** Update one class's CSS by name (edit op update_class). */
    public static function set_class_css( $name, $css ) {
        $before = self::read_classes();
        $after  = $before;
        $found  = false;
        foreach ( $after as &$gc ) {
            if ( ( $gc['name'] ?? '' ) === $name ) {
                $gc['settings']['_cssCustom'] = (string) $css;
                $gc['modified']               = time();
                $found                        = true;
            }
        }
        unset( $gc );
        if ( ! $found ) {
            return new WP_Error( 'snn_class_not_found', 'No global class ".' . $name . '" exists.' );
        }
        $write = self::cas_write( self::classes_option(), $before, $after );
        if ( ! is_wp_error( $write ) ) {
            self::regenerate_global_class_css();
        }
        return $write;
    }

    /** Make sure an empty class exists for each name; returns name → id. */
    public static function ensure_classes( array $names, SNN_Bricks_Id_Pool $ids ) {
        $map    = array();
        $create = array();
        foreach ( self::read_classes() as $gc ) {
            $map[ $gc['name'] ] = $gc['id'];
            $ids->reserve( $gc['id'] );
        }
        foreach ( $names as $raw ) {
            $name = ltrim( (string) $raw, '.' );
            if ( $name === '' || isset( $map[ $name ] ) ) {
                continue;
            }
            $id         = $ids->next();
            $map[ $name ] = $id;
            $create[]   = array( 'id' => $id, 'name' => $name, 'settings' => array( '_cssCustom' => '.' . $name . " {\n}" ) );
        }
        if ( $create ) {
            $res = self::apply( $create );
            if ( is_wp_error( $res ) ) {
                return $res;
            }
        }
        return $map;
    }

    /** Add :root colours to the first palette (by name, never overwriting). */
    public static function add_palette_colors( array $color_vars, SNN_Bricks_Id_Pool $ids ) {
        if ( ! $color_vars ) {
            return 0;
        }
        $option  = self::opt( 'BRICKS_DB_COLOR_PALETTE', 'bricks_color_palette' );
        $before  = get_option( $option, array() );
        $before  = is_array( $before ) ? $before : array();
        $after   = $before;
        if ( ! $after ) {
            $after[] = array( 'id' => $ids->next(), 'name' => 'Default', 'colors' => array() );
        }
        if ( ! isset( $after[0]['colors'] ) || ! is_array( $after[0]['colors'] ) ) {
            $after[0]['colors'] = array();
        }
        $names = array();
        foreach ( $after[0]['colors'] as $c ) {
            $names[ $c['name'] ?? '' ] = true;
        }
        $added = 0;
        foreach ( $color_vars as $v ) {
            if ( isset( $names[ $v['name'] ] ) ) {
                continue;
            }
            $after[0]['colors'][] = array(
                'id'    => $ids->next(),
                'name'  => $v['name'],
                'raw'   => 'var(--' . $v['name'] . ')',
                'light' => preg_match( '/^var\(--/', $v['value'] ) ? '' : $v['value'],
            );
            $names[ $v['name'] ] = true;
            $added++;
        }
        if ( $added ) {
            $write = self::cas_write( $option, $before, $after );
            if ( is_wp_error( $write ) ) {
                return $write;
            }
            self::regenerate_file( '\Bricks\Assets_Color_Palettes', $after );
        }
        return $added;
    }

    /** Add :root sizes as Bricks global variables (by name, never overwriting). */
    public static function add_variables( array $size_vars, SNN_Bricks_Id_Pool $ids ) {
        if ( ! $size_vars ) {
            return 0;
        }
        $option = self::opt( 'BRICKS_DB_GLOBAL_VARIABLES', 'bricks_global_variables' );
        $before = get_option( $option, array() );
        $before = is_array( $before ) ? $before : array();
        $after  = $before;
        $names  = array();
        foreach ( $after as $v ) {
            $names[ $v['name'] ?? '' ] = true;
        }
        $added = 0;
        foreach ( $size_vars as $v ) {
            if ( isset( $names[ $v['name'] ] ) ) {
                continue;
            }
            $after[] = array( 'id' => $ids->next(), 'name' => $v['name'], 'value' => $v['value'] );
            $names[ $v['name'] ] = true;
            $added++;
        }
        if ( $added ) {
            $write = self::cas_write( $option, $before, $after );
            if ( is_wp_error( $write ) ) {
                return $write;
            }
            self::regenerate_file( '\Bricks\Assets_Global_Variables', $after );
        }
        return $added;
    }

    /**
     * Global class CSS has no file of its own: Bricks prints it into each post's
     * CSS, which the post save regenerates. Kept as the one hook point.
     */
    public static function regenerate_global_class_css() {
        do_action( 'snn_bricks_global_classes_changed' );
    }

    /** When Bricks loads CSS from files, rebuild the palette / variables file. */
    private static function regenerate_file( $class, $value ) {
        if ( class_exists( $class ) && method_exists( $class, 'generate_css_file' ) ) {
            try {
                call_user_func( array( $class, 'generate_css_file' ), $value );
            } catch ( \Throwable $e ) {
                // Inline CSS loading needs no file; a failed rebuild must not fail the write.
            }
        }
    }
}
