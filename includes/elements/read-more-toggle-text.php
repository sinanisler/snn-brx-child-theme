<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Bricks\Element;

class Prefix_Element_Toggle_Text extends Element {
    public $category  = 'snn';
    public $name      = 'toggle-text';
    public $icon      = 'ti-text';
    public $scripts   = []; 
    public $nestable  = false;

    public function get_label(): string {
        return esc_html__( 'Read More and Toggle Text', 'snn' );
    }

    public function set_controls(): void {
        $this->controls['text_content'] = [
            'tab'           => 'content',
            'label'         => esc_html__( 'Text Content', 'snn' ),
            'type'          => 'editor',
            'default'       => esc_html__( 'Lorem ipsum dolor sinan amet...', 'snn' ),
            'inlineEditing' => true,
        ];

        $this->controls['text_height'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Text Height (px)', 'snn' ),
            'type'    => 'number',
            'default' => 100,
            'min'     => 0,
        ];

        $this->controls['button_selector'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Button Selector (ID or Class)', 'snn' ),
            'type'        => 'text',
            'default'     => '',
            'placeholder' => '#my-button or .my-button',
            'description' => "
                <p data-control='info'>
                    Add a button and copy the selector (ID or Class) here to make the toggle work.<br>
                    Each instance should have a unique button selector.<br><br>
                    Button icon animate CSS: <br>
                    %root%.active-toggle-text i{ <br>
                        rotate:180deg; <br>
                    }
                </p>
            ",
        ];

        $this->controls['text_typography'] = [
            'tab'   => 'style',
            'label' => esc_html__( 'Text Typography', 'snn' ),
            'type'  => 'typography',
            'css'   => [
                [
                    'property' => 'typography',
                    'selector' => '.toggle-text-content',
                ],
            ],
        ];
    }

    public function render(): void {
        $unique_class = 'toggle-text-' . uniqid();
        $this->set_attribute( '_root', 'class', [ 'toggle-text-wrapper', $unique_class ] );

        $text_content    = $this->settings['text_content'] ?? esc_html__( 'Your content goes here...', 'snn' );
        $text_height     = $this->settings['text_height'] ?? 100;
        $button_selector = $this->settings['button_selector'] ?? '';

        // Add button selector as data attribute if provided
        if ( ! empty( $button_selector ) ) {
            $this->set_attribute( '_root', 'data-button-selector', $button_selector );
        }

        ?>
        <style>
            .toggle-text-wrapper {
                margin: 20px 0;
            }
            .toggle-text-content {
                overflow: hidden;
                transition: max-height 0.3s ease;
            }
        </style>

        <div <?php echo $this->render_attributes( '_root' ); ?>>
            <div class="toggle-text-content">
                <?php echo $text_content; ?>
            </div>
        </div>

        <script>
            (function() {
                const container = document.querySelector(".<?php echo esc_js( $unique_class ); ?>");
                if (!container) return;

                const content = container.querySelector(".toggle-text-content");
                if (!content) return;

                <?php if ( ! empty( $button_selector ) ) : ?>
                    const collapsedHeight = <?php echo json_encode( $text_height ); ?>;
                    const buttonSelector = <?php echo json_encode( $button_selector ); ?>;
                    
                    // Set initial collapsed height
                    content.style.maxHeight = collapsedHeight + "px";

                    let isExpanded = false;

                    // Function to initialize button
                    function initButton() {
                        const button = document.querySelector(buttonSelector);
                        if (!button) return false;

                        // Add data attribute to button to match this toggle text
                        button.setAttribute("data-toggle-target", ".<?php echo esc_js( $unique_class ); ?>");
                        button.setAttribute("aria-expanded", "false");

                        // Remove any existing listeners to prevent duplicates
                        const newButton = button.cloneNode(true);
                        button.parentNode.replaceChild(newButton, button);

                        newButton.addEventListener("click", function(e) {
                            e.preventDefault();
                            
                            if (isExpanded) {
                                content.style.maxHeight = collapsedHeight + "px";
                                newButton.classList.remove("active-toggle-text");
                                newButton.setAttribute("aria-expanded", "false");
                            } else {
                                content.style.maxHeight = content.scrollHeight + "px";
                                newButton.classList.add("active-toggle-text");
                                newButton.setAttribute("aria-expanded", "true");
                            }
                            isExpanded = !isExpanded;
                        });

                        return true;
                    }

                    // Try to initialize immediately
                    if (!initButton()) {
                        // If button not found, wait for DOM to be fully loaded
                        if (document.readyState === 'loading') {
                            document.addEventListener('DOMContentLoaded', initButton);
                        } else {
                            // Wait a bit for dynamic content
                            setTimeout(initButton, 100);
                        }
                    }
                <?php else : ?>
                    console.warn("Button selector is not defined for toggle text element.");
                <?php endif; ?>
            })();
        </script>
        <?php
    }

    public static function render_builder(): void {
        ?>
        <script type="text/x-template" id="tmpl-bricks-element-toggle-text">
            <component :is="tag">
                <div v-if="element.settings.text_content" class="toggle-text-content" v-html="element.settings.text_content"></div>
                <bricks-element-children :element="element"/>
            </component>
        </script>
        <?php
    }
}
?>
