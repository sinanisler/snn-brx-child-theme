<?php 
if ( ! defined( 'ABSPATH' ) ) exit;



class Custom_HTML_CSS_Script extends \Bricks\Element {
  public $category     = 'snn';
  public $name         = 'custom-html-css-script';
  public $icon         = 'fas fa-code'; 
  public $css_selector = '.snn-brx-html-css-script-wrapper';

  public function get_label() {
    return 'Custom Code HTML -SNN';
  }

  // Set builder control
  public function set_controls() {
    $this->controls['content'] = [
      'tab' => 'content',
      'label' => 'Custom HTML Code',
      'type' => 'code',
      'mode' => 'php',
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
