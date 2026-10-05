/**
 * MyFlix Watch Room Social Coordinator & Real-Time Presence
 */
class WatchRoomApp {
  constructor(roomData, participantData, isHost) {
    this.room = roomData;
    this.participant = participantData;
    this.isHost = isHost;
    this.lastChatMs = 0;
    this.participants = [];
    this.player = null;
    this.mediaManager = null;
    this.pollTimer = null;
    this.heartbeatTimer = null;
    this.isPolling = false;

    this.init();
  }

  async init() {
    // 1. Initialize Video Player
    const videoEl = document.getElementById('cinema-video');
    if (videoEl) {
      this.player = new CinemaPlayer(
        videoEl,
        this.room.room_code,
        this.room.playback_position,
        this.room.playback_state
      );
      this.player.isHost = this.isHost;
    }

    // 2. Initialize Media Manager (Camera & Mic)
    this.mediaManager = new LiveKitCallManager(this.room.room_code, this.participant);
    
    this.mediaManager.onSpeakingChange = (sessionId, isSpeaking) => {
      this.setSpeakingState(sessionId, isSpeaking);
    };

    this.mediaManager.onMediaBlocked = (reason) => {
      showToast(reason, 'error');
    };

    // Pre-acquire camera & mic
    const localTile = document.getElementById(`participant-tile-${this.participant.session_id}`);
    const localVideo = localTile?.querySelector('video');
    await this.mediaManager.initLocalMedia(localVideo);
    await this.mediaManager.connect();

    // 3. Bind UI Events
    this.bindUI();

    // 4. Start Event Sync Loop & Presence
    this.startSyncLoop();
    this.startHeartbeat();

    // 5. Initial Chat Fetch
    this.fetchChat();
  }

  bindUI() {
    // Copy Invite Link
    const copyBtn = document.getElementById('btn-copy-invite');
    if (copyBtn) {
      copyBtn.addEventListener('click', () => {
        const fullUrl = window.location.origin + '/room/' + this.room.room_code;
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(fullUrl).then(() => {
            showToast('Room link copied! Send it to your friends.');
          }).catch(() => {
            prompt('Copy watch party link:', fullUrl);
          });
        } else {
          prompt('Copy watch party link:', fullUrl);
        }
      });
    }

    // Chat form submit
    const chatForm = document.getElementById('chat-form');
    const chatInput = document.getElementById('chat-input');
    if (chatForm && chatInput) {
      chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const text = chatInput.value.trim();
        if (!text) return;
        chatInput.value = '';

        try {
          await API.post(`/api/rooms/${this.room.room_code}/chat`, {
            message: text,
            sender_name: this.participant.display_name
          });
          this.fetchChat();
        } catch (err) {
          showToast(err.message || 'Failed to send message', 'error');
        }
      });
    }

    // Quick emoji reactions
    document.querySelectorAll('.reaction-emoji-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const emoji = btn.getAttribute('data-emoji') || btn.textContent.trim();
        this.sendReaction(emoji);
      });
    });

    // Media Controls Toolbar (Mic & Cam toggle)
    const btnMic = document.getElementById('btn-toggle-mic');
    if (btnMic) {
      btnMic.addEventListener('click', () => {
        const isActive = this.mediaManager.toggleMicrophone();
        btnMic.classList.toggle('off', !isActive);
        btnMic.innerHTML = isActive ? '<i class="ph-bold ph-microphone"></i>' : '<i class="ph-bold ph-microphone-slash"></i>';
        this.updateLocalMediaBadge('mic', !isActive);
      });
    }

    const btnCam = document.getElementById('btn-toggle-cam');
    if (btnCam) {
      btnCam.addEventListener('click', () => {
        const isActive = this.mediaManager.toggleCamera();
        btnCam.classList.toggle('off', !isActive);
        btnCam.innerHTML = isActive ? '<i class="ph-bold ph-video-camera"></i>' : '<i class="ph-bold ph-video-camera-slash"></i>';
        this.updateLocalMediaBadge('cam', !isActive);
      });
    }

    // Collapse Sidebar
    const btnCollapse = document.getElementById('btn-collapse-sidebar');
    const sidebar = document.querySelector('.social-sidebar');
    if (btnCollapse && sidebar) {
      btnCollapse.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
      });
    }

    // Leave & End Party
    const btnLeave = document.getElementById('btn-leave-room');
    if (btnLeave) {
      btnLeave.addEventListener('click', async () => {
        if (confirm('Are you sure you want to leave this watch party?')) {
          await API.post(`/api/rooms/${this.room.room_code}/leave`).catch(() => {});
          this.mediaManager?.disconnect();
          window.location.href = '/';
        }
      });
    }

    const btnEnd = document.getElementById('btn-end-party');
    if (btnEnd) {
      btnEnd.addEventListener('click', async () => {
        if (confirm('End this watch party for all participants?')) {
          await API.post(`/api/rooms/${this.room.room_code}/end`).catch(() => {});
          this.mediaManager?.disconnect();
          window.location.href = '/';
        }
      });
    }
  }

  startSyncLoop() {
    this.pollSync();
    this.pollTimer = setInterval(() => {
      this.pollSync();
    }, 850); // 850ms interval for sub-second smooth sync without locking server
  }

  async pollSync() {
    if (this.isPolling) return;
    this.isPolling = true;

    try {
      const res = await API.get(`/api/rooms/${this.room.room_code}/events`, { since_ms: this.lastChatMs });
      
      if (res.success) {
        if (res.status === 'ended') {
          alert('This watch party has been ended by the host.');
          window.location.href = '/';
          return;
        }

        // 1. Playback sync
        this.player?.applyRemoteSync(res);

        // 2. Participants sync & WebRTC mesh
        if (res.participants) {
          this.renderParticipants(res.participants);
          this.mediaManager?.syncPeers(res.participants);
        }

        // 3. New Chat messages
        if (res.messages && res.messages.length > 0) {
          res.messages.forEach(msg => this.renderChatMessage(msg));
          if (res.last_chat_ms) this.lastChatMs = res.last_chat_ms;
        }

        // 4. Process WebRTC Signals
        if (res.signals && res.signals.length > 0) {
          for (const sig of res.signals) {
            await this.mediaManager?.handleSignal(sig);
          }
        }
      }
    } catch (e) {
      // transient network glitch
    } finally {
      this.isPolling = false;
    }
  }

  async fetchChat() {
    try {
      const res = await API.get(`/api/rooms/${this.room.room_code}/chat`, { since_ms: this.lastChatMs });
      if (res.success && res.messages) {
        res.messages.forEach(m => this.renderChatMessage(m));
        if (res.messages.length > 0) {
          this.lastChatMs = res.messages[res.messages.length - 1].created_at_ms;
        }
      }
    } catch (e) {}
  }

  renderChatMessage(msg) {
    const list = document.getElementById('chat-messages-list');
    if (!list) return;

    if (document.getElementById(`msg-${msg.id}`)) return;

    const div = document.createElement('div');
    div.id = `msg-${msg.id}`;

    if (msg.is_system) {
      div.className = 'chat-msg system';
      div.textContent = msg.message;
    } else {
      const isMine = msg.display_name === this.participant.display_name;
      div.className = `chat-msg ${isMine ? 'mine' : ''}`;
      
      div.innerHTML = `
        <div class="chat-msg-header">
          <span class="chat-author" style="color: ${msg.avatar_color || '#E50914'}">${escapeHtml(msg.display_name)}</span>
          <span class="chat-time">${msg.time_formatted || ''}</span>
        </div>
        <div class="chat-bubble">${escapeHtml(msg.message)}</div>
      `;
    }

    list.appendChild(div);
    list.scrollTop = list.scrollHeight;
  }

  async sendReaction(emoji) {
    this.player?.triggerReaction(emoji);
    try {
      await API.post(`/api/rooms/${this.room.room_code}/reaction`, {
        emoji,
        sender_name: this.participant.display_name
      });
    } catch (e) {}
  }

  renderParticipants(participantsList) {
    this.participants = participantsList;
    const grid = document.getElementById('participants-grid');
    const counter = document.getElementById('participants-count-text');

    if (counter) {
      counter.textContent = `${participantsList.length}/${this.room.max_participants || 4}`;
    }

    if (!grid) return;

    participantsList.forEach(p => {
      let tile = document.getElementById(`participant-tile-${p.session_id}`);
      const isSelf = p.session_id === this.participant.session_id;

      if (!tile) {
        tile = document.createElement('div');
        tile.className = 'participant-tile';
        tile.id = `participant-tile-${p.session_id}`;
        
        const initials = (p.display_name || 'U').substring(0, 2).toUpperCase();
        tile.innerHTML = `
          <div class="participant-avatar-fallback" style="background-color: ${p.avatar_color || '#E50914'}">${initials}</div>
          <video class="participant-video ${isSelf ? '' : 'hidden'}" autoplay playsinline muted></video>
          <div class="participant-status-icons">
            <span class="media-badge mic ${p.mic_muted ? 'muted' : 'hidden'}"><i class="ph-bold ph-microphone-slash"></i></span>
          </div>
          <div class="participant-tag">
            ${p.is_host ? '👑 ' : ''}${escapeHtml(p.display_name)} ${isSelf ? '(You)' : ''}
          </div>
        `;
        grid.appendChild(tile);

        if (isSelf && this.mediaManager?.localStream) {
          const video = tile.querySelector('video');
          video.srcObject = this.mediaManager.localStream;
          video.classList.remove('hidden');
          const avatar = tile.querySelector('.participant-avatar-fallback');
          if (avatar) avatar.classList.add('hidden');
        }
      } else {
        const micBadge = tile.querySelector('.media-badge.mic');
        if (micBadge) {
          micBadge.classList.toggle('hidden', !p.mic_muted);
        }
      }
    });

    // Clean up participants who left
    const activeIds = new Set(participantsList.map(p => `participant-tile-${p.session_id}`));
    Array.from(grid.children).forEach(child => {
      if (!activeIds.has(child.id)) {
        child.remove();
      }
    });
  }

  setSpeakingState(sessionId, isSpeaking) {
    const tile = document.getElementById(`participant-tile-${sessionId}`);
    if (tile) {
      tile.classList.toggle('speaking', isSpeaking);
    }
  }

  updateLocalMediaBadge(type, isMuted) {
    const localTile = document.getElementById(`participant-tile-${this.participant.session_id}`);
    if (!localTile) return;

    if (type === 'mic') {
      const badge = localTile.querySelector('.media-badge.mic');
      if (badge) badge.classList.toggle('hidden', !isMuted);
    } else if (type === 'cam') {
      const video = localTile.querySelector('video');
      const avatar = localTile.querySelector('.participant-avatar-fallback');
      if (video) video.classList.toggle('hidden', isMuted);
      if (avatar) avatar.classList.toggle('hidden', !isMuted);
    }

    this.sendHeartbeat();
  }

  startHeartbeat() {
    this.sendHeartbeat();
    this.heartbeatTimer = setInterval(() => this.sendHeartbeat(), 8000);
  }

  async sendHeartbeat() {
    try {
      await API.post(`/api/rooms/${this.room.room_code}/heartbeat`, {
        mic_muted: this.mediaManager?.isMicMuted ? 1 : 0,
        cam_muted: this.mediaManager?.isCamMuted ? 1 : 0
      });
    } catch (e) {}
  }
}

function escapeHtml(str) {
  if (!str) return '';
  return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}
