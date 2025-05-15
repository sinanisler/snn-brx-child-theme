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
		return esc_html__( 'Comment Form', 'bricks' );
	}

	public function set_controls() {
		$this->controls['editor_height'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Editor min-height', 'bricks' ),
			'type'    => 'slider',
			'units'   => [ 'px' => [ 'min' => 100, 'max' => 600, 'step' => 10 ] ],
			'default' => '200px',
		];
		$this->controls['submit_label'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Submit button label', 'bricks' ),
			'type'    => 'text',
			'default' => esc_html__( 'Post Comment', 'bricks' ),
		];
		$this->controls['allow_uploads'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Enable media upload', 'bricks' ),
			'type'    => 'checkbox',
			'default' => true,
			'inline'  => true,
		];
		$this->controls['button_typography'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Button typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'typography',
					'selector' => '.snn-comment-submit',
				],
			],
		];
	}

	public function render() {
		$min_h   = is_array( $this->settings['editor_height'] ?? '' )
			? ( $this->settings['editor_height']['value'] . ( $this->settings['editor_height']['unit'] ?? 'px' ) )
			: '200px';
		$label   = $this->settings['submit_label'] ?? esc_html__( 'Post Comment', 'bricks' );
		$uploads = ! empty( $this->settings['allow_uploads'] );
		$nonce   = wp_create_nonce( 'snn_comment_media_upload' );

		$this->set_attribute( '_root', 'class', 'snn-comment-form-wrapper' );
		echo '<div ' . $this->render_attributes( '_root' ) . '>';
		?>
		<style>
		.brxe-comment-form{width:100%}
		.snn-comment-form{margin-top:30px}
		.snn-comment-form-comment label{display:block;margin-bottom:5px;font-weight:bold}
		.snn-comment-editor-container{max-width:100%;margin:1em 0;background:#fff;border:1px solid #e0e0e0;border-radius:8px;position:relative}
		.snn-comment-editor-toolbar{display:flex;flex-wrap:wrap;gap:5px;padding:10px;background:#f8f9fa;border-bottom:1px solid #eee}
		.snn-comment-editor-toolbar-group{display:flex;gap:4px;align-items:center}
		.snn-comment-editor-btn{padding:6px 10px;background:#fff;border:1px solid #ddd;border-radius:4px;cursor:pointer;user-select:none;transition:.2s}
		.snn-comment-editor-btn:hover{background:#e9ecef}
		.snn-comment-editor-btn.active{background:#e2e5e9}
		.snn-comment-editor-select{padding:5px;border:1px solid #ddd;border-radius:4px;min-width:110px}
		#snn-comment-editor-font-family{min-width:150px}
		.snn-comment-editor-color-picker{width:30px;height:30px;padding:0;border:none;cursor:pointer;background:none}
		#snn-comment-editor-editor{min-height:<?php echo esc_attr( $min_h ); ?>;padding:10px;outline:none;overflow-y:auto;line-height:1.6}
		#snn-comment-editor-editor:focus{box-shadow:inset 0 0 0 1px #1971c2}
		#snn-comment-editor-editor img{max-width:100%;height:auto}
		.snn-comment-editor-image-tools{display:none;flex-wrap:wrap;gap:10px;padding:8px;background:#f0f0f0;border-bottom:1px solid #eee}
		#snn-comment-editor-editor img.snn-img-align-left{float:left;margin-right:10px;margin-bottom:10px}
		#snn-comment-editor-editor img.snn-img-align-right{float:right;margin-left:10px;margin-bottom:10px}
		#snn-comment-editor-editor img.snn-img-align-center{display:block;float:none;margin:auto;margin-bottom:10px}
		#snn-comment-editor-editor img.snn-img-align-none{display:block;float:none;margin:0 0 10px}
		#snn-comment-editor-editor img.snn-selected-image{outline:2px solid #0073aa;outline-offset:2px}
		</style>
		<?php

		comment_form( [
			'class_form'    => 'snn-comment-form',
			'class_submit'  => 'snn-comment-submit',
			'label_submit'  => $label,
			'comment_field' => '
				<p class="snn-comment-form-comment">
					<textarea id="comment" name="comment" cols="45" rows="8" required style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;"></textarea>
				</p>',
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

			/* Font Size */
			container.querySelector('#snn-comment-editor-font-size').onchange = e => {
				const v = e.target.value;
				if ( ! v ) return;
				document.execCommand('fontSize', false, '7');
				editor.querySelectorAll('font[size="7"]').forEach(el=>{
					el.style.fontSize = v;
					el.removeAttribute('size');
				});
				e.target.value = '';
			};

			/* Font Family */
			container.querySelector('#snn-comment-editor-font-family').onchange = e => {
				const v = e.target.value;
				if ( ! v ) return;
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
						if ( ! r.ok ) throw new Error('HTTP '+r.status);
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
			wp_send_json_error( __( 'Permission denied.', 'bricks' ), 403 );
		}
		if ( empty( $_FILES['file'] ) ) {
			wp_send_json_error( __( 'No file received.', 'bricks' ), 400 );
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
	// add_action( 'wp_ajax_nopriv_snn_comment_media_upload', 'snn_comment_media_upload' );
}
