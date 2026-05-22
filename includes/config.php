<?php
// ============================================================
// includes/config.php — Configuration centrale
// Charge depuis les variables d'environnement ou les valeurs
// par défaut pour le développement local.
// ============================================================

// ── Base de données ────────────────────────────────────────
define('DB_HOST',    getenv('DB_HOST')    ?: 'localhost');
define('DB_NAME',    getenv('DB_NAME')    ?: 'emploi_du_temps');
define('DB_USER',    getenv('DB_USER')    ?: 'root');
define('DB_PASS',    getenv('DB_PASS')    ?: '');
define('DB_CHARSET', 'utf8mb4');

// ── Application ────────────────────────────────────────────
define('APP_NAME',    'EduSchedule');
define('APP_VERSION', '2.0.0');

// Auto-détection de l'URL de base (fonctionne en local ET en production)
function detectAppUrl(): string {
    if (getenv('APP_URL')) return rtrim(getenv('APP_URL'), '/');
    $proto  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    // Remonte jusqu'au dossier racine du projet
    $base = '';
    $parts = explode('/', trim($script, '/'));
    // Cherche le dossier "eduschedule" ou "emploi_du_temps" comme racine
    foreach ($parts as $i => $part) {
        if (in_array($part, ['eduschedule', 'emplois_du_temps', 'emploi_du_temps'])) {
            $base = '/' . implode('/', array_slice($parts, 0, $i + 1));
            break;
        }
    }
    // Sinon, si on est à la racine du serveur
    if ($base === '') {
        $depth = count(array_filter($parts, fn($p) => $p !== '' && !str_contains($p, '.')));
        $base  = $depth > 1 ? '/' . implode('/', array_slice(array_filter($parts), 0, -1)) : '';
    }
    return $proto . '://' . $host . $base;
}
if (!defined('APP_URL')) define('APP_URL', detectAppUrl());

// ── SMTP (pour l'envoi d'emails) ──────────────────────────
define('SMTP_HOST',     getenv('SMTP_HOST')     ?: 'smtp.gmail.com');
define('SMTP_PORT',     (int)(getenv('SMTP_PORT') ?: 587));
define('SMTP_USER',     getenv('SMTP_USER')     ?: '');
define('SMTP_PASS',     getenv('SMTP_PASS')     ?: '');
define('EMAIL_FROM',    getenv('EMAIL_FROM')    ?: 'noreply@eduschedule.sn');
define('EMAIL_FROM_NAME', getenv('EMAIL_FROM_NAME') ?: APP_NAME);

// ── Sécurité session ──────────────────────────────────────
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');

// ── Gestion des erreurs ────────────────────────────────────
// En production : mettre APP_ENV=production dans l'environnement
$env = getenv('APP_ENV') ?: 'development';
if ($env === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// ── Timezone ───────────────────────────────────────────────
date_default_timezone_set('Africa/Dakar');
