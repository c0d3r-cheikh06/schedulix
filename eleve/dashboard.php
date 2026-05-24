<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireEleve();
$currentUser = getCurrentUser();
$pdo = getDB();

$version  = getCurrentVersion();
$idClasse = $currentUser['id_classe'] ?? 0;

$cours = [];
if ($idClasse) {
    $stmt = $pdo->prepare("
        SELECT e.*, m.nom AS mat_nom, m.couleur_hex,
               s.nom AS salle_nom, u.nom AS prof_nom, u.prenom AS prof_prenom,
               c.jour, c.heure_debut, c.heure_fin
        FROM emplois_du_temps e
        JOIN matieres m     ON m.id=e.id_matiere
        JOIN salles s       ON s.id=e.id_salle
        JOIN utilisateurs u ON u.id=e.id_professeur
        JOIN creneaux c     ON c.id=e.id_creneau
        WHERE e.version=? AND e.id_classe=? AND e.statut IN ('valide','confirme')
        ORDER BY FIELD(c.jour,'Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'), c.heure_debut
    ");
    $stmt->execute([$version, $idClasse]);
    $cours = $stmt->fetchAll();
}

$jours   = getJoursSemaine();
$semaine = array_fill_keys($jours,[]);
foreach ($cours as $c) $semaine[$c['jour']][] = $c;

// Derniers messages
$recentMessages = [];
if ($idClasse) {
    $s = $pdo->prepare("
        SELECT m.*, u.nom AS exp_nom, u.prenom AS exp_prenom,
               (SELECT COUNT(*) FROM message_lu ml WHERE ml.id_message=m.id AND ml.id_eleve=?) AS est_lu
        FROM messages m JOIN utilisateurs u ON u.id=m.id_expediteur
        WHERE m.id_classe=? ORDER BY m.date_envoi DESC LIMIT 4
    ");
    $s->execute([$currentUser['id'], $idClasse]);
    $recentMessages = $s->fetchAll();
}
$nbMsgNonLus = countMessagesNonLus($currentUser['id']);

$pageTitle = 'Tableau de bord'; $activeMenu = 'dashboard';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_eleve.php';
?>
<div class="main-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1 class="page-title"><span class="material-icons-round">dashboard</span> Tableau de bord</h1>
      <p class="page-subtitle">Bonjour, <?= h($currentUser['prenom']) ?> — <?= date('l d F Y') ?></p>
    </div>
    <?php if ($version > 0): ?>
    <span class="badge badge-primary" style="font-size:.875rem;padding:.4rem .85rem">Version <?= $version ?></span>
    <?php endif; ?>
  </div>

  <?php if ($nbMsgNonLus > 0): ?>
  <div class="alert alert-info">
    <span class="material-icons-round">inbox</span>
    <div class="alert-content">
      <strong><?= $nbMsgNonLus ?> nouveau<?= $nbMsgNonLus>1?'x':'' ?> message<?= $nbMsgNonLus>1?'s':'' ?>.</strong>
      <a href="<?= APP_URL ?>/eleve/messages.php" style="margin-left:.5rem">Consulter →</a>
    </div>
  </div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 300px;gap:1.5rem;align-items:start" class="db-grid">
    <!-- EDT semaine -->
    <div class="card">
      <div class="card-header">
        <div class="card-header-title"><span class="material-icons-round">calendar_view_week</span> Ma semaine</div>
        <a href="<?= APP_URL ?>/eleve/emploi_du_temps.php" class="btn btn-sm btn-outline">Voir tout</a>
      </div>
      <div class="card-body">
        <?php if (empty($cours)): ?>
        <div class="empty-state" style="padding:2rem">
          <div class="empty-state-icon"><span class="material-icons-round">calendar_today</span></div>
          <h3>Emploi du temps non disponible</h3>
          <p>Votre emploi du temps n'est pas encore publié.</p>
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

    <!-- Messages récents -->
    <div class="card">
      <div class="card-header">
        <div class="card-header-title"><span class="material-icons-round">inbox</span> Messages</div>
        <a href="<?= APP_URL ?>/eleve/messages.php" class="btn btn-sm btn-outline">Voir tout</a>
      </div>
      <?php if (empty($recentMessages)): ?>
      <div class="card-body"><div class="empty-state" style="padding:1.5rem 1rem">
        <div class="empty-state-icon"><span class="material-icons-round">inbox</span></div>
        <h3>Aucun message</h3>
      </div></div>
      <?php else: ?>
      <div style="padding:.4rem">
        <?php foreach ($recentMessages as $m):
          [$bClass,$icon] = getMessageTypeLabel($m['type']); ?>
        <a href="<?= APP_URL ?>/eleve/messages.php?id=<?= $m['id'] ?>"
           style="display:flex;align-items:center;gap:.7rem;padding:.65rem .75rem;border-radius:var(--radius-sm);text-decoration:none;background:<?= !$m['est_lu']?'var(--primary-lt)':'transparent' ?>">
          <div class="stat-icon <?= $bClass==='purple'?'purple':($bClass==='warning'?'orange':$bClass) ?>" style="width:32px;height:32px;flex-shrink:0">
            <span class="material-icons-round" style="font-size:15px"><?= $icon ?></span>
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-size:.82rem;font-weight:<?= !$m['est_lu']?'700':'500' ?>;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= h($m['sujet']) ?></div>
            <div style="font-size:.72rem;color:var(--text-muted)"><?= h($m['exp_prenom'].' '.$m['exp_nom']) ?> · <?= date('d/m',strtotime($m['date_envoi'])) ?></div>
          </div>
          <?php if (!$m['est_lu']): ?><div style="width:7px;height:7px;background:var(--primary);border-radius:50%;flex-shrink:0"></div><?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<style>
@media(max-width:900px){.db-grid{grid-template-columns:1fr!important}}
</style>
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body></html>
