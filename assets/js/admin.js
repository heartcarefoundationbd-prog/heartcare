/* HeartCare admin: drawer menu, confirm dialog, rich-text editor, image preview + resize, unsaved-changes guard */
(function () {
  'use strict';

  /* ---------- Drawer menu ---------- */
  var btn = document.querySelector('[data-a-menu]');
  var side = document.getElementById('a-side');
  var overlay = document.querySelector('[data-a-overlay]');
  if (btn && side) {
    var setMenu = function (open, refocus) {
      side.classList.toggle('open', open);
      if (overlay) { overlay.hidden = !open; }
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      btn.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
      var ic = btn.querySelector('span'); if (ic) { ic.textContent = open ? '×' : '☰'; }
      document.body.style.overflow = open ? 'hidden' : '';
      if (open) { var first = side.querySelector('a'); if (first) { first.focus(); } }
      else if (refocus) { btn.focus(); }
    };
    btn.addEventListener('click', function () { setMenu(!side.classList.contains('open'), false); });
    if (overlay) { overlay.addEventListener('click', function () { setMenu(false, true); }); }
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && side.classList.contains('open')) { setMenu(false, true); }
    });
    window.matchMedia('(min-width: 900px)').addEventListener('change', function (m) { if (m.matches) { setMenu(false, false); } });
  }

  /* ---------- Confirm dialog for destructive forms ---------- */
  var dlg = document.getElementById('a-confirm');
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form.matches || !form.matches('form[data-confirm]')) { return; }
    if (form.dataset.confirmed === '1') { return; }
    e.preventDefault();
    var msg = form.getAttribute('data-confirm');
    if (!dlg || typeof dlg.showModal !== 'function') {
      if (window.confirm(msg)) { form.dataset.confirmed = '1'; form.submit(); }
      return;
    }
    document.getElementById('a-confirm-text').textContent = msg;
    dlg.returnValue = '';
    dlg.addEventListener('close', function onClose() {
      dlg.removeEventListener('close', onClose);
      if (dlg.returnValue === 'ok') { form.dataset.confirmed = '1'; form.submit(); }
    });
    dlg.showModal();
  });

  /* ---------- Rich-text editor (progressive enhancement over the textarea) ---------- */
  var TOOLS = [
    ['bold', 'B', 'Bold'], ['italic', 'I', 'Italic'], ['h2', 'H2', 'Heading'], ['h3', 'H3', 'Subheading'],
    ['ul', '•', 'Bulleted list'], ['ol', '1.', 'Numbered list'], ['quote', '❝', 'Quote'],
    ['link', '🔗', 'Add link'], ['unlink', '⛓', 'Remove link'], ['clear', '⌫', 'Clear formatting'], ['source', '</>', 'Edit HTML']
  ];
  document.querySelectorAll('[data-rte]').forEach(function (box) {
    var area = box.querySelector('.rte-area');
    var src = box.querySelector('.rte-src');
    var bar = box.querySelector('.rte-bar');
    if (!area || !src || !bar || !document.execCommand) { return; }
    box.classList.add('js'); area.hidden = false;
    area.innerHTML = src.value;
    area.setAttribute('data-placeholder', 'Write here…');
    if (src.lang) { area.lang = src.lang; }

    TOOLS.forEach(function (t) {
      var b = document.createElement('button');
      b.type = 'button'; b.textContent = t[1]; b.setAttribute('aria-label', t[2]); b.title = t[2];
      b.addEventListener('mousedown', function (e) { e.preventDefault(); });   // keep the text selection
      b.addEventListener('click', function () {
        var c = t[0];
        if (c === 'source') {
          var on = !box.classList.contains('source');
          if (on) { src.value = area.innerHTML; } else { area.innerHTML = src.value; }
          box.classList.toggle('source', on); b.setAttribute('aria-pressed', on ? 'true' : 'false');
          return;
        }
        area.focus();
        if (c === 'h2' || c === 'h3') { document.execCommand('formatBlock', false, c.toUpperCase()); }
        else if (c === 'quote') { document.execCommand('formatBlock', false, 'BLOCKQUOTE'); }
        else if (c === 'ul') { document.execCommand('insertUnorderedList'); }
        else if (c === 'ol') { document.execCommand('insertOrderedList'); }
        else if (c === 'clear') { document.execCommand('removeFormat'); document.execCommand('formatBlock', false, 'P'); }
        else if (c === 'link') {
          var url = window.prompt('Link address (https://…)', 'https://');
          if (url && /^(https?:\/\/|mailto:|tel:)/i.test(url)) { document.execCommand('createLink', false, url); }
        } else { document.execCommand(c); }
      });
      bar.appendChild(b);
    });

    var sync = function () { if (!box.classList.contains('source')) { src.value = area.innerHTML; } };
    area.addEventListener('input', sync);
    area.addEventListener('paste', function (e) {            // paste as plain text: no foreign styles
      e.preventDefault();
      var text = (e.clipboardData || window.clipboardData).getData('text/plain');
      document.execCommand('insertText', false, text);
    });
    box.closest('form').addEventListener('submit', sync);
  });

  /* ---------- Image preview + shrink big phone photos before upload ---------- */
  var MAX = 1800;
  document.querySelectorAll('[data-image-input]').forEach(function (input) {
    var wrap = input.parentNode.querySelector('[data-new-preview]');
    input.addEventListener('change', function () {
      var f = input.files && input.files[0];
      if (!f || !/^image\/(jpeg|png|webp)$/.test(f.type)) { if (wrap) { wrap.hidden = true; } return; }
      var url = URL.createObjectURL(f);
      if (wrap) { wrap.hidden = false; wrap.querySelector('img').src = url; }
      if (f.size < 1.5 * 1024 * 1024 || !window.DataTransfer) { return; }
      var img = new Image();
      img.onload = function () {
        var s = Math.min(1, MAX / Math.max(img.width, img.height));
        var c = document.createElement('canvas');
        c.width = Math.round(img.width * s); c.height = Math.round(img.height * s);
        c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
        c.toBlob(function (blob) {
          if (!blob || blob.size >= f.size) { return; }
          try {
            var dt = new DataTransfer();
            dt.items.add(new File([blob], f.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' }));
            input.files = dt.files;
          } catch (err) { /* keep the original file */ }
        }, 'image/jpeg', 0.86);
      };
      img.src = url;
    });
  });
  document.querySelectorAll('input[type="file"][multiple]').forEach(function (input) {
    var preview = input.parentNode.querySelector('[data-multi-preview]');
    if (!preview) { return; }
    input.addEventListener('change', function () {
      Array.prototype.forEach.call(preview.querySelectorAll('img'), function (image) {
        URL.revokeObjectURL(image.src);
      });
      preview.replaceChildren();
      Array.prototype.forEach.call(input.files || [], function (file) {
        if (!/^image\/(jpeg|png|webp)$/.test(file.type)) { return; }
        var image = document.createElement('img');
        image.alt = file.name;
        image.src = URL.createObjectURL(file);
        preview.appendChild(image);
      });
    });
  });

  /* ---------- Repeatable home-card statistics and CTA rows ---------- */
  document.querySelectorAll('[data-card-add]').forEach(function (button) {
    button.addEventListener('click', function () {
      var type = button.getAttribute('data-card-add');
      var form = button.closest('form');
      var rows = form && form.querySelector('[data-card-rows="' + type + '"]');
      var template = form && form.querySelector('template[data-card-template="' + type + '"]');
      if (!rows || !template) { return; }
      var next = Number.parseInt(rows.dataset.nextIndex || '0', 10);
      if (!Number.isInteger(next)) { next = 0; }
      rows.dataset.nextIndex = String(next + 1);
      rows.insertAdjacentHTML('beforeend', template.innerHTML.replace(/__INDEX__/g, String(next)));
      var first = rows.lastElementChild && rows.lastElementChild.querySelector('input:not([type="hidden"]), select');
      if (first) { first.focus(); }
    });
  });
  document.addEventListener('click', function (event) {
    var remove = event.target.closest('[data-card-remove]');
    if (remove) {
      var row = remove.closest('[data-card-row]');
      if (row) { row.remove(); }
    }
  });
  document.querySelectorAll('[data-card-rows]').forEach(function (rows) {
    var max = -1;
    rows.querySelectorAll('[name]').forEach(function (field) {
      var match = field.name.match(/\[(\d+)\]$/);
      if (match) { max = Math.max(max, Number.parseInt(match[1], 10)); }
    });
    rows.dataset.nextIndex = String(max + 1);
  });

  /* ---------- Warn before leaving a form with unsaved changes ---------- */
  document.querySelectorAll('form[data-dirty-guard]').forEach(function (form) {
    var dirty = false;
    form.addEventListener('input', function () { dirty = true; });
    form.addEventListener('change', function () { dirty = true; });
    form.addEventListener('submit', function () { dirty = false; });
    window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
  });
})();
