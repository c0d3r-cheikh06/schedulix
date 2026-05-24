<?php
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
    $action  = $_POST['action'] ?? '';
    $id      = (int)($_POST['id'] ?? 0);
    $comment = sanitize($_POST['commentaire'] ?? '');

    // Vérifier que le créneau appartient bien à ce professeur
    $chk = $pdo->prepare('SELECT id FROM emplois_du_temps WHERE id=? AND id_professeur=? LIMIT 1');
    $chk->execute([$id, $currentUser['id']]);
    if (!$chk->fetch()) {
        $msg = 'Action non autorisée.'; $msgType = 'danger';
    } elseif ($action === 'valider') {
        $pdo->prepare("UPDATE emplois_du_temps SET statut='valide', commentaire_validation=? WHERE id=?")
            ->execute([$comment, $id]);
        $msg = 'Créneau validé avec succès.';
    } elseif ($action === 'rejeter') {
        if (empty($comment)) {
            $msg = 'Un commentaire est requis pour rejeter un créneau.'; $msgType = 'danger';
        } else {
            $pdo->prepare("UPDATE emplois_du_temps SET statut='rejete', commentaire_validation=? WHERE id=?")
                ->execute([$comment, $id]);
            $msg = 'Créneau rejeté. L\'administrateur a été notifié.'; $msgType = 'warning';
            // Notifier l'admin
            $admins = $pdo->query("SELECT id FROM utilisateurs WHERE role='admin'")->fetchAll();
            foreach ($admins as $a) {
                sendNotification($a['id'],
                    'Créneau rejeté',
                    $currentUser['prenom'].' '.$currentUser['nom']." a rejeté un créneau : {$comment}",
                    'warning');
            }
        }
    } elseif ($action === 'valider_tout') {
        $version = getCurrentVersion();
        $pdo->prepare("UPDATE emplois_du_temps SET statut='valide', commentaire_validation='Validé en masse' WHERE id_professeur=? AND version=? AND statut='provisoire'")
            ->execute([$currentUser['id'], $version]);
        $msg = 'Tous les créneaux en attente ont été validés.';
    }
}

$version = getCurrentVersion();
$stmt = $pdo->prepare("
    SELECT e.*, m.nom AS mat_nom, m.couleur_hex,
           cl.nom AS classe_nom, s.nom AS salle_nom,
           c.jour, c.heure_debut, c.heure_fin
    FROM emplois_du_temps e
    JOIN matieres m  ON m.id=e.id_matiere
    JOIN classes cl  ON cl.id=e.id_classe
    JOIN salles s    ON s.id=e.id_salle
    JOIN creneaux c  ON c.id=e.id_creneau
    WHERE e.version=? AND e.id_professeur=?
    ORDER BY FIELD(e.statut,'provisoire','rejete','valide'), c.jour, c.heure_debut
");
$stmt->execute([$version, $currentUser['id']]);
$cours = $stmt->fetchAll();

$enAttente = array_filter($cours, fn($c)=>$c['statut']==='provisoire');

$pageTitle = 'Validation des créneaux'; $activeMenu = 'valider';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_prof.php';
?>

<div class="main-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1 class="page-title"><span class="material-icons-round">fact_check</span> Valider mes créneaux</h1>
      <p class="page-subtitle">Version <?= $version ?> — <?= count($enAttente) ?> en attente</p>
    </div>
    <?php if (!empty($enAttente)): ?>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
      <input type="hidden" name="action" value="valider_tout">
      <button type="submit" class="btn btn-success">
        <span class="material-icons-round">done_all</span> Tout valider
      </button>
    </form>
    <?php endif; ?>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msgType ?>" data-auto-dismiss="5000">
    <span class="material-icons-round"><?= $msgType==='danger'?'error_outline':($msgType==='warning'?'warning':'check_circle') ?></span>
    <div class="alert-content"><?= h($msg) ?></div>
  </div>
  <?php endif; ?>

  <?php if (empty($cours)): ?>
  <div class="card"><div class="card-body">
    <div class="empty-state">
      <div class="empty-state-icon"><span class="material-icons-round">fact_check</span></div>
      <h3>Aucun créneau à valider</h3>
      <p>Vous n'avez aucun créneau assigné pour la version actuelle.</p>
    </div>
  </div></div>
  <?php else: ?>

  <div style="display:flex;flex-direction:column;gap:.85rem">
    <?php foreach ($cours as $c): ?>
    <div class="card" style="<?= $c['statut']==='provisoire' ? 'border-left:4px solid var(--warning)' : ($c['statut']==='valide'?'border-left:4px solid var(--success)':'border-left:4px solid var(--danger)') ?>">
      <div class="card-body">
        <div style="display:flex;align-items:flex-start;gap:1rem;flex-wrap:wrap">
          <div style="width:4px;height:auto;align-self:stretch;flex-shrink:0"></div>
          <div style="flex:1;min-width:200px">
            <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.5rem">
              <span class="color-dot" style="width:12px;height:12px;background:<?= h($c['couleur_hex']) ?>"></span>
              <strong style="font-size:1rem;color:var(--text)"><?= h($c['mat_nom']) ?></strong>
              <?= getStatusBadge($c['statut']) ?>
            </div>
            <div style="display:flex;gap:1.5rem;flex-wrap:wrap">
              <span style="font-size:.875rem;color:var(--text-muted);display:flex;align-items:center;gap:.3rem">
                <span class="material-icons-round" style="font-size:15px">class</span><?= h($c['classe_nom']) ?>
              </span>
              <span style="font-size:.875rem;color:var(--text-muted);display:flex;align-items:center;gap:.3rem">
                <span class="material-icons-round" style="font-size:15px">schedule</span><?= h($c['jour']) ?>, <?= formatHeure($c['heure_debut']) ?>–<?= formatHeure($c['heure_fin']) ?>
              </span>
              <span style="font-size:.875rem;color:var(--text-muted);display:flex;align-items:center;gap:.3rem">
                <span class="material-icons-round" style="font-size:15px">meeting_room</span><?= h($c['salle_nom']) ?>
              </span>
            </div>
            <?php if ($c['commentaire_validation']): ?>
            <div style="margin-top:.5rem;font-size:.8rem;color:var(--text-muted);font-style:italic">
              Commentaire : <?= h($c['commentaire_validation']) ?>
            </div>
            <?php endif; ?>
          </div>

          <?php if ($c['statut'] === 'provisoire'): ?>
          <div style="display:flex;gap:.5rem;align-items:center;flex-shrink:0">
            <button class="btn btn-success btn-sm" onclick="openValidModal(<?= $c['id'] ?>,'valider')">
              <span class="material-icons-round">check</span> Valider
            </button>
            <button class="btn btn-danger btn-sm" onclick="openValidModal(<?= $c['id'] ?>,'rejeter')">
              <span class="material-icons-round">close</span> Rejeter
            </button>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Modal validation -->
<div class="modal-overlay" id="modalValid">
  <div class="modal" style="max-width:440px">
    <div class="modal-header">
      <span class="modal-title" id="validModalTitle">Valider le créneau</span>
      <button class="modal-close" onclick="closeModal('modalValid')"><span class="material-icons-round">close</span></button>
    </div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
      <input type="hidden" name="action" id="valid_action">
      <input type="hidden" name="id" id="valid_id">
      <div class="modal-body">
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Commentaire <span id="commentRequired" style="color:var(--danger);display:none">*</span></label>
          <textarea name="commentaire" id="commentaire" class="form-control" rows="3"
                    placeholder="Facultatif pour une validation, requis pour un rejet…"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalValid')">Annuler</button>
        <button type="submit" id="validSubmitBtn" class="btn btn-success">
          <span class="material-icons-round">check</span> Confirmer
        </button>
      </div>
    </form>
  </div>
</div>

<script src="<?= APP_URL ?>/assets/js/main.js"></script>
<script>
function openValidModal(id, action) {
  document.getElementById('valid_id').value     = id;
  document.getElementById('valid_action').value = action;
  const isRejet = action === 'rejeter';
  document.getElementById('validModalTitle').textContent = isRejet ? 'Rejeter le créneau' : 'Valider le créneau';
  document.getElementById('commentRequired').style.display = isRejet ? 'inline' : 'none';
  const btn = document.getElementById('validSubmitBtn');
  btn.className = isRejet ? 'btn btn-danger' : 'btn btn-success';
  btn.innerHTML = isRejet
    ? '<span class="material-icons-round">close</span> Rejeter'
    : '<span class="material-icons-round">check</span> Valider';
  openModal('modalValid');
}
</script>
</body></html>
