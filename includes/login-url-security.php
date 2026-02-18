<?php
if (!defined('ABSPATH')) {
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// LOGIN URL SECURITY
// Hides /wp-login.php behind a custom slug. Blocks direct access to wp-login.php
// and serves the login form at the custom slug instead.
//
// EMERGENCY RECOVERY: If you get locked out, add this line to wp-config.php:
//   define('SNN_DISABLE_CUSTOM_LOGIN', true);
// This bypasses the feature entirely and restores normal wp-login.php access.
// ─────────────────────────────────────────────────────────────────────────────

if (defined('SNN_DISABLE_CUSTOM_LOGIN') && SNN_DISABLE_CUSTOM_LOGIN) {
    return;
}

// ─────────────────────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────────────────────

function snn_custom_login_is_enabled() {
    $options = get_option('snn_security_options', array());
    return !empty($options['enable_custom_login_url']);
}

function snn_custom_login_get_slug() {
    $options = get_option('snn_security_options', array());
    $slug    = isset($options['custom_login_slug']) ? $options['custom_login_slug'] : 'snn';
    $slug    = strtolower(preg_replace('/[^a-z0-9\-]/', '', $slug));
    return !empty($slug) ? $slug : 'snn';
}

function snn_custom_login_url($scheme = null) {
    return trailingslashit(home_url('/' . snn_custom_login_get_slug(), $scheme));
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. BLOCK direct /wp-login.php access
//    The init hook fires during wp-login.php execution (it boots via wp-load.php
//    which triggers the full WordPress init sequence including this hook).
//    We redirect before wp-login.php outputs anything.
// ─────────────────────────────────────────────────────────────────────────────

add_action('init', 'snn_block_direct_wplogin', 1);

function snn_block_direct_wplogin() {
    if (!snn_custom_login_is_enabled()) {
        return;
    }

    global $pagenow;
    if ('wp-login.php' !== $pagenow) {
        return;
    }

    // Never block AJAX, Cron, or WP-CLI contexts
    if (
        (defined('DOING_AJAX') && DOING_AJAX) ||
        (defined('DOING_CRON') && DOING_CRON) ||
        (defined('WP_CLI')    && WP_CLI)
    ) {
        return;
    }

    // Redirect silently to homepage — no hint a login page exists
    wp_safe_redirect(home_url('/'));
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// 2. SERVE the login form at the custom slug
//    template_redirect fires during normal WordPress routing before any template
//    is rendered, giving us a clean interception point for the custom slug URL.
// ─────────────────────────────────────────────────────────────────────────────

add_action('template_redirect', 'snn_serve_custom_login_slug', 1);

function snn_serve_custom_login_slug() {
    if (!snn_custom_login_is_enabled()) {
        return;
    }

    $slug         = snn_custom_login_get_slug();
    $request_uri  = isset($_SERVER['REQUEST_URI']) ? rawurldecode($_SERVER['REQUEST_URI']) : '';
    $request_path = untrailingslashit(parse_url($request_uri, PHP_URL_PATH) ?: '/');
    $parsed_home  = wp_parse_url(home_url());
    $home_path    = isset($parsed_home['path']) ? untrailingslashit($parsed_home['path']) : '';

    $is_custom_login = false;

    if (get_option('permalink_structure')) {
        // Pretty permalinks: compare by path
        $custom_path = untrailingslashit($home_path . '/' . $slug);
        if ($request_path === $custom_path) {
            $is_custom_login = true;
        }
    } else {
        // Plain permalinks: detect as a query variable (?my-slug with empty value)
        if (isset($_GET[$slug]) && '' === $_GET[$slug]) {
            $is_custom_login = true;
        }
    }

    if (!$is_custom_login) {
        return;
    }

    // Crucial: prevent browsers/proxies from caching the login page
    nocache_headers();

    // Set SCRIPT_NAME so wp-login.php generates correct form action URLs
    $_SERVER['SCRIPT_NAME'] = '/' . $slug;

    // Load WordPress's actual login page — all actions (login, logout,
    // lostpassword, rp, resetpass, register, postpass) work automatically
    require_once ABSPATH . 'wp-login.php';
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// 3. URL FILTERS
//    Rewrite every wp-login.php URL WordPress generates to the custom slug.
//    Covers: site_url(), login_url(), logout_url(), lostpassword_url(),
//    register_url(), and any wp_redirect() calls.
// ─────────────────────────────────────────────────────────────────────────────

function snn_rewrite_login_url($url, $scheme = null) {
    if (strpos($url, 'wp-login.php') === false) {
        return $url;
    }
    $parts      = explode('?', $url, 2);
    $query_args = array();
    if (!empty($parts[1])) {
        parse_str($parts[1], $query_args);
    }
    $base = snn_custom_login_url($scheme);
    return !empty($query_args)
        ? add_query_arg($query_args, untrailingslashit($base))
        : $base;
}

add_filter('site_url', function ($url, $path, $scheme, $blog_id) {
    return snn_custom_login_is_enabled() ? snn_rewrite_login_url($url, $scheme) : $url;
}, 10, 4);

add_filter('network_site_url', function ($url, $path, $scheme) {
    return snn_custom_login_is_enabled() ? snn_rewrite_login_url($url, $scheme) : $url;
}, 10, 3);

add_filter('login_url', function ($url, $redirect, $force_reauth) {
    if (!snn_custom_login_is_enabled()) return $url;
    $new = snn_custom_login_url();
    if ($redirect)     $new = add_query_arg('redirect_to', urlencode($redirect), $new);
    if ($force_reauth) $new = add_query_arg('reauth', '1', $new);
    return $new;
}, 10, 3);

add_filter('logout_url', function ($url, $redirect) {
    return snn_custom_login_is_enabled() ? snn_rewrite_login_url($url) : $url;
}, 10, 2);

add_filter('lostpassword_url', function ($url, $redirect) {
    return snn_custom_login_is_enabled() ? snn_rewrite_login_url($url) : $url;
}, 10, 2);

add_filter('register_url', function ($url) {
    return snn_custom_login_is_enabled() ? snn_rewrite_login_url($url) : $url;
}, 10, 1);

// Catch any remaining wp-login.php redirects (e.g. ?checkemail=confirm after lost password)
add_filter('wp_redirect', function ($location, $status) {
    if (!snn_custom_login_is_enabled()) return $location;
    return strpos($location, 'wp-login.php') !== false
        ? snn_rewrite_login_url($location)
        : $location;
}, 10, 2);

// ─────────────────────────────────────────────────────────────────────────────
// 4. FIX PASSWORD RESET EMAIL LINKS
//    WordPress generates the reset link using site_url('wp-login.php?...')
//    inside retrieve_password(). The site_url filter above handles most cases,
//    but we also filter the message body directly as a safety net.
// ─────────────────────────────────────────────────────────────────────────────

add_filter('retrieve_password_message', function ($message, $key, $user_login, $user_data) {
    if (!snn_custom_login_is_enabled()) return $message;

    $reset_url = add_query_arg(
        array('action' => 'rp', 'key' => $key, 'login' => rawurlencode($user_login)),
        snn_custom_login_url()
    );

    // Replace any remaining wp-login.php reset URL in the email body
    $message = preg_replace(
        '#https?://[^\s]+/wp-login\.php\?[^\s\r\n]+#',
        $reset_url,
        $message
    );

    return $message;
}, 10, 4);

// ─────────────────────────────────────────────────────────────────────────────
// 5. ADMIN NOTICE: Custom login active but XML-RPC still enabled
//    XML-RPC can expose login credentials, bypassing the custom URL protection.
// ─────────────────────────────────────────────────────────────────────────────

add_action('admin_notices', function () {
    if (!snn_custom_login_is_enabled())      return;
    if (!current_user_can('manage_options')) return;

    $options = get_option('snn_security_options', array());
    if (!empty($options['disable_xmlrpc']))  return; // already disabled, no warning needed

    $url = admin_url('admin.php?page=snn-security');
    ?>
    <div class="notice notice-warning is-dismissible">
        <p>
            <strong><?php esc_html_e('Security Warning:', 'snn'); ?></strong>
            <?php esc_html_e('Custom Login URL is active but XML-RPC is still enabled. XML-RPC can expose login credentials and bypass your custom login URL protection. We strongly recommend disabling XML-RPC.', 'snn'); ?>
            <a href="<?php echo esc_url($url); ?>" style="margin-left: 6px;"><?php esc_html_e('Go to Security Settings →', 'snn'); ?></a>
        </p>
    </div>
    <?php
});

// ─────────────────────────────────────────────────────────────────────────────
// 6. SETTINGS FIELD
//    Registered via admin_init — adds the checkbox + slug input to the
//    Security Settings admin page.
// ─────────────────────────────────────────────────────────────────────────────

add_action('admin_init', function () {
    add_settings_field(
        'custom_login_url',
        __('Custom Login URL', 'snn'),
        'snn_custom_login_url_field_cb',
        'snn-security',
        'snn_security_main_section'
    );
});

function snn_custom_login_url_field_cb() {
    $options  = get_option('snn_security_options', array());
    $enabled  = !empty($options['enable_custom_login_url']);
    $slug     = isset($options['custom_login_slug']) ? $options['custom_login_slug'] : 'snn';
    $slug     = strtolower(preg_replace('/[^a-z0-9\-]/', '', $slug));
    if (empty($slug)) $slug = 'snn';

    $home_url = untrailingslashit(home_url());
    ?>
    <input type="checkbox"
           id="snn_enable_custom_login_url"
           name="snn_security_options[enable_custom_login_url]"
           value="1"
           <?php checked($enabled); ?>>
    <p><?php esc_html_e('Enable to serve the WordPress login page at a custom URL and block direct /wp-login.php access.', 'snn'); ?></p>

    <div id="snn_custom_login_slug_wrap" style="padding-left: 40px; margin-top: 8px;">
        <label>
            <strong><?php esc_html_e('Login Slug:', 'snn'); ?></strong><br>
            <div style="display: flex; align-items: center; margin-top: 4px;">
                <span style="font-size: 13px; color: #555; background: #f0f0f1; padding: 0 8px; border: 1px solid #8c8f94; border-right: none; line-height: 30px; border-radius: 3px 0 0 3px; white-space: nowrap;"><?php echo esc_html($home_url); ?>/</span>
                <input type="text"
                       id="snn_custom_login_slug"
                       name="snn_security_options[custom_login_slug]"
                       value="<?php echo esc_attr($slug); ?>"
                       style="width: 140px !important; margin: 0 !important; border-radius: 0 3px 3px 0 !important;"
                       placeholder="snn"
                       autocomplete="off"
                       spellcheck="false">
            </div>
        </label>
        <p style="color: #666; margin-top: 6px;"><?php esc_html_e('Allowed: lowercase letters (a-z), numbers (0-9), dashes (-). No spaces or special characters. Default: snn', 'snn'); ?></p>

        <?php if ($enabled) : ?>
        <div style="margin-top: 10px; padding: 10px 14px; background: #fff8e1; border-left: 4px solid #ffc107; border-radius: 2px;">
            <strong><?php esc_html_e('Your current login URL:', 'snn'); ?></strong>
            <a href="<?php echo esc_url($home_url . '/' . $slug); ?>" target="_blank" rel="noopener"><?php echo esc_html($home_url . '/' . $slug); ?></a>
            <br><br>
            <strong><?php esc_html_e('Emergency recovery:', 'snn'); ?></strong>
            <?php esc_html_e('If you get locked out, add this line to your wp-config.php to restore normal login access:', 'snn'); ?>
            <br>
            <code style="background: #f5f5f5; padding: 3px 8px; display: inline-block; margin-top: 6px; user-select: all; font-size: 12px;">define('SNN_DISABLE_CUSTOM_LOGIN', true);</code>
        </div>
        <?php endif; ?>
    </div>

    <script>
    (function () {
        var cb    = document.getElementById('snn_enable_custom_login_url');
        var wrap  = document.getElementById('snn_custom_login_slug_wrap');
        var input = document.getElementById('snn_custom_login_slug');

        function toggle() {
            if (wrap) wrap.style.display = cb.checked ? 'block' : 'none';
        }
        toggle();
        if (cb) cb.addEventListener('change', toggle);

        // Strip invalid characters in real-time
        if (input) {
            input.addEventListener('input', function () {
                this.value = this.value.toLowerCase().replace(/[^a-z0-9\-]/g, '');
            });
        }

        // Prevent saving an empty slug — fall back to 'snn'
        var form = cb ? cb.closest('form') : null;
        if (form) {
            form.addEventListener('submit', function () {
                if (cb.checked && input && input.value.trim() === '') {
                    input.value = 'snn';
                }
            });
        }
    })();
    </script>
    <?php
}
