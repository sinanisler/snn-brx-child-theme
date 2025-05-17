<?php
if ( ! defined( 'ABSPATH' ) ) exit;
use Bricks\Element;

class Matrix_Rain extends Element {
    public $category      = 'snn';
    public $name          = 'matrix-rain';
    public $icon          = 'ti-layers';
    public $css_selector  = '.matrix-rain-wrapper'; // Note: this class isn't explicitly added to a wrapper in render, canvas is child of 'dom_selector'
    public $scripts       = [];
    public $nestable      = false;

    public function get_label() {
        return esc_html__('Matrix Rain', 'snn');
    }

    public function set_controls() {
        $this->controls['dom_selector'] = [
            'tab'         => 'content',
            'label'       => esc_html__('DOM Selector', 'snn'),
            'type'        => 'text',
            'default'     => 'body',
            'placeholder' => '.class, #id, body',
            'description' => esc_html__('Which element to overlay Matrix Rain on? CSS selector. Default is body.', 'snn'),
            'inline'      => true,
        ];
        $this->controls['char_color'] = [
            'tab'     => 'content',
            'label'   => esc_html__('Text Color', 'snn'),
            'type'    => 'color',
            'default' => [
                'hex' => '#4af626',
                'rgb' => 'rgba(74, 246, 38, 1)',
            ],
        ];
        $this->controls['bg_color'] = [
            'tab'         => 'content',
            'label'       => esc_html__('Background Color (fade trail)', 'snn'),
            'type'        => 'color',
            'default'     => [
                'rgb' => 'rgba(0,0,0,0.1)', // Changed: Default to semi-transparent for fade effect
                'hex' => '#000000',         // Changed: Consistent hex for black
            ],
            'description' => esc_html__('Controls the trail/fade effect. Use semi-transparent for classic Matrix (e.g., rgba(0,0,0,0.1)). Set fully transparent (alpha 0) for no overlay clearing (letters will stack).', 'snn'),
        ];
        $this->controls['opacity'] = [
            'tab'     => 'content',
            'label'   => esc_html__('Overlay Opacity (0-1)', 'snn'),
            'type'    => 'number',
            'default' => 0.85,
            'min'     => 0,
            'max'     => 1,
            'step'    => 0.01,
            'unit'    => '',
        ];
        $this->controls['speed'] = [
            'tab'     => 'content',
            'label'   => esc_html__('Rain Speed', 'snn'),
            'type'    => 'number',
            'units'   => [
                'ms' => [
                    'min'  => 10,
                    'max'  => 200,
                    'step' => 1,
                ],
            ],
            'default' => '40ms',
        ];
        $this->controls['density'] = [ // Higher density means more columns horizontally
            'tab'     => 'content',
            'label'   => esc_html__('Column Density (baseline 20)', 'snn'),
            'type'    => 'number',
            'default' => 20, // Changed default to 20 (baseline for 1x font-size spacing)
            'min'     => 4,
            'max'     => 60,
            'step'    => 1,
            'unit'    => '',
            'description' => esc_html__('Controls horizontal density. 20 = columns spaced by font size. Higher = denser. Lower = sparser.', 'snn'),
        ];
        $this->controls['font_size'] = [
            'tab'     => 'content',
            'label'   => esc_html__('Font Size (px)', 'snn'),
            'type'    => 'number',
            'default' => 22,
            'min'     => 10,
            'max'     => 80,
            'step'    => 1,
        ];
        $this->controls['custom_chars'] = [
            'tab'         => 'content',
            'label'       => esc_html__('Custom Characters (optional)', 'snn'),
            'type'        => 'text',
            'default'     => '',
            'description' => esc_html__('Leave blank for random Japanese + ASCII. Enter your own for custom effect!', 'snn'),
        ];
        $this->controls['reverse'] = [
            'tab'     => 'content',
            'label'   => esc_html__('Reverse Direction (Upwards)?', 'snn'),
            'type'    => 'checkbox',
            'default' => false,
        ];
    }

    public function render() {
        $settings   = $this->settings;
        $selector   = trim($settings['dom_selector'] ?? 'body');
        if(!$selector) $selector = 'body';

        $color      = $settings['char_color']['hex'] ?? '#4af626';
        // Ensure default bg_color from controls is used if not set, or fallback to a good value
        $bg_color_setting = $settings['bg_color']['rgb'] ?? null;
        if (strpos($bg_color_setting ?? '', 'rgba') === false || (isset($settings['bg_color']['rgb']) && explode(',', $settings['bg_color']['rgb'])[3] === '0)')) {
             // If the saved value is transparent black (alpha 0) or invalid, use a sensible default for trails.
             // The control default is now 'rgba(0,0,0,0.1)'. This PHP fallback ensures older saved instances also get a working trail.
            $bgcolor = $settings['bg_color']['rgb'] ?? 'rgba(0,0,0,0.1)';
            if (strpos($bgcolor, 'rgba') !== false) {
                $parts = explode(',', str_replace(['rgba(', ')'], '', $bgcolor));
                if (count($parts) === 4 && floatval($parts[3]) === 0) {
                    $bgcolor = 'rgba('.$parts[0].','.$parts[1].','.$parts[2].',0.1)'; // Force alpha if it was zero
                }
            } else { // If not a valid rgba, force a default
                 $bgcolor = 'rgba(0,0,0,0.1)';
            }
        } else {
            $bgcolor = $bg_color_setting;
        }


        $opacity    = isset($settings['opacity']) ? floatval($settings['opacity']) : 0.85;
        $speed      = isset($settings['speed']) ? intval($settings['speed']) : 40;
        $density    = isset($settings['density']) ? intval($settings['density']) : 20; // Default to 20 if not set
        $font_size  = isset($settings['font_size']) ? intval($settings['font_size']) : 22;
        $chars_str  = $settings['custom_chars'] ?? '';
        $reverse    = !empty($settings['reverse']);
        $unique     = 'matrix-rain-' . uniqid();

        // This dummy div is not strictly necessary for the script below but might be used by Bricks in some contexts.
        echo '<div style="display:none;" id="'.esc_attr($unique).'-dummy" class="matrix-rain-wrapper"></div>';
        ?>
        <script>
        (function(){
            function matrixRainInit_<?php echo esc_js(str_replace('-', '_', $unique)); ?>() {
                var selector = <?php echo json_encode($selector); ?>;
                var charColor = <?php echo json_encode($color); ?>;
                var bgColor = <?php echo json_encode($bgcolor); ?>;
                var overallOpacity = <?php echo json_encode($opacity); ?>;
                var speed = <?php echo json_encode($speed); ?>;
                var density = <?php echo json_encode($density); ?>; // User-defined density
                var fontSize = <?php echo json_encode($font_size); ?>;
                var customChars = <?php echo json_encode($chars_str); ?>;
                var chars = customChars || "アァカサタナハマヤラワガザダバパイィキシチニヒミリヰギジヂビピウゥクスツヌフムユルグズヅブプエェケセテネヘメレヱゲゼデベペオォコソトノホモヨロヲゴゾドボポヴABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
                chars = chars.split('');
                var reverse = <?php echo $reverse ? 'true' : 'false'; ?>;
                
                var root = document.querySelector(selector);
                if(!root) return;

                if(root.querySelector('.matrix-rain-overlay-canvas[data-matrix-id="<?php echo esc_js($unique); ?>"]')) return; // Prevent multiple initializations on same root with same ID

                var canvas = document.createElement('canvas');
                canvas.className = 'matrix-rain-overlay-canvas';
                canvas.setAttribute('data-matrix-id', '<?php echo esc_js($unique); ?>'); // Unique ID for canvas
                canvas.style.position = 'absolute';
                canvas.style.top = '0';
                canvas.style.left = '0';
                canvas.style.width = '100%';
                canvas.style.height = '100%';
                canvas.style.pointerEvents = 'none';
                canvas.style.zIndex = '9999'; // Ensure it's on top, adjust if needed
                canvas.style.opacity = overallOpacity;
                canvas.style.display = 'block';
                
                var rootStyle = getComputedStyle(root);
                canvas.style.borderRadius = rootStyle.borderRadius || '';
                
                var prevPos = rootStyle.position;
                if(prevPos === 'static' || !prevPos) root.style.position = 'relative';

                root.appendChild(canvas);

                var ctx = canvas.getContext('2d');
                var w, h; // Will be set in resize
                var rainDrops = [];
                var effectiveColumnSpacing; // To be calculated

                function calculateMetrics() {
                    w = canvas.width;
                    h = canvas.height;

                    // Higher density value means smaller spacing (more columns)
                    // Baseline density of 20 means spacing is equal to fontSize
                    effectiveColumnSpacing = fontSize * (20.0 / Math.max(1, density)); // Ensure density is at least 1
                    if (effectiveColumnSpacing < 1) effectiveColumnSpacing = 1; // Minimum 1px spacing

                    var numColumns = Math.ceil(w / effectiveColumnSpacing);
                    return numColumns;
                }
                
                function initializeRaindrops(numColumns) {
                    var oldLength = rainDrops.length;
                    rainDrops.length = numColumns; // Resize array
                    for(var i = 0; i < numColumns; i++) {
                        if (i >= oldLength || rainDrops[i] === undefined) { // New or uninitialized columns
                             // rainDrops[i] stores the y-position multiplier for fontSize
                            rainDrops[i] = Math.random() * (h / fontSize);
                        } else {
                            // Adjust if height changed significantly
                            if ((rainDrops[i] * fontSize) > h) {
                                rainDrops[i] = Math.random() * (h / fontSize);
                            }
                        }
                    }
                }

                function resizeAndReinitialize() {
                    canvas.width = root.clientWidth;
                    canvas.height = root.clientHeight;
                    w = canvas.width; // Update w and h immediately
                    h = canvas.height;
                    var numColumns = calculateMetrics(); // This updates effectiveColumnSpacing
                    initializeRaindrops(numColumns);
                }
                resizeAndReinitialize(); // Initial setup

                if(window.ResizeObserver) {
                    var obs = new ResizeObserver(resizeAndReinitialize);
                    obs.observe(root);
                    // Observing canvas itself for size changes can sometimes be tricky if its size depends on root.
                    // Observing root is generally more reliable for clientWidth/Height changes.
                } else {
                    window.addEventListener('resize', resizeAndReinitialize, {passive:true});
                }

                function draw() {
                    // Fill canvas with semi-transparent BG to create the fading trail effect
                    ctx.fillStyle = bgColor;
                    ctx.fillRect(0, 0, w, h);

                    ctx.fillStyle = charColor;
                    ctx.font = fontSize + "px monospace";
                    
                    for(var i = 0; i < rainDrops.length; i++) {
                        var text = chars[Math.floor(Math.random() * chars.length)];
                        var x_coord = i * effectiveColumnSpacing;
                        
                        // rainDrops[i] determines the vertical position (as a multiple of fontSize)
                        // y_pos is the baseline for the text
                        var y_pos = rainDrops[i] * fontSize; 

                        if (reverse) {
                            // For upward movement, draw from bottom (h) towards top (0)
                            // As rainDrops[i] increases, h - y_pos decreases.
                            ctx.fillText(text, x_coord, h - y_pos);
                        } else {
                            ctx.fillText(text, x_coord, y_pos);
                        }

                        // If character goes off screen (bottom for normal, top for reverse) & random chance, reset it
                        // The condition y_pos > h checks if the original logical position is off screen.
                        if(y_pos > h && Math.random() > 0.975) {
                            rainDrops[i] = 0; // Reset to the "top" (logical top)
                        }
                        rainDrops[i]++;
                    }
                }

                if(canvas._matrixRainInterval) clearInterval(canvas._matrixRainInterval);
                canvas._matrixRainInterval = setInterval(draw, speed);
            }

            if(document.readyState === 'complete') {
                matrixRainInit_<?php echo esc_js(str_replace('-', '_', $unique)); ?>();
            } else {
                window.addEventListener('DOMContentLoaded', matrixRainInit_<?php echo esc_js(str_replace('-', '_', $unique)); ?>);
            }
            // Handle Bricks AJAX re-render (e.g., in the builder)
            document.addEventListener('bricks/frontend/render', function(e){
                 // Check if the specific element being re-rendered is or contains our target
                var targetElement = document.getElementById('<?php echo esc_js($unique . "-dummy"); ?>');
                if (targetElement && e.target && (e.target.contains(targetElement) || e.target === targetElement.closest(selector))) {
                    // A small delay can sometimes help ensure the DOM is fully ready after AJAX
                    setTimeout(function() {
                         matrixRainInit_<?php echo esc_js(str_replace('-', '_', $unique)); ?>();
                    }, 150);
                } else if (!targetElement && selector === 'body' && e.target.classList.contains('brx-ajax-render')) {
                    // Broader check if targeting body and a general Bricks AJAX render happens
                     setTimeout(function() {
                         matrixRainInit_<?php echo esc_js(str_replace('-', '_', $unique)); ?>();
                    }, 150);
                }
            });
        })();
        </script>
        <?php
    }
}
?>