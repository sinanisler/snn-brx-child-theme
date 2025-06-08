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
            "pointerenter","pointerleave"
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

        // Controls (kept same)
        $this->controls['trigger_selector'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'DOM selector (event trigger)', 'snn' ),
            'type'        => 'text',
            'placeholder' => '.btn, #my-id, header a, …',
            'default'     => '.custom-selector',
        ];

        $this->controls['target_selector'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'DOM selector (action target)', 'snn' ),
            'type'        => 'text',
            'placeholder' => '.box, #same-id, body, …',
            'default'     => '.demo-target',
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

        $this->controls['action'] = [
            'tab'        => 'content',
            'label'      => esc_html__( 'Action', 'snn' ),
            'type'       => 'select',
            'options'    => array_combine( $actions, $actions ),
            'searchable' => true,
            'clearable'  => true,
            'default'    => 'Show/Hide Element',
        ];

        $this->controls['class_name'] = [
            'tab'      => 'content',
            'label'    => esc_html__( 'Class Name', 'snn' ),
            'type'     => 'text',
            'default'  => 'active',
            'required' => [
                ['action', '=', ['Add Class', 'Remove Class', 'Toggle Class']],
            ],
            'placeholder' => 'e.g. highlight, visible, ...',
        ];

        $this->controls['attribute_name'] = [
            'tab'      => 'content',
            'label'    => esc_html__( 'Attribute Name', 'snn' ),
            'type'     => 'text',
            'default'  => 'data-active',
            'required' => [
                ['action', '=', ['Set Attribute', 'Remove Attribute', 'Toggle Attribute']],
            ],
            'placeholder' => 'e.g. aria-expanded, data-toggle, ...',
        ];

        $this->controls['attribute_value'] = [
            'tab'      => 'content',
            'label'    => esc_html__( 'Attribute Value', 'snn' ),
            'type'     => 'text',
            'default'  => 'true',
            'required' => [
                ['action', '=', ['Set Attribute', 'Toggle Attribute']],
            ],
            'placeholder' => 'e.g. true, false, open, ...',
        ];

        $this->controls['replace_html'] = [
            'tab'      => 'content',
            'label'    => esc_html__( 'Replace HTML', 'snn' ),
            'type'     => 'textarea',
            'required' => [
                ['action', '=', ['Replace HTML']],
            ],
            'placeholder' => 'New HTML content...',
        ];

        $this->controls['append_html'] = [
            'tab'      => 'content',
            'label'    => esc_html__( 'Append HTML', 'snn' ),
            'type'     => 'textarea',
            'required' => [
                ['action', '=', ['Append HTML']],
            ],
            'placeholder' => 'HTML to append...',
        ];

        $this->controls['prepend_html'] = [
            'tab'      => 'content',
            'label'    => esc_html__( 'Prepend HTML', 'snn' ),
            'type'     => 'textarea',
            'required' => [
                ['action', '=', ['Prepend HTML']],
            ],
            'placeholder' => 'HTML to prepend...',
        ];

        $this->controls['style_property'] = [
            'tab'      => 'content',
            'label'    => esc_html__( 'Style Property', 'snn' ),
            'type'     => 'text',
            'default'  => 'display',
            'required' => [
                ['action', '=', ['Toggle Style Property']],
            ],
            'placeholder' => 'e.g. color, display, ...',
        ];

        $this->controls['style_value'] = [
            'tab'      => 'content',
            'label'    => esc_html__( 'Style Value', 'snn' ),
            'type'     => 'text',
            'default'  => 'block',
            'required' => [
                ['action', '=', ['Toggle Style Property']],
            ],
            'placeholder' => 'e.g. red, none, block, ...',
        ];

        $this->controls['custom_event_name'] = [
            'tab'      => 'content',
            'label'    => esc_html__( 'Custom Event Name', 'snn' ),
            'type'     => 'text',
            'default'  => 'my-custom-event',
            'required' => [
                ['action', '=', ['Dispatch Custom Event']],
            ],
        ];

        $this->controls['custom_js_function'] = [
            'tab'      => 'content',
            'label'    => esc_html__( 'Custom JS Function Body', 'snn' ),
            'type'     => 'textarea',
            'required' => [
                ['action', '=', ['Custom JS Function']],
            ],
            'placeholder' => 'function(targets, event) { ... }',
        ];

        $this->controls['text_value'] = [
            'tab'      => 'content',
            'label'    => esc_html__( 'Text Value', 'snn' ),
            'type'     => 'text',
            'required' => [
                ['action', '=', ['Change Text', 'Set Value']],
            ],
            'placeholder' => 'New text/value...',
        ];

        $this->controls['download_url'] = [
            'tab'      => 'content',
            'label'    => esc_html__( 'Download File URL', 'snn' ),
            'type'     => 'text',
            'required' => [
                ['action', '=', ['Download File']],
            ],
            'placeholder' => 'https://example.com/file.pdf',
        ];

        $this->controls['copy_value'] = [
            'tab'      => 'content',
            'label'    => esc_html__( 'Copy This Text', 'snn' ),
            'type'     => 'text',
            'required' => [
                ['action', '=', ['Copy To Clipboard']],
            ],
            'placeholder' => 'Text to copy...',
        ];

        $this->controls['scroll_behavior'] = [
            'tab'      => 'content',
            'label'    => esc_html__( 'Scroll Behavior', 'snn' ),
            'type'     => 'select',
            'options'  => [
                'smooth' => 'Smooth',
                'auto'   => 'Auto'
            ],
            'default'  => 'smooth',
            'required' => ['action', '=', 'Scroll Into View'],
        ];

        $this->controls['scroll_block'] = [
            'tab'      => 'content',
            'label'    => esc_html__( 'Scroll To Position', 'snn' ),
            'type'     => 'select',
            'options'  => [
                'center'   => 'Center',
                'start'    => 'Start',
                'end'      => 'End',
                'nearest'  => 'Nearest',
            ],
            'default'  => 'center',
            'required' => ['action', '=', 'Scroll Into View'],
        ];

    }

    public function render() {

        $trigger_sel     = isset( $this->settings['trigger_selector'] ) ? trim( $this->settings['trigger_selector'] ) : '';
        $target_sel      = isset( $this->settings['target_selector'] )  ? trim( $this->settings['target_selector'] )  : '';
        $event           = isset( $this->settings['event'] )            ? $this->settings['event']                  : '';
        $action          = isset( $this->settings['action'] )           ? $this->settings['action']                 : '';
        $class_name      = isset( $this->settings['class_name'] )       ? trim( $this->settings['class_name'] )     : 'active';
        $attribute_name  = isset( $this->settings['attribute_name'] )   ? trim( $this->settings['attribute_name'] ) : '';
        $attribute_value = isset( $this->settings['attribute_value'] )  ? trim( $this->settings['attribute_value'] ): '';
        $replace_html    = isset( $this->settings['replace_html'] )     ? $this->settings['replace_html']           : '';
        $append_html     = isset( $this->settings['append_html'] )      ? $this->settings['append_html']            : '';
        $prepend_html    = isset( $this->settings['prepend_html'] )     ? $this->settings['prepend_html']           : '';
        $style_property  = isset( $this->settings['style_property'] )   ? trim( $this->settings['style_property'] ) : '';
        $style_value     = isset( $this->settings['style_value'] )      ? trim( $this->settings['style_value'] )    : '';
        $custom_event    = isset( $this->settings['custom_event_name'] )? trim( $this->settings['custom_event_name'] ): '';
        $custom_js_func  = isset( $this->settings['custom_js_function'] )? $this->settings['custom_js_function']    : '';
        $text_value      = isset( $this->settings['text_value'] )       ? $this->settings['text_value']             : '';
        $download_url    = isset( $this->settings['download_url'] )     ? $this->settings['download_url']           : '';
        $copy_value      = isset( $this->settings['copy_value'] )       ? $this->settings['copy_value']             : '';
        $scroll_behavior = isset( $this->settings['scroll_behavior'] )  ? $this->settings['scroll_behavior']        : 'smooth';
        $scroll_block    = isset( $this->settings['scroll_block'] )     ? $this->settings['scroll_block']           : 'center';

        if ( ! $trigger_sel || ! $event || ! $action ) {
            echo $this->render_element_placeholder( [
                'icon-class' => 'ti-flash',
                'text'       => esc_html__( 'Define trigger selector, event and action.', 'snn' ),
            ] );
            return;
        }

        $uid = 'eas-' . uniqid();
        $this->set_attribute( '_root', 'class', [ 'snn-event-action-selector', $uid ] );
        echo '<div ' . $this->render_attributes( '_root' ) . '></div>'; // logic only

        ?>


<script>
(function(){
    // Wrap code inside DOMContentLoaded to ensure full DOM is ready!
    document.addEventListener('DOMContentLoaded', function() {
        const uid        = <?php echo json_encode( $uid ); ?>;
        const root       = document.querySelector('.' + uid);
        if (!root) return;

        const triggerSel     = <?php echo json_encode( $trigger_sel ); ?>;
        const targetSel      = <?php echo json_encode( $target_sel ); ?>;
        const evType         = <?php echo json_encode( $event ); ?>;
        const action         = <?php echo json_encode( $action ); ?>;
        const className      = <?php echo json_encode( $class_name ); ?>;
        const attributeName  = <?php echo json_encode( $attribute_name ); ?>;
        const attributeValue = <?php echo json_encode( $attribute_value ); ?>;
        const replaceHtml    = <?php echo json_encode( $replace_html ); ?>;
        const appendHtml     = <?php echo json_encode( $append_html ); ?>;
        const prependHtml    = <?php echo json_encode( $prepend_html ); ?>;
        const styleProperty  = <?php echo json_encode( $style_property ); ?>;
        const styleValue     = <?php echo json_encode( $style_value ); ?>;
        const customEvent    = <?php echo json_encode( $custom_event ); ?>;
        const customJsFunc   = <?php echo json_encode( $custom_js_func ); ?>;
        const textValue      = <?php echo json_encode( $text_value ); ?>;
        const downloadUrl    = <?php echo json_encode( $download_url ); ?>;
        const copyValue      = <?php echo json_encode( $copy_value ); ?>;
        const scrollBehavior = <?php echo json_encode( $scroll_behavior ); ?>;
        const scrollBlock    = <?php echo json_encode( $scroll_block ); ?>;

        function performAction(targets, e){
            targets.forEach(el=>{
                switch(action){
                    case 'Show/Hide Element':
                        el.style.display = (el.style.display==='none'?'':'none');
                        break;
                    case 'Add Class':
                        if(className) el.classList.add(className);
                        break;
                    case 'Remove Class':
                        if(className) el.classList.remove(className);
                        break;
                    case 'Toggle Class':
                        if(className) el.classList.toggle(className);
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
                        el.scrollIntoView({
                            behavior: scrollBehavior || 'smooth',
                            block: scrollBlock || 'center'
                        });
                        break;
                    case 'Focus Element':
                        el.focus({preventScroll:false});
                        break;
                    case 'Blur Element':
                        el.blur();
                        break;
                    case 'Toggle Fullscreen':
                        if(!document.fullscreenElement){ el.requestFullscreen().catch(()=>{}); }
                        else { document.exitFullscreen(); }
                        break;

                    // === Advanced Actions ===

                    case 'Clone Element':
                        el.parentNode && el.parentNode.insertBefore(el.cloneNode(true), el.nextSibling);
                        break;
                    case 'Replace HTML':
                        el.innerHTML = replaceHtml || '';
                        break;
                    case 'Set Attribute':
                        if(attributeName) el.setAttribute(attributeName, attributeValue||'');
                        break;
                    case 'Remove Attribute':
                        if(attributeName) el.removeAttribute(attributeName);
                        break;
                    case 'Toggle Attribute':
                        if(attributeName) {
                            if(el.hasAttribute(attributeName)) el.removeAttribute(attributeName);
                            else el.setAttribute(attributeName, attributeValue||'');
                        }
                        break;
                    case 'Dispatch Custom Event':
                        if(customEvent) el.dispatchEvent(new CustomEvent(customEvent, {bubbles:true}));
                        break;
                    case 'Download File':
                        if(downloadUrl) {
                            const a = document.createElement('a');
                            a.href = downloadUrl;
                            a.download = '';
                            document.body.appendChild(a);
                            a.click();
                            document.body.removeChild(a);
                        }
                        break;
                    case 'Change Text':
                        el.textContent = textValue || '';
                        break;
                    case 'Append HTML':
                        el.insertAdjacentHTML('beforeend', appendHtml || '');
                        break;
                    case 'Prepend HTML':
                        el.insertAdjacentHTML('afterbegin', prependHtml || '');
                        break;
                    case 'Toggle Style Property':
                        if(styleProperty) {
                            if(el.style[styleProperty]) el.style[styleProperty] = '';
                            else el.style[styleProperty] = styleValue || '';
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
                            html2canvas(el).then(canvas=>{
                                const a = document.createElement('a');
                                a.href = canvas.toDataURL();
                                a.download = 'screenshot.png';
                                a.click();
                            });
                        } else {
                            alert('html2canvas.js required!');
                        }
                        break;
                    case 'Copy To Clipboard':
                        if(copyValue) {
                            navigator.clipboard && navigator.clipboard.writeText(copyValue);
                        }
                        break;
                    case 'Set Value':
                        if('value' in el) el.value = textValue || '';
                        break;
                    case 'Log To Console':
                        console.log('[Event⇄Action]', el, e);
                        break;
                    case 'Custom JS Function':
                        try {
                            // eslint-disable-next-line no-new-func
                            const fn = new Function('targets','event', customJsFunc || '');
                            fn([el], e);
                        } catch(err) {
                            console.warn('Error in custom JS function:', err);
                        }
                        break;
                    default:
                        console.warn('Action "'+action+'" not implemented.');
                }
            });
        }

        const triggers = document.querySelectorAll(triggerSel);
        if(!triggers.length) { console.warn('[Event⇄Action] No elements match "'+triggerSel+'"'); return; }

        triggers.forEach(tr=>{
            tr.addEventListener(evType, function(e){
                const targets = targetSel ? document.querySelectorAll(targetSel) : [tr];
                performAction(Array.from(targets), e);
            });
        });
    });
})();
</script>




<?php
    }
}
?>
