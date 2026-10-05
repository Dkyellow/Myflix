<?php include dirname(__DIR__) . '/views/layout/header.php'; ?>

<!-- Top Navigation -->
<nav class="navbar">
  <div class="nav-left">
    <a href="/" class="brand-logo">
      MYFLIX <span class="brand-logo-party-badge">PARTY</span>
    </a>
    <ul class="nav-links">
      <li><a href="/" class="nav-link active">Home</a></li>
      <li><a href="#trending" class="nav-link">Trending</a></li>
      <li><a href="#popular" class="nav-link">Popular</a></li>
      <li><a href="#action" class="nav-link">Action</a></li>
      <li><a href="#mylist" class="nav-link">My List</a></li>
    </ul>
  </div>

  <div class="nav-right">
    <!-- Search Box -->
    <div class="search-container">
      <div class="search-input-wrapper">
        <i class="ph-bold ph-magnifying-glass" style="color: var(--text-muted); font-size: 1.1rem;"></i>
        <input type="text" id="nav-search-input" class="search-input" placeholder="Titles, genres, cast...">
      </div>
      <div id="search-dropdown" class="search-results-dropdown"></div>
    </div>

    <!-- User Auth / Profile -->
    <?php if ($user): ?>
      <div class="user-menu-btn" onclick="API.post('/api/auth/logout').then(() => window.location.reload())" title="Click to Logout">
        <div class="user-avatar" style="background-color: <?= htmlspecialchars($user['avatar_color'] ?? '#E50914') ?>">
          <?= strtoupper(substr($user['username'] ?? 'U', 0, 1)) ?>
        </div>
        <span class="text-sm font-semibold hidden md:inline"><?= htmlspecialchars($user['username']) ?></span>
      </div>
    <?php else: ?>
      <button class="btn btn-primary btn-sm" onclick="openAuthModal('login')">Sign In</button>
    <?php endif; ?>
  </div>
</nav>

<!-- Cinematic Hero Banner -->
<?php if ($featured): ?>
<section class="hero-banner">
  <div class="hero-backdrop-wrapper">
    <img src="<?= htmlspecialchars($featured['backdrop_url']) ?>" alt="<?= htmlspecialchars($featured['title']) ?>" class="hero-backdrop-img">
    <div class="hero-vignette"></div>
  </div>

  <div class="hero-content">
    <span class="hero-badge">Featured Watch Party</span>
    <h1 class="hero-title"><?= htmlspecialchars($featured['title']) ?></h1>
    
    <div class="hero-meta">
      <span class="match-badge"><?= $featured['match_percentage'] ?>% Match</span>
      <span class="age-badge"><?= htmlspecialchars($featured['age_rating']) ?></span>
      <span><?= floor($featured['duration_seconds'] / 60) ?>m</span>
      <span class="hd-badge">4K ULTRA HD</span>
      <span class="text-secondary"><?= htmlspecialchars($featured['genre']) ?></span>
    </div>

    <p class="hero-desc"><?= htmlspecialchars($featured['description']) ?></p>

    <div class="hero-actions">
      <!-- Primary Watch Together Button -->
      <button class="btn btn-primary btn-lg btn-watch-together"
              data-movie-id="<?= $featured['id'] ?>"
              data-movie-title="<?= htmlspecialchars($featured['title']) ?>"
              data-movie-poster="<?= htmlspecialchars($featured['poster_url']) ?>">
        <i class="ph-bold ph-users-three" style="font-size: 1.3rem;"></i> Watch Together
      </button>

      <!-- More Info Button -->
      <button class="btn btn-secondary btn-lg" onclick="openMovieDetail(<?= $featured['id'] ?>)">
        <i class="ph-bold ph-info" style="font-size: 1.3rem;"></i> More Info
      </button>

      <!-- Add to My List -->
      <button class="btn btn-icon btn-outline btn-toggle-list" data-movie-id="<?= $featured['id'] ?>" title="Save to My List">
        <i class="ph-bold ph-plus"></i>
      </button>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- Content Rows Container -->
<main class="content-container">
  <!-- My List Row (if user has added movies) -->
  <?php if (!empty($myList)): ?>
  <div id="mylist">
    <?php 
      $title = 'My List'; 
      $movies = $myList;
      include dirname(__DIR__) . '/views/partials/row.php'; 
    ?>
  </div>
  <?php endif; ?>

  <!-- Trending Now -->
  <div id="trending">
    <?php 
      $title = 'Trending Now'; 
      $movies = $trending;
      include dirname(__DIR__) . '/views/partials/row.php'; 
    ?>
  </div>

  <!-- Popular Movies -->
  <div id="popular">
    <?php 
      $title = 'Popular on MyFlix'; 
      $movies = $popular;
      include dirname(__DIR__) . '/views/partials/row.php'; 
    ?>
  </div>

  <!-- Action & Sci-Fi -->
  <div id="action">
    <?php 
      $title = 'Action & Sci-Fi Blockbusters'; 
      $movies = $action;
      include dirname(__DIR__) . '/views/partials/row.php'; 
    ?>
  </div>

  <!-- Drama & Classics -->
  <div id="drama">
    <?php 
      $title = 'Critically Acclaimed Stories'; 
      $movies = $drama;
      include dirname(__DIR__) . '/views/partials/row.php'; 
    ?>
  </div>
</main>

<!-- Include Global Modals -->
<?php include dirname(__DIR__) . '/views/partials/modals.php'; ?>

<?php include dirname(__DIR__) . '/views/layout/footer.php'; ?>
