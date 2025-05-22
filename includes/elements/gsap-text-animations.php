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

                'x:2000px' => esc_html__('Slide 2000px right', 'bricks'),
                'x:-2000px' => esc_html__('Slide 2000px left', 'bricks'),
                'y:-2000px' => esc_html__('Slide 2000px top', 'bricks'),
                'y:2000px' => esc_html__('Slide 2000px bottom', 'bricks'),

                'x:2000px,style_start-opacity:0,style_end-opacity:1' => esc_html__('Slide 2000px right and fade in', 'bricks'),
                'x:-2000px,style_start-opacity:0,style_end-opacity:1' => esc_html__('Slide 2000px left and fade in', 'bricks'),
                'y:-2000px,style_start-opacity:0,style_end-opacity:1' => esc_html__('Slide 2000px top and fade in', 'bricks'),
                'y:2000px,style_start-opacity:0,style_end-opacity:1' => esc_html__('Slide 2000px bottom and fade in', 'bricks'),

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




// Fading
'style_start-opacity:0, style_end-opacity:1' => esc_html__('Fade In ', 'snn'),
'style_start-opacity:1, style_end-opacity:0' => esc_html__('Fade Ou ', 'snn'),

'style_start-opacity:0, style_end-opacity:1, style_start-transform:translateY(-1000px), style_end-transform:translateY(0px)' => esc_html__('Fade In Down', 'snn'),
'style_start-opacity:1, style_end-opacity:0, style_start-transform:translateY(0px), style_end-transform:translateY(1000px)' => esc_html__('Fade Out Down', 'snn'),
'style_start-opacity:0, style_end-opacity:1, style_start-transform:translateX(-1000px), style_end-transform:translateX(0px)' => esc_html__('Fade In Left', 'snn'),
'style_start-opacity:1, style_end-opacity:0, style_start-transform:translateX(0px), style_end-transform:translateX(-1000px)' => esc_html__('Fade Out Left', 'snn'),
'style_start-opacity:0, style_end-opacity:1, style_start-transform:translateX(1000px), style_end-transform:translateX(0px)' => esc_html__('Fade In Right', 'snn'),
'style_start-opacity:1, style_end-opacity:0, style_start-transform:translateX(0px), style_end-transform:translateX(1000px)' => esc_html__('Fade Out Right', 'snn'),
'style_start-opacity:0, style_end-opacity:1, style_start-transform:translateY(1000px), style_end-transform:translateY(0px)' => esc_html__('Fade In Up', 'snn'),
'style_start-opacity:1, style_end-opacity:0, style_start-transform:translateY(0px), style_end-transform:translateY(-1000px)' => esc_html__('Fade Out Up', 'snn'),
'style_start-opacity:0, style_end-opacity:1, style_start-transform:translateX(-1000px) translateY(-1000px), style_end-transform:translateX(0px) translateY(0px)' => esc_html__('Fade In Top Left', 'snn'),
'style_start-opacity:1, style_end-opacity:0, style_start-transform:translateX(0px) translateY(0px), style_end-transform:translateX(-1000px) translateY(-1000px)' => esc_html__('Fade Out Top Left', 'snn'),
'style_start-opacity:0, style_end-opacity:1, style_start-transform:translateX(1000px) translateY(-1000px), style_end-transform:translateX(0px) translateY(0px)' => esc_html__('Fade In Top Right', 'snn'),
'style_start-opacity:1, style_end-opacity:0, style_start-transform:translateX(0px) translateY(0px), style_end-transform:translateX(1000px) translateY(-1000px)' => esc_html__('Fade Out Top Right', 'snn'),
'style_start-opacity:0, style_end-opacity:1, style_start-transform:translateX(-1000px) translateY(1000px), style_end-transform:translateX(0px) translateY(0px)' => esc_html__('Fade In Bottom Left', 'snn'),
'style_start-opacity:1, style_end-opacity:0, style_start-transform:translateX(0px) translateY(0px), style_end-transform:translateX(-1000px) translateY(1000px)' => esc_html__('Fade Out Bottom Left', 'snn'),
'style_start-opacity:0, style_end-opacity:1, style_start-transform:translateX(1000px) translateY(1000px), style_end-transform:translateX(0px) translateY(0px)' => esc_html__('Fade In Bottom Right', 'snn'),
'style_start-opacity:1, style_end-opacity:0, style_start-transform:translateX(0px) translateY(0px), style_end-transform:translateX(1000px) translateY(1000px)' => esc_html__('Fade Out Bottom Right', 'snn'),

// Zooming
'style_start-opacity:0, style_end-opacity:1, style_start-transform:scale(0.8), style_end-transform:scale(1)' => esc_html__('Zoom In', 'snn'),
'style_start-opacity:1, style_end-opacity:0, style_start-transform:scale(1), style_end-transform:scale(0.8)' => esc_html__('Zoom Out', 'snn'),
'style_start-opacity:0, style_end-opacity:1, style_start-transform:scale(0.8) translateY(-1000px), style_end-transform:scale(1) translateY(0px)' => esc_html__('Zoom In Down', 'snn'),
'style_start-opacity:1, style_end-opacity:0, style_start-transform:scale(1) translateY(0px), style_end-transform:scale(0.8) translateY(1000px)' => esc_html__('Zoom Out Down', 'snn'),
'style_start-opacity:0, style_end-opacity:1, style_start-transform:scale(0.8) translateX(-1000px), style_end-transform:scale(1) translateX(0px)' => esc_html__('Zoom In Left', 'snn'),
'style_start-opacity:1, style_end-opacity:0, style_start-transform:scale(1) translateX(0px), style_end-transform:scale(0.8) translateX(-1000px)' => esc_html__('Zoom Out Left', 'snn'),
'style_start-opacity:0, style_end-opacity:1, style_start-transform:scale(0.8) translateX(1000px), style_end-transform:scale(1) translateX(0px)' => esc_html__('Zoom In Right', 'snn'),
'style_start-opacity:1, style_end-opacity:0, style_start-transform:scale(1) translateX(0px), style_end-transform:scale(0.8) translateX(1000px)' => esc_html__('Zoom Out Right', 'snn'),
'style_start-opacity:0, style_end-opacity:1, style_start-transform:scale(0.8) translateY(1000px), style_end-transform:scale(1) translateY(0px)' => esc_html__('Zoom In Up', 'snn'),
'style_start-opacity:1, style_end-opacity:0, style_start-transform:scale(1) translateY(0px), style_end-transform:scale(0.8) translateY(-1000px)' => esc_html__('Zoom Out Up', 'snn'),


'style_start-transform:translateY(-1000px), style_end-transform:translateY(0px)' => esc_html__('Slide In Down ', 'snn'),
'style_start-transform:translateY(0px), style_end-transform:translateY(1000px)' => esc_html__('Slide Out Down ', 'snn'),
'style_start-transform:translateX(-1000px), style_end-transform:translateX(0px)' => esc_html__('Slide In Left ', 'snn'),
'style_start-transform:translateX(0px), style_end-transform:translateX(-1000px)' => esc_html__('Slide Out Left ', 'snn'),
'style_start-transform:translateX(1000px), style_end-transform:translateX(0px)' => esc_html__('Slide In Right ', 'snn'),
'style_start-transform:translateX(0px), style_end-transform:translateX(1000px)' => esc_html__('Slide Out Right ', 'snn'),
'style_start-transform:translateY(1000px), style_end-transform:translateY(0px)' => esc_html__('Slide In Up ', 'snn'),
'style_start-transform:translateY(0px), style_end-transform:translateY(-1000px)' => esc_html__('Slide Out Up ', 'snn'),


// All other animations kept exactly the same as you had
// Rotating
'style_start-opacity:0, style_end-opacity:1, style_start-transform:rotate(-200deg) scale(0.8), style_end-transform:rotate(0deg) scale(1)' => esc_html__('Rotate In', 'snn'),
'style_start-opacity:1, style_end-opacity:0, style_start-transform:rotate(0deg) scale(1), style_end-transform:rotate(200deg) scale(0.8)' => esc_html__('Rotate Out', 'snn'),
'style_start-opacity:0, style_end-opacity:1, style_start-transform:rotate(-90deg) translateY(-1000px), style_end-transform:rotate(0deg) translateY(0px)' => esc_html__('Rotate In Down Left', 'snn'),
'style_start-opacity:1, style_end-opacity:0, style_start-transform:rotate(0deg) translateY(0px), style_end-transform:rotate(90deg) translateY(1000px)' => esc_html__('Rotate Out Down Left', 'snn'),
'style_start-opacity:0, style_end-opacity:1, style_start-transform:rotate(90deg) translateY(-1000px), style_end-transform:rotate(0deg) translateY(0px)' => esc_html__('Rotate In Down Right', 'snn'),
'style_start-opacity:1, style_end-opacity:0, style_start-transform:rotate(0deg) translateY(0px), style_end-transform:rotate(-90deg) translateY(1000px)' => esc_html__('Rotate Out Down Right', 'snn'),
'style_start-opacity:0, style_end-opacity:1, style_start-transform:rotate(-90deg) translateY(1000px), style_end-transform:rotate(0deg) translateY(0px)' => esc_html__('Rotate In Up Left', 'snn'),
'style_start-opacity:1, style_end-opacity:0, style_start-transform:rotate(0deg) translateY(0px), style_end-transform:rotate(90deg) translateY(-1000px)' => esc_html__('Rotate Out Up Left', 'snn'),
'style_start-opacity:0, style_end-opacity:1, style_start-transform:rotate(90deg) translateY(1000px), style_end-transform:rotate(0deg) translateY(0px)' => esc_html__('Rotate In Up Right', 'snn'),
'style_start-opacity:1, style_end-opacity:0, style_start-transform:rotate(0deg) translateY(0px), style_end-transform:rotate(-90deg) translateY(-1000px)' => esc_html__('Rotate Out Up Right', 'snn'),

// Flipping & 3D
'style_start-opacity:0, style_end-opacity:1, style_start-transform:rotateX(90deg), style_end-transform:rotateX(0deg)' => esc_html__('Flip In X', 'snn'),
'style_start-opacity:1, style_end-opacity:0, style_start-transform:rotateX(0deg), style_end-transform:rotateX(90deg)' => esc_html__('Flip Out X', 'snn'),
'style_start-opacity:0, style_end-opacity:1, style_start-transform:rotateY(90deg), style_end-transform:rotateY(0deg)' => esc_html__('Flip In Y', 'snn'),
'style_start-opacity:1, style_end-opacity:0, style_start-transform:rotateY(0deg), style_end-transform:rotateY(90deg)' => esc_html__('Flip Out Y', 'snn'),
'style_start-opacity:0, style_end-opacity:1, style_start-transform:rotate3d(1,1,0,90deg), style_end-transform:rotate3d(1,1,0,0deg)' => esc_html__('Flip In 3D', 'snn'),
'style_start-opacity:1, style_end-opacity:0, style_start-transform:rotate3d(1,1,0,0deg), style_end-transform:rotate3d(1,1,0,90deg)' => esc_html__('Flip Out 3D', 'snn'),
'style_start-opacity:0, style_end-opacity:1, style_start-transform:rotateY(90deg) scale(0.8), style_end-transform:rotateY(0deg) scale(1)' => esc_html__('Cube Rotate In', 'snn'),
'style_start-opacity:1, style_end-opacity:0, style_start-transform:rotateY(0deg) scale(1), style_end-transform:rotateY(90deg) scale(0.8)' => esc_html__('Cube Rotate Out', 'snn'),
'style_start-opacity:0, style_end-opacity:1, style_start-transform:rotateY(180deg), style_end-transform:rotateY(0deg)' => esc_html__('Card Flip In', 'snn'),
'style_start-opacity:1, style_end-opacity:0, style_start-transform:rotateY(0deg), style_end-transform:rotateY(180deg)' => esc_html__('Card Flip Out', 'snn'),


// Scaling (very common utility)
'style_start-transform:scale(0), style_end-transform:scale(1)' => esc_html__('Scale 0 to 1', 'snn'),
'style_start-transform:scale(10), style_end-transform:scale(1)' => esc_html__('Scale 10 to 1', 'snn'),
'style_start-transform:scale(1), style_end-transform:scale(0)' => esc_html__('Scale 1 to 0', 'snn'),


// Opacity Utility (great for fading/combos)
'style_start-opacity:0, style_end-opacity:1' => esc_html__('Opacity 0 to 1', 'snn'),
'style_start-opacity:1, style_end-opacity:0' => esc_html__('Opacity 1 to 0', 'snn'),

// Rotate
'style_start-transform:rotate(0deg), style_end-transform:rotate(180deg)' => esc_html__('Rotate 0 to 180', 'snn'),
'style_start-transform:rotate(180deg), style_end-transform:rotate(0deg)' => esc_html__('Rotate 180 to 0', 'snn'),

'delay:0.1' => esc_html__('Delay 0.1', 'snn'),
'delay:1' => esc_html__('Delay 1', 'snn'),
'delay:2' => esc_html__('Delay 2', 'snn'),
'delay:3' => esc_html__('Delay 3', 'snn'),
'delay:4' => esc_html__('Delay 4', 'snn'),



 
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