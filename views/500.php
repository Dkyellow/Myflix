<?php include dirname(__DIR__) . '/views/layout/header.php'; ?>

<div style="min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 24px;">
  <a href="/" class="brand-logo" style="margin-bottom: 30px;">
    MYFLIX <span class="brand-logo-party-badge">PARTY</span>
  </a>

  <h1 style="font-family: var(--font-logo); font-size: 5rem; color: var(--brand-red); line-height: 1;">500</h1>
  <h2 style="font-size: 1.8rem; font-weight: 700; margin-top: 10px;">Technical Difficulties</h2>
  <p style="color: var(--text-secondary); max-width: 500px; margin: 12px 0 28px;">
    <?= htmlspecialchars($message ?? 'An unexpected error occurred. Please try again.') ?>
  </p>

  <?php if (!empty($trace)): ?>
    <pre style="text-align: left; background: #000; padding: 16px; border-radius: 6px; font-size: 0.75rem; color: #ff6b6b; max-width: 800px; overflow-x: auto; margin-bottom: 24px;"><?= htmlspecialchars($trace) ?></pre>
  <?php endif; ?>

  <a href="/" class="btn btn-primary btn-lg">
    <i class="ph-bold ph-house"></i> Return to Home
  </a>
</div>

<?php include dirname(__DIR__) . '/views/layout/footer.php'; ?>
