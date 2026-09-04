<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

try {
    $db = getDB();
    ensureApiSchema($db);
    apiEnsureSession();
} catch (Throwable $e) {
    apiError('Connexion base de données impossible.', 500);
}

$method = $_SERVER['REQUEST_METHOD'];
$route = apiRoute();

try {
    if ($route === 'ping' && $method === 'GET') {
        apiJson(true, ['status' => 'ok', 'version' => '1.0'], 'API active.');
    }

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
        $sessionId = apiSetUserSession($user);

        apiJson(true, [
            'token' => $token,
            'session_id' => $sessionId,
            'user' => [
                'id' => (int) $user['id'],
                'nom' => $user['nom'],
                'email' => $user['email'],
                'role' => $user['role'],
            ],
            'app' => [
                'name' => appName(),
            ],
        ], 'Connexion réussie.');
    }

    if ($route === 'auth/logout' && $method === 'POST') {
        $user = requireApiAuth($db);
        apiClearUserSession($db, $user);
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
            $prefix = $q . '%';
            $stmt->execute([$prefix, $prefix]);
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

    if ($route === 'stock' && $method === 'GET') {
        requireApiAuth($db);
        $q = trim($_GET['q'] ?? '');
        if ($q !== '') {
            $stmt = $db->prepare('
                SELECT m.id, m.code, m.nom, m.prix_vente, m.quantite_stock, m.seuil_alerte,
                       m.date_expiration, c.nom AS categorie_nom
                FROM medicaments m
                LEFT JOIN categories c ON c.id = m.categorie_id
                WHERE m.actif = 1 AND (m.nom LIKE ? OR m.code LIKE ?)
                ORDER BY m.nom LIMIT 200
            ');
            $prefix = $q . '%';
            $stmt->execute([$prefix, $prefix]);
        } else {
            $stmt = $db->query('
                SELECT m.id, m.code, m.nom, m.prix_vente, m.quantite_stock, m.seuil_alerte,
                       m.date_expiration, c.nom AS categorie_nom
                FROM medicaments m
                LEFT JOIN categories c ON c.id = m.categorie_id
                WHERE m.actif = 1
                ORDER BY m.nom
            ');
        }

        $rows = array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'code' => $row['code'],
                'nom' => $row['nom'],
                'categorie' => $row['categorie_nom'],
                'prix_vente' => (float) $row['prix_vente'],
                'quantite_stock' => (int) $row['quantite_stock'],
                'seuil_alerte' => (int) $row['seuil_alerte'],
                'date_expiration' => $row['date_expiration'],
                'stock_faible' => (int) $row['quantite_stock'] <= (int) $row['seuil_alerte'],
                'statut_expiration' => expirationStatusLabel($row['date_expiration']),
            ];
        }, $stmt->fetchAll());

        apiJson(true, ['stock' => $rows]);
    }

    if ($route === 'ventes' && $method === 'POST') {
        $user = requireApiAuth($db);
        $result = createVente($db, $user, apiBody());
        apiJson(true, $result, 'Vente enregistrée.');
    }

    if ($route === 'ventes' && $method === 'GET') {
        requireApiAuth($db);
        if (($_GET['liste'] ?? '') === '1') {
            $date = trim($_GET['date'] ?? '');
            if ($date === '') {
                $date = date('Y-m-d');
            }
            $limit = (int) ($_GET['limit'] ?? 50);
            apiJson(true, ['ventes' => fetchVentesListe($db, $date, $limit), 'date' => $date]);
        }
        $debut = $_GET['debut'] ?? date('Y-m-d');
        $fin = $_GET['fin'] ?? date('Y-m-d');
        apiJson(true, reportSummary($db, $debut, $fin));
    }

    if ($route === 'recu' && $method === 'GET') {
        requireApiAuth($db);
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            apiError('Identifiant de vente requis.');
        }
        apiJson(true, fetchVenteRecu($db, $id));
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

    if ($route === 'caisse' && $method === 'GET') {
        requireApiAuth($db);
        $date = trim($_GET['date'] ?? '') ?: date('Y-m-d');
        apiJson(true, [
            'date' => $date,
            'resume' => resumeCaisseJour($db, $date),
            'mouvements' => fetchMouvementsCaisse($db, $date),
        ]);
    }

    if ($route === 'caisse' && $method === 'POST') {
        $user = requireApiAuth($db);
        $result = createMouvementCaisse($db, $user, apiBody());
        apiJson(true, $result, 'Mouvement enregistré.');
    }

    if ($route === 'journee' && $method === 'GET') {
        requireApiAuth($db);
        require_once __DIR__ . '/../includes/journee.php';
        $date = trim($_GET['date'] ?? '') ?: date('Y-m-d');
        $status = getJourneeStatus($db, $date);
        apiJson(true, $status);
    }

    if ($route === 'journee/open' && $method === 'POST') {
        $user = requireApiAuth($db);
        if (!in_array($user['role'], ['admin', 'pharmacien'], true)) {
            apiError('Seul le superviseur peut ouvrir une journée.', 403);
        }
        require_once __DIR__ . '/../includes/journee.php';
        $body = apiBody();
        $date = trim($body['date'] ?? '') ?: date('Y-m-d');
        $fondCdf = (float) ($body['fond_caisse_cdf'] ?? 0);
        $fondUsd = (float) ($body['fond_caisse_usd'] ?? 0);
        $taux = (float) ($body['taux_usd_cdf'] ?? getTauxUsdCdf());
        if ($taux <= 0) {
            apiError('Taux USD/CDF obligatoire.');
        }
        $id = openJourneeWithCaisse($db, $date, $fondCdf, $fondUsd, $taux);
        apiJson(true, array_merge(getJourneeStatus($db, $date), ['journal_id' => $id]), 'Journée ouverte.');
    }

    if ($route === 'journee/close' && $method === 'POST') {
        $user = requireApiAuth($db);
        if (!in_array($user['role'], ['admin', 'pharmacien'], true)) {
            apiError('Seul le superviseur peut clôturer une journée.', 403);
        }
        require_once __DIR__ . '/../includes/journee.php';
        $body = apiBody();
        $date = trim($body['date'] ?? '') ?: date('Y-m-d');
        $caisseCdf = (float) ($body['caisse_cloture_cdf'] ?? 0);
        $caisseUsd = (float) ($body['caisse_cloture_usd'] ?? 0);
        closeJourneeWithCaisse($db, $date, $caisseCdf, $caisseUsd, (int) $user['id']);
        $nextDate = date('Y-m-d', strtotime($date . ' +1 day'));
        apiJson(true, [
            'closed' => getJourneeStatus($db, $date),
            'next_date' => $nextDate,
        ], 'Journée clôturée.');
    }

    if ($route === 'rapports/jours' && $method === 'GET') {
        requireApiAuth($db);
        require_once __DIR__ . '/../includes/journee.php';
        $debut = $_GET['debut'] ?? date('Y-m-01');
        $fin = $_GET['fin'] ?? date('Y-m-d');
        apiJson(true, ['jours' => fetchRapportParJours($db, $debut, $fin)]);
    }

    apiError('Route introuvable : ' . $route, 404);
} catch (Throwable $e) {
    apiError('Erreur serveur.', 500);
}
