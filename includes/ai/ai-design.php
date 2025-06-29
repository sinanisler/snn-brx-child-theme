<?php
/**
 * SNN AI Design Generator Overlay & Frontend Logic (Procedural, Refined)
 * - Procedural, consistent, highly detailed AI prompt for Bricks Builder
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'snn_get_ai_api_config' ) ) {
    error_log('SNN AI Error: snn_get_ai_api_config() function not found in ai-design.php. Make sure ai-api.php is included.');
    return;
}

function snn_add_ai_design_script_to_footer() {
    if (
        ! current_user_can('manage_options') ||
        ! isset($_GET['bricks']) ||
        $_GET['bricks'] !== 'run'
    ) {
        return;
    }

    $ai_enabled = get_option('snn_ai_enabled', 'no');
    if ($ai_enabled !== 'yes') return;

    $config = snn_get_ai_api_config();
    if (empty($config['apiKey']) || empty($config['apiEndpoint'])) return;
    ?>
    <style>
    .snn-ai-design-overlay { display: none; position: fixed; bottom: 0; left: 0; width: 100%; z-index: 99999999; justify-content: center; font-size: 14px; line-height: 1.2; align-items: flex-end;}
    .snn-ai-design-modal { background-color: var(--builder-bg); color: var(--builder-color); border-radius: 4px 4px 0 0; width: 800px; max-width: 90%; max-height: 80vh; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 0 20px rgba(0,0,0,0.3);}
    .snn-ai-design-modal-header { padding: 10px 15px; background-color: var(--builder-bg-shade); display: flex; justify-content: space-between; align-items: center; }
    .snn-ai-design-modal-header h3 { margin: 0; font-size: 18px; color: var(--builder-color); }
    .snn-ai-design-close { cursor: pointer; font-size: 26px; color: var(--builder-color-light); line-height: 1; transform: scaleX(1.3);}
    .snn-ai-design-modal-body { padding: 15px; overflow-y: auto; flex: 1;}
    .snn-ai-design-prompt { width: 100%; min-height: 80px; padding: 8px; border-radius: 4px; margin-bottom: 10px; font-family: inherit; resize: vertical; background-color: var(--builder-bg-light); color: var(--builder-color); border: solid 1px #00000055; box-sizing: border-box;}
    .snn-ai-design-submit, .snn-ai-design-apply { background-color: var(--builder-color-accent); color: var(--builder-bg); border: none; border-radius: 4px; padding: 10px 20px; cursor: pointer; font-size: 14px; transition: all 0.2s ease; border: solid 1px transparent; margin-top: 5px; }
    .snn-ai-design-submit:hover, .snn-ai-design-apply:hover { color: var(--builder-color-accent); background: var(--builder-bg); border: solid 1px #00000055; }
    .snn-ai-design-submit:disabled, .snn-ai-design-apply:disabled { background-color: #ccc; cursor: not-allowed; }
    .snn-ai-design-spinner { display: none; margin: 20px auto; border: 3px solid var(--builder-border-color); border-top: 3px solid #10a37f; border-radius: 50%; width: 30px; height: 30px; animation: snn-ai-spin 1s linear infinite;}
    @keyframes snn-ai-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    .snn-ai-design-response { padding: 10px; background-color: var(--builder-bg-light); border-radius: 4px; margin-top: 15px; display: none; overflow: auto; max-height: 150px; white-space: pre-wrap; font-size: 12px;}
    .snn-ai-design-footer-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 15px;}
    </style>

    <div class="snn-ai-design-overlay" id="snn-ai-design-overlay">
        <div class="snn-ai-design-modal">
            <div class="snn-ai-design-modal-header">
                <h3><?php esc_html_e('AI Design Generator', 'snn'); ?></h3>
                <span class="snn-ai-design-close" id="snn-ai-design-close-button">X</span>
            </div>
            <div class="snn-ai-design-modal-body">
                <textarea
                    id="snn-ai-design-prompt-textarea"
                    class="snn-ai-design-prompt"
                    placeholder="<?php esc_attr_e('Describe the layout or design you want to generate.', 'snn'); ?>"
                ></textarea>
                <button id="snn-ai-design-submit" class="snn-ai-design-submit"><?php esc_html_e('Generate', 'snn'); ?></button>
                <div id="snn-ai-design-spinner" class="snn-ai-design-spinner"></div>
                <div id="snn-ai-design-response" class="snn-ai-design-response"></div>
                <div class="snn-ai-design-footer-actions">
                    <button id="snn-ai-design-apply" class="snn-ai-design-apply" style="display: none;"><?php esc_html_e('Add To Canvas', 'snn'); ?></button>
                </div>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const config = {
            apiKey: <?php echo json_encode($config['apiKey']); ?>,
            model: <?php echo json_encode($config['model']); ?>,
            systemPrompt: `
You are an expert procedural layout generator for Bricks Builder (WordPress visual builder).

**Your Output:**
- Output ONLY a JSON array of layout objects.
- Each object is a Bricks Builder element: section, container, block, heading, text, button, image, divider, etc.
- Your array is to be parsed by JS: it must be pure JSON, no markdown, no explanations, no comments.

**Each Element Object MUST contain:**
- id: Unique alphanumeric string per element (e.g. "jdh12x").
- name: The Bricks element type (e.g. "section", "container", "block", "heading", "text", "button", "image", "divider").
- parent: Parent id or 0 for top-level.
- children: Array of ids for direct children, order matters.
- settings: Object with keys for content and styling.
- (Optional) themeStyles: Object for global design tokens if needed.

**Procedural / Style Logic:**
- Apply common settings (such as padding, border-radius, typography, background) consistently. Reuse style object structures across elements (e.g. border radius, gap, background, colors).
- Do not invent random style keys—only use valid and meaningful keys for Bricks elements, and always use the same structure for similar settings (see examples).
- For background images, always use either a remote placeholder or provided structure. For procedural images, use:
    "image": { "url": "https://random.imagecdn.app/500/150", "external": true, "filename": "150" }
- For backgrounds, _background can combine color and/or image. Example:
    "_background": { "color": { "hex": "#ffffff" }, "image": { "url": "...", "external": true, "filename": "..." } }

**Element Details:**
- **Sections:** Always top-level (\`parent: 0\`). Used for main layout blocks.
- **Containers:** Hold blocks, rows, or columns.
- **Blocks:** Generic content holders. Often inside containers.
- **Heading:** Use "text" (plain or HTML) and "_typography" (font-size, color, etc).
- **Text:** Use "text" (HTML or plain) and "_typography".
- **Button:** Use "text", "_background", "_padding", "_typography", "_border", and "_background:hover" if needed.
- **Image:** Use "image" (as shown above), "_border" (optional), and sizing.
- **Divider:** Use "color" key.

**Layout Logic:**
- For multi-column layouts, use containers/blocks inside sections, and set "_direction": "row" (or appropriate grid settings).
- Use "_columnGap" and "_rowGap" on containers for spacing.
- Add CSS id in "_cssId" for reusable sections if needed.

**Responsive:**
- You may add mobile/tablet specific settings as keys like "_height:mobile_landscape".
- For procedural layouts, provide mobile-optimized adjustments (e.g. reduced gaps, smaller font-size).

**Output Rules:**
- Do not use any markdown, no triple backticks.
- Do not include explanations or comments, only the raw JSON array.

**Template Example:**
[
  {
    "id": "abc123",
    "name": "section",
    "parent": 0,
    "children": ["def456"],
    "settings": {
      "_cssId": "hero-section",
      "_background": { "color": { "hex": "#111" }, "image": { "url": "https://random.imagecdn.app/1100/550", "external": true, "filename": "550" } },
      "_height": "600",
      "_alignItems": "center"
    }
  },
  {
    "id": "def456",
    "name": "container",
    "parent": "abc123",
    "children": ["ghi789", "xyz987"],
    "settings": {
      "_direction": "row",
      "_columnGap": "30",
      "_rowGap": "30"
    }
  }
  // ... etc
]

**ALWAYS follow these instructions and these only.**
            `,
            apiEndpoint: <?php echo json_encode($config['apiEndpoint']); ?>
        };

        const overlay = document.getElementById('snn-ai-design-overlay');
        const closeModalButton = document.getElementById('snn-ai-design-close-button');
        const promptTextarea = document.getElementById('snn-ai-design-prompt-textarea');
        const submitButton = document.getElementById('snn-ai-design-submit');
        const spinner = document.getElementById('snn-ai-design-spinner');
        const responseDiv = document.getElementById('snn-ai-design-response');
        const applyButton = document.getElementById('snn-ai-design-apply');

        let aiResponse = null;
        let isRequestPending = false;
        let lastParsedDesign = null;

        function showModal() {
            overlay.style.display = 'flex';
            promptTextarea.value = '';
            responseDiv.textContent = '';
            responseDiv.style.display = 'none';
            spinner.style.display = 'none';
            submitButton.disabled = false;
            applyButton.style.display = 'none';
            aiResponse = null;
            lastParsedDesign = null;
            promptTextarea.focus();
        }
        function hideModal() {
            overlay.style.display = 'none';
            aiResponse = null;
            lastParsedDesign = null;
        }
        if(closeModalButton) closeModalButton.addEventListener('click', hideModal);
        if(overlay) {
            overlay.addEventListener('click', e => {
                if (e.target === overlay) hideModal();
            });
        }

        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === '2') {
                e.preventDefault();
                showModal();
            }
        });

        if(submitButton) submitButton.addEventListener('click', async () => {
            if (isRequestPending) return;
            const userPrompt = promptTextarea.value.trim();
            if (!userPrompt) {
                responseDiv.textContent = 'Please enter a prompt describing your layout.';
                responseDiv.style.display = 'block';
                return;
            }
            submitButton.disabled = true;
            spinner.style.display = 'block';
            responseDiv.style.display = 'none';
            applyButton.style.display = 'none';
            isRequestPending = true;
            aiResponse = null;
            lastParsedDesign = null;

            const messages = [
                { role: 'system', content: config.systemPrompt },
                { role: 'user', content: userPrompt }
            ];
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
                let rawContent = '';
                if (data.choices && data.choices[0] && data.choices[0].message && data.choices[0].message.content) {
                    rawContent = data.choices[0].message.content.trim();
                } else {
                    throw new Error('Unexpected AI response format.');
                }
                aiResponse = rawContent;
                let parsed = null;
                try {
                    parsed = JSON.parse(rawContent);
                } catch (e) {
                    rawContent = rawContent.replace(/```(json)?/g, '').replace(/^\s*[\r\n]/, '').trim();
                    parsed = JSON.parse(rawContent);
                }
                if (!Array.isArray(parsed)) throw new Error('AI response is not a JSON array.');
                lastParsedDesign = parsed;
                responseDiv.textContent = JSON.stringify(parsed, null, 2);
                responseDiv.style.display = 'block';
                applyButton.style.display = 'inline-block';
            } catch (error) {
                responseDiv.textContent = 'Error: ' + error.message;
                responseDiv.style.display = 'block';
                aiResponse = null;
                lastParsedDesign = null;
            } finally {
                spinner.style.display = 'none';
                isRequestPending = false;
                submitButton.disabled = false;
            }
        });

        if(applyButton) applyButton.addEventListener('click', () => {
            if (!lastParsedDesign || !Array.isArray(lastParsedDesign)) {
                responseDiv.textContent = 'No valid design structure to add.';
                responseDiv.style.display = 'block';
                return;
            }
            try {
                const app = document.querySelector("[data-v-app]");
                if (!app) throw new Error('Bricks app not found.');
                const bricksState = app.__vue_app__.config.globalProperties.$_state;
                if (!Array.isArray(bricksState.content)) throw new Error('Bricks content not found.');
                bricksState.content.push(...lastParsedDesign);
                hideModal();
                if (typeof bricks !== 'undefined' && bricks.notify) {
                    bricks.notify('AI design added to canvas!', 'success');
                }
            } catch (e) {
                responseDiv.textContent = 'Error: ' + e.message;
                responseDiv.style.display = 'block';
            }
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'snn_add_ai_design_script_to_footer', 98);
