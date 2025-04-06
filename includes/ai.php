<?php

function snn_add_ai_settings_submenu() {
    add_submenu_page(
        'snn-settings',  // Parent slug
        'AI Settings',   // Page title
        'AI',            // Menu title
        'manage_options',// Capability
        'snn-ai-settings',// Menu slug
        'snn_render_ai_settings' // Callback function
    );
}
add_action('admin_menu', 'snn_add_ai_settings_submenu');

function snn_register_ai_settings() {
    register_setting('snn_ai_settings_group', 'snn_ai_enabled');
    register_setting('snn_ai_settings_group', 'snn_openai_api_key');
    register_setting('snn_ai_settings_group', 'snn_openai_model');
    register_setting('snn_ai_settings_group', 'snn_system_prompt');
    // New setting for action presets (stored as an array)
    register_setting('snn_ai_settings_group', 'snn_ai_action_presets');
}
add_action('admin_init', 'snn_register_ai_settings');

function snn_render_ai_settings() {
    $ai_enabled     = get_option('snn_ai_enabled', 'no');
    $openai_api_key = get_option('snn_openai_api_key', '');
    $openai_model   = get_option('snn_openai_model', 'gpt-4o-mini');
    $system_prompt  = get_option('snn_system_prompt', 'You are a helpful assistant that helps with content creation or manipulation. You work inside a wordpress visual builder. User usually changes a website content. Keep the content length as similar the existing content when you are editing or follow the users instructions accordingly. Generate with as same language as the content unless told otherwise. Only respond with the needed content and nothing else always!');

    // Default presets if the user has NEVER saved any presets (the option is false)
    $default_presets = [
        ['name' => 'Title', 'prompt' => 'Generate a catchy title.'],
        ['name' => 'Content', 'prompt' => 'Generate engaging content.'],
        ['name' => 'Button', 'prompt' => 'Suggest a call-to-action button text.'],
        ['name' => 'Funny', 'prompt' => 'Make it funny.'],
        ['name' => 'Sad', 'prompt' => 'Make it sad.'],
        ['name' => 'Business', 'prompt' => 'Make it professional and business-like.'],
        ['name' => 'CSS', 'prompt' => 'Ignore all previous instructions. Write clean native CSS only. Always use selector %root%, no <style> tag.'],
    ];

    $stored_action_presets = get_option('snn_ai_action_presets', false);
    if ($stored_action_presets === false) {
        // Option has never been saved; use defaults
        $action_presets = $default_presets;
    } elseif (!is_array($stored_action_presets)) {
        // If something invalid got saved, reset to an empty array
        $action_presets = [];
    } else {
        // The user saved something valid (including possibly an empty array)
        $action_presets = $stored_action_presets;
    }
    ?>
    <div class="wrap">
        <h1>AI Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('snn_ai_settings_group');
            do_settings_sections('snn-ai-settings');
            ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="snn_ai_enabled">Enable AI Features</label>
                    </th>
                    <td>
                        <input type="checkbox" name="snn_ai_enabled" id="snn_ai_enabled" value="yes" <?php checked($ai_enabled, 'yes'); ?> />
                        <p class="description">Check this box to enable AI features.</p>
                    </td>
                </tr>
            </table>

            <!-- Wrap the OpenAI API Settings and Action Presets in one container -->
            <div id="openai-settings" style="display: <?php echo ($ai_enabled === 'yes') ? '' : 'none'; ?>;">
                <h2>OpenAI API Settings</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="snn_openai_api_key">OpenAI API Key</label>
                        </th>
                        <td>
                            <input type="text" name="snn_openai_api_key" id="snn_openai_api_key" value="<?php echo esc_attr($openai_api_key); ?>" class="regular-text" />
                            <p class="description">
                                Enter your OpenAI API key.<br>
                                For more information, visit the
                                <a href="https://platform.openai.com/settings/organization/api-keys" target="_blank">OpenAI API Documentation</a>.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="snn_openai_model">OpenAI Model</label>
                        </th>
                        <td>
                            <select name="snn_openai_model" id="snn_openai_model">
                                <option value="gpt-4o-mini" <?php selected($openai_model, 'gpt-4o-mini'); ?>>
                                    gpt-4o-mini (128k context)
                                </option>
                                <option value="gpt-4o" <?php selected($openai_model, 'gpt-4o'); ?>>
                                    gpt-4o (128k context)
                                </option>
                                <option value="o1" <?php selected($openai_model, 'o1'); ?>>
                                    o1 (128k context)
                                </option>
                                <option value="o1-pro" <?php selected($openai_model, 'o1-pro'); ?>>
                                    o1-pro (128k context)
                                </option>
                                <option value="o3-mini" <?php selected($openai_model, 'o3-mini'); ?>>
                                    o3-mini (200k context)
                                </option>
                                <option value="o1-mini" <?php selected($openai_model, 'o1-mini'); ?>>
                                    o1-mini (128k context)
                                </option>
                            </select>
                            <p class="description">
                                Select the OpenAI model to use. <br>
                                The context length indicates the maximum number of tokens.<br>
                                <a href="https://openai.com/api/pricing/" target="_blank">OpenAI API Model Prices</a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="snn_system_prompt">System Prompt</label>
                        </th>
                        <td>
                            <textarea name="snn_system_prompt" id="snn_system_prompt" class="regular-text" rows="5"><?php echo esc_textarea($system_prompt); ?></textarea>
                            <p class="description">
                                Enter the system prompt for AI interactions.
                            </p>
                        </td>
                    </tr>
                </table>

                <h2>Action Presets</h2>
                <p>Add, edit, or remove AI action presets. These presets will be available as selectable buttons in the AI overlay.</p>
                <table class="form-table" id="snn-ai-action-presets-table">
                    <tbody>
                    <?php if (!empty($action_presets)) : ?>
                        <?php foreach ($action_presets as $index => $preset) : ?>
                            <tr class="snn-ai-action-preset-row">
                                <td style="padding:0">
                                    <input type="text" name="snn_ai_action_presets[<?php echo $index; ?>][name]" value="<?php echo esc_attr($preset['name']); ?>" class="regular-text" />
                                </td>
                                <td style="padding:0">
                                    <textarea name="snn_ai_action_presets[<?php echo $index; ?>][prompt]" rows="2" class="regular-text"><?php echo esc_textarea($preset['prompt']); ?></textarea>
                                </td>
                                <td style="padding:0">
                                    <button class="button snn-ai-remove-preset">Remove</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
                <p>
                    <button type="button" class="button" id="snn-ai-add-preset">Add Preset</button>
                </p>
            </div>
            <style>
            #snn-ai-action-presets-table{
                max-width:660px;
            }
            #snn-ai-action-presets-table td{
                vertical-align: top;
            }
            .snn-ai-action-preset-row input.regular-text{
                max-width: 220px;
                height:46px;
            }
            #openai-settings #snn_system_prompt,
            #openai-settings #snn_openai_model,
            #openai-settings #snn_openai_api_key {
                width:430px;
                max-width:430px;
            }
            #openai-settings #snn_system_prompt{
                min-height:200px;
            }
            </style>
                        
            <?php submit_button(); ?>
        </form>
        <script>
        document.getElementById('snn_ai_enabled').addEventListener('change', function() {
            var openaiSettings = document.getElementById('openai-settings');
            openaiSettings.style.display = this.checked ? '' : 'none';
        });

        // Repeater functionality for action presets
        document.getElementById('snn-ai-add-preset').addEventListener('click', function() {
            var tableBody = document.querySelector('#snn-ai-action-presets-table tbody');
            var index = tableBody.children.length;
            var row = document.createElement('tr');
            row.className = 'snn-ai-action-preset-row';
            row.innerHTML = '<td><label>Action Name:</label><input type="text" name="snn_ai_action_presets[' + index + '][name]" class="regular-text" /></td>' +
                            '<td><label>Action Prompt:</label><textarea name="snn_ai_action_presets[' + index + '][prompt]" rows="2" class="regular-text"></textarea></td>' +
                            '<td><button class="button snn-ai-remove-preset">Remove</button></td>';
            tableBody.appendChild(row);
        });

        document.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('snn-ai-remove-preset')) {
                e.preventDefault();
                var row = e.target.closest('tr');
                row.parentNode.removeChild(row);
            }
        });
        </script>
    </div>
    <?php
}

// Add script to footer when in admin and Bricks Builder is active
function snn_add_ai_script_to_footer() {
    // Only add if user is an admin and we're in Bricks Builder
    if ( ! current_user_can('manage_options') || ! isset($_GET['bricks']) || $_GET['bricks'] !== 'run' ) {
        return;
    }

    // Get settings
    $ai_enabled     = get_option('snn_ai_enabled', 'no');
    $openai_api_key = get_option('snn_openai_api_key', '');
    $openai_model   = get_option('snn_openai_model', 'gpt-4o-mini');
    $system_prompt  = get_option('snn_system_prompt', 'You are a helpful assistant that helps with content creation or manipulation. Never use markdown.');

    // If AI isn't enabled, don't load the script
    if ($ai_enabled !== 'yes') {
        return;
    }

    // Retrieve action presets
    $default_presets = [
        ['name' => 'Title', 'prompt' => 'Generate a catchy title.'],
        ['name' => 'Content', 'prompt' => 'Generate engaging content.'],
        ['name' => 'Button', 'prompt' => 'Suggest a call-to-action button text.'],
        ['name' => 'Funny', 'prompt' => 'Make it funny.'],
        ['name' => 'Sad', 'prompt' => 'Make it sad.'],
        ['name' => 'Business', 'prompt' => 'Make it professional and business-like.'],
    ];

    $stored_action_presets = get_option('snn_ai_action_presets', false);
    if ($stored_action_presets === false) {
        // Not saved yet, use defaults
        $action_presets = $default_presets;
    } elseif (!is_array($stored_action_presets)) {
        // Corrupted or invalid, make empty
        $action_presets = [];
    } else {
        // Re-index so JSON becomes a true array instead of an object
        $action_presets = array_values($stored_action_presets);
    }
    ?>
    <style>
        .snn-ai-button {
            background-color: #454f59;
            color: #bebebe;
            padding: 2px 4px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin-left: 10px;
            display: inline-flex;
            align-items: center;
            transition: all 0.2s ease;
            position: absolute;
            right: 4px;
            top: 26px;
            z-index: 99
        }
        [data-control="editor"] .snn-ai-button{
            top: auto;
            bottom: 20px;
        }
        [data-control="text"] .snn-ai-button{
            top: auto;
            bottom: 6px;
            right:30px;
        }
        [data-control="code"] .snn-ai-button{
            top: auto;
            bottom: 32px;
            padding: 3px 5px;
            font-size:16px;
        }
        [data-control="query"] .snn-ai-button{
            display:none
        }
        .snn-ai-button:hover {
            background-color: var(--builder-bg-accent);
            color: var(--builder-color-accent);
        }
        .snn-ai-overlay {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            z-index: 9999;
            justify-content: center;
            font-size: 14px;
            line-height: 1.2
        }
        .snn-ai-modal {
            background-color: var(--builder-bg);
            color: var(--builder-color);
            border-radius: 4px 4px 0 0;
            width: 600px;
            max-width: 90%;
            max-height: 80vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
        }
        .snn-ai-modal-header {
            padding: 0px;
            background-color: var(--builder-bg-shade);
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }
        .snn-ai-modal-header h3 {
            margin: 0;
            font-size: 18px;
            color: var(--builder-color);
        }
        .snn-ai-close {
            cursor: pointer;
            font-size: 20px;
            color: var(--builder-color-light);
            line-height: 1;
            margin-right: 10px;
            top: 5px;
            position: relative;
            transform: scaleX(1.2);
        }
        .snn-ai-modal-body {
            padding: 10px;
            overflow-y: auto;
            flex: 1;
        }
        .snn-ai-prompt {
            width: 100%;
            min-height: 140px;
            padding: 5px;
            border-radius: 4px;
            margin-bottom: 10px;
            font-family: inherit;
            resize: vertical;
            background-color: var(--builder-bg-light);
            color: var(--builder-color);
            border: solid 1px #00000055;
        }
        .snn-ai-actions-container {
            margin-bottom: 15px;
        }
        .snn-ai-action-button {
            display: inline-block;
            padding: 5px;
            margin: 3px;
            background-color: var(--builder-bg);
            border: 1px solid #00000055;
            border-radius: 4px;
            cursor: pointer;
            color: var(--builder-color);
        }
        .snn-ai-action-button.selected {
            background-color: var(--builder-bg-accent);
            color: var(--builder-color-accent);
            border-color: var(--builder-color-accent);
        }
        .snn-ai-submit, .snn-ai-copy, .snn-ai-apply {
            background-color: var(--builder-color-accent);
            color: var(--builder-bg);
            border: none;
            border-radius: 4px;
            padding: 10px 20px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s ease;
            border: solid 1px #00000000;
        }
        .snn-ai-submit:hover, .snn-ai-copy:hover, .snn-ai-apply:hover {
            color: var(--builder-color-accent);
            background: var(--builder-bg);
            border: solid 1px #00000055;
        }
        .snn-ai-submit:disabled, .snn-ai-copy:disabled, .snn-ai-apply:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        .snn-ai-response {
            padding: 15px;
            background-color: var(--builder-bg-light);
            border-radius: 4px;
            margin-top: 15px;
            display: none;
            overflow: auto;
            max-height: 100px;
        }
        .snn-ai-response-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 15px;
        }
        .snn-ai-spinner {
            display: none;
            margin: 20px auto;
            border: 3px solid var(--builder-border-color);
            border-top: 3px solid #10a37f;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: snn-ai-spin 1s linear infinite;
        }
        @keyframes snn-ai-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>

    <div class="snn-ai-overlay">
        <div class="snn-ai-modal">
            <div class="snn-ai-modal-header">
                <span class="snn-ai-close">X</span>
            </div>
            <div class="snn-ai-modal-body">
                <div>
                    <div id="snn-ai-actions-container" class="snn-ai-actions-container"></div>
                    <textarea id="snn-ai-prompt-textarea" class="snn-ai-prompt" placeholder="Enter your instructions..."></textarea>
                </div>
                <button id="snn-ai-submit" class="snn-ai-submit">Generate</button>
                <div id="snn-ai-spinner" class="snn-ai-spinner"></div>
                <div id="snn-ai-response" class="snn-ai-response"></div>
                <div class="snn-ai-response-actions">
                    <button id="snn-ai-copy" class="snn-ai-copy" style="display: none;">Copy Text</button>
                    <button id="snn-ai-apply" class="snn-ai-apply" style="display: none;">Apply to Editor</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Convert the PHP-encoded array to a JS variable (ensure it's an array)
        let actionPresets = <?php echo json_encode($action_presets); ?>;
        if (!Array.isArray(actionPresets)) {
            actionPresets = [];
        }

        const config = {
            apiKey: '<?php echo esc_js($openai_api_key); ?>',
            model: '<?php echo esc_js($openai_model); ?>',
            systemPrompt: '<?php echo esc_js($system_prompt); ?>'
        };

        // Use an array to allow multiple selected presets
        let selectedPresets = [];

        // Populate preset buttons
        const actionsContainer = document.getElementById('snn-ai-actions-container');
        actionPresets.forEach(preset => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'snn-ai-action-button';
            btn.textContent = preset.name;
            btn.dataset.prompt = preset.prompt;
            btn.addEventListener('click', function() {
                // Toggle selection: if already selected, remove; otherwise add it.
                if (btn.classList.contains('selected')) {
                    btn.classList.remove('selected');
                    selectedPresets = selectedPresets.filter(p => p.name !== preset.name);
                } else {
                    btn.classList.add('selected');
                    selectedPresets.push({name: preset.name, prompt: preset.prompt});
                }
            });
            actionsContainer.appendChild(btn);
        });

        // Store the target element and its type
        let targetElement = null;
        let targetType = null;
        let aiResponse = null;

        // Define supported editor types and their handlers
        const editorTypes = {
            'textarea': {
                selector: '[data-control="textarea"]',
                getContent: function(element) {
                    const textarea = element.querySelector('textarea');
                    return textarea ? textarea.value.trim() : '';
                },
                setContent: function(element, content) {
                    const textarea = element.querySelector('textarea');
                    if (textarea) {
                        textarea.value = content;
                        const event = new Event('input', { bubbles: true });
                        textarea.dispatchEvent(event);
                        textarea.style.transition = 'background-color 0.5s';
                        textarea.style.backgroundColor = '#00000055';
                        setTimeout(() => {
                            textarea.style.backgroundColor = '';
                        }, 1500);
                    }
                }
            },
            'richtext': {
                selector: '[data-control="editor"]',
                getContent: function(element) {
                    const iframe = element.querySelector('iframe');
                    if (iframe && iframe.contentDocument) {
                        const tinymce = iframe.contentDocument.getElementById('tinymce');
                        return tinymce ? tinymce.innerHTML : '';
                    }
                    return '';
                },
                setContent: function(element, content) {
                    const iframe = element.querySelector('iframe');
                    if (iframe && iframe.contentDocument) {
                        const tinymce = iframe.contentDocument.getElementById('tinymce');
                        if (tinymce) {
                            // Set the new content
                            tinymce.innerHTML = content;
                            // Dispatch input event to update editor
                            const event = new Event('input', { bubbles: true });
                            tinymce.dispatchEvent(event);

                            // Focus and move caret to the end of the content
                            tinymce.focus();
                            var range = tinymce.ownerDocument.createRange();
                            range.selectNodeContents(tinymce);
                            range.collapse(false);
                            var sel = tinymce.ownerDocument.getSelection();
                            sel.removeAllRanges();
                            sel.addRange(range);

                            // Simulate pressing Enter to force tinymce to re-render the content
                            var enterKeyEventDown = new KeyboardEvent('keydown', {
                                key: 'Enter',
                                code: 'Enter',
                                keyCode: 13,
                                which: 13,
                                bubbles: true,
                                cancelable: true
                            });
                            tinymce.dispatchEvent(enterKeyEventDown);
                            var enterKeyEventUp = new KeyboardEvent('keyup', {
                                key: 'Enter',
                                code: 'Enter',
                                keyCode: 13,
                                which: 13,
                                bubbles: true,
                                cancelable: true
                            });
                            tinymce.dispatchEvent(enterKeyEventUp);

                            tinymce.style.transition = 'background-color 0.5s';
                            tinymce.style.backgroundColor = '#00000055';
                            setTimeout(() => {
                                tinymce.style.backgroundColor = '';
                            }, 1500);
                        }
                    }
                }
            },
            'text': {
                selector: '[data-control="text"]',
                getContent: function(element) {
                    const input = element.querySelector('input');
                    return input ? input.value.trim() : '';
                },
                setContent: function(element, content) {
                    const input = element.querySelector('input');
                    if (input) {
                        input.value = content;
                        const event = new Event('input', { bubbles: true });
                        input.dispatchEvent(event);
                        input.style.transition = 'background-color 0.5s';
                        input.style.backgroundColor = '#00000055';
                        setTimeout(() => {
                            input.style.backgroundColor = '';
                        }, 1500);
                    }
                }
            },
            // "code" control now checks for a CodeMirror instance
            'code': {
                selector: '[data-control="code"]',
                getContent: function(element) {
                    // Check if a CodeMirror instance exists within the element
                    const cmElement = element.querySelector('.CodeMirror');
                    if (cmElement && cmElement.CodeMirror) {
                        return cmElement.CodeMirror.getValue();
                    } else {
                        // Fallback to the textarea method if CodeMirror is not present
                        const textarea = element.querySelector('textarea');
                        if (textarea) {
                            let codeVal = textarea.value.trim();
                            if (!codeVal) {
                                codeVal = textarea.placeholder || '';
                            }
                            return codeVal;
                        }
                        return '';
                    }
                },
                setContent: function(element, content) {
                    // Check if a CodeMirror instance exists within the element
                    const cmElement = element.querySelector('.CodeMirror');
                    if (cmElement && cmElement.CodeMirror) {
                        cmElement.CodeMirror.setValue(content);
                    } else {
                        // Fallback to the textarea method if CodeMirror is not present
                        const textarea = element.querySelector('textarea');
                        if (textarea) {
                            textarea.value = content;
                            const event = new Event('input', { bubbles: true });
                            textarea.dispatchEvent(event);
                            textarea.style.transition = 'background-color 0.5s';
                            textarea.style.backgroundColor = '#00000055';
                            setTimeout(() => {
                                textarea.style.backgroundColor = '';
                            }, 1500);
                        }
                    }
                }
            }
        };

        // Observer to detect new elements being added to the DOM
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes && mutation.addedNodes.length > 0) {
                    for (let i = 0; i < mutation.addedNodes.length; i++) {
                        const node = mutation.addedNodes[i];
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            Object.keys(editorTypes).forEach(type => {
                                const selector = editorTypes[type].selector;
                                if (node.matches && node.matches(selector)) {
                                    addAiButtonTo(node, type);
                                } else {
                                    // Check any children
                                    const elements = node.querySelectorAll ? node.querySelectorAll(selector) : [];
                                    elements.forEach(el => addAiButtonTo(el, type));
                                }
                            });
                        }
                    }
                }
            });
        });
        observer.observe(document.body, { childList: true, subtree: true });

        // Initially attach AI button to all matching elements
        Object.keys(editorTypes).forEach(type => {
            const elements = document.querySelectorAll(editorTypes[type].selector);
            elements.forEach(el => addAiButtonTo(el, type));
        });

        function addAiButtonTo(element, type) {
            // Avoid duplication if the button was already added
            if (element.querySelector('.snn-ai-button')) {
                return; 
            }
            const aiButton = document.createElement('span');
            aiButton.className = 'snn-ai-button';
            aiButton.textContent = 'AI';
            aiButton.setAttribute('data-balloon', 'Generate with AI');
            aiButton.setAttribute('data-balloon-pos', 'left');
            aiButton.setAttribute('data-editor-type', type);
            const controlLabel = element.querySelector('.control-label');
            if (controlLabel) {
                controlLabel.appendChild(aiButton);
            } else {
                element.insertBefore(aiButton, element.firstChild);
            }
            aiButton.addEventListener('click', function() {
                openAiModal(element, type);
            });
        }

        function openAiModal(element, type) {
            targetElement = element;
            targetType = type;
            // Clear the textarea and any previous response
            document.getElementById('snn-ai-prompt-textarea').value = '';
            document.getElementById('snn-ai-response').style.display = 'none';
            document.getElementById('snn-ai-apply').style.display = 'none';
            document.getElementById('snn-ai-copy').style.display = 'none';
            // Clear any previously selected presets
            document.querySelectorAll('.snn-ai-action-button').forEach(b => b.classList.remove('selected'));
            selectedPresets = [];

            // Optionally, you may prefill with existing content from the editor
            const textarea = document.getElementById('snn-ai-prompt-textarea');
            const existingContent = editorTypes[type].getContent(element);
            if (existingContent) {
                textarea.value = existingContent + "\n\n";
            }
            document.querySelector('.snn-ai-overlay').style.display = 'flex';
        }

        document.querySelector('.snn-ai-close').addEventListener('click', function() {
            document.querySelector('.snn-ai-overlay').style.display = 'none';
        });

        document.getElementById('snn-ai-submit').addEventListener('click', function() {
            const promptText = document.getElementById('snn-ai-prompt-textarea').value.trim();
            if (!promptText && selectedPresets.length === 0) {
                alert('Please enter a prompt or select at least one action preset');
                return;
            }
            this.disabled = true;
            document.getElementById('snn-ai-spinner').style.display = 'block';
            document.getElementById('snn-ai-response').style.display = 'none';
            document.getElementById('snn-ai-apply').style.display = 'none';
            document.getElementById('snn-ai-copy').style.display = 'none';

            // Build final prompt by concatenating selected preset prompts and the manual prompt text
            let finalPrompt = '';
            if (selectedPresets.length > 0) {
                let combinedPresetPrompts = selectedPresets.map(p => p.prompt).join("\n");
                finalPrompt = combinedPresetPrompts + (promptText ? "\n" + promptText : "");
            } else {
                finalPrompt = promptText;
            }

            callOpenAI(finalPrompt)
                .then(response => {
                    document.getElementById('snn-ai-submit').disabled = false;
                    document.getElementById('snn-ai-spinner').style.display = 'none';
                    aiResponse = response;
                    const responseElement = document.getElementById('snn-ai-response');
                    responseElement.textContent = response;
                    responseElement.style.display = 'block';
                    document.getElementById('snn-ai-apply').style.display = 'inline-block';
                    document.getElementById('snn-ai-copy').style.display = 'inline-block';
                })
                .catch(error => {
                    document.getElementById('snn-ai-submit').disabled = false;
                    document.getElementById('snn-ai-spinner').style.display = 'none';
                    const responseElement = document.getElementById('snn-ai-response');
                    responseElement.textContent = 'Error: ' + error.message;
                    responseElement.style.display = 'block';
                });
        });

        document.getElementById('snn-ai-copy').addEventListener('click', function() {
            if (aiResponse) {
                navigator.clipboard.writeText(aiResponse).then(() => {
                    const originalText = this.textContent;
                    this.textContent = 'Copied!';
                    setTimeout(() => {
                        this.textContent = originalText;
                    }, 1500);
                }).catch(err => {
                    console.error('Failed to copy text: ', err);
                });
            }
        });

        document.getElementById('snn-ai-apply').addEventListener('click', function() {
            if (targetElement && targetType && aiResponse) {
                editorTypes[targetType].setContent(targetElement, aiResponse);
                document.querySelector('.snn-ai-overlay').style.display = 'none';
            }
        });

        async function callOpenAI(prompt) {
            try {
                const response = await fetch('https://api.openai.com/v1/chat/completions', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${config.apiKey}`
                    },
                    body: JSON.stringify({
                        model: config.model,
                        messages: [
                            {
                                role: 'system',
                                content: config.systemPrompt
                            },
                            {
                                role: 'user',
                                content: prompt
                            }
                        ],
                        temperature: 0.7
                    })
                });
                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.error?.message || 'API request failed');
                }
                const data = await response.json();
                return data.choices[0].message.content;
            } catch (error) {
                console.error('OpenAI API error:', error);
                throw error;
            }
        }
    });
    </script>
    <?php
}
add_action('wp_footer', 'snn_add_ai_script_to_footer');
add_action('admin_footer', 'snn_add_ai_script_to_footer');
?>
