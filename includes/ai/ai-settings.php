<?php
/**
 * SNN AI Settings
 * 
 * File: ai-settings.php
 *
 * Purpose: This file manages the administrative settings for the AI features. It is responsible for creating the
 * "AI Settings" submenu within the WordPress admin dashboard, registering all necessary settings with the
 * WordPress Settings API, and rendering the settings page itself. This includes the HTML form for all options
 * like API keys, provider selection, model names, and the repeater field for custom action prompts. It also
 * contains the client-side JavaScript necessary for the settings page to function, such as toggling setting
 * visibility based on the selected provider and handling the dynamic "Action Prompts" repeater.
 *
 * ---
 *
 * This file is part of a 3-file system:
 *
 * 1. ai-settings.php (This file): Handles the backend WordPress admin settings UI and options saving.
 * - Key Functions: snn_add_ai_settings_submenu(), snn_register_ai_settings(), snn_render_ai_settings().
 * - It provides the user-configured values that the other files will use.
 *
 * 2. ai-api.php: A helper file that prepares the necessary configuration for making API calls.
 * - Key Functions: snn_get_ai_api_config().
 * - It reads the options saved by this settings file (e.g., 'snn_ai_provider', 'snn_openai_api_key') and
 * determines the correct API endpoint, key, and model to be used by the frontend overlay. This abstracts
 * the logic away from the overlay itself.
 *
 * 3. ai-overlay.php: Manages the frontend user interface that appears inside the Bricks builder.
 * - Key Functions: snn_add_ai_script_to_footer().
 * - It injects the "AI" button into builder controls, displays the AI assistant modal (both single and bulk),
 * and contains the primary client-side JavaScript for interacting with the AI. It makes the final `fetch`
 * request to the AI provider using the configuration prepared by `ai-api.php`.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

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
    register_setting('snn_ai_settings_group', 'snn_openrouter_image_model');
    register_setting('snn_ai_settings_group', 'snn_system_prompt');
    register_setting('snn_ai_settings_group', 'snn_ai_action_presets', [
        'type' => 'array',
        'default' => [],
    ]);

    // 1. Register new settings for custom provider
    register_setting('snn_ai_settings_group', 'snn_custom_api_key');
    register_setting('snn_ai_settings_group', 'snn_custom_api_endpoint');
    register_setting('snn_ai_settings_group', 'snn_custom_model');

    // 2. Register multimodal configuration settings
    register_setting('snn_ai_settings_group', 'snn_ai_image_aspect_ratio', [
        'type' => 'string',
        'default' => '16:9',
    ]);
    register_setting('snn_ai_settings_group', 'snn_ai_image_size', [
        'type' => 'string',
        'default' => '1K',
    ]);
}
add_action('admin_init', 'snn_register_ai_settings');

function snn_render_ai_settings() {
    $ai_enabled           = get_option('snn_ai_enabled', 'no');
    $ai_provider          = get_option('snn_ai_provider', 'openrouter');
    $openai_api_key       = get_option('snn_openai_api_key', '');
    $openai_model         = get_option('snn_openai_model', 'google/gemini-2.5-flash-lite');
    $openrouter_api_key   = get_option('snn_openrouter_api_key', '');
    $openrouter_model     = get_option('snn_openrouter_model', '');
    $openrouter_image_model = get_option('snn_openrouter_image_model', '');
    $system_prompt        = get_option(
        'snn_system_prompt',
        'You are a helpful assistant that helps with content creation or manipulation. You work inside a wordpress visual builder. User usually changes a website content. Keep the content length as similar the existing content when you are editing or follow the users instructions accordingly. Only respond with the needed content and nothing else always!'
    );

    // Multimodal configuration settings
    $image_aspect_ratio = get_option('snn_ai_image_aspect_ratio', '16:9');
    $image_size         = get_option('snn_ai_image_size', '1K');

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
        ['name' => 'SEO',      'prompt' => 'Generate SEO-optimized content.'],
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
                            <option value="openrouter" <?php selected($ai_provider, 'openrouter'); ?>>OpenRouter</option>
                            <option value="openai" <?php selected($ai_provider, 'openai'); ?>>OpenAI (Will Be Deprecated and Removed Soon)</option>
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
                            <div id="openai-selected-model-features" class="selected-model-features" style="margin-top: 10px; padding: 10px; border: 1px solid #ccc; background-color: #f9f9f9; display: none;">
                                <strong><?php esc_html_e('Selected Model Features:', 'snn'); ?></strong>
                                <ul style="list-style-type: disc; margin-left: 20px;"></ul>
                            </div>
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
                            <div id="openrouter-model-capabilities" class="model-capabilities-tags" style="margin-top: 10px; display: none;">
                                <div class="capabilities-tags" style="margin-top: 5px;"></div>
                            </div>
                            <div id="openrouter-selected-model-features" class="selected-model-features" style="margin-top: 10px; padding: 10px; border: 1px solid #ccc; background-color: #f9f9f9; display: none; max-width: 410px; height: 220px; overflow: auto;">
                                <strong><?php esc_html_e('Selected Model Features:', 'snn'); ?></strong>
                                <ul style="list-style-type: disc; margin-left: 20px;"></ul>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="snn_openrouter_image_model"><?php esc_html_e('OpenRouter Image Model', 'snn'); ?></label>
                        </th>
                        <td>
                            <input
                                type="text"
                                name="snn_openrouter_image_model"
                                id="snn_openrouter_image_model"
                                class="regular-text"
                                value="<?php echo esc_attr($openrouter_image_model); ?>"
                                placeholder="<?php esc_attr_e('Search for image model...', 'snn'); ?>"
                                list="openrouter-image-models"
                                autocomplete="off"
                            >
                            <datalist id="openrouter-image-models">
                                <option value=""><?php esc_html_e('Loading image models...', 'snn'); ?></option>
                            </datalist>
                            <p class="description">
                                <?php esc_html_e('Select an OpenRouter model with image output capabilities. Start typing to search.', 'snn'); ?>
                            </p>
                            <div id="openrouter-image-model-capabilities" class="model-capabilities-tags" style="margin-top: 10px; display: none;">
                                <div class="capabilities-tags" style="margin-top: 5px;"></div>
                            </div>
                            <div id="openrouter-image-selected-model-features" class="selected-model-features" style="margin-top: 10px; padding: 10px; border: 1px solid #ccc; background-color: #f9f9f9; display: none; max-width: 410px; height: 220px; overflow: auto;">
                                <strong><?php esc_html_e('Selected Image Model Features:', 'snn'); ?></strong>
                                <ul style="list-style-type: disc; margin-left: 20px;"></ul>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="snn_ai_image_aspect_ratio"><?php esc_html_e('Image Generation Settings', 'snn'); ?></label>
                        </th>
                        <td>
                            <label for="snn_ai_image_aspect_ratio" style="display: inline-block; min-width: 100px;"><?php esc_html_e('Aspect Ratio:', 'snn'); ?></label>
                            <select name="snn_ai_image_aspect_ratio" id="snn_ai_image_aspect_ratio" style="width: 150px;">
                                <option value="1:1" <?php selected($image_aspect_ratio, '1:1'); ?>>1:1 (1024×1024)</option>
                                <option value="2:3" <?php selected($image_aspect_ratio, '2:3'); ?>>2:3 (832×1248)</option>
                                <option value="3:2" <?php selected($image_aspect_ratio, '3:2'); ?>>3:2 (1248×832)</option>
                                <option value="3:4" <?php selected($image_aspect_ratio, '3:4'); ?>>3:4 (864×1184)</option>
                                <option value="4:3" <?php selected($image_aspect_ratio, '4:3'); ?>>4:3 (1184×864)</option>
                                <option value="4:5" <?php selected($image_aspect_ratio, '4:5'); ?>>4:5 (896×1152)</option>
                                <option value="5:4" <?php selected($image_aspect_ratio, '5:4'); ?>>5:4 (1152×896)</option>
                                <option value="9:16" <?php selected($image_aspect_ratio, '9:16'); ?>>9:16 (768×1344)</option>
                                <option value="16:9" <?php selected($image_aspect_ratio, '16:9'); ?>>16:9 (1344×768)</option>
                                <option value="21:9" <?php selected($image_aspect_ratio, '21:9'); ?>>21:9 (1536×672)</option>
                            </select>
                            <br><br>
                            <label for="snn_ai_image_size" style="display: inline-block; min-width: 100px;"><?php esc_html_e('Image Size:', 'snn'); ?></label>
                            <select name="snn_ai_image_size" id="snn_ai_image_size" style="width: 150px;">
                                <option value="1K" <?php selected($image_size, '1K'); ?>>1K (Standard)</option>
                                <option value="2K" <?php selected($image_size, '2K'); ?>>2K (Higher Resolution)</option>
                                <option value="4K" <?php selected($image_size, '4K'); ?>>4K (Highest Resolution)</option>
                            </select>
                            <p class="description">
                                <?php esc_html_e('Default settings for image generation. Applied when generating images with compatible models. Note: Image size options are currently only supported by Gemini models.', 'snn'); ?>
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

            <h2><?php esc_html_e('Prompt Presets', 'snn'); ?></h2>
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

            <div id="snn-ai-import-export-container" style="margin-top: 20px;">
                <button type="button" class="button" id="snn-ai-export-button"><?php esc_html_e('Export Presets', 'snn'); ?></button>
                <button type="button" class="button" id="snn-ai-import-button" style="margin-left: 10px;"><?php esc_html_e('Import Presets', 'snn'); ?></button>

                <div id="snn-ai-export-area" style="display: none; margin-top: 10px;">
                    <h3><?php esc_html_e('Exported Presets', 'snn'); ?></h3>
                    <p><?php esc_html_e('Copy the text below to save your presets.', 'snn'); ?></p>
                    <textarea id="snn-ai-export-textarea" rows="8" style="width: 100%; max-width: 660px;" readonly></textarea>
                </div>

                <div id="snn-ai-import-area" style="display: none; margin-top: 10px;">
                    <h3><?php esc_html_e('Import Presets', 'snn'); ?></h3>
                    <p><?php esc_html_e('Paste your previously exported presets into the text area below and click "Import". This will add the imported presets to your current list, skipping any duplicates.', 'snn'); ?></p>
                    <textarea id="snn-ai-import-textarea" rows="8" style="width: 100%; max-width: 660px;"></textarea>
                    <p>
                        <button type="button" class="button button-primary" id="snn-ai-import-apply-button"><?php esc_html_e('Import', 'snn'); ?></button>
                        <span id="snn-ai-import-status" style="margin-left: 10px; font-style: italic;"></span>
                    </p>
                </div>
            </div>

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
            #openrouter-settings #snn_openrouter_model,
            #openrouter-settings #snn_openrouter_image_model {
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
            .selected-model-features ul {
                padding-left: 20px;
                margin-top: 5px;
            }
            .selected-model-features li {
                margin-bottom: 3px;
            }
            .capabilities-tags {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
            }
            .capability-tag {
                display: inline-block;
                padding: 4px 10px;
                background-color: #0073aa;
                color: #fff;
                border-radius: 3px;
                font-size: 12px;
                font-weight: 500;
            }
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

            // Model feature display elements
            const openaiModelInput = document.getElementById('snn_openai_model');
            const openaiFeaturesDiv = document.getElementById('openai-selected-model-features');
            const openaiFeaturesList = openaiFeaturesDiv ? openaiFeaturesDiv.querySelector('ul') : null;

            const openrouterModelInput = document.getElementById('snn_openrouter_model');
            const openrouterFeaturesDiv = document.getElementById('openrouter-selected-model-features');
            const openrouterFeaturesList = openrouterFeaturesDiv ? openrouterFeaturesDiv.querySelector('ul') : null;
            const openrouterCapabilitiesDiv = document.getElementById('openrouter-model-capabilities');
            const openrouterCapabilitiesContainer = openrouterCapabilitiesDiv ? openrouterCapabilitiesDiv.querySelector('.capabilities-tags') : null;

            const openrouterImageModelInput = document.getElementById('snn_openrouter_image_model');
            const openrouterImageFeaturesDiv = document.getElementById('openrouter-image-selected-model-features');
            const openrouterImageFeaturesList = openrouterImageFeaturesDiv ? openrouterImageFeaturesDiv.querySelector('ul') : null;
            const openrouterImageCapabilitiesDiv = document.getElementById('openrouter-image-model-capabilities');
            const openrouterImageCapabilitiesContainer = openrouterImageCapabilitiesDiv ? openrouterImageCapabilitiesDiv.querySelector('.capabilities-tags') : null;

            let allOpenAiModels = [];
            let allOpenRouterModels = [];
            let allOpenRouterImageModels = [];

            function toggleSettingsVisibility() {
                const isEnabled = enableCheckbox.checked;
                openaiSettingsDiv.style.display = 'none';
                openrouterSettingsDiv.style.display = 'none';
                customSettingsDiv.style.display = 'none';

                // Hide feature divs when provider changes or AI is disabled
                if (openaiFeaturesDiv) openaiFeaturesDiv.style.display = 'none';
                if (openrouterFeaturesDiv) openrouterFeaturesDiv.style.display = 'none';
                if (openrouterCapabilitiesDiv) openrouterCapabilitiesDiv.style.display = 'none';
                if (openrouterImageFeaturesDiv) openrouterImageFeaturesDiv.style.display = 'none';
                if (openrouterImageCapabilitiesDiv) openrouterImageCapabilitiesDiv.style.display = 'none';

                if (isEnabled) {
                    if (providerSelect.value === 'openai') {
                        openaiSettingsDiv.style.display = 'block';
                        fetchOpenAiModels();
                    } else if (providerSelect.value === 'openrouter') {
                        openrouterSettingsDiv.style.display = 'block';
                        fetchOpenRouterModels();
                        fetchOpenRouterImageModels();
                    } else if (providerSelect.value === 'custom') {
                        customSettingsDiv.style.display = 'block';
                    }
                }
            }

            /**
             * Formats a value for display, handling booleans, numbers (including timestamps), and null/undefined/empty strings.
             * @param {*} value The value to format.
             * @returns {string} The formatted value.
             */
            function formatValue(value) {
                if (typeof value === 'boolean') {
                    return value ? 'Yes' : 'No';
                }
                if (typeof value === 'number' && !isNaN(value) && value !== null) {
                    // Check if it's a Unix timestamp (seconds or milliseconds)
                    if (String(value).length === 10 || String(value).length === 13) {
                        return new Date(value * (String(value).length === 10 ? 1000 : 1)).toLocaleString();
                    }
                    return value.toString();
                }
                if (value === null || value === undefined || value === '') {
                    return 'N/A';
                }
                return value;
            }

            /**
             * Displays all features of a given model object in a list, handling nested objects and arrays.
             * @param {HTMLElement} featuresListElement The <ul> element to append features to.
             * @param {Object} modelData The model object containing features.
             */
            function displayFeatures(featuresListElement, modelData) {
                if (!featuresListElement || !modelData) return;
                featuresListElement.innerHTML = ''; // Clear previous features

                for (const key in modelData) {
                    if (Object.prototype.hasOwnProperty.call(modelData, key)) {
                        const value = modelData[key];
                        const li = document.createElement('li');

                        if (Array.isArray(value)) {
                            // Handle arrays (e.g., supported_parameters, permissions)
                            if (value.length > 0) {
                                li.innerHTML = `<strong>${key}:</strong>`;
                                const nestedUl = document.createElement('ul');
                                value.forEach((item, index) => {
                                    const nestedLi = document.createElement('li');
                                    if (typeof item === 'object' && item !== null) {
                                        // If array contains objects (like OpenAI permissions)
                                        nestedLi.innerHTML = `<strong>Item ${index + 1}:</strong>`;
                                        const deeperNestedUl = document.createElement('ul');
                                        for (const itemKey in item) {
                                            if (Object.prototype.hasOwnProperty.call(item, itemKey)) {
                                                const deeperLi = document.createElement('li');
                                                deeperLi.textContent = `${itemKey}: ${formatValue(item[itemKey])}`;
                                                deeperNestedUl.appendChild(deeperLi);
                                            }
                                        }
                                        nestedLi.appendChild(deeperNestedUl);
                                    } else {
                                        // If array contains primitive values (like supported_parameters)
                                        nestedLi.textContent = formatValue(item);
                                    }
                                    nestedUl.appendChild(nestedLi);
                                });
                                li.appendChild(nestedUl);
                            } else {
                                li.textContent = `${key}: N/A (empty list)`;
                            }
                        } else if (typeof value === 'object' && value !== null) {
                            // Handle nested objects (e.g., architecture, pricing, top_provider)
                            li.innerHTML = `<strong>${key}:</strong>`;
                            const nestedUl = document.createElement('ul');
                            for (const nestedKey in value) {
                                if (Object.prototype.hasOwnProperty.call(value, nestedKey)) {
                                    const nestedLi = document.createElement('li');
                                    nestedLi.textContent = `${nestedKey}: ${formatValue(value[nestedKey])}`;
                                    nestedUl.appendChild(nestedLi);
                                }
                            }
                            li.appendChild(nestedUl);
                        } else {
                            // Handle primitive values
                            li.textContent = `${key}: ${formatValue(value)}`;
                        }
                        featuresListElement.appendChild(li);
                    }
                }
            }

            function displayOpenAiModelFeatures(modelId) {
                if (!openaiFeaturesList) return;
                openaiFeaturesDiv.style.display = 'none';

                const selectedModel = allOpenAiModels.find(model => model.id === modelId);

                if (selectedModel) {
                    openaiFeaturesDiv.style.display = 'block';
                    displayFeatures(openaiFeaturesList, selectedModel);
                }
            }

            /**
             * Displays architecture capabilities as simple tags
             * @param {HTMLElement} container The container element for tags
             * @param {Object} architecture The architecture object from model data
             */
            function displayCapabilityTags(container, architecture) {
                if (!container || !architecture) return;
                container.innerHTML = ''; // Clear previous tags

                // Create tags for each architecture property
                const createTag = (label, value) => {
                    const tag = document.createElement('span');
                    tag.className = 'capability-tag';
                    if (Array.isArray(value)) {
                        tag.textContent = `${label}: ${value.join(', ')}`;
                    } else {
                        tag.textContent = `${label}: ${value || 'N/A'}`;
                    }
                    return tag;
                };

                // Add tags for key architecture properties
                if (architecture.modality) {
                    container.appendChild(createTag('Modality', architecture.modality));
                }
                if (architecture.input_modalities && architecture.input_modalities.length > 0) {
                    container.appendChild(createTag('Input', architecture.input_modalities));
                }
                if (architecture.output_modalities && architecture.output_modalities.length > 0) {
                    container.appendChild(createTag('Output', architecture.output_modalities));
                }
                if (architecture.tokenizer) {
                    container.appendChild(createTag('Tokenizer', architecture.tokenizer));
                }
                if (architecture.instruct_type) {
                    container.appendChild(createTag('Instruct Type', architecture.instruct_type));
                }
            }

            function displayOpenRouterModelFeatures(modelId) {
                if (!openrouterFeaturesList) return;
                openrouterFeaturesDiv.style.display = 'none';
                if (openrouterCapabilitiesDiv) openrouterCapabilitiesDiv.style.display = 'none';

                const selectedModel = allOpenRouterModels.find(model => model.id === modelId);

                if (selectedModel) {
                    // Display capability tags if architecture data exists
                    if (selectedModel.architecture && openrouterCapabilitiesContainer) {
                        openrouterCapabilitiesDiv.style.display = 'block';
                        displayCapabilityTags(openrouterCapabilitiesContainer, selectedModel.architecture);
                    }

                    // Display full features list
                    openrouterFeaturesDiv.style.display = 'block';
                    displayFeatures(openrouterFeaturesList, selectedModel);
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
                        allOpenRouterModels = data.data; // Store all models
                        dataListEl.innerHTML = '';
                        data.data.forEach(model => {
                            const option = document.createElement('option');
                            option.value = model.id;
                            let priceInfo = '';
                            if (model.pricing && model.pricing.prompt && model.pricing.completion) {
                                const promptCost = (model.pricing.prompt.cost * 1000000).toFixed(6); // Per 1M tokens
                                const completionCost = (model.pricing.completion.cost * 1000000).toFixed(6); // Per 1M tokens
                                priceInfo = ` | Prompt: $${promptCost}/M, Comp: $${completionCost}/M`;
                            }
                            const providerInfo = model.top_provider ? ` | Provider: ${model.top_provider.name}` : '';
                            option.text = `${model.name} (${model.id}) | ${model.context_length} tokens${priceInfo}${providerInfo}`;
                            dataListEl.appendChild(option);
                        });
                        // Display features for the currently selected model if any
                        displayOpenRouterModelFeatures(openrouterModelInput.value);
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

            function displayOpenRouterImageModelFeatures(modelId) {
                if (!openrouterImageFeaturesList) return;
                openrouterImageFeaturesDiv.style.display = 'none';
                if (openrouterImageCapabilitiesDiv) openrouterImageCapabilitiesDiv.style.display = 'none';

                const selectedModel = allOpenRouterImageModels.find(model => model.id === modelId);

                if (selectedModel) {
                    // Display capability tags if architecture data exists
                    if (selectedModel.architecture && openrouterImageCapabilitiesContainer) {
                        openrouterImageCapabilitiesDiv.style.display = 'block';
                        displayCapabilityTags(openrouterImageCapabilitiesContainer, selectedModel.architecture);
                    }

                    // Display full features list
                    openrouterImageFeaturesDiv.style.display = 'block';
                    displayFeatures(openrouterImageFeaturesList, selectedModel);
                }
            }

            function fetchOpenRouterImageModels() {
                const dataListEl = document.getElementById('openrouter-image-models');
                if (!dataListEl) return;
                const openrouterKeyEl = document.getElementById('snn_openrouter_api_key');
                const openrouterKey = openrouterKeyEl ? openrouterKeyEl.value.trim() : '';
                if (!openrouterKey) {
                    dataListEl.innerHTML = '<option value=""><?php esc_html_e('OpenRouter key missing. Please add your key first.', 'snn'); ?></option>';
                    return;
                }
                dataListEl.innerHTML = '<option value=""><?php esc_html_e('Loading image models...', 'snn'); ?></option>';
                let slowTimeout = setTimeout(function(){
                    dataListEl.innerHTML = '<option value=""><?php esc_html_e('Still loading image models... (this is taking longer than usual)', 'snn'); ?></option>';
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
                        // Filter models that have "image" in their output_modalities
                        const imageModels = data.data.filter(model => {
                            return model.architecture && 
                                   model.architecture.output_modalities && 
                                   Array.isArray(model.architecture.output_modalities) &&
                                   model.architecture.output_modalities.includes('image');
                        });
                        
                        allOpenRouterImageModels = imageModels; // Store filtered image models
                        dataListEl.innerHTML = '';
                        
                        if (imageModels.length === 0) {
                            dataListEl.innerHTML = '<option value=""><?php esc_html_e('No image models found.', 'snn'); ?></option>';
                            return;
                        }
                        
                        imageModels.forEach(model => {
                            const option = document.createElement('option');
                            option.value = model.id;
                            let priceInfo = '';
                            if (model.pricing && model.pricing.image) {
                                const imageCost = (parseFloat(model.pricing.image) * 1000000).toFixed(6);
                                priceInfo = ` | Image: $${imageCost}/M`;
                            } else if (model.pricing && model.pricing.prompt && model.pricing.completion) {
                                const promptCost = (parseFloat(model.pricing.prompt) * 1000000).toFixed(6);
                                const completionCost = (parseFloat(model.pricing.completion) * 1000000).toFixed(6);
                                priceInfo = ` | Prompt: $${promptCost}/M, Comp: $${completionCost}/M`;
                            }
                            const providerInfo = model.top_provider ? ` | Provider: ${model.top_provider.name}` : '';
                            const modalityInfo = model.architecture && model.architecture.modality ? ` | ${model.architecture.modality}` : '';
                            option.text = `${model.name} (${model.id})${modalityInfo}${priceInfo}${providerInfo}`;
                            dataListEl.appendChild(option);
                        });
                        // Display features for the currently selected image model if any
                        displayOpenRouterImageModelFeatures(openrouterImageModelInput.value);
                    } else {
                        dataListEl.innerHTML = '<option value=""><?php esc_html_e('No models found.', 'snn'); ?></option>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching OpenRouter image models:', error);
                    dataListEl.innerHTML = '<option value=""><?php esc_html_e('Error loading image models.', 'snn'); ?></option>';
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
                        allOpenAiModels = miniModels.concat(otherModels); // Store all models
                        dataListEl.innerHTML = '';
                        allOpenAiModels.forEach(model => {
                            if (model.id) {
                                const option = document.createElement('option');
                                option.value = model.id;
                                const ownedBy = model.owned_by ? ` (by ${model.owned_by})` : '';
                                option.text = `${model.id}${ownedBy}`;
                                dataListEl.appendChild(option);
                            }
                        });
                        // Display features for the currently selected model if any
                        displayOpenAiModelFeatures(openaiModelInput.value);
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

            // Add event listeners for model input changes to update features
            if (openaiModelInput) {
                openaiModelInput.addEventListener('input', (e) => {
                    displayOpenAiModelFeatures(e.target.value);
                });
            }
            if (openrouterModelInput) {
                openrouterModelInput.addEventListener('input', (e) => {
                    displayOpenRouterModelFeatures(e.target.value);
                });
            }
            if (openrouterImageModelInput) {
                openrouterImageModelInput.addEventListener('input', (e) => {
                    displayOpenRouterImageModelFeatures(e.target.value);
                });
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

            function createPresetRow(preset, index) {
                const row = document.createElement('tr');
                row.className = 'snn-ai-action-preset-row';
                row.setAttribute('draggable', 'true');
                row.innerHTML = `
                    <td class="snn-ai-drag-handle" style="padding:0; width:30px; text-align:center; cursor:move; font-size:30px">⬍</td>
                    <td style="padding:2px">
                        <input
                            type="text"
                            name="snn_ai_action_presets[${index}][name]"
                            value="${preset.name.replace(/"/g, '&quot;')}"
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
                return row;
            }

            if (addPresetButton && presetsTableBody) {
                addPresetButton.addEventListener('click', function() {
                    const newIndex = presetsTableBody.querySelectorAll('tr.snn-ai-action-preset-row').length;
                    const newPreset = { name: '', prompt: '' };
                    const row = createPresetRow(newPreset, newIndex);
                    presetsTableBody.appendChild(row);
                    updatePresetIndices();
                });
            }

            if (resetPresetButton && presetsTableBody) {
                resetPresetButton.addEventListener('click', function() {
                    if (confirm('<?php echo esc_js(__('Are you sure you want to reset all presets to their defaults? This cannot be undone.', 'snn')); ?>')) {
                        presetsTableBody.innerHTML = '';
                        const defaultPresets = <?php echo json_encode($default_presets); ?>;
                        defaultPresets.forEach((preset, index) => {
                            const row = createPresetRow(preset, index);
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
                    const target = e.target.closest('tr.snn-ai-action-preset-row');
                    if (target) {
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

            // Import/Export Logic
            const exportButton = document.getElementById('snn-ai-export-button');
            const importButton = document.getElementById('snn-ai-import-button');
            const exportArea = document.getElementById('snn-ai-export-area');
            const importArea = document.getElementById('snn-ai-import-area');
            const exportTextarea = document.getElementById('snn-ai-export-textarea');
            const importTextarea = document.getElementById('snn-ai-import-textarea');
            const importApplyButton = document.getElementById('snn-ai-import-apply-button');
            const importStatus = document.getElementById('snn-ai-import-status');

            if (exportButton) {
                exportButton.addEventListener('click', () => {
                    const presets = [];
                    presetsTableBody.querySelectorAll('tr.snn-ai-action-preset-row').forEach(row => {
                        const name = row.querySelector('.preset-name-input').value.trim();
                        const prompt = row.querySelector('.preset-prompt-input').value.trim();
                        if (name && prompt) {
                            presets.push({ name, prompt });
                        }
                    });
                    exportTextarea.value = JSON.stringify(presets, null, 2);
                    exportArea.style.display = 'block';
                    importArea.style.display = 'none';
                    exportTextarea.select();
                });
            }

            if (importButton) {
                importButton.addEventListener('click', () => {
                    importArea.style.display = 'block';
                    exportArea.style.display = 'none';
                    importStatus.textContent = '';
                    importTextarea.value = '';
                });
            }

            if (importApplyButton) {
                importApplyButton.addEventListener('click', () => {
                    const jsonString = importTextarea.value.trim();
                    if (!jsonString) {
                        importStatus.textContent = '<?php echo esc_js(__('Textarea is empty.', 'snn')); ?>';
                        return;
                    }
                    try {
                        const importedPresets = JSON.parse(jsonString);
                        if (!Array.isArray(importedPresets)) {
                            throw new Error('<?php echo esc_js(__('Data is not a valid array.', 'snn')); ?>');
                        }

                        const existingPresets = new Set();
                        presetsTableBody.querySelectorAll('tr.snn-ai-action-preset-row').forEach(row => {
                            const name = row.querySelector('.preset-name-input').value.trim().toLowerCase();
                            const prompt = row.querySelector('.preset-prompt-input').value.trim().toLowerCase();
                            existingPresets.add(`${name}|||${prompt}`);
                        });

                        let addedCount = 0;
                        let skippedCount = 0;
                        importedPresets.forEach(preset => {
                            if (preset && typeof preset.name === 'string' && typeof preset.prompt === 'string') {
                                const newName = preset.name.trim();
                                const newPrompt = preset.prompt.trim();
                                const presetKey = `${newName.toLowerCase()}|||${newPrompt.toLowerCase()}`;
                                if (newName && newPrompt && !existingPresets.has(presetKey)) {
                                    const newIndex = presetsTableBody.querySelectorAll('tr.snn-ai-action-preset-row').length;
                                    const row = createPresetRow({ name: newName, prompt: newPrompt }, newIndex);
                                    presetsTableBody.appendChild(row);
                                    existingPresets.add(presetKey);
                                    addedCount++;
                                } else {
                                    skippedCount++;
                                }
                            }
                        });

                        updatePresetIndices();
                        importStatus.textContent = `<?php echo esc_js(__('Import complete!', 'snn')); ?> ${addedCount} <?php echo esc_js(__('presets added', 'snn')); ?>, ${skippedCount} <?php echo esc_js(__('duplicates skipped.', 'snn')); ?>`;

                    } catch (error) {
                        importStatus.textContent = `<?php echo esc_js(__('Invalid JSON format.', 'snn')); ?> ${error.message}`;
                        console.error("Import error:", error);
                    }
                });
            }
        });
        </script>
    </div>
    <?php
}
