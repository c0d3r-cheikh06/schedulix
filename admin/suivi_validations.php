<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();
$currentUser = getCurrentUser();
$pdo = getDB();

$version = getCurrentVersion();

$stmt = $pdo->prepare("
    SELECT e.id, e.statut, e.commentaire_validation,
           m.nom AS mat_nom, m.couleur_hex,
           cl.nom AS classe_nom, s.nom AS salle_nom,
           u.nom AS prof_nom, u.prenom AS prof_prenom,
           c.jour, c.heure_debut, c.heure_fin
    FROM emplois_du_temps e
    JOIN matieres m  ON m.id=e.id_matiere
    JOIN classes cl  ON cl.id=e.id_classe
    JOIN salles s    ON s.id=e.id_salle
    JOIN utilisateurs u ON u.id=e.id_professeur
    JOIN creneaux c  ON c.id=e.id_creneau
    WHERE e.version=?
    ORDER BY c.jour, c.heure_debut, cl.nom
");
$stmt->execute([$version]);
$rows = $stmt->fetchAll();

// Résumé
$stats = ['provisoire'=>0,'valide'=>0,'rejete'=>0,'confirme'=>0];
foreach ($rows as $r) { if (isset($stats[$r['statut']])) $stats[$r['statut']]++; }
$total = count($rows);

$pageTitle = 'Suivi des validations'; $activeMenu = 'suivi_validations';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_admin.php';
?>

<div class="main-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1 class="page-title"><span class="material-icons-round">fact_check</span> Suivi des validations</h1>
      <p class="page-subtitle">Version <?= $version ?> — <?= $total ?> créneau<?= $total>1?'x':'' ?> au total</p>
    </div>
    <?php if ($stats['valide'] === $total && $total > 0): ?>
    <span class="badge badge-success" style="font-size:.875rem;padding:.4rem .85rem">
      <span class="material-icons-round" style="font-size:14px">check_circle</span> Tout est validé
    </span>
    <?php endif; ?>
  </div>

  <!-- Stats -->
  <div class="stat-grid" style="margin-bottom:1.5rem">
    <div class="stat-card">
      <div class="stat-icon orange"><span class="material-icons-round">pending</span></div>
      <div><div class="stat-value"><?= $stats['provisoire'] ?></div><div class="stat-label">En attente</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon green"><span class="material-icons-round">check_circle</span></div>
      <div><div class="stat-value"><?= $stats['valide'] ?></div><div class="stat-label">Validés</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon red"><span class="material-icons-round">cancel</span></div>
      <div><div class="stat-value"><?= $stats['rejete'] ?></div><div class="stat-label">Rejetés</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon blue"><span class="material-icons-round">verified</span></div>
      <div><div class="stat-value"><?= $stats['confirme'] ?></div><div class="stat-label">Confirmés</div></div>
    </div>
  </div>

  <!-- Barre de progression -->
  <?php if ($total > 0): $pct = round($stats['valide']/$total*100); ?>
  <div class="card" style="margin-bottom:1.25rem">
    <div class="card-body" style="padding:1.1rem 1.5rem">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.6rem">
        <span style="font-size:.875rem;font-weight:600;color:var(--text)">Progression des validations</span>
        <span style="font-size:.875rem;font-weight:700;color:var(--success)"><?= $pct ?>%</span>
      </div>
      <div style="height:8px;background:var(--bg);border-radius:99px;overflow:hidden">
        <div style="height:100%;width:<?= $pct ?>%;background:linear-gradient(90deg,var(--success),#34D399);border-radius:99px;transition:width .6s ease"></div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="table-wrapper" style="border:none;border-radius:0">
      <table class="table">
        <thead>
          <tr>
            <th>Créneau</th>
            <th>Matière</th>
            <th>Classe</th>
            <th>Professeur</th>
            <th>Salle</th>
            <th>Statut</th>
            <th>Commentaire</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
          <tr><td colspan="7">
            <div class="empty-state"><div class="empty-state-icon"><span class="material-icons-round">fact_check</span></div>
              <h3>Aucun créneau à valider</h3><p>Générez un emploi du temps d'abord.</p>
            </div>
          </td></tr>
          <?php else: foreach ($rows as $r): ?>
          <tr>
            <td style="white-space:nowrap">
              <div style="font-weight:600;font-size:.825rem;color:var(--text)"><?= h($r['jour']) ?></div>
              <div style="font-size:.78rem;color:var(--text-muted)"><?= formatHeure($r['heure_debut']) ?> – <?= formatHeure($r['heure_fin']) ?></div>
            </td>
            <td>
              <div style="display:flex;align-items:center;gap:.5rem">
                <span class="color-dot" style="background:<?= h($r['couleur_hex']) ?>"></span>
                <span style="font-size:.875rem;font-weight:500"><?= h($r['mat_nom']) ?></span>
              </div>
            </td>
            <td><span class="badge badge-secondary"><?= h($r['classe_nom']) ?></span></td>
            <td style="font-size:.875rem"><?= h($r['prof_prenom'].' '.$r['prof_nom']) ?></td>
            <td style="font-size:.875rem;color:var(--text-muted)"><?= h($r['salle_nom']) ?></td>
            <td><?= getStatusBadge($r['statut']) ?></td>
            <td style="font-size:.8rem;color:var(--text-muted);max-width:200px">
              <?= $r['commentaire_validation'] ? h($r['commentaire_validation']) : '<span style="font-style:italic;color:var(--text-light)">—</span>' ?>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body></html>
