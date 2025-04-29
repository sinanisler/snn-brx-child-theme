<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function snnseo_add_admin_menu() {
    add_submenu_page(
        'snn-settings',
        __( 'SEO Settings', 'snnseo' ),
        __( 'SEO Settings', 'snnseo' ),
        'manage_options',
        'snn-seo-settings',
        'snnseo_render_admin_page'
    );
}
add_action( 'admin_menu', 'snnseo_add_admin_menu' );

function snnseo_get_registered_options() {
     $options = [
         'snnseo_enable',
         'snnseo_site_title',
         'snnseo_home_meta_desc',
         'snnseo_separator',
         'snnseo_selected_post_types',
         'snnseo_selected_taxonomies',
         'snnseo_og_title_template',
         'snnseo_og_desc_template',
         'snnseo_enable_sitemap',
         'snnseo_sitemap_per_page',
     ];

     $all_post_types = get_post_types( array( 'public' => true ), 'names' );
     if ( is_array( $all_post_types ) ) {
         foreach ( $all_post_types as $pt ) {
             if ( $pt === 'attachment' ) continue;
             $options[] = "snnseo_title_template_{$pt}";
             $options[] = "snnseo_meta_desc_template_{$pt}";
         }
     }

     $all_taxonomies = get_taxonomies( array( 'public' => true ), 'names' );
     if ( is_array( $all_taxonomies ) ) {
         foreach ( $all_taxonomies as $tax ) {
             if ( $tax === 'post_format' ) continue;
             $options[] = "snnseo_title_template_{$tax}";
             $options[] = "snnseo_meta_desc_template_{$tax}";
         }
     }

     return $options;
}


function snnseo_register_settings() {
    $option_group = 'snnseo_options_group';

    register_setting( $option_group, 'snnseo_enable', 'intval' );
    register_setting( $option_group, 'snnseo_site_title', 'sanitize_text_field' );
    register_setting( $option_group, 'snnseo_home_meta_desc', 'sanitize_textarea_field' );
    register_setting( $option_group, 'snnseo_separator', 'sanitize_text_field' );

    register_setting( $option_group, 'snnseo_selected_post_types', 'snnseo_sanitize_array' );
    register_setting( $option_group, 'snnseo_selected_taxonomies', 'snnseo_sanitize_array' );

    $all_post_types = get_post_types( array( 'public' => true ), 'names' );
    if ( is_array( $all_post_types ) ) {
        foreach ( $all_post_types as $pt ) {
            if ( $pt === 'attachment' ) continue;
            register_setting( $option_group, "snnseo_title_template_{$pt}", 'sanitize_text_field' );
            register_setting( $option_group, "snnseo_meta_desc_template_{$pt}", 'sanitize_text_field' );
        }
    }

    $all_taxonomies = get_taxonomies( array( 'public' => true ), 'names' );
    if ( is_array( $all_taxonomies ) ) {
        foreach ( $all_taxonomies as $tax ) {
            if ( $tax === 'post_format' ) continue;
            register_setting( $option_group, "snnseo_title_template_{$tax}", 'sanitize_text_field' );
            register_setting( $option_group, "snnseo_meta_desc_template_{$tax}", 'sanitize_text_field' );
        }
    }

    register_setting( $option_group, 'snnseo_og_title_template', 'sanitize_text_field' );
    register_setting( $option_group, 'snnseo_og_desc_template', 'sanitize_text_field' );

    register_setting( $option_group, 'snnseo_enable_sitemap', 'intval' );
    register_setting( $option_group, 'snnseo_sitemap_per_page', array(
        'type'              => 'integer',
        'sanitize_callback' => 'absint',
        'default'           => 1000,
    ) );

    add_action( 'update_option_snnseo_enable', 'snnseo_flush_rewrite_rules_on_enable_change', 10, 2 );
    add_action( 'update_option_snnseo_enable_sitemap', 'snnseo_flush_rewrite_rules_on_sitemap_change', 10, 0 );
}
add_action( 'admin_init', 'snnseo_register_settings' );

function snnseo_sanitize_array( $input ) {
    if ( is_array( $input ) ) {
        $sanitized = array_map( 'sanitize_text_field', $input );
        return array_map( function( $str ) {
            return str_replace( '%', '%%', $str );
        }, $sanitized );
    }
    return array();
}

function snnseo_flush_rewrite_rules_on_enable_change( $old_value, $new_value ) {
    if ( $old_value != $new_value ) {
        snnseo_sitemap_init();
        flush_rewrite_rules();
    }
}

function snnseo_flush_rewrite_rules_on_sitemap_change() {
    snnseo_sitemap_init();
    flush_rewrite_rules();
}

function snnseo_handle_reset_settings() {
    if ( isset( $_POST['snnseo_reset_settings'] ) ) {
        if ( ! isset( $_POST['snnseo_reset_nonce'] ) || ! wp_verify_nonce( $_POST['snnseo_reset_nonce'], 'snnseo_reset_action' ) ) {
            add_settings_error( 'snnseo_reset', 'nonce_fail', __( 'Security check failed. Please try resetting again.', 'snnseo' ), 'error' );
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            add_settings_error( 'snnseo_reset', 'permissions_fail', __( 'You do not have permission to reset settings.', 'snnseo' ), 'error' );
            return;
        }

        $registered_options = snnseo_get_registered_options();
        $deleted_count = 0;
        foreach ( $registered_options as $option_name ) {
            if ( delete_option( $option_name ) ) {
                $deleted_count++;
            }
        }

        $redirect_url = add_query_arg( 'settings-updated', 'reset', admin_url( 'admin.php?page=snn-seo-settings' ) );

        wp_safe_redirect( $redirect_url );
        exit;
    }
}
add_action('admin_init', 'snnseo_handle_reset_settings');


function snnseo_render_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'snnseo' ) );
    }

    if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] === 'reset' ) {
         add_settings_error( 'snnseo_reset', 'reset_success', __( 'SNN SEO settings have been reset to defaults.', 'snnseo' ), 'updated' );
    }

    $selected_post_types   = (array) get_option( 'snnseo_selected_post_types', array() );
    $selected_taxonomies = (array) get_option( 'snnseo_selected_taxonomies', array() );
    $option_group        = 'snnseo_options_group';
    $is_sitemap_enabled  = get_option( 'snnseo_enable_sitemap', 0 );

    ?>
    <style>
        .snnseo-settings-wrap { display: flex; flex-wrap: wrap; gap: 20px; }
        .snnseo-settings-col { flex: 1; min-width: 300px; background: #fff; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04); border: 1px solid #e5e5e5; }
        .snnseo-settings-col h2, .snnseo-settings-col h3 { margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        .snnseo-template-group {
            border: 1px solid #ccd0d4;
            padding: 5px 10px;
            margin-bottom: 10px;
            border-radius: 4px;
            background-color: #fdfdfd;
        }
         .snnseo-template-group h4 {
             margin-top: 0;
             margin-bottom: 10px;
             font-size: 1.1em;
             border-bottom: 1px dashed #ccd0d4;
             padding-bottom: 5px;
         }
        .snnseo-template-group .form-table { margin-top: 0; }
        .snnseo-template-group .form-table tr { border-bottom: none; }
        .snnseo-template-group .form-table th,
        .snnseo-template-group .form-table td { padding: 4px 0; }
        .snnseo-template-group .form-table th { padding-right: 10px; width: 120px; }
        .snnseo-notice-inline { margin-top: 10px; margin-bottom: 0px; }
        .snnseo-tag-list code { background: #eee; padding: 2px 5px; border-radius: 3px; font-size: 0.9em; }
        .snnseo-submit-section { margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .snnseo-reset-button { margin-left: 10px; }
    </style>
    <div class="wrap">
        <h1><?php esc_html_e( 'SNN SEO Settings', 'snnseo' ); ?></h1>

        <?php settings_errors(); ?>

        <form method="post" action="options.php" id="snnseo-main-form">
            <?php settings_fields( $option_group ); ?>

            <div class="snnseo-settings-wrap">

                <div class="snnseo-settings-col">
                    <h2><?php esc_html_e( 'Core Settings', 'snnseo' ); ?></h2>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr valign="top">
                                <th scope="row"><label for="snnseo_enable"><?php esc_html_e( 'Enable SEO Features', 'snnseo' ); ?></label></th>
                                <td>
                                    <input type="checkbox" id="snnseo_enable" name="snnseo_enable" value="1" <?php checked( get_option( 'snnseo_enable', 0 ), 1 ); ?> />
                                    <p class="description"><?php esc_html_e( 'Enable dynamic titles, meta descriptions, Open Graph tags, meta boxes, and sitemaps (if enabled below).', 'snnseo' ); ?></p>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><label for="snnseo_site_title"><?php esc_html_e( 'Homepage Title', 'snnseo' ); ?></label></th>
                                <td>
                                    <input type="text" id="snnseo_site_title" name="snnseo_site_title" value="<?php echo esc_attr( get_option( 'snnseo_site_title', '{{sitename}}' ) ); ?>" class="regular-text" />
                                    <p class="description"><?php esc_html_e( 'Custom title tag for the homepage (set via Settings > Reading). Uses dynamic tags.', 'snnseo' ); ?></p>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><label for="snnseo_home_meta_desc"><?php esc_html_e( 'Homepage Meta Description', 'snnseo' ); ?></label></th>
                                <td>
                                    <textarea id="snnseo_home_meta_desc" name="snnseo_home_meta_desc" rows="3" class="regular-text"><?php echo esc_textarea( get_option( 'snnseo_home_meta_desc', '{{tagline}}' ) ); ?></textarea>
                                    <p class="description"><?php esc_html_e( 'Custom meta description for the homepage. Uses dynamic tags.', 'snnseo' ); ?></p>
                                </td>
                            </tr>
                             <tr valign="top">
                                 <th scope="row"><label for="snnseo_separator"><?php esc_html_e( 'Title Separator', 'snnseo' ); ?></label></th>
                                 <td>
                                     <input type="text" id="snnseo_separator" name="snnseo_separator" value="<?php echo esc_attr( get_option( 'snnseo_separator', ' | ' ) ); ?>" class="regular-text" style="width: 50px; text-align: center;" />
                                     <p class="description"><?php esc_html_e( 'Character(s) used by the {{separator}} tag in title templates.', 'snnseo' ); ?></p>
                                 </td>
                             </tr>
                        </tbody>
                    </table>

                    <h2><?php esc_html_e( 'Content Type Selection', 'snnseo' ); ?></h2>
                    <p><?php esc_html_e( 'Select the post types and taxonomies for which you want to configure specific SEO title/description templates and include in the sitemap.', 'snnseo' ); ?></p>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr valign="top">
                                <th scope="row"><label for="snnseo_selected_post_types"><?php esc_html_e( 'Manage SEO for Post Types', 'snnseo' ); ?></label></th>
                                <td>
                                    <select id="snnseo_selected_post_types" name="snnseo_selected_post_types[]" multiple="multiple" style="min-width:300px; height: 150px;">
                                        <?php
                                        $all_post_types = get_post_types( array( 'public' => true ), 'objects' );
                                        if ( is_array( $all_post_types ) ) {
                                            foreach ( $all_post_types as $pt_slug => $pt_obj ) {
                                                if ( $pt_slug === 'attachment' ) continue;
                                                echo '<option value="' . esc_attr( $pt_slug ) . '" ' . selected( in_array( $pt_slug, $selected_post_types, true ), true, false ) . '>' . esc_html( $pt_obj->labels->name ) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                    <p class="description"><?php esc_html_e( 'Hold Ctrl/Cmd to select multiple. These types will get SEO meta boxes and appear in the sitemap (if enabled).', 'snnseo' ); ?></p>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><label for="snnseo_selected_taxonomies"><?php esc_html_e( 'Manage SEO for Taxonomies', 'snnseo' ); ?></label></th>
                                <td>
                                    <select id="snnseo_selected_taxonomies" name="snnseo_selected_taxonomies[]" multiple="multiple" style="min-width:300px; height: 150px;">
                                        <?php
                                        $all_taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
                                        if ( is_array( $all_taxonomies ) ) {
                                            foreach ( $all_taxonomies as $tax_slug => $tax_obj ) {
                                                if ( $tax_slug === 'post_format' ) continue;
                                                echo '<option value="' . esc_attr( $tax_slug ) . '" ' . selected( in_array( $tax_slug, $selected_taxonomies, true ), true, false ) . '>' . esc_html( $tax_obj->labels->name ) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                     <p class="description"><?php esc_html_e( 'Hold Ctrl/Cmd to select multiple. These taxonomy archive pages will use templates below and appear in the sitemap (if enabled).', 'snnseo' ); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <h2><?php esc_html_e( 'XML Sitemap Settings', 'snnseo' ); ?></h2>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr valign="top">
                                <th scope="row"><label for="snnseo_enable_sitemap"><?php esc_html_e( 'Enable Sitemap Generation', 'snnseo' ); ?></label></th>
                                <td>
                                    <input type="checkbox" id="snnseo_enable_sitemap" name="snnseo_enable_sitemap" value="1" <?php checked( $is_sitemap_enabled, 1 ); ?> />
                                    <p class="description"><?php esc_html_e( 'Generate an XML sitemap index at /sitemap.xml. Rewrite rules will be flushed automatically when saving this setting.', 'snnseo' ); ?></p>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><label for="snnseo_sitemap_per_page"><?php esc_html_e( 'URLs per Sitemap Page', 'snnseo' ); ?></label></th>
                                <td>
                                    <input type="number" id="snnseo_sitemap_per_page" name="snnseo_sitemap_per_page" min="1" max="50000" step="1" value="<?php echo esc_attr( get_option( 'snnseo_sitemap_per_page', 1000 ) ); ?>" class="small-text" />
                                    <p class="description"><?php esc_html_e( 'Maximum number of URLs per sub-sitemap file (e.g., posts-sitemap1.xml). Google recommends max 50,000.', 'snnseo' ); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p class="notice notice-warning snnseo-notice-inline">
                        <?php esc_html_e( 'Note: If sitemap links (e.g., /sitemap.xml) result in a 404 error after enabling/disabling the sitemap feature or changing permalinks, please go to Settings -> Permalinks and click "Save Changes" once to refresh the rewrite rules.', 'snnseo' ); ?>
                    </p>

                    <h2><?php esc_html_e( 'Sitemap Preview', 'snnseo' ); ?></h2>
                    <?php if ( $is_sitemap_enabled && get_option('snnseo_enable', 0) ) : ?>
                        <p><?php esc_html_e( 'Your sitemap index should be available at:', 'snnseo' ); ?></p>
                        <p><a href="<?php echo esc_url( home_url( '/sitemap.xml' ) ); ?>" target="_blank"><?php echo esc_url( home_url( '/sitemap.xml' ) ); ?></a></p>
                        <p><?php esc_html_e( 'If this link gives a 404 error, go to Settings -> Permalinks and click "Save Changes" to manually refresh rewrite rules.', 'snnseo' ); ?></p>
                    <?php else : ?>
                        <p><?php esc_html_e( 'Sitemap generation is currently disabled (either via the main SEO enable toggle or the sitemap toggle). Enable both above to activate the sitemap.', 'snnseo' ); ?></p>
                    <?php endif; ?>

                </div><div class="snnseo-settings-col">
                    <h2><?php esc_html_e( 'Template Settings', 'snnseo' ); ?></h2>
                    <p><?php esc_html_e( 'Use these tags in the Title & Meta Description templates below:', 'snnseo' ); ?></p>
                    <p class="snnseo-tag-list">
                        <code>{{title}}</code>, <code>{{sitename}}</code>, <code>{{tagline}}</code>, <code>{{excerpt}}</code>, <code>{{description}}</code> (term description or post excerpt/meta),
                        <code>{{date}}</code>, <code>{{post_modified}}</code>,
                        <code>{{separator}}</code>, <code>{{cf_YOUR_CUSTOM_FIELD_NAME}}</code> (for posts/pages)
                    </p>

                    <p><?php esc_html_e( 'Configure default templates for the selected post types and taxonomies. These can be overridden on individual posts/pages/terms.', 'snnseo' ); ?></p>

                    <?php
                    if ( ! empty( $selected_post_types ) ) {
                        echo '<h3>' . esc_html__( 'Post Type Templates', 'snnseo' ) . '</h3>';
                        foreach ( $selected_post_types as $pt ) {
                            $pt_obj       = get_post_type_object( $pt );
                            $pt_label     = $pt_obj ? esc_html( $pt_obj->labels->name ) : esc_html( $pt );
                            $pt_singular  = $pt_obj ? esc_html( $pt_obj->labels->singular_name ) : esc_html( $pt );
                            $title_opt_name = "snnseo_title_template_{$pt}";
                            $desc_opt_name  = "snnseo_meta_desc_template_{$pt}";

                            $title_val = get_option( $title_opt_name );
                            if ( $title_val === false || $title_val === '' ) {
                                $title_val = '{{title}} {{separator}} {{sitename}}';
                            }
                            $desc_val = get_option( $desc_opt_name );
                             if ( $desc_val === false || $desc_val === '' ) {
                                $desc_val = '{{excerpt}}';
                            }

                            ?>
                            <div class="snnseo-template-group">
                                <h4><?php echo $pt_label; ?></h4>
                                <table class="form-table" role="presentation"><tbody>
                                    <tr valign="top">
                                        <th scope="row"><label for="<?php echo esc_attr( $title_opt_name ); ?>"><?php printf( esc_html__( '%s Title', 'snnseo' ), $pt_singular ); ?></label></th>
                                        <td><input type="text" id="<?php echo esc_attr( $title_opt_name ); ?>" class="regular-text" name="<?php echo esc_attr( $title_opt_name ); ?>" value="<?php echo esc_attr( $title_val ); ?>" /></td>
                                    </tr>
                                    <tr valign="top">
                                        <th scope="row"><label for="<?php echo esc_attr( $desc_opt_name ); ?>"><?php printf( esc_html__( '%s Meta Desc', 'snnseo' ), $pt_singular ); ?></label></th>
                                        <td><input type="text" id="<?php echo esc_attr( $desc_opt_name ); ?>" class="regular-text" name="<?php echo esc_attr( $desc_opt_name ); ?>" value="<?php echo esc_attr( $desc_val ); ?>" /></td>
                                    </tr>
                                </tbody></table>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<p>' . esc_html__( 'No post types selected. Choose post types on the left to set templates.', 'snnseo' ) . '</p>';
                    }

                    if ( ! empty( $selected_taxonomies ) ) {
                        echo '<h3>' . esc_html__( 'Taxonomy Templates', 'snnseo' ) . '</h3>';
                        foreach ( $selected_taxonomies as $tax ) {
                            $tax_obj      = get_taxonomy( $tax );
                            $tax_label    = $tax_obj ? esc_html( $tax_obj->labels->name ) : esc_html( $tax );
                            $tax_singular = $tax_obj ? esc_html( $tax_obj->labels->singular_name ) : esc_html( $tax );
                            $title_opt_name = "snnseo_title_template_{$tax}";
                            $desc_opt_name  = "snnseo_meta_desc_template_{$tax}";

                            $title_val = get_option( $title_opt_name );
                            if ( $title_val === false || $title_val === '' ) {
                                 $title_val = '{{title}} {{separator}} {{sitename}}';
                             }
                            $desc_val = get_option( $desc_opt_name );
                            if ( $desc_val === false || $desc_val === '' ) {
                                $desc_val = '{{description}}';
                            }

                            ?>
                             <div class="snnseo-template-group">
                                 <h4><?php echo $tax_label; ?></h4>
                                 <table class="form-table" role="presentation"><tbody>
                                     <tr valign="top">
                                         <th scope="row"><label for="<?php echo esc_attr( $title_opt_name ); ?>"><?php printf( esc_html__( '%s Title', 'snnseo' ), $tax_singular ); ?></label></th>
                                         <td><input type="text" id="<?php echo esc_attr( $title_opt_name ); ?>" class="regular-text" name="<?php echo esc_attr( $title_opt_name ); ?>" value="<?php echo esc_attr( $title_val ); ?>" /></td>
                                     </tr>
                                     <tr valign="top">
                                         <th scope="row"><label for="<?php echo esc_attr( $desc_opt_name ); ?>"><?php printf( esc_html__( '%s Meta Desc', 'snnseo' ), $tax_singular ); ?></label></th>
                                         <td><input type="text" id="<?php echo esc_attr( $desc_opt_name ); ?>" class="regular-text" name="<?php echo esc_attr( $desc_opt_name ); ?>" value="<?php echo esc_attr( $desc_val ); ?>" /></td>
                                     </tr>
                                 </tbody></table>
                             </div>
                             <?php
                        }
                    } else {
                         echo '<p>' . esc_html__( 'No taxonomies selected. Choose taxonomies on the left to set templates.', 'snnseo' ) . '</p>';
                    }
                    ?>

                    <h2><?php esc_html_e( 'Open Graph Defaults', 'snnseo' ); ?></h2>
                    <p><?php esc_html_e( 'Configure default templates for Open Graph (Facebook, Twitter, etc.) tags. These apply primarily to single posts/pages.', 'snnseo' ); ?></p>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr valign="top">
                                <th scope="row"><label for="snnseo_og_title_template"><?php esc_html_e( 'Default OG Title Template', 'snnseo' ); ?></label></th>
                                <td>
                                    <input type="text" id="snnseo_og_title_template" name="snnseo_og_title_template" value="<?php echo esc_attr( get_option( 'snnseo_og_title_template', '{{title}}' ) ); ?>" class="regular-text" />
                                    <p class="description"><?php esc_html_e( 'Template for og:title and twitter:title tags. Uses dynamic tags.', 'snnseo' ); ?></p>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><label for="snnseo_og_desc_template"><?php esc_html_e( 'Default OG Description Template', 'snnseo' ); ?></label></th>
                                <td>
                                    <input type="text" id="snnseo_og_desc_template" name="snnseo_og_desc_template" value="<?php echo esc_attr( get_option( 'snnseo_og_desc_template', '{{description}}' ) ); ?>" class="regular-text" />
                                    <p class="description"><?php esc_html_e( 'Template for og:description and twitter:description. Uses dynamic tags (often {{description}} or {{excerpt}}).', 'snnseo' ); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                </div></div><div class="snnseo-submit-section">
                 <?php submit_button( __( 'Save Changes', 'snnseo' ), 'primary', 'submit', false ); ?>
                 <form method="post" action="" style="display: inline; margin-left: 10px;">
                     <?php wp_nonce_field('snnseo_reset_action', 'snnseo_reset_nonce'); ?>
                     <input type="submit" name="snnseo_reset_settings" class="button button-secondary snnseo-reset-button" value="<?php esc_attr_e( 'Reset Settings', 'snnseo' ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to reset all SNN SEO settings to their defaults? This cannot be undone.', 'snnseo' ) ); ?>');">
                 </form>
             </div>
        </form>

    </div><?php
}

function snnseo_add_meta_boxes() {
    if ( ! get_option( 'snnseo_enable', 0 ) ) {
        return;
    }

    $selected_post_types = get_option( 'snnseo_selected_post_types', array() );

    if ( ! empty( $selected_post_types ) && is_array( $selected_post_types ) ) {
        foreach ( $selected_post_types as $post_type ) {
            add_meta_box(
                'snnseo_meta_box',
                __( 'SNN SEO Settings', 'snnseo' ),
                'snnseo_meta_box_callback',
                $post_type,
                'normal',
                'high'
            );
        }
    }
}
add_action( 'add_meta_boxes', 'snnseo_add_meta_boxes' );

function snnseo_meta_box_callback( $post ) {
    wp_nonce_field( 'snnseo_save_meta_box_data', 'snnseo_meta_box_nonce' );

    $title_tag    = get_post_meta( $post->ID, '_snnseo_title_tag', true );
    $meta_desc    = get_post_meta( $post->ID, '_snnseo_meta_description', true );
    $robots_noindex = get_post_meta( $post->ID, '_snnseo_robots_noindex', true );
    $og_image     = get_post_meta( $post->ID, '_snnseo_og_image', true );

    $post_type = get_post_type( $post );
    $title_template = get_option( "snnseo_title_template_{$post_type}", '{{title}} {{separator}} {{sitename}}' );
    $desc_template  = get_option( "snnseo_meta_desc_template_{$post_type}", '{{excerpt}}' );

    $title_placeholder = snnseo_parse_dynamic_tags( $title_template, $post );
    $desc_placeholder  = snnseo_parse_dynamic_tags( $desc_template, $post );
    $title_placeholder = is_string($title_placeholder) ? $title_placeholder : '';
    $desc_placeholder = is_string($desc_placeholder) ? $desc_placeholder : '';

    ?>
    <style>
        .snnseo-meta-box-flex-container { display: flex; flex-wrap: wrap; gap: 20px; }
        .snnseo-meta-box-col { flex: 1; min-width: 250px; }
        .snnseo-meta-box-col label { display: block; margin-bottom: 5px; font-weight: bold; }
        .snnseo-meta-box-col input[type="text"],
        .snnseo-meta-box-col input[type="url"],
        .snnseo-meta-box-col textarea { width: 100%; margin-bottom: 5px; }
        .snnseo-meta-box-col .description { margin-top: 0; margin-bottom: 15px; color: #666; font-size: 0.9em;}
        .snnseo-meta-box-col .description code { word-break: break-all; background: #f0f0f1; padding: 1px 4px; border-radius: 2px; }
        .snnseo-meta-box-col-full { flex-basis: 100%; margin-top: 10px; }
        .snnseo-meta-box-col label[for="snnseo_robots_noindex"] { display: inline-block; margin-bottom: 0; font-weight: normal; margin-left: 5px; }
        .snnseo-meta-box-col input[type="checkbox"] { vertical-align: middle; }
    </style>
    <div class="snnseo-meta-box-flex-container">
        <div class="snnseo-meta-box-col">
            <label for="snnseo_title_tag"><?php esc_html_e( 'SEO Title', 'snnseo' ); ?></label>
            <input type="text" name="snnseo_title_tag" id="snnseo_title_tag" value="<?php echo esc_attr( $title_tag ); ?>" placeholder="<?php echo esc_attr( $title_placeholder ); ?>" />
            <p class="description"><?php esc_html_e( 'Overrides default. Placeholder:', 'snnseo' ); ?><br><code><?php echo esc_html( $title_placeholder ?: 'Template not available' ); ?></code></p>
        </div>

        <div class="snnseo-meta-box-col">
            <label for="snnseo_meta_description"><?php esc_html_e( 'Meta Description', 'snnseo' ); ?></label>
            <textarea name="snnseo_meta_description" id="snnseo_meta_description" rows="3" placeholder="<?php echo esc_attr( $desc_placeholder ); ?>"><?php echo esc_textarea( $meta_desc ); ?></textarea>
            <p class="description"><?php esc_html_e( 'Overrides default. Placeholder:', 'snnseo' ); ?><br><code><?php echo esc_html( $desc_placeholder ?: 'Template not available' ); ?></code></p>
        </div>

        <div class="snnseo-meta-box-col">
             <label for="snnseo_og_image"><?php esc_html_e( 'Open Graph Image URL', 'snnseo' ); ?></label>
             <input type="url" name="snnseo_og_image" id="snnseo_og_image" value="<?php echo esc_url( $og_image ); ?>" placeholder="https://example.com/image.jpg" />
             <p class="description"><?php esc_html_e( 'URL for Facebook/Twitter shares. Uses Featured Image if blank.', 'snnseo' ); ?></p>
        </div>

        <div class="snnseo-meta-box-col-full">
             <input type="checkbox" name="snnseo_robots_noindex" id="snnseo_robots_noindex" value="1" <?php checked( $robots_noindex, '1' ); ?> <?php disabled( post_password_required( $post->ID ), true ); ?> />
             <label for="snnseo_robots_noindex">
                 <?php esc_html_e( 'Exclude this page from search engine indexes (noindex)', 'snnseo' ); ?>
             </label>
             <p class="description">
                <?php
                if ( post_password_required( $post->ID ) ) {
                    esc_html_e( 'Password protected content is automatically set to "noindex".', 'snnseo' );
                } else {
                    esc_html_e( 'Adds a "noindex" meta tag, suggesting search engines not to index this page.', 'snnseo' );
                }
                ?>
             </p>
        </div>
    </div>
    <?php
}

function snnseo_save_meta_box_data( $post_id ) {
    if ( ! isset( $_POST['snnseo_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['snnseo_meta_box_nonce'], 'snnseo_save_meta_box_data' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    $post_type = isset( $_POST['post_type'] ) ? sanitize_key( $_POST['post_type'] ) : get_post_type( $post_id );
    $post_type_obj = get_post_type_object( $post_type );
    if ( ! $post_type_obj || ! current_user_can( $post_type_obj->cap->edit_post, $post_id ) ) {
         return;
    }

    if ( isset( $_POST['snnseo_title_tag'] ) ) {
        update_post_meta( $post_id, '_snnseo_title_tag', sanitize_text_field( $_POST['snnseo_title_tag'] ) );
    }
    if ( isset( $_POST['snnseo_meta_description'] ) ) {
        update_post_meta( $post_id, '_snnseo_meta_description', sanitize_textarea_field( $_POST['snnseo_meta_description'] ) );
    }
    if ( post_password_required( $post_id ) ) {
        $noindex_value = '1';
    } else {
        $noindex_value = isset( $_POST['snnseo_robots_noindex'] ) ? '1' : '0';
    }
    update_post_meta( $post_id, '_snnseo_robots_noindex', $noindex_value );

    if ( isset( $_POST['snnseo_og_image'] ) ) {
        update_post_meta( $post_id, '_snnseo_og_image', esc_url_raw( $_POST['snnseo_og_image'] ) );
    }
}
add_action( 'save_post', 'snnseo_save_meta_box_data' );

function snnseo_dynamic_title( $title ) {
    if ( ! get_option( 'snnseo_enable', 0 ) ) {
        return $title;
    }

    $new_title = '';
    $post_obj = null;

    if ( is_front_page() ) {
        $home_title = get_option( 'snnseo_site_title', '{{sitename}}' );
        if ( ! empty( $home_title ) ) {
            $new_title = snnseo_parse_dynamic_tags( $home_title, null );
        }
    }
    elseif ( is_singular() ) {
        $post_obj = get_queried_object();
        if ($post_obj instanceof WP_Post) {
            $post_type = $post_obj->post_type;
            $selected_post_types = (array) get_option( 'snnseo_selected_post_types', array() );

            if ( in_array( $post_type, $selected_post_types, true ) ) {
                $custom_title = get_post_meta( $post_obj->ID, '_snnseo_title_tag', true );
                if ( ! empty( $custom_title ) ) {
                    $new_title = snnseo_parse_dynamic_tags( $custom_title, $post_obj );
                } else {
                    $template = get_option( "snnseo_title_template_{$post_type}", '{{title}} {{separator}} {{sitename}}' );
                    if ( ! empty( $template ) ) {
                        $new_title = snnseo_parse_dynamic_tags( $template, $post_obj );
                    }
                }
            }
        }
    }
    elseif ( is_tax() || is_category() || is_tag() ) {
        $term = get_queried_object();
        if ( $term instanceof WP_Term ) {
            $taxonomy = $term->taxonomy;
            $selected_taxonomies = (array) get_option( 'snnseo_selected_taxonomies', array() );

            if ( in_array( $taxonomy, $selected_taxonomies, true ) ) {
                $template = get_option( "snnseo_title_template_{$taxonomy}", '{{title}} {{separator}} {{sitename}}' );
                 if ( ! empty( $template ) ) {
                     $new_title = snnseo_parse_term_dynamic_tags( $template, $term );
                 }
            }
        }
    }
    elseif ( is_post_type_archive() ) {
        $post_type = get_query_var( 'post_type' );
        if ( is_array( $post_type ) ) { $post_type = reset( $post_type ); }

        $selected_post_types = (array) get_option( 'snnseo_selected_post_types', array() );

        if ( $post_type && in_array( $post_type, $selected_post_types, true ) ) {
            $template = get_option( "snnseo_title_template_{$post_type}", '{{title}} {{separator}} {{sitename}}' );
              if ( ! empty( $template ) ) {
                  $new_title = snnseo_parse_pt_archive_tags( $template, $post_type );
              }
        }
    }
    elseif ( is_home() ) {
        $page_for_posts_id = get_option( 'page_for_posts' );
        if ( $page_for_posts_id ) {
            $post_obj = get_post( $page_for_posts_id );
            if ( $post_obj instanceof WP_Post ) {
                $post_type = $post_obj->post_type;
                $selected_post_types = (array) get_option( 'snnseo_selected_post_types', array() );

                if ( in_array( $post_type, $selected_post_types, true ) ) {
                    $custom_title = get_post_meta( $post_obj->ID, '_snnseo_title_tag', true );
                    if ( ! empty( $custom_title ) ) {
                        $new_title = snnseo_parse_dynamic_tags( $custom_title, $post_obj );
                    } else {
                        $template = get_option( "snnseo_title_template_{$post_type}", '{{title}} {{separator}} {{sitename}}' );
                        if ( ! empty( $template ) ) {
                            $new_title = snnseo_parse_dynamic_tags( $template, $post_obj );
                        }
                    }
                }
            }
        } else {
             $home_title = get_option( 'snnseo_site_title', '{{sitename}}' );
             if ( ! empty( $home_title ) ) {
                 $new_title = snnseo_parse_dynamic_tags( $home_title, null );
             }
        }
    }

    return ! empty( $new_title ) ? $new_title : $title;
}
add_filter( 'pre_get_document_title', 'snnseo_dynamic_title', 99 );


function snnseo_output_meta_tags() {
    if ( ! get_option( 'snnseo_enable', 0 ) ) {
        return;
    }

    $meta_desc = '';
    $meta_desc_clean = '';
    $post_obj = null;
    $canonical_url = '';
    $robots_content = '';

    if ( is_front_page() ) {
        $meta_desc = get_option( 'snnseo_home_meta_desc', '{{tagline}}' );
        $meta_desc = snnseo_parse_dynamic_tags($meta_desc, null);
        $canonical_url = home_url( '/' );
    }
    elseif ( is_singular() ) {
        $post_obj = get_queried_object();
        if ($post_obj instanceof WP_Post) {
            $post_type = $post_obj->post_type;
            $selected_post_types = (array) get_option( 'snnseo_selected_post_types', array() );

            if ( in_array( $post_type, $selected_post_types, true ) ) {
                if ( post_password_required( $post_obj->ID ) ) {
                    $robots_content = 'noindex, follow';
                } else {
                    $robots_noindex = get_post_meta( $post_obj->ID, '_snnseo_robots_noindex', true );
                    if ( $robots_noindex === '1' ) {
                        $robots_content = 'noindex, follow';
                    }
                }

                $meta_desc = get_post_meta( $post_obj->ID, '_snnseo_meta_description', true );
                if ( empty( $meta_desc ) ) {
                    $template_desc = get_option( "snnseo_meta_desc_template_{$post_type}", '{{excerpt}}' );
                    if ( ! empty( $template_desc ) ) {
                        $meta_desc = snnseo_parse_dynamic_tags( $template_desc, $post_obj );
                    }
                }
                if ( empty( $meta_desc ) ) {
                     $meta_desc = snnseo_get_post_excerpt( $post_obj );
                }
            }
            $canonical_url = get_permalink( $post_obj->ID );
        }
    } elseif ( is_tax() || is_category() || is_tag() ) {
        $term = get_queried_object();
        if ( $term instanceof WP_Term ) {
            $taxonomy = $term->taxonomy;
            $selected_taxonomies = (array) get_option( 'snnseo_selected_taxonomies', array() );
            if ( in_array( $taxonomy, $selected_taxonomies, true ) ) {
                $template_desc = get_option( "snnseo_meta_desc_template_{$taxonomy}", '{{description}}' );
                if ( ! empty( $template_desc ) ) {
                    $meta_desc = snnseo_parse_term_dynamic_tags( $template_desc, $term );
                }
                 if ( empty( $meta_desc ) && ! empty( $term->description ) ) {
                      $meta_desc = $term->description;
                 }
            }
            $link = get_term_link( $term, $term->taxonomy );
            if ( ! is_wp_error( $link ) ) $canonical_url = $link;
        }
    } elseif ( is_post_type_archive() ) {
        $post_type = get_query_var( 'post_type' );
        if ( is_array( $post_type ) ) { $post_type = reset( $post_type ); }
        $selected_post_types = (array) get_option( 'snnseo_selected_post_types', array() );
        if ( $post_type && in_array( $post_type, $selected_post_types, true ) ) {
            $template_desc = get_option( "snnseo_meta_desc_template_{$post_type}", '{{description}}' );
            if ( ! empty( $template_desc ) ) {
                $meta_desc = snnseo_parse_pt_archive_tags( $template_desc, $post_type );
            }
             if ( empty( $meta_desc ) ) {
                 $pt_object = get_post_type_object( $post_type );
                 if ( $pt_object && ! empty( $pt_object->description ) ) {
                     $meta_desc = $pt_object->description;
                 }
             }
        }
        $link = get_post_type_archive_link( $post_type );
        if ( $link ) $canonical_url = $link;
    } elseif ( is_home() ) {
         $page_for_posts_id = get_option( 'page_for_posts' );
         if ($page_for_posts_id) {
             $post_obj = get_post($page_for_posts_id);
             if ($post_obj instanceof WP_Post) {
                 $post_type = $post_obj->post_type;
                 $selected_post_types = (array) get_option( 'snnseo_selected_post_types', array() );

                 if ( in_array( $post_type, $selected_post_types, true ) ) {
                     if ( post_password_required( $post_obj->ID ) ) {
                         $robots_content = 'noindex, follow';
                     } else {
                         $robots_noindex = get_post_meta( $post_obj->ID, '_snnseo_robots_noindex', true );
                         if ( $robots_noindex === '1' ) {
                             $robots_content = 'noindex, follow';
                         }
                     }

                     $meta_desc = get_post_meta( $post_obj->ID, '_snnseo_meta_description', true );
                     if ( empty( $meta_desc ) ) {
                         $template_desc = get_option( "snnseo_meta_desc_template_{$post_type}", '{{excerpt}}' );
                         if ( ! empty( $template_desc ) ) {
                             $meta_desc = snnseo_parse_dynamic_tags( $template_desc, $post_obj );
                         }
                     }
                      if ( empty( $meta_desc ) ) {
                          $meta_desc = snnseo_get_post_excerpt( $post_obj );
                     }
                 }
             }
             $canonical_url = get_permalink( $page_for_posts_id );
         } else {
             $meta_desc = get_option( 'snnseo_home_meta_desc', '{{tagline}}' );
             $meta_desc = snnseo_parse_dynamic_tags($meta_desc, null);
             $canonical_url = home_url( '/' );
         }
    } elseif ( is_search() ) {
        $robots_content = 'noindex, follow';
        $canonical_url = get_search_link();
    } elseif ( is_404() ) {
        $robots_content = 'noindex, follow';
    }

    if ( ! empty( $meta_desc ) ) {
        $meta_desc_clean = trim( wp_strip_all_tags( $meta_desc ) );
        $meta_desc_clean = mb_substr( $meta_desc_clean, 0, 160 );
        if ( ! empty( $meta_desc_clean ) ) {
             echo '<meta name="description" content="' . esc_attr( $meta_desc_clean ) . '" />' . "\n";
        }
    }

    if ( empty($robots_content) ) {
        if ( ! get_option('blog_public') ) {
             $robots_content = 'noindex, nofollow';
        } elseif ( is_paged() && !is_front_page() ) {
             $robots_content = 'noindex, follow';
        }
    }

    if ( ! empty( $robots_content ) ) {
        echo '<meta name="robots" content="' . esc_attr( $robots_content ) . '" />' . "\n";
    }

    if ( ! empty( $canonical_url ) && ( is_archive() || is_home() ) && ! is_front_page() ) {
        $paged = get_query_var( 'paged' ) ? (int) get_query_var( 'paged' ) : 1;
        if ( $paged > 1 ) {
            if ( get_option( 'permalink_structure' ) ) {
                $canonical_url = user_trailingslashit( trailingslashit( $canonical_url ) . 'page/' . $paged );
            } else {
                $canonical_url = add_query_arg( 'paged', $paged, $canonical_url );
            }
        }
    }

    if ( ! empty( $canonical_url ) ) {
        echo '<link rel="canonical" href="' . esc_url( $canonical_url ) . '" />' . "\n";
    }

    $og_title = '';
    $og_desc  = '';
    $og_image = '';
    $og_url   = $canonical_url ?: home_url( add_query_arg( null, null ) );
    $og_type  = 'website';

    if ( is_front_page() ) {
        $og_title = wp_get_document_title();
        $og_desc = $meta_desc_clean;
    }
    elseif ( is_singular() && $post_obj instanceof WP_Post ) {
        $og_type = 'article';
        $og_title = wp_get_document_title();

        $og_desc = $meta_desc_clean;
        if ( empty( $og_desc ) ) {
            $template_og_desc = get_option( 'snnseo_og_desc_template', '{{description}}' );
            $og_desc = snnseo_parse_dynamic_tags( $template_og_desc, $post_obj );
            $og_desc = trim( wp_strip_all_tags( $og_desc ) );
            $og_desc = mb_substr( $og_desc, 0, 200 );
        }

        $og_image = get_post_meta( $post_obj->ID, '_snnseo_og_image', true );
        if ( empty( $og_image ) && has_post_thumbnail( $post_obj->ID ) ) {
            $og_image = get_the_post_thumbnail_url( $post_obj->ID, 'large' );
        }
    } elseif ( is_home() || is_archive() ) {
        $og_title = wp_get_document_title();
        $og_desc = $meta_desc_clean;
    }

    if ( ! empty( $og_title ) ) echo '<meta property="og:title" content="' . esc_attr( $og_title ) . '" />' . "\n";
    if ( ! empty( $og_desc ) ) echo '<meta property="og:description" content="' . esc_attr( $og_desc ) . '" />' . "\n";
    if ( ! empty( $og_url ) ) echo '<meta property="og:url" content="' . esc_url( $og_url ) . '" />' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '" />' . "\n";
    echo '<meta property="og:type" content="' . esc_attr( $og_type ) . '" />' . "\n";
    if ( ! empty( $og_image ) ) {
        echo '<meta property="og:image" content="' . esc_url( $og_image ) . '" />' . "\n";
    }

    if ( $og_type === 'article' && $post_obj instanceof WP_Post && !is_page($post_obj) ) {
        echo '<meta property="article:published_time" content="' . esc_attr( get_the_date( 'c', $post_obj ) ) . '" />' . "\n";
        echo '<meta property="article:modified_time" content="' . esc_attr( get_the_modified_time( 'c', $post_obj ) ) . '" />' . "\n";
    }

    $twitter_card = ! empty( $og_image ) ? 'summary_large_image' : 'summary';
    echo '<meta name="twitter:card" content="' . esc_attr( $twitter_card ) . '" />' . "\n";
    if ( ! empty( $og_title ) ) echo '<meta name="twitter:title" content="' . esc_attr( $og_title ) . '" />' . "\n";
    if ( ! empty( $og_desc ) ) echo '<meta name="twitter:description" content="' . esc_attr( $og_desc ) . '" />' . "\n";
    if ( ! empty( $og_image ) ) echo '<meta name="twitter:image" content="' . esc_url( $og_image ) . '" />' . "\n";

}
add_action( 'wp_head', 'snnseo_output_meta_tags', 5 );


function snnseo_parse_dynamic_tags( $template, $post_obj = null ) {
    if (!is_string($template)) return '';

    if ( ! $post_obj instanceof WP_Post && is_singular() ) {
        global $post;
        if ($post instanceof WP_Post) $post_obj = $post;
    }

    $replacements = array(
        '{{sitename}}'    => get_bloginfo( 'name' ),
        '{{tagline}}'     => get_bloginfo( 'description' ),
        '{{separator}}'   => get_option( 'snnseo_separator', ' | ' ),
        '{{title}}'       => '',
        '{{excerpt}}'     => '',
        '{{description}}' => '',
        '{{date}}'        => '',
        '{{post_modified}}' => '',
    );

    if ( $post_obj instanceof WP_Post ) {
        $post_id  = $post_obj->ID;

        $replacements['{{title}}'] = get_the_title( $post_id );

        $post_excerpt = snnseo_get_post_excerpt( $post_obj );
        $replacements['{{excerpt}}'] = $post_excerpt;

        $replacements['{{date}}']          = get_the_date( '', $post_id );
        $replacements['{{post_modified}}'] = get_the_modified_date( '', $post_id );

        $meta_desc = get_post_meta( $post_id, '_snnseo_meta_description', true );
        $replacements['{{description}}'] = ! empty( $meta_desc ) ? $meta_desc : $replacements['{{excerpt}}'];
    } else {
         if ( is_front_page() || ( is_home() && ! get_option('page_for_posts') ) ) {
             $replacements['{{title}}'] = get_option('snnseo_site_title', '{{sitename}}');
             if ( '{{sitename}}' === $replacements['{{title}}'] ) {
                 $replacements['{{title}}'] = get_bloginfo('name');
             }
             $replacements['{{description}}'] = get_option('snnseo_home_meta_desc', '{{tagline}}');
              if ( '{{tagline}}' === $replacements['{{description}}'] ) {
                  $replacements['{{description}}'] = get_bloginfo('description');
              }
         }
    }

    $output = strtr( $template, $replacements );

    if ( $post_obj instanceof WP_Post ) {
        $post_id = $post_obj->ID;
        $output = preg_replace_callback( '/{{\s*cf_([a-zA-Z0-9_-]+)\s*}}/', function( $matches ) use ( $post_id ) {
            $field_name = $matches[1];
            $custom_field_value = get_post_meta( $post_id, $field_name, true );
            return is_scalar( $custom_field_value ) ? (string) $custom_field_value : '';
        }, $output );
    } else {
        $output = preg_replace( '/{{\s*cf_([a-zA-Z0-9_-]+)\s*}}/', '', $output );
    }

    $output = preg_replace('/\s+/', ' ', $output);
    return trim( $output );
}


function snnseo_parse_term_dynamic_tags( $template, $term_obj ) {
    if ( ! is_string( $template ) || ! $term_obj instanceof WP_Term ) return '';

    $term_description = ! empty( $term_obj->description ) ? trim( wp_strip_all_tags( $term_obj->description ) ) : '';

    $replacements = array(
        '{{sitename}}'    => get_bloginfo( 'name' ),
        '{{tagline}}'     => get_bloginfo( 'description' ),
        '{{separator}}'   => get_option( 'snnseo_separator', ' | ' ),
        '{{title}}'       => $term_obj->name,
        '{{description}}' => $term_description,
    );

    $output = strtr( $template, $replacements );

    $output = preg_replace( '/{{\s*(excerpt|date|post_modified|cf_[a-zA-Z0-9_-]+)\s*}}/', '', $output );

    $output = preg_replace( '/\s+/', ' ', $output );
    return trim( $output );
}


function snnseo_parse_pt_archive_tags( $template, $post_type ) {
    if (!is_string($template)) return '';

    $pt_obj = get_post_type_object( $post_type );
    if ( ! $pt_obj ) return '';

    $archive_title = post_type_archive_title( '', false );
    $archive_desc = $pt_obj->description ?? '';

    $replacements = array(
        '{{sitename}}'    => get_bloginfo( 'name' ),
        '{{tagline}}'     => get_bloginfo( 'description' ),
        '{{separator}}'   => get_option( 'snnseo_separator', ' | ' ),
        '{{title}}'       => $archive_title,
        '{{description}}' => trim( wp_strip_all_tags( $archive_desc ) ),
    );

    $output = strtr( $template, $replacements );

    $output = preg_replace('/{{\s*(excerpt|date|post_modified|cf_[a-zA-Z0-9_-]+)\s*}}/', '', $output);

    $output = preg_replace('/\s+/', ' ', $output);
    return trim( $output );
}

function snnseo_get_post_excerpt( $post_obj ) {
    if ( ! $post_obj instanceof WP_Post ) {
        return '';
    }
    if ( post_password_required( $post_obj->ID ) ) {
        return __( 'This content is password protected.', 'snnseo' );
    } elseif ( has_excerpt( $post_obj->ID ) ) {
        return $post_obj->post_excerpt;
    } else {
        return wp_trim_excerpt( '', $post_obj );
    }
}


function snnseo_sitemap_init() {
    if ( get_option( 'snnseo_enable', 0 ) && get_option( 'snnseo_enable_sitemap', 0 ) ) {
        add_rewrite_rule( '^sitemap\.xml$', 'index.php?snn_sitemap=index', 'top' );
        add_rewrite_rule( '^([a-z0-9_]+?)-sitemap([0-9]+)?\.xml$', 'index.php?snn_sitemap=sub&snn_sitemap_type=$matches[1]&snn_sitemap_page=$matches[2]', 'top' );
        add_rewrite_rule( '^([a-z0-9_]+?)-sitemap\.xml$', 'index.php?snn_sitemap=sub&snn_sitemap_type=$matches[1]&snn_sitemap_page=1', 'top' );
    }
}
add_action( 'init', 'snnseo_sitemap_init' );

function snnseo_sitemap_query_vars( $vars ) {
    $vars[] = 'snn_sitemap';
    $vars[] = 'snn_sitemap_type';
    $vars[] = 'snn_sitemap_page';
    return $vars;
}
add_filter( 'query_vars', 'snnseo_sitemap_query_vars' );

function snnseo_sitemap_template_redirect() {
    if ( ! get_option( 'snnseo_enable', 0 ) || ! get_option( 'snnseo_enable_sitemap', 0 ) ) {
        return;
    }

    $snn_sitemap = get_query_var( 'snn_sitemap' );

    if ( ! empty( $snn_sitemap ) ) {
        header( 'X-Robots-Tag: noindex, follow', true );
        header( 'Content-Type: application/xml; charset=utf-8' );

        while ( ob_get_level() > 0 ) {
            ob_end_clean();
        }

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

        if ( $snn_sitemap === 'index' ) {
            snnseo_output_sitemap_index();
        } elseif ( $snn_sitemap === 'sub' ) {
            $type = get_query_var( 'snn_sitemap_type' );
            $page = max( 1, (int) get_query_var( 'snn_sitemap_page' ) );
            snnseo_output_sub_sitemap( $type, $page );
        } else {
            status_header( 404 );
            echo '';
        }
        exit;
    }
}
add_action( 'template_redirect', 'snnseo_sitemap_template_redirect', 5 );

function snnseo_output_sitemap_index() {
    $selected_post_types   = (array) get_option( 'snnseo_selected_post_types', array() );
    $selected_taxonomies = (array) get_option( 'snnseo_selected_taxonomies', array() );
    $per_page            = max( 1, (int) get_option( 'snnseo_sitemap_per_page', 1000 ) );
    $base_url            = home_url();

    echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    if ( is_array( $selected_post_types ) ) {
        foreach ( $selected_post_types as $pt ) {
            $pt_object = get_post_type_object( $pt );
            if ( ! $pt_object || ! $pt_object->public || $pt === 'attachment' ) continue;

            $count_args = array(
                'post_type' => $pt,
                'post_status' => 'publish',
                'has_password' => false
            );
            $query = new WP_Query($count_args);
            $total_posts = $query->found_posts;


            if ( $total_posts > 0 ) {
                $max_pages = ceil( $total_posts / $per_page );

                $last_post_args = array(
                    'post_type'      => $pt,
                    'post_status'    => 'publish',
                    'has_password'   => false,
                    'numberposts'    => 1,
                    'orderby'        => 'modified',
                    'order'          => 'DESC',
                    'fields'         => 'ids',
                    'no_found_rows'  => true,
                    'cache_results'  => false
                );
                $last_post = get_posts( $last_post_args );
                $lastmod = $last_post ? get_the_modified_time( 'c', $last_post[0] ) : date( 'c' );

                for ( $page = 1; $page <= $max_pages; $page++ ) {
                    $sitemap_url = trailingslashit( $base_url ) . $pt . '-sitemap' . ( $max_pages > 1 ? $page : '' ) . '.xml';
                    echo "\t<sitemap>\n";
                    echo "\t\t<loc>" . esc_url( $sitemap_url ) . "</loc>\n";
                    echo "\t\t<lastmod>" . esc_html( $lastmod ) . "</lastmod>\n";
                    echo "\t</sitemap>\n";
                }
            }
        }
    }

    if ( is_array( $selected_taxonomies ) ) {
        foreach ( $selected_taxonomies as $tax ) {
            $tax_object = get_taxonomy( $tax );
            if ( ! $tax_object || ! $tax_object->public || $tax === 'post_format' ) continue;

            $total_terms = wp_count_terms( $tax, array( 'hide_empty' => true ) );

            if ( $total_terms > 0 ) {
                $max_pages = ceil( $total_terms / $per_page );
                $lastmod = date( 'c' );

                for ( $page = 1; $page <= $max_pages; $page++ ) {
                    $sitemap_url = trailingslashit( $base_url ) . $tax . '-sitemap' . ( $max_pages > 1 ? $page : '' ) . '.xml';
                    echo "\t<sitemap>\n";
                    echo "\t\t<loc>" . esc_url( $sitemap_url ) . "</loc>\n";
                    echo "\t\t<lastmod>" . esc_html( $lastmod ) . "</lastmod>\n";
                    echo "\t</sitemap>\n";
                }
            }
        }
    }

    echo '</sitemapindex>';
}

function snnseo_output_sub_sitemap( $type, $page ) {
    $selected_post_types   = (array) get_option( 'snnseo_selected_post_types', array() );
    $selected_taxonomies = (array) get_option( 'snnseo_selected_taxonomies', array() );
    $per_page            = max( 1, (int) get_option( 'snnseo_sitemap_per_page', 1000 ) );
    $offset              = ( $page - 1 ) * $per_page;
    $xml_output          = '';

    if ( in_array( $type, $selected_post_types, true ) ) {
        $pt_object = get_post_type_object( $type );
        if ( $pt_object && $pt_object->public && $type !== 'attachment' ) {
            $posts_query_args = array(
                'post_type'              => $type,
                'post_status'            => 'publish',
                'has_password'           => false,
                'posts_per_page'         => $per_page,
                'offset'                 => $offset,
                'orderby'                => 'date',
                'order'                  => 'DESC',
                'no_found_rows'          => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key'     => '_snnseo_robots_noindex',
                        'value'   => '1',
                        'compare' => '!=',
                    ),
                    array(
                        'key'     => '_snnseo_robots_noindex',
                        'compare' => 'NOT EXISTS',
                    ),
                ),
            );
            $posts_query = new WP_Query( $posts_query_args );

            if ( $posts_query->have_posts() ) {
                $xml_output = snnseo_build_sitemap_xml_for_posts( $posts_query->posts );
            }
            wp_reset_postdata();
        }
    }
    elseif ( in_array( $type, $selected_taxonomies, true ) ) {
        $tax_object = get_taxonomy( $type );
        if ( $tax_object && $tax_object->public && $type !== 'post_format' ) {
            $terms_args = array(
                'taxonomy'   => $type,
                'hide_empty' => true,
                'number'     => $per_page,
                'offset'     => $offset,
                'orderby'    => 'name',
                'order'      => 'ASC',
            );
            $terms = get_terms( $terms_args );

            if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                 $xml_output = snnseo_build_sitemap_xml_for_terms( $terms );
            }
        }
    }

    if ( ! empty( $xml_output ) ) {
        echo $xml_output;
    } else {
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>';
    }
}

function snnseo_build_sitemap_xml_for_posts( $posts ) {
    $xml = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    if ( ! empty( $posts ) && is_array( $posts ) ) {
        foreach ( $posts as $post ) {
            if ( ! $post instanceof WP_Post ) continue;

            if ( post_password_required( $post->ID ) ) continue;

            $url     = get_permalink( $post );
            $lastmod = get_the_modified_time( 'c', $post );

            $xml .= "\t<url>\n";
            $xml .= "\t\t<loc>" . esc_url( $url ) . "</loc>\n";
            $xml .= "\t\t<lastmod>" . esc_html( $lastmod ) . "</lastmod>\n";
            $xml .= "\t</url>\n";
        }
    }
    $xml .= '</urlset>';
    return $xml;
}

function snnseo_build_sitemap_xml_for_terms( $terms ) {
    $xml = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    if ( ! empty( $terms ) && is_array( $terms ) && ! is_wp_error( $terms ) ) {
        foreach ( $terms as $term ) {
            if ( ! $term instanceof WP_Term ) continue;

            $url = get_term_link( $term );
            if ( is_wp_error( $url ) ) continue;

            $lastmod = date( 'c' );

            $xml .= "\t<url>\n";
            $xml .= "\t\t<loc>" . esc_url( $url ) . "</loc>\n";
            $xml .= "\t\t<lastmod>" . esc_html( $lastmod ) . "</lastmod>\n";
            $xml .= "\t</url>\n";
        }
    }
    $xml .= '</urlset>';
    return $xml;
}

function snnseo_add_sitemap_to_robots( $output, $public ) {
    if ( $public && get_option( 'snnseo_enable', 0 ) && get_option( 'snnseo_enable_sitemap', 0 ) ) {
        $sitemap_url = home_url( '/sitemap.xml' );
        $output .= "\nSitemap: " . esc_url( $sitemap_url ) . "\n";
    }
    return trim( $output );
}
add_filter( 'robots_txt', 'snnseo_add_sitemap_to_robots', 90, 2 );


function snnseo_add_action_links( $links ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        return $links;
    }

    $settings_url = admin_url( 'admin.php?page=snn-seo-settings' );
    $settings_link = '<a href="' . esc_url( $settings_url ) . '">' . __( 'Settings', 'snnseo' ) . '</a>';

    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'snnseo_add_action_links' );


function snnseo_add_quick_edit_columns( $columns ) {
    if ( ! get_option( 'snnseo_enable', 0 ) ) {
        return $columns;
    }

    $new_columns = array();
    $added = false;

    foreach ( $columns as $key => $value ) {
        if ( $key == 'date' && !$added ) {
            $new_columns['snnseo_title'] = __( 'SEO Title', 'snnseo' );
            $new_columns['snnseo_desc'] = __( 'SEO Desc', 'snnseo' );
            $new_columns['snnseo_noindex'] = __( 'Noindex', 'snnseo' );
            $added = true;
        }
        $new_columns[$key] = $value;
    }

    if (!$added) {
        $new_columns['snnseo_title'] = __( 'SEO Title', 'snnseo' );
        $new_columns['snnseo_desc'] = __( 'SEO Desc', 'snnseo' );
        $new_columns['snnseo_noindex'] = __( 'Noindex', 'snnseo' );
    }

    return $new_columns;
}

function snnseo_populate_quick_edit_columns( $column_name, $post_id ) {
    if ( ! get_option( 'snnseo_enable', 0 ) ) {
        return;
    }

    switch ( $column_name ) {
        case 'snnseo_title':
            $title = get_post_meta( $post_id, '_snnseo_title_tag', true );
            echo esc_html( $title ? mb_strimwidth( $title, 0, 50, '...' ) : '—' );
            echo '<div class="hidden" id="snnseo_title_' . $post_id . '">' . esc_attr( $title ) . '</div>';
            break;

        case 'snnseo_desc':
            $desc = get_post_meta( $post_id, '_snnseo_meta_description', true );
            $display_desc = $desc ? mb_strimwidth( $desc, 0, 50, '...' ) : '—';
            echo esc_html( $display_desc );
            echo '<div class="hidden" id="snnseo_desc_' . $post_id . '">' . esc_textarea( $desc ) . '</div>';
            break;

        case 'snnseo_noindex':
            if ( post_password_required( $post_id ) ) {
                $noindex = '1';
                $display_text = __( 'Yes (Pwd)', 'snnseo' );
            } else {
                $noindex = get_post_meta( $post_id, '_snnseo_robots_noindex', true );
                $display_text = ( $noindex === '1' ) ? __( 'Yes', 'snnseo' ) : __( 'No', 'snnseo' );
            }
            echo esc_html( $display_text );
            echo '<div class="hidden" id="snnseo_noindex_' . $post_id . '">' . esc_attr( $noindex ?: '0' ) . '</div>';
            echo '<div class="hidden" id="snnseo_is_pwd_' . $post_id . '">' . ( post_password_required( $post_id ) ? '1' : '0' ) . '</div>';
            break;
    }
}

function snnseo_add_quick_edit_fields( $column_name, $post_type ) {
    $selected_post_types = (array) get_option( 'snnseo_selected_post_types', array() );
    if ( ! get_option( 'snnseo_enable', 0 ) || ! in_array( $post_type, $selected_post_types ) ) {
        return;
    }

    if ( $column_name !== 'snnseo_title' ) {
        return;
    }

    wp_nonce_field( 'snnseo_save_quick_edit_data', 'snnseo_quick_edit_nonce' );
    ?>
    <fieldset class="inline-edit-col-right snnseo-quick-edit-fields">
        <div class="inline-edit-col">
            <legend class="inline-edit-legend"><?php esc_html_e('SNN SEO', 'snnseo'); ?></legend>
            <div class="inline-edit-group wp-clearfix">
                <label class="alignleft">
                    <span class="title"><?php esc_html_e( 'SEO Title', 'snnseo' ); ?></span>
                    <span class="input-text-wrap">
                        <input type="text" name="snnseo_title_tag" class="ptitle" value="" style="width: 100%;">
                    </span>
                </label>
            </div>
             <div class="inline-edit-group wp-clearfix">
                <label class="alignleft">
                    <span class="title"><?php esc_html_e( 'SEO Desc', 'snnseo' ); ?></span>
                    <span class="input-text-wrap">
                        <textarea name="snnseo_meta_description" cols="22" rows="2" style="width: 100%; height: 4em;"></textarea>
                    </span>
                </label>
            </div>
             <div class="inline-edit-group wp-clearfix snnseo-noindex-quickedit">
                <label class="alignleft">
                     <input type="checkbox" name="snnseo_robots_noindex" value="1">
                    <span class="checkbox-title"><?php esc_html_e( 'Noindex', 'snnseo' ); ?></span>
                </label>
                <em class="snnseo-pwd-notice" style="display:none; margin-left: 5px; font-size: 0.9em; color: #888;"><?php esc_html_e('(Forced by password)', 'snnseo'); ?></em>
            </div>
        </div>
    </fieldset>
    <?php
}

function snnseo_save_quick_edit_data( $post_id ) {
    if ( ! isset( $_POST['snnseo_quick_edit_nonce'] ) || ! wp_verify_nonce( $_POST['snnseo_quick_edit_nonce'], 'snnseo_save_quick_edit_data' ) ) {
        return;
    }

    if ( ! defined('DOING_AJAX') || ! DOING_AJAX || ! isset($_POST['action']) || $_POST['action'] !== 'inline-save') {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    if ( isset( $_POST['snnseo_title_tag'] ) ) {
        update_post_meta( $post_id, '_snnseo_title_tag', sanitize_text_field( $_POST['snnseo_title_tag'] ) );
    }
    if ( isset( $_POST['snnseo_meta_description'] ) ) {
        update_post_meta( $post_id, '_snnseo_meta_description', sanitize_textarea_field( $_POST['snnseo_meta_description'] ) );
    }

    if ( post_password_required( $post_id ) ) {
        $noindex_value = '1';
    } else {
        $noindex_value = isset( $_POST['snnseo_robots_noindex'] ) ? '1' : '0';
    }
    update_post_meta( $post_id, '_snnseo_robots_noindex', $noindex_value );

}
add_action( 'save_post', 'snnseo_save_quick_edit_data' );


function snnseo_enqueue_quick_edit_script( $hook ) {
    if ( $hook !== 'edit.php' ) {
        return;
    }
    if ( ! get_option( 'snnseo_enable', 0 ) ) {
        return;
    }

    $script = "
    jQuery(document).ready(function($) {
        if (typeof inlineEditPost !== 'undefined') {

            var wp_inline_edit = inlineEditPost.edit;

            inlineEditPost.edit = function( id ) {
                wp_inline_edit.apply( this, arguments );

                var post_id = 0;
                if ( typeof( id ) == 'object' ) {
                    post_id = parseInt( this.getId( id ) );
                }
                if ( post_id === 0 ) return;

                var edit_row = $( '#edit-' + post_id );
                var post_row = $( '#post-' + post_id );

                var snn_title = $( '#snnseo_title_' + post_id, post_row ).text();
                var snn_desc = $( '#snnseo_desc_' + post_id, post_row ).text();
                var snn_noindex = $( '#snnseo_noindex_' + post_id, post_row ).text();
                var snn_is_pwd = $( '#snnseo_is_pwd_' + post_id, post_row ).text() === '1';

                var title_input = $( ':input[name=\"snnseo_title_tag\"]', edit_row );
                var desc_input = $( 'textarea[name=\"snnseo_meta_description\"]', edit_row );
                var noindex_input = $( ':input[name=\"snnseo_robots_noindex\"]', edit_row );
                var noindex_label = noindex_input.closest('label');
                var pwd_notice = $( '.snnseo-pwd-notice', edit_row );

                title_input.val( snn_title );
                desc_input.val( snn_desc );
                noindex_input.prop( 'checked', snn_noindex === '1' );

                if ( snn_is_pwd ) {
                    noindex_input.prop( 'disabled', true );
                    noindex_input.prop( 'checked', true );
                    noindex_label.css('color', '#888');
                    pwd_notice.show();
                } else {
                    noindex_input.prop( 'disabled', false );
                    noindex_label.css('color', '');
                    pwd_notice.hide();
                }
            };

             $('#bulk_edit').on('click', function() {
                 var bulk_row = $('#bulk-edit');
                 $( ':input[name=\"snnseo_title_tag\"]', bulk_row ).val('');
                 $( 'textarea[name=\"snnseo_meta_description\"]', bulk_row ).val('');
                 $( ':input[name=\"snnseo_robots_noindex\"]', bulk_row ).prop('checked', false);
             });
        }
    });
    ";
    wp_add_inline_script( 'inline-edit-post', $script );
}
add_action( 'admin_enqueue_scripts', 'snnseo_enqueue_quick_edit_script' );


function snnseo_add_quick_edit_hooks_for_post_types() {
     if ( ! get_option( 'snnseo_enable', 0 ) ) {
         return;
     }
    $selected_post_types = (array) get_option( 'snnseo_selected_post_types', array() );
    if ( ! empty( $selected_post_types ) ) {
        foreach ( $selected_post_types as $pt ) {
            add_filter( "manage_{$pt}_posts_columns", 'snnseo_add_quick_edit_columns' );
            add_action( "manage_{$pt}_posts_custom_column", 'snnseo_populate_quick_edit_columns', 10, 2 );
            add_action( 'quick_edit_custom_box', 'snnseo_add_quick_edit_fields', 10, 2 );
        }
    }
}
add_action('admin_init', 'snnseo_add_quick_edit_hooks_for_post_types');


?>
