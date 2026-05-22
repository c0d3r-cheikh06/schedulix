<?php
// admin/dashboard.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();
$currentUser = getCurrentUser();
$pdo = getDB();

$nbClasses   = (int)$pdo->query('SELECT COUNT(*) FROM classes')->fetchColumn();
$nbProfs     = (int)$pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role='professeur' AND statut='actif'")->fetchColumn();
$nbMatieres  = (int)$pdo->query('SELECT COUNT(*) FROM matieres')->fetchColumn();
$nbSalles    = (int)$pdo->query('SELECT COUNT(*) FROM salles')->fetchColumn();
$version     = getCurrentVersion();
$stmtNbEdt   = $pdo->prepare('SELECT COUNT(*) FROM emplois_du_temps WHERE version=?');
$stmtNbEdt->execute([$version]);
$nbEdt       = (int)$stmtNbEdt->fetchColumn();
$nbEnAttente = countPendingValidations();
$nbNotifs    = countNotificationsNonLues($currentUser['id']);

$stmtNotifs = $pdo->prepare("SELECT * FROM notifications WHERE id_utilisateur=? ORDER BY date_envoi DESC LIMIT 6");
$stmtNotifs->execute([$currentUser['id']]);
$recentNotifs = $stmtNotifs->fetchAll();

$classes  = getClasses();
$joursEdt = getJoursSemaine();
$semaine  = [];
foreach ($joursEdt as $j) $semaine[$j] = [];

if ($version > 0) {
    $stmtSem = $pdo->prepare("
        SELECT e.*, m.nom AS matiere_nom, m.couleur_hex, cl.nom AS classe_nom, c.jour, c.heure_debut
        FROM emplois_du_temps e
        JOIN matieres m ON m.id=e.id_matiere
        JOIN classes cl ON cl.id=e.id_classe
        JOIN creneaux c ON c.id=e.id_creneau
        WHERE e.version=?
        ORDER BY c.heure_debut
    ");
    $stmtSem->execute([$version]);
    foreach ($stmtSem->fetchAll() as $row) {
        $semaine[$row['jour']][] = $row;
    }
}

$pageTitle = 'Tableau de bord'; $activeMenu = 'dashboard';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_admin.php';
?>

<div class="main-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1 class="page-title">
        <span class="material-icons-round">dashboard</span>
        Tableau de bord
      </h1>
      <p class="page-subtitle">Vue d'ensemble de la plateforme — <?= date('l d F Y') ?></p>
    </div>
    <a href="<?= APP_URL ?>/admin/generer.php" class="btn btn-primary">
      <span class="material-icons-round">auto_fix_high</span>
      Générer EDT
    </a>
  </div>

  <!-- Stats -->
  <div class="stat-grid">
    <div class="stat-card">
      <div class="stat-icon blue"><span class="material-icons-round">class</span></div>
      <div class="stat-info">
        <div class="stat-value"><?= $nbClasses ?></div>
        <div class="stat-label">Classes</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon green"><span class="material-icons-round">person</span></div>
      <div class="stat-info">
        <div class="stat-value"><?= $nbProfs ?></div>
        <div class="stat-label">Professeurs actifs</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon teal"><span class="material-icons-round">menu_book</span></div>
      <div class="stat-info">
        <div class="stat-value"><?= $nbMatieres ?></div>
        <div class="stat-label">Matières</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon purple"><span class="material-icons-round">meeting_room</span></div>
      <div class="stat-info">
        <div class="stat-value"><?= $nbSalles ?></div>
        <div class="stat-label">Salles</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon indigo"><span class="material-icons-round">calendar_month</span></div>
      <div class="stat-info">
        <div class="stat-value"><?= $nbEdt ?></div>
        <div class="stat-label">Créneaux (v<?= $version ?>)</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon orange"><span class="material-icons-round">pending_actions</span></div>
      <div class="stat-info">
        <div class="stat-value"><?= $nbEnAttente ?></div>
        <div class="stat-label">En attente validation</div>
      </div>
    </div>
  </div>

  <!-- Grid -->
  <div style="display:grid;grid-template-columns:1fr 340px;gap:1.5rem" class="dashboard-lower">

    <!-- Semaine -->
    <div class="card">
      <div class="card-header">
        <div class="card-header-title">
          <span class="material-icons-round">calendar_view_week</span>
          Résumé de la semaine
        </div>
        <?php if ($version > 0): ?>
          <span class="badge badge-primary">Version <?= $version ?></span>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <?php if ($version === 0): ?>
          <div class="empty-state">
            <div class="empty-state-icon"><span class="material-icons-round">calendar_month</span></div>
            <h3>Aucun emploi du temps</h3>
            <p>Aucune version n'a encore été générée. Commencez par générer un emploi du temps.</p>
            <a href="<?= APP_URL ?>/admin/generer.php" class="btn btn-primary" style="margin-top:1rem">
              <span class="material-icons-round">auto_fix_high</span> Générer maintenant
            </a>
          </div>
        <?php else: ?>
          <div class="week-summary">
            <?php foreach ($joursEdt as $jour): ?>
            <div class="day-col">
              <div class="day-col-header"><?= mb_substr($jour, 0, 3) ?></div>
              <div class="day-col-body">
                <?php if (empty($semaine[$jour])): ?>
                  <span style="font-size:.72rem;color:var(--text-light);font-style:italic;margin:auto">Libre</span>
                <?php else: foreach ($semaine[$jour] as $c): ?>
                  <div class="mini-course" style="background:<?= h($c['couleur_hex']) ?>">
                    <strong><?= h($c['matiere_nom']) ?></strong>
                    <span><?= h($c['classe_nom']) ?></span>
                  </div>
                <?php endforeach; endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Notifications -->
    <div class="card">
      <div class="card-header">
        <div class="card-header-title">
          <span class="material-icons-round">notifications</span>
          Notifications récentes
        </div>
        <a href="<?= APP_URL ?>/admin/notifications.php" class="btn btn-sm btn-outline">Tout voir</a>
      </div>
      <div class="card-body" style="padding:.5rem">
        <?php if (empty($recentNotifs)): ?>
          <div class="empty-state" style="padding:2rem 1rem">
            <div class="empty-state-icon"><span class="material-icons-round">notifications_none</span></div>
            <h3>Aucune notification</h3>
          </div>
        <?php else: ?>
          <div class="notif-list">
            <?php foreach ($recentNotifs as $n): ?>
            <div class="notif-item<?= $n['est_lu'] ? '' : ' unread' ?>">
              <div class="notif-icon <?= h($n['type']) ?>">
                <span class="material-icons-round"><?= $n['type']==='success'?'check_circle':($n['type']==='warning'?'warning':'info') ?></span>
              </div>
              <div class="notif-body">
                <div class="notif-title"><?= h($n['titre']) ?></div>
                <div class="notif-msg"><?= h($n['message']) ?></div>
                <div class="notif-date"><?= date('d/m H:i', strtotime($n['date_envoi'])) ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<style>
@media(max-width:900px){.dashboard-lower{grid-template-columns:1fr!important}}
</style>
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body></html>
