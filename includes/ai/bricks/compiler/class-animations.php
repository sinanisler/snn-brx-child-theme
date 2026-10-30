<?php
/**
 * Entrance animations as Bricks' own interactions.
 *
 * `data-animate="fadeInUp"` on an element becomes an interaction in its
 * `_interactions` setting: trigger enterView (scrolls into view) or
 * contentLoaded (page load), action startAnimation with Bricks' animate.css
 * type. Optional `data-animate-duration="0.9s"` and `data-animate-delay="0.2s"`
 * are passed through. Scripts are never kept, so this is the only animation
 * path: GSAP and other JavaScript do not convert.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SNN_Bricks_Animate {

    /** Bricks animation type names look like fadeInUp, bounce, zoomIn. */
    const TYPE_PATTERN = '/^[a-zA-Z]{2,30}$/';

    /** Time values such as 0.5s, 300ms or 1.2s. */
    const TIME_PATTERN = '/^\d+(\.\d+)?(s|ms)$/';

    /** Scroll offsets such as 120px, 50% or 20vh. */
    const OFFSET_PATTERN = '/^\d+(\.\d+)?(px|%|vh)$/';

    /** data-animate-trigger values → Bricks interaction triggers. */
    const TRIGGERS = array(
        'contentLoaded' => 'contentLoaded',
        'scroll'        => 'scroll',
        'hover'         => 'mouseover',
    );

    /**
     * The `_interactions` array for an element, or an empty array.
     *
     * @param DOMElement $el Source HTML element.
     * @param string     $id Bricks element id (seeds the interaction id).
     * @return array
     */
    public static function interactions_for( $el, $id ) {
        $type = trim( (string) SNN_Bricks_Dom::attr( $el, 'data-animate' ) );
        if ( ! preg_match( self::TYPE_PATTERN, $type ) ) {
            return array();
        }

        $trigger  = trim( (string) SNN_Bricks_Dom::attr( $el, 'data-animate-trigger' ) );
        $duration = trim( (string) SNN_Bricks_Dom::attr( $el, 'data-animate-duration' ) );
        $delay    = trim( (string) SNN_Bricks_Dom::attr( $el, 'data-animate-delay' ) );
        $offset   = trim( (string) SNN_Bricks_Dom::attr( $el, 'data-animate-offset' ) );

        // Bricks trigger names: enterView (default), contentLoaded (page load), scroll (page
        // position, with an offset), mouseover (hover).
        $bricks_trigger = self::TRIGGERS[ $trigger ] ?? 'enterView';

        $interaction = array(
            'id'            => substr( md5( 'animate-' . $id ), 0, 6 ),
            'trigger'       => $bricks_trigger,
            'action'        => 'startAnimation',
            'target'        => 'self',
            'animationType' => $type,
        );
        if ( $bricks_trigger === 'scroll' && preg_match( self::OFFSET_PATTERN, $offset ) ) {
            $interaction['scrollOffset'] = $offset;
        }
        if ( preg_match( self::TIME_PATTERN, $duration ) ) {
            $interaction['animationDuration'] = $duration;
        }
        if ( preg_match( self::TIME_PATTERN, $delay ) ) {
            $interaction['animationDelay'] = $delay;
        }

        return array( $interaction );
    }
}
