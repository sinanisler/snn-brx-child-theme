<?php 

function snn_custom_inline_styles_and_scripts_improved() {
    // Get options from the settings panel.
    $options = get_option('snn_editor_settings');

    // Check if 'snn_bricks_builder_color_fix' is enabled and if URL has ?bricks=run.
    if (
        isset($options['snn_bricks_builder_color_fix']) &&
        $options['snn_bricks_builder_color_fix'] &&
        isset($_GET['bricks']) &&
        $_GET['bricks'] === 'run'
    ) {
        // Retrieve the global color sync variables (if any)
        // Change default value from [] to false to differentiate between never set and intentionally empty.
        $global_colors = get_option('snn_global_color_sync_variables', false);

        // For security: create a nonce for the AJAX save action.
        $nonce = wp_create_nonce('snn_save_colors_nonce');
        ?>
 
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                // Helper function to convert any valid CSS color to a hex string.
                function cssColorToHex(color) {
                    var dummy = document.createElement("div");
                    dummy.style.color = color;
                    document.body.appendChild(dummy);
                    var computedColor = getComputedStyle(dummy).color;
                    document.body.removeChild(dummy);
                    var match = computedColor.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)/);
                    if (match) {
                        var r = parseInt(match[1]).toString(16).padStart(2, "0");
                        var g = parseInt(match[2]).toString(16).padStart(2, "0");
                        var b = parseInt(match[3]).toString(16).padStart(2, "0");
                        return "#" + r + g + b;
                    }
                    return null;
                }

                // Insert SNN button into the Bricks toolbar.
                function insertSnnListItem(toolbar) {
                    // Determine the UL element that holds the li items.
                    var ul = (toolbar.tagName.toLowerCase() === "ul") ? toolbar : toolbar.querySelector("ul");
                    if (!ul) {
                        return;
                    }
                    
                    // Avoid inserting duplicate SNN li element.
                    if (ul.querySelector(".snn-enhance-li")) {
                        return;
                    }
                    
                    // Create the new li element.
                    var li = document.createElement("li");
                    li.className = "snn-enhance-li";
                    li.tabIndex = 0;
                    li.setAttribute("data-balloon", "SNN-BRX");
                    li.setAttribute("data-balloon-pos", "bottom");
                    li.innerHTML = 'SNN'; 
                    
                    // Click event to open the popup.
                    li.addEventListener("click", function() {
                        var popup = document.querySelector("#snn-popup");
                        if (popup) {
                            popup.classList.add("active");
                        }
                    });
                    
                    // Always insert at the desired position.
                    if (ul.children.length >= 7) {
                        ul.insertBefore(li, ul.children[6]);
                    } else {
                        ul.appendChild(li);
                    }
                }

                function findAndInsertSnn() {
                    var toolbar = document.querySelector("#bricks-toolbar");
                    if (toolbar) {
                        insertSnnListItem(toolbar);
                        return true;
                    }
                    return false;
                }

                var intervalCheck = setInterval(function() {
                    if (findAndInsertSnn()) {
                        clearInterval(intervalCheck);
                    }
                }, 500);

                var observer = new MutationObserver(function(mutationsList) {
                    mutationsList.forEach(function(mutation) {
                        mutation.addedNodes.forEach(function(node) {
                            if (node.nodeType === 1) {
                                if (node.matches("#bricks-toolbar")) {
                                    insertSnnListItem(node);
                                    clearInterval(intervalCheck);
                                } else {
                                    var toolbarInside = node.querySelector("#bricks-toolbar");
                                    if (toolbarInside) {
                                        insertSnnListItem(toolbarInside);
                                        clearInterval(intervalCheck);
                                    }
                                }
                            }
                        });
                    });
                });
                observer.observe(document.body, { childList: true, subtree: true });

                // Close popup when the close button is clicked.
                document.addEventListener("click", function(e) {
                    if (e.target && e.target.classList.contains("snn-close-button")) {
                        var popup = document.querySelector("#snn-popup");
                        if (popup) {
                            popup.classList.remove("active");
                        }
                    }
                });

                // ----- Repeater Functionality for Global Color Variables -----
                // Function to create a new color row with a static auto-generated name,
                // a text input for color value (supports any valid CSS color or CSS variable),
                // a color picker (updated if a valid color is detected), and a remove button.
                function createColorRow(hex = "") {
                    function expandShortHex(hexVal) {
                        return "#" + hexVal[1] + hexVal[1] + hexVal[2] + hexVal[2] + hexVal[3] + hexVal[3];
                    }
                    var displayValue = "";
                    var colorPickerValue = "";
                    if (hex) {
                        if (/^#([0-9A-Fa-f]{3})$/.test(hex)) {
                            displayValue = expandShortHex(hex).toUpperCase();
                            colorPickerValue = displayValue;
                        } else if (/^#([0-9A-Fa-f]{6})$/.test(hex)) {
                            displayValue = hex.toUpperCase();
                            colorPickerValue = displayValue;
                        } else {
                            displayValue = hex;
                            var converted = cssColorToHex(hex);
                            if (converted) {
                                colorPickerValue = converted;
                            }
                        }
                    }
                    var colorValueAttr = colorPickerValue ? `value="${colorPickerValue}"` : "";
                    var row = document.createElement("div");
                    row.className = "snn-color-row";
                    row.innerHTML = `
                        <span class="snn-color-name-display"></span>
                        <input type="text" class="snn-hex-input" placeholder="Enter CSS color" value="${displayValue}" />
                        <input type="color" class="snn-color-picker" ${colorValueAttr} />
                        <button class="snn-remove-color">Remove</button>
                    `;
                    // Sync text input and color picker.
                    var hexInput = row.querySelector(".snn-hex-input");
                    var colorPicker = row.querySelector(".snn-color-picker");

                    hexInput.addEventListener("input", function() {
                        var inputVal = hexInput.value.trim();
                        var converted = cssColorToHex(inputVal);
                        if (converted) {
                            colorPicker.value = converted;
                        }
                    });
                    
                    colorPicker.addEventListener("input", function() {
                        // Only update the text input if its current value is already a valid hex.
                        if (/^#([0-9A-Fa-f]{6})$/.test(hexInput.value.trim())) {
                            hexInput.value = colorPicker.value.toUpperCase();
                        }
                    });

                    // Remove button event.
                    row.querySelector(".snn-remove-color").addEventListener("click", function() {
                        row.remove();
                        updateColorNames();
                    });

                    return row;
                }

                // Function to update auto-generated color names for all rows.
                function updateColorNames() {
                    var rows = document.querySelectorAll(".snn-color-row");
                    rows.forEach(function(row, index) {
                        var nameDisplay = row.querySelector(".snn-color-name-display");
                        if (nameDisplay) {
                            nameDisplay.textContent = "var(snn-color-" + (index + 1) + ")";
                        }
                    });
                }

                var repeaterContainer = document.getElementById("snn-color-repeater");
                var addColorButton = document.getElementById("snn-add-color");

                // Load existing colors from PHP.
                var existingColors = <?php echo json_encode($global_colors); ?>;
                if (existingColors === false) {
                    // First time load: add one empty row.
                    repeaterContainer.appendChild(createColorRow());
                } else if (Array.isArray(existingColors) && existingColors.length > 0) {
                    existingColors.forEach(function(colorItem) {
                        var hex = colorItem.hex ? colorItem.hex : "";
                        var row = createColorRow(hex);
                        repeaterContainer.appendChild(row);
                    });
                }
                // If existingColors is an empty array (colors intentionally removed), do not add any row.
                updateColorNames();

                addColorButton.addEventListener("click", function() {
                    repeaterContainer.appendChild(createColorRow());
                    updateColorNames();
                });

                // Save settings via AJAX when the "Save Settings" button is clicked.
                var saveButton = document.querySelector(".snn-panel-button");
                saveButton.addEventListener("click", function() {
                    var colorRows = document.querySelectorAll(".snn-color-row");
                    var colorsData = [];
                    colorRows.forEach(function(row, index) {
                        var hexValue = row.querySelector(".snn-hex-input").value.trim();
                        // Auto-generate the color name based on the row order.
                        var autoName = "snn-color-" + (index + 1);
                        if (hexValue) {
                            colorsData.push({ name: autoName, hex: hexValue });
                        }
                    });

                    fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: "action=snn_save_colors_improved&nonce=<?php echo $nonce; ?>&colors=" + encodeURIComponent(JSON.stringify(colorsData))
                    })
                    .then(response => response.json())
                    .then(data => {
                        var feedbackEl = document.querySelector(".snn-feedback-after-save");
                        if (data.success) {
                            feedbackEl.textContent = "Settings saved.";
                            // Optionally update dynamic CSS variables on the page without reload.
                            updateDynamicCSSVariables(colorsData);
                            setTimeout(function() {
                                feedbackEl.textContent = "";
                            }, 3000);
                        } else {
                            feedbackEl.textContent = "Error saving settings: " + data.data;
                            setTimeout(function() {
                                feedbackEl.textContent = "";
                            }, 3000);
                        }
                    })
                    .catch(error => {
                        console.error("Error:", error);
                        var feedbackEl = document.querySelector(".snn-feedback-after-save");
                        feedbackEl.textContent = "An error occurred while saving settings.";
                        setTimeout(function() {
                            feedbackEl.textContent = "";
                        }, 3000);
                    });
                });

                // Function to update dynamic CSS variables on the page based on new colors data.
                function updateDynamicCSSVariables(colorsData) {
                    var styleEl = document.getElementById("snn-dynamic-colors");
                    if (!styleEl) {
                        styleEl = document.createElement("style");
                        styleEl.id = "snn-dynamic-colors";
                        document.head.appendChild(styleEl);
                    }
                    var cssVars = ":root {";
                    colorsData.forEach(function(color, index) {
                        cssVars += "--snn-color-" + (index + 1) + ": " + color.hex + ";";
                    });
                    cssVars += "}";
                    styleEl.textContent = cssVars;
                }
            });
        </script>

        <style>
            #snn-popup {
                align-items: center;
                background-color: #ffffffdd;
                bottom: 0;
                color: #fff;
                display: none;
                font-size: 14px;
                justify-content: center;
                left: 0;
                padding: 60px;
                position: fixed;
                right: 0;
                top: 0;
                z-index: 10001;
            }
            #snn-popup.active {
                display: flex;
            }
            #snn-popup h1 {
                font-size: 2em;
                font-weight: 600;
            }
            .snn-enhance-li {
                width:26px !important;
                padding-left: 3px;
                font-size: 12px;
                letter-spacing: -0.3px;
                padding-top: 1px;
            }
            .snn-enhance-li i{
                font-size: 19px;
                opacity: 0.8;
            }
            #snn-popup-inner {
                background-color: var(--builder-bg);
                border-radius: var(--builder-border-radius);
                box-shadow: 0 6px 24px 0 rgba(0, 0, 0, 0.25);
                display: flex;
                flex-direction: column;
                height: 100%;
                max-width: 1200px;
                overflow-y: auto;
                position: relative;
                width: 100%;
                color: #fff;
                padding: 20px;
            }
            #snn-popup .snn-filters li.active {
                background-color: var(--builder-bg-accent);
                border-radius: var(--builder-border-radius);
                color: #fff;
            }
            #snn-popup .snn-title-wrapper {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 10px;
                position: relative;
            }
            .snn-close-button {
                cursor: pointer;
                font-size: 28px;
                background: transparent;
                border: none;
                color: var(--builder-color-accent);
                transform: scaleX(1.3);
            }
            .snn-close-button:hover {
                color: white;
            }
            .snn-toolbar-container {
                background-color: #f5f5f5 !important;
                border: 1px solid #ddd !important;
                padding: 10px !important;
                border-radius: 5px !important;
            }
            .snn-toolbar-container:hover {
                background-color: #e0e0e0 !important;
            }
            .snn-toolbar-container li {
                font-size: 12px;
            }
            .snn-settings-content-wrapper {
                height: calc(100% - 50px);
            }
            .snn-panel-button {
                align-items: center;
                background-color: var(--builder-bg-3);
                border-radius: var(--builder-border-radius);
                cursor: pointer;
                display: flex;
                height: var(--builder-popup-input-height);
                width: 157px;
                padding: 12px;
                font-size: 16px;
                letter-spacing: 0.3px;
            }
            .snn-panel-button:hover {
                background-color: var(--builder-color-accent);
                color: black;
            }
            .snn-panel-button svg {
                margin-right: 10px;
            }
            .snn-settings-content-wrapper-section {
                padding: 10px;
                border: solid #00000055 1px;
                border-radius: 4px;
                margin-bottom: 15px;
            }
            .snn-color-row {
                display: flex;
                align-items: center;
                margin-bottom: 4px;
            }
            .snn-color-row .snn-color-name-display {
                width: 120px;
                text-align: center;
                font-size: 14px;
                margin-right: 10px;
                background: #171a1d;
                line-height: 32px;
                height: 32px;
                color: #868686;
            }
            .snn-color-row input[type="text"] {
                padding: 5px;
                font-size: 16px;
                width: 150px;
                text-align: center;
                margin-right: 10px;
                background: #171a1d;
                border: 0;
                line-height: 22px;
            }
            .snn-color-row input[type="text"]::placeholder {
                color: #ffffff44;
            }
            .snn-color-row input[type="color"] {
                width: 50px;
                height: 22px;
                border: none;
                cursor: pointer;
                margin-right: 10px;
                padding: 0;
            }
            .snn-color-row button.snn-remove-color {
                padding: 5px 10px;
                font-size: 14px;
                cursor: pointer;
                background: #171a1d;
                color: white;
            }
            .snn-color-row button.snn-remove-color:hover {
                background-color: var(--builder-color-accent);
                color: black;
            }
            #snn-add-color {
                margin-top: 10px;
                padding: 8px 12px;
                font-size: 14px;
                cursor: pointer;
                background: #171a1d;
                color: white;
            }
            .snn-feedback-after-save {
                margin-bottom: 10px;
                font-size: 14px;
                color: #0f0;
            }
            .snn-feedback-after-save:hover {
                background-color: var(--builder-color-accent);
                color: black;
            }
            .snn-settings-content-wrapper-section-title {
                margin-bottom: 5px;
            }
        </style>
 
        <?php
    }
}
add_action('wp_head', 'snn_custom_inline_styles_and_scripts_improved');

function snn_popup_container_improved() {
    $options = get_option('snn_editor_settings');
    if (
        isset($options['snn_bricks_builder_color_fix']) &&
        $options['snn_bricks_builder_color_fix'] &&
        isset($_GET['bricks']) &&
        $_GET['bricks'] === 'run'
    ) {
        ?>
        <div id="snn-popup" class="snn-popup docs">
            <div id="snn-popup-inner" class="snn-popup-inner">
                <div class="snn-title-wrapper">
                    <h1>SNN-BRX</h1>
                    <button class="snn-close-button">X</button>
                </div>
                <div class="snn-settings-content-wrapper">
                    <div class="snn-settings-content-wrapper-section">
                        <div class="snn-settings-content-wrapper-section-title">
                            Global Color Variables
                        </div>
                        <div class="snn-settings-content-wrapper-section-setting-area">
                            <div id="snn-color-repeater">
                                <!-- Repeater rows will be inserted here -->
                            </div>
                            <button type="button" id="snn-add-color">Add Color +</button>
                        </div>
                    </div>
                </div>
                
                <div class="snn-panel-button" data-balloon="Refresh Editor After Save" data-balloon-pos="top">
                    <span class="bricks-svg-wrapper" data-name="save">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" class="bricks-svg">
                            <path d="M21.75 23.25H2.25a1.5 1.5 0 0 1 -1.5 -1.5V7.243a3 3 0 0 1 0.879 -2.121l3.492 -3.493A3 3 0 0 1 7.243 0.75H21.75a1.5 1.5 0 0 1 1.5 1.5v19.5a1.5 1.5 0 0 1 -1.5 1.5Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            <path d="M9.75 12.75a3 3 0 1 0 6 0 3 3 0 1 0 -6 0Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            <path d="m12.75 20.25 6.75 0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            <path d="M8.25 0.75v3a1.5 1.5 0 0 0 1.5 1.5h7.5a1.5 1.5 0 0 0 1.5 -1.5v-3" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        Save Settings
                    </span>
                </div>
                <div class="snn-feedback-after-save"></div>
            </div>
        </div>
        <?php
    }
}
add_action('wp_footer', 'snn_popup_container_improved');


// AJAX handler for saving the color settings.
function snn_save_color_settings_improved() {
    // Verify nonce for security.
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'snn_save_colors_nonce' ) ) {
        wp_send_json_error('Invalid nonce');
        wp_die();
    }

    if ( ! isset( $_POST['colors'] ) ) {
        wp_send_json_error('No colors data provided');
        wp_die();
    }

    $colors = json_decode( stripslashes( $_POST['colors'] ), true );

    if ( ! is_array( $colors ) ) {
        wp_send_json_error('Invalid colors data');
        wp_die();
    }

    // Save the colors into the option "snn_global_color_sync_variables".
    update_option( 'snn_global_color_sync_variables', $colors );

    wp_send_json_success('Settings saved');
    wp_die();
}
add_action('wp_ajax_snn_save_colors_improved', 'snn_save_color_settings_improved');


// Function to output dynamic CSS variables based on saved colors in both head and footer.
// No default color is printed if no color is saved.
function snn_dynamic_color_variables_roots() {
    $colors = get_option('snn_global_color_sync_variables', []);
    if ( ! empty($colors) && is_array($colors) ) {
         echo '<style id="snn-dynamic-colors">';
         echo '
:root {
';
         $i = 1;
         foreach ( $colors as $color ) {
            $hex = isset($color['hex']) ? $color['hex'] : '';
            if( $hex !== '' ){
                echo '  --snn-color-' . $i . ': ' . $hex . ';
';
            }
            $i++;
         }
         echo '}';
         echo '
</style>';
    }
}
add_action( 'wp_head', 'snn_dynamic_color_variables_roots', 1 );
add_action( 'wp_footer', 'snn_dynamic_color_variables_roots', 9999 );


// --------------------------------------------------------------------------
// New function: Inject colors into the Bricks Builder color palette
// This script will run as the last script in the footer so that it seamlessly
// integrates with Bricks Builder's own data. We now only use the colors from
// "snn_global_color_sync_variables" and build our own names to match the CSS variables.
function snn_inject_bricks_color_palette() {
    $options = get_option('snn_editor_settings');
    if (
        isset($options['snn_bricks_builder_color_fix']) &&
        $options['snn_bricks_builder_color_fix'] &&
        isset($_GET['bricks']) &&
        $_GET['bricks'] === 'run'
    ) {
        $colors = get_option('snn_global_color_sync_variables', []);
        if (!empty($colors) && is_array($colors)) {
            echo "
<script>
(function(){
if (typeof bricksData !== 'undefined' && bricksData.loadData && bricksData.loadData.colorPalette && bricksData.loadData.colorPalette[0]) {
    bricksData.loadData.colorPalette[0].colors.unshift(
";
            $color_objects = [];
            $index = 1;
            foreach ($colors as $color) {
                if (isset($color['hex']) && $color['hex'] !== '') {
                    // Generate our own variable name matching the dynamic CSS output.
                    $js_var = 'snn-color-' . $index;
                    $color_objects[] = '    {
        "raw": "var(--' . $js_var . ')",
        "id": "snn1' . $index . '",
        "name": "' . $js_var . '"
    }';
                    $index++;
                }
            }
            echo "\n" . implode(",\n", $color_objects) . "\n);";
            echo "\n}
})();
</script>
";
        }
    }
}
add_action('wp_footer', 'snn_inject_bricks_color_palette', 100000);
?>
