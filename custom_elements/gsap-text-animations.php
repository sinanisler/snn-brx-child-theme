<?php

if (!defined('ABSPATH')) {
    exit;
}

use Bricks\Frontend;

class Prefix_Element_Gsap_Text_Animations extends \Bricks\Element {
    public $category = 'snn';
    public $name = 'gsap-text-animations';
    public $icon = 'ti-bolt-alt';
    public $scripts = [];
    public $nestable = false;

    public function get_label() {
        return esc_html__('GSAP Text Animations', 'bricks');
    }

    public function set_control_groups() {
        // You can add control groups here if needed
    }

    private function parse_unit_value($value) {
        if (empty($value)) {
            return '';
        }

        $value = trim($value);

        if (preg_match('/^(auto|initial|inherit|unset)$/', $value)) {
            return $value;
        }

        if (preg_match('/[a-zA-Z%()]/', $value)) {
            return $value;
        }

        return is_numeric($value) ? $value . 'px' : $value;
    }

    public function set_controls() {
        // Content Controls
        $this->controls['text_content'] = [
            'tab' => 'content',
            'label' => esc_html__('Text Content', 'bricks'),
            'type' => 'text',
            'placeholder' => esc_html__('Enter your text content here', 'bricks'),
            'default' => '',
        ];

        $this->controls['loop'] = [
            'tab' => 'content',
            'label' => esc_html__('Loop', 'bricks'),
            'type' => 'select',
            'options' => [
                'true' => esc_html__('Yes', 'bricks'),
                'false' => esc_html__('No', 'bricks'),
            ],
            'default' => '',
            'inline' => true,
            'placeholder' => esc_html__('Select', 'bricks'),
        ];

        $this->controls['scroll'] = [
            'tab' => 'content',
            'label' => esc_html__('Scroll', 'bricks'),
            'type' => 'select',
            'options' => [
                'true' => esc_html__('Yes', 'bricks'),
                'false' => esc_html__('No', 'bricks'),
            ],
            'default' => '',
            'inline' => true,
            'placeholder' => esc_html__('Select', 'bricks'),
        ];

        $this->controls['splittext'] = [
            'tab' => 'content',
            'label' => esc_html__('Split Text', 'bricks'),
            'type' => 'select',
            'options' => [
                'true' => esc_html__('Yes', 'bricks'),
                'false' => esc_html__('No', 'bricks'),
            ],
            'default' => '',
            'inline' => true,
            'placeholder' => esc_html__('Select', 'bricks'),
        ];

        $this->controls['rand'] = [
            'tab' => 'content',
            'label' => esc_html__('Random', 'bricks'),
            'type' => 'select',
            'options' => [
                'true' => esc_html__('Yes', 'bricks'),
                'false' => esc_html__('No', 'bricks'),
            ],
            'default' => '',
            'inline' => true,
            'placeholder' => esc_html__('Select', 'bricks'),
        ];

        $this->controls['pin'] = [
            'tab' => 'content',
            'label' => esc_html__('Pin', 'bricks'),
            'type' => 'select',
            'options' => [
                'true' => esc_html__('Yes', 'bricks'),
                'false' => esc_html__('No', 'bricks'),
            ],
            'default' => '',
            'inline' => true,
            'placeholder' => esc_html__('Select', 'bricks'),
        ];

        $this->controls['stagger'] = [
            'tab' => 'content',
            'label' => esc_html__('Stagger', 'bricks'),
            'type' => 'number',
            'placeholder' => '0',
            'min' => 0,
            'step' => 0.1,
        ];

        $this->controls['markers'] = [
            'tab' => 'content',
            'label' => esc_html__('Markers', 'bricks'),
            'type' => 'select',
            'options' => [
                'true' => esc_html__('Yes', 'bricks'),
                'false' => esc_html__('No', 'bricks'),
            ],
            'default' => '',
            'inline' => true,
            'placeholder' => esc_html__('Select', 'bricks'),
        ];

        $this->controls['scroll_start'] = [
            'tab' => 'content',
            'label' => esc_html__('Scroll Start (%)', 'bricks'),
            'type' => 'number',
            'min' => 0,
            'max' => 100,
            'step' => 1,
            'placeholder' => '40',
        ];

        $this->controls['scroll_end'] = [
            'tab' => 'content',
            'label' => esc_html__('Scroll End (%)', 'bricks'),
            'type' => 'number',
            'min' => 0,
            'max' => 100,
            'step' => 1,
            'placeholder' => '60',
        ];

        // Style Controls
        $this->controls['animation_text_typography'] = [
            'tab' => 'style',
            'group' => 'style',
            'label' => esc_html__('Typography', 'bricks'),
            'type' => 'typography',
            'css' => [
                [
                    'property' => 'font',
                    'selector' => '',
                ],
            ],
        ];
    }

    public function render() {
        $root_classes = ['snn-gsap-text-animations-wrapper'];
        $this->set_attribute('_root', 'class', $root_classes);

        $text_content = isset($this->settings['text_content']) ? $this->settings['text_content'] : '';
        $props = [];

        // Global settings
        $global_settings = [];
        if (isset($this->settings['loop']) && $this->settings['loop'] === 'true') {
            $global_settings[] = "loop:true";
        }
        if (isset($this->settings['scroll']) && $this->settings['scroll'] !== '') {
            $global_settings[] = "scroll:" . ($this->settings['scroll'] === 'true' ? 'true' : 'false');
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

        // Combine global settings into a final data-animate attribute
        $data_animate = !empty($global_settings) ? implode(', ', $global_settings) : '';
        $data_animate_attr = !empty($data_animate) ? ' data-animate="' . esc_attr($data_animate) . '"' : '';

        // Render the output
        echo '<div ' . $this->render_attributes('_root') . $data_animate_attr . '>';
        echo $text_content;
        echo Frontend::render_children($this);
        echo '</div>';
    }

    /**
     * Render builder method - used within the Bricks builder interface.
     */
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
