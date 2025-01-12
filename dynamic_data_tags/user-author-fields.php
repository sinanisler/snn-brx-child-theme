<?php 

/**
 * ----------------------------------------
 * Current User Fields Tag
 * ----------------------------------------
 * Usage: {current_user_fields:field_name}
 * Description: Fetches various fields of the current user, such as name, firstname, lastname, email, and custom fields.
 */



add_filter( 'bricks/dynamic_tags_list', 'add_current_user_fields_tag_to_builder' );
function add_current_user_fields_tag_to_builder( $tags ) {
    $tags[] = [
        'name'  => '{current_user_fields}',
        'label' => 'Current User Fields',
        'group' => 'SNN BRX',
    ];
    return $tags;
}

function get_current_user_field( $field ) {
    $current_user = wp_get_current_user();
    if ( $current_user->ID !== 0 ) {
        switch ( strtolower( $field ) ) {
            case 'name':
                return esc_html( $current_user->display_name );
            case 'firstname':
                return esc_html( get_user_meta( $current_user->ID, 'first_name', true ) );
            case 'lastname':
                return esc_html( get_user_meta( $current_user->ID, 'last_name', true ) );
            case 'email':
                return esc_html( $current_user->user_email );
            default:
                // Handle custom fields, return an empty string if the field is not set
                return esc_html( get_user_meta( $current_user->ID, sanitize_key( $field ), true ) ) ?: '';
        }
    }
    return '';
}

add_filter( 'bricks/dynamic_data/render_tag', 'render_current_user_fields_tag', 10, 3 );
function render_current_user_fields_tag( $tag, $post, $context = 'text' ) {
    if ( strpos( $tag, '{current_user_fields:' ) === 0 ) {
        // Extract the field name after 'current_user_fields:'
        $field = str_replace( '{current_user_fields:', '', trim( $tag, '{}' ) );
        return get_current_user_field( sanitize_key( $field ) );
    }
    return $tag;
}

add_filter( 'bricks/dynamic_data/render_content', 'render_current_user_fields_in_content', 10, 3 );
add_filter( 'bricks/frontend/render_data', 'render_current_user_fields_in_content', 10, 2 );
function render_current_user_fields_in_content( $content, $post, $context = 'text' ) {
    // Find all occurrences of '{current_user_fields:field}'
    if ( preg_match_all( '/\{current_user_fields:([^}]+)\}/', $content, $matches ) ) {
        foreach ( $matches[1] as $field ) {
            $field_value = get_current_user_field( sanitize_key( $field ) );
            // Replace the placeholder with the actual user field value
            $content = str_replace( "{current_user_fields:$field}", $field_value, $content );
        }
    }
    return $content;
}
