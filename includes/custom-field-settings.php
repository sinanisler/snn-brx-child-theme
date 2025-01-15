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


add_filter(
    'wp_default_editor',
    function () {
        return 'html';
    }
);

function snn_custom_fields_page_callback() {
    $custom_fields = get_option('snn_custom_fields', []);
    $post_types    = get_post_types(['public' => true], 'objects'); 
    $taxonomies    = get_taxonomies(['public' => true], 'objects');

    if (isset($_POST['snn_custom_fields_nonce']) && wp_verify_nonce($_POST['snn_custom_fields_nonce'], 'snn_custom_fields_save')) {
        $new_fields = [];
        if (!empty($_POST['custom_fields']) && is_array($_POST['custom_fields'])) {
            foreach ($_POST['custom_fields'] as $field) {
                $has_post_type = !empty($field['post_type']) && is_array($field['post_type']);
                $has_taxonomies = !empty($field['taxonomies']) && is_array($field['taxonomies']);

                if (!empty($field['name']) && !empty($field['type']) && !empty($field['group_name']) && ($has_post_type || $has_taxonomies)) {
                    $post_types_selected = $has_post_type ? array_map('sanitize_text_field', $field['post_type']) : [];
                    $taxonomies_selected = $has_taxonomies ? array_map('sanitize_text_field', $field['taxonomies']) : [];

                    $new_fields[] = [
                        'group_name' => sanitize_text_field($field['group_name']),
                        'name'       => sanitize_text_field($field['name']),
                        'type'       => sanitize_text_field($field['type']),
                        'post_type'  => $post_types_selected, 
                        'taxonomies' => $taxonomies_selected, 
                        'repeater'   => !empty($field['repeater']) ? 1 : 0,
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
                <p>Define custom fields with group name, field name, field type, and post type or taxonomy:<br>
                    Select one or more to register same Custom Field to Post Types or Taxonomies.<br>
                    Press CTRL/CMD to select multiple or to remove selection.

                </p>
                <?php
                if (!empty($custom_fields) && is_array($custom_fields)) {
                    foreach ($custom_fields as $index => $field) {
                        ?>
                        <div class="custom-field-row" data-index="<?php echo $index; ?>">

                            <div class="buttons">
                                <button type="button" class="move-up">▲</button>
                                <button type="button" class="move-down">▼</button>
                                <button type="button" class="remove-field">Remove</button>
                            </div>

                            <label>Group Name</label>
                            <input type="text" name="custom_fields[<?php echo $index; ?>][group_name]" placeholder="Group Name" value="<?php echo isset($field['group_name']) ? esc_attr($field['group_name']) : ''; ?>" />
                            
                            <label>Field Name</label>
                            <input type="text" name="custom_fields[<?php echo $index; ?>][name]" placeholder="Field Name" value="<?php echo esc_attr($field['name']); ?>" />
                            
                            <label>Field Type</label>
                            <select name="custom_fields[<?php echo $index; ?>][type]" class="field-type-select" style="width:140px">
                                <option value="text"      <?php selected($field['type'], 'text'); ?>>Text</option>
                                <option value="number"    <?php selected($field['type'], 'number'); ?>>Number</option>
                                <option value="textarea"  <?php selected($field['type'], 'textarea'); ?>>Textarea</option>
                                <option value="rich_text" <?php selected($field['type'], 'rich_text'); ?>>Rich Text</option>
                                <option value="media"     <?php selected($field['type'], 'media'); ?>>Media</option>
                                <option value="date"      <?php selected($field['type'], 'date'); ?>>Date</option>
                                <option value="color"     <?php selected($field['type'], 'color'); ?>>Color</option>
                            </select>
                            
                            <label>Post Types</label>
                            <select name="custom_fields[<?php echo $index; ?>][post_type][]" multiple>
                                <?php foreach ($post_types as $post_type) : ?>
                                    <option value="<?php echo esc_attr($post_type->name); ?>" <?php echo (!empty($field['post_type']) && in_array($post_type->name, $field['post_type'])) ? 'selected' : ''; ?>>
                                        <?php echo esc_html($post_type->label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <label>Taxonomies</label>
                            <select name="custom_fields[<?php echo $index; ?>][taxonomies][]" multiple>
                                <?php foreach ($taxonomies as $tax) : ?>
                                    <option value="<?php echo esc_attr($tax->name); ?>" <?php echo (!empty($field['taxonomies']) && in_array($tax->name, $field['taxonomies'])) ? 'selected' : ''; ?>>
                                        <?php echo esc_html($tax->label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <label>Repeater</label>
                            <input type="checkbox" name="custom_fields[<?php echo $index; ?>][repeater]" <?php checked(!empty($field['repeater'])); ?> <?php echo $field['type'] === 'rich_text' ? 'disabled' : ''; ?> />
                            
                        </div>
                        <?php
                    }
                } else {
                    ?>
                    <div class="custom-field-row" data-index="0">

                        <div class="buttons">
                            <button type="button" class="move-up">▲</button>
                            <button type="button" class="move-down">▼</button>
                            <button type="button" class="remove-field">Remove</button>
                        </div>

                        <label>Group Name</label>
                        <input type="text" name="custom_fields[0][group_name]" placeholder="Group Name" />
                        
                        <label>Field Name</label>
                        <input type="text" name="custom_fields[0][name]" placeholder="Field Name" />
                        
                        <label>Field Type</label>
                        <select name="custom_fields[0][type]" class="field-type-select">
                            <option value="text">Text</option>
                            <option value="number">Number</option>
                            <option value="textarea">Textarea</option>
                            <option value="rich_text">Rich Text</option>
                            <option value="media">Media</option>
                            <option value="date">Date</option>
                            <option value="color">Color</option>
                        </select>
                        
                        <label>Post Types</label>
                        <select name="custom_fields[0][post_type][]" multiple>
                            <?php foreach ($post_types as $post_type) : ?>
                                <option value="<?php echo esc_attr($post_type->name); ?>">
                                    <?php echo esc_html($post_type->label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label>Taxonomies</label>
                        <select name="custom_fields[0][taxonomies][]" multiple>
                            <?php foreach ($taxonomies as $tax) : ?>
                                <option value="<?php echo esc_attr($tax->name); ?>">
                                    <?php echo esc_html($tax->label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <label>Repeater</label>
                        <input type="checkbox" name="custom_fields[0][repeater]" disabled />
                        
                    </div>
                    <?php
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
                    const inputs = row.querySelectorAll('input, select');
                    inputs.forEach(input => {
                        const name = input.name;
                        input.name = name.replace(/\[\d+\]/, '[' + index + ']');
                    });
                });
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

                    <label>Group Name</label>
                    <input type="text" name="custom_fields[${newIndex}][group_name]" placeholder="Group Name" />

                    <label>Field Name</label>
                    <input type="text" name="custom_fields[${newIndex}][name]" placeholder="Field Name" />

                    <label>Field Type</label>
                    <select name="custom_fields[${newIndex}][type]" class="field-type-select">
                        <option value="text">Text</option>
                        <option value="number">Number</option>
                        <option value="textarea">Textarea</option>
                        <option value="rich_text">Rich Text</option>
                        <option value="media">Media</option>
                        <option value="date">Date</option>
                        <option value="color">Color</option>
                    </select>

                    <label>Post Types</label>
                    <select name="custom_fields[${newIndex}][post_type][]" multiple>
                        <?php foreach ($post_types as $post_type) : ?>
                            <option value="<?php echo esc_js($post_type->name); ?>"><?php echo esc_js($post_type->label); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label>Taxonomies</label>
                    <select name="custom_fields[${newIndex}][taxonomies][]" multiple>
                        <?php foreach ($taxonomies as $tax) : ?>
                            <option value="<?php echo esc_js($tax->name); ?>"><?php echo esc_js($tax->label); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label>Repeater</label>
                    <input type="checkbox" name="custom_fields[${newIndex}][repeater]" disabled />
                `;
                fieldContainer.appendChild(newRow);
            });

            fieldContainer.addEventListener('click', function(event) {
                if (event.target.classList.contains('remove-field')) {
                    event.target.closest('.custom-field-row').remove();
                    updateFieldIndexes();
                }
            });

            fieldContainer.addEventListener('click', function(event) {
                if (event.target.classList.contains('move-up')) {
                    const row = event.target.closest('.custom-field-row');
                    const prevRow = row.previousElementSibling;
                    if (prevRow) {
                        fieldContainer.insertBefore(row, prevRow);
                        updateFieldIndexes();
                    }
                }
            });

            fieldContainer.addEventListener('click', function(event) {
                if (event.target.classList.contains('move-down')) {
                    const row = event.target.closest('.custom-field-row');
                    const nextRow = row.nextElementSibling;
                    if (nextRow) {
                        fieldContainer.insertBefore(nextRow, row);
                        updateFieldIndexes();
                    }
                }
            });

            document.addEventListener('change', function(event) {
                if (event.target.classList.contains('field-type-select')) {
                    const row = event.target.closest('.custom-field-row');
                    const repeaterCheckbox = row.querySelector('input[type="checkbox"][name*="[repeater]"]');
                    
                    if (event.target.value === 'rich_text') {
                        repeaterCheckbox.disabled = true;
                        repeaterCheckbox.checked = false;
                    } else {
                        repeaterCheckbox.disabled = false;
                    }
                }
            });
        });
        </script>

        <style>
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
                flex-direction: column;
                gap: 5px;
                flex-direction:row;
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
    $custom_fields    = get_option('snn_custom_fields', []);
    $grouped_fields   = [];
    global $snn_repeater_fields_exist;
    $snn_repeater_fields_exist = false;
    global $snn_media_fields_exist;
    $snn_media_fields_exist = false;

    foreach ($custom_fields as $field) {
        $group_name = isset($field['group_name']) ? $field['group_name'] : 'default';

        if (!empty($field['post_type']) && is_array($field['post_type'])) {
            foreach ($field['post_type'] as $pt) {
                if (!isset($grouped_fields[$pt])) {
                    $grouped_fields[$pt] = [];
                }
                if (!isset($grouped_fields[$pt][$group_name])) {
                    $grouped_fields[$pt][$group_name] = [];
                }
                $grouped_fields[$pt][$group_name][] = $field;

                if ($field['type'] === 'media') {
                    $snn_media_fields_exist = true;
                }
            }
        }
    }

    foreach ($grouped_fields as $post_type => $groups) {
        foreach ($groups as $group_name => $fields) {
            add_meta_box(
                'custom_field_group_' . sanitize_title($group_name),
                $group_name,
                function($post) use ($fields) {
                    global $snn_repeater_fields_exist;
                    wp_nonce_field('snn_save_custom_fields', 'snn_custom_fields_nonce');
                    foreach ($fields as $field) {
                        $field_name  = $field['name'];
                        $field_value = get_post_meta($post->ID, $field_name, true);

                        echo '<div class="custom-field snn-custom-field">';
                        echo '<label>' . esc_html($field_name) . '</label>';

                        if (!empty($field['repeater'])) {
                            $snn_repeater_fields_exist = true;
                            $values = is_array($field_value) ? $field_value : [''];
                            echo '<div class="repeater-container" data-field-name="' . esc_attr($field_name) . '" data-field-type="' . esc_attr($field['type']) . '">';
                            foreach ($values as $index => $value) {
                                echo '<div class="repeater-item">';
                                snn_render_field_input($field, $value, $index);
                                echo '<button type="button" class="remove-repeater-item">Remove</button>';
                                echo '</div>';
                            }
                            echo '<button type="button" class="button add-repeater-item">Add More +</button>';
                            echo '</div>';
                        } else {
                            snn_render_field_input($field, $field_value);
                        }
                        echo '</div>';
                        ?>

                        <style>
                            .snn-custom-field {
                                display: grid;
                                grid-template-columns: 1fr;
                                gap: 5px;
                                margin-bottom: 20px;
                            }
                            .snn-custom-field textarea {
                                height: 100px;
                            }
                        </style>

                        <?php
                    }
                },
                $post_type
            );
        }
    }

    add_action('admin_footer', 'snn_output_repeater_field_js');
}
add_action('add_meta_boxes', 'snn_register_dynamic_metaboxes');

function snn_register_dynamic_taxonomy_fields() {
    $custom_fields = get_option('snn_custom_fields', []);

    if (!empty($custom_fields)) {
        foreach ($custom_fields as $field) {
            if (!empty($field['taxonomies']) && is_array($field['taxonomies'])) {
                foreach ($field['taxonomies'] as $tax) {
                    add_action($tax . '_add_form_fields', function($taxonomy) use ($field) {
                        ?>
                        <div class="form-field snn-tax-field">
                            <label for="<?php echo esc_attr($field['name']); ?>"><?php echo esc_html($field['name']); ?></label>
                            <?php
                            snn_render_field_input($field, '');
                            ?>
                        </div>
                        <?php
                    });

                    add_action($tax . '_edit_form_fields', function($term) use ($field) {
                        $value = get_term_meta($term->term_id, $field['name'], true);
                        ?>
                        <tr class="form-field snn-tax-field">
                            <th scope="row">
                                <label for="<?php echo esc_attr($field['name']); ?>">
                                    <?php echo esc_html($field['name']); ?>
                                </label>
                            </th>
                            <td>
                                <?php
                                snn_render_field_input($field, $value);
                                ?>
                            </td>
                        </tr>
                        <?php
                    });

                    add_action('created_' . $tax, function($term_id) use ($field) {
                        snn_save_taxonomy_field_data($term_id, $field);
                    });

                    add_action('edited_' . $tax, function($term_id) use ($field) {
                        snn_save_taxonomy_field_data($term_id, $field);
                    });
                }
            }
        }
    }
}
add_action('admin_init', 'snn_register_dynamic_taxonomy_fields');

function snn_save_taxonomy_field_data($term_id, $field) {
    if (!current_user_can('manage_categories')) {
        return;
    }

    if (!empty($field['repeater'])) {
        if (isset($_POST['custom_fields'][$field['name']]) && is_array($_POST['custom_fields'][$field['name']])) {
            $sanitized = array_map(function($value) use ($field) {
                return snn_sanitize_value_by_type($field['type'], $value);
            }, $_POST['custom_fields'][$field['name']]);
            update_term_meta($term_id, $field['name'], $sanitized);
        } else {
            delete_term_meta($term_id, $field['name']);
        }
    }
    else {
        if (isset($_POST['custom_fields'][$field['name']])) {
            $value = snn_sanitize_value_by_type($field['type'], $_POST['custom_fields'][$field['name']]);
            update_term_meta($term_id, $field['name'], $value);
        } else {
            delete_term_meta($term_id, $field['name']);
        }
    }
}

function snn_sanitize_value_by_type($type, $value) {
    switch ($type) {
        case 'rich_text':
            return wp_kses_post($value);
        case 'media':
            return intval($value);
        case 'textarea':
            return $value;
        case 'number':
            return floatval($value);
        case 'date':
        case 'color':
            return sanitize_text_field($value);
        default:
            return sanitize_text_field($value);
    }
}

function snn_render_field_input($field, $value = '', $index = '') {
    $field_name = $field['name'];
    if ($index !== '') {
        $name_attribute = 'custom_fields[' . esc_attr($field_name) . '][' . esc_attr($index) . ']';
    } else {
        $name_attribute = 'custom_fields[' . esc_attr($field_name) . ']';
    }

    switch ($field['type']) {
        case 'text':
            echo '<input type="text" name="' . esc_attr($name_attribute) . '" value="' . esc_attr($value) . '" />';
            break;
        case 'number':
            echo '<input type="number" name="' . esc_attr($name_attribute) . '" value="' . esc_attr($value) . '" />';
            break;
        case 'textarea':
            echo '<textarea name="' . esc_attr($name_attribute) . '">' . esc_textarea($value) . '</textarea>';
            break;
        case 'rich_text':
            $editor_id = str_replace(['[', ']'], '_', $name_attribute);
            wp_editor($value, $editor_id, [
                'textarea_name' => $name_attribute,
                'media_buttons' => true,
                'tinymce'       => true,
            ]);
            break;
        case 'media':
            echo '<div class="media-uploader">';
            echo '<input type="hidden" name="' . esc_attr($name_attribute) . '" value="' . esc_attr($value) . '" class="media-url-field" />';
            if ($value) {
                $attachment = get_post($value);
                if ($attachment) {
                    $mime_type = get_post_mime_type($attachment);
                    if (strpos($mime_type, 'image/') === 0) {
                        $image = wp_get_attachment_image_src($value, 'thumbnail');
                        if ($image) {
                            echo '<img src="' . esc_url($image[0]) . '" class="media-preview" style="max-width: 100px; max-height: 100px;" />';
                        } else {
                            echo '<img src="" class="media-preview" style="display: none; max-width: 100px; max-height: 100px;" />';
                        }
                    } else {
                        // Determine appropriate Dashicon based on file type
                        $dashicon = snn_get_dashicon_for_mime($mime_type);
                        echo '<span class="dashicons ' . esc_attr($dashicon) . ' media-preview" style="font-size: 48px;"></span>';
                    }
                } else {
                    echo '<img src="" class="media-preview" style="display: none; max-width: 100px; max-height: 100px;" />';
                }
            } else {
                echo '<img src="" class="media-preview" style="display: none; max-width: 100px; max-height: 100px;" />';
            }
            echo '<button type="button" class="button media-upload-button">Select Media</button>';
            echo '</div>';
            break;
        case 'date':
            echo '<input type="date" name="' . esc_attr($name_attribute) . '" value="' . esc_attr($value) . '" />';
            break;
        case 'color':
            echo '<input type="color" name="' . esc_attr($name_attribute) . '" value="' . esc_attr($value) . '" />';
            break;
        default:
            echo '<input type="text" name="' . esc_attr($name_attribute) . '" value="' . esc_attr($value) . '" />';
            break;
    }
}

/**
 * Get the appropriate Dashicon class for a given MIME type.
 *
 * @param string $mime_type The MIME type of the file.
 * @return string The Dashicon class name.
 */
function snn_get_dashicon_for_mime($mime_type) {
    $mime_to_dashicon = [
        'application/pdf' => 'dashicons-media-document',
        'application/json' => 'dashicons-editor-code',
        'application/vnd.ms-excel' => 'dashicons-media-spreadsheet',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'dashicons-media-spreadsheet',
        'application/msword' => 'dashicons-media-document',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'dashicons-media-document',
        'text/plain' => 'dashicons-editor-paragraph',
        'video/mp4' => 'dashicons-video-alt3',
        'audio/mpeg' => 'dashicons-format-audio',
        // Add more mappings as needed
    ];

    return isset($mime_to_dashicon[$mime_type]) ? $mime_to_dashicon[$mime_type] : 'dashicons-media-default';
}

function snn_output_repeater_field_js() {
    global $snn_repeater_fields_exist, $snn_media_fields_exist;
    if (!$snn_repeater_fields_exist && !$snn_media_fields_exist) {
        return;
    }

    $field_types = ['text', 'number', 'textarea', 'media', 'date', 'color'];
    $templates = [];

    foreach ($field_types as $field_type) {
        ob_start();
        snn_render_field_input(['type' => $field_type, 'name' => '{{field_name}}'], '', '{{index}}');
        $templates[$field_type] = str_replace(["\n", "\r", "'"], ["", "", "\\'"], ob_get_clean());
    }
    ?>
    <style>
    [data-field-type="media"]{
        display:flex;
        gap:10px;
        flex-wrap: wrap;
    }
    [data-field-type="media"] .repeater-item{
        float:left;
    }

    [data-field-type="media"] .media-uploader{
        display: flex;
        flex-direction: column;
        gap:10px;
    }

    .media-uploader .media-preview{
        width:140px !important;
    }

    [data-field-type="media"] .media-uploader img,
    [data-field-type="media"] .media-uploader .dashicons {
        aspect-ratio:1;
        object-fit:cover;
    }

    [class="remove-repeater-item"] {
        color: #2271b1;
        background: #f6f7f7;
        border: solid 1px #2271b1;
        border-radius: 3px;
        cursor: pointer;
        padding: 6px 12px;
        margin-top: 8px;
    }

    .snn-custom-field {
        margin-bottom: 20px;
    }
    </style>

    <script>
    (function($){
        $(document).ready(function(){
            const templates = <?php echo json_encode($templates); ?>;
            $('body').on('click', '.add-repeater-item', function(e){
                e.preventDefault();
                const container = $(this).closest('.repeater-container');
                const fieldName = container.data('field-name');
                const fieldType = container.data('field-type') || 'text';
                const index = container.find('.repeater-item').length;
                const repeaterItem = $('<div class="repeater-item"></div>');

                if (templates[fieldType]) {
                    repeaterItem.html(
                        templates[fieldType]
                            .replace(/{{field_name}}/g, fieldName)
                            .replace(/{{index}}/g, index)
                    );
                    repeaterItem.append('<button type="button" class="remove-repeater-item">Remove</button>');
                    $(this).before(repeaterItem);
                } else {
                    alert('Unsupported field type: ' + fieldType);
                }
            });

            $('body').on('click', '.remove-repeater-item', function(e){
                e.preventDefault();
                $(this).closest('.repeater-item').remove();
            });

            $('body').on('click', '.media-upload-button', function(e){
                e.preventDefault();
                var button = $(this);
                var custom_uploader = wp.media({
                    title: 'Select Media',
                    button: {
                        text: 'Use this media'
                    },
                    multiple: false
                })
                .on('select', function(){
                    var attachment = custom_uploader.state().get('selection').first().toJSON();
                    button.siblings('.media-url-field').val(attachment.id);
                    if (attachment.type === 'image') {
                        button.siblings('.media-preview').attr('src', attachment.url).show();
                        button.siblings('.dashicons').remove();
                    } else {
                        button.siblings('.media-preview').remove();
                        // Create a new Dashicon element based on the MIME type
                        var dashiconClass = '<?php echo esc_js(snn_get_dashicon_for_mime('application/pdf')); ?>'; // Default icon
                        // Ideally, you would pass the mapping from PHP to JS, but for simplicity, using a default
                        button.siblings('.media-preview').remove();
                        var dashicon = $('<span class="dashicons dashicons-media-default media-preview" style="font-size: 48px;"></span>');
                        // Update dashicon based on attachment.mime
                        var mimeType = attachment.mime;
                        var dashiconMap = {
                            'application/pdf': 'dashicons-media-document',
                            'application/json': 'dashicons-editor-code',
                            'application/vnd.ms-excel': 'dashicons-media-spreadsheet',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': 'dashicons-media-spreadsheet',
                            'application/msword': 'dashicons-media-document',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'dashicons-media-document',
                            'text/plain': 'dashicons-editor-paragraph',
                            'video/mp4': 'dashicons-video-alt3',
                            'audio/mpeg': 'dashicons-format-audio',
                            // Add more mappings as needed
                        };
                        var selectedDashicon = dashiconMap[mimeType] || 'dashicons-media-default';
                        dashicon.removeClass('dashicons-media-default').addClass(selectedDashicon);
                        button.before(dashicon);
                    }
                })
                .open();
            });
        });
    })(jQuery);
    </script>
    <?php
}

function snn_enqueue_admin_scripts($hook) {
    global $snn_media_fields_exist;
    if ($snn_media_fields_exist && ('post.php' == $hook || 'post-new.php' == $hook)) {
        wp_enqueue_media();
    }
}
add_action('admin_enqueue_scripts', 'snn_enqueue_admin_scripts');

function snn_save_dynamic_metabox_data($post_id) {
    if (!isset($_POST['snn_custom_fields_nonce']) || !wp_verify_nonce($_POST['snn_custom_fields_nonce'], 'snn_save_custom_fields')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (isset($_POST['post_type']) && 'page' == $_POST['post_type']) {
        if (!current_user_can('edit_page', $post_id)) {
            return;
        }
    } else {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    if (!isset($_POST['custom_fields']) || !is_array($_POST['custom_fields'])) {
        return;
    }

    $custom_fields = get_option('snn_custom_fields', []);

    foreach ($custom_fields as $field) {
        $field_name = $field['name'];
        if (!empty($field['post_type']) && in_array(get_post_type($post_id), $field['post_type'])) {
            
            if (!empty($field['repeater'])) {
                if (isset($_POST['custom_fields'][$field_name]) && is_array($_POST['custom_fields'][$field_name])) {
                    $values = array_map(function($value) use ($field) {
                        return snn_sanitize_value_by_type($field['type'], $value);
                    }, $_POST['custom_fields'][$field_name]);
                    update_post_meta($post_id, $field_name, $values);
                } else {
                    delete_post_meta($post_id, $field_name);
                }
            }
            else {
                if (isset($_POST['custom_fields'][$field_name])) {
                    $value = snn_sanitize_value_by_type($field['type'], $_POST['custom_fields'][$field_name]);
                    update_post_meta($post_id, $field_name, $value);
                } else {
                    delete_post_meta($post_id, $field_name);
                }
            }
        }
    }
}
add_action('save_post', 'snn_save_dynamic_metabox_data');

?>
