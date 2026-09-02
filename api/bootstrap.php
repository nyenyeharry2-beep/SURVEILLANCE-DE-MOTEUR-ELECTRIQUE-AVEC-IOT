<?php

declare(strict_types=1);

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

function createApiToken(PDO $db, int $userId): string
{
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

function reportSummary(PDO $db, string $debut, string $fin): array
{
    $totaux = sommeVentesDual($db, 'DATE(date_vente) BETWEEN ? AND ?', [$debut, $fin]);

    $stmt = $db->prepare('
        SELECT COALESCE(devise, "CDF") AS devise, COUNT(*) AS nb_ventes, SUM(montant_total) AS total
        FROM ventes
        WHERE DATE(date_vente) BETWEEN ? AND ?
        GROUP BY COALESCE(devise, "CDF")
    ');
    $stmt->execute([$debut, $fin]);
    $parDevise = $stmt->fetchAll();

    $stmt = $db->prepare('
        SELECT v.id, v.numero, v.date_vente, v.montant_total, COALESCE(v.devise, "CDF") AS devise,
               v.client_nom, u.nom AS vendeur
        FROM ventes v
        JOIN utilisateurs u ON u.id = v.utilisateur_id
        WHERE DATE(v.date_vente) BETWEEN ? AND ?
        ORDER BY v.date_vente DESC
    ');
    $stmt->execute([$debut, $fin]);
    $ventes = $stmt->fetchAll();

    return [
        'periode' => ['debut' => $debut, 'fin' => $fin],
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
