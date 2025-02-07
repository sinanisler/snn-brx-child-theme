<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class Custom_HTML_CSS_Script extends \Bricks\Element {
  public $category     = 'snn';
  public $name         = 'custom-html-css-script';
  public $icon         = 'fas fa-code';

  public function get_label() {
    return 'Custom Code HTML JS CSS';
  }

  public function set_controls() {
    $this->controls['content'] = [
      'tab' => 'content',
      'label' => '
Custom HTML, CSS, and JS Code<br>
      ',
      'type' => 'code',
      'mode' => 'php',
      'default' => '
<div>Your HTML here</div>

<h1 class="redme">Title</h1>

<style>
.redme{color:red}
</style>

<script>
var sebas_sama = 6969;
</script>
      ',
    ];
  }

  public function enqueue_scripts() {
    // Optionally enqueue styles or scripts
  }

  public function render() {
    echo "<div {$this->render_attributes('_root')}>";
    echo wp_kses_post($this->settings['content']);
    echo "</div>";
  }
}

add_action( 'bricks/element_classes', function( $element_classes ) {
  $element_classes['custom-html-css-script'] = 'Custom_HTML_CSS_Script';
  return $element_classes;
});
