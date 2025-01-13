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
        // You can define control groups here if needed
    }

    public function set_controls() {
        $this->controls['animations'] = [
            'tab'           => 'content',
            'label'         => esc_html__( 'Animations', 'bricks' ),
            'type'          => 'repeater',
            'titleProperty' => '',
            'description'   => '  <p data-control="info"> To make this feature work you need to enable Other Settings > GSAP setting. <p>',
            'default'       => [
                [
                    'x'               => '',
                    'y'               => '',
                    'opacity'         => '',
                    'scale'           => '',
                    'rotate'          => '',
                    'duration'        => '',
                    'delay'           => '',
                    'scroll'          => '',
                    'scrub'           => '',
                    'pin'             => '',
                    'pinSpacing'      => '',
                    'markers'         => '',
                ],
            ],
            'placeholder'   => esc_html__( 'Animation', 'bricks' ),
            'fields'        => [
                'x' => [
                    'label'       => esc_html__( 'Move Horizontal (px)', 'bricks' ),
                    'type'        => 'number',
                    'default'     => '',
                    'placeholder' => esc_html__( 'e.g., 100', 'bricks' ),
                ],
                'y' => [
                    'label'       => esc_html__( 'Move Vertical (px)', 'bricks' ),
                    'type'        => 'number',
                    'default'     => '',
                    'placeholder' => esc_html__( 'e.g., -50', 'bricks' ),
                ],
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
                    'default'     => '', // Changed from '1' to ''
                    'placeholder' => esc_html__( 'e.g., 2', 'bricks' ),
                ],
                'delay' => [
                    'label'       => esc_html__( 'Delay (s)', 'bricks' ),
                    'type'        => 'number',
                    'min'         => '0',
                    'step'        => '0.1',
                    'default'     => '', // Changed from '0' to ''
                    'placeholder' => esc_html__( 'e.g., 0.5', 'bricks' ),
                ],
                'scroll' => [
                    'label'       => esc_html__( 'Scroll Trigger', 'bricks' ),
                    'type'        => 'select',
                    'options'     => [
                        'true'  => esc_html__( 'Yes', 'bricks' ),
                        'false' => esc_html__( 'No', 'bricks' ),
                    ],
                    'default'     => '', // Changed from 'true' to ''
                    'inline'      => true,
                    'placeholder' => esc_html__( 'Select', 'bricks' ),
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
                    'default'     => '', // Changed from 'false' to ''
                    'inline'      => true,
                    'placeholder' => esc_html__( 'Select', 'bricks' ),
                ],
                'pin' => [
                    'label'       => esc_html__( 'Pin', 'bricks' ),
                    'type'        => 'select',
                    'options'     => [
                        'true'  => esc_html__( 'Yes', 'bricks' ),
                        'false' => esc_html__( 'No', 'bricks' ),
                    ],
                    'default'     => '', // Changed from 'false' to ''
                    'inline'      => true,
                    'placeholder' => esc_html__( 'Select', 'bricks' ),
                ],
                'pinSpacing' => [
                    'label'       => esc_html__( 'Pin Spacing', 'bricks' ),
                    'type'        => 'select',
                    'options'     => [
                        'margin' => esc_html__( 'Margin', 'bricks' ),
                        'padding' => esc_html__( 'Padding', 'bricks' ),
                        'false'   => esc_html__( 'False', 'bricks' ),
                    ],
                    'default'     => '', // Changed from 'margin' to ''
                    'inline'      => true,
                    'placeholder' => esc_html__( 'Select', 'bricks' ),
                ],
                'markers' => [
                    'label'       => esc_html__( 'Markers', 'bricks' ),
                    'type'        => 'select',
                    'options'     => [
                        'true'  => esc_html__( 'Yes', 'bricks' ),
                        'false' => esc_html__( 'No', 'bricks' ),
                    ],
                    'default'     => '', // Changed from 'false' to ''
                    'inline'      => true,
                    'placeholder' => esc_html__( 'Select', 'bricks' ),
                ],
            ],
        ];

    }

    public function render() {
        $root_classes = ['prefix-gsap-animations-wrapper'];
        $this->set_attribute( '_root', 'class', $root_classes );
        $animations = isset( $this->settings['animations'] ) ? $this->settings['animations'] : [];
        $animation_strings = [];

        foreach ( $animations as $anim ) {
            $props = [];

            if ( ($x = $anim['x'] ?? '') !== '' ) {
                $x = floatval( $x );
                $props[] = "x:{$x}";
            }

            if ( ($y = $anim['y'] ?? '') !== '' ) {
                $y = floatval( $y );
                $props[] = "y:{$y}";
            }

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

            if ( ($scroll = $anim['scroll'] ?? '') !== '' ) {
                $scroll = filter_var( $scroll, FILTER_VALIDATE_BOOLEAN ) ? 'true' : 'false';
                $props[] = "scroll:{$scroll}";
            }

            if ( ($scrub = $anim['scrub'] ?? '') !== '' ) {
                if ( is_numeric( $scrub ) ) {
                    $scrub = floatval( $scrub );
                } else {
                    $scrub = filter_var( $scrub, FILTER_VALIDATE_BOOLEAN ) ? 'true' : 'false';
                }
                $props[] = "scrub:{$scrub}";
            }

            if ( ($pin = $anim['pin'] ?? '') !== '' ) {
                $pin = filter_var( $pin, FILTER_VALIDATE_BOOLEAN ) ? 'true' : 'false';
                $props[] = "pin:{$pin}";
            }

            if ( ($markers = $anim['markers'] ?? '') !== '' ) {
                $markers = filter_var( $markers, FILTER_VALIDATE_BOOLEAN ) ? 'true' : 'false';
                $props[] = "markers:{$markers}";
            }

            if ( ($pinSpacing = $anim['pinSpacing'] ?? '') !== '' ) {
                $pinSpacing = sanitize_text_field( $pinSpacing );
                $props[] = "pinSpacing:{$pinSpacing}";
            }

            if ( ! empty( $props ) ) {
                $animation_strings[] = implode( ', ', $props );
            }
        }

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
