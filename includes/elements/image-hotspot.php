<?php
if ( ! defined( 'ABSPATH' ) ) exit;
use Bricks\Element;

class Snn_Image_Hotspots extends Element {
    public $category     = 'snn';
    public $name         = 'image-hotspots';
    public $icon         = 'ti-location-pin';
    public $css_selector = '.snn-image-hotspots-wrapper';
    public $scripts      = [];
    public $nestable     = false;

    public function get_label() {
        return esc_html__( 'Image Hotspots', 'snn' );
    }

    public function set_controls() {
        // Main image selector
        $this->controls['main_image'] = [
            'tab' => 'content',
            'label' => esc_html__( 'Image', 'snn' ),
            'type' => 'image',
        ];

        // Hotspot repeater
        $this->controls['hotspots'] = [
            'tab' => 'content',
            'label' => esc_html__( 'Hotspots', 'snn' ),
            'type' => 'repeater',
            'titleProperty' => 'tooltip',
            'fields' => [
                'x' => [
                    'label' => esc_html__( 'X (%)', 'snn' ),
                    'type' => 'slider',
                    'units' => [
                        '%' => [ 'min'=>0, 'max'=>100, 'step'=>0.1 ],
                    ],
                    'default' => '50%',
                    'inline' => true,
                ],
                'y' => [
                    'label' => esc_html__( 'Y (%)', 'snn' ),
                    'type' => 'slider',
                    'units' => [
                        '%' => [ 'min'=>0, 'max'=>100, 'step'=>0.1 ],
                    ],
                    'default' => '50%',
                    'inline' => true,
                ],
                'dot_size' => [
                    'label' => esc_html__( 'Dot Size (px)', 'snn' ),
                    'type' => 'number',
                    'default' => 20,
                    'min' => 8,
                    'max' => 100,
                    'step' => 1,
                    'inline' => true,
                ],
                'dot_color' => [
                    'label' => esc_html__( 'Dot Color', 'snn' ),
                    'type' => 'color',
                    'default' => [
                        'hex' => '#ffffff'
                    ],
                ],
                'tooltip' => [
                    'label' => esc_html__( 'Tooltip Text', 'snn' ),
                    'type' => 'text',
                    'default' => 'Hotspot',
                    'inlineEditing' => true,
                ],
                'tooltip_pos' => [
                    'label' => esc_html__( 'Tooltip Position', 'snn' ),
                    'type' => 'select',
                    'options' => [
                        'top' => 'top',
                        'right' => 'right',
                        'bottom' => 'bottom',
                        'left' => 'left',
                        'top-right' => 'top-right',
                        'top-left' => 'top-left',
                        'bottom-right' => 'bottom-right',
                        'bottom-left' => 'bottom-left',
                    ],
                    'default' => 'top',
                ],
                'popup_content' => [
                    'label' => esc_html__( 'Popup Content', 'snn' ),
                    'type' => 'editor',
                    'default' => '',
                ],
            ],
        ];
    }

    public function render() {
        $main_image = isset($this->settings['main_image']) ? $this->settings['main_image'] : false;
        $hotspots   = isset($this->settings['hotspots']) ? $this->settings['hotspots'] : [];

        $unique = 'image-hotspots-' . uniqid();
        $this->set_attribute( '_root', 'class', [ 'snn-image-hotspots-wrapper', $unique ] );
        $this->set_attribute( '_root', 'style', 'position: relative; width: 100%; display: inline-block;');

        echo '<div ' . $this->render_attributes('_root') . '>';

        // Render the image
        if ($main_image && isset($main_image['id'])) {
            echo wp_get_attachment_image($main_image['id'], isset($main_image['size']) ? $main_image['size'] : 'full', false, [
                'style' => 'width:100%; height:auto; display:block;',
                'class' => 'snn-hotspot-image',
            ]);
        } else {
            echo '<div style="width:100%;min-height:300px;background:#f3f3f3;text-align:center;line-height:300px;">No Image Selected</div>';
        }

        // CSS for hotspots and popups
        echo '<style>
            .' . $unique . ' .hotspot-dot {
                cursor: pointer;
                position: absolute;
                z-index: 10;
                transition: transform 0.1s;
                box-shadow: 0 2px 10px rgba(0,0,0,0.15);
                outline: none;
                border: none;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .' . $unique . ' .hotspot-dot:focus {
                z-index: 22;
                transform: scale(1.18);
            }
            .' . $unique . ' .hotspot-popup {
                display: none;
                position: absolute;
                min-width:220px;
                max-width:300px;
                background: #fff;
                color: #222;
                border-radius: 7px;
                border: 1px solid #dedede;
                padding: 18px 24px;
                box-shadow: 0 10px 34px 4px rgba(50,50,50,0.23);
                z-index: 50;
                top: 120%;
                left: 50%;
                transform: translateX(-50%);
                font-size: 15px;
                line-height: 1.5;
                text-align: left;
                pointer-events: none;
            }
            .' . $unique . ' .hotspot-dot.active + .hotspot-popup {
                display: block;
                pointer-events: auto;
            }
        </style>';

        // Hotspots HTML
        foreach ($hotspots as $i => $hotspot) {
            // --- Parse X/Y value (slider returns "50%" or float) ---
            $x = 50;
            if (isset($hotspot['x'])) {
                if (is_array($hotspot['x']) && isset($hotspot['x']['value'])) {
                    $x = floatval($hotspot['x']['value']);
                } elseif (is_numeric($hotspot['x'])) {
                    $x = floatval($hotspot['x']);
                } elseif (is_string($hotspot['x'])) {
                    $x = floatval(str_replace('%', '', $hotspot['x']));
                }
            }
            $y = 50;
            if (isset($hotspot['y'])) {
                if (is_array($hotspot['y']) && isset($hotspot['y']['value'])) {
                    $y = floatval($hotspot['y']['value']);
                } elseif (is_numeric($hotspot['y'])) {
                    $y = floatval($hotspot['y']);
                } elseif (is_string($hotspot['y'])) {
                    $y = floatval(str_replace('%', '', $hotspot['y']));
                }
            }
            $dot_size = isset($hotspot['dot_size']) ? intval($hotspot['dot_size']) : 20;

            // --- Color Robust Parsing ---
            $dot_color = '#fff';
            if (!empty($hotspot['dot_color'])) {
                $c = $hotspot['dot_color'];
                // Accept various keys from Bricks color control
                if (is_array($c)) {
                    if (!empty($c['rgba']))        $dot_color = $c['rgba'];
                    elseif (!empty($c['hex']))     $dot_color = $c['hex'];
                    elseif (!empty($c['hsl']))     $dot_color = $c['hsl'];
                    elseif (!empty($c['css']))     $dot_color = $c['css'];
                    elseif (!empty($c['value']))   $dot_color = $c['value'];
                    elseif (!empty($c['var']))     $dot_color = $c['var'];
                } else {
                    $dot_color = $c;
                }
            }

            $tooltip  = isset($hotspot['tooltip']) ? esc_attr($hotspot['tooltip']) : '';
            $tooltip_pos = isset($hotspot['tooltip_pos']) ? esc_attr($hotspot['tooltip_pos']) : 'top';
            $popup_content = !empty($hotspot['popup_content']) ? $hotspot['popup_content'] : '';

            $dot_id = $unique . '-dot-' . $i;
            $dot_style = 'left:' . $x . '%; top:' . $y . '%; width:' . $dot_size . 'px; height:' . $dot_size . 'px; background:' . $dot_color . '; border-radius: 50%; transform:translate(-50%,-50%);';

            echo '<button
                type="button"
                tabindex="0"
                id="'. esc_attr($dot_id) .'"
                class="hotspot-dot"
                style="'. esc_attr($dot_style) .'"
                data-balloon="'. esc_attr($tooltip) .'"
                data-balloon-pos="'. esc_attr($tooltip_pos) .'"
                aria-haspopup="true"
                aria-expanded="false"
            ></button>';

            echo '<div class="hotspot-popup" aria-labelledby="'. esc_attr($dot_id) .'">' . $popup_content . '</div>';
        }

        // JS for popup toggle, tooltip already works on hover with Balloon.css
        ?>
        <script>
            (function(){
                var root = document.querySelector('.<?php echo esc_js($unique); ?>');
                if(!root) return;
                var dots = root.querySelectorAll('.hotspot-dot');
                dots.forEach(function(dot){
                    dot.addEventListener('click', function(e){
                        dots.forEach(function(other){ if(other!==dot){ other.classList.remove('active'); other.setAttribute('aria-expanded','false'); } });
                        if(dot.classList.contains('active')){
                            dot.classList.remove('active');
                            dot.setAttribute('aria-expanded','false');
                        } else {
                            dot.classList.add('active');
                            dot.setAttribute('aria-expanded','true');
                        }
                    });
                    document.addEventListener('click', function(ev){
                        if(!dot.contains(ev.target) && !dot.nextElementSibling.contains(ev.target)){
                            dot.classList.remove('active');
                            dot.setAttribute('aria-expanded','false');
                        }
                    });
                    dot.addEventListener('keydown', function(ev){
                        if(ev.key==='Escape'){ dot.classList.remove('active'); dot.setAttribute('aria-expanded','false'); }
                    });
                });
            })();
        </script>
        <?php

        echo '</div>';
    }
}

?>
