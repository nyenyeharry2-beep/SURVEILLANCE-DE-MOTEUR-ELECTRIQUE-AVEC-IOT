<?php
declare(strict_types=1);

if (!defined('SUPERGENIES_NO_HEADERS')) {
    header('Content-Type: application/json; charset=utf-8');

    $dbConfig = require __DIR__ . '/database.php';
    $appConfig = require __DIR__ . '/app.php';

    foreach ($appConfig['cors_origins'] as $origin) {
        header("Access-Control-Allow-Origin: $origin");
    }
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-Token');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

function getPdo(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $cfg = require __DIR__ . '/database.php';
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $cfg['host'],
            $cfg['port'],
            $cfg['dbname'],
            $cfg['charset']
        );
        $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

function jsonResponse(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function jsonError(string $message, int $code = 400): void
{
    jsonResponse(['success' => false, 'error' => $message], $code);
}

function getAppConfig(): array
{
    return require __DIR__ . '/app.php';
}

function ensureAdminTables(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS admin_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        token VARCHAR(64) NOT NULL UNIQUE,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
}

function ensureParentMessagesTable(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS parent_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom_parent VARCHAR(120) NOT NULL,
        telephone_parent VARCHAR(30) NOT NULL,
        matricule VARCHAR(50) NOT NULL,
        nom_eleve VARCHAR(100) NULL,
        prenom_eleve VARCHAR(100) NULL,
        classe_eleve VARCHAR(120) NULL,
        section_eleve VARCHAR(80) NULL,
        motif ENUM(
            "paiement_non_enregistre",
            "montant_incorrect",
            "double_paiement",
            "probleme_inscription",
            "autre"
        ) NOT NULL DEFAULT "autre",
        message TEXT NOT NULL,
        statut ENUM("nouveau", "en_cours", "traite") NOT NULL DEFAULT "nouveau",
        note_admin TEXT NULL,
        reponse_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_statut (statut),
        INDEX idx_matricule (matricule),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    try {
        $pdo->exec('ALTER TABLE parent_messages ADD COLUMN reponse_at DATETIME NULL AFTER note_admin');
    } catch (Throwable $e) {
        // colonne déjà présente
    }
}

function ensureImportLogsTable(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS import_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type_import ENUM("inscriptions", "paiements") NOT NULL,
        fichier VARCHAR(255) NOT NULL,
        classe_detectee VARCHAR(120) NULL,
        section_detectee VARCHAR(80) NULL,
        lignes_traitees INT DEFAULT 0,
        lignes_erreur INT DEFAULT 0,
        details JSON NULL,
        imported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
}

/** @return list<array<string, mixed>> */
function fetchParentMessagesByMatricule(PDO $pdo, string $matricule, int $limit = 20): array
{
    ensureParentMessagesTable($pdo);
    $limit = max(1, min(50, $limit));
    $stmt = $pdo->prepare('
        SELECT id, motif, message, statut, note_admin, reponse_at, created_at, updated_at
        FROM parent_messages
        WHERE matricule = ?
        ORDER BY created_at DESC
        LIMIT ' . $limit
    );
    $stmt->execute([strtoupper(trim($matricule))]);
    return $stmt->fetchAll();
}

function ensureCommuniquesTable(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS communiques (
        id INT AUTO_INCREMENT PRIMARY KEY,
        titre VARCHAR(200) NOT NULL,
        contenu TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
}

/** @return array{students:int,fees:int,impayes:int} */
function getImportStats(PDO $pdo): array
{
    return [
        'students' => (int) $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn(),
        'fees' => (int) $pdo->query('SELECT COUNT(*) FROM student_fees')->fetchColumn(),
        'impayes' => (int) $pdo->query('SELECT COUNT(*) FROM student_fees WHERE statut IN ("impaye", "partiel")')->fetchColumn(),
    ];
}

/** @return list<array<string, mixed>> */
function fetchCommuniques(PDO $pdo, int $limit = 20): array
{
    ensureCommuniquesTable($pdo);
    $limit = max(1, min(50, $limit));
    $stmt = $pdo->query('SELECT id, titre, contenu, created_at FROM communiques ORDER BY created_at DESC LIMIT ' . $limit);
    return $stmt->fetchAll();
}

function getAdminTokenFromRequest(): string
{
    return trim(
        $_SERVER['HTTP_X_ADMIN_TOKEN']
        ?? $_GET['token']
        ?? $_POST['token']
        ?? ''
    );
}

function requireAdminAuth(): void
{
    $token = getAdminTokenFromRequest();
    if ($token === '') {
        jsonError('Authentification requise', 401);
    }
    try {
        $pdo = getPdo();
        ensureAdminTables($pdo);
        $stmt = $pdo->prepare('SELECT id FROM admin_sessions WHERE token = ? AND expires_at > NOW()');
        $stmt->execute([$token]);
        if (!$stmt->fetch()) {
            jsonError('Session expirée ou invalide', 401);
        }
    } catch (Throwable $e) {
        jsonError('Authentification indisponible', 500);
    }
}

function detectSection(string $classe): string
{
    $classeUpper = mb_strtoupper($classe);
    if (str_contains($classeUpper, 'MATERNELLE') || str_contains($classeUpper, 'PRIMAIRE') || preg_match('/\b[1-6]E?\s*(ANNEE|ANNÉE)?\s*PRIMAIRE/i', $classeUpper)) {
        return 'Primaire';
    }
    if (str_contains($classeUpper, 'PÉTROCHIMIE') || str_contains($classeUpper, 'PETROCHIMIE')) {
        return 'Pétrochimie';
    }
    if (preg_match('/\b(7|8)(E|ÈME|EME)\b/i', $classeUpper)) {
        return 'EB';
    }
    if (preg_match('/\b(1|2|3|4)(E|ÈME|EME|RE|ÈRE|ERE)\b.*TECH/i', $classeUpper)) {
        return 'Secondaire Technique';
    }
    return 'Secondaire Général';
}

function parseFrenchDate(?string $dateStr): ?string
{
    if ($dateStr === null || trim($dateStr) === '' || $dateStr === '—') {
        return null;
    }
    $dateStr = trim($dateStr);
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $dateStr, $m)) {
        return sprintf('%s-%s-%s', $m[3], $m[2], $m[1]);
    }
    return null;
}
