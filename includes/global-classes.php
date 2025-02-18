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

    // Process Categories Save
    if (isset($_POST['bgcc_categories_submit']) && wp_verify_nonce($_POST['bgcc_categories_nonce'], 'bgcc_save_categories')) {
        $postedCategories = $_POST['categories'] ?? null;
        $new_categories   = [];

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
        add_settings_error('bgcc_messages', 'bgcc_categories_message', 'Categories Saved', 'updated');
        wp_redirect(add_query_arg(['page' => 'bricks-global-classes', 'categories-updated' => 'true'], admin_url('admin.php')));
        exit;
    }

    // Process Classes Save
    if (isset($_POST['bgcc_classes_submit']) && wp_verify_nonce($_POST['bgcc_classes_nonce'], 'bgcc_save_classes')) {
        $existing_classes = get_option('bricks_global_classes', []);
        $oldClassById     = [];
        foreach ($existing_classes as $cl) {
            $oldClassById[$cl['id']] = $cl;
        }
        $postedClasses = $_POST['classes'] ?? null;
        $new_classes   = [];

        if ($postedClasses && is_array($postedClasses)) {
            foreach ($postedClasses as $cl) {
                $classId   = !empty($cl['id']) ? sanitize_text_field($cl['id']) : bgcc_rand_id();
                $className = !empty($cl['name']) ? sanitize_text_field($cl['name']) : '';
                // Allow category to be empty so that classes can be saved without a category.
                $catId     = isset($cl['category']) ? sanitize_text_field($cl['category']) : '';

                if ($className) {
                    $parsedSettings = bgcc_parse_css($cl['css'] ?? '');
                    if (isset($oldClassById[$classId])) {
                        $oldSettings = $oldClassById[$classId]['settings'] ?? [];
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
        add_settings_error('bgcc_messages', 'bgcc_classes_message', 'Classes Saved', 'updated');
        wp_redirect(add_query_arg(['page' => 'bricks-global-classes', 'classes-updated' => 'true'], admin_url('admin.php')));
        exit;
    }
});

function bgcc_rand_id($len = 6) {
    return substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, $len);
}

function bgcc_parse_css($css) {
    $settings = [];
    $css = preg_replace('/\/\*.*?\*\//s', '', trim($css));
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
                if ($hex === null) { // allow named colors or non-hex values
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
    $css .= '}';
    return $css;
}

function bgcc_page() {
    $categories = get_option('bricks_global_classes_categories', []);
    $classes    = get_option('bricks_global_classes', []);
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
        /* Ensure inputs and textareas take proper width */
        #categories-table input[type="text"],
        #classes-table textarea {
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
        /* Style for the no classes message */
        .no-classes td {
            padding: 15px;
            font-style: italic;
            color: #555;
        }
    </style>

    <div class="wrap">
        <h1>Bricks Global Classes Manager - <span style="color:Red">EXPERIMENTAL</span></h1>
        <?php settings_errors('bgcc_messages'); ?>

        <div id="bgcc-container">
            <!-- Categories Section -->
            <div id="categories-section">
                <h2>Categories</h2>
                <form method="post">
                    <?php wp_nonce_field('bgcc_save_categories', 'bgcc_categories_nonce'); ?>
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
                    <?php submit_button('Save Categories', 'primary', 'bgcc_categories_submit'); ?>
                </form>
            </div>

            <!-- Classes Section -->
            <div id="classes-section">
                <h2>Classes</h2>
                <div style="margin-bottom:20px;">
                    <h3>Bulk CSS</h3>
                    <p>
                        Paste multiple CSS class definitions here (e.g. <code>.my-class { color: red; }</code>)
                        and click "Generate Classes".
                    </p>
                    <textarea id="bulk-css" rows="4" style="width:100%; font-family:monospace;"></textarea>
                    <br><br>
                    <button type="button" class="button button-secondary" id="generate-classes">Generate Classes</button>
                </div>
                <!-- Bulk Actions for Classes -->
                <div id="bulk-actions" style="margin-bottom:10px;">
                    <button type="button" class="button" id="bulk-delete">Delete Selected</button>
                    <select id="bulk-category">
                        <option value="">— Change Category To —</option>
                        <?php foreach ($categories as $cat) : ?>
                            <option value="<?php echo esc_attr($cat['id']); ?>"><?php echo esc_html($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="button" id="bulk-change-category">Apply</button>
                </div>
                <form method="post">
                    <?php wp_nonce_field('bgcc_save_classes', 'bgcc_classes_nonce'); ?>
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
                    <?php submit_button('Save Classes', 'primary', 'bgcc_classes_submit'); ?>
                </form>
            </div>
        </div>

        <!-- Export Section -->
        <div id="export-section" style="margin-top:30px;">
            <h2>Export</h2>
            <p>Copy the CSS for all classes below to backup your class list:</p>
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
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const categoriesTable = document.getElementById('categories-table').querySelector('tbody');
        const classesTable = document.getElementById('classes-table').querySelector('tbody');
        const addCategoryBtn = document.getElementById('add-category');
        const addClassBtn = document.getElementById('add-class');
        const generateBtn = document.getElementById('generate-classes');
        const bulkCssTextarea = document.getElementById('bulk-css');
        const bulkDeleteBtn = document.getElementById('bulk-delete');
        const bulkChangeCategoryBtn = document.getElementById('bulk-change-category');
        const bulkCategorySelect = document.getElementById('bulk-category');
        const selectAllCheckbox = document.getElementById('select-all');
        const categories = <?php echo json_encode(array_map(function($c) {
            return ['id' => $c['id'], 'name' => $c['name']];
        }, $categories)); ?>;
    
        function bgccRandId(len = 6) {
            return [...Array(len)]
                .map(() => 'abcdefghijklmnopqrstuvwxyz0123456789'[Math.floor(Math.random() * 36)])
                .join('');
        }
        
        // Helper to update the classes table empty state.
        function updateClassesEmptyState() {
            if (classesTable.rows.length === 0) {
                let row = classesTable.insertRow();
                row.classList.add('no-classes');
                row.innerHTML = '<td colspan="5" style="text-align: center;">No classes added yet. Click "Add" to create a class.</td>';
            }
        }
    
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
    
        addClassBtn.addEventListener('click', () => {
            // Remove no-classes message if present.
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
    
        document.body.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-row')) {
                const row = e.target.closest('tr');
                row.remove();
                // If the removed row is in the classes table, update empty state.
                if (row.closest('tbody') === classesTable) {
                    updateClassesEmptyState();
                }
            }
        });
    
        generateBtn.addEventListener('click', () => {
            const text = bulkCssTextarea.value.trim();
            if (!text) {
                alert('Please paste some CSS first.');
                return;
            }
            // Remove no-classes message if present.
            const noClassesMsg = classesTable.querySelector('.no-classes');
            if (noClassesMsg) {
                noClassesMsg.remove();
            }
            const regex = /\.([A-Za-z0-9_\-]+)\s*\{([^}]*)\}/g;
            let match, found = 0;
            while ((match = regex.exec(text)) !== null) {
                found++;
                const className = match[1].trim();
                const rules = match[2].trim();
                const idx = classesTable.rows.length;
                const row = classesTable.insertRow(-1);
                let options = '<option value="">— None —</option>';
                categories.forEach(c => {
                    options += `<option value="${c.id}">${c.name}</option>`;
                });
                const finalCSS = '.' + className + ' {\n' + rules + '\n}';
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
            }
            alert(found + ' classes generated from Bulk CSS.');
        });
    
        bulkDeleteBtn.addEventListener('click', () => {
            const checkboxes = classesTable.querySelectorAll('input.bulk-select:checked');
            checkboxes.forEach(cb => {
                const row = cb.closest('tr');
                row.remove();
            });
            updateClassesEmptyState();
        });
    
        bulkChangeCategoryBtn.addEventListener('click', () => {
            const newCategory = bulkCategorySelect.value;
            if (!newCategory) {
                alert('Please select a category.');
                return;
            }
            const checkboxes = classesTable.querySelectorAll('input.bulk-select:checked');
            checkboxes.forEach(cb => {
                const row = cb.closest('tr');
                const select = row.querySelector('select[name^="classes"]');
                if (select) {
                    select.value = newCategory;
                }
            });
        });
    
        selectAllCheckbox.addEventListener('change', function(){
            const checkboxes = classesTable.querySelectorAll('input.bulk-select');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
    });
    </script>
    <?php
}
?>
