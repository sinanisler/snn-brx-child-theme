<?php
if (!defined('ABSPATH')) {
    exit;
}

function snn_add_block_theme_json_submenu() {
    add_submenu_page(
        'snn-settings',
        'Block Theme JSON',
        'Block Theme JSON',
        'manage_options',
        'snn-block-theme-json',
        'snn_block_theme_json_page_callback'
    );
}
add_action('admin_menu', 'snn_add_block_theme_json_submenu' , 11);

function snn_block_theme_json_page_callback() {
    // Get the path to the child theme's theme.json file
    $theme_json_path = get_stylesheet_directory() . '/theme.json';

    // Check if the theme.json file exists
    if (!file_exists($theme_json_path)) {
        echo '<div class="notice notice-error"><p>The <code>theme.json</code> file does not exist in the current child theme.</p></div>';
        return;
    }

    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Verify nonce
        if (!isset($_POST['snn_theme_json_nonce']) || !wp_verify_nonce($_POST['snn_theme_json_nonce'], 'snn_theme_json_edit')) {
            wp_die(__('Invalid nonce. Please try again.'));
        }

        // Ensure the user has proper permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Get submitted JSON
        $new_content = wp_unslash($_POST['theme_json_content']);
        $decoded_json = json_decode($new_content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            echo '<div class="notice notice-error"><p>Invalid JSON format. Please fix the errors and try again.</p></div>';
        } else {
            // Attempt to write the content to the theme.json file
            if (file_put_contents($theme_json_path, $new_content) === false) {
                echo '<div class="notice notice-error"><p>Failed to save the <code>theme.json</code> file. Please check file permissions.</p></div>';
            } else {
                echo '<div class="notice notice-success"><p><code>theme.json</code> file updated successfully!</p></div>';
            }
        }
    }

    // Get the current content of the theme.json file
    $current_content = file_get_contents($theme_json_path);
    if ($current_content === false) {
        echo '<div class="notice notice-error"><p>Unable to read the <code>theme.json</code> file. Please check file permissions.</p></div>';
        return;
    }

    // Decode JSON
    $theme_json_data = json_decode($current_content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo '<div class="notice notice-error"><p>Invalid JSON in the <code>theme.json</code> file.</p></div>';
        return;
    }

    // Extract known fields for editing
    $appearance_tools       = isset($theme_json_data['settings']['appearanceTools']) ? (bool)$theme_json_data['settings']['appearanceTools'] : false;
    $layout_content_size    = isset($theme_json_data['settings']['layout']['contentSize']) ? $theme_json_data['settings']['layout']['contentSize'] : '';
    $layout_wide_size       = isset($theme_json_data['settings']['layout']['wideSize']) ? $theme_json_data['settings']['layout']['wideSize'] : '';
    $spacing_units          = isset($theme_json_data['settings']['spacing']['units']) ? $theme_json_data['settings']['spacing']['units'] : [];
    $spacing_units_str      = implode(',', $spacing_units);

    // Typography font families
    $typography_font_families = isset($theme_json_data['settings']['typography']['fontFamilies']) ? $theme_json_data['settings']['typography']['fontFamilies'] : [];

    // Colors
    $colors = $theme_json_data['settings']['color']['palette'] ?? [];

    ?>
    <div class="wrap">
        <h1>Block Theme JSON Editor (BETA)</h1>
        <p>
            Use the interface below to dynamically edit the values in your <code>theme.json</code> file. All changes are reflected in the JSON textarea at the bottom. You can edit values using the fields, or directly edit the JSON. Clicking "Save Changes" will update the file.
        </p>

        <form method="post" id="dynamic-form">
            <?php wp_nonce_field('snn_theme_json_edit', 'snn_theme_json_nonce'); ?>

            <h2 style="margin-top: 40px;">Color Palette</h2>
            <p>These are the colors defined in your theme.json. Add or remove as needed.</p>
            <div id="color-list" style="margin-bottom:20px;">
                <?php foreach ($colors as $index => $color) : ?>
                    <div class="color-node" style="margin-bottom:10px;display:flex;align-items:center;">
                        <input type="color" name="colors[<?php echo $index; ?>][color]" value="<?php echo esc_attr($color['color']); ?>" style="margin-right:10px;">
                        <input type="text" name="colors[<?php echo $index; ?>][name]" value="<?php echo esc_attr($color['name']); ?>" placeholder="Name" style="margin-right:10px;">
                        <input type="text" name="colors[<?php echo $index; ?>][slug]" value="<?php echo esc_attr($color['slug']); ?>" placeholder="Slug" style="margin-right:10px;">
                        <button type="button" class="remove-color-node" style="margin-left:10px;">Remove</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-color-node" class="button" style="margin-bottom:40px;">Add Color</button>


            <h2 style="margin-top: 40px;">Layout</h2>
            <div style="margin-bottom:8px;">
                <label>Content Size:
                    <input type="text" name="layout_content_size" value="<?php echo esc_attr($layout_content_size); ?>" style="margin-left:10px;">
                </label>
            </div>
            <div style="margin-bottom:8px;">
                <label>Wide Size:
                    <input type="text" name="layout_wide_size" value="<?php echo esc_attr($layout_wide_size); ?>" style="margin-left:10px;">
                </label>
            </div>

            <h2 style="margin-top: 40px;">Spacing</h2>
            <div style="margin-bottom:8px;">
                <label>Units (comma separated):
                    <input type="text" name="spacing_units" value="<?php echo esc_attr($spacing_units_str); ?>" style="margin-left:10px;width:200px;">
                </label>
            </div>

            <h2 style="margin-top: 40px;">Typography Font Families</h2>
            <p>Manage your font families here. Add or remove as needed.</p>
            <div id="font-families-list" style="margin-bottom:20px;">
                <?php foreach ($typography_font_families as $index => $font) :
                    $name       = isset($font['name']) ? $font['name'] : '';
                    $slug       = isset($font['slug']) ? $font['slug'] : '';
                    $fontFamily = isset($font['fontFamily']) ? $font['fontFamily'] : '';
                ?>
                    <div class="font-family-node" style="margin-bottom:10px; display:flex; align-items:center;">
                        <input type="text" name="fontFamilies[<?php echo $index; ?>][name]" value="<?php echo esc_attr($name); ?>" placeholder="Name" style="margin-right:10px;">
                        <input type="text" name="fontFamilies[<?php echo $index; ?>][slug]" value="<?php echo esc_attr($slug); ?>" placeholder="Slug" style="margin-right:10px;">
                        <input type="text" name="fontFamilies[<?php echo $index; ?>][fontFamily]" value="<?php echo esc_attr($fontFamily); ?>" placeholder="Font Family CSS Value" style="margin-right:10px;width:300px;">
                        <button type="button" class="remove-font-family-node" style="margin-left:10px;">Remove</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-font-family-node" class="button" style="margin-bottom:40px;">Add Font Family</button>

            <h2 style="margin-top: 40px;">Full JSON Editor</h2>
            <p>All the fields above are synchronized with the JSON below. You can also directly edit the JSON here if you prefer. Ensure that the JSON is valid before saving.</p>
            <textarea id="theme-json-textarea" name="theme_json_content" style="width:100%; height:500px; font-family: monospace;"><?php echo esc_textarea(json_encode($theme_json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></textarea>
            <p><strong>Note:</strong> Ensure that the JSON is valid before saving changes.</p>
            <?php submit_button('Save Changes'); ?>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const themeJsonTextarea = document.getElementById('theme-json-textarea');
            const dynamicForm        = document.getElementById('dynamic-form');
            const colorList          = document.getElementById('color-list');
            const addColorButton     = document.getElementById('add-color-node');
            const fontFamiliesList   = document.getElementById('font-families-list');
            const addFontFamilyButton = document.getElementById('add-font-family-node');

            let jsonData;
            try {
                jsonData = JSON.parse(themeJsonTextarea.value);
            } catch (e) {
                jsonData = {};
            }

            function getFormDataAsJson() {
                let newData = JSON.parse(JSON.stringify(jsonData));

                if (typeof newData.settings !== 'object' || newData.settings === null) {
                    newData.settings = {};
                }
                if (typeof newData.settings.layout !== 'object' || newData.settings.layout === null) {
                    newData.settings.layout = {};
                }
                if (typeof newData.settings.spacing !== 'object' || newData.settings.spacing === null) {
                    newData.settings.spacing = {};
                }
                if (typeof newData.settings.typography !== 'object' || newData.settings.typography === null) {
                    newData.settings.typography = {};
                }
                if (typeof newData.settings.color !== 'object' || newData.settings.color === null) {
                    newData.settings.color = {};
                }

                // Remove editor section entirely if it exists
                if (newData.settings.editor) {
                    delete newData.settings.editor;
                }

                // Layout
                let layoutContentSize = dynamicForm.querySelector('input[name="layout_content_size"]').value;
                let layoutWideSize    = dynamicForm.querySelector('input[name="layout_wide_size"]').value;
                newData.settings.layout.contentSize = layoutContentSize;
                newData.settings.layout.wideSize    = layoutWideSize;

                // Spacing
                let spacingUnits = dynamicForm.querySelector('input[name="spacing_units"]').value
                    .split(',')
                    .map(u => u.trim())
                    .filter(Boolean);
                newData.settings.spacing.units = spacingUnits;

                // Typography Font Families
                let fontFamilies = [];
                fontFamiliesList.querySelectorAll('.font-family-node').forEach((node) => {
                    let name       = node.querySelector('input[placeholder="Name"]').value;
                    let slug       = node.querySelector('input[placeholder="Slug"]').value;
                    let fontFamily = node.querySelector('input[placeholder="Font Family CSS Value"]').value;
                    fontFamilies.push({
                        name: name,
                        slug: slug,
                        fontFamily: fontFamily
                    });
                });
                newData.settings.typography.fontFamilies = fontFamilies;

                // If at least one fontFamily is defined, update "styles" references
                // to use the first family's slug in var(--wp--preset--font-family--XYZ).
                if (fontFamilies.length > 0) {
                    const primarySlug = fontFamilies[0].slug || 'default-font';

                    // Walk through newData.styles and replace relevant fontFamily references
                    if (newData.styles) {
                        // Update blocks
                        if (newData.styles.blocks) {
                            for (const blockName in newData.styles.blocks) {
                                if (newData.styles.blocks[blockName].typography
                                    && newData.styles.blocks[blockName].typography.fontFamily) {
                                    newData.styles.blocks[blockName].typography.fontFamily =
                                        `var(--wp--preset--font-family--${primarySlug})`;
                                }
                            }
                        }
                        // Update elements
                        if (newData.styles.elements) {
                            for (const elementName in newData.styles.elements) {
                                if (newData.styles.elements[elementName].typography
                                    && newData.styles.elements[elementName].typography.fontFamily) {
                                    newData.styles.elements[elementName].typography.fontFamily =
                                        `var(--wp--preset--font-family--${primarySlug})`;
                                }
                            }
                        }
                        // Update styles.typography
                        if (newData.styles.typography && newData.styles.typography.fontFamily) {
                            newData.styles.typography.fontFamily =
                                `var(--wp--preset--font-family--${primarySlug})`;
                        }
                    }
                }

                // Color Palette
                let palette = [];
                colorList.querySelectorAll('.color-node').forEach((node) => {
                    let c = node.querySelector('input[type="color"]').value;
                    let n = node.querySelector('input[placeholder="Name"]').value;
                    let s = node.querySelector('input[placeholder="Slug"]').value;
                    palette.push({
                        color: c,
                        name: n,
                        slug: s
                    });
                });
                newData.settings.color.palette = palette;

                return newData;
            }

            function updateJsonTextarea() {
                let updatedData = getFormDataAsJson();
                themeJsonTextarea.value = JSON.stringify(updatedData, null, 4);
            }

            addColorButton.addEventListener('click', function () {
                const index = colorList.children.length;
                const div = document.createElement('div');
                div.className = 'color-node';
                div.style.marginBottom = '10px';
                div.style.display = 'flex';
                div.style.alignItems = 'center';

                div.innerHTML = `
                    <input type="color" name="colors[${index}][color]" value="#000000" style="margin-right:10px;">
                    <input type="text" name="colors[${index}][name]" placeholder="Name" style="margin-right:10px;">
                    <input type="text" name="colors[${index}][slug]" placeholder="Slug" style="margin-right:10px;">
                    <button type="button" class="remove-color-node" style="margin-left:10px;">Remove</button>
                `;
                colorList.appendChild(div);

                const removeButton = div.querySelector('.remove-color-node');
                removeButton.addEventListener('click', function () {
                    div.remove();
                    updateJsonTextarea();
                });

                updateJsonTextarea();
            });

            addFontFamilyButton.addEventListener('click', function () {
                const index = fontFamiliesList.children.length;
                const div = document.createElement('div');
                div.className = 'font-family-node';
                div.style.marginBottom = '10px';
                div.style.display = 'flex';
                div.style.alignItems = 'center';

                div.innerHTML = `
                    <input type="text" name="fontFamilies[${index}][name]" placeholder="Name" style="margin-right:10px;">
                    <input type="text" name="fontFamilies[${index}][slug]" placeholder="Slug" style="margin-right:10px;">
                    <input type="text" name="fontFamilies[${index}][fontFamily]" placeholder="Font Family CSS Value" style="margin-right:10px; width:300px;">
                    <button type="button" class="remove-font-family-node" style="margin-left:10px;">Remove</button>
                `;
                fontFamiliesList.appendChild(div);

                const removeButton = div.querySelector('.remove-font-family-node');
                removeButton.addEventListener('click', function () {
                    div.remove();
                    updateJsonTextarea();
                });

                updateJsonTextarea();
            });

            colorList.addEventListener('click', function (e) {
                if (e.target.classList.contains('remove-color-node')) {
                    e.target.parentElement.remove();
                    updateJsonTextarea();
                }
            });

            fontFamiliesList.addEventListener('click', function (e) {
                if (e.target.classList.contains('remove-font-family-node')) {
                    e.target.parentElement.remove();
                    updateJsonTextarea();
                }
            });

            dynamicForm.addEventListener('input', function (e) {
                if (e.target !== themeJsonTextarea) {
                    updateJsonTextarea();
                }
            });

            themeJsonTextarea.addEventListener('input', function () {
                let val = themeJsonTextarea.value;
                try {
                    let parsed = JSON.parse(val);
                    jsonData = parsed;
                    syncFieldsFromJsonData();
                } catch (e) {
                    // Invalid JSON, do nothing
                }
            });

            function syncFieldsFromJsonData() {
                let appearanceTools = dynamicForm.querySelector('input[name="appearanceTools"]');
                if (appearanceTools) {
                    appearanceTools.checked = !!(jsonData?.settings?.appearanceTools);
                }

                let layoutContentSize = dynamicForm.querySelector('input[name="layout_content_size"]');
                let layoutWideSize    = dynamicForm.querySelector('input[name="layout_wide_size"]');
                layoutContentSize.value = jsonData?.settings?.layout?.contentSize || '';
                layoutWideSize.value    = jsonData?.settings?.layout?.wideSize || '';

                let spacingUnits = dynamicForm.querySelector('input[name="spacing_units"]');
                let unitsArr = jsonData?.settings?.spacing?.units || [];
                spacingUnits.value = unitsArr.join(',');

                // Rebuild font families fields
                while (fontFamiliesList.firstChild) {
                    fontFamiliesList.removeChild(fontFamiliesList.firstChild);
                }
                let ff = jsonData?.settings?.typography?.fontFamilies || [];
                ff.forEach((font, index) => {
                    const div = document.createElement('div');
                    div.className = 'font-family-node';
                    div.style.marginBottom = '10px';
                    div.style.display = 'flex';
                    div.style.alignItems = 'center';

                    const nameVal       = font.name || '';
                    const slugVal       = font.slug || '';
                    const fontFamilyVal = font.fontFamily || '';

                    div.innerHTML = `
                        <input type="text" name="fontFamilies[${index}][name]" placeholder="Name" value="${nameVal}" style="margin-right:10px;">
                        <input type="text" name="fontFamilies[${index}][slug]" placeholder="Slug" value="${slugVal}" style="margin-right:10px;">
                        <input type="text" name="fontFamilies[${index}][fontFamily]" placeholder="Font Family CSS Value" value="${fontFamilyVal}" style="margin-right:10px;width:300px;">
                        <button type="button" class="remove-font-family-node" style="margin-left:10px;">Remove</button>
                    `;
                    fontFamiliesList.appendChild(div);
                    const removeButton = div.querySelector('.remove-font-family-node');
                    removeButton.addEventListener('click', function () {
                        div.remove();
                        updateJsonTextarea();
                    });
                });

                // Rebuild color fields
                while (colorList.firstChild) {
                    colorList.removeChild(colorList.firstChild);
                }
                let palette = jsonData?.settings?.color?.palette || [];
                palette.forEach((color, index) => {
                    const div = document.createElement('div');
                    div.className = 'color-node';
                    div.style.marginBottom = '10px';
                    div.style.display = 'flex';
                    div.style.alignItems = 'center';

                    const cVal = color.color || '#000000';
                    const nVal = color.name  || '';
                    const sVal = color.slug  || '';

                    div.innerHTML = `
                        <input type="color" name="colors[${index}][color]" value="${cVal}" style="margin-right:10px;">
                        <input type="text" name="colors[${index}][name]" placeholder="Name" value="${nVal}" style="margin-right:10px;">
                        <input type="text" name="colors[${index}][slug]" placeholder="Slug" value="${sVal}" style="margin-right:10px;">
                        <button type="button" class="remove-color-node" style="margin-left:10px;">Remove</button>
                    `;
                    colorList.appendChild(div);
                    const removeButton = div.querySelector('.remove-color-node');
                    removeButton.addEventListener('click', function () {
                        div.remove();
                        updateJsonTextarea();
                    });
                });
            }
        });
    </script>
    <?php
    // No need to close PHP tag at the end of the file
}
