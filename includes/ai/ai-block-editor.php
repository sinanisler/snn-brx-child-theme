<?php
/**
 * SNN AI Block Editor Integration
 *
 * File: ai-block-editor.php
 *
 * Purpose: This file adds AI assistant functionality to the WordPress Block Editor.
 * It adds a panel to the Document Settings sidebar with AI content generation capabilities.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Enqueue AI Block Editor Sidebar Panel
 */
function snn_enqueue_ai_sidebar_panel() {
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

    // Enqueue inline script for AI sidebar panel
    wp_enqueue_script('wp-plugins');
    wp_enqueue_script('wp-edit-post');
    wp_enqueue_script('wp-element');
    wp_enqueue_script('wp-components');
    wp_enqueue_script('wp-data');
    wp_enqueue_script('wp-i18n');
    wp_enqueue_script('wp-blocks');

    $inline_script = "
    (function(wp) {
        const { registerPlugin } = wp.plugins;
        const { PluginDocumentSettingPanel } = wp.editPost;
        const { Button, TextareaControl, Spinner, Notice } = wp.components;
        const { createElement: el, useState, useEffect } = wp.element;
        const { useSelect, useDispatch } = wp.data;
        const { __ } = wp.i18n;

        const config = {
            apiKey: " . json_encode($config['apiKey']) . ",
            model: " . json_encode($config['model']) . ",
            systemPrompt: " . json_encode($config['systemPrompt']) . ",
            apiEndpoint: " . json_encode($config['apiEndpoint']) . "
        };

        const actionPresets = " . json_encode($config['actionPresets']) . " || [];

        const SNNAISidebarPanel = () => {
            const [isGenerating, setIsGenerating] = useState(false);
            const [aiResponse, setAiResponse] = useState(null);
            const [error, setError] = useState(null);
            const [selectedPresets, setSelectedPresets] = useState([]);
            const [customPrompt, setCustomPrompt] = useState('');
            const [currentContent, setCurrentContent] = useState('');

            const blocks = useSelect((select) => {
                return select('core/block-editor').getBlocks();
            }, []);

            const { resetBlocks } = useDispatch('core/block-editor');

            useEffect(() => {
                const content = blocks.map(block => {
                    if (block.name === 'core/paragraph' || block.name === 'core/heading') {
                        return block.attributes.content || '';
                    }
                    return '';
                }).filter(text => text).join('\\n\\n');
                setCurrentContent(content);
            }, [blocks]);

            const togglePreset = (preset) => {
                setSelectedPresets((prev) => {
                    const exists = prev.find(p => p.name === preset.name);
                    if (exists) {
                        return prev.filter(p => p.name !== preset.name);
                    } else {
                        return [...prev, { name: preset.name, prompt: preset.prompt }];
                    }
                });
            };

            const isPresetSelected = (preset) => {
                return selectedPresets.some(p => p.name === preset.name);
            };

            const handleGenerate = async () => {
                if (!config.apiKey) {
                    setError(__('API Key missing in settings.', 'snn'));
                    return;
                }

                if (selectedPresets.length === 0 && !customPrompt.trim()) {
                    setError(__('Please select a preset or enter custom instructions.', 'snn'));
                    return;
                }

                setIsGenerating(true);
                setError(null);
                setAiResponse(null);

                try {
                    const messages = [];

                    if (config.systemPrompt) {
                        messages.push({ role: 'system', content: config.systemPrompt });
                    }

                    let instructionForAI = '';

                    if (selectedPresets.length > 0) {
                        instructionForAI += 'Apply the following actions:\\n';
                        selectedPresets.forEach(p => {
                            instructionForAI += \`- \${p.prompt}\\n\`;
                        });
                        instructionForAI += '\\n';
                    }

                    if (customPrompt.trim()) {
                        instructionForAI += \`Additional instructions: \${customPrompt.trim()}\`;
                    }

                    if (currentContent.trim()) {
                        messages.push({
                            role: 'user',
                            content: \`The current content is:\\n\\\`\\\`\\\`\\n\${currentContent}\\n\\\`\\\`\\\`\`
                        });
                        if (instructionForAI.trim() === '') {
                            instructionForAI = 'Review the current content and provide an improved version.';
                        }
                        messages.push({
                            role: 'user',
                            content: \`\${instructionForAI}\\n\\nYour response must be *only* the new, fully revised version of the content, suitable for direct replacement of the original.\`
                        });
                    } else {
                        if (instructionForAI.trim() === '') {
                            instructionForAI = 'Generate some relevant content.';
                        }
                        messages.push({ role: 'user', content: instructionForAI });
                    }

                    const response = await fetch(config.apiEndpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': \`Bearer \${config.apiKey}\`
                        },
                        body: JSON.stringify({
                            model: config.model,
                            messages
                        })
                    });

                    if (!response.ok) {
                        const errorData = await response.json().catch(() => ({}));
                        let errorMsg = \`API Error: \${response.status} \${response.statusText}\`;
                        if (errorData.error && errorData.error.message) {
                            errorMsg += \` - \${errorData.error.message}\`;
                        } else if (response.status === 401) {
                            errorMsg += ' - Check API key.';
                        } else if (response.status === 429) {
                            errorMsg += ' - Quota exceeded.';
                        }
                        throw new Error(errorMsg);
                    }

                    const data = await response.json();
                    if (data.choices && data.choices.length && data.choices[0].message && data.choices[0].message.content) {
                        const generatedContent = data.choices[0].message.content.trim();
                        setAiResponse(generatedContent);
                    } else {
                        throw new Error('Unexpected AI response format.');
                    }
                } catch (err) {
                    setError(err.message);
                    console.error('SNN AI Error:', err);
                } finally {
                    setIsGenerating(false);
                }
            };

            const handleApply = () => {
                if (!aiResponse) {
                    return;
                }

                const paragraphs = aiResponse.split('\\n\\n').filter(p => p.trim());

                const newBlocks = paragraphs.map(text => {
                    return wp.blocks.createBlock('core/paragraph', {
                        content: text.trim()
                    });
                });

                resetBlocks(newBlocks);

                setAiResponse(null);
                setSelectedPresets([]);
                setCustomPrompt('');
            };

            const handleCopy = () => {
                if (aiResponse) {
                    navigator.clipboard.writeText(aiResponse).then(() => {
                        // Copied successfully
                    }).catch(err => {
                        console.error('Failed to copy text:', err);
                    });
                }
            };

            return el(
                PluginDocumentSettingPanel,
                {
                    name: 'snn-ai-assistant-panel',
                    title: __('AI Content Assistant', 'snn'),
                    className: 'snn-ai-assistant-panel',
                },
                [
                    actionPresets.length > 0 && el(
                        'div',
                        {
                            key: 'presets',
                            style: {
                                marginBottom: '12px',
                                display: 'flex',
                                flexWrap: 'wrap',
                                gap: '6px'
                            }
                        },
                        actionPresets.map((preset, index) =>
                            el(
                                Button,
                                {
                                    key: index,
                                    variant: isPresetSelected(preset) ? 'primary' : 'secondary',
                                    isSmall: true,
                                    onClick: () => togglePreset(preset),
                                },
                                preset.name
                            )
                        )
                    ),

                    el(TextareaControl, {
                        key: 'prompt',
                        label: __('Custom Instructions', 'snn'),
                        value: customPrompt,
                        onChange: setCustomPrompt,
                        placeholder: __('Enter additional instructions...', 'snn'),
                        rows: 4,
                        help: currentContent.trim()
                            ? __('The AI will improve your existing content based on the selected actions and instructions.', 'snn')
                            : __('The AI will generate new content based on your instructions.', 'snn')
                    }),

                    el(
                        Button,
                        {
                            key: 'generate',
                            variant: 'primary',
                            onClick: handleGenerate,
                            disabled: isGenerating || (selectedPresets.length === 0 && !customPrompt.trim()),
                            style: { marginTop: '12px', width: '100%' }
                        },
                        isGenerating ? __('Generating...', 'snn') : __('Generate Content', 'snn')
                    ),

                    isGenerating && el(
                        'div',
                        {
                            key: 'spinner',
                            style: {
                                display: 'flex',
                                justifyContent: 'center',
                                margin: '16px 0'
                            }
                        },
                        el(Spinner)
                    ),

                    error && el(
                        Notice,
                        {
                            key: 'error',
                            status: 'error',
                            isDismissible: true,
                            onRemove: () => setError(null),
                        },
                        error
                    ),

                    aiResponse && el(
                        'div',
                        {
                            key: 'response',
                            style: {
                                marginTop: '16px',
                                padding: '12px',
                                backgroundColor: '#f6f7f7',
                                borderRadius: '4px',
                                border: '1px solid #dcdcde',
                                maxHeight: '200px',
                                overflowY: 'auto',
                                whiteSpace: 'pre-wrap',
                                fontSize: '13px'
                            }
                        },
                        aiResponse
                    ),

                    aiResponse && el(
                        'div',
                        {
                            key: 'actions',
                            style: {
                                display: 'flex',
                                gap: '8px',
                                marginTop: '12px'
                            }
                        },
                        [
                            el(
                                Button,
                                {
                                    key: 'copy',
                                    variant: 'secondary',
                                    onClick: handleCopy,
                                },
                                __('Copy', 'snn')
                            ),
                            el(
                                Button,
                                {
                                    key: 'apply',
                                    variant: 'primary',
                                    onClick: handleApply,
                                },
                                __('Apply to Editor', 'snn')
                            )
                        ]
                    )
                ]
            );
        };

        registerPlugin('snn-ai-assistant', {
            render: SNNAISidebarPanel,
            icon: 'superhero-alt',
        });

    })(window.wp);
    ";

    wp_add_inline_script('wp-plugins', $inline_script);
}
add_action('enqueue_block_editor_assets', 'snn_enqueue_ai_sidebar_panel');
