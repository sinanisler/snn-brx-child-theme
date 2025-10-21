<?php


// Configuration
$update_proxy_url = 'https://sinanisler.com/snn-brx-github/update.php';
$theme_slug       = get_stylesheet();

/**
 * Check for Theme Updates (runs via proxy)
 */
add_filter('pre_set_site_transient_update_themes', 'snn_brx_check_theme_update_proxy');
function snn_brx_check_theme_update_proxy($transient) {
    global $update_proxy_url, $theme_slug;

    // Get current version of the installed theme
    $current_theme   = wp_get_theme($theme_slug);
    $current_version = $current_theme->get('Version');

    // Query the update proxy for latest GitHub release (cached and tracked)
    $response = wp_remote_get($update_proxy_url, array(
        'timeout' => 15,
        'headers' => array(
            'Accept'      => 'application/vnd.github.v3+json',
            'User-Agent'  => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url'),
            'X-Site-Url'  => get_site_url(), 
        ),
    ));

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        return $transient;
    }

    $release_data = json_decode(wp_remote_retrieve_body($response));

    if (!$release_data || !isset($release_data->tag_name)) {
        return $transient;
    }

    $latest_version = ltrim($release_data->tag_name, 'vV');
    $expected_asset_name = $theme_slug . '.zip';
    $download_url = '';

    if (isset($release_data->assets) && is_array($release_data->assets)) {
        foreach ($release_data->assets as $asset) {
            if (isset($asset->browser_download_url) && $asset->name === $expected_asset_name) {
                $download_url = $asset->browser_download_url;
                break;
            }
        }
    }

    // Only trigger update if new version exists and download URL is found
    if (
        $download_url
        && version_compare($latest_version, $current_version, '>')
    ) {
        $transient->response[$theme_slug] = array(
            'theme'       => $theme_slug,
            'new_version' => $latest_version,
            'url'         => $release_data->html_url ?? '',
            'package'     => $download_url,
        );
    }

    return $transient;
}

/**
 * Provide Theme Info for the "View version x.x.x details" popup in WP
 */
add_filter('themes_api', 'snn_brx_theme_info_from_proxy', 10, 3);
function snn_brx_theme_info_from_proxy($result, $action, $args) {
    global $update_proxy_url, $theme_slug;

    if ($action !== 'theme_information' || $args->slug !== $theme_slug) {
        return $result;
    }

    // Fetch latest release info via proxy
    $response = wp_remote_get($update_proxy_url, array(
        'timeout' => 15,
        'headers' => array(
            'Accept'     => 'application/vnd.github.v3+json',
            'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url'),
        ),
    ));

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        return $result;
    }

    $release_data = json_decode(wp_remote_retrieve_body($response));
    if (!$release_data || !isset($release_data->tag_name)) {
        return $result;
    }

    $latest_version      = ltrim($release_data->tag_name, 'vV');
    $expected_asset_name = $theme_slug . '.zip';
    $download_url        = '';

    if (isset($release_data->assets) && is_array($release_data->assets)) {
        foreach ($release_data->assets as $asset) {
            if (isset($asset->browser_download_url) && $asset->name === $expected_asset_name) {
                $download_url = $asset->browser_download_url;
                break;
            }
        }
    }

    // Prepare details for the WP theme info popup
    $result = (object) array(
        'name'         => $args->slug,
        'slug'         => $args->slug,
        'version'      => $latest_version,
        'requires'     => '5.0',
        'tested'       => get_bloginfo('version'),
        'requires_php' => '7.4',
        'download_link'=> $download_url,
        'sections'     => array(
            'description' => $release_data->body ?? __('Latest release information from GitHub.', 'snn'),
            'changelog'   => $release_data->body ?? __('See GitHub release notes for details.', 'snn'),
        ),
        'added'        => isset($release_data->published_at) ? date('Y-m-d', strtotime($release_data->published_at)) : '',
        'last_updated' => isset($release_data->published_at) ? date('Y-m-d', strtotime($release_data->published_at)) : '',
        'homepage'     => $release_data->html_url ?? '',
    );

    return $result;
}

/**
 * Add JavaScript to admin footer to redirect version details link
 */
add_action('admin_footer', 'snn_brx_github_redirect_version_link');
function snn_brx_github_redirect_version_link() {
    ?>
    <script type="text/javascript">
        (function() {
            const githubUrl = 'https://github.com/sinanisler/snn-brx-child-theme/releases';
            
            function modifyLink(link) {
                if (link.getAttribute('aria-label') && link.getAttribute('aria-label').includes('SNN-BRX')) {
                    // Replace the href completely
                    link.href = githubUrl;
                    
                    // Remove thickbox classes
                    link.classList.remove('thickbox', 'open-plugin-details-modal');
                    
                    // Set target to open in new tab
                    link.target = '_blank';
                    link.rel = 'noopener noreferrer';
                    
                    // Add click handler as extra safety
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        window.open(githubUrl, '_blank', 'noopener,noreferrer');
                        return false;
                    }, true);
                }
            }
            
            function processLinks() {
                const links = document.querySelectorAll('a[aria-label*="SNN-BRX"]');
                links.forEach(modifyLink);
            }
            
            // Process on DOM ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', processLinks);
            } else {
                processLinks();
            }
            
            // Also watch for dynamically added links
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) {
                            if (node.matches && node.matches('a[aria-label*="SNN-BRX"]')) {
                                modifyLink(node);
                            }
                            const links = node.querySelectorAll && node.querySelectorAll('a[aria-label*="SNN-BRX"]');
                            if (links) {
                                links.forEach(modifyLink);
                            }
                        }
                    });
                });
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        })();
    </script>
    <?php
}
