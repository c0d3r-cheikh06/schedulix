<?php
// register.php — Inscription élève
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (isLoggedIn()) redirect(APP_URL . '/eleve/dashboard.php');

$error = ''; $success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom     = sanitize($_POST['nom']    ?? '');
    $prenom  = sanitize($_POST['prenom'] ?? '');
    $email   = strtolower(trim($_POST['email'] ?? ''));
    $pass    = $_POST['mot_de_passe'] ?? '';
    $confirm = $_POST['confirm_passe'] ?? '';
    $idCls   = (int)($_POST['id_classe'] ?? 0);

    if (!$nom || !$prenom || !$email || !$pass || !$idCls) {
        $error = getBusinessError('champs_vides');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = getBusinessError('format_email');
    } elseif (strlen($pass) < 6) {
        $error = 'Le mot de passe doit comporter au moins 6 caractères.';
    } elseif ($pass !== $confirm) {
        $error = 'Les mots de passe ne correspondent pas.';
    } else {
        $chk = getDB()->prepare('SELECT id FROM utilisateurs WHERE LOWER(email)=LOWER(?) LIMIT 1');
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $error = getBusinessError('email_used');
        } else {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            getDB()->prepare("INSERT INTO utilisateurs (nom,prenom,email,mot_de_passe,role,statut,id_classe) VALUES (?,?,?,?,'eleve','actif',?)")
                ->execute([$nom,$prenom,$email,$hash,$idCls]);
            $success = true;
        }
    }
}

$classes = getClasses();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Inscription — <?= APP_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=DM+Serif+Display&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/main.css">
</head>
<body style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--bg);padding:2rem 1rem">
<div style="width:100%;max-width:460px">
  <div style="text-align:center;margin-bottom:2rem">
    <div style="display:inline-flex;align-items:center;gap:.6rem;margin-bottom:.5rem">
      <div style="width:36px;height:36px;background:linear-gradient(135deg,var(--primary),var(--accent));border-radius:9px;display:flex;align-items:center;justify-content:center">
        <span class="material-icons-round" style="font-size:18px;color:#fff">school</span>
      </div>
      <span style="font-family:'DM Serif Display',serif;font-size:1.2rem;color:var(--primary)"><?= APP_NAME ?></span>
    </div>
    <h2 style="font-size:1.4rem;font-weight:700;color:var(--text)">Créer un compte élève</h2>
    <p style="color:var(--text-muted);font-size:.875rem">Accédez à votre emploi du temps</p>
  </div>

  <div class="card">
    <div class="card-body">
      <?php if ($success): ?>
      <div class="alert alert-success">
        <span class="material-icons-round">check_circle</span>
        <div class="alert-content"><strong>Compte créé !</strong> <a href="<?= APP_URL ?>">Se connecter →</a></div>
      </div>
      <?php else: ?>

      <?php if ($error): ?>
      <div class="alert alert-danger"><span class="material-icons-round">error_outline</span>
        <div class="alert-content"><?= h($error) ?></div></div>
      <?php endif; ?>

      <form method="POST">
        <div class="form-grid">
          <div class="form-group"><label class="form-label">Prénom *</label>
            <input type="text" name="prenom" class="form-control" required></div>
          <div class="form-group"><label class="form-label">Nom *</label>
            <input type="text" name="nom" class="form-control" required></div>
        </div>
        <div class="form-group"><label class="form-label">Adresse e-mail *</label>
          <input type="email" name="email" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Classe *</label>
          <select name="id_classe" class="form-control" required>
            <option value="">Sélectionner votre classe</option>
            <?php foreach ($classes as $c): ?>
            <option value="<?= $c['id'] ?>"><?= h($c['nom']) ?> — <?= h($c['niveau']) ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="form-grid">
          <div class="form-group"><label class="form-label">Mot de passe *</label>
            <input type="password" name="mot_de_passe" class="form-control" required minlength="6"></div>
          <div class="form-group"><label class="form-label">Confirmer *</label>
            <input type="password" name="confirm_passe" class="form-control" required></div>
        </div>
        <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;margin-top:.5rem">
          <span class="material-icons-round">person_add</span> Créer mon compte
        </button>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <p style="text-align:center;margin-top:1rem;font-size:.875rem;color:var(--text-muted)">
    Déjà un compte ? <a href="<?= APP_URL ?>">Se connecter</a>
  </p>
</div>
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body></html>
