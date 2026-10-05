<?php
/**
 * @var array $movie
 */
$durationMins = floor(($movie['duration_seconds'] ?? 600) / 60);
?>
<div class="movie-card" data-movie-id="<?= $movie['id'] ?>" onclick="openMovieDetail(<?= $movie['id'] ?>)">
  <img src="<?= htmlspecialchars($movie['backdrop_url'] ?: $movie['poster_url']) ?>" alt="<?= htmlspecialchars($movie['title']) ?>" class="movie-card-img" loading="lazy">
  
  <div class="card-overlay">
    <div class="card-title"><?= htmlspecialchars($movie['title']) ?></div>
    <div class="card-meta-row">
      <span class="match-badge"><?= $movie['match_percentage'] ?? 98 ?>% Match</span>
      <span class="age-badge"><?= htmlspecialchars($movie['age_rating'] ?? 'PG-13') ?></span>
      <span><?= $durationMins ?>m</span>
      <span class="hd-badge">HD</span>
    </div>
    <div class="card-actions">
      <button class="card-btn party btn-watch-together" 
              title="Watch Together with Friends"
              data-movie-id="<?= $movie['id'] ?>"
              data-movie-title="<?= htmlspecialchars($movie['title']) ?>"
              data-movie-poster="<?= htmlspecialchars($movie['poster_url']) ?>">
        <i class="ph-bold ph-users-three"></i>
      </button>

      <button class="card-btn btn-toggle-list" 
              title="Add to My List"
              data-movie-id="<?= $movie['id'] ?>">
        <i class="ph-bold ph-plus"></i>
      </button>

      <button class="card-btn" 
              title="More Info"
              onclick="event.stopPropagation(); openMovieDetail(<?= $movie['id'] ?>)">
        <i class="ph-bold ph-caret-down"></i>
      </button>
    </div>
  </div>
</div>
