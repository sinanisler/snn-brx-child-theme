<?php

function snn_add_custom_fields_submenu() {
    add_submenu_page(
        'snn-settings',
        'Register Custom Fields',
        'Custom Fields',
        'manage_options',
        'snn-custom-fields',
        'snn_custom_fields_page_callback'
    );
}
add_action('admin_menu', 'snn_add_custom_fields_submenu', 10);


if ( ! function_exists( 'is_plugin_active' ) ) {
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


function snn_custom_fields_page_callback() {
    $custom_fields = get_option('snn_custom_fields', []);
    $post_types    = get_post_types(['public' => true], 'objects');
    $taxonomies    = get_taxonomies(['public' => true], 'objects');


    if (isset($_POST['snn_custom_fields_nonce']) && wp_verify_nonce($_POST['snn_custom_fields_nonce'], 'snn_custom_fields_save')) {
        $new_fields = [];
        if (!empty($_POST['custom_fields']) && is_array($_POST['custom_fields'])) {
            foreach ($_POST['custom_fields'] as $field) {

                if (!empty($field['name']) && !empty($field['type']) && !empty($field['group_name'])) {
                    $post_types_selected = isset($field['post_type']) && is_array($field['post_type']) ? array_map('sanitize_text_field', $field['post_type']) : [];
                    $taxonomies_selected = isset($field['taxonomies']) && is_array($field['taxonomies']) ? array_map('sanitize_text_field', $field['taxonomies']) : [];
                    $choices_raw = isset($field['choices']) ? trim($field['choices']) : '';
                    $choices_sanitized = sanitize_textarea_field($choices_raw);

                    $field_type_for_repeater_check = isset($field['type']) ? $field['type'] : 'text';
                    $is_repeater_disabled_type = in_array($field_type_for_repeater_check, ['rich_text', 'select', 'checkbox', 'radio', 'true_false', 'url', 'email']);

                    $new_fields[] = [
                        'group_name' => sanitize_text_field($field['group_name']),
                        'name'       => sanitize_key($field['name']),
                        'type'       => sanitize_text_field($field['type']),
                        'post_type'  => $post_types_selected,
                        'taxonomies' => $taxonomies_selected,
                        'choices'    => $choices_sanitized,
                        'repeater'   => (!$is_repeater_disabled_type && !empty($field['repeater'])) ? 1 : 0,
                        'author'     => !empty($field['author']) ? 1 : 0,
                    ];
                }
            }
        }
        update_option('snn_custom_fields', $new_fields);
        $custom_fields = $new_fields;
        echo '<div class="updated"><p>Custom fields saved successfully.</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>Manage Custom Fields</h1>
        <form method="post">
            <?php wp_nonce_field('snn_custom_fields_save', 'snn_custom_fields_nonce'); ?>

            <div id="custom-field-settings">
                 <p>Define custom fields with group name, field name, field type, and post type, taxonomy, or author:<br>
                    Select one or more to register same Custom Field to Post Types, Taxonomies, or Author.<br>
                    Press CTRL/CMD to select multiple or to remove selection.
                 </p>
                <?php
                if (!empty($custom_fields) && is_array($custom_fields)) {
                    foreach ($custom_fields as $index => $field) {
                        $field_type = isset($field['type']) ? $field['type'] : 'text';
                        $show_choices = in_array($field_type, ['select', 'checkbox', 'radio']);
                        $is_repeater_disabled_type = in_array($field_type, ['rich_text', 'select', 'checkbox', 'radio', 'true_false', 'url', 'email']);
                        $repeater_title = $is_repeater_disabled_type ? 'This field type cannot be a repeater' : 'Allow multiple values';
                        ?>
                        <div class="custom-field-row" data-index="<?php echo $index; ?>">

                            <div class="buttons">
                                <button type="button" class="move-up">▲</button>
                                <button type="button" class="move-down">▼</button>
                                <button type="button" class="remove-field">Remove</button>
                            </div>

                            <div class="field-group">
                                <label>Group Name</label><br>
                                <input type="text" name="custom_fields[<?php echo $index; ?>][group_name]" placeholder="Group Name" value="<?php echo isset($field['group_name']) ? esc_attr($field['group_name']) : ''; ?>" />
                            </div>

                            <div class="field-group">
                                <label>Field Name</label><br>
                                <input type="text" class="sanitize-key" name="custom_fields[<?php echo $index; ?>][name]" placeholder="Field Name" value="<?php echo esc_attr($field['name']); ?>" />
                            </div>

                            <div class="field-group">
                                <label>Field Type</label><br>
                                <select name="custom_fields[<?php echo $index; ?>][type]" class="field-type-select" style="width:140px">
                                    <option value="text"      <?php selected($field_type, 'text'); ?>>Text</option>
                                    <option value="number"    <?php selected($field_type, 'number'); ?>>Number</option>
                                    <option value="textarea"  <?php selected($field_type, 'textarea'); ?>>Textarea</option>
                                    <option value="rich_text" <?php selected($field_type, 'rich_text'); ?>>Rich Text</option>
                                    <option value="media"     <?php selected($field_type, 'media'); ?>>Media</option>
                                    <option value="date"      <?php selected($field_type, 'date'); ?>>Date</option>
                                    <option value="color"     <?php selected($field_type, 'color'); ?>>Color</option>
                                    <option value="select"    <?php selected($field_type, 'select'); ?>>Select</option>
                                    <option value="checkbox"  <?php selected($field_type, 'checkbox'); ?>>Checkbox</option>
                                    <option value="radio"     <?php selected($field_type, 'radio'); ?>>Radio</option>
                                    <option value="true_false" <?php selected($field_type, 'true_false'); ?>>True / False</option>
                                    <option value="url"       <?php selected($field_type, 'url'); ?>>URL</option>
                                    <option value="email"     <?php selected($field_type, 'email'); ?>>Email</option>
                                </select>
                            </div>

                             <div class="field-group field-group-choices" style="<?php echo $show_choices ? '' : 'display: none;'; ?>">
                                <label>Choices <small><code>(value:label)</code></small></label><br>
                                <textarea name="custom_fields[<?php echo $index; ?>][choices]" rows="4" placeholder="red : Red Color&#10;green : Green Color"><?php echo isset($field['choices']) ? esc_textarea($field['choices']) : ''; ?></textarea>
                            </div>

                            <div class="field-group">
                                <label>Post Types</label><br>
                                <select name="custom_fields[<?php echo $index; ?>][post_type][]" multiple>
                                    <?php foreach ($post_types as $post_type) : ?>
                                        <option value="<?php echo esc_attr($post_type->name); ?>" <?php echo (!empty($field['post_type']) && is_array($field['post_type']) && in_array($post_type->name, $field['post_type'])) ? 'selected' : ''; ?>>
                                            <?php echo esc_html($post_type->label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="field-group">
                                <label>Taxonomies</label><br>
                                <select name="custom_fields[<?php echo $index; ?>][taxonomies][]" multiple>
                                    <?php foreach ($taxonomies as $tax) : ?>
                                        <option value="<?php echo esc_attr($tax->name); ?>" <?php echo (!empty($field['taxonomies']) && is_array($field['taxonomies']) && in_array($tax->name, $field['taxonomies'])) ? 'selected' : ''; ?>>
                                            <?php echo esc_html($tax->label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="field-group">
                                <label>Author</label><br>
                                <input type="checkbox" name="custom_fields[<?php echo $index; ?>][author]" value="1" <?php checked(!empty($field['author'])); ?> />
                            </div>

                            <div class="field-group">
                                <label>Repeater</label><br>
                                <input type="checkbox" class="repeater-checkbox" name="custom_fields[<?php echo $index; ?>][repeater]" value="1" <?php checked(!empty($field['repeater'])); ?> <?php echo $is_repeater_disabled_type ? 'disabled' : ''; ?> title="<?php echo esc_attr($repeater_title); ?>" />
                            </div>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
            <button type="button" id="add-custom-field-row">Add New Field</button>
            <br><br>
            <?php submit_button('Save Custom Fields'); ?>
        </form>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fieldContainer = document.getElementById('custom-field-settings');
            const addFieldButton = document.getElementById('add-custom-field-row');

            function updateFieldIndexes() {
                const rows = fieldContainer.querySelectorAll('.custom-field-row');
                rows.forEach((row, index) => {
                    row.dataset.index = index;
                    const inputs = row.querySelectorAll('input, select, textarea');
                    inputs.forEach(input => {
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
                const showChoices = ['select', 'checkbox', 'radio'].includes(typeSelect.value);
                choicesGroup.style.display = showChoices ? '' : 'none';
            }

             function toggleRepeaterCheckbox(row) {
                const typeSelect = row.querySelector('.field-type-select');
                const repeaterCheckbox = row.querySelector('.repeater-checkbox');
                 if (!typeSelect || !repeaterCheckbox) return;

                const disableRepeater = ['rich_text', 'select', 'checkbox', 'radio', 'true_false', 'url', 'email'].includes(typeSelect.value);
                repeaterCheckbox.disabled = disableRepeater;
                 repeaterCheckbox.title = disableRepeater ? 'This field type cannot be a repeater' : 'Allow multiple values';
                if (disableRepeater) {
                    repeaterCheckbox.checked = false;
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
                        <button type="button" class="remove-field">Remove</button>
                    </div>
                    <div class="field-group"><label>Group Name</label><br><input type="text" name="custom_fields[${newIndex}][group_name]" placeholder="Group Name" /></div>
                    <div class="field-group"><label>Field Name</label><br><input type="text" class="sanitize-key" name="custom_fields[${newIndex}][name]" placeholder="Field Name" /></div>
                    <div class="field-group">
                        <label>Field Type</label><br>
                        <select name="custom_fields[${newIndex}][type]" class="field-type-select" style="width:140px">
                            <option value="text">Text</option> <option value="number">Number</option> <option value="textarea">Textarea</option>
                            <option value="rich_text">Rich Text</option> <option value="media">Media</option> <option value="date">Date</option>
                            <option value="color">Color</option> <option value="select">Select</option> <option value="checkbox">Checkbox</option>
                            <option value="radio">Radio</option> <option value="true_false">True / False</option> <option value="url">URL</option> <option value="email">Email</option>
                        </select>
                    </div>
                    <div class="field-group field-group-choices" style="display: none;"><label>Choices <small>(one per line: <code>value : Label</code>)</small></label><br><textarea name="custom_fields[${newIndex}][choices]" rows="4" placeholder="e.g.,\nred : Red Color\ngreen : Green Color"></textarea></div>
                    <div class="field-group"><label>Post Types</label><br><select name="custom_fields[${newIndex}][post_type][]" multiple><?php foreach ($post_types as $post_type) : ?><option value="<?php echo esc_js($post_type->name); ?>"><?php echo esc_js($post_type->label); ?></option><?php endforeach; ?></select></div>
                    <div class="field-group"><label>Taxonomies</label><br><select name="custom_fields[${newIndex}][taxonomies][]" multiple><?php foreach ($taxonomies as $tax) : ?><option value="<?php echo esc_js($tax->name); ?>"><?php echo esc_js($tax->label); ?></option><?php endforeach; ?></select></div>
                    <div class="field-group"><label>Author</label><br><input type="checkbox" name="custom_fields[${newIndex}][author]" value="1" /></div>
                    <div class="field-group"><label>Repeater</label><br><input type="checkbox" class="repeater-checkbox" name="custom_fields[${newIndex}][repeater]" value="1" title="Allow multiple values" /></div>
                `;
                fieldContainer.appendChild(newRow);
                attachFieldNameSanitizer(newRow.querySelector('.sanitize-key'));
                toggleChoicesField(newRow);
                toggleRepeaterCheckbox(newRow);
                updateFieldIndexes();
            });


            fieldContainer.addEventListener('click', function(event) {
                if (event.target.classList.contains('remove-field')) {
                    event.target.closest('.custom-field-row').remove();
                    updateFieldIndexes();
                }
                if (event.target.classList.contains('move-up')) {
                    const row = event.target.closest('.custom-field-row');
                    const prevRow = row.previousElementSibling;
                    if (prevRow) {
                        fieldContainer.insertBefore(row, prevRow);
                        updateFieldIndexes();
                    }
                }
                if (event.target.classList.contains('move-down')) {
                     const row = event.target.closest('.custom-field-row');
                    const nextRow = row.nextElementSibling;
                    if (nextRow) {
                        fieldContainer.insertBefore(nextRow, row);
                        updateFieldIndexes();
                    }
                }
            });


            fieldContainer.addEventListener('change', function(event) {
                if (event.target.classList.contains('field-type-select')) {
                    const row = event.target.closest('.custom-field-row');
                    toggleChoicesField(row);
                    toggleRepeaterCheckbox(row);
                }
            });


            fieldContainer.querySelectorAll('.custom-field-row').forEach(row => {
                 toggleChoicesField(row);
                 toggleRepeaterCheckbox(row);
                 attachFieldNameSanitizer(row.querySelector('.sanitize-key'));
            });


            function sanitizeFieldNameKey(value) {
                 value = value.trim();
                 value = value.replace(/\s+/g, '_');
                 value = value.replace(/[^a-z0-9_]/g, '');
                return value.toLowerCase();
            }

            function attachFieldNameSanitizer(input) {
                if (!input) return;

                 input.addEventListener('keydown', function(e) {
                    if (e.key === ' ') {
                        e.preventDefault();
                        var start = input.selectionStart;
                        var end = input.selectionEnd;
                        var value = input.value;
                        input.value = value.substring(0, start) + '_' + value.substring(end);
                        input.setSelectionRange(start + 1, start + 1);
                     }
                 });

                input.addEventListener('input', function(e) {
                    var sanitized = sanitizeFieldNameKey(e.target.value);
                    if (e.target.value !== sanitized) {
                        var start = e.target.selectionStart;
                        var diff = e.target.value.length - sanitized.length;
                        e.target.value = sanitized;
                        e.target.setSelectionRange(start - diff, start - diff);
                    }
                });
                 input.addEventListener('blur', function(e) {
                    e.target.value = sanitizeFieldNameKey(e.target.value);
                 });
            }


            fieldContainer.querySelectorAll('.sanitize-key').forEach(function(input) {
                attachFieldNameSanitizer(input);
            });

        });
        </script>

        <style>
           .custom-field-row [type="text"]{
               width:240px;
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
           }
           .custom-field-row input,
           .custom-field-row select {
               font-size: 14px;
           }
           .custom-field-row .buttons button {
               margin-left: 5px;
           }

           .custom-field-row {
               gap: 15px;
               margin-bottom: 15px;
               padding: 15px;
               border: 1px solid #ddd;
               border-radius: 5px;
               background-color: #f9f9f9;
           }

           .custom-field-row .buttons {
               display: flex;
               flex-direction: row;
               gap: 5px;
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
               background: rgb(242, 242, 242);
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

           .buttons button{
               cursor:pointer;
               border:solid 1px gray;
               padding:4px 10px;
           }
           .buttons button:hover{
               background:white;
           }

           @media (max-width: 768px) {
               .custom-field-row {
                   flex-direction: column;
                   align-items: flex-start;
               }
               .custom-field-row .buttons {
                   flex-direction: row;
                   gap: 10px;
               }
               .custom-field-row label,
               .custom-field-row input[type="text"],
               .custom-field-row select {
                   width: 100%;
               }
           }
       </style>
    </div>
    <?php
}


function snn_register_dynamic_metaboxes() {
    $custom_fields = get_option('snn_custom_fields', []);
    $grouped_fields = [];
    global $snn_repeater_fields_exist, $snn_media_fields_exist, $snn_color_fields_exist;
    $snn_repeater_fields_exist = false;
    $snn_media_fields_exist = false;
    $snn_color_fields_exist = false;


    foreach ($custom_fields as $field) {
        $group_name = isset($field['group_name']) && trim($field['group_name']) !== '' ? $field['group_name'] : 'Custom Fields';


        if (!empty($field['post_type']) && is_array($field['post_type'])) {
            foreach ($field['post_type'] as $pt) {
                 if (!isset($grouped_fields[$pt])) $grouped_fields[$pt] = [];
                 if (!isset($grouped_fields[$pt][$group_name])) $grouped_fields[$pt][$group_name] = [];
                 $grouped_fields[$pt][$group_name][] = $field;

                 $is_repeater_allowed_type = !in_array($field['type'], ['rich_text', 'select', 'checkbox', 'radio', 'true_false', 'url', 'email']);

                 if (!empty($field['repeater']) && $is_repeater_allowed_type) $snn_repeater_fields_exist = true;
                 if ($field['type'] === 'media') $snn_media_fields_exist = true;
                 if ($field['type'] === 'color') $snn_color_fields_exist = true;
                 if ($field['type'] === 'date') wp_enqueue_script('jquery-ui-datepicker');
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


    if ($snn_media_fields_exist || $snn_repeater_fields_exist || $snn_color_fields_exist) {
         add_action('admin_enqueue_scripts', 'snn_enqueue_metabox_scripts');
    }
     if ($snn_repeater_fields_exist || $snn_media_fields_exist || $snn_color_fields_exist) {
        add_action('admin_footer', 'snn_output_dynamic_field_js');
     }
}
add_action('add_meta_boxes', 'snn_register_dynamic_metaboxes');


function snn_enqueue_metabox_scripts($hook_suffix) {
    global $pagenow, $snn_media_fields_exist, $snn_color_fields_exist;

    if (in_array($pagenow, ['post.php', 'post-new.php'])) {
         if ($snn_media_fields_exist) {
            wp_enqueue_media();
        }
         if ($snn_color_fields_exist) {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
        }
    }

     if ($hook_suffix === 'profile.php' || $hook_suffix === 'user-edit.php') {
         $custom_fields = get_option('snn_custom_fields', []);
         $has_author_media = false;
         $has_author_color = false;
         foreach ($custom_fields as $field) {
             if (!empty($field['author'])) {
                 if ($field['type'] === 'media') $has_author_media = true;
                 if ($field['type'] === 'color') $has_author_color = true;
             }
         }
         if ($has_author_media) wp_enqueue_media();
         if ($has_author_color) {
             wp_enqueue_style('wp-color-picker');
             wp_enqueue_script('wp-color-picker');
         }
         if ($has_author_media || $has_author_color) {
             add_action('admin_footer', 'snn_output_dynamic_field_js');
         }
     }

    if (in_array($pagenow, ['term.php', 'edit-tags.php'])) {
        $custom_fields = get_option('snn_custom_fields', []);
        $has_tax_media = false;
        $has_tax_color = false;
         foreach ($custom_fields as $field) {
             if (!empty($field['taxonomies'])) {
                 if ($field['type'] === 'media') $has_tax_media = true;
                 if ($field['type'] === 'color') $has_tax_color = true;
             }
         }
        if ($has_tax_media) wp_enqueue_media();
        if ($has_tax_color) {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
        }
         if ($has_tax_media || $has_tax_color) {
            add_action('admin_footer', 'snn_output_dynamic_field_js');
        }
    }
}


function snn_render_metabox_content($post, $metabox) {
    $fields = $metabox['args']['fields'];
    wp_nonce_field('snn_save_custom_fields', 'snn_custom_fields_nonce');

    echo '<div class="snn-metabox-wrapper">';

    foreach ($fields as $field) {
        $field_name  = $field['name'];
        $field_value = get_post_meta($post->ID, $field_name, true);
        $field_type = $field['type'];
        $is_repeater_allowed_type = !in_array($field_type, ['rich_text', 'select', 'checkbox', 'radio', 'true_false', 'url', 'email']);


        echo '<div class="snn-field-wrap snn-field-type-' . esc_attr($field['type']) . ' ' . (!empty($field['repeater']) && $is_repeater_allowed_type ? 'snn-is-repeater' : '') . '">';
        echo '<label class="snn-field-label" for="' . esc_attr($field_name) . '_0">' . esc_html(ucwords(str_replace('_', ' ', $field_name))) . '</label>';


        if (!empty($field['repeater']) && $is_repeater_allowed_type) {
            global $snn_repeater_fields_exist;
            $snn_repeater_fields_exist = true;


            $values = is_array($field_value) ? $field_value : (!empty($field_value) ? [$field_value] : ['']);
            if (empty($values)) $values = [''];

            echo '<div class="repeater-container" data-field-name="' . esc_attr($field_name) . '" data-field-type="' . esc_attr($field['type']) . '">';
            foreach ($values as $index => $value) {
                 echo '<div class="repeater-item">';
                 echo '<div class="repeater-content">';
                 snn_render_field_input($field, $value, $index);
                 echo '</div>';
                 echo '<button type="button" class="button button-small remove-repeater-item">Remove</button>';
                 echo '</div>';
            }
             echo '<button type="button" class="button add-repeater-item">Add More +</button>';
            echo '</div>';

        } else {

            snn_render_field_input($field, $field_value);
        }
        echo '</div>';
    }
     echo '</div>';


    ?>
    <style>
        .snn-metabox-wrapper { padding: 10px 0; }
        .snn-field-wrap { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px dotted #eee; }
        .snn-field-wrap:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .snn-field-label { display: block; font-weight: bold; margin-bottom: 8px; font-size: 14px; }
        .snn-field-wrap input[type="text"],
        .snn-field-wrap input[type="number"],
        .snn-field-wrap input[type="url"],
        .snn-field-wrap input[type="email"],
        .snn-field-wrap input[type="date"],
        .snn-field-wrap select,
        .snn-field-wrap textarea { width: 100%; max-width: 600px; padding: 8px; margin-bottom: 5px; }
        .snn-field-wrap textarea { min-height: 100px; }
        .snn-field-type-true_false input[type="checkbox"] { margin-top: 5px; width: auto; }


        .repeater-container .repeater-item { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 10px; padding: 10px; background: #f9f9f9; border: 1px solid #e5e5e5; border-radius: 3px; }
        .repeater-container .repeater-item .repeater-content { flex-grow: 1; }
        .repeater-container .remove-repeater-item { margin-top: 5px; align-self: center; }
        .repeater-container .add-repeater-item { margin-top: 5px; }


        .snn-field-type-media .media-uploader { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .snn-field-type-media .media-preview { max-width: 80px; max-height: 80px; display: inline-block; border: 1px solid #ddd; padding: 2px; background: #fff; vertical-align: middle; }
         .snn-field-type-media .media-preview .dashicons { font-size: 60px; width: 60px; height: 60px; vertical-align: middle; }
        .snn-field-type-media .media-uploader button { margin-top: 5px; vertical-align: middle; }


        .snn-field-type-checkbox .choice-item,
        .snn-field-type-radio .choice-item { display: block; margin-bottom: 5px; }
        .snn-field-type-checkbox .choice-item input,
        .snn-field-type-radio .choice-item input { margin-right: 5px; width: auto; vertical-align: middle; }
        .snn-field-type-checkbox .choice-item label,
        .snn-field-type-radio .choice-item label { display: inline-block; font-weight: normal; margin-bottom: 0; vertical-align: middle; }


        .wp-picker-container { display: inline-block; }
         .wp-picker-container .wp-color-result.button { margin: 0; vertical-align: middle; }
         .wp-picker-container .wp-picker-input-wrap input[type=text].wp-color-picker { width: 80px; margin-left: 5px; vertical-align: middle; }
    </style>
    <?php
}


function snn_render_field_input($field, $value = '', $index = '') {
    $field_name = $field['name'];
    $field_type = $field['type'];
    $is_repeater = ($index !== '');
    $input_id = esc_attr($field_name . '_' . ($index !== '' ? $index : '0'));


    if ($is_repeater) {
        $name_attribute = 'custom_fields[' . esc_attr($field_name) . '][' . esc_attr($index) . ']';
    } elseif ($field_type === 'checkbox') {
         $name_attribute = 'custom_fields[' . esc_attr($field_name) . '][]';
    } else {
        $name_attribute = 'custom_fields[' . esc_attr($field_name) . ']';
    }


    $choices = [];
    if (in_array($field_type, ['select', 'checkbox', 'radio']) && !empty($field['choices'])) {
        $lines = explode("\n", trim($field['choices']));
        foreach ($lines as $line) {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                 $val = trim($parts[0]);
                 $label = trim($parts[1]);
                 if ($val !== '') {
                     $choices[$val] = $label;
                 }
            }
        }
    }


    switch ($field_type) {
        case 'text':
            echo '<input type="text" id="' . $input_id . '" name="' . esc_attr($name_attribute) . '" value="' . esc_attr($value) . '" />';
            break;

        case 'number':
            echo '<input type="number" id="' . $input_id . '" name="' . esc_attr($name_attribute) . '" value="' . esc_attr($value) . '" step="any" />';
            break;

        case 'textarea':
            echo '<textarea id="' . $input_id . '" name="' . esc_attr($name_attribute) . '">' . esc_textarea($value) . '</textarea>';
            break;

        case 'rich_text':

             $editor_id = esc_attr(str_replace(['[', ']'], '_', $name_attribute));
            wp_editor(wp_kses_post($value), $editor_id, [
                'textarea_name' => $name_attribute,
                'media_buttons' => true,
                'textarea_rows' => 10,
                'tinymce'       => true,
                'quicktags'     => true
            ]);
            break;

        case 'media':
             $img_src = '';
             $preview_style = 'display: none;';
             $dashicon = '';
             if (!empty($value) && is_numeric($value)) {
                 $attachment_id = intval($value);
                 $attachment = get_post($attachment_id);
                 if ($attachment) {
                     $mime_type = get_post_mime_type($attachment);
                     if (strpos($mime_type, 'image/') === 0) {
                        $image = wp_get_attachment_image_src($attachment_id, 'thumbnail');
                        if ($image) {
                             $img_src = $image[0];
                             $preview_style = 'max-width: 100px; max-height: 100px;';
                        }
                     } else {
                         $dashicon = snn_get_dashicon_for_mime($mime_type);
                         $preview_style = 'font-size: 48px; width: auto; height: auto; display: inline-block;';
                     }
                 }
            }

             echo '<div class="media-uploader">';
             echo '<input type="hidden" id="' . $input_id . '" name="' . esc_attr($name_attribute) . '" value="' . esc_attr($value) . '" class="media-value-field" />';
             echo '<span class="media-preview-wrapper">';
            if ($dashicon) {
                echo '<span class="dashicons ' . esc_attr($dashicon) . ' media-preview" style="' . $preview_style . '"></span>';
            } else {
                echo '<img src="' . esc_url($img_src) . '" class="media-preview" style="' . $preview_style . '" />';
            }
             echo '</span> ';
             echo '<button type="button" class="button media-upload-button">Select/Upload Media</button>';
             echo '<button type="button" class="button media-remove-button" style="' . (empty($value) ? 'display:none;' : '') . '">Remove Media</button>';
             echo '</div>';
            break;

        case 'date':
             echo '<input type="date" class="snn-datepicker" id="' . $input_id . '" name="' . esc_attr($name_attribute) . '" value="' . esc_attr($value) . '" placeholder="YYYY-MM-DD" />';
            break;

        case 'color':
            echo '<input type="text" class="snn-color-picker" id="' . $input_id . '" name="' . esc_attr($name_attribute) . '" value="' . esc_attr($value) . '" data-default-color="#ffffff" />';
            break;

        case 'select':
            echo '<select id="' . $input_id . '" name="' . esc_attr($name_attribute) . '">';
            echo '<option value="">-- Select --</option>';
            if (!empty($choices)) {
                foreach ($choices as $val => $label) {
                     echo '<option value="' . esc_attr($val) . '" ' . selected($value, $val, false) . '>' . esc_html($label) . '</option>';
                }
            }
            echo '</select>';
            break;

        case 'checkbox':
             $checked_values = is_array($value) ? $value : (!empty($value) ? [$value] : []);
             echo '<div class="checkbox-group">';
             if (!empty($choices)) {
                 $choice_index = 0;
                 foreach ($choices as $val => $label) {
                     $choice_id = $input_id . '_' . $choice_index++;
                     echo '<span class="choice-item">';
                     echo '<input type="checkbox" id="' . $choice_id . '" name="' . esc_attr($name_attribute) . '" value="' . esc_attr($val) . '" ' . (in_array($val, $checked_values) ? 'checked' : '') . ' />';
                     echo '<label for="' . $choice_id . '">' . esc_html($label) . '</label>';
                     echo '</span>';
                 }
             } else {
                 echo '<em>No choices defined.</em>';
             }
            echo '</div>';
            break;

        case 'radio':
             echo '<div class="radio-group">';
             if (!empty($choices)) {
                $choice_index = 0;
                 foreach ($choices as $val => $label) {
                    $choice_id = $input_id . '_' . $choice_index++;
                    $radio_name_attr = $is_repeater ? 'custom_fields[' . esc_attr($field_name) . '][' . esc_attr($index) . ']' : 'custom_fields[' . esc_attr($field_name) . ']';
                     echo '<span class="choice-item">';
                     echo '<input type="radio" id="' . $choice_id . '" name="' . esc_attr($radio_name_attr) . '" value="' . esc_attr($val) . '" ' . checked($value, $val, false) . ' />';
                     echo '<label for="' . $choice_id . '">' . esc_html($label) . '</label>';
                     echo '</span>';
                 }
             } else {
                 echo '<em>No choices defined.</em>';
             }
            echo '</div>';
            break;

        case 'true_false':
            echo '<input type="hidden" name="' . esc_attr($name_attribute) . '" value="0" />';
            echo '<input type="checkbox" id="' . $input_id . '" name="' . esc_attr($name_attribute) . '" value="1" ' . checked($value, '1', false) . ' />';
            break;

        case 'url':
             echo '<input type="url" id="' . $input_id . '" name="' . esc_attr($name_attribute) . '" value="' . esc_attr($value) . '" placeholder="https://example.com" />';
             break;

        case 'email':
            echo '<input type="email" id="' . $input_id . '" name="' . esc_attr($name_attribute) . '" value="' . esc_attr($value) . '" placeholder="name@example.com" />';
            break;

        default:
            echo '<input type="text" id="' . $input_id . '" name="' . esc_attr($name_attribute) . '" value="' . esc_attr($value) . '" />';
            break;
    }
}


function snn_get_dashicon_for_mime($mime_type) {
    $mime_to_dashicon = [
        'application/pdf' => 'dashicons-media-document',
        'application/json' => 'dashicons-media-code',
        'application/zip' => 'dashicons-media-archive',
        'application/vnd.ms-excel' => 'dashicons-media-spreadsheet',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'dashicons-media-spreadsheet',
        'application/msword' => 'dashicons-media-document',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'dashicons-media-document',
        'text/plain' => 'dashicons-media-text',
        'text/csv' => 'dashicons-media-spreadsheet',
        'video' => 'dashicons-media-video',
        'audio' => 'dashicons-media-audio',
    ];


     if (strpos($mime_type, 'video/') === 0) return $mime_to_dashicon['video'];
     if (strpos($mime_type, 'audio/') === 0) return $mime_to_dashicon['audio'];


    return isset($mime_to_dashicon[$mime_type]) ? $mime_to_dashicon[$mime_type] : 'dashicons-media-default';
}



function snn_save_custom_fields_meta($post_id) {

    if (!isset($_POST['snn_custom_fields_nonce']) || !wp_verify_nonce($_POST['snn_custom_fields_nonce'], 'snn_save_custom_fields')) {
        return $post_id;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }
    if (isset($_POST['post_type']) && 'page' == $_POST['post_type']) {
        if (!current_user_can('edit_page', $post_id)) {
            return $post_id;
        }
    } else {
        if (!current_user_can('edit_post', $post_id)) {
            return $post_id;
        }
    }

     if (wp_is_post_revision($post_id)) {
        return $post_id;
     }


    $custom_fields = get_option('snn_custom_fields', []);
     $posted_data = isset($_POST['custom_fields']) ? $_POST['custom_fields'] : [];

    foreach ($custom_fields as $field) {
        $field_name = $field['name'];


         $post_type = get_post_type($post_id);
         if (empty($field['post_type']) || !in_array($post_type, $field['post_type'])) {
             continue;
         }

        if (isset($posted_data[$field_name])) {
            $value = $posted_data[$field_name];


            if (is_array($value)) {

                 $sanitized_values = [];
                 foreach ($value as $item_value) {

                    if ( ($item_value !== '' && !is_null($item_value)) || $field['type'] === 'true_false') {
                        $sanitized_values[] = snn_sanitize_value_by_type($field['type'], $item_value);
                    }
                 }

                $non_empty_values = array_filter($sanitized_values, function($v) { return $v !== '' && !is_null($v) && $v !== '0'; });


                 if (!empty($non_empty_values) || ($field['type'] === 'true_false' && isset($sanitized_values[0])) ) {
                    update_post_meta($post_id, $field_name, $sanitized_values);
                 } else {

                    delete_post_meta($post_id, $field_name);
                 }
            } else {

                $sanitized_value = snn_sanitize_value_by_type($field['type'], $value);

                if (($sanitized_value !== '' && !is_null($sanitized_value)) || $field['type'] === 'true_false') {
                     update_post_meta($post_id, $field_name, $sanitized_value);
                } else {
                     delete_post_meta($post_id, $field_name);
                }
            }
        } else {

            if ($field['type'] === 'true_false') {
                 update_post_meta($post_id, $field_name, '0');
             } elseif ($field['type'] === 'checkbox') {
                 $field_key_exists = array_key_exists($field_name, $posted_data);
                 if ($field_key_exists && empty($posted_data[$field_name])) {
                     delete_post_meta($post_id, $field_name);
                 }

             } else {
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

                    add_action($tax . '_add_form_fields', function($taxonomy) use ($field) {
                        ?>
                        <div class="form-field term-<?php echo esc_attr($field['name']); ?>-wrap snn-tax-field snn-field-wrap snn-field-type-<?php echo esc_attr($field['type']); ?>">
                            <label for="snn_term_<?php echo esc_attr($field['name']); ?>"><?php echo esc_html(ucwords(str_replace('_', ' ', $field['name']))); ?></label>
                            <?php

                            snn_render_field_input($field, '', '');
                            ?>
                        </div>
                        <?php
                    }, 10, 1);


                    add_action($tax . '_edit_form_fields', function($term) use ($field) {
                         $value = get_term_meta($term->term_id, $field['name'], true);
                        ?>
                        <tr class="form-field term-<?php echo esc_attr($field['name']); ?>-wrap snn-tax-field snn-field-wrap snn-field-type-<?php echo esc_attr($field['type']); ?>">
                            <th scope="row">
                                <label for="snn_term_<?php echo esc_attr($field['name']); ?>">
                                    <?php echo esc_html(ucwords(str_replace('_', ' ', $field['name']))); ?>
                                </label>
                            </th>
                            <td>
                                <?php
                                snn_render_field_input($field, $value, '');
                                ?>
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
     ?>
    <style>
        .snn-tax-field { margin-bottom: 15px; }
        .snn-tax-field label { display: block; font-weight: bold; margin-bottom: 5px; }
        .snn-tax-field input[type="text"],
        .snn-tax-field input[type="number"],
        .snn-tax-field input[type="url"],
        .snn-tax-field input[type="email"],
        .snn-tax-field input[type="date"],
        .snn-tax-field select,
        .snn-tax-field textarea { width: 95%; max-width: 400px; padding: 6px; }
         .snn-tax-field textarea { min-height: 80px; }
         .snn-tax-field input[type="checkbox"] { width: auto; }
        .snn-field-type-media .media-uploader { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .snn-field-type-media .media-preview { max-width: 60px; max-height: 60px; vertical-align: middle; }
         .snn-field-type-media .media-preview .dashicons { font-size: 40px; width: 40px; height: 40px; vertical-align: middle; }
         .snn-field-type-media .media-uploader button { vertical-align: middle; }
         .snn-field-type-checkbox .choice-item,
         .snn-field-type-radio .choice-item { display: inline-block; margin-right: 15px; margin-bottom: 3px; }
         .snn-field-type-checkbox .choice-item input,
         .snn-field-type-radio .choice-item input { margin-right: 4px; vertical-align: middle; }
         .snn-field-type-checkbox .choice-item label,
         .snn-field-type-radio .choice-item label { display: inline; font-weight: normal; vertical-align: middle; }

         .edit-tags-php .form-wrap .form-field input[type="text"],
         .edit-tags-php .form-wrap .form-field select,
         .edit-tags-php .form-wrap .form-field textarea { width: 100%; max-width: none; }
    </style>
    <?php
}


function snn_save_taxonomy_field_data($term_id) {

     if (!current_user_can('manage_categories')) {
        return;
    }

    $custom_fields = get_option('snn_custom_fields', []);
     $posted_data = isset($_POST['custom_fields']) ? $_POST['custom_fields'] : [];


     $term = get_term($term_id);
     if (!$term || is_wp_error($term)) return;
     $taxonomy = $term->taxonomy;

    foreach ($custom_fields as $field) {

         if (!empty($field['repeater'])) continue;


         if (empty($field['taxonomies']) || !in_array($taxonomy, $field['taxonomies'])) {
             continue;
         }

         $field_name = $field['name'];

         if (isset($posted_data[$field_name])) {
             $value = $posted_data[$field_name];


            if (is_array($value)) {
                 $sanitized_values = [];
                 foreach ($value as $item_value) {
                     if ($item_value !== '' && !is_null($item_value)) {
                        $sanitized_values[] = snn_sanitize_value_by_type($field['type'], $item_value);
                    }
                 }
                 if (!empty($sanitized_values)) {
                     update_term_meta($term_id, $field_name, $sanitized_values);
                 } else {
                     delete_term_meta($term_id, $field_name);
                 }
            } else {

                 $sanitized_value = snn_sanitize_value_by_type($field['type'], $value);
                 if (($sanitized_value !== '' && !is_null($sanitized_value)) || $field['type'] === 'true_false') {
                     update_term_meta($term_id, $field_name, $sanitized_value);
                 } else {
                     delete_term_meta($term_id, $field_name);
                 }
            }
        } else {

             if ($field['type'] === 'true_false') {

                  update_term_meta($term_id, $field_name, '0');
             } elseif ($field['type'] === 'checkbox') {

                  $field_key_exists = array_key_exists($field_name, $posted_data);
                  if ($field_key_exists && empty($posted_data[$field_name])) {
                      delete_term_meta($term_id, $field_name);
                  }
             } else {

                delete_term_meta($term_id, $field_name);
            }
        }
    }
}


function snn_add_author_profile_fields() {
    $custom_fields = get_option('snn_custom_fields', []);
    $author_fields = [];
     global $snn_media_fields_exist, $snn_color_fields_exist;

    foreach ($custom_fields as $field) {
        if (!empty($field['author']) && empty($field['repeater'])) {
             $author_fields[] = $field;

             if ($field['type'] === 'media') $snn_media_fields_exist = true;
             if ($field['type'] === 'color') $snn_color_fields_exist = true;
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
    foreach ($custom_fields as $field) {
        if (!empty($field['author']) && empty($field['repeater'])) {
            $author_fields[] = $field;
        }
    }

    if (empty($author_fields)) return;

    ?>
    <h2>Custom Author Information</h2>
    <?php wp_nonce_field('snn_save_author_fields', 'snn_author_fields_nonce'); ?>
    <table class="form-table snn-author-fields">
        <?php
        foreach ($author_fields as $field) {
            $field_name = $field['name'];
            $value = get_user_meta($user->ID, $field_name, true);
            ?>
            <tr class="snn-field-wrap snn-field-type-<?php echo esc_attr($field['type']); ?>">
                <th>
                    <label for="snn_user_<?php echo esc_attr($field_name); ?>">
                        <?php echo esc_html(ucwords(str_replace('_', ' ', $field_name))); ?>
                    </label>
                </th>
                <td>
                    <?php snn_render_field_input($field, $value, ''); ?>
                </td>
            </tr>
            <?php
        }
        ?>
    </table>
     <style>
         .snn-author-fields .snn-field-wrap td input[type="text"],
         .snn-author-fields .snn-field-wrap td input[type="number"],
         .snn-author-fields .snn-field-wrap td input[type="url"],
         .snn-author-fields .snn-field-wrap td input[type="email"],
         .snn-author-fields .snn-field-wrap td input[type="date"],
         .snn-author-fields .snn-field-wrap td select,
         .snn-author-fields .snn-field-wrap td textarea { width: 95%; max-width: 400px; padding: 6px; }
         .snn-author-fields .snn-field-wrap td textarea { min-height: 80px; }
         .snn-author-fields .snn-field-wrap td input[type="checkbox"] { width: auto; }
         .snn-author-fields .snn-field-type-media .media-uploader { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
         .snn-author-fields .snn-field-type-media .media-preview { max-width: 60px; max-height: 60px; vertical-align: middle;}
         .snn-author-fields .snn-field-type-media .media-preview .dashicons { font-size: 40px; width: 40px; height: 40px; vertical-align: middle;}
          .snn-author-fields .snn-field-type-media .media-uploader button { vertical-align: middle;}
         .snn-author-fields .snn-field-type-checkbox .choice-item,
         .snn-author-fields .snn-field-type-radio .choice-item { display: inline-block; margin-right: 15px; margin-bottom: 3px; }
         .snn-author-fields .snn-field-type-checkbox .choice-item input,
         .snn-author-fields .snn-field-type-radio .choice-item input { margin-right: 4px; vertical-align: middle; }
         .snn-author-fields .snn-field-type-checkbox .choice-item label,
         .snn-author-fields .snn-field-type-radio .choice-item label { display: inline; font-weight: normal; vertical-align: middle; }
     </style>
    <?php
}

function snn_save_author_custom_fields($user_id) {

    if (!isset($_POST['snn_author_fields_nonce']) || !wp_verify_nonce($_POST['snn_author_fields_nonce'], 'snn_save_author_fields')) {
        return;
    }

    if (!current_user_can('edit_user', $user_id)) {
        return;
    }

    $custom_fields = get_option('snn_custom_fields', []);
    $posted_data = isset($_POST['custom_fields']) ? $_POST['custom_fields'] : [];

    foreach ($custom_fields as $field) {

        if (empty($field['author']) || !empty($field['repeater'])) {
            continue;
        }

        $field_name = $field['name'];

         if (isset($posted_data[$field_name])) {
             $value = $posted_data[$field_name];


             if (is_array($value)) {
                 $sanitized_values = [];
                 foreach ($value as $item_value) {
                    if ($item_value !== '' && !is_null($item_value)) {
                        $sanitized_values[] = snn_sanitize_value_by_type($field['type'], $item_value);
                    }
                 }
                 if (!empty($sanitized_values)) {
                     update_user_meta($user_id, $field_name, $sanitized_values);
                 } else {
                     delete_user_meta($user_id, $field_name);
                 }
             } else {

                 $sanitized_value = snn_sanitize_value_by_type($field['type'], $value);
                 if (($sanitized_value !== '' && !is_null($sanitized_value)) || $field['type'] === 'true_false') {
                     update_user_meta($user_id, $field_name, $sanitized_value);
                 } else {
                     delete_user_meta($user_id, $field_name);
                 }
             }
         } else {

             if ($field['type'] === 'true_false') {
                 update_user_meta($user_id, $field_name, '0');
             } elseif ($field['type'] === 'checkbox') {
                 $field_key_exists = array_key_exists($field_name, $posted_data);
                 if ($field_key_exists && empty($posted_data[$field_name])) {
                     delete_user_meta($user_id, $field_name);
                 }
             } else {
                 delete_user_meta($user_id, $field_name);
             }
         }
    }
}



function snn_sanitize_value_by_type($type, $value) {
    switch ($type) {
        case 'rich_text':
            return wp_kses_post($value);
        case 'textarea':
             return sanitize_textarea_field($value);
        case 'media':
            return $value ? intval($value) : '';
        case 'number':
            return is_numeric($value) ? floatval($value) : '';
        case 'date':
        case 'color':
        case 'select':
        case 'radio':
             return sanitize_text_field($value);
        case 'checkbox':
            return sanitize_text_field($value);
        case 'true_false':
            return ($value == '1' || $value === true) ? '1' : '0';
        case 'url':
            return esc_url_raw($value);
        case 'email':
            return sanitize_email($value);
        case 'text':
        default:
            return sanitize_text_field($value);
    }
}



function snn_output_dynamic_field_js() {
    global $snn_repeater_fields_exist, $snn_media_fields_exist, $snn_color_fields_exist;


     if (!$snn_repeater_fields_exist && !$snn_media_fields_exist && !$snn_color_fields_exist) {
        return;
     }


    ob_start();
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {

        <?php if ($snn_color_fields_exist): ?>
        function initColorPickers(context) {
            $('.snn-color-picker', context).each(function() {
                 if (!$(this).closest('.wp-picker-container').length) {
                    $(this).wpColorPicker();
                 }
            });
        }
         initColorPickers(document);
        <?php endif; ?>


        <?php if ($snn_media_fields_exist): ?>
        
            var mediaUploader;

            function setupMediaUploader(context) {
                $(context).on('click', '.media-upload-button', function(e) {
                    e.preventDefault();
                    var $button = $(this);
                    var $uploaderContainer = $button.closest('.media-uploader');
                    var $previewWrapper = $uploaderContainer.find('.media-preview-wrapper');
                    var $removeButton = $uploaderContainer.find('.media-remove-button');
                    var isRepeater = $button.closest('.repeater-container').length > 0;

                    mediaUploader = wp.media({
                        title: 'Choose Media',
                        button: { text: 'Insert Media' },
                        multiple: isRepeater // allows multiple selection only if repeater
                    });

                    mediaUploader.on('select', function() {
                        var attachments = mediaUploader.state().get('selection').toArray();

                        if (isRepeater) {
                            var $repeaterContainer = $button.closest('.repeater-container');
                            var $templateItem = $repeaterContainer.find('.repeater-item:first');
                            attachments.forEach(function(attachment, index) {
                                var attachmentJSON = attachment.toJSON();
                                var $item;

                                if (index === 0 && !$templateItem.find('.media-value-field').val()) {
                                    $item = $templateItem;
                                } else {
                                    $item = $templateItem.clone();
                                    $item.insertAfter($repeaterContainer.find('.repeater-item:last'));
                                }

                                var $valueField = $item.find('.media-value-field');
                                var $itemPreviewWrapper = $item.find('.media-preview-wrapper');
                                var $itemRemoveButton = $item.find('.media-remove-button');

                                $valueField.val(attachmentJSON.id);

                                var previewHtml = '';
                                if (attachmentJSON.type === 'image') {
                                    var thumbnailUrl = attachmentJSON.sizes && attachmentJSON.sizes.thumbnail ? attachmentJSON.sizes.thumbnail.url : attachmentJSON.url;
                                    previewHtml = '<img src="' + thumbnailUrl + '" class="media-preview" style="max-width:100px;max-height:100px;" />';
                                } else {
                                    var icon = attachmentJSON.icon || 'dashicons-media-default';
                                    previewHtml = '<span class="dashicons ' + icon + ' media-preview" style="font-size:48px;width:auto;height:auto;"></span>';
                                }

                                $itemPreviewWrapper.html(previewHtml);
                                $itemRemoveButton.show();
                            });

                            setupRepeaters($repeaterContainer); // Reinitialize repeaters
                        } else {
                            var attachment = attachments[0].toJSON();
                            var $valueField = $uploaderContainer.find('.media-value-field');

                            $valueField.val(attachment.id);

                            var previewHtml = '';
                            if (attachment.type === 'image') {
                                var thumbnailUrl = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
                                previewHtml = '<img src="' + thumbnailUrl + '" class="media-preview" style="max-width:100px;max-height:100px;" />';
                            } else {
                                var icon = attachment.icon || 'dashicons-media-default';
                                previewHtml = '<span class="dashicons ' + icon + ' media-preview" style="font-size:48px;width:auto;height:auto;"></span>';
                            }

                            $previewWrapper.html(previewHtml);
                            $removeButton.show();
                        }
                    });

                    mediaUploader.open();
                });

                $(context).on('click', '.media-remove-button', function(e) {
                    e.preventDefault();
                    var $button = $(this);
                    var $uploaderContainer = $button.closest('.media-uploader');
                    var $valueField = $uploaderContainer.find('.media-value-field');
                    var $previewWrapper = $uploaderContainer.find('.media-preview-wrapper');

                    $valueField.val('');
                    $previewWrapper.html('');
                    $button.hide();
                });
            }

         setupMediaUploader(document);
        <?php endif; ?>


        <?php if ($snn_repeater_fields_exist): ?>
        function setupRepeaters(context) {

             $('.add-repeater-item', context).off('click').on('click', function(e) {
                e.preventDefault();
                var $button = $(this);
                var $container = $button.closest('.repeater-container');
                var $lastItem = $container.find('.repeater-item:last');
                var $newItem = $lastItem.clone();


                 $newItem.find('input[type="text"], input[type="number"], input[type="url"], input[type="email"], input[type="date"], textarea, select').val('');
                 $newItem.find('input[type="color"]').val('#ffffff').trigger('change');
                 $newItem.find('input[type="checkbox"], input[type="radio"]').prop('checked', false);
                 $newItem.find('input.media-value-field').val('');
                 $newItem.find('.media-preview').attr('src', '').hide();
                 $newItem.find('.media-preview.dashicons').removeClass().addClass('dashicons media-preview').hide();
                 $newItem.find('.media-remove-button').hide();


                 var newIndex = $container.find('.repeater-item').length;
                 $newItem.find('input, select, textarea').each(function() {
                     var name = $(this).attr('name');
                     if (name) {

                         var newName = name.replace(/\[\d+\]$/, '[' + newIndex + ']');
                         $(this).attr('name', newName);


                          var id = $(this).attr('id');
                          if (id) {
                              var newId = id.replace(/_\d+$/, '_' + newIndex);
                              $(this).attr('id', newId);

                               $newItem.find('label[for="' + id + '"]').attr('for', newId);
                          }
                     }
                 });


                 $newItem.insertAfter($lastItem);


                  if ($newItem.find('.snn-color-picker').length > 0) {
                     $newItem.find('.wp-picker-container').remove();
                     $newItem.find('.snn-color-picker').show().wpColorPicker();
                 }


             });


             $(context).on('click', '.remove-repeater-item', function(e) {
                 e.preventDefault();
                 var $button = $(this);
                 var $item = $button.closest('.repeater-item');
                 var $container = $item.closest('.repeater-container');


                 $item.remove();



                 $container.find('.repeater-item').each(function(index) {
                     var $currentItem = $(this);
                     $currentItem.find('input, select, textarea').each(function() {
                         var name = $(this).attr('name');
                         if (name) {
                             var newName = name.replace(/\[\d+\]/, '[' + index + ']');
                             $(this).attr('name', newName);

                             var id = $(this).attr('id');
                             if (id) {
                                 var newId = id.replace(/_\d+/, '_' + index);
                                 $(this).attr('id', newId);

                                 $currentItem.find('label[for^="' + id.replace(/_\d+$/, '') + '"]').attr('for', newId);
                             }
                         }
                     });
                 });
             });
         }
         setupRepeaters(document);

        <?php endif; ?>


    });
    </script>
    <?php
    echo ob_get_clean();
}


?>