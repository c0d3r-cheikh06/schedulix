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

    if ($action === 'change_classe') {
        $idEleve      = (int)$_POST['id_eleve'];
        $idNvClasse   = (int)$_POST['id_classe'];
        $motif        = sanitize($_POST['motif'] ?? '');

        $chk = $pdo->prepare("SELECT id, nom, prenom, id_classe FROM utilisateurs WHERE id=? AND role='eleve' LIMIT 1");
        $chk->execute([$idEleve]);
        $eleve = $chk->fetch();

        if (!$eleve) {
            $msg = 'Élève introuvable.'; $msgType = 'danger';
        } elseif ($eleve['id_classe'] === $idNvClasse) {
            $msg = 'L\'élève est déjà dans cette classe.'; $msgType = 'warning';
        } else {
            $ancienneClasse = null;
            if ($eleve['id_classe']) {
                $r = $pdo->prepare('SELECT nom FROM classes WHERE id=?');
                $r->execute([$eleve['id_classe']]);
                $ancienneClasse = $r->fetchColumn();
            }
            $nvClasse = null;
            if ($idNvClasse) {
                $r = $pdo->prepare('SELECT nom FROM classes WHERE id=?');
                $r->execute([$idNvClasse]);
                $nvClasse = $r->fetchColumn();
            }

            $pdo->prepare("UPDATE utilisateurs SET id_classe=? WHERE id=?")
                ->execute([$idNvClasse ?: null, $idEleve]);

            // Marquer les messages de l'ancienne classe comme lus (évite confusion)
            // Les messages restent visibles dans l'historique

            // Notification à l'élève
            $notifMsg = $nvClasse
                ? "Votre classe a été modifiée : vous êtes maintenant en <strong>{$nvClasse}</strong>."
                : "Vous n'êtes plus affecté à une classe.";
            if ($motif) $notifMsg .= " Motif : {$motif}";
            sendNotification($idEleve, 'Changement de classe', strip_tags($notifMsg), 'info');

            $fromLabel = $ancienneClasse ? h($ancienneClasse) : 'Aucune';
            $toLabel   = $nvClasse ? h($nvClasse) : 'Aucune';
            $msg = "Classe de <strong>" . h($eleve['prenom'].' '.$eleve['nom']) . "</strong> modifiée : {$fromLabel} → {$toLabel}.";
        }

    } elseif ($action === 'promotion_masse') {
        // Promotion en masse : faire passer tous les élèves d'une classe à une autre
        $idClasseSource = (int)$_POST['id_classe_source'];
        $idClasseCible  = (int)$_POST['id_classe_cible'];
        if ($idClasseSource === $idClasseCible) {
            $msg = 'Les classes source et cible doivent être différentes.'; $msgType = 'danger';
        } else {
            $stmt = $pdo->prepare("UPDATE utilisateurs SET id_classe=? WHERE id_classe=? AND role='eleve'");
            $stmt->execute([$idClasseCible, $idClasseSource]);
            $nbAffectes = $stmt->rowCount();

            $rSrc = $pdo->prepare('SELECT nom FROM classes WHERE id=?'); $rSrc->execute([$idClasseSource]);
            $rDst = $pdo->prepare('SELECT nom FROM classes WHERE id=?'); $rDst->execute([$idClasseCible]);
            $nomSrc = $rSrc->fetchColumn(); $nomDst = $rDst->fetchColumn();

            // Notifier les élèves promus
            $stmtEl = $pdo->prepare("SELECT id FROM utilisateurs WHERE id_classe=? AND role='eleve'");
            $stmtEl->execute([$idClasseCible]);
            foreach ($stmtEl->fetchAll() as $e) {
                sendNotification($e['id'], 'Promotion de classe',
                    "Vous avez été promu(e) de la classe {$nomSrc} vers {$nomDst}.", 'success');
            }
            $msg = "<strong>{$nbAffectes} élève(s)</strong> déplacés de <strong>".h($nomSrc)."</strong> vers <strong>".h($nomDst)."</strong>.";
        }

    } elseif ($action === 'desaffecter') {
        $idEleve = (int)$_POST['id_eleve'];
        $pdo->prepare("UPDATE utilisateurs SET id_classe=NULL WHERE id=? AND role='eleve'")->execute([$idEleve]);
        sendNotification($idEleve, 'Désaffectation', 'Vous n\'êtes plus affecté à une classe.', 'warning');
        $msg = 'Élève désaffecté de sa classe.'; $msgType = 'warning';

    } elseif ($action === 'toggle_statut') {
        $idEleve  = (int)$_POST['id_eleve'];
        $statut   = $_POST['statut'] === 'actif' ? 'inactif' : 'actif';
        $pdo->prepare("UPDATE utilisateurs SET statut=? WHERE id=? AND role='eleve'")->execute([$statut, $idEleve]);
        $msg = "Statut de l'élève mis à jour : <strong>{$statut}</strong>.";
        $msgType = $statut === 'actif' ? 'success' : 'warning';
    }
}

// Filtres
$filtreClasse = (int)($_GET['classe'] ?? 0);
$filtreSearch = sanitize($_GET['q'] ?? '');

$where  = ["u.role='eleve'"];
$params = [];
if ($filtreClasse) { $where[] = 'u.id_classe=?'; $params[] = $filtreClasse; }
if ($filtreSearch) { $where[] = "(u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)"; $params = array_merge($params, ["%{$filtreSearch}%","%{$filtreSearch}%","%{$filtreSearch}%"]); }

$sql = "SELECT u.*, c.nom AS classe_nom, n.nom AS niveau_nom
        FROM utilisateurs u
        LEFT JOIN classes c ON c.id=u.id_classe
        LEFT JOIN niveaux n ON n.id=c.id_niveau
        WHERE ".implode(' AND ',$where)."
        ORDER BY c.nom, u.nom, u.prenom";
$stmtEl = $pdo->prepare($sql);
$stmtEl->execute($params);
$eleves = $stmtEl->fetchAll();

$allClasses  = getClassesWithNiveau();
$nbEleves    = count($eleves);
$nbSansClasse = count(array_filter($eleves, fn($e) => !$e['id_classe']));

$pageTitle = 'Gestion des Élèves'; $activeMenu = 'eleves';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_admin.php';
?>

<div class="main-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1 class="page-title"><span class="material-icons-round">groups</span> Élèves</h1>
      <p class="page-subtitle">
        <?= $nbEleves ?> élève<?= $nbEleves>1?'s':'' ?>
        <?php if ($nbSansClasse > 0): ?> · <span style="color:var(--warning)"><?= $nbSansClasse ?> sans classe</span><?php endif; ?>
      </p>
    </div>
    <?php if (!empty($allClasses)): ?>
    <button class="btn btn-outline" onclick="openModal('modalPromoMasse')">
      <span class="material-icons-round">trending_up</span> Promotion en masse
    </button>
    <?php endif; ?>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msgType ?>">
    <span class="material-icons-round"><?= $msgType==='danger'?'error_outline':($msgType==='warning'?'warning':'check_circle') ?></span>
    <div class="alert-content"><?= $msg ?></div>
  </div>
  <?php endif; ?>

  <?php if ($nbSansClasse > 0): ?>
  <div class="alert alert-warning">
    <span class="material-icons-round">warning</span>
    <div class="alert-content">
      <strong><?= $nbSansClasse ?> élève<?= $nbSansClasse>1?'s':'' ?> non affecté<?= $nbSansClasse>1?'s':'' ?> à une classe.</strong>
      Ces élèves ne verront aucun emploi du temps.
    </div>
  </div>
  <?php endif; ?>

  <!-- Filtres -->
  <div class="card" style="margin-bottom:1.25rem">
    <div class="card-body" style="padding:.85rem 1.25rem">
      <form method="GET" style="display:flex;gap:.85rem;flex-wrap:wrap;align-items:flex-end">
        <div class="form-group" style="margin:0;flex:1;min-width:200px">
          <label class="form-label">Recherche</label>
          <input type="text" name="q" class="form-control" value="<?= h($filtreSearch) ?>" placeholder="Nom, prénom, email…">
        </div>
        <div class="form-group" style="margin:0;min-width:180px">
          <label class="form-label">Filtrer par classe</label>
          <select name="classe" class="form-control" onchange="this.form.submit()">
            <option value="">Toutes les classes</option>
            <option value="-1" <?= $filtreClasse===-1?'selected':'' ?>>Sans classe</option>
            <?php foreach ($allClasses as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $c['id']===$filtreClasse?'selected':'' ?>><?= h($c['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-primary" style="align-self:flex-end">
          <span class="material-icons-round">search</span> Filtrer
        </button>
        <?php if ($filtreSearch || $filtreClasse): ?>
        <a href="?" class="btn btn-outline" style="align-self:flex-end">
          <span class="material-icons-round">clear</span> Effacer
        </a>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="table-wrapper" style="border:none;border-radius:0">
      <table class="table">
        <thead>
          <tr>
            <th>Élève</th>
            <th>Classe actuelle</th>
            <th>Niveau</th>
            <th>Statut</th>
            <th style="text-align:right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($eleves)): ?>
          <tr><td colspan="5">
            <div class="empty-state">
              <div class="empty-state-icon"><span class="material-icons-round">groups</span></div>
              <h3>Aucun élève trouvé</h3>
              <p>Aucun compte élève enregistré ou correspondant aux filtres.</p>
            </div>
          </td></tr>
          <?php else: foreach ($eleves as $e): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:.75rem">
                <div class="user-avatar" style="width:36px;height:36px;font-size:.8rem;flex-shrink:0;background:<?= $e['statut']==='actif'?'linear-gradient(135deg,var(--primary),var(--accent))':'#9CA3AF' ?>">
                  <?= strtoupper(substr($e['prenom'],0,1).substr($e['nom'],0,1)) ?>
                </div>
                <div>
                  <div style="font-weight:600;color:var(--text)"><?= h($e['prenom'].' '.$e['nom']) ?></div>
                  <div style="font-size:.75rem;color:var(--text-muted)"><?= h($e['email']) ?></div>
                </div>
              </div>
            </td>
            <td>
              <?php if ($e['classe_nom']): ?>
              <span class="badge badge-secondary"><?= h($e['classe_nom']) ?></span>
              <?php else: ?>
              <span class="badge badge-warning"><span class="material-icons-round" style="font-size:11px">warning</span> Non affecté</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($e['niveau_nom']): ?>
              <span style="font-size:.82rem;color:var(--text-muted)"><?= h($e['niveau_nom']) ?></span>
              <?php else: ?>
              <span style="font-size:.8rem;color:var(--text-light);font-style:italic">—</span>
              <?php endif; ?>
            </td>
            <td>
              <?= $e['statut']==='actif'
                ? '<span class="badge badge-success"><span class="material-icons-round" style="font-size:10px">circle</span> Actif</span>'
                : '<span class="badge badge-danger"><span class="material-icons-round" style="font-size:10px">circle</span> Inactif</span>' ?>
            </td>
            <td>
              <div class="table-actions" style="justify-content:flex-end">
                <button class="btn btn-sm btn-primary"
                        onclick='openChangeClasse(<?= json_encode($e, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'
                        title="Changer de classe">
                  <span class="material-icons-round">swap_horiz</span> Classe
                </button>
                <?php if ($e['id_classe']): ?>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                  <input type="hidden" name="action" value="desaffecter">
                  <input type="hidden" name="id_eleve" value="<?= $e['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline" title="Désaffecter"
                          onclick="return confirm('Désaffecter <?= h(addslashes($e['prenom'].' '.$e['nom'])) ?> de sa classe ?')">
                    <span class="material-icons-round">link_off</span>
                  </button>
                </form>
                <?php endif; ?>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                  <input type="hidden" name="action" value="toggle_statut">
                  <input type="hidden" name="id_eleve" value="<?= $e['id'] ?>">
                  <input type="hidden" name="statut" value="<?= $e['statut'] ?>">
                  <button type="submit" class="btn btn-sm <?= $e['statut']==='actif'?'btn-danger':'btn-success' ?>"
                          title="<?= $e['statut']==='actif'?'Désactiver':'Activer' ?>">
                    <span class="material-icons-round"><?= $e['statut']==='actif'?'block':'check_circle' ?></span>
                  </button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Changer de classe -->
<div class="modal-overlay" id="modalChangeClasse">
  <div class="modal" style="max-width:460px">
    <div class="modal-header">
      <span class="modal-title">Changer la classe de l'élève</span>
      <button class="modal-close" onclick="closeModal('modalChangeClasse')"><span class="material-icons-round">close</span></button>
    </div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
      <input type="hidden" name="action" value="change_classe">
      <input type="hidden" name="id_eleve" id="cc_id">
      <div class="modal-body">
        <!-- Info élève -->
        <div style="display:flex;align-items:center;gap:.75rem;padding:.85rem;background:var(--bg);border-radius:var(--radius);margin-bottom:1.1rem">
          <div class="user-avatar" style="width:40px;height:40px;font-size:.85rem" id="cc_avatar"></div>
          <div>
            <div style="font-weight:700;color:var(--text)" id="cc_name"></div>
            <div style="font-size:.8rem;color:var(--text-muted)">
              Classe actuelle : <strong id="cc_current_classe"></strong>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Nouvelle classe <span style="color:var(--danger)">*</span></label>
          <select name="id_classe" id="cc_classe" class="form-control" required>
            <option value="">— Sélectionner une classe —</option>
            <?php
            $lastNiveau = null;
            foreach ($allClasses as $c):
                if ($c['niveau_nom'] !== $lastNiveau) {
                    if ($lastNiveau !== null) echo '</optgroup>';
                    echo '<optgroup label="'.h($c['niveau_nom'] ?: 'Sans niveau').'">';
                    $lastNiveau = $c['niveau_nom'];
                }
            ?>
            <option value="<?= $c['id'] ?>"><?= h($c['nom']) ?></option>
            <?php endforeach; if ($lastNiveau !== null) echo '</optgroup>'; ?>
          </select>
        </div>

        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Motif du changement (facultatif)</label>
          <input type="text" name="motif" class="form-control"
                 placeholder="Ex : Fin d'année scolaire, Redoublement, Transfert…" maxlength="200">
          <div class="form-hint">L'élève recevra une notification avec ce motif.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalChangeClasse')">Annuler</button>
        <button type="submit" class="btn btn-primary">
          <span class="material-icons-round">swap_horiz</span> Confirmer le changement
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Promotion en masse -->
<div class="modal-overlay" id="modalPromoMasse">
  <div class="modal" style="max-width:480px">
    <div class="modal-header">
      <span class="modal-title">Promotion en masse</span>
      <button class="modal-close" onclick="closeModal('modalPromoMasse')"><span class="material-icons-round">close</span></button>
    </div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
      <input type="hidden" name="action" value="promotion_masse">
      <div class="modal-body">
        <div class="alert alert-info" style="margin-bottom:1.1rem">
          <span class="material-icons-round">info</span>
          <div class="alert-content">
            Tous les élèves de la classe source seront déplacés vers la classe cible.
            Utile en fin d'année scolaire pour une promotion de classe entière.
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Classe source (départ) <span style="color:var(--danger)">*</span></label>
          <select name="id_classe_source" class="form-control" required>
            <option value="">— Classe actuelle des élèves —</option>
            <?php foreach ($allClasses as $c): ?>
            <option value="<?= $c['id'] ?>"><?= h($c['nom']) ?><?= $c['niveau_nom']?' — '.h($c['niveau_nom']):'' ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Classe cible (arrivée) <span style="color:var(--danger)">*</span></label>
          <select name="id_classe_cible" class="form-control" required>
            <option value="">— Nouvelle classe —</option>
            <?php foreach ($allClasses as $c): ?>
            <option value="<?= $c['id'] ?>"><?= h($c['nom']) ?><?= $c['niveau_nom']?' — '.h($c['niveau_nom']):'' ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalPromoMasse')">Annuler</button>
        <button type="submit" class="btn btn-primary"
                onclick="return confirm('Confirmer la promotion en masse ? Cette action affectera tous les élèves de la classe source.')">
          <span class="material-icons-round">trending_up</span> Promouvoir
        </button>
      </div>
    </form>
  </div>
</div>

<script src="<?= APP_URL ?>/assets/js/main.js"></script>
<script>
function openChangeClasse(e) {
  document.getElementById('cc_id').value      = e.id;
  document.getElementById('cc_name').textContent = e.prenom + ' ' + e.nom;
  document.getElementById('cc_avatar').textContent =
    (e.prenom.charAt(0) + e.nom.charAt(0)).toUpperCase();
  document.getElementById('cc_current_classe').textContent = e.classe_nom || 'Aucune';
  // Présélectionner la classe actuelle
  const sel = document.getElementById('cc_classe');
  for (let opt of sel.options) {
    if (parseInt(opt.value) === parseInt(e.id_classe)) { sel.value = opt.value; break; }
  }
  openModal('modalChangeClasse');
}
</script>
</body>
</html>
