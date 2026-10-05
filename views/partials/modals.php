<!-- Movie Detail Modal -->
<div id="modal-movie-detail" class="modal-backdrop">
  <div class="modal-window">
    <button class="modal-close-btn" onclick="closeModal('modal-movie-detail')"><i class="ph-bold ph-x"></i></button>
    <div class="modal-detail-banner">
      <img id="modal-movie-img" src="" alt="Movie" class="modal-detail-img">
      <div class="modal-detail-vignette"></div>
      <div class="modal-detail-banner-content">
        <h2 id="modal-movie-title" class="modal-detail-title">Movie Title</h2>
        <div class="flex items-center gap-md">
          <button id="modal-btn-watch-together" class="btn btn-primary btn-lg">
            <i class="ph-bold ph-users-three"></i> Watch Together
          </button>
        </div>
      </div>
    </div>
    <div class="modal-detail-body">
      <div class="flex-col gap-md">
        <div class="flex items-center gap-md text-sm font-semibold">
          <span id="modal-movie-match" class="match-badge">98% Match</span>
          <span id="modal-movie-year">2024</span>
          <span id="modal-movie-age" class="age-badge">PG-13</span>
          <span id="modal-movie-duration">1h 45m</span>
          <span class="hd-badge">HD</span>
        </div>
        <p id="modal-movie-desc" class="text-secondary leading-relaxed"></p>
      </div>
      <div class="modal-info-sidebar">
        <div><strong>Cast:</strong> <span id="modal-movie-cast"></span></div>
        <div><strong>Director:</strong> <span id="modal-movie-director"></span></div>
        <div><strong>Genre:</strong> <span id="modal-movie-genre"></span></div>
        <button id="modal-btn-delete-movie" class="btn btn-outline btn-sm hidden" style="margin-top: 8px; color: var(--brand-red); border-color: var(--brand-red);">
          <i class="ph-bold ph-trash"></i> Remove from MyFlix
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Create Watch Party Modal -->
<div id="modal-create-room" class="modal-backdrop">
  <div class="modal-window" style="max-width: 500px;">
    <button class="modal-close-btn" onclick="closeModal('modal-create-room')"><i class="ph-bold ph-x"></i></button>
    <div style="padding: 32px 28px;">
      <div class="flex items-center gap-md" style="margin-bottom: 24px;">
        <img id="create-room-poster-img" src="" style="width: 56px; height: 80px; object-fit: cover; border-radius: var(--radius-sm);" alt="Poster">
        <div>
          <span class="hero-badge" style="font-size: 0.7rem;">New Watch Party</span>
          <h3 id="create-room-movie-title" style="font-size: 1.25rem; font-weight: 700; margin-top: 4px;">Movie Title</h3>
        </div>
      </div>

      <form id="create-room-form">
        <input type="hidden" id="create-room-movie-id" value="">
        
        <div class="form-group">
          <label class="form-label">Party Name</label>
          <input type="text" id="create-room-name-input" class="form-control" placeholder="e.g. Friday Night Watch Party" required>
        </div>

        <div class="form-group">
          <label class="form-label">Who can join?</label>
          <div style="font-size: 0.85rem; color: var(--text-secondary); background: var(--bg-elevated); padding: 10px 14px; border-radius: var(--radius-sm);">
            <i class="ph-bold ph-link" style="color: var(--brand-red);"></i> Anyone with the private room link
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Maximum Participants (2–4)</label>
          <select id="create-room-max-guests" class="form-control">
            <option value="4" selected>4 people (Recommended)</option>
            <option value="3">3 people</option>
            <option value="2">2 people</option>
          </select>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 12px;">
          <i class="ph-bold ph-video-camera"></i> Create Watch Room
        </button>
      </form>
    </div>
  </div>
</div>

<!-- Authentication Modal -->
<div id="modal-auth" class="modal-backdrop">
  <div class="modal-window" style="max-width: 440px;">
    <button class="modal-close-btn" onclick="closeModal('modal-auth')"><i class="ph-bold ph-x"></i></button>
    <div style="padding: 36px 32px;">
      <h2 id="auth-modal-title" style="font-size: 1.75rem; font-weight: 700; margin-bottom: 24px;">Sign In</h2>
      
      <form id="auth-form" data-mode="login">
        <div id="auth-username-field" class="form-group hidden">
          <label class="form-label">Username</label>
          <input type="text" id="auth-username-input" class="form-control" placeholder="Choose a username">
        </div>

        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input type="email" id="auth-email-input" class="form-control" placeholder="name@example.com" required>
        </div>

        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" id="auth-password-input" class="form-control" placeholder="••••••••" required>
        </div>

        <button type="submit" id="auth-submit-btn" class="btn btn-primary" style="width: 100%; margin-top: 12px;">
          Sign In
        </button>
      </form>

      <div style="margin-top: 24px; text-align: center; font-size: 0.875rem; color: var(--text-secondary);">
        <span id="auth-toggle-link" style="cursor: pointer;" onclick="toggleAuthMode()">New to MyFlix? <strong>Sign up now.</strong></span>
      </div>
    </div>
  </div>
</div>

<!-- Upload Movie Modal -->
<div id="modal-upload" class="modal-backdrop">
  <div class="modal-window" style="max-width: 560px;">
    <button class="modal-close-btn" onclick="closeModal('modal-upload')"><i class="ph-bold ph-x"></i></button>
    <div style="padding: 32px 28px;">
      <span class="hero-badge" style="font-size: 0.7rem;">Add To Your Library</span>
      <h3 style="font-size: 1.4rem; font-weight: 700; margin: 6px 0 20px;">Upload a Movie</h3>

      <form id="upload-movie-form" data-max-upload="<?= htmlspecialchars((string)ini_get('upload_max_filesize')) ?>">
        <div class="form-group">
          <label class="form-label">Video File</label>
          <div id="upload-dropzone" class="upload-dropzone">
            <input type="file" id="upload-video-input" hidden
                   accept="video/mp4,video/webm,video/ogg,video/quicktime,.mp4,.m4v,.webm,.ogv,.ogg,.mov">

            <div id="upload-picker">
              <i class="ph-bold ph-film" style="font-size: 2rem; color: var(--text-muted);"></i>
              <p style="margin-top: 8px; font-weight: 600;">Click to choose a video</p>
              <small style="color: var(--text-muted);">MP4, WebM, MOV, M4V or OGV</small>
              <small id="upload-max-hint" style="display: block; color: var(--text-muted);"></small>
            </div>

            <div id="upload-preview" class="hidden">
              <video id="upload-preview-video" muted playsinline></video>
              <div id="upload-file-meta" class="upload-file-meta"></div>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Title</label>
          <input type="text" id="upload-title-input" class="form-control" maxlength="255" placeholder="Movie title" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
          <div class="form-group">
            <label class="form-label">Genre</label>
            <input type="text" id="upload-genre-input" class="form-control" maxlength="100" placeholder="e.g. Documentary">
          </div>
          <div class="form-group">
            <label class="form-label">Release Year</label>
            <input type="number" id="upload-year-input" class="form-control" min="1888" max="2100" placeholder="<?= date('Y') ?>">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea id="upload-desc-input" class="form-control" rows="3" maxlength="2000" placeholder="What is it about?"></textarea>
        </div>

        <div id="upload-progress" class="upload-progress hidden">
          <div id="upload-progress-bar" class="upload-progress-bar"></div>
        </div>
        <div id="upload-progress-text" class="upload-progress-text hidden">0%</div>

        <button type="submit" id="upload-submit-btn" class="btn btn-primary" style="width: 100%; margin-top: 16px;">
          <i class="ph-bold ph-upload-simple"></i> Upload Movie
        </button>
      </form>
    </div>
  </div>
</div>
