<?php
// wil lbe deprecated
add_action('admin_menu', function () {
    add_submenu_page(
        'snn-settings',
        __('Global Classes Manager', 'snn'),
        __('Global Classes', 'snn'),
        'manage_options',
        'snn-classes',
        'bgcc_classes_page',
        99
    );
});

add_action('admin_init', function () {
    if (isset($_POST['bgcc_classes_save']) && wp_verify_nonce($_POST['bgcc_classes_nonce'], 'bgcc_classes_save')) {

        $postedCategories = $_POST['categories'] ?? null;
        $new_categories = [];
        if ($postedCategories && is_array($postedCategories)) {
            foreach ($postedCategories as $c) {
                $catId   = !empty($c['id']) ? sanitize_text_field($c['id']) : bgcc_rand_id();
                $catName = !empty($c['name']) ? sanitize_text_field($c['name']) : '';
                if ($catName) {
                    $new_categories[] = [
                        'id'   => $catId,
                        'name' => $catName
                    ];
                }
            }
        }
        update_option('snn_classes_categories', $new_categories);

        // Ensure the existing classes option is an array
        $existing_classes = get_option('snn_classes', []);
        if (!is_array($existing_classes)) {
            $existing_classes = [];
        }
        $oldClassById = [];
        foreach ($existing_classes as $cl) {
            $oldClassById[$cl['id']] = $cl;
        }
        $postedClasses = $_POST['classes'] ?? null;
        if (is_array($postedClasses)) { // If classes are posted, process them
            $new_classes = [];
            foreach ($postedClasses as $cl) {
                $classId   = !empty($cl['id']) ? sanitize_text_field($cl['id']) : bgcc_rand_id();
                $className = !empty($cl['name']) ? sanitize_text_field($cl['name']) : '';
                $catId     = isset($cl['category']) ? sanitize_text_field($cl['category']) : '';

                if ($className) {
                    $parsedSettings = bgcc_parse_css($cl['css_generated'] ?? '');
                    // Removed custom CSS processing
                    if (isset($oldClassById[$classId])) {
                        $oldSettings = $oldClassById[$classId]['settings'] ?? [];
                        if (!empty($oldSettings['_raw']['color']) && !empty($parsedSettings['_raw']['color'])) {
                            $oldColor = $oldSettings['_raw']['color'];
                            $parsedSettings['_raw']['color'] = $oldColor;
                        }
                        $parsedSettings = array_replace_recursive($oldSettings, $parsedSettings);
                    }
                    $new_classes[] = [
                        'id'       => $classId,
                        'name'     => $className,
                        'settings' => $parsedSettings,
                        'category' => $catId,
                        'modified' => time(),
                        'user_id'  => get_current_user_id(),
                    ];
                }
            }
        } else {
            // If no classes are posted, update with an empty array
            $new_classes = [];
        }
        update_option('snn_classes', $new_classes);

        add_settings_error('bgcc_messages', 'bgcc_save_message', __('Settings Saved', 'snn'), 'updated');
        wp_redirect(add_query_arg(['page' => 'snn-classes', 'updated' => 'true'], admin_url('admin.php')));
        exit;
    }
});

// Utility Functions
function bgcc_rand_id($len = 6) {
    return substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, $len);
}

function bgcc_extract_brace_block($css, $startBracePos) {
    $braceCount = 0;
    $len = strlen($css);
    for ($i = $startBracePos; $i < $len; $i++) {
        if ($css[$i] === '{') {
            $braceCount++;
        } elseif ($css[$i] === '}') {
            $braceCount--;
            if ($braceCount === 0) {
                return [substr($css, $startBracePos, $i - $startBracePos + 1), $i + 1];
            }
        }
    }
    return [substr($css, $startBracePos), $len];
}

function flatten_value($value) {
    if (!is_array($value)) {
        return $value;
    }
    if (isset($value['raw'])) {
        return $value['raw'];
    }
    if (array_keys($value) === range(0, count($value) - 1)) {
        $flattened = array_map('flatten_value', $value);
        return implode(', ', $flattened);
    }
    $flattened = [];
    foreach ($value as $k => $v) {
        $flattened[] = $k . ': ' . flatten_value($v);
    }
    return implode('; ', $flattened);
}

function bgcc_parse_css($css) {
    $settings = [];
    $settings['_raw'] = [];

    $css = preg_replace('/\/\*.*?\*\//s', '', trim($css));

    $settings['_keyframes'] = [];
    $offset = 0;
    while (($pos = strpos($css, '@keyframes', $offset)) !== false) {
        $bracePos = strpos($css, '{', $pos);
        if ($bracePos === false) {
            break;
        }
        list($block, $endPos) = bgcc_extract_brace_block($css, $bracePos);
        $fullBlock = substr($css, $pos, $endPos - $pos);
        if (preg_match('/@keyframes\s+([A-Za-z0-9_-]+)/', $fullBlock, $m)) {
            $animationName = $m[1];
            $settings['_keyframes'][$animationName] = trim($fullBlock);
        }
        $css = substr_replace($css, '', $pos, $endPos - $pos);
        $offset = $pos;
    }

    if (preg_match('/\{(.*)\}/s', $css, $m)) {
        $rules = explode(';', $m[1]);
    } else {
        $rules = explode(';', $css);
    }

    foreach ($rules as $r) {
        $r = trim($r);
        if (!$r || strpos($r, ':') === false) {
            continue;
        }
        list($property, $value) = array_map('trim', explode(':', $r, 2));
        $property = sanitize_text_field($property);
        while (strpos($property, 'raw-') === 0) {
            $property = substr($property, 4);
        }
        $settings['_raw'][$property] = sanitize_text_field($value);
    }
    return $settings;
}

function bgcc_gen_css($name, $s) {
    $generated = bgcc_generate_css_from_settings($s);
    return $generated;
}

function bgcc_generate_css_from_settings($settings) {
    $cssLines = "";
    $flat_groups = array('_typography', '_background', '_border', '_boxShadow', '_gradient', '_transform');
    foreach ($settings as $key => $value) {
        if (in_array($key, array('_cssCustom', '_keyframes'))) continue;
        
        if ($key === '_raw' && is_array($value)) {
            foreach ($value as $subKey => $subVal) {
                $prop = convertKeyToCssProperty($subKey);
                $cssLines .= "  {$prop}: {$subVal};\n";
            }
            continue;
        }
        
        if (in_array($key, array('_margin', '_padding'))) {
            if (is_array($value) && isset($value['top'], $value['right'], $value['bottom'], $value['left'])) {
                $prop = str_replace('_', '-', strtolower(ltrim($key, '_')));
                $cssLines .= "  {$prop}: {$value['top']} {$value['right']} {$value['bottom']} {$value['left']};\n";
            } else {
                foreach ($value as $subKey => $subVal) {
                    $prop = convertKeyToCssProperty($subKey);
                    $cssLines .= "  {$prop}: " . flatten_value($subVal) . ";\n";
                }
            }
        } else if (in_array($key, $flat_groups)) {
            if (is_array($value)) {
                foreach ($value as $subKey => $subVal) {
                    if (is_array($subVal) && isset($subVal['raw'])) {
                        $prop = convertKeyToCssProperty($subKey);
                        $cssLines .= "  {$prop}: " . $subVal['raw'] . ";\n";
                    } else if (!is_array($subVal)) {
                        $prop = convertKeyToCssProperty($subKey);
                        $cssLines .= "  {$prop}: {$subVal};\n";
                    } else {
                        foreach ($subVal as $subSubKey => $subSubVal) {
                            $prop = convertKeyToCssProperty($subKey . '-' . $subSubKey);
                            $cssLines .= "  {$prop}: " . flatten_value($subSubVal) . ";\n";
                        }
                    }
                }
            }
        } else {
            if (!is_array($value)) {
                $prop = convertKeyToCssProperty($key);
                $cssLines .= "  {$prop}: {$value};\n";
            } else {
                foreach ($value as $subKey => $subVal) {
                    if (is_array($subVal) && isset($subVal['raw'])) {
                        $prop = convertKeyToCssProperty($key . '-' . $subKey);
                        $cssLines .= "  {$prop}: " . $subVal['raw'] . ";\n";
                    } else if (!is_array($subVal)) {
                        $prop = convertKeyToCssProperty($key . '-' . $subKey);
                        $cssLines .= "  {$prop}: {$subVal};\n";
                    } else {
                        $prop = convertKeyToCssProperty($key . '-' . $subKey);
                        $cssLines .= "  {$prop}: " . flatten_value($subVal) . ";\n";
                    }
                }
            }
        }
    }
    return $cssLines;
}

function convertKeyToCssProperty($key) {
    $key = ltrim($key, '_');
    $key = preg_replace('/([a-z])([A-Z])/', '$1-$2', $key);
    return strtolower($key);
}

// ADMIN PAGE: Classes UI
function bgcc_classes_page() {
    $categories = get_option('snn_classes_categories', []);
    if (!is_array($categories)) {
        $categories = [];
    }
    $classes = get_option('snn_classes', []);
    if (!is_array($classes)) {
        $classes = [];
    }
    ?>
    <style>
        /* Grid layout */
        #bgcc-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
        }
        #categories-section {
            width: 300px;
        }
        /* Tables and inputs styling */
        #categories-table input[type="text"],
        #classes-table textarea,
        #bulk-css {
            width: 90%;
        }
        #classes-table textarea {
            height: 100px;
        }
        /* Export textarea styling */
        #export-section textarea {
            width: 100%;
            font-family: monospace;
        }
        .no-classes td {
            padding: 15px;
            font-style: italic;
            color: #555;
        }
        /* Bulk actions styling */
        #bulk-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
    </style>

    <div class="wrap">
        <h1><?php _e('Global Class Manager', 'snn'); ?>  <b style="color:red"><?php _e('EXPERIMENTAL', 'snn'); ?></b></h1>
        <?php settings_errors('bgcc_messages'); ?>
        <form method="post" id="bgcc-main-form">
            <?php wp_nonce_field('bgcc_classes_save', 'bgcc_classes_nonce'); ?>
            <div id="bgcc-container">
                <div id="categories-section">
                    <h2><?php _e('Categories', 'snn'); ?></h2>
                    <table class="widefat fixed" id="categories-table">
                        <thead>
                            <tr>
                                <th><?php _e('Name', 'snn'); ?></th>
                                <th style="width:100px"><?php _e('Actions', 'snn'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($categories && count($categories) > 0) : ?>
                                <?php foreach ($categories as $i => $c) : ?>
                                    <tr>
                                        <td>
                                            <input type="hidden" name="categories[<?php echo $i; ?>][id]" value="<?php echo esc_attr($c['id']); ?>">
                                            <input type="text" name="categories[<?php echo $i; ?>][name]" value="<?php echo esc_attr($c['name']); ?>" required>
                                        </td>
                                        <td>
                                            <button type="button" class="button button-danger remove-row"><?php _e('Remove', 'snn'); ?></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr class="no-classes">
                                    <td colspan="2" style="text-align: center;"><?php _e('No categories added yet. Click "Add" to create one.', 'snn'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2">
                                    <button type="button" class="button button-secondary" id="add-category"><?php _e('Add', 'snn'); ?></button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>

                    <!-- Bulk CSS Section -->
                    <div style="margin-top:40px;">
                        <h3><?php _e('Bulk CSS', 'snn'); ?></h3>
                        <p><?php _e('Paste multiple CSS class definitions here (e.g. <code>.my-class { color: red; }</code>) and click "Generate Classes".<br>
                        Multiple selectors (comma separated) and both <code>@media</code> and <code>@keyframes</code> blocks are supported.', 'snn'); ?></p>
                        <textarea id="bulk-css" rows="4" style="width:100%; font-family:monospace;"></textarea>
                        <br><br>
                        <button type="button" class="button button-secondary" id="generate-classes"><?php _e('Generate Classes', 'snn'); ?></button>
                    </div>
                </div>

                <div id="classes-section">
                    <h2><?php _e('Classes', 'snn'); ?></h2>
                    <!-- Bulk actions for classes with Save All -->
                    <div id="bulk-actions">
                        <?php submit_button(__('Save All', 'snn'), 'primary', 'bgcc_classes_save', false); ?>
                        <button type="button" class="button" id="bulk-delete"><?php _e('Delete Selected', 'snn'); ?></button>
                        <select id="bulk-category">
                            <option value=""><?php _e('- Change Category To -', 'snn'); ?></option>
                            <?php foreach ($categories as $cat) : ?>
                                <option value="<?php echo esc_attr($cat['id']); ?>"><?php echo esc_html($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="button" id="bulk-change-category"><?php _e('Apply', 'snn'); ?></button>
                        <input type="text" id="bulk-search" placeholder="<?php _e('Search classes...', 'snn'); ?>" style="margin-left:20px; padding-left:5px;">
                    </div>
                    <!-- Classes table -->
                    <table class="widefat fixed" id="classes-table">
                        <thead>
                            <tr>
                                <th style="width:30px"><input type="checkbox" id="select-all"></th>
                                <th><?php _e('Name', 'snn'); ?></th>
                                <th><?php _e('Category (Optional)', 'snn'); ?></th>
                                <th><?php _e('CSS (Generated)', 'snn'); ?></th>
                                <th style="width:100px"><?php _e('Actions', 'snn'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($classes && count($classes) > 0) : ?>
                                <?php foreach ($classes as $i => $cl) : ?>
                                    <tr>
                                        <td><input type="checkbox" class="bulk-select"></td>
                                        <td>
                                            <input type="hidden" name="classes[<?php echo $i; ?>][id]" value="<?php echo esc_attr($cl['id']); ?>">
                                            <input type="text" name="classes[<?php echo $i; ?>][name]" value="<?php echo esc_attr($cl['name']); ?>" required>
                                        </td>
                                        <td>
                                            <select name="classes[<?php echo $i; ?>][category]">
                                                <option value=""><?php _e('- None -', 'snn'); ?></option>
                                                <?php foreach ($categories as $cat) : ?>
                                                    <option value="<?php echo esc_attr($cat['id']); ?>" <?php selected(($cl['category'] ?? ''), $cat['id']); ?>>
                                                        <?php echo esc_html($cat['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <textarea name="classes[<?php echo $i; ?>][css_generated]" rows="5" placeholder="<?php _e('Generated CSS', 'snn'); ?>" required><?php echo esc_textarea(bgcc_generate_css_from_settings($cl['settings'])); ?></textarea>
                                        </td>
                                        <td>
                                            <button type="button" class="button button-danger remove-row"><?php _e('Remove', 'snn'); ?></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr class="no-classes">
                                    <td colspan="5" style="text-align: center;"><?php _e('No classes added yet. Click "Add" to create a class.', 'snn'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5">
                                    <button type="button" class="button button-secondary" id="add-class"><?php _e('Add', 'snn'); ?></button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </form>

        <div id="export-section" style="margin-top:30px;">
            <h2><?php _e('Export Classes', 'snn'); ?></h2>
            <p><?php _e('Copy the CSS for all classes below to back up your class list:', 'snn'); ?></p>
            <textarea readonly rows="15"><?php 
                $export_css = '';
                if (is_array($classes) && count($classes) > 0) {
                    foreach ($classes as $cl) {
                        if(isset($cl['settings'])) {
                            $selector = $cl['name'];
                            // Ensure selector starts with a dot
                            if (strpos($selector, '.') !== 0) {
                                $selector = '.' . $selector;
                            }
                            $export_css .= $selector . " {\n" 
                                        . bgcc_generate_css_from_settings($cl['settings']) 
                                        . "}\n\n";
                        }
                    }
                }
                echo esc_textarea($export_css);
            ?></textarea>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // DOM elements for Classes page
        const categoriesTable = document.getElementById('categories-table').querySelector('tbody');
        const classesTable = document.getElementById('classes-table').querySelector('tbody');
        const bulkCssTextarea = document.getElementById('bulk-css');

        // Buttons
        const addCategoryBtn = document.getElementById('add-category');
        const addClassBtn = document.getElementById('add-class');
        const generateClassesBtn = document.getElementById('generate-classes');

        // Bulk actions: Classes
        const bulkDeleteBtn = document.getElementById('bulk-delete');
        const bulkChangeCategoryBtn = document.getElementById('bulk-change-category');
        const bulkCategorySelect = document.getElementById('bulk-category');
        const selectAllCheckbox = document.getElementById('select-all');
        const bulkSearchInput = document.getElementById('bulk-search');

        // Categories data from PHP
        const categories = <?php echo json_encode(array_map(function($c) {
            return ['id' => $c['id'], 'name' => $c['name']];
        }, $categories)); ?>;

        // Helper: random ID generator
        function bgccRandId(len = 6) {
            return [...Array(len)]
                .map(() => 'abcdefghijklmnopqrstuvwxyz0123456789'[Math.floor(Math.random() * 36)])
                .join('');
        }

        // Add new category row
        addCategoryBtn.addEventListener('click', () => {
            const idx = categoriesTable.rows.length;
            const row = categoriesTable.insertRow(-1);
            row.innerHTML = `
                <td>
                    <input type="hidden" name="categories[${idx}][id]" value="${bgccRandId()}">
                    <input type="text" name="categories[${idx}][name]" required>
                </td>
                <td>
                    <button type="button" class="button button-danger remove-row"><?php _e('Remove', 'snn'); ?></button>
                </td>`;
        });

        // Add new class row
        addClassBtn.addEventListener('click', () => {
            const noClassesMsg = classesTable.querySelector('.no-classes');
            if (noClassesMsg) {
                noClassesMsg.remove();
            }
            const idx = classesTable.rows.length;
            let options = '<option value=""><?php _e('- None -', 'snn'); ?></option>';
            categories.forEach(c => {
                options += `<option value="${c.id}">${c.name}</option>`;
            });
            const row = classesTable.insertRow(-1);
            row.innerHTML = `
                <td><input type="checkbox" class="bulk-select"></td>
                <td>
                    <input type="hidden" name="classes[${idx}][id]" value="${bgccRandId()}">
                    <input type="text" name="classes[${idx}][name]" required>
                </td>
                <td>
                    <select name="classes[${idx}][category]">${options}</select>
                </td>
                <td>
                    <textarea name="classes[${idx}][css_generated]" rows="5" placeholder="<?php _e('Generated CSS', 'snn'); ?>" required></textarea>
                </td>
                <td>
                    <button type="button" class="button button-danger remove-row"><?php _e('Remove', 'snn'); ?></button>
                </td>`;
        });

        document.body.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-row')) {
                const row = e.target.closest('tr');
                const tbody = row.closest('tbody');
                row.remove();
                if (tbody === classesTable) {
                    if (!classesTable.rows.length) {
                        let newRow = classesTable.insertRow();
                        newRow.classList.add('no-classes');
                        newRow.innerHTML = '<td colspan="5" style="text-align: center;"><?php _e('No classes added yet. Click "Add" to create a class.', 'snn'); ?></td>';
                    }
                }
            }
        });

        // Bulk CSS => Generate Classes
        generateClassesBtn.addEventListener('click', () => {
            const text = bulkCssTextarea.value.trim();
            if (!text) {
                alert('<?php _e('Please paste some CSS first.', 'snn'); ?>');
                return;
            }
            const noClassesMsg = classesTable.querySelector('.no-classes');
            if (noClassesMsg) {
                noClassesMsg.remove();
            }
            // Extract media blocks using a regex that supports complex conditions
            function extractMediaBlocks(css) {
                let mediaBlocks = [];
                let remaining = css;
                let regex = /@media\s*([^{]+)\{/g;
                let match;
                while ((match = regex.exec(remaining)) !== null) {
                    let startIndex = match.index;
                    let condition = match[1].trim();
                    let braceCount = 1;
                    let i = regex.lastIndex;
                    while (i < remaining.length && braceCount > 0) {
                        if (remaining[i] === '{') braceCount++;
                        else if (remaining[i] === '}') braceCount--;
                        i++;
                    }
                    let mediaBlock = remaining.substring(startIndex, i);
                    mediaBlocks.push({ condition, block: mediaBlock });
                    remaining = remaining.substring(0, startIndex) + remaining.substring(i);
                    regex.lastIndex = startIndex;
                }
                return { remaining, mediaBlocks };
            }
            const { remaining: topCss, mediaBlocks } = extractMediaBlocks(text);
            // Updated regex to support more complex class selectors (with pseudo-classes, compound selectors, etc.)
            function processCssRules(cssText, wrapWithMedia) {
                const classRuleRegex = /(?:^|})\s*((?:\.[^,{]+\s*(?:,\s*\.[^,{]+\s*)*))\s*\{([\s\S]*?)\}/g;
                let match;
                while ((match = classRuleRegex.exec(cssText)) !== null) {
                    const selectors = match[1].split(',').map(s => s.trim());
                    const rules = match[2].trim();
                    let keyframes = "";
                    let currentIndex = classRuleRegex.lastIndex;
                    while (currentIndex < cssText.length && /\s/.test(cssText[currentIndex])) {
                        currentIndex++;
                    }
                    while (currentIndex < cssText.length &&
                        (cssText.substring(currentIndex).startsWith('@keyframes') ||
                         cssText.substring(currentIndex).startsWith('@-webkit-keyframes'))
                    ) {
                        let extraction = (function(css, startIndex) {
                            let firstBrace = css.indexOf('{', startIndex);
                            if (firstBrace === -1) return { block: "", end: startIndex };
                            let braceCount = 0;
                            let pos = firstBrace;
                            for (; pos < css.length; pos++) {
                                if (css[pos] === '{') {
                                    braceCount++;
                                } else if (css[pos] === '}') {
                                    braceCount--;
                                    if (braceCount === 0) {
                                        return { block: css.substring(startIndex, pos + 1), end: pos + 1 };
                                    }
                                }
                            }
                            return { block: css.substring(startIndex), end: css.length };
                        })(cssText, currentIndex);
                        keyframes += "\n\n" + extraction.block.trim();
                        currentIndex = extraction.end;
                        classRuleRegex.lastIndex = currentIndex;
                        while (currentIndex < cssText.length && /\s/.test(cssText[currentIndex])) {
                            currentIndex++;
                        }
                    }
                    selectors.forEach(selector => {
                        // Remove the leading dot for storing the name
                        const className = selector.startsWith('.') ? selector.slice(1) : selector;
                        const idx = classesTable.rows.length;
                        let options = '<option value=""><?php _e('- None -', 'snn'); ?></option>';
                        categories.forEach(c => {
                            options += `<option value="${c.id}">${c.name}</option>`;
                        });
                        let classCss = selector + ' {\n' + rules + '\n}';
                        if (keyframes) {
                            classCss += "\n\n" + keyframes;
                        }
                        if (wrapWithMedia) {
                            classCss = `@media ${wrapWithMedia} {\n` + classCss + '\n}';
                        }
                        const row = classesTable.insertRow(-1);
                        row.innerHTML = `
                            <td><input type="checkbox" class="bulk-select"></td>
                            <td>
                                <input type="hidden" name="classes[${idx}][id]" value="${bgccRandId()}">
                                <input type="text" name="classes[${idx}][name]" value="${className}" required>
                            </td>
                            <td>
                                <select name="classes[${idx}][category]">${options}</select>
                            </td>
                            <td>
                                <textarea name="classes[${idx}][css_generated]" rows="5" required>${classCss}</textarea>
                            </td>
                            <td>
                                <button type="button" class="button button-danger remove-row"><?php _e('Remove', 'snn'); ?></button>
                            </td>`;
                    });
                }
            }
            processCssRules(topCss, null);
            mediaBlocks.forEach(media => {
                let block = media.block;
                let firstBrace = block.indexOf('{');
                let innerContent = block.substring(firstBrace + 1, block.lastIndexOf('}'));
                processCssRules(innerContent, '(' + media.condition + ')');
            });
        });

        // Bulk Delete (Classes)
        bulkDeleteBtn.addEventListener('click', () => {
            const checkboxes = classesTable.querySelectorAll('input.bulk-select:checked');
            checkboxes.forEach(cb => {
                const row = cb.closest('tr');
                row.remove();
            });
            if (!classesTable.rows.length) {
                let newRow = classesTable.insertRow();
                newRow.classList.add('no-classes');
                newRow.innerHTML = '<td colspan="5" style="text-align: center;"><?php _e('No classes added yet. Click "Add" to create a class.', 'snn'); ?></td>';
            }
        });

        // Bulk Change Category (Classes)
        bulkChangeCategoryBtn.addEventListener('click', () => {
            const newCategory = bulkCategorySelect.value;
            if (!newCategory) {
                alert('<?php _e('Please select a category.', 'snn'); ?>');
                return;
            }
            const checkboxes = classesTable.querySelectorAll('input.bulk-select:checked');
            checkboxes.forEach(cb => {
                const row = cb.closest('tr');
                const select = row.querySelector('select[name*="[category]"]');
                if (select) {
                    select.value = newCategory;
                }
            });
        });

        // Select all (Classes)
        selectAllCheckbox.addEventListener('change', function(){
            const rows = classesTable.querySelectorAll('tbody tr');
            rows.forEach(row => {
                if(row.style.display !== 'none'){
                    const cb = row.querySelector('input.bulk-select');
                    if(cb){
                        cb.checked = this.checked;
                    }
                }
            });
        });

        // Classes search
        bulkSearchInput.addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            const rows = classesTable.querySelectorAll('tbody tr');
            rows.forEach(row => {
                if (row.classList.contains('no-classes')) return;
                const nameInput = row.querySelector('input[name*="[name]"]');
                if (nameInput) {
                    const text = nameInput.value.toLowerCase();
                    row.style.display = (text.indexOf(filter) !== -1) ? '' : 'none';
                }
            });
        });
    });
    </script>
    <?php
}
?>
