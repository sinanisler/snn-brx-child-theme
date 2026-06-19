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

        // Hover/Click toggle
        $this->controls['hover_click_toggle'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Hover/Click', 'snn' ),
            'type'    => 'checkbox',
            'inline'  => true,
            'small'   => true,
            'default' => false,
        ];

        // Global dot controls
        $this->controls['dot_size'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Dot Size (px)', 'snn' ),
            'type'  => 'number',
            'default' => 20,
            'min'    => 8,
            'max'    => 100,
            'step'   => 1,
        ];
        $this->controls['dot_border_radius'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Dot Radius (%)', 'snn' ),
            'type'  => 'number',
            'units' => [
                '%' => [ 'min' => 0, 'max' => 100, 'step' => 1 ],
            ],
            'default' => '50%',
        ];
        $this->controls['dot_color'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Dot Color', 'snn' ),
            'type'  => 'color',
            'default' => '#333',
        ];

        // Hotspot repeater (without dot size, radius, color)
        $this->controls['hotspots'] = [
            'tab'           => 'content',
            'label'         => esc_html__( 'Hotspots', 'snn' ),
            'type'          => 'repeater',
            'titleProperty' => 'pin',
            'fields'        => [
                'tooltip' => [
                    'label'         => esc_html__( 'Tooltip Content', 'snn' ),
                    'type'          => 'editor', // Allows HTML
                    'default'       => 'Hotspot',
                    'titleProperty' => 'label',
                    'inlineEditing' => true,
                ],
                'x' => [
                    'label'   => esc_html__( 'X (%)', 'snn' ),
                    'type'    => 'slider',
                    'units'   => [
                        '%' => [ 'min' => 0, 'max' => 100, 'step' => 0.1 ],
                    ],
                    'default' => '50%',
                ],
                'y' => [
                    'label'   => esc_html__( 'Y (%)', 'snn' ),
                    'type'    => 'slider',
                    'units'   => [
                        '%' => [ 'min' => 0, 'max' => 100, 'step' => 0.1 ],
                    ],
                    'default' => '50%',
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
                'tooltip_bg_color' => [
                    'label'   => esc_html__( 'Tooltip Background', 'snn' ),
                    'type'    => 'color',
                    'default' => '#333',
                ],
                'tooltip_text_color' => [
                    'label'   => esc_html__( 'Tooltip Text Color', 'snn' ),
                    'type'    => 'color',
                    'default' => '#fff',
                ],
                'tooltip_width' => [
                    'label'   => esc_html__( 'Tooltip Width (px)', 'snn' ),
                    'type'    => 'number',
                    'default' => 200,
                    'placeholder' => 200,
                    'step'    => 1,
                    'inline'  => true,
                ],
                'tooltip_border_radius' => [
                    'label'   => esc_html__( 'Tooltip Border Radius (px)', 'snn' ),
                    'type'    => 'number',
                    'default' => 5,
                    'min'     => 0,
                    'max'     => 50,
                    'step'    => 1,
                    'inline'  => true,
                ],
                'icon' => [
                    'label' => esc_html__( 'Icon', 'snn' ),
                    'type'  => 'icon',
                ],
                'icon_size' => [
                    'label'   => esc_html__( 'Icon Size (px)', 'snn' ),
                    'type'    => 'number',
                    'default' => 16,
                    'min'     => 8,
                    'max'     => 80,
                    'step'    => 1,
                    'inline'  => true,
                ],
                'icon_color' => [
                    'label'   => esc_html__( 'Icon Color', 'snn' ),
                    'type'    => 'color',
                    'default' => '#fff',
                ],
            ],
        ];
    }

    public function render() {
        $main_image = isset( $this->settings['main_image'] ) ? $this->settings['main_image'] : false;
        $hotspots   = isset( $this->settings['hotspots'] ) ? $this->settings['hotspots'] : [];
        // Global dot settings
        $dot_size = isset($this->settings['dot_size']) ? intval($this->settings['dot_size']) : 20;
        $dot_border_radius = '50%';
        if (isset($this->settings['dot_border_radius'])) {
            $br = $this->settings['dot_border_radius'];
            $dot_border_radius = (is_array($br) ? $br['value'] : $br) . '%';
        }
        $dot_color = '#333';
        if (isset($this->settings['dot_color'])) {
            $c = $this->settings['dot_color'];
            if (is_array($c)) {
                $dot_color = isset($c['raw']) ? $c['raw'] : (isset($c['hex']) ? $c['hex'] : '#333');
            } else {
                $dot_color = $c;
            }
        }

        // Click mode (Hover/Click toggle)
        $click_mode = isset( $this->settings['hover_click_toggle'] ) ? true : false;
        if ( $click_mode ) {
            $this->set_attribute( '_root', 'data-snn-click-mode', 'true' );
        }

        $unique = 'image-hotspots-' . uniqid();
        $this->set_attribute( '_root', 'class', [ 'snn-image-hotspots-wrapper', $unique ] );
        // $this->set_attribute( '_root', 'style', 'position: relative; width: 100%; display: inline-block;' );

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

        // --- Base CSS for hotspots and tooltips ---
        echo '<style>
            .' . $unique . ' {
                position: relative; 
                width: 100%; 
                display: inline-block;
            }
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

            /* Custom Tooltip Base Styles */
            .' . $unique . ' .snn-tooltip-content {
                position: absolute;
                padding: 22px 16px 22px 16px;
                border-radius: 5px;
                font-size: 14px;
                line-height: 1.5;
                white-space: normal;
                text-align: center;
                z-index: 100;
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
                transition: opacity 0.3s, visibility 0.3s;
            }

            .' . $unique . ' .hotspot-dot:hover .snn-tooltip-content,
            .' . $unique . ' .hotspot-dot:focus .snn-tooltip-content {
                opacity: 1;
                visibility: visible;
            }

            /* Click mode: disable hover, enable click-to-toggle */
            .' . $unique . '[data-snn-click-mode="true"] .hotspot-dot:hover .snn-tooltip-content,
            .' . $unique . '[data-snn-click-mode="true"] .hotspot-dot:focus .snn-tooltip-content {
                opacity: 0;
                visibility: hidden;
            }
            .' . $unique . '[data-snn-click-mode="true"] .hotspot-dot.snn-active .snn-tooltip-content {
                opacity: 1;
                visibility: visible;
                pointer-events: auto;
            }

            /* Close button inside tooltip */
            .' . $unique . ' .snn-tooltip-close {
                position: absolute;
                top: 2px;
                right: 4px;
                width: 16px;
                height: 16px;
                padding: 0;
                border: none;
                background: transparent;
                color: inherit;
                font-size: 22px;
                font-weight:bold;
                line-height: 1;
                cursor: pointer;
                opacity: 0.6;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                transition: opacity 0.2s;
            }
            .' . $unique . ' .snn-tooltip-close:hover {
                opacity: 1;
                background: rgba(255,255,255,0.15);
            }

            /* Tooltip Positioning */
            .' . $unique . ' .hotspot-dot[data-snn-tooltip-pos="top"] .snn-tooltip-content {
                bottom: calc(100% + 8px);
                left: 50%;
                transform: translateX(-50%);
            }
            .' . $unique . ' .hotspot-dot[data-snn-tooltip-pos="bottom"] .snn-tooltip-content {
                top: calc(100% + 8px);
                left: 50%;
                transform: translateX(-50%);
            }
            .' . $unique . ' .hotspot-dot[data-snn-tooltip-pos="left"] .snn-tooltip-content {
                right: calc(100% + 8px);
                top: 50%;
                transform: translateY(-50%);
            }
            .' . $unique . ' .hotspot-dot[data-snn-tooltip-pos="right"] .snn-tooltip-content {
                left: calc(100% + 8px);
                top: 50%;
                transform: translateY(-50%);
            }
        </style>';

        // --- Per-Hotspot CSS and HTML ---
        $dynamic_styles = '';
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

            // --- Tooltip Color Parsing ---
            $parse_color = function( $color_setting, $default_color ) {
                if ( ! empty( $color_setting ) ) {
                    if ( is_array( $color_setting ) ) {
                        return isset( $color_setting['raw'] ) ? $color_setting['raw'] : (isset($color_setting['hex']) ? $color_setting['hex'] : $default_color);
                    }
                    return $color_setting;
                }
                return $default_color;
            };

            $tooltip_bg_color   = $parse_color( isset($hotspot['tooltip_bg_color']) ? $hotspot['tooltip_bg_color'] : null, '#333' );
            $tooltip_text_color = $parse_color( isset($hotspot['tooltip_text_color']) ? $hotspot['tooltip_text_color'] : null, '#fff' );

            $tooltip_content    = isset( $hotspot['tooltip'] ) ? $hotspot['tooltip'] : '';
            $tooltip_pos        = isset( $hotspot['tooltip_pos'] ) ? esc_attr( $hotspot['tooltip_pos'] ) : 'top';
            $tooltip_width      = isset( $hotspot['tooltip_width'] ) ? intval( $hotspot['tooltip_width'] ) : 0;
            $tooltip_border_radius = isset( $hotspot['tooltip_border_radius'] ) ? intval( $hotspot['tooltip_border_radius'] ) : 5;

            // --- Icon Parsing ---
            $icon_settings = isset( $hotspot['icon'] ) ? $hotspot['icon'] : null;
            $has_icon      = ! empty( $icon_settings ) && ( ( is_array( $icon_settings ) && ! empty( $icon_settings['icon'] ) ) || ( is_string( $icon_settings ) && ! empty( $icon_settings ) ) );
            $icon_size_val = isset( $hotspot['icon_size'] ) ? intval( $hotspot['icon_size'] ) : 16;
            $icon_color_val = $parse_color( isset( $hotspot['icon_color'] ) ? $hotspot['icon_color'] : null, '#fff' );

            $dot_id    = $unique . '-dot-' . $i;
            $dot_style = 'left:' . $x . '%; top:' . $y . '%; width:' . $dot_size . 'px; height:' . $dot_size . 'px; background:' . $dot_color . '; border-radius:' . $dot_border_radius . '; transform:translate(-50%,-50%);';

            // --- Tooltip Styles ---
            $tooltip_inline_styles = "background: {$tooltip_bg_color}; color: {$tooltip_text_color}; border-radius: {$tooltip_border_radius}px;";
            if ( $tooltip_width > 0 ) {
                $tooltip_inline_styles .= " width: {$tooltip_width}px;";
            }

            // Append tooltip color/width styles for this specific dot's tooltip
            $dynamic_styles .= "
                #{$dot_id} .snn-tooltip-content {
                    {$tooltip_inline_styles}
                }
            ";

            // --- Render Dot HTML ---
            echo '<div
                tabindex="0"
                id="' . esc_attr( $dot_id ) . '"
                class="hotspot-dot"
                role="button"
                aria-describedby="tooltip-content-' . esc_attr( $dot_id ) . '"
                style="' . esc_attr( $dot_style ) . '"
                data-snn-tooltip-pos="' . esc_attr( $tooltip_pos ) . '"
            >';
                // Render icon inside the dot if set
                if ( $has_icon ) {
                    echo '<span class="hotspot-icon" style="font-size:' . $icon_size_val . 'px; color:' . esc_attr( $icon_color_val ) . '; line-height:1; display:flex; align-items:center; justify-content:center;">';
                    if ( class_exists( '\\Bricks\\Helpers' ) && method_exists( '\\Bricks\\Helpers', 'render_control_icon' ) ) {
                        \Bricks\Helpers::render_control_icon( $icon_settings, [] );
                    } else {
                        // Fallback: manually render the icon
                        if ( is_array( $icon_settings ) && isset( $icon_settings['icon'] ) ) {
                            echo '<i class="' . esc_attr( $icon_settings['icon'] ) . '"></i>';
                        }
                    }
                    echo '</span>';
                }
                // The actual tooltip element which can contain HTML
                echo '<div class="snn-tooltip-content" role="tooltip" id="tooltip-content-' . esc_attr( $dot_id ) . '"><button class="snn-tooltip-close" aria-label="' . esc_attr__( 'Close', 'snn' ) . '" type="button">&times;</button>' . $tooltip_content . '</div>';
            echo '</div>';
        }
        
        // Output dynamic styles if any exist
        if ( ! empty( $dynamic_styles ) ) {
            echo '<style>' . $dynamic_styles . '</style>';
        }

        // Click-mode JavaScript
        if ( $click_mode ) {
            echo '<script>
                (function() {
                    var wrapper = document.querySelector(".' . $unique . '");
                    if (!wrapper) return;
                    var dots = wrapper.querySelectorAll(".hotspot-dot");
                    var closeButtons = wrapper.querySelectorAll(".snn-tooltip-close");

                    function closeAll() {
                        dots.forEach(function(d) { d.classList.remove("snn-active"); });
                    }

                    dots.forEach(function(dot) {
                        dot.addEventListener("click", function(e) {
                            e.stopPropagation();
                            var wasActive = dot.classList.contains("snn-active");
                            closeAll();
                            if (!wasActive) {
                                dot.classList.add("snn-active");
                            }
                        });
                    });

                    closeButtons.forEach(function(btn) {
                        btn.addEventListener("click", function(e) {
                            e.stopPropagation();
                            closeAll();
                        });
                    });

                    document.addEventListener("click", function(e) {
                        if (!wrapper.contains(e.target)) {
                            closeAll();
                        }
                    });
                })();
            </script>';
        }

        echo '</div>';
    }
}