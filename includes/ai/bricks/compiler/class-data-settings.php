<?php
/**
 * Native settings written as attributes: data-bricks-<setting>.
 *
 *   <div data-bricks="icon-box" data-bricks-iconsize="32px" data-bricks-items='[{"title":"A"}]'>
 *
 * Any setting of any Bricks element can be set this way. The name is matched
 * to the element's real schema key whatever its case or dashes, so
 * data-bricks-iconsize and data-bricks-icon-size both reach iconSize. A name
 * the element does not have is reported, with a suggestion, and not written.
 * Values "true" and "false" become booleans; values starting with [ or { are
 * parsed as JSON (repeaters, objects).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SNN_Bricks_Data_Settings {

    const PREFIX = 'data-bricks-';

    public static function is_setting_attribute( $name ) {
        return strpos( (string) $name, self::PREFIX ) === 0;
    }

    /**
     * Settings the data-bricks-* attributes of one element write.
     *
     * @return array { settings: array, notes: string[] }
     */
    public static function parse( $el, $element_name ) {
        $settings = array();
        $notes    = array();
        foreach ( SNN_Bricks_Dom::attributes( $el ) as $attr => $value ) {
            if ( ! self::is_setting_attribute( $attr ) ) {
                continue;
            }
            $raw = substr( (string) $attr, strlen( self::PREFIX ) );
            if ( $raw === '' ) {
                continue;
            }
            $key = SNN_Bricks_Schema::canonical_key( $element_name, $raw );
            if ( $key === null ) {
                $notes[] = SNN_Bricks_Schema::unknown_key_message( $element_name, $raw ) . ' (from ' . $attr . ')';
                continue;
            }
            $parsed = self::value( (string) $value );
            // A repeater's items: a field the repeater does not have is reported and dropped.
            $fields = SNN_Bricks_Schema::repeater_fields( $element_name, $key );
            if ( $fields !== null && is_array( $parsed ) ) {
                $clean = array();
                foreach ( $parsed as $item ) {
                    if ( ! is_array( $item ) ) {
                        $clean[] = $item;
                        continue;
                    }
                    $kept = array();
                    foreach ( $item as $field => $field_value ) {
                        if ( in_array( (string) $field, $fields, true ) ) {
                            $kept[ $field ] = $field_value;
                            continue;
                        }
                        $near    = SNN_Bricks_Schema::suggest( (string) $field, $fields, 1 );
                        $notes[] = 'Field "' . $field . '" is not a field of the ' . $key . ' repeater of ' . $element_name . '. Its fields: '
                            . implode( ', ', array_slice( $fields, 0, 30 ) ) . '.' . ( $near ? ' Did you mean "' . $near[0] . '"?' : '' ) . ' (from ' . $attr . ')';
                    }
                    $clean[] = $kept;
                }
                $parsed = $clean;
            }
            $settings[ $key ] = $parsed;
        }
        return array( 'settings' => $settings, 'notes' => array_values( array_unique( $notes ) ) );
    }

    /** Attribute text → setting value. */
    public static function value( $text ) {
        $t = trim( $text );
        if ( $t === 'true' ) {
            return true;
        }
        if ( $t === 'false' ) {
            return false;
        }
        if ( $t !== '' && ( $t[0] === '[' || $t[0] === '{' ) ) {
            $decoded = json_decode( $t, true );
            if ( is_array( $decoded ) ) {
                return $decoded;
            }
        }
        return $text;
    }
}
