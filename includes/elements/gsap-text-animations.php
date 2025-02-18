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
        // You can add control groups here if needed.
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
            'tab'         => 'content',
            'label'       => esc_html__('Text Content', 'bricks'),
            'type'        => 'text',
            'placeholder' => esc_html__('Enter your text content here', 'bricks'),
            'default'     => '',
        ];

        // Presets control with a list of predefined animations.
        // Note: Any keys already available as separate controls (loop, scroll, splittext, rand, pin, stagger, markers)
        // have been removed from these preset strings.
        $this->controls['presets'] = [
            'tab'         => 'content',
            'label'       => esc_html__('Presets', 'bricks'),
            'type'        => 'select',
            'options'     => [
                // Existing basic presets:
                'style_start-top:100px, style_end-top:0px, style_start-opacity:0, style_end-opacity:1, duration:1' =>
                    esc_html__('Starts 100px above and fades in.', 'bricks'),
                'style_start-top:50px, style_end-top:0px, style_start-opacity:0, style_end-opacity:1, duration:1'  =>
                    esc_html__('Starts 50px below and slides up with fade.', 'bricks'),
                'style_start-left:100px, style_end-left:0px, style_start-opacity:0, style_end-opacity:1, duration:1'  =>
                    esc_html__('Starts 100px right and slides in with fade.', 'bricks'),

                // Slide animations (duplicates removed):
                'x:100, y:100, duration:2, opacity:0.5, scale:0.8' =>
                    esc_html__('Slide in from bottom right', 'bricks'),
                'x:100, y:0, duration:1.5, opacity:0.7, scale:0.9'   =>
                    esc_html__('Slide in from right', 'bricks'),
                'x:0, y:-100, duration:1, opacity:0.8, scale:1'       =>
                    esc_html__('Slide down from top', 'bricks'),
                'x:0, y:100, duration:1, opacity:0.6, scale:0.8'       =>
                    esc_html__('Slide up from bottom', 'bricks'),
                'x:50, y:-50, duration:1.5, opacity:0.8, scale:1.1'    =>
                    esc_html__('Slide in from top left', 'bricks'),
                'x:-50, y:0, duration:1, opacity:0.8, scale:1.1'       =>
                    esc_html__('Slide in from left', 'bricks'),

                // Split text animations (cleaned of splittext and stagger settings):
                'style_start-opacity:0, style_end-opacity:1, style_start-transform:rotate(180deg), style_end-transform:rotate(0deg)' =>
                    esc_html__('Split text fade in (rotate 180°)', 'bricks'),
                'style_start-opacity:0, style_end-opacity:1, style_start-transform:rotate(45deg), style_end-transform:rotate(0deg)'  =>
                    esc_html__('Split text rotate in (rotate 45°)', 'bricks'),
                'style_start-opacity:0, style_end-opacity:1, style_start-transform:rotate(360deg), style_end-transform:rotate(0deg)' =>
                    esc_html__('Split text 360 rotation', 'bricks'),
                'style_start-opacity:0, style_end-opacity:1, style_start-transform:rotate(90deg), style_end-transform:rotate(0deg)'  =>
                    esc_html__('Split text rotate in (rotate 90°)', 'bricks'),

                // Random animations (cleaned of rand, splittext, and stagger settings):
                'x:-50, y:50, duration:1.5, scale:1.2' =>
                    esc_html__('Random position animation', 'bricks'),
                'style_start-opacity:0, style_end-opacity:1, style_start-transform:scale(0), style_end-transform:scale(1)' =>
                    esc_html__('Random scale animation (in)', 'bricks'),
                'style_start-opacity:0, style_end-opacity:1, style_start-transform:scale(1), style_end-transform:scale(0)' =>
                    esc_html__('Random scale animation (out)', 'bricks'),
                'x:0, y:100, duration:1.5, opacity:0.7, scale:0.9' =>
                    esc_html__('Random slide from bottom', 'bricks'),
                'x:-100, y:0, duration:1, opacity:0.5, scale:0.7' =>
                    esc_html__('Random slide from left', 'bricks'),

                // Rotation animations:
                'style_start-transform:rotate(90deg), style_end-transform:rotate(0deg), style_start-opacity:0, style_end-opacity:1' =>
                    esc_html__('Rotate in (90°)', 'bricks'),
                'style_start-transform:rotate(180deg), style_end-transform:rotate(0deg), style_start-opacity:0, style_end-opacity:1' =>
                    esc_html__('Rotate 180 degrees', 'bricks'),
                'style_start-transform:rotate(360deg), style_end-transform:rotate(0deg), style_start-opacity:0, style_end-opacity:1' =>
                    esc_html__('Rotate 360 degrees', 'bricks'),
            ],
            'default'     => '',
            'inline'      => false,
            'placeholder' => esc_html__('Select Preset', 'bricks'),
        ];

        // Additional controls already provided separately.
        $this->controls['loop'] = [
            'tab'         => 'content',
            'label'       => esc_html__('Loop', 'bricks'),
            'type'        => 'select',
            'options'     => [
                'true'  => esc_html__('True', 'bricks'),
                'false' => esc_html__('False', 'bricks'),
            ],
            'default'     => '',
            'inline'      => true,
            'placeholder' => esc_html__('Select', 'bricks'),
        ];

        $this->controls['scroll'] = [
            'tab'         => 'content',
            'label'       => esc_html__('Scroll', 'bricks'),
            'type'        => 'select',
            'options'     => [
                'true'  => esc_html__('True', 'bricks'),
                'false' => esc_html__('False', 'bricks'),
            ],
            'default'     => '',
            'inline'      => true,
            'placeholder' => esc_html__('Select', 'bricks'),
        ];

        $this->controls['splittext'] = [
            'tab'         => 'content',
            'label'       => esc_html__('Split Text', 'bricks'),
            'type'        => 'select',
            'options'     => [
                'true'  => esc_html__('True', 'bricks'),
                'false' => esc_html__('False', 'bricks'),
            ],
            'default'     => '',
            'inline'      => true,
            'placeholder' => esc_html__('Select', 'bricks'),
        ];

        $this->controls['rand'] = [
            'tab'         => 'content',
            'label'       => esc_html__('Random', 'bricks'),
            'type'        => 'select',
            'options'     => [
                'true'  => esc_html__('True', 'bricks'),
                'false' => esc_html__('False', 'bricks'),
            ],
            'default'     => '',
            'inline'      => true,
            'placeholder' => esc_html__('Select', 'bricks'),
        ];

        $this->controls['pin'] = [
            'tab'         => 'content',
            'label'       => esc_html__('Pin', 'bricks'),
            'type'        => 'select',
            'options'     => [
                'true'  => esc_html__('True', 'bricks'),
                'false' => esc_html__('False', 'bricks'),
            ],
            'default'     => '',
            'inline'      => true,
            'placeholder' => esc_html__('Select', 'bricks'),
        ];

        $this->controls['stagger'] = [
            'tab'         => 'content',
            'label'       => esc_html__('Stagger', 'bricks'),
            'type'        => 'number',
            'placeholder' => '0',
            'min'         => 0,
            'step'        => 0.1,
        ];

        $this->controls['scrub'] = [
            'tab'         => 'content',
            'label'       => esc_html__('Scrub', 'bricks'),
            'type'        => 'text',
            'placeholder' => esc_html__('true, 1, 2', 'bricks'),
            'default'     => '',
            'inline'      => true,
        ];

        $this->controls['markers'] = [
            'tab'         => 'content',
            'label'       => esc_html__('Markers', 'bricks'),
            'type'        => 'select',
            'options'     => [
                'true'  => esc_html__('True', 'bricks'),
                'false' => esc_html__('False', 'bricks'),
            ],
            'default'     => '',
            'inline'      => true,
            'placeholder' => esc_html__('Select', 'bricks'),
        ];

        $this->controls['scroll_start'] = [
            'tab'         => 'content',
            'label'       => esc_html__('Scroll Start (%)', 'bricks'),
            'type'        => 'number',
            'min'         => 0,
            'max'         => 100,
            'step'        => 1,
            'placeholder' => '50',
        ];

        $this->controls['scroll_end'] = [
            'tab'         => 'content',
            'label'       => esc_html__('Scroll End (%)', 'bricks'),
            'type'        => 'number',
            'min'         => 0,
            'max'         => 100,
            'step'        => 1,
            'placeholder' => '50',
        ];

        // Style Controls
        $this->controls['animation_text_typography'] = [
            'tab'   => 'style',
            'group' => 'style',
            'label' => esc_html__('Typography', 'bricks'),
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
        $this->set_attribute('_root', 'class', $root_classes);

        $text_content = isset($this->settings['text_content']) ? $this->settings['text_content'] : '';
        $global_settings = [];

        // For each boolean/select control we now use filter_var to correctly interpret "true" or "false"
        if (isset($this->settings['loop']) && $this->settings['loop'] !== '') {
            $global_settings[] = "loop:" . (filter_var($this->settings['loop'], FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false');
        }
        if (isset($this->settings['scroll']) && $this->settings['scroll'] !== '') {
            $global_settings[] = "scroll:" . (filter_var($this->settings['scroll'], FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false');
        }
        if (isset($this->settings['splittext']) && $this->settings['splittext'] !== '') {
            $global_settings[] = "splittext:" . (filter_var($this->settings['splittext'], FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false');
        }
        if (isset($this->settings['rand']) && $this->settings['rand'] !== '') {
            $global_settings[] = "rand:" . (filter_var($this->settings['rand'], FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false');
        }
        if (isset($this->settings['pin']) && $this->settings['pin'] !== '') {
            $global_settings[] = "pin:" . (filter_var($this->settings['pin'], FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false');
        }
        if (isset($this->settings['markers']) && $this->settings['markers'] !== '') {
            $global_settings[] = "markers:" . (filter_var($this->settings['markers'], FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false');
        }
        if (isset($this->settings['stagger']) && $this->settings['stagger'] !== '') {
            $global_settings[] = "stagger:" . $this->settings['stagger'];
        }
        if (isset($this->settings['scrub']) && $this->settings['scrub'] !== '') {
            $global_settings[] = "scrub:" . $this->settings['scrub'];
        }
        if (isset($this->settings['scroll_start']) && $this->settings['scroll_start'] !== '') {
            $global_settings[] = "start:'top " . $this->settings['scroll_start'] . "%'";
        }
        if (isset($this->settings['scroll_end']) && $this->settings['scroll_end'] !== '') {
            $global_settings[] = "end:'bottom " . $this->settings['scroll_end'] . "%'";
        }
        if (isset($this->settings['presets']) && $this->settings['presets'] !== '') {
            $global_settings[] = $this->settings['presets'];
        }

        // Combine all settings into the final data-animate attribute.
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
?>
