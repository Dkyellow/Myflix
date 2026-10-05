<?php include dirname(__DIR__) . '/views/layout/header.php'; ?>

<div class="room-container">
  <!-- Room Header -->
  <header class="room-header">
    <div class="room-header-left">
      <a href="/" class="brand-logo" style="font-size: 1.5rem;" title="Back to MyFlix Home">
        MYFLIX <span class="brand-logo-party-badge">PARTY</span>
      </a>
      <div class="room-info">
        <span class="room-name-title"><?= htmlspecialchars($room['room_name']) ?></span>
        <span class="room-status-badge">
          <span class="room-status-dot"></span> LIVE
        </span>
      </div>
    </div>

    <div class="room-header-right">
      <!-- Invite Link Copy Button -->
      <button id="btn-copy-invite" class="btn btn-outline btn-sm" title="Copy Invite Link">
        <i class="ph-bold ph-link"></i> <span>Invite</span>
      </button>

      <!-- Active Participant Counter -->
      <div class="room-participants-count" title="Participants in room">
        <i class="ph-bold ph-users"></i>
        <span id="participants-count-text">1/<?= $room['max_participants'] ?? 4 ?></span>
      </div>

      <!-- Toggle Social Sidebar (Desktop only) -->
      <button id="btn-collapse-sidebar" class="btn btn-icon btn-secondary btn-sm hidden md:flex" title="Toggle Sidebar">
        <i class="ph-bold ph-sidebar-simple"></i>
      </button>

      <!-- Host End Room or Leave -->
      <?php if ($isHost): ?>
        <button id="btn-end-party" class="btn btn-primary btn-sm" title="End Party for everyone">
          <i class="ph-bold ph-power"></i> <span>End</span>
        </button>
      <?php else: ?>
        <button id="btn-leave-room" class="btn btn-outline btn-sm" title="Leave Watch Room">
          <i class="ph-bold ph-sign-out"></i> <span>Leave</span>
        </button>
      <?php endif; ?>
    </div>
  </header>

  <!-- Room Body (Cinema + Social) -->
  <div class="room-body">
    <!-- Movie Player Canvas -->
    <main class="cinema-section">
      <div class="player-wrapper">
        <!-- Native HTML5 Video Element. `controls` is a safety net: it stays
             only if player.js fails to initialise, and is removed on startup. -->
        <video id="cinema-video" class="cinema-video" playsinline webkit-playsinline controls preload="auto">
          <source src="<?= htmlspecialchars($room['movie_video_url']) ?>" type="video/mp4">
          Your browser does not support the video tag.
        </video>

        <!-- Floating Emoji Burst Layer -->
        <div id="reaction-burst-layer" class="reaction-burst-layer"></div>

        <!-- Center Play/Pause Flash Icon -->
        <div id="center-playback-indicator" class="center-playback-indicator">
          <i class="ph-fill ph-play"></i>
        </div>

        <!-- Drift Warning Banner -->
        <div id="drift-warning-banner" class="drift-warning-banner">
          <i class="ph-bold ph-arrows-clockwise"></i>
          <span>You are out of sync (<strong id="drift-diff-text">0.0s</strong>)</span>
          <button id="btn-sync-now" class="btn-sync-now">SYNC TO ROOM</button>
        </div>

        <!-- Custom Video Controls Overlay -->
        <div class="cinema-controls-overlay">
          <!-- Scrubber Container -->
          <div class="scrubber-container">
            <div class="scrubber-tooltip">00:00</div>
            <div class="scrubber-track">
              <div class="scrubber-buffer"></div>
              <div class="scrubber-progress">
                <div class="scrubber-handle"></div>
              </div>
            </div>
          </div>

          <!-- Bottom Action Buttons -->
          <div class="controls-bottom-bar">
            <div class="controls-left">
              <button id="btn-play-pause" class="ctrl-btn" title="Play/Pause (Space)">
                <i class="ph-fill ph-play"></i>
              </button>

              <div class="volume-control">
                <button id="btn-volume" class="ctrl-btn" title="Mute/Unmute (M)">
                  <i class="ph-bold ph-speaker-simple-high"></i>
                </button>
                <input type="range" id="volume-slider" class="volume-slider" min="0" max="1" step="0.05" value="1">
              </div>

              <div id="time-display" class="time-display">00:00 / 00:00</div>
            </div>

            <div class="controls-right">
              <!-- Quick Emoji Reaction Dock -->
              <div class="quick-reaction-dock">
                <span class="reaction-emoji-btn" data-emoji="🍿">🍿</span>
                <span class="reaction-emoji-btn" data-emoji="❤️">❤️</span>
                <span class="reaction-emoji-btn" data-emoji="😂">😂</span>
                <span class="reaction-emoji-btn" data-emoji="😱">😱</span>
                <span class="reaction-emoji-btn" data-emoji="🔥">🔥</span>
                <span class="reaction-emoji-btn" data-emoji="👏">👏</span>
              </div>

              <button id="btn-fullscreen" class="ctrl-btn" title="Fullscreen (F)">
                <i class="ph-bold ph-corners-out"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Social Sidebar (Participants + Chat) -->
    <aside class="social-sidebar">
      <!-- Participant Video Tiles -->
      <section class="participants-section">
        <div class="participants-header">
          <span>Watching Together</span>
          <span style="font-size: 0.75rem; color: var(--text-muted);">Max 4</span>
        </div>

        <div id="participants-grid" class="participants-grid">
          <!-- Populated dynamically by JS -->
        </div>

        <!-- Video Call Controls -->
        <div class="call-toolbar">
          <button id="btn-toggle-mic" class="call-tool-btn" title="Toggle Microphone">
            <i class="ph-bold ph-microphone"></i>
          </button>
          <button id="btn-toggle-cam" class="call-tool-btn" title="Toggle Camera">
            <i class="ph-bold ph-video-camera"></i>
          </button>
        </div>
      </section>

      <!-- Realtime Chat -->
      <section class="chat-section">
        <div class="chat-header">
          <span>Party Chat</span>
        </div>

        <div id="chat-messages-list" class="chat-messages">
          <!-- Chat messages rendered dynamically -->
        </div>

        <form id="chat-form" class="chat-input-form">
          <input type="text" id="chat-input" class="chat-input" placeholder="Type a message..." autocomplete="off">
          <button type="submit" class="chat-send-btn" title="Send message">
            <i class="ph-bold ph-paper-plane-right"></i>
          </button>
        </form>
      </section>
    </aside>
  </div>
</div>

<!-- Pass Room Configuration to Client JS -->
<script>
  window.MYFLIX_ROOM = <?= json_encode([
    'id' => (int)$room['id'],
    'room_code' => $room['room_code'],
    'room_name' => $room['room_name'],
    'max_participants' => (int)($room['max_participants'] ?? 4),
    'playback_position' => (float)$estimatedPosition,
    'playback_state' => $room['playback_state']
  ]) ?>;

  window.MYFLIX_PARTICIPANT = <?= json_encode([
    'id' => (int)$participant['id'],
    'session_id' => $participant['session_id'],
    'display_name' => $participant['display_name'],
    'avatar_color' => $participant['avatar_color'],
    'is_host' => (bool)$isHost
  ]) ?>;

  window.MYFLIX_USER = {
    displayName: <?= json_encode($participant['display_name']) ?>
  };

  document.addEventListener('DOMContentLoaded', () => {
    window.watchRoomApp = new WatchRoomApp(
      window.MYFLIX_ROOM,
      window.MYFLIX_PARTICIPANT,
      <?= $isHost ? 'true' : 'false' ?>
    );
  });
</script>

<?php include dirname(__DIR__) . '/views/layout/footer.php'; ?>
