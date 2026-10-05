<?php
/**
 * "Live Parties Now" strip — rooms someone can walk into right now.
 *
 * @var array $liveRooms
 */
if (empty($liveRooms)) return;
?>
<div class="content-row">
  <div class="row-header">
    <div class="row-header-main">
      <h2 class="row-title">Live Parties Now</h2>
      <span class="live-rooms-refresh-note">updates automatically</span>
    </div>
  </div>
  <div class="slider-wrapper">
    <button class="slider-arrow left" aria-label="Scroll left"><i class="ph-bold ph-caret-left"></i></button>
    <div class="card-slider">
      <?php foreach ($liveRooms as $room): ?>
        <a class="live-room-card" href="/room/<?= htmlspecialchars($room['room_code']) ?>">
          <img src="<?= htmlspecialchars($room['movie_backdrop'] ?: $room['movie_poster']) ?>"
               alt="<?= htmlspecialchars($room['movie_title']) ?>" loading="lazy">
          <div class="live-room-top">
            <span class="live-badge"><span class="live-dot"></span>LIVE</span>
            <span class="live-count">
              <i class="ph-bold ph-users-three"></i><?= (int)$room['participant_count'] ?>/<?= (int)$room['max_participants'] ?>
            </span>
          </div>
          <div class="live-room-body">
            <div class="live-room-text">
              <div class="live-room-name"><?= htmlspecialchars($room['room_name']) ?></div>
              <div class="live-room-movie"><?= htmlspecialchars($room['movie_title']) ?></div>
            </div>
            <span class="live-room-join">
              <?= $room['playback_state'] === 'playing' ? 'Join now' : 'Join' ?>
              <i class="ph-bold ph-arrow-right"></i>
            </span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
    <button class="slider-arrow right" aria-label="Scroll right"><i class="ph-bold ph-caret-right"></i></button>
  </div>
</div>
