<?php
// includes/sidebar_admin.php — v3.1
$menu = [
    // ── Tableau de bord ──────────────────────────────────
    ['dashboard',        'Tableau de bord',    'dashboard',        '/admin/dashboard.php'],
    // ── Scolarité ────────────────────────────────────────
    ['niveaux',          'Niveaux scolaires',  'school',           '/admin/niveaux.php'],
    ['classes',          'Classes',            'class',            '/admin/classes.php'],
    ['eleves',           'Élèves',             'groups',           '/admin/eleves.php'],
    ['matieres',         'Matières',           'menu_book',        '/admin/matieres.php'],
    ['professeurs',      'Professeurs',        'person',           '/admin/professeurs.php'],
    ['salles',           'Salles',             'meeting_room',     '/admin/salles.php'],
    ['horaires',         'Horaires',           'schedule',         '/admin/horaires.php'],
    // ── EDT ──────────────────────────────────────────────
    ['volume_horaire',   'Matières & Horaires','tune',             '/admin/volume_horaire.php'],
    ['generer',          'Générer EDT',        'auto_fix_high',    '/admin/generer.php'],
    ['emplois_du_temps', 'Emplois du temps',   'calendar_month',   '/admin/emplois_du_temps.php'],
    ['suivi_validations','Suivi validations',  'fact_check',       '/admin/suivi_validations.php'],
    ['modifier_edt',     'Modifier EDT',       'edit_calendar',    '/admin/modifier_edt.php'],
    // ── Système ──────────────────────────────────────────
    ['notifications',    'Notifications',      'notifications',    '/admin/notifications.php'],
];

$nbPending    = countPendingValidations();
$nbNotif      = countNotificationsNonLues($currentUser['id']);
$nbSansClasse = (int)getDB()->query("SELECT COUNT(*) FROM utilisateurs WHERE role='eleve' AND id_classe IS NULL AND statut='actif'")->fetchColumn();

$sections = [
    'Général'       => ['dashboard'],
    'Scolarité'     => ['niveaux','classes','eleves','matieres','professeurs','salles','horaires'],
    'Emplois du temps' => ['volume_horaire','generer','emplois_du_temps','suivi_validations','modifier_edt'],
    'Système'       => ['notifications'],
];
?>
<nav class="sidebar" id="sidebar" role="navigation" aria-label="Navigation admin">
  <?php foreach ($sections as $sectionLabel => $keys): ?>
  <div class="sidebar-section">
    <div class="sidebar-section-label"><?= $sectionLabel ?></div>
    <ul class="sidebar-nav">
      <?php foreach ($menu as [$key, $label, $icon, $path]):
        if (!in_array($key, $keys)) continue; ?>
      <li>
        <a class="sidebar-link <?= $activeMenu === $key ? 'active' : '' ?>"
           href="<?= APP_URL . $path ?>">
          <span class="material-icons-round"><?= $icon ?></span>
          <?= $label ?>
          <?php if ($key === 'suivi_validations' && $nbPending > 0): ?>
            <span class="sidebar-badge"><?= $nbPending ?></span>
          <?php elseif ($key === 'notifications' && $nbNotif > 0): ?>
            <span class="sidebar-badge"><?= $nbNotif ?></span>
          <?php elseif ($key === 'eleves' && $nbSansClasse > 0): ?>
            <span class="sidebar-badge" style="background:var(--warning)"><?= $nbSansClasse ?></span>
          <?php endif; ?>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endforeach; ?>
</nav>
