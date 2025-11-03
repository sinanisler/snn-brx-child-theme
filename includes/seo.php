<?php
/**
 * SEO Settings and Features
 * 
 * Features:
 * - Meta titles and descriptions for post types, archives, taxonomies, authors
 * - Dynamic tag replacement system
 * - XML Sitemap generation (paginated)
 * - Open Graph support
 * - Admin column previews
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Add SEO Settings submenu page
 */
function snn_seo_add_submenu_page() {
    add_submenu_page(
        'snn-settings',
        __('SEO Settings', 'snn'),
        __('SEO Settings', 'snn'),
        'manage_options',
        'snn-seo-settings',
        'snn_seo_settings_page_callback'
    );
}
add_action('admin_menu', 'snn_seo_add_submenu_page');

/**
 * Register SEO settings
 */
function snn_seo_register_settings() {
    // Sanitization callback for arrays
    $sanitize_array = function($input) {
        return is_array($input) ? $input : [];
    };
    
    register_setting('snn_seo_settings_group', 'snn_seo_enabled', [
        'type' => 'boolean',
        'default' => false,
        'sanitize_callback' => 'rest_sanitize_boolean'
    ]);
    
    register_setting('snn_seo_settings_group', 'snn_seo_post_types_enabled', [
        'type' => 'array',
        'default' => [],
        'sanitize_callback' => $sanitize_array
    ]);
    
    register_setting('snn_seo_settings_group', 'snn_seo_taxonomies_enabled', [
        'type' => 'array',
        'default' => [],
        'sanitize_callback' => $sanitize_array
    ]);
    
    register_setting('snn_seo_settings_group', 'snn_seo_authors_enabled', [
        'type' => 'boolean',
        'default' => true,
        'sanitize_callback' => 'rest_sanitize_boolean'
    ]);
    
    register_setting('snn_seo_settings_group', 'snn_seo_post_type_titles', [
        'type' => 'array',
        'default' => [],
        'sanitize_callback' => $sanitize_array
    ]);
    
    register_setting('snn_seo_settings_group', 'snn_seo_post_type_descriptions', [
        'type' => 'array',
        'default' => [],
        'sanitize_callback' => $sanitize_array
    ]);
    
    register_setting('snn_seo_settings_group', 'snn_seo_archive_titles', [
        'type' => 'array',
        'default' => [],
        'sanitize_callback' => $sanitize_array
    ]);
    
    register_setting('snn_seo_settings_group', 'snn_seo_archive_descriptions', [
        'type' => 'array',
        'default' => [],
        'sanitize_callback' => $sanitize_array
    ]);
    
    register_setting('snn_seo_settings_group', 'snn_seo_taxonomy_titles', [
        'type' => 'array',
        'default' => [],
        'sanitize_callback' => $sanitize_array
    ]);
    
    register_setting('snn_seo_settings_group', 'snn_seo_taxonomy_descriptions', [
        'type' => 'array',
        'default' => [],
        'sanitize_callback' => $sanitize_array
    ]);
    
    register_setting('snn_seo_settings_group', 'snn_seo_author_title', [
        'type' => 'string',
        'default' => '{author_name} - {site_title}',
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    
    register_setting('snn_seo_settings_group', 'snn_seo_author_description', [
        'type' => 'string',
        'default' => 'Author archive for {author_name}',
        'sanitize_callback' => 'sanitize_textarea_field'
    ]);
    
    register_setting('snn_seo_settings_group', 'snn_seo_sitemap_enabled', [
        'type' => 'boolean',
        'default' => true,
        'sanitize_callback' => 'rest_sanitize_boolean'
    ]);
    
    register_setting('snn_seo_settings_group', 'snn_seo_sitemap_post_types', [
        'type' => 'array',
        'default' => [],
        'sanitize_callback' => $sanitize_array
    ]);
    
    register_setting('snn_seo_settings_group', 'snn_seo_sitemap_taxonomies', [
        'type' => 'array',
        'default' => [],
        'sanitize_callback' => $sanitize_array
    ]);
    
    register_setting('snn_seo_settings_group', 'snn_seo_opengraph_enabled', [
        'type' => 'boolean',
        'default' => true,
        'sanitize_callback' => 'rest_sanitize_boolean'
    ]);
    
    register_setting('snn_seo_settings_group', 'snn_seo_post_meta_titles');
    register_setting('snn_seo_settings_group', 'snn_seo_post_meta_descriptions');
}
add_action('admin_init', 'snn_seo_register_settings');

/**
 * SEO Settings Page Callback
 */
function snn_seo_settings_page_callback() {
    // Get all public post types
    $post_types = get_post_types(['public' => true], 'objects');
    $post_types = array_filter($post_types, function($pt) {
        return !in_array($pt->name, ['attachment']);
    });
    
    // Get all public taxonomies
    $taxonomies = get_taxonomies(['public' => true], 'objects');
    
    // Get current settings with proper type checking
    $seo_enabled = get_option('snn_seo_enabled', false);
    $post_types_enabled = get_option('snn_seo_post_types_enabled', []);
    $taxonomies_enabled = get_option('snn_seo_taxonomies_enabled', []);
    $authors_enabled = get_option('snn_seo_authors_enabled', true);
    $post_type_titles = get_option('snn_seo_post_type_titles', []);
    $post_type_descriptions = get_option('snn_seo_post_type_descriptions', []);
    $archive_titles = get_option('snn_seo_archive_titles', []);
    $archive_descriptions = get_option('snn_seo_archive_descriptions', []);
    $taxonomy_titles = get_option('snn_seo_taxonomy_titles', []);
    $taxonomy_descriptions = get_option('snn_seo_taxonomy_descriptions', []);
    $author_title = get_option('snn_seo_author_title', '{author_name} - {site_title}');
    $author_description = get_option('snn_seo_author_description', __('Author archive for {author_name}', 'snn'));
    $sitemap_enabled = get_option('snn_seo_sitemap_enabled', true);
    $sitemap_post_types = get_option('snn_seo_sitemap_post_types', []);
    $sitemap_taxonomies = get_option('snn_seo_sitemap_taxonomies', []);
    $opengraph_enabled = get_option('snn_seo_opengraph_enabled', true);
    
    // Ensure arrays are actually arrays (fix for string/serialization issues)
    $post_types_enabled = is_array($post_types_enabled) ? $post_types_enabled : [];
    $taxonomies_enabled = is_array($taxonomies_enabled) ? $taxonomies_enabled : [];
    $post_type_titles = is_array($post_type_titles) ? $post_type_titles : [];
    $post_type_descriptions = is_array($post_type_descriptions) ? $post_type_descriptions : [];
    $archive_titles = is_array($archive_titles) ? $archive_titles : [];
    $archive_descriptions = is_array($archive_descriptions) ? $archive_descriptions : [];
    $taxonomy_titles = is_array($taxonomy_titles) ? $taxonomy_titles : [];
    $taxonomy_descriptions = is_array($taxonomy_descriptions) ? $taxonomy_descriptions : [];
    $sitemap_post_types = is_array($sitemap_post_types) ? $sitemap_post_types : [];
    $sitemap_taxonomies = is_array($sitemap_taxonomies) ? $sitemap_taxonomies : [];
    
    // Set defaults if empty
    if (empty($post_types_enabled)) {
        foreach ($post_types as $pt) {
            $post_types_enabled[$pt->name] = true;
        }
    }
    if (empty($taxonomies_enabled)) {
        foreach ($taxonomies as $tax) {
            $taxonomies_enabled[$tax->name] = true;
        }
    }
    if (empty($sitemap_post_types)) {
        foreach ($post_types as $pt) {
            $sitemap_post_types[$pt->name] = true;
        }
    }
    if (empty($sitemap_taxonomies)) {
        foreach ($taxonomies as $tax) {
            $sitemap_taxonomies[$tax->name] = true;
        }
    }
    
    ?>
    <div class="wrap">
        <h1><?php _e('SEO Settings', 'snn'); ?></h1>
        
        <form method="post" action="options.php">
            <?php settings_fields('snn_seo_settings_group'); ?>
            
            <!-- Master Enable/Disable -->
            <div style="background: #fff; padding: 20px; margin: 20px 0; border-left: 4px solid #2271b1;">
                <h2><?php _e('Enable SEO Features', 'snn'); ?></h2>
                <label>
                    <input type="checkbox" name="snn_seo_enabled" value="1" <?php checked($seo_enabled, 1); ?>>
                    <strong><?php _e('Enable all SEO features', 'snn'); ?></strong>
                </label>
                <p class="description">
                    <?php _e('When enabled, this will activate meta titles, descriptions, sitemap, and Open Graph features.', 'snn'); ?>
                </p>
            </div>

            <?php if ($seo_enabled): ?>
            
            <!-- Enable/Disable for Post Types -->
            <div style="background: #fff; padding: 20px; margin: 20px 0;">
                <h2><?php _e('Enable SEO for Post Types', 'snn'); ?></h2>
                <p class="description"><?php _e('Select which post types should have SEO features enabled.', 'snn'); ?></p>
                <?php foreach ($post_types as $post_type): ?>
                    <label style="display: block; margin: 10px 0;">
                        <input type="checkbox" 
                               name="snn_seo_post_types_enabled[<?php echo esc_attr($post_type->name); ?>]" 
                               value="1" 
                               <?php checked(isset($post_types_enabled[$post_type->name]) && $post_types_enabled[$post_type->name], 1); ?>>
                        <strong><?php echo esc_html($post_type->label); ?></strong> (<?php echo esc_html($post_type->name); ?>)
                    </label>
                <?php endforeach; ?>
            </div>

            <!-- Enable/Disable for Taxonomies -->
            <div style="background: #fff; padding: 20px; margin: 20px 0;">
                <h2><?php _e('Enable SEO for Taxonomies', 'snn'); ?></h2>
                <p class="description"><?php _e('Select which taxonomies should have SEO features enabled.', 'snn'); ?></p>
                <?php foreach ($taxonomies as $taxonomy): ?>
                    <label style="display: block; margin: 10px 0;">
                        <input type="checkbox" 
                               name="snn_seo_taxonomies_enabled[<?php echo esc_attr($taxonomy->name); ?>]" 
                               value="1" 
                               <?php checked(isset($taxonomies_enabled[$taxonomy->name]) && $taxonomies_enabled[$taxonomy->name], 1); ?>>
                        <strong><?php echo esc_html($taxonomy->label); ?></strong> (<?php echo esc_html($taxonomy->name); ?>)
                    </label>
                <?php endforeach; ?>
            </div>

            <!-- Enable/Disable for Authors -->
            <div style="background: #fff; padding: 20px; margin: 20px 0;">
                <h2><?php _e('Enable SEO for Authors', 'snn'); ?></h2>
                <label>
                    <input type="checkbox" name="snn_seo_authors_enabled" value="1" <?php checked($authors_enabled, 1); ?>>
                    <strong><?php _e('Enable SEO for author archives', 'snn'); ?></strong>
                </label>
            </div>

            <!-- Dynamic Tags Documentation -->
            <div style="background: #fffbcc; padding: 20px; margin: 20px 0; border-left: 4px solid #ffb900;">
                <h2><?php _e('Available Dynamic Tags', 'snn'); ?></h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <div>
                        <h3><?php _e('Site Tags:', 'snn'); ?></h3>
                        <code>{site_title}</code> - <?php _e('Site title', 'snn'); ?><br>
                        <code>{site_tagline}</code> - <?php _e('Site tagline', 'snn'); ?>
                    </div>
                    <div>
                        <h3><?php _e('Post Type Tags:', 'snn'); ?></h3>
                        <code>{post_title}</code> - <?php _e('Post title', 'snn'); ?><br>
                        <code>{post_url}</code> - <?php _e('Post URL', 'snn'); ?><br>
                        <code>{post_slug}</code> - <?php _e('Post slug', 'snn'); ?><br>
                        <code>{post_date}</code> - <?php _e('Post date', 'snn'); ?><br>
                        <code>{post_author}</code> - <?php _e('Post author', 'snn'); ?><br>
                        <code>{post_excerpt}</code> - <?php _e('Post excerpt', 'snn'); ?><br>
                        <code>{post_cat}</code> - <?php _e('First category', 'snn'); ?><br>
                        <code>{post_tag}</code> - <?php _e('First tag', 'snn'); ?><br>
                        <code>{post_taxonomyname}</code> - <?php _e('Replace with taxonomy name', 'snn'); ?><br>
                        <code>{post_customfield_fieldname}</code> - <?php _e('Replace fieldname', 'snn'); ?>
                    </div>
                    <div>
                        <h3><?php _e('Author Tags:', 'snn'); ?></h3>
                        <code>{author_name}</code> - <?php _e('Author name', 'snn'); ?><br>
                        <code>{author_customfield_fieldname}</code> - <?php _e('Replace fieldname', 'snn'); ?>
                    </div>
                    <div>
                        <h3><?php _e('Term Tags:', 'snn'); ?></h3>
                        <code>{term_name}</code> - <?php _e('Term name', 'snn'); ?><br>
                        <code>{term_desc}</code> - <?php _e('Term description', 'snn'); ?><br>
                        <code>{term_customfield_fieldname}</code> - <?php _e('Replace fieldname', 'snn'); ?>
                    </div>
                </div>
            </div>

            <!-- Post Type Single Templates -->
            <div style="background: #fff; padding: 20px; margin: 20px 0;">
                <h2><?php _e('Post Type Single Page Templates', 'snn'); ?></h2>
                <?php foreach ($post_types as $post_type): ?>
                    <?php if (!isset($post_types_enabled[$post_type->name]) || !$post_types_enabled[$post_type->name]) continue; ?>
                    <div style="margin: 20px 0; padding: 15px; background: #f9f9f9;">
                        <h3><?php echo esc_html($post_type->label); ?> (<?php echo esc_html($post_type->name); ?>)</h3>
                        
                        <label style="display: block; margin: 10px 0;">
                            <strong><?php _e('Meta Title Template:', 'snn'); ?></strong><br>
                            <input type="text" 
                                   name="snn_seo_post_type_titles[<?php echo esc_attr($post_type->name); ?>]" 
                                   value="<?php echo esc_attr(isset($post_type_titles[$post_type->name]) ? $post_type_titles[$post_type->name] : '{post_title} - {site_title}'); ?>" 
                                   style="width: 100%; max-width: 600px;">
                        </label>
                        
                        <label style="display: block; margin: 10px 0;">
                            <strong><?php _e('Meta Description Template:', 'snn'); ?></strong><br>
                            <textarea name="snn_seo_post_type_descriptions[<?php echo esc_attr($post_type->name); ?>]" 
                                      style="width: 100%; max-width: 600px; height: 80px;"><?php 
                                echo esc_textarea(isset($post_type_descriptions[$post_type->name]) ? $post_type_descriptions[$post_type->name] : '{post_excerpt}'); 
                            ?></textarea>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Post Type Archive Templates -->
            <div style="background: #fff; padding: 20px; margin: 20px 0;">
                <h2><?php _e('Post Type Archive Templates', 'snn'); ?></h2>
                <?php foreach ($post_types as $post_type): ?>
                    <?php if (!isset($post_types_enabled[$post_type->name]) || !$post_types_enabled[$post_type->name]) continue; ?>
                    <?php if (!$post_type->has_archive) continue; ?>
                    <div style="margin: 20px 0; padding: 15px; background: #f9f9f9;">
                        <h3><?php echo esc_html($post_type->label); ?> <?php _e('Archive', 'snn'); ?></h3>
                        
                        <label style="display: block; margin: 10px 0;">
                            <strong><?php _e('Meta Title Template:', 'snn'); ?></strong><br>
                            <input type="text" 
                                   name="snn_seo_archive_titles[<?php echo esc_attr($post_type->name); ?>]" 
                                   value="<?php echo esc_attr(isset($archive_titles[$post_type->name]) ? $archive_titles[$post_type->name] : '{post_title} ' . __('Archive', 'snn') . ' - {site_title}'); ?>" 
                                   style="width: 100%; max-width: 600px;">
                        </label>
                        
                        <label style="display: block; margin: 10px 0;">
                            <strong><?php _e('Meta Description Template:', 'snn'); ?></strong><br>
                            <textarea name="snn_seo_archive_descriptions[<?php echo esc_attr($post_type->name); ?>]" 
                                      style="width: 100%; max-width: 600px; height: 80px;"><?php 
                                echo esc_textarea(isset($archive_descriptions[$post_type->name]) ? $archive_descriptions[$post_type->name] : __('Browse all', 'snn') . ' {post_title}'); 
                            ?></textarea>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Taxonomy Templates -->
            <div style="background: #fff; padding: 20px; margin: 20px 0;">
                <h2><?php _e('Taxonomy Archive Templates', 'snn'); ?></h2>
                <?php foreach ($taxonomies as $taxonomy): ?>
                    <?php if (!isset($taxonomies_enabled[$taxonomy->name]) || !$taxonomies_enabled[$taxonomy->name]) continue; ?>
                    <div style="margin: 20px 0; padding: 15px; background: #f9f9f9;">
                        <h3><?php echo esc_html($taxonomy->label); ?> (<?php echo esc_html($taxonomy->name); ?>)</h3>
                        
                        <label style="display: block; margin: 10px 0;">
                            <strong><?php _e('Meta Title Template:', 'snn'); ?></strong><br>
                            <input type="text" 
                                   name="snn_seo_taxonomy_titles[<?php echo esc_attr($taxonomy->name); ?>]" 
                                   value="<?php echo esc_attr(isset($taxonomy_titles[$taxonomy->name]) ? $taxonomy_titles[$taxonomy->name] : '{term_name} - {site_title}'); ?>" 
                                   style="width: 100%; max-width: 600px;">
                        </label>
                        
                        <label style="display: block; margin: 10px 0;">
                            <strong><?php _e('Meta Description Template:', 'snn'); ?></strong><br>
                            <textarea name="snn_seo_taxonomy_descriptions[<?php echo esc_attr($taxonomy->name); ?>]" 
                                      style="width: 100%; max-width: 600px; height: 80px;"><?php 
                                echo esc_textarea(isset($taxonomy_descriptions[$taxonomy->name]) ? $taxonomy_descriptions[$taxonomy->name] : '{term_desc}'); 
                            ?></textarea>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Author Templates -->
            <?php if ($authors_enabled): ?>
            <div style="background: #fff; padding: 20px; margin: 20px 0;">
                <h2><?php _e('Author Archive Templates', 'snn'); ?></h2>
                
                <label style="display: block; margin: 10px 0;">
                    <strong><?php _e('Meta Title Template:', 'snn'); ?></strong><br>
                    <input type="text" 
                           name="snn_seo_author_title" 
                           value="<?php echo esc_attr($author_title); ?>" 
                           style="width: 100%; max-width: 600px;">
                </label>
                
                <label style="display: block; margin: 10px 0;">
                    <strong><?php _e('Meta Description Template:', 'snn'); ?></strong><br>
                    <textarea name="snn_seo_author_description" 
                              style="width: 100%; max-width: 600px; height: 80px;"><?php 
                        echo esc_textarea($author_description); 
                    ?></textarea>
                </label>
            </div>
            <?php endif; ?>

            <!-- Sitemap Settings -->
            <div style="background: #fff; padding: 20px; margin: 20px 0;">
                <h2><?php _e('XML Sitemap Settings', 'snn'); ?></h2>
                
                <label style="display: block; margin: 15px 0;">
                    <input type="checkbox" name="snn_seo_sitemap_enabled" value="1" <?php checked($sitemap_enabled, 1); ?>>
                    <strong><?php _e('Enable XML Sitemap', 'snn'); ?></strong>
                </label>
                <p class="description">
                    <?php _e('Sitemap URL:', 'snn'); ?> <code><?php echo home_url('/sitemap.xml'); ?></code><br>
                    <?php _e('Paginated with max 100 links per page. Each post type and taxonomy gets its own sitemap.', 'snn'); ?>
                </p>

                <?php if ($sitemap_enabled): ?>
                <div style="margin: 20px 0;">
                    <h3><?php _e('Include Post Types in Sitemap:', 'snn'); ?></h3>
                    <?php foreach ($post_types as $post_type): ?>
                        <label style="display: block; margin: 10px 0;">
                            <input type="checkbox" 
                                   name="snn_seo_sitemap_post_types[<?php echo esc_attr($post_type->name); ?>]" 
                                   value="1" 
                                   <?php checked(isset($sitemap_post_types[$post_type->name]) && $sitemap_post_types[$post_type->name], 1); ?>>
                            <?php echo esc_html($post_type->label); ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div style="margin: 20px 0;">
                    <h3><?php _e('Include Taxonomies in Sitemap:', 'snn'); ?></h3>
                    <?php foreach ($taxonomies as $taxonomy): ?>
                        <label style="display: block; margin: 10px 0;">
                            <input type="checkbox" 
                                   name="snn_seo_sitemap_taxonomies[<?php echo esc_attr($taxonomy->name); ?>]" 
                                   value="1" 
                                   <?php checked(isset($sitemap_taxonomies[$taxonomy->name]) && $sitemap_taxonomies[$taxonomy->name], 1); ?>>
                            <?php echo esc_html($taxonomy->label); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Open Graph Settings -->
            <div style="background: #fff; padding: 20px; margin: 20px 0;">
                <h2><?php _e('Open Graph Settings', 'snn'); ?></h2>
                <label>
                    <input type="checkbox" name="snn_seo_opengraph_enabled" value="1" <?php checked($opengraph_enabled, 1); ?>>
                    <strong><?php _e('Enable Open Graph meta tags', 'snn'); ?></strong>
                </label>
                <p class="description">
                    <?php _e('Adds Open Graph meta tags for better social media sharing (Facebook, Twitter, LinkedIn, etc.)', 'snn'); ?>
                </p>
            </div>

            <?php endif; // End if SEO enabled ?>

            <?php submit_button(__('Save SEO Settings', 'snn')); ?>
        </form>
    </div>

    <style>
        .wrap h2 { margin-top: 10px; }
        .wrap code { 
            background: #f0f0f1; 
            padding: 2px 6px; 
            border-radius: 3px; 
            font-size: 13px;
            display: inline-block;
            margin: 2px 0;
        }
    </style>
    <?php
}

/**
 * Replace dynamic tags with actual content
 */
function snn_seo_replace_tags($template, $context = []) {
    if (empty($template) || !is_string($template)) {
        return '';
    }
    
    // Site tags
    $template = str_replace('{site_title}', get_bloginfo('name'), $template);
    $template = str_replace('{site_tagline}', get_bloginfo('description'), $template);
    
    // Post type tags
    if (isset($context['post_id'])) {
        $post = get_post($context['post_id']);
        if ($post) {
            $template = str_replace('{post_title}', get_the_title($post), $template);
            $template = str_replace('{post_url}', get_permalink($post), $template);
            $template = str_replace('{post_slug}', $post->post_name, $template);
            $template = str_replace('{post_date}', get_the_date('', $post), $template);
            $template = str_replace('{post_author}', get_the_author_meta('display_name', $post->post_author), $template);
            
            $excerpt = get_the_excerpt($post);
            $template = str_replace('{post_excerpt}', wp_trim_words($excerpt, 30), $template);
            
            // Categories
            $categories = get_the_category($post->ID);
            $first_cat = !empty($categories) ? $categories[0]->name : '';
            $template = str_replace('{post_cat}', $first_cat, $template);
            
            // Tags
            $tags = get_the_tags($post->ID);
            $first_tag = !empty($tags) ? array_values($tags)[0]->name : '';
            $template = str_replace('{post_tag}', $first_tag, $template);
            
            // Custom taxonomies - replace {post_taxonomyname}
            preg_match_all('/{post_([a-z_]+)}/', $template, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $tax_name) {
                    if (taxonomy_exists($tax_name)) {
                        $terms = get_the_terms($post->ID, $tax_name);
                        $first_term = !empty($terms) && !is_wp_error($terms) ? $terms[0]->name : '';
                        $template = str_replace('{post_' . $tax_name . '}', $first_term, $template);
                    }
                }
            }
            
            // Custom fields - replace {post_customfield_fieldname}
            preg_match_all('/{post_customfield_([a-z_]+)}/', $template, $cf_matches);
            if (!empty($cf_matches[1])) {
                foreach ($cf_matches[1] as $field_name) {
                    $field_value = get_post_meta($post->ID, $field_name, true);
                    $field_value = is_string($field_value) ? $field_value : '';
                    $template = str_replace('{post_customfield_' . $field_name . '}', $field_value, $template);
                }
            }
        }
    }
    
    // Author tags
    if (isset($context['author_id'])) {
        $author_id = $context['author_id'];
        $template = str_replace('{author_name}', get_the_author_meta('display_name', $author_id), $template);
        
        // Author custom fields - replace {author_customfield_fieldname}
        preg_match_all('/{author_customfield_([a-z_]+)}/', $template, $author_cf_matches);
        if (!empty($author_cf_matches[1])) {
            foreach ($author_cf_matches[1] as $field_name) {
                $field_value = get_user_meta($author_id, $field_name, true);
                $field_value = is_string($field_value) ? $field_value : '';
                $template = str_replace('{author_customfield_' . $field_name . '}', $field_value, $template);
            }
        }
    }
    
    // Term tags
    if (isset($context['term_id']) && isset($context['taxonomy'])) {
        $term = get_term($context['term_id'], $context['taxonomy']);
        if ($term && !is_wp_error($term)) {
            $template = str_replace('{term_name}', $term->name, $template);
            $template = str_replace('{term_desc}', $term->description, $template);
            
            // Term custom fields - replace {term_customfield_fieldname}
            preg_match_all('/{term_customfield_([a-z_]+)}/', $template, $term_cf_matches);
            if (!empty($term_cf_matches[1])) {
                foreach ($term_cf_matches[1] as $field_name) {
                    $field_value = get_term_meta($term->term_id, $field_name, true);
                    $field_value = is_string($field_value) ? $field_value : '';
                    $template = str_replace('{term_customfield_' . $field_name . '}', $field_value, $template);
                }
            }
        }
    }
    
    return $template;
}

/**
 * Output SEO meta tags in <head>
 */
function snn_seo_output_meta_tags() {
    if (!get_option('snn_seo_enabled')) return;
    
    $title = '';
    $description = '';
    $context = [];
    
    // Single post
    if (is_singular()) {
        global $post;
        if (!$post) return;
        
        $post_type = get_post_type();
        $post_types_enabled = get_option('snn_seo_post_types_enabled', []);
        
        // Ensure array
        if (!is_array($post_types_enabled)) {
            $post_types_enabled = [];
        }
        
        if (isset($post_types_enabled[$post_type]) && $post_types_enabled[$post_type]) {
            // Check for custom meta first
            $custom_title = get_post_meta($post->ID, '_snn_seo_title', true);
            $custom_desc = get_post_meta($post->ID, '_snn_seo_description', true);
            
            $context = ['post_id' => $post->ID];
            
            if ($custom_title) {
                $title = snn_seo_replace_tags($custom_title, $context);
            } else {
                $post_type_titles = get_option('snn_seo_post_type_titles', []);
                if (!is_array($post_type_titles)) {
                    $post_type_titles = [];
                }
                $template = isset($post_type_titles[$post_type]) ? $post_type_titles[$post_type] : '{post_title} - {site_title}';
                $title = snn_seo_replace_tags($template, $context);
            }
            
            if ($custom_desc) {
                $description = snn_seo_replace_tags($custom_desc, $context);
            } else {
                $post_type_descriptions = get_option('snn_seo_post_type_descriptions', []);
                if (!is_array($post_type_descriptions)) {
                    $post_type_descriptions = [];
                }
                $template = isset($post_type_descriptions[$post_type]) ? $post_type_descriptions[$post_type] : '{post_excerpt}';
                $description = snn_seo_replace_tags($template, $context);
            }
        }
    }
    // Post type archive
    elseif (is_post_type_archive()) {
        $post_type = get_query_var('post_type');
        $post_types_enabled = get_option('snn_seo_post_types_enabled', []);
        
        // Ensure array
        if (!is_array($post_types_enabled)) {
            $post_types_enabled = [];
        }
        
        if (isset($post_types_enabled[$post_type]) && $post_types_enabled[$post_type]) {
            $archive_titles = get_option('snn_seo_archive_titles', []);
            $archive_descriptions = get_option('snn_seo_archive_descriptions', []);
            
            // Ensure arrays
            if (!is_array($archive_titles)) {
                $archive_titles = [];
            }
            if (!is_array($archive_descriptions)) {
                $archive_descriptions = [];
            }
            
            $title_template = isset($archive_titles[$post_type]) ? $archive_titles[$post_type] : '{post_title} ' . __('Archive', 'snn') . ' - {site_title}';
            $desc_template = isset($archive_descriptions[$post_type]) ? $archive_descriptions[$post_type] : __('Browse all', 'snn') . ' {post_title}';
            
            $title = snn_seo_replace_tags($title_template);
            $description = snn_seo_replace_tags($desc_template);
        }
    }
    // Taxonomy archive
    elseif (is_tax() || is_category() || is_tag()) {
        $term = get_queried_object();
        if (!$term) return;
        
        $taxonomies_enabled = get_option('snn_seo_taxonomies_enabled', []);
        
        // Ensure array
        if (!is_array($taxonomies_enabled)) {
            $taxonomies_enabled = [];
        }
        
        if ($term && isset($taxonomies_enabled[$term->taxonomy]) && $taxonomies_enabled[$term->taxonomy]) {
            $context = ['term_id' => $term->term_id, 'taxonomy' => $term->taxonomy];
            
            $taxonomy_titles = get_option('snn_seo_taxonomy_titles', []);
            $taxonomy_descriptions = get_option('snn_seo_taxonomy_descriptions', []);
            
            // Ensure arrays
            if (!is_array($taxonomy_titles)) {
                $taxonomy_titles = [];
            }
            if (!is_array($taxonomy_descriptions)) {
                $taxonomy_descriptions = [];
            }
            
            $title_template = isset($taxonomy_titles[$term->taxonomy]) ? $taxonomy_titles[$term->taxonomy] : '{term_name} - {site_title}';
            $desc_template = isset($taxonomy_descriptions[$term->taxonomy]) ? $taxonomy_descriptions[$term->taxonomy] : '{term_desc}';
            
            $title = snn_seo_replace_tags($title_template, $context);
            $description = snn_seo_replace_tags($desc_template, $context);
        }
    }
    // Author archive
    elseif (is_author()) {
        $authors_enabled = get_option('snn_seo_authors_enabled');
        
        if ($authors_enabled) {
            $author_id = get_query_var('author');
            $context = ['author_id' => $author_id];
            
            $author_title = get_option('snn_seo_author_title', '{author_name} - {site_title}');
            $author_description = get_option('snn_seo_author_description', __('Author archive for {author_name}', 'snn'));
            
            $title = snn_seo_replace_tags($author_title, $context);
            $description = snn_seo_replace_tags($author_description, $context);
        }
    }
    
    // Output meta tags
    if ($title) {
        echo '<title>' . esc_html($title) . '</title>' . "\n";
        echo '<meta name="title" content="' . esc_attr($title) . '">' . "\n";
    }
    
    if ($description) {
        echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
    }
    
    // Open Graph tags
    if (get_option('snn_seo_opengraph_enabled')) {
        snn_seo_output_opengraph_tags($title, $description);
    }
}
add_action('wp_head', 'snn_seo_output_meta_tags', 1);

/**
 * Output Open Graph meta tags
 */
function snn_seo_output_opengraph_tags($title = '', $description = '') {
    if (!$title) $title = get_bloginfo('name');
    if (!$description) $description = get_bloginfo('description');
    
    echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
    echo '<meta property="og:type" content="' . (is_singular() ? 'article' : 'website') . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url(get_permalink()) . '">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
    
    // Image
    if (is_singular() && has_post_thumbnail()) {
        $image_url = get_the_post_thumbnail_url(get_the_ID(), 'large');
        echo '<meta property="og:image" content="' . esc_url($image_url) . '">' . "\n";
    }
    
    // Twitter Card
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
    
    if (is_singular() && has_post_thumbnail()) {
        $image_url = get_the_post_thumbnail_url(get_the_ID(), 'large');
        echo '<meta name="twitter:image" content="' . esc_url($image_url) . '">' . "\n";
    }
}

/**
 * Add meta box for custom SEO per post
 */
function snn_seo_add_meta_box() {
    if (!get_option('snn_seo_enabled')) return;
    
    $post_types_enabled = get_option('snn_seo_post_types_enabled', []);
    
    foreach ($post_types_enabled as $post_type => $enabled) {
        if ($enabled) {
            add_meta_box(
                'snn_seo_meta_box',
                __('SEO Settings', 'snn'),
                'snn_seo_meta_box_callback',
                $post_type,
                'normal',
                'high'
            );
        }
    }
}
add_action('add_meta_boxes', 'snn_seo_add_meta_box');

/**
 * Meta box callback
 */
function snn_seo_meta_box_callback($post) {
    wp_nonce_field('snn_seo_meta_box', 'snn_seo_meta_box_nonce');
    
    $title = get_post_meta($post->ID, '_snn_seo_title', true);
    $description = get_post_meta($post->ID, '_snn_seo_description', true);
    
    ?>
    <div style="margin: 15px 0;">
        <label style="display: block; margin-bottom: 5px; font-weight: 600;">
            <?php _e('Meta Title', 'snn'); ?>
        </label>
        <input type="text" 
               name="snn_seo_title" 
               value="<?php echo esc_attr($title); ?>" 
               style="width: 100%;"
               placeholder="<?php _e('Leave empty to use template', 'snn'); ?>">
        <p class="description">
            <?php _e('Recommended length: 50-60 characters', 'snn'); ?> 
            (<span id="snn-title-count">0</span> <?php _e('characters', 'snn'); ?>)
        </p>
    </div>
    
    <div style="margin: 15px 0;">
        <label style="display: block; margin-bottom: 5px; font-weight: 600;">
            <?php _e('Meta Description', 'snn'); ?>
        </label>
        <textarea name="snn_seo_description" 
                  style="width: 100%; height: 80px;"
                  placeholder="<?php _e('Leave empty to use template', 'snn'); ?>"><?php 
            echo esc_textarea($description); 
        ?></textarea>
        <p class="description">
            <?php _e('Recommended length: 150-160 characters', 'snn'); ?> 
            (<span id="snn-desc-count">0</span> <?php _e('characters', 'snn'); ?>)
        </p>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        function updateCount() {
            var titleCount = $('input[name="snn_seo_title"]').val().length;
            var descCount = $('textarea[name="snn_seo_description"]').val().length;
            $('#snn-title-count').text(titleCount);
            $('#snn-desc-count').text(descCount);
        }
        
        $('input[name="snn_seo_title"], textarea[name="snn_seo_description"]').on('input', updateCount);
        updateCount();
    });
    </script>
    <?php
}

/**
 * Save meta box data
 */
function snn_seo_save_meta_box($post_id) {
    if (!isset($_POST['snn_seo_meta_box_nonce'])) return;
    if (!wp_verify_nonce($_POST['snn_seo_meta_box_nonce'], 'snn_seo_meta_box')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    if (isset($_POST['snn_seo_title'])) {
        update_post_meta($post_id, '_snn_seo_title', sanitize_text_field($_POST['snn_seo_title']));
    }
    
    if (isset($_POST['snn_seo_description'])) {
        update_post_meta($post_id, '_snn_seo_description', sanitize_textarea_field($_POST['snn_seo_description']));
    }
}
add_action('save_post', 'snn_seo_save_meta_box');

/**
 * Add SEO columns to post list
 */
function snn_seo_add_columns($columns) {
    if (!get_option('snn_seo_enabled')) return $columns;
    
    $new_columns = [];
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'title') {
            $new_columns['snn_seo_title'] = __('SEO Title', 'snn');
            $new_columns['snn_seo_description'] = __('SEO Description', 'snn');
        }
    }
    return $new_columns;
}

function snn_seo_column_content($column, $post_id) {
    if ($column === 'snn_seo_title') {
        $title = get_post_meta($post_id, '_snn_seo_title', true);
        if ($title) {
            $context = ['post_id' => $post_id];
            $title = snn_seo_replace_tags($title, $context);
            echo '<div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="' . esc_attr($title) . '">';
            echo esc_html($title);
            echo '</div>';
        } else {
            echo '<span style="color: #999;">' . __('Using template', 'snn') . '</span>';
        }
    }
    
    if ($column === 'snn_seo_description') {
        $description = get_post_meta($post_id, '_snn_seo_description', true);
        if ($description) {
            $context = ['post_id' => $post_id];
            $description = snn_seo_replace_tags($description, $context);
            echo '<div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="' . esc_attr($description) . '">';
            echo esc_html($description);
            echo '</div>';
        } else {
            echo '<span style="color: #999;">' . __('Using template', 'snn') . '</span>';
        }
    }
}

// Register columns for enabled post types
function snn_seo_register_columns() {
    if (!get_option('snn_seo_enabled')) return;
    
    $post_types_enabled = get_option('snn_seo_post_types_enabled', []);
    
    foreach ($post_types_enabled as $post_type => $enabled) {
        if ($enabled) {
            add_filter("manage_{$post_type}_posts_columns", 'snn_seo_add_columns');
            add_action("manage_{$post_type}_posts_custom_column", 'snn_seo_column_content', 10, 2);
        }
    }
}
add_action('admin_init', 'snn_seo_register_columns');

/**
 * XML Sitemap Generation
 */
function snn_seo_sitemap_init() {
    if (!get_option('snn_seo_enabled')) return;
    if (!get_option('snn_seo_sitemap_enabled')) return;
    
    add_rewrite_rule('^sitemap\.xml$', 'index.php?snn_sitemap=index', 'top');
    add_rewrite_rule('^sitemap-([a-z_]+)-([0-9]+)\.xml$', 'index.php?snn_sitemap=$matches[1]&snn_sitemap_page=$matches[2]', 'top');
    
    add_filter('query_vars', function($vars) {
        $vars[] = 'snn_sitemap';
        $vars[] = 'snn_sitemap_page';
        return $vars;
    });
    
    add_action('template_redirect', 'snn_seo_sitemap_output');
}
add_action('init', 'snn_seo_sitemap_init');

/**
 * Output sitemap
 */
function snn_seo_sitemap_output() {
    $sitemap = get_query_var('snn_sitemap');
    if (!$sitemap) return;
    
    header('Content-Type: application/xml; charset=utf-8');
    
    if ($sitemap === 'index') {
        snn_seo_sitemap_index();
    } else {
        $page = max(1, intval(get_query_var('snn_sitemap_page', 1)));
        snn_seo_sitemap_generate($sitemap, $page);
    }
    
    exit;
}

/**
 * Sitemap index
 */
function snn_seo_sitemap_index() {
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    
    $sitemap_post_types = get_option('snn_seo_sitemap_post_types', []);
    $sitemap_taxonomies = get_option('snn_seo_sitemap_taxonomies', []);
    
    // Ensure arrays
    if (!is_array($sitemap_post_types)) {
        $sitemap_post_types = [];
    }
    if (!is_array($sitemap_taxonomies)) {
        $sitemap_taxonomies = [];
    }
    
    // Post types
    foreach ($sitemap_post_types as $post_type => $enabled) {
        if (!$enabled || !post_type_exists($post_type)) continue;
        
        $count = wp_count_posts($post_type);
        $total = isset($count->publish) ? $count->publish : 0;
        $pages = ceil($total / 100);
        
        for ($i = 1; $i <= $pages; $i++) {
            echo '<sitemap>';
            echo '<loc>' . esc_url(home_url("/sitemap-{$post_type}-{$i}.xml")) . '</loc>';
            echo '<lastmod>' . date('Y-m-d\TH:i:s+00:00') . '</lastmod>';
            echo '</sitemap>';
        }
    }
    
    // Taxonomies
    foreach ($sitemap_taxonomies as $taxonomy => $enabled) {
        if (!$enabled || !taxonomy_exists($taxonomy)) continue;
        
        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false, 'fields' => 'ids']);
        $total = is_array($terms) ? count($terms) : 0;
        $pages = ceil($total / 100);
        
        for ($i = 1; $i <= $pages; $i++) {
            echo '<sitemap>';
            echo '<loc>' . esc_url(home_url("/sitemap-{$taxonomy}-{$i}.xml")) . '</loc>';
            echo '<lastmod>' . date('Y-m-d\TH:i:s+00:00') . '</lastmod>';
            echo '</sitemap>';
        }
    }
    
    echo '</sitemapindex>';
}

/**
 * Generate sitemap for specific type
 */
function snn_seo_sitemap_generate($type, $page = 1) {
    $per_page = 100;
    $offset = ($page - 1) * $per_page;
    
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    
    // Check if it's a post type
    if (post_type_exists($type)) {
        $posts = get_posts([
            'post_type' => $type,
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'offset' => $offset,
            'orderby' => 'modified',
            'order' => 'DESC'
        ]);
        
        if (!empty($posts) && is_array($posts)) {
            foreach ($posts as $post) {
                $permalink = get_permalink($post);
                if ($permalink) {
                    echo '<url>';
                    echo '<loc>' . esc_url($permalink) . '</loc>';
                    echo '<lastmod>' . date('Y-m-d\TH:i:s+00:00', strtotime($post->post_modified)) . '</lastmod>';
                    echo '<changefreq>weekly</changefreq>';
                    echo '<priority>0.8</priority>';
                    echo '</url>';
                }
            }
        }
    }
    // Check if it's a taxonomy
    elseif (taxonomy_exists($type)) {
        $terms = get_terms([
            'taxonomy' => $type,
            'hide_empty' => false,
            'number' => $per_page,
            'offset' => $offset
        ]);
        
        if (!is_wp_error($terms) && !empty($terms) && is_array($terms)) {
            foreach ($terms as $term) {
                $term_link = get_term_link($term);
                if (!is_wp_error($term_link)) {
                    echo '<url>';
                    echo '<loc>' . esc_url($term_link) . '</loc>';
                    echo '<lastmod>' . date('Y-m-d\TH:i:s+00:00') . '</lastmod>';
                    echo '<changefreq>weekly</changefreq>';
                    echo '<priority>0.6</priority>';
                    echo '</url>';
                }
            }
        }
    }
    
    echo '</urlset>';
}

/**
 * Flush rewrite rules on activation
 */
function snn_seo_flush_rules() {
    snn_seo_sitemap_init();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'snn_seo_flush_rules');
