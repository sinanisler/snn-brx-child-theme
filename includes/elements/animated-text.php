<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use Bricks\Element;

class Animated_Text_Element extends Element {
    public $category = 'snn';
    public $name = 'animated-text';
    public $icon = 'ti-text';
    public $scripts = []; // No external scripts
    public $nestable = false;

    public function get_label() {
        return esc_html__('Animated Text', 'bricks');
    }

    public function set_control_groups() {
        // Define control groups if necessary
    }

    public function set_controls() {
        // Text control for Before Text
        $this->controls['before_text'] = [
            'tab'     => 'content',
            'label'   => esc_html__('Before Text', 'bricks'),
            'type'    => 'text',
            'default' => esc_html__('This is the before text.', 'bricks'),
        ];

        // Text control for Animated Text
        $this->controls['animated_text'] = [
            'tab'     => 'content',
            'label'   => esc_html__('Animated Text', 'bricks'),
            'type'    => 'text',
            'default' => esc_html__('Animated Text', 'bricks'),
        ];

        // Text control for After Text
        $this->controls['after_text'] = [
            'tab'     => 'content',
            'label'   => esc_html__('After Text', 'bricks'),
            'type'    => 'text',
            'default' => esc_html__('This is the after text.', 'bricks'),
        ];

        // Typography control for text styling
        $this->controls['text_typography'] = [
            'tab'   => 'style',
            'group' => 'Typography',
            'label' => esc_html__('Typography', 'bricks'),
            'type'  => 'typography',
            'css'   => [
                [
                    'property' => 'font',
                    'selector' => '.animated-text-wrapper',
                ],
            ],
        ];

        // Color control for SVG stroke color
        $this->controls['svg_color'] = [
            'tab'     => 'content',
            'label'   => esc_html__('SVG Stroke Color', 'bricks'),
            'type'    => 'color',
            'default' => '#000000',
            'css'     => [
                [
                    'property' => 'stroke',
                    'selector' => '.animated-svg path',
                ],
            ],
        ];

        // Checkbox control to enable loop animation or not
        $this->controls['loop_animation'] = [
            'tab'     => 'content',
            'label'   => esc_html__('Loop Animation', 'bricks'),
            'type'    => 'checkbox',
            'default' => false,
        ];

        
    }

    public function render() {
        $before_text    = isset($this->settings['before_text']) ? $this->settings['before_text'] : '';
        $animated_text  = isset($this->settings['animated_text']) ? $this->settings['animated_text'] : '';
        $after_text     = isset($this->settings['after_text']) ? $this->settings['after_text'] : '';
        $svg_color      = isset($this->settings['svg_color']) ? $this->settings['svg_color'] : '#000000';
        $loop_animation = isset($this->settings['loop_animation']) ? $this->settings['loop_animation'] : false;

        // Ensure variables are strings before escaping
        $before_text   = is_array($before_text) ? '' : esc_html($before_text);
        $animated_text = is_array($animated_text) ? '' : esc_html($animated_text);
        $after_text    = is_array($after_text) ? '' : esc_html($after_text);
        $svg_color     = is_array($svg_color) ? '#000000' : esc_html($svg_color);
        $repeat_count  = $loop_animation ? 'indefinite' : '1';
        ?>
        <div class="animated-text-wrapper">
            <span class="before-text"><?php echo $before_text; ?></span>
            <span class="animated-text">
                <?php echo $animated_text; ?>
                <div class="animated-svg">
                    <!-- SVG code with inline animation -->
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 500 150" preserveAspectRatio="none">
                        <path d="M9.3,127.3c49.3-3,150.7-7.6,199.7-7.4
                                 c121.9,0.4,189.9,0.4,282.3,7.2
                                 C380.1,129.6,181.2,130.6,70,139
                                 c82.6-2.9,254.2-1,335.9,1.3
                                 c-56,1.4-137.2-0.3-197.1,9"
                              stroke="<?php echo $svg_color; ?>" fill="none">
                            <animate id="svgAnimate" attributeName="stroke-dasharray" from="0,1000" to="1000,0" dur="2s" fill="freeze" repeatCount="<?php echo $repeat_count; ?>" />
                        </path>
                    </svg>
                </div>
            </span>
            <span class="after-text"><?php echo $after_text; ?></span>
        </div>
        <style>
            .animated-text-wrapper {
                display: flex;
                align-items: center;
                gap: 5px;
                position: relative;
            }
            .animated-text {
                position: relative;
                display: inline-block;
            }
            .animated-svg {
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                height: 20px;
                overflow: hidden;
            }
            .animated-svg svg {
                width: 100%;
                height: 100%;
            }
        </style>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Inline JS to ensure the SVG animation starts
                var svgAnimation = document.getElementById('svgAnimate');
                if (svgAnimation && typeof svgAnimation.beginElement === 'function') {
                    svgAnimation.beginElement();
                }
            });
        </script>
        <?php
    }

    public static function render_builder() {
        ?>
        <script type="text/x-template" id="tmpl-bricks-element-animated-text">
            <component :is="tag">
                <div class="animated-text-wrapper">
                    <span class="before-text">{{ element.settings.before_text }}</span>
                    <span class="animated-text">
                        {{ element.settings.animated_text }}
                        <div class="animated-svg">
                            <!-- SVG code with inline animation -->
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 500 150" preserveAspectRatio="none">
                                <path d="M9.3,127.3c49.3-3,150.7-7.6,199.7-7.4
                                         c121.9,0.4,189.9,0.4,282.3,7.2
                                         C380.1,129.6,181.2,130.6,70,139
                                         c82.6-2.9,254.2-1,335.9,1.3
                                         c-56,1.4-137.2-0.3-197.1,9"
                                      :stroke="element.settings.svg_color || '#000000'" fill="none">
                                    <animate attributeName="stroke-dasharray" from="0,1000" to="1000,0" dur="2s" fill="freeze" :repeatCount="element.settings.loop_animation ? 'indefinite' : '1'" />
                                </path>
                            </svg>
                        </div>
                    </span>
                    <span class="after-text">{{ element.settings.after_text }}</span>
                </div>
            </component>
        </script>
        <?php
    }
}
?>
