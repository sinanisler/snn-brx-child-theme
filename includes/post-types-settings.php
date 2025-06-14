<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', 'snn_add_custom_post_types_submenu' );

function snn_add_custom_post_types_submenu() {
    add_submenu_page(
        'snn-settings',
        __( 'Register Post Types', 'snn' ),
        __( 'Post Types', 'snn' ),
        'manage_options',
        'snn-custom-post-types',
        'snn_render_custom_post_types_page'
    );
}

function snn_render_custom_post_types_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $available_supports = array(
        'title'           => __( 'Title', 'snn' ),
        'editor'          => __( 'Editor', 'snn' ),
        'thumbnail'       => __( 'Thumbnail', 'snn' ),
        'author'          => __( 'Author', 'snn' ),
        'excerpt'         => __( 'Excerpt', 'snn' ),
        'comments'        => __( 'Comments', 'snn' ),
        'custom-fields'   => __( 'Custom Fields', 'snn' ),
        'revisions'       => __( 'Revisions', 'snn' ),
        'page-attributes' => __( 'Page Attributes', 'snn' ),
    );

    if ( isset( $_POST['snn_custom_post_types_nonce'] ) && wp_verify_nonce( $_POST['snn_custom_post_types_nonce'], 'snn_save_custom_post_types' ) ) {
        if ( isset( $_POST['custom_post_types'] ) && is_array( $_POST['custom_post_types'] ) ) {
            $custom_post_types = array();

            foreach ( $_POST['custom_post_types'] as $post_type ) {
                if ( ! empty( $post_type['name'] ) && ! empty( $post_type['slug'] ) ) {
                    // Collect supports, defaulting to all if not set
                    $supports = isset( $post_type['supports'] ) && is_array( $post_type['supports'] ) ? array_keys( $post_type['supports'] ) : array_keys( $available_supports );

                    // Sanitize supports values to ensure they are valid.
                    $supports = array_intersect( array_keys( $available_supports ), $supports );

                    $custom_post_types[] = array(
                        'name'         => sanitize_text_field( $post_type['name'] ),
                        'slug'         => substr( sanitize_title( $post_type['slug'] ), 0, 20 ), // enforce max-20 char slug
                        'private'      => isset( $post_type['private'] ) ? 1 : 0,
                        'show_in_ui'   => isset( $post_type['show_in_ui'] ) ? 1 : 0, // Save 'Show in UI' setting
                        'show_in_menu' => isset( $post_type['show_in_menu'] ) ? 1 : 0, // Save 'Show in Menu' setting
                        'dashicon'     => sanitize_text_field( $post_type['dashicon'] ),
                        'supports'     => $supports,
                    );
                }
            }

            update_option( 'snn_custom_post_types', $custom_post_types );
            echo '<div class="updated"><p>' . __( 'Custom Post Types saved successfully.', 'snn' ) . '</p></div>';
        } else {
            // If no custom post types are submitted, clear the option.
            update_option( 'snn_custom_post_types', array() );
            echo '<div class="updated"><p>' . __( 'Custom Post Types saved successfully.', 'snn' ) . '</p></div>';
        }
    }

    $custom_post_types = get_option( 'snn_custom_post_types', array() );

    foreach ( $custom_post_types as &$post_type ) {
        if ( ! isset( $post_type['supports'] ) || ! is_array( $post_type['supports'] ) ) {
            $post_type['supports'] = array_keys( $available_supports );
        }
        if ( ! isset( $post_type['dashicon'] ) || empty( $post_type['dashicon'] ) ) {
            $post_type['dashicon'] = 'dashicons-admin-page';
        }
        if ( ! isset( $post_type['show_in_ui'] ) ) {
            $post_type['show_in_ui'] = 1;
        }
        if ( ! isset( $post_type['show_in_menu'] ) ) {
            $post_type['show_in_menu'] = 1;
        }
        $post_type['slug'] = substr( $post_type['slug'], 0, 20 );
    }
    unset( $post_type );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__( 'Manage Custom Post Types', 'snn' ); ?></h1>
        <form method="post">
            <?php wp_nonce_field( 'snn_save_custom_post_types', 'snn_custom_post_types_nonce' ); ?>
            <div id="custom-post-type-settings">
                <p><?php echo esc_html__( 'Define custom post types with name, slug (max 20 chars), visibility, dashicon, and supported features:', 'snn' ); ?></p>
                <?php foreach ( $custom_post_types as $index => $post_type ) : ?>
                    <div class="custom-post-type-row" data-index="<?php echo esc_attr( $index ); ?>">
                        <div class="buttons">
                            <button type="button" class="move-up" title="<?php echo esc_attr__( 'Move Up', 'snn' ); ?>">▲</button>
                            <button type="button" class="move-down" title="<?php echo esc_attr__( 'Move Down', 'snn' ); ?>">▼</button>
                            <button type="button" class="remove-post-type" title="<?php echo esc_attr__( 'Remove Post Type', 'snn' ); ?>"><?php echo esc_html__( 'Remove', 'snn' ); ?></button>
                        </div>
                        <div class="post-type-name">
                            <label><?php echo esc_html__( 'Post Type Name', 'snn' ); ?></label><br>
                            <input type="text" name="custom_post_types[<?php echo esc_attr( $index ); ?>][name]" placeholder="<?php echo esc_attr__( 'Post Type Name', 'snn' ); ?>" value="<?php echo esc_attr( $post_type['name'] ); ?>" />
                        </div>
    
                        <div class="post-type-slug">
                            <label><?php echo esc_html__( 'Post Type Slug (max 20 chars)', 'snn' ); ?></label><br>
                            <input type="text" name="custom_post_types[<?php echo esc_attr( $index ); ?>][slug]" placeholder="<?php echo esc_attr__( 'post-slug', 'snn' ); ?>" value="<?php echo esc_attr( $post_type['slug'] ); ?>" maxlength="20" />
                        </div>

                        <div class="post-type-icon">
                            <label><?php echo esc_html__( 'Dashicon', 'snn' ); ?> </label> <a href="https://developer.wordpress.org/resource/dashicons" target="_blank" style="text-decoration:none"><span class="dashicons dashicons-arrow-up-alt" style="rotate:45deg"></span></a><br>
                            <input type="text" name="custom_post_types[<?php echo esc_attr( $index ); ?>][dashicon]" placeholder="<?php echo esc_attr__( 'dashicons-admin-page', 'snn' ); ?>" value="<?php echo esc_attr( $post_type['dashicon'] ); ?>" style="width:90px" />
                        </div>
                        
                        <div class="checkbox-container">
                            <label><?php echo esc_html__( 'Private', 'snn' ); ?></label>
                            <input type="checkbox" name="custom_post_types[<?php echo esc_attr( $index ); ?>][private]" <?php checked( $post_type['private'], 1 ); ?> />
                        </div>
                        <div class="checkbox-container">
                            <label><?php echo esc_html__( 'Show in UI', 'snn' ); ?></label>
                            <input type="checkbox" name="custom_post_types[<?php echo esc_attr( $index ); ?>][show_in_ui]" <?php checked( $post_type['show_in_ui'], 1 ); ?> />
                        </div>
                        <div class="checkbox-container">
                            <label><?php echo esc_html__( 'Show in Menu', 'snn' ); ?></label>
                            <input type="checkbox" name="custom_post_types[<?php echo esc_attr( $index ); ?>][show_in_menu]" <?php checked( $post_type['show_in_menu'], 1 ); ?> />
                        </div>

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
            <button type="button" id="add-custom-post-type-row" class="button"><?php echo esc_html__( 'Add New Post Type', 'snn' ); ?></button>
            <br><br>
            <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo esc_attr__( 'Save Custom Post Types', 'snn' ); ?>"></p>
        </form>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fieldContainer = document.getElementById('custom-post-type-settings');
            const addFieldButton = document.getElementById('add-custom-post-type-row');

            const availableSupports = {
                'title': '<?php echo esc_js( __( 'Title', 'snn' ) ); ?>',
                'editor': '<?php echo esc_js( __( 'Editor', 'snn' ) ); ?>',
                'thumbnail': '<?php echo esc_js( __( 'Thumbnail', 'snn' ) ); ?>',
                'author': '<?php echo esc_js( __( 'Author', 'snn' ) ); ?>',
                'excerpt': '<?php echo esc_js( __( 'Excerpt', 'snn' ) ); ?>',
                'comments': '<?php echo esc_js( __( 'Comments', 'snn' ) ); ?>',
                'custom-fields': '<?php echo esc_js( __( 'Custom Fields', 'snn' ) ); ?>',
                'revisions': '<?php echo esc_js( __( 'Revisions', 'snn' ) ); ?>',
                'page-attributes': '<?php echo esc_js( __( 'Page Attributes', 'snn' ) ); ?>'
            };

            function slugify(value) {
                value = value.toLowerCase();
                value = value.replace(/\s+/g, '-');
                value = value.replace(/[^a-z0-9\-]/g, '');
                value = value.replace(/-+/g, '-');
                return value.slice(0, 20); // enforce max-20 char slug
            }

            function attachSlugListener(input) {
                input.addEventListener('input', function() {
                    this.value = slugify(this.value);
                });
            }

            document.querySelectorAll('input[name*="[slug]"]').forEach(function(input) {
                input.setAttribute('maxlength', '20');
                attachSlugListener(input);
            });

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
            
            function generateSupportsHTML(index) {
                let html = '<div class="supports-section">';
                for (const [key, label] of Object.entries(availableSupports)) {
                    const isChecked = key !== 'comments'; // Default all to checked except comments
                    html += `
                        <label>
                            <input type="checkbox" name="custom_post_types[${index}][supports][${key}]" ${isChecked ? 'checked' : ''} />
                            ${label}
                        </label>
                    `;
                }
                html += '</div>';
                return html;
            }

            addFieldButton.addEventListener('click', function() {
                const newIndex = fieldContainer.querySelectorAll('.custom-post-type-row').length;
                const newRow = document.createElement('div');
                newRow.classList.add('custom-post-type-row');
                newRow.dataset.index = newIndex;
                newRow.innerHTML = `
                    <div class="buttons">
                        <button type="button" class="move-up" title="<?php echo esc_attr__( 'Move Up', 'snn' ); ?>">▲</button>
                        <button type="button" class="move-down" title="<?php echo esc_attr__( 'Move Down', 'snn' ); ?>">▼</button>
                        <button type="button" class="remove-post-type" title="<?php echo esc_attr__( 'Remove Post Type', 'snn' ); ?>"><?php echo esc_html__( 'Remove', 'snn' ); ?></button>
                    </div>
                    <div class="post-type-name">
                        <label><?php echo esc_html__( 'Post Type Name', 'snn' ); ?></label><br>
                        <input type="text" name="custom_post_types[${newIndex}][name]" placeholder="<?php echo esc_attr__( 'Post Type Name', 'snn' ); ?>" />
                    </div>
                    <div class="post-type-slug">
                        <label><?php echo esc_html__( 'Post Type Slug (max 20 chars)', 'snn' ); ?></label><br>
                        <input type="text" name="custom_post_types[${newIndex}][slug]" placeholder="<?php echo esc_attr__( 'post-slug', 'snn' ); ?>" maxlength="20" />
                    </div>
                    <div class="post-type-icon">
                        <label><?php echo esc_html__( 'Dashicon', 'snn' ); ?> </label> <a href="https://developer.wordpress.org/resource/dashicons" target="_blank" style="text-decoration:none"><span class="dashicons dashicons-arrow-up-alt" style="rotate:45deg"></span></a><br>
                        <input type="text" name="custom_post_types[${newIndex}][dashicon]" placeholder="<?php echo esc_attr__( 'dashicons-admin-page', 'snn' ); ?>" style="width:90px" />
                    </div>
                    <div class="checkbox-container">
                        <label><?php echo esc_html__( 'Private', 'snn' ); ?></label>
                        <input type="checkbox" name="custom_post_types[${newIndex}][private]" />
                    </div>
                    <div class="checkbox-container">
                        <label><?php echo esc_html__( 'Show in UI', 'snn' ); ?></label>
                        <input type="checkbox" name="custom_post_types[${newIndex}][show_in_ui]" checked />
                    </div>
                    <div class="checkbox-container">
                        <label><?php echo esc_html__( 'Show in Menu', 'snn' ); ?></label>
                        <input type="checkbox" name="custom_post_types[${newIndex}][show_in_menu]" checked />
                    </div>
                    <!-- Supports Section -->
                    ${generateSupportsHTML(newIndex)}
                    <!-- End of Supports Section -->
                `;
                fieldContainer.appendChild(newRow);

                const newSlugInput = newRow.querySelector('input[name*="[slug]"]');
                if (newSlugInput) {
                    attachSlugListener(newSlugInput);
                }
            });

            fieldContainer.addEventListener('click', function(event) {
                if (event.target.classList.contains('remove-post-type')) {
                    if (confirm('<?php echo esc_js( __( 'Are you sure you want to remove this post type?', 'snn' ) ); ?>')) {
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
            .custom-post-type-row .checkbox-container {
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            .custom-post-type-row label {
                width: auto;
                font-weight: bold;
                display: block;
                margin-bottom: 5px;
            }
            .custom-post-type-row input, .custom-post-type-row select {
                flex: 1;
                width: 300px;
                padding: 5px;
                border: 1px solid #ccc;
                border-radius: 3px;
            }
            .custom-post-type-row .buttons {
                flex-direction: column;
                gap: 5px;
            }
            .custom-post-type-row button {
                cursor:pointer;
                border:solid 1px gray;
                padding:4px 10px;
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
                flex-wrap: wrap;
                padding-left:155px; /* Adjust based on button width */
            }
            .supports-section label {
                font-weight: normal;
                display: flex;
                align-items: center;
                gap: 5px;
            }

            @media(max-width:768px){ 
                .supports-section { padding-left:0; } 
                .custom-post-type-row {
                    flex-direction: column;
                    align-items: flex-start;
                }
                .custom-post-type-row > div {
                    width: 100%;
                }
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

add_action( 'init', 'snn_register_custom_post_types' );

function snn_register_custom_post_types() {
    $custom_post_types = get_option( 'snn_custom_post_types', array() );

    foreach ( $custom_post_types as $post_type ) {
        $default_supports = array( 'title', 'editor', 'thumbnail', 'author', 'excerpt', 'comments', 'custom-fields', 'revisions', 'page-attributes' );
        $supports = isset( $post_type['supports'] ) && is_array( $post_type['supports'] ) ? $post_type['supports'] : $default_supports;

        $allowed_supports = array(
            'title', 'editor', 'thumbnail', 'author', 'excerpt', 'comments',
            'custom-fields', 'revisions', 'page-attributes'
        );
        $supports = array_intersect( $supports, $allowed_supports );

        $args = array(
            'label'         => $post_type['name'],
            'public'        => ! (bool) $post_type['private'],
            'show_ui'       => (bool) ( $post_type['show_in_ui'] ?? 1 ),   // Default to true if not set
            'show_in_menu'  => (bool) ( $post_type['show_in_menu'] ?? 1 ), // Default to true if not set
            'has_archive'   => true,
            'supports'      => ! empty( $supports ) ? $supports : $default_supports,
            'show_in_rest'  => true,
            'menu_position' => 20,
            'menu_icon'     => ! empty( $post_type['dashicon'] ) ? $post_type['dashicon'] : 'dashicons-admin-page',
            'hierarchical'  => true,
        );

        register_post_type( substr( $post_type['slug'], 0, 20 ), $args );
    }
}
