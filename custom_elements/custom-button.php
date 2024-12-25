<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Custom_Element_Button extends \Bricks\Element {
    public $category      = 'snn';
    public $name          = 'custom-button';
    public $icon          = 'fas fa-hand-pointer';
    public $css_selector  = '.custom-button-wrapper';
    public $scripts       = [];

    public function get_label() {
        return 'Button EXPERIMENTAL -SNN';
    }

    public function set_controls() {
        // Existing Controls
        $this->controls['button_label'] = [
            'tab'     => 'content',
            'label'   => 'Button Label',
            'type'    => 'text',
            'default' => 'Click Me',
        ];

        $this->controls['button_link'] = [
            'tab'         => 'content',
            'label'       => 'Button Link',
            'type'        => 'link',
            'placeholder' => 'https://yoursite.com',
            'pasteStyles' => false,
        ];

        $this->controls['animation_style'] = [
            'tab'     => 'content',
            'label'   => 'Animation Style',
            'type'    => 'select',
            'options' => [
                'none'            => 'None',
                'gradient-slide'  => 'Gradient Slide',
                'shadow-lift'     => 'Shadow Lift',
                'scale-up'        => 'Scale Up',
            ],
            'default' => 'none',
        ];

        $this->controls['typography'] = [
            'tab'   => 'content',
            'label' => 'Typography',
            'type'  => 'typography',
            'css'   => [
                [
                    'property' => 'font-family',
                    'selector' => '.custom-button',
                ],
                [
                    'property' => 'font-size',
                    'selector' => '.custom-button',
                ],
                [
                    'property' => 'font-weight',
                    'selector' => '.custom-button',
                ],
                [
                    'property' => 'line-height',
                    'selector' => '.custom-button',
                ],
                [
                    'property' => 'letter-spacing',
                    'selector' => '.custom-button',
                ],
            ],
        ];

        $this->controls['background_color'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Background Color', 'bricks' ),
            'type'  => 'color',
            'css'   => [
                [
                    'property' => 'background-color',
                    'selector' => '.custom-button',
                ],
            ],
            'default' => '#0073e6',
        ];

        $this->controls['text_color'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Text Color', 'bricks' ),
            'type'  => 'color',
            'css'   => [
                [
                    'property' => 'color',
                    'selector' => '.custom-button',
                ],
            ],
            'default' => '#ffffff',
        ];

        $this->controls['padding'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Padding', 'bricks' ),
            'type'  => 'dimensions',
            'css'   => [
                [
                    'property' => 'padding',
                    'selector' => '.custom-button',
                ],
            ],
            'default' => [
                'top'    => '10px',
                'right'  => '20px',
                'bottom' => '10px',
                'left'   => '20px',
            ],
        ];

        $this->controls['box_shadow'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Box Shadow', 'bricks' ),
            'type'  => 'box-shadow',
            'css'   => [
                [
                    'property' => 'box-shadow',
                    'selector' => '.custom-button',
                ],
            ],
            'inline' => true,
            'small'  => true,
        ];

        // ** New Controls for Icon Selection **
        
        // Icon Selection Control
        $this->controls['button_icon'] = [
            'tab'       => 'content',
            'label'     => esc_html__( 'Button Icon', 'bricks' ),
            'type'      => 'icon', // Ensure Bricks supports 'icon' type
            'default'   => [
                'library' => 'fontawesome', // Options: fontawesome, ionicons, themify
                'icon'    => 'fas fa-star',  // Example FontAwesome icon class
            ],
            'separator' => 'before',
            'css'       => [
                [
                    'selector' => '.custom-button-icon', // Target for CSS styling
                ],
            ],
        ];

        // Icon Placement Control
        $this->controls['icon_position'] = [
            'tab'       => 'content',
            'label'     => 'Icon Position',
            'type'      => 'select',
            'options'   => [
                'left'  => 'Left',
                'right' => 'Right',
            ],
            'default'   => 'left',
            'condition' => [
                'button_icon' => [
                    'library' => '!empty', // Show only if an icon is selected
                ],
            ],
        ];

        // ** New Control for Icon Gap **
        $this->controls['icon_gap'] = [
            'tab'       => 'content',
            'label'     => 'Icon Gap (px)',
            'type'      => 'number',
            'unit'      => 'px',
            'min'       => 0,
            'step'      => 1,
            'default'   => 10,
            'condition' => [
                'button_icon' => [
                    'library' => '!empty',
                ],
            ],
        ];

        // ** New Control for Button Border **
        $this->controls['button_border'] = [
            'tab'    => 'content',
            'label'  => esc_html__( 'Button Border', 'bricks' ),
            'type'   => 'border',
            'css'    => [
                [
                    'property' => 'border',
                    'selector' => '.custom-button',
                ],
                [
                    'property' => 'border-radius',
                    'selector' => '.custom-button',
                ],
            ],
            'inline' => true,
            'small'  => true,
            'default' => [
                'width' => [
                    'top'    => 1,
                    'right'  => 1,
                    'bottom' => 1,
                    'left'   => 1,
                ],
                'style' => 'solid',
                'color' => [
                    'hex' => '#000000',
                ],
                'radius' => [
                    'top'    => 4,
                    'right'  => 4,
                    'bottom' => 4,
                    'left'   => 4,
                ],
            ],
        ];
    }

    public function render() {
        $label         = $this->settings['button_label'] ?? 'Click Me';
        $link          = $this->settings['button_link'] ?? null;
        $animation     = $this->settings['animation_style'] ?? 'none';
        $button_icon   = $this->settings['button_icon'] ?? null;
        $icon_position = $this->settings['icon_position'] ?? 'left';
        $icon_gap      = intval( $this->settings['icon_gap'] ?? 10 ); // Default to 10 if not set

        // Generate a unique class based on animation to prevent conflicts
        $animation_class = $animation !== 'none' ? ' animate-' . esc_attr( $animation ) . '-hover' : '';

        $this->set_attribute( '_root', 'class', 'custom-button-wrapper' );
        $this->set_attribute( 'button', 'class', 'custom-button' . $animation_class );

        if ( $link ) {
            $this->set_link_attributes( 'button', $link );
        } else {
            $this->set_attribute( 'button', 'type', 'button' );
        }

        // Prepare inline style for icon gap
        $icon_gap_style = '';
        if ( is_array( $button_icon ) && ! empty( $button_icon['icon'] ) ) {
            if ( $icon_position === 'left' ) {
                $icon_gap_style = 'style="margin-right: ' . esc_attr( $icon_gap ) . 'px;"';
            } else {
                $icon_gap_style = 'style="margin-left: ' . esc_attr( $icon_gap ) . 'px;"';
            }
        }

        echo '<div ' . $this->render_attributes( '_root' ) . '>';
        if ( $link ) {
            echo '<a ' . $this->render_attributes( 'button' ) . '>';
        } else {
            echo '<button ' . $this->render_attributes( 'button' ) . '>';
        }

        // Render Icon and Label based on Position
        if ( is_array( $button_icon ) && ! empty( $button_icon['icon'] ) ) {
            $icon_class = esc_attr( $button_icon['icon'] );
            $icon_html  = '<i class="' . $icon_class . ' custom-button-icon" aria-hidden="true" ' . $icon_gap_style . '></i>';

            if ( $icon_position === 'left' ) {
                echo $icon_html . ' ' . esc_html( $label );
            } else {
                echo esc_html( $label ) . ' ' . $icon_html;
            }
        } else {
            echo esc_html( $label );
        }

        if ( $link ) {
            echo '</a>';
        } else {
            echo '</button>';
        }
        echo '</div>';
    }

    public function enqueue_scripts() {
        // Enqueue FontAwesome if not already enqueued
        // Uncomment the line below if FontAwesome is not already loaded in your theme or plugin
        // wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css' );

        // Always enqueue the base styles
        ?>
        <style>
            .custom-button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                text-decoration: none;
                transition: all 0.3s ease;
                cursor: pointer;
            }

            .custom-button-wrapper {
                display: inline-block;
            }

            .custom-button-icon {
                display: inline-block;
            }

            /* Adjust margin is handled via inline styles */
        </style>
        <?php

        // Retrieve the selected animation from the settings
        $animation = $this->settings['animation_style'] ?? 'none';

        // Conditionally enqueue animation-specific CSS
        switch ( $animation ) {
            case 'gradient-slide':
                ?>
                <style>
                    .animate-gradient-slide-hover {
                        background-size: 300% 100%;
                        transition: all 0.4s ease-in-out;
                        background-image: linear-gradient(to right, #25aae1, #40e495, #2bb673);
                        box-shadow: 0 4px 15px 0 rgba(49, 196, 190, 0.75);
                    }
                    .animate-gradient-slide-hover:hover {
                        background-position: 100% 0;
                    }
                </style>
                <?php
                break;

            case 'shadow-lift':
                ?>
                <style>
                    .animate-shadow-lift-hover {
                        transition: box-shadow 0.3s ease, transform 0.3s ease;
                    }
                    .animate-shadow-lift-hover:hover {
                        box-shadow: 0 10px 15px rgba(0,0,0,0.2);
                        transform: translateY(-3px);
                    }
                </style>
                <?php
                break;

            case 'scale-up':
                ?>
                <style>
                    .animate-scale-up-hover {
                        transform: scale(1);
                        transition: transform 0.3s ease;
                    }
                    .animate-scale-up-hover:hover {
                        transform: scale(1.05);
                    }
                </style>
                <?php
                break;

            // No additional CSS needed for 'none'
            default:
                break;
        }
    }
}

add_action( 'bricks_register_elements', function() {
    \Bricks\Element::register_element( 'Custom_Element_Button', 'custom-button' );
} );
