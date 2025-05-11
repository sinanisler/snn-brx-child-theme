<?php

if (!defined('ABSPATH')) {
    exit;
}

define('SNN_ROLE_PAGE_RESTRICTIONS_OPTION', 'snn_role_page_restrictions');
define('SNN_ROLE_MANAGER_VERSION', '5.1.0');


function snn_ensure_admin_capability() {
    if ( current_user_can('administrator') ) {
        $admin_role = get_role('administrator');
        if ($admin_role && !$admin_role->has_cap('manage_snn_roles')) {
            $admin_role->add_cap('manage_snn_roles', true);
        }
    }
    if (false === get_option(SNN_ROLE_PAGE_RESTRICTIONS_OPTION)) {
        add_option(SNN_ROLE_PAGE_RESTRICTIONS_OPTION, [], '', 'no');
    }
}
add_action('admin_init', 'snn_ensure_admin_capability');


function snn_add_role_management_submenu_page() {
    add_submenu_page(
        'snn-settings',
        __('Role Management', 'snn-role-manager'),
        __('Role Management', 'snn-role-manager'),
        'manage_snn_roles',
        'snn-role-management',
        'snn_render_role_management_page'
    );
}
add_action('admin_menu', 'snn_add_role_management_submenu_page');


function snn_get_all_capabilities() {
    global $wp_roles;
    if (!isset($wp_roles)) { $wp_roles = new WP_Roles(); }

    $all_caps = [];
    foreach ($wp_roles->role_objects as $role) {
        if (isset($role->capabilities) && is_array($role->capabilities)) {
            $all_caps = array_merge($all_caps, array_keys($role->capabilities));
        }
    }
    $potential_caps = ['edit_pages', 'edit_posts', 'read'];
    $all_caps = array_merge($all_caps, $potential_caps);
    $unique_caps = array_unique($all_caps);
    sort($unique_caps);
    return $unique_caps;
}


function snn_is_core_role($role_slug) {
    $core_roles = ['administrator', 'editor', 'author', 'contributor', 'subscriber'];
    return in_array($role_slug, $core_roles, true);
}


function snn_get_all_page_restrictions() {
    return get_option(SNN_ROLE_PAGE_RESTRICTIONS_OPTION, []);
}


function snn_update_role_page_restrictions($role_id, $page_ids) {
    $all_restrictions = snn_get_all_page_restrictions();
    $sanitized_page_ids = array_map('absint', array_filter($page_ids, 'is_numeric'));
    $sanitized_page_ids = array_unique($sanitized_page_ids);

    if (empty($sanitized_page_ids)) {
        unset($all_restrictions[$role_id]);
    } else {
        $all_restrictions[$role_id] = $sanitized_page_ids;
    }
    return update_option(SNN_ROLE_PAGE_RESTRICTIONS_OPTION, $all_restrictions);
}


function snn_delete_role_page_restrictions($role_id) {
    $all_restrictions = snn_get_all_page_restrictions();
    if (isset($all_restrictions[$role_id])) {
        unset($all_restrictions[$role_id]);
        return update_option(SNN_ROLE_PAGE_RESTRICTIONS_OPTION, $all_restrictions);
    }
    return true;
}


function snn_handle_role_management_actions() {
    if (!isset($_POST['_snn_role_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_snn_role_nonce'])), 'snn_role_action') || !isset($_POST['snn_action'])) {
        return;
    }
    if (!current_user_can('manage_snn_roles')) {
        wp_die(__('You do not have sufficient permissions to manage roles.', 'snn-role-manager'));
    }

    $action = sanitize_key($_POST['snn_action']);
    $message = '';
    $type = 'error';

    try {
        switch ($action) {
            case 'add_role':
                $role_id = isset($_POST['role_id']) ? sanitize_key($_POST['role_id']) : '';
                $role_display_name = isset($_POST['role_display_name']) ? sanitize_text_field(wp_unslash($_POST['role_display_name'])) : '';
                $capabilities_input = isset($_POST['capabilities']) && is_array($_POST['capabilities']) ? wp_unslash($_POST['capabilities']) : [];
                $allowed_page_ids_str = isset($_POST['snn_allowed_page_ids_hidden']) ? sanitize_text_field(wp_unslash($_POST['snn_allowed_page_ids_hidden'])) : '';
                $allowed_page_ids = !empty($allowed_page_ids_str) ? explode(',', $allowed_page_ids_str) : [];

                if (empty($role_id) || empty($role_display_name)) throw new Exception(__('Role ID and Display Name are required.', 'snn-role-manager'));
                if (!preg_match('/^[a-z0-9_]+$/', $role_id)) throw new Exception(__('Role ID can only contain lowercase letters, numbers, and underscores.', 'snn-role-manager'));
                if ($role_id === 'administrator') throw new Exception(__('Cannot create a role with the ID "administrator".', 'snn-role-manager'));
                if (get_role($role_id)) throw new Exception(sprintf(__('Role "%s" already exists.', 'snn-role-manager'), esc_html($role_id)));
                if (snn_is_core_role($role_id)) throw new Exception(sprintf(__('Cannot create a role with the core ID "%s". Use a unique ID.', 'snn-role-manager'), esc_html($role_id)));

                $role_caps = [];
                foreach ($capabilities_input as $cap_name => $value) {
                    if ($value === '1') {
                        $sanitized_cap_name = sanitize_key($cap_name);
                        if (!empty($sanitized_cap_name)) $role_caps[$sanitized_cap_name] = true;
                    }
                }
                if (!isset($role_caps['read'])) $role_caps['read'] = true;

                $result = add_role($role_id, $role_display_name, $role_caps);
                if ($result instanceof WP_Role) {
                    snn_update_role_page_restrictions($role_id, $allowed_page_ids);
                    $message = sprintf(__('Role "%s" created successfully.', 'snn-role-manager'), esc_html($role_display_name));
                    $type = 'success';
                } else {
                    throw new Exception(sprintf(__('Failed to create role "%s".', 'snn-role-manager'), esc_html($role_display_name)));
                }
                break;

            case 'update_role':
                $role_id = isset($_POST['role_id']) ? sanitize_key($_POST['role_id']) : '';
                $capabilities_input = isset($_POST['capabilities']) && is_array($_POST['capabilities']) ? wp_unslash($_POST['capabilities']) : [];
                $allowed_page_ids_str = isset($_POST['snn_allowed_page_ids_hidden']) ? sanitize_text_field(wp_unslash($_POST['snn_allowed_page_ids_hidden'])) : '';
                $allowed_page_ids = !empty($allowed_page_ids_str) ? explode(',', $allowed_page_ids_str) : [];

                if ($role_id === 'administrator') throw new Exception(__('The Administrator role cannot be modified here.', 'snn-role-manager'));
                $role = get_role($role_id);
                if (!$role) throw new Exception(sprintf(__('Role "%s" not found for updating.', 'snn-role-manager'), esc_html($role_id)));
                if (!isset(get_editable_roles()[$role_id])) throw new Exception(__('You do not have permission to edit this specific role.', 'snn-role-manager'));

                $new_caps_selected = [];
                foreach ($capabilities_input as $cap_name => $value) {
                    if ($value === '1') {
                        $sanitized_cap_name = sanitize_key($cap_name);
                        if (!empty($sanitized_cap_name)) $new_caps_selected[$sanitized_cap_name] = true;
                    }
                }
                if ($role->has_cap('read') || !snn_is_core_role($role_id)) $new_caps_selected['read'] = true;

                foreach ($new_caps_selected as $cap => $val) {
                    if (!$role->has_cap($cap)) $role->add_cap($cap, true);
                }
                $visible_caps_in_form = snn_get_all_capabilities();
                foreach ($visible_caps_in_form as $cap) {
                   if ($role->has_cap($cap) && !isset($new_caps_selected[$cap]) && $cap !== 'read') {
                       $role->remove_cap($cap);
                   }
                }

                snn_update_role_page_restrictions($role_id, $allowed_page_ids);

                $message = sprintf(__('Role "%s" updated successfully.', 'snn-role-manager'), esc_html(translate_user_role($role->name)));
                $type = 'success';
                break;

            case 'delete_role':
                $role_id = isset($_POST['role_id']) ? sanitize_key($_POST['role_id']) : '';

                if (empty($role_id)) throw new Exception(__('No role specified for deletion.', 'snn-role-manager'));
                if ($role_id === 'administrator') throw new Exception(__('The Administrator role cannot be deleted.', 'snn-role-manager'));
                if (snn_is_core_role($role_id)) throw new Exception(__('Core WordPress roles cannot be deleted.', 'snn-role-manager'));
                if (!isset(get_editable_roles()[$role_id])) throw new Exception(__('You do not have permission to delete this specific role.', 'snn-role-manager'));
                $role_to_delete = get_role($role_id);
                if (!$role_to_delete) throw new Exception(sprintf(__('Role "%s" not found or already deleted.', 'snn-role-manager'), esc_html($role_id)));

                $role_display_name_before_delete = translate_user_role($role_to_delete->name);
                if (remove_role($role_id)) {
                    snn_delete_role_page_restrictions($role_id);
                    $message = sprintf(__('Role "%s" (%s) deleted successfully.', 'snn-role-manager'), esc_html($role_display_name_before_delete), esc_html($role_id));
                    $type = 'success';
                } else {
                    throw new Exception(sprintf(__('Failed to delete role "%s".', 'snn-role-manager'), esc_html($role_display_name_before_delete)));
                }
                break;

            default: return;
        }
        add_settings_error('snn_role_manager_notices', esc_attr('settings_updated'), $message, $type);
    } catch (Exception $e) {
        add_settings_error('snn_role_manager_notices', esc_attr('settings_error'), $e->getMessage(), 'error');
    }
}
add_action('admin_init', 'snn_handle_role_management_actions');


function snn_filter_page_edit_capability($required_caps, $cap, $user_id, $args) {
    $target_caps = ['edit_post', 'delete_post', 'edit_page', 'delete_page'];
    if (!in_array($cap, $target_caps) || empty($args[0])) {
        return $required_caps;
    }

    $post_id = absint($args[0]);
    $post = get_post($post_id);
    $post_type_object = $post ? get_post_type_object($post->post_type) : null;

    if (!$post || !$post_type_object || !$post_type_object->public) {
        return $required_caps;
    }

    $user = get_userdata($user_id);
    if (!$user || empty($user->roles)) {
        return $required_caps;
    }

    if (user_can($user_id, 'manage_options')) {
         return $required_caps;
    }

    $all_restrictions = snn_get_all_page_restrictions();
    if (empty($all_restrictions)) {
        return $required_caps;
    }

    $user_roles = $user->roles;
    $user_allowed_page_ids = [];
    $has_restricted_role_with_edit_cap = false;
    $has_unrestricted_edit_role = false;

    if ($cap === 'edit_post' || $cap === 'edit_page') {
        $primitive_cap = $post_type_object->cap->edit_posts;
    } elseif ($cap === 'delete_post' || $cap === 'delete_page') {
        $primitive_cap = $post_type_object->cap->delete_posts;
    } else {
        return $required_caps;
    }

    foreach ($user_roles as $role_slug) {
        $role_object = get_role($role_slug);
        if (!$role_object) continue;

        if ($role_object->has_cap($primitive_cap)) {
            if (isset($all_restrictions[$role_slug])) {
                $has_restricted_role_with_edit_cap = true;
                $user_allowed_page_ids = array_merge($user_allowed_page_ids, $all_restrictions[$role_slug]);
            } else {
                $has_unrestricted_edit_role = true;
                break;
            }
        }
    }

    if ($has_unrestricted_edit_role) {
        return $required_caps;
    }

    if ($has_restricted_role_with_edit_cap) {
        $user_allowed_page_ids = array_unique(array_map('absint', $user_allowed_page_ids));

        if (in_array($post_id, $user_allowed_page_ids, true)) {
            if (user_can($user_id, $primitive_cap)) {
                 return [$primitive_cap];
             } else {
                 return ['do_not_allow'];
             }
        } else {
            return ['do_not_allow'];
        }
    }

    return $required_caps;
}
add_filter('map_meta_cap', 'snn_filter_page_edit_capability', 10, 4);


function snn_filter_admin_page_list($query) {
    if (!is_admin() || !$query->is_main_query() || !function_exists('get_current_screen')) {
        return;
    }
    $screen = get_current_screen();
    if (!$screen || $screen->base !== 'edit') {
        return;
    }
    $post_type = $screen->post_type;
    $post_type_object = get_post_type_object($post_type);
    if (!$post_type_object || !$post_type_object->public) {
        return;
    }

    $user_id = get_current_user_id();
    $user = get_userdata($user_id);

    if (!$user || empty($user->roles)) return;
    if (current_user_can('manage_options')) return;

    $all_restrictions = snn_get_all_page_restrictions();
    if (empty($all_restrictions)) return;

    $user_roles = $user->roles;
    $user_allowed_page_ids = [];
    $user_is_restricted_by_plugin = false;
    $has_unrestricted_edit_role = false;

    $edit_cap = $post_type_object->cap->edit_posts;

    foreach ($user_roles as $role_slug) {
        $role_object = get_role($role_slug);
        if (!$role_object) continue;

        if ($role_object->has_cap($edit_cap)) {
            if (isset($all_restrictions[$role_slug])) {
                $user_is_restricted_by_plugin = true;
                $user_allowed_page_ids = array_merge($user_allowed_page_ids, $all_restrictions[$role_slug]);
            } else {
                $has_unrestricted_edit_role = true;
                break;
            }
        }
    }

    if ($has_unrestricted_edit_role) {
        return;
    }

    if ($user_is_restricted_by_plugin) {
        $user_allowed_page_ids = array_unique(array_map('absint', $user_allowed_page_ids));
        if (empty($user_allowed_page_ids)) {
            $query->set('post__in', [0]);
        } else {
            $existing_post_in = $query->get('post__in');
            if (!empty($existing_post_in)) {
                $allowed_ids = array_intersect($existing_post_in, $user_allowed_page_ids);
                 $query->set('post__in', !empty($allowed_ids) ? $allowed_ids : [0]);
            } else {
                 $query->set('post__in', $user_allowed_page_ids);
            }
        }
    }
}
add_action('pre_get_posts', 'snn_filter_admin_page_list');


function snn_render_role_management_page() {
    if (!current_user_can('manage_snn_roles')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'snn-role-manager'));
    }
    settings_errors('snn_role_manager_notices');

    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'manage_roles';
    $edit_role_id = isset($_GET['edit_role']) ? sanitize_key($_GET['edit_role']) : null;
    $page_slug = 'snn-role-management';

    if ($edit_role_id === 'administrator') {
        $edit_role_id = null;
        add_settings_error('snn_role_manager_notices', 'cannot_edit_admin', __('The Administrator role cannot be managed here.', 'snn-role-manager'), 'error');
        settings_errors('snn_role_manager_notices');
        $active_tab = 'manage_roles';
    } elseif ($edit_role_id && !isset(get_editable_roles()[$edit_role_id])) {
        add_settings_error('snn_role_manager_notices', 'cannot_edit_role', __('You do not have permission to edit the specified role.', 'snn-role-manager'), 'error');
        settings_errors('snn_role_manager_notices');
        $edit_role_id = null;
        $active_tab = 'manage_roles';
    }

    ?>
    <div class="wrap snn-role-manager">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <p><?php _e('Manage user roles and capabilities. Core roles cannot be deleted and the Administrator role cannot be modified. You can restrict editing access for roles to specific posts or pages.', 'snn-role-manager'); ?></p>

        <nav class="nav-tab-wrapper wp-clearfix" aria-label="<?php esc_attr_e('Secondary menu', 'snn-role-manager'); ?>">
            <a href="<?php echo esc_url(admin_url('admin.php?page=' . $page_slug . '&tab=manage_roles')); ?>"
               class="nav-tab <?php echo $active_tab == 'manage_roles' && !$edit_role_id ? 'nav-tab-active' : ''; ?>"
               aria-current="<?php echo $active_tab == 'manage_roles' && !$edit_role_id ? 'page' : 'false'; ?>">
                <?php _e('Manage Roles', 'snn-role-manager'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=' . $page_slug . '&tab=add_role')); ?>"
               class="nav-tab <?php echo $active_tab == 'add_role' ? 'nav-tab-active' : ''; ?>"
               aria-current="<?php echo $active_tab == 'add_role' ? 'page' : 'false'; ?>">
                <?php _e('Add New Role', 'snn-role-manager'); ?>
            </a>
            <?php
            if ($edit_role_id && $active_tab == 'manage_roles') :
                $role_being_edited = get_role($edit_role_id);
                 if ($role_being_edited && isset(get_editable_roles()[$edit_role_id]) && $edit_role_id !== 'administrator') {
                      $edit_title = translate_user_role($role_being_edited->name);
                      ?>
                      <span class="nav-tab nav-tab-active"><?php printf(__('Editing Role: %s', 'snn-role-manager'), esc_html($edit_title)); ?></span>
                      <?php
                 } else {
                      $edit_role_id = null;
                 }
            endif; ?>
        </nav>

        <div class="snn-role-manager-content">
            <?php
            if ($edit_role_id && $active_tab == 'manage_roles') {
                 if ($edit_role_id !== 'administrator' && isset(get_editable_roles()[$edit_role_id])) {
                      snn_render_edit_role_form($edit_role_id);
                 } else {
                      snn_render_manage_roles_list();
                 }
            } elseif ($active_tab == 'manage_roles') {
                snn_render_manage_roles_list();
            } elseif ($active_tab == 'add_role') {
                snn_render_add_role_form();
            }
            ?>
        </div>
    </div>
    <?php
}

function snn_render_manage_roles_list() {
    global $wp_roles;
    if (!isset($wp_roles)) { $wp_roles = new WP_Roles(); }
    $roles = $wp_roles->get_names();
    $editable_roles = get_editable_roles();
    $all_restrictions = snn_get_all_page_restrictions();
    $page_slug = 'snn-role-management';
    ?>
    <h2><?php _e('Existing Roles', 'snn-role-manager'); ?></h2>
    <p><?php _e('View, edit, or delete user roles. Core roles cannot be deleted, and the Administrator role cannot be modified or deleted from this interface.', 'snn-role-manager'); ?></p>

    <table class="wp-list-table widefat fixed striped roles">
        <thead>
            <tr>
                <th scope="col" id="role_name" class="manage-column column-role_name column-primary"><?php _e('Display Name', 'snn-role-manager'); ?></th>
                <th scope="col" id="role_id" class="manage-column column-role_id"><?php _e('Role ID (Slug)', 'snn-role-manager'); ?></th>
                <th scope="col" id="capabilities" class="manage-column column-capabilities"><?php _e('Capabilities', 'snn-role-manager'); ?></th>
                <th scope="col" id="restrictions" class="manage-column column-restrictions"><?php _e('Post Edit Allowlist', 'snn-role-manager'); ?></th>
                <th scope="col" id="actions" class="manage-column column-actions"><?php _e('Actions', 'snn-role-manager'); ?></th>
            </tr>
        </thead>
        <tbody id="the-list">
            <?php
            if (!empty($roles)) {
                uasort($roles, function($a, $b) { return strcasecmp(translate_user_role($a), translate_user_role($b)); });

                if (isset($roles['administrator'])) {
                     $admin_role_name = translate_user_role($roles['administrator']);
                     $admin_role_object = get_role('administrator');
                     $admin_caps = $admin_role_object ? $admin_role_object->capabilities : [];
                     $admin_cap_count = count($admin_caps);
                     $admin_capability_keys = array_keys($admin_caps); sort($admin_capability_keys);
                     ?>
                     <tr>
                         <td class="column-role_name column-primary" data-colname="<?php esc_attr_e('Display Name', 'snn-role-manager'); ?>">
                             <strong><?php echo esc_html($admin_role_name); ?></strong>
                             <br><span class="description">(<?php _e('Core Role', 'snn-role-manager'); ?>)</span>
                             <button type="button" class="toggle-row"><span class="screen-reader-text"><?php _e( 'Show more details' ); ?></span></button>
                         </td>
                         <td class="column-role_id" data-colname="<?php esc_attr_e('Role ID (Slug)', 'snn-role-manager'); ?>"><code>administrator</code></td>
                         <td class="column-capabilities" data-colname="<?php esc_attr_e('Capabilities', 'snn-role-manager'); ?>">
                             <?php if ($admin_cap_count > 0) : ?><details><summary><?php printf(_n('%d Capability', '%d Capabilities', $admin_cap_count, 'snn-role-manager'), $admin_cap_count); ?> <span class="details-hint">(<?php _e('click to view', 'snn-role-manager'); ?>)</span></summary><div class="capabilities-list"><?php echo implode(', ', array_map(function($cap) { return '<code>' . esc_html($cap) . '</code>'; }, $admin_capability_keys)); ?></div></details><?php else : ?><span class="na"><?php _e('None', 'snn-role-manager'); ?></span><?php endif; ?>
                         </td>
                         <td class="column-restrictions" data-colname="<?php esc_attr_e('Post Edit Restrictions', 'snn-role-manager'); ?>">
                             <span class="na"><?php _e('N/A', 'snn-role-manager'); ?></span>
                         </td>
                         <td class="column-actions" data-colname="<?php esc_attr_e('Actions', 'snn-role-manager'); ?>">
                             <button class="button button-secondary button-small" disabled title="<?php esc_attr_e('Administrator role cannot be modified here.', 'snn-role-manager'); ?>"><?php _e('Edit', 'snn-role-manager'); ?></button>
                             <button class="button button-link-delete button-small" disabled title="<?php esc_attr_e('Administrator role cannot be deleted.', 'snn-role-manager'); ?>"><?php _e('Delete', 'snn-role-manager'); ?></button>
                         </td>
                     </tr>
                     <?php
                }

                foreach ($roles as $role_id => $role_display_name) :
                    if ($role_id === 'administrator') continue;

                    $role_object = get_role($role_id);
                    if (!$role_object) continue;

                    $capabilities = $role_object->capabilities;
                    $cap_count = count($capabilities);
                    $capability_keys = array_keys($capabilities); sort($capability_keys);
                    $is_core = snn_is_core_role($role_id);
                    $can_edit_this_role = isset($editable_roles[$role_id]);
                    $role_restrictions = isset($all_restrictions[$role_id]) ? $all_restrictions[$role_id] : [];
                    $has_restrictions = !empty($role_restrictions);
                    ?>
                    <tr>
                        <td class="column-role_name column-primary" data-colname="<?php esc_attr_e('Display Name', 'snn-role-manager'); ?>">
                            <strong><?php echo esc_html(translate_user_role($role_display_name)); ?></strong>
                            <?php if ($is_core) echo '<br><span class="description">(' . __('Core Role', 'snn-role-manager') . ')</span>'; ?>
                            <button type="button" class="toggle-row"><span class="screen-reader-text"><?php _e( 'Show more details' ); ?></span></button>
                        </td>
                        <td class="column-role_id" data-colname="<?php esc_attr_e('Role ID (Slug)', 'snn-role-manager'); ?>"><code><?php echo esc_html($role_id); ?></code></td>
                        <td class="column-capabilities" data-colname="<?php esc_attr_e('Capabilities', 'snn-role-manager'); ?>">
                             <?php if ($cap_count > 0) : ?><details><summary><?php printf(_n('%d Capability', '%d Capabilities', $cap_count, 'snn-role-manager'), $cap_count); ?> <span class="details-hint">(<?php _e('click to view', 'snn-role-manager'); ?>)</span></summary><div class="capabilities-list"><?php echo implode(', ', array_map(function($cap) { return '<code>' . esc_html($cap) . '</code>'; }, $capability_keys)); ?></div></details><?php else : ?><span class="na"><?php _e('None', 'snn-role-manager'); ?></span><?php endif; ?>
                        </td>
                        <td class="column-restrictions" data-colname="<?php esc_attr_e('Post Edit Restrictions', 'snn-role-manager'); ?>">
                            <?php
                            if ($has_restrictions) {
                                $restricted_post_titles = [];
                                $restricted_posts = get_posts([
                                    'post__in' => $role_restrictions,
                                    'post_type' => 'any',
                                    'numberposts' => -1,
                                    'orderby' => 'title',
                                    'order' => 'ASC',
                                    'suppress_filters' => true
                                ]);
                                foreach ($restricted_posts as $restricted_post) {
                                    $restricted_post_titles[] = esc_html($restricted_post->post_title) . ' (' . esc_html($restricted_post->ID) . ')';
                                }
                                $missing_ids = array_diff($role_restrictions, wp_list_pluck($restricted_posts, 'ID'));
                                foreach ($missing_ids as $missing_id) {
                                     $restricted_post_titles[] = sprintf(__('ID %d (Not Found)', 'snn-role-manager'), $missing_id);
                                }

                                if (!empty($restricted_post_titles)) {
                                    echo '<details><summary>' . sprintf(_n('%d Post', '%d Posts', count($role_restrictions), 'snn-role-manager'), count($role_restrictions)) . ' <span class="details-hint">(' . __('click to view', 'snn-role-manager') . ')</span></summary>';
                                    echo '<div class="capabilities-list">' . implode('<br>', $restricted_post_titles) . '</div></details>';
                                } else {
                                     echo '<span class="na">' . __('None', 'snn-role-manager') . '</span>';
                                }

                            } else {
                                echo '<span class="na">' . __('None', 'snn-role-manager') . '</span>';
                            }
                            ?>
                        </td>
                        <td class="column-actions" data-colname="<?php esc_attr_e('Actions', 'snn-role-manager'); ?>">
                            <?php if ($can_edit_this_role): ?>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=' . $page_slug . '&tab=manage_roles&edit_role=' . urlencode($role_id))); ?>" class="button button-secondary button-small"><?php _e('Edit', 'snn-role-manager'); ?></a>
                            <?php else: ?>
                                <button class="button button-secondary button-small" disabled title="<?php esc_attr_e('You do not have permission to edit this role.', 'snn-role-manager'); ?>"><?php _e('Edit', 'snn-role-manager'); ?></button>
                            <?php endif; ?>

                            <?php if (!$is_core && $can_edit_this_role) : ?>
                                <form method="post" action="" class="delete-role-form" onsubmit="return confirm('<?php echo esc_js(sprintf(__('Are you absolutely sure you want to delete the role "%s"? This action cannot be undone. Users assigned to this role might lose permissions or be reassigned to the default role. Any post editing restrictions for this role will also be removed.', 'snn-role-manager'), translate_user_role($role_display_name))); ?>');">
                                    <input type="hidden" name="snn_action" value="delete_role">
                                    <input type="hidden" name="role_id" value="<?php echo esc_attr($role_id); ?>">
                                    <?php wp_nonce_field('snn_role_action', '_snn_role_nonce'); ?>
                                    <button type="submit" class="button button-link-delete button-small"><?php _e('Delete', 'snn-role-manager'); ?></button>
                                </form>
                            <?php elseif ($is_core): ?>
                                <button class="button button-link-delete button-small" disabled title="<?php esc_attr_e('Core roles cannot be deleted.', 'snn-role-manager'); ?>"><?php _e('Delete', 'snn-role-manager'); ?></button>
                            <?php else: ?>
                                <button class="button button-link-delete button-small" disabled title="<?php esc_attr_e('You do not have permission to delete this role.', 'snn-role-manager'); ?>"><?php _e('Delete', 'snn-role-manager'); ?></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach;
            } else { ?>
                <tr><td colspan="5"><?php _e('No editable roles found (excluding Administrator). If you expect to see core roles like Editor, check plugin conflicts or user permissions.', 'snn-role-manager'); ?></td></tr>
            <?php } ?>
        </tbody>
        <tfoot>
             <tr>
                <th scope="col" class="manage-column column-role_name column-primary"><?php _e('Display Name', 'snn-role-manager'); ?></th>
                <th scope="col" class="manage-column column-role_id"><?php _e('Role ID (Slug)', 'snn-role-manager'); ?></th>
                <th scope="col" class="manage-column column-capabilities"><?php _e('Capabilities', 'snn-role-manager'); ?></th>
                <th scope="col" class="manage-column column-restrictions"><?php _e('Post Edit Restrictions', 'snn-role-manager'); ?></th>
                <th scope="col" class="manage-column column-actions"><?php _e('Actions', 'snn-role-manager'); ?></th>
             </tr>
        </tfoot>
    </table>
    <?php
}

function snn_render_add_role_form() {
    $all_capabilities = snn_get_all_capabilities();
    $page_slug = 'snn-role-management';
    ?>
    <h2><?php _e('Add New Role', 'snn-role-manager'); ?></h2>
    <p><?php _e('Create a new custom user role and assign initial capabilities and optional post editing restrictions.', 'snn-role-manager'); ?></p>
    <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=' . $page_slug . '&tab=add_role')); ?>">
        <input type="hidden" name="snn_action" value="add_role">
        <?php wp_nonce_field('snn_role_action', '_snn_role_nonce'); ?>
        <table class="form-table" role="presentation"><tbody>
            <tr><th scope="row"><label for="snn-role-id"><?php _e('Role ID (Slug)', 'snn-role-manager'); ?></label></th><td><input name="role_id" type="text" id="snn-role-id" value="" class="regular-text" required pattern="[a-z0-9_]+" title="<?php esc_attr_e('Lowercase letters, numbers, and underscores only.', 'snn-role-manager'); ?>" aria-describedby="snn-role-id-desc"><p class="description" id="snn-role-id-desc"><?php _e('Unique identifier (slug) for the role. Use only lowercase letters, numbers, and underscores (e.g., "event_manager"). Cannot be changed later. Cannot be "administrator" or a core role ID.', 'snn-role-manager'); ?></p></td></tr>
            <tr><th scope="row"><label for="snn-role-display-name"><?php _e('Display Name', 'snn-role-manager'); ?></label></th><td><input name="role_display_name" type="text" id="snn-role-display-name" value="" class="regular-text" required aria-describedby="snn-role-display-name-desc"><p class="description" id="snn-role-display-name-desc"><?php _e('The name displayed in the WordPress admin area (e.g., "Event Manager").', 'snn-role-manager'); ?></p></td></tr>
            <tr>
                <th scope="row"><?php _e('Initial Capabilities', 'snn-role-manager'); ?></th>
                <td>
                    <p class="snn-capability-search-wrap">
                        <label for="snn-capability-search-add" class="screen-reader-text"><?php _e('Search Capabilities:', 'snn-role-manager'); ?></label>
                        <input type="text" id="snn-capability-search-add" class="regular-text snn-capability-search" placeholder="<?php esc_attr_e('Search Capabilities...', 'snn-role-manager'); ?>" aria-controls="snn-capabilities-list-add">
                    </p>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php _e('Capabilities', 'snn-role-manager'); ?></span></legend>
                        <p><?php _e('Select the capabilities this role should have. The "read" capability is essential and automatically assigned. To enable post/page editing and restrictions, ensure the relevant "edit_posts", "edit_pages", or custom post type edit capability is selected.', 'snn-role-manager'); ?></p>
                        <div class="capabilities-checkbox-list">
                            <ul class="capabilities-columns" id="snn-capabilities-list-add">
                                <?php if (!empty($all_capabilities)) : foreach ($all_capabilities as $cap) :
                                    $is_disabled = ($cap === 'read');
                                    ?>
                                    <li class="capability-item">
                                        <label title="<?php echo esc_attr($cap); ?>">
                                            <input type="checkbox" name="capabilities[<?php echo esc_attr($cap); ?>]" value="1" <?php checked($is_disabled); ?> <?php disabled($is_disabled); ?>>
                                            <code><?php echo esc_html($cap); ?></code>
                                            <?php if ($is_disabled) echo ' <span class="required-cap">(' . __('Required', 'snn-role-manager') . ')</span>'; ?>
                                        </label>
                                    </li>
                                <?php endforeach; else: ?>
                                <li><?php _e('No capabilities found to assign.', 'snn-role-manager'); ?></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Restrict Post Editing (Optional)', 'snn-role-manager'); ?></th>
                <td>
                    <div class="snn-post-restriction-control">
                        <label for="snn-post-search-add" class="screen-reader-text"><?php _e('Search Posts/Pages:', 'snn-role-manager'); ?></label>
                        <input type="text" id="snn-post-search-add" class="regular-text snn-post-search-input" placeholder="<?php esc_attr_e('Search by title...', 'snn-role-manager'); ?>">
                        <div class="snn-search-results" id="snn-search-results-add" style="display: none;"></div>
                        <div class="snn-selected-posts" id="snn-selected-posts-add">
                            <span class="placeholder"><?php _e('No posts selected.', 'snn-role-manager'); ?></span>
                        </div>
                        <input type="hidden" name="snn_allowed_page_ids_hidden" id="snn-allowed-page-ids-hidden-add" value="">
                        <p class="description"><?php _e('Search for and select specific Posts, Pages, or other public post types that users with this role should be allowed to edit. Leave empty to allow editing of any post (requires the relevant "edit" capability above).', 'snn-role-manager'); ?></p>
                    </div>
                </td>
            </tr>
        </tbody></table>
        <?php submit_button(__('Add New Role', 'snn-role-manager')); ?>
    </form>
    <?php
}

function snn_render_edit_role_form($role_id) {
    $role_object = get_role($role_id);
    if (!$role_object || !isset(get_editable_roles()[$role_id]) || $role_id === 'administrator') {
        wp_die(__('Invalid role specified, you do not have permission to edit it, or you attempted to edit the Administrator role.', 'snn-role-manager'));
    }

    $role_caps = $role_object->capabilities;
    $all_capabilities = snn_get_all_capabilities();
    $is_core = snn_is_core_role($role_id);

    $combined_capabilities = array_unique(array_merge($all_capabilities, array_keys($role_caps)));
    sort($combined_capabilities);

    $all_restrictions = snn_get_all_page_restrictions();
    $current_restrictions = isset($all_restrictions[$role_id]) ? $all_restrictions[$role_id] : [];
    $current_restrictions_str = implode(',', $current_restrictions);

    $selected_posts_data = [];
    if (!empty($current_restrictions)) {
        $posts = get_posts([
            'post__in' => $current_restrictions,
            'post_type' => 'any',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'suppress_filters' => true
        ]);
        foreach ($posts as $post) {
            $post_type_obj = get_post_type_object($post->post_type);
            $selected_posts_data[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'type' => $post_type_obj ? $post_type_obj->labels->singular_name : $post->post_type
            ];
        }
         $found_ids = wp_list_pluck($selected_posts_data, 'id');
         $missing_ids = array_diff($current_restrictions, $found_ids);
         foreach($missing_ids as $missing_id) {
              $selected_posts_data[] = [
                   'id' => $missing_id,
                   'title' => sprintf(__('ID %d (Not Found)', 'snn-role-manager'), $missing_id),
                   'type' => __('N/A', 'snn-role-manager')
              ];
         }
    }

    $page_slug = 'snn-role-management';
    ?>
    <h2><?php printf(__('Edit Role: %s', 'snn-role-manager'), esc_html(translate_user_role($role_object->name))); ?> <code>(<?php echo esc_html($role_id); ?>)</code></h2>
    <?php if ($is_core) : ?><div class="notice notice-warning inline notice-alt"><p><strong><?php _e('Warning:', 'snn-role-manager'); ?></strong> <?php _e('You are editing a core WordPress role. Modifying core capabilities can lead to unexpected behavior or security issues. Proceed with caution.', 'snn-role-manager'); ?></p></div><?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=' . $page_slug . '&tab=manage_roles')); ?>">
        <input type="hidden" name="snn_action" value="update_role">
        <input type="hidden" name="role_id" value="<?php echo esc_attr($role_id); ?>">
        <?php wp_nonce_field('snn_role_action', '_snn_role_nonce'); ?>
        <table class="form-table" role="presentation"><tbody>
            <tr>
                <th scope="row"><?php _e('Capabilities', 'snn-role-manager'); ?></th>
                <td>
                     <p class="snn-capability-search-wrap">
                         <label for="snn-capability-search-edit" class="screen-reader-text"><?php _e('Search Capabilities:', 'snn-role-manager'); ?></label>
                         <input type="text" id="snn-capability-search-edit" class="regular-text snn-capability-search" placeholder="<?php esc_attr_e('Search Capabilities...', 'snn-role-manager'); ?>" aria-controls="snn-capabilities-list-edit">
                     </p>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php _e('Capabilities', 'snn-role-manager'); ?></span></legend>
                        <p><?php _e('Select the capabilities this role should have. The "read" capability is essential and cannot be removed. To enable post/page editing and restrictions, ensure the relevant "edit" capability is selected.', 'snn-role-manager'); ?></p>
                        <div class="capabilities-checkbox-list">
                            <ul class="capabilities-columns" id="snn-capabilities-list-edit">
                                <?php if (!empty($combined_capabilities)) : foreach ($combined_capabilities as $cap) :
                                    $is_checked = isset($role_caps[$cap]) && $role_caps[$cap];
                                    $is_disabled = ($cap === 'read');
                                ?>
                                    <li class="capability-item">
                                        <label title="<?php echo esc_attr($cap); ?>">
                                            <input type="checkbox" name="capabilities[<?php echo esc_attr($cap); ?>]" value="1" <?php checked($is_checked); ?> <?php disabled($is_disabled); ?>>
                                            <code><?php echo esc_html($cap); ?></code>
                                            <?php if ($is_disabled) echo ' <span class="required-cap">(' . __('Required', 'snn-role-manager') . ')</span>'; ?>
                                        </label>
                                    </li>
                                <?php endforeach; else: ?>
                                <li><?php _e('No capabilities found.', 'snn-role-manager'); ?></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Restrict Post Editing (Optional)', 'snn-role-manager'); ?></th>
                 <td>
                    <div class="snn-post-restriction-control">
                        <label for="snn-post-search-edit" class="screen-reader-text"><?php _e('Search Posts/Pages:', 'snn-role-manager'); ?></label>
                        <input type="text" id="snn-post-search-edit" class="regular-text snn-post-search-input" placeholder="<?php esc_attr_e('Search by title...', 'snn-role-manager'); ?>">
                        <div class="snn-search-results" id="snn-search-results-edit" style="display: none;"></div>
                        <div class="snn-selected-posts" id="snn-selected-posts-edit">
                             <?php if (empty($selected_posts_data)) : ?>
                                 <span class="placeholder"><?php _e('No posts selected.', 'snn-role-manager'); ?></span>
                             <?php else : ?>
                                 <?php foreach ($selected_posts_data as $post_data) : ?>
                                     <span class="snn-selected-post-item" data-id="<?php echo esc_attr($post_data['id']); ?>">
                                         <?php echo esc_html($post_data['title']); ?> (<?php echo esc_html($post_data['type']); ?>)
                                         <button type="button" class="snn-remove-post" aria-label="<?php esc_attr_e('Remove', 'snn-role-manager'); ?>">&times;</button>
                                     </span>
                                 <?php endforeach; ?>
                             <?php endif; ?>
                        </div>
                        <input type="hidden" name="snn_allowed_page_ids_hidden" id="snn-allowed-page-ids-hidden-edit" value="<?php echo esc_attr($current_restrictions_str); ?>">
                        <p class="description"><?php _e('Search for and select specific Posts, Pages, or other public post types that users with this role should be allowed to edit. Leave empty to allow editing of any post (requires the relevant "edit" capability above).', 'snn-role-manager'); ?></p>
                    </div>
                </td>
            </tr>
        </tbody></table>
        <?php submit_button(__('Update Role', 'snn-role-manager')); ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=' . $page_slug . '&tab=manage_roles')); ?>" class="button button-secondary"><?php _e('Cancel', 'snn-role-manager'); ?></a>
    </form>
    <?php
}


function snn_ajax_search_posts() {
    check_ajax_referer('snn_search_posts_nonce', 'nonce');

    if (!current_user_can('manage_snn_roles')) {
        wp_send_json_error(['message' => __('Permission denied.', 'snn-role-manager')], 403);
    }

    $search_term = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
    $selected_ids = isset($_POST['selected_ids']) && is_array($_POST['selected_ids']) ? array_map('absint', $_POST['selected_ids']) : [];

    if (empty($search_term)) {
        wp_send_json_success([]);
    }

    $post_types = get_post_types(['public' => true], 'names');
    if (isset($post_types['attachment'])) {
        unset($post_types['attachment']);
    }

    $args = [
        'post_type' => array_values($post_types),
        'post_status' => 'publish',
        'posts_per_page' => 20,
        's' => $search_term,
        'orderby' => 'title',
        'order' => 'ASC',
        'post__not_in' => $selected_ids
    ];

    $query = new WP_Query($args);
    $results = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_type_obj = get_post_type_object(get_post_type());
            $results[] = [
                'id' => get_the_ID(),
                'title' => get_the_title(),
                'type' => $post_type_obj ? esc_html($post_type_obj->labels->singular_name) : get_post_type(),
            ];
        }
        wp_reset_postdata();
    }

    wp_send_json_success($results);
}
add_action('wp_ajax_snn_search_posts', 'snn_ajax_search_posts');


function snn_is_role_manager_page() {
    return isset($_GET['page']) && $_GET['page'] === 'snn-role-management';
}


function snn_role_manager_inline_admin_css() {
    if (!snn_is_role_manager_page()) {
        return;
    }
    ?>
    <style type="text/css">
        .snn-role-manager .form-table th { width: 200px; padding-right: 20px; vertical-align: top; }
        .snn-role-manager .form-table td .description { margin-top: 4px; }
        .snn-role-manager .notice-warning p { margin: 0.5em 0; }
        .snn-role-manager .snn-role-manager-content { margin-top: 20px; }
        .snn-role-manager .nav-tab-wrapper .nav-tab-active { background-color: #f0f0f1; border-bottom-color: #f0f0f1;}
        .snn-role-manager .nav-tab-wrapper span.nav-tab-active {
            display: inline-block; padding: 9px 15px; margin: 0 2px -1px 0; font-size: 14px;
            line-height: 1.4; font-weight: 600; background: #f0f0f1; border: 1px solid #ccc;
            border-bottom: 1px solid #f0f0f1; border-radius: 3px 3px 0 0; color: #3c434a;
        }
        .snn-role-manager .wp-list-table td details summary { cursor: pointer; color: #2271b1; }
        .snn-role-manager .wp-list-table td details summary .details-hint { color: #777; font-size: smaller; }
        .snn-role-manager .wp-list-table td details .capabilities-list {
            max-height: 150px; overflow-y: auto; padding: 8px; border: 1px solid #ddd;
            background: #fdfdfd; margin-top: 5px; font-size: smaller; line-height: 1.6;
        }
        .snn-role-manager .wp-list-table td details .capabilities-list code {
             background-color: #f0f0f1; padding: 1px 4px; border-radius: 3px; font-size: 0.95em;
             white-space: normal; display: inline-block; margin-right: 4px; margin-bottom: 2px;
        }
        .snn-role-manager .wp-list-table span.na { color: #999; font-style: italic; }
        .snn-role-manager .wp-list-table td.column-actions { white-space: nowrap; }
        .snn-role-manager .wp-list-table td.column-actions .button-small { margin-right: 5px; vertical-align: middle;}
        .snn-role-manager .wp-list-table td.column-actions .delete-role-form { display: inline-block; margin: 0; padding: 0; vertical-align: middle; }
        .snn-role-manager .wp-list-table td.column-actions .button-link-delete {
             color: #b32d2e; vertical-align: middle; padding: 0 2px; text-decoration: underline; border: none; background: none; cursor: pointer;
        }
        .snn-role-manager .wp-list-table td.column-actions .button-link-delete:hover { color: #dc3232; }
        .snn-role-manager .wp-list-table td.column-actions button[disabled] {
             color: #a7aaad; cursor: not-allowed; text-decoration: none; opacity: 0.7;
        }
        .snn-role-manager .snn-capability-search-wrap { margin-bottom: 10px; }
        .snn-role-manager .snn-capability-search { max-width: 350px; }
        .snn-role-manager .capabilities-checkbox-list {
            max-height: 450px; overflow-y: scroll; border: 1px solid #ccc;
            padding: 15px; background: #fff; margin-bottom: 10px;
        }
        .snn-role-manager .capabilities-columns {
            list-style: none; margin: 0; padding: 0;
            -webkit-column-count: 3; -moz-column-count: 3; column-count: 3;
            column-gap: 20px;
        }
        .snn-role-manager .capability-item {
            margin-bottom: 8px;
            break-inside: avoid-column;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .snn-role-manager .capability-item label { display: inline-block; max-width: 100%; cursor: pointer; }
        .snn-role-manager .capability-item input[type="checkbox"] { margin-right: 5px; vertical-align: middle;}
        .snn-role-manager .capability-item code {
            background-color: #f0f0f1; padding: 1px 4px; border-radius: 3px; font-size: 0.9em; vertical-align: middle;
        }
        .snn-role-manager .capability-item .required-cap {
            color:#999; font-size:smaller; vertical-align: middle; font-style: italic;
        }
        .snn-role-manager input[type="checkbox"]:disabled + code,
        .snn-role-manager input[type="checkbox"]:disabled + code + .required-cap { opacity: 0.7; }
        .snn-post-restriction-control { position: relative; }
        .snn-post-search-input { margin-bottom: 5px; }
        .snn-search-results {
            position: absolute;
            background-color: white;
            border: 1px solid #ccc;
            box-shadow: 0 2px 5px rgba(0,0,0,0.15);
            z-index: 100;
            max-height: 200px;
            overflow-y: auto;
            width: calc(100% - 2px);
            margin-top: -1px;
        }
        .snn-search-results ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .snn-search-results li {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }
        .snn-search-results li:last-child { border-bottom: none; }
        .snn-search-results li:hover { background-color: #f0f0f1; }
        .snn-search-results li .post-type {
            font-size: 0.9em;
            color: #777;
            margin-left: 8px;
        }
        .snn-search-results .no-results {
            padding: 8px 12px;
            color: #777;
            font-style: italic;
        }
        .snn-selected-posts {
            border: 1px solid #ddd;
            padding: 10px;
            margin-top: 10px;
            min-height: 40px;
            background-color: #fdfdfd;
        }
        .snn-selected-posts .placeholder {
            color: #999;
            font-style: italic;
        }
        .snn-selected-post-item {
            display: inline-block;
            background-color: #e0e0e0;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 4px 8px;
            margin-right: 5px;
            margin-bottom: 5px;
            font-size: 0.9em;
            line-height: 1.4;
        }
        .snn-remove-post {
            background: none;
            border: none;
            color: #b32d2e;
            cursor: pointer;
            font-size: 1.2em;
            margin-left: 5px;
            padding: 0;
            line-height: 1;
            vertical-align: middle;
        }
        .snn-remove-post:hover { color: #dc3232; }
        @media screen and (max-width: 1100px) {
            .snn-role-manager .capabilities-columns { column-count: 2; }
        }
        @media screen and (max-width: 782px) {
            .snn-role-manager .capabilities-columns { column-count: 1; }
            .snn-role-manager .capability-item { white-space: normal; }
            .snn-role-manager .form-table th { width: auto; }
            .snn-role-manager .wp-list-table .column-role_id,
            .snn-role-manager .wp-list-table .column-capabilities,
            .snn-role-manager .wp-list-table .column-restrictions { display: none; }
            .snn-role-manager .wp-list-table tbody th,
            .snn-role-manager .wp-list-table tbody td { display: block; width: auto; float: none; text-align: left; }
            .snn-role-manager .wp-list-table tbody td:before {
                content: attr(data-colname) ': '; font-weight: bold; display: inline-block; margin-right: 5px;
            }
             .snn-role-manager .wp-list-table td.column-actions { text-align: left; padding-left: 0; white-space: normal; }
             .snn-role-manager .wp-list-table td.column-actions .button-small,
             .snn-role-manager .wp-list-table td.column-actions .delete-role-form { display: inline-block; margin-bottom: 5px; margin-right: 10px; }
        }
    </style>
    <?php
}
add_action('admin_head', 'snn_role_manager_inline_admin_css');


function snn_role_manager_inline_admin_js() {
    if (!snn_is_role_manager_page()) {
        return;
    }

    $js_data = [
        'ajax_url' => admin_url('admin-ajax.php'),
        'search_nonce' => wp_create_nonce('snn_search_posts_nonce'),
        'labels' => [
            'no_results' => __('No posts found.', 'snn-role-manager'),
            'error' => __('An error occurred. Please try again.', 'snn-role-manager'),
            'remove' => __('Remove', 'snn-role-manager'),
            'no_posts_selected' => __('No posts selected.', 'snn-role-manager'),
            'loading' => __('Loading...', 'snn-role-manager'),
        ]
    ];
    ?>
    <script type="text/javascript">
        const snnRoleManagerData = <?php echo wp_json_encode($js_data); ?>;

        jQuery(document).ready(function($) {

            function filterCapabilities(searchInput, listElement) {
                if (!searchInput.length || !listElement.length) return;

                const searchTerm = searchInput.val().toLowerCase().trim();
                const capabilities = listElement.find('li.capability-item');

                capabilities.each(function() {
                    const item = $(this);
                    const codeElement = item.find('code');
                    if (codeElement.length) {
                        const capabilityText = codeElement.text().toLowerCase();
                        if (capabilityText.includes(searchTerm)) {
                            item.show();
                        } else {
                            item.hide();
                        }
                    }
                });
            }

            $('.snn-capability-search').on('input', function() {
                const searchInput = $(this);
                const listId = searchInput.attr('aria-controls');
                const listElement = $('#' + listId);
                filterCapabilities(searchInput, listElement);
            });

            let searchTimeout;
            const searchDelay = 300;

            function updateHiddenInput(container) {
                const selectedItems = container.find('.snn-selected-post-item');
                const ids = selectedItems.map(function() {
                    return $(this).data('id');
                }).get();
                container.siblings('input[type="hidden"]').val(ids.join(','));

                const placeholder = container.find('.placeholder');
                if (ids.length > 0) {
                    placeholder.hide();
                } else {
                    placeholder.show();
                }
            }

            function addSelectedPost(post, selectedContainer) {
                if (selectedContainer.find('.snn-selected-post-item[data-id="' + post.id + '"]').length > 0) {
                    return;
                }

                const pill = $('<span class="snn-selected-post-item" data-id="' + post.id + '"></span>');
                pill.text(post.title + ' (' + post.type + ') ');
                const removeButton = $('<button type="button" class="snn-remove-post" aria-label="' + snnRoleManagerData.labels.remove + '">&times;</button>');
                pill.append(removeButton);

                selectedContainer.find('.placeholder').hide();
                selectedContainer.append(pill);
                updateHiddenInput(selectedContainer);
            }

            $('.snn-post-search-input').on('input', function() {
                clearTimeout(searchTimeout);
                const input = $(this);
                const resultsContainer = input.siblings('.snn-search-results');
                const selectedContainer = input.siblings('.snn-selected-posts');
                const searchTerm = input.val().trim();

                if (searchTerm.length < 2) {
                    resultsContainer.hide().empty();
                    return;
                }

                resultsContainer.show().html('<ul><li>' + snnRoleManagerData.labels.loading + '</li></ul>');

                searchTimeout = setTimeout(function() {
                    const selectedIds = selectedContainer.find('.snn-selected-post-item').map(function() {
                        return $(this).data('id');
                    }).get();

                    $.ajax({
                        url: snnRoleManagerData.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'snn_search_posts',
                            nonce: snnRoleManagerData.search_nonce,
                            search: searchTerm,
                            selected_ids: selectedIds
                        },
                        dataType: 'json',
                        success: function(response) {
                            resultsContainer.empty();
                            const ul = $('<ul></ul>');
                            if (response.success && response.data && response.data.length > 0) {
                                response.data.forEach(function(post) {
                                    const li = $('<li></li>');
                                    li.attr('data-id', post.id);
                                    li.attr('data-title', post.title);
                                    li.attr('data-type', post.type);
                                    li.text(post.title);
                                    li.append(' <span class="post-type">(' + post.type + ')</span>');
                                    ul.append(li);
                                });
                            } else {
                                ul.append('<li class="no-results">' + snnRoleManagerData.labels.no_results + '</li>');
                            }
                            resultsContainer.append(ul);
                        },
                        error: function() {
                             resultsContainer.html('<ul><li class="no-results">' + snnRoleManagerData.labels.error + '</li></ul>');
                        }
                    });
                }, searchDelay);
            });

            $('body').on('click', '.snn-search-results li:not(.no-results)', function() {
                const li = $(this);
                const post = {
                    id: li.data('id'),
                    title: li.data('title'),
                    type: li.data('type')
                };
                const controlWrapper = li.closest('.snn-post-restriction-control');
                const selectedContainer = controlWrapper.find('.snn-selected-posts');
                const searchInput = controlWrapper.find('.snn-post-search-input');
                const resultsContainer = controlWrapper.find('.snn-search-results');

                addSelectedPost(post, selectedContainer);
                searchInput.val('');
                resultsContainer.hide().empty();
            });

            $('body').on('click', '.snn-remove-post', function() {
                const button = $(this);
                const pill = button.closest('.snn-selected-post-item');
                const selectedContainer = pill.closest('.snn-selected-posts');
                pill.remove();
                updateHiddenInput(selectedContainer);
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('.snn-post-restriction-control').length) {
                    $('.snn-search-results').hide().empty();
                }
            });

             const table = $('.snn-role-manager table.roles');
             if (table.length) {
                  table.on('click', '.toggle-row', function(e) {
                       const button = $(this);
                       const row = button.closest('tr');
                       if (row.length) {
                            row.toggleClass('is-expanded');
                       }
                  });
             }

        });
    </script>
    <?php
}
add_action('admin_footer', 'snn_role_manager_inline_admin_js');

?>
