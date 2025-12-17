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
                    'units'   => true,
                    'css'     => [
                        [
                            'property' => 'font-size',
                            'selector' => '.leaflet-icon-custom',
                        ],
                    ],
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
            'label'   => __( 'Map Height', 'snn' ),
            'type'    => 'number',
            'units'   => true,
            'css'     => [
                [
                    'property' => 'height',
                    'selector' => '',
                ],
            ],
            'default' => 400,
            'min'     => 100,
            'step'    => 10,
        ];

        $this->controls['popup_font_size'] = [
            'tab'    => 'content',
            'label'  => __( 'Popup Font Size', 'snn' ),
            'type'   => 'number',
            'units'  => true,
            'css'    => [
                [
                    'property' => 'font-size',
                    'selector' => '.custom-openstreetmap-popup',
                ],
            ],
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

        // Control: Enable Post Type Query
        $this->controls['enable_post_type_query'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Use Post Type for Markers', 'snn' ),
            'type'    => 'checkbox',
            'inline'  => true,
            'default' => false,
            'description' => esc_html__( 'Enable to populate map markers from a custom post type. Uses custom fields: location_latitude, location_longitude', 'snn' ),
        ];

        // Control: Select Post Type
        $post_types = get_post_types( [ 'public' => true ], 'objects' );
        $post_type_options = [];
        
        foreach ( $post_types as $post_type ) {
            $post_type_options[ $post_type->name ] = $post_type->label;
        }

        $this->controls['post_type_select'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Select Post Type', 'snn' ),
            'type'        => 'select',
            'options'     => $post_type_options,
            'inline'      => true,
            'placeholder' => esc_html__( 'Select post type', 'snn' ),
            'default'     => 'post',
            'required'    => [ 'enable_post_type_query', '=', true ],
        ];
    }

    public function enqueue_scripts() {
        wp_enqueue_style( 'leaflet-css', SNN_URL_ASSETS . 'css/leaflet.css', [], '1.9.4' );
        wp_enqueue_script( 'leaflet-js', SNN_URL_ASSETS . 'js/leaflet.js', [], '1.9.4', true );
    }

    public function render() {
        $enable_scroll_zoom     = isset( $this->settings['enable_scroll_zoom'] ) ? (bool) $this->settings['enable_scroll_zoom'] : false;
        $enable_post_type_query = isset( $this->settings['enable_post_type_query'] ) ? (bool) $this->settings['enable_post_type_query'] : false;
        $map_center_lat         = isset( $this->settings['map_center_lat'] ) ? floatval( $this->settings['map_center_lat'] ) : 51.5;
        $map_center_lng         = isset( $this->settings['map_center_lng'] ) ? floatval( $this->settings['map_center_lng'] ) : -0.09;
        $zoom_level             = isset( $this->settings['zoom_level'] ) ? intval( $this->settings['zoom_level'] ) : 13;
        $markers                = isset( $this->settings['markers'] ) ? $this->settings['markers'] : [];
        $map_style              = isset( $this->settings['map_style'] ) ? $this->settings['map_style'] : 'default';

        // If post type query is enabled, fetch markers from posts
        if ( $enable_post_type_query ) {
            // Store current global post to restore it later (prevent any loop conflicts)
            global $post;
            $original_post = $post;
            
            $post_type = isset( $this->settings['post_type_select'] ) ? $this->settings['post_type_select'] : 'post';
            
            // Create completely isolated query args
            $query_args = [
                'post_type'      => $post_type,
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'fields'         => 'ids', // Get only IDs first for better performance
                'no_found_rows'  => true,  // Skip pagination calculations
                'ignore_sticky_posts' => true, // Don't let sticky posts interfere
                'suppress_filters' => false, // Allow filters but ensure clean query
                'meta_query'     => [
                    'relation' => 'AND',
                    [
                        'key'     => 'location_latitude',
                        'compare' => 'EXISTS',
                    ],
                    [
                        'key'     => 'location_latitude',
                        'value'   => '',
                        'compare' => '!=',
                    ],
                    [
                        'key'     => 'location_longitude',
                        'compare' => 'EXISTS',
                    ],
                    [
                        'key'     => 'location_longitude',
                        'value'   => '',
                        'compare' => '!=',
                    ],
                ],
            ];

            // Create new isolated query
            $posts_query = new \WP_Query( $query_args );
            $markers = []; // Always reset markers array

            // Process posts if found
            if ( ! empty( $posts_query->posts ) ) {
                foreach ( $posts_query->posts as $post_id ) {
                    // Get meta directly without using template tags (no loop context needed)
                    $lat = get_post_meta( $post_id, 'location_latitude', true );
                    $lng = get_post_meta( $post_id, 'location_longitude', true );

                    // Validate coordinates are numeric and not empty
                    if ( ! empty( $lat ) && ! empty( $lng ) && is_numeric( $lat ) && is_numeric( $lng ) ) {
                        // Validate coordinate ranges
                        $lat_float = floatval( $lat );
                        $lng_float = floatval( $lng );
                        
                        if ( $lat_float >= -90 && $lat_float <= 90 && $lng_float >= -180 && $lng_float <= 180 ) {
                            // Get post data without affecting global loop
                            $post_title = get_the_title( $post_id );
                            $post_obj = get_post( $post_id );
                            $post_content = ! empty( $post_obj->post_content ) ? apply_filters( 'the_content', $post_obj->post_content ) : '';
                            
                            $marker = [
                                'lat'        => $lat_float,
                                'lng'        => $lng_float,
                                'popup'      => '<h3>' . esc_html( $post_title ) . '</h3>' . $post_content,
                                'icon_size'  => 32,
                                'icon_color' => '#000000',
                            ];

                            // Check for featured image
                            if ( has_post_thumbnail( $post_id ) ) {
                                $thumbnail_url = get_the_post_thumbnail_url( $post_id, 'thumbnail' );
                                $marker['featured_image'] = $thumbnail_url;
                            } else {
                                // Default icon if no featured image
                                $marker['icon'] = [
                                    'library' => 'fontawesome',
                                    'icon'    => 'fa-map-marker-alt',
                                ];
                            }

                            $markers[] = $marker;
                        }
                    }
                }
            }
            
            // Clean up query and restore original global post state
            wp_reset_postdata();
            $post = $original_post;
            
            // If global $wp_query exists, ensure it's not affected
            if ( isset( $GLOBALS['wp_query'] ) ) {
                $GLOBALS['wp_query']->reset_postdata();
            }
        }

        // Set up the root attributes using Bricks methods.
        // Here we add our wrapper class.
        $root_classes = ['custom-openstreetmap-wrapper'];
        $this->set_attribute('_root', 'class', $root_classes);

        // Check if an ID is already set on the element’s root.
        // If not, generate one.
        if ( isset( $this->attributes['_root']['id'] ) && ! empty( $this->attributes['_root']['id'] ) ) {
            $map_id = $this->attributes['_root']['id'];
        } else {
            $map_id = 'custom-openstreetmap-' . uniqid();
            $this->set_attribute('_root', 'id', $map_id);
        }

        // Inline CSS for Leaflet styling
        $popup_font_size_css = "
            <style>
                .leaflet-icon-custom {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .leaflet-icon-custom svg {
                    width: 100% !important;
                    height: auto;
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
                #{$map_id} {
                    width: 100%;
                    max-width: 100%;
                }
            </style>
        ";

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

                // Check if this marker uses a featured image
                $use_featured_image = isset( $marker['featured_image'] ) && ! empty( $marker['featured_image'] );

                if ( $use_featured_image ) {
                    $featured_image_url = esc_url( $marker['featured_image'] );
                    $icon_label = !empty( $popup ) ? wp_strip_all_tags( $popup ) : 'Map marker';
                    $icon_label_escaped = str_replace( "'", "\\'", $icon_label );
                    $popup_escaped = str_replace( "'", "\\'", $popup );
                    ?>

                    // Create marker with featured image
                    var imageIcon<?php echo $index; ?> = L.icon({
                        iconUrl: '<?php echo $featured_image_url; ?>',
                        iconSize: [<?php echo $icon_size; ?>, <?php echo $icon_size; ?>],
                        iconAnchor: [<?php echo $icon_size / 2; ?>, <?php echo $icon_size; ?>],
                        popupAnchor: [0, -<?php echo $icon_size; ?>]
                    });

                    var marker<?php echo $index; ?> = L.marker(
                        [<?php echo $lat; ?>, <?php echo $lng; ?>],
                        { icon: imageIcon<?php echo $index; ?> }
                    ).bindPopup(
                        '<div class="custom-openstreetmap-popup"><?php echo str_replace(array("\r", "\n"), '', $popup_escaped); ?></div>'
                    ).addTo(map);

                <?php } else {
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

            // Create marker with custom icon
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

            <?php } ?>

            <?php endforeach; ?>
        });
        </script>
        <?php
    }

    public static function render_builder() {
        ?>
        <script type="text/x-template" id="tmpl-bricks-element-openstreetmap">
            <div class="custom-openstreetmap-wrapper" style="width:100%; height: 400px; background: #eaeaea; display: flex; align-items: center; justify-content: center;">
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
