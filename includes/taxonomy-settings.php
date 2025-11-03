<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', 'snn_add_taxonomy_submenu' );

function snn_add_taxonomy_submenu() {
    add_submenu_page(
        'snn-settings',
        esc_html__( 'Register Taxonomies', 'snn' ),
        esc_html__( 'Taxonomies', 'snn' ),
        'manage_options',
        'snn-taxonomies',
        'snn_render_taxonomies_page'
    );
}

function snn_render_taxonomies_page() {

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( isset( $_POST['snn_taxonomies_nonce'] ) && wp_verify_nonce( $_POST['snn_taxonomies_nonce'], 'snn_save_taxonomies' ) ) {
        if ( isset( $_POST['taxonomies'] ) && is_array( $_POST['taxonomies'] ) ) {
            $taxonomies = array();
            $slugs_seen = array();

            // Ensure we iterate over ALL submitted taxonomies, regardless of index gaps
            // array_values() re-indexes the array sequentially, fixing deletion/reordering issues
            $submitted_taxonomies = array_values( $_POST['taxonomies'] );

            foreach ( $submitted_taxonomies as $taxonomy ) {
                // Validate required fields
                if ( empty( $taxonomy['name'] ) || empty( $taxonomy['slug'] ) ) {
                    continue; // Skip invalid entries
                }

                // Default to 'post' if no post types are selected (better UX - don't waste user's work)
                if ( ! isset( $taxonomy['post_types'] ) || ! is_array( $taxonomy['post_types'] ) || empty( $taxonomy['post_types'] ) ) {
                    $taxonomy['post_types'] = array( 'post' );
                }

                $sanitized_slug = sanitize_title( $taxonomy['slug'] );

                // Check for duplicate slugs
                if ( in_array( $sanitized_slug, $slugs_seen ) ) {
                    continue; // Skip duplicate slugs
                }

                $slugs_seen[] = $sanitized_slug;

                $taxonomies[] = array(
                    'name'         => sanitize_text_field( $taxonomy['name'] ),
                    'slug'         => $sanitized_slug,
                    'hierarchical' => isset( $taxonomy['hierarchical'] ) ? 1 : 0,
                    'post_types'   => array_map( 'sanitize_text_field', $taxonomy['post_types'] ),
                    'add_columns'  => isset( $taxonomy['add_columns'] ) ? 1 : 0,
                );
            }

            update_option( 'snn_taxonomies', $taxonomies );

            echo '<div class="updated"><p>' . esc_html__( 'Taxonomies saved successfully.', 'snn' ) . '</p></div>';
        }
    }

    // Retrieve existing taxonomies
    $taxonomies = get_option( 'snn_taxonomies', array() );

    // Retrieve all registered post types for association
    $registered_post_types = get_post_types( array( 'public' => true ), 'objects' );
    ?>
    <div class="wrap">
    <h1><?php esc_html_e( 'Manage Taxonomies', 'snn' ); ?></h1>
        <form method="post">
            <?php wp_nonce_field( 'snn_save_taxonomies', 'snn_taxonomies_nonce' ); ?>
            <div id="taxonomy-settings">
                <p><?php esc_html_e( 'Define custom taxonomies with name, slug, hierarchical setting, and associated post types:', 'snn' ); ?></p>
                <?php foreach ( $taxonomies as $index => $taxonomy ) : ?>
                    <div class="taxonomy-row" data-index="<?php echo esc_attr( $index ); ?>">
                        <div class="buttons">
                            <button type="button" class="move-up" title="<?php esc_attr_e( 'Move Up', 'snn' ); ?>">▲</button>
                            <button type="button" class="move-down" title="<?php esc_attr_e( 'Move Down', 'snn' ); ?>">▼</button>
                            <button type="button" class="remove-taxonomy" title="<?php esc_attr_e( 'Remove Taxonomy', 'snn' ); ?>"><?php esc_html_e( 'Remove', 'snn' ); ?></button>
                        </div>
                        <div class="field-group">
                            <label><?php esc_html_e( 'Taxonomy Name', 'snn' ); ?></label><br>
                            <input type="text" name="taxonomies[<?php echo esc_attr( $index ); ?>][name]" placeholder="Taxonomy Name" value="<?php echo esc_attr( $taxonomy['name'] ); ?>" />
                        </div>
                        <div class="field-group">
                            <label><?php esc_html_e( 'Taxonomy Slug', 'snn' ); ?></label><br>
                            <input type="text" class="taxonomy-slug" name="taxonomies[<?php echo esc_attr( $index ); ?>][slug]" placeholder="taxonomy-slug" value="<?php echo esc_attr( $taxonomy['slug'] ); ?>" />
                        </div>
                        <label><?php esc_html_e( 'Hierarchical', 'snn' ); ?></label>
                        <div class="checkbox-container">
                            <input type="checkbox" name="taxonomies[<?php echo esc_attr( $index ); ?>][hierarchical]" <?php checked( $taxonomy['hierarchical'], 1 ); ?> />
                        </div>
                        <label><?php esc_html_e( 'Link Post Types', 'snn' ); ?></label>
                        <select name="taxonomies[<?php echo esc_attr( $index ); ?>][post_types][]" multiple>
                            <?php foreach ( $registered_post_types as $post_type ) : ?>
                                <option value="<?php echo esc_attr( $post_type->name ); ?>" <?php echo in_array( $post_type->name, $taxonomy['post_types'] ) ? 'selected' : ''; ?>>
                                    <?php echo esc_html( $post_type->label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="field-group">
                            <label><?php esc_html_e( 'Show Columns', 'snn' ); ?></label><br>
                            <input type="checkbox" name="taxonomies[<?php echo esc_attr( $index ); ?>][add_columns]" <?php checked( isset( $taxonomy['add_columns'] ) ? $taxonomy['add_columns'] : 0, 1 ); ?> />
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-taxonomy-row" class="button"><?php esc_html_e( 'Add New Taxonomy', 'snn' ); ?></button>
            <br><br>
            <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Taxonomies', 'snn' ); ?>"></p>
        </form>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fieldContainer = document.getElementById('taxonomy-settings');
            const addFieldButton = document.getElementById('add-taxonomy-row');

            // Function to update the name attributes with the correct index
            function updateFieldIndexes() {
                const rows = fieldContainer.querySelectorAll('.taxonomy-row');
                rows.forEach((row, index) => {
                    row.dataset.index = index;
                    const inputs = row.querySelectorAll('input, select');
                    inputs.forEach(input => {
                        if (input.name) {
                            input.name = input.name.replace(/\[\d+\]/, '[' + index + ']');
                        }
                    });
                });
            }

            function sanitizeSlug(value) {
                // Normalize string to decompose accented characters
                value = value.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                // Convert to lowercase
                value = value.toLowerCase();
                // Replace spaces with dashes
                value = value.replace(/\s+/g, "-");
                // Remove disallowed characters (only allow a-z, 0-9, and dashes)
                value = value.replace(/[^a-z0-9\-]/g, "");
                // Remove leading digits
                value = value.replace(/^\d+/, "");
                // Remove multiple consecutive dashes
                value = value.replace(/-+/g, "-");
                // Remove leading and trailing dashes
                value = value.replace(/^-+|-+$/g, "");
                return value;
            }

            // Listen for input events on any slug input (existing or new)
            fieldContainer.addEventListener('input', function(event) {
                if (event.target.classList.contains('taxonomy-slug')) {
                    event.target.value = sanitizeSlug(event.target.value);
                }
            });

            /**
             * Adds a new taxonomy row.
             */
            addFieldButton.addEventListener('click', function() {
                const newIndex = fieldContainer.querySelectorAll('.taxonomy-row').length;
                const newRow = document.createElement('div');
                newRow.classList.add('taxonomy-row');
                newRow.dataset.index = newIndex;
                newRow.innerHTML = `
                    <div class="buttons">
                        <button type="button" class="move-up" title="<?php esc_attr_e( 'Move Up', 'snn' ); ?>">▲</button>
                        <button type="button" class="move-down" title="<?php esc_attr_e( 'Move Down', 'snn' ); ?>">▼</button>
                        <button type="button" class="remove-taxonomy" title="<?php esc_attr_e( 'Remove Taxonomy', 'snn' ); ?>"><?php esc_html_e( 'Remove', 'snn' ); ?></button>
                    </div>
                    <div class="field-group">
                        <label><?php esc_html_e( 'Taxonomy Name', 'snn' ); ?></label><br>
                        <input type="text" name="taxonomies[${newIndex}][name]" placeholder="Taxonomy Name" />
                    </div>
                    <div class="field-group">
                        <label><?php esc_html_e( 'Taxonomy Slug', 'snn' ); ?></label><br>
                        <input type="text" class="taxonomy-slug" name="taxonomies[${newIndex}][slug]" placeholder="taxonomy-slug" />
                    </div>
                    <label><?php esc_html_e( 'Hierarchical', 'snn' ); ?></label>
                    <div class="checkbox-container">
                        <input type="checkbox" name="taxonomies[${newIndex}][hierarchical]" />
                    </div>
                    <label><?php esc_html_e( 'Link Post Types', 'snn' ); ?></label>
                    <select name="taxonomies[${newIndex}][post_types][]" multiple>
                        <?php
                        // Fetch all public post types for the JavaScript template
                        $post_types = get_post_types( array( 'public' => true ), 'objects' );
                        foreach ( $post_types as $post_type ) :
                            ?>
                            <option value="<?php echo esc_attr( $post_type->name ); ?>"><?php echo esc_html( $post_type->label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="field-group">
                        <label><?php esc_html_e( 'Show Columns', 'snn' ); ?></label><br>
                        <input type="checkbox" name="taxonomies[${newIndex}][add_columns]" />
                    </div>
                `;
                fieldContainer.appendChild(newRow);
                updateFieldIndexes(); // Ensure indexes are correct after adding
            });

            /**
             * Handles removal and reordering of taxonomy rows.
             */
            fieldContainer.addEventListener('click', function(event) {
                if (event.target.classList.contains('remove-taxonomy')) {
                    const row = event.target.closest('.taxonomy-row');
                    if (row) {
                        row.remove();
                        updateFieldIndexes(); // Critical: reindex after removal
                    }
                }

                if (event.target.classList.contains('move-up')) {
                    const row = event.target.closest('.taxonomy-row');
                    const prevRow = row.previousElementSibling;
                    if (prevRow) {
                        fieldContainer.insertBefore(row, prevRow);
                        updateFieldIndexes(); // Reindex after reordering
                    }
                }

                if (event.target.classList.contains('move-down')) {
                    const row = event.target.closest('.taxonomy-row');
                    const nextRow = row.nextElementSibling;
                    if (nextRow) {
                        fieldContainer.insertBefore(nextRow, row);
                        updateFieldIndexes(); // Reindex after reordering
                    }
                }
            });

            // Initial index update on page load to ensure consistency
            updateFieldIndexes();
        });
        </script>

        <style>
            .taxonomy-row {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin-bottom: 10px;
                align-items: center;
                padding: 20px;
                border: 1px solid #ccc;
                border-radius: 4px;
                background-color: #f9f9f9;
            }
            .taxonomy-row label { 
                width: auto;
                font-weight: bold;
            }
            .taxonomy-row input, .taxonomy-row select { 
                flex: 1;
                min-width: 150px;
                padding: 5px;
                border: 1px solid #ccc;
                border-radius: 3px;
            }
            .taxonomy-row .buttons {
                flex-direction: column;
                gap: 5px;
            }
            #add-taxonomy-row {
                margin-top: 10px;
            }
            [type="checkbox"] {
                width: 20px !important;
                min-width: 20px !important;
            }
            select[multiple] {
                height: 100px;
            }
            .buttons button {
                cursor: pointer;
                border: solid 1px gray;
                padding: 4px 10px;
            }
            .buttons button:hover {
                background: white;
            }
            .taxonomy-row [type="text"]{
                width:240px;
            }
        </style>
    </div>
    <?php
}

/**
 * Registers the custom taxonomies based on saved configurations.
 */
add_action( 'init', 'snn_register_custom_taxonomies' );

function snn_register_custom_taxonomies() {
    $taxonomies = get_option( 'snn_taxonomies', array() );

    foreach ( $taxonomies as $taxonomy ) {
        // Ensure associated post types exist
        $post_types = array_filter( $taxonomy['post_types'], function( $pt ) {
            return post_type_exists( $pt );
        });

        if ( empty( $post_types ) ) {
            continue; // Skip taxonomy if no valid post types are associated
        }

        $args = array(
            'labels' => array(
                'name'              => $taxonomy['name'],
                'singular_name'     => $taxonomy['name'],
                'search_items'      => 'Search ' . $taxonomy['name'],
                'all_items'         => 'All ' . $taxonomy['name'],
                'parent_item'       => 'Parent ' . $taxonomy['name'],
                'parent_item_colon' => 'Parent ' . $taxonomy['name'] . ':',
                'edit_item'         => 'Edit ' . $taxonomy['name'],
                'update_item'       => 'Update ' . $taxonomy['name'],
                'add_new_item'      => 'Add New ' . $taxonomy['name'],
                'new_item_name'     => 'New ' . $taxonomy['name'] . ' Name',
                'menu_name'         => $taxonomy['name'],
            ),
            'hierarchical'      => (bool) $taxonomy['hierarchical'],
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => ( isset( $taxonomy['add_columns'] ) && $taxonomy['add_columns'] == 1 ) ? true : false,
            'show_in_rest'      => true,
            'rewrite'           => array( 'slug' => $taxonomy['slug'] ),
        );

        register_taxonomy( $taxonomy['slug'], $post_types, $args );
    }
}
?>
