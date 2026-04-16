<?php 
/*

    When SEO Setting is enabled this code should 
    disable the Bricks Builder Settings Options;

    wp_options

    - Disable Bricks Open Graph meta tags, db option slug should be = "disableOpenGraph"
    - Disable Bricks SEO meta tags, db option slug should be = "disableSeo"

    We should only run the action just ones not multiple times 
    for keeping the action optimized no need to run multiple times.

*/


function snn_seo_is_enabled() {
    $seo_enabled = get_option('snn_seo_enabled', false);
    
    // The option is stored as boolean (true/false or 1/0)
    return (bool) $seo_enabled;
}

/**
 * Save default post type and taxonomy SEO selections when SEO is first enabled.
 * This prevents the double-save issue where users had to save twice because
 * the checkboxes for post types/taxonomies are hidden until SEO is enabled.
 */
function snn_seo_save_defaults_on_enable() {
    $default_post_types = ['post', 'page'];
    $default_taxonomies = ['category', 'post_tag'];

    $all_post_types = array_keys(get_post_types(['public' => true], 'names'));
    $all_taxonomies = array_keys(get_taxonomies(['public' => true], 'names'));

    $pt_defaults  = array_fill_keys($all_post_types, false);
    foreach ($default_post_types as $pt) {
        if (array_key_exists($pt, $pt_defaults)) {
            $pt_defaults[$pt] = true;
        }
    }

    $tax_defaults = array_fill_keys($all_taxonomies, false);
    foreach ($default_taxonomies as $tax) {
        if (array_key_exists($tax, $tax_defaults)) {
            $tax_defaults[$tax] = true;
        }
    }

    $post_types_enabled = get_option('snn_seo_post_types_enabled', []);
    if (empty($post_types_enabled) || !is_array($post_types_enabled)) {
        update_option('snn_seo_post_types_enabled', $pt_defaults);
    }

    $taxonomies_enabled = get_option('snn_seo_taxonomies_enabled', []);
    if (empty($taxonomies_enabled) || !is_array($taxonomies_enabled)) {
        update_option('snn_seo_taxonomies_enabled', $tax_defaults);
    }

    $sitemap_post_types = get_option('snn_seo_sitemap_post_types', []);
    if (empty($sitemap_post_types) || !is_array($sitemap_post_types)) {
        update_option('snn_seo_sitemap_post_types', $pt_defaults);
    }

    $sitemap_taxonomies = get_option('snn_seo_sitemap_taxonomies', []);
    if (empty($sitemap_taxonomies) || !is_array($sitemap_taxonomies)) {
        update_option('snn_seo_sitemap_taxonomies', $tax_defaults);
    }
}

/**
 * Disable Bricks SEO and OpenGraph settings and mark as done.
 */
function snn_apply_bricks_seo_disable() {
    $bricks_settings = get_option('bricks_global_settings', array());
    if (!empty($bricks_settings)) {
        $bricks_settings['disableOpenGraph'] = true;
        $bricks_settings['disableSeo'] = true;
        update_option('bricks_global_settings', $bricks_settings);
        update_option('snn_bricks_seo_disabled', 'yes');
    }
}

/**
 * Disable Bricks SEO and Open Graph settings when custom SEO is enabled
 * Runs only once to optimize performance
 */
function snn_disable_bricks_seo_settings() {
    // Check if our SEO feature is enabled
    if (!snn_seo_is_enabled()) {
        return;
    }
    
    // Check if we've already run this action
    $already_disabled = get_option('snn_bricks_seo_disabled', false);
    if ($already_disabled) {
        return;
    }
    
    snn_apply_bricks_seo_disable();
}
add_action('init', 'snn_disable_bricks_seo_settings');

/**
 * Reset flag and immediately disable Bricks SEO when SEO setting is updated.
 * Also saves default post type/taxonomy selections so users don't need a
 * second save to activate them (fixes the double-save issue).
 * This runs right when the option changes, before init hook.
 */
function snn_handle_seo_enabled_change($old_value, $new_value) {
    // If SEO is being enabled
    if ($new_value) {
        // Reset the flag so the disable function can run again
        delete_option('snn_bricks_seo_disabled');

        // Immediately disable Bricks SEO settings
        snn_apply_bricks_seo_disable();

        // Save defaults for post types/taxonomies so they take effect immediately
        // without requiring a second save.
        snn_seo_save_defaults_on_enable();
    } else {
        // If SEO is being disabled, just reset the flag
        delete_option('snn_bricks_seo_disabled');
    }
}
add_action('update_option_snn_seo_enabled', 'snn_handle_seo_enabled_change', 10, 2);

/**
 * Handle the very first time snn_seo_enabled is saved to the database.
 * WordPress fires added_option instead of update_option_{option}
 * when the option does not exist yet.
 */
function snn_handle_seo_enabled_added($option, $value) {
    if ($option !== 'snn_seo_enabled') {
        return;
    }
    if ($value) {
        delete_option('snn_bricks_seo_disabled');
        snn_apply_bricks_seo_disable();
        snn_seo_save_defaults_on_enable();
    }
}
add_action('added_option', 'snn_handle_seo_enabled_added', 10, 2);



