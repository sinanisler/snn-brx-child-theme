<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Hook to add submenu page
add_action( 'admin_menu', 'snn_add_custom_post_types_submenu' );

/**
 * Adds a submenu page under 'snn-settings'.
 */
function snn_add_custom_post_types_submenu() {
    add_submenu_page(
        'snn-settings',                    // Parent slug
        'Register Post Types',            // Page title
        'Post Types',                     // Menu title
        'manage_options',                 // Capability
        'snn-custom-post-types',          // Menu slug
        'snn_render_custom_post_types_page' // Callback function
    );
}

/**
 * Renders the Custom Post Types management page.
 */
function snn_render_custom_post_types_page() {
    // Verify user permissions
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Define available supports options
    $available_supports = array(
        'title'           => 'Title',
        'editor'          => 'Editor',
        'thumbnail'       => 'Thumbnail',
        'author'          => 'Author',
        'excerpt'         => 'Excerpt',
        'custom-fields'   => 'Custom Fields',
        'revisions'       => 'Revisions',
        'page-attributes' => 'Page Attributes',
    );

    // Handle form submission
    if ( isset( $_POST['snn_custom_post_types_nonce'] ) && wp_verify_nonce( $_POST['snn_custom_post_types_nonce'], 'snn_save_custom_post_types' ) ) {
        if ( isset( $_POST['custom_post_types'] ) && is_array( $_POST['custom_post_types'] ) ) {
            $custom_post_types = array();

            foreach ( $_POST['custom_post_types'] as $post_type ) {
                if ( ! empty( $post_type['name'] ) && ! empty( $post_type['slug'] ) ) {
                    // Collect supports, defaulting to all if not set
                    $supports = isset( $post_type['supports'] ) && is_array( $post_type['supports'] ) ? array_keys( $post_type['supports'] ) : array_keys( $available_supports );

                    // Sanitize supports values
                    $supports = array_intersect( array_keys( $available_supports ), $supports );

                    $custom_post_types[] = array(
                        'name'     => sanitize_text_field( $post_type['name'] ),
                        'slug'     => sanitize_title( $post_type['slug'] ),
                        'private'  => isset( $post_type['private'] ) ? 1 : 0,
                        'supports' => $supports,
                    );
                }
            }

            update_option( 'snn_custom_post_types', $custom_post_types );

            echo '<div class="updated"><p>Custom Post Types saved successfully.</p></div>';
        }
    }

    // Retrieve existing custom post types
    $custom_post_types = get_option( 'snn_custom_post_types', array() );

    // Ensure each post type has 'supports' as an array
    foreach ( $custom_post_types as &$post_type ) {
        if ( ! isset( $post_type['supports'] ) || ! is_array( $post_type['supports'] ) ) {
            $post_type['supports'] = array_keys( $available_supports );
        }
    }
    unset( $post_type ); // Break reference

    ?>
    <div class="wrap">
        <h1>Manage Custom Post Types</h1>
        <form method="post">
            <?php wp_nonce_field( 'snn_save_custom_post_types', 'snn_custom_post_types_nonce' ); ?>
            <div id="custom-post-type-settings">
                <p>Define custom post types with name, slug, visibility, and supported features:</p>
                <?php foreach ( $custom_post_types as $index => $post_type ) : ?>
                    <div class="custom-post-type-row" data-index="<?php echo esc_attr( $index ); ?>">
                        <div class="buttons">
                            <button type="button" class="move-up" title="Move Up">▲</button>
                            <button type="button" class="move-down" title="Move Down">▼</button>
                            <button type="button" class="remove-post-type" title="Remove Post Type">Remove</button>
                        </div>
                        <label>Post Type Name</label>
                        <input type="text" name="custom_post_types[<?php echo esc_attr( $index ); ?>][name]" placeholder="Post Type Name" value="<?php echo esc_attr( $post_type['name'] ); ?>" />
                        
                        <label>Post Type Slug</label>
                        <input type="text" name="custom_post_types[<?php echo esc_attr( $index ); ?>][slug]" placeholder="post-slug" value="<?php echo esc_attr( $post_type['slug'] ); ?>" />
                        
                        <label>Private</label>
                        <div class="checkbox-container">
                            <input type="checkbox" name="custom_post_types[<?php echo esc_attr( $index ); ?>][private]" <?php checked( $post_type['private'], 1 ); ?> />
                        </div>

                        <!-- Supports Section -->
                        <div class="supports-section">
                            <?php foreach ( $available_supports as $key => $label ) : ?>
                                <label>
                                    <input type="checkbox" name="custom_post_types[<?php echo esc_attr( $index ); ?>][supports][<?php echo esc_attr( $key ); ?>]" <?php checked( in_array( $key, $post_type['supports'], true ), true ); ?> />
                                    <?php echo esc_html( $label ); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <!-- End of Supports Section -->
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-custom-post-type-row" class="button">Add New Post Type</button>
            <br><br>
            <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Custom Post Types"></p>
        </form>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fieldContainer = document.getElementById('custom-post-type-settings');
            const addFieldButton = document.getElementById('add-custom-post-type-row');

            // Define available supports options (must match PHP array)
            const availableSupports = {
                'title': 'Title',
                'editor': 'Editor',
                'thumbnail': 'Thumbnail',
                'author': 'Author',
                'excerpt': 'Excerpt',
                'custom-fields': 'Custom Fields',
                'revisions': 'Revisions',
                'page-attributes': 'Page Attributes'
            };

            /**
             * Updates the index of each custom post type row.
             */
            function updateFieldIndexes() {
                const rows = fieldContainer.querySelectorAll('.custom-post-type-row');
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
             * Generates the HTML for the supports checkboxes.
             */
            function generateSupportsHTML(index) {
                let html = '<div class="supports-section">';
                for (const [key, label] of Object.entries(availableSupports)) {
                    html += `
                        <label>
                            <input type="checkbox" name="custom_post_types[${index}][supports][${key}]" checked />
                            ${label}
                        </label>
                    `;
                }
                html += '</div>';
                return html;
            }

            /**
             * Adds a new custom post type row.
             */
            addFieldButton.addEventListener('click', function() {
                const newIndex = fieldContainer.querySelectorAll('.custom-post-type-row').length;
                const newRow = document.createElement('div');
                newRow.classList.add('custom-post-type-row');
                newRow.dataset.index = newIndex;
                newRow.innerHTML = `
                    <div class="buttons">
                        <button type="button" class="move-up" title="Move Up">▲</button>
                        <button type="button" class="move-down" title="Move Down">▼</button>
                        <button type="button" class="remove-post-type" title="Remove Post Type">Remove</button>
                    </div>
                    <label>Post Type Name</label>
                    <input type="text" name="custom_post_types[${newIndex}][name]" placeholder="Post Type Name" />
                    
                    <label>Post Type Slug</label>
                    <input type="text" name="custom_post_types[${newIndex}][slug]" placeholder="post-slug" />
                    
                    <label>Private</label>
                    <div class="checkbox-container">
                        <input type="checkbox" name="custom_post_types[${newIndex}][private]" />
                    </div>

                    <!-- Supports Section -->
                    ${generateSupportsHTML(newIndex)}
                    <!-- End of Supports Section -->
                `;
                fieldContainer.appendChild(newRow);
            });

            /**
             * Handles removal and reordering of custom post type rows.
             */
            fieldContainer.addEventListener('click', function(event) {
                if (event.target.classList.contains('remove-post-type')) {
                    // Show confirmation dialog
                    if (confirm('Are you sure you want to remove this post type?')) {
                        event.target.closest('.custom-post-type-row').remove();
                        updateFieldIndexes();
                    }
                }

                if (event.target.classList.contains('move-up')) {
                    const row = event.target.closest('.custom-post-type-row');
                    const prevRow = row.previousElementSibling;
                    if (prevRow) {
                        fieldContainer.insertBefore(row, prevRow);
                        updateFieldIndexes();
                    }
                }

                if (event.target.classList.contains('move-down')) {
                    const row = event.target.closest('.custom-post-type-row');
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
            /* Retain original styles */
            .custom-post-type-row {
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
            .custom-post-type-row label { 
                width: auto;
                font-weight: bold;
            }
            .custom-post-type-row input, .custom-post-type-row select { 
                flex: 1;
                min-width: 150px;
                padding: 5px;
                border: 1px solid #ccc;
                border-radius: 3px;
            }
            .custom-post-type-row .buttons {
                flex-direction: column;
                gap: 5px;
            }
            .custom-post-type-row button {
                /* Keep buttons minimal as before */
            }
            .custom-post-type-row button:hover {
                background-color: #005177;
                color: #fff;
            }
            #add-custom-post-type-row {
                margin-top: 10px;
            }
            [type="checkbox"]{
                width:20px !important;
                min-width:20px !important;
            }
            .supports-section {
                width:100%;
                display: flex;
                gap: 10px;
                flex-direction: row-reverse;
            }
            .custom-post-type-row button{
                cursor:pointer;
                border:solid 1px gray;
                padding:4px 10px;
            }
            .custom-post-type-row button:hover{
                background:none;
                color:black;  
                border:solid 1px;
            }
        </style>
    </div>
    <?php
}

/**
 * Registers the custom post types based on saved configurations.
 */
add_action( 'init', 'snn_register_custom_post_types' );

function snn_register_custom_post_types() {
    $custom_post_types = get_option( 'snn_custom_post_types', array() );

    foreach ( $custom_post_types as $post_type ) {
        // Ensure 'supports' is an array
        $supports = isset( $post_type['supports'] ) && is_array( $post_type['supports'] ) ? $post_type['supports'] : array( 'title', 'editor', 'thumbnail','author','excerpt','custom-fields','revisions','page-attributes' );

        // Filter supports to only include allowed options
        $allowed_supports = array(
            'title',
            'editor',
            'thumbnail',
            'author',
            'excerpt',
            'custom-fields',
            'revisions',
            'page-attributes'
        );
        $supports = array_intersect( $supports, $allowed_supports );

        $args = array(
            'label'               => $post_type['name'],
            'public'              => ! (bool) $post_type['private'],
            'has_archive'         => true,
            'supports'            => ! empty( $supports ) ? $supports : array( 'title', 'editor', 'thumbnail','author','excerpt','custom-fields','revisions','page-attributes' ),
            'show_in_rest'        => true,
            'menu_position'       => 20,
            'menu_icon'           => 'dashicons-welcome-write-blog',
            'hierarchical'        => true,
        );

        register_post_type( $post_type['slug'], $args );
    }
}
?>
