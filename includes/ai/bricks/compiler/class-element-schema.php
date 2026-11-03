<?php
/**
 * Element names and setting keys, as Bricks defines them at runtime.
 *
 * Every check the compiler and the edit ops make about an element type or a
 * setting key goes through here. When Bricks is loaded the answer comes from
 * Bricks' own registry (Elements::$elements) and settings schema
 * (Abilities\Element_Settings_Schema), so an element or control that Bricks or
 * a plugin adds is recognised without a change here. Without Bricks the short
 * fallback list is used and setting keys are not checked.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SNN_Bricks_Schema {

    /** Element types the compiler can produce when Bricks is not loaded. */
    const FALLBACK_ELEMENTS = array(
        'section', 'container', 'block', 'heading', 'text-basic', 'text', 'text-link', 'button',
        'image', 'icon', 'svg', 'divider', 'custom-html-css-script', 'accordion', 'accordion-nested',
        'tabs', 'tabs-nested', 'slider', 'slider-nested', 'video', 'list',
    );

    /** @var array element name => settings schema (per request). */
    private static $cache = array();

    public static function bricks_loaded() {
        return class_exists( '\Bricks\Elements' ) && is_array( \Bricks\Elements::$elements ) && \Bricks\Elements::$elements;
    }

    /** Is this a registered Bricks element type (or one the fallback knows)? */
    public static function has_element( $name ) {
        $name = (string) $name;
        if ( $name === '' ) {
            return false;
        }
        if ( self::bricks_loaded() ) {
            return isset( \Bricks\Elements::$elements[ $name ] );
        }
        return in_array( $name, self::FALLBACK_ELEMENTS, true );
    }

    /** Every element type that can be placed on a page. */
    public static function element_names() {
        return self::bricks_loaded() ? array_keys( \Bricks\Elements::$elements ) : self::FALLBACK_ELEMENTS;
    }

    /** Registry facts for one element: label, category. */
    public static function element_info( $name ) {
        $info = array( 'name' => (string) $name, 'label' => (string) $name, 'category' => '' );
        if ( self::bricks_loaded() && isset( \Bricks\Elements::$elements[ $name ] ) ) {
            $entry = \Bricks\Elements::$elements[ $name ];
            if ( is_array( $entry ) ) {
                $info['label']    = (string) ( $entry['label'] ?? $name );
                $info['category'] = (string) ( $entry['category'] ?? '' );
            }
        }
        return $info;
    }

    /** Can this element type hold child elements? Bricks decides; the fallback covers the layout types. */
    public static function is_nestable( $name ) {
        if ( self::bricks_loaded() && class_exists( '\Bricks\Abilities\Element_Normalizer' ) ) {
            return \Bricks\Abilities\Element_Normalizer::is_nestable( $name );
        }
        return in_array( $name, array( 'section', 'container', 'block', 'div', 'accordion-nested', 'tabs-nested', 'slider-nested' ), true );
    }

    /**
     * Settings schema of one element type: setting key => definition (type, label, options ...).
     * Null when Bricks is not loaded, so callers can skip key checks.
     */
    public static function settings( $name ) {
        if ( ! self::bricks_loaded() || ! self::has_element( $name ) || ! class_exists( '\Bricks\Abilities\Element_Settings_Schema' ) ) {
            return null;
        }
        if ( ! isset( self::$cache[ $name ] ) ) {
            $schema               = \Bricks\Abilities\Element_Settings_Schema::get( $name );
            self::$cache[ $name ] = is_array( $schema ) ? $schema : array();
        }
        return self::$cache[ $name ];
    }

    /**
     * The schema's own spelling of a key written in another case or with dashes or
     * underscores removed (data-bricks-iconsize -> iconSize). Null when no single key matches.
     */
    public static function canonical_key( $name, $key ) {
        $schema = self::settings( $name );
        if ( $schema === null ) {
            return (string) $key;
        }
        if ( isset( $schema[ $key ] ) ) {
            return (string) $key;
        }
        $want    = self::loose( $key );
        $matches = array();
        foreach ( array_keys( $schema ) as $candidate ) {
            if ( self::loose( $candidate ) === $want ) {
                $matches[] = $candidate;
            }
        }
        return count( $matches ) === 1 ? $matches[0] : null;
    }

    /**
     * The setting an element that holds only text takes its content in: "text" (text elements),
     * "content" (editor elements such as icon-box and alert), or null when it has neither.
     */
    public static function text_setting( $name ) {
        $schema = self::settings( $name );
        if ( $schema === null ) {
            return 'text';
        }
        if ( isset( $schema['text'] ) ) {
            return 'text';
        }
        if ( isset( $schema['content'] ) && in_array( $schema['content']['type'] ?? '', array( 'editor', 'textarea', 'text' ), true ) ) {
            return 'content';
        }
        return null;
    }

    /** Field keys of a repeater setting (items, bars, pricingTables ...), or null when the key is not a repeater. */
    public static function repeater_fields( $name, $key ) {
        $schema = self::settings( $name );
        if ( $schema === null || ! isset( $schema[ $key ] ) || ( $schema[ $key ]['type'] ?? '' ) !== 'repeater' ) {
            return null;
        }
        return array_map( 'strval', array_keys( (array) ( $schema[ $key ]['fields'] ?? array() ) ) );
    }

    /** Setting keys (breakpoint suffixes removed) that the element's schema does not define. */
    public static function unknown_keys( $name, array $settings ) {
        $schema = self::settings( $name );
        if ( $schema === null ) {
            return array();
        }
        $unknown = array();
        foreach ( array_keys( $settings ) as $key ) {
            $base = explode( ':', (string) $key )[0];
            if ( ! isset( $schema[ $base ] ) ) {
                $unknown[] = (string) $key;
            }
        }
        return $unknown;
    }

    /** Close spellings of a word, for "did you mean" messages. */
    public static function suggest( $word, array $candidates, $limit = 3 ) {
        $word   = strtolower( (string) $word );
        $max    = max( 2, (int) floor( strlen( $word ) / 3 ) );
        $scored = array();
        foreach ( $candidates as $c ) {
            $d = levenshtein( $word, strtolower( (string) $c ) );
            if ( $d <= $max ) {
                $scored[ (string) $c ] = $d;
            }
        }
        asort( $scored );
        return array_slice( array_keys( $scored ), 0, $limit );
    }

    /** Message for an element name the registry does not know. */
    public static function unknown_element_message( $name ) {
        $near = self::suggest( $name, self::element_names() );
        return 'Unknown element type "' . $name . '".' . ( $near ? ' Did you mean ' . implode( ', ', array_map( function ( $n ) {
            return '"' . $n . '"';
        }, $near ) ) . '?' : '' ) . ' Use snn/bricks-element-schema to list element types.';
    }

    /** Message for a setting key the element's schema does not define. */
    public static function unknown_key_message( $element, $key ) {
        $schema = self::settings( $element );
        $near   = $schema === null ? array() : self::suggest( explode( ':', $key )[0], array_keys( $schema ) );
        return 'Setting "' . $key . '" is not a ' . $element . ' setting in Bricks.' . ( $near ? ' Did you mean ' . implode( ', ', array_map( function ( $n ) {
            return '"' . $n . '"';
        }, $near ) ) . '?' : '' ) . ' Use snn/bricks-element-schema with names ["' . $element . '"] to list its settings.';
    }

    private static function loose( $key ) {
        return strtolower( str_replace( array( '-', '_' ), '', (string) $key ) );
    }
}
