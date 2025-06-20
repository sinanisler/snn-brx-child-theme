<?php
if ( ! defined( 'ABSPATH' ) ) exit;

use Bricks\Element;

class Snn_Print_Page_Pdf extends Element {
    public $category     = 'snn';
    public $name         = 'snn-print-page-pdf';
    public $icon         = 'ti-printer';
    public $css_selector = '.brxe-snn-print-page-pdf';
    public $scripts      = [];
    public $nestable     = false;

    public function get_label() {
        return esc_html__( 'Print Page/PDF', 'snn' );
    }

    public function set_controls() {
        $this->controls['button_text'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Button Text', 'snn' ),
            'type'    => 'text',
            'default' => esc_html__( 'Print Page', 'snn' ),
        ];
        $this->controls['print_selector'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Print Selector (optional)', 'snn' ),
            'type'        => 'text',
            'default'     => '',
            'placeholder' => '.my-section or #my-id',
            'description' => esc_html__( 'If you want to print only a specific section, enter a CSS selector (like .my-section or #my-id). Leave blank to print the full page.', 'bricks' ),
        ];
    }

    public function render() {
        $button_text    = !empty($this->settings['button_text']) ? $this->settings['button_text'] : esc_html__( 'Print Page', 'snn' );
        $print_selector = trim($this->settings['print_selector'] ?? '');

        // Only append, never overwrite classes!
        $this->set_attribute('_root', 'class', ['brxe-snn-print-page-pdf', 'snn-print-page-pdf-wrapper']);
        // $this->set_attribute('_root', 'type', 'button');

        // Get Bricks-generated ID for the element
        $root_id = !empty($this->attributes['_root']['id']) ? $this->attributes['_root']['id'] : '';

        echo '<button ' . $this->render_attributes('_root') . '>';
        echo esc_html($button_text);
        echo '</button>';

        // Only print styles for print mode (no visual styling)
        ?>
        <style>
            .brxe-snn-print-page-pdf {
                cursor: pointer;
                padding: 10px 18px;
            }
            .snn-printing .snn-print-section-to-print {
            }
        </style>
        <script>
        (function(){
            var btn = document.getElementById('<?php echo esc_js($root_id); ?>');
            var selector = <?php echo json_encode($print_selector); ?>;
            if (!btn) return;
            btn.addEventListener('click', function() {
                if(selector) {
                    var el = document.querySelector(selector);
                    if(el) {
                        el.classList.add('snn-print-section-to-print');
                        document.body.classList.add('snn-printing');
                        window.print();
                        setTimeout(function(){
                            el.classList.remove('snn-print-section-to-print');
                            document.body.classList.remove('snn-printing');
                        }, 1000);
                    } else {
                        alert('No element found for selector: ' + selector);
                        window.print();
                    }
                } else {
                    window.print();
                }
            });
        })();
        </script>
        <?php
    }
}
?>
