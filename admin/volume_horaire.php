<?php
// admin/volume_horaire.php — v4.1
// Gestion des matières par classe + volume horaire + affectation prof
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();
$currentUser = getCurrentUser();
$pdo  = getDB();
$msg  = ''; $msgType = 'success';

// ── Auto-création des tables si absentes ─────────────────────
$pdo->exec("CREATE TABLE IF NOT EXISTS `volume_horaire` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `id_classe` INT(11) NOT NULL, `id_matiere` INT(11) NOT NULL,
  `nb_heures_semaine` INT(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_vh` (`id_classe`,`id_matiere`),
  FOREIGN KEY (`id_classe`)  REFERENCES `classes`  (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_matiere`) REFERENCES `matieres` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS `affectations` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `id_classe` INT(11) NOT NULL, `id_matiere` INT(11) NOT NULL, `id_professeur` INT(11) NOT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_aff` (`id_classe`,`id_matiere`,`id_professeur`),
  FOREIGN KEY (`id_classe`)     REFERENCES `classes`      (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_matiere`)    REFERENCES `matieres`     (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_professeur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Traitement POST ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    // ── Sauvegarder grille complète ───────────────────────────
    if ($action === 'save_all') {
        $pdo->beginTransaction();
        try {
            $cells    = $_POST['vh']   ?? [];    // [idClasse][idMatiere] = nbH
            $affProfs = $_POST['aff']  ?? [];    // [idClasse][idMatiere] = idProf
            $saved    = 0;

            foreach ($cells as $idClasse => $mats) {
                foreach ($mats as $idMatiere => $nbH) {
                    $idClasse  = (int)$idClasse;
                    $idMatiere = (int)$idMatiere;
                    $nbH       = max(0, min(40, (int)$nbH));
                    $idProf    = (int)($affProfs[$idClasse][$idMatiere] ?? 0);

                    if ($nbH === 0) {
                        // Supprimer la matière de la classe
                        $pdo->prepare('DELETE FROM volume_horaire WHERE id_classe=? AND id_matiere=?')->execute([$idClasse,$idMatiere]);
                        $pdo->prepare('DELETE FROM affectations WHERE id_classe=? AND id_matiere=?')->execute([$idClasse,$idMatiere]);
                    } else {
                        // Upsert volume
                        $pdo->prepare('INSERT INTO volume_horaire (id_classe,id_matiere,nb_heures_semaine) VALUES (?,?,?)
                                       ON DUPLICATE KEY UPDATE nb_heures_semaine=VALUES(nb_heures_semaine)')
                            ->execute([$idClasse,$idMatiere,$nbH]);
                        // Upsert affectation prof
                        if ($idProf) {
                            $pdo->prepare('DELETE FROM affectations WHERE id_classe=? AND id_matiere=?')->execute([$idClasse,$idMatiere]);
                            $pdo->prepare('INSERT IGNORE INTO affectations (id_classe,id_matiere,id_professeur) VALUES (?,?,?)')->execute([$idClasse,$idMatiere,$idProf]);
                        }
                        $saved++;
                    }
                }
            }
            $pdo->commit();
            $msg = "Configuration enregistrée — {$saved} matière(s) configurée(s).";
        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = 'Erreur lors de la sauvegarde : '.$e->getMessage(); $msgType='danger';
        }

    // ── Copier config d'une classe vers d'autres ──────────────
    } elseif ($action === 'copy_from') {
        $idSrc  = (int)($_POST['id_classe_source'] ?? 0);
        $cibles = array_map('intval', $_POST['classes_cibles'] ?? []);
        if ($idSrc && !empty($cibles)) {
            $vols = $pdo->prepare('SELECT id_matiere,nb_heures_semaine FROM volume_horaire WHERE id_classe=?');
            $vols->execute([$idSrc]);
            $affs = $pdo->prepare('SELECT id_matiere,id_professeur FROM affectations WHERE id_classe=?');
            $affs->execute([$idSrc]);
            $affsData = $affs->fetchAll();
            $copied = 0;
            foreach ($cibles as $idDst) {
                if ($idDst === $idSrc) continue;
                foreach ($vols->fetchAll() as $r) {
                    $pdo->prepare('INSERT INTO volume_horaire (id_classe,id_matiere,nb_heures_semaine) VALUES (?,?,?)
                                   ON DUPLICATE KEY UPDATE nb_heures_semaine=VALUES(nb_heures_semaine)')
                        ->execute([$idDst,$r['id_matiere'],$r['nb_heures_semaine']]);
                    $copied++;
                }
                foreach ($affsData as $a) {
                    $pdo->prepare('INSERT IGNORE INTO affectations (id_classe,id_matiere,id_professeur) VALUES (?,?,?)')->execute([$idDst,$a['id_matiere'],$a['id_professeur']]);
                }
            }
            // Refetch vols for next iteration
            $vols->execute([$idSrc]);
            $msg = "Copié vers ".count($cibles)." classe(s) — {$copied} matière(s).";
        }

    // ── Réinitialiser tout ────────────────────────────────────
    } elseif ($action === 'reset_all') {
        $pdo->exec('DELETE FROM affectations');
        $pdo->exec('DELETE FROM volume_horaire');
        $msg = 'Configuration réinitialisée. Toutes les associations classe×matière ont été supprimées.';
        $msgType = 'warning';
    }
}

// ── Données ───────────────────────────────────────────────────
$classes   = getClassesWithNiveau();
$matieres  = getMatieres();
$profs     = getProfesseurs();

// Charger volumes : [idClasse][idMatiere] = nbH
$volumes = [];
foreach ($pdo->query('SELECT * FROM volume_horaire')->fetchAll() as $r) {
    $volumes[$r['id_classe']][$r['id_matiere']] = (int)$r['nb_heures_semaine'];
}

// Charger affectations : [idClasse][idMatiere] = idProf
$affectations = [];
foreach ($pdo->query('SELECT * FROM affectations')->fetchAll() as $r) {
    $affectations[$r['id_classe']][$r['id_matiere']] = (int)$r['id_professeur'];
}

// Profs par matière (pour le select)
$profsByMat = [];
foreach ($pdo->query("SELECT pm.id_matiere, u.id, u.nom, u.prenom FROM professeur_matiere pm JOIN utilisateurs u ON u.id=pm.id_professeur WHERE u.statut='actif' ORDER BY u.nom,u.prenom")->fetchAll() as $r) {
    $profsByMat[$r['id_matiere']][] = $r;
}

// Statistiques par classe (nb matières configurées, total heures)
$statsClasse = [];
foreach ($classes as $cl) {
    $nbMats = count($volumes[$cl['id']] ?? []);
    $totalH = array_sum($volumes[$cl['id']] ?? []);
    $statsClasse[$cl['id']] = ['mats' => $nbMats, 'heures' => $totalH];
}

$pageTitle = 'Volume horaire & Matières'; $activeMenu = 'volume_horaire';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_admin.php';
?>

<style>
/* ── Page layout ─────────────────────────────────────────── */
.vh-layout { display:grid; grid-template-columns:280px 1fr; gap:1.25rem; align-items:start; }
@media(max-width:1100px){ .vh-layout { grid-template-columns:1fr; } }

/* ── Classe selector ─────────────────────────────────────── */
.classe-list { display:flex; flex-direction:column; gap:.3rem; }
.classe-item {
  display:flex; align-items:center; justify-content:space-between;
  padding:.65rem .9rem; border-radius:var(--radius-sm);
  cursor:pointer; border:1.5px solid var(--border);
  background:#fff; gap:.6rem;
}
.classe-item:hover { border-color:var(--primary); background:var(--primary-lt); }
.classe-item.active { border-color:var(--primary); background:var(--primary-lt); font-weight:600; color:var(--primary); }
.classe-item .mat-count {
  font-size:.72rem; padding:.1rem .45rem;
  border-radius:99px; font-weight:700;
  background:var(--bg); color:var(--text-muted);
  white-space:nowrap; flex-shrink:0;
}
.classe-item.active .mat-count { background:var(--primary); color:#fff; }

/* ── Matières grid ───────────────────────────────────────── */
.mat-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(310px,1fr)); gap:.85rem; }

.mat-card {
  border:1.5px solid var(--border); border-radius:var(--radius-lg);
  background:#fff; overflow:hidden;
}
.mat-card.enabled  { border-color:var(--primary); }
.mat-card-header {
  display:flex; align-items:center; gap:.75rem;
  padding:.75rem 1rem;
  border-bottom:1px solid var(--border);
  background:var(--bg);
}
.mat-card.enabled .mat-card-header { background:var(--primary-lt); border-bottom-color:#BFDBFE; }
.mat-card-body { padding:.9rem 1rem; display:flex; flex-direction:column; gap:.75rem; }

/* Toggle switch matière */
.toggle-wrap { display:flex; align-items:center; gap:.5rem; cursor:pointer; }
.toggle-switch {
  width:40px; height:22px; border-radius:99px;
  background:var(--border); position:relative;
  flex-shrink:0; transition:background .15s;
}
.toggle-switch::after {
  content:''; position:absolute; top:3px; left:3px;
  width:16px; height:16px; border-radius:50%;
  background:#fff; transition:transform .15s;
  box-shadow:0 1px 3px rgba(0,0,0,.2);
}
.toggle-switch.on { background:var(--primary); }
.toggle-switch.on::after { transform:translateX(18px); }

/* Champ heures */
.h-input {
  width:60px; text-align:center; border:1.5px solid var(--border);
  border-radius:var(--radius-sm); padding:.35rem .3rem;
  font-size:.9rem; font-family:inherit; color:var(--text);
  background:#fff; outline:none;
}
.h-input:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(26,86,219,.1); }

/* Sélecteur prof compact */
.prof-select {
  width:100%; border:1.5px solid var(--border); border-radius:var(--radius-sm);
  padding:.35rem .65rem; font-size:.82rem; font-family:inherit;
  color:var(--text); background:#fff; outline:none; cursor:pointer;
}
.prof-select:focus { border-color:var(--primary); }

/* Disabled state */
.mat-card-body.disabled { opacity:.4; pointer-events:none; }

/* Résumé bas */
.summary-bar {
  display:flex; align-items:center; gap:1.25rem;
  padding:.85rem 1.25rem;
  border-top:1px solid var(--border);
  background:var(--bg);
  flex-wrap:wrap;
}
</style>

<div class="main-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1 class="page-title"><span class="material-icons-round">tune</span> Matières & Volume horaire</h1>
      <p class="page-subtitle">Associez les matières à chaque classe et définissez les volumes horaires hebdomadaires.</p>
    </div>
    <div style="display:flex;gap:.6rem;flex-wrap:wrap">
      <button class="btn btn-outline" onclick="openModal('modalCopy')">
        <span class="material-icons-round">content_copy</span> Copier vers…
      </button>
      <button class="btn btn-outline" style="color:var(--danger);border-color:#FCA5A5" onclick="openModal('modalReset')">
        <span class="material-icons-round">restart_alt</span> Réinitialiser
      </button>
    </div>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msgType ?>" data-auto-dismiss="5000">
    <span class="material-icons-round"><?= $msgType==='danger'?'error_outline':($msgType==='warning'?'warning':'check_circle') ?></span>
    <div class="alert-content"><?= $msg ?></div>
  </div>
  <?php endif; ?>

  <div class="alert alert-info" style="margin-bottom:1.25rem">
    <span class="material-icons-round">info</span>
    <div class="alert-content">
      <strong>Fonctionnement :</strong> activez les matières suivies par chaque classe, définissez le volume horaire hebdomadaire et affectez un professeur.
      Lors de la génération, seules les matières activées seront planifiées, et exactement pour le nombre d'heures défini.
    </div>
  </div>

  <?php if (empty($classes)): ?>
  <div class="card"><div class="card-body">
    <div class="empty-state">
      <div class="empty-state-icon"><span class="material-icons-round">class</span></div>
      <h3>Aucune classe</h3>
      <p>Créez d'abord des <a href="<?= APP_URL ?>/admin/classes.php">classes</a>.</p>
    </div>
  </div></div>
  <?php else: ?>

  <div class="vh-layout">
    <!-- Liste des classes -->
    <div class="card">
      <div class="card-header">
        <div class="card-header-title"><span class="material-icons-round">class</span> Classes</div>
      </div>
      <div class="card-body" style="padding:.65rem">
        <div class="classe-list">
          <?php
          $lastNiv = null;
          foreach ($classes as $idx => $cl):
            $niv = $cl['niveau_nom'] ?: $cl['niveau'] ?: '';
            if ($niv !== $lastNiv):
              $lastNiv = $niv;
          ?>
          <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);padding:.3rem .4rem .15rem;margin-top:<?= $idx?'.4rem':'0' ?>">
            <?= h($niv ?: 'Autres') ?>
          </div>
          <?php endif; ?>
          <div class="classe-item <?= $idx===0?'active':'' ?>"
               onclick="selectClasse(<?= $cl['id'] ?>, this)"
               id="li-<?= $cl['id'] ?>">
            <div style="display:flex;align-items:center;gap:.5rem">
              <span class="material-icons-round" style="font-size:17px">class</span>
              <span style="font-size:.875rem"><?= h($cl['nom']) ?></span>
            </div>
            <div style="display:flex;align-items:center;gap:.4rem">
              <span class="mat-count" id="mc-<?= $cl['id'] ?>"><?= $statsClasse[$cl['id']]['mats'] ?> mat.</span>
              <span style="font-size:.72rem;color:var(--text-muted)" id="hc-<?= $cl['id'] ?>"><?= $statsClasse[$cl['id']]['heures'] ?>h</span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Configuration matières de la classe sélectionnée -->
    <div>
      <?php foreach ($classes as $idx => $cl): ?>
      <div id="panel-<?= $cl['id'] ?>" style="display:<?= $idx===0?'block':'none' ?>">
        <form method="POST" id="form-<?= $cl['id'] ?>">
          <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
          <input type="hidden" name="action" value="save_all">

          <div class="card" style="margin-bottom:0">
            <div class="card-header">
              <div class="card-header-title">
                <span class="material-icons-round" style="color:var(--primary)">tune</span>
                Configuration — <strong><?= h($cl['nom']) ?></strong>
                <?php if ($cl['niveau_nom']): ?>
                <span class="badge badge-secondary" style="margin-left:.3rem"><?= h($cl['niveau_nom']) ?></span>
                <?php endif; ?>
              </div>
              <button type="submit" class="btn btn-primary btn-sm">
                <span class="material-icons-round">save</span> Enregistrer
              </button>
            </div>

            <div class="card-body">
              <?php if (empty($matieres)): ?>
              <div class="empty-state">
                <div class="empty-state-icon"><span class="material-icons-round">menu_book</span></div>
                <h3>Aucune matière</h3>
                <p>Créez d'abord des <a href="<?= APP_URL ?>/admin/matieres.php">matières</a>.</p>
              </div>
              <?php else: ?>
              <div class="mat-grid">
                <?php foreach ($matieres as $m):
                  $isEnabled  = isset($volumes[$cl['id']][$m['id']]);
                  $nbH        = $volumes[$cl['id']][$m['id']] ?? (int)$m['nb_heures_semaine'];
                  $idProfAff  = $affectations[$cl['id']][$m['id']] ?? 0;
                  $profsDisp  = $profsByMat[$m['id']] ?? [];
                ?>
                <div class="mat-card <?= $isEnabled?'enabled':'' ?>" id="mc-<?= $cl['id'] ?>-<?= $m['id'] ?>">
                  <div class="mat-card-header">
                    <!-- Toggle -->
                    <label class="toggle-wrap" title="Activer/désactiver cette matière pour <?= h($cl['nom']) ?>">
                      <div class="toggle-switch <?= $isEnabled?'on':'' ?>"
                           id="toggle-<?= $cl['id'] ?>-<?= $m['id'] ?>"></div>
                      <input type="checkbox"
                             name="vh[<?= $cl['id'] ?>][<?= $m['id'] ?>]"
                             class="mat-toggle-cb"
                             style="display:none"
                             data-classe="<?= $cl['id'] ?>"
                             data-matiere="<?= $m['id'] ?>"
                             data-default-h="<?= $m['nb_heures_semaine'] ?>"
                             <?= $isEnabled?'checked':'' ?>>
                    </label>
                    <!-- Couleur + nom matière -->
                    <div style="display:flex;align-items:center;gap:.5rem;flex:1">
                      <span style="width:10px;height:10px;border-radius:50%;background:<?= h($m['couleur_hex']) ?>;flex-shrink:0"></span>
                      <span style="font-weight:600;font-size:.875rem"><?= h($m['nom']) ?></span>
                    </div>
                    <!-- Heures par semaine -->
                    <div style="display:flex;align-items:center;gap:.35rem;flex-shrink:0">
                      <input type="number"
                             name="vh[<?= $cl['id'] ?>][<?= $m['id'] ?>]"
                             value="<?= $isEnabled ? $nbH : $m['nb_heures_semaine'] ?>"
                             min="1" max="40"
                             class="h-input mat-h-input"
                             id="h-<?= $cl['id'] ?>-<?= $m['id'] ?>"
                             title="Heures / semaine"
                             <?= $isEnabled?'':'disabled' ?>>
                      <span style="font-size:.75rem;color:var(--text-muted)">h/sem</span>
                    </div>
                  </div>
                  <!-- Corps : sélecteur prof -->
                  <div class="mat-card-body <?= $isEnabled?'':'disabled' ?>" id="body-<?= $cl['id'] ?>-<?= $m['id'] ?>">
                    <?php if (empty($profsDisp)): ?>
                    <div style="font-size:.8rem;color:var(--warning);display:flex;align-items:center;gap:.4rem">
                      <span class="material-icons-round" style="font-size:15px">warning</span>
                      Aucun professeur n'enseigne cette matière.
                      <a href="<?= APP_URL ?>/admin/professeurs.php" style="font-size:.78rem">Configurer →</a>
                    </div>
                    <?php else: ?>
                    <div>
                      <label style="font-size:.75rem;font-weight:600;color:var(--text-muted);display:block;margin-bottom:.3rem">
                        Professeur affecté
                      </label>
                      <select name="aff[<?= $cl['id'] ?>][<?= $m['id'] ?>]"
                              class="prof-select"
                              <?= $isEnabled?'':'disabled' ?>>
                        <option value="">— Sélectionner un professeur —</option>
                        <?php foreach ($profsDisp as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $idProfAff===$p['id']?'selected':'' ?>>
                          <?= h($p['prenom'].' '.$p['nom']) ?>
                        </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <?php endif; ?>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
            </div>

            <!-- Barre résumé -->
            <div class="summary-bar">
              <div style="display:flex;align-items:center;gap:.4rem;font-size:.82rem;color:var(--text-muted)">
                <span class="material-icons-round" style="font-size:16px">menu_book</span>
                <span id="sum-mat-<?= $cl['id'] ?>"><strong><?= $statsClasse[$cl['id']]['mats'] ?></strong> matière<?= $statsClasse[$cl['id']]['mats']>1?'s':'' ?> activée<?= $statsClasse[$cl['id']]['mats']>1?'s':'' ?></span>
              </div>
              <div style="display:flex;align-items:center;gap:.4rem;font-size:.82rem;color:var(--text-muted)">
                <span class="material-icons-round" style="font-size:16px">schedule</span>
                <span id="sum-h-<?= $cl['id'] ?>"><strong><?= $statsClasse[$cl['id']]['heures'] ?></strong> heures / semaine</span>
              </div>
              <div style="margin-left:auto">
                <button type="submit" class="btn btn-primary btn-sm">
                  <span class="material-icons-round">save</span> Enregistrer <?= h($cl['nom']) ?>
                </button>
              </div>
            </div>
          </div>
        </form>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Modal Copier -->
<div class="modal-overlay" id="modalCopy">
  <div class="modal" style="max-width:460px">
    <div class="modal-header"><span class="modal-title">Copier la configuration vers d'autres classes</span>
      <button class="modal-close" onclick="closeModal('modalCopy')"><span class="material-icons-round">close</span></button></div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
      <input type="hidden" name="action" value="copy_from">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Classe source *</label>
          <select name="id_classe_source" class="form-control" required>
            <option value="">— Choisir —</option>
            <?php foreach ($classes as $cl): ?>
            <option value="<?= $cl['id'] ?>"><?= h($cl['nom']) ?><?= $cl['niveau_nom']?' — '.h($cl['niveau_nom']):'' ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Classes destinataires *</label>
          <div style="display:flex;flex-direction:column;gap:.3rem;padding:.65rem;border:1.5px solid var(--border);border-radius:var(--radius-sm);max-height:200px;overflow-y:auto;background:#fff">
            <?php foreach ($classes as $cl): ?>
            <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer;font-size:.875rem">
              <input type="checkbox" name="classes_cibles[]" value="<?= $cl['id'] ?>" style="width:14px;height:14px;accent-color:var(--primary)">
              <strong><?= h($cl['nom']) ?></strong>
              <?php if ($cl['niveau_nom']): ?><span style="color:var(--text-muted);font-size:.78rem">— <?= h($cl['niveau_nom']) ?></span><?php endif; ?>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalCopy')">Annuler</button>
        <button type="submit" class="btn btn-primary"><span class="material-icons-round">content_copy</span> Copier</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Réinitialiser -->
<div class="modal-overlay" id="modalReset">
  <div class="modal" style="max-width:400px">
    <div class="modal-header"><span class="modal-title">Réinitialiser toute la configuration</span>
      <button class="modal-close" onclick="closeModal('modalReset')"><span class="material-icons-round">close</span></button></div>
    <div class="modal-body">
      <p style="font-size:.875rem;color:var(--text-muted)">Toutes les associations classe×matière, volumes horaires et affectations de professeurs seront supprimés.</p>
    </div>
    <div class="modal-footer">
      <form method="POST" style="display:flex;gap:.6rem">
        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
        <input type="hidden" name="action" value="reset_all">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalReset')">Annuler</button>
        <button type="submit" class="btn btn-danger"><span class="material-icons-round">restart_alt</span> Réinitialiser</button>
      </form>
    </div>
  </div>
</div>

<script src="<?= APP_URL ?>/assets/js/main.js"></script>
<script>
// ── Sélection de classe ───────────────────────────────────
function selectClasse(id, el) {
  document.querySelectorAll('.classe-item').forEach(i=>i.classList.remove('active'));
  el.classList.add('active');
  document.querySelectorAll('[id^="panel-"]').forEach(p=>p.style.display='none');
  document.getElementById('panel-'+id).style.display='block';
}

// ── Toggle matière on/off ─────────────────────────────────
document.querySelectorAll('.mat-toggle-cb').forEach(cb => {
  // Initialisation visuelle
  syncToggle(cb);

  cb.closest('.toggle-wrap').addEventListener('click', function(e) {
    cb.checked = !cb.checked;
    syncToggle(cb);
    e.stopPropagation();
  });
});

function syncToggle(cb) {
  const idC = cb.dataset.classe;
  const idM = cb.dataset.matiere;
  const on  = cb.checked;
  const toggle = document.getElementById('toggle-'+idC+'-'+idM);
  const card   = document.getElementById('mc-'+idC+'-'+idM);
  const body   = document.getElementById('body-'+idC+'-'+idM);
  const hInput = document.getElementById('h-'+idC+'-'+idM);

  if (toggle) { toggle.classList.toggle('on', on); }
  if (card)   { card.classList.toggle('enabled', on); }
  if (body)   { body.classList.toggle('disabled', !on); }
  if (hInput) {
    hInput.disabled = !on;
    if (!on) hInput.value = cb.dataset.defaultH; // reset to global
  }
  // Activer/désactiver le select prof
  if (body) {
    body.querySelectorAll('select,input').forEach(el => { el.disabled = !on; });
  }
  updateSummary(idC);
}

// ── Mise à jour du résumé en temps réel ──────────────────
function updateSummary(idClasse) {
  const cbs = document.querySelectorAll(`.mat-toggle-cb[data-classe="${idClasse}"]`);
  let nbMats = 0, totalH = 0;
  cbs.forEach(cb => {
    if (cb.checked) {
      nbMats++;
      const idM = cb.dataset.matiere;
      const h = parseInt(document.getElementById('h-'+idClasse+'-'+idM)?.value||0);
      totalH += h;
    }
  });
  const sumMat = document.getElementById('sum-mat-'+idClasse);
  const sumH   = document.getElementById('sum-h-'+idClasse);
  const mc     = document.getElementById('mc-'+idClasse);
  const hc     = document.getElementById('hc-'+idClasse);
  if (sumMat) sumMat.innerHTML = `<strong>${nbMats}</strong> matière${nbMats>1?'s':''} activée${nbMats>1?'s':''}`;
  if (sumH)   sumH.innerHTML   = `<strong>${totalH}</strong> heures / semaine`;
  if (mc)     mc.textContent   = nbMats+' mat.';
  if (hc)     hc.textContent   = totalH+'h';
}

// ── Initialiser les inputs heures (écoute changement) ────
document.querySelectorAll('.mat-h-input').forEach(input => {
  input.addEventListener('input', function() {
    const name  = this.name; // vh[CLASSE][MAT]
    const match = name.match(/vh\[(\d+)\]/);
    if (match) updateSummary(match[1]);
  });
});

// ── Fix: checkbox name doit valoir 0 quand désactivé ─────
// Les formulaires n'envoient pas les checkboxes non cochées,
// donc on ajoute un input hidden 0 pour chaque matière désactivée.
document.querySelectorAll('form[id^="form-"]').forEach(form => {
  form.addEventListener('submit', function(e) {
    // Pour chaque toggle non coché, forcer vh[c][m]=0
    this.querySelectorAll('.mat-toggle-cb:not(:checked)').forEach(cb => {
      const hidden = document.createElement('input');
      hidden.type  = 'hidden';
      hidden.name  = `vh[${cb.dataset.classe}][${cb.dataset.matiere}]`;
      hidden.value = '0';
      this.appendChild(hidden);
    });
  });
});
</script>
</body>
</html>
