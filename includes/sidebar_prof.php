<?php
$menu = [
    ['dashboard',        'Tableau de bord',    'dashboard',       '/professeur/dashboard.php'],
    ['emplois_du_temps', 'Mon emploi du temps','calendar_month',  '/professeur/emplois_du_temps.php'],
    ['valider',          'Valider créneaux',   'fact_check',      '/professeur/valider.php'],
    ['disponibilites',   'Mes disponibilités', 'event_available', '/professeur/disponibilites.php'],
    ['messages',         'Messagerie',         'send',            '/professeur/messages.php'],
    ['notifications',    'Notifications',      'notifications',   '/professeur/notifications.php'],
];
$nbNotif = countNotificationsNonLues($currentUser['id']);
// Compter messages envoyés (côté prof — conversations actives)
$stmtMsg = getDB()->prepare("SELECT COUNT(DISTINCT id_classe) FROM messages WHERE id_expediteur=?");
$stmtMsg->execute([$currentUser['id']]);
$nbMsgClasses = (int)$stmtMsg->fetchColumn();
?>
<nav class="sidebar" id="sidebar" role="navigation" aria-label="Navigation professeur">
  <div class="sidebar-section">
    <div class="sidebar-section-label">Espace Professeur</div>
    <ul class="sidebar-nav">
      <?php foreach ($menu as [$key, $label, $icon, $path]): ?>
      <li>
        <a class="sidebar-link <?= $activeMenu === $key ? 'active' : '' ?>"
           href="<?= APP_URL . $path ?>">
          <span class="material-icons-round"><?= $icon ?></span>
          <?= $label ?>
          <?php if ($key === 'notifications' && $nbNotif > 0): ?>
            <span class="sidebar-badge"><?= $nbNotif ?></span>
          <?php endif; ?>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</nav>
