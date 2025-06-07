
function applyInlineStyleToSelection(styleProp, value) {
	const sel = window.getSelection();
	if (!sel.rangeCount) return;

	let range = sel.getRangeAt(0);

	function splitTextBoundaries(r) {
		if (r.startContainer.nodeType === 3 && r.startOffset > 0) {
			r.setStart(r.startContainer.splitText(r.startOffset), 0);
		}
		if (r.endContainer.nodeType === 3 && r.endOffset < r.endContainer.length) {
			r.endContainer.splitText(r.endOffset);
		}
	}

	function walk(node, r, cb) {
		if (node.nodeType === 3) {
			cb(node);
		} else {
			for (let child of Array.from(node.childNodes)) {
				if (r.intersectsNode(child)) walk(child, r, cb);
			}
		}
	}

	splitTextBoundaries(range);

	const ancestor = range.commonAncestorContainer;
	walk(ancestor, range, txt => {
		let span = txt.parentNode;
		if (!span || span.nodeName !== 'SPAN') {
			const newSpan = document.createElement('span');
			span ? span.insertBefore(newSpan, txt) : ancestor.appendChild(newSpan);
			newSpan.appendChild(txt);
			span = newSpan;
		}
		span.style[styleProp] = value;
	});
}

function initSnnRichTextEditor(textarea) {
	if (textarea._snnRteActive) return; // Avoid double init
	textarea._snnRteActive = true;

	const ajaxurl  = window.snnRichTextEditorVars?.ajaxUrl || '';
	const snnNonce = window.snnRichTextEditorVars?.nonce || '';

	const container = document.createElement('div');
	container.className = 'snn-rich-text-editor-container';
	container.innerHTML = `
		<div class="snn-rich-text-editor-toolbar">
			<div class="snn-rich-text-editor-toolbar-group">
				<select class="snn-rich-text-editor-font-size snn-rich-text-editor-select">
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
				<select class="snn-rich-text-editor-font-family snn-rich-text-editor-select">
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
			<div class="snn-rich-text-editor-toolbar-group">
				<div class="snn-rich-text-editor-btn" data-command="bold"><strong>B</strong></div>
				<div class="snn-rich-text-editor-btn" data-command="italic"><em>I</em></div>
				<div class="snn-rich-text-editor-btn" data-command="underline"><u>U</u></div>
				<div class="snn-rich-text-editor-btn" data-command="justifyLeft"  title="Left">⇤</div>
				<div class="snn-rich-text-editor-btn" data-command="justifyCenter" title="Center">↔</div>
				<div class="snn-rich-text-editor-btn" data-command="justifyRight" title="Right">⇥</div>
			</div>
			<div class="snn-rich-text-editor-toolbar-group">
				<label>Text</label>
				<input type="color" class="snn-rich-text-editor-text-color snn-rich-text-editor-color-picker" value="#000000">
				<label style="margin-left:10px;">BG</label>
				<input type="color" class="snn-rich-text-editor-bg-color snn-rich-text-editor-color-picker" value="#FFFFFF">
			</div>
			<div class="snn-rich-text-editor-toolbar-group">
				<div class="snn-rich-text-editor-btn" data-command="createLink">Link</div>
				<div class="snn-rich-text-editor-btn" data-media-btn>Media +</div>
				<input type="file" class="snn-rich-text-editor-file-input" accept="image/*" style="display:none">
				<div class="snn-rich-text-editor-btn" data-command="removeFormat" title="Clear">Clear X</div>
			</div>
		</div>

		<div class="snn-rich-text-editor-image-tools">
			<div class="snn-rich-text-editor-toolbar-group">
				<button type="button" class="snn-rich-text-editor-btn" data-align="left">Left</button>
				<button type="button" class="snn-rich-text-editor-btn" data-align="center">Center</button>
				<button type="button" class="snn-rich-text-editor-btn" data-align="right">Right</button>
				<button type="button" class="snn-rich-text-editor-btn" data-align="none">None</button>
			</div>
			<div class="snn-rich-text-editor-toolbar-group">
				<button type="button" class="snn-rich-text-editor-btn" data-width="25%">25%</button>
				<button type="button" class="snn-rich-text-editor-btn" data-width="50%">50%</button>
				<button type="button" class="snn-rich-text-editor-btn" data-width="75%">75%</button>
				<button type="button" class="snn-rich-text-editor-btn" data-width="100%">100%</button>
			</div>
		</div>

		<div class="snn-rich-text-editor-main" contenteditable="true"></div>
	`;

	textarea.style.display = 'none';
	textarea.parentNode.insertBefore(container, textarea);

	const editor = container.querySelector('.snn-rich-text-editor-main');
	editor.innerHTML = textarea.value;

	const sync = () => textarea.value = editor.innerHTML;

	function uploadImageFile(file) {
		const fd = new FormData();
		fd.append('action','snn_comment_media_upload');
		fd.append('_wpnonce', snnNonce);
		fd.append('file', file);

		fetch(ajaxurl,{method:'POST',credentials:'same-origin',body:fd})
			.then(r=>{if(!r.ok)throw new Error('HTTP '+r.status);return r.json();})
			.then(j=>{
				if(j.success && j.data?.url){
					document.execCommand('insertImage',false,j.data.url);
					sync();
				}else alert(j.data||'Upload failed');
			})
			.catch(e=>alert(e.message||'Network'));
	}

	editor.addEventListener('paste', e => {
		const items = e.clipboardData && e.clipboardData.items;
		let imageFound = false;
		if (items) {
			for (let i = 0; i < items.length; i++) {
				const item = items[i];
				if (item.kind === 'file' && item.type.startsWith('image/')) {
					const file = item.getAsFile();
					if (file) {
						imageFound = true;
						uploadImageFile(file);
					}
				}
			}
		}
		if (imageFound) {
			e.preventDefault();
			return;
		}
		e.preventDefault();
		const text = (e.clipboardData || window.clipboardData).getData('text/plain');
		const html = text.split(/\n+/).map(l=>l.trim()?`<p>${l.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</p>`:'').join('');
		document.execCommand('insertHTML', false, html);
	});

	editor.addEventListener('keydown', e => {
		if (e.key === 'Enter' && !e.shiftKey) {
			e.preventDefault();
			document.execCommand('insertParagraph');
		}
	});

	container.querySelectorAll('.snn-rich-text-editor-btn[data-command]').forEach(btn => {
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
			sync();
		};
	});

	container.querySelector('.snn-rich-text-editor-font-size').onchange = e => {
		const v = e.target.value; if(!v) return;
		applyInlineStyleToSelection('fontSize', v);
		e.target.value=''; sync();
	};
	container.querySelector('.snn-rich-text-editor-font-family').onchange = e => {
		const v = e.target.value; if(!v) return;
		applyInlineStyleToSelection('fontFamily', v);
		e.target.value=''; sync();
	};
	container.querySelector('.snn-rich-text-editor-text-color').oninput = e => {
		applyInlineStyleToSelection('color', e.target.value); sync();
	};
	container.querySelector('.snn-rich-text-editor-bg-color').oninput = e => {
		applyInlineStyleToSelection('backgroundColor', e.target.value); sync();
	};

	editor.addEventListener('input', sync);

	const mediaBtn = container.querySelector('[data-media-btn]'),
	      fileInp  = container.querySelector('.snn-rich-text-editor-file-input');
	if(mediaBtn && fileInp){
		mediaBtn.onclick = () => fileInp.click();
		fileInp.onchange = () => { const f=fileInp.files[0]; if(f){ uploadImageFile(f); fileInp.value=''; } };
	}

	let selectedImage = null;
	const imageTools = container.querySelector('.snn-rich-text-editor-image-tools');
	const alignBtns  = imageTools.querySelectorAll('.snn-rich-text-editor-btn[data-align]');
	const widthBtns  = imageTools.querySelectorAll('.snn-rich-text-editor-btn[data-width]');

	editor.addEventListener('click', e => {
		const img = e.target.closest('img');
		if (img) {
			if (selectedImage) selectedImage.classList.remove('snn-selected-image');
			selectedImage = img;
			img.classList.add('snn-selected-image');
			imageTools.style.display = 'flex';
			alignBtns.forEach(b=>b.classList.toggle('active', img.classList.contains('snn-img-align-'+b.dataset.align)));
		}else if(selectedImage){
			selectedImage.classList.remove('snn-selected-image');
			selectedImage = null; imageTools.style.display='none';
		}
	});

	alignBtns.forEach(btn=>{
		btn.onmousedown=e=>e.preventDefault();
		btn.onclick=e=>{
			e.preventDefault();
			if(!selectedImage)return;
			selectedImage.classList.remove('snn-img-align-left','snn-img-align-center','snn-img-align-right','snn-img-align-none');
			selectedImage.classList.add('snn-img-align-'+btn.dataset.align);
			alignBtns.forEach(b=>b.classList.toggle('active', b===btn));
			sync();
		};
	});

	widthBtns.forEach(btn=>{
		btn.onmousedown=e=>e.preventDefault();
		btn.onclick=e=>{
			e.preventDefault();
			if(!selectedImage)return;
			selectedImage.style.width = btn.dataset.width;
			selectedImage.removeAttribute('height');
			sync();
		};
	});
}

// Auto-init all .snn-rich-text-editor
document.addEventListener('DOMContentLoaded', () => {
	document.querySelectorAll('textarea.snn-rich-text-editor').forEach(initSnnRichTextEditor);
});
