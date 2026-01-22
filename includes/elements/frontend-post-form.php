<?php
if ( ! defined( 'ABSPATH' ) ) exit;

use Bricks\Element;

class SNN_Element_Frontend_Post_Form extends Element {
    public $category     = 'snn';
    public $name         = 'frontend-post-form';
    public $icon         = 'ti-write';
    public $css_selector = '.snn-frontend-post-form';
    public $nestable     = false;

    public function get_label() {
        return esc_html__( 'Frontend Post Form', 'snn' );
    }

    public function set_controls() {
        $post_types = get_post_types([ 'public' => true ], 'objects');
        $post_type_options = [];
        foreach ( $post_types as $pt ) {
            $post_type_options[$pt->name] = $pt->labels->singular_name;
        }

        $taxonomies = get_taxonomies(['public' => true], 'objects');
        $taxonomy_options = [];
        foreach ($taxonomies as $tx) {
            $taxonomy_options[$tx->name] = $tx->labels->singular_name;
        }

        global $wp_roles;
        $role_options = [];
        foreach( $wp_roles->roles as $role_key => $role_info ) {
            $role_options[$role_key] = $role_info['name'];
        }

        $this->controls['post_type'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Post type', 'snn' ),
            'type'    => 'select',
            'options' => $post_type_options,
            'default' => 'post',
            'inline'  => true,
        ];

        $this->controls['taxonomy'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Taxonomy (Optional)', 'snn' ),
            'type'    => 'select',
            'options' => ['' => esc_html__('None', 'snn')] + $taxonomy_options,
            'default' => '',
            'inline'  => true,
        ];

        $this->controls['post_status'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Post status', 'snn' ),
            'type'    => 'select',
            'options' => [
                'publish' => 'Publish',
                'draft'   => 'Draft',
                'private' => 'Private',
            ],
            'default' => 'publish',
            'inline'  => true,
        ];

        $this->controls['submit_label'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Submit button label', 'snn' ),
            'type'    => 'text',
            'default' => esc_html__( 'Save Post', 'snn' ),
            'inline'  => true,
        ];

        $this->controls['enable_featured_image'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Enable featured image', 'snn' ),
            'type'    => 'checkbox',
            'default' => false,
            'inline'  => true,
        ];

        $this->controls['guest_warning_text'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Guest warning text', 'snn' ),
            'type'    => 'text',
            'default' => esc_html__( 'You do not have permission to post.', 'snn' ),
            'placeholder' => esc_html__( 'You do not have permission to post.', 'snn' ),
            'inline'  => true,
        ];

        $this->controls['allowed_user_roles'] = [
            'tab'        => 'content',
            'label'      => esc_html__( 'Allowed user roles', 'snn' ),
            'type'       => 'select',
            'options'    => $role_options,
            'multiple'   => true,
            'searchable' => true,
            'clearable'  => true,
            'placeholder'=> esc_html__('Select allowed roles', 'snn'),
            'default'    => ['administrator','editor','author'],
            'inline'  => true,
        ];

        $this->controls['custom_fields'] = [
            'tab'           => 'content',
            'label'         => esc_html__( 'Custom Fields', 'snn' ),
            'type'          => 'repeater',
            'titleProperty' => 'field_label',
            'placeholder'   => esc_html__( 'Add Custom Field', 'snn' ),
            'fields'        => [
                'field_type' => [
                    'label'      => esc_html__( 'Field Type', 'snn' ),
                    'type'       => 'select',
                    'options'    => [
                        'text'     => esc_html__( 'Text', 'snn' ),
                        'textarea' => esc_html__( 'Textarea', 'snn' ),
                        'checkbox' => esc_html__( 'Checkbox', 'snn' ),
                    ],
                    'default'    => 'text',
                ],
                'field_label' => [
                    'label'       => esc_html__( 'Field Label', 'snn' ),
                    'type'        => 'text',
                    'placeholder' => esc_html__( 'Enter field label', 'snn' ),
                    'default'     => '',
                ],
                'field_name' => [
                    'label'       => esc_html__( 'Field Name (meta key)', 'snn' ),
                    'type'        => 'text',
                    'placeholder' => esc_html__( 'custom_field_name', 'snn' ),
                    'default'     => '',
                ],
                'field_placeholder' => [
                    'label'       => esc_html__( 'Placeholder', 'snn' ),
                    'type'        => 'text',
                    'placeholder' => esc_html__( 'Enter placeholder text', 'snn' ),
                    'default'     => '',
                    'required'    => [['field_type', '=', ['text', 'textarea']]],
                ],
                'field_required' => [
                    'label'   => esc_html__( 'Required Field', 'snn' ),
                    'type'    => 'checkbox',
                    'default' => false,
                ],
                'default_checked' => [
                    'label'    => esc_html__( 'Default Checked', 'snn' ),
                    'type'     => 'checkbox',
                    'default'  => false,
                    'required' => [['field_type', '=', 'checkbox']],
                ],
                'field_width' => [
                    'label'       => esc_html__( 'Field Width', 'snn' ),
                    'type'        => 'text',
                    'placeholder' => esc_html__( '100% (e.g., 50%, 300px)', 'snn' ),
                    'default'     => '100%',
                ],
            ],
        ];

        $this->controls['button_typography'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Button typography', 'snn' ),
            'type'  => 'typography',
            'css'   => [
                [
                    'property' => 'typography',
                    'selector' => '.snn-post-submit',
                ],
            ],
        ];

        $this->controls['button_background'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Button background', 'snn' ),
            'type'  => 'color',
            'css'   => [
                [
                    'property'  => 'background-color',
                    'selector'  => '.snn-post-submit',
                    'important' => true,
                ],
            ],
        ];

        $this->controls['toolbar_bg_color'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Toolbar background', 'snn' ),
            'type'  => 'color',
            'css'   => [
                [
                    'property'  => 'background-color',
                    'selector'  => '.snn-post-editor-toolbar',
                    'important' => true,
                ],
            ],
        ];

        $this->controls['toolbar_text_color'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Toolbar text color', 'snn' ),
            'type'  => 'color',
            'css'   => [
                [
                    'property'  => 'color',
                    'selector'  => '.snn-post-editor-toolbar',
                    'important' => true,
                ],
            ],
        ];

        $this->controls['toolbar_button_background'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Toolbar button background', 'snn' ),
            'type'  => 'color',
            'default' => '#ffffff',
            'css'   => [
                [
                    'property'  => 'background-color',
                    'selector'  => '.snn-post-editor-btn',
                    'important' => true,
                ],
            ],
        ];

        $this->controls['editor_bg_color'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Editor background', 'snn' ),
            'type'  => 'color',
            'css'   => [
                [
                    'property'  => 'background-color',
                    'selector'  => '#snn-post-editor-editor',
                    'important' => true,
                ],
            ],
        ];

        $this->controls['editor_text_color'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Editor text color', 'snn' ),
            'type'  => 'color',
            'css'   => [
                [
                    'property'  => 'color',
                    'selector'  => '#snn-post-editor-editor',
                    'important' => true,
                ],
            ],
        ];
    }

    public function render() {
        $allowed_roles = isset($this->settings['allowed_user_roles']) && is_array($this->settings['allowed_user_roles']) 
            ? $this->settings['allowed_user_roles'] 
            : ['administrator','editor','author'];

        $current_user = wp_get_current_user();
        $is_allowed = false;

        if ( is_user_logged_in() ) {
            foreach ( $current_user->roles as $role ) {
                if ( in_array($role, $allowed_roles) ) {
                    $is_allowed = true;
                    break;
                }
            }
        }

        if ( ! $is_allowed ) {
            $guest_msg = isset($this->settings['guest_warning_text']) ? esc_html($this->settings['guest_warning_text']) : esc_html__('You do not have permission to post.', 'snn');
            ?>
            <div class="snn-frontend-post-form-wrapper">
                <div class="snn-guest-warning">
                    <?php echo $guest_msg; ?>
                </div>
            </div>
            <style>
                .snn-guest-warning {
                    padding: 25px;
                    background: #ffefef;
                    border: 1px solid #edcaca;
                    border-radius: 7px;
                    text-align: center;
                    color: #b90000;
                    font-size: 18px;
                }
            </style>
            <?php
            return;
        }

        $post_type   = esc_attr($this->settings['post_type']);
        $post_status = esc_attr($this->settings['post_status']);
        $label       = esc_html($this->settings['submit_label']);
        $nonce       = wp_create_nonce('snn_frontend_post');
        $can_upload  = current_user_can('upload_files');
        $enable_feat = !empty($this->settings['enable_featured_image']);
        $taxonomy    = isset($this->settings['taxonomy']) ? sanitize_key($this->settings['taxonomy']) : '';
        $custom_fields = isset($this->settings['custom_fields']) ? $this->settings['custom_fields'] : [];

        // Fetch taxonomy terms if taxonomy is selected
        $tax_terms = [];
        if ($taxonomy && taxonomy_exists($taxonomy)) {
            $tax_terms = get_terms([
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
            ]);
        }

        ?>
        <div class="snn-frontend-post-form-wrapper">
            <form class="snn-frontend-post-form" autocomplete="off">
                <input type="hidden" name="action" value="snn_frontend_post"/>
                <input type="text" name="post_title" placeholder="Title" required class="snn-post-title-input" />

                <div class="snn-flex-form-row">
                    <?php if($enable_feat): ?>
                    <div class="snn-featured-image-col">
                        <div class="snn-featured-image-box">
                            <div class="snn-featured-image-preview"></div>
                            <button type="button" class="snn-featured-image-btn">Select Featured Image</button>
                            <button type="button" class="snn-featured-image-remove">Remove</button>
                            <input type="file" class="snn-featured-image-input" accept="image/*" style="display:none">
                            <input type="hidden" name="featured_image_id" value="">
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if($taxonomy && !empty($tax_terms) && !is_wp_error($tax_terms)): ?>
                    <div class="snn-taxonomy-col">
                        <div class="snn-taxonomy-box">
                            <select class="snn-taxonomy-select" name="snn_tax_terms[]" multiple="multiple" data-placeholder="Select <?php echo esc_attr(get_taxonomy($taxonomy)->labels->singular_name); ?>">
                                <?php foreach($tax_terms as $term): ?>
                                    <option value="<?php echo esc_attr($term->term_id); ?>"><?php echo esc_html($term->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="snn-post-editor-parent"></div>

                <?php if(!empty($custom_fields)): ?>
                <div class="snn-custom-fields-wrapper">
                    <?php foreach($custom_fields as $field):
                        $field_type = isset($field['field_type']) ? $field['field_type'] : 'text';
                        $field_label = isset($field['field_label']) ? esc_html($field['field_label']) : '';
                        $field_name = isset($field['field_name']) ? sanitize_key($field['field_name']) : '';
                        $field_placeholder = isset($field['field_placeholder']) ? esc_attr($field['field_placeholder']) : '';
                        $field_required = !empty($field['field_required']);
                        $default_checked = !empty($field['default_checked']);
                        $field_width = isset($field['field_width']) ? esc_attr($field['field_width']) : '100%';

                        if(empty($field_name)) continue;
                    ?>
                    <div class="snn-custom-field snn-custom-field-<?php echo esc_attr($field_type); ?>" style="width:<?php echo $field_width; ?>;">
                        <?php if($field_type === 'checkbox'): ?>
                            <label class="snn-custom-field-checkbox-label">
                                <input type="checkbox"
                                       name="snn_custom_field[<?php echo esc_attr($field_name); ?>]"
                                       value="1"
                                       <?php checked($default_checked, true); ?>
                                       <?php if($default_checked) echo 'data-default-checked="true"'; ?>
                                       <?php if($field_required) echo 'required'; ?>
                                       class="snn-custom-field-input snn-custom-checkbox-input" />
                                <span><?php echo $field_label; ?></span>
                            </label>
                        <?php else: ?>
                            <?php if($field_label): ?>
                            <label class="snn-custom-field-label"><?php echo $field_label; ?></label>
                            <?php endif; ?>
                            <?php if($field_type === 'textarea'): ?>
                                <textarea name="snn_custom_field[<?php echo esc_attr($field_name); ?>]"
                                          placeholder="<?php echo $field_placeholder; ?>"
                                          <?php if($field_required) echo 'required'; ?>
                                          class="snn-custom-field-input snn-custom-textarea-input"></textarea>
                            <?php else: ?>
                                <input type="text"
                                       name="snn_custom_field[<?php echo esc_attr($field_name); ?>]"
                                       placeholder="<?php echo $field_placeholder; ?>"
                                       <?php if($field_required) echo 'required'; ?>
                                       class="snn-custom-field-input snn-custom-text-input" />
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <button type="submit" class="snn-post-submit"><?php echo $label; ?></button>
                <div class="snn-form-msg"></div>
                <input type="hidden" name="snn_nonce" value="<?php echo $nonce; ?>"/>
                <input type="hidden" name="snn_post_status" value="<?php echo $post_status; ?>"/>
                <input type="hidden" name="snn_post_type" value="<?php echo $post_type; ?>"/>
                <?php if($taxonomy): ?>
                    <input type="hidden" name="snn_taxonomy" value="<?php echo esc_attr($taxonomy); ?>"/>
                <?php endif; ?>
                <textarea name="post_content" id="snn-post-editor-textarea" style="display:none"></textarea>
            </form>
        </div>
        <style>
            .snn-frontend-post-form-wrapper { width:100%; }
            .snn-post-title-input { width:100%; padding:10px; margin-bottom:10px; font-size:18px; border:1px solid #ccc; border-radius:6px; }
            .snn-custom-fields-wrapper { margin-bottom:15px; display:flex; flex-wrap:wrap; gap:10px; }
            .snn-custom-field { margin-bottom:0; box-sizing:border-box; }
            .snn-custom-field-label { display:block; margin-bottom:5px; font-weight:500; font-size:15px; color:#333; }
            .snn-custom-field-input { width:100%; padding:10px; font-size:15px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box; }
            .snn-custom-textarea-input { min-height:100px; resize:vertical; font-family:inherit; }
            .snn-custom-field-checkbox-label { display:flex; align-items:center; gap:8px; cursor:pointer; font-size:15px; }
            .snn-custom-checkbox-input { width:auto; margin:0; cursor:pointer; }
            .snn-flex-form-row { display:flex; gap:25px; align-items:flex-start; justify-content:space-between; }
            .snn-featured-image-col { flex:1 1 180px; min-width:180px; max-width:220px; }
            .snn-featured-image-box { margin-bottom:15px; }
            .snn-featured-image-preview img { max-width:100%; max-height:180px; display:block; margin-bottom:8px; }
            .snn-featured-image-btn,
            .snn-featured-image-remove { padding:9px 12px; border:1px solid #ccc; border-radius:5px; background:#fafafa; cursor:pointer; }
            .snn-featured-image-remove { display:none; }
            .snn-taxonomy-col { flex:1 1 300px; max-width:300px; }
            .snn-taxonomy-box {width:300px; background:#f8f9fa;  margin-bottom:15px; }
            .snn-taxonomy-label { font-weight:500; margin-bottom:8px; }
            .snn-taxonomy-select {
                width:100%;
                min-width:100px;
                min-height:42px;
                border:1px solid #ddd;
                border-radius:6px;
                padding:5px 9px;
                font-size:15px;
                background:#fff;
                box-sizing: border-box;
                resize:vertical;
            }
            .snn-taxonomy-select option { padding:5px 8px; }
            /* Editor styles same as previous */
            .snn-post-editor-container{max-width:100%;margin:1em 0;background:#fff;border-radius:8px;position:relative;}
            .snn-post-editor-toolbar{display:flex;flex-wrap:wrap;gap:5px;padding:10px;background:#f8f9fa; border-radius:5px 5px 0px 0px;}
            .snn-post-editor-toolbar-group{display:flex;gap:4px;align-items:center}
            .snn-post-editor-btn{padding:6px 10px;background:#fff;border:1px solid #ddd;border-radius:4px;cursor:pointer;user-select:none;transition:.2s}
            .snn-post-editor-btn:hover{background:#e9ecef}
            .snn-post-editor-btn.active{background:#e2e5e9}
            .snn-post-editor-select{padding:0px 5px;border:1px solid #ddd;border-radius:4px;min-width:110px}
            #snn-post-editor-font-family{min-width:150px}
            .snn-post-editor-color-picker{width:40px;height:40px;padding:0;border:none;cursor:pointer;background:none; border-radius:5px;}
            #snn-post-editor-editor img{max-width:100%;height:auto}
            .snn-post-editor-image-tools{display:none;flex-wrap:wrap;gap:10px;padding:8px;background:#f0f0f0;border-bottom:1px solid #eee}
            #snn-post-editor-editor img.snn-img-align-left{float:left;margin-right:10px;margin-bottom:10px}
            #snn-post-editor-editor img.snn-img-align-right{float:right;margin-left:10px;margin-bottom:10px}
            #snn-post-editor-editor img.snn-img-align-center{display:block;float:none;margin:auto;margin-bottom:10px}
            #snn-post-editor-editor img.snn-img-align-none{display:block;float:none;margin:0 0 10px}
            #snn-post-editor-editor img.snn-selected-image{outline:2px solid #0073aa;outline-offset:2px}
            .snn-post-submit{border:none; padding:10px 20px;  border-radius:6px; font-size:17px; cursor:pointer;}
            .snn-post-submit:hover{}
            .snn-form-msg{margin-top:10px;}
        </style>
        <!-- Add a simple multi-select dropdown style and basic search for long lists -->
        <script>
        // Minimal multi-select dropdown search/filter and "select all/clear" support
        document.addEventListener('DOMContentLoaded', function() {
            // Feature Image code (unchanged)
            const ajaxurl = '<?php echo esc_js(admin_url('admin-ajax.php', 'relative')); ?>';
            const snnNonce = '<?php echo esc_js($nonce); ?>';
            const canUpload = <?php echo $can_upload ? 'true' : 'false'; ?>;
            const parent = document.querySelector('.snn-post-editor-parent');
            if (!parent) return;

            <?php if($enable_feat): ?>
            const featBox = document.querySelector('.snn-featured-image-box');
            const featPreview = featBox.querySelector('.snn-featured-image-preview');
            const featBtn = featBox.querySelector('.snn-featured-image-btn');
            const featInput = featBox.querySelector('.snn-featured-image-input');
            const featRemove = featBox.querySelector('.snn-featured-image-remove');
            const featHidden = featBox.querySelector('input[name="featured_image_id"]');

            featBtn.onclick = function() { featInput.click(); };
            featInput.onchange = function() {
                const file = featInput.files[0];
                if (!file) return;
                const fd = new FormData();
                fd.append('action', 'snn_post_media_upload');
                fd.append('_wpnonce', snnNonce);
                fd.append('file', file);
                featBtn.textContent = 'Uploading…';
                featBtn.disabled = true;
                fetch(ajaxurl, { method: 'POST', credentials: 'same-origin', body: fd })
                    .then(async r => {
                        if (!r.ok) throw new Error('HTTP ' + r.status);
                        return r.json();
                    })
                    .then(j => {
                        if (j.success && j.data?.url) {
                            featPreview.innerHTML = '<img src="'+j.data.url+'" alt="Featured Image">';
                            featHidden.value = j.data.id || '';
                            featBtn.style.display = 'none';
                            featRemove.style.display = 'inline-block';
                        } else {
                            alert(j.data || 'Upload failed');
                        }
                    })
                    .catch(e => alert(e.message || 'Network'))
                    .finally(() => {
                        featBtn.textContent = 'Select Image';
                        featBtn.disabled = false;
                        featInput.value = '';
                    });
            };
            featRemove.onclick = function() {
                featPreview.innerHTML = '';
                featHidden.value = '';
                featBtn.style.display = '';
                featRemove.style.display = 'none';
            };
            <?php endif; ?>

            // ==== TAXONOMY MULTI-SELECT ENHANCEMENT ====
            // Custom simple dropdown multi-select w/ search
            const taxSelect = document.querySelector('.snn-taxonomy-select');
            if (taxSelect) {
                // Wrap select in a custom div for styling
                taxSelect.style.display = "none";
                const wrapper = document.createElement('div');
                wrapper.className = 'snn-taxonomy-dropdown-wrapper';
                taxSelect.parentNode.insertBefore(wrapper, taxSelect);
                wrapper.appendChild(taxSelect);

                // Selected display box
                const selectedBox = document.createElement('div');
                selectedBox.className = 'snn-taxonomy-selected-box';
                selectedBox.tabIndex = 0;
                selectedBox.textContent = taxSelect.getAttribute('data-placeholder') || 'Select';
                wrapper.insertBefore(selectedBox, taxSelect);

                // Dropdown list
                const dropdown = document.createElement('div');
                dropdown.className = 'snn-taxonomy-dropdown';
                dropdown.style.display = 'none';
                dropdown.innerHTML = `<div class="snn-taxonomy-dropdown-search"><input type="text" placeholder="Search..." class="snn-taxonomy-search-input" /></div>
                <div class="snn-taxonomy-options"></div>
                <div class="snn-taxonomy-dropdown-actions">
                    <button type="button" class="snn-tax-select-all">All</button>
                    <button type="button" class="snn-tax-clear-all">Clear</button>
                </div>
                `;
                wrapper.appendChild(dropdown);

                // Render options
                function renderOptions(filter='') {
                    const optionsWrap = dropdown.querySelector('.snn-taxonomy-options');
                    optionsWrap.innerHTML = '';
                    Array.from(taxSelect.options).forEach(opt => {
                        if (filter && !opt.text.toLowerCase().includes(filter.toLowerCase())) return;
                        const div = document.createElement('div');
                        div.className = 'snn-taxonomy-option';
                        const input = document.createElement('input');
                        input.type = 'checkbox';
                        input.value = opt.value;
                        input.checked = opt.selected;
                        input.id = 'taxterm-'+opt.value;
                        input.onchange = function() {
                            opt.selected = input.checked;
                            updateSelectedBox();
                        };
                        const label = document.createElement('label');
                        label.setAttribute('for', input.id);
                        label.textContent = opt.text;
                        div.appendChild(input);
                        div.appendChild(label);
                        optionsWrap.appendChild(div);
                    });
                }
                renderOptions();

                // Show/hide dropdown
                function openDropdown() {
                    dropdown.style.display = 'block';
                    selectedBox.classList.add('active');
                    renderOptions();
                }
                function closeDropdown() {
                    dropdown.style.display = 'none';
                    selectedBox.classList.remove('active');
                }
                selectedBox.onclick = function(e) {
                    e.stopPropagation();
                    if (dropdown.style.display === 'block') closeDropdown();
                    else openDropdown();
                };
                document.addEventListener('click', function(e) {
                    if (!wrapper.contains(e.target)) closeDropdown();
                });

                // Search
                dropdown.querySelector('.snn-taxonomy-search-input').oninput = function(e) {
                    renderOptions(e.target.value);
                };

                // Select all / Clear all
                dropdown.querySelector('.snn-tax-select-all').onclick = function() {
                    Array.from(taxSelect.options).forEach(opt => { opt.selected = true; });
                    renderOptions(dropdown.querySelector('.snn-taxonomy-search-input').value);
                    updateSelectedBox();
                };
                dropdown.querySelector('.snn-tax-clear-all').onclick = function() {
                    Array.from(taxSelect.options).forEach(opt => { opt.selected = false; });
                    renderOptions(dropdown.querySelector('.snn-taxonomy-search-input').value);
                    updateSelectedBox();
                };

                // Update selected
                function updateSelectedBox() {
                    let selected = Array.from(taxSelect.selectedOptions).map(o=>o.text);
                    if(selected.length > 0)
                        selectedBox.textContent = selected.join(', ');
                    else
                        selectedBox.textContent = taxSelect.getAttribute('data-placeholder') || 'Select';
                }
                updateSelectedBox();
            }

            // ==== END TAXONOMY MULTI-SELECT ====

            // === EDITOR CODE (UNCHANGED) ===
            const container = document.createElement('div');
            container.className = 'snn-post-editor-container';
            container.innerHTML = `
                <div class="snn-post-editor-toolbar">
                    <div class="snn-post-editor-toolbar-group">
                        <select id="snn-post-editor-font-size" class="snn-post-editor-select">
                            <option value="">Size</option>
                            <option value="16">16</option>
                            <option value="18">18</option>
                            <option value="20">20</option>
                            <option value="24">24</option>
                            <option value="30">30</option>
                            <option value="40">40</option>
                            <option value="50">50</option>
                            <option value="80">80</option>
                        </select>
                        <select id="snn-post-editor-font-family" class="snn-post-editor-select">
                            <option value="">Font</option>
                            <option value="system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif">System UI</option>
                            <option value="Arial, Helvetica, sans-serif">Arial</option>
                            <option value="Verdana, Geneva, sans-serif">Verdana</option>
                            <option value="Trebuchet MS, Trebuchet, sans-serif">Trebuchet MS</option>
                            <option value="Times New Roman, Times, serif">Times New Roman</option>
                            <option value="Georgia, serif">Georgia</option>
                            <option value="Courier New, Courier, monospace">Courier New</option>
                            <option value="Comic Sans MS, Comic Sans, cursive">Comic Sans MS</option>
                        </select>
                    </div>
                    <div class="snn-post-editor-toolbar-group">
                        <div class="snn-post-editor-btn" data-command="bold"><strong>B</strong></div>
                        <div class="snn-post-editor-btn" data-command="italic"><em>I</em></div>
                        <div class="snn-post-editor-btn" data-command="underline"><u>U</u></div>
                        <div class="snn-post-editor-btn" data-command="justifyLeft"  title="Left">⇤</div>
                        <div class="snn-post-editor-btn" data-command="justifyCenter" title="Center">↔</div>
                        <div class="snn-post-editor-btn" data-command="justifyRight" title="Right">⇥</div>
                    </div>
                    <div class="snn-post-editor-toolbar-group">
                        <label for="snn-post-editor-text-color">Text</label>
                        <input type="color" id="snn-post-editor-text-color" class="snn-post-editor-color-picker" value="#000000">
                        <label for="snn-post-editor-bg-color" style="margin-left:10px;">BG</label>
                        <input type="color" id="snn-post-editor-bg-color" class="snn-post-editor-color-picker" value="#FFFFFF">
                    </div>
                    <div class="snn-post-editor-toolbar-group">
                        <div class="snn-post-editor-btn" data-command="createLink">Link</div>
                        ${canUpload ? `<div class="snn-post-editor-btn" id="snn-post-editor-media-btn">Media +</div>
                        <input type="file" id="snn-post-editor-file-input" accept="image/*" style="display:none">` : ''}
                        <div class="snn-post-editor-btn" data-command="removeFormat" title="Clear">Clear X</div>
                    </div>
                </div>
                <div class="snn-post-editor-image-tools">
                    <div class="snn-post-editor-toolbar-group">
                        <button type="button" class="snn-post-editor-btn" data-align="left">Left</button>
                        <button type="button" class="snn-post-editor-btn" data-align="center">Center</button>
                        <button type="button" class="snn-post-editor-btn" data-align="right">Right</button>
                        <button type="button" class="snn-post-editor-btn" data-align="none">None</button>
                    </div>
                    <div class="snn-post-editor-toolbar-group">
                        <button type="button" class="snn-post-editor-btn" data-width="25%">25%</button>
                        <button type="button" class="snn-post-editor-btn" data-width="50%">50%</button>
                        <button type="button" class="snn-post-editor-btn" data-width="75%">75%</button>
                        <button type="button" class="snn-post-editor-btn" data-width="100%">100%</button>
                    </div>
                </div>
                <div id="snn-post-editor-editor" contenteditable="true" style="min-height:200px; padding:10px; outline:none; overflow-y:auto; line-height:1.6; background:#f8f9fa; border-radius:0 0 5px 5px;"></div>
            `;
            parent.appendChild(container);

            const editor = container.querySelector('#snn-post-editor-editor');
            const textarea = document.getElementById('snn-post-editor-textarea');
            const imageTools = container.querySelector('.snn-post-editor-image-tools');
            const alignBtns = imageTools.querySelectorAll('.snn-post-editor-btn[data-align]');
            const widthBtns = imageTools.querySelectorAll('.snn-post-editor-btn[data-width]');
            const form = parent.closest('form.snn-frontend-post-form');
            const msg = form.querySelector('.snn-form-msg');
            const btn = form.querySelector('.snn-post-submit');
            let selectedImage = null;

            // === FONT SIZE CUSTOM HANDLER ===
            container.querySelector('#snn-post-editor-font-size').onchange = e => {
                const px = e.target.value;
                if (!px) return;
                setFontSizeSpan(px);
                e.target.value = '';
                textarea.value = editor.innerHTML;
            };

            // Insert font-size with <span style="font-size:XXpx"> for selection
            function setFontSizeSpan(px) {
                const sel = window.getSelection();
                if (!sel.rangeCount) return;
                const range = sel.getRangeAt(0);
                if (range.collapsed) return;
                // Create span
                const span = document.createElement('span');
                span.style.fontSize = px + 'px';
                range.surroundContents(span);
                sel.removeAllRanges();
                sel.addRange(range);
            }

            // FONT FAMILY (no change)
            container.querySelector('#snn-post-editor-font-family').onchange = e => {
                const v = e.target.value;
                if (!v) return;
                document.execCommand('fontName', false, v);
                e.target.value = '';
                textarea.value = editor.innerHTML;
            };

            // Color pickers (no change)
            container.querySelector('#snn-post-editor-text-color').oninput = e => {
                document.execCommand('foreColor', false, e.target.value);
                textarea.value = editor.innerHTML;
            };
            container.querySelector('#snn-post-editor-bg-color').oninput = e => {
                document.execCommand('hiliteColor', false, e.target.value);
                textarea.value = editor.innerHTML;
            };

            // Toolbar commands (no change)
            container.querySelectorAll('.snn-post-editor-btn[data-command]').forEach(btn => {
                btn.onmousedown = e => e.preventDefault();
                btn.onclick = e => {
                    e.preventDefault();
                    const cmd = btn.dataset.command;
                    if (cmd === 'createLink') {
                        const url = prompt('Enter URL');
                        if (url) document.execCommand('createLink', false, url);
                    } else {
                        document.execCommand(cmd, false, null);
                    }
                    editor.focus();
                    textarea.value = editor.innerHTML;
                };
            });

            // Media Upload (AJAX) (no change)
            if (canUpload) {
                const mediaBtn = container.querySelector('#snn-post-editor-media-btn'),
                    fileInp  = container.querySelector('#snn-post-editor-file-input');
                mediaBtn.onclick = () => fileInp.click();
                fileInp.onchange = () => {
                    const f = fileInp.files[0];
                    if (!f) return;
                    const fd = new FormData();
                    fd.append('action', 'snn_post_media_upload');
                    fd.append('_wpnonce', snnNonce);
                    fd.append('file', f);
                    mediaBtn.textContent = 'Uploading…';
                    mediaBtn.disabled = true;
                    fetch(ajaxurl, { method: 'POST', credentials: 'same-origin', body: fd })
                        .then(async r => {
                            if (!r.ok) throw new Error('HTTP ' + r.status);
                            return r.json();
                        })
                        .then(j => {
                            if (j.success && j.data?.url) {
                                document.execCommand('insertImage', false, j.data.url);
                            } else {
                                alert(j.data || 'Upload failed');
                            }
                            textarea.value = editor.innerHTML;
                        })
                        .catch(e => alert(e.message || 'Network'))
                        .finally(() => {
                            mediaBtn.textContent = 'Media +';
                            mediaBtn.disabled = false;
                            fileInp.value = '';
                        });
                };
            }

            // Image selection/tools (no change)
            editor.addEventListener('click', e => {
                const img = e.target.closest('img');
                if (img) {
                    if (selectedImage) selectedImage.classList.remove('snn-selected-image');
                    selectedImage = img;
                    img.classList.add('snn-selected-image');
                    imageTools.style.display = 'flex';
                    alignBtns.forEach(btn => {
                        btn.classList.toggle(
                            'active',
                            img.classList.contains('snn-img-align-' + btn.dataset.align)
                        );
                    });
                } else if (selectedImage) {
                    selectedImage.classList.remove('snn-selected-image');
                    selectedImage = null;
                    imageTools.style.display = 'none';
                }
            });

            // Alignment buttons (no change)
            alignBtns.forEach(btn => {
                btn.onmousedown = e => e.preventDefault();
                btn.onclick = e => {
                    e.preventDefault();
                    if (!selectedImage) return;
                    selectedImage.classList.remove(
                        'snn-img-align-left',
                        'snn-img-align-center',
                        'snn-img-align-right',
                        'snn-img-align-none'
                    );
                    selectedImage.classList.add('snn-img-align-' + btn.dataset.align);
                    alignBtns.forEach(b => b.classList.toggle('active', b === btn));
                    textarea.value = editor.innerHTML;
                };
            });

            // Width percentage buttons (no change)
            widthBtns.forEach(btn => {
                btn.onmousedown = e => e.preventDefault();
                btn.onclick = e => {
                    e.preventDefault();
                    if (!selectedImage) return;
                    selectedImage.style.width = btn.dataset.width;
                    selectedImage.removeAttribute('height');
                    textarea.value = editor.innerHTML;
                };
            });

            // ====== ENSURE <p> TAGS FOR PARAGRAPHS ======
            // Normalize on input and paste to wrap text blocks into <p> tags
            function wrapParagraphs(node) {
                if (!node || !node.childNodes) return;
                const nodes = Array.from(node.childNodes);
                let buffer = [];
                function flushParagraph() {
                    if (buffer.length > 0) {
                        const p = document.createElement('p');
                        buffer.forEach(n => p.appendChild(n));
                        node.insertBefore(p, nodes[0]);
                        buffer = [];
                    }
                }
                nodes.forEach(n => {
                    if (
                        n.nodeType === 3 && n.nodeValue.trim().length > 0
                        || (n.nodeType === 1 && n.nodeName === 'BR')
                    ) {
                        buffer.push(n);
                    }
                    else if (
                        n.nodeType === 1 && ['P', 'DIV'].indexOf(n.nodeName) === -1
                    ) {
                        buffer.push(n);
                    }
                    else if (n.nodeType === 1 && n.nodeName === 'DIV') {
                        flushParagraph();
                        const p = document.createElement('p');
                        while (n.firstChild) p.appendChild(n.firstChild);
                        node.replaceChild(p, n);
                    } else if (n.nodeType === 1 && n.nodeName === 'P') {
                        flushParagraph();
                    }
                });
                flushParagraph();
            }

            // On input: convert orphan text/divs to <p>
            editor.addEventListener('input', function() {
                editor.innerHTML = html;
                textarea.value = editor.innerHTML;
            });

            // On paste: clean up and ensure <p> for paragraphs
            editor.addEventListener('paste', function(e) {
                e.preventDefault();
                let text = '';
                if (e.clipboardData) {
                    text = e.clipboardData.getData('text/plain');
                } else if (window.clipboardData) {
                    text = window.clipboardData.getData('Text');
                }
                let html = text.split(/\n+/).map(line => line.trim() ? '<p>' + line + '</p>' : '').join('');
                document.execCommand('insertHTML', false, html);
                textarea.value = editor.innerHTML;
            });

            textarea.value = editor.innerHTML;

            // --------- FIXED SUBMIT HANDLER FOR RELIABLE REDIRECT ----------
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                textarea.value = editor.innerHTML;
                msg.textContent = '';
                btn.disabled = true;
                fetch(ajaxurl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {'X-Requested-With': 'XMLHttpRequest'},
                    body: new FormData(form)
                })
                .then(r => r.json())
                .then(res => {
                    if(res.success){
                        if(res.data.status === 'publish'){
                            // REDIRECT IMMEDIATELY AND RELIABLY TO NEW POST
                            if (res.data.url && typeof res.data.url === 'string') {
                                // Try both assign() and setting href, so it works in all browsers, iframed, AJAX etc.
                                setTimeout(function(){
                                    try {
                                        window.location.assign(res.data.url);
                                        window.location.href = res.data.url;
                                    } catch(err){
                                        // fallback to clickable link if something blocks
                                        msg.innerHTML = 'Post published! <a href="'+res.data.url+'" target="_blank" rel="noopener">View Post</a>';
                                    }
                                }, 100); // Small delay for UI
                            } else {
                                msg.textContent = 'Post published but redirect failed: no URL returned.';
                            }
                        } else {
                            msg.textContent = 'Post saved successfully with status: ' + res.data.status;
                            form.reset();
                            editor.innerHTML = '';
                            textarea.value = '';
                            <?php if($enable_feat): ?>
                            if (featPreview && featBtn && featRemove && featHidden) {
                                featPreview.innerHTML = '';
                                featBtn.style.display = '';
                                featRemove.style.display = 'none';
                                featHidden.value = '';
                            }
                            <?php endif; ?>
                            // Reset custom fields
                            form.querySelectorAll('.snn-custom-field-input').forEach(function(input) {
                                if(input.type === 'checkbox') {
                                    input.checked = input.hasAttribute('data-default-checked');
                                } else {
                                    input.value = '';
                                }
                            });
                        }
                    } else {
                        msg.textContent = res.data || 'Error';
                    }
                })
                .catch(()=>msg.textContent='An error occurred. Try again.')
                .finally(()=>btn.disabled = false);
            });
            // --------- END FIX ---------
        });
        </script>
        <style>
        /* Taxonomy dropdown custom styles */
        .snn-taxonomy-dropdown-wrapper {
            position:relative;
            width:100%;
            
        }
        .snn-taxonomy-selected-box {
            width:100%;
            min-height:38px;
            padding:5px 12px;
            border:1px solid #ccc;
            border-radius:6px;
            background:#fff;
            cursor:pointer;
            box-sizing:border-box;
            font-size:15px;
            margin-bottom:2px;
        }
        .snn-taxonomy-selected-box.active {
            border-color: #0073aa;
            box-shadow: 0 0 2px #0073aa;
        }
        .snn-taxonomy-dropdown {
            position:absolute;
            left:0;right:0;top:100%;
            background:#fff;
            border:1px solid #ccc;
            border-radius:7px;
            z-index: 99;
            box-shadow: 0 4px 16px #0001;
            margin-top:2px;
            overflow:auto;
        }
        .snn-taxonomy-dropdown-search {
            padding:6px 8px;
            border-bottom:1px solid #eee;
        }
        .snn-taxonomy-search-input {
            width:100%;
            padding:5px 7px;
            border-radius:5px;
            border:1px solid #eee;
            font-size:15px;
        }
        .snn-taxonomy-options { max-height:150px; overflow:auto; }
        .snn-taxonomy-option {
            display:flex;align-items:center;gap:7px;padding:6px 12px;font-size:15px;cursor:pointer;
        }
        .snn-taxonomy-option input[type=checkbox] {
            margin-right:5px;
        }
        .snn-taxonomy-option:hover {
            background:#f0f2fa;
        }
        .snn-taxonomy-dropdown-actions {
            border-top:1px solid #eee;
            padding:6px 12px;
            display:flex;
            gap:8px;
            justify-content:right;
        }
        .snn-taxonomy-dropdown-actions button {
            border:none;
            background:#e9ecef;
            border-radius:5px;
            padding:4px 12px;
            font-size:13px;
            cursor:pointer;
        }
        .snn-taxonomy-dropdown-actions button:hover {
            background:#0073aa;color:#fff;
        }
        </style>
        <?php
    }
}

// Register element
add_action('init', function(){
    if(class_exists('Bricks\Elements')){
        \Bricks\Elements::register_element('SNN_Element_Frontend_Post_Form');
    }
});

// AJAX: Save post
add_action('wp_ajax_snn_frontend_post', 'snn_frontend_post_handler');
function snn_frontend_post_handler(){
    if(!is_user_logged_in() || !check_ajax_referer('snn_frontend_post','snn_nonce',false)){
        wp_send_json_error('Unauthorized request.');
    }
    $title = sanitize_text_field($_POST['post_title']??'');
    $content = wp_kses_post($_POST['post_content']??'');
    $status = in_array($_POST['snn_post_status'], ['publish','draft','private']) ? $_POST['snn_post_status'] : 'draft';
    $type = post_type_exists($_POST['snn_post_type']) ? $_POST['snn_post_type'] : 'post';
    $feat_id = isset($_POST['featured_image_id']) ? intval($_POST['featured_image_id']) : 0;
    $taxonomy = !empty($_POST['snn_taxonomy']) ? sanitize_key($_POST['snn_taxonomy']) : '';
    $term_ids = [];

    if(!$title || !$content) wp_send_json_error('Title and content required.');
    
    // Check if user has capability to create this post type
    $post_type_obj = get_post_type_object($type);
    if (!$post_type_obj || !current_user_can($post_type_obj->cap->create_posts)) {
        wp_send_json_error('Insufficient permissions to create this post type.');
    }
    
    // Additional check for post status capability
    if ($status === 'publish' && !current_user_can($post_type_obj->cap->publish_posts)) {
        // Downgrade to draft if user can't publish
        $status = 'draft';
    }
    
    if($taxonomy && taxonomy_exists($taxonomy) && !empty($_POST['snn_tax_terms'])) {
        foreach((array)$_POST['snn_tax_terms'] as $tid){
            $term_ids[] = intval($tid);
        }
    }
    $post_id = wp_insert_post([
        'post_title'   => $title,
        'post_content' => $content,
        'post_status'  => $status,
        'post_type'    => $type,
        'post_author'  => get_current_user_id(),
    ]);
    if(is_wp_error($post_id)) wp_send_json_error($post_id->get_error_message());
    if ($feat_id) {
        set_post_thumbnail($post_id, $feat_id);
    }
    if ($taxonomy && taxonomy_exists($taxonomy) && !empty($term_ids)) {
        wp_set_object_terms($post_id, $term_ids, $taxonomy, false);
    }

    // Save custom fields as post meta
    if(!empty($_POST['snn_custom_field']) && is_array($_POST['snn_custom_field'])) {
        foreach($_POST['snn_custom_field'] as $meta_key => $meta_value) {
            $meta_key = sanitize_key($meta_key);
            if(is_array($meta_value)) {
                $meta_value = array_map('sanitize_text_field', $meta_value);
            } else {
                $meta_value = sanitize_text_field($meta_value);
            }
            update_post_meta($post_id, $meta_key, $meta_value);
        }
    }

    wp_send_json_success([
        'status' => $status,
        'url'    => get_permalink($post_id),
    ]);
}

// AJAX: Media upload (image only)
add_action('wp_ajax_snn_post_media_upload', function(){
    if(!is_user_logged_in() || !check_ajax_referer('snn_frontend_post','_wpnonce',false)){
        wp_send_json_error('Unauthorized');
    }
    if (empty($_FILES['file'])) wp_send_json_error('No file');
    $file = $_FILES['file'];
    if($file['error'] !== 0) wp_send_json_error('Upload error');
    $type = wp_check_filetype($file['name']);
    if(strpos($type['type'], 'image/') !== 0) wp_send_json_error('Image only');
    require_once(ABSPATH.'wp-admin/includes/file.php');
    require_once(ABSPATH.'wp-admin/includes/media.php');
    require_once(ABSPATH.'wp-admin/includes/image.php');
    $id = media_handle_upload('file', 0);
    if(is_wp_error($id)) wp_send_json_error($id->get_error_message());
    $url = wp_get_attachment_url($id);
    wp_send_json_success(['url'=>$url, 'id'=>$id]);
});
?>
