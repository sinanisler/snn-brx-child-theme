<?php

add_action('admin_menu', function () {
    add_submenu_page(
        'snn-settings',
        'Bricks Global Classes Manager',
        'Bricks Global Classes',
        'manage_options',
        'bricks-global-classes',
        'bgcc_page',
        99
    );
});

add_action('admin_init', function () {

    if (isset($_POST['bgcc_save_all']) && wp_verify_nonce($_POST['bgcc_nonce'], 'bgcc_save_all')) {

        // Process Categories
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

        // Process Classes
        $existing_classes = get_option('bricks_global_classes', []);
        $oldClassById     = [];
        foreach ($existing_classes as $cl) {
            $oldClassById[$cl['id']] = $cl;
        }
        $classes_json  = $_POST['bgcc_classes_data'] ?? '';
        $postedClasses = json_decode(stripslashes($classes_json), true);
        $new_classes   = [];

        if ($postedClasses && is_array($postedClasses)) {
            foreach ($postedClasses as $cl) {
                $classId   = !empty($cl['id']) ? sanitize_text_field($cl['id']) : bgcc_rand_id();
                $className = !empty($cl['name']) ? sanitize_text_field($cl['name']) : '';
                $catId     = isset($cl['category']) ? sanitize_text_field($cl['category']) : '';

                if ($className) {
                    $parsedSettings = bgcc_parse_css($cl['css'] ?? '');
                    if (isset($oldClassById[$classId])) {
                        $oldSettings = $oldClassById[$classId]['settings'] ?? [];
                        // Keep any existing color references (IDs/names) if they exist
                        if (!empty($oldSettings['_typography']['color']) && !empty($parsedSettings['_typography']['color'])) {
                            $oldColor = $oldSettings['_typography']['color'];
                            $parsedSettings['_typography']['color']['id']   = $oldColor['id'];
                            $parsedSettings['_typography']['color']['name'] = $oldColor['name'];
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
        }

        update_option('bricks_global_classes', $new_classes);

        // Process Variables
        $variables_json = $_POST['bgcc_variables_data'] ?? '';
        $postedVariables = json_decode(stripslashes($variables_json), true);
        $new_variables   = [];
        if ($postedVariables && is_array($postedVariables)) {
            foreach ($postedVariables as $var) {
                $varId    = !empty($var['id']) ? sanitize_text_field($var['id']) : bgcc_rand_id();
                $varName  = !empty($var['name']) ? sanitize_text_field($var['name']) : '';
                $varValue = isset($var['value']) ? sanitize_text_field($var['value']) : '';
                $varName  = trim($varName);
                // Only import valid CSS variables that start with "--"
                if ($varName && substr($varName, 0, 2) === '--') {
                    $new_variables[] = [
                        'id'    => $varId,
                        'name'  => $varName,
                        'value' => $varValue,
                    ];
                }
            }
        }
        update_option('bricks_global_variables', $new_variables);

        // Process inline setting
        $inline = isset($_POST['bgcc_inline']) ? 1 : 0;
        update_option('bricks_global_classes_inline', $inline);

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

function bgcc_parse_css($css) {
    $settings = [];
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

    // Grab everything between braces if possible
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
        list($p, $v) = array_map('trim', explode(':', $r, 2));
        switch (strtolower($p)) {
            case 'color':
                $hex = sanitize_hex_color($v);
                if ($hex === null) {
                    $hex = sanitize_text_field($v);
                }
                $settings['_typography']['color'] = [
                    'hex'  => $hex,
                    'id'   => bgcc_rand_id(),
                    'name' => 'Custom Color'
                ];
                break;
            case 'background-color':
                $hex = sanitize_hex_color($v);
                if ($hex === null) {
                    $hex = sanitize_text_field($v);
                }
                $settings['_background']['color'] = [
                    'hex'  => $hex,
                    'id'   => bgcc_rand_id(),
                    'name' => 'Custom BG'
                ];
                break;
            case 'text-align':
                if (in_array(strtolower($v), ['left', 'center', 'right', 'justify'])) {
                    $settings['_typography']['text-align'] = strtolower($v);
                }
                break;
            case 'text-transform':
                if (in_array(strtolower($v), ['none', 'capitalize', 'uppercase', 'lowercase'])) {
                    $settings['_typography']['text-transform'] = strtolower($v);
                }
                break;
            case 'font-weight':
                $settings['_typography']['font-weight'] = sanitize_text_field($v);
                break;
            case 'border':
                $settings['_border']['border'] = sanitize_text_field($v);
                break;
            default:
                // Store any unknown/unsupported props in _custom_css
                $settings['_custom_css'][sanitize_text_field($p)] = sanitize_text_field($v);
        }
    }
    return $settings;
}

function bgcc_gen_css($name, $s) {
    $css = '.' . $name . " {\n";
    if (!empty($s['_typography'])) {
        if (!empty($s['_typography']['text-align'])) {
            $css .= "  text-align: {$s['_typography']['text-align']};\n";
        }
        if (!empty($s['_typography']['text-transform'])) {
            $css .= "  text-transform: {$s['_typography']['text-transform']};\n";
        }
        if (!empty($s['_typography']['color']['hex'])) {
            $css .= "  color: {$s['_typography']['color']['hex']};\n";
        }
        if (!empty($s['_typography']['font-weight'])) {
            $css .= "  font-weight: {$s['_typography']['font-weight']};\n";
        }
    }
    if (!empty($s['_background']['color']['hex'])) {
        $css .= "  background-color: {$s['_background']['color']['hex']};\n";
    }
    if (!empty($s['_border']['border'])) {
        $css .= "  border: {$s['_border']['border']};\n";
    }
    if (!empty($s['_custom_css']) && is_array($s['_custom_css'])) {
        foreach ($s['_custom_css'] as $p => $v) {
            $css .= "  {$p}: {$v};\n";
        }
    }
    $css .= "}";

    // Append any @keyframes blocks for this class
    if (!empty($s['_keyframes']) && is_array($s['_keyframes'])) {
        foreach ($s['_keyframes'] as $keyframesBlock) {
            $css .= "\n\n" . $keyframesBlock;
        }
    }
    return $css;
}

function bgcc_page() {
    $categories   = get_option('bricks_global_classes_categories', []);
    $classes      = get_option('bricks_global_classes', []);
    $variables    = get_option('bricks_global_variables', []);
    $load_inline  = get_option('bricks_global_classes_inline', 0);
    ?>
    <style>
        /* Grid layout: left section fixed at 300px and right section takes the remaining width */
        #bgcc-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
        }
        #categories-section {
            width: 300px;
        }
        /* Tabs styling for right section */
        #right-tabs {
            border: 1px solid #ccc;
        }
        #right-tabs ul.tabs {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            border-bottom: 1px solid #ccc;
        }
        #right-tabs ul.tabs li {
            padding: 10px 20px;
            cursor: pointer;
        }
        #right-tabs ul.tabs li.active {
            background: #f1f1f1;
            font-weight: bold;
        }
        .tab-content {
            display: none;
            padding: 20px;
        }
        .tab-content.active {
            display: block;
        }
        /* Ensure inputs and textareas take proper width */
        #categories-table input[type="text"],
        #classes-table textarea,
        #variables-table input[type="text"],
        #bulk-css,
        #bulk-variables {
            width: 90%;
        }
        #classes-table textarea {
            height: 100px;
        }
        /* Export textarea styling */
        #export-section textarea,
        #export-variables-section textarea {
            width: 100%;
            font-family: monospace;
        }
        /* Style for the "no data" rows */
        .no-classes td,
        .no-resources td,
        .no-variables td {
            padding: 15px;
            font-style: italic;
            color: #555;
        }
        /* Top save button styling */
        #top-save {
            margin-bottom: 10px;
        }
        /* Inline setting styling */
        .inline-setting {
            margin-bottom: 20px;
        }
        .inline-setting label {
            font-weight: bold;
        }
        .inline-setting p.description {
            font-size: 0.9em;
            color: #555;
            margin: 5px 0 0;
        }
    </style>

    <div class="wrap">
        <h1>Bricks Global Class and CSS Manager</h1>
        <?php settings_errors('bgcc_messages'); ?>

        <form method="post" id="bgcc-main-form">
            <?php wp_nonce_field('bgcc_save_all', 'bgcc_nonce'); ?>

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
                            <?php if ($categories) : ?>
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

                    <div class="inline-setting" style="margin-top:40px">
                        <label for="bgcc_inline">
                            <input type="checkbox" name="bgcc_inline" id="bgcc_inline" <?php checked($load_inline, 1); ?>>
                            Load global classes CSS inline on frontend
                        </label>
                        <p class="desc">When enabled, all global classes will be output in minified form in the frontend.</p>
                    </div>

                    <!-- External Resources Section -->
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
                                <?php
                                $external_resources = get_option('bricks_global_classes_external_resources', []);
                                if ($external_resources) :
                                    foreach ($external_resources as $i => $resource) : ?>
                                        <tr>
                                            <td>
                                                <input type="text" name="external_resources[<?php echo $i; ?>]" value="<?php echo esc_attr($resource); ?>" style="width: 90%;" placeholder="https://example.com/script.js">
                                            </td>
                                            <td>
                                                <button type="button" class="button button-danger remove-row">Remove</button>
                                            </td>
                                        </tr>
                                    <?php endforeach;
                                else : ?>
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

                    <p id="top-save"><?php submit_button('Save All', 'primary', 'bgcc_save_all', false); ?></p>
                </div>

                <div id="right-tabs">
                    <ul class="tabs">
                        <li class="tab active" data-tab="classes-tab">Classes</li>
                        <li class="tab" data-tab="variables-tab">Variables</li>
                    </ul>
                    <!-- CLASSES TAB -->
                    <div id="classes-tab" class="tab-content active">
                        <h2>Classes</h2>
                        <!-- Bulk CSS block -->
                        <div style="margin-bottom:20px;">
                            <h3>Bulk CSS</h3>
                            <p>Paste multiple CSS class definitions here (e.g. <code>.my-class { color: red; }</code>) and click "Generate Classes".<br>Multiple selectors (comma-separated) and <code>@keyframes</code> blocks are supported.</p>
                            <textarea id="bulk-css" rows="4" style="width:100%; font-family:monospace;"></textarea>
                            <br><br>
                            <button type="button" class="button button-secondary" id="generate-classes">Generate Classes</button>
                        </div>
                        <!-- Bulk actions for classes -->
                        <div id="bulk-actions" style="margin-bottom:10px;">
                            <button type="button" class="button" id="bulk-delete">Delete Selected</button>
                            <select id="bulk-category">
                                <option value="">— Change Category To —</option>
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
                                    <th>CSS</th>
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
                                                    <option value="">— None —</option>
                                                    <?php foreach ($categories as $cat) : ?>
                                                        <option value="<?php echo esc_attr($cat['id']); ?>" <?php selected($cl['category'], $cat['id']); ?>>
                                                            <?php echo esc_html($cat['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td>
                                                <textarea name="classes[<?php echo $i; ?>][css]" rows="5" placeholder=".classname { /* CSS */ }" required><?php echo esc_textarea(bgcc_gen_css($cl['name'], $cl['settings'])); ?></textarea>
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
                    <!-- VARIABLES TAB -->
                    <div id="variables-tab" class="tab-content">
                        <h2>Variables</h2>
                        <!-- Bulk Variables block -->
                        <div style="margin-bottom:20px;">
                            <h3>Bulk Variables</h3>
                            <p>
                                Paste multiple variable definitions here (<code>--my-var: #cccccc;</code>) and click "Generate Variables".<br>
                                Use the format: <code>--variableName: value;</code> (one per line).
                            </p>
                            <textarea id="bulk-variables" rows="4" style="width:100%; font-family:monospace;"></textarea>
                            <br><br>
                            <button type="button" class="button button-secondary" id="generate-variables">Generate Variables</button>
                        </div>
                        <!-- Bulk actions for variables -->
                        <div id="bulk-actions-variables" style="margin-bottom:10px;">
                            <button type="button" class="button" id="bulk-delete-variables">Delete Selected</button>
                            <input type="text" id="bulk-search-variables" placeholder="Search variables..." style="margin-left:20px; padding-left:5px;">
                        </div>
                        <!-- Variables table -->
                        <table class="widefat fixed" id="variables-table">
                            <thead>
                                <tr>
                                    <th style="width:30px"><input type="checkbox" id="select-all-variables"></th>
                                    <th>Name</th>
                                    <th>Value</th>
                                    <th style="width:100px">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($variables && count($variables) > 0) : ?>
                                    <?php foreach ($variables as $i => $var) : ?>
                                        <tr>
                                            <td><input type="checkbox" class="bulk-select-variable"></td>
                                            <td>
                                                <input type="hidden" name="variables[<?php echo $i; ?>][id]" value="<?php echo esc_attr($var['id']); ?>">
                                                <input type="text" name="variables[<?php echo $i; ?>][name]" value="<?php echo esc_attr($var['name']); ?>" required>
                                            </td>
                                            <td>
                                                <input type="text" name="variables[<?php echo $i; ?>][value]" value="<?php echo esc_attr($var['value']); ?>" required>
                                            </td>
                                            <td>
                                                <button type="button" class="button button-danger remove-row">Remove</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr class="no-variables">
                                        <td colspan="4" style="text-align: center;">No variables added yet. Click "Add" to create a variable.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4">
                                        <button type="button" class="button button-secondary" id="add-variable">Add</button>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <input type="hidden" name="bgcc_classes_data" id="bgcc_classes_data" value="">
            <input type="hidden" name="bgcc_variables_data" id="bgcc_variables_data" value="">
        </form>

        <div id="export-section" style="margin-top:30px;">
            <h2>Export Classes</h2>
            <p>Copy the CSS for all classes below to back up your class list:</p>
            <textarea readonly rows="15"><?php 
                $export_css = '';
                if ($classes) {
                    foreach ($classes as $cl) {
                        $export_css .= bgcc_gen_css($cl['name'], $cl['settings']) . "\n\n";
                    }
                }
                echo esc_textarea($export_css);
            ?></textarea>
        </div>

        <div id="export-variables-section" style="margin-top:30px;">
            <h2>Export Variables</h2>
            <p>Copy the variable definitions below to back up your variables:</p>
            <textarea readonly rows="10"><?php 
                $export_variables = '';
                if ($variables) {
                    foreach ($variables as $var) {
                        $export_variables .= $var['name'] . ': ' . $var['value'] . ";\n";
                    }
                }
                echo esc_textarea($export_variables);
            ?></textarea>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // DOM elements
        const categoriesTable = document.getElementById('categories-table').querySelector('tbody');
        const classesTable = document.getElementById('classes-table').querySelector('tbody');
        const externalResourcesTable = document.getElementById('external-resources-table').querySelector('tbody');
        const variablesTable = document.getElementById('variables-table').querySelector('tbody');

        // Buttons
        const addCategoryBtn = document.getElementById('add-category');
        const addClassBtn = document.getElementById('add-class');
        const addVariableBtn = document.getElementById('add-variable');
        const addExternalResourceBtn = document.getElementById('add-external-resource');
        const generateClassesBtn = document.getElementById('generate-classes');
        const generateVariablesBtn = document.getElementById('generate-variables');

        // Bulk actions: Classes
        const bulkDeleteBtn = document.getElementById('bulk-delete');
        const bulkChangeCategoryBtn = document.getElementById('bulk-change-category');
        const bulkCategorySelect = document.getElementById('bulk-category');
        const selectAllCheckbox = document.getElementById('select-all');
        const bulkSearchInput = document.getElementById('bulk-search');

        // Bulk actions: Variables
        const bulkDeleteVariablesBtn = document.getElementById('bulk-delete-variables');
        const selectAllVariablesCheckbox = document.getElementById('select-all-variables');
        const bulkSearchVariablesInput = document.getElementById('bulk-search-variables');

        // Hidden form fields
        const mainForm = document.getElementById('bgcc-main-form');
        const classesDataInput = document.getElementById('bgcc_classes_data');
        const variablesDataInput = document.getElementById('bgcc_variables_data');

        // Bulk textareas
        const bulkCssTextarea = document.getElementById('bulk-css');
        const bulkVariablesTextarea = document.getElementById('bulk-variables');

        // Tabs
        const tabs = document.querySelectorAll('#right-tabs ul.tabs li');
        const tabContents = document.querySelectorAll('.tab-content');

        // Category data from PHP
        const categories = <?php echo json_encode(array_map(function($c) {
            return ['id' => $c['id'], 'name' => $c['name']];
        }, $categories)); ?>;

        // Helper: random ID generator
        function bgccRandId(len = 6) {
            return [...Array(len)]
                .map(() => 'abcdefghijklmnopqrstuvwxyz0123456789'[Math.floor(Math.random() * 36)])
                .join('');
        }

        // Helpers: update "no data" rows
        function updateClassesEmptyState() {
            if (classesTable.rows.length === 0) {
                let row = classesTable.insertRow();
                row.classList.add('no-classes');
                row.innerHTML = '<td colspan="5" style="text-align: center;">No classes added yet. Click "Add" to create a class.</td>';
            }
        }
        function updateVariablesEmptyState() {
            if (variablesTable.rows.length === 0) {
                let row = variablesTable.insertRow();
                row.classList.add('no-variables');
                row.innerHTML = '<td colspan="4" style="text-align: center;">No variables added yet. Click "Add" to create a variable.</td>';
            }
        }
        function updateExternalResourcesEmptyState() {
            if (externalResourcesTable.rows.length === 0) {
                let row = externalResourcesTable.insertRow();
                row.classList.add('no-resources');
                row.innerHTML = '<td colspan="2" style="text-align: center;">No external resources added yet. Click "Add External Resource" to create one.</td>';
            }
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
            const row = classesTable.insertRow(-1);
            let options = '<option value="">— None —</option>';
            categories.forEach(c => {
                options += `<option value="${c.id}">${c.name}</option>`;
            });
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
                    <textarea name="classes[${idx}][css]" rows="5" placeholder=".classname { /* CSS */ }" required></textarea>
                </td>
                <td>
                    <button type="button" class="button button-danger remove-row">Remove</button>
                </td>`;
        });

        // Add new variable row
        addVariableBtn.addEventListener('click', () => {
            const noVariablesMsg = variablesTable.querySelector('.no-variables');
            if (noVariablesMsg) {
                noVariablesMsg.remove();
            }
            const idx = variablesTable.rows.length;
            const row = variablesTable.insertRow(-1);
            row.innerHTML = `
                <td>
                    <input type="checkbox" class="bulk-select-variable">
                </td>
                <td>
                    <input type="hidden" name="variables[${idx}][id]" value="${bgccRandId()}">
                    <input type="text" name="variables[${idx}][name]" required>
                </td>
                <td>
                    <input type="text" name="variables[${idx}][value]" required>
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

        // Handle remove-row clicks
        document.body.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-row')) {
                const row = e.target.closest('tr');
                const tbody = row.closest('tbody');
                row.remove();
                if (tbody === classesTable) {
                    updateClassesEmptyState();
                } else if (tbody === variablesTable) {
                    updateVariablesEmptyState();
                } else if (tbody === externalResourcesTable) {
                    updateExternalResourcesEmptyState();
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
            // Regex that captures class definitions and optional keyframes after
            const regex = /((?:\.[A-Za-z0-9_\-]+\s*,\s*)*\.[A-Za-z0-9_\-]+)\s*\{([\s\S]*?)\}\s*(?:(@keyframes\s+[A-Za-z0-9_-]+\s*\{[\s\S]*?\}))?/g;
            let match, found = 0;
            while ((match = regex.exec(text)) !== null) {
                found++;
                const selectors = match[1].split(',').map(s => s.trim());
                const rules = match[2].trim();
                const keyframesBlock = match[3] ? match[3].trim() : '';
                selectors.forEach(selector => {
                    const className = selector.startsWith('.') ? selector.slice(1) : selector;
                    const idx = classesTable.rows.length;
                    const row = classesTable.insertRow(-1);
                    let options = '<option value="">— None —</option>';
                    categories.forEach(c => {
                        options += `<option value="${c.id}">${c.name}</option>`;
                    });
                    let finalCSS = '.' + className + ' {\n' + rules + '\n}';
                    if(keyframesBlock){
                        finalCSS += "\n\n" + keyframesBlock;
                    }
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
                            <textarea name="classes[${idx}][css]" rows="5" required>${finalCSS}</textarea>
                        </td>
                        <td>
                            <button type="button" class="button button-danger remove-row">Remove</button>
                        </td>`;
                });
            }
            // alert(found + ' CSS block(s) generated from Bulk CSS.');
        });

        // Bulk Variables => Generate Variables
        generateVariablesBtn.addEventListener('click', () => {
            const text = bulkVariablesTextarea.value.trim();
            if (!text) {
                alert('Please paste some variables first.');
                return;
            }
            const noVariablesMsg = variablesTable.querySelector('.no-variables');
            if (noVariablesMsg) {
                noVariablesMsg.remove();
            }
            // Regex to capture lines like "--my-var: #cccccc;"
            const regex = /([\w-]+)\s*:\s*([^;]+);?/g;
            let match, found = 0;
            while ((match = regex.exec(text)) !== null) {
                const varName = match[1].trim();
                const varValue = match[2].trim();

                // Only process if it starts with --
                if (!varName.startsWith('--')) {
                    // Skip lines that are not valid CSS variables
                    continue;
                }

                found++;
                const idx = variablesTable.rows.length;
                const row = variablesTable.insertRow(-1);
                row.innerHTML = `
                    <td>
                        <input type="checkbox" class="bulk-select-variable">
                    </td>
                    <td>
                        <input type="hidden" name="variables[${idx}][id]" value="${bgccRandId()}">
                        <input type="text" name="variables[${idx}][name]" value="${varName}" required>
                    </td>
                    <td>
                        <input type="text" name="variables[${idx}][value]" value="${varValue}" required>
                    </td>
                    <td>
                        <button type="button" class="button button-danger remove-row">Remove</button>
                    </td>`;
            }
            // alert(found + ' variable(s) generated from Bulk Variables.');
        });

        // Bulk Delete (Classes)
        bulkDeleteBtn.addEventListener('click', () => {
            const checkboxes = classesTable.querySelectorAll('input.bulk-select:checked');
            checkboxes.forEach(cb => {
                const row = cb.closest('tr');
                row.remove();
            });
            updateClassesEmptyState();
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

        // Bulk Delete (Variables)
        bulkDeleteVariablesBtn.addEventListener('click', () => {
            const varCheckboxes = variablesTable.querySelectorAll('input.bulk-select-variable:checked');
            varCheckboxes.forEach(cb => {
                const row = cb.closest('tr');
                row.remove();
            });
            updateVariablesEmptyState();
        });

        // Select all (Variables)
        selectAllVariablesCheckbox.addEventListener('change', function(){
            const varRows = variablesTable.querySelectorAll('tbody tr');
            varRows.forEach(row => {
                if(row.style.display !== 'none'){
                    const cb = row.querySelector('input.bulk-select-variable');
                    if(cb){
                        cb.checked = this.checked;
                    }
                }
            });
        });

        // Variables search
        bulkSearchVariablesInput.addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            const varRows = variablesTable.querySelectorAll('tbody tr');
            varRows.forEach(row => {
                if (row.classList.contains('no-variables')) return;
                const nameInput = row.querySelector('input[name*="[name]"]');
                if (nameInput) {
                    const text = nameInput.value.toLowerCase();
                    row.style.display = (text.indexOf(filter) !== -1) ? '' : 'none';
                }
            });
        });

        // Tabs functionality
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(tc => tc.classList.remove('active'));
                this.classList.add('active');
                document.getElementById(this.getAttribute('data-tab')).classList.add('active');
            });
        });

        // Before form submit, assemble JSON data for classes & variables
        mainForm.addEventListener('submit', function(e) {
            // Gather classes data
            let classesData = [];
            const classRows = classesTable.querySelectorAll('tbody tr');
            classRows.forEach(row => {
                if (row.classList.contains('no-classes')) return;
                const idInput = row.querySelector('input[name*="[id]"]');
                const nameInput = row.querySelector('input[name*="[name]"]');
                const categorySelect = row.querySelector('select[name*="[category]"]');
                const cssTextarea = row.querySelector('textarea[name*="[css]"]');
                if (!idInput || !nameInput || !categorySelect || !cssTextarea) return;
                classesData.push({
                    id: idInput.value,
                    name: nameInput.value,
                    category: categorySelect.value,
                    css: cssTextarea.value
                });
                // Disable these so they don't send as standard form fields
                idInput.disabled = true;
                nameInput.disabled = true;
                categorySelect.disabled = true;
                cssTextarea.disabled = true;
            });
            classesDataInput.value = JSON.stringify(classesData);

            // Gather variables data
            let variablesData = [];
            const variableRows = variablesTable.querySelectorAll('tbody tr');
            variableRows.forEach(row => {
                if (row.classList.contains('no-variables')) return;
                const idInput = row.querySelector('input[name*="[id]"]');
                const nameInput = row.querySelector('input[name*="[name]"]');
                const valueInput = row.querySelector('input[name*="[value]"]');
                if (!idInput || !nameInput || !valueInput) return;
                variablesData.push({
                    id: idInput.value,
                    name: nameInput.value,
                    value: valueInput.value
                });
                // Disable these so they don't send as standard form fields
                idInput.disabled = true;
                nameInput.disabled = true;
                valueInput.disabled = true;
            });
            variablesDataInput.value = JSON.stringify(variablesData);
        });
    });
    </script>
    <?php
}

// FRONTEND OUTPUT
//----------------------------------------------------------------------------------

if (!is_admin()) {
    // Output inline CSS for global classes in footer
    add_action('wp_footer', 'bgcc_output_inline_css');
    // Output external CSS resources in head
    add_action('wp_head', 'bgcc_output_external_css');
    // Output external JS resources in footer
    add_action('wp_footer', 'bgcc_output_external_js');
}

function bgcc_output_inline_css() {
    if (get_option('bricks_global_classes_inline', 0)) {
        $classes = get_option('bricks_global_classes', []);
        $css = '';
        if ($classes) {
            foreach ($classes as $cl) {
                $css .= bgcc_gen_css($cl['name'], $cl['settings']);
            }
        }
        // Minify by collapsing multiple spaces
        $minified = preg_replace('/\s+/', ' ', $css);
        echo '
        

<style id="bgcc-inline-css" class="snn-global-classes-frontend">
' . $minified . '
</style>


';
    }
}

function bgcc_output_external_css() {
    $external_resources = get_option('bricks_global_classes_external_resources', []);
    if ($external_resources && is_array($external_resources)) {
        foreach ($external_resources as $resource) {
            // If it ends with .css (case-insensitive), load as CSS
            if (stripos($resource, '.css') !== false) {
                echo '<link rel="stylesheet" href="' . esc_url($resource) . '" />' . "\n";
            }
        }
    }
}

function bgcc_output_external_js() {
    $external_resources = get_option('bricks_global_classes_external_resources', []);
    if ($external_resources && is_array($external_resources)) {
        foreach ($external_resources as $resource) {
            // If it ends with .js (case-insensitive), load as JS
            if (stripos($resource, '.js') !== false) {
                echo '<script src="' . esc_url($resource) . '"></script>' . "\n";
            }
        }
    }
}
?>
