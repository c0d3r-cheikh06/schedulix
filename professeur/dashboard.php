<?php
// professeur/dashboard.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireProfesseur();
$currentUser = getCurrentUser();
$pdo = getDB();

$version = getCurrentVersion();

$stmtCours = $pdo->prepare("
    SELECT e.*, m.nom AS mat_nom, m.couleur_hex,
           cl.nom AS classe_nom, s.nom AS salle_nom,
           c.jour, c.heure_debut, c.heure_fin, e.statut
    FROM emplois_du_temps e
    JOIN matieres m  ON m.id=e.id_matiere
    JOIN classes cl  ON cl.id=e.id_classe
    JOIN salles s    ON s.id=e.id_salle
    JOIN creneaux c  ON c.id=e.id_creneau
    WHERE e.version=? AND e.id_professeur=?
    ORDER BY FIELD(c.jour,'Lundi','Mardi','Mercredi','Jeudi','Vendredi'), c.heure_debut
");
$stmtCours->execute([$version, $currentUser['id']]);
$cours = $stmtCours->fetchAll();

$nbTotal     = count($cours);
$nbValide    = count(array_filter($cours, fn($c)=>$c['statut']==='valide'));
$nbEnAttente = count(array_filter($cours, fn($c)=>$c['statut']==='provisoire'));
$nbRejete    = count(array_filter($cours, fn($c)=>$c['statut']==='rejete'));
$notifCount  = countNotificationsNonLues($currentUser['id']);

$jours = getJoursSemaine();
$semaine = array_fill_keys($jours, []);
foreach ($cours as $c) { $semaine[$c['jour']][] = $c; }

$pageTitle = 'Tableau de bord'; $activeMenu = 'dashboard';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_prof.php';
?>

<div class="main-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1 class="page-title"><span class="material-icons-round">dashboard</span> Tableau de bord</h1>
      <p class="page-subtitle">Bonjour, <?= h($currentUser['prenom']) ?> — <?= date('l d F Y') ?></p>
    </div>
    <?php if ($nbEnAttente > 0): ?>
    <a href="<?= APP_URL ?>/professeur/valider.php" class="btn btn-primary">
      <span class="material-icons-round">fact_check</span>
      Valider mes créneaux (<?= $nbEnAttente ?>)
    </a>
    <?php endif; ?>
  </div>

  <?php if ($nbEnAttente > 0): ?>
  <div class="alert alert-info" data-auto-dismiss="10000">
    <span class="material-icons-round">info</span>
    <div class="alert-content">
      <strong><?= $nbEnAttente ?> créneau<?= $nbEnAttente>1?'x':'' ?> en attente de votre validation.</strong>
      Merci de consulter et valider votre emploi du temps le plus tôt possible.
    </div>
  </div>
  <?php endif; ?>

  <div class="stat-grid">
    <div class="stat-card">
      <div class="stat-icon blue"><span class="material-icons-round">calendar_month</span></div>
      <div><div class="stat-value"><?= $nbTotal ?></div><div class="stat-label">Cours / semaine</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon green"><span class="material-icons-round">check_circle</span></div>
      <div><div class="stat-value"><?= $nbValide ?></div><div class="stat-label">Validés</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon orange"><span class="material-icons-round">pending</span></div>
      <div><div class="stat-value"><?= $nbEnAttente ?></div><div class="stat-label">En attente</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon red"><span class="material-icons-round">cancel</span></div>
      <div><div class="stat-value"><?= $nbRejete ?></div><div class="stat-label">Rejetés</div></div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <div class="card-header-title"><span class="material-icons-round">calendar_view_week</span> Mon emploi du temps — Version <?= $version ?></div>
      <a href="<?= APP_URL ?>/professeur/emplois_du_temps.php" class="btn btn-sm btn-outline">Vue détaillée</a>
    </div>
    <div class="card-body">
      <?php if (empty($cours)): ?>
        <div class="empty-state">
          <div class="empty-state-icon"><span class="material-icons-round">calendar_today</span></div>
          <h3>Aucun cours planifié</h3>
          <p>Aucun créneau ne vous est assigné pour la version actuelle.</p>
        </div>
      <?php else: ?>
        <div class="week-summary">
          <?php foreach ($jours as $jour): ?>
          <div class="day-col">
            <div class="day-col-header"><?= mb_substr($jour,0,3) ?></div>
            <div class="day-col-body">
              <?php if (empty($semaine[$jour])): ?>
                <span style="font-size:.72rem;color:var(--text-light);font-style:italic;margin:auto">Libre</span>
              <?php else: foreach ($semaine[$jour] as $c): ?>
                <div class="mini-course" style="background:<?= h($c['couleur_hex']) ?>">
                  <strong><?= h($c['mat_nom']) ?></strong>
                  <span><?= h($c['classe_nom']) ?></span>
                  <span style="font-size:.68rem"><?= formatHeure($c['heure_debut']) ?></span>
                </div>
              <?php endforeach; endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body></html>
