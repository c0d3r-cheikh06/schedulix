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
    $action   = $_POST['action'] ?? '';

    if ($action === 'send') {
        $idClasse = (int)($_POST['id_classe'] ?? 0);
        $sujet    = sanitize($_POST['sujet']   ?? '');
        $contenu  = sanitize($_POST['contenu'] ?? '');
        $type     = in_array($_POST['type']??'',['info','absence','devoir','report','autre']) ? $_POST['type'] : 'info';

        if (!$idClasse || !$sujet || !$contenu) {
            $msg = getBusinessError('champs_vides'); $msgType = 'danger';
        } elseif (strlen($sujet) > 191) {
            $msg = 'Le sujet ne peut pas dépasser 191 caractères.'; $msgType = 'danger';
        } else {
            // Vérifier que le prof enseigne bien dans cette classe
            $chk = $pdo->prepare("
                SELECT COUNT(*) FROM emplois_du_temps
                WHERE id_professeur=? AND id_classe=? AND version=(SELECT MAX(version) FROM emplois_du_temps)
            ");
            $chk->execute([$currentUser['id'], $idClasse]);
            if ((int)$chk->fetchColumn() === 0) {
                $msg = "Vous n'êtes pas autorisé à envoyer des messages à cette classe."; $msgType = 'danger';
            } else {
                $pdo->prepare("INSERT INTO messages (id_expediteur,id_classe,sujet,contenu,type) VALUES (?,?,?,?,?)")
                    ->execute([$currentUser['id'],$idClasse,$sujet,$contenu,$type]);
                $idMsg = (int)$pdo->lastInsertId();

                // Récupérer infos message complet pour email
                $msgData = [
                    'id'          => $idMsg,
                    'id_classe'   => $idClasse,
                    'sujet'       => $sujet,
                    'contenu'     => $contenu,
                    'type'        => $type,
                    'exp_nom'     => $currentUser['nom'],
                    'exp_prenom'  => $currentUser['prenom'],
                ];
                $classeRow = $pdo->prepare('SELECT nom FROM classes WHERE id=?');
                $classeRow->execute([$idClasse]);
                $classeNom = $classeRow->fetchColumn() ?: 'Votre classe';

                // Notifications internes aux élèves
                $stmtE = $pdo->prepare("SELECT id FROM utilisateurs WHERE role='eleve' AND id_classe=? AND statut='actif'");
                $stmtE->execute([$idClasse]);
                foreach ($stmtE->fetchAll() as $e) {
                    sendNotification($e['id'], "Message de ".$currentUser['prenom']." ".$currentUser['nom'], $sujet, 'info');
                }

                // Emails
                $emailResult = notifyMessageEleves($msgData, $classeNom);
                $typeData = getMessageTypeLabel($type);
$typeLabel = $typeData[2];
                $msg = "Message envoyé à la classe <strong>".h($classeNom)."</strong>. {$emailResult['sent']} email(s) envoyé(s).";
            }
        }
    } elseif ($action === 'delete') {
        $idMsg = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM messages WHERE id=? AND id_expediteur=?")->execute([$idMsg,$currentUser['id']]);
        $msg = 'Message supprimé.'; $msgType = 'warning';
    }
}

// Classes où ce prof enseigne (version actuelle)
$version = getCurrentVersion();
$stmtClasses = $pdo->prepare("
    SELECT DISTINCT cl.id, cl.nom, cl.niveau
    FROM emplois_du_temps e
    JOIN classes cl ON cl.id=e.id_classe
    WHERE e.id_professeur=? AND e.version=?
    ORDER BY cl.nom
");
$stmtClasses->execute([$currentUser['id'], $version]);
$mesClasses = $stmtClasses->fetchAll();

// Historique des messages envoyés
$stmtMsgs = $pdo->prepare("
    SELECT m.*, cl.nom AS classe_nom,
           (SELECT COUNT(*) FROM message_lu ml WHERE ml.id_message=m.id) AS nb_lus,
           (SELECT COUNT(*) FROM utilisateurs u WHERE u.role='eleve' AND u.id_classe=m.id_classe AND u.statut='actif') AS nb_eleves
    FROM messages m
    JOIN classes cl ON cl.id=m.id_classe
    WHERE m.id_expediteur=?
    ORDER BY m.date_envoi DESC
    LIMIT 50
");
$stmtMsgs->execute([$currentUser['id']]);
$historique = $stmtMsgs->fetchAll();

$typeOptions = [
    'info'    => '📢 Information générale',
    'absence' => '🚫 Absence du professeur',
    'devoir'  => '📚 Devoir / Travail',
    'report'  => '🔄 Report de cours',
    'autre'   => '💬 Autre',
];

$pageTitle = 'Messagerie'; $activeMenu = 'messages';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_prof.php';
?>

<div class="main-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1 class="page-title"><span class="material-icons-round">send</span> Messagerie</h1>
      <p class="page-subtitle">Envoyez des messages à vos classes</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modalCompose')">
      <span class="material-icons-round">edit</span> Nouveau message
    </button>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msgType ?>">
    <span class="material-icons-round"><?= $msgType==='danger'?'error_outline':'check_circle' ?></span>
    <div class="alert-content"><?= $msg ?></div>
  </div>
  <?php endif; ?>

  <?php if (empty($mesClasses)): ?>
  <div class="alert alert-warning">
    <span class="material-icons-round">warning</span>
    <div class="alert-content"><strong>Aucune classe assignée.</strong> Vous devez avoir des créneaux dans l'emploi du temps pour envoyer des messages.</div>
  </div>
  <?php endif; ?>

  <!-- Historique -->
  <div class="card">
    <div class="card-header">
      <div class="card-header-title"><span class="material-icons-round">history</span> Messages envoyés</div>
      <span class="badge badge-secondary"><?= count($historique) ?></span>
    </div>
    <?php if (empty($historique)): ?>
    <div class="card-body">
      <div class="empty-state">
        <div class="empty-state-icon"><span class="material-icons-round">inbox</span></div>
        <h3>Aucun message envoyé</h3>
        <p>Composez votre premier message à destination de vos classes.</p>
        <button class="btn btn-primary" onclick="openModal('modalCompose')" style="margin-top:1rem">
          <span class="material-icons-round">edit</span> Nouveau message
        </button>
      </div>
    </div>
    <?php else: ?>
    <div style="padding:.5rem">
      <?php foreach ($historique as $m):
        [$badgeClass,$icon,$typeLabel] = getMessageTypeLabel($m['type']);
        $pct = $m['nb_eleves'] > 0 ? round($m['nb_lus']/$m['nb_eleves']*100) : 0;
      ?>
      <div style="display:flex;align-items:flex-start;gap:1rem;padding:.9rem .75rem;border-radius:var(--radius-sm);border-bottom:1px solid var(--border)">
        <!-- Icône type -->
        <div class="stat-icon <?= $badgeClass==='purple'?'purple':($badgeClass==='warning'?'orange':$badgeClass) ?>" style="width:40px;height:40px;flex-shrink:0">
          <span class="material-icons-round" style="font-size:18px"><?= $icon ?></span>
        </div>
        <div style="flex:1;min-width:0">
          <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.2rem;flex-wrap:wrap">
            <strong style="font-size:.9rem"><?= h($m['sujet']) ?></strong>
            <span class="badge badge-<?= $badgeClass ?>"><?= $typeLabel ?></span>
            <span class="badge badge-secondary"><?= h($m['classe_nom']) ?></span>
          </div>
          <div style="font-size:.8rem;color:var(--text-muted);margin-bottom:.4rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:500px">
            <?= h($m['contenu']) ?>
          </div>
          <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
            <span style="font-size:.75rem;color:var(--text-light)"><?= date('d/m/Y à H:i',strtotime($m['date_envoi'])) ?></span>
            <!-- Barre de lecture -->
            <div style="display:flex;align-items:center;gap:.4rem">
              <div style="width:80px;height:5px;background:var(--bg);border-radius:99px;overflow:hidden">
                <div style="height:100%;width:<?= $pct ?>%;background:var(--success);border-radius:99px"></div>
              </div>
              <span style="font-size:.72rem;color:var(--text-muted)"><?= $m['nb_lus'] ?>/<?= $m['nb_eleves'] ?> lu<?= $m['nb_lus']>1?'s':'' ?></span>
            </div>
          </div>
        </div>
        <button class="btn btn-sm btn-ghost" style="padding:.3rem"
                onclick="if(confirm('Supprimer ce message ?')) { document.getElementById('del_form_id').value=<?= $m['id'] ?>; document.getElementById('del_form').submit(); }">
          <span class="material-icons-round" style="font-size:17px;color:var(--danger)">delete</span>
        </button>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Form suppression cachée -->
<form method="POST" id="del_form" style="display:none">
  <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
  <input type="hidden" name="action" value="delete">
  <input type="hidden" name="id" id="del_form_id">
</form>

<!-- Modal Composer -->
<div class="modal-overlay" id="modalCompose">
  <div class="modal" style="max-width:540px">
    <div class="modal-header">
      <span class="modal-title">Nouveau message</span>
      <button class="modal-close" onclick="closeModal('modalCompose')"><span class="material-icons-round">close</span></button>
    </div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
      <input type="hidden" name="action" value="send">
      <div class="modal-body">
        <?php if (empty($mesClasses)): ?>
        <div class="alert alert-warning" style="margin-bottom:0">
          <span class="material-icons-round">warning</span>
          <div class="alert-content">Aucune classe disponible. Vous devez être assigné à des classes dans l'emploi du temps.</div>
        </div>
        <?php else: ?>
        <div class="form-group">
          <label class="form-label">Classe destinataire <span style="color:var(--danger)">*</span></label>
          <select name="id_classe" class="form-control" required>
            <option value="">Sélectionner une classe…</option>
            <?php foreach ($mesClasses as $c): ?>
            <option value="<?= $c['id'] ?>"><?= h($c['nom']) ?> — <?= h($c['niveau']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Type de message</label>
          <select name="type" class="form-control">
            <?php foreach ($typeOptions as $val=>$lbl): ?>
            <option value="<?= $val ?>"><?= $lbl ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Sujet <span style="color:var(--danger)">*</span></label>
          <input type="text" name="sujet" class="form-control" required maxlength="191" placeholder="Ex : Absence demain, Devoir pour lundi…">
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Message <span style="color:var(--danger)">*</span></label>
          <textarea name="contenu" class="form-control" required rows="5" placeholder="Rédigez votre message ici…"></textarea>
          <div class="form-hint">Les élèves de la classe recevront une notification et un email.</div>
        </div>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalCompose')">Annuler</button>
        <?php if (!empty($mesClasses)): ?>
        <button type="submit" class="btn btn-primary">
          <span class="material-icons-round">send</span> Envoyer
        </button>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body></html>
