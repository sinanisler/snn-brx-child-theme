<?php
// Add a submenu page for custom fields under the main settings menu  
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

// Display the Custom Fields page with support for reorder functionality
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
                        'repeater' => !empty($field['repeater']) ? 1 : 0, // Add repeater flag
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
                        <div class="custom-field-row" data-index="<?php echo $index; ?>">

                            <div>
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
                            
                            <label>Repeater</label>
                            <input type="checkbox" name="custom_fields[<?php echo $index; ?>][repeater]" <?php checked(!empty($field['repeater'])); ?> <?php echo $field['type'] === 'rich_text' ? 'disabled' : ''; ?> />
                            
                        </div>
                        <?php
                    }
                } else {
                    ?>
                    <div class="custom-field-row" data-index="0">

                        <button type="button" class="move-up">▲</button>
                        <button type="button" class="move-down">▼</button>
                        <button type="button" class="remove-field">Remove</button>



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
                        
                        <label>Post Type</label>
                        <select name="custom_fields[0][post_type]">
                            <?php foreach ($post_types as $post_type) : ?>
                                <option value="<?php echo esc_attr($post_type->name); ?>">
                                    <?php echo esc_html($post_type->label); ?>
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

            // Function to update the index of the fields
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

            // Add new field row
            addFieldButton.addEventListener('click', function() {
                const newIndex = fieldContainer.querySelectorAll('.custom-field-row').length;
                const newRow = document.createElement('div');
                newRow.classList.add('custom-field-row');
                newRow.dataset.index = newIndex;
                newRow.innerHTML = `

                    <button type="button" class="move-up">▲</button>
                    <button type="button" class="move-down">▼</button>
                    <button type="button" class="remove-field">Remove</button>


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
                    <label>Post Type</label>
                    <select name="custom_fields[${newIndex}][post_type]">
                        <?php foreach ($post_types as $post_type) : ?>
                            <option value="<?php echo esc_js($post_type->name); ?>"><?php echo esc_js($post_type->label); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Repeater</label>
                    <input type="checkbox" name="custom_fields[${newIndex}][repeater]" disabled />
                `;
                fieldContainer.appendChild(newRow);
            });

            // Remove a field row
            fieldContainer.addEventListener('click', function(event) {
                if (event.target.classList.contains('remove-field')) {
                    event.target.closest('.custom-field-row').remove();
                    updateFieldIndexes();
                }
            });

            // Move field row up
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

            // Move field row down
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

            // Disable repeater checkbox for rich text fields
            document.addEventListener('change', function(event) {
                if (event.target.classList.contains('field-type-select')) {
                    const row = event.target.closest('.custom-field-row');
                    const repeaterCheckbox = row.querySelector('input[type="checkbox"][name*="[repeater]"]');
                    
                    if (event.target.value === 'rich_text') {
                        repeaterCheckbox.disabled = true;
                        repeaterCheckbox.checked = false; // Uncheck if it was checked
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
            }
            .custom-field-row input, .custom-field-row select { 
            }
            .custom-field-row button {
                margin-left: 5px;
            }






/* Styles for Custom Fields Management Page */
.custom-field-row {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 15px;
    align-items: center;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    background-color: #f9f9f9;
}

.custom-field-row .buttons {
    display: flex;
    flex-direction: column;
    gap: 5px;
    margin-right: 10px;
}

.custom-field-row .buttons button {
    padding: 5px 10px;
    border: none;
    background-color: #0073aa;
    color: #fff;
    border-radius: 3px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.3s ease;
}

.custom-field-row .buttons button:hover {
    background-color: #005177;
}

.custom-field-row label { 
    
    font-weight: bold;
    margin-right: 10px;
    font-size: 14px;
}

.custom-field-row input[type="text"],
.custom-field-row select {
     
    padding: 8px 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 14px;
}

.custom-field-row input[type="checkbox"] {
    transform: scale(1.2);
    margin-left: 5px;
    cursor: pointer;
}

#add-custom-field-row {
    color: #2271b1;
    border-color: #2271b1;
    background: #f6f7f7;
    vertical-align: top;
    padding:5px 20px;
    border:solid 1px;
    cursor:pointer;
    border-radius:3px;
}

#add-custom-field-row:hover {
    background:rgb(242, 242, 242);
}

.submit input[type="submit"] {
    background: #2271b1;
    border-color: #2271b1;
    color: #fff;
    text-decoration: none;
    text-shadow: none;
}

.submit input[type="submit"]:hover {
    background-color: #005177;
}

/* Responsive Design */
@media (max-width: 768px) {
    .custom-field-row {
        flex-direction: column;
        align-items: flex-start;
    }

    .custom-field-row label {
        flex: none;
        margin-bottom: 5px;
    }

    .custom-field-row input[type="text"],
    .custom-field-row select {
        flex: none;
        width: 100%;
    }

    .custom-field-row .buttons {
        flex-direction: row;
        gap: 10px;
    }
}













        </style>
    </div>
<?php
}

// Dynamically register metaboxes with grouped and repeater fields
function snn_register_dynamic_metaboxes() {
    $custom_fields = get_option('snn_custom_fields', []);
    $grouped_fields = [];
    global $snn_repeater_fields_exist;
    $snn_repeater_fields_exist = false;
    global $snn_media_fields_exist;
    $snn_media_fields_exist = false;


    
    foreach ($custom_fields as $field) {
        // Ensure group_name is set, otherwise use 'default'
        $group_name = isset($field['group_name']) ? $field['group_name'] : 'default';
    
        // Ensure post_type is set
        if (isset($field['post_type']) && isset($group_name)) {
            // Ensure the arrays are initialized before setting values
            if (!isset($grouped_fields[$field['post_type']])) {
                $grouped_fields[$field['post_type']] = [];
            }
            if (!isset($grouped_fields[$field['post_type']][$group_name])) {
                $grouped_fields[$field['post_type']][$group_name] = [];
            }
    
            // Add field to the group
            $grouped_fields[$field['post_type']][$group_name][] = $field;
            
            if ($field['type'] === 'media') {
                $snn_media_fields_exist = true;
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
                        $field_name = $field['name'];
                        $field_value = get_post_meta($post->ID, $field_name, true);

                        echo '<div class="custom-field snn-custom-field">';
                        echo '<label>' . esc_html($field_name) . '</label>';

                        if (!empty($field['repeater'])) {
                            $snn_repeater_fields_exist = true;
                            // Repeater Field
                            $values = is_array($field_value) ? $field_value : [''];
                            echo '<div class="repeater-container" data-field-name="' . esc_attr($field_name) . '" data-field-type="' . esc_attr($field['type']) . '">';
                            foreach ($values as $index => $value) {
                                echo '<div class="repeater-item">';
                                snn_render_field_input($field, $value, $index);
                                echo '<button type="button" class="remove-repeater-item">Remove</button>';
                                echo '</div>';
                            }
                            echo '<button type="button" class="add-repeater-item">Add More</button>';
                            echo '</div>';
                        } else {
                            // Single Field
                            snn_render_field_input($field, $field_value);
                        }
                        echo '</div>';
                        ?>

                        <style>
                            /*    SNN Custom Field Editor Styles     */
                            .snn-custom-field {
                                display: grid;
                                grid-template-columns: 1fr;
                                gap: 5px;
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

    // Hook into admin_footer to output JavaScript code if needed
    add_action('admin_footer', 'snn_output_repeater_field_js');
}
add_action('add_meta_boxes', 'snn_register_dynamic_metaboxes');

// Helper function to render field inputs
function snn_render_field_input($field, $value = '', $index = '') {
    $field_name = $field['name'];
    if ($index !== '') {
        $name_attribute = 'custom_fields[' . esc_attr($field_name) . '][' . esc_attr($index) . ']';
    } else {
        $name_attribute = 'custom_fields[' . esc_attr($field_name) . ']';
    }

    switch ($field['type']) {
        case 'text':
            echo '<input type="text" name="' . $name_attribute . '" value="' . esc_attr($value) . '" />';
            break;
        case 'number':
            echo '<input type="number" name="' . $name_attribute . '" value="' . esc_attr($value) . '" />';
            break;
        case 'textarea':
            echo '<textarea name="' . $name_attribute . '">' . esc_textarea($value) . '</textarea>';
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
            echo '<input type="hidden" name="' . $name_attribute . '" value="' . esc_attr($value) . '" class="media-url-field" />';
            if ($value) {
                $image = wp_get_attachment_image_src($value, 'thumbnail');
                if ($image) {
                    echo '<img src="' . esc_url($image[0]) . '" class="media-preview" style="max-width: 100px; max-height: 100px;" />';
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
            echo '<input type="date" name="' . $name_attribute . '" value="' . esc_attr($value) . '" />';
            break;
        case 'color':
            echo '<input type="color" name="' . $name_attribute . '" value="' . esc_attr($value) . '" />';
            break;
        default:
            echo '<input type="text" name="' . $name_attribute . '" value="' . esc_attr($value) . '" />';
            break;
    }
}

// Output JavaScript code for repeater fields and media uploader
function snn_output_repeater_field_js() {
    global $snn_repeater_fields_exist, $snn_media_fields_exist;
    if (!$snn_repeater_fields_exist && !$snn_media_fields_exist) {
        return;
    }

    // Generate templates for each field type
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

    [data-field-type="media"] .media-uploader img{
        aspect-ratio:1;
        object-fit:cover;
    }

    [class="remove-repeater-item"] {
        color: #2271b1;
        background: #f6f7f7;
        vertical-align: top;
        border:solid 1px  #2271b1;
        border-radius:3px;
        cursor:pointer;
        padding:6px 12px;
        margin-top:8px;
    }

    .snn-custom-field{
    margin-bottom:20px;
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
                    repeaterItem.html(templates[fieldType]
                        .replace(/{{field_name}}/g, fieldName)
                        .replace(/{{index}}/g, index));
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

            // Media uploader code
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
                    button.siblings('.media-preview').attr('src', attachment.url).show();
                })
                .open();
            });
        });
    })(jQuery);
    </script>
    <?php
}

// Enqueue media uploader scripts
function snn_enqueue_admin_scripts($hook) {
    global $snn_media_fields_exist;
    if ($snn_media_fields_exist && ('post.php' == $hook || 'post-new.php' == $hook)) {
        wp_enqueue_media();
    }
}
add_action('admin_enqueue_scripts', 'snn_enqueue_admin_scripts');

// Save dynamically created metabox data
function snn_save_dynamic_metabox_data($post_id) {
    if (!isset($_POST['snn_custom_fields_nonce']) || !wp_verify_nonce($_POST['snn_custom_fields_nonce'], 'snn_save_custom_fields')) {
        return;
    }

    if (!isset($_POST['custom_fields']) || !is_array($_POST['custom_fields'])) {
        return;
    }

    $custom_fields = get_option('snn_custom_fields', []);

    foreach ($custom_fields as $field) {
        $field_name = $field['name'];
        if (!empty($field['repeater'])) {
            // Repeater Field
            if (isset($_POST['custom_fields'][$field_name]) && is_array($_POST['custom_fields'][$field_name])) {
                $values = array_map('sanitize_text_field', $_POST['custom_fields'][$field_name]);
                update_post_meta($post_id, $field_name, $values);
            } else {
                delete_post_meta($post_id, $field_name);
            }
        } else {
            // Single Field
            if (isset($_POST['custom_fields'][$field_name])) {
                $value = $_POST['custom_fields'][$field_name];
                if ($field['type'] == 'rich_text') {
                    $value = wp_kses_post($value);
                } else {
                    $value = sanitize_text_field($value);
                }
                update_post_meta($post_id, $field_name, $value);
            } else {
                delete_post_meta($post_id, $field_name);
            }
        }
    }
}
add_action('save_post', 'snn_save_dynamic_metabox_data');
?>