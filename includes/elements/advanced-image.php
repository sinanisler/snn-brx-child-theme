<?php
// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) exit; 

class Prefix_Element_Advanced_Image extends \Bricks\Element {
  // Element properties
  public $category     = 'snn';
  public $name         = 'prefix-advanced-image';
  public $icon         = 'ti-image';
  public $css_selector = '.prefix-advanced-image-wrapper';
  public $scripts      = [];

  // Return localized element label
  public function get_label() {
    return esc_html__( 'Advanced Image', 'bricks' );
  }

  // Set builder controls
  public function set_controls() {
    // Image Control
    $this->controls['image'] = [
      'tab'   => 'content',
      'label' => esc_html__( 'Image', 'bricks' ),
      'type'  => 'image',
    ];

    // CSS Filters Sliders
    $filters = [
      'blur' => [
        'label'   => esc_html__( 'Blur', 'bricks' ),
        'unit'    => 'px',
        'min'     => 0,
        'max'     => 10,
        'step'    => 0.1,
        'default' => '0px'
      ],
      'brightness' => [
        'label'   => esc_html__( 'Brightness', 'bricks' ),
        'unit'    => '%',
        'min'     => 0,
        'max'     => 200,
        'step'    => 1,
        'default' => '100%'
      ],
      'contrast' => [
        'label'   => esc_html__( 'Contrast', 'bricks' ),
        'unit'    => '%',
        'min'     => 0,
        'max'     => 200,
        'step'    => 1,
        'default' => '100%'
      ],
      'grayscale' => [
        'label'   => esc_html__( 'Grayscale', 'bricks' ),
        'unit'    => '%',
        'min'     => 0,
        'max'     => 100,
        'step'    => 1,
        'default' => '0%'
      ],
      'hue-rotate' => [
        'label'   => esc_html__( 'Hue Rotate', 'bricks' ),
        'unit'    => 'deg',
        'min'     => 0,
        'max'     => 360,
        'step'    => 1,
        'default' => '0deg'
      ],
      'invert' => [
        'label'   => esc_html__( 'Invert', 'bricks' ),
        'unit'    => '%',
        'min'     => 0,
        'max'     => 100,
        'step'    => 1,
        'default' => '0%'
      ],
      'opacity' => [
        'label'   => esc_html__( 'Opacity', 'bricks' ),
        'unit'    => '%',
        'min'     => 0,
        'max'     => 100,
        'step'    => 1,
        'default' => '100%'
      ],
      'saturate' => [
        'label'   => esc_html__( 'Saturate', 'bricks' ),
        'unit'    => '%',
        'min'     => 0,
        'max'     => 300,
        'step'    => 1,
        'default' => '100%'
      ],
      'sepia' => [
        'label'   => esc_html__( 'Sepia', 'bricks' ),
        'unit'    => '%',
        'min'     => 0,
        'max'     => 100,
        'step'    => 1,
        'default' => '0%'
      ],
    ];

    foreach ( $filters as $key => $filter ) {
      $this->controls[$key] = [
        'tab'     => 'content',
        'label'   => $filter['label'],
        'type'    => 'slider',
        'units'   => [
          $filter['unit'] => [
            'min'  => $filter['min'],
            'max'  => $filter['max'],
            'step' => $filter['step'],
          ],
        ],
        'default' => $filter['default'],
      ];
    }
  }

  // Render element HTML
  public function render() {
    // Set the root attributes
    $root_classes = ['prefix-advanced-image-wrapper'];
    $this->set_attribute('_root', 'class', $root_classes);

    // Generate or retrieve a unique ID for the root element
    if ( isset( $this->attributes['_root']['id'] ) && ! empty( $this->attributes['_root']['id'] ) ) {
      $root_id = $this->attributes['_root']['id'];
    } else {
      $root_id = 'prefix-advanced-image-' . uniqid();
      $this->set_attribute('_root', 'id', $root_id);
    }

    if ( isset( $this->settings['image'] ) ) {
      $image_id   = $this->settings['image']['id'];
      $image_size = $this->settings['image']['size'];

      // Build the filter string from the slider settings
      $filters = [
        'blur'       => 'px',
        'brightness' => '%',
        'contrast'   => '%',
        'grayscale'  => '%',
        'hue-rotate' => 'deg',
        'invert'     => '%',
        'opacity'    => '%',
        'saturate'   => '%',
        'sepia'      => '%',
      ];

      $filter_styles = [];
      foreach ( $filters as $key => $unit ) {
        if ( ! empty( $this->settings[$key] ) ) {
          $filter_styles[] = "{$key}({$this->settings[$key]})";
        }
      }
      $filter_string = implode( ' ', $filter_styles );

      // Instead of outputting a <style> block, add the filter as an inline style on the root element.
      if ( ! empty( $filter_string ) ) {
        $this->set_attribute('_root', 'style', "filter: {$filter_string};" );
      }

      // Render the HTML with the root attributes and image element
      echo '<div ' . $this->render_attributes('_root') . '>';
      echo wp_get_attachment_image(
        $image_id,
        $image_size,
        false,
        [ 'class' => 'prefix-advanced-image-img' ]
      );
      echo '</div>';
    } else {
      esc_html_e( 'No image selected.', 'bricks' );
    }
  }
}
