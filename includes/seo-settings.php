<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =============================================================================
   1. Activation / Deactivation
============================================================================= */
register_activation_hook(__FILE__, 'snnseo_childtheme_activate');
function snnseo_childtheme_activate() {
    snnseo_childtheme_sitemap_init();
    flush_rewrite_rules();
}

register_deactivation_hook(__FILE__, 'snnseo_childtheme_deactivate');
function snnseo_childtheme_deactivate() {
    flush_rewrite_rules();
}

/* =============================================================================
   2. Admin Menu
============================================================================= */
add_action('admin_menu', 'snnseo_childtheme_add_admin_menu');
function snnseo_childtheme_add_admin_menu() {
    add_submenu_page(
        'snn-settings',
        __('SEO Settings', 'snnseo'),
        __('SEO Settings', 'snnseo'),
        'manage_options',
        'snn-seo-ultimate',
        'snnseo_childtheme_render_admin_page'
    );
}

/* =============================================================================
   3. Register Settings
============================================================================= */
add_action('admin_init', 'snnseo_childtheme_register_settings');
function snnseo_childtheme_register_settings() {
    // General settings
    register_setting('snnseo_options_group', 'snnseo_enable', 'intval');
    register_setting('snnseo_options_group', 'snnseo_site_title', 'sanitize_text_field');
    register_setting('snnseo_options_group', 'snnseo_home_meta_desc', 'sanitize_textarea_field');

    // Open Graph settings
    register_setting('snnseo_options_group', 'snnseo_og_title_template', 'sanitize_text_field');
    register_setting('snnseo_options_group', 'snnseo_og_desc_template', 'sanitize_text_field');

    // Single sitemap checkbox
    register_setting('snnseo_options_group', 'snnseo_enable_sitemap', 'intval');

    // New multi-select options for dynamic templates
    register_setting('snnseo_options_group', 'snnseo_selected_post_types', 'snnseo_sanitize_array');
    register_setting('snnseo_options_group', 'snnseo_selected_taxonomies', 'snnseo_sanitize_array');

    // Register dynamic title/meta templates ONLY for selected post types
    $selected_post_types = get_option('snnseo_selected_post_types', array());
    if ( is_array($selected_post_types) ) {
        foreach ($selected_post_types as $pt) {
            register_setting('snnseo_options_group', "snnseo_title_template_{$pt}", 'sanitize_text_field');
            register_setting('snnseo_options_group', "snnseo_meta_desc_template_{$pt}", 'sanitize_text_field');
        }
    }

    // Register dynamic title/meta templates ONLY for selected taxonomies
    $selected_taxonomies = get_option('snnseo_selected_taxonomies', array());
    if ( is_array($selected_taxonomies) ) {
        foreach ($selected_taxonomies as $tax) {
            register_setting('snnseo_options_group', "snnseo_title_template_{$tax}", 'sanitize_text_field');
            register_setting('snnseo_options_group', "snnseo_meta_desc_template_{$tax}", 'sanitize_text_field');
        }
    }
}

// Helper function to sanitize array inputs
function snnseo_sanitize_array($input) {
    if ( is_array($input) ) {
        return array_map('sanitize_text_field', $input);
    }
    return array();
}

/* =============================================================================
   4. Admin Page
============================================================================= */
function snnseo_childtheme_render_admin_page() {
    if ( ! current_user_can('manage_options') ) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php _e('SEO Settings', 'snnseo'); ?></h1>

        <form method="post" action="options.php">
            <?php settings_fields('snnseo_options_group'); ?>

            <!-- General Settings -->
            <h2><?php _e('General Settings', 'snnseo'); ?></h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e('Enable SEO', 'snnseo'); ?></th>
                    <td>
                        <input type="checkbox" name="snnseo_enable" value="1" <?php checked(get_option('snnseo_enable'), 1); ?> />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Site Title', 'snnseo'); ?></th>
                    <td>
                        <input type="text" name="snnseo_site_title" value="<?php echo esc_attr(get_option('snnseo_site_title', '')); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Site Meta Desc', 'snnseo'); ?></th>
                    <td>
                        <textarea name="snnseo_home_meta_desc" rows="3" cols="50"><?php echo esc_textarea(get_option('snnseo_home_meta_desc', '')); ?></textarea>
                    </td>
                </tr>
            </table>

            <!-- Multi-select for Post Types and Taxonomies -->
            <h2><?php _e('Select Post Types and Taxonomies', 'snnseo'); ?></h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e('Post Types', 'snnseo'); ?></th>
                    <td>
                        <select name="snnseo_selected_post_types[]" multiple="multiple" style="width:300px;">
                            <?php
                            $all_post_types = get_post_types(array(
                                'public'             => true,
                                'exclude_from_search' => false,
                            ), 'objects');
                            $selected_post_types = (array) get_option('snnseo_selected_post_types', array());
                            foreach ($all_post_types as $pt_slug => $pt_obj) {
                                echo '<option value="' . esc_attr($pt_slug) . '" ' . selected(in_array($pt_slug, $selected_post_types), true, false) . '>' . esc_html($pt_obj->labels->name) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Taxonomies', 'snnseo'); ?></th>
                    <td>
                        <select name="snnseo_selected_taxonomies[]" multiple="multiple" style="width:300px;">
                            <?php
                            $all_taxonomies = get_taxonomies(array('public' => true), 'objects');
                            $selected_taxonomies = (array) get_option('snnseo_selected_taxonomies', array());
                            foreach ($all_taxonomies as $tax_slug => $tax_obj) {
                                echo '<option value="' . esc_attr($tax_slug) . '" ' . selected(in_array($tax_slug, $selected_taxonomies), true, false) . '>' . esc_html($tax_obj->labels->name) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
            </table>

            <!-- Dynamic Tags Information -->
            <h2><?php _e('Meta Tag  (use dynamic tags)', 'snnseo'); ?></h2>
            <p><?php _e('Available tags (new ones added above the existing):', 'snnseo'); ?>
                <code>%%separator%%</code>, <code>%%sitename%%</code>, <code>%%tagline%%</code>, <code>%%title%%</code>, <code>%%excerpt%%</code>, <code>%%description%%</code>,
                <code>%%date%%</code>, <code>%%post_modified%%</code>, <code>%%thumbnail%%</code>, <code>%%url%%</code>, <code>%%author_first_name%%</code>, <code>%%author_last_name%%</code>, <code>%%cf_fieldname%%</code>
            </p><br>

            <!-- Post Types and Taxonomies -->
            <h2><?php _e('Titles and Meta Descriptions', 'snnseo'); ?></h2>
            <?php
            // Post Type 
            $selected_post_types = (array) get_option('snnseo_selected_post_types', array());
            if ( ! empty($selected_post_types) ) {
                echo '<h3>' . esc_html__('Post Type', 'snnseo') . '</h3>';
                echo '<table class="form-table">';
                foreach ($selected_post_types as $pt) {
                    $pt_obj   = get_post_type_object($pt);
                    $pt_label = isset($pt_obj->labels->name) ? $pt_obj->labels->name : $pt;
                    $title_opt = get_option("snnseo_title_template_{$pt}", '%%title%% - %%sitename%%');
                    $desc_opt  = get_option("snnseo_meta_desc_template_{$pt}", '');
                    ?>
                    <tr valign="top">
                        <th scope="row">
                            <?php echo sprintf(__('%s Title', 'snnseo'), esc_html($pt_label)); ?>
                        </th>
                        <td>
                            <input type="text" class="regular-text" name="snnseo_title_template_<?php echo esc_attr($pt); ?>" value="<?php echo esc_attr($title_opt); ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <?php echo sprintf(__('%s Meta Description', 'snnseo'), esc_html($pt_label)); ?>
                        </th>
                        <td>
                            <input type="text" class="regular-text" name="snnseo_meta_desc_template_<?php echo esc_attr($pt); ?>" value="<?php echo esc_attr($desc_opt); ?>" />
                        </td>
                    </tr>
                    <?php
                }
                echo '</table>';
            }

            // Taxonomy
            $selected_taxonomies = (array) get_option('snnseo_selected_taxonomies', array());
            if ( ! empty($selected_taxonomies) ) {
                echo '<h3>' . esc_html__('Taxonomy', 'snnseo') . '</h3>';
                echo '<table class="form-table">';
                foreach ($selected_taxonomies as $tax) {
                    $tax_obj   = get_taxonomy($tax);
                    $tax_label = isset($tax_obj->labels->name) ? $tax_obj->labels->name : $tax;
                    $title_opt = get_option("snnseo_title_template_{$tax}", '%%title%% - %%sitename%%');
                    $desc_opt  = get_option("snnseo_meta_desc_template_{$tax}", '');
                    ?>
                    <tr valign="top">
                        <th scope="row">
                            <?php echo sprintf(__('%s Title', 'snnseo'), esc_html($tax_label)); ?>
                        </th>
                        <td>
                            <input type="text" class="regular-text" name="snnseo_title_template_<?php echo esc_attr($tax); ?>" value="<?php echo esc_attr($title_opt); ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <?php echo sprintf(__('%s Meta Description', 'snnseo'), esc_html($tax_label)); ?>
                        </th>
                        <td>
                            <input type="text" class="regular-text" name="snnseo_meta_desc_template_<?php echo esc_attr($tax); ?>" value="<?php echo esc_attr($desc_opt); ?>" />
                        </td>
                    </tr>
                    <?php
                }
                echo '</table>';
            }
            ?>

            <!-- Open Graph  -->
            <h2><?php _e('Open Graph', 'snnseo'); ?></h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e('Open Graph Title', 'snnseo'); ?></th>
                    <td>
                        <?php $og_title = get_option('snnseo_og_title_template', '%%title%%'); ?>
                        <input type="text" name="snnseo_og_title_template" value="<?php echo esc_attr($og_title); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Open Graph Description Template', 'snnseo'); ?></th>
                    <td>
                        <?php $og_desc = get_option('snnseo_og_desc_template', '%%description%%'); ?>
                        <input type="text" name="snnseo_og_desc_template" value="<?php echo esc_attr($og_desc); ?>" class="regular-text" />
                    </td>
                </tr>
            </table>

            <!-- Sitemap Settings -->
            <h2><?php _e('Sitemap Settings', 'snnseo'); ?></h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e('Enable Sitemap Generation', 'snnseo'); ?></th>
                    <td>
                        <input type="checkbox" name="snnseo_enable_sitemap" value="1" <?php checked(get_option('snnseo_enable_sitemap'), 1); ?> />
                    </td>
                </tr>
            </table>

            <!-- Sitemap Preview -->
            <h2><?php _e('Sitemap Preview', 'snnseo'); ?></h2>
            <p><?php _e('Click on the link below to view your sitemap (if enabled):', 'snnseo'); ?></p>
            <ul>
                <?php if ( get_option('snnseo_enable_sitemap') ) : ?>
                    <li>
                        <a href="<?php echo esc_url(site_url('sitemap.xml')); ?>" target="_blank">
                            <?php _e('Sitemap Index', 'snnseo'); ?>
                        </a>
                    </li>
                <?php else : ?>
                    <li><?php _e('Sitemap generation is disabled.', 'snnseo'); ?></li>
                <?php endif; ?>
            </ul>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/* =============================================================================
   5. Meta Boxes for Posts
============================================================================= */
add_action('add_meta_boxes', 'snnseo_childtheme_add_meta_boxes');
function snnseo_childtheme_add_meta_boxes() {
    if ( ! get_option('snnseo_enable') ) {
        return;
    }
    $post_types = get_post_types(array('public' => true), 'names');
    foreach ( $post_types as $post_type ) {
        add_meta_box(
            'snnseo_meta_box',
            __('SEO Meta Data', 'snnseo'),
            'snnseo_childtheme_meta_box_callback',
            $post_type,
            'normal',
            'high'
        );
    }
}

function snnseo_childtheme_meta_box_callback($post) {
    wp_nonce_field('snnseo_meta_box_nonce', 'snnseo_meta_box_nonce_field');
    $title_tag      = get_post_meta($post->ID, 'snnseo_title_tag', true);
    $meta_desc      = get_post_meta($post->ID, 'snnseo_meta_description', true);
    $focus_keyword  = get_post_meta($post->ID, 'snnseo_focus_keyword', true);
    $robots_noindex = get_post_meta($post->ID, 'snnseo_robots_noindex', true);
    $og_image       = get_post_meta($post->ID, 'snnseo_og_image', true);
    ?>
    <p>
        <label for="snnseo_title_tag"><strong><?php _e('SEO Title:', 'snnseo'); ?></strong></label><br/>
        <input type="text" name="snnseo_title_tag" id="snnseo_title_tag" value="<?php echo esc_attr($title_tag); ?>" class="widefat" />
    </p>
    <p>
        <label for="snnseo_meta_description"><strong><?php _e('Meta Description:', 'snnseo'); ?></strong></label><br/>
        <textarea name="snnseo_meta_description" id="snnseo_meta_description" rows="3" class="widefat"><?php echo esc_textarea($meta_desc); ?></textarea>
    </p>
    <p>
        <label for="snnseo_focus_keyword"><strong><?php _e('Focus Keyword:', 'snnseo'); ?></strong></label><br/>
        <input type="text" name="snnseo_focus_keyword" id="snnseo_focus_keyword" value="<?php echo esc_attr($focus_keyword); ?>" class="widefat" />
    </p>
    <p>
        <label for="snnseo_robots_noindex">
            <input type="checkbox" name="snnseo_robots_noindex" id="snnseo_robots_noindex" value="1" <?php checked($robots_noindex, '1'); ?> />
            <?php _e('Add noindex to this page', 'snnseo'); ?>
        </label>
    </p>
    <p>
        <label for="snnseo_og_image"><strong><?php _e('Open Graph Image URL:', 'snnseo'); ?></strong></label><br/>
        <input type="text" name="snnseo_og_image" id="snnseo_og_image" value="<?php echo esc_url($og_image); ?>" class="widefat" />
    </p>
    <?php
}

add_action('save_post', 'snnseo_childtheme_save_meta_box_data');
function snnseo_childtheme_save_meta_box_data($post_id) {
    if ( ! isset($_POST['snnseo_meta_box_nonce_field']) 
         || ! wp_verify_nonce($_POST['snnseo_meta_box_nonce_field'], 'snnseo_meta_box_nonce') ) {
        return;
    }
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
        return;
    }
    if ( isset($_POST['post_type']) && ! current_user_can('edit_post', $post_id) ) {
        return;
    }

    if ( isset($_POST['snnseo_title_tag']) ) {
        update_post_meta($post_id, 'snnseo_title_tag', sanitize_text_field($_POST['snnseo_title_tag']));
    }
    if ( isset($_POST['snnseo_meta_description']) ) {
        update_post_meta($post_id, 'snnseo_meta_description', sanitize_textarea_field($_POST['snnseo_meta_description']));
    }
    if ( isset($_POST['snnseo_focus_keyword']) ) {
        update_post_meta($post_id, 'snnseo_focus_keyword', sanitize_text_field($_POST['snnseo_focus_keyword']));
    }
    update_post_meta($post_id, 'snnseo_robots_noindex', isset($_POST['snnseo_robots_noindex']) ? '1' : '');
    if ( isset($_POST['snnseo_og_image']) ) {
        update_post_meta($post_id, 'snnseo_og_image', esc_url_raw($_POST['snnseo_og_image']));
    }
}

/* =============================================================================
   6. Dynamic Title and Meta Tag Output
============================================================================= */
add_filter('pre_get_document_title', 'snnseo_childtheme_dynamic_title');
function snnseo_childtheme_dynamic_title($title) {
    if ( ! get_option('snnseo_enable') ) {
        return $title;
    }

    // 1) Singular
    if ( is_singular() ) {
        global $post;
        $post_type = get_post_type($post);
        $custom_title = get_post_meta($post->ID, 'snnseo_title_tag', true);
        $selected_post_types = (array) get_option('snnseo_selected_post_types', array());
        if ( in_array($post_type, $selected_post_types) && ! empty($custom_title) ) {
            $template = get_option("snnseo_title_template_{$post_type}", '%%title%% - %%sitename%%');
            $title = snnseo_childtheme_parse_dynamic_tags($template, $post);
        }
    }
    // 2) Taxonomy Archive (category, tag, custom taxonomy)
    elseif ( is_tax() || is_category() || is_tag() ) {
        $term = get_queried_object();
        $taxonomy = $term->taxonomy;
        $selected_taxonomies = (array) get_option('snnseo_selected_taxonomies', array());
        if ( in_array($taxonomy, $selected_taxonomies) ) {
            $template = get_option("snnseo_title_template_{$taxonomy}", '%%title%% - %%sitename%%');
            $title = snnseo_childtheme_parse_term_dynamic_tags($template, $term);
        }
    }
    // 3) Post Type Archive
    elseif ( is_post_type_archive() ) {
        $post_type = get_query_var('post_type');
        $selected_post_types = (array) get_option('snnseo_selected_post_types', array());
        if ( in_array($post_type, $selected_post_types) ) {
            $template = get_option("snnseo_title_template_{$post_type}", '%%title%% - %%sitename%%');
            $title = snnseo_childtheme_parse_pt_archive_tags($template, $post_type);
        }
    }
    // 4) Front/home
    elseif ( is_front_page() || is_home() ) {
        $site_title = get_option('snnseo_site_title', '');
        if ( ! empty($site_title) ) {
            $title = $site_title;
        }
    }

    return $title;
}

add_action('wp_head', 'snnseo_childtheme_output_meta_tags', 5);
function snnseo_childtheme_output_meta_tags() {
    if ( ! get_option('snnseo_enable') ) {
        return;
    }

    // Single posts/pages
    if ( is_singular() ) {
        global $post;
        $template_desc = get_option("snnseo_meta_desc_template_". get_post_type($post), '');
        $meta_desc     = get_post_meta($post->ID, 'snnseo_meta_description', true);

        if ( empty($meta_desc) && ! empty($template_desc) ) {
            $meta_desc = snnseo_childtheme_parse_dynamic_tags($template_desc, $post);
        }

        if ( ! empty($meta_desc) ) {
            echo '<meta name="description" content="' . esc_attr($meta_desc) . '" />' . "\n";
        }

        $robots_noindex = get_post_meta($post->ID, 'snnseo_robots_noindex', true);
        if ( $robots_noindex === '1' ) {
            echo '<meta name="robots" content="noindex, nofollow" />' . "\n";
        }

        $canonical_url = get_permalink($post);
        echo '<link rel="canonical" href="' . esc_url($canonical_url) . '" />' . "\n";

        $template_og_title = get_option('snnseo_og_title_template', '%%title%%');
        $template_og_desc  = get_option('snnseo_og_desc_template', '%%description%%');
        $og_title = snnseo_childtheme_parse_dynamic_tags($template_og_title, $post);
        $og_desc  = snnseo_childtheme_parse_dynamic_tags($template_og_desc, $post);
        $og_image = get_post_meta($post->ID, 'snnseo_og_image', true);

        echo '<meta property="og:title" content="' . esc_attr($og_title) . '" />' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($og_desc) . '" />' . "\n";
        echo '<meta property="og:url" content="' . esc_url($canonical_url) . '" />' . "\n";
        if ( ! empty($og_image) ) {
            echo '<meta property="og:image" content="' . esc_url($og_image) . '" />' . "\n";
        }

        echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($og_title) . '" />' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($og_desc) . '" />' . "\n";
        if ( ! empty($og_image) ) {
            echo '<meta name="twitter:image" content="' . esc_url($og_image) . '" />' . "\n";
        }
    }
    // Taxonomy Archive (category, tag, custom taxonomy)
    elseif ( is_tax() || is_category() || is_tag() ) {
        $term = get_queried_object();
        $taxonomy = $term->taxonomy;
        $selected_taxonomies = (array) get_option('snnseo_selected_taxonomies', array());
        if ( in_array($taxonomy, $selected_taxonomies) ) {
            $template_desc = get_option("snnseo_meta_desc_template_{$taxonomy}", '');
            $desc = snnseo_childtheme_parse_term_dynamic_tags($template_desc, $term);
            if ( ! empty($desc) ) {
                echo '<meta name="description" content="' . esc_attr($desc) . '" />' . "\n";
            }

            $canonical_url = get_term_link($term);
            if ( ! is_wp_error($canonical_url) ) {
                echo '<link rel="canonical" href="' . esc_url($canonical_url) . '" />' . "\n";
            }
        }
    }
    // Post Type Archive
    elseif ( is_post_type_archive() ) {
        $post_type = get_query_var('post_type');
        $selected_post_types = (array) get_option('snnseo_selected_post_types', array());
        if ( in_array($post_type, $selected_post_types) ) {
            $template_desc = get_option("snnseo_meta_desc_template_{$post_type}", '');
            $desc = snnseo_childtheme_parse_pt_archive_tags($template_desc, $post_type);
            if ( ! empty($desc) ) {
                echo '<meta name="description" content="' . esc_attr($desc) . '" />' . "\n";
            }

            $canonical_url = get_post_type_archive_link($post_type);
            if ( $canonical_url ) {
                echo '<link rel="canonical" href="' . esc_url($canonical_url) . '" />' . "\n";
            }
        }
    }
    // Front/Home
    elseif ( is_front_page() || is_home() ) {
        $home_meta = get_option('snnseo_home_meta_desc', '');
        if ( ! empty($home_meta) ) {
            echo '<meta name="description" content="' . esc_attr($home_meta) . '" />' . "\n";
        }
        $site_title = get_option('snnseo_site_title', '');
        if ( ! empty($site_title) ) {
            echo '<title>' . esc_html($site_title) . '</title>' . "\n";
        }
    }
}

/* =============================================================================
   7. Dynamic Tag Parsing Functions
============================================================================= */
function snnseo_childtheme_parse_dynamic_tags($template, $post) {
    if ( ! $post ) {
        return $template;
    }
    $replacements = array(
        '%%sitename%%'         => get_bloginfo('name'),
        '%%title%%'            => get_the_title($post),
        '%%excerpt%%'          => has_excerpt($post->ID) ? get_the_excerpt($post) : '',
        '%%description%%'      => ( get_post_meta($post->ID, 'snnseo_meta_description', true) )
                                    ? get_post_meta($post->ID, 'snnseo_meta_description', true)
                                    : ( has_excerpt($post->ID) ? get_the_excerpt($post) : '' ),
        '%%date%%'             => get_the_date('', $post),
        '%%url%%'              => get_permalink($post),
        // New dynamic tags
        '%%separator%%'        => ' | ',
        '%%tagline%%'          => get_bloginfo('description'),
        '%%post_modified%%'    => get_the_modified_time('c', $post),
        '%%thumbnail%%'        => get_the_post_thumbnail_url($post, 'full'),
        '%%author_first_name%%'=> get_the_author_meta('first_name', $post->post_author),
        '%%author_last_name%%' => get_the_author_meta('last_name', $post->post_author),
    );
    $template = strtr($template, $replacements);
    // Process custom field dynamic tags, e.g. %%cf_customfield%%
    $template = preg_replace_callback('/%%cf_([a-zA-Z0-9_]+)%%/', function($matches) use ($post) {
        return get_post_meta($post->ID, 'cf_' . $matches[1], true);
    }, $template);
    return $template;
}

function snnseo_childtheme_parse_term_dynamic_tags($template, $term) {
    if ( ! $term || ! is_object($term) ) {
        return $template;
    }
    $replacements = array(
        '%%sitename%%'    => get_bloginfo('name'),
        '%%title%%'       => $term->name,
        '%%description%%' => $term->description,
        '%%url%%'         => (! is_wp_error(get_term_link($term))) ? get_term_link($term) : '',
        '%%tagline%%'     => get_bloginfo('description'),
    );
    return strtr($template, $replacements);
}

function snnseo_childtheme_parse_pt_archive_tags($template, $post_type) {
    $obj = get_post_type_object($post_type);
    if ( ! $obj ) {
        return $template;
    }
    $replacements = array(
        '%%sitename%%' => get_bloginfo('name'),
        '%%title%%'    => $obj->labels->name,
        '%%url%%'      => get_post_type_archive_link($post_type),
    );
    return strtr($template, $replacements);
}

/* =============================================================================
   8. Sitemaps (Index & Sub-Sitemaps)
============================================================================= */
add_action('init', 'snnseo_childtheme_sitemap_init');
function snnseo_childtheme_sitemap_init() {
    if ( ! get_option('snnseo_enable_sitemap') ) {
        return;
    }

    add_rewrite_rule('^sitemap\.xml$', 'index.php?snn_sitemap=index', 'top');
    add_rewrite_rule('^([^/]*)-sitemap([0-9]+)\.xml$', 'index.php?snn_sitemap=sub&snn_sitemap_type=$matches[1]&snn_sitemap_page=$matches[2]', 'top');
}

add_filter('query_vars', 'snnseo_childtheme_sitemap_query_vars');
function snnseo_childtheme_sitemap_query_vars($vars) {
    $vars[] = 'snn_sitemap';
    $vars[] = 'snn_sitemap_type';
    $vars[] = 'snn_sitemap_page';
    return $vars;
}

add_action('template_redirect', 'snnseo_childtheme_sitemap_template_redirect');
function snnseo_childtheme_sitemap_template_redirect() {
    $snn_sitemap = get_query_var('snn_sitemap');
    if ( $snn_sitemap === 'index' ) {
        snnseo_childtheme_output_sitemap_index();
        exit;
    } elseif ( $snn_sitemap === 'sub' ) {
        $type = get_query_var('snn_sitemap_type');
        $page = get_query_var('snn_sitemap_page');
        snnseo_childtheme_output_sub_sitemap($type, $page);
        exit;
    }
}

function snnseo_childtheme_output_sitemap_index() {
    while ( ob_get_level() ) {
        ob_end_clean();
    }
    header('Content-Type: application/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

    $post_types = get_post_types(array(
        'public'             => true,
        'exclude_from_search' => false,
    ), 'names');
    $taxonomies = get_taxonomies(array('public' => true), 'names');
    $base_url = site_url();
    echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    foreach ( $post_types as $pt ) {
        $loc = trailingslashit($base_url) . "{$pt}-sitemap1.xml";
        echo "  <sitemap>\n";
        echo "    <loc>" . esc_url($loc) . "</loc>\n";
        echo "  </sitemap>\n";
    }

    foreach ( $taxonomies as $tax ) {
        $loc = trailingslashit($base_url) . "{$tax}-sitemap1.xml";
        echo "  <sitemap>\n";
        echo "    <loc>" . esc_url($loc) . "</loc>\n";
        echo "  </sitemap>\n";
    }

    echo '</sitemapindex>';
}

function snnseo_childtheme_output_sub_sitemap($type, $page) {
    while ( ob_get_level() ) {
        ob_end_clean();
    }
    header('Content-Type: application/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

    $post_types = get_post_types(array(
        'public'             => true,
        'exclude_from_search' => false
    ), 'names');
    $taxonomies = get_taxonomies(array('public' => true), 'names');

    $page     = intval($page);
    if ($page < 1) {
        $page = 1;
    }
    $per_page = 1000;
    $offset   = ($page - 1) * $per_page;

    if ( in_array($type, $post_types) ) {
        $items = get_posts(array(
            'post_type'   => $type,
            'post_status' => 'publish',
            'numberposts' => $per_page,
            'offset'      => $offset,
        ));
        echo snnseo_childtheme_build_sitemap_xml_for_posts($items);
    } elseif ( in_array($type, $taxonomies) ) {
        $terms = get_terms(array(
            'taxonomy'   => $type,
            'hide_empty' => true,
            'number'     => $per_page,
            'offset'     => $offset,
        ));
        echo snnseo_childtheme_build_sitemap_xml_for_terms($terms);
    } else {
        echo '<!-- Unknown sitemap type -->';
    }
    exit;
}

function snnseo_childtheme_build_sitemap_xml_for_posts($posts) {
    $xml  = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ( $posts as $post ) {
        $url     = get_permalink($post);
        $lastmod = get_the_modified_time('c', $post);
        $xml    .= "  <url>\n";
        $xml    .= "    <loc>" . esc_url($url) . "</loc>\n";
        $xml    .= "    <lastmod>" . esc_html($lastmod) . "</lastmod>\n";
        $xml    .= "  </url>\n";
    }
    $xml .= '</urlset>';
    return $xml;
}

function snnseo_childtheme_build_sitemap_xml_for_terms($terms) {
    $xml  = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ( $terms as $term ) {
        $url = get_term_link($term);
        if ( is_wp_error($url) ) {
            continue;
        }
        $xml .= "  <url>\n";
        $xml .= "    <loc>" . esc_url($url) . "</loc>\n";
        $xml .= "    <lastmod>" . date('c') . "</lastmod>\n";
        $xml .= "  </url>\n";
    }
    $xml .= '</urlset>';
    return $xml;
}

/* =============================================================================
   9. Add Sitemaps to robots.txt
============================================================================= */
add_filter('robots_txt', 'snnseo_add_sitemap_to_robots', 10, 2);
function snnseo_add_sitemap_to_robots($output, $public) {
    if ( get_option('snnseo_enable_sitemap') ) {
        $output .= "\nSitemap: " . site_url('/sitemap.xml');
    }
    return $output;
}
