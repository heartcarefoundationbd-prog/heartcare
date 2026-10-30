(function () {
  'use strict';

  let endpoint = '/admin/media.php';
  const csrf = () => document.querySelector('input[name="_csrf"]')?.value || '';
  let activeButton = null;
  let currentPage = 1;
  let totalItems = 0;
  const pageSize = 24;

  const dialog = document.createElement('dialog');
  dialog.className = 'a-media-picker-dialog';
  dialog.setAttribute('aria-labelledby', 'a-media-picker-title');
  dialog.innerHTML = `
    <div class="a-media-picker-head">
      <div><h2 id="a-media-picker-title">Choose media</h2><p>Select an existing file or upload a new one.</p></div>
      <button type="button" class="a-media-picker-close" aria-label="Close">×</button>
    </div>
    <div class="a-media-picker-controls">
      <input type="search" class="a-media-search" placeholder="Search files" aria-label="Search files">
      <select class="a-media-kind" aria-label="Filter by file type">
        <option value="">All file types</option><option value="image">Images</option>
        <option value="document">Documents</option><option value="archive">Archives</option>
        <option value="audio">Audio</option><option value="video">Video</option>
      </select>
      <label class="a-media-upload-button">Upload files<input type="file" class="a-media-upload-input" multiple></label>
    </div>
    <p class="a-media-status" role="status" aria-live="polite"></p>
    <div class="a-media-items"></div>
    <div class="a-media-pages"><button type="button" data-page="-1">Previous</button><span></span><button type="button" data-page="1">Next</button></div>
  `;
  document.body.append(dialog);

  const status = dialog.querySelector('.a-media-status');
  const grid = dialog.querySelector('.a-media-items');
  const pageLabel = dialog.querySelector('.a-media-pages span');
  const search = dialog.querySelector('.a-media-search');
  const kindFilter = dialog.querySelector('.a-media-kind');

  function isAllowed(item) {
    if (!activeButton) return false;
    const kind = activeButton.dataset.kind || '';
    const extensions = (activeButton.dataset.extensions || '').split(',').filter(Boolean);
    if (kind && item.kind !== kind) return false;
    return !extensions.length || extensions.includes(item.extension);
  }

  function renderItems(items) {
    grid.replaceChildren();
    const allowed = items.filter(isAllowed);
    if (!allowed.length) {
      status.textContent = items.length ? 'No files match this field.' : 'No media found. Upload a file or change the filters.';
      return;
    }
    status.textContent = '';
    for (const item of allowed) {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'a-media-item';
      button.title = item.name;
      const preview = document.createElement('span');
      preview.className = 'a-media-item-preview';
      if (item.kind === 'image') {
        const image = document.createElement('img');
        image.src = item.url;
        image.alt = '';
        image.loading = 'lazy';
        preview.append(image);
      } else {
        preview.textContent = item.extension.toUpperCase();
      }
      const name = document.createElement('span');
      name.className = 'a-media-item-name';
      name.textContent = item.name;
      button.append(preview, name);
      button.addEventListener('click', () => selectItem(item));
      grid.append(button);
    }
  }

  async function loadItems() {
    if (!activeButton) return;
    status.textContent = 'Loading media…';
    const params = new URLSearchParams({
      ajax: 'list',
      q: search.value.trim(),
      kind: kindFilter.value,
      extensions: activeButton.dataset.extensions || '',
      page: String(currentPage)
    });
    try {
      const response = await fetch(endpoint + '?' + params.toString(), { credentials: 'same-origin' });
      if (!response.ok) throw new Error('Media could not be loaded. Please reload and try again.');
      const data = await response.json();
      totalItems = Number(data.total || 0);
      pageLabel.textContent = totalItems ? `Page ${currentPage} of ${Math.max(1, Math.ceil(totalItems / pageSize))}` : '';
      dialog.querySelector('[data-page="-1"]').disabled = currentPage <= 1;
      dialog.querySelector('[data-page="1"]').disabled = currentPage >= Math.ceil(totalItems / pageSize);
      renderItems(data.items || []);
    } catch (error) {
      status.textContent = error.message;
    }
  }

  function selectionInputs() {
    const form = activeButton.closest('form');
    const name = activeButton.dataset.selectionName;
    return [...(form?.querySelectorAll('input[name^="media_selection["]') || [])]
      .filter(input => input.name === `media_selection[${name}]` || input.name === `media_selection[${name}][]`);
  }

  function selectItem(item) {
    const form = activeButton.closest('form');
    const name = activeButton.dataset.selectionName;
    const multiple = activeButton.dataset.multiple === '1';
    const inputs = selectionInputs();
    if (!multiple) inputs.forEach(input => input.remove());
    const hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = `media_selection[${name}]${multiple ? '[]' : ''}`;
    hidden.value = String(item.id);
    form.append(hidden);

    if (!multiple) {
      let label = activeButton.parentElement.querySelector('.a-media-picker-selection');
      if (!label) {
        label = document.createElement('span');
        label.className = 'a-media-picker-selection';
        activeButton.after(label);
      }
      label.textContent = item.name;
      const fileInput = form.querySelector(`[name="${CSS.escape(name)}"]`);
      if (fileInput && fileInput.type === 'file') fileInput.value = '';
    }
    dialog.close();
  }

  dialog.querySelector('.a-media-picker-close').addEventListener('click', () => dialog.close());
  dialog.querySelectorAll('[data-page]').forEach(button => button.addEventListener('click', () => {
    currentPage += Number(button.dataset.page);
    loadItems();
  }));
  search.addEventListener('input', () => {
    currentPage = 1;
    window.clearTimeout(search._timer);
    search._timer = window.setTimeout(loadItems, 220);
  });
  kindFilter.addEventListener('change', () => {
    currentPage = 1;
    loadItems();
  });
  dialog.querySelector('.a-media-upload-input').addEventListener('change', async event => {
    const files = [...event.target.files];
    if (!files.length) return;
    const payload = new FormData();
    payload.append('ajax', 'upload');
    payload.append('_csrf', csrf());
    files.forEach(file => payload.append('files[]', file));
    status.textContent = 'Uploading files…';
    try {
      const response = await fetch(endpoint, { method: 'POST', body: payload, credentials: 'same-origin' });
      const data = await response.json();
      if (!response.ok || !data.ok) {
        const reasons = (data.errors || []).join(' ');
        throw new Error(data.error || reasons || 'Upload failed.');
      }
      event.target.value = '';
      status.textContent = data.errors?.length ? data.errors.join(' ') : `${data.uploaded.length} file(s) uploaded.`;
      currentPage = 1;
      await loadItems();
      if (data.uploaded.length === 1 && isAllowed(data.uploaded[0])) selectItem(data.uploaded[0]);
    } catch (error) {
      status.textContent = error.message;
    }
  });

  document.addEventListener('click', event => {
    const button = event.target.closest('[data-media-picker-open]');
    if (!button) return;
    activeButton = button;
    endpoint = button.dataset.endpoint || endpoint;
    search.value = '';
    kindFilter.value = button.dataset.kind || '';
    currentPage = 1;
    dialog.showModal();
    loadItems();
  });

  document.addEventListener('change', event => {
    if (event.target.matches('input[type="file"]:not(.a-media-upload-input)')) {
      const form = event.target.closest('form');
      const field = event.target.name.replace(/\[\]$/, '');
      [...(form?.querySelectorAll('input[name^="media_selection["]') || [])]
        .filter(input => input.name === `media_selection[${field}]` || input.name === `media_selection[${field}][]`)
        .forEach(input => input.remove());
      event.target.closest('.a-field')?.querySelector('.a-media-picker-selection')?.remove();
    }
  });
})();
