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
        return esc_html__( 'Text Action Social Share', 'bricks' );
    }

    public function set_controls() {
        $this->controls['help'] = [
            'tab'     => 'content',
            'type'    => 'info',
            'content' => esc_html__(
                'Add action buttons for selected text. Use {text} in link - it will be replaced with user selection. Example: https://twitter.com/intent/tweet?text={text}', 'bricks'
            ),
        ];

        $this->controls['dom_selector'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'DOM Selector', 'bricks' ),
            'type'        => 'text',
            'default'     => 'comment',
            'placeholder' => esc_html__( 'e.g. .selectable-comment-content-wrapper', 'bricks' ),
            'description' => esc_html__(
                'Only allow text selection & actions inside these elements. Use any valid CSS selector (e.g. .my-comments, #post-content, .comment-body). Leave blank for whole page.', 'bricks'
            ),
        ];

        $this->controls['actions'] = [
            'tab'          => 'content',
            'label'        => esc_html__( 'Actions', 'bricks' ),
            'type'         => 'repeater',
            'titleProperty'=> 'label',
            'fields'       => [
                'label' => [
                    'label' => esc_html__( 'Label', 'bricks' ),
                    'type'  => 'text',
                ],
                'icon' => [
                    'label' => esc_html__( 'Icon', 'bricks' ),
                    'type'  => 'icon',
                    'default' => [
                        'library' => 'fontawesome',
                        'icon'    => 'fas fa-share-alt',
                    ],
                ],
                'link' => [
                    'label'       => esc_html__( 'Action Link (use {text})', 'bricks' ),
                    'type'        => 'text',
                    'placeholder' => esc_html__( 'https://twitter.com/intent/tweet?text={text}', 'bricks' ),
                ],
                'target' => [
                    'label' => esc_html__( 'Open in new tab?', 'bricks' ),
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
                    'label' => 'Email',
                    'icon'  => [ 'library' => 'themify', 'icon' => 'ti-email' ],
                    'link'  => 'mailto:?body={text}',
                    'target'=> false,
                ],
                [
                    'label' => 'Twitter',
                    'icon'  => [ 'library' => 'fontawesomeBrands', 'icon' => 'fab fa-x-twitter' ],
                    'link'  => 'https://twitter.com/intent/tweet?text={text}',
                    'target'=> true,
                ],
                [
                    'label' => 'LinkedIn',
                    'icon'  => [ 'library' => 'fontawesomeBrands', 'icon' => 'fab fa-linkedin-in' ],
                    'link'  => 'https://www.linkedin.com/shareArticle?mini=true&summary={text}',
                    'target'=> true,
                ],
            ],
        ];

        $this->controls['offsetY'] = [
            'tab'     => 'style',
            'label'   => esc_html__( 'Vertical offset (px)', 'bricks' ),
            'type'    => 'number',
            'default' => 5,
        ];

        $this->controls['offsetX'] = [
            'tab'     => 'style',
            'label'   => esc_html__( 'Horizontal offset (px)', 'bricks' ),
            'type'    => 'number',
            'default' => 0,
        ];

        $this->controls['bar_style'] = [
            'tab'   => 'style',
            'label' => esc_html__( 'Bar Style', 'bricks' ),
            'type'  => 'css',
            'css'   => [
                [
                    'property' => 'background',
                    'selector' => '.snn-text-action-share-bar',
                ],
                [
                    'property' => 'box-shadow',
                    'selector' => '.snn-text-action-share-bar',
                ],
                [
                    'property' => 'border-radius',
                    'selector' => '.snn-text-action-share-bar',
                ],
            ],
        ];

        $this->controls['icon_color'] = [
            'tab'   => 'style',
            'label' => esc_html__( 'Icon Color', 'bricks' ),
            'type'  => 'color',
            'css'   => [
                [
                    'property' => 'color',
                    'selector' => '.snn-text-action-share-bar a',
                ],
            ],
            'default' => [
                'hex' => '#23282d',
            ],
        ];
    }

    public function render() {
        $actions = $this->settings['actions'] ?? [];
        $offsetY = isset($this->settings['offsetY']) ? intval($this->settings['offsetY']) : 5;
        $offsetX = isset($this->settings['offsetX']) ? intval($this->settings['offsetX']) : 0;
        $dom_selector = trim($this->settings['dom_selector'] ?? 'comment');

        $uniqid = 'snn-text-action-share-bar-' . uniqid();

        $this->set_attribute('_root', 'class', [ 'snn-text-action-share-bar', $uniqid ]);
        $this->set_attribute('_root', 'style', 'display:none;position:fixed;z-index:99999;top:0;left:0;opacity:1;pointer-events:auto;');

        echo '<div ' . $this->render_attributes('_root') . '>';
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
        ?>
        <script>
        (function() {
            const bar = document.querySelector('.<?php echo esc_js($uniqid); ?>');
            if (!bar) return;
            const actions = <?php echo json_encode(array_values($actions)); ?>;
            let selectedText = '';
            let selectionRect = null;
            const domSelector = <?php echo json_encode($dom_selector); ?>.trim();

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
                bar.querySelectorAll('.snn-text-action-share-btn').forEach((btn, idx) => {
                    if (!actions[idx]) return;
                    let url = actions[idx].link || '#';
                    url = url.replace(/\{text\}/g, encodeURIComponent(selectedText));
                    btn.setAttribute('href', url);
                });
            }

            function showBar(rect) {
                bar.style.display = 'flex';
                bar.style.position = 'fixed';
                let top = rect.bottom + <?php echo intval($offsetY); ?>;
                let left = rect.left + <?php echo intval($offsetX); ?>;
                if (left + bar.offsetWidth > window.innerWidth) {
                    left = window.innerWidth - bar.offsetWidth - 10;
                }
                if (top + bar.offsetHeight > window.innerHeight) {
                    top = rect.top - bar.offsetHeight - <?php echo intval($offsetY); ?>;
                    if (top < 0) top = 5;
                }
                bar.style.top = top + 'px';
                bar.style.left = left + 'px';
            }

            function hideBar() {
                bar.style.display = 'none';
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
        .snn-text-action-share-bar {
            gap: 8px;
            padding: 6px 12px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            align-items: center;
            pointer-events: auto;
            min-width: 1px;
        }
        .snn-text-action-share-bar a.snn-text-action-share-btn {
            display: flex;
            align-items: center;
            padding: 4px 8px;
            font-size: 1.2em;
            color: inherit;
            background: none;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.15s;
        }
        .snn-text-action-share-bar a.snn-text-action-share-btn:hover,
        .snn-text-action-share-bar a.snn-text-action-share-btn:focus {
            background: #f1f3f6;
            color: #0073aa;
            outline: none;
        }
        .snn-text-action-share-bar i {
            pointer-events: none;
        }
        </style>
        <?php
    }
}
?>
