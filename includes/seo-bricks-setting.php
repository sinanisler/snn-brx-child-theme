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


function snn_seo_settigns_is_enabled() {
    $seo_enabled = get_option('snn_seo_enabled', false);
    
    return $seo_enabled === 'yes';
}

/**
 * Disable Bricks Builder SEO settings when SNN SEO is enabled
 * Runs only once to optimize performance
 */
function snn_disable_bricks_seo_settings() {
    // Check if SEO feature is enabled
    if (!snn_seo_settigns_is_enabled()) {
        return;
    }
    
    // Check if we've already run this function
    $already_disabled = get_option('snn_bricks_seo_disabled', false);
    if ($already_disabled) {
        return;
    }
    
    // Get current Bricks settings
    $bricks_settings = get_option('bricks_settings', array());
    
    // Disable Bricks Open Graph meta tags
    $bricks_settings['disableOpenGraph'] = true;
    
    // Disable Bricks SEO meta tags
    $bricks_settings['disableSeo'] = true;
    
    // Update the settings
    update_option('bricks_settings', $bricks_settings);
    
    // Mark as already disabled to prevent running again
    update_option('snn_bricks_seo_disabled', true);
}

// Hook into WordPress init to run once
add_action('init', 'snn_disable_bricks_seo_settings', 1);



