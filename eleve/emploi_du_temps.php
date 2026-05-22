<?php
// eleve/emploi_du_temps.php — v3 impression portrait améliorée
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireEleve();
$currentUser = getCurrentUser();
$pdo = getDB();

$idClasse = $currentUser['id_classe'] ?? 0;
$version  = getCurrentVersion();
$jours    = getJoursSemaine();

$classe = null;
if ($idClasse) {
    $s = $pdo->prepare('SELECT c.*, n.nom AS niveau_nom FROM classes c LEFT JOIN niveaux n ON n.id=c.id_niveau WHERE c.id=? LIMIT 1');
    $s->execute([$idClasse]);
    $classe = $s->fetch();
}

$rows = [];
if ($idClasse && $version > 0) {
    $stmt = $pdo->prepare("
        SELECT e.*, m.nom AS mat_nom, m.couleur_hex,
               s.nom AS salle_nom,
               u.nom AS prof_nom, u.prenom AS prof_prenom,
               c.jour, c.heure_debut, c.heure_fin, e.statut
        FROM emplois_du_temps e
        JOIN matieres m     ON m.id=e.id_matiere
        JOIN salles s       ON s.id=e.id_salle
        JOIN utilisateurs u ON u.id=e.id_professeur
        JOIN creneaux c     ON c.id=e.id_creneau
        WHERE e.version=? AND e.id_classe=? AND e.statut IN ('valide','confirme')
        ORDER BY c.heure_debut
    ");
    $stmt->execute([$version, $idClasse]);
    $rows = $stmt->fetchAll();
}

// Grille horaire
$creneaux = [];
foreach ($rows as $r) {
    $key = $r['heure_debut'].'-'.$r['heure_fin'];
    if (!isset($creneaux[$key])) {
        $creneaux[$key] = ['heure_debut'=>$r['heure_debut'],'heure_fin'=>$r['heure_fin']];
        foreach ($jours as $j) $creneaux[$key][$j] = null;
    }
    $creneaux[$key][$r['jour']] = $r;
}
ksort($creneaux);

$parJour = array_fill_keys($jours,[]);
foreach ($rows as $r) $parJour[$r['jour']][] = $r;

$matieresSeen = []; $legendeItems = [];
foreach ($rows as $r) {
    if (!in_array($r['id_matiere'],$matieresSeen)) {
        $matieresSeen[] = $r['id_matiere'];
        $legendeItems[] = $r;
    }
}

$nbCours    = count($rows);
$nbMatieres = count($legendeItems);
$nbProfs    = count(array_unique(array_column($rows,'id_professeur')));

$pageTitle = 'Mon emploi du temps'; $activeMenu = 'emploi_du_temps';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_eleve.php';
?>
<style>
/* ══ IMPRESSION PORTRAIT — A4 ══════════════════════════════ */
@media print {
  #pageLoader,.topbar,.sidebar,.sidebar-overlay,
  #toast-container,.spinner-overlay,.no-print { display:none!important; }

  * { -webkit-print-color-adjust:exact!important; print-color-adjust:exact!important; }
  body { background:#fff!important; font-family:Arial,Helvetica,sans-serif!important; font-size:9pt; }
  .main-content { margin:0!important; padding:0!important; }

  #print-zone { display:block!important; width:100%; }

  /* En-tête établissement */
  .ph-wrap {
    display:flex!important; align-items:center;
    justify-content:space-between;
    padding-bottom:10pt; margin-bottom:14pt;
    border-bottom:2.5pt solid #1A56DB;
  }
  .ph-logo {
    width:44pt; height:44pt;
    background:#1A56DB!important;
    border-radius:10pt;
    display:flex!important; align-items:center; justify-content:center;
    flex-shrink:0;
  }
  .ph-logo span { font-size:24pt; color:#fff; }
  .ph-info { flex:1; padding-left:10pt; }
  .ph-info h1 { font-size:14pt; font-weight:700; color:#111827; margin:0 0 2pt; }
  .ph-info p  { font-size:8.5pt; color:#6B7280; margin:0; }
  .ph-meta    { text-align:right; }
  .ph-meta .app-name { font-size:12pt; font-weight:700; color:#1A56DB; }
  .ph-meta p  { font-size:7.5pt; color:#9CA3AF; margin:2pt 0 0; }

  /* Tableau principal */
  .pt-table { width:100%; border-collapse:collapse; margin-bottom:10pt; }
  .pt-table th {
    background:#1A56DB!important;
    color:#fff!important; font-weight:700;
    font-size:8pt; padding:5.5pt 4pt;
    text-align:center; border:1pt solid #1346C0;
    text-transform:uppercase; letter-spacing:.3pt;
  }
  .pt-table th.th-time { text-align:left; width:52pt; background:#0F1C3F!important; }
  .pt-table td {
    border:1pt solid #E5E9F2;
    padding:0; vertical-align:top;
    height:36pt; min-height:36pt;
  }
  .pt-table td.td-time {
    font-size:7.5pt; font-weight:700; color:#374151;
    background:#F8F9FC!important;
    text-align:center; padding:4pt 3pt;
    border-right:2pt solid #C8D0E0;
    white-space:nowrap;
  }
  .pt-table tr:nth-child(even) td.td-time { background:#F0F4FB!important; }

  /* Cellule cours */
  .pc {
    height:100%; padding:3pt 4pt;
    border-left:3pt solid transparent;
  }
  .pc-mat  { font-weight:700; font-size:7.5pt; color:#111827; margin-bottom:1.5pt; }
  .pc-prof { font-size:6.5pt; color:#374151; margin-bottom:1pt; }
  .pc-room { font-size:6.5pt; color:#6B7280; }

  /* Libre */
  .td-free { background:#FAFAFA!important; }

  /* Légende */
  .pl-wrap {
    display:flex!important; flex-wrap:wrap;
    gap:5pt 10pt; margin-top:8pt;
    padding-top:6pt; border-top:1pt solid #E5E9F2;
  }
  .pl-item { display:flex!important; align-items:center; gap:3.5pt; font-size:7pt; color:#374151; }
  .pl-dot  { width:7pt; height:7pt; border-radius:50%; flex-shrink:0; }

  /* Résumé stats */
  .pstat-row {
    display:flex!important; gap:8pt; margin-bottom:10pt;
  }
  .pstat {
    flex:1; border:1pt solid #E5E9F2; border-radius:5pt;
    padding:6pt 8pt; text-align:center;
  }
  .pstat-val { font-size:14pt; font-weight:700; color:#1A56DB; }
  .pstat-lbl { font-size:6.5pt; color:#6B7280; }

  /* Footer */
  .pf-wrap {
    display:flex!important; justify-content:space-between;
    margin-top:8pt; padding-top:6pt;
    border-top:1pt solid #E5E9F2;
    font-size:7pt; color:#9CA3AF;
  }

  @page {
    size: A4 portrait;
    margin: 13mm 11mm 13mm 11mm;
  }
}
@media screen { #print-zone { display:none; } }
</style>

<div class="main-content">
  <div class="page-header no-print">
    <div class="page-header-left">
      <h1 class="page-title"><span class="material-icons-round">calendar_month</span> Mon emploi du temps</h1>
      <p class="page-subtitle">
        <?php if ($classe): ?>Classe : <strong><?= h($classe['nom']) ?></strong>
          <?php if (!empty($classe['niveau_nom'])): ?> — <?= h($classe['niveau_nom']) ?><?php endif; ?>
        <?php else: ?>Aucune classe assignée<?php endif; ?>
        <?php if ($version > 0): ?> · Version <?= $version ?><?php endif; ?>
      </p>
    </div>
    <div style="display:flex;gap:.6rem;align-items:center">
      <?php if ($version > 0 && !empty($rows)): ?>
      <span class="badge badge-success"><span class="material-icons-round" style="font-size:14px">verified</span> Validé</span>
      <button class="btn btn-outline" onclick="window.print()">
        <span class="material-icons-round">print</span> Imprimer
      </button>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!$idClasse): ?>
  <div class="alert alert-warning no-print">
    <span class="material-icons-round">warning</span>
    <div class="alert-content"><strong>Classe non assignée.</strong> Contactez votre administrateur.</div>
  </div>
  <?php elseif (empty($rows)): ?>
  <div class="card no-print"><div class="card-body">
    <div class="empty-state">
      <div class="empty-state-icon"><span class="material-icons-round">calendar_today</span></div>
      <h3>Emploi du temps non disponible</h3>
      <p>Votre emploi du temps n'a pas encore été publié. Revenez ultérieurement.</p>
    </div>
  </div></div>
  <?php else: ?>

  <!-- Stats écran -->
  <div class="stat-grid no-print" style="margin-bottom:1.5rem">
    <div class="stat-card">
      <div class="stat-icon blue"><span class="material-icons-round">calendar_month</span></div>
      <div><div class="stat-value"><?= $nbCours ?></div><div class="stat-label">Cours / semaine</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon teal"><span class="material-icons-round">menu_book</span></div>
      <div><div class="stat-value"><?= $nbMatieres ?></div><div class="stat-label">Matières</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon green"><span class="material-icons-round">person</span></div>
      <div><div class="stat-value"><?= $nbProfs ?></div><div class="stat-label">Professeurs</div></div>
    </div>
  </div>

  <!-- Grille horaire écran -->
  <div class="card no-print" style="margin-bottom:1.5rem">
    <div class="card-header">
      <div class="card-header-title"><span class="material-icons-round">view_week</span> Planning de la semaine</div>
    </div>
    <div style="overflow-x:auto">
      <table class="edt-grid-table">
        <thead>
          <tr>
            <th class="time-col">Horaire</th>
            <?php foreach ($jours as $j): ?><th><?= $j ?></th><?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($creneaux as $data): ?>
          <tr>
            <td class="time-col">
              <?= formatHeure($data['heure_debut']) ?><br>
              <span style="color:var(--text-light);font-size:.75rem"><?= formatHeure($data['heure_fin']) ?></span>
            </td>
            <?php foreach ($jours as $j): $cell=$data[$j]??null; ?>
            <td>
              <?php if ($cell): ?>
              <div class="edt-cell" style="background:<?= h($cell['couleur_hex']) ?>1A;border-left:3px solid <?= h($cell['couleur_hex']) ?>">
                <div class="edt-cell-mat"><?= h($cell['mat_nom']) ?></div>
                <div class="edt-cell-info"><span class="material-icons-round">person</span><?= h($cell['prof_prenom'].' '.$cell['prof_nom']) ?></div>
                <div class="edt-cell-info"><span class="material-icons-round">meeting_room</span><?= h($cell['salle_nom']) ?></div>
              </div>
              <?php else: ?><div style="min-height:58px"></div><?php endif; ?>
            </td>
            <?php endforeach; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Légende écran -->
  <div class="card no-print">
    <div class="card-header"><div class="card-header-title"><span class="material-icons-round">palette</span> Légende</div></div>
    <div class="card-body" style="display:flex;flex-wrap:wrap;gap:.6rem">
      <?php foreach ($legendeItems as $r): ?>
      <div style="display:flex;align-items:center;gap:.5rem;padding:.35rem .75rem;border:1px solid var(--border);border-radius:99px;background:#fff">
        <span class="color-dot" style="background:<?= h($r['couleur_hex']) ?>"></span>
        <span style="font-size:.8rem;font-weight:500"><?= h($r['mat_nom']) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- ══ ZONE D'IMPRESSION PORTRAIT ══════════════════════════ -->
<?php if (!empty($rows)): ?>
<div id="print-zone">

  <!-- En-tête -->
  <div class="ph-wrap">
    <div class="ph-logo"><span>🎓</span></div>
    <div class="ph-info">
      <h1>Emploi du temps — <?= $classe ? h($classe['nom']) : 'Ma classe' ?></h1>
      <p>
        <?php if (!empty($classe['niveau_nom'])): ?><?= h($classe['niveau_nom']) ?> · <?php endif; ?>
        Version <?= $version ?>
        <?php if ($classe): ?> · Capacité : <?= $classe['capacite'] ?> élèves<?php endif; ?>
        · Année scolaire <?= date('Y').'/'.((int)date('Y')+1) ?>
      </p>
    </div>
    <div class="ph-meta">
      <div class="app-name">EduSchedule</div>
      <p>Imprimé le <?= date('d/m/Y à H:i') ?></p>
    </div>
  </div>

  <!-- Stats -->
  <div class="pstat-row">
    <div class="pstat"><div class="pstat-val"><?= $nbCours ?></div><div class="pstat-lbl">Cours / semaine</div></div>
    <div class="pstat"><div class="pstat-val"><?= $nbMatieres ?></div><div class="pstat-lbl">Matières</div></div>
    <div class="pstat"><div class="pstat-val"><?= $nbProfs ?></div><div class="pstat-lbl">Professeurs</div></div>
    <div class="pstat"><div class="pstat-val"><?= count($jours) ?></div><div class="pstat-lbl">Jours / semaine</div></div>
  </div>

  <!-- Tableau -->
  <table class="pt-table">
    <thead>
      <tr>
        <th class="th-time">Horaire</th>
        <?php foreach ($jours as $j): ?><th><?= $j ?></th><?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($creneaux as $data): ?>
      <tr>
        <td class="td-time">
          <?= formatHeure($data['heure_debut']) ?><br>
          <span style="font-weight:400;color:#9CA3AF"><?= formatHeure($data['heure_fin']) ?></span>
        </td>
        <?php foreach ($jours as $j): $cell=$data[$j]??null; ?>
        <td class="<?= $cell?'':'td-free' ?>">
          <?php if ($cell): ?>
          <div class="pc" style="background:<?= h($cell['couleur_hex']) ?>15;border-left-color:<?= h($cell['couleur_hex']) ?>">
            <div class="pc-mat"><?= h($cell['mat_nom']) ?></div>
            <div class="pc-prof">👤 <?= h($cell['prof_prenom'].' '.$cell['prof_nom']) ?></div>
            <div class="pc-room">📍 <?= h($cell['salle_nom']) ?></div>
          </div>
          <?php endif; ?>
        </td>
        <?php endforeach; ?>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Légende -->
  <div class="pl-wrap">
    <span style="font-size:7pt;font-weight:700;color:#374151;margin-right:4pt">Légende :</span>
    <?php foreach ($legendeItems as $r): ?>
    <div class="pl-item">
      <div class="pl-dot" style="background:<?= h($r['couleur_hex']) ?>"></div>
      <?= h($r['mat_nom']) ?>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Footer -->
  <div class="pf-wrap">
    <span><?= h($currentUser['prenom'].' '.$currentUser['nom']) ?> — <?= $classe ? h($classe['nom']) : '' ?></span>
    <span>Document confidentiel · EduSchedule · <?= date('d/m/Y') ?></span>
  </div>

</div>
<?php endif; ?>

<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body></html>
