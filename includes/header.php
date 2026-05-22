<?php
// ============================================================
// includes/header.php — Head HTML commun + Topbar
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$currentUser = getCurrentUser();
$notifCount  = $currentUser ? countNotificationsNonLues($currentUser['id']) : 0;
$pageTitle   = $pageTitle  ?? APP_NAME;
$activeMenu  = $activeMenu ?? '';
$initials    = $currentUser ? getUserInitials($currentUser) : '?';
$userName    = $currentUser ? ($currentUser['prenom'] . ' ' . $currentUser['nom']) : '';

// URL notifications selon rôle
$notifUrl = match($currentUser['role'] ?? '') {
    'admin'      => APP_URL . '/admin/notifications.php',
    'professeur' => APP_URL . '/professeur/notifications.php',
    default      => '#',
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title><?= h($pageTitle) ?> — <?= APP_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/main.css">
  <?php if (!empty($extraCss)): foreach ($extraCss as $css): ?>
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/<?= h($css) ?>">
  <?php endforeach; endif; ?>
</head>
<body>

<!-- Page Loader -->
<div id="pageLoader">
  <div class="loader-logo">
    <div class="loader-logo-icon"><span class="material-icons-round">school</span></div>
    <span class="loader-logo-text"><?= APP_NAME ?></span>
  </div>
  <div class="loader-bar-track"><div class="loader-bar-fill"></div></div>
  <p class="loader-tagline">Plateforme académique professionnelle</p>
</div>

<!-- Spinner overlay -->
<div class="spinner-overlay" id="spinnerOverlay">
  <div class="spinner"></div>
  <p class="spinner-text" id="spinnerText">Traitement en cours...</p>
</div>

<!-- Toast container -->
<div id="toast-container"></div>

<!-- TOPBAR -->
<header class="topbar">
  <button class="hamburger" id="hamburgerBtn" aria-label="Menu">
    <span class="material-icons-round">menu</span>
  </button>

  <div class="topbar-brand">
    <div class="topbar-brand-icon"><span class="material-icons-round">school</span></div>
    <?= APP_NAME ?>
  </div>

  <div class="topbar-divider"></div>
  <span class="topbar-page-title"><?= h($pageTitle) ?></span>

  <div class="topbar-actions">
    <!-- Notifications -->
    <a href="<?= $notifUrl ?>" class="topbar-icon-btn" title="Notifications">
      <span class="material-icons-round">notifications</span>
      <?php if ($notifCount > 0): ?>
        <span class="notif-badge-count"><?= $notifCount > 9 ? '9+' : $notifCount ?></span>
      <?php endif; ?>
    </a>

    <!-- User chip -->
    <div class="user-chip" onclick="window.location='<?= APP_URL ?>/logout.php'">
      <div class="user-avatar"><?= h($initials) ?></div>
      <span class="user-name"><?= h($userName) ?></span>
      <span class="material-icons-round" style="font-size:16px;color:var(--text-muted)">logout</span>
    </div>
  </div>
</header>

<!-- Sidebar overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>
