<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

use Bricks\Element;
use Bricks\Frontend;

class Flipbox_Element extends Element {
    public $category       = 'basic';
    public $name           = 'flipbox';
    public $icon           = 'ti-exchange-vertical';
    public $css_selector   = '.flip-container';
    public $scripts        = [];
    public $nestable       = true;
    public $nestable_areas = ['front', 'back'];

    public function get_label() {
        return esc_html__( 'Flipbox (Nestable)', 'bricks' );
    }

    public function set_controls() {
        // Flipbox Height Control.
        $this->controls['flipbox_height'] = [
            'tab'         => 'content',
            'type'        => 'number',
            'label'       => esc_html__( 'Flipbox Height', 'bricks' ),
            'default'     => 250,
            'min'         => 100,
            'max'         => 1000,
            'step'        => 1,
            'unit'        => 'px',
            'description' => "<br>
                <p data-control='info'>
                    Add 2 Column or 2 Block or 2 Div and create your own custom flipbox. Easy.
                </p>
            ",
        ];

        // Flipbox Perspective Control.
        $this->controls['flipbox_perspective'] = [
            'tab'         => 'content',
            'type'        => 'number',
            'label'       => esc_html__( 'Flipbox Perspective', 'bricks' ),
            'default'     => '1000px',
            'step'        => 1,
            'unit'        => 'px',
        ];

        // Flipbox Animation Control.
        $this->controls['flipbox_animation'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Flipbox Animation', 'bricks' ),
            'type'    => 'select',
            'options' => [
                'left-to-right'         => esc_html__( 'Left to Right (Flip)', 'bricks' ),
                'right-to-left'         => esc_html__( 'Right to Left (Flip)', 'bricks' ),
                'top-to-bottom'         => esc_html__( 'Top to Bottom (Flip)', 'bricks' ),
                'bottom-to-top'         => esc_html__( 'Bottom to Top (Flip)', 'bricks' ),
                'fade'                  => esc_html__( 'Fade', 'bricks' ),
                'spin-flip'             => esc_html__( 'Spin Flip', 'bricks' ),
                'left-to-right-slide'   => esc_html__( 'Left to Right Slide', 'bricks' ),
                'right-to-left-slide'   => esc_html__( 'Right to Left Slide', 'bricks' ),
                'top-to-down-slide'     => esc_html__( 'Top to Down Slide', 'bricks' ),
                'bottom-to-top-slide'   => esc_html__( 'Bottom to Top Slide', 'bricks' ),
            ],
            'default' => 'left-to-right',
        ];
    }

    public function render() {
        // Retrieve settings.
        $height      = intval( $this->settings['flipbox_height'] ?? 250 );
        $perspective = intval( $this->settings['flipbox_perspective'] ?? 1000 );
        $animation   = $this->settings['flipbox_animation'] ?? 'left-to-right';

        // Set up root element attributes.
        $this->set_attribute( '_root', 'class', 'brxe-flipbox flip-container' );
        if ( ! empty( $this->attributes['_root']['id'] ) ) {
            $root_id = $this->attributes['_root']['id'];
        } else {
            $root_id = 'flipbox-' . uniqid();
            $this->set_attribute( '_root', 'id', $root_id );
        }

        // Render the flipbox container.
        echo '<div ' . $this->render_attributes('_root') . '>';
            echo '<style>
                #' . esc_attr( $root_id ) . ' {
                    width: 100%;
                    height: ' . esc_attr( $height ) . 'px;
                    perspective: ' . esc_attr( $perspective ) . 'px;
                    cursor: pointer;
                }
                #' . esc_attr( $root_id ) . ' .flip-box {
                    width: 100%;
                    height: 100%;
                    position: relative;
                    transform-style: preserve-3d;
                    transition: transform 0.6s ease-in-out;
                }';

                // Animation-specific CSS.
                if ( $animation === "fade" ) {
                    echo '
                    #' . esc_attr( $root_id ) . ' .flip-box > div {
                        width: 100%;
                        height: 100%;
                        position: absolute;
                        transition: opacity 0.6s ease-in-out;
                    }
                    #' . esc_attr( $root_id ) . ' .flip-box > div:nth-of-type(1) {
                        opacity: 1;
                    }
                    #' . esc_attr( $root_id ) . ' .flip-box > div:nth-of-type(2) {
                        opacity: 0;
                    }
                    #' . esc_attr( $root_id ) . ':hover .flip-box > div:nth-of-type(1),
                    #' . esc_attr( $root_id ) . ':focus .flip-box > div:nth-of-type(1) {
                        opacity: 0;
                    }
                    #' . esc_attr( $root_id ) . ':hover .flip-box > div:nth-of-type(2),
                    #' . esc_attr( $root_id ) . ':focus .flip-box > div:nth-of-type(2) {
                        opacity: 1;
                    }';
                } elseif ( in_array( $animation, ['left-to-right-slide', 'right-to-left-slide', 'top-to-down-slide', 'bottom-to-top-slide'] ) ) {
                    echo '
                    #' . esc_attr( $root_id ) . ' .flip-box > div {
                        width: 100%;
                        height: 100%;
                        position: absolute;
                        transition: transform 0.6s ease-in-out;
                    }';
                    if ( $animation === "left-to-right-slide" ) {
                        echo '
                        #' . esc_attr( $root_id ) . ' .flip-box > div:nth-of-type(1) {
                            transform: translateX(0);
                        }
                        #' . esc_attr( $root_id ) . ' .flip-box > div:nth-of-type(2) {
                            transform: translateX(100%);
                        }
                        #' . esc_attr( $root_id ) . ':hover .flip-box > div:nth-of-type(1),
                        #' . esc_attr( $root_id ) . ':focus .flip-box > div:nth-of-type(1) {
                            transform: translateX(-100%);
                        }
                        #' . esc_attr( $root_id ) . ':hover .flip-box > div:nth-of-type(2),
                        #' . esc_attr( $root_id ) . ':focus .flip-box > div:nth-of-type(2) {
                            transform: translateX(0);
                        }';
                    } elseif ( $animation === "right-to-left-slide" ) {
                        echo '
                        #' . esc_attr( $root_id ) . ' .flip-box > div:nth-of-type(1) {
                            transform: translateX(0);
                        }
                        #' . esc_attr( $root_id ) . ' .flip-box > div:nth-of-type(2) {
                            transform: translateX(-100%);
                        }
                        #' . esc_attr( $root_id ) . ':hover .flip-box > div:nth-of-type(1),
                        #' . esc_attr( $root_id ) . ':focus .flip-box > div:nth-of-type(1) {
                            transform: translateX(100%);
                        }
                        #' . esc_attr( $root_id ) . ':hover .flip-box > div:nth-of-type(2),
                        #' . esc_attr( $root_id ) . ':focus .flip-box > div:nth-of-type(2) {
                            transform: translateX(0);
                        }';
                    } elseif ( $animation === "top-to-down-slide" ) {
                        echo '
                        #' . esc_attr( $root_id ) . ' .flip-box > div:nth-of-type(1) {
                            transform: translateY(0);
                        }
                        #' . esc_attr( $root_id ) . ' .flip-box > div:nth-of-type(2) {
                            transform: translateY(-100%);
                        }
                        #' . esc_attr( $root_id ) . ':hover .flip-box > div:nth-of-type(1),
                        #' . esc_attr( $root_id ) . ':focus .flip-box > div:nth-of-type(1) {
                            transform: translateY(100%);
                        }
                        #' . esc_attr( $root_id ) . ':hover .flip-box > div:nth-of-type(2),
                        #' . esc_attr( $root_id ) . ':focus .flip-box > div:nth-of-type(2) {
                            transform: translateY(0);
                        }';
                    } elseif ( $animation === "bottom-to-top-slide" ) {
                        echo '
                        #' . esc_attr( $root_id ) . ' .flip-box > div:nth-of-type(1) {
                            transform: translateY(0);
                        }
                        #' . esc_attr( $root_id ) . ' .flip-box > div:nth-of-type(2) {
                            transform: translateY(100%);
                        }
                        #' . esc_attr( $root_id ) . ':hover .flip-box > div:nth-of-type(1),
                        #' . esc_attr( $root_id ) . ':focus .flip-box > div:nth-of-type(1) {
                            transform: translateY(-100%);
                        }
                        #' . esc_attr( $root_id ) . ':hover .flip-box > div:nth-of-type(2),
                        #' . esc_attr( $root_id ) . ':focus .flip-box > div:nth-of-type(2) {
                            transform: translateY(0);
                        }';
                    }
                } else {
                    // Flip animations.
                    $containerHoverTransform = "";
                    $backTransform = "";
                    if ( $animation === "left-to-right" ) {
                        $containerHoverTransform = "rotateY(180deg)";
                        $backTransform = "rotateY(180deg)";
                    } elseif ( $animation === "right-to-left" ) {
                        $containerHoverTransform = "rotateY(-180deg)";
                        $backTransform = "rotateY(-180deg)";
                    } elseif ( $animation === "top-to-bottom" ) {
                        $containerHoverTransform = "rotateX(180deg)";
                        $backTransform = "rotateX(180deg)";
                    } elseif ( $animation === "bottom-to-top" ) {
                        $containerHoverTransform = "rotateX(-180deg)";
                        $backTransform = "rotateX(-180deg)";
                    } elseif ( $animation === "spin-flip" ) {
                        $containerHoverTransform = "rotateY(180deg) rotateZ(360deg)";
                        $backTransform = "rotateY(180deg)";
                    }
                    echo '
                    #' . esc_attr( $root_id ) . ' .flip-box > div {
                        width: 100%;
                        height: 100%;
                        position: absolute;
                        backface-visibility: hidden;
                        display: flex;
                    }
                    #' . esc_attr( $root_id ) . ' .flip-box > div:nth-of-type(2) {
                        transform: ' . esc_attr( $backTransform ) . ';
                    }
                    #' . esc_attr( $root_id ) . ':hover .flip-box,
                    #' . esc_attr( $root_id ) . ':focus .flip-box {
                        transform: ' . esc_attr( $containerHoverTransform ) . ';
                    }';
                }
                echo '
                .iframe #' . esc_attr( $root_id ) . ' .flip-box > div {
                    position: relative;
                    backface-visibility: visible;
                }
            </style>';
            // Render inner container without reusing _root attributes.
            echo '<div class="flip-box">';
                echo Frontend::render_children( $this );
            echo '</div>';
        echo '</div>';
    }
}
?>
