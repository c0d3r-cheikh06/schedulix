<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();
$currentUser = getCurrentUser();
$pdo = getDB();
$msg = ''; $msgType = 'success';
$tempPassword = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $nom    = sanitize($_POST['nom']    ?? '');
        $prenom = sanitize($_POST['prenom'] ?? '');
        $email  = sanitize($_POST['email']  ?? '');
        $mats   = array_map('intval', $_POST['matieres'] ?? []);
        $niveaux= array_map('intval', $_POST['niveaux']  ?? []);

        if (!$nom || !$prenom || !$email) { $msg = getBusinessError('champs_vides'); $msgType='danger'; }
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $msg = getBusinessError('format_email'); $msgType='danger'; }
        else {
            $chk = $pdo->prepare('SELECT id FROM utilisateurs WHERE LOWER(email)=LOWER(?) LIMIT 1');
            $chk->execute([$email]);
            if ($chk->fetch()) { $msg = getBusinessError('prof_exists'); $msgType='danger'; }
            else {
                $tempPassword = generateTempPassword();
                $hash = password_hash($tempPassword, PASSWORD_BCRYPT);
                $pdo->prepare("INSERT INTO utilisateurs (nom,prenom,email,mot_de_passe,role,statut) VALUES (?,?,?,?,'professeur','actif')")
                    ->execute([$nom,$prenom,$email,$hash]);
                $idProf = (int)$pdo->lastInsertId();
                foreach ($mats    as $idM) { $pdo->prepare('INSERT IGNORE INTO professeur_matiere (id_professeur,id_matiere) VALUES (?,?)')->execute([$idProf,$idM]); }
                foreach ($niveaux as $idN) { $pdo->prepare('INSERT IGNORE INTO professeur_niveau  (id_professeur,id_niveau)  VALUES (?,?)')->execute([$idProf,$idN]); }
                sendNotification($idProf,'Bienvenue !','Votre compte professeur a été créé.','info');
                $msg = "Professeur <strong>".h($prenom.' '.$nom)."</strong> créé. Mot de passe temporaire : <code style='background:var(--bg);padding:.1rem .4rem;border-radius:4px'>".h($tempPassword)."</code>";
            }
        }
    } elseif ($action === 'edit') {
        $id      = (int)$_POST['id'];
        $nom     = sanitize($_POST['nom']    ?? '');
        $pren    = sanitize($_POST['prenom'] ?? '');
        $stat    = in_array($_POST['statut']??'',['actif','inactif']) ? $_POST['statut'] : 'actif';
        $mats    = array_map('intval', $_POST['matieres'] ?? []);
        $niveaux = array_map('intval', $_POST['niveaux']  ?? []);
        $pdo->prepare('UPDATE utilisateurs SET nom=?,prenom=?,statut=? WHERE id=?')->execute([$nom,$pren,$stat,$id]);
        $pdo->prepare('DELETE FROM professeur_matiere WHERE id_professeur=?')->execute([$id]);
        $pdo->prepare('DELETE FROM professeur_niveau  WHERE id_professeur=?')->execute([$id]);
        foreach ($mats    as $idM) { $pdo->prepare('INSERT IGNORE INTO professeur_matiere (id_professeur,id_matiere) VALUES (?,?)')->execute([$id,$idM]); }
        foreach ($niveaux as $idN) { $pdo->prepare('INSERT IGNORE INTO professeur_niveau  (id_professeur,id_niveau)  VALUES (?,?)')->execute([$id,$idN]); }
        $msg = "Profil de <strong>".h($pren.' '.$nom)."</strong> mis à jour.";
    } elseif ($action === 'reset_pwd') {
        $id = (int)$_POST['id'];
        $tempPassword = generateTempPassword();
        $pdo->prepare('UPDATE utilisateurs SET mot_de_passe=? WHERE id=?')->execute([password_hash($tempPassword,PASSWORD_BCRYPT),$id]);
        $msg = "Nouveau mot de passe : <code style='background:var(--bg);padding:.1rem .4rem;border-radius:4px'>".h($tempPassword)."</code>"; $msgType='warning';
    } elseif ($action === 'delete') {
        $pdo->prepare("UPDATE utilisateurs SET statut='inactif' WHERE id=?")->execute([(int)$_POST['id']]);
        $msg = 'Professeur désactivé.'; $msgType='warning';
    }
}

$professeurs = $pdo->query("
    SELECT u.*,
           GROUP_CONCAT(DISTINCT m.nom ORDER BY m.nom SEPARATOR ', ') AS matieres_noms,
           GROUP_CONCAT(DISTINCT pm.id_matiere) AS mat_ids,
           GROUP_CONCAT(DISTINCT n.nom  ORDER BY n.ordre SEPARATOR ', ') AS niveaux_noms,
           GROUP_CONCAT(DISTINCT pn.id_niveau) AS niv_ids
    FROM utilisateurs u
    LEFT JOIN professeur_matiere pm ON pm.id_professeur=u.id
    LEFT JOIN matieres m ON m.id=pm.id_matiere
    LEFT JOIN professeur_niveau  pn ON pn.id_professeur=u.id
    LEFT JOIN niveaux n ON n.id=pn.id_niveau
    WHERE u.role='professeur'
    GROUP BY u.id ORDER BY u.statut,u.nom,u.prenom
")->fetchAll();

$matieres = getMatieres();
$niveaux  = getNiveaux();

$pageTitle = 'Gestion des Professeurs'; $activeMenu = 'professeurs';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_admin.php';
?>

<div class="main-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1 class="page-title"><span class="material-icons-round">person</span> Professeurs</h1>
      <p class="page-subtitle"><?= count($professeurs) ?> professeur<?= count($professeurs)>1?'s':'' ?></p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modalAdd')">
      <span class="material-icons-round">person_add</span> Nouveau professeur
    </button>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msgType ?>">
    <span class="material-icons-round"><?= $msgType==='danger'?'error_outline':'check_circle' ?></span>
    <div class="alert-content"><?= $msg ?></div>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="table-wrapper" style="border:none;border-radius:0">
      <table class="table">
        <thead>
          <tr><th>Professeur</th><th>Matières</th><th>Niveaux autorisés</th><th>Statut</th><th style="text-align:right">Actions</th></tr>
        </thead>
        <tbody>
          <?php if (empty($professeurs)): ?>
          <tr><td colspan="5"><div class="empty-state">
            <div class="empty-state-icon"><span class="material-icons-round">person_off</span></div>
            <h3>Aucun professeur</h3>
          </div></td></tr>
          <?php else: foreach ($professeurs as $p): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:.75rem">
                <div class="user-avatar" style="width:36px;height:36px;font-size:.8rem;flex-shrink:0">
                  <?= strtoupper(substr($p['prenom'],0,1).substr($p['nom'],0,1)) ?>
                </div>
                <div>
                  <div style="font-weight:600"><?= h($p['prenom'].' '.$p['nom']) ?></div>
                  <div style="font-size:.75rem;color:var(--text-muted)"><?= h($p['email']) ?></div>
                </div>
              </div>
            </td>
            <td style="font-size:.82rem;color:var(--text-muted)">
              <?= $p['matieres_noms'] ? h($p['matieres_noms']) : '<span style="font-style:italic">Aucune</span>' ?>
            </td>
            <td>
              <?php if ($p['niveaux_noms']): ?>
                <?php foreach (explode(', ', $p['niveaux_noms']) as $niv): ?>
                <span class="badge badge-secondary" style="margin:.1rem .15rem"><?= h($niv) ?></span>
                <?php endforeach; ?>
              <?php else: ?>
                <span class="badge badge-warning">Tous niveaux</span>
              <?php endif; ?>
            </td>
            <td>
              <?= $p['statut']==='actif'
                ? '<span class="badge badge-success"><span class="material-icons-round" style="font-size:10px">circle</span> Actif</span>'
                : '<span class="badge badge-danger"><span class="material-icons-round" style="font-size:10px">circle</span> Inactif</span>' ?>
            </td>
            <td>
              <div class="table-actions" style="justify-content:flex-end">
                <button class="btn btn-sm btn-outline" onclick='editProf(<?= json_encode($p,JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>
                  <span class="material-icons-round">edit</span>
                </button>
                <button class="btn btn-sm btn-outline" onclick="resetPwd(<?= $p['id'] ?>,'<?= h(addslashes($p['prenom'].' '.$p['nom'])) ?>')">
                  <span class="material-icons-round">lock_reset</span>
                </button>
                <?php if ($p['statut']==='actif'): ?>
                <button class="btn btn-sm btn-danger" onclick="deactivate(<?= $p['id'] ?>,'<?= h(addslashes($p['prenom'].' '.$p['nom'])) ?>')">
                  <span class="material-icons-round">block</span>
                </button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php
// ── Fonction helper pour les checkboxes matières/niveaux ──
function renderCheckboxes(array $items, string $name, string $idField, string $labelField, array $selected = [], string $colorField = ''): string {
    $html = '<div style="display:flex;flex-wrap:wrap;gap:.4rem;padding:.65rem;border:1.5px solid var(--border);border-radius:var(--radius-sm);background:#fff">';
    foreach ($items as $item) {
        $checked = in_array($item[$idField], $selected) ? 'checked' : '';
        $color   = $colorField && $item[$colorField] ? "background:{$item[$colorField]}22;border-color:{$item[$colorField]}" : '';
        $html   .= '<label style="display:flex;align-items:center;gap:.35rem;cursor:pointer;font-size:.82rem;padding:.25rem .6rem;border-radius:99px;border:1px solid var(--border);'.$color.'">';
        $html   .= '<input type="checkbox" name="'.$name.'[]" value="'.(int)$item[$idField].'" '.($checked?'checked':'').' style="width:13px;height:13px;accent-color:var(--primary)">';
        if ($colorField && $item[$colorField]) $html .= '<span style="width:8px;height:8px;border-radius:50%;background:'.$item[$colorField].';flex-shrink:0"></span>';
        $html   .= htmlspecialchars($item[$labelField], ENT_QUOTES).'</label>';
    }
    return $html.'</div>';
}
?>

<!-- Modal Add -->
<div class="modal-overlay" id="modalAdd">
  <div class="modal" style="max-width:520px">
    <div class="modal-header"><span class="modal-title">Nouveau professeur</span>
      <button class="modal-close" onclick="closeModal('modalAdd')"><span class="material-icons-round">close</span></button></div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
      <input type="hidden" name="action" value="add">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group"><label class="form-label">Prénom *</label><input type="text" name="prenom" class="form-control" required></div>
          <div class="form-group"><label class="form-label">Nom *</label><input type="text" name="nom" class="form-control" required></div>
        </div>
        <div class="form-group"><label class="form-label">E-mail *</label><input type="email" name="email" class="form-control" required></div>
        <div class="form-group">
          <label class="form-label">Matières enseignées</label>
          <?= renderCheckboxes($matieres,'matieres','id','nom',[],'couleur_hex') ?>
        </div>
        <div class="form-group">
          <label class="form-label">Niveaux autorisés <span style="color:var(--text-muted);font-weight:400">(laisser vide = tous)</span></label>
          <?= renderCheckboxes($niveaux,'niveaux','id','nom') ?>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalAdd')">Annuler</button>
        <button type="submit" class="btn btn-primary"><span class="material-icons-round">person_add</span> Créer</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit -->
<div class="modal-overlay" id="modalEdit">
  <div class="modal" style="max-width:520px">
    <div class="modal-header"><span class="modal-title">Modifier le professeur</span>
      <button class="modal-close" onclick="closeModal('modalEdit')"><span class="material-icons-round">close</span></button></div>
    <form method="POST" id="editForm">
      <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="edit_id">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group"><label class="form-label">Prénom</label><input type="text" name="prenom" id="edit_prenom" class="form-control" required></div>
          <div class="form-group"><label class="form-label">Nom</label><input type="text" name="nom" id="edit_nom" class="form-control" required></div>
        </div>
        <div class="form-group"><label class="form-label">Statut</label>
          <select name="statut" id="edit_statut" class="form-control">
            <option value="actif">Actif</option><option value="inactif">Inactif</option>
          </select></div>
        <div class="form-group">
          <label class="form-label">Matières enseignées</label>
          <div id="edit_matieres_wrap">
            <?= renderCheckboxes($matieres,'matieres','id','nom',[],'couleur_hex') ?>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Niveaux autorisés</label>
          <div id="edit_niveaux_wrap">
            <?= renderCheckboxes($niveaux,'niveaux','id','nom') ?>
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

<!-- Modal Reset Pwd -->
<div class="modal-overlay" id="modalReset">
  <div class="modal" style="max-width:400px">
    <div class="modal-header"><span class="modal-title">Réinitialiser le mot de passe</span>
      <button class="modal-close" onclick="closeModal('modalReset')"><span class="material-icons-round">close</span></button></div>
    <div class="modal-body"><p style="font-size:.875rem;color:var(--text-muted)">Un mot de passe temporaire sera généré pour <strong id="reset_name"></strong>.</p></div>
    <div class="modal-footer">
      <form method="POST" style="display:flex;gap:.6rem">
        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
        <input type="hidden" name="action" value="reset_pwd">
        <input type="hidden" name="id" id="reset_id">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalReset')">Annuler</button>
        <button type="submit" class="btn btn-warning" style="background:var(--warning);color:#fff;border:none"><span class="material-icons-round">lock_reset</span> Réinitialiser</button>
      </form>
    </div>
  </div>
</div>

<!-- Modal Désactivation -->
<div class="modal-overlay" id="modalDeact">
  <div class="modal" style="max-width:400px">
    <div class="modal-header"><span class="modal-title">Désactiver le professeur</span>
      <button class="modal-close" onclick="closeModal('modalDeact')"><span class="material-icons-round">close</span></button></div>
    <div class="modal-body"><p style="font-size:.875rem;color:var(--text-muted)"><strong id="deact_name"></strong> ne pourra plus se connecter.</p></div>
    <div class="modal-footer">
      <form method="POST" style="display:flex;gap:.6rem">
        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deact_id">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalDeact')">Annuler</button>
        <button type="submit" class="btn btn-danger"><span class="material-icons-round">block</span> Désactiver</button>
      </form>
    </div>
  </div>
</div>

<script src="<?= APP_URL ?>/assets/js/main.js"></script>
<script>
function editProf(p) {
  document.getElementById('edit_id').value     = p.id;
  document.getElementById('edit_nom').value    = p.nom;
  document.getElementById('edit_prenom').value = p.prenom;
  document.getElementById('edit_statut').value = p.statut;
  const matIds  = (p.mat_ids||'').split(',').map(Number);
  const nivIds  = (p.niv_ids||'').split(',').map(Number);
  document.querySelectorAll('#edit_matieres_wrap input[type=checkbox]').forEach(cb => {
    cb.checked = matIds.includes(parseInt(cb.value));
  });
  document.querySelectorAll('#edit_niveaux_wrap input[type=checkbox]').forEach(cb => {
    cb.checked = nivIds.includes(parseInt(cb.value));
  });
  openModal('modalEdit');
}
function resetPwd(id,name){
  document.getElementById('reset_id').value=id;
  document.getElementById('reset_name').textContent=name;
  openModal('modalReset');
}
function deactivate(id,name){
  document.getElementById('deact_id').value=id;
  document.getElementById('deact_name').textContent=name;
  openModal('modalDeact');
}
</script>
</body></html>
