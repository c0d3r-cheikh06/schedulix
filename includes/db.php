<?php
// ============================================================
// includes/db.php — Connexion PDO singleton
// ============================================================
require_once __DIR__ . '/config.php';

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            // Ne pas exposer les détails en production
            $msg = (getenv('APP_ENV') === 'production')
                ? 'Service temporairement indisponible.'
                : 'Connexion BDD impossible : ' . $e->getMessage();
            die(json_encode(['error' => $msg]));
        }
    }
    return $pdo;
}
