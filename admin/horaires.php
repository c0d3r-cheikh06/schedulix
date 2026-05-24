<?php
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
    $action   = $_POST['action'] ?? '';
    $jour     = $_POST['jour']        ?? '';
    $hdeb     = $_POST['heure_debut'] ?? '';
    $hfin     = $_POST['heure_fin']   ?? '';

    if ($action === 'add') {
        if (!$jour || !$hdeb || !$hfin) {
            $msg = getBusinessError('champs_vides'); $msgType = 'danger';
        } elseif ($hdeb >= $hfin) {
            $msg = getBusinessError('horaire_invalide'); $msgType = 'danger';
        } else {
            $chk = $pdo->prepare('SELECT id FROM creneaux WHERE jour=? AND heure_debut=? AND heure_fin=? LIMIT 1');
            $chk->execute([$jour, $hdeb.':00', $hfin.':00']);
            if ($chk->fetch()) {
                $msg = 'Ce créneau existe déjà pour ce jour.'; $msgType = 'danger';
            } else {
                $pdo->prepare('INSERT INTO creneaux (jour,heure_debut,heure_fin) VALUES (?,?,?)')->execute([$jour,$hdeb.':00',$hfin.':00']);
                $msg = "Créneau <strong>{$jour} {$hdeb}–{$hfin}</strong> ajouté.";
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $chk = $pdo->prepare('SELECT COUNT(*) FROM emplois_du_temps WHERE id_creneau=?');
        $chk->execute([$id]);
        if ((int)$chk->fetchColumn() > 0) {
            $msg = getBusinessError('delete_protected'); $msgType = 'danger';
        } else {
            $pdo->prepare('DELETE FROM creneaux WHERE id=?')->execute([$id]);
            $msg = 'Créneau supprimé.'; $msgType = 'warning';
        }
    }
}

$creneaux = getCreneaux();
$jours    = getJoursSemaine();

// Grouper par jour
$parJour = array_fill_keys($jours, []);
foreach ($creneaux as $cr) { $parJour[$cr['jour']][] = $cr; }

$pageTitle = 'Gestion des Horaires'; $activeMenu = 'horaires';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_admin.php';
?>

<div class="main-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1 class="page-title"><span class="material-icons-round">schedule</span> Horaires et Créneaux</h1>
      <p class="page-subtitle"><?= count($creneaux) ?> créneau<?= count($creneaux)>1?'x':'' ?> défini<?= count($creneaux)>1?'s':'' ?></p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modalAdd')">
      <span class="material-icons-round">add</span> Nouveau créneau
    </button>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msgType ?>" data-auto-dismiss="5000">
    <span class="material-icons-round"><?= $msgType==='danger'?'error_outline':'check_circle' ?></span>
    <div class="alert-content"><?= $msg ?></div>
  </div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem">
    <?php foreach ($jours as $jour): ?>
    <div class="card">
      <div class="card-header">
        <div class="card-header-title"><span class="material-icons-round">calendar_today</span> <?= $jour ?></div>
        <span class="badge badge-secondary"><?= count($parJour[$jour]) ?></span>
      </div>
      <div class="card-body" style="padding:.5rem">
        <?php if (empty($parJour[$jour])): ?>
        <div style="padding:.75rem;text-align:center;font-size:.8rem;color:var(--text-light);font-style:italic">Aucun créneau</div>
        <?php else: foreach ($parJour[$jour] as $cr): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:.5rem .75rem;border-radius:var(--radius-sm);transition:background var(--transition)" onmouseenter="this.style.background='var(--bg)'" onmouseleave="this.style.background=''">
          <div style="display:flex;align-items:center;gap:.5rem">
            <span class="material-icons-round" style="font-size:16px;color:var(--primary)">schedule</span>
            <span style="font-size:.875rem;font-weight:500"><?= formatHeure($cr['heure_debut']) ?> – <?= formatHeure($cr['heure_fin']) ?></span>
          </div>
          <button class="btn btn-sm btn-ghost" onclick="confirmDelCr(<?= $cr['id'] ?>,'<?= $jour ?> <?= formatHeure($cr['heure_debut']) ?>–<?= formatHeure($cr['heure_fin']) ?>')" style="padding:.2rem .4rem">
            <span class="material-icons-round" style="font-size:15px;color:var(--danger)">delete</span>
          </button>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Modal Add -->
<div class="modal-overlay" id="modalAdd">
  <div class="modal" style="max-width:400px">
    <div class="modal-header"><span class="modal-title">Nouveau créneau</span>
      <button class="modal-close" onclick="closeModal('modalAdd')"><span class="material-icons-round">close</span></button></div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
      <input type="hidden" name="action" value="add">
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Jour <span style="color:var(--danger)">*</span></label>
          <select name="jour" class="form-control">
            <?php foreach ($jours as $j): ?><option><?= $j ?></option><?php endforeach; ?>
          </select></div>
        <div class="form-grid">
          <div class="form-group"><label class="form-label">Heure début</label>
            <input type="time" name="heure_debut" class="form-control" value="08:00"></div>
          <div class="form-group"><label class="form-label">Heure fin</label>
            <input type="time" name="heure_fin" class="form-control" value="09:00"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalAdd')">Annuler</button>
        <button type="submit" class="btn btn-primary"><span class="material-icons-round">save</span> Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Delete -->
<div class="modal-overlay" id="modalDelete">
  <div class="modal" style="max-width:380px">
    <div class="modal-header"><span class="modal-title">Supprimer le créneau</span>
      <button class="modal-close" onclick="closeModal('modalDelete')"><span class="material-icons-round">close</span></button></div>
    <div class="modal-body">
      <p style="font-size:.875rem;color:var(--text-muted)">Supprimer le créneau <strong id="del_label"></strong> ?</p>
    </div>
    <div class="modal-footer">
      <form method="POST" style="display:flex;gap:.6rem">
        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="del_id">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalDelete')">Annuler</button>
        <button type="submit" class="btn btn-danger"><span class="material-icons-round">delete</span> Supprimer</button>
      </form>
    </div>
  </div>
</div>

<script src="<?= APP_URL ?>/assets/js/main.js"></script>
<script>
function confirmDelCr(id,label){
  document.getElementById('del_id').value=id;
  document.getElementById('del_label').textContent=label;
  openModal('modalDelete');
}
</script>
</body></html>
