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
        // No specific groups required
    }

    public function set_controls() {

        $this->controls['gsap_animations'] = [
            'tab'           => 'content',
            'label'         => esc_html__( 'GSAP Animations', 'bricks' ),
            'type'          => 'repeater',
            'titleProperty' => '',
            'default'       => [
                [
                    'x_start' => '',
                    'y_start' => '',
                    'x_end'   => '',
                    'y_end'   => '',
                    'duration'=> '',
                ],
            ],
            'placeholder'   => esc_html__( 'Animation', 'bricks' ),
            'fields'        => [
                'x_start' => [
                    'label'       => esc_html__( 'X Start', 'bricks' ),
                    'type'        => 'number',
                    'min'         => '-1000',
                    'max'         => '1000',
                    'step'        => '10',
                    'default'     => '',
                    'placeholder' => esc_html__( 'e.g., 100', 'bricks' ),
                ],
                'y_start' => [
                    'label'       => esc_html__( 'Y Start', 'bricks' ),
                    'type'        => 'number',
                    'min'         => '-1000',
                    'max'         => '1000',
                    'step'        => '10',
                    'default'     => '',
                    'placeholder' => esc_html__( 'e.g., 50', 'bricks' ),
                ],
                'x_end' => [
                    'label'       => esc_html__( 'X End', 'bricks' ),
                    'type'        => 'number',
                    'min'         => '-1000',
                    'max'         => '1000',
                    'step'        => '10',
                    'default'     => '',
                    'placeholder' => esc_html__( 'e.g., 200', 'bricks' ),
                ],
                'y_end' => [
                    'label'       => esc_html__( 'Y End', 'bricks' ),
                    'type'        => 'number',
                    'min'         => '-1000',
                    'max'         => '1000',
                    'step'        => '10',
                    'default'     => '',
                    'placeholder' => esc_html__( 'e.g., 100', 'bricks' ),
                ],
                'duration' => [
                    'label'       => esc_html__( 'Duration', 'bricks' ),
                    'type'        => 'number',
                    'min'         => '0',
                    'max'         => '60',
                    'step'        => '0.1',
                    'default'     => '',
                    'placeholder' => esc_html__( '0', 'bricks' ),
                ],
            ],
        ];

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

        // Scroll Control
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
            'description'   => '<br><p data-control="info">To make this feature work, enable "Other Settings > GSAP".</p>',
        ];
    }

    public function render() {
        $root_classes = ['prefix-gsap-animations-wrapper'];
        $this->set_attribute( '_root', 'class', $root_classes );

        $gsap_animations = isset( $this->settings['gsap_animations'] ) ? $this->settings['gsap_animations'] : [];
        $animation_strings = [];

        foreach ( $gsap_animations as $anim ) {
            $props = [];

            // Add X Start
            if ( ($xStart = $anim['x_start'] ?? '') !== '' ) {
                $xStart = floatval( $xStart );
                $props[] = "style_start-transform:translateX({$xStart}px)";
            }

            // Add Y Start
            if ( ($yStart = $anim['y_start'] ?? '') !== '' ) {
                $yStart = floatval( $yStart );
                $props[] = "style_start-transform:translateY({$yStart}px)";
            }

            // Add X End
            if ( ($xEnd = $anim['x_end'] ?? '') !== '' ) {
                $xEnd = floatval( $xEnd );
                $props[] = "style_end-transform:translateX({$xEnd}px)";
            }

            // Add Y End
            if ( ($yEnd = $anim['y_end'] ?? '') !== '' ) {
                $yEnd = floatval( $yEnd );
                $props[] = "style_end-transform:translateY({$yEnd}px)";
            }

            // Add Duration
            if ( ($duration = $anim['duration'] ?? '') !== '' ) {
                $duration = floatval( $duration );
                $props[] = "duration:{$duration}s";
            }

            // Convert this single animation's properties array to a string and append a semicolon
            if ( ! empty( $props ) ) {
                $animation_strings[] = implode( ', ', $props ) . ';';
            }
        }

        // Global settings
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

        // Add global settings to the beginning of the string, separated by commas
        if ( ! empty( $global_settings ) ) {
            array_unshift( $animation_strings, implode( ', ', $global_settings ) . ',' );
        }

        $data_animate = implode( ' ', $animation_strings );

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
