<?php 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use Bricks\Element;

class Snn_Event_Action_Selector extends Element {

    public $category     = 'snn';
    public $name         = 'event-action-selector';
    public $icon         = 'ti-reload';
    public $css_selector = '.snn-event-action-selector';
    public $scripts      = [];
    public $nestable     = false;

    public function get_label() {
        return esc_html__( 'Event ⇄ Action', 'snn' );
    }

    public function set_controls() {

        $events = [
            "click","dblclick","mousedown","mouseup","mousemove","mouseenter","mouseleave",
            "mouseover","mouseout","contextmenu",
            "keydown","keyup","keypress",
            "submit","reset","change","input","focus","blur","select","invalid",
            "load","DOMContentLoaded","resize","scroll","unload","beforeunload",
            "hashchange","popstate","error",
            "copy","cut","paste",
            "touchstart","touchmove","touchend","touchcancel",
            "drag","dragstart","dragend","dragenter","dragleave","dragover","drop",
            "play","pause","ended","volumechange","timeupdate","seeking","seeked",
            "animationstart","animationend","animationiteration",
            "transitionend","wheel","pointerdown","pointerup","pointermove",
            "pointerenter","pointerleave",
            "bricks/form/submit","bricks/form/success","bricks/form/error",
            "bricks/tabs/changed",
            "bricks/accordion/open","bricks/accordion/close",
            "bricks/popup/open","bricks/popup/close",
            "bricks/ajax/popup/start","bricks/ajax/popup/end","bricks/ajax/popup/loaded",
            "bricks/ajax/start","bricks/ajax/end",
            "bricks/ajax/pagination/completed","bricks/ajax/load_page/completed",
            "bricks/ajax/query_result/completed","bricks/ajax/query_result/displayed",
            "focus / blur","mouseenter / mouseleave","mouseover / mouseout",
            "pointerenter / pointerleave","mousedown / mouseup","touchstart / touchend",
            "dragstart / dragend","play / pause","animationstart / animationend"
        ];

        $actions = [
            "Show/Hide Element",
            "Add Class",
            "Remove Class",
            "Toggle Class",
            "Remove Element",
            "Enable/Disable Element",
            "Prevent Default",
            "Stop Propagation",
            "Scroll Into View",
            "Focus Element",
            "Blur Element",
            "Toggle Fullscreen",
            // More actions for edge cases & power users:
            "Clone Element",
            "Replace HTML",
            "Set Attribute",
            "Remove Attribute",
            "Dispatch Custom Event",
            "Download File",
            "Change Text",
            "Append HTML",
            "Prepend HTML",
            "Toggle Attribute",
            "Toggle Style Property",
            "Play/Pause Media",
            "Capture Screenshot",
            "Copy To Clipboard",
            "Set Value",
            "Log To Console",
            "Custom JS Function",
        ];

        // --- Single Controls ---
        $this->controls['trigger_selector'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'DOM selector (event trigger)', 'snn' ),
            'type'        => 'text',
            'placeholder' => '.btn, #my-id, header a, …',
            'default'     => '.custom-selector',
        ];

        $this->controls['event'] = [
            'tab'        => 'content',
            'label'      => esc_html__( 'Event', 'snn' ),
            'type'       => 'select',
            'options'    => array_combine( $events, $events ),
            'searchable' => true,
            'clearable'  => true,
            'default'    => 'click',
        ];

        // --- Repeater for Actions ---
        $this->controls['actions_repeater'] = [
            'tab'           => 'content',
            'label'         => esc_html__( 'Actions', 'snn' ),
            'type'          => 'repeater',
            'titleProperty' => 'action',
            'placeholder'   => esc_html__( 'Select Action', 'snn' ),
            'fields'        => [
                'target_selector' => [
                    'label'       => esc_html__( 'DOM selector (action target)', 'snn' ),
                    'type'        => 'text',
                    'placeholder' => '.box, #same-id, body, …',
                    'default'     => '.demo-target',
                ],
                'action' => [
                    'label'      => esc_html__( 'Action', 'snn' ),
                    'type'       => 'select',
                    'options'    => array_combine( $actions, $actions ),
                    'searchable' => true,
                    'clearable'  => true,
                    'default'    => 'Show/Hide Element',
                ],
                'class_name' => [
                    'label'       => esc_html__( 'Class Name', 'snn' ),
                    'type'        => 'text',
                    'default'     => 'active',
                    'required'    => [['action', '=', ['Add Class', 'Remove Class', 'Toggle Class']]],
                    'placeholder' => 'e.g. highlight, visible, ...',
                ],
                'attribute_name' => [
                    'label'       => esc_html__( 'Attribute Name', 'snn' ),
                    'type'        => 'text',
                    'default'     => 'data-active',
                    'required'    => [['action', '=', ['Set Attribute', 'Remove Attribute', 'Toggle Attribute']]],
                    'placeholder' => 'e.g. aria-expanded, data-toggle, ...',
                ],
                'attribute_value' => [
                    'label'       => esc_html__( 'Attribute Value', 'snn' ),
                    'type'        => 'text',
                    'default'     => 'true',
                    'required'    => [['action', '=', ['Set Attribute', 'Toggle Attribute']]],
                    'placeholder' => 'e.g. true, false, open, ...',
                ],
                'replace_html' => [
                    'label'       => esc_html__( 'Replace HTML', 'snn' ),
                    'type'        => 'textarea',
                    'required'    => [['action', '=', ['Replace HTML']]],
                    'placeholder' => 'New HTML content...',
                ],
                'append_html' => [
                    'label'       => esc_html__( 'Append HTML', 'snn' ),
                    'type'        => 'textarea',
                    'required'    => [['action', '=', ['Append HTML']]],
                    'placeholder' => 'HTML to append...',
                ],
                'prepend_html' => [
                    'label'       => esc_html__( 'Prepend HTML', 'snn' ),
                    'type'        => 'textarea',
                    'required'    => [['action', '=', ['Prepend HTML']]],
                    'placeholder' => 'HTML to prepend...',
                ],
                 'style_property' => [
                    'label'       => esc_html__( 'Style Property', 'snn' ),
                    'type'        => 'text',
                    'default'     => 'display',
                    'required'    => [['action', '=', ['Toggle Style Property']]],
                    'placeholder' => 'e.g. color, display, ...',
                ],
                'style_value' => [
                    'label'       => esc_html__( 'Style Value', 'snn' ),
                    'type'        => 'text',
                    'default'     => 'block',
                    'required'    => [['action', '=', ['Toggle Style Property']]],
                    'placeholder' => 'e.g. red, none, block, ...',
                ],
                'custom_event_name' => [
                    'label'    => esc_html__( 'Custom Event Name', 'snn' ),
                    'type'     => 'text',
                    'default'  => 'my-custom-event',
                    'required' => [['action', '=', ['Dispatch Custom Event']]],
                ],
                'custom_js_function' => [
                    'label'       => esc_html__( 'Custom JS Function Body', 'snn' ),
                    'type'        => 'textarea',
                    'required'    => [['action', '=', ['Custom JS Function']]],
                    'placeholder' => 'function(targets, event) { ... }',
                ],
                'text_value' => [
                    'label'       => esc_html__( 'Text Value', 'snn' ),
                    'type'        => 'text',
                    'required'    => [['action', '=', ['Change Text', 'Set Value']]],
                    'placeholder' => 'New text/value...',
                ],
                'download_url' => [
                    'label'       => esc_html__( 'Download File URL', 'snn' ),
                    'type'        => 'text',
                    'required'    => [['action', '=', ['Download File']]],
                    'placeholder' => 'https://example.com/file.pdf',
                ],
                'copy_value' => [
                    'label'       => esc_html__( 'Copy This Text', 'snn' ),
                    'type'        => 'text',
                    'required'    => [['action', '=', ['Copy To Clipboard']]],
                    'placeholder' => 'Text to copy...',
                ],
                'scroll_behavior' => [
                    'label'    => esc_html__( 'Scroll Behavior', 'snn' ),
                    'type'     => 'select',
                    'options'  => ['smooth' => 'Smooth', 'auto' => 'Auto'],
                    'default'  => 'smooth',
                    'required' => [['action', '=', 'Scroll Into View']],
                ],
                'scroll_block' => [
                    'label'    => esc_html__( 'Scroll To Position', 'snn' ),
                    'type'     => 'select',
                    'options'  => ['center' => 'Center', 'start' => 'Start', 'end' => 'End', 'nearest' => 'Nearest'],
                    'default'  => 'center',
                    'required' => [['action', '=', 'Scroll Into View']],
                ],
            ],
        ];
    }

    public function render() {
        $settings = $this->settings;

        $trigger_sel = isset( $settings['trigger_selector'] ) ? trim( $settings['trigger_selector'] ) : '';
        $event       = isset( $settings['event'] ) ? $settings['event'] : '';
        $actions     = isset( $settings['actions_repeater'] ) ? $settings['actions_repeater'] : [];

        if ( ! $trigger_sel || ! $event || empty($actions) ) {
            echo $this->render_element_placeholder( [
                'icon-class' => 'ti-flash',
                'text'       => esc_html__( 'Define trigger selector, event and at least one action.', 'snn' ),
            ] );
            return;
        }

        $uid = 'eas-' . uniqid();
        $this->set_attribute( '_root', 'class', [ 'snn-event-action-selector', $uid ] );
        echo '<div ' . $this->render_attributes( '_root' ) . '></div>'; // logic only element

        // Prepare script data
        $script_data = [
            'uid'         => $uid,
            'triggerSel'  => $trigger_sel,
            'evType'      => $event,
            'actions'     => $actions,
        ];

        // Add script to footer
        add_action( 'wp_footer', function() use ( $script_data ) {
            ?>
            <script>
            (function(){
                const uid        = <?php echo json_encode( $script_data['uid'] ); ?>;
                const root       = document.querySelector('.' + uid);
                if (!root) return;

                const triggerSel = <?php echo json_encode( $script_data['triggerSel'] ); ?>;
                const evType     = <?php echo json_encode( $script_data['evType'] ); ?>;
                const actions    = <?php echo json_encode( $script_data['actions'] ); ?>;

                function performAction(actionConfig, targets, e) {
                    const {
                        action,
                        class_name = 'active',
                        attribute_name = '',
                        attribute_value = '',
                        replace_html = '',
                        append_html = '',
                        prepend_html = '',
                        style_property = '',
                        style_value = '',
                        custom_event_name = '',
                        custom_js_function = '',
                        text_value = '',
                        download_url = '',
                        copy_value = '',
                        scroll_behavior = 'smooth',
                        scroll_block = 'center'
                    } = actionConfig;

                    targets.forEach(el => {
                        switch(action) {
                            case 'Show/Hide Element':
                                el.style.display = (el.style.display === 'none' ? '' : 'none');
                                break;
                            case 'Add Class':
                                if(class_name) el.classList.add(class_name);
                                break;
                            case 'Remove Class':
                                if(class_name) el.classList.remove(class_name);
                                break;
                            case 'Toggle Class':
                                if(class_name) el.classList.toggle(class_name);
                                break;
                            case 'Remove Element':
                                el.remove();
                                break;
                            case 'Enable/Disable Element':
                                el.disabled = !el.disabled;
                                break;
                            case 'Prevent Default':
                                if(e) e.preventDefault();
                                break;
                            case 'Stop Propagation':
                                if(e) e.stopPropagation();
                                break;
                            case 'Scroll Into View':
                                el.scrollIntoView({ behavior: scroll_behavior, block: scroll_block });
                                break;
                            case 'Focus Element':
                                el.focus({ preventScroll: false });
                                break;
                            case 'Blur Element':
                                el.blur();
                                break;
                            case 'Toggle Fullscreen':
                                if (!document.fullscreenElement) { el.requestFullscreen().catch(()=>{}); }
                                else { document.exitFullscreen(); }
                                break;
                            case 'Clone Element':
                                el.parentNode && el.parentNode.insertBefore(el.cloneNode(true), el.nextSibling);
                                break;
                            case 'Replace HTML':
                                el.innerHTML = replace_html || '';
                                break;
                            case 'Set Attribute':
                                if(attribute_name) el.setAttribute(attribute_name, attribute_value || '');
                                break;
                            case 'Remove Attribute':
                                if(attribute_name) el.removeAttribute(attribute_name);
                                break;
                            case 'Toggle Attribute':
                                if(attribute_name) {
                                    if(el.hasAttribute(attribute_name)) el.removeAttribute(attribute_name);
                                    else el.setAttribute(attribute_name, attribute_value || '');
                                }
                                break;
                            case 'Dispatch Custom Event':
                                if(custom_event_name) el.dispatchEvent(new CustomEvent(custom_event_name, { bubbles: true }));
                                break;
                            case 'Download File':
                                if(download_url) {
                                    const a = document.createElement('a');
                                    a.href = download_url;
                                    a.download = '';
                                    document.body.appendChild(a);
                                    a.click();
                                    document.body.removeChild(a);
                                }
                                break;
                            case 'Change Text':
                                el.textContent = text_value || '';
                                break;
                            case 'Append HTML':
                                el.insertAdjacentHTML('beforeend', append_html || '');
                                break;
                            case 'Prepend HTML':
                                el.insertAdjacentHTML('afterbegin', prepend_html || '');
                                break;
                            case 'Toggle Style Property':
                                if(style_property) {
                                    if(el.style[style_property]) el.style[style_property] = '';
                                    else el.style[style_property] = style_value || '';
                                }
                                break;
                            case 'Play/Pause Media':
                                if(el.paused !== undefined) {
                                    if(el.paused) el.play();
                                    else el.pause();
                                }
                                break;
                            case 'Capture Screenshot':
                                if(window.html2canvas) {
                                    html2canvas(el).then(canvas => {
                                        const a = document.createElement('a');
                                        a.href = canvas.toDataURL();
                                        a.download = 'screenshot.png';
                                        a.click();
                                    });
                                } else {
                                    console.warn('html2canvas.js library is required for this action.');
                                }
                                break;
                            case 'Copy To Clipboard':
                                if(copy_value && navigator.clipboard) {
                                    navigator.clipboard.writeText(copy_value).catch(err => console.warn('Copy to clipboard failed:', err));
                                }
                                break;
                            case 'Set Value':
                                if('value' in el) el.value = text_value || '';
                                break;
                            case 'Log To Console':
                                console.log('[Event⇄Action]', { target: el, event: e, config: actionConfig });
                                break;
                            case 'Custom JS Function':
                                try {
                                    const fn = new Function('targets', 'event', custom_js_function || '');
                                    fn([el], e);
                                } catch(err) {
                                    console.warn('Error in custom JS function:', err);
                                }
                                break;
                            default:
                                console.warn('Action "' + action + '" not implemented.');
                        }
                    });
                }

                // Check if this is a Bricks custom event
                const isBricksEvent = evType.startsWith('bricks/');

                if (isBricksEvent) {
                    // Handle Bricks custom events with document.addEventListener
                    document.addEventListener(evType, function(e) {
                        actions.forEach(actionConfig => {
                            const targetSel = actionConfig.target_selector;
                            const targets = targetSel ? document.querySelectorAll(targetSel) : [];
                            if(targets.length) {
                                performAction(actionConfig, Array.from(targets), e);
                            } else if (!targetSel) {
                                console.warn('[Event⇄Action] Target selector required for Bricks events');
                            } else {
                                console.warn('[Event⇄Action] No elements match target selector: "' + targetSel + '"');
                            }
                        });
                    });
                } else {
                    // Handle regular DOM events
                    const triggers = document.querySelectorAll(triggerSel);
                    if(!triggers.length) {
                        console.warn('[Event⇄Action] No elements match trigger selector: "' + triggerSel + '"');
                        return;
                    }

                    // Check if this is a paired event (e.g., "focus / blur")
                    const isPairedEvent = evType.includes(' / ');
                    const eventTypes = isPairedEvent ? evType.split(' / ').map(e => e.trim()) : [evType];

                    triggers.forEach(tr => {
                        // Add listener for each event in the pair (or single event)
                        eventTypes.forEach(eventType => {
                            tr.addEventListener(eventType, function(e) {
                                // Special handling for events that can fire when interacting with child elements
                                if (eventType === 'blur' || eventType === 'mouseout' || eventType === 'mouseleave' || eventType === 'pointerleave') {
                                    setTimeout(() => {
                                        actions.forEach(actionConfig => {
                                            const targetSel = actionConfig.target_selector;
                                            const targets = targetSel ? document.querySelectorAll(targetSel) : [tr];

                                            let isStillInTargets = false;

                                            if (eventType === 'blur') {
                                                // Check if focus is still within action targets
                                                const newFocus = document.activeElement;
                                                isStillInTargets = Array.from(targets).some(t =>
                                                    t.contains(newFocus) || t === newFocus
                                                );
                                            } else {
                                                // For mouse/pointer events, check if mouse is still hovering over targets
                                                const hoveredElements = document.querySelectorAll(':hover');
                                                isStillInTargets = Array.from(targets).some(t =>
                                                    Array.from(hoveredElements).some(h => h === t || t.contains(h))
                                                );
                                            }

                                            // Only perform action if interaction moved outside all targets
                                            if (!isStillInTargets && targets.length) {
                                                performAction(actionConfig, Array.from(targets), e);
                                            }
                                        });
                                    }, 150);
                                } else {
                                    // Normal handling for other events
                                    actions.forEach(actionConfig => {
                                        const targetSel = actionConfig.target_selector;
                                        const targets = targetSel ? document.querySelectorAll(targetSel) : [tr];
                                        if(targets.length) {
                                            performAction(actionConfig, Array.from(targets), e);
                                        } else {
                                            console.warn('[Event⇄Action] No elements match target selector: "' + targetSel + '"');
                                        }
                                    });
                                }
                            });
                        });
                    });
                }
            })();
            </script>
            <?php
        }, 99 );
    }
}
?>