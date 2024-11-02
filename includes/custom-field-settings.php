<?php
// Add a submenu page for custom fields under the main settings menu
function snn_add_custom_fields_submenu() {
    add_submenu_page(
        'snn-settings',
        'Custom Fields',
        'Custom Fields',
        'manage_options',
        'snn-custom-fields',
        'snn_custom_fields_page_callback'
    );
}
add_action('admin_menu', 'snn_add_custom_fields_submenu', 89);

// Display the Custom Fields page with group name, field name, type, and post type
function snn_custom_fields_page_callback() {
    $custom_fields = get_option('snn_custom_fields', []);
    $post_types = get_post_types(['public' => true], 'objects'); // Get public post types

    if (isset($_POST['snn_custom_fields_nonce']) && wp_verify_nonce($_POST['snn_custom_fields_nonce'], 'snn_custom_fields_save')) {
        $new_fields = [];
        if (!empty($_POST['custom_fields'])) {
            foreach ($_POST['custom_fields'] as $field) {
                if (!empty($field['name']) && !empty($field['type']) && !empty($field['post_type']) && !empty($field['group_name'])) {
                    $new_fields[] = [
                        'group_name' => sanitize_text_field($field['group_name']),
                        'name' => sanitize_text_field($field['name']),
                        'type' => sanitize_text_field($field['type']),
                        'post_type' => sanitize_text_field($field['post_type']),
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
                <p>Define custom fields with group name, field name, field type, and post type:</p>
                <?php
                if (!empty($custom_fields) && is_array($custom_fields)) {
                    foreach ($custom_fields as $index => $field) {
                        ?>
                        <div class="custom-field-row">
                            <label>Group Name</label>
                            <input type="text" name="custom_fields[<?php echo $index; ?>][group_name]" placeholder="Group Name" value="<?php echo isset($field['group_name']) ? esc_attr($field['group_name']) : ''; ?>" />
                            
                            <label>Field Name</label>
                            <input type="text" name="custom_fields[<?php echo $index; ?>][name]" placeholder="Field Name" value="<?php echo esc_attr($field['name']); ?>" />
                            
                            <label>Field Type</label>
                            <select name="custom_fields[<?php echo $index; ?>][type]">
                                <option value="text" <?php selected($field['type'], 'text'); ?>>Text</option>
                                <option value="number" <?php selected($field['type'], 'number'); ?>>Number</option>
                                <option value="textarea" <?php selected($field['type'], 'textarea'); ?>>Textarea</option>
                                <option value="rich_text" <?php selected($field['type'], 'rich_text'); ?>>Rich Text</option>
                                <option value="media" <?php selected($field['type'], 'media'); ?>>Media</option>
                                <option value="date" <?php selected($field['type'], 'date'); ?>>Date</option>
                                <option value="color" <?php selected($field['type'], 'color'); ?>>Color</option>
                            </select>
                            
                            <label>Post Type</label>
                            <select name="custom_fields[<?php echo $index; ?>][post_type]">
                                <?php foreach ($post_types as $post_type) : ?>
                                    <option value="<?php echo esc_attr($post_type->name); ?>" <?php selected($field['post_type'], $post_type->name); ?>>
                                        <?php echo esc_html($post_type->label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="remove-field">Remove</button>
                        </div>
                        <?php
                    }
                } else {
                    ?>
                    <div class="custom-field-row">
                        <label>Group Name</label>
                        <input type="text" name="custom_fields[0][group_name]" placeholder="Group Name" />
                        
                        <label>Field Name</label>
                        <input type="text" name="custom_fields[0][name]" placeholder="Field Name" />
                        
                        <label>Field Type</label>
                        <select name="custom_fields[0][type]">
                            <option value="text">Text</option>
                            <option value="number">Number</option>
                            <option value="textarea">Textarea</option>
                            <option value="rich_text">Rich Text</option>
                            <option value="media">Media</option>
                            <option value="date">Date</option>
                            <option value="color">Color</option>
                        </select>
                        
                        <label>Post Type</label>
                        <select name="custom_fields[0][post_type]">
                            <?php foreach ($post_types as $post_type) : ?>
                                <option value="<?php echo esc_attr($post_type->name); ?>">
                                    <?php echo esc_html($post_type->label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="remove-field">Remove</button>
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

            addFieldButton.addEventListener('click', function() {
                const newIndex = fieldContainer.querySelectorAll('.custom-field-row').length;
                const newRow = document.createElement('div');
                newRow.classList.add('custom-field-row');
                newRow.innerHTML = '<label>Group Name</label>' +
                                   '<input type="text" name="custom_fields[' + newIndex + '][group_name]" placeholder="Group Name" />' +
                                   '<label>Field Name</label>' +
                                   '<input type="text" name="custom_fields[' + newIndex + '][name]" placeholder="Field Name" />' +
                                   '<label>Field Type</label>' +
                                   '<select name="custom_fields[' + newIndex + '][type]">' +
                                   '<option value="text">Text</option>' +
                                   '<option value="number">Number</option>' +
                                   '<option value="textarea">Textarea</option>' +
                                   '<option value="rich_text">Rich Text</option>' +
                                   '<option value="media">Media</option>' +
                                   '<option value="date">Date</option>' +
                                   '<option value="color">Color</option>' +
                                   '</select>' +
                                   '<label>Post Type</label>' +
                                   '<select name="custom_fields[' + newIndex + '][post_type]">' +
                                   <?php foreach ($post_types as $post_type) : ?>
                                       '<option value="<?php echo esc_js($post_type->name); ?>"><?php echo esc_js($post_type->label); ?></option>' +
                                   <?php endforeach; ?>
                                   '</select>' +
                                   '<button type="button" class="remove-field">Remove</button>';
                fieldContainer.appendChild(newRow);
            });

            fieldContainer.addEventListener('click', function(event) {
                if (event.target.classList.contains('remove-field')) {
                    event.target.closest('.custom-field-row').remove();
                }
            });
        });
        </script>
    </div>
    <?php
}

// Dynamically register metaboxes with grouped fields
function snn_register_dynamic_metaboxes() {
    $custom_fields = get_option('snn_custom_fields', []);
    $grouped_fields = [];

    foreach ($custom_fields as $field) {
        $group_name = $field['group_name'] ?? 'default';
        $grouped_fields[$field['post_type']][$group_name][] = $field;
    }

    foreach ($grouped_fields as $post_type => $groups) {
        foreach ($groups as $group_name => $fields) {
            add_meta_box(
                'custom_field_group_' . sanitize_title($group_name),
                $group_name,
                function($post) use ($fields) {
                    foreach ($fields as $field) {
                        $value = get_post_meta($post->ID, $field['name'], true);
                        echo '<p><label>' . esc_html($field['name']) . '</label>';

                        if ($field['type'] === 'text') {
                            echo '<input type="text" name="custom_fields[' . esc_attr($field['name']) . ']" value="' . esc_attr($value) . '" />';
                        } elseif ($field['type'] === 'number') {
                            echo '<input type="number" name="custom_fields[' . esc_attr($field['name']) . ']" value="' . esc_attr($value) . '" />';
                        } elseif ($field['type'] === 'textarea') {
                            echo '<textarea name="custom_fields[' . esc_attr($field['name']) . ']">' . esc_textarea($value) . '</textarea>';
                        } elseif ($field['type'] === 'rich_text') {
                            wp_editor($value, 'custom_fields_' . esc_attr($field['name']), [
                                'textarea_name' => 'custom_fields[' . esc_attr($field['name']) . ']',
                                'media_buttons' => true,
                                'tinymce'       => true,
                            ]);
                        } elseif ($field['type'] === 'media') {
                            echo '<input type="text" name="custom_fields[' . esc_attr($field['name']) . ']" value="' . esc_attr($value) . '" />';
                        } elseif ($field['type'] === 'date') {
                            echo '<input type="date" name="custom_fields[' . esc_attr($field['name']) . ']" value="' . esc_attr($value) . '" />';
                        } elseif ($field['type'] === 'color') {
                            echo '<input type="color" name="custom_fields[' . esc_attr($field['name']) . ']" value="' . esc_attr($value) . '" />';
                        }
                        echo '</p>';
                    }
                },
                $post_type
            );
        }
    }
}
add_action('add_meta_boxes', 'snn_register_dynamic_metaboxes');

// Save dynamically created metabox data
function snn_save_dynamic_metabox_data($post_id) {
    if (!isset($_POST['custom_fields']) || !is_array($_POST['custom_fields'])) {
        return;
    }

    foreach ($_POST['custom_fields'] as $field_name => $value) {
        update_post_meta($post_id, sanitize_text_field($field_name), sanitize_text_field($value));
    }
}
add_action('save_post', 'snn_save_dynamic_metabox_data');
?>
