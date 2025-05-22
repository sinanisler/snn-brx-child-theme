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

        $this->controls['dom_element_tag'] = [
            'tab'         => 'content',
            'label'       => esc_html__('DOM Element Tag', 'bricks'),
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
            'default'     => 'div'
        ];

        $this->controls['presets'] = [
            'tab'         => 'content',
            'label'       => esc_html__('Presets', 'bricks'),
            'type'        => 'select',
            'options'     => [
                // [snip] - your full list, keep same!
                // ... (keep all your preset options here)
            ],
            'default'     => '',
            'multiple' => true,
            'searchable' => true,
            'clearable' => true,
            'inline'      => false,
            'placeholder' => esc_html__('Select Preset', 'bricks'),
        ];

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

    // --- ADDED SPLIT HELPER ---
	private function split_text_by_words_with_spaces($text) {
		$words = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		$output = '';
		foreach ($words as $word) {
			if (trim($word) === '') {
				// It's a space (or tabs, newlines, etc)
				// You can use &nbsp; if you want non-breaking, or just a span with space:
				$output .= '<span class="split-space">&nbsp;</span>';
			} else {
				$output .= '<span class="split-word">' . esc_html($word) . '</span>';
			}
		}
		return $output;
	}
    // --- OPTIONAL: for letter split, add this as well ---
    private function split_text_by_letters_with_spaces($text) {
        $chars = preg_split('//u', $text, null, PREG_SPLIT_NO_EMPTY);
        $output = '';
        foreach ($chars as $char) {
            if ($char === ' ') {
                $output .= ' ';
            } else {
                $output .= '<span class="split-letter">' . esc_html($char) . '</span>';
            }
        }
        return $output;
    }

    public function render() {
        $root_classes = ['snn-gsap-text-animations-wrapper'];
        $this->set_attribute('_root', 'class', $root_classes);

        $text_content = isset($this->settings['text_content']) ? $this->settings['text_content'] : '';
        $global_settings = [];

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
            if (is_array($this->settings['presets'])) {
                $global_settings[] = implode(', ', $this->settings['presets']);
            } else {
                $global_settings[] = $this->settings['presets'];
            }
        }

        $data_animate = !empty($global_settings) ? implode(', ', $global_settings) : '';
        $data_animate_attr = !empty($data_animate) ? ' data-animate="' . esc_attr($data_animate) . '"' : '';

        $dom_element_tag = isset($this->settings['dom_element_tag']) && !empty($this->settings['dom_element_tag']) ? $this->settings['dom_element_tag'] : 'div';

        // --- SPLIT TEXT MODE ---
        $splittext_enabled = (isset($this->settings['splittext']) && filter_var($this->settings['splittext'], FILTER_VALIDATE_BOOLEAN));
        $split_mode = 'words'; // or 'letters' if you want letter split

        echo '<' . esc_html($dom_element_tag) . ' ' . $this->render_attributes('_root') . $data_animate_attr . '>';

        if ($splittext_enabled && !empty($text_content)) {
            if ($split_mode === 'words') {
                echo $this->split_text_by_words_with_spaces($text_content);
            } else {
                echo $this->split_text_by_letters_with_spaces($text_content);
            }
        } else {
            echo $text_content;
        }

        echo Frontend::render_children($this);
        echo '</' . esc_html($dom_element_tag) . '>';
    }

    public static function render_builder() {
        ?>
        <script type="text/x-template" id="tmpl-bricks-element-gsap-text-animations">
            <component :is="element.settings.dom_element_tag || 'div'">
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
