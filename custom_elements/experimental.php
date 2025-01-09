<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Prefix_Element_Custom extends \Bricks\Element {
  // Element properties
  public $category     = 'general'; // Use predefined element category 'general'
  public $name         = 'prefix-custom'; // Unique element name with prefix
  public $icon         = 'ti-ruler-pencil'; // Themify icon font class
  public $css_selector = ''; // Default CSS selector

  // Return localized element label
  public function get_label() {
    return esc_html__( 'Custom Transform Element', 'bricks' );
  }

  // Set builder controls
  public function set_controls() {
    // Text content control
    $this->controls['content'] = [
      'tab' => 'content',
      'label' => esc_html__( 'Content', 'bricks' ),
      'type' => 'text',
      'default' => esc_html__( 'This is a custom element.', 'bricks' ),
    ];

  }

  // Render element HTML
  public function render() {
    // Get settings
    $content = ! empty( $this->settings['content'] ) ? $this->settings['content'] : '';


    // Render element HTML
    echo "<div class='prefix-custom-wrapper' >";
      echo "<div class='custom-element-content'>{$content}</div>";
    echo '</div>';
  }
}
