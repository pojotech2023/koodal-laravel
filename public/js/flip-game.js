/**
 * Flip the Tiles 1v1 Online Memory Game Client App
 */

class FlipFireworks {
  static firecrackers() {
    let canvas = document.getElementById('crackers-canvas');
    if (!canvas) {
      canvas = document.createElement('canvas');
      canvas.id = 'crackers-canvas';
      document.body.appendChild(canvas);
    }
    const ctx = canvas.getContext('2d');
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;

    const particles = [];
    const colors = ['#f97316', '#ec4899', '#3b82f6', '#10b981', '#eab308', '#a855f7', '#ef4444'];

    // Create 3 burst points across the screen
    const centers = [
      { x: canvas.width * 0.25, y: canvas.height * 0.4 },
      { x: canvas.width * 0.5, y: canvas.height * 0.3 },
      { x: canvas.width * 0.75, y: canvas.height * 0.4 }
    ];

    centers.forEach(c => {
      for (let i = 0; i < 40; i++) {
        const angle = Math.random() * Math.PI * 2;
        const speed = Math.random() * 8 + 3;
        particles.push({
          x: c.x,
          y: c.y,
          vx: Math.cos(angle) * speed,
          vy: Math.sin(angle) * speed - 2,
          radius: Math.random() * 4 + 2,
          color: colors[Math.floor(Math.random() * colors.length)],
          alpha: 1,
          decay: Math.random() * 0.02 + 0.015,
          gravity: 0.15
        });
      }
    });

    let startTime = performance.now();
    function render(now) {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      let alive = false;

      particles.forEach(p => {
        if (p.alpha > 0) {
          alive = true;
          p.x += p.vx;
          p.y += p.vy;
          p.vy += p.gravity;
          p.alpha -= p.decay;

          ctx.beginPath();
          ctx.arc(p.x, p.y, Math.max(0, p.radius), 0, Math.PI * 2);
          ctx.fillStyle = p.color;
          ctx.globalAlpha = Math.max(0, p.alpha);
          ctx.fill();
        }
      });

      if (alive && (now - startTime < 3000)) {
        requestAnimationFrame(render);
      } else {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
      }
    }
    requestAnimationFrame(render);
  }
}

class FlipGameApp {
  constructor(config) {
    this.code = config.code;
    this.you = config.you;
    this.partnerId = config.partnerId || 1;
    this.csrfToken = config.csrfToken;
    this.stateUrl = `/flip/${this.code}/state`;
    this.flipUrl = `/flip/${this.code}/flip`;
    this.previewUrl = `/flip/${this.code}/preview`;
    this.readyUrl = `/flip/${this.code}/ready`;
    this.chatUrl = `/flip/${this.code}/chat`;

    this.pollTimer = null;
    this.isRequestInProgress = false;
    this.currentState = null;
    this.preloadedImages = new Set();
    this.lastMessageCount = 0;
    this.gameTimer = null;
    this.remainingSeconds = 300; // 5 Minutes total game duration
    this.hasCountedDown = false;
    this.isCountingDown = false;

    this.initElements();
    this.bindEvents();
    this.startPolling();
  }

  initElements() {
    this.boardEl = document.getElementById('flip-board');
    this.statusTextEl = document.getElementById('status-text');
    this.timerTextEl = document.getElementById('timer-text');
    this.p1ScoreEl = document.getElementById('p1-score');
    this.p2ScoreEl = document.getElementById('p2-score');
    this.p1NameEl = document.getElementById('p1-name');
    this.p2NameEl = document.getElementById('p2-name');
    this.p1CardEl = document.getElementById('p1-card');
    this.p2CardEl = document.getElementById('p2-card');
    this.previewBtn = document.getElementById('preview-btn');
    this.waitingOverlay = document.getElementById('waiting-overlay');
    this.readySection = document.getElementById('ready-section');
    this.readyBtn = document.getElementById('ready-btn');
    this.p1ReadyStatus = document.getElementById('p1-ready-status');
    this.p2ReadyStatus = document.getElementById('p2-ready-status');
    this.countdownOverlay = document.getElementById('countdown-overlay');
    this.countdownRuleBox = document.getElementById('countdown-rule-box');
    this.countdownNumberBox = document.getElementById('countdown-number-box');
    this.ruleCountdownSec = document.getElementById('rule-countdown-sec');
    this.countdownNumber = document.getElementById('countdown-number');
    this.resultModal = document.getElementById('result-modal');
    this.resultTitleEl = document.getElementById('result-title');
    this.resultDescEl = document.getElementById('result-desc');
    this.replayGameBtn = document.getElementById('flip-replay-game-btn');
    this.nextGameBtn = document.getElementById('flip-next-game-btn');
    this.toastEl = document.getElementById('flip-toast');
    this.chatBoxEl = document.getElementById('chat-messages');
    this.chatFormEl = document.getElementById('chat-form');
    this.chatInputEl = document.getElementById('chat-input');
    this.copyLinkBtn = document.getElementById('copy-invite-btn');
  }

  bindEvents() {
    // Board tile clicks using event delegation
    if (this.boardEl) {
      this.boardEl.addEventListener('click', (e) => {
        const tileEl = e.target.closest('.tile-item');
        if (tileEl) {
          const index = parseInt(tileEl.dataset.index, 10);
          this.handleTileClick(index);
        }
      });
    }

    // Preview button
    if (this.previewBtn) {
      this.previewBtn.addEventListener('click', () => this.handlePreviewClick());
    }

    // Ready button
    if (this.readyBtn) {
      this.readyBtn.addEventListener('click', () => this.handleReadyClick());
    }

    // Chat form
    if (this.chatFormEl) {
      this.chatFormEl.addEventListener('submit', (e) => {
        e.preventDefault();
        this.handleSendChat();
      });
    }

    // Quick Reaction buttons delegation
    document.querySelectorAll('.reaction-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const text = btn.dataset.msg || btn.textContent.trim();
        this.sendQuickReaction(text);
      });
    });

    // Copy link button
    if (this.copyLinkBtn) {
      this.copyLinkBtn.addEventListener('click', () => this.copyInviteLink());
    }

    // Bridge floating chat widget quick reactions
    window.addEventListener('koodal-quick-chat-send', (e) => {
      if (e.detail && e.detail.message) {
        this.handleSendChat(e.detail.message);
      }
    });
  }

  startCountdownTimer() {
    // Untimed gameplay - timer disabled per user request
    if (this.gameTimer) clearInterval(this.gameTimer);
  }

  renderTimer() {
    // Untimed gameplay
  }

  startPolling() {
    const poll = async () => {
      await this.fetchState();
      this.pollTimer = setTimeout(poll, 1000);
    };
    poll();
  }

  stopPolling() {
    if (this.pollTimer) {
      clearTimeout(this.pollTimer);
      this.pollTimer = null;
    }
  }

  async fetchState() {
    try {
      const response = await fetch(this.stateUrl, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      if (!response.ok) return;

      const state = await response.json();
      this.renderState(state);
    } catch (err) {
      console.warn('State poll failed', err);
    }
  }

  renderState(state) {
    this.currentState = state;

    // Update player role if not assigned initially
    if (state.you && !this.you) {
      this.you = state.you;
    }

    // Update player names & scores
    if (this.p1NameEl) this.p1NameEl.textContent = state.names['1'] || 'Player 1';
    if (this.p2NameEl) this.p2NameEl.textContent = state.names['2'] || 'Player 2';
    if (this.p1ScoreEl) this.p1ScoreEl.textContent = state.scores['1'] || 0;
    if (this.p2ScoreEl) this.p2ScoreEl.textContent = state.scores['2'] || 0;

    // Update active turn card highlights
    if (this.p1CardEl) {
      this.p1CardEl.classList.toggle('is-active', state.turn === 1 && state.status === 'playing');
    }
    if (this.p2CardEl) {
      this.p2CardEl.classList.toggle('is-active', state.turn === 2 && state.status === 'playing');
    }

    // Update Ready Section
    this.renderReadySection(state);

    // Update status text
    this.updateStatusBanner(state);

    // Toggle waiting screen overlay
    if (this.waitingOverlay) {
      const isP2Joined = Boolean(state.p2_joined) || (state.names && state.names['2'] && state.names['2'] !== 'Player 2') || (state.ready && (state.ready['1'] || state.ready['2']));
      if (state.status === 'waiting' && !isP2Joined) {
        this.waitingOverlay.classList.remove('hidden');
      } else {
        this.waitingOverlay.classList.add('hidden');
      }
    }

    // Update preview button state
    if (this.previewBtn) {
      const isMyTurn = state.turn === this.you;
      const canUsePreview = isMyTurn && !state.preview_used && state.status === 'playing' && state.flipped.length === 0 && (!state.preview || !state.preview.active);
      this.previewBtn.disabled = !canUsePreview;
      if (state.preview_used) {
        this.previewBtn.classList.add('opacity-50');
        this.previewBtn.title = 'Preview used';
      }
    }

    // Preload images if preview active or on match
    if (state.preview && state.preview.images) {
      state.preview.images.forEach(img => this.preloadImage(img.image));
    }

    // Render Board Grid
    this.renderBoard(state);

    // Render Chat Messages & trigger crackers animation if new celebration message
    if (state.messages) {
      this.renderChat(state.messages);
    }

    // Result modal if finished or all 16 tiles matched
    if (state.status === 'finished' || (state.matched && state.matched.length >= 16)) {
      this.showResultModal(state);
    }
  }

  renderReadySection(state) {
    if (!this.readySection) return;

    if (state.status === 'finished' || state.status === 'abandoned') {
      this.readySection.classList.add('hidden');
      return;
    }

    const readyObj = state.ready || {};

    if (this.p1ReadyStatus) {
      const isP1Ready = Boolean(readyObj['1']);
      this.p1ReadyStatus.textContent = isP1Ready ? 'Ready ✓' : 'Waiting ⏳';
      this.p1ReadyStatus.className = `ready-status-badge ${isP1Ready ? 'is-ready' : 'not-ready'}`;
    }

    if (this.p2ReadyStatus) {
      const isP2Ready = Boolean(readyObj['2']);
      this.p2ReadyStatus.textContent = isP2Ready ? 'Ready ✓' : 'Waiting ⏳';
      this.p2ReadyStatus.className = `ready-status-badge ${isP2Ready ? 'is-ready' : 'not-ready'}`;
    }

    // Trigger countdown when both players are ready and countdown hasn't run yet
    if (readyObj['1'] && readyObj['2'] && !this.hasCountedDown) {
      this.trigger321Countdown();
    }

    // While counting down, hide readySection so countdown is full focus
    if (this.isCountingDown) {
      this.readySection.classList.add('hidden');
      return;
    }

    // If game has transitioned to playing and countdown has finished, hide ready section
    if (state.status === 'playing' && this.hasCountedDown) {
      this.readySection.classList.add('hidden');
      if (this.waitingOverlay) this.waitingOverlay.classList.add('hidden');
      return;
    }

    // Otherwise (in waiting state or before countdown completes), KEEP READY SECTION VISIBLE!
    this.readySection.classList.remove('hidden');

    const amIReady = this.you ? Boolean(readyObj[String(this.you)]) : false;
    if (this.readyBtn) {
      if (amIReady) {
        this.readyBtn.innerHTML = '<span>✓</span><span>You are Ready! Waiting for partner...</span>';
        this.readyBtn.disabled = true;
        this.readyBtn.className = 'flip-btn-start is-ready';
      } else {
        this.readyBtn.innerHTML = '<span>🚀</span><span>Start the Game</span>';
        this.readyBtn.disabled = false;
        this.readyBtn.className = 'flip-btn-start';
      }
    }
  }

  trigger321Countdown() {
    if (this.hasCountedDown || this.isCountingDown) return;
    this.isCountingDown = true;

    if (!this.countdownOverlay) {
      this.hasCountedDown = true;
      this.isCountingDown = false;
      if (this.currentState) this.currentState.status = 'playing';
      return;
    }

    // Immediately hide readySection and waitingOverlay
    if (this.readySection) this.readySection.classList.add('hidden');
    if (this.waitingOverlay) this.waitingOverlay.classList.add('hidden');

    this.countdownOverlay.classList.remove('hidden');

    // STEP 1: Show Secret Love Rule for 5 seconds before 3-2-1 count starts
    if (this.countdownRuleBox) this.countdownRuleBox.classList.remove('hidden');
    if (this.countdownNumberBox) this.countdownNumberBox.classList.add('hidden');

    let ruleSecs = 5;
    if (this.ruleCountdownSec) this.ruleCountdownSec.textContent = ruleSecs;

    const ruleInterval = setInterval(() => {
      ruleSecs--;
      if (this.ruleCountdownSec) this.ruleCountdownSec.textContent = ruleSecs;

      if (ruleSecs <= 0) {
        clearInterval(ruleInterval);

        // STEP 2: Start 3-2-1 countdown
        if (this.countdownRuleBox) this.countdownRuleBox.classList.add('hidden');
        if (this.countdownNumberBox) this.countdownNumberBox.classList.remove('hidden');

        let count = 3;
        if (this.countdownNumber) this.countdownNumber.textContent = count;
        if (window.KoodalGameAudio) {
          window.KoodalGameAudio.playCountdown(count);
        }

        const countInterval = setInterval(() => {
          count--;
          if (count > 0) {
            if (this.countdownNumber) this.countdownNumber.textContent = count;
            if (window.KoodalGameAudio) {
              window.KoodalGameAudio.playCountdown(count);
            }
          } else if (count === 0) {
            if (this.countdownNumber) this.countdownNumber.textContent = 'GO! 🚀';
            if (window.KoodalGameAudio) {
              window.KoodalGameAudio.playCountdown('go');
            }
            FlipFireworks.firecrackers();
          } else {
            clearInterval(countInterval);
            this.countdownOverlay.classList.add('hidden');
            if (this.waitingOverlay) this.waitingOverlay.classList.add('hidden');
            if (this.readySection) this.readySection.classList.add('hidden');
            this.hasCountedDown = true;
            this.isCountingDown = false;
            // Instantly ensure status is active
            if (this.currentState) {
              this.currentState.status = 'playing';
            }
            this.fetchState();
          }
        }, 900);
      }
    }, 1000);
  }

  updateStatusBanner(state) {
    if (!this.statusTextEl) return;

    if (state.status === 'waiting') {
      if (!state.ready || !state.ready.both) {
        this.statusTextEl.innerHTML = '🤝 Both players must click <strong>Start the Game</strong>!';
      } else {
        this.statusTextEl.innerHTML = '⏳ Waiting for opponent to join... Share invite link!';
      }
      return;
    }

    if (state.status === 'abandoned') {
      this.statusTextEl.innerHTML = '⚠️ Game abandoned due to inactivity.';
      return;
    }

    if (state.status === 'finished') {
      this.statusTextEl.innerHTML = '🎉 Game Finished!';
      return;
    }

    const isMyTurn = state.turn === this.you;
    const currentName = state.names[state.turn] || `Player ${state.turn}`;

    if (state.preview && state.preview.active) {
      this.statusTextEl.innerHTML = '👁️ <strong>PREVIEW ACTIVE!</strong> Remembering tile positions...';
      return;
    }

    if (state.flipped.length === 2) {
      this.statusTextEl.innerHTML = `🔍 <strong>Not a match!</strong> Memorize these tiles (closing soon)...`;
      return;
    }

    if (state.flipped.length === 1) {
      if (isMyTurn) {
        this.statusTextEl.innerHTML = `🎯 <strong>YOUR TURN!</strong> Pick the 2nd tile to complete match!`;
      } else {
        this.statusTextEl.innerHTML = `⌛ <strong>${this.escapeHtml(currentName)}'s turn</strong> to pick 2nd tile...`;
      }
      return;
    }

    if (isMyTurn) {
      this.statusTextEl.innerHTML = `🎯 <strong>YOUR TURN!</strong> Open 1st tile.`;
    } else {
      this.statusTextEl.innerHTML = `⌛ <strong>${this.escapeHtml(currentName)}'s turn</strong> to open tile...`;
    }
  }

  renderBoard(state) {
    if (!this.boardEl) return;

    // Build maps for quick lookup
    const matchedMap = new Map();
    (state.matched || []).forEach(m => matchedMap.set(m.index, m));

    const flippedMap = new Map();
    (state.flipped || []).forEach(f => flippedMap.set(f.index, f));

    const previewMap = new Map();
    if (state.preview && state.preview.images) {
      state.preview.images.forEach(p => previewMap.set(p.index, p.image));
    }

    // Build or update 16 tiles (4x4 board)
    for (let i = 0; i < 16; i++) {
      let tileEl = this.boardEl.children[i];
      if (!tileEl) continue;

      const isMatched = matchedMap.has(i);
      const isFlipped = flippedMap.has(i);
      const isPreview = previewMap.has(i);

      const matchedObj = matchedMap.get(i);
      const flippedObj = flippedMap.get(i);
      const previewUrl = previewMap.get(i);

      const frontImgEl = tileEl.querySelector('.tile-front img');
      const backFaceEl = tileEl.querySelector('.tile-back');

      // Use setting card back image if available
      if (state.card_back && backFaceEl) {
        let backImg = backFaceEl.querySelector('img');
        if (!backImg) {
          backFaceEl.innerHTML = `<img src="${state.card_back}" alt="Card Back" class="w-full h-full object-cover">`;
        }
      }

      if (isMatched) {
        tileEl.classList.add('is-matched', 'is-flipped');
        tileEl.classList.remove('matched-by-1', 'matched-by-2');
        if (matchedObj.by === 1) tileEl.classList.add('matched-by-1');
        if (matchedObj.by === 2) tileEl.classList.add('matched-by-2');

        if (frontImgEl && matchedObj.image) {
          frontImgEl.src = matchedObj.image;
        }
      } else if (isFlipped) {
        tileEl.classList.add('is-flipped');
        tileEl.classList.remove('is-matched', 'matched-by-1', 'matched-by-2');

        if (frontImgEl && flippedObj.image) {
          frontImgEl.src = flippedObj.image;
          this.preloadImage(flippedObj.image);
        }
      } else if (isPreview) {
        tileEl.classList.add('is-flipped');
        tileEl.classList.remove('is-matched', 'matched-by-1', 'matched-by-2');

        if (frontImgEl && previewUrl) {
          frontImgEl.src = previewUrl;
        }
      } else {
        // Face down
        tileEl.classList.remove('is-flipped', 'is-matched', 'matched-by-1', 'matched-by-2');
      }
    }
  }

  async handleTileClick(index) {
    if (!this.currentState) return;
    if (this.isRequestInProgress) return;

    if (this.currentState.status !== 'playing') {
      if (this.currentState.status === 'waiting') {
        const readyObj = this.currentState.ready || {};
        const amIReady = this.you ? Boolean(readyObj[String(this.you)]) : false;
        if (!amIReady) {
          this.showToast("Please click 'Start the Game' above to begin!", 'warning');
        } else {
          this.showToast("Waiting for your partner to click 'Start the Game'!", 'info');
        }
        if (this.readyBtn) {
          this.readyBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
          this.readyBtn.classList.add('animate-pulse');
          setTimeout(() => this.readyBtn && this.readyBtn.classList.remove('animate-pulse'), 1500);
        }
      }
      return;
    }

    if (this.currentState.turn !== this.you) {
      this.showToast("It's not your turn!", 'warning');
      return;
    }

    const matchedIndices = (this.currentState.matched || []).map(m => m.index);
    if (matchedIndices.includes(index)) return;

    const flippedIndices = (this.currentState.flipped || []).map(f => f.index);
    if (flippedIndices.includes(index)) return;

    if (this.currentState.preview && this.currentState.preview.active) return;

    this.isRequestInProgress = true;

    try {
      const response = await fetch(this.flipUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': this.csrfToken,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ tile: index })
      });

      const data = await response.json();

      if (!response.ok) {
        this.showToast(data.message || 'Flip failed', 'error');
        this.isRequestInProgress = false;
        return;
      }

      // Immediately set tile image & flip locally
      const tileEl = this.boardEl.children[index];
      if (tileEl && data.image) {
        const frontImgEl = tileEl.querySelector('.tile-front img');
        if (frontImgEl) frontImgEl.src = data.image;
        tileEl.classList.add('is-flipped');
      }

      if (window.KoodalGameAudio) {
        if (data.matched) {
          window.KoodalGameAudio.playMatchSuccess();
        } else {
          window.KoodalGameAudio.playSelect();
        }
      }

      // Trigger crackers if match occurred
      if (data.matched) {
        FlipFireworks.firecrackers();
        const firstFlipperRole = data.awarded_to || (this.you === 1 ? 2 : 1);
        const recipientName = (this.currentState && this.currentState.names && this.currentState.names[firstFlipperRole]) 
          ? this.currentState.names[firstFlipperRole] 
          : 'Partner';
        const isMe = (firstFlipperRole === this.you);
        this.showToast(isMe ? `✨ Match! +1 Point awarded to You!` : `✨ Match! You scored +1 point for ${recipientName}!`, 'success');
      }

      // Refresh state immediately after flip
      await this.fetchState();
    } catch (err) {
      this.showToast('Network error while flipping tile', 'error');
    } finally {
      this.isRequestInProgress = false;
    }
  }

  async handleReadyClick() {
    if (this.isRequestInProgress) return;
    if (window.KoodalGameAudio) {
      window.KoodalGameAudio.playSelect();
    }
    this.isRequestInProgress = true;
    if (this.readyBtn) {
      this.readyBtn.textContent = '⏳ Readying...';
      this.readyBtn.disabled = true;
    }

    try {
      const response = await fetch(this.readyUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': this.csrfToken,
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      if (response.ok) {
        await this.fetchState();
      } else {
        const errData = await response.json().catch(() => ({}));
        this.showToast(errData.message || 'Failed to update ready state', 'error');
        if (this.readyBtn) {
          this.readyBtn.disabled = false;
        }
      }
    } catch (err) {
      this.showToast('Failed to update ready state', 'error');
      if (this.readyBtn) {
        this.readyBtn.disabled = false;
      }
    } finally {
      this.isRequestInProgress = false;
    }
  }

  async handlePreviewClick() {
    if (!this.currentState || this.isRequestInProgress) return;

    this.isRequestInProgress = true;

    try {
      const response = await fetch(this.previewUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': this.csrfToken,
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      const data = await response.json();

      if (!response.ok) {
        this.showToast(data.message || 'Preview failed', 'error');
      } else {
        await this.fetchState();
      }
    } catch (err) {
      this.showToast('Failed to trigger preview', 'error');
    } finally {
      this.isRequestInProgress = false;
    }
  }

  sendQuickReaction(text) {
    if (window.triggerKoodalCelebration) {
      window.triggerKoodalCelebration(text);
    }
    if (window.pushKoodalChatStream) {
      window.pushKoodalChatStream(text, true);
    }
    this.handleSendChat(text);
  }

  async handleSendChat(directText = null) {
    const text = directText !== null ? String(directText).trim() : (this.chatInputEl ? this.chatInputEl.value.trim() : '');
    if (!text) return;

    if (directText === null && this.chatInputEl) {
      this.chatInputEl.value = '';
    }

    if (window.triggerKoodalCelebration) {
      window.triggerKoodalCelebration(text);
    }
    if (window.pushKoodalChatStream) {
      window.pushKoodalChatStream(text, true);
    }

    try {
      const response = await fetch(this.chatUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': this.csrfToken,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ message: text })
      });

      if (response.ok) {
        // Trigger crackers celebration animation locally for reaction messages
        if (this.isReactionMsg(text)) {
          FlipFireworks.firecrackers();
        }
        await this.fetchState();
      }
    } catch (err) {
      console.warn('Send chat error', err);
    }
  }

  isReactionMsg(text) {
    const triggers = ['Good', 'Super', 'Wow', 'Excellent', 'Nice', 'All the Best', 'Fantastic', 'Awesome', 'Well Played', 'Love it', '😊', '👍', '🔥', '🎉', '👏', '⭐', '💖', '❤️'];
    return triggers.some(t => text.toLowerCase().includes(t.toLowerCase()));
  }

  renderChat(messages) {
    if (Array.isArray(messages) && window.syncKoodalChatMessages) {
      const mapped = messages.map((msg, idx) => ({
        id: msg.id || ('flip-msg-' + idx + '-' + (msg.time || '')),
        message: msg.text || '',
        sender_name: msg.name || '',
        mine: Number(msg.sender) === Number(this.you),
        time: msg.time || ''
      }));
      const partnerRole = this.you === 1 ? '2' : '1';
      const partnerName = (this.currentState && this.currentState.names && this.currentState.names[partnerRole])
        ? this.currentState.names[partnerRole]
        : 'Partner';
      window.syncKoodalChatMessages(mapped, 0, partnerName);
    }

    if (messages.length > this.lastMessageCount && this.lastMessageCount > 0) {
      const latestMsg = messages[messages.length - 1];
      if (latestMsg && this.isReactionMsg(latestMsg.text)) {
        FlipFireworks.firecrackers();
      }
    }

    this.lastMessageCount = messages.length;
    if (!this.chatBoxEl) return;
    this.chatBoxEl.innerHTML = '';

    messages.forEach(msg => {
      const bubbleEl = document.createElement('div');
      const isP1 = msg.sender === 1;
      const isYou = msg.sender === this.you;
      const avatar = isP1 ? '🤵' : '👰';

      bubbleEl.className = `chat-bubble flex gap-2 ${isP1 ? 'chat-bubble-p1' : 'chat-bubble-p2'} ${isYou ? 'ml-auto' : 'mr-auto'}`;

      bubbleEl.innerHTML = `
        <span class="text-sm shrink-0">${avatar}</span>
        <div class="flex-1">
          <div class="flex items-center justify-between gap-3 text-[10px] font-extrabold opacity-75 mb-0.5">
            <span>${this.escapeHtml(msg.name)} ${isYou ? '(You)' : ''}</span>
            <span>${msg.time}</span>
          </div>
          <div class="font-semibold text-xs leading-snug">${this.escapeHtml(msg.text)}</div>
        </div>
      `;

      this.chatBoxEl.appendChild(bubbleEl);
    });

    this.chatBoxEl.scrollTop = this.chatBoxEl.scrollHeight;
  }

  showResultModal(state) {
    if (!this.resultModal) return;

    if (!this.playedFinalClap) {
      this.playedFinalClap = true;
      FlipFireworks.firecrackers();
      if (window.KoodalGameAudio) {
        window.KoodalGameAudio.playFinalClapAndFanfare();
      }
    }

    const p1Score = parseInt((state.scores && state.scores['1']) ? state.scores['1'] : 0, 10);
    const p2Score = parseInt((state.scores && state.scores['2']) ? state.scores['2'] : 0, 10);
    const diff = Math.abs(p1Score - p2Score);

    // Chemistry percentage balance table:
    // 4 - 4: 100%, 5 - 3: 80%, 6 - 2: 60%, 7 - 1: 30%, 8 - 0: 0%
    let chemistryPct = (state.chemistry_score !== undefined && state.chemistry_score !== null)
      ? state.chemistry_score
      : null;

    if (chemistryPct === null) {
      if (diff === 0) chemistryPct = 100;
      else if (diff <= 2) chemistryPct = 80;
      else if (diff <= 4) chemistryPct = 60;
      else if (diff <= 6) chemistryPct = 30;
      else chemistryPct = 0;
    }

    let chemistryMsg = 'Balance Mismatch';
    if (chemistryPct === 100) {
      chemistryMsg = 'Perfect Team Balance (100% Match!)';
    } else if (chemistryPct >= 80) {
      chemistryMsg = 'Great Chemistry (80% Match!)';
    } else if (chemistryPct >= 60) {
      chemistryMsg = 'Good Chemistry (60% Match!)';
    } else if (chemistryPct >= 30) {
      chemistryMsg = 'Room for Chemistry (30% Match)';
    } else {
      chemistryMsg = 'Single Player Domination (0% Balance)';
    }

    if (state.winner === 'draw') {
      this.resultTitleEl.textContent = "🤝 It's a Draw!";
      this.resultDescEl.textContent = `Both players matched equal pairs (${p1Score} - ${p2Score})!`;
    } else {
      const winnerName = (state.names && state.names[state.winner]) ? state.names[state.winner] : `Player ${state.winner}`;
      const isWinner = state.winner === this.you;
      this.resultTitleEl.textContent = isWinner ? "🏆 You Won!" : `🎉 ${winnerName} Won!`;
      this.resultDescEl.textContent = `Final Score: ${state.names['1']} (${p1Score}) vs ${state.names['2']} (${p2Score})`;
    }

    // Populate Chemistry score elements
    const chemScoreBadge = document.getElementById('chemistry-score-badge');
    const chemScoreDetail = document.getElementById('chemistry-score-detail');
    const chemProgressBar = document.getElementById('chemistry-progress-bar');

    if (chemScoreBadge) {
      chemScoreBadge.textContent = `${chemistryPct}%`;
    }
    if (chemScoreDetail) {
      chemScoreDetail.textContent = `${chemistryMsg} · ${p1Score} vs ${p2Score} Pairs`;
    }
    if (chemProgressBar) {
      chemProgressBar.style.width = `${chemistryPct}%`;
    }

    const pId = this.partnerId || (state && state.partner_id) || 1;
    const vibeBtn = document.getElementById('flip-vibe-btn');
    if (vibeBtn) {
      vibeBtn.href = `/games/${pId}/vibe`;
    }
    const hubBtn = document.getElementById('flip-hub-btn');
    if (hubBtn) {
      hubBtn.href = `/games/${pId}`;
    }
    const replayBtn = document.getElementById('flip-replay-game-btn');
    if (replayBtn) {
      replayBtn.onclick = () => {
        replayBtn.disabled = true;
        replayBtn.textContent = '⏳ Sending Replay Invite...';
        fetch(`/games/${pId}/invite`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': this.csrfToken
          },
          body: JSON.stringify({ game_type: 'flip', force_fresh: true })
        })
          .then(r => r.json().catch(() => null))
          .then(d => {
            replayBtn.textContent = '⏳ Replay Invite Sent! Waiting for partner...';
            this.showToast('📢 Replay invitation sent to partner! Waiting for them to accept...', 'info');
          })
          .catch(() => {
            replayBtn.disabled = false;
            replayBtn.textContent = '🔄 Replay Game (Fresh Match with Partner)';
            this.showToast('Could not send replay invitation', 'warning');
          });
      };
    }

    const nextGameBtn = document.getElementById('flip-next-game-btn');
    if (nextGameBtn) {
      nextGameBtn.onclick = () => {
        nextGameBtn.disabled = true;
        nextGameBtn.textContent = '⏳ Sending Invite & Opening Memory Snap...';
        fetch(`/games/${pId}/invite`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': this.csrfToken
          },
          body: JSON.stringify({ game_type: 'memory_snap', force_fresh: true })
        })
          .then(r => r.json().catch(() => null))
          .then(d => {
            if (d && d.redirect_url) {
              window.location.href = d.redirect_url;
            } else {
              window.location.href = `/memory-snap/start/${pId}`;
            }
          })
          .catch(() => {
            window.location.href = `/memory-snap/start/${pId}`;
          });
      };
    }

    this.resultModal.classList.remove('hidden');
  }

  copyInviteLink() {
    if (!this.currentState || !this.currentState.invite_url) return;
    navigator.clipboard.writeText(this.currentState.invite_url).then(() => {
      this.showToast('Invite link copied to clipboard!', 'success');
    }).catch(() => {
      this.showToast('Could not copy link', 'warning');
    });
  }

  preloadImage(url) {
    if (!url || this.preloadedImages.has(url)) return;
    const img = new Image();
    img.src = url;
    this.preloadedImages.add(url);
  }

  showToast(message, type = 'info') {
    if (!this.toastEl) return;
    this.toastEl.textContent = message;

    this.toastEl.className = 'flip-toast px-4 py-2.5 rounded-full text-xs font-bold text-white shadow-lg show ';
    if (type === 'error') this.toastEl.classList.add('bg-red-600');
    else if (type === 'warning') this.toastEl.classList.add('bg-amber-600');
    else if (type === 'success') this.toastEl.classList.add('bg-emerald-600');
    else this.toastEl.classList.add('bg-slate-800');

    setTimeout(() => {
      this.toastEl.classList.remove('show');
    }, 3000);
  }

  escapeHtml(str) {
    return String(str || '').replace(/[&<>"']/g, m => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    })[m]);
  }
}

window.FlipGameApp = FlipGameApp;
