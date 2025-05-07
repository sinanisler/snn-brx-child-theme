<?php

$github_username = 'sinanisler';
$github_repo_name = 'snn-brx-child-theme';

$theme_slug = get_stylesheet(); 

add_filter( 'pre_set_site_transient_update_themes', 'my_child_theme_check_github_update' );

function my_child_theme_check_github_update( $transient ) {
    global $github_username, $github_repo_name, $theme_slug;

    $current_theme = wp_get_theme( $theme_slug );
    $current_version = $current_theme->get( 'Version' );

    $github_api_url = "https://api.github.com/repos/{$github_username}/{$github_repo_name}/releases/latest";

    $response = wp_remote_get( $github_api_url, array(
        'timeout'     => 15,
        'headers'     => array(
            'Accept'     => 'application/vnd.github.v3+json',
            'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ),
        ),
    ) );

    if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
        return $transient;
    }

    $release_data = json_decode( wp_remote_retrieve_body( $response ) );

    if ( ! $release_data || ! isset( $release_data->tag_name ) ) {
        return $transient;
    }

    $latest_version = ltrim( $release_data->tag_name, 'vV' );

    if ( version_compare( $latest_version, $current_version, '>' ) ) {

        $download_url = null;
        $expected_asset_name = $theme_slug . '.zip';

        if ( isset( $release_data->assets ) && is_array( $release_data->assets ) ) {
            foreach ( $release_data->assets as $asset ) {
                if ( isset( $asset->browser_download_url ) && $asset->name === $expected_asset_name ) {
                    $download_url = $asset->browser_download_url;
                    break; 
                }
            }
        }

        if ( $download_url ) {
            $transient->response[ $theme_slug ] = array(
                'theme'       => $theme_slug,
                'new_version' => $latest_version,
                'url'         => $release_data->html_url, 
                'package'     => $download_url,
            );
        } else {
        }
    } else {
    }


    return $transient;
}

add_filter( 'themes_api', 'my_child_theme_github_theme_info', 10, 3 );

function my_child_theme_github_theme_info( $res, $action, $args ) {
    global $github_username, $github_repo_name, $theme_slug;

    if ( $action !== 'theme_information' || $args->slug !== $theme_slug ) {
        return $res;
    }

    $github_api_url = "https://api.github.com/repos/{$github_username}/{$github_repo_name}/releases/latest";

    $response = wp_remote_get( $github_api_url, array(
        'timeout'     => 15,
        'headers'     => array(
            'Accept'     => 'application/vnd.github.v3+json',
            'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ),
        ),
    ) );

    if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
        return $res; 
    }

    $release_data = json_decode( wp_remote_retrieve_body( $response ) );

    if ( ! $release_data || ! isset( $release_data->tag_name ) ) {
        return $res; 
    }

    $download_link = '';
    $expected_asset_name = $theme_slug . '.zip';
    if ( isset( $release_data->assets ) && is_array( $release_data->assets ) ) {
        foreach ( $release_data->assets as $asset ) {
            if ( isset( $asset->browser_download_url ) && $asset->name === $expected_asset_name ) {
                $download_link = $asset->browser_download_url;
                break;
            }
        }
    }


    $res = (object) array(
        'name'        => $args->slug, 
        'slug'        => $args->slug,
        'version'     => ltrim( $release_data->tag_name, 'vV' ), 
        'requires'    => '5.0', 
        'tested'      => get_bloginfo('version'), 
        'requires_php' => '7.4', 
        'download_link' => $download_link, 
        'sections'    => array(
            'description' => isset( $release_data->body ) ? $release_data->body : 'Latest release information from GitHub.', 
            'changelog'   => isset( $release_data->body ) ? $release_data->body : 'See GitHub release notes for details.', 
        ),
        'added'       => date( 'Y-m-d', strtotime( $release_data->published_at ) ), 
        'last_updated' => date( 'Y-m-d', strtotime( $release_data->published_at ) ),
        'homepage'    => "https://github.com/{$github_username}/{$github_repo_name}", 
    );

    return $res;
}
