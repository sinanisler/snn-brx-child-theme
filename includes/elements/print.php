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
        $this->controls['print_selector'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Print Selector', 'snn' ),
            'type'        => 'text',
            'default'     => '',
            'placeholder' => '#brxe-xxxx',
            'description' => esc_html__( 'If you want to print only a specific section, enter a CSS selector (#brxe-xxxx). Leave blank to print the full page.', 'snn' ),
        ];
        $this->controls['button_selector'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Button Selector', 'snn' ),
            'type'        => 'text',
            'default'     => '',
            'placeholder' => '#brxe-xxxx',
            'description' => esc_html__( 'Provide a CSS selector for the button you want to trigger the print action (#brxe-xxxx).', 'snn' ),
        ];
        $this->controls['print_button_text'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Print Button Text', 'snn' ),
            'type'        => 'text',
            'default'     => 'PRINT',
            'placeholder' => 'PRINT',
            'description' => esc_html__( 'Change the print button text in the popup.', 'snn' ),
        ];
    }

    public function render() {
        $button_selector = trim($this->settings['button_selector'] ?? '');
        $print_selector  = trim($this->settings['print_selector'] ?? '');
        $print_button_text = trim($this->settings['print_button_text'] ?? 'PRINT');

        $this->set_attribute('_root', 'class', ['brxe-snn-print-page-pdf', 'snn-print-page-pdf-wrapper']);

        echo '<div ' . $this->render_attributes('_root') . '></div>';

        ?>
        <style>
            .brxe-snn-print-page-pdf {
                display: contents;
            }
        </style>
        <script>
        (function(){
            var btnSelector = <?php echo json_encode($button_selector); ?>;
            var printSelector = <?php echo json_encode($print_selector); ?>;
            var printButtonText = <?php echo json_encode($print_button_text); ?>;
            if (!btnSelector) return;

            function printSectionContent(el) {
                // Collect all stylesheets
                var css = '';
                var stylesheets = Array.from(document.querySelectorAll('link[rel="stylesheet"],style'));
                stylesheets.forEach(function(node) {
                    if(node.tagName === "LINK") {
                        css += '<link rel="stylesheet" href="' + node.href + '" />';
                    } else {
                        css += '<style>' + node.innerHTML + '</style>';
                    }
                });

                // Open as big as possible
                var width = window.screen.width;
                var height = window.screen.height;
                var printWindow = window.open('', '_blank', 'top=0,left=0,width=' + width + ',height=' + height + ',scrollbars=yes,resizable=yes');

                // Style for print button
                var printBtnCss = `
                    <style>
                        #snn-print-btn {
                            position: fixed;
                            top: 24px;
                            right: 24px;
                            z-index: 99999;
                            padding: 18px 36px;
                            font-size: 20px;
                            background:rgb(29, 29, 29);
                            color: #fff;
                            border: none;
                            border-radius: 6px;
                            box-shadow: 0 2px 8px rgba(0,0,0,0.14);
                            cursor: pointer;
                            transition: background 0.2s;
                        }
                        #snn-print-btn:hover {
                            background:rgb(0, 0, 0);
                        }
                        @media print {
                            #snn-print-btn {
                                display: none !important;
                            }
                        }
                        html,body{
                            margin:0;padding:10px;width:100vw;height:100vh;box-sizing:border-box;
                        }
                        @page{size:auto;margin:0;}
                    </style>
                `;

                // Print button markup (customizable text)
                var printBtnHtml = `<button id="snn-print-btn">` + (printButtonText || 'PRINT') + `</button>`;

                printWindow.document.write(
                    '<html><head><title>Print</title>' +
                    css + printBtnCss +
                    '</head><body>' +
                    el.innerHTML + printBtnHtml +
                    '</body></html>'
                );
                printWindow.document.close();

                // Wait for popup ready
                printWindow.onload = function(){
                    // Attach click to PRINT button
                    var btn = printWindow.document.getElementById('snn-print-btn');
                    if (btn) {
                        btn.addEventListener('click', function() {
                            printWindow.print();
                            printWindow.close();
                        });
                    }
                };
            }

            function printHandler(e) {
                e.preventDefault();
                if (printSelector) {
                    var el = document.querySelector(printSelector);
                    if (el) {
                        printSectionContent(el);
                    } else {
                        alert('No element found for selector: ' + printSelector);
                    }
                } else {
                    window.print();
                }
            }

            function attachHandler() {
                var btns = document.querySelectorAll(btnSelector);
                btns.forEach(function(btn) {
                    btn.removeEventListener('click', printHandler);
                    btn.addEventListener('click', printHandler);
                });
            }

            if (document.readyState !== 'loading') {
                attachHandler();
            } else {
                document.addEventListener('DOMContentLoaded', attachHandler);
            }
            document.addEventListener('bricks/frontend/render', attachHandler);

        })();
        </script>
        <?php
    }
}
?>
