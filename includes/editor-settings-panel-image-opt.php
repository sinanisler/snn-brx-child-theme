<?php

// Image optimization functionality for SNN Bricks editor

// Function to render the image optimization section in the popup
function snn_render_image_optimization_section() {
    ?>
    <div class="snn-settings-content-wrapper-section">
        <div class="snn-settings-content-wrapper-section-title">
            
            <p style="margin-bottom:20px; font-size:14px; color:var(--builder-color-accent); max-width:550px"><?php _e('Optimize or Convert or Resize gigantic images. Fast. It uses your cpu to optimize images. If img count is more than 5 it will create .zip download.', 'snn'); ?></p>
        </div>
        <div class="snn-settings-content-wrapper-section-setting-area snn-image-optimize-container">
            
        




<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/javascript-canvas-to-blob/3.29.0/js/canvas-to-blob.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<style>
  /* General & App Layout */
  .snn-image-optimize-container .app-container {
    width: 100%;
    margin-left: auto;
    margin-right: auto;
    font-family: sans-serif;
  }
  .snn-image-optimize-container .hidden {
    display: none;
  }

  /* Upload Area */
  .snn-image-optimize-container #uploadAreaWrapper {
    margin-bottom: 24px;
  }
  .snn-image-optimize-container #uploadArea {
    border: 2px dashed #000000ff;
    border-radius: 8px;
    cursor: pointer;
    background-color: #000000ff;
    position: relative;
    min-height: 150px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    transition: border-color 200ms, background-color 200ms;
  }
  .snn-image-optimize-container #uploadArea:hover, .snn-image-optimize-container #uploadArea.drag-over {
    border-color: #1a1a1aff;
    background-color: #000000ff;
  }
  .snn-image-optimize-container #uploadAreaInitialContent {
    padding: 24px;
    text-align: center;
  }
  .snn-image-optimize-container .upload-icon {
    height: 48px;
    width: 48px;
    margin: 0 auto 8px auto;
    color: #e6e6e6ff;
  }
  .snn-image-optimize-container .upload-text {
    color: #e6e6e6ff;
  }
  .snn-image-optimize-container .upload-text-highlight {
    font-weight: 600;
    color: var(--builder-color-accent);
  }
  .snn-image-optimize-container .upload-subtext {
    font-size: 14px;
    color: #e6e6e6ff;
    margin-top: 4px;
  }

  /* File Previews */
  .snn-image-optimize-container #selectedFilesPreview {
    width: 100%;
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    padding: 16px;
    overflow-y: auto;
    max-height: 300px;
  }
  .snn-image-optimize-container .preview-item {
    position: relative;
    border: 1px solid #000000ff;
    border-radius: 6px;
    padding: 8px;
    background-color: #000000ff;
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 128px;
    transition: box-shadow 150ms;
  }
  .snn-image-optimize-container .preview-item:hover {
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
  }
  .snn-image-optimize-container .preview-img {
    width: 96px;
    height: 96px;
    object-fit: contain;
    border-radius: 4px;
    margin-bottom: 4px;
    background-color: #f1f5f9;
  }
  .snn-image-optimize-container .preview-name {
    display: block;
    font-size: 11px;
    color: #ffffffff;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    width: 100%;
    text-align: center;
  }
  .snn-image-optimize-container .remove-btn {
    position: absolute;
    top: -8px;
    right: -8px;
    background-color: #ef4444;
    color: white;
    border-radius: 9999px;
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
  .snn-image-optimize-container .preview-item:hover .remove-btn, .snn-image-optimize-container .remove-btn:focus {
    opacity: 1;
  }
  .snn-image-optimize-container .remove-btn:focus {
     box-shadow: 0 0 0 2px #f87171;
  }
  
  /* Clear All Button */
  .snn-image-optimize-container #clearAllButton {
    margin-top: 8px;
    width: auto;
    color: #dc2626;
    background-color: transparent;
    font-weight: 500;
    padding: 8px 12px;
    border-radius: 6px;
    border: 1px solid #fca5a5;
    transition: color 150ms, background-color 150ms;
    cursor: pointer;
  }
  .snn-image-optimize-container #clearAllButton:hover {
    color: #991b1b;
    background-color: #fef2f2;
  }
  
  /* Form & Inputs */
  .snn-image-optimize-container #imageForm {
    margin-bottom: 24px;
    display: flex;
    flex-direction: column;
    gap: 24px;
  }
  .snn-image-optimize-container .form-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
    align-items: end;
  }
  @media (min-width: 768px) {
    .snn-image-optimize-container .form-grid {
      grid-template-columns: repeat(3, 1fr);
    }
  }
  .snn-image-optimize-container .form-label {
    display: block;
    font-weight: 500;
    color: #ffffffff;
    margin-bottom: 4px;
  }
  .snn-image-optimize-container .form-input {
    width: 100%;
    padding: 12px;
    border: 1px solid #000000;
    border-radius: 6px;
    box-shadow: #000000;
    transition: border-color 150ms, box-shadow 150ms;
    background: #000000;
    color:white;
  }
  .snn-image-optimize-container .form-input:focus {
    outline: none;
    border-color: var(--builder-color-accent);
    
  }
  .snn-image-optimize-container select.form-input {
      background-color: #000000;
  }
  .snn-image-optimize-container #qualityInputContainer.disabled {
      opacity: 0.5;
      cursor: not-allowed;
  }
  
  /* Convert Button */
  .snn-image-optimize-container #convertButton {
    width: 100%;
    background-color: #000000ff;
    color: white;
    font-weight: 600;
    padding: 20px;
    border-radius: 6px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: none;
    cursor: pointer;
    transition: background-color 150ms ease-in-out;
  }
  .snn-image-optimize-container #convertButton:hover {
    background-color: #1f2937;
  }
  .snn-image-optimize-container #convertButton:focus {
    outline: 2px solid #4b5563;
    outline-offset: 2px;
  }
  .snn-image-optimize-container #convertButton:disabled {
    opacity: 0.7;
    cursor: wait;
  }
  .snn-image-optimize-container .spinner {
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
  .snn-image-optimize-container .message {
      font-weight: 500;
      padding: 12px;
      border-radius: 6px;
      box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
      margin-top: 16px;
      text-align: center;
  }
  .snn-image-optimize-container .message.error {
      color: #dc2626;
      background-color: #fee2e2;
  }
  .snn-image-optimize-container .message.success {
      color: #16a34a;
      background-color: #dcfce7;
  }
  .snn-image-optimize-container .message.info {
      color: #2563eb;
      background-color: #dbeafe;
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
    <button type="submit" id="convertButton">
      <svg xmlns="http://www.w3.org/2000/svg" style="width:20px; height:20px;" viewBox="0 0 20 20" fill="currentColor" id="convertButtonIcon">
        <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" />
        <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z" />
      </svg>

      <span id="convertButtonText">Download</span>

    </button>
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

    let selectedFiles = [];

    function generateId() {
      return Date.now().toString(36) + Math.random().toString(36).substring(2);
    }

    function showMessage(message, type = 'info') {
      messageArea.innerHTML = '';
      const p = document.createElement('p');
      p.textContent = message;
      p.classList.add('message', type); // types are 'error', 'success', 'info'
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
        uploadArea.style.display = 'flex'; // Re-apply flex properties
        clearAllButton.classList.add('hidden');
      } else {
        uploadAreaInitialContent.classList.add('hidden');
        selectedFilesPreview.classList.remove('hidden');
        uploadArea.style.display = 'block'; // Change display for preview layout
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
          img.src = `https://placehold.co/96x96/e2e8f0/94a3b8?text=Preview N/A`;
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
            convertButtonText.textContent = 'Convert Images';
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
    };

    formatSelect.onchange = updateQualityInputState;

    imageInput.onchange = (e) => {
      handleFiles(e.target.files);
      imageInput.value = null;
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

    imageForm.onsubmit = (e) => {
      e.preventDefault();
      messageArea.innerHTML = '';

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
              // Only zip if more than 5 images, else download separately (single or multiple).
              if (successCount > 5) {
                const zip = new JSZip();
                collectedBlobs.forEach(item => {
                  zip.file(item.name, item.blob);
                });
                zip.generateAsync({ type: "blob" })
                  .then(function(content) {
                    saveAs(content, "converted_images.zip");
                    let message = `Successfully converted ${successCount} image(s) and downloaded as a ZIP.`;
                    if (errorCount > 0) {
                      message += ` ${errorCount} image(s) failed to convert.`;
                      showMessage(message, 'error');
                    } else {
                      showMessage(message, 'success');
                    }
                  })
                  .catch(function (err) {
                    let message = `Error creating ZIP file: ${err.message}.`;
                    if (errorCount > 0) {
                        message += ` Additionally, ${errorCount} image(s) failed to convert before ZIP creation.`;
                    }
                    showMessage(message, 'error');
                  });
              } else {
                // Single image: download directly; 2-5 images: download one by one (no zip).
                collectedBlobs.forEach((item, idx) => {
                  setTimeout(() => {
                    saveAs(item.blob, item.name);
                  }, idx * 200); // Delay each by 200ms to help browser download.
                });
                let message = `Successfully converted and downloaded ${successCount} image(s).`;
                if (errorCount > 0) {
                  message += ` ${errorCount} image(s) failed to convert.`;
                  showMessage(message, 'error');
                } else {
                  showMessage(message, 'success');
                }
              }
            } else {
              if (errorCount > 0) {
                showMessage(`${errorCount} image(s) failed to convert. Please check individual error messages if any.`, 'error');
              } else if (selectedFiles.length > 0) {
                showMessage('Conversion process completed, but no files were processed successfully or failed explicitly.', 'info');
              }
            }
          }
        });
      });
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
});
</script>









        
        </div>
    </div>
    <?php
}
