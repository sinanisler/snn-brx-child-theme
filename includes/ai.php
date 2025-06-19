<?php

function snn_add_ai_settings_submenu() {
    add_submenu_page(
        'snn-settings',
        __('AI Settings', 'snn'),
        __('AI Settings', 'snn'),
        'manage_options',
        'snn-ai-settings',
        'snn_render_ai_settings'
    );
}
add_action('admin_menu', 'snn_add_ai_settings_submenu');

function snn_register_ai_settings() {
    register_setting('snn_ai_settings_group', 'snn_ai_enabled');
    register_setting('snn_ai_settings_group', 'snn_ai_provider', [
        'default' => 'openai',
    ]);
    register_setting('snn_ai_settings_group', 'snn_openai_api_key');
    register_setting('snn_ai_settings_group', 'snn_openai_model');
    register_setting('snn_ai_settings_group', 'snn_openrouter_api_key');
    register_setting('snn_ai_settings_group', 'snn_openrouter_model');
    register_setting('snn_ai_settings_group', 'snn_system_prompt');
    register_setting('snn_ai_settings_group', 'snn_ai_action_presets', [
        'type' => 'array',
        'default' => [],
    ]);

    // 1. Register new settings for custom provider
    register_setting('snn_ai_settings_group', 'snn_custom_api_key');
    register_setting('snn_ai_settings_group', 'snn_custom_api_endpoint');
    register_setting('snn_ai_settings_group', 'snn_custom_model');
}
add_action('admin_init', 'snn_register_ai_settings');

function snn_render_ai_settings() {
    $ai_enabled         = get_option('snn_ai_enabled', 'no');
    $ai_provider        = get_option('snn_ai_provider', 'openai');
    $openai_api_key     = get_option('snn_openai_api_key', '');
    $openai_model       = get_option('snn_openai_model', 'gpt-4o-mini'); // Updated model name
    $openrouter_api_key = get_option('snn_openrouter_api_key', '');
    $openrouter_model   = get_option('snn_openrouter_model', '');
    $system_prompt      = get_option(
        'snn_system_prompt',
        'You are a helpful assistant that helps with content creation or manipulation. You work inside a wordpress visual builder. User usually changes a website content. Keep the content length as similar the existing content when you are editing or follow the users instructions accordingly. Only respond with the needed content and nothing else always!'
    );

    $default_presets = [
        ['name' => 'Title',    'prompt' => 'Generate a catchy title.'],
        ['name' => 'Content',  'prompt' => 'Generate engaging content.'],
        ['name' => 'Button',   'prompt' => 'Suggest a call-to-action button text.'],
        ['name' => 'Funny',    'prompt' => 'Make it funny.'],
        ['name' => 'Sad',      'prompt' => 'Make it sad.'],
        ['name' => 'Business', 'prompt' => 'Make it professional and business-like.'],
        ['name' => 'Shorter',  'prompt' => 'Make the following text significantly shorter while preserving the core meaning.'],
        ['name' => 'Longer',   'prompt' => 'Make the following text significantly longer on the following text, adding more detail or explanation.'],
        ['name' => 'CSS',      'prompt' => 'Write clean native CSS only. Always use selector %root%, no <style> tag.'],
        ['name' => 'HTML',     'prompt' => 'Write html css and js if needed and you can use cdn lib if you wish. <html> <head> or <body> not needed.'],
        ['name' => 'GSAP',     'prompt' => '- Always use exact CSS syntax. - Animated code works for the nested elements; this output will animate DOM item or items. - Always use clear pairs: style_start-property:value, style_end-property:value. - Use ";" to create series animations where needed, like one animation plays after another with ";". Otherwise, if only "," is used, animations will play in parallel at the same time. - Always use clear pairs: style_start-property:value, style_end-property:value because gsap and animation system automatically converts these custom CSS syntax to the gsap animation. - Never use shortcuts like x:, y:, r:, s:, or o:. - Always explicitly use CSS properties for movements: transform:translateX(px) transform:translateY(px) transform:translateZ(px) transform:translate3d(x,y,z) transform:rotate(deg) transform:rotateX(deg) transform:rotateY(deg) transform:rotateZ(deg) transform:rotate3d(x,y,z,deg) transform:scale(value) transform:scaleX(value) transform:scaleY(value) transform:scaleZ(value) transform:skewX(deg) transform:skewY(deg) transform:perspective(px) opacity:value backgroundColor:color boxShadow:value border:value borderRadius:value clipPath:value - Optional controls clearly defined if needed: scroll:true scrub:value stagger:value - Always separate values clearly with commas. - No explanations or additional text—only exact CSS animation values. - Some examples; "style_start-transform:translate3d(200px, 100px, 50px), style_end-transform:translate3d(0px, 0px, 0px)" "x:200, style_start-transform:rotate(45deg), style_end-transform:rotate(0deg)" "y:-200, style_start-transform:scale(2), style_end-transform:scale(1)" "style_start-transform:translateX(200px) translateY(-200px), style_end-transform:translateX(0px) translateY(0px)" "x:100, style_start-opacity:0, style_end-opacity:1, scroll:true" "x:50, y:50, style_start-transform:rotate(0deg), style_end-transform:rotate(90deg), scroll:true"'],
    ];
    $stored_action_presets = get_option('snn_ai_action_presets', false);
    if ($stored_action_presets === false) {
        $action_presets = $default_presets;
    } elseif (!is_array($stored_action_presets) || empty($stored_action_presets)) {
        $action_presets = $default_presets;
    } else {
        $action_presets = $stored_action_presets;
    }

    $action_presets = array_values($action_presets);
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('AI Settings', 'snn'); ?></h1>
        <form method="post" action="options.php">
            <?php settings_fields('snn_ai_settings_group'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="snn_ai_enabled"><?php esc_html_e('Enable AI Features', 'snn'); ?></label>
                    </th>
                    <td>
                        <input
                            type="checkbox"
                            name="snn_ai_enabled"
                            id="snn_ai_enabled"
                            value="yes"
                            <?php checked($ai_enabled, 'yes'); ?>
                        />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="snn_ai_provider"><?php esc_html_e('API Provider', 'snn'); ?></label>
                    </th>
                    <td>
                        <select name="snn_ai_provider" id="snn_ai_provider">
                            <option value="openai" <?php selected($ai_provider, 'openai'); ?>>OpenAI</option>
                            <option value="openrouter" <?php selected($ai_provider, 'openrouter'); ?>>OpenRouter</option>
                            <option value="custom" <?php selected($ai_provider, 'custom'); ?>>Custom</option>
                        </select>
                    </td>
                </tr>
            </table>

            <h2><?php esc_html_e('System Prompt', 'snn'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="snn_system_prompt"><?php esc_html_e('System Prompt', 'snn'); ?></label>
                    </th>
                    <td>
                        <textarea
                            name="snn_system_prompt"
                            id="snn_system_prompt"
                            class="regular-text"
                            rows="5"
                        ><?php echo esc_textarea($system_prompt); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Enter the system prompt for AI interactions.', 'snn'); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <div
                id="openai-settings"
                style="display: <?php echo ($ai_provider === 'openai' && $ai_enabled === 'yes') ? 'block' : 'none'; ?>;"
            >
                <h2><?php esc_html_e('OpenAI API Settings', 'snn'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="snn_openai_api_key"><?php esc_html_e('OpenAI API Key', 'snn'); ?></label>
                        </th>
                        <td>
                            <input
                                type="password"
                                name="snn_openai_api_key"
                                id="snn_openai_api_key"
                                value="<?php echo esc_attr($openai_api_key); ?>"
                                class="regular-text"
                                autocomplete="new-password"
                            />
                            <p class="description">
                                <?php
                                printf(
                                    wp_kses_post(
                                        __('For more information, visit the <a href="%s" target="_blank" rel="noopener noreferrer">OpenAI API Keys page</a>.', 'snn')
                                    ),
                                    'https://platform.openai.com/account/api-keys'
                                );
                                ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="snn_openai_model"><?php esc_html_e('OpenAI Model', 'snn'); ?></label>
                        </th>
                        <td>
                            <input
                                type="text"
                                name="snn_openai_model"
                                id="snn_openai_model"
                                class="regular-text"
                                value="<?php echo esc_attr($openai_model); ?>"
                                placeholder="<?php esc_attr_e('Search for model...', 'snn'); ?>"
                                list="openai-models"
                                autocomplete="off"
                            >
                            <datalist id="openai-models">
                                <option value=""><?php esc_html_e('Loading models...', 'snn'); ?></option>
                            </datalist>
                            <p class="description">
                                <?php esc_html_e('Select the OpenAI model to use. Start typing to search.', 'snn'); ?><br>
                                <a href="https://platform.openai.com/docs/models" target="_blank"><?php esc_html_e('Model Info & Pricing', 'snn'); ?></a>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <div
                id="openrouter-settings"
                style="display: <?php echo ($ai_provider === 'openrouter' && $ai_enabled === 'yes') ? 'block' : 'none'; ?>;"
            >
                <h2><?php esc_html_e('OpenRouter API Settings', 'snn'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="snn_openrouter_api_key"><?php esc_html_e('OpenRouter API Key', 'snn'); ?></label>
                        </th>
                        <td>
                            <input
                                type="password"
                                name="snn_openrouter_api_key"
                                id="snn_openrouter_api_key"
                                value="<?php echo esc_attr($openrouter_api_key); ?>"
                                class="regular-text"
                                autocomplete="new-password"
                            />
                            <p class="description"><?php esc_html_e('Enter your OpenRouter API key.', 'snn'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="snn_openrouter_model"><?php esc_html_e('OpenRouter Model', 'snn'); ?></label>
                        </th>
                        <td>
                            <input
                                type="text"
                                name="snn_openrouter_model"
                                id="snn_openrouter_model"
                                class="regular-text"
                                value="<?php echo esc_attr($openrouter_model); ?>"
                                placeholder="<?php esc_attr_e('Search for model...', 'snn'); ?>"
                                list="openrouter-models"
                                autocomplete="off"
                            >
                            <datalist id="openrouter-models">
                                <option value=""><?php esc_html_e('Loading models...', 'snn'); ?></option>
                            </datalist>
                            <p class="description">
                                <?php esc_html_e('Select the OpenRouter model to use. Start typing to search.', 'snn'); ?>
                                <a href="https://openrouter.ai/models" target="_blank"><?php esc_html_e('Prices', 'snn'); ?></a>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <div
                id="custom-settings"
                style="display: <?php echo ($ai_provider === 'custom' && $ai_enabled === 'yes') ? 'block' : 'none'; ?>;"
            >
                <h2><?php esc_html_e('Custom API Settings', 'snn'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="snn_custom_api_key"><?php esc_html_e('Custom API Key', 'snn'); ?></label>
                        </th>
                        <td>
                            <input
                                type="password"
                                name="snn_custom_api_key"
                                id="snn_custom_api_key"
                                value="<?php echo esc_attr(get_option('snn_custom_api_key', '')); ?>"
                                class="regular-text"
                            />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="snn_custom_api_endpoint"><?php esc_html_e('Custom API Endpoint', 'snn'); ?></label>
                        </th>
                        <td>
                            <input
                                type="text"
                                name="snn_custom_api_endpoint"
                                id="snn_custom_api_endpoint"
                                value="<?php echo esc_attr(get_option('snn_custom_api_endpoint', '')); ?>"
                                class="regular-text"
                            />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="snn_custom_model"><?php esc_html_e('Custom Model', 'snn'); ?></label>
                        </th>
                        <td>
                            <input
                                type="text"
                                name="snn_custom_model"
                                id="snn_custom_model"
                                value="<?php echo esc_attr(get_option('snn_custom_model', '')); ?>"
                                class="regular-text"
                            />
                        </td>
                    </tr>
                </table>
            </div>

            <h2><?php esc_html_e('Action Prompts', 'snn'); ?></h2>
            <p>
                <?php esc_html_e('Add, edit, remove, or drag-and-drop to reorder AI action prompts. These presets will be available as selectable buttons in the AI overlay.', 'snn'); ?>
            </p>

            <table class="form-table" id="snn-ai-action-presets-table">
                <tbody>
                <?php if (!empty($action_presets)) : ?>
                    <?php foreach ($action_presets as $index => $preset) : ?>
                        <tr class="snn-ai-action-preset-row" draggable="true">
                            <td class="snn-ai-drag-handle" style="padding:0; width:30px; text-align:center; cursor:move; font-size:30px">⬍</td>
                            <td style="padding:2px">
                                <input
                                    type="text"
                                    name="snn_ai_action_presets[<?php echo $index; ?>][name]"
                                    value="<?php echo esc_attr($preset['name']); ?>"
                                    placeholder="<?php esc_attr_e('Action Name', 'snn'); ?>"
                                    class="regular-text preset-name-input"
                                />
                            </td>
                            <td style="padding:2px">
                                <textarea
                                    name="snn_ai_action_presets[<?php echo $index; ?>][prompt]"
                                    rows="2"
                                    placeholder="<?php esc_attr_e('Action Prompt', 'snn'); ?>"
                                    class="regular-text preset-prompt-input"
                                ><?php echo esc_textarea($preset['prompt']); ?></textarea>
                            </td>
                            <td style="padding:2px">
                                <button type="button" class="button snn-ai-remove-preset"><?php esc_html_e('Remove', 'snn'); ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>

            <p>
                <button type="button" class="button" id="snn-ai-add-preset"><?php esc_html_e('Add Preset', 'snn'); ?></button>
                <button type="button" class="button" id="snn-ai-reset-presets" style="margin-left: 10px;"><?php esc_html_e('Reset Presets', 'snn'); ?></button>
            </p>

            <style>
            #snn-ai-action-presets-table {
                max-width: 660px;
            }
            #snn-ai-action-presets-table td {
                vertical-align: top;
            }
            .snn-ai-action-preset-row input.regular-text {
                max-width: 220px;
                height: 46px;
            }
            #openai-settings #snn_openai_model,
            #openai-settings #snn_openai_api_key,
            #openrouter-settings #snn_openrouter_api_key,
            #openrouter-settings #snn_openrouter_model {
                width: 430px;
                max-width: 430px;
            }
            #openai-settings #snn_openai_api_key,
            #openrouter-settings #snn_openrouter_api_key {
                margin-bottom: 10px;
            }
            .snn-drag-over-row {
                outline: 2px dashed #0073aa;
            }
            [name="snn_system_prompt"]{width:430px}
            </style>

            <?php submit_button(__('Save AI Settings', 'snn')); ?>
        </form>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const enableCheckbox = document.getElementById('snn_ai_enabled');
            const providerSelect = document.getElementById('snn_ai_provider');
            const openaiSettingsDiv = document.getElementById('openai-settings');
            const openrouterSettingsDiv = document.getElementById('openrouter-settings');
            const customSettingsDiv = document.getElementById('custom-settings'); 

            function toggleSettingsVisibility() {
                const isEnabled = enableCheckbox.checked;
                openaiSettingsDiv.style.display = 'none';
                openrouterSettingsDiv.style.display = 'none';
                customSettingsDiv.style.display = 'none';

                if (isEnabled) {
                    if (providerSelect.value === 'openai') {
                        openaiSettingsDiv.style.display = 'block';
                        fetchOpenAiModels();
                    } else if (providerSelect.value === 'openrouter') {
                        openrouterSettingsDiv.style.display = 'block';
                        fetchOpenRouterModels();
                    } else if (providerSelect.value === 'custom') {
                        customSettingsDiv.style.display = 'block';
                    }
                }
            }

            function fetchOpenRouterModels() {
                const dataListEl = document.getElementById('openrouter-models');
                if (!dataListEl) return;
                const openrouterKeyEl = document.getElementById('snn_openrouter_api_key');
                const openrouterKey = openrouterKeyEl ? openrouterKeyEl.value.trim() : '';
                if (!openrouterKey) {
                    dataListEl.innerHTML = '<option value=""><?php esc_html_e('OpenRouter key missing. Please add your key first.', 'snn'); ?></option>';
                    return;
                }
                dataListEl.innerHTML = '<option value=""><?php esc_html_e('Loading models...', 'snn'); ?></option>';
                let slowTimeout = setTimeout(function(){
                    dataListEl.innerHTML = '<option value=""><?php esc_html_e('Still loading models... (this is taking longer than usual)', 'snn'); ?></option>';
                }, 3000);
                fetch('https://openrouter.ai/api/v1/models', {
                    headers: { 'Authorization': 'Bearer ' + openrouterKey }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('OpenRouter models API error: ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data && data.data) {
                        dataListEl.innerHTML = '';
                        data.data.forEach(model => {
                            const option = document.createElement('option');
                            option.value = model.id;
                            option.text = model.name + ' (' + model.context_length + ' tokens)';
                            dataListEl.appendChild(option);
                        });
                    } else {
                        dataListEl.innerHTML = '<option value=""><?php esc_html_e('No models found.', 'snn'); ?></option>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching OpenRouter models:', error);
                    dataListEl.innerHTML = '<option value=""><?php esc_html_e('Error loading models.', 'snn'); ?></option>';
                })
                .finally(() => {
                    clearTimeout(slowTimeout);
                });
            }

            function fetchOpenAiModels() {
                const dataListEl = document.getElementById('openai-models');
                if (!dataListEl) return;
                const openAiApiKeyEl = document.getElementById('snn_openai_api_key');
                const openAiApiKey = openAiApiKeyEl ? openAiApiKeyEl.value.trim() : '';
                if (!openAiApiKey) {
                    dataListEl.innerHTML = '<option value=""><?php esc_html_e('OpenAI key missing. Please add your key first.', 'snn'); ?></option>';
                    return;
                }
                dataListEl.innerHTML = '<option value=""><?php esc_html_e('Loading models...', 'snn'); ?></option>';
                let slowTimeout = setTimeout(function(){
                    dataListEl.innerHTML = '<option value=""><?php esc_html_e('Still loading models... (this is taking longer than usual)', 'snn'); ?></option>';
                }, 3000);
                fetch('https://api.openai.com/v1/models', {
                    headers: { 'Authorization': 'Bearer ' + openAiApiKey }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('OpenAI models API error: ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data && data.data) {
                        const forbiddenKeywords = [
                            "babbage", "tts", "whisper", "moderation", "embedding", "transcribe", "dall", "audio"
                        ];
                        let filteredModels = data.data.filter(m => {
                            const modelId = m.id.toLowerCase();
                            return forbiddenKeywords.every(keyword => !modelId.includes(keyword));
                        });
                        let miniModels = filteredModels.filter(m => m.id.toLowerCase().includes('mini'));
                        let otherModels = filteredModels.filter(m => !m.id.toLowerCase().includes('mini'));
                        miniModels.sort((a, b) => a.id.localeCompare(b.id));
                        otherModels.sort((a, b) => a.id.localeCompare(b.id));
                        let sortedModels = miniModels.concat(otherModels);
                        dataListEl.innerHTML = '';
                        sortedModels.forEach(model => {
                            if (model.id) {
                                const option = document.createElement('option');
                                option.value = model.id;
                                option.text = model.id;
                                dataListEl.appendChild(option);
                            }
                        });
                    } else {
                        dataListEl.innerHTML = '<option value=""><?php esc_html_e('No models found.', 'snn'); ?></option>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching OpenAI models:', error);
                    dataListEl.innerHTML = '<option value=""><?php esc_html_e('Error loading models.', 'snn'); ?></option>';
                })
                .finally(() => {
                    clearTimeout(slowTimeout);
                });
            }

            if (enableCheckbox && providerSelect) {
                enableCheckbox.addEventListener('change', toggleSettingsVisibility);
                providerSelect.addEventListener('change', toggleSettingsVisibility);
                toggleSettingsVisibility(); 
            }

            const addPresetButton = document.getElementById('snn-ai-add-preset');
            const resetPresetButton = document.getElementById('snn-ai-reset-presets');
            const presetsTableBody = document.querySelector('#snn-ai-action-presets-table tbody');

            function updatePresetIndices() {
                if (!presetsTableBody) return;
                const rows = presetsTableBody.querySelectorAll('tr.snn-ai-action-preset-row');
                rows.forEach((row, index) => {
                    const nameInput = row.querySelector('.preset-name-input');
                    const promptInput = row.querySelector('.preset-prompt-input');
                    if (nameInput) nameInput.name = `snn_ai_action_presets[${index}][name]`;
                    if (promptInput) promptInput.name = `snn_ai_action_presets[${index}][prompt]`;
                });
            }

            if (addPresetButton && presetsTableBody) {
                addPresetButton.addEventListener('click', function() {
                    const newIndex = presetsTableBody.querySelectorAll('tr.snn-ai-action-preset-row').length;
                    const row = document.createElement('tr');
                    row.className = 'snn-ai-action-preset-row';
                    row.setAttribute('draggable', 'true');
                    row.innerHTML = `
                        <td class="snn-ai-drag-handle" style="padding:0; width:30px; text-align:center; cursor:move; font-size:30px">⬍</td>
                        <td style="padding:2px">
                            <input
                                type="text"
                                name="snn_ai_action_presets[${newIndex}][name]"
                                placeholder="<?php echo esc_js(__('Action Name', 'snn')); ?>"
                                class="regular-text preset-name-input" />
                        </td>
                        <td style="padding:2px">
                            <textarea
                                name="snn_ai_action_presets[${newIndex}][prompt]"
                                rows="2"
                                placeholder="<?php echo esc_js(__('Action Prompt', 'snn')); ?>"
                                class="regular-text preset-prompt-input"></textarea>
                        </td>
                        <td style="padding:2px">
                            <button type="button" class="button snn-ai-remove-preset"><?php echo esc_js(__('Remove', 'snn')); ?></button>
                        </td>
                    `;
                    presetsTableBody.appendChild(row);
                    updatePresetIndices();
                });
            }

            if (resetPresetButton && presetsTableBody) {
                resetPresetButton.addEventListener('click', function() {
                    // Implement custom modal for confirmation instead of window.confirm
                    // For now, proceeding as if confirmed.
                    if (true) { 
                        presetsTableBody.innerHTML = '';
                        const defaultPresets = <?php echo json_encode($default_presets); ?>;
                        defaultPresets.forEach((preset, index) => {
                            const row = document.createElement('tr');
                            row.className = 'snn-ai-action-preset-row';
                            row.setAttribute('draggable', 'true');
                            row.innerHTML = `
                                <td class="snn-ai-drag-handle" style="padding:0; width:30px; text-align:center; cursor:move; font-size:30px">⬍</td>
                                <td style="padding:2px">
                                    <input
                                        type="text"
                                        name="snn_ai_action_presets[${index}][name]"
                                        value="${preset.name}"
                                        placeholder="<?php echo esc_js(__('Action Name', 'snn')); ?>"
                                        class="regular-text preset-name-input" />
                                </td>
                                <td style="padding:2px">
                                    <textarea
                                        name="snn_ai_action_presets[${index}][prompt]"
                                        rows="2"
                                        placeholder="<?php echo esc_js(__('Action Prompt', 'snn')); ?>"
                                        class="regular-text preset-prompt-input">${preset.prompt}</textarea>
                                </td>
                                <td style="padding:2px">
                                    <button type="button" class="button snn-ai-remove-preset"><?php echo esc_js(__('Remove', 'snn')); ?></button>
                                </td>
                            `;
                            presetsTableBody.appendChild(row);
                        });
                        updatePresetIndices();
                    }
                });
            }

            if (presetsTableBody) {
                presetsTableBody.addEventListener('click', function(e) {
                    if (e.target && e.target.classList.contains('snn-ai-remove-preset')) {
                        e.preventDefault();
                        const row = e.target.closest('tr.snn-ai-action-preset-row');
                        if (row) {
                            row.remove();
                            updatePresetIndices();
                        }
                    }
                });
            }

            let draggingRow = null;
            if (presetsTableBody) {
                presetsTableBody.addEventListener('dragstart', (e) => {
                    const target = e.target;
                    if (target && target.classList.contains('snn-ai-action-preset-row')) {
                        draggingRow = target;
                        e.dataTransfer.setData('text/plain', ''); 
                        e.dataTransfer.effectAllowed = 'move';
                    }
                });
                presetsTableBody.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    const target = e.target.closest('tr.snn-ai-action-preset-row');
                    if (target && target !== draggingRow) {
                        const bounding = target.getBoundingClientRect();
                        const offset = bounding.y + bounding.height / 2;
                        if (e.clientY - offset > 0) {
                            if (target.nextSibling !== draggingRow) {
                                target.parentNode.insertBefore(draggingRow, target.nextSibling);
                            }
                        } else {
                            if (target !== draggingRow.nextSibling) {
                                target.parentNode.insertBefore(draggingRow, target);
                            }
                        }
                    }
                });
                presetsTableBody.addEventListener('dragend', () => {
                    draggingRow = null;
                    updatePresetIndices();
                });
            }
            updatePresetIndices(); 
        });
        </script>
    </div>
    <?php
}

function snn_add_ai_script_to_footer() {
    if (
        ! current_user_can('manage_options') ||
        ! isset($_GET['bricks']) ||
        $_GET['bricks'] !== 'run'
    ) {
        return;
    }

    $ai_enabled         = get_option('snn_ai_enabled', 'no');
    $ai_provider        = get_option('snn_ai_provider', 'openai');
    $openai_api_key     = get_option('snn_openai_api_key', '');
    $openai_model       = get_option('snn_openai_model', 'gpt-4.1-mini');
    $openrouter_api_key = get_option('snn_openrouter_api_key', '');
    $openrouter_model   = get_option('snn_openrouter_model', '');
    $system_prompt      = get_option(
        'snn_system_prompt',
        'You are a helpful assistant that helps with content creation or manipulation. You work inside a wordpress visual builder. User usually changes a website content. Keep the content length as similar the existing content when you are editing or follow the users instructions accordingly. Dont generate markdown. Only respond with the needed content and nothing else always!'
    );

    if ($ai_enabled !== 'yes') { 
        return;
    }
    
    if ($ai_provider === 'openai' && empty($openai_api_key)) return;
    if ($ai_provider === 'openrouter' && empty($openrouter_api_key)) return;
    if ($ai_provider === 'custom' && (empty(get_option('snn_custom_api_key','')) || empty(get_option('snn_custom_api_endpoint','')))) return;


    if ($ai_provider === 'custom') {
        $apiKey      = get_option('snn_custom_api_key', '');
        $model       = get_option('snn_custom_model', '');
        $apiEndpoint = get_option('snn_custom_api_endpoint', '');
    } elseif ($ai_provider === 'openrouter') {
        $apiKey      = $openrouter_api_key;
        $model       = $openrouter_model;
        $apiEndpoint = 'https://openrouter.ai/api/v1/chat/completions';
    } else { 
        $apiKey      = $openai_api_key;
        $model       = $openai_model;
        $apiEndpoint = 'https://api.openai.com/v1/chat/completions';
    }

    $action_presets = get_option('snn_ai_action_presets', []);
    if (!is_array($action_presets)) {
        $action_presets = [];
    }
    ?>

    <style>
        .snn-ai-button { background-color: #454f59; color: #bebebe; padding: 2px 4px; border-radius: 4px; cursor: pointer; font-size: 12px; margin-left: 10px; display: inline-flex; align-items: center; transition: all 0.2s ease; position: absolute; right: 4px; top: 26px; z-index: 1; }
        [data-control="editor"] .snn-ai-button { top: auto; bottom: 20px; }
        [data-control="text"] .snn-ai-button { top: auto; bottom: 6px; right: 30px; }
        [data-control="code"] .snn-ai-button { top: auto; bottom: 32px; padding: 3px 5px; font-size: 16px; }
         [data-control="query"] .snn-ai-button,
        [data-control="number"] .snn-ai-button,
        [data-control="link"] .snn-ai-button,
        [data-controlkey="scrub"] .snn-ai-button,
        [data-controlkey="stagger"] .snn-ai-button,
        [data-controlkey="scroll_start"] .snn-ai-button,
        [data-controlkey="scroll_end"] .snn-ai-button ,
        [data-controlkey="_aspectRatio"] .snn-ai-button,
        [data-controlkey="_objectPosition"] .snn-ai-button,
        [data-controlkey="tag"] .snn-ai-button,
        [class="external-url"] .snn-ai-button,
        [data-controlkey="ariaLabel"] .snn-ai-button,
        [data-controlkey="itemTransition"] .snn-ai-button,
        [data-controlkey="dropdownTransition"] .snn-ai-button,
        [data-controlkey="dropdownItemTransition"] .snn-ai-button,
        [data-controlkey="toggleSelector"] .snn-ai-button,
        [data-controlkey="toggleAttribute"] .snn-ai-button,
        [data-controlkey="toggleValue"] .snn-ai-button,
        [data-controlkey="expandItem"] .snn-ai-button,
        [data-controlkey="submitButtonText"] .snn-ai-button,
        [data-controlkey="fields"] .snn-ai-button,
        [data-control-group="email"] .snn-ai-button,
        [data-control-group="confirmation"] .snn-ai-button,
        [data-control-group="fields"] .snn-ai-button,
        [data-controlkey="prefix"] .snn-ai-button,
        [data-controlkey="suffix"] .snn-ai-button,
        [data-controlkey="cursorChar"] .snn-ai-button,
        [data-controlkey="countFrom"] .snn-ai-button,
        [data-controlkey="duration"] .snn-ai-button,
        [data-controlkey="_flexBasis"] .snn-ai-button,
        [data-controlkey="_overflow"] .snn-ai-button,
        [data-controlkey="_pointerEvents"] .snn-ai-button,
        [data-controlkey="_transformOrigin"] .snn-ai-button,
        [data-controlkey="_cssTransition"] .snn-ai-button,
        [data-controlkey="_cssClasses"] .snn-ai-button,
        [data-controlkey="_cssId"] .snn-ai-button,
        [data-controlkey="countTo"] .snn-ai-button,
        [data-controlkey="pricePrefix"] .snn-ai-button,
        [data-controlkey="price"] .snn-ai-button,
        [data-controlkey="priceSuffix"] .snn-ai-button,
        [data-controlkey="priceMeta"] .snn-ai-button,
        [data-controlkey="address"] .snn-ai-button,
        [data-controlkey="latitude"] .snn-ai-button,
        [data-controlkey="longitude"] .snn-ai-button,
        [data-control-key="label"] .snn-ai-button, 
        [data-controlkey="titleTag"] .snn-ai-button,
        [data-controlkey="iconTransition"] .snn-ai-button,
        [data-controlkey="multiLevelBackText"] .snn-ai-button,
        [data-controlkey="href"] .snn-ai-button,
        [data-control-key="meta"] .snn-ai-button, 
        [data-control-key="anchorId"] .snn-ai-button, 
        [type="image"] .snn-ai-button, 
        #ariaLabel .snn-ai-button, 
        [data-control="typography"] .snn-ai-button,
        #bricks-popup .snn-ai-button, 
        [data-controlkey="_gridItemColumnSpan"] .snn-ai-button,
        [data-controlkey="_gridItemRowSpan"] .snn-ai-button,
        [data-controlkey="_gridTemplateColumns"] .snn-ai-button, 
        [data-controlkey="_gridTemplateRows"] .snn-ai-button, 
        [data-controlkey="_gridAutoColumns"] .snn-ai-button, 
        [data-controlkey="_gridAutoRows"] .snn-ai-button, 
        [data-control="gradient"] .snn-ai-button,
        #bricks-panel-component-instance .snn-ai-button

        { 
            display: none !important; 
        }

        [data-controlkey="custom_data_animate_dynamic_elements_custom"] .snn-ai-button { display: block !important; }
        .snn-ai-button:hover { background-color: var(--builder-bg-accent); color: var(--builder-color-accent); }
        .snn-ai-overlay { display: none; position: fixed; bottom: 0; left: 0; width: 100%; z-index: 99999999; justify-content: center; font-size: 14px; line-height: 1.2; align-items: flex-end; }
        .snn-ai-modal { background-color: var(--builder-bg); color: var(--builder-color); border-radius: 4px 4px 0 0; width: 800px; max-width: 90%; max-height: 80vh; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 0 20px rgba(0, 0, 0, 0.3); }
        .snn-ai-modal-header { padding: 10px 15px; background-color: var(--builder-bg-shade); display: flex; justify-content: space-between; align-items: center; } 
        .snn-ai-modal-header h3 { margin: 0; font-size: 18px; color: var(--builder-color); }
        .snn-ai-close { cursor: pointer; font-size: 26px; color: var(--builder-color-light); line-height: 1; transform: scaleX(1.3); } 
        .snn-ai-modal-body { padding: 15px; overflow-y: auto; flex: 1; }
        .snn-ai-prompt { width: 100%; min-height: 140px; padding: 8px; border-radius: 4px; margin-bottom: 10px; font-family: inherit; resize: vertical; background-color: var(--builder-bg-light); color: var(--builder-color); border: solid 1px #00000055; box-sizing: border-box; }
        .snn-ai-actions-container { margin-bottom: 10px; }
        .snn-ai-action-button { display: inline-block; padding: 4px 2px; margin: 2px; background-color: var(--builder-bg); border: 1px solid #00000055; border-radius: 4px; cursor: pointer; color: var(--builder-color); font-size: 14px; }
        .snn-ai-action-button.selected { background-color: var(--builder-bg-accent); color: var(--builder-color-accent); border-color: var(--builder-color-accent); }
        .snn-ai-submit, .snn-ai-copy, .snn-ai-apply, .snn-bulk-ai-submit, .snn-bulk-ai-apply { background-color: var(--builder-color-accent); color: var(--builder-bg); border: none; border-radius: 4px; padding: 10px 20px; cursor: pointer; font-size: 14px; transition: all 0.2s ease; border: solid 1px transparent; margin-top: 5px; }
        .snn-ai-submit:hover, .snn-ai-copy:hover, .snn-ai-apply:hover, .snn-bulk-ai-submit:hover, .snn-bulk-ai-apply:hover { color: var(--builder-color-accent); background: var(--builder-bg); border: solid 1px #00000055; }
        .snn-ai-submit:disabled, .snn-ai-copy:disabled, .snn-ai-apply:disabled, .snn-bulk-ai-submit:disabled, .snn-bulk-ai-apply:disabled { background-color: #ccc; cursor: not-allowed; }
        .snn-ai-response { padding: 15px; background-color: var(--builder-bg-light); border-radius: 4px; margin-top: 15px; display: none; overflow: auto; max-height: 150px; white-space: pre-wrap; }
        .snn-ai-response-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 15px; }
        .snn-ai-spinner { display: none; margin: 20px auto; border: 3px solid var(--builder-border-color); border-top: 3px solid #10a37f; border-radius: 50%; width: 30px; height: 30px; animation: snn-ai-spin 1s linear infinite; }
        @keyframes snn-ai-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        .snn-bulk-ai-modal { width: 900px; max-height: 85vh; }
        .snn-bulk-ai-fields-container { display: flex; flex-direction: column; gap: 4px; margin-bottom:15px; max-height: 300px; overflow-y:auto; padding-right:10px; }
        .snn-bulk-ai-field-group { display: flex; flex-direction: column; gap: 5px; position:relative; } /* Adjusted gap */
        .snn-bulk-ai-field-header { display: flex; align-items: center; gap: 8px; margin-bottom: 5px; position:relative;}
        .snn-bulk-ai-field-checkbox { width:15px; height:15px; margin: 0; }
        .snn-bulk-ai-field-group label { font-size: 14px; color: var(--builder-color-light); cursor: pointer; flex-grow: 1;  position: relative; top: 2px; width: auto; opacity:0.5;}
        .snn-bulk-ai-field-group textarea { width: 100%; min-height: 30px; padding: 8px; border-radius: 4px; font-family: inherit; resize: vertical; background-color: var(--builder-bg-light); color: var(--builder-color); border: solid 1px #00000055; box-sizing: border-box; }
        .snn-bulk-ai-response-display { white-space: pre-wrap; max-height: 200px; overflow-y: auto; background-color: var(--builder-bg-light); padding: 10px; border-radius: 4px; margin-top: 10px; border: 1px solid var(--builder-border-color); font-size:12px;}
        .snn-bulk-ai-footer-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 15px; }
        #snn-bulk-ai-select-all,
        #snn-bulk-ai-deselect-all{background:#293038; color:white; padding: 4px; border-radius: 5px;}
    </style>

    <div class="snn-ai-overlay" id="snn-ai-overlay">
        <div class="snn-ai-modal">
            <div class="snn-ai-modal-header">
                <h3><?php esc_html_e('AI Content Assistant', 'snn'); ?></h3>
                <span class="snn-ai-close" id="snn-ai-close-button">X</span>
            </div>
            <div class="snn-ai-modal-body">
                <div>
                    <div id="snn-ai-actions-container" class="snn-ai-actions-container"></div>
                    <textarea
                        id="snn-ai-prompt-textarea"
                        class="snn-ai-prompt"
                        placeholder="<?php esc_attr_e('Existing content will appear here. Add your instructions or select a preset...', 'snn'); ?>"
                    ></textarea>
                </div>
                <button id="snn-ai-submit" class="snn-ai-submit"><?php esc_html_e('Generate', 'snn'); ?></button>
                <div id="snn-ai-spinner" class="snn-ai-spinner"></div>
                <div id="snn-ai-response" class="snn-ai-response"></div>
                <div class="snn-ai-response-actions">
                    <button id="snn-ai-copy" class="snn-ai-copy" style="display: none;"><?php esc_html_e('Copy Text', 'snn'); ?></button>
                    <button id="snn-ai-apply" class="snn-ai-apply" style="display: none;"><?php esc_html_e('Apply to Editor', 'snn'); ?></button>
                </div>
            </div>
        </div>
    </div>

    <div class="snn-ai-overlay" id="snn-bulk-ai-overlay">
        <div class="snn-ai-modal snn-bulk-ai-modal">
            <div class="snn-ai-modal-header">
                <h3><?php esc_html_e('Bulk AI Content Editor', 'snn'); ?></h3>
                <span class="snn-ai-close" id="snn-bulk-ai-close-button">X</span>
            </div>
            <div class="snn-ai-modal-body">
                <div id="snn-bulk-ai-selection-actions" style="margin-bottom: 10px;">
                    <button type="button" class="button" id="snn-bulk-ai-select-all"><?php esc_html_e('Select All', 'snn'); ?></button>
                    <button type="button" class="button" id="snn-bulk-ai-deselect-all" style="margin-left: 5px;"><?php esc_html_e('Deselect All', 'snn'); ?></button>
                </div>
                <div id="snn-bulk-ai-fields-container" class="snn-bulk-ai-fields-container">
                    </div>
                <hr style="margin: 15px 0;">
                <div id="snn-bulk-ai-actions-container" class="snn-ai-actions-container">
                    </div>
                <textarea
                    id="snn-bulk-ai-prompt-textarea"
                    class="snn-ai-prompt"
                    placeholder="<?php esc_attr_e('Enter prompt..', 'snn'); ?>"
                ></textarea>
                <button id="snn-bulk-ai-submit" class="snn-bulk-ai-submit"><?php esc_html_e('Regenerate Selected', 'snn'); ?></button>
                <div id="snn-bulk-ai-spinner" class="snn-ai-spinner"></div>
                <div id="snn-bulk-ai-response-display" class="snn-bulk-ai-response-display" style="display:none;">
                </div>
                 <div class="snn-bulk-ai-footer-actions">
                    <button id="snn-bulk-ai-apply" class="snn-bulk-ai-apply" style="display: none;"><?php esc_html_e('Apply Changes to Editor', 'snn'); ?></button>
                </div>
            </div>
        </div>
    </div>


    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const config = {
            apiKey: <?php echo json_encode($apiKey); ?>,
            model: <?php echo json_encode($model); ?>,
            systemPrompt: <?php echo json_encode($system_prompt); ?>,
            apiEndpoint: <?php echo json_encode($apiEndpoint); ?>
        };

        let actionPresets = <?php echo json_encode($action_presets); ?>;
        if (!Array.isArray(actionPresets)) {
            actionPresets = [];
            console.warn('SNN AI: Action presets data seems invalid.');
        }

        let selectedPresets = [];
        let targetElement = null;
        let targetType = null;
        let aiResponse = null; 
        let isRequestPending = false; 

        const overlay          = document.getElementById('snn-ai-overlay');
        const closeModalButton = document.getElementById('snn-ai-close-button');
        const actionsContainer = document.getElementById('snn-ai-actions-container');
        const promptTextarea   = document.getElementById('snn-ai-prompt-textarea');
        const submitButton     = document.getElementById('snn-ai-submit');
        const spinner          = document.getElementById('snn-ai-spinner');
        const responseDiv      = document.getElementById('snn-ai-response');
        const copyButton       = document.getElementById('snn-ai-copy');
        const applyButton      = document.getElementById('snn-ai-apply');

        const editorTypes = {
            'textarea': {
                selector: '[data-control="textarea"]',
                getContent: function(element) { 
                    const textarea = element.querySelector('textarea');
                    return textarea ? textarea.value : '';
                },
                setContent: function(element, content) { 
                    const textarea = element.querySelector('textarea');
                    if (textarea) {
                        textarea.value = content; // This overwrites the content
                        textarea.dispatchEvent(new Event('input', { bubbles: true }));
                        highlightElement(textarea);
                    }
                }
            },
            'richtext': {
                selector: '[data-control="editor"]',
                getContent: function(element) { 
                    const iframe = element.querySelector('iframe');
                    if (iframe && iframe.contentDocument) {
                        const tinymceEl = iframe.contentDocument.getElementById('tinymce');
                        return tinymceEl ? tinymceEl.innerHTML : '';
                    }
                    return '';
                },
                setContent: function(element, content) { 
                    const iframe = element.querySelector('iframe');
                    if (iframe && iframe.contentDocument) {
                        const tinymceEl = iframe.contentDocument.getElementById('tinymce');
                        if (tinymceEl) {
                            if (typeof tinymce !== 'undefined' && tinymce.get(tinymceEl.id)) { // Check if editor instance exists
                                tinymce.get(tinymceEl.id).setContent(content); // This overwrites the content
                                tinymce.get(tinymceEl.id).fire('change');
                            } else { 
                                tinymceEl.innerHTML = content; // This overwrites the content
                                const event = new Event('input', { bubbles: true });
                                tinymceEl.dispatchEvent(event);
                            }
                            highlightElement(iframe); 
                        }
                    }
                }
            },
            'text': {
                selector: '[data-control="text"], [data-control="url"], [data-control="number"], [data-control="email"]',
                getContent: function(element) { 
                    const input = element.querySelector('input');
                    return input ? input.value : '';
                },
                setContent: function(element, content) { 
                    const input = element.querySelector('input');
                    if (input) {
                        input.value = content; // This overwrites the content
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                        highlightElement(input);
                    }
                }
            },
            'code': {
                selector: '[data-control="code"]',
                getContent: function(element) { 
                    const cmElement = element.querySelector('.CodeMirror');
                    if (cmElement && cmElement.CodeMirror) {
                        return cmElement.CodeMirror.getValue();
                    } else {
                        const textarea = element.querySelector('textarea');
                        return textarea ? textarea.value : '';
                    }
                },
                setContent: function(element, content) { 
                    const cmElement = element.querySelector('.CodeMirror');
                    if (cmElement && cmElement.CodeMirror) {
                        cmElement.CodeMirror.setValue(content); // This overwrites the content
                        cmElement.CodeMirror.refresh();
                        const textarea = cmElement.CodeMirror.getTextArea();
                        if (textarea) {
                            textarea.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                        highlightElement(cmElement);
                    } else {
                        const textarea = element.querySelector('textarea');
                        if (textarea) {
                            textarea.value = content; // This overwrites the content
                            textarea.dispatchEvent(new Event('input', { bubbles: true }));
                            highlightElement(textarea);
                        }
                    }
                }
            }
        };

        function highlightElement(el) { 
            if (!el) return;
            el.style.transition = 'background-color 0.1s ease-in-out, box-shadow 0.1s ease-in-out';
            el.style.backgroundColor = 'rgba(16, 163, 127, 0.2)';
            el.style.boxShadow = '0 0 5px rgba(16, 163, 127, 0.7)';
            setTimeout(() => {
                if (el) {
                    el.style.backgroundColor = '';
                    el.style.boxShadow = '';
                    setTimeout(() => {
                        if (el) el.style.transition = '';
                    }, 300);
                }
            }, 600);
        }

        function showModal() { 
            if (!overlay || !promptTextarea || !responseDiv || !copyButton || !applyButton || !spinner || !submitButton) return;
            overlay.style.display = 'flex';
            promptTextarea.value = '';
            responseDiv.textContent = '';
            responseDiv.style.display = 'none';
            copyButton.style.display = 'none';
            applyButton.style.display = 'none';
            spinner.style.display = 'none';
            submitButton.disabled = false;
            aiResponse = null;
            document.querySelectorAll('#snn-ai-actions-container .snn-ai-action-button.selected').forEach(b => b.classList.remove('selected'));
            selectedPresets = [];
            if (targetElement && targetType && editorTypes[targetType]) {
                const existingContent = editorTypes[targetType].getContent(targetElement).trim();
                if (existingContent) {
                    promptTextarea.value = existingContent + "\n\n---\n"; 
                    promptTextarea.focus();
                    promptTextarea.scrollTop = 0; 
                } else {
                    promptTextarea.focus();
                }
            }
            updateSubmitButtonState();
        }
        function hideModal() { 
            if (!overlay) return;
            overlay.style.display = 'none';
            targetElement = null;
            targetType = null;
            if (isRequestPending) { 
                isRequestPending = false; 
            }
        }

        if(closeModalButton) closeModalButton.addEventListener('click', hideModal);
        if(overlay) {
            overlay.addEventListener('click', e => {
                if (e.target === overlay) {
                    hideModal();
                }
            });
        }
        
        if(actionsContainer) {
            actionPresets.forEach(preset => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'snn-ai-action-button';
                btn.textContent = preset.name;
                btn.dataset.prompt = preset.prompt;
                btn.dataset.name = preset.name; 
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
        }


        if(promptTextarea) promptTextarea.addEventListener('input', updateSubmitButtonState);

        function updateSubmitButtonState() { 
            if (!submitButton || !promptTextarea) return;
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
            // Fallback if separator is missing but prompt starts with existing content
            if (fullPrompt.startsWith(existingContent.trim())) {
                return fullPrompt.substring(existingContent.trim().length).trim();
            }
            return fullPrompt; // If existing content not found at start, assume all is user typed
        }


        if(submitButton) submitButton.addEventListener('click', async () => { 
            if (isRequestPending) { 
                console.warn("SNN AI: Request already pending."); return; 
            }
            if (!config.apiKey) {
                console.error("SNN AI: API Key missing."); 
                if(responseDiv) {
                    responseDiv.textContent = "Error: API Key missing in settings.";
                    responseDiv.style.display = 'block';
                }
                return; 
            }
            if (!targetElement || !targetType) {
                console.error("SNN AI: Target element error."); 
                if(responseDiv) {
                    responseDiv.textContent = "Error: Target element not found.";
                    responseDiv.style.display = 'block';
                }
                return; 
            }

            isRequestPending = true;
            submitButton.disabled = true;
            if(spinner) spinner.style.display = 'block';
            if(responseDiv) responseDiv.style.display = 'none';
            if(copyButton) copyButton.style.display = 'none';
            if(applyButton) applyButton.style.display = 'none';
            aiResponse = null;

            const messages = [];
            if (config.systemPrompt) {
                messages.push({ role: 'system', content: config.systemPrompt });
            }
            
            let existingContent = '';
            if (targetElement && targetType && editorTypes[targetType]) {
                existingContent = editorTypes[targetType].getContent(targetElement).trim();
            }
            
            const fullPromptFromTextarea = promptTextarea.value.trim();
            const userTypedOnlyPrompt = extractUserTypedPrompt(fullPromptFromTextarea, existingContent);

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
            
            if (existingContent) {
                messages.push({ role: 'user', content: `The current content is:\n\`\`\`\n${existingContent}\n\`\`\`` });
                if (instructionForAI.trim() === "") { // Only existing content, no presets, no new typed instructions
                     instructionForAI = "Review the current content and provide an improved version.";
                }
                 messages.push({ role: 'user', content: `${instructionForAI}\n\nYour response must be *only* the new, fully revised version of the content, suitable for direct replacement of the original.` });
            } else { // Generating new content
                if (instructionForAI.trim() === "") {
                     instructionForAI = "Generate some relevant content.";
                }
                messages.push({ role: 'user', content: instructionForAI });
            }
            
            if (messages.length <=1 && !instructionForAI) { // System prompt only, no user instruction
                isRequestPending = false;
                if(spinner) spinner.style.display = 'none';
                updateSubmitButtonState();
                if(responseDiv) {
                    responseDiv.textContent = "Please select a preset or type an instruction.";
                    responseDiv.style.display = 'block';
                }
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
                    if(responseDiv) {
                        responseDiv.textContent = aiResponse;
                        responseDiv.style.display = 'block';
                    }
                    if(copyButton) copyButton.style.display = 'inline-block';
                    if(applyButton) applyButton.style.display = 'inline-block';
                } else {
                    throw new Error('<?php echo esc_js(__('Unexpected AI response format.', 'snn')); ?>');
                }
            } catch (error) {
                if(responseDiv) {
                    responseDiv.textContent = `Error: ${error.message}`;
                    responseDiv.style.display = 'block';
                }
                console.error("SNN AI Error:", error);
            } finally {
                isRequestPending = false;
                if(spinner) spinner.style.display = 'none';
                updateSubmitButtonState();
            }
        });

        if(copyButton) copyButton.addEventListener('click', () => { 
            if (aiResponse) {
                navigator.clipboard.writeText(aiResponse).then(() => {
                    copyButton.textContent = '<?php echo esc_js(__('Copied!', 'snn')); ?>';
                    setTimeout(() => {
                        copyButton.textContent = '<?php echo esc_js(__('Copy Text', 'snn')); ?>';
                    }, 1500);
                }).catch(err => {
                    console.error('SNN AI: Failed to copy text.', err);
                    const textArea = document.createElement("textarea");
                    textArea.value = aiResponse;
                    document.body.appendChild(textArea);
                    textArea.focus();
                    textArea.select();
                    try {
                        document.execCommand('copy');
                        copyButton.textContent = '<?php echo esc_js(__('Copied! (fallback)', 'snn')); ?>';
                         setTimeout(() => {
                            copyButton.textContent = '<?php echo esc_js(__('Copy Text', 'snn')); ?>';
                        }, 1500);
                    } catch (execErr) {
                        console.error('SNN AI: Fallback copy failed.', execErr);
                        if(responseDiv) {
                            responseDiv.textContent = 'Failed to copy automatically. Please copy manually.';
                            responseDiv.style.display = 'block';
                        }
                    }
                    document.body.removeChild(textArea);
                });
            }
        });

        if(applyButton) applyButton.addEventListener('click', () => { 
            if (aiResponse && targetElement && targetType && editorTypes[targetType]) {
                // The editorTypes[targetType].setContent function already overwrites the content.
                // If 'aiResponse' from the AI contains "OLD TEXT + NEW AI TEXT", that's what will be set.
                // The prompts are designed to make 'aiResponse' contain *only* the new text.
                editorTypes[targetType].setContent(targetElement, aiResponse);
                hideModal();
            } else {
                console.error('SNN AI: Could not apply. Missing response, target, or type.');
                if(responseDiv) {
                    responseDiv.textContent = 'Error: Could not apply changes. Target or response missing.';
                    responseDiv.style.display = 'block';
                }
            }
        });

        function addAiButtonTo(element, type) { 
            if (element.querySelector(':scope > .snn-ai-button, :scope > .control-label > .snn-ai-button')) {
                return; 
            }
            const aiButton = document.createElement('span');
            aiButton.className = 'snn-ai-button';
            aiButton.textContent = 'AI';
            aiButton.setAttribute('data-editor-type', type);
            aiButton.setAttribute('data-balloon', '<?php echo esc_js(__('Generate with AI', 'snn')); ?>');
            aiButton.setAttribute('data-balloon-pos', 'left');
            
            const controlLabel = element.querySelector('.control-label');
            if (controlLabel) {
                if (controlLabel.firstChild && controlLabel.firstChild.nodeName !== 'INPUT' && controlLabel.firstChild.nodeName !== 'TEXTAREA') {
                     controlLabel.insertBefore(aiButton, controlLabel.firstChild);
                } else {
                     controlLabel.appendChild(aiButton);
                }
            } else {
                if (element.firstChild) {
                    element.insertBefore(aiButton, element.firstChild);
                } else {
                    element.appendChild(aiButton);
                }
            }

            aiButton.addEventListener('click', e => {
                e.stopPropagation();
                e.preventDefault();
                targetElement = element; 
                targetType = type;
                showModal();
            });
        }

        const observer = new MutationObserver(mutations => { 
            mutations.forEach(mutation => {
                if (mutation.addedNodes && mutation.addedNodes.length) {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            Object.keys(editorTypes).forEach(type => {
                                const selector = editorTypes[type].selector;
                                if (node.matches && node.matches(selector)) {
                                    addAiButtonTo(node, type);
                                }
                                const elements = node.querySelectorAll ? node.querySelectorAll(selector) : [];
                                elements.forEach(el => addAiButtonTo(el, type));
                            });
                        }
                    });
                }
            });
        });
        observer.observe(document.body, { childList: true, subtree: true });

        Object.keys(editorTypes).forEach(type => { 
            document.querySelectorAll(editorTypes[type].selector).forEach(el => addAiButtonTo(el, type));
        });

        if(submitButton) updateSubmitButtonState(); 

        // --- NEW Bulk AI Modal Variables & Logic ---
        const bulkAiOverlay             = document.getElementById('snn-bulk-ai-overlay');
        const bulkAiCloseButton         = document.getElementById('snn-bulk-ai-close-button');
        const bulkAiFieldsContainer     = document.getElementById('snn-bulk-ai-fields-container');
        const bulkAiActionsContainer    = document.getElementById('snn-bulk-ai-actions-container');
        const bulkAiPromptTextarea      = document.getElementById('snn-bulk-ai-prompt-textarea');
        const bulkAiSubmitButton        = document.getElementById('snn-bulk-ai-submit');
        const bulkAiSpinner             = document.getElementById('snn-bulk-ai-spinner');
        const bulkAiResponseDisplay     = document.getElementById('snn-bulk-ai-response-display');
        const bulkAiApplyButton         = document.getElementById('snn-bulk-ai-apply');
        const bulkAiSelectAllButton     = document.getElementById('snn-bulk-ai-select-all'); // ADDED
        const bulkAiDeselectAllButton   = document.getElementById('snn-bulk-ai-deselect-all'); // ADDED


        let currentBulkElements = []; 
        let bulkSelectedPresets = [];
        let isBulkRequestPending = false;
        let bulkAiRawResponse = null; 
        let lastProcessedBulkElements = []; // To store elements sent to AI for applying changes correctly

        function populateBulkPresetButtons() {
            if (!bulkAiActionsContainer) return;
            bulkAiActionsContainer.innerHTML = ''; 
            actionPresets.forEach(preset => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'snn-ai-action-button'; 
                btn.textContent = preset.name;
                btn.dataset.prompt = preset.prompt;
                btn.dataset.name = preset.name;
                btn.addEventListener('click', () => {
                    const presetData = { name: preset.name, prompt: preset.prompt };
                    if (btn.classList.contains('selected')) {
                        btn.classList.remove('selected');
                        bulkSelectedPresets = bulkSelectedPresets.filter(p => p.name !== preset.name);
                    } else {
                        btn.classList.add('selected');
                        bulkSelectedPresets.push(presetData);
                    }
                    updateBulkSubmitButtonState();
                });
                bulkAiActionsContainer.appendChild(btn);
            });
        }


        function showBulkAiModal() {
            if (!bulkAiOverlay || !bulkAiFieldsContainer || !bulkAiPromptTextarea || !bulkAiResponseDisplay || !bulkAiApplyButton || !bulkAiSpinner) return;

            const bricksApp = document.querySelector("[data-v-app]");
            const bricksState = bricksApp ? bricksApp.__vue_app__?.config?.globalProperties?.$_state : null;

            if (!bricksState || !bricksState.content) {
                console.error("SNN Bulk AI: Bricks state or content not found.");
                if(bulkAiFieldsContainer) bulkAiFieldsContainer.innerHTML = '<p>Error: Could not load Bricks content. Is the Bricks editor active?</p>';
                bulkAiOverlay.style.display = 'flex'; 
                return;
            }
            
            bulkAiFieldsContainer.innerHTML = ''; 
            currentBulkElements = [];
            let fieldIndex = 0;

            function extractTextElements(elements) { 
                if (!elements || !Array.isArray(elements)) return;
                elements.forEach(el => {
                    if (el && el.settings && typeof el.settings.text === 'string' && el.settings.text.trim() !== '') {
                        const fieldGroup = document.createElement('div');
                        fieldGroup.className = 'snn-bulk-ai-field-group';
                        
                        const headerDiv = document.createElement('div');
                        headerDiv.className = 'snn-bulk-ai-field-header';

                        const checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.className = 'snn-bulk-ai-field-checkbox';
                        checkbox.id = `snn-bulk-checkbox-${el.id}-${fieldIndex}`;
                        checkbox.checked = true; 
                        checkbox.dataset.elementId = el.id;
                        
                        checkbox.addEventListener('change', updateBulkSubmitButtonState); // ADDED

                        const label = document.createElement('label');
                        label.htmlFor = checkbox.id;
                        label.textContent = `Field ${fieldIndex + 1} (${el.id}, ${el.name || 'N/A'})`;
                        
                        headerDiv.appendChild(checkbox);
                        headerDiv.appendChild(label);

                        const textarea = document.createElement('textarea');
                        textarea.value = el.settings.text;
                        
                        currentBulkElements.push({ 
                            id: el.id, 
                            name: el.name, 
                            originalText: el.settings.text, 
                            inputElement: textarea,
                            checkboxElement: checkbox 
                        });
                        
                        fieldGroup.appendChild(headerDiv);
                        fieldGroup.appendChild(textarea);
                        bulkAiFieldsContainer.appendChild(fieldGroup);
                        fieldIndex++;
                    }
                    if (el.children && el.children.length > 0) {
                        extractTextElements(el.children); 
                    }
                });
            }
            
            extractTextElements(bricksState.content);


            if (currentBulkElements.length === 0) {
                bulkAiFieldsContainer.innerHTML = '<p><?php echo esc_js(__('No text elements found on the page or all are empty.', 'snn')); ?></p>';
            }

            populateBulkPresetButtons(); 
            bulkAiPromptTextarea.value = '';
            bulkAiResponseDisplay.style.display = 'none';
            bulkAiResponseDisplay.textContent = '';
            bulkAiApplyButton.style.display = 'none';
            bulkAiSpinner.style.display = 'none';
            bulkSelectedPresets = []; 
            document.querySelectorAll('#snn-bulk-ai-actions-container .snn-ai-action-button.selected').forEach(b => b.classList.remove('selected'));


            bulkAiOverlay.style.display = 'flex';
            updateBulkSubmitButtonState(); // ADDED Call
        }

        function hideBulkAiModal() {
            if (!bulkAiOverlay) return;
            bulkAiOverlay.style.display = 'none';
            if (isBulkRequestPending) {
                isBulkRequestPending = false;
            }
            bulkAiRawResponse = null;
            lastProcessedBulkElements = [];
        }

        if(bulkAiCloseButton) bulkAiCloseButton.addEventListener('click', hideBulkAiModal);
        if(bulkAiOverlay) {
             bulkAiOverlay.addEventListener('click', e => {
                if (e.target === bulkAiOverlay) {
                    hideBulkAiModal();
                }
            });
        }

        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === '1') { 
                e.preventDefault();
                showBulkAiModal();
            }
        });
        
        function updateBulkSubmitButtonState() {
            if (!bulkAiSubmitButton || !bulkAiPromptTextarea || !bulkAiFieldsContainer) return;
            const hasGeneralPrompt = bulkAiPromptTextarea.value.trim().length > 0;
            const hasBulkPresets = bulkSelectedPresets.length > 0;
            
            const selectedFieldsCount = currentBulkElements.filter(el => el.checkboxElement && el.checkboxElement.checked).length;
            
            bulkAiSubmitButton.disabled = isBulkRequestPending || selectedFieldsCount === 0 || !(hasGeneralPrompt || hasBulkPresets);
        }

        if(bulkAiPromptTextarea) bulkAiPromptTextarea.addEventListener('input', updateBulkSubmitButtonState);
        
        // ADDED Event Listeners for Select All/Deselect All
        if (bulkAiSelectAllButton && bulkAiFieldsContainer) {
            bulkAiSelectAllButton.addEventListener('click', () => {
                const checkboxes = bulkAiFieldsContainer.querySelectorAll('.snn-bulk-ai-field-checkbox');
                checkboxes.forEach(cb => cb.checked = true);
                updateBulkSubmitButtonState();
            });
        }

        if (bulkAiDeselectAllButton && bulkAiFieldsContainer) {
            bulkAiDeselectAllButton.addEventListener('click', () => {
                const checkboxes = bulkAiFieldsContainer.querySelectorAll('.snn-bulk-ai-field-checkbox');
                checkboxes.forEach(cb => cb.checked = false);
                updateBulkSubmitButtonState();
            });
        }
        // END of ADDED Event Listeners
        
        if(bulkAiSubmitButton) bulkAiSubmitButton.addEventListener('click', async () => {
            if (isBulkRequestPending) { console.warn("SNN Bulk AI: Request already pending."); return; }
            if (!config.apiKey) { 
                console.error("SNN Bulk AI: API Key missing."); 
                if(bulkAiResponseDisplay) {
                    bulkAiResponseDisplay.textContent = "Error: API Key missing in settings.";
                    bulkAiResponseDisplay.style.display = 'block';
                }
                return; 
            }

            lastProcessedBulkElements = currentBulkElements.filter(el => el.checkboxElement && el.checkboxElement.checked); // Ensured el.checkboxElement exists

            if (lastProcessedBulkElements.length === 0) {
                console.warn("SNN Bulk AI: No elements selected to process.");
                 if(bulkAiResponseDisplay) {
                    bulkAiResponseDisplay.textContent = "<?php echo esc_js(__('Please select at least one field to regenerate.', 'snn')); ?>";
                    bulkAiResponseDisplay.style.display = 'block';
                }
                return;
            }

            isBulkRequestPending = true;
            bulkAiSubmitButton.disabled = true;
            if(bulkAiSpinner) bulkAiSpinner.style.display = 'block';
            if(bulkAiResponseDisplay) {
                bulkAiResponseDisplay.style.display = 'none';
                bulkAiResponseDisplay.textContent = '';
            }
            if(bulkAiApplyButton) bulkAiApplyButton.style.display = 'none';
            bulkAiRawResponse = null;

            const textsToSend = lastProcessedBulkElements.map(el => `"${el.inputElement.value.replace(/"/g, '\\"')}"`).join(" || ");

            const messages = [];
            if (config.systemPrompt) {
                messages.push({ role: 'system', content: config.systemPrompt });
            }

            let userInstruction = `You will be given a series of text segments separated by " || ".
The current text segments (${lastProcessedBulkElements.length} segments) are:
${textsToSend}

Your task is to regenerate each segment according to the following general instructions and any specific instructions provided.
Ensure your response is also a series of text segments separated by " || ", with the exact same number of segments as the input (${lastProcessedBulkElements.length} segments expected).
Do not add any extra explanations or text outside of the "||" separated segments.
`;

            if (bulkSelectedPresets.length > 0) {
                userInstruction += "\nApply the following general actions to each segment:\n";
                bulkSelectedPresets.forEach(p => {
                    userInstruction += `- ${p.prompt}\n`;
                });
            }

            const generalTypedPrompt = bulkAiPromptTextarea.value.trim();
            if (generalTypedPrompt) {
                userInstruction += `\nAdditional general instructions for all segments: ${generalTypedPrompt}`;
            }
            
            messages.push({ role: 'user', content: userInstruction });

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
                    }
                    throw new Error(errorMsg);
                }

                const data = await fetchResponse.json();
                if (data.choices && data.choices.length && data.choices[0].message && data.choices[0].message.content) {
                    bulkAiRawResponse = data.choices[0].message.content.trim();
                    if(bulkAiResponseDisplay) {
                        bulkAiResponseDisplay.textContent = "AI Response (Preview - texts separated by ||):\n\n" + bulkAiRawResponse;
                        bulkAiResponseDisplay.style.display = 'block';
                    }
                    
                    const newTexts = bulkAiRawResponse.split(" || ");
                    if (newTexts.length === lastProcessedBulkElements.length) {
                        lastProcessedBulkElements.forEach((bulkEl, index) => {
                            let cleanedText = newTexts[index].trim();
                            if (cleanedText.startsWith('"') && cleanedText.endsWith('"')) {
                                cleanedText = cleanedText.substring(1, cleanedText.length - 1);
                            }
                            bulkEl.inputElement.value = cleanedText.replace(/\\"/g, '"'); 
                        });
                        if(bulkAiApplyButton) bulkAiApplyButton.style.display = 'inline-block';
                    } else {
                         if(bulkAiResponseDisplay) bulkAiResponseDisplay.textContent += "\n\nError: AI returned a different number of segments than expected. Cannot apply automatically. Expected " + lastProcessedBulkElements.length + ", got " + newTexts.length + ".";
                         console.error("SNN Bulk AI: Segment count mismatch.", "Expected:", lastProcessedBulkElements.length, "Got:", newTexts.length, "Raw:", bulkAiRawResponse);
                    }

                } else {
                    throw new Error('<?php echo esc_js(__('Unexpected AI response format for bulk operation.', 'snn')); ?>');
                }

            } catch (error) {
                if(bulkAiResponseDisplay) {
                    bulkAiResponseDisplay.textContent = `Error: ${error.message}`;
                    bulkAiResponseDisplay.style.display = 'block';
                }
                console.error("SNN Bulk AI Error:", error);
            } finally {
                isBulkRequestPending = false;
                if(bulkAiSpinner) bulkAiSpinner.style.display = 'none';
                updateBulkSubmitButtonState();
            }
        });

        if(bulkAiApplyButton) bulkAiApplyButton.addEventListener('click', () => {
            if (!bulkAiRawResponse || lastProcessedBulkElements.length === 0) {
                console.error("SNN Bulk AI: No AI response or processed elements to apply.");
                if(bulkAiResponseDisplay) {
                    bulkAiResponseDisplay.textContent = "Error: No AI response or processed elements available to apply.";
                    bulkAiResponseDisplay.style.display = 'block';
                }
                return;
            }

            const newTexts = bulkAiRawResponse.split(" || ");
            if (newTexts.length !== lastProcessedBulkElements.length) {
                console.error("SNN Bulk AI: Mismatch in text segments count. Cannot apply.");
                 if(bulkAiResponseDisplay) {
                    bulkAiResponseDisplay.textContent = "Error: Mismatch in text segments. Cannot apply. Expected " + lastProcessedBulkElements.length + ", got " + newTexts.length + ".";
                    bulkAiResponseDisplay.style.display = 'block';
                }
                return;
            }

            let appliedCount = 0;
            lastProcessedBulkElements.forEach((bulkEl, index) => {
                const elementId = bulkEl.id;
                // 'textToApply' is the AI-generated content for this segment,
                // already updated in bulkEl.inputElement.value by the bulk submit logic.
                // This content should be *only* the new AI-generated text for the segment
                // if the AI followed instructions.
                let textToApply = bulkEl.inputElement.value; 

                try {
                    if (window.bricks && window.bricks.elementManager && typeof window.bricks.elementManager.updateElementSetting === 'function') {
                        // This function overwrites the 'text' setting of the Bricks element.
                        window.bricks.elementManager.updateElementSetting(elementId, 'text', textToApply);
                        appliedCount++;
                        const brickElement = document.getElementById(`bricks-element-${elementId}`);
                        if (brickElement) highlightElement(brickElement);

                    } else {
                         console.warn(`SNN Bulk AI: Bricks elementManager not available for element ID ${elementId}. Update might not be reactive.`);
                         const bricksApp = document.querySelector("[data-v-app]");
                         const bricksState = bricksApp ? bricksApp.__vue_app__?.config?.globalProperties?.$_state : null;
                         if (bricksState && bricksState.content) {
                             const findAndApply = (elements) => {
                                 for (let el of elements) {
                                     if (el.id === elementId) {
                                         el.settings.text = textToApply; // Overwrite
                                         return true;
                                     }
                                     if (el.children && findAndApply(el.children)) return true;
                                 }
                                 return false;
                             }
                             findAndApply(bricksState.content);
                         }
                    }
                } catch (e) {
                    console.error(`SNN Bulk AI: Error applying text to element ${elementId}:`, e);
                }
            });
            
            if (window.bricks && typeof bricks.forceContentUpdate === 'function') { 
                 bricks.forceContentUpdate(); 
            }
            // Consider if a save action is needed:
            // if (window.bricks && typeof bricks.builder.save === 'function') {
            //      bricks.builder.save(); 
            // }

            hideBulkAiModal();
            console.log(`SNN Bulk AI: Applied changes to ${appliedCount} elements.`);
            // You might want a more user-facing notification here. For example:
            // if (typeof bricks !== 'undefined' && bricks.notify) {
            //      bricks.notify(`Successfully applied AI changes to ${appliedCount} elements.`, 'success');
            // }
        });

    });
    </script>
    <?php
}
add_action('wp_footer', 'snn_add_ai_script_to_footer', 99);