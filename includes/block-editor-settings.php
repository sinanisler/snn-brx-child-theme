<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function snn_add_block_editor_settings_submenu() {
    add_submenu_page(
        'snn-settings',
        __( 'Block Editor Settings', 'snn' ),
        __( 'Block Editor Settings', 'snn' ),
        'manage_options',
        'snn-block-editor-settings',
        'snn_block_editor_settings_page_callback'
    );
}
add_action( 'admin_menu', 'snn_add_block_editor_settings_submenu' );

function snn_register_block_editor_settings() {
    register_setting( 'snn_block_editor_settings_group', 'snn_block_editor_settings' );

    add_settings_section(
        'snn_block_editor_section',
        __( 'Block Editor Settings', 'snn' ),
        null,
        'snn-block-editor-settings'
    );
}
add_action( 'admin_init', 'snn_register_block_editor_settings' );

function snn_block_editor_settings_page_callback() {
    $options    = get_option( 'snn_block_editor_settings', [] );
    $all_blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();
    ?>
    <style>
    .form-table th,
    .form-table td {
        padding: 0.5em;
    }
    #blocks-list {
        display: none;
        margin-top: 10px;
    }
    </style>

    <div class="wrap">
        <h1><?php _e( 'Block Editor Settings', 'snn' ); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'snn_block_editor_settings_group' );
            do_settings_sections( 'snn-block-editor-settings' );
            ?>

            <!-- Editor Behavior Settings -->
            <h2><?php _e( 'Editor Behavior Settings', 'snn' ); ?></h2>
            <p><?php _e( 'Check any of the following to change the default:', 'snn' ); ?></p>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="snn_disable_fullscreen"><?php _e( 'Disable Fullscreen Mode', 'snn' ); ?></label>
                    </th>
                    <td>
                        <input
                            type="checkbox"
                            id="snn_disable_fullscreen"
                            name="snn_block_editor_settings[disable_fullscreen]"
                            value="1"
                            <?php checked( ! empty( $options['disable_fullscreen'] ) ); ?>
                        />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="snn_disable_welcome_guide"><?php _e( 'Disable Welcome Guide Popup', 'snn' ); ?></label>
                    </th>
                    <td>
                        <input
                            type="checkbox"
                            id="snn_disable_welcome_guide"
                            name="snn_block_editor_settings[disable_welcome_guide]"
                            value="1"
                            <?php checked( ! empty( $options['disable_welcome_guide'] ) ); ?>
                        />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="snn_disable_spotlight_mode"><?php _e( 'Disable Spotlight Mode', 'snn' ); ?></label>
                    </th>
                    <td>
                        <input
                            type="checkbox"
                            id="snn_disable_spotlight_mode"
                            name="snn_block_editor_settings[disable_spotlight_mode]"
                            value="1"
                            <?php checked( ! empty( $options['disable_spotlight_mode'] ) ); ?>
                        />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="snn_enable_top_toolbar"><?php _e( 'Enable Top Toolbar', 'snn' ); ?></label>
                    </th>
                    <td>
                        <input
                            type="checkbox"
                            id="snn_enable_top_toolbar"
                            name="snn_block_editor_settings[enable_top_toolbar]"
                            value="1"
                            <?php checked( ! empty( $options['enable_top_toolbar'] ) ); ?>
                        />
                    </td>
                </tr>
            </table>
            <!-- End Editor Behavior Settings -->

            <h2><?php _e( 'Disable Core Blocks', 'snn' ); ?></h2>
            <p><?php _e( 'Select the blocks you want to disable in the Gutenberg editor:', 'snn' ); ?></p>

            <button type="button" id="toggle-list"><?php _e( 'Show Blocks', 'snn' ); ?></button>

            <div id="blocks-list">
                <button type="button" id="toggle-select-all"><?php _e( 'Select All', 'snn' ); ?></button>
                <table class="form-table">
                    <?php foreach ( $all_blocks as $block_name => $block_type ) : ?>
                        <tr>
                            <th scope="row"><?php echo esc_html( $block_name ); ?></th>
                            <td>
                                <input
                                    type="checkbox"
                                    class="block-checkbox"
                                    name="snn_block_editor_settings[<?php echo esc_attr( $block_name ); ?>]"
                                    value="1"
                                    <?php checked( ! empty( $options[ $block_name ] ) ); ?>
                                />
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <?php submit_button(); ?>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggleListBtn = document.getElementById('toggle-list');
        const blocksList    = document.getElementById('blocks-list');

        toggleListBtn.addEventListener('click', function () {
            const hidden = blocksList.style.display === 'none';
            blocksList.style.display = hidden ? 'block' : 'none';
            toggleListBtn.textContent = hidden ? '<?php echo esc_js( __( 'Hide Blocks', 'snn' ) ); ?>' : '<?php echo esc_js( __( 'Show Blocks', 'snn' ) ); ?>';
        });

        const selectAllBtn = document.getElementById('toggle-select-all');
        const checkboxes   = document.querySelectorAll('.block-checkbox');
        let allSelected    = Array.from(checkboxes).every(cb => cb.checked);

        function updateSelectAllText() {
            selectAllBtn.textContent = allSelected ? '<?php echo esc_js( __( 'Unselect All', 'snn' ) ); ?>' : '<?php echo esc_js( __( 'Select All', 'snn' ) ); ?>';
        }

        selectAllBtn.addEventListener('click', function () {
            allSelected = ! allSelected;
            checkboxes.forEach(cb => cb.checked = allSelected);
            updateSelectAllText();
        });

        checkboxes.forEach(cb => {
            cb.addEventListener('change', function () {
                allSelected = Array.from(checkboxes).every(cb => cb.checked);
                updateSelectAllText();
            });
        });

        updateSelectAllText();
    });
    </script>
    <?php
}

function snn_disable_selected_blocks( $allowed, $ctx ) {
    $registry = WP_Block_Type_Registry::get_instance();
    $all      = array_keys( $registry->get_all_registered() );
    $opts     = (array) get_option( 'snn_block_editor_settings', [] );

    foreach ( $opts as $name => $val ) {
        if ( $val && in_array( $name, $all, true ) ) {
            unset( $all[ array_search( $name, $all, true ) ] );
        }
    }

    $to_keep = [];
    foreach ( $all as $parent ) {
        foreach ( $registry->get_all_registered() as $block ) {
            if ( ! empty( $block->parent ) && in_array( $parent, (array) $block->parent, true ) ) {
                $to_keep[] = $block->name;
            }
        }
    }

    return array_values( array_unique( array_merge( $all, $to_keep ) ) );
}
add_filter( 'allowed_block_types_all', 'snn_disable_selected_blocks', 20, 2 );

function snn_enqueue_block_editor_feature_scripts() {
    $opts  = (array) get_option( 'snn_block_editor_settings', [] );
    $parts = [];

    // Disable Fullscreen Mode
    if ( ! empty( $opts['disable_fullscreen'] ) ) {
        $parts[] = "
            if ( wp.data && wp.data.select( 'core/edit-post' ).isFeatureActive( 'fullscreenMode' ) ) {
                wp.data.dispatch( 'core/edit-post' ).toggleFeature( 'fullscreenMode' );
            }
        ";
    }

    // Disable Welcome Guide
    if ( ! empty( $opts['disable_welcome_guide'] ) ) {
        $parts[] = "
            if ( wp.data && wp.data.select( 'core/edit-post' ).isFeatureActive( 'welcomeGuide' ) ) {
                wp.data.dispatch( 'core/edit-post' ).toggleFeature( 'welcomeGuide' );
            }
        ";
    }

    // Disable Spotlight Mode
    if ( ! empty( $opts['disable_spotlight_mode'] ) ) {
        $parts[] = "
            if ( wp.data && wp.data.select( 'core/edit-post' ).isFeatureActive( 'spotlightMode' ) ) {
                wp.data.dispatch( 'core/edit-post' ).toggleFeature( 'spotlightMode' );
            }
        ";
    }

    // Enable Top Toolbar
    if ( ! empty( $opts['enable_top_toolbar'] ) ) {
        $parts[] = "
            if ( wp.data && ! wp.data.select( 'core/preferences' ).get( 'core', 'fixedToolbar' ) ) {
                wp.data.dispatch( 'core/preferences' ).set( 'core', 'fixedToolbar', true );
            }
        ";
    }

    if ( $parts ) {
        $script = 'window.addEventListener( "load", function() {' . implode( "\n", $parts ) . '} );';
        wp_add_inline_script( 'wp-blocks', $script );
    }
}
add_action( 'enqueue_block_editor_assets', 'snn_enqueue_block_editor_feature_scripts' );
