<?php
/**
 * SNN AI SEO Generation
 *
 * File: ai-seo-generation.php
 *
 * Purpose: This file handles AI-powered SEO title and description generation for posts, pages,
 * custom post types, and taxonomies. It integrates with the existing AI infrastructure
 * (ai-api.php) and provides a unified overlay interface for preview and customization.
 *
 * Features:
 * - Unified AI overlay for single posts, bulk operations, and taxonomy terms
 * - Preview before saving with regeneration capabilities
 * - Action preset selection and custom prompt support
 * - Context display (shows what AI is reading)
 * - Modern WordPress-styled UI with smooth UX
 */

if (!defined('ABSPATH')) exit;

/**
 * Check if SEO AI features are enabled
 */
function snn_seo_ai_is_enabled() {
    $seo_enabled = get_option('snn_seo_enabled', false);
    $seo_ai_enabled = get_option('snn_seo_ai_enabled', false);
    $ai_enabled = get_option('snn_ai_enabled', 'no');
    
    return $seo_enabled && $seo_ai_enabled && $ai_enabled === 'yes';
}

/**
 * Enqueue AI SEO scripts and styles for admin
 */
function snn_seo_ai_enqueue_admin_scripts($hook) {
    if (!snn_seo_ai_is_enabled()) {
        return;
    }

    // Only load on post edit screens and post list screens
    $allowed_hooks = ['post.php', 'post-new.php', 'edit.php', 'term.php', 'edit-tags.php'];
    if (!in_array($hook, $allowed_hooks)) {
        return;
    }

    // Get AI config
    if (!function_exists('snn_get_ai_api_config')) {
        return;
    }
    
    $config = snn_get_ai_api_config();
    
    if (empty($config['apiKey']) || empty($config['apiEndpoint'])) {
        return;
    }

    // Pass config to JavaScript (inject directly into page)
    add_action('admin_footer', function() use ($config) {
        ?>
        <script>
        window.snnSeoAiConfig = <?php echo json_encode(array(
            'apiKey' => $config['apiKey'],
            'model' => $config['model'],
            'apiEndpoint' => $config['apiEndpoint'],
            'systemPrompt' => $config['systemPrompt'],
            'actionPresets' => $config['actionPresets'],
            'responseFormat' => $config['responseFormat'],
            'nonce' => wp_create_nonce('snn_seo_ai_nonce'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'strings' => array(
                'generating' => __('Generating...', 'snn'),
                'regenerating' => __('Regenerating...', 'snn'),
                'error' => __('Error generating content', 'snn'),
                'success' => __('Saved successfully', 'snn'),
                'preview' => __('Preview & Edit', 'snn'),
                'save' => __('Save', 'snn'),
                'cancel' => __('Cancel', 'snn'),
                'generate' => __('Generate', 'snn'),
                'regenerate' => __('Regenerate', 'snn'),
                'selectPreset' => __('Select Action Preset', 'snn'),
                'customPrompt' => __('Custom Prompt (Optional)', 'snn'),
                'contextInfo' => __('AI is reading:', 'snn'),
                'seoTitle' => __('SEO Title', 'snn'),
                'seoDescription' => __('SEO Description', 'snn'),
                'processing' => __('Processing item', 'snn'),
                'of' => __('of', 'snn'),
            )
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        </script>
        <?php
    }, 5);
    
    // Inject the unified overlay HTML
    add_action('admin_footer', 'snn_seo_ai_render_overlay');
}
add_action('admin_enqueue_scripts', 'snn_seo_ai_enqueue_admin_scripts');

/**
 * Render the unified AI SEO overlay
 */
function snn_seo_ai_render_overlay() {
    if (!snn_seo_ai_is_enabled()) {
        return;
    }
    
    $config = snn_get_ai_api_config();
    $action_presets = $config['actionPresets'];
    ?>
    <div id="snn-seo-ai-overlay" style="display: none;">
        <div class="snn-seo-ai-overlay-backdrop"></div>
        <div class="snn-seo-ai-overlay-container">
            <div class="snn-seo-ai-overlay-header">
                <h2><?php _e('AI SEO Generation', 'snn'); ?> <span id="snn-seo-item-count" style="font-size: 14px; font-weight: normal; color: #646970;"></span></h2>
                <button class="snn-seo-ai-close">&times;</button>
            </div>
            
            <div class="snn-seo-ai-overlay-body">
                
                <!-- Action Presets -->
                <div class="snn-seo-ai-presets">
                    <label><?php _e('Action Preset:', 'snn'); ?></label>
                    <div class="snn-seo-ai-preset-buttons">
                        <?php foreach ($action_presets as $preset): ?>
                            <button type="button" class="snn-preset-btn" data-prompt="<?php echo esc_attr($preset['prompt']); ?>">
                                <?php echo esc_html($preset['name']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Generate Options -->
                <div class="snn-seo-ai-options">
                    <label><?php _e('Generate:', 'snn'); ?></label>
                    <div class="snn-seo-ai-checkboxes">
                        <label class="snn-checkbox-label">
                            <input type="checkbox" id="snn-generate-title" checked />
                            <?php _e('SEO Title', 'snn'); ?>
                        </label>
                        <label class="snn-checkbox-label">
                            <input type="checkbox" id="snn-generate-description" checked />
                            <?php _e('SEO Description', 'snn'); ?>
                        </label>
                    </div>
                </div>
                
                <!-- Custom Prompt -->
                <div class="snn-seo-ai-custom">
                    <label for="snn-seo-custom-prompt"><?php _e('Custom Prompt (Optional):', 'snn'); ?></label>
                    <textarea id="snn-seo-custom-prompt" rows="3" placeholder="<?php _e('Add additional instructions...', 'snn'); ?>"></textarea>
                </div>
                
                <!-- Results -->
                <div class="snn-seo-ai-results" style="display: none;">
                    <div class="snn-seo-result-item">
                        <label><?php _e('SEO Title:', 'snn'); ?></label>
                        <input type="text" id="snn-seo-result-title" class="snn-result-input" />
                        <span class="snn-char-count"><span id="snn-title-count">0</span>/60</span>
                    </div>
                    
                    <div class="snn-seo-result-item">
                        <label><?php _e('SEO Description:', 'snn'); ?></label>
                        <textarea id="snn-seo-result-description" class="snn-result-input" rows="3"></textarea>
                        <span class="snn-char-count"><span id="snn-desc-count">0</span>/160</span>
                    </div>
                </div>
                
                <!-- Bulk Progress -->
                <div class="snn-seo-ai-bulk-progress" style="display: none;">
                    <div class="snn-progress-text">
                        <span id="snn-bulk-current">0</span> / <span id="snn-bulk-total">0</span> <?php _e('items generated', 'snn'); ?>
                    </div>
                    <div class="snn-progress-bar">
                        <div class="snn-progress-fill"></div>
                    </div>
                </div>
                
                <!-- Bulk Results Preview -->
                <div class="snn-seo-ai-bulk-results" style="display: none;">
                    <div id="snn-bulk-results-container"></div>
                </div>
            </div>
            
            <div class="snn-seo-ai-overlay-footer">
                <button type="button" class="button button-large snn-seo-cancel"><?php _e('Cancel', 'snn'); ?></button>
                <button type="button" class="button button-large snn-seo-generate"><?php _e('Generate', 'snn'); ?></button>
                <button type="button" class="button button-large snn-seo-regenerate" style="display: none;"><?php _e('Regenerate', 'snn'); ?></button>
                <button type="button" class="button button-large snn-seo-save" style="display: none;"><?php _e('Save', 'snn'); ?></button>
            </div>
        </div>
    </div>
    
    <style>
    #snn-seo-ai-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 160000;
    }
    
    .snn-seo-ai-overlay-backdrop {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        animation: snnFadeIn 0.2s ease;
    }
    
    .snn-seo-ai-overlay-container {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: #fff;
        border-radius: 4px;
        box-shadow: 0 5px 25px rgba(0,0,0,0.3);
        width: 90%;
        max-width: 800px;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        animation: snnSlideIn 0.3s ease;
    }
    
    .snn-seo-ai-overlay-header {
        padding: 20px 24px;
        border-bottom: 1px solid #dcdcde;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .snn-seo-ai-overlay-header h2 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
    }
    
    .snn-seo-ai-close {
        background: none;
        border: none;
        font-size: 28px;
        line-height: 1;
        cursor: pointer;
        color: #646970;
        padding: 0;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 3px;
    }
    
    .snn-seo-ai-close:hover {
        background: #f0f0f1;
        color: #000;
    }
    
    .snn-seo-ai-overlay-body {
        padding: 24px;
        overflow-y: auto;
        flex: 1;
    }
    
    .snn-seo-ai-presets {
        margin-bottom: 20px;
    }
    
    .snn-seo-ai-presets label {
        display: block;
        margin-bottom: 10px;
        font-weight: 600;
        font-size: 14px;
    }
    
    .snn-seo-ai-options {
        margin-bottom: 20px;
    }
    
    .snn-seo-ai-options > label {
        display: block;
        margin-bottom: 10px;
        font-weight: 600;
        font-size: 14px;
    }
    
    .snn-seo-ai-checkboxes {
        display: flex;
        gap: 6px;
    }
    
    .snn-checkbox-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        cursor: pointer;
        padding: 4px 6px;
        border-radius: 4px;
        transition: all 0.2s;
    }
    
    .snn-checkbox-label:hover {
        background: #e5f2ff;
    }
    
    .snn-checkbox-label input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
        margin: 0;
    }
    
    .snn-seo-ai-preset-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }
    
    .snn-preset-btn {
        padding: 4px 6px;
        background: #fff;
        border: 1px solid #2271b1;
        color: #2271b1;
        border-radius: 3px;
        cursor: pointer;
        font-size: 13px;
        transition: all 0.2s;
    }
    
    .snn-preset-btn:hover {
        background: #f0f6fc;
    }
    
    .snn-preset-btn.active {
        background: #2271b1;
        color: #fff;
    }
    
    .snn-seo-ai-custom {
        margin-bottom: 20px;
    }
    
    .snn-seo-ai-custom label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        font-size: 14px;
    }
    
    #snn-seo-custom-prompt {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #8c8f94;
        border-radius: 3px;
        font-size: 13px;
        resize: vertical;
    }
    
    #snn-seo-custom-prompt:focus {
        border-color: #2271b1;
        box-shadow: 0 0 0 1px #2271b1;
        outline: none;
    }
    
    .snn-seo-ai-results {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 2px solid #2271b1;
    }
    
    .snn-seo-result-item {
        margin-bottom: 20px;
        position: relative;
    }
    
    .snn-seo-result-item label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        font-size: 14px;
    }
    
    .snn-result-input {
        width: 100%;
        padding: 10px 12px;
        border: 2px solid #2271b1;
        border-radius: 3px;
        font-size: 14px;
        background: #f0f6fc;
    }
    
    .snn-result-input:focus {
        background: #fff;
        outline: none;
        box-shadow: 0 0 0 2px rgba(34, 113, 177, 0.3);
    }
    
    .snn-char-count {
        position: absolute;
        right: 12px;
        top: 38px;
        font-size: 11px;
        color: #646970;
        background: #fff;
        padding: 2px 6px;
        border-radius: 2px;
    }
    
    .snn-seo-result-item:last-child .snn-char-count {
        top: auto;
        bottom: 12px;
    }
    
    .snn-seo-ai-bulk-progress {
        margin-top: 20px;
        padding: 20px;
        background: #f6f7f7;
        border-radius: 4px;
    }
    
    .snn-progress-text {
        margin-bottom: 10px;
        font-size: 14px;
        font-weight: 600;
        text-align: center;
    }
    
    .snn-progress-bar {
        height: 24px;
        background: #dcdcde;
        border-radius: 12px;
        overflow: hidden;
    }
    
    .snn-progress-fill {
        height: 100%;
        background: #2271b1;
        width: 0%;
        transition: width 0.3s ease;
    }
    
    .snn-seo-ai-bulk-results {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 2px solid #2271b1;
    }
    
    .snn-bulk-item {
        background: #f0f6fc;
        border: 2px solid #2271b1;
        border-radius: 4px;
        padding: 16px;
        margin-bottom: 16px;
    }
    
    .snn-bulk-item-header {
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 12px;
        color: #1d2327;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .snn-bulk-item-title {
        flex: 1;
    }
    
    .snn-bulk-item-regenerate {
        padding: 4px 12px;
        background: #2271b1;
        color: #fff;
        border: none;
        border-radius: 3px;
        cursor: pointer;
        font-size: 12px;
        transition: background 0.2s;
    }
    
    .snn-bulk-item-regenerate:hover {
        background: #135e96;
    }
    
    .snn-bulk-item-regenerate:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .snn-bulk-field {
        margin-bottom: 12px;
    }
    
    .snn-bulk-field:last-child {
        margin-bottom: 0;
    }
    
    .snn-bulk-field label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 4px;
        color: #646970;
    }
    
    .snn-bulk-field input,
    .snn-bulk-field textarea {
        width: 100%;
        padding: 8px 10px;
        border: 1px solid #8c8f94;
        border-radius: 3px;
        font-size: 13px;
        background: #fff;
    }
    
    .snn-bulk-field input:focus,
    .snn-bulk-field textarea:focus {
        border-color: #2271b1;
        outline: none;
        box-shadow: 0 0 0 1px #2271b1;
    }
    
    .snn-bulk-field textarea {
        resize: vertical;
        min-height: 60px;
    }
    
    .snn-bulk-field-count {
        float: right;
        font-size: 11px;
        color: #646970;
    }
    
    .snn-seo-ai-overlay-footer {
        padding: 16px 24px;
        border-top: 1px solid #dcdcde;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }
    
    .snn-seo-ai-overlay-footer .button {
        min-width: 100px;
    }
    
    .snn-seo-ai-overlay-footer .button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    @keyframes snnFadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes snnSlideIn {
        from {
            opacity: 0;
            transform: translate(-50%, -45%);
        }
        to {
            opacity: 1;
            transform: translate(-50%, -50%);
        }
    }
    </style>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof snnSeoAiConfig === 'undefined') return;

        const config = snnSeoAiConfig;
        const overlay = document.getElementById('snn-seo-ai-overlay');
        const itemCount = document.getElementById('snn-seo-item-count');
        const presetButtons = document.querySelectorAll('.snn-preset-btn');
        const customPrompt = document.getElementById('snn-seo-custom-prompt');
        const generateTitle = document.getElementById('snn-generate-title');
        const generateDesc = document.getElementById('snn-generate-description');
        const results = document.querySelector('.snn-seo-ai-results');
        const resultTitle = document.getElementById('snn-seo-result-title');
        const resultDesc = document.getElementById('snn-seo-result-description');
        const titleCountEl = document.getElementById('snn-title-count');
        const descCountEl = document.getElementById('snn-desc-count');
        const generateBtn = document.querySelector('.snn-seo-generate');
        const regenerateBtn = document.querySelector('.snn-seo-regenerate');
        const saveBtn = document.querySelector('.snn-seo-save');
        const cancelBtn = document.querySelector('.snn-seo-cancel');
        const bulkProgress = document.querySelector('.snn-seo-ai-bulk-progress');
        const bulkResults = document.querySelector('.snn-seo-ai-bulk-results');
        const bulkResultsContainer = document.getElementById('snn-bulk-results-container');

        let currentMode = 'single'; // 'single', 'bulk', 'term'
        let currentData = {};
        let selectedPresets = [];
        let bulkGeneratedData = []; // Store generated results for bulk

        // Helper functions for show/hide
        function showElement(el) {
            if (el) el.style.display = '';
        }

        function hideElement(el) {
            if (el) el.style.display = 'none';
        }

        function fadeIn(el, duration = 200) {
            if (!el) return;
            el.style.display = '';
            el.style.opacity = '0';
            let start = null;

            function animate(timestamp) {
                if (!start) start = timestamp;
                const progress = (timestamp - start) / duration;
                el.style.opacity = Math.min(progress, 1);
                if (progress < 1) requestAnimationFrame(animate);
            }
            requestAnimationFrame(animate);
        }

        function fadeOut(el, duration = 200) {
            if (!el) return;
            el.style.opacity = '1';
            let start = null;

            function animate(timestamp) {
                if (!start) start = timestamp;
                const progress = (timestamp - start) / duration;
                el.style.opacity = Math.max(1 - progress, 0);
                if (progress < 1) {
                    requestAnimationFrame(animate);
                } else {
                    el.style.display = 'none';
                }
            }
            requestAnimationFrame(animate);
        }

        // Character counter
        function updateCharCount() {
            titleCountEl.textContent = resultTitle.value.length;
            descCountEl.textContent = resultDesc.value.length;
        }

        resultTitle.addEventListener('input', updateCharCount);
        resultDesc.addEventListener('input', updateCharCount);

        // Preset selection (allow multiple)
        presetButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                this.classList.toggle('active');
                const prompt = this.dataset.prompt;

                if (this.classList.contains('active')) {
                    if (!selectedPresets.includes(prompt)) {
                        selectedPresets.push(prompt);
                    }
                } else {
                    selectedPresets = selectedPresets.filter(p => p !== prompt);
                }
            });
        });

        // Open overlay
        window.snnSeoAiOpenOverlay = function(mode, data) {
            currentMode = mode;
            currentData = data;
            selectedPresets = [];
            bulkGeneratedData = [];

            // Reset UI
            presetButtons.forEach(btn => btn.classList.remove('active'));
            customPrompt.value = '';
            hideElement(results);
            hideElement(bulkProgress);
            hideElement(bulkResults);
            bulkResultsContainer.innerHTML = '';
            showElement(generateBtn);
            generateBtn.disabled = false;
            generateBtn.textContent = config.strings.generate;
            hideElement(regenerateBtn);
            hideElement(saveBtn);

            // Set item count in header
            let countText = '';
            if (mode === 'single') {
                countText = '';
            } else if (mode === 'bulk') {
                countText = `(${data.items.length} items selected)`;
            } else if (mode === 'term') {
                countText = `(${data.name})`;
            }
            itemCount.textContent = countText;

            fadeIn(overlay, 200);
        };

        // Close overlay
        function closeOverlay() {
            fadeOut(overlay, 200);
        }

        const closeButtons = document.querySelectorAll('.snn-seo-ai-close, .snn-seo-cancel');
        closeButtons.forEach(btn => {
            btn.addEventListener('click', closeOverlay);
        });

        const backdrop = document.querySelector('.snn-seo-ai-overlay-backdrop');
        if (backdrop) {
            backdrop.addEventListener('click', function(e) {
                if (e.target === this) closeOverlay();
            });
        }

        // Generate
        generateBtn.addEventListener('click', async function() {
            if (selectedPresets.length === 0 && !customPrompt.value.trim()) {
                return;
            }

            if (!generateTitle.checked && !generateDesc.checked) {
                alert('Please select at least one option to generate (Title or Description).');
                return;
            }

            generateBtn.disabled = true;
            generateBtn.textContent = config.strings.generating;

            try {
                if (currentMode === 'bulk') {
                    await processBulk();
                } else {
                    await generateSingle();
                }
            } catch (error) {
                console.error('Generation error:', error);
                alert(config.strings.error + ': ' + error.message);
                generateBtn.disabled = false;
                generateBtn.textContent = config.strings.generate;
            }
        });

        // Regenerate
        regenerateBtn.addEventListener('click', async function() {
            if (selectedPresets.length === 0 && !customPrompt.value.trim()) {
                return;
            }

            if (!generateTitle.checked && !generateDesc.checked) {
                alert('Please select at least one option to generate (Title or Description).');
                return;
            }

            regenerateBtn.disabled = true;
            regenerateBtn.textContent = config.strings.regenerating;

            try {
                if (currentMode === 'bulk') {
                    await processBulk();
                } else {
                    await generateSingle();
                }
            } catch (error) {
                console.error('Regeneration error:', error);
                alert(config.strings.error + ': ' + error.message);
                regenerateBtn.disabled = false;
                regenerateBtn.textContent = config.strings.regenerate;
            }
        });

        // Save
        saveBtn.addEventListener('click', async function() {
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';

            try {
                if (currentMode === 'bulk') {
                    await saveBulk();
                } else if (currentMode === 'term') {
                    await saveTerm();
                } else {
                    await saveSingle();
                }

                closeOverlay();
                location.reload();
            } catch (error) {
                console.error('Save error:', error);
                saveBtn.disabled = false;
                saveBtn.textContent = config.strings.save;
            }
        });

        // Generate for single item or term
        async function generateSingle() {
            const prompt = buildPrompt();
            const result = await callAI(prompt);

            const titleItem = resultTitle.closest('.snn-seo-result-item');
            const descItem = resultDesc.closest('.snn-seo-result-item');

            if (generateTitle.checked) {
                resultTitle.value = result.title || '';
                showElement(titleItem);
            } else {
                hideElement(titleItem);
            }

            if (generateDesc.checked) {
                resultDesc.value = result.description || '';
                showElement(descItem);
            } else {
                hideElement(descItem);
            }

            updateCharCount();

            fadeIn(results);
            hideElement(generateBtn);
            showElement(regenerateBtn);
            regenerateBtn.disabled = false;
            regenerateBtn.textContent = config.strings.regenerate;
            showElement(saveBtn);
        }

        // Process bulk items - generate and preview
        async function processBulk() {
            hideElement(generateBtn);
            hideElement(regenerateBtn);
            showElement(bulkProgress);
            hideElement(bulkResults);
            bulkResultsContainer.innerHTML = '';

            const items = currentData.items;
            const total = items.length;
            document.getElementById('snn-bulk-total').textContent = total;

            bulkGeneratedData = [];

            for (let i = 0; i < items.length; i++) {
                const postId = items[i];
                document.getElementById('snn-bulk-current').textContent = i + 1;
                const progressFill = document.querySelector('.snn-progress-fill');
                if (progressFill) {
                    progressFill.style.width = ((i + 1) / total * 100) + '%';
                }

                try {
                    // Fetch post data using fetch API
                    const formData = new FormData();
                    formData.append('action', 'snn_seo_ai_get_post_data');
                    formData.append('post_id', postId);
                    formData.append('nonce', config.nonce);

                    const response = await fetch(config.ajaxUrl, {
                        method: 'POST',
                        body: formData
                    });

                    const postData = await response.json();

                    if (!postData.success) continue;

                    // Generate SEO
                    const prompt = buildPromptForData(postData.data);
                    const result = await callAI(prompt);

                    // Store generated data
                    bulkGeneratedData.push({
                        postId: postId,
                        postTitle: postData.data.title,
                        title: result.title,
                        description: result.description
                    });
                } catch (error) {
                    console.error('Error processing post ' + postId, error);
                }
            }

            // Show results preview
            hideElement(bulkProgress);
            renderBulkResults();
            fadeIn(bulkResults);
            showElement(regenerateBtn);
            regenerateBtn.disabled = false;
            regenerateBtn.textContent = config.strings.regenerate;
            showElement(saveBtn);
        }

        // Render bulk results for preview/edit
        function renderBulkResults() {
            bulkResultsContainer.innerHTML = '';

            const showTitle = generateTitle.checked;
            const showDesc = generateDesc.checked;

            bulkGeneratedData.forEach((item, index) => {
                let titleField = '';
                let descField = '';

                if (showTitle) {
                    titleField = `
                        <div class="snn-bulk-field">
                            <label>
                                ${config.strings.seoTitle}
                                <span class="snn-bulk-field-count">
                                    <span class="title-count">${item.title.length}</span>/60
                                </span>
                            </label>
                            <input type="text" class="bulk-title-input" value="${escapeHtml(item.title)}" data-index="${index}" />
                        </div>
                    `;
                }

                if (showDesc) {
                    descField = `
                        <div class="snn-bulk-field">
                            <label>
                                ${config.strings.seoDescription}
                                <span class="snn-bulk-field-count">
                                    <span class="desc-count">${item.description.length}</span>/160
                                </span>
                            </label>
                            <textarea class="bulk-desc-input" data-index="${index}">${escapeHtml(item.description)}</textarea>
                        </div>
                    `;
                }

                const itemHtml = `
                    <div class="snn-bulk-item" data-index="${index}">
                        <div class="snn-bulk-item-header">
                            <span class="snn-bulk-item-title">${escapeHtml(item.postTitle)}</span>
                            <button type="button" class="snn-bulk-item-regenerate" data-index="${index}">
                                ${config.strings.regenerate}
                            </button>
                        </div>
                        ${titleField}
                        ${descField}
                    </div>
                `;
                bulkResultsContainer.insertAdjacentHTML('beforeend', itemHtml);
            });

            // Bind input events for character counting and data update
            const titleInputs = document.querySelectorAll('.bulk-title-input');
            titleInputs.forEach(input => {
                input.addEventListener('input', function() {
                    const index = parseInt(this.dataset.index);
                    const value = this.value;
                    bulkGeneratedData[index].title = value;
                    const countEl = this.closest('.snn-bulk-field').querySelector('.title-count');
                    if (countEl) countEl.textContent = value.length;
                });
            });

            const descInputs = document.querySelectorAll('.bulk-desc-input');
            descInputs.forEach(input => {
                input.addEventListener('input', function() {
                    const index = parseInt(this.dataset.index);
                    const value = this.value;
                    bulkGeneratedData[index].description = value;
                    const countEl = this.closest('.snn-bulk-field').querySelector('.desc-count');
                    if (countEl) countEl.textContent = value.length;
                });
            });

            // Bind regenerate button for individual items
            const regenButtons = document.querySelectorAll('.snn-bulk-item-regenerate');
            regenButtons.forEach(button => {
                button.addEventListener('click', async function() {
                    const index = parseInt(this.dataset.index);
                    const btn = this;
                    const item = document.querySelector(`.snn-bulk-item[data-index="${index}"]`);

                    btn.disabled = true;
                    btn.textContent = config.strings.regenerating;

                    try {
                        const itemData = bulkGeneratedData[index];

                        // Fetch fresh post data
                        const formData = new FormData();
                        formData.append('action', 'snn_seo_ai_get_post_data');
                        formData.append('post_id', itemData.postId);
                        formData.append('nonce', config.nonce);

                        const response = await fetch(config.ajaxUrl, {
                            method: 'POST',
                            body: formData
                        });

                        const postData = await response.json();

                        if (postData.success) {
                            const prompt = buildPromptForData(postData.data);
                            const result = await callAI(prompt);

                            // Update stored data
                            bulkGeneratedData[index].title = result.title;
                            bulkGeneratedData[index].description = result.description;

                            // Update UI
                            const titleInput = item.querySelector('.bulk-title-input');
                            if (titleInput) {
                                titleInput.value = result.title;
                                titleInput.dispatchEvent(new Event('input'));
                            }

                            const descInput = item.querySelector('.bulk-desc-input');
                            if (descInput) {
                                descInput.value = result.description;
                                descInput.dispatchEvent(new Event('input'));
                            }
                        }
                    } catch (error) {
                        console.error('Regenerate error:', error);
                        alert(config.strings.error);
                    }

                    btn.disabled = false;
                    btn.textContent = config.strings.regenerate;
                });
            });
        }

        // Save bulk items
        async function saveBulk() {
            const total = bulkGeneratedData.length;
            let saved = 0;

            for (const item of bulkGeneratedData) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'snn_seo_ai_save_post');
                    formData.append('post_id', item.postId);
                    formData.append('nonce', config.nonce);

                    if (generateTitle.checked) {
                        formData.append('title', item.title);
                    }

                    if (generateDesc.checked) {
                        formData.append('description', item.description);
                    }

                    await fetch(config.ajaxUrl, {
                        method: 'POST',
                        body: formData
                    });
                    saved++;
                } catch (error) {
                    console.error('Error saving post ' + item.postId, error);
                }
            }

            if (saved < total) {
                alert(`Saved ${saved} of ${total} items. Some items failed to save.`);
            }
        }

        // Helper function to escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Build prompt
        function buildPrompt() {
            return buildPromptForData(currentData);
        }

        function buildPromptForData(data) {
            let basePrompt = selectedPresets.join(' ') || '';
            const customPromptValue = customPrompt.value.trim();

            if (customPromptValue) {
                basePrompt += (basePrompt ? ' ' : '') + customPromptValue;
            }

            const genTitle = generateTitle.checked;
            const genDesc = generateDesc.checked;

            let whatToGenerate = '';
            if (genTitle && genDesc) {
                whatToGenerate = 'Generate SEO title (max 60 chars) and description (max 160 chars)';
            } else if (genTitle) {
                whatToGenerate = 'Generate SEO title (max 60 chars)';
            } else if (genDesc) {
                whatToGenerate = 'Generate SEO description (max 160 chars)';
            }

            if (currentMode === 'term') {
                return `${basePrompt}\n\n${whatToGenerate} for this taxonomy term:\nTerm: ${data.name}\nDescription: ${data.description || 'N/A'}\n\nReturn JSON: {"title": "...", "description": "..."}`;
            } else {
                return `${basePrompt}\n\n${whatToGenerate} for this content:\nTitle: ${data.title}\nContent: ${(data.content || '').substring(0, 2000)}\n\nReturn JSON: {"title": "...", "description": "..."}`;
            }
        }
        
        // Call AI API
        async function callAI(prompt) {
            const requestBody = {
                model: config.model,
                messages: [
                    { role: 'system', content: config.systemPrompt },
                    { role: 'user', content: prompt }
                ],
                temperature: 0.7,
                max_tokens: 300
            };
            
            if (config.responseFormat && config.responseFormat.type) {
                requestBody.response_format = { type: 'json_object' };
            }
            
            const response = await fetch(config.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${config.apiKey}`
                },
                body: JSON.stringify(requestBody)
            });
            
            if (!response.ok) {
                throw new Error(`API request failed: ${response.status}`);
            }
            
            const data = await response.json();
            let content = data.choices[0].message.content;
            
            // Strip markdown code blocks if present (e.g., ```json ... ```)
            content = content.replace(/```json\s*/g, '').replace(/```/g, '').trim();
            
            // Try to parse JSON
            try {
                return JSON.parse(content);
            } catch (e) {
                // Fallback: extract title and description
                const lines = content.split('\n').filter(l => l.trim());
                return {
                    title: lines[0] || '',
                    description: lines[1] || ''
                };
            }
        }

        // Save single post
        async function saveSingle() {
            const formData = new FormData();
            formData.append('action', 'snn_seo_ai_save_post');
            formData.append('post_id', currentData.postId);
            formData.append('nonce', config.nonce);

            if (generateTitle.checked) {
                formData.append('title', resultTitle.value);
            }

            if (generateDesc.checked) {
                formData.append('description', resultDesc.value);
            }

            return fetch(config.ajaxUrl, {
                method: 'POST',
                body: formData
            });
        }

        // Save term
        async function saveTerm() {
            const formData = new FormData();
            formData.append('action', 'snn_seo_ai_save_term');
            formData.append('term_id', currentData.termId);
            formData.append('nonce', config.nonce);

            if (generateTitle.checked) {
                formData.append('title', resultTitle.value);
                // Also update the visible form field
                const termTitleField = document.getElementById('snn_seo_term_title');
                if (termTitleField) termTitleField.value = resultTitle.value;
            }

            if (generateDesc.checked) {
                formData.append('description', resultDesc.value);
                // Also update the visible form field
                const termDescField = document.getElementById('snn_seo_term_description');
                if (termDescField) termDescField.value = resultDesc.value;
            }

            return fetch(config.ajaxUrl, {
                method: 'POST',
                body: formData
            });
        }
    });
    </script>
    <?php
}

/**
 * Add AI generation button to SEO meta box
 */
function snn_seo_ai_meta_box_buttons($post) {
    if (!snn_seo_ai_is_enabled()) {
        return;
    }

    $post_type = get_post_type($post);
    $enabled_post_types = get_option('snn_seo_post_types_enabled', []);
    
    if (!isset($enabled_post_types[$post_type]) || !$enabled_post_types[$post_type]) {
        return;
    }

    // Get post content for context
    $post_content = $post->post_content;
    $post_title = $post->post_title;
    
    // Extract Bricks content if available
    if (function_exists('snn_seo_extract_bricks_content')) {
        $bricks_content = snn_seo_extract_bricks_content($post->ID);
        if (!empty($bricks_content)) {
            $post_content = $bricks_content;
        }
    }
    
    ?>
    <div style="margin-top: 15px;">
        <button type="button" class="button button-large" id="snn-seo-ai-generate-btn" >
            <?php _e('Generate with AI ✨', 'snn'); ?>
        </button>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const postContent = <?php echo json_encode(wp_strip_all_tags(substr($post_content, 0, 3000))); ?>;
        const postTitle = <?php echo json_encode($post_title); ?>;
        const postId = <?php echo $post->ID; ?>;

        const generateBtn = document.getElementById('snn-seo-ai-generate-btn');
        if (generateBtn) {
            generateBtn.addEventListener('click', function(e) {
                e.preventDefault();

                if (typeof window.snnSeoAiOpenOverlay === 'function') {
                    window.snnSeoAiOpenOverlay('single', {
                        postId: postId,
                        title: postTitle,
                        content: postContent
                    });
                }
            });
        }
    });
    </script>
    <?php
}

/**
 * Add bulk AI generation button to post list screens
 */
function snn_seo_ai_add_bulk_button() {
    if (!snn_seo_ai_is_enabled()) {
        return;
    }
    
    $screen = get_current_screen();
    if (!$screen || $screen->base !== 'edit') {
        return;
    }
    
    $enabled_post_types = get_option('snn_seo_post_types_enabled', []);
    if (!isset($enabled_post_types[$screen->post_type]) || !$enabled_post_types[$screen->post_type]) {
        return;
    }
    
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof snnSeoAiOpenOverlay === 'undefined') return;

        // Add bulk button container to actions bar
        const bulkActions = document.querySelector('.tablenav.top .bulkactions');
        if (bulkActions) {
            const container = document.createElement('div');
            container.id = 'snn-seo-bulk-container';
            container.style.cssText = 'display: inline-block; margin-left: 10px; vertical-align: top;';
            container.innerHTML = '<button type="button" class="button" id="snn-seo-bulk-ai-btn"><?php _e('Bulk AI SEO Generation ✨', 'snn'); ?></button>';
            bulkActions.insertAdjacentElement('afterend', container);

            const bulkBtn = document.getElementById('snn-seo-bulk-ai-btn');
            if (bulkBtn) {
                bulkBtn.addEventListener('click', function(e) {
                    e.preventDefault();

                    const checkedBoxes = document.querySelectorAll('tbody input[name="post[]"]:checked');

                    if (checkedBoxes.length === 0) {
                        // Remove any existing warning
                        const existingWarning = document.getElementById('snn-bulk-warning');
                        if (existingWarning) existingWarning.remove();

                        // Add warning text below button
                        const warning = document.createElement('div');
                        warning.id = 'snn-bulk-warning';
                        warning.style.cssText = 'color: #d63638; font-size: 12px; margin-top: 5px;';
                        warning.textContent = '<?php _e('Please select posts first.', 'snn'); ?>';
                        container.appendChild(warning);

                        // Remove warning after 3 seconds
                        setTimeout(function() {
                            warning.style.opacity = '1';
                            let start = null;
                            function fadeOut(timestamp) {
                                if (!start) start = timestamp;
                                const progress = (timestamp - start) / 300;
                                warning.style.opacity = Math.max(1 - progress, 0);
                                if (progress < 1) {
                                    requestAnimationFrame(fadeOut);
                                } else {
                                    warning.remove();
                                }
                            }
                            requestAnimationFrame(fadeOut);
                        }, 3000);

                        return;
                    }

                    // Remove any existing warning
                    const existingWarning = document.getElementById('snn-bulk-warning');
                    if (existingWarning) existingWarning.remove();

                    const postIds = Array.from(checkedBoxes).map(checkbox => parseInt(checkbox.value));

                    snnSeoAiOpenOverlay('bulk', {
                        items: postIds
                    });
                });
            }
        }
    });
    </script>
    <?php
}
add_action('admin_footer-edit.php', 'snn_seo_ai_add_bulk_button');

/**
 * AJAX: Get post data for generation
 */
function snn_seo_ai_get_post_data_handler() {
    check_ajax_referer('snn_seo_ai_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    
    if (!$post_id) {
        wp_send_json_error('Invalid post ID');
    }
    
    $post = get_post($post_id);
    if (!$post) {
        wp_send_json_error('Post not found');
    }
    
    // Get post content
    $content = $post->post_content;
    
    // Extract Bricks content if available
    if (function_exists('snn_seo_extract_bricks_content')) {
        $bricks_content = snn_seo_extract_bricks_content($post_id);
        if (!empty($bricks_content)) {
            $content = $bricks_content;
        }
    }
    
    wp_send_json_success(array(
        'postId' => $post_id,
        'title' => $post->post_title,
        'content' => wp_strip_all_tags(substr($content, 0, 3000))
    ));
}
add_action('wp_ajax_snn_seo_ai_get_post_data', 'snn_seo_ai_get_post_data_handler');

/**
 * AJAX: Save post SEO data
 */
function snn_seo_ai_save_post_handler() {
    check_ajax_referer('snn_seo_ai_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
    $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
    
    if (!$post_id) {
        wp_send_json_error('Invalid post ID');
    }
    
    if ($title) {
        update_post_meta($post_id, '_snn_seo_title', $title);
    }
    
    if ($description) {
        update_post_meta($post_id, '_snn_seo_description', $description);
    }
    
    wp_send_json_success();
}
add_action('wp_ajax_snn_seo_ai_save_post', 'snn_seo_ai_save_post_handler');

/**
 * AJAX: Save term SEO data
 */
function snn_seo_ai_save_term_handler() {
    check_ajax_referer('snn_seo_ai_nonce', 'nonce');
    
    if (!current_user_can('manage_categories')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    $term_id = isset($_POST['term_id']) ? intval($_POST['term_id']) : 0;
    $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
    $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
    
    if (!$term_id) {
        wp_send_json_error('Invalid term ID');
    }
    
    if ($title) {
        update_term_meta($term_id, '_snn_seo_title', $title);
    }
    
    if ($description) {
        update_term_meta($term_id, '_snn_seo_description', $description);
    }
    
    wp_send_json_success();
}
add_action('wp_ajax_snn_seo_ai_save_term', 'snn_seo_ai_save_term_handler');

/**
 * Add AI button to taxonomy term edit screen
 * Note: This only adds the AI generation button. The actual SEO fields are managed by seo.php
 * The AI overlay saves directly via AJAX (snn_seo_ai_save_term_handler)
 */
function snn_seo_ai_taxonomy_fields($term) {
    if (!snn_seo_ai_is_enabled()) {
        return;
    }

    $taxonomy = $term->taxonomy;
    $enabled_taxonomies = get_option('snn_seo_taxonomies_enabled', []);
    
    if (!isset($enabled_taxonomies[$taxonomy]) || !$enabled_taxonomies[$taxonomy]) {
        return;
    }

    ?>
    <tr class="form-field">
        <th scope="row">
            <label><?php _e('AI SEO Generation', 'snn'); ?></label>
        </th>
        <td>
            <button type="button" class="button button-large" id="snn-seo-ai-generate-term-btn" style="width: 100%;">
                <?php _e('✨ Generate SEO with AI', 'snn'); ?>
            </button>
            <p class="description"><?php _e('Generate SEO title and description using AI', 'snn'); ?></p>
        </td>
    </tr>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const termName = <?php echo json_encode($term->name); ?>;
        const termDescription = <?php echo json_encode($term->description); ?>;
        const termId = <?php echo $term->term_id; ?>;

        const generateTermBtn = document.getElementById('snn-seo-ai-generate-term-btn');
        if (generateTermBtn) {
            generateTermBtn.addEventListener('click', function(e) {
                e.preventDefault();

                if (typeof window.snnSeoAiOpenOverlay === 'function') {
                    window.snnSeoAiOpenOverlay('term', {
                        termId: termId,
                        name: termName,
                        description: termDescription
                    });
                }
            });
        }
    });
    </script>
    <?php
}

/**
 * Register taxonomy field hooks for enabled taxonomies
 * Note: We only add the AI button field, not save hooks
 * The AI overlay saves directly via AJAX, and the main SEO fields are handled by seo.php
 */
function snn_seo_ai_register_taxonomy_hooks() {
    if (!snn_seo_ai_is_enabled()) {
        return;
    }

    $enabled_taxonomies = get_option('snn_seo_taxonomies_enabled', []);
    
    foreach ($enabled_taxonomies as $taxonomy => $enabled) {
        if ($enabled) {
            // Only add the AI button field to the taxonomy edit page
            // Do NOT hook into save - that's handled by seo.php
            add_action("{$taxonomy}_edit_form_fields", 'snn_seo_ai_taxonomy_fields', 10, 1);
        }
    }
}
add_action('admin_init', 'snn_seo_ai_register_taxonomy_hooks');

/**
 * Hook AI buttons into SEO meta box
 */
function snn_seo_ai_init() {
    if (!snn_seo_ai_is_enabled()) {
        return;
    }
    
    // Add buttons after meta box is rendered
    add_action('snn_seo_meta_box_after_fields', 'snn_seo_ai_meta_box_buttons');
}
add_action('init', 'snn_seo_ai_init');
