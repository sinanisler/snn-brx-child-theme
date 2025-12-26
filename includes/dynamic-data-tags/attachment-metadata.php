<?php
/**
 * ----------------------------------------
 * Attachment Metadata Tag Module
 * ----------------------------------------
 * Usage: 
 * - {attachment_metadata:field:attachment_id}
 * - {attachment_metadata_CUSTOMFIELD:field}
 * 
 * Examples:
 * - {attachment_metadata:width} - Gets width of current post's featured image
 * - {attachment_metadata:height:123} - Gets height of attachment ID 123
 * - {attachment_metadata:file} - Gets file path
 * - {attachment_metadata:filesize} - Gets file size in bytes
 * - {attachment_metadata:mime_type} - Gets MIME type
 * - {attachment_metadata:image_meta.camera} - Gets camera info (nested data)
 * 
 * Custom Field Examples:
 * - {attachment_metadata_logo:width} - Gets width from attachment stored in 'logo' custom field
 * - {attachment_metadata_hero_image:height} - Gets height from 'hero_image' custom field
 * - {attachment_metadata_gallery_image:filesize} - Gets filesize from 'gallery_image' custom field
 * 
 * Available Fields:
 * - width, height, file, filesize, mime_type, sizes, image_meta, duration, length
 * ----------------------------------------
 */

// Step 1: Register the dynamic tags with Bricks Builder.
add_filter('bricks/dynamic_tags_list', 'add_attachment_metadata_tags_to_builder');
function add_attachment_metadata_tags_to_builder($tags) {
    $metadata_fields = [
        'width'      => 'Attachment Width',
        'height'     => 'Attachment Height',
        'file'       => 'Attachment File Path',
        'filesize'   => 'Attachment File Size',
        'mime_type'  => 'Attachment MIME Type',
        'sizes'      => 'Attachment Image Sizes',
        'image_meta' => 'Attachment Image Meta',
        'duration'   => 'Attachment Duration',
    ];

    // Register standard attachment_metadata tags
    foreach ($metadata_fields as $field => $label) {
        $tags[] = [
            'name'  => "{attachment_metadata:$field}",
            'label' => $label,
            'group' => 'SNN',
        ];
    }

    // Register custom field examples (note: dynamic parsing will handle ANY custom field name)
    $custom_field_examples = [
        'logo'        => 'Logo',, $custom_field = null) {
    // If custom field is specified, get attachment ID from that field
    if ($custom_field) {
        global $post;
        $post_id = $post ? $post->ID : get_the_ID();
        
        if (!$post_id) {
            return '';
        }
        
        // Try to get attachment ID from custom field
        $attachment_id = get_post_meta($post_id, $custom_field, true);
        
        // If custom field is an array (like ACF image field), get ID from it
        if (is_array($attachment_id) && isset($attachment_id['ID'])) {
            $attachment_id = $attachment_id['ID'];
        } elseif (is_array($attachment_id) && isset($attachment_id['id'])) {
            $attachment_id = $attachment_id['id'];
        }
        
        $attachment_id = intval($attachment_id);
    }
    
        'hero_image'  => 'Hero Image',
        'thumbnail'   => 'Thumbnail',
        'gallery'     => 'Gallery',
        'banner'      => 'Banner',
    ];

    foreach ($custom_field_examples as $cf_name => $cf_label) {
        foreach ($metadata_fields as $field => $label) {
            $tags[] = [
                'name'  => "{attachment_metadata_{$cf_name}:$field}",
                'label' => "$cf_label $label",
                'group' => 'SNN - Custom Fields',
            ];
        }
    }

    return $tags;
}

// Step 2: Helper function to retrieve attachment metadata.
function get_attachment_metadata_value($field, $attachment_id = null) {
    // If no attachment ID provided, try to determine from context
    if (!$attachment_id) {
        global $post;
        
        // Priority 1: Check if current post is an attachment
        if ($post && $post->post_type === 'attachment') {
            $attachment_id = $post->ID;
        }
        // Priority 2: Try to get featured image of current post
        elseif ($post) {
            $attachment_id = get_post_thumbnail_id($post->ID);
        }
    }

    if (!$attachment_id) {
        return '';
    }

    $metadata = wp_get_attachment_metadata($attachment_id);

    if (!$metadata) {
        return '';
    }

    // Handle nested fields (e.g., image_meta.camera or sizes.thumbnail.width)
    if (strpos($field, '.') !== false) {
        $parts = explode('.', $field);
        $value = $metadata;
        foreach ($parts as $part) {
            if (isset($value[$part])) {
                $value = $value[$part];
            } else {
                return '';
            }
        }
        return is_array($value) ? '' : $value;
    }

    // Always output file and mime_type if present
    if ($field === 'file') {
        if (!empty($metadata['file'])) {
            return $metadata['file'];
        }
        // fallback: get_attached_file
        $file_path = get_attached_file($attachment_id);
        return $file_path ? basename($file_path) : '';
    }
    if ($field === 'mime_type') {
        // Try metadata first
        if (!empty($metadata['mime_type'])) {
            return $metadata['mime_type'];
        }
        // fallback: get_post_mime_type
        $mime = get_post_mime_type($attachment_id);
        return $mime ? $mime : '';
    }

    // For video attachments, output duration if available
    if ($field === 'length' || $field === 'duration') {
        $total_seconds = null;
        if (!empty($metadata['length'])) {
            $total_seconds = intval($metadata['length']);
        } elseif (!empty($metadata['image_meta']['length_seconds'])) {
            $total_seconds = intval($metadata['image_meta']['length_seconds']);
        }
        if ($total_seconds !== null) {
            $hours = floor($total_seconds / 3600);
            $minutes = floor(($total_seconds % 3600) / 60);
            $seconds = $total_seconds % 60;
            if ($hours > 0) {
                return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
            }  1: {attachment_metadata_CUSTOMFIELD:field}
    if (preg_match('/^\{attachment_metadata_([a-zA-Z0-9_-]+):([a-zA-Z0-9_.-]+)\}$/', $tag, $matches)) {
        $custom_field = sanitize_text_field($matches[1]);
        $field = sanitize_text_field($matches[2]);
        
        return get_attachment_metadata_value($field, null, $custom_field);
    }

    // Pattern 2else {
                return sprintf('%02d:%02d', $minutes, $seconds);
            }
        }
        return '';
    }

    // Handle direct fields
    if (isset($metadata[$field])) {
        return is_array($metadata[$field]) ? '' : $metadata[$field];
    }

    return '';
}

// Step 3: Render the dynamic tag when Bricks encounters it.
add_filter('bricks/dynamic_data/render_tag', 'render_attachment_metadata_tag', 20, 3);
function render_attachment_metadata_tag($tag, $post, $context = 'text') {
    if (!is_string($tag)) {
        return $tag;
    }

    // Pattern: {attachment_metadata:field} or {attachment_metadata:field:attachment_id}
    if (strpos($tag, '{attachment_metadata:') === 0 && substr($tag, -1) === '}') {
        $parts = str_replace(['{attachment_metadata:', '}'], '', $tag);
        $parts = explode(':', $parts);
        
        $field = isset($parts[0]) ? sanitize_text_field($parts[0]) : '';
        $attachment_id = isset($parts[1]) ? intval($parts[1]) : null;
        
        // If no explicit ID provided, check if $post is an attachment in loop context
        if (!$attachment_id && $post && isset($post->post_type) && $post->post_type === 'attachment') {
            $attachment_id = $post->ID;
        }

        return get_attachment_metadata_value($field, $attachment_id);
    }

    return $tag;
}

// Step 4: Rep 1: {attachment_metadata_CUSTOMFIELD:field} - Custom field pattern
    if (preg_match_all('/\{attachment_metadata_([a-zA-Z0-9_-]+):([\w.-]+)\}/', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $custom_field = sanitize_text_field($match[1]);
            $field = sanitize_text_field($match[2]);
            
            $value = get_attachment_metadata_value($field, null, $custom_field);
            $content = str_replace($match[0], $value, $content);
        }
    }

    // Pattern 2: {attachment_metadata:field} or {attachment_metadata:field:attachment_id} - Standard pattern
add_filter('bricks/dynamic_data/render_content', 'replace_attachment_metadata_in_content', 20, 3);
add_filter('bricks/frontend/render_data', 'replace_attachment_metadata_in_content', 20, 2);
function replace_attachment_metadata_in_content($content, $post, $context = 'text') {
    if (!is_string($content)) {
        return $content;
    }

    // Pattern: {attachment_metadata:field} or {attachment_metadata:field:attachment_id}
    if (preg_match_all('/\{attachment_metadata:([\w.-]+)(?::(\d+))?\}/', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $field = sanitize_text_field($match[1]);
            $attachment_id = isset($match[2]) ? intval($match[2]) : null;
            
            // If no explicit ID provided, check if $post is an attachment in loop context
            if (!$attachment_id && $post && isset($post->post_type) && $post->post_type === 'attachment') {
                $attachment_id = $post->ID;
            }
            
            $value = get_attachment_metadata_value($field, $attachment_id);
            $content = str_replace($match[0], $value, $content);
        }
    }

    return $content;
}
