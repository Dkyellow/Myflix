/**
 * MyFlix Cinema Video Player & Real-Time Sync Engine
 */
class CinemaPlayer {
  constructor(videoElement, roomCode, initialPosition = 0, initialPlaybackState = 'paused') {
    this.video = videoElement;
    this.roomCode = roomCode;
    this.isRemoteUpdate = false;
    this.isScrubbing = false;
    this.lastEmittedTime = 0;
    this.lastBroadcastMs = 0;
    this.driftThreshold = 2.0; // seconds before prominent drift warning
    this.targetRoomTime = initialPosition;
    this.targetStampedAt = Date.now();
    this.roomPlaybackState = initialPlaybackState;
    this.isHost = false;
    this.driftOverCount = 0;
    this.seekOverCount = 0;
    this.pendingSync = null;
    this.syncTimer = null;
    this.lastLocalActionMs = 0;
    this.autoplayBlockedNotified = false;

    // The markup carries `controls` so the video stays playable if this script
    // ever fails to initialise. Drop them again in favour of the custom bar.
    try {
      this.video.removeAttribute('controls');
    } catch (e) {}

    this.initElements();
    this.bindEvents();

    if (initialPosition > 0) {
      this.video.currentTime = initialPosition;
    }
    if (initialPlaybackState === 'playing') {
      this.video.play().catch(e => console.log('Autoplay policy prevented audio:', e));
    }
  }

  initElements() {
    this.wrapper = document.querySelector('.player-wrapper');
    this.controlsOverlay = document.querySelector('.cinema-controls-overlay');
    this.playBtn = document.getElementById('btn-play-pause');
    this.playIcon = this.playBtn?.querySelector('i') || this.playBtn;
    this.scrubberContainer = document.querySelector('.scrubber-container');
    this.scrubberProgress = document.querySelector('.scrubber-progress');
    this.scrubberBuffer = document.querySelector('.scrubber-buffer');
    this.scrubberTooltip = document.querySelector('.scrubber-tooltip');
    this.timeDisplay = document.getElementById('time-display');
    this.volumeBtn = document.getElementById('btn-volume');
    this.volumeSlider = document.getElementById('volume-slider');
    this.fullscreenBtn = document.getElementById('btn-fullscreen');
    this.driftBanner = document.getElementById('drift-warning-banner');
    this.driftDiffText = document.getElementById('drift-diff-text');
    this.btnSyncNow = document.getElementById('btn-sync-now');
    this.centerIndicator = document.getElementById('center-playback-indicator');
    this.reactionLayer = document.getElementById('reaction-burst-layer');

    // Restore volume (video.volume is read-only on iOS Safari)
    const savedVol = localStorage.getItem('myflix_player_volume');
    if (savedVol !== null) {
      try {
        this.video.volume = parseFloat(savedVol);
      } catch (e) {}
      if (this.volumeSlider) this.volumeSlider.value = savedVol;
    }
  }

  bindEvents() {
    // Video native events
    this.video.addEventListener('timeupdate', () => this.onTimeUpdate());
    this.video.addEventListener('progress', () => this.onProgress());
    this.video.addEventListener('play', () => this.onLocalPlay());
    this.video.addEventListener('pause', () => this.onLocalPause());
    this.video.addEventListener('ended', () => this.onLocalEnded());
    this.video.addEventListener('volumechange', () => this.onVolumeChange());
    this.video.addEventListener('error', () => this.onMediaError());

    // currentTime can only be set once metadata is known; re-apply the room
    // position when it finally arrives so late joiners start at the right spot.
    this.video.addEventListener('loadedmetadata', () => {
      const target = this.projectedRoomTime();
      if (target > 0 && Math.abs(this.video.currentTime - target) > 1.2) {
        this.video.currentTime = target;
      }
      if (this.roomPlaybackState === 'playing') {
        this.safePlay();
      }
    }, { once: true });

    const source = this.video.querySelector('source');
    if (source) {
      source.addEventListener('error', () => this.onMediaError());
    }
    this.video.addEventListener('loadedmetadata', () => {
      if (this.reportedMediaError) this.reportedMediaError = false;
    });

    // Click on video to toggle play/pause
    this.video.addEventListener('click', () => this.togglePlayPause());

    // Play/Pause button
    if (this.playBtn) {
      this.playBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        this.togglePlayPause();
      });
    }

    // Scrubber interactions (mouse + touch: iOS Safari fires neither
    // mousemove nor mouseup during a drag)
    if (this.scrubberContainer) {
      this.scrubberContainer.addEventListener('mousedown', (e) => this.startScrubbing(e));
      this.scrubberContainer.addEventListener('mousemove', (e) => this.onScrubberHover(e));
      this.scrubberContainer.addEventListener('mouseleave', () => {
        if (this.scrubberTooltip) this.scrubberTooltip.style.display = 'none';
      });
      this.scrubberContainer.addEventListener('touchstart', (e) => this.startScrubbing(e), { passive: true });
    }

    document.addEventListener('mousemove', (e) => {
      if (this.isScrubbing) this.scrub(e);
    });

    document.addEventListener('mouseup', (e) => {
      if (this.isScrubbing) this.stopScrubbing(e);
    });

    document.addEventListener('touchmove', (e) => {
      if (this.isScrubbing) {
        e.preventDefault();
        this.scrub(e);
      }
    }, { passive: false });

    document.addEventListener('touchend', (e) => {
      if (this.isScrubbing) this.stopScrubbing(e);
    });

    document.addEventListener('touchcancel', () => {
      if (this.isScrubbing) this.stopScrubbing({});
    });

    // Volume controls. iOS Safari exposes video.volume as read-only, so a
    // slider that cannot change anything is worse than no slider at all.
    let canSetVolume = true;
    try {
      const desc = Object.getOwnPropertyDescriptor(HTMLMediaElement.prototype, 'volume');
      canSetVolume = !!(desc && desc.set);
    } catch (e) {
      canSetVolume = false;
    }
    if (!canSetVolume) {
      this.volumeSlider?.closest('.volume-control')?.classList.add('hidden');
    }

    if (this.volumeBtn) {
      this.volumeBtn.addEventListener('click', () => {
        this.video.muted = !this.video.muted;
      });
    }
    if (this.volumeSlider) {
      this.volumeSlider.addEventListener('input', (e) => {
        if (!canSetVolume) return;
        try {
          this.video.volume = parseFloat(e.target.value);
        } catch (err) {
          return;
        }
        this.video.muted = false;
        localStorage.setItem('myflix_player_volume', this.video.volume);
      });
    }

    // Fullscreen
    if (this.fullscreenBtn) {
      this.fullscreenBtn.addEventListener('click', () => this.toggleFullscreen());
    }

    // Manual sync button
    if (this.btnSyncNow) {
      this.btnSyncNow.addEventListener('click', () => this.syncToRoom());
    }

    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => {
      if (['input', 'textarea'].includes(document.activeElement.tagName.toLowerCase())) {
        return; // Don't trigger shortcuts when typing in chat
      }
      if (e.code === 'Space') {
        e.preventDefault();
        this.togglePlayPause();
      } else if (e.code === 'ArrowRight') {
        this.seekRelative(10);
      } else if (e.code === 'ArrowLeft') {
        this.seekRelative(-10);
      } else if (e.code === 'KeyF') {
        this.toggleFullscreen();
      } else if (e.code === 'KeyM') {
        this.video.muted = !this.video.muted;
      }
    });

    // Hide controls on idle
    let controlsTimer;
    if (this.wrapper) {
      this.wrapper.addEventListener('mousemove', () => {
        this.controlsOverlay?.classList.add('show-always');
        clearTimeout(controlsTimer);
        controlsTimer = setTimeout(() => {
          if (!this.video.paused) {
            this.controlsOverlay?.classList.remove('show-always');
          }
        }, 3500);
      });
    }
  }

  /**
   * play() rejects when the browser has not seen a user gesture yet — this is
   * the normal case on iPhone, where autoplay with sound is always blocked.
   * Report it once and reveal the controls so the tap target is visible.
   */
  safePlay() {
    let promise;
    try {
      promise = this.video.play();
    } catch (e) {
      this.onAutoplayBlocked(e);
      return Promise.resolve(false);
    }

    if (!promise || typeof promise.then !== 'function') {
      return Promise.resolve(true);
    }

    return promise.then(() => {
      this.autoplayBlockedNotified = false;
      this.updatePlayBtnUI(true);
      return true;
    }).catch((err) => {
      this.onAutoplayBlocked(err);
      return false;
    });
  }

  onAutoplayBlocked(err) {
    console.warn('[MyFlix] autoplay blocked:', err && err.name, err && err.message);
    if (this.autoplayBlockedNotified) return;
    this.autoplayBlockedNotified = true;
    this.updatePlayBtnUI(false);
    this.controlsOverlay?.classList.add('show-always');
    if (typeof showToast === 'function') {
      showToast('Autoplay was blocked — tap the play button to start the movie.', 'error');
    }
  }

  togglePlayPause() {
    if (this.video.paused) {
      this.safePlay();
    } else {
      this.video.pause();
    }
  }

  onLocalPlay() {
    this.updatePlayBtnUI(true);

    if (this.isRemoteUpdate) {
      this.isRemoteUpdate = false;
      return;
    }

    this.flashIndicator('play');
    this.broadcastSync('play', this.video.currentTime);
  }

  onLocalPause() {
    this.updatePlayBtnUI(false);

    if (this.isRemoteUpdate) {
      this.isRemoteUpdate = false;
      return;
    }

    this.flashIndicator('pause');
    this.broadcastSync('pause', this.video.currentTime);
  }

  onLocalEnded() {
    this.updatePlayBtnUI(false);
  }

  onMediaError() {
    if (this.reportedMediaError) return;
    this.reportedMediaError = true;
    const err = this.video.error;
    const detail = err ? ` (code ${err.code})` : '';
    const message = 'The movie failed to load and cannot play' + detail + '. The video source may be unavailable.';
    console.error('Video playback error:', err);
    if (typeof showToast === 'function') showToast(message, 'error');
  }

  updatePlayBtnUI(isPlaying) {
    if (this.playBtn) {
      this.playBtn.innerHTML = isPlaying ? '<i class="ph-fill ph-pause"></i>' : '<i class="ph-fill ph-play"></i>';
    }
  }

  flashIndicator(type) {
    if (!this.centerIndicator) return;
    this.centerIndicator.innerHTML = type === 'play' ? '<i class="ph-fill ph-play"></i>' : '<i class="ph-fill ph-pause"></i>';
    this.centerIndicator.classList.add('active');
    setTimeout(() => {
      this.centerIndicator.classList.remove('active');
    }, 400);
  }

  /** Mouse events carry clientX; touch events only carry touches[]/changedTouches[]. */
  pointerClientX(e) {
    if (e.touches && e.touches.length) return e.touches[0].clientX;
    if (e.changedTouches && e.changedTouches.length) return e.changedTouches[0].clientX;
    return e.clientX;
  }

  scrubPosition(e) {
    if (!this.scrubberContainer) return 0;
    const rect = this.scrubberContainer.getBoundingClientRect();
    const clientX = this.pointerClientX(e);
    if (typeof clientX !== 'number') return 0;
    return Math.max(0, Math.min(1, (clientX - rect.left) / rect.width));
  }

  startScrubbing(e) {
    // iOS has no duration metadata yet; seeking would land at NaN/0, so do
    // not start a drag at all until the file is readable.
    if (!isFinite(this.video.duration) || this.video.duration <= 0) return;
    this.isScrubbing = true;
    this.scrub(e);
  }

  scrub(e) {
    if (!this.isScrubbing || !this.scrubberContainer) return;
    if (!isFinite(this.video.duration) || this.video.duration <= 0) return;

    const pos = this.scrubPosition(e);

    if (this.scrubberProgress) {
      this.scrubberProgress.style.width = `${pos * 100}%`;
    }
    this.video.currentTime = pos * this.video.duration;
  }

  stopScrubbing(e) {
    if (!this.isScrubbing) return;
    this.isScrubbing = false;
    if (!isFinite(this.video.duration) || this.video.duration <= 0) return;

    const pos = this.scrubPosition(e);
    const targetTime = pos * this.video.duration;

    this.video.currentTime = targetTime;
    this.broadcastSync('seek', targetTime);
  }

  onScrubberHover(e) {
    if (!this.scrubberContainer || !this.scrubberTooltip) return;
    const pos = this.scrubPosition(e);
    const time = pos * (this.video.duration || 0);

    this.scrubberTooltip.style.display = 'block';
    this.scrubberTooltip.style.left = `${pos * 100}%`;
    this.scrubberTooltip.textContent = this.formatTime(time);
  }

  seekRelative(deltaSeconds) {
    const target = Math.max(0, Math.min(this.video.duration || 0, this.video.currentTime + deltaSeconds));
    this.video.currentTime = target;
    this.broadcastSync('seek', target);
  }

  onTimeUpdate() {
    if (this.isScrubbing) return;

    const current = this.video.currentTime;
    const duration = this.video.duration || 0;
    const pct = duration > 0 ? (current / duration) * 100 : 0;

    if (this.scrubberProgress) {
      this.scrubberProgress.style.width = `${pct}%`;
    }

    if (this.timeDisplay) {
      this.timeDisplay.textContent = `${this.formatTime(current)} / ${this.formatTime(duration)}`;
    }

    this.checkDrift();
  }

  onProgress() {
    if (this.video.buffered.length > 0 && this.video.duration > 0 && this.scrubberBuffer) {
      const bufferedEnd = this.video.buffered.end(this.video.buffered.length - 1);
      const pct = (bufferedEnd / this.video.duration) * 100;
      this.scrubberBuffer.style.width = `${pct}%`;
    }
  }

  onVolumeChange() {
    if (!this.volumeBtn) return;
    if (this.video.muted || this.video.volume === 0) {
      this.volumeBtn.innerHTML = '<i class="ph-bold ph-speaker-simple-slash"></i>';
      if (this.volumeSlider) this.volumeSlider.value = 0;
    } else if (this.video.volume < 0.5) {
      this.volumeBtn.innerHTML = '<i class="ph-bold ph-speaker-simple-low"></i>';
      if (this.volumeSlider) this.volumeSlider.value = this.video.volume;
    } else {
      this.volumeBtn.innerHTML = '<i class="ph-bold ph-speaker-simple-high"></i>';
      if (this.volumeSlider) this.volumeSlider.value = this.video.volume;
    }
  }

  toggleFullscreen() {
    const isFullscreen = !!(document.fullscreenElement || document.webkitFullscreenElement);

    if (!isFullscreen) {
      if (this.wrapper?.requestFullscreen) {
        Promise.resolve(this.wrapper.requestFullscreen()).catch(() => {});
      } else if (this.video.webkitEnterFullscreen) {
        // iPhone Safari has no Element.requestFullscreen — only the video
        // element itself can go fullscreen, via the prefixed API.
        this.video.webkitEnterFullscreen();
      } else if (this.video.requestFullscreen) {
        Promise.resolve(this.video.requestFullscreen()).catch(() => {});
      }
    } else {
      if (document.exitFullscreen) {
        Promise.resolve(document.exitFullscreen()).catch(() => {});
      } else if (document.webkitExitFullscreen) {
        document.webkitExitFullscreen();
      }
    }
  }

  formatTime(seconds) {
    if (isNaN(seconds) || seconds === null) return '00:00';
    const s = Math.floor(seconds);
    const m = Math.floor(s / 60);
    const h = Math.floor(m / 60);
    const sec = s % 60;
    const min = m % 60;

    if (h > 0) {
      return `${h}:${min < 10 ? '0' : ''}${min}:${sec < 10 ? '0' : ''}${sec}`;
    }
    return `${min < 10 ? '0' : ''}${min}:${sec < 10 ? '0' : ''}${sec}`;
  }

  /**
   * Broadcast local playback action to room server.
   * Coalesces bursts (rapid toggling / scrubbing) into a single request
   * carrying the LATEST state, so no action is ever silently dropped.
   */
  broadcastSync(action, position) {
    this.lastLocalActionMs = Date.now();
    this.pendingSync = {
      action,
      position: parseFloat((+position || 0).toFixed(2))
    };

    if (this.syncTimer) return;
    this.syncTimer = setTimeout(() => this.flushSync(), 120);
  }

  async flushSync() {
    this.syncTimer = null;
    const payload = this.pendingSync;
    if (!payload) return;
    this.pendingSync = null;
    this.lastBroadcastMs = Date.now();

    try {
      await API.post(`/api/rooms/${this.roomCode}/sync`, {
        action: payload.action,
        position: payload.position,
        sender_name: window.MYFLIX_USER?.displayName || 'Someone'
      });
    } catch (e) {
      console.warn('Sync broadcast failed:', e);
    }
  }

  /**
   * The server only recomputes the room position when a poll arrives, so the
   * estimate it sends is already stale by the time it lands. Re-stamp it with
   * the local clock and project it forward, otherwise every poll looks like
   * drift equal to (network latency + poll interval).
   */
  projectedRoomTime() {
    const stamped = this.targetRoomTime || 0;
    if (this.roomPlaybackState !== 'playing' || !this.targetStampedAt) {
      return stamped;
    }
    return stamped + (Date.now() - this.targetStampedAt) / 1000;
  }

  /**
   * Apply remote playback event received from room SSE / sync
   */
  applyRemoteSync(data) {
    if (!data) return;

    // A local action is still travelling to the server, so this snapshot
    // predates it. Applying it would immediately undo what the user just did.
    if (Date.now() - this.lastLocalActionMs < 800) return;

    const state = data.playback_state || 'paused';
    const stampedTarget = data.estimated_position ?? data.playback_position ?? 0;
    const shouldBePlaying = state === 'playing';
    const changingState = shouldBePlaying !== !this.video.paused;

    this.roomPlaybackState = state;
    this.targetRoomTime = stampedTarget;
    this.targetStampedAt = Date.now();

    if (changingState) {
      this.isRemoteUpdate = true;
      // Safety net: if the media event never fires, release the flag.
      setTimeout(() => { this.isRemoteUpdate = false; }, 600);
    }

    const target = this.projectedRoomTime();
    const diff = this.video.currentTime - target;

    // Only hard-seek on sustained drift so one slow poll can't cause a seek storm.
    if (data.type === 'seek') {
      this.video.currentTime = target;
      this.seekOverCount = 0;
    } else if (Math.abs(diff) > 1.2) {
      this.seekOverCount++;
      if (this.seekOverCount >= 2) {
        this.video.currentTime = target;
        this.seekOverCount = 0;
      }
    } else {
      this.seekOverCount = 0;
    }

    if (shouldBePlaying && this.video.paused) {
      this.safePlay();
    } else if (!shouldBePlaying && !this.video.paused) {
      this.video.pause();
      this.updatePlayBtnUI(false);
    }

    this.checkDrift();
  }

  /**
   * Drift monitoring & soft auto-adjustment
   */
  checkDrift() {
    if (!this.targetStampedAt || this.roomPlaybackState !== 'playing' || this.video.paused) {
      if (this.driftBanner) this.driftBanner.classList.remove('active');
      this.driftOverCount = 0;
      this.video.playbackRate = 1.0;
      return;
    }

    const target = this.projectedRoomTime();
    const diff = this.video.currentTime - target;
    const absDiff = Math.abs(diff);

    // Minor drift: nudge playbackRate gently back into place
    if (absDiff > 0.6 && absDiff < this.driftThreshold) {
      this.video.playbackRate = diff < 0 ? 1.03 : 0.97;
    } else {
      this.video.playbackRate = 1.0;
    }

    if (absDiff >= this.driftThreshold) {
      this.driftOverCount++;
      if (this.driftOverCount >= 2 && this.driftBanner) {
        this.driftBanner.classList.add('active');
        if (this.driftDiffText) {
          const sign = diff < 0 ? '-' : '+';
          this.driftDiffText.textContent = `${sign}${absDiff.toFixed(1)}s`;
        }
      }
    } else {
      if (absDiff < this.driftThreshold - 0.5) {
        this.driftOverCount = 0;
        if (this.driftBanner) this.driftBanner.classList.remove('active');
      }
    }
  }

  syncToRoom() {
    if (this.targetRoomTime !== undefined) {
      this.isRemoteUpdate = true;
      this.video.currentTime = this.projectedRoomTime();
      if (this.roomPlaybackState === 'playing') {
        this.safePlay();
      }
      setTimeout(() => {
        this.isRemoteUpdate = false;
      }, 600);
      this.driftOverCount = 0;
      this.seekOverCount = 0;
      if (this.driftBanner) this.driftBanner.classList.remove('active');
    }
  }

  triggerReaction(emoji) {
    if (!this.reactionLayer) return;
    const burst = document.createElement('div');
    burst.className = 'burst-emoji';
    burst.textContent = emoji;
    burst.style.left = `${Math.random() * 70 + 15}%`;
    this.reactionLayer.appendChild(burst);
    setTimeout(() => {
      burst.remove();
    }, 2600);
  }
}
