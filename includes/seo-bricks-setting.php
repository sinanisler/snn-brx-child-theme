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
    
    return $seo_enabled === 'yes';
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
 * Re-enable check when SEO feature is toggled
 * This allows re-running the disable function if SEO is re-enabled later
 */
function snn_reset_bricks_seo_flag() {
    if (!snn_seo_is_enabled()) {
        delete_option('snn_bricks_seo_disabled');
    }
}
add_action('update_option_snn_seo_enabled', 'snn_reset_bricks_seo_flag');



