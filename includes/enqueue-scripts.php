<?php

add_action('wp_enqueue_scripts', function () {
  if (!bricks_is_builder_main()) {
    wp_enqueue_style('bricks-child', get_stylesheet_uri(), ['bricks-frontend'], filemtime(get_stylesheet_directory() . '/style.css'));
  }
});



// Check if the user is logged in and the URL '?bricks=run'
function add_footer_inline_js_for_logged_users() {
  if (is_user_logged_in() && isset($_GET['bricks']) && $_GET['bricks'] === 'run') {
    add_action('wp_footer', function() {
    ?>
<style>
[data-control-group="_attributes"]:not(:has([value="data-animate"])) #synced-textarea ,
[data-control-group="_attributes"]:not(:has([value="data-animate"])) #highlighted-editor{    display: none;}
.key        { color:#ffe369;       }
.value      { color:#a1deff;    }    
.comma      { color: white; font-weight:900; scale:2; padding:0px 2px;    }
.colon      { color: white; font-weight:900; scale:2; padding:0px 2px;    }
.semicolon  { color: #ff4053; font-weight:900; scale:2; padding:0px 2px;     }
</style>
<script>






// Flag to prevent recursive updates
let isUpdating = false;

// Function to attach the event listener to the input field
function attachInputListener() {
    const inputField = document.querySelector('[data-control-group="_attributes"].open #value');

    if (inputField && !inputField.dataset.listenerAttached) {
        console.log('Initial value:', inputField.value);

        // Create a single contenteditable div for editing and highlighting
        let editableDiv = document.querySelector('#highlighted-editor');
        if (!editableDiv) {
            editableDiv = document.createElement('div');
            editableDiv.id = 'highlighted-editor';
            editableDiv.contentEditable = true;
            editableDiv.style.width = '100%';
            editableDiv.style.height = '150px';
            editableDiv.style.fontSize = '14px';
            editableDiv.style.boxSizing = 'border-box';
            editableDiv.style.padding = '10px';
            editableDiv.style.border = '1px solid #ccc';
            editableDiv.style.overflow = 'auto';
            editableDiv.style.whiteSpace = 'pre-wrap';
            editableDiv.style.wordWrap = 'break-word';
            editableDiv.style.lineHeight = '24px';

            // Insert the editable div after the input field
            inputField.parentElement.appendChild(editableDiv);
        }

        // Sync the initial value and apply highlighting
        editableDiv.innerHTML = syntaxHighlight(inputField.value);

        // Add event listener to sync changes from the input field to the editable div
        inputField.addEventListener('input', () => {
            if (isUpdating) return; // Prevent recursive updates
            isUpdating = true;

            console.log('Input field value changed:', inputField.value);
            editableDiv.innerHTML = syntaxHighlight(inputField.value);

            isUpdating = false;
        });

        // Add event listener to sync changes from the editable div back to the input field
        editableDiv.addEventListener('input', () => {
            if (isUpdating) return; // Prevent recursive updates
            isUpdating = true;

            const plainText = editableDiv.innerText; // Get plain text from contenteditable
            if (inputField.value !== plainText) {
                inputField.value = plainText;
            }

            // Preserve caret position based on character index
            preserveCaret(plainText, () => {
                editableDiv.innerHTML = syntaxHighlight(plainText);
            });

            isUpdating = false;
        });

        // Mark that the listener has been attached
        inputField.dataset.listenerAttached = 'true';
    }
}

// Syntax highlighting function
function syntaxHighlight(text) {
    // Escape HTML
    const escapedText = text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;");

    // Highlight syntax
    return escapedText
        .replace(/([^:\s,;]+):([^,;]+)/g, '<span class="key">$1</span><span class="colon">:</span><span class="value">$2</span>') // Key:Value pairs with colon highlighted
        .replace(/,/g, '<span class="comma">,</span>') // Commas
        .replace(/;/g, '<span class="semicolon">;</span>'); // Semicolons
}

// Utility function to preserve the caret position based on character index
function preserveCaret(text, callback) {
    const selection = window.getSelection();
    if (!selection.rangeCount) {
        callback();
        return;
    }

    // Calculate caret position as character index
    const range = selection.getRangeAt(0);
    const preCaretRange = range.cloneRange();
    preCaretRange.selectNodeContents(document.querySelector('#highlighted-editor'));
    preCaretRange.setEnd(range.endContainer, range.endOffset);
    const caretIndex = preCaretRange.toString().length;

    callback();

    // Restore caret position based on character index
    const newNode = document.querySelector('#highlighted-editor');
    let charCount = 0;
    let nodeStack = [newNode];
    let node, foundStart = false;
    const newRange = document.createRange();

    while (nodeStack.length > 0) {
        node = nodeStack.pop();

        if (node.nodeType === Node.TEXT_NODE) {
            const nextCharCount = charCount + node.textContent.length;
            if (!foundStart && caretIndex <= nextCharCount) {
                newRange.setStart(node, caretIndex - charCount);
                newRange.collapse(true);
                foundStart = true;
                break;
            }
            charCount = nextCharCount;
        } else {
            // Add child nodes to stack in reverse order for correct traversal
            for (let i = node.childNodes.length - 1; i >= 0; i--) {
                nodeStack.push(node.childNodes[i]);
            }
        }
    }

    if (foundStart) {
        selection.removeAllRanges();
        selection.addRange(newRange);
    }
}

// Initial attachment
attachInputListener();

// Create a MutationObserver to monitor changes in the DOM
const observer = new MutationObserver((mutationsList) => {
    for (const mutation of mutationsList) {
        if (mutation.type === 'childList' || mutation.type === 'attributes') {
            attachInputListener();
        }
    }
});

// Start observing the entire document for changes
observer.observe(document.body, {
    childList: true,
    subtree: true,
    attributes: true,
});








</script>
    <?php
    });
  }
}
add_action('wp', 'add_footer_inline_js_for_logged_users');
