<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();
$currentUser = getCurrentUser();
$pdo = getDB();

$version    = (int)($_GET['version'] ?? getCurrentVersion());
$idClasse   = (int)($_GET['classe']  ?? 0);
$allClasses = getClasses();
$versions   = $pdo->query('SELECT DISTINCT version FROM emplois_du_temps ORDER BY version DESC')->fetchAll(PDO::FETCH_COLUMN);
$jours      = getJoursSemaine();

if (!$idClasse && !empty($allClasses)) $idClasse = $allClasses[0]['id'];

$stmtCr = $pdo->prepare("
    SELECT DISTINCT c.heure_debut, c.heure_fin
    FROM emplois_du_temps e
    JOIN creneaux c ON c.id=e.id_creneau
    WHERE e.version=? AND e.id_classe=?
    ORDER BY c.heure_debut
");
$stmtCr->execute([$version, $idClasse]);
$creneaux = $stmtCr->fetchAll();

$grille = [];
foreach ($creneaux as $cr) {
    $key = $cr['heure_debut'] . '-' . $cr['heure_fin'];
    $grille[$key] = [];
    foreach ($jours as $j) $grille[$key][$j] = null;
}
$stmtEdt = $pdo->prepare("
    SELECT e.*, m.nom AS mat_nom, m.couleur_hex,
           s.nom AS salle_nom, c.jour, c.heure_debut, c.heure_fin,
           u.nom AS prof_nom, u.prenom AS prof_prenom, e.statut
    FROM emplois_du_temps e
    JOIN matieres m  ON m.id=e.id_matiere
    JOIN salles s    ON s.id=e.id_salle
    JOIN creneaux c  ON c.id=e.id_creneau
    JOIN utilisateurs u ON u.id=e.id_professeur
    WHERE e.version=? AND e.id_classe=?
");
$stmtEdt->execute([$version, $idClasse]);
foreach ($stmtEdt->fetchAll() as $row) {
    $key = $row['heure_debut'] . '-' . $row['heure_fin'];
    if (isset($grille[$key][$row['jour']])) $grille[$key][$row['jour']] = $row;
}

$pageTitle = 'Emplois du temps'; $activeMenu = 'emplois_du_temps';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_admin.php';
?>

<div class="main-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1 class="page-title"><span class="material-icons-round">calendar_month</span> Emplois du temps</h1>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
      <a href="?version=<?= $version ?>&classe=<?= $idClasse ?>&export=1" class="btn btn-outline">
        <span class="material-icons-round">print</span> Imprimer
      </a>
    </div>
  </div>

  <!-- Filtres -->
  <div class="card" style="margin-bottom:1.25rem">
    <div class="card-body" style="padding:.85rem 1.25rem">
      <form method="GET" style="display:flex;gap:.85rem;flex-wrap:wrap;align-items:flex-end">
        <div class="form-group" style="margin:0;flex:1;min-width:160px">
          <label class="form-label">Classe</label>
          <select name="classe" class="form-control" onchange="this.form.submit()">
            <?php foreach ($allClasses as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $c['id']==$idClasse?'selected':'' ?>><?= h($c['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="margin:0;min-width:120px">
          <label class="form-label">Version</label>
          <select name="version" class="form-control" onchange="this.form.submit()">
            <?php foreach ($versions as $v): ?>
            <option value="<?= $v ?>" <?= $v==$version?'selected':'' ?>>Version <?= $v ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </form>
    </div>
  </div>

  <?php if (empty($grille)): ?>
  <div class="card"><div class="card-body">
    <div class="empty-state">
      <div class="empty-state-icon"><span class="material-icons-round">calendar_today</span></div>
      <h3>Aucun créneau pour cette sélection</h3>
      <p>Aucun emploi du temps n'a été généré pour cette classe et cette version.</p>
      <a href="<?= APP_URL ?>/admin/generer.php" class="btn btn-primary" style="margin-top:1rem">
        <span class="material-icons-round">auto_fix_high</span> Générer
      </a>
    </div>
  </div></div>
  <?php else: ?>
  <!-- Grille EDT -->
  <div class="card">
    <div class="card-header">
      <div class="card-header-title"><span class="material-icons-round">view_week</span>
        <?= h($allClasses[array_search($idClasse, array_column($allClasses,'id'))]['nom'] ?? '') ?>
      </div>
      <span class="badge badge-primary">Version <?= $version ?></span>
    </div>
    <div style="overflow-x:auto">
      <table style="width:100%;border-collapse:collapse;min-width:700px">
        <thead>
          <tr>
            <th style="padding:.7rem 1rem;background:var(--bg);border-bottom:1px solid var(--border);font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);width:110px">Horaire</th>
            <?php foreach ($jours as $j): ?>
            <th style="padding:.7rem .85rem;background:var(--bg);border-bottom:1px solid var(--border);font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);text-align:center"><?= $j ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($grille as $crKey => $joursData):
            [$hDeb,$hFin] = explode('-', $crKey, 2);
          ?>
          <tr>
            <td style="padding:.6rem 1rem;border-bottom:1px solid var(--border);border-right:1px solid var(--border);font-size:.8rem;color:var(--text-muted);white-space:nowrap;font-weight:500">
              <?= formatHeure($hDeb) ?><br><span style="color:var(--text-light)"><?= formatHeure($hFin) ?></span>
            </td>
            <?php foreach ($jours as $j):
              $cell = $joursData[$j] ?? null;
            ?>
            <td style="padding:.4rem .5rem;border-bottom:1px solid var(--border);border-right:1px solid var(--border);vertical-align:top;min-width:130px">
              <?php if ($cell): ?>
              <div style="background:<?= h($cell['couleur_hex']) ?>18;border-left:3px solid <?= h($cell['couleur_hex']) ?>;border-radius:0 var(--radius-sm) var(--radius-sm) 0;padding:.5rem .6rem">
                <div style="font-weight:700;font-size:.8rem;color:var(--text);margin-bottom:.15rem"><?= h($cell['mat_nom']) ?></div>
                <div style="font-size:.72rem;color:var(--text-muted)"><?= h($cell['prof_prenom'].' '.$cell['prof_nom']) ?></div>
                <div style="font-size:.72rem;color:var(--text-muted);margin-top:.1rem">
                  <span class="material-icons-round" style="font-size:11px">meeting_room</span> <?= h($cell['salle_nom']) ?>
                </div>
                <div style="margin-top:.3rem"><?= getStatusBadge($cell['statut']) ?></div>
              </div>
              <?php else: ?>
              <div style="height:100%;min-height:55px"></div>
              <?php endif; ?>
            </td>
            <?php endforeach; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</div>

<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body></html>
