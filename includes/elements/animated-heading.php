<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use Bricks\Element;

class Animated_Headline_Element extends Element {
    public $category = 'snn';
    public $name = 'animated-headline';
    public $icon = 'ti-text';
    public $scripts = []; // No external scripts required
    public $nestable = false;

    public function get_label() {
        return esc_html__('Animated Headline', 'bricks');
    }

    public function set_control_groups() {
        // Define control groups if necessary.
    }

    public function set_controls() {

        // Control for text before the dynamic text.
        $this->controls['before_text'] = [
            'tab'     => 'content',
            'type'    => 'text',
            'default' => esc_html__('Before', 'bricks'),
        ];

        // Control for the dynamic animated text.
        $this->controls['dynamic_text'] = [
            'tab'     => 'content',
            'type'    => 'text',
            'default' => esc_html__('Animated Headline', 'bricks'),
        ];

        // Control for text after the dynamic text.
        $this->controls['after_text'] = [
            'tab'     => 'content',
            'type'    => 'text',
            'default' => esc_html__('After', 'bricks'),
        ];

        // Control for the SVG stroke color.
        $this->controls['svg_color'] = [
            'label'   => esc_html__('SVG Stroke Color', 'bricks'),
            'type'    => 'color',
            'default' => '#000000',
            'css'     => [
                [
                    'property' => 'stroke',
                    'selector' => '.animated-headline-svg path',
                ],
            ],
        ];

        // Slider control for the SVG stroke width.
        $this->controls['stroke_width'] = [
            'label'   => esc_html__('SVG Stroke Width', 'bricks'),
            'type'    => 'slider',
            'css'     => [
                [
                    'property' => 'stroke-width',
                    'selector' => '.animated-headline-svg path',
                ],
            ],
            'units'   => [
                'px' => [
                    'min'  => 1,
                    'max'  => 40,
                    'step' => 1,
                ],
                'em' => [
                    'min'  => 0.1,
                    'max'  => 5,
                    'step' => 0.1,
                ],
            ],
            'default' => '8px',
        ];

        // Slider control for the SVG bottom position (in em).
        $this->controls['svg_bottom'] = [
            'label'   => esc_html__('SVG Bottom', 'bricks'),
            'type'    => 'slider',
            'css'     => [
                [
                    'property' => 'bottom',
                    'selector' => '.animated-headline-svg',
                ],
            ],
            'units'   => [
                'em' => [
                    'min'  => -5,
                    'max'  => 5,
                    'step' => 0.1,
                ],
            ],
            'default' => '-0.2em',
        ];

        // Slider control for the SVG height (in em).
        $this->controls['svg_height'] = [
            'label'   => esc_html__('SVG Height', 'bricks'),
            'type'    => 'slider',
            'css'     => [
                [
                    'property' => 'height',
                    'selector' => '.animated-headline-svg',
                ],
            ],
            'units'   => [
                'em' => [
                    'min'  => 0.1,
                    'max'  => 10,
                    'step' => 0.1,
                ],
            ],
            'default' => '1.7em',
        ];

        // Checkbox to enable loop animation.
        $this->controls['loop_animation'] = [
            'tab'     => 'content',
            'label'   => esc_html__('Loop Animation', 'bricks'),
            'type'    => 'checkbox',
            'default' => false,
        ];

        // Control for animation duration.
        $this->controls['animation_duration'] = [
            'tab'         => 'content',
            'label'       => esc_html__('Duration', 'bricks'),
            'type'        => 'number',
            'placeholder' => '2s',
        ];

        // New control for Rounded paths.
        $this->controls['rounded'] = [
            'tab'     => 'content',
            'label'   => esc_html__('Rounded', 'bricks'),
            'type'    => 'checkbox',
            'default' => false,
        ];

        // New checkbox control for Text On Top.
        $this->controls['text_on_top'] = [
            'tab'     => 'content',
            'label'   => esc_html__('Text On Top', 'bricks'),
            'type'    => 'checkbox',
            'default' => false,
        ];

        // Select control for choosing the SVG animation path.
        $this->controls['svg_option'] = [
            'tab'     => 'content',
            'type'    => 'select',
            'options' => [
                'option4' => esc_html__('Underline', 'bricks'),
                'option1' => esc_html__('Underline Zigzag', 'bricks'),
                'option3' => esc_html__('Underline Wavevy', 'bricks'),
                'option2' => esc_html__('Circle', 'bricks'),
                'option5' => esc_html__('Double Line', 'bricks'),
                'option6' => esc_html__('Strikethrough', 'bricks'),
                'option7' => esc_html__('Strikethrough X', 'bricks'),
            ],
            'default' => 'option4',
        ];

        // Typography control for headline styling.
        $this->controls['headline_typography'] = [
            'group' => 'Typography',
            'label' => esc_html__('Headline Typography', 'bricks'),
            'type'  => 'typography',
            'css'   => [
                [
                    'property' => 'font',
                    'selector' => '.animated-headline-wrapper',
                ],
            ],
        ];

        // DOM Element Tag Control for selecting h1, h2, h3, etc.
        $this->controls['dom_element_tag'] = [
            'tab'         => 'content',
            'type'        => 'select',
            'options'     => [
                'h1'   => esc_html__('H1', 'bricks'),
                'h2'   => esc_html__('H2', 'bricks'),
                'h3'   => esc_html__('H3', 'bricks'),
                'h4'   => esc_html__('H4', 'bricks'),
                'p'    => esc_html__('Paragraph', 'bricks'),
                'span' => esc_html__('Span', 'bricks'),
                'div'  => esc_html__('Div', 'bricks'),
            ],
            'placeholder' => 'div',
        ];

        // New control for custom SVG upload.
        $this->controls['custom_svg'] = [
            'tab'         => 'content',
            'type'        => 'svg',
            'label'       => esc_html__('Custom SVG', 'bricks'),
            'description' => esc_html__('Upload your own SVG path. <br>Create 510px x 170px transparent rectangle and draw your path inside of that. Group it export it.', 'bricks'),
        ];
    }

    public function render() {
        $before_text        = isset($this->settings['before_text']) ? $this->settings['before_text'] : '';
        $dynamic_text       = isset($this->settings['dynamic_text']) ? $this->settings['dynamic_text'] : '';
        $after_text         = isset($this->settings['after_text']) ? $this->settings['after_text'] : '';
        $svg_color          = isset($this->settings['svg_color']) ? $this->settings['svg_color'] : '#000000';
        $stroke_width       = isset($this->settings['stroke_width']) ? $this->settings['stroke_width'] : '8px';
        $svg_bottom         = isset($this->settings['svg_bottom']) ? $this->settings['svg_bottom'] : '-0.2em';
        $svg_height         = isset($this->settings['svg_height']) ? $this->settings['svg_height'] : '1.7em';
        $loop_animation     = isset($this->settings['loop_animation']) ? $this->settings['loop_animation'] : false;
        $animation_duration = isset($this->settings['animation_duration']) ? $this->settings['animation_duration'] : '2s';
        $svg_option         = isset($this->settings['svg_option']) ? $this->settings['svg_option'] : 'option4';
        $rounded            = isset($this->settings['rounded']) ? $this->settings['rounded'] : false;
        $text_on_top        = isset($this->settings['text_on_top']) ? $this->settings['text_on_top'] : false;
        $dom_element_tag    = isset($this->settings['dom_element_tag']) ? $this->settings['dom_element_tag'] : 'div';

        // Ensure variables are safe strings.
        $before_text   = is_array($before_text) ? '' : esc_html($before_text);
        $dynamic_text  = is_array($dynamic_text) ? '' : esc_html($dynamic_text);
        $after_text    = is_array($after_text) ? '' : esc_html($after_text);
        $svg_color     = is_array($svg_color) ? '#000000' : esc_html($svg_color);
        $stroke_width  = is_array($stroke_width) ? '8px' : $stroke_width;
        $svg_bottom    = is_array($svg_bottom) ? '-0.2em' : $svg_bottom;
        $svg_height    = is_array($svg_height) ? '1.7em' : $svg_height;

        // Define mapping for SVG paths.
        $svg_paths = [
            'option1' => [
                'M9.3,127.3c49.3-3,150.7-7.6,199.7-7.4 c121.9,0.4,189.9,0.4,282.3,7.2 C380.1,129.6,181.2,130.6,70,139 c82.6-2.9,254.2-1,335.9,1.3 c-56,1.4-137.2-0.3-197.1,9'
            ],
            'option2' => [
                'M325,18C228.7-8.3,118.5,8.3,78,21C22.4,38.4,4.6,54.6,5.6,77.6c1.4,32.4,52.2,54,142.6,63.7 c66.2,7.1,212.2,7.5,273.5-8.3c64.4-16.6,104.3-57.6,33.8-98.2C386.7-4.9,179.4-1.4,126.3,20.7'
            ],
            'option3' => [
                'M3,146.1c17.1-8.8,33.5-17.8,51.4-17.8c15.6,0,17.1,18.1,30.2,18.1c22.9,0,36-18.6,53.9-18.6 c17.1,0,21.3,18.5,37.5,18.5c21.3,0,31.8-18.6,49-18.6c22.1,0,18.8,18.8,36.8,18.8c18.8,0,37.5-18.6,49-18.6c20.4,0,17.1,19,36.8,19 c22.9,0,36.8-20.6,54.7-18.6c17.7,1.4,7.1,19.5,33.5,18.8c17.1,0,47.2-6.5,61.1-15.6'
            ],
            'option4' => [
                'M7.7,145.6C109,125,299.9,116.2,401,121.3c42.1,2.2,87.6,11.8,87.3,25.7'
            ],
            'option5' => [
                'M8.4,143.1c14.2-8,97.6-8.8,200.6-9.2c122.3-0.4,287.5,7.2,287.5,7.2',
                'M8,19.4c72.3-5.3,162-7.8,216-7.8c54,0,136.2,0,267,7.8'
            ],
            'option6' => [
                'M3,75h493.5'
            ],
            'option7' => [
                'M497.4,23.9C301.6,40,155.9,80.6,4,144.4',
                'M14.1,27.6c204.5,20.3,393.8,74,467.3,111.7'
            ],
        ];

        // Get the paths for the selected option; fallback to option4 if not set.
        $paths = isset($svg_paths[$svg_option]) ? $svg_paths[$svg_option] : $svg_paths['option4'];

        // Generate a unique class for the root element to prevent conflicts.
        $unique_class = 'animated-headline-' . uniqid();
        $this->set_attribute('_root', 'class', ['snn-headline', 'e-animated', 'animated-headline-wrapper', $unique_class]);
        ?>
        <style>
            .<?php echo $unique_class; ?> {
                display: flex;
                align-items: center;
                gap: 5px;
                position: relative;
            }
            .<?php echo $unique_class; ?> .snn-headline-wrapper {
                position: relative;
            }
            .<?php echo $unique_class; ?> .animated-headline-svg {
                position: absolute;
                bottom: <?php echo esc_attr($svg_bottom); ?>;
                left: 0;
                width: 100%;
                height: <?php echo esc_attr($svg_height); ?>;
                stroke-width: <?php echo esc_attr($stroke_width); ?>;
            }
            <?php if ($text_on_top) : ?>
            .<?php echo $unique_class; ?> .snn-headline-text {
                position: relative;
                z-index: 1;
            }
            .<?php echo $unique_class; ?> .animated-headline-svg {
                z-index: 0;
            }
            <?php endif; ?>
        </style>
        <<?php echo $dom_element_tag; ?> <?php echo $this->render_attributes('_root'); ?>>
            <span class="snn-headline-plain-text"><?php echo $before_text; ?></span>
            <span class="snn-headline-wrapper">
                <span class="snn-headline-text"><?php echo $dynamic_text; ?></span>
                <svg class="animated-headline-svg" xmlns="http://www.w3.org/2000/svg" viewBox="-10 -14 520 178" preserveAspectRatio="none">
                    <?php if (isset($this->settings['custom_svg']['url']) && !empty($this->settings['custom_svg']['url'])): ?>
                        <?php echo file_get_contents( esc_url($this->settings['custom_svg']['url']) ); ?>
                    <?php else: ?>
                        <?php foreach ($paths as $d) : ?>
                            <path d="<?php echo esc_attr($d); ?>" stroke="<?php echo $svg_color; ?>" fill="none" <?php if ($rounded) { echo 'stroke-linecap="round" stroke-linejoin="round"'; } ?>></path>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </svg>
            </span>
            <span class="snn-headline-plain-text"><?php echo $after_text; ?></span>
        </<?php echo $dom_element_tag; ?>>
        <script>
            document.addEventListener('DOMContentLoaded', function(){
                function animatePath(path, duration, loop) {
                    var length = path.getTotalLength();
                    path.style.transition = 'none';
                    path.style.strokeDasharray = length;
                    path.style.strokeDashoffset = length;
                    // Force reflow
                    path.getBoundingClientRect();
                    path.style.transition = 'stroke-dashoffset ' + duration + ' ease-out';
                    path.style.strokeDashoffset = '0';
                    if (loop) {
                        var durationMs = 0;
                        if (duration.endsWith('ms')) {
                            durationMs = parseFloat(duration);
                        } else if (duration.endsWith('s')) {
                            durationMs = parseFloat(duration) * 1000;
                        } else {
                            durationMs = parseFloat(duration);
                        }
                        path.addEventListener('transitionend', function() {
                            setTimeout(function() {
                                animatePath(path, duration, loop);
                            }, 500);
                        }, {once: true});
                    }
                }
                
                var element = document.querySelector('.<?php echo $unique_class; ?>');
                if (!element) return;
                var observer = new IntersectionObserver(function(entries, observer){
                    entries.forEach(function(entry){
                        if(entry.isIntersecting){
                            var paths = entry.target.querySelectorAll('svg.animated-headline-svg path');
                            paths.forEach(function(path) {
                                animatePath(path, '<?php echo esc_attr($animation_duration); ?>', <?php echo $loop_animation ? 'true' : 'false'; ?>);
                            });
                            observer.unobserve(entry.target);
                        }
                    });
                }, {threshold: 0.1});
                observer.observe(element);
            });
        </script>
        <?php
    }

    public static function render_builder() {
        ?>
        <script type="text/x-template" id="tmpl-bricks-element-animated-headline">
            <component :is="element.settings.dom_element_tag || 'div'" class="snn-headline e-animated animated-headline-wrapper" style="display: flex; align-items: center; gap: 5px; position: relative;">
                <span class="snn-headline-plain-text">{{ element.settings.before_text }}</span>
                <span class="snn-headline-wrapper" style="position: relative;">
                    <span class="snn-headline-text" :style="element.settings.text_on_top ? { position: 'relative', zIndex: 1 } : {}">{{ element.settings.dynamic_text }}</span>
                    <svg class="animated-headline-svg" xmlns="http://www.w3.org/2000/svg" viewBox="-10 -14 520 178" preserveAspectRatio="none" 
                        :style="[{
                            position: 'absolute',
                            bottom: element.settings.svg_bottom || '-0.2em',
                            left: '0',
                            width: '100%',
                            height: element.settings.svg_height || '1.7em',
                            'stroke-width': element.settings.stroke_width || '8px'
                        }, element.settings.text_on_top ? { zIndex: 0 } : {}]">
                        <template v-if="element.settings.custom_svg && element.settings.custom_svg.url">
                            <g>
                                <text x="0" y="50" fill="#000">Custom SVG preview not available</text>
                            </g>
                        </template>
                        <template v-else>
                            <template v-for="(d, index) in ({
                                'option1': ['M9.3,127.3c49.3-3,150.7-7.6,199.7-7.4 c121.9,0.4,189.9,0.4,282.3,7.2 C380.1,129.6,181.2,130.6,70,139 c82.6-2.9,254.2-1,335.9,1.3 c-56,1.4-137.2-0.3-197.1,9'],
                                'option2': ['M325,18C228.7-8.3,118.5,8.3,78,21C22.4,38.4,4.6,54.6,5.6,77.6c1.4,32.4,52.2,54,142.6,63.7 c66.2,7.1,212.2,7.5,273.5-8.3c64.4-16.6,104.3-57.6,33.8-98.2C386.7-4.9,179.4-1.4,126.3,20.7'],
                                'option3': ['M3,146.1c17.1-8.8,33.5-17.8,51.4-17.8c15.6,0,17.1,18.1,30.2,18.1c22.9,0,36-18.6,53.9-18.6 c17.1,0,21.3,18.5,37.5,18.5c21.3,0,31.8-18.6,49-18.6c22.1,0,18.8,18.8,36.8,18.8c18.8,0,37.5-18.6,49-18.6c20.4,0,17.1,19,36.8,19 c22.9,0,36.8-20.6,54.7-18.6c17.7,1.4,7.1,19.5,33.5,18.8c17.1,0,47.2-6.5,61.1-15.6'],
                                'option4': ['M7.7,145.6C109,125,299.9,116.2,401,121.3c42.1,2.2,87.6,11.8,87.3,25.7'],
                                'option5': ['M8.4,143.1c14.2-8,97.6-8.8,200.6-9.2c122.3-0.4,287.5,7.2,287.5,7.2', 'M8,19.4c72.3-5.3,162-7.8,216-7.8c54,0,136.2,0,267,7.8'],
                                'option6': ['M3,75h493.5'],
                                'option7': ['M497.4,23.9C301.6,40,155.9,80.6,4,144.4', 'M14.1,27.6c204.5,20.3,393.8,74,467.3,111.7']
                            }[ element.settings.svg_option || 'option4' ] )" :key="index">
                                <path :d="d" :stroke="element.settings.svg_color || '#000000'" fill="none" 
                                      :stroke-linecap="element.settings.rounded ? 'round' : ''" 
                                      :stroke-linejoin="element.settings.rounded ? 'round' : ''"></path>
                            </template>
                        </template>
                    </svg>
                </span>
                <span class="snn-headline-plain-text">{{ element.settings.after_text }}</span>
            </component>
        </script>
        <?php
    }
}
?>
