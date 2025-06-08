<?php

if (!defined('ABSPATH')) {
    exit;
}

use Bricks\Frontend;

class Prefix_Element_Smoke_Text extends \Bricks\Element {
    public $category = 'snn';
    public $name = 'smoke-text';
    public $icon = 'ti-text';
    public $scripts = [];
    public $nestable = false;

    public function get_label() {
        return esc_html__('Smoke Text', 'snn');
    }

    public function set_control_groups() {
        // Define any control groups if needed.
    }

    public function set_controls() {
        // Textarea control for text input
        $this->controls['text_content'] = [
            'tab'         => 'content',
            'label'       => esc_html__('Text Content', 'snn'),
            'type'        => 'textarea',
            'rows'        => 5, // Default number of rows
            'spellcheck'  => true, // Enable spellcheck
            'inlineEditing' => true, // Allow inline editing
            'default'     => esc_html__('Lorem ipsum dolor sinan amet.', 'snn'),
        ];

        // Typography control for text styling
        $this->controls['text_typography'] = [
            'tab'   => 'style',
            'group' => 'style',
            'label' => esc_html__('Typography', 'snn'),
            'type'  => 'typography',
            'css'   => [
                [
                    'property' => 'font',
                    'selector' => '.smoke-text',
                ],
            ],
        ];
    }

    public function render() {
        $unique_class = 'smoke-text-' . uniqid(); // Unique class instead of ID
        $this->set_attribute('_root', 'class', ['smoke-text-wrapper', $unique_class]);

        // Get the text content from the settings
        $text_content = isset($this->settings['text_content']) ? nl2br(esc_html($this->settings['text_content'])) : 'Hover Me!';

        ?>
        <style>
            .smoke-text-wrapper { cursor: pointer; }
            .smoke-text span { display: inline-block; transition: transform 0.5s ease-out, opacity 0.5s ease-out; position: relative; z-index: 1; }
            .smoke-text span.active { animation: Smoke 1.5s forwards; }
            @keyframes Smoke {
                0% {
                    opacity: 1;
                    filter: blur(0);
                    transform: translate(0, 0) rotate(0deg) scale(1);
                    z-index: 1;
                }
                30% {
                    opacity: 1;
                    pointer-events: none;
                }
                100% {
                    opacity: 0;
                    filter: blur(20px);
                    transform: translate(var(--randomX, 400px), var(--randomY, -400px)) 
                               rotate(var(--randomRot, 3turn)) 
                               scale(3.5);
                    z-index: -1; 
                }
            }
        </style>

        <div <?php echo $this->render_attributes('_root'); ?>>
            <p class="smoke-text"><?php echo $text_content; ?></p>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                let paragraphs = document.querySelectorAll(".<?php echo $unique_class; ?> .smoke-text");

                paragraphs.forEach(text => {
                    text.innerHTML = text.textContent.replace(/\S/g, "<span>$&</span>");
                });

                let letters = document.querySelectorAll(".<?php echo $unique_class; ?> .smoke-text span");

                letters.forEach(letter => {
                    let randomX = Math.random() * 600 - 300; 
                    let randomY = Math.random() * 600 - 300; 
                    let randomRot = Math.random() * 2 + 2;
                    letter.style.setProperty('--randomX', `${randomX}px`);
                    letter.style.setProperty('--randomY', `${randomY}px`);
                    letter.style.setProperty('--randomRot', `${randomRot}turn`);

                    letter.addEventListener("mouseover", () => {
                        letter.classList.add("active");
                        setTimeout(() => {
                            letter.style.zIndex = "-1";
                        }, 1500); 
                    });
                });
            });
        </script>
        <?php
    }

    public static function render_builder() {
        ?>
        <script type="text/x-template" id="tmpl-bricks-element-smoke-text">
            <component :is="tag">
                <div v-if="element.settings.text_content" class="smoke-text">
                    {{ element.settings.text_content }}
                </div>
                <bricks-element-children :element="element"/>
            </component>
        </script>
        <?php
    }
}
?>
