<?php


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', 'snn_add_taxonomy_submenu' );



function snn_add_taxonomy_submenu() {
    add_submenu_page(
        'snn-settings',
        'Register Taxonomies',
        'Taxonomies',
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

            foreach ( $_POST['taxonomies'] as $taxonomy ) {
                if ( ! empty( $taxonomy['name'] ) && ! empty( $taxonomy['slug'] ) && ! empty( $taxonomy['post_types'] ) ) {
                    $taxonomies[] = array(
                        'name'        => sanitize_text_field( $taxonomy['name'] ),
                        'slug'        => sanitize_title( $taxonomy['slug'] ),
                        'hierarchical'=> isset( $taxonomy['hierarchical'] ) ? 1 : 0,
                        'post_types'  => array_map( 'sanitize_text_field', $taxonomy['post_types'] ),
                    );
                }
            }

            update_option( 'snn_taxonomies', $taxonomies );

            echo '<div class="updated"><p>Taxonomies saved successfully.</p></div>';
        }
    }

    // Retrieve existing taxonomies
    $taxonomies = get_option( 'snn_taxonomies', array() );

    // Retrieve all registered post types for association
    $registered_post_types = get_post_types( array( 'public' => true ), 'objects' );

    ?>
    <div class="wrap">
        <h1>Manage Taxonomies</h1>
        <form method="post">
            <?php wp_nonce_field( 'snn_save_taxonomies', 'snn_taxonomies_nonce' ); ?>
            <div id="taxonomy-settings">
                <p>Define custom taxonomies with name, slug, hierarchical setting, and associated post types:</p>
                <?php foreach ( $taxonomies as $index => $taxonomy ) : ?>
                    <div class="taxonomy-row" data-index="<?php echo esc_attr( $index ); ?>">
                        <div class="buttons">
                            <button type="button" class="move-up" title="Move Up">▲</button>
                            <button type="button" class="move-down" title="Move Down">▼</button>
                            <button type="button" class="remove-taxonomy" title="Remove Taxonomy">Remove</button>
                        </div>
                        <label>Taxonomy Name</label>
                        <input type="text" name="taxonomies[<?php echo esc_attr( $index ); ?>][name]" placeholder="Taxonomy Name" value="<?php echo esc_attr( $taxonomy['name'] ); ?>" />

                        <label>Taxonomy Slug</label>
                        <input type="text" name="taxonomies[<?php echo esc_attr( $index ); ?>][slug]" placeholder="taxonomy-slug" value="<?php echo esc_attr( $taxonomy['slug'] ); ?>" />

                        <label>Hierarchical</label>
                        <div class="checkbox-container">
                            <input type="checkbox" name="taxonomies[<?php echo esc_attr( $index ); ?>][hierarchical]" <?php checked( $taxonomy['hierarchical'], 1 ); ?> />
                        </div>

                        <label>Link Post Types</label>
                        <select name="taxonomies[<?php echo esc_attr( $index ); ?>][post_types][]" multiple>
                            <?php foreach ( $registered_post_types as $post_type ) : ?>
                                <option value="<?php echo esc_attr( $post_type->name ); ?>" <?php echo in_array( $post_type->name, $taxonomy['post_types'] ) ? 'selected' : ''; ?>>
                                    <?php echo esc_html( $post_type->label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-taxonomy-row" class="button">Add New Taxonomy</button>
            <br><br>
            <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Taxonomies"></p>
        </form>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fieldContainer = document.getElementById('taxonomy-settings');
            const addFieldButton = document.getElementById('add-taxonomy-row');


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
                        <button type="button" class="move-up" title="Move Up">▲</button>
                        <button type="button" class="move-down" title="Move Down">▼</button>
                        <button type="button" class="remove-taxonomy" title="Remove Taxonomy">Remove</button>
                    </div>
                    <label>Taxonomy Name</label>
                    <input type="text" name="taxonomies[${newIndex}][name]" placeholder="Taxonomy Name" />

                    <label>Taxonomy Slug</label>
                    <input type="text" name="taxonomies[${newIndex}][slug]" placeholder="taxonomy-slug" />

                    <label>Hierarchical</label>
                    <div class="checkbox-container">
                        <input type="checkbox" name="taxonomies[${newIndex}][hierarchical]" />
                    </div>

                    <label>Associated Post Types</label>
                    <select name="taxonomies[${newIndex}][post_types][]" multiple>
                        <?php
                        // Fetch all public post types for the JavaScript template
                        $post_types = get_post_types( array( 'public' => true ), 'objects' );
                        foreach ( $post_types as $post_type ) :
                            ?>
                            <option value="<?php echo esc_attr( $post_type->name ); ?>"><?php echo esc_html( $post_type->label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                `;
                fieldContainer.appendChild(newRow);
            });

            /**
             * Handles removal and reordering of taxonomy rows.
             */
            fieldContainer.addEventListener('click', function(event) {
                if (event.target.classList.contains('remove-taxonomy')) {
                    event.target.closest('.taxonomy-row').remove();
                    updateFieldIndexes();
                }

                if (event.target.classList.contains('move-up')) {
                    const row = event.target.closest('.taxonomy-row');
                    const prevRow = row.previousElementSibling;
                    if (prevRow) {
                        fieldContainer.insertBefore(row, prevRow);
                        updateFieldIndexes();
                    }
                }

                if (event.target.classList.contains('move-down')) {
                    const row = event.target.closest('.taxonomy-row');
                    const nextRow = row.nextElementSibling;
                    if (nextRow) {
                        fieldContainer.insertBefore(nextRow, row);
                        updateFieldIndexes();
                    }
                }
            });
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
            [type="checkbox"]{
                width:20px !important;
                min-width:20px !important;
            }
            select[multiple] {
                height: 100px;
            }
            .buttons button{
                cursor:pointer;
                border:solid 1px gray;
                padding:4px 10px;
            }
            .buttons button:hover{
                background:white;
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
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array( 'slug' => $taxonomy['slug'] ),
        );

        register_taxonomy( $taxonomy['slug'], $post_types, $args );
    }
}
