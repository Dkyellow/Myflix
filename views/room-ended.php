<?php include dirname(__DIR__) . '/views/layout/header.php'; ?>

<div style="min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 24px;">
  <a href="/" class="brand-logo" style="margin-bottom: 30px;">
    MYFLIX <span class="brand-logo-party-badge">PARTY</span>
  </a>

  <div style="width: 80px; height: 80px; border-radius: 50%; background: var(--bg-surface); border: 1px solid var(--border-subtle); display: flex; align-items: center; justify-content: center; font-size: 2.2rem; color: var(--brand-red); margin-bottom: 20px;">
    <i class="ph-bold ph-film-slate"></i>
  </div>

  <h1 style="font-size: 2rem; font-weight: 700;">Watch Party Has Ended</h1>
  <p style="color: var(--text-secondary); max-width: 480px; margin: 12px 0 28px;">
    The host has concluded this watch party for <strong><?= htmlspecialchars($room['movie_title'] ?? 'this movie') ?></strong>.
  </p>

  <div class="flex items-center gap-md">
    <a href="/" class="btn btn-primary btn-lg">
      <i class="ph-bold ph-house"></i> Browse Other Movies
    </a>
  </div>
</div>

<?php include dirname(__DIR__) . '/views/layout/footer.php'; ?>
