<?php
/**
 * Plugin Name: Bricks Global Classes Manager (Merged Example)
 * Description: Example code showing how to save & merge Bricks Global Classes/Categories safely.
 */

// --------------------------------------------------------------------------
// 1. REGISTER ADMIN PAGE
// --------------------------------------------------------------------------
add_action('admin_menu', function () {
    add_submenu_page(
        'snn-settings',
        'Bricks Global Classes Manager',
        'Bricks Global Classes',
        'manage_options',
        'bricks-global-classes',
        'bgcc_page',
        99
    );
});

// --------------------------------------------------------------------------
// 2. MERGED SAVE ROUTINE (INSTEAD OF OVERWRITING)
// --------------------------------------------------------------------------
add_action('admin_init', function () {
    // Check if user clicked our "Save Changes" button
    if (isset($_POST['bgcc_submit']) && wp_verify_nonce($_POST['bgcc_nonce'], 'bgcc_save_options')) {

        // Retrieve existing data from DB
        $existing_categories = get_option('bricks_global_classes_categories', []);
        $existing_classes    = get_option('bricks_global_classes', []);

        // Convert existing categories to [id => {...}] for easy lookups
        $oldCatById = [];
        foreach ($existing_categories as $cat) {
            $oldCatById[ $cat['id'] ] = $cat;
        }

        // -----------------------------------------------------------
        // CATEGORIES: Merge with existing or optionally overwrite
        // -----------------------------------------------------------
        // If user posted no categories at all, we just keep existing
        // to avoid wiping out everything by mistake.
        $postedCategories = $_POST['categories'] ?? null;
        $new_categories   = [];

        if ($postedCategories && is_array($postedCategories)) {
            foreach ($postedCategories as $c) {
                $catId   = !empty($c['id']) ? sanitize_text_field($c['id']) : bgcc_rand_id();
                $catName = !empty($c['name']) ? sanitize_text_field($c['name']) : '';

                // Only keep categories that have a name
                if ($catName) {
                    // If this category existed, merge. 
                    // (Right now we only store [id, name], so not much to merge.)
                    // But in the future if you have more fields, you can preserve them here.
                    $new_categories[] = [
                        'id'   => $catId,
                        'name' => $catName
                    ];
                }
            }
        } else {
            // If no categories posted, keep all existing
            $new_categories = $existing_categories;
        }

        // Save categories
        update_option('bricks_global_classes_categories', $new_categories);

        // Build an array of valid IDs for the new categories 
        // (so we don't save classes with invalid categories).
        $valid_ids = array_column($new_categories, 'id');

        // Convert existing classes to [id => {...}] for merging
        $oldClassById = [];
        foreach ($existing_classes as $cl) {
            $oldClassById[ $cl['id'] ] = $cl;
        }

        // -----------------------------------------------------------
        // CLASSES: Merge posted data with existing
        // -----------------------------------------------------------
        // If user posted nothing, keep existing classes
        $postedClasses = $_POST['classes'] ?? null;
        $new_classes   = [];

        if ($postedClasses && is_array($postedClasses)) {
            foreach ($postedClasses as $cl) {
                $classId   = !empty($cl['id']) ? sanitize_text_field($cl['id']) : bgcc_rand_id();
                $className = !empty($cl['name']) ? sanitize_text_field($cl['name']) : '';
                $catId     = !empty($cl['category']) ? sanitize_text_field($cl['category']) : '';

                // Must have a name AND a valid category
                if ($className && in_array($catId, $valid_ids)) {
                    // Parse new CSS into structured array
                    $parsedSettings = bgcc_parse_css($cl['css'] ?? '');

                    // Merge with old data if it existed:
                    if (isset($oldClassById[$classId])) {
                        $oldSettings = $oldClassById[$classId]['settings'] ?? [];

                        // Merge color sub-array so we preserve color ID & name if user only changed hex
                        if (!empty($oldSettings['_typography']['color']) && !empty($parsedSettings['_typography']['color'])) {
                            $oldColor = $oldSettings['_typography']['color'];
                            $newColor = $parsedSettings['_typography']['color'];

                            // If the hex changed, preserve old 'id' and 'name' unless you want to rename
                            $parsedSettings['_typography']['color']['id']   = $oldColor['id'];
                            $parsedSettings['_typography']['color']['name'] = $oldColor['name'];
                        }

                        // Similarly, you could merge other sub-arrays here if you want to keep border/fallback, etc.
                        // For simplicity, let's do a shallow array_merge:
                        $parsedSettings = array_replace_recursive($oldSettings, $parsedSettings);
                    }

                    // Build final class array
                    $new_classes[] = [
                        'id'       => $classId,
                        'name'     => $className,
                        'settings' => $parsedSettings,
                        'category' => $catId,
                        'modified' => time(),
                        'user_id'  => get_current_user_id(),
                    ];
                }
            }
        } else {
            // If no classes posted, keep all existing
            $new_classes = $existing_classes;
        }

        // Save classes
        update_option('bricks_global_classes', $new_classes);

        // Show success message
        add_settings_error('bgcc_messages', 'bgcc_message', 'Settings Saved', 'updated');

        // Redirect to avoid POST re-submission
        wp_redirect(add_query_arg(['page' => 'bricks-global-classes', 'settings-updated' => 'true'], admin_url('admin.php')));
        exit;
    }
});

// --------------------------------------------------------------------------
// 3. HELPER FUNCTION: Generate random ID
// --------------------------------------------------------------------------
function bgcc_rand_id($len = 6) {
    return substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, $len);
}

// --------------------------------------------------------------------------
// 4. HELPER FUNCTION: PARSE CSS INTO Bricks-LIKE SETTINGS
//    - Slightly tweaked to avoid overwriting color 'id'/'name' unless new
// --------------------------------------------------------------------------
function bgcc_parse_css($css) {
    $settings = [];
    $css = preg_replace('/\/\*.*?\*\//s','', trim($css));

    // Extract rules between { ... }
    if (preg_match('/\{(.*)\}/s', $css, $m)) {
        $rules = explode(';', $m[1]);
    } else {
        $rules = explode(';', $css);
    }

    foreach ($rules as $r) {
        $r = trim($r);
        if (! $r || strpos($r, ':') === false) {
            continue;
        }
        list($p, $v) = array_map('trim', explode(':', $r, 2));
        switch (strtolower($p)) {
            case 'color':
                if ($hex = sanitize_hex_color($v)) {
                    // We'll create a fresh color ID & name, but we can merge later
                    $settings['_typography']['color'] = [
                        'hex'  => $hex,
                        'id'   => bgcc_rand_id(),
                        'name' => 'Custom Color'
                    ];
                }
                break;

            case 'background-color':
                if ($hex = sanitize_hex_color($v)) {
                    $settings['_background']['color'] = [
                        'hex'  => $hex,
                        'id'   => bgcc_rand_id(),
                        'name' => 'Custom BG'
                    ];
                }
                break;

            case 'text-align':
                if (in_array(strtolower($v), ['left','center','right','justify'])) {
                    $settings['_typography']['text-align'] = strtolower($v);
                }
                break;

            case 'text-transform':
                if (in_array(strtolower($v), ['none','capitalize','uppercase','lowercase'])) {
                    $settings['_typography']['text-transform'] = strtolower($v);
                }
                break;

            case 'font-weight':
                $settings['_typography']['font-weight'] = sanitize_text_field($v);
                break;

            case 'border':
                $settings['_border']['border'] = sanitize_text_field($v);
                break;

            default:
                // Put unknown props into _custom_css
                $settings['_custom_css'][sanitize_text_field($p)] = sanitize_text_field($v);
        }
    }
    return $settings;
}

// --------------------------------------------------------------------------
// 5. ADMIN PAGE HTML
// --------------------------------------------------------------------------
function bgcc_page() {
    $categories = get_option('bricks_global_classes_categories', []);
    $classes    = get_option('bricks_global_classes', []);
    ?>
    <div class="wrap">
        <h1>Bricks Global Classes Manager - EXPERIMENTAL DONT USE IT YET ;)</h1>
        <?php settings_errors('bgcc_messages'); ?>

        <h2>Bulk CSS</h2>
        <p>Paste multiple CSS class definitions here (e.g. <code>.my-class { color: red; }</code>) and click "Generate Classes".</p>
        <textarea id="bulk-css" rows="4" style="width:100%; font-family:monospace;"></textarea><br><br>
        <button type="button" class="button button-secondary" id="generate-classes">Generate Classes</button>

        <form method="post" style="margin-top: 30px;">
            <?php wp_nonce_field('bgcc_save_options', 'bgcc_nonce'); ?>

            <div style="display: grid; grid-template-columns: 1fr 3fr; gap: 20px;">
                <!-- ===================== CATEGORIES ===================== -->
                <div>
                    <h2>Categories</h2>
                    <table class="widefat fixed" id="categories-table">
                        <thead>
                            <tr>
                                <th style="display:none;">ID</th>
                                <th>Name</th>
                                <th><button type="button" class="button button-secondary" id="add-category">Add</button></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($categories): foreach ($categories as $i => $c): ?>
                            <tr>
                                <td style="display:none;">
                                    <input type="hidden" name="categories[<?php echo $i; ?>][id]" value="<?php echo esc_attr($c['id']); ?>">
                                </td>
                                <td>
                                    <input type="text" name="categories[<?php echo $i; ?>][name]" value="<?php echo esc_attr($c['name']); ?>" required>
                                </td>
                                <td>
                                    <button type="button" class="button button-danger remove-row">Remove</button>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                            <!-- If no existing categories, show one blank row -->
                            <tr>
                                <td style="display:none;">
                                    <input type="hidden" name="categories[0][id]" value="<?php echo esc_attr(bgcc_rand_id()); ?>">
                                </td>
                                <td>
                                    <input type="text" name="categories[0][name]" required>
                                </td>
                                <td>
                                    <button type="button" class="button button-danger remove-row">Remove</button>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- ===================== CLASSES ===================== -->
                <div>
                    <h2>Classes</h2>
                    <table class="widefat fixed" id="classes-table">
                        <thead>
                            <tr>
                                <th style="display:none;">ID</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>CSS</th>
                                <th><button type="button" class="button button-secondary" id="add-class">Add</button></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($classes): foreach ($classes as $i => $cl): ?>
                            <tr>
                                <td style="display:none;">
                                    <input type="hidden" name="classes[<?php echo $i; ?>][id]" value="<?php echo esc_attr($cl['id']); ?>">
                                </td>
                                <td>
                                    <input type="text" name="classes[<?php echo $i; ?>][name]" value="<?php echo esc_attr($cl['name']); ?>" required>
                                </td>
                                <td>
                                    <select name="classes[<?php echo $i; ?>][category]" required>
                                        <option value="">— Select —</option>
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo esc_attr($cat['id']); ?>" <?php selected($cl['category'], $cat['id']); ?>>
                                            <?php echo esc_html($cat['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <textarea name="classes[<?php echo $i; ?>][css]" rows="5" placeholder=".classname { /* CSS */ }" required>
                                        <?php echo esc_textarea(bgcc_gen_css($cl['name'], $cl['settings'])); ?>
                                    </textarea>
                                </td>
                                <td>
                                    <button type="button" class="button button-danger remove-row">Remove</button>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                            <!-- If no existing classes, show one blank row -->
                            <tr>
                                <td style="display:none;">
                                    <input type="hidden" name="classes[0][id]" value="<?php echo esc_attr(bgcc_rand_id()); ?>">
                                </td>
                                <td>
                                    <input type="text" name="classes[0][name]" required>
                                </td>
                                <td>
                                    <select name="classes[0][category]" required>
                                        <option value="">— Select —</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo esc_attr($cat['id']); ?>">
                                                <?php echo esc_html($cat['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <textarea name="classes[0][css]" rows="5" placeholder=".classname { /* CSS */ }" required></textarea>
                                </td>
                                <td>
                                    <button type="button" class="button button-danger remove-row">Remove</button>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php submit_button('Save Changes', 'primary', 'bgcc_submit'); ?>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const categoriesTable   = document.getElementById('categories-table').querySelector('tbody');
        const classesTable      = document.getElementById('classes-table').querySelector('tbody');
        const addCategoryBtn    = document.getElementById('add-category');
        const addClassBtn       = document.getElementById('add-class');
        const generateBtn       = document.getElementById('generate-classes');
        const bulkCssTextarea   = document.getElementById('bulk-css');

        // Build a quick array from categories for Bulk CSS generation
        const categories = <?php echo json_encode(array_map(fn($c) => ['id' => $c['id'], 'name' => $c['name']], $categories)); ?>;

        function bgccRandId(len = 6) {
            return [...Array(len)].map(() => 'abcdefghijklmnopqrstuvwxyz0123456789'[Math.floor(Math.random() * 36)]).join('');
        }

        // Add Category button
        addCategoryBtn.addEventListener('click', () => {
            const row = categoriesTable.insertRow();
            const idx = categoriesTable.rows.length - 1;
            row.innerHTML = `
                <td style="display:none;">
                    <input type="hidden" name="categories[${idx}][id]" value="${bgccRandId()}">
                </td>
                <td>
                    <input type="text" name="categories[${idx}][name]" required>
                </td>
                <td>
                    <button type="button" class="button button-danger remove-row">Remove</button>
                </td>`;
        });

        // Add Class button
        addClassBtn.addEventListener('click', () => {
            const row = classesTable.insertRow();
            const idx = classesTable.rows.length - 1;
            let options = '<option value="">— Select —</option>';
            categories.forEach(c => {
                options += `<option value="${c.id}">${c.name}</option>`;
            });

            row.innerHTML = `
                <td style="display:none;">
                    <input type="hidden" name="classes[${idx}][id]" value="${bgccRandId()}">
                </td>
                <td>
                    <input type="text" name="classes[${idx}][name]" required>
                </td>
                <td>
                    <select name="classes[${idx}][category]" required>${options}</select>
                </td>
                <td>
                    <textarea name="classes[${idx}][css]" rows="5" placeholder=".classname { /* CSS */ }" required></textarea>
                </td>
                <td>
                    <button type="button" class="button button-danger remove-row">Remove</button>
                </td>`;
        });

        // Remove row in dynamic tables
        document.body.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-row')) {
                e.target.closest('tr').remove();
            }
        });

        // Bulk CSS generator
        generateBtn.addEventListener('click', () => {
            const text = bulkCssTextarea.value.trim();
            if (!text) {
                alert('Please paste some CSS first.');
                return;
            }
            if (!categories.length) {
                alert('Please create at least one category first.');
                return;
            }

            const regex = /\.([A-Za-z0-9_\-]+)\s*\{([^}]*)\}/g;
            let match, found = 0;

            while ((match = regex.exec(text)) !== null) {
                found++;
                const className = match[1].trim();
                const rules     = match[2].trim(); 
                
                const row = classesTable.insertRow();
                const idx = classesTable.rows.length - 1;

                let options = '<option value="">— Select —</option>';
                categories.forEach((c, index) => {
                    const selected = (index === 0) ? 'selected' : '';
                    options += `<option value="${c.id}" ${selected}>${c.name}</option>`;
                });

                const finalCSS = '.' + className + ' {\n' + rules + '\n}';

                row.innerHTML = `
                    <td style="display:none;">
                        <input type="hidden" name="classes[${idx}][id]" value="${bgccRandId()}">
                    </td>
                    <td>
                        <input type="text" name="classes[${idx}][name]" value="${className}" required>
                    </td>
                    <td>
                        <select name="classes[${idx}][category]" required>${options}</select>
                    </td>
                    <td>
                        <textarea name="classes[${idx}][css]" rows="5" required>${finalCSS}</textarea>
                    </td>
                    <td>
                        <button type="button" class="button button-danger remove-row">Remove</button>
                    </td>`;
            }

            alert(found + ' classes generated from Bulk CSS.');
        });
    });
    </script>
    <?php
}

// --------------------------------------------------------------------------
// 6. HELPER FUNCTION: Generate CSS from parsed settings
// --------------------------------------------------------------------------
function bgcc_gen_css($name, $s) {
    $css = '.' . $name . ' {' . "\n";

    // Typography
    if (!empty($s['_typography'])) {
        if (!empty($s['_typography']['text-align'])) {
            $css .= "  text-align: {$s['_typography']['text-align']};\n";
        }
        if (!empty($s['_typography']['text-transform'])) {
            $css .= "  text-transform: {$s['_typography']['text-transform']};\n";
        }
        if (!empty($s['_typography']['color']['hex'])) {
            $css .= "  color: {$s['_typography']['color']['hex']};\n";
        }
        if (!empty($s['_typography']['font-weight'])) {
            $css .= "  font-weight: {$s['_typography']['font-weight']};\n";
        }
    }

    // Background
    if (!empty($s['_background']['color']['hex'])) {
        $css .= "  background-color: {$s['_background']['color']['hex']};\n";
    }

    // Border
    if (!empty($s['_border']['border'])) {
        $css .= "  border: {$s['_border']['border']};\n";
    }

    // Custom props
    if (!empty($s['_custom_css']) && is_array($s['_custom_css'])) {
        foreach ($s['_custom_css'] as $p => $v) {
            $css .= "  {$p}: {$v};\n";
        }
    }

    $css .= '}';
    return $css;
}
