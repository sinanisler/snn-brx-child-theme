<?php
if ( ! defined( 'ABSPATH' ) ) exit;
use Bricks\Element;

class Snn_Image_Hotspots extends Element {
    public $category      = 'snn';
    public $name          = 'image-hotspots';
    public $icon          = 'ti-location-pin';
    public $css_selector  = '.snn-image-hotspots-wrapper';
    public $scripts       = [];
    public $nestable      = false;

    public function get_label() {
        return esc_html__( 'Image Hotspots', 'snn' );
    }

    public function set_controls() {
        // Main image selector
        $this->controls['main_image'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Image', 'snn' ),
            'type'  => 'image',
        ];

        // Hotspot repeater
        $this->controls['hotspots'] = [
            'tab'           => 'content',
            'label'         => esc_html__( 'Hotspots', 'snn' ),
            'type'          => 'repeater',
            'titleProperty' => 'tooltip',
            'fields'        => [
                'tooltip' => [
                    'label'         => esc_html__( 'Tooltip Text', 'snn' ),
                    'type'          => 'text',
                    'default'       => 'Hotspot',
                    'inlineEditing' => true,
                ],
                'x' => [
                    'label'   => esc_html__( 'X (%)', 'snn' ),
                    'type'    => 'slider',
                    'units'   => [
                        'px' => [ 'min' => 0, 'max' => 100, 'step' => 0.1 ],
                    ],
                    'default' => '50%',
                ],
                'y' => [
                    'label'   => esc_html__( 'Y (%)', 'snn' ),
                    'type'    => 'slider',
                    'units'   => [
                        'px' => [ 'min' => 0, 'max' => 100, 'step' => 0.1 ],
                    ],
                    'default' => '50%',
                ],
                'dot_size' => [
                    'label'   => esc_html__( 'Dot Size (px)', 'snn' ),
                    'type'    => 'number',
                    'default' => 20,
                    'min'     => 8,
                    'max'     => 100,
                    'step'    => 1,
                    'inline'  => true,
                ],
                'dot_color' => [
                    'label'   => esc_html__( 'Dot Color', 'snn' ),
                    'type'    => 'color',
                    'default' => '#ccc',
                ],
                'tooltip_pos' => [
                    'label'   => esc_html__( 'Tooltip Position', 'snn' ),
                    'type'    => 'select',
                    'options' => [
                        'top'    => esc_html__( 'Top', 'snn' ),
                        'right'  => esc_html__( 'Right', 'snn' ),
                        'bottom' => esc_html__( 'Bottom', 'snn' ),
                        'left'   => esc_html__( 'Left', 'snn' ),
                    ],
                    'default' => 'top',
                ],
            ],
        ];
    }

    public function render() {
        $main_image = isset( $this->settings['main_image'] ) ? $this->settings['main_image'] : false;
        $hotspots   = isset( $this->settings['hotspots'] ) ? $this->settings['hotspots'] : [];

        $unique = 'image-hotspots-' . uniqid();
        $this->set_attribute( '_root', 'class', [ 'snn-image-hotspots-wrapper', $unique ] );
        $this->set_attribute( '_root', 'style', 'position: relative; width: 100%; display: inline-block;' );

        echo '<div ' . $this->render_attributes( '_root' ) . '>';

        // Render the image
        if ( $main_image && isset( $main_image['id'] ) ) {
            echo wp_get_attachment_image( $main_image['id'], isset( $main_image['size'] ) ? $main_image['size'] : 'full', false, [
                'style' => 'width:100%; height:auto; display:block;',
                'class' => 'snn-hotspot-image',
            ] );
        } else {
            echo '<div style="width:100%;min-height:300px;background:#f3f3f3;text-align:center;line-height:300px;">No Image Selected</div>';
        }

        // CSS for hotspots and our new tooltip
        echo '<style>
            .' . $unique . ' .hotspot-dot {
                cursor: pointer;
                position: absolute;
                z-index: 10;
                transition: transform 0.2s;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                outline: none;
                border: none;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .' . $unique . ' .hotspot-dot:hover,
            .' . $unique . ' .hotspot-dot:focus {
                z-index: 20;
                transform: translate(-50%,-50%) scale(1.15);
            }

            /* Custom Tooltip Implementation (snn-balloon) */
            .' . $unique . ' .hotspot-dot[data-snn-tooltip]:after {
                content: attr(data-snn-tooltip);
                position: absolute;
                background: #333;
                color: #fff;
                padding: 6px 12px;
                border-radius: 5px;
                font-size: 14px;
                line-height: 1.4;
                white-space: nowrap;
                z-index: 100;
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
                transition: opacity 0.3s, visibility 0.3s;
            }

            .' . $unique . ' .hotspot-dot:hover:after,
            .' . $unique . ' .hotspot-dot:focus:after {
                opacity: 1;
                visibility: visible;
            }

            /* Tooltip Positioning */
            .' . $unique . ' .hotspot-dot[data-snn-tooltip-pos="top"]:after {
                bottom: calc(100% + 8px);
                left: 50%;
                transform: translateX(-50%);
            }
            .' . $unique . ' .hotspot-dot[data-snn-tooltip-pos="bottom"]:after {
                top: calc(100% + 8px);
                left: 50%;
                transform: translateX(-50%);
            }
            .' . $unique . ' .hotspot-dot[data-snn-tooltip-pos="left"]:after {
                right: calc(100% + 8px);
                top: 50%;
                transform: translateY(-50%);
            }
            .' . $unique . ' .hotspot-dot[data-snn-tooltip-pos="right"]:after {
                left: calc(100% + 8px);
                top: 50%;
                transform: translateY(-50%);
            }
        </style>';

        // Hotspots HTML
        foreach ( $hotspots as $i => $hotspot ) {
            // --- Parse X/Y value ---
            $x = 50;
            if ( isset( $hotspot['x'] ) ) {
                $x = is_array( $hotspot['x'] ) ? floatval( $hotspot['x']['value'] ) : floatval( $hotspot['x'] );
            }
            $y = 50;
            if ( isset( $hotspot['y'] ) ) {
                $y = is_array( $hotspot['y'] ) ? floatval( $hotspot['y']['value'] ) : floatval( $hotspot['y'] );
            }
            $dot_size = isset( $hotspot['dot_size'] ) ? intval( $hotspot['dot_size'] ) : 20;

            // --- Color Robust Parsing ---
            $dot_color = 'var(--septenary-color)'; // Set default
            if ( ! empty( $hotspot['dot_color'] ) ) {
                $c = $hotspot['dot_color'];
                if ( is_array( $c ) ) {
                    // Prioritize 'raw' for CSS variables, fallback to 'hex'
                    if ( isset( $c['raw'] ) ) {
                        $dot_color = $c['raw'];
                    } elseif ( isset( $c['hex'] ) ) {
                        $dot_color = $c['hex'];
                    }
                } else {
                    // Handle string values (like our default)
                    $dot_color = $c;
                }
            }

            $tooltip     = isset( $hotspot['tooltip'] ) ? esc_attr( $hotspot['tooltip'] ) : '';
            $tooltip_pos = isset( $hotspot['tooltip_pos'] ) ? esc_attr( $hotspot['tooltip_pos'] ) : 'top';

            $dot_id    = $unique . '-dot-' . $i;
            $dot_style = 'left:' . $x . '%; top:' . $y . '%; width:' . $dot_size . 'px; height:' . $dot_size . 'px; background:' . $dot_color . '; border-radius: 50%; transform:translate(-50%,-50%);';

            echo '<div
                tabindex="0"
                id="' . esc_attr( $dot_id ) . '"
                class="hotspot-dot"
                role="tooltip"
                style="' . esc_attr( $dot_style ) . '"
                data-snn-tooltip="' . esc_attr( $tooltip ) . '"
                data-snn-tooltip-pos="' . esc_attr( $tooltip_pos ) . '"
            ></div>';
        }

        echo '</div>';
    }
}