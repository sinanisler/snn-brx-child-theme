<?php 



if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Custom_HTML_CSS_Script extends \Bricks\Element {
  // Element properties
  public $category     = 'SNN';
  public $name         = 'custom-html-css-script';
  public $icon         = 'fas fa-code'; // Assuming you use FontAwesome
  public $css_selector = '.snn-brx-html-css-script-wrapper';

  // Return localized element label
  public function get_label() {
    return 'Custom Code HTML';
  }

  // Set builder control groups
  public function set_control_groups() {
    //
  }

  // Set builder controls
  public function set_controls() {
    $this->controls['content'] = [
      'tab' => 'content',
      'label' => 'Custom HTML Code',
      'type' => 'textarea',
      'default' => '<div>Your HTML here</div>

<h1 class="redme">Title</h1>

<style>
.redme{color:red}
</style>
      ',
    ];
  }

  // Enqueue element styles and scripts
  public function enqueue_scripts() {
    // Optionally enqueue styles or scripts
  }

  // Render element HTML
  public function render() {
    echo "<div class='custom-html-css-script-wrapper'>";
    echo $this->settings['content']; // Direct output, consider security implications
    echo "</div>";
  }
}

// Register the custom element
add_action( 'bricks/element_classes', function( $element_classes ) {
  $element_classes['custom-html-css-script'] = 'Custom_HTML_CSS_Script';
  return $element_classes;
});
