<?php
/**
 * SNN AI Block Editor Integration
 *
 * File: ai-block-editor.php
 *
 * Purpose: This file adds AI assistant functionality to the WordPress Block Editor (Gutenberg).
 * It allows users to generate or regenerate selected text within blocks using AI, with access
 * to the same action presets configured in the AI settings.
 *
 * Features:
 * - Toolbar button to launch AI overlay
 * - Generate or regenerate selected text in blocks
 * - Support for single and multi-block text selection
 * - Uses existing AI provider configuration and action presets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Enqueue block editor AI script and styles
 */
function snn_enqueue_block_editor_ai_assets() {
    // Only load in block editor
    if ( ! function_exists( 'get_current_screen' ) ) {
        return;
    }

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

    $ai_config = snn_get_ai_api_config();

    // Only proceed if we have valid configuration
    if ( empty( $ai_config['apiKey'] ) || empty( $ai_config['model'] ) || empty( $ai_config['apiEndpoint'] ) ) {
        return;
    }

    // Add inline script with AI configuration
    add_action( 'admin_footer', 'snn_render_block_editor_ai_overlay' );
}
add_action( 'admin_enqueue_scripts', 'snn_enqueue_block_editor_ai_assets' );

/**
 * Render the AI overlay HTML and scripts
 */
function snn_render_block_editor_ai_overlay() {
    $ai_config = snn_get_ai_api_config();
    ?>
    <style>
        /* AI Toolbar Button Styles */
        .snn-ai-toolbar-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            border: none;
            border-radius: 4px;
            padding: 6px 12px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            margin: 0 4px;
        }

        .snn-ai-toolbar-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .snn-ai-toolbar-button:active {
            transform: translateY(0);
        }

        /* AI Overlay Styles */
        #snn-block-ai-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 999999;
            backdrop-filter: blur(4px);
        }

        #snn-block-ai-overlay.active {
            display: flex;
        }

        .snn-block-ai-modal {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .snn-block-ai-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .snn-block-ai-header h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: white;
        }

        .snn-block-ai-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }

        .snn-block-ai-close:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .snn-block-ai-body {
            padding: 24px;
            overflow-y: auto;
            flex: 1;
        }

        .snn-block-ai-section {
            margin-bottom: 20px;
        }

        .snn-block-ai-section label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
            color: #374151;
        }

        .snn-block-ai-selected-text {
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 12px;
            font-size: 13px;
            color: #6b7280;
            max-height: 120px;
            overflow-y: auto;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.5;
        }

        .snn-block-ai-presets {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 12px;
        }

        .snn-block-ai-preset-btn {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 8px 14px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
            color: #374151;
            font-weight: 500;
        }

        .snn-block-ai-preset-btn:hover {
            background: #e5e7eb;
            border-color: #9ca3af;
        }

        .snn-block-ai-preset-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }

        .snn-block-ai-custom-input {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 10px 12px;
            font-size: 14px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            resize: vertical;
            min-height: 80px;
        }

        .snn-block-ai-custom-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .snn-block-ai-footer {
            padding: 16px 24px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            background: #f9fafb;
        }

        .snn-block-ai-btn {
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }

        .snn-block-ai-btn-cancel {
            background: white;
            color: #6b7280;
            border: 1px solid #d1d5db;
        }

        .snn-block-ai-btn-cancel:hover {
            background: #f9fafb;
        }

        .snn-block-ai-btn-generate {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .snn-block-ai-btn-generate:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .snn-block-ai-btn-generate:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .snn-block-ai-loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .snn-block-ai-loading.active {
            display: block;
        }

        .snn-block-ai-spinner {
            border: 3px solid #f3f4f6;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: snn-spin 1s linear infinite;
            margin: 0 auto 12px;
        }

        @keyframes snn-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .snn-block-ai-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 12px;
            border-radius: 6px;
            font-size: 13px;
            display: none;
            margin-top: 12px;
        }

        .snn-block-ai-error.active {
            display: block;
        }
    </style>

    <div id="snn-block-ai-overlay">
        <div class="snn-block-ai-modal">
            <div class="snn-block-ai-header">
                <h2>AI Content Assistant</h2>
                <button class="snn-block-ai-close" type="button">&times;</button>
            </div>
            <div class="snn-block-ai-body">
                <div class="snn-block-ai-section">
                    <label>Selected Text:</label>
                    <div class="snn-block-ai-selected-text" id="snn-block-ai-selected-text"></div>
                </div>

                <div class="snn-block-ai-section">
                    <label>Action Presets:</label>
                    <div class="snn-block-ai-presets" id="snn-block-ai-presets"></div>
                </div>

                <div class="snn-block-ai-section">
                    <label>Custom Instructions:</label>
                    <textarea
                        class="snn-block-ai-custom-input"
                        id="snn-block-ai-custom-prompt"
                        placeholder="Enter custom instructions or select a preset above..."></textarea>
                </div>

                <div class="snn-block-ai-loading" id="snn-block-ai-loading">
                    <div class="snn-block-ai-spinner"></div>
                    <div>Generating content...</div>
                </div>

                <div class="snn-block-ai-error" id="snn-block-ai-error"></div>
            </div>
            <div class="snn-block-ai-footer">
                <button class="snn-block-ai-btn snn-block-ai-btn-cancel" id="snn-block-ai-cancel" type="button">Cancel</button>
                <button class="snn-block-ai-btn snn-block-ai-btn-generate" id="snn-block-ai-generate" type="button">Generate</button>
            </div>
        </div>
    </div>

    <script>
    (function() {
        'use strict';

        // AI Configuration from PHP
        const aiConfig = <?php echo wp_json_encode($ai_config); ?>;

        // Store selected text and blocks
        let selectedText = '';
        let selectedBlocks = [];

        // Initialize when WordPress editor is ready
        if (typeof wp !== 'undefined' && wp.data && wp.blocks && wp.blockEditor) {
            wp.domReady(function() {
                initializeBlockEditorAI();
            });
        }

        function initializeBlockEditorAI() {
            const { registerPlugin } = wp.plugins;
            const { BlockControls } = wp.blockEditor;
            const { ToolbarGroup, ToolbarButton } = wp.components;
            const { createElement, Fragment } = wp.element;

            // Create AI icon SVG
            const aiIcon = createElement('svg', {
                width: 20,
                height: 20,
                viewBox: '0 0 20 20',
                xmlns: 'http://www.w3.org/2000/svg'
            },
                createElement('defs', null,
                    createElement('linearGradient', {
                        id: 'aiGradient',
                        x1: '0%',
                        y1: '0%',
                        x2: '100%',
                        y2: '100%'
                    },
                        createElement('stop', { offset: '0%', style: { stopColor: '#667eea' } }),
                        createElement('stop', { offset: '100%', style: { stopColor: '#764ba2' } })
                    )
                ),
                createElement('text', {
                    x: '50%',
                    y: '50%',
                    dominantBaseline: 'middle',
                    textAnchor: 'middle',
                    fontSize: '14',
                    fontWeight: 'bold',
                    fill: 'url(#aiGradient)'
                }, 'AI')
            );

            // Register plugin to add toolbar button
            registerPlugin('snn-ai-assistant', {
                render: function() {
                    const { useSelect } = wp.data;

                    const hasSelection = useSelect(function(select) {
                        const editor = select('core/block-editor');
                        const selectedBlockIds = editor.getSelectedBlockClientIds();
                        const selection = window.getSelection();
                        return selectedBlockIds.length > 0 || (selection && selection.toString().trim());
                    }, []);

                    if (!hasSelection) {
                        return null;
                    }

                    return createElement(BlockControls, { group: 'block' },
                        createElement(ToolbarGroup, null,
                            createElement(ToolbarButton, {
                                icon: aiIcon,
                                label: 'AI Assistant',
                                onClick: function() {
                                    openAIOverlay();
                                }
                            })
                        )
                    );
                }
            });

            // Setup overlay functionality
            setupOverlay();
        }

        function setupOverlay() {
            const overlay = document.getElementById('snn-block-ai-overlay');
            const closeBtn = document.querySelector('.snn-block-ai-close');
            const cancelBtn = document.getElementById('snn-block-ai-cancel');
            const generateBtn = document.getElementById('snn-block-ai-generate');
            const customPrompt = document.getElementById('snn-block-ai-custom-prompt');
            const presetsContainer = document.getElementById('snn-block-ai-presets');

            // Populate presets
            if (aiConfig.actionPresets && aiConfig.actionPresets.length > 0) {
                aiConfig.actionPresets.forEach(function(preset, index) {
                    const btn = document.createElement('button');
                    btn.className = 'snn-block-ai-preset-btn';
                    btn.textContent = preset.name;
                    btn.type = 'button';
                    btn.dataset.prompt = preset.prompt;
                    btn.addEventListener('click', function() {
                        // Toggle active state
                        document.querySelectorAll('.snn-block-ai-preset-btn').forEach(function(b) {
                            b.classList.remove('active');
                        });
                        btn.classList.add('active');
                        customPrompt.value = preset.prompt;
                    });
                    presetsContainer.appendChild(btn);
                });
            }

            // Close overlay handlers
            closeBtn.addEventListener('click', closeOverlay);
            cancelBtn.addEventListener('click', closeOverlay);
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) {
                    closeOverlay();
                }
            });

            // Generate button handler
            generateBtn.addEventListener('click', handleGenerate);

            // Clear active preset when typing custom prompt
            customPrompt.addEventListener('input', function() {
                document.querySelectorAll('.snn-block-ai-preset-btn').forEach(function(b) {
                    b.classList.remove('active');
                });
            });
        }

        function openAIOverlay() {
            const editor = wp.data.select('core/block-editor');
            const selectedBlockIds = editor.getSelectedBlockClientIds();

            // Get selected text from blocks
            selectedText = '';
            selectedBlocks = [];

            if (selectedBlockIds.length > 0) {
                selectedBlockIds.forEach(function(blockId) {
                    const block = editor.getBlock(blockId);
                    if (block) {
                        selectedBlocks.push(blockId);
                        // Extract text content from block
                        const textContent = extractTextFromBlock(block);
                        if (textContent) {
                            selectedText += textContent + '\n';
                        }
                    }
                });
            }

            // If no blocks selected, try to get text selection from current block
            if (!selectedText) {
                const selection = window.getSelection();
                if (selection && selection.toString().trim()) {
                    selectedText = selection.toString().trim();
                    // Get the current block
                    const currentBlockId = editor.getSelectedBlockClientId();
                    if (currentBlockId) {
                        selectedBlocks = [currentBlockId];
                    }
                }
            }

            if (!selectedText.trim()) {
                alert('Please select some text or blocks first.');
                return;
            }

            // Display selected text
            document.getElementById('snn-block-ai-selected-text').textContent = selectedText.trim();

            // Reset form
            document.getElementById('snn-block-ai-custom-prompt').value = '';
            document.querySelectorAll('.snn-block-ai-preset-btn').forEach(function(b) {
                b.classList.remove('active');
            });
            document.getElementById('snn-block-ai-error').classList.remove('active');
            document.getElementById('snn-block-ai-loading').classList.remove('active');

            // Show overlay
            document.getElementById('snn-block-ai-overlay').classList.add('active');
        }

        function closeOverlay() {
            document.getElementById('snn-block-ai-overlay').classList.remove('active');
        }

        function extractTextFromBlock(block) {
            let text = '';

            // Handle different block types
            if (block.attributes) {
                // Paragraph, heading, etc. with content attribute
                if (block.attributes.content) {
                    text = stripHtmlTags(block.attributes.content);
                }
                // List items
                else if (block.attributes.values) {
                    text = stripHtmlTags(block.attributes.values);
                }
                // Other text-based attributes
                else if (typeof block.attributes.text === 'string') {
                    text = block.attributes.text;
                }
            }

            // Recursively extract from inner blocks
            if (block.innerBlocks && block.innerBlocks.length > 0) {
                block.innerBlocks.forEach(function(innerBlock) {
                    const innerText = extractTextFromBlock(innerBlock);
                    if (innerText) {
                        text += '\n' + innerText;
                    }
                });
            }

            return text.trim();
        }

        function stripHtmlTags(html) {
            const tmp = document.createElement('div');
            tmp.innerHTML = html;
            return tmp.textContent || tmp.innerText || '';
        }

        async function handleGenerate() {
            const customPrompt = document.getElementById('snn-block-ai-custom-prompt').value.trim();
            const errorDiv = document.getElementById('snn-block-ai-error');
            const loadingDiv = document.getElementById('snn-block-ai-loading');
            const generateBtn = document.getElementById('snn-block-ai-generate');

            if (!customPrompt) {
                errorDiv.textContent = 'Please enter custom instructions or select a preset.';
                errorDiv.classList.add('active');
                return;
            }

            // Hide error, show loading
            errorDiv.classList.remove('active');
            loadingDiv.classList.add('active');
            generateBtn.disabled = true;

            try {
                const result = await callAI(customPrompt);

                // Update selected blocks with new content
                updateBlocksWithContent(result);

                // Close overlay
                closeOverlay();
            } catch (error) {
                errorDiv.textContent = 'Error: ' + error.message;
                errorDiv.classList.add('active');
            } finally {
                loadingDiv.classList.remove('active');
                generateBtn.disabled = false;
            }
        }

        async function callAI(userPrompt) {
            const messages = [
                {
                    role: 'system',
                    content: aiConfig.systemPrompt
                },
                {
                    role: 'user',
                    content: userPrompt + '\n\nCurrent text:\n' + selectedText
                }
            ];

            const requestBody = {
                model: aiConfig.model,
                messages: messages,
                temperature: 0.7
            };

            // Add response format if configured
            if (aiConfig.responseFormat && Object.keys(aiConfig.responseFormat).length > 0) {
                requestBody.response_format = aiConfig.responseFormat;
            }

            const response = await fetch(aiConfig.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + aiConfig.apiKey
                },
                body: JSON.stringify(requestBody)
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error('API request failed: ' + response.status + ' ' + errorText);
            }

            const data = await response.json();

            if (!data.choices || !data.choices[0] || !data.choices[0].message) {
                throw new Error('Invalid API response format');
            }

            return data.choices[0].message.content.trim();
        }

        function updateBlocksWithContent(newContent) {
            const { dispatch, select } = wp.data;
            const editor = select('core/block-editor');

            if (selectedBlocks.length === 0) {
                return;
            }

            // Update the first selected block with the new content
            const firstBlockId = selectedBlocks[0];
            const block = editor.getBlock(firstBlockId);

            if (block) {
                // Update block attributes based on block type
                const updatedAttributes = {};

                if (block.attributes.content !== undefined) {
                    // Paragraph, heading, etc.
                    updatedAttributes.content = newContent;
                } else if (block.attributes.text !== undefined) {
                    updatedAttributes.text = newContent;
                } else if (block.attributes.values !== undefined) {
                    // List
                    updatedAttributes.values = newContent;
                } else {
                    // Fallback: try content
                    updatedAttributes.content = newContent;
                }

                dispatch('core/block-editor').updateBlockAttributes(
                    firstBlockId,
                    updatedAttributes
                );
            }
        }
    })();
    </script>
    <?php
}
