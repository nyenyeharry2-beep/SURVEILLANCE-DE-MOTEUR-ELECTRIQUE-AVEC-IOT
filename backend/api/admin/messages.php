<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

requireAdminAuth();

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getPdo();
    ensureParentMessagesTable($pdo);

    if ($method === 'GET') {
        $statut = $_GET['statut'] ?? null;
        $limit = min(100, max(1, (int) ($_GET['limit'] ?? 50)));

        $sql = '
            SELECT id, nom_parent, telephone_parent, matricule,
                   nom_eleve, prenom_eleve, classe_eleve, section_eleve,
                   motif, message, statut, note_admin, created_at, updated_at
            FROM parent_messages
        ';
        $params = [];

        if ($statut !== null && $statut !== '' && $statut !== 'all') {
            $sql .= ' WHERE statut = ?';
            $params[] = $statut;
        }

        $sql .= ' ORDER BY FIELD(statut, "nouveau", "en_cours", "traite"), created_at DESC LIMIT ' . $limit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $messages = $stmt->fetchAll();

        $counts = $pdo->query('
            SELECT statut, COUNT(*) as total FROM parent_messages GROUP BY statut
        ')->fetchAll(PDO::FETCH_KEY_PAIR);

        jsonResponse([
            'success' => true,
            'messages' => $messages,
            'counts' => [
                'nouveau' => (int) ($counts['nouveau'] ?? 0),
                'en_cours' => (int) ($counts['en_cours'] ?? 0),
                'traite' => (int) ($counts['traite'] ?? 0),
                'total' => array_sum(array_map('intval', $counts)),
            ],
        ]);
    }

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $action = $input['action'] ?? 'update_status';
        $id = (int) ($input['id'] ?? 0);

        if ($id <= 0) {
            jsonError('ID message requis');
        }

        if ($action === 'update_status') {
            $statut = $input['statut'] ?? '';
            if (!in_array($statut, ['nouveau', 'en_cours', 'traite'], true)) {
                jsonError('Statut invalide');
            }
            $noteAdmin = trim($input['note_admin'] ?? '');

            $stmt = $pdo->prepare('UPDATE parent_messages SET statut = ?, note_admin = ?, reponse_at = IF(? != "", NOW(), reponse_at) WHERE id = ?');
            $stmt->execute([$statut, $noteAdmin !== '' ? $noteAdmin : null, $noteAdmin, $id]);

            if ($stmt->rowCount() === 0) {
                jsonError('Message introuvable', 404);
            }

            jsonResponse([
                'success' => true,
                'message' => 'Statut mis à jour',
            ]);
        }

        if ($action === 'delete') {
            $stmt = $pdo->prepare('DELETE FROM parent_messages WHERE id = ?');
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 0) {
                jsonError('Message introuvable', 404);
            }
            jsonResponse(['success' => true, 'message' => 'Message supprimé']);
        }

        jsonError('Action inconnue');
    }

    jsonError('Méthode non supportée', 405);
} catch (Throwable $e) {
    jsonError('Erreur serveur: ' . $e->getMessage(), 500);
}
