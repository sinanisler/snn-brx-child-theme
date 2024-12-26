<?php
function snn_add_block_editor_settings_submenu() {
    add_submenu_page(
        'snn-settings',
        'Block Editor Settings',
        'Block Editor Settings',
        'manage_options',
        'snn-block-editor-settings',
        'snn_block_editor_settings_page_callback'
    );
}
add_action('admin_menu', 'snn_add_block_editor_settings_submenu' , 11);

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

function snn_block_editor_settings_page_callback() {
    $options = get_option('snn_block_editor_settings');
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

            selectAllButton.textContent = allSelected ? 'Unselect All' : 'Select All';
        });
    });
    </script>

    <?php
}

function snn_disable_selected_blocks($allowed_block_types, $post) {
    $options = get_option('snn_block_editor_settings');

    if (!$options || empty($options)) {
        return $allowed_block_types;
    }

    if ($allowed_block_types === true) {
        $allowed_block_types = array_keys(WP_Block_Type_Registry::get_instance()->get_all_registered());
    } elseif (!is_array($allowed_block_types)) {
        $allowed_block_types = [];
    }

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
