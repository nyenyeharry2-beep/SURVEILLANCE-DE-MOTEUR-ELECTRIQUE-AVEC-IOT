<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$route = apiRoute();

try {
    if ($route === 'auth/login' && $method === 'POST') {
        $body = apiBody();
        $email = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';

        if ($email === '' || $password === '') {
            apiError('Email et mot de passe requis.');
        }

        $stmt = $db->prepare('SELECT * FROM utilisateurs WHERE email = ? AND actif = 1 LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['mot_de_passe'])) {
            apiError('Identifiants incorrects.', 401);
        }

        $token = createApiToken($db, (int) $user['id']);

        apiJson(true, [
            'token' => $token,
            'user' => [
                'id' => (int) $user['id'],
                'nom' => $user['nom'],
                'email' => $user['email'],
                'role' => $user['role'],
            ],
            'app' => [
                'name' => appName(),
                'url' => appUrl(),
            ],
        ], 'Connexion réussie.');
    }

    if ($route === 'auth/logout' && $method === 'POST') {
        $user = requireApiAuth($db);
        $db->prepare('DELETE FROM api_tokens WHERE user_id = ?')->execute([$user['id']]);
        apiJson(true, null, 'Déconnexion réussie.');
    }

    if ($route === 'medicaments' && $method === 'GET') {
        requireApiAuth($db);
        $q = trim($_GET['q'] ?? '');
        if ($q !== '') {
            $stmt = $db->prepare('
                SELECT id, code, nom, prix_vente, quantite_stock, date_expiration
                FROM medicaments
                WHERE actif = 1 AND quantite_stock > 0
                  AND (nom LIKE ? OR code LIKE ?)
                ORDER BY nom LIMIT 100
            ');
            $like = '%' . $q . '%';
            $stmt->execute([$like, $like]);
        } else {
            $stmt = $db->query('
                SELECT id, code, nom, prix_vente, quantite_stock, date_expiration
                FROM medicaments
                WHERE actif = 1 AND quantite_stock > 0
                ORDER BY nom
            ');
        }

        apiJson(true, ['medicaments' => $stmt->fetchAll()]);
    }

    if ($route === 'ventes' && $method === 'POST') {
        $user = requireApiAuth($db);
        $result = createVente($db, $user, apiBody());
        apiJson(true, $result, 'Vente enregistrée.');
    }

    if ($route === 'ventes' && $method === 'GET') {
        requireApiAuth($db);
        $debut = $_GET['debut'] ?? date('Y-m-d');
        $fin = $_GET['fin'] ?? date('Y-m-d');
        apiJson(true, reportSummary($db, $debut, $fin));
    }

    if ($route === 'rapports/jour' && $method === 'GET') {
        requireApiAuth($db);
        $date = $_GET['date'] ?? date('Y-m-d');
        apiJson(true, reportSummary($db, $date, $date));
    }

    if ($route === 'rapports/mois' && $method === 'GET') {
        requireApiAuth($db);
        $annee = (int) ($_GET['annee'] ?? date('Y'));
        $mois = (int) ($_GET['mois'] ?? date('n'));
        $debut = sprintf('%04d-%02d-01', $annee, $mois);
        $fin = date('Y-m-t', strtotime($debut));
        apiJson(true, reportSummary($db, $debut, $fin));
    }

    if ($route === 'alertes' && $method === 'GET') {
        requireApiAuth($db);
        $type = $_GET['type'] ?? 'all';
        apiJson(true, [
            'type' => $type,
            'alertes' => fetchAlertes($db, $type),
            'alerte_expiration_mois' => getAlerteExpirationMois(),
        ]);
    }

    if ($route === 'config' && $method === 'GET') {
        requireApiAuth($db);
        apiJson(true, [
            'app_name' => appName(),
            'taux_usd_cdf' => getTauxUsdCdf(),
            'devise_defaut' => defined('DEVISE_DEFAUT') ? DEVISE_DEFAUT : 'CDF',
            'alerte_expiration_mois' => getAlerteExpirationMois(),
        ]);
    }

    apiError('Route introuvable : ' . $route, 404);
} catch (Throwable $e) {
    apiError('Erreur serveur.', 500);
}
