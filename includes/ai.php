<?php

function snn_add_ai_settings_submenu() {
    add_submenu_page(
        'snn-settings',                  
        'AI Settings',                    
        'AI',                            
        'manage_options',                 
        'snn-ai-settings',                
        'snn_render_ai_settings'          
    );
}
add_action('admin_menu', 'snn_add_ai_settings_submenu');

function snn_register_ai_settings() {
    register_setting('snn_ai_settings_group', 'snn_ai_enabled');
    register_setting('snn_ai_settings_group', 'snn_openai_api_key');
    register_setting('snn_ai_settings_group', 'snn_openai_model');
}
add_action('admin_init', 'snn_register_ai_settings');

function snn_render_ai_settings() {
    $ai_enabled     = get_option('snn_ai_enabled', 'no');
    $openai_api_key = get_option('snn_openai_api_key', '');
    $openai_model   = get_option('snn_openai_model', 'gpt-4o-mini');
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

            <div id="openai-settings" style="<?php echo ($ai_enabled === 'yes') ? '' : 'display:none;'; ?>">
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
                                <a href="https://openai.com/api/pricing/" target="_blank" >OpenAI API Model Prices</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <?php submit_button(); ?>
        </form>
        <script type="text/javascript">
            document.getElementById('snn_ai_enabled').addEventListener('change', function() {
                var openaiSettings = document.getElementById('openai-settings');
                openaiSettings.style.display = this.checked ? 'block' : 'none';
            });
        </script>
    </div>
    <?php
}

// Add script to footer when in admin and Bricks Builder is active
function snn_add_ai_script_to_footer() {
    // Only add if user is an admin and we're in Bricks Builder
    if (!current_user_can('manage_options') || !isset($_GET['bricks']) || $_GET['bricks'] !== 'run') {
        return;
    }

    // Get settings
    $ai_enabled = get_option('snn_ai_enabled', 'no');
    $openai_api_key = get_option('snn_openai_api_key', '');
    $openai_model = get_option('snn_openai_model', 'gpt-4o-mini');

    // Only proceed if AI is enabled and API key is present
    if ($ai_enabled !== 'yes' || empty($openai_api_key)) {
        return;
    }
    
    // Add inline styles
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
            z-index:99
        }

        [data-control="editor"] .snn-ai-button{
            top: auto;
            bottom:20px;
        }

        [data-control="text"] .snn-ai-button{
            top: auto;
            bottom: 6px;
            right:30px;
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
            font-size:14px;
            line-height:1
        }
        
        .snn-ai-modal {
            background-color: var(--builder-bg);
            color: var(--builder-color);
            border-radius: 8px 8px 0 0;
            width: 600px;
            max-width: 90%;
            max-height: 80vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
        }
        
        .snn-ai-modal-header {
            padding: 10px;
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
            float: right;
            line-height: 1;
        }
        
        .snn-ai-modal-body {
            padding: 10px;
            overflow-y: auto;
            flex: 1;
        }
        
        .snn-ai-prompt {
            width: 100%;
            min-height: 100px;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-family: inherit;
            resize: vertical;
            background-color: var(--builder-bg-light);
            color: var(--builder-color);
            border:solid 1px #00000055;
        }
        
        .snn-ai-options {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .snn-ai-action-type {
            flex: 1;
        }
        
        .snn-ai-action-type select {
            background-color: var(--builder-bg-light);
            color: var(--builder-color);
            border-radius: 4px;
            padding: 5px;
            border:solid 1px #00000055;
        }
        .snn-ai-action-type option {
            color: black;
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
            border:solid 1px #00000000;
        }
        
        .snn-ai-submit:hover, .snn-ai-copy:hover, .snn-ai-apply:hover {
            color:var(--builder-color-accent);
            background:var(--builder-bg);
            border:solid 1px #00000055;
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
                    <label for="snn-ai-prompt-textarea">What would you like the AI to do?</label>
                    <textarea id="snn-ai-prompt-textarea" class="snn-ai-prompt" placeholder="e.g., 'Write a paragraph about sustainability', 'Fix grammar errors', 'Summarize this text'"></textarea>
                </div>
                <div class="snn-ai-options">
                    <div class="snn-ai-action-type">
                        <label for="snn-ai-action">Action:</label>
                        <select id="snn-ai-action">
                            <option value="generate">Generate new content</option>
                            <option value="improve">Improve existing content</option>
                            <option value="translate">Translate content</option>
                            <option value="summarize">Summarize content</option>
                        </select>
                    </div>
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
        const config = {
            apiKey: '<?php echo esc_js($openai_api_key); ?>',
            model: '<?php echo esc_js($openai_model); ?>'
        };
        
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
                        // Trigger input event to make sure Bricks registers the change
                        const event = new Event('input', { bubbles: true });
                        textarea.dispatchEvent(event);
                        
                        // Visual feedback
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
                            tinymce.innerHTML = content;
                            // Create and dispatch input event
                            const event = new Event('input', { bubbles: true });
                            tinymce.dispatchEvent(event);
                            
                            // Visual feedback
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
                        // Trigger input event to make sure Bricks registers the change
                        const event = new Event('input', { bubbles: true });
                        input.dispatchEvent(event);
                        
                        // Visual feedback
                        input.style.transition = 'background-color 0.5s';
                        input.style.backgroundColor = '#00000055';
                        setTimeout(() => {
                            input.style.backgroundColor = '';
                        }, 1500);
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
                            // Process each editor type
                            Object.keys(editorTypes).forEach(type => {
                                const selector = editorTypes[type].selector;
                                
                                // Check if the added node itself matches or contains the selector
                                if (node.matches && node.matches(selector)) {
                                    addAiButtonTo(node, type);
                                } else {
                                    const elements = node.querySelectorAll(selector);
                                    elements.forEach(el => addAiButtonTo(el, type));
                                }
                            });
                        }
                    }
                }
            });
        });
        
        // Start observing the document with the configured parameters
        observer.observe(document.body, { childList: true, subtree: true });
        
        // Initial scan for existing elements
        Object.keys(editorTypes).forEach(type => {
            const elements = document.querySelectorAll(editorTypes[type].selector);
            elements.forEach(el => addAiButtonTo(el, type));
        });
        
        // Function to add the AI button to an element
        function addAiButtonTo(element, type) {
            // Check if button already exists
            if (element.querySelector('.snn-ai-button')) {
                return;
            }
            
            const aiButton = document.createElement('span');
            aiButton.className = 'snn-ai-button';
            aiButton.textContent = 'AI';
            aiButton.setAttribute('data-balloon', 'Generate with AI');
            aiButton.setAttribute('data-balloon-pos', 'left');
            aiButton.setAttribute('data-editor-type', type);
            
            // Find the best place to insert the button
            const controlLabel = element.querySelector('.control-label');
            if (controlLabel) {
                controlLabel.appendChild(aiButton);
            } else {
                element.insertBefore(aiButton, element.firstChild);
            }
            
            // Add click event
            aiButton.addEventListener('click', function() {
                openAiModal(element, type);
            });
        }
        
        // Function to open the AI modal
        function openAiModal(element, type) {
            targetElement = element;
            targetType = type;
            
            // Reset the form
            document.getElementById('snn-ai-prompt-textarea').value = '';
            document.getElementById('snn-ai-response').style.display = 'none';
            document.getElementById('snn-ai-apply').style.display = 'none';
            document.getElementById('snn-ai-copy').style.display = 'none';
            
            // Check if we should pre-fill the existing content
            const action = document.getElementById('snn-ai-action').value;
            if (action !== 'generate') {
                const existingContent = editorTypes[type].getContent(element);
                if (existingContent) {
                    document.getElementById('snn-ai-prompt-textarea').value = 
                        'Content to work with:\n\n' + existingContent;
                }
            }
            
            // Show the modal
            document.querySelector('.snn-ai-overlay').style.display = 'flex';
        }
        
        // Close button event
        document.querySelector('.snn-ai-close').addEventListener('click', function() {
            document.querySelector('.snn-ai-overlay').style.display = 'none';
        });
        
        // Action type change event
        document.getElementById('snn-ai-action').addEventListener('change', function() {
            const action = this.value;
            const promptTextarea = document.getElementById('snn-ai-prompt-textarea');
            
            // If not generating new content and we have target content, pre-fill
            if (action !== 'generate' && targetElement && targetType) {
                const existingContent = editorTypes[targetType].getContent(targetElement);
                if (existingContent) {
                    promptTextarea.value = 'Content to work with:\n\n' + existingContent;
                } else {
                    promptTextarea.value = '';
                }
            } else {
                promptTextarea.value = '';
            }
            
            // Update placeholder based on action
            switch(action) {
                case 'improve':
                    promptTextarea.placeholder = 'How would you like to improve the content? e.g., "Make it more formal", "Fix grammar"';
                    break;
                case 'translate':
                    promptTextarea.placeholder = 'Which language would you like to translate to? e.g., "Translate to Spanish"';
                    break;
                case 'summarize':
                    promptTextarea.placeholder = 'How would you like it summarized? e.g., "Summarize in 3 bullets", "Make it concise"';
                    break;
                default:
                    promptTextarea.placeholder = 'e.g., "Write a paragraph about sustainability", "Create a product description"';
            }
        });
        
        // Submit button event
        document.getElementById('snn-ai-submit').addEventListener('click', function() {
            const promptText = document.getElementById('snn-ai-prompt-textarea').value.trim();
            const action = document.getElementById('snn-ai-action').value;
            
            if (!promptText) {
                alert('Please enter a prompt');
                return;
            }
            
            // Show spinner, disable button
            this.disabled = true;
            document.getElementById('snn-ai-spinner').style.display = 'block';
            document.getElementById('snn-ai-response').style.display = 'none';
            document.getElementById('snn-ai-apply').style.display = 'none';
            document.getElementById('snn-ai-copy').style.display = 'none';
            
            // Prepare the prompt based on the action type
            let fullPrompt = '';
            const existingContent = targetElement && targetType ? editorTypes[targetType].getContent(targetElement) : '';
            
            switch(action) {
                case 'improve':
                    fullPrompt = `Improve the following content: ${promptText}\n\nYour improved version:`;
                    break;
                case 'translate':
                    fullPrompt = `${promptText}\n\n${existingContent}\n\nTranslated content:`;
                    break;
                case 'summarize':
                    fullPrompt = `${promptText}\n\n${existingContent}\n\nSummary:`;
                    break;
                default:
                    fullPrompt = promptText;
            }
            
            // Make API request to OpenAI
            callOpenAI(fullPrompt)
                .then(response => {
                    // Hide spinner, enable button
                    document.getElementById('snn-ai-submit').disabled = false;
                    document.getElementById('snn-ai-spinner').style.display = 'none';
                    
                    // Display and store response
                    aiResponse = response;
                    const responseElement = document.getElementById('snn-ai-response');
                    responseElement.textContent = response;
                    responseElement.style.display = 'block';
                    
                    // Show apply and copy buttons
                    document.getElementById('snn-ai-apply').style.display = 'inline-block';
                    document.getElementById('snn-ai-copy').style.display = 'inline-block';
                })
                .catch(error => {
                    // Handle error
                    document.getElementById('snn-ai-submit').disabled = false;
                    document.getElementById('snn-ai-spinner').style.display = 'none';
                    
                    const responseElement = document.getElementById('snn-ai-response');
                    responseElement.textContent = 'Error: ' + error.message;
                    responseElement.style.display = 'block';
                });
        });
        
        // Copy button event
        document.getElementById('snn-ai-copy').addEventListener('click', function() {
            if (aiResponse) {
                navigator.clipboard.writeText(aiResponse).then(() => {
                    // Visual feedback for copy
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
        
        // Apply button event
        document.getElementById('snn-ai-apply').addEventListener('click', function() {
            if (targetElement && targetType && aiResponse) {
                // Apply content using the appropriate handler
                editorTypes[targetType].setContent(targetElement, aiResponse);
                
                // Close the modal
                document.querySelector('.snn-ai-overlay').style.display = 'none';
            }
        });
        
        // Function to call OpenAI API
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
                                content: 'You are a helpful assistant that helps with content creation or manipulation. You work inside a wordpress visual builder. User usually changes a website content. Keep the content length as similar the existing content when you are editing or follow the users instructions accordingly. Generate with as same language as the content unless told otherwise. Only respond with the needed content and nothing else always!'
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