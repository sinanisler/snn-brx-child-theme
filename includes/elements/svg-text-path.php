<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

use Bricks\Element;

class Svg_Text_Path_Element extends Element {
    public $category     = 'snn';
    public $name         = 'svg-text-path';
    public $icon         = 'ti-text';
    public $css_selector = '.svg-text-path-wrapper';
    public $scripts      = []; // No external scripts required
    public $nestable     = false;

    public function get_label() {
        return esc_html__( 'SVG Text Path', 'bricks' );
    }

    public function set_control_groups() {
        // Define control groups if necessary.
    }

    public function set_controls() {
        // Text control for the text to be displayed along the path.
        $this->controls['text'] = [
            'tab'     => 'content',
            'type'    => 'text',
            'default' => esc_html__( 'Your Text Here', 'bricks' ),
        ];

        // Select control for choosing the SVG path shape.
        $this->controls['svg_option'] = [
            'tab'     => 'content',
            'type'    => 'select',
            'options' => [
                'wave'   => esc_html__( 'Wave', 'bricks' ),
                'arc'    => esc_html__( 'Arc', 'bricks' ),
                'circle' => esc_html__( 'Circle', 'bricks' ),
                'elypse' => esc_html__( 'Elypse', 'bricks' ),
                'spiral' => esc_html__( 'Spiral', 'bricks' ),
            ],
            'default' => 'wave',
        ];

        // Control for custom SVG upload.
        $this->controls['custom_svg'] = [
            'tab'         => 'content',
            'type'        => 'svg',
            'label'       => esc_html__( 'Custom SVG', 'bricks' ),
            'description' => esc_html__( 'Upload your own SVG path. <br>Create a 510px x 170px transparent rectangle, draw your path inside it, group and export it.', 'bricks' ),
        ];

        // New slider control to rotate the text (in degrees).
        $this->controls['rotate'] = [
            'tab'     => 'content',
            'type'    => 'slider',
            'label'   => esc_html__( 'Rotate Text', 'bricks' ),
            'units'   => [
                'deg' => [
                    'min'  => 0,
                    'max'  => 360,
                    'step' => 1,
                ],
            ],
            'default' => '0deg',
        ];

        // New number control for the starting point (startOffset) of the text path.
        $this->controls['start_offset'] = [
            'tab'     => 'content',
            'type'    => 'number',
            'label'   => esc_html__( 'Text Start Offset', 'bricks' ),
            'default' => 0,
            'min'     => 0,
            'max'     => 100,
            'step'    => 1,
            'unit'    => 'px',
        ];

        // New number control for word spacing.
        $this->controls['word_spacing'] = [
            'tab'     => 'content',
            'type'    => 'number',
            'label'   => esc_html__( 'Word Spacing', 'bricks' ),
            'default' => 0,
            'min'     => 0,
            'max'     => 50,
            'step'    => 0.1,
            'unit'    => 'px',
        ];
        
        // New control for path stroke color.
        $this->controls['path_stroke_color'] = [
            'tab'     => 'content',
            'type'    => 'color',
            'label'   => esc_html__( 'Path Stroke Color', 'bricks' ),
            'default' => '#000000',
        ];
        
        // New control for path stroke width.
        $this->controls['path_stroke_width'] = [
            'tab'     => 'content',
            'type'    => 'slider',
            'label'   => esc_html__( 'Path Stroke Width', 'bricks' ),
            'units'   => [
                'px' => [
                    'min'  => 0,
                    'max'  => 100,
                    'step' => 0.1,
                ],
            ],
            'default' => '0px',
        ];
    }

    public function render() {
        $text       = isset( $this->settings['text'] ) ? $this->settings['text'] : '';
        $svg_option = isset( $this->settings['svg_option'] ) ? $this->settings['svg_option'] : 'wave';

        // Sanitize settings.
        $text = is_array( $text ) ? '' : esc_html( $text );

        $rotate       = isset( $this->settings['rotate'] ) ? floatval( $this->settings['rotate'] ) : 0;
        $start_offset = isset( $this->settings['start_offset'] ) ? floatval( $this->settings['start_offset'] ) : 0;
        $word_spacing = isset( $this->settings['word_spacing'] ) ? floatval( $this->settings['word_spacing'] ) : 0;
        
        // Retrieve stroke color and stroke width.
        $path_stroke_color = isset( $this->settings['path_stroke_color'] ) ? $this->settings['path_stroke_color'] : '#000000';
        if ( is_array( $path_stroke_color ) ) {
            $path_stroke_color = !empty( $path_stroke_color['rgba'] )
                ? $path_stroke_color['rgba']
                : ( !empty( $path_stroke_color['hex'] ) ? $path_stroke_color['hex'] : '#000000' );
        }
        
        $stroke_width_value = isset( $this->settings['path_stroke_width'] ) ? $this->settings['path_stroke_width'] : '2px';
        if ( is_array( $stroke_width_value ) ) {
            $val  = isset( $stroke_width_value['value'] ) ? $stroke_width_value['value'] : '2';
            $unit = isset( $stroke_width_value['unit'] ) ? $stroke_width_value['unit'] : 'px';
            $stroke_width_value = $val . $unit;
        }

        // Use a CSS class for the <text> element instead of inline transform attributes.
        $text_attrs = ' class="svg-text-path-text"';

        // Set attributes on the root element.
        $this->set_attribute( '_root', 'class', 'brxe-svg-text-path' );
        if ( ! empty( $this->attributes['_root']['id'] ) ) {
            $root_id = $this->attributes['_root']['id'];
        } else {
            $root_id = 'svg-text-path-' . uniqid();
            $this->set_attribute( '_root', 'id', $root_id );
        }

        // Set attributes on the child element so Bricks applies its dynamic styles.
        $this->set_attribute( 'child', 'class', 'svg-text-path-wrapper' );

        // Preset SVG markups with placeholders for text attributes, start offset and text.
        $preset_svgs = [
            'wave' => '<svg viewBox="0 0 250 42.4994" xmlns="http://www.w3.org/2000/svg">
  <path d="M0,42.2494C62.5,42.2494,62.5.25,125,.25s62.5,41.9994,125,41.9994" id="e-path-a9421d5"></path>
  <text' . $text_attrs . '>
    <textPath id="e-text-path-a9421d5" href="#e-path-a9421d5" startOffset="%s">%s</textPath>
  </text>
</svg>',
            'arc' => '<svg viewBox="0 0 250.5 125.25" xmlns="http://www.w3.org/2000/svg">
  <path d="M.25,125.25a125,125,0,0,1,250,0" id="e-path-1bb1e70"></path>
  <text' . $text_attrs . '>
    <textPath id="e-text-path-1bb1e70" href="#e-path-1bb1e70" startOffset="%s">%s</textPath>
  </text>
</svg>',
            'circle' => '<svg viewBox="0 0 250.5 250.5" xmlns="http://www.w3.org/2000/svg">
  <path d="M.25,125.25a125,125,0,1,1,125,125,125,125,0,0,1-125-125" id="e-path-5de0159"></path>
  <text' . $text_attrs . '>
    <textPath id="e-text-path-5de0159" href="#e-path-5de0159" startOffset="%s">%s</textPath>
  </text>
</svg>',
            'elypse' => '<svg viewBox="0 0 250.5 125.75" xmlns="http://www.w3.org/2000/svg">
  <path d="M.25,62.875C.25,28.2882,56.2144.25,125.25.25s125,28.0382,125,62.625-55.9644,62.625-125,62.625S.25,97.4619.25,62.875" id="e-path-6995a9f"></path>
  <text' . $text_attrs . '>
    <textPath id="e-text-path-6995a9f" href="#e-path-6995a9f" startOffset="%s">%s</textPath>
  </text>
</svg>',
            'spiral' => '<svg viewBox="0 0 250.4348 239.4454" xmlns="http://www.w3.org/2000/svg">
  <path d="M.1848,49.0219a149.3489,149.3489,0,0,1,210.9824-9.8266,119.479,119.479,0,0,1,7.8613,168.786A95.5831,95.5831,0,0,1,84,214.27a76.4666,76.4666,0,0,1-5.0312-108.023" id="e-path-00f165a"></path>
  <text' . $text_attrs . '>
    <textPath id="e-text-path-00f165a" href="#e-path-00f165a" startOffset="%s">%s</textPath>
  </text>
</svg>',
        ];

        echo '<div ' . $this->render_attributes( '_root' ) . '>';
            echo '<style>
  #' . esc_attr( $root_id ) . ' svg {
    width: 100% ;
    height: auto ;
    position: relative ;
    left: 0 ;
    overflow: visible ;';
            if ( $rotate !== 0 ) {
                echo ' transform: rotate(' . esc_attr( $rotate ) . 'deg); transform-origin: center;';
            }
            echo ' }
  #' . esc_attr( $root_id ) . ' svg path {
    fill: transparent ;
  }
  #' . esc_attr( $root_id ) . ' svg textPath {
    fill: currentColor ;
    stroke: ' . esc_attr( $path_stroke_color ) . ' ;
    stroke-width: ' . esc_attr( $stroke_width_value ) . ' ;
  }
  #' . esc_attr( $root_id ) . ' svg text.svg-text-path-text {';
            if ( $word_spacing !== 0 ) {
                echo ' word-spacing: ' . esc_attr( $word_spacing ) . 'px;';
            }
            echo ' }
</style>';
            echo '<div ' . $this->render_attributes( 'child' ) . '>';
                if ( isset( $this->settings['custom_svg']['url'] ) && ! empty( $this->settings['custom_svg']['url'] ) ) {
                    // For frontend rendering we load the full SVG content and inject the text along its path.
                    $custom_svg_content = file_get_contents( esc_url( $this->settings['custom_svg']['url'] ) );
                    
                    // Use DOMDocument to modify the custom SVG.
                    libxml_use_internal_errors(true);
                    $doc = new DOMDocument();
                    $doc->loadXML( $custom_svg_content );
                    libxml_clear_errors();
                    
                    // Get the first <path> element.
                    $paths = $doc->getElementsByTagName('path');
                    if ( $paths->length > 0 ) {
                        $path = $paths->item(0);
                        // Remove stroke attribute if present.
                        if ( $path->hasAttribute('stroke') ) {
                            $path->removeAttribute('stroke');
                        }
                        // Ensure the path has an ID.
                        if ( ! $path->hasAttribute('id') ) {
                            $generated_id = 'custom-svg-path-' . uniqid();
                            $path->setAttribute('id', $generated_id);
                        } else {
                            $generated_id = $path->getAttribute('id');
                        }
                        
                        // Create a <text> element and assign the CSS class.
                        $textElement = $doc->createElement('text');
                        $textElement->setAttribute('class', 'svg-text-path-text');
                        
                        // Create the <textPath> element with the provided text.
                        $textPathElement = $doc->createElement('textPath', htmlspecialchars( $text ));
                        $textPathElement->setAttribute('href', '#' . $generated_id);
                        $textPathElement->setAttribute('startOffset', esc_attr( $start_offset ) . '%');
                        
                        // Append the textPath to the text element.
                        $textElement->appendChild( $textPathElement );
                        
                        // Append the text element to the main <svg> element.
                        $svgs = $doc->getElementsByTagName('svg');
                        if ( $svgs->length > 0 ) {
                            $svg = $svgs->item(0);
                            $svg->appendChild( $textElement );
                        }
                        
                        echo $doc->saveXML();
                    } else {
                        // If no path is found, output the custom SVG as is.
                        echo $custom_svg_content;
                    }
                } else {
                    $preset_svg = isset( $preset_svgs[ $svg_option ] ) ? $preset_svgs[ $svg_option ] : $preset_svgs['wave'];
                    echo sprintf( $preset_svg, sprintf( '%s%%', esc_attr( $start_offset ) ), $text );
                }
            echo '</div>';
        echo '</div>';
    }
}
?>
