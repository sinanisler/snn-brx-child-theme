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
                    'x_start'             => '',
                    'y_start'             => '',
                    'x_end'               => '',
                    'y_end'               => '',
                    'style_start-scale'   => '',
                    'style_end-scale'     => '',
                    'style_start-rotate'  => '',
                    'style_end-rotate'    => '',
                    'style_start-opacity' => '',
                    'style_end-opacity'   => '',
                ],
            ],
            'placeholder'   => esc_html__( 'Animation', 'bricks' ),
            'fields'        => [
                'x_start' => [
                    'label'       => esc_html__( 'X Start', 'bricks' ),
                    'type'        => 'number',
                    'placeholder' => '0',
                ],
                'x_end' => [
                    'label'       => esc_html__( 'X End', 'bricks' ),
                    'type'        => 'number',
                    'placeholder' => '0',
                ],
                'y_start' => [
                    'label'       => esc_html__( 'Y Start', 'bricks' ),
                    'type'        => 'number',
                    'placeholder' => '0',
                ],
                'y_end' => [
                    'label'       => esc_html__( 'Y End', 'bricks' ),
                    'type'        => 'number',
                    'placeholder' => '0',
                ],
                'style_start-scale' => [
                    'label'       => esc_html__( 'Scale Start', 'bricks' ),
                    'type'        => 'number',
                    'placeholder' => '1',
                ],
                'style_end-scale' => [
                    'label'       => esc_html__( 'Scale End', 'bricks' ),
                    'type'        => 'number',
                    'placeholder' => '1',
                ],
                'style_start-rotate' => [
                    'label'       => esc_html__( 'Rotate Start', 'bricks' ),
                    'type'        => 'number',
                    'placeholder' => '0',
                ],
                'style_end-rotate' => [
                    'label'       => esc_html__( 'Rotate End', 'bricks' ),
                    'type'        => 'number',
                    'placeholder' => '0',
                ],
                'style_start-opacity' => [
                    'label'       => esc_html__( 'Opacity Start', 'bricks' ),
                    'type'        => 'number',
                    'placeholder' => '1',
                ],
                'style_end-opacity' => [
                    'label'       => esc_html__( 'Opacity End', 'bricks' ),
                    'type'        => 'number',
                    'placeholder' => '1',
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

            if ( ( $xStart = $anim['x_start'] ?? '' ) !== '' ) {
                $props[] = "style_start-transform:translateX({$xStart}px)";
            }
            if ( ( $yStart = $anim['y_start'] ?? '' ) !== '' ) {
                $props[] = "style_start-transform:translateY({$yStart}px)";
            }
            if ( ( $xEnd = $anim['x_end'] ?? '' ) !== '' ) {
                $props[] = "style_end-transform:translateX({$xEnd}px)";
            }
            if ( ( $yEnd = $anim['y_end'] ?? '' ) !== '' ) {
                $props[] = "style_end-transform:translateY({$yEnd}px)";
            }
            if ( ( $scaleStart = $anim['style_start-scale'] ?? '' ) !== '' ) {
                $props[] = "style_start-scale:{$scaleStart}";
            }
            if ( ( $scaleEnd = $anim['style_end-scale'] ?? '' ) !== '' ) {
                $props[] = "style_end-scale:{$scaleEnd}";
            }
            if ( ( $rotateStart = $anim['style_start-rotate'] ?? '' ) !== '' ) {
                $props[] = "style_start-rotate:{$rotateStart}deg";
            }
            if ( ( $rotateEnd = $anim['style_end-rotate'] ?? '' ) !== '' ) {
                $props[] = "style_end-rotate:{$rotateEnd}deg";
            }
            if ( ( $opacityStart = $anim['style_start-opacity'] ?? '' ) !== '' ) {
                $props[] = "style_start-opacity:{$opacityStart}";
            }
            if ( ( $opacityEnd = $anim['style_end-opacity'] ?? '' ) !== '' ) {
                $props[] = "style_end-opacity:{$opacityEnd}";
            }

            if ( ! empty( $props ) ) {
                $animation_strings[] = implode( ', ', $props ) . ';';
            }
        }

        $global_settings = [];

        if ( isset( $this->settings['markers'] ) ) {
            $global_settings[] = "markers:" . ($this->settings['markers'] === 'true' ? 'true' : 'false');
        }

        if ( isset( $this->settings['scroll'] ) ) {
            $global_settings[] = "scroll:" . ($this->settings['scroll'] === 'true' ? 'true' : 'false');
        }

        $global = ! empty( $global_settings ) ? implode( ', ', $global_settings ) . ',' : '';
        $data_animate = $global . implode( ' ', $animation_strings );

        $data_animate_attr = ! empty( $data_animate ) ? ' data-animate="' . esc_attr( $data_animate ) . '"' : '';

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
