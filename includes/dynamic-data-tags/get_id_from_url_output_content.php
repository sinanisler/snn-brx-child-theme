<?php
/**
 * ----------------------------------------
 * Get ID from URL Dynamic Content Tag Module
 * ----------------------------------------
 * Usage: {get_id_from_url_output_content:url_param:field}
 *
 * Example URL: https://snn.academy/instructor/2/?cid=332&uid=2&certificate_id=123
 *
 * POST FIELDS (using any URL parameter):
 * - post_title: Post title
 * - post_name: Post slug
 * - post_content: Post content
 * - post_excerpt: Post excerpt
 * - post_date: Post publish date
 * - post_modified: Post modified date
 * - post_author: Post author ID
 * - post_type: Post type
 * - post_status: Post status
 * - featured_image: Featured image URL
 * - permalink: Post permalink
 * - [custom_field]: Any custom field value
 *
 * USER/AUTHOR FIELDS (using any URL parameter):
 * - author_name: User display name
 * - author_firstname: User first name
 * - author_lastname: User last name
 * - author_email: User email
 * - author_url: User website URL
 * - author_description: User bio/description
 * - author_nicename: User nicename
 * - author_login: User login name
 * - author_registered: User registration date
 * - [user_meta_key]: Any user meta value
 *
 * Examples:
 * {get_id_from_url_output_content:cid:post_title}
 * {get_id_from_url_output_content:cid:post_content}
 * {get_id_from_url_output_content:uid:author_name}
 * {get_id_from_url_output_content:uid:author_email}
 *
 * Security Features:
 * - URL parameters sanitized with sanitize_key()
 * - IDs validated with absint() (positive integers only)
 * - Post existence and publish status verification
 * - User existence verification
 * - WordPress core functions used for data retrieval
 * ----------------------------------------
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Step 1: Register the dynamic tags with Bricks Builder
add_filter('bricks/dynamic_tags_list', 'add_get_id_from_url_tags_to_builder');
function add_get_id_from_url_tags_to_builder($tags) {
    // Common post fields
    $post_fields = [
        'post_title'      => 'Post Title from URL ID',
        'post_name'       => 'Post Slug from URL ID',
        'post_content'    => 'Post Content from URL ID',
        'post_excerpt'    => 'Post Excerpt from URL ID',
        'post_date'       => 'Post Date from URL ID',
        'post_modified'   => 'Post Modified Date from URL ID',
        'featured_image'  => 'Featured Image from URL ID',
        'permalink'       => 'Permalink from URL ID',
    ];

    foreach ($post_fields as $field => $label) {
        $tags[] = [
            'name'  => "{get_id_from_url_output_content:param:$field}",
            'label' => $label,
            'group' => 'SNN URL',
        ];
    }

    // Common user/author fields
    $user_fields = [
        'author_name'        => 'Author Name from URL ID',
        'author_firstname'   => 'Author First Name from URL ID',
        'author_lastname'    => 'Author Last Name from URL ID',
        'author_email'       => 'Author Email from URL ID',
        'author_description' => 'Author Bio from URL ID',
    ];

    foreach ($user_fields as $field => $label) {
        $tags[] = [
            'name'  => "{get_id_from_url_output_content:param:$field}",
            'label' => $label,
            'group' => 'SNN URL',
        ];
    }

    return $tags;
}

// Step 2: Get and sanitize ID from URL parameter
function get_id_from_url_parameter($param_name) {
    // Sanitize parameter name - only allow alphanumeric and underscore
    // This prevents any malicious parameter names
    $param_name = sanitize_key($param_name);

    if (empty($param_name)) {
        return 0;
    }

    // Get the parameter value from URL
    $param_value = isset($_GET[$param_name]) ? $_GET[$param_name] : '';

    // Convert to absolute integer (only positive integers allowed)
    // This prevents SQL injection, XSS, and any malicious input
    // absint() ensures we only get positive integers, anything else becomes 0
    $id = absint($param_value);

    // Return 0 if no valid ID found
    return $id;
}

// Step 3: Determine if field is for post or user based on prefix
function is_url_author_field($field) {
    $author_prefixes = ['author_', 'user_'];

    foreach ($author_prefixes as $prefix) {
        if (strpos($field, $prefix) === 0) {
            return true;
        }
    }

    return false;
}

// Step 4: Get post content by ID
function get_post_content_from_url_id($post_id, $field) {
    // Verify post exists
    $post = get_post($post_id);

    if (!$post) {
        return '';
    }

    // Security: Only return content from published posts
    // This prevents accessing draft or private content
    if ($post->post_status !== 'publish') {
        return '';
    }

    // Handle standard post fields
    switch ($field) {
        case 'post_title':
            return get_the_title($post_id);

        case 'post_name':
            return $post->post_name;

        case 'post_content':
            return apply_filters('the_content', $post->post_content);

        case 'post_excerpt':
            return get_the_excerpt($post_id);

        case 'post_date':
            return get_the_date('', $post_id);

        case 'post_modified':
            return get_the_modified_date('', $post_id);

        case 'post_author':
            return $post->post_author;

        case 'post_type':
            $post_type_obj = get_post_type_object($post->post_type);
            return $post_type_obj ? $post_type_obj->labels->singular_name : $post->post_type;

        case 'post_status':
            return $post->post_status;

        case 'featured_image':
            return get_the_post_thumbnail_url($post_id, 'full');

        case 'permalink':
            return get_permalink($post_id);

        default:
            // Try to get custom field
            // get_post_meta is safe and sanitized by WordPress
            $value = get_post_meta($post_id, $field, true);
            return $value !== '' ? $value : '';
    }
}

// Step 5: Get user/author content by ID
function get_author_content_from_url_id($user_id, $field) {
    // Verify user exists - security check
    $user = get_user_by('id', $user_id);

    if (!$user) {
        return '';
    }

    // Handle standard user fields
    switch ($field) {
        case 'author_name':
        case 'user_name':
            return $user->display_name;

        case 'author_firstname':
        case 'user_firstname':
            return get_user_meta($user_id, 'first_name', true);

        case 'author_lastname':
        case 'user_lastname':
            return get_user_meta($user_id, 'last_name', true);

        case 'author_email':
        case 'user_email':
            return $user->user_email;

        case 'author_url':
        case 'user_url':
            return $user->user_url;

        case 'author_description':
        case 'user_description':
            return get_user_meta($user_id, 'description', true);

        case 'author_nicename':
        case 'user_nicename':
            return $user->user_nicename;

        case 'author_login':
        case 'user_login':
            return $user->user_login;

        case 'author_registered':
        case 'user_registered':
            return date_i18n(get_option('date_format'), strtotime($user->user_registered));

        default:
            // Try to get user meta - remove author_/user_ prefix for cleaner meta keys
            $meta_key = str_replace(['author_', 'user_'], '', $field);
            // get_user_meta is safe and sanitized by WordPress
            $value = get_user_meta($user_id, $meta_key, true);
            return $value !== '' ? $value : '';
    }
}

// Step 6: Main function to get content from URL parameter
function get_id_from_url_output_content($params) {
    // Split parameters by colon
    $parts = explode(':', $params);

    // Need at least 2 parts: url_param and field
    if (count($parts) < 2) {
        return '';
    }

    $url_param = trim($parts[0]);
    $field = trim($parts[1]);

    // Get ID from URL parameter (fully sanitized and validated)
    $id = get_id_from_url_parameter($url_param);

    // If no valid ID found, return empty
    // This handles cases where ID is 0, negative, or non-numeric
    if ($id === 0) {
        return '';
    }

    // Determine if this is a user/author field or post field
    if (is_url_author_field($field)) {
        return get_author_content_from_url_id($id, $field);
    } else {
        return get_post_content_from_url_id($id, $field);
    }
}

// Step 7: Render the dynamic tag in Bricks Builder
add_filter('bricks/dynamic_data/render_tag', 'render_get_id_from_url_tag', 20, 3);
function render_get_id_from_url_tag($tag, $post, $context = 'text') {
    // Ensure that $tag is a string before processing
    if (is_string($tag) && strpos($tag, '{get_id_from_url_output_content:') === 0) {
        // Extract the parameters from the tag
        $params = trim(str_replace(['{get_id_from_url_output_content:', '}'], '', $tag));
        return get_id_from_url_output_content($params);
    }

    // If $tag is an array, iterate through and process each element
    if (is_array($tag)) {
        foreach ($tag as $key => $value) {
            if (is_string($value) && strpos($value, '{get_id_from_url_output_content:') === 0) {
                $params = trim(str_replace(['{get_id_from_url_output_content:', '}'], '', $value));
                $tag[$key] = get_id_from_url_output_content($params);
            }
        }
        return $tag;
    }

    // Return the original tag if it doesn't match the expected pattern
    return $tag;
}

// Step 8: Replace placeholders in dynamic content
add_filter('bricks/dynamic_data/render_content', 'replace_get_id_from_url_in_content', 20, 3);
add_filter('bricks/frontend/render_data', 'replace_get_id_from_url_in_content', 20, 2);
function replace_get_id_from_url_in_content($content, $post, $context = 'text') {
    if (!is_string($content)) {
        return $content;
    }

    // Find all instances of the tag in content
    preg_match_all('/{get_id_from_url_output_content:([^}]+)}/', $content, $matches);
    if (!empty($matches[1])) {
        foreach ($matches[1] as $params) {
            $value = get_id_from_url_output_content($params);
            $content = str_replace("{get_id_from_url_output_content:$params}", $value, $content);
        }
    }
    return $content;
}
