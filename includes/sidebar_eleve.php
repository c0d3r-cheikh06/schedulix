<?php
$nbMsgNonLus = countMessagesNonLus($currentUser['id']);
$menu = [
    ['dashboard',       'Tableau de bord',    'dashboard',     '/eleve/dashboard.php'],
    ['emploi_du_temps', 'Mon emploi du temps','calendar_month','/eleve/emploi_du_temps.php'],
    ['messages',        'Messages',           'inbox',         '/eleve/messages.php'],
];
?>
<nav class="sidebar" id="sidebar" role="navigation" aria-label="Navigation élève">
  <div class="sidebar-section">
    <div class="sidebar-section-label">Espace Élève</div>
    <ul class="sidebar-nav">
      <?php foreach ($menu as [$key, $label, $icon, $path]): ?>
      <li>
        <a class="sidebar-link <?= $activeMenu === $key ? 'active' : '' ?>"
           href="<?= APP_URL . $path ?>">
          <span class="material-icons-round"><?= $icon ?></span>
          <?= $label ?>
          <?php if ($key === 'messages' && $nbMsgNonLus > 0): ?>
            <span class="sidebar-badge"><?= $nbMsgNonLus ?></span>
          <?php endif; ?>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</nav>
