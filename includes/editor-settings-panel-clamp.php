<?php

// Clamp calculator functionality for SNN Bricks editor

// Function to render the clamp calculator section in the popup
function snn_render_clamp_calculator_section() {
?>
<div class="snn-settings-content-wrapper-section">
    <div class="snn-settings-content-wrapper-section-title">
        <p style="margin-bottom:20px; font-size:14px; color:var(--builder-color-accent); max-width:550px"><?php _e('Generate CSS clamp() values for responsive typography and spacing. Copy the generated value or save it directly to Bricks Global Variables for easy reuse. (Do not forget to match your HTML font-size with clamp. Go to: Theme Styles > Typography > HTML font-size)', 'snn'); ?></p>
    </div>
    <div class="snn-settings-content-wrapper-section-setting-area snn-clamp-container">
        






<!-- Custom CSS for Styling -->
<style>
    .snn-clamp-container :root {
        --gray-50: #f9fafb;
        --gray-100: #f3f4f6;
        --gray-200: #e5e7eb;
        --gray-300: #d1d5db;
        --gray-600: #4b5563;
        --gray-700: #374151;
        --gray-800: #1f2937;
        --gray-900: #111827; /* Darker black for input bg */
        --blue-500: #3b82f6;
        --blue-600: #2563eb;
        --font-sans: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        --font-mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    }

    .snn-clamp-container .clamp-calculator-container * {
        box-sizing: border-box;
    }

    .snn-clamp-container .clamp-calculator-container {
        width: 100%;
        font-family: var(--font-sans);
    }

    /* Form Controls Grid */
    .snn-clamp-container #controls {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
        margin-bottom: 2rem; /* Increased margin after removing button */
    }

    @media (min-width: 768px) {
        .snn-clamp-container #controls {
            grid-template-columns: repeat(2, 1fr);
        }
        .snn-clamp-container .md-col-span-2 {
            grid-column: span 2 / span 2;
        }
    }

    /* Labels and Inputs */
    .snn-clamp-container label {
        display: block;
        font-weight: 600;
        color: white;
        margin-bottom: 0.25rem;
    }

    .snn-clamp-container input[type="number"],
    .snn-clamp-container input[type="text"] {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid var(--gray-800);
        border-radius: 0.5rem;
        outline: none;
        transition: all 0.2s ease-in-out;
        /* Dark Theme for Inputs */
        background-color: black;
        color: white;
        font-size: 16px;
    }

    /* Placeholder text color */
    .snn-clamp-container input[type="number"]::placeholder,
    .snn-clamp-container input[type="text"]::placeholder {
        color: var(--gray-300);
    }
    
    /* Custom focus ring colors for dark inputs */
    .snn-clamp-container input[type="number"]:focus,
    .snn-clamp-container input[type="text"]:focus {
        border-color: var(--builder-color-accent);
        box-shadow: 0 0 0 2px var(--builder-color-accent);
    }
    .snn-clamp-container #minSize:focus { box-shadow: 0 0 0 2px var(--builder-color-accent); border-color: var(--builder-color-accent); }
    .snn-clamp-container #maxSize:focus { box-shadow: 0 0 0 2px var(--builder-color-accent); border-color: var(--builder-color-accent); }
    .snn-clamp-container #minViewport:focus { box-shadow: 0 0 0 2px var(--builder-color-accent); border-color: var(--builder-color-accent); }
    .snn-clamp-container #maxViewport:focus { box-shadow: 0 0 0 2px var(--builder-color-accent); border-color: var(--builder-color-accent); }
    .snn-clamp-container #rootFontSize:focus { box-shadow: 0 0 0 2px var(--builder-color-accent); border-color: var(--builder-color-accent); }
    .snn-clamp-container #variableName:focus { box-shadow: 0 0 0 2px var(--builder-color-accent); border-color: var(--builder-color-accent); }

    /* Buttons */
    .snn-clamp-container .copy-btn,
    .snn-clamp-container .save-btn {
        margin-top: 0.5rem;
        color: #fff;
        padding: 0.5rem 1rem;
        border-radius: 0.375rem;
        border: none;
        cursor: pointer;
        font-size: 18px; /* Slightly adjusted font size */
        font-weight: 500;
        transition: background-color 0.2s ease-in-out;
        display: flex;
        align-items: center;
    }

    .snn-clamp-container .copy-btn {
        background-color: var(--blue-500);
    }

    .snn-clamp-container .save-btn {
        background-color: #10b981; /* Green color for save button */
    }

    @media (min-width: 640px) {
        .snn-clamp-container .copy-btn,
        .snn-clamp-container .save-btn {
            margin-top: 0;
        }
    }

    .snn-clamp-container .copy-btn:hover {
        background-color: var(--blue-600);
    }

    .snn-clamp-container .save-btn:hover {
        background-color: #059669; /* Darker green on hover */
    }

    .snn-clamp-container .copy-btn:focus,
    .snn-clamp-container .save-btn:focus {
        outline: none;
        box-shadow: 0 0 0 2px currentColor;
    }

    .snn-clamp-container .copy-btn svg,
    .snn-clamp-container .save-btn svg {
        width: 20px;
        height: 20px;
        margin-right: 0.5rem;
    }

    /* Result Box */
    .snn-clamp-container #resultBox {
        background-color: black;
        padding: 1.5rem;
        border-radius: 0.75rem;
        border: 1px solid var(--gray-200);
        box-shadow: inset 0 2px 4px 0 rgb(0 0 0 / 0.05);
    }
    
    .snn-clamp-container .result-wrapper {
        background-color: var(--gray-100);
        padding: 1rem;
        border-radius: 0.5rem;
        border: 1px solid var(--gray-200);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .snn-clamp-container #result {
        font-family: var(--font-mono);
        font-size: 18px; /* Slightly adjusted font size */
        font-weight: 600;
        color: var(--gray-800);
        word-break: break-all;
        flex: 1 1 100%;
        padding-right: 1rem;
    }

    .snn-clamp-container .button-group {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        width: 100%;
    }

    @media (min-width: 640px) {
        .snn-clamp-container #result {
            flex: 1 1 auto;
        }
        .snn-clamp-container .button-group {
            width: auto;
        }
    }

    /* Pure CSS Tooltip */
    .snn-clamp-container [data-tooltip] {
        position: relative;
    }
    .snn-clamp-container [data-tooltip]::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        margin-bottom: 5px;
        background: var(--gray-800);
        color: white;
        padding: 0.3rem 0.6rem;
        border-radius: 0.25rem;
        font-size: 14px;
        font-weight: 400;
        white-space: nowrap;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.2s, visibility 0.2s;
        z-index: 10;
    }
    .snn-clamp-container [data-tooltip]:hover::after {
        opacity: 1;
        visibility: visible;
    }
</style>

<!-- HTML Structure -->
<div class="clamp-calculator-container">
    <div id="controls">
        <div class="input-group">
            <label for="minSize">Minimum Size (px):</label>
            <input type="number" id="minSize" value="16" min="1" step="1" placeholder="e.g. 16"
                   data-tooltip="The smallest font size your text will scale down to.">
        </div>

        <div class="input-group">
            <label for="maxSize">Maximum Size (px):</label>
            <input type="number" id="maxSize" value="190" min="1" step="1" placeholder="e.g. 190"
                   data-tooltip="The largest font size your text will scale up to.">
        </div>

        <div class="input-group">
            <label for="minViewport">Minimum Viewport/Container (px):</label>
            <input type="number" id="minViewport" value="380" min="1" step="1" placeholder="e.g. 360"
                   data-tooltip="The viewport width at which the font size will be at its minimum.">
        </div>

        <div class="input-group">
            <label for="maxViewport">Maximum Viewport/Container (px):</label>
            <input type="number" id="maxViewport" value="1400" min="1" step="1" placeholder="e.g. 1400"
                   data-tooltip="The viewport width at which the font size will be at its maximum.">
        </div>

        <div class="input-group md-col-span-2">
            <label for="rootFontSize">HTML Root Font Size (px):</label>
            <input type="number" id="rootFontSize" value="16" min="1" step="1" placeholder="e.g. 16"
                   data-tooltip="The base font size on your HTML root (usually 16px). Used for 'rem' conversion.">
        </div>

        <div class="input-group md-col-span-2">
            <label for="variableName">Variable Name:</label>
            <input type="text" id="variableName" value="" placeholder="size-99"
                   data-tooltip="Enter a unique name for this clamp value to save it as a global variable.">
        </div>
    </div>

    <div id="resultBox">
        <div class="result-wrapper">
            <p id="result">
                clamp(1rem, -2.764rem + 16.731vw, 11.875rem)
            </p>
            <div class="button-group">
                <button onclick="copyToClipboard()" class="copy-btn" aria-label="Copy clamp() to clipboard" data-tooltip="Copy clamp() to clipboard" type="button" data-snn-no-close="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                        <rect x="3" y="8" width="13" height="13" rx="2" ry="2"></rect>
                        <path d="M8 8V6a2 2 0 0 1 2-2h7a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path>
                        <path d="M7 13h5"></path>
                    </svg>
                    <span class="copy-text">Copy</span>
                </button>
                <button onclick="saveToGlobalVariables()" class="save-btn" aria-label="Save to Global Variables" data-tooltip="Save to Global Variables" type="button" data-snn-no-close="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    <span class="save-text">Save</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript Logic -->
<script>
    /**
     * Calculates the CSS clamp() function values based on user inputs.
     * Updates the result display and calculation info in real-time.
     */
    function calculateClamp() {
        const minSizeEl = document.getElementById("minSize");
        const maxSizeEl = document.getElementById("maxSize");
        const minViewportEl = document.getElementById("minViewport");
        const maxViewportEl = document.getElementById("maxViewport");
        const rootFontSizeEl = document.getElementById("rootFontSize");

        const minSize = parseFloat(minSizeEl.value);
        const maxSize = parseFloat(maxSizeEl.value);
        const minViewport = parseFloat(minViewportEl.value);
        const maxViewport = parseFloat(maxViewportEl.value);
        const rootFontSize = parseFloat(rootFontSizeEl.value);

        // --- Validation ---
        if (isNaN(minSize) || isNaN(maxSize) || isNaN(minViewport) || isNaN(maxViewport) || isNaN(rootFontSize)) {
            document.getElementById("result").textContent = "Please enter valid numbers in all fields.";
            return;
        }

        if (minSize <= 0 || maxSize <= 0 || minViewport <= 0 || maxViewport <= 0 || rootFontSize <= 0) {
            document.getElementById("result").textContent = "All values must be greater than zero.";
            return;
        }

        if (maxViewport <= minViewport) {
            document.getElementById("result").textContent = "Max viewport must be greater than min viewport.";
            return;
        }
        
        if (maxSize <= minSize) {
            document.getElementById("result").textContent = "Max size should be greater than min size.";
            return;
        }

        // --- Calculation ---
        const slope = (maxSize - minSize) / (maxViewport - minViewport);
        const base = minSize - slope * minViewport;
        const clampResult = `clamp(${(minSize / rootFontSize).toFixed(3)}rem, ${(base / rootFontSize).toFixed(3)}rem + ${(slope * 100).toFixed(3)}vw, ${(maxSize / rootFontSize).toFixed(3)}rem)`;

        document.getElementById("result").textContent = clampResult;
    }

    /**
     * Copies the generated clamp() CSS to the clipboard.
     */
    function copyToClipboard() {
        const resultText = document.getElementById("result").textContent;
        if (!resultText || resultText.includes("Please") || resultText.includes("must be")) {
             // Don't copy error messages
            return;
        }
        try {
            const textarea = document.createElement('textarea');
            textarea.value = resultText;
            textarea.style.position = 'absolute';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);

            const copyButton = document.querySelector('.copy-btn');
            if (!copyButton.dataset.originalContent) {
                copyButton.dataset.originalContent = copyButton.innerHTML;
            }
            copyButton.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10"></circle><path d="M9 12.5l2 2 4-5"/></svg><span class="copy-text">Copied</span>';
            copyButton.setAttribute('aria-label','Copied!');
            setTimeout(() => {
                copyButton.innerHTML = copyButton.dataset.originalContent;
                copyButton.setAttribute('aria-label','Copy clamp() to clipboard');
            }, 2000);
        } catch (err) {
            console.error('Failed to copy text: ', err);
        }
    }

    /**
     * Saves the generated clamp() value to Bricks Global Variables.
     */
    function saveToGlobalVariables() {
        const resultText = document.getElementById("result").textContent;
        const variableName = document.getElementById("variableName").value.trim();

        // Validate inputs
        if (!resultText || resultText.includes("Please") || resultText.includes("must be")) {
            alert("Please generate a valid clamp() value first.");
            return;
        }

        if (!variableName) {
            alert("Please enter a variable name before saving.");
            document.getElementById("variableName").focus();
            return;
        }

        try {
            // Access Bricks Vue state
            const vueApp = document.querySelector("[data-v-app]");
            if (!vueApp || !vueApp.__vue_app__) {
                alert("Error: Could not access Bricks editor state. Please make sure you're in the Bricks editor.");
                console.error("Bricks Vue app not found");
                return;
            }

            const bricksState = vueApp.__vue_app__.config.globalProperties.$_state;
            if (!bricksState) {
                alert("Error: Could not access Bricks state.");
                console.error("Bricks state not found");
                return;
            }

            // Get existing global variables or initialize empty array
            if (!bricksState.globalVariables) {
                bricksState.globalVariables = [];
            }

            const globalVariables = bricksState.globalVariables;

            // Check if variable name already exists
            const existingVariable = globalVariables.find(v => v.name === variableName);
            if (existingVariable) {
                const confirmUpdate = confirm(`Variable "${variableName}" already exists. Do you want to update it?`);
                if (!confirmUpdate) {
                    return;
                }
                // Update existing variable
                existingVariable.value = resultText;
            } else {
                // Generate unique ID (similar to how Bricks does it)
                const generateUniqueId = () => {
                    const timestamp = Date.now().toString(36);
                    const randomStr = Math.random().toString(36).substring(2, 9);
                    return `${timestamp}${randomStr}`;
                };

                // Create new global variable object
                const newVariable = {
                    id: generateUniqueId(),
                    name: variableName,
                    value: resultText
                };

                // Add to global variables array
                globalVariables.push(newVariable);
            }

            // Update the save button to show success
            const saveButton = document.querySelector('.save-btn');
            if (!saveButton.dataset.originalContent) {
                saveButton.dataset.originalContent = saveButton.innerHTML;
            }
            saveButton.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10"></circle><path d="M9 12.5l2 2 4-5"/></svg><span class="save-text">Saved!</span>';
            saveButton.setAttribute('aria-label', 'Saved!');

            setTimeout(() => {
                saveButton.innerHTML = saveButton.dataset.originalContent;
                saveButton.setAttribute('aria-label', 'Save to Global Variables');
            }, 2000);

            console.log(`Successfully saved variable "${variableName}" with value: ${resultText}`);
            console.log('Current global variables:', globalVariables);

        } catch (err) {
            console.error('Failed to save to global variables:', err);
            alert("Error: Failed to save to global variables. Check console for details.");
        }
    }

    // --- Event Listeners ---
    const inputs = document.querySelectorAll("#controls input");
    inputs.forEach(input => {
        input.addEventListener("input", calculateClamp);
    });

    // Make functions available globally
    window.copyToClipboard = copyToClipboard;
    window.saveToGlobalVariables = saveToGlobalVariables;

    document.addEventListener('DOMContentLoaded', () => {
        calculateClamp();
    });
</script>







    
    </div>
</div>
<?php
}