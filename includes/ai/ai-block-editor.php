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
            const { registerFormatType } = wp.richText;
            const { BlockControls } = wp.blockEditor;
            const { ToolbarGroup, ToolbarButton } = wp.components;
            const { createElement } = wp.element;

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

            // Register format type with modern ToolbarButton
            registerFormatType('snn/ai-assistant', {
                title: 'AI Assistant',
                tagName: 'span',
                className: null,
                edit: function(props) {
                    return createElement(BlockControls, null,
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

            if (!block || !block.attributes) {
                return text;
            }

            const attrs = block.attributes;
            const blockName = block.name;

            // Handle different block types based on their specific attributes
            switch (blockName) {
                // Text blocks with 'content' attribute
                case 'core/paragraph':
                case 'core/heading':
                case 'core/verse':
                case 'core/preformatted':
                case 'core/code':
                case 'core/list-item':
                case 'core/post-title':
                case 'core/site-title':
                case 'core/site-tagline':
                    text = attrs.content ? stripHtmlTags(attrs.content) : '';
                    break;

                // Quote blocks
                case 'core/quote':
                case 'core/pullquote':
                    text = attrs.value ? stripHtmlTags(attrs.value) : '';
                    if (attrs.citation) {
                        text += '\n— ' + stripHtmlTags(attrs.citation);
                    }
                    break;

                // List block
                case 'core/list':
                    text = attrs.values ? stripHtmlTags(attrs.values) : '';
                    break;

                // Button block
                case 'core/button':
                    text = attrs.text ? stripHtmlTags(attrs.text) : '';
                    break;

                // Cover block
                case 'core/cover':
                    text = attrs.alt || '';
                    break;

                // Image/Media blocks
                case 'core/image':
                case 'core/post-featured-image':
                    text = attrs.alt || attrs.caption ? stripHtmlTags(attrs.caption || '') : '';
                    break;

                // Video/Audio blocks
                case 'core/video':
                case 'core/audio':
                    text = attrs.caption ? stripHtmlTags(attrs.caption) : '';
                    break;

                // Table block
                case 'core/table':
                    if (attrs.body && Array.isArray(attrs.body)) {
                        attrs.body.forEach(function(row) {
                            if (row.cells && Array.isArray(row.cells)) {
                                row.cells.forEach(function(cell) {
                                    text += stripHtmlTags(cell.content || '') + ' | ';
                                });
                                text += '\n';
                            }
                        });
                    }
                    break;

                // Post content blocks
                case 'core/post-excerpt':
                    text = attrs.excerpt || '';
                    break;

                case 'core/post-content':
                case 'core/post-author-biography':
                case 'core/term-description':
                    text = attrs.content ? stripHtmlTags(attrs.content) : '';
                    break;

                // Comment blocks
                case 'core/comment-content':
                    text = attrs.content ? stripHtmlTags(attrs.content) : '';
                    break;

                // Search/Form blocks
                case 'core/search':
                    text = attrs.placeholder || attrs.buttonText || '';
                    break;

                // HTML/Shortcode blocks
                case 'core/html':
                case 'core/shortcode':
                    text = attrs.content || '';
                    break;

                // Details/Accordion blocks
                case 'core/details':
                    text = attrs.summary ? stripHtmlTags(attrs.summary) : '';
                    break;

                case 'core/accordion-item':
                    text = attrs.title ? stripHtmlTags(attrs.title) : '';
                    break;

                // File block
                case 'core/file':
                    text = attrs.fileName || '';
                    if (attrs.textLinkTarget) {
                        text += ' - ' + stripHtmlTags(attrs.textLinkTarget);
                    }
                    break;

                // Navigation blocks
                case 'core/navigation-link':
                case 'core/navigation-submenu':
                    text = attrs.label || '';
                    break;

                // Default: try common attributes
                default:
                    if (attrs.content) {
                        text = stripHtmlTags(attrs.content);
                    } else if (attrs.text) {
                        text = typeof attrs.text === 'string' ? attrs.text : stripHtmlTags(attrs.text);
                    } else if (attrs.value) {
                        text = stripHtmlTags(attrs.value);
                    } else if (attrs.values) {
                        text = stripHtmlTags(attrs.values);
                    }
                    break;
            }

            // Recursively extract from inner blocks
            if (block.innerBlocks && block.innerBlocks.length > 0) {
                block.innerBlocks.forEach(function(innerBlock) {
                    const innerText = extractTextFromBlock(innerBlock);
                    if (innerText) {
                        text += (text ? '\n' : '') + innerText;
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

            // If multiple blocks selected, split content by lines and distribute
            if (selectedBlocks.length > 1) {
                const contentLines = newContent.split('\n').filter(function(line) {
                    return line.trim();
                });

                selectedBlocks.forEach(function(blockId, index) {
                    const block = editor.getBlock(blockId);
                    if (block && contentLines[index]) {
                        updateSingleBlock(dispatch, block, blockId, contentLines[index]);
                    }
                });
            } else {
                // Single block update
                const blockId = selectedBlocks[0];
                const block = editor.getBlock(blockId);
                if (block) {
                    updateSingleBlock(dispatch, block, blockId, newContent);
                }
            }
        }

        function updateSingleBlock(dispatch, block, blockId, content) {
            const updatedAttributes = {};
            const blockName = block.name;

            // Handle different block types based on their specific attributes
            switch (blockName) {
                // Text blocks with 'content' attribute
                case 'core/paragraph':
                case 'core/heading':
                case 'core/verse':
                case 'core/preformatted':
                case 'core/code':
                case 'core/list-item':
                case 'core/post-title':
                case 'core/site-title':
                case 'core/site-tagline':
                case 'core/post-content':
                case 'core/post-author-biography':
                case 'core/term-description':
                case 'core/comment-content':
                    updatedAttributes.content = content;
                    break;

                // Quote blocks
                case 'core/quote':
                case 'core/pullquote':
                    // Try to split citation if present
                    const citationMatch = content.match(/\n—\s*(.+)$/);
                    if (citationMatch) {
                        updatedAttributes.value = content.replace(/\n—\s*.+$/, '');
                        updatedAttributes.citation = citationMatch[1];
                    } else {
                        updatedAttributes.value = content;
                    }
                    break;

                // List block
                case 'core/list':
                    updatedAttributes.values = content;
                    break;

                // Button block
                case 'core/button':
                    updatedAttributes.text = content;
                    break;

                // Cover block
                case 'core/cover':
                    updatedAttributes.alt = content;
                    break;

                // Image/Media blocks
                case 'core/image':
                case 'core/post-featured-image':
                    if (block.attributes.caption !== undefined) {
                        updatedAttributes.caption = content;
                    } else {
                        updatedAttributes.alt = content;
                    }
                    break;

                // Video/Audio blocks
                case 'core/video':
                case 'core/audio':
                    updatedAttributes.caption = content;
                    break;

                // Post content blocks
                case 'core/post-excerpt':
                    updatedAttributes.excerpt = content;
                    break;

                // Search/Form blocks
                case 'core/search':
                    if (block.attributes.placeholder !== undefined) {
                        updatedAttributes.placeholder = content;
                    } else {
                        updatedAttributes.buttonText = content;
                    }
                    break;

                // HTML/Shortcode blocks
                case 'core/html':
                case 'core/shortcode':
                    updatedAttributes.content = content;
                    break;

                // Details/Accordion blocks
                case 'core/details':
                    updatedAttributes.summary = content;
                    break;

                case 'core/accordion-item':
                    updatedAttributes.title = content;
                    break;

                // File block
                case 'core/file':
                    updatedAttributes.fileName = content;
                    break;

                // Navigation blocks
                case 'core/navigation-link':
                case 'core/navigation-submenu':
                    updatedAttributes.label = content;
                    break;

                // Default: try common attributes
                default:
                    if (block.attributes.content !== undefined) {
                        updatedAttributes.content = content;
                    } else if (block.attributes.text !== undefined) {
                        updatedAttributes.text = content;
                    } else if (block.attributes.value !== undefined) {
                        updatedAttributes.value = content;
                    } else if (block.attributes.values !== undefined) {
                        updatedAttributes.values = content;
                    } else {
                        // Fallback to content
                        updatedAttributes.content = content;
                    }
                    break;
            }

            // Apply the update
            if (Object.keys(updatedAttributes).length > 0) {
                dispatch('core/block-editor').updateBlockAttributes(
                    blockId,
                    updatedAttributes
                );
            }
        }
    })();
    </script>
    <?php
}
