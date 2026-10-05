<?php
/**
 * @var string $title
 * @var array $movies
 */
if (empty($movies)) return;
?>
<div class="content-row">
  <div class="row-header">
    <h2 class="row-title"><?= htmlspecialchars($title) ?></h2>
  </div>
  <div class="slider-wrapper">
    <button class="slider-arrow left" aria-label="Scroll left"><i class="ph-bold ph-caret-left"></i></button>
    <div class="card-slider">
      <?php foreach ($movies as $movie): ?>
        <?php include dirname(__DIR__) . '/partials/movie-card.php'; ?>
      <?php endforeach; ?>
    </div>
    <button class="slider-arrow right" aria-label="Scroll right"><i class="ph-bold ph-caret-right"></i></button>
  </div>
</div>
