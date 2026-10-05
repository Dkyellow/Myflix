/**
 * MyFlix Movie Upload
 * Reads the video in the browser first (duration + poster frame), then posts
 * it as multipart/form-data with an upload progress bar.
 */
const Upload = {
  file: null,
  posterBlob: null,
  duration: 0,
  objectUrl: null,
  posterCaptured: false,
  posterTimer: null,
  busy: false
};

function openUploadModal() {
  resetUploadForm();
  openModal('modal-upload');
}

function resetUploadForm() {
  const input = document.getElementById('upload-video-input');
  const form = document.getElementById('upload-movie-form');
  if (!input || !form) return;

  input.value = '';
  form.reset();

  Upload.file = null;
  Upload.posterBlob = null;
  Upload.duration = 0;
  Upload.posterCaptured = false;
  clearTimeout(Upload.posterTimer);

  if (Upload.objectUrl) {
    URL.revokeObjectURL(Upload.objectUrl);
    Upload.objectUrl = null;
  }

  document.getElementById('upload-preview')?.classList.add('hidden');
  document.getElementById('upload-picker')?.classList.remove('hidden');
  setUploadProgress(null);
  setUploadBusy(false);
}

function setUploadBusy(busy) {
  Upload.busy = busy;
  const btn = document.getElementById('upload-submit-btn');
  if (btn) {
    btn.disabled = busy;
    btn.innerHTML = busy
      ? '<i class="ph-bold ph-spinner"></i> Uploading...'
      : '<i class="ph-bold ph-upload-simple"></i> Upload Movie';
  }
}

function setUploadProgress(pct) {
  const bar = document.getElementById('upload-progress');
  const fill = document.getElementById('upload-progress-bar');
  const text = document.getElementById('upload-progress-text');
  if (!bar || !fill || !text) return;

  if (pct === null) {
    bar.classList.add('hidden');
    text.classList.add('hidden');
    fill.style.width = '0%';
    return;
  }

  bar.classList.remove('hidden');
  text.classList.remove('hidden');
  fill.style.width = pct + '%';
  text.textContent = pct + '%';
}

function formatBytes(bytes) {
  if (!bytes) return '0 B';
  const units = ['B', 'KB', 'MB', 'GB'];
  const i = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
  return (bytes / Math.pow(1024, i)).toFixed(i === 0 ? 0 : 1) + ' ' + units[i];
}

function formatDuration(seconds) {
  const s = Math.floor(seconds || 0);
  const m = Math.floor(s / 60);
  const h = Math.floor(m / 60);
  const pad = n => (n < 10 ? '0' : '') + n;
  return h > 0 ? `${h}:${pad(m % 60)}:${pad(s % 60)}` : `${m}:${pad(s % 60)}`;
}

function acceptUploadFile(file) {
  if (!file) return;

  const okExt = /\.(mp4|m4v|webm|ogv|ogg|mov)$/i.test(file.name);
  if (!okExt) {
    showToast('Unsupported format. Use MP4, WebM, MOV, M4V or OGV.', 'error');
    return;
  }

  Upload.file = file;
  Upload.posterBlob = null;
  Upload.duration = 0;
  Upload.posterCaptured = false;
  clearTimeout(Upload.posterTimer);

  if (Upload.objectUrl) URL.revokeObjectURL(Upload.objectUrl);
  Upload.objectUrl = URL.createObjectURL(file);

  const preview = document.getElementById('upload-preview');
  const picker = document.getElementById('upload-picker');
  const video = document.getElementById('upload-preview-video');
  const meta = document.getElementById('upload-file-meta');
  const titleInput = document.getElementById('upload-title-input');

  if (preview) preview.classList.remove('hidden');
  if (picker) picker.classList.add('hidden');
  if (meta) meta.textContent = 'Reading video…';

  const fileName = file.name.replace(/\.[^.]+$/, '');
  if (titleInput && !titleInput.value.trim()) {
    titleInput.value = fileName.slice(0, 255);
  }

  if (!video) return;
  video.src = Upload.objectUrl;
  video.muted = true;
  video.playsInline = true;

  video.onloadedmetadata = () => {
    Upload.duration = isFinite(video.duration) ? video.duration : 0;
    if (meta) {
      meta.textContent = `${formatDuration(Upload.duration)} • ${formatBytes(file.size)}`;
    }

    if (Upload.duration <= 0) {
      showToast('Could not read this video\'s length.', 'error');
      return;
    }

    // Grab a poster frame ~10% in, before the first major scene change
    try {
      video.currentTime = Math.min(Upload.duration * 0.1, 8);
    } catch (e) {
      // ignore
    }

    Upload.posterTimer = setTimeout(() => {
      if (!Upload.posterCaptured && meta && !Upload.posterBlob) {
        meta.textContent += ' • no poster captured';
      }
    }, 4000);
  };

  video.onseeked = () => {
    if (Upload.posterCaptured) return;
    Upload.posterCaptured = true;
    clearTimeout(Upload.posterTimer);
    try {
      const canvas = document.createElement('canvas');
      const scale = Math.min(1, 640 / (video.videoWidth || 640));
      canvas.width = Math.round((video.videoWidth || 640) * scale);
      canvas.height = Math.round((video.videoHeight || 360) * scale);
      canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
      canvas.toBlob(blob => {
        if (blob) Upload.posterBlob = blob;
      }, 'image/jpeg', 0.82);
    } catch (e) {
      // Cross-origin or unsupported frame: the server falls back to a placeholder
    }
  };

  video.onerror = () => {
    showToast('This browser cannot preview that video. It may still upload fine.', 'error');
    if (meta) meta.textContent = 'Preview unavailable';
  };
}

function submitUpload() {
  const file = Upload.file;
  if (!file) {
    showToast('Choose a video file first', 'error');
    return;
  }
  if (!Upload.duration) {
    showToast('Still reading the video — try again in a moment', 'error');
    return;
  }
  if (Upload.busy) return;

  const title = document.getElementById('upload-title-input').value.trim();
  if (!title) {
    showToast('Give the movie a title', 'error');
    return;
  }

  const fd = new FormData();
  fd.append('video', file, file.name);
  if (Upload.posterBlob) {
    fd.append('poster', Upload.posterBlob, 'poster.jpg');
  }
  fd.append('title', title);
  fd.append('genre', document.getElementById('upload-genre-input').value.trim());
  fd.append('release_year', document.getElementById('upload-year-input').value.trim());
  fd.append('description', document.getElementById('upload-desc-input').value.trim());
  fd.append('duration', String(Math.round(Upload.duration)));

  setUploadBusy(true);
  setUploadProgress(0);

  const xhr = new XMLHttpRequest();
  xhr.open('POST', '/api/movies/upload');
  xhr.setRequestHeader('Accept', 'application/json');
  if (API.csrfToken) {
    xhr.setRequestHeader('X-CSRF-Token', API.csrfToken);
  }

  xhr.upload.onprogress = e => {
    if (e.lengthComputable) {
      setUploadProgress(Math.round((e.loaded / e.total) * 100));
    }
  };

  xhr.onload = () => {
    let res = {};
    try { res = JSON.parse(xhr.responseText || '{}'); } catch (e) {}

    setUploadBusy(false);

    if (xhr.status >= 200 && xhr.status < 300 && res.success) {
      setUploadProgress(100);
      showToast(res.message || 'Movie uploaded');
      setTimeout(() => window.location.reload(), 700);
    } else {
      setUploadProgress(null);
      showToast(res.error || `Upload failed (HTTP ${xhr.status})`, 'error');
    }
  };

  xhr.onerror = () => {
    setUploadBusy(false);
    setUploadProgress(null);
    showToast('Network error — the upload did not finish', 'error');
  };

  xhr.send(fd);
}

document.addEventListener('DOMContentLoaded', () => {
  const dropzone = document.getElementById('upload-dropzone');
  const input = document.getElementById('upload-video-input');
  const form = document.getElementById('upload-movie-form');

  if (dropzone && input) {
    dropzone.addEventListener('click', e => {
      if (e.target !== input) input.click();
    });

    ['dragenter', 'dragover'].forEach(type => {
      dropzone.addEventListener(type, e => {
        e.preventDefault();
        dropzone.classList.add('dragging');
      });
    });
    ['dragleave', 'drop'].forEach(type => {
      dropzone.addEventListener(type, e => {
        e.preventDefault();
        dropzone.classList.remove('dragging');
      });
    });
    dropzone.addEventListener('drop', e => {
      const file = e.dataTransfer?.files?.[0];
      if (file) acceptUploadFile(file);
    });

    input.addEventListener('change', () => acceptUploadFile(input.files?.[0]));
  }

  if (form) {
    form.addEventListener('submit', e => {
      e.preventDefault();
      submitUpload();
    });
  }
});
