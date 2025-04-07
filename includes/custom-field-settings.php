<?php

// ------------------------------------------------
// 1) CREATE SUBMENU TO REGISTER FIELDS
// ------------------------------------------------
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

// ------------------------------------------------
// 2) ENQUEUE SCRIPTS FOR OUR CUSTOM FIELDS PAGE
// ------------------------------------------------
add_action('admin_enqueue_scripts', 'snn_enqueue_scripts_for_custom_fields_page');
function snn_enqueue_scripts_for_custom_fields_page($hook_suffix) {
    // Submenu slug: snn-settings_page_snn-custom-fields
    if ($hook_suffix === 'snn-settings_page_snn-custom-fields') {
        wp_enqueue_media();
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        add_action('admin_footer', 'snn_output_dynamic_field_js');
    }
}

// ------------------------------------------------
// 2.1) ENQUEUE SCRIPTS ON TAXONOMY & AUTHOR PAGES
// ------------------------------------------------
add_action('admin_enqueue_scripts', 'snn_enqueue_taxonomy_author_assets');
function snn_enqueue_taxonomy_author_assets($hook) {
    // Common pages: term.php, edit-tags.php = Taxonomy editing
    // profile.php, user-edit.php = Author profile
    if ( in_array($hook, ['term.php', 'edit-tags.php', 'profile.php', 'user-edit.php'], true) ) {
        wp_enqueue_media();                 // Ensure Media Uploader works
        wp_enqueue_style('wp-color-picker'); // Ensure Color Picker CSS
        wp_enqueue_script('wp-color-picker'); 
        // Our dynamic field JS (repeater, media button, color pickers)
        add_action('admin_footer', 'snn_output_dynamic_field_js');
    }
}

// ------------------------------------------------
// 3) ADMIN PAGE CALLBACK
// ------------------------------------------------
function snn_custom_fields_page_callback() {
    $custom_fields = get_option('snn_custom_fields', []);
    $post_types    = get_post_types(['public' => true], 'objects');
    $taxonomies    = get_taxonomies(['public' => true], 'objects');

    // Process form submission
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
                    $is_repeater_disabled_type = in_array($field_type_for_repeater_check, ['rich_text','select','checkbox','radio','true_false','url','email']);

                    $new_fields[] = [
                        'group_name'     => sanitize_text_field($field['group_name']),
                        'name'           => sanitize_key($field['name']),
                        'type'           => sanitize_text_field($field['type']),
                        'post_type'      => $post_types_selected,
                        'taxonomies'     => $taxonomies_selected,
                        'choices'        => $choices_sanitized,
                        'repeater'       => (!$is_repeater_disabled_type && !empty($field['repeater'])) ? 1 : 0,
                        'author'         => !empty($field['author']) ? 1 : 0,
                        'column_width'   => isset($field['column_width']) && is_numeric($field['column_width']) ? intval($field['column_width']) : '',
                        'return_full_url'=> ($field['type'] === 'media' && !empty($field['return_full_url'])) ? 1 : 0,
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
                <p>
                    Define custom fields with group name, field name, field type, and post type, taxonomy, or author:
                    <br>Select one or more to register the same Custom Field to Post Types, Taxonomies, or Author.
                    <br>Press CTRL/CMD to select multiple or remove selection.
                </p>
                <?php
                if (!empty($custom_fields) && is_array($custom_fields)) {
                    foreach ($custom_fields as $index => $field) {
                        $field_type = isset($field['type']) ? $field['type'] : 'text';
                        $show_choices = in_array($field_type, ['select','checkbox','radio']);
                        $is_repeater_disabled_type = in_array($field_type, ['rich_text','select','checkbox','radio','true_false','url','email']);
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
                                <input type="text" name="custom_fields[<?php echo $index; ?>][group_name]" placeholder="Group Name" 
                                       value="<?php echo esc_attr($field['group_name']) ?? ''; ?>" />
                            </div>
                            <div class="field-group">
                                <label>Field Name</label><br>
                                <input type="text" class="sanitize-key" name="custom_fields[<?php echo $index; ?>][name]" 
                                       placeholder="Field Name" 
                                       value="<?php echo esc_attr($field['name']); ?>" />
                            </div>
                            <!-- Moved Width (%) field here, immediately after Field Name -->
                            <div class="field-group">
                                <label>Width (%)</label><br>
                                <input style="width:70px" type="number" min="10" max="100"
                                       name="custom_fields[<?php echo $index; ?>][column_width]" 
                                       placeholder="25" 
                                       value="<?php echo esc_attr($field['column_width'] ?? ''); ?>" />
                            </div>
                            <div class="field-group">
                                <label>Field Type</label><br>
                                <select name="custom_fields[<?php echo $index; ?>][type]" class="field-type-select" style="width:140px">
                                    <option value="text"       <?php selected($field_type, 'text'); ?>>Text</option>
                                    <option value="number"     <?php selected($field_type, 'number'); ?>>Number</option>
                                    <option value="textarea"   <?php selected($field_type, 'textarea'); ?>>Textarea</option>
                                    <option value="rich_text"  <?php selected($field_type, 'rich_text'); ?>>Rich Text</option>
                                    <option value="media"      <?php selected($field_type, 'media'); ?>>Media</option>
                                    <option value="date"       <?php selected($field_type, 'date'); ?>>Date</option>
                                    <option value="color"      <?php selected($field_type, 'color'); ?>>Color</option>
                                    <option value="select"     <?php selected($field_type, 'select'); ?>>Select</option>
                                    <option value="checkbox"   <?php selected($field_type, 'checkbox'); ?>>Checkbox</option>
                                    <option value="radio"      <?php selected($field_type, 'radio'); ?>>Radio</option>
                                    <option value="true_false" <?php selected($field_type, 'true_false'); ?>>True/False</option>
                                    <option value="url"        <?php selected($field_type, 'url'); ?>>URL</option>
                                    <option value="email"      <?php selected($field_type, 'email'); ?>>Email</option>
                                </select>
                            </div>
                            <div class="field-group field-group-choices" style="<?php echo $show_choices ? '' : 'display:none;'; ?>">
                                <label>Choices <small><code>(value:label)</code></small></label><br>
                                <textarea name="custom_fields[<?php echo $index; ?>][choices]" rows="4" 
                                          placeholder="red : Red Color&#10;green : Green Color"><?php 
                                          echo esc_textarea($field['choices'] ?? ''); ?></textarea>
                            </div>
                            <div class="field-group">
                                <label>Post Types</label><br>
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
                                <label>Taxonomies</label><br>
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
                                <label>Author</label><br>
                                <input type="checkbox" name="custom_fields[<?php echo $index; ?>][author]" value="1" 
                                       <?php checked(!empty($field['author'])); ?> />
                            </div>
                            <div class="field-group">
                                <label>Repeater</label><br>
                                <input type="checkbox" class="repeater-checkbox" name="custom_fields[<?php echo $index; ?>][repeater]" value="1"
                                       <?php checked(!empty($field['repeater'])); echo $is_repeater_disabled_type ? ' disabled' : ''; ?>
                                       title="<?php echo esc_attr($repeater_title); ?>" />
                            </div>
                            <?php if ($field_type === 'media') : ?>
                            <div class="field-group media-return-url-group">
                                <label>Return URL</label><br>
                                <input type="checkbox" name="custom_fields[<?php echo $index; ?>][return_full_url]" value="1" <?php checked(!empty($field['return_full_url'])); ?> />
                            </div>
                            <?php endif; ?>
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

        <!-- The JS below handles dynamic adding/removing/moving of custom fields on the admin page -->
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
                const disable = ['rich_text','select','checkbox','radio','true_false','url','email'].includes(typeSelect.value);
                repeaterCheckbox.disabled = disable;
                repeaterCheckbox.title = disable ? 'This field type cannot be a repeater' : 'Allow multiple values';
                if (disable) {
                    repeaterCheckbox.checked = false;
                }
            }

            function toggleMediaReturnUrlField(row) {
                const typeSelect = row.querySelector('.field-type-select');
                const mediaReturnGroup = row.querySelector('.media-return-url-group');
                if (!typeSelect || !mediaReturnGroup) return;
                mediaReturnGroup.style.display = (typeSelect.value === 'media') ? '' : 'none';
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
                    <div class="field-group"><label>Group Name</label><br><input type="text" name="custom_fields[${newIndex}][group_name]" placeholder="Group Name"></div>
                    <div class="field-group"><label>Field Name</label><br><input type="text" class="sanitize-key" name="custom_fields[${newIndex}][name]" placeholder="Field Name"></div>
                    <div class="field-group"><label>Width (%)</label><br><input style="width:70px" type="number" name="custom_fields[${newIndex}][column_width]" min="10" max="100" placeholder="25"></div>
                    <div class="field-group">
                        <label>Field Type</label><br>
                        <select name="custom_fields[${newIndex}][type]" class="field-type-select" style="width:140px">
                            <option value="text">Text</option>
                            <option value="number">Number</option>
                            <option value="textarea">Textarea</option>
                            <option value="rich_text">Rich Text</option>
                            <option value="media">Media</option>
                            <option value="date">Date</option>
                            <option value="color">Color</option>
                            <option value="select">Select</option>
                            <option value="checkbox">Checkbox</option>
                            <option value="radio">Radio</option>
                            <option value="true_false">True / False</option>
                            <option value="url">URL</option>
                            <option value="email">Email</option>
                        </select>
                    </div>
                    <div class="field-group field-group-choices" style="display:none;">
                        <label>Choices <small>(value:label)</small></label><br>
                        <textarea name="custom_fields[${newIndex}][choices]" rows="4" placeholder="red : Red&#10;green : Green"></textarea>
                    </div>
                    <div class="field-group"><label>Post Types</label><br><select name="custom_fields[${newIndex}][post_type][]" multiple>
                        <?php foreach ($post_types as $pt) : ?>
                            <option value="<?php echo esc_js($pt->name); ?>"><?php echo esc_js($pt->label); ?></option>
                        <?php endforeach; ?>
                    </select></div>
                    <div class="field-group"><label>Taxonomies</label><br><select name="custom_fields[${newIndex}][taxonomies][]" multiple>
                        <?php foreach ($taxonomies as $tax) : ?>
                            <option value="<?php echo esc_js($tax->name); ?>"><?php echo esc_js($tax->label); ?></option>
                        <?php endforeach; ?>
                    </select></div>
                    <div class="field-group"><label>Author</label><br><input type="checkbox" name="custom_fields[${newIndex}][author]" value="1"></div>
                    <div class="field-group"><label>Repeater</label><br><input type="checkbox" class="repeater-checkbox" name="custom_fields[${newIndex}][repeater]" value="1"></div>
                    <div class="field-group media-return-url-group" style="display:none;"><label>Return Full URL</label><br><input type="checkbox" name="custom_fields[${newIndex}][return_full_url]" value="1"></div>
                `;
                fieldContainer.appendChild(newRow);
                attachFieldNameSanitizer(newRow.querySelector('.sanitize-key'));
                toggleChoicesField(newRow);
                toggleRepeaterCheckbox(newRow);
                toggleMediaReturnUrlField(newRow);
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
                    toggleMediaReturnUrlField(row);
                }
            });

            fieldContainer.querySelectorAll('.custom-field-row').forEach(function(row) {
                toggleChoicesField(row);
                toggleRepeaterCheckbox(row);
                toggleMediaReturnUrlField(row);
                attachFieldNameSanitizer(row.querySelector('.sanitize-key'));
            });

            function sanitizeFieldNameKey(value) {
                // Lowercase letters, numbers, underscore only
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
               width:180px;
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
           .custom-field-row select,
           .custom-field-row textarea {
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

// ------------------------------------------------
// 4) REGISTER DYNAMIC META BOXES FOR POSTS
// ------------------------------------------------
function snn_register_dynamic_metaboxes() {
    $custom_fields = get_option('snn_custom_fields', []);
    $grouped_fields = [];
    global $snn_repeater_fields_exist, $snn_media_fields_exist, $snn_color_fields_exist;
    $snn_repeater_fields_exist = false;
    $snn_media_fields_exist = false;
    $snn_color_fields_exist = false;

    foreach ($custom_fields as $field) {
        $group_name = (!empty($field['group_name'])) ? $field['group_name'] : 'Custom Fields';
        if (!empty($field['post_type']) && is_array($field['post_type'])) {
            foreach ($field['post_type'] as $pt) {
                if (!isset($grouped_fields[$pt])) {
                    $grouped_fields[$pt] = [];
                }
                if (!isset($grouped_fields[$pt][$group_name])) {
                    $grouped_fields[$pt][$group_name] = [];
                }
                $grouped_fields[$pt][$group_name][] = $field;

                // Track field existence
                $disallowed_for_repeater = ['rich_text','select','checkbox','radio','true_false','url','email'];
                if (!in_array($field['type'], $disallowed_for_repeater) && !empty($field['repeater'])) {
                    $snn_repeater_fields_exist = true;
                }
                if ($field['type'] === 'media') {
                    $snn_media_fields_exist = true;
                }
                if ($field['type'] === 'color') {
                    $snn_color_fields_exist = true;
                }
                if ($field['type'] === 'date') {
                    wp_enqueue_script('jquery-ui-datepicker');
                }
            }
        }
    }

    // Add meta boxes
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

    // Possibly enqueue scripts
    if ($snn_media_fields_exist || $snn_repeater_fields_exist || $snn_color_fields_exist) {
        add_action('admin_enqueue_scripts', 'snn_enqueue_metabox_scripts');
        add_action('admin_footer', 'snn_output_dynamic_field_js');
    }
}
add_action('add_meta_boxes', 'snn_register_dynamic_metaboxes');

function snn_enqueue_metabox_scripts($hook_suffix) {
    global $pagenow, $snn_media_fields_exist, $snn_color_fields_exist;
    if (in_array($pagenow, ['post.php','post-new.php'])) {
        if ($snn_media_fields_exist) {
            wp_enqueue_media();
            wp_enqueue_style('dashicons');
        }
        if ($snn_color_fields_exist) {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
        }
    }
    // Profile
    if (in_array($hook_suffix, ['profile.php','user-edit.php'])) {
        $custom_fields = get_option('snn_custom_fields', []);
        $has_author_media = false;
        $has_author_color = false;
        foreach ($custom_fields as $field) {
            if (!empty($field['author'])) {
                if ($field['type'] === 'media') {
                    $has_author_media = true;
                }
                if ($field['type'] === 'color') {
                    $has_author_color = true;
                }
            }
        }
        if ($has_author_media) {
            wp_enqueue_media();
        }
        if ($has_author_color) {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
        }
        if ($has_author_media || $has_author_color) {
            add_action('admin_footer', 'snn_output_dynamic_field_js');
        }
    }
    // Taxonomy pages
    if (in_array($pagenow, ['term.php','edit-tags.php'])) {
        $custom_fields = get_option('snn_custom_fields', []);
        $has_tax_media = false;
        $has_tax_color = false;
        foreach ($custom_fields as $field) {
            if (!empty($field['taxonomies'])) {
                if ($field['type'] === 'media') {
                    $has_tax_media = true;
                }
                if ($field['type'] === 'color') {
                    $has_tax_color = true;
                }
            }
        }
        if ($has_tax_media) {
            wp_enqueue_media();
        }
        if ($has_tax_color) {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
        }
        if ($has_tax_media || $has_tax_color) {
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

        // Filter out empty strings to avoid empty repeater rows
        if (is_array($field_value)) {
            $field_value = array_filter($field_value, function($val) {
                return $val !== '';
            });
        }

        echo '<div class="snn-field-wrap snn-field-type-' . esc_attr($field['type']) 
             . (!empty($field['repeater']) ? ' snn-is-repeater' : '') 
             . '" style="width:calc(' . $col_width . '% - 30px);margin-right:20px;box-sizing:border-box;">';

        echo '<label class="snn-field-label" for="' . esc_attr($field_name . '_0') . '">'
             . esc_html(ucwords(str_replace('_',' ',$field_name))) . '</label>';

        // If repeater:
        if (!empty($field['repeater'])) {
            $values = (is_array($field_value)) ? $field_value : [];
            echo '<div class="repeater-container" data-field-name="' . esc_attr($field_name) . '">';

            // Existing items
            if (!empty($values)) {
                foreach ($values as $index => $value) {
                    echo '<div class="repeater-item">';
                    echo '<div class="repeater-content">';
                    snn_render_field_input($field, $value, $index);
                    echo '</div>';
                    echo '<button type="button" class="button remove-repeater-item">Remove</button>';
                    echo '</div>';
                }
            }

            // Hidden template
            echo '<div class="repeater-item repeater-template" style="display:none;">';
            echo '<div class="repeater-content">';
            // Use a placeholder index "__index__"
            snn_render_field_input($field, '', '__index__');
            echo '</div>';
            echo '<button type="button" class="button remove-repeater-item">Remove</button>';
            echo '</div>';

            echo '<button type="button" class="button add-repeater-item">Add More +</button>';
            echo '</div>';
        } else {
            // Normal single field
            snn_render_field_input($field, $field_value, '0');
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
        margin-bottom: 15px;
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
    .snn-field-wrap select,
    .snn-field-wrap textarea {
        width: 100%;
        max-width: 600px;
        padding: 8px;
        margin-bottom: 5px;
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
        margin-top: 5px;
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
    .snn-field-type-media .media-preview {
        max-width: 50px; 
        max-height: 50px; 
        display: inline-block; 
        border: 1px solid #ddd; 
        padding: 2px; 
        background: #fff;
        vertical-align: middle;
    }
    .snn-field-type-media .media-uploader button {
        margin-top: 5px; 
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
    .wp-picker-container {
        display: inline-block;
    }
    .wp-picker-container .wp-color-result.button {
        margin: 0; 
        vertical-align: middle;
    }
    .wp-picker-container .wp-picker-input-wrap input[type=text].wp-color-picker {
        width: 80px; 
        margin-left: 5px; 
        vertical-align: middle;
    }
    .media-filename {
        font-size: 12px; 
        color: #555; 
        margin-top: 4px;
    }
    </style>
    <?php
}

// ------------------------------------------------
// 6) RENDER FIELD INPUT
// ------------------------------------------------
function snn_render_field_input($field, $value = '', $index = '0') {
    $field_name = $field['name'];
    $field_type = $field['type'];
    $is_template = ($index === '__index__');

    if (!empty($field['repeater']) && !$is_template) {
        // e.g. custom_fields[fieldname][2]
        $name_attribute = 'custom_fields[' . esc_attr($field_name) . '][' . intval($index) . ']';
    } elseif (!empty($field['repeater']) && $is_template) {
        // Hidden template row
        $name_attribute = 'custom_fields[' . esc_attr($field_name) . '][__index__]';
    } elseif ($field_type === 'checkbox') {
        // If single checkboxes with multiple choices
        $name_attribute = 'custom_fields[' . esc_attr($field_name) . '][]';
    } else {
        $name_attribute = 'custom_fields[' . esc_attr($field_name) . ']';
    }

    // Prepare choices if needed
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
            echo '<input type="text" id="' . esc_attr($field_name . '_' . $index) 
                 . '" name="' . esc_attr($name_attribute) . '" value="' . esc_attr($value) . '" />';
            break;

        case 'number':
            echo '<input type="number" id="' . esc_attr($field_name . '_' . $index) 
                 . '" name="' . esc_attr($name_attribute) . '" value="' . esc_attr($value) . '" step="any" />';
            break;

        case 'textarea':
            echo '<textarea id="' . esc_attr($field_name . '_' . $index) 
                 . '" name="' . esc_attr($name_attribute) . '">' . esc_textarea($value) . '</textarea>';
            break;

        case 'rich_text':
            $editor_id = str_replace(['[',']'], '_', $name_attribute);
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
            $filename = '';
            $dashicon = 'dashicons-media-default'; // default icon
            if (!empty($value)) {
                if (is_numeric($value)) {
                    $attachment_id = intval($value);
                    $attachment = get_post($attachment_id);
                    if ($attachment) {
                        $filename = esc_html(basename(get_attached_file($attachment_id)));
                        $mime_type = get_post_mime_type($attachment);
                        if (strpos($mime_type, 'image/') === 0 || $mime_type === 'image/svg+xml') {
                            $image = wp_get_attachment_image_src($attachment_id, 'thumbnail');
                            if ($image) {
                                $img_src = $image[0];
                            }
                        } elseif (strpos($mime_type, 'video/') === 0) {
                            $dashicon = 'dashicons-media-video';
                        } elseif (strpos($mime_type, 'audio/') === 0) {
                            $dashicon = 'dashicons-media-audio';
                        } elseif ($mime_type === 'application/pdf') {
                            $dashicon = 'dashicons-media-document';
                        } elseif (strpos($mime_type, 'application/') === 0) {
                            $dashicon = 'dashicons-media-spreadsheet';
                        } else {
                            $dashicon = 'dashicons-media-default';
                        }
                    }
                } else {
                    // Assume $value is a URL (for full URL return)
                    $img_src = esc_url($value);
                    $filename = basename($value);
                }
            }

            echo '<div class="media-uploader">';
            echo '<input type="hidden" class="media-value-field" id="' . esc_attr($field_name . '_' . $index) 
                 . '" name="' . esc_attr($name_attribute) . '" value="' . esc_attr($value) . '" />';
            echo '<span class="media-preview-wrapper" style="width:50px; height:50px;">';
            if ($img_src) {
                if (strpos($img_src, 'http') === 0) {
                    echo '<img src="' . esc_url($img_src) . '" class="media-preview" style="max-width:50px;max-height:50px;" />';
                } else {
                    echo '<span class="dashicons ' . esc_attr($dashicon) . ' media-preview" style="font-size:40px;line-height:50px;display:inline-block;width:50px;height:50px;text-align:center;"></span>';
                }
            } else {
                echo '<span class="dashicons ' . esc_attr($dashicon) . ' media-preview" style="font-size:40px;line-height:50px;display:inline-block;width:50px;height:50px;text-align:center;"></span>';
            }
            echo '</span>';
            echo '<button type="button" class="button media-upload-button">Select</button>';
            echo '<button type="button" class="button media-remove-button" style="' . (empty($value)?'display:none;':'') . '">X</button>';
            if (!empty($filename)) {
                echo '<div class="media-filename" style="font-size:12px;margin-top:4px;">' . esc_html($filename) . '</div>';
            }
            echo '</div>';
            break;

        case 'date':
            echo '<input type="date" id="' . esc_attr($field_name . '_' . $index) 
                 . '" name="' . esc_attr($name_attribute) . '" value="' . esc_attr($value) 
                 . '" placeholder="YYYY-MM-DD" class="snn-datepicker" />';
            break;

        case 'color':
            echo '<input type="text" id="' . esc_attr($field_name . '_' . $index) 
                 . '" name="' . esc_attr($name_attribute) . '" value="' . esc_attr($value) 
                 . '" class="snn-color-picker" data-default-color="#ffffff" />';
            break;

        case 'select':
            echo '<select id="' . esc_attr($field_name . '_' . $index) . '" name="' . esc_attr($name_attribute) . '">';
            echo '<option value="">-- Select --</option>';
            if (!empty($choices)) {
                foreach ($choices as $val => $label) {
                    echo '<option value="' . esc_attr($val) . '" ' . selected($value, $val, false) . '>'
                         . esc_html($label) . '</option>';
                }
            }
            echo '</select>';
            break;

        case 'checkbox':
            // multiple checkboxes
            $checked_values = (is_array($value)) ? $value : ((!empty($value)) ? [$value] : []);
            echo '<div class="checkbox-group">';
            if (!empty($choices)) {
                $i = 0;
                foreach ($choices as $val => $label) {
                    $choice_id = $field_name . '_' . $index . '_' . $i++;
                    $is_checked = in_array($val, $checked_values);
                    echo '<span class="choice-item">';
                    echo '<input type="checkbox" id="' . esc_attr($choice_id) 
                         . '" name="' . esc_attr($name_attribute) 
                         . '" value="' . esc_attr($val) . '" ' . ($is_checked?'checked':'') . ' />';
                    echo '<label for="' . esc_attr($choice_id) . '">' . esc_html($label) . '</label>';
                    echo '</span>';
                }
            } else {
                echo '<em>No choices defined.</em>';
            }
            echo '</div>';
            break;

        case 'radio':
            // single choice among many
            echo '<div class="radio-group">';
            if (!empty($choices)) {
                $i=0;
                foreach ($choices as $val => $label) {
                    $choice_id = $field_name . '_' . $index . '_' . $i++;
                    // if repeater, name might be custom_fields[field_name][index]
                    $radio_name = (!empty($field['repeater']) && !$is_template)
                                  ? 'custom_fields[' . esc_attr($field_name) . ']['.intval($index).']'
                                  : $name_attribute;
                    echo '<span class="choice-item">';
                    echo '<input type="radio" id="' . esc_attr($choice_id) 
                         . '" name="' . esc_attr($radio_name) 
                         . '" value="' . esc_attr($val) . '" ' . checked($value, $val, false) . ' />';
                    echo '<label for="' . esc_attr($choice_id) . '">' . esc_html($label) . '</label>';
                    echo '</span>';
                }
            } else {
                echo '<em>No choices defined.</em>';
            }
            echo '</div>';
            break;

        case 'true_false':
            // boolean toggle
            echo '<input type="hidden" name="' . esc_attr($name_attribute) . '" value="0" />';
            echo '<input type="checkbox" id="' . esc_attr($field_name . '_' . $index) 
                 . '" name="' . esc_attr($name_attribute) . '" value="1" ' . checked($value, '1', false) . ' />';
            break;

        case 'url':
            echo '<input type="url" id="' . esc_attr($field_name . '_' . $index) 
                 . '" name="' . esc_attr($name_attribute) . '" value="' . esc_attr($value) 
                 . '" placeholder="https://example.com" />';
            break;

        case 'email':
            echo '<input type="email" id="' . esc_attr($field_name . '_' . $index) 
                 . '" name="' . esc_attr($name_attribute) . '" value="' . esc_attr($value) 
                 . '" placeholder="name@example.com" />';
            break;

        default: // fallback
            echo '<input type="text" id="' . esc_attr($field_name . '_' . $index) 
                 . '" name="' . esc_attr($name_attribute) . '" value="' . esc_attr($value) . '" />';
            break;
    }
}

// ------------------------------------------------
// 7) SAVE POST META
// ------------------------------------------------
function snn_save_custom_fields_meta($post_id) {
    if (!isset($_POST['snn_custom_fields_nonce']) || !wp_verify_nonce($_POST['snn_custom_fields_nonce'], 'snn_save_custom_fields')) {
        return $post_id;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }
    if (isset($_POST['post_type']) && $_POST['post_type'] === 'page') {
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
    $posted_data   = $_POST['custom_fields'] ?? [];

    foreach ($custom_fields as $field) {
        $field_name = $field['name'];
        $post_type  = get_post_type($post_id);

        // If this field doesn't apply to current post_type, skip
        if (empty($field['post_type']) || !in_array($post_type, $field['post_type'])) {
            continue;
        }

        // Check if we have data for this field
        if (isset($posted_data[$field_name])) {
            // If it's an array (repeater or multiple checkboxes, etc.)
            if (is_array($posted_data[$field_name])) {
                $sanitized_values = array_map(function($item) use ($field) {
                    return snn_sanitize_value_by_type($field['type'], $item, $field);
                }, $posted_data[$field_name]);
                // Filter out truly empty items
                $sanitized_values = array_filter($sanitized_values, function($v) {
                    return ($v !== null && $v !== '');
                });

                update_post_meta($post_id, $field_name, $sanitized_values);
            } else {
                // Single value
                $val = snn_sanitize_value_by_type($field['type'], $posted_data[$field_name], $field);
                if ($val !== '' && $val !== null) {
                    update_post_meta($post_id, $field_name, $val);
                } else {
                    delete_post_meta($post_id, $field_name);
                }
            }
        } else {
            // If not set in $_POST
            if ($field['type'] === 'true_false') {
                update_post_meta($post_id, $field_name, '0');
            } else {
                delete_post_meta($post_id, $field_name);
            }
        }
    }
}
add_action('save_post', 'snn_save_custom_fields_meta');

// ------------------------------------------------
// 8) REGISTER DYNAMIC TAXONOMY FIELDS
// ------------------------------------------------
function snn_register_dynamic_taxonomy_fields() {
    $custom_fields = get_option('snn_custom_fields', []);
    if (!empty($custom_fields)) {
        foreach ($custom_fields as $field) {
            // No repeater for taxonomy forms
            if (!empty($field['repeater'])) {
                continue;
            }
            if (!empty($field['taxonomies']) && is_array($field['taxonomies'])) {
                foreach ($field['taxonomies'] as $tax) {
                    add_action($tax . '_add_form_fields', function() use ($field) {
                        $col_width = !empty($field['column_width']) ? intval($field['column_width']) : 100;
                        ?>
                        <!-- Wrap in snn-metabox-wrapper & snn-field-wrap -->
                        <div class="form-field snn-metabox-wrapper" style="display:flex;flex-wrap:wrap;">
                            <div class="snn-field-wrap" style="width:100%;margin-right:20px;box-sizing:border-box; padding:10px">
                                <label><?php echo esc_html(ucwords(str_replace('_',' ',$field['name']))); ?></label>
                                <?php snn_render_field_input($field, '', '0'); ?>
                            </div>
                        </div>
                        <?php
                    }, 10, 1);

                    add_action($tax . '_edit_form_fields', function($term) use ($field) {
                        $value = get_term_meta($term->term_id, $field['name'], true);
                        $col_width = !empty($field['column_width']) ? intval($field['column_width']) : 100;
                        ?>
                        <tr class="form-field snn-metabox-wrapper" >
                            <th scope="row"></th>
                            <td style="width:100%;padding:10px;">
                                <div class="snn-field-wrap" style="width:100%;margin-right:20px;box-sizing:border-box;">
                                    <label><?php echo esc_html(ucwords(str_replace('_',' ',$field['name']))); ?></label>
                                    <?php snn_render_field_input($field, $value, '0'); ?>
                                </div>
                            </td>
                        </tr>
                        <?php
                    }, 10, 1);

                    // Save
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
    .snn-metabox-wrapper {
        background: #fff;
        border: 1px solid #eee;
        border-radius: 5px;
        padding: 10px;
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
    .snn-field-wrap select,
    .snn-field-wrap textarea {
        width: 100%;
        max-width: 600px;
        padding: 8px;
        margin-bottom: 5px;
    }
    .snn-field-wrap textarea {
        min-height: 80px;
    }
    </style>
    <?php
}

function snn_save_taxonomy_field_data($term_id) {
    if (!current_user_can('manage_categories')) {
        return;
    }
    $custom_fields = get_option('snn_custom_fields', []);
    $posted_data   = $_POST['custom_fields'] ?? [];
    $term          = get_term($term_id);
    if (!$term || is_wp_error($term)) {
        return;
    }
    $taxonomy = $term->taxonomy;

    foreach ($custom_fields as $field) {
        if (!empty($field['repeater'])) {
            continue;
        }
        if (empty($field['taxonomies']) || !in_array($taxonomy, $field['taxonomies'])) {
            continue;
        }
        $field_name = $field['name'];
        // If posted
        if (isset($posted_data[$field_name])) {
            $val = $posted_data[$field_name];
            if (is_array($val)) {
                $sanitized = array_map(function($v) use ($field) {
                    return snn_sanitize_value_by_type($field['type'], $v, $field);
                }, $val);
                $sanitized = array_filter($sanitized, function($v) {
                    return ($v !== '' && $v !== null);
                });
                if (!empty($sanitized)) {
                    update_term_meta($term_id, $field_name, $sanitized);
                } else {
                    delete_term_meta($term_id, $field_name);
                }
            } else {
                $san = snn_sanitize_value_by_type($field['type'], $val, $field);
                if ($san !== '' && $san !== null) {
                    update_term_meta($term_id, $field_name, $san);
                } else {
                    delete_term_meta($term_id, $field_name);
                }
            }
        } else {
            if ($field['type'] === 'true_false') {
                update_term_meta($term_id, $field_name, '0');
            } else {
                delete_term_meta($term_id, $field_name);
            }
        }
    }
}

// ------------------------------------------------
// 9) CUSTOM FIELDS ON AUTHOR PROFILES
// ------------------------------------------------
function snn_add_author_profile_fields() {
    $custom_fields = get_option('snn_custom_fields', []);
    $author_fields = [];
    global $snn_media_fields_exist, $snn_color_fields_exist;

    foreach ($custom_fields as $field) {
        if (!empty($field['author']) && empty($field['repeater'])) {
            $author_fields[] = $field;
            if ($field['type'] === 'media') {
                $snn_media_fields_exist = true;
            }
            if ($field['type'] === 'color') {
                $snn_color_fields_exist = true;
            }
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
    if (empty($author_fields)) {
        return;
    }
    ?>
    <h2>Custom Author Information</h2>
    <?php wp_nonce_field('snn_save_author_fields', 'snn_author_fields_nonce'); ?>
    <!-- We'll mimic the .snn-metabox-wrapper style for consistent UI -->
    <div class="snn-metabox-wrapper" style="display:flex;flex-wrap:wrap;">
        <?php
        foreach ($author_fields as $field) {
            $field_name = $field['name'];
            $value      = get_user_meta($user->ID, $field_name, true);
            $col_width  = !empty($field['column_width']) ? intval($field['column_width']) : 100;
            ?>
            <div class="snn-field-wrap snn-field-type-<?php echo esc_attr($field['type']); ?>"
                 style="width:calc(<?php echo $col_width; ?>% - 30px);margin-right:20px;box-sizing:border-box;">
                <label><?php echo esc_html(ucwords(str_replace('_',' ',$field['name']))); ?></label>
                <?php snn_render_field_input($field, $value, '0'); ?>
            </div>
            <?php
        }
        ?>
    </div>
    <style>
    .snn-metabox-wrapper {
        background: #fff;
        border: 1px solid #eee;
        border-radius: 5px;
        padding: 10px;
        margin-bottom: 20px;
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
    .snn-field-wrap select,
    .snn-field-wrap textarea {
        width: 100%;
        max-width: 400px;
        padding: 6px;
    }
    .snn-field-wrap textarea {
        min-height: 80px;
    }
    .snn-field-wrap input[type="checkbox"] {
        width: auto;
    }
    </style>
    <?php
}

function snn_save_author_custom_fields($user_id) {
    if (!isset($_POST['snn_author_fields_nonce']) || 
        !wp_verify_nonce($_POST['snn_author_fields_nonce'], 'snn_save_author_fields')) {
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
            $val = $posted_data[$field_name];
            if (is_array($val)) {
                $vals = array_map(function($v) use ($field) {
                    return snn_sanitize_value_by_type($field['type'], $v, $field);
                }, $val);
                $vals = array_filter($vals, function($v) {
                    return ($v !== '' && $v !== null);
                });
                if (!empty($vals)) {
                    update_user_meta($user_id, $field_name, $vals);
                } else {
                    delete_user_meta($user_id, $field_name);
                }
            } else {
                $san = snn_sanitize_value_by_type($field['type'], $val, $field);
                if ($san !== '' && $san !== null) {
                    update_user_meta($user_id, $field_name, $san);
                } else {
                    delete_user_meta($user_id, $field_name);
                }
            }
        } else {
            // true_false default to 0
            if ($field['type'] === 'true_false') {
                update_user_meta($user_id, $field_name, '0');
            } else {
                delete_user_meta($user_id, $field_name);
            }
        }
    }
}

// ------------------------------------------------
// 10) HELPER: SANITIZE VALUE BY TYPE
// ------------------------------------------------
function snn_sanitize_value_by_type($type, $value, $field = null) {
    switch ($type) {
        case 'rich_text':
            return wp_kses_post($value);
        case 'textarea':
            return sanitize_textarea_field($value);
        case 'media':
            if ($field && !empty($field['return_full_url'])) {
                return $value ? esc_url_raw(wp_get_attachment_url(intval($value))) : '';
            } else {
                return $value ? intval($value) : '';
            }
        case 'number':
            return (is_numeric($value)) ? floatval($value) : '';
        case 'date':
        case 'color':
        case 'select':
        case 'radio':
        case 'checkbox':
            return sanitize_text_field($value);
        case 'true_false':
            return ($value == '1' || $value === true) ? '1' : '0';
        case 'url':
            return esc_url_raw($value);
        case 'email':
            return sanitize_email($value);
        default: // 'text'
            return sanitize_text_field($value);
    }
}

// ------------------------------------------------
// 11) OUTPUT DYNAMIC JS FOR MEDIA/REPEATERS
// ------------------------------------------------
function snn_output_dynamic_field_js() {
    // Skip if AJAX or REST
    if (wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {

        // Open WP media
        $(document).on('click', '.media-upload-button', function(e) {
            e.preventDefault();
            var $button   = $(this);
            var $uploader = $button.closest('.media-uploader');
            var $preview  = $uploader.find('.media-preview-wrapper');
            var $remove   = $uploader.find('.media-remove-button');
            var $input    = $uploader.find('.media-value-field');

            var frame = wp.media({
                title: 'Choose Media',
                button: { text: 'Select' },
                multiple: false
            });

            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                $input.val(attachment.id);
                var thumbUrl = (attachment.sizes && attachment.sizes.thumbnail)
                               ? attachment.sizes.thumbnail.url : attachment.url;
                $preview.html('<img src="'+ thumbUrl +'" class="media-preview" style="max-width:50px;max-height:50px;">');
                $remove.show();
            });

            frame.open();
        });

        // Remove media
        $(document).on('click', '.media-remove-button', function(e) {
            e.preventDefault();
            var $btn      = $(this);
            var $uploader = $btn.closest('.media-uploader');
            $uploader.find('.media-value-field').val('');
            $uploader.find('.media-preview-wrapper').empty();
            $btn.hide();
        });

        // Add repeater item
        $(document).on('click', '.add-repeater-item', function(e) {
            e.preventDefault();
            var $container = $(this).closest('.repeater-container');
            var $template  = $container.find('.repeater-template').first().clone(true);
            // Clean up
            $template.removeClass('repeater-template').show();

            // Insert before the Add button
            $template.insertBefore($(this));

            // Reindex
            reindexRepeaterItems($container);
        });

        // Remove repeater item
        $(document).on('click', '.remove-repeater-item', function(e) {
            e.preventDefault();
            var $item      = $(this).closest('.repeater-item');
            var $container = $item.closest('.repeater-container');
            $item.remove();
            reindexRepeaterItems($container);
        });

        // Reindex function
        function reindexRepeaterItems($container) {
            var fieldName = $container.data('field-name');
            var $items    = $container.find('.repeater-item').not('.repeater-template');
            
            $items.each(function(index) {
                var $inputs = $(this).find('input, select, textarea');
                $inputs.each(function() {
                    var type = $(this).attr('type');
                    if (type !== 'button') {
                        // Replace "__index__" or old numeric index with the new index
                        var nameAttr = 'custom_fields['+ fieldName +']['+ index +']';
                        $(this).attr('name', nameAttr);
                        $(this).attr('id', fieldName + '_' + index);
                    }
                });
            });
        }

        // Initialize color pickers if any
        if ($('.snn-color-picker').length) {
            $('.snn-color-picker').wpColorPicker();
        }

        // -----------------------------------------------------------------
        // NEW: Force all wp_editor instances to open in the HTML (Text) tab
        // on post edit screens (post-new.php and post.php) for post type "post"
        // -----------------------------------------------------------------
        if (typeof switchEditors !== 'undefined') {
            $('.wp-editor-wrap').each(function() {
                var editorID = $(this).attr('id').replace('-wrap', '');
                switchEditors.go(editorID, 'html');
            });
        }

        // Optional: For dynamically added editors in repeater items, add a slight delay
        $(document).on('click', '.add-repeater-item', function() {
            setTimeout(function() {
                if (typeof switchEditors !== 'undefined') {
                    $('.wp-editor-wrap').each(function() {
                        var editorID = $(this).attr('id').replace('-wrap','');
                        switchEditors.go(editorID, 'html');
                    });
                }
            }, 200);
        });

    });
    </script>
    <?php
}

// -----------------------------------------------------------------
// NEW: INIT TINYMCE EDITOR ON TEXT/HTML TAB BY DEFAULT ON POST EDIT SCREENS
// -----------------------------------------------------------------
add_action('admin_footer', 'snn_init_tinymce_html_default', 100);
function snn_init_tinymce_html_default() {
    global $pagenow;
    // Only on post-new.php and post.php screens
    if (in_array($pagenow, ['post-new.php', 'post.php'])) {
        // Ensure we are editing a "post" type page
        $screen = get_current_screen();
        if ( isset($screen->post_type) && $screen->post_type === 'post' ) {
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                if (typeof switchEditors !== 'undefined') {
                    $('.wp-editor-wrap').each(function() {
                        var editorID = $(this).attr('id').replace('-wrap','');
                        switchEditors.go(editorID, 'html');
                    });
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

                // 1. Detect taxonomy form submissions (add/edit)
                $('form').on('submit', function(event) {
                    const action = $(this).attr('action') || '';
                    if (action.includes('edit-tags.php')) {
                        const form = this;
                        const $submitButton = $(form).find('input[type="submit"], button[type="submit"]');
                        
                        // Avoid multiple reloads
                        $submitButton.prop('disabled', true);
                        
                        // Wait for AJAX response, then reload
                        setTimeout(function () {
                            location.reload();
                        }, 500);
                    }
                });

                // 2. Detect delete links
                $(document).on('click', '.delete-tag', function(event) {
                    event.preventDefault();
                    const url = $(this).attr('href');

                    if (confirm('Are you sure you want to delete this item?')) {
                        $.post(url, { action: 'delete-tag' }, function () {
                            location.reload();
                        });
                    }
                });

                // 3. Optional: Hook into ajaxComplete for extra safety (optional but recommended)
                $(document).ajaxComplete(function(event, xhr, settings) {
                    if (settings && settings.url && settings.url.includes('edit-tags.php')) {
                        location.reload();
                    }
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
        // Listen for any AJAX call that handles taxonomy term actions.
        $(document).ajaxComplete(function(event, xhr, settings) {
            // Check if the AJAX request is for adding, updating, or deleting a taxonomy term.
            if (settings.data && (
                settings.data.indexOf('action=add-tag') !== -1 ||
                settings.data.indexOf('action=delete-tag') !== -1 ||
                settings.data.indexOf('action=update-tag') !== -1
            )) {
                // Refresh the page to update the taxonomy overview.
                window.location.reload();
            }
        });
    });
    </script>
    <?php
}
add_action('admin_footer-edit-tags.php', 'snn_taxonomy_overview_js');

?>
