<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Bricks\Frontend;

class Prefix_Element_Gsap_Animations extends \Bricks\Element {
    public $category     = 'snn';
    public $name         = 'gsap-animations';
    public $icon         = 'ti-bolt-alt';
    public $css_selector = '.snn-gsap-animations-wrapper';
    public $scripts      = [];
    public $nestable     = true; 

    public function get_label() {
        return esc_html__( 'GSAP Animations (Nestable)', 'bricks' );
    }

    public function set_control_groups() {
        // Define control groups here if needed
    }

    public function set_controls() {

        /**
         * -----------------------------------------------------------------
         * ANIMATIONS REPEATER
         * -----------------------------------------------------------------
         */
        $this->controls['animations'] = [
            'tab'           => 'content',
            'label'         => esc_html__( 'Animations', 'bricks' ),
            'type'          => 'repeater',
            'titleProperty' => '',
            'description'   => '<p data-control="info">To make this feature work, enable "Other Settings > GSAP".</p>',
            'default'       => [
                [
                    'opacity'     => '',
                    'scale'       => '',
                    'rotate'      => '',
                    'duration'    => '',
                    'delay'       => '',
                    'scrub'       => '',
                    'style_start' => '',
                    'style_end'   => '',
                ],
            ],
            'placeholder'   => esc_html__( 'Animation', 'bricks' ),
            'fields'        => [

                // Optional: Keep these fields if you still want to allow users to set them individually
                'opacity' => [
                    'label'       => esc_html__( 'Opacity', 'bricks' ),
                    'type'        => 'number',
                    'min'         => '0',
                    'max'         => '1',
                    'step'        => '0.1',
                    'default'     => '',
                    'placeholder' => esc_html__( 'e.g., 0.5', 'bricks' ),
                ],
                'scale' => [
                    'label'       => esc_html__( 'Scale', 'bricks' ),
                    'type'        => 'number',
                    'min'         => '0',
                    'step'        => '0.1',
                    'default'     => '',
                    'placeholder' => esc_html__( 'e.g., 1.5', 'bricks' ),
                ],
                'rotate' => [
                    'label'       => esc_html__( 'Rotate (degrees)', 'bricks' ),
                    'type'        => 'number',
                    'default'     => '',
                    'placeholder' => esc_html__( 'e.g., 90', 'bricks' ),
                ],
                'duration' => [
                    'label'       => esc_html__( 'Duration (s)', 'bricks' ),
                    'type'        => 'number',
                    'min'         => '0',
                    'step'        => '0.1',
                    'default'     => '',
                    'placeholder' => esc_html__( 'e.g., 2', 'bricks' ),
                ],
                'delay' => [
                    'label'       => esc_html__( 'Delay (s)', 'bricks' ),
                    'type'        => 'number',
                    'min'         => '0',
                    'step'        => '0.1',
                    'default'     => '',
                    'placeholder' => esc_html__( 'e.g., 0.5', 'bricks' ),
                ],
                'scrub' => [
                    'label'       => esc_html__( 'Scrub', 'bricks' ),
                    'type'        => 'select',
                    'options'     => [
                        'false' => esc_html__( 'False', 'bricks' ),
                        'true'  => esc_html__( 'True', 'bricks' ),
                        '1'     => esc_html__( '1', 'bricks' ),
                        '2'     => esc_html__( '2', 'bricks' ),
                    ],
                    'default'     => '',
                    'inline'      => true,
                    'placeholder' => esc_html__( 'Select', 'bricks' ),
                ],

                // ---------------------------------------------
                // NEW FIELDS: style_start AND style_end (TEXTAREA)
                // ---------------------------------------------
                'style_start' => [
                    'label'       => esc_html__( 'Start Styles', 'bricks' ),
                    'type'        => 'textarea',
                    'description' => esc_html__( 'Enter one or more CSS properties, separated by semicolons, e.g. "transform: skewY(20deg); background-color: #00ff00".', 'bricks' ),
                    'default'     => '',
                ],
                'style_end' => [
                    'label'       => esc_html__( 'End Styles', 'bricks' ),
                    'type'        => 'textarea',
                    'description' => esc_html__( 'Enter one or more CSS properties, separated by semicolons, e.g. "transform: skewY(0deg); background-color: #ff0000".', 'bricks' ),
                    'default'     => '',
                ],
            ],
        ];

        /**
         * -----------------------------------------------------------------
         * GLOBAL CONTROLS
         * -----------------------------------------------------------------
         */

        // Markers Control
        $this->controls['markers'] = [
            'tab'           => 'content',
            'label'         => esc_html__( 'Markers', 'bricks' ),
            'type'          => 'select',
            'options'       => [
                'true'  => esc_html__( 'Yes', 'bricks' ),
                'false' => esc_html__( 'No', 'bricks' ),
            ],
            'default'       => '',
            'inline'        => true,
            'placeholder'   => esc_html__( 'Select', 'bricks' ),
        ];

        // Scroll Control (Renamed from 'scroll_trigger' to 'scroll')
        $this->controls['scroll'] = [
            'tab'           => 'content',
            'label'         => esc_html__( 'ScrollTrigger', 'bricks' ),
            'type'          => 'select',
            'options'       => [
                'true'  => esc_html__( 'Yes', 'bricks' ),
                'false' => esc_html__( 'No', 'bricks' ),
            ],
            'default'       => '',
            'inline'        => true,
            'placeholder'   => esc_html__( 'Select', 'bricks' ),
        ];

        // Removed "Scroller-Start" and "Scroller-End" Controls
        // If you have any other global controls, define them here.
    }

    /**
     * Helper function to parse user’s CSS text (e.g. "transform: skewY(20deg); background-color: #00ff00")
     * into data-animate-friendly chunks like "style_start-transform:skewY(20deg)".
     *
     * @param string $cssString  e.g. "transform: skewY(20deg); background-color: #ff0000"
     * @param string $prefix     'style_start' or 'style_end'
     *
     * @return string[]          array of strings, each string looks like: "style_start-transform:skewY(20deg)"
     */
    private function parse_css_properties( $cssString, $prefix = 'style_start' ) {
        $cssString = trim( $cssString );
        if ( empty( $cssString ) ) {
            return [];
        }

        // Split by semicolon
        $declarations = explode( ';', $cssString );
        $props = [];

        foreach ( $declarations as $declaration ) {
            $declaration = trim( $declaration );

            // If it's empty (maybe last semicolon or blank line), skip
            if ( empty( $declaration ) ) {
                continue;
            }

            // "transform: skewY(20deg)" -> ["transform", "skewY(20deg)"]
            $parts = explode( ':', $declaration, 2 );

            if ( count( $parts ) === 2 ) {
                $propName  = trim( $parts[0] );
                $propValue = trim( $parts[1] );

                // Build "style_start-transform:skewY(20deg)"
                $props[] = "{$prefix}-{$propName}:{$propValue}";
            }
        }

        return $props;
    }

    public function render() {
        $root_classes = ['prefix-gsap-animations-wrapper'];
        $this->set_attribute( '_root', 'class', $root_classes );

        $animations = isset( $this->settings['animations'] ) ? $this->settings['animations'] : [];
        $animation_strings = [];

        foreach ( $animations as $anim ) {
            $props = [];

            /**
             * 1) Gather simpler numeric fields (if still used) 
             *    e.g. scale, rotate, opacity, duration, delay, scrub...
             */
            if ( ($opacity = $anim['opacity'] ?? '') !== '' ) {
                $opacity = floatval( $opacity );
                $props[] = "opacity:{$opacity}";
            }

            if ( ($scale = $anim['scale'] ?? '') !== '' ) {
                $scale = floatval( $scale );
                $props[] = "scale:{$scale}";
            }

            if ( ($rotate = $anim['rotate'] ?? '') !== '' ) {
                $rotate = floatval( $rotate );
                $props[] = "rotate:{$rotate}";
            }

            if ( ($duration = $anim['duration'] ?? '') !== '' ) {
                $duration = floatval( $duration );
                $props[] = "duration:{$duration}";
            }

            if ( ($delay = $anim['delay'] ?? '') !== '' ) {
                $delay = floatval( $delay );
                $props[] = "delay:{$delay}";
            }

            if ( ($scrub = $anim['scrub'] ?? '') !== '' ) {
                if ( is_numeric( $scrub ) ) {
                    $scrub = floatval( $scrub );
                } else {
                    // "true" or "false"
                    $scrub = filter_var( $scrub, FILTER_VALIDATE_BOOLEAN ) ? 'true' : 'false';
                }
                $props[] = "scrub:{$scrub}";
            }

            /**
             * 2) Gather style_start / style_end
             */
            $styleStart = $anim['style_start'] ?? '';
            $styleEnd   = $anim['style_end']   ?? '';

            // Parse them into arrays of "style_start-prop:val"
            $styleStartProps = $this->parse_css_properties($styleStart, 'style_start');
            $styleEndProps   = $this->parse_css_properties($styleEnd, 'style_end');

            // Merge them in with everything else
            $props = array_merge($props, $styleStartProps, $styleEndProps);

            /**
             * 3) Convert this single animation’s props array
             *    to a string. The script expects them separated by comma+space.
             */
            if ( ! empty( $props ) ) {
                $animation_strings[] = implode( ', ', $props );
            }
        }

        // ---------------------------
        // Global “markers”, “scroll”
        // ---------------------------
        $global_settings = [];

        if ( isset( $this->settings['markers'] ) ) {
            $markers = $this->settings['markers'];
            if ( $markers === 'true' ) {
                $global_settings[] = "markers:true";
            } elseif ( $markers === 'false' ) {
                $global_settings[] = "markers:false";
            }
        }

        if ( isset( $this->settings['scroll'] ) ) {
            $scroll = $this->settings['scroll'] === 'true' ? 'true' : 'false';
            $global_settings[] = "scroll:{$scroll}";
        }

        /**
         * If we have any global settings, prepend them as the first “animation block”
         * so that "markers" or "scroll:false" get recognized by the script.
         */
        if ( ! empty( $global_settings ) ) {
            array_unshift( $animation_strings, implode( ', ', $global_settings ) );
        }

        // Final "data-animate" - separate each block with semicolons
        $data_animate = implode( '; ', $animation_strings );

        $data_animate_attr = '';
        if ( ! empty( $data_animate ) ) {
            $data_animate_sanitized = esc_attr( $data_animate );
            $data_animate_attr = " data-animate=\"{$data_animate_sanitized}\"";
        }

        $other_attributes = $this->render_attributes( '_root' );

        echo '<div ' . $data_animate_attr . ' ' . $other_attributes . '>';
            echo Frontend::render_children( $this );
        echo '</div>';
    }

    public static function render_builder() {
        ?>
        <script type="text/x-template" id="tmpl-bricks-element-gsap-animations">
            <component :is="tag">
                <bricks-element-children :element="element"/>
            </component>
        </script>
        <?php
    }
}
?>
