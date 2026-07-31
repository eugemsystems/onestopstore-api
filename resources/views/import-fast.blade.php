<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Products Import Fast</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 20px; background: #f7f7fb; color: #222; }
    .container { max-width: 1200px; margin: 0 auto; background: #fff; border-radius: 12px; box-shadow: 0 4px 18px rgba(0,0,0,.06); overflow: hidden; }
    .header { padding: 16px 20px; background: #0f172a; color: #fff; display: flex; justify-content: space-between; align-items: center; }
    .header h1 { margin: 0; font-size: 18px; }
    .content { padding: 20px; }
    .row { display: flex; gap: 16px; align-items: center; flex-wrap: wrap; }
    .row > * { flex: 1; }
    label { display: block; font-weight: 600; margin-bottom: 6px; }
    input[type="number"], input[type="text"], input[type="file"] { width: 100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 8px; background: #fff; box-sizing: border-box; }
    input[type="file"] { padding: 8px; }
    .btn { display: inline-block; padding: 10px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; border: 0; cursor: pointer; transition: all .2s ease; margin-right: 8px; }
    .btn-primary { background: #2563eb; color: #fff; }
    .btn-primary:disabled { background: #94a3b8; cursor: not-allowed; }
    .btn-secondary { background: #64748b; color: #fff; }
    .btn-secondary:hover { background: #475569; text-decoration: none; }
    a.btn { color: #fff; } /* Ensure anchor button text is white */
    .btn-secondary:disabled { background: #cbd5e1; cursor: not-allowed; }
    .btn-danger { background: #dc2626; color: #fff; }
    .btn-success { background: #16a34a; color: #fff; }
    .btn-sm { padding: 6px 12px; font-size: 13px; }
    .muted { color: #6b7280; font-size: 12px; }
    .progress { width: 100%; background: #e5e7eb; height: 10px; border-radius: 100px; overflow: hidden; }
    .progress > span { display: block; height: 10px; background: linear-gradient(90deg, #22c55e, #16a34a); width: 0; transition: width .2s ease; }
    .output { background: #0b1020; color: #d1d5db; padding: 12px; border-radius: 8px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace; font-size: 12px; white-space: pre-wrap; max-height: 520px; overflow: auto; }
    .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .spinner { display: inline-block; width: 14px; height: 14px; border: 2px solid rgba(255,255,255,.3); border-radius: 50%; border-top-color: #fff; animation: spin 0.6s linear infinite; margin-left: 8px; vertical-align: middle; }
    @keyframes spin { to { transform: rotate(360deg); } }
    .btn-content { display: inline-flex; align-items: center; }
    .file-list { max-height: 300px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 8px; background: #f9fafb; }
    .file-item { padding: 12px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; gap: 12px; transition: background .15s; }
    .file-item:hover { background: #f3f4f6; }
    .file-item:last-child { border-bottom: 0; }
    .file-item input[type="checkbox"] { width: auto; margin: 0; cursor: pointer; }
    .file-info { flex: 1; }
    .file-name { font-weight: 500; font-size: 14px; color: #111827; margin-bottom: 4px; }
    .file-meta { font-size: 12px; color: #6b7280; }
    .empty-state { padding: 40px; text-align: center; color: #6b7280; }
    .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
    .badge-info { background: #dbeafe; color: #1e40af; }
    .section { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
    .section-title { font-weight: 600; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>Products Import Fast</h1>
      <div style="display: flex; gap: 8px; align-items: center;">
        <a href="/api/import-fast/history" class="btn btn-sm btn-secondary" style="white-space: nowrap;">📊 View History</a>
        <button class="btn btn-sm btn-secondary" onclick="location.reload()" style="white-space: nowrap;">🔄 Refresh Files</button>
      </div>
    </div>
    <div class="content">
      <p class="muted">Select existing JSON files from the list below or upload new files. Set percentage and today's rate, then run the import.</p>

      <div class="section">
        <div class="section-title">
          <span>📁 Existing JSON Files</span>
          <span class="badge badge-info" id="fileCount">0 files</span>
        </div>
        <div class="file-list" id="fileList">
          <div class="empty-state">No JSON files found in storage/app/jsonfiles</div>
        </div>
        <div style="margin-top: 12px; display: flex; gap: 8px;">
          <button class="btn btn-sm btn-success" id="btnSelectAll">Select All</button>
          <button class="btn btn-sm btn-secondary" id="btnDeselectAll">Deselect All</button>
          <button class="btn btn-sm btn-danger" id="btnDeleteSelected">Delete Selected</button>
        </div>
      </div>

      <div class="grid">
        <div class="section">
          <div class="section-title">📤 Upload New File</div>
          <input type="file" id="jsonfile" accept=".json,.txt" />
          <div class="muted" style="margin-top: 8px;">Large files supported. Will be uploaded to storage/app/jsonfiles.</div>
          <div style="margin:10px 0">
            <div class="progress"><span id="prog"></span></div>
          </div>
          <button class="btn btn-secondary" id="btnUpload">Upload</button>
          <div id="uploadStatus" class="muted" style="margin-top:6px"></div>
        </div>
        <div class="section">
          <div class="section-title">⚙️ Import Settings</div>
          <div class="row">
            <div>
              <label for="percentage">Percentage</label>
              <input required type="number" step="0.01" id="percentage" placeholder="e.g. 1" />
              <div class="muted">Required. No defaults.</div>
            </div>
            <div>
              <label for="todaysRate">Today's Rate</label>
              <input required type="number" step="0.0001" id="todaysRate" placeholder="e.g. 18" />
              <div class="muted">Required. No defaults.</div>
            </div>
          </div>
          <div style="margin-top:14px">
            <button class="btn btn-primary" id="btnRun">
              <span class="btn-content">
                <span id="btnRunText">Run Import on Selected Files</span>
                <span id="btnRunSpinner" class="spinner" style="display:none;"></span>
              </span>
            </button>
          </div>
        </div>
      </div>

      <h3 style="margin-top:24px">Live Output</h3>
      <div class="output" id="out"></div>
    </div>
  </div>

<script>
const existingFiles = @json($existingFiles);

(function(){
  const elFile = document.getElementById('jsonfile');
  const elProg = document.getElementById('prog');
  const elUpload = document.getElementById('btnUpload');
  const elStatus = document.getElementById('uploadStatus');
  const elOut = document.getElementById('out');
  const elRun = document.getElementById('btnRun');
  const elRunText = document.getElementById('btnRunText');
  const elRunSpinner = document.getElementById('btnRunSpinner');
  const elPct = document.getElementById('percentage');
  const elRate = document.getElementById('todaysRate');
  const elFileList = document.getElementById('fileList');
  const elFileCount = document.getElementById('fileCount');
  const elSelectAll = document.getElementById('btnSelectAll');
  const elDeselectAll = document.getElementById('btnDeselectAll');
  const elDeleteSelected = document.getElementById('btnDeleteSelected');

  // Render file list
  function renderFileList() {
    if (existingFiles.length === 0) {
      elFileList.innerHTML = '<div class="empty-state">No JSON files found in storage/app/jsonfiles</div>';
      elFileCount.textContent = '0 files';
      return;
    }

    elFileCount.textContent = existingFiles.length + (existingFiles.length === 1 ? ' file' : ' files');

    let html = '';
    existingFiles.forEach((file, index) => {
      const sizeKB = (file.size / 1024).toFixed(2);
      const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
      const sizeDisplay = file.size > 1024 * 1024 ? sizeMB + ' MB' : sizeKB + ' KB';

      html += `
        <div class="file-item">
          <input type="checkbox" class="file-checkbox" data-filename="${file.name}" id="file_${index}">
          <label for="file_${index}" style="flex: 1; cursor: pointer; margin: 0;">
            <div class="file-info">
              <div class="file-name">${file.name}</div>
              <div class="file-meta">${sizeDisplay} • ${file.date}</div>
            </div>
          </label>
        </div>
      `;
    });

    elFileList.innerHTML = html;
  }

  renderFileList();

  // Select/Deselect all
  elSelectAll.addEventListener('click', function() {
    document.querySelectorAll('.file-checkbox').forEach(cb => cb.checked = true);
  });

  elDeselectAll.addEventListener('click', function() {
    document.querySelectorAll('.file-checkbox').forEach(cb => cb.checked = false);
  });

  // Delete selected files
  elDeleteSelected.addEventListener('click', async function() {
    const selected = Array.from(document.querySelectorAll('.file-checkbox:checked'))
      .map(cb => cb.dataset.filename);

    if (selected.length === 0) {
      showWarning('No Selection', 'No files selected');
      return;
    }

    const _r = await Swal.fire({ title: 'Are you sure?', text: `Delete ${selected.length} file(s)? This cannot be undone.`, icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
    if (!_r.isConfirmed) return;

    try {
      const resp = await fetch('/api/import-fast/delete-files', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ files: selected })
      });

      const result = await resp.json();
      showSuccess('Deleted', result.message || 'Files deleted');
      location.reload();
    } catch (e) {
      showError('Error', 'Failed to delete files: ' + e.message);
    }
  });

  elUpload.addEventListener('click', async function(){
    const file = elFile.files[0];
    if(!file){ showWarning('No File', 'Choose a JSON file first.'); return; }

    elUpload.disabled = true;
    elProg.style.width = '0%';
    elStatus.textContent = 'Uploading...';

    const chunkSize = 512 * 1024;
    const totalChunks = Math.ceil(file.size / chunkSize);
    const uploadId = `${Date.now()}-${Math.random().toString(36).slice(2)}`;
    const fileName = file.name;

    let uploaded = 0;
    const uploadChunk = (index, blob) => new Promise((resolve, reject) => {
      const fd = new FormData();
      fd.append('uploadId', uploadId);
      fd.append('fileName', fileName);
      fd.append('chunkIndex', String(index));
      fd.append('totalChunks', String(totalChunks));
      fd.append('chunk', blob, `${fileName}.part${index}`);

      const xhr = new XMLHttpRequest();
      xhr.open('POST', '/api/import-fast/upload-chunk', true);
      xhr.onload = () => (xhr.status >= 200 && xhr.status < 300) ? resolve() : reject(new Error('Chunk upload failed: '+xhr.status));
      xhr.onerror = () => reject(new Error('Network error'));
      xhr.send(fd);
    });

    try {
      for (let i = 0; i < totalChunks; i++) {
        const start = i * chunkSize;
        const end = Math.min(start + chunkSize, file.size);
        const blob = file.slice(start, end);
        await uploadChunk(i, blob);
        uploaded++;
        const pct = Math.round((uploaded / totalChunks) * 100);
        elProg.style.width = pct + '%';
        elStatus.textContent = `Uploading... ${pct}%`;
      }

      const params = new URLSearchParams();
      params.append('uploadId', uploadId);
      params.append('fileName', fileName);
      params.append('totalChunks', String(totalChunks));

      const resp = await fetch('/api/import-fast/upload-complete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params.toString(),
      });
      if (!resp.ok) throw new Error('Finalize failed '+resp.status);
      const js = await resp.json();
      elStatus.textContent = 'Uploaded: ' + (js.stored_as || fileName) + ' - Refreshing...';
      setTimeout(() => location.reload(), 1000);
    } catch (e) {
      elStatus.textContent = 'Upload failed: ' + (e?.message || 'unknown');
    } finally {
      elUpload.disabled = false;
    }
  });

  elRun.addEventListener('click', function(){
    const pct = elPct.value.trim();
    const rate = elRate.value.trim();

    if(!pct || !rate) {
      showWarning('Required Fields', 'Please enter both Percentage and Today\'s Rate');
      return;
    }

    const selected = Array.from(document.querySelectorAll('.file-checkbox:checked'))
      .map(cb => cb.dataset.filename);

    if (selected.length === 0) {
      showWarning('No Selection', 'Please select at least one JSON file to import');
      return;
    }

    // Show loading state
    elRun.disabled = true;
    elRunText.textContent = 'Processing...';
    elRunSpinner.style.display = 'inline-block';
    elOut.textContent = `Starting import of ${selected.length} file(s)...\n`;

    const params = new URLSearchParams();
    params.append('percentage', pct);
    params.append('todaysRate', rate);
    params.append('selectedFiles', JSON.stringify(selected));

    const xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/import-fast/run', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange = function(){
      if(xhr.readyState === 3 || xhr.readyState === 4){
        elOut.innerHTML = xhr.responseText;
        elOut.scrollTop = elOut.scrollHeight;

        if(xhr.readyState === 4) {
          elRun.disabled = false;
          elRunText.textContent = 'Run Import on Selected Files';
          elRunSpinner.style.display = 'none';
        }
      }
    };

    xhr.onerror = function() {
      elRun.disabled = false;
      elRunText.textContent = 'Run Import on Selected Files';
      elRunSpinner.style.display = 'none';
      elOut.innerHTML += '<br><span style="color:#ef4444;">Error: Request failed</span>';
    };

    xhr.send(params.toString());
  });
})();
</script>
</body>
</html>

