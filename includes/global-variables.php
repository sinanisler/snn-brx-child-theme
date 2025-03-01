<?php

add_action('admin_menu', function () {
    add_submenu_page(
        'snn-settings',
        'Global Variables',
        'Global Variables',
        'manage_options',
        'bricks-global-variables',
        'bgv_page',
        99
    );
});

add_action('admin_init', function () {
    if (isset($_POST['bgv_save_variables']) && wp_verify_nonce($_POST['bgv_variables_nonce'], 'bgv_save_variables')) {
        $variables_json = $_POST['bgv_variables_data'] ?? '';
        $postedVariables = json_decode(stripslashes($variables_json), true);
        $new_variables   = [];
        if ($postedVariables && is_array($postedVariables)) {
            foreach ($postedVariables as $var) {
                $varId    = !empty($var['id']) ? sanitize_text_field($var['id']) : bgv_rand_id();
                $varName  = !empty($var['name']) ? sanitize_text_field($var['name']) : '';
                $varValue = isset($var['value']) ? sanitize_text_field($var['value']) : '';
                $varName  = trim($varName);
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
        add_settings_error('bgv_messages', 'bgv_save_message', 'Settings Saved', 'updated');
        wp_redirect(add_query_arg(['page' => 'bricks-global-variables', 'updated' => 'true'], admin_url('admin.php')));
        exit;
    }
});

function bgv_rand_id($len = 6) {
    return substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, $len);
}

function bgv_page() {
    $variables = get_option('bricks_global_variables', []);
    ?>
    <style>
        /* Styles for Global Variables Manager */
        #bgv-container {
            width: 100%;
        }
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
        #top-save {
            margin-bottom: 10px;
        }
    </style>

    <div class="wrap">
        <h1>Global Variables - <b style="color:red">EXPERIMENTAL</b></h1>
        <?php settings_errors('bgv_messages'); ?>

        <form method="post" id="bgv-main-form">
            <?php wp_nonce_field('bgv_save_variables', 'bgv_variables_nonce'); ?>

            <div id="bgv-container">
                <!-- Bulk Variables block -->
                <div style="margin-bottom:20px;">
                    <h2>Bulk Variables</h2>
                    <p>
                        Paste multiple variable definitions here (<code>--my-var: #cccccc;</code>) and click "Generate Variables".<br>
                        Use the format: <code>--variableName: value;</code> (one per line).
                    </p>
                    <textarea id="bulk-variables" rows="4" style="width:100%; font-family:monospace;"></textarea>
                    <br><br>
                    <button type="button" class="button button-secondary" id="generate-variables">Generate Variables</button>
                </div>
                <!-- Bulk actions for variables -->
                <div style="margin-bottom:10px;">
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

            <input type="hidden" name="bgv_variables_data" id="bgv_variables_data" value="">
            <p id="top-save"><?php submit_button('Save All', 'primary', 'bgv_save_variables', false); ?></p>
        </form>

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
        const variablesTable = document.getElementById('variables-table').querySelector('tbody');
        const addVariableBtn = document.getElementById('add-variable');
        const generateVariablesBtn = document.getElementById('generate-variables');
        const bulkDeleteVariablesBtn = document.getElementById('bulk-delete-variables');
        const selectAllVariablesCheckbox = document.getElementById('select-all-variables');
        const bulkSearchVariablesInput = document.getElementById('bulk-search-variables');
        const mainForm = document.getElementById('bgv-main-form');
        const variablesDataInput = document.getElementById('bgv_variables_data');
        const bulkVariablesTextarea = document.getElementById('bulk-variables');

        function bgvRandId(len = 6) {
            return [...Array(len)]
                .map(() => 'abcdefghijklmnopqrstuvwxyz0123456789'[Math.floor(Math.random() * 36)])
                .join('');
        }

        function updateVariablesEmptyState() {
            if (variablesTable.rows.length === 0) {
                let row = variablesTable.insertRow();
                row.classList.add('no-variables');
                row.innerHTML = '<td colspan="4" style="text-align: center;">No variables added yet. Click "Add" to create a variable.</td>';
            }
        }

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
                    <input type="hidden" name="variables[${idx}][id]" value="${bgvRandId()}">
                    <input type="text" name="variables[${idx}][name]" required>
                </td>
                <td>
                    <input type="text" name="variables[${idx}][value]" required>
                </td>
                <td>
                    <button type="button" class="button button-danger remove-row">Remove</button>
                </td>`;
        });

        document.body.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-row')) {
                const row = e.target.closest('tr');
                row.remove();
                updateVariablesEmptyState();
            }
        });

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
            const regex = /([\w-]+)\s*:\s*([^;]+);?/g;
            let match;
            while ((match = regex.exec(text)) !== null) {
                const varName = match[1].trim();
                const varValue = match[2].trim();
                if (!varName.startsWith('--')) {
                    continue;
                }
                const idx = variablesTable.rows.length;
                const row = variablesTable.insertRow(-1);
                row.innerHTML = `
                    <td>
                        <input type="checkbox" class="bulk-select-variable">
                    </td>
                    <td>
                        <input type="hidden" name="variables[${idx}][id]" value="${bgvRandId()}">
                        <input type="text" name="variables[${idx}][name]" value="${varName}" required>
                    </td>
                    <td>
                        <input type="text" name="variables[${idx}][value]" value="${varValue}" required>
                    </td>
                    <td>
                        <button type="button" class="button button-danger remove-row">Remove</button>
                    </td>`;
            }
        });

        bulkDeleteVariablesBtn.addEventListener('click', () => {
            const varCheckboxes = variablesTable.querySelectorAll('input.bulk-select-variable:checked');
            varCheckboxes.forEach(cb => {
                const row = cb.closest('tr');
                row.remove();
            });
            updateVariablesEmptyState();
        });

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

        mainForm.addEventListener('submit', function(e) {
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
?>
