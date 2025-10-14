<?php

// WordPress Admin Image Optimization Page
// Adds a submenu under Media for image optimization

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu item
add_action('admin_menu', 'snn_add_image_optimization_menu');

function snn_add_image_optimization_menu() {
  add_submenu_page(
    'upload.php',                           // Parent slug (Media menu)
    __( 'Image Optimization', 'snn' ),        // Page title
    __( 'Image Optimization', 'snn' ),        // Menu title
    'upload_files',                         // Capability
    'snn-image-optimization',               // Menu slug
    'snn_image_optimization_page'           // Callback function
  );
}

// Render the image optimization page
function snn_image_optimization_page() {
    ?>
    <div class="wrap">
  <h1><?php _e( 'Image Optimization', 'snn' ); ?></h1>
  <p class="description"><?php _e( 'Optimize, convert, and resize images before adding them to your media library.', 'snn' ); ?></p>
        
        <?php snn_render_wp_admin_image_optimization_section(); ?>
    </div>
    <?php
}

// Function to render the image optimization section
function snn_render_wp_admin_image_optimization_section() {
    ?>
    <div class="snn-wp-admin-image-optimize-container">

<script src="<?php echo get_stylesheet_directory_uri(); ?>/assets/js/FileSaver.min.js"></script>
<script src="<?php echo get_stylesheet_directory_uri(); ?>/assets/js/canvas-to-blob.min.js"></script>
<script src="<?php echo get_stylesheet_directory_uri(); ?>/assets/js/jszip.min.js"></script>

<style>
  /* General & App Layout */
  .snn-wp-admin-image-optimize-container .app-container {
    margin: 20px 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    border-radius: 4px;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
  }
  .snn-wp-admin-image-optimize-container .hidden {
    display: none;
  }

  /* Upload Area */
  .snn-wp-admin-image-optimize-container #uploadAreaWrapper {
    margin-bottom: 24px;
  }
  .snn-wp-admin-image-optimize-container #uploadArea {
    border: 2px dashed #c3c4c7;
    border-radius: 4px;
    cursor: pointer;
    background-color: #f6f7f7;
    position: relative;
    min-height: 150px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    transition: border-color 200ms, background-color 200ms;
  }
  .snn-wp-admin-image-optimize-container #uploadArea:hover, 
  .snn-wp-admin-image-optimize-container #uploadArea.drag-over {
    border-color: #2271b1;
    background-color: #f0f6fc;
  }
  .snn-wp-admin-image-optimize-container #uploadAreaInitialContent {
    padding: 24px;
    text-align: center;
  }
  .snn-wp-admin-image-optimize-container .upload-icon {
    height: 48px;
    width: 48px;
    margin: 0 auto 8px auto;
    color: #646970;
  }
  .snn-wp-admin-image-optimize-container .upload-text {
    color: #50575e;
    font-size: 14px;
  }
  .snn-wp-admin-image-optimize-container .upload-text-highlight {
    font-weight: 600;
    color: #2271b1;
  }
  .snn-wp-admin-image-optimize-container .upload-subtext {
    font-size: 13px;
    color: #646970;
    margin-top: 4px;
  }

  /* File Previews */
  .snn-wp-admin-image-optimize-container #selectedFilesPreview {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    padding: 16px;
    overflow-y: auto;
    max-height: 300px;
  }
  .snn-wp-admin-image-optimize-container .preview-item {
    position: relative;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 8px;
    background-color: #fff;
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 128px;
    transition: box-shadow 150ms;
  }
  .snn-wp-admin-image-optimize-container .preview-item:hover {
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.13);
  }
  .snn-wp-admin-image-optimize-container .preview-img {
    width: 96px;
    height: 96px;
    object-fit: contain;
    border-radius: 2px;
    margin-bottom: 4px;
    background-color: #f6f7f7;
  }
  .snn-wp-admin-image-optimize-container .preview-name {
    display: block;
    font-size: 11px;
    color: #50575e;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    width: 100%;
    text-align: center;
  }
  .snn-wp-admin-image-optimize-container .remove-btn {
    position: absolute;
    top: -8px;
    right: -8px;
    background-color: #d63638;
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: bold;
    opacity: 0;
    transition: opacity 150ms;
    border: none;
    cursor: pointer;
  }
  .snn-wp-admin-image-optimize-container .preview-item:hover .remove-btn, 
  .snn-wp-admin-image-optimize-container .remove-btn:focus {
    opacity: 1;
  }
  .snn-wp-admin-image-optimize-container .remove-btn:focus {
     box-shadow: 0 0 0 2px #d63638;
  }
  
  /* Clear All Button */
  .snn-wp-admin-image-optimize-container #clearAllButton {
    margin-top: 8px;
    width: auto;
    color: #d63638;
    background-color: transparent;
    font-weight: 500;
    padding: 8px 12px;
    border-radius: 3px;
    border: 1px solid #d63638;
    transition: color 150ms, background-color 150ms;
    cursor: pointer;
    font-size: 13px;
  }
  .snn-wp-admin-image-optimize-container #clearAllButton:hover {
    color: #fff;
    background-color: #d63638;
  }
  
  /* Form & Inputs */
  .snn-wp-admin-image-optimize-container #imageForm {
    margin-bottom: 24px;
    display: flex;
    flex-direction: column;
    gap: 24px;
  }
  .snn-wp-admin-image-optimize-container .form-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
    align-items: end;
  }
  @media (min-width: 768px) {
    .snn-wp-admin-image-optimize-container .form-grid {
      grid-template-columns: repeat(3, 1fr);
    }
  }
  .snn-wp-admin-image-optimize-container .form-label {
    display: block;
    font-weight: 500;
    color: #1d2327;
    margin-bottom: 4px;
    font-size: 14px;
  }
  .snn-wp-admin-image-optimize-container .form-input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #8c8f94;
    border-radius: 3px;
    transition: border-color 150ms, box-shadow 150ms;
    background: #fff;
    color: #2c3338;
    font-size: 14px;
  }
  .snn-wp-admin-image-optimize-container .form-input:focus {
    outline: none;
    border-color: #2271b1;
    box-shadow: 0 0 0 1px #2271b1;
  }
  .snn-wp-admin-image-optimize-container select.form-input {
    background-color: #fff;
    background-image: url("data:image/svg+xml;charset=US-ASCII,<svg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 20 20'><polygon fill='%23646970' points='6,8 14,8 10,12'/></svg>");
    background-repeat: no-repeat;
    background-position: right 8px center;
    background-size: 16px 16px;
    padding-right: 32px;
    max-width:100%;
  }
  .snn-wp-admin-image-optimize-container #qualityInputContainer.disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }
  
  /* Buttons */
  .snn-wp-admin-image-optimize-container #convertButton {
    width: 100%;
    background-color: #2271b1;
    color: white;
    font-weight: 500;
    padding: 12px 24px;
    border-radius: 3px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: none;
    cursor: pointer;
    transition: background-color 150ms ease-in-out;
    font-size: 14px;
  }
  .snn-wp-admin-image-optimize-container #convertButton:hover {
    background-color: #135e96;
  }
  .snn-wp-admin-image-optimize-container #convertButton:focus {
    outline: 2px solid #2271b1;
    outline-offset: 2px;
  }
  .snn-wp-admin-image-optimize-container #convertButton:disabled {
    opacity: 0.7;
    cursor: wait;
  }

  /* Button Container */
  .snn-wp-admin-image-optimize-container .button-container {
    display: flex;
    gap: 12px;
    width: 100%;
  }
  .snn-wp-admin-image-optimize-container .button-container button {
    flex: 1;
  }

  /* Save to Media Library Button */
  .snn-wp-admin-image-optimize-container #saveToMediaButton {
    background-color: #00a32a;
    color: white;
    font-weight: 500;
    padding: 12px 24px;
    border-radius: 3px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: none;
    cursor: pointer;
    transition: background-color 150ms ease-in-out;
    font-size: 14px;
  }
  .snn-wp-admin-image-optimize-container #saveToMediaButton:hover {
    background-color: #008a20;
  }
  .snn-wp-admin-image-optimize-container #saveToMediaButton:focus {
    outline: 2px solid #00a32a;
    outline-offset: 2px;
  }
  .snn-wp-admin-image-optimize-container #saveToMediaButton:disabled {
    opacity: 0.7;
    cursor: wait;
  }

  /* Progress Bar */
  .snn-wp-admin-image-optimize-container .progress-container {
    width: 100%;
    background-color: #f0f0f1;
    border-radius: 3px;
    overflow: hidden;
    margin-top: 16px;
    display: none;
    border: 1px solid #c3c4c7;
  }
  .snn-wp-admin-image-optimize-container .progress-bar {
    height: 8px;
    background-color: #2271b1;
    transition: width 0.1s ease;
    width: 0%;
  }
  .snn-wp-admin-image-optimize-container .progress-text {
    text-align: center;
    margin-top: 8px;
    font-size: 13px;
    color: #2271b1;
    font-weight: 500;
  }
  .snn-wp-admin-image-optimize-container .spinner {
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top-color: #fff;
    width: 16px;
    height: 16px;
    animation: spin 1s linear infinite;
  }
  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  /* Message Area */
  .snn-wp-admin-image-optimize-container .message {
    font-weight: 500;
    padding: 12px 16px;
    border-radius: 3px;
    margin-top: 16px;
    text-align: left;
    border-left: 4px solid;
    font-size: 14px;
  }
  .snn-wp-admin-image-optimize-container .message.error {
    color: #d63638;
    background-color: #fcf0f1;
    border-left-color: #d63638;
  }
  .snn-wp-admin-image-optimize-container .message.success {
    color: #00a32a;
    background-color: #f0f6fc;
    border-left-color: #00a32a;
  }
  .snn-wp-admin-image-optimize-container .message.info {
    color: #2271b1;
    background-color: #f0f6fc;
    border-left-color: #2271b1;
  }

  /* WordPress admin responsive adjustments */
  @media screen and (max-width: 782px) {
    .snn-wp-admin-image-optimize-container .app-container {
      padding: 15px;
    }
    .snn-wp-admin-image-optimize-container .form-grid {
      grid-template-columns: 1fr;
    }
    .snn-wp-admin-image-optimize-container .button-container {
      flex-direction: column;
    }
  }
</style>

<div class="app-container">
  <div id="uploadAreaWrapper">
    <div id="uploadArea">
      <input type="file" id="imageInput" accept=".png, .jpg, .jpeg, .webp, .jfif" multiple class="hidden" />

      <div id="uploadAreaInitialContent">
        <svg xmlns="http://www.w3.org/2000/svg" class="upload-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
        </svg>
        <p class="upload-text">
          <span class="upload-text-highlight">Click to upload</span> or drag and drop images here.
        </p>
        <p class="upload-subtext">You can also paste images (Ctrl+V or CMD+V).</p>
      </div>

      <div id="selectedFilesPreview" class="hidden"></div>
    </div>
    <button id="clearAllButton" class="hidden">
      Clear All Selections
    </button>
  </div>

  <form id="imageForm">
    <div class="form-grid">
      <div>
        <label for="resizeWidth" class="form-label">Resize Width (px):</label>
        <input type="number" id="resizeWidth" placeholder="Original" class="form-input">
      </div>
      <div>
        <label for="formatSelect" class="form-label">Output Format:</label>
        <select id="formatSelect" class="form-input">
          <option value="image/webp">WebP</option>
          <option value="image/png">PNG</option>
          <option value="image/jpeg">JPG</option>
        </select>
      </div>
      <div id="qualityInputContainer">
        <label for="qualityInput" class="form-label">Quality (0-1):</label>
        <input type="number" id="qualityInput" min="0" max="1" step="0.01" value="0.85" class="form-input">
      </div>
    </div>
    <div class="button-container">
      <button type="submit" id="convertButton">
        <svg xmlns="http://www.w3.org/2000/svg" style="width:20px; height:20px;" viewBox="0 0 20 20" fill="currentColor" id="convertButtonIcon">
          <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" />
          <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z" />
        </svg>
        <span id="convertButtonText">Convert and Optimize</span>
      </button>
      <button type="button" id="saveToMediaButton" class="hidden">
        <svg xmlns="http://www.w3.org/2000/svg" style="width:20px; height:20px;" viewBox="0 0 20 20" fill="currentColor" id="saveToMediaButtonIcon">
          <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
        <span id="saveToMediaButtonText">Save to Media Library</span>
      </button>
    </div>
    <div class="progress-container" id="progressContainer">
      <div class="progress-bar" id="progressBar"></div>
      <div class="progress-text" id="progressText"></div>
    </div>
  </form>

  <div id="messageArea"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const imageInput = document.getElementById('imageInput');
    const uploadArea = document.getElementById('uploadArea');
    const uploadAreaInitialContent = document.getElementById('uploadAreaInitialContent');
    const selectedFilesPreview = document.getElementById('selectedFilesPreview');
    const clearAllButton = document.getElementById('clearAllButton');
    const imageForm = document.getElementById('imageForm');
    const resizeWidthInput = document.getElementById('resizeWidth');
    const formatSelect = document.getElementById('formatSelect');
    const qualityInput = document.getElementById('qualityInput');
    const qualityInputContainer = document.getElementById('qualityInputContainer');
    const messageArea = document.getElementById('messageArea');
    const convertButton = document.getElementById('convertButton');
    const convertButtonText = document.getElementById('convertButtonText');
    const convertButtonIcon = document.getElementById('convertButtonIcon');
    const saveToMediaButton = document.getElementById('saveToMediaButton');
    const saveToMediaButtonText = document.getElementById('saveToMediaButtonText');
    const saveToMediaButtonIcon = document.getElementById('saveToMediaButtonIcon');
    const progressContainer = document.getElementById('progressContainer');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');

    let selectedFiles = [];

    function generateId() {
      return Date.now().toString(36) + Math.random().toString(36).substring(2);
    }

    function showMessage(message, type = 'info') {
      messageArea.innerHTML = '';
      const p = document.createElement('p');
      p.textContent = message;
      p.classList.add('message', type);
      messageArea.appendChild(p);
      setTimeout(() => {
        if (messageArea.contains(p)) {
          messageArea.removeChild(p);
        }
      }, 7000);
    }

    function updateUploadAreaDisplay() {
      if (selectedFiles.length === 0) {
        uploadAreaInitialContent.classList.remove('hidden');
        selectedFilesPreview.classList.add('hidden');
        selectedFilesPreview.innerHTML = '';
        uploadArea.style.display = 'flex';
        clearAllButton.classList.add('hidden');
      } else {
        uploadAreaInitialContent.classList.add('hidden');
        selectedFilesPreview.classList.remove('hidden');
        uploadArea.style.display = 'block';
        clearAllButton.classList.remove('hidden');
      }
    }

    function updateQualityInputState() {
      if (formatSelect.value === 'image/png') {
        qualityInput.disabled = true;
        qualityInputContainer.classList.add('disabled');
        qualityInput.value = '';
        qualityInput.placeholder = 'N/A for PNG';
      } else {
        qualityInput.disabled = false;
        qualityInputContainer.classList.remove('disabled');
        qualityInput.placeholder = '0.0 - 1.0';
        if (!qualityInput.value) {
          qualityInput.value = '0.85';
        }
      }
    }

    updateUploadAreaDisplay();
    updateQualityInputState();

    function renderPreviews() {
      selectedFilesPreview.innerHTML = '';
      if (selectedFiles.length === 0) {
        updateUploadAreaDisplay();
        return;
      }

      selectedFiles.forEach(fileObj => {
        const previewElement = document.createElement('div');
        previewElement.className = 'preview-item';
        previewElement.setAttribute('aria-label', `Preview of ${fileObj.file.name}`);

        const img = document.createElement('img');
        img.src = fileObj.thumbnailUrl;
        img.alt = `Preview of ${fileObj.file.name}`;
        img.className = 'preview-img';
        img.onerror = function() {
          img.src = `https://placehold.co/96x96/f6f7f7/646970?text=Preview N/A`;
          img.alt = `Preview not available for ${fileObj.file.name}`;
        };

        const nameSpan = document.createElement('span');
        nameSpan.textContent = fileObj.file.name;
        nameSpan.className = 'preview-name';

        const removeButton = document.createElement('button');
        removeButton.innerHTML = '&times;';
        removeButton.className = 'remove-btn';
        removeButton.setAttribute('aria-label', `Remove ${fileObj.file.name}`);
        removeButton.dataset.id = fileObj.id;
        removeButton.onclick = (e) => {
          e.stopPropagation();
          removeFile(fileObj.id);
        };

        previewElement.appendChild(img);
        previewElement.appendChild(nameSpan);
        previewElement.appendChild(removeButton);
        selectedFilesPreview.appendChild(previewElement);
      });
      updateUploadAreaDisplay();
    }

    function addFile(file) {
      if (!file.type || !file.type.startsWith('image/')) {
        showMessage(`Skipped non-image file or file with unknown type: ${file.name}`, 'error');
        return;
      }
      if (selectedFiles.some(sf => sf.file.name === file.name && sf.file.size === file.size)) {
        showMessage(`File "${file.name}" is already selected.`, 'info');
        return;
      }

      const id = generateId();
      const thumbnailUrl = URL.createObjectURL(file);

      selectedFiles.push({
        id: id,
        file: file,
        thumbnailUrl: thumbnailUrl
      });
      renderPreviews();
    }

    function handleFiles(files) {
      messageArea.innerHTML = '';
      if (files.length === 0) {
        return;
      }
      Array.from(files).forEach(addFile);
    }

    function removeFile(id) {
      const fileIndex = selectedFiles.findIndex(fileObj => fileObj.id === id);
      if (fileIndex > -1) {
        const fileObj = selectedFiles[fileIndex];
        if (fileObj.thumbnailUrl.startsWith('blob:')) {
          URL.revokeObjectURL(fileObj.thumbnailUrl);
        }
        selectedFiles.splice(fileIndex, 1);
      }
      renderPreviews();
      if (selectedFiles.length === 0) {
        showMessage('All files removed from selection.', 'info');
      }
    }

    function setConvertingState(isConverting) {
      if (isConverting) {
        convertButton.disabled = true;
        convertButtonText.textContent = 'Converting...';
        convertButtonIcon.innerHTML = '<div class="spinner"></div>';
      } else {
        convertButton.disabled = false;
        convertButtonText.textContent = 'Convert and Optimize';
        convertButtonIcon.innerHTML = `
          <svg xmlns="http://www.w3.org/2000/svg" style="width:20px; height:20px;" viewBox="0 0 20 20" fill="currentColor">
          <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" />
          <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z" />
          </svg>`;
      }
    }

    uploadArea.onclick = (e) => {
      if (e.target.closest('button[data-id]')) {
        return;
      }
      imageInput.click();
    };

    clearAllButton.onclick = () => {
      selectedFiles.forEach(fileObj => {
        if (fileObj.thumbnailUrl.startsWith('blob:')) {
          URL.revokeObjectURL(fileObj.thumbnailUrl);
        }
      });
      selectedFiles = [];
      renderPreviews();
      showMessage('All selections cleared.', 'info');
      imageInput.value = null;
      onSelectionChange();
    };

    formatSelect.onchange = updateQualityInputState;

    imageInput.onchange = (e) => {
      handleFiles(e.target.files);
      imageInput.value = null;
      onSelectionChange();
    };

    uploadArea.ondragover = (e) => {
      e.preventDefault();
      uploadArea.classList.add('drag-over');
    };
    uploadArea.ondragleave = () => {
      uploadArea.classList.remove('drag-over');
    };
    uploadArea.ondrop = (e) => {
      e.preventDefault();
      uploadArea.classList.remove('drag-over');
      handleFiles(e.dataTransfer.files);
    };

    document.onpaste = (e) => {
      if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable) {
        return;
      }
      const items = (e.clipboardData || window.clipboardData).items;
      const filesToProcess = [];
      for (let i = 0; i < items.length; i++) {
        if (items[i].type.indexOf("image") !== -1) {
          const blob = items[i].getAsFile();
          if (blob) {
            const type = blob.type || 'image/png';
            const extension = type.split('/')[1] || 'png';
            const fileName = `p-image-${generateId()}.${extension}`;
            const file = new File([blob], fileName, { type: type });
            filesToProcess.push(file);
          }
        }
      }
      if (filesToProcess.length > 0) {
        e.preventDefault();
        handleFiles(filesToProcess);
        showMessage(`${filesToProcess.length} image(s) pasted.`, 'success');
      }
    };

    // Store converted blobs for download after conversion
    let convertedBlobs = [];
    let conversionDone = false;

    function resetDownloadState() {
        convertedBlobs = [];
        conversionDone = false;
        convertButtonText.textContent = 'Convert and Optimize';
        convertButton.disabled = false;
        saveToMediaButton.classList.add('hidden');
        progressContainer.style.display = 'none';
    }

    function onSelectionChange() {
        resetDownloadState();
    }

    // Convert and Optimize on first click, Download on second click
    imageForm.onsubmit = (e) => {
      e.preventDefault();
      messageArea.innerHTML = '';

      if (!conversionDone) {
        // Start conversion
        if (selectedFiles.length === 0) {
          showMessage('Please select one or more images to convert.', 'error');
          return;
        }
        setConvertingState(true);
        const resizeWidth = resizeWidthInput.value ? parseInt(resizeWidthInput.value) : null;
        if (resizeWidth !== null && (isNaN(resizeWidth) || resizeWidth <= 0)) {
            showMessage('Invalid resize width. Please enter a positive number or leave blank.', 'error');
            setConvertingState(false);
            return;
        }
        const format = formatSelect.value;
        let finalQuality;
        if (format === 'image/png') {
          finalQuality = undefined;
        } else {
          let parsedQuality = parseFloat(qualityInput.value);
          if (isNaN(parsedQuality) || parsedQuality < 0 || parsedQuality > 1) {
            showMessage('Invalid quality value. Using default. Please enter a number between 0.0 and 1.0.', 'error');
            finalQuality = undefined;
          } else {
            finalQuality = parsedQuality;
          }
        }
        let processedCount = 0;
        let successCount = 0;
        let errorCount = 0;
        let collectedBlobs = [];
        selectedFiles.forEach(fileObj => {
          processAndConvertFile(fileObj.file, resizeWidth, format, finalQuality, (blob, name) => {
            processedCount++;
            if (blob && name) {
              collectedBlobs.push({ name: name, blob: blob });
              successCount++;
            } else {
              errorCount++;
            }
            if (processedCount === selectedFiles.length) {
              setConvertingState(false);
              if (successCount > 0) {
                convertedBlobs = collectedBlobs;
                conversionDone = true;
                convertButtonText.textContent = 'Download';
                convertButton.disabled = false;
                saveToMediaButton.classList.remove('hidden');
                showMessage(`Successfully converted ${successCount} image(s). Click 'Download' to save or 'Save to Media Library'.`, 'success');
              } else {
                convertedBlobs = [];
                conversionDone = false;
                convertButtonText.textContent = 'Convert and Optimize';
                convertButton.disabled = false;
                if (errorCount > 0) {
                  showMessage(`${errorCount} image(s) failed to convert.`, 'error');
                } else {
                  showMessage('Conversion process completed, but no files were processed successfully or failed explicitly.', 'info');
                }
              }
            }
          });
        });
      } else {
        // Download phase
        if (convertedBlobs.length === 0) {
          showMessage('No converted images to download.', 'error');
          return;
        }
        if (convertedBlobs.length > 5) {
          const zip = new JSZip();
          convertedBlobs.forEach(item => {
            zip.file(item.name, item.blob);
          });
          zip.generateAsync({ type: "blob" })
            .then(function(content) {
              saveAs(content, "converted_images.zip");
              showMessage(`Downloaded ${convertedBlobs.length} images as ZIP.`, 'success');
              resetDownloadState();
            })
            .catch(function (err) {
              showMessage(`Error creating ZIP file: ${err.message}.`, 'error');
            });
        } else {
          convertedBlobs.forEach((item, idx) => {
            setTimeout(() => {
              saveAs(item.blob, item.name);
            }, idx * 200);
          });
          showMessage(`Downloaded ${convertedBlobs.length} image(s).`, 'success');
          resetDownloadState();
        }
      }
    };

    function processAndConvertFile(file, targetWidth, format, qualityParam, callback) {
      const reader = new FileReader();
      reader.onload = function (event) {
        convertImage(event.target.result, file.name, targetWidth, format, qualityParam, callback);
      };
      reader.onerror = function() {
        showMessage(`Error reading file: ${file.name}. It might be corrupted or inaccessible.`, 'error');
        callback(null, null);
      }
      reader.readAsDataURL(file);
    }

    function convertImage(imageUrl, originalFileName, targetWidth, format, qualityParam, callback) {
      const img = new Image();
      img.onload = function () {
        const canvas = document.createElement('canvas');
        let scale = 1;

        if (targetWidth && targetWidth > 0 && img.width > 0) {
          if (img.width > targetWidth) {
            scale = targetWidth / img.width;
          }
        }

        canvas.width = Math.max(1, Math.round(img.width * scale));
        canvas.height = Math.max(1, Math.round(img.height * scale));

        const ctx = canvas.getContext('2d');

        if (format === 'image/jpeg') {
            ctx.fillStyle = '#FFFFFF';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
        }

        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

        const toBlobCallback = function(blobResult) {
          if (!blobResult) {
            showMessage(`Error converting ${originalFileName} to ${format}. The image might be too small after resize, corrupted, or the format/quality combination is problematic.`, 'error');
            callback(null, null);
            return;
          }
          const fileNameWithoutExtension = originalFileName.substring(0, originalFileName.lastIndexOf('.')) || originalFileName;
          const extension = format.split('/')[1];
          callback(blobResult, `${fileNameWithoutExtension}.${extension}`);
        };

        const args = [toBlobCallback, format];
        if (qualityParam !== undefined && (format === 'image/jpeg' || format === 'image/webp')) {
          args.push(qualityParam);
        }

        try {
            if (canvas.width === 0 || canvas.height === 0) {
                throw new Error("Canvas dimensions are zero.");
            }
            canvas.toBlob.apply(canvas, args);
        } catch (error) {
            showMessage(`Error during canvas.toBlob for ${originalFileName} (Format: ${format}): ${error.message}. This can happen with very large images, unsupported types, or if canvas dimensions are zero.`, 'error');
            callback(null, null);
        }
      };
      img.onerror = function() {
        showMessage(`Could not load image: ${originalFileName}. It might be corrupted, an unsupported format, or a network issue if it was a data URL from a paste.`, 'error');
        callback(null, null);
      }
      img.src = imageUrl;
    }

    // Save to Media Library functionality
    saveToMediaButton.onclick = async () => {
      if (convertedBlobs.length === 0) {
        showMessage('No converted images to save to media library.', 'error');
        return;
      }

      setSavingState(true);
      showProgress(0, convertedBlobs.length);
      
      let successCount = 0;
      let errorCount = 0;
      
      for (let i = 0; i < convertedBlobs.length; i++) {
        const item = convertedBlobs[i];
        
        try {
          const result = await saveImageToMediaLibrary(item.blob, item.name, i, true);
          
          if (result.success) {
            successCount++;
          } else {
            errorCount++;
            console.error(`Failed to save image ${i + 1}:`, result.error);
          }
        } catch (error) {
          errorCount++;
          console.error(`Failed to save image ${i + 1}:`, error);
        }
        
        updateProgress(i + 1, convertedBlobs.length, successCount, errorCount);
        
        if (i < convertedBlobs.length - 1) {
          await new Promise(resolve => setTimeout(resolve, 100));
        }
      }
      
      setSavingState(false);
      hideProgress();
      
      if (successCount > 0) {
        const message = `Successfully saved ${successCount} image(s) to media library${errorCount > 0 ? ` (${errorCount} failed)` : ''}. Thumbnails are being generated in the background.`;
        showMessage(message, 'success');
        
        // Reset the form after successful save
        resetDownloadState();
        
        // Optionally clear selections
        setTimeout(() => {
          clearAllButton.click();
        }, 2000);
      } else {
        showMessage(`Failed to save images to media library. Please check console for details.`, 'error');
      }
    };

    function setSavingState(isSaving) {
      saveToMediaButton.disabled = isSaving;
      convertButton.disabled = isSaving;
      
      if (isSaving) {
        saveToMediaButtonText.textContent = 'Saving...';
        saveToMediaButtonIcon.innerHTML = '<div class="spinner"></div>';
      } else {
        saveToMediaButtonText.textContent = 'Save to Media Library';
        saveToMediaButtonIcon.innerHTML = `
          <svg xmlns="http://www.w3.org/2000/svg" style="width:20px; height:20px;" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
          </svg>`;
      }
    }

    function showProgress(current, total) {
      progressContainer.style.display = 'block';
      updateProgress(current, total, 0, 0);
    }

    function updateProgress(current, total, successCount, errorCount) {
      const percentage = (current / total) * 100;
      progressBar.style.width = percentage + '%';
      
      if (current === total) {
        progressText.textContent = `Completed! ${successCount} saved${errorCount > 0 ? `, ${errorCount} failed` : ''}`;
      } else {
        progressText.textContent = `Saving ${current}/${total} images... (${successCount} saved${errorCount > 0 ? `, ${errorCount} failed` : ''})`;
      }
    }

    function hideProgress() {
      setTimeout(() => {
        progressContainer.style.display = 'none';
        progressBar.style.width = '0%';
        progressText.textContent = '';
      }, 2000);
    }

    async function saveImageToMediaLibrary(blob, filename, index, skipMetadata = false) {
      return new Promise((resolve) => {
        const formData = new FormData();
        formData.append('action', 'snn_save_optimized_image');
        formData.append('image', blob, filename);
        formData.append('filename', filename);
        formData.append('skip_metadata', skipMetadata ? 'true' : 'false');
        formData.append('nonce', '<?php echo wp_create_nonce('snn_save_image_nonce'); ?>');

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 30000);

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
          method: 'POST',
          body: formData,
          signal: controller.signal
        })
        .then(response => {
          clearTimeout(timeoutId);
          return response.json();
        })
        .then(data => {
          resolve(data);
        })
        .catch(error => {
          clearTimeout(timeoutId);
          if (error.name === 'AbortError') {
            resolve({ success: false, error: 'Request timeout' });
          } else {
            resolve({ success: false, error: error.message });
          }
        });
      });
    }
});
</script>

    </div>
    <?php
}

// Add admin styles for better integration
add_action('admin_head', 'snn_image_optimization_admin_styles');

function snn_image_optimization_admin_styles() {
    $current_screen = get_current_screen();
    if ($current_screen && $current_screen->id === 'media_page_snn-image-optimization') {
        ?>
        <style>
        .wrap {
            margin-right: 20px;
        }
        .wrap h1 {
            margin-bottom: 10px;
        }
        .wrap .description {
            margin-bottom: 20px;
            color: #646970;
        }
        </style>
        <?php
    }
}