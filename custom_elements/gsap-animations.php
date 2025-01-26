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
        if ( empty( $value ) ) {
            return '';
        }
        
        $value = trim( $value );
        
        // Allow special values like 'auto' or 'initial'
        if ( preg_match( '/^(auto|initial|inherit|unset)$/', $value ) ) {
            return $value;
        }
        
        // Check if value contains any unit or is a CSS function
        if ( preg_match( '/[a-zA-Z%()]/', $value ) ) {
            return $value;
        }
        
        // Default to pixels if no unit specified and it's a numeric value
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
                    'style_start_custom'   => '',
                    'style_end_custom'     => '',
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
                'style_start_custom' => [
                    'label'       => esc_html__( 'Style Start (Custom CSS)', 'bricks' ),
                    'type'        => 'textarea',
                    'placeholder' => 'background: red; 
color: white;',
                    'description' => esc_html__( 'Enter custom CSS properties for start state (without selectors or braces)', 'bricks' ),
                ],
                'style_end_custom' => [
                    'label'       => esc_html__( 'Style End (Custom CSS)', 'bricks' ),
                    'type'        => 'textarea',
                    'placeholder' => 'background: blue; 
color: yellow;',
                    'description' => esc_html__( 'Enter custom CSS properties for end state (without selectors or braces)', 'bricks' ),
                ],
            ],
        ];

        // Rest of the controls remain the same
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

            // Handle X/Y transforms
            foreach (['x', 'y'] as $axis) {
                foreach (['start', 'end'] as $state) {
                    $key = "{$axis}_{$state}";
                    if (!empty($anim[$key])) {
                        $value = $this->parse_unit_value($anim[$key]);
                        $transform = "translate" . strtoupper($axis) . "($value)";
                        if ($state === 'start') {
                            $transform_start[] = $transform;
                        } else {
                            $transform_end[] = $transform;
                        }
                    }
                }
            }

            // Handle scale
            foreach (['start', 'end'] as $state) {
                $key = "style_{$state}-scale";
                if (!empty($anim[$key])) {
                    $value = $anim[$key];
                    if ($state === 'start') {
                        $transform_start[] = "scale($value)";
                    } else {
                        $transform_end[] = "scale($value)";
                    }
                }
            }

            // Handle rotate
            foreach (['start', 'end'] as $state) {
                $key = "style_{$state}-rotate";
                if (!empty($anim[$key])) {
                    $value = $anim[$key];
                    if ($state === 'start') {
                        $transform_start[] = "rotate({$value}deg)";
                    } else {
                        $transform_end[] = "rotate({$value}deg)";
                    }
                }
            }

            // Add combined transform properties
            if (!empty($transform_start)) {
                $props[] = "style_start-transform:" . implode(' ', $transform_start);
            }
            if (!empty($transform_end)) {
                $props[] = "style_end-transform:" . implode(' ', $transform_end);
            }

            // Handle opacity
            foreach (['start', 'end'] as $state) {
                $key = "style_{$state}-opacity";
                if (!empty($anim[$key])) {
                    $value = $anim[$key];
                    $props[] = "style_{$state}-opacity:{$value}";
                }
            }

            // Handle filters
            $filters = [
                'start' => [],
                'end' => []
            ];

            // Blur filter
            foreach (['start', 'end'] as $state) {
                $key = "style_{$state}-filter";
                if (!empty($anim[$key])) {
                    $value = $this->parse_unit_value($anim[$key]);
                    $filters[$state][] = "blur($value)";
                }
            }

            // Grayscale filter
            foreach (['start', 'end'] as $state) {
                $key = "style_{$state}-grayscale";
                if (!empty($anim[$key])) {
                    $value = $anim[$key];
                    $filters[$state][] = "grayscale({$value}%)";
                }
            }

            // Add filter properties
            foreach (['start', 'end'] as $state) {
                if (!empty($filters[$state])) {
                    $props[] = "style_{$state}-filter:" . implode(' ', $filters[$state]);
                }
            }

            // Process custom CSS inputs
            foreach (['start', 'end'] as $state) {
                $key = "style_{$state}_custom";
                if (!empty($anim[$key])) {
                    $custom_css = $anim[$key];
                    $declarations = array_map('trim', explode(';', $custom_css));
                    
                    foreach ($declarations as $declaration) {
                        if (!empty($declaration)) {
                            $parts = array_map('trim', explode(':', $declaration, 2));
                            if (count($parts) === 2) {
                                list($css_prop, $css_value) = $parts;
                                $gsap_prop = str_replace('_', '-', $css_prop);
                                $props[] = "style_{$state}-{$gsap_prop}:{$css_value}";
                            }
                        }
                    }
                }
            }

            if (!empty($props)) {
                $animation_strings[] = implode(', ', $props) . ';';
            }
        }

        $global_settings = [];

        if (isset($this->settings['markers'])) {
            $global_settings[] = "markers:" . ($this->settings['markers'] === 'true' ? 'true' : 'false');
        }

        if (isset($this->settings['scroll_start']) && $this->settings['scroll_start'] !== '') {
            $global_settings[] = "start:'top " . $this->settings['scroll_start'] . "%'";
        }

        if (isset($this->settings['scroll_end']) && $this->settings['scroll_end'] !== '') {
            $global_settings[] = "end:'bottom " . $this->settings['scroll_end'] . "%'";
        }

        $global = !empty($global_settings) ? implode(', ', $global_settings) . ',' : '';
        $data_animate = $global . implode(' ', $animation_strings);

        $data_animate_attr = !empty($data_animate) ? ' data-animate="' . esc_attr($data_animate) . '"' : '';

        $other_attributes = $this->render_attributes('_root');

        echo '<div ' . $data_animate_attr . ' ' . $other_attributes . '>';
        echo Frontend::render_children($this);
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