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

    private function parse_unit_value( $value ) {
        if ( $value === '' ) {
            return '';
        }

        $value = trim( $value );

        if ( preg_match( '/^(auto|initial|inherit|unset)$/', $value ) ) {
            return $value;
        }

        if ( preg_match( '/[a-zA-Z%()]/', $value ) ) {
            return $value;
        }

        return is_numeric( $value ) ? $value . 'px' : $value;
    }

    public function set_controls() {
        $this->controls['gsap_animations'] = [
            'tab'           => 'content',
            'label'         => esc_html__( 'GSAP Animations', 'bricks' ),
            'type'          => 'repeater',
            'titleProperty' => '',
            'default'       => [
                [
                    'x_start'               => '',
                    'y_start'               => '',
                    'x_end'                 => '',
                    'y_end'                 => '',
                    'style_start-scale'     => '',
                    'style_end-scale'       => '',
                    'style_start-rotate'    => '',
                    'style_end-rotate'      => '',
                    'style_start-opacity'   => '',
                    'style_end-opacity'     => '',
                    'style_start-filter'    => '',
                    'style_end-filter'      => '',
                    'style_start-grayscale' => '',
                    'style_end-grayscale'   => '',
                    'style_start_custom'    => '',
                    'style_end_custom'      => '',
                ],
            ],
            'placeholder'   => esc_html__( 'Animation', 'bricks' ),
            'fields'        => [
                'x_start' => [
                    'label'       => esc_html__( 'X Start', 'bricks' ),
                    'type'        => 'number',
                    'placeholder' => '0px',
                ],
                'x_end' => [
                    'label'       => esc_html__( 'X End', 'bricks' ),
                    'type'        => 'number',
                    'placeholder' => '0px',
                ],
                'y_start' => [
                    'label'       => esc_html__( 'Y Start', 'bricks' ),
                    'type'        => 'number',
                    'placeholder' => '0px',
                ],
                'y_end' => [
                    'label'       => esc_html__( 'Y End', 'bricks' ),
                    'type'        => 'number',
                    'placeholder' => '0px',
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
                    'label'       => esc_html__( 'Blur Start', 'bricks' ),
                    'type'        => 'number',
                    'placeholder' => '0px',
                ],
                'style_end-filter' => [
                    'label'       => esc_html__( 'Blur End', 'bricks' ),
                    'type'        => 'number',
                    'placeholder' => '0px',
                ],
                'style_start-grayscale' => [
                    'label'       => esc_html__( 'Grayscale Start (%)', 'bricks' ),
                    'type'        => 'number',
                    'placeholder' => '1-100',
                    'min'         => '0',
                    'max'         => '100',
                    'step'        => '1',
                ],
                'style_end-grayscale' => [
                    'label'       => esc_html__( 'Grayscale End (%)', 'bricks' ),
                    'type'        => 'number',
                    'placeholder' => '1-100',
                    'min'         => '100',
                    'max'         => '100',
                    'step'        => '1',
                ],
                'style_start_custom' => [
                    'label'       => esc_html__( 'Style Start (Custom CSS)', 'bricks' ),
                    'type'        => 'textarea',
                    'placeholder' => "background: red;\ncolor: white;",
                    'description' => esc_html__( 'Enter custom CSS properties for start state (without selectors or braces)', 'bricks' ),
                ],
                'style_end_custom' => [
                    'label'       => esc_html__( 'Style End (Custom CSS)', 'bricks' ),
                    'type'        => 'textarea',
                    'placeholder' => "background: blue;\ncolor: yellow;",
                    'description' => esc_html__( 'Enter custom CSS properties for end state (without selectors or braces)', 'bricks' ),
                ],
            ],
        ];

        $this->controls['loop'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Loop / yoyo', 'bricks' ),
            'type'        => 'select',
            'options'     => [
                'true'  => esc_html__( 'Yes', 'bricks' ),
                'false' => esc_html__( 'No', 'bricks' ),
            ],
            'default'     => '',
            'inline'      => true,
            'placeholder' => esc_html__( 'Select', 'bricks' ),
        ];

        $this->controls['scroll'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Scroll', 'bricks' ),
            'type'        => 'select',
            'options'     => [
                'true'  => esc_html__( 'Yes', 'bricks' ),
                'false' => esc_html__( 'No', 'bricks' ),
            ],
            'default'     => '',
            'inline'      => true,
            'placeholder' => esc_html__( 'Select', 'bricks' ),
        ];

        $this->controls['pin'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Pin', 'bricks' ),
            'type'        => 'select',
            'options'     => [
                'true'  => esc_html__( 'Yes', 'bricks' ),
                'false' => esc_html__( 'No', 'bricks' ),
            ],
            'default'     => '',
            'inline'      => true,
            'placeholder' => esc_html__( 'Select', 'bricks' ),
        ];

        $this->controls['duration'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Duration', 'bricks' ),
            'type'        => 'number',
            'placeholder' => '1',
            'min'         => 0,
            'step'        => 1,
        ];

        $this->controls['delay'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Delay', 'bricks' ),
            'type'        => 'number',
            'placeholder' => '0',
            'min'         => 0,
            'step'        => 1,
        ];

        $this->controls['scrub'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Scrub', 'bricks' ),
            'type'        => 'text',
            'placeholder' => 'true, 1, 2',
            'inline'      => true,
        ];

        $this->controls['stagger'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Stagger', 'bricks' ),
            'type'        => 'text',
            'placeholder' => '1, 2',
            'inline'      => true,
        ];

        $this->controls['markers'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Markers', 'bricks' ),
            'type'        => 'select',
            'options'     => [
                'true'  => esc_html__( 'Yes', 'bricks' ),
                'false' => esc_html__( 'No', 'bricks' ),
            ],
            'default'     => '',
            'inline'      => true,
            'placeholder' => esc_html__( 'Select', 'bricks' ),
        ];

        $this->controls['scroll_start'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Scroll Start', 'bricks' ),
            'type'        => 'text',
            'placeholder' => 'top 40% or +=2000px',
        ];

        $this->controls['scroll_end'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Scroll End', 'bricks' ),
            'type'        => 'text',
            'placeholder' => 'bottom 60% or +=2000px',
        ];
    }

    public function render() {
        $root_classes = ['snn-gsap-animations-wrapper'];
        $this->set_attribute( '_root', 'class', $root_classes );

        $gsap_animations   = isset( $this->settings['gsap_animations'] ) ? $this->settings['gsap_animations'] : [];
        $animation_strings = [];

        foreach ( $gsap_animations as $index => $anim ) {
            $props            = [];
            $transform_start  = [];
            $transform_end    = [];

            foreach ( ['x', 'y'] as $axis ) {
                foreach ( ['start', 'end'] as $state ) {
                    $key = "{$axis}_{$state}";
                    if ( isset( $anim[$key] ) && $anim[$key] !== '' ) {
                        $value      = $this->parse_unit_value( $anim[ $key ] );
                        $transform  = "translate" . strtoupper( $axis ) . "($value)";
                        if ( $state === 'start' ) {
                            $transform_start[] = $transform;
                        } else {
                            $transform_end[] = $transform;
                        }
                    }
                }
            }

            foreach ( ['start', 'end'] as $state ) {
                $key = "style_{$state}-scale";
                if ( isset( $anim[$key] ) && $anim[$key] !== '' ) {
                    $value    = $anim[ $key ];
                    $props[]  = "style_{$state}-scale($value)";
                }
            }

            foreach ( ['start', 'end'] as $state ) {
                $key = "style_{$state}-rotate";
                if ( isset( $anim[$key] ) && $anim[$key] !== '' ) {
                    $value    = $anim[ $key ];
                    $props[]  = "style_{$state}-rotate({$value}deg)";
                }
            }

            if ( ! empty( $transform_start ) ) {
                $props[] = "style_start-transform:" . implode( ' ', $transform_start );
            }
            if ( ! empty( $transform_end ) ) {
                $props[] = "style_end-transform:" . implode( ' ', $transform_end );
            }

            foreach ( ['start', 'end'] as $state ) {
                $key = "style_{$state}-opacity";
                if ( isset( $anim[$key] ) && $anim[$key] !== '' ) {
                    $value    = $anim[ $key ];
                    $props[]  = "style_{$state}-opacity($value)";
                }
            }

            $filters = [
                'start' => [],
                'end'   => []
            ];

            foreach ( ['start', 'end'] as $state ) {
                $key = "style_{$state}-filter";
                if ( isset( $anim[$key] ) && $anim[$key] !== '' ) {
                    $value                = $this->parse_unit_value( $anim[ $key ] );
                    $filters[ $state ][] = "blur($value)";
                }
            }

            foreach ( ['start', 'end'] as $state ) {
                $key = "style_{$state}-grayscale";
                if ( isset( $anim[$key] ) && $anim[$key] !== '' ) {
                    $value                = $anim[ $key ];
                    $filters[ $state ][] = "grayscale({$value}%)";
                }
            }

            foreach ( ['start', 'end'] as $state ) {
                if ( ! empty( $filters[ $state ] ) ) {
                    $props[] = "style_{$state}-filter:" . implode( ' ', $filters[ $state ] );
                }
            }

            foreach ( ['start', 'end'] as $state ) {
                $key = "style_{$state}_custom";
                if ( isset( $anim[$key] ) && $anim[$key] !== '' ) {
                    $custom_css   = $anim[ $key ];
                    $declarations = array_map( 'trim', explode( ';', $custom_css ) );
                    foreach ( $declarations as $declaration ) {
                        if ( $declaration !== '' ) {
                            $parts = array_map( 'trim', explode( ':', $declaration, 2 ) );
                            if ( count( $parts ) === 2 ) {
                                list( $css_prop, $css_value ) = $parts;
                                $gsap_prop = str_replace( '_', '-', $css_prop );
                                $props[]   = "style_{$state}-{$gsap_prop}:" . $css_value;
                            }
                        }
                    }
                }
            }

            if ( $index === 1 && isset( $this->settings['stagger'] ) && $this->settings['stagger'] !== '' ) {
                array_unshift( $props, "stagger:" . $this->settings['stagger'] );
            }

            if ( ! empty( $props ) ) {
                $animation_strings[] = implode( ', ', $props ) . ';';
            }
        }

        $global_settings = [];

        if ( isset( $this->settings['loop'] ) && $this->settings['loop'] === 'true' ) {
            array_unshift( $global_settings, "loop:true" );
        }

        if ( isset( $this->settings['scroll'] ) && $this->settings['scroll'] !== '' ) {
            $global_settings[] = "scroll:" . ( $this->settings['scroll'] === 'true' ? 'true' : 'false' );
        }

        if ( isset( $this->settings['pin'] ) && $this->settings['pin'] !== '' ) {
            $global_settings[] = "pin:" . ( $this->settings['pin'] === 'true' ? 'true' : 'false' );
        }

        if ( isset( $this->settings['markers'] ) ) {
            $global_settings[] = "markers:" . ( $this->settings['markers'] === 'true' ? 'true' : 'false' );
        }

        if ( ! empty( $this->settings['scroll_start'] ) ) {
            $global_settings[] = "start:'" . $this->settings['scroll_start'] . "'";
        }
        if ( ! empty( $this->settings['scroll_end'] ) ) {
            $global_settings[] = "end:'" . $this->settings['scroll_end'] . "'";
        }

        if ( isset( $this->settings['duration'] ) && $this->settings['duration'] !== '' ) {
            $global_settings[] = "duration:" . $this->settings['duration'];
        }

        if ( isset( $this->settings['delay'] ) && $this->settings['delay'] !== '' ) {
            $global_settings[] = "delay:" . $this->settings['delay'];
        }

        if ( isset( $this->settings['scrub'] ) && $this->settings['scrub'] !== '' ) {
            $global_settings[] = "scrub:" . $this->settings['scrub'];
        }

        if ( isset( $this->settings['stagger'] ) && $this->settings['stagger'] !== '' ) {
            $global_settings[] = "stagger:" . $this->settings['stagger'];
        }

        $global   = ! empty( $global_settings ) ? implode( ', ', $global_settings ) . ',' : '';
        $data_all = $global . implode( ' ', $animation_strings );

        $data_animate_attr = ! empty( $data_all ) 
            ? ' data-animate="' . esc_attr( $data_all ) . '"' 
            : '';

        echo '<div ' . $this->render_attributes('_root') . $data_animate_attr . '>';
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
