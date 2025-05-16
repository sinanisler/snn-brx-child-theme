<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Bricks\Element;

/**
 * Comment Form element (rich-text editor)
 */
class SNN_Element_Comment_Form extends Element {
	public $category     = 'snn';
	public $name         = 'comment-form';
	public $icon         = 'ti-write';
	public $css_selector = '.snn-comment-form';
	public $nestable     = false;

	public function get_label() {
		return esc_html__( 'Comment Form', 'snn' );
	}

	public function set_controls() {

		/* ---------- CONTENT TAB ------------------------------------------------ */

		$this->controls['submit_label'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Submit button label', 'snn' ),
			'type'    => 'text',
			'default' => esc_html__( 'Post Comment', 'snn' ),
			'placeholder' => esc_html__( 'Post Comment', 'snn' ),
		];

		$this->controls['allow_uploads'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Enable media upload', 'snn' ),
			'type'    => 'checkbox',
			'default' => true,
			'inline'  => true,
		];

		$this->controls['enable_website_field'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Show Website input (dummy, does not show)', 'snn' ),
			'type'    => 'checkbox',
			'default' => false,
			'inline'  => true,
			'desc'    => esc_html__( 'Not functional, website field is not shown.', 'snn' ),
		];

		$this->controls['hide_logged_in_as'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Hide “logged-in as” info', 'snn' ),
			'type'    => 'checkbox',
			'default' => false,
			'inline'  => true,
		];

		$this->controls['reply_title'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Reply title', 'snn' ),
			'type'    => 'text',
			'default' => esc_html__( 'Leave a Reply', 'snn' ),
			'placeholder' => esc_html__( 'Leave a Reply', 'snn' ),
		];

		/* ---------- STYLE TAB -------------------------------------------------- */

		$this->controls['button_typography'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Button typography', 'snn' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'typography',
					'selector' => '.snn-comment-submit',
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
					'selector'  => '.snn-comment-submit',
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
					'selector'  => '.snn-comment-editor-toolbar',
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
					'selector'  => '.snn-comment-editor-toolbar',
					'important' => true,
				],
			],
		];

		// New control: toolbar button background
		$this->controls['toolbar_button_background'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Toolbar button background', 'snn' ),
			'type'  => 'color',
			'default' => '#ffffff',
			'css'   => [
				[
					'property'  => 'background-color',
					'selector'  => '.snn-comment-editor-btn',
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
					'selector'  => '#snn-comment-editor-editor',
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
					'selector'  => '#snn-comment-editor-editor',
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
					'selector'  => '.snn-comment-submit',
					'important' => true,
				],
			],
		];




	}

public function render() {

    $label          = $this->settings['submit_label'] ?? esc_html__( 'Post Comment', 'snn' );
    $uploads        = ! empty( $this->settings['allow_uploads'] );
    $hide_logged_in = ! empty( $this->settings['hide_logged_in_as'] );
    $reply_title    = $this->settings['reply_title'] ?? esc_html__( 'Leave a Reply', 'snn' );
    $nonce          = wp_create_nonce( 'snn_comment_media_upload' );
    $show_website   = ! empty( $this->settings['enable_website_field'] );

    $commenter = wp_get_current_commenter();
    $req       = get_option( 'require_name_email' );
    $post_id   = get_the_ID();

    $this->set_attribute( '_root', 'class', 'snn-comment-form-wrapper' );
    if ( $hide_logged_in ) {
        $this->set_attribute( '_root', 'class', 'hide-logged-in-as' );
    }

    echo '<div ' . $this->render_attributes( '_root' ) . '>';
    ?>
    <style>
    /* --- base layout --- */
    .brxe-comment-form{width:100%}
    .snn-comment-form{margin-top:30px}
    .snn-comment-form-wrapper.hide-logged-in-as .logged-in-as{display:none}
    /* --- editor --- */
    .snn-comment-form-comment label{display:block;margin-bottom:5px;font-weight:bold}
    .snn-comment-editor-container{max-width:100%;margin:1em 0;background:#fff;border-radius:8px;position:relative;}
    .snn-comment-editor-toolbar{display:flex;flex-wrap:wrap;gap:5px;padding:10px;background:#f8f9fa; border-radius:5px 5px 0px 0px;}
    .snn-comment-editor-toolbar-group{display:flex;gap:4px;align-items:center}
    .snn-comment-editor-btn{padding:6px 10px;background:#fff;border:1px solid #ddd;border-radius:4px;cursor:pointer;user-select:none;transition:.2s}
    .snn-comment-editor-btn:hover{background:#e9ecef}
    .snn-comment-editor-btn.active{background:#e2e5e9}
    .snn-comment-editor-select{padding:0px 5px;border:1px solid #ddd;border-radius:4px;min-width:110px}
    #snn-comment-editor-font-family{min-width:150px}
    .snn-comment-editor-color-picker{width:40px;height:40px;padding:0;border:none;cursor:pointer;background:none; border-radius:5px;}
    #snn-comment-editor-editor{min-height:200px;padding:10px;outline:none;overflow-y:auto;line-height:1.6;  border-radius:0 0 5px 5px; background: #f8f9fa;}
    #snn-comment-editor-editor:focus{box-shadow:inset 0 0 0 1px #1971c2}
    #snn-comment-editor-editor img{max-width:100%;height:auto}
    /* --- image tools --- */
    .snn-comment-editor-image-tools{display:none;flex-wrap:wrap;gap:10px;padding:8px;background:#f0f0f0;border-bottom:1px solid #eee}
    #snn-comment-editor-editor img.snn-img-align-left{float:left;margin-right:10px;margin-bottom:10px}
    #snn-comment-editor-editor img.snn-img-align-right{float:right;margin-left:10px;margin-bottom:10px}
    #snn-comment-editor-editor img.snn-img-align-center{display:block;float:none;margin:auto;margin-bottom:10px}
    #snn-comment-editor-editor img.snn-img-align-none{display:block;float:none;margin:0 0 10px}
    #snn-comment-editor-editor img.snn-selected-image{outline:2px solid #0073aa;outline-offset:2px}
    .snn-comment-submit{border:none; padding:10px;}
    </style>
    <?php

    // Build comment fields array, website conditional
    $fields = [
        'author' => '<p class="comment-form-author">' .
            '<label for="author">' . esc_html__( 'Name', 'snn' ) . '</label> ' .
            ( $req ? '<span class="required">*</span>' : '' ) .
            '<input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ?? '' ) .
            '" size="30"' . ( $req ? ' required="required"' : '' ) . ' /></p>',
        'email'  => '<p class="comment-form-email">' .
            '<label for="email">' . esc_html__( 'Email', 'snn' ) . '</label> ' .
            ( $req ? '<span class="required">*</span>' : '' ) .
            '<input id="email" name="email" type="email" value="' . esc_attr( $commenter['comment_author_email'] ?? '' ) .
            '" size="30"' . ( $req ? ' required="required"' : '' ) . ' /></p>',
    ];
    if ( $show_website ) {
        $fields['url'] = '<p class="comment-form-url">' .
            '<label for="url">' . esc_html__( 'Website', 'snn' ) . '</label>' .
            '<input id="url" name="url" type="url" value="' . esc_attr( $commenter['comment_author_url'] ?? '' ) .
            '" size="30" /></p>';
    }

    comment_form( [
        'class_form'    => 'snn-comment-form',
        'class_submit'  => 'snn-comment-submit',
        'label_submit'  => $label,
        'title_reply'   => $reply_title,
        'comment_field' => '
            <p class="snn-comment-form-comment">
                <textarea id="comment" name="comment" cols="45" rows="8" required style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;"></textarea>
            </p>',
        'fields'        => apply_filters( 'comment_form_default_fields', $fields, $post_id ),
    ] );
    ?>
    <script>
    document.addEventListener('DOMContentLoaded',()=>{
        const textarea = document.getElementById('comment');
        if (!textarea) return;

        const ajaxurl   = '<?php echo esc_js( admin_url( 'admin-ajax.php', 'relative' ) ); ?>';
        const snnNonce  = '<?php echo esc_js( $nonce ); ?>';

        /* Build editor */
        const container = document.createElement('div');
        container.className = 'snn-comment-editor-container';
        container.innerHTML = `
            <div class="snn-comment-editor-toolbar">
                <div class="snn-comment-editor-toolbar-group">
                    <select id="snn-comment-editor-font-size" class="snn-comment-editor-select">
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
                    <select id="snn-comment-editor-font-family" class="snn-comment-editor-select">
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
                <div class="snn-comment-editor-toolbar-group">
                    <div class="snn-comment-editor-btn" data-command="bold"><strong>B</strong></div>
                    <div class="snn-comment-editor-btn" data-command="italic"><em>I</em></div>
                    <div class="snn-comment-editor-btn" data-command="underline"><u>U</u></div>
                    <div class="snn-comment-editor-btn" data-command="justifyLeft"  title="Left">⇤</div>
                    <div class="snn-comment-editor-btn" data-command="justifyCenter" title="Center">↔</div>
                    <div class="snn-comment-editor-btn" data-command="justifyRight" title="Right">⇥</div>
                </div>
                <div class="snn-comment-editor-toolbar-group">
                    <label for="snn-comment-editor-text-color">Text</label>
                    <input type="color" id="snn-comment-editor-text-color" class="snn-comment-editor-color-picker" value="#000000">
                    <label for="snn-comment-editor-bg-color" style="margin-left:10px;">BG</label>
                    <input type="color" id="snn-comment-editor-bg-color" class="snn-comment-editor-color-picker" value="#FFFFFF">
                </div>
                <div class="snn-comment-editor-toolbar-group">
                    <div class="snn-comment-editor-btn" data-command="createLink">Link</div>
                    <?php if ( current_user_can( 'upload_files' ) && $uploads ) : ?>
                    <div class="snn-comment-editor-btn" id="snn-comment-editor-media-btn">Media +</div>
                    <input type="file" id="snn-comment-editor-file-input" accept="image/*" style="display:none">
                    <?php endif; ?>
                    <div class="snn-comment-editor-btn" data-command="removeFormat" title="Clear">Clear X</div>
                </div>
            </div>

            <div class="snn-comment-editor-image-tools">
                <div class="snn-comment-editor-toolbar-group">
                    <button type="button" class="snn-comment-editor-btn" data-align="left">Left</button>
                    <button type="button" class="snn-comment-editor-btn" data-align="center">Center</button>
                    <button type="button" class="snn-comment-editor-btn" data-align="right">Right</button>
                    <button type="button" class="snn-comment-editor-btn" data-align="none">None</button>
                </div>
                <div class="snn-comment-editor-toolbar-group">
                    <button type="button" class="snn-comment-editor-btn" data-width="25%">25%</button>
                    <button type="button" class="snn-comment-editor-btn" data-width="50%">50%</button>
                    <button type="button" class="snn-comment-editor-btn" data-width="75%">75%</button>
                    <button type="button" class="snn-comment-editor-btn" data-width="100%">100%</button>
                </div>
            </div>

            <div id="snn-comment-editor-editor" contenteditable="true"></div>
        `;
        textarea.parentNode.insertBefore(container, textarea);

        const editor = container.querySelector('#snn-comment-editor-editor');
        editor.innerHTML = textarea.value;
        const sync = () => textarea.value = editor.innerHTML;

        /* Toolbar commands */
        container.querySelectorAll('.snn-comment-editor-btn[data-command]').forEach(btn=>{
            btn.onmousedown = e => e.preventDefault();
            btn.onclick = e => {
                e.preventDefault();
                const cmd = btn.dataset.command;
                if ( cmd === 'createLink' ) {
                    const url = prompt('Enter URL');
                    if ( url ) document.execCommand('createLink', false, url);
                } else {
                    document.execCommand(cmd, false, null);
                }
                editor.focus();
                sync();
            };
        });

        /* Font size */
        container.querySelector('#snn-comment-editor-font-size').onchange = e => {
            const v = e.target.value;
            if ( !v ) return;
            document.execCommand('fontSize', false, '7');
            editor.querySelectorAll('font[size="7"]').forEach(el=>{
                el.style.fontSize = v;
                el.removeAttribute('size');
            });
            e.target.value = '';
        };

        /* Font family */
        container.querySelector('#snn-comment-editor-font-family').onchange = e => {
            const v = e.target.value;
            if ( !v ) return;
            document.execCommand('fontName', false, v);
            e.target.value = '';
        };

        /* Color pickers */
        container.querySelector('#snn-comment-editor-text-color').oninput = e => document.execCommand('foreColor', false, e.target.value);
        container.querySelector('#snn-comment-editor-bg-color').oninput  = e => document.execCommand('hiliteColor', false, e.target.value);

        /* Sync on input */
        editor.addEventListener('input', sync);

        /* === IMAGE UPLOAD === */
        <?php if ( current_user_can( 'upload_files' ) && $uploads ) : ?>
        const mediaBtn = container.querySelector('#snn-comment-editor-media-btn'),
              fileInp  = container.querySelector('#snn-comment-editor-file-input');
        mediaBtn.onclick = () => fileInp.click();
        fileInp.onchange = () => {
            const f = fileInp.files[0];
            if ( ! f ) return;
            const fd = new FormData();
            fd.append('action', 'snn_comment_media_upload');
            fd.append('_wpnonce', snnNonce);
            fd.append('file', f);

            mediaBtn.textContent = 'Uploading…';
            mediaBtn.disabled    = true;
            fetch(ajaxurl, { method: 'POST', credentials: 'same-origin', body: fd })
                .then(async r => {
                    if ( !r.ok ) throw new Error('HTTP '+r.status);
                    return r.json();
                })
                .then(j => {
                    if ( j.success && j.data?.url ) {
                        document.execCommand('insertImage', false, j.data.url);
                    } else {
                        alert(j.data || 'Upload failed');
                    }
                })
                .catch(e => alert(e.message || 'Network'))
                .finally(()=>{
                    mediaBtn.textContent = 'Media +';
                    mediaBtn.disabled    = false;
                    fileInp.value        = '';
                    sync();
                });
        };
        <?php endif; ?>

        /* === IMAGE SELECTION & TOOLS === */
        let selectedImage = null;
        const imageTools  = container.querySelector('.snn-comment-editor-image-tools');
        const alignBtns   = imageTools.querySelectorAll('.snn-comment-editor-btn[data-align]');
        const widthBtns   = imageTools.querySelectorAll('.snn-comment-editor-btn[data-width]');

        // Show/hide tools when clicking on an <img>
        editor.addEventListener('click', e => {
            const img = e.target.closest('img');
            if ( img ) {
                if ( selectedImage ) selectedImage.classList.remove('snn-selected-image');
                selectedImage = img;
                img.classList.add('snn-selected-image');

                imageTools.style.display = 'flex';

                alignBtns.forEach(btn => {
                    btn.classList.toggle(
                        'active',
                        img.classList.contains('snn-img-align-'+btn.dataset.align)
                    );
                });
            } else if ( selectedImage ) {
                selectedImage.classList.remove('snn-selected-image');
                selectedImage = null;
                imageTools.style.display = 'none';
            }
        });

        // Alignment buttons
        alignBtns.forEach(btn => {
            btn.onmousedown = e => e.preventDefault();
            btn.onclick     = e => {
                e.preventDefault();
                if ( ! selectedImage ) return;
                selectedImage.classList.remove(
                    'snn-img-align-left',
                    'snn-img-align-center',
                    'snn-img-align-right',
                    'snn-img-align-none'
                );
                selectedImage.classList.add('snn-img-align-'+btn.dataset.align);
                alignBtns.forEach(b => b.classList.toggle('active', b === btn));
                sync();
            };
        });

        // Width percentage buttons
        widthBtns.forEach(btn => {
            btn.onmousedown = e => e.preventDefault();
            btn.onclick     = e => {
                e.preventDefault();
                if ( ! selectedImage ) return;
                selectedImage.style.width = btn.dataset.width;
                selectedImage.removeAttribute('height');
                sync();
            };
        });
    });
    </script>
    <?php
    echo '</div>';
}

	

}

/* ---------- AJAX HANDLER ------------------------------------------- */
if ( ! function_exists( 'snn_comment_media_upload' ) ) {
	function snn_comment_media_upload() {
		check_ajax_referer( 'snn_comment_media_upload' );
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'snn' ), 403 );
		}
		if ( empty( $_FILES['file'] ) ) {
			wp_send_json_error( __( 'No file received.', 'snn' ), 400 );
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$attachment_id = media_handle_upload( 'file', 0 );
		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( $attachment_id->get_error_message(), 400 );
		}

		wp_send_json_success( [
			'id'  => $attachment_id,
			'url' => wp_get_attachment_url( $attachment_id ),
		] );
	}
	add_action( 'wp_ajax_snn_comment_media_upload', 'snn_comment_media_upload' );
}
