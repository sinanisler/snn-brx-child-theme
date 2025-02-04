<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Bricks\Frontend;

class Prefix_Element_Gsap_Text_Animations extends \Bricks\Element {
    public $category     = 'snn';
    public $name         = 'gsap-text-animations';
    public $icon         = 'ti-bolt-alt';
    public $scripts      = [];
    public $nestable     = false;

    public function get_label() {
        return esc_html__( 'GSAP Text Animations', 'bricks' );
    }

    public function set_control_groups() {

    }

    private function parse_unit_value( $value ) {
        if ( empty( $value ) ) {
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
        // Content Controls
        $this->controls['text_content'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Text Content', 'bricks' ),
            'type'        => 'text',
            'placeholder' => esc_html__( 'Enter your text content here', 'bricks' ),
            'default'     => '',
        ];

        $this->controls['style_start_custom'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Style Start (Custom CSS)', 'bricks' ),
            'type'        => 'textarea',
            'placeholder' => "background: red;\ncolor: white;",
            'description' => esc_html__( 'Enter custom CSS properties for the start state (without selectors or braces)', 'bricks' ),
        ];

        $this->controls['style_end_custom'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Style End (Custom CSS)', 'bricks' ),
            'type'        => 'textarea',
            'placeholder' => "background: blue;\ncolor: yellow;",
            'description' => esc_html__( 'Enter custom CSS properties for the end state (without selectors or braces)', 'bricks' ),
        ];

        $this->controls['loop'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Loop', 'bricks' ),
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

        $this->controls['splittext'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Split Text', 'bricks' ),
            'type'        => 'select',
            'options'     => [
                'true'  => esc_html__( 'Yes', 'bricks' ),
                'false' => esc_html__( 'No', 'bricks' ),
            ],
            'default'     => '',
            'inline'      => true,
            'placeholder' => esc_html__( 'Select', 'bricks' ),
        ];

        $this->controls['rand'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Random', 'bricks' ),
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

        $this->controls['stagger'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Stagger', 'bricks' ),
            'type'        => 'number',
            'placeholder' => '0',
            'min'         => 0,
            'step'        => 0.1,
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

        // Style Controls
        $this->controls['animation_text_typography'] = [
            'tab'   => 'style',
            'group' => 'style',
            'label' => esc_html__( 'Typography', 'bricks' ),
            'type'  => 'typography',
            'css'   => [
                [
                    'property' => 'font',
                    'selector' => '',
                ],
            ],
        ];
    }

    public function render() {
        $root_classes = ['snn-gsap-text-animations-wrapper'];
        $this->set_attribute( '_root', 'class', $root_classes );

        $text_content = isset( $this->settings['text_content'] ) ? $this->settings['text_content'] : '';
        $props = [];

        // Process style_start_custom
        if (!empty($this->settings['style_start_custom'])) {
            $custom_css = $this->settings['style_start_custom'];
            $declarations = array_map('trim', explode(';', $custom_css));
            
            foreach ($declarations as $declaration) {
                if (!empty($declaration)) {
                    $parts = explode(':', $declaration, 2);
                    if (count($parts) === 2) {
                        list($css_prop, $css_value) = array_map('trim', $parts);
                        $gsap_prop = str_replace('_', '-', $css_prop);
                        $props[] = "style_start-{$gsap_prop}:{$css_value}";
                    }
                }
            }
        }

        // Process style_end_custom
        if (!empty($this->settings['style_end_custom'])) {
            $custom_css = $this->settings['style_end_custom'];
            $declarations = array_map('trim', explode(';', $custom_css));
            
            foreach ($declarations as $declaration) {
                if (!empty($declaration)) {
                    $parts = explode(':', $declaration, 2);
                    if (count($parts) === 2) {
                        list($css_prop, $css_value) = array_map('trim', $parts);
                        $gsap_prop = str_replace('_', '-', $css_prop);
                        $props[] = "style_end-{$gsap_prop}:{$css_value}";
                    }
                }
            }
        }

        // Global settings
        $global_settings = [];
        if (isset($this->settings['loop']) && $this->settings['loop'] === 'true') {
            $global_settings[] = "loop:true";
        }
        if (isset($this->settings['scroll']) && $this->settings['scroll'] !== '') {
            $global_settings[] = "scroll:" . ($this->settings['scroll'] === 'true' ? 'true' : 'false');
        }
        if (isset($this->settings['duration']) && $this->settings['duration'] !== '') {
            $global_settings[] = "duration:" . $this->settings['duration'];
        }
        if (isset($this->settings['delay']) && $this->settings['delay'] !== '') {
            $global_settings[] = "delay:" . $this->settings['delay'];
        }
        if (isset($this->settings['splittext']) && $this->settings['splittext'] !== '') {
            $global_settings[] = "splittext:" . ($this->settings['splittext'] === 'true' ? 'true' : 'false');
        }
        if (isset($this->settings['rand']) && $this->settings['rand'] !== '') {
            $global_settings[] = "rand:" . ($this->settings['rand'] === 'true' ? 'true' : 'false');
        }
        if (isset($this->settings['pin']) && $this->settings['pin'] !== '') {
            $global_settings[] = "pin:" . ($this->settings['pin'] === 'true' ? 'true' : 'false');
        }
        if (isset($this->settings['stagger']) && $this->settings['stagger'] !== '') {
            $global_settings[] = "stagger:" . $this->settings['stagger'];
        }
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
        $animation_string = !empty($props) ? implode(', ', $props) : '';
        $data_animate = $global . $animation_string;
        $data_animate_attr = !empty($data_animate) ? ' data-animate="' . esc_attr($data_animate) . '"' : '';

        echo '<div ' . $this->render_attributes('_root') . $data_animate_attr . '>';
        echo $text_content;
        echo Frontend::render_children($this);
        echo '</div>';
    }

    public static function render_builder() {
        ?>
        <script type="text/x-template" id="tmpl-bricks-element-gsap-text-animations">
            <component :is="tag">
                <div v-if="element.settings.text_content" class="snn-gsap-text-animations-wrapper">
                    {{ element.settings.text_content }}
                </div>
                <bricks-element-children :element="element"/>
            </component>
        </script>
        <?php
    }
}