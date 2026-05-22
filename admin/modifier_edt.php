<?php
// admin/modifier_edt.php — Modification manuelle d'un créneau EDT
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();
$currentUser = getCurrentUser();
$pdo = getDB();
$msg = ''; $msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $id         = (int)$_POST['id'];
    $idSalle    = (int)$_POST['id_salle'];
    $idCreneau  = (int)$_POST['id_creneau'];
    $idMatiere  = (int)$_POST['id_matiere'];
    $idProf     = (int)$_POST['id_professeur'];
    $version    = getCurrentVersion();

    // Vérifier conflits
    $errors = [];
    // Salle occupée sur ce créneau (autre que cet enregistrement)
    $chkS = $pdo->prepare("SELECT id FROM emplois_du_temps WHERE version=? AND id_creneau=? AND id_salle=? AND id!=? LIMIT 1");
    $chkS->execute([$version,$idCreneau,$idSalle,$id]);
    if ($chkS->fetch()) $errors[] = getBusinessError('salle_occupied');

    // Prof occupé sur ce créneau
    $chkP = $pdo->prepare("SELECT id FROM emplois_du_temps WHERE version=? AND id_creneau=? AND id_professeur=? AND id!=? LIMIT 1");
    $chkP->execute([$version,$idCreneau,$idProf,$id]);
    if ($chkP->fetch()) $errors[] = getBusinessError('prof_occupied');

    if (!empty($errors)) {
        $msg = implode('<br>', $errors); $msgType = 'danger';
    } else {
        $pdo->prepare("UPDATE emplois_du_temps SET id_salle=?,id_creneau=?,id_matiere=?,id_professeur=?,statut='provisoire' WHERE id=?")
            ->execute([$idSalle,$idCreneau,$idMatiere,$idProf,$id]);
        $msg = 'Créneau modifié avec succès. Statut remis à <em>provisoire</em> pour re-validation.';
        sendNotification($currentUser['id'],'EDT modifié manuellement','Un créneau a été modifié par l\'administrateur.','info');
    }
}

$version  = getCurrentVersion();
$classes  = getClasses();
$idClasse = (int)($_GET['classe'] ?? ($classes[0]['id'] ?? 0));

$stmt = $pdo->prepare("
    SELECT e.*, m.nom AS mat_nom, m.couleur_hex,
           cl.nom AS classe_nom, s.nom AS salle_nom,
           u.nom AS prof_nom, u.prenom AS prof_prenom,
           c.jour, c.heure_debut, c.heure_fin
    FROM emplois_du_temps e
    JOIN matieres m  ON m.id=e.id_matiere
    JOIN classes cl  ON cl.id=e.id_classe
    JOIN salles s    ON s.id=e.id_salle
    JOIN utilisateurs u ON u.id=e.id_professeur
    JOIN creneaux c  ON c.id=e.id_creneau
    WHERE e.version=? AND e.id_classe=?
    ORDER BY c.jour, c.heure_debut
");
$stmt->execute([$version, $idClasse]);
$cours = $stmt->fetchAll();

$allSalles   = getSalles();
$allMatieres = getMatieres();
$allCreneaux = getCreneaux();
$allProfs    = getProfesseurs();

$pageTitle = 'Modifier l\'EDT'; $activeMenu = 'modifier_edt';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_admin.php';
?>

<div class="main-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1 class="page-title"><span class="material-icons-round">edit_calendar</span> Modifier l'emploi du temps</h1>
      <p class="page-subtitle">Modification manuelle — Version <?= $version ?></p>
    </div>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msgType ?>" data-auto-dismiss="6000">
    <span class="material-icons-round"><?= $msgType==='danger'?'error_outline':'check_circle' ?></span>
    <div class="alert-content"><?= $msg ?></div>
  </div>
  <?php endif; ?>

  <!-- Sélecteur de classe -->
  <div class="card" style="margin-bottom:1.25rem">
    <div class="card-body" style="padding:.85rem 1.25rem">
      <form method="GET" style="display:flex;align-items:flex-end;gap:1rem">
        <div class="form-group" style="margin:0;flex:1;max-width:260px">
          <label class="form-label">Classe</label>
          <select name="classe" class="form-control" onchange="this.form.submit()">
            <?php foreach ($classes as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $c['id']==$idClasse?'selected':'' ?>><?= h($c['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </form>
    </div>
  </div>

  <?php if (empty($cours)): ?>
  <div class="card"><div class="card-body"><div class="empty-state">
    <div class="empty-state-icon"><span class="material-icons-round">edit_calendar</span></div>
    <h3>Aucun créneau pour cette classe</h3>
    <p>Générez d'abord un emploi du temps.</p>
  </div></div></div>
  <?php else: ?>
  <div class="card">
    <div class="table-wrapper" style="border:none;border-radius:0">
      <table class="table">
        <thead>
          <tr><th>Jour / Horaire</th><th>Matière</th><th>Professeur</th><th>Salle</th><th>Statut</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php foreach ($cours as $row): ?>
          <tr>
            <td>
              <div style="font-weight:600;font-size:.85rem"><?= h($row['jour']) ?></div>
              <div style="font-size:.78rem;color:var(--text-muted)"><?= formatHeure($row['heure_debut']) ?> – <?= formatHeure($row['heure_fin']) ?></div>
            </td>
            <td><div style="display:flex;align-items:center;gap:.5rem"><span class="color-dot" style="background:<?= h($row['couleur_hex']) ?>"></span><?= h($row['mat_nom']) ?></div></td>
            <td style="font-size:.875rem"><?= h($row['prof_prenom'].' '.$row['prof_nom']) ?></td>
            <td style="font-size:.875rem;color:var(--text-muted)"><?= h($row['salle_nom']) ?></td>
            <td><?= getStatusBadge($row['statut']) ?></td>
            <td>
              <button class="btn btn-sm btn-outline" onclick='openEdit(<?= json_encode($row,JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>
                <span class="material-icons-round">edit</span>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Modal Edit -->
<div class="modal-overlay" id="modalEdit">
  <div class="modal" style="max-width:500px">
    <div class="modal-header"><span class="modal-title">Modifier le créneau</span>
      <button class="modal-close" onclick="closeModal('modalEdit')"><span class="material-icons-round">close</span></button></div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
      <input type="hidden" name="id" id="e_id">
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Matière</label>
          <select name="id_matiere" id="e_mat" class="form-control">
            <?php foreach ($allMatieres as $m): ?><option value="<?= $m['id'] ?>"><?= h($m['nom']) ?></option><?php endforeach; ?>
          </select></div>
        <div class="form-group"><label class="form-label">Professeur</label>
          <select name="id_professeur" id="e_prof" class="form-control">
            <?php foreach ($allProfs as $p): ?><option value="<?= $p['id'] ?>"><?= h($p['prenom'].' '.$p['nom']) ?></option><?php endforeach; ?>
          </select></div>
        <div class="form-group"><label class="form-label">Salle</label>
          <select name="id_salle" id="e_salle" class="form-control">
            <?php foreach ($allSalles as $s): ?><option value="<?= $s['id'] ?>"><?= h($s['nom']) ?> (<?= $s['capacite'] ?> pl.)</option><?php endforeach; ?>
          </select></div>
        <div class="form-group"><label class="form-label">Créneau horaire</label>
          <select name="id_creneau" id="e_creneau" class="form-control">
            <?php foreach ($allCreneaux as $cr): ?><option value="<?= $cr['id'] ?>"><?= h($cr['jour']) ?> <?= formatHeure($cr['heure_debut']) ?>–<?= formatHeure($cr['heure_fin']) ?></option><?php endforeach; ?>
          </select></div>
        <div class="alert alert-warning" style="margin-bottom:0">
          <span class="material-icons-round">warning</span>
          <div class="alert-content">Toute modification remettra le créneau en statut <em>provisoire</em> pour re-validation.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalEdit')">Annuler</button>
        <button type="submit" class="btn btn-primary"><span class="material-icons-round">save</span> Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<script src="<?= APP_URL ?>/assets/js/main.js"></script>
<script>
function openEdit(row) {
  document.getElementById('e_id').value      = row.id;
  document.getElementById('e_mat').value     = row.id_matiere;
  document.getElementById('e_prof').value    = row.id_professeur;
  document.getElementById('e_salle').value   = row.id_salle;
  document.getElementById('e_creneau').value = row.id_creneau;
  openModal('modalEdit');
}
</script>
</body></html>
