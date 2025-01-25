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
                    'opacity' => '',
                ],
            ],
            'placeholder'   => esc_html__( 'Animation', 'bricks' ),
            'fields'        => [
                'opacity' => [
                    'label'       => esc_html__( 'Opacity', 'bricks' ),
                    'type'        => 'number',
                    'min'         => '0',
                    'max'         => '1',
                    'step'        => '0.1',
                    'default'     => '',
                    'placeholder' => esc_html__( 'e.g., 0.5', 'bricks' ),
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
    }

    public function render() {
        $root_classes = ['prefix-gsap-animations-wrapper'];
        $this->set_attribute( '_root', 'class', $root_classes );

        $animations = isset( $this->settings['animations'] ) ? $this->settings['animations'] : [];
        $animation_strings = [];

        foreach ( $animations as $anim ) {
            $props = [];

            // Gather opacity field
            if ( ($opacity = $anim['opacity'] ?? '') !== '' ) {
                $opacity = floatval( $opacity );
                $props[] = "opacity:{$opacity}";
            }

            if ( ! empty( $props ) ) {
                $animation_strings[] = implode( ', ', $props );
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

        if ( ! empty( $global_settings ) ) {
            array_unshift( $animation_strings, implode( ', ', $global_settings ) );
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
