<?php
// professeur/notifications.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireProfesseur();
$currentUser = getCurrentUser();
$pdo = getDB();

if (isset($_GET['mark_all'])) {
    $pdo->prepare('UPDATE notifications SET est_lu=1 WHERE id_utilisateur=?')->execute([$currentUser['id']]);
    redirect(APP_URL . '/professeur/notifications.php');
}

$stmt = $pdo->prepare('SELECT * FROM notifications WHERE id_utilisateur=? ORDER BY date_envoi DESC LIMIT 50');
$stmt->execute([$currentUser['id']]);
$notifs  = $stmt->fetchAll();
$nbUnread = countNotificationsNonLues($currentUser['id']);

$pageTitle = 'Notifications'; $activeMenu = 'notifications';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_prof.php';
?>
<div class="main-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1 class="page-title"><span class="material-icons-round">notifications</span> Notifications</h1>
      <p class="page-subtitle"><?= $nbUnread ?> non lue<?= $nbUnread>1?'s':'' ?></p>
    </div>
    <?php if ($nbUnread > 0): ?>
    <a href="?mark_all=1" class="btn btn-outline"><span class="material-icons-round">done_all</span> Tout marquer lu</a>
    <?php endif; ?>
  </div>
  <div class="card">
    <?php if (empty($notifs)): ?>
    <div class="card-body"><div class="empty-state">
      <div class="empty-state-icon"><span class="material-icons-round">notifications_none</span></div>
      <h3>Aucune notification</h3>
    </div></div>
    <?php else: ?>
    <div class="notif-list" style="padding:.5rem">
      <?php foreach ($notifs as $n):
        $icon = $n['type']==='success'?'check_circle':($n['type']==='warning'?'warning':'info');
      ?>
      <div class="notif-item<?= $n['est_lu']?'':' unread' ?>" style="border-radius:var(--radius-sm)">
        <div class="notif-icon <?= h($n['type']) ?>"><span class="material-icons-round"><?= $icon ?></span></div>
        <div class="notif-body" style="overflow:visible;white-space:normal">
          <div class="notif-title"><?= h($n['titre']) ?></div>
          <div class="notif-msg" style="white-space:normal;overflow:visible"><?= h($n['message']) ?></div>
          <div class="notif-date"><?= date('d/m/Y à H:i', strtotime($n['date_envoi'])) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body></html>
