<?php
/**
 * Point d'entrée - Super Genies API
 * URL: https://supergenies2026.site.je/
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'app' => 'Super Genies - API Suivi Paiements',
    'version' => '1.0.0',
    'school' => 'C.S. LES SUPER GENIES',
    'endpoints' => [
        'GET /api/student.php?matricule=CSLSG-2026-2027-00167' => 'Consultation élève',
        'GET /api/info/inscriptions.php' => 'Conditions d\'admission',
        'GET /api/info/trousseau.php' => 'Trousseau et équipements',
        'POST /api/admin/login.php' => 'Connexion administrateur',
        'POST /api/admin/upload.php' => 'Import PDF (header X-Admin-Token)',
        'GET /api/admin/stats.php' => 'Statistiques admin',
    ],
    'documentation' => 'Voir README.md',
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
