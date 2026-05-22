<?php
// admin/classes.php — v3 avec niveaux
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
    $action   = $_POST['action']   ?? '';
    $nom      = sanitize($_POST['nom']      ?? '');
    $idNiveau = (int)($_POST['id_niveau']   ?? 0);
    $capacite = (int)($_POST['capacite']    ?? 30);

    if ($action === 'add') {
        if (!$nom) { $msg = getBusinessError('champs_vides'); $msgType='danger'; }
        else {
            $chk = $pdo->prepare('SELECT id FROM classes WHERE LOWER(nom)=LOWER(?) LIMIT 1');
            $chk->execute([$nom]);
            if ($chk->fetch()) { $msg = getBusinessError('classe_exists'); $msgType='danger'; }
            else {
                $pdo->prepare('INSERT INTO classes (nom,niveau,id_niveau,capacite) VALUES (?,?,?,?)')
                    ->execute([$nom,$nom,$idNiveau?:null,$capacite]);
                $msg = "Classe <strong>".h($nom)."</strong> ajoutée.";
            }
        }
    } elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $chk = $pdo->prepare('SELECT id FROM classes WHERE LOWER(nom)=LOWER(?) AND id!=? LIMIT 1');
        $chk->execute([$nom,$id]);
        if ($chk->fetch()) { $msg = getBusinessError('classe_exists'); $msgType='danger'; }
        else {
            $pdo->prepare('UPDATE classes SET nom=?,id_niveau=?,capacite=? WHERE id=?')
                ->execute([$nom,$idNiveau?:null,$capacite,$id]);
            $msg = "Classe <strong>".h($nom)."</strong> modifiée.";
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $r = $pdo->prepare('SELECT nom FROM classes WHERE id=?'); $r->execute([$id]);
        $nomC = $r->fetchColumn();
        $pdo->prepare('DELETE FROM classes WHERE id=?')->execute([$id]);
        $msg = "Classe <strong>".h($nomC)."</strong> supprimée."; $msgType='warning';
    }
}

$classes = getClassesWithNiveau();
$niveaux = getNiveaux();

$pageTitle = 'Gestion des Classes'; $activeMenu = 'classes';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_admin.php';
?>
<div class="main-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1 class="page-title"><span class="material-icons-round">class</span> Classes</h1>
      <p class="page-subtitle"><?= count($classes) ?> classe<?= count($classes)>1?'s':'' ?></p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modalAdd')">
      <span class="material-icons-round">add</span> Nouvelle classe
    </button>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msgType ?>">
    <span class="material-icons-round"><?= $msgType==='danger'?'error_outline':'check_circle' ?></span>
    <div class="alert-content"><?= $msg ?></div>
  </div>
  <?php endif; ?>

  <?php if (empty($niveaux)): ?>
  <div class="alert alert-warning">
    <span class="material-icons-round">info</span>
    <div class="alert-content">Aucun niveau configuré. <a href="<?= APP_URL ?>/admin/niveaux.php">Créez des niveaux</a> pour mieux organiser vos classes.</div>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="table-wrapper" style="border:none;border-radius:0">
      <table class="table">
        <thead>
          <tr><th>Classe</th><th>Niveau</th><th>Capacité</th><th style="text-align:right">Actions</th></tr>
        </thead>
        <tbody>
          <?php if (empty($classes)): ?>
          <tr><td colspan="4"><div class="empty-state"><div class="empty-state-icon"><span class="material-icons-round">class</span></div><h3>Aucune classe</h3></div></td></tr>
          <?php else: foreach ($classes as $c): ?>
          <tr>
            <td><strong><?= h($c['nom']) ?></strong></td>
            <td>
              <?php if ($c['niveau_nom']): ?>
              <span class="badge badge-indigo"><?= h($c['niveau_nom']) ?></span>
              <?php else: ?>
              <span style="font-size:.8rem;color:var(--text-light);font-style:italic">Non défini</span>
              <?php endif; ?>
            </td>
            <td><span style="color:var(--text-muted)"><?= $c['capacite'] ?> élèves</span></td>
            <td>
              <div class="table-actions" style="justify-content:flex-end">
                <button class="btn btn-sm btn-outline" onclick='editClasse(<?= json_encode($c,JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>
                  <span class="material-icons-round">edit</span>
                </button>
                <button class="btn btn-sm btn-danger" onclick="confirmDel(<?= $c['id'] ?>,'<?= h(addslashes($c['nom'])) ?>')">
                  <span class="material-icons-round">delete</span>
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Add -->
<div class="modal-overlay" id="modalAdd">
  <div class="modal" style="max-width:440px">
    <div class="modal-header"><span class="modal-title">Nouvelle classe</span>
      <button class="modal-close" onclick="closeModal('modalAdd')"><span class="material-icons-round">close</span></button></div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
      <input type="hidden" name="action" value="add">
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Nom de la classe *</label>
          <input type="text" name="nom" class="form-control" required placeholder="Ex : 3ème A, Terminale S…"></div>
        <div class="form-grid">
          <div class="form-group"><label class="form-label">Niveau scolaire</label>
            <select name="id_niveau" class="form-control">
              <option value="">— Sélectionner —</option>
              <?php foreach ($niveaux as $n): ?><option value="<?= $n['id'] ?>"><?= h($n['nom']) ?></option><?php endforeach; ?>
            </select></div>
          <div class="form-group"><label class="form-label">Capacité</label>
            <input type="number" name="capacite" class="form-control" value="30" min="1" max="200"></div>
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
    <div class="modal-header"><span class="modal-title">Modifier la classe</span>
      <button class="modal-close" onclick="closeModal('modalEdit')"><span class="material-icons-round">close</span></button></div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="e_id">
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Nom</label>
          <input type="text" name="nom" id="e_nom" class="form-control" required></div>
        <div class="form-grid">
          <div class="form-group"><label class="form-label">Niveau</label>
            <select name="id_niveau" id="e_niveau" class="form-control">
              <option value="">— Aucun —</option>
              <?php foreach ($niveaux as $n): ?><option value="<?= $n['id'] ?>"><?= h($n['nom']) ?></option><?php endforeach; ?>
            </select></div>
          <div class="form-group"><label class="form-label">Capacité</label>
            <input type="number" name="capacite" id="e_cap" class="form-control" min="1"></div>
        </div>
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
    <div class="modal-header"><span class="modal-title">Supprimer la classe</span>
      <button class="modal-close" onclick="closeModal('modalDelete')"><span class="material-icons-round">close</span></button></div>
    <div class="modal-body"><p style="font-size:.875rem;color:var(--text-muted)">Supprimer <strong id="del_label"></strong> ? Les données liées seront affectées.</p></div>
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

<style>.badge-indigo{background:#EEF2FF;color:#4F46E5}</style>
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
<script>
function editClasse(c){
  document.getElementById('e_id').value    = c.id;
  document.getElementById('e_nom').value   = c.nom;
  document.getElementById('e_cap').value   = c.capacite;
  document.getElementById('e_niveau').value = c.id_niveau_rel || '';
  openModal('modalEdit');
}
function confirmDel(id,nom){
  document.getElementById('del_id').value=id;
  document.getElementById('del_label').textContent=nom;
  openModal('modalDelete');
}
</script>
</body></html>
