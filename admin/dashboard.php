<?php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();
$currentUser = getCurrentUser();
$pdo = getDB();
$msg = ''; $msgType = 'success';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset_edt') {
    verifyCsrf();
    $confirm = sanitize($_POST['confirm_text'] ?? '');
    if ($confirm !== 'REINITIALISER') {
        $msg = 'Confirmation incorrecte. Tapez exactement REINITIALISER pour valider.';
        $msgType = 'danger';
    } else {

        $pdo->exec("DELETE FROM notifications WHERE titre LIKE '%Génération%' OR titre LIKE '%Nouveau planning%' OR titre LIKE '%planning v%'");
       
        $pdo->exec('DELETE FROM emplois_du_temps');
        
        $pdo->exec("DELETE FROM creneaux WHERE id NOT IN (SELECT DISTINCT id_creneau FROM emplois_du_temps) AND id > 0");
        
        sendNotification($currentUser['id'], 'EDT réinitialisé',
            'Tous les emplois du temps ont été supprimés par '.$currentUser['prenom'].' '.$currentUser['nom'].'.', 'warning');
        $msg = 'Tous les emplois du temps ont été supprimés. Vous pouvez maintenant générer un nouvel emploi du temps.';
    }
}

$nbClasses   = (int)$pdo->query('SELECT COUNT(*) FROM classes')->fetchColumn();
$nbProfs     = (int)$pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role='professeur' AND statut='actif'")->fetchColumn();
$nbMatieres  = (int)$pdo->query('SELECT COUNT(*) FROM matieres')->fetchColumn();
$nbSalles    = (int)$pdo->query('SELECT COUNT(*) FROM salles')->fetchColumn();
$version     = getCurrentVersion();

$nbEdt = 0;
if ($version > 0) {
    $s = $pdo->prepare('SELECT COUNT(*) FROM emplois_du_temps WHERE version=?');
    $s->execute([$version]);
    $nbEdt = (int)$s->fetchColumn();
}
$nbEnAttente = countPendingValidations();
$nbNotifs    = countNotificationsNonLues($currentUser['id']);

// Résumé semaine
$joursEdt = getJoursSemaine();
$semaine  = array_fill_keys($joursEdt, []);
if ($version > 0) {
    $stmtSem = $pdo->prepare("
        SELECT e.*, m.nom AS matiere_nom, m.couleur_hex, cl.nom AS classe_nom, c.jour, c.heure_debut
        FROM emplois_du_temps e
        JOIN matieres m ON m.id=e.id_matiere
        JOIN classes cl ON cl.id=e.id_classe
        JOIN creneaux c ON c.id=e.id_creneau
        WHERE e.version=? ORDER BY c.heure_debut
    ");
    $stmtSem->execute([$version]);
    foreach ($stmtSem->fetchAll() as $row) $semaine[$row['jour']][] = $row;
}

// Notifications récentes
$stmtNotifs = $pdo->prepare("SELECT * FROM notifications WHERE id_utilisateur=? ORDER BY date_envoi DESC LIMIT 6");
$stmtNotifs->execute([$currentUser['id']]);
$recentNotifs = $stmtNotifs->fetchAll();

// Stats globales EDT
$totalVersions = (int)$pdo->query('SELECT COUNT(DISTINCT version) FROM emplois_du_temps')->fetchColumn();
$totalCreneaux = (int)$pdo->query('SELECT COUNT(*) FROM emplois_du_temps')->fetchColumn();

$pageTitle = 'Tableau de bord'; $activeMenu = 'dashboard';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_admin.php';
?>

<div class="main-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1 class="page-title"><span class="material-icons-round">dashboard</span> Tableau de bord</h1>
      <p class="page-subtitle">Vue d'ensemble de la plateforme — <?= date('l d F Y') ?></p>
    </div>
    <div style="display:flex;gap:.6rem;flex-wrap:wrap">
      <?php if ($totalCreneaux > 0): ?>
      <button class="btn btn-outline" onclick="openModal('modalResetEdt')"
              style="color:var(--danger);border-color:#FCA5A5">
        <span class="material-icons-round">restart_alt</span> Réinitialiser EDT
      </button>
      <?php endif; ?>
      <a href="<?= APP_URL ?>/admin/generer.php" class="btn btn-primary">
        <span class="material-icons-round">auto_fix_high</span> Générer EDT
      </a>
    </div>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msgType ?>">
    <span class="material-icons-round"><?= $msgType==='danger'?'error_outline':'check_circle' ?></span>
    <div class="alert-content"><?= $msg ?></div>
  </div>
  <?php endif; ?>

  <!-- Stats -->
  <div class="stat-grid">
    <div class="stat-card">
      <div class="stat-icon blue"><span class="material-icons-round">class</span></div>
      <div><div class="stat-value"><?= $nbClasses ?></div><div class="stat-label">Classes</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon green"><span class="material-icons-round">person</span></div>
      <div><div class="stat-value"><?= $nbProfs ?></div><div class="stat-label">Professeurs actifs</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon teal"><span class="material-icons-round">menu_book</span></div>
      <div><div class="stat-value"><?= $nbMatieres ?></div><div class="stat-label">Matières</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon purple"><span class="material-icons-round">meeting_room</span></div>
      <div><div class="stat-value"><?= $nbSalles ?></div><div class="stat-label">Salles</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon indigo"><span class="material-icons-round">calendar_month</span></div>
      <div>
        <div class="stat-value"><?= $nbEdt ?></div>
        <div class="stat-label">Créneaux v<?= $version ?></div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon orange"><span class="material-icons-round">pending_actions</span></div>
      <div><div class="stat-value"><?= $nbEnAttente ?></div><div class="stat-label">En attente validation</div></div>
    </div>
  </div>

  <!-- Bandeau récapitulatif EDT -->
  <?php if ($totalCreneaux > 0): ?>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:.85rem;margin-bottom:1.5rem">
    <div style="background:var(--primary-lt);border:1px solid #BFDBFE;border-radius:var(--radius-lg);padding:1rem;text-align:center">
      <div style="font-size:1.5rem;font-weight:700;color:var(--primary)"><?= $totalVersions ?></div>
      <div style="font-size:.78rem;color:var(--primary)">Version<?= $totalVersions>1?'s':'' ?> générée<?= $totalVersions>1?'s':'' ?></div>
    </div>
    <div style="background:var(--success-lt);border:1px solid #6EE7B7;border-radius:var(--radius-lg);padding:1rem;text-align:center">
      <div style="font-size:1.5rem;font-weight:700;color:var(--success)"><?= $totalCreneaux ?></div>
      <div style="font-size:.78rem;color:var(--success)">Total créneaux (toutes versions)</div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Grid semaine + notifications -->
  <div style="display:grid;grid-template-columns:1fr 340px;gap:1.5rem" class="dashboard-lower">

    <!-- Semaine courante -->
    <div class="card">
      <div class="card-header">
        <div class="card-header-title">
          <span class="material-icons-round">calendar_view_week</span> Résumé de la semaine
        </div>
        <?php if ($version > 0): ?>
        <span class="badge badge-primary">Version <?= $version ?></span>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <?php if ($version === 0): ?>
        <div class="empty-state">
          <div class="empty-state-icon"><span class="material-icons-round">calendar_month</span></div>
          <h3>Aucun emploi du temps</h3>
          <p>Aucune version générée. Commencez par générer un emploi du temps.</p>
          <a href="<?= APP_URL ?>/admin/generer.php" class="btn btn-primary" style="margin-top:1rem">
            <span class="material-icons-round">auto_fix_high</span> Générer
          </a>
        </div>
        <?php else: ?>
        <div class="week-summary">
          <?php foreach ($joursEdt as $jour): ?>
          <div class="day-col">
            <div class="day-col-header"><?= mb_substr($jour,0,3) ?></div>
            <div class="day-col-body">
              <?php if (empty($semaine[$jour])): ?>
                <span style="font-size:.72rem;color:var(--text-light);font-style:italic;margin:auto">Libre</span>
              <?php else: foreach ($semaine[$jour] as $c): ?>
                <div class="mini-course" style="background:<?= h($c['couleur_hex']) ?>">
                  <strong><?= h($c['matiere_nom']) ?></strong>
                  <span><?= h($c['classe_nom']) ?></span>
                </div>
              <?php endforeach; endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Notifications -->
    <div class="card">
      <div class="card-header">
        <div class="card-header-title"><span class="material-icons-round">notifications</span> Notifications</div>
        <a href="<?= APP_URL ?>/admin/notifications.php" class="btn btn-sm btn-outline">Tout voir</a>
      </div>
      <div class="card-body" style="padding:.5rem">
        <?php if (empty($recentNotifs)): ?>
        <div class="empty-state" style="padding:2rem 1rem">
          <div class="empty-state-icon"><span class="material-icons-round">notifications_none</span></div>
          <h3>Aucune notification</h3>
        </div>
        <?php else: ?>
        <div class="notif-list">
          <?php foreach ($recentNotifs as $n): ?>
          <div class="notif-item<?= $n['est_lu'] ? '' : ' unread' ?>">
            <div class="notif-icon <?= h($n['type']) ?>">
              <span class="material-icons-round"><?= $n['type']==='success'?'check_circle':($n['type']==='warning'?'warning':'info') ?></span>
            </div>
            <div class="notif-body">
              <div class="notif-title"><?= h($n['titre']) ?></div>
              <div class="notif-msg"><?= h($n['message']) ?></div>
              <div class="notif-date"><?= date('d/m H:i', strtotime($n['date_envoi'])) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ── Modal Réinitialisation EDT ──────────────────────────── -->
<div class="modal-overlay" id="modalResetEdt">
  <div class="modal" style="max-width:480px">
    <div class="modal-header">
      <span class="modal-title" style="color:var(--danger)">
        <span class="material-icons-round" style="font-size:20px;vertical-align:middle;margin-right:.35rem">warning</span>
        Réinitialiser tous les emplois du temps
      </span>
      <button class="modal-close" onclick="closeModal('modalResetEdt')"><span class="material-icons-round">close</span></button>
    </div>
    <div class="modal-body">
      <div style="background:var(--danger-lt);border:1px solid #FCA5A5;border-radius:var(--radius);padding:1rem;margin-bottom:1.25rem">
        <div style="font-weight:700;color:var(--danger);margin-bottom:.4rem;display:flex;align-items:center;gap:.4rem">
          <span class="material-icons-round" style="font-size:18px">dangerous</span>
          Action irréversible
        </div>
        <p style="font-size:.875rem;color:#991B1B;margin:0">
          Cette action va supprimer <strong>toutes les versions</strong> des emplois du temps
          (<strong><?= number_format($totalCreneaux) ?> créneau<?= $totalCreneaux>1?'x':'' ?></strong>).
          Les professeurs, classes, matières et salles ne seront pas affectés.
        </p>
      </div>

      <p style="font-size:.875rem;color:var(--text-muted);margin-bottom:1rem">
        Pour confirmer, tapez exactement <strong style="color:var(--danger);font-family:monospace">REINITIALISER</strong> dans le champ ci-dessous :
      </p>

      <form method="POST" id="resetForm">
        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
        <input type="hidden" name="action" value="reset_edt">
        <div class="form-group" style="margin-bottom:0">
          <input type="text" name="confirm_text" id="confirmInput" class="form-control"
                 placeholder="Tapez REINITIALISER"
                 autocomplete="off"
                 oninput="document.getElementById('resetBtn').disabled = this.value !== 'REINITIALISER'">
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-outline" onclick="closeModal('modalResetEdt')">Annuler</button>
      <button type="submit" form="resetForm" id="resetBtn" disabled
              style="background:var(--danger);color:#fff;border:none"
              class="btn">
        <span class="material-icons-round">restart_alt</span> Supprimer tout
      </button>
    </div>
  </div>
</div>

<style>
@media(max-width:900px){.dashboard-lower{grid-template-columns:1fr!important}}
</style>
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body>
</html>
