// ============================================================
// EduSchedule — main.js (v2.1)
// Navigation instantanée — aucune animation de page.
// Seule la page de chargement initiale est animée (CSS).
// ============================================================

// ── Page Loader (premier chargement uniquement) ───────────
(function() {
  const loader = document.getElementById('pageLoader');
  if (!loader) return;
  // Marquer la session : après le 1er affichage, on masque immédiatement
  if (sessionStorage.getItem('eduldr')) {
    loader.style.display = 'none';
    return;
  }
  // Premier chargement : laisser l'animation CSS jouer, puis masquer
  setTimeout(function() {
    loader.classList.add('hidden');
    // Après que l'opacité passe à 0, on retire du flux
    setTimeout(function() { loader.style.display = 'none'; }, 450);
    sessionStorage.setItem('eduldr', '1');
  }, 1800);
})();

// ── DOM Ready ─────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {

  // Sidebar mobile (toggle instantané)
  var hamburger = document.getElementById('hamburgerBtn');
  var sidebar   = document.getElementById('sidebar');
  var overlay   = document.getElementById('sidebarOverlay');

  if (hamburger && sidebar) {
    hamburger.addEventListener('click', function() {
      sidebar.classList.toggle('open');
      if (overlay) overlay.classList.toggle('active');
    });
  }
  if (overlay) {
    overlay.addEventListener('click', function() {
      if (sidebar) sidebar.classList.remove('open');
      overlay.classList.remove('active');
    });
  }

  // Fermer sidebar au clic d'un lien (mobile)
  document.querySelectorAll('.sidebar-link').forEach(function(link) {
    link.addEventListener('click', function() {
      if (sidebar) sidebar.classList.remove('open');
      if (overlay) overlay.classList.remove('active');
    });
  });

  // Fermer modal avec Echap
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.active').forEach(function(m) {
        closeModal(m.id);
      });
    }
  });

  // Fermer modal en cliquant sur l'overlay
  document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
      if (e.target === overlay) closeModal(overlay.id);
    });
  });

  // Auto-masquer les alertes
  document.querySelectorAll('.alert[data-auto-dismiss]').forEach(function(alert) {
    var delay = parseInt(alert.dataset.autoDismiss) || 5000;
    setTimeout(function() { dismissAlert(alert); }, delay);
  });

  // Spinner sur soumission de formulaire
  document.querySelectorAll('form[data-loading]').forEach(function(form) {
    form.addEventListener('submit', function() {
      showSpinner(form.dataset.loading);
    });
  });

});

// ── Modals (instantané) ───────────────────────────────────
function openModal(id) {
  var el = document.getElementById(id);
  if (!el) return;
  el.classList.add('active');
  document.body.style.overflow = 'hidden';
  var first = el.querySelector('input:not([type=hidden]),select,textarea');
  if (first) first.focus();
}
function closeModal(id) {
  var el = document.getElementById(id);
  if (!el) return;
  el.classList.remove('active');
  document.body.style.overflow = '';
}

// ── Toasts (instantané, disparition après délai) ──────────
function showToast(message, type, title, duration) {
  type     = type     || 'info';
  duration = duration || 4000;
  var container = document.getElementById('toast-container');
  if (!container) return;

  var icons = { success: 'check_circle', danger: 'error', warning: 'warning', info: 'info' };
  var toast = document.createElement('div');
  toast.className = 'toast ' + type;
  toast.innerHTML =
    '<span class="material-icons-round">' + (icons[type] || 'info') + '</span>' +
    '<div class="toast-body">' +
      (title ? '<div class="toast-title">' + title + '</div>' : '') +
      '<div class="toast-msg">' + message + '</div>' +
    '</div>' +
    '<button onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;color:var(--text-muted);padding:0;margin-left:.5rem">' +
      '<span class="material-icons-round" style="font-size:16px">close</span>' +
    '</button>';

  container.appendChild(toast);
  setTimeout(function() { toast.remove(); }, duration);
}

// ── Spinner (pour les traitements serveur) ────────────────
function showSpinner(text) {
  var el = document.getElementById('spinnerOverlay');
  if (!el) return;
  var t = document.getElementById('spinnerText');
  if (t) t.textContent = text || 'Traitement en cours...';
  el.classList.add('active');
}
function hideSpinner() {
  var el = document.getElementById('spinnerOverlay');
  if (el) el.classList.remove('active');
}

// ── Masquer alerte sans animation ────────────────────────
function dismissAlert(el) {
  if (el && el.parentNode) el.parentNode.removeChild(el);
}

// ── Toggle mot de passe ───────────────────────────────────
function togglePwd(inputId, iconId) {
  inputId = inputId || 'mot_de_passe';
  iconId  = iconId  || 'eyeIcon';
  var input = document.getElementById(inputId);
  var icon  = document.getElementById(iconId);
  if (!input) return;
  if (input.type === 'password') {
    input.type = 'text';
    if (icon) icon.textContent = 'visibility_off';
  } else {
    input.type = 'password';
    if (icon) icon.textContent = 'visibility';
  }
}
