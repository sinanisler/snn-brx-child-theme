<?php
add_action('admin_menu', function () {
    add_submenu_page(
        'snn-settings',
        'Global Classes Manager',
        'Global Classes',
        'manage_options',
        'bricks-global-classes',
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
        update_option('bricks_global_classes_categories', $new_categories);

        // Ensure the existing classes option is an array
        $existing_classes = get_option('bricks_global_classes', []);
        if (!is_array($existing_classes)) {
            $existing_classes = [];
        }
        $oldClassById = [];
        foreach ($existing_classes as $cl) {
            $oldClassById[$cl['id']] = $cl;
        }
        $postedClasses = $_POST['classes'] ?? null;
        if (is_array($postedClasses)) { // Changed: if classes are posted, process them
            $new_classes = [];
            foreach ($postedClasses as $cl) {
                $classId   = !empty($cl['id']) ? sanitize_text_field($cl['id']) : bgcc_rand_id();
                $className = !empty($cl['name']) ? sanitize_text_field($cl['name']) : '';
                $catId     = isset($cl['category']) ? sanitize_text_field($cl['category']) : '';

                if ($className) {
                    $parsedSettings = bgcc_parse_css($cl['css_generated'] ?? '');
                    if (!empty($cl['css_custom'])) {
                        $parsedSettings['_cssCustom'] = sanitize_textarea_field($cl['css_custom']);
                    }
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
            // Changed: if no classes are posted, update with an empty array
            $new_classes = [];
        }
        update_option('bricks_global_classes', $new_classes);

        // Process external resources (JS/CSS URLs)
        $external_resources_post = $_POST['external_resources'] ?? [];
        $new_external_resources = [];
        if (is_array($external_resources_post)) {
            foreach ($external_resources_post as $resource) {
                $resource = esc_url_raw(trim($resource));
                if ($resource) {
                    $new_external_resources[] = $resource;
                }
            }
        }
        update_option('bricks_global_classes_external_resources', $new_external_resources);

        add_settings_error('bgcc_messages', 'bgcc_save_message', 'Settings Saved', 'updated');
        wp_redirect(add_query_arg(['page' => 'bricks-global-classes', 'updated' => 'true'], admin_url('admin.php')));
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
    $categories = get_option('bricks_global_classes_categories', []);
    if (!is_array($categories)) {
        $categories = [];
    }
    $classes = get_option('bricks_global_classes', []);
    if (!is_array($classes)) {
        $classes = [];
    }
    $external_resources = get_option('bricks_global_classes_external_resources', []);
    if (!is_array($external_resources)) {
        $external_resources = [];
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
        .no-classes td,
        .no-resources td {
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
        <h1>Global Class Manager</h1>
        <?php settings_errors('bgcc_messages'); ?>
        <form method="post" id="bgcc-main-form">
            <?php wp_nonce_field('bgcc_classes_save', 'bgcc_classes_nonce'); ?>
            <div id="bgcc-container">
                <div id="categories-section">
                    <h2>Categories</h2>
                    <table class="widefat fixed" id="categories-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th style="width:100px">Actions</th>
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
                                            <button type="button" class="button button-danger remove-row">Remove</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr class="no-classes">
                                    <td colspan="2" style="text-align: center;">No categories added yet. Click "Add" to create one.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2">
                                    <button type="button" class="button button-secondary" id="add-category">Add</button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>

                    <!-- External CDN Resources Section -->
                    <div id="external-resources-section" style="margin-top:40px;">
                        <h2>External CDN Resources</h2>
                        <p>JS or CSS external URL resources to load on the frontend.</p>
                        <table class="widefat fixed" id="external-resources-table">
                            <thead>
                                <tr>
                                    <th>Resource URL</th>
                                    <th style="width:100px">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($external_resources && count($external_resources) > 0) : ?>
                                    <?php foreach ($external_resources as $i => $resource) : ?>
                                        <tr>
                                            <td>
                                                <input type="text" name="external_resources[<?php echo $i; ?>]" value="<?php echo esc_attr($resource); ?>" style="width: 90%;" placeholder="https://example.com/script.js">
                                            </td>
                                            <td>
                                                <button type="button" class="button button-danger remove-row">Remove</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr class="no-resources">
                                        <td colspan="2" style="text-align: center;">No external resources added yet. Click "Add External Resource" to create one.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2">
                                        <button type="button" class="button button-secondary" id="add-external-resource">Add External Resource</button>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Bulk CSS Section moved here -->
                    <div style="margin-top:40px;">
                        <h3>Bulk CSS</h3>
                        <p>Paste multiple CSS class definitions here (e.g. <code>.my-class { color: red; }</code>) and click "Generate Classes".<br>
                        Multiple selectors (comma separated) and both <code>@media</code> and <code>@keyframes</code> blocks are supported.</p>
                        <textarea id="bulk-css" rows="4" style="width:100%; font-family:monospace;"></textarea>
                        <br><br>
                        <button type="button" class="button button-secondary" id="generate-classes">Generate Classes</button>
                    </div>
                </div>

                <div id="classes-section">
                    <h2>Classes</h2>
                    <!-- Bulk actions for classes with Save All moved here -->
                    <div id="bulk-actions">
                        <?php submit_button('Save All', 'primary', 'bgcc_classes_save', false); ?>
                        <button type="button" class="button" id="bulk-delete">Delete Selected</button>
                        <select id="bulk-category">
                            <option value="">- Change Category To -</option>
                            <?php foreach ($categories as $cat) : ?>
                                <option value="<?php echo esc_attr($cat['id']); ?>"><?php echo esc_html($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="button" id="bulk-change-category">Apply</button>
                        <input type="text" id="bulk-search" placeholder="Search classes..." style="margin-left:20px; padding-left:5px;">
                    </div>
                    <!-- Classes table -->
                    <table class="widefat fixed" id="classes-table">
                        <thead>
                            <tr>
                                <th style="width:30px"><input type="checkbox" id="select-all"></th>
                                <th>Name</th>
                                <th>Category (Optional)</th>
                                <th>CSS (Generated / Custom)</th>
                                <th style="width:100px">Actions</th>
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
                                                <option value="">- None -</option>
                                                <?php foreach ($categories as $cat) : ?>
                                                    <option value="<?php echo esc_attr($cat['id']); ?>" <?php selected(($cl['category'] ?? ''), $cat['id']); ?>>
                                                        <?php echo esc_html($cat['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <textarea name="classes[<?php echo $i; ?>][css_generated]" rows="5" placeholder="Generated CSS" required><?php echo esc_textarea(bgcc_generate_css_from_settings($cl['settings'])); ?></textarea>
                                            <textarea name="classes[<?php echo $i; ?>][css_custom]" rows="5" placeholder="Custom CSS"><?php echo isset($cl['settings']['_cssCustom']) ? esc_textarea($cl['settings']['_cssCustom']) : ''; ?></textarea>
                                        </td>
                                        <td>
                                            <button type="button" class="button button-danger remove-row">Remove</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr class="no-classes">
                                    <td colspan="5" style="text-align: center;">No classes added yet. Click "Add" to create a class.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5">
                                    <button type="button" class="button button-secondary" id="add-class">Add</button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </form>

        <div id="export-section" style="margin-top:30px;">
            <h2>Export Classes</h2>
            <p>Copy the CSS for all classes below to back up your class list:</p>
            <textarea readonly rows="15"><?php 
                $export_css = '';
                if (is_array($classes) && count($classes) > 0) {
                    foreach ($classes as $cl) {
                        if(isset($cl['settings'])) {
                            $export_css .= bgcc_generate_css_from_settings($cl['settings']) . "\n\n";
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
        const externalResourcesTable = document.getElementById('external-resources-table').querySelector('tbody');
        const bulkCssTextarea = document.getElementById('bulk-css');

        // Buttons
        const addCategoryBtn = document.getElementById('add-category');
        const addClassBtn = document.getElementById('add-class');
        const addExternalResourceBtn = document.getElementById('add-external-resource');
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
                    <button type="button" class="button button-danger remove-row">Remove</button>
                </td>`;
        });

        // Add new class row
        addClassBtn.addEventListener('click', () => {
            const noClassesMsg = classesTable.querySelector('.no-classes');
            if (noClassesMsg) {
                noClassesMsg.remove();
            }
            const idx = classesTable.rows.length;
            let options = '<option value="">- None -</option>';
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
                    <textarea name="classes[${idx}][css_generated]" rows="5" placeholder="Generated CSS" required></textarea>
                    <textarea name="classes[${idx}][css_custom]" rows="5" placeholder="Custom CSS"></textarea>
                </td>
                <td>
                    <button type="button" class="button button-danger remove-row">Remove</button>
                </td>`;
        });

        // Add new external resource row
        addExternalResourceBtn.addEventListener('click', () => {
            const idx = externalResourcesTable.rows.length;
            const noResourcesRow = externalResourcesTable.querySelector('.no-resources');
            if (noResourcesRow) {
                noResourcesRow.remove();
            }
            const row = externalResourcesTable.insertRow(-1);
            row.innerHTML = `
                <td>
                    <input type="text" name="external_resources[${idx}]" style="width: 90%;" placeholder="https://example.com/script.js">
                </td>
                <td>
                    <button type="button" class="button button-danger remove-row">Remove</button>
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
                        newRow.innerHTML = '<td colspan="5" style="text-align: center;">No classes added yet. Click "Add" to create a class.</td>';
                    }
                } else if (tbody === externalResourcesTable) {
                    if (!externalResourcesTable.rows.length) {
                        let newRow = externalResourcesTable.insertRow();
                        newRow.classList.add('no-resources');
                        newRow.innerHTML = '<td colspan="2" style="text-align: center;">No external resources added yet. Click "Add External Resource" to create one.</td>';
                    }
                }
            }
        });

        // Bulk CSS => Generate Classes
        generateClassesBtn.addEventListener('click', () => {
            const text = bulkCssTextarea.value.trim();
            if (!text) {
                alert('Please paste some CSS first.');
                return;
            }
            const noClassesMsg = classesTable.querySelector('.no-classes');
            if (noClassesMsg) {
                noClassesMsg.remove();
            }
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
            function processCssRules(cssText, wrapWithMedia) {
                const classRuleRegex = /((?:\.[A-Za-z0-9_\-]+\s*,\s*)*\.[A-Za-z0-9_\-]+)\s*\{([\s\S]*?)\}/g;
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
                        const className = selector.startsWith('.') ? selector.slice(1) : selector;
                        const idx = classesTable.rows.length;
                        let options = '<option value="">- None -</option>';
                        categories.forEach(c => {
                            options += `<option value="${c.id}">${c.name}</option>`;
                        });
                        let classCss = '.' + className + ' {\n' + rules + '\n}';
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
                                <textarea name="classes[${idx}][css_custom]" rows="5" placeholder="Custom CSS"></textarea>
                            </td>
                            <td>
                                <button type="button" class="button button-danger remove-row">Remove</button>
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
                newRow.innerHTML = '<td colspan="5" style="text-align: center;">No classes added yet. Click "Add" to create a class.</td>';
            }
        });

        // Bulk Change Category (Classes)
        bulkChangeCategoryBtn.addEventListener('click', () => {
            const newCategory = bulkCategorySelect.value;
            if (!newCategory) {
                alert('Please select a category.');
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

if (!is_admin()) {
    add_action('wp_head', 'bgcc_output_external_css');
    add_action('wp_footer', 'bgcc_output_external_js');
}

function bgcc_output_external_css() {
    $external_resources = get_option('bricks_global_classes_external_resources', []);
    if (is_array($external_resources) && count($external_resources) > 0) {
        foreach ($external_resources as $resource) {
            if (stripos($resource, '.css') !== false) {
                echo '<link rel="stylesheet" href="' . esc_url($resource) . '" />' . "\n";
            }
        }
    }
}

function bgcc_output_external_js() {
    $external_resources = get_option('bricks_global_classes_external_resources', []);
    if (is_array($external_resources) && count($external_resources) > 0) {
        foreach ($external_resources as $resource) {
            if (stripos($resource, '.js') !== false) {
                echo '<script src="' . esc_url($resource) . '"></script>' . "\n";
            }
        }
    }
}
?>
