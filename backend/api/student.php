<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

$matricule = trim($_GET['matricule'] ?? '');

if ($matricule === '') {
    jsonError('Matricule requis. Exemple: CSLSG-2026-2027-00167');
}

$matricule = strtoupper($matricule);

try {
    $pdo = getPdo();

    $stmt = $pdo->prepare('SELECT * FROM students WHERE matricule = ? LIMIT 1');
    $stmt->execute([$matricule]);
    $student = $stmt->fetch();

    if (!$student) {
        jsonError('Aucun élève trouvé pour ce matricule.', 404);
    }

    $feesStmt = $pdo->prepare('
        SELECT id, label, montant_du, montant_paye, statut, mois, annee_scolaire, notes
        FROM student_fees
        WHERE student_id = ?
        ORDER BY FIELD(statut, "impaye", "partiel", "paye", "exempt"), mois ASC, label ASC
    ');
    $feesStmt->execute([$student['id']]);
    $fees = $feesStmt->fetchAll();

    $totalDu = 0.0;
    $totalPaye = 0.0;
    foreach ($fees as &$fee) {
        $fee['montant_du'] = (float) $fee['montant_du'];
        $fee['montant_paye'] = (float) $fee['montant_paye'];
        $fee['solde'] = round($fee['montant_du'] - $fee['montant_paye'], 2);
        $totalDu += $fee['montant_du'];
        $totalPaye += $fee['montant_paye'];
    }
    unset($fee);

    $config = getAppConfig();

    jsonResponse([
        'success' => true,
        'school' => [
            'name' => $config['school_name'],
            'motto' => $config['school_motto'],
            'phone' => $config['school_phone'],
        ],
        'student' => [
            'matricule' => $student['matricule'],
            'nom' => $student['nom'],
            'prenom' => $student['prenom'],
            'nom_complet' => trim($student['nom'] . ' ' . $student['prenom']),
            'genre' => $student['genre'],
            'classe' => $student['classe'],
            'section' => $student['section'],
            'annee_scolaire' => $student['annee_scolaire'],
            'statut_inscription' => $student['statut_inscription'],
            'date_inscription' => $student['date_inscription'],
            'telephone' => $student['telephone'],
        ],
        'fees' => $fees,
        'summary' => [
            'total_du' => round($totalDu, 2),
            'total_paye' => round($totalPaye, 2),
            'solde' => round($totalDu - $totalPaye, 2),
            'nb_frais' => count($fees),
            'nb_impayes' => count(array_filter($fees, fn($f) => $f['statut'] === 'impaye')),
            'en_ordre' => ($totalDu - $totalPaye) <= 0 && count($fees) > 0,
        ],
    ]);
} catch (Throwable $e) {
    jsonError('Erreur serveur: ' . $e->getMessage(), 500);
}
