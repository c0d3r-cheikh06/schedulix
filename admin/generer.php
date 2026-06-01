<?php
// admin/generer.php — Génération EDT v3 (disponibilités + niveaux)
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();
$currentUser = getCurrentUser();
$pdo  = getDB();
$msg  = ''; $msgType = 'success';
$stats = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $classes        = array_map('intval', $_POST['classes']       ?? []);
    $selectedSalles = array_map('intval', $_POST['salles']        ?? []);
    $heureDebut     = $_POST['heure_debut']  ?? '08:00';
    $heureFin       = $_POST['heure_fin']    ?? '18:00';
    $pauseDeb       = $_POST['pause_debut']  ?? '12:00';
    $pauseFin       = $_POST['pause_fin']    ?? '14:00';
    $dureeCreneau   = max(30, min(180, (int)($_POST['duree_creneau'] ?? 60)));
    $respectDispo   = !empty($_POST['respect_dispo']);
    $respectNiveau  = !empty($_POST['respect_niveau']);

    if (empty($classes)) {
        $msg = 'Veuillez sélectionner au moins une classe.'; $msgType = 'danger';
    } else {
        $versionActuelle = getCurrentVersion();
        $nouvelleVersion = $versionActuelle + 1;
        $jours = getJoursSemaine();

        // ── Construire les créneaux horaires ──────────────────
        $creneaux = [];
        foreach ($jours as $jour) {
            $cur = strtotime($heureDebut);
            $end = strtotime($heureFin);
            while ($cur + $dureeCreneau * 60 <= $end) {
                $hD = date('H:i', $cur);
                $hF = date('H:i', $cur + $dureeCreneau * 60);
                if ($hD >= $pauseDeb && $hD < $pauseFin) { $cur = strtotime($pauseFin); continue; }
                $s = $pdo->prepare('SELECT id FROM creneaux WHERE jour=? AND heure_debut=? AND heure_fin=? LIMIT 1');
                $s->execute([$jour, $hD.':00', $hF.':00']);
                $cr = $s->fetch();
                if (!$cr) {
                    $pdo->prepare('INSERT INTO creneaux (jour,heure_debut,heure_fin) VALUES (?,?,?)')->execute([$jour,$hD.':00',$hF.':00']);
                    $creneaux[] = ['id'=>(int)$pdo->lastInsertId(),'jour'=>$jour,'heure_debut'=>$hD,'heure_fin'=>$hF];
                } else {
                    $creneaux[] = ['id'=>(int)$cr['id'],'jour'=>$jour,'heure_debut'=>$hD,'heure_fin'=>$hF];
                }
                $cur += $dureeCreneau * 60;
            }
        }

        $inserted = 0; $skipped = 0; $skipDispo = 0; $skipNiveau = 0;
        $salleOccupee = []; $profOccupe = []; $classeOccupee = [];

        // Cache des niveaux de classe
        $classeNiveaux = [];
        foreach ($classes as $idC) {
            $row = $pdo->prepare('SELECT id_niveau FROM classes WHERE id=?');
            $row->execute([$idC]);
            $classeNiveaux[$idC] = (int)($row->fetchColumn() ?? 0);
        }

        foreach ($classes as $idClasse) {
            $idNiveauClasse = $classeNiveaux[$idClasse] ?? 0;

            // ══════════════════════════════════════════════════════
            // SOURCE DES MATIÈRES : table affectations (si configurée)
            // Fallback : professeur_matiere (comportement historique)
            // ══════════════════════════════════════════════════════
            $hasAffTable = false;
            try { $pdo->query('SELECT 1 FROM affectations LIMIT 1'); $hasAffTable = true; }
            catch (PDOException $e) {}

            if ($hasAffTable) {
                // Mode v4 : utiliser les affectations classe×matière×prof
                $stmtAff = $pdo->prepare("
                    SELECT a.id_matiere, a.id_professeur,
                           COALESCE(vh.nb_heures_semaine, m.nb_heures_semaine) AS nb_heures_cible
                    FROM affectations a
                    JOIN matieres m ON m.id=a.id_matiere
                    JOIN utilisateurs u ON u.id=a.id_professeur AND u.statut='actif'
                    LEFT JOIN volume_horaire vh ON vh.id_classe=a.id_classe AND vh.id_matiere=a.id_matiere
                    WHERE a.id_classe=?
                ");
                $stmtAff->execute([$idClasse]);
                $matsProfs = $stmtAff->fetchAll();
            } else {
                // Fallback : toutes les paires prof×matière (mode avant v4)
                $stmtMats = $pdo->prepare("
                    SELECT pm.id_matiere, pm.id_professeur,
                           COALESCE(vh.nb_heures_semaine, m.nb_heures_semaine) AS nb_heures_cible
                    FROM professeur_matiere pm
                    JOIN matieres m ON m.id=pm.id_matiere
                    JOIN utilisateurs u ON u.id=pm.id_professeur AND u.statut='actif'
                    LEFT JOIN volume_horaire vh ON vh.id_classe=? AND vh.id_matiere=pm.id_matiere
                ");
                $stmtMats->execute([$idClasse]);
                $matsProfs = $stmtMats->fetchAll();
            }

            // Clé de déduplication : une seule entrée par matière pour cette classe
            // (évite le bug de doublement quand plusieurs profs enseignent la même matière)
            $matieresPlanifiees = []; // [idMatiere] = nbHeuresDejaPlacees

            foreach ($matsProfs as $mp) {
                $idProf    = (int)$mp['id_professeur'];
                $idMatiere = (int)$mp['id_matiere'];

                // ── Filtre niveau ──────────────────────────────────
                if ($respectNiveau && $idNiveauClasse > 0) {
                    if (!profAutoriseNiveau($idProf, $idNiveauClasse)) {
                        $skipNiveau++;
                        continue;
                    }
                }

                // ── Volume cible STRICT pour ce couple classe×matière ──
                // nb_heures_cible est en HEURES. On le convertit en nombre de SÉANCES
                // selon la durée du créneau choisi (ex : 4h ÷ 2h/créneau = 2 séances).
                $nbHeuresMatiere  = (int)$mp['nb_heures_cible'];
                $dureeCreneauH    = $dureeCreneau / 60.0;          // minutes → heures
                $nbSeancesCible   = (int)ceil($nbHeuresMatiere / $dureeCreneauH);

                // ── Déduplication : ne pas replanifier une matière déjà complète ──
                $seancesDejaPlacees = $matieresPlanifiees[$idMatiere] ?? 0;
                if ($seancesDejaPlacees >= $nbSeancesCible) continue;

                foreach ($creneaux as $cr) {
                    // Vérifier combien de séances on a déjà placé pour cette matière dans cette classe
                    $seancesDejaPlacees = $matieresPlanifiees[$idMatiere] ?? 0;
                    if ($seancesDejaPlacees >= $nbSeancesCible) break;

                    $key = $cr['id'];
                    if (!empty($classeOccupee[$idClasse][$key])) continue;
                    if (!empty($profOccupe[$idProf][$key]))       continue;

                    // ── Filtre disponibilité ──────────────────────
                    if ($respectDispo) {
                        if (!profEstDisponible($idProf, $cr['jour'], $cr['heure_debut'].':00', $cr['heure_fin'].':00')) {
                            $skipDispo++;
                            continue;
                        }
                    }

                    // Trouver salle libre
                    $idSalle = null;
                    foreach ($selectedSalles ?: array_column(getSalles(), 'id') as $sId) {
                        if (empty($salleOccupee[$sId][$key])) { $idSalle = $sId; break; }
                    }
                    if (!$idSalle) { $skipped++; continue; }

                    // Vérification doublon strict BDD
                    $chkDbl = $pdo->prepare("
                        SELECT id FROM emplois_du_temps
                        WHERE version=? AND id_creneau=?
                          AND (id_salle=? OR id_professeur=? OR id_classe=?)
                        LIMIT 1
                    ");
                    $chkDbl->execute([$nouvelleVersion,$cr['id'],$idSalle,$idProf,$idClasse]);
                    if ($chkDbl->fetch()) { $skipped++; continue; }

                    $pdo->prepare("INSERT INTO emplois_du_temps
                        (version,id_classe,id_matiere,id_professeur,id_salle,id_creneau,statut)
                        VALUES (?,?,?,?,?,?,'provisoire')")
                        ->execute([$nouvelleVersion,$idClasse,$idMatiere,$idProf,$idSalle,$cr['id']]);

                    $salleOccupee[$idSalle][$key]         = true;
                    $profOccupe[$idProf][$key]             = true;
                    $classeOccupee[$idClasse][$key]        = true;
                    $matieresPlanifiees[$idMatiere]        = ($matieresPlanifiees[$idMatiere] ?? 0) + 1; // +1 séance placée
                    $inserted++;
                }
            }
        }

        // Notifications internes
        $stmtP = $pdo->prepare("SELECT DISTINCT id_professeur FROM emplois_du_temps WHERE version=?");
        $stmtP->execute([$nouvelleVersion]);
        foreach ($stmtP->fetchAll() as $r) {
            sendNotification($r['id_professeur'],"Nouveau planning v{$nouvelleVersion}",
                "L'emploi du temps version {$nouvelleVersion} a été généré. Veuillez valider vos créneaux.",'info');
        }
        sendNotification($currentUser['id'],"Génération terminée (v{$nouvelleVersion})",
            "{$inserted} créneaux générés. {$skipped} conflits. {$skipDispo} ignorés (disponibilités). {$skipNiveau} ignorés (niveaux).",'success');

        // Emails
        $classesAff = [];
        foreach ($classes as $idC) {
            $r = $pdo->prepare('SELECT * FROM classes WHERE id=?'); $r->execute([$idC]);
            $cl = $r->fetch(); if ($cl) $classesAff[] = $cl;
        }
        $emailResult = notifyEdtUpdate($nouvelleVersion, $classesAff);

        // ── Rapport de couverture strict ─────────────────────────────
        $rapportManquants = [];
        $hasAffTableRapport = false;
        try { $pdo->query('SELECT 1 FROM affectations LIMIT 1'); $hasAffTableRapport = true; }
        catch (PDOException $e) {}

        foreach ($classes as $idClasse) {
            $clRow = $pdo->prepare('SELECT nom FROM classes WHERE id=? LIMIT 1');
            $clRow->execute([$idClasse]);
            $clNom = $clRow->fetchColumn();

            if ($hasAffTableRapport) {
                // Mode v4 : seules les matières configurées dans affectations
                $stmtR = $pdo->prepare("
                    SELECT a.id_matiere, m.nom AS mat_nom,
                           COALESCE(vh.nb_heures_semaine, m.nb_heures_semaine) AS cible
                    FROM affectations a
                    JOIN matieres m ON m.id=a.id_matiere
                    LEFT JOIN volume_horaire vh ON vh.id_classe=a.id_classe AND vh.id_matiere=a.id_matiere
                    WHERE a.id_classe=?
                    GROUP BY a.id_matiere
                ");
                $stmtR->execute([$idClasse]);
            } else {
                $stmtR = $pdo->prepare("
                    SELECT pm.id_matiere, m.nom AS mat_nom,
                           COALESCE(vh.nb_heures_semaine, m.nb_heures_semaine) AS cible
                    FROM professeur_matiere pm
                    JOIN matieres m ON m.id=pm.id_matiere
                    LEFT JOIN volume_horaire vh ON vh.id_classe=? AND vh.id_matiere=pm.id_matiere
                    GROUP BY pm.id_matiere
                ");
                $stmtR->execute([$idClasse]);
            }

            foreach ($stmtR->fetchAll() as $row) {
                // Convertir les heures en séances selon la durée du créneau
                $cibleH       = (int)$row['cible'];
                $dureeH       = $dureeCreneau / 60.0;
                $cibleSeances = (int)ceil($cibleH / $dureeH);
                $stmtCnt = $pdo->prepare("SELECT COUNT(*) FROM emplois_du_temps WHERE version=? AND id_classe=? AND id_matiere=?");
                $stmtCnt->execute([$nouvelleVersion, $idClasse, $row['id_matiere']]);
                $place = (int)$stmtCnt->fetchColumn();
                if ($place !== $cibleSeances) {
                    $rapportManquants[] = [
                        'classe'  => $clNom,
                        'matiere' => $row['mat_nom'],
                        'cible'   => $cibleH,          // afficher en heures
                        'seances' => $cibleSeances,    // en séances
                        'place'   => $place,
                        'manque'  => $place < $cibleSeances ? $cibleSeances - $place : 0,
                        'surplus' => $place > $cibleSeances ? $place - $cibleSeances : 0,
                    ];
                }
            }
        }

        $stats = [
            'version'           => $nouvelleVersion,
            'inserted'          => $inserted,
            'skipped'           => $skipped,
            'skip_dispo'        => $skipDispo,
            'skip_niveau'       => $skipNiveau,
            'email_sent'        => $emailResult['sent'],
            'email_errors'      => $emailResult['errors'],
            'rapport_manquants' => $rapportManquants,
        ];
    }
}

$allClasses = getClassesWithNiveau();
$allSalles  = getSalles();
$allNiveaux = getNiveaux();
$version    = getCurrentVersion();

$pageTitle = 'Générer un emploi du temps'; $activeMenu = 'generer';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_admin.php';
?>

<div class="main-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1 class="page-title"><span class="material-icons-round">auto_fix_high</span> Générer un emploi du temps</h1>
      <p class="page-subtitle">Génération automatique intelligente avec contraintes</p>
    </div>
    <?php if ($version > 0): ?>
    <span class="badge badge-primary" style="font-size:.875rem;padding:.4rem .85rem">Version actuelle : <?= $version ?></span>
    <?php endif; ?>
  </div>

  <?php if ($msg && !$stats): ?>
  <div class="alert alert-<?= $msgType ?>">
    <span class="material-icons-round">error_outline</span>
    <div class="alert-content"><?= $msg ?></div>
  </div>
  <?php endif; ?>

  <?php if ($stats): ?>
  <div class="card" style="border-left:4px solid var(--success);margin-bottom:1.5rem">
    <div class="card-body">
      <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.25rem">
        <div style="width:48px;height:48px;background:var(--success-lt);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <span class="material-icons-round" style="color:var(--success);font-size:24px">check_circle</span>
        </div>
        <div>
          <div style="font-weight:700;font-size:1.05rem">Emploi du temps v<?= $stats['version'] ?> généré</div>
          <div style="font-size:.875rem;color:var(--text-muted)">Résultats de la génération automatique</div>
        </div>
      </div>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:.85rem;margin-bottom:1rem">
        <?php
        $statCards = [
            [$stats['inserted'],    'Créneaux placés',   'success'],
            [$stats['skipped'],     'Conflits évités',   'warning'],
            [$stats['skip_dispo'],  'Hors disponibilité','orange'],
            [$stats['skip_niveau'], 'Hors niveau',       'indigo'],
            [$stats['email_sent'],  'Emails envoyés',    'blue'],
        ];
        foreach ($statCards as [$val,$lbl,$col]):
        ?>
        <div style="background:var(--bg);border-radius:var(--radius);padding:.85rem;text-align:center;border:1px solid var(--border)">
          <div style="font-size:1.5rem;font-weight:700;color:var(--<?= $col==='orange'?'warning':$col ?>)"><?= $val ?></div>
          <div style="font-size:.72rem;color:var(--text-muted)"><?= $lbl ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php if ($stats['email_sent'] > 0): ?>
      <div class="alert alert-success" style="margin-bottom:.75rem">
        <span class="material-icons-round">email</span>
        <div class="alert-content">Professeurs et élèves notifiés par email avec succès.</div>
      </div>
      <?php elseif ($stats['email_errors'] > 0): ?>
      <div class="alert alert-warning" style="margin-bottom:.75rem">
        <span class="material-icons-round">warning</span>
        <div class="alert-content">Certaines notifications n'ont pas pu être envoyées. Vérifiez la configuration SMTP.</div>
      </div>
      <?php endif; ?>

      <?php if (!empty($stats['rapport_manquants'])): ?>
      <div class="alert alert-warning" style="margin-bottom:.75rem;flex-direction:column;align-items:flex-start">
        <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.6rem">
          <span class="material-icons-round" style="color:var(--warning)">warning</span>
          <strong>Heures non planifiées — <?= count($stats['rapport_manquants']) ?> couple(s) classe×matière non conformes</strong>
        </div>
        <div style="width:100%;overflow-x:auto">
          <table style="width:100%;border-collapse:collapse;font-size:.8rem">
            <thead>
              <tr style="background:rgba(0,0,0,.05)">
                <th style="padding:.4rem .75rem;text-align:left;font-weight:600">Classe</th>
                <th style="padding:.4rem .75rem;text-align:left;font-weight:600">Matière</th>
                <th style="padding:.4rem .75rem;text-align:center;font-weight:600">Cible (h)</th>
                <th style="padding:.4rem .75rem;text-align:center;font-weight:600">Cible (séances)</th>
                <th style="padding:.4rem .75rem;text-align:center;font-weight:600">Placées</th>
                <th style="padding:.4rem .75rem;text-align:center;font-weight:600">Écart</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($stats['rapport_manquants'] as $r): ?>
              <tr style="border-top:1px solid rgba(0,0,0,.07)">
                <td style="padding:.35rem .75rem;font-weight:600"><?= h($r['classe']) ?></td>
                <td style="padding:.35rem .75rem"><?= h($r['matiere']) ?></td>
                <td style="padding:.35rem .75rem;text-align:center;font-weight:700;color:var(--primary)"><?= $r['cible'] ?>h</td>
                <td style="padding:.35rem .75rem;text-align:center;color:var(--text-muted)"><?= $r['seances'] ?? '—' ?> séances</td>
                <td style="padding:.35rem .75rem;text-align:center"><?= $r['place'] ?> séances</td>
                <td style="padding:.35rem .75rem;text-align:center">
                  <?php if ($r['surplus'] > 0): ?>
                  <span style="background:var(--warning-lt);color:var(--warning);font-weight:700;padding:.1rem .4rem;border-radius:99px">
                    +<?= $r['surplus'] ?>h en trop
                  </span>
                  <?php else: ?>
                  <span style="background:var(--danger-lt);color:var(--danger);font-weight:700;padding:.1rem .4rem;border-radius:99px">
                    -<?= $r['manque'] ?>h manquantes
                  </span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <p style="font-size:.78rem;margin-top:.6rem;color:#92400E">
          Causes possibles : créneaux insuffisants, conflits, disponibilités restrictives ou volume horaire mal configuré. Vérifiez la page <strong>Matières &amp; Volume horaire</strong>.
          Ajoutez des créneaux ou assouplissez les contraintes, puis regénérez.
        </p>
      </div>
      <?php else: ?>
      <div class="alert alert-success" style="margin-bottom:.75rem">
        <span class="material-icons-round">check_circle</span>
        <div class="alert-content"><strong>Couverture complète.</strong> Toutes les heures prévues ont été planifiées.</div>
      </div>
      <?php endif; ?>

      <div style="display:flex;gap:.75rem;margin-top:1rem">
        <a href="<?= APP_URL ?>/admin/suivi_validations.php" class="btn btn-primary">
          <span class="material-icons-round">fact_check</span> Suivre les validations
        </a>
        <a href="<?= APP_URL ?>/admin/emplois_du_temps.php" class="btn btn-outline">
          <span class="material-icons-round">calendar_month</span> Voir l'EDT
        </a>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 380px;gap:1.5rem;align-items:start" class="gen-grid">
    <form method="POST" id="genForm">
      <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

      <!-- Classes groupées par niveau -->
      <div class="card" style="margin-bottom:1.25rem">
        <div class="card-header">
          <div class="card-header-title"><span class="material-icons-round">class</span> Classes à planifier</div>
          <button type="button" class="btn btn-sm btn-outline" onclick="toggleAll('classes[]')">Tout sélectionner</button>
        </div>
        <div class="card-body">
          <?php if (empty($allClasses)): ?>
          <div class="alert alert-warning" style="margin:0">
            <span class="material-icons-round">warning</span>
            <div class="alert-content">Aucune classe. <a href="<?= APP_URL ?>/admin/classes.php">Ajoutez-en</a> d'abord.</div>
          </div>
          <?php else:
            // Grouper par niveau
            $byNiveau = ['(Sans niveau)' => []];
            foreach ($allClasses as $c) {
                $key = $c['niveau_nom'] ?? '(Sans niveau)';
                $byNiveau[$key][] = $c;
            }
            foreach ($byNiveau as $nivNom => $cls): if (empty($cls)) continue; ?>
            <div style="margin-bottom:1rem">
              <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:.5rem;display:flex;align-items:center;gap:.4rem">
                <span class="material-icons-round" style="font-size:14px">school</span><?= h($nivNom) ?>
              </div>
              <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:.5rem">
                <?php foreach ($cls as $c): ?>
                <label style="display:flex;align-items:center;gap:.55rem;padding:.55rem .75rem;border:1.5px solid var(--border);border-radius:var(--radius-sm);cursor:pointer;font-size:.85rem;font-weight:500" class="sel-card">
                  <input type="checkbox" name="classes[]" value="<?= $c['id'] ?>" style="display:none" class="sel-cb">
                  <span class="material-icons-round" style="font-size:17px;color:var(--text-muted)">class</span>
                  <?= h($c['nom']) ?>
                </label>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
      </div>

      <!-- Salles -->
      <div class="card" style="margin-bottom:1.25rem">
        <div class="card-header">
          <div class="card-header-title"><span class="material-icons-round">meeting_room</span> Salles disponibles</div>
          <button type="button" class="btn btn-sm btn-outline" onclick="toggleAll('salles[]')">Tout sélectionner</button>
        </div>
        <div class="card-body">
          <?php if (empty($allSalles)): ?>
          <div class="alert alert-warning" style="margin:0"><span class="material-icons-round">warning</span>
            <div class="alert-content">Aucune salle. <a href="<?= APP_URL ?>/admin/salles.php">Ajoutez-en</a>.</div></div>
          <?php else: ?>
          <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:.5rem">
            <?php foreach ($allSalles as $s): ?>
            <label style="display:flex;align-items:center;gap:.55rem;padding:.55rem .75rem;border:1.5px solid var(--border);border-radius:var(--radius-sm);cursor:pointer;font-size:.85rem;font-weight:500" class="sel-card">
              <input type="checkbox" name="salles[]" value="<?= $s['id'] ?>" style="display:none" class="sel-cb">
              <span class="material-icons-round" style="font-size:17px;color:var(--text-muted)">meeting_room</span>
              <div><div><?= h($s['nom']) ?></div><div style="font-size:.7rem;color:var(--text-muted)"><?= $s['capacite'] ?> pl.</div></div>
            </label>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center"
              onclick="showSpinner('Génération en cours…')">
        <span class="material-icons-round">auto_fix_high</span> Lancer la génération
      </button>
    </form>

    <!-- Paramètres -->
    <div>
      <div class="card" style="margin-bottom:1.25rem">
        <div class="card-header">
          <div class="card-header-title"><span class="material-icons-round">schedule</span> Paramètres horaires</div>
        </div>
        <div class="card-body">
          <div class="form-grid">
            <div class="form-group"><label class="form-label">Heure de début</label>
              <input type="time" name="heure_debut" form="genForm" class="form-control" value="08:00"></div>
            <div class="form-group"><label class="form-label">Heure de fin</label>
              <input type="time" name="heure_fin" form="genForm" class="form-control" value="18:00"></div>
          </div>
          <div class="form-grid">
            <div class="form-group"><label class="form-label">Début pause</label>
              <input type="time" name="pause_debut" form="genForm" class="form-control" value="12:00"></div>
            <div class="form-group"><label class="form-label">Fin pause</label>
              <input type="time" name="pause_fin" form="genForm" class="form-control" value="14:00"></div>
          </div>
          <div class="form-group" style="margin-bottom:0"><label class="form-label">Durée d'un créneau</label>
            <select name="duree_creneau" form="genForm" class="form-control">
              <option value="45">45 min</option>
              <option value="60" selected>1 heure</option>
              <option value="90">1h30</option>
              <option value="120">2 heures</option>
            </select></div>
        </div>
      </div>

      <!-- Contraintes intelligentes -->
      <div class="card" style="margin-bottom:1.25rem">
        <div class="card-header">
          <div class="card-header-title"><span class="material-icons-round">tune</span> Contraintes intelligentes</div>
        </div>
        <div class="card-body">
          <label style="display:flex;align-items:flex-start;gap:.75rem;cursor:pointer;margin-bottom:1rem">
            <input type="checkbox" name="respect_dispo" form="genForm" value="1" checked
                   style="width:16px;height:16px;margin-top:2px;accent-color:var(--primary)">
            <div>
              <div style="font-weight:600;font-size:.875rem">Respecter les disponibilités</div>
              <div style="font-size:.78rem;color:var(--text-muted)">
                Les professeurs ne seront planifiés que sur leurs créneaux disponibles déclarés.
              </div>
            </div>
          </label>
          <label style="display:flex;align-items:flex-start;gap:.75rem;cursor:pointer">
            <input type="checkbox" name="respect_niveau" form="genForm" value="1" checked
                   style="width:16px;height:16px;margin-top:2px;accent-color:var(--primary)">
            <div>
              <div style="font-weight:600;font-size:.875rem">Respecter les niveaux autorisés</div>
              <div style="font-size:.78rem;color:var(--text-muted)">
                Un professeur ne sera affecté qu'aux classes dont le niveau lui est attribué.
              </div>
            </div>
          </label>
        </div>
      </div>

      <!-- Info -->
      <div class="card" style="border-left:4px solid var(--primary)">
        <div class="card-body">
          <div style="display:flex;gap:.75rem;align-items:flex-start">
            <span class="material-icons-round" style="color:var(--primary);margin-top:.1rem">info</span>
            <div>
              <div style="font-weight:600;font-size:.875rem;margin-bottom:.5rem">Génération v3</div>
              <ul style="font-size:.8rem;color:var(--text-muted);list-style:none;display:flex;flex-direction:column;gap:.3rem">
                <li>✓ Conflits salle/prof/classe détectés</li>
                <li>✓ Disponibilités des professeurs respectées</li>
                <li>✓ Niveaux scolaires autorisés vérifiés</li>
                <li>✓ Notifications email automatiques</li>
                <li>✓ Statut provisoire — validation requise</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.sel-card:has(.sel-cb:checked) { background:var(--primary-lt);border-color:var(--primary); }
.sel-card:has(.sel-cb:checked) .material-icons-round { color:var(--primary); }
@media(max-width:900px){.gen-grid{grid-template-columns:1fr!important}}
</style>
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
<script>
function toggleAll(name) {
  const cbs = document.querySelectorAll(`input[name="${name}"]`);
  const any = [...cbs].some(c => !c.checked);
  cbs.forEach(cb => { cb.checked = any; });
}
document.querySelectorAll('.sel-card').forEach(card => {
  card.addEventListener('click', () => { card.querySelector('.sel-cb').checked = !card.querySelector('.sel-cb').checked; });
});
</script>
</body></html>
