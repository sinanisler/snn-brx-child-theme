/**
 * Applies an inline style to the current selection in a contenteditable element.
 * It wraps the selected text in <span> tags and applies the style.
 * @param {string} styleProp The CSS property to change (e.g., 'color', 'fontSize').
 * @param {string} value The value for the CSS property.
 */
function applyInlineStyleToSelection(styleProp, value) {
    const sel = window.getSelection();
    if (!sel.rangeCount) return;

    let range = sel.getRangeAt(0);

    // This function splits text nodes at the boundaries of the selection range.
    // This is necessary to ensure that styling is only applied to the selected text,
    // not the entire text node.
    function splitTextBoundaries(r) {
        if (r.startContainer.nodeType === 3 && r.startOffset > 0) {
            r.setStart(r.startContainer.splitText(r.startOffset), 0);
        }
        if (r.endContainer.nodeType === 3 && r.endOffset < r.endContainer.length) {
            r.endContainer.splitText(r.endOffset);
        }
    }

    // This function walks the DOM tree within the range and applies a callback
    // to each text node found.
    function walk(node, r, cb) {
        if (node.nodeType === 3) { // It's a text node
            cb(node);
        } else {
            // It's an element node, so we check its children.
            for (let child of Array.from(node.childNodes)) {
                // We only process nodes that are intersected by the selection range.
                if (r.intersectsNode(child)) walk(child, r, cb);
            }
        }
    }

    splitTextBoundaries(range);

    const ancestor = range.commonAncestorContainer;

    // Walk through the selected nodes and apply the style.
    walk(ancestor, range, txt => {
        let span = txt.parentNode;
        // If the text node is not already wrapped in a SPAN, create one.
        if (!span || span.nodeName !== 'SPAN') {
            const newSpan = document.createElement('span');
            span ? span.insertBefore(newSpan, txt) : ancestor.appendChild(newSpan);
            newSpan.appendChild(txt);
            span = newSpan;
        }
        // Apply the style to the wrapper span.
        span.style[styleProp] = value;
    });
}

/**
 * Initializes a rich text editor for a given textarea element.
 * It replaces the textarea with a contenteditable div and a toolbar.
 * @param {HTMLTextAreaElement} textarea The textarea to transform.
 */
function initSnnRichTextEditor(textarea) {
    if (textarea._snnRteActive) return; // Avoid double initialization
    textarea._snnRteActive = true;

    // These variables would typically be localized via wp_localize_script in WordPress.
    const ajaxurl = window.snnRichTextEditorVars?.ajaxUrl || '';
    const snnNonce = window.snnRichTextEditorVars?.nonce || '';

    const container = document.createElement('div');
    container.className = 'snn-rich-text-editor-container';
    container.innerHTML = `
        <div class="snn-rich-text-editor-toolbar">
            <div class="snn-rich-text-editor-toolbar-group">
                <select class="snn-rich-text-editor-block-type snn-rich-text-editor-select">
                    <option value="">Format</option>
                    <option value="p">Paragraph</option>
                    <option value="h1">Heading 1</option>
                    <option value="h2">Heading 2</option>
                    <option value="h3">Heading 3</option>
                    <option value="h4">Heading 4</option>
                    <option value="blockquote">Blockquote</option>
                    <option value="pre">Preformatted</option>
                </select>
            </div>
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

    // --- Undo/Redo History Management ---
    let history = [editor.innerHTML];
    let historyIndex = 0;

    // Saves the current state of the editor to the history stack
    const saveState = () => {
        const currentHtml = editor.innerHTML;
        // Don't save if the content hasn't changed from the last state
        if (history[historyIndex] === currentHtml) return;

        // If we undo and then make a change, we want to erase the "future" redo history
        if (historyIndex < history.length - 1) {
            history = history.slice(0, historyIndex + 1);
        }
        history.push(currentHtml);
        historyIndex = history.length - 1;
    };
    
    const undo = () => {
        if (historyIndex > 0) {
            historyIndex--;
            editor.innerHTML = history[historyIndex];
            sync(); // Sync with textarea after undoing
        }
    };

    const redo = () => {
        if (historyIndex < history.length - 1) {
            historyIndex++;
            editor.innerHTML = history[historyIndex];
            sync(); // Sync with textarea after redoing
        }
    };
    // --- End of Undo/Redo ---

    // Syncs the contenteditable div's content back to the original textarea.
    const sync = () => textarea.value = editor.innerHTML;

    // Debounce utility to prevent a function from firing too frequently.
    const debounce = (func, timeout = 500) => {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => { func.apply(this, args); }, timeout);
        };
    };

    // A debounced version of saveState to use with the 'input' event.
    const debouncedSaveState = debounce(saveState);

    editor.addEventListener('paste', e => {
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData('text/plain');
        const html = text.split(/\n+/).map(l => l.trim() ? `<p>${l.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</p>` : '').join('');
        document.execCommand('insertHTML', false, html);
        saveState(); // Save state immediately after paste
    });

    editor.addEventListener('keydown', e => {
        // Handle Ctrl+Z for Undo
        if (e.ctrlKey && e.key === 'z') {
            e.preventDefault();
            undo();
        }
        // Handle Ctrl+Y for Redo
        else if (e.ctrlKey && e.key === 'y') {
            e.preventDefault();
            redo();
        }
        // Handle Enter key to insert a paragraph
        else if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            document.execCommand('insertParagraph');
            saveState(); // Save state after inserting paragraph
        }
    });

    container.querySelectorAll('.snn-rich-text-editor-btn[data-command]').forEach(btn => {
        btn.onmousedown = e => e.preventDefault(); // Prevents loss of selection
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
            saveState();
            sync();
        };
    });

    // Block type dropdown handler (Format: p, h1, h2, h3, h4, blockquote, pre)
    container.querySelector('.snn-rich-text-editor-block-type').onchange = e => {
        const v = e.target.value;
        if (!v) return;
        document.execCommand('formatBlock', false, v === 'p' ? 'P' : v.toUpperCase());
        e.target.value = ''; // Reset dropdown
        saveState(); sync();
    };

    // Event listeners for toolbar controls that apply inline styles.
    container.querySelector('.snn-rich-text-editor-font-size').onchange = e => {
        const v = e.target.value;
        if (!v) return;
        applyInlineStyleToSelection('fontSize', v);
        e.target.value = ''; // Reset dropdown
        saveState(); sync();
    };
    container.querySelector('.snn-rich-text-editor-text-color').oninput = e => {
        applyInlineStyleToSelection('color', e.target.value);
        saveState(); sync();
    };
    container.querySelector('.snn-rich-text-editor-bg-color').oninput = e => {
        applyInlineStyleToSelection('backgroundColor', e.target.value);
        saveState(); sync();
    };

    // Save state on any input, but debounced to avoid saving on every keystroke.
    editor.addEventListener('input', () => {
        sync();
        debouncedSaveState();
    });
    
    // --- Image Tools Logic ---
    let selectedImage = null;
    const imageTools = container.querySelector('.snn-rich-text-editor-image-tools');
    const alignBtns = imageTools.querySelectorAll('.snn-rich-text-editor-btn[data-align]');
    const widthBtns = imageTools.querySelectorAll('.snn-rich-text-editor-btn[data-width]');

    editor.addEventListener('click', e => {
        const img = e.target.closest('img');
        if (img) {
            if (selectedImage) selectedImage.classList.remove('snn-selected-image');
            selectedImage = img;
            img.classList.add('snn-selected-image');
            imageTools.style.display = 'flex';
            alignBtns.forEach(b => b.classList.toggle('active', img.classList.contains('snn-img-align-' + b.dataset.align)));
        } else if (selectedImage) {
            selectedImage.classList.remove('snn-selected-image');
            selectedImage = null;
            imageTools.style.display = 'none';
        }
    });

    alignBtns.forEach(btn => {
        btn.onmousedown = e => e.preventDefault();
        btn.onclick = e => {
            e.preventDefault();
            if (!selectedImage) return;
            selectedImage.classList.remove('snn-img-align-left', 'snn-img-align-center', 'snn-img-align-right', 'snn-img-align-none');
            selectedImage.classList.add('snn-img-align-' + btn.dataset.align);
            alignBtns.forEach(b => b.classList.toggle('active', b === btn));
            saveState(); sync();
        };
    });

    widthBtns.forEach(btn => {
        btn.onmousedown = e => e.preventDefault();
        btn.onclick = e => {
            e.preventDefault();
            if (!selectedImage) return;
            selectedImage.style.width = btn.dataset.width;
            selectedImage.removeAttribute('height'); // Maintain aspect ratio
            saveState(); sync();
        };
    });
}

// Auto-initialize all textareas with the class 'snn-rich-text-editor'
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('textarea.snn-rich-text-editor').forEach(initSnnRichTextEditor);
});
