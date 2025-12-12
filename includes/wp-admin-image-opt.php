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
                    echo '<div class="notice notice-info inline" style="margin: 15px 0;"><p><strong>⚡ ' . __('Fast Client-Side Processing:', 'snn') . '</strong> ' . __('Images are optimized directly in your browser using your CPU. Keep this tab open during processing for best performance. Even thousands of images will be processed quickly!', 'snn') . '</p></div>';
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
// OPTIMIZE EXISTING MEDIA TAB (IMPROVED - Client-Side @jsquash/webp)
// ============================================

function snn_render_optimize_existing_media_tab() {
    ?>
    <div class="snn-existing-media-optimizer">
        <p class="description"><?php _e('Convert existing JPG and PNG images in your media library to WebP format. Original images are preserved and can be restored anytime.', 'snn'); ?></p>
        
        <div class="notice notice-info inline" style="margin: 15px 0;">
            <p><strong>⚡ <?php _e('Fast Client-Side Processing:', 'snn'); ?></strong> <?php _e('Images are downloaded and optimized directly in your browser using WebAssembly (libwebp). This gives you access to all advanced compression options and is much faster than server-side processing!', 'snn'); ?></p>
            <p><strong>🧠 <?php _e('Smart Adaptive Performance:', 'snn'); ?></strong> <?php _e('The optimizer automatically detects your computer\'s speed and adjusts batch sizes and delays to prevent high CPU usage and freezing. Slower machines get smaller batches with cooling periods between processing.', 'snn'); ?></p>
        </div>
        
        <div class="optimization-controls" style="margin: 20px 0; padding: 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px;">
            <h3 style="margin-top: 0;"><?php _e('Optimization Settings', 'snn'); ?></h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <!-- Quality Setting -->
                <div class="form-field">
                    <label for="snn_existing_quality" style="display: block; font-weight: 500; margin-bottom: 5px;">
                        <?php _e('Quality (0-100):', 'snn'); ?>
                    </label>
                    <input type="number" id="snn_existing_quality" min="0" max="100" step="1" value="80" style="width: 100%;">
                    <p class="description" style="margin-top: 4px;"><?php _e('75-85 for photos, 85-95 for graphics.', 'snn'); ?></p>
                </div>
                
                <!-- Maximum Width Setting -->
                <div class="form-field">
                    <label for="snn_max_width" style="display: block; font-weight: 500; margin-bottom: 5px;">
                        <?php _e('Max Width (px):', 'snn'); ?>
                    </label>
                    <input type="number" id="snn_max_width" min="0" step="1" value="0" style="width: 100%;">
                    <p class="description" style="margin-top: 4px;"><?php _e('0 = no resize. 2000 recommended.', 'snn'); ?></p>
                </div>
                
                <!-- Compression Method -->
                <div class="form-field">
                    <label for="snn_method" style="display: block; font-weight: 500; margin-bottom: 5px;">
                        <?php _e('Method (0-6):', 'snn'); ?>
                    </label>
                    <select id="snn_method" style="width: 100%;">
                        <option value="0"><?php _e('0 - Fastest', 'snn'); ?></option>
                        <option value="2"><?php _e('2 - Fast', 'snn'); ?></option>
                        <option value="4" selected><?php _e('4 - Balanced', 'snn'); ?></option>
                        <option value="6"><?php _e('6 - Best compression', 'snn'); ?></option>
                    </select>
                    <p class="description" style="margin-top: 4px;"><?php _e('Higher = smaller files, slower.', 'snn'); ?></p>
                </div>
                
                <!-- Compression Type -->
                <div class="form-field">
                    <label for="snn_lossless" style="display: block; font-weight: 500; margin-bottom: 5px;">
                        <?php _e('Compression Type:', 'snn'); ?>
                    </label>
                    <select id="snn_lossless" style="width: 100%;">
                        <option value="auto"><?php _e('Auto (smart)', 'snn'); ?></option>
                        <option value="lossy"><?php _e('Lossy (photos)', 'snn'); ?></option>
                        <option value="lossless"><?php _e('Lossless (perfect)', 'snn'); ?></option>
                        <option value="near_lossless"><?php _e('Near-Lossless', 'snn'); ?></option>
                    </select>
                    <p class="description" style="margin-top: 4px;"><?php _e('Auto: lossy for JPG, lossless for PNG.', 'snn'); ?></p>
                </div>
                
                <!-- Near-Lossless Level -->
                <div class="form-field" id="snn_near_lossless_container" style="display: none;">
                    <label for="snn_near_lossless_level" style="display: block; font-weight: 500; margin-bottom: 5px;">
                        <?php _e('Near-Lossless (0-100):', 'snn'); ?>
                    </label>
                    <input type="number" id="snn_near_lossless_level" min="0" max="100" step="1" value="60" style="width: 100%;">
                    <p class="description" style="margin-top: 4px;"><?php _e('60 typical. 0=max, 100=none.', 'snn'); ?></p>
                </div>
                
                <!-- Alpha Quality -->
                <div class="form-field">
                    <label for="snn_alpha_quality" style="display: block; font-weight: 500; margin-bottom: 5px;">
                        <?php _e('Alpha Quality (0-100):', 'snn'); ?>
                    </label>
                    <input type="number" id="snn_alpha_quality" min="0" max="100" step="1" value="100" style="width: 100%;">
                    <p class="description" style="margin-top: 4px;"><?php _e('For PNG transparency. 100=lossless.', 'snn'); ?></p>
                </div>
            </div>
            
            <div style="margin-top: 20px; display: flex; flex-direction: column; gap: 10px;">
                <!-- Skip if Bigger -->
                <label style="display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" id="snn_skip_if_bigger" checked>
                    <span><?php _e('Skip if WebP would be larger than original', 'snn'); ?></span>
                </label>
                
                <!-- Include already optimized -->
                <label style="display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" id="snn_include_optimized">
                    <span><?php _e('Include already optimized images (for re-optimization)', 'snn'); ?></span>
                </label>
            </div>
            
            <div class="button-group" style="margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
                <button type="button" id="snn_scan_images" class="button button-primary">
                    <?php _e('Scan for Unoptimized Images', 'snn'); ?>
                </button>
                <button type="button" id="snn_optimize_all" class="button button-secondary" style="display: none; border: solid 1px #2271b1; color: #2271b1;">
                    <?php _e('Optimize All Unoptimized', 'snn'); ?>
                </button>
                <button type="button" id="snn_optimize_selected" class="button button-secondary" style="display: none; border: solid 1px">
                    <?php _e('Optimize Only Selected Images', 'snn'); ?>
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
            <div style="margin-bottom: 15px; display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <label style="display: flex; align-items: center; gap: 5px;">
                        <input type="checkbox" id="snn_select_all_images">
                        <?php _e('Select All on This Page', 'snn'); ?>
                    </label>
                    <span id="snn_selected_count" style="color: #646970;"></span>
                    <span id="snn_total_size_info" style="color: #00a32a; font-weight: 500;"></span>
                </div>
                
                <!-- Pagination Controls -->
                <div id="snn_pagination_top" style="display: flex; align-items: center; gap: 8px;">
                    <button type="button" id="snn_first_page" class="button button-small">«</button>
                    <button type="button" id="snn_prev_page" class="button button-small">‹</button>
                    <span id="snn_page_info" style="color: #646970; font-size: 13px; min-width: 100px; text-align: center;"></span>
                    <button type="button" id="snn_next_page" class="button button-small">›</button>
                    <button type="button" id="snn_last_page" class="button button-small">»</button>
                </div>
            </div>
            
            <table class="wp-list-table widefat fixed striped" id="snn_images_table">
                <thead>
                    <tr>
                        <th style="width: 40px;"> </th>
                        <th style="width: 60px;"><?php _e('Thumb', 'snn'); ?></th>
                        <th><?php _e('File Name', 'snn'); ?></th>
                        <th style="width: 90px;"><?php _e('Dimensions', 'snn'); ?></th>
                        <th style="width: 60px;"><?php _e('Type', 'snn'); ?></th>
                        <th style="width: 80px;"><?php _e('Size', 'snn'); ?></th>
                        <th style="width: 80px;"><?php _e('New Size', 'snn'); ?></th>
                        <th style="width: 70px;"><?php _e('Saved', 'snn'); ?></th>
                        <th style="width: 90px;"><?php _e('Status', 'snn'); ?></th>
                    </tr>
                </thead>
                <tbody id="snn_images_tbody">
                </tbody>
            </table>
        </div>
        
        <div id="snn_message_area"></div>
    </div>
    
    <script type="module">
    // Import @jsquash libraries from ESM (WebAssembly libwebp)
    import { decode as decodeJpeg } from "https://esm.sh/@jsquash/jpeg@1.4.0";
    import { decode as decodePng } from "https://esm.sh/@jsquash/png@3.0.1";
    import { encode as encodeWebp } from "https://esm.sh/@jsquash/webp@1.4.0";
    
    const $ = jQuery;
    let scannedImages = [];
    let currentPage = 1;
    const perPage = 50;
    let selectedImageIds = new Set(); // Persist selections across pages
    
    // Adaptive Performance System
    let performanceProfile = {
        batchSize: 5,           // Start with 5 images per batch
        delayBetweenBatches: 100,  // ms delay between batches
        delayBetweenImages: 50,    // ms delay between images in a batch
        isMeasured: false,      // Whether we've measured performance yet
        avgProcessingTime: 0,   // Average time per image in ms
        isSlowMachine: false    // Flag for slow machines
    };
    
    // Measure performance on first few images to adapt batch sizes
    function analyzePerformance(processingTimes) {
        if (processingTimes.length < 3) return; // Need at least 3 samples
        
        const avgTime = processingTimes.reduce((a, b) => a + b, 0) / processingTimes.length;
        performanceProfile.avgProcessingTime = avgTime;
        performanceProfile.isMeasured = true;
        
        // Categorize machine speed based on average processing time
        if (avgTime < 200) {
            // Very fast machine: < 0.2s per image
            performanceProfile.batchSize = 25;
            performanceProfile.delayBetweenBatches = 20;
            performanceProfile.delayBetweenImages = 0;
            performanceProfile.isSlowMachine = false;
            console.log('🚀🚀 Very fast machine detected - using maximum batches (25)');
        } else if (avgTime < 500) {
            // Fast machine: 0.2-0.5s per image
            performanceProfile.batchSize = 20;
            performanceProfile.delayBetweenBatches = 30;
            performanceProfile.delayBetweenImages = 0;
            performanceProfile.isSlowMachine = false;
            console.log('🚀 Fast machine detected - using large batches (20)');
        } else if (avgTime < 1500) {
            // Medium machine: 0.5-1.5s per image
            performanceProfile.batchSize = 5;
            performanceProfile.delayBetweenBatches = 100;
            performanceProfile.delayBetweenImages = 50;
            performanceProfile.isSlowMachine = false;
            console.log('⚡ Medium speed machine - using balanced batches');
        } else if (avgTime < 3000) {
            // Slow machine: 1.5-3s per image
            performanceProfile.batchSize = 3;
            performanceProfile.delayBetweenBatches = 200;
            performanceProfile.delayBetweenImages = 100;
            performanceProfile.isSlowMachine = true;
            console.log('🐢 Slow machine detected - using smaller batches with delays');
        } else {
            // Very slow machine: > 3s per image
            performanceProfile.batchSize = 1;
            performanceProfile.delayBetweenBatches = 300;
            performanceProfile.delayBetweenImages = 200;
            performanceProfile.isSlowMachine = true;
            console.log('🐌 Very slow machine detected - processing one at a time');
        }
        
        console.log(`Performance Profile: ${avgTime.toFixed(0)}ms avg, batch size: ${performanceProfile.batchSize}`);
    }
    
    // Delay function for CPU relief
    function delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
    
    // Show/hide near-lossless level
    $('#snn_lossless').on('change', function() {
        $('#snn_near_lossless_container').toggle($(this).val() === 'near_lossless');
    });
    
    function showMessage(message, type) {
        const colors = {
            success: { bg: '#f0f6fc', border: '#00a32a', text: '#00a32a' },
            error: { bg: '#fcf0f1', border: '#d63638', text: '#d63638' },
            info: { bg: '#f0f6fc', border: '#2271b1', text: '#2271b1' },
            warning: { bg: '#fcf9e8', border: '#dba617', text: '#996800' }
        };
        const c = colors[type] || colors.info;
        $('#snn_message_area').html(`<div style="padding: 12px 16px; border-radius: 3px; margin-top: 16px; border-left: 4px solid ${c.border}; background: ${c.bg}; color: ${c.text}; font-weight: 500;">${message}</div>`);
    }
    
    function formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return (bytes / Math.pow(k, i)).toFixed(1) + ' ' + sizes[i];
    }
    
    function updateSelectedCount() {
        const totalSelected = selectedImageIds.size;
        const total = scannedImages.length;
        $('#snn_selected_count').text(`${totalSelected} / ${total} <?php _e('selected', 'snn'); ?>`);
        $('#snn_optimize_selected').toggle(totalSelected > 0);
        
        let totalSize = 0;
        scannedImages.forEach(img => {
            if (selectedImageIds.has(img.id)) {
                totalSize += img.size_bytes || 0;
            }
        });
        
        if (totalSize > 0) {
            $('#snn_total_size_info').text(`(${formatBytes(totalSize)} <?php _e('total', 'snn'); ?>)`);
        } else {
            $('#snn_total_size_info').text('');
        }
    }
    
    function updatePagination() {
        const totalPages = Math.ceil(scannedImages.length / perPage);
        
        $('#snn_page_info').text(`Page ${currentPage} of ${totalPages}`);
        
        $('#snn_first_page').prop('disabled', currentPage === 1);
        $('#snn_prev_page').prop('disabled', currentPage === 1);
        $('#snn_next_page').prop('disabled', currentPage === totalPages || totalPages === 0);
        $('#snn_last_page').prop('disabled', currentPage === totalPages || totalPages === 0);
    }
    
    function goToPage(page) {
        const totalPages = Math.ceil(scannedImages.length / perPage);
        if (page < 1) page = 1;
        if (page > totalPages) page = totalPages;
        
        currentPage = page;
        renderImagesTable(getCurrentPageImages());
        updatePagination();
    }
    
    function getCurrentPageImages() {
        const start = (currentPage - 1) * perPage;
        const end = start + perPage;
        return scannedImages.slice(start, end);
    }
    
    // Pagination button handlers
    $('#snn_first_page').on('click', () => goToPage(1));
    $('#snn_prev_page').on('click', () => goToPage(currentPage - 1));
    $('#snn_next_page').on('click', () => goToPage(currentPage + 1));
    $('#snn_last_page').on('click', () => goToPage(Math.ceil(scannedImages.length / perPage)));
    
    
    // Load a specific page of images from server
    function loadPage(page) {
        const includeOptimized = $('#snn_include_optimized').is(':checked');
        
        return $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'snn_scan_unoptimized_images',
                include_optimized: includeOptimized ? 'true' : 'false',
                page: page,
                per_page: perPage,
                nonce: '<?php echo wp_create_nonce('snn_optimize_existing_nonce'); ?>'
            }
        });
    }
    
    // Load all pages to get all image data
    async function loadAllImages(includeOptimized) {
        try {
            // First request to get total count
            const firstResponse = await loadPage(1);
            
            if (!firstResponse.success || !firstResponse.data) {
                return { success: false, data: 'Error loading images' };
            }
            
            const totalPages = firstResponse.data.total_pages || 1;
            let allImages = [...firstResponse.data.images];
            
            // Load remaining pages in parallel (if more than 1 page)
            if (totalPages > 1) {
                const pagePromises = [];
                for (let p = 2; p <= totalPages; p++) {
                    pagePromises.push(loadPage(p));
                }
                
                const responses = await Promise.all(pagePromises);
                responses.forEach(response => {
                    if (response.success && response.data && response.data.images) {
                        allImages = allImages.concat(response.data.images);
                    }
                });
            }
            
            return {
                success: true,
                data: {
                    images: allImages,
                    total: firstResponse.data.total
                }
            };
        } catch (error) {
            console.error('Error loading images:', error);
            return { success: false, data: 'Network error loading images' };
        }
    }
    
    // Scan for images
    $('#snn_scan_images').on('click', async function() {
        const $btn = $(this);
        const includeOptimized = $('#snn_include_optimized').is(':checked');
        $btn.prop('disabled', true).text('<?php _e('Scanning...', 'snn'); ?>');
        $('#snn_message_area').empty();
        
        const response = await loadAllImages(includeOptimized);
        
        $btn.prop('disabled', false).text('<?php _e('Scan for Unoptimized Images', 'snn'); ?>');
        
        if (response.success && response.data.images.length > 0) {
            scannedImages = response.data.images;
            selectedImageIds.clear();
            currentPage = 1;
            renderImagesTable(getCurrentPageImages());
            updatePagination();
            $('#snn_images_list_container').show();
            $('#snn_optimize_all').show(); // Show the "Optimize All" button
            
            let totalSize = scannedImages.reduce((sum, img) => sum + (img.size_bytes || 0), 0);
            showMessage(`<?php _e('Found', 'snn'); ?> ${scannedImages.length} <?php _e('images', 'snn'); ?> (${formatBytes(totalSize)}).`, 'success');
        } else if (response.success) {
            $('#snn_images_list_container').hide();
            $('#snn_optimize_all').hide();
            showMessage('<?php _e('No unoptimized JPG or PNG images found.', 'snn'); ?>', 'info');
        } else {
            $('#snn_optimize_all').hide();
            showMessage(response.data || '<?php _e('Error scanning images.', 'snn'); ?>', 'error');
        }
    });
    
    function renderImagesTable(images) {
        const $tbody = $('#snn_images_tbody');
        $tbody.empty();
        
        const maxWidth = parseInt($('#snn_max_width').val()) || 0;
        
        images.forEach(function(img) {
            // Use persisted status if available
            let statusText, statusColor, newSizeText, savingsText, savingsColor;
            
            if (img.status === 'done') {
                statusText = '<?php _e('Done', 'snn'); ?>';
                statusColor = '#00a32a';
                newSizeText = formatBytes(img.newSize);
                savingsText = `-${img.savingsPercent}%`;
                savingsColor = '#00a32a';
            } else if (img.status === 'skipped') {
                statusText = '<?php _e('Skipped', 'snn'); ?>';
                statusColor = '#dba617';
                newSizeText = '-';
                savingsText = '0%';
                savingsColor = '#646970';
            } else if (img.status === 'error') {
                statusText = '<?php _e('Error', 'snn'); ?>';
                statusColor = '#d63638';
                newSizeText = '-';
                savingsText = '-';
                savingsColor = '#d63638';
            } else {
                // Default/Pending status
                statusText = img.optimized ? '<?php _e('Optimized', 'snn'); ?>' : '<?php _e('Pending', 'snn'); ?>';
                statusColor = img.optimized ? '#00a32a' : '#646970';
                newSizeText = '-';
                savingsText = '-';
                savingsColor = '#646970';
            }
            
            const dims = img.width && img.height ? `${img.width}×${img.height}` : '-';
            const willResize = maxWidth > 0 && img.width > maxWidth;
            const resizeNote = willResize ? `<br><small style="color:#dba617;">→${maxWidth}px</small>` : '';
            const shortName = img.filename.length > 25 ? img.filename.substring(0,22) + '...' : img.filename;
            const isChecked = selectedImageIds.has(img.id) ? 'checked' : '';
            
            const row = `<tr data-id="${img.id}" data-url="${img.full_url}" data-mime="${img.mime_type}" data-size="${img.size_bytes}" data-filename="${img.filename}">
                <td><input type="checkbox" class="snn-image-checkbox" value="${img.id}" ${isChecked}></td>
                <td><img src="${img.thumbnail}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 3px;"></td>
                <td title="${img.filename}">${shortName}</td>
                <td>${dims}${resizeNote}</td>
                <td>${img.mime_type.replace('image/', '').toUpperCase()}</td>
                <td>${img.size}</td>
                <td class="new-size">${newSizeText}</td>
                <td class="savings" style="color: ${savingsColor};">${savingsText}</td>
                <td><span class="snn-status" style="color: ${statusColor}; font-weight: 500;">${statusText}</span></td>
            </tr>`;
            $tbody.append(row);
        });
        
        // Update "select all on page" checkbox state
        updateSelectAllCheckbox();
        updateSelectedCount();
    }
    
    function updateSelectAllCheckbox() {
        const visibleCheckboxes = $('.snn-image-checkbox');
        const checkedCount = visibleCheckboxes.filter(':checked').length;
        const totalCount = visibleCheckboxes.length;
        
        $('#snn_select_all_images, #snn_select_all_header').prop('checked', totalCount > 0 && checkedCount === totalCount);
    }
    
    $(document).on('change', '.snn-image-checkbox, #snn_select_all_images, #snn_select_all_header', function() {
        if ($(this).is('#snn_select_all_images, #snn_select_all_header')) {
            // Select/deselect all on current page
            const isChecked = $(this).prop('checked');
            $('.snn-image-checkbox').each(function() {
                const id = parseInt($(this).val());
                if (isChecked) {
                    selectedImageIds.add(id);
                } else {
                    selectedImageIds.delete(id);
                }
                $(this).prop('checked', isChecked);
            });
            // Sync both "select all" checkboxes
            $('#snn_select_all_images, #snn_select_all_header').prop('checked', isChecked);
        } else {
            // Individual checkbox changed
            const id = parseInt($(this).val());
            if ($(this).prop('checked')) {
                selectedImageIds.add(id);
            } else {
                selectedImageIds.delete(id);
            }
            updateSelectAllCheckbox();
        }
        updateSelectedCount();
    });
    
    // Get settings
    function getSettings() {
        return {
            quality: parseInt($('#snn_existing_quality').val()) || 82,
            maxWidth: parseInt($('#snn_max_width').val()) || 0,
            method: parseInt($('#snn_method').val()) || 4,
            losslessMode: $('#snn_lossless').val() || 'auto',
            nearLosslessLevel: parseInt($('#snn_near_lossless_level').val()) || 60,
            alphaQuality: parseInt($('#snn_alpha_quality').val()) || 100,
            skipIfBigger: $('#snn_skip_if_bigger').is(':checked')
        };
    }
    
    // Resize ImageData using canvas
    function resizeImageData(imageData, maxWidth) {
        if (maxWidth <= 0 || imageData.width <= maxWidth) {
            return imageData;
        }
        
        const scale = maxWidth / imageData.width;
        const newWidth = maxWidth;
        const newHeight = Math.round(imageData.height * scale);
        
        const srcCanvas = document.createElement('canvas');
        srcCanvas.width = imageData.width;
        srcCanvas.height = imageData.height;
        const srcCtx = srcCanvas.getContext('2d');
        srcCtx.putImageData(imageData, 0, 0);
        
        const dstCanvas = document.createElement('canvas');
        dstCanvas.width = newWidth;
        dstCanvas.height = newHeight;
        const dstCtx = dstCanvas.getContext('2d');
        dstCtx.imageSmoothingEnabled = true;
        dstCtx.imageSmoothingQuality = 'high';
        dstCtx.drawImage(srcCanvas, 0, 0, newWidth, newHeight);
        
        return dstCtx.getImageData(0, 0, newWidth, newHeight);
    }
    
    // Decode image based on type
    async function decodeImage(arrayBuffer, mimeType) {
        // Normalize mime type
        const normalizedMime = mimeType.toLowerCase().trim();
        
        try {
            if (normalizedMime === 'image/jpeg' || normalizedMime === 'image/jpg') {
                return await decodeJpeg(arrayBuffer);
            } else if (normalizedMime === 'image/png') {
                return await decodePng(arrayBuffer);
            }
            throw new Error(`Unsupported mime type: ${mimeType}`);
        } catch (error) {
            // If decoder fails, try to detect format from file signature
            const uint8Array = new Uint8Array(arrayBuffer);
            const signature = Array.from(uint8Array.slice(0, 4));
            
            // PNG signature: 89 50 4E 47
            if (signature[0] === 0x89 && signature[1] === 0x50 && signature[2] === 0x4E && signature[3] === 0x47) {
                console.log(`File signature detected as PNG, but mime type was ${mimeType}. Retrying with PNG decoder...`);
                return await decodePng(arrayBuffer);
            }
            
            // JPEG signature: FF D8 FF
            if (signature[0] === 0xFF && signature[1] === 0xD8 && signature[2] === 0xFF) {
                console.log(`File signature detected as JPEG, but mime type was ${mimeType}. Retrying with JPEG decoder...`);
                return await decodeJpeg(arrayBuffer);
            }
            
            // Re-throw original error if we can't detect format
            throw error;
        }
    }
    
    // Build WebP encode options
    function buildEncodeOptions(settings, mimeType) {
        const options = {
            quality: settings.quality,
            method: settings.method,
            alpha_quality: settings.alphaQuality,
        };
        
        if (settings.losslessMode === 'lossless') {
            options.lossless = 1;
        } else if (settings.losslessMode === 'near_lossless') {
            options.lossless = 1;
            options.near_lossless = settings.nearLosslessLevel;
        } else if (settings.losslessMode === 'auto') {
            options.lossless = (mimeType === 'image/png') ? 1 : 0;
        } else {
            options.lossless = 0;
        }
        
        return options;
    }
    
    // Process single image client-side
    async function processImage(imageUrl, mimeType, originalSize, settings) {
        try {
            // Download
            const response = await fetch(imageUrl);
            if (!response.ok) throw new Error('Download failed');
            const arrayBuffer = await response.arrayBuffer();
            
            // Decode
            let imageData = await decodeImage(arrayBuffer, mimeType);
            
            // Resize if needed
            if (settings.maxWidth > 0 && imageData.width > settings.maxWidth) {
                imageData = resizeImageData(imageData, settings.maxWidth);
            }
            
            // Encode to WebP
            let encodeOptions = buildEncodeOptions(settings, mimeType);
            let webpBuffer = await encodeWebp(imageData, encodeOptions);
            let webpSize = webpBuffer.byteLength;
            
            // If auto mode PNG and lossless is bigger, try lossy
            if (settings.losslessMode === 'auto' && mimeType === 'image/png' && webpSize >= originalSize) {
                encodeOptions.lossless = 0;
                webpBuffer = await encodeWebp(imageData, encodeOptions);
                webpSize = webpBuffer.byteLength;
            }
            
            // Skip if bigger
            if (settings.skipIfBigger && webpSize >= originalSize) {
                return { success: true, skipped: true, originalSize, wouldBeSize: webpSize };
            }
            
            return {
                success: true,
                skipped: false,
                blob: new Blob([webpBuffer], { type: 'image/webp' }),
                originalSize,
                newSize: webpSize,
                savings: originalSize - webpSize
            };
        } catch (error) {
            return { success: false, error: error.message };
        }
    }
    
    // Upload optimized image to server
    async function uploadOptimizedImage(attachmentId, blob, filename) {
        return new Promise((resolve) => {
            const formData = new FormData();
            formData.append('action', 'snn_save_optimized_existing_image');
            formData.append('attachment_id', attachmentId);
            formData.append('image', blob, filename.replace(/\.(jpg|jpeg|png)$/i, '.webp'));
            formData.append('nonce', '<?php echo wp_create_nonce('snn_optimize_existing_nonce'); ?>');
            
            fetch(ajaxurl, { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => resolve(data))
                .catch(e => resolve({ success: false, error: e.message }));
        });
    }
    
    // Main optimization with adaptive batching
    $('#snn_optimize_selected').on('click', async function() {
        const selectedRows = [];
        
        // Get selected images from all pages using selectedImageIds
        scannedImages.forEach(img => {
            if (selectedImageIds.has(img.id)) {
                selectedRows.push({
                    id: img.id,
                    url: img.full_url,
                    mime: img.mime_type,
                    size: img.size_bytes,
                    filename: img.filename
                });
            }
        });
        
        if (selectedRows.length === 0) {
            showMessage('<?php _e('Please select at least one image.', 'snn'); ?>', 'error');
            return;
        }
        
        const settings = getSettings();
        
        if (!confirm(`<?php _e('Optimize', 'snn'); ?> ${selectedRows.length} <?php _e('image(s) using your browser?', 'snn'); ?>`)) {
            return;
        }
        
        await optimizeImagesWithAdaptiveBatching(selectedRows, settings);
    });
    
    // Optimize images with adaptive batching and performance monitoring
    async function optimizeImagesWithAdaptiveBatching(imagesToProcess, settings) {
        const $btnSelected = $('#snn_optimize_selected');
        const $btnAll = $('#snn_optimize_all');
        $btnSelected.prop('disabled', true);
        $btnAll.prop('disabled', true);
        $('#snn_scan_images').prop('disabled', true);
        
        // Show progress bar
        $('#snn_optimization_progress').show();
        
        let successCount = 0, skippedCount = 0, errorCount = 0, totalSaved = 0;
        const processingTimes = []; // Track processing times for first few images
        const startTime = Date.now();
        
        // Reset performance profile for new optimization run
        performanceProfile.isMeasured = false;
        performanceProfile.batchSize = 5; // Start with conservative batch size
        
        for (let i = 0; i < imagesToProcess.length; i++) {
            const item = imagesToProcess[i];
            const imageStartTime = Date.now();
            
            // Update progress bar
            const progress = ((i + 1) / imagesToProcess.length) * 100;
            $('#snn_opt_progress_bar').css('width', progress + '%');
            
            // Show detailed progress with performance info
            let progressInfo = `<?php _e('Processing', 'snn'); ?> ${i + 1}/${imagesToProcess.length}`;
            if (performanceProfile.isMeasured) {
                progressInfo += ` (${performanceProfile.isSlowMachine ? '🐢 ' : '⚡ '}<?php _e('Batch:', 'snn'); ?> ${performanceProfile.batchSize})`;
            }
            progressInfo += `<br><small>${successCount} <?php _e('done', 'snn'); ?>, ${skippedCount} <?php _e('skipped', 'snn'); ?>, ${errorCount} <?php _e('failed', 'snn'); ?> | ${formatBytes(totalSaved)} <?php _e('saved', 'snn'); ?></small>`;
            
            if (performanceProfile.avgProcessingTime > 0) {
                const remainingImages = imagesToProcess.length - i;
                const estimatedSeconds = Math.ceil((remainingImages * performanceProfile.avgProcessingTime) / 1000);
                progressInfo += `<br><small><?php _e('Est. time remaining:', 'snn'); ?> ~${estimatedSeconds}s</small>`;
            }
            
            $('#snn_opt_progress_text').html(progressInfo);
            
            // Find the row in DOM if exists
            const $row = $(`tr[data-id="${item.id}"]`);
            if ($row.length) {
                $row.find('.snn-status').text('<?php _e('Processing...', 'snn'); ?>').css('color', '#2271b1');
            }
            
            try {
                const result = await processImage(item.url, item.mime, item.size, settings);
                
                // Track processing time for first few images
                const imageEndTime = Date.now();
                const processingTime = imageEndTime - imageStartTime;
                
                if (processingTimes.length < 10) {
                    processingTimes.push(processingTime);
                    
                    // Analyze performance after 3, 5, and 10 images
                    if (processingTimes.length === 3 || processingTimes.length === 5 || processingTimes.length === 10) {
                        analyzePerformance(processingTimes);
                    }
                }
                
                if (!result.success) throw new Error(result.error);
                
                if (result.skipped) {
                    skippedCount++;
                    
                    // Update scannedImages array to persist status across pagination
                    const imgIndex = scannedImages.findIndex(img => img.id === item.id);
                    if (imgIndex !== -1) {
                        scannedImages[imgIndex].status = 'skipped';
                        scannedImages[imgIndex].newSize = null;
                        scannedImages[imgIndex].savings = 0;
                    }
                    
                    if ($row.length) {
                        $row.find('.snn-status').text('<?php _e('Skipped', 'snn'); ?>').css('color', '#dba617');
                        $row.find('.new-size').text('-');
                        $row.find('.savings').text('0%').css('color', '#646970');
                    }
                } else {
                    const uploadResult = await uploadOptimizedImage(item.id, result.blob, item.filename);
                    
                    if (!uploadResult.success) throw new Error(uploadResult.error || 'Upload failed');
                    
                    successCount++;
                    totalSaved += result.savings;
                    
                    const pct = Math.round((result.savings / result.originalSize) * 100);
                    
                    // Update scannedImages array to persist status across pagination
                    const imgIndex = scannedImages.findIndex(img => img.id === item.id);
                    if (imgIndex !== -1) {
                        scannedImages[imgIndex].status = 'done';
                        scannedImages[imgIndex].newSize = result.newSize;
                        scannedImages[imgIndex].savings = result.savings;
                        scannedImages[imgIndex].savingsPercent = pct;
                        scannedImages[imgIndex].optimized = true;
                    }
                    
                    if ($row.length) {
                        $row.find('.snn-status').text('<?php _e('Done', 'snn'); ?>').css('color', '#00a32a');
                        $row.find('.new-size').text(formatBytes(result.newSize));
                        $row.find('.savings').text(`-${pct}%`).css('color', '#00a32a');
                    }
                }
                
            } catch (error) {
                errorCount++;
                
                // Update scannedImages array to persist status across pagination
                const imgIndex = scannedImages.findIndex(img => img.id === item.id);
                if (imgIndex !== -1) {
                    scannedImages[imgIndex].status = 'error';
                    scannedImages[imgIndex].newSize = null;
                    scannedImages[imgIndex].savings = 0;
                }
                
                if ($row.length) {
                    $row.find('.snn-status').text('<?php _e('Error', 'snn'); ?>').css('color', '#d63638');
                }
                console.error(`Error: ${item.url}`, error);
            }
            
            // Apply delays based on performance profile
            // Delay between images within a batch
            if (performanceProfile.isMeasured && performanceProfile.delayBetweenImages > 0) {
                await delay(performanceProfile.delayBetweenImages);
            }
            
            // Delay between batches (every N images based on batch size)
            if (performanceProfile.isMeasured && (i + 1) % performanceProfile.batchSize === 0 && i < imagesToProcess.length - 1) {
                await delay(performanceProfile.delayBetweenBatches);
            }
        }
        
        $btnSelected.prop('disabled', false);
        $btnAll.prop('disabled', false);
        $('#snn_scan_images').prop('disabled', false);
        
        const totalTime = ((Date.now() - startTime) / 1000).toFixed(1);
        
        $('#snn_opt_progress_text').html(
            `<?php _e('Complete!', 'snn'); ?> (${totalTime}s)<br><small>${successCount} <?php _e('done', 'snn'); ?>, ${skippedCount} <?php _e('skipped', 'snn'); ?>, ${errorCount} <?php _e('failed', 'snn'); ?> | ${formatBytes(totalSaved)} <?php _e('saved', 'snn'); ?></small>`
        );
        
        setTimeout(() => $('#snn_optimization_progress').hide(), 5000);
        
        const msgType = successCount > 0 ? 'success' : (skippedCount > 0 ? 'warning' : 'error');
        showMessage(
            `<?php _e('Complete:', 'snn'); ?> ${successCount} <?php _e('optimized', 'snn'); ?>, ${skippedCount} <?php _e('skipped', 'snn'); ?>, ${errorCount} <?php _e('failed', 'snn'); ?>. <?php _e('Saved:', 'snn'); ?> ${formatBytes(totalSaved)} (${totalTime}s)`,
            msgType
        );
    }
    
    // Optimize All button handler with adaptive batching
    $('#snn_optimize_all').on('click', async function() {
        if (scannedImages.length === 0) {
            showMessage('<?php _e('No images to optimize. Please scan first.', 'snn'); ?>', 'error');
            return;
        }
        
        const settings = getSettings();
        
        if (!confirm(`<?php _e('Optimize ALL', 'snn'); ?> ${scannedImages.length} <?php _e('unoptimized image(s) using your browser? This may take a while.', 'snn'); ?>`)) {
            return;
        }
        
        // Convert scannedImages to the format expected by optimizeImagesWithAdaptiveBatching
        const imagesToProcess = scannedImages.map(img => ({
            id: img.id,
            url: img.full_url,
            mime: img.mime_type,
            size: img.size_bytes,
            filename: img.filename
        }));
        
        await optimizeImagesWithAdaptiveBatching(imagesToProcess, settings);
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
            
            <div class="button-group" style="margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap;">
                <button type="button" id="snn_restore_selected" class="button button-secondary">
                    <?php _e('Restore Selected', 'snn'); ?>
                </button>
                <button type="button" id="snn_restore_all" class="button" style="color: #d63638; border-color: #d63638;">
                    <?php _e('Restore All Originals', 'snn'); ?>
                </button>
                <button type="button" id="snn_delete_originals" class="button"
                    style="background-color: #d63638; color: #fff; border-color: #d63638;"
                    title="⚠️ WARNING: This will permanently delete all original image files to save disk space. Optimization metadata will be preserved but images cannot be restored to their original format after this action.">
                    <?php _e('Delete Originals to Save Space', 'snn'); ?>
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
            <div style="margin-bottom: 15px; display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <label style="display: flex; align-items: center; gap: 5px;">
                        <input type="checkbox" id="snn_select_all_history">
                        <?php _e('Select All on This Page', 'snn'); ?>
                    </label>
                    <span id="snn_history_selected_count" style="color: #646970;"></span>
                    <button type="button" id="snn_refresh_history" class="button button-small"><?php _e('Refresh List', 'snn'); ?></button>
                </div>
                
                <!-- Pagination Controls for History -->
                <div id="snn_history_pagination" style="display: flex; align-items: center; gap: 8px;">
                    <button type="button" id="snn_history_first_page" class="button button-small">«</button>
                    <button type="button" id="snn_history_prev_page" class="button button-small">‹</button>
                    <span id="snn_history_page_info" style="color: #646970; font-size: 13px; min-width: 100px; text-align: center;"></span>
                    <button type="button" id="snn_history_next_page" class="button button-small">›</button>
                    <button type="button" id="snn_history_last_page" class="button button-small">»</button>
                </div>
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
    
    <style>
        #snn_delete_originals {
            position: relative;
        }
        #snn_delete_originals:hover::after {
            content: attr(title);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            margin-bottom: 8px;
            padding: 12px 16px;
            background-color: #d63638;
            color: #fff;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
            white-space: nowrap;
            max-width: 400px;
            white-space: normal;
            width: max-content;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            line-height: 1.4;
        }
        #snn_delete_originals:hover::before {
            content: '';
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 6px solid transparent;
            border-top-color: #d63638;
            z-index: 1001;
        }
        @media (max-width: 782px) {
            #snn_delete_originals:hover::after {
                left: 0;
                transform: none;
                max-width: 280px;
            }
            #snn_delete_originals:hover::before {
                left: 20px;
                transform: none;
            }
        }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        let historyItems = [];
        let historyCurrentPage = 1;
        const historyPerPage = 50;
        let selectedHistoryIds = new Set(); // Persist selections across pages
        
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
            const totalSelected = selectedHistoryIds.size;
            const total = historyItems.length;
            $('#snn_history_selected_count').text(totalSelected + ' / ' + total + ' <?php _e('selected', 'snn'); ?>');
        }
        
        function updateHistoryPagination() {
            const totalPages = Math.ceil(historyItems.length / historyPerPage);
            
            $('#snn_history_page_info').text(`Page ${historyCurrentPage} of ${totalPages}`);
            
            $('#snn_history_first_page').prop('disabled', historyCurrentPage === 1);
            $('#snn_history_prev_page').prop('disabled', historyCurrentPage === 1);
            $('#snn_history_next_page').prop('disabled', historyCurrentPage === totalPages || totalPages === 0);
            $('#snn_history_last_page').prop('disabled', historyCurrentPage === totalPages || totalPages === 0);
        }
        
        function goToHistoryPage(page) {
            const totalPages = Math.ceil(historyItems.length / historyPerPage);
            if (page < 1) page = 1;
            if (page > totalPages) page = totalPages;
            
            historyCurrentPage = page;
            renderHistoryTable(getCurrentHistoryPageItems());
            updateHistoryPagination();
        }
        
        function getCurrentHistoryPageItems() {
            const start = (historyCurrentPage - 1) * historyPerPage;
            const end = start + historyPerPage;
            return historyItems.slice(start, end);
        }
        
        // Pagination button handlers
        $('#snn_history_first_page').on('click', () => goToHistoryPage(1));
        $('#snn_history_prev_page').on('click', () => goToHistoryPage(historyCurrentPage - 1));
        $('#snn_history_next_page').on('click', () => goToHistoryPage(historyCurrentPage + 1));
        $('#snn_history_last_page').on('click', () => goToHistoryPage(Math.ceil(historyItems.length / historyPerPage)));
        
        // Load a specific page of history from server
        function loadHistoryPage(page) {
            return $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'snn_get_optimization_history',
                    page: page,
                    per_page: historyPerPage,
                    nonce: '<?php echo wp_create_nonce('snn_optimize_existing_nonce'); ?>'
                }
            });
        }
        
        // Load all history pages
        async function loadAllHistory() {
            try {
                // First request to get total count
                const firstResponse = await loadHistoryPage(1);
                
                if (!firstResponse.success || !firstResponse.data) {
                    return { success: false, data: [] };
                }
                
                const totalPages = firstResponse.data.total_pages || 1;
                let allHistory = [...firstResponse.data.data]; // Note: response.data.data contains the array
                
                // Load remaining pages in parallel (if more than 1 page)
                if (totalPages > 1) {
                    const pagePromises = [];
                    for (let p = 2; p <= totalPages; p++) {
                        pagePromises.push(loadHistoryPage(p));
                    }
                    
                    const responses = await Promise.all(pagePromises);
                    responses.forEach(response => {
                        if (response.success && response.data && response.data.data) {
                            allHistory = allHistory.concat(response.data.data);
                        }
                    });
                }
                
                return {
                    success: true,
                    data: allHistory
                };
            } catch (error) {
                console.error('Error loading history:', error);
                return { success: false, data: [] };
            }
        }
        
        async function loadHistory() {
            const response = await loadAllHistory();
            
            if (response.success && response.data.length > 0) {
                historyItems = response.data;
                selectedHistoryIds.clear();
                historyCurrentPage = 1;
                renderHistoryTable(getCurrentHistoryPageItems());
                updateHistoryPagination();
                $('#snn_history_table').show();
                $('#snn_no_history').hide();
            } else {
                historyItems = [];
                $('#snn_history_table').hide();
                $('#snn_no_history').show();
            }
            
            if (!response.success && response.data.length === 0 && historyItems.length === 0) {
                showHistoryMessage('<?php _e('Error loading history.', 'snn'); ?>', 'error');
            }
        }
        
        function renderHistoryTable(items) {
            const $tbody = $('#snn_history_tbody');
            $tbody.empty();

            items.forEach(function(item) {
                const isChecked = selectedHistoryIds.has(item.id) ? 'checked' : '';
                const originalDeleted = item.original_deleted || false;

                let originalUrlDisplay;
                if (originalDeleted) {
                    originalUrlDisplay = '<span style="color: #d63638; font-weight: 500;">🗑️ <?php _e('Deleted (Space Saved)', 'snn'); ?></span>';
                } else {
                    originalUrlDisplay = `<a href="${item.original_url}" target="_blank" style="font-size: 11px; word-break: break-all;">${item.original_url}</a>`;
                }

                let actionButton;
                if (originalDeleted) {
                    actionButton = `<button type="button" class="button button-small" disabled title="<?php _e('Original was deleted', 'snn'); ?>">
                        <?php _e('Cannot Restore', 'snn'); ?>
                    </button>`;
                } else {
                    actionButton = `<button type="button" class="button button-small snn-restore-single" data-id="${item.id}">
                        <?php _e('Restore', 'snn'); ?>
                    </button>`;
                }

                const row = `<tr data-id="${item.id}">
                    <td><input type="checkbox" class="snn-history-checkbox" value="${item.id}" ${isChecked} ${originalDeleted ? 'disabled' : ''}></td>
                    <td><img src="${item.thumbnail}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 3px;"></td>
                    <td>${item.filename}</td>
                    <td>${originalUrlDisplay}</td>
                    <td><a href="${item.current_url}" target="_blank" style="font-size: 11px; word-break: break-all;">${item.current_url}</a></td>
                    <td>${actionButton}</td>
                </tr>`;
                $tbody.append(row);
            });

            updateHistorySelectAllCheckbox();
            updateHistorySelectedCount();
        }
        
        function updateHistorySelectAllCheckbox() {
            const visibleCheckboxes = $('.snn-history-checkbox');
            const checkedCount = visibleCheckboxes.filter(':checked').length;
            const totalCount = visibleCheckboxes.length;
            
            $('#snn_select_all_history, #snn_select_all_history_header').prop('checked', totalCount > 0 && checkedCount === totalCount);
        }
        
        // Load history on page load
        loadHistory();
        
        $('#snn_refresh_history').on('click', loadHistory);
        
        $(document).on('change', '.snn-history-checkbox, #snn_select_all_history, #snn_select_all_history_header', function() {
            if ($(this).is('#snn_select_all_history, #snn_select_all_history_header')) {
                // Select/deselect all on current page
                const isChecked = $(this).prop('checked');
                $('.snn-history-checkbox').each(function() {
                    const id = parseInt($(this).val());
                    if (isChecked) {
                        selectedHistoryIds.add(id);
                    } else {
                        selectedHistoryIds.delete(id);
                    }
                    $(this).prop('checked', isChecked);
                });
                // Sync both "select all" checkboxes
                $('#snn_select_all_history, #snn_select_all_history_header').prop('checked', isChecked);
            } else {
                // Individual checkbox changed
                const id = parseInt($(this).val());
                if ($(this).prop('checked')) {
                    selectedHistoryIds.add(id);
                } else {
                    selectedHistoryIds.delete(id);
                }
                updateHistorySelectAllCheckbox();
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
            const selectedIds = Array.from(selectedHistoryIds);
            
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
        
        // Delete all original files
        $('#snn_delete_originals').on('click', async function() {
            if (historyItems.length === 0) {
                showHistoryMessage('<?php _e('No optimized images found.', 'snn'); ?>', 'info');
                return;
            }

            // Count how many originals are NOT already deleted
            const deletableItems = historyItems.filter(item => !item.original_deleted);

            if (deletableItems.length === 0) {
                showHistoryMessage('<?php _e('All originals have already been deleted.', 'snn'); ?>', 'info');
                return;
            }

            const warningMessage = '⚠️ CRITICAL WARNING ⚠️\n' +
                'You are about to PERMANENTLY DELETE ' + deletableItems.length + ' original image files to save disk space!\n\n' +
                '✅ Optimization metadata WILL BE PRESERVED (you can still see savings info)\n' +
                '❌ Images CANNOT be restored to original format after deletion\n' +
                '❌ This action CANNOT be reversed!\n\n' +
                'Type "DELETE" (in uppercase) to confirm:';

            const userInput = prompt(warningMessage);

            if (userInput !== 'DELETE') {
                showHistoryMessage('<?php _e('Action cancelled. Original files were not deleted.', 'snn'); ?>', 'info');
                return;
            }

            // Second confirmation
            if (!confirm('<?php _e('FINAL CONFIRMATION: Are you absolutely certain you want to delete all original files? This cannot be undone!', 'snn'); ?>')) {
                showHistoryMessage('<?php _e('Action cancelled. Original files were not deleted.', 'snn'); ?>', 'info');
                return;
            }

            const allIds = historyItems.map(item => item.id);
            await deleteOriginalFiles(allIds);
        });
        
        async function deleteOriginalFiles(ids) {
            $('#snn_restore_selected, #snn_restore_all, #snn_delete_originals').prop('disabled', true);
            $('#snn_restore_progress').show();

            let successCount = 0;
            let alreadyDeletedCount = 0;
            let errorCount = 0;

            for (let i = 0; i < ids.length; i++) {
                const id = ids[i];
                const progress = ((i + 1) / ids.length) * 100;
                $('#snn_restore_progress_bar').css('width', progress + '%').css('background-color', '#d63638');
                $('#snn_restore_progress_text').text('<?php _e('Deleting original', 'snn'); ?> ' + (i + 1) + ' / ' + ids.length + '...').css('color', '#d63638');

                try {
                    const response = await $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'snn_delete_original_file',
                            attachment_id: id,
                            nonce: '<?php echo wp_create_nonce('snn_optimize_existing_nonce'); ?>'
                        }
                    });

                    if (response.success) {
                        // Check if it was already deleted or newly deleted
                        if (response.data && response.data.message && response.data.message.includes('already deleted')) {
                            alreadyDeletedCount++;
                        } else {
                            successCount++;
                        }
                    } else {
                        errorCount++;
                    }
                } catch (e) {
                    errorCount++;
                }
            }

            $('#snn_restore_selected, #snn_restore_all, #snn_delete_originals').prop('disabled', false);
            $('#snn_restore_progress_text').text('<?php _e('Complete!', 'snn'); ?>');

            setTimeout(function() {
                $('#snn_restore_progress').hide();
                $('#snn_restore_progress_bar').css('background-color', '#d63638');
                loadHistory();
            }, 2000);

            const totalProcessed = successCount + alreadyDeletedCount;
            const message = '<?php _e('Deletion complete:', 'snn'); ?> ' + successCount + ' <?php _e('deleted', 'snn'); ?>' +
                (alreadyDeletedCount > 0 ? ', ' + alreadyDeletedCount + ' <?php _e('already deleted', 'snn'); ?>' : '') +
                (errorCount > 0 ? ', ' + errorCount + ' <?php _e('failed', 'snn'); ?>' : '') +
                '. <?php _e('Optimization metadata preserved. Images cannot be restored to original format.', 'snn'); ?>';

            showHistoryMessage(message, totalProcessed > 0 ? 'success' : 'error');
        }
        
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
// ATTACHMENT EDIT PAGE METABOX
// ============================================

// Add metabox to attachment edit page
add_action('add_meta_boxes', 'snn_add_image_optimization_metabox');

function snn_add_image_optimization_metabox() {
    add_meta_box(
        'snn_image_optimization',
        __('Image Optimization', 'snn'),
        'snn_image_optimization_metabox_callback',
        'attachment',
        'side',
        'default'
    );
}

function snn_image_optimization_metabox_callback($post) {
    $mime_type = get_post_mime_type($post->ID);
    $is_image = strpos($mime_type, 'image/') === 0;

    if (!$is_image) {
        echo '<p>' . __('Not an image file.', 'snn') . '</p>';
        return;
    }

    $is_optimized = get_post_meta($post->ID, '_snn_optimized', true);
    $original_url = get_post_meta($post->ID, '_snn_original_url', true);
    $original_size = get_post_meta($post->ID, '_snn_original_size', true);
    $original_total_size = get_post_meta($post->ID, '_snn_original_total_size', true);
    $optimized_size = get_post_meta($post->ID, '_snn_optimized_size', true);
    $original_deleted = get_post_meta($post->ID, '_snn_original_deleted', true);
    $space_freed = get_post_meta($post->ID, '_snn_space_freed', true);
    $file_path = get_attached_file($post->ID);
    $current_size = file_exists($file_path) ? filesize($file_path) : 0;

    wp_nonce_field('snn_optimize_single_attachment', 'snn_optimize_nonce');

    echo '<div class="snn-attachment-optimization">';

    if ($is_optimized && $original_url) {
        echo '<p><strong style="color: #00a32a;">✓ ' . __('Optimized', 'snn') . '</strong></p>';

        if ($original_size && $optimized_size) {
            $savings = $original_size - $optimized_size;
            $savings_percent = round(($savings / $original_size) * 100, 1);
            echo '<p><strong>' . __('File Size Comparison:', 'snn') . '</strong><br>';
            echo __('Original:', 'snn') . ' ' . size_format($original_size) . '<br>';
            echo __('Optimized:', 'snn') . ' ' . size_format($optimized_size) . '<br>';
            echo '<span style="color: ' . ($savings > 0 ? '#00a32a' : '#d63638') . ';">'
                . ($savings > 0 ? '↓' : '↑') . ' '
                . size_format(abs($savings)) . ' (' . abs($savings_percent) . '%)</span>';
            echo '</p>';
        }

        if ($original_deleted === '1') {
            echo '<p><strong>' . __('Original Status:', 'snn') . '</strong><br>';
            echo '<span style="color: #d63638;">🗑️ ' . __('Original Deleted (Space Saved)', 'snn') . '</span><br>';
            echo '<small style="color: #646970;">' . __('Cannot be restored', 'snn') . '</small>';

            if ($space_freed) {
                echo '<br><strong style="color: #00a32a;">💾 ' . sprintf(__('Freed: %s', 'snn'), size_format($space_freed)) . '</strong>';
            }
            echo '</p>';
        } else {
            echo '<p><strong>' . __('Original URL:', 'snn') . '</strong><br>';
            echo '<a href="' . esc_url($original_url) . '" target="_blank" style="font-size: 11px; word-break: break-all;">'
                . esc_html(basename($original_url)) . '</a></p>';

            echo '<button type="button" class="button button-secondary snn-restore-single-btn" data-id="' . $post->ID . '"'
                . ' style="width: 100%; margin-bottom: 10px;">' . __('Restore Original', 'snn') . '</button>';
        }

        echo '<button type="button" class="button snn-reoptimize-btn" data-id="' . $post->ID . '"'
            . ' style="width: 100%;">' . __('Re-optimize', 'snn') . '</button>';
    } else {
        echo '<p>' . __('This image is not optimized.', 'snn') . '</p>';

        if ($current_size) {
            echo '<p><strong>' . __('Current Size:', 'snn') . '</strong> ' . size_format($current_size) . '</p>';
        }

        echo '<p><a href="' . admin_url('upload.php?page=snn-image-optimization&tab=existing') . '" class="button button-primary" style="width: 100%;">' . __('Go to Bulk Optimizer', 'snn') . '</a></p>';
    }

    echo '<div id="snn-metabox-message-' . $post->ID . '" style="margin-top: 10px;"></div>';

    // Add debug metadata section
    echo '<hr style="margin: 15px 0; border: none; border-top: 1px solid #dcdcde;">';
    echo '<details style="margin-top: 10px;">';
    echo '<summary style="cursor: pointer; color: #646970; font-size: 12px; user-select: none;">' . __('🔍 Debug: Raw Metadata', 'snn') . '</summary>';
    echo '<div style="margin-top: 10px; padding: 10px; background: #f6f7f7; border: 1px solid #dcdcde; border-radius: 3px; font-family: monospace; font-size: 11px; max-height: 300px; overflow-y: auto;">';

    // Gather all metadata
    $original_meta = get_post_meta($post->ID, '_snn_original_metadata', true);
    $has_unscaled_original = !empty($original_meta['original_image']);

    $debug_data = array(
        'attachment_id' => $post->ID,
        'file_path' => $file_path,
        'file_exists' => file_exists($file_path),
        'mime_type' => $mime_type,
        'current_size_bytes' => $current_size,
        'current_url' => wp_get_attachment_url($post->ID),
        'has_unscaled_original' => $has_unscaled_original ? 'YES - ' . $original_meta['original_image'] : 'NO',
        'wp_metadata' => wp_get_attachment_metadata($post->ID),
        'optimization_meta' => array(
            '_snn_optimized' => get_post_meta($post->ID, '_snn_optimized', true),
            '_snn_original_url' => get_post_meta($post->ID, '_snn_original_url', true),
            '_snn_original_file' => get_post_meta($post->ID, '_snn_original_file', true),
            '_snn_original_size' => get_post_meta($post->ID, '_snn_original_size', true),
            '_snn_original_total_size' => get_post_meta($post->ID, '_snn_original_total_size', true),
            '_snn_optimized_size' => get_post_meta($post->ID, '_snn_optimized_size', true),
            '_snn_optimized_date' => get_post_meta($post->ID, '_snn_optimized_date', true),
            '_snn_original_metadata' => $original_meta,
            '_snn_original_deleted' => get_post_meta($post->ID, '_snn_original_deleted', true),
            '_snn_original_deleted_date' => get_post_meta($post->ID, '_snn_original_deleted_date', true),
            '_snn_deleted_files_count' => get_post_meta($post->ID, '_snn_deleted_files_count', true),
            '_snn_space_freed' => get_post_meta($post->ID, '_snn_space_freed', true),
        )
    );

    echo '<pre style="margin: 0; white-space: pre-wrap; word-wrap: break-word;">' . esc_html(print_r($debug_data, true)) . '</pre>';
    echo '</div>';

    // Add manual delete trigger button if files exist and not deleted
    if ($is_optimized && $original_deleted !== '1') {
        echo '<div style="margin-top: 10px; padding: 10px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 3px;">';
        echo '<p style="margin: 0 0 8px 0; font-size: 12px; color: #856404;"><strong>Debug Action:</strong></p>';
        echo '<button type="button" class="button button-small snn-debug-delete-btn" data-id="' . $post->ID . '" style="font-size: 11px;">';
        echo '🗑️ Delete Original Files Now (Debug)';
        echo '</button>';
        echo '<p style="margin: 8px 0 0 0; font-size: 11px; color: #856404;">This will attempt to delete the original file and all thumbnails. Check browser console and WordPress debug.log for details.</p>';
        echo '</div>';
    }

    echo '</details>';

    echo '</div>';

    // Add inline JavaScript for metabox actions
    ?>
    <script>
    jQuery(document).ready(function($) {
        var attachmentId = <?php echo $post->ID; ?>;
        
        function showMetaboxMessage(message, type) {
            var colors = {
                success: '#00a32a',
                error: '#d63638',
                info: '#2271b1'
            };
            var color = colors[type] || colors.info;
            $('#snn-metabox-message-' + attachmentId).html(
                '<div style="padding: 8px; border-left: 3px solid ' + color + '; background: #f6f7f7; font-size: 12px;">'
                + message + '</div>'
            ).fadeIn();
        }
        
        // Restore button
        $('.snn-restore-single-btn[data-id="' + attachmentId + '"]').on('click', function() {
            var $btn = $(this);
            
            if (!confirm('<?php _e('Restore this image to its original? The WebP version will be deleted.', 'snn'); ?>')) {
                return;
            }
            
            $btn.prop('disabled', true).text('<?php _e('Restoring...', 'snn'); ?>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'snn_restore_original_image',
                    attachment_id: attachmentId,
                    nonce: '<?php echo wp_create_nonce('snn_optimize_existing_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        showMetaboxMessage('<?php _e('Image restored! Refreshing page...', 'snn'); ?>', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        $btn.prop('disabled', false).text('<?php _e('Restore Original', 'snn'); ?>');
                        showMetaboxMessage(response.data || '<?php _e('Restore failed.', 'snn'); ?>', 'error');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text('<?php _e('Restore Original', 'snn'); ?>');
                    showMetaboxMessage('<?php _e('Error during restore.', 'snn'); ?>', 'error');
                }
            });
        });
        
        // Re-optimize button
        $('.snn-reoptimize-btn[data-id="' + attachmentId + '"]').on('click', function() {
            showMetaboxMessage('<?php _e('Please use the Bulk Optimizer page for re-optimization.', 'snn'); ?>', 'info');
        });

        // Debug delete button
        $('.snn-debug-delete-btn[data-id="' + attachmentId + '"]').on('click', function() {
            var $btn = $(this);

            if (!confirm('<?php _e('DEBUG: Delete original files now? This will permanently delete the original image and all its thumbnails.', 'snn'); ?>')) {
                return;
            }

            $btn.prop('disabled', true).text('<?php _e('Deleting...', 'snn'); ?>');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'snn_delete_original_file',
                    attachment_id: attachmentId,
                    nonce: '<?php echo wp_create_nonce('snn_optimize_existing_nonce'); ?>'
                },
                success: function(response) {
                    console.log('Delete response:', response);
                    if (response.success) {
                        showMetaboxMessage('<?php _e('Success!', 'snn'); ?> ' + response.data.message + '<br><?php _e('Refreshing page...', 'snn'); ?>', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        $btn.prop('disabled', false).text('🗑️ <?php _e('Delete Original Files Now (Debug)', 'snn'); ?>');
                        showMetaboxMessage('<?php _e('Error:', 'snn'); ?> ' + (response.data || '<?php _e('Unknown error', 'snn'); ?>'), 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', xhr, status, error);
                    $btn.prop('disabled', false).text('🗑️ <?php _e('Delete Original Files Now (Debug)', 'snn'); ?>');
                    showMetaboxMessage('<?php _e('AJAX Error:', 'snn'); ?> ' + error, 'error');
                }
            });
        });
    });
    </script>
    <?php
}

// ============================================
// AJAX HANDLERS
// ============================================

// AJAX handler for saving images from Upload tab
add_action('wp_ajax_snn_save_optimized_image', 'snn_save_optimized_image');

function snn_save_optimized_image() {
    check_ajax_referer('snn_save_image_nonce', 'nonce');
    
    if (!current_user_can('upload_files')) {
        wp_send_json_error(__('Permission denied.', 'snn'));
    }
    
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error(__('No image uploaded or upload error.', 'snn'));
    }
    
    $file = $_FILES['image'];
    $filename = isset($_POST['filename']) ? sanitize_file_name($_POST['filename']) : $file['name'];
    
    // Upload to media library
    $upload = wp_handle_upload($file, array('test_form' => false));
    
    if (isset($upload['error'])) {
        wp_send_json_error($upload['error']);
    }
    
    // Create attachment
    $attachment = array(
        'post_mime_type' => $upload['type'],
        'post_title'     => preg_replace('/\.[^.]+$/', '', $filename),
        'post_content'   => '',
        'post_status'    => 'inherit'
    );
    
    $attach_id = wp_insert_attachment($attachment, $upload['file']);
    
    if (is_wp_error($attach_id)) {
        wp_send_json_error($attach_id->get_error_message());
    }
    
    // Generate metadata
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
    wp_update_attachment_metadata($attach_id, $attach_data);
    
    wp_send_json_success(array(
        'id' => $attach_id,
        'url' => $upload['url'],
        'filename' => $filename
    ));
}

// Scan for unoptimized images
add_action('wp_ajax_snn_scan_unoptimized_images', 'snn_scan_unoptimized_images');

function snn_scan_unoptimized_images() {
    check_ajax_referer('snn_optimize_existing_nonce', 'nonce');
    
    if (!current_user_can('upload_files')) {
        wp_send_json_error(__('Permission denied.', 'snn'));
    }
    
    $include_optimized = isset($_POST['include_optimized']) && $_POST['include_optimized'] === 'true';
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 50;
    
    // Ensure valid values
    if ($page < 1) $page = 1;
    if ($per_page < 1) $per_page = 50;
    if ($per_page > 100) $per_page = 100; // Max 100 per page for safety
    
    $base_args = array(
        'post_type' => 'attachment',
        'post_mime_type' => array('image/jpeg', 'image/png'),
        'posts_per_page' => $per_page,
        'paged' => $page,
        'post_status' => 'inherit'
    );
    
    if (!$include_optimized) {
        $base_args['meta_query'] = array(
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
        );
    }
    
    $query = new WP_Query($base_args);
    $attachments = $query->posts;
    $total_items = $query->found_posts;
    
    $images = array();
    
    foreach ($attachments as $attachment) {
        $file_path = get_attached_file($attachment->ID);
        $file_size_bytes = file_exists($file_path) ? filesize($file_path) : 0;
        $file_size = size_format($file_size_bytes);
        $thumbnail = wp_get_attachment_image_src($attachment->ID, 'thumbnail');
        $full_url = wp_get_attachment_url($attachment->ID);
        
        // Get dimensions
        $metadata = wp_get_attachment_metadata($attachment->ID);
        $width = isset($metadata['width']) ? $metadata['width'] : 0;
        $height = isset($metadata['height']) ? $metadata['height'] : 0;
        
        $is_optimized = get_post_meta($attachment->ID, '_snn_optimized', true) == '1';
        $optimized_size = get_post_meta($attachment->ID, '_snn_optimized_size', true);
        
        $images[] = array(
            'id' => $attachment->ID,
            'filename' => basename($file_path),
            'url' => $full_url,
            'full_url' => $full_url,
            'thumbnail' => $thumbnail ? $thumbnail[0] : '',
            'mime_type' => $attachment->post_mime_type,
            'size' => $file_size,
            'size_bytes' => $file_size_bytes,
            'width' => $width,
            'height' => $height,
            'optimized_size' => $optimized_size ? size_format($optimized_size) : '-',
            'optimized' => $is_optimized
        );
    }
    
    wp_send_json_success(array(
        'images' => $images,
        'total' => $total_items,
        'page' => $page,
        'per_page' => $per_page,
        'total_pages' => ceil($total_items / $per_page)
    ));
}

// Handle upload of client-side optimized image (for Optimize Existing tab)
add_action('wp_ajax_snn_save_optimized_existing_image', 'snn_save_optimized_existing_image');

function snn_save_optimized_existing_image() {
    check_ajax_referer('snn_optimize_existing_nonce', 'nonce');
    
    if (!current_user_can('upload_files')) {
        wp_send_json_error(__('Permission denied.', 'snn'));
    }
    
    $attachment_id = intval($_POST['attachment_id']);
    
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error(__('No image uploaded or upload error.', 'snn'));
    }
    
    $uploaded_file = $_FILES['image']['tmp_name'];
    $new_filename = sanitize_file_name($_FILES['image']['name']);
    
    // Get original file info
    $original_file = get_attached_file($attachment_id);
    $original_url = wp_get_attachment_url($attachment_id);
    $original_size = file_exists($original_file) ? filesize($original_file) : 0;
    
    if (!$original_file || !file_exists($original_file)) {
        wp_send_json_error(__('Original file not found.', 'snn'));
    }
    
    // Get original metadata (includes thumbnail info)
    $original_metadata = wp_get_attachment_metadata($attachment_id);

    // Store original info (only if not already optimized)
    $is_already_optimized = get_post_meta($attachment_id, '_snn_optimized', true);
    if (!$is_already_optimized) {
        update_post_meta($attachment_id, '_snn_original_url', $original_url);
        update_post_meta($attachment_id, '_snn_original_file', $original_file);
        update_post_meta($attachment_id, '_snn_original_size', $original_size);
        // CRITICAL: Store the complete original metadata including thumbnail list
        update_post_meta($attachment_id, '_snn_original_metadata', $original_metadata);

        // Calculate total size of original + all thumbnails for accurate space reporting
        $total_original_size = $original_size;
        if (!empty($original_metadata['sizes']) && is_array($original_metadata['sizes'])) {
            $original_dir = dirname($original_file);
            foreach ($original_metadata['sizes'] as $size_name => $size_info) {
                if (!empty($size_info['file'])) {
                    $thumbnail_path = path_join($original_dir, $size_info['file']);
                    if (file_exists($thumbnail_path)) {
                        $total_original_size += filesize($thumbnail_path);
                    }
                }
            }
        }
        update_post_meta($attachment_id, '_snn_original_total_size', $total_original_size);
    }
    
    // Determine new file path
    $path_info = pathinfo($original_file);
    $webp_path = $path_info['dirname'] . '/' . $path_info['filename'] . '.webp';
    
    // Move uploaded file
    if (!move_uploaded_file($uploaded_file, $webp_path)) {
        wp_send_json_error(__('Failed to save WebP file.', 'snn'));
    }
    
    $optimized_size = filesize($webp_path);
    
    // Update WordPress attachment
    $upload_dir = wp_upload_dir();
    $webp_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $webp_path);
    
    update_attached_file($attachment_id, $webp_path);
    
    wp_update_post(array(
        'ID' => $attachment_id,
        'post_mime_type' => 'image/webp',
        'guid' => $webp_url
    ));
    
    // Regenerate thumbnails
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $metadata = wp_generate_attachment_metadata($attachment_id, $webp_path);
    wp_update_attachment_metadata($attachment_id, $metadata);
    
    // Mark as optimized
    update_post_meta($attachment_id, '_snn_optimized', '1');
    update_post_meta($attachment_id, '_snn_optimized_date', current_time('mysql'));
    update_post_meta($attachment_id, '_snn_optimized_size', $optimized_size);
    
    // Keep the original file for restoration purposes
    // Do NOT delete the original file - we need it for the restore functionality
    // The original file path is stored in _snn_original_file meta
    
    wp_send_json_success(array(
        'message' => __('Image optimized successfully.', 'snn'),
        'new_url' => $webp_url,
        'original_size' => $original_size,
        'optimized_size' => $optimized_size,
        'savings' => $original_size - $optimized_size
    ));
}

// Get optimization history
add_action('wp_ajax_snn_get_optimization_history', 'snn_get_optimization_history');

function snn_get_optimization_history() {
    check_ajax_referer('snn_optimize_existing_nonce', 'nonce');
    
    if (!current_user_can('upload_files')) {
        wp_send_json_error(__('Permission denied.', 'snn'));
    }
    
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 50;
    
    // Ensure valid values
    if ($page < 1) $page = 1;
    if ($per_page < 1) $per_page = 50;
    if ($per_page > 100) $per_page = 100; // Max 100 per page for safety
    
    $args = array(
        'post_type' => 'attachment',
        'posts_per_page' => $per_page,
        'paged' => $page,
        'post_status' => 'inherit',
        'meta_query' => array(
            array(
                'key' => '_snn_optimized',
                'value' => '1',
                'compare' => '='
            )
        )
    );
    
    $query = new WP_Query($args);
    $attachments = $query->posts;
    $total_items = $query->found_posts;
    
    $history = array();
    
    foreach ($attachments as $attachment) {
        $original_url = get_post_meta($attachment->ID, '_snn_original_url', true);
        $current_url = wp_get_attachment_url($attachment->ID);
        $thumbnail = wp_get_attachment_image_src($attachment->ID, 'thumbnail');
        $file_path = get_attached_file($attachment->ID);
        $original_deleted = get_post_meta($attachment->ID, '_snn_original_deleted', true);

        if ($original_url) {
            $history[] = array(
                'id' => $attachment->ID,
                'filename' => basename($file_path),
                'original_url' => $original_url,
                'current_url' => $current_url,
                'thumbnail' => $thumbnail ? $thumbnail[0] : '',
                'optimized_date' => get_post_meta($attachment->ID, '_snn_optimized_date', true),
                'original_deleted' => $original_deleted === '1'
            );
        }
    }
    
    wp_send_json_success(array(
        'data' => $history,
        'total' => $total_items,
        'page' => $page,
        'per_page' => $per_page,
        'total_pages' => ceil($total_items / $per_page)
    ));
}

// Delete original file (for space saving)
// Handles edge cases: Windows localhost (mixed slashes), Linux hosting (standard paths)
add_action('wp_ajax_snn_delete_original_file', 'snn_delete_original_file');

function snn_delete_original_file() {
    check_ajax_referer('snn_optimize_existing_nonce', 'nonce');

    if (!current_user_can('upload_files')) {
        wp_send_json_error(__('Permission denied.', 'snn'));
    }

    $attachment_id = intval($_POST['attachment_id']);

    $original_file = get_post_meta($attachment_id, '_snn_original_file', true);
    $original_metadata = get_post_meta($attachment_id, '_snn_original_metadata', true);

    if (!$original_file) {
        wp_send_json_error(__('Original file path not found in metadata.', 'snn'));
    }

    // Check if already deleted
    $already_deleted = get_post_meta($attachment_id, '_snn_original_deleted', true);
    if ($already_deleted === '1') {
        wp_send_json_success(array(
            'message' => __('Original files were already deleted previously.', 'snn')
        ));
    }

    // DEBUG: Log what we're working with
    error_log("SNN Delete Original - Attachment ID: $attachment_id");
    error_log("SNN Delete Original - Original File (raw): $original_file");
    error_log("SNN Delete Original - Original Metadata: " . print_r($original_metadata, true));

    // Track deletion results
    $files_to_delete = array();
    $deleted_count = 0;
    $failed_count = 0;
    $total_size_freed = 0;

    // Get upload directory info
    $upload_dir = wp_upload_dir();
    $upload_basedir = wp_normalize_path($upload_dir['basedir']);

    // EDGE CASE HANDLING: Normalize and fix path issues (Windows localhost + Linux hosting)
    // This handles malformed paths from various environments
    $original_file = wp_normalize_path($original_file);

    // Method 1: Try stored path directly
    $original_file_found = false;
    if (file_exists($original_file)) {
        $original_file_found = true;
        error_log("SNN Delete Original - Found via stored path: $original_file");
    }

    // Method 2: Rebuild from metadata file path (most reliable)
    if (!$original_file_found && !empty($original_metadata['file'])) {
        $rebuilt_path = wp_normalize_path($upload_basedir . '/' . $original_metadata['file']);
        if (file_exists($rebuilt_path)) {
            $original_file = $rebuilt_path;
            $original_file_found = true;
            error_log("SNN Delete Original - Found via metadata rebuild: $original_file");
        }
    }

    // Method 3: Try to fix common path issues (Windows backslash corruption)
    if (!$original_file_found) {
        // Fix cases like "C:Apache24" -> "C:\Apache24" or "C:/Apache24"
        $fixed_path = preg_replace('/^([A-Z]):(?![\\\\\/])/', '$1:/', $original_file);
        $fixed_path = wp_normalize_path($fixed_path);
        if (file_exists($fixed_path)) {
            $original_file = $fixed_path;
            $original_file_found = true;
            error_log("SNN Delete Original - Found via path fix: $original_file");
        }
    }

    // Method 4: Extract filename and search in upload directory
    if (!$original_file_found && !empty($original_metadata['file'])) {
        $basename_from_meta = basename($original_metadata['file']);
        $dir_from_meta = dirname($original_metadata['file']);
        $search_path = wp_normalize_path($upload_basedir . '/' . $dir_from_meta . '/' . $basename_from_meta);
        if (file_exists($search_path)) {
            $original_file = $search_path;
            $original_file_found = true;
            error_log("SNN Delete Original - Found via basename search: $original_file");
        }
    }

    $original_dir = dirname($original_file);

    $original_filename_no_ext = pathinfo($original_file, PATHINFO_FILENAME);
    $original_extension = pathinfo($original_file, PATHINFO_EXTENSION);

    // Add main original file (scaled version)
    if (file_exists($original_file)) {
        $files_to_delete[] = array(
            'path' => $original_file,
            'size' => filesize($original_file),
            'type' => 'main_scaled',
            'name' => basename($original_file)
        );
        error_log("SNN Delete Original - Found main scaled file: " . basename($original_file));
    } else {
        error_log("SNN Delete Original - Main scaled file NOT found: $original_file");
    }

    // CRITICAL: Check for WordPress full-size original (before scaling)
    // When WordPress uploads large images, it creates a "-scaled" version and keeps the original
    // The original unscaled image is stored in metadata['original_image']
    if (!empty($original_metadata['original_image'])) {
        $original_unscaled_path = wp_normalize_path($original_dir . '/' . $original_metadata['original_image']);
        error_log("SNN Delete Original - Checking for unscaled original: " . $original_metadata['original_image']);
        error_log("SNN Delete Original - Full unscaled path: $original_unscaled_path");

        if (file_exists($original_unscaled_path)) {
            $files_to_delete[] = array(
                'path' => $original_unscaled_path,
                'size' => filesize($original_unscaled_path),
                'type' => 'main_unscaled',
                'name' => basename($original_unscaled_path)
            );
            error_log("SNN Delete Original - Found unscaled original file: " . basename($original_unscaled_path));
        } else {
            error_log("SNN Delete Original - Unscaled original NOT found: $original_unscaled_path");
        }
    } else {
        error_log("SNN Delete Original - No original_image in metadata (image was not scaled by WordPress)");
    }

    // Use WordPress functions to get all intermediate sizes
    $intermediate_sizes = get_intermediate_image_sizes();
    error_log("SNN Delete Original - Registered image sizes: " . print_r($intermediate_sizes, true));

    // Method 1: Use stored metadata
    if (!empty($original_metadata['sizes']) && is_array($original_metadata['sizes'])) {
        error_log("SNN Delete Original - Found " . count($original_metadata['sizes']) . " thumbnail sizes in metadata");

        foreach ($original_metadata['sizes'] as $size_name => $size_info) {
            if (!empty($size_info['file'])) {
                $thumbnail_path = wp_normalize_path($original_dir . '/' . $size_info['file']);
                error_log("SNN Delete Original - Checking metadata thumbnail ($size_name): " . $size_info['file']);
                error_log("SNN Delete Original - Full thumbnail path: $thumbnail_path");

                if (file_exists($thumbnail_path)) {
                    $files_to_delete[] = array(
                        'path' => $thumbnail_path,
                        'size' => filesize($thumbnail_path),
                        'type' => 'thumbnail',
                        'size_name' => $size_name,
                        'name' => basename($thumbnail_path)
                    );
                    error_log("SNN Delete Original - Found metadata thumbnail: " . basename($thumbnail_path));
                } else {
                    error_log("SNN Delete Original - Metadata thumbnail NOT found: $thumbnail_path");
                }
            }
        }
    }

    // Method 2: Scan directory for matching files (catches any thumbnails metadata might have missed)
    // Pattern: {filename}-{width}x{height}.{extension}
    if (is_dir($original_dir)) {
        $pattern = $original_filename_no_ext . '-*.' . $original_extension;
        $glob_pattern = wp_normalize_path($original_dir . '/' . $pattern);
        error_log("SNN Delete Original - Scanning directory with pattern: $glob_pattern");

        $found_files = glob($glob_pattern);
        if ($found_files) {
            error_log("SNN Delete Original - Found " . count($found_files) . " files matching pattern");

            foreach ($found_files as $file_path) {
                // Check if not already in our list
                $already_added = false;
                foreach ($files_to_delete as $existing_file) {
                    if ($existing_file['path'] === $file_path) {
                        $already_added = true;
                        break;
                    }
                }

                if (!$already_added && file_exists($file_path)) {
                    $files_to_delete[] = array(
                        'path' => $file_path,
                        'size' => filesize($file_path),
                        'type' => 'thumbnail_scanned',
                        'name' => basename($file_path)
                    );
                    error_log("SNN Delete Original - Found additional thumbnail via scan: " . basename($file_path));
                }
            }
        } else {
            error_log("SNN Delete Original - No files found matching pattern");
        }
    }

    error_log("SNN Delete Original - Total files to delete: " . count($files_to_delete));

    // If no files found, mark as already deleted
    if (empty($files_to_delete)) {
        update_post_meta($attachment_id, '_snn_original_deleted', '1');
        update_post_meta($attachment_id, '_snn_original_deleted_date', current_time('mysql'));

        wp_send_json_success(array(
            'message' => __('Original file was already deleted or missing. Marked as deleted.', 'snn')
        ));
    }

    // Delete all files
    foreach ($files_to_delete as $file_info) {
        error_log("SNN Delete Original - Attempting to delete: " . $file_info['path']);

        if (@unlink($file_info['path'])) {
            $deleted_count++;
            $total_size_freed += $file_info['size'];
            error_log("SNN Delete Original - Successfully deleted: " . $file_info['name']);
        } else {
            $failed_count++;
            error_log("SNN Delete Original - FAILED to delete: " . $file_info['name']);
        }
    }

    // If at least the main file was deleted, mark as success
    if ($deleted_count > 0) {
        // Mark original as deleted, but KEEP all optimization metadata for better UX
        update_post_meta($attachment_id, '_snn_original_deleted', '1');
        update_post_meta($attachment_id, '_snn_original_deleted_date', current_time('mysql'));
        update_post_meta($attachment_id, '_snn_deleted_files_count', $deleted_count);
        update_post_meta($attachment_id, '_snn_space_freed', $total_size_freed);

        $message = sprintf(
            __('Successfully deleted %d file(s) and freed %s of disk space.', 'snn'),
            $deleted_count,
            size_format($total_size_freed)
        );

        if ($failed_count > 0) {
            $message .= ' ' . sprintf(__('%d file(s) could not be deleted.', 'snn'), $failed_count);
        }

        error_log("SNN Delete Original - Success! Deleted: $deleted_count, Failed: $failed_count, Space freed: " . size_format($total_size_freed));

        wp_send_json_success(array(
            'message' => $message,
            'deleted_count' => $deleted_count,
            'failed_count' => $failed_count,
            'space_freed' => $total_size_freed,
            'space_freed_formatted' => size_format($total_size_freed)
        ));
    } else {
        error_log("SNN Delete Original - FAILED! No files were deleted. Check permissions.");
        wp_send_json_error(__('Failed to delete original files. Check file permissions.', 'snn'));
    }
}

// Restore original image
add_action('wp_ajax_snn_restore_original_image', 'snn_restore_original_image');

function snn_restore_original_image() {
    check_ajax_referer('snn_optimize_existing_nonce', 'nonce');

    if (!current_user_can('upload_files')) {
        wp_send_json_error(__('Permission denied.', 'snn'));
    }

    $attachment_id = intval($_POST['attachment_id']);

    // Check if original was deleted
    $original_deleted = get_post_meta($attachment_id, '_snn_original_deleted', true);
    if ($original_deleted === '1') {
        wp_send_json_error(__('Cannot restore: Original file was permanently deleted to save space.', 'snn'));
    }

    $original_file = get_post_meta($attachment_id, '_snn_original_file', true);
    $original_url = get_post_meta($attachment_id, '_snn_original_url', true);

    if (!$original_file) {
        wp_send_json_error(__('Original file path not found in metadata.', 'snn'));
    }
    
    // Try to locate the original file using multiple methods
    $file_exists = false;
    $correct_original_file = $original_file;
    
    // Method 1: Check the stored path directly
    if (file_exists($original_file)) {
        $file_exists = true;
    }
    // Method 2: Try to reconstruct path from URL
    else if ($original_url) {
        $upload_dir = wp_upload_dir();
        $relative_path = str_replace($upload_dir['baseurl'], '', $original_url);
        $possible_file = $upload_dir['basedir'] . $relative_path;
        
        if (file_exists($possible_file)) {
            $correct_original_file = $possible_file;
            $file_exists = true;
        }
    }
    // Method 3: Check if file exists in the same directory as current file with original extension
    if (!$file_exists) {
        $current_file = get_attached_file($attachment_id);
        if ($current_file) {
            $current_dir = dirname($current_file);
            $original_basename = basename($original_file);
            $possible_file = $current_dir . '/' . $original_basename;
            
            if (file_exists($possible_file)) {
                $correct_original_file = $possible_file;
                $file_exists = true;
            }
        }
    }
    
    if (!$file_exists) {
        wp_send_json_error(__('Original file does not exist: ', 'snn') . basename($original_file) . '. ' . __('It may have been deleted or moved.', 'snn'));
    }
    
    // Update to use the correct path
    $original_file = $correct_original_file;
    
    // Get current WebP file path to delete later
    $current_file = get_attached_file($attachment_id);
    
    // Determine original mime type from file extension
    $extension = strtolower(pathinfo($original_file, PATHINFO_EXTENSION));
    $mime_type_map = array(
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp'
    );
    $mime_type = isset($mime_type_map[$extension]) ? $mime_type_map[$extension] : 'image/jpeg';
    
    // Update the attached file path back to original
    update_attached_file($attachment_id, $original_file);
    
    // Update attachment post
    $update_result = wp_update_post(array(
        'ID' => $attachment_id,
        'post_mime_type' => $mime_type,
        'guid' => $original_url
    ), true);
    
    if (is_wp_error($update_result)) {
        wp_send_json_error(__('Failed to update attachment: ', 'snn') . $update_result->get_error_message());
    }
    
    // Regenerate metadata for original
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $metadata = wp_generate_attachment_metadata($attachment_id, $original_file);
    wp_update_attachment_metadata($attachment_id, $metadata);
    
    // Delete the WebP file(s) - main file and all thumbnails
    $webp_files_deleted = 0;
    $webp_files_failed = 0;

    if ($current_file && $current_file !== $original_file && file_exists($current_file)) {
        // Delete main WebP file
        if (@unlink($current_file)) {
            $webp_files_deleted++;
        } else {
            $webp_files_failed++;
            error_log('SNN Image Optimization: Failed to delete WebP file: ' . $current_file);
        }

        // Delete WebP thumbnails
        $current_metadata = wp_get_attachment_metadata($attachment_id);
        if (!empty($current_metadata['sizes']) && is_array($current_metadata['sizes'])) {
            $current_dir = dirname($current_file);
            foreach ($current_metadata['sizes'] as $size_name => $size_info) {
                if (!empty($size_info['file'])) {
                    $webp_thumbnail_path = path_join($current_dir, $size_info['file']);
                    if (file_exists($webp_thumbnail_path)) {
                        if (@unlink($webp_thumbnail_path)) {
                            $webp_files_deleted++;
                        } else {
                            $webp_files_failed++;
                            error_log('SNN Image Optimization: Failed to delete WebP thumbnail: ' . $webp_thumbnail_path);
                        }
                    }
                }
            }
        }
    }

    // Remove optimization meta
    delete_post_meta($attachment_id, '_snn_optimized');
    delete_post_meta($attachment_id, '_snn_optimized_date');
    delete_post_meta($attachment_id, '_snn_optimized_size');
    delete_post_meta($attachment_id, '_snn_optimization_quality');
    delete_post_meta($attachment_id, '_snn_original_url');
    delete_post_meta($attachment_id, '_snn_original_file');
    delete_post_meta($attachment_id, '_snn_original_size');
    delete_post_meta($attachment_id, '_snn_original_metadata');
    delete_post_meta($attachment_id, '_snn_original_total_size');
    delete_post_meta($attachment_id, '_snn_original_deleted');
    delete_post_meta($attachment_id, '_snn_original_deleted_date');
    delete_post_meta($attachment_id, '_snn_deleted_files_count');
    delete_post_meta($attachment_id, '_snn_space_freed');
    
    // Clean the cache for this attachment
    clean_attachment_cache($attachment_id);
    
    wp_send_json_success(array(
        'message' => __('Image restored successfully.', 'snn'),
        'url' => $original_url
    ));
}