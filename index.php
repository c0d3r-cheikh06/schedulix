<?php
// index.php — Page de connexion
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (isLoggedIn()) {
    $user = getCurrentUser();
    $dest = match($user['role']) {
        'admin'      => APP_URL . '/admin/dashboard.php',
        'professeur' => APP_URL . '/professeur/dashboard.php',
        default      => APP_URL . '/eleve/dashboard.php',
    };
    redirect($dest);
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass  = $_POST['mot_de_passe'] ?? '';
    if (empty($email) || empty($pass)) {
        $error = getBusinessError('champs_vides');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = getBusinessError('format_email');
    } else {
        $stmt = getDB()->prepare("SELECT * FROM utilisateurs WHERE LOWER(email)=LOWER(?) AND statut='actif' LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($pass, $user['mot_de_passe'])) {
            login($user);
            $dest = match($user['role']) {
                'admin'      => APP_URL . '/admin/dashboard.php',
                'professeur' => APP_URL . '/professeur/dashboard.php',
                default      => APP_URL . '/eleve/dashboard.php',
            };
            redirect($dest);
        } else {
            $error = 'Identifiants incorrects ou compte désactivé. Vérifiez votre adresse e-mail et votre mot de passe.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion — <?= APP_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&family=DM+Serif+Display&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/main.css">
</head>
<body>
<div id="toast-container"></div>

<div class="login-page">
  <!-- Left panel -->
  <div class="login-left">
    <div class="login-left-content">
      <div class="login-hero-icon">
        <span class="material-icons-round">school</span>
      </div>
      <h1>Gérez vos emplois du temps en toute sérénité.</h1>
      <p>Une plateforme académique moderne, intuitive et fiable pour l'administration, les professeurs et les élèves.</p>
    </div>
  </div>

  <!-- Right panel -->
  <div class="login-right">
    <div class="login-form-container">
      <div class="login-form-header">
        <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:1.5rem">
          <div style="width:36px;height:36px;background:linear-gradient(135deg,var(--primary) 0%,var(--accent) 100%);border-radius:9px;display:flex;align-items:center;justify-content:center">
            <span class="material-icons-round" style="font-size:18px;color:#fff">school</span>
          </div>
          <span style="font-family:'DM Serif Display',serif;font-size:1.15rem;color:var(--primary)"><?= APP_NAME ?></span>
        </div>
        <h2>Bienvenue</h2>
        <p>Connectez-vous à votre espace académique</p>
      </div>

      <?php if ($error): ?>
      <div class="alert alert-danger" data-auto-dismiss="7000">
        <span class="material-icons-round">error_outline</span>
        <div class="alert-content"><?= h($error) ?></div>
      </div>
      <?php endif; ?>

      <?php if (!empty($_GET['registered'])): ?>
      <div class="alert alert-success" data-auto-dismiss="6000">
        <span class="material-icons-round">check_circle</span>
        <div class="alert-content"><strong>Inscription réussie !</strong> Vous pouvez maintenant vous connecter.</div>
      </div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="form-group">
          <label class="form-label" for="email">Adresse e-mail</label>
          <input type="email" id="email" name="email" class="form-control<?= $error ? ' is-invalid' : '' ?>"
                 placeholder="votre@email.sn" required autocomplete="email"
                 value="<?= h($_POST['email'] ?? '') ?>">
        </div>
        <div class="form-group" style="margin-bottom:1.5rem">
          <label class="form-label" for="mot_de_passe">Mot de passe</label>
          <div style="position:relative">
            <input type="password" id="mot_de_passe" name="mot_de_passe"
                   class="form-control<?= $error ? ' is-invalid' : '' ?>"
                   placeholder="••••••••" required autocomplete="current-password"
                   style="padding-right:2.8rem">
            <button type="button" onclick="togglePwd()"
                    style="position:absolute;right:.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);display:flex;align-items:center">
              <span class="material-icons-round" id="eyeIcon" style="font-size:20px">visibility</span>
            </button>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center">
          <span class="material-icons-round">login</span>
          Se connecter
        </button>
      </form>

      <div class="login-footer">
        Pas encore de compte ?
        <a href="<?= APP_URL ?>/register.php">Inscription élève</a>
      </div>

    </div>
  </div>
</div>

<script src="<?= APP_URL ?>/assets/js/main.js"></script>
<script>
document.addEventListener('DOMContentLoaded',()=>{
  // Auto-dismiss alerts
  document.querySelectorAll('.alert[data-auto-dismiss]').forEach(a=>{
    setTimeout(()=>{a.style.opacity='0';a.style.transform='translateY(-6px)';a.style.transition='all .4s';setTimeout(()=>a.remove(),400);},parseInt(a.dataset.autoDismiss)||5000);
  });
});
</script>
</body>
</html>
