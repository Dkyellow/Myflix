<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title><?= htmlspecialchars($title ?? 'MyFlix — Watch Movies Together') ?></title>
  <meta name="description" content="Watch movies together in synchronized playback with live video and chat. The ultimate social watch party platform.">
  <meta name="theme-color" content="#141414">

  <!-- Phosphor Icons -->
  <script src="https://unpkg.com/@phosphor-icons/web"></script>

  <!-- LiveKit Client SDK (official CDN) -->
  <script src="https://cdn.jsdelivr.net/npm/livekit-client/dist/livekit-client.umd.min.js"></script>

  <!-- CSS Stylesheets -->
  <link rel="stylesheet" href="/assets/css/design-system.css">
  <link rel="stylesheet" href="/assets/css/components.css">
  <link rel="stylesheet" href="/assets/css/room.css">
</head>
<body>
