<?php

if (!defined('ABSPATH')) {
    exit;
}

function snn_add_media_submenu() {
    add_submenu_page(
        'snn-settings',
        __('Media Settings', 'snn'),
        __('Media Settings', 'snn'),
        'manage_options',
        'snn-media-settings',
        'snn_render_media_settings'
    );
}
add_action('admin_menu', 'snn_add_media_submenu');

function snn_render_media_settings() {
    $options = get_option('snn_media_settings');
    ?>
    <div class="wrap">
        <h1><?php _e('Media Settings', 'snn'); ?></h1>
        <form method="post" action="options.php">
            <?php
                settings_fields('snn_media_settings_group');
                do_settings_sections('snn-media-settings');
                submit_button();
            ?>
        </form>
    </div>
    <?php
}

function snn_register_media_settings() {
    register_setting(
        'snn_media_settings_group',
        'snn_media_settings',
        'snn_sanitize_media_settings'
    );

    add_settings_section(
        'snn_media_settings_section',
        __('Media Settings', 'snn'),
        'snn_media_settings_section_callback',
        'snn-media-settings'
    );

    add_settings_field(
        'redirect_media_library',
        __('Redirect Media Library Grid View to List View', 'snn'),
        'snn_redirect_media_library_callback',
        'snn-media-settings',
        'snn_media_settings_section'
    );

    add_settings_field(
        'media_categories',
        __('Enable Media Categories', 'snn'),
        'snn_media_categories_callback',
        'snn-media-settings',
        'snn_media_settings_section'
    );

    add_settings_field(
        'force_optimized_uploads',
        __('Force Optimized Uploads', 'snn'),
        'snn_force_optimized_uploads_callback',
        'snn-media-settings',
        'snn_media_settings_section'
    );

    add_settings_field(
        'extra_formats',
        __('Extra Upload Formats', 'snn'),
        'snn_extra_formats_callback',
        'snn-media-settings',
        'snn_media_settings_section'
    );

    add_settings_field(
        'custom_formats',
        __('Custom Upload Formats', 'snn'),
        'snn_custom_formats_callback',
        'snn-media-settings',
        'snn_media_settings_section'
    );
}
add_action('admin_init', 'snn_register_media_settings');

function snn_sanitize_media_settings($input) {
    $sanitized = array();
    $sanitized['redirect_media_library'] = isset($input['redirect_media_library']) && $input['redirect_media_library'] ? 1 : 0;
    $sanitized['media_categories'] = isset($input['media_categories']) && $input['media_categories'] ? 1 : 0;
    $sanitized['force_optimized_uploads'] = isset($input['force_optimized_uploads']) && $input['force_optimized_uploads'] ? 1 : 0;

    $known = snn_media_extra_format_choices();
    $sanitized['extra_formats'] = array();
    if (isset($input['extra_formats']) && is_array($input['extra_formats'])) {
        foreach ($input['extra_formats'] as $key) {
            if (isset($known[$key])) {
                $sanitized['extra_formats'][] = $key;
            }
        }
    }

    // Keep only well-formed "ext|mime" lines; blocked server-executable extensions are dropped.
    $lines = array();
    $raw   = isset($input['custom_formats']) ? (string) $input['custom_formats'] : '';
    foreach (snn_media_parse_custom_formats($raw) as $ext => $mime) {
        $lines[] = $ext . '|' . $mime;
    }
    $sanitized['custom_formats'] = implode("\n", $lines);

    return $sanitized;
}

function snn_media_settings_section_callback() {
    echo '<p>' . __('Configure media-related settings below.', 'snn') . '</p>';
}

function snn_redirect_media_library_callback() {
    $options = get_option('snn_media_settings');
    ?>
    <input type="checkbox" name="snn_media_settings[redirect_media_library]" value="1" <?php checked(1, isset($options['redirect_media_library']) ? $options['redirect_media_library'] : 0); ?>>
    <p><?php _e('Media list view default.', 'snn'); ?></p>
    <?php
}

function snn_media_categories_callback() {
    $options = get_option('snn_media_settings');
    ?>
    <input type="checkbox" name="snn_media_settings[media_categories]" value="1" <?php checked(1, isset($options['media_categories']) ? $options['media_categories'] : 0); ?> >
    <p><?php _e('Enable Media Categories with drag-and-drop functionality. (right click on grid view)', 'snn'); ?></p>
    <?php
}

function snn_force_optimized_uploads_callback() {
    $options = get_option('snn_media_settings');
    ?>
    <input type="checkbox" name="snn_media_settings[force_optimized_uploads]" value="1" <?php checked(1, isset($options['force_optimized_uploads']) ? $options['force_optimized_uploads'] : 0); ?>>
    <p><?php _e('Hide the native WordPress upload screens (Media > Add Media File, the "Upload files" tab in media modals, the "Add Media File" buttons) so every upload goes through "Optimize & Upload" and images always get optimized.', 'snn'); ?></p>
    <?php
}

function snn_extra_formats_callback() {
    $options  = get_option('snn_media_settings');
    $selected = isset($options['extra_formats']) && is_array($options['extra_formats']) ? $options['extra_formats'] : array();
    foreach (snn_media_extra_format_choices() as $key => $choice) {
        ?>
        <label style="display:block;margin-bottom:6px;">
            <input type="checkbox" name="snn_media_settings[extra_formats][]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, $selected, true)); ?>>
            <strong><?php echo esc_html($choice['label']); ?></strong>
            <?php if (!empty($choice['warning'])) : ?>
                <span style="color:#b32d2e;"> — <?php echo esc_html($choice['warning']); ?></span>
            <?php endif; ?>
        </label>
        <?php
    }
    ?>
    <p><?php _e('Formats WordPress blocks by default. Only administrators can upload them. Everything WordPress already allows (images, PDF, MP4, MP3, Office files, ZIP...) works without any setting.', 'snn'); ?></p>
    <?php
}

function snn_custom_formats_callback() {
    $options = get_option('snn_media_settings');
    $value   = isset($options['custom_formats']) ? $options['custom_formats'] : '';
    ?>
    <textarea name="snn_media_settings[custom_formats]" rows="4" cols="50" class="code" placeholder="stl|model/stl&#10;dwg|image/vnd.dwg"><?php echo esc_textarea($value); ?></textarea>
    <p><?php _e('One format per line as extension|mime-type. Only administrators can upload them. A file type allowed here can be abused if the file is harmful, so add only what you need. Server-executable types (php, phtml, phar, cgi, asp, jsp, sh...) are always blocked.', 'snn'); ?></p>
    <?php
}

/**
 * Upload formats WordPress blocks by default that can be switched on in Media Settings.
 */
function snn_media_extra_format_choices() {
    return array(
        'svg'    => array(
            'label'   => 'SVG (.svg)',
            'mimes'   => array('svg' => 'image/svg+xml'),
            'warning' => __('can carry scripts, uploads are sanitized', 'snn'),
        ),
        'json'   => array(
            'label' => 'JSON (.json)',
            'mimes' => array('json' => 'application/json'),
        ),
        'fonts'  => array(
            'label' => 'Fonts (.woff, .woff2, .ttf, .otf)',
            'mimes' => array(
                'woff'  => 'font/woff',
                'woff2' => 'font/woff2',
                'ttf'   => 'font/ttf',
                'otf'   => 'font/otf',
            ),
        ),
        'lottie' => array(
            'label' => 'Lottie (.lottie)',
            'mimes' => array('lottie' => 'application/zip'),
        ),
        '3d'     => array(
            'label' => '3D models (.glb, .gltf)',
            'mimes' => array(
                'glb'  => 'model/gltf-binary',
                'gltf' => 'model/gltf+json',
            ),
        ),
        'xml'    => array(
            'label' => 'XML (.xml)',
            'mimes' => array('xml' => 'application/xml'),
        ),
        'eps'    => array(
            'label' => 'EPS / AI (.eps, .ai)',
            'mimes' => array(
                'eps' => 'application/postscript',
                'ai'  => 'application/postscript',
            ),
        ),
        'html'   => array(
            'label'   => 'HTML (.html, .htm)',
            'mimes'   => array(
                'html' => 'text/html',
                'htm'  => 'text/html',
            ),
            'warning' => __('dangerous, an uploaded page can run scripts on your domain', 'snn'),
        ),
        'js'     => array(
            'label'   => 'JavaScript (.js)',
            'mimes'   => array('js' => 'application/javascript'),
            'warning' => __('dangerous, scripts can be served from your domain', 'snn'),
        ),
    );
}

/**
 * Extensions that could run code on the server; never allowed, not even as custom formats.
 */
function snn_media_blocked_extensions() {
    return array(
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'pht', 'phps', 'phar',
        'cgi', 'pl', 'py', 'asp', 'aspx', 'jsp', 'sh', 'shtml', 'htaccess', 'htpasswd', 'ini',
    );
}

/**
 * Parse "ext|mime" lines into array( ext => mime ).
 */
function snn_media_parse_custom_formats($raw) {
    $mimes = array();
    foreach (preg_split('/\r\n|\r|\n/', (string) $raw) as $line) {
        $parts = array_map('trim', explode('|', $line));
        if (count($parts) !== 2) {
            continue;
        }
        $ext  = strtolower(ltrim($parts[0], '.'));
        $mime = strtolower($parts[1]);
        if (!preg_match('/^[a-z0-9]{1,10}$/', $ext) || !preg_match('#^[a-z0-9.+-]+/[a-z0-9.+-]+$#', $mime)) {
            continue;
        }
        if (in_array($ext, snn_media_blocked_extensions(), true)) {
            continue;
        }
        $mimes[$ext] = $mime;
    }
    return $mimes;
}

/**
 * All extra ext => mime pairs switched on in Media Settings.
 */
function snn_media_extra_mimes() {
    $options = get_option('snn_media_settings');
    $mimes   = array();
    if (!empty($options['extra_formats']) && is_array($options['extra_formats'])) {
        $choices = snn_media_extra_format_choices();
        foreach ($options['extra_formats'] as $key) {
            if (isset($choices[$key])) {
                $mimes = array_merge($mimes, $choices[$key]['mimes']);
            }
        }
    }
    if (!empty($options['custom_formats'])) {
        $mimes = array_merge($mimes, snn_media_parse_custom_formats($options['custom_formats']));
    }
    return $mimes;
}

function snn_media_allow_extra_mimes($mimes) {
    if (!current_user_can('manage_options')) {
        return $mimes;
    }
    foreach (snn_media_extra_mimes() as $ext => $mime) {
        $mimes[$ext] = $mime;
    }
    return $mimes;
}
add_filter('upload_mimes', 'snn_media_allow_extra_mimes', 20);

/**
 * WordPress sniffs the real file type and rejects many text, font and model files
 * (finfo reports text/plain, application/octet-stream...). Trust the extension
 * for the formats switched on here.
 */
function snn_media_fix_extra_filetype($data, $file, $filename, $mimes) {
    if (!empty($data['ext']) && !empty($data['type'])) {
        return $data;
    }
    if (!current_user_can('manage_options')) {
        return $data;
    }
    $ext   = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $extra = snn_media_extra_mimes();
    if ($ext !== '' && isset($extra[$ext])) {
        $data['ext']  = $ext;
        $data['type'] = $extra[$ext];
    }
    return $data;
}
add_filter('wp_check_filetype_and_ext', 'snn_media_fix_extra_filetype', 20, 4);

/**
 * Sanitize SVG uploads with the svg-sanitizer library that ships with Bricks.
 * If the library is missing, the SVG is rejected instead of stored unsanitized.
 */
function snn_media_sanitize_svg_upload($file) {
    $ext = strtolower(pathinfo(isset($file['name']) ? $file['name'] : '', PATHINFO_EXTENSION));
    if ($ext !== 'svg' || empty($file['tmp_name'])) {
        return $file;
    }
    $extra = snn_media_extra_mimes();
    if (!isset($extra['svg'])) {
        return $file;
    }

    if (!class_exists('\enshrined\svgSanitize\Sanitizer') && defined('BRICKS_PATH')) {
        $autoload = BRICKS_PATH . 'includes/integrations/svg-sanitizer/library/vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }
    }
    if (!class_exists('\enshrined\svgSanitize\Sanitizer')) {
        $file['error'] = __('SVG sanitizer is not available, so the SVG was not uploaded.', 'snn');
        return $file;
    }

    $content = file_get_contents($file['tmp_name']);
    $gzipped = is_string($content) && strpos($content, "\x1f\x8b\x08") === 0;
    if ($gzipped) {
        $content = gzdecode($content);
    }
    $clean = false;
    if (is_string($content)) {
        $sanitizer = new \enshrined\svgSanitize\Sanitizer();
        $clean     = $sanitizer->sanitize($content);
    }
    if (!is_string($clean) || $clean === '') {
        $file['error'] = __('This SVG could not be sanitized and was not uploaded.', 'snn');
        return $file;
    }
    file_put_contents($file['tmp_name'], $gzipped ? gzencode($clean) : $clean);
    return $file;
}
add_filter('wp_handle_upload_prefilter', 'snn_media_sanitize_svg_upload');

/**
 * Accept attribute for the "Optimize & Upload" pickers: every extension this user may upload.
 */
function snn_media_upload_accept() {
    $exts = array();
    foreach (array_keys(get_allowed_mime_types()) as $group) {
        foreach (explode('|', $group) as $ext) {
            $exts[] = '.' . $ext;
        }
    }
    return implode(',', array_unique($exts));
}

function snn_media_force_optimized_uploads_enabled() {
    $options = get_option('snn_media_settings');
    return !empty($options['force_optimized_uploads']);
}

/**
 * Force Optimized Uploads: hide the native upload entry points (Media > Add Media File,
 * "Add Media File" buttons, admin bar New > Media, the modal "Upload files" tab).
 * The modal also switches away from that tab in snn-media-modal-optimize.js.
 */
function snn_media_hide_native_upload_ui() {
    if (!snn_media_force_optimized_uploads_enabled() || !is_user_logged_in()) {
        return;
    }
    ?>
    <style id="snn-force-optimized-uploads">
        #menu-media li:has(> a[href="media-new.php"]),
        .wrap .page-title-action[href*="media-new.php"],
        #wp-admin-bar-new-media,
        .media-frame-router #menu-item-upload { display: none !important; }
    </style>
    <?php
}
add_action('admin_head', 'snn_media_hide_native_upload_ui');
add_action('wp_head', 'snn_media_hide_native_upload_ui');

function snn_redirect_media_library_grid_to_list() {
    // Only run on GET requests and skip AJAX calls
    if (defined('DOING_AJAX') && DOING_AJAX) {
        return;
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        return;
    }

    $options = get_option('snn_media_settings');
    if (
        isset($options['redirect_media_library']) &&
        $options['redirect_media_library'] &&
        is_admin() &&
        strpos($_SERVER['REQUEST_URI'], 'upload.php') !== false
    ) {
        $current_mode = isset($_GET['mode']) ? $_GET['mode'] : '';
        if ($current_mode !== 'list') {
            $list_mode_url = remove_query_arg('mode');
            $list_mode_url = add_query_arg('mode', 'list', $list_mode_url);
            wp_redirect($list_mode_url);
            exit;
        }
    }
}
add_action('admin_init', 'snn_redirect_media_library_grid_to_list');

function snn_register_media_taxonomy_categories() {
    $options = get_option('snn_media_settings');
    if (isset($options['media_categories']) && $options['media_categories']) {
        $labels = array(
            'name'              => _x('Media Categories', 'taxonomy general name', 'snn'),
            'singular_name'     => _x('Media Category', 'taxonomy singular name', 'snn'),
            'search_items'      => __('Search Media Categories', 'snn'),
            'all_items'         => __('All Media Categories', 'snn'),
            'parent_item'       => __('Parent Media Category', 'snn'),
            'parent_item_colon' => __('Parent Media Category:', 'snn'),
            'edit_item'         => __('Edit Media Category', 'snn'),
            'update_item'       => __('Update Media Category', 'snn'),
            'add_new_item'      => __('Add New Media Category', 'snn'),
            'new_item_name'     => __('New Media Category Name', 'snn'),
            'menu_name'         => __('Media Categories', 'snn'),
        );

        $args = array(
            'hierarchical'          => true,
            'labels'                => $labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'query_var'             => false,
            'rewrite'               => false,
            'public'                => false,
            'show_in_rest'          => false,
            'update_count_callback' => '_update_generic_term_count'
        );

        register_taxonomy('media_taxonomy_categories', 'attachment', $args);
    }
}
add_action('init', 'snn_register_media_taxonomy_categories');

function snn_add_custom_css_js_to_media_page() {
    if (!function_exists('get_current_screen')) {
        return;
    }
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'upload') {
        return;
    }

    $options = get_option('snn_media_settings');

    if (isset($options['media_categories']) && $options['media_categories']) {
        ?>
        <style>
        table.media .column-title .media-icon img {
            max-width: 100px;
            max-height:80px;
            width: 100%;
        }
        .media-icon {
            width: 100%;
            text-align: left;
        }
        .row-actions {
            margin-left:0px !important;
        }
        #the-list .title img {
            cursor: grab;
        }
        #custom-context-menu {
            position: absolute;
            z-index: 10000;
            display: none;
            background-color: #fff;
            border: 1px solid #ccc;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
            padding: 5px 0;
            min-width: 150px;
            font-family: Arial, sans-serif;
            font-size: 14px;
        }
        .context-menu-item {
            padding: 5px 10px;
            cursor: pointer;
        }
        .context-menu-item:hover {
            background-color: #f0f0f0;
        }
        </style>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const menu = document.createElement('div');
            menu.id = 'custom-context-menu';
            document.body.appendChild(menu);

            function loadTaxonomy() {
                menu.innerHTML = '';

                <?php
                $taxonomyItems = get_terms([
                    'taxonomy' => 'media_taxonomy_categories',
                    'hide_empty' => false,
                ]);

                if (!empty($taxonomyItems) && !is_wp_error($taxonomyItems)) {
                    foreach ($taxonomyItems as $term) {
                        $item = esc_js($term->name);
                        echo "menu.innerHTML += '<div class=\"context-menu-item\" data-term-id=\"{$term->term_id}\">" . esc_js($term->name) . "</div>';\n";
                    }
                } else {
                    echo "menu.innerHTML = '<div class=\"context-menu-item\">" . esc_js( __('No categories found', 'snn') ) . "</div>';\n";
                }
                ?>

                const menuItems = menu.querySelectorAll('.context-menu-item');
                menuItems.forEach(item => {
                    item.addEventListener('click', () => {
                        const termId = item.getAttribute('data-term-id');
                        if (!termId) return;

                        if (!currentMediaId) return;

                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', ajaxurl, true);
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        xhr.onreadystatechange = function() {
                            if (xhr.readyState === 4 && xhr.status === 200) {
                                try {
                                    const response = JSON.parse(xhr.responseText);
                                    if (response.success) {
                                        const categoryCountSpan = document.querySelector(`li[data-id="${termId}"] .category-count`);
                                        if (categoryCountSpan) {
                                            if (response.data.action_type === 'added') {
                                                categoryCountSpan.textContent = parseInt(categoryCountSpan.textContent) + 1;
                                            } else if (response.data.action_type === 'removed') {
                                                categoryCountSpan.textContent = Math.max(0, parseInt(categoryCountSpan.textContent) - 1);
                                            }
                                        }
                                        menu.style.display = 'none';
                                    } else {
                                        alert(`<?php _e('Error:', 'snn'); ?> ${response.data}`);
                                    }
                                } catch (err) {
                                    console.error(err);
                                }
                            }
                        };
                        xhr.send(
                            'action=snn_assign_media_category' +
                            '&media_id=' + encodeURIComponent(currentMediaId) +
                            '&term_id=' + encodeURIComponent(termId) +
                            '&nonce=' + '<?php echo wp_create_nonce("snn_media_categories_nonce"); ?>'
                        );

                        menu.style.display = 'none';
                    });
                });
            }

            let currentMediaId = null;

            document.addEventListener('contextmenu', function (e) {
                const attachmentElement = e.target.closest('li.attachment, div.attachment');

                if (attachmentElement) {
                    e.preventDefault();

                    if (attachmentElement.dataset.id) {
                        currentMediaId = attachmentElement.dataset.id;
                    } else if (attachmentElement.id) {
                        const idMatch = attachmentElement.id.match(/post-(\d+)/);
                        currentMediaId = idMatch ? idMatch[1] : null;
                    } else {
                        currentMediaId = null;
                    }

                    if (!currentMediaId) return;

                    loadTaxonomy();

                    menu.style.display = 'block';
                    menu.style.left = `${e.pageX}px`;
                    menu.style.top = `${e.pageY}px`;
                }
            });

            document.addEventListener('click', function (e) {
                if (!menu.contains(e.target)) {
                    menu.style.display = 'none';
                }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    menu.style.display = 'none';
                }
            });

            window.addEventListener('resize', () => {
                menu.style.display = 'none';
            });
        });
        </script>

        <?php
    }

    if (isset($options['media_categories']) && $options['media_categories']) {
        ?>
        <style>
            #media-categories-manager {
                padding-right: 10px;
                padding-top:25px;
                border-right: 1px solid #ddd;
                margin-bottom: 20px;
                position:relative;
            }
            #wpbody {
                display:grid;
                grid-template-columns:220px 1fr;
                gap:20px;
            }
            .media-categories-wrapper {
                position:fixed;
                width:200px;
            }
            #media-categories-manager h2 {
                margin-top: 0;
                margin-bottom:23px;
            }
            #media-categories-list a{
                color:#3c434a;
                text-decoration:none;
            }
            #media-categories-list {
                list-style: none;
                padding: 0;
            }
            #media-categories-list li {
                padding: 2px 8px;
                background: #fff;
                margin-bottom: 2px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-radius:4px;
                border: 2px dashed #ffffff00;
            }
            #media-categories-list li:hover {
                background: #ffffff88;
            }
            #media-categories-list li .delete-category {
                color: red;
                cursor: pointer;
                margin-left: 10px;
                display: none; 
            }
            #media-categories-list li:hover .delete-category {
                display: inline; 
            }
            #add-category-form  {
                opacity:0.4
            }
            #add-category-form:hover  {
                opacity:1
            }
            #add-category-form input[type="text"] {
                padding: 5px;
                width: 100%;
                margin-bottom:5px;
            }
            #add-category-form input[type="submit"] {
                padding: 5px 10px;
                line-height:1;
            }
            .drag-over {
                border: 2px dashed #000 !important;
            }
            #media-categories-list li .category-name {
                flex: 1;
            }
            #media-categories-list li .category-count {
                margin-left: 10px;
                font-size: 0.9em;
                background:#2271b1;
                color:white;
                padding:4px 5px;
                border-radius:4px;
                line-height:1;
            }
            #the-list .title, .attachments .attachment .attachment-details {
 
            }
            #file_size , #date{
                width:90px
            }
            #comments , .column-comments{
                display:none
            }
        </style>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('add-category-form').addEventListener('submit', function(e) {
                e.preventDefault();
                const categoryName = document.getElementById('new-category-name').value.trim();
                if (categoryName === '') return;

                const xhr = new XMLHttpRequest();
                xhr.open('POST', ajaxurl, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                const termId = response.data.term_id;
                                const termName = response.data.name;
                                const li = document.createElement('li');
                                li.setAttribute('data-id', termId);

                                const nameSpan = document.createElement('span');
                                nameSpan.classList.add('category-name');
                                nameSpan.style.cursor = 'pointer';
                                nameSpan.innerHTML = `<a href="<?php echo admin_url('upload.php?mode=list'); ?>&media_taxonomy_categories=${termId}">${termName}</a>`;

                                const countSpan = document.createElement('span');
                                countSpan.classList.add('category-count');
                                countSpan.textContent = '0';

                                const deleteSpan = document.createElement('span');
                                deleteSpan.classList.add('delete-category');
                                deleteSpan.setAttribute('data-id', termId);
                                deleteSpan.innerHTML = '&#10006;';

                                li.appendChild(nameSpan);
                                li.appendChild(countSpan);
                                li.appendChild(deleteSpan);

                                document.getElementById('media-categories-list').appendChild(li);
                                document.getElementById('new-category-name').value = '';

                                location.reload(); 
                            } else {
                                alert(response.data);
                            }
                        } catch (err) {
                            console.error(err);
                        }
                    }
                };
                xhr.send(
                    'action=snn_add_media_category' +
                    '&category_name=' + encodeURIComponent(categoryName) +
                    '&nonce=' + '<?php echo wp_create_nonce("snn_media_categories_nonce"); ?>'
                );
            });

            document.getElementById('media-categories-list').addEventListener('click', function(e) {
                if (e.target && (e.target.classList.contains('delete-category') || e.target.closest('.delete-category'))) {
                    const deleteSpan = e.target.classList.contains('delete-category') ? e.target : e.target.closest('.delete-category');
                    const termId = deleteSpan.getAttribute('data-id');
                    if (!confirm('<?php echo esc_js(__('Are you sure you want to delete this category?', 'snn')); ?>')) return;

                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', ajaxurl, true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4 && xhr.status === 200) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                if (response.success) {
                                    const li = deleteSpan.parentElement;
                                    li.parentElement.removeChild(li);
                                    location.reload(); 
                                } else {
                                    alert(response.data);
                                }
                            } catch (err) {
                                console.error(err);
                            }
                        }
                    };
                    xhr.send(
                        'action=snn_delete_media_category' +
                        '&term_id=' + encodeURIComponent(termId) +
                        '&nonce=' + '<?php echo wp_create_nonce("snn_media_categories_nonce"); ?>'
                    );
                }
            });

            const categories = document.querySelectorAll('#media-categories-list li');
            const mediaRows = document.querySelectorAll('#the-list tr, .attachments .attachment');

            categories.forEach(function(category) {
                category.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    category.classList.add('drag-over');
                });

                category.addEventListener('dragleave', function(e) {
                    category.classList.remove('drag-over');
                });

                category.addEventListener('drop', function(e) {
                    e.preventDefault();
                    category.classList.remove('drag-over');
                    const termId = category.getAttribute('data-id');
                    const mediaId = e.dataTransfer.getData('text/plain');

                    if (mediaId && termId) {
                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', ajaxurl, true);
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        xhr.onreadystatechange = function() {
                            if (xhr.readyState === 4 && xhr.status === 200) {
                                try {
                                    const response = JSON.parse(xhr.responseText);
                                    if (!response.success) {
                                        alert(response.data);
                                    } else {
                                        let countSpan = category.querySelector('.category-count');
                                        if (countSpan) {
                                            let currentCount = parseInt(countSpan.textContent, 10);
                                            if (response.data.action_type === 'added') {
                                                currentCount++;
                                            } else if (response.data.action_type === 'removed') {
                                                currentCount = Math.max(0, currentCount - 1);
                                            }
                                            countSpan.textContent = currentCount;
                                        }
                                    }
                                } catch (err) {
                                    console.error(err);
                                }
                            }
                        };
                        xhr.send(
                            'action=snn_assign_media_category' +
                            '&media_id=' + encodeURIComponent(mediaId) +
                            '&term_id=' + encodeURIComponent(termId) +
                            '&nonce=' + '<?php echo wp_create_nonce("snn_media_categories_nonce"); ?>'
                        );
                    }
                });
            });

            mediaRows.forEach(function(row) {
                row.addEventListener('dragstart', function(e) {
                    const mediaId = row.getAttribute('id') ? row.getAttribute('id').replace('post-', '') : row.dataset.id;
                    if (mediaId) {
                        e.dataTransfer.setData('text/plain', mediaId);
                    }
                });
            });
        });
        </script>

        <div id="media-categories-manager">
            <div class="media-categories-wrapper">
                <h2><?php _e('Media Categories', 'snn'); ?></h2>

                <ul id="media-categories-list">
                    <li>
                        <span class="category-name" style="cursor:pointer;">
                            <a href="<?php echo admin_url('upload.php?mode=list'); ?>"><?php _e('All Media Files', 'snn'); ?></a>
                        </span>
                        <span class="category-count"><?php
                            $count_posts = wp_count_attachments();
                            $total_media = 0;
                            if ( is_object( $count_posts ) ) {
                                foreach ( $count_posts as $status => $count ) {
                                    $total_media += $count;
                                }
                            }
                            echo number_format_i18n( $total_media );
                        ?></span>
                        <span class="delete-category" style="display:none">&#10006;</span>
                    </li>
                    <?php
                    $terms = get_terms(array(
                        'taxonomy'   => 'media_taxonomy_categories',
                        'hide_empty' => false,
                    ));
                    if (!empty($terms) && !is_wp_error($terms)) {
                        foreach ($terms as $term) {
                            $term_id   = esc_attr($term->term_id);
                            $term_name = esc_html($term->name);
                            $count     = intval($term->count);
                            $link_url  = admin_url('upload.php?mode=list&media_taxonomy_categories=' . $term_id);

                            echo '<li data-id="' . $term_id . '">';
                            echo '<span class="category-name" style="cursor:pointer;">';
                            echo '<a href="' . esc_url($link_url) . '">' . $term_name . '</a>';
                            echo '</span>';
                            echo '<span class="category-count">' . number_format_i18n( $count ) . '</span>';
                            echo '<span class="delete-category" data-id="' . $term_id . '">&#10006;</span>';
                            echo '</li>';
                        }
                    }
                    ?>
                </ul>

                <form id="add-category-form">
                    <input type="text" id="new-category-name" placeholder="<?php echo esc_attr(__('New Category Name', 'snn')); ?>" required>
                    <input type="submit" value="<?php echo esc_attr(__('Add Category', 'snn')); ?>" class="button">
                </form>
            </div>
        </div>
        <?php
    }
}
add_action('admin_head', 'snn_add_custom_css_js_to_media_page');

function snn_add_media_categories_manager_dom() {
    if (!function_exists('get_current_screen')) {
        return;
    }
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'upload') {
        return;
    }

    $options = get_option('snn_media_settings');
    if (isset($options['media_categories']) && $options['media_categories']) {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const wpBodyContent = document.getElementById('wpbody-content');
            const mediaManager = document.getElementById('media-categories-manager');
            if (wpBodyContent && mediaManager) {
                wpBodyContent.parentNode.insertBefore(mediaManager, wpBodyContent);
            }
        });
        </script>
        <?php
    }
}
add_action('admin_footer', 'snn_add_media_categories_manager_dom');

function snn_add_media_category() {
    check_ajax_referer('snn_media_categories_nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(__('Unauthorized user', 'snn'));
    }

    $category_name = sanitize_text_field($_POST['category_name']);
    if (empty($category_name)) {
        wp_send_json_error(__('Category name cannot be empty', 'snn'));
    }

    $term = wp_insert_term($category_name, 'media_taxonomy_categories');
    if (is_wp_error($term)) {
        wp_send_json_error($term->get_error_message());
    }

    $term_obj = get_term($term['term_id'], 'media_taxonomy_categories');
    if (is_wp_error($term_obj) || !$term_obj) {
        wp_send_json_error(__('Error fetching new category.', 'snn'));
    }

    wp_send_json_success(array(
        'term_id' => $term_obj->term_id,
        'name'    => $term_obj->name
    ));
}
add_action('wp_ajax_snn_add_media_category', 'snn_add_media_category');

function snn_delete_media_category() {
    check_ajax_referer('snn_media_categories_nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(__('Unauthorized user', 'snn'));
    }

    $term_id = intval($_POST['term_id']);
    if (!$term_id) {
        wp_send_json_error(__('Invalid term ID', 'snn'));
    }

    $result = wp_delete_term($term_id, 'media_taxonomy_categories');
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }

    wp_send_json_success();
}
add_action('wp_ajax_snn_delete_media_category', 'snn_delete_media_category');

function snn_assign_media_category() {
    check_ajax_referer('snn_media_categories_nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(__('Unauthorized user', 'snn'));
    }

    $media_id = intval($_POST['media_id']);
    $term_id = intval($_POST['term_id']);

    if (!$media_id || !$term_id) {
        wp_send_json_error(__('Invalid media ID or term ID', 'snn'));
    }

    $existing_terms = wp_get_post_terms($media_id, 'media_taxonomy_categories', array('fields' => 'ids'));
 
    if (in_array($term_id, $existing_terms)) {
        $result = wp_remove_object_terms($media_id, $term_id, 'media_taxonomy_categories');
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        wp_send_json_success(array('action_type' => 'removed'));
    } else {
        $result = wp_set_object_terms($media_id, array($term_id), 'media_taxonomy_categories', true);
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        wp_send_json_success(array('action_type' => 'added'));
    }
}
add_action('wp_ajax_snn_assign_media_category', 'snn_assign_media_category');

function snn_filter_media_by_taxonomy($query) {
    global $pagenow;

    if (
        is_admin() &&
        $pagenow === 'upload.php' &&
        $query->is_main_query() &&
        isset($_GET['media_taxonomy_categories']) &&
        !empty($_GET['media_taxonomy_categories'])
    ) {
        $term_id = intval($_GET['media_taxonomy_categories']);
        if ($term_id > 0) {
            $tax_query = array(
                array(
                    'taxonomy' => 'media_taxonomy_categories',
                    'field'    => 'term_id',
                    'terms'    => $term_id
                )
            );
            $query->set('tax_query', $tax_query);
        }
    }
}
add_filter('pre_get_posts', 'snn_filter_media_by_taxonomy');
?>
