/**
 * SNN Image Optimizer core.
 *
 * Canvas based resize + re-encode shared by the "Optimize Media" admin page
 * and the "Optimize & Upload" tab inside the wp.media modal.
 */
(function (window) {
  'use strict';

  var STORAGE_KEY = 'snnOptimizeMediaSettings';
  var FORMATS = ['image/webp', 'image/jpeg', 'image/png'];
  // Canvas would rasterize SVGs and flatten animated GIFs, so those pass through untouched.
  var CONVERTIBLE = ['image/jpeg', 'image/jpg', 'image/pjpeg', 'image/png', 'image/webp', 'image/bmp', 'image/avif'];

  var DEFAULTS = { format: 'image/jpeg', quality: 85, width: '' };

  function loadSettings() {
    var settings = { format: DEFAULTS.format, quality: DEFAULTS.quality, width: DEFAULTS.width };
    var saved = null;
    try {
      saved = JSON.parse(window.localStorage.getItem(STORAGE_KEY) || 'null');
    } catch (err) {
      saved = null;
    }
    if (saved) {
      if (FORMATS.indexOf(saved.format) > -1) {
        settings.format = saved.format;
      }
      var q = parseInt(saved.quality, 10);
      if (!isNaN(q) && q >= 10 && q <= 100) {
        settings.quality = q;
      }
      var w = parseInt(saved.width, 10);
      if (!isNaN(w) && w > 0) {
        settings.width = String(w);
      }
    }
    return settings;
  }

  function saveSettings(settings) {
    try {
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify({
        format:  settings.format,
        quality: settings.quality,
        width:   settings.width
      }));
    } catch (err) {
      /* storage unavailable: settings simply are not remembered */
    }
  }

  // Turns the stored UI values into what convertFile() expects.
  function toConvertOptions(settings) {
    var width = parseInt(settings.width, 10);
    var quality;
    if (settings.format !== 'image/png') {
      var q = parseInt(settings.quality, 10);
      quality = (isNaN(q) ? 85 : q) / 100;
    }
    return {
      width: (!isNaN(width) && width > 0) ? width : null,
      format: settings.format,
      quality: quality
    };
  }

  function canConvert(file) {
    return !!file && CONVERTIBLE.indexOf((file.type || '').toLowerCase()) > -1;
  }

  function convertImage(imageUrl, originalFileName, options, resolve) {
    var img = new Image();
    img.onload = function () {
      var canvas = document.createElement('canvas');
      var scale = 1;
      if (options.width && img.width > options.width) {
        scale = options.width / img.width;
      }
      canvas.width  = Math.max(1, Math.round(img.width * scale));
      canvas.height = Math.max(1, Math.round(img.height * scale));

      var ctx = canvas.getContext('2d');
      if (options.format === 'image/jpeg') {
        ctx.fillStyle = '#FFFFFF';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
      }
      ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

      var done = function (blob) {
        if (!blob) { resolve(null); return; }
        var base = originalFileName.substring(0, originalFileName.lastIndexOf('.')) || originalFileName;
        var ext  = (options.format === 'image/jpeg') ? 'jpg' : options.format.split('/')[1];
        resolve({ blob: blob, name: base + '.' + ext, width: canvas.width, height: canvas.height });
      };

      var args = [done, options.format];
      if (options.quality !== undefined && options.format !== 'image/png') {
        args.push(options.quality);
      }
      try {
        canvas.toBlob.apply(canvas, args);
      } catch (error) {
        resolve(null);
      }
    };
    img.onerror = function () { resolve(null); };
    img.src = imageUrl;
  }

  // Resolves { blob, name, width, height } or null when the file could not be converted.
  function convertFile(file, options) {
    return new Promise(function (resolve) {
      var reader = new FileReader();
      reader.onload  = function (event) { convertImage(event.target.result, file.name, options, resolve); };
      reader.onerror = function () { resolve(null); };
      reader.readAsDataURL(file);
    });
  }

  function formatBytes(bytes) {
    if (bytes < 1024) { return bytes + ' B'; }
    if (bytes < 1048576) { return Math.round(bytes / 1024) + ' KB'; }
    return (bytes / 1048576).toFixed(1) + ' MB';
  }

  window.SnnImageOptimizer = {
    STORAGE_KEY: STORAGE_KEY,
    loadSettings: loadSettings,
    saveSettings: saveSettings,
    toConvertOptions: toConvertOptions,
    canConvert: canConvert,
    convertFile: convertFile,
    formatBytes: formatBytes
  };
})(window);
