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

        $this->controls['post_type'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Post type', 'snn' ),
            'type'    => 'select',
            'options' => $post_type_options,
            'default' => 'post',
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
        ];

        $this->controls['submit_label'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Submit button label', 'snn' ),
            'type'    => 'text',
            'default' => esc_html__( 'Post', 'snn' ),
        ];

        // ==== EDITOR CONTROLS (style, background, colors, etc.) ====

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

        $this->controls['button_padding'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Button padding', 'snn' ),
            'type'  => 'dimensions',
            'css'   => [
                [
                    'property'  => 'padding',
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
        $post_type   = esc_attr($this->settings['post_type']);
        $post_status = esc_attr($this->settings['post_status']);
        $label       = esc_html($this->settings['submit_label']);
        $nonce       = wp_create_nonce('snn_frontend_post');
        $can_upload  = current_user_can('upload_files');
        ?>
        <div class="snn-frontend-post-form-wrapper">
            <form class="snn-frontend-post-form" autocomplete="off">
                <input type="hidden" name="action" value="snn_frontend_post"/>
                <input type="text" name="post_title" placeholder="Title" required style="width:100%; padding:10px; margin-bottom:10px; font-size:18px;" />
                <div class="snn-post-editor-parent"></div>
                <button type="submit" class="snn-post-submit" style="padding:10px 20px;"><?php echo $label; ?></button>
                <div class="snn-form-msg" style="margin-top:10px;"></div>
                <input type="hidden" name="snn_nonce" value="<?php echo $nonce; ?>"/>
                <input type="hidden" name="snn_post_status" value="<?php echo $post_status; ?>"/>
                <input type="hidden" name="snn_post_type" value="<?php echo $post_type; ?>"/>
                <textarea name="post_content" id="snn-post-editor-textarea" style="display:none"></textarea>
            </form>
        </div>
        <style>
            .snn-frontend-post-form-wrapper{width:100%}
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
            .snn-post-submit{border:none; padding:10px;}
        </style>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ajaxurl = '<?php echo esc_js(admin_url('admin-ajax.php', 'relative')); ?>';
            const snnNonce = '<?php echo esc_js($nonce); ?>';
            const canUpload = <?php echo $can_upload ? 'true' : 'false'; ?>;
            const parent = document.querySelector('.snn-post-editor-parent');
            if (!parent) return;

            // Build editor DOM dynamically (like comment editor)
            const container = document.createElement('div');
            container.className = 'snn-post-editor-container';
            container.innerHTML = `
                <div class="snn-post-editor-toolbar">
                    <div class="snn-post-editor-toolbar-group">
                        <select id="snn-post-editor-font-size" class="snn-post-editor-select">
                            <option value="">Size</option>
                            <option value="16px" selected>16</option>
                            <option value="18px">18</option>
                            <option value="20px">20</option>
                            <option value="24px">24</option>
                            <option value="30px">30</option>
                            <option value="40px">40</option>
                            <option value="50px">50</option>
                            <option value="80px">80</option>
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

            // Now JS for editor logic (identical to previous, just adapted for new DOM)
            const editor = container.querySelector('#snn-post-editor-editor');
            const textarea = document.getElementById('snn-post-editor-textarea');
            const imageTools = container.querySelector('.snn-post-editor-image-tools');
            const alignBtns = imageTools.querySelectorAll('.snn-post-editor-btn[data-align]');
            const widthBtns = imageTools.querySelectorAll('.snn-post-editor-btn[data-width]');
            const form = parent.closest('form.snn-frontend-post-form');
            const msg = form.querySelector('.snn-form-msg');
            const btn = form.querySelector('.snn-post-submit');
            let selectedImage = null;

            // Toolbar commands
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

            // Font size
            container.querySelector('#snn-post-editor-font-size').onchange = e => {
                const v = e.target.value;
                if (!v) return;
                document.execCommand('fontSize', false, '7');
                editor.querySelectorAll('font[size="7"]').forEach(el => {
                    el.style.fontSize = v;
                    el.removeAttribute('size');
                });
                e.target.value = '';
                textarea.value = editor.innerHTML;
            };

            // Font family
            container.querySelector('#snn-post-editor-font-family').onchange = e => {
                const v = e.target.value;
                if (!v) return;
                document.execCommand('fontName', false, v);
                e.target.value = '';
                textarea.value = editor.innerHTML;
            };

            // Color pickers
            container.querySelector('#snn-post-editor-text-color').oninput = e => {
                document.execCommand('foreColor', false, e.target.value);
                textarea.value = editor.innerHTML;
            };
            container.querySelector('#snn-post-editor-bg-color').oninput = e => {
                document.execCommand('hiliteColor', false, e.target.value);
                textarea.value = editor.innerHTML;
            };

            // Sync on input
            editor.addEventListener('input', () => textarea.value = editor.innerHTML);

            // Media Upload (AJAX)
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

            // Image selection/tools
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

            // Alignment buttons
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

            // Width percentage buttons
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

            // FORM AJAX
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                textarea.value = editor.innerHTML; // Sync content
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
                            window.location.href = res.data.url;
                        } else {
                            msg.textContent = 'Post saved successfully with status: ' + res.data.status;
                            form.reset();
                            editor.innerHTML = '';
                            textarea.value = '';
                        }
                    } else {
                        msg.textContent = res.data || 'Error';
                    }
                })
                .catch(()=>msg.textContent='An error occurred. Try again.')
                .finally(()=>btn.disabled = false);
            });
        });
        </script>
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
    if(!$title || !$content) wp_send_json_error('Title and content required.');
    $post_id = wp_insert_post([
        'post_title'   => $title,
        'post_content' => $content,
        'post_status'  => $status,
        'post_type'    => $type,
        'post_author'  => get_current_user_id(),
    ]);
    if(is_wp_error($post_id)) wp_send_json_error($post_id->get_error_message());
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
    wp_send_json_success(['url'=>$url]);
});
