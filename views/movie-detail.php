<?php include dirname(__DIR__) . '/views/layout/header.php'; ?>

<!-- Top Navigation -->
<nav class="navbar scrolled">
  <div class="nav-left">
    <a href="/" class="brand-logo">
      MYFLIX <span class="brand-logo-party-badge">PARTY</span>
    </a>
  </div>
  <div class="nav-right">
    <a href="/" class="btn btn-outline btn-sm"><i class="ph-bold ph-arrow-left"></i> Back to Browse</a>
  </div>
</nav>

<div style="padding-top: var(--nav-height); min-height: 100vh;">
  <div style="position: relative; height: 60vh; min-height: 400px;">
    <img src="<?= htmlspecialchars($movie['backdrop_url']) ?>" alt="<?= htmlspecialchars($movie['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
    <div style="position: absolute; inset: 0; background: linear-gradient(180deg, rgba(20,20,20,0.2) 0%, var(--bg-main) 100%);"></div>
    
    <div style="position: absolute; bottom: 40px; left: 4%; right: 4%; max-width: 800px; display: flex; flex-direction: column; gap: 16px;">
      <h1 style="font-family: var(--font-logo); font-size: 3.8rem; line-height: 1;"><?= htmlspecialchars($movie['title']) ?></h1>
      
      <div class="flex items-center gap-md font-semibold text-sm">
        <span class="match-badge"><?= $movie['match_percentage'] ?>% Match</span>
        <span class="age-badge"><?= htmlspecialchars($movie['age_rating']) ?></span>
        <span><?= floor($movie['duration_seconds'] / 60) ?>m</span>
        <span class="hd-badge">4K ULTRA HD</span>
        <span class="text-secondary"><?= htmlspecialchars($movie['genre']) ?></span>
      </div>

      <div class="flex items-center gap-md" style="margin-top: 8px;">
        <button class="btn btn-primary btn-lg btn-watch-together"
                data-movie-id="<?= $movie['id'] ?>"
                data-movie-title="<?= htmlspecialchars($movie['title']) ?>"
                data-movie-poster="<?= htmlspecialchars($movie['poster_url']) ?>">
          <i class="ph-bold ph-users-three" style="font-size: 1.3rem;"></i> Watch Together
        </button>

        <button class="btn btn-outline btn-lg btn-toggle-list" data-movie-id="<?= $movie['id'] ?>">
          <i class="ph-bold <?= $inList ? 'ph-check' : 'ph-plus' ?>"></i> My List
        </button>
      </div>
    </div>
  </div>

  <div style="padding: 40px 4%; max-width: 1000px;">
    <p style="font-size: 1.15rem; color: #ddd; line-height: 1.6; margin-bottom: 24px;"><?= htmlspecialchars($movie['description']) ?></p>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; color: var(--text-secondary); font-size: 0.9rem; border-top: 1px solid var(--border-subtle); padding-top: 24px;">
      <div><strong style="color: #fff;">Director:</strong> <?= htmlspecialchars($movie['director'] ?? 'N/A') ?></div>
      <div><strong style="color: #fff;">Cast:</strong> <?= htmlspecialchars($movie['cast_members'] ?? 'N/A') ?></div>
      <div><strong style="color: #fff;">Release Year:</strong> <?= $movie['release_year'] ?></div>
    </div>
  </div>
</div>

<?php include dirname(__DIR__) . '/views/partials/modals.php'; ?>
<?php include dirname(__DIR__) . '/views/layout/footer.php'; ?>
