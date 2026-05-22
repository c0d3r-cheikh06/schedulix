<?php
// eleve/messages.php — Boîte de réception des messages
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireEleve();
$currentUser = getCurrentUser();
$pdo = getDB();
$idClasse = $currentUser['id_classe'] ?? 0;

// Marquer un message comme lu
if (isset($_GET['read']) && is_numeric($_GET['read'])) {
    $idMsg = (int)$_GET['read'];
    $pdo->prepare("INSERT IGNORE INTO message_lu (id_message,id_eleve) VALUES (?,?)")->execute([$idMsg,$currentUser['id']]);
    // Redirect pour propre URL
    header('Location: '.$_SERVER['PHP_SELF'].'?ok=1');
    exit;
}
// Marquer tout comme lu
if (isset($_GET['mark_all'])) {
    if ($idClasse) {
        $stmtAll = $pdo->prepare("SELECT id FROM messages WHERE id_classe=?");
        $stmtAll->execute([$idClasse]);
        foreach ($stmtAll->fetchAll() as $m) {
            $pdo->prepare("INSERT IGNORE INTO message_lu (id_message,id_eleve) VALUES (?,?)")->execute([$m['id'],$currentUser['id']]);
        }
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

// Message sélectionné
$selectedId = (int)($_GET['id'] ?? 0);
$selectedMsg = null;
if ($selectedId && $idClasse) {
    $s = $pdo->prepare("
        SELECT m.*, u.nom AS exp_nom, u.prenom AS exp_prenom, cl.nom AS classe_nom
        FROM messages m
        JOIN utilisateurs u ON u.id=m.id_expediteur
        JOIN classes cl ON cl.id=m.id_classe
        WHERE m.id=? AND m.id_classe=?
    ");
    $s->execute([$selectedId, $idClasse]);
    $selectedMsg = $s->fetch() ?: null;
    if ($selectedMsg) {
        $pdo->prepare("INSERT IGNORE INTO message_lu (id_message,id_eleve) VALUES (?,?)")->execute([$selectedId,$currentUser['id']]);
    }
}

// Liste des messages
$messages = [];
if ($idClasse) {
    $stmtM = $pdo->prepare("
        SELECT m.*, u.nom AS exp_nom, u.prenom AS exp_prenom,
               (SELECT COUNT(*) FROM message_lu ml WHERE ml.id_message=m.id AND ml.id_eleve=?) AS est_lu
        FROM messages m
        JOIN utilisateurs u ON u.id=m.id_expediteur
        WHERE m.id_classe=?
        ORDER BY m.date_envoi DESC
    ");
    $stmtM->execute([$currentUser['id'], $idClasse]);
    $messages = $stmtM->fetchAll();
}
$nbNonLus = count(array_filter($messages, fn($m)=>!$m['est_lu']));

$pageTitle = 'Messages'; $activeMenu = 'messages';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_eleve.php';
?>

<div class="main-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1 class="page-title"><span class="material-icons-round">inbox</span> Messages</h1>
      <p class="page-subtitle"><?= $nbNonLus ?> non lu<?= $nbNonLus>1?'s':'' ?> — <?= count($messages) ?> message<?= count($messages)>1?'s':'' ?> au total</p>
    </div>
    <?php if ($nbNonLus > 0): ?>
    <a href="?mark_all=1" class="btn btn-outline">
      <span class="material-icons-round">done_all</span> Tout marquer lu
    </a>
    <?php endif; ?>
  </div>

  <?php if (!$idClasse): ?>
  <div class="alert alert-warning">
    <span class="material-icons-round">warning</span>
    <div class="alert-content"><strong>Classe non assignée.</strong> Contactez votre administrateur.</div>
  </div>
  <?php elseif (empty($messages)): ?>
  <div class="card"><div class="card-body">
    <div class="empty-state">
      <div class="empty-state-icon"><span class="material-icons-round">inbox</span></div>
      <h3>Aucun message</h3>
      <p>Vous n'avez reçu aucun message de vos professeurs pour le moment.</p>
    </div>
  </div></div>
  <?php else: ?>

  <div style="display:grid;grid-template-columns:340px 1fr;gap:1.25rem;align-items:start" class="msg-grid">
    <!-- Liste messages -->
    <div class="card">
      <div class="card-header">
        <div class="card-header-title"><span class="material-icons-round">list</span> Tous les messages</div>
        <?php if ($nbNonLus > 0): ?>
        <span class="badge badge-danger"><?= $nbNonLus ?></span>
        <?php endif; ?>
      </div>
      <div style="max-height:600px;overflow-y:auto">
        <?php foreach ($messages as $m):
          [$bClass,$icon,$typeLabel] = getMessageTypeLabel($m['type']);
          $isSelected = $selectedId === (int)$m['id'];
          $isUnread   = !$m['est_lu'];
        ?>
        <a href="?id=<?= $m['id'] ?>" style="display:flex;align-items:flex-start;gap:.75rem;padding:.85rem 1rem;border-bottom:1px solid var(--border);text-decoration:none;background:<?= $isSelected?'var(--primary-lt)':($isUnread?'#FAFBFF':'#fff') ?>">
          <div class="stat-icon <?= $bClass==='purple'?'purple':($bClass==='warning'?'orange':$bClass) ?>" style="width:36px;height:36px;flex-shrink:0">
            <span class="material-icons-round" style="font-size:16px"><?= $icon ?></span>
          </div>
          <div style="flex:1;min-width:0">
            <div style="display:flex;align-items:center;gap:.4rem;margin-bottom:.15rem">
              <?php if ($isUnread): ?>
              <span style="width:7px;height:7px;background:var(--primary);border-radius:50%;flex-shrink:0"></span>
              <?php endif; ?>
              <span style="font-size:.85rem;font-weight:<?= $isUnread?'700':'500' ?>;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                <?= h($m['sujet']) ?>
              </span>
            </div>
            <div style="font-size:.75rem;color:var(--text-muted)">
              <?= h($m['exp_prenom'].' '.$m['exp_nom']) ?> · <?= date('d/m à H:i',strtotime($m['date_envoi'])) ?>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Détail message -->
    <div class="card">
      <?php if ($selectedMsg): ?>
      <?php [$bClass,$icon,$typeLabel] = getMessageTypeLabel($selectedMsg['type']); ?>
      <div class="card-header">
        <div style="flex:1;min-width:0">
          <div style="font-weight:700;font-size:1rem;color:var(--text);margin-bottom:.3rem"><?= h($selectedMsg['sujet']) ?></div>
          <div style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap">
            <span class="badge badge-<?= $bClass ?>"><?= $typeLabel ?></span>
            <span style="font-size:.78rem;color:var(--text-muted)">
              De : <strong><?= h($selectedMsg['exp_prenom'].' '.$selectedMsg['exp_nom']) ?></strong>
              · <?= date('d/m/Y à H:i',strtotime($selectedMsg['date_envoi'])) ?>
            </span>
          </div>
        </div>
      </div>
      <div class="card-body">
        <div style="font-size:.925rem;color:var(--text-2);line-height:1.75;white-space:pre-wrap;border-left:3px solid var(--primary);padding-left:1rem;margin:0">
          <?= h($selectedMsg['contenu']) ?>
        </div>
      </div>
      <?php else: ?>
      <div class="card-body">
        <div class="empty-state" style="padding:4rem 2rem">
          <div class="empty-state-icon"><span class="material-icons-round">mark_email_read</span></div>
          <h3>Sélectionnez un message</h3>
          <p>Cliquez sur un message dans la liste pour le lire.</p>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <?php endif; ?>
</div>

<style>
@media(max-width:768px){.msg-grid{grid-template-columns:1fr!important}}
</style>
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body></html>
