<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

use Bricks\Element;

class Animated_Text_Element extends Element {
    public $category = 'snn';
    public $name     = 'animated-vfx-text';
    public $icon     = 'ti-text';
    public $scripts  = []; // No external scripts are enqueued here
    public $nestable = false;

    public function get_label() {
        return esc_html__( 'Animated VFX Text', 'bricks' );
    }

    public function set_control_groups() {
        // Define control groups if necessary
    }

    public function set_controls() {
        // Single text control for Animated Text
        $this->controls['animated_text'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Animated Text', 'bricks' ),
            'type'    => 'text',
            'default' => esc_html__( 'Lorem ipsum dolor sinan amet animated text', 'bricks' ),
        ];

        // Select list for animation effects
        $this->controls['animation_effect'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Animation Effect', 'bricks' ),
            'type'    => 'select',
            'options' => [
                'glitch'             => esc_html__( 'Glitch', 'bricks' ),
                'rgbShift'           => esc_html__( 'RGB Shift', 'bricks' ),
                'rainbow'            => esc_html__( 'Rainbow', 'bricks' ),
                'warpTransition'     => esc_html__( 'Warp Transition', 'bricks' ),
                'slitScanTransition' => esc_html__( 'Slit Scan Transition', 'bricks' ),
                'pixelateTransition' => esc_html__( 'Pixelate Transition', 'bricks' ),
                // Removed Focus Transition because it doesn't work
                'wavevy'             => esc_html__( 'Wavevy Wave Shader', 'bricks' ),
            ],
            'default' => 'glitch',
        ];

        // Select list for DOM element tag selection
        $this->controls['dom_element_tag'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'DOM Element Tag', 'bricks' ),
            'type'    => 'select',
            'options' => [
                'h1'   => esc_html__( 'H1', 'bricks' ),
                'h2'   => esc_html__( 'H2', 'bricks' ),
                'h3'   => esc_html__( 'H3', 'bricks' ),
                'h4'   => esc_html__( 'H4', 'bricks' ),
                'p'    => esc_html__( 'Paragraph', 'bricks' ),
                'span' => esc_html__( 'Span', 'bricks' ),
                'div'  => esc_html__( 'Div', 'bricks' ),
            ],
            'default'     => 'div',
            'description' => "
                <br> 
                <p data-control='info'>
                    Scroll Start and Stop can be counter-intuitive.
                    Enable markers and test it out.
                </p>
            ",
        ];

        // Typography control for text styling
        $this->controls['text_typography'] = [
            'tab'   => 'style',
            'group' => 'Typography',
            'label' => esc_html__( 'Typography', 'bricks' ),
            'type'  => 'typography',
            'css'   => [
                [
                    'property' => 'font',
                    'selector' => '.animated-vfx-text',
                ],
            ],
        ];
    }

    public function render() {
        // Generate a unique class for this element
        $unique_class = 'animated-vfx-text-' . uniqid();

        // Set root element attributes including the unique class
        $root_classes = ['snn-gsap-text-animations-wrapper', $unique_class];
        $this->set_attribute('_root', 'class', $root_classes);

        $animated_text    = isset( $this->settings['animated_text'] ) ? $this->settings['animated_text'] : '';
        $animation_effect = isset( $this->settings['animation_effect'] ) ? $this->settings['animation_effect'] : 'glitch';
        $dom_element_tag  = isset( $this->settings['dom_element_tag'] ) ? $this->settings['dom_element_tag'] : 'div';

        // Ensure variables are strings before output
        $animated_text    = is_array( $animated_text ) ? '' : esc_html( $animated_text );
        $animation_effect = is_array( $animation_effect ) ? 'glitch' : $animation_effect;
        $dom_element_tag  = is_array( $dom_element_tag ) ? 'div' : $dom_element_tag;
        ?>
        <<?php echo esc_html( $dom_element_tag ); ?> <?php echo $this->render_attributes( '_root' ); ?> data-effect="<?php echo esc_attr( $animation_effect ); ?>">
            <?php echo $animated_text; ?>
        </<?php echo esc_html( $dom_element_tag ); ?>>
        <style>
            .snn-gsap-text-animations-wrapper {
                display: inline-block;
            }
        </style>
        <script type="module">
            import { VFX } from "https://esm.sh/@vfx-js/core";
            document.addEventListener("DOMContentLoaded", () => {
                const vfx = new VFX();
                // Using the unique class to select the element
                const textEl = document.querySelector(".<?php echo esc_js( $unique_class ); ?>");
                if (textEl) {
                    let options = {};
                    const effect = textEl.getAttribute('data-effect');
                    switch(effect) {
                        case "glitch":
                            options = { shader: "glitch", overflow: 50 };
                            break;
                        case "rgbShift":
                            options = { shader: "rgbShift" };
                            break;
                        case "rainbow":
                            options = { shader: "rainbow" };
                            break;
                        case "warpTransition":
                            options = { shader: "warpTransition" };
                            break;
                        case "slitScanTransition":
                            options = { shader: "slitScanTransition" };
                            break;
                        case "pixelateTransition":
                            options = { shader: "pixelateTransition" };
                            break;
                        case "wavevy":
                            options = {
                                shader: `
precision highp float;
uniform vec2 resolution;   // Resolution of the output
uniform vec2 offset;       // Offset for positioning
uniform float time;        // Time parameter for animation
uniform sampler2D src;     // Source texture
uniform float wave;        // Wave amplitude
out vec4 outColor;
void main(void) {
    // Normalize pixel coordinates to [0,1]
    vec2 uv = (gl_FragCoord.xy - offset) / resolution;
    // Apply multi-directional sine wave distortions
    float frequency = 10.0;
    float amplitude = wave * 0.003;
    uv.x += sin(uv.y * frequency + time) * amplitude;
    uv.y += cos(uv.x * frequency + time) * amplitude;
    // Sample the texture with the modified coordinates
    outColor = texture(src, uv);
}
                                `,
                                uniforms: {
                                    wave: () => Math.sin(Date.now() * 0.001) * 2,
                                }
                            };
                            break;
                        default:
                            options = { shader: "glitch", overflow: 50 };
                    }
                    vfx.add(textEl, options);
                }
            });
        </script>
        <?php
    }

    public static function render_builder() {
        ?>
        <script type="text/x-template" id="tmpl-bricks-element-animated-vfx-text">
            <component :is="element.settings.dom_element_tag || 'div'" class="animated-vfx-text">
                {{ element.settings.animated_text }}
            </component>
        </script>
        <?php
    }
}
?>
