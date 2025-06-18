<?php

function snn_add_custom_fields_submenu() {
    add_submenu_page(
        'snn-settings',
        __('Register Custom Fields', 'snn'),
        __('Custom Fields', 'snn'),
        'manage_options',
        'snn-custom-fields',
        'snn_custom_fields_page_callback'
    );
}
add_action('admin_menu', 'snn_add_custom_fields_submenu', 10);

// (Optional) Check if Classic Editor plugin is active. If NOT, disable block editor for products.
if ( ! function_exists('is_plugin_active') ) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
if ( ! is_plugin_active( 'classic-editor/classic-editor.php' ) ) {
    add_filter('use_block_editor_for_post_type', function ($is_enabled, $post_type) {
        if ($post_type === 'product') {
            return false;
        }
        return $is_enabled;
    }, 10, 2);
}

add_action('admin_enqueue_scripts', 'snn_enqueue_scripts_for_custom_fields_page');
function snn_enqueue_scripts_for_custom_fields_page($hook_suffix) {
    $current_screen = get_current_screen();
    if ($current_screen && $current_screen->id === 'snn-settings_page_snn-custom-fields') {
        wp_enqueue_media();
        add_action('admin_footer', 'snn_output_dynamic_field_js');
    }
}

add_action('admin_enqueue_scripts', 'snn_enqueue_taxonomy_author_assets');
function snn_enqueue_taxonomy_author_assets($hook) {
    // Common pages: term.php, edit-tags.php = Taxonomy editing
    // profile.php, user-edit.php = Author profile
    if ( in_array($hook, ['term.php', 'edit-tags.php', 'profile.php', 'user-edit.php'], true) ) {
        wp_enqueue_media();      
        add_action('admin_footer', 'snn_output_dynamic_field_js');
    }
}

function snn_custom_fields_page_callback() {
    $custom_fields = get_option('snn_custom_fields', []);
    $post_types    = get_post_types(['public' => true], 'objects');
    $taxonomies    = get_taxonomies(['public' => true], 'objects');

    if (isset($_POST['snn_custom_fields_nonce']) && wp_verify_nonce($_POST['snn_custom_fields_nonce'], 'snn_custom_fields_save')) {
        $new_fields = [];
        if (!empty($_POST['custom_fields']) && is_array($_POST['custom_fields'])) {
            foreach ($_POST['custom_fields'] as $field_data) {  
                if (!empty($field_data['name']) && !empty($field_data['type']) && !empty($field_data['group_name'])) {
                    $post_types_selected = isset($field_data['post_type']) && is_array($field_data['post_type']) ? array_map('sanitize_text_field', $field_data['post_type']) : [];
                    $taxonomies_selected = isset($field_data['taxonomies']) && is_array($field_data['taxonomies']) ? array_map('sanitize_text_field', $field_data['taxonomies']) : [];
                    $choices_raw = isset($field_data['choices']) ? trim($field_data['choices']) : '';
                    $choices_sanitized = sanitize_textarea_field($choices_raw);

                    $field_type_for_repeater_check = isset($field_data['type']) ? $field_data['type'] : 'text';
                    $is_repeater_disabled_type = in_array($field_type_for_repeater_check, ['rich_text', 'basic_rich_text', 'select','checkbox','radio','true_false','url','email']);

                    $new_fields[] = [
                        'group_name'    => sanitize_text_field($field_data['group_name']),
                        'label'         => sanitize_text_field($field_data['label']),
                        'name'          => sanitize_key($field_data['name']),
                        'type'          => sanitize_text_field($field_data['type']),
                        'post_type'     => $post_types_selected,
                        'taxonomies'    => $taxonomies_selected,
                        'choices'       => $choices_sanitized,
                        'repeater'      => (!$is_repeater_disabled_type && !empty($field_data['repeater'])) ? 1 : 0,
                        'author'        => !empty($field_data['author']) ? 1 : 0,
                        'options_page'  => !empty($field_data['options_page']) ? 1 : 0,
                        'column_width'  => isset($field_data['column_width']) && is_numeric($field_data['column_width']) ? intval($field_data['column_width']) : '',
                        'return_full_url'=> ($field_data['type'] === 'media' && !empty($field_data['return_full_url'])) ? 1 : 0,
                    ];
                }
            }
        }
        update_option('snn_custom_fields', $new_fields);
        $custom_fields = $new_fields;
        echo '<div class="updated"><p>' . esc_html__('Custom fields saved successfully.', 'snn') . '</p></div>';
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Manage Custom Fields', 'snn'); ?></h1>
        <form method="post">
            <?php wp_nonce_field('snn_custom_fields_save', 'snn_custom_fields_nonce'); ?>
            <div id="custom-field-settings">
                <p>
                    <?php esc_html_e('Define custom fields with group name, field name, field type, and assign to post type, taxonomy, author, or an options page:', 'snn'); ?>
                    <br><?php esc_html_e('Select one or more to register the same Custom Field to Post Types, Taxonomies, or Author.', 'snn'); ?>
                    <br><?php esc_html_e('Checking "Options Page" will make this field available on a new admin page named after its "Group Name".', 'snn'); ?>
                    <br><?php esc_html_e('Press CTRL/CMD to select multiple or remove selection.', 'snn'); ?>
                </p>
                <?php
                if (!empty($custom_fields) && is_array($custom_fields)) {
                    foreach ($custom_fields as $index => $field) {
                        $field_type = isset($field['type']) ? $field['type'] : 'text';
                        $show_choices = in_array($field_type, ['select','checkbox','radio']);
                        $is_repeater_disabled_type = in_array($field_type, ['rich_text', 'basic_rich_text', 'select','checkbox','radio','true_false','url','email']);
                        $repeater_title = $is_repeater_disabled_type ? __('This field type cannot be a repeater', 'snn') : __('Allow multiple values', 'snn');
                        ?>
                        <div class="custom-field-row" data-index="<?php echo $index; ?>">
                            <div class="buttons">
                                <button type="button" class="move-up">▲</button>
                                <button type="button" class="move-down">▼</button>
                                <button type="button" class="remove-field"><?php esc_html_e('Remove', 'snn'); ?></button>
                            </div>
                            <div class="field-identity-group">
                                <div class="field-group">
                                    <label><?php esc_html_e('Group Name', 'snn'); ?></label>
                                    <input type="text" name="custom_fields[<?php echo $index; ?>][group_name]" placeholder="<?php esc_attr_e('Group Name', 'snn'); ?>"  
                                           value="<?php echo esc_attr($field['group_name'] ?? ''); ?>" />
                                </div>
                                <div class="field-group">
                                    <label><?php esc_html_e('Field Label', 'snn'); ?></label>
                                    <input type="text" name="custom_fields[<?php echo $index; ?>][label]"  
                                           placeholder="<?php esc_attr_e('Field Label Name', 'snn'); ?>"  
                                           value="<?php echo esc_attr($field['label'] ?? ''); ?>" />
                                </div>
                                <div class="field-group">
                                    <label><?php esc_html_e('Slug Name', 'snn'); ?></label>
                                    <input type="text" class="sanitize-key" name="custom_fields[<?php echo $index; ?>][name]"  
                                           placeholder="<?php esc_attr_e('field_name', 'snn'); ?>"  
                                           value="<?php echo esc_attr($field['name']); ?>" />
                                </div>
                            </div>
                            <div class="field-group">
                                <label><?php esc_html_e('Width (%)', 'snn'); ?></label>
                                <input style="width:70px" type="number" min="10" max="100"
                                       name="custom_fields[<?php echo $index; ?>][column_width]"  
                                       placeholder="25"  
                                       value="<?php echo esc_attr($field['column_width'] ?? ''); ?>" />
                            </div>
                            <div class="field-group">
                                <label><?php esc_html_e('Field Type', 'snn'); ?></label>
                                <select name="custom_fields[<?php echo $index; ?>][type]" class="field-type-select" style="width:140px">
                                    <option value="text"    <?php selected($field_type, 'text'); ?>><?php esc_html_e('Text', 'snn'); ?></option>
                                    <option value="number"    <?php selected($field_type, 'number'); ?>><?php esc_html_e('Number', 'snn'); ?></option>
                                    <option value="textarea"  <?php selected($field_type, 'textarea'); ?>><?php esc_html_e('Textarea', 'snn'); ?></option>
                                    <option value="rich_text" <?php selected($field_type, 'rich_text'); ?>><?php esc_html_e('Rich Text', 'snn'); ?></option>
                                    <option value="basic_rich_text" <?php selected($field_type, 'basic_rich_text'); ?>><?php esc_html_e('Basic Rich Text', 'snn'); ?></option>
                                    <option value="media"     <?php selected($field_type, 'media'); ?>><?php esc_html_e('Media', 'snn'); ?></option>
                                    <option value="date"      <?php selected($field_type, 'date'); ?>><?php esc_html_e('Date', 'snn'); ?></option>
                                    <option value="time"      <?php selected($field_type, 'time'); ?>><?php esc_html_e('Time', 'snn'); ?></option>
                                    <option value="color"     <?php selected($field_type, 'color'); ?>><?php esc_html_e('Color', 'snn'); ?></option>
                                    <option value="select"    <?php selected($field_type, 'select'); ?>><?php esc_html_e('Select', 'snn'); ?></option>
                                    <option value="checkbox"  <?php selected($field_type, 'checkbox'); ?>><?php esc_html_e('Checkbox', 'snn'); ?></option>
                                    <option value="radio"     <?php selected($field_type, 'radio'); ?>><?php esc_html_e('Radio', 'snn'); ?></option>
                                    <option value="true_false"<?php selected($field_type, 'true_false'); ?>><?php esc_html_e('True/False', 'snn'); ?></option>
                                    <option value="url"       <?php selected($field_type, 'url'); ?>><?php esc_html_e('URL', 'snn'); ?></option>
                                    <option value="email"     <?php selected($field_type, 'email'); ?>><?php esc_html_e('Email', 'snn'); ?></option>
                                </select>
                            </div>
                            <div class="field-group field-group-choices" style="<?php echo $show_choices ? '' : 'display:none;'; ?>">
                                <label><?php esc_html_e('Choices', 'snn'); ?> <small><code>(<?php esc_html_e('value:label', 'snn'); ?>)</code></small></label>
                                <textarea name="custom_fields[<?php echo $index; ?>][choices]" rows="4"  
                                          placeholder="red : <?php esc_attr_e('Red Color', 'snn'); ?>&#10;green : <?php esc_attr_e('Green Color', 'snn'); ?>"><?php  
                                          echo esc_textarea($field['choices'] ?? ''); ?></textarea>
                            </div>
                            <div class="field-group">
                                <label><?php esc_html_e('Post Types', 'snn'); ?></label>
                                <select name="custom_fields[<?php echo $index; ?>][post_type][]" multiple>
                                    <?php foreach ($post_types as $pt) : ?>
                                        <option value="<?php echo esc_attr($pt->name); ?>"  
                                            <?php echo (!empty($field['post_type']) && in_array($pt->name, $field['post_type'])) ? 'selected' : ''; ?>>
                                            <?php echo esc_html($pt->label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field-group">
                                <label><?php esc_html_e('Taxonomies', 'snn'); ?></label>
                                <select name="custom_fields[<?php echo $index; ?>][taxonomies][]" multiple>
                                    <?php foreach ($taxonomies as $tax) : ?>
                                        <option value="<?php echo esc_attr($tax->name); ?>"  
                                            <?php echo (!empty($field['taxonomies']) && in_array($tax->name, $field['taxonomies'])) ? 'selected' : ''; ?>>
                                            <?php echo esc_html($tax->label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field-group">
                                <label><?php esc_html_e('Author', 'snn'); ?></label>
                                <input type="checkbox" name="custom_fields[<?php echo $index; ?>][author]" value="1"  
                                       <?php checked(!empty($field['author'])); ?> />
                            </div>
                             <div class="field-group">
                                <label><?php esc_html_e('Options Page', 'snn'); ?></label>
                                <input type="checkbox" name="custom_fields[<?php echo $index; ?>][options_page]" value="1"  
                                       <?php checked(!empty($field['options_page'])); ?> />
                            </div>
                            <div class="field-group">
                                <label><?php esc_html_e('Repeater', 'snn'); ?></label>
                                <input type="checkbox" class="repeater-checkbox" name="custom_fields[<?php echo $index; ?>][repeater]" value="1"
                                       <?php checked(!empty($field['repeater'])); echo $is_repeater_disabled_type ? ' disabled' : ''; ?>
                                       title="<?php echo esc_attr($repeater_title); ?>" />
                            </div>
                            <?php if ($field_type === 'media') : ?>
                            <div class="field-group media-return-url-group">
                                <label><?php esc_html_e('Return URL', 'snn'); ?></label>
                                <input type="checkbox" name="custom_fields[<?php echo $index; ?>][return_full_url]" value="1" <?php checked(!empty($field['return_full_url'])); ?> />
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
            <button type="button" id="add-custom-field-row"><?php esc_html_e('Add New Field', 'snn'); ?></button>
            <br><br>
            <?php submit_button(__('Save Custom Fields', 'snn')); ?>
        </form>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fieldContainer = document.getElementById('custom-field-settings');
            const addFieldButton = document.getElementById('add-custom-field-row');

            function updateFieldIndexes() {
                const rows = fieldContainer.querySelectorAll('.custom-field-row');
                rows.forEach(function(row, index) {
                    row.dataset.index = index;
                    row.querySelectorAll('input, select, textarea').forEach(function(input) {
                        const name = input.name;
                        if (name) {
                            input.name = name.replace(/\[\d+\]/, '[' + index + ']');
                        }
                    });
                });
            }

            function toggleChoicesField(row) {
                const typeSelect = row.querySelector('.field-type-select');
                const choicesGroup = row.querySelector('.field-group-choices');
                if (!typeSelect || !choicesGroup) return;
                const showChoices = ['select','checkbox','radio'].includes(typeSelect.value);
                choicesGroup.style.display = showChoices ? '' : 'none';
            }

            function toggleRepeaterCheckbox(row) {
                const typeSelect = row.querySelector('.field-type-select');
                const repeaterCheckbox = row.querySelector('.repeater-checkbox');
                if (!typeSelect || !repeaterCheckbox) return;
                const disable = ['rich_text', 'basic_rich_text', 'select','checkbox','radio','true_false','url','email'].includes(typeSelect.value);
                repeaterCheckbox.disabled = disable;
                repeaterCheckbox.title = disable ? '<?php echo esc_js(__('This field type cannot be a repeater', 'snn')); ?>' : '<?php echo esc_js(__('Allow multiple values', 'snn')); ?>';
                if (disable) {
                    repeaterCheckbox.checked = false;
                }
            }

            function toggleMediaReturnUrlField(row) {
                const typeSelect = row.querySelector('.field-type-select');
                const mediaReturnGroup = row.querySelector('.media-return-url-group');
                if (mediaReturnGroup) { 
                    mediaReturnGroup.style.display = (typeSelect && typeSelect.value === 'media') ? '' : 'none';
                } else if (typeSelect && typeSelect.value === 'media') {
                }
            }
            
            function handleMediaReturnUrlForNewRow(row, fieldType) {
                let mediaReturnGroup = row.querySelector('.media-return-url-group');
                if (fieldType === 'media') {
                    if (!mediaReturnGroup) {
                        const newIndex = row.dataset.index; 
                        const div = document.createElement('div');
                        div.classList.add('field-group', 'media-return-url-group');
                        div.innerHTML = `<label><?php esc_html_e('Return Full URL', 'snn'); ?></label><br><input type="checkbox" name="custom_fields[${newIndex}][return_full_url]" value="1">`;
                        const repeaterDiv = row.querySelector('input[name*="[repeater]"]');
                        if (repeaterDiv && repeaterDiv.closest('.field-group')) {
                            repeaterDiv.closest('.field-group').insertAdjacentElement('afterend', div);
                        } else {
                            row.appendChild(div); 
                        }
                        mediaReturnGroup = div;
                    }
                    mediaReturnGroup.style.display = '';
                } else {
                    if (mediaReturnGroup) {
                        mediaReturnGroup.style.display = 'none';
                    }
                }
            }


            addFieldButton.addEventListener('click', function() {
                const newIndex = fieldContainer.querySelectorAll('.custom-field-row').length;
                const newRow = document.createElement('div');
                newRow.classList.add('custom-field-row');
                newRow.dataset.index = newIndex;
                newRow.innerHTML = `
                    <div class="buttons">
                        <button type="button" class="move-up">▲</button>
                        <button type="button" class="move-down">▼</button>
                        <button type="button" class="remove-field"><?php esc_html_e('Remove', 'snn'); ?></button>
                    </div>
                    <div class="field-identity-group">
                        <div class="field-group"><label><?php esc_html_e('Group Name', 'snn'); ?></label><input type="text" name="custom_fields[${newIndex}][group_name]" placeholder="<?php esc_attr_e('Group Name', 'snn'); ?>"></div>
                        <div class="field-group"><label><?php esc_html_e('Field Label', 'snn'); ?></label><input type="text" name="custom_fields[${newIndex}][label]" placeholder="<?php esc_attr_e('Field Name', 'snn'); ?>"></div>
                        <div class="field-group"><label><?php esc_html_e('Slug Name', 'snn'); ?></label><input type="text" class="sanitize-key" name="custom_fields[${newIndex}][name]" placeholder="<?php esc_attr_e('field_name', 'snn'); ?>"></div>
                    </div>
                    <div class="field-group"><label><?php esc_html_e('Width (%)', 'snn'); ?></label><input style="width:70px" type="number" name="custom_fields[${newIndex}][column_width]" min="10" max="100" placeholder="25"></div>
                    <div class="field-group">
                        <label><?php esc_html_e('Field Type', 'snn'); ?></label>
                        <select name="custom_fields[${newIndex}][type]" class="field-type-select" style="width:140px">
                            <option value="text"><?php esc_html_e('Text', 'snn'); ?></option>
                            <option value="number"><?php esc_html_e('Number', 'snn'); ?></option>
                            <option value="textarea"><?php esc_html_e('Textarea', 'snn'); ?></option>
                            <option value="rich_text"><?php esc_html_e('Rich Text', 'snn'); ?></option>
                            <option value="basic_rich_text"><?php esc_html_e('Basic Rich Text', 'snn'); ?></option>
                            <option value="media"><?php esc_html_e('Media', 'snn'); ?></option>
                            <option value="date"><?php esc_html_e('Date', 'snn'); ?></option>
                            <option value="time"><?php esc_html_e('Time', 'snn'); ?></option>
                            <option value="color"><?php esc_html_e('Color', 'snn'); ?></option>
                            <option value="select"><?php esc_html_e('Select', 'snn'); ?></option>
                            <option value="checkbox"><?php esc_html_e('Checkbox', 'snn'); ?></option>
                            <option value="radio"><?php esc_html_e('Radio', 'snn'); ?></option>
                            <option value="true_false"><?php esc_html_e('True / False', 'snn'); ?></option>
                            <option value="url"><?php esc_html_e('URL', 'snn'); ?></option>
                            <option value="email"><?php esc_html_e('Email', 'snn'); ?></option>
                        </select>
                    </div>
                    <div class="field-group field-group-choices" style="display:none;">
                        <label><?php esc_html_e('Choices', 'snn'); ?> <small>(<?php esc_html_e('value:label', 'snn'); ?>)</small></label>
                        <textarea name="custom_fields[${newIndex}][choices]" rows="4" placeholder="red : <?php esc_attr_e('Red', 'snn'); ?>&#10;green : <?php esc_attr_e('Green', 'snn'); ?>"></textarea>
                    </div>
                    <div class="field-group"><label><?php esc_html_e('Post Types', 'snn'); ?></label><select name="custom_fields[${newIndex}][post_type][]" multiple>
                        <?php foreach ($post_types as $pt) : ?>
                            <option value="<?php echo esc_js($pt->name); ?>"><?php echo esc_js($pt->label); ?></option>
                        <?php endforeach; ?>
                    </select></div>
                    <div class="field-group"><label><?php esc_html_e('Taxonomies', 'snn'); ?></label><select name="custom_fields[${newIndex}][taxonomies][]" multiple>
                        <?php foreach ($taxonomies as $tax) : ?>
                            <option value="<?php echo esc_js($tax->name); ?>"><?php echo esc_js($tax->label); ?></option>
                        <?php endforeach; ?>
                    </select></div>
                    <div class="field-group"><label><?php esc_html_e('Author', 'snn'); ?></label><input type="checkbox" name="custom_fields[${newIndex}][author]" value="1"></div>
                    <div class="field-group"><label><?php esc_html_e('Options Page', 'snn'); ?></label><input type="checkbox" name="custom_fields[${newIndex}][options_page]" value="1"></div>
                    <div class="field-group"><label><?php esc_html_e('Repeater', 'snn'); ?></label><input type="checkbox" class="repeater-checkbox" name="custom_fields[${newIndex}][repeater]" value="1"></div>
                    <div class="field-group media-return-url-group" style="display:none;"><label><?php esc_html_e('Return Full URL', 'snn'); ?></label><input type="checkbox" name="custom_fields[${newIndex}][return_full_url]" value="1"></div>
                `;
                fieldContainer.appendChild(newRow);
                attachFieldNameSanitizer(newRow.querySelector('.sanitize-key'));
                toggleChoicesField(newRow);
                toggleRepeaterCheckbox(newRow);
                handleMediaReturnUrlForNewRow(newRow, newRow.querySelector('.field-type-select').value); 
                updateFieldIndexes();
            });

            fieldContainer.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-field')) {
                    e.target.closest('.custom-field-row').remove();
                    updateFieldIndexes();
                }
                if (e.target.classList.contains('move-up')) {
                    const row = e.target.closest('.custom-field-row');
                    const prev = row.previousElementSibling;
                    if (prev) {
                        fieldContainer.insertBefore(row, prev);
                        updateFieldIndexes();
                    }
                }
                if (e.target.classList.contains('move-down')) {
                    const row = e.target.closest('.custom-field-row');
                    const next = row.nextElementSibling;
                    if (next) {
                        fieldContainer.insertBefore(next, row); 
                        updateFieldIndexes();
                    }
                }
            });

            fieldContainer.addEventListener('change', function(e) {
                if (e.target.classList.contains('field-type-select')) {
                    const row = e.target.closest('.custom-field-row');
                    toggleChoicesField(row);
                    toggleRepeaterCheckbox(row);
                    handleMediaReturnUrlForNewRow(row, e.target.value); 
                }
            });

            fieldContainer.querySelectorAll('.custom-field-row').forEach(function(row) {
                toggleChoicesField(row);
                toggleRepeaterCheckbox(row);
                toggleMediaReturnUrlField(row); 
                attachFieldNameSanitizer(row.querySelector('.sanitize-key'));
            });

            function sanitizeFieldNameKey(value) {
                value = value.trim().replace(/\s+/g, '_').replace(/[^a-z0-9_]/gi, '').toLowerCase();
                return value;
            }

            function attachFieldNameSanitizer(input) {
                if (!input) return;
                input.addEventListener('keydown', function(e) {
                    if (e.key === ' ') {
                        e.preventDefault();
                        const start = input.selectionStart;
                        const end   = input.selectionEnd;
                        input.value = input.value.substring(0, start) + '_' + input.value.substring(end);
                        input.setSelectionRange(start+1, start+1);
                    }
                });
                input.addEventListener('input', function(e) {
                    const sanitized = sanitizeFieldNameKey(e.target.value);
                    if (e.target.value !== sanitized) {
                        const start = e.target.selectionStart;
                        const diff  = e.target.value.length - sanitized.length;
                        e.target.value = sanitized;
                        e.target.setSelectionRange(start - diff, start - diff);
                    }
                });
                input.addEventListener('blur', function(e) {
                    e.target.value = sanitizeFieldNameKey(e.target.value);
                });
            }
        });
        </script>

        <style>
            .custom-field-row [type="text"],
            .custom-field-row input[type="number"] {
                width:140px;
            }
            .custom-field-row {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin-bottom: 10px;
                align-items: center; 
            }
            .custom-field-row label {
                font-weight: bold;
                font-size: 14px; 
                display: block; 
                margin-bottom: 3px; 
            }
            .custom-field-row .field-group { 
                display: flex;
                flex-direction: column; 
            }
            .field-identity-group {
                display: flex;
                flex-direction: row;
                gap: 8px; 
                margin-top: 4px;
            }
            .field-identity-group input{
                height:auto;
                min-height:auto ;
            }
            .field-identity-group label{
                line-height:1
            }
            .custom-field-row input,
            .custom-field-row select,
            .custom-field-row textarea {
                font-size: 14px;
            }
            .custom-field-row .buttons button {
                margin-left: 5px;
            }
            .custom-field-row {
                gap: 10px; 
                margin-bottom: 5px;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 5px;
                background-color: #f9f9f9;
            }
            .custom-field-row .buttons {
                display: flex;
                flex-direction: row; 
                gap: 5px;
                margin-right: 10px; 
                order: -1; 
            }
            #add-custom-field-row {
                color: #2271b1;
                border-color: #2271b1;
                background: #f6f7f7;
                padding: 5px 20px;
                border: solid 1px;
                cursor: pointer;
                border-radius: 3px;
            }
            #add-custom-field-row:hover {
                background: #eee;
            }
            .submit input[type="submit"] {
                background: #2271b1;
                border-color: #2271b1;
                color: #fff;
                text-shadow: none;
            }
            .submit input[type="submit"]:hover {
                background-color: #005177;
            }
            .buttons button {
                cursor: pointer;
                border: 1px solid gray;
                padding: 4px 10px;
            }
            .buttons button:hover {
                background: white;
            }
            @media (max-width: 768px) {
                .custom-field-row {
                    flex-direction: column;
                    align-items: flex-start;
                }
                .custom-field-row .buttons {
                    flex-direction: row; 
                    gap: 10px;
                    margin-bottom: 10px; 
                    order: 0; 
                }
                .custom-field-row .field-group, 
                .custom-field-row .field-identity-group,
                .custom-field-row input[type="text"],
                .custom-field-row input[type="number"], 
                .custom-field-row select,
                .custom_field-row textarea { 
                    width: 100%; 
                }
                .custom-field-row input[style*="width:70px"] { 
                    width: 100% !important;
                }
                .custom-field-row select[style*="width:140px"] { 
                    width: 100% !important;
                }
            }
        </style>
    </div>
    <?php
}

// ------------------------------------------------
// 4) REGISTER DYNAMIC META BOXES FOR POSTS
// ------------------------------------------------
function snn_register_dynamic_metaboxes() {
    $custom_fields = get_option('snn_custom_fields', []);
    $grouped_fields = [];
    global $snn_repeater_fields_exist, $snn_media_fields_exist;
    $snn_repeater_fields_exist = false;
    $snn_media_fields_exist = false;

    foreach ($custom_fields as $field) {
        $group_name = (!empty($field['group_name'])) ? $field['group_name'] : __('Custom Fields', 'snn');
        if (!empty($field['post_type']) && is_array($field['post_type'])) {
            foreach ($field['post_type'] as $pt) {
                if (!isset($grouped_fields[$pt])) {
                    $grouped_fields[$pt] = [];
                }
                if (!isset($grouped_fields[$pt][$group_name])) {
                    $grouped_fields[$pt][$group_name] = [];
                }
                $grouped_fields[$pt][$group_name][] = $field;

                $disallowed_for_repeater = ['rich_text', 'basic_rich_text','select','checkbox','radio','true_false','url','email'];
                if (!in_array($field['type'], $disallowed_for_repeater) && !empty($field['repeater'])) {
                    $snn_repeater_fields_exist = true;
                }
                if ($field['type'] === 'media') {
                    $snn_media_fields_exist = true;
                }
                if ($field['type'] === 'date') {
                    wp_enqueue_script('jquery-ui-datepicker');
                }
            }
        }
    }

    foreach ($grouped_fields as $post_type => $groups) {
        foreach ($groups as $group_name => $fields) {
            add_meta_box(
                'snn_custom_field_group_' . sanitize_title($group_name) . '_' . $post_type,
                esc_html($group_name),
                'snn_render_metabox_content',
                $post_type,
                'normal',
                'default',
                ['fields' => $fields]
            );
        }
    }

    if ($snn_media_fields_exist || $snn_repeater_fields_exist) {
        add_action('admin_enqueue_scripts', 'snn_enqueue_metabox_scripts');
        if (is_admin()) { 
             add_action('admin_footer', 'snn_output_dynamic_field_js');
        }
    }
}
add_action('add_meta_boxes', 'snn_register_dynamic_metaboxes');

function snn_enqueue_metabox_scripts($hook_suffix) {
    global $pagenow; 
    
    if (in_array($pagenow, ['post.php','post-new.php'])) {
        $current_post_type = get_current_screen()->post_type;
        $post_type_has_media = false;
        $post_type_has_repeater = false;
        $post_type_has_basic_rich_text = false;

        $custom_fields = get_option('snn_custom_fields', []);
        foreach ($custom_fields as $field) {
            if (!empty($field['post_type']) && in_array($current_post_type, $field['post_type'])) {
                if ($field['type'] === 'media') $post_type_has_media = true;
                if ($field['type'] === 'basic_rich_text') $post_type_has_basic_rich_text = true;
                $disallowed_for_repeater = ['rich_text', 'basic_rich_text','select','checkbox','radio','true_false','url','email'];
                if (!in_array($field['type'], $disallowed_for_repeater) && !empty($field['repeater'])) {
                    $post_type_has_repeater = true;
                }
            }
        }

        if ($post_type_has_media) {
            wp_enqueue_media();
            wp_enqueue_style('dashicons');
        }
        if ($post_type_has_basic_rich_text) {
             wp_enqueue_script('snn-rich-text-editor', plugin_dir_url(__FILE__) . 'assets/js/snn-rich-text-editor.js', ['jquery'], '1.1', true);
        }
    }
    if (in_array($hook_suffix, ['profile.php','user-edit.php'])) {
        $custom_fields = get_option('snn_custom_fields', []);
        $has_author_media = false;
        $has_author_repeater = false;
        $has_author_basic_rich_text = false;
        foreach ($custom_fields as $field) {
            if (!empty($field['author'])) {
                if ($field['type'] === 'media') $has_author_media = true;
                if ($field['type'] === 'basic_rich_text') $has_author_basic_rich_text = true;
                $disallowed_for_repeater = ['rich_text', 'basic_rich_text','select','checkbox','radio','true_false','url','email'];
                if (!in_array($field['type'], $disallowed_for_repeater) && !empty($field['repeater'])) {
                    $has_author_repeater = true; 
                }
            }
        }
        if ($has_author_media) wp_enqueue_media();
        if ($has_author_basic_rich_text) {
             wp_enqueue_script('snn-rich-text-editor', plugin_dir_url(__FILE__) . 'assets/js/snn-rich-text-editor.js', ['jquery'], '1.1', true);
        }
        if ($has_author_media || $has_author_repeater) {
            add_action('admin_footer', 'snn_output_dynamic_field_js');
        }
    }
    if (in_array($pagenow, ['term.php','edit-tags.php'])) { 
        $custom_fields = get_option('snn_custom_fields', []);
        $has_tax_media = false;
        $has_tax_basic_rich_text = false;
        
        $current_taxonomy = isset($_GET['taxonomy']) ? sanitize_text_field($_GET['taxonomy']) : null;
        if(defined('DOING_AJAX') && DOING_AJAX && isset($_POST['taxonomy'])){ // Handle AJAX calls for terms screen (e.g. quick edit)
            $current_taxonomy = sanitize_text_field($_POST['taxonomy']);
        }

        if ($current_taxonomy) {
            foreach ($custom_fields as $field) {
                if (!empty($field['taxonomies']) && in_array($current_taxonomy, $field['taxonomies'])) {
                    if ($field['type'] === 'media') $has_tax_media = true;
                    if ($field['type'] === 'basic_rich_text') $has_tax_basic_rich_text = true;
                }
            }
        }

        if ($has_tax_media) wp_enqueue_media();
        if ($has_tax_basic_rich_text) {
             wp_enqueue_script('snn-rich-text-editor', plugin_dir_url(__FILE__) . 'assets/js/snn-rich-text-editor.js', ['jquery'], '1.1', true);
        }
        if ($has_tax_media) { 
            add_action('admin_footer', 'snn_output_dynamic_field_js');
        }
    }
}

// ------------------------------------------------
// 5) METABOX CONTENT RENDER
// ------------------------------------------------
function snn_render_metabox_content($post, $metabox) {
    $fields = $metabox['args']['fields'];
    wp_nonce_field('snn_save_custom_fields', 'snn_custom_fields_nonce');
    echo '<div class="snn-metabox-wrapper" style="display:flex;flex-wrap:wrap;">';

    foreach ($fields as $field) {
        $field_name  = $field['name'];
        $col_width   = !empty($field['column_width']) ? intval($field['column_width']) : 100;
        $field_value = get_post_meta($post->ID, $field_name, true);

        if (is_array($field_value)) {
            $field_value = array_filter($field_value, function($val) {
                return $val !== '';
            });
        }
        
        // Determine the label: use new 'label' field if it exists, otherwise fallback to generating from 'name'
        $field_label = (!empty($field['label'])) ? $field['label'] : ucwords(str_replace('_',' ',$field_name));

        echo '<div class="snn-field-wrap snn-field-type-' . esc_attr($field['type']) 
               . (!empty($field['repeater']) ? ' snn-is-repeater' : '') 
               . '" style="width:calc(' . $col_width . '% - 20px);margin-right:20px;margin-bottom:15px;box-sizing:border-box;">';

        echo '<label class="snn-field-label" for="' . esc_attr($field_name . '_0') . '">'
             . esc_html($field_label) . '</label>';
        
        if (!empty($field['repeater'])) {
            $values = (is_array($field_value)) ? $field_value : [];
            echo '<div class="repeater-container" data-field-name="' . esc_attr($field_name) . '" data-name-prefix="custom_fields">';

            if (!empty($values)) {
                foreach ($values as $index => $value) {
                    echo '<div class="repeater-item">';
                    echo '<div class="repeater-content">';
                    snn_render_field_input($field, $value, $index, 'meta'); 
                    echo '</div>';
                    echo '<button type="button" class="button remove-repeater-item">' . esc_html__('Remove', 'snn') . '</button>';
                    echo '</div>';
                }
            }

            echo '<div class="repeater-item repeater-template" style="display:none;">';
            echo '<div class="repeater-content">';
            snn_render_field_input($field, '', '__index__', 'meta'); 
            echo '</div>';
            echo '<button type="button" class="button remove-repeater-item">' . esc_html__('Remove', 'snn') . '</button>';
            echo '</div>';

            echo '<button type="button" class="button add-repeater-item">' . esc_html__('Add More +', 'snn') . '</button>';
            echo '</div>';
        } else {
            snn_render_field_input($field, $field_value, '0', 'meta'); 
        }

        echo '</div>';
    }
    echo '</div>';
    ?>
    <style>
    .snn-field-wrap {
        padding: 10px; 
        border: 1px solid #eee !important; 
        border-radius: 5px; 
        background: #fff;
    }
    .snn-field-label {
        display: block;
        font-weight: bold;
        margin-bottom: 8px;
        font-size: 14px;
    }
    .snn-field-wrap input[type="text"],
    .snn-field-wrap input[type="number"],
    .snn-field-wrap input[type="url"],
    .snn-field-wrap input[type="email"],
    .snn-field-wrap input[type="date"],
    .snn-field-wrap input[type="time"],
    .snn-field-wrap select,
    .snn-field-wrap textarea {
        width: 100%;
        max-width: 600px;
        padding: 8px;
        margin-bottom: 5px;
        box-sizing: border-box; 
    }
    .snn-field-wrap textarea {
        min-height: 80px;
    }
    .snn-field-type-true_false input[type="checkbox"] {
        margin-top: 5px; 
        width: auto;
    }
    .repeater-container {
        margin-top: 5px;
    }
    .repeater-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 10px;
        padding: 10px;
        background: #f9f9f9;
        border: 1px solid #e5e5e5;
        border-radius: 3px;
    }
    .repeater-content {
        flex-grow: 1;
    }
    .remove-repeater-item {
        align-self: center; 
    }
    .add-repeater-item {
        margin-top: 5px;
    }
    .snn-field-type-media .media-uploader {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .snn-field-type-media .media-preview-wrapper { 
        width:50px; 
        height:50px;
        display: inline-flex; 
        justify-content: center;
        align-items: center;
        border: 1px solid #ddd; 
        padding: 2px; 
        background: #fff;
        vertical-align: middle;
        overflow: hidden; 
    }
    .snn-field-type-media .media-preview-wrapper img,
    .snn-field-type-media .media-preview-wrapper .dashicons {
        max-width: 100%;
        max-height: 100%;
        object-fit: cover; 
    }
     .snn-field-type-media .media-preview-wrapper .dashicons {
        font-size: 40px; 
        line-height: 50px; 
        width: 50px; 
        text-align: center;
    }

    .snn-field-type-media .media-uploader button {
        vertical-align: middle;
    }
    .snn-field-type-checkbox .choice-item,
    .snn-field-type-radio .choice-item {
        display: block;
        margin-bottom: 5px;
    }
    .snn-field-type-checkbox .choice-item input,
    .snn-field-type-radio .choice-item input {
        margin-right: 5px; 
        width: auto; 
        vertical-align: middle;
    }
    .media-filename {
        font-size: 12px; 
        color: #555; 
        margin-top: 4px;
        word-break: break-all; 
    }
    </style>
    <?php
}

function snn_render_field_input($field, $value = '', $index = '0', $context = 'meta') {
    $field_name = $field['name'];
    $field_type = $field['type'];
    $is_template = ($index === '__index__');
    $disabled_attr = $is_template ? ' disabled' : '';

    $prefix = ($context === 'options_page') ? 'snn_page_options' : 'custom_fields';
    $base_field_part = esc_attr($field_name);

    if (!empty($field['repeater'])) {
        $currentIndex = $is_template ? '__index__' : intval($index);
        if ($field_type === 'checkbox') { 
            $name_attribute = $prefix . '[' . $base_field_part . '][' . $currentIndex . '][]';
        } else { 
            $name_attribute = $prefix . '[' . $base_field_part . '][' . $currentIndex . ']';
        }
    } else { 
        if ($field_type === 'checkbox') { 
            $name_attribute = $prefix . '[' . $base_field_part . '][]';
        } else { 
            $name_attribute = $prefix . '[' . $base_field_part . ']';
        }
    }
    
    $id_attribute_base = esc_attr($field_name . '_' . ($is_template ? '__index__' : $index));

    $choices = [];
    if (in_array($field_type, ['select','checkbox','radio']) && !empty($field['choices'])) {
        $lines = explode("\n", trim($field['choices']));
        foreach ($lines as $line) {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $val   = trim($parts[0]);
                $label = trim($parts[1]);
                if ($val !== '') { 
                    $choices[$val] = $label;
                }
            }
        }
    }

    switch ($field_type) {
        case 'text':
            echo '<input type="text" id="' . $id_attribute_base
                 . '" name="' . esc_attr($name_attribute) . '" value="' . esc_attr($value) . '"' . $disabled_attr . ' />';
            break;

        case 'number':
            echo '<input type="number" id="' . $id_attribute_base
                 . '" name="' . esc_attr($name_attribute) . '" value="' . esc_attr($value) . '" step="any"' . $disabled_attr . ' />';
            break;

        case 'textarea':
        case 'basic_rich_text':
               echo '<textarea class="snn-rich-text-editor" id="' . $id_attribute_base
                     . '" name="' . esc_attr($name_attribute) . '"' . $disabled_attr . '>' . esc_textarea($value) . '</textarea>';
               break;

        case 'rich_text':
            $editor_id = preg_replace('/\[|\]/', '_', $name_attribute);
            $editor_id = rtrim($editor_id, '_'); 
            $editor_id = sanitize_key($editor_id); 
            if ($is_template) { 
                 $editor_id .= '_template';
            }

            wp_editor(wp_kses_post($value), $editor_id, [
                'textarea_name' => $name_attribute,
                'disabled'      => $is_template,
                'media_buttons' => true,
                'textarea_rows' => 10,
                'tinymce'       => true,
                'quicktags'     => true
            ]);
            break;

        case 'media':
            $img_src = '';
            $filename = '';
            $dashicon_class = 'dashicons-media-default'; 
            if (!empty($value)) {
                if (is_numeric($value)) { 
                    $attachment_id = intval($value);
                    $attachment = get_post($attachment_id);
                    if ($attachment) {
                        $filename = esc_html(basename(get_attached_file($attachment_id)));
                        $mime_type = get_post_mime_type($attachment_id);
                        if (strpos($mime_type, 'image/') === 0 || $mime_type === 'image/svg+xml') {
                            $image_array = wp_get_attachment_image_src($attachment_id, 'thumbnail');
                            if ($image_array) $img_src = $image_array[0];
                        } elseif (strpos($mime_type, 'video/') === 0) $dashicon_class = 'dashicons-media-video';
                        elseif (strpos($mime_type, 'audio/') === 0) $dashicon_class = 'dashicons-media-audio';
                        elseif ($mime_type === 'application/pdf') $dashicon_class = 'dashicons-media-document';
                        elseif (strpos($mime_type, 'application/') === 0 || strpos($mime_type, 'text/') === 0) $dashicon_class = 'dashicons-media-spreadsheet'; 
                    }
                } else { 
                    $filename = esc_html(basename($value));
                    $file_url_lower = strtolower($value);
                    if (preg_match('/\.(jpg|jpeg|png|gif|svg)$/', $file_url_lower)) $img_src = esc_url($value);
                    elseif (preg_match('/\.pdf$/', $file_url_lower)) $dashicon_class = 'dashicons-media-document';
                    elseif (preg_match('/\.(mp4|mov|avi|wmv|m4v|mkv)$/', $file_url_lower)) $dashicon_class = 'dashicons-media-video';
                    elseif (preg_match('/\.(mp3|wav|ogg|m4a|flac)$/', $file_url_lower)) $dashicon_class = 'dashicons-media-audio';
                }
            }

            echo '<div class="media-uploader">';
            echo '<input type="hidden" class="media-value-field" id="' . $id_attribute_base
                 . '" name="' . esc_attr($name_attribute) . '" value="' . esc_attr($value) . '"' . $disabled_attr . ' />';
            echo '<span class="media-preview-wrapper">';
            if ($img_src) {
                echo '<img src="' . esc_url($img_src) . '" class="media-preview" />';
            } else {
                echo '<span class="dashicons ' . esc_attr($dashicon_class) . ' media-preview"></span>';
            }
            echo '</span>';
            echo '<button type="button" class="button media-upload-button"'. $disabled_attr .'>' . esc_html__('Select', 'snn') . '</button>';
            echo '<button type="button" class="button media-remove-button" style="' . (empty($value)?'display:none;':'') . '"'. $disabled_attr .'>X</button>';
            if (!empty($filename)) {
                 echo '<div class="media-filename">' . $filename . '</div>';
            }
            echo '</div>';
            break;

        case 'date':
            echo '<input type="date" id="' . $id_attribute_base
                 . '" name="' . esc_attr($name_attribute) . '" value="' . esc_attr($value) 
                 . '" placeholder="YYYY-MM-DD" class="snn-datepicker"' . $disabled_attr . ' />';
            break;


        case 'time':
            echo '<input type="time" id="' . $id_attribute_base
                 . '" name="' . esc_attr($name_attribute) . '" value="' . esc_attr($value) 
                 . '" placeholder="HH:MM" class="snn-timepicker"' . $disabled_attr . ' />';
            break;


        case 'color':
            $color_value = esc_attr($value ? $value : '#000000');
            echo '<input type="color" id="' . $id_attribute_base
                 . '" name="' . esc_attr($name_attribute) . '" value="' . $color_value
                 . '" style="padding: 2px; height: 40px; width: 80px;"' . $disabled_attr . ' />';
            break;

        case 'select':
            echo '<select id="' . $id_attribute_base . '" name="' . esc_attr($name_attribute) . '"' . $disabled_attr . '>';
            echo '<option value="">-- ' . esc_html__('Select', 'snn') . ' --</option>';
            if (!empty($choices)) {
                foreach ($choices as $val => $label) {
                    echo '<option value="' . esc_attr($val) . '" ' . selected($value, $val, false) . '>'
                         . esc_html($label) . '</option>';
                }
            }
            echo '</select>';
            break;

        case 'checkbox':
            $checked_values = (is_array($value)) ? $value : ((!empty($value) || $value === '0') ? [$value] : []);
            echo '<div class="checkbox-group">';
            if (!empty($choices)) {
                $i = 0;
                foreach ($choices as $val => $label) {
                    $choice_id = $id_attribute_base . '_' . $i++;
                    $is_checked = in_array((string)$val, array_map('strval', $checked_values), true); 
                    echo '<span class="choice-item">';
                    echo '<input type="checkbox" id="' . esc_attr($choice_id) 
                         . '" name="' . esc_attr($name_attribute) 
                         . '" value="' . esc_attr($val) . '" ' . ($is_checked?'checked':'') . $disabled_attr . ' />';
                    echo '<label for="' . esc_attr($choice_id) . '">' . esc_html($label) . '</label>';
                    echo '</span>';
                }
            } else {
                echo '<em>' . esc_html__('No choices defined.', 'snn') . '</em>';
            }
            echo '</div>';
            break;

        case 'radio':
            echo '<div class="radio-group">';
            if (!empty($choices)) {
                $i=0;
                foreach ($choices as $val => $label) {
                    $choice_id = $id_attribute_base . '_' . $i++;
                    echo '<span class="choice-item">';
                    echo '<input type="radio" id="' . esc_attr($choice_id) 
                         . '" name="' . esc_attr($name_attribute) 
                         . '" value="' . esc_attr($val) . '" ' . checked($value, $val, false) . $disabled_attr . ' />';
                    echo '<label for="' . esc_attr($choice_id) . '">' . esc_html($label) . '</label>';
                    echo '</span>';
                }
            } else {
                echo '<em>' . esc_html__('No choices defined.', 'snn') . '</em>';
            }
            echo '</div>';
            break;

        case 'true_false':
            echo '<input type="hidden" name="' . esc_attr($name_attribute) . '" value="0"' . $disabled_attr . ' />'; 
            echo '<input type="checkbox" id="' . $id_attribute_base 
                 . '" name="' . esc_attr($name_attribute) . '" value="1" ' . checked($value, '1', false) . $disabled_attr . ' />';
            break;

        case 'url':
            echo '<input type="url" id="' . $id_attribute_base
                 . '" name="' . esc_attr($name_attribute) . '" value="' . esc_attr($value) 
                 . '" placeholder="https://example.com"' . $disabled_attr . ' />';
            break;

        case 'email':
            echo '<input type="email" id="' . $id_attribute_base
                 . '" name="' . esc_attr($name_attribute) . '" value="' . esc_attr($value) 
                 . '" placeholder="name@example.com"' . $disabled_attr . ' />';
            break;

        default: 
            echo '<input type="text" id="' . $id_attribute_base
                 . '" name="' . esc_attr($name_attribute) . '" value="' . esc_attr($value) . '"' . $disabled_attr . ' />';
            break;
    }
}

function snn_save_custom_fields_meta($post_id) {
    if (!isset($_POST['snn_custom_fields_nonce']) || !wp_verify_nonce($_POST['snn_custom_fields_nonce'], 'snn_save_custom_fields')) {
        return $post_id;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }
    
    $post_type_object = get_post_type_object(get_post_type($post_id));
    if (!$post_type_object || !current_user_can($post_type_object->cap->edit_post, $post_id)) {
        return $post_id;
    }

    if (wp_is_post_revision($post_id)) {
        return $post_id;
    }

    $custom_fields = get_option('snn_custom_fields', []);
    $posted_data   = $_POST['custom_fields'] ?? [];

    foreach ($custom_fields as $field) {
        $field_name = $field['name'];
        $current_post_type = get_post_type($post_id);

        if (empty($field['post_type']) || !in_array($current_post_type, $field['post_type'])) {
            continue;
        }

        if (isset($posted_data[$field_name])) {
            $raw_value = $posted_data[$field_name];
            if (is_array($raw_value)) { 
                $sanitized_values = array_map(function($item) use ($field) {
                    return snn_sanitize_value_by_type($field['type'], $item, $field);
                }, $raw_value);
                $sanitized_values = array_filter($sanitized_values, function($v) {
                    return ($v !== null && $v !== ''); 
                });
                $sanitized_values = array_values($sanitized_values);
                if (!empty($sanitized_values)) {
                    update_post_meta($post_id, $field_name, $sanitized_values);
                } else {
                    delete_post_meta($post_id, $field_name);
                }
            } else { 
                $sanitized_value = snn_sanitize_value_by_type($field['type'], $raw_value, $field);
                if ($sanitized_value !== '' && $sanitized_value !== null) {
                    update_post_meta($post_id, $field_name, $sanitized_value);
                } else {
                    delete_post_meta($post_id, $field_name);
                }
            }
        } else { 
            if ($field['type'] === 'true_false') {
                update_post_meta($post_id, $field_name, '0'); 
            } elseif ($field['type'] === 'checkbox') {
                delete_post_meta($post_id, $field_name); 
            }
        }
    }
}
add_action('save_post', 'snn_save_custom_fields_meta');

function snn_register_dynamic_taxonomy_fields() {
    $custom_fields = get_option('snn_custom_fields', []);
    if (!empty($custom_fields)) {
        foreach ($custom_fields as $field) {
            if (!empty($field['repeater'])) {
                continue;
            }
            if (!empty($field['taxonomies']) && is_array($field['taxonomies'])) {
                foreach ($field['taxonomies'] as $tax) {
                    add_action($tax . '_add_form_fields', function($taxonomy_slug) use ($field) { // Parameter is taxonomy slug
                        $field_label = (!empty($field['label'])) ? $field['label'] : ucwords(str_replace('_',' ',$field['name']));
                        ?>
                        <div class="form-field snn-metabox-wrapper"> 
                            <div class="snn-field-wrap" style="width:100%;box-sizing:border-box; padding:10px 0;"> 
                                <label for="<?php echo esc_attr($field['name'] . '_0'); ?>"><?php echo esc_html($field_label); ?></label>
                                <?php snn_render_field_input($field, '', '0', 'meta'); ?>
                            </div>
                        </div>
                        <?php
                    }, 10, 1); 

                    add_action($tax . '_edit_form_fields', function($term) use ($field) {
                        $value = get_term_meta($term->term_id, $field['name'], true);
                        $field_label = (!empty($field['label'])) ? $field['label'] : ucwords(str_replace('_',' ',$field['name']));
                        ?>
                        <tr class="form-field snn-metabox-wrapper">
                            <th scope="row">
                                <label for="<?php echo esc_attr($field['name'] . '_0'); ?>"><?php echo esc_html($field_label); ?></label>
                            </th>
                            <td> 
                                <div class="snn-field-wrap" style="box-sizing:border-box;">
                                    <?php snn_render_field_input($field, $value, '0', 'meta'); ?>
                                </div>
                            </td>
                        </tr>
                        <?php
                    }, 10, 1); 

                    add_action('created_' . $tax, 'snn_save_taxonomy_field_data', 10, 1); 
                    add_action('edited_' . $tax, 'snn_save_taxonomy_field_data', 10, 1);  
                }
            }
        }
        add_action('admin_print_styles-edit-tags.php', 'snn_print_tax_styles');
        add_action('admin_print_styles-term.php', 'snn_print_tax_styles');
    }
}
add_action('admin_init', 'snn_register_dynamic_taxonomy_fields');

function snn_print_tax_styles() {
    if (wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }
    ?>
    <style>
    .form-field .snn-metabox-wrapper { 
        background: transparent; 
        border: none;
        padding: 0;
        margin-bottom: 10px; 
    }
    .form-field .snn-field-wrap { 
        padding: 0; 
    }
    .snn-field-wrap label { 
        display: block;
        font-weight: bold;
        margin-bottom: 8px;
    }
    .snn-field-wrap input[type="text"],
    .snn-field-wrap input[type="number"],
    .snn-field-wrap input[type="url"],
    .snn-field-wrap input[type="email"],
    .snn-field-wrap input[type="date"],
    .snn-field-wrap input[type="time"], 
    .snn-field-wrap select,
    .snn-field-wrap textarea {
        width: 100%;
        max-width: 600px; 
        padding: 8px;
        margin-bottom: 5px;
        box-sizing: border-box;
    }
    .snn-field-wrap textarea {
        min-height: 80px;
    }
    .snn-field-type-media .media-uploader {
        margin-top: 5px;
    }
    </style>
    <?php
}

function snn_save_taxonomy_field_data($term_id) {
    $term = get_term($term_id);
    if (!$term || is_wp_error($term)) return;
    $taxonomy_obj = get_taxonomy($term->taxonomy);
    if (!current_user_can($taxonomy_obj->cap->edit_terms)) { 
        return;
    }

    $custom_fields = get_option('snn_custom_fields', []);
    $posted_data   = $_POST['custom_fields'] ?? []; 

    foreach ($custom_fields as $field) {
        if (!empty($field['repeater'])) { 
            continue;
        }
        if (empty($field['taxonomies']) || !in_array($term->taxonomy, $field['taxonomies'])) {
            continue;
        }

        $field_name = $field['name'];
        if (isset($posted_data[$field_name])) {
            $raw_value = $posted_data[$field_name];
            if (is_array($raw_value)) { 
                $sanitized = array_map(function($v) use ($field) {
                    return snn_sanitize_value_by_type($field['type'], $v, $field);
                }, $raw_value);
                $sanitized = array_filter($sanitized, function($v) { return ($v !== '' && $v !== null); });
                $sanitized = array_values($sanitized);
                if (!empty($sanitized)) {
                    update_term_meta($term_id, $field_name, $sanitized);
                } else {
                    delete_term_meta($term_id, $field_name);
                }
            } else { 
                $san = snn_sanitize_value_by_type($field['type'], $raw_value, $field);
                if ($san !== '' && $san !== null) {
                    update_term_meta($term_id, $field_name, $san);
                } else {
                    delete_term_meta($term_id, $field_name);
                }
            }
        } else { 
            if ($field['type'] === 'true_false') {
                update_term_meta($term_id, $field_name, '0');
            } elseif ($field['type'] === 'checkbox') {
                 delete_term_meta($term_id, $field_name); 
            }
        }
    }
}

function snn_add_author_profile_fields() {
    $custom_fields = get_option('snn_custom_fields', []);
    $author_fields = [];

    foreach ($custom_fields as $field) {
        if (!empty($field['author']) && empty($field['repeater'])) { 
            $author_fields[] = $field;
        }
    }
    if (!empty($author_fields)) {
        add_action('show_user_profile', 'snn_display_author_custom_fields');
        add_action('edit_user_profile', 'snn_display_author_custom_fields');
        add_action('personal_options_update', 'snn_save_author_custom_fields');
        add_action('edit_user_profile_update', 'snn_save_author_custom_fields');
    }
}
add_action('admin_init', 'snn_add_author_profile_fields');

function snn_display_author_custom_fields($user) {
    if (!current_user_can('edit_user', $user->ID)) { 
        return;
    }
    $custom_fields = get_option('snn_custom_fields', []);
    $author_fields = [];
    $needs_media = false;

    foreach ($custom_fields as $field_config) {
        if (!empty($field_config['author']) && empty($field_config['repeater'])) { 
            $author_fields[] = $field_config;
            if ($field_config['type'] === 'media') $needs_media = true;
        }
    }

    if (empty($author_fields)) {
        return;
    }

    if ($needs_media) wp_enqueue_media();
    if ($needs_media) { 
        add_action('admin_footer', 'snn_output_dynamic_field_js', 20); 
    }

    ?>
    <h2><?php esc_html_e('Custom Author Information', 'snn'); ?></h2>
    <?php wp_nonce_field('snn_save_author_fields_' . $user->ID, 'snn_author_fields_nonce'); ?>
    <table class="form-table snn-metabox-wrapper" role="presentation" style="border:none; background:transparent; padding:0;"> 
        <tbody>
        <?php
        foreach ($author_fields as $field) {
            $field_name = $field['name'];
            $value      = get_user_meta($user->ID, $field_name, true);
            $field_label = (!empty($field['label'])) ? $field['label'] : ucwords(str_replace('_',' ',$field_name));
            ?>
            <tr class="snn-field-wrap snn-field-type-<?php echo esc_attr($field['type']); ?>">
                <th>
                    <label for="<?php echo esc_attr($field_name . '_0'); ?>"><?php echo esc_html($field_label); ?></label>
                </th>
                <td>
                    <?php snn_render_field_input($field, $value, '0', 'meta'); ?>
                </td>
            </tr>
            <?php
        }
        ?>
        </tbody>
    </table>
    <style>
    .form-table.snn-metabox-wrapper { 
        background: transparent;
        border: none;
        padding: 0;
    }
    .form-table .snn-field-wrap th,
    .form-table .snn-field-wrap td {
        padding-top: 10px;
        padding-bottom: 10px;
    }
    .snn-field-wrap input[type="text"],
    .snn-field-wrap input[type="number"],
    .snn-field-wrap input[type="url"],
    .snn-field-wrap input[type="email"],
    .snn-field-wrap input[type="date"],
    .snn-field-wrap input[type="time"],
    .snn-field-wrap select,
    .snn-field-wrap textarea {
        width: 100%; 
        max-width: 400px; 
        padding: 6px;
        box-sizing: border-box;
    }
    .snn-field-wrap textarea {
        min-height: 80px;
    }
    .snn-field-wrap input[type="checkbox"] { 
        width: auto;
    }
    .snn-field-type-media .media-uploader {
        margin-top: 5px;
    }
    </style>
    <?php
}

function snn_save_author_custom_fields($user_id) {
    if (!isset($_POST['snn_author_fields_nonce']) ||  
        !wp_verify_nonce($_POST['snn_author_fields_nonce'], 'snn_save_author_fields_' . $user_id)) {
        return;
    }
    if (!current_user_can('edit_user', $user_id)) { 
        return;
    }
    $custom_fields = get_option('snn_custom_fields', []);
    $posted_data   = $_POST['custom_fields'] ?? []; 

    foreach ($custom_fields as $field) {
        if (empty($field['author']) || !empty($field['repeater'])) { 
            continue;
        }
        $field_name = $field['name'];
        if (isset($posted_data[$field_name])) {
            $raw_value = $posted_data[$field_name];
            if (is_array($raw_value)) { 
                $vals = array_map(function($v_item) use ($field) {
                    return snn_sanitize_value_by_type($field['type'], $v_item, $field);
                }, $raw_value);
                $vals = array_filter($vals, function($v_item) { return ($v_item !== '' && $v_item !== null); });
                $vals = array_values($vals);
                if (!empty($vals)) {
                    update_user_meta($user_id, $field_name, $vals);
                } else {
                    delete_user_meta($user_id, $field_name);
                }
            } else { 
                $san = snn_sanitize_value_by_type($field['type'], $raw_value, $field);
                if ($san !== '' && $san !== null) {
                    update_user_meta($user_id, $field_name, $san);
                } else {
                    delete_user_meta($user_id, $field_name);
                }
            }
        } else { 
            if ($field['type'] === 'true_false') {
                update_user_meta($user_id, $field_name, '0');
            } elseif ($field['type'] === 'checkbox') {
                delete_user_meta($user_id, $field_name); 
            }
        }
    }
}

function snn_sanitize_value_by_type($type, $value, $field = null) {
    switch ($type) {
        case 'rich_text':
        case 'basic_rich_text':
        case 'textarea':
            return wp_kses_post($value);

        case 'media':
            if ($field && !empty($field['return_full_url'])) {
                if (is_numeric($value)) {
                    $url = wp_get_attachment_url(intval($value));
                    return $url ? esc_url_raw($url) : '';
                } else { 
                    return $value ? esc_url_raw(trim($value)) : '';
                }
            } else { 
                return $value ? intval($value) : '';
            }

        case 'number':
            return ($value === '' || $value === null) ? '' : (is_numeric($value) ? floatval($value) : '');

        case 'date': 
            if (preg_match("/^\d{4}-\d{2}-\d{2}$/", $value)) {
                 return sanitize_text_field($value);
            }
             return '';
        case 'color': 
            return sanitize_hex_color($value); 
        case 'select': 
        case 'radio':  
            return sanitize_text_field($value); 
        
        case 'checkbox': 
            return sanitize_text_field($value); 

        case 'time': 
            return preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value) ? sanitize_text_field($value) : '';

        case 'true_false':
            return ($value == '1' || $value === true || $value === 'true') ? '1' : '0';

        case 'url':
            return esc_url_raw(trim($value));

        case 'email':
            return sanitize_email($value);

        default: 
            return sanitize_text_field($value);
    }
}

function snn_output_dynamic_field_js() {
    if (wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }
    static $snn_dynamic_js_outputted = false;
    if ($snn_dynamic_js_outputted) {
        return;
    }
    $snn_dynamic_js_outputted = true;
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {

        $(document).off('click', '.media-upload-button').on('click', '.media-upload-button', function(e) {
            e.preventDefault();
            var $button = $(this);
            var $uploader = $button.closest('.media-uploader');
            var $repeaterItem = $button.closest('.repeater-item');
            var $container = $button.closest('.repeater-container');
            var $previewWrapper = $uploader.find('.media-preview-wrapper');
            var $remove = $uploader.find('.media-remove-button');
            var $input = $uploader.find('.media-value-field');
            var $filenameDisplay = $uploader.find('.media-filename');
            if (!$filenameDisplay.length) { 
                $filenameDisplay = $('<div class="media-filename"></div>').insertAfter($remove);
            }

            // If inside a repeater, allow multiple selection
            var allowMultiple = !!$container.length;
            var frame = wp.media({
                title: '<?php esc_html_e('Choose Media', 'snn'); ?>',
                button: { text: allowMultiple ? '<?php esc_html_e('Add Selected', 'snn'); ?>' : '<?php esc_html_e('Select', 'snn'); ?>' },
                multiple: allowMultiple
            });

            frame.on('select', function() {
                var selection = frame.state().get('selection');
                if (allowMultiple && selection.length > 1) {
                    // Remove this empty row if still empty
                    if ($input.val() === '') $repeaterItem.remove();

                    selection.each(function(attachment) {
                        var att = attachment.toJSON();
                        // Create a new repeater item for each media
                        var $template = $container.find('.repeater-template').clone(true).removeClass('repeater-template').show();
                        $template.find('input, select, textarea, button').prop('disabled', false);

                        var $newInput = $template.find('.media-value-field');
                        var $newPreview = $template.find('.media-preview-wrapper');
                        var $newFilename = $template.find('.media-filename');
                        var $newRemove = $template.find('.media-remove-button');

                        $newInput.val(att.id);

                        var mimeType = att.mime || '';
                        var imageURL = (att.sizes && att.sizes.thumbnail) ? att.sizes.thumbnail.url : att.url;
                        var dashiconClass = 'dashicons-media-default';
                        $newPreview.empty();
                        if (mimeType.indexOf('image/') === 0 || mimeType === 'image/svg+xml') {
                            $newPreview.html('<img src="'+ imageURL +'" class="media-preview" />');
                        } else {
                            if (mimeType.indexOf('video/') === 0) dashiconClass = 'dashicons-media-video';
                            else if (mimeType.indexOf('audio/') === 0) dashiconClass = 'dashicons-media-audio';
                            else if (mimeType === 'application/pdf') dashiconClass = 'dashicons-media-document';
                            else if (mimeType.indexOf('application/') === 0 || mimeType.indexOf('text/') === 0) dashiconClass = 'dashicons-media-spreadsheet'; 
                            $newPreview.html('<span class="dashicons '+dashiconClass+' media-preview"></span>');
                        }
                        $newFilename.text(att.filename).show();
                        $newRemove.show();

                        // Insert the new item before the Add More button
                        $container.find('.add-repeater-item').before($template);
                    });
                    // Reindex all repeater items after adding
                    reindexRepeaterItems($container);
                } else {
                    // Single selection (original logic)
                    var attachment = selection.first().toJSON();
                    $input.val(attachment.id);
                    var mimeType = attachment.mime || '';
                    var imageURL = (attachment.sizes && attachment.sizes.thumbnail) ? attachment.sizes.thumbnail.url : attachment.url;
                    var dashiconClass = 'dashicons-media-default';

                    $previewWrapper.empty();
                    if (mimeType.indexOf('image/') === 0 || mimeType === 'image/svg+xml') {
                        $previewWrapper.html('<img src="'+ imageURL +'" class="media-preview" />');
                    } else {
                        if (mimeType.indexOf('video/') === 0) dashiconClass = 'dashicons-media-video';
                        else if (mimeType.indexOf('audio/') === 0) dashiconClass = 'dashicons-media-audio';
                        else if (mimeType === 'application/pdf') dashiconClass = 'dashicons-media-document';
                        else if (mimeType.indexOf('application/') === 0 || mimeType.indexOf('text/') === 0) dashiconClass = 'dashicons-media-spreadsheet'; 
                        $previewWrapper.html('<span class="dashicons '+dashiconClass+' media-preview"></span>');
                    }
                    $filenameDisplay.text(attachment.filename).show();
                    $remove.show();
                }
            });
            frame.open();
        });


        $(document).on('click', '.media-remove-button', function(e) {
            e.preventDefault();
            var $btn      = $(this);
            var $uploader = $btn.closest('.media-uploader');
            $uploader.find('.media-value-field').val('');
            $uploader.find('.media-preview-wrapper').empty(); 
            $uploader.find('.media-filename').empty().hide(); 
            $btn.hide();
        });

        $(document).on('click', '.add-repeater-item', function(e) {
            e.preventDefault();
            var $container = $(this).closest('.repeater-container');
            var $template  = $container.find('.repeater-template').first().clone(true); 
            
            $template.removeClass('repeater-template').show();
            $template.find('input, select, textarea, button').prop('disabled', false);

            $template.insertBefore($(this)); 

            reindexRepeaterItems($container);
        });

        $(document).on('click', '.remove-repeater-item', function(e) {
            e.preventDefault();
            var $item      = $(this).closest('.repeater-item');
            var $container = $item.closest('.repeater-container');
            $item.remove();
            reindexRepeaterItems($container);
        });

        function reindexRepeaterItems($container) {
            var namePrefix = $container.data('name-prefix') || 'custom_fields';
            var mainFieldName = $container.data('field-name'); 

            $container.find('.repeater-item').not('.repeater-template').each(function(currentIndex) {
                var $item = $(this);
                $item.find('input, select, textarea, button').each(function() { 
                    var $el = $(this);
                    var oldName = $el.attr('name');
                    var oldId = $el.attr('id');

                    if (oldName) {
                        var newName = oldName.replace(
                            new RegExp("(\\b" + namePrefix + "\\b\\[" + mainFieldName + "\\]\\[)(?:__index__|\\d+)(\\](?:\\[\\])?)", "g"),
                            '$1' + currentIndex + '$2'
                        );
                        $el.attr('name', newName);
                    }

                    if (oldId) {
                        var newId = oldId.replace(
                            new RegExp("(\\b" + mainFieldName + "_)(?:__index__|\\d+)(.*)", "g"),
                            '$1' + currentIndex + '$2'
                        );
                        if (newId !== oldId) {
                            $el.attr('id', newId);
                            var $label = $item.find('label[for="' + oldId + '"]');
                            if ($label.length) {
                                $label.attr('for', newId);
                            }
                            if ($el.hasClass('wp-editor-area')) {
                                var editorWrapId = newId + '-wrap'; 
                                var $editorWrap = $('#' + editorWrapId);
                            }
                        }
                    }
                });
            });
        }
        
        if (typeof $.fn.datepicker === 'function') {
            $('input.snn-datepicker').each(function(){
                if (!$(this).data('datepicker-initialized')) {
                    $(this).datepicker({ dateFormat: 'yy-mm-dd' });
                    $(this).data('datepicker-initialized', true);
                }
            });
        }
         $(document).on('click', '.add-repeater-item', function() {
            var $newItem = $(this).prev('.repeater-item'); 
            if (typeof $.fn.datepicker === 'function') {
                $newItem.find('input.snn-datepicker').each(function(){
                        if (!$(this).data('datepicker-initialized')) {
                             $(this).datepicker({ dateFormat: 'yy-mm-dd' });
                             $(this).data('datepicker-initialized', true);
                        }
                });
            }
        });

        if (typeof switchEditors !== 'undefined') {
            $('.wp-editor-wrap').each(function() { 
                var editorID = $(this).attr('id');
                if (editorID) {
                    editorID = editorID.replace('-wrap', '');
                    if ($(this).closest('.snn-field-wrap').length || $(this).closest('.snn-metabox-wrapper').length) {
                    }
                }
            });
        }
        $(document).on('click', '.add-repeater-item', function() {
            setTimeout(function() {
                if (typeof switchEditors !== 'undefined') {
                    $('.repeater-item:not(.repeater-template) .wp-editor-wrap').each(function() {
                        var editorID = $(this).attr('id');
                         if (editorID) {
                              editorID = editorID.replace('-wrap','');
                         }
                    });
                }
            }, 250); 
        });
    });
    </script>
    <?php
}

add_action('admin_footer', 'snn_init_tinymce_html_default', 100);
function snn_init_tinymce_html_default() {
    global $pagenow;
    if (in_array($pagenow, ['post-new.php', 'post.php'])) {
        $screen = get_current_screen();
        if ( isset($screen->post_type) ) { 
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                if (typeof switchEditors !== 'undefined' && $('#wp-content-wrap').hasClass('html-active')) {
                } else if (typeof switchEditors !== 'undefined' && $('#wp-content-wrap').hasClass('tmce-active')) {
                }
            });
            </script>
            <?php
        }
    }
}

add_action('admin_enqueue_scripts', function ($hook) {
    if (in_array($hook, ['edit-tags.php', 'term.php'], true)) {
        add_action('admin_footer', function () {
            ?>
            <script>
            jQuery(document).ready(function($) {
                $('form#addtag, form#edittag').on('submit', function(event) { 
                        const form = this;
                        const $submitButton = $(form).find('input[type="submit"], button[type="submit"]');
                });
            });
            </script>
            <?php
        });
    }
});
function snn_taxonomy_overview_js() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $(document).ajaxComplete(function(event, xhr, settings) {
            if (settings && settings.data && typeof settings.data === 'string' && (
                settings.data.includes('action=add-tag') ||   
                settings.data.includes('action=delete-tag') || 
                settings.data.includes('action=editedtag') ||  
                settings.data.includes('action=inline-save-tax') 
            )) {
                var isError = false;
                if (xhr.responseXML && $(xhr.responseXML).find('wp_error').length > 0) {
                    isError = true;
                } else if (xhr.responseText && xhr.responseText.startsWith('{"wp_error":true')) {
                    isError = true;
                }

                if (!isError) {
                    setTimeout(function() {
                        window.location.reload();
                    }, 300); 
                }
            }
        });
    });
    </script>
    <?php
}
add_action('admin_footer-edit-tags.php', 'snn_taxonomy_overview_js');

global $snn_options_pages_hooks;
$snn_options_pages_hooks = [];

add_action('admin_menu', 'snn_register_options_pages', 20); 
function snn_register_options_pages() {
    $custom_fields = get_option('snn_custom_fields', []);
    if (empty($custom_fields)) return;

    $options_page_groups = [];
    foreach ($custom_fields as $field) {
        if (!empty($field['options_page']) && !empty($field['group_name'])) {
            $options_page_groups[$field['group_name']][] = $field;
        }
    }

    if (empty($options_page_groups)) return;

    global $snn_options_pages_hooks;
    $base_position = 81; 

    foreach ($options_page_groups as $group_name => $fields_for_group) {
        if (empty($fields_for_group)) continue;

        $menu_slug = 'snn_options_' . sanitize_title($group_name);
        $page_title = esc_html($group_name) . __(' Settings', 'snn'); 
        $menu_title = esc_html($group_name);

        $hook = add_menu_page(
            $page_title,
            $menu_title,
            'manage_options', 
            $menu_slug,
            'snn_render_actual_options_page_callback', 
            'dashicons-admin-settings', 
            $base_position++ 
        );
        if ($hook) {
            $snn_options_pages_hooks[] = $hook;
        }
    }
}

function snn_render_actual_options_page_callback() {
    $current_screen = get_current_screen();
    $menu_slug_from_screen = $current_screen->id; 
    
    $options_page_slug_part = '';
    if (strpos($menu_slug_from_screen, 'toplevel_page_') === 0) {
        $options_page_slug_part = str_replace('toplevel_page_', '', $menu_slug_from_screen);
    } else { 
        $options_page_slug_part = $menu_slug_from_screen;
    }

    $group_name_sanitized = str_replace('snn_options_', '', $options_page_slug_part);

    $all_custom_fields = get_option('snn_custom_fields', []);
    $fields_for_this_page = [];
    $actual_group_name_display = '';

    foreach ($all_custom_fields as $field_config) {
        if (!empty($field_config['options_page']) && !empty($field_config['group_name'])) {
            if (sanitize_title($field_config['group_name']) === $group_name_sanitized) {
                $fields_for_this_page[] = $field_config;
                if (empty($actual_group_name_display)) {
                    $actual_group_name_display = $field_config['group_name']; 
                }
            }
        }
    }

    if (empty($fields_for_this_page)) {
        echo '<div class="wrap"><h1>' . esc_html__('Error', 'snn') . '</h1><p>' . esc_html__('No fields configured for this options page or group not found.', 'snn') . '</p></div>';
        return;
    }

    snn_options_page_form_handler($actual_group_name_display, $fields_for_this_page, $group_name_sanitized);
}


function snn_options_page_form_handler($group_name_display, $fields_for_page, $group_slug_sanitized) {
    if (isset($_POST['snn_options_page_nonce_' . $group_slug_sanitized]) && 
        wp_verify_nonce($_POST['snn_options_page_nonce_' . $group_slug_sanitized], 'snn_save_options_for_' . $group_slug_sanitized)) {
        
        $posted_options_data = $_POST['snn_page_options'] ?? [];

        foreach ($fields_for_page as $field_config) {
            $field_name = $field_config['name'];
            $option_key = 'snn_opt_' . $field_name; 

            if (isset($posted_options_data[$field_name])) {
                $raw_value = $posted_options_data[$field_name];
                if (is_array($raw_value)) { 
                    $sanitized_values = array_map(function($item) use ($field_config) {
                        return snn_sanitize_value_by_type($field_config['type'], $item, $field_config);
                    }, $raw_value);
                    $sanitized_values = array_filter($sanitized_values, function($v) { return ($v !== null && $v !== ''); });
                    $sanitized_values = array_values($sanitized_values);
                    if (!empty($sanitized_values)) {
                        update_option($option_key, $sanitized_values);
                    } else {
                        delete_option($option_key); 
                    }
                } else { 
                    $sanitized_value = snn_sanitize_value_by_type($field_config['type'], $raw_value, $field_config);
                    if ($sanitized_value !== '' && $sanitized_value !== null) {
                        update_option($option_key, $sanitized_value);
                    } else {
                        delete_option($option_key);
                    }
                }
            } else { 
                if ($field_config['type'] === 'true_false') {
                    update_option($option_key, '0'); 
                } elseif ($field_config['type'] === 'checkbox') {
                    delete_option($option_key); 
                } else {
                    delete_option($option_key);
                }
            }
        }
        echo '<div id="message" class="updated notice is-dismissible"><p>' . esc_html__('Settings saved.', 'snn') . '</p></div>';
    }

    echo '<div class="wrap">';
    echo '<h1>' . esc_html($group_name_display) . '</h1>';
    echo '<form method="post" action="">'; 
    
    wp_nonce_field('snn_save_options_for_' . $group_slug_sanitized, 'snn_options_page_nonce_' . $group_slug_sanitized);
    
    echo '<div class="snn-metabox-wrapper" style="display:flex;flex-wrap:wrap; background-color: #fff; padding: 20px; margin-top:15px; border: 1px solid #c3c4c7; box-shadow: 0 1px 1px rgba(0,0,0,.04);">';

    foreach ($fields_for_page as $field) {
        $field_name  = $field['name'];
        $option_key  = 'snn_opt_' . $field_name;
        $col_width   = !empty($field['column_width']) ? intval($field['column_width']) : 100;
        $field_value = get_option($option_key);
        $field_label = (!empty($field['label'])) ? $field['label'] : ucwords(str_replace('_',' ',$field_name));


        if (is_array($field_value)) { 
            $field_value = array_filter($field_value, function($val) { return $val !== ''; });
        }

        echo '<div class="snn-field-wrap snn-field-type-' . esc_attr($field['type']) 
             . (!empty($field['repeater']) ? ' snn-is-repeater' : '') 
             . '" style="width:calc(' . $col_width . '% - 20px); margin-right:20px; margin-bottom:15px; box-sizing:border-box; padding:10px; border:1px solid #eee; background:#fdfdfd;">';
        
        echo '<label class="snn-field-label" for="' . esc_attr($field_name . '_0') . '">' 
             . esc_html($field_label) . '</label>';

        $input_name_prefix = 'snn_page_options'; 

        if (!empty($field['repeater'])) {
            $values = (is_array($field_value)) ? $field_value : [];
            echo '<div class="repeater-container" data-field-name="' . esc_attr($field_name) . '" data-name-prefix="' . $input_name_prefix . '">';

            if (!empty($values)) {
                foreach ($values as $index => $value_item) {
                    echo '<div class="repeater-item">';
                    echo '<div class="repeater-content">';
                    snn_render_field_input($field, $value_item, $index, 'options_page');
                    echo '</div>';
                    echo '<button type="button" class="button remove-repeater-item">' . esc_html__('Remove', 'snn') . '</button>';
                    echo '</div>';
                }
            }
            echo '<div class="repeater-item repeater-template" style="display:none;">';
            echo '<div class="repeater-content">';
            snn_render_field_input($field, '', '__index__', 'options_page');
            echo '</div>';
            echo '<button type="button" class="button remove-repeater-item">' . esc_html__('Remove', 'snn') . '</button>';
            echo '</div>';
            echo '<button type="button" class="button add-repeater-item">' . esc_html__('Add More +', 'snn') . '</button>';
            echo '</div>';
        } else {
            snn_render_field_input($field, $field_value, '0', 'options_page');
        }
        echo '</div>'; 
    }
    echo '</div>'; 
    
    submit_button();
    echo '</form>';
    echo '</div>'; 
    ?>
    <style>
    .snn-metabox-wrapper{display:flex;flex-wrap:wrap;}
    .snn-field-wrap{
        padding:10px;
        border:1px solid #eee !important;
        border-radius:5px;
        background:#fff;
        box-sizing:border-box;
    }
    .snn-field-label{display:block;font-weight:bold;margin-bottom:8px;font-size:14px;}
    .snn-field-wrap input[type="text"],
    .snn-field-wrap input[type="number"],
    .snn-field-wrap input[type="url"],
    .snn-field-wrap input[type="email"],
    .snn-field-wrap input[type="date"],
    .snn-field-wrap input[type="time"],
    .snn-field-wrap select,
    .snn-field-wrap textarea{
        width:100%;
        max-width:600px;
        padding:8px;
        margin-bottom:5px;
        box-sizing:border-box;
    }
    .snn-field-wrap textarea{min-height:80px;}
    .snn-field-type-true_false input[type="checkbox"]{margin-top:5px;width:auto;}
    .repeater-container{margin-top:5px;}
    .repeater-item{
        display:flex;
        align-items:flex-start;
        gap:10px;
        margin-bottom:10px;
        padding:10px;
        background:#f9f9f9;
        border:1px solid #e5e5e5;
        border-radius:3px;
    }
    .repeater-content{flex-grow:1;}
    .snn-field-type-media .media-uploader{
        display:flex;
        align-items:center;
        gap:10px;
        flex-wrap:wrap;
    }
    .snn-field-type-media .media-preview-wrapper{
        width:50px;
        height:50px;
        display:inline-flex;
        justify-content:center;
        align-items:center;
        border:1px solid #ddd;
        padding:2px;
        background:#fff;
        overflow:hidden;
    }
    .snn-field-type-media .media-preview-wrapper img,
    .snn-field-type-media .media-preview-wrapper .dashicons{
        max-width:100%;
        max-height:100%;
        object-fit:cover;
    }
    .snn-field-type-media .media-preview-wrapper .dashicons{
        font-size:40px;
        line-height:50px;
        width:50px;
        text-align:center;
    }
    .snn-field-type-checkbox .choice-item,
    .snn-field-type-radio .choice-item{display:block;margin-bottom:5px;}
    .snn-field-type-checkbox .choice-item input,
    .snn-field-type-radio .choice-item input{margin-right:5px;width:auto;vertical-align:middle;}
    .media-filename{font-size:12px;color:#555;margin-top:4px;word-break:break-all;}
    </style>
    <?php
}

add_action('admin_enqueue_scripts', 'snn_enqueue_dynamic_options_page_scripts');
function snn_enqueue_dynamic_options_page_scripts($hook_suffix) {
    global $snn_options_pages_hooks; 
    if (empty($snn_options_pages_hooks) || !in_array($hook_suffix, $snn_options_pages_hooks)) {
        return; 
    }

    $current_screen = get_current_screen(); 
    $group_name_sanitized = str_replace(['toplevel_page_snn_options_', 'snn_options_'], '', $hook_suffix);

    $all_custom_fields = get_option('snn_custom_fields', []);
    $needs_media = false;
    $needs_repeater_js = false; 
    $needs_datepicker = false;
    $needs_basic_rich_text = false;

    foreach ($all_custom_fields as $field_cfg) {
        if (!empty($field_cfg['options_page']) && !empty($field_cfg['group_name']) && sanitize_title($field_cfg['group_name']) === $group_name_sanitized) {
            if ($field_cfg['type'] === 'media') $needs_media = true;
            if ($field_cfg['type'] === 'date') $needs_datepicker = true;
            if ($field_cfg['type'] === 'basic_rich_text') $needs_basic_rich_text = true;
            
            $disallowed_for_repeater = ['rich_text', 'basic_rich_text','select','checkbox','radio','true_false','url','email'];
            if (!in_array($field_cfg['type'], $disallowed_for_repeater) && !empty($field_cfg['repeater'])) {
                 $needs_repeater_js = true; 
            }
        }
    }

    if ($needs_media) {
        wp_enqueue_media();
        wp_enqueue_style('dashicons'); 
    }
    if ($needs_datepicker) {
        wp_enqueue_script('jquery-ui-datepicker');
    }
    if ($needs_basic_rich_text) {
        wp_enqueue_script('snn-rich-text-editor', plugin_dir_url(__FILE__) . 'assets/js/snn-rich-text-editor.js', ['jquery'], '1.1', true);
    }

    if ($needs_media || $needs_repeater_js || $needs_datepicker) { 
        add_action('admin_footer', 'snn_output_dynamic_field_js');
    }
}

?>
