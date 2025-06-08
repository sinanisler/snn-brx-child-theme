<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
use Bricks\Element;

class SNN_Text_Action_Share extends Element {
    public $category     = 'snn';
    public $name         = 'snn-text-action-share';
    public $icon         = 'ti-share';
    public $css_selector = '.snn-text-action-share-bar';
    public $scripts      = [];
    public $nestable     = false;

    public function get_label() {
        return esc_html__( 'Text Select Action Social Share', 'snn' );
    }

    public function set_controls() {
        $this->controls['help'] = [
            'tab'     => 'content',
            'type'    => 'info',
            'content' => esc_html__(
                'Add action buttons for selected text. Use {text} and {url} in link - they will be replaced with user selection and page URL. Example: https://twitter.com/intent/tweet?text={text}%20{url}', 'snn'
            ),
        ];

        $this->controls['dom_selector'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'DOM Selector', 'snn' ),
            'type'        => 'text',
            'default'     => 'comment',
            'placeholder' => esc_html__( 'e.g. .selectable-comment-content-wrapper', 'snn' ),
            'description' => esc_html__(
                'Only allow text selection & actions inside these elements. Use any valid CSS selector (e.g. .my-comments, #post-content, .comment-body). Leave blank for whole page.', 'snn'
            ),
        ];

        $this->controls['actions'] = [
            'tab'          => 'content',
            'label'        => esc_html__( 'Actions', 'snn' ),
            'type'         => 'repeater',
            'titleProperty'=> 'label',
            'fields'       => [
                'label' => [
                    'label' => esc_html__( 'Label', 'snn' ),
                    'type'  => 'text',
                ],
                'icon' => [
                    'label' => esc_html__( 'Icon', 'snn' ),
                    'type'  => 'icon',
                    'default' => [
                        'library' => 'fontawesome',
                        'icon'    => 'fas fa-share-alt',
                    ],
                ],
                'link' => [
                    'label'       => esc_html__( 'Action Link (use {text} and/or {url})', 'snn' ),
                    'type'        => 'text',
                    'placeholder' => esc_html__( 'https://twitter.com/intent/tweet?text={text} {url}', 'snn' ),
                ],
                'target' => [
                    'label' => esc_html__( 'Open in new tab?', 'snn' ),
                    'type'  => 'checkbox',
                    'default' => true,
                ],
            ],
            'default' => [
                [
                    'label' => 'Reply',
                    'icon'  => [ 'library' => 'themify', 'icon' => 'ti-comment' ],
                    'link'  => '#reply-{text}',
                    'target'=> false,
                ],
                [
                    'label' => 'Twitter',
                    'icon'  => [ 'library' => 'fontawesomeBrands', 'icon' => 'fab fa-x-twitter' ],
                    'link'  => 'https://twitter.com/intent/tweet?text={text}%20{url}',
                    'target'=> true,
                ],
                [
                    'label' => 'WhatsApp',
                    'icon'  => [ 'library' => 'fontawesomeBrands', 'icon' => 'fab fa-whatsapp' ],
                    'link'  => 'https://wa.me/?text={text}%20{url}',
                    'target'=> true,
                ],
                [
                    'label' => 'Email',
                    'icon'  => [ 'library' => 'themify', 'icon' => 'ti-email' ],
                    'link'  => 'mailto:?subject=Shared%20from%20this%20page&body={text}%0A%0A{url}',
                    'target'=> false,
                ],
            ],
        ];

    }

    public function render() {
        $actions = $this->settings['actions'] ?? [];
        $offsetY = isset($this->settings['offsetY']) ? intval($this->settings['offsetY']) : 5;
        $offsetX = isset($this->settings['offsetX']) ? intval($this->settings['offsetX']) : 0;
        $dom_selector = trim($this->settings['dom_selector'] ?? 'comment');
        $wrapper_bg_color = $this->settings['wrapper_bg_color']['hex'] ?? '#fff';
        $icon_color = $this->settings['icon_color']['hex'] ?? '#23282d';

        $uniqid = 'snn-text-action-share-bar-' . uniqid();

        $this->set_attribute('_root', 'class', [ 'brxe-snn-text-action-share', $uniqid ]);
        // no inline style for display/position

        echo '<div ' . $this->render_attributes('_root') . '>';
        echo '<div class="snn-text-action-share-bar">';
        foreach($actions as $idx => $item) {
            $icon = isset($item['icon']) ? $item['icon'] : null;
            $label = esc_html( $item['label'] ?? '' );
            $href = esc_url( $item['link'] ?? '#' );
            $target = !empty($item['target']) ? ' target="_blank" rel="noopener"' : '';
            echo '<a href="#" class="snn-text-action-share-btn" data-action-index="'.esc_attr($idx).'" '.$target.' aria-label="'.esc_attr($label).'" tabindex="-1">';
                if ($icon) {
                    if ( function_exists( '\Bricks\Helpers::render_control_icon' ) ) {
                        \Bricks\Helpers::render_control_icon($icon, []);
                    } else if (!empty($icon['icon'])) {
                        echo '<i class="'.esc_attr($icon['icon']).'"></i>';
                    }
                }
                echo '<span class="snn-text-action-share-label" style="display:none;">'.esc_html($label).'</span>';
            echo '</a>';
        }
        echo '</div>';
        echo '</div>';
        ?>
        <script>
        (function() {
            const wrapper = document.querySelector('.<?php echo esc_js($uniqid); ?>');
            if (!wrapper) return;
            const bar = wrapper.querySelector('.snn-text-action-share-bar');
            const actions = <?php echo json_encode(array_values($actions)); ?>;
            let selectedText = '';
            let selectionRect = null;
            const domSelector = <?php echo json_encode($dom_selector); ?>.trim();
            const offsetY = <?php echo intval($offsetY); ?>;
            const offsetX = <?php echo intval($offsetX); ?>;

            function isSelectionInsideAllowedArea() {
                if (!domSelector) return true; // all page
                let sel = window.getSelection();
                if (!sel || sel.isCollapsed) return false;
                if (sel.rangeCount < 1) return false;
                const range = sel.getRangeAt(0);
                let node = range.commonAncestorContainer;
                // Climb up to element node
                while (node && node.nodeType !== 1) {
                    node = node.parentNode;
                }
                if (!node) return false;
                // Accept if selector matches
                try {
                    if (node.matches(domSelector)) return true;
                    if (node.closest && node.closest(domSelector)) return true;
                } catch(e) {}
                return false;
            }

            function updateBarLinks() {
                var url = location.href;
                bar.querySelectorAll('.snn-text-action-share-btn').forEach((btn, idx) => {
                    if (!actions[idx]) return;
                    let linkTemplate = actions[idx].link || '#';
                    let link = linkTemplate
                        .replace(/\{text\}/g, encodeURIComponent(selectedText))
                        .replace(/\{url\}/g, encodeURIComponent(url));
                    btn.setAttribute('href', link);
                });
            }

            function showBar(rect) {
                wrapper.classList.add('snn-text-action-share-bar-visible');
                // Position using CSS variables
                wrapper.style.setProperty('--snn-bar-top', (rect.bottom + offsetY) + 'px');
                wrapper.style.setProperty('--snn-bar-left', (rect.left + offsetX) + 'px');
                // Responsive adjustment
                setTimeout(() => {
                    const barRect = bar.getBoundingClientRect();
                    let left = rect.left + offsetX;
                    let top = rect.bottom + offsetY;
                    if (left + barRect.width > window.innerWidth) {
                        left = window.innerWidth - barRect.width - 10;
                    }
                    if (top + barRect.height > window.innerHeight) {
                        top = rect.top - barRect.height - offsetY;
                        if (top < 0) top = 5;
                    }
                    wrapper.style.setProperty('--snn-bar-top', top + 'px');
                    wrapper.style.setProperty('--snn-bar-left', left + 'px');
                }, 0);
            }

            function hideBar() {
                wrapper.classList.remove('snn-text-action-share-bar-visible');
            }

            function getSelectedTextAndRect() {
                let sel = window.getSelection();
                if (sel && !sel.isCollapsed && sel.rangeCount > 0) {
                    selectedText = sel.toString();
                    if (selectedText.trim().length < 1) return false;
                    let range = sel.getRangeAt(0);
                    selectionRect = range.getBoundingClientRect();
                    return true;
                }
                return false;
            }

            document.addEventListener('selectionchange', function() {
                setTimeout(function() {
                    if (getSelectedTextAndRect() && isSelectionInsideAllowedArea()) {
                        updateBarLinks();
                        showBar(selectionRect);
                    } else {
                        hideBar();
                    }
                }, 20);
            });

            document.addEventListener('mousedown', function(e) {
                if (!bar.contains(e.target)) hideBar();
            });

            window.addEventListener('scroll', hideBar, true);

            // === REPLY BUTTON CUSTOM ACTION ===
            bar.querySelectorAll('.snn-text-action-share-btn').forEach((btn, idx) => {
                btn.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        btn.click();
                    }
                });

                // Only intercept click for Reply (label or #reply-)
                if (
                    actions[idx] &&
                    (actions[idx].label && actions[idx].label.toLowerCase() === 'reply' || (actions[idx].link && actions[idx].link.indexOf('#reply-') === 0))
                ) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        if (!selectedText.trim()) return;

                        // Find editor by ID
                        var editor = document.getElementById('snn-comment-editor-editor');
                        if (editor) {
                            // Focus and inject blockquote at cursor or append
                            editor.focus();
                            var blockquoteHtml = '<blockquote>' + selectedText.replace(/</g, "&lt;").replace(/>/g, "&gt;") + '</blockquote><br>';
                            // Insert at caret if possible, else append
                            var sel = window.getSelection();
                            if (sel && sel.rangeCount && editor.contains(sel.anchorNode)) {
                                var range = sel.getRangeAt(0);
                                range.deleteContents();
                                var temp = document.createElement('div');
                                temp.innerHTML = blockquoteHtml;
                                var frag = document.createDocumentFragment(), node, lastNode;
                                while ((node = temp.firstChild)) {
                                    lastNode = frag.appendChild(node);
                                }
                                range.insertNode(frag);
                                // Move caret after blockquote
                                if (lastNode) {
                                    range.setStartAfter(lastNode);
                                    range.collapse(true);
                                    sel.removeAllRanges();
                                    sel.addRange(range);
                                }
                            } else {
                                // Just append at the end
                                editor.innerHTML += blockquoteHtml;
                            }
                            // Trigger input event to sync textarea
                            editor.dispatchEvent(new Event('input', {bubbles:true}));
                        }
                        hideBar();
                    });
                }
            });

        })();
        </script>
        <style>
        .brxe-snn-text-action-share {
            /* Position defaults, will be set by JS using variables */
            position: fixed;
            z-index: 99999;
            pointer-events: none;
            top: 0;
            left: 0;
            opacity: 0;
            transition: opacity 0.18s;
        }
        .brxe-snn-text-action-share.snn-text-action-share-bar-visible {
            pointer-events: auto;
            opacity: 1;
        }
        .brxe-snn-text-action-share {
            top: var(--snn-bar-top, 0px) !important;
            left: var(--snn-bar-left, 0px) !important;
        }
        .brxe-snn-text-action-share .snn-text-action-share-bar {
            display: flex;
            gap: 8px;
            padding: 6px 12px;
            background: <?php echo esc_attr($wrapper_bg_color); ?>;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            align-items: center;
            min-width: 1px;
            pointer-events: auto;
        }
        .brxe-snn-text-action-share .snn-text-action-share-bar a.snn-text-action-share-btn {
            display: flex;
            align-items: center;
            padding: 4px 8px;
            font-size: 1.2em;
            color: <?php echo esc_attr($icon_color); ?>;
            background: none;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
        }
        .brxe-snn-text-action-share .snn-text-action-share-bar a.snn-text-action-share-btn:hover,
        .brxe-snn-text-action-share .snn-text-action-share-bar a.snn-text-action-share-btn:focus {
            background: #f1f3f6;
            color: #0073aa;
            outline: none;
        }
        .brxe-snn-text-action-share .snn-text-action-share-bar i {
            pointer-events: none;
            color: inherit !important;
        }
        </style>
        <?php
    }
}
?>
