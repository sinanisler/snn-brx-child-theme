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
        // NO
    }

    public function set_controls() {

        $this->controls['animations'] = [
            'tab'           => 'content',
            'label'         => esc_html__( 'Animations', 'bricks' ),
            'type'          => 'repeater',
            'titleProperty' => '',
            'description'   => '<p data-control="info">To make this feature work, enable "Other Settings > GSAP".</p>',
            'default'       => [
                [
                    'position_start_horizontal' => '',
                    'position_start_vertical'   => '',
                ],
            ],
            'placeholder'   => esc_html__( 'Animation', 'bricks' ),
            'fields'        => [
                'position_start_horizontal' => [
                    'label'       => esc_html__( 'Position Start Horizontal', 'bricks' ),
                    'type'        => 'number',
                    'min'         => '-1000',
                    'max'         => '1000',
                    'step'        => '10',
                    'default'     => '',
                    'placeholder' => esc_html__( 'e.g., 100', 'bricks' ),
                ],
                'position_start_vertical' => [
                    'label'       => esc_html__( 'Position Start Vertical', 'bricks' ),
                    'type'        => 'number',
                    'min'         => '-1000',
                    'max'         => '1000',
                    'step'        => '10',
                    'default'     => '',
                    'placeholder' => esc_html__( 'e.g., 50', 'bricks' ),
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
    }

    public function render() {
        $root_classes = ['prefix-gsap-animations-wrapper'];
        $this->set_attribute( '_root', 'class', $root_classes );

        $animations = isset( $this->settings['animations'] ) ? $this->settings['animations'] : [];
        $animation_strings = [];

        foreach ( $animations as $anim ) {
            $props = [];

            // Add Position Start Horizontal to the data-animate attribute
            if ( ($posX = $anim['position_start_horizontal'] ?? '') !== '' ) {
                $posX = floatval( $posX );
                $props[] = "style_start-transform:translateX({$posX}px)";
            }

            // Add Position Start Vertical to the data-animate attribute
            if ( ($posY = $anim['position_start_vertical'] ?? '') !== '' ) {
                $posY = floatval( $posY );
                $props[] = "style_start-transform:translateY({$posY}px)";
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
