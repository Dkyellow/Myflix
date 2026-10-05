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
