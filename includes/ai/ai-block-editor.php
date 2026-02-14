<?php
/**
 * SNN AI Block Editor Integration 
 *
 * File: ai-block-editor.php
 *
 * Purpose: This file adds AI assistant functionality to the WordPress Block Editor.
 * It allows users to generate or regenerate the complete post content using AI,
 * with access to the same action presets configured in the AI settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Enqueue block editor assets
 */
function snn_enqueue_block_editor_ai_assets() {
    $screen = get_current_screen();
    if ( ! $screen || ! $screen->is_block_editor() ) {
        return;
    }

    // Check if AI is enabled
    $ai_enabled = get_option('snn_ai_enabled', 'no');
    if ($ai_enabled !== 'yes') {
        return;
    }

    // Get AI configuration
    if ( ! function_exists( 'snn_get_ai_api_config' ) ) {
        error_log('SNN AI Block Editor Error: snn_get_ai_api_config() function not found.');
        return;
    }

    $config = snn_get_ai_api_config();

    // Only proceed if we have valid configuration
    if ( empty( $config['apiKey'] ) || empty( $config['apiEndpoint'] ) ) {
        return;
    }

    $post_id = get_the_ID();
    if ( ! $post_id ) {
        global $post;
        $post_id = isset($post->ID) ? $post->ID : 0;
    }

    // Pass config to JavaScript
    wp_localize_script('wp-plugins', 'snnAiConfig', array(
        'apiKey' => $config['apiKey'],
        'model' => $config['model'],
        'systemPrompt' => $config['systemPrompt'],
        'apiEndpoint' => $config['apiEndpoint'],
        'actionPresets' => $config['actionPresets'],
        'postId' => $post_id
    ));
}
add_action('enqueue_block_editor_assets', 'snn_enqueue_block_editor_ai_assets');

/**
 * Add AI panel and modal to block editor
 */
function snn_add_block_editor_ai_panel() {
    $screen = get_current_screen();
    if ( ! $screen || ! $screen->is_block_editor() ) {
        return;
    }

    // Check if AI is enabled
    $ai_enabled = get_option('snn_ai_enabled', 'no');
    if ($ai_enabled !== 'yes') {
        return;
    }

    // Get AI configuration
    if ( ! function_exists( 'snn_get_ai_api_config' ) ) {
        return;
    }

    $config = snn_get_ai_api_config();

    // Only proceed if we have valid configuration
    if ( empty( $config['apiKey'] ) || empty( $config['apiEndpoint'] ) ) {
        return;
    }

    $post_id = get_the_ID();
    if ( ! $post_id ) {
        global $post;
        $post_id = isset($post->ID) ? $post->ID : 0;
    }

    ?>
    <style>
        .snn-ai-panel-container {
            padding: 16px;
        }

        .snn-block-ai-panel-button {
            width: 100%;
            padding: 10px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: background-color 0.2s ease;
            border: none;
        }
        .snn-block-ai-panel-button:hover {
            background-color: #135e96;
        }

        .snn-block-ai-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 999999;
            justify-content: center;
            align-items: center;
        }

        .snn-block-ai-modal {
            background-color: white;
            border-radius: 8px;
            width: 800px;
            max-width: 90%;
            max-height: 80vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        .snn-block-ai-modal-header {
            padding: 16px 20px;
            background-color: #f0f0f1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #dcdcde;
        }

        .snn-block-ai-modal-header h3 {
            margin: 0;
            font-size: 18px;
            color: #1d2327;
            font-weight: 600;
        }

        .snn-block-ai-close {
            cursor: pointer;
            font-size: 24px;
            color: #646970;
            line-height: 1;
            background: none;
            border: none;
            padding: 0;
            width: 24px;
            height: 24px;
        }

        .snn-block-ai-close:hover {
            color: #1d2327;
        }

        .snn-block-ai-modal-body {
            padding: 20px;
            overflow-y: auto;
            flex: 1;
        }

        .snn-block-ai-actions-container {
            margin-bottom: 6px;
        }

        .snn-block-ai-action-button {
            display: inline-block;
            padding: 2px 6px;
            margin: 3px;
            background-color: white;
            border: 1px solid #8c8f94;
            border-radius: 4px;
            cursor: pointer;
            color: #1d2327;
            font-size: 13px;
            transition: all 0.2s ease;
        }

        .snn-block-ai-action-button.selected {
            background-color: #2271b1;
            color: white;
            border-color: #2271b1;
        }

        .snn-block-ai-action-button:hover {
            border-color: #2271b1;
        }

        .snn-block-ai-prompt {
            width: 100%;
            min-height: 140px;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 12px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            resize: vertical;
            background-color: white;
            color: #1d2327;
            border: 1px solid #8c8f94;
            box-sizing: border-box;
            font-size: 13px;
        }

        .snn-block-ai-prompt:focus {
            border-color: #2271b1;
            outline: none;
            box-shadow: 0 0 0 1px #2271b1;
        }

        .snn-block-ai-submit,
        .snn-block-ai-copy,
        .snn-block-ai-apply {
            background-color: #2271b1;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 10px 20px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s ease;
            margin-right: 8px;
        }

        .snn-block-ai-submit:hover,
        .snn-block-ai-copy:hover,
        .snn-block-ai-apply:hover {
            background-color: #135e96;
        }

        .snn-block-ai-submit:disabled,
        .snn-block-ai-copy:disabled,
        .snn-block-ai-apply:disabled {
            background-color: #dcdcde;
            cursor: not-allowed;
            color: #a7aaad;
        }

        .snn-block-ai-response {
            padding: 16px;
            background-color: #f6f7f7;
            border-radius: 4px;
            margin-top: 16px;
            display: none;
            overflow: auto;
            max-height: 200px;
            white-space: pre-wrap;
            border: 1px solid #dcdcde;
            font-size: 13px;
            color: #1d2327;
        }

        .snn-block-ai-response-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 16px;
        }

        .snn-block-ai-spinner {
            display: none;
            margin: 20px auto;
            border: 3px solid #f0f0f1;
            border-top: 3px solid #2271b1;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: snn-block-ai-spin 1s linear infinite;
        }

        @keyframes snn-block-ai-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>

    <!-- Modal overlay (hidden by default) -->
    <div class="snn-block-ai-overlay" id="snn-block-ai-overlay">
        <div class="snn-block-ai-modal">
            <div class="snn-block-ai-modal-header">
                <h3><?php esc_html_e('AI Content Generation', 'snn'); ?></h3>
                <button class="snn-block-ai-close" id="snn-block-ai-close-button">×</button>
            </div>
            <div class="snn-block-ai-modal-body">
                <div id="snn-block-ai-actions-container" class="snn-block-ai-actions-container"></div>
                <textarea
                    id="snn-block-ai-prompt-textarea"
                    class="snn-block-ai-prompt"
                    placeholder="<?php esc_attr_e('Existing content will appear here. Add your instructions or select a preset...', 'snn'); ?>"
                ></textarea>
                <button id="snn-block-ai-submit" class="snn-block-ai-submit"><?php esc_html_e('Generate', 'snn'); ?></button>
                <div id="snn-block-ai-spinner" class="snn-block-ai-spinner"></div>
                <div id="snn-block-ai-response" class="snn-block-ai-response"></div>
                <div class="snn-block-ai-response-actions">
                    <button id="snn-block-ai-copy" class="snn-block-ai-copy" style="display: none;"><?php esc_html_e('Copy Text', 'snn'); ?></button>
                    <button id="snn-block-ai-apply" class="snn-block-ai-apply" style="display: none;"><?php esc_html_e('Apply to Editor', 'snn'); ?></button>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function() {
        'use strict';

        // Wait for WordPress editor to be ready
        function initAIPanel() {
            if (!window.wp || !window.wp.data || !window.wp.plugins || !window.wp.element || !window.wp.editPost) {
                //console.log('SNN AI: Waiting for WordPress editor...');
                setTimeout(initAIPanel, 500);
                return;
            }

            // WordPress APIs are available (we don't need to extract them since we're using DOM injection)

            // Get config from localized script
            const config = window.snnAiConfig || {
                apiKey: <?php echo json_encode($config['apiKey']); ?>,
                model: <?php echo json_encode($config['model']); ?>,
                systemPrompt: <?php echo json_encode($config['systemPrompt']); ?>,
                apiEndpoint: <?php echo json_encode($config['apiEndpoint']); ?>,
                actionPresets: <?php echo json_encode($config['actionPresets']); ?>,
                postId: <?php echo json_encode($post_id); ?>
            };

            let actionPresets = config.actionPresets || [];
            if (!Array.isArray(actionPresets)) {
                actionPresets = [];
            }

            let selectedPresets = [];
            let aiResponse = null;
            let isRequestPending = false;
            let currentContent = '';

            // Inject AI button into the Summary panel
            function injectAIButtonIntoSummaryPanel() {
                // Find the summary panel section
                const summaryPanel = document.querySelector('.editor-post-panel__section.editor-post-summary');

                if (!summaryPanel) {
                    //console.log('SNN AI: Summary panel not found yet');
                    return false;
                }

                // Check if button already exists
                if (document.getElementById('snn-ai-summary-button')) {
                    //console.log('SNN AI: Button already exists');
                    return true;
                }

                // Create button container matching WordPress styles
                const buttonContainer = document.createElement('div');
                buttonContainer.className = 'components-flex components-h-stack components-v-stack css-1i2unhf e19lxcc00';
                buttonContainer.setAttribute('data-wp-c16t', 'true');
                buttonContainer.setAttribute('data-wp-component', 'VStack');

                const button = document.createElement('button');
                button.id = 'snn-ai-summary-button';
                button.type = 'button';
                button.className = 'snn-block-ai-panel-button button';
                button.textContent = 'Generate Content with AI ✨';
                button.onclick = showModal;

                buttonContainer.appendChild(button);

                // Find the featured image section to insert after it
                const featuredImageSection = summaryPanel.querySelector('.editor-post-featured-image');

                if (featuredImageSection) {
                    // Insert after featured image
                    featuredImageSection.parentNode.insertBefore(buttonContainer, featuredImageSection.nextSibling);
                    //console.log('SNN AI: Button injected into Summary panel after featured image');
                } else {
                    // Fallback: insert at the beginning of the summary panel
                    summaryPanel.insertBefore(buttonContainer, summaryPanel.firstChild);
                    //console.log('SNN AI: Button injected into Summary panel at top');
                }

                return true;
            }

            // Setup MutationObserver to watch for DOM changes and re-inject button if needed
            function setupButtonPersistence() {
                let injectionTimer = null;
                let isInjecting = false;

                // Debounced injection attempt
                const attemptInjection = () => {
                    if (isInjecting) return;

                    clearTimeout(injectionTimer);
                    injectionTimer = setTimeout(() => {
                        const summaryPanel = document.querySelector('.editor-post-panel__section.editor-post-summary');
                        const buttonExists = document.getElementById('snn-ai-summary-button');

                        // Only inject if panel exists but button doesn't
                        if (summaryPanel && !buttonExists) {
                            //console.log('SNN AI: Re-injecting button...');
                            isInjecting = true;
                            injectAIButtonIntoSummaryPanel();
                            isInjecting = false;
                        }
                    }, 100);
                };

                // Initial injection
                attemptInjection();

                // Watch for DOM changes to re-inject button if needed
                const observer = new MutationObserver((mutations) => {
                    // Check if button is missing but panel exists
                    const summaryPanel = document.querySelector('.editor-post-panel__section.editor-post-summary');
                    const buttonExists = document.getElementById('snn-ai-summary-button');

                    if (summaryPanel && !buttonExists) {
                        attemptInjection();
                    }
                });

                // Observe the sidebar area for changes
                const observeTarget = document.querySelector('.interface-interface-skeleton__sidebar') ||
                                     document.querySelector('.edit-post-layout') ||
                                     document.body;

                observer.observe(observeTarget, {
                    childList: true,
                    subtree: true
                });

                // console.log('SNN AI: Button persistence observer initialized');

                // Also check periodically as a fallback
                setInterval(() => {
                    const summaryPanel = document.querySelector('.editor-post-panel__section.editor-post-summary');
                    const buttonExists = document.getElementById('snn-ai-summary-button');

                    if (summaryPanel && !buttonExists) {
                        //console.log('SNN AI: Periodic check - button missing, re-injecting...');
                        injectAIButtonIntoSummaryPanel();
                    }
                }, 1000);
            }

            // Start injection with persistence
            setupButtonPersistence();

            // Initialize modal functionality
            const overlay = document.getElementById('snn-block-ai-overlay');
            const closeButton = document.getElementById('snn-block-ai-close-button');
            const actionsContainer = document.getElementById('snn-block-ai-actions-container');
            const promptTextarea = document.getElementById('snn-block-ai-prompt-textarea');
            const submitButton = document.getElementById('snn-block-ai-submit');
            const spinner = document.getElementById('snn-block-ai-spinner');
            const responseDiv = document.getElementById('snn-block-ai-response');
            const copyButton = document.getElementById('snn-block-ai-copy');
            const applyButton = document.getElementById('snn-block-ai-apply');

            // Populate action preset buttons
            actionPresets.forEach(preset => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'snn-block-ai-action-button';
                btn.textContent = preset.name;
                btn.addEventListener('click', () => {
                    const presetData = { name: preset.name, prompt: preset.prompt };
                    if (btn.classList.contains('selected')) {
                        btn.classList.remove('selected');
                        selectedPresets = selectedPresets.filter(p => p.name !== preset.name);
                    } else {
                        btn.classList.add('selected');
                        selectedPresets.push(presetData);
                    }
                    updateSubmitButtonState();
                });
                actionsContainer.appendChild(btn);
            });

            function showModal() {
                overlay.style.display = 'flex';
                promptTextarea.value = '';
                responseDiv.textContent = '';
                responseDiv.style.display = 'none';
                copyButton.style.display = 'none';
                applyButton.style.display = 'none';
                spinner.style.display = 'none';
                submitButton.disabled = false;
                aiResponse = null;
                selectedPresets = [];
                document.querySelectorAll('.snn-block-ai-action-button.selected').forEach(b => b.classList.remove('selected'));

                // Get current post content
                if (wp.data && wp.data.select) {
                    const editor = wp.data.select('core/editor');
                    if (editor) {
                        const blocks = editor.getBlocks();
                        currentContent = blocks.map(block => {
                            if (block.name === 'core/paragraph' || block.name === 'core/heading') {
                                return block.attributes.content || '';
                            }
                            return '';
                        }).filter(text => text).join('\n\n');

                        if (currentContent.trim()) {
                            promptTextarea.value = currentContent + "\n\n---\n";
                            promptTextarea.focus();
                            promptTextarea.scrollTop = 0;
                        } else {
                            promptTextarea.focus();
                        }
                    }
                }
                updateSubmitButtonState();
            }

            function hideModal() {
                overlay.style.display = 'none';
                if (isRequestPending) {
                    isRequestPending = false;
                }
            }

            function updateSubmitButtonState() {
                const hasPrompt = promptTextarea.value.trim().length > 0;
                const hasPresets = selectedPresets.length > 0;
                submitButton.disabled = isRequestPending || !(hasPrompt || hasPresets);
            }

            function extractUserTypedPrompt(fullPrompt, existingContent) {
                if (!existingContent) return fullPrompt;
                const separator = "\n\n---\n";
                const separatorIndex = fullPrompt.indexOf(separator);
                if (fullPrompt.startsWith(existingContent.trim()) && separatorIndex > -1) {
                    return fullPrompt.substring(separatorIndex + separator.length).trim();
                }
                if (fullPrompt.startsWith(existingContent.trim())) {
                    return fullPrompt.substring(existingContent.trim().length).trim();
                }
                return fullPrompt;
            }

            closeButton.addEventListener('click', hideModal);
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    hideModal();
                }
            });

            promptTextarea.addEventListener('input', updateSubmitButtonState);

            submitButton.addEventListener('click', async () => {
                if (isRequestPending) {
                    console.warn("SNN AI: Request already pending.");
                    return;
                }
                if (!config.apiKey) {
                    console.error("SNN AI: API Key missing.");
                    responseDiv.textContent = "Error: API Key missing in settings.";
                    responseDiv.style.display = 'block';
                    return;
                }

                isRequestPending = true;
                submitButton.disabled = true;
                spinner.style.display = 'block';
                responseDiv.style.display = 'none';
                copyButton.style.display = 'none';
                applyButton.style.display = 'none';
                aiResponse = null;

                const messages = [];
                if (config.systemPrompt) {
                    messages.push({ role: 'system', content: config.systemPrompt });
                }

                const fullPromptFromTextarea = promptTextarea.value.trim();
                const userTypedOnlyPrompt = extractUserTypedPrompt(fullPromptFromTextarea, currentContent);

                let instructionForAI = "";

                if (selectedPresets.length > 0) {
                    instructionForAI += "Apply the following actions:\n";
                    selectedPresets.forEach(p => {
                        instructionForAI += `- ${p.prompt}\n`;
                    });
                    instructionForAI += "\n";
                }

                if (userTypedOnlyPrompt) {
                    instructionForAI += `Additional instructions: ${userTypedOnlyPrompt}`;
                }

                if (currentContent.trim()) {
                    messages.push({ role: 'user', content: `The current content is:\n\`\`\`\n${currentContent}\n\`\`\`` });
                    if (instructionForAI.trim() === "") {
                        instructionForAI = "Review the current content and provide an improved version.";
                    }
                    messages.push({ role: 'user', content: `${instructionForAI}\n\nYour response must be *only* the new, fully revised version of the content, suitable for direct replacement of the original.` });
                } else {
                    if (instructionForAI.trim() === "") {
                        instructionForAI = "Generate some relevant content.";
                    }
                    messages.push({ role: 'user', content: instructionForAI });
                }

                if (messages.length <= 1 && !instructionForAI) {
                    isRequestPending = false;
                    spinner.style.display = 'none';
                    updateSubmitButtonState();
                    responseDiv.textContent = "Please select a preset or type an instruction.";
                    responseDiv.style.display = 'block';
                    console.warn("SNN AI: No explicit instruction or preset selected for generation.");
                    return;
                }

                try {
                    const fetchResponse = await fetch(config.apiEndpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${config.apiKey}`
                        },
                        body: JSON.stringify({ model: config.model, messages })
                    });

                    if (!fetchResponse.ok) {
                        const errorData = await fetchResponse.json().catch(() => ({}));
                        let errorMsg = `API Error: ${fetchResponse.status} ${fetchResponse.statusText}`;
                        if (errorData.error && errorData.error.message) {
                            errorMsg += ` - ${errorData.error.message}`;
                        } else if (fetchResponse.status === 401) {
                            errorMsg += ' - Check API key.';
                        } else if (fetchResponse.status === 429) {
                            errorMsg += ' - Quota exceeded.';
                        }
                        throw new Error(errorMsg);
                    }

                    const data = await fetchResponse.json();
                    if (data.choices && data.choices.length && data.choices[0].message && data.choices[0].message.content) {
                        aiResponse = data.choices[0].message.content.trim();
                        responseDiv.textContent = aiResponse;
                        responseDiv.style.display = 'block';
                        copyButton.style.display = 'inline-block';
                        applyButton.style.display = 'inline-block';
                    } else {
                        throw new Error('Unexpected AI response format.');
                    }
                } catch (error) {
                    responseDiv.textContent = `Error: ${error.message}`;
                    responseDiv.style.display = 'block';
                    console.error("SNN AI Error:", error);
                } finally {
                    isRequestPending = false;
                    spinner.style.display = 'none';
                    updateSubmitButtonState();
                }
            });

            copyButton.addEventListener('click', () => {
                if (aiResponse) {
                    navigator.clipboard.writeText(aiResponse).then(() => {
                        copyButton.textContent = 'Copied!';
                        setTimeout(() => {
                            copyButton.textContent = 'Copy Text';
                        }, 1500);
                    }).catch(err => {
                        console.error('Failed to copy text.', err);
                    });
                }
            });

            applyButton.addEventListener('click', () => {
                if (!aiResponse) {
                    console.error('SNN AI: No response to apply.');
                    return;
                }

                // Apply content to block editor
                if (wp.data && wp.data.dispatch) {
                    const editor = wp.data.dispatch('core/editor');
                    if (editor) {
                        // Split content by double newlines to create paragraphs
                        const paragraphs = aiResponse.split('\n\n').filter(p => p.trim());

                        // Create paragraph blocks
                        const blocks = paragraphs.map(text => {
                            return wp.blocks.createBlock('core/paragraph', {
                                content: text.trim()
                            });
                        });

                        // Replace all blocks
                        wp.data.dispatch('core/block-editor').resetBlocks(blocks);

                        hideModal();
                    } else {
                        console.error('SNN AI: Could not access block editor.');
                        responseDiv.textContent = 'Error: Could not apply changes to editor.';
                        responseDiv.style.display = 'block';
                    }
                }
            });
        }

        // Start initialization
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initAIPanel);
        } else {
            initAIPanel();
        }
    })();
    </script>
    <?php
}
add_action('admin_footer', 'snn_add_block_editor_ai_panel');
