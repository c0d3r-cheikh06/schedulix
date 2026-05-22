<?php
// admin/niveaux.php — CRUD Niveaux scolaires
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
        $nom   = sanitize($_POST['nom']  ?? '');
        $ordre = (int)($_POST['ordre']   ?? 0);
        $desc  = sanitize($_POST['description'] ?? '');
        if (!$nom) { $msg = getBusinessError('champs_vides'); $msgType = 'danger'; }
        else {
            $chk = $pdo->prepare('SELECT id FROM niveaux WHERE LOWER(nom)=LOWER(?) LIMIT 1');
            $chk->execute([$nom]);
            if ($chk->fetch()) { $msg = 'Ce niveau existe déjà.'; $msgType = 'danger'; }
            else {
                $pdo->prepare('INSERT INTO niveaux (nom,ordre,description) VALUES (?,?,?)')->execute([$nom,$ordre,$desc]);
                $msg = "Niveau <strong>".h($nom)."</strong> ajouté.";
            }
        }
    } elseif ($action === 'edit') {
        $id    = (int)$_POST['id'];
        $nom   = sanitize($_POST['nom']  ?? '');
        $ordre = (int)($_POST['ordre']   ?? 0);
        $desc  = sanitize($_POST['description'] ?? '');
        $chk   = $pdo->prepare('SELECT id FROM niveaux WHERE LOWER(nom)=LOWER(?) AND id!=? LIMIT 1');
        $chk->execute([$nom, $id]);
        if ($chk->fetch()) { $msg = 'Ce nom de niveau est déjà utilisé.'; $msgType = 'danger'; }
        else {
            $pdo->prepare('UPDATE niveaux SET nom=?,ordre=?,description=? WHERE id=?')->execute([$nom,$ordre,$desc,$id]);
            $msg = "Niveau <strong>".h($nom)."</strong> modifié.";
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        // Vérifier si des classes utilisent ce niveau
        $chk = $pdo->prepare('SELECT COUNT(*) FROM classes WHERE id_niveau=?');
        $chk->execute([$id]);
        if ((int)$chk->fetchColumn() > 0) { $msg = 'Ce niveau est utilisé par des classes. Réaffectez-les d\'abord.'; $msgType = 'danger'; }
        else {
            $pdo->prepare('DELETE FROM niveaux WHERE id=?')->execute([$id]);
            $msg = 'Niveau supprimé.'; $msgType = 'warning';
        }
    }
}

$niveaux = getNiveaux();
// Compter classes par niveau
$countClasses = [];
foreach ($pdo->query('SELECT id_niveau, COUNT(*) as nb FROM classes GROUP BY id_niveau')->fetchAll() as $r) {
    $countClasses[$r['id_niveau']] = $r['nb'];
}
// Compter profs par niveau
$countProfs = [];
foreach ($pdo->query('SELECT id_niveau, COUNT(*) as nb FROM professeur_niveau GROUP BY id_niveau')->fetchAll() as $r) {
    $countProfs[$r['id_niveau']] = $r['nb'];
}

$pageTitle = 'Niveaux scolaires'; $activeMenu = 'niveaux';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_admin.php';
?>
<div class="main-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1 class="page-title"><span class="material-icons-round">school</span> Niveaux scolaires</h1>
      <p class="page-subtitle"><?= count($niveaux) ?> niveau<?= count($niveaux)>1?'x':'' ?> configuré<?= count($niveaux)>1?'s':'' ?></p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modalAdd')">
      <span class="material-icons-round">add</span> Nouveau niveau
    </button>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msgType ?>">
    <span class="material-icons-round"><?= $msgType==='danger'?'error_outline':'check_circle' ?></span>
    <div class="alert-content"><?= $msg ?></div>
  </div>
  <?php endif; ?>

  <div class="alert alert-info">
    <span class="material-icons-round">info</span>
    <div class="alert-content">
      Les niveaux scolaires permettent de structurer les classes et de définir les autorisations d'enseignement des professeurs.
      Un professeur ne sera affecté qu'aux classes dont le niveau lui est attribué.
    </div>
  </div>

  <?php if (empty($niveaux)): ?>
  <div class="card"><div class="card-body">
    <div class="empty-state">
      <div class="empty-state-icon"><span class="material-icons-round">school</span></div>
      <h3>Aucun niveau configuré</h3>
      <p>Créez des niveaux scolaires pour organiser vos classes et définir les habilitations des professeurs.</p>
      <button class="btn btn-primary" onclick="openModal('modalAdd')" style="margin-top:1rem">
        <span class="material-icons-round">add</span> Créer le premier niveau
      </button>
    </div>
  </div></div>
  <?php else: ?>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem">
    <?php foreach ($niveaux as $n): ?>
    <div class="card" style="display:flex;flex-direction:column">
      <div class="card-body" style="flex:1">
        <div style="display:flex;align-items:flex-start;gap:.85rem;margin-bottom:.85rem">
          <div class="stat-icon indigo" style="flex-shrink:0">
            <span class="material-icons-round">school</span>
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-weight:700;font-size:1.05rem;color:var(--text)"><?= h($n['nom']) ?></div>
            <div style="font-size:.78rem;color:var(--text-muted)">Ordre : <?= $n['ordre'] ?></div>
          </div>
          <span class="badge badge-secondary">#<?= $n['ordre'] ?></span>
        </div>
        <?php if ($n['description']): ?>
        <p style="font-size:.82rem;color:var(--text-muted);margin-bottom:.85rem"><?= h($n['description']) ?></p>
        <?php endif; ?>
        <div style="display:flex;gap:1rem">
          <div style="text-align:center;flex:1;padding:.6rem;background:var(--bg);border-radius:var(--radius-sm)">
            <div style="font-size:1.25rem;font-weight:700;color:var(--primary)"><?= $countClasses[$n['id']] ?? 0 ?></div>
            <div style="font-size:.72rem;color:var(--text-muted)">Classe<?= ($countClasses[$n['id']] ?? 0)>1?'s':'' ?></div>
          </div>
          <div style="text-align:center;flex:1;padding:.6rem;background:var(--bg);border-radius:var(--radius-sm)">
            <div style="font-size:1.25rem;font-weight:700;color:var(--success)"><?= $countProfs[$n['id']] ?? 0 ?></div>
            <div style="font-size:.72rem;color:var(--text-muted)">Prof<?= ($countProfs[$n['id']] ?? 0)>1?'s':'' ?></div>
          </div>
        </div>
      </div>
      <div style="padding:.75rem 1.25rem;border-top:1px solid var(--border);display:flex;gap:.5rem;justify-content:flex-end">
        <button class="btn btn-sm btn-outline" onclick='editNiveau(<?= json_encode($n,JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>
          <span class="material-icons-round">edit</span> Modifier
        </button>
        <button class="btn btn-sm btn-danger" onclick="confirmDel(<?= $n['id'] ?>,'<?= h(addslashes($n['nom'])) ?>')">
          <span class="material-icons-round">delete</span>
        </button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Modal Add -->
<div class="modal-overlay" id="modalAdd">
  <div class="modal" style="max-width:440px">
    <div class="modal-header"><span class="modal-title">Nouveau niveau scolaire</span>
      <button class="modal-close" onclick="closeModal('modalAdd')"><span class="material-icons-round">close</span></button></div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
      <input type="hidden" name="action" value="add">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Nom du niveau <span style="color:var(--danger)">*</span></label>
          <input type="text" name="nom" class="form-control" required placeholder="Ex : Troisième, Seconde…">
        </div>
        <div class="form-group">
          <label class="form-label">Ordre d'affichage</label>
          <input type="number" name="ordre" class="form-control" value="<?= count($niveaux)+1 ?>" min="0" max="99">
          <div class="form-hint">Détermine l'ordre d'affichage dans les listes (1 = premier).</div>
        </div>
        <div class="form-group">
          <label class="form-label">Description (facultatif)</label>
          <input type="text" name="description" class="form-control" placeholder="Ex : Cycle d'orientation…">
        </div>
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
  <div class="modal" style="max-width:440px">
    <div class="modal-header"><span class="modal-title">Modifier le niveau</span>
      <button class="modal-close" onclick="closeModal('modalEdit')"><span class="material-icons-round">close</span></button></div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="e_id">
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Nom</label>
          <input type="text" name="nom" id="e_nom" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Ordre</label>
          <input type="number" name="ordre" id="e_ordre" class="form-control" min="0" max="99"></div>
        <div class="form-group"><label class="form-label">Description</label>
          <input type="text" name="description" id="e_desc" class="form-control"></div>
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
    <div class="modal-header"><span class="modal-title">Supprimer le niveau</span>
      <button class="modal-close" onclick="closeModal('modalDelete')"><span class="material-icons-round">close</span></button></div>
    <div class="modal-body">
      <p style="font-size:.875rem;color:var(--text-muted)">Supprimer le niveau <strong id="del_label"></strong> ? Les classes associées seront déliées (non supprimées).</p>
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
function editNiveau(n) {
  document.getElementById('e_id').value    = n.id;
  document.getElementById('e_nom').value   = n.nom;
  document.getElementById('e_ordre').value = n.ordre;
  document.getElementById('e_desc').value  = n.description || '';
  openModal('modalEdit');
}
function confirmDel(id, nom) {
  document.getElementById('del_id').value        = id;
  document.getElementById('del_label').textContent = nom;
  openModal('modalDelete');
}
</script>
</body></html>
