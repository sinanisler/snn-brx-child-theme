<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

use Bricks\Element;

class Timeline_Element extends Element {
    public $category     = 'snn';
    public $name         = 'timeline';
    public $icon         = 'ti-time'; 
    public $css_selector = '.timeline-element';
    public $scripts      = []; // No external scripts required.
    public $nestable     = false;

    /**
     * Helper to ensure a value is returned as a string.
     */
    private function ensure_string( $value ) {
        if ( is_array( $value ) ) {
            // For color controls, prefer 'hex'
            if ( isset( $value['hex'] ) ) {
                return $value['hex'];
            }
            // For editor controls, check for 'raw'
            if ( isset( $value['raw'] ) ) {
                return $value['raw'];
            }
            return implode( ' ', $value );
        } elseif ( is_scalar( $value ) ) {
            return (string) $value;
        }
        return '';
    }

    public function get_label() {
        return esc_html__( 'Timeline', 'bricks' );
    }

    public function set_control_groups() {
        // Define control groups if necessary.
    }

    public function set_controls() {
        // Global control for the timeline line color.
        $this->controls['line_color'] = [
            'tab'     => 'content',
            'type'    => 'color',
            'label'   => esc_html__( 'Timeline Line Color', 'bricks' ),
            'default' => '#3498db',
        ];

        // Global control for the timeline dot border color.
        $this->controls['dot_color'] = [
            'tab'     => 'content',
            'type'    => 'color',
            'label'   => esc_html__( 'Timeline Dot Border Color', 'bricks' ),
            'default' => '#3498db',
        ];

        // Global control for the mini line color.
        $this->controls['mini_line_color'] = [
            'tab'     => 'content',
            'type'    => 'color',
            'label'   => esc_html__( 'Mini Line Color', 'bricks' ),
            'default' => '#3498db',
        ];

        // Global control for the timeline line width.
        $this->controls['line_width'] = [
            'tab'     => 'content',
            'type'    => 'number',
            'label'   => esc_html__( 'Timeline Line Width', 'bricks' ),
            'default' => 6,
            'min'     => 1,
            'max'     => 20,
            'step'    => 1,
            'unit'    => 'px',
        ];

        // Repeater for timeline points using an editor control only.
        $this->controls['timeline_points'] = [
            'tab'     => 'content',
            'type'    => 'repeater',
            'label'   => esc_html__( 'Timeline Points', 'bricks' ),
            'default' => [],
            'fields'  => [
                'point_content' => [
                    'type'    => 'editor',
                    'label'   => esc_html__( 'Content', 'bricks' ),
                    'default' => '<p>Timeline point content goes here.</p>',
                ],
            ],
        ];
    }

    public function render() {
        // Retrieve control settings.
        $line_color = isset( $this->settings['line_color'] ) ? $this->ensure_string( $this->settings['line_color'] ) : '#3498db';
        
        $line_width_value = isset( $this->settings['line_width'] ) ? $this->settings['line_width'] : 6;
        if ( is_array( $line_width_value ) ) {
            $line_width_value = reset( $line_width_value );
        }
        $line_width = intval( $line_width_value );

        $dot_color = isset( $this->settings['dot_color'] ) ? $this->ensure_string( $this->settings['dot_color'] ) : '#3498db';

        $mini_line_color = isset( $this->settings['mini_line_color'] ) ? $this->ensure_string( $this->settings['mini_line_color'] ) : $line_color;

        $timeline_points = isset( $this->settings['timeline_points'] ) && is_array( $this->settings['timeline_points'] )
            ? $this->settings['timeline_points']
            : [];

        // Set attributes on the root element.
        $this->set_attribute( '_root', 'class', 'brxe-timeline timeline-element' );
        if ( ! empty( $this->attributes['_root']['id'] ) ) {
            $root_id = $this->attributes['_root']['id'];
        } else {
            $root_id = 'timeline-' . uniqid();
            $this->set_attribute( '_root', 'id', $root_id );
        }

        echo '<div ' . $this->render_attributes( '_root' ) . '>';
            // Inline style block using the new timeline CSS.
            echo '<style>
                #' . esc_attr( $root_id ) . ' .timeline-container {
                    width:100%;
                    margin: 0 auto;
                    position: relative;
                    padding: 20px 0;
                }
                #' . esc_attr( $root_id ) . ' .timeline-container::after {
                    content: "";
                    position: absolute;
                    width: ' . esc_attr( $line_width ) . 'px;
                    background-color: ' . esc_attr( $line_color ) . ';
                    top: 0;
                    bottom: 0;
                    left: 50%;
                    margin-left: -' . esc_attr( round($line_width / 2) ) . 'px;
                    border-radius: 5px;
                }
                #' . esc_attr( $root_id ) . ' .timeline-item {
                    padding: 10px 40px;
                    position: relative;
                    width: 50%;
                    margin-bottom: 30px;
                    box-sizing: border-box;
                }
                #' . esc_attr( $root_id ) . ' .left {
                    left: 0;
                    text-align: right;
                }
                #' . esc_attr( $root_id ) . ' .right {
                    left: 50%;
                    text-align: left;
                }
                #' . esc_attr( $root_id ) . ' .timeline-item::after {
                    content: "";
                    position: absolute;
                    width: 20px;
                    height: 20px;
                    background-color: ' . esc_attr( $dot_color ) . '; 
                    border-radius: 50%;
                    top: 15px;
                    z-index: 1;
                }
                #' . esc_attr( $root_id ) . ' .left::after {
                    right: -10px;
                }
                #' . esc_attr( $root_id ) . ' .right::after {
                    left: -10px;
                }
                #' . esc_attr( $root_id ) . ' .timeline-item::before {
                    content: "";
                    position: absolute;
                    width: 40px;
                    height: 3px;
                    background-color: ' . esc_attr( $mini_line_color ) . ';
                    top: 24px;
                    z-index: 1;
                }
                #' . esc_attr( $root_id ) . ' .left::before {
                    right: 0;
                }
                #' . esc_attr( $root_id ) . ' .right::before {
                    left: 0;
                }
                #' . esc_attr( $root_id ) . ' .content {
                    padding: 15px;
                    background-color: #fff;
                    border-radius: 6px;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                }
                #' . esc_attr( $root_id ) . ' .content p {
                    line-height: 1.5;
                    color: #555;
                }
                @media screen and (max-width: 768px) {
                    #' . esc_attr( $root_id ) . ' .timeline-container::after {
                        left: 31px;
                    }
                    #' . esc_attr( $root_id ) . ' .timeline-item {
                        width: 100%;
                        padding-left: 60px;
                        padding-right: 15px;
                        text-align: left; 
                    }
                    #' . esc_attr( $root_id ) . ' .timeline-item::before {
                        left: 40px;
                        width: 20px;
                    }
                    #' . esc_attr( $root_id ) . ' .timeline-item::after {
                        left: 20px;
                    }
                    #' . esc_attr( $root_id ) . ' .left, #' . esc_attr( $root_id ) . ' .right {
                        left: 0;
                    }
                }
            </style>';

            // Render the timeline container and timeline items.
            echo '<div  class="timeline-container">';
                if ( ! empty( $timeline_points ) ) {
                    $i = 0;
                    foreach ( $timeline_points as $point ) {
                        $side_class = ( $i % 2 === 0 ) ? 'left' : 'right';
                        $point_content = isset( $point['point_content'] ) ? $this->ensure_string( $point['point_content'] ) : '';

                        echo '<div class="timeline-item ' . esc_attr( $side_class ) . '">';
                            echo '<div class="content">';
                                echo wp_kses_post( $point_content );
                            echo '</div>';
                        echo '</div>';
                        $i++;
                    }
                }
            echo '</div>';
        echo '</div>';
    }
}
?>
