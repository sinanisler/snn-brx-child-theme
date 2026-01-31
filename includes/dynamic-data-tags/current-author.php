<?php


/**
 * ----------------------------------------
 * Current Author Tag
 * ----------------------------------------
 * Usage:
 * - {current_author:id} - Returns author ID
 * - {current_author:name} - Returns author display name
 * - {current_author:firstname} - Returns author first name
 * - {current_author:lastname} - Returns author last name
 * - {current_author:email} - Returns author email
 * - {current_author:role} - Returns author role (e.g., administrator, editor)
 * - {current_author:meta:field_name} - Returns custom user meta field
 * - {current_author:url} - Returns author website URL
 * - {current_author:description} - Returns author bio/description
 * - {current_author:nicename} - Returns author nicename (slug)
 *
 * Description:
 * Works on post pages (gets post author), author archive pages (gets queried author),
 * and other contexts where there's a current author
 */
add_filter( 'bricks/dynamic_tags_list', 'register_current_author_tag' );
function register_current_author_tag( $tags ) {
    // Register all current author tag variations
    $author_tags = [
        'id'          => 'Current Author ID',
        'name'        => 'Current Author Display Name',
        'firstname'   => 'Current Author First Name',
        'lastname'    => 'Current Author Last Name',
        'email'       => 'Current Author Email',
        'role'        => 'Current Author Role(s)',
        'url'         => 'Current Author Website URL',
        'description' => 'Current Author Bio/Description',
        'nicename'    => 'Current Author Nicename (Slug)',
        'login'       => 'Current Author Username',
        'registered'  => 'Current Author Registration Date',
    ];

    foreach ( $author_tags as $type => $label ) {
        $tags[] = [
            'name'  => "{current_author:$type}",
            'label' => $label,
            'group' => 'SNN',
        ];
    }

    return $tags;
}

add_filter( 'bricks/dynamic_data/render_tag', 'render_current_author_tag', 10, 3 );
function render_current_author_tag( $tag, $post, $context = 'text' ) {
    if ( strpos( $tag, '{current_author' ) === false ) {
        return $tag;
    }

    // Get the author ID
    $author_id = 0;

    // Check if we're on an author archive
    if ( is_author() ) {
        $author_id = get_queried_object_id();
    }
    // Otherwise, get the post author
    elseif ( isset( $post->post_author ) ) {
        $author_id = $post->post_author;
    }

    // If no author found, return empty
    if ( ! $author_id ) {
        return '';
    }

    // Extract parameter from tag (e.g., {current_author:id} -> 'id')
    preg_match( '/{current_author:?([^}]*)}/', $tag, $matches );
    $param = isset( $matches[1] ) ? $matches[1] : 'id';

    // Handle meta fields (e.g., {current_author:meta:field_name})
    if ( strpos( $param, 'meta:' ) === 0 ) {
        $meta_key = str_replace( 'meta:', '', $param );
        return get_user_meta( $author_id, $meta_key, true );
    }

    // Get author data
    $author_data = get_userdata( $author_id );

    if ( ! $author_data ) {
        return '';
    }

    // Return the requested field
    switch ( $param ) {
        case 'id':
        case '':
            return $author_id;

        case 'name':
        case 'display_name':
            return $author_data->display_name;

        case 'firstname':
        case 'first_name':
            return $author_data->first_name;

        case 'lastname':
        case 'last_name':
            return $author_data->last_name;

        case 'email':
            return $author_data->user_email;

        case 'role':
            $roles = $author_data->roles;
            return ! empty( $roles ) ? implode( ', ', $roles ) : '';

        case 'url':
        case 'website':
            return $author_data->user_url;

        case 'description':
        case 'bio':
            return $author_data->description;

        case 'nicename':
        case 'slug':
            return $author_data->user_nicename;

        case 'login':
        case 'username':
            return $author_data->user_login;

        case 'registered':
            return $author_data->user_registered;

        default:
            // Try to get it as a user property first
            if ( isset( $author_data->$param ) ) {
                return $author_data->$param;
            }
            // Otherwise try as user meta
            return get_user_meta( $author_id, $param, true );
    }
}

add_filter( 'bricks/dynamic_data/render_content', 'render_current_author_tag_in_content', 10, 3 );
add_filter( 'bricks/frontend/render_data', 'render_current_author_tag_in_content', 10, 2 );
function render_current_author_tag_in_content( $content, $post, $context = 'text' ) {
    // Check if content contains current_author tag
    if ( strpos( $content, '{current_author' ) === false ) {
        return $content;
    }

    // Find all {current_author:...} tags
    preg_match_all( '/{current_author:?[^}]*}/', $content, $matches );

    if ( empty( $matches[0] ) ) {
        return $content;
    }

    // Replace each occurrence
    foreach ( $matches[0] as $tag ) {
        $replacement = render_current_author_tag( $tag, $post, $context );
        $content = str_replace( $tag, $replacement, $content );
    }

    return $content;
}
