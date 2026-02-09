<?php
/**
 * ----------------------------------------
 * Attachment Metadata Tag Module
 * ----------------------------------------
 * Usage: {attachment_metadata:field:attachment_id}
 * 
 * Examples:
 * - {attachment_metadata:url} - Gets URL of current post's featured image
 * - {attachment_metadata:width} - Gets width of current post's featured image
 * - {attachment_metadata:height:123} - Gets height of attachment ID 123
 * - {attachment_metadata:file} - Gets file path
 * - {attachment_metadata:filesize} - Gets file size in bytes
 * - {attachment_metadata:mime_type} - Gets MIME type
 * - {attachment_metadata:image_meta.camera} - Gets camera info (nested data)
 *
 * Custom Field Examples:
 * - {attachment_metadata:logo:url} - Gets URL from attachment stored in 'logo' custom field
 * - {attachment_metadata:logo:width} - Gets width from attachment stored in 'logo' custom field
 * - {attachment_metadata:hero_image:height} - Gets height from 'hero_image' custom field
 * - {attachment_metadata:gallery_image:filesize} - Gets filesize from 'gallery_image' custom field
 * - {attachment_metadata:video_url:length} - Gets length from 'video_url' custom field
 * 
 * Available Fields:
 * - url, title, alt, caption, description (attachment post fields)
 * - width, height, file, filesize, mime_type, sizes, image_meta, length, duration (metadata)
 * ----------------------------------------
 */

// Step 1: Register the dynamic tags with Bricks Builder.
add_filter('bricks/dynamic_tags_list', 'add_attachment_metadata_tags_to_builder');
function add_attachment_metadata_tags_to_builder($tags) {
    $metadata_fields = [
        'url'        => 'Attachment URL',
        'title'      => 'Attachment Title',
        'alt'        => 'Attachment Alt Text',
        'caption'    => 'Attachment Caption',
        'description'=> 'Attachment Description',
        'width'      => 'Attachment Width',
        'height'     => 'Attachment Height',
        'file'       => 'Attachment File Path',
        'filesize'   => 'Attachment File Size',
        'mime_type'  => 'Attachment MIME Type',
        'sizes'      => 'Attachment Image Sizes',
        'image_meta' => 'Attachment Image Meta',
    ];

    foreach ($metadata_fields as $field => $label) {
        $tags[] = [
            'name'  => "{attachment_metadata:$field}",
            'label' => $label,
            'group' => 'SNN',
        ];
    }

    return $tags;
}

// Step 2: Helper function to retrieve attachment metadata.
function get_attachment_metadata_value($field, $attachment_id = null, $use_fallback = true) {
    // If no attachment ID provided, try to determine from context (only if fallback is enabled)
    if (!$attachment_id && $use_fallback) {
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

    // Handle attachment post fields (don't need metadata for these)
    if ($field === 'url') {
        return wp_get_attachment_url($attachment_id);
    }
    if ($field === 'title') {
        return get_the_title($attachment_id);
    }
    if ($field === 'alt') {
        return get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
    }
    if ($field === 'caption') {
        $post_obj = get_post($attachment_id);
        return $post_obj ? $post_obj->post_excerpt : '';
    }
    if ($field === 'description') {
        $post_obj = get_post($attachment_id);
        return $post_obj ? $post_obj->post_content : '';
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
            } else {
                return sprintf('%02d:%02d', $minutes, $seconds);
            }
        }
        return '';
    }

    // Handle filesize - format as human-readable (KB, MB, GB)
    if ($field === 'filesize') {
        if (!empty($metadata['filesize'])) {
            return size_format($metadata['filesize'], 2);
        }
        // Fallback: get file size from actual file
        $file_path = get_attached_file($attachment_id);
        if ($file_path && file_exists($file_path)) {
            return size_format(filesize($file_path), 2);
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

    // Pattern: {attachment_metadata:field} or {attachment_metadata:field:attachment_id} or {attachment_metadata:custom_field:field}
    if (strpos($tag, '{attachment_metadata:') === 0 && substr($tag, -1) === '}') {
        $parts = str_replace(['{attachment_metadata:', '}'], '', $tag);
        $parts = explode(':', $parts);

        $field = isset($parts[0]) ? sanitize_text_field($parts[0]) : '';
        $attachment_id = null;
        $use_fallback = true;

        // Check if we have 2 parts
        if (isset($parts[1])) {
            $second_part = sanitize_text_field($parts[1]);

            // If second part is numeric, it's an attachment ID
            if (is_numeric($second_part)) {
                $attachment_id = intval($second_part);
                $use_fallback = false; // Explicit ID provided, don't use fallback
            } else {
                // Otherwise, first part is custom field name, second part is the metadata field
                $custom_field_name = $field;
                $field = $second_part;
                $use_fallback = false; // Custom field lookup, don't use fallback

                // Get attachment ID from custom field
                if ($post && isset($post->ID)) {
                    $custom_field_value = get_post_meta($post->ID, $custom_field_name, true);
                    if (is_numeric($custom_field_value)) {
                        $attachment_id = intval($custom_field_value);
                    } elseif (is_array($custom_field_value) && isset($custom_field_value['id'])) {
                        // Handle ACF image field format
                        $attachment_id = intval($custom_field_value['id']);
                    }
                }
            }
        }

        // If no explicit ID provided, check if $post is an attachment in loop context
        if (!$attachment_id && $use_fallback && $post && isset($post->post_type) && $post->post_type === 'attachment') {
            $attachment_id = $post->ID;
        }

        return get_attachment_metadata_value($field, $attachment_id, $use_fallback);
    }

    return $tag;
}

// Step 4: Replace placeholders in dynamic content.
add_filter('bricks/dynamic_data/render_content', 'replace_attachment_metadata_in_content', 20, 3);
add_filter('bricks/frontend/render_data', 'replace_attachment_metadata_in_content', 20, 2);
function replace_attachment_metadata_in_content($content, $post, $context = 'text') {
    if (!is_string($content)) {
        return $content;
    }

    // Pattern: {attachment_metadata:field} or {attachment_metadata:field:attachment_id} or {attachment_metadata:custom_field:field}
    if (preg_match_all('/\{attachment_metadata:([\w.-]+)(?::([\w.-]+))?\}/', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $field = sanitize_text_field($match[1]);
            $attachment_id = null;
            $use_fallback = true;

            // Check if we have a second part
            if (isset($match[2]) && !empty($match[2])) {
                $second_part = sanitize_text_field($match[2]);

                // If second part is numeric, it's an attachment ID
                if (is_numeric($second_part)) {
                    $attachment_id = intval($second_part);
                    $use_fallback = false; // Explicit ID provided, don't use fallback
                } else {
                    // Otherwise, first part is custom field name, second part is the metadata field
                    $custom_field_name = $field;
                    $field = $second_part;
                    $use_fallback = false; // Custom field lookup, don't use fallback

                    // Get attachment ID from custom field
                    if ($post && isset($post->ID)) {
                        $custom_field_value = get_post_meta($post->ID, $custom_field_name, true);
                        if (is_numeric($custom_field_value)) {
                            $attachment_id = intval($custom_field_value);
                        } elseif (is_array($custom_field_value) && isset($custom_field_value['id'])) {
                            // Handle ACF image field format
                            $attachment_id = intval($custom_field_value['id']);
                        }
                    }
                }
            }

            // If no explicit ID provided, check if $post is an attachment in loop context
            if (!$attachment_id && $use_fallback && $post && isset($post->post_type) && $post->post_type === 'attachment') {
                $attachment_id = $post->ID;
            }

            $value = get_attachment_metadata_value($field, $attachment_id, $use_fallback);
            $content = str_replace($match[0], $value, $content);
        }
    }

    return $content;
}
