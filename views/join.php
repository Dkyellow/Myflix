<?php include dirname(__DIR__) . '/views/layout/header.php'; ?>

<div class="lobby-container">
  <div class="lobby-card">
    <!-- Camera / Mic Hardware Preview Box -->
    <div class="lobby-media-preview">
      <div class="lobby-cam-box">
        <video id="lobby-preview-video" class="lobby-video-preview" autoplay playsinline muted></video>
        <div id="lobby-preview-fallback" class="lobby-cam-fallback hidden">
          <i class="ph-bold ph-user"></i>
        </div>
      </div>

      <div class="lobby-controls-bar">
        <button id="lobby-btn-mic" class="call-tool-btn" title="Toggle Mic">
          <i class="ph-bold ph-microphone"></i>
        </button>
        <button id="lobby-btn-cam" class="call-tool-btn" title="Toggle Camera">
          <i class="ph-bold ph-video-camera"></i>
        </button>
      </div>

      <div style="font-size: 0.75rem; color: var(--text-muted); text-align: center;">
        Check your video & microphone before entering
      </div>
    </div>

    <!-- Join Form Side -->
    <div class="lobby-form-side">
      <div>
        <a href="/" class="brand-logo" style="font-size: 1.5rem;">
          MYFLIX <span class="brand-logo-party-badge">PARTY</span>
        </a>

        <div style="margin-top: 20px;">
          <span class="hero-badge" style="font-size: 0.7rem;">You're Invited To Watch</span>
          <h1 style="font-size: 1.6rem; font-weight: 700; margin-top: 6px;"><?= htmlspecialchars($room['movie_title']) ?></h1>
          <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 4px;">
            Room: <strong><?= htmlspecialchars($room['room_name']) ?></strong>
          </p>
        </div>
      </div>

      <?php if ($isRoomFull): ?>
        <div style="background: rgba(229, 9, 20, 0.15); border: 1px solid var(--brand-red); border-radius: var(--radius-sm); padding: 16px; margin: 20px 0;">
          <div class="flex items-center gap-sm text-red font-bold" style="font-size: 0.95rem;">
            <i class="ph-bold ph-warning-circle"></i> Room is Full
          </div>
          <p style="font-size: 0.85rem; color: #fff; margin-top: 6px;">
            This watch party already has 4 participants (maximum capacity). Please ask a participant to leave or create a new room.
          </p>
          <a href="/" class="btn btn-outline btn-sm" style="margin-top: 12px; width: 100%;">Return to Home</a>
        </div>
      <?php else: ?>
        <form id="lobby-join-form" style="margin-top: 24px;">
          <div class="form-group">
            <label class="form-label">Your Display Name</label>
            <input type="text" id="lobby-name-input" class="form-control" value="<?= htmlspecialchars($user['username'] ?? '') ?>" placeholder="Enter your name" required>
          </div>

          <button type="submit" id="lobby-submit-btn" class="btn btn-primary" style="width: 100%; margin-top: 14px;">
            <i class="ph-bold ph-users-three"></i> Join Watch Party
          </button>
        </form>
      <?php endif; ?>

      <div style="font-size: 0.75rem; color: var(--text-muted); text-align: center; margin-top: 20px;">
        MyFlix synchronized cinema experience • Up to 4 participants
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', async () => {
    let localStream = null;
    let isMicMuted = false;
    let isCamMuted = false;

    const videoEl = document.getElementById('lobby-preview-video');
    const fallbackEl = document.getElementById('lobby-preview-fallback');
    const btnMic = document.getElementById('lobby-btn-mic');
    const btnCam = document.getElementById('lobby-btn-cam');
    const form = document.getElementById('lobby-join-form');
    const submitBtn = document.getElementById('lobby-submit-btn');

    // Request camera/mic preview safely
    if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
      showToast(
        'Camera and microphone are blocked: this page is not a secure context (' +
        location.protocol + '). Add this origin to chrome://flags/#unsafely-treat-insecure-origin-as-secure, or use HTTPS/localhost.',
        'error'
      );
      if (fallbackEl) fallbackEl.classList.remove('hidden');
      if (videoEl) videoEl.classList.add('hidden');
    } else try {
      localStream = await navigator.mediaDevices.getUserMedia({
        video: { width: { ideal: 480 }, height: { ideal: 360 } },
        audio: true
      });
      if (videoEl && localStream) {
        videoEl.srcObject = localStream;
      }
    } catch (e) {
      console.warn('Hardware preview permission:', e);
      if (fallbackEl) fallbackEl.classList.remove('hidden');
      if (videoEl) videoEl.classList.add('hidden');
    }

    if (btnMic) {
      btnMic.addEventListener('click', () => {
        isMicMuted = !isMicMuted;
        if (localStream) {
          localStream.getAudioTracks().forEach(t => t.enabled = !isMicMuted);
        }
        btnMic.classList.toggle('off', isMicMuted);
        btnMic.innerHTML = isMicMuted ? '<i class="ph-bold ph-microphone-slash"></i>' : '<i class="ph-bold ph-microphone"></i>';
      });
    }

    if (btnCam) {
      btnCam.addEventListener('click', () => {
        isCamMuted = !isCamMuted;
        if (localStream) {
          localStream.getVideoTracks().forEach(t => t.enabled = !isCamMuted);
        }
        btnCam.classList.toggle('off', isCamMuted);
        btnCam.innerHTML = isCamMuted ? '<i class="ph-bold ph-video-camera-slash"></i>' : '<i class="ph-bold ph-video-camera"></i>';
        if (fallbackEl && videoEl) {
          fallbackEl.classList.toggle('hidden', !isCamMuted);
          videoEl.classList.toggle('hidden', isCamMuted);
        }
      });
    }

    if (form) {
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const name = document.getElementById('lobby-name-input').value.trim();
        if (!name) return;

        try {
          if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Joining Party...';
          }

          const res = await API.post('/api/rooms/<?= $room['room_code'] ?>/join', {
            display_name: name
          });

          if (res.success) {
            // Stop preview stream before reloading into room view
            if (localStream) {
              localStream.getTracks().forEach(t => t.stop());
            }
            window.location.reload();
          }
        } catch (err) {
          alert(err.message || 'Failed to join room');
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Join Watch Party';
          }
        }
      });
    }
  });
</script>

<?php include dirname(__DIR__) . '/views/layout/footer.php'; ?>
