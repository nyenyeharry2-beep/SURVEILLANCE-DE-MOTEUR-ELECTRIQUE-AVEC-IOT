<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Méthode POST requise', 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$nomParent = trim($input['nom_parent'] ?? '');
$telephone = trim($input['telephone_parent'] ?? '');
$matricule = strtoupper(trim($input['matricule'] ?? ''));
$motif = trim($input['motif'] ?? 'autre');
$message = trim($input['message'] ?? '');

$motifsValides = [
    'paiement_non_enregistre',
    'montant_incorrect',
    'double_paiement',
    'probleme_inscription',
    'autre',
];

if ($nomParent === '' || $telephone === '' || $matricule === '' || $message === '') {
    jsonError('Champs requis: nom_parent, telephone_parent, matricule, message');
}

if (!in_array($motif, $motifsValides, true)) {
    jsonError('Motif invalide');
}

if (strlen($message) < 10) {
    jsonError('Le message doit contenir au moins 10 caractères');
}

try {
    $pdo = getPdo();

    $nomEleve = trim($input['nom_eleve'] ?? '');
    $prenomEleve = trim($input['prenom_eleve'] ?? '');
    $classeEleve = trim($input['classe_eleve'] ?? '');
    $sectionEleve = trim($input['section_eleve'] ?? '');

    // Compléter automatiquement les infos élève depuis la base
    $stmt = $pdo->prepare('SELECT nom, prenom, classe, section FROM students WHERE matricule = ? LIMIT 1');
    $stmt->execute([$matricule]);
    $student = $stmt->fetch();

    if ($student) {
        if ($nomEleve === '') {
            $nomEleve = $student['nom'];
        }
        if ($prenomEleve === '') {
            $prenomEleve = $student['prenom'];
        }
        if ($classeEleve === '') {
            $classeEleve = $student['classe'];
        }
        if ($sectionEleve === '') {
            $sectionEleve = $student['section'] ?? '';
        }
    }

    $insert = $pdo->prepare('
        INSERT INTO parent_messages
        (nom_parent, telephone_parent, matricule, nom_eleve, prenom_eleve, classe_eleve, section_eleve, motif, message)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $insert->execute([
        $nomParent,
        $telephone,
        $matricule,
        $nomEleve ?: null,
        $prenomEleve ?: null,
        $classeEleve ?: null,
        $sectionEleve ?: null,
        $motif,
        $message,
    ]);

    $messageId = (int) $pdo->lastInsertId();

    jsonResponse([
        'success' => true,
        'message_id' => $messageId,
        'message' => 'Votre signalement a été transmis à la facturation pour vérification.',
    ], 201);
} catch (Throwable $e) {
    jsonError('Impossible d\'envoyer votre message pour le moment. Veuillez réessayer.', 500);
}
