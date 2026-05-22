<?php
// ============================================================
// includes/functions.php — Fonctions utilitaires globales
// ============================================================
require_once __DIR__ . '/db.php';

// ── Sécurité / sanitisation ───────────────────────────────
function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
function sanitize(string $val): string {
    return trim(strip_tags($val));
}
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

// ── CSRF ──────────────────────────────────────────────────
function getCsrfToken(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function verifyCsrf(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die(json_encode(['error' => 'Token CSRF invalide.']));
    }
}

// ── Notifications internes ────────────────────────────────
function countNotificationsNonLues(int $userId): int {
    $stmt = getDB()->prepare('SELECT COUNT(*) FROM notifications WHERE id_utilisateur=? AND est_lu=0');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}
function sendNotification(int $userId, string $titre, string $message, string $type = 'info'): void {
    $stmt = getDB()->prepare('INSERT INTO notifications (id_utilisateur,titre,message,type,est_lu,date_envoi) VALUES (?,?,?,?,0,NOW())');
    $stmt->execute([$userId, $titre, $message, $type]);
}

// ── Données ───────────────────────────────────────────────
function getClasses(): array {
    return getDB()->query('SELECT * FROM classes ORDER BY niveau, nom')->fetchAll();
}
function getMatieres(): array {
    return getDB()->query('SELECT * FROM matieres ORDER BY nom')->fetchAll();
}
function getSalles(): array {
    return getDB()->query('SELECT * FROM salles ORDER BY nom')->fetchAll();
}
function getProfesseurs(): array {
    return getDB()->query("SELECT * FROM utilisateurs WHERE role='professeur' AND statut='actif' ORDER BY nom,prenom")->fetchAll();
}
function getCreneaux(): array {
    return getDB()->query("SELECT * FROM creneaux ORDER BY FIELD(jour,'Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'),heure_debut")->fetchAll();
}
function getJoursSemaine(): array {
    return ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];
}
function getCurrentVersion(): int {
    $row = getDB()->query('SELECT MAX(version) AS v FROM emplois_du_temps')->fetch();
    return $row ? (int)($row['v'] ?? 0) : 0;
}
function countPendingValidations(): int {
    $v = getCurrentVersion();
    if ($v === 0) return 0;
    $stmt = getDB()->prepare("SELECT COUNT(*) FROM emplois_du_temps WHERE version=? AND statut='provisoire'");
    $stmt->execute([$v]);
    return (int)$stmt->fetchColumn();
}
function getStatusBadge(string $statut): string {
    $map = [
        'provisoire' => ['warning', 'schedule',     'En attente'],
        'valide'     => ['success', 'check_circle',  'Validé'],
        'rejete'     => ['danger',  'cancel',         'Rejeté'],
        'confirme'   => ['primary', 'verified',       'Confirmé'],
    ];
    [$cls, $icon, $label] = $map[$statut] ?? ['secondary', 'help', ucfirst($statut)];
    return "<span class=\"badge badge-{$cls}\"><span class=\"material-icons-round\" style=\"font-size:11px\">{$icon}</span> {$label}</span>";
}
function formatHeure(string $time): string {
    return substr($time, 0, 5);
}
function generateTempPassword(int $length = 10): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%';
    $pass  = '';
    for ($i = 0; $i < $length; $i++) {
        $pass .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $pass;
}

// ── Messages d'erreur professionnels ─────────────────────
function getBusinessError(string $code): string {
    $errors = [
        'salle_exists'      => 'Cette salle existe déjà dans le système. Veuillez choisir un nom différent ou modifier la salle existante.',
        'prof_exists'       => 'Ce professeur est déjà enregistré dans le système. Vérifiez l\'adresse e-mail.',
        'matiere_exists'    => 'Cette matière a déjà été enregistrée dans le système.',
        'classe_exists'     => 'Cette classe existe déjà dans le système.',
        'email_used'        => 'Cette adresse e-mail est déjà associée à un compte existant.',
        'salle_occupied'    => 'La salle sélectionnée est déjà occupée sur ce créneau horaire. Veuillez choisir un autre créneau ou une autre salle.',
        'prof_occupied'     => 'Le professeur est déjà affecté à un autre cours sur ce créneau. Vérifiez ses disponibilités.',
        'classe_occupied'   => 'Cette classe a déjà un cours planifié sur ce créneau horaire.',
        'horaire_invalide'  => 'Le créneau horaire sélectionné est invalide. L\'heure de fin doit être supérieure à l\'heure de début.',
        'creneau_pause'     => 'Impossible de placer un cours sur un créneau de pause.',
        'chevauchement'     => 'Il y a un chevauchement horaire avec un cours déjà planifié.',
        'champs_vides'      => 'Veuillez remplir tous les champs obligatoires.',
        'format_email'      => 'Le format de l\'adresse e-mail est invalide.',
        'longueur_nom'      => 'Le nom doit comporter entre 2 et 100 caractères.',
        'capacite_invalide' => 'La capacité doit être un nombre entier entre 1 et 500.',
        'not_found'         => 'L\'élément demandé est introuvable dans le système.',
        'delete_protected'  => 'Cet élément ne peut pas être supprimé car il est utilisé dans des emplois du temps actifs.',
    ];
    return $errors[$code] ?? 'Une erreur inattendue s\'est produite. Veuillez réessayer.';
}

// ── Email (PHPMailer-compatible, ou fallback mail()) ──────
function sendEmailNotification(string $to, string $toName, string $subject, string $htmlBody): bool {
    // Si SMTP non configuré, on log et on renvoie true silencieusement
    if (!SMTP_USER || !SMTP_PASS) {
        error_log("[EduSchedule] Email non envoyé (SMTP non configuré) → {$to} : {$subject}");
        return true; // Ne pas bloquer l'UX
    }

    // Utilise PHPMailer si disponible (recommandé)
    $mailerClass = __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
    if (file_exists($mailerClass)) {
        require_once $mailerClass;
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8';
            $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
            $mail->addAddress($to, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = strip_tags($htmlBody);
            $mail->send();
            return true;
        } catch (\Exception $e) {
            error_log("[EduSchedule] Erreur envoi email → {$to} : " . $e->getMessage());
            return false;
        }
    }

    // Fallback : mail() natif PHP
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM . ">\r\n";
    return mail($to, $subject, $htmlBody, $headers);
}

// ── Template HTML email professionnel ────────────────────
function buildEmailTemplate(array $data): string {
    $name       = h($data['name']       ?? 'Utilisateur');
    $title      = h($data['title']      ?? 'Notification EduSchedule');
    $body       = $data['body']         ?? '';
    $classe     = h($data['classe']     ?? '');
    $version    = h($data['version']    ?? '');
    $date       = h($data['date']       ?? date('d/m/Y à H:i'));
    $actionUrl  = $data['action_url']   ?? APP_URL;
    $actionText = h($data['action_text'] ?? 'Consulter l\'emploi du temps');
    $appUrl     = APP_URL;
    $year       = date('Y');

    $classeRow = $classe
    ? "<tr>
        <td style='padding:4px 0;font-size:13px;color:#6B7280;'>
            Classe concernée
        </td>
        <td style='padding:4px 0;font-size:13px;font-weight:600;color:#111827;text-align:right;'>
            {$classe}
        </td>
      </tr>"
    : '';

    $versionRow = $version
    ? "<tr>
        <td style='padding:4px 0;font-size:13px;color:#6B7280;'>
            Version
        </td>
        <td style='padding:4px 0;font-size:13px;font-weight:600;color:#111827;text-align:right;'>
            {$version}
        </td>
      </tr>"
    : '';
    return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>{$title}</title>
</head>
<body style="margin:0;padding:0;background:#F3F6FB;font-family:'Segoe UI',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#F3F6FB;padding:40px 20px;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

        <!-- Header -->
        <tr><td style="background:linear-gradient(135deg,#0F1C3F 0%,#1A56DB 100%);border-radius:14px 14px 0 0;padding:32px 40px;text-align:center;">
          <div style="display:inline-flex;align-items:center;gap:10px;">
            <div style="width:42px;height:42px;background:rgba(255,255,255,.15);border-radius:10px;display:inline-flex;align-items:center;justify-content:center;vertical-align:middle;">
              <span style="font-size:22px;color:#fff;">🎓</span>
            </div>
            <span style="font-size:22px;font-weight:700;color:#fff;vertical-align:middle;letter-spacing:-.3px;">EduSchedule</span>
          </div>
          <p style="color:rgba(255,255,255,.55);font-size:13px;margin:8px 0 0;">Système de gestion des emplois du temps</p>
        </td></tr>

        <!-- Body -->
        <tr><td style="background:#fff;padding:36px 40px;">
          <p style="font-size:15px;color:#374151;margin:0 0 8px;">Bonjour <strong style="color:#111827;">{$name}</strong>,</p>
          <div style="font-size:14px;color:#4B5563;line-height:1.7;margin:16px 0;">{$body}</div>

          <!-- Info card -->
          <table width="100%" cellpadding="0" cellspacing="0" style="background:#F3F6FB;border:1px solid #E5E9F2;border-radius:10px;margin:24px 0;">
            <tr><td style="padding:20px 24px;">
              <table width="100%" cellpadding="0" cellspacing="0">
               {$classeRow}
               {$versionRow}
                <tr><td style='padding:4px 0;font-size:13px;color:#6B7280;'>Date de mise à jour</td><td style='padding:4px 0;font-size:13px;font-weight:600;color:#111827;text-align:right;'>{$date}</td></tr>
              </table>
            </td></tr>
          </table>

          <!-- CTA Button -->
          <div style="text-align:center;margin:28px 0 8px;">
            <a href="{$actionUrl}" style="display:inline-block;background:#1A56DB;color:#fff;text-decoration:none;padding:13px 32px;border-radius:8px;font-size:14px;font-weight:600;letter-spacing:.2px;">
              {$actionText} →
            </a>
          </div>
        </td></tr>

        <!-- Footer -->
        <tr><td style="background:#F3F6FB;border-radius:0 0 14px 14px;padding:24px 40px;text-align:center;border-top:1px solid #E5E9F2;">
          <p style="font-size:12px;color:#9CA3AF;margin:0 0 4px;">Cet email a été envoyé automatiquement par EduSchedule.</p>
          <p style="font-size:12px;color:#9CA3AF;margin:0;">
            <a href="{$appUrl}" style="color:#1A56DB;text-decoration:none;">Accéder à la plateforme</a>
            &nbsp;·&nbsp; © {$year} EduSchedule · Tous droits réservés
          </p>
        </td></tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
}

// ── Envoi email EDT mis à jour (professeurs + élèves) ─────
function notifyEdtUpdate(int $version, array $classesAffectees = []): array {
    $pdo   = getDB();
    $date  = date('d/m/Y à H:i');
    $sent  = 0;
    $errors = 0;

    foreach ($classesAffectees as $classe) {
        $classeNom = $classe['nom'] ?? 'N/A';

        // Professeurs de la classe
        $stmtP = $pdo->prepare("
            SELECT DISTINCT u.id, u.nom, u.prenom, u.email
            FROM utilisateurs u
            JOIN emplois_du_temps e ON e.id_professeur = u.id
            WHERE e.version = ? AND e.id_classe = ? AND u.email != ''
        ");
        $stmtP->execute([$version, $classe['id']]);
        $profs = $stmtP->fetchAll();

        foreach ($profs as $prof) {
            if (!filter_var($prof['email'], FILTER_VALIDATE_EMAIL)) continue;
            $body = "L'emploi du temps de la classe <strong>{$classeNom}</strong> vient d'être mis à jour (version {$version}).<br>Veuillez vous connecter à la plateforme pour consulter vos nouveaux créneaux et procéder à leur validation.";
            $html = buildEmailTemplate([
                'name'        => $prof['prenom'] . ' ' . $prof['nom'],
                'title'       => "Mise à jour de l'emploi du temps — " . APP_NAME,
                'body'        => $body,
                'classe'      => $classeNom,
                'version'     => "Version {$version}",
                'date'        => $date,
                'action_url'  => APP_URL . '/professeur/valider.php',
                'action_text' => 'Valider mes créneaux',
            ]);
            $ok = sendEmailNotification($prof['email'], $prof['prenom'] . ' ' . $prof['nom'], "📅 Nouvel emploi du temps — {$classeNom}", $html);
            $ok ? $sent++ : $errors++;
        }

        // Élèves de la classe
        $stmtE = $pdo->prepare("SELECT id, nom, prenom, email FROM utilisateurs WHERE role='eleve' AND id_classe=? AND email != ''");
        $stmtE->execute([$classe['id']]);
        $eleves = $stmtE->fetchAll();

        foreach ($eleves as $eleve) {
            if (!filter_var($eleve['email'], FILTER_VALIDATE_EMAIL)) continue;
            $body = "L'emploi du temps de votre classe <strong>{$classeNom}</strong> a été mis à jour (version {$version}).<br>Connectez-vous pour consulter votre nouveau planning de la semaine.";
            $html = buildEmailTemplate([
                'name'        => $eleve['prenom'] . ' ' . $eleve['nom'],
                'title'       => "Nouvel emploi du temps disponible — " . APP_NAME,
                'body'        => $body,
                'classe'      => $classeNom,
                'version'     => "Version {$version}",
                'date'        => $date,
                'action_url'  => APP_URL . '/eleve/emploi_du_temps.php',
                'action_text' => 'Voir mon emploi du temps',
            ]);
            $ok = sendEmailNotification($eleve['email'], $eleve['prenom'] . ' ' . $eleve['nom'], "📅 Emploi du temps mis à jour — {$classeNom}", $html);
            $ok ? $sent++ : $errors++;
        }
    }
    return ['sent' => $sent, 'errors' => $errors];
}

// ============================================================
// FONCTIONS v3 — Niveaux, Messagerie, EDT amélioré
// ============================================================

// ── Niveaux ───────────────────────────────────────────────
function getNiveaux(): array {
    return getDB()->query('SELECT * FROM niveaux ORDER BY ordre, nom')->fetchAll();
}
function getClassesWithNiveau(): array {
    return getDB()->query("
        SELECT c.*, n.nom AS niveau_nom, n.id AS id_niveau_rel
        FROM classes c
        LEFT JOIN niveaux n ON n.id = c.id_niveau
        ORDER BY n.ordre, c.nom
    ")->fetchAll();
}

// ── Niveaux d'un professeur ───────────────────────────────
function getNiveauxProfesseur(int $idProf): array {
    $stmt = getDB()->prepare('SELECT id_niveau FROM professeur_niveau WHERE id_professeur=?');
    $stmt->execute([$idProf]);
    return array_column($stmt->fetchAll(), 'id_niveau');
}

// ── Disponibilités d'un professeur (indexées par jour+heure) ─
function getDisponibilitesProfesseur(int $idProf): array {
    $stmt = getDB()->prepare('SELECT * FROM disponibilites WHERE id_professeur=? AND disponible=1');
    $stmt->execute([$idProf]);
    $result = [];
    foreach ($stmt->fetchAll() as $d) {
        $result[$d['jour']][] = ['debut' => $d['heure_debut'], 'fin' => $d['heure_fin']];
    }
    return $result;
}

// Vérifie si un professeur est disponible pour un créneau donné
function profEstDisponible(int $idProf, string $jour, string $hDeb, string $hFin): bool {
    $dispos = getDisponibilitesProfesseur($idProf);
    // Si aucune dispo enregistrée → on considère disponible (pas de contrainte)
    if (empty($dispos)) return true;
    if (!isset($dispos[$jour])) return false;
    foreach ($dispos[$jour] as $d) {
        // Le créneau doit être entièrement dans une plage dispo
        if ($hDeb >= $d['debut'] && $hFin <= $d['fin']) return true;
    }
    return false;
}

// Vérifie si un prof est autorisé à enseigner dans un niveau
function profAutoriseNiveau(int $idProf, ?int $idNiveau): bool {
    if (!$idNiveau) return true; // Pas de niveau défini → pas de contrainte
    $niveaux = getNiveauxProfesseur($idProf);
    if (empty($niveaux)) return true; // Aucune restriction définie → autorisé partout
    return in_array($idNiveau, $niveaux);
}

// ── Messagerie ────────────────────────────────────────────
function countMessagesNonLus(int $idEleve): int {
    $stmt = getDB()->prepare("
        SELECT COUNT(*) FROM messages m
        WHERE m.id_classe = (SELECT id_classe FROM utilisateurs WHERE id=?)
          AND m.id NOT IN (SELECT id_message FROM message_lu WHERE id_eleve=?)
    ");
    $stmt->execute([$idEleve, $idEleve]);
    return (int)$stmt->fetchColumn();
}

function getMessagesClasse(int $idClasse, int $limit = 20): array {
    $stmt = getDB()->prepare("
        SELECT m.*, u.nom AS exp_nom, u.prenom AS exp_prenom
        FROM messages m
        JOIN utilisateurs u ON u.id = m.id_expediteur
        WHERE m.id_classe = ?
        ORDER BY m.date_envoi DESC
        LIMIT ?
    ");
    $stmt->execute([$idClasse, $limit]);
    return $stmt->fetchAll();
}

function getMessageTypeLabel(string $type): array {
    $map = [
        'info'    => ['primary', 'info',             'Information'],
        'absence' => ['danger',  'person_off',        'Absence'],
        'devoir'  => ['purple',  'assignment',        'Devoir'],
        'report'  => ['warning', 'update',            'Report de cours'],
        'autre'   => ['secondary','chat',             'Autre'],
    ];
    return $map[$type] ?? ['secondary', 'chat', ucfirst($type)];
}

// Envoyer emails aux élèves d'une classe lors d'un nouveau message
function notifyMessageEleves(array $message, string $classeNom): array {
    $pdo    = getDB();
    $sent   = 0; $errors = 0;
    $stmt   = $pdo->prepare("SELECT id, nom, prenom, email FROM utilisateurs WHERE role='eleve' AND id_classe=? AND email != '' AND statut='actif'");
    $stmt->execute([$message['id_classe']]);
    $eleves = $stmt->fetchAll();
    [,$typeIcon,$typeLabel] = getMessageTypeLabel($message['type']);
    foreach ($eleves as $e) {
        if (!filter_var($e['email'], FILTER_VALIDATE_EMAIL)) continue;
        $body = "Votre professeur <strong>{$message['exp_prenom']} {$message['exp_nom']}</strong> vous a envoyé un message :<br><br>"
              . "<div style='background:#F3F6FB;border-left:4px solid #1A56DB;padding:16px 20px;border-radius:6px;margin:12px 0;font-size:14px;color:#374151;'>"
              . nl2br(h($message['contenu']))
              . "</div>";
        $html = buildEmailTemplate([
            'name'        => $e['prenom'] . ' ' . $e['nom'],
            'title'       => "[{$typeLabel}] " . $message['sujet'] . " — " . APP_NAME,
            'body'        => $body,
            'classe'      => $classeNom,
            'date'        => date('d/m/Y à H:i'),
            'action_url'  => APP_URL . '/eleve/messages.php',
            'action_text' => 'Lire le message',
        ]);
        $ok = sendEmailNotification($e['email'], $e['prenom'].' '.$e['nom'], "[{$typeLabel}] ".h($message['sujet']), $html);
        $ok ? $sent++ : $errors++;
    }
    return ['sent' => $sent, 'errors' => $errors];
}
