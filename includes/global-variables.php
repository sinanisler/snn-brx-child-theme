<?php
// Will be deprecated
add_action('admin_menu', function () {
    add_submenu_page(
        'snn-settings',
        __('Global Variables Manager', 'snn'),
        __('Global Variables', 'snn'),
        'manage_options',
        'bricks-global-variables',
        'bgcc_variables_page',
        100
    );
});

// Process both categories and variables on form submission.
add_action('admin_init', function () {
    if (isset($_POST['bgcc_variables_save']) && wp_verify_nonce($_POST['bgcc_variables_nonce'], 'bgcc_variables_save')) {

        // Process Categories
        if (isset($_POST['categories']) && is_array($_POST['categories'])) {
            $postedCategories = $_POST['categories'];
            $new_categories = [];
            foreach ($postedCategories as $cat) {
                $catId   = !empty($cat['id']) ? sanitize_text_field($cat['id']) : bgcc_rand_id();
                $catName = !empty($cat['name']) ? sanitize_text_field($cat['name']) : '';
                $catName = trim($catName);
                if ($catName !== '') {
                    $new_categories[] = [
                        'id'   => $catId,
                        'name' => $catName,
                    ];
                }
            }
            update_option('bricks_global_variables_categories', $new_categories);
        }

        // Process Variables
        if (!empty($_POST['variables_json'])) {
            $postedVariables = json_decode(stripslashes($_POST['variables_json']), true);
        } elseif (isset($_POST['variables']) && is_array($_POST['variables'])) {
            $postedVariables = $_POST['variables'];
        } else {
            $postedVariables = [];
        }
        $new_variables = [];
        if ($postedVariables && is_array($postedVariables)) {
            foreach ($postedVariables as $var) {
                $varId       = !empty($var['id']) ? sanitize_text_field($var['id']) : bgcc_rand_id();
                $varName     = !empty($var['name']) ? sanitize_text_field($var['name']) : '';
                $varValue    = isset($var['value']) ? sanitize_text_field($var['value']) : '';
                $varCategory = isset($var['category']) ? sanitize_text_field($var['category']) : '';
                $varName     = trim($varName);
                $varValue    = trim($varValue);
                if ($varName !== '' && $varValue !== '' && substr($varName, 0, 2) === '--') {
                    $new_variables[] = [
                        'id'       => $varId,
                        'name'     => $varName,
                        'value'    => $varValue,
                        'category' => $varCategory,
                    ];
                }
            }
        }
        update_option('bricks_global_variables', $new_variables);
        add_settings_error('bgcc_messages', 'bgcc_save_message', __('Variables and Categories Saved', 'snn'), 'updated');
        wp_redirect(add_query_arg(['page' => 'bricks-global-variables', 'updated' => 'true'], admin_url('admin.php')));
        exit;
    }
});

if (!function_exists('bgcc_rand_id')) {
    function bgcc_rand_id($len = 6) {
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, $len);
    }
}

function bgcc_variables_page() {
    $variables  = get_option('bricks_global_variables', []);
    $categories = get_option('bricks_global_variables_categories', []);
    ?>
    <style>
        /* Layout */
        #bgcc-categories-sidebar {
            float: left;
            width: 20%;
            padding: 10px;
            border: 1px solid #ddd;
            margin-right: 20px;
        }
        #bgcc-main-content {
            float: left;
            width: 75%;
        }
        .clear { clear: both; }

        /* Category repeater styles */
        .bgcc-category-row {
            margin-bottom: 5px;
        }
        .bgcc-category-row input[type="text"] {
            width: 70%;
        }
        .bgcc-category-row .bgcc-remove-category {
            margin-left: 5px;
        }

        /* Table and form styles */
        #variables-table input[type="text"],
        #bulk-variables {
            width: 90%;
        }
        #export-variables-section textarea {
            width: 100%;
            font-family: monospace;
        }
        .no-variables td {
            padding: 15px;
            font-style: italic;
            color: #555;
        }
    </style>
    <div class="wrap">
        <h1><?php _e('Global Variables Manager', 'snn'); ?> <b style="color:red"><?php _e('EXPERIMENTAL', 'snn'); ?></b></h1>
        <?php settings_errors('bgcc_messages'); ?>
        <form method="post" id="bgcc-variables-form">
            <?php wp_nonce_field('bgcc_variables_save', 'bgcc_variables_nonce'); ?>

            <!-- Categories Section (Left Sidebar) -->
            <div id="bgcc-categories-sidebar">
                <h3><?php _e('Categories', 'snn'); ?></h3>
                <div id="bgcc-category-repeater">
                    <?php if (!empty($categories) && is_array($categories)): ?>
                        <?php foreach($categories as $index => $cat): ?>
                            <div class="bgcc-category-row">
                                <input type="hidden" name="categories[<?php echo $index; ?>][id]" value="<?php echo esc_attr($cat['id']); ?>">
                                <input type="text" name="categories[<?php echo $index; ?>][name]" value="<?php echo esc_attr($cat['name']); ?>" placeholder="<?php esc_attr_e('Category Name', 'snn'); ?>">
                                <button type="button" class="button bgcc-remove-category"><?php _e('Remove', 'snn'); ?></button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="bgcc-category-row">
                            <input type="hidden" name="categories[0][id]" value="<?php echo bgcc_rand_id(); ?>">
                            <input type="text" name="categories[0][name]" value="" placeholder="<?php esc_attr_e('Category Name', 'snn'); ?>">
                            <button type="button" class="button bgcc-remove-category"><?php _e('Remove', 'snn'); ?></button>
                        </div>
                    <?php endif; ?>
                </div>
                <button type="button" class="button" id="bgcc-add-category"><?php _e('Add Category', 'snn'); ?></button>
                
                <!-- Moved Bulk Variables Section -->
                <div style="margin-top:20px;">
                    <h3><?php _e('Bulk Variables', 'snn'); ?></h3>
                    <p>
                        <?php _e('Paste multiple variable definitions here', 'snn'); ?> (<code>--my-var: #cccccc;</code>) <?php _e('and click', 'snn'); ?> "<?php _e('Generate Variables', 'snn'); ?>".<br>
                        <?php _e('Use the format:', 'snn'); ?> <code>--variableName: value;</code> (<?php _e('one per line', 'snn'); ?>).
                    </p>
                    <textarea id="bulk-variables" rows="4" style="width:100%; font-family:monospace;"></textarea>
                    <br><br>
                    <button type="button" class="button button-secondary" id="generate-variables"><?php _e('Generate Variables', 'snn'); ?></button>
                </div>
                <!-- End Moved Bulk Variables Section -->
            </div>

            <div id="bgcc-main-content">
                <div id="bulk-actions-variables" style="margin-bottom:10px;">
                    <?php submit_button(__('Save All', 'snn'), 'primary', 'bgcc_variables_save', false); ?>
                    <button type="button" class="button" id="bulk-delete-variables"><?php _e('Delete Selected', 'snn'); ?></button>
                    <input type="text" id="bulk-search-variables" placeholder="<?php esc_attr_e('Search variables...', 'snn'); ?>" style="margin-left:20px; padding-left:5px;">
                </div>
                <table class="widefat fixed" id="variables-table">
                    <thead>
                        <tr>
                            <th style="width:30px"><input type="checkbox" id="select-all-variables"></th>
                            <th><?php _e('Name', 'snn'); ?></th>
                            <th><?php _e('Value', 'snn'); ?></th>
                            <th><?php _e('Category', 'snn'); ?></th>
                            <th style="width:100px"><?php _e('Actions', 'snn'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($variables && count($variables) > 0) : ?>
                            <?php foreach ($variables as $var) : ?>
                                <tr data-category="<?php echo isset($var['category']) ? esc_attr($var['category']) : ''; ?>">
                                    <td><input type="checkbox" class="bulk-select-variable"></td>
                                    <td>
                                        <input type="hidden" class="var-id" value="<?php echo esc_attr($var['id']); ?>">
                                        <input type="text" class="var-name" value="<?php echo esc_attr($var['name']); ?>" required>
                                    </td>
                                    <td>
                                        <input type="text" class="var-value" value="<?php echo esc_attr($var['value']); ?>" required>
                                    </td>
                                    <td>
                                        <select class="var-category">
                                            <option value=""><?php _e('None', 'snn'); ?></option>
                                            <?php
                                            // Use the current categories option.
                                            if (!empty($categories) && is_array($categories)) {
                                                foreach($categories as $cat) {
                                                    ?>
                                                    <option value="<?php echo esc_attr($cat['id']); ?>" <?php selected(isset($var['category']) && $var['category'] == $cat['id']); ?>>
                                                        <?php echo esc_html($cat['name']); ?>
                                                    </option>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        </select>
                                    </td>
                                    <td>
                                        <button type="button" class="button button-danger remove-row"><?php _e('Remove', 'snn'); ?></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr class="no-variables">
                                <td colspan="5" style="text-align: center;"><?php _e('No variables added yet. Click "Add" to create a variable.', 'snn'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5">
                                <button type="button" class="button button-secondary" id="add-variable"><?php _e('Add', 'snn'); ?></button>
                            </td>
                        </tr>
                    </tfoot>
                </table>
                <!-- Save Button at Bottom -->
                <?php submit_button(__('Save All', 'snn'), 'primary', 'bgcc_variables_save', false); ?>

                <div id="export-variables-section" style="margin-top:30px;">
                    <h2><?php _e('Export Variables', 'snn'); ?></h2>
                    <p><?php _e('Copy the variable definitions below to back up your variables:', 'snn'); ?></p>
                    <textarea readonly rows="10"><?php 
                        // Group variables by category.
                        $groups = [];
                        if ($variables && is_array($variables)) {
                            foreach($variables as $var) {
                                $cat = (!empty($var['category'])) ? $var['category'] : 'none';
                                if (!isset($groups[$cat])) {
                                    $groups[$cat] = [];
                                }
                                $groups[$cat][] = $var;
                            }
                        }
                        
                        $export_output = '';
                        foreach ($groups as $cat => $vars) {
                            if ($cat === 'none') {
                                $selector = ':root';
                            } else {
                                // Look up the category name from the saved categories.
                                $cat_name = $cat;
                                if ($categories && is_array($categories)) {
                                    foreach ($categories as $c) {
                                        if ($c['id'] === $cat) {
                                            $cat_name = $c['name'];
                                            break;
                                        }
                                    }
                                }
                                // Generate a simple CSS class selector from the category name.
                                $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $cat_name));
                                $selector = '.' . $slug;
                            }
                            $export_output .= $selector . " {\n";
                            foreach ($vars as $var) {
                                $export_output .= "    " . $var['name'] . ": " . $var['value'] . ";\n";
                            }
                            $export_output .= "}\n\n";
                        }
                        echo esc_textarea($export_output);
                    ?></textarea>
                </div>
            </div>
            <div class="clear"></div>
            <script>
            document.addEventListener('DOMContentLoaded', function () {
                // --- Categories Repeater ---
                const categoryRepeater = document.getElementById('bgcc-category-repeater');
                const addCategoryBtn = document.getElementById('bgcc-add-category');

                addCategoryBtn.addEventListener('click', function() {
                    // New index is based on current number of category rows.
                    const index = categoryRepeater.children.length;
                    const newId = '<?php echo bgcc_rand_id(); ?>';
                    const newRow = document.createElement('div');
                    newRow.className = 'bgcc-category-row';
                    newRow.innerHTML = `
                        <input type="hidden" name="categories[${index}][id]" value="${newId}">
                        <input type="text" name="categories[${index}][name]" value="" placeholder="<?php echo esc_attr__('Category Name', 'snn'); ?>">
                        <button type="button" class="button bgcc-remove-category"><?php echo esc_js(__('Remove', 'snn')); ?></button>
                    `;
                    categoryRepeater.appendChild(newRow);
                });

                categoryRepeater.addEventListener('click', function(e) {
                    if(e.target && e.target.classList.contains('bgcc-remove-category')) {
                        e.target.parentElement.remove();
                        // Re-index the category rows
                        Array.from(categoryRepeater.children).forEach(function(row, idx) {
                            const hiddenInput = row.querySelector('input[type="hidden"]');
                            const textInput = row.querySelector('input[type="text"]');
                            hiddenInput.name = `categories[${idx}][id]`;
                            textInput.name = `categories[${idx}][name]`;
                        });
                    }
                });

                // --- Variables Table Code ---
                const variablesTable = document.getElementById('variables-table').querySelector('tbody');
                const addVariableBtn = document.getElementById('add-variable');
                const generateVariablesBtn = document.getElementById('generate-variables');
                const bulkDeleteVariablesBtn = document.getElementById('bulk-delete-variables');
                const selectAllVariablesCheckbox = document.getElementById('select-all-variables');
                const bulkSearchVariablesInput = document.getElementById('bulk-search-variables');
                const bulkVariablesTextarea = document.getElementById('bulk-variables');
                const variablesForm = document.getElementById('bgcc-variables-form');
                const nonceField = document.getElementById('bgcc_variables_nonce');

                // Helper: random ID generator.
                function bgccRandId(len = 6) {
                    return [...Array(len)]
                        .map(() => 'abcdefghijklmnopqrstuvwxyz0123456789'[Math.floor(Math.random() * 36)])
                        .join('');
                }

                // Helper: generate category select HTML by reading current categories from the repeater.
                function generateCategorySelect(selectedValue = '') {
                    let html = '<select class="var-category"><option value=""><?php echo esc_js(__('None', 'snn')); ?></option>';
                    const currentCategoryRows = document.querySelectorAll('#bgcc-category-repeater .bgcc-category-row');
                    currentCategoryRows.forEach(function(row) {
                        const catId = row.querySelector('input[type="hidden"]').value;
                        const catName = row.querySelector('input[type="text"]').value;
                        html += '<option value="'+catId+'"'+ (catId === selectedValue ? ' selected' : '') +'>'+catName+'</option>';
                    });
                    html += '</select>';
                    return html;
                }

                // Add new variable row.
                addVariableBtn.addEventListener('click', () => {
                    const noVariablesMsg = variablesTable.querySelector('.no-variables');
                    if (noVariablesMsg) {
                        noVariablesMsg.remove();
                    }
                    const row = variablesTable.insertRow(-1);
                    row.innerHTML = `
                        <td>
                            <input type="checkbox" class="bulk-select-variable">
                        </td>
                        <td>
                            <input type="hidden" class="var-id" value="${bgccRandId()}">
                            <input type="text" class="var-name" required>
                        </td>
                        <td>
                            <input type="text" class="var-value" required>
                        </td>
                        <td>
                            ${generateCategorySelect()}
                        </td>
                        <td>
                            <button type="button" class="button button-danger remove-row"><?php echo esc_js(__('Remove', 'snn')); ?></button>
                        </td>`;
                });

                // Remove variable row.
                document.body.addEventListener('click', function (e) {
                    if (e.target.classList.contains('remove-row')) {
                        const row = e.target.closest('tr');
                        row.remove();
                        if (!variablesTable.querySelectorAll('tr:not(.no-variables)').length) {
                            let newRow = variablesTable.insertRow();
                            newRow.classList.add('no-variables');
                            newRow.innerHTML = '<td colspan="5" style="text-align: center;"><?php echo esc_js(__('No variables added yet. Click "Add" to create a variable.', 'snn')); ?></td>';
                        }
                    }
                });

                // Generate variables from bulk input.
                generateVariablesBtn.addEventListener('click', () => {
                    const text = bulkVariablesTextarea.value.trim();
                    if (!text) {
                        alert('<?php echo esc_js(__('Please paste some variables first.', 'snn')); ?>');
                        return;
                    }
                    const noVariablesMsg = variablesTable.querySelector('.no-variables');
                    if (noVariablesMsg) {
                        noVariablesMsg.remove();
                    }
                    const regex = /([\w-]+)\s*:\s*([^;]+);?/g;
                    let match;
                    while ((match = regex.exec(text)) !== null) {
                        const varName = match[1].trim();
                        const varValue = match[2].trim();
                        if (!varName.startsWith('--') || varValue === '') {
                            continue;
                        }
                        const row = variablesTable.insertRow(-1);
                        row.innerHTML = `
                            <td>
                                <input type="checkbox" class="bulk-select-variable">
                            </td>
                            <td>
                                <input type="hidden" class="var-id" value="${bgccRandId()}">
                                <input type="text" class="var-name" value="${varName}" required>
                            </td>
                            <td>
                                <input type="text" class="var-value" value="${varValue}" required>
                            </td>
                            <td>
                                ${generateCategorySelect()}
                            </td>
                            <td>
                                <button type="button" class="button button-danger remove-row"><?php echo esc_js(__('Remove', 'snn')); ?></button>
                            </td>`;
                    }
                });

                // Bulk delete selected variable rows.
                bulkDeleteVariablesBtn.addEventListener('click', () => {
                    const varCheckboxes = variablesTable.querySelectorAll('input.bulk-select-variable:checked');
                    varCheckboxes.forEach(cb => {
                        const row = cb.closest('tr');
                        row.remove();
                    });
                    if (!variablesTable.querySelectorAll('tr:not(.no-variables)').length) {
                        let newRow = variablesTable.insertRow();
                        newRow.classList.add('no-variables');
                        newRow.innerHTML = '<td colspan="5" style="text-align: center;"><?php echo esc_js(__('No variables added yet. Click "Add" to create a variable.', 'snn')); ?></td>';
                    }
                });

                // "Select All" checkbox.
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

                // Search functionality: search by variable name and category text.
                bulkSearchVariablesInput.addEventListener('input', function() {
                    const filter = this.value.toLowerCase();
                    const varRows = variablesTable.querySelectorAll('tbody tr');
                    varRows.forEach(row => {
                        if (row.classList.contains('no-variables')) return;
                        const nameInput = row.querySelector('.var-name');
                        const categorySelect = row.querySelector('.var-category');
                        const text = (nameInput ? nameInput.value.toLowerCase() : '') + ' ' +
                                     (categorySelect ? categorySelect.options[categorySelect.selectedIndex].text.toLowerCase() : '');
                        row.style.display = (text.indexOf(filter) !== -1) ? '' : 'none';
                    });
                });

                // On form submit, send variable data in chunks via AJAX.
                variablesForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const rows = variablesTable.querySelectorAll('tbody tr');
                    const variablesArray = [];
                    rows.forEach(function(row) {
                        if (row.classList.contains('no-variables')) return;
                        const idInput = row.querySelector('.var-id');
                        const nameInput = row.querySelector('.var-name');
                        const valueInput = row.querySelector('.var-value');
                        const categorySelect = row.querySelector('.var-category');
                        if (!nameInput || !valueInput) return;
                        const varName = nameInput.value.trim();
                        const varValue = valueInput.value.trim();
                        const varCategory = categorySelect ? categorySelect.value : '';
                        if(varName !== '' && varValue !== '' && varName.startsWith('--')){
                            variablesArray.push({
                                id: idInput.value,
                                name: varName,
                                value: varValue,
                                category: varCategory
                            });
                        }
                    });
                    
                    // Split variables into chunks (500 per chunk).
                    const chunkSize = 500;
                    let chunks = [];
                    for (let i = 0; i < variablesArray.length; i += chunkSize) {
                        chunks.push(variablesArray.slice(i, i + chunkSize));
                    }
                    
                    function sendChunk(index) {
                        if (index >= chunks.length) {
                            // All chunks sent; redirect to simulate successful save.
                            window.location = "<?php echo add_query_arg(['page' => 'bricks-global-variables', 'updated' => 'true'], admin_url('admin.php')); ?>";
                            return;
                        }
                        let chunk = chunks[index];
                        let isFinal = (index === chunks.length - 1) ? '1' : '0';
                        let formData = new FormData();
                        formData.append('action', 'bgcc_save_variables_chunk');
                        formData.append('nonce', nonceField.value);
                        formData.append('chunk', JSON.stringify(chunk));
                        formData.append('final', isFinal);
                        
                        fetch(ajaxurl, {
                            method: 'POST',
                            body: formData,
                            credentials: 'same-origin'
                        })
                        .then(response => response.json())
                        .then(data => {
                            if(data.success) {
                                sendChunk(index + 1);
                            } else {
                                alert('<?php echo esc_js(__('Error saving variables.', 'snn')); ?>');
                            }
                        })
                        .catch(err => {
                            alert('<?php echo esc_js(__('Error saving variables.', 'snn')); ?>');
                        });
                    }
                    
                    sendChunk(0);
                });
            });
            </script>
        </form>
    </div>
    <?php
}
?>
