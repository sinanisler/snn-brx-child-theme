<?php
/**
 * Bricks interactions from HTML.
 *
 * Two ways to write one, both stored as Bricks' own `_interactions` entries
 * (the same repeater Bricks' builder edits, see abilities/interactions.php):
 *
 *   Shorthand, one interaction per element:
 *     <button data-interact="click" data-interact-action="toggleAttribute"
 *             data-interact-key="data-open" data-interact-target="#menu">
 *
 *   JSON, any number per element:
 *     <button data-interactions='[{"trigger":"click","action":"show","target":"#menu"},
 *                                 {"trigger":"mouseenter","action":"startAnimation","animation":"pulse"}]'>
 *
 * Triggers and actions are the ones this Bricks version offers. Target "self"
 * (default), "popup", or a CSS selector such as "#menu" (an element's CSS ID).
 * Anything else is reported as a note and dropped, never written half-formed.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SNN_Bricks_Interactions {

    /** Trigger names Bricks stores, with the friendly aliases HTML authors reach for. */
    const TRIGGERS = array(
        'click', 'mouseover', 'mouseenter', 'mouseleave', 'focus', 'blur',
        'enterView', 'leaveView', 'contentLoaded', 'scroll', 'animationEnd',
    );
    const TRIGGER_ALIASES = array(
        'hover' => 'mouseover', 'load' => 'contentLoaded', 'inview' => 'enterView', 'visible' => 'enterView',
        'mouseOver' => 'mouseover', 'mouseEnter' => 'mouseenter', 'mouseLeave' => 'mouseleave',
    );

    /** Actions that are safe to store from markup (no JavaScript, no checkout or form internals). */
    const ACTIONS = array(
        'show', 'hide', 'click', 'setAttribute', 'removeAttribute', 'toggleAttribute',
        'scrollTo', 'startAnimation', 'toggleOffCanvas', 'clearForm',
    );
    const ACTION_ALIASES = array(
        'toggle' => 'toggleAttribute', 'set' => 'setAttribute', 'remove' => 'removeAttribute',
        'animate' => 'startAnimation', 'scroll' => 'scrollTo', 'offcanvas' => 'toggleOffCanvas',
    );

    /** Actions that act on an attribute of the target and therefore need a key. */
    const KEY_ACTIONS = array( 'setAttribute', 'removeAttribute', 'toggleAttribute' );

    /**
     * Interactions written on one HTML element.
     *
     * @return array { interactions: array (Bricks entries), notes: string[] }
     */
    public static function parse( $el, $element_id ) {
        $out   = array();
        $notes = array();

        $json = trim( (string) SNN_Bricks_Dom::attr( $el, 'data-interactions' ) );
        if ( $json !== '' ) {
            $list = json_decode( $json, true );
            if ( ! is_array( $list ) || ( $list && array_keys( $list ) !== range( 0, count( $list ) - 1 ) ) ) {
                $notes[] = 'data-interactions must be a JSON array of interaction objects; it was ignored.';
            } else {
                foreach ( $list as $i => $spec ) {
                    if ( ! is_array( $spec ) ) {
                        $notes[] = 'data-interactions item ' . ( $i + 1 ) . ' is not an object; skipped.';
                        continue;
                    }
                    $out[] = self::spec_to_entry( $spec, $element_id, count( $out ), $notes );
                }
            }
        }

        $trigger = trim( (string) SNN_Bricks_Dom::attr( $el, 'data-interact' ) );
        if ( $trigger !== '' ) {
            $spec = array(
                'trigger'   => $trigger,
                'action'    => SNN_Bricks_Dom::attr( $el, 'data-interact-action' ),
                'target'    => SNN_Bricks_Dom::attr( $el, 'data-interact-target' ),
                'key'       => SNN_Bricks_Dom::attr( $el, 'data-interact-key' ),
                'value'     => SNN_Bricks_Dom::attr( $el, 'data-interact-value' ),
                'animation' => SNN_Bricks_Dom::attr( $el, 'data-interact-animation' ),
                'duration'  => SNN_Bricks_Dom::attr( $el, 'data-interact-duration' ),
                'delay'     => SNN_Bricks_Dom::attr( $el, 'data-interact-delay' ),
                'offset'    => SNN_Bricks_Dom::attr( $el, 'data-interact-offset' ),
                'rootMargin' => SNN_Bricks_Dom::attr( $el, 'data-interact-root-margin' ),
            );
            $spec  = array_filter( $spec, function ( $v ) {
                return $v !== null && $v !== '';
            } );
            $out[] = self::spec_to_entry( $spec, $element_id, count( $out ), $notes );
        }

        // Entries that failed validation come back as null.
        $out = array_values( array_filter( $out ) );
        return array( 'interactions' => $out, 'notes' => $notes );
    }

    /** One spec (any input spelling) → Bricks entry, or null with a note. */
    public static function spec_to_entry( array $spec, $element_id, $index, array &$notes ) {
        $trigger = self::pick( $spec, array( 'trigger' ) );
        $action  = self::pick( $spec, array( 'action' ) );
        if ( $trigger === '' || $action === '' ) {
            $notes[] = 'An interaction needs a trigger and an action (for example data-interact="click" data-interact-action="show").';
            return null;
        }
        $trigger = self::TRIGGER_ALIASES[ $trigger ] ?? $trigger;
        if ( ! in_array( $trigger, self::TRIGGERS, true ) ) {
            $notes[] = 'Unknown interaction trigger "' . $trigger . '". Use one of: ' . implode( ', ', self::TRIGGERS ) . '.';
            return null;
        }
        $action = self::ACTION_ALIASES[ $action ] ?? $action;
        if ( ! in_array( $action, self::ACTIONS, true ) ) {
            $notes[] = 'Unknown interaction action "' . $action . '" on ' . $trigger . '. Use one of: ' . implode( ', ', self::ACTIONS ) . '.';
            return null;
        }

        $entry = array(
            'id'      => substr( md5( 'interact-' . $element_id . '-' . $index ), 0, 6 ),
            'trigger' => $trigger,
            'action'  => $action,
            'target'  => 'self',
        );

        $target = self::pick( $spec, array( 'target', 'selector', 'targetSelector' ) );
        if ( $target === 'popup' ) {
            $entry['target'] = 'popup';
        } elseif ( $target !== '' && $target !== 'self' ) {
            $entry['target']         = 'custom';
            $entry['targetSelector'] = $target;
        }

        if ( in_array( $action, self::KEY_ACTIONS, true ) ) {
            $key = self::pick( $spec, array( 'key', 'attribute' ) );
            if ( $key === '' ) {
                $notes[] = $action . ' needs data-interact-key (the attribute name, for example data-open).';
                return null;
            }
            $entry['actionAttributeKey'] = $key;
            $value = self::pick( $spec, array( 'value' ) );
            if ( $value !== '' || $action === 'setAttribute' ) {
                $entry['actionAttributeValue'] = $value;
            }
        }

        if ( $action === 'startAnimation' ) {
            $animation = self::pick( $spec, array( 'animation', 'animationType' ) );
            if ( $animation === '' || ! preg_match( SNN_Bricks_Animate::TYPE_PATTERN, $animation ) ) {
                $notes[] = 'startAnimation needs data-interact-animation with an animate.css name, for example "pulse" or "fadeInUp".';
                return null;
            }
            $entry['animationType'] = $animation;
        }

        $duration = self::pick( $spec, array( 'duration', 'animationDuration' ) );
        if ( $duration !== '' ) {
            if ( preg_match( SNN_Bricks_Animate::TIME_PATTERN, $duration ) ) {
                $entry['animationDuration'] = $duration;
            } else {
                $notes[] = 'Interaction duration "' . $duration . '" is not a time such as 0.6s or 600ms; ignored.';
            }
        }
        $delay = self::pick( $spec, array( 'delay', 'animationDelay' ) );
        if ( $delay !== '' ) {
            if ( preg_match( SNN_Bricks_Animate::TIME_PATTERN, $delay ) ) {
                $entry['animationDelay'] = $delay;
            } else {
                $notes[] = 'Interaction delay "' . $delay . '" is not a time such as 0.2s; ignored.';
            }
        }

        $offset = self::pick( $spec, array( 'offset', 'scrollOffset' ) );
        if ( $offset !== '' && $trigger === 'scroll' ) {
            if ( preg_match( SNN_Bricks_Animate::OFFSET_PATTERN, $offset ) ) {
                $entry['scrollOffset'] = $offset;
            } else {
                $notes[] = 'Scroll offset "' . $offset . '" must be a number with px, % or vh.';
            }
        }

        $margin = self::pick( $spec, array( 'rootMargin' ) );
        if ( $margin !== '' && in_array( $trigger, array( 'enterView', 'leaveView' ), true ) ) {
            $entry['rootMargin'] = $margin;
        }

        return $entry;
    }

    /** First non-empty value among alternative spellings of one field. */
    private static function pick( array $spec, array $names ) {
        foreach ( $names as $n ) {
            if ( isset( $spec[ $n ] ) && is_scalar( $spec[ $n ] ) ) {
                $v = trim( (string) $spec[ $n ] );
                if ( $v !== '' ) {
                    return $v;
                }
            }
        }
        return '';
    }

    /** CSS ids a custom target selector names (#menu → menu; #brxe-abc123 → abc123). */
    public static function selector_ids( $selector ) {
        $ids = array();
        if ( preg_match_all( '/#([A-Za-z][\w-]*)/', (string) $selector, $m ) ) {
            foreach ( $m[1] as $id ) {
                $ids[] = preg_match( '/^brxe-(.+)$/', $id, $b ) ? $b[1] : $id;
            }
        }
        return $ids;
    }
}
