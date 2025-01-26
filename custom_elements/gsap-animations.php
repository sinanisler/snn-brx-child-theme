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
                    'x_start'              => '',
                    'y_start'              => '',
                    'x_end'                => '',
                    'y_end'                => '',
                    'style_start-scale'    => '',
                    'style_end-scale'      => '',
                    'style_start-rotate'   => '',
                    'style_end-rotate'     => '',
                    'style_start-opacity'  => '',
                    'style_end-opacity'    => '',
                    'style_start-filter'   => '',
                    'style_end-filter'     => '',
                    'style_start-grayscale' => '',
                    'style_end-grayscale'  => '',
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
                    'min'         => '0',
                    'max'         => '1',
                    'step'        => '0.1',
                ],
                'style_end-opacity' => [
                    'label'       => esc_html__( 'Opacity End', 'bricks' ),
                    'type'        => 'number',
                    'placeholder' => '1',
                    'min'         => '0',
                    'max'         => '1',
                    'step'        => '0.1',
                ],
                'style_start-filter' => [
                    'label'       => esc_html__( 'Blur Start (px)', 'bricks' ),
                    'type'        => 'number',
                    'placeholder' => '0',
                    'min'         => '0',
                    'step'        => '1',
                ],
                'style_end-filter' => [
                    'label'       => esc_html__( 'Blur End (px)', 'bricks' ),
                    'type'        => 'number',
                    'placeholder' => '0',
                    'min'         => '0',
                    'step'        => '1',
                ],
                'style_start-grayscale' => [
                    'label'       => esc_html__( 'Grayscale Start (%)', 'bricks' ),
                    'type'        => 'number',
                    'placeholder' => '0',
                    'min'         => '0',
                    'max'         => '100',
                    'step'        => '1',
                ],
                'style_end-grayscale' => [
                    'label'       => esc_html__( 'Grayscale End (%)', 'bricks' ),
                    'type'        => 'number',
                    'placeholder' => '0',
                    'min'         => '0',
                    'max'         => '100',
                    'step'        => '1',
                ],
            ],
        ];

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

        $this->controls['scroll_start'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Scroll Start (%)', 'bricks' ),
            'type'        => 'number',
            'min'         => 0,
            'max'         => 100,
            'step'        => 1,
            'placeholder' => '40',
        ];

        $this->controls['scroll_end'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Scroll End (%)', 'bricks' ),
            'type'        => 'number',
            'min'         => 0,
            'max'         => 100,
            'step'        => 1,
            'placeholder' => '60',
        ];
    }

    public function render() {
        $root_classes = ['snn-gsap-animations-wrapper'];
        $this->set_attribute( '_root', 'class', $root_classes );

        $gsap_animations = isset( $this->settings['gsap_animations'] ) ? $this->settings['gsap_animations'] : [];
        $animation_strings = [];

        foreach ( $gsap_animations as $anim ) {
            $props = [];
            $transform_start = [];
            $transform_end = [];

            // Build transform properties
            if ( isset( $anim['x_start'] ) && $anim['x_start'] !== '' ) {
                $transform_start[] = "translateX({$anim['x_start']}px)";
            }
            if ( isset( $anim['y_start'] ) && $anim['y_start'] !== '' ) {
                $transform_start[] = "translateY({$anim['y_start']}px)";
            }
            if ( isset( $anim['style_start-rotate'] ) && $anim['style_start-rotate'] !== '' ) {
                $transform_start[] = "rotate({$anim['style_start-rotate']}deg)";
            }
            if ( isset( $anim['style_start-scale'] ) && $anim['style_start-scale'] !== '' ) {
                $transform_start[] = "scale({$anim['style_start-scale']})";
            }

            if ( isset( $anim['x_end'] ) && $anim['x_end'] !== '' ) {
                $transform_end[] = "translateX({$anim['x_end']}px)";
            }
            if ( isset( $anim['y_end'] ) && $anim['y_end'] !== '' ) {
                $transform_end[] = "translateY({$anim['y_end']}px)";
            }
            if ( isset( $anim['style_end-rotate'] ) && $anim['style_end-rotate'] !== '' ) {
                $transform_end[] = "rotate({$anim['style_end-rotate']}deg)";
            }
            if ( isset( $anim['style_end-scale'] ) && $anim['style_end-scale'] !== '' ) {
                $transform_end[] = "scale({$anim['style_end-scale']})";
            }

            // Add combined transform properties
            if ( ! empty( $transform_start ) ) {
                $props[] = "style_start-transform:" . implode( ' ', $transform_start );
            }
            if ( ! empty( $transform_end ) ) {
                $props[] = "style_end-transform:" . implode( ' ', $transform_end );
            }

            // Handle opacity
            if ( isset( $anim['style_start-opacity'] ) && $anim['style_start-opacity'] !== '' ) {
                $props[] = "style_start-opacity:{$anim['style_start-opacity']}";
            }
            if ( isset( $anim['style_end-opacity'] ) && $anim['style_end-opacity'] !== '' ) {
                $props[] = "style_end-opacity:{$anim['style_end-opacity']}";
            }

            // Handle filters
            $filter_start = [];
            $filter_end = [];

            if ( isset( $anim['style_start-filter'] ) && $anim['style_start-filter'] !== '' ) {
                $filter_start[] = "blur({$anim['style_start-filter']}px)";
            }
            if ( isset( $anim['style_start-grayscale'] ) && $anim['style_start-grayscale'] !== '' ) {
                $filter_start[] = "grayscale({$anim['style_start-grayscale']}%)";
            }

            if ( isset( $anim['style_end-filter'] ) && $anim['style_end-filter'] !== '' ) {
                $filter_end[] = "blur({$anim['style_end-filter']}px)";
            }
            if ( isset( $anim['style_end-grayscale'] ) && $anim['style_end-grayscale'] !== '' ) {
                $filter_end[] = "grayscale({$anim['style_end-grayscale']}%)";
            }

            if ( ! empty( $filter_start ) ) {
                $props[] = "style_start-filter:" . implode( ' ', $filter_start );
            }
            if ( ! empty( $filter_end ) ) {
                $props[] = "style_end-filter:" . implode( ' ', $filter_end );
            }

            if ( ! empty( $props ) ) {
                $animation_strings[] = implode( ', ', $props ) . ';';
            }
        }

        $global_settings = [];

        if ( isset( $this->settings['markers'] ) ) {
            $global_settings[] = "markers:" . ( $this->settings['markers'] === 'true' ? 'true' : 'false' );
        }

        if ( isset( $this->settings['scroll_start'] ) && $this->settings['scroll_start'] !== '' ) {
            $global_settings[] = "start:'top " . $this->settings['scroll_start'] . "%'";
        }

        if ( isset( $this->settings['scroll_end'] ) && $this->settings['scroll_end'] !== '' ) {
            $global_settings[] = "end:'bottom " . $this->settings['scroll_end'] . "%'";
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