<?php 

function snn_custom_inline_styles_and_scripts_improved() {
    $options = get_option('snn_editor_settings');

    if (
        isset($options['snn_bricks_builder_color_fix']) &&
        $options['snn_bricks_builder_color_fix'] &&
        isset($_GET['bricks']) &&
        $_GET['bricks'] === 'run' &&
        current_user_can('manage_options')
    ) {
        $global_colors = get_option('snn_global_color_sync_variables', false);

        $nonce = wp_create_nonce('snn_save_colors_nonce');
        ?>
 
        <script>
            document.addEventListener("DOMContentLoaded", function() {
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

                function hexToHsl(hex) {
                    if (/^#([A-Fa-f0-9]{3})$/.test(hex)) {
                        hex = '#' + hex[1] + hex[1] + hex[2] + hex[2] + hex[3] + hex[3];
                    }
                    var r = parseInt(hex.substr(1,2),16) / 255;
                    var g = parseInt(hex.substr(3,2),16) / 255;
                    var b = parseInt(hex.substr(5,2),16) / 255;
                    var max = Math.max(r, g, b), min = Math.min(r, g, b);
                    var h, s, l = (max + min) / 2;
                    if (max === min) {
                        h = s = 0;
                    } else {
                        var d = max - min;
                        s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
                        switch (max) {
                            case r: h = (g - b) / d + (g < b ? 6 : 0); break;
                            case g: h = (b - r) / d + 2; break;
                            case b: h = (r - g) / d + 4; break;
                        }
                        h /= 6;
                    }
                    return { h: h, s: s, l: l };
                }

                function hslToHex(h, s, l) {
                    function hue2rgb(p, q, t) {
                        if(t < 0) t += 1;
                        if(t > 1) t -= 1;
                        if(t < 1/6) return p + (q - p) * 6 * t;
                        if(t < 1/2) return q;
                        if(t < 2/3) return p + (q - p) * (2/3 - t) * 6;
                        return p;
                    }
                    var r, g, b;
                    if(s === 0) {
                        r = g = b = l;
                    } else {
                        var q = l < 0.5 ? l * (1 + s) : l + s - l * s;
                        var p = 2 * l - q;
                        r = hue2rgb(p, q, h + 1/3);
                        g = hue2rgb(p, q, h);
                        b = hue2rgb(p, q, h - 1/3);
                    }
                    var toHex = function(x){
                        var hexVal = Math.round(x * 255).toString(16);
                        return hexVal.padStart(2, "0");
                    };
                    return "#" + toHex(r) + toHex(g) + toHex(b);
                }

                function lightenColor(hex, fraction) {
                    var hsl = hexToHsl(hex);
                    hsl.l = hsl.l + (1 - hsl.l) * fraction;
                    return hslToHex(hsl.h, hsl.s, hsl.l);
                }

                function darkenColor(hex, fraction) {
                    var hsl = hexToHsl(hex);
                    hsl.l = hsl.l * (1 - fraction);
                    return hslToHex(hsl.h, hsl.s, hsl.l);
                }

                // Function to return the auto-generated color name based on index.
                function getAutoColorName(index) {
                    const names = [
                        <?php
                        echo '"' . esc_js(__('primary-color', 'snn')) . '", ';
                        echo '"' . esc_js(__('secondary-color', 'snn')) . '", ';
                        echo '"' . esc_js(__('tertiary-color', 'snn')) . '", ';
                        echo '"' . esc_js(__('quaternary-color', 'snn')) . '", ';
                        echo '"' . esc_js(__('quinary-color', 'snn')) . '", ';
                        echo '"' . esc_js(__('senary-color', 'snn')) . '", ';
                        echo '"' . esc_js(__('septenary-color', 'snn')) . '", ';
                        echo '"' . esc_js(__('octonary-color', 'snn')) . '", ';
                        echo '"' . esc_js(__('nonary-color', 'snn')) . '", ';
                        echo '"' . esc_js(__('denary-color', 'snn')) . '"';
                        ?>
                    ];
                    if (index < names.length) {
                        return names[index];
                    } else {
                        return "<?php echo esc_js(__('color-', 'snn')); ?>" + (index + 1);
                    }
                }

                // Insert SNN button into the Bricks toolbar.
                function insertSnnListItem(toolbar) {
                    const ul = (toolbar.tagName.toLowerCase() === "ul") ? toolbar : toolbar.querySelector("ul");
                    if (!ul) return;
                    if (ul.querySelector(".snn-enhance-li")) return;

                    const li = document.createElement("li");
                    li.className = "snn-enhance-li";
                    li.tabIndex = 0;
                    li.setAttribute("data-balloon", "<?php echo esc_js(__('SNN-BRX', 'snn')); ?>");
                    li.setAttribute("data-balloon-pos", "bottom");
                    li.innerHTML = '<?php echo esc_js(__('S', 'snn')); ?>';
                    li.addEventListener("click", function() {
                        const popup = document.querySelector("#snn-popup");
                        if (popup) popup.classList.add("active");
                    });

                    const waitForChildren = setInterval(() => {
                        if (ul.children.length >= 6) {
                            ul.insertBefore(li, ul.children[5]); // Between 5th and 6th
                            clearInterval(waitForChildren);
                        }
                    }, 300);
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

                document.addEventListener("click", function(e) {
                    if (e.target && e.target.classList.contains("snn-close-button")) {
                        var popup = document.querySelector("#snn-popup");
                        if (popup) popup.classList.remove("active");
                    }
                });

                function createColorRow(hex = "", shadeValue = "") {
                    function expandShortHex(hexVal) {
                        return "#" + hexVal[1] + hexVal[1] + hexVal[2] + hexVal[2] + hexVal[3] + hexVal[3];
                    }
                    var displayValue = "";
                    var isValidHex = false;
                    if (hex) {
                        if (/^#([0-9A-Fa-f]{3})$/.test(hex)) {
                            displayValue = expandShortHex(hex).toUpperCase();
                            isValidHex = true;
                        } else if (/^#([0-9A-Fa-f]{6})$/.test(hex)) {
                            displayValue = hex.toUpperCase();
                            isValidHex = true;
                        } else {
                            displayValue = hex;
                            isValidHex = false;
                        }
                    }
                    var row = document.createElement("div");
                    row.className = "snn-color-row";

                    var nameInput = document.createElement("input");
                    nameInput.type = "text";
                    nameInput.className = "snn-color-name-input";
                    nameInput.placeholder = "<?php echo esc_js(__('Variable name', 'snn')); ?>";
                    row.appendChild(nameInput);

                    var hexInput = document.createElement("input");
                    hexInput.type = "text";
                    hexInput.className = "snn-hex-input";
                    hexInput.placeholder = "<?php echo esc_js(__('Enter CSS color', 'snn')); ?>";
                    hexInput.value = displayValue;
                    row.appendChild(hexInput);

                    var colorPicker = null;
                    if (displayValue !== "") {
                        if (isValidHex) {
                            colorPicker = document.createElement("input");
                            colorPicker.type = "color";
                            colorPicker.className = "snn-color-picker";
                            colorPicker.value = displayValue;
                            row.appendChild(colorPicker);
                        }
                    } else {
                        colorPicker = document.createElement("input");
                        colorPicker.type = "color";
                        colorPicker.className = "snn-color-picker";
                        colorPicker.value = "#000000";
                        row.appendChild(colorPicker);
                    }

                    var removeButton = document.createElement("button");
                    removeButton.className = "snn-remove-color";
                    removeButton.textContent = "<?php echo esc_js(__('Remove', 'snn')); ?>";

                    if (isValidHex) {
                        var shadeInput = document.createElement("input");
                        shadeInput.type = "number";
                        shadeInput.className = "snn-shade-input";
                        shadeInput.placeholder = "<?php echo esc_js(__('Shade n', 'snn')); ?>";
                        shadeInput.style.width = "70px";
                        shadeInput.min = "0";
                        shadeInput.step = "1";
                        shadeInput.value = shadeValue;
                        row.appendChild(shadeInput);
                    }
                    row.appendChild(removeButton);

                    hexInput.addEventListener("input", function() {
                        var inputVal = hexInput.value.trim();
                        var validHex3 = /^#([0-9A-Fa-f]{3})$/;
                        var validHex6 = /^#([0-9A-Fa-f]{6})$/;
                        var colorPickerElem = row.querySelector(".snn-color-picker");
                        if (validHex3.test(inputVal)) {
                            var newHex = expandShortHex(inputVal).toUpperCase();
                            hexInput.value = newHex;
                            if (colorPickerElem) {
                                colorPickerElem.value = newHex;
                            } else {
                                var newColorPicker = document.createElement("input");
                                newColorPicker.type = "color";
                                newColorPicker.className = "snn-color-picker";
                                newColorPicker.value = newHex;
                                row.insertBefore(newColorPicker, removeButton);
                            }
                            if (!row.querySelector(".snn-shade-input")) {
                                var newShadeInput = document.createElement("input");
                                newShadeInput.type = "number";
                                newShadeInput.className = "snn-shade-input";
                                newShadeInput.placeholder = "<?php echo esc_js(__('Shade n', 'snn')); ?>";
                                newShadeInput.style.width = "70px";
                                newShadeInput.min = "0";
                                newShadeInput.step = "1";
                                newShadeInput.value = "";
                                row.insertBefore(newShadeInput, removeButton);
                            }
                        } else if (validHex6.test(inputVal)) {
                            var newHex = inputVal.toUpperCase();
                            hexInput.value = newHex;
                            if (colorPickerElem) {
                                colorPickerElem.value = newHex;
                            } else {
                                var newColorPicker = document.createElement("input");
                                newColorPicker.type = "color";
                                newColorPicker.className = "snn-color-picker";
                                newColorPicker.value = newHex;
                                row.insertBefore(newColorPicker, removeButton);
                            }
                            if (!row.querySelector(".snn-shade-input")) {
                                var newShadeInput = document.createElement("input");
                                newShadeInput.type = "number";
                                newShadeInput.className = "snn-shade-input";
                                newShadeInput.placeholder = "<?php echo esc_js(__('Shade n', 'snn')); ?>";
                                newShadeInput.style.width = "70px";
                                newShadeInput.min = "0";
                                newShadeInput.step = "1";
                                newShadeInput.value = "";
                                row.insertBefore(newShadeInput, removeButton);
                            }
                        } else {
                            if (colorPickerElem) {
                                colorPickerElem.remove();
                            }
                            var shadeInputElem = row.querySelector(".snn-shade-input");
                            if (shadeInputElem) {
                                shadeInputElem.remove();
                            }
                        }
                    });
                    
                    row.addEventListener("input", function(e) {
                        if (e.target && e.target.classList.contains("snn-color-picker")) {
                            var newVal = e.target.value;
                            hexInput.value = newVal.toUpperCase();
                        }
                    });

                    removeButton.addEventListener("click", function(e) {
                        e.stopPropagation();
                        row.remove();
                        updateColorNames();
                    });

                    return row;
                }

                function updateColorNames() {
                    var rows = document.querySelectorAll(".snn-color-row");
                    rows.forEach(function(row, index) {
                        var nameInput = row.querySelector(".snn-color-name-input");
                        if (nameInput && document.activeElement !== nameInput) {
                            if(nameInput.value.trim() === "") {
                                nameInput.value = getAutoColorName(index);
                            }
                        }
                    });
                }

                var repeaterContainer = document.getElementById("snn-color-repeater");
                var addColorButton = document.getElementById("snn-add-color");

                var existingColors = <?php echo json_encode($global_colors); ?>;
                if (existingColors === false) {
                    repeaterContainer.appendChild(createColorRow());
                } else if (Array.isArray(existingColors) && existingColors.length > 0) {
                    existingColors.forEach(function(colorItem) {
                        var hex = colorItem.hex ? colorItem.hex : "";
                        var shadeVal = colorItem.shade ? colorItem.shade : "";
                        var row = createColorRow(hex, shadeVal);
                        if(colorItem.name) {
                            var nameInput = row.querySelector(".snn-color-name-input");
                            if(nameInput) {
                                nameInput.value = colorItem.name;
                            }
                        }
                        repeaterContainer.appendChild(row);
                    });
                }
                updateColorNames();

                addColorButton.addEventListener("click", function() {
                    repeaterContainer.appendChild(createColorRow());
                    updateColorNames();
                });

                var saveButton = document.querySelector(".snn-panel-button");
                saveButton.addEventListener("click", function() {
                    var colorRows = document.querySelectorAll(".snn-color-row");
                    var colorsData = [];
                    colorRows.forEach(function(row, index) {
                        var hexValue = row.querySelector(".snn-hex-input").value.trim();
                        var shadeInput = row.querySelector(".snn-shade-input");
                        var shadeValue = shadeInput ? shadeInput.value.trim() : "";
                        var nameInput = row.querySelector(".snn-color-name-input");
                        var customName = nameInput ? nameInput.value.trim() : "";
                        if (!customName) { 
                            customName = getAutoColorName(index);
                        }
                        if (hexValue) {
                            colorsData.push({ name: customName, hex: hexValue, shade: shadeValue });
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
                            feedbackEl.textContent = "<?php echo esc_js(__('Settings saved.', 'snn')); ?>";
                            updateDynamicCSSVariables(colorsData);
                            setTimeout(function() {
                                feedbackEl.textContent = "";
                            }, 3000);
                        } else {
                            feedbackEl.textContent = "<?php echo esc_js(__('Error saving settings:', 'snn')); ?> " + data.data;
                            setTimeout(function() {
                                feedbackEl.textContent = "";
                            }, 3000);
                        }
                    })
                    .catch(error => {
                        console.error("Error:", error);
                        var feedbackEl = document.querySelector(".snn-feedback-after-save");
                        feedbackEl.textContent = "<?php echo esc_js(__('An error occurred while saving settings.', 'snn')); ?>";
                        setTimeout(function() {
                            feedbackEl.textContent = "";
                        }, 3000);
                    });
                });

                function updateDynamicCSSVariables(colorsData) {
                    var styleEl = document.getElementById("snn-dynamic-colors");
                    if (!styleEl) {
                        styleEl = document.createElement("style");
                        styleEl.id = "snn-dynamic-colors";
                        document.head.appendChild(styleEl);
                    }
                    var cssVars = ":root {\n";
                    
                    colorsData.forEach(function(color, index) {
                        var customName = color.name ? color.name : getAutoColorName(index);
                        var baseVarName = "--" + customName;
                        cssVars += "  " + baseVarName + ": " + color.hex + ";\n";

                        var shadeCount = parseInt(color.shade, 10);
                        if (!isNaN(shadeCount) && shadeCount > 0) {
                            for (var i = 1; i <= shadeCount; i++) {
                                var fraction = i / (shadeCount + 1);
                                var lightHex = lightenColor(color.hex, fraction);
                                cssVars += "  " + baseVarName + "-light-" + i + ": " + lightHex + ";\n";
                            }
                            for (var i = 1; i <= shadeCount; i++) {
                                var fraction = i / (shadeCount + 1);
                                var darkHex = darkenColor(color.hex, fraction);
                                cssVars += "  " + baseVarName + "-dark-" + i + ": " + darkHex + ";\n";
                            }
                        }
                    });
                    cssVars += "}";
                    styleEl.textContent = cssVars;
                }

                document.addEventListener("click", function(e) {
                    if (e.target.closest('.snn-enhance-li')) {
                        return;
                    }
                    var popup = document.getElementById("snn-popup");
                    var popupInner = document.getElementById("snn-popup-inner");
                    if (popup && popup.classList.contains("active") && popupInner && !popupInner.contains(e.target)) {
                        popup.classList.remove("active");
                    }
                });

                document.addEventListener("keydown", function(e) {
                    if (e.key === "Escape") {
                        var popup = document.getElementById("snn-popup");
                        if (popup && popup.classList.contains("active")) {
                            popup.classList.remove("active");
                        }
                    }
                });


            });
        </script>

        <style>
        #snn-popup{align-items:center;background-color:#ffffffdd;bottom:0;color:#fff;display:none;font-size:14px;justify-content:center;left:0;padding:60px;position:fixed;right:0;top:0;z-index:10001;}
        #snn-popup.active{display:flex;}
        #snn-popup h1{font-size:2em;font-weight:600;}
        .snn-enhance-li{width:26px !important;padding-left:3px;font-size:21px;letter-spacing:-0.3px;padding-top:0px;color:#b0b4b7;}
        .snn-enhance-li i{font-size:19px;opacity:0.8;}
        #snn-popup-inner{background-color:var(--builder-bg);border-radius:var(--builder-border-radius);box-shadow:0 6px 24px 0 rgba(0, 0, 0, 0.25);display:flex;flex-direction:column;height:100%;max-width:1200px;overflow-y:auto;position:relative;width:100%;color:#fff;padding:20px;}
        #snn-popup .snn-filters li.active{background-color:var(--builder-bg-accent);border-radius:var(--builder-border-radius);color:#fff;}
        #snn-popup .snn-title-wrapper{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;position:relative;}
        .snn-close-button{cursor:pointer;font-size:28px;background:transparent;border:none;color:var(--builder-color-accent);transform:scaleX(1.3);}
        .snn-close-button:hover{color:white;}
        .snn-toolbar-container{background-color:#f5f5f5 !important;border:1px solid #ddd !important;padding:10px !important;border-radius:5px !important;}
        .snn-toolbar-container:hover{background-color:#e0e0e0 !important;}
        .snn-toolbar-container li{font-size:12px;}
        .snn-settings-content-wrapper{height:calc(100% - 50px);}
        .snn-panel-button{align-items:center;background-color:var(--builder-bg-3);border-radius:var(--builder-border-radius);cursor:pointer;display:flex;height:var(--builder-popup-input-height);width:157px;padding:12px;font-size:16px;letter-spacing:0.3px;}
        .snn-panel-button:hover{background-color:var(--builder-color-accent);color:black;}
        .snn-panel-button svg{margin-right:10px;}
        .snn-settings-content-wrapper-section{padding:10px;border:solid #00000055 1px;border-radius:4px;margin-bottom:15px;}
        .snn-color-row{display:flex;align-items:center;margin-bottom:4px;}
        .snn-color-row .snn-color-name-input,.snn-color-row .snn-shade-input{width:120px;text-align:center;font-size:14px;margin-right:10px;background:#171a1d;line-height:32px;height:32px;color:#868686;border:none;padding:0;}
        .snn-color-row input[type="text"]{padding:5px;font-size:16px;width:170px;text-align:center;margin-right:10px;background:#171a1d;border:0;line-height:22px;}
        .snn-color-row input[type="text"]::placeholder,.snn-color-row .snn-shade-input::placeholder{color:#ffffff44;}
        .snn-color-row input[type="color"]{width:50px;height:22px;border:none;cursor:pointer;margin-right:10px;padding:0;}
        .snn-color-row button.snn-remove-color{padding:5px 10px;font-size:14px;cursor:pointer;background:#171a1d;color:white;}
        .snn-color-row button.snn-remove-color:hover{background-color:var(--builder-color-accent);color:black;}
        #snn-add-color{margin-top:10px;padding:8px 12px;font-size:14px;cursor:pointer;background:#171a1d;color:white;}
        .snn-feedback-after-save{margin-bottom:10px;font-size:14px;color:#0f0;}
        .snn-settings-content-wrapper-section-title{margin-bottom:5px;}
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
        $_GET['bricks'] === 'run' &&
        current_user_can('manage_options')
    ) {
        ?>
        <div id="snn-popup" class="snn-popup docs">
            <div id="snn-popup-inner" class="snn-popup-inner">
                <div class="snn-title-wrapper">
                    <h1><?php _e('Settings', 'snn'); ?></h1>
                    <button class="snn-close-button"><?php _e('X', 'snn'); ?></button>
                </div>
                <div class="snn-settings-content-wrapper">
                    <div class="snn-settings-content-wrapper-section">
                        <div class="snn-settings-content-wrapper-section-title">
                            <?php _e('Global Color Variables', 'snn'); ?>
                        </div>
                        <div class="snn-settings-content-wrapper-section-setting-area">
                            <div id="snn-color-repeater">
                            </div>
                            <button type="button" id="snn-add-color"><?php _e('Add Color +', 'snn'); ?></button>
                        </div>
                    </div>
                </div>
                
                <div class="snn-panel-button" data-balloon="<?php esc_attr_e('Refresh Editor After Save', 'snn'); ?>" data-balloon-pos="top">
                    <span class="bricks-svg-wrapper" data-name="save">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" class="bricks-svg">
                            <path d="M21.75 23.25H2.25a1.5 1.5 0 0 1 -1.5 -1.5V7.243a3 3 0 0 1 0.879 -2.121l3.492 -3.493A3 3 0 0 1 7.243 0.75H21.75a1.5 1.5 0 0 1 1.5 1.5v19.5a1.5 1.5 0 0 1 -1.5 1.5Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            <path d="M9.75 12.75a3 3 0 1 0 6 0 3 3 0 1 0 -6 0Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            <path d="m12.75 20.25 6.75 0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            <path d="M8.25 0.75v3a1.5 1.5 0 0 0 1.5 1.5h7.5a1.5 1.5 0 0 0 1.5 -1.5v-3" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        <?php _e('Save Settings', 'snn'); ?>
                    </span>
                </div>
                <div class="snn-feedback-after-save"></div>
            </div>
        </div>
        <?php
    }
}
add_action('wp_footer', 'snn_popup_container_improved');


function snn_save_color_settings_improved() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __('Unauthorized', 'snn') );
        wp_die();
    }
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'snn_save_colors_nonce' ) ) {
        wp_send_json_error(__('Invalid nonce', 'snn'));
        wp_die();
    }
    if ( ! isset( $_POST['colors'] ) ) {
        wp_send_json_error(__('No colors data provided', 'snn'));
        wp_die();
    }
    $colors = json_decode( stripslashes( $_POST['colors'] ), true );
    if ( ! is_array( $colors ) ) {
        wp_send_json_error(__('Invalid colors data', 'snn'));
        wp_die();
    }
    update_option( 'snn_global_color_sync_variables', $colors );
    wp_send_json_success(__('Settings saved', 'snn'));
    wp_die();
}
add_action('wp_ajax_snn_save_colors_improved', 'snn_save_color_settings_improved');

function snn_dynamic_color_variables_roots() {
    $colors = get_option('snn_global_color_sync_variables', []);
    if ( ! empty($colors) && is_array($colors) ) {
         echo '<style id="snn-dynamic-colors">'."\n";
         echo ":root {\n";
         foreach ( $colors as $index => $color ) {
            $hex = isset($color['hex']) ? $color['hex'] : '';
            if( $hex === '' ){
                continue;
            }
            $varName = (!empty($color['name'])) ? $color['name'] : __('snn-color-', 'snn') . ($index + 1);
            echo "  --".$varName.": ".$hex.";\n";
            $shadeCount = isset($color['shade']) ? intval($color['shade']) : 0;
            if ($shadeCount > 0) {
                for ($j = 1; $j <= $shadeCount; $j++) {
                    $fraction = $j / ($shadeCount + 1);
                    $lightHex = snn_lighten_color($hex, $fraction);
                    echo "  --".$varName."-light-".$j.": ".$lightHex.";\n";
                }
                for ($j = 1; $j <= $shadeCount; $j++) {
                    $fraction = $j / ($shadeCount + 1);
                    $darkHex = snn_darken_color($hex, $fraction);
                    echo "  --".$varName."-dark-".$j.": ".$darkHex.";\n";
                }
            }
         }
         echo "}\n</style>\n";
    }
}
add_action( 'wp_head', 'snn_dynamic_color_variables_roots', 1 );
add_action( 'wp_footer', 'snn_dynamic_color_variables_roots', 9999 );


function snn_inject_bricks_color_palette() {
    $options = get_option('snn_editor_settings');
    if (
        isset($options['snn_bricks_builder_color_fix']) &&
        $options['snn_bricks_builder_color_fix'] &&
        isset($_GET['bricks']) &&
        $_GET['bricks'] === 'run'
    ) {
        $colors = get_option('snn_global_color_sync_variables', []);
        if ( ! empty($colors) && is_array($colors) ) {
            echo "<script>
(function(){
  if (
    typeof bricksData !== 'undefined' &&
    bricksData.loadData &&
    bricksData.loadData.colorPalette &&
    bricksData.loadData.colorPalette[0]
  ) {
    var palette   = bricksData.loadData.colorPalette[0].colors;
    var newColors = [
";
            foreach ( $colors as $index => $color ) {
                if ( isset($color['hex']) && $color['hex'] !== '' ) {
                    $varName    = ! empty($color['name']) ? $color['name'] : __('snn-color-', 'snn') . ($index + 1);
                    echo '      {
        "raw": "var(--' . $varName . ')",
        "id": "snn1' . $index . '",
        "name": "' . $varName . '"
      }';
                    $shadeCount = isset($color['shade']) ? intval($color['shade']) : 0;
                    for ( $i = 1; $i <= $shadeCount; $i++ ) {
                        $lightVar = $varName . '-light-' . $i;
                        echo ",
      {
        \"raw\": \"var(--$lightVar)\",
        \"id\": \"snn1light{$index}-{$i}\",
        \"name\": \"$lightVar\"
      }";
                    }
                    for ( $i = 1; $i <= $shadeCount; $i++ ) {
                        $darkVar = $varName . '-dark-' . $i;
                        echo ",
      {
        \"raw\": \"var(--$darkVar)\",
        \"id\": \"snn1dark{$index}-{$i}\",
        \"name\": \"$darkVar\"
      }";
                    }
                    if ( $index < count($colors) - 1 ) {
                        echo ",";
                    }
                    echo "\n";
                }
            }
            echo "    ];
    var ids      = newColors.map(function(c){ return c.id; });
    var defaults = palette.filter(function(item){
      return ids.indexOf(item.id) === -1;
    });
    palette.splice(0, palette.length, ...newColors, ...defaults);
  }
})();
</script>
";
        }
    }
}
add_action('wp_footer', 'snn_inject_bricks_color_palette', 100000);



if ( ! function_exists('snn_hex_to_hsl') ) {
    function snn_hex_to_hsl($hex) {
        if (preg_match('/^#([A-Fa-f0-9]{3})$/', $hex)) {
            $hex = '#'.$hex[1].$hex[1].$hex[2].$hex[2].$hex[3].$hex[3];
        }
        $r = hexdec(substr($hex,1,2)) / 255;
        $g = hexdec(substr($hex,3,2)) / 255;
        $b = hexdec(substr($hex,5,2)) / 255;
        $max = max($r,$g,$b);
        $min = min($r,$g,$b);
        $h; 
        $s;
        $l = ($max + $min) / 2;
        if($max == $min){
            $h = $s = 0;
        } else {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
            switch($max){
                case $r:
                    $h = ($g - $b) / $d + ($g < $b ? 6 : 0); 
                    break;
                case $g: 
                    $h = ($b - $r) / $d + 2; 
                    break;
                case $b: 
                    $h = ($r - $g) / $d + 4; 
                    break;
            }
            $h /= 6;
        }
        return array($h, $s, $l);
    }
}

if ( ! function_exists('snn_hsl_to_hex') ) {
    function snn_hsl_to_hex($h, $s, $l) {
        $r; 
        $g; 
        $b;
        if($s == 0){
            $r = $g = $b = $l;
        } else {
            $hue2rgb = function($p, $q, $t) use (&$hue2rgb) {
                if($t < 0) $t += 1;
                if($t > 1) $t -= 1;
                if($t < 1/6) return $p + ($q - $p) * 6 * $t;
                if($t < 1/2) return $q;
                if($t < 2/3) return $p + ($q - $p) * (2/3 - $t) * 6;
                return $p;
            };
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;
            $r = $hue2rgb($p, $q, $h + 1/3);
            $g = $hue2rgb($p, $q, $h);
            $b = $hue2rgb($p, $q, $h - 1/3);
        }
        $toHex = function($x){
            $hexVal = dechex((int)round($x * 255));
            return str_pad($hexVal, 2, "0", STR_PAD_LEFT);
        };
        return "#".$toHex($r).$toHex($g).$toHex($b);
    }
}

if ( ! function_exists('snn_lighten_color') ) {
    function snn_lighten_color($hex, $fraction) {
        list($h, $s, $l) = snn_hex_to_hsl($hex);
        $l = $l + (1 - $l) * $fraction;
        return snn_hsl_to_hex($h, $s, $l);
    }
}

if ( ! function_exists('snn_darken_color') ) {
    function snn_darken_color($hex, $fraction) {
        list($h, $s, $l) = snn_hex_to_hsl($hex);
        $l = $l * (1 - $fraction);
        return snn_hsl_to_hex($h, $s, $l);
    }
}
?>
