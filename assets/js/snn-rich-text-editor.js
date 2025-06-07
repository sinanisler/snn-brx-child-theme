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

    if (range.collapsed) return;

    function mergeAdjacentSpans(span, styleProp, value) {
        // Merge with previous sibling
        let prev = span.previousSibling;
        if (prev && prev.nodeType === 1 && prev.tagName === 'SPAN' && prev.style[styleProp] === value) {
            while (span.firstChild) prev.appendChild(span.firstChild);
            span.remove();
            span = prev;
        }
        // Merge with next sibling
        let next = span.nextSibling;
        if (next && next.nodeType === 1 && next.tagName === 'SPAN' && next.style[styleProp] === value) {
            while (next.firstChild) span.appendChild(next.firstChild);
            next.remove();
        }
    }

    // If selection is fully within a text node or simple element, use surroundContents
    if (range.canSurroundContents()) {
        // Check if everything is already in a span with the style, just update style
        let common = range.commonAncestorContainer;
        if (common.nodeType === 3) common = common.parentNode;
        if (common.nodeType === 1 && common.tagName === "SPAN") {
            common.style[styleProp] = value;
        } else {
            const span = document.createElement('span');
            span.style[styleProp] = value;
            range.surroundContents(span);
            mergeAdjacentSpans(span, styleProp, value);
        }
        return;
    }

    // If not, handle each text node within the selection individually
    let textNodes = [];
    let treeWalker = document.createTreeWalker(
        range.commonAncestorContainer,
        NodeFilter.SHOW_TEXT,
        {
            acceptNode: function(node) {
                return range.intersectsNode(node) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
            }
        }
    );
    while (treeWalker.nextNode()) {
        textNodes.push(treeWalker.currentNode);
    }

    textNodes.forEach(node => {
        let nodeRange = document.createRange();
        let start = 0;
        let end = node.length;
        if (node === range.startContainer) start = range.startOffset;
        if (node === range.endContainer) end = range.endOffset;
        if (start === end) return;
        nodeRange.setStart(node, start);
        nodeRange.setEnd(node, end);

        const styledSpan = document.createElement('span');
        styledSpan.style[styleProp] = value;
        nodeRange.surroundContents(styledSpan);
        mergeAdjacentSpans(styledSpan, styleProp, value);
    });
}

// The rest of your code remains EXACTLY the same:

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
