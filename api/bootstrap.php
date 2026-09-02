<?php

declare(strict_types=1);

if (!defined('NOUVELLE_EVE_API')) {
    define('NOUVELLE_EVE_API', true);
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

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
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
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

function requireApiAuth(PDO $db): array
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/Bearer\s+(\S+)/i', $header, $m)) {
        apiError('Token manquant.', 401);
    }

    $stmt = $db->prepare('
        SELECT u.id, u.nom, u.email, u.role
        FROM api_tokens t
        JOIN utilisateurs u ON u.id = t.user_id
        WHERE t.token = ? AND t.expires_at > NOW() AND u.actif = 1
        LIMIT 1
    ');
    $stmt->execute([$m[1]]);
    $user = $stmt->fetch();

    if (!$user) {
        apiError('Session expirée. Reconnectez-vous.', 401);
    }

    return $user;
}

function createVente(PDO $db, array $user, array $body): array
{
    $medicamentId = (int) ($body['medicament_id'] ?? 0);
    $quantite = (int) ($body['quantite'] ?? 0);
    $devise = normalizeDevise($body['devise'] ?? 'CDF');
    $clientNom = trim($body['client_nom'] ?? '');
    $notes = trim($body['notes'] ?? '');
    $prixUnitaire = (float) ($body['prix_unitaire'] ?? 0);

    if ($medicamentId <= 0 || $quantite <= 0) {
        apiError('Médicament et quantité obligatoires.');
    }

    $stmt = $db->prepare('SELECT * FROM medicaments WHERE id = ? AND actif = 1');
    $stmt->execute([$medicamentId]);
    $med = $stmt->fetch();

    if (!$med) {
        apiError('Médicament introuvable.');
    }

    if ((int) $med['quantite_stock'] < $quantite) {
        apiError('Stock insuffisant. Disponible : ' . $med['quantite_stock'], 409);
    }

    if ($prixUnitaire <= 0) {
        $prixUnitaire = convertirDevise((float) $med['prix_vente'], 'CDF', $devise);
    }

    $sousTotal = $quantite * $prixUnitaire;
    $numero = 'VTE-' . date('Ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);

    $db->beginTransaction();

    try {
        $db->prepare('INSERT INTO ventes (numero, utilisateur_id, client_nom, montant_total, devise, notes) VALUES (?,?,?,?,?,?)')
           ->execute([$numero, $user['id'], $clientNom ?: null, $sousTotal, $devise, $notes ?: null]);
        $venteId = (int) $db->lastInsertId();

        $db->prepare('INSERT INTO vente_lignes (vente_id, medicament_id, quantite, prix_unitaire, sous_total) VALUES (?,?,?,?,?)')
           ->execute([$venteId, $medicamentId, $quantite, $prixUnitaire, $sousTotal]);

        $db->prepare('UPDATE medicaments SET quantite_stock = quantite_stock - ? WHERE id = ?')
           ->execute([$quantite, $medicamentId]);

        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        apiError('Erreur lors de la vente.', 500);
    }

    return [
        'id' => $venteId,
        'numero' => $numero,
        'montant_total' => $sousTotal,
        'devise' => $devise,
        'medicament' => $med['nom'],
        'quantite' => $quantite,
    ];
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

    return [
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
