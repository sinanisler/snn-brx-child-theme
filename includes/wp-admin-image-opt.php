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
    __( 'Optimize Media', 'snn' ),        // Page title
    __( 'Optimize Media', 'snn' ),        // Menu title
    'upload_files',                         // Capability
    'snn-image-optimization',               // Menu slug
    'snn_image_optimization_page'           // Callback function
  );
}

// Render the image optimization page
function snn_image_optimization_page() {
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'upload';
    ?>
    <div class="wrap">
        <h1><?php _e( 'Image Optimization', 'snn' ); ?></h1>
        
        <nav class="nav-tab-wrapper">
            <a href="?page=snn-image-optimization&tab=upload" class="nav-tab <?php echo $active_tab === 'upload' ? 'nav-tab-active' : ''; ?>">
                <?php _e('Upload Optimized Media', 'snn'); ?>
            </a>
            <a href="?page=snn-image-optimization&tab=existing" class="nav-tab <?php echo $active_tab === 'existing' ? 'nav-tab-active' : ''; ?>">
                <?php _e('Optimize Existing Media', 'snn'); ?>
            </a>
            <a href="?page=snn-image-optimization&tab=settings" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                <?php _e('History & Settings', 'snn'); ?>
            </a>
        </nav>
        
        <div class="tab-content" style="margin-top: 20px;">
            <?php
            switch ($active_tab) {
                case 'existing':
                    snn_render_optimize_existing_media_tab();
                    break;
                case 'settings':
                    snn_render_optimization_settings_tab();
                    break;
                case 'upload':
                default:
                    echo '<p class="description">' . __('Optimize, convert, and resize images before adding them to your media library.', 'snn') . '</p>';
                    snn_render_wp_admin_image_optimization_section();
                    break;
            }
            ?>
        </div>
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

// ============================================
// OPTIMIZE EXISTING MEDIA TAB
// ============================================

function snn_render_optimize_existing_media_tab() {
    ?>
    <div class="snn-existing-media-optimizer">
        <p class="description"><?php _e('Convert existing JPG and PNG images in your media library to WebP format. Original images are preserved and can be restored anytime.', 'snn'); ?></p>
        
        <div class="optimization-controls" style="margin: 20px 0; padding: 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px;">
            <h3 style="margin-top: 0;"><?php _e('Optimization Settings', 'snn'); ?></h3>
            
            <div class="form-field" style="margin-bottom: 15px;">
                <label for="snn_existing_quality" style="display: block; font-weight: 500; margin-bottom: 5px;">
                    <?php _e('WebP Quality (0-1):', 'snn'); ?>
                </label>
                <input type="number" id="snn_existing_quality" min="0" max="1" step="0.01" value="0.85" style="width: 100px;">
                <p class="description"><?php _e('Lower values = smaller files, higher values = better quality.', 'snn'); ?></p>
            </div>
            
            <div class="button-group" style="margin-top: 20px;">
                <button type="button" id="snn_scan_images" class="button button-primary">
                    <?php _e('Scan for Unoptimized Images', 'snn'); ?>
                </button>
                <button type="button" id="snn_optimize_selected" class="button button-secondary" style="display: none;">
                    <?php _e('Optimize Selected Images', 'snn'); ?>
                </button>
            </div>
            
            <div id="snn_optimization_progress" style="display: none; margin-top: 20px;">
                <div style="background: #f0f0f1; border-radius: 3px; overflow: hidden; border: 1px solid #c3c4c7;">
                    <div id="snn_opt_progress_bar" style="height: 8px; background-color: #2271b1; width: 0%; transition: width 0.3s;"></div>
                </div>
                <p id="snn_opt_progress_text" style="text-align: center; margin-top: 8px; color: #2271b1; font-weight: 500;"></p>
            </div>
        </div>
        
        <div id="snn_images_list_container" style="display: none;">
            <div style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                <label style="display: flex; align-items: center; gap: 5px;">
                    <input type="checkbox" id="snn_select_all_images">
                    <?php _e('Select All', 'snn'); ?>
                </label>
                <span id="snn_selected_count" style="color: #646970;"></span>
            </div>
            
            <table class="wp-list-table widefat fixed striped" id="snn_images_table">
                <thead>
                    <tr>
                        <th style="width: 40px;"><input type="checkbox" id="snn_select_all_header"></th>
                        <th style="width: 80px;"><?php _e('Thumbnail', 'snn'); ?></th>
                        <th><?php _e('File Name', 'snn'); ?></th>
                        <th style="width: 100px;"><?php _e('Type', 'snn'); ?></th>
                        <th style="width: 100px;"><?php _e('Size', 'snn'); ?></th>
                        <th style="width: 120px;"><?php _e('Status', 'snn'); ?></th>
                    </tr>
                </thead>
                <tbody id="snn_images_tbody">
                </tbody>
            </table>
        </div>
        
        <div id="snn_message_area"></div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        let scannedImages = [];
        
        function showMessage(message, type) {
            const colors = {
                success: { bg: '#f0f6fc', border: '#00a32a', text: '#00a32a' },
                error: { bg: '#fcf0f1', border: '#d63638', text: '#d63638' },
                info: { bg: '#f0f6fc', border: '#2271b1', text: '#2271b1' }
            };
            const c = colors[type] || colors.info;
            $('#snn_message_area').html(`<div style="padding: 12px 16px; border-radius: 3px; margin-top: 16px; border-left: 4px solid ${c.border}; background: ${c.bg}; color: ${c.text}; font-weight: 500;">${message}</div>`);
        }
        
        function updateSelectedCount() {
            const count = $('.snn-image-checkbox:checked').length;
            const total = scannedImages.length;
            $('#snn_selected_count').text(count + ' / ' + total + ' <?php _e('selected', 'snn'); ?>');
            $('#snn_optimize_selected').toggle(count > 0);
        }
        
        $('#snn_scan_images').on('click', function() {
            const $btn = $(this);
            $btn.prop('disabled', true).text('<?php _e('Scanning...', 'snn'); ?>');
            $('#snn_message_area').empty();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'snn_scan_unoptimized_images',
                    nonce: '<?php echo wp_create_nonce('snn_optimize_existing_nonce'); ?>'
                },
                success: function(response) {
                    $btn.prop('disabled', false).text('<?php _e('Scan for Unoptimized Images', 'snn'); ?>');
                    
                    if (response.success && response.data.images.length > 0) {
                        scannedImages = response.data.images;
                        renderImagesTable(scannedImages);
                        $('#snn_images_list_container').show();
                        showMessage('<?php _e('Found', 'snn'); ?> ' + scannedImages.length + ' <?php _e('images to optimize.', 'snn'); ?>', 'success');
                    } else if (response.success) {
                        $('#snn_images_list_container').hide();
                        showMessage('<?php _e('No unoptimized JPG or PNG images found.', 'snn'); ?>', 'info');
                    } else {
                        showMessage(response.data || '<?php _e('Error scanning images.', 'snn'); ?>', 'error');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text('<?php _e('Scan for Unoptimized Images', 'snn'); ?>');
                    showMessage('<?php _e('Error scanning images.', 'snn'); ?>', 'error');
                }
            });
        });
        
        function renderImagesTable(images) {
            const $tbody = $('#snn_images_tbody');
            $tbody.empty();
            
            images.forEach(function(img) {
                const row = `<tr data-id="${img.id}">
                    <td><input type="checkbox" class="snn-image-checkbox" value="${img.id}"></td>
                    <td><img src="${img.thumbnail}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 3px;"></td>
                    <td><a href="${img.url}" target="_blank">${img.filename}</a></td>
                    <td>${img.mime_type}</td>
                    <td>${img.size}</td>
                    <td><span class="snn-status">${img.optimized ? '<?php _e('Optimized', 'snn'); ?>' : '<?php _e('Not optimized', 'snn'); ?>'}</span></td>
                </tr>`;
                $tbody.append(row);
            });
            
            updateSelectedCount();
        }
        
        $(document).on('change', '.snn-image-checkbox, #snn_select_all_images, #snn_select_all_header', function() {
            if ($(this).is('#snn_select_all_images, #snn_select_all_header')) {
                $('.snn-image-checkbox').prop('checked', $(this).prop('checked'));
                $('#snn_select_all_images, #snn_select_all_header').prop('checked', $(this).prop('checked'));
            }
            updateSelectedCount();
        });
        
        $('#snn_optimize_selected').on('click', async function() {
            const selectedIds = $('.snn-image-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
            
            if (selectedIds.length === 0) {
                showMessage('<?php _e('Please select at least one image.', 'snn'); ?>', 'error');
                return;
            }
            
            const quality = parseFloat($('#snn_existing_quality').val()) || 0.85;
            const $btn = $(this);
            $btn.prop('disabled', true);
            $('#snn_scan_images').prop('disabled', true);
            $('#snn_optimization_progress').show();
            
            let successCount = 0;
            let errorCount = 0;
            
            for (let i = 0; i < selectedIds.length; i++) {
                const id = selectedIds[i];
                const progress = ((i + 1) / selectedIds.length) * 100;
                $('#snn_opt_progress_bar').css('width', progress + '%');
                $('#snn_opt_progress_text').text('<?php _e('Optimizing', 'snn'); ?> ' + (i + 1) + ' / ' + selectedIds.length + '...');
                
                try {
                    const response = await $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'snn_optimize_single_image',
                            attachment_id: id,
                            quality: quality,
                            nonce: '<?php echo wp_create_nonce('snn_optimize_existing_nonce'); ?>'
                        }
                    });
                    
                    if (response.success) {
                        successCount++;
                        $('tr[data-id="' + id + '"] .snn-status').text('<?php _e('Optimized', 'snn'); ?>').css('color', '#00a32a');
                    } else {
                        errorCount++;
                    }
                } catch (e) {
                    errorCount++;
                }
            }
            
            $btn.prop('disabled', false);
            $('#snn_scan_images').prop('disabled', false);
            $('#snn_opt_progress_text').text('<?php _e('Complete!', 'snn'); ?>');
            
            setTimeout(function() {
                $('#snn_optimization_progress').hide();
            }, 2000);
            
            showMessage('<?php _e('Optimization complete:', 'snn'); ?> ' + successCount + ' <?php _e('succeeded', 'snn'); ?>' + (errorCount > 0 ? ', ' + errorCount + ' <?php _e('failed', 'snn'); ?>' : ''), successCount > 0 ? 'success' : 'error');
        });
    });
    </script>
    <?php
}

// ============================================
// SETTINGS & HISTORY TAB
// ============================================

function snn_render_optimization_settings_tab() {
    ?>
    <div class="snn-optimization-settings">
        <h2><?php _e('Optimization History & Rollback', 'snn'); ?></h2>
        <p class="description"><?php _e('View all optimized images and restore originals if needed.', 'snn'); ?></p>
        
        <div class="rollback-controls" style="margin: 20px 0; padding: 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px;">
            <h3 style="margin-top: 0; color: #d63638;"><?php _e('Bulk Restore', 'snn'); ?></h3>
            <p class="description"><?php _e('Restore selected or all images back to their original state.', 'snn'); ?></p>
            
            <div class="button-group" style="margin-top: 15px; display: flex; gap: 10px;">
                <button type="button" id="snn_restore_selected" class="button button-secondary">
                    <?php _e('Restore Selected', 'snn'); ?>
                </button>
                <button type="button" id="snn_restore_all" class="button" style="color: #d63638; border-color: #d63638;">
                    <?php _e('Restore All Originals', 'snn'); ?>
                </button>
            </div>
        </div>
        
        <div id="snn_restore_progress" style="display: none; margin: 20px 0;">
            <div style="background: #f0f0f1; border-radius: 3px; overflow: hidden; border: 1px solid #c3c4c7;">
                <div id="snn_restore_progress_bar" style="height: 8px; background-color: #d63638; width: 0%; transition: width 0.3s;"></div>
            </div>
            <p id="snn_restore_progress_text" style="text-align: center; margin-top: 8px; color: #d63638; font-weight: 500;"></p>
        </div>
        
        <div class="history-list" style="margin-top: 20px;">
            <div style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                <label style="display: flex; align-items: center; gap: 5px;">
                    <input type="checkbox" id="snn_select_all_history">
                    <?php _e('Select All', 'snn'); ?>
                </label>
                <span id="snn_history_selected_count" style="color: #646970;"></span>
                <button type="button" id="snn_refresh_history" class="button button-small"><?php _e('Refresh List', 'snn'); ?></button>
            </div>
            
            <table class="wp-list-table widefat fixed striped" id="snn_history_table">
                <thead>
                    <tr>
                        <th style="width: 40px;"><input type="checkbox" id="snn_select_all_history_header"></th>
                        <th style="width: 80px;"><?php _e('Thumbnail', 'snn'); ?></th>
                        <th><?php _e('File Name', 'snn'); ?></th>
                        <th><?php _e('Original URL', 'snn'); ?></th>
                        <th><?php _e('Current URL', 'snn'); ?></th>
                        <th style="width: 150px;"><?php _e('Actions', 'snn'); ?></th>
                    </tr>
                </thead>
                <tbody id="snn_history_tbody">
                </tbody>
            </table>
            
            <p id="snn_no_history" style="display: none; text-align: center; padding: 40px; color: #646970;">
                <?php _e('No optimized images found.', 'snn'); ?>
            </p>
        </div>
        
        <div id="snn_history_message_area"></div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        let historyItems = [];
        
        function showHistoryMessage(message, type) {
            const colors = {
                success: { bg: '#f0f6fc', border: '#00a32a', text: '#00a32a' },
                error: { bg: '#fcf0f1', border: '#d63638', text: '#d63638' },
                info: { bg: '#f0f6fc', border: '#2271b1', text: '#2271b1' }
            };
            const c = colors[type] || colors.info;
            $('#snn_history_message_area').html(`<div style="padding: 12px 16px; border-radius: 3px; margin-top: 16px; border-left: 4px solid ${c.border}; background: ${c.bg}; color: ${c.text}; font-weight: 500;">${message}</div>`);
        }
        
        function updateHistorySelectedCount() {
            const count = $('.snn-history-checkbox:checked').length;
            const total = historyItems.length;
            $('#snn_history_selected_count').text(count + ' / ' + total + ' <?php _e('selected', 'snn'); ?>');
        }
        
        function loadHistory() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'snn_get_optimization_history',
                    nonce: '<?php echo wp_create_nonce('snn_optimize_existing_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        historyItems = response.data;
                        renderHistoryTable(historyItems);
                        $('#snn_history_table').show();
                        $('#snn_no_history').hide();
                    } else {
                        historyItems = [];
                        $('#snn_history_table').hide();
                        $('#snn_no_history').show();
                    }
                },
                error: function() {
                    showHistoryMessage('<?php _e('Error loading history.', 'snn'); ?>', 'error');
                }
            });
        }
        
        function renderHistoryTable(items) {
            const $tbody = $('#snn_history_tbody');
            $tbody.empty();
            
            items.forEach(function(item) {
                const row = `<tr data-id="${item.id}">
                    <td><input type="checkbox" class="snn-history-checkbox" value="${item.id}"></td>
                    <td><img src="${item.thumbnail}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 3px;"></td>
                    <td>${item.filename}</td>
                    <td><a href="${item.original_url}" target="_blank" style="font-size: 11px; word-break: break-all;">${item.original_url}</a></td>
                    <td><a href="${item.current_url}" target="_blank" style="font-size: 11px; word-break: break-all;">${item.current_url}</a></td>
                    <td>
                        <button type="button" class="button button-small snn-restore-single" data-id="${item.id}">
                            <?php _e('Restore', 'snn'); ?>
                        </button>
                    </td>
                </tr>`;
                $tbody.append(row);
            });
            
            updateHistorySelectedCount();
        }
        
        // Load history on page load
        loadHistory();
        
        $('#snn_refresh_history').on('click', loadHistory);
        
        $(document).on('change', '.snn-history-checkbox, #snn_select_all_history, #snn_select_all_history_header', function() {
            if ($(this).is('#snn_select_all_history, #snn_select_all_history_header')) {
                $('.snn-history-checkbox').prop('checked', $(this).prop('checked'));
                $('#snn_select_all_history, #snn_select_all_history_header').prop('checked', $(this).prop('checked'));
            }
            updateHistorySelectedCount();
        });
        
        // Restore single image
        $(document).on('click', '.snn-restore-single', function() {
            const id = $(this).data('id');
            const $btn = $(this);
            
            if (!confirm('<?php _e('Are you sure you want to restore this image to its original?', 'snn'); ?>')) {
                return;
            }
            
            $btn.prop('disabled', true).text('<?php _e('Restoring...', 'snn'); ?>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'snn_restore_original_image',
                    attachment_id: id,
                    nonce: '<?php echo wp_create_nonce('snn_optimize_existing_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $('tr[data-id="' + id + '"]').fadeOut(300, function() {
                            $(this).remove();
                            historyItems = historyItems.filter(item => item.id != id);
                            if (historyItems.length === 0) {
                                $('#snn_history_table').hide();
                                $('#snn_no_history').show();
                            }
                            updateHistorySelectedCount();
                        });
                        showHistoryMessage('<?php _e('Image restored successfully.', 'snn'); ?>', 'success');
                    } else {
                        $btn.prop('disabled', false).text('<?php _e('Restore', 'snn'); ?>');
                        showHistoryMessage(response.data || '<?php _e('Error restoring image.', 'snn'); ?>', 'error');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text('<?php _e('Restore', 'snn'); ?>');
                    showHistoryMessage('<?php _e('Error restoring image.', 'snn'); ?>', 'error');
                }
            });
        });
        
        // Restore selected
        $('#snn_restore_selected').on('click', async function() {
            const selectedIds = $('.snn-history-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
            
            if (selectedIds.length === 0) {
                showHistoryMessage('<?php _e('Please select at least one image to restore.', 'snn'); ?>', 'error');
                return;
            }
            
            if (!confirm('<?php _e('Are you sure you want to restore', 'snn'); ?> ' + selectedIds.length + ' <?php _e('image(s) to their originals?', 'snn'); ?>')) {
                return;
            }
            
            await restoreImages(selectedIds);
        });
        
        // Restore all
        $('#snn_restore_all').on('click', async function() {
            if (historyItems.length === 0) {
                showHistoryMessage('<?php _e('No images to restore.', 'snn'); ?>', 'info');
                return;
            }
            
            if (!confirm('<?php _e('Are you sure you want to restore ALL', 'snn'); ?> ' + historyItems.length + ' <?php _e('image(s) to their originals? This cannot be undone.', 'snn'); ?>')) {
                return;
            }
            
            const allIds = historyItems.map(item => item.id);
            await restoreImages(allIds);
        });
        
        async function restoreImages(ids) {
            $('#snn_restore_selected, #snn_restore_all').prop('disabled', true);
            $('#snn_restore_progress').show();
            
            let successCount = 0;
            let errorCount = 0;
            
            for (let i = 0; i < ids.length; i++) {
                const id = ids[i];
                const progress = ((i + 1) / ids.length) * 100;
                $('#snn_restore_progress_bar').css('width', progress + '%');
                $('#snn_restore_progress_text').text('<?php _e('Restoring', 'snn'); ?> ' + (i + 1) + ' / ' + ids.length + '...');
                
                try {
                    const response = await $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'snn_restore_original_image',
                            attachment_id: id,
                            nonce: '<?php echo wp_create_nonce('snn_optimize_existing_nonce'); ?>'
                        }
                    });
                    
                    if (response.success) {
                        successCount++;
                        $('tr[data-id="' + id + '"]').remove();
                    } else {
                        errorCount++;
                    }
                } catch (e) {
                    errorCount++;
                }
            }
            
            $('#snn_restore_selected, #snn_restore_all').prop('disabled', false);
            $('#snn_restore_progress_text').text('<?php _e('Complete!', 'snn'); ?>');
            
            setTimeout(function() {
                $('#snn_restore_progress').hide();
                loadHistory();
            }, 2000);
            
            showHistoryMessage('<?php _e('Restore complete:', 'snn'); ?> ' + successCount + ' <?php _e('succeeded', 'snn'); ?>' + (errorCount > 0 ? ', ' + errorCount + ' <?php _e('failed', 'snn'); ?>' : ''), successCount > 0 ? 'success' : 'error');
        }
    });
    </script>
    <?php
}

// ============================================
// AJAX HANDLERS
// ============================================

// Scan for unoptimized images
add_action('wp_ajax_snn_scan_unoptimized_images', 'snn_scan_unoptimized_images');

function snn_scan_unoptimized_images() {
    check_ajax_referer('snn_optimize_existing_nonce', 'nonce');
    
    if (!current_user_can('upload_files')) {
        wp_send_json_error(__('Permission denied.', 'snn'));
    }
    
    $args = array(
        'post_type' => 'attachment',
        'post_mime_type' => array('image/jpeg', 'image/png'),
        'posts_per_page' => -1,
        'post_status' => 'inherit',
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => '_snn_optimized',
                'compare' => 'NOT EXISTS'
            ),
            array(
                'key' => '_snn_optimized',
                'value' => '1',
                'compare' => '!='
            )
        )
    );
    
    $attachments = get_posts($args);
    $images = array();
    
    foreach ($attachments as $attachment) {
        $file_path = get_attached_file($attachment->ID);
        $file_size = file_exists($file_path) ? size_format(filesize($file_path)) : __('Unknown', 'snn');
        $thumbnail = wp_get_attachment_image_src($attachment->ID, 'thumbnail');
        
        $images[] = array(
            'id' => $attachment->ID,
            'filename' => basename($file_path),
            'url' => wp_get_attachment_url($attachment->ID),
            'thumbnail' => $thumbnail ? $thumbnail[0] : '',
            'mime_type' => $attachment->post_mime_type,
            'size' => $file_size,
            'optimized' => false
        );
    }
    
    wp_send_json_success(array('images' => $images));
}

// Optimize single image
add_action('wp_ajax_snn_optimize_single_image', 'snn_optimize_single_image');

function snn_optimize_single_image() {
    check_ajax_referer('snn_optimize_existing_nonce', 'nonce');
    
    if (!current_user_can('upload_files')) {
        wp_send_json_error(__('Permission denied.', 'snn'));
    }
    
    $attachment_id = intval($_POST['attachment_id']);
    $quality = isset($_POST['quality']) ? floatval($_POST['quality']) : 0.85;
    
    if ($quality < 0 || $quality > 1) {
        $quality = 0.85;
    }
    
    $file_path = get_attached_file($attachment_id);
    
    if (!file_exists($file_path)) {
        wp_send_json_error(__('File not found.', 'snn'));
    }
    
    $mime_type = get_post_mime_type($attachment_id);
    
    if (!in_array($mime_type, array('image/jpeg', 'image/png'))) {
        wp_send_json_error(__('Invalid file type.', 'snn'));
    }
    
    // Store original URL before any changes
    $original_url = wp_get_attachment_url($attachment_id);
    
    // Create WebP version
    $path_info = pathinfo($file_path);
    $webp_path = $path_info['dirname'] . '/' . $path_info['filename'] . '.webp';
    
    // Load image based on type
    if ($mime_type === 'image/jpeg') {
        $image = @imagecreatefromjpeg($file_path);
    } else {
        $image = @imagecreatefrompng($file_path);
    }
    
    if (!$image) {
        wp_send_json_error(__('Could not load image.', 'snn'));
    }
    
    // Handle transparency for PNG
    if ($mime_type === 'image/png') {
        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);
    }
    
    // Convert to WebP
    $webp_quality = intval($quality * 100);
    $success = @imagewebp($image, $webp_path, $webp_quality);
    imagedestroy($image);
    
    if (!$success || !file_exists($webp_path)) {
        wp_send_json_error(__('Failed to create WebP image.', 'snn'));
    }
    
    // Store original URL in meta BEFORE changing the attachment
    update_post_meta($attachment_id, '_snn_original_url', $original_url);
    update_post_meta($attachment_id, '_snn_original_file', $file_path);
    
    // Update attachment to use WebP
    $upload_dir = wp_upload_dir();
    $webp_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $webp_path);
    
    // Update the attached file path
    update_attached_file($attachment_id, $webp_path);
    
    // Update attachment post
    wp_update_post(array(
        'ID' => $attachment_id,
        'post_mime_type' => 'image/webp',
        'guid' => $webp_url
    ));
    
    // Update attachment metadata
    $metadata = wp_generate_attachment_metadata($attachment_id, $webp_path);
    wp_update_attachment_metadata($attachment_id, $metadata);
    
    // Mark as optimized
    update_post_meta($attachment_id, '_snn_optimized', '1');
    update_post_meta($attachment_id, '_snn_optimized_date', current_time('mysql'));
    
    wp_send_json_success(array(
        'message' => __('Image optimized successfully.', 'snn'),
        'new_url' => $webp_url
    ));
}

// Get optimization history
add_action('wp_ajax_snn_get_optimization_history', 'snn_get_optimization_history');

function snn_get_optimization_history() {
    check_ajax_referer('snn_optimize_existing_nonce', 'nonce');
    
    if (!current_user_can('upload_files')) {
        wp_send_json_error(__('Permission denied.', 'snn'));
    }
    
    $args = array(
        'post_type' => 'attachment',
        'posts_per_page' => -1,
        'post_status' => 'inherit',
        'meta_query' => array(
            array(
                'key' => '_snn_optimized',
                'value' => '1',
                'compare' => '='
            )
        )
    );
    
    $attachments = get_posts($args);
    $history = array();
    
    foreach ($attachments as $attachment) {
        $original_url = get_post_meta($attachment->ID, '_snn_original_url', true);
        $current_url = wp_get_attachment_url($attachment->ID);
        $thumbnail = wp_get_attachment_image_src($attachment->ID, 'thumbnail');
        $file_path = get_attached_file($attachment->ID);
        
        if ($original_url) {
            $history[] = array(
                'id' => $attachment->ID,
                'filename' => basename($file_path),
                'original_url' => $original_url,
                'current_url' => $current_url,
                'thumbnail' => $thumbnail ? $thumbnail[0] : '',
                'optimized_date' => get_post_meta($attachment->ID, '_snn_optimized_date', true)
            );
        }
    }
    
    wp_send_json_success($history);
}

// Restore original image
add_action('wp_ajax_snn_restore_original_image', 'snn_restore_original_image');

function snn_restore_original_image() {
    check_ajax_referer('snn_optimize_existing_nonce', 'nonce');
    
    if (!current_user_can('upload_files')) {
        wp_send_json_error(__('Permission denied.', 'snn'));
    }
    
    $attachment_id = intval($_POST['attachment_id']);
    
    $original_file = get_post_meta($attachment_id, '_snn_original_file', true);
    $original_url = get_post_meta($attachment_id, '_snn_original_url', true);
    
    if (!$original_file || !file_exists($original_file)) {
        wp_send_json_error(__('Original file not found.', 'snn'));
    }
    
    // Get current WebP file path to delete later
    $current_file = get_attached_file($attachment_id);
    
    // Determine original mime type
    $extension = strtolower(pathinfo($original_file, PATHINFO_EXTENSION));
    $mime_type = ($extension === 'png') ? 'image/png' : 'image/jpeg';
    
    // Update the attached file path back to original
    update_attached_file($attachment_id, $original_file);
    
    // Update attachment post
    wp_update_post(array(
        'ID' => $attachment_id,
        'post_mime_type' => $mime_type,
        'guid' => $original_url
    ));
    
    // Regenerate metadata for original
    $metadata = wp_generate_attachment_metadata($attachment_id, $original_file);
    wp_update_attachment_metadata($attachment_id, $metadata);
    
    // Remove optimization meta
    delete_post_meta($attachment_id, '_snn_optimized');
    delete_post_meta($attachment_id, '_snn_optimized_date');
    delete_post_meta($attachment_id, '_snn_original_url');
    delete_post_meta($attachment_id, '_snn_original_file');
    
    // Delete the WebP file if it exists and is different from original
    if ($current_file && $current_file !== $original_file && file_exists($current_file)) {
        @unlink($current_file);
    }
    
    wp_send_json_success(array(
        'message' => __('Image restored successfully.', 'snn'),
        'url' => $original_url
    ));
}