<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();
$currentUser = getCurrentUser();
$pdo = getDB();

// Marquer tout comme lu
if (isset($_GET['mark_all'])) {
    $pdo->prepare('UPDATE notifications SET est_lu=1 WHERE id_utilisateur=?')->execute([$currentUser['id']]);
    redirect(APP_URL . '/admin/notifications.php');
}
// Marquer une notif
if (isset($_GET['mark']) && is_numeric($_GET['mark'])) {
    $pdo->prepare('UPDATE notifications SET est_lu=1 WHERE id=? AND id_utilisateur=?')->execute([(int)$_GET['mark'], $currentUser['id']]);
}

$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;
$total = (int)$pdo->prepare('SELECT COUNT(*) FROM notifications WHERE id_utilisateur=?')->execute([$currentUser['id']]) ? 0 : 0;
$stmtCount = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE id_utilisateur=?');
$stmtCount->execute([$currentUser['id']]);
$total = (int)$stmtCount->fetchColumn();
$pages = ceil($total / $limit);

$stmt = $pdo->prepare('SELECT * FROM notifications WHERE id_utilisateur=? ORDER BY date_envoi DESC LIMIT ? OFFSET ?');
$stmt->execute([$currentUser['id'], $limit, $offset]);
$notifs = $stmt->fetchAll();

$nbUnread = countNotificationsNonLues($currentUser['id']);

$pageTitle = 'Notifications'; $activeMenu = 'notifications';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_admin.php';
?>
<div class="main-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1 class="page-title"><span class="material-icons-round">notifications</span> Notifications</h1>
      <p class="page-subtitle"><?= $total ?> notification<?= $total>1?'s':'' ?> — <?= $nbUnread ?> non lue<?= $nbUnread>1?'s':'' ?></p>
    </div>
    <?php if ($nbUnread > 0): ?>
    <a href="?mark_all=1" class="btn btn-outline">
      <span class="material-icons-round">done_all</span> Tout marquer comme lu
    </a>
    <?php endif; ?>
  </div>

  <div class="card">
    <?php if (empty($notifs)): ?>
    <div class="card-body">
      <div class="empty-state">
        <div class="empty-state-icon"><span class="material-icons-round">notifications_none</span></div>
        <h3>Aucune notification</h3>
        <p>Vous n'avez reçu aucune notification pour le moment.</p>
      </div>
    </div>
    <?php else: ?>
    <div class="notif-list" style="padding:.5rem">
      <?php foreach ($notifs as $n):
        $icon = $n['type']==='success'?'check_circle':($n['type']==='warning'?'warning':'info');
      ?>
      <div class="notif-item<?= $n['est_lu']?'':' unread' ?>"
           onclick="markRead(<?= $n['id'] ?>)"
           style="cursor:pointer;border-radius:var(--radius-sm);padding:.85rem">
        <div class="notif-icon <?= h($n['type']) ?>">
          <span class="material-icons-round"><?= $icon ?></span>
        </div>
        <div class="notif-body" style="overflow:visible;white-space:normal">
          <div class="notif-title"><?= h($n['titre']) ?></div>
          <div class="notif-msg" style="white-space:normal;overflow:visible"><?= h($n['message']) ?></div>
          <div class="notif-date"><?= date('d/m/Y à H:i', strtotime($n['date_envoi'])) ?></div>
        </div>
        <?php if (!$n['est_lu']): ?>
        <div style="width:8px;height:8px;background:var(--primary);border-radius:50%;flex-shrink:0;margin-top:.3rem"></div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <div class="card-footer" style="display:flex;gap:.5rem;justify-content:center">
      <?php for ($i=1;$i<=$pages;$i++): ?>
      <a href="?page=<?= $i ?>" class="btn btn-sm <?= $i===$page?'btn-primary':'btn-outline' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<script src="<?= APP_URL ?>/assets/js/main.js"></script>
<script>
function markRead(id) {
  fetch('?mark='+id);
  const item = event.currentTarget;
  item.classList.remove('unread');
  const dot = item.querySelector('[style*="border-radius:50%"]');
  if(dot) dot.remove();
}
</script>
</body></html>
