<?php
// professeur/disponibilites.php — Gestion des disponibilités
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireProfesseur();
$currentUser = getCurrentUser();
$pdo = getDB();
$msg = ''; $msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $jour  = $_POST['jour']        ?? '';
        $hdeb  = $_POST['heure_debut'] ?? '';
        $hfin  = $_POST['heure_fin']   ?? '';
        $dispo = isset($_POST['disponible']) ? 1 : 0;

        if (!$jour || !$hdeb || !$hfin) {
            $msg = getBusinessError('champs_vides'); $msgType = 'danger';
        } elseif ($hdeb >= $hfin) {
            $msg = getBusinessError('horaire_invalide'); $msgType = 'danger';
        } else {
            // Vérifier chevauchement
            $chk = $pdo->prepare("
                SELECT id FROM disponibilites
                WHERE id_professeur=? AND jour=?
                AND ((heure_debut <= ? AND heure_fin > ?) OR (heure_debut < ? AND heure_fin >= ?))
                LIMIT 1
            ");
            $chk->execute([$currentUser['id'], $jour, $hdeb, $hdeb, $hfin, $hfin]);
            if ($chk->fetch()) {
                $msg = 'Ce créneau chevauche une disponibilité déjà enregistrée pour ce jour.';
                $msgType = 'danger';
            } else {
                $pdo->prepare("INSERT INTO disponibilites (id_professeur, jour, heure_debut, heure_fin, disponible) VALUES (?,?,?,?,?)")
                    ->execute([$currentUser['id'], $jour, $hdeb.':00', $hfin.':00', $dispo]);
                $msg = 'Disponibilité enregistrée avec succès.';
            }
        }
    } elseif ($action === 'toggle') {
        $id  = (int)$_POST['id'];
        $val = (int)$_POST['val'];
        $pdo->prepare("UPDATE disponibilites SET disponible=? WHERE id=? AND id_professeur=?")
            ->execute([$val, $id, $currentUser['id']]);
        $msg = $val ? 'Créneau marqué comme disponible.' : 'Créneau marqué comme indisponible.';
        $msgType = $val ? 'success' : 'warning';
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM disponibilites WHERE id=? AND id_professeur=?")
            ->execute([$id, $currentUser['id']]);
        $msg = 'Disponibilité supprimée.'; $msgType = 'warning';
    } elseif ($action === 'reset') {
        $pdo->prepare("DELETE FROM disponibilites WHERE id_professeur=?")->execute([$currentUser['id']]);
        $msg = 'Toutes vos disponibilités ont été réinitialisées.'; $msgType = 'warning';
    }
}

$jours = getJoursSemaine();

$stmt = $pdo->prepare("
    SELECT * FROM disponibilites
    WHERE id_professeur=?
    ORDER BY FIELD(jour,'Lundi','Mardi','Mercredi','Jeudi','Vendredi'), heure_debut
");
$stmt->execute([$currentUser['id']]);
$dispos = $stmt->fetchAll();

// Grouper par jour
$parJour = array_fill_keys($jours, []);
foreach ($dispos as $d) {
    if (isset($parJour[$d['jour']])) $parJour[$d['jour']][] = $d;
}

$nbDispo   = count(array_filter($dispos, fn($d) => $d['disponible'] == 1));
$nbIndispo = count(array_filter($dispos, fn($d) => $d['disponible'] == 0));

$pageTitle = 'Mes disponibilités'; $activeMenu = 'disponibilites';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_prof.php';
?>

<div class="main-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1 class="page-title">
        <span class="material-icons-round">event_available</span>
        Mes disponibilités
      </h1>
      <p class="page-subtitle">
        Indiquez vos créneaux disponibles pour faciliter la génération de l'emploi du temps
      </p>
    </div>
    <div style="display:flex;gap:.6rem;flex-wrap:wrap">
      <button class="btn btn-primary" onclick="openModal('modalAdd')">
        <span class="material-icons-round">add</span> Ajouter un créneau
      </button>
      <?php if (!empty($dispos)): ?>
      <button class="btn btn-outline" onclick="openModal('modalReset')">
        <span class="material-icons-round">restart_alt</span> Réinitialiser
      </button>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msgType ?>">
    <span class="material-icons-round"><?= $msgType === 'danger' ? 'error_outline' : ($msgType === 'warning' ? 'warning' : 'check_circle') ?></span>
    <div class="alert-content"><?= h($msg) ?></div>
  </div>
  <?php endif; ?>

  <!-- Résumé -->
  <div class="stat-grid" style="margin-bottom:1.5rem">
    <div class="stat-card">
      <div class="stat-icon green"><span class="material-icons-round">check_circle</span></div>
      <div>
        <div class="stat-value"><?= $nbDispo ?></div>
        <div class="stat-label">Créneaux disponibles</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon red"><span class="material-icons-round">cancel</span></div>
      <div>
        <div class="stat-value"><?= $nbIndispo ?></div>
        <div class="stat-label">Créneaux indisponibles</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon blue"><span class="material-icons-round">calendar_month</span></div>
      <div>
        <div class="stat-value"><?= count($dispos) ?></div>
        <div class="stat-label">Total enregistrés</div>
      </div>
    </div>
  </div>

  <!-- Info -->
  <div class="alert alert-info" style="margin-bottom:1.5rem">
    <span class="material-icons-round">info</span>
    <div class="alert-content">
      <strong>Comment ça fonctionne ?</strong>
      Ajoutez vos créneaux disponibles pour que l'administrateur puisse en tenir compte lors de la génération de l'emploi du temps.
      Vous pouvez marquer chaque créneau comme disponible ou indisponible à tout moment.
    </div>
  </div>

  <?php if (empty($dispos)): ?>
  <div class="card">
    <div class="card-body">
      <div class="empty-state">
        <div class="empty-state-icon"><span class="material-icons-round">event_available</span></div>
        <h3>Aucune disponibilité enregistrée</h3>
        <p>Ajoutez vos créneaux disponibles pour aider l'administration à construire un emploi du temps adapté.</p>
        <button class="btn btn-primary" onclick="openModal('modalAdd')" style="margin-top:1rem">
          <span class="material-icons-round">add</span> Ajouter un créneau
        </button>
      </div>
    </div>
  </div>
  <?php else: ?>

  <!-- Vue par jour -->
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem">
    <?php foreach ($jours as $jour): ?>
    <div class="card">
      <div class="card-header">
        <div class="card-header-title">
          <span class="material-icons-round">calendar_today</span>
          <?= $jour ?>
        </div>
        <span class="badge badge-secondary"><?= count($parJour[$jour]) ?> créneau<?= count($parJour[$jour]) > 1 ? 'x' : '' ?></span>
      </div>
      <div class="card-body" style="padding:.6rem">
        <?php if (empty($parJour[$jour])): ?>
          <div style="padding:.75rem;text-align:center;font-size:.8rem;color:var(--text-light);font-style:italic">
            Aucun créneau pour ce jour
          </div>
        <?php else: foreach ($parJour[$jour] as $d): ?>
          <div class="dispo-slot <?= $d['disponible'] ? 'available' : 'unavailable' ?>" style="margin-bottom:.4rem">
            <div style="display:flex;align-items:center;gap:.6rem">
              <span class="material-icons-round" style="font-size:16px;color:<?= $d['disponible'] ? 'var(--success)' : 'var(--danger)' ?>">
                <?= $d['disponible'] ? 'check_circle' : 'cancel' ?>
              </span>
              <div>
                <div style="font-weight:600;font-size:.85rem;color:var(--text)">
                  <?= formatHeure($d['heure_debut']) ?> – <?= formatHeure($d['heure_fin']) ?>
                </div>
                <div style="font-size:.72rem;color:var(--text-muted)">
                  <?= $d['disponible'] ? 'Disponible' : 'Indisponible' ?>
                </div>
              </div>
            </div>
            <div style="display:flex;gap:.3rem">
              <!-- Toggle -->
              <form method="POST" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?= $d['id'] ?>">
                <input type="hidden" name="val" value="<?= $d['disponible'] ? 0 : 1 ?>">
                <button type="submit" class="btn btn-sm btn-ghost" style="padding:.25rem"
                        title="<?= $d['disponible'] ? 'Marquer indisponible' : 'Marquer disponible' ?>">
                  <span class="material-icons-round" style="font-size:16px;color:var(--text-muted)">
                    <?= $d['disponible'] ? 'toggle_on' : 'toggle_off' ?>
                  </span>
                </button>
              </form>
              <!-- Supprimer -->
              <button class="btn btn-sm btn-ghost" style="padding:.25rem"
                      onclick="confirmDel(<?= $d['id'] ?>,'<?= h(addslashes($jour)) ?> <?= formatHeure($d['heure_debut']) ?>–<?= formatHeure($d['heure_fin']) ?>')"
                      title="Supprimer">
                <span class="material-icons-round" style="font-size:16px;color:var(--danger)">delete</span>
              </button>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Modal Ajout -->
<div class="modal-overlay" id="modalAdd">
  <div class="modal" style="max-width:420px">
    <div class="modal-header">
      <span class="modal-title">Ajouter une disponibilité</span>
      <button class="modal-close" onclick="closeModal('modalAdd')"><span class="material-icons-round">close</span></button>
    </div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
      <input type="hidden" name="action" value="add">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Jour <span style="color:var(--danger)">*</span></label>
          <select name="jour" class="form-control">
            <?php foreach ($jours as $j): ?><option value="<?= $j ?>"><?= $j ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Heure de début <span style="color:var(--danger)">*</span></label>
            <input type="time" name="heure_debut" class="form-control" value="08:00" required>
          </div>
          <div class="form-group">
            <label class="form-label">Heure de fin <span style="color:var(--danger)">*</span></label>
            <input type="time" name="heure_fin" class="form-control" value="10:00" required>
          </div>
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer">
            <input type="checkbox" name="disponible" value="1" checked style="width:16px;height:16px;accent-color:var(--primary)">
            <span style="font-size:.875rem;font-weight:500;color:var(--text)">Je suis disponible sur ce créneau</span>
          </label>
          <div class="form-hint">Décochez si vous souhaitez enregistrer une indisponibilité.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalAdd')">Annuler</button>
        <button type="submit" class="btn btn-primary"><span class="material-icons-round">save</span> Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Suppression -->
<div class="modal-overlay" id="modalDelete">
  <div class="modal" style="max-width:380px">
    <div class="modal-header">
      <span class="modal-title">Supprimer ce créneau</span>
      <button class="modal-close" onclick="closeModal('modalDelete')"><span class="material-icons-round">close</span></button>
    </div>
    <div class="modal-body">
      <div style="display:flex;gap:1rem;align-items:flex-start">
        <div style="width:44px;height:44px;background:var(--danger-lt);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <span class="material-icons-round" style="color:var(--danger)">delete</span>
        </div>
        <p style="font-size:.875rem;color:var(--text-muted)">
          Supprimer le créneau <strong id="del_label"></strong> de vos disponibilités ?
        </p>
      </div>
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

<!-- Modal Réinitialisation -->
<div class="modal-overlay" id="modalReset">
  <div class="modal" style="max-width:400px">
    <div class="modal-header">
      <span class="modal-title">Réinitialiser les disponibilités</span>
      <button class="modal-close" onclick="closeModal('modalReset')"><span class="material-icons-round">close</span></button>
    </div>
    <div class="modal-body">
      <div style="display:flex;gap:1rem;align-items:flex-start">
        <div style="width:44px;height:44px;background:var(--warning-lt);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <span class="material-icons-round" style="color:var(--warning)">warning</span>
        </div>
        <p style="font-size:.875rem;color:var(--text-muted)">
          Toutes vos disponibilités enregistrées seront supprimées. Cette action est irréversible.
        </p>
      </div>
    </div>
    <div class="modal-footer">
      <form method="POST" style="display:flex;gap:.6rem">
        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
        <input type="hidden" name="action" value="reset">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalReset')">Annuler</button>
        <button type="submit" class="btn btn-danger"><span class="material-icons-round">restart_alt</span> Réinitialiser tout</button>
      </form>
    </div>
  </div>
</div>

<script src="<?= APP_URL ?>/assets/js/main.js"></script>
<script>
function confirmDel(id, label) {
  document.getElementById('del_id').value      = id;
  document.getElementById('del_label').textContent = label;
  openModal('modalDelete');
}
</script>
</body></html>
