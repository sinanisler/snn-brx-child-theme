<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

use Bricks\Element;
use Bricks\Frontend;

class Snn_Consent_Cookie_Block extends Element {
    public $category     = 'snn';
    public $name         = 'snn-consent-cookie-block';
    public $icon         = 'ti-lock';
    public $css_selector = '';
    public $scripts      = [];
    public $nestable     = true;

    // Global CSS + JS are printed once per page, no matter how many blocks are used.
    private static $assets_printed = false;

    public function get_label() {
        return esc_html__( 'Consent Cookie Block (Nestable)', 'snn' );
    }

    public function set_controls() {
        $this->controls['consent_text'] = [
            'tab'           => 'content',
            'label'         => esc_html__( 'Consent Text', 'snn' ),
            'type'          => 'editor',
            'default'       => esc_html__( 'This content is loaded from a third party. Accept to view it.', 'snn' ),
            'inlineEditing' => true,
            'description'   => "
                <p data-control='info'>
                    Nest anything you want to gate: an iframe, a Code element, a third party embed.<br>
                    Nothing loads or runs until the visitor clicks the button.
                </p>
            ",
        ];

        $this->controls['button_label'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Button Label', 'snn' ),
            'type'    => 'text',
            'default' => esc_html__( 'Accept & Load', 'snn' ),
            'inline'  => true,
        ];

        $this->controls['consent_key'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Consent Key', 'snn' ),
            'type'        => 'text',
            'default'     => '',
            'placeholder' => 'google-maps',
            'inline'      => true,
            'description' => "
                <p data-control='info'>
                    Blocks sharing the same key unlock together: on the same page, on every other page, and in other open tabs.<br>
                    Leave empty to keep this block on its own (nothing is remembered).
                </p>
            ",
        ];

        $this->controls['session_only'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Ask again on every visit', 'snn' ),
            'type'        => 'checkbox',
            'description' => esc_html__( 'The choice is kept for the current page view only and never stored.', 'snn' ),
        ];

        $this->controls['min_height'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Reserved Height', 'snn' ),
            'type'    => 'number',
            'units'   => [ 'px', 'vh', 'rem' ],
            'default' => '',
            'inline'  => true,
            'css'     => [
                [
                    'property' => 'min-height',
                    'selector' => '.snn-consent__prompt',
                ],
            ],
            'description' => esc_html__( 'Match the height of the gated content so the page does not jump when it loads.', 'snn' ),
        ];

        // ---------------------------------------------------------------- Style: prompt
        $this->controls['prompt_separator'] = [
            'tab'   => 'style',
            'label' => esc_html__( 'Prompt', 'snn' ),
            'type'  => 'separator',
        ];

        $this->controls['prompt_background'] = [
            'tab'   => 'style',
            'label' => esc_html__( 'Background', 'snn' ),
            'type'  => 'background',
            'css'   => [
                [
                    'property' => 'background',
                    'selector' => '.snn-consent__prompt',
                ],
            ],
        ];

        $this->controls['prompt_border'] = [
            'tab'   => 'style',
            'label' => esc_html__( 'Border', 'snn' ),
            'type'  => 'border',
            'css'   => [
                [
                    'property' => 'border',
                    'selector' => '.snn-consent__prompt',
                ],
            ],
        ];

        $this->controls['prompt_padding'] = [
            'tab'   => 'style',
            'label' => esc_html__( 'Padding', 'snn' ),
            'type'  => 'dimensions',
            'css'   => [
                [
                    'property' => 'padding',
                    'selector' => '.snn-consent__prompt',
                ],
            ],
        ];

        $this->controls['prompt_align'] = [
            'tab'     => 'style',
            'label'   => esc_html__( 'Alignment', 'snn' ),
            'type'    => 'select',
            'options' => [
                'flex-start' => esc_html__( 'Left', 'snn' ),
                'center'     => esc_html__( 'Center', 'snn' ),
                'flex-end'   => esc_html__( 'Right', 'snn' ),
            ],
            'inline'  => true,
            'css'     => [
                [
                    'property' => 'align-items',
                    'selector' => '.snn-consent__prompt',
                ],
            ],
        ];

        $this->controls['text_typography'] = [
            'tab'   => 'style',
            'label' => esc_html__( 'Text Typography', 'snn' ),
            'type'  => 'typography',
            'css'   => [
                [
                    'property' => 'typography',
                    'selector' => '.snn-consent__text',
                ],
            ],
        ];

        // ---------------------------------------------------------------- Style: button
        $this->controls['button_separator'] = [
            'tab'   => 'style',
            'label' => esc_html__( 'Button', 'snn' ),
            'type'  => 'separator',
        ];

        $this->controls['button_background'] = [
            'tab'   => 'style',
            'label' => esc_html__( 'Background Color', 'snn' ),
            'type'  => 'color',
            'css'   => [
                [
                    'property' => 'background-color',
                    'selector' => '.snn-consent__btn',
                ],
            ],
        ];

        $this->controls['button_color'] = [
            'tab'   => 'style',
            'label' => esc_html__( 'Text Color', 'snn' ),
            'type'  => 'color',
            'css'   => [
                [
                    'property' => 'color',
                    'selector' => '.snn-consent__btn',
                ],
            ],
        ];

        $this->controls['button_border'] = [
            'tab'   => 'style',
            'label' => esc_html__( 'Border', 'snn' ),
            'type'  => 'border',
            'css'   => [
                [
                    'property' => 'border',
                    'selector' => '.snn-consent__btn',
                ],
            ],
        ];

        $this->controls['button_padding'] = [
            'tab'   => 'style',
            'label' => esc_html__( 'Padding', 'snn' ),
            'type'  => 'dimensions',
            'css'   => [
                [
                    'property' => 'padding',
                    'selector' => '.snn-consent__btn',
                ],
            ],
        ];

        $this->controls['button_typography'] = [
            'tab'   => 'style',
            'label' => esc_html__( 'Typography', 'snn' ),
            'type'  => 'typography',
            'css'   => [
                [
                    'property' => 'typography',
                    'selector' => '.snn-consent__btn',
                ],
            ],
        ];
    }

    public function render() {
        $consent_text = $this->settings['consent_text'] ?? esc_html__( 'This content is loaded from a third party. Accept to view it.', 'snn' );
        $button_label = $this->settings['button_label'] ?? esc_html__( 'Accept & Load', 'snn' );
        $consent_key  = strtolower( trim( (string) ( $this->settings['consent_key'] ?? '' ) ) );
        $session_only = ! empty( $this->settings['session_only'] );

        $is_builder = function_exists( 'bricks_is_builder' ) && bricks_is_builder();

        $this->set_attribute( '_root', 'class', 'snn-consent' );

        // Inside the builder canvas the children stay live so they remain editable.
        if ( $is_builder ) {
            echo '<div ' . $this->render_attributes( '_root' ) . '>';
                echo '<div class="snn-consent__prompt snn-consent--ready">';
                    echo '<div class="snn-consent__text">' . $consent_text . '</div>';
                    echo '<button type="button" class="snn-consent__btn">' . esc_html( $button_label ) . '</button>';
                echo '</div>';
                echo Frontend::render_children( $this );
            echo '</div>';
            return;
        }

        $payload = Frontend::render_children( $this );

        // A payload containing the closing string would end our wrapper early.
        // That block falls back to a base64 attribute, which is never parsed at all.
        $use_base64 = ( stripos( $payload, '</template' ) !== false );

        $this->set_attribute( '_root', 'data-snn-consent', $use_base64 ? 'base64' : 'template' );
        $this->set_attribute( '_root', 'data-consent-key', $consent_key );
        $this->set_attribute( '_root', 'data-remember', $session_only ? '0' : '1' );

        if ( $use_base64 ) {
            $this->set_attribute( '_root', 'data-snn-payload', base64_encode( $payload ) );
        }

        // Unique only for the aria relationship. Never used as a JS hook, so query
        // loops and AJAX inserted copies stay safe.
        $describe_id = 'snn-consent-text-' . uniqid();

        self::print_assets();
        ?>
        <div <?php echo $this->render_attributes( '_root' ); ?>>
            <div class="snn-consent__prompt">
                <div class="snn-consent__text" id="<?php echo esc_attr( $describe_id ); ?>"><?php echo $consent_text; ?></div>
                <button type="button" class="snn-consent__btn" aria-describedby="<?php echo esc_attr( $describe_id ); ?>"><?php echo esc_html( $button_label ); ?></button>
            </div>
            <?php if ( ! $use_base64 && $payload !== '' ) : ?>
                <template class="snn-consent__payload"><?php echo $payload; ?></template>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Stylesheet shared by every block. Also used by the builder canvas.
     */
    public static function css() {
        return '
            .snn-consent{display:block;width:100%}
            .snn-consent__prompt{display:flex;flex-direction:column;align-items:flex-start;justify-content:center;gap:12px;padding:24px;background:#f3f4f6;border:1px solid #e2e5ea;border-radius:6px;visibility:hidden}
            .snn-consent--ready .snn-consent__prompt,.snn-consent__prompt.snn-consent--ready{visibility:visible}
            .snn-consent__text{max-width:60ch}
            .snn-consent__btn{cursor:pointer;font:inherit;line-height:1.2;padding:11px 20px;border:0;border-radius:4px;background:#1b1f24;color:#fff}
            .snn-consent__btn:focus-visible{outline:2px solid currentColor;outline-offset:2px}
            .snn-consent__content{display:block;width:100%}
            .snn-consent__content:focus{outline:none}
        ';
    }

    /**
     * One stylesheet and one delegated handler, printed on the first block only.
     */
    private static function print_assets() {
        if ( self::$assets_printed ) {
            return;
        }
        self::$assets_printed = true;
        ?>
        <style><?php echo self::css(); ?></style>
        <noscript><style>.snn-consent__prompt{visibility:visible}.snn-consent__btn{display:none}</style></noscript>
        <script>
        (function(){
            if (window.snnConsent) return;

            var PREFIX = 'snn_consent_';
            var memory = {}; // used when localStorage is unavailable or the block is session only

            function isGranted(key){
                if (!key) return false;
                if (memory[key]) return true;
                try { return localStorage.getItem(PREFIX + key) === '1'; } catch(e) { return false; }
            }

            function grant(key, remember){
                if (!key) return;
                memory[key] = true;
                if (remember) {
                    try { localStorage.setItem(PREFIX + key, '1'); } catch(e) {}
                }
            }

            function decodePayload(encoded){
                var binary = atob(encoded);
                try {
                    var bytes = new Uint8Array(binary.length);
                    for (var i = 0; i < binary.length; i++) { bytes[i] = binary.charCodeAt(i); }
                    return new TextDecoder('utf-8').decode(bytes);
                } catch(e) {
                    return decodeURIComponent(escape(binary));
                }
            }

            function absolute(url){
                try { return new URL(url, location.href).href; } catch(e) { return url; }
            }

            function scriptAlreadyLoaded(url){
                var existing = document.getElementsByTagName('script');
                for (var i = 0; i < existing.length; i++) {
                    if (existing[i].src && existing[i].src === url) return true;
                }
                return false;
            }

            function unlock(root, moveFocus){
                if (!root || root.dataset.snnConsentDone) return;
                root.dataset.snnConsentDone = '1';

                var fragment;

                if (root.getAttribute('data-snn-consent') === 'base64') {
                    var carrier = document.createElement('template');
                    carrier.innerHTML = decodePayload(root.getAttribute('data-snn-payload') || '');
                    fragment = carrier.content.cloneNode(true);
                    root.removeAttribute('data-snn-payload');
                } else {
                    var template = root.querySelector(':scope > template.snn-consent__payload');
                    fragment = template ? template.content.cloneNode(true) : document.createDocumentFragment();
                }

                // Pull every script out before the markup reaches the document, so an
                // init snippet can never run ahead of the element it targets.
                var pending = [];
                fragment.querySelectorAll('script').forEach(function(node){
                    pending.push(node);
                    node.parentNode.removeChild(node);
                });

                var holder = document.createElement('div');
                holder.className = 'snn-consent__content';
                holder.setAttribute('tabindex', '-1');
                holder.appendChild(fragment);

                root.textContent = ''; // drops the prompt and the template together
                root.appendChild(holder);
                root.classList.add('snn-consent--loaded');

                // Re-create the scripts in their original order. async=false keeps a
                // loader library ahead of whatever depends on it.
                pending.forEach(function(original){
                    var src = original.getAttribute('src');
                    if (src && scriptAlreadyLoaded(absolute(src))) return;

                    var script = document.createElement('script');
                    for (var i = 0; i < original.attributes.length; i++) {
                        script.setAttribute(original.attributes[i].name, original.attributes[i].value);
                    }
                    if (src) { script.async = false; } else { script.text = original.textContent; }
                    holder.appendChild(script);
                });

                if (moveFocus) {
                    try { holder.focus({ preventScroll: true }); } catch(e) { holder.focus(); }
                }
            }

            function unlockKey(key){
                if (!key) return;
                document.querySelectorAll('.snn-consent[data-consent-key="' + key.replace(/"/g, '\\"') + '"]').forEach(function(root){
                    unlock(root, false);
                });
            }

            function scan(scope){
                var root = scope && scope.querySelectorAll ? scope : document;
                root.querySelectorAll('.snn-consent[data-snn-consent]').forEach(function(block){
                    if (block.dataset.snnConsentDone) return;
                    if (isGranted(block.getAttribute('data-consent-key'))) {
                        unlock(block, false);
                    } else {
                        block.classList.add('snn-consent--ready');
                    }
                });
            }

            document.addEventListener('click', function(event){
                var button = event.target && event.target.closest ? event.target.closest('.snn-consent__btn') : null;
                if (!button) return;

                var root = button.closest('.snn-consent');
                if (!root) return;

                event.preventDefault();

                var key = root.getAttribute('data-consent-key') || '';
                grant(key, root.getAttribute('data-remember') === '1');

                unlock(root, true);
                unlockKey(key);

                document.dispatchEvent(new CustomEvent('snn-consent-accepted', {
                    bubbles: true,
                    detail: { key: key, element: root }
                }));
            });

            // Lets an external "accept all" button drive the blocks.
            document.addEventListener('snn-consent-accept', function(event){
                var key = event.detail && event.detail.key;
                if (!key) return;
                grant(key, true);
                unlockKey(key);
            });

            // Another tab accepted the same key.
            window.addEventListener('storage', function(event){
                if (!event.key || event.key.indexOf(PREFIX) !== 0 || event.newValue !== '1') return;
                var key = event.key.slice(PREFIX.length);
                memory[key] = true;
                unlockKey(key);
            });

            // A block is only safe to unlock once the parser has finished it, so the
            // first pass waits for DOMContentLoaded. The prompt stays invisible until
            // then, which is what keeps consented content from flashing a button first.
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function(){ scan(document); });
            } else {
                scan(document);
            }

            // Blocks arriving through a Bricks AJAX response.
            document.addEventListener('bricks/ajax/end', function(){ scan(document); });
            document.addEventListener('bricks/ajax/query_result/completed', function(){ scan(document); });

            window.snnConsent = {
                scan: scan,
                accept: function(key){ grant(key, true); unlockKey(key); },
                isGranted: isGranted
            };
        })();
        </script>
        <?php
    }

    public static function render_builder() {
        ?>
        <script type="text/x-template" id="tmpl-bricks-element-snn-consent-cookie-block">
            <component :is="tag">
                <div class="snn-consent__prompt snn-consent--ready">
                    <div class="snn-consent__text" v-html="element.settings.consent_text"></div>
                    <button type="button" class="snn-consent__btn">{{ element.settings.button_label || '<?php echo esc_js( __( 'Accept & Load', 'snn' ) ); ?>' }}</button>
                </div>
                <bricks-element-children :element="element"/>
            </component>
        </script>
        <?php
    }
}

// The builder canvas renders nestable elements through Vue, so the shared
// stylesheet has to reach it separately.
add_action( 'wp_head', function() {
    if ( function_exists( 'bricks_is_builder' ) && bricks_is_builder() ) {
        echo '<style>' . Snn_Consent_Cookie_Block::css() . '</style>';
    }
} );
