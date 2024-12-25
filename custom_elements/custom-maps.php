<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Custom_Element_OpenStreetMap extends \Bricks\Element {

    public $category     = 'general';
    public $name         = 'openstreetmap';
    public $icon         = 'fas fa-map';
    public $css_selector = '.custom-openstreetmap-wrapper';

    public function get_label() {
        return 'OpenStreetMap';
    }

    // Define your controls
    public function set_controls() {

        // Repeater for Markers
        $this->controls['markers'] = [
            'tab'           => 'content',
            'label'         => 'Location',
            'type'          => 'repeater',
            'titleProperty' => 'Location', 
            'default'       => [],
            'fields'        => [
                'lat' => [
                    'label'   => __( 'Latitude', 'bricks' ),
                    'type'    => 'number',
                    'default' => 51.5,
                    'step'    => 0.0001,
                    'min'     => -90,
                    'max'     => 90,
                ],
                'lng' => [
                    'label'   => __( 'Longitude', 'bricks' ),
                    'type'    => 'number',
                    'default' => -0.09,
                    'step'    => 0.0001,
                    'min'     => -180,
                    'max'     => 180,
                ],
                'popup' => [
                    'label'   => __( 'Popup Text', 'bricks' ),
                    'type'    => 'editor',
                    'default' => 'Marker Popup Text',
                ],
                'icon' => [
                    'label'   => __( 'Icon', 'bricks' ),
                    'type'    => 'icon',
                    'default' => [
                        'library' => 'fontawesome',
                        'icon'    => 'fa-map-marker-alt',
                    ],
                ],
                'icon_size' => [
                    'label'   => __( 'Icon Size (px)', 'bricks' ),
                    'type'    => 'number',
                    'default' => 24,
                    'step'    => 1,
                    'min'     => 10,
                    'inline'  => true,
                ],
                'icon_color' => [
                    'label'   => __( 'Icon Color', 'bricks' ),
                    'type'    => 'color',
                    'default' => [
                        'hex'  => '#000000',
                        'rgba' => 'rgba(0,0,0,1)',
                    ],
                    'inline'  => true,
                ],
            ],
        ];

        // Map Center Latitude
        $this->controls['map_center_lat'] = [
            'tab'     => 'content',
            'label'   => __( 'Map Center Latitude', 'bricks' ),
            'type'    => 'number',
            'default' => 51.5,
            'step'    => 0.0001,
            'min'     => -90,
            'max'     => 90,
        ];

        // Map Center Longitude
        $this->controls['map_center_lng'] = [
            'tab'     => 'content',
            'label'   => __( 'Map Center Longitude', 'bricks' ),
            'type'    => 'number',
            'default' => -0.09,
            'step'    => 0.0001,
            'min'     => -180,
            'max'     => 180,
        ];

        // Zoom Level
        $this->controls['zoom_level'] = [
            'tab'     => 'content',
            'label'   => __( 'Zoom Level', 'bricks' ),
            'type'    => 'number',
            'default' => 13,
            'min'     => 1,
            'max'     => 19,
            'step'    => 1,
        ];

        // Map Height
        $this->controls['map_height'] = [
            'tab'     => 'content',
            'label'   => __( 'Map Height (px)', 'bricks' ),
            'type'    => 'number',
            'default' => 400,
            'min'     => 100,
            'step'    => 10,
        ];

        // Popup Font Size
        $this->controls['popup_font_size'] = [
            'tab'    => 'content',
            'label'  => __( 'Popup Font Size (px)', 'bricks' ),
            'type'   => 'number',
            'min'    => 10,
            'step'   => 1,
            'default'=> 14,
            'inline' => true,
        ];

        // Map Style
        $this->controls['map_style'] = [
            'tab'     => 'content',
            'label'   => __( 'Map Style', 'bricks' ),
            'type'    => 'select',
            'options' => [
                'default'  => 'Default (OSM Free Tiles)',
                'light'    => 'Light (Fastly Free Tiles)',
                'dark'     => 'Dark (Fastly Free Tiles)',
                'topology' => 'Topology (OSM Free Tiles)',
            ],
            'default' => 'default',
        ];
    }

    // Enqueue Leaflet CSS/JS
    public function enqueue_scripts() {
        wp_enqueue_style( 'leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4' );
        wp_enqueue_script( 'leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true );
        // No FontAwesome or other icon library enqueues — let Bricks handle it.
    }

    public function render() {
        // Retrieve settings
        $map_center_lat  = isset( $this->settings['map_center_lat'] ) ? floatval( $this->settings['map_center_lat'] ) : 51.5;
        $map_center_lng  = isset( $this->settings['map_center_lng'] ) ? floatval( $this->settings['map_center_lng'] ) : -0.09;
        $zoom_level      = isset( $this->settings['zoom_level'] ) ? intval( $this->settings['zoom_level'] ) : 13;
        $markers         = isset( $this->settings['markers'] ) ? $this->settings['markers'] : [];
        $map_height      = isset( $this->settings['map_height'] ) ? intval( $this->settings['map_height'] ) : 400;
        $popup_font_size = isset( $this->settings['popup_font_size'] ) ? intval( $this->settings['popup_font_size'] ) : 14;
        $map_style       = isset( $this->settings['map_style'] ) ? $this->settings['map_style'] : 'default';

        // Generate unique map ID
        $map_id = 'custom-openstreetmap-' . uniqid();

        // Optional inline CSS for popup font size
        $popup_font_size_css = '';
        if ( ! empty( $popup_font_size ) ) {
            $popup_font_size_css = "
                <style>
                    #{$map_id} .custom-openstreetmap-popup {
                        font-size: {$popup_font_size}px;
                        
                    }
                    .leaflet-icon-custom{
                        display:flex;
                    }
                    .leaflet-icon-custom svg{
                        width:100% !important;
                    }
                    .leaflet-marker-icon {
                        height:auto !important;
                    }
                </style>
            ";
        }

        // Decide tile layer URL
        $tile_url = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'; // Default
        if ( $map_style === 'light' ) {
            $tile_url = 'https://cartodb-basemaps-c.global.ssl.fastly.net/light_all/{z}/{x}/{y}.png';
        } elseif ( $map_style === 'dark' ) {
            $tile_url = 'https://cartodb-basemaps-c.global.ssl.fastly.net/dark_all/{z}/{x}/{y}.png';
        } elseif ( $map_style === 'topology' ) {
            $tile_url = 'https://b.tile.opentopomap.org/{z}/{x}/{y}.png';
        }
        $tile_attribution = '©OpenStreetMap';
        ?>
        
        <!-- Map container -->
        <div id="<?php echo esc_attr( $map_id ); ?>" class="custom-openstreetmap-wrapper"
             style="height: <?php echo esc_attr( $map_height ); ?>px; width: 100%; max-width: 100%;">
        </div>
        
        <!-- Inject optional CSS for popup font size -->
        <?php echo $popup_font_size_css; ?>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize map
            var map = L.map('<?php echo esc_js( $map_id ); ?>').setView(
                [<?php echo esc_js( $map_center_lat ); ?>, <?php echo esc_js( $map_center_lng ); ?>],
                <?php echo esc_js( $zoom_level ); ?>
            );

            // Add tile layer
            L.tileLayer('<?php echo esc_js( $tile_url ); ?>', {
                attribution: '<?php echo esc_js( $tile_attribution ); ?>'
            }).addTo(map);

            // Helper to create a Leaflet DivIcon from an HTML string
            function createIcon(iconHtml, size, color) {
                var styledIconHtml = '<div class="leaflet-icon-custom" style="font-size:' + size + 'px; color:' + color + '; line-height:1;">'
                                   + iconHtml
                                   + '</div>';
                return L.divIcon({
                    html: styledIconHtml,
                    iconSize: [size + 10, size + 10],
                    className: 'custom-div-icon'
                });
            }

            // Add markers
            <?php foreach ( $markers as $index => $marker ) :
                $lat = isset( $marker['lat'] ) ? floatval( $marker['lat'] ) : 0;
                $lng = isset( $marker['lng'] ) ? floatval( $marker['lng'] ) : 0;
                $popup = isset( $marker['popup'] ) ? $marker['popup'] : '';
                
                // Icon size/color
                $icon_size = isset( $marker['icon_size'] ) ? intval( $marker['icon_size'] ) : 24;
                if ( isset( $marker['icon_color']['hex'] ) && ! empty( $marker['icon_color']['hex'] ) ) {
                    $icon_color = sanitize_hex_color( $marker['icon_color']['hex'] );
                } else {
                    $icon_color = '#000000';
                }

                // Render the icon using $this->render_icon()
                // capture to a variable via output buffering
                ob_start();
                echo $this->render_icon( $marker['icon'] );
                $icon_html = ob_get_clean();

                // Escape for use in JS
                $icon_html_escaped  = str_replace( "'", "\\'", $icon_html );
                $popup_escaped      = str_replace( "'", "\\'", $popup );
            ?>

            // Create marker
            var marker<?php echo $index; ?> = L.marker(
                [<?php echo $lat; ?>, <?php echo $lng; ?>],
                {
                    icon: createIcon(
                        '<?php echo $icon_html_escaped; ?>',
                        <?php echo $icon_size; ?>,
                        '<?php echo esc_js( $icon_color ); ?>'
                    )
                }
            ).bindPopup(
                '<div class="custom-openstreetmap-popup"><?php echo $popup_escaped; ?></div>'
            ).addTo(map);

            <?php endforeach; ?>
        });
        </script>
        <?php
    }
}

// Register the Custom Element
add_action( 'bricks_register_elements', function() {
    \Bricks\Element::register_element( 'Custom_Element_OpenStreetMap', 'openstreetmap' );
} );
