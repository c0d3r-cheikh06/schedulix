<?php
// ============================================================
// includes/auth.php — Gestion de l'authentification
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}
function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    static $user = null;
    if ($user === null) {
        $stmt = getDB()->prepare('SELECT * FROM utilisateurs WHERE id=? AND statut="actif" LIMIT 1');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}
function login(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_role'] = $user['role'];
}
function logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/index.php');
        exit;
    }
}
function requireAdmin(): void {
    requireLogin();
    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        header('Location: ' . APP_URL . '/index.php');
        exit;
    }
}
function requireProfesseur(): void {
    requireLogin();
    if (($_SESSION['user_role'] ?? '') !== 'professeur') {
        header('Location: ' . APP_URL . '/index.php');
        exit;
    }
}
function requireEleve(): void {
    requireLogin();
    if (($_SESSION['user_role'] ?? '') !== 'eleve') {
        header('Location: ' . APP_URL . '/index.php');
        exit;
    }
}
function getUserInitials(array $user): string {
    return strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1));
}
