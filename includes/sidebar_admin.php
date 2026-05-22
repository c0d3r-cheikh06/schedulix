<?php
// includes/sidebar_admin.php — v3
$menu = [
    ['dashboard',        'Tableau de bord',    'dashboard',        '/admin/dashboard.php'],
    ['niveaux',          'Niveaux scolaires',  'school',           '/admin/niveaux.php'],
    ['classes',          'Classes',            'class',            '/admin/classes.php'],
    ['matieres',         'Matières',           'menu_book',        '/admin/matieres.php'],
    ['professeurs',      'Professeurs',        'person',           '/admin/professeurs.php'],
    ['salles',           'Salles',             'meeting_room',     '/admin/salles.php'],
    ['horaires',         'Horaires',           'schedule',         '/admin/horaires.php'],
    ['generer',          'Générer EDT',        'auto_fix_high',    '/admin/generer.php'],
    ['emplois_du_temps', 'Emplois du temps',   'calendar_month',   '/admin/emplois_du_temps.php'],
    ['suivi_validations','Suivi validations',  'fact_check',       '/admin/suivi_validations.php'],
    ['modifier_edt',     'Modifier EDT',       'edit_calendar',    '/admin/modifier_edt.php'],
    ['notifications',    'Notifications',      'notifications',    '/admin/notifications.php'],
];
$nbPending = countPendingValidations();
$nbNotif   = countNotificationsNonLues($currentUser['id']);
?>
<nav class="sidebar" id="sidebar" role="navigation" aria-label="Navigation admin">
  <div class="sidebar-section">
    <div class="sidebar-section-label">Administration</div>
    <ul class="sidebar-nav">
      <?php foreach ($menu as [$key, $label, $icon, $path]): ?>
      <li>
        <a class="sidebar-link <?= $activeMenu === $key ? 'active' : '' ?>"
           href="<?= APP_URL . $path ?>">
          <span class="material-icons-round"><?= $icon ?></span>
          <?= $label ?>
          <?php if ($key === 'suivi_validations' && $nbPending > 0): ?>
            <span class="sidebar-badge"><?= $nbPending ?></span>
          <?php elseif ($key === 'notifications' && $nbNotif > 0): ?>
            <span class="sidebar-badge"><?= $nbNotif ?></span>
          <?php endif; ?>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</nav>
