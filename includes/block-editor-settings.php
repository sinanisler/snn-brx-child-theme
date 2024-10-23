<?php
// Register the submenu and settings for Block Editor Settings
function snn_add_block_editor_settings_submenu() {
    add_submenu_page(
        'snn-settings', // Parent slug (SNN Settings menu)
        'Block Editor Settings', // Page title
        'Block Editor Settings', // Menu title
        'manage_options', // Capability
        'snn-block-editor-settings', // Menu slug
        'snn_block_editor_settings_page_callback' // Function
    );
}
add_action('admin_menu', 'snn_add_block_editor_settings_submenu');

// Register the settings
function snn_register_block_editor_settings() {
    register_setting('snn_block_editor_settings_group', 'snn_block_editor_settings');

    add_settings_section(
        'snn_block_editor_section',
        'Block Editor Settings',
        null,
        'snn-block-editor-settings'
    );
}
add_action('admin_init', 'snn_register_block_editor_settings');

// Callback function for displaying the settings page
function snn_block_editor_settings_page_callback() {
    // Get saved block settings
    $options = get_option('snn_block_editor_settings');

    // Get all registered blocks
    $all_blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();
    ?>

    <style>
    .form-table th,
    .form-table td {
        padding: 0;
    }
    </style>

    <div class="wrap">
        <h1>Block Editor Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('snn_block_editor_settings_group');
            do_settings_sections('snn-block-editor-settings');
            ?>
            <h2>Disable Core Blocks</h2>
            <p>Select the blocks you want to disable in the Gutenberg editor:</p>

            <!-- Toggle Select All / Unselect All -->
            <button type="button" id="toggle-select-all">Select All</button>

            <table class="form-table">
                <?php foreach ($all_blocks as $block_name => $block_type): ?>
                    <tr>
                        <th scope="row"><?php echo esc_html($block_name); ?></th>
                        <td>
                            <input type="checkbox" name="snn_block_editor_settings[<?php echo esc_attr($block_name); ?>]" 
                            value="1" <?php checked(isset($options[$block_name]), 1); ?> class="block-checkbox">
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>

    <!-- Inline JavaScript for toggle select all functionality -->
    <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function () {
        const selectAllButton = document.getElementById('toggle-select-all');
        const checkboxes = document.querySelectorAll('.block-checkbox');
        let allSelected = false;

        selectAllButton.addEventListener('click', function () {
            allSelected = !allSelected;
            checkboxes.forEach(checkbox => {
                checkbox.checked = allSelected;
            });

            // Change button text based on selection state
            selectAllButton.textContent = allSelected ? 'Unselect All' : 'Select All';
        });
    });
    </script>

    <?php
}

// Disable selected blocks in the editor
function snn_disable_selected_blocks($allowed_block_types, $post) {
    // Get the options for disabled blocks
    $options = get_option('snn_block_editor_settings');

    // If no blocks are disabled, return the original allowed block types
    if (!$options || empty($options)) {
        return $allowed_block_types;
    }

    // If all blocks are allowed (true), get all registered block names
    if ($allowed_block_types === true) {
        $allowed_block_types = array_keys(WP_Block_Type_Registry::get_instance()->get_all_registered());
    } elseif (!is_array($allowed_block_types)) {
        // If $allowed_block_types is not an array, set it to an empty array
        $allowed_block_types = [];
    }

    // Remove the disabled blocks from the allowed block types
    foreach ($options as $block_name => $value) {
        if (isset($options[$block_name]) && $value == 1) {
            $key = array_search($block_name, $allowed_block_types);
            if ($key !== false) {
                unset($allowed_block_types[$key]);
            }
        }
    }

    return $allowed_block_types;
}
add_filter('allowed_block_types_all', 'snn_disable_selected_blocks', 10, 2);


