<?php
// professeur/emplois_du_temps.php — v3 impression portrait améliorée
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireProfesseur();
$currentUser = getCurrentUser();
$pdo = getDB();

$version = getCurrentVersion();
$jours   = getJoursSemaine();

$stmt = $pdo->prepare("
    SELECT e.*, m.nom AS mat_nom, m.couleur_hex,
           cl.nom AS classe_nom, s.nom AS salle_nom,
           c.jour, c.heure_debut, c.heure_fin, e.statut
    FROM emplois_du_temps e
    JOIN matieres m  ON m.id=e.id_matiere
    JOIN classes cl  ON cl.id=e.id_classe
    JOIN salles s    ON s.id=e.id_salle
    JOIN creneaux c  ON c.id=e.id_creneau
    WHERE e.version=? AND e.id_professeur=?
    ORDER BY c.heure_debut
");
$stmt->execute([$version, $currentUser['id']]);
$rows = $stmt->fetchAll();

// Grille horaire
$creneaux = [];
foreach ($rows as $r) {
    $key = $r['heure_debut'].'-'.$r['heure_fin'];
    if (!isset($creneaux[$key])) { foreach ($jours as $j) $creneaux[$key][$j] = null; }
    $creneaux[$key][$r['jour']] = $r;
}
ksort($creneaux);

// Légende matières
$matieresSeen = []; $legendeItems = [];
foreach ($rows as $r) {
    if (!in_array($r['id_matiere'],$matieresSeen)) {
        $matieresSeen[] = $r['id_matiere'];
        $legendeItems[] = $r;
    }
}

$nbCours    = count($rows);
$nbClasses  = count(array_unique(array_column($rows,'id_classe')));
$nbMatieres = count($legendeItems);

// Niveaux autorisés du prof (pour info)
$niveauxProf = getNiveauxProfesseur($currentUser['id']);

$pageTitle = 'Mon emploi du temps'; $activeMenu = 'emplois_du_temps';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_prof.php';
?>
<style>
@media print {
  #pageLoader,.topbar,.sidebar,.sidebar-overlay,
  #toast-container,.spinner-overlay,.no-print { display:none!important; }

  * { -webkit-print-color-adjust:exact!important; print-color-adjust:exact!important; }
  body { background:#fff!important; font-family:Arial,Helvetica,sans-serif!important; font-size:9pt; }
  .main-content { margin:0!important; padding:0!important; }
  #print-zone { display:block!important; width:100%; }

  .ph-wrap {
    display:flex!important; align-items:center;
    justify-content:space-between;
    padding-bottom:10pt; margin-bottom:12pt;
    border-bottom:2.5pt solid #1A56DB;
  }
  .ph-logo { width:44pt;height:44pt;background:#1A56DB!important;border-radius:10pt;display:flex!important;align-items:center;justify-content:center;flex-shrink:0; }
  .ph-logo span { font-size:24pt;color:#fff; }
  .ph-info { flex:1;padding-left:10pt; }
  .ph-info h1 { font-size:13pt;font-weight:700;color:#111827;margin:0 0 2pt; }
  .ph-info p  { font-size:8pt;color:#6B7280;margin:0; }
  .ph-meta { text-align:right; }
  .ph-meta .app-name { font-size:12pt;font-weight:700;color:#1A56DB; }
  .ph-meta p { font-size:7.5pt;color:#9CA3AF;margin:2pt 0 0; }

  .pstat-row { display:flex!important;gap:7pt;margin-bottom:10pt; }
  .pstat { flex:1;border:1pt solid #E5E9F2;border-radius:5pt;padding:6pt 8pt;text-align:center; }
  .pstat-val { font-size:13pt;font-weight:700;color:#1A56DB; }
  .pstat-lbl { font-size:6.5pt;color:#6B7280; }

  .pt-table { width:100%;border-collapse:collapse;margin-bottom:10pt; }
  .pt-table th {
    background:#1A56DB!important; color:#fff!important;
    font-weight:700; font-size:7.5pt; padding:5pt 4pt;
    text-align:center; border:1pt solid #1346C0;
    text-transform:uppercase; letter-spacing:.3pt;
  }
  .pt-table th.th-time { text-align:left;width:52pt;background:#0F1C3F!important; }
  .pt-table td { border:1pt solid #E5E9F2;padding:0;vertical-align:top;height:38pt; }
  .pt-table td.td-time {
    font-size:7.5pt;font-weight:700;color:#374151;
    background:#F8F9FC!important;text-align:center;
    padding:4pt 3pt;border-right:2pt solid #C8D0E0;white-space:nowrap;
  }
  .pt-table tr:nth-child(even) td.td-time { background:#F0F4FB!important; }

  .pc { height:100%;padding:3pt 4pt;border-left:3pt solid transparent; }
  .pc-mat  { font-weight:700;font-size:7.5pt;color:#111827;margin-bottom:1.5pt; }
  .pc-sub  { font-size:6.5pt;color:#374151;font-style:italic;margin-bottom:1pt; }
  .pc-room { font-size:6.5pt;color:#6B7280; }
  .pc-badge {
    display:inline-block;font-size:5.5pt;padding:1pt 3.5pt;
    border-radius:99pt;font-weight:700;margin-top:1.5pt;
  }
  .pc-badge.valide    { background:#ECFDF5;color:#059669; }
  .pc-badge.provisoire{ background:#FFFBEB;color:#D97706; }
  .pc-badge.rejete    { background:#FEF2F2;color:#DC2626; }
  .td-free { background:#FAFAFA!important; }

  .pl-wrap { display:flex!important;flex-wrap:wrap;gap:5pt 10pt;margin-top:8pt;padding-top:6pt;border-top:1pt solid #E5E9F2; }
  .pl-item { display:flex!important;align-items:center;gap:3.5pt;font-size:7pt;color:#374151; }
  .pl-dot  { width:7pt;height:7pt;border-radius:50%;flex-shrink:0; }

  .pf-wrap { display:flex!important;justify-content:space-between;margin-top:8pt;padding-top:6pt;border-top:1pt solid #E5E9F2;font-size:7pt;color:#9CA3AF; }

  @page { size:A4 portrait; margin:13mm 11mm; }
}
@media screen { #print-zone { display:none; } }
</style>

<div class="main-content">
  <div class="page-header no-print">
    <div class="page-header-left">
      <h1 class="page-title"><span class="material-icons-round">calendar_month</span> Mon emploi du temps</h1>
      <p class="page-subtitle">Version <?= $version ?> — <?= $nbCours ?> cours planifiés</p>
    </div>
    <?php if (!empty($rows)): ?>
    <button class="btn btn-outline" onclick="window.print()">
      <span class="material-icons-round">print</span> Imprimer
    </button>
    <?php endif; ?>
  </div>

  <?php if (empty($rows)): ?>
  <div class="card no-print"><div class="card-body">
    <div class="empty-state">
      <div class="empty-state-icon"><span class="material-icons-round">calendar_today</span></div>
      <h3>Aucun cours planifié</h3>
      <p>Vous n'avez aucun créneau dans l'emploi du temps actuel.</p>
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
      <div class="stat-icon purple"><span class="material-icons-round">class</span></div>
      <div><div class="stat-value"><?= $nbClasses ?></div><div class="stat-label">Classes</div></div>
    </div>
  </div>

  <!-- Grille écran -->
  <div class="card no-print">
    <div class="card-header">
      <div class="card-header-title"><span class="material-icons-round">view_week</span> Planning de la semaine</div>
    </div>
    <div style="overflow-x:auto">
      <table style="width:100%;border-collapse:collapse;min-width:700px">
        <thead>
          <tr>
            <th style="padding:.7rem 1rem;background:var(--bg);border-bottom:1px solid var(--border);font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);width:110px">Horaire</th>
            <?php foreach ($jours as $j): ?>
            <th style="padding:.7rem .85rem;background:var(--bg);border-bottom:1px solid var(--border);font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);text-align:center"><?= $j ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($creneaux as $crKey=>$joursData):
            [$hD,$hF]=explode('-',$crKey,2); ?>
          <tr>
            <td style="padding:.6rem 1rem;border-bottom:1px solid var(--border);border-right:1px solid var(--border);font-size:.8rem;color:var(--text-muted);white-space:nowrap;font-weight:500;background:var(--bg)">
              <?= formatHeure($hD) ?><br><span style="color:var(--text-light)"><?= formatHeure($hF) ?></span>
            </td>
            <?php foreach ($jours as $j): $cell=$joursData[$j]??null; ?>
            <td style="padding:.4rem .5rem;border-bottom:1px solid var(--border);border-right:1px solid var(--border);vertical-align:top;min-width:130px">
              <?php if ($cell): ?>
              <div style="background:<?= h($cell['couleur_hex']) ?>1A;border-left:3px solid <?= h($cell['couleur_hex']) ?>;border-radius:0 var(--radius-sm) var(--radius-sm) 0;padding:.5rem .6rem">
                <div style="font-weight:700;font-size:.8rem;color:var(--text)"><?= h($cell['mat_nom']) ?></div>
                <div style="font-size:.72rem;color:var(--text-muted);font-style:italic"><?= h($cell['classe_nom']) ?></div>
                <div style="font-size:.72rem;color:var(--text-muted);margin-top:.1rem"><span class="material-icons-round" style="font-size:11px">meeting_room</span> <?= h($cell['salle_nom']) ?></div>
                <div style="margin-top:.25rem"><?= getStatusBadge($cell['statut']) ?></div>
              </div>
              <?php else: ?><div style="min-height:55px"></div><?php endif; ?>
            </td>
            <?php endforeach; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Légende écran -->
  <div class="card no-print" style="margin-top:1.25rem">
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

  <div class="ph-wrap">
    <div class="ph-logo"><span>🎓</span></div>
    <div class="ph-info">
      <h1>Planning — <?= h($currentUser['prenom'].' '.$currentUser['nom']) ?></h1>
      <p>Professeur · <?= $nbCours ?> cours · <?= $nbClasses ?> classe<?= $nbClasses>1?'s':'' ?> · Version <?= $version ?> · Année <?= date('Y').'/'.((int)date('Y')+1) ?></p>
    </div>
    <div class="ph-meta">
      <div class="app-name">EduSchedule</div>
      <p>Imprimé le <?= date('d/m/Y à H:i') ?></p>
    </div>
  </div>

  <div class="pstat-row">
    <div class="pstat"><div class="pstat-val"><?= $nbCours ?></div><div class="pstat-lbl">Cours / semaine</div></div>
    <div class="pstat"><div class="pstat-val"><?= $nbMatieres ?></div><div class="pstat-lbl">Matières</div></div>
    <div class="pstat"><div class="pstat-val"><?= $nbClasses ?></div><div class="pstat-lbl">Classes</div></div>
    <div class="pstat"><div class="pstat-val"><?= count(array_filter($rows,fn($r)=>$r['statut']==='valide')) ?></div><div class="pstat-lbl">Créneaux validés</div></div>
  </div>

  <table class="pt-table">
    <thead>
      <tr>
        <th class="th-time">Horaire</th>
        <?php foreach ($jours as $j): ?><th><?= $j ?></th><?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($creneaux as $crKey=>$joursData):
        [$hD,$hF]=explode('-',$crKey,2); ?>
      <tr>
        <td class="td-time"><?= formatHeure($hD) ?><br><span style="font-weight:400;color:#9CA3AF"><?= formatHeure($hF) ?></span></td>
        <?php foreach ($jours as $j): $cell=$joursData[$j]??null; ?>
        <td class="<?= $cell?'':'td-free' ?>">
          <?php if ($cell): ?>
          <div class="pc" style="background:<?= h($cell['couleur_hex']) ?>15;border-left-color:<?= h($cell['couleur_hex']) ?>">
            <div class="pc-mat"><?= h($cell['mat_nom']) ?></div>
            <div class="pc-sub"><?= h($cell['classe_nom']) ?></div>
            <div class="pc-room">📍 <?= h($cell['salle_nom']) ?></div>
            <span class="pc-badge <?= h($cell['statut']) ?>">
              <?= $cell['statut']==='valide'?'✓ Validé':($cell['statut']==='rejete'?'✗ Rejeté':'⏳ Attente') ?>
            </span>
          </div>
          <?php endif; ?>
        </td>
        <?php endforeach; ?>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="pl-wrap">
    <span style="font-size:7pt;font-weight:700;color:#374151;margin-right:4pt">Légende matières :</span>
    <?php foreach ($legendeItems as $r): ?>
    <div class="pl-item"><div class="pl-dot" style="background:<?= h($r['couleur_hex']) ?>"></div><?= h($r['mat_nom']) ?></div>
    <?php endforeach; ?>
  </div>

  <div class="pf-wrap">
    <span>Professeur : <?= h($currentUser['prenom'].' '.$currentUser['nom']) ?></span>
    <span>Document confidentiel · EduSchedule · <?= date('d/m/Y') ?></span>
  </div>

</div>
<?php endif; ?>

<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body></html>
