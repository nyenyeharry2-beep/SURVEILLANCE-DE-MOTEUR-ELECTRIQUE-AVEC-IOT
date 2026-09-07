<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

requireAdminAuth();

try {
    $pdo = getPdo();

    $students = (int) $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
    $fees = (int) $pdo->query('SELECT COUNT(*) FROM student_fees')->fetchColumn();
    $impayes = (int) $pdo->query('SELECT COUNT(*) FROM student_fees WHERE statut IN ("impaye", "partiel")')->fetchColumn();

    $byClass = $pdo->query('
        SELECT classe, section, COUNT(*) as total
        FROM students
        GROUP BY classe, section
        ORDER BY section, classe
    ')->fetchAll();

    $imports = $pdo->query('
        SELECT id, type_import, fichier, classe_detectee, section_detectee, lignes_traitees, lignes_erreur, imported_at
        FROM import_logs
        ORDER BY imported_at DESC
        LIMIT 20
    ')->fetchAll();

    jsonResponse([
        'success' => true,
        'stats' => [
            'total_students' => $students,
            'total_fees' => $fees,
            'fees_impayes' => $impayes,
        ],
        'par_classe' => $byClass,
        'derniers_imports' => $imports,
    ]);
} catch (Throwable $e) {
    jsonError('Erreur serveur: ' . $e->getMessage(), 500);
}
