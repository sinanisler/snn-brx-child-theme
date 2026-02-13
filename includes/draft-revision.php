<?php
/**
 * Draft Revision System
 *
 * Allows creating draft revisions of any post type and syncing them back to the original
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SNN_Draft_Revision {

    /**
     * Meta key to store the original post ID in the draft
     */
    const ORIGINAL_POST_META_KEY = '_snn_original_post_id';

    /**
     * Initialize the class
     */
    public function __construct() {
        // Add row actions for all post types
        add_filter('post_row_actions', array($this, 'add_row_actions'), 10, 2);
        add_filter('page_row_actions', array($this, 'add_row_actions'), 10, 2);

        // Handle AJAX requests
        add_action('admin_action_snn_create_revision', array($this, 'create_revision'));
        add_action('admin_action_snn_sync_with_original', array($this, 'sync_with_original'));

        // Add admin notices
        add_action('admin_notices', array($this, 'admin_notices'));
    }

    /**
     * Add custom row actions to posts
     */
    public function add_row_actions($actions, $post) {
        // Check if user has permission to edit posts
        if (!current_user_can('edit_post', $post->ID)) {
            return $actions;
        }

        // Check if this post is a draft revision (has original post ID)
        $original_post_id = get_post_meta($post->ID, self::ORIGINAL_POST_META_KEY, true);

        if ($original_post_id && get_post($original_post_id)) {
            // This is a draft revision - add "Sync with Original" link
            $sync_url = wp_nonce_url(
                admin_url('admin.php?action=snn_sync_with_original&post=' . $post->ID),
                'snn_sync_' . $post->ID
            );

            $actions['snn_sync_original'] = sprintf(
                '<a href="%s" style="color: #00a32a; font-weight: bold;">%s</a>',
                esc_url($sync_url),
                __('Sync with Original', 'snn')
            );
        } else {
            // This is an original post - add "Create Revision" link
            $create_url = wp_nonce_url(
                admin_url('admin.php?action=snn_create_revision&post=' . $post->ID),
                'snn_create_revision_' . $post->ID
            );

            $actions['snn_create_revision'] = sprintf(
                '<a href="%s" style="color: #2271b1; font-weight: bold;">%s</a>',
                esc_url($create_url),
                __('Create Revision', 'snn')
            );
        }

        return $actions;
    }

    /**
     * Create a draft revision of a post
     */
    public function create_revision() {
        // Get post ID
        $post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;

        // Verify nonce
        if (!wp_verify_nonce($_GET['_wpnonce'], 'snn_create_revision_' . $post_id)) {
            wp_die(__('Security check failed', 'snn'));
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            wp_die(__('You do not have permission to do this', 'snn'));
        }

        // Get the original post
        $original_post = get_post($post_id);

        if (!$original_post) {
            wp_die(__('Post not found', 'snn'));
        }

        // Clone the post
        $new_post_id = $this->clone_post($original_post);

        if (is_wp_error($new_post_id)) {
            wp_die($new_post_id->get_error_message());
        }

        // Redirect to edit the new draft
        $redirect_url = add_query_arg(
            array(
                'post' => $new_post_id,
                'action' => 'edit',
                'snn_revision_created' => '1'
            ),
            admin_url('post.php')
        );

        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Clone a post completely (1-to-1 copy)
     */
    private function clone_post($original_post) {
        global $wpdb;

        // Prepare the new post data
        $new_post_data = array(
            'post_author'           => get_current_user_id(),
            'post_date'             => current_time('mysql'),
            'post_date_gmt'         => current_time('mysql', 1),
            'post_content'          => $original_post->post_content,
            'post_content_filtered' => $original_post->post_content_filtered,
            'post_title'            => $original_post->post_title . ' (Draft Revision)',
            'post_excerpt'          => $original_post->post_excerpt,
            'post_status'           => 'draft',
            'post_type'             => $original_post->post_type,
            'comment_status'        => $original_post->comment_status,
            'ping_status'           => $original_post->ping_status,
            'post_password'         => $original_post->post_password,
            'to_ping'               => $original_post->to_ping,
            'pinged'                => $original_post->pinged,
            'post_parent'           => $original_post->post_parent,
            'menu_order'            => $original_post->menu_order,
            'post_mime_type'        => $original_post->post_mime_type,
        );

        // Insert the new post
        $new_post_id = wp_insert_post($new_post_data, true);

        if (is_wp_error($new_post_id)) {
            return $new_post_id;
        }

        // Clone all post meta (custom fields, including private ones)
        $post_meta = get_post_meta($original_post->ID);

        if ($post_meta) {
            foreach ($post_meta as $meta_key => $meta_values) {
                // Skip the original post ID meta (we'll add it separately)
                if ($meta_key === self::ORIGINAL_POST_META_KEY) {
                    continue;
                }

                foreach ($meta_values as $meta_value) {
                    // Maybe unserialize
                    $meta_value = maybe_unserialize($meta_value);
                    add_post_meta($new_post_id, $meta_key, $meta_value);
                }
            }
        }

        // Store the original post ID in the draft
        update_post_meta($new_post_id, self::ORIGINAL_POST_META_KEY, $original_post->ID);

        // Clone featured image (thumbnail)
        $thumbnail_id = get_post_thumbnail_id($original_post->ID);
        if ($thumbnail_id) {
            set_post_thumbnail($new_post_id, $thumbnail_id);
        }

        // Clone taxonomies (categories, tags, custom taxonomies)
        $taxonomies = get_object_taxonomies($original_post->post_type);

        if ($taxonomies) {
            foreach ($taxonomies as $taxonomy) {
                $terms = wp_get_object_terms($original_post->ID, $taxonomy, array('fields' => 'ids'));

                if (!is_wp_error($terms) && !empty($terms)) {
                    wp_set_object_terms($new_post_id, $terms, $taxonomy);
                }
            }
        }

        // Clone post format (if applicable)
        $post_format = get_post_format($original_post->ID);
        if ($post_format) {
            set_post_format($new_post_id, $post_format);
        }

        return $new_post_id;
    }

    /**
     * Sync the draft revision back to the original post
     */
    public function sync_with_original() {
        // Get post ID
        $draft_post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;

        // Verify nonce
        if (!wp_verify_nonce($_GET['_wpnonce'], 'snn_sync_' . $draft_post_id)) {
            wp_die(__('Security check failed', 'snn'));
        }

        // Check permissions
        if (!current_user_can('edit_post', $draft_post_id)) {
            wp_die(__('You do not have permission to do this', 'snn'));
        }

        // Get the draft post
        $draft_post = get_post($draft_post_id);

        if (!$draft_post) {
            wp_die(__('Draft post not found', 'snn'));
        }

        // Get the original post ID
        $original_post_id = get_post_meta($draft_post_id, self::ORIGINAL_POST_META_KEY, true);

        if (!$original_post_id) {
            wp_die(__('Original post ID not found', 'snn'));
        }

        // Get the original post
        $original_post = get_post($original_post_id);

        if (!$original_post) {
            wp_die(__('Original post not found', 'snn'));
        }

        // Check permission to edit the original post
        if (!current_user_can('edit_post', $original_post_id)) {
            wp_die(__('You do not have permission to edit the original post', 'snn'));
        }

        // Sync the draft to the original
        $result = $this->sync_posts($draft_post, $original_post);

        if (is_wp_error($result)) {
            wp_die($result->get_error_message());
        }

        // Move draft to trash
        wp_trash_post($draft_post_id);

        // Redirect to the original post
        $redirect_url = add_query_arg(
            array(
                'post' => $original_post_id,
                'action' => 'edit',
                'snn_synced' => '1'
            ),
            admin_url('post.php')
        );

        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Sync all data from draft to original post
     */
    private function sync_posts($draft_post, $original_post) {
        global $wpdb;

        // Update the original post with draft data
        $update_data = array(
            'ID'                    => $original_post->ID,
            'post_content'          => $draft_post->post_content,
            'post_content_filtered' => $draft_post->post_content_filtered,
            'post_title'            => str_replace(' (Draft Revision)', '', $draft_post->post_title),
            'post_excerpt'          => $draft_post->post_excerpt,
            'comment_status'        => $draft_post->comment_status,
            'ping_status'           => $draft_post->ping_status,
            'post_password'         => $draft_post->post_password,
            'to_ping'               => $draft_post->to_ping,
            'pinged'                => $draft_post->pinged,
            'post_parent'           => $draft_post->post_parent,
            'menu_order'            => $draft_post->menu_order,
            'post_mime_type'        => $draft_post->post_mime_type,
        );

        // Update the post
        $result = wp_update_post($update_data, true);

        if (is_wp_error($result)) {
            return $result;
        }

        // Delete all existing meta from original post (except protected WordPress meta)
        $original_meta = get_post_meta($original_post->ID);

        if ($original_meta) {
            foreach ($original_meta as $meta_key => $meta_values) {
                // Keep essential WordPress meta
                if (in_array($meta_key, array('_edit_lock', '_edit_last'))) {
                    continue;
                }

                delete_post_meta($original_post->ID, $meta_key);
            }
        }

        // Copy all meta from draft to original
        $draft_meta = get_post_meta($draft_post->ID);

        if ($draft_meta) {
            foreach ($draft_meta as $meta_key => $meta_values) {
                // Skip the original post ID meta
                if ($meta_key === self::ORIGINAL_POST_META_KEY) {
                    continue;
                }

                // Skip WordPress internal meta that shouldn't be copied
                if (in_array($meta_key, array('_edit_lock', '_edit_last'))) {
                    continue;
                }

                foreach ($meta_values as $meta_value) {
                    // Maybe unserialize
                    $meta_value = maybe_unserialize($meta_value);
                    add_post_meta($original_post->ID, $meta_key, $meta_value);
                }
            }
        }

        // Sync featured image
        $draft_thumbnail_id = get_post_thumbnail_id($draft_post->ID);

        if ($draft_thumbnail_id) {
            set_post_thumbnail($original_post->ID, $draft_thumbnail_id);
        } else {
            // Remove featured image if draft doesn't have one
            delete_post_thumbnail($original_post->ID);
        }

        // Sync taxonomies (categories, tags, custom taxonomies)
        $taxonomies = get_object_taxonomies($original_post->post_type);

        if ($taxonomies) {
            foreach ($taxonomies as $taxonomy) {
                // Get terms from draft
                $terms = wp_get_object_terms($draft_post->ID, $taxonomy, array('fields' => 'ids'));

                if (!is_wp_error($terms)) {
                    // Set terms on original (this will replace existing terms)
                    wp_set_object_terms($original_post->ID, $terms, $taxonomy);
                }
            }
        }

        // Sync post format
        $draft_format = get_post_format($draft_post->ID);

        if ($draft_format) {
            set_post_format($original_post->ID, $draft_format);
        } else {
            // Remove post format if draft doesn't have one
            set_post_format($original_post->ID, false);
        }

        return true;
    }

    /**
     * Display admin notices
     */
    public function admin_notices() {
        // Revision created notice
        if (isset($_GET['snn_revision_created']) && $_GET['snn_revision_created'] == '1') {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>' . __('Draft revision created successfully!', 'snn') . '</strong> ' . __('You can now edit this draft. When ready, click "Sync with Original" to update the original post.', 'snn') . '</p>';
            echo '</div>';
        }

        // Synced notice
        if (isset($_GET['snn_synced']) && $_GET['snn_synced'] == '1') {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>' . __('Draft synced successfully!', 'snn') . '</strong> ' . __('All changes from the draft have been applied to this post. The draft has been moved to trash.', 'snn') . '</p>';
            echo '</div>';
        }
    }
}

// Initialize the class
new SNN_Draft_Revision();
