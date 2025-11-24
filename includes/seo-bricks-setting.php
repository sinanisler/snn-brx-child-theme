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
    
    // Get current Bricks global settings
    $bricks_settings = get_option('bricks_global_settings', array());
    
    // If settings exist, update them
    if (!empty($bricks_settings)) {
        $bricks_settings['disableOpenGraph'] = true;
        $bricks_settings['disableSeo'] = true;
        
        // Update the option
        update_option('bricks_global_settings', $bricks_settings);
        
        // Set flag to prevent running again
        update_option('snn_bricks_seo_disabled', 'yes');
    }
}
add_action('init', 'snn_disable_bricks_seo_settings');

/**
 * Reset flag and immediately disable Bricks SEO when SEO setting is updated
 * This runs right when the option changes, before init hook
 */
function snn_handle_seo_enabled_change($old_value, $new_value) {
    // If SEO is being enabled
    if ($new_value) {
        // Reset the flag so the disable function can run again
        delete_option('snn_bricks_seo_disabled');
        
        // Immediately disable Bricks SEO settings
        $bricks_settings = get_option('bricks_global_settings', array());
        if (!empty($bricks_settings)) {
            $bricks_settings['disableOpenGraph'] = true;
            $bricks_settings['disableSeo'] = true;
            update_option('bricks_global_settings', $bricks_settings);
            update_option('snn_bricks_seo_disabled', 'yes');
        }
    } else {
        // If SEO is being disabled, just reset the flag
        delete_option('snn_bricks_seo_disabled');
    }
}
add_action('update_option_snn_seo_enabled', 'snn_handle_seo_enabled_change', 10, 2);



