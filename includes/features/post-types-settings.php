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
        'notes'           => __( 'Notes', 'snn' ),
        'thumbnail'       => __( 'Thumbnail', 'snn' ),
        'author'          => __( 'Author', 'snn' ),
        'excerpt'         => __( 'Excerpt', 'snn' ),
        'comments'        => __( 'Comments', 'snn' ),
        'custom-fields'   => __( 'Custom Fields', 'snn' ),
        'revisions'       => __( 'Revisions', 'snn' ),
        'page-attributes' => __( 'Page Attributes', 'snn' ),
    );

    $additional_options = array(
        'show_in_menu'      => __( 'Show in Menu', 'snn' ),
        'show_ui'           => __( 'Show UI', 'snn' ),
        'show_in_nav_menus' => __( 'Show in Nav Menus', 'snn' ),
        'show_order'        => __( 'Show Order', 'snn' ),
        'has_archive'       => __( 'Has Archive', 'snn' ),
        'show_in_rest'      => __( 'Show in REST', 'snn' ),
        'hierarchical'      => __( 'Hierarchical', 'snn' ),
    );

    if ( isset( $_POST['snn_custom_post_types_nonce'] ) && wp_verify_nonce( $_POST['snn_custom_post_types_nonce'], 'snn_save_custom_post_types' ) ) {
        if ( isset( $_POST['custom_post_types'] ) && is_array( $_POST['custom_post_types'] ) ) {
            $custom_post_types = array();

            foreach ( $_POST['custom_post_types'] as $post_type ) {
                if ( ! empty( $post_type['name'] ) && ! empty( $post_type['slug'] ) ) {
                    $supports = isset( $post_type['supports'] ) && is_array( $post_type['supports'] ) ? array_keys( $post_type['supports'] ) : array_keys( $available_supports );
                    $supports = array_intersect( array_keys( $available_supports ), $supports );

                    $show_in_menu      = isset( $post_type['show_in_menu'] ) ? 1 : 0;
                    $show_ui           = isset( $post_type['show_ui'] ) ? 1 : 0;
                    $show_in_nav_menus = isset( $post_type['show_in_nav_menus'] ) ? 1 : 0;
                    $show_order        = isset( $post_type['show_order'] ) ? 1 : 0;
                    $has_archive       = isset( $post_type['has_archive'] ) ? 1 : 0;
                    $show_in_rest      = isset( $post_type['show_in_rest'] ) ? 1 : 0;
                    $hierarchical      = isset( $post_type['hierarchical'] ) ? 1 : 0;
                    $private           = isset( $post_type['private'] ) ? 1 : 0;

                    $custom_post_types[] = array(
                        'name'               => sanitize_text_field( $post_type['name'] ),
                        'slug'               => substr( sanitize_title( $post_type['slug'] ), 0, 20 ),
                        'private'            => $private,
                        'dashicon'           => sanitize_text_field( $post_type['dashicon'] ),
                        'supports'           => $supports,
                        'show_in_menu'       => $show_in_menu,
                        'show_ui'            => $show_ui,
                        'show_in_nav_menus'  => $show_in_nav_menus,
                        'show_order'         => $show_order,
                        'has_archive'        => $has_archive,
                        'show_in_rest'       => $show_in_rest,
                        'hierarchical'       => $hierarchical,
                    );
                }
            }

            update_option( 'snn_custom_post_types', $custom_post_types );
            echo '<div class="updated"><p>' . __( 'Custom Post Types saved successfully.', 'snn' ) . '</p></div>';
        } else {
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
        $post_type['slug'] = substr( $post_type['slug'], 0, 20 );
        // Always default to enabled for missing/empty new fields
        $post_type['show_in_menu']      = ( isset( $post_type['show_in_menu'] ) && $post_type['show_in_menu'] !== '' ) ? $post_type['show_in_menu'] : 1;
        $post_type['show_ui']           = ( isset( $post_type['show_ui'] ) && $post_type['show_ui'] !== '' ) ? $post_type['show_ui'] : 1;
        $post_type['show_in_nav_menus'] = ( isset( $post_type['show_in_nav_menus'] ) && $post_type['show_in_nav_menus'] !== '' ) ? $post_type['show_in_nav_menus'] : 1;
        $post_type['show_order']        = isset( $post_type['show_order'] ) ? $post_type['show_order'] : 0;
        $post_type['has_archive']       = ( isset( $post_type['has_archive'] ) && $post_type['has_archive'] !== '' ) ? $post_type['has_archive'] : 1;
        $post_type['show_in_rest']      = ( isset( $post_type['show_in_rest'] ) && $post_type['show_in_rest'] !== '' ) ? $post_type['show_in_rest'] : 1;
        $post_type['hierarchical']      = ( isset( $post_type['hierarchical'] ) && $post_type['hierarchical'] !== '' ) ? $post_type['hierarchical'] : 1;
        $post_type['private']           = isset( $post_type['private'] ) ? $post_type['private'] : 0;
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
                            <label><?php echo esc_html__( 'Dashicon', 'snn' ); ?></label><br>
                            <input type="hidden" name="custom_post_types[<?php echo esc_attr( $index ); ?>][dashicon]" value="<?php echo esc_attr( $post_type['dashicon'] ); ?>" class="dashicon-value-input" />
                            <button type="button" class="dashicon-picker-btn" title="<?php echo esc_attr__( 'Click to pick icon', 'snn' ); ?>">
                                <span class="dashicons <?php echo esc_attr( $post_type['dashicon'] ); ?>"></span>
                                <span class="dashicon-btn-label"><?php echo esc_html( str_replace( 'dashicons-', '', $post_type['dashicon'] ) ); ?></span>
                            </button>
                        </div>
                        <!-- Supports Section -->
                        <div class="advanced-settings-wrap">
                            <button type="button" class="toggle-advanced-btn">Advanced Settings ▼</button>
                            <div class="supports-section" style="display:none;">
                                <?php foreach ( $available_supports as $key => $label ) : ?>
                                    <label>
                                        <input type="checkbox" name="custom_post_types[<?php echo esc_attr( $index ); ?>][supports][<?php echo esc_attr( $key ); ?>]" <?php checked( in_array( $key, $post_type['supports'], true ), true ); ?> />
                                        <?php echo esc_html( $label ); ?>
                                    </label>
                                <?php endforeach; ?>

                                <?php foreach ( $additional_options as $opt_key => $opt_label ) : ?>
                                    <label>
                                        <input type="checkbox" name="custom_post_types[<?php echo esc_attr( $index ); ?>][<?php echo esc_attr( $opt_key ); ?>]" <?php checked( $opt_key === 'show_order' ? (isset($post_type[$opt_key]) && $post_type[$opt_key]) : (!isset($post_type[$opt_key]) || $post_type[$opt_key]), 1 ); ?> />
                                        <?php echo esc_html( $opt_label ); ?>
                                    </label>
                                <?php endforeach; ?>
                                <label>
                                    <input type="checkbox" name="custom_post_types[<?php echo esc_attr( $index ); ?>][private]" <?php checked( isset($post_type['private']) && $post_type['private'], 1 ); ?> />
                                    <?php echo esc_html__( 'Private', 'snn' ); ?>
                                </label>
                            </div>
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
                'notes': '<?php echo esc_js( __( 'Notes', 'snn' ) ); ?>',
                'thumbnail': '<?php echo esc_js( __( 'Thumbnail', 'snn' ) ); ?>',
                'author': '<?php echo esc_js( __( 'Author', 'snn' ) ); ?>',
                'excerpt': '<?php echo esc_js( __( 'Excerpt', 'snn' ) ); ?>',
                'comments': '<?php echo esc_js( __( 'Comments', 'snn' ) ); ?>',
                'custom-fields': '<?php echo esc_js( __( 'Custom Fields', 'snn' ) ); ?>',
                'revisions': '<?php echo esc_js( __( 'Revisions', 'snn' ) ); ?>',
                'page-attributes': '<?php echo esc_js( __( 'Page Attributes', 'snn' ) ); ?>'
            };

            const additionalOptions = {
                'show_in_menu': '<?php echo esc_js( __( 'Show in Menu', 'snn' ) ); ?>',
                'show_ui': '<?php echo esc_js( __( 'Show UI', 'snn' ) ); ?>',
                'show_in_nav_menus': '<?php echo esc_js( __( 'Show in Nav Menus', 'snn' ) ); ?>',
                'show_order': '<?php echo esc_js( __( 'Show Order', 'snn' ) ); ?>',
                'has_archive': '<?php echo esc_js( __( 'Has Archive', 'snn' ) ); ?>',
                'show_in_rest': '<?php echo esc_js( __( 'Show in REST', 'snn' ) ); ?>',
                'hierarchical': '<?php echo esc_js( __( 'Hierarchical', 'snn' ) ); ?>'
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
                input.setAttribute('maxlength', '20'); // ensure existing inputs observe max length
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
                let inner = '';
                for (const [key, label] of Object.entries(availableSupports)) {
                    const isChecked = key !== 'comments';
                    inner += `
                        <label>
                            <input type="checkbox" name="custom_post_types[${index}][supports][${key}]" ${isChecked ? 'checked' : ''} />
                            ${label}
                        </label>
                    `;
                    if (key === 'page-attributes') {
                        for (const [optKey, optLabel] of Object.entries(additionalOptions)) {
                            const isChecked = optKey !== 'show_order';
                            inner += `
                                <label>
                                    <input type="checkbox" name="custom_post_types[${index}][${optKey}]" ${isChecked ? 'checked' : ''} />
                                    ${optLabel}
                                </label>
                            `;
                        }
                        inner += `
                            <label>
                                <input type="checkbox" name="custom_post_types[${index}][private]" />
                                <?php echo esc_html__( 'Private', 'snn' ); ?>
                            </label>
                        `;
                    }
                }
                return `<div class="advanced-settings-wrap">
                    <button type="button" class="toggle-advanced-btn">Advanced Settings ▼</button>
                    <div class="supports-section" style="display:none;">${inner}</div>
                </div>`;
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
                        <label><?php echo esc_html__( 'Dashicon', 'snn' ); ?></label><br>
                        <input type="hidden" name="custom_post_types[${newIndex}][dashicon]" value="dashicons-admin-page" class="dashicon-value-input" />
                        <button type="button" class="dashicon-picker-btn" title="<?php echo esc_attr__( 'Click to pick icon', 'snn' ); ?>">
                            <span class="dashicons dashicons-admin-page"></span>
                            <span class="dashicon-btn-label">admin-page</span>
                        </button>
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

                if (event.target.classList.contains('toggle-advanced-btn')) {
                    const section = event.target.nextElementSibling;
                    const isHidden = section.style.display === 'none';
                    section.style.display = isHidden ? 'flex' : 'none';
                    event.target.textContent = isHidden ? 'Advanced Settings ▲' : 'Advanced Settings ▼';
                }
            });
        });
        </script>
        <!-- Dashicon Picker Modal -->
        <div id="dashicon-picker-modal" style="display:none;">
            <div class="dashicon-picker-backdrop"></div>
            <div class="dashicon-picker-dialog">
                <div class="dashicon-picker-header">
                    <input type="text" id="dashicon-search" placeholder="<?php echo esc_attr__( 'Search icons…', 'snn' ); ?>" autocomplete="off" />
                    <button type="button" id="dashicon-picker-close">&#x2715;</button>
                </div>
                <div class="dashicon-picker-grid" id="dashicon-picker-grid"></div>
            </div>
        </div>

        <script>
        (function() {
            const allDashicons = [
                'dashicons-admin-appearance','dashicons-admin-collapse','dashicons-admin-comments',
                'dashicons-admin-customizer','dashicons-admin-generic','dashicons-admin-home',
                'dashicons-admin-links','dashicons-admin-media','dashicons-admin-multisite',
                'dashicons-admin-network','dashicons-admin-page','dashicons-admin-plugins',
                'dashicons-admin-post','dashicons-admin-settings','dashicons-admin-site',
                'dashicons-admin-site-alt','dashicons-admin-site-alt2','dashicons-admin-site-alt3',
                'dashicons-admin-tools','dashicons-admin-users',
                'dashicons-dismiss','dashicons-info','dashicons-no','dashicons-no-alt','dashicons-warning',
                'dashicons-arrow-down','dashicons-arrow-down-alt','dashicons-arrow-down-alt2',
                'dashicons-arrow-left','dashicons-arrow-left-alt','dashicons-arrow-left-alt2',
                'dashicons-arrow-right','dashicons-arrow-right-alt','dashicons-arrow-right-alt2',
                'dashicons-arrow-up','dashicons-arrow-up-alt','dashicons-arrow-up-alt2',
                'dashicons-controls-back','dashicons-controls-forward','dashicons-controls-pause',
                'dashicons-controls-play','dashicons-controls-repeat','dashicons-controls-skipback',
                'dashicons-controls-skipforward','dashicons-controls-stop',
                'dashicons-controls-volumeoff','dashicons-controls-volumeon',
                'dashicons-awards','dashicons-medal','dashicons-ribbon',
                'dashicons-star-empty','dashicons-star-filled','dashicons-star-half',
                'dashicons-businessman','dashicons-businessperson','dashicons-businesswoman',
                'dashicons-groups','dashicons-nametag','dashicons-id','dashicons-id-alt',
                'dashicons-building','dashicons-home','dashicons-location','dashicons-location-alt',
                'dashicons-megaphone','dashicons-store','dashicons-bank','dashicons-palmtree',
                'dashicons-cart','dashicons-money','dashicons-money-alt','dashicons-products',
                'dashicons-tickets','dashicons-tickets-alt',
                'dashicons-book','dashicons-book-alt','dashicons-format-aside','dashicons-format-audio',
                'dashicons-format-chat','dashicons-format-gallery','dashicons-format-image',
                'dashicons-format-links','dashicons-format-quote','dashicons-format-standard',
                'dashicons-format-status','dashicons-format-video','dashicons-text','dashicons-text-page',
                'dashicons-album','dashicons-camera','dashicons-camera-alt','dashicons-images-alt',
                'dashicons-images-alt2','dashicons-media-archive','dashicons-media-audio',
                'dashicons-media-code','dashicons-media-default','dashicons-media-document',
                'dashicons-media-interactive','dashicons-media-spreadsheet','dashicons-media-text',
                'dashicons-media-video','dashicons-playlist-audio','dashicons-playlist-video',
                'dashicons-video-alt','dashicons-video-alt2','dashicons-video-alt3',
                'dashicons-email','dashicons-email-alt','dashicons-email-alt2',
                'dashicons-facebook','dashicons-facebook-alt','dashicons-instagram',
                'dashicons-linkedin','dashicons-pinterest','dashicons-reddit','dashicons-rss',
                'dashicons-share','dashicons-share-alt','dashicons-share-alt2',
                'dashicons-twitch','dashicons-twitter','dashicons-twitter-alt',
                'dashicons-xing','dashicons-youtube','dashicons-google',
                'dashicons-wordpress','dashicons-wordpress-alt',
                'dashicons-analytics','dashicons-art','dashicons-backup','dashicons-calendar',
                'dashicons-calendar-alt','dashicons-chart-area','dashicons-chart-bar',
                'dashicons-chart-line','dashicons-chart-pie','dashicons-clipboard','dashicons-clock',
                'dashicons-cloud','dashicons-cloud-saved','dashicons-cloud-upload','dashicons-coffee',
                'dashicons-color-picker','dashicons-columns','dashicons-dashboard',
                'dashicons-database','dashicons-database-add','dashicons-database-export',
                'dashicons-database-import','dashicons-database-remove','dashicons-database-view',
                'dashicons-desktop','dashicons-download','dashicons-edit','dashicons-edit-large',
                'dashicons-edit-page','dashicons-ellipsis','dashicons-embed-audio',
                'dashicons-embed-generic','dashicons-embed-photo','dashicons-embed-post',
                'dashicons-embed-video','dashicons-excerpt-view','dashicons-exit',
                'dashicons-external','dashicons-feedback','dashicons-filter','dashicons-flag',
                'dashicons-food','dashicons-games','dashicons-gear','dashicons-gifts',
                'dashicons-hammer','dashicons-heart','dashicons-hidden','dashicons-hobbies',
                'dashicons-image-crop','dashicons-image-filter','dashicons-image-flip-horizontal',
                'dashicons-image-flip-vertical','dashicons-image-rotate','dashicons-image-rotate-left',
                'dashicons-image-rotate-right','dashicons-index-card','dashicons-keyboard-hide',
                'dashicons-laptop','dashicons-layout','dashicons-leftright','dashicons-lightbulb',
                'dashicons-list-view','dashicons-lock','dashicons-marker','dashicons-menu',
                'dashicons-menu-alt','dashicons-menu-alt2','dashicons-menu-alt3','dashicons-microphone',
                'dashicons-migrate','dashicons-minus','dashicons-move','dashicons-music',
                'dashicons-networking','dashicons-open-folder','dashicons-paperclip','dashicons-pdf',
                'dashicons-performance','dashicons-pets','dashicons-phone','dashicons-plus',
                'dashicons-plus-alt','dashicons-plus-alt2','dashicons-portfolio','dashicons-post-status',
                'dashicons-pressthis','dashicons-printer','dashicons-privacy','dashicons-randomize',
                'dashicons-remove','dashicons-rest-api','dashicons-reusable-block','dashicons-saved',
                'dashicons-screenoptions','dashicons-search','dashicons-shield','dashicons-shield-alt',
                'dashicons-shortcode','dashicons-slides','dashicons-smartphone','dashicons-smiley',
                'dashicons-sort','dashicons-sos','dashicons-speaker','dashicons-superhero',
                'dashicons-superhero-alt','dashicons-tablet','dashicons-tag','dashicons-tagcloud',
                'dashicons-thumbs-down','dashicons-thumbs-up','dashicons-tide','dashicons-totop',
                'dashicons-translation','dashicons-trash','dashicons-trophy','dashicons-undo',
                'dashicons-unlock','dashicons-update','dashicons-update-alt','dashicons-upload',
                'dashicons-vault','dashicons-visibility','dashicons-woo','dashicons-woo-alt',
                'dashicons-yes','dashicons-yes-alt',
                'dashicons-welcome-add-page','dashicons-welcome-comments','dashicons-welcome-learn-more',
                'dashicons-welcome-view-site','dashicons-welcome-widgets-menus','dashicons-welcome-write-blog',
                'dashicons-buddicons-activity','dashicons-buddicons-bbpress-logo',
                'dashicons-buddicons-community','dashicons-buddicons-forums','dashicons-buddicons-friends',
                'dashicons-buddicons-groups','dashicons-buddicons-pm','dashicons-buddicons-replies',
                'dashicons-buddicons-topics','dashicons-buddicons-tracking',
                'dashicons-grid-view','dashicons-html','dashicons-layout','dashicons-nametag',
                'dashicons-paperclip','dashicons-privacy','dashicons-shortcode','dashicons-slides',
            ];

            const modal   = document.getElementById('dashicon-picker-modal');
            const grid    = document.getElementById('dashicon-picker-grid');
            const search  = document.getElementById('dashicon-search');
            let currentInput = null;

            function renderIcons(q) {
                q = (q || '').toLowerCase().replace(/^dashicons-/, '');
                grid.innerHTML = '';
                const current = currentInput ? currentInput.value : '';
                allDashicons.forEach(function(icon) {
                    if (q && icon.indexOf(q) === -1) return;
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'dashicon-item' + (icon === current ? ' selected' : '');
                    btn.title = icon;
                    btn.innerHTML =
                        '<span class="dashicons ' + icon + '"></span>' +
                        '<span class="dashicon-item-label">' + icon.replace('dashicons-', '') + '</span>';
                    btn.addEventListener('click', function() { selectIcon(icon); });
                    grid.appendChild(btn);
                });
            }

            function selectIcon(icon) {
                if (currentInput) {
                    currentInput.value = icon;
                    const pickerBtn = currentInput.nextElementSibling;
                    pickerBtn.querySelector('.dashicons').className = 'dashicons ' + icon;
                    pickerBtn.querySelector('.dashicon-btn-label').textContent = icon.replace('dashicons-', '');
                }
                closePicker();
            }

            function openPicker(input) {
                currentInput = input;
                search.value = '';
                renderIcons('');
                modal.style.display = 'flex';
                search.focus();
                setTimeout(function() {
                    const sel = grid.querySelector('.selected');
                    if (sel) sel.scrollIntoView({ block: 'nearest' });
                }, 30);
            }

            function closePicker() {
                modal.style.display = 'none';
                currentInput = null;
            }

            search.addEventListener('input', function() { renderIcons(this.value); });
            document.getElementById('dashicon-picker-close').addEventListener('click', closePicker);
            modal.querySelector('.dashicon-picker-backdrop').addEventListener('click', closePicker);
            modal.addEventListener('keydown', function(e) { if (e.key === 'Escape') closePicker(); });

            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.dashicon-picker-btn');
                if (btn) {
                    const input = btn.closest('.post-type-icon').querySelector('.dashicon-value-input');
                    openPicker(input);
                }
            });
        })();
        </script>

        <style>
            .custom-post-type-row {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin-bottom: 10px;
                align-items: center;
                padding: 20px;
                border: 1px solid #e2e2e2;
                border-radius: 10px;
                background-color: #ffffff;
            }
            .custom-post-type-row label {
                width: auto;
                font-weight: bold;
                min-width:200px
            }
            .custom-post-type-row input[type="text"] {
                flex: 1;
                width: 300px;
                padding: 5px;
                background: #ffffff;
                border-radius: 5px;
                height: 40px;
                border: solid 1px #e2e2e2;
            }
            .custom-post-type-row input[type="text"]:hover {
                border: solid 1px #000000;
            }
            .custom-post-type-row .buttons {
                display: flex;
                flex-direction: row;
                gap: 5px;
                position: relative;
                top:10px;
            }
            .custom-post-type-row .buttons button {
                background: #ffffff;
                border-radius: 5px;
                padding: 10px;
                border: solid 1px #e2e2e2;
                height: 40px;
                cursor: pointer;
            }
            .custom-post-type-row .buttons button:hover {
                background: var(--wp-admin-theme-color);
                color: white;
            }
            #add-custom-post-type-row {
                margin-top: 10px;
            }
            .custom-post-type-row [type="checkbox"] {
                appearance: none;
                -webkit-appearance: none;
                cursor: pointer;
                position: relative;
                width: 40px;
                height: 22px;
                background-color: #eeeeee;
                border-radius: 11px;
                border: 1px solid #e2e2e2;
                transition: 0.25s;
                margin-top: 2px;
                min-width: 40px;
            }
            .custom-post-type-row [type="checkbox"]::before {
                content: "";
                position: absolute;
                top: 2px;
                left: 3px;
                width: 16px;
                height: 16px;
                background-color: #ffffff;
                border-radius: 50%;
                transition: 0.25s;
            }
            .custom-post-type-row [type="checkbox"]:hover {
                border: 2px solid var(--wp-admin-theme-color);
            }
            .custom-post-type-row [type="checkbox"]:checked {
                background-color: #eeeeee;
                border: 2px solid var(--wp-admin-theme-color);
            }
            .custom-post-type-row [type="checkbox"]:checked::before {
                left: 21px;
                background-color: var(--wp-admin-theme-color);
            }
            .advanced-settings-wrap {
                width: 100%;
                padding-left: 155px;
            }
            @media(max-width:768px) { .advanced-settings-wrap { padding-left: 0; } }
            .toggle-advanced-btn {
                background: none;
                border: none;
                padding: 0;
                cursor: pointer;
                font-size: 13px;
                color: #666;
                margin-bottom: 8px;
            }
            .toggle-advanced-btn:hover {
                color: #000;
            }
            .supports-section {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }
            /* Dashicon picker button */
            .post-type-icon .dashicon-picker-btn {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 0 12px;
                background: #ffffff;
                border: 1px solid #e2e2e2;
                border-radius: 5px;
                cursor: pointer;
                height: 40px;
                min-width: 160px;
                max-width: 220px;
                font-size: 13px;
                transition: border-color 0.15s;
            }
            .post-type-icon .dashicon-picker-btn:hover {
                border-color: #000;
            }
            .post-type-icon .dashicon-picker-btn .dashicons {
                font-size: 20px;
                width: 20px;
                height: 20px;
                flex-shrink: 0;
            }
            .dashicon-btn-label {
                font-size: 11px;
                color: #555;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            /* Modal */
            #dashicon-picker-modal {
                position: fixed;
                inset: 0;
                z-index: 999999;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .dashicon-picker-backdrop {
                position: absolute;
                inset: 0;
                background: rgba(0,0,0,0.55);
            }
            .dashicon-picker-dialog {
                position: relative;
                background: #fff;
                border-radius: 8px;
                width: 660px;
                max-width: 95vw;
                max-height: 82vh;
                display: flex;
                flex-direction: column;
                box-shadow: 0 12px 48px rgba(0,0,0,0.25);
            }
            .dashicon-picker-header {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 14px 16px;
                border-bottom: 1px solid #e2e2e2;
                flex-shrink: 0;
            }
            #dashicon-search {
                flex: 1;
                height: 36px;
                padding: 0 10px;
                border: 1px solid #e2e2e2;
                border-radius: 5px;
                font-size: 13px;
                background: #f9f9f9;
            }
            #dashicon-search:focus {
                outline: none;
                border-color: var(--wp-admin-theme-color);
                background: #fff;
            }
            #dashicon-picker-close {
                background: none;
                border: none;
                font-size: 18px;
                cursor: pointer;
                color: #666;
                padding: 4px 8px;
                border-radius: 4px;
                line-height: 1;
                flex-shrink: 0;
            }
            #dashicon-picker-close:hover {
                background: #f0f0f0;
                color: #000;
            }
            .dashicon-picker-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(78px, 1fr));
                gap: 4px;
                padding: 14px;
                overflow-y: auto;
            }
            .dashicon-item {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 5px;
                padding: 10px 4px;
                background: none;
                border: 1px solid transparent;
                border-radius: 6px;
                cursor: pointer;
                transition: background 0.12s, border-color 0.12s;
            }
            .dashicon-item:hover {
                background: #f0f7ff;
                border-color: var(--wp-admin-theme-color, #2271b1);
            }
            .dashicon-item.selected {
                background: #e8f3fb;
                border-color: var(--wp-admin-theme-color, #2271b1);
            }
            .dashicon-item .dashicons {
                font-size: 24px;
                width: 24px;
                height: 24px;
                color: #333;
            }
            .dashicon-item.selected .dashicons {
                color: var(--wp-admin-theme-color, #2271b1);
            }
            .dashicon-item-label {
                font-size: 9px;
                color: #666;
                text-align: center;
                max-width: 72px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                line-height: 1.2;
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
            'title',
            'editor',
            'notes',
            'thumbnail',
            'author',
            'excerpt',
            'comments',
            'custom-fields',
            'revisions',
            'page-attributes'
        );
        $supports = array_intersect( $supports, $allowed_supports );

        $enable_notes = in_array( 'notes', $supports, true );
        $supports = array_diff( $supports, array( 'notes' ) );

        $args = array(
            'label'             => $post_type['name'],
            'public'            => ! (bool) $post_type['private'],
            'has_archive'       => ( isset($post_type['has_archive']) && $post_type['has_archive'] !== '' ) ? (bool)$post_type['has_archive'] : true,
            'supports'          => ! empty( $supports ) ? $supports : $default_supports,
            'show_in_rest'      => ( isset($post_type['show_in_rest']) && $post_type['show_in_rest'] !== '' ) ? (bool)$post_type['show_in_rest'] : true,
            'menu_position'     => 20,
            'menu_icon'         => ! empty( $post_type['dashicon'] ) ? $post_type['dashicon'] : 'dashicons-admin-page',
            'hierarchical'      => ( isset($post_type['hierarchical']) && $post_type['hierarchical'] !== '' ) ? (bool)$post_type['hierarchical'] : true,
            'show_in_menu'      => ( isset($post_type['show_in_menu']) && $post_type['show_in_menu'] !== '' ) ? (bool)$post_type['show_in_menu'] : true,
            'show_ui'           => ( isset($post_type['show_ui']) && $post_type['show_ui'] !== '' ) ? (bool)$post_type['show_ui'] : true,
            'show_in_nav_menus' => ( isset($post_type['show_in_nav_menus']) && $post_type['show_in_nav_menus'] !== '' ) ? (bool)$post_type['show_in_nav_menus'] : true,
        );

        $post_type_slug = substr( $post_type['slug'], 0, 20 );
        register_post_type( $post_type_slug, $args );

        if ( $enable_notes ) {
            add_post_type_support( $post_type_slug, 'editor', array( 'notes' => true ) );
        }

        // Add Order column if show_order is enabled
        if ( isset( $post_type['show_order'] ) && $post_type['show_order'] ) {
            add_filter( "manage_{$post_type_slug}_posts_columns", function( $columns ) {
                $new_columns = array();
                foreach ( $columns as $key => $value ) {
                    $new_columns[$key] = $value;
                    if ( $key === 'title' ) {
                        $new_columns['menu_order'] = __( 'Order', 'snn' );
                    }
                }
                return $new_columns;
            } );

            add_action( "manage_{$post_type_slug}_posts_custom_column", function( $column_name, $post_id ) {
                if ( $column_name === 'menu_order' ) {
                    $post = get_post( $post_id );
                    echo esc_html( $post->menu_order );
                }
            }, 10, 2 );

            add_filter( "manage_edit-{$post_type_slug}_sortable_columns", function( $columns ) {
                $columns['menu_order'] = 'menu_order';
                return $columns;
            } );
        }
    }
}
