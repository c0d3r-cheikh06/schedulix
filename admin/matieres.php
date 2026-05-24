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
        $nom         = sanitize($_POST['nom']               ?? '');
        $couleur     = preg_match('/^#[0-9A-Fa-f]{6}$/', $_POST['couleur_hex'] ?? '') ? $_POST['couleur_hex'] : '#1A56DB';
        $nbH         = (int)($_POST['nb_heures_semaine']    ?? 2);
        $description = sanitize($_POST['description']        ?? '');

        if (!$nom) {
            $msg = getBusinessError('champs_vides'); $msgType = 'danger';
        } else {
            $chk = $pdo->prepare('SELECT id FROM matieres WHERE LOWER(nom)=LOWER(?) LIMIT 1');
            $chk->execute([$nom]);
            if ($chk->fetch()) {
                $msg = getBusinessError('matiere_exists'); $msgType = 'danger';
            } else {
                $pdo->prepare('INSERT INTO matieres (nom,couleur_hex,nb_heures_semaine,description) VALUES (?,?,?,?)')->execute([$nom,$couleur,$nbH,$description]);
                $msg = "La matière <strong>" . h($nom) . "</strong> a été ajoutée.";
            }
        }
    } elseif ($action === 'edit') {
        $id      = (int)$_POST['id'];
        $nom     = sanitize($_POST['nom'] ?? '');
        $couleur = preg_match('/^#[0-9A-Fa-f]{6}$/', $_POST['couleur_hex'] ?? '') ? $_POST['couleur_hex'] : '#1A56DB';
        $nbH     = (int)($_POST['nb_heures_semaine'] ?? 2);
        $desc    = sanitize($_POST['description'] ?? '');

        $chk = $pdo->prepare('SELECT id FROM matieres WHERE LOWER(nom)=LOWER(?) AND id!=? LIMIT 1');
        $chk->execute([$nom, $id]);
        if ($chk->fetch()) {
            $msg = getBusinessError('matiere_exists'); $msgType = 'danger';
        } else {
            $pdo->prepare('UPDATE matieres SET nom=?,couleur_hex=?,nb_heures_semaine=?,description=? WHERE id=?')->execute([$nom,$couleur,$nbH,$desc,$id]);
            $msg = "La matière <strong>" . h($nom) . "</strong> a été modifiée.";
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $row = $pdo->prepare('SELECT nom FROM matieres WHERE id=?'); $row->execute([$id]);
        $nomMat = $row->fetchColumn();
        $pdo->prepare('DELETE FROM matieres WHERE id=?')->execute([$id]);
        $msg = "La matière <strong>" . h($nomMat) . "</strong> a été supprimée."; $msgType = 'warning';
    }
}

$matieres = getMatieres();

$pageTitle = 'Gestion des Matières'; $activeMenu = 'matieres';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_admin.php';
?>

<div class="main-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1 class="page-title"><span class="material-icons-round">menu_book</span> Matières</h1>
      <p class="page-subtitle"><?= count($matieres) ?> matière<?= count($matieres)>1?'s':'' ?> enregistrée<?= count($matieres)>1?'s':'' ?></p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modalAdd')">
      <span class="material-icons-round">add</span> Nouvelle matière
    </button>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msgType ?>" data-auto-dismiss="5000">
    <span class="material-icons-round"><?= $msgType==='danger'?'error_outline':'check_circle' ?></span>
    <div class="alert-content"><?= $msg ?></div>
  </div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1rem">
    <?php foreach ($matieres as $m): ?>
    <div class="card" style="display:flex;flex-direction:column">
      <div class="card-body" style="flex:1">
        <div style="display:flex;align-items:center;gap:.85rem;margin-bottom:.85rem">
          <div style="width:44px;height:44px;border-radius:var(--radius);background:<?= h($m['couleur_hex']) ?>22;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <span class="color-dot" style="width:16px;height:16px;background:<?= h($m['couleur_hex']) ?>"></span>
          </div>
          <div>
            <div style="font-weight:700;font-size:1rem;color:var(--text)"><?= h($m['nom']) ?></div>
            <div style="font-size:.78rem;color:var(--text-muted)"><?= $m['nb_heures_semaine'] ?>h / semaine</div>
          </div>
        </div>
        <?php if ($m['description']): ?>
          <p style="font-size:.8rem;color:var(--text-muted);line-height:1.5"><?= h($m['description']) ?></p>
        <?php endif; ?>
      </div>
      <div style="padding:.75rem 1.25rem;border-top:1px solid var(--border);display:flex;gap:.5rem;justify-content:flex-end">
        <button class="btn btn-sm btn-outline" onclick='editMat(<?= json_encode($m,JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>
          <span class="material-icons-round">edit</span> Modifier
        </button>
        <button class="btn btn-sm btn-danger" onclick="confirmDel(<?= $m['id'] ?>,'<?= h(addslashes($m['nom'])) ?>')">
          <span class="material-icons-round">delete</span>
        </button>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($matieres)): ?>
    <div class="card" style="grid-column:1/-1"><div class="card-body">
      <div class="empty-state"><div class="empty-state-icon"><span class="material-icons-round">menu_book</span></div><h3>Aucune matière</h3></div>
    </div></div>
    <?php endif; ?>
  </div>
</div>

<!-- Modal Add -->
<div class="modal-overlay" id="modalAdd">
  <div class="modal" style="max-width:460px">
    <div class="modal-header"><span class="modal-title">Nouvelle matière</span>
      <button class="modal-close" onclick="closeModal('modalAdd')"><span class="material-icons-round">close</span></button></div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
      <input type="hidden" name="action" value="add">
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Nom de la matière <span style="color:var(--danger)">*</span></label>
          <input type="text" name="nom" class="form-control" required placeholder="Ex : Mathématiques, Physique-Chimie…"></div>
        <div class="form-grid">
          <div class="form-group"><label class="form-label">Couleur de l'emploi du temps</label>
            <input type="color" name="couleur_hex" class="form-control" value="#1A56DB" style="height:42px;padding:.3rem;cursor:pointer"></div>
          <div class="form-group"><label class="form-label">Heures / semaine</label>
            <input type="number" name="nb_heures_semaine" class="form-control" value="2" min="1" max="20"></div>
        </div>
        <div class="form-group"><label class="form-label">Description (facultatif)</label>
          <textarea name="description" class="form-control" placeholder="Brève description du contenu…" rows="2"></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalAdd')">Annuler</button>
        <button type="submit" class="btn btn-primary"><span class="material-icons-round">save</span> Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit -->
<div class="modal-overlay" id="modalEdit">
  <div class="modal" style="max-width:460px">
    <div class="modal-header"><span class="modal-title">Modifier la matière</span>
      <button class="modal-close" onclick="closeModal('modalEdit')"><span class="material-icons-round">close</span></button></div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="e_id">
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Nom</label>
          <input type="text" name="nom" id="e_nom" class="form-control" required></div>
        <div class="form-grid">
          <div class="form-group"><label class="form-label">Couleur</label>
            <input type="color" name="couleur_hex" id="e_couleur" class="form-control" style="height:42px;padding:.3rem;cursor:pointer"></div>
          <div class="form-group"><label class="form-label">Heures / semaine</label>
            <input type="number" name="nb_heures_semaine" id="e_nbh" class="form-control" min="1" max="20"></div>
        </div>
        <div class="form-group"><label class="form-label">Description</label>
          <textarea name="description" id="e_desc" class="form-control" rows="2"></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalEdit')">Annuler</button>
        <button type="submit" class="btn btn-primary"><span class="material-icons-round">save</span> Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Delete -->
<div class="modal-overlay" id="modalDelete">
  <div class="modal" style="max-width:380px">
    <div class="modal-header"><span class="modal-title">Supprimer la matière</span>
      <button class="modal-close" onclick="closeModal('modalDelete')"><span class="material-icons-round">close</span></button></div>
    <div class="modal-body"><p style="color:var(--text-muted);font-size:.875rem">Supprimer <strong id="del_label"></strong> ? Les créneaux associés dans les EDT seront supprimés.</p></div>
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
function editMat(m){
  document.getElementById('e_id').value=m.id;
  document.getElementById('e_nom').value=m.nom;
  document.getElementById('e_couleur').value=m.couleur_hex;
  document.getElementById('e_nbh').value=m.nb_heures_semaine;
  document.getElementById('e_desc').value=m.description||'';
  openModal('modalEdit');
}
function confirmDel(id,nom){
  document.getElementById('del_id').value=id;
  document.getElementById('del_label').textContent=nom;
  openModal('modalDelete');
}
</script>
</body></html>
