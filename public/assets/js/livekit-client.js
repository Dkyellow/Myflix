/**
 * WebRTC Video/Audio Call Manager for MyFlix
 * Supports both LiveKit Cloud and Peer-to-Peer WebRTC Mesh with Audio Analysis
 */
class LiveKitCallManager {
  constructor(roomCode, participantInfo) {
    this.roomCode = roomCode;
    this.participantInfo = participantInfo;
    this.localStream = null;
    this.audioContext = null;
    this.analyser = null;
    this.isMicMuted = false;
    this.isCamMuted = false;
    this.peerConnections = {}; // sessionId -> RTCPeerConnection
    this.rtcConfig = {
      iceServers: [
        { urls: 'stun:stun.l.google.com:19302' },
        { urls: 'stun:stun1.l.google.com:19302' },
        { urls: 'stun:stun2.l.google.com:19302' }
      ]
    };

    this.onSpeakingChange = null;
    this.isSecureContext = window.isSecureContext;
    this.isSupported = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
  }

  getBlockReason() {
    if (this.isSupported) return null;
    if (!this.isSecureContext) {
      return 'Camera and microphone are blocked: this page is not a secure context (' +
        location.protocol + '). Open the site over HTTPS or localhost, or add this origin ' +
        'to chrome://flags/#unsafely-treat-insecure-origin-as-secure';
    }
    return 'Camera and microphone are not available in this browser.';
  }

  /**
   * Acquire local user media (camera & mic)
   */
  async initLocalMedia(videoElement = null) {
    if (!this.isSupported) {
      console.warn(this.getBlockReason());
      if (this.onMediaBlocked) this.onMediaBlocked(this.getBlockReason());
      return false;
    }

    try {
      this.localStream = await navigator.mediaDevices.getUserMedia({
        video: { width: { ideal: 640 }, height: { ideal: 480 }, frameRate: { ideal: 24 } },
        audio: { echoCancellation: true, noiseSuppression: true, autoGainControl: true }
      });

      if (videoElement && this.localStream) {
        videoElement.srcObject = this.localStream;
        videoElement.muted = true;
        videoElement.play().catch(() => {});
      }

      this.setupSpeakingDetector();
      return true;
    } catch (err) {
      console.warn('Camera/mic access permission notice:', err.name, err.message);
      // Try audio only if camera was denied
      try {
        this.localStream = await navigator.mediaDevices.getUserMedia({ audio: true });
        this.setupSpeakingDetector();
        this.isCamMuted = true;
        return true;
      } catch (audioErr) {
        return false;
      }
    }
  }

  /**
   * Connect and prepare peer-to-peer WebRTC connections
   */
  async connect() {
    console.log('WebRTC Call Manager initialized for room:', this.roomCode);
    return true;
  }

  /**
   * Update active peers in the room and initiate peer connections
   */
  syncPeers(participantsList) {
    const currentSession = this.participantInfo.session_id;

    participantsList.forEach(p => {
      if (p.session_id === currentSession) return;

      if (!this.peerConnections[p.session_id]) {
        // Higher sessionId initiates the offer (avoids glare)
        const isInitiator = currentSession > p.session_id;
        this.createPeerConnection(p.session_id, isInitiator);
      }
    });

    // Cleanup disconnected peers
    const activeSessions = new Set(participantsList.map(p => p.session_id));
    Object.keys(this.peerConnections).forEach(sId => {
      if (!activeSessions.has(sId)) {
        this.peerConnections[sId].close();
        delete this.peerConnections[sId];
      }
    });
  }

  createPeerConnection(remoteSessionId, isInitiator) {
    const pc = new RTCPeerConnection(this.rtcConfig);
    this.peerConnections[remoteSessionId] = pc;

    // Add local tracks if available
    if (this.localStream) {
      this.localStream.getTracks().forEach(track => {
        pc.addTrack(track, this.localStream);
      });
    }

    // Handle remote track arrival
    pc.ontrack = (event) => {
      const stream = event.streams[0] || new MediaStream([event.track]);
      this.attachRemoteStream(remoteSessionId, stream);
    };

    // Handle ICE candidates
    pc.onicecandidate = (event) => {
      if (event.candidate) {
        this.sendSignal(remoteSessionId, 'candidate', event.candidate);
      }
    };

    if (isInitiator) {
      pc.onnegotiationneeded = async () => {
        try {
          const offer = await pc.createOffer();
          await pc.setLocalDescription(offer);
          this.sendSignal(remoteSessionId, 'offer', pc.localDescription);
        } catch (e) {
          console.warn('Offer error:', e);
        }
      };
    }

    return pc;
  }

  async handleSignal(signal) {
    const { from_session, type, payload } = signal;
    let data = payload;
    if (typeof data === 'string') {
      try { data = JSON.parse(data); } catch (e) {}
    }

    let pc = this.peerConnections[from_session];
    if (!pc) {
      pc = this.createPeerConnection(from_session, false);
    }

    try {
      if (type === 'offer') {
        await pc.setRemoteDescription(new RTCSessionDescription(data));
        const answer = await pc.createAnswer();
        await pc.setLocalDescription(answer);
        this.sendSignal(from_session, 'answer', pc.localDescription);
      } else if (type === 'answer') {
        if (pc.signalingState === 'have-local-offer') {
          await pc.setRemoteDescription(new RTCSessionDescription(data));
        }
      } else if (type === 'candidate' && data) {
        await pc.addIceCandidate(new RTCIceCandidate(data)).catch(() => {});
      }
    } catch (err) {
      console.warn('Signal handling notice:', err);
    }
  }

  sendSignal(toSession, type, payload) {
    API.post(`/api/rooms/${this.roomCode}/signal`, {
      to_session: toSession,
      type,
      payload
    }).catch(() => {});
  }

  attachRemoteStream(sessionId, stream) {
    const tile = document.getElementById(`participant-tile-${sessionId}`);
    if (tile) {
      let videoEl = tile.querySelector('video');
      if (!videoEl) {
        videoEl = document.createElement('video');
        videoEl.className = 'participant-video';
        videoEl.autoplay = true;
        videoEl.playsInline = true;
        tile.appendChild(videoEl);
      }
      videoEl.srcObject = stream;
      videoEl.classList.remove('hidden');
      videoEl.play().catch(() => {});

      const avatar = tile.querySelector('.participant-avatar-fallback');
      if (avatar) avatar.classList.add('hidden');
    }
  }

  setupSpeakingDetector() {
    if (!this.localStream) return;
    const audioTrack = this.localStream.getAudioTracks()[0];
    if (!audioTrack) return;

    try {
      const AudioCtx = window.AudioContext || window.webkitAudioContext;
      this.audioContext = new AudioCtx();
      const source = this.audioContext.createMediaStreamSource(this.localStream);
      this.analyser = this.audioContext.createAnalyser();
      this.analyser.fftSize = 256;
      source.connect(this.analyser);

      const bufferLength = this.analyser.frequencyBinCount;
      const dataArray = new Uint8Array(bufferLength);

      const checkVolume = () => {
        if (!this.analyser || this.isMicMuted) {
          requestAnimationFrame(checkVolume);
          return;
        }
        this.analyser.getByteFrequencyData(dataArray);
        let sum = 0;
        for (let i = 0; i < bufferLength; i++) {
          sum += dataArray[i];
        }
        const avg = sum / bufferLength;
        const isSpeaking = avg > 20;

        if (this.onSpeakingChange && this.participantInfo?.session_id) {
          this.onSpeakingChange(this.participantInfo.session_id, isSpeaking);
        }

        requestAnimationFrame(checkVolume);
      };

      checkVolume();
    } catch (e) {}
  }

  toggleMicrophone() {
    this.isMicMuted = !this.isMicMuted;
    if (this.localStream) {
      this.localStream.getAudioTracks().forEach(t => t.enabled = !this.isMicMuted);
    }
    return !this.isMicMuted;
  }

  toggleCamera() {
    this.isCamMuted = !this.isCamMuted;
    if (this.localStream) {
      this.localStream.getVideoTracks().forEach(t => t.enabled = !this.isCamMuted);
    }
    return !this.isCamMuted;
  }

  disconnect() {
    if (this.localStream) {
      this.localStream.getTracks().forEach(t => t.stop());
    }
    if (this.audioContext) {
      this.audioContext.close().catch(() => {});
    }
    Object.values(this.peerConnections).forEach(pc => pc.close());
    this.peerConnections = {};
  }
}
