/**
 * MyFlix Main Application Script
 */
document.addEventListener('DOMContentLoaded', async () => {
  // 1. Initialize API & Session
  await API.init();

  // 2. Navigation bar scroll effect
  const navbar = document.querySelector('.navbar');
  if (navbar) {
    window.addEventListener('scroll', () => {
      if (window.scrollY > 40) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }
    });
  }

  // 3. Horizontal Sliders Arrow Navigation
  document.querySelectorAll('.slider-wrapper').forEach(wrapper => {
    const slider = wrapper.querySelector('.card-slider');
    const leftArrow = wrapper.querySelector('.slider-arrow.left');
    const rightArrow = wrapper.querySelector('.slider-arrow.right');

    if (slider && leftArrow && rightArrow) {
      leftArrow.addEventListener('click', () => {
        slider.scrollBy({ left: -slider.clientWidth * 0.75, behavior: 'smooth' });
      });
      rightArrow.addEventListener('click', () => {
        slider.scrollBy({ left: slider.clientWidth * 0.75, behavior: 'smooth' });
      });
    }
  });

  // 4. Search live filtering
  const searchInput = document.getElementById('nav-search-input');
  const searchDropdown = document.getElementById('search-dropdown');
  let searchTimeout;

  if (searchInput && searchDropdown) {
    searchInput.addEventListener('input', (e) => {
      clearTimeout(searchTimeout);
      const query = e.target.value.trim();

      if (query.length < 2) {
        searchDropdown.classList.remove('active');
        searchDropdown.innerHTML = '';
        return;
      }

      searchTimeout = setTimeout(async () => {
        try {
          const res = await API.get('/api/movies/search', { q: query });
          if (res.results && res.results.length > 0) {
            searchDropdown.innerHTML = res.results.map(m => `
              <div class="search-result-item" data-movie-id="${m.id}" onclick="openMovieDetail(${m.id})">
                <img src="${m.poster_url}" class="search-result-poster" alt="${escapeHtml(m.title)}">
                <div class="search-result-info">
                  <div class="search-result-title">${escapeHtml(m.title)}</div>
                  <div class="search-result-meta">${m.release_year} • ${m.genre} • <span class="match-badge">${m.match_percentage}% Match</span></div>
                </div>
              </div>
            `).join('');
            searchDropdown.classList.add('active');
          } else {
            searchDropdown.innerHTML = `
              <div style="padding: 16px; text-align: center; color: var(--text-muted); font-size: 0.85rem;">
                No movies found for "${escapeHtml(query)}"
              </div>
            `;
            searchDropdown.classList.add('active');
          }
        } catch (err) {
          console.warn('Search error:', err);
        }
      }, 250);
    });

    document.addEventListener('click', (e) => {
      if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
        searchDropdown.classList.remove('active');
      }
    });
  }

  // 5. Watch Together Action Buttons
  document.querySelectorAll('.btn-watch-together').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const movieId = btn.getAttribute('data-movie-id');
      const movieTitle = btn.getAttribute('data-movie-title');
      const moviePoster = btn.getAttribute('data-movie-poster');
      openCreateRoomModal(movieId, movieTitle, moviePoster);
    });
  });

  // 6. My List Toggle Buttons
  document.querySelectorAll('.btn-toggle-list').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.stopPropagation();
      const movieId = btn.getAttribute('data-movie-id');
      try {
        const res = await API.post('/api/my-list/toggle', { movie_id: parseInt(movieId) });
        if (res.success) {
          btn.classList.toggle('active', res.in_list);
          const icon = btn.querySelector('i');
          if (icon) {
            icon.className = res.in_list ? 'ph-bold ph-check' : 'ph-bold ph-plus';
          }
          showToast(res.message);
        }
      } catch (err) {
        showToast(err.message, 'error');
      }
    });
  });

  // 7. Room Creation Form Handler
  const createRoomForm = document.getElementById('create-room-form');
  if (createRoomForm) {
    createRoomForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const submitBtn = createRoomForm.querySelector('button[type="submit"]');
      const movieId = document.getElementById('create-room-movie-id').value;
      const roomName = document.getElementById('create-room-name-input').value;
      const maxGuests = document.getElementById('create-room-max-guests').value;

      try {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Creating Party...';

        const res = await API.post('/api/rooms/create', {
          movie_id: parseInt(movieId),
          room_name: roomName,
          max_participants: parseInt(maxGuests)
        });

        if (res.success && res.room_url) {
          window.location.href = res.room_url;
        }
      } catch (err) {
        showToast(err.message || 'Failed to create room', 'error');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Create Watch Room';
      }
    });
  }

  // 8. Auth Modal (Login / Register)
  const authForm = document.getElementById('auth-form');
  if (authForm) {
    authForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const isRegister = authForm.getAttribute('data-mode') === 'register';
      const endpoint = isRegister ? '/api/auth/register' : '/api/auth/login';
      const username = document.getElementById('auth-username-input')?.value;
      const email = document.getElementById('auth-email-input').value;
      const password = document.getElementById('auth-password-input').value;

      try {
        const res = await API.post(endpoint, { username, email, password });
        if (res.success) {
          showToast(res.message);
          closeModal('modal-auth');
          setTimeout(() => window.location.reload(), 600);
        }
      } catch (err) {
        showToast(err.message || 'Authentication failed', 'error');
      }
    });
  }

  // 9. Close Modal Handlers
  document.querySelectorAll('.modal-close-btn, .modal-backdrop').forEach(el => {
    el.addEventListener('click', (e) => {
      if (e.target === el) {
        closeAllModals();
      }
    });
  });
});

/* Global UI Helpers */

function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.add('active');
  }
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.remove('active');
  }
}

function closeAllModals() {
  document.querySelectorAll('.modal-backdrop').forEach(m => m.classList.remove('active'));
}

async function openMovieDetail(movieId) {
  try {
    const res = await API.get(`/api/movie/${movieId}`);
    if (res) {
      document.getElementById('modal-movie-img').src = res.backdrop_url;
      document.getElementById('modal-movie-title').textContent = res.title;
      document.getElementById('modal-movie-match').textContent = `${res.match_percentage}% Match`;
      document.getElementById('modal-movie-year').textContent = res.release_year;
      document.getElementById('modal-movie-age').textContent = res.age_rating;
      document.getElementById('modal-movie-duration').textContent = `${Math.floor(res.duration_seconds / 60)}m`;
      document.getElementById('modal-movie-desc').textContent = res.description;
      document.getElementById('modal-movie-cast').textContent = res.cast_members || 'Ensemble Cast';
      document.getElementById('modal-movie-director').textContent = res.director || 'Visionary Director';
      document.getElementById('modal-movie-genre').textContent = res.genre;

      // Update Watch Together button inside modal
      const watchBtn = document.getElementById('modal-btn-watch-together');
      if (watchBtn) {
        watchBtn.onclick = () => {
          closeModal('modal-movie-detail');
          openCreateRoomModal(res.id, res.title, res.poster_url);
        };
      }

      openModal('modal-movie-detail');
    }
  } catch (err) {
    showToast('Failed to load movie details', 'error');
  }
}

function openCreateRoomModal(movieId, title, poster) {
  document.getElementById('create-room-movie-id').value = movieId;
  document.getElementById('create-room-movie-title').textContent = title;
  document.getElementById('create-room-name-input').value = `${title} Watch Party`;
  if (poster) {
    document.getElementById('create-room-poster-img').src = poster;
  }
  openModal('modal-create-room');
}

function openAuthModal(mode = 'login') {
  const form = document.getElementById('auth-form');
  const title = document.getElementById('auth-modal-title');
  const userField = document.getElementById('auth-username-field');
  const submitBtn = document.getElementById('auth-submit-btn');
  const toggleLink = document.getElementById('auth-toggle-link');

  if (mode === 'register') {
    form.setAttribute('data-mode', 'register');
    title.textContent = 'Sign Up for MyFlix';
    userField.classList.remove('hidden');
    submitBtn.textContent = 'Create Account';
    toggleLink.innerHTML = 'Already have an account? <strong>Sign In</strong>';
  } else {
    form.setAttribute('data-mode', 'login');
    title.textContent = 'Sign In';
    userField.classList.add('hidden');
    submitBtn.textContent = 'Sign In';
    toggleLink.innerHTML = 'New to MyFlix? <strong>Sign up now.</strong>';
  }

  openModal('modal-auth');
}

function toggleAuthMode() {
  const form = document.getElementById('auth-form');
  const current = form.getAttribute('data-mode');
  openAuthModal(current === 'register' ? 'login' : 'register');
}

function showToast(message, type = 'info') {
  let container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.innerHTML = `<i class="ph-bold ${type === 'error' ? 'ph-warning-circle' : 'ph-check-circle'}"></i> <span>${escapeHtml(message)}</span>`;
  container.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(100%)';
    toast.style.transition = 'all 0.3s ease';
    setTimeout(() => toast.remove(), 300);
  }, 3500);
}

function escapeHtml(str) {
  if (!str) return '';
  return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}
