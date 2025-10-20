<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Custom_Element_OpenStreetMap extends \Bricks\Element {

    public $category     = 'snn';
    public $name         = 'openstreetmap';
    public $icon         = 'fas fa-map';
    public $css_selector = '.custom-openstreetmap-wrapper';

    public function get_label() {
        return 'OpenStreetMap';
    }

    public function set_controls() {

        // Control for Enabling/Disabling Scroll Wheel Zoom
        $this->controls['enable_scroll_zoom'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Enable Scroll Zoom', 'snn' ),
            'type'    => 'checkbox',
            'inline'  => true,
            'small'   => true,
            'default' => false,
        ];

        // Marker controls
        $this->controls['markers'] = [
            'tab'           => 'content',
            'label'         => 'Location',
            'type'          => 'repeater',
            'titleProperty' => 'Location',
            'default'       => [],
            'fields'        => [
                'lat' => [
                    'label'   => __( 'Latitude', 'snn' ),
                    'type'    => 'number',
                    'default' => 51.5238,
                    'step'    => 0.0001,
                    'min'     => -90,
                    'max'     => 90,
                ],
                'lng' => [
                    'label'   => __( 'Longitude', 'snn' ),
                    'type'    => 'number',
                    'default' => -0.1583,
                    'step'    => 0.0001,
                    'min'     => -180,
                    'max'     => 180,
                ],
                'popup' => [
                    'label'   => __( 'Popup Text', 'snn' ),
                    'type'    => 'editor',
                    'default' => 'Marker Popup Text',
                ],
                'icon' => [
                    'label'   => __( 'Icon', 'snn' ),
                    'type'    => 'icon',
                    'default' => [
                        'library' => 'fontawesome',
                        'icon'    => 'fa-map-marker-alt',
                    ],
                ],
                'icon_size' => [
                    'label'   => __( 'Icon Size (px)', 'snn' ),
                    'type'    => 'number',
                    'default' => 24,
                    'step'    => 1,
                    'min'     => 10,
                    'inline'  => true,
                ],
                'icon_color' => [
                    'label'   => __( 'Icon Color', 'snn' ),
                    'type'    => 'color',
                    'default' => [
                        'hex'  => '#000000',
                        'rgba' => 'rgba(0,0,0,1)',
                    ],
                    'inline'  => true,
                ],
            ],
        ];

        $this->controls['map_center_lat'] = [
            'tab'     => 'content',
            'label'   => __( 'Map Center Latitude', 'snn' ),
            'type'    => 'number',
            'default' => 51.5238,
            'step'    => 0.0001,
            'min'     => -90,
            'max'     => 90,
        ];

        $this->controls['map_center_lng'] = [
            'tab'     => 'content',
            'label'   => __( 'Map Center Longitude', 'snn' ),
            'type'    => 'number',
            'default' => -0.1583,
            'step'    => 0.0001,
            'min'     => -180,
            'max'     => 180,
        ];

        $this->controls['zoom_level'] = [
            'tab'     => 'content',
            'label'   => __( 'Zoom Level', 'snn' ),
            'type'    => 'number',
            'default' => 18,
            'min'     => 1,
            'max'     => 20,
            'step'    => 1,
        ];

        $this->controls['map_height'] = [
            'tab'     => 'content',
            'label'   => __( 'Map Height (px)', 'snn' ),
            'type'    => 'number',
            'default' => 400,
            'min'     => 100,
            'step'    => 10,
        ];

        $this->controls['popup_font_size'] = [
            'tab'    => 'content',
            'label'  => __( 'Popup Font Size (px)', 'snn' ),
            'type'   => 'number',
            'min'    => 10,
            'step'   => 1,
            'default'=> 14,
            'inline' => true,
        ];

        $this->controls['map_style'] = [
            'tab'     => 'content',
            'label'   => __( 'Map Style', 'snn' ),
            'type'    => 'select',
            'options' => [
                'default' => 'Default (OSM Free Tiles)',
                'light'   => 'Light (Fastly Free Tiles)',
                'dark'    => 'Dark (Fastly Free Tiles)',
            ],
            'default' => 'default',
        ];

        // New control: CSS filters for the .leaflet-pane element
        $this->controls['leaflet_pane_filters'] = [
            'tab'    => 'content',
            'label'  => esc_html__( 'Leaflet Pane Filters', 'snn' ),
            'type'   => 'filters',
            'inline' => true,
            'css'    => [
                [
                    'property' => 'filter',
                    'selector' => '.leaflet-tile-container',
                ],
            ],
        ];
    }

    public function enqueue_scripts() {
        wp_enqueue_style( 'leaflet-css', SNN_URL_ASSETS . 'css/leaflet.css', [], '1.9.4' );
        wp_enqueue_script( 'leaflet-js', SNN_URL_ASSETS . 'js/leaflet.js', [], '1.9.4', true );
    }

    public function render() {
        $enable_scroll_zoom = isset( $this->settings['enable_scroll_zoom'] ) ? (bool) $this->settings['enable_scroll_zoom'] : false;
        $map_center_lat     = isset( $this->settings['map_center_lat'] ) ? floatval( $this->settings['map_center_lat'] ) : 51.5;
        $map_center_lng     = isset( $this->settings['map_center_lng'] ) ? floatval( $this->settings['map_center_lng'] ) : -0.09;
        $zoom_level         = isset( $this->settings['zoom_level'] ) ? intval( $this->settings['zoom_level'] ) : 13;
        $markers            = isset( $this->settings['markers'] ) ? $this->settings['markers'] : [];
        $map_height         = isset( $this->settings['map_height'] ) ? intval( $this->settings['map_height'] ) : 400;
        $popup_font_size    = isset( $this->settings['popup_font_size'] ) ? intval( $this->settings['popup_font_size'] ) : 14;
        $map_style          = isset( $this->settings['map_style'] ) ? $this->settings['map_style'] : 'default';

        // Set up the root attributes using Bricks methods.
        // Here we add our wrapper class and inline style.
        $root_classes = ['custom-openstreetmap-wrapper'];
        $this->set_attribute('_root', 'class', $root_classes);
        $this->set_attribute('_root', 'style', "height: {$map_height}px; width: 100%; max-width: 100%;");

        // Check if an ID is already set on the element’s root.
        // If not, generate one.
        if ( isset( $this->attributes['_root']['id'] ) && ! empty( $this->attributes['_root']['id'] ) ) {
            $map_id = $this->attributes['_root']['id'];
        } else {
            $map_id = 'custom-openstreetmap-' . uniqid();
            $this->set_attribute('_root', 'id', $map_id);
        }

        // Inline CSS for popup font size and other Leaflet styling
        $popup_font_size_css = '';
        if ( ! empty( $popup_font_size ) ) {
            $popup_font_size_css = "
                <style>
                    #{$map_id} .custom-openstreetmap-popup {
                        font-size: {$popup_font_size}px;
                    }
                    .leaflet-icon-custom {
                        display: flex;
                    }
                    .leaflet-icon-custom svg {
                        width: 100% !important;
                        height:auto ;
                    }
                    .leaflet-marker-icon {
                        height: auto !important;
                    }
                    .leaflet-container a.leaflet-popup-close-button {
                        font-size: 20px !important;
                    }
                    .leaflet-control-attribution {
                        font-size: 11px;
                        color: gray !important;
                    }
                    .leaflet-control-attribution a,
                    .leaflet-control-attribution span {
                        display: none;
                    }
                    .leaflet-top, .leaflet-bottom {
                        z-index: 500 !important;
                    }
                </style>
            ";
        }

        $tile_url = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
        if ( $map_style === 'light' ) {
            $tile_url = 'https://cartodb-basemaps-c.global.ssl.fastly.net/light_all/{z}/{x}/{y}.png';
        } elseif ( $map_style === 'dark' ) {
            $tile_url = 'https://cartodb-basemaps-c.global.ssl.fastly.net/dark_all/{z}/{x}/{y}.png';
        }
        $tile_attribution = '©OpenStreetMap';
        ?>

        <?php echo $popup_font_size_css; ?>

        <div <?php echo $this->render_attributes('_root'); ?>>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize map with scrollWheelZoom option based on user setting
            var map = L.map('<?php echo esc_js( $map_id ); ?>', {
                scrollWheelZoom: <?php echo $enable_scroll_zoom ? 'true' : 'false'; ?>
            }).setView(
                [<?php echo esc_js( $map_center_lat ); ?>, <?php echo esc_js( $map_center_lng ); ?>],
                <?php echo esc_js( $zoom_level ); ?>
            );

            // Add tile layer
            L.tileLayer('<?php echo esc_js( $tile_url ); ?>', {
                attribution: '<?php echo esc_js( $tile_attribution ); ?>'
            }).addTo(map);

            // Helper to create a Leaflet DivIcon using the provided icon HTML and color
            function createIcon(iconHtml, size, color, label) {
                var styledIconHtml = '<div class="leaflet-icon-custom" aria-label="' + label + '" style="font-size:' + size + 'px; color:' + color + '; line-height:1;">' + iconHtml + '</div>';
                return L.divIcon({
                    html: styledIconHtml,
                    iconSize: [size + 10, size + 10],
                    className: 'custom-div-icon'
                });
            }

            // Add markers
            <?php foreach ( $markers as $index => $marker ) :
                $lat   = isset( $marker['lat'] ) ? floatval( $marker['lat'] ) : 0;
                $lng   = isset( $marker['lng'] ) ? floatval( $marker['lng'] ) : 0;
                $popup = isset( $marker['popup'] ) ? $marker['popup'] : '';

                // Icon size
                $icon_size = isset( $marker['icon_size'] ) ? intval( $marker['icon_size'] ) : 24;

                // Determine the proper icon color value
                $icon_color = '';
                if ( is_array( $marker['icon_color'] ) ) {
                    // Check for CSS variable first
                    if ( !empty( $marker['icon_color']['css'] ) ) {
                        $icon_color = $marker['icon_color']['css'];
                    }
                    // Then check for rgba
                    elseif ( !empty( $marker['icon_color']['rgba'] ) ) {
                        $icon_color = $marker['icon_color']['rgba'];
                    }
                    // Then check for hex
                    elseif ( !empty( $marker['icon_color']['hex'] ) ) {
                        $icon_color = $marker['icon_color']['hex'];
                    }
                    // Fallback to raw if present
                    elseif ( !empty( $marker['icon_color']['raw'] ) ) {
                        $icon_color = $marker['icon_color']['raw'];
                    }
                } else {
                    $icon_color = $marker['icon_color'];
                }

                // Render the icon using $this->render_icon()
                ob_start();
                echo $this->render_icon( $marker['icon'] );
                $icon_html = ob_get_clean();

                // Escape icon HTML and popup for use in JS
                $icon_html_escaped  = str_replace( "'", "\\'", $icon_html );
                $popup_escaped      = str_replace( "'", "\\'", $popup );

                // Set ARIA label for the icon (using the popup text if available)
                $icon_label = !empty( $popup ) ? wp_strip_all_tags( $popup ) : 'Map marker';
                $icon_label_escaped = str_replace( "'", "\\'", $icon_label );
            ?>

            // Create marker
            var marker<?php echo $index; ?> = L.marker(
                [<?php echo $lat; ?>, <?php echo $lng; ?>],
                {
                    icon: createIcon(
                        '<?php echo $icon_html_escaped; ?>',
                        <?php echo $icon_size; ?>,
                        '<?php echo $icon_color; ?>',
                        '<?php echo str_replace(array("\r", "\n"), '', $icon_label_escaped); ?>'
                    )
                }
            ).bindPopup(
                '<div class="custom-openstreetmap-popup"><?php echo str_replace(array("\r", "\n"), '', $popup_escaped); ?></div>'
            ).addTo(map);

            <?php endforeach; ?>
        });
        </script>
        <?php
    }

    public static function render_builder() {
        ?>
        <script type="text/x-template" id="tmpl-bricks-element-openstreetmap">
            <div class="custom-openstreetmap-wrapper" style="height: 400px; background: #eaeaea; display: flex; align-items: center; justify-content: center;">
                <span style="color: #555;">OpenStreetMap Placeholder</span>
            </div>
        </script>
        <?php
    }
}

add_action( 'bricks_register_elements', function() {
    \Bricks\Element::register_element( 'Custom_Element_OpenStreetMap', 'openstreetmap' );
} );
?>
