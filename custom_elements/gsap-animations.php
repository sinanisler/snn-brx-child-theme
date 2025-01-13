<?php
// element-gsap-animations.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Prefix_Element_Gsap_Animations extends \Bricks\Element {
    // Element properties
    public $category     = 'snn'; // Custom category 'snn'
    public $name         = 'gsap-animations'; // Unique element name
    public $icon         = 'ti-bolt-alt'; // Themify icon class
    public $css_selector = '.prefix-gsap-animations-wrapper'; // Default CSS selector
    public $scripts      = []; // No scripts to enqueue as per user instruction
    public $nestable     = true; 

    /**
     * Return localized element label.
     */
    public function get_label() {
        return esc_html__( 'GSAP Animations', 'bricks' );
    }

    /**
     * Set builder control groups.
     * Removed 'animations' and 'trigger' groups as per user instruction.
     */
    public function set_control_groups() {
        // No control groups needed
    }

    /**
     * Define element controls.
     */
    public function set_controls() {
        // Repeater Control for Animations without "Animation Label"
        $this->controls['animations'] = [
            'tab'           => 'content',
            'label'         => esc_html__( 'Animations', 'bricks' ),
            'type'          => 'repeater',
            'titleProperty' => '', // Removed 'animation_label',
            'description'   => '  <p data-control="info"> To make this feature work you need to enable Other Settings > GSAP setting. <p>',
            'default'       => [
                [
                    'x'               => '',
                    'y'               => '',
                    'opacity'         => '',
                    'scale'           => '',
                    'rotate'          => '',
                    'duration'        => '',
                    'delay'           => '',
                    'scroll'          => 'true',
                    'scrub'           => '',
                    'pin'             => 'false',
                    'markers'         => 'false',
                    'toggleClass'     => '',
                    'pinSpacing'      => '',
                ],
            ],
            'placeholder'   => esc_html__( 'Animation', 'bricks' ),
            'fields'        => [
                // Specific Animation Properties
                'x' => [
                    'label'       => esc_html__( 'Move Horizontal (px)', 'bricks' ),
                    'type'        => 'number',
                    'default'     => '',
                    'placeholder' => esc_html__( 'e.g., 100', 'bricks' ),
                ],
                'y' => [
                    'label'       => esc_html__( 'Move Vertical (px)', 'bricks' ),
                    'type'        => 'number',
                    'default'     => '',
                    'placeholder' => esc_html__( 'e.g., -50', 'bricks' ),
                ],
                'opacity' => [
                    'label'       => esc_html__( 'Opacity', 'bricks' ),
                    'type'        => 'number',
                    'min'         => '0',
                    'max'         => '1',
                    'step'        => '0.1',
                    'default'     => '',
                    'placeholder' => esc_html__( 'e.g., 0.5', 'bricks' ),
                ],
                'scale' => [
                    'label'       => esc_html__( 'Scale', 'bricks' ),
                    'type'        => 'number',
                    'min'         => '0',
                    'step'        => '0.1',
                    'default'     => '',
                    'placeholder' => esc_html__( 'e.g., 1.5', 'bricks' ),
                ],
                'rotate' => [
                    'label'       => esc_html__( 'Rotate (degrees)', 'bricks' ),
                    'type'        => 'number',
                    'default'     => '',
                    'placeholder' => esc_html__( 'e.g., 90', 'bricks' ),
                ],
                'duration' => [
                    'label'       => esc_html__( 'Duration (s)', 'bricks' ),
                    'type'        => 'number',
                    'min'         => '0',
                    'step'        => '0.1',
                    'default'     => '1',
                    'placeholder' => esc_html__( 'e.g., 2', 'bricks' ),
                ],
                'delay' => [
                    'label'       => esc_html__( 'Delay (s)', 'bricks' ),
                    'type'        => 'number',
                    'min'         => '0',
                    'step'        => '0.1',
                    'default'     => '0',
                    'placeholder' => esc_html__( 'e.g., 0.5', 'bricks' ),
                ],
                'scroll' => [
                    'label'       => esc_html__( 'Enable Scroll Trigger', 'bricks' ),
                    'type'        => 'select',
                    'options'     => [
                        'true'  => esc_html__( 'Yes', 'bricks' ),
                        'false' => esc_html__( 'No', 'bricks' ),
                    ],
                    'default'     => 'true',
                    'inline'      => true,
                    'placeholder' => esc_html__( 'Select', 'bricks' ),
                ],
                'scrub' => [
                    'label'       => esc_html__( 'Scrub', 'bricks' ),
                    'type'        => 'select',
                    'options'     => [
                        'false' => esc_html__( 'False', 'bricks' ),
                        'true'  => esc_html__( 'True', 'bricks' ),
                        '1'     => esc_html__( '1', 'bricks' ),
                        '2'     => esc_html__( '2', 'bricks' ),
                        // Add more numeric options as needed
                    ],
                    'default'     => 'false',
                    'inline'      => true,
                    'placeholder' => esc_html__( 'Select', 'bricks' ),
                ],
                'pin' => [
                    'label'       => esc_html__( 'Pin', 'bricks' ),
                    'type'        => 'select',
                    'options'     => [
                        'true'  => esc_html__( 'Yes', 'bricks' ),
                        'false' => esc_html__( 'No', 'bricks' ),
                    ],
                    'default'     => 'false',
                    'inline'      => true,
                    'placeholder' => esc_html__( 'Select', 'bricks' ),
                ],
                'markers' => [
                    'label'       => esc_html__( 'Markers', 'bricks' ),
                    'type'        => 'select',
                    'options'     => [
                        'true'  => esc_html__( 'Yes', 'bricks' ),
                        'false' => esc_html__( 'No', 'bricks' ),
                    ],
                    'default'     => 'false',
                    'inline'      => true,
                    'placeholder' => esc_html__( 'Select', 'bricks' ),
                ],
                'toggleClass' => [
                    'label'       => esc_html__( 'Toggle Class', 'bricks' ),
                    'type'        => 'text',
                    'default'     => '',
                    'placeholder' => esc_html__( 'e.g., active', 'bricks' ),
                ],
                'pinSpacing' => [
                    'label'       => esc_html__( 'Pin Spacing', 'bricks' ),
                    'type'        => 'select',
                    'options'     => [
                        'margin' => esc_html__( 'Margin', 'bricks' ),
                        'padding' => esc_html__( 'Padding', 'bricks' ),
                        'false'   => esc_html__( 'False', 'bricks' ),
                    ],
                    'default'     => 'margin',
                    'inline'      => true,
                    'placeholder' => esc_html__( 'Select', 'bricks' ),
                ],
            ],
        ];

        // Removed 'data_trigger' control as the 'trigger' group setting is not needed
    }

    /**
     * Enqueue element-specific scripts.
     * (No scripts to enqueue as per user instruction)
     */
    public function enqueue_scripts() {
        // No scripts to enqueue
    }

    /**
     * Render element HTML.
     */
    public function render() {
        // Set wrapper classes
        $root_classes = ['prefix-gsap-animations-wrapper'];

        // Add additional classes if needed
        $this->set_attribute( '_root', 'class', $root_classes );

        // Get animations settings
        $animations = isset( $this->settings['animations'] ) ? $this->settings['animations'] : [];

        // Initialize array to hold each animation's string
        $animation_strings = [];

        foreach ( $animations as $anim ) {
            $props = [];

            // Handle other properties
            if ( $anim['x'] !== '' ) {
                $x = floatval( $anim['x'] );
                $props[] = "x:{$x}";
            }

            if ( $anim['y'] !== '' ) {
                $y = floatval( $anim['y'] );
                $props[] = "y:{$y}";
            }

            if ( $anim['opacity'] !== '' ) {
                $opacity = floatval( $anim['opacity'] );
                $props[] = "opacity:{$opacity}";
            }

            if ( $anim['scale'] !== '' ) {
                $scale = floatval( $anim['scale'] );
                $props[] = "scale:{$scale}";
            }

            if ( $anim['rotate'] !== '' ) {
                $rotate = floatval( $anim['rotate'] );
                $props[] = "rotate:{$rotate}";
            }

            if ( ! empty( $anim['duration'] ) ) {
                $duration = floatval( $anim['duration'] );
                $props[] = "duration:{$duration}";
            }

            if ( ! empty( $anim['delay'] ) ) {
                $delay = floatval( $anim['delay'] );
                $props[] = "delay:{$delay}";
            }

            // Removed stagger processing

            if ( ! empty( $anim['scroll'] ) ) {
                $scroll = filter_var( $anim['scroll'], FILTER_VALIDATE_BOOLEAN ) ? 'true' : 'false';
                $props[] = "scroll:{$scroll}";
            }

            if ( ! empty( $anim['scrub'] ) ) {
                if ( is_numeric( $anim['scrub'] ) ) {
                    $scrub = floatval( $anim['scrub'] );
                } else {
                    $scrub = filter_var( $anim['scrub'], FILTER_VALIDATE_BOOLEAN ) ? 'true' : 'false';
                }
                $props[] = "scrub:{$scrub}";
            }

            if ( ! empty( $anim['pin'] ) ) {
                $pin = filter_var( $anim['pin'], FILTER_VALIDATE_BOOLEAN ) ? 'true' : 'false';
                $props[] = "pin:{$pin}";
            }

            if ( ! empty( $anim['markers'] ) ) {
                $markers = filter_var( $anim['markers'], FILTER_VALIDATE_BOOLEAN ) ? 'true' : 'false';
                $props[] = "markers:{$markers}";
            }

            if ( ! empty( $anim['toggleClass'] ) ) {
                $toggleClass = sanitize_html_class( $anim['toggleClass'] );
                $props[] = "toggleClass:{$toggleClass}";
            }

            if ( ! empty( $anim['pinSpacing'] ) ) {
                $pinSpacing = sanitize_text_field( $anim['pinSpacing'] );
                $props[] = "pinSpacing:{$pinSpacing}";
            }

            // Combine properties for this animation
            if ( ! empty( $props ) ) {
                $animation_strings[] = implode( ', ', $props );
            }
        }

        // Combine all animations separated by "; "
        $data_animate = implode( '; ', $animation_strings );

        // Prepare the final data-animate attribute
        $data_animate_attr = '';
        if ( ! empty( $data_animate ) ) {
            $data_animate_sanitized = esc_attr( $data_animate );
            $data_animate_attr = " data-animate=\"{$data_animate_sanitized}\"";
        }

        // Combine other attributes
        $other_attributes = $this->render_attributes( '_root' );

        // Output the element wrapper
        echo '<div ' . $data_animate_attr . ' ' . $other_attributes . '>';
            // Users can place their own content inside this wrapper
            // For demonstration, we'll add a container
            echo '<div class="gsap-animated-content">';
                echo '<p>' . esc_html__( 'Animate me with GSAP!', 'bricks' ) . '</p>';
            echo '</div>';
        echo '</div>';
    }
}
?>
