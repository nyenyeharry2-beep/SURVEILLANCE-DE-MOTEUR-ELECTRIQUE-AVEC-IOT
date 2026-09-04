<?php

declare(strict_types=1);

if (!defined('NOUVELLE_EVE_API')) {
    define('NOUVELLE_EVE_API', true);
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token, X-Session-Id');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/caisse.php';

function apiEnsureSession(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $sid = null;
    foreach (['sid', 'session_id'] as $key) {
        if (!empty($_GET[$key])) {
            $sid = trim((string) $_GET[$key]);
            break;
        }
    }

    if ($sid === null && !empty($_SERVER['HTTP_X_SESSION_ID'])) {
        $sid = trim((string) $_SERVER['HTTP_X_SESSION_ID']);
    }

    if ($sid === null) {
        $body = apiBody();
        if (!empty($body['sid'])) {
            $sid = trim((string) $body['sid']);
        } elseif (!empty($body['session_id'])) {
            $sid = trim((string) $body['session_id']);
        }
    }

    if ($sid !== null && preg_match('/^[a-z0-9,-]{16,128}$/i', $sid)) {
        session_id($sid);
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $ready = true;
}

function apiSetUserSession(array $user): string
{
    apiEnsureSession();
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_nom'] = (string) $user['nom'];
    $_SESSION['user_email'] = (string) $user['email'];
    $_SESSION['user_role'] = (string) $user['role'];
    $_SESSION['api_mobile'] = true;

    return session_id();
}

function apiCurrentSessionUser(PDO $db): ?array
{
    apiEnsureSession();
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $stmt = $db->prepare('
        SELECT id, nom, email, role
        FROM utilisateurs
        WHERE id = ? AND actif = 1
        LIMIT 1
    ');
    $stmt->execute([(int) $_SESSION['user_id']]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function apiClearUserSession(PDO $db, array $user): void
{
    apiEnsureSession();
    $db->prepare('DELETE FROM api_tokens WHERE user_id = ?')->execute([(int) $user['id']]);
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function apiJson(bool $success, $data = null, ?string $message = null, int $code = 200): void
{
    http_response_code($code);
    $payload = ['success' => $success];
    if ($message !== null) {
        $payload['message'] = $message;
    }
    if ($data !== null) {
        $payload['data'] = $data;
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function apiError(string $message, int $code = 400): void
{
    apiJson(false, null, $message, $code);
}

function apiBody(): array
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        $cached = [];
        return $cached;
    }
    $data = json_decode($raw, true);
    $cached = is_array($data) ? $data : [];
    return $cached;
}

function apiRoute(): string
{
    $route = $_GET['route'] ?? '';
    if ($route !== '') {
        return trim($route, '/');
    }

    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    $uri = preg_replace('#/api/index\.php#', '/api', $uri);
    $uri = preg_replace('#^/api/#', '', $uri);
    return trim($uri, '/');
}

function ensureApiSchema(PDO $db): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $db->exec('
        CREATE TABLE IF NOT EXISTS api_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(64) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
            INDEX idx_token (token),
            INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ');

    ensureCaisseTable($db);

    if (file_exists(__DIR__ . '/../includes/journee.php')) {
        require_once __DIR__ . '/../includes/journee.php';
        ensureJourneeSchema($db);
    }

    $ready = true;
}

function createApiToken(PDO $db, int $userId): string
{
    ensureApiSchema($db);
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+30 days'));

    $db->prepare('DELETE FROM api_tokens WHERE user_id = ?')->execute([$userId]);
    $db->prepare('INSERT INTO api_tokens (user_id, token, expires_at) VALUES (?, ?, ?)')
       ->execute([$userId, $token, $expires]);

    return $token;
}

function apiExtractToken(): ?string
{
    $candidates = [];

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if ($authHeader !== '' && preg_match('/Bearer\s+(\S+)/i', $authHeader, $m)) {
        $candidates[] = $m[1];
    }

    if (!empty($_SERVER['HTTP_X_AUTH_TOKEN'])) {
        $candidates[] = trim((string) $_SERVER['HTTP_X_AUTH_TOKEN']);
    }

    if (function_exists('getallheaders')) {
        foreach (getallheaders() as $name => $value) {
            $lower = strtolower((string) $name);
            if ($lower === 'authorization' && preg_match('/Bearer\s+(\S+)/i', (string) $value, $m)) {
                $candidates[] = $m[1];
            }
            if ($lower === 'x-auth-token' && trim((string) $value) !== '') {
                $candidates[] = trim((string) $value);
            }
        }
    }

    foreach (['token', 'access_token'] as $key) {
        if (!empty($_GET[$key])) {
            $candidates[] = trim((string) $_GET[$key]);
        }
    }

    $body = apiBody();
    if (!empty($body['token'])) {
        $candidates[] = trim((string) $body['token']);
    }

    foreach ($candidates as $candidate) {
        if ($candidate !== '') {
            return $candidate;
        }
    }

    return null;
}

function requireApiAuth(PDO $db): array
{
    $sessionUser = apiCurrentSessionUser($db);
    if ($sessionUser !== null) {
        return $sessionUser;
    }

    $token = apiExtractToken();
    if ($token === null) {
        apiError('Token manquant. Uploadez api/bootstrap.php sur le site puis reconnectez-vous.', 401);
    }

    $stmt = $db->prepare('
        SELECT u.id, u.nom, u.email, u.role
        FROM api_tokens t
        JOIN utilisateurs u ON u.id = t.user_id
        WHERE t.token = ? AND t.expires_at > NOW() AND u.actif = 1
        LIMIT 1
    ');
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        apiError('Session expirée. Reconnectez-vous.', 401);
    }

    return $user;
}

function createVente(PDO $db, array $user, array $body): array
{
    require_once __DIR__ . '/../includes/ventes.php';

    try {
        return createVenteTransaction($db, $user, $body);
    } catch (InvalidArgumentException $e) {
        apiError($e->getMessage());
    } catch (RuntimeException $e) {
        apiError($e->getMessage(), 403);
    } catch (Throwable $e) {
        apiError('Erreur lors de la vente.', 500);
    }
}

function venteDetailsSql(): string
{
    return '(SELECT GROUP_CONCAT(CONCAT(m.nom, " x", vl.quantite) SEPARATOR ", ")
             FROM vente_lignes vl JOIN medicaments m ON m.id = vl.medicament_id
             WHERE vl.vente_id = v.id) AS details';
}

function enrichVenteRow(array $row): array
{
    $devise = normalizeDevise($row['devise'] ?? 'CDF');
    $montant = (float) $row['montant_total'];

    return [
        'id' => (int) $row['id'],
        'numero' => $row['numero'],
        'date_vente' => $row['date_vente'],
        'montant_total' => $montant,
        'devise' => $devise,
        'client_nom' => $row['client_nom'],
        'vendeur' => $row['vendeur'] ?? null,
        'details' => $row['details'] ?? null,
        'notes' => $row['notes'] ?? null,
        'montant_cdf' => convertirDevise($montant, $devise, 'CDF'),
        'montant_usd' => convertirDevise($montant, $devise, 'USD'),
    ];
}

function fetchVentesListe(PDO $db, ?string $date = null, int $limit = 50): array
{
    $limit = max(1, min(200, $limit));
    $detailsSql = venteDetailsSql();

    if ($date !== null && $date !== '') {
        $stmt = $db->prepare("
            SELECT v.id, v.numero, v.date_vente, v.montant_total, COALESCE(v.devise, 'CDF') AS devise,
                   v.client_nom, v.notes, u.nom AS vendeur, {$detailsSql}
            FROM ventes v
            JOIN utilisateurs u ON u.id = v.utilisateur_id
            WHERE DATE(v.date_vente) = ?
            ORDER BY v.date_vente DESC
            LIMIT {$limit}
        ");
        $stmt->execute([$date]);
    } else {
        $stmt = $db->query("
            SELECT v.id, v.numero, v.date_vente, v.montant_total, COALESCE(v.devise, 'CDF') AS devise,
                   v.client_nom, v.notes, u.nom AS vendeur, {$detailsSql}
            FROM ventes v
            JOIN utilisateurs u ON u.id = v.utilisateur_id
            ORDER BY v.date_vente DESC
            LIMIT {$limit}
        ");
    }

    return array_map('enrichVenteRow', $stmt->fetchAll());
}

function fetchVenteRecu(PDO $db, int $id): array
{
    $stmt = $db->prepare('
        SELECT v.*, u.nom AS vendeur
        FROM ventes v
        JOIN utilisateurs u ON u.id = v.utilisateur_id
        WHERE v.id = ?
    ');
    $stmt->execute([$id]);
    $vente = $stmt->fetch();

    if (!$vente) {
        apiError('Reçu introuvable.', 404);
    }

    $lignes = $db->prepare('
        SELECT vl.*, m.code, m.nom
        FROM vente_lignes vl
        JOIN medicaments m ON m.id = vl.medicament_id
        WHERE vl.vente_id = ?
    ');
    $lignes->execute([$id]);
    $details = $lignes->fetchAll();

    $devise = normalizeDevise($vente['devise'] ?? 'CDF');
    $montant = (float) $vente['montant_total'];

    return [
        'vente' => [
            'id' => (int) $vente['id'],
            'numero' => $vente['numero'],
            'date_vente' => $vente['date_vente'],
            'montant_total' => $montant,
            'devise' => $devise,
            'client_nom' => $vente['client_nom'],
            'notes' => $vente['notes'],
            'vendeur' => $vente['vendeur'],
            'montant_lettres' => montantEnLettres($montant, $devise),
            'montant_cdf' => convertirDevise($montant, $devise, 'CDF'),
            'montant_usd' => convertirDevise($montant, $devise, 'USD'),
            'equivalent' => formatDualMoney($montant, $devise),
        ],
        'lignes' => array_map(static function (array $l): array {
            return [
                'code' => $l['code'],
                'nom' => $l['nom'],
                'quantite' => (int) $l['quantite'],
                'prix_unitaire' => (float) $l['prix_unitaire'],
                'sous_total' => (float) $l['sous_total'],
            ];
        }, $details),
        'pharmacie' => [
            'nom' => appName(),
            'tagline' => appTagline(),
            'adresse' => appAddress(),
            'telephone' => appPhone(),
            'url' => appUrl(),
        ],
    ];
}

function reportSummary(PDO $db, string $debut, string $fin): array
{
    $taux = getTauxUsdCdf();
    $totaux = sommeVentesDual($db, 'DATE(date_vente) BETWEEN ? AND ?', [$debut, $fin]);

    $stmt = $db->prepare('
        SELECT COALESCE(devise, "CDF") AS devise, COUNT(*) AS nb_ventes, SUM(montant_total) AS total
        FROM ventes
        WHERE DATE(date_vente) BETWEEN ? AND ?
        GROUP BY COALESCE(devise, "CDF")
    ');
    $stmt->execute([$debut, $fin]);
    $parDevise = array_map(static function (array $row) use ($taux): array {
        $devise = normalizeDevise($row['devise']);
        $total = (float) $row['total'];

        return [
            'devise' => $devise,
            'nb_ventes' => (int) $row['nb_ventes'],
            'total' => $total,
            'equivalent_cdf' => convertirDevise($total, $devise, 'CDF'),
            'equivalent_usd' => convertirDevise($total, $devise, 'USD'),
        ];
    }, $stmt->fetchAll());

    $detailsSql = venteDetailsSql();
    $stmt = $db->prepare("
        SELECT v.id, v.numero, v.date_vente, v.montant_total, COALESCE(v.devise, 'CDF') AS devise,
               v.client_nom, v.notes, u.nom AS vendeur, {$detailsSql}
        FROM ventes v
        JOIN utilisateurs u ON u.id = v.utilisateur_id
        WHERE DATE(v.date_vente) BETWEEN ? AND ?
        ORDER BY v.date_vente DESC
    ");
    $stmt->execute([$debut, $fin]);
    $ventes = array_map('enrichVenteRow', $stmt->fetchAll());

    $result = [
        'periode' => ['debut' => $debut, 'fin' => $fin],
        'taux_usd_cdf' => $taux,
        'totaux' => [
            'cdf_brut' => (float) $totaux['total_cdf_brut'],
            'usd_brut' => (float) $totaux['total_usd_brut'],
            'cdf_converti' => (float) $totaux['total_cdf'],
            'usd_converti' => (float) $totaux['total_usd'],
            'nb_ventes' => count($ventes),
        ],
        'par_devise' => $parDevise,
        'ventes' => $ventes,
    ];

    if ($debut === $fin) {
        $result['caisse'] = resumeCaisseJour($db, $debut);
        $result['mouvements_caisse'] = fetchMouvementsCaisse($db, $debut);
    }

    return $result;
}

function fetchAlertes(PDO $db, string $type): array
{
    $mois = getAlerteExpirationMois();
    $base = '
        SELECT m.id, m.code, m.nom, m.quantite_stock, m.seuil_alerte,
               m.date_fabrication, m.date_expiration, c.nom AS categorie_nom
        FROM medicaments m
        LEFT JOIN categories c ON c.id = m.categorie_id
        WHERE m.actif = 1
    ';

    switch ($type) {
        case 'stock':
            $sql = $base . ' AND m.quantite_stock <= m.seuil_alerte ORDER BY m.quantite_stock ASC';
            break;
        case 'ecouler':
            $sql = $base . ' AND m.date_expiration IS NOT NULL
                AND m.date_expiration >= CURDATE()
                AND m.date_expiration <= DATE_ADD(CURDATE(), INTERVAL ' . (int) $mois . ' MONTH)
                ORDER BY m.date_expiration ASC';
            break;
        case 'expiration':
            $sql = $base . ' AND m.date_expiration IS NOT NULL AND m.date_expiration < CURDATE()
                ORDER BY m.date_expiration ASC';
            break;
        default:
            $sql = $base . ' AND (
                m.quantite_stock <= m.seuil_alerte
                OR (m.date_expiration IS NOT NULL AND m.date_expiration <= DATE_ADD(CURDATE(), INTERVAL ' . (int) $mois . ' MONTH))
            ) ORDER BY m.date_expiration ASC, m.quantite_stock ASC';
    }

    $rows = $db->query($sql)->fetchAll();

    return array_map(static function (array $row): array {
        return [
            'id' => (int) $row['id'],
            'code' => $row['code'],
            'nom' => $row['nom'],
            'categorie' => $row['categorie_nom'],
            'quantite_stock' => (int) $row['quantite_stock'],
            'seuil_alerte' => (int) $row['seuil_alerte'],
            'date_fabrication' => $row['date_fabrication'],
            'date_expiration' => $row['date_expiration'],
            'statut' => expirationStatus($row['date_expiration']),
            'statut_label' => expirationStatusLabel($row['date_expiration']),
            'stock_faible' => (int) $row['quantite_stock'] <= (int) $row['seuil_alerte'],
        ];
    }, $rows);
}
