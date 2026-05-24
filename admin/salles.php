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
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $nom      = sanitize($_POST['nom'] ?? '');
        $capacite = (int)($_POST['capacite'] ?? 30);
        $type     = in_array($_POST['type'] ?? '', ['normale','informatique','laboratoire']) ? $_POST['type'] : 'normale';

        if (!$nom) {
            $msg = getBusinessError('champs_vides'); $msgType = 'danger';
        } elseif (strlen($nom) < 2 || strlen($nom) > 100) {
            $msg = getBusinessError('longueur_nom'); $msgType = 'danger';
        } elseif ($capacite < 1 || $capacite > 500) {
            $msg = getBusinessError('capacite_invalide'); $msgType = 'danger';
        } else {
            // Vérifier doublon
            $chk = $pdo->prepare('SELECT id FROM salles WHERE LOWER(nom)=LOWER(?) LIMIT 1');
            $chk->execute([$nom]);
            if ($chk->fetch()) {
                $msg = getBusinessError('salle_exists'); $msgType = 'danger';
            } else {
                $pdo->prepare('INSERT INTO salles (nom,capacite,type) VALUES (?,?,?)')->execute([$nom,$capacite,$type]);
                $msg = "La salle «&nbsp;<strong>" . h($nom) . "</strong>&nbsp;» a été ajoutée avec succès.";
            }
        }
    } elseif ($action === 'edit') {
        $id       = (int)$_POST['id'];
        $nom      = sanitize($_POST['nom'] ?? '');
        $capacite = (int)($_POST['capacite'] ?? 30);
        $type     = in_array($_POST['type'] ?? '', ['normale','informatique','laboratoire']) ? $_POST['type'] : 'normale';

        if (!$nom) {
            $msg = getBusinessError('champs_vides'); $msgType = 'danger';
        } else {
            // Vérifier doublon (sauf l'élément lui-même)
            $chk = $pdo->prepare('SELECT id FROM salles WHERE LOWER(nom)=LOWER(?) AND id!=? LIMIT 1');
            $chk->execute([$nom, $id]);
            if ($chk->fetch()) {
                $msg = getBusinessError('salle_exists'); $msgType = 'danger';
            } else {
                $pdo->prepare('UPDATE salles SET nom=?,capacite=?,type=? WHERE id=?')->execute([$nom,$capacite,$type,$id]);
                $msg = "La salle «&nbsp;<strong>" . h($nom) . "</strong>&nbsp;» a été modifiée.";
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        // Vérifier si utilisée dans un EDT actif
        $chk = $pdo->prepare("SELECT COUNT(*) FROM emplois_du_temps e JOIN (SELECT MAX(version) v FROM emplois_du_temps) mv ON e.version=mv.v WHERE e.id_salle=?");
        $chk->execute([$id]);
        if ((int)$chk->fetchColumn() > 0) {
            $msg = getBusinessError('delete_protected'); $msgType = 'danger';
        } else {
            $row = $pdo->prepare('SELECT nom FROM salles WHERE id=?');
            $row->execute([$id]);
            $nomSalle = $row->fetchColumn();
            $pdo->prepare('DELETE FROM salles WHERE id=?')->execute([$id]);
            $msg = "La salle «&nbsp;<strong>" . h($nomSalle) . "</strong>&nbsp;» a été supprimée.";
            $msgType = 'warning';
        }
    }
}

$salles     = getSalles();
$typeIcons  = ['normale'=>'meeting_room','informatique'=>'computer','laboratoire'=>'science'];
$typeLabels = ['normale'=>'Salle normale','informatique'=>'Informatique','laboratoire'=>'Laboratoire'];
$typeColors = ['normale'=>'blue','informatique'=>'purple','laboratoire'=>'teal'];

$pageTitle  = 'Gestion des Salles'; $activeMenu = 'salles';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_admin.php';
?>

<div class="main-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1 class="page-title"><span class="material-icons-round">meeting_room</span> Salles</h1>
      <p class="page-subtitle"><?= count($salles) ?> salle<?= count($salles) > 1 ? 's' : '' ?> enregistrée<?= count($salles) > 1 ? 's' : '' ?></p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modalAdd')">
      <span class="material-icons-round">add</span> Nouvelle salle
    </button>
  </div>

  <?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?>" data-auto-dismiss="6000">
      <span class="material-icons-round"><?= $msgType==='success'||$msgType==='warning' ? 'check_circle' : 'error_outline' ?></span>
      <div class="alert-content"><?= $msg ?></div>
    </div>
  <?php endif; ?>

  <?php if (empty($salles)): ?>
    <div class="card"><div class="card-body">
      <div class="empty-state">
        <div class="empty-state-icon"><span class="material-icons-round">meeting_room</span></div>
        <h3>Aucune salle enregistrée</h3>
        <p>Commencez par ajouter des salles de classe pour pouvoir générer les emplois du temps.</p>
        <button class="btn btn-primary" onclick="openModal('modalAdd')" style="margin-top:1rem">
          <span class="material-icons-round">add</span> Ajouter une salle
        </button>
      </div>
    </div></div>
  <?php else: ?>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1rem">
    <?php foreach ($salles as $s): ?>
    <div class="card" style="display:flex;flex-direction:column">
      <div class="card-body" style="flex:1">
        <div style="display:flex;align-items:flex-start;gap:.85rem;margin-bottom:1rem">
          <div class="stat-icon <?= $typeColors[$s['type']] ?>">
            <span class="material-icons-round"><?= $typeIcons[$s['type']] ?></span>
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-weight:700;font-size:1rem;color:var(--text);margin-bottom:.2rem"><?= h($s['nom']) ?></div>
            <span class="badge badge-secondary"><?= $typeLabels[$s['type']] ?></span>
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;color:var(--text-muted)">
          <span class="material-icons-round" style="font-size:16px">group</span>
          Capacité : <strong style="color:var(--text-2)"><?= $s['capacite'] ?> places</strong>
        </div>
      </div>
      <div style="padding:.75rem 1.25rem;border-top:1px solid var(--border);display:flex;gap:.5rem;justify-content:flex-end">
        <button class="btn btn-sm btn-outline" onclick='editSalle(<?= json_encode($s, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>
          <span class="material-icons-round">edit</span> Modifier
        </button>
        <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $s['id'] ?>,'<?= h(addslashes($s['nom'])) ?>')">
          <span class="material-icons-round">delete</span>
        </button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Modal Ajout -->
<div class="modal-overlay" id="modalAdd">
  <div class="modal" style="max-width:440px">
    <div class="modal-header">
      <span class="modal-title">Nouvelle salle</span>
      <button class="modal-close" onclick="closeModal('modalAdd')"><span class="material-icons-round">close</span></button>
    </div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
      <input type="hidden" name="action" value="add">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Nom / Numéro de salle <span style="color:var(--danger)">*</span></label>
          <input type="text" name="nom" class="form-control" required placeholder="Ex : Salle 201, Amphi A…" maxlength="100">
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Capacité <span style="color:var(--danger)">*</span></label>
            <input type="number" name="capacite" class="form-control" value="30" min="1" max="500">
          </div>
          <div class="form-group">
            <label class="form-label">Type de salle</label>
            <select name="type" class="form-control">
              <option value="normale">Normale</option>
              <option value="informatique">Informatique</option>
              <option value="laboratoire">Laboratoire</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalAdd')">Annuler</button>
        <button type="submit" class="btn btn-primary"><span class="material-icons-round">save</span> Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Modification -->
<div class="modal-overlay" id="modalEdit">
  <div class="modal" style="max-width:440px">
    <div class="modal-header">
      <span class="modal-title">Modifier la salle</span>
      <button class="modal-close" onclick="closeModal('modalEdit')"><span class="material-icons-round">close</span></button>
    </div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="edit_id">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Nom / Numéro de salle <span style="color:var(--danger)">*</span></label>
          <input type="text" name="nom" id="edit_nom" class="form-control" required maxlength="100">
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Capacité</label>
            <input type="number" name="capacite" id="edit_cap" class="form-control" min="1" max="500">
          </div>
          <div class="form-group">
            <label class="form-label">Type de salle</label>
            <select name="type" id="edit_type" class="form-control">
              <option value="normale">Normale</option>
              <option value="informatique">Informatique</option>
              <option value="laboratoire">Laboratoire</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalEdit')">Annuler</button>
        <button type="submit" class="btn btn-primary"><span class="material-icons-round">save</span> Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Suppression -->
<div class="modal-overlay" id="modalDelete">
  <div class="modal" style="max-width:400px">
    <div class="modal-header">
      <span class="modal-title">Confirmer la suppression</span>
      <button class="modal-close" onclick="closeModal('modalDelete')"><span class="material-icons-round">close</span></button>
    </div>
    <div class="modal-body">
      <div style="display:flex;gap:1rem;align-items:flex-start">
        <div style="width:44px;height:44px;background:var(--danger-lt);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <span class="material-icons-round" style="color:var(--danger)">warning</span>
        </div>
        <div>
          <p style="font-weight:600;color:var(--text);margin-bottom:.35rem">Supprimer la salle ?</p>
          <p style="font-size:.875rem;color:var(--text-muted)">La salle <strong id="deleteLabel"></strong> sera définitivement supprimée. Cette action est irréversible.</p>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <form method="POST" style="display:flex;gap:.6rem">
        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete_id">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalDelete')">Annuler</button>
        <button type="submit" class="btn btn-danger"><span class="material-icons-round">delete</span> Supprimer</button>
      </form>
    </div>
  </div>
</div>

<script src="<?= APP_URL ?>/assets/js/main.js"></script>
<script>
function editSalle(s) {
  document.getElementById('edit_id').value  = s.id;
  document.getElementById('edit_nom').value = s.nom;
  document.getElementById('edit_cap').value = s.capacite;
  document.getElementById('edit_type').value = s.type;
  openModal('modalEdit');
}
function confirmDelete(id, nom) {
  document.getElementById('delete_id').value       = id;
  document.getElementById('deleteLabel').textContent = nom;
  openModal('modalDelete');
}
</script>
</body></html>
