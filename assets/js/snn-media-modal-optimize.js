/**
 * "Optimize & Upload" tab for the wp.media modal.
 *
 * Patches MediaFrame.Select (MediaFrame.Post extends it), so every modal that
 * shows the "Upload files | Media Library" router gets the tab: featured image,
 * Add Media, Bricks, ACF, ...
 *
 * Optimized files are handed to the frame's own uploader, so they travel the
 * native async-upload.php path, show the modal's progress, land in the library
 * and get selected just like a regular upload.
 */
(function ($, wp, Optimizer, i18n) {
  'use strict';

  if (!wp || !wp.media || !wp.media.view || !wp.media.view.MediaFrame || !Optimizer) {
    return;
  }

  var TAB = 'snn-optimize';
  var Select = wp.media.view.MediaFrame.Select;
  var FORMATS = [
    { value: 'image/webp', label: 'WebP' },
    { value: 'image/jpeg', label: 'JPEG' },
    { value: 'image/png',  label: 'PNG' }
  ];
  var PRESETS = [
    { value: '2560', label: '2560' },
    { value: '1920', label: '1920' },
    { value: '1280', label: '1280' },
    { value: '',     label: i18n.full }
  ];

  function el(tag, className, text) {
    var node = document.createElement(tag);
    if (className) { node.className = className; }
    if (text !== undefined) { node.textContent = text; }
    return node;
  }

  function nativeUploader(frame) {
    // UploaderWindow view -> wp.Uploader -> plupload.Uploader
    var view = frame.uploader;
    return (view && view.uploader && view.uploader.uploader) ? view.uploader.uploader : null;
  }

  var OptimizeView = wp.media.View.extend({
    className: 'snn-optimize-tab',

    initialize: function () {
      this.settings = Optimizer.loadSettings();
      this.busy = false;
    },

    render: function () {
      var self = this;
      var root = this.el;
      root.innerHTML = '';

      var inner = el('div', 'snn-mo-inner');
      root.appendChild(inner);

      /* ---------- Settings ---------- */
      var card = el('div', 'snn-mo-settings');
      inner.appendChild(card);

      var formatField = el('div', 'snn-mo-field');
      formatField.appendChild(el('span', 'snn-mo-label', i18n.format));
      var seg = el('div', 'snn-mo-segmented');
      seg.setAttribute('role', 'radiogroup');
      seg.setAttribute('aria-label', i18n.format);
      FORMATS.forEach(function (f) {
        var b = el('button', '', f.label);
        b.type = 'button';
        b.setAttribute('role', 'radio');
        b.dataset.format = f.value;
        b.onclick = function () {
          self.settings.format = f.value;
          self.persist();
        };
        seg.appendChild(b);
      });
      formatField.appendChild(seg);
      card.appendChild(formatField);

      var qualityField = el('div', 'snn-mo-field snn-mo-quality');
      qualityField.appendChild(el('span', 'snn-mo-label', i18n.quality));
      var sliderRow = el('div', 'snn-mo-row');
      var range = el('input');
      range.type = 'range';
      range.min = '10';
      range.max = '100';
      range.step = '1';
      range.setAttribute('aria-label', i18n.quality);
      var chip = el('span', 'snn-mo-chip');
      range.oninput = function () {
        self.settings.quality = parseInt(range.value, 10);
        self.sync();
      };
      range.onchange = function () { self.persist(); };
      sliderRow.appendChild(range);
      sliderRow.appendChild(chip);
      qualityField.appendChild(sliderRow);
      card.appendChild(qualityField);

      var widthField = el('div', 'snn-mo-field');
      widthField.appendChild(el('span', 'snn-mo-label', i18n.maxWidth));
      var widthRow = el('div', 'snn-mo-row');
      var widthWrap = el('span', 'snn-mo-width-wrap');
      var width = el('input');
      width.type = 'number';
      width.min = '1';
      width.step = '1';
      width.placeholder = i18n.original;
      width.setAttribute('aria-label', i18n.maxWidth);
      width.oninput = function () {
        self.settings.width = width.value;
        self.sync();
      };
      width.onchange = function () { self.persist(); };
      widthWrap.appendChild(width);
      widthWrap.appendChild(el('span', 'snn-mo-suffix', 'px'));
      widthRow.appendChild(widthWrap);
      PRESETS.forEach(function (p) {
        var b = el('button', 'snn-mo-preset', p.label);
        b.type = 'button';
        b.dataset.width = p.value;
        b.onclick = function () {
          self.settings.width = p.value;
          self.persist();
        };
        widthRow.appendChild(b);
      });
      widthField.appendChild(widthRow);
      card.appendChild(widthField);

      /* ---------- Drop zone ---------- */
      var input = el('input');
      input.type = 'file';
      input.multiple = true;
      input.accept = i18n.accept || 'image/*';
      input.hidden = true;
      input.onchange = function () {
        self.handleFiles(input.files);
        input.value = '';
      };

      var drop = el('button', 'snn-mo-drop');
      drop.type = 'button';
      drop.innerHTML = '<span class="snn-mo-drop-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg></span>';
      var dropText = el('span', 'snn-mo-drop-text');
      dropText.appendChild(el('strong', '', i18n.dropHere));
      dropText.appendChild(document.createTextNode(' ' + i18n.orBrowse));
      drop.appendChild(dropText);
      drop.onclick = function () {
        if (!self.busy) { input.click(); }
      };
      // Stop the frame-wide uploader dropzone from grabbing the raw files.
      drop.addEventListener('dragover', function (e) {
        e.preventDefault();
        e.stopPropagation();
        drop.classList.add('snn-mo-drag-over');
      });
      drop.addEventListener('dragleave', function () {
        drop.classList.remove('snn-mo-drag-over');
      });
      drop.addEventListener('drop', function (e) {
        e.preventDefault();
        e.stopPropagation();
        drop.classList.remove('snn-mo-drag-over');
        self.handleFiles(e.dataTransfer && e.dataTransfer.files);
      });

      inner.appendChild(input);
      inner.appendChild(drop);
      inner.appendChild(el('p', 'snn-mo-hint', i18n.hint));

      var status = el('div', 'snn-mo-status');
      status.setAttribute('role', 'status');
      inner.appendChild(status);

      this.ui = {
        formatButtons: Array.prototype.slice.call(seg.querySelectorAll('button')),
        presetButtons: Array.prototype.slice.call(widthRow.querySelectorAll('.snn-mo-preset')),
        qualityField: qualityField,
        range: range,
        chip: chip,
        width: width,
        drop: drop,
        status: status
      };
      this.sync();
      return this;
    },

    sync: function () {
      var s = this.settings;
      var ui = this.ui;
      ui.formatButtons.forEach(function (b) {
        b.setAttribute('aria-checked', b.dataset.format === s.format ? 'true' : 'false');
      });
      ui.range.value = s.quality;
      ui.chip.textContent = s.quality + '%';
      ui.qualityField.classList.toggle('snn-mo-disabled', s.format === 'image/png');
      if (ui.width.value !== String(s.width)) {
        ui.width.value = s.width;
      }
      ui.presetButtons.forEach(function (b) {
        b.classList.toggle('snn-mo-active', b.dataset.width === String(s.width));
      });
      ui.drop.disabled = this.busy;
    },

    persist: function () {
      this.sync();
      Optimizer.saveSettings(this.settings);
    },

    setStatus: function (text, type) {
      this.ui.status.textContent = text || '';
      this.ui.status.className = 'snn-mo-status' + (type ? ' snn-mo-' + type : '');
    },

    handleFiles: async function (fileList) {
      var files = Array.prototype.slice.call(fileList || []);
      if (!files.length || this.busy) { return; }

      var uploader = nativeUploader(this.controller);
      if (!uploader) {
        this.setStatus(i18n.noUploader, 'error');
        return;
      }

      this.busy = true;
      this.sync();

      var options = Optimizer.toConvertOptions(this.settings);
      var ready = [];
      var failed = 0;
      var before = 0;
      var after = 0;

      for (var i = 0; i < files.length; i++) {
        var file = files[i];
        this.setStatus(i18n.optimizing + ' ' + (i + 1) + '/' + files.length + ': ' + file.name);

        if (!Optimizer.canConvert(file)) {
          // SVG, GIF, PDF, video...: upload as is.
          ready.push(file);
          continue;
        }

        var converted = null;
        try {
          converted = await Optimizer.convertFile(file, options);
        } catch (err) {
          converted = null;
        }
        if (!converted) {
          failed++;
          continue;
        }
        before += file.size;
        after += converted.blob.size;
        ready.push(new File([converted.blob], converted.name, { type: converted.blob.type || options.format }));
      }

      this.busy = false;

      if (!ready.length) {
        this.sync();
        this.setStatus(i18n.noneDone, 'error');
        return;
      }

      var message = ready.length + ' ' + i18n.uploading;
      if (before > 0) {
        message += ' (' + Optimizer.formatBytes(before) + ' → ' + Optimizer.formatBytes(after) + ')';
      }
      if (failed) {
        message += ' · ' + failed + ' ' + i18n.failed;
      }
      this.setStatus(message, failed ? 'error' : 'success');

      // The library state flips back to "browse" and selects each upload.
      this.controller.content.mode('browse');
      ready.forEach(function (f) { uploader.addFile(f); });
    }
  });

  var originalBrowseRouter = Select.prototype.browseRouter;
  Select.prototype.browseRouter = function (routerView) {
    originalBrowseRouter.apply(this, arguments);
    routerView.set(TAB, { text: i18n.tab, priority: 60 });
  };

  var originalBindHandlers = Select.prototype.bindHandlers;
  Select.prototype.bindHandlers = function () {
    originalBindHandlers.apply(this, arguments);
    this.on('content:render:' + TAB, function () {
      this.content.set(new OptimizeView({ controller: this }));
    }, this);
    // Force Optimized Uploads: the native "Upload files" tab is hidden with CSS,
    // so whenever the frame opens on it (empty library, Add Media...) jump to ours.
    if (i18n.hideNative) {
      this.on('content:render:upload', function () {
        this.content.mode(TAB);
      }, this);
    }
  };
})(jQuery, window.wp, window.SnnImageOptimizer, window.snnMediaModalOptimize || {});
